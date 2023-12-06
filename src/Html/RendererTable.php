<?php

namespace jsonstatPhpViz\Html;

use DOMElement;
use DOMException;
use DOMNode;
use jsonstatPhpViz\DOM\ClassList;
use jsonstatPhpViz\DOM\Table;
use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;
use jsonstatPhpViz\UtilHtml;
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
 * Note: In the context of JSON-stat, the word value is used. In the context of html, data is used.
 * So we speak either of value cells and label cells, or of data cells and header cells.
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
    public Reader $reader;

    /* @var array $colDims dimensions used for columns containing values */
    public array $colDims;

    /* @var array $rowDims dimensions used for rows containing labels, that make up the rows */
    public array $rowDims;

    /** @var int number of dimensions of size one */
    public int $numOneDim;

    /** @var int number of columns with values */
    public int $numValueCols;

    /** @var int number of columns with labels */
    public int $numLabelCols;

    /** @var int|null number of dimensions to be used for rows */
    public ?int $numRowDim;

    /** @var DOMNode|Table */
    public Table|DOMNode $table;

    /** @var int|float number of row headers */
    public int|float $numHeaderRows;

    /**
     * Do not render the row with the labels of the last dimension?
     * default = false
     * @var bool
     */
    public bool $noLabelLastDim = false;

    /**
     * Render the table with rowspans ?
     * default = true
     * Note: When this is set to false, empty row headers might be created, which are an accessibility problem.
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
    public array $shape;

    /** @var array strides of the array */
    public array $strides;

    protected RendererCell $rendererCell;

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
        $this->initCaption();
        $this->initRendererCell();
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
        $this->rowDims = $this->extractDims($this->shape);
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
        $css->add('jst-viz', 'numRowDims' . $numRowDims, 'lastDimSize' . $lastDimSize);
        $domNode->setAttribute('data-shape', $shape);
        $domNode->setAttribute('data-num-row-dim', $numRowDims);
    }

    /**
     * Automatically sets the caption.
     * Sets the caption from the optional JSON-stat label property. HTML from the JSON-stat is escaped.
     * @return void
     */
    protected function initCaption(): void
    {
        // since html content is allowed in caption when the property is set explicitly,
        // we have to escape it when set via json-stat to prevent html content from the untrusted source
        if (property_exists($this->reader->data, 'label')) {
            $this->caption = UtilHtml::escape($this->reader->data->label);
        }
    }

    /**
     * Instantiate the RendererCell class.
     * @return void
     */
    protected function initRendererCell(): void
    {
        $this->rendererCell = new RendererCell($this, new FormatterCell($this->reader, new Formatter()));
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
                $this->rendererCell->headerLabelCells($row, $rowIdx);
                $this->rendererCell->headerValueCells($row, $rowIdx);
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
                $this->rendererCell->labelCells($row, $rowIdx);
                $rowIdx++;
            }
            $this->rendererCell->valueCell($row, $offset);
        }
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
     * Returns the default number of dimensions used for rendering rows.
     * By default, a table is rendered using all dimensions for rows expect the last two dimensions are used for columns.
     * When there are fewer than 3 dimensions, only the first dimension is used for rows.
     * @return int
     */
    public function numRowDimAuto(): int
    {
        $dims = $this->reader->getDimensionSizes($this->excludeOneDim);

        return count($dims) < 3 ? 1 : count(array_slice($dims, 0, count($dims) - 2));
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
}