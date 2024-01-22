<?php

namespace jsonstatPhpViz\Renderer;

use PhpOffice\PhpSpreadsheet\Style\Alignment;

interface StylerInterface
{

    /**
     * Style the table.
     * @param TableInterface $table
     * @return TableExcel
     */
    public function style(TableInterface $table): TableExcel;


    /**
     * Style the caption.
     * @param TableInterface $table
     * @return void
     */
    public function styleCaption(TableInterface $table): void;


    /**
     * Style the header cells of the current worksheet.
     * @param TableInterface $table
     * @return void
     */
    public function styleHeader(TableInterface $table): void;


    /**
     * Style the label cells of the body.
     * Set the alignment of the value cells to left.
     * @param TableExcel $table
     */
    public function styleLabelCellBody(TableInterface $table): void;


    /**
     * Style the value cells of the body.
     * Set the alignment of the value cells to right.
     * @param TableExcel $table
     */
    public function styleValueCellBody(TableExcel $table): void;
}