<?php
declare(strict_types=1);

namespace jsonstatPhpViz\Test\TestFactory;

use jsonstatPhpViz\Reader;
use stdClass;
use function array_is_list;
use function count;

/**
 * Build a minimal JSON-stat structure for testing.
 *
 * Only the properties required by the Reader are created, so a fixture stays readable
 * inside the test that uses it. The methods indexAsList(), withoutCategoryLabels() and unit()
 * modify the dimension that was added last, which allows covering the schema variants
 * of the category property, e.g.:
 *
 *  JsonstatBuilder::create()
 *      ->dimension('area', 'Region', ['CH' => 'Switzerland'])
 *      ->dimension('year', '2003-2004', ['2003', '2004'])->withoutCategoryLabels()
 *      ->reader();
 */
class JsonstatBuilder
{
    /** @var string|null the dataset label, which is used as the table caption */
    private ?string $label = null;

    /** @var array<int, string> dimension ids in the order of rendering */
    private array $ids = [];

    /** @var array<int, int> the shape */
    private array $sizes = [];

    /** @var array<string, stdClass> dimension objects by dimension id */
    private array $dimensions = [];

    /** @var array<string, array<int, string>> category ids by dimension id */
    private array $categoryIds = [];

    /** @var array<int, string|int|float|null>|null */
    private ?array $values = null;

    /** @var string|null id of the dimension added last */
    private ?string $lastId = null;

    public static function create(): self
    {
        return new self();
    }

    /**
     * Create a fixture from a shape alone.
     * Dimensions are named A, B, C, ... and get generated category ids and labels, e.g. 'B2' labelled 'B: 2'.
     * Note: use this whenever a test only cares about the number of rows and columns.
     * @param array<int, int> $shape sizes of the dimensions, max. 26 dimensions
     */
    public static function fromShape(array $shape): self
    {
        $builder = self::create();
        foreach ($shape as $dimIdx => $size) {
            $dimId = chr(65 + $dimIdx);
            $categories = [];
            for ($categIdx = 1; $categIdx <= $size; $categIdx++) {
                $categories[$dimId.$categIdx] = $dimId.': '.$categIdx;
            }
            $builder->dimension($dimId, 'dimension '.$dimId, $categories);
        }

        return $builder;
    }

    /**
     * Set the dataset label.
     * Note: when never called, the label property is omitted entirely, so no caption is rendered.
     */
    public function label(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Append a dimension.
     * @param array<string, string>|array<int, string> $categories map of category id => label, or a list of ids
     */
    public function dimension(string $id, string $label, array $categories): self
    {
        $categories = array_is_list($categories) ? array_combine($categories, $categories) : $categories;
        $categoryIds = array_keys($categories);

        $dim = new stdClass();
        $dim->label = $label;
        $dim->category = new stdClass();
        // a dimension of size one has no index property according to the JSON-stat schema
        if (count($categoryIds) > 1) {
            $dim->category->index = (object)array_combine($categoryIds, range(0, count($categoryIds) - 1));
        }
        $dim->category->label = (object)$categories;

        $this->ids[] = $id;
        $this->sizes[] = count($categoryIds);
        $this->dimensions[$id] = $dim;
        $this->categoryIds[$id] = $categoryIds;
        $this->lastId = $id;

        return $this;
    }

    /**
     * Use a JSON array instead of an object for the category.index property of the dimension added last.
     */
    public function indexAsList(): self
    {
        $this->dimensions[$this->lastId]->category->index = $this->categoryIds[$this->lastId];

        return $this;
    }

    /**
     * Drop the category.label property of the dimension added last, so that the ids act as labels.
     */
    public function withoutCategoryLabels(): self
    {
        unset($this->dimensions[$this->lastId]->category->label);

        return $this;
    }

    /**
     * Add a category.unit property to the dimension added last.
     * @param array<string, int> $decimals map of category id => number of decimals
     */
    public function unit(array $decimals, string $symbol = ''): self
    {
        $unit = new stdClass();
        foreach ($decimals as $categoryId => $num) {
            $unit->{$categoryId} = (object)['symbol' => $symbol, 'decimals' => $num];
        }
        $this->dimensions[$this->lastId]->category->unit = $unit;

        return $this;
    }

    /**
     * Set the value array explicitly.
     * @param array<int, string|int|float|null> $values
     */
    public function values(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Return the JSON-stat.
     * Note: when no values were set, they default to 1, 2, ..., n, which makes the offset of a cell
     *      readable from its content when a rendered table is inspected.
     */
    public function build(): stdClass
    {
        $jsonstat = new stdClass();
        $jsonstat->version = '2.0';
        $jsonstat->class = 'dataset';
        if ($this->label !== null) {
            $jsonstat->label = $this->label;
        }
        $jsonstat->id = $this->ids;
        $jsonstat->size = $this->sizes;
        $jsonstat->value = $this->values ?? range(1, (int)array_product($this->sizes));
        $jsonstat->dimension = (object)$this->dimensions;

        return $jsonstat;
    }

    public function reader(): Reader
    {
        return new Reader($this->build());
    }
}