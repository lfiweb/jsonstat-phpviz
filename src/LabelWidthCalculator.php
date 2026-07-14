<?php

namespace jsonstatPhpViz;

use function array_slice;
use function count;
use function strlen;

class LabelWidthCalculator
{

    public readonly int $maxValueCharWidth;
    /**
     * @var int[]
     */
    private array $valueLabelWidths;

    public function __construct(
        private readonly Reader $reader,
        private readonly int $numLabelCols,
        private readonly int $numValueCols,
        private readonly array $colDims,
        private readonly int $skippedDims,
        private readonly array $colStrides,
        private readonly bool $noLabelLastDim = false
    ) {
        $this->maxValueCharWidth = $this->preCalcMaxValueWidth();
        // Pre-calculate all value columns at once!
        $this->valueLabelWidths = $this->preCalcColDimLabelWidths($this->numValueCols);
    }

    /**
     * Calculates the number of characters from the JSON-stat value array having the most characters.
     * @return int number of characters
     */
    private function preCalcMaxValueWidth(): int
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

    /**
     * Calculates the exact required widths for all value columns using a bottom-up deficit distribution algorithm.
     * @param int $numValueCols The total number of value columns in the grid
     * @return array<int> An array of calculated widths, keyed by the excel column index ($colIdx)
     */
    protected function preCalcColDimLabelWidths(int $numValueCols): array
    {
        // Initialize all columns to a baseline width of 0
        $colWidths = array_fill(0, $numValueCols, 0);
        $numColDims = count($this->colDims);

        // Sweep from bottom to top (leaf nodes up to parent dimensions)
        for ($c = $numColDims - 1; $c >= 0; $c--) {
            $renderedDimIdx = $this->numLabelCols + $c;
            $realDimIdx = $this->skippedDims + $renderedDimIdx;
            $dimId = $this->reader->getDimensionId($realDimIdx);

            $span = $this->colStrides[$c];
            $numBlocks = $numValueCols / $span;

            // --- Category Labels (e.g., "Jura", "Mittelland") ---
            for ($b = 0; $b < $numBlocks; $b++) {
                $startCol = $b * $span;
                $categIdx = $b % $this->colDims[$c];
                $categId = $this->reader->getCategoryId($dimId, $categIdx);
                $categLabel = $this->reader->getCategoryLabel($dimId, $categId);

                $catReqWidth = mb_strlen($categLabel);
                $this->distributeDeficit($colWidths, $startCol, $span, $catReqWidth);
            }

            // --- Dimension Label (e.g., "Produktionsregion") ---
            // Skip this evaluation entirely if the label of the last dimension is not rendered
            if (!($this->noLabelLastDim && $c === ($numColDims - 1))) {
                $dimLabelSpan = $this->colDims[$c] * $span;
                $numDimBlocks = $numValueCols / $dimLabelSpan;

                $dimLabel = $this->reader->getDimensionLabel($dimId);
                $dimReqWidth = mb_strlen($dimLabel);

                for ($b = 0; $b < $numDimBlocks; $b++) {
                    $startCol = $b * $dimLabelSpan;
                    $this->distributeDeficit($colWidths, $startCol, $dimLabelSpan, $dimReqWidth);
                }
            }
        }

        return $colWidths;
    }

    /**
     * Calculates the current width of a spanned block and distributes any missing
     * width equally across its child columns.
     * * @param array<int> &$colWidths The running state array of column widths (passed by reference)
     * @param int $startCol The starting index of the child columns
     * @param int $span The number of columns spanned
     * @param int $reqWidth The minimum required characters for the parent label
     */
    private function distributeDeficit(array &$colWidths, int $startCol, int $span, int $reqWidth): void
    {
        $currentSum = array_sum(array_slice($colWidths, $startCol, $span));

        if ($reqWidth > $currentSum) {
            $padding = (int)ceil(($reqWidth - $currentSum) / $span);
            for ($i = 0; $i < $span; $i++) {
                $colWidths[$startCol + $i] += $padding;
            }
        }
    }

    /**
     * Calculate the width of a column. taking into account
     * @param int $colIdx excel column index
     * @return int
     */
    public function calculateLabelWidth(int $colIdx): int
    {
        --$colIdx;  // convert Excel 1-based to (JSON-stat) array 0-based

        if ($colIdx < $this->numLabelCols) {
            // Row dimensions (Labels) still calculate 1:1 on the fly
            $width = $this->calcRowDimLabelWidth($colIdx);
        } else {
            // value column
            $colIdx -= $this->numLabelCols;
            $width = $this->valueLabelWidths[$colIdx];
        }

        return $width;
    }

    /**
     * Calculate the required width for Row Dimensions (Label Columns).
     */
    protected function calcRowDimLabelWidth(int $colIdx): int
    {
        $realDimIdx = $this->skippedDims + $colIdx;
        $dimId = $this->reader->getDimensionId($realDimIdx);
        $labels = $this->reader->getAllCategoryLabels($dimId);
        $labels[] = $this->reader->getDimensionLabel($dimId);

        $lengths = array_map(static fn($str) => mb_strlen((string)$str), $labels);

        return max($lengths);
    }
}