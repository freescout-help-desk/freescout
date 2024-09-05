<?php

/*
 * Copyright (C) 2016 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork;

use Normalizer as n;

/**
 * UTF-8 Grapheme Cluster aware string manipulations implementing the quasi complete
 * set of native PHP string functions that need UTF-8 awareness and more.
 * Missing are printf-family functions.
 */
class Utf8
{
    protected static $pathPrefix;
    protected static $commonCaseFold = array(
        array('µ','ſ',"\xCD\x85",'ς',"\xCF\x90","\xCF\x91","\xCF\x95","\xCF\x96","\xCF\xB0","\xCF\xB1","\xCF\xB5","\xE1\xBA\x9B","\xE1\xBE\xBE"),
        array('μ','s','ι',       'σ','β',       'θ',       'φ',       'π',       'κ',       'ρ',       'ε',       "\xE1\xB9\xA1",'ι'),
    );
    protected static $cp1252 = array('','','','','','','','','','','','','','','','','','','','','','','','','','','');
    protected static $utf8   = array('€','‚','ƒ','„','…','†','‡','ˆ','‰','Š','‹','Œ','Ž','‘','’','“','”','•','–','—','˜','™','š','›','œ','ž','Ÿ');

    public static function isUtf8($s)
    {
        return (bool) preg_match('//u', $s); // Since PHP 5.2.5, this also excludes invalid five and six bytes sequences
    }

    // Generic UTF-8 to ASCII transliteration

    public static function toAscii($s, $subst_chr = '?')
    {
        if (preg_match("/[\x80-\xFF]/", $s)) {
            static $translitExtra = array();
            $translitExtra or $translitExtra = static::getData('translit_extra');

            $s = n::normalize($s, n::NFKC);

            $glibc = 'glibc' === ICONV_IMPL;

            preg_match_all('/./u', $s, $s);

            foreach ($s[0] as &$c) {
                if (!isset($c[1])) {
                    continue;
                }

                if ($glibc) {
                    $t = iconv('UTF-8', 'ASCII//TRANSLIT', $c);
                } else {
                    $t = iconv('UTF-8', 'ASCII//IGNORE//TRANSLIT', $c);

                    if (!isset($t[0])) {
                        $t = '?';
                    } elseif (isset($t[1])) {
                        $t = ltrim($t, '\'`"^~');
                    }
                }

                if ('?' === $t) {
                    if (isset($translitExtra[$c])) {
                        $t = $translitExtra[$c];
                    } else {
                        $t = n::normalize($c, n::NFD);

                        if ($t[0] < "\x80") {
                            $t = $t[0];
                        } else {
                            $t = $subst_chr;
                        }
                    }
                }

                $c = $t;
            }

            $s = implode('', $s[0]);
        }

        return $s;
    }

    public static function wrapPath($path = '')
    {
        if (null === static::$pathPrefix) {
            static $hasWfio;
            isset($hasWfio) or $hasWfio = extension_loaded('wfio');

            if ($hasWfio) {
                static::$pathPrefix = 'wfio://';
            } elseif ('\\' === DIRECTORY_SEPARATOR && class_exists('COM', false)) {
                static::$pathPrefix = 'utf8'.mt_rand();
                stream_wrapper_register(static::$pathPrefix, 'Patchwork\Utf8\WindowsStreamWrapper');
                static::$pathPrefix .= '://';
            } else {
                if ('\\' === DIRECTORY_SEPARATOR) {
                    trigger_error('The `wfio` or `com_dotnet` extension is required to handle UTF-8 filesystem access on Windows');
                }
                static::$pathPrefix = 'file://';
            }
        }

        return static::$pathPrefix.$path;
    }

    public static function filter($var, $normalization_form = 4 /* n::NFC */, $leading_combining = '◌')
    {
        switch (gettype($var)) {
            case 'array':
                foreach ($var as $k => $v) {
                    $var[$k] = static::filter($v, $normalization_form, $leading_combining);
                }
                break;

            case 'object':
                foreach ($var as $k => $v) {
                    $var->$k = static::filter($v, $normalization_form, $leading_combining);
                }
                break;

            case 'string':
                if (false !== strpos($var, "\r")) {
                    // Workaround https://bugs.php.net/65732
                    $var = str_replace("\r\n", "\n", $var);
                    $var = strtr($var, "\r", "\n");
                }

                if (preg_match('/[\x80-\xFF]/', $var)) {
                    if (n::isNormalized($var, $normalization_form)) {
                        $n = '-';
                    } else {
                        $n = n::normalize($var, $normalization_form);
                        if (isset($n[0])) {
                            $var = $n;
                        } else {
                            $var = static::utf8_encode($var);
                        }
                    }

                    if ($var[0] >= "\x80" && isset($n[0], $leading_combining[0]) && preg_match('/^\p{Mn}/u', $var)) {
                        // Prevent leading combining chars
                        // for NFC-safe concatenations.
                        $var = $leading_combining.$var;
                    }
                }
                break;
        }

        return $var;
    }

