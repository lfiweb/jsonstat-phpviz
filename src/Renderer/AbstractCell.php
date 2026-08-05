<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;

/**
 * Implements some methods of the CellInterface common to all cell renderers.
 */
abstract class AbstractCell implements CellInterface
{
    public FormatterCell $formatter;
    public Reader $reader;


    /**
     * @param AbstractTable $table
     */
    public function __construct(public AbstractTable $table)
    {
        $this->formatter = $table->formatter;
        $this->reader = $table->reader;
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
     * Return a category label from the JSON-stat.
     * Returns the category label by category index and dimension index.
     * @param int $catIdx category index
     * @param int $dimIdx dimensions index
     * @return string
     */
    protected function getLabel(int $catIdx, int $dimIdx): string
    {
        $reader = $this->table->reader;
        $id = $reader->getDimensionId($this->table->numOneDim + $dimIdx);
        $catId = $reader->getCategoryId($id, $catIdx);

        return $reader->getCategoryLabel($id, $catId);
    }
}