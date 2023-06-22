<?php
declare(strict_types=1);

namespace jsonstatPhpViz\tests\phpunit;

use DOMException;
use JsonException;
use jsonstatPhpViz\src\RendererTable;
use jsonstatPhpViz\tests\phpunit\TestFactory\JsonstatReader;
use jsonstatPhpViz\tests\phpunit\TestFactory\RendererTable as FactoryRendererTable;
use PHPUnit\Framework\TestCase;
use function array_slice;
use function count;

class RendererTableTest extends TestCase
{
    private JsonstatReader $reader;

    /**
     * This method is called before each test.
     */
    public function setUp(): void
    {
        $this->reader = new JsonstatReader();
    }

    /**
     * Test the render method.
     * @return void
     * @throws DOMException|JsonException
     */
    public function testRenderHtml(): void
    {
        $reader = $this->reader->create(__DIR__ . '/../resources/integer.json');
        $fileHtml = __DIR__ . '/../resources/integer.html';
        $table = new RendererTable($reader);
        $htmlTable = $table->render();
        self::assertStringEqualsFile($fileHtml, $htmlTable);
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
        $reader = $this->reader->create(__DIR__ . '/../resources/integer.json');
        $reader->data->value[1] = null; // inject a null value
        $table = new RendererTable($reader);
        $htmlTable = $table->render();

        self::assertStringNotContainsString('<td/>', $htmlTable);   // this would be invalid on a non-void element
        self::assertStringNotContainsString('<th/>', $htmlTable);   // this would be invalid on a non-void element
    }

    /**
     * Test, that the correct number of rows and columns are created when using the numRowDim argument.
     * @throws DOMException
     * @throws JsonException
     */
    public function testRenderRowDim(): void
    {
        $reader = $this->reader->create(__DIR__ . '/../resources/volume.json');
        $size = $reader->data->size;
        $len = count($size) + 1;
        $i = 0;
        $x = [];
        for (; $i < $len; $i++) {
            $renderer = new RendererTable($reader);
            $renderer->setNumRowDim($i);
            $table = $renderer->render(false);
            $nlX = FactoryRendererTable::getTBodyChildNodes($table);
            $nlY = FactoryRendererTable::getTheadLastChildNodes($table);
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
        $reader = $this->reader->create(__DIR__ . '/../resources/volume.json');
        $table = new RendererTable($reader);
        self::assertSame(4, $table->numRowDimAuto());
        $table->excludeOneDim = true;
        self::assertSame(2, $table->numRowDimAuto());

        $reader = $this->reader->create(__DIR__ . '/../resources/oecd.json');
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
        $reader = $this->reader->create(__DIR__ . '/../resources/volume.json');
        $size = $reader->data->size;
        $x = array_slice($size, 0, 4);
        $y = array_slice($size, 4);
        $renderer = new RendererTable($reader, 2);
        $renderer->excludeOneDim = true;
        $table = $renderer->render(false);
        $nlX = FactoryRendererTable::getTBodyChildNodes($table);
        $nlY = FactoryRendererTable::getTheadLastChildNodes($table);
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
        $reader = $this->reader->create(__DIR__ . '/../resources/integer.json');
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
        $reader = $this->reader->create(__DIR__ . '/../resources/integer.json');
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
    public function testNoLabelLastDim() {
        $reader = $this->reader->create(__DIR__ . '/../resources/integer.json');
        $table = new RendererTable($reader, 2);
        $table->noLabelLastDim = true;
        $domTable = $table->render(false);
        $num = $domTable->getElementsByTagName('thead')->item(0)->childNodes->length;
        self::assertSame(3, $num);
    }
}
