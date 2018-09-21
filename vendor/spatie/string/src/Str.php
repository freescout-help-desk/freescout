<?php

namespace Spatie\String;

use ArrayAccess;
use Spatie\String\Integrations\Underscore;
use Spatie\String\Exceptions\UnsetOffsetException;
use Spatie\String\Exceptions\UnknownFunctionException;
use Spatie\String\Exceptions\ErrorCreatingStringException;

/**
 * Magic methods provided by underscore are documented here.
 *
 * @see \Underscore\Methods\StringsMethods
 *
 * @method \Spatie\String\Str accord($count, $many, $one, $zero = null)
 * @method \Spatie\String\Str random($length = 16)
 * @method \Spatie\String\Str quickRandom($length = 16)
 * @method randomStrings($words, $length = 10)
 * @method bool endsWith($needles)
 * @method bool isIp()
 * @method bool isEmail()
 * @method bool isUrl()
 * @method bool startsWith()
 * @method bool find($needle, $caseSensitive = false, $absolute = false)
 * @method array slice($slice)
 * @method \Spatie\String\Str sliceFrom($slice)
 * @method \Spatie\String\Str sliceTo($slice)
 * @method \Spatie\String\Str baseClass()
 * @method \Spatie\String\Str prepend($with)
 * @method \Spatie\String\Str append($with)
 * @method \Spatie\String\Str limit($limit = 100, $end = '...')
 * @method \Spatie\String\Str remove($remove)
 * @method \Spatie\String\Str replace($replace, $with)
 * @method \Spatie\String\Str toggle($first, $second, $loose = false)
 * @method \Spatie\String\Str slugify($separator = '-')
 * @method array explode($with, $limit = null)
 * @method \Spatie\String\Str lower()
 * @method \Spatie\String\Str plural()
 * @method \Spatie\String\Str singular()
 * @method \Spatie\String\Str upper()
 * @method \Spatie\String\Str title()
 * @method \Spatie\String\Str words($words = 100, $end = '...')
 * @method \Spatie\String\Str toPascalCase()
 * @method \Spatie\String\Str toSnakeCase()
 * @method \Spatie\String\Str toCamelCase()
 */
class Str implements ArrayAccess
{
    /**
     * @var string
     */
    protected $string;

    /**
     * @param string $string
     */
    public function __construct($string = '')
    {
        if (is_array($string)) {
            throw new ErrorCreatingStringException('Can\'t create string from an array');
        }

        if (is_object($string) && ! method_exists($string, '__toString')) {
            throw new ErrorCreatingStringException(
                'Can\'t create string from an object that doesn\'t implement __toString'
            );
        }

        $this->string = (string) $string;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->string;
    }

    /**
     * Get the string between the given start and end.
     *
     * @param $start
     * @param $end
     *
     * @return \Spatie\String\Str
     */
    public function between($start, $end)
    {
        if ($start == '' && $end == '') {
            return $this;
        }

        if ($start != '' && strpos($this->string, $start) === false) {
            return new static();
        }

        if ($end != '' && strpos($this->string, $end) === false) {
            return new static();
        }

        if ($start == '') {
            return new static(substr($this->string, 0, strpos($this->string, $end)));
        }

        if ($end == '') {
            return new static(substr($this->string, strpos($this->string, $start) + strlen($start)));
        }

        $stringWithoutStart = explode($start, $this->string)[1];

        $middle = explode($end, $stringWithoutStart)[0];

        return new static($middle);
    }

    /**
     * Convert the string to uppercase.
     *
     * @return \Spatie\String\Str
     */
    public function toUpper()
    {
        return new static(strtoupper($this->string));
    }

    /**
     * Convert the string to lowercase.
     *
     * @return \Spatie\String\Str
     */
    public function toLower()
    {
        return new static(strtolower($this->string));
    }

    /**
     * Shortens a string in a pretty way. It will clean it by trimming
     * it, remove all double spaces and html. If the string is then still
     * longer than the specified $length it will be shortened. The end
     * of the string is always a full word concatinated with the
     * specified moreTextIndicator.
     *
     * @param int    $length
     * @param string $moreTextIndicator
     *
     * @return \Spatie\String\Str
     */
    public function tease($length = 200, $moreTextIndicator = '...')
    {
        $sanitizedString = $this->sanitizeForTease($this->string);

        if (strlen($sanitizedString) == 0) {
            return new static();
        }

        if (strlen($sanitizedString) <= $length) {
            return new static($sanitizedString);
        }

        $ww = wordwrap($sanitizedString, $length, "\n");
        $shortenedString = substr($ww, 0, strpos($ww, "\n")).$moreTextIndicator;

        return new static($shortenedString);
    }

    /**
     * Sanitize the string for teasing.
     *
     * @param $string
     *
     * @return string
     */
    private function sanitizeForTease($string)
    {
        $string = trim($string);

        //remove html
        $string = strip_tags($string);

        //replace multiple spaces
        $string = preg_replace("/\s+/", ' ', $string);

        return $string;
    }

