urlDispatcher.addRoutes({
    'module.routing': NETCAT_PATH + 'modules/routing/admin/?controller=route&action=index&site_id=%1',
    'module.routing.route.list': NETCAT_PATH + 'modules/routing/admin/?controller=route&action=index&site_id=%1',
    'module.routing.route.add': NETCAT_PATH + 'modules/routing/admin/?controller=route&action=add&site_id=%1',
    'module.routing.route.edit': NETCAT_PATH + 'modules/routing/admin/?controller=route&action=edit&route_id=%1',
    'module.routing.settings': NETCAT_PATH + 'modules/routing/admin/?controller=settings&action=index&site_id=%1',
    1: '' // dummy entry
});