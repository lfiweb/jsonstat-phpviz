# jsonstat-phpviz
Render [JSON-stat v2.0 data](https://json-stat.org/) with any number of dimensions as a HTML table using PHP.

## Features
- render any number of dimensions of any size as an HTML table (e.g. theoretically limited only by memory).
- use any number of dimensions to group rows and columns.
- transpose dimensions along two or more axes
- structures the table with `<thead>` and `<tbody>` elements
- creates a table `<caption>` automatically from the JSON-stat.
- renders column and row headers using the attributes `scope`, `colspan` and `rowspan` to provide
screenreader support for visually impaired users
- sets css classes (`first` and `last`) to identify starting and ending of row groups (e.g. row totals)
- exclude dimensions of size one (when ordered continuously from index 0) from rendering when wanted
### not implemented
- `child` property, e.g. hierarchical relationships between different categories

## Usage
### Example 1
Render a table from JSON-stat data having 4 dimensions with sizes `[3,2,4,2]` (= shape).
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
See [NumPy transpose](https://numpy.org/doc/stable/reference/generated/numpy.transpose.html) for how to use the axes array.
![screenshot-03](demo/screenshot-03.png)

### Example 4
Real-world example with [data from the Swiss NFI](https://www.lfi.ch/resultate/sammlungenliste-en.php?prodNr=32&prodItNr=189147&lang=en) having a caption, column units and row totals as well as
two dimensions of size one excluded from rendering.
```php
$reader = new Reader($jsonstat);
$table = new RendererTable($reader);
$table->excludeOneDim = true;
$table->noLabelLastDim = true;
$html = $table->render();
```
![screenshot-04](demo/screenshot-04.png)

## Installation
Install with composer

## Dependencies
none

## JSON-stat rendering rules
The renderer applies the following rules when generating a html table:
- the sizes of the dimensions are read from the `size` property from left to right and also rendered in that order
- dimensions 1, ..., n-2 are used to group rows (can be set manually to any number <= n)
- the second to last dimension n-1 is used as the first, outer column
- the last dimension is used as the innermost column
- the `label` property is used for the caption (can be set manually to null or any string)

### Author
Simon Speich for the [Swiss National Forest Inventory](https://www.lfi.ch/)

## License
GNU General Public License v3.0 or later\
See [COPYING](README.md) for the full text.
