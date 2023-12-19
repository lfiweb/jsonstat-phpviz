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

    public function addFirstCellBody(int $dimIdx, int $rowIdx)
    {
        // TODO: Implement firstCell() method.
    }

    public function addLabelCellBody(int $rowIdx, int $dimIdx)
    {
        // TODO: Implement labelCell() method.
    }

    public function addValueCellBody(int $offset)
    {
        // TODO: Implement valueCell() method.
    }

    public function addLastCellBody(int $rowIdx, int $offset)
    {
        // TODO: Implement lastCell() method.
    }
}