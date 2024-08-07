<?php

namespace jsonstatPhpViz\Renderer;

use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Apply styles to the worksheet.
 */
class StylerExcel implements StylerInterface
{

    /**
     * Apply styles before setting cell content.
     * @param TableExcel $table
     * @return void
     */
    public function styleInitial(TableExcel $table): void
    {
        $worksheet = $table->getActiveWorksheet();
        $toCol =  array_product($table->reader->getDimensionSizes());   // note: table properties such as table->shape are not initialized yet
        for ($colIdx = 1; $colIdx < $toCol + 1; $colIdx++) {
            $worksheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }
    }

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
        $fromCol = 1;
        $fromRow = 1;
        if ($table->caption) {
            $fromRow += $table->numCaptionRows;
        }
        $toCol = $table->numLabelCols + $table->numValueCols;
        $toRow = $table->getRowIdxBodyAdjusted() - 1;
        $style = $worksheet->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $toRow += array_product($table->rowDims);
        $style = $worksheet->getStyle([$fromCol, $fromRow, $toCol, $toRow]);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        /*for ($colIdx = 1; $colIdx < $toCol + 1; $colIdx++) {
            $worksheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }*/
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
}