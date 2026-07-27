<?php

namespace jsonstatPhpViz\Renderer;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
    protected Worksheet $worksheet;

    public function __construct(protected TableExcel $table)
    {
    }

    /**
     * Style the Excel spreadsheet.
     */
    public function style(): void
    {
        $this->worksheet = $this->table->getActiveWorksheet();
        $this->styleCaption();
        $this->styleHeader();
        $this->styleLabelCellBody();
        $this->styleValueCellBody();
        $this->worksheet->setSelectedCell('A1');    // there doesn't seem to be a deselect method
    }

    /**
     * Style the caption cells of the current worksheet.
     * @return void
     */
    public function styleCaption(): void
    {
        $this->worksheet->getRowDimension(1)->setRowHeight(24);
        $style = $this->worksheet->getStyle([1, 1, 1, 1]);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }

    /**
     * Style the header cells of the current worksheet.
     * @return void
     */
    public function styleHeader(): void
    {
        $table = $this->table;
        // calculate and set the width of the columns
        // Note: we cannot use autoSize, it kills performance and choke on more than a few hundred cells
        // label columns
        $fromCol = 1;
        $toCol = $table->numLabelCols + 1;
        for ($colIdx = $fromCol; $colIdx < $toCol; $colIdx++) {
            $this->setColWidth($colIdx);
        }
        // value columns
        $fromCol = $table->numLabelCols + 1;
        $toCol = $table->numLabelCols + $table->numValueCols;
        for ($colIdx = $fromCol; $colIdx < $toCol + 1; $colIdx++) {
            $this->setColWidth($colIdx);
        }

        // set text alignment
        $fromRow = 1;
        $fromCol = 1;
        if ($table->caption) {
            $fromRow += $table->numCaptionRows;
        }
        $toRow = $table->getRowIdxBodyAdjusted() - 1;
        $style = $this->worksheet->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $toRow += $table->numRenderedBodyRows;
        $style = $this->worksheet->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }

    /**
     * Set the column width.
     * Sets the column width to a fixed width. If $numCells is passed,
     * then spreadsheet autosize is used when the number of cells to render is smaller than self::COL_AUTO_SIZE.
     * @param int $colIdx
     * @return void
     */
    public function setColWidth(int $colIdx): void
    {
        $width = $this->calcColWidth($colIdx);
        $colDim = $this->worksheet->getColumnDimensionByColumn($colIdx);
        $colDim->setWidth($width);
    }

    /**
     * Calculate and set the width of a column by asking the Table for data hints.
     * @param int $colIdx The 1-indexed Excel column position
     * @return int
     */
    protected function calcColWidth(int $colIdx): int
    {
        $calculator = $this->table->widthCalculator;

        // Calculate the required character width from the JSON-stat.
        $charLength = $calculator->calculateLabelWidth($colIdx);

        // Ensure the column is at least as wide as the largest number in the column
        if ($colIdx > $this->table->numLabelCols) {
            $dataColIdx = $colIdx - $this->table->numLabelCols - 1;
            $dataMaxWidth = $calculator->maxValueCharWidths[$dataColIdx];
            $charLength = max($charLength, $dataMaxWidth);
        }

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
     */
    public function styleLabelCellBody(): void
    {
        $table = $this->table;
        $fromRow = $table->getRowIdxBodyAdjusted();
        $toRow = $fromRow + array_product($table->rowDims);
        $fromCol = ($table->numLabelCols === 0 ? 1 : $table->numLabelCols) + 1;
        $toCol = $fromCol + $table->numLabelCols;
        $style = $this->worksheet->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    /**
     * Style the value cells of the body.
     * Set the alignment of the value cells to right.
     */
    public function styleValueCellBody(): void
    {
        $table = $this->table;
        $fromRow = $table->getRowIdxBodyAdjusted();
        $toRow = $fromRow + array_product($table->rowDims);
        $fromCol = ($table->numLabelCols === 0 ? 1 : $table->numLabelCols) + 1;
        $toCol = $table->numLabelCols + $table->numValueCols;
        $style = $this->worksheet->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
}