<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Reader;

/**
 * Handles rendering of the table.
 */
interface TableInterface
{
    /**
     * Instantiates the class.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim number of row dimensions
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null);

    /**
     * Set the number of dimensions to be used for rows.
     * @param int $numRowDim number of row dimensions
     */
    public function setNumRowDim(int $numRowDim): void;

    /**
     * Renders the data as an HTML table.
     * Reads the value array and renders it as a table.
     * @return string
     */
    public function render(): string;

    /**
     * Returns the default number of dimensions used for rendering rows.
     * By default, a table is rendered using all dimensions for rows
     * except the last two dimensions are used for columns. When there are fewer than three dimensions,
     * only the first dimension is used for rows.
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
     * Sets the caption from the optional JSON-stat label property.
     * @return void
     */
    public function readCaption(): void;

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