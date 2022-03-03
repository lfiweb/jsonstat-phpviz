# jsonstat-phpviz
Render [JSON-stat data](https://json-stat.org/) with any number of dimensions as a HTML table using PHP.

## Usage
### Example 1
Render a table from a JSON-stat having 4 dimensions with sizes `[3,2,4,2]` (= shape).
Two dimensions are automatically used to group the rows:
```php
<?php

use jsonstatPhpViz\src\Reader;
use jsonstatPhpViz\src\RendererTable;

require_once __DIR__.'/../vendor/autoload.php';

$filename = 'integer.json';
$json = file_get_contents($filename);
$jsonstat = json_decode($json);

$reader = new Reader($jsonstat);
$table = new RendererTable($reader);
$html = $table->render();
```
![screenshot-01](demo/screenshot-01.png)

### Example 2
Render a table from the same JSON-stat data, but with 3 dimensions used for the row grouping instead:
```php
$table = new RendererTable($reader, 3);
$html = $table->render();
```
![screenshot-02](demo/screenshot-02.png)

### Example 3
Transpose the table by permutating dimension A with dimension D. Also hide label of dimension A:
```php
$axes = [3, 1, 2, 0];
$reader->transpose($axes);
$table = new RendererTable($reader);
$table->noLabelLastDim = true;
$html = $table->render();
```
![screenshot-03](demo/screenshot-03.png)

# Example 4
Real-world example from the Swiss National Forest Inventory

## Installation
Install with composer

## Dependencies
none

## Features
- The number of dimensions the table renderer can handle is theoretically unlimited (e.g. limited only by browser memory).
- transposition
- accessible using scope and spans, render without spans
- table caption is created from jsonstat . Can be hidden by setting to null
- dimensions of size one are excluded by default,
- css classes are set to allow for row totals (last category of each row dimension)

## JSON-stat rendering rules
The renderer follows these rules:

* only dimensions of size > 1 are used to render
* the last dimension is used for the innermost table columns
* all the other dimensions are use for either rows or columns.
* by default the second to last dimension is also used for columns, all the others for rows

### Author
Simon Speich, [Swiss National Forest Inventory](https://www.lfi.ch/)

## License
GNU General Public License v3.0 or later\
See COPYING for the full text.
