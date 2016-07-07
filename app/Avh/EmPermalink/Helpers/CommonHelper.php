<?php
namespace Avh\EmPermalink\Helpers;

/**
 * Class CommonHelper
 *
 * @package   Avh\EmPermalink\Helpers
 * @author    Peter van der Does <peter@avirtualhome.com>
 * @copyright Copyright (c) 2015, AVH Software
 */
class CommonHelper
{
    /**
     * Sort an array on multiple columns
     *
     * @param array $array
     * @param array $cols
     *
     * @return array
     */
    public static function arrayMsort($array, $cols)
    {
        $row_is_object     = false;
        $sort_column_array = [];

        // Create multiple arrays using the array $cols.
        // These arrays hold the values of each field that we want to sort on.
        foreach ($cols as $col => $order) {
            $sort_column_array[$col] = [];
            foreach ($array as $key => $row) {
                if (is_object($row)) {
                    $row           = (array) $row;
                    $row_is_object = true;
                }
                $sort_column_array[$col][$key] = strtolower($row[$col]);
            }
        }

        $params = [];
        foreach ($cols as $col => $order) {
            $params[] = &$sort_column_array[$col];
            foreach ($order as $order_element) {
                // pass by reference, as required by php 5.3
                $params[] = &$order_element;
                unset($order_element);
            }
        }

        $params[] = &$array;
        call_user_func_array('array_multisort', $params);
        if ($row_is_object) {
            foreach ($array as $key => $row) {
                $array[$key] = (object) $row;
            }
        }

        return $array;
    }

    /**
     * Create a directory if it does not exist.
     *
     * @param string $path
     */
    public static function createDirectory($path)
    {
        if (!file_exists($path)) { // Create the directory if it is missing
            wp_mkdir_p($path);
        }
    }

    /**
     * Improve the default WordPress plugins_url.
     * The standard function requires a file at the end of the 2nd parameter.
     *
     * @param string $file
     * @param string $directory
     *
     * @return string
     */
    public static function getPluginUrl($file, $directory)
    {
        if (is_dir($directory)) {
            $directory .= '/foo';
        }

        return plugins_url($file, $directory);
    }

    /**
     * Check if the given date in the given format is valid.
     *
     * @param  string $date
     * @param string  $format
     *
     * @return bool
     */
    public static function isValidDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) == $date;
    }
}
