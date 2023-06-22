<?php

namespace jsonstatPhpViz\tests\phpunit\TestFactory;

use DOMElement;
use DOMNodeList;

class RendererTable
{
    /**
     * Returns all children of the last child of the table head element.
     * @param DOMElement $table table element
     * @return DOMNodeList
     */
    public static function getTheadLastChildNodes(DOMElement $table): DOMNodeList
    {

        return $table->getElementsByTagName('thead')->item(0)->lastChild->childNodes;
    }

    /**
     * Return all children of the table body element.
     * @param DOMElement $table table element
     * @return DOMNodeList
     */
    public static function getTBodyChildNodes(DOMElement $table): DOMNodeList
    {

        return $table->getElementsByTagName('tbody')->item(0)->childNodes;
    }
}
