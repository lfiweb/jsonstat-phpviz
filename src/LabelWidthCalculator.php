<?php

namespace jsonstatPhpViz;

use function array_slice;
use function count;
use function strlen;

class LabelWidthCalculator
{

    public readonly int $maxValueCharWidth;

    public function __construct(
        private readonly Reader $reader,
        private readonly int $numLabelCols,
        private readonly array $rowDims,
        private readonly array $colDims,
    ) {
        $this->maxValueCharWidth = $this->calcMaxCharValues();
    }

    /**
     * Calculate the max character width for a given column purely from JSON-stat memory.
     */
    public function calculate(int $colIdx): int
    {
        if ($colIdx <= $this->numLabelCols) {
            $charLength = $this->calcRowDimLabelWidth($colIdx);
        }
        else {
            $totalRawDims = count($this->reader->data->size);
            $totalRenderedDims = count($this->rowDims) + count($this->colDims);
            $skippedDims = $totalRawDims - $totalRenderedDims;
            $charLength = $this->calcColDimLabelWidth($colIdx, $skippedDims);
            // Ensure the column is at least as wide as the largest number in the entire table
            $charLength = max($charLength, $this->maxValueCharWidth);
        }

        return $charLength;
    }

    /**
     * Calculate the required width for Row Dimensions (Label Columns).
     */
    protected function calcRowDimLabelWidth(string $dimId): int
    {
        $labels = $this->reader->getAllCategoryLabels($dimId);
        $labels[] = $this->reader->getDimensionLabel($dimId);

        $lengths = array_map(static fn($str) => mb_strlen((string)$str), $labels);

        return max($lengths);
    }

    /**
     * Calculate the required width for Column Dimensions (Value Columns).
     */
    protected function calcColDimLabelWidth(int $colIdx, int $skippedDims): int
    {
        $maxLength = 0;
        $vIdx = $colIdx - $this->numLabelCols - 1;
        $numColDims = count($this->colDims);

        for ($c = 0; $c < $numColDims; $c++) {
            $renderedDimIdx = $this->numLabelCols + $c;
            $realDimIdx = $skippedDims + $renderedDimIdx;

            $dimId = $this->reader->getDimensionId($realDimIdx);

            // Calculate horizontal span
            $remainingDims = array_slice($this->colDims, $c + 1);
            $span = empty($remainingDims) ? 1 : array_product($remainingDims);

            // 1. Evaluate Dimension Label
            $dimLabel = $this->reader->getDimensionLabel($dimId);
            $dimLabelSpan = $this->colDims[$c] * $span;

            $dimReqWidth = mb_strlen($dimLabel);
            if ($dimLabelSpan > 1) {
                $dimReqWidth = (int)ceil($dimReqWidth / $dimLabelSpan);
            }
            $maxLength = max($maxLength, $dimReqWidth);

            // 2. Evaluate Category Label
            $categIdx = (int)floor($vIdx / $span) % $this->colDims[$c];
            $categId = $this->reader->getCategoryId($dimId, $categIdx);
            $categLabel = $this->reader->getCategoryLabel($dimId, $categId);

            $catReqWidth = mb_strlen($categLabel);
            if ($span > 1) {
                $catReqWidth = (int)ceil($catReqWidth / $span);
            }
            $maxLength = max($maxLength, $catReqWidth);
        }

        return $maxLength;
    }


    private function calcMaxCharValues(): int
    {
        // Find the absolute largest and smallest (negative) numbers in the entire dataset
        $highestVal = max($this->reader->data->value);
        $lowestVal = min($this->reader->data->value); // To catch long negative numbers like -9999999

        // Calculate their raw string lengths
        $lenHigh = strlen((string)$highestVal);
        $lenLow = strlen((string)$lowestVal);

        // Store the global maximum required width for numbers
        return max($lenHigh, $lenLow);
    }

}