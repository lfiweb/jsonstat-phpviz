<?php

namespace jsonstatPhpViz;

interface IRendererTable
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
     * Renders the data as a html table.
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
    public function numRowDimAuto(): int;

}