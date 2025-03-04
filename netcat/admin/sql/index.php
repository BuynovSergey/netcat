<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."sql/function.inc.php");

$UI_CONFIG = new ui_config_tool(TOOLS_SQL, TOOLS_SQL, 'i_tool_sql_big.gif', 'tools.sql');

$Delimeter = " &gt ";
$main_section = "settings";
$item_id = 9;
$Title2 = TOOLS_SQL;

if (!isset($phase)) $phase = 1;

if (in_array($phase, array(2))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/sql/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

switch ($phase) {
    case 1:
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/sql/");
        $perm->ExitIfNotAccess(NC_PERM_SQL, 0, 0, 0, 0);
        ShowSQLForm ();
        break;

    case 2:
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/sql/");
        $perm->ExitIfNotAccess(NC_PERM_SQL, 0, 0, 0, 1);
        $nc_core->security->sql_filter->set_mode(nc_security_filter::MODE_DISABLED);
        if ($Query) {
            $queries = (array) nc_parse_queries_string_to_array($Query);
            foreach ($queries as $Query) {
                echo "<div>";
                ExecuteSQLQuery($Query);
                echo "</div>";
            }
        } else {
            nc_print_status(TOOLS_SQL_ERR_NOQUERY, 'error');
        }

        ShowSQLForm ();
        break;
}

EndHtml ();
