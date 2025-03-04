var oSiteMapSearch, oSiteMap, oNotFound, oldSearchKeyword, searchTimeoutId;

bindEvent(window, 'load', function() {
    if (oSiteMap = document.getElementById('nc_site_map')) {
        for (var i = 0; i < oSiteMap.rows.length; i++) {
            bindEvent(oSiteMap.rows[i], 'mouseover', siteMapMouseOver);
            bindEvent(oSiteMap.rows[i], 'mouseout', siteMapMouseOut);
        }
    }
    if ((oNotFound = document.getElementById('siteMapNotFound')) && (oSiteMapSearch = document.getElementById('siteMapSearch'))) {
        oldSearchKeyword = oSiteMapSearch.searchKeyword.value;

        oSiteMapSearch.searchKeyword.disabled = false;
        window.setInterval(checkKeyword, 500);
    }
});

function siteMapMouseOver() {
    //  this.className = 'highlight';
    this.style.backgroundColor = '#cbdde7';
}

function siteMapMouseOut() {
    //  this.className = '';
    this.style.backgroundColor = '';
}


function checkKeyword() {
    var searchKeyword = oSiteMapSearch.searchKeyword.value;
    if (oldSearchKeyword != searchKeyword) {
        if (searchTimeoutId) {
            clearTimeout(searchTimeoutId);
        }
        searchTimeoutId = setTimeout('searchSiteMap("'+searchKeyword.toLowerCase().replace('"', '&quot;') +'")', 1000);
        oldSearchKeyword = searchKeyword;
    }
}

function searchSiteMap(keyword) {
    // oSiteMap.className = (keyword.length) ? 'search' : '';
    var foundSomeItems = (keyword.length) ? false : true;

    for (var i = 0; i < oSiteMap.rows.length; i++) {
        var hideRow = false, cell = oSiteMap.rows[i].cells[0];

        if (keyword.length) {
            if (!cell.searchText) {
                cell.searchText = (cell.innerText ? cell.innerText.toLowerCase()
                    : cell.textContent.toLowerCase());
                cell.originalPadding = cell.style.paddingLeft;
            }

            if ((cell.searchText.indexOf(keyword) == -1)) {
                hideRow = true;
            }
            else {
                foundSomeItems = true;
            }
        }
        oSiteMap.rows[i].style.display = hideRow ? 'none' : '';
        if (!hideRow) cell.style.paddingLeft = keyword.length ? '8px' : cell.originalPadding;
    }

    oNotFound.style.display = (foundSomeItems ? 'none' : '');
}
