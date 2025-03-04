
urlDispatcher.addRoutes( {
    // append tab
    'module.subscriber': NETCAT_PATH + 'modules/subscriber/admin.php',
    'module.subscriber.mailer': NETCAT_PATH + 'modules/subscriber/admin.php?phase=1',
    'module.subscriber.mailer.add': NETCAT_PATH + 'modules/subscriber/admin.php?phase=2',
    'module.subscriber.mailer.edit': NETCAT_PATH + 'modules/subscriber/admin.php?phase=2&mailer_id=%1',

    'module.subscriber.mailer.users': NETCAT_PATH + 'modules/subscriber/admin.php?phase=6&mailer_id=%1',

    'module.subscriber.stats': NETCAT_PATH + 'modules/subscriber/admin.php?phase=3',
    'module.subscriber.stats.mailer': NETCAT_PATH + 'modules/subscriber/admin.php?phase=4&mailer_id=%1',

    'module.subscriber.settings': NETCAT_PATH + 'modules/subscriber/admin.php?phase=5',

    'module.subscriber.once': NETCAT_PATH + 'modules/subscriber/admin.php?phase=7'

} );