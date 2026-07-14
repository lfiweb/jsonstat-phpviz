<?php

namespace jsonstatPhpViz\Renderer;

use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Apply styles to the worksheet.
 */
class StylerExcel
{
    /**
     * minimum column width when the column is empty
     */
    public const COL_WIDTH_MIN = 4;
    /**
     * maximum column width when the column has more characters
     */
    public const COL_WIDTH_MAX = 32;

    /**
     * Style the Excel.
     * @param TableExcel $table
     */
    public function style(TableExcel $table): void
    {
        $this->styleCaption($table);
        $this->styleHeader($table);
        $this->styleLabelCellBody($table);
        $this->styleValueCellBody($table);
        $table->getActiveWorksheet()->setSelectedCell('A1');    // there doesn't seem to be a deselect method
    }

    /**
     * Style the caption cells of the current worksheet.
     * @param TableExcel $table
     * @return void
     */
    public function styleCaption(TableExcel $table): void
    {
        $worksheet = $table->getActiveWorksheet();
        $worksheet->getRowDimension(1)->setRowHeight(24);
        $style = $worksheet->getStyle([1, 1, 1, 1]);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }

    /**
     * Style the header cells of the current worksheet.
     * @param TableExcel $table
     * @return void
     */
    public function styleHeader(TableExcel $table): void
    {
        // calculate and set the width of the columns
        // Note: we cannot use autoSize, it kills performance and choke on more than a few hundred cells
        // label columns
        $fromCol = 1;
        $toCol = $table->numLabelCols + 1;
        for ($colIdx = $fromCol; $colIdx < $toCol; $colIdx++) {
            $this->setColWidth($table, $colIdx);
        }
        // value columns
        $fromCol = $table->numLabelCols + 1;
        $toCol = $table->numLabelCols + $table->numValueCols;
        for ($colIdx = $fromCol; $colIdx < $toCol + 1; $colIdx++) {
            $this->setColWidth($table, $colIdx);
        }

        // set text alignment
        $worksheet = $table->getActiveWorksheet();
        $fromRow = 1;
        $fromCol = 1;
        if ($table->caption) {
            $fromRow += $table->numCaptionRows;
        }
        $toRow = $table->getRowIdxBodyAdjusted() - 1;
        $style = $worksheet->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $toRow += array_product($table->rowDims);
        $style = $worksheet->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }

    /**
     * Set the column width.
     * Sets the column width to a fixed width. If $numCells is passed,
     * then spreadsheet autosize is used when the number of cells to render is smaller than self::COL_AUTO_SIZE.
     * @param TableExcel $table
     * @param int $colIdx
     * @return void
     */
    public function setColWidth(TableExcel $table, int $colIdx): void
    {
        $worksheet = $table->getActiveWorksheet();
        $width = $this->calcColWidth($table, $colIdx);
        $colDim = $worksheet->getColumnDimensionByColumn($colIdx);
        $colDim->setWidth($width);
    }

    /**
     * Calculate and set the width of a column by asking the Table for data hints.
     * @param TableExcel $table
     * @param int $colIdx The 1-indexed Excel column position
     * @return int
     */
    protected function calcColWidth(TableExcel $table, int $colIdx): int
    {
        // Calculate the required character width from the JSON-stat.
        $charLength = $table->widthCalculator->calculateLabelWidth($colIdx);

        // Ensure the column is at least as wide as the largest number in the entire table
        // For performance reasons we don't do this for every col separately, but just the max once
        $charLength = max($charLength, $table->widthCalculator->maxValueCharWidth);

        // Add two characters for visual padding.
        $charLength += 2;

        // Enforce the min/max limits defined in the Styler.
        $charLength = max(self::COL_WIDTH_MIN, $charLength);
        $charLength = min($charLength, self::COL_WIDTH_MAX);

        return $charLength;
    }

    /**
     * Style the label cells of the body.
     * Set the alignment of the value cells to left.
     * @param TableExcel $table
     */
    public function styleLabelCellBody(TableExcel $table): void
    {
        $fromRow = $table->getRowIdxBodyAdjusted();
        $toRow = $fromRow + array_product($table->rowDims);
        $fromCol = ($table->numLabelCols === 0 ? 1 : $table->numLabelCols) + 1;
        $toCol = $fromCol + $table->numLabelCols;
        $style = $table->getActiveWorksheet()->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    /**
     * Style the value cells of the body.
     * Set the alignment of the value cells to right.
     * @param TableExcel $table
     */
    public function styleValueCellBody(TableExcel $table): void
    {
        $fromRow = $table->getRowIdxBodyAdjusted();
        $toRow = $fromRow + array_product($table->rowDims);
        $fromCol = ($table->numLabelCols === 0 ? 1 : $table->numLabelCols) + 1;
        $toCol = $table->numLabelCols + $table->numValueCols;
        $style = $table->getActiveWorksheet()->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
}