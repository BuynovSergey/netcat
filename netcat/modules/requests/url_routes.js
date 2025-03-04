urlDispatcher.addRoutes({
    'module.requests': NETCAT_PATH + 'modules/requests/admin/?controller=settings&action=index',
    'module.requests.settings': NETCAT_PATH + 'modules/requests/admin/?controller=settings&action=index',
    'module.requests.list': NETCAT_PATH + 'modules/requests/admin/?controller=list&action=index&site_id=%1',
      
    1: '' // dummy entry
});