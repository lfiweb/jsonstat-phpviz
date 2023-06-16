<?php

namespace jsonstatPhpViz\src;

use DOMElement;
use DOMException;
use DOMNode;
use jsonstatPhpViz\src\DOM\ClassList;
use jsonstatPhpViz\src\DOM\Table;
use function array_slice;
use function count;

/**
 * Renders json-stat data as a html table.
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
 * @see www.json-stat.org
 */
class RendererTable
{
    /** @var int dimension of type row */
    public const DIM_TYPE_ROW = 1;

    /** @var int dimensions of type col */
    public const DIM_TYPE_COL = 2;

    /** @var Reader */
    protected Reader $reader;

    /* @var array $colDims dimensions used for columns containing values */
    protected array $colDims;

    /* @var array $rowDims dimensions used for rows containing labels, that make up the rows */
    protected array $rowDims;

    /** @var int number of dimensions of size one */
    protected int $numOneDim;

    /** @var int number of columns with values */
    protected int $numValueCols;

    /** @var int number of columns with labels */
    protected int $numLabelCols;

    /** @var int|null number of dimensions to be used for rows */
    protected ?int $numRowDim;

    /** @var DOMNode|Table */
    protected Table|DOMNode $table;

    /** @var int|float number of row headers */
    protected int|float $numHeaderRows;

    /** @var bool render the row with labels of last dimension? default = true */
    public bool $noLabelLastDim = false;

    /**
     * Render the table with rowspans ?
     * default = true
     * Note: When this is set to false, empty rowheaders might be created, which are an accessibility problem.
     * @var bool $useRowSpans
     */
    public bool $useRowSpans = true;

    /**
     * Exclude dimensions of size one from rendering.
     * Only excludes continuous dimensions of size one, e.g. when each dimension with a lower index is also of size one.
     * @var bool
     */
    public ?bool $excludeOneDim = false;

    /** @var null|string|DOMNode caption of the table */
    public null|string|DOMNode $caption;

    /** @var array shape of the json-stat value array */
    private array $shape;

    /** @var array strides of the array */
    private array $strides;

    /**
     * Instantiates the class.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        $this->reader = $jsonStatReader;
        $this->table = new Table();
        $this->numRowDim = $numRowDim;
        if (property_exists($this->reader->data, 'label')) {
            // since html content is allowed in caption when set explicitly, we have to escape it when set via json-stat to prevent html content
            $this->caption = $this->escapeHtml($this->reader->data->label);
        }
    }

    /**
     * Set the number of dimensions to be used for rows.
     * @param int $numRowDim
     */
    public function setNumRowDim(int $numRowDim): void
    {
        $this->numRowDim = $numRowDim;
    }

    /**
     * Precalculate and cache often used numbers before rendering.
     * @return void
     */
    protected function init(): void
    {
        $this->shape = $this->reader->getDimensionSizes($this->excludeOneDim);
        $this->strides = UtilArray::getStrides($this->shape);
        $this->numRowDim = $this->numRowDim ?? $this->numRowDimAuto();
        $this->rowDims = $this->extractDims($this->shape, self::DIM_TYPE_ROW);
        $this->colDims = $this->extractDims($this->shape, self::DIM_TYPE_COL);
        $this->initTable();
        // cache some often used numbers before rendering table
        $dimsAll = $this->reader->getDimensionSizes(false);
        $this->numOneDim = count($dimsAll) - count($this->rowDims) - count($this->colDims);
        $this->numValueCols = count($this->colDims) > 0 ? array_product($this->colDims) : 1;
        $this->numLabelCols = count($this->rowDims);
        // add an additional row to label each dimension
        $this->numHeaderRows = count($this->colDims) > 0 ? count($this->colDims) * 2 : 1;
    }

    /**
     * Set the attributes of the table element.
     * @return void
     */
    protected function initTable(): void
    {
        $numRowDims = count($this->rowDims);
        $shape = implode(',', $this->shape);
        $lastDimSize = $this->shape[count($this->shape) - 1];

        $domNode = $this->table->get();
        $css = new ClassList($domNode);
        $css->add('jst-viz', 'numRowDims'.$numRowDims, 'lastDimSize'.$lastDimSize);
        $domNode->setAttribute('data-shape', $shape);
        $domNode->setAttribute('data-num-row-dim', $numRowDims);
    }

