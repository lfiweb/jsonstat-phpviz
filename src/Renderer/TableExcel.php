<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\Formatter;
use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TableExcel extends AbstractTable
{
    private Spreadsheet $xls;

    public Worksheet $worksheet;

    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        parent::__construct($jsonStatReader, $numRowDim);
        $this->xls = new Spreadsheet();
        $this->worksheet = $this->xls->getActiveSheet();
    }

    /**
     * Instantiate the RendererCell class.
     * @return void
     */
    public function initRendererCell(): void
    {
        $formatter = new FormatterCell($this->reader, new Formatter());
        $this->rendererCell = new CellExcel($formatter, $this->reader, $this);
    }

    /**
     * Render the table in memory.
     * @return string
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
     * Render and download the table.
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
     * Create and insert the caption.
     */
    public function addCaption(): void
    {
        $this->worksheet->setCellValue([1, 1], $this->caption);
        $this->worksheet->mergeCells([1, 1, $this->numLabelCols + $this->numValueCols, 1]);
    }

    /**
     * Set the caption automatically.
     * Sets the caption from the optional JSON-stat label property.
     * @return void
     */
    public function readCaption(): void
    {
        if (property_exists($this->reader->data, 'label')) {
            $this->caption = $this->reader->data->label;
        }
    }

    /**
     * Create the table head and append header cells, row by row to it.
     */
    public function addHeaders(): void
    {
        parent::addHeaders();
        $this->styleHeaders();
    }

    /**
     * Style all the cells of the current worksheet.
     * @return void
     */
    private function styleHeaders(): void
    {
        $numCols = $this->numLabelCols + $this->numValueCols;
        $numRows = 0;
        if ($this->caption) {
            $numRows =+ 1;
        }
        $numRows += $this->numHeaderRows;
        if ($this->noLabelLastDim) {
            --$numRows;
        }
        $style = $this->worksheet->getStyle([1, 2, $numCols, $numRows]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $numRows += array_product($this->rowDims);
        $style = $this->worksheet->getStyle([1, 2, $this->numLabelCols, $numRows]);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        for ($colIdx = 1; $colIdx < $numCols + 1; $colIdx++) {
            $this->worksheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }
        $this->worksheet->setSelectedCell('A1');    // there doesn't seem to be a deselect method
    }
}