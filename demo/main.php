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
$table->caption = $table->caption.', dimension A and B used as row dimensions';
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



$filename = 'volume.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
//$reader->transpose([2,3,0,1,4,5]);
$table = new RendererTable($reader, 4);
$table->noLabelLastDim = true;
$table->excludeOneDim = false;
$html4 = $table->render();
/*
$filename = 'sizeone.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table = new RendererTable($reader);
$table->excludeOneDim = true;
$html5 = $table->render();*/

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
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
