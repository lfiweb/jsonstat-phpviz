<?php


namespace jsonstatPhpViz\DOM;


use DOMElement;
use function call_user_func;
use function call_user_func_array;


/**
 * Represents a set of space-separated css class names.
 * Simplified from PhpGt DOM
 * @see https://github.com/PhpGt/Dom/blob/facade/src/DOMTokenList.php
 */
class ClassList
{

    /** @var callable Return an indexed array of tokens */
    private $accessCallback;

    /** @var callable Variadic string parameters, void return */
    private $mutateCallback;

    /** @var string */
    private string $className;

    public function __construct(DOMElement $element)
    {
        $this->className = $element->getAttribute('class');
        $this->accessCallback = function () {
            return explode(' ', $this->className);
        };
        $this->mutateCallback = static function (string ...$tokens) use ($element) {
            $element->setAttribute('class', implode(' ', $tokens));
        };
    }

    /**
     * The add() method of the DOMTokenList interface adds the given token
     * to the list.
     *
     * @param string ...$tokens A DOMString representing the token (or
     * tokens) to add to the tokenList.
     * @link https://developer.mozilla.org/en-US/docs/Web/API/DOMTokenList/add
     */
    public function add(string...$tokens): void
    {
        $existing = $this->callAccessor();
        $newTokens = array_merge($existing, $tokens);
        $newTokens = array_unique($newTokens);
        $this->callMutator(...$newTokens);
    }

    /** @return string[] */
    private function callAccessor(): array
    {
        $values = call_user_func($this->accessCallback);
        return array_filter($values);
    }

    private function callMutator(string...$values): void
    {
        call_user_func_array($this->mutateCallback, $values);
    }
}
