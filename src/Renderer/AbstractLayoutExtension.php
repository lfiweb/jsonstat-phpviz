<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Renderer\LayoutExtensionInterface;

class AbstractLayoutExtension implements LayoutExtensionInterface
{

    /**
     * @inheritDoc
     */
    public function renderCaption(AbstractTable $table): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function renderFooter(AbstractTable $table): void {}
}