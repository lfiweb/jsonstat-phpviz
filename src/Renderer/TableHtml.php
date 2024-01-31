<?php

namespace jsonstatPhpViz\Renderer;

use DOMDocument;
use DOMElement;
use DOMException;
use jsonstatPhpViz\DOM\ClassList;
use jsonstatPhpViz\DOM\Table;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilHtml;
use function count;

/**
 * Renders json-stat data as an html table.
 *
 * A table consists of a number of dimensions. They are either used to define the rows of the two-dimensional table
 * (referred to as row dimensions) or to define the columns of the table (referred to as col dimensions).
 * Each row dimension creates its own pre-column, containing only category labels,
 * whereas the column dimensions contain the actual values.
 *
 * Setting the property numRowDim (number of row dimensions) defines how many of the dimensions are used for the rows,
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
class TableHtml extends AbstractTable
{

    /** @var Table */
    public Table $dom;

    public DOMDocument $doc;

    /**
     * the table element
     * @var DOMElement
     */
    public DOMElement $domNode;

    /**
     * the tHead element
     * @var DOMElement
     */
    public DOMElement $head;

    /**
     * the tBody element
     * @var DOMElement
     */
    public DOMElement $body;

    /**
     * Instantiates the class.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        parent::__construct($jsonStatReader, $numRowDim);
        $this->dom = new Table();
        $this->doc = $this->dom->doc;
        $this->domNode = $this->dom->domNode;
    }

    /**
     * Automatically sets the caption.
     * Sets the caption from the optional JSON-stat label property. HTML from the JSON-stat is escaped.
     * @return void
     */
    public function readCaption(): void
    {
        // since html content is allowed in caption when the property is set explicitly,
        // we have to escape it when set via json-stat to prevent html content from the untrusted source
        if (property_exists($this->reader->data, 'label')) {
            $this->caption = UtilHtml::escape($this->reader->data->label);
        }
    }

    /**
     * Return a new instance of the cell renderer.
     * @return CellInterface
     */
    protected function newCellRenderer(): CellInterface
    {
        $formatter = new FormatterCell($this->reader);
        return new CellHtml($formatter, $this->reader, $this);
    }

    /**
     * Renders the data as an html table.
     * Reads the value array and renders it as an html table.
     */
    public function render(): string
    {
        $this->build();

        return $this->dom->toHtml();
    }

    /**
     * Creates and inserts a caption.
     * @throws DOMException
     */
    public function addCaption(): void
    {
        $caption = $this->dom->insertCaption();
        $fragment = $this->doc->createDocumentFragment();
        $fragment->appendXML($this->caption);
        $caption->appendChild($fragment);
    }

    /**
     * Precalculate and cache often used numbers before rendering.
     * @return void
     * @throws DOMException
     */
    protected function init(): void
    {
        parent::init();
        $this->initTable();
    }

    /**
     * Set the attributes of the table element.
     * @return void
     * @throws DOMException
     */
    protected function initTable(): void
    {
        $numRowDims = count($this->rowDims);
        $shape = implode(',', $this->shape);
        $lastDimSize = $this->shape[count($this->shape) - 1];

        $css = new ClassList($this->domNode);
        $css->add('jst-viz', 'numRowDims'.$numRowDims, 'lastDimSize'.$lastDimSize);
        $this->domNode->setAttribute('data-shape', $shape);
        $this->domNode->setAttribute('data-num-row-dim', $numRowDims);
        $this->head = $this->dom->createTHead();
        $this->body = $this->dom->createTBody();
    }
}