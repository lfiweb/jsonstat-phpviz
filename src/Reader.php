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
        if (property_exists($dim->category, 'index')) {
            $index = $dim->category->index;

            return is_array($index) ? $index[$categIdx] : $this->categoryIdFromObject($index, $categIdx);
        }

        return null;
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
     * Dimensions of size 1 are excluded by default, if each dimension with a lower index is also of size one, e.g.:
     * [1,1,3,2,5] --> [3,2,5], but
     * [1,1,3,2,1] --> [3,2,1]
     * @param bool $excludeSizeOne do not return dimensions of size one from the beginning
     * @return array list of sizes
     */
    public function getDimensionSizes(bool $excludeSizeOne = true): array
    {
        $size = 0;
        $dimensions = $this->data->size;
        $arr = array_filter($dimensions, static function ($value, $idx) use ($excludeSizeOne, $dimensions, $size) {
            if ($excludeSizeOne && self::continuous($dimensions, $idx)) {
                $size = 1;
            }
            return $value > $size;
        }, ARRAY_FILTER_USE_BOTH);

        return array_values($arr);  // reindex the keys, in case some values were excluded with the filter
    }

    /**
     * Check if the $dimension at $idx is of size 1 and all dimensions with a lower index also.
     * @param array $dimensions
     * @param $idx
     * @return bool
     */
    private static function continuous(array $dimensions, $idx): bool
    {
        for (; $idx > -1; $idx--) {
            if ($dimensions[$idx] !== 1) {
                return false;
            }
        }

        return $idx === -1;
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
        if (property_exists($dim->category, 'index')) {
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
        foreach ($obj as $key => $value) {
            if ($value === $labelIdx) {
                return $key;
            }
        }

        return $key;
    }

    /**
     * Permute the axes of the value array.
     * @see https://numpy.org/doc/stable/reference/generated/numpy.transpose.html
     * @param array $axes contains the permutations of [0,1,..,N-1] where N is the number of axes of the value array
     * @return void
     */
    public function transpose(array $axes): void
    {
        $jsonstat = $this->data;
        $jsonstat->value = UtilArray::transpose($jsonstat->value, $jsonstat->size, $axes);
        $jsonstat->id = UtilArray::swap($jsonstat->id, $axes);
        $jsonstat->size = UtilArray::swap($jsonstat->size, $axes);
    }
}
