/* $Id: url_routes.js 4308 2011-03-02 14:32:11Z gaika $ */

urlDispatcher.addRoutes( { 
  // append tab
  'module.comments': NETCAT_PATH + 'modules/comments/admin.php',
  'module.comments.list': NETCAT_PATH + 'modules/comments/admin.php',
  'module.comments.template': NETCAT_PATH + 'modules/comments/admin.php?phase=2',
  'module.comments.subscribe': NETCAT_PATH + 'modules/comments/admin.php?phase=3',
  'module.comments.converter': NETCAT_PATH + 'modules/comments/admin.php?phase=4',
  'module.comments.optimize': NETCAT_PATH + 'modules/comments/admin.php?phase=8',
  'module.comments.settings': NETCAT_PATH + 'modules/comments/admin.php?phase=9',
  'module.comments.subscribes': NETCAT_PATH + 'modules/comments/admin.php?phase=10',
  'module.comments.edit': NETCAT_PATH + 'modules/comments/admin.php?phase=15&comment=$1'

} );