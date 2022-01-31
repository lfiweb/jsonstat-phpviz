<?php

namespace jsonstatPhpViz\src;

use JetBrains\PhpStorm\Pure;
use stdClass;
use function count;
use function is_array;

/**
 * Class to work with json-stat.org files.
 */
class Reader
{
    public stdClass $data;

    /**
     * @param stdClass $jsonstat
     */
    public function __construct(stdClass $jsonstat)
    {
        $this->data = $jsonstat;
    }

    /**
     * Returns the id of a dimension by its array index.
     * @param int $dimIdx index of the dimension array
     * @return string id of the dimension
     */
    public function getDimensionId(int $dimIdx): string
    {
        return $this->data->id[$dimIdx];
    }

    /**
     * Returns the id of a category label.
     * @param int $categIdx index of the category label
     */
    #[Pure] public function getCategoryId(string $dimId, int $categIdx) {
        $dim = $this->data->dimension->{$dimId};
        $index = $dim->category->index;

        return is_array($index) ? $index[$categIdx] : $this->categoryIdFromObject($index, $categIdx);
    }

    /**
     * Returns the label of a dimension by its id.
     * @param string $dimId dimension id
     * @return string dimension label
     */
    public function getDimensionLabel(string $dimId): string
    {
        return $this->data->dimension->{$dimId}->label;
    }

    /**
     * Return list with the sizes of the dimensions.
     * @param bool $excludeSizeOne do not return dimensions with size one
     * @return array list of sizes
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
     * Returns the label of a category of a dimension by dimension id and category id.
     * @param string $dimId dimension id
     * @param ?string $labelId id of the category label
     * @return string label
     */
    public function getCategoryLabel(string $dimId, ?string $labelId = null): string
    {
        $dim = $this->data->dimension->{$dimId};
        $index = $dim->category->index;
        if ($index) {

            $label = $dim->category->label->{$labelId} ?? $labelId;
        } else {  // e.g. constant dimension with a single category and no index, label is required
            $keys = array_keys((array)$dim->category->label);
            $label = $dim->category->label->{$keys[0]};
        }

        return $label;
    }

    /**
     * Return the category id by index, when category is an object.
     * @param stdClass $obj category object
     * @param int $labelIdx index of the label
     * @return string id of the category label
     */
    protected function categoryIdFromObject(stdClass $obj, int $labelIdx): string
    {
        $arr = array_keys((array)$obj);

        return array_search($labelIdx, $arr, true);
    }
}
