urlDispatcher.addRoutes({
    'module.search': NETCAT_PATH + 'modules/search/admin.php?view=info'
})
// лень прописывать 100500 адресов
.addPrefixRouter('module.search.', function(path, params) {
    var url = NETCAT_PATH + "modules/search/admin.php?view=" + path.substr(14);
    if (params) {
        url += "&id=" + params;
    }
    mainView.loadIframe(url);
});
