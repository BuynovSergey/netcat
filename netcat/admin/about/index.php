<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
require_once $ADMIN_FOLDER . 'report/function.inc.php';

$UI_CONFIG = new ui_config_tool(SECTION_ABOUT_TITLE, SECTION_ABOUT_TITLE, 'i_netcat_big.gif', 'help.about');

if (!isset($phase) || !$phase) {
    $phase = 1;
}

BeginHtml(SECTION_ABOUT_TITLE, SECTION_ABOUT_HEADER, 'http://' . $DOC_DOMAIN . '/');

switch ($phase) {
    case 1:
        if (CMS_SYSTEM_NAME === 'Netcat') {
            printf(SECTION_ABOUT_BODY, $SYSTEM_COLOR, $SYSTEM_NAME, $VERSION_ID);
        }

        if ($DEVELOPER_NAME) {
            echo '<br><br>';
            echo '<p>';
            echo '<i>' . SECTION_ABOUT_DEVELOPER . ':</i> ';
            if ($DEVELOPER_URL) {
                echo '<a href="' . htmlspecialchars($DEVELOPER_URL, ENT_QUOTES) . '" target="_blank">';
            }

            echo '<i>' . htmlspecialchars($DEVELOPER_NAME, ENT_QUOTES) . '</i>';

            if ($DEVELOPER_URL) {
                echo '</a>';
            }

            echo '</p>';
        }

        echo nc_report_status();

        break;
    case 2:
        $nc_core->token->exit_if_invalid();
        ob_clean();
        phpinfo();
        exit;
        break;
}

EndHtml();
?>