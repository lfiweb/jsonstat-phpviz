<?php

namespace jsonstatPhpViz\Renderer;

use jsonstatPhpViz\FormatterCell;
use jsonstatPhpViz\LabelWidthCalculator;
use jsonstatPhpViz\Reader;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    /*
     * the writer used for rendering (saving), defaults to Xlsx.
     */
    /**
     * an instance of the PhpSpreadsheet
     * @var Spreadsheet
     */
    private Spreadsheet $xls;
    /**
     * the current worksheet of the PhpSpreadsheet
     * @var Worksheet
     */
    private Worksheet $worksheet;
    private IWriter $writer;
    public LabelWidthCalculator $widthCalculator;

    /**
     * Constructs the class instance and sets some (internal) default properties.
     * @param Reader $jsonStatReader
     * @param int|null $numRowDim
     */
    public function __construct(Reader $jsonStatReader, ?int $numRowDim = null)
    {
        parent::__construct($jsonStatReader, $numRowDim);
        $this->xls = new Spreadsheet();
        $this->worksheet = $this->xls->getActiveSheet();
        $this->writer = new Xlsx($this->xls);
    }

    /**
     * Set the writer to be used when rendering the output.
     * @param IWriter $writer
     *
     * @return void
     */
    public function setWriter(IWriter $writer): void
    {
        $this->writer = $writer;
    }

    /**
     * Render the table in memory.
     * Writes the file temporarily to disk or memory and then returns it as a binary string.
     * @return string binary, zipped string
     * @throws Exception
     */
    public function render(): string
    {
        $this->build();
        $this->styler?->style($this);

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
     * Save the spreadsheet to the system's temp directory and return it as a binary string.
     *
     * ARCHITECTURE NOTE:
     * PhpSpreadsheet relies on PHP's native ZipArchive extension to build .xlsx and .ods
     * files. ZipArchive is strictly designed to write to a physical POSIX file path.
     * Attempting to force it to write to a stream wrapper like 'php://memory' causes
     * severe CPU thrashing and memory reallocation loops.
     * To bypass this while maintaining a high-performance, stateless design, we write
     * to sys_get_temp_dir(). In modern Linux environments, /tmp is typically mounted
     * as a 'tmpfs' RAM disk. This ensures the operation remains a fast,
     * pure memory-to-memory transfer with zero physical disk I/O. The temporary
     * file is instantly unlinked after reading to prevent memory bloat.
     *
     * @return string binary, zipped string
     * @throws Exception
     */
    protected function getBinaryContent(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpviz_');
        try {
            $this->writer->save($tempFile);
            $content = file_get_contents($tempFile);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        return $content;
    }

    /**
     * Create and insert the caption.
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function addCaption(): void
    {
        $this->worksheet->setCellValueExplicit([1, 1], $this->caption, DataType::TYPE_STRING);
        $this->worksheet->mergeCells([1, 1, $this->numLabelCols + $this->numValueCols, 1]);
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
     * Return the spreadsheet.
     * @return Spreadsheet
     */
    public function getSpreadSheet(): Spreadsheet
    {
        return $this->xls;
    }

    /**
     * Return the active worksheet.
     * @return Worksheet
     */
    public function getActiveWorksheet(): Worksheet
    {
        return $this->worksheet;
    }


    /**
     * Return a new instance of the cell renderer.
     * @return CellInterface
     */
    protected function newCellRenderer(): CellInterface
    {
        $formatter = new FormatterCell($this->reader);
        return new CellExcel($formatter, $this->reader, $this);
    }
}