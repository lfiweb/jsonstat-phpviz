document.addEventListener('DOMContentLoaded', () => {
    /* find the first th element with scope=colgroup of the row which is a previous sibling of the last row */
    const cssQuery = 'tr:has(+ tr:last-of-type) th:nth-child(1 of [scope=colgroup])';
    let colspanLastDim;

    document.querySelectorAll('.jst-viz')
        .forEach(table => {
            table.classList.add('ci-hidden');
        });

    // 95%
    colspanLastDim = document.querySelector(cssQuery).colSpan;
    document.querySelectorAll('thead tr')
        .forEach((tr, rowIdx) => {
            tr.querySelectorAll('th[colspan]')
                .forEach(th => {
                    th.colSpan -= th.colSpan / colspanLastDim;  // * 2 for both confidence intervals
                });

        });

    //
    colspanLastDim = document.querySelector(cssQuery).colSpan;
    document.querySelectorAll('table.lastDimSize3 thead tr')
        .forEach((tr, rowIdx) => {
            tr.querySelectorAll('th[colspan]')
                .forEach(th => {
                    th.colSpan -= th.colSpan / colspanLastDim;  // * 2 for both confidence intervals
                });

        });
});