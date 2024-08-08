<?php

use jsonstatPhpViz\Reader;
use jsonstatPhpViz\Renderer\AbstractTable;
use jsonstatPhpViz\Renderer\StylerExcel;
use jsonstatPhpViz\Renderer\TableExcel;
use jsonstatPhpViz\Renderer\TableHtml;
use jsonstatPhpViz\Renderer\TableTsv;
use PhpOffice\PhpSpreadsheet\Writer\Ods;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Factory to return the renderer.
 * @param Reader $reader
 * @param $format
 * @return TableTsv|TableExcel|TableHtml
 */
function getRenderer(Reader $reader, $format): TableTsv|TableExcel|TableHtml
{
    if ($format === 'tsv') {
        $renderer = new TableTsv($reader);
    } elseif ($format === 'ods' || $format === 'xlsx') {
        $renderer = new TableExcel($reader);
    } else {
        $renderer = new TableHtml($reader);
    }

    return $renderer;
}

/**
 * Render a html download link.
 * @param int $id
 * @return string
 */
function renderDownloadLink(int $id): string
{
    return '<p>download as: <a href="main.php?f=tsv&id='.$id.'">tab separated</a> (tsv) |
            <a href="main.php?f=ods&id='.$id.'">Writer</a> (ods) | 
            <a href="main.php?f=xlsx&id='.$id.'">Excel</a> (xlsx)</p>';
}

/**
 * Download the table.
 * @param AbstractTable|TableExcel $table
 * @param string $format
 * @param string $id
 * @return void
 */
function download(AbstractTable|TableExcel $table, string $format, string $id): void
{
    if (isset($_GET['id']) && $_GET['id'] === $id) {
        if ($format === 'tsv') {
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="table.tsv"');
        }
        if ($format === 'ods') {
            $table->styler = new StylerExcel();
            $table->setWriter(new Ods($table->getSpreadSheet()));
            header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
            header('Content-Disposition: attachment; filename="table.ods"');
        }
        if ($format === 'xlsx') {
            $table->styler = new StylerExcel();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="table.xlsx"');
        }

        try {
            $data = $table->render();
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $exception) {
            $data = $exception->getMessage();
        }
        exit($data);
    }
}

$format = 'html';
if (isset($_GET['f'])) {
    if ($_GET['f'] === 'tsv') {
        $format = 'tsv';
    }
    if ($_GET['f'] === 'ods') {
        $format = 'ods';
    }
    if ($_GET['f'] === 'xlsx') {
        $format = 'xlsx';
    }
}

// Create a table with 4 dimensions of size `[3,2,4,2]` (shape). To dimensions are used to group the rows:
$filename = '../tests/resources/integer.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table1 = getRenderer($reader, $format);
$table1->caption .= ', dimension A and B are used as row dimensions.';
download($table1, $format, '1');

// create the table with 3 dimensions used for the row grouping instead of 2 (default):
$table2 = getRenderer($reader, $format);
$table2->numRowDim = 0;
$table2->caption .= ', dimension A, B and C are used as row dimensions.';
download($table2, $format, '2');

// transpose the table by permutating dimension A with dimension D. Also hide label of dimension A:
$jsonstat2 = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat2);
$axes = [3, 1, 2, 0];
$reader->transpose($axes);
$table3 = getRenderer($reader, $format);
$table3->noLabelLastDim = true;
$table3->caption = 'Integers transposed: dimension A is permutated with Dimension D';
download($table3, $format, '3');

// Real-world example with data from the Swiss NFI having a caption, column units and row totals as well as
// two dimensions of size one excluded from rendering.
$filename = '../tests/resources/volume.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table4 = getRenderer($reader, $format);
$table4->excludeOneDim = true;
$table4->noLabelLastDim = true;
download($table4, $format, '4');

// use JSON-stat OECD test data
$filename = '../tests/resources/oecd.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table6 = getRenderer($reader, $format);
$table6->excludeOneDim = true;
download($table6, $format, '6');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>JSON-stat table renderer</title>
    <link rel="stylesheet" href="main.min.css">
    <link rel="stylesheet" href="table.min.css">
    <script src="main.js" type="module"></script>
</head>
<body class="theme1">
<h1>Demo of rendering JSON-stat as html tables</h1>
<?php
echo $table1->render();
echo renderDownloadLink(1);
echo $table2->render();
echo renderDownloadLink(2);
echo $table3->render();
echo renderDownloadLink(3);
echo $table4->render();
echo renderDownloadLink(4);
echo $table6->render();
echo renderDownloadLink(6);
?>
</body>
</html>