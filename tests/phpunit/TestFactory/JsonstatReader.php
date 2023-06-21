<?php

namespace jsonstatPhpViz\tests\phpunit\TestFactory;

use JsonException;
use jsonstatPhpViz\src\Reader;

class JsonstatReader
{

    /**
     * Returns an instance of the jsonstatPhpViz\src\Reader class.
     * Directly loads the JSON-stat and instantiates the reader with it.
     * @param string $path
     * @return Reader
     * @throws JsonException
     */
    public function create(string $path): Reader
    {
        $json = file_get_contents($path);
        $jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        return new Reader($jsonstat);
    }
}
