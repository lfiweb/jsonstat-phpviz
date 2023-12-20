<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Reader;

interface TableInterface
{
    /**
     * Instantiates the class.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null);

    /**
     * Set the number of dimensions to be used for rows.
     * @param int $numRowDim
     */
    public function setNumRowDim(int $numRowDim): void;

    /**
     * Renders the data as an html table.
     * Reads the value array and renders it as a table.
     * @return string csv
     */
    public function render(): string;

    /**
     * Returns the default number of dimensions used for rendering rows.
     * By default, a table is rendered using all dimensions for rows expect the last two dimensions are used for columns.
     * When there are fewer than 3 dimensions, only the first dimension is used for rows.
     * @return int
     */
    public function getNumRowDimAuto(): int;

    /**
     * Creates the internal structure of the table.
     * @return void
     */
    public function build(): void;

    /**
     * Automatically sets the caption.
     * Sets the caption from the optional JSON-stat label property. HTML from the JSON-stat is escaped.
     * @return void
     */
    public function readCaption(): void;

    /**
     * Instantiate the RendererCell class.
     * @return void
     */
    public function initRendererCell(): void;

    /**
     * Creates the table body and appends table cells row by row to it.
     */
    public function addRows(): void;

    /**
     * Creates the table head and appends header cells, row by row to it.
     */
    public function addHeaders(): void;

    /**
     * Creates and inserts a caption.
     */
    public function addCaption(): void;
}