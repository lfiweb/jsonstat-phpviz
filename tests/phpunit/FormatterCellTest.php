<?php
declare(strict_types=1);

namespace jsonstatPhpViz\Test;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Test\TestFactory\JsonstatBuilder;
use PHPUnit\Framework\TestCase;

class FormatterCellTest extends TestCase
{
    /**
     * Test formatting a decimal value.
     * Note: no Reader/JSON-stat is needed, the method is pure.
     */
    public function testFormatDecimal(): void
    {
        $formatter = new FormatterCell(JsonstatBuilder::fromShape([1])->reader());
        self::assertSame('3.80', $formatter->formatDecimal(3.8, 2));
        self::assertSame('9', $formatter->formatDecimal('9', 2)); // strings are left untouched
    }

    /**
     * Test that null is replaced by the configured null label.
     */
    public function testFormatNull(): void
    {
        $formatter = new FormatterCell(JsonstatBuilder::fromShape([1])->reader());
        self::assertSame('', $formatter->formatNull(null));
        $formatter->nullLabel = '-';
        self::assertSame('-', $formatter->formatNull(null));
    }

    /**
     * Test that the number of decimals is read from the unit of the last dimension.
     * Note: this is the one method that actually needs a JSON-stat fixture.
     */
    public function testFormatValueCell(): void
    {
        $reader = JsonstatBuilder::create()
            ->dimension('area', 'Region', ['CH' => 'Schweiz'])
            ->dimension('unit', 'Einheit', ['vol' => 'm³/ha', 'err' => '±%'])
            ->unit(['vol' => 1, 'err' => 0])
            ->reader();
        $formatter = new FormatterCell($reader);

        self::assertSame('3.8', $formatter->formatValueCell(3.8, 0));
        self::assertSame('9', $formatter->formatValueCell(9, 1));
    }
}