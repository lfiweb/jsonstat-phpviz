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
    protected Spreadsheet $xls;
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
    public function addCaption(): void
    {
        $this->worksheet->setCellValue([1, 1], $this->caption);
    }

    /**
     * Automatically sets the caption.
     * Sets the caption from the optional JSON-stat label property. HTML from the JSON-stat is escaped.
     * @return void
     */
    public function readCaption(): void
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
    public function addHeaders(): void
    {
        parent::addHeaders();
        $this->styleHeaders();
    }

    /**
     * Style all the cells of the current worksheet.
     * @return void
     */
    private function styleHeaders()
    {
        $numCols = $this->numLabelCols + $this->numValueCols;
        $numRows = $this->numHeaderRows;
        if ($this->noLabelLastDim) {
            --$numRows;
        }
        $style = $this->worksheet->getStyle([1, 1, $numCols, $numRows]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $numRows += array_product($this->rowDims);
        $style = $this->worksheet->getStyle([1, 1, $this->numLabelCols, $numRows]);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        for ($colIdx = 1; $colIdx < $numCols + 1; $colIdx++) {
            $this->worksheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }
        $this->worksheet->setSelectedCell('A1');    // there seems not to be a deselect method
    }
}