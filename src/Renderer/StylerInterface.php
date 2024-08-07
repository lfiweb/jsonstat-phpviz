<?php

namespace jsonstatPhpViz\Renderer;

/**
 * Interface to define methods related to the styling of the table.
 */
interface StylerInterface
{

    /**
     * Style the table.
     * @param TableInterface $table
     * @return void
     */
    public function style(TableInterface $table): void;


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