<?php
declare(strict_types=1);

namespace jsonstatPhpViz\Test;

use DOMDocument;
use DOMElement;
use DOMException;
use jsonstatPhpViz\UtilHtml;
use PHPUnit\Framework\TestCase;

class UtilHtmlTest extends TestCase
{
    private DOMDocument $doc;

    private DOMElement $body;

    /**
     * This method is called before each test.
     * @throws DOMException
     */
    protected function setUp(): void
    {
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->body = $this->doc->createElement('body');
        $this->doc->appendChild($this->body);
    }

    /**
     * Test that a well-formed fragment is inserted as markup (XML path).
     */
    public function testAppend(): void
    {
        $strHtml = '<p class="test">This is <strong>a test</strong> to insert directly html</p>';
        $parsed = UtilHtml::append($this->body, $strHtml);
        self::assertTrue($parsed);
        self::assertSame('<body>'.$strHtml.'</body>', $this->doc->saveHTML($this->body));
    }

    /**
     * Test that a fragment with more than one root node is appended completely.
     */
    public function testAppendMultipleRootNodes(): void
    {
        UtilHtml::append($this->body, '<b>Vorrat</b> und <b>Zuwachs</b>');
        self::assertSame(3, $this->body->childNodes->length);
        self::assertSame(2, $this->body->getElementsByTagName('b')->length);
        self::assertSame('Vorrat und Zuwachs', $this->body->textContent);
    }

    /**
     * Test that an HTML-only entity is resolved by the HTML parser instead of breaking the XML parser.
     */
    public function testAppendHtmlEntity(): void
    {
        $parsed = UtilHtml::append($this->body, 'm&sup3;/ha&nbsp;&plusmn;%');
        self::assertTrue($parsed);
        self::assertSame("m\u{00B3}/ha\u{00A0}\u{00B1}%", $this->body->textContent);
    }

    /**
     * Test that the encoding is preserved when the HTML parser is used as a fallback.
     * Note: without pinning the encoding, the HTML parser would assume ISO-8859-1 and mangle the label.
     */
    public function testAppendPreservesUtf8(): void
    {
        UtilHtml::append($this->body, 'Alpens&uuml;dseite&nbsp;<b>Total</b>');
        self::assertSame("Alpensüdseite\u{00A0}Total", $this->body->textContent);
    }

    /**
     * Test that a bare ampersand, which is not well-formed XML, is kept as text.
     */
    public function testAppendUnescapedAmpersand(): void
    {
        UtilHtml::append($this->body, 'Vorrat & Zuwachs');
        self::assertSame('Vorrat & Zuwachs', $this->body->textContent);
        // the serializer escapes the ampersand again, so the output stays valid
        self::assertStringContainsString('Vorrat &amp; Zuwachs', $this->doc->saveXML($this->body));
    }

    /**
     * Test that markup missing its end tag is repaired by the HTML parser.
     */
    public function testAppendUnclosedTag(): void
    {
        $parsed = UtilHtml::append($this->body, '<p>unclosed');
        self::assertTrue($parsed);
        self::assertSame(1, $this->body->getElementsByTagName('p')->length);
        self::assertSame('unclosed', $this->body->textContent);
    }

    /**
     * Test that content is never silently lost, whichever of the three paths is taken.
     */
    public function testAppendNeverLosesContent(): void
    {
        $strings = [
            'plain label' => 'plain label',
            'markup' => '1%',   // '<1%' is neither valid XML nor a tag
            'entity' => '1%',
        ];
        $fragments = [
            'plain label' => 'plain label',
            'markup' => '<1%',
            'entity' => '&lt;1%',
        ];
        foreach ($fragments as $key => $fragment) {
            $doc = new DOMDocument('1.0', 'UTF-8');
            $body = $doc->createElement('body');
            $doc->appendChild($body);
            UtilHtml::append($body, $fragment);
            self::assertStringContainsString($strings[$key], $body->textContent, $fragment);
        }
    }

    /**
     * Test that the libxml error handling is left untouched by append().
     * Note: append() has to enable the internal error handling to be able to try the parsers,
     *      but must restore the state of the calling code and must not leak errors into its buffer.
     */
    public function testAppendRestoresLibxmlState(): void
    {
        $initial = libxml_use_internal_errors(true);
        libxml_clear_errors();
        UtilHtml::append($this->body, 'Vorrat & Zuwachs');    // fails as XML, thus produces libxml errors
        self::assertTrue(libxml_use_internal_errors(true));
        self::assertSame([], libxml_get_errors());

        libxml_use_internal_errors(false);
        UtilHtml::append($this->body, 'Vorrat & Zuwachs');
        self::assertFalse(libxml_use_internal_errors(false));

        libxml_use_internal_errors($initial);
    }

    /**
     * Test that an escaped string is appended as text and not as markup.
     * Note: this is the combination used for labels from the JSON-stat, e.g. escape() then append().
     */
    public function testAppendEscapedMarkup(): void
    {
        $strHtml = '<i>Test:</i> cell';
        UtilHtml::append($this->body, UtilHtml::escape($strHtml));
        self::assertSame(0, $this->body->getElementsByTagName('i')->length);
        self::assertSame($strHtml, $this->body->textContent);
    }

    /**
     * Test that markup and both quote types are escaped.
     */
    public function testEscape(): void
    {
        self::assertSame('&lt;i&gt;Test:&lt;/i&gt; cell', UtilHtml::escape('<i>Test:</i> cell'));
        self::assertSame('&quot;a&quot; &amp; &#039;b&#039;', UtilHtml::escape('"a" & \'b\''));
        self::assertSame('&gt;= 52 cm', UtilHtml::escape('>= 52 cm'));
    }

    /**
     * Test that already encoded entities are left intact, so that escaping twice is harmless.
     */
    public function testEscapeDoubleEncode(): void
    {
        $str = UtilHtml::escape('>= 52 cm');
        self::assertSame($str, UtilHtml::escape($str));
        self::assertSame('&amp;gt;= 52 cm', UtilHtml::escape($str, true));
    }

    /**
     * Test that null is escaped to an empty string.
     * Note: an empty string prevents void elements such as <td/> from being created.
     */
    public function testEscapeNull(): void
    {
        self::assertSame('', UtilHtml::escape(null));
    }

    /**
     * Test that an invalid encoding is substituted instead of returning an empty string.
     */
    public function testEscapeInvalidUtf8(): void
    {
        $str = UtilHtml::escape("Fl\xE4che");   // 'Fläche' encoded as ISO-8859-1
        self::assertStringContainsString("\u{FFFD}", $str);
        self::assertStringContainsString('che', $str);
    }
}