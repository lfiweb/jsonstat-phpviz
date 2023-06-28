<?php

namespace jsonstatPhpViz;

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
     * Return the number of decimal places.
     * Returns the number of decimal places of the last dimension "concept".
     * @return int
     */
    public function getDecimal(string $dimId, string $unitId): int
    {
        $dim = $this->data->dimension->{$dimId};

        return $dim->category->unit->{$unitId}->decimals;
    }

    /**
     * According to JSON-stat schema 2.0, when the unit property is present, the decimals property is required.
     * @return bool
     */
    public function hasDecimal($dimId): bool
    {
        $dim = $this->data->dimension->{$dimId};

        return property_exists($dim->category, 'unit');
    }

    /**
     * Returns the label of a category of a dimension by dimension id and category id.
     * @param string $dimId dimension id
     * @param string $labelId id of the category label
     * @return string label
     */
    public function getCategoryLabel(string $dimId, string $labelId): string
    {
        $dim = $this->data->dimension->{$dimId};
        if (property_exists($dim->category, 'label')) {
            $label = $dim->category->label->{$labelId};
        }
        else {  // if there is no label property, the index property is an object where the keys are the label (ids).
            $label = $labelId;
        }

        return $label;
    }

    /**
     * Returns the id of a category label.
     * @param string $dimId dimension id
     * @param int $categIdx index of the category label
     * @return string
     */
    #[Pure] public function getCategoryId(string $dimId, int $categIdx): string
    {
        $dim = $this->data->dimension->{$dimId};
        if (property_exists($dim->category, 'index')) {
            $index = $dim->category->index;
            $id = is_array($index) ? $index[$categIdx] : $this->categoryIdFromObject($index, $categIdx);
        }
        // if there is no index property, we can safely assume that we are dealing with a dimension of size one
        else {
            return key((array)$dim->category->label);
        }

        return $id;
    }

    /**
     * Return the category id by index, when category is an object.
     * @param stdClass $obj category object
     * @param int $labelIdx index of the label
     * @return string|null id of the category label
     */
    protected function categoryIdFromObject(stdClass $obj, int $labelIdx): string|null
    {
        foreach ($obj as $key => $value) {
            if ($value === $labelIdx) {
                return $key;
            }
        }

        return null;
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
