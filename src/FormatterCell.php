<?php

namespace jsonstatPhpViz;

class FormatterCell
{
    public function __construct(public readonly Reader $reader, public readonly Formatter $formatter)
    {
    }

    /**
     * Format a head cell <th>
     * Format cells used as a header for group of columns or rows (headings).
     * @param string|null $str
     * @return string
     */
    public function formatHeaderCell(null|string $str): string
    {
        return $this->formatter->formatNull($str);
    }

    /**
     * Format a data cell <td>.
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
            $val = $this->formatter->formatDecimal($val, $decimals);
        }

        return $this->formatter->formatNull($val);
    }

}