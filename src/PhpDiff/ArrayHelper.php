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

    /**
     * Sort Blocks
     * @param Block $a
     * @param Block $b
     * @return int -1, 0 or 1, as expected by the usort function
     */
    public static function blockSort(Block $a, Block $b)
    {
        if ($a->size === $b->size) {
            if ($a->beginLeft === $b->beginLeft) {
                if ($a->beginRight === $b->beginRight) {
                    return 0;
                }

                return $a->beginRight < $b->beginRight ? -1 : 1;
            }

            return $a->beginLeft < $b->beginLeft ? -1 : 1;
        }

        return $a->size < $b->size ? -1 : 1;
    }
}
