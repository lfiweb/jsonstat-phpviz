<?php

namespace jsonstatPhpViz;


use function is_float;
use function is_int;

/**
 * Handle formatting of values.
 */
class Formatter implements FormatterInterface
{

    /**
     * Null values have to be replaced with an empty string in the table cells,
     * otherwise a void element <td/> is created by the DOMDocument, wich is invalid html.
     */
    public string $nullLabel = '';

    /**
     * Format a numeric value from the JSON-stat value property.
     * Format the value according to the number of decimals provided.
     * @param string|int|float|null $val
     * @param int $decimals the number of digits printed after the decimal point
     * @return null|string
     */
    public function formatDecimal(null|string|int|float $val, int $decimals): null|string
    {
        if (is_int($val) || is_float($val)) {
            $val = sprintf('%.' . $decimals . 'f', $val);   // note: sprintf() creates a string
        }

        return $val;
    }

    /**
     * Format a null value from the JSON-stat value property as an empty string.
     * @param string|int|float|null $val
     * @return string
     */
    public function formatNull(null|string|int|float $val): string
    {
        return $val ?? $this->nullLabel;
    }
}