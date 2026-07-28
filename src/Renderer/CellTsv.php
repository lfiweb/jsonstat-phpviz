<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;

use function count;

/**
 * Handle rendering of tab-separated items (cells).
 * @see CellInterface
 */
class CellTsv extends AbstractCell
{
    protected TableTsv $table;

    /** @var string internal reference to the tab-separated string */
    protected string $tsv;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     * @param TableTsv $rendererTable
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader, TableTsv $rendererTable)
    {
        parent::__construct($cellFormatter, $reader);
        $this->table = $rendererTable;
        $this->tsv = &$this->table->getTsv();
    }

    /**
     * Add the first category label to the header line.
     * @param int $rowIdx row index
     * @return void
     */
    public function addFirstCellHeader(int $rowIdx): void
    {
        if ($this->table->numRowDim > 0) {
            $this->addLabelCellHeader(0, $rowIdx);
        }
    }

    /**
     * Add a category label to the table header.
     * @param int $dimIdx dimension index
     * @param int $rowIdx row index
     * @return void
     */
    public function addLabelCellHeader(int $dimIdx, int $rowIdx): void
    {
        $label = '';
        $table = $this->table;
        if ($table->repeatLabels || $table->isLastRowHeader($rowIdx)) {
            $id = $this->reader->getDimensionId($table->numOneDim + $dimIdx);
            $label = $this->reader->getDimensionLabel($id);
        }
        $this->tsv .= $this->formatter->formatHeaderCell($label).$table->separatorCol;
    }

    /**
     * Add the category label of the first dimension to the table body.
     * @param int $offset
     * @param int $rowIdx
     * @return void
     */
    public function addFirstCellBody(int $offset, int $rowIdx): void
    {
        for ($colIdx = 0; $colIdx < $this->table->numLabelCols; $colIdx++) {
            $this->addLabelCellBody($offset, $colIdx, $rowIdx);
        }
        $this->addValueCellBody($offset, $rowIdx);
    }

    /**
     * Append a category label to the table body.
     * @param int $offset index of the JSON-stat value array
     * @param int $dimIdx
     * @param int $rowIdx
     * @return void
     */
    public function addLabelCellBody(int $offset, int $dimIdx, int $rowIdx): void
    {
        $table = $this->table;
        $isFirstRenderedRow = $table->isFirstRenderedRow($offset, $dimIdx);
        $label = '';
        if ($table->repeatLabels || $isFirstRenderedRow) {
            $label = $this->getCategoryLabel($offset, $dimIdx);
        }
        $this->tsv .= $this->formatter->formatHeaderCell($label).$table->separatorCol;
    }

    /**
     * Appends cells with values to the row.
     * Inserts an HTMLTableCellElement at the end of the row with a value taken from the values at the given offset.
     * @param int $offset value index
     * @param int $rowIdx row index
     * @return void the content of the cell
     */
    public function addValueCellBody(int $offset, int $rowIdx): void
    {
        $val = $this->reader->data->value[$offset];
        $this->tsv .= $this->formatter->formatValueCell($val, $offset).$this->table->separatorCol;
    }

    /**
     * Add the last cell to the header line.
     * @param int $offset value index
     * @param int $rowIdx row index
     * @return void
     */
    public function addLastCellHeader(int $offset, int $rowIdx): void
    {
        if (count($this->table->colDims) !== 0) {
            $this->addValueCellHeader($offset, $rowIdx);
        }
        $this->tsv .= $this->table->separatorRow;
    }

    /**
     * Add a value cell to the table header.
     * @param int $offset value index
     * @param int $rowIdx row index
     * @return void
     */
    public function addValueCellHeader(int $offset, int $rowIdx): void
    {
        // remember: we render two rows with headings per column dimension,
        //  e.g., one for the dimension label and one for the category label
        $table = $this->table;
        $dimIdx = $table->numRowDim + (int)floor($rowIdx / 2);
        $id = $this->reader->getDimensionId($table->numOneDim + $dimIdx);
        if ($table->isDimensionRowHeader($rowIdx)) {
            $label = $this->reader->getDimensionLabel($id);
        } else {
            $label = $this->getCategoryLabel($offset, $dimIdx);
        }
        $this->tsv .= $this->formatter->formatHeaderCell($label).$table->separatorCol;
    }

    /**
     * Add the last cell to a row of the table body.
     * @param int $offset value index
     * @param int $rowIdx row index
     * @return void
     */
    public function addLastCellBody(int $offset, int $rowIdx): void
    {
        $this->addValueCellBody($offset, $rowIdx);
        $this->tsv .= $this->table->separatorRow;
    }
}