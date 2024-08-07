<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilArray;

/**
 * Implements some methods of the CellInterface common to all cell renderers.
 */
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
     * Returns the category label.
     * Returns the category label by value index and dimension index.
     * @param int $offset value index
     * @param int $dimIdx dimension index
     * @return string
     */
    protected function getCategoryLabel(int $offset, int $dimIdx): string
    {
        $stride = $this->table->strides[$dimIdx];
        // note: $this->table->strides[$dimIdx - 1] would have entry missing for the first dim
        $prevStride = $stride * $this->table->shape[$dimIdx];
        $catIdx = floor(($offset % $prevStride) / $stride);

        return $this->getLabel($catIdx, $dimIdx);
    }

    /**
     * Return the category label used for the row heading.
     * Returns the category label identified by dimension index and row index.
     * @param int $dimIdx dimension index
     * @param int $rowIdx row index
     * @return string
     */
    protected function getRowLabel(int $dimIdx, int $rowIdx): string
    {
        $rowStrides = UtilArray::getStrides($this->table->rowDims);
        $stride = $rowStrides[$dimIdx];
        // note: $this->table->strides[$dimIdx - 1] would have entry missing for the first dim
        $prevStride = $stride * $this->table->shape[$dimIdx];
        $catIdx = floor(($rowIdx % $prevStride) / $stride);

        return $this->getLabel($catIdx, $dimIdx);
    }

    /**
     * Return a category label from the JSON-stat.
     * Returns the category label by category index and dimension index.
     * @param int $catIdx category index
     * @param int $dimIdx dimensions index
     * @return string
     */
    protected function getLabel(int $catIdx, int $dimIdx): string
    {
        $id = $this->reader->getDimensionId($this->table->numOneDim + $dimIdx);
        $catId = $this->reader->getCategoryId($id, $catIdx);
        return $this->reader->getCategoryLabel($id, $catId);
    }
}