    // Unicode transformation for caseless matching
    // see http://unicode.org/reports/tr21/tr21-5.html

    public static function strtocasefold($s, $full = true)
    {
        $s = str_replace(self::$commonCaseFold[0], self::$commonCaseFold[1], $s);

        if ($full) {
            static $fullCaseFold = false;
            $fullCaseFold or $fullCaseFold = static::getData('caseFolding_full');

            $s = str_replace($fullCaseFold[0], $fullCaseFold[1], $s);
        }

        return static::strtolower($s);
    }

    // Generic case sensitive collation support for self::strnatcmp()

    public static function strtonatfold($s)
    {
        $s = n::normalize($s, n::NFD);

        return preg_replace('/\p{Mn}+/u', '', $s);
    }

    // PHP string functions that need UTF-8 awareness

    public static function filter_input($type, $var, $filter = FILTER_DEFAULT, $option = null)
    {
        if (4 > func_num_args()) {
            $var = filter_input($type, $var, $filter);
        } else {
            $var = filter_input($type, $var, $filter, $option);
        }

        return static::filter($var);
    }

    public static function filter_input_array($type, $def = null, $add_empty = true)
    {
        if (2 > func_num_args()) {
            $a = filter_input_array($type);
        } else {
            $a = filter_input_array($type, $def, $add_empty);
        }

        return static::filter($a);
    }

    public static function json_decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        if (PHP_VERSION_ID < 50400) {
            $json = json_decode($json, $assoc, $depth);
        } else {
            $json = json_decode($json, $assoc, $depth, $options);
        }

