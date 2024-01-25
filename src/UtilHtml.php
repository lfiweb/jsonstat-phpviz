<?php

namespace jsonstatPhpViz;

use DOMDocument;
use DOMNode;

/**
 * Utility that provides html related methods.
 */
class UtilHtml
{

    /**
     * Append a html fragment to the provided node.
     * @param DOMNode $parent
     * @param string $html
     * @return void
     */
    public static function append(DOMNode $parent, string $html): void
    {
        // DOMDocument::appendXML() requires X(HT)ML as input,
        // which can be especially cumbersome with SVG because of namespaces.
        // Thus, in some cases, it's easier to just insert html instead.
        $tmpDoc = new DOMDocument();
        @$tmpDoc->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>');    // prevent complaining about svg
        $frag = $tmpDoc->getElementsByTagName('div')->item(0);
        foreach ($frag?->childNodes as $node) {
            $node = $parent->ownerDocument->importNode($node, true);
            $parent->appendChild($node);
        }
    }

    /**
     * Escape a string, so it can be safely inserted into html.
     * @param String $text
     * @return String
     */
    public static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_HTML5, 'UTF-8');
    }

}