    /**
     * Returns the dimensions that can be used for rows or cols.
     * Constant dimensions (e.g. of length 1) are excluded.
     * @param array $dims
     * @param int $type 'row' or 'col' possible values are RendererTable::DIM_TYPE_ROW or RendererTable::DIM_TYPE_COL
     * @return array
     */
    protected function extractDims(array $dims, int $type = RendererTable::DIM_TYPE_ROW): array
    {
        if ($type === self::DIM_TYPE_ROW) {
            return array_slice($dims, 0, $this->numRowDim);
        }

        return array_slice($dims, $this->numRowDim);
    }

    /**
     * Renders the data as a html table.
     * Reads the value array and renders it as a table.
     * @param bool $asHtml return html or DOMElement?
     * @return DOMElement|string table
     * @throws DOMException
     */
    public function render(bool $asHtml = true): string|DOMElement
    {
        $this->init();
        $this->caption();
        $this->headers();
        $this->rows();

        return $asHtml ? $this->table->toHtml() : $this->table->get();
    }

    /**
     * Creates the table head and appends header cells, row by row to it.
     * @throws DOMException
     */
    protected function headers(): void
    {
        $tHead = $this->table->createTHead();
        for ($rowIdx = 0; $rowIdx < $this->numHeaderRows; $rowIdx++) {
            if ($this->noLabelLastDim === false || $rowIdx !== $this->numHeaderRows - 2) {
                $row = $this->table->appendRow($tHead);
                $this->headerLabelCells($row, $rowIdx);
                $this->headerValueCells($row, $rowIdx);
            }
        }
    }

    /**
     * Creates the table body and appends table cells row by row to it.
     * @throws DOMException
     */
    protected function rows(): void
    {
        $rowIdx = 0;
        $tBody = $this->table->createTBody();
        for ($offset = 0, $len = $this->reader->getNumValues(); $offset < $len; $offset++) {
            if ($offset % $this->numValueCols === 0) {
                $row = $this->table->appendRow($tBody);
                $this->labelCells($row, $rowIdx);
                $rowIdx++;
            }
            $this->valueCell($row, $offset);
        }
    }

