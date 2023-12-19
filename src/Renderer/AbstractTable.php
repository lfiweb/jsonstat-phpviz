<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;
use function array_slice;
use function count;

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
     * Only excludes continuous dimensions of size one, e.g. when each dimension with a lower index is also of size one.
     * @var bool
     */
    public ?bool $excludeOneDim = false;

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

    /**
     * Instantiates the class.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        $this->reader = $jsonStatReader;
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
     * Creates the internal structure of the table.
     * @return void
     */
    public function build(): void
    {
        $this->init();
        $this->caption();
        $this->headers();
        $this->rows();
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
    protected function extractDims(array $dims, int $type = self::DIM_TYPE_ROW): array
    {
        if ($type === self::DIM_TYPE_ROW) {
            return array_slice($dims, 0, $this->numRowDim);
        }

        return array_slice($dims, $this->numRowDim);
    }

    public function headers(): void
    {
        $numHeaderRows = $this->noLabelLastDim === true ? $this->numHeaderRows - 1 : $this->numHeaderRows;

        for ($rowIdx = 0; $rowIdx < $numHeaderRows; $rowIdx++) {
            $this->rendererCell->addFirstCellHeader($rowIdx);
            for ($colIdx = 1; $colIdx < $this->numLabelCols; $colIdx++) {
                $this->rendererCell->addLabelCellHeader($rowIdx, $colIdx);
            }
            for ($colIdx = 0; $colIdx < $this->numValueCols - 1; $colIdx++) {
                $this->rendererCell->addValueCellHeader($rowIdx, $colIdx);
            }
            $this->rendererCell->addLastCellHeader($rowIdx, $colIdx);
        }
    }

    public function rows(): void
    {
        $rowIdx = 0;
        for ($offset = 0, $len = $this->reader->getNumValues(); $offset < $len; $offset++) {
            if ($offset % $this->numValueCols === 0) {
                $this->rendererCell->addFirstCellBody($rowIdx);
                for ($colIdx = 1; $colIdx < $this->numLabelCols; $colIdx++) {
                    $this->rendererCell->addLabelCellBody($rowIdx, $colIdx);
                }
            }
            if ($offset % $this->numValueCols < $this->numValueCols - 1) {
                $this->rendererCell->addValueCellBody($rowIdx, $offset);
            } elseif ($offset % $this->numValueCols === $this->numValueCols - 1) {
                $this->rendererCell->addLastCellBody($rowIdx, $offset);
                $rowIdx++;
            }
        }
    }
}