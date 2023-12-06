<?php

namespace jsonstatPhpViz\Tsv;

use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;
use function array_slice;
use function count;

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

    /**
     * Holds the tab separated data.
     * @var string
     */
    public string $tsv;

    /** @var int|float number of row headers */
    public int|float $numHeaderRows;

    /**
     * Do not render dimension labels?
     * default = true
     * @var bool
     */
    public bool $noLabelDim = true;

    /**
     * Exclude dimensions of size one from rendering.
     * Only excludes continuous dimensions of size one, e.g. when each dimension with a lower index is also of size one.
     * @var bool
     */
    public ?bool $excludeOneDim = false;

    /** @var string|null caption of the table */
    public null|string $caption;

    /** @var array shape of the json-stat value array */
    public array $shape;

    /** @var array strides of the array */
    public array $strides;

    protected RendererCell $rendererCell;

    public string $separatorRow = "\n";

    public string $separatorCol = "\t";

    /**
     * Instantiates the class.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        $this->reader = $jsonStatReader;
        $this->tsv = '';
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

        // cache some often used numbers before rendering table
        $dimsAll = $this->reader->getDimensionSizes(false);
        $this->numOneDim = count($dimsAll) - count($this->rowDims) - count($this->colDims);
        $this->numValueCols = count($this->colDims) > 0 ? array_product($this->colDims) : 1;
        $this->numLabelCols = count($this->rowDims);
        // add an additional row to label each dimension
        $this->numHeaderRows = count($this->colDims) > 0 ? count($this->colDims) * 2 : 1;
    }

    /**
     * Automatically sets the caption.
     * Sets the caption from the optional JSON-stat label property. HTML from the JSON-stat is escaped.
     * @return void
     */
    protected function initCaption(): void
    {
        if (property_exists($this->reader->data, 'label')) {
            $this->caption = $this->reader->data->label;
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
     * @return string csv
     */
    public function render(): string
    {
        $this->init();
        $this->caption();
        $this->headers();
        $this->rows();

        return $this->tsv;
    }

    /**
     * Creates the table head and appends header cells, row by row to it.
     */
    protected function headers(): void
    {
        for ($rowIdx = 0; $rowIdx < $this->numHeaderRows; $rowIdx++) {
            if (!$this->noLabelDim || $rowIdx % 2 === 1) {
                $this->rendererCell->headerLabelCells($rowIdx);
                $this->rendererCell->headerValueCells($rowIdx);
                $this->tsv .= $this->separatorRow;
            }
        }
    }

    /**
     * Creates the table body and appends table cells row by row to it.
     */
    protected function rows(): void
    {
        $rowIdx = 0;
        for ($offset = 0, $len = $this->reader->getNumValues(); $offset < $len; $offset++) {
            if ($offset % $this->numValueCols === 0) {
                $this->tsv = rtrim($this->tsv, $this->separatorCol).($rowIdx > 0 ? $this->separatorRow : '');
                $this->rendererCell->labelCells($rowIdx);
                $rowIdx++;
            }
            $this->tsv .= $this->rendererCell->valueCell($offset).$this->separatorCol;
        }
    }

    /**
     * Creates and inserts a caption.
     * @return string|null
     */
    protected function caption(): ?string
    {
        $this->tsv .= $this->caption.$this->separatorRow.$this->separatorRow;

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