        return static::filter($json);
    }

    public static function substr($s, $start, $len = 2147483647)
    {
        static $bug62759;
        isset($bug62759) or $bug62759 = extension_loaded('intl') && 'à' === @grapheme_substr('éà', 1, -2);

        if ($bug62759) {
            return PHP\Shim\Intl::grapheme_substr_workaround62759($s, $start, $len);
        } else {
            return grapheme_substr($s, $start, $len);
        }
    }

    public static function strlen($s)
    {
        return grapheme_strlen($s);
    }
    public static function strpos($s, $needle, $offset = 0)
    {
        // ignore invalid negative offset to keep compatility
        // with php < 5.5.35, < 5.6.21, < 7.0.6
        return grapheme_strpos($s, $needle, $offset > 0 ? $offset : 0);
    }
    public static function strrpos($s, $needle, $offset = 0)
    {
        return grapheme_strrpos($s, $needle, $offset);
    }

    public static function stripos($s, $needle, $offset = 0)
    {
        if (50418 > PHP_VERSION_ID || 50500 == PHP_VERSION_ID) {
            // Don't use grapheme_stripos because of https://bugs.php.net/61860
            if (!preg_match('//u', $s .= '')) {
                return false;
            }
            if ($offset < 0) {
                $offset = 0;
            }
            if (!$needle = mb_stripos($s, $needle .= '', $offset, 'UTF-8')) {
                return $needle;
            }

            return grapheme_strlen(iconv_substr($s, 0, $needle, 'UTF-8'));
        }

        return grapheme_stripos($s, $needle, $offset);
    }

    public static function strripos($s, $needle, $offset = 0)
    {
        if (50418 > PHP_VERSION_ID || 50500 == PHP_VERSION_ID) {
            // Don't use grapheme_strripos because of https://bugs.php.net/61860
            if (!preg_match('//u', $s .= '')) {
                return false;
            }
            if ($offset < 0) {
                $offset = 0;
            }
            if (!$needle = mb_strripos($s, $needle .= '', $offset, 'UTF-8')) {
                return $needle;
            }

            return grapheme_strlen(iconv_substr($s, 0, $needle, 'UTF-8'));
        }

        return grapheme_strripos($s, $needle, $offset);
    }

    public static function stristr($s, $needle, $before_needle = false)
    {
        if ('' === $needle .= '') {
            return false;
        }

        return mb_stristr($s, $needle, $before_needle, 'UTF-8');
    }

    public static function strstr($s, $needle, $before_needle = false)
    {
        return grapheme_strstr($s, $needle, $before_needle);
    }
    public static function strrchr($s, $needle, $before_needle = false)
    {
        return mb_strrchr($s, $needle, $before_needle, 'UTF-8');
    }
    public static function strrichr($s, $needle, $before_needle = false)
    {
        return mb_strrichr($s, $needle, $before_needle, 'UTF-8');
    }

    public static function strtolower($s)
    {
        return mb_strtolower($s, 'UTF-8');
    }
    public static function strtoupper($s)
    {
        return mb_strtoupper($s, 'UTF-8');
    }

    public static function wordwrap($s, $width = 75, $break = "\n", $cut = false)
    {
        if (false === wordwrap('-', $width, $break, $cut)) {
            return false;
        }

        is_string($break) or $break = (string) $break;

        $w = '';
        $s = explode($break, $s);
        $iLen = count($s);
        $chars = array();

        if (1 === $iLen && '' === $s[0]) {
            return '';
        }

        for ($i = 0; $i < $iLen; ++$i) {
            if ($i) {
                $chars[] = $break;
                $w .= '#';
            }

            $c = $s[$i];
            unset($s[$i]);

            foreach (self::str_split($c) as $c) {
                $chars[] = $c;
                $w .= ' ' === $c ? ' ' : '?';
            }
        }

        $s = '';
        $j = 0;
        $b = $i = -1;
        $w = wordwrap($w, $width, '#', $cut);

        while (false !== $b = strpos($w, '#', $b + 1)) {
            for (++$i; $i < $b; ++$i) {
                $s .= $chars[$j];
                unset($chars[$j++]);
            }

            if ($break === $chars[$j] || ' ' === $chars[$j]) {
                unset($chars[$j++]);
            }
            $s .= $break;
        }

        return $s.implode('', $chars);
    }

    public static function chr($c)
    {
        if (0x80 > $c %= 0x200000) {
            return chr($c);
        }
        if (0x800 > $c) {
            return chr(0xC0 | $c >> 6).chr(0x80 | $c & 0x3F);
        }
        if (0x10000 > $c) {
            return chr(0xE0 | $c >> 12).chr(0x80 | $c >> 6 & 0x3F).chr(0x80 | $c & 0x3F);
        }

        return chr(0xF0 | $c >> 18).chr(0x80 | $c >> 12 & 0x3F).chr(0x80 | $c >> 6 & 0x3F).chr(0x80 | $c & 0x3F);
    }

    public static function count_chars($s, $mode = 0)
    {
        if (1 != $mode) {
            user_error(__METHOD__.'(): the only allowed $mode is 1', E_USER_WARNING);
        }
        $s = self::str_split($s);

        return array_count_values($s);
    }

    public static function ltrim($s, $charlist = null)
    {
        $charlist = null === $charlist ? '\s' : self::rxClass($charlist);

        return preg_replace("/^{$charlist}+/u", '', $s);
    }

    public static function ord($s)
    {
        $a = ($s = unpack('C*', substr($s, 0, 4))) ? $s[1] : 0;
        if (0xF0 <= $a) {
            return (($a - 0xF0) << 18) + (($s[2] - 0x80) << 12) + (($s[3] - 0x80) << 6) + $s[4] - 0x80;
        }
        if (0xE0 <= $a) {
            return (($a - 0xE0) << 12) + (($s[2] - 0x80) << 6) + $s[3] - 0x80;
        }
        if (0xC0 <= $a) {
            return (($a - 0xC0) << 6) + $s[2] - 0x80;
        }

        return $a;
    }

    public static function rtrim($s, $charlist = null)
    {
        $charlist = null === $charlist ? '\s' : self::rxClass($charlist);

        return preg_replace("/{$charlist}+$/u", '', $s);
    }

    public static function trim($s, $charlist = null)
    {
        return self::rtrim(self::ltrim($s, $charlist), $charlist);
    }

    public static function str_ireplace($search, $replace, $subject, &$count = null)
    {
        $search = (array) $search;

        foreach ($search as $i => $s) {
            if ('' === $s .= '') {
                $s = '/^(?<=.)$/';
            } else {
                $s = '/'.preg_quote($s, '/').'/ui';
            }

            $search[$i] = $s;
        }

        $subject = preg_replace($search, $replace, $subject, -1, $replace);
        $count = $replace;

        return $subject;
    }

    public static function str_pad($s, $len, $pad = ' ', $type = STR_PAD_RIGHT)
    {
        $slen = grapheme_strlen($s);
        if ($len <= $slen) {
            return $s;
        }

        $padlen = grapheme_strlen($pad);
        $freelen = $len - $slen;
        $len = $freelen % $padlen;

        if (STR_PAD_RIGHT == $type) {
            return $s.str_repeat($pad, $freelen / $padlen).($len ? grapheme_substr($pad, 0, $len) : '');
        }
        if (STR_PAD_LEFT == $type) {
            return str_repeat($pad, $freelen / $padlen).($len ? grapheme_substr($pad, 0, $len) : '').$s;
        }
        if (STR_PAD_BOTH == $type) {
            $freelen /= 2;

            $type = ceil($freelen);
            $len = $type % $padlen;
            $s .= str_repeat($pad, $type / $padlen).($len ? grapheme_substr($pad, 0, $len) : '');

            $type = floor($freelen);
            $len = $type % $padlen;

            return str_repeat($pad, $type / $padlen).($len ? grapheme_substr($pad, 0, $len) : '').$s;
        }

        user_error(__METHOD__.'(): Padding type has to be STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH', E_USER_WARNING);
    }

    public static function str_shuffle($s)
    {
        $s = self::str_split($s);
        shuffle($s);

        return implode('', $s);
    }

    public static function str_split($s, $len = 1)
    {
        if (1 > $len = (int) $len) {
            $len = func_get_arg(1);

            return str_split($s, $len);
        }

        static $hasIntl;
        isset($hasIntl) or $hasIntl = extension_loaded('intl');

        if ($hasIntl) {
            $a = array();
            $p = 0;
            $l = strlen($s);

            while ($p < $l) {
                $a[] = grapheme_extract($s, 1, GRAPHEME_EXTR_COUNT, $p, $p);
            }
        } else {
            preg_match_all('/'.GRAPHEME_CLUSTER_RX.'/u', $s, $a);
            $a = $a[0];
        }

        if (1 == $len) {
            return $a;
        }

        $s = array();
        $p = -1;

        foreach ($a as $l => $a) {
            if ($l % $len) {
                $s[$p] .= $a;
            } else {
                $s[++$p] = $a;
            }
        }

        return $s;
    }

    public static function str_word_count($s, $format = 0, $charlist = '')
    {
        $charlist = self::rxClass($charlist, '\pL');
        $s = preg_split("/({$charlist}+(?:[\p{Pd}’']{$charlist}+)*)/u", $s, -1, PREG_SPLIT_DELIM_CAPTURE);

        $charlist = array();
        $len = count($s);

        if (1 == $format) {
            for ($i = 1; $i < $len; $i += 2) {
                $charlist[] = $s[$i];
            }
        } elseif (2 == $format) {
            $offset = grapheme_strlen($s[0]);
            for ($i = 1; $i < $len; $i += 2) {
                $charlist[$offset] = $s[$i];
                $offset += grapheme_strlen($s[$i]) + grapheme_strlen($s[$i + 1]);
            }
        } else {
            $charlist = ($len - 1) / 2;
        }

        return $charlist;
    }

    public static function strcmp($a, $b)
    {
        return $a.'' === $b.'' ? 0 : strcmp(n::normalize($a, n::NFD), n::normalize($b, n::NFD));
    }
    public static function strnatcmp($a, $b)
    {
        return $a.'' === $b.'' ? 0 : strnatcmp(self::strtonatfold($a), self::strtonatfold($b));
    }
    public static function strcasecmp($a, $b)
    {
        return self::strcmp(static::strtocasefold($a), static::strtocasefold($b));
    }
    public static function strnatcasecmp($a, $b)
    {
        return self::strnatcmp(static::strtocasefold($a), static::strtocasefold($b));
    }
    public static function strncasecmp($a, $b, $len)
    {
        return self::strncmp(static::strtocasefold($a), static::strtocasefold($b), $len);
    }
    public static function strncmp($a, $b, $len)
    {
        return self::strcmp(self::substr($a, 0, $len), self::substr($b, 0, $len));
    }

    public static function strcspn($s, $charlist, $start = 0, $len = 2147483647)
    {
        if ('' === $charlist .= '') {
            return;
        }
        if ($start || 2147483647 != $len) {
            $s = self::substr($s, $start, $len);
        }

        return preg_match('/^(.*?)'.self::rxClass($charlist).'/us', $s, $len) ? grapheme_strlen($len[1]) : grapheme_strlen($s);
    }

    public static function strpbrk($s, $charlist)
    {
        if (preg_match('/'.self::rxClass($charlist).'/us', $s, $m)) {
            return substr($s, strpos($s, $m[0]));
        } else {
            return false;
        }
    }

    public static function strrev($s)
    {
        $s = self::str_split($s);

        return implode('', array_reverse($s));
    }

    public static function strspn($s, $mask, $start = 0, $len = 2147483647)
    {
        if ($start || 2147483647 != $len) {
            $s = self::substr($s, $start, $len);
        }

        return preg_match('/^'.self::rxClass($mask).'+/u', $s, $s) ? grapheme_strlen($s[0]) : 0;
    }

    public static function strtr($s, $from, $to = null)
    {
        if (null !== $to) {
            $from = self::str_split($from);
            $to   = self::str_split($to);

            $a = count($from);
            $b = count($to);

            if ($a > $b) {
                $from = array_slice($from, 0, $b);
            } elseif ($a < $b) {
                $to   = array_slice($to, 0, $a);
            }

            $from = array_combine($from, $to);
        }

        return strtr($s, $from);
    }

    public static function substr_compare($a, $b, $offset, $len = 2147483647, $i = 0)
    {
        $a = self::substr($a, $offset, $len);

        return $i ? static::strcasecmp($a, $b) : self::strcmp($a, $b);
    }

    public static function substr_count($s, $needle, $offset = 0, $len = 2147483647)
    {
        return substr_count(self::substr($s, $offset, $len), $needle);
    }

    public static function substr_replace($s, $replace, $start, $len = 2147483647)
    {
        $s       = self::str_split($s);
        $replace = self::str_split($replace);
        array_splice($s, $start, $len, $replace);

        return implode('', $s);
    }

    public static function ucfirst($s)
    {
        $c = iconv_substr($s, 0, 1, 'UTF-8');

        return static::ucwords($c).substr($s, strlen($c));
    }

    public static function lcfirst($s)
    {
        $c = iconv_substr($s, 0, 1, 'UTF-8');

        return static::strtolower($c).substr($s, strlen($c));
    }

    public static function ucwords($s)
    {
        return preg_replace_callback(
            "/\b(.)/u",
            function ($matches) {
                return mb_convert_case($matches[1], MB_CASE_TITLE, 'UTF-8');
            },
            $s
        );
    }

    public static function number_format($number, $decimals = 0, $dec_point = '.', $thousands_sep = ',')
    {
        if (PHP_VERSION_ID < 50400) {
            if (isset($thousands_sep[1]) || isset($dec_point[1])) {
                return str_replace(
                    array('.', ','),
                    array($dec_point, $thousands_sep),
                    number_format($number, $decimals, '.', ',')
                );
            }
        }

        return number_format($number, $decimals, $dec_point, $thousands_sep);
    }

    public static function utf8_encode($s)
    {
        $s = utf8_encode($s);
        if (false === strpos($s, "\xC2")) {
            return $s;
        } else {
            return str_replace(self::$cp1252, self::$utf8, $s);
        }
    }

    public static function utf8_decode($s)
    {
        $s = str_replace(self::$utf8, self::$cp1252, $s);

        return utf8_decode($s);
    }

    public static function strwidth($s)
    {
        if (false !== strpos($s, "\r")) {
            $s = str_replace("\r\n", "\n", $s);
            $s = strtr($s, "\r", "\n");
        }
        $width = 0;

        foreach (explode("\n", $s) as $s) {
            $s = preg_replace('/\x1B\[[\d;]*m/', '', $s);
            $c = substr_count($s, "\xAD") - substr_count($s, "\x08");
            $s = preg_replace('/[\x00\x05\x07\p{Mn}\p{Me}\p{Cf}\x{1160}-\x{11FF}\x{200B}]+/u', '', $s);
            preg_replace('/[\x{1100}-\x{115F}\x{2329}\x{232A}\x{2E80}-\x{303E}\x{3040}-\x{A4CF}\x{AC00}-\x{D7A3}\x{F900}-\x{FAFF}\x{FE10}-\x{FE19}\x{FE30}-\x{FE6F}\x{FF00}-\x{FF60}\x{FFE0}-\x{FFE6}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}]/u', '', $s, -1, $wide);

            if ($width < $c = iconv_strlen($s, 'UTF-8') + $wide + $c) {
                $width = $c;
            }
        }

        return $width;
    }

    protected static function rxClass($s, $class = '')
    {
        $class = array($class);

        foreach (self::str_split($s) as $s) {
            if ('-' === $s) {
                $class[0] = '-'.$class[0];
            } elseif (!isset($s[2])) {
                $class[0] .= preg_quote($s, '/');
            } elseif (1 === iconv_strlen($s, 'UTF-8')) {
                $class[0] .= $s;
            } else {
                $class[] = $s;
            }
        }

        $class[0] = '['.$class[0].']';

        if (1 === count($class)) {
            return $class[0];
        } else {
            return '(?:'.implode('|', $class).')';
        }
    }

    protected static function getData($file)
    {
        $file = __DIR__.'/Utf8/data/'.$file.'.ser';
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        } else {
            return false;
        }
    }
}
