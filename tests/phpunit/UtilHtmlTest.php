<?php

namespace jsonstatPhpViz\Test;

use DOMDocument;
use DOMException;
use jsonstatPhpViz\UtilHtml;
use PHPUnit\Framework\TestCase;

class UtilHtmlTest extends TestCase
{
    /**
     * @throws DOMException
     */
    public function testAppend(): void
    {
        $strHtml = '<p class="test">This is <strong>a test</strong> to insert directly html</p>';
        $doc = new DOMDocument('1.0', 'UTF-8');
        $body = $doc->createElement('body');
        $doc->appendChild($body);
        UtilHtml::append($body, $strHtml);
        self::assertSame('<body>'.$strHtml.'</body>', $doc->saveHTML($body));
    }
}
