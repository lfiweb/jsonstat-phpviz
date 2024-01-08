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
    private string $tsv;

    /** @var string|null caption of the table */
    public null|string $caption;

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
    public function readCaption(): void
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
     * Creates and inserts a caption.
     * @return void
     */
    public function addCaption(): void
    {
        $this->tsv .= $this->caption.$this->separatorRow;
    }

    /**
     * Return the internal, tab separated string.
     * Returns the tab separated string as a reference, not as a copy.
     * @return string
     */
    public function &getTsv(): string
    {
        return $this->tsv;
    }
}