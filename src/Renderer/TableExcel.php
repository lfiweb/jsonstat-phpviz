<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TableExcel extends TableArray
{

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

    public function xxxrows(): void
    {
        parent::rows();
        $spreadsheet = $this->xls->getActiveSheet();
        foreach ($this->data as $x => $row) {
            foreach ($row as $y => $val) {
                $spreadsheet->setCellValue([$y + 1, $x + 1], $val);
            }
        }
    }


    public function build(): void
    {
        parent::build();
        $spreadsheet = $this->xls->getActiveSheet();
        foreach ($this->data as $x => $row) {
            foreach ($row as $y => $val) {
                $spreadsheet->setCellValue([$y + 1, $x + 1], $val);
            }
        }

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
    public function xxxcaption(): void
    {
        $this->xls->getActiveSheet()->setCellValue([1, 1], $this->caption);
    }
}