<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER . "vars.inc.php");
require ($ADMIN_FOLDER . "function.inc.php");
require ($ADMIN_FOLDER . "class/function.inc.php");

$Delimeter = " &gt ";
$main_section = "settings";
$item_id = 1;
$Title2 = SECTION_SECTIONS_OPTIONS_SYSTEM;
$Title3 = "<a href=\"" . $ADMIN_PATH . "field/system.php\">" . SECTION_SECTIONS_OPTIONS_SYSTEM . "</a>";
$Title8 = CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_EDIT;

/**
 * Выведем список системных таблиц
 */
function SystemTableList() {
    global $db, $UI_CONFIG, $ADMIN_PATH;
    $nc_core = nc_Core::get_object();
    $nc_core->load('modules');
    if (!isset($_REQUEST['fs'])) {
        $_REQUEST['fs'] = null;
    }
    $UI_CONFIG = new ui_config_system_classes('systemclass.list');
    // reinit old value from class/function.inc.php
    $UI_CONFIG->headerText = SECTION_SECTIONS_OPTIONS_SYSTEM;
    ?>
    <form method='post' action='index.php'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr><td>
                <table class='nc-table nc--striped nc--hovered' width='100%'>
                    <tr>
                        <th>ID</th>
                        <th width='60%'><?= CONTROL_SCLASS_TABLE ?></th>
                        <th width='30%'><?= CONTROL_SCLASS_ACTION ?></th>
                        <th width='10%'><?= CONTROL_CLASS_FIELDS ?></th>
                    </tr>
<?php
    $select = "SELECT a.System_Table_ID, a.System_Table_Rus_Name,b.Class_ID,IF(b.AddTemplate<>'' OR b.AddCond<>'' OR b.AddActionTemplate<>'',1,0) AS IsAdd, IF(b.EditTemplate<>'' OR b.EditCond<>'' OR b.EditActionTemplate<>'' OR b.CheckActionTemplate<>'' OR b.DeleteActionTemplate<>'',1,0) AS IsEdit, IF(b.SearchTemplate<>'' OR b.FullSearchTemplate<>'',1,0) AS IsSearch, IF(b.SubscribeTemplate<>'' OR b.SubscribeCond<>'',1,0) AS IsSubscribe, COUNT(f.Field_ID) AS `Fields`
        FROM System_Table AS a
        LEFT JOIN Class AS b ON a.System_Table_ID=b.System_Table_ID	AND b.ClassTemplate = 0 AND b.File_Mode =" . +$_REQUEST['fs'] . "
        LEFT JOIN Field AS f ON f.System_Table_ID = a.System_Table_ID
        GROUP BY a.System_Table_ID
        ORDER BY a.System_Table_ID";
    $Result = $db->get_results($select, ARRAY_N);

    foreach ($Result as $Array) {
        if ($Array[0] == 3) {
            //$Array[7] = $Array[7] / 2;
        }
        else if (!+$_REQUEST['fs']) {
            continue;
        }

        print "<tr>";
        print "<td>" . $Array[0] . "</td>";
        print "<td " . (!$Array[2] ? "colspan=2 " : "") . "bgcolor=white>" . ($Array[2] && $nc_core->modules->get_by_keyword('auth', 0) ? "<a href=" . $ADMIN_PATH . "field/system.php?fs=" . +$_REQUEST['fs'] . "&phase=2&SystemTableID=" . $Array[0] . ">" : "<a href=" . $ADMIN_PATH . "field/index.php?fs=" . +$_REQUEST['fs'] . "&isSys=1&amp;SystemTableID=" . $Array[0] . ">") . constant($Array[1]) . "</a></td>";
        if ($Array[2]) {
            print "<td>
                    <a href=" . $ADMIN_PATH . "field/system.php?fs=" . +$_REQUEST['fs'] . "&phase=4&SystemTableID=" . $Array[0] . "&myaction=1>" . (!$Array[3] ? "<font color=gray>" : "") . CONTROL_CLASS_ACTIONS_ADD . "</a>&nbsp;&nbsp;
                    <a href=" . $ADMIN_PATH . "field/system.php?fs=" . +$_REQUEST['fs'] . "&phase=4&SystemTableID=" . $Array[0] . "&myaction=2>" . (!$Array[4] ? "<font color=gray>" : "") . CONTROL_CLASS_ACTIONS_EDIT . "</a>&nbsp;&nbsp;
                    <a href=" . $ADMIN_PATH . "field/system.php?fs=" . +$_REQUEST['fs'] . "&phase=4&SystemTableID=" . $Array[0] . "&myaction=3>" . (!$Array[5] ? "<font color=gray>" : "") . CONTROL_CLASS_ACTIONS_SEARCH . "</a></td>";
        }
        print "<td><a class=\"nc-label nc--blue\" href=\"" . $ADMIN_PATH . "field/index.php?fs=" . +$_REQUEST['fs'] . "&isSys=1&amp;SystemTableID=" . $Array[0] . "&fs=".+$_REQUEST['fs']."\">" . $Array[7] . " ".mb_strtolower( $nc_core->lang->get_numerical_inclination($Array[7], array(CONTROL_CLASS_FIELD, CONTROL_CLASS_FIELDS, CONTROL_CLASS_FIELDS_COUNT)) )."</a></td>\n";
        print "</tr>";
    }
?>
                </table>
            </td></tr>
        </table>
<?php
}


