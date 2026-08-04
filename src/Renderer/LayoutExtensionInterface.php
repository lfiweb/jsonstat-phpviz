<?php

namespace jsonstatPhpViz\Renderer;

/**
 * Defines hooks to extend the rendering lifecycle (cell, caption, and footer) of the table.
 */
interface LayoutExtensionInterface
{
    /**
     * Hook to render a custom caption before headers are built.
     */
    public function renderCaption(AbstractTable $table): bool;

    /**
     * Hook to render a custom footer after all rows are built.
     */
    public function renderFooter(AbstractTable $table): void;
}