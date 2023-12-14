<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;

class CellArray
{
    protected Reader $reader;
    protected FormatterCell $formatter;
    protected AbstractTable $table;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     * @param AbstractTable $rendererTable
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader, AbstractTable $rendererTable)
    {
        $this->reader = $reader;
        $this->formatter = $cellFormatter;
        $this->table = $rendererTable;
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
     * @param int $rowIdx the row index of the body rows only
     *
     * TODO: if we keep Array/RendererCell pull up to share with Tsv/RendererCell
     */
    public function labelCells(int $rowIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;
        $rowStrides = UtilArray::getStrides($table->rowDims);
        $x = $rowIdx + $table->numHeaderRows;

        for ($dimIdx = 0; $dimIdx < $table->numLabelCols; $dimIdx++) {
            $stride = $rowStrides[$dimIdx];
            $product = $table->shape[$dimIdx] * $stride;
            $label = '';
            if ($table->repeatLabels === true || $rowIdx % $stride === 0) {
                $catIdx = floor($rowIdx % $product / $stride);
                $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
                $categId = $reader->getCategoryId($id, $catIdx);
                $label = $reader->getCategoryLabel($id, $categId);
            }
            $this->table->data[$x][$dimIdx] = $this->headerCell($label);
        }
    }

    /**
     * Add the header cell to the 2dim-array.
     * @param string $label
     * @return string
     */
    public function headerCell(string $label): string
    {
        return $this->formatter->formatHeaderCell($label);
    }

    /**
     * Creates the cells for the headers of the label columns.
     */
    public function headerLabelCells(int $rowIdx): void
    {
        for ($y = 0; $y < $this->table->numLabelCols; $y++) {
            $id = $this->reader->getDimensionId($this->table->numOneDim + $y);
            $label = $this->reader->getDimensionLabel($id);
            $this->table->data[$rowIdx][$y] = $this->headerCell($label);
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
            $this->table->data[$rowIdx][$y] = $this->headerCell($label);
        }
    }
}
