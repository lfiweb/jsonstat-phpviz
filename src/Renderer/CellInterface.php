<?php

namespace jsonstatPhpViz\Renderer;

interface CellInterface
{
    public function firstCell(int $dimIdx, int $rowIdx): void;

    public function labelCell(int $dimIdx, int $rowIdx): void;

    public function valueCell(int $offset): void;

    public function lastCell(int $offset, int $rowIdx): void;
}