<?php

namespace jsonstatPhpViz;

use function count;
use function is_float;
use function is_int;

/**
 * Handle formatting of table cells.
 */
class FormatterCell
{
    /**
     * Construct the formatter of the table cells.
     * @param Reader $reader
     */
    public function __construct(public readonly Reader $reader)
    {
    }

    /**
     * Null values have to be replaced with an empty string in the table cells,
     * otherwise a void element <td/> is created by the DOMDocument, wich is invalid html.
     */
    public string $nullLabel = '';

    /**
     * Format the value.
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
     * Render a null value as an empty string.
     * @param string|int|float|null $val
     * @return string
     */
    public function formatNull(null|string|int|float $val): string
    {
        return $val ?? $this->nullLabel;
    }

    /**
     * Format a header cell <th>
     * Format cells used as a header for group of columns or rows (headings).
     * @param string|null $str
     * @return string
     */
    public function formatHeaderCell(null|string $str): string
    {
        return $this->formatNull($str);
    }

    /**
     * Format a value cell <td>.
     * Format a cell used for the JSON-stat value property.
     * Note: If value is an int or float, the number of decimals from the unit of the category is used if available.
     * @param string|int|float|null $val
     * @param int $offset
     * @return string
     */
    public function formatValueCell(null|string|int|float $val, int $offset): string
    {
        $stat = $this->reader;
        $idxLastDim = count($stat->data->id) - 1;
        $dimId = $stat->getDimensionId($idxLastDim);
        if ($stat->hasDecimal($dimId)) {
            $categoryId = $stat->getCategoryId($dimId, $offset % $stat->data->size[$idxLastDim]);
            $decimals = $stat->getDecimal($dimId, $categoryId);
            $val = $this->formatDecimal($val, $decimals);
        }

        return $this->formatNull($val);
    }

}