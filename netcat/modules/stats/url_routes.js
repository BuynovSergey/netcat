urlDispatcher.addRoutes({
    'module.stats': NETCAT_PATH + 'modules/stats/openstat/admin.php',
    'module.stats.settings': NETCAT_PATH + 'modules/stats/settings.php',
    'module.stats.nc_stat': NETCAT_PATH + 'modules/stats/admin.php',
    'module.stats.openstat': NETCAT_PATH + 'modules/stats/openstat/admin.php',
    'module.stats.analytics': NETCAT_PATH + 'modules/stats/admin/?controller=analytics&action=index'
})
.addPrefixRouter('module.stats.openstat.', function(sub_view, params) {
    var url = NETCAT_PATH + "modules/stats/openstat/admin.php?sub_view=" + sub_view.substr('module.stats.openstat'.length+1);
    if (params) {
        url += "&phase=" + params;
    }
    mainView.loadIframe(url);
});