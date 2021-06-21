<?php


use jsonstatPhpViz\JsonStatReader;
use jsonstatPhpViz\RendererTable;

require_once 'vendor/autoload.php';

$filename = 'vorrat.json';
//$filename = 'basalarea.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
$reader = new JsonStatReader($jsonstat);
$dims = $reader->getDimensionSizes();
$numRowDim = count(array_slice($dims, 0, count($dims) - 2));
$renderer = new RendererTable($reader, $numRowDim);
$renderer->init();
$table = $renderer->render();
/*$doc = new DOMDocument('1.0', 'UTF-8');
$node = $doc->importNode($table, true);
$doc->appendChild($node);
$table = $doc->saveHTML();*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="table.css">
</head>
<body class="lfi4">
<?php echo $table; ?>
</body>
</html>
