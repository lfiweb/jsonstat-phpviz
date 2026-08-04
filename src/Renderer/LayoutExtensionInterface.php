<?php

namespace jsonstatPhpViz\Renderer;

/**
 * Defines hooks to extend the rendering lifecycle (cell, caption, and footer) of the table.
 */
interface LayoutExtensionInterface
{
    /**
     * Hook to add a custom caption before headers are built.
     * @return bool True if a custom caption was added, false to fall back to the default caption property if set.
     */
    public function addCaption(AbstractTable $table): bool;

    /**
     * Hook to add a custom footer after all rows are built.
     */
    public function addFooter(AbstractTable $table): void;
}