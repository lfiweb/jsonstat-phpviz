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
    public function addFirstCellHeader($rowIdx): DOMNode
    {
        $row = $this->table->dom->appendRow($this->table->head);
        $this->addLabelCellHeader($rowIdx, 0);

        return $row;
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
        $row = $this->getCurrentRowHeader();
        $row->appendChild($cell);
    }

    /**
     * Create, add and return a header cell element.
     * @param ?String $label cell content
     * @return DOMElement table cell element
     * @throws DOMException
     */
    public function addCellHeader(?string $label = null): DOMNode
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
     * @param DOMElement $row
     * @throws DOMException
     */
    public function addValueCellHeader(int $rowIdx, int $offset): void
    {
        $table = $this->table;
        $reader = $this->reader;
        $row = $this->getCurrentRowHeader();

        if (count($table->colDims) === 0) {
            $cell = $this->addCellHeader();
            $row->appendChild($cell);

            return;
        }

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
        if ($colspan === null || $offset % $colspan === 0) { // skip colspan - 1 cells
            $scope = $colspan === null ? 'col' : 'colgroup';
            $cell = $this->addCellHeader($label);
            $this->setAttrCellHeader($cell, $scope, $colspan);
            $row->appendChild($cell);
        }
    }

    public function addFirstCellBody(int $rowIdx): mixed
    {
        $row = $this->table->dom->appendRow($this->table->body);
        if ($this->table->numLabelCols > 0) {
            $this->addLabelCellBody($rowIdx, 0);
        }

        return $row;
    }

    /**
     * @throws DOMException
     */
    function addLabelCellBody(int $rowIdx, int $dimIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;
        $rowStrides = UtilArray::getStrides($table->rowDims);
        $stride = $rowStrides[$dimIdx];
        $product = $table->shape[$dimIdx] * $stride;
        $label = null;
        $scope = $stride > 1 ? 'rowgroup' : 'row';
        $rowspan = $table->useRowSpans && $stride > 1 ? $stride : null;
        if ($rowIdx % $stride === 0) {
            $catIdx = floor($rowIdx % $product / $stride);
            $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
            $categId = $reader->getCategoryId($id, $catIdx);
            $label = $reader->getCategoryLabel($id, $categId);
        }
        if ($table->useRowSpans === false || $rowIdx % $stride === 0) {
            $cell = $this->addCellHeader($label);  //
            $this->setAttrCellHeader($cell, $scope, null, $rowspan);
            $this->setCssLabelCell($cell, $dimIdx, $rowIdx, $stride);
            $row = $this->getCurrentRowBody();
            $row->appendChild($cell);
        }
    }

    /**
     * Append a value cell to the row.
     * Inserts a HTMLTableCellElement at the end of the row
     * with a value taken from the JSON-stat values attribute at the given offset.
     * @param int $offset
     * @return void the created table cell
     * @throws DOMException
     */
    public function addValueCellBody(int $offset): void
    {
        $doc = $this->table->doc;
        $val = $this->reader->data->value[$offset];
        $val = $this->formatter->formatValueCell($val, $offset);
        $cell = $doc->createElement('td');
        $cell->appendChild($doc->createTextNode($val));
        $row = $this->getCurrentRowBody();
        $row->appendChild($cell);
    }

    function addLastCellBody(int $rowIdx, int $offset): void
    {
        $this->addValueCellBody($offset);
    }

    private function getCurrentRowBody(): DOMElement
    {
        $rows = $this->table->body->getElementsByTagName('tr');

        return $rows->item($rows->length - 1);
    }

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
}