<?php

namespace jsonstatPhpViz\Tsv;

use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;

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
class RendererTable extends \jsonstatPhpViz\RendererTable
{

    /**
     * Holds the tab separated data.
     * @var string
     */
    public string $tsv;

    /**
     * Do not render dimension labels?
     * default = true
     * @var bool
     */
    public bool $noLabelDim = true;

    /** @var string|null caption of the table */
    public null|string $caption;

    protected RendererCell $rendererCell;

    public string $separatorRow = "\n";

    public string $separatorCol = "\t";

    /**
     * Repeat column labels
     * @var bool
     */
    public bool $repeatLabels = true;

    /**
     * Instantiates the class.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        parent::__construct($jsonStatReader, $numRowDim);
        $this->tsv = '';
    }

    /**
     * Automatically sets the caption.
     * Sets the caption from the optional JSON-stat label property. HTML from the JSON-stat is escaped.
     * @return void
     */
    public function initCaption(): void
    {
        if (property_exists($this->reader->data, 'label')) {
            $this->caption = $this->reader->data->label;
        }
    }

    /**
     * Instantiate the RendererCell class.
     * @return void
     */
    public function initRendererCell(): void
    {
        $formatter = new FormatterCell($this->reader, new Formatter());
        $this->rendererCell = new RendererCell($formatter, $this->reader, $this);
    }

    /**
     * Renders the data as a html table.
     * Reads the value array and renders it as a table.
     * @return string csv
     */
    public function render(): string
    {
        $this->build();

        return $this->tsv;
    }

    /**
     * Creates the table head and appends header cells, row by row to it.
     */
    public function headers(): void
    {
        for ($rowIdx = 0; $rowIdx < $this->numHeaderRows; $rowIdx++) {
            if (!$this->noLabelDim || $rowIdx % 2 === 1) {
                $this->rendererCell->headerLabelCells();
                $this->rendererCell->headerValueCells($rowIdx);
                $this->tsv .= $this->separatorRow;
            }
        }
    }

    /**
     * Creates the table body and appends table cells row by row to it.
     */
    public function rows(): void
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
     * @return void
     */
    public function caption(): void
    {
        $this->tsv .= $this->caption.$this->separatorRow.$this->separatorRow;
    }
}