<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;

class CellExcel extends CellArray
{
    public function xxxlabelCells(int $rowIdx): void
    {
        parent::labelCells($rowIdx);
        $spreadsheet = $this->table->xls;
        foreach ($this->table->data[$rowIdx+ $this->table->numHeaderRows] as $y => $val) {
            $spreadsheet->getActiveSheet()->setCellValue([$y + 1, $rowIdx + 1], $val);
        }
    }
}