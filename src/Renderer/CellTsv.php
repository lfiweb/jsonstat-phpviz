<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;

class CellTsv implements CellInterface
{
    protected Reader $reader;
    protected FormatterCell $formatter;
    protected AbstractTable $table;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     * @param TableTsv $rendererTable
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader, TableTsv $rendererTable)
    {
        $this->reader = $reader;
        $this->formatter = $cellFormatter;
        $this->table = $rendererTable;
    }

    /**
     * Creates the cells for the headers of the label columns.
     */
    public function headerLabelCells(int $rowIdx): void
    {
        for ($k = 0; $k < $this->table->numLabelCols; $k++) {
            $label = '';
            if ($this->table->repeatLabels === true || $rowIdx === $this->table->numHeaderRows - 1) { // last header row
                $id = $this->reader->getDimensionId($this->table->numOneDim + $k);
                $label = $this->reader->getDimensionLabel($id);
            }
            $this->table->tsv .= $this->headerCell($label);
        }
    }

    /**
     * Append the header cell to the internal tab separated string.
     * @param string $label
     * @return string
     */
    public function headerCell(string $label): string
    {
        return $this->formatter->formatHeaderCell($label).$this->table->separatorCol;
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
            $this->table->tsv .= $this->headerCell($label);
        }
        $this->table->tsv = rtrim($this->table->tsv, $this->table->separatorCol);
    }

    /**
     * Appends cells with labels to the row.
     * Inserts the label as a HTMLTableHeaderElement at the end of the row.
     * @param int $rowIdx the row index of the body rows only
     */
    public function xxxlabelCells(int $rowIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;
        $rowStrides = UtilArray::getStrides($table->rowDims);

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
            $this->table->tsv .= $this->headerCell($label);
        }
    }

    public function addFirstCellBody(int $dimIdx, int $rowIdx): void
    {
        $this->addLabelCellBody($dimIdx, $rowIdx);
    }

    public function addLabelCellBody(int $dimIdx, int $rowIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;
        $rowStrides = UtilArray::getStrides($table->rowDims);
        $stride = $rowStrides[$dimIdx];
        $product = $table->shape[$dimIdx] * $stride;
        $label = '';
        if ($table->repeatLabels === true || $rowIdx % $stride === 0) {
            $catIdx = floor($rowIdx % $product / $stride);
            $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
            $categId = $reader->getCategoryId($id, $catIdx);
            $label = $reader->getCategoryLabel($id, $categId);
        }
        $this->table->tsv .= $this->headerCell($label);
    }

    /**
     * Appends cells with values to the row.
     * Inserts a HTMLTableCellElement at the end of the row with a value taken from the values at given offset.
     * @param int $rowIdx
     * @return void the content of the cell
     */
    public function addValueCellBody(int $rowIdx): void
    {
        $val = $this->reader->data->value[$rowIdx];

        $this->table->tsv .= $this->formatter->formatValueCell($val, $rowIdx).$this->table->separatorCol;
    }

    public function addLastCellBody(int $offset, int $rowIdx): void
    {
        $val = rtrim($this->table->tsv, $this->table->separatorCol);
        $this->table->tsv .= $val.$this->table->separatorRow;
    }
}