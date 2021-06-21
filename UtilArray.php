<?php

namespace jsonstatPhpViz;

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
     * The first returned value is the product of all values with an element index equal or higher than the passed one, the
     * second is the product of all values with an index higher. If it is the last element then the product is 1.
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
}