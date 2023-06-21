<?php
declare(strict_types=1);

namespace jsonstatPhpViz\tests\phpunit;

use jsonstatPhpViz\src\UtilArray;
use jsonstatPhpViz\tests\phpunit\TestFactory\UtilArrayFactory;
use PHPUnit\Framework\TestCase;

class UtilArrayTest extends TestCase
{

    public function testSwap()
    {
        $arr = UtilArray::swap([0 => 'a', 1 => 'b', 2 => 'c', 3 => 'd', 4 => 'e', 5 => 'f'], [0, 1, 3, 5, 4, 2]);
        self::assertSame([0 => 'a', 1 => 'b', 2 => 'd', 3 => 'f', 4 => 'e', 5 => 'c'], $arr);
    }

    public function testGetStrides()
    {
        $arr = UtilArray::getStrides([4, 5, 1, 7, 3]);
        self::assertSame([0 => 105, 1 => 21, 2 => 21, 3 => 3, 4 => 1], $arr);
    }

    public function testLinearToSub()
    {
        $shape = [3, 6, 2];
        for ($i = 0; $i < array_product($shape); $i++) {
            $arr[] = UtilArray::linearToSub($shape, $i);
        }
        self::assertSame($arr, UtilArrayFactory::ARRAY_SUBSCRIPT);
    }

    public function testTranspose()
    {
        $arr = UtilArray::transpose(UtilArrayFactory::ARRAY_INT, [3, 2, 4, 2], [3, 1, 2, 0]);
        self::assertSame(UtilArrayFactory::ARRAY_INT_TRANSPOSED, $arr);
        $arr = UtilArray::transpose($arr, [2, 2, 4, 3], [3, 1, 2, 0]);
        self::assertSame(UtilArrayFactory::ARRAY_INT, $arr);
    }

    public function testGetIndex()
    {
        $arr = UtilArray::getIndex([7, 2, 3], [7, 1, 2, 7.7, 9, 57, 3, 7]);
        self::assertSame([0, 2, 6], $arr);
    }

    public function testSubToLinear()
    {
        // $shape = [3, 2, 4, 2] -> $strides = [16, 8, 2, 1]
        // note: you can run render on integer.json to visually check
        $arr = UtilArray::subToLinear([16, 8, 2, 1], [1, 0, 0, 0]);
        self::assertSame(16, $arr);
        $arr = UtilArray::subToLinear([16, 8, 2, 1], [2, 1, 2, 0]);
        self::assertSame(44, $arr);
    }
}
