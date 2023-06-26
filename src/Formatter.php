<?php

namespace jsonstatPhpViz;


use function is_float;
use function is_int;

class Formatter
{

    /**
     * Null values have to be replaced with an empty string in the table cells,
     * otherwise a void element <td/> is created, wich is invalid html.
     */
    public string $nullLabel = '';

    /**
     * Format the value
     * Formats according to the format defined in the json-stat by parameter Zahlenformat (power and decimal places).
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
     * @param string|int|float|null $val
     * @return string
     */
    public function formatNull(null|string|int|float $val): string
    {
        return $val ?? $this->nullLabel;
    }
}
