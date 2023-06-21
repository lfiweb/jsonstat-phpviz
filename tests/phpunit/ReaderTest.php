<?php

namespace jsonstatPhpViz\tests\phpunit;

use JsonException;
use jsonstatPhpViz\src\Reader;
use jsonstatPhpViz\src\RendererTable;
use jsonstatPhpViz\tests\phpunit\TestFactory\JsonstatReader;
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

    public function testTranspose(): void
    {
        /*
        $html = file_get_contents(__DIR__.'/../resources/volume-transposed.html');

        $this->reader->transpose([0, 1, 2, 4, 3, 5]);
        $table = new RendererTable($this->reader, 2);
        self::assertSame($html, $table->render());
*/
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

    public function testGetCategoryId(): void
    {
        $id = $this->reader->getCategoryId('concept', 0);
        self::assertSame('1', $id);
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