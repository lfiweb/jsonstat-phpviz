<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Renderer\LayoutExtensionInterface;

class AbstractLayoutExtension implements LayoutExtensionInterface
{

    /**
     * @inheritDoc
     */
    public function addCaption(AbstractTable $table): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function addFooter(AbstractTable $table): void {}
}