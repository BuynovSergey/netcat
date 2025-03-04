<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once $NETCAT_FOLDER . 'vars.inc.php';
@require_once $ADMIN_FOLDER . 'function.inc.php';
@require_once nc_module_folder('stats') . 'openstat/openstat_core_class.php';

if (!isset($perm) || !$perm->isSupervisor()) {
    die;
}
?><html>
    <head>
        <title>Openstat redirector</title>
    </head>
    <body onload='document.openstat_auch.submit()'>
        <h2><?= NETCAT_MODULE_STATS_OPENSTAT_REDIRECTING_TO; ?> https://www.openstat.ru<?= htmlspecialchars($_GET['url']); ?></h2>

        <form action='https://www.openstat.ru/login' method='POST' name='openstat_auch'>
            <input type='hidden' name='login' value='<?= $GLOBALS['nc_core']->get_settings('Openstat_Login', 'stats'); ?>' />
            <input type='hidden' name='password' value='<?= $GLOBALS['nc_core']->get_settings('Openstat_Password', 'stats'); ?>' />
            <input type='hidden' name='destination' value='<?= htmlspecialchars($_GET['url']); ?>'/>
<?= NETCAT_MODULE_STATS_OPENSTAT_WAIT_OR_CLICK; ?> <input type='submit' title="<?= NETCAT_MODULE_STATS_OPENSTAT_GO; ?>" value="<?= NETCAT_MODULE_STATS_OPENSTAT_GO; ?>" />
        </form>

    </body>
</html>