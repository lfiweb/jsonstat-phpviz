<?php

namespace jsonstatPhpViz\Renderer;

/**
 * Defines the strategy for handling data iteration and offset filtering
 * during the JSON-stat table rendering loop.
 */
abstract class AbstractOffsetStrategy
{
    /**
     * Triggered immediately before the data at the current offset is processed into cells.
     *
     * Use this to initialize state, set up row-level flags, or prepare
     * formatting before the data at the current offset is rendered.
     *
     * @param int $offset The current array offset of the JSON-stat values.
     * @param int $rowIdx The current vertical row index being rendered.
     * @param AbstractTable $table
     * @return void
     */
    public function beforeAddCells(int $offset, int $rowIdx, AbstractTable $table): void {}

    /**
     * Determines whether the cell at the current offset should be physically rendered.
     *
     * Use this to implement custom filtering, such as skipping specific cells.
     *
     * @param int $offset The current array offset of the JSON-stat values.
     * @param AbstractTable $table
     * @return bool True if the cell should be rendered, false to skip rendering.
     */
    abstract public function shouldRenderOffset(int $offset, AbstractTable $table): bool;

    /**
     * Triggered immediately after the data at the current offset has been processed.
     *
     * Use this to execute side effects, such as calculating CSS classes,
     * injecting DOM attributes, or cleaning up state.
     *
     * @param int $offset The current array offset of the JSON-stat values.
     * @param int $rowIdx The current vertical row index being rendered.
     * @param AbstractTable $table
     * @return void
     */
    public function afterAddCells(int $offset, int $rowIdx, AbstractTable $table): void {}

    /**
     * Determines whether the vertical row index should increment at the end of a data row.
     *
     * This hook is evaluated when the loop reaches the end of the defined column span.
     * Use this to freeze the row index if the preceding row was completely skipped.
     *
     * @param int $offset The current array offset at the end of the column span.
     * @param AbstractTable $table
     * @return bool True to increment the row index, false to maintain the current index.
     */
    abstract public function shouldAdvanceRow(int $offset, AbstractTable $table): bool;
}