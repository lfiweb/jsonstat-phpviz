<?php

namespace jsonstatPhpViz;

interface FormatterInterface
{

    /**
     * Format a numeric value from the JSON-stat value property.
     * Format the value according to the number of decimals provided.
     * @param string|int|float|null $val
     * @param int $decimals the number of digits printed after the decimal point
     * @return null|string
     */
    public function formatDecimal(null|string|int|float $val, int $decimals): null|string;

    /**
     * Format a null value from the JSON-stat value property as a character or a string.
     * @param string|int|float|null $val
     * @return string
     */
    public function formatNull(null|string|int|float $val): string;
}