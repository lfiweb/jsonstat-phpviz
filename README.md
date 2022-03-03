# jsonstat-phpviz
Render [JSON-stat data](https://json-stat.org/) with any number of dimensions as a HTML table using PHP.

## Features
- theoretically any number of dimensions of any size can be rendered as an HTML table (e.g. limited only by memory).
- tables are structured with `<thead>` and `<tbody>`
- a table `<caption>` is automatically created from the JSONS-stat. Can be hidden by setting `RendererTable->caption = null`
- screenreader support for visually impaired users by rendering column and row headers together \
with the `scope`, `colspan` and `rowspan` attributes
- css classes (`first` and `last`) are set to identify starting and ending of row groups (e.g. row totals)
- dimensions can be transposed along two or more axes with the method Reader::transpose() method using
- dimensions of size one (when ordered continuously from index 0) can be excluded from rendering

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
Transpose the table by permutating dimension A with dimension D:
```php
$axes = [3, 1, 2, 0];
$reader->transpose($axes);
$table = new RendererTable($reader);
$html = $table->render();
```
See [NumPy tranpose](https://numpy.org/doc/stable/reference/generated/numpy.transpose.html) for how to use the axes array.
![screenshot-03](demo/screenshot-03.png)

### Example 4
Real-world example from the Swiss National Forest Inventory

## Installation
Install with composer

## Dependencies
none


## JSON-stat rendering rules
The renderer follows these rules:

- only dimensions of size > 1 are used to render
- the last dimension is used for the innermost table columns
- label property is used for the caption
- all the other dimensions are use for either rows or columns.
- by default the second to last dimension is also used for columns, all the others for rows

### Author
Simon Speich, [Swiss National Forest Inventory](https://www.lfi.ch/)

## License
GNU General Public License v3.0 or later\
See [COPYING](README.md) for the full text.
