<?php

namespace jsonstatPhpViz\src;

use function count;

/**
 * Utility to provide additional array methods.
 * Some are modelled after numpy.
 */
class UtilArray
{
    /**
     * Calculate the product of all array elements.
     * @param array $values
     * @return int
     */
    public static function product(array $values): int
    {
        if (count($values) > 0) {
            $initial = array_shift($values);
            return array_reduce($values, static function ($a, $b) {
                return $a * $b;
            }, $initial);
        }

        return 0;
    }

    /**
     * Calculate two products from array values.
     * The first returned value is the product of all values with an element index equal or higher than the passed one,
     * the second is the product of all values with an index higher. If it is the last element then the product is 1.
     * @param array $values
     * @param int $idx
     * @return array
     * @private
     */
    public static function productUpperNext(array $values, int $idx): array
    {
        $f = [];
        $f[0] = self::productUpper($values, $idx);
        $f[1] = $idx < count($values) ? self::productUpper($values, $idx + 1) : 1;

        return $f;
    }

    /**
     * Calculates the product of all array values with an element index equal or higher than the passed one.
     * @param array $values
     * @param int $idx
     * @return int
     */
    public static function productUpper(array $values, int $idx): int
    {
        $num = 1;
        $len = count($values);
        for ($i = $idx; $i < $len; $i++) {
            $num *= $values[$i];
        }

        return $num;
    }

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
     * Convert a linear index to a multidimensional index.
     * Creates an array of subscripts from the shape,
     * e.g. when called repeatedly from idx[0,1,2,3,...,48] with shape[4,2,3,2], it creates the following sequence:
     * -> [0,0,0,0], [0,0,0,1], [0,0,1,0], [0,0,1,1], [0,0,2,0], [0,0,2,1], [0,1,0,0], [0,1,0,1], ..., [3,1,2,1]
     * @see https://stackoverflow.com/questions/46782444/how-to-convert-a-linear-index-to-subscripts-with-support-for-negative-strides
     * @param array<int> $shape
     * @param int $idx
     * @return array<int>
     */
    public static function linearToMultiDim(array $shape, int $idx): array
    {
        $i = count($shape) - 1;
        $arr = [];
        for (; $i >= 0; $i--) {
            $s = $idx % $shape[$i];
            $idx -= $s;
            $idx /= $shape[$i];
            $arr[$i] = $s;
        }

        return $arr;
    }

    /**
     * Convert a multidimensional index to a linear index.
     * Converts the subscripts back to a linear index, @see UtilArray::linearToMultiDim()
     * @param array<int> $strides
     * @param array<int> $subscripts
     * @return int index
     */
    public static function multiDimToLinear(array $strides, array $subscripts): int
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
            $multi = self::linearToMultiDim($shapeTransp, $i);
            $idx = self::multiDimToLinear($stridesTransp, $multi);
            $values[$i] = $arr[$idx];
        }

        return $values;
    }
}
