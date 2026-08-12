<?php

namespace jsonstatPhpViz\Renderer;


/**
 * Abstract class to add a custom caption and footer without having to extend the AbstractTable class.
 */
class AbstractLayoutExtension implements LayoutExtensionInterface
{

    /**
     * @inheritDoc
     */
    public function addCaption(AbstractTable $table): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function addFooter(AbstractTable $table): void
    {
    }
}