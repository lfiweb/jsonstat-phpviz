<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;
use function array_slice;
use function count;

/**
 * Create a class template for rendering JSON-stat as a table.
 */
abstract class AbstractTable implements TableInterface
{
    /** @var int dimension of type row */
    public const DIM_TYPE_ROW = 1;

    /** @var int dimensions of type col */
    public const DIM_TYPE_COL = 2;

    /** @var int|float number of row headers */
    public int|float $numHeaderRows;

    /** @var Reader */
    public Reader $reader;

    /** @var int|null number of dimensions to be used for rows */
    public ?int $numRowDim;

    /* @var array $colDims dimensions used for columns containing values */
    public array $colDims;

    /** @var int number of columns with labels */
    public int $numLabelCols;

    /** @var int number of columns with values */
    public int $numValueCols;

    /* @var array $rowDims dimensions used for rows containing labels, that make up the rows */
    public array $rowDims;

    /**
     * Do not render the row with the labels of the last dimension?
     * default = false
     * @var bool
     */
    public bool $noLabelLastDim = false;

    /**
     * Exclude dimensions of size one from rendering.
     * Only excludes continuous dimensions of size one, when each dimension with a lower index is also of size one.
     * @var bool
     */
    public ?bool $excludeOneDim = false;

    /**
     * Render the table with rowspans ?
     * default = true
     * Note: When this is set to false, empty row headers might be created, which are an accessibility problem.
     * @var bool $useRowSpans
     */
    public bool $useRowSpans = true;

    /** @var array shape of the json-stat value array */
    public array $shape;

    /** @var array strides of the array */
    public array $strides;

    /**
     * Number of dimensions of size one.
     * @var int
     */
    public int $numOneDim;

    public CellInterface $rendererCell;

    public ?StylerInterface $styler = null;

    /**
     * the caption element
     * @var null|string
     */
    public null|string $caption = null;

    /**
     * Instantiates the class.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        $this->reader = $jsonStatReader;
        $this->numRowDim = $numRowDim;
        $this->readCaption();
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
     * Creates the internal structure of the table.
     * @return void
     */
    public function build(): void
    {
        $this->init();
        if ($this->caption) {
            $this->addCaption();
        }
        $this->addHeaders();
        $this->addRows();
        $this->styler?->style($this);
    }

    /**
     * Precalculate and cache often used numbers before rendering.
     * @return void
     */
    protected function init(): void
    {
        $this->shape = $this->reader->getDimensionSizes($this->excludeOneDim);
        $this->strides = UtilArray::getStrides($this->shape);
        $this->numRowDim = $this->numRowDim ?? $this->getNumRowDimAuto();
        $this->rowDims = $this->extractDims($this->shape);
        $this->colDims = $this->extractDims($this->shape, self::DIM_TYPE_COL);

        // cache some often used numbers before rendering table
        $dimsAll = $this->reader->getDimensionSizes(false);
        $this->numOneDim = count($dimsAll) - count($this->rowDims) - count($this->colDims);
        $this->numValueCols = count($this->colDims) > 0 ? array_product($this->colDims) : 1;
        $this->numLabelCols = count($this->rowDims);
        // add an additional row to label each dimension
        $this->numHeaderRows = count($this->colDims) > 0 ? count($this->colDims) * 2 : 1;
        $this->rendererCell = $this->newCellRenderer();
    }

    /**
     * Returns the default number of dimensions used for rendering rows.
     * By default, a table is rendered using all dimensions for rows expect the last two dimensions are used for columns.
     * When there are fewer than three dimensions, only the first dimension is used for rows.
     * @return int
     */
    public function getNumRowDimAuto(): int
    {
        $dims = $this->reader->getDimensionSizes($this->excludeOneDim);

        return count($dims) < 3 ? 1 : count(array_slice($dims, 0, count($dims) - 2));
    }

    /**
     * Returns the dimensions that can be used for rows or cols.
     * Constant dimensions (i.e., of length 1) are excluded.
     * @param array $dims
     * @param int $type 'row' or 'col' possible values are RendererTable::DIM_TYPE_ROW or RendererTable::DIM_TYPE_COL
     * @return array
     */
    protected function extractDims(array $dims, int $type = self::DIM_TYPE_ROW): array
    {
        if ($type === self::DIM_TYPE_ROW) {
            return array_slice($dims, 0, $this->numRowDim);
        }

        return array_slice($dims, $this->numRowDim);
    }

    public function addHeaders(): void
    {
        $numHeaderRows = $this->noLabelLastDim === true ? $this->numHeaderRows - 1 : $this->numHeaderRows;

        for ($rowIdx = 0; $rowIdx < $numHeaderRows; $rowIdx++) {
            $this->rendererCell->addFirstCellHeader($rowIdx);
            for ($colIdx = 1; $colIdx < $this->numLabelCols; $colIdx++) {
                $this->rendererCell->addLabelCellHeader($colIdx, $rowIdx);
            }
            // note: since we are reading from the linear JSON-stat value property,
            //      we have to align the columns and start with zero instead of
            //      zero + numLabelCols.
            for ($colIdx = 0; $colIdx < $this->numValueCols - 1; $colIdx++) {
                $this->rendererCell->addValueCellHeader($colIdx, $rowIdx);
            }
            $this->rendererCell->addLastCellHeader($colIdx, $rowIdx);
        }
    }

    /**
     * Add rows to the table body.
     * Note: The Row index starts at zero for body rows since we are using the offset of the value array to loop over.
     * The row index can be adjusted for header rows in the cell renderer if necessary.
     * @return void
     */
    public function addRows(): void
    {
        $rowIdx = 0;
        for ($offset = 0, $len = $this->reader->getNumValues(); $offset < $len; $offset++) {
            if ($offset % $this->numValueCols === 0) {
                $this->rendererCell->addFirstCellBody($rowIdx);
                for ($colIdx = 1; $colIdx < $this->numLabelCols; $colIdx++) {
                    $this->rendererCell->addLabelCellBody($colIdx, $rowIdx);
                }
            }
            if ($offset % $this->numValueCols < $this->numValueCols - 1) {
                $this->rendererCell->addValueCellBody($offset, $rowIdx);
            } elseif ($offset % $this->numValueCols === $this->numValueCols - 1) {
                $this->rendererCell->addLastCellBody($offset, $rowIdx);
                $rowIdx++;
            }
        }
    }

    /**
     * Is this the last row of the table header rows?
     * Takes the state of the property CellHtml::noLabelLastDim into account.
     * @param int $rowIdx row index
     * @return bool
     */
    public function isLastRowHeader(int $rowIdx): bool
    {
        $lastRow = $this->numHeaderRows - 1;
        if ($this->noLabelLastDim === true) {
            --$lastRow;
        }

        return $rowIdx === $lastRow;
    }

    /**
     * Is this a dimension label or a category label row?
     * Note: Per column dimension, a row for the dimension label
     *      and a row for the dimension's category label is rendered.
     * @param int $rowIdx row index
     * @return bool
     */
    public function isDimensionRowHeader(int $rowIdx): bool
    {
        return $rowIdx % 2 === 0 && (
                $this->noLabelLastDim === false || $rowIdx !== $this->numHeaderRows - 2
            );
    }

    /**
     * Return a new instance of the cell renderer.
     * @return CellInterface
     */
    abstract protected function newCellRenderer(): CellInterface;
}