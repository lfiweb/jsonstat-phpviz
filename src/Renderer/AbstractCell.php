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
     * @return string
     */
    protected function getCategoryLabel(int $offset, int $dimIdx): string
    {
        $stride = $this->table->strides[$dimIdx];
        // note: $this->table->strides[$dimIdx - 1] would have entry missing for the first dim
        $prevStride = $stride * $this->table->shape[$dimIdx];
        $id = $this->reader->getDimensionId($this->table->numOneDim + $dimIdx);
        $catIdx = floor(($offset % $prevStride) / $stride);
        $catId = $this->reader->getCategoryId($id, $catIdx);

        return $this->reader->getCategoryLabel($id, $catId);
    }
}