if (!isset($phase))
    $phase = 1;

if (in_array($phase, array(3, 5))) {
    if (!$nc_core->token->verify()) {
        if (defined('NC_ADMIN_DISABLE_LOGIN_PAGE') || $nc_core->input->fetch_post('NC_HTTP_REQUEST')) { // AJAX call
            nc_set_http_response_code(401);
            exit;
        }

        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/management/class/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

// обработка этапов действий
switch ($phase) {
    // покажем список системных таблиц
    case 1:
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/settings/systables/");
        $perm->ExitIfNotAccess(NC_PERM_SYSTABLE, NC_PERM_ACTION_LIST, 0, 0, 0);
        
        SystemTableList();
    break;
    // редактирование
    case 2:
        $AJAX_SAVER = true;
        if ( $perm->isGuest() )
            $AJAX_SAVER = false;
        BeginHtml($Title2, $Title3 . $Delimeter . $Title8, "http://" . $DOC_DOMAIN . "/settings/systables/users/");
        $perm->ExitIfNotAccess(NC_PERM_SYSTABLE, 0, 0, 0, 0);
        $UI_CONFIG = new ui_config_system_class('edit', $SystemTableID);
        ClassForm($SystemTableID, "system.php", 3, 3, 0);
    break;
    // редактирование завершено
    case 3:
        BeginHtml($Title2, $Title2, "http://" . $DOC_DOMAIN . "/settings/systables/");
        $perm->ExitIfNotAccess(NC_PERM_SYSTABLE, 0, 0, 0, 1);
        $UI_CONFIG = new ui_config_system_class('fields', $System_Table_ID);
        ActionClassComleted($type);
        if ($System_Table_ID == 3) {
            if (+$_REQUEST['isNaked']) {
                ob_clean();
                echo 'OK';
                exit;
            }
            nc_print_status(CONTROL_CONTENT_CLASS_SUCCESS_EDIT, 'ok');
            ClassForm($System_Table_ID, "system.php", 3, 3, 0);
        } else {
            SystemTableList();
        }
    break;
    // редактирование без UI_CONFIG
    case 4:
        $AJAX_SAVER = true;
        if ( $perm->isGuest() )
            $AJAX_SAVER = false;
        BeginHtml($Title2, $Title3 . $Delimeter . $Title8, "http://" . $DOC_DOMAIN . "/settings/systables/");
        $perm->ExitIfNotAccess(NC_PERM_SYSTABLE, 0, 0, 0, 0);
        ClassActionForm($SystemTableID, "system.php", 5, 2, $myaction);
    break;
    // редактирование завершено без UI_CONFIG
    case 5:
        BeginHtml($Title2, $Title3 . $Delimeter . $Title8, "http://" . $DOC_DOMAIN . "/settings/systables/");
        $perm->ExitIfNotAccess(NC_PERM_SYSTABLE, 0, 0, 0, 1);
        ClassActionCompleted($myaction, $type);
        if (+$_REQUEST['isNaked']) {
            ob_clean();
            echo 'OK';
            exit;
        }
        SystemTableList();
    break;
}

EndHtml();
?>
