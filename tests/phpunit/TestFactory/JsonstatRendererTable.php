<?php

namespace jsonstatPhpViz\tests\phpunit\TestFactory;

use DOMElement;
use DOMException;
use DOMNodeList;
use jsonstatPhpViz\src\Reader;
use jsonstatPhpViz\src\RendererTable;

class JsonstatRendererTable
{
    /**
     * Holds a reference to the DOMElement <table>
     * @var DOMElement
     */
    public DOMElement $domTable;

    /**
     * Instantiates the jsonstatPhpViz\src\RendererTable class
     * Directly renders the table and sets its DOM to the domTable property.
     * @throws DOMException
     */
    public function __construct(Reader $reader, int $numRowDim, bool $excludeNumOneDim = false)
    {
        $table = new RendererTable($reader, $numRowDim);
        $table->excludeOneDim = $excludeNumOneDim;
        $this->domTable = $table->render(false);
    }

    /**
     * Returns all children of the last child of the table head element.
     * @return DOMNodeList
     */
    public function getTheadLastChildNodes(): DOMNodeList
    {

        return $this->domTable->getElementsByTagName('thead')->item(0)->lastChild->childNodes;
    }

    /**
     * Return all children of the table body element.
     * @return DOMNodeList
     */
    public function getTBodyChildNodes(): DOMNodeList
    {

        return $this->domTable->getElementsByTagName('tbody')->item(0)->childNodes;
    }
}
