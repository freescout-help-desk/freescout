<?php

/*
 * Copyright (C) 2016 Nicolas Grekas - p@tchwork.com
 *
 * This library is free software; you can redistribute it and/or modify it
 * under the terms of the (at your option):
 * Apache License v2.0 (http://apache.org/licenses/LICENSE-2.0.txt), or
 * GNU General Public License v2.0 (http://gnu.org/licenses/gpl-2.0.txt).
 */

namespace Patchwork\Utf8;

/**
 * UTF-8 to Code Page conversion using best fit mappings
 * See http://www.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/WindowsBestFit/.
 */
class BestFit
{
    public static function fit($cp, $s, $placeholder = '?')
    {
        if (!$len = strlen($s)) {
            return 0 === $len ? '' : false;
        }

        static $map = array();
        static $ulen_mask = array("\xC0" => 2, "\xD0" => 2, "\xE0" => 3, "\xF0" => 4);

        $s .= '';
        $cp = (string) (int) $cp;
        $result = '9' === $cp[0] ? $s.$s : $s;

        if ('932' === $cp && 2 === func_num_args()) {
            $placeholder = "\x81\x45"; // Katakana Middle Dot in CP932
        }

        if (!isset($map[$cp])) {
            $i = static::getData('to.bestfit'.$cp);
            if (false === $i) {
                return false;
            }
            $map[$cp] = $i;
        }

        $i = $j = 0;
        $cp = $map[$cp];

        while ($i < $len) {
            if ($s[$i] < "\x80") {
                $uchr = $s[$i++];
            } else {
                $ulen = $ulen_mask[$s[$i] & "\xF0"];
                $uchr = substr($s, $i, $ulen);
                $i += $ulen;
            }

            if (isset($cp[$uchr])) {
                $uchr = $cp[$uchr];
            } else {
                $uchr = $placeholder;
            }

            isset($uchr[0]) and $result[$j++] = $uchr[0];
            isset($uchr[1]) and $result[$j++] = $uchr[1];
        }

        return substr($result, 0, $j);
    }

    protected static function getData($file)
    {
        $file = __DIR__.'/data/'.$file.'.ser';
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        } else {
            return false;
        }
    }
}
