<?php

namespace jsonstatPhpViz\Renderer;

/**
 * Handles rendering of table cells.
 *
 * There are four types of cells to render:
 *
 * |-----------------------------------------------------------|
 * | header label cell | header value cell | header value cell |
 * |===================|===================|===================|
 * |     label cell    |    value cell     |     value cell    |
 * |-------------------|-------------------|-------------------|
 *
 * e.g.:
 *
 * |-----------------------------------------------------------|
 * |    OECD country   |     year 2003     |     year 2004     |
 * |===================|===================|===================|
 * |       Sweden      |    6.56574156     |    7.373480411    |
 * |-------------------|-------------------|-------------------|
 * |     Switzerland   |    4.033356027    |     4.31699694    |
 * |-------------------|-------------------|-------------------|
 * |         ...       |         ...       |         ...       |
 * |-------------------|-------------------|-------------------|
 */
interface CellInterface
{
    /**
     * Add the first cell to the table header.
     * @param int $rowIdx row index
     * @return mixed
     */
    public function addFirstCellHeader(int $rowIdx): mixed;


    /**
     * Add the first cell to the table body
     * @param int $rowIdx row index
     * @return mixed
     */
    public function addFirstCellBody(int $rowIdx): mixed;

    /**
     * Add a lable cell to the table header.
     * @param int $rowIdx row index
     * @param int $dimIdx dimension index
     * @return void
     */
    public function addLabelCellHeader(int $rowIdx, int $dimIdx): void;

    /**
     * Add a label cell to the table body.
     * @param int $rowIdx
     * @param int $dimIdx dimension index
     * @return void
     */
    public function addLabelCellBody(int $rowIdx, int $dimIdx): void;

    /**
     * Add a value cell to the table header.
     * @param int $rowIdx
     * @param int $offset
     * @return void
     */
    public function addValueCellHeader(int $rowIdx, int $offset): void;

    /**
     * Add a value cell to the table body.
     * @param int $offset
     * @return void
     */
    public function addValueCellBody(int $offset): void;

    /**
     * Add the last cell to the table body.
     * @param int $rowIdx
     * @param int $offset
     * @return void
     */
    public function addLastCellBody(int $rowIdx, int $offset): void;
}