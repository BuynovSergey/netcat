/*urlDispatcher.addRoutes( {
  // append tab
  'module.auth': NETCAT_PATH + 'modules/auth/admin.php',
  'module.auth.info': NETCAT_PATH + 'modules/auth/admin.php?phase=1',
  'module.auth.reg': NETCAT_PATH + 'modules/auth/admin.php?phase=2',
  'module.auth.reg.classic': NETCAT_PATH + 'modules/auth/admin.php?phase=2',
  'module.auth.reg.ex': NETCAT_PATH + 'modules/auth/admin.php?phase=3',
  'module.auth.mail': NETCAT_PATH + 'modules/auth/admin.php?phase=4',
  'module.auth.template': NETCAT_PATH + 'modules/auth/admin.php?phase=5',
  'module.auth.settings': NETCAT_PATH + 'modules/auth/admin.php?phase=6'
} );
*/

urlDispatcher.addRoutes({
    'module.auth': NETCAT_PATH + 'modules/auth/admin.php?view=info'
})
.addPrefixRouter('module.auth.', function(path, params) {
    var url = NETCAT_PATH + "modules/auth/admin.php?view=" + path.substr(12);
    if (params) {
        url += "&id=" + params;
    }
    mainView.loadIframe(url);
});