<?php


namespace jsonstatPhpViz\src\DOM;


use DOMDocument;
use DOMElement;
use DOMNode;
use function in_array;

/**
 * Class HTMLTableElement
 * @package jsonstatPhpViz
 *
 * Simplified from PhpGt DOM
 * @see https://github.com/PhpGt/Dom/blob/facade/src/HTMLElement/HTMLTableElement.php
 */
class Table
{
    /** @var DOMDocument */
    public DOMDocument $doc;

    /**
     * @var DOMElement
     */
    private DOMElement|false $domNode;

    /** @var int */
    private int $rowIndex = 0;

    /**
     * @throws \DOMException
     */
    public function __construct()
    {
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->preserveWhiteSpace = false;
        $this->domNode = $this->doc->createElement('table');
        $this->doc->appendChild($this->domNode);
    }

    /**
     * Returns the DOMElement of the table.
     * @return DOMElement|false
     */
    public function get(): bool|DOMElement
    {
        return $this->domNode;
    }

    /**
     * Return the table as a html string
     * @return false|string html
     */
    public function toHtml(): bool|string
    {
        // do not use saveHTML, since that will encode html entities
        return $this->doc->saveXML($this->domNode);
    }

    /**
     * Returns the table head element.
     * @return DOMElement
     * @throws \DOMException
     */
    public function createTHead(): DOMElement
    {
        return $this->getCreateChild('thead');
    }

    /**
     * Creates the HTMLTBodyElement.
     * @return DOMElement
     * @throws \DOMException
     */
    public function createTBody(): DOMElement
    {
        $tbody = $this->doc->createElement('tbody');
        $this->placeTBody($tbody);

        return $tbody;
    }

    /**
     * Appends a row to the table.
     * @param DOMElement $parent
     * @return DOMNode
     * @throws \DOMException
     */
    public function appendRow(DOMElement $parent): DOMNode
    {
        $row = $this->doc->createElement('tr');
        $row->setAttribute('rowindex', $this->rowIndex);
        $this->rowIndex++;
        $this->domNode->appendChild($parent);

        return $parent->appendChild($row);
    }

    /**
     * Inserts and returns an empty caption element
     * @return DOMElement caption element
     */
    public function insertCaption(): DOMElement
    {

        return $this->getCreateChild('caption');
    }

    /**
     * Return existing child or create it first if it does not exist.
     * If the child already exists it is simply returned. If not, it will be created first
     * and inserted at the correct place before being returned.
     * @param string $name element name
     * @return DOMElement
     * @throws \DOMException
     */
    private function getCreateChild(string $name): DOMElement
    {
        $child = $this->hasChildFirst($name);
        if ($child === null) {
            $child = $this->doc->createElement($name);
            $this->placeChild($name, $child);
        }

        return $child;
    }

    /**
     * Check if the table already has the specified child element.
     * Returns the first occurrence of the child or null if child was not found.
     * @param string $name element name
     * @return null|DOMNode
     */
    private function hasChildFirst(string $name): ?DOMNode
    {
        for ($i = 0, $len = $this->domNode->childNodes->length; $i < $len; $i++) {
            $child = $this->domNode->childNodes->item($i);
            if ($child !== null && strtolower($child->nodeName) === $name) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Check if the table already has the specified child element.
     * Returns the last occurrence of the child or null if child was not found.
     * @param string $name element name
     * @return DOMNode
     */
    private function hasChildLast(string $name): DOMNode
    {
        $lastChild = null;
        for ($i = 0, $len = $this->domNode->childNodes->length; $i < $len; $i++) {
            $child = $this->domNode->childNodes->item($i);
            if ($child !== null && strtolower($child->nodeName) === $name) {
                $lastChild = $child;
            }
        }

        return $lastChild;
    }

    /**
     * Insert the section element after the specified nodes.
     * @param DOMElement $newNode
     * @param string[] $refNames names of nodes to insert after
     */
    private function insertChildAfter(DOMElement $newNode, array $refNames): void
    {
        $child = $this->getFirstElementChild($this->domNode);
        while ($child && in_array($child->nodeName, $refNames, true)) {
            $child = $this->getNextElementSibling($child);
        }
        $this->domNode->insertBefore($newNode, $child);
    }

    /**
     * Place the child at the correct location.
     * @param string $name
     * @param ?DOMElement $node
     */
    private function placeChild(string $name, ?DOMElement $node): void
    {
        switch ($name) {
            case 'caption':
                $this->placeCaption($node);
                break;
            case 'thead':
                $this->placeThead($node);
                break;
            case 'tfoot':
                $this->placeTFoot($node);
                break;
        }
    }

    /**
     * Inserts a caption element.
     * @param DOMElement|null $caption
     * @return void
     */
    private function placeCaption(?DOMElement $caption): void
    {
        if ($caption !== null) {
            $this->domNode->insertBefore($caption, $this->domNode->firstChild);
        }
    }

    /**
     * Inserts a thead element
     * @param DOMElement|null $thead
     * @return void
     */
    private function placeThead(?DOMElement $thead): void
    {
        if ($thead !== null) {
            $this->insertChildAfter($thead, ['caption', 'colgroup']);
        }
    }

    /**
     * Inserts a tbody element.
     * @param DOMElement|null $tbody
     * @return void
     */
    private function placeTBody(?DOMElement $tbody): void
    {
        if ($tbody !== null) {
            $this->insertChildAfter($tbody, ['caption', 'colgroup', 'thead', 'tbody']);
        }
    }

    /**
     * Inserts a tfoot element.
     * @param DOMElement|null $tfoot
     * @return void
     */
    private function placeTFoot(?DOMElement $tfoot): void
    {
        if ($tfoot !== null) {
            $this->domNode->appendChild($tfoot);
        }
    }

    /**
     *
     * @param DOMNode $node
     * @return DOMNode|null
     */
    protected function getFirstElementChild(DOMNode $node): ?DOMNode
    {
        for ($i = 0, $len = $node->childNodes->length; $i < $len; $i++) {
            $child = $node->childNodes->item($i);
            if ($child instanceof DOMElement) {
                continue;
            }

            return $child;
        }

        return null;
    }

    protected function getNextElementSibling($node)
    {
        $context = $node;
        while ($context = $context->nextSibling) {
            if ($node instanceof DOMElement) {
                return $context;
            }
        }

        return null;
    }
}
