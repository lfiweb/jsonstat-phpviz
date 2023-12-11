<?php

namespace jsonstatPhpViz\Tsv;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;

class RendererCell extends \jsonstatPhpViz\RendererCell
{
    protected RendererTable $table;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     * @param RendererTable $rendererTable
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader, RendererTable $rendererTable)
    {
        parent::__construct($cellFormatter, $reader);
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
     */
    public function labelCells(int $rowIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;
        $rowStrides = UtilArray::getStrides($table->rowDims);

        for ($i = 0; $i < $table->numLabelCols; $i++) {
            $stride = $rowStrides[$i];
            $product = $table->shape[$i] * $stride;
            $catIdx = floor($rowIdx % $product / $stride);
            $id = $reader->getDimensionId($table->numOneDim + $i);
            $categId = $reader->getCategoryId($id, $catIdx);
            $label = $reader->getCategoryLabel($id, $categId);
            $this->headerCell($label);
        }
    }

    /**
     * Append the header cell to the internal tab separated string.
     * @param string $label
     * @return string
     */
    public function headerCell(string $label): string
    {
        $val = $this->formatter->formatHeaderCell($label).$this->table->separatorCol;
        $this->table->tsv .= $val;

        return $val;
    }

    /**
     * Creates the cells for the headers of the label columns.
     */
    public function headerLabelCells(): void
    {
        for ($k = 0; $k < $this->table->numLabelCols; $k++) {
            $id = $this->reader->getDimensionId($this->table->numOneDim + $k);
            $label = $this->reader->getDimensionLabel($id);
            $this->headerCell($label);
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
            $this->headerCell($label);
        }
        $this->table->tsv = rtrim($this->table->tsv, $this->table->separatorCol);
    }

}