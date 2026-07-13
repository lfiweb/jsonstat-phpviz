<?php

namespace jsonstatPhpViz\Renderer;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Apply styles to the worksheet.
 */
class StylerExcel implements StylerInterface
{
    public const CELL_WIDTH_DEFAULT = 8;
    public const CELL_WIDTH_MAX = 24;
    public const CELL_AUTO_SIZE = 100;


    /**
     * Style the Excel.
     * @param TableInterface|TableExcel $table
     */
    public function style(TableInterface|TableExcel $table): void
    {
        $this->styleCaption($table);
        $this->styleHeader($table);
        $this->styleLabelCellBody($table);
        $this->styleValueCellBody($table);
        $table->getActiveWorksheet()->setSelectedCell('A1');    // there doesn't seem to be a deselect method
    }

    /**
     * Style the caption cells of the current worksheet.
     * @param TableInterface|TableExcel $table
     * @return void
     */
    public function styleCaption(TableInterface|TableExcel $table): void
    {
        $worksheet = $table->getActiveWorksheet();
        $worksheet->getRowDimension(1)->setRowHeight(24);
        $style = $worksheet->getStyle([1, 1, 1, 1]);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }

    /**
     * Style the header cells of the current worksheet.
     * @param TableInterface|TableExcel $table
     * @return void
     */
    public function styleHeader(TableInterface|TableExcel $table): void
    {
        $worksheet = $table->getActiveWorksheet();
        $numCells = array_product($table->shape);

        // calculate and set the width of the columns
        // Note: we cannot use autoSize, it kills performance and choke on more than a few hundred cells
        // label columns
        $fromRow = $table->getRowIdxBodyAdjusted() - 1;
        $fromCol = 1;
        $toCol = $table->numLabelCols + 1;
        for ($colIdx = $fromCol; $colIdx < $toCol; $colIdx++) {
            $colWidth = $this->calcSetColWidth($worksheet, $colIdx, $fromRow);
            if ($numCells < self::CELL_AUTO_SIZE) {
                $worksheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
            } else {
                $worksheet->getColumnDimensionByColumn($colIdx)->setWidth($colWidth);
            }
        }
        // value columns
        --$fromRow;
        $fromCol = $table->numLabelCols + 1;
        $toCol = $table->numLabelCols + $table->numValueCols;
        for ($colIdx = $fromCol; $colIdx < $toCol + 1; $colIdx++) {
            $colWidth = $this->calcSetColWidth($worksheet, $colIdx, $fromRow);
            if ($numCells < self::CELL_AUTO_SIZE) {
                $worksheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
            } else {
                $worksheet->getColumnDimensionByColumn($colIdx)->setWidth($colWidth);
            }
        }

        // set text alignment
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
     * Style the label cells of the body.
     * Set the alignment of the value cells to left.
     * @param TableInterface|TableExcel $table
     */
    public function styleLabelCellBody(TableInterface|TableExcel $table): void
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
     * @param TableInterface|TableExcel $table
     */
    public function styleValueCellBody(TableInterface|TableExcel $table): void
    {
        $fromRow = $table->getRowIdxBodyAdjusted();
        $toRow = $fromRow + array_product($table->rowDims);
        $fromCol = ($table->numLabelCols === 0 ? 1 : $table->numLabelCols) + 1;
        $toCol = $table->numLabelCols + $table->numValueCols;
        $style = $table->getActiveWorksheet()->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    /**
     * Calculate and set the width of a column
     * The width of the column is calculated using the number of characters
     * in the cell and adds two characters padding.
     * @param Worksheet $worksheet
     * @param int $x
     * @param int $y
     * @return int
     */
    protected function calcSetColWidth(Worksheet $worksheet, int $x, int $y): int
    {
        $cellValue = $worksheet->getCell([$x, $y])->getValue();
        if (empty($cellValue)) {
            $colWidth = self::CELL_WIDTH_DEFAULT;
        } else {
            // Measure string + 2 chars for visual padding, ensuring a minimum default width
            $length = mb_strlen((string)$cellValue) + 2;
            $colWidth = max(self::CELL_WIDTH_DEFAULT, $length);
            $colWidth = min($colWidth, self::CELL_WIDTH_MAX);
        }

        return $colWidth;
    }
}