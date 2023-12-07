<?php

namespace jsonstatPhpViz\Tsv;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\UtilArray;

class RendererCell
{
    protected RendererTable $table;
    private FormatterCell $formatter;

    /**
     * @param RendererTable $rendererTable
     * @param FormatterCell $cellFormatter
     */
    public function __construct(RendererTable $rendererTable, FormatterCell $cellFormatter)
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

        return $this->formatter->formatValueCell($val, $offset);
    }

    /**
     * Appends cells with labels to the row.
     * Inserts the label as a HTMLTableHeaderElement at the end of the row.
     * @param int $rowIdxBody the row index of the body rows only
     */
    public function labelCells(int $rowIdxBody): void
    {
        $table = $this->table;
        $rowStrides = UtilArray::getStrides($table->rowDims);

        for ($i = 0; $i < $table->numLabelCols; $i++) {
            $stride = $rowStrides[$i];
            $product = $table->shape[$i] * $stride;
            $reader = $this->reader;
            $catIdx = floor($rowIdxBody % $product / $stride);
            $id = $reader->getDimensionId($table->numOneDim + $i);
            $categId = $reader->getCategoryId($id, $catIdx);
            $label = $reader->getCategoryLabel($id, $categId);
            $this->table->tsv .= $this->formatter->formatHeaderCell($label).$this->table->separatorCol;
        }
    }

    /**
     * Creates the cells for the headers of the label columns.
     */
    public function headerLabelCells(): void
    {
        for ($k = 0; $k < $this->table->numLabelCols; $k++) {
            $id = $this->reader->getDimensionId($this->table->numOneDim + $k);
            $label = $this->reader->getDimensionLabel($id);
            $this->table->tsv .= $this->formatter->formatHeaderCell($label).$this->table->separatorCol;
        }
    }

    /**
     * Creates the cells for the headers of the value columns.
     * @param int $rowIdx row index
     */
    public function headerValueCells(int $rowIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;
        $csv = '';
        // remember: we render two rows with headings per column dimension, e.g.
        //      one for the dimension label and one for the category label
        $dimIdx = $table->numRowDim + (int)floor($rowIdx / 2);
        $stride = $table->strides[$dimIdx];
        $z = $rowIdx % 2;
        $product = $table->shape[$dimIdx] * $stride;
        for ($i = 0; $i < $table->numValueCols; $i++) {
            $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
            if ($z === 0) { // set attributes for dimension label cell
                $label = $reader->getDimensionLabel($id);
            } else {    // set attributes for category label cell
                $catIdx = floor(($i % $product) / $stride);
                $catId = $reader->getCategoryId($id, $catIdx);
                $label = $reader->getCategoryLabel($id, $catId);
            }
            $csv .= $this->formatter->formatHeaderCell($label).$this->table->separatorCol;
        }
        $this->table->tsv .= rtrim($csv, $this->table->separatorCol);
    }
}