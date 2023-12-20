<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;

abstract class AbstractCell implements CellInterface
{
    protected Reader $reader;
    protected FormatterCell $formatter;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader)
    {
        $this->reader = $reader;
        $this->formatter = $cellFormatter;
    }

    /**
     * @param int $offset
     * @param int $dimIdx
     * @param $stride
     * @param $product
     * @return string
     */
    protected function getCategoryLabel(int $offset, int $dimIdx, $stride, $product): string
    {
        $id = $this->reader->getDimensionId($dimIdx);
        $catIdx = floor(($offset % $product) / $stride);
        $catId = $this->reader->getCategoryId($id, $catIdx);

        return $this->reader->getCategoryLabel($id, $catId);
    }
}