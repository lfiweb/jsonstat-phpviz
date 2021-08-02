# jsonstat-phpviz
Render a html table from jsonstat.json data https://json-stat.org/ with php, especially Swiss NFI data https://www.lfi.ch.
The number of dimensions the table renderer can handle is theoretically unlimited (e.g. limited only by browser memory).

The renderer follows these rules:

* only dimensions of size > 1 are used to render
* the last dimension is used for the innermost table columns
* all the other dimensions are use for either rows or columns. 
* by default the second to last dimension is also used for columns, all the others for rows