<?php

namespace jsonstatPhpViz\src;

use function count;

/**
 * Utility to provide additional array methods.
 * Some are modelled after NumPy.
 */
class UtilArray
{
    /**
     * Calculate strides from the shape.
     * @see https://numpy.org/doc/stable/reference/generated/numpy.ndarray.strides.html
     * @param array $shape
     * @return array
     */
    public static function getStrides(array $shape): array
    {
        $len = count($shape);
        $size = 1;
        $i = $len - 1;
        $stride = [];
        // note: using $stride[$i] = $size instead of $stride[] = $size in the loop below,
        // we could do away with the array_reverse() in the return, since we are setting the correct keys, but the
        // actual order would be inverted and turn out wrong, when using implode() or a 'for loop' instead of foreach,
        // e.g shape[4,2,3,2] --> [3 => 1, 2 => 2, 1 => 6, 0 => 12] looking like [12,6,2,1], but imploding to '1,2,6,12'
        for (; $i >= 0; --$i) {
            $stride[] = $size;
            $size *= $shape[$i];
        }

        return array_reverse($stride);
    }

    /**
     * Permutate the axes of a 1-dim array.
     * @param array $arr input array
     * @param array $axes
     * @return array
     */
    public static function swap(array $arr, array $axes): array
    {
        return array_map(static function ($val) use ($arr) {
            return $arr[$val];
        }, $axes);
    }

    /**
     * Convert a linear index (row major) to subindexes.
     * Creates an array of subscripts from the shape,
     * e.g. when called repeatedly from idx[0,1,2,3,...,48] with shape[4,2,3,2], it creates the following sequence:
     * -> [0,0,0,0], [0,0,0,1], [0,0,1,0], [0,0,1,1], [0,0,2,0], [0,0,2,1], [0,1,0,0], [0,1,0,1], ..., [3,1,2,1]
     * @see https://stackoverflow.com/questions/46782444/how-to-convert-a-linear-index-to-subscripts-with-support-for-negative-strides
     * @param array<int> $shape
     * @param int $idx
     * @return array<int>
     */
    public static function linearToSub(array $shape, int $idx): array
    {
        $i = count($shape) - 1;
        $arr = [];
        for (; $i >= 0; $i--) {
            $s = $idx % $shape[$i];
            $idx -= $s;
            $idx /= $shape[$i];
            $arr[$i] = $s;
        }

        return array_reverse($arr);
    }

    /**
     * Convert subindexes to a linear index.
     * Converts the subscripts back to a linear index (row major), @param array<int> $strides
     * @param array<int> $subscripts
     * @return int index
     *@see UtilArray::linearToSub()
     */
    public static function subToLinear(array $strides, array $subscripts): int
    {
        $n = 0;
        $len = count($strides);
        $idx = 0;
        for (; $n < $len; $n++) {
            $idx += $subscripts[$n] * $strides[$n];
        }

        return $idx;
    }

    /**
     * Permutate the axes of an array.
     * @see https://numpy.org/doc/stable/reference/generated/numpy.transpose.html#numpy.transpose
     * @param array $arr input of 1-dim array (in row major order)
     * @param array $shape shape of the array (before transposing)
     * @param array $axes permutation of [0, 1, ..., N-1] where N is the number of axes of $arr
     * @return array array with axes permutated
     */
    public static function transpose(array $arr, array $shape, array $axes): array
    {
        $i = 0;
        $len = count($arr);
        $values = [];
        $strides = self::getStrides($shape);
        $stridesTransp = self::swap($strides, $axes);
        $shapeTransp = self::swap($shape, $axes);
        for (; $i < $len; $i++) {
            $multi = self::linearToSub($shapeTransp, $i);
            $idx = self::subToLinear($stridesTransp, $multi);
            $values[$i] = $arr[$idx];
        }

        return $values;
    }

    /**
     * Searches for the values in needle in the haystack and returns an array with positions of the matches.
     * @param array $needle the array values to check
     * @param array $haystack the array values to check against
     * @return array array indexes of the matched values
     */
    public static function getIndex(array $needle, array $haystack): array
    {
        return array_map(static function ($item) use ($haystack) {
            return array_search($item, $haystack, true);
        }, $needle);
    }

}
