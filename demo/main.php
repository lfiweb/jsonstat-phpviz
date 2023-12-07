<?php

use jsonstatPhpViz\Reader;

require_once __DIR__.'/../vendor/autoload.php';

function getRenderer(Reader $reader, $format): \jsonstatPhpViz\Tsv\RendererTable|\jsonstatPhpViz\Html\RendererTable
{
    if ($format === 'tsv') {
        $renderer = new \jsonstatPhpViz\Tsv\RendererTable($reader);
    } else {
        $renderer = new \jsonstatPhpViz\Html\RendererTable($reader);
    }
    return $renderer;
}

function download(string $table, string $format, int $id)
{
    if ($format === 'html') {
        return '<p>download as: <a href="main.php?f=tsv&id='.$id.'">tab separated (tsv)</a></p>';
    }

    if ($format === 'tsv' && (int)$_GET['id'] === $id) {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="table'.$id.'.tsv"');
        echo $table;
        exit;
    }

}

$format = 'html';
if (isset($_GET['f']) && $_GET['f'] === 'tsv') {
    $format = 'tsv';
}


// Render a table with 4 dimensions of size `[3,2,4,2]` (shape). To dimensions are used to group the rows:
$filename = '../tests/resources/integer.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table = getRenderer($reader, $format);
$table->caption .= ', dimension A and B are used as row dimensions.';
$html1 = $table->render();
$html1 .= download($html1, $format, 1);


// Render the table with 3 dimensions used for the row grouping instead of 2 (default):
$table = getRenderer($reader, $format);
$table->caption .= ', dimension A, B and C are used as row dimensions.';
$html2 = $table->render();
$html2 .= download($html2, $format, 2);

// Transpose table by permutating dimension A with dimension D. Also hide label of dimension A:
$axes = [3, 1, 2, 0];
$reader->transpose($axes);
$table = getRenderer($reader, $format);
$table->noLabelLastDim = true;
$table->caption = 'Integers transposed: dimension A is permutated with Dimension D';
$html3 = $table->render();
$html3 .= download($html3, $format, 3);

// Real-world example with data from the Swiss NFI having a caption, column units and row totals as well as
// two dimensions of size one excluded from rendering.
$filename = '../tests/resources/volume.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table = getRenderer($reader, $format);
$table->excludeOneDim = true;
$table->noLabelLastDim = true;
$html4 = $table->render();
$html4 .= download($html4, $format, 4);
// render as csv
$reader = new Reader($jsonstat);
$table = new \jsonstatPhpViz\Tsv\RendererTable($reader);
$table->excludeOneDim = true;
$table->separatorCol = ',';
$html6 = nl2br($table->render());

// JSON-stat OECD testdata
$filename = '../tests/resources/oecd.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table = getRenderer($reader, $format);
$table->excludeOneDim = true;
$html5 = $table->render();
$html5 .= download($html5, $format, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>JSON-stat table renderer</title>
    <link rel="stylesheet" href="main.min.css">
    <link rel="stylesheet" href="table.min.css">
</head>
<body class="theme1">
<h1>Demo of rendering JSON-stat as html tables</h1>
<?=
$html1.
$html2.
$html3.
$html4.
$html5.
'<code>'.$html6.'</code>'
?>
</body>
</html>