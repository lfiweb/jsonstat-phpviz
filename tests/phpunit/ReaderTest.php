<?php

namespace jsonstatPhpViz\Test;

use JsonException;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\Test\TestFactory\JsonstatBuilder;
use jsonstatPhpViz\Test\TestFactory\JsonstatReader;
use PHPUnit\Framework\TestCase;
use stdClass;

class ReaderTest extends TestCase
{
    private Reader $reader;

    /**
     * @throws JsonException
     */
    protected function setUp(): void
    {
        $factory = new JsonstatReader();
        $this->reader = $factory->create(__DIR__ . '/../resources/oecd.json');
    }

    /**
     * Test that the axes of the value array are permutated.
     * @throws JsonException
     */
    public function testTranspose(): void
    {
        // 2x3 -> [[1,2,3],[4,5,6]] transposed is 3x2 -> [[1,4],[2,5],[3,6]]
        $reader = JsonstatBuilder::fromShape([2, 3])->reader();
        $reader->transpose([1, 0]);
        self::assertSame(['B', 'A'], $reader->data->id);
        self::assertSame([3, 2], $reader->data->size);
        self::assertSame([1, 4, 2, 5, 3, 6], $reader->data->value);

        // transposing with the same axes restores the original
        $reader->transpose([1, 0]);
        self::assertSame([2, 3], $reader->data->size);
        self::assertSame([1, 2, 3, 4, 5, 6], $reader->data->value);
    }

    /**
     * Test that a dimension of size one is transposed.
     */
    public function testTransposeOneDim(): void
    {
        $reader = JsonstatBuilder::fromShape([1, 2, 3])->reader();
        $reader->transpose([1, 0, 2]);
        self::assertSame(['B', 'A', 'C'], $reader->data->id);
        self::assertSame([2, 1, 3], $reader->data->size);
        self::assertSame([1, 2, 3, 4, 5, 6], $reader->data->value);
    }


    /**
     * Test that all JSON-stat schema variants of the category label property are handled correctly.
     * @throws JsonException
     */
    public function testGetCategoryLabel(): void
    {
        $reader = JsonstatBuilder::create()
            // size one, with a category.label but without a category.index property
            ->dimension('concept', 'indicator', ['UNR' => 'unemployment rate'])
            // without a category.label property, so the index keys act as labels
            ->dimension('year', '2003-2004', ['2003', '2004'])->withoutCategoryLabels()
            // with a category.label property and a category.index object
            ->dimension('area', 'countries', ['CH' => 'Switzerland', 'DE' => 'Germany'])
            // with a category.label property and a category.index array
            ->dimension('region', 'Produktionsregion', ['1' => 'Jura', '3' => 'Voralpen'])->indexAsList()
            ->reader();

        self::assertSame('unemployment rate', $reader->getCategoryLabel('concept', 'UNR'));
        self::assertSame('2003', $reader->getCategoryLabel('year', '2003'));
        self::assertSame('Switzerland', $reader->getCategoryLabel('area', 'CH'));
        self::assertSame('Voralpen', $reader->getCategoryLabel('region', '3'));
    }

    /**
     * Test getting the dimension label.
     * @return void
     */
    public function testGetDimensionLabel(): void
    {
        $label = $this->reader->getDimensionLabel('year');
        self::assertSame('2003-2014', $label);
    }

    /**
     * Test that dimensions of size one are only excluded when continuous from index zero.
     */
    public function testGetDimensionSizes(): void
    {
        $reader = JsonstatBuilder::fromShape([1, 1, 3, 2, 1])->reader();
        self::assertSame([1, 1, 3, 2, 1], $reader->getDimensionSizes(false));
        self::assertSame([3, 2, 1], $reader->getDimensionSizes());
    }

    /**
     * Test that the category id is returned, whether the index property is an array or an object.
     * @return void
     * @throws JsonException
     */
    public function testGetCategoryId(): void
    {
        // test id from array
        $id = $this->reader->getCategoryId('area', 30);
        self::assertSame('CH', $id);

        // test id from object
        $arr = json_decode('["AU", "AT", "BE", "CA", "CL", "CZ", "DK"]', false, 512, JSON_THROW_ON_ERROR);
        $this->reader->data->dimension->{'area'}->category->index = $arr;
        $id = $this->reader->getCategoryId('area', 2);
        self::assertSame('BE', $id);
    }

    /**
     * Test returning the dimension id.
     * @return void
     */
    public function testGetDimensionId(): void
    {
        $id = $this->reader->getDimensionId(0);
        self::assertSame('concept', $id);
    }

    /**
     * Test calculating the number of items in the value array.
     * @return void
     */
    public function testGetNumValues(): void
    {
        $num = $this->reader->getNumValues();
        self::assertSame(432, $num);
    }
}
