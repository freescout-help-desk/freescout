<?php
namespace Underscore;

use Underscore\Methods\ArraysMethods;

/**
 * Parse from various formats to various formats.
 */
class Parse
{
    ////////////////////////////////////////////////////////////////////
    /////////////////////////////// JSON ///////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Converts data from JSON.
     *
     * @param string $data The data to parse
     *
     * @return mixed
     */
    public static function fromJSON($data)
    {
        return json_decode($data, true);
    }

    /**
     * Converts data to JSON.
     *
     * @param array|object $data The data to convert
     *
     * @return string Converted data
     */
    public static function toJSON($data)
    {
        return json_encode($data);
    }

    ////////////////////////////////////////////////////////////////////
    //////////////////////////////// XML ///////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Converts data from XML.
     *
     * @param string $xml The data to parse
     *
     * @return array
     */
    public static function fromXML($xml)
    {
        $xml = simplexml_load_string($xml);
        $xml = json_encode($xml);
        $xml = json_decode($xml, true);

        return $xml;
    }

    ////////////////////////////////////////////////////////////////////
    //////////////////////////////// CSV ///////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Converts data from CSV.
     *
     * @param string $data       The data to parse
     * @param bool   $hasHeaders Whether the CSV has headers
     *
     * @return mixed
     */
    public static function fromCSV($data, $hasHeaders = false)
    {
        $data = trim($data);

        // Explodes rows
        $data = static::explodeWith($data, [PHP_EOL, "\r", "\n"]);
        $data = array_map(function ($row) {
            return Parse::explodeWith($row, [';', "\t", ',']);
        }, $data);

        // Get headers
        $headers = $hasHeaders ? $data[0] : array_keys($data[0]);
        if ($hasHeaders) {
            array_shift($data);
        }

        // Parse the columns in each row
        $array = [];
        foreach ($data as $row => $columns) {
            foreach ($columns as $columnNumber => $column) {
                $array[$row][$headers[$columnNumber]] = $column;
            }
        }

        return $array;
    }

    /**
     * Converts data to CSV.
     *
     * @param mixed  $data The data to convert
     * @param string $delimiter
     * @param bool   $exportHeaders
     *
     * @return string Converted data
     */
    public static function toCSV($data, $delimiter = ';', $exportHeaders = false)
    {
        $csv = [];

        // Convert objects to arrays
        if (is_object($data)) {
            $data = (array) $data;
        }

        // Don't convert if it's not an array
        if (!is_array($data)) {
            return $data;
        }

        // Fetch headers if requested
        if ($exportHeaders) {
            $headers = array_keys(ArraysMethods::first($data));
            $csv[]   = implode($delimiter, $headers);
        }

        // Quote values and create row
        foreach ($data as $header => $row) {
            // If single column
            if (!is_array($row)) {
                $csv[] = '"'.$header.'"'.$delimiter.'"'.$row.'"';
                continue;
            }

            // Else add values
            foreach ($row as $key => $value) {
                $row[$key] = '"'.stripslashes($value).'"';
            }

            $csv[] = implode($delimiter, $row);
        }

        return implode(PHP_EOL, $csv);
    }

    ////////////////////////////////////////////////////////////////////
    ///////////////////////// TYPES SWITCHERS //////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Converts data to an array.
     *
     * @param string|object $data
     *
     * @return array
     */
    public static function toArray($data)
    {
        // Look for common array conversion patterns in objects
        if (is_object($data) and method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }

        return (array) $data;
    }

    /**
     * Converts data to a string.
     *
     * @param array|object $data
     *
     * @return string
     */
    public static function toString($data)
    {
        // Avoid Array to Strings conversion exception
        if (is_array($data)) {
            return static::toJSON($data);
        }

        return (string) $data;
    }

    /**
     * Converts data to an integer.
     *
     * @param array|string|object $data
     *
     * @return int
     */
    public static function toInteger($data)
    {
        // Returns size of arrays
        if (is_array($data)) {
            return count($data);
        }

        // Returns size of strings
        if (is_string($data) and !preg_match('/[0-9. ,]+/', $data)) {
            return strlen($data);
        }

        return (int) $data;
    }

    /**
     * Converts data to a boolean.
     *
     * @param array|sring|object $data
     *
     * @return bool
     */
    public static function toBoolean($data)
    {
        return (bool) $data;
    }

    /**
     * Converts data to an object.
     *
     * @param array|string $data
     *
     * @return object
     */
    public static function toObject($data)
    {
        return (object) $data;
    }

    ////////////////////////////////////////////////////////////////////
    ///////////////////////////// HELPERS //////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Tries to explode a string with an array of delimiters.
     *
     * @param string $string     The string
     * @param array  $delimiters An array of delimiters
     *
     * @return array
     */
    public static function explodeWith($string, array $delimiters)
    {
        $array = $string;

        foreach ($delimiters as $delimiter) {
            $array = explode($delimiter, $string);
            if (count($array) === 1) {
                continue;
            } else {
                return $array;
            }
        }

        return $array;
    }
}
