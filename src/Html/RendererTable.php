<?php

namespace jsonstatPhpViz\Html;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use jsonstatPhpViz\DOM\ClassList;
use jsonstatPhpViz\DOM\Table;
use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
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
class RendererTable extends \jsonstatPhpViz\RendererTable implements \jsonstatPhpViz\IRendererTable
{

    /** @var DOMNode|Table */
    protected Table|DOMNode $table;

    /**
     * Render the table with rowspans ?
     * default = true
     * Note: When this is set to false, empty row headers might be created, which are an accessibility problem.
     * @var bool $useRowSpans
     */
    public bool $useRowSpans = true;

    /** @var null|string|DOMNode caption of the table */
    public null|string|DOMNode $caption;

    protected RendererCell $rendererCell;

    /**
     * Instantiates the class.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        parent::__construct($jsonStatReader, $numRowDim);
        $this->table = new Table();

    }

    /**
     * Precalculate and cache often used numbers before rendering.
     * @return void
     */
    protected function init(): void
    {
        parent::init();
        $this->initTable();
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
     * Reads the value array and renders it as a html table.
     * @throws DOMException
     */
    public function render(): string
    {
        $this->build();

        return $this->table->toHtml();
    }

    /**
     * Creates the internal structure of the table.
     * @return void
     * @throws DOMException
     */
    protected function build(): void
    {
        $this->init();
        $this->caption();
        $this->headers();
        $this->rows();
    }

    /**
     * Render the table and return the DOM
     * @return DOMElement
     * @throws DOMException
     */
    public function renderDom(): DOMElement
    {
        $this->build();

        return $this->table->get();
    }

    /**
     * Return the DOMDocument.
     * @return DOMDocument
     */
    public function getDoc(): DOMDocument
    {
        return $this->table->doc;
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
}