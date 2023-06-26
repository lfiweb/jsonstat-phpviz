<?php

namespace jsonstatPhpViz\Test;

use DOMException;
use JsonException;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\RendererTable;
use jsonstatPhpViz\Test\TestFactory\JsonstatReader;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    private Reader $reader;

    /**
     * @throws JsonException
     */
    public function setUp(): void
    {
        $reader = new JsonstatReader();
        $this->reader = $reader->create(__DIR__ . '/../resources/volume.json');
    }

    /**
     * Tests, that the JSON-stat was correctly transposed.
     * @throws DOMException
     */
    public function testTranspose(): void
    {
        $this->reader->transpose([0, 1, 2, 4, 3, 5]);
        $table = new RendererTable($this->reader, 2);
        $table->excludeOneDim = true;
        $html = file_get_contents(__DIR__ . '/../resources/volume-transposed.html');
        self::assertSame($html, $table->render());

        // transpose back
        $this->reader->transpose([0, 1, 2, 4, 3, 5]);

        // transpose dimension of size one
        $this->reader->transpose([0, 4, 2, 3, 1, 5]);
        $table = new RendererTable($this->reader, 3);
        $table->excludeOneDim = true;
        $html = file_get_contents(__DIR__ . '/../resources/volume-onedim-transposed.html');
        self::assertSame($html, $table->render());
    }

    public function testGetCategoryLabel(): void
    {
        $label = $this->reader->getCategoryLabel('BHDKL', '3');
        self::assertSame('36-51 cm', $label);
        $label = $this->reader->getCategoryLabel('N4P12345');
        self::assertSame('grid NFI4 2009-2013', $label);
    }

    public function testGetDimensionLabel(): void
    {
        $label = $this->reader->getDimensionLabel('BHDKL');
        self::assertSame('diameter classes', $label);
    }

    public function testGetDimensionSizes(): void
    {
        $size = $this->reader->getDimensionSizes(false);
        self::assertSame([1, 1, 6, 3, 6, 2], $size);
        $size = $this->reader->getDimensionSizes();
        self::assertSame([6, 3, 6, 2], $size);
    }

    /**
     * Test, that the category id is returned, whether the index property is an array or an object.
     * @return void
     * @throws JsonException
     */
    public function testGetCategoryId(): void
    {
        // test id from array
        $id = $this->reader->getCategoryId('BHDKL', 5);
        self::assertSame('999999', $id);

        // test id from object
        $obj = json_decode('{
            "0": 0,
            "1": 1,
            "2": 2,
            "3": 3,
            "4": 4,
            "999999": 5
        }', false, 512, JSON_THROW_ON_ERROR);
        $this->reader->data->dimension->{'BHDKL'}->category->index = $obj;
        $id = $this->reader->getCategoryId('BHDKL', 5);
        self::assertSame('999999', $id);
    }

    public function testGetDimensionId(): void
    {
        $id = $this->reader->getDimensionId(4);
        self::assertSame('PRODREG', $id);
    }

    public function testGetNumValues(): void
    {
        $num = $this->reader->getNumValues();
        self::assertSame(216, $num);
    }
}
