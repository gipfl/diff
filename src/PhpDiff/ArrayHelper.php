<?php

namespace gipfl\Diff\PhpDiff;

abstract class ArrayHelper
{
    /**
     * Helper function that provides the ability to return the value for a key
     * in an array of it exists, or if it doesn't then return a default value.
     * Essentially cleaner than doing a series of if(isset()) {} else {} calls.
     *
     * @param array $array The array to search.
     * @param string $key The key to check that exists.
     * @param mixed $default The value to return as the default value if the key doesn't exist.
     * @return mixed The value from the array if the key exists or otherwise the default.
     */
    public static function getPropertyOrDefault($array, $key, $default)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        return $default;
    }
}
