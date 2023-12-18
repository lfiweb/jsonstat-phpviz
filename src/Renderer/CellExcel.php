<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;

class CellExcel implements CellInterface
{
    public function xxxlabelCells(int $rowIdx): void
    {
        parent::labelCells($rowIdx);
        $spreadsheet = $this->table->xls;
        foreach ($this->table->data[$rowIdx+ $this->table->numHeaderRows] as $y => $val) {
            $spreadsheet->getActiveSheet()->setCellValue([$y + 1, $rowIdx + 1], $val);
        }
    }

    public function firstCell(int $dimIdx, int $rowIdx)
    {
        // TODO: Implement firstCell() method.
    }

    public function labelCell(int $dimIdx, int $rowIdx)
    {
        // TODO: Implement labelCell() method.
    }

    public function valueCell(int $offset)
    {
        // TODO: Implement valueCell() method.
    }

    public function lastCell(int $offset, int $rowIdx)
    {
        // TODO: Implement lastCell() method.
    }
}