<?php

namespace jsonstatPhpViz;

use function array_slice;
use function count;
use function strlen;

class LabelWidthCalculator
{

    /**
     * Holds the precalculated maximum string length of value cells by columns.
     * @var array|int[]
     */
    public readonly array $maxValueCharWidths;

    /**
     * Holds the precalculated maximum string length of header labels by columns.
     *
     * @var array|int[]
     */
    public readonly array $valueLabelWidths;

    public function __construct(
        private readonly Reader $reader,
        private readonly int $numLabelCols,
        private readonly int $numValueCols,
        private readonly array $colDims,
        private readonly int $skippedDims,
        private readonly array $colStrides,
        private readonly bool $noLabelLastDim = false
    ) {
        $this->maxValueCharWidths = $this->preCalcMaxValueWidths();
        $this->valueLabelWidths = $this->preCalcColDimLabelWidths($this->numValueCols);
    }

    /**
     * Calculates the maximum string length of the data values for each value column.
     *
     * @return array<int, int> Map of zero-indexed column offset to max character length
     */
    private function preCalcMaxValueWidths(): array
    {
        $widths = array_fill(0, $this->numValueCols, 0);
        $values = $this->reader->data->value;
        $totalValues = count($values);

        // Process one column at a time
        for ($colIdx = 0; $colIdx < $this->numValueCols; $colIdx++) {
            $maxLen = 0;

            // Step through the flat array using the number of columns as the stride
            for ($i = $colIdx; $i < $totalValues; $i += $this->numValueCols) {
                if ($values[$i] === null) {
                    continue;
                }

                $len = mb_strlen((string)$values[$i]);
                if ($len > $maxLen) {
                    $maxLen = $len;
                }
            }
            $widths[$colIdx] = $maxLen;
        }

        return $widths;
    }

    /**
     * Calculates the exact required widths for all header value label columns using a bottom-up deficit distribution algorithm.
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
     * Calculate the width of a column.
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