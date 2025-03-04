<?php
/* 
 * Creates a backup.
 *
 * Script is intended to be run from cron or console
 *
 * By default, full backup is being created.
 * Options may be passed via $_GET
 *
 * Known options and possible values:
 * 	mode: 
 * 		backup creation mode. 
 * 		Possible values (string): 
 * 			full: means full backup (default)
 * 			sql: means sql-only backup
 * 			default (or any other value): means 5.0.2 default settings (data only)
 * 		value should be checked in mkDump() function
 * 	no_standalone:
 * 		do not add restore.php script.
 * 		Possible values (int):
 * 			1: disable restore script
 * 			0: enable restore script (default)
 * 	keep:
 * 		remove old backups and keep only specified count of backups
 * 		Possible values (int): 
 * 		1 (or any other integer number > 0): means count of backups to keep
 * 		0: do not remove any old backups
 *
 *
 */

// Multi-mode: detect running mode and select appropriate API (one for console, and one for web-based cron)
// Security is provided via cron_api automatically
$ADMIN_PATH = realpath(dirname(__FILE__));
if (!isset($_SERVER['REMOTE_ADDR'])) require_once $ADMIN_PATH . '/../require/console_api.inc.php';
else require_once $ADMIN_PATH . '/../require/cron_api.inc.php';

// Load required sources
require_once ("module.inc.php");
require_once ("tar.inc.php");
require_once ("dump.inc.php");


// Define default backup options
$backup_options = array('mode' => 'full', 'standalone' => true);

// Get options from $_GET
if (isset($_GET['mode'])) $backup_options['mode'] = $_GET['mode'];
if (isset($_GET['no_standalone']) && intval($_GET['no_standalone'])>0) $backup_options['standalone'] = false;

// Actually make a backup
mkDump(true, $backup_options);

if (isset($_GET['keep']) && intval($_GET['keep']>0)) {
	RemoveOldBackups(intval($_GET['keep']));
}
