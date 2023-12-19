<?php

namespace jsonstatPhpViz\Renderer;

use DOMElement;
use DOMException;
use DOMNode;
use jsonstatPhpViz\DOM\ClassList;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;
use function count;

/**
 * Handle rendering of html table cells.
 * @see CellInterface
 */
class CellHtml implements CellInterface
{
    protected Reader $reader;
    protected FormatterCell $formatter;
    protected AbstractTable $table;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     * @param TableHtml $rendererTable
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader, TableHtml $rendererTable)
    {
        $this->reader = $reader;
        $this->formatter = $cellFormatter;
        $this->table = $rendererTable;
    }

    /**
     * @throws DOMException
     */
    public function addFirstCellHeader($rowIdx): void
    {
        $this->table->dom->appendRow($this->table->head);
        if ($this->table->numRowDim > 0) {
            $this->addLabelCellHeader($rowIdx, 0);
        }
    }

    /**
     * @throws DOMException
     */
    public function addLabelCellHeader($rowIdx, $dimIdx): void
    {
        $label = null;
        $scope = null;
        if ($rowIdx === $this->table->numHeaderRows - 1) { // last header row
            $id = $this->reader->getDimensionId($this->table->numOneDim + $dimIdx);
            $label = $this->reader->getDimensionLabel($id);
            $scope = 'col';
        }
        $cell = $this->addCellHeader($label);
        $this->setAttrCellHeader($cell, $scope);
        $row = $this->getRowHeader($rowIdx);
        $row->appendChild($cell);
    }

    /**
     * Create, add and return a header cell element.
     * @param ?String $label cell content
     * @return DOMElement table cell element
     * @throws DOMException
     */
    private function addCellHeader(?string $label = null): DOMNode
    {
        $doc = $this->table->doc;
        $cell = $doc->createElement('th');
        $label = $this->formatter->formatHeaderCell($label);
        $cell->appendChild($doc->createTextNode($label));
        $this->table->head->appendChild($cell);

        return $cell;
    }

    /**
     * @param ?String $scope scope of cell
     * @param ?String $colspan number of columns to span
     * @param ?String $rowspan number of rows to span
     */
    public function setAttrCellHeader(DOMElement $cell, ?string $scope = null, ?string $colspan = null, ?string $rowspan = null): void
    {
        if ($scope !== null) {
            $cell->setAttribute('scope', $scope);
        }
        if ($colspan !== null) {
            $cell->setAttribute('colspan', $colspan);
        }
        if ($rowspan !== null) {
            $cell->setAttribute('rowspan', $rowspan);
        }
    }

    /**
     * Creates the cells for the headers of the value columns.
     * @param int $rowIdx
     * @param int $offset
     * @throws DOMException
     */
    public function addValueCellHeader(int $rowIdx, int $offset): void
    {
        $table = $this->table;
        $reader = $this->reader;

        // remember: we render two rows with headings per column dimension,
        //  e.g., one for the dimension label and one for the category label
        $dimIdx = $table->numRowDim + (int)floor($rowIdx / 2);
        $stride = $table->strides[$dimIdx];
        $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
        $product = $table->shape[$dimIdx] * $stride;
        if ($rowIdx % 2 === 0 && ($table->noLabelLastDim === false || $rowIdx !== $table->numHeaderRows - 2)) { // set attributes for dimension label cell
            $label = $reader->getDimensionLabel($id);
            $colspan = $product > 1 ? $product : null;
        } else {    // set attributes for category label cell
            $catIdx = floor(($offset % $product) / $stride);
            $catId = $reader->getCategoryId($id, $catIdx);
            $label = $reader->getCategoryLabel($id, $catId);
            $colspan = $stride > 1 ? $stride : null;
        }
        if ($colspan === null || $offset % $colspan === 0) {
            $scope = $colspan === null ? 'col' : 'colgroup';
            $cell = $this->addCellHeader($label);
            $this->setAttrCellHeader($cell, $scope, $colspan);
            $row = $this->getRowHeader($rowIdx);
            $row->appendChild($cell);
        }
    }

    /**
     * @throws DOMException
     */
    public function addLastCellHeader(int $rowIdx, int $offset): void
    {
        if (count($this->table->colDims) === 0) {
            $cell = $this->addCellHeader();
            $row = $this->getRowHeader($rowIdx);
            $row->appendChild($cell);
        } else {
            $this->addValueCellHeader($rowIdx, $offset);
        }
    }

    /**
     * @throws DOMException
     */
    public function addFirstCellBody(int $rowIdx): void
    {
        $row = $this->table->dom->appendRow($this->table->body);
        if ($this->table->numLabelCols > 0) {
            $this->addLabelCellBody($rowIdx, 0);
        }
    }

    /**
     * @throws DOMException
     */
    public function addLabelCellBody(int $rowIdx, int $dimIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;
        $rowStrides = UtilArray::getStrides($table->rowDims);
        $stride = $rowStrides[$dimIdx];
        $label = null;
        if ($rowIdx % $stride === 0) {
            $product = $table->shape[$dimIdx] * $stride;
            $catIdx = floor($rowIdx % $product / $stride);
            $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
            $categId = $reader->getCategoryId($id, $catIdx);
            $label = $reader->getCategoryLabel($id, $categId);
        }
        if ($table->useRowSpans === false || $rowIdx % $stride === 0) {
            $rowspan = $table->useRowSpans && $stride > 1 ? $stride : null;
            $scope = $stride > 1 ? 'rowgroup' : 'row';
            $cell = $this->addCellHeader($label);  //
            $this->setAttrCellHeader($cell, $scope, null, $rowspan);
            $this->setCssLabelCell($cell, $dimIdx, $rowIdx, $stride);
            $row = $this->getRowBody($rowIdx);
            $row->appendChild($cell);
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
        $doc = $this->table->doc;
        $val = $this->reader->data->value[$offset];
        $val = $this->formatter->formatValueCell($val, $offset);
        $cell = $doc->createElement('td');
        $cell->appendChild($doc->createTextNode($val));
        $row = $this->getRowBody($rowIdx);
        $row->appendChild($cell);
    }

    /**
     * Add the last cell to the table body.
     * @param int $rowIdx
     * @param int $offset
     * @return void
     * @throws DOMException
     */
    public function addLastCellBody(int $rowIdx, int $offset): void
    {
        $this->addValueCellBody($rowIdx, $offset);
    }

    /**
     * @param int $rowIdx
     * @return DOMElement
     */
    private function getRowBody(int $rowIdx): DOMElement
    {
        return $this->table->body->getElementsByTagName('tr')->item($rowIdx);
    }

    /**
     * @param int $rowIdx
     * @return DOMElement
     */
    private function getRowHeader(int $rowIdx): DOMElement
    {
        return $this->table->head->getElementsByTagName('tr')->item($rowIdx);
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
}