<?php
declare(strict_types=1);

namespace jsonstatPhpViz\Test;

use DOMException;
use JsonException;
use jsonstatPhpViz\Html\RendererTable;
use jsonstatPhpViz\Test\TestFactory\JsonstatReader;
use jsonstatPhpViz\Test\TestFactory\RendererTable as FactoryRendererTable;
use PHPUnit\Framework\TestCase;
use function array_slice;
use function count;

class RendererTableTest extends TestCase
{
    private JsonstatReader $factory;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->factory = new JsonstatReader();
    }

    /**
     * Test the render method.
     * @return void
     * @throws DOMException|JsonException
     */
    public function testRenderHtml(): void
    {
        $reader = $this->factory->create(__DIR__ . '/../resources/integer.json');
        $fileHtml = __DIR__ . '/../resources/integer.html';
        $table = new RendererTable($reader);
        $htmlTable = $table->render();
        self::assertStringEqualsFile($fileHtml, $htmlTable);
    }

    /**
     * Test, that the JSON-stat was correctly transposed.
     * @throws DOMException|JsonException
     */
    public function testRendererTransposed(): void
    {
        // transpose a dimension having size > 1 while excluding dimensions of size one
        $reader = $this->factory->create(__DIR__ . '/../resources/volume.json');
        $reader->transpose([0, 1, 2, 4, 3, 5]);
        $table = new RendererTable($reader);
        $table->excludeOneDim = true;
        $path = __DIR__ . '/../resources/volume-transposed.html';
        self::assertStringEqualsFile($path, $table->render());

        // transpose back
        $reader->transpose([0, 1, 2, 4, 3, 5]);

        // transpose a dimension of size one while excluding dimensions of size one
        //  note: this time, the transposed is of size one and should not be excluded since it is not at the beginning
        //      of the sizes array, which gives the order of rendering.
        // also we test setting the number of row dimensions to three
        $numRowDim = 3;
        $reader->transpose([0, 4, 2, 3, 1, 5]);
        $table = new RendererTable($reader, $numRowDim);
        $table->excludeOneDim = true;
        $path = __DIR__ . '/../resources/volume-onedim-transposed.html';
        self::assertStringEqualsFile($path, $table->render());
    }

    /**
     * Test, that the renderer handles null values (in the JSON-stat) correctly.
     * @return void
     * @throws DOMException
     * @throws JsonException
     */
    public function testRenderNull(): void
    {
        // note: top-left header cell never has content, no need to set anything to null for testing
        $reader = $this->factory->create(__DIR__ . '/../resources/integer.json');
        $reader->data->value[1] = null; // inject a null value
        $table = new RendererTable($reader);
        $htmlTable = $table->render();

        self::assertStringNotContainsString('<td/>', $htmlTable);   // this would be invalid on a non-void element
        self::assertStringNotContainsString('<th/>', $htmlTable);   // this would be invalid on a non-void element
    }

    /**
     * Test, that integers and floats are rendered with the number of decimals defined in the unit of the category.
     * @throws DOMException
     * @throws JsonException
     */
    public function testRenderDecimals(): void
    {
        $reader = $this->factory->create(__DIR__ . '/../resources/volume.json');
        $rendererTable = new RendererTable($reader);
        $rendererTable->excludeOneDim = false;
        $rendererTable->render();
        $table = $rendererTable->getDom();
        $cell = FactoryRendererTable::getValueCell($table, 0);
        self::assertEquals('3.8', $cell->textContent);
        $cell = FactoryRendererTable::getValueCell($table, 1);
        self::assertEquals('9', $cell->textContent);
        $cell = FactoryRendererTable::getValueCell($table, 44);
        self::assertEquals('7.0', $cell->textContent);

        $rendererTable = new RendererTable($reader);
        $rendererTable->excludeOneDim = true;
        $rendererTable->render();
        $cell = FactoryRendererTable::getValueCell($table, 44);
        self::assertEquals('7.0', $cell->textContent);
    }

    /**
     * Test, that the correct number of rows and columns are created when using the numRowDim argument.
     * @throws DOMException
     * @throws JsonException
     */
    public function testRenderRowDim(): void
    {
        $reader = $this->factory->create(__DIR__ . '/../resources/volume.json');
        $size = $reader->data->size;
        $len = count($size) + 1;
        $i = 0;
        $x = [];
        for (; $i < $len; $i++) {
            $renderer = new RendererTable($reader);
            $renderer->setNumRowDim($i);
            $renderer->render();
            $nlX = FactoryRendererTable::getTBodyChildNodes($renderer->getDom());
            $nlY = FactoryRendererTable::getTheadLastChildNodes($renderer->getDom());
            self::assertSame(array_product($x), $nlX->length);
            self::assertSame(array_product($size) + $i, $nlY->length);
            $x[] = array_shift($size);
        }
    }

    /**
     * Test, that the correct number of dimensions used to render rows is returned.
     * @return void
     * @throws JsonException
     */
    public function testNumRowDimAuto(): void
    {
        $reader = $this->factory->create(__DIR__ . '/../resources/volume.json');
        $table = new RendererTable($reader);
        self::assertSame(4, $table->numRowDimAuto());
        $table->excludeOneDim = true;
        self::assertSame(2, $table->numRowDimAuto());

        $reader = $this->factory->create(__DIR__ . '/../resources/oecd.json');
        $table = new RendererTable($reader);
        self::assertSame(1, $table->numRowDimAuto());
        $table->excludeOneDim = true;
        self::assertSame(1, $table->numRowDimAuto());
    }

    /**
     * @throws DOMException
     * @throws JsonException
     */
    public function testExcludeOneDim(): void
    {
        $reader = $this->factory->create(__DIR__ . '/../resources/volume.json');
        $size = $reader->data->size;
        $x = array_slice($size, 0, 4);
        $y = array_slice($size, 4);
        $renderer = new RendererTable($reader, 2);
        $renderer->excludeOneDim = true;
        $renderer->render();
        $nlX = FactoryRendererTable::getTBodyChildNodes($renderer->getDom());
        $nlY = FactoryRendererTable::getTheadLastChildNodes($renderer->getDom());
        self::assertSame(array_product($x), $nlX->length);
        self::assertSame(array_product($y) + 2, $nlY->length);
    }

    /**
     * Test, that html in the JSON-stat is encoded.
     * @return void
     * @throws DOMException
     * @throws JsonException
     */
    public function testHtmlCell(): void
    {
        $html = '<i>Test:</i> cell';
        $htmlEncoded = htmlspecialchars($html, ENT_HTML5, 'UTF-8');
        $reader = $this->factory->create(__DIR__ . '/../resources/integer.json');
        $reader->data->value[3] = $html;
        $reader->data->dimension->{'A'}->label = $html;

        $table = new RendererTable($reader);
        $htmlTable = $table->render();
        self::assertStringNotContainsString($html, $htmlTable);
        self::assertStringContainsString($htmlEncoded, $htmlTable);
    }

    /**
     * Test, that html from the JSON-stat is encoded in the caption except when explicitly being set.
     * @return void
     * @throws DOMException
     * @throws JsonException
     */
    public function testHtmlCaption(): void
    {
        $html = '<p><b>Test:</b> caption</p>';
        $htmlEncoded = htmlspecialchars($html, ENT_HTML5, 'UTF-8');
        $reader = $this->factory->create(__DIR__ . '/../resources/integer.json');
        $reader->data->label = $html;

        $table = new RendererTable($reader);
        $htmlTable = $table->render();
        // html in JSON-stat should get encoded:
        self::assertStringContainsString($htmlEncoded, $htmlTable);
        // html set explicitly should be allowed and not encoded:
        $table->caption = $html;
        $htmlTable = $table->render();
        self::assertStringContainsString($html, $htmlTable);
    }

    /**
     * Test, that the label row of the last dimension is excluded from rendering.
     * @return void
     * @throws DOMException
     * @throws JsonException
     */
    public function testNoLabelLastDim(): void
    {
        $reader = $this->factory->create(__DIR__ . '/../resources/integer.json');
        $renderer = new RendererTable($reader, 2);
        $renderer->noLabelLastDim = true;
        $renderer->render();
        $num = $renderer->getDom()->getElementsByTagName('thead')->item(0)->childNodes->length;
        self::assertSame(3, $num);
    }
}