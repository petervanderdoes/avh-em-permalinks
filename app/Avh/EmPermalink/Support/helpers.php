<?php
if (!function_exists('avh_starts_with')) {
    /**
     * Determine if a string starts with a given needle.
     *
     * @param string       $haystack
     * @param string|array $needle
     *
     * @return bool
     */
    function avh_starts_with($haystack, $needle)
    {
        return \Illuminate\Support\Str::startsWith($haystack, $needle);
    }
}

if (!function_exists('avh_is_valid_url')) {
    /**
     * Determine if the given path is a valid URL.
     *
     * @param string $path
     *
     * @return bool
     */
    function avh_is_valid_url($path)
    {
        if (avh_starts_with($path, ['#', '//', 'mailto:', 'tel:'])) {
            return true;
        }

        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('avh_array_get')) {
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function avh_array_get($array, $key, $default = null)
    {
        if ($key === null) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }
}

if (!function_exists('avh_get_array_value')) {
    /**
     * Get the value of the array[key], and if it does not exist, return the given default value.
     *
     * @param array  $array
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    function avh_get_array_value($array, $key, $default = '')
    {
        if (!array_key_exists($key, $array)) {
            $return = $default;
        } else {
            $return = $array[$key];
        }

        return $return;
    }
}