    /**
     * Creates the cells for the headers of the label columns.
     * @param DOMElement $row
     * @param int $rowIdx
     * @throws DOMException
     */
    protected function headerLabelCells(DOMElement $row, int $rowIdx): void
    {
        for ($k = 0; $k < $this->numLabelCols; $k++) {
            $label = null;
            $scope = null;

            if ($rowIdx === $this->numHeaderRows - 1) { // last header row
                $id = $this->reader->getDimensionId($this->numOneDim + $k);
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
    protected function headerValueCells(DOMElement $row, int $rowIdx): void
    {
        if (count($this->colDims) === 0) {
            $this->headerCell($row);

            return;
        }

        // remember: we render two rows with headings per column dimension, e.g.
        //      one for the dimension label and one for the category label
        $dimIdx = $this->numRowDim + (int)floor($rowIdx / 2);
        $stride = $this->strides[$dimIdx];
        $product = $this->shape[$dimIdx] * $stride;
        for ($i = 0; $i < $this->numValueCols; $i++) {
            $z = $rowIdx % 2;
            $id = $this->reader->getDimensionId($this->numOneDim + $dimIdx);
            if ($z === 0) { // set attributes for dimension label cell
                $label = $this->reader->getDimensionLabel($id);
                $colspan = $product > 1 ? $product : null;
            } else {    // set attributes for category label cell
                $catIdx = floor(($i % $product) / $stride);
                $catId = $this->reader->getCategoryId($id, $catIdx);
                $label = $this->reader->getCategoryLabel($id, $catId);
                $colspan = $stride > 1 ? $stride : null;
            }
            if ($colspan) {
                $scope = 'colgroup';
                $i += $colspan - 1; // skip colspan - 1 cells
            }
            else {
                $scope = 'col';
            }
            $cell = $this->headerCell($row, $label, $scope, $colspan);
            $row->appendChild($cell);
        }
    }

    /**
     * Appends cells with labels to the row.
     * Inserts the label as a HTMLTableHeaderElement at the end of the row.
     * @param DOMElement $row HTMLTableRow
     * @param int $rowIdxBody row index
     * @throws DOMException
     */
    protected function labelCells(DOMElement $row, int $rowIdxBody): void
    {
        $rowStrides = UtilArray::getStrides($this->rowDims);
        for ($i = 0; $i < $this->numLabelCols; $i++) {
            $dimIdx = $i;
            $stride = $rowStrides[$dimIdx];
            $product = $this->shape[$dimIdx] * $stride;
            $label = null;
            $scope = $stride > 1 ? 'rowgroup' : 'row';
            $rowspan = $this->useRowSpans && $stride > 1 ? $stride : null;
            if ($rowIdxBody % $stride === 0) {
                $catIdx = floor($rowIdxBody % $product / $stride);
                $id = $this->reader->getDimensionId($this->numOneDim + $dimIdx);
                $labelId = $this->reader->getCategoryId($id, $catIdx);
                $label = $this->reader->getCategoryLabel($id, $labelId);
            }
            if ($this->useRowSpans === false || $rowIdxBody % $stride === 0) {
                $cell = $this->headerCell($row, $label, $scope, null, $rowspan);
                $this->labelCellCss($cell, $i, $rowIdxBody, $stride);
                $row->appendChild($cell);
            }
        }
    }

    /**
     * Sets the css class of the body row
     * @param DOMElement $cell
     * @param int $cellIdx
     * @param int $rowIdxBody
     * @param int $rowStride
     */
    protected function labelCellCss(DOMElement $cell, int $cellIdx, int $rowIdxBody, int $rowStride): void
    {
        $cl = new ClassList($cell);
        $product = $this->shape[$cellIdx] * $rowStride;
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
     * Appends cells with values to the row.
     * Inserts a HTMLTableCellElement at the end of the row with a value taken from the values at given offset.
     * @param DOMElement $row
     * @param int $offset
     * @return DOMNode the created table cell
     * @throws DOMException
     */
    protected function valueCell(DOMElement $row, int $offset): DOMNode
    {
        $stat = $this->reader;
        $cell = $this->table->doc->createElement('td');
        $cell->textContent = $stat->data->value[$offset]; // no need to escape

        return $row->appendChild($cell);
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
    protected function headerCell(
        DOMElement $row,
        ?string $str = null,
        ?string $scope = null,
        ?string $colspan = null,
        ?string $rowspan = null
    ): DOMElement {
        $cell = $this->table->doc->createElement('th');
        if ($scope !== null) {
            $cell->setAttribute('scope', $scope);
        }
        if ($colspan !== null) {
            $cell->setAttribute('colspan', $colspan);
        }
        if ($rowspan !== null) {
            $cell->setAttribute('rowspan', $rowspan);
        }
        if ($str === null) {
            // create empty text node, otherwise <th/> is created, which is invalid on a non-void element
            $str = '';
        }
        $cell->appendChild($this->table->doc->createTextNode($str));

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $row->appendChild($cell);
    }

    /**
     * Creates and inserts a caption.
     * @return DOMNode|string|null
     * @throws DOMException
     */
    protected function caption(): DOMNode|string|null
    {
        if ($this->caption) {
            $caption = $this->table->insertCaption();
            $fragment = $this->table->doc->createDocumentFragment();
            $fragment->appendXML($this->caption);
            $caption->appendChild($fragment);
            $this->caption = $caption;
        }

        return $this->caption;
    }

    /**
     * Returns the default number of dimensions used for rows.
     * Uses at least two dimensions for the columns when there are more than 2 dimensions.
     * @return int
     */
    public function numRowDimAuto(): int
    {
        $dims = $this->reader->getDimensionSizes($this->excludeOneDim);

        return count($dims) === 2 ? 1 : count(array_slice($dims, 0, count($dims) - 2));
    }

    /**
     * Escape a string, so it can be safely inserted into html.
     * @param String $text
     * @return String
     */
    public function escapeHtml(string $text): string
    {

        return htmlspecialchars($text, ENT_HTML5, 'UTF-8');
    }
}
