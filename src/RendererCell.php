<?php

namespace jsonstatPhpViz;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use jsonstatPhpViz\DOM\ClassList;
use function count;

class RendererCell
{
    protected RendererTable $table;
    protected Reader $reader;
    protected DOMDocument $doc;
    protected Formatter $formatter;

    /**
     * @param RendererTable $rendererTable
     * @param Formatter $cellFormatter
     */
    public function __construct(RendererTable $rendererTable, Formatter $cellFormatter)
    {
        $this->table = $rendererTable;
        $this->reader = $this->table->reader;
        $this->doc = $this->table->table->doc;
        $this->formatter = $cellFormatter;
    }

    /**
     * Sets the css class of the body row
     * @param DOMElement $cell
     * @param int $cellIdx
     * @param int $rowIdxBody
     * @param int $rowStride
     */
    public function labelCellCss(DOMElement $cell, int $cellIdx, int $rowIdxBody, int $rowStride): void
    {
        $cl = new ClassList($cell);
        $product = $this->table->shape[$cellIdx] * $rowStride;
        $css = 'rowdim' . ($cellIdx + 1);
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
     * Appends cells with values to the row.
     * Inserts a HTMLTableCellElement at the end of the row with a value taken from the values at given offset.
     * @param DOMElement $row
     * @param int $offset
     * @return DOMElement the created table cell
     * @throws DOMException
     */
    public function valueCell(DOMElement $row, int $offset): DOMElement
    {
        $doc = $this->doc;
        $cell = $doc->createElement('td');
        $val = $this->reader->data->value[$offset];
        $val = $this->formatValueCell($val, $offset);
        $cell->appendChild($doc->createTextNode($val));
        $row->appendChild($cell);

        return $cell;
    }

    /**
     * Appends cells with labels to the row.
     * Inserts the label as a HTMLTableHeaderElement at the end of the row.
     * @param DOMElement $row HTMLTableRow
     * @param int $rowIdxBody row index
     * @throws DOMException
     */
    public function labelCells(DOMElement $row, int $rowIdxBody): void
    {
        $table = $this->table;
        $rowStrides = UtilArray::getStrides($table->rowDims);

        for ($i = 0; $i < $table->numLabelCols; $i++) {
            $dimIdx = $i;
            $stride = $rowStrides[$dimIdx];
            $product = $table->shape[$dimIdx] * $stride;
            $label = null;
            $scope = $stride > 1 ? 'rowgroup' : 'row';
            $rowspan = $table->useRowSpans && $stride > 1 ? $stride : null;
            if ($rowIdxBody % $stride === 0) {
                $reader = $this->reader;
                $catIdx = floor($rowIdxBody % $product / $stride);
                $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
                $categId = $reader->getCategoryId($id, $catIdx);
                $label = $reader->getCategoryLabel($id, $categId);
            }
            if ($table->useRowSpans === false || $rowIdxBody % $stride === 0) {
                $cell = $this->headerCell($row, $label, $scope, null, $rowspan);
                $this->labelCellCss($cell, $i, $rowIdxBody, $stride);
            }
        }
    }

    /**
     * Create and returns a header cell element.
     * @param DOMElement $row
     * @param ?String $str cell content
     * @param ?String $scope scope of cell
     * @param ?String $colspan number of columns to span
     * @param ?String $rowspan number of rows to span
     * @return DOMElement
     * @throws DOMException
     */
    public function headerCell(
        DOMElement $row,
        ?string    $str = null,
        ?string    $scope = null,
        ?string    $colspan = null,
        ?string    $rowspan = null
    ): DOMNode
    {
        $cell = $this->doc->createElement('th');
        if ($scope !== null) {
            $cell->setAttribute('scope', $scope);
        }
        if ($colspan !== null) {
            $cell->setAttribute('colspan', $colspan);
        }
        if ($rowspan !== null) {
            $cell->setAttribute('rowspan', $rowspan);
        }
        $str = $this->formatHeaderCell($str);
        $cell->appendChild($this->doc->createTextNode($str));

        return $row->appendChild($cell);
    }

    /**
     * Creates the cells for the headers of the label columns.
     * @param DOMElement $row
     * @param int $rowIdx
     * @throws DOMException
     */
    public function headerLabelCells(DOMElement $row, int $rowIdx): void
    {
        for ($k = 0; $k < $this->table->numLabelCols; $k++) {
            $label = null;
            $scope = null;

            if ($rowIdx === $this->table->numHeaderRows - 1) { // last header row
                $id = $this->reader->getDimensionId($this->table->numOneDim + $k);
                $label = $this->reader->getDimensionLabel($id);
                $scope = 'col';
            }
            $this->headerCell($row, $label, $scope);
        }
    }

    /**
     * Creates the cells for the headers of the value columns.
     * @param DOMElement $row
     * @param int $rowIdx
     * @throws DOMException
     */
    public function headerValueCells(DOMElement $row, int $rowIdx): void
    {
        $table = $this->table;
        $reader = $this->reader;

        if (count($table->colDims) === 0) {
            $this->headerCell($row);

            return;
        }

        // remember: we render two rows with headings per column dimension, e.g.
        //      one for the dimension label and one for the category label
        $dimIdx = $table->numRowDim + (int)floor($rowIdx / 2);
        $stride = $table->strides[$dimIdx];
        $product = $table->shape[$dimIdx] * $stride;
        for ($i = 0; $i < $table->numValueCols; $i++) {
            $z = $rowIdx % 2;
            $id = $reader->getDimensionId($table->numOneDim + $dimIdx);
            if ($z === 0) { // set attributes for dimension label cell
                $label = $reader->getDimensionLabel($id);
                $colspan = $product > 1 ? $product : null;
            } else {    // set attributes for category label cell
                $catIdx = floor(($i % $product) / $stride);
                $catId = $reader->getCategoryId($id, $catIdx);
                $label = $reader->getCategoryLabel($id, $catId);
                $colspan = $stride > 1 ? $stride : null;
            }
            if ($colspan) {
                $scope = 'colgroup';
                $i += $colspan - 1; // skip colspan - 1 cells
            } else {
                $scope = 'col';
            }
            $this->headerCell($row, $label, $scope, $colspan);
        }
    }

    /**
     * Format a head cell <th>
     * Format cells used as a header for group of columns or rows (headings).
     * @param string|null $str
     * @return string
     */
    public function formatHeaderCell(null|string $str): string
    {
        return $this->formatter->formatNull($str);
    }

    /**
     * Format a data cell <td>.
     * Format a cell used for the JSON-stat value property.
     * Note: If value is an int or float, the number of decimals from the unit of the category is used if available.
     * @param string|int|float|null $val
     * @param int $offset
     * @return string
     */
    public function formatValueCell(null|string|int|float $val, int $offset): string
    {
        $stat = $this->reader;
        $idxLastDim = count($stat->data->id) - 1;
        $dimId = $stat->getDimensionId($idxLastDim);
        if ($stat->hasDecimal($dimId)) {
            $categoryId = $stat->getCategoryId($dimId, $offset % $stat->data->size[$idxLastDim]);
            $decimals = $stat->getDecimal($dimId, $categoryId);
            $val = $this->formatter->formatDecimal($val, $decimals);
        }

        return $this->formatter->formatNull($val);
    }
}