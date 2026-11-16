<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\LabelWidthCalculator;
use jsonstatPhpViz\Reader;
use SpreadsheetWriter\Exception\ExceptionInterface;
use SpreadsheetWriter\Sheet;
use SpreadsheetWriter\Workbook;
use SpreadsheetWriter\Writer\WriterInterface;
use SpreadsheetWriter\Writer\Xlsx;

use function array_slice;
use function count;

/**
 * @see TableHtml
 */
class TableExcel extends AbstractTable
{
    /**
     * if this instance is set, it's style method will be called
     * after building the table when rendering it.
     * @var StylerExcel|null
     */
    public ?StylerExcel $styler = null;
    /**
     * number of rows used for the caption
     * @var int
     */
    public int $numCaptionRows = 0;

    /**
     * the workbook holding the sheet
     * @var Workbook
     */
    protected Workbook $workbook;
    /**
     * the sheet of the workbook
     * @var Sheet
     */
    protected Sheet $sheet;
    /**
     * the writer used for rendering, defaults to Xlsx
     * @var WriterInterface
     */
    private WriterInterface $writer;
    public LabelWidthCalculator $widthCalculator;

    /**
     * Constructs the class instance and sets some (internal) default properties.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        parent::__construct($jsonStatReader, $numRowDim);
        $this->workbook = new Workbook();
        $this->sheet = $this->workbook->getActiveSheet();
        $this->writer = new Xlsx();
    }

    /**
     * Set the writer to be used when rendering the output, e.g. new Ods().
     * @param WriterInterface $writer
     *
     * @return void
     */
    public function setWriter(WriterInterface $writer): void
    {
        $this->writer = $writer;
    }

    /**
     * Render the table in memory.
     * Builds and styles the table and then returns the file as a binary string.
     * @return string binary, zipped string
     * @throws ExceptionInterface
     */
    public function render(): string
    {
        $this->build();
        $this->styler?->style();

        return $this->getBinaryContent();
    }

    /**
     * Precalculate and cache often used numbers before rendering.
     * @return void
     */
    protected function init(): void
    {
        parent::init();
        $numSkippedSizeOneDims = count($this->reader->data->size) - count($this->shape);
        $numColDims = count($this->colDims);
        $colStrides = array_slice($this->strides, -$numColDims);
        $this->widthCalculator = new LabelWidthCalculator(
            $this->reader,
            $this->numLabelCols,
            $this->numValueCols,
            $this->colDims,
            $numSkippedSizeOneDims,
            $colStrides,
            $this->noLabelLastDim
        );
    }


    /**
     * Return the spreadsheet file as a binary string.
     * The file is created in memory by the writer, no temp file is used.
     * @return string binary, zipped string
     * @throws ExceptionInterface
     */
    public function getBinaryContent(): string
    {
        return $this->writer->write($this->workbook);
    }

    /**
     * Create and insert the caption.
     * @throws ExceptionInterface
     */
    public function addCaption(): void
    {
        $this->sheet->setString(1, 1, $this->caption);
        $this->sheet->mergeCells(1, 1, $this->numLabelCols + $this->numValueCols, 1);
        ++$this->numCaptionRows;
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
     * Return the row index of the first body row.
     * This returns the row index adjusted by the caption and header rows.
     * It returns the index of the first body row after the header rows.
     * @return int
     */
    public function getRowIdxBodyAdjusted(): int
    {
        $numRows = 0;
        if ($this->caption) {
            $numRows += $this->numCaptionRows;
        }
        $numRows += $this->numHeaderRows;

        return $numRows + 1;
    }

    /**
     * Return the workbook.
     * @return Workbook
     */
    public function getWorkbook(): Workbook
    {
        return $this->workbook;
    }

    /**
     * Return the sheet of the workbook.
     * @return Sheet
     */
    public function getActiveSheet(): Sheet
    {
        return $this->sheet;
    }


    /**
     * Return a new instance of the cell renderer.
     * @return CellInterface
     */
    protected function newCellRenderer(): CellInterface
    {
        return new CellExcel($this);
    }
}