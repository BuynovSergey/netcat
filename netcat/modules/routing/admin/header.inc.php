<?

$NETCAT_FOLDER = realpath(dirname(__FILE__) . "/../../../../") . "/";
require_once $NETCAT_FOLDER . "vars.inc.php";
require_once $ADMIN_FOLDER . "function.inc.php";

/** @var Permission $perm */
$perm->ExitIfNotAccess(array(NC_PERM_MODULE, 'routing'), 0, 0, 0, 1);

BeginHtml(NETCAT_MODULE_ROUTING_TITLE, '', '');
