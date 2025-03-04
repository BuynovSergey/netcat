<?php
/* Console bootstrap API
 *
 * Include this file for scripts which are intended to run from console only
 */

// Forbid running from web
if (isset($_SERVER['REMOTE_ADDR'])) die('Forbidden');

// Initialize system variables
$NETCAT_FOLDER = realpath(dirname(__FILE__)."/../../");
putenv("DOCUMENT_ROOT=$NETCAT_FOLDER");
putenv("HTTP_HOST=localhost");
putenv("REQUEST_URI=/");

// Load system settings
require_once("$NETCAT_FOLDER/vars.inc.php");
require_once($ROOT_FOLDER . "connect_io.php");
