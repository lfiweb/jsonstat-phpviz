document.addEventListener('DOMContentLoaded', () => {
    /* find the first th element with scope=colgroup of the row which is a previous sibling of the last row */
    const cssQuery = '.lastDimSize3 tr:has(+ tr:last-of-type) th:nth-child(1 of [scope=colgroup])',
     colspanLastDim = document.querySelector(cssQuery).colSpan;

    // reduce the colspans of the header cells
    document.querySelectorAll('.lastDimSize3 thead tr th[colspan]')
        .forEach(th => {
            th.colSpan -= th.colSpan / colspanLastDim;
        });

});