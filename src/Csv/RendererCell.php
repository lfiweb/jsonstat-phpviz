<?php

namespace jsonstatPhpViz\Csv;

use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;
use function count;

class RendererCell
{
    protected RendererTable $table;
    protected Reader $reader;

    protected Formatter $formatter;
    /*private string $separator = "\t";*/
    private string $separatorCol = ",";


    /**
     * @param RendererTable $rendererTable
     * @param Formatter $cellFormatter
     */
    public function __construct(RendererTable $rendererTable, Formatter $cellFormatter)
    {
        $this->table = $rendererTable;
        $this->reader = $this->table->reader;
        $this->formatter = $cellFormatter;
    }

    /**
     * Appends cells with values to the row.
     * Inserts a HTMLTableCellElement at the end of the row with a value taken from the values at given offset.
     * @param int $offset
     * @return string the content of the cell
     */
    public function valueCell(int $offset): string
    {
        $val = $this->reader->data->value[$offset];
        $val = $this->formatValueCell($val, $offset);
        $this->table->csv .= $val.$this->separatorCol;

        return $val;
    }

    /**
     * Appends cells with labels to the row.
     * Inserts the label as a HTMLTableHeaderElement at the end of the row.
     * @param int $rowIdxBody row index
     */
    public function labelCells(int $rowIdxBody): void
    {
        $table = $this->table;
        $rowStrides = UtilArray::getStrides($table->rowDims);

        for ($i = 0; $i < $table->numLabelCols; $i++) {
            $dimIdx = $i;
            $stride = $rowStrides[$dimIdx];
            $product = $table->shape[$dimIdx] * $stride;
            $reader = $this->reader;
            $catIdx = floor($rowIdxBody % $product / $stride);
            $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
            $categId = $reader->getCategoryId($id, $catIdx);
            $label = $reader->getCategoryLabel($id, $categId);
            $this->table->csv .= $this->formatHeaderCell($label).$this->separatorCol;
        }
    }


    /**
     * Creates the cells for the headers of the label columns.
     * @param int $rowIdx
     */
    public function headerLabelCells(int $rowIdx): void
    {
        for ($k = 0; $k < $this->table->numLabelCols; $k++) {
            $label = null;

            if ($rowIdx === $this->table->numHeaderRows - 1) { // last header row
                $id = $this->reader->getDimensionId($this->table->numOneDim + $k);
                $label = $this->reader->getDimensionLabel($id);
            }
            $this->table->csv .= $this->formatHeaderCell($label).$this->separatorCol;
        }
    }

    /**
     * Creates the cells for the headers of the value columns.
     * @param int $rowIdx
     */
    public function headerValueCells(int $rowIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;

        // remember: we render two rows with headings per column dimension, e.g.
        //      one for the dimension label and one for the category label
        $dimIdx = $table->numRowDim + (int)floor($rowIdx / 2);
        $stride = $table->strides[$dimIdx];
        $product = $table->shape[$dimIdx] * $stride;
        for ($i = 0; $i < $table->numValueCols; $i++) {
            $z = $rowIdx % 2;
            $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
            if ($z === 0) { // set attributes for dimension label cell
                $label = $reader->getDimensionLabel($id);
            } else {    // set attributes for category label cell
                $catIdx = floor(($i % $product) / $stride);
                $catId = $reader->getCategoryId($id, $catIdx);
                $label = $reader->getCategoryLabel($id, $catId);
            }
            $this->table->csv .= $this->formatHeaderCell($label).$this->separatorCol;
        }
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