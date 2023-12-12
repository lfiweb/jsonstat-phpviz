<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;

/**
 * Renders json-stat data as a tab separated table.
 *
 * @see TableHtml class for more info
 */
class TableTsv extends AbstractTable
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

    protected CellTsv $rendererCell;

    public string $separatorRow = "\n";

    public string $separatorCol = "\t";

    /**
     * Repeat column labels ?
     * default = true
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
        $this->rendererCell = new CellTsv($formatter, $this->reader, $this);
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
                $this->rendererCell->headerLabelCells($rowIdx);
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