    /**
     * Replace the first occurrence of a string.
     *
     * @param $search
     * @param $replace
     *
     * @return \Spatie\String\Str
     */
    public function replaceFirst($search, $replace)
    {
        if ($search == '') {
            return $this;
        }

        $position = strpos($this->string, $search);

        if ($position === false) {
            return $this;
        }

        $resultString = substr_replace($this->string, $replace, $position, strlen($search));

        return new static($resultString);
    }

    /**
     * Replace the last occurrence of a string.
     *
     * @param $search
     * @param $replace
     *
     * @return \Spatie\String\Str
     */
    public function replaceLast($search, $replace)
    {
        if ($search == '') {
            return $this;
        }

        $position = strrpos($this->string, $search);

        if ($position === false) {
            return $this;
        }

        $resultString = substr_replace($this->string, $replace, $position, strlen($search));

        return new static($resultString);
    }

    /**
     * Prefix a string.
     *
     * @param $string
     *
     * @return \Spatie\String\Str
     */
    public function prefix($string)
    {
        return new static($string.$this->string);
    }

    /**
     * Suffix a string.
     *
     * @param $string
     *
     * @return \Spatie\String\Str
     */
    public function suffix($string)
    {
        return new static($this->string.$string);
    }

    /**
     * Concatenate a string.
     *
     * @param $string
     *
     * @return \Spatie\String\Str
     */
    public function concat($string)
    {
        return $this->suffix($string);
    }

    /**
     * Get the possessive version of a string.
     *
     * @return \Spatie\String\Str
     */
    public function possessive()
    {
        if ($this->string == '') {
            return new static();
        }

        $noApostropheEdgeCases = ['it'];

        if (in_array($this->string, $noApostropheEdgeCases)) {
            return new static($this->string.'s');
        }

        return new static($this->string.'\''.($this->string[strlen($this->string) - 1] != 's' ? 's' : ''));
    }

    /**
     * Get a segment from a string based on a delimiter.
     * Returns an empty string when the offset doesn't exist.
     * Use a negative index to start counting from the last element.
     *
     * @param string $delimiter
     * @param int    $index
     *
     * @return \Spatie\String\Str
     */
    public function segment($delimiter, $index)
    {
        $segments = explode($delimiter, $this->string);

        if ($index < 0) {
            $segments = array_reverse($segments);
            $index = abs($index) - 1;
        }

        $segment = isset($segments[$index]) ? $segments[$index] : '';

        return new static($segment);
    }

    /**
     * Get the first segment from a string based on a delimiter.
     *
     * @param string $delimiter
     *
     * @return \Spatie\String\Str
     */
    public function firstSegment($delimiter)
    {
        return (new static($this->string))->segment($delimiter, 0);
    }

    /**
     * Get the last segment from a string based on a delimiter.
     *
     * @param string $delimiter
     *
     * @return \Spatie\String\Str
     */
    public function lastSegment($delimiter)
    {
        return (new static($this->string))->segment($delimiter, -1);
    }

    /**
     * Pop (remove) the last segment of a string based on a delimiter.
     *
     * @param string $delimiter
     *
     * @return \Spatie\String\Str
     */
    public function pop($delimiter)
    {
        return (new static($this->string))->replaceLast($delimiter.$this->lastSegment($delimiter), '');
    }

    /**
     * Strip whitespace (or other characters) from the beginning and end of a string.
     *
     * @param string $characterMask
     *
     * @return \Spatie\String\Str
     */
    public function trim($characterMask = " \t\n\r\0\x0B")
    {
        return new static(trim($this->string, $characterMask));
    }

    /**
     * Alias for find.
     *
     * @param array|string $needle
     * @param bool         $caseSensitive
     * @param bool         $absolute
     *
     * @return bool
     */
    public function contains($needle, $caseSensitive = false, $absolute = false)
    {
        return $this->find($needle, $caseSensitive, $absolute);
    }

    /**
     * Unknown methods calls will be handled by various integrations.
     *
     * @param $method
     * @param $args
     *
     * @throws UnknownFunctionException
     *
     * @return mixed|\Spatie\String\Str
     */
    public function __call($method, $args)
    {
        $underscore = new Underscore();

        if ($underscore->isSupportedMethod($method)) {
            return $underscore->call($this, $method, $args);
        }

        throw new UnknownFunctionException(sprintf('String function %s does not exist', $method));
    }

    /**
     * Whether a offset exists.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset An offset to check for.
     *
     * @return bool true on success or false on failure.
     *              The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return strlen($this->string) >= ($offset + 1);
    }

    /**
     * Offset to retrieve.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        $character = substr($this->string, $offset, 1);

        return new static($character ?: '');
    }

    /**
     * Offset to set.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     */
    public function offsetSet($offset, $value)
    {
        $this->string[$offset] = $value;
    }

    /**
     * Offset to unset.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset The offset to unset.
     *
     * @throws UnsetOffsetException
     */
    public function offsetUnset($offset)
    {
        throw new UnsetOffsetException();
    }
}
