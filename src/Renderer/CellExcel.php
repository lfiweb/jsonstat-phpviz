<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\DOM\ClassList;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use function count;

class CellExcel implements CellInterface
{
    protected Reader $reader;
    protected FormatterCell $formatter;
    protected AbstractTable $table;
    private Worksheet $worksheet;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     * @param TableExcel $rendererTable
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader, TableExcel $rendererTable)
    {
        $this->reader = $reader;
        $this->formatter = $cellFormatter;
        $this->table = $rendererTable;
        $this->worksheet = $this->table->xls->spreadsheet->getActiveSheet();
    }

    public function addFirstCellHeader($rowIdx): void
    {
        if ($this->table->numRowDim > 0) {
            $this->addLabelCellHeader($rowIdx, 0);
        }
    }

    public function addLabelCellHeader($rowIdx, $dimIdx): void
    {
        $label = null;
        if ($rowIdx === $this->table->numHeaderRows - 1) { // last header row
            $id = $this->reader->getDimensionId($this->table->numOneDim + $dimIdx);
            $label = $this->reader->getDimensionLabel($id);
        }
        $this->addCellHeader($rowIdx + 1, $dimIdx + 1, $label);
    }

    /**
     * Create, add and return a header cell element.
     * @param $x
     * @param $y
     * @param ?String $label cell content
     * @return void table cell element
     */
    public function addCellHeader($x, $y, ?string $label = null): void
    {
        $label = $this->formatter->formatHeaderCell($label);
        $this->worksheet->setCellValue([$y, $x], $label);
    }

    /**
     * Creates the cells for the headers of the value columns.
     * @param int $rowIdx
     * @param int $offset
     */
    public function addValueCellHeader(int $rowIdx, int $offset): void
    {
        $table = $this->table;
        $reader = $this->reader;

        // remember: we render two rows with headings per column dimension,
        //  e.g., one for the dimension label and one for the category label
        $dimIdx = $table->numRowDim + (int)floor($rowIdx / 2);
        $stride = $table->strides[$dimIdx];
        $product = $table->shape[$dimIdx] * $stride;
        $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
        if ($rowIdx % 2 === 0) { // set attributes for dimension label cell
            $label = $reader->getDimensionLabel($id);
            $colspan = $product > 1 ? $product : null;
        } else {    // set attributes for category label cell
            $catIdx = floor(($offset % $product) / $stride);
            $catId = $reader->getCategoryId($id, $catIdx);
            $label = $reader->getCategoryLabel($id, $catId);
            $colspan = $stride > 1 ? $stride : null;
        }
        if ($colspan === null || $offset % $colspan === 0) {
            $x = $rowIdx + 1;
            $y = $this->table->numLabelCols + $offset + 1;
            $this->addCellHeader($x, $y, $label);
            $this->worksheet->mergeCells([$y, $x, $y + $colspan, $x]);
        }
    }

    public function addFirstCellBody(int $rowIdx): void
    {
        if ($this->table->numLabelCols > 0) {
            $this->addLabelCellBody($rowIdx, 0);
        }
    }

    public function addLabelCellBody(int $rowIdx, int $dimIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;
        $rowStrides = UtilArray::getStrides($table->rowDims);
        $stride = $rowStrides[$dimIdx];
        $product = $table->shape[$dimIdx] * $stride;
        $label = null;
        $rowspan = $table->useRowSpans && $stride > 1 ? $stride : null;
        if ($rowIdx % $stride === 0) {
            $catIdx = floor($rowIdx % $product / $stride);
            $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
            $categId = $reader->getCategoryId($id, $catIdx);
            $label = $reader->getCategoryLabel($id, $categId);
        }
        if ($table->useRowSpans === false || $rowIdx % $stride === 0) {
            $x = $rowIdx + 1;
            $y = $dimIdx + 1;
            $this->addCellHeader($x, $y, $label);
            $this->worksheet->mergeCells([$y, $x, $y, $x  + $rowspan]);
        }
    }

    /**
     * Append a value cell to the row.
     * Inserts a HTMLTableCellElement at the end of the row
     * with a value taken from the JSON-stat values attribute at the given offset.
     * @param int $rowIdx
     * @param int $offset
     * @return void the created table cell
     * @throws DOMException
     */
    public function addValueCellBody(int $rowIdx, int $offset): void
    {
        $cellIdx = $offset % $this->numValueCols;
        $x = $rowIdx + $this->numHeaderRows;
        $y = $cellIdx + $this->numLabelCols;
        $val = $this->reader->data->value[$offset];
        $val = $this->formatter->formatValueCell($val, $offset);
        $this->worksheet->setCellValue([$y, $x], $val);
    }

    /**
     * Add the last cell to the table body.
     * @param int $rowIdx
     * @param int $offset
     * @return void
     */
    public function addLastCellBody(int $rowIdx, int $offset): void
    {
        $this->addValueCellBody($offset);
    }

    /**
     * @return DOMElement
     */
    private function getCurrentRowBody(): DOMElement
    {
        $rows = $this->table->body->getElementsByTagName('tr');

        return $rows->item($rows->length - 1);
    }

    /**
     * @return DOMElement
     */
    private function getCurrentRowHeader(): DOMElement
    {
        $rows = $this->table->head->getElementsByTagName('tr');

        return $rows->item($rows->length - 1);
    }

    /**
     * Sets the css class of the body row
     * @param DOMElement $cell
     * @param int $cellIdx
     * @param int $rowIdxBody
     * @param int $rowStride
     */
    public function setCssLabelCell(DOMElement $cell, int $cellIdx, int $rowIdxBody, int $rowStride): void
    {
        $cl = new ClassList($cell);
        $product = $this->table->shape[$cellIdx] * $rowStride;
        $css = 'rowdim'.($cellIdx + 1);
        $modulo = $rowIdxBody % $product;
        if ($rowIdxBody % $rowStride === 0) {
            $cl->add($css);
        }
        if ($modulo === 0) {
            $cl->add($css, 'first');
        } elseif ($modulo === $product - $rowStride) {
            $cl->add($css, 'last');
        }
    }

    /**
     * Add the last cell to the table header.
     * @param int $rowIdx
     * @return void
     */
    public function addLastCellHeader(int $rowIdx, int $offset): void
    {
        // TODO: Implement addLastCellHeader() method.
    }
}