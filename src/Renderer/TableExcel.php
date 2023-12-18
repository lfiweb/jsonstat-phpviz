<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use jsonstatPhpViz\UtilHtml;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TableExcel extends AbstractTable
{

    public bool $repeatLabels = false;

    public Spreadsheet $xls;

    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        parent::__construct($jsonStatReader, $numRowDim);
        $this->xls = new Spreadsheet();
    }

    public function initRendererCell(): void
    {
        $formatter = new FormatterCell($this->reader, new Formatter());
        $this->rendererCell = new CellExcel($formatter, $this->reader, $this);
    }

    /**
     * @throws Exception
     */
    public function render(): string
    {
        $this->build();
        $writer = new Xlsx($this->xls);
        $fp = fopen('php://memory', 'rwb');
        $writer->save($fp);
        rewind($fp);
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, 8000);
        }
        fclose($fp);
        return $content;
    }

    /**
     * @throws Exception
     */
    public function download(): void
    {
        $this->build();
        $writer = new Xlsx($this->xls);
        $fp = fopen('php://memory', 'rwb');
        $writer->save($fp);
        rewind($fp);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="table.xlsx"');
        while (!feof($fp)) {
            echo fread($fp, 8000);
        }
        fclose($fp);
        exit();
    }

    /**
     * Creates and inserts a caption.
     */
    public function caption(): void
    {
        $this->xls->getActiveSheet()->setCellValue([1, 1], $this->caption);
    }

    /**
     * Automatically sets the caption.
     * Sets the caption from the optional JSON-stat label property. HTML from the JSON-stat is escaped.
     * @return void
     */
    public function initCaption(): void
    {
        // since html content is allowed in caption when the property is set explicitly,
        // we have to escape it when set via json-stat to prevent html content from the untrusted source
        if (property_exists($this->reader->data, 'label')) {
            $this->caption = $this->reader->data->label;
        }
    }


    /**
     * Creates the table head and appends header cells, row by row to it.
     */
    public function headers(): void
    {
        for ($rowIdx = 0; $rowIdx < $this->numHeaderRows; $rowIdx++) {
            if (!$this->noLabelDim || $rowIdx % 2 === 1) {
                $this->rendererCell->headerLabelCells($rowIdx);
                $this->rendererCell->headerValueCells($rowIdx);
            }
        }
    }
}