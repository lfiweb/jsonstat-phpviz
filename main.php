<?php

use jsonstatPhpViz\src\Reader;
use jsonstatPhpViz\src\RendererTable;
use jsonstatPhpViz\src\UtilArray;

require_once __DIR__.'/vendor/autoload.php';


$filename = 'integer.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$dims = $reader->getDimensionSizes();
$table = new RendererTable($reader, 2);
$html = $table->render();

$axes = [2, 1, 0, 3];
$reader->transpose($axes);
$numRowDim = 2;
$table = new RendererTable($reader, $numRowDim);
$html2 = $table->render();

$filename = 'integer-transposed.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$dims = $reader->getDimensionSizes();
$numRowDim = count(array_slice($dims, 0, count($dims) - 2));
$table = new RendererTable($reader, $numRowDim);
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

$filename = 'sizeone.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new Reader($jsonstat);
$table = new RendererTable($reader);
$table->excludeOneDim = true;
$html5 = $table->render();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="table.css">
</head>
<body class="theme1 lfi4">

<form><label>render with rowspans<input id="fldUseRowSpans" type="checkbox"></label><br>
    <label>select source<select id="source" autocomplete="off">
            <option value="vorrat.json">Vorrat</option>
            <option value="stammzahl.json">Stammzahl</option>
            <option value="waldflaeche.json">Waldfläche</option>
            <option value="jungwald.json">Jungwald</option>
            <option value="https://json-stat.org/samples/oecd.json">OECD</option>
            <option value="https://json-stat.org/samples/canada.json">Canada</option>
            <option value="https://json-stat.org/samples/galicia.json">Galicia</option>
        </select></label><label>row dimensions<select id="numDim" autocomplete="off">
            <option value="0">0</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
        </select></label></form>
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
