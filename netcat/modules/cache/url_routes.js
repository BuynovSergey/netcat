// $Id: url_routes.js 6206 2012-02-10 10:12:34Z denis $

urlDispatcher.addRoutes( { 
    // append tab
    'module.cache': NETCAT_PATH + 'modules/cache/admin.php',
    'module.cache.settings': NETCAT_PATH + 'modules/cache/admin.php?page=settings',
    'module.cache.info': NETCAT_PATH + 'modules/cache/admin.php?phase=3&page=info',
    'module.cache.audit': NETCAT_PATH + 'modules/cache/admin.php?phase=5&page=audit'
} );