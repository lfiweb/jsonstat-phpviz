<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;

class CellTsv extends AbstractCell
{
    protected TableTsv $table;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     * @param TableTsv $rendererTable
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader, TableTsv $rendererTable)
    {
        parent::__construct($cellFormatter, $reader);
        $this->table = $rendererTable;
    }

    public function addFirstCellHeader($rowIdx): void
    {
        if ($this->table->numRowDim > 0) {
            $this->addLabelCellHeader(0, $rowIdx);
        }
    }

    public function addLabelCellHeader($dimIdx, $rowIdx): void
    {
        $label = '';
        $table = $this->table;
        if ($table->repeatLabels || $table->isLastRowHeader($rowIdx)) {
            $id = $this->reader->getDimensionId($table->numOneDim + $dimIdx);
            $label = $this->reader->getDimensionLabel($id);
        }
        $table->tsv .= $this->formatter->formatHeaderCell($label).$table->separatorCol;
    }

    public function addFirstCellBody(int $rowIdx): void
    {
        if ($this->table->numLabelCols > 0) {
            $this->addLabelCellBody(0, $rowIdx);
        }
    }

    public function addLabelCellBody(int $dimIdx, int $rowIdx): void
    {
        $table = $this->table;
        $rowStrides = UtilArray::getStrides($table->rowDims);
        $stride = $rowStrides[$dimIdx];
        $label = '';
        $product = $table->shape[$dimIdx] * $stride;
        if ($table->repeatLabels || $rowIdx % $stride === 0) {
            $label = $this->getCategoryLabel($rowIdx, $table->numOneDim + $dimIdx, $stride, $product);
        }
        $table->tsv .= $this->formatter->formatHeaderCell($label).$table->separatorCol;
    }

    /**
     * Add the last cell to the table header.
     * @param int $rowIdx
     * @return void
     */
    public function addLastCellHeader(int $offset, int $rowIdx): void
    {
        if (count($this->table->colDims) !== 0) {
            $this->addValueCellHeader($offset, $rowIdx);
        }
        $this->table->tsv .= $this->table->separatorRow;
    }

    /**
     * Add a value cell to the table header.
     * @param int $offset
     * @param int $rowIdx
     * @return void
     */
    public function addValueCellHeader(int $offset, int $rowIdx): void
    {
        // remember: we render two rows with headings per column dimension,
        //  e.g., one for the dimension label and one for the category label
        $table = $this->table;
        $dimIdx = $table->numRowDim + (int)floor($rowIdx / 2);
        $stride = $table->strides[$dimIdx];
        $product = $table->shape[$dimIdx] * $stride;
        $id = $this->reader->getDimensionId($table->numOneDim + $dimIdx);
        if ($table->isDimensionRowHeader($rowIdx)) {
            $label = $this->reader->getDimensionLabel($id);
        } else {
            $label = $this->getCategoryLabel($offset, $table->numOneDim + $dimIdx, $stride, $product);
        }
        $table->tsv .= $this->formatter->formatHeaderCell($label).$table->separatorCol;
    }

    public function addLastCellBody(int $offset, int $rowIdx): void
    {
        $this->addValueCellBody($offset, $rowIdx);
        $this->table->tsv .= $this->table->separatorRow;
    }

    /**
     * Appends cells with values to the row.
     * Inserts a HTMLTableCellElement at the end of the row with a value taken from the values at given offset.
     * @param int $rowIdx
     * @return void the content of the cell
     */
    public function addValueCellBody(int $offset, int $rowIdx): void
    {
        $val = $this->reader->data->value[$offset];
        $this->table->tsv .= $this->formatter->formatValueCell($val, $offset).$this->table->separatorCol;
    }
}