<?php

namespace jsonstatPhpViz\Test;

use JsonException;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\Test\TestFactory\JsonstatReader;
use PHPUnit\Framework\TestCase;
use stdClass;

class ReaderTest extends TestCase
{
    private Reader $reader;

    /**
     * @throws JsonException
     */
    public function setUp(): void
    {
        $factory = new JsonstatReader();
        $this->reader = $factory->create(__DIR__ . '/../resources/oecd.json');
    }

    /**
     * Test, that the JSON-stat was correctly transposed.
     * @throws JsonException
     */
    public function testTranspose(): void
    {
        $factory = new JsonstatReader();
        $reader = $factory->create(__DIR__ . '/../resources/volume.json');

        $reader->transpose([0, 1, 4, 3, 2, 5]);
        $file = __DIR__ . '/../resources/volume-transposed.json';
        self::assertJsonStringEqualsJsonFile($file, $factory->getJsonstat($reader));

        // transpose back
        $reader->transpose([0, 1, 4, 3, 2, 5]);
        $file = __DIR__ . '/../resources/volume.json';
        self::assertJsonStringEqualsJsonFile($file, $factory->getJsonstat($reader));

        // this time, transpose a dimension of size one
        $reader->transpose([0, 4, 2, 3, 1, 5]);
        $file = __DIR__ . '/../resources/volume-onedim-transposed.json';
        self::assertJsonStringEqualsJsonFile($file, $factory->getJsonstat($reader));
    }

    /**
     * Test, that all JSON-stat schema variants of the category label property are handled correctly.
     * @throws JsonException
     */
    public function testGetCategoryLabel(): void
    {
        // dimension of size one with a category.label property, but without a category.index property
        $label = $this->reader->getCategoryLabel('concept', 'UNR');
        self::assertSame('unemployment rate', $label);

        // dimension, without a category.label property, where the category.index property is an object
        $label = $this->reader->getCategoryLabel('year', '2006');
        self::assertSame('2006', $label);

        // dimension, where the category label property is provided and the category index is an object
        $label = $this->reader->getCategoryLabel('area', 'CH');
        self::assertSame('Switzerland', $label);

        $factory = new JsonstatReader();
        $reader = $factory->create(__DIR__ . '/../resources/volume.json');

        // dimension of size one without a category.label property, but a category.index object
        unset($reader->data->dimension->{'period'}->category->label);
        $obj = new stdClass();
        $obj->{'2003'} = 0;
        $reader->data->dimension->{'period'}->category->index = $obj;
        $label = $reader->getCategoryLabel('period', '2003');
        self::assertSame('2003', $label);

        // dimension, where the category label property is provided and the category index is an array
        $label = $reader->getCategoryLabel('2', '3');
        self::assertSame('Voralpen', $label);
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
     * Test returning the dimension sizes.
     * @return void
     */
    public function testGetDimensionSizes(): void
    {
        $size = $this->reader->getDimensionSizes(false);
        self::assertSame([1, 36, 12], $size);
        $size = $this->reader->getDimensionSizes();
        self::assertSame([36, 12], $size);
    }

    /**
     * Test, that the category id is returned, whether the index property is an array or an object.
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
