<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\FormatterCell;

/**
 * Renders json-stat data as a tab separated table.
 *
 * A table consists of a number of dimensions that are used to define the rows of the two-dimensional table
 * (referred to as row dimensions) and a number of dimensions that are used to define the columns of the table
 * (referred to as col dimensions). Each row dimension creates its own pre column, containing only category labels,
 * whereas the column dimensions contain the actual values.
 *
 * Setting the property numRowDim (number of row dimensions) defines how many of the dimensions are use for the rows,
 * beginning at the start of the ordered size array of the json-stat schema. Remaining dimensions are used for columns.
 * Dimensions of length one can be excluded from rendering with property excludeOneDim.
 *
 * Setting the property noLabelLastDim will skip the row in the table heading containing the labels of the last
 * dimension.
 *
 * Note: In the context of JSON-stat, the word value is used. In the context of html, data is used.
 * So we speak either of value cells and label cells, or of data cells and header cells.
 *
 * @see www.json-stat.org
 */
class TableArray extends AbstractTable
{

    /**
     * Holds the tab separated data.
     * @var array
     */
    public array $data = [];

    /**
     * Do not render dimension labels?
     * default = true
     * @var bool
     */
    public bool $noLabelDim = true;

    /** @var string|null caption of the table */
    public null|string $caption;

    /**
     * Repeat column labels ?
     * default = true
     * @var bool
     */
    public bool $repeatLabels = true;


    /**
     * Automatically sets the caption.
     * Sets the caption from the optional JSON-stat label property. HTML from the JSON-stat is escaped.
     * @return void
     */
    public function readCaption(): void
    {
        if (property_exists($this->reader->data, 'label')) {
            $this->caption = $this->reader->data->label;
        }
    }

    /**
     * Instantiate the RendererCell class.
     * @return void
     */
    public function initRendererCell(): void
    {
        $formatter = new FormatterCell($this->reader, new Formatter());
        $this->rendererCell = new CellArray($formatter, $this->reader, $this);
    }

    /**
     * Renders the data as an html table.
     * Reads the value array and renders it as a table.
     * @return string csv
     */
    public function render(): string
    {
        $this->build();
        $arr = [];
        foreach ($this->data as $row) {
            $arr[] = implode(',', $row);
        }
        return implode(',', $arr);
    }

    public function getData()
    {
        $this->build();

        return $this->data;
    }

    /**
     * Creates the table head and appends header cells, row by row to it.
     */
    public function addHeaders(): void
    {
        for ($rowIdx = 0; $rowIdx < $this->numHeaderRows; $rowIdx++) {
            if (!$this->noLabelDim || $rowIdx % 2 === 1) {
                $this->rendererCell->headerLabelCells($rowIdx);
                $this->rendererCell->addValueCellHeader($rowIdx, $rowIdx);
            }
        }
    }

    /**
     * Creates the table body and appends table cells row by row to it.
     */
    public function addRows(): void
    {
        $rowIdx = 0;
        $lastCol = $this->numValueCols - 1;
        for ($offset = 0, $len = $this->reader->getNumValues(); $offset < $len; $offset++) {
            if ($offset % $this->numValueCols === 0) {
                $this->rendererCell->labelCells($rowIdx);
            }
            $cellIdx = $offset % $this->numValueCols;
            $x = $rowIdx + $this->numHeaderRows;
            $y = $cellIdx + $this->numLabelCols;
            $this->data[$x][$y] = $this->rendererCell->addValueCellBody($offset, $offset);
            if ($offset % $this->numValueCols === $lastCol) {
                $rowIdx++;
            }
        }
    }

    /**
     * Creates and inserts a caption.
     */
    public function addCaption(): void
    {
        $this->data[0] = [$this->caption];
    }
}