<?php

use jsonstatPhpViz\src\Reader;
use jsonstatPhpViz\src\RendererTable;

require_once __DIR__.'/../vendor/autoload.php';

// Render a table with 4 dimensions of size `[3,2,4,2]` (shape). To dimensions are used to group the rows:
$filename = 'integer.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table = new RendererTable($reader);
$table->caption.= ', dimension A and B used as row dimensions';
$html = $table->render();

// Render the table with 3 dimensions used for the row grouping instead of 2 (default):
$table = new RendererTable($reader, 3);
$table->caption = 'Integers transposed: dimension A is swapped with Dimension C';
$html2 = $table->render();

// Transpose table by permutating dimension A with dimension D. Also hide label of dimension A:
$axes = [3, 1, 2, 0];
$reader->transpose($axes);
$table = new RendererTable($reader);
$table->noLabelLastDim = true;
$table->caption = 'Integers transposed: dimension A is permutated with Dimension D';
$html3 = $table->render();

// Real-world example with data from the Swiss NFI having a caption, column units and row totals as well as
// two dimensions of size one excluded from rendering.
$filename = 'volume.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table = new RendererTable($reader);
$table->excludeOneDim = true;
$table->noLabelLastDim = true;
$html4 = $table->render();

// JSON-stat testdata
$filename = '../tests/oecd.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table = new RendererTable($reader, 2);
$html5 = $table->render();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>JSON-stat table renderer</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="table.css">
</head>
<body class="theme1">
<h1>Demo of rendering JSON-stat as html tables</h1>
<?php echo $html; ?>
<p><br><br></p>
<?php echo $html2; ?>
<p><br><br></p>
<?php echo $html3; ?>
<p><br><br></p>
<?php echo $html4; ?>
<p><br><br></p>
<?php echo $html5; ?>
</body>
</html>
