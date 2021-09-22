<?php

namespace jsonstatPhpViz;

use stdClass;

/**
 * Class to work with jsonstat.org files.
 */
class JsonStatReader
{
    public $data;

    /**
     * @param stdClass jsonstat
     * @param string jsonstat.label
     * @param array jsonstat.id
     * @param array jsonstat.size
     * @param array jsonstat.value
     * @param object jsonstat.dimension
     * @property {Object} data
     */
    public function __construct(stdClass $jsonstat)
    {
        $this->data = $jsonstat;
    }

    /**
     * Returns the id of a dimension by its index.
     * @param int $dimIdx
     * @return mixed {*}
     */
    public function getId(int $dimIdx)
    {
        return $this->data->id[$dimIdx];
    }

    /**
     * Returns the label of a dimension by its index.
     * @param int $dimIdx
     * @return string
     */
    public function getLabel(int $dimIdx): string
    {
        return $this->data->dimension->{$this->getId($dimIdx)}->label;
    }

    /**
     * Return list with the sizes of the dimensions.
     * @param bool $excludeSizeOne
     * @return array
     */
    public function getDimensionSizes(bool $excludeSizeOne = true): array
    {
        $size = $excludeSizeOne ? 1 : 0;

        return array_filter($this->data->size, static function ($value) use ($size) {
            return $value > $size;
        });
    }

    /**
     * Returns the number of values.
     * @return int
     */
    public function getNumValues(): int
    {

        return count($this->data->value);
    }

    /**
     * Returns the label of a category of a dimension by dimension index and category index.
     * @param int $dimIdx
     * @param int|null $labelIdx
     * @return string
     */
    public function getCategoryLabel(int $dimIdx, ?int $labelIdx = null): string
    {
        $dim = $this->data->dimension->{$this->getId($dimIdx)};
        $index = $dim->category->index;
        if ($index) {
            $id = is_array($index) ? $index[$labelIdx] : $this->categoryIdFromObject($index, $labelIdx);
            $label = $dim->category->label->{$id} ?? $id;
        } else {  // e.g. constant dimension with single category and no index, label is required
            $keys = array_keys((array)$dim->category->label);
            $label = $dim->category->label->{$keys[0]};
        }

        return $label;
    }

    /**
     * @param stdClass $obj
     * @param $labelIdx
     * @return string
     */
    protected function categoryIdFromObject(stdClass $obj, $labelIdx): string
    {
        $arr = array_keys((array)$obj);

        return array_search($labelIdx, $arr, true);
    }
}