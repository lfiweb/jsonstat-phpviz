<?php


namespace jsonstatPhpViz\src\DOM;


use DOMElement;
use function call_user_func;
use function call_user_func_array;
use function in_array;


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
     * The contains() method of the DOMTokenList interface returns a
     * Boolean — true if the underlying list contains the given token,
     * otherwise false.
     *
     * @param string $token A DOMString representing the token you want to
     * check for the existence of in the list.
     * @return bool A Boolean, which is true if the calling list contains
     * token, otherwise false.
     * @link https://developer.mozilla.org/en-US/docs/Web/API/DOMTokenList/contains
     */
    public function contains(string $token): bool
    {
        return in_array($token, $this->callAccessor());
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

    /**
     * The remove() method of the DOMTokenList interface removes the
     * specified tokens from the list.
     *
     * @param string ...$tokens A DOMString representing the token you want
     * to remove from the list. If the string is not in the list, no error
     * is thrown, and nothing happens.
     * @link https://developer.mozilla.org/en-US/docs/Web/API/DOMTokenList/remove
     */
    public function remove(string...$tokens): void
    {
        $currentTokens = $this->callAccessor();
        foreach ($tokens as $token) {
            $key = array_search($token, $currentTokens);
            if ($key === false) {
                continue;
            }

            unset($currentTokens[$key]);
        }

        $currentTokens = array_values($currentTokens);
        $this->accessCallback = static function () use ($currentTokens) {
            return $currentTokens;
        };
        $this->callMutator(...$currentTokens);
    }

    /**
     * The replace() method of the DOMTokenList interface replaces an
     * existing token with a new token. If the first token doesn't exist,
     * replace() returns false immediately, without adding the new token to
     * the token list.
     *
     * @param string $oldToken
     * @param string $newToken
     * @return bool A boolean value, which is true if oldToken was
     * successfully replaced, or false if not.
     * @link https://developer.mozilla.org/en-US/docs/Web/API/DOMTokenList/replace
     */
    public function replace(string $oldToken, string $newToken): bool
    {
        $currentTokens = $this->callAccessor();
        $key = array_search($oldToken, $currentTokens);
        if ($key === false) {
            return false;
        }

        $currentTokens[$key] = $newToken;

        $currentTokens = array_values($currentTokens);
        $this->accessCallback = static function () use ($currentTokens) {
            return $currentTokens;
        };
        $this->callMutator(...$currentTokens);
        return true;
    }

    /**
     * The toggle() method of the DOMTokenList interface removes a given
     * token from the list and returns false. If token doesn't exist it's
     * added and the function returns true.
     *
     * @param string $token A DOMString representing the token you want to
     * toggle.
     * @param ?bool $force A Boolean that, if included, turns the toggle
     * into a one way-only operation. If set to false, then token will only
     * be removed, but not added. If set to true, then token will only be
     * added, but not removed.
     * @return bool A Boolean indicating whether token is in the list after
     * the call.
     * @link https://developer.mozilla.org/en-US/docs/Web/API/DOMTokenList/toggle
     */
    public function toggle(string $token, bool $force = null): bool
    {
        /** @var ?bool $addRemove True to add, false to remove */
        $addRemove = $force;

        $currentTokens = $this->callAccessor();
        $key = in_array($token, $currentTokens);
        if ($key === false) {
            if ($force !== false) {
                $addRemove = true;
            }
        } elseif ($force !== true) {
            $addRemove = false;
        }

        if ($addRemove) {
            $this->add($token);
            return true;
        }

        $this->remove($token);
        return false;
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
