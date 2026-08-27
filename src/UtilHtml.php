<?php

namespace jsonstatPhpViz;

use DOMDocument;
use DOMNode;

/**
 * Utility that provides HTML-related methods.
 */
class UtilHtml
{

    /**
     * Append a markup fragment to a node.
     *
     * The fragment is first parsed as XML, which is the fastest path and the common case.
     * Strings that are not well-formed XML - for example, labels containing '<1%' or HTML-only
     * entities such as &nbsp; - are then parsed with the more forgiving HTML parser.
     * If both fail, the string is appended as text so that content is never silently lost.
     *
     * @param DOMNode $node node to append to
     * @param string $html markup fragment
     * @return bool true when the fragment was parsed as markup, false when appended as text
     */
    public static function append(DOMNode $node, string $html): bool
    {
        $doc = $node->ownerDocument;
        $fragment = $doc->createDocumentFragment();
        $parsed = false;

        $prev = libxml_use_internal_errors(true);   // enable internal errors
        try {
            if ($fragment->appendXML($html)) {
                $node->appendChild($fragment);
                $parsed = true;
            } else {
                $parsed = self::appendAsHtml($node, $html);
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);  // restore previous state
        }

        if ($parsed === false) {
            // last resort: the string is not (valid) markup at all, append it as text
            $node->appendChild($doc->createTextNode($html));
        }

        return $parsed;
    }

    /**
     * Parse the fragment with the HTML parser and import the resulting nodes.
     */
    private static function appendAsHtml(DOMNode $node, string $html): bool
    {
        $tmp = new DOMDocument('1.0', 'UTF-8');
        // the <meta> element pins the encoding, the flags LIBXML_HTML_* prevent html/body/doctype from being added
        $loaded = $tmp->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        $wrapper = $loaded ? $tmp->getElementsByTagName('div')->item(0) : null;

        if ($wrapper !== null) {
            foreach (iterator_to_array($wrapper->childNodes) as $child) {
                $node->appendChild($node->ownerDocument->importNode($child, true));
            }
        }

        return $wrapper !== null;
    }

    /**
     * Escape a string coming from the JSON-stat so that it can safely be used in a HTML fragment.
     * Already encoded entities are left intact ($doubleEncode = false), so calling this twice is harmless.
     */
    public static function escape(?string $str, bool $doubleEncode = false): string
    {
        return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

}