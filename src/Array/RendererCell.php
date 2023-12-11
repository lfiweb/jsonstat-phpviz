<?php

namespace jsonstatPhpViz\Array;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;

class RendererCell
{
    protected RendererTable $table;
    protected Reader $reader;
    protected FormatterCell $formatter;

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
     * @param int $rowIdx
     * @return void the content of the cell
     */
    public function valueCell(int $offset, int $rowIdx): void
    {
        $cellIdx = $offset % $this->table->numValueCols;
        $x = $rowIdx + $this->table->numHeaderRows;
        $y = $cellIdx + $this->table->numLabelCols;
        $val = $this->reader->data->value[$offset];

        $this->table->data[$x][$y] = $this->formatter->formatValueCell($val, $offset);
    }

    /**
     * Appends cells with labels to the row.
     * Inserts the label as a HTMLTableHeaderElement at the end of the row.
     * @param int $rowIdx the row index of the body rows only
     */
    public function labelCells(int $rowIdx): void
    {
        $table = $this->table;
        $rowStrides = UtilArray::getStrides($table->rowDims);
        $x = $rowIdx + $table->numHeaderRows;

        for ($y = 0; $y < $table->numLabelCols; $y++) {
            $stride = $rowStrides[$y];
            $product = $table->shape[$y] * $stride;
            $reader = $this->reader;
            $catIdx = floor($rowIdx % $product / $stride);
            $id = $reader->getDimensionId($table->numOneDim + $y);
            $categId = $reader->getCategoryId($id, $catIdx);
            if ($table->repeatLabels === true || $rowIdx % $stride === 0) {
                $label = $reader->getCategoryLabel($id, $categId);
            }
            else {
                $label = '';
            }
            $this->headerCell($label, $x, $y);
        }
    }

    /**
     * Add the header cell to the 2dim-array.
     * @param string $label
     * @param float|int $x
     * @param int $y
     * @return void
     */
    public function headerCell(string $label, float|int $x, int $y): void
    {
        $this->table->data[$x][$y] = $this->formatter->formatHeaderCell($label);
    }

    /**
     * Creates the cells for the headers of the label columns.
     */
    public function headerLabelCells(int $rowIdx): void
    {
        for ($y = 0; $y < $this->table->numLabelCols; $y++) {
            $id = $this->reader->getDimensionId($this->table->numOneDim + $y);
            $label = $this->reader->getDimensionLabel($id);
            $this->headerCell($label, $rowIdx, $y);
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
        // remember: we render two rows with headings per column dimension, e.g.
        //      one for the dimension label and one for the category label
        $dimIdx = $table->numRowDim + (int)floor($rowIdx / 2);
        $stride = $table->strides[$dimIdx];
        $z = $rowIdx % 2;
        $product = $table->shape[$dimIdx] * $stride;
        for ($i = 0; $i < $table->numValueCols; $i++) {
            $y = $i + $table->numLabelCols;
            $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
            if ($z === 0) { // set attributes for dimension label cell
                $label = $reader->getDimensionLabel($id);
            } else {    // set attributes for category label cell
                $catIdx = floor(($i % $product) / $stride);
                $catId = $reader->getCategoryId($id, $catIdx);
                $label = $reader->getCategoryLabel($id, $catId);
            }
            $this->headerCell($label, $rowIdx, $y);
        }
    }
}