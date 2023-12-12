<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;

class CellExcel
{
    protected Reader $reader;
    protected FormatterCell $formatter;
    protected TableArray $table;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     * @param TableExcel $rendererTable
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader, TableExcel $rendererTable)
    {
        $this->reader = $reader;
        $this->formatter = $cellFormatter;
        $this->table = $rendererTable;
    }
}
