<?php
if (!class_exists("nc_System")) {
    die("Unable to load file.");
}
if (!isset($UserID)) {
    $UserID = null;
}
$systemMessageID = $UserID;
$systemTableName = 'User';
$systemTableID = GetSystemTableID($systemTableName);

/**
 * Return array with error discrp.
 *
 * @return array array[code_error] = error
 */
function GetArrayWithError_User() {
    return array(
        2 => NETCAT_MODERATION_ERROR_NORIGHTS,
        3 => CONTROL_USER_RIGHTS_ERROR_NOSELECTED,
        4 => CONTROL_USER_RIGHTS_ERROR_DATA,
        5 => CONTROL_USER_RIGHTS_ERROR_DB,
        6 => CONTROL_USER_RIGHTS_ERROR_POSSIBILITY,
        7 => CONTROL_USER_RIGHTS_ERROR_NOTSITE,
        8 => CONTROL_USER_RIGHTS_ERROR_NOTSUB,
        9 => CONTROL_USER_RIGHTS_ERROR_NOTCCINSUB,
        10 => CONTROL_USER_RIGHTS_ERROR_NOTTYPEOFRIGHT,
        11 => CONTROL_USER_RIGHTS_ERROR_START,
        12 => CONTROL_USER_RIGHTS_ERROR_END,
        13 => CONTROL_USER_RIGHTS_ERROR_STARTEND,
        14 => NETCAT_MODULE_MAILER_NO_ONE_MAILER,
        15 => CONTROL_USER_RIGHTS_ERROR_GUEST,
        16 => CONTROL_USER_RIGHTS_ERROR_NONE_AVAILABLE,
    );
}

/**
 * Return html-code with form for user search
 *
 * @return string html-code
 */
function SearchUserForm($totalUsers) {
    global $db, $ROOT_FOLDER, $INCLUDE_FOLDER, $admin_mode, $MODULE_VARS;
    global $systemTableID, $systemMessageID, $systemTableName, $ADMIN_PATH;
    global $UserID, $Checked, $grpID, $sort_by, $sort_order, $objcount, $nonConfirmed, $rightsIds, $srchPat;

    $module_subscriber = 0;
    if (nc_module_check_by_keyword('subscriber', 0)) {
        if (isset($MODULE_VARS['subscriber']['VERSION'])) {
            $module_subscriber = ($MODULE_VARS['subscriber']['VERSION'] > 1) ? 2 : 1;
        }
    }

    if (!$UserID) {
        $UserID = '';
    }
    if (!$Checked) {
        $Checked = 0;
    }
    if (!$grpID || !is_array($grpID)) {
        $grpID = array();
    }
    if (!$rightsIds || !is_array($rightsIds)) {
        $rightsIds = array();
    }
    if (!$sort_by) {
        $sort_by = 0;
    }
    if (!$sort_order) {
        $sort_order = 0;
    }
    if (!$objcount) {
        $objcount = 20;
    }
    if (!$nonConfirmed) {
        $nonConfirmed = 0;
    }
    if ($nonConfirmed) {
        $Checked = 2;
    }

    require_once $INCLUDE_FOLDER . "s_list.inc.php";
    $is_there_any_files = 0;

    $nc_core = nc_core::get_object();
    require $ROOT_FOLDER . "message_fields.php";

    $flds = array_flip($fld);
    $login_num = isset($flds['Login']) ? $flds['Login'] : null;
    $email_num = $flds['Email'];
    $filter_by_login = '';

    if ($login_num) {
        $filter_by_login = "
          <tr>
            <td style='padding-right: 10px'>$fldName[$login_num]: </td>
            <td>
                <input type='text' name='srchPat[$login_num]' size='50' maxlength='255' value='" .
            htmlspecialchars(stripcslashes($srchPat[$login_num]), ENT_QUOTES) . "'>
            </td>
            <td rowspan='2' style='padding-left: 30px'>
                <input style='background: #EEE; margin-top:15px; padding: 8px 6px 12px 6px; font-size: 15px; color: #333; border: 2px solid #1A87C2;' type='submit' class='s' value='" .
            CONTROL_USER_FUNCS_DOGET . "' title='" . CONTROL_USER_FUNCS_DOGET . "'/>
            </td>
          </tr>";
    }
    if (!isset($srchPat[$email_num])) {
        $srchPat[$email_num] = null;
    }
    $html = "<div id='userFormSearch'><legend>" . CONTROL_USER_FUNCS_USERSGET . "</legend>
              <form method='get' action='index.php' id='userSearchForm' >
                <table border='0' cellpadding='0' cellspacing='0'>
                  $filter_by_login
                  <tr>
                    <td style='padding-right: 10px'>$fldName[$email_num]: </td>
                    <td>
                        <input type='text' name='srchPat[$email_num]' size='50' maxlength='255' value='" .
        htmlspecialchars(stripcslashes($srchPat[$email_num]), ENT_QUOTES) . "'>
                    </td>
                  </tr>
                </table>
                <input type='hidden' name=phase value='2'/>
	            <input type='submit' class='hidden'/>
	            <input type='hidden' name='isSearch' value='1'/>
              </form>
            </div>";


    $html .= "<fieldset id='userFormSearchOff' style='cursor: pointer;' onclick=\"\$nc('#userFormSearchOff').css('display', 'none'); \$nc('#userFormSearchOn').css('display', ''); \$nc('#userFormSearch').css('display', 'none');\">";
    $html .= "<legend ><span style='color: #1A87C2; border-bottom: 1px dashed;'>" . CONTROL_USER_FUNCS_USERSGET_EXT .
        "</span>&nbsp;[" . $totalUsers . "]</legend>";
    $html .= "</fieldset>";
    $html .= "<fieldset id='userFormSearchOn' style='display: none'>";
    $html .= "<legend  style='cursor: pointer;' onclick=\"\$nc('#userFormSearchOn').css('display', 'none'); \$nc('#userFormSearchOff').css('display', ''); \$nc('#userFormSearch').css('display', '');\">";
    $html .= "<span style='color: #1A87C2; border-bottom: 1px dashed;'>" . CONTROL_USER_FUNCS_USERSGET_EXT .
        "</span>&nbsp;[" . $totalUsers . "]:";
    $html .= "</legend>";

    $html .= "
  <form method='get' action='index.php' id='userSearchForm' style='background-color: #EEE; padding: 16px; margin-right: 16px;'>
    <table border='0' cellpadding='0' cellspacing='0' width='97%'>
      <tr>
        <td width='5%'><nobr>ID: " . nc_admin_input_simple('UserID', $UserID, 5, '', "maxlength='15'") . "</nobr></td>
        <td rowspan='2' style='padding-left: 45px'>" . CONTROL_USER_GROUP . "<br>";

    $html .= "<select name='grpID[]' multiple size='3'>"; //<option value='0'>".CONTROL_USER_MAIL_ALLGROUPS;
    if ($Result = $db->get_results("SELECT `PermissionGroup_ID`, `PermissionGroup_Name` FROM `PermissionGroup`",
        ARRAY_N)
    ) {
        foreach ($Result as $GroupArray)
            $html .= "<option value='" . $GroupArray[0] . "' " . (in_array($GroupArray[0], $grpID) ? 'selected' : '') .
                ">" . $GroupArray[0] . ": " . $GroupArray[1] . "</option>";
    }
    $html .= "</select>";

    $html .= "</td>";

    $html .= "<td rowspan=2>" . CONTROL_USER_RIGHTS_TYPE_OF_RIGHT . "<br>";
    $html .= "<select name='rightsIds[]' multiple size=3>";
    $html .= "<option value='" . DIRECTOR . "' " . (in_array(DIRECTOR, $rightsIds) ? 'selected' : '') . ">" .
        CONTROL_USER_RIGHTS_DIRECTOR . "</option>";
    $html .= "<option value='" . SUPERVISOR . "' " . (in_array(SUPERVISOR, $rightsIds) ? 'selected' : '') . ">" .
        CONTROL_USER_RIGHTS_SUPERVISOR . "</option>";
    $html .= "<option value='" . EDITOR . "' " . (in_array(EDITOR, $rightsIds) ? 'selected' : '') . ">" .
        CONTROL_USER_RIGHTS_EDITOR . "</option>";
    $html .= "<option value='" . MODERATOR . "' " . (in_array(MODERATOR, $rightsIds) ? 'selected' : '') . ">" .
        CONTROL_USER_RIGHTS_MODERATOR . "</option>";
    $html .= "<option value='" . GROUP_MODERATOR . "' " . (in_array(GROUP_MODERATOR, $rightsIds) ? 'selected' : '') .
        ">" . CONTROL_USER_RIGHTS_USER_GROUP . "</option>";
    $html .= "<option value='" . CLASSIFICATOR_ADMIN . "' " .
        (in_array(CLASSIFICATOR_ADMIN, $rightsIds) ? 'selected' : '') . ">" . CONTROL_USER_RIGHTS_CLASSIFICATORADMIN .
        "</option>";
    if ($module_subscriber == 2) {
        $html .= "<option value='" . SUBSCRIBER . "' " . (in_array(SUBSCRIBER, $rightsIds) ? 'selected' : '') . ">" .
            CONTROL_USER_RIGHTS_SUBSCRIBER . "</option>";
    }
    $html .= "<option value='" . BAN . "' " . (in_array(BAN, $rightsIds) ? 'selected' : '') . ">" .
        CONTROL_USER_RIGHTS_BAN . "</option>";
    $html .= "<option value='" . GUEST . "' " . (in_array(GUEST, $rightsIds) ? 'selected' : '') . ">" .
        CONTROL_USER_RIGHTS_GUESTONE . "</option>";
    $html .= "</select>";

    $html .= "</td>";


    $html .= "</tr>
              <tr>
                  <td><nobr>
                  " . nc_admin_radio_simple('Checked', '', CONTROL_USER_FUNCS_ALLUSERS, !$Checked, 'chk1', 'checked') . "
                  " . nc_admin_radio_simple('Checked', 1, CONTROL_USER_FUNCS_ONUSERS, $Checked == 1, 'chk2') . "
                  " . nc_admin_radio_simple('Checked', 2, CONTROL_USER_FUNCS_OFFUSERS, $Checked == 2, 'chk3') . "
                  </nobr>
                  </td>

              </tr>
              <tr>
                    <td colspan='3' align='right' style='padding-right: 10px;'>
                        <input style='background: #EEE; padding: 8px 6px 12px 6px; font-size: 15px; color: #333; border: 2px solid #1A87C2;' type='submit' class='s' value='" .
        CONTROL_USER_FUNCS_DOGET . "' title='" . CONTROL_USER_FUNCS_DOGET . "' />
                    </td>
              </tr>";


    if ($searchForm = showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt)) {

        $html .= " <tr>
                <td colspan ='3' style='padding:0'>
                  <fieldset>";

        $html .= $searchForm;
        $html .= "

                  </fieldset>
                </td>
              </tr>";
    }

    $html .= "<tr><td colspan='3' style='padding:0'>

      <br>
      <fieldset>
        <legend>" . CONTROL_USER_FUNCS_VIEWCONTROL . "</legend>
        <table border ='0'>
          <tr>
            <td> " . CONTROL_USER_FUNCS_SORTBY . " </td>
            <td>";

    $html .= "<select name='sort_by' style='width: 100%'>";
    $html .= "<option value='0' " . ($sort_by == 0 ? 'selected' : '') . " >" . CONTROL_USER_GROUP . "</option>";
    $html .= "<option value='1' " . ($sort_by == 1 ? 'selected' : '') . " >ID</option>";
    foreach ($fldID as $k => $v)
        $html .= "<option value='" . $fldID[$k] . "' " . ($sort_by == $fldID[$k] ? 'selected' : '') . " >" .
            $fldName[$k] . "</option>";
    $html .= "</select><br></td></tr>";
    $html .= "<tr><td>" . CONTROL_USER_FUNCS_SORT_ORDER . "</td><td>" .
        nc_admin_select_simple('', 'sort_order',
            array(CONTROL_USER_FUNCS_SORT_ORDER_ACS, CONTROL_USER_FUNCS_SORT_ORDER_DESC), $sort_order,
            "style='width: 100%'") . "</td><tr>
      <tr><td>" . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW . "</td>
      <td>" . nc_admin_input_simple('objcount', $objcount, 3) . "&nbsp;" . CONTROL_USER_FUNCS_USER_NUMBER_ON_THE_PAGE . "</td>
      </tr></table>
      </fieldset>

      </td></tr><tr><td valign='bottom' align='right' colspan='3' style='padding-right: 10px;'/>
      <input style='background: #EEE; padding: 8px 6px 12px 6px; font-size: 15px; color: #333; border: 2px solid #1A87C2;' type='submit' class='s' value='" .
        CONTROL_USER_FUNCS_DOGET . "' title='" . CONTROL_USER_FUNCS_DOGET . "'/>
      <input type='hidden' name=phase value='2'/>
	    <input type='submit' class='hidden'/>
	    <input type='hidden' name='isSearch' value='1'/>
      </form></td></tr></table>";
    $html .= "</fieldset><br>";

    return $html;
}

/**
 * Листинг пользователей
 *
 * @param int $totRows
 * @param string $queryStr
 * @param array $grpID
 * @param int $Checked
 * @param int $sort_by
 * @param int $sort_order
 * @param int $objcount
 * @param array $rightsIds
 *
 * @return string html code
 */
function ListUserPages($totRows, $queryStr, $grpID, $Checked, $sort_by, $sort_order, $objcount, $rightsIds) {
    global $db, $curPos;

    $html = ""; // результат работы функции

    $range = 10;
    $maxRows = intval($objcount);
    if ($maxRows < 1) {
        $maxRows = 20;
    }

    $curPos += 0;
    $Checked += 0;
    $sort_by += 0;
    $sort_order += 0;

    if (!$maxRows || !$totRows) {
        return;
    }

    $page_count = ceil($totRows / $maxRows);
    $half_range = ceil($range / 2);
    $cur_page = ceil($curPos / $maxRows) + 1;

    if ($page_count < 2) {
        return;
    }

    $maybe_from = $cur_page - $half_range;
    $maybe_to = $cur_page + $half_range;

    if ($maybe_from < 0) {
        $maybe_to = $maybe_to - $maybe_from;
        $maybe_from = 0;

        if ($maybe_to > $page_count) {
            $maybe_to = $page_count;
        }
    }

    if ($maybe_to > $page_count) {
        $maybe_from = $page_count - $range;
        $maybe_to = $page_count;

        if ($maybe_from < 0) {
            $maybe_from = 0;
        }
    }

    $html .= "<div align='center'>";
    $native_pars = "&sort_by=" . $sort_by . "&sort_order=" . $sort_order . "&objcount=" . $objcount;

    //в ссылку добавим группы
    if (is_array($grpID) && !empty($grpID)) {
        foreach ($grpID as $v)
            $native_pars .= "&grpID[]=" . intval($v);
    }
    //в ссылку добавим права
    if (is_array($rightsIds) && !empty($rightsIds)) {
        foreach ($rightsIds as $v) {
            $native_pars .= "&rightsIds[]=" . intval($v);
        }
    }

    // включен\ выключен
    if ($Checked) {
        $native_pars .= "&Checked=" . $Checked;
    }


    if ($cur_page > 1 && $page_count > $range) {
        $url = "?phase=2" . $native_pars . "&amp;" . $queryStr . "&curPos=" . ($curPos - $maxRows);
        $html .= "| <a href='{$url}' title='" . CONTROL_USER_FUNCS_PREV_PAGE . "'>&laquo;</a> | ";
    }

    for ($i = $maybe_from; $i < $maybe_to; $i++) {
        $page_number = $i + 1;
        $page_from = $i * $maxRows;
        $page_to = $page_from + $maxRows;
        $url = "?phase=2" . $native_pars . "&amp;" . $queryStr . "&curPos=" . $page_from;

        $html .= ($curPos == $page_from) ? "$page_number" : "<a href='$url'>$page_number</a>";

        if ($i != ($maybe_to - 1)) {
            $html .= " | ";
        }
    }

    if ($cur_page != $page_count && $page_count > $range) {
        $url = "?phase=2" . $native_pars . "&amp;" . $queryStr . "&curPos=" . ($curPos + $maxRows);
        $html .= " | <a href='{$url}' title='" . CONTROL_USER_FUNCS_NEXT_PAGE . "'>&raquo;</a> |";
    }

    $html .= "</div><br>";

    return $html;
}

/**
 * Show table with all users
 */
function SearchUserResult() {
    global $db, $perm, $ROOT_FOLDER, $INCLUDE_FOLDER;
    global $UserID, $PermissionGroupID, $Checked, $sort_by, $sort_order, $objcount, $isSearch, $nonConfirmed;
    global $srchPat, $admin_mode, $curPos;
    global $systemTableID, $systemMessageID, $systemTableName;
    global $AUTHORIZE_BY, $ADMIN_PATH, $ADMIN_TEMPLATE;

    $nc_core = nc_Core::get_object();

    $curPos += 0;
    if (!isset($_GET['grpID'])) {
        $_GET['grpID'] = null;
    }
    $grpID = $_GET['grpID'];
    if (!isset($_GET['rightsIds'])) {
        $_GET['rightsIds'] = null;
    }
    $rightsIds = $_GET['rightsIds'];
    $Checked = empty($Checked) ? 0 : (int)$Checked;
    $sort_by += 0;
    $sort_order += 0;
    $objcount += 0;
    $nonConfirmed += 0;

    if (is_array($grpID)) {
        $grpID = array_map('intval', $grpID);
    }

    if (is_array($rightsIds)) {
        $rightsIds = array_map('intval', $rightsIds);
    }

    require($ROOT_FOLDER . "message_fields.php");
    require_once($INCLUDE_FOLDER . "s_list.inc.php");

    //кол-во выводимых пользователей на странице
    if ($objcount < 1) {
        $objcount = 20;
    }

    //имя поля, по которому будет производиться сортировка
    switch ($sort_by) {
        case -2:
            $order_by_fld = "a.`" . $AUTHORIZE_BY . '`';
            break;
        case -1:
            $order_by_fld = "a.`User_ID`";
            break;
        case 0:
            $order_by_fld = "a.`PermissionGroup_ID`";
            break;
        default:
            foreach ($fld as $k => $v) {
                if ($fldID[$k] == $sort_by) {
                    $order_by_fld = "a.`" . $fld[$k] . "`";
                    break;
                }
            }
            break;
    }

    if (!$order_by_fld) {
        $order_by_fld = "g.`PermissionGroup_ID`";
    }
    $order = " ORDER BY " . $order_by_fld . ($sort_order ? " DESC" : " ASC");

    //параметры поиска
    $search_params = $nc_core->get_component('User')->get_search_query($srchPat);
    $fullSearchStr = $search_params['query'];

    // формирование ссылки, чтобы при переходе по навигации и при сортировке не сбивались рез-ты выборки
    $native_pars = "";
    if (is_array($grpID) && !empty($grpID)) {
        foreach ($grpID as $v) {
            $native_pars .= "&grpID[]=" . $v;
        }
    }

    if (is_array($rightsIds) && !empty($rightsIds)) {
        foreach ($rightsIds as $v) {
            $native_pars .= "&rightsIds[]=" . $v;
        }
    }
    if ($Checked) {
        $native_pars .= "&amp;Checked=" . $Checked;
    }
    if ($nonConfirmed) {
        $native_pars .= "&amp;nonConfirmed=" . $nonConfirmed;
    }
    $url = $native_pars . "&amp;" . $search_params['link'] . "&amp;curPos=" . $curPos . "&amp;objcount=" . $objcount;

    // -= Определение параметров выборки =-
    $tables = "";
    $where = " WHERE ug.`User_ID` = a.`User_ID` AND ug.`PermissionGroup_ID` = g.`PermissionGroup_ID` ";
    $where .= $fullSearchStr;

    // В выборке участвует группы
    if (is_array($grpID) && !empty($grpID)) {
        $user_in_group = array();

        foreach ($grpID as $v) {
            // Получим всех пользователей, находящихся в данной группе
            $user_in_group[] = nc_usergroup_get_users_from_group($v);
        }

        if (count($user_in_group) > 1) { // если выбрано больше одной группы, то массивы нудно объединить
            $to_eval = " \$users_id = array_intersect(";
            for ($i = 0; $i < count($user_in_group) - 1; $i++) {
                $to_eval .= " \$user_in_group[$i], ";
            }
            $to_eval .= " \$user_in_group[$i] );";
            eval($to_eval);
        } else { // выбрана одна группа
            $users_id = $user_in_group[0];
        }

        if (empty($users_id)) {
            $users_id[] = 0; // на случай, если ничего не нашлось
        }

        $where .= "AND a.`User_ID` IN ('" . implode("','", (array)$users_id) . "')";
    }

    // В выборке участвуют права
    if (is_array($rightsIds) && !empty($rightsIds)) {
        $tables .= ", `Permission` AS `p` ";
        $where .= " AND p.`AdminType` IN (" . implode(",", $rightsIds) . ") AND a.`User_ID` = p.`User_ID` ";
    }

    //условия выборки
    if ($nonConfirmed) {
        $where .= " AND a.`Confirmed` = 0 AND a.`RegistrationCode` <> '' ";
        $Checked = 2;
    }
    if ($UserID) {
        $where .= " AND a.`User_ID` = '" . (int)$UserID . "'";
    }
    if ($Checked === 1) {
        $where .= " AND a.`Checked` = 1";
    }
    if ($Checked === 2) {
        $where .= " AND a.`Checked` = 0";
    }


    // ограничение по количеству
    $limit = " LIMIT " . $curPos . "," . $objcount;


    // Основной запрос на выбору
    $select = "SELECT SQL_CALC_FOUND_ROWS a.`User_ID` AS `id`, a.`Checked` AS `checked`, a.`" . $AUTHORIZE_BY . "` AS `login`, `Email` AS `email`,
             g.`PermissionGroup_ID` AS `grp`, GROUP_CONCAT(CONCAT(g.`PermissionGroup_ID`, '. ', g.`PermissionGroup_Name`) SEPARATOR '<br>') AS `groupsList`
             FROM `User` AS `a`,
             `User_Group` AS `ug`,
             `PermissionGroup` AS `g`" . $tables
        . $where . " GROUP BY a.`User_ID` " .
        $order . $limit;

    $Users = $db->get_results($select, ARRAY_A);
    // общее количество пользователей
    $totRows = $db->get_var("SELECT FOUND_ROWS()");

    //Форма для выборки пользователей
    $searchForm = SearchUserForm($totRows);

    // листинг пользователей
    $listing = ListUserPages($totRows, $search_params['link'], $grpID, $Checked, $sort_by, $sort_order, $objcount,
        $rightsIds);

    // информация о количестве найденных пользователей
    if (false && $totRows) {
        echo ($isSearch ? CONTROL_USER_FUNCS_SEARCHEDUSER : CONTROL_USER_FUNCS_USERCOUNT) . ": " . $totRows . "\n";
    }

    echo "<div id='mainForm_c'>";
    echo $searchForm;
    echo $listing;

    /** @var Permission $perm */
    if (!empty($Users)) {
        $can_edit_any_user = $perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT,
            -1);  // Есть ли в принципе доступ к редактированию
        $can_delete_any_user = $perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_DEL,
            -1);  //                             и удалению
        $can_admin_any_user = $perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT,
            -1);  //                            и к правам
        ?>
        <form method='post' action='index.php' id='mainForm'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <table class='nc-table nc--striped nc--small' width='100%'>
                            <tr>
                                <th>
                                    <a href='?sort_by=-1&amp;sort_order=<?= ($sort_by == -1) ? !$sort_order :
                                        0 ?>&<?= $url ?>'>ID</a>
                                </th>
                                <th width="40%">
                                    <a href='?sort_by=-2&amp;sort_order=<?= ($sort_by == -2) ? !$sort_order :
                                        0 ?>&<?= $url ?>'><?= CONTROL_USER ?></a>
                                </th>
                                <th>
                                    <a href='?sort_by=0&amp;sort_order=<?= ($sort_by == 0) ? !$sort_order :
                                        0 ?>&<?= $url ?>'><?= CONTROL_USER_GROUP ?></a>
                                </th>
                                <? if ($can_edit_any_user) : ?>
                                    <th class='nc-text-right' width='25%'><?= CONTROL_USER_ACTIONS ?></th>
                                <? endif;
                                if ($can_admin_any_user): ?>
                                    <th class='nc-text-center'><?= CONTROL_USER_RIGHTS ?></th>
                                <? endif;
                                if ($can_delete_any_user): ?>
                                    <th class='nc-text-center'>
                                        <i class='nc-icon nc--remove'
                                                title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></i>
                                    </th>
                                <? endif; ?>
                            </tr>
                            <?
                            global $AUTH_USER_ID;
                            $is_saas_site = defined('NC_SAAS_SITE') && function_exists('saas_get_site_info');

                            foreach ($Users as $User) {
                                $can_edit_this_user = $perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT,
                                    $User['id']); // может ли редактировать данного пользователя
                                $is_current_user = $User['id'] == $perm->getUserID();

                                print "<tr>\n";
                                print "<td >" . $User['id'] . "</td>\n";
                                print "<td >\n";
                                if ($can_edit_this_user) {
                                    print "<a href=\"index.php?phase=4&UserID=" . $User['id'] . "\" " .
                                        (!$User['checked'] ? "style='color:#cccccc;'" : "") . ">\n";
                                }
                                print (($AUTHORIZE_BY != "User_ID") && !empty($User['login'])) ? $User['login'] :
                                    $User['email'];
                                print "</a></td>";

                                print "<td nowrap>" . $User['groupsList'] . "</td>";

                                if ($can_edit_any_user) {
                                    print "<td align=right nowrap>\n";
                                    if ($can_edit_this_user) {
                                        if (!$is_current_user) {
                                            print "<a href=\"index.php?" . $nc_core->token->get_url() .
                                                "&phase=12&UserID=" . $User['id'] . "\">" .
                                                ($User['checked'] ? NETCAT_MODERATION_TURNTOOFF :
                                                    NETCAT_MODERATION_TURNTOON) . "</a>";
                                            if (CMS_SYSTEM_NAME === 'Netcat') {
                                                print ' | ';
                                            }
                                        }

                                        if (CMS_SYSTEM_NAME === 'Netcat') {
                                            $link = "index.php?phase=6&UserID={$User['id']}";

                                            if ($is_saas_site && $AUTH_USER_ID == 1) {
                                                $saas_site_info = saas_get_site_info();
                                                $link = nc_array_value($saas_site_info, 'leave_saas_url', '#');
                                            }

                                            $target = $is_saas_site && $AUTH_USER_ID == 1 ? '_blank' : '_self';
                                            print "<a href='$link' target='$target'>" . CONTROL_USER_CHANGEPASS . "</a>\n";
                                        }
                                    }

                                    print "</td>\n";
                                }

                                if ($can_admin_any_user) {
                                    print "<td align=center>\n";
                                    if ($perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, $User['id'])) {
                                        print "<a href=\"index.php?phase=8&UserID=" . $User['id'] .
                                            "\"><i class='nc-icon nc--settings nc--hovered' title='" .
                                            CONTROL_USER_FUNCS_EDITACCESSRIGHT . "'></div></a>";
                                    }
                                    print "</td>";
                                }

                                if ($can_delete_any_user) {
                                    print "<td align=center>\n";
                                    if ($perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_DEL, $User['id'])) {
                                        print nc_admin_checkbox_simple("User" . $User['id'], $User['id']);
                                    }
                                    print "</td>\n";
                                }
                                print "</tr>\n";
                            }
                            ?>
                        </table>
                    </td>
                </tr>
            </table>&nbsp;<br/>
            <?php
            global $UI_CONFIG;
            if ($perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_ADD)) {
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "adduser",
                    "caption" => CONTROL_USER_REG,
                    "align" => "left",
                    "location" => "user.add()",
                );
            }

            if ($can_delete_any_user) {
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => NETCAT_ADMIN_DELETE_SELECTED,
                    "align" => "right",
                    "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form(14)",
                    "red_border" => true,
                );
            }
            ?>
            <script type='text/javascript'>
                function sumbit_form(phase) {
                    document.getElementById("mainForm").phase.value = phase;
                    parent.mainView.submitIframeForm("mainForm");
                    return 0;
                }
            </script>
            <input type='hidden' name=phase id='phase' value='14' />
            <?= $nc_core->token->get_input() ?>
        </form>
        <?
        echo $listing;
    } else {
        nc_print_status(CONTROL_USER_MSG_USERNOTFOUND, 'info');
    }
    echo "</div>&nbsp;<br />";

}

###############################################################################

function GroupList() {
    global $db, $ROOT_FOLDER;
    global $Email;
    global $srchPat;
    global $systemTableID, $systemMessageID, $systemTableName, $ADMIN_TEMPLATE;
    /** @var Permission $perm */
    global $perm;

    $can_admin_any_group = $perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_RIGHT);
    $can_delete_any_group = $perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_DEL);

    $nc_core = nc_core::get_object();
    $link = $nc_core->ADMIN_PATH . '#usergroup';

    $groups = $db->get_results("SELECT PermissionGroup_ID, PermissionGroup_Name FROM PermissionGroup ", ARRAY_N);

    ?>
    <form method=post action=group.php>
        <table border=0 cellpadding=0 cellspacing=0 width=100%>
            <tr>
                <td>
                    <table class='nc-table nc--striped nc--small' width='100%'>
                        <tr>
                            <th>ID</th>
                            <th width='<?= $can_delete_any_group ? 80 : 95 ?>%'><?= CONTROL_USER_GROUP ?></th>
                            <th class='nc-text-center'><?= $can_admin_any_group ? CONTROL_USER_RIGHTS : '' ?></th>
                            <?php if ($can_delete_any_group): ?>
                                <th class='nc-text-center'><i class='nc-icon nc--remove'
                                            title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></i>
                                </th>
                            <?php endif; ?>
                        </tr>
                        <?php
                        foreach ($groups as $group) {
                            list($id, $name) = $group;

                            print "<tr>";

                            // Название
                            print "<td>$id</td><td>";
                            if ($perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_EDIT, $id)) {
                                echo "<a href='$link.edit($id)' target='_top'>$name</a>";
                            } else {
                                echo $name;
                            }
                            print "</td>";

                            // Права
                            print "<td align='center'>";
                            if ($perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_RIGHT, $id)) {
                                print "<a href='$link.rights($id)' target='_top'><i class='nc-icon nc--settings nc--hovered' title='" .
                                    CONTROL_USER_CHANGERIGHTS . "'></i></a>";
                            }
                            print "</td>";

                            // Удалить
                            if ($can_delete_any_group) {
                                if ($perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_DEL, $id)) {
                                    print "<td align='center'>" . nc_admin_checkbox_simple("Delete$id", $id) . "</td>";
                                } else {
                                    print "<td align='center'></td>";
                                }
                            }
                            print "</tr>";
                        }
                        ?>
                    </table>
                </td>
            </tr>
        </table>
        <br>
        <?php
        global $UI_CONFIG;

        if ($can_delete_any_group) {
            $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_ADMIN_DELETE_SELECTED,
                "action" => "nc_print_custom_modal_callback(function(){mainView.submitIframeForm();})",
                "red_border" => true,
            );
        }

        if ($perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_ADD)) {
            $UI_CONFIG->actionButtons[] = array(
                "id" => "adduser",
                "caption" => CONTROL_USER_ADDNEWGROUP,
                "align" => "left",
                "location" => "usergroup.add()",
            );
        }
        ?>
        <input type='hidden' name='phase' value='2'>
        <?= $nc_core->token->get_input() ?>
    </form>
    <?
}


/**
 * Форма для добавления \ изменения пользователя
 *
 * @param int $UserID UserID
 * @param string $action_file action file
 * @param int $phase next phase
 * @param int $type type: 1 - insert; 2 - update
 *
 * @throws Exception
 */
function UserForm($UserID, $action_file, $phase, $type) {
    /** @var Permission $perm */
    global $nc_core, $db, $ROOT_FOLDER, $admin_mode, $perm;
    global $HTTP_FILES_PATH, $FILES_FOLDER;
    global $systemTableID, $systemMessageID, $systemTableName;
    global $Checked, $PermissionGroupID, $InsideAdminAccess;
    global $INCLUDE_FOLDER, $ADMIN_PATH;

    $UserID = (int)$UserID;
    $Checked = (int)$nc_core->input->fetch_post_get('Checked');
    $InsideAdminAccess = (int)$nc_core->input->fetch_post_get('InsideAdminAccess');
    $PermissionGroupID = (int)$nc_core->input->fetch_post_get('PermissionGroupID');
    $Catalogue_ID = (int)$nc_core->input->fetch_post_get('Catalogue_ID');
    $Password1 = $nc_core->input->fetch_post_get('Password1');
    $Password2 = $nc_core->input->fetch_post_get('Password2');
    $posting = (int)$nc_core->input->fetch_post_get('posting');

    //есть ли файлы
    $is_there_any_files = getFileCount(0, $systemTableID);

    $st = new nc_Component(0, 3);
    foreach ($st->get_fields() as $v) {
        $name = 'f_' . $v['name'];
        global $$name;
        if ($v['type'] == NC_FIELDTYPE_FILE) {
            global ${$name . "_old"};
            global ${"f_KILL" . $v['id']};
        }
    }

    if ($type == 1) {
        $User['Checked'] = $Checked;
        $User['PermissionGroup_ID'] = $PermissionGroupID;
        $User['InsideAdminAccess'] = $InsideAdminAccess;
    } elseif ($type == 2) {
        $User = $db->get_row("SELECT `Checked`,  `InsideAdminAccess`, `Catalogue_ID`
                          FROM `User`
                          WHERE `User_ID`='" . $UserID . "'", ARRAY_A);
        if (!$User) {
            nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DBERROR, 'error');
            exit();
        }
        // узнаем группы, где он состоит
        $User['PermissionGroup_ID'] = nc_usergroup_get_group_by_user($UserID);
    }

    // Блокировка из-за 2FA
    $is_blocked =
        $UserID &&
        $nc_core->get_settings('AuthCodeMode') &&
        nc_security_2fa::for_user($UserID)->is_user_blocked();

    if ($is_blocked && $phase == 5 && $nc_core->input->fetch_post('unblock')) {
        nc_security_2fa::for_user($UserID)->unblock_user();
        $is_blocked = false;
    }

    if ($is_blocked) {
        $blocked_message = NETCAT_USER_2FA_BLOCKED;
        if (empty($User['ncBlockedPermanently']) && !empty($User['ncBlockedTimestamp'])) {
            $unblock_timestamp = $User['ncBlockedTimestamp'] + $nc_core->get_settings('AuthCodeUnblockMinutes') * 60;
            $blocked_message .= ' ' . NETCAT_USER_2FA_BLOCKED_UNTIL . ' ' . date(NETCAT_CONDITION_DATE_FORMAT . ' H:i:s', $unblock_timestamp);
        }
        nc_print_status($blocked_message, 'info');
    }

    echo "<br /><form name='adminForm' class='nc-form' id='adminForm' " .
        ($is_there_any_files ? "enctype='multipart/form-data'" : "") . " method='post' action='" . $action_file . "'>";

    if ($type == 2) {
        echo "ID: $UserID&nbsp;&nbsp;";
    }

    // включен / выключен
    echo nc_admin_checkbox_simple('Checked', 1, CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON, $User['Checked'],
            'chk') . "&nbsp;&nbsp;";
    // доступ в админку
    echo nc_admin_checkbox_simple('InsideAdminAccess', 1, NETCAT_MODULE_AUTH_INSIDE_ADMIN_ACCESS,
            $User['InsideAdminAccess']) . "&nbsp;&nbsp;";

    if ($is_blocked) {
        echo nc_admin_checkbox_simple('unblock', 1, NETCAT_USER_2FA_UNBLOCK, 0) . "&nbsp;&nbsp;";
    }

    echo "<br /><br />";

    // PermissionGroupID
    //$UserPermGroupID = ($PermissionGroupID ? (int)$PermissionGroupID : $Array['PermissionGroup_ID']);
    // Группы пользователей
    $Result = $db->get_results("SELECT `PermissionGroup_ID`, `PermissionGroup_Name` FROM `PermissionGroup` ORDER BY `PermissionGroup_ID`",
        ARRAY_A);

    echo (count($Result) == 1 ? CONTROL_USER_GROUP : CONTROL_USER_GROUPS) . ":<br>";
    echo "<div style='overflow-y: auto; overflow-x: hidden; height: 130px; white-space:nowrap; display: inline-block; padding: 5px 25px 5px 5px; '><br>";
    foreach ((array)$Result as $Group) {
        $id = $Group['PermissionGroup_ID'];
        $name = $Group['PermissionGroup_Name'];
        // выключить группы с бо́льшими правами
        $disabled = (!$perm->isSupervisor() && !$perm->hasAllRightsOf(new Permission(0, $id))) ? 'disabled' : '';
        echo nc_admin_checkbox_simple("PermissionGroupID[" . $id . "]", $id, $id . ":" . $name,
                in_array($id, (array)$User['PermissionGroup_ID']), "grp_" . $id, $disabled) . "<br>";
    }
    echo "</div><br/>";

    // если есть модуль авторизации, то можно выбрать сайт, где user сможет авторизоваться
    if (nc_module_check_by_keyword('auth')) {
        // Catalogue_ID
        $UserCatID = (isset($_POST['Catalogue_ID']) ? (int)$_POST['Catalogue_ID'] :
            nc_array_value($User, 'Catalogue_ID'));
        $Result = $db->get_results("SELECT Catalogue_ID, Catalogue_Name FROM Catalogue", ARRAY_N);
        echo CONTROL_AUTH_ON_ONE_SITE . ":<br><select name='Catalogue_ID'><option value='0'" .
            (!$UserCatID ? " selected" : "") . ">" . CONTROL_AUTH_ON_ALL_SITES . "</option>";
        foreach ($Result as $row) {
            echo "<option value='" . $row[0] . "'" . (nc_array_value($User, 'Catalogue_ID') == $row[0] ? " selected" : "") . ">" .
                $row[0] . '. ' . $row[1] . "</option>";
        }
        echo "</select><br><br>";
    }


    if ($type == 1) {
        if (CMS_SYSTEM_NAME === 'Netcat') {
            echo CONTROL_AUTH_HTML_PASSWORD .
                ":<br><input type='password' name='Password1' size='30' maxlength='50' value='" . htmlspecialchars($Password1) .
                "'><br><br>";
            echo CONTROL_AUTH_HTML_PASSWORDCONFIRM .
                ":<br><input type='password' name='Password2' size='30' maxlength='50' value='" . htmlspecialchars($Password2) . "'>";
        } else {
            $random_password = md5(mt_rand(6, 100) . time());
            echo "<input type='hidden' name='Password1' value='$random_password'>";
            echo "<input type='hidden' name='Password2' value='$random_password'>";
        }
        $action = "add";
        $nc_core->action = "add";
    } elseif ($type == 2) {
        $action = "change";
        $nc_core->action = "change";
        $message = $systemMessageID;
    }

    require $ROOT_FOLDER . "message_fields.php";

    if ($fldCount) {
        if ($type == 2) {
            $fieldQuery = implode(",", $fld);
            $fldValue = $db->get_row("select $fieldQuery from User where User_ID='" . $systemMessageID . "'", ARRAY_N);
        }
        ?>
        <br/>
        <style>.nc_admin_form_body > span {
                display: block;
            }</style>
        <fieldset>
            <legend><?= CONTROL_USER_TITLE_USERINFOEDIT ?></legend>
            <div class='nc_admin_form_body nc-admin'>
                <?
                $nc_notmodal = 1;
                require $ROOT_FOLDER . "message_edit.php"; ?>
            </div>
        </fieldset>
        <?
    } else {
        ?>
        <hr size="1" color="CCCCCC"><?
    }
    print "<input type='hidden' name='UserID' value='" . $UserID . "' />";
    print "<input type='hidden' name='posting' value='1' />";
    ?>
    <div align="right">
        <?
        global $UI_CONFIG;
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => ($type == 1 ? CONTROL_USER_FUNCS_ADDUSER : CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE),
            "action" => "mainView.submitIframeForm()",
        );
        ?>
    </div>

    <?php if (nc_module_check_by_keyword('auth')): ?>
        <?php
        $nc_auth_token = new nc_auth_token();
        $logins = $nc_auth_token->get_logins($UserID);
        ?>

        <fieldset>
            <legend><?= NETCAT_SETTINGS_USETOKEN ?></legend>
            <?php if (!empty($logins)): ?>
                <input type="hidden" id="nc_token_destroy" name="nc_token_destroy" value=""/>
                <div style="margin-bottom: 5px; font-weight: bold;"><?= CONTROL_AUTH_TOKEN_CURRENT_TOKENS ?>:</div>

                <?php foreach ($logins as $id => $login): ?>
                    <div style="margin: 0 0 3px 5px;">
                        <span style="margin-right: 0.5rem;"><?= $login ?></span>
                        <a href="#"
                                onclick="delete_token_container(<?= "'$id', '$login'" ?>); return false;"><?= NETCAT_MODERATION_DELETE ?></a>
                    </div>
                <?php endforeach ?>
            <?php endif ?>

            <div style="margin: 10px 0; font-weight: bold;"><?= CONTROL_AUTH_TOKEN_NEW ?></div>
            <div id="token_error" class="nc-alert nc--red" style="display:none;"></div>
            <div><?= CONTROL_AUTH_HTML_LOGIN ?>: <br/>
                <input name="nc_token_login" id="nc_token_login"/>
                <input type="hidden" name="nc_token_key" id="nc_token_key" value=""/>
                <input type="button" onclick="create_token_container()" value="<?= CONTROL_AUTH_TOKEN_NEW_BUTTON ?>"
                        title="<?= CONTROL_AUTH_TOKEN_NEW_BUTTON ?>"/>
            </div>
        </fieldset>

        <script src="<?= nc_module_path('auth') . 'rutoken.js' ?>"></script>
        <script src="<?= nc_module_path('auth') . 'RutokenService.js' ?>"></script>
        <script src="<?= nc_add_revision_to_url(nc_module_path('auth') . 'auth.js') ?>"></script>

        <script>
            const nc_token_obj = new nc_auth_token({ "token_id": "nc_token_key" });

            async function create_token_container() {
                $nc("#token_error").hide();

                try {
                    await nc_token_obj.reg();
                    document.getElementById("adminForm").submit();
                } catch (e) {
                    $nc("#token_error").html(e);
                    $nc("#token_error").show();
                    console.error(e);
                }
            }

            async function delete_token_container(id, name) {
                if (confirm("<?= NETCAT_MODERATION_DELETE ?>")) {
                    try {
                        await nc_token_obj.attempt_delete(name);
                        $nc("#nc_token_destroy").val(id);
                    } catch (e) {
                        console.error(e);
                    } finally {
                        document.getElementById("adminForm").submit();
                    }
                }
            }

        </script>
    <?php endif ?>

    <input type='hidden' name='phase' value='<?= $phase ?>'>
    <?= $nc_core->token->get_input(); ?>
    </form>

<?php } // UserForm ?>


<?php
function ActionUserCompleted($action_file, $type) {
    if (!nc_core::get_object()->token->verify()) {
        nc_print_status(NETCAT_TOKEN_INVALID, "error");
        EndHtml();
        exit();
    }

    /** @var Permission $perm */
    global $nc_core, $db, $ROOT_FOLDER, $perm, $systemTableID, $AUTHORIZE_BY;

    $params = array(
        'Checked',
        'InsideAdminAccess',
        'PermissionGroupID',
        'Catalogue_ID',
        'Password1',
        'Password2',
        'UserID',
        'posting',
    );

    foreach ($params as $v) {
        global $$v;
    }

    $st = new nc_Component(0, 3);

    foreach ($st->get_fields() as $v) {
        $name = 'f_' . $v['name'];

        global $$name;

        if ($v['type'] == NC_FIELDTYPE_FILE) {
            global ${$name . "_old"};
            global ${"f_KILL" . $v['id']};
        }

        if ($v['type'] == NC_FIELDTYPE_DATETIME) {
            global ${$name . "_day"};
            global ${$name . "_month"};
            global ${$name . "_year"};
            global ${$name . "_hours"};
            global ${$name . "_minutes"};
            global ${$name . "_seconds"};
        }
    }

    $UserID = intval($UserID);
    $Checked = intval($Checked);

    $ret = 0; // возвращаемое значение (текст ошибки или 0)
    $is_there_any_files = getFileCount(0, $systemTableID);

    $user_table_mode = true;

    if ($type == 1) {
        $action = "add";
        $nc_core->action = "add";
    } else {
        $action = "change";
        $nc_core->action = "change";
        $message = $UserID;
    }

    if (empty($Priority)) {
        $Priority = 0;
    }

    $Priority += 0;

    require $ROOT_FOLDER . "message_fields.php";

    if ($posting == 0) {
        return $warnText;
    }

    require $ROOT_FOLDER . "message_put.php";

    if (empty($PermissionGroupID)) {
        return CONTROL_USER_FUNC_GROUP_ERROR;
    }

    // значение, которое пойдет в таблицу User
    // для совместимости со старыми версиями
    $mainPermissionGroupID = intval(min($PermissionGroupID));

    // нельзя добавить в группу с бо́льшими правами
    if (!$perm->isSupervisor()) {
        foreach ($PermissionGroupID as $id) {
            if (!$perm->hasAllRightsOf(new Permission(0, $id))) {
                return NETCAT_MODERATION_ERROR_NORIGHT;
            }
        }
    }

    $Login = ${'f_' . $AUTHORIZE_BY};

    if ($type == 1) {
        $Password = $Password1;

        for ($i = 0; $i < $fldCount; $i++) {
            if (isset(${$fld[$i] . 'Defined'}) && ${$fld[$i] . 'Defined'} == true) {
                $fieldString .= "`" . $fld[$i] . "`,";
                $valueString .= ${$fld[$i] . 'NewValue'} . ",";
            }
        }

        $insert = "INSERT INTO User ( " . $fieldString;
        $insert .= "PermissionGroup_ID, Catalogue_ID, Password, Checked, Created,InsideAdminAccess) values ( " . $valueString;
        $insert .= "'" . $mainPermissionGroupID . "', ";

        if (isset($_POST['Catalogue_ID'])) {
            $insert .= +$_POST['Catalogue_ID'] . ", ";
        } else {
            $insert .= "0, ";
        }

        $insert .= $nc_core->MYSQL_ENCRYPT . "('" . $Password . "'),'$Checked','" . date("Y-m-d H:i:s") . "', '" .
            (int)$InsideAdminAccess . "')";

        // execute core action
        $nc_core->event->execute(nc_Event::BEFORE_USER_CREATED, 0);

        $Result = $db->query($insert);
        $UserID = $db->insert_id;
        $message = $UserID;

        if ($Result) {
            nc_print_status(CONTROL_USER_NEW_ADDED, 'ok');

            foreach ($PermissionGroupID as $v) {
                nc_usergroup_add_to_group($UserID, $v, false);
            }

            //постобработка файлов с учетом нового $message
            $nc_core->files->field_save_file_afteraction($message);
            $nc_core->event->execute(nc_Event::AFTER_USER_CREATED, $message);
        } else {
            return CONTROL_USER_NEW_NOTADDED . "<br/>" . sprintf(NETCAT_ERROR_SQL, $db->last_query, $db->last_error);
        }
    }

    if ($type == 2) {
        $cur_checked = $db->get_var("SELECT `Checked` FROM `User` WHERE `User_ID` = '" . $UserID . "'");
        $update = "UPDATE User SET ";
        for ($i = 0; $i < $fldCount; $i++) {
            if (
                $fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE ||
                ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
            ) {
                continue;
            }

            if (isset(${$fld[$i] . 'Defined'}) && ${$fld[$i] . 'Defined'} == true) {
                $update .= $fld[$i] . "=" . ${$fld[$i] . 'NewValue'} . ",";
            } else {
                $update .= $fld[$i] . "=" . ($fldValue[$i] ? $fldValue[$i] : "NULL") . ",";
            }

        }

        $update .= "Checked=\"" . $Checked . "\",";
        $update .= "PermissionGroup_ID=\"" . $mainPermissionGroupID . "\",";
        $update .= "InsideAdminAccess=" . (int)$InsideAdminAccess;

        if (isset($_POST['Catalogue_ID'])) {
            $update .= ", Catalogue_ID=" . (int)$_POST['Catalogue_ID'];
        }

        $update .= " where User_ID=" . $UserID;

        // execute core action
        $nc_core->event->execute(nc_Event::BEFORE_USER_UPDATED, $UserID);
        if ($cur_checked != $Checked) {
            $nc_core->event->execute($Checked ? nc_Event::BEFORE_USER_ENABLED : nc_Event::BEFORE_USER_DISABLED,
                $UserID);
        }

        $Result = $db->query($update);
        // execute core action
        $nc_core->event->execute(nc_Event::AFTER_USER_UPDATED, $UserID);

        $db->query("DELETE FROM `User_Group` WHERE `User_ID`='" . intval($UserID) . "'");

        foreach ($PermissionGroupID as $v) {
            nc_usergroup_add_to_group($UserID, $v, 0);
        }

        // произошла смена состояния пользователя
        if ($cur_checked != $Checked) {
            $nc_core->event->execute($Checked ? nc_Event::AFTER_USER_ENABLED : nc_Event::AFTER_USER_DISABLED, $UserID);
        }
    }

    $nc_multifield_field_names = $nc_core->get_component('User')->get_fields(NC_FIELDTYPE_MULTIFILE, false);

    foreach ($nc_multifield_field_names as $nc_multifield_field_name) {
        nc_multifield_saver::save_from_post_data('User', $UserID, ${"f_{$nc_multifield_field_name}"}, $action === 'add');
    }

    // привязка токена
    $nc_token_login = $nc_core->input->fetch_get_post('nc_token_login');
    $nc_token_key = $nc_core->input->fetch_get_post('nc_token_key');

    if ($nc_token_login && $nc_token_key && $UserID) {
        $db->query("INSERT INTO `Auth_Token`
                  SET `Login` = '" . $db->escape($nc_token_login) . "',
                      `PublicKey` = '" . $db->escape($nc_token_key) . "',
                      `User_ID` = '" . $UserID . "' ");
    }


    $nc_token_destroy = $nc_core->input->fetch_get_post('nc_token_destroy');

    if ($nc_token_destroy) {
        $nc_auth_token = new nc_auth_token();
        $nc_auth_token->delete_by_id($nc_token_destroy);
    }

    return 0;
}

###############################################################################

function GroupForm($PermissionGroupID, $action, $phase, $type) {
    # type = 1 - это insert
    # type = 2 - это update
    global $db, $ADMIN_PATH, $nc_core, $AUTHORIZE_BY;

    /** @var Permission $perm */
    global $perm;

    $PermissionGroupID = intval($PermissionGroupID);

    if ($type == 2) {
        $Group = $db->get_row("SELECT `PermissionGroup_Name`
                               FROM `PermissionGroup`
                               WHERE `PermissionGroup_ID`='" . $PermissionGroupID . "'", ARRAY_A);
        if (!$Group) {
            nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DBERROR, 'error');

            return;
        }
    }
    ?>
    <?php if ($perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_EDIT, $PermissionGroupID, 0)): ?>
        <br/>
        <form method="post" action="<?= htmlspecialchars($action) ?>">
            <?= CONTROL_USER_GROUPNAME ?>:<br>
            <?= nc_admin_input_simple('PermissionGroupName', isset($Group['PermissionGroup_Name']) ? $Group['PermissionGroup_Name'] : '', 50, "maxlength='64'") ?>
            <input type='hidden' name='PermissionGroupID' value='<?= $PermissionGroupID ?>'>

            <?php
            global $UI_CONFIG;
            $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => ($type == 1 ? CONTROL_USER_ADDNEWGROUP :
                    CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE),
                "action" => "mainView.submitIframeForm()",
            );
            ?>
            <input type='hidden' name='phase' value='<?= $phase ?>' />
            <?= $nc_core->token->get_input() ?>
        </form>
    <?php endif; ?>
    <br/>
    <?php
    // Покажем всех пользователей группы
    if ($type == 2) {
        $users = $db->get_results("SELECT u.* FROM `User` as `u`, `User_Group` as `ug` WHERE u.User_ID = ug.User_ID AND ug.PermissionGroup_ID = '" .
            $PermissionGroupID . "' ", ARRAY_A);

        if ($users) {
            echo CONTROL_USER_GROUP_MEMBERS . " (" . CONTROL_USER_GROUP_TOTAL . " " . count($users) . "):";
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li><a href='" . $ADMIN_PATH . "user/index.php?phase=4&amp;UserID=" . $user['User_ID'] . "'>" .
                    htmlspecialchars($user[$AUTHORIZE_BY] ?:
                        ($user['ForumName'] ?: nc_array_value($user, 'Name', $user['User_ID']))) . "</a>";
                switch ($user['UserType']) {
                    case 'vk':
                        echo ' (vkontakte.ru)';
                        break;
                    case 'fb':
                        echo ' (facebook.com)';
                        break;
                    case 'twitter':
                        echo ' (twitter.com)';
                        break;
                    case 'openid':
                        echo ' (OpenID)';
                        break;
                    case 'oauth':
                        echo ' (OAuth)';
                        break;
                }
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo CONTROL_USER_GROUP_NOMEMBERS;
        }
    }
}

###############################################################################

/**
 * Изменить или добавить группу с прорисовкой дерева
 *
 * @param int type - тип операции: 1 - добавить группу. 2 - обновить группк
 *
 * @return bool true - удачно, false - неудачно
 */
function ActionGroupCompleted($type) {
    global $db, $UI_CONFIG;

    $PermissionGroupID = $_POST['PermissionGroupID'];
    $PermissionGroupName = $_POST['PermissionGroupName'];
    $ret = false; // возвращаемое значение

    if ($type == 1) { // добавить группу
        $ret = nc_usergroup_create($PermissionGroupName);
        if ($ret) { // обновить дерево
            $UI_CONFIG->treeChanges['addNode'][] = array(
                "nodeId" => "usergroup-$ret",
                "name" => $PermissionGroupName,
                "href" => "#usergroup.edit($ret)",
                "image" => "icon_usergroups",
                "hasChildren" => false,
                "parentNodeId" => "usergroup",
            );
        } else {
            nc_print_status(CONTROL_USER_ERROR_GROUPNAME_IS_EMPTY, 'error');
        }
    } else if ($type == 2) { // переименовать группу
        $ret = nc_usergroup_rename($PermissionGroupID, $PermissionGroupName);
        if ($ret) {
            $UI_CONFIG->treeChanges['updateNode'][] = array(
                "nodeId" => "usergroup-$PermissionGroupID",
                "name" => "$PermissionGroupName",
            );
        } else {
            nc_print_status(CONTROL_USER_ERROR_GROUPNAME_IS_EMPTY, 'error');
        }
    }

    return $ret;
}

###############################################################################

function ChangePasswordFormAdmin($UserID) {
    global $nc_core;
    ?>

    <form method=post action=index.php>
        <?= CONTROL_USER_NEWPASSWORD ?>:<br>
        <input type=PASSWORD name=Password1 size=30 maxlength=32><br><br>
        <?= CONTROL_USER_NEWPASSWORDAGAIN ?>:<br>
        <input type=PASSWORD name=Password2 size=30 maxlength=32><br>
        <input type='hidden' name=UserID value=<?= $UserID ?>>
        <input type='hidden' name=phase value=7>
        <?
        global $UI_CONFIG;
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
            "action" => "mainView.submitIframeForm()",
        );
        ?>
        <input type='submit' class='hidden'>
        <?php echo $nc_core->token->get_input(); ?>
    </form>
    <?
}

/**
 * Show user permission
 *
 * @param int $UserID - user id
 * @param int $phase phase in hidden
 * @param string $action action in form
 * @param int $PermissionGroupID PermissionGroupID
 */
function ShowUserPermissions($UserID, $phase, $action = "index.php", $PermissionGroupID = 0) {
    /** @var Permission $perm */
    global $db, $UI_CONFIG, $nc_core;
    global $USER_PERM_ARRAY, $USER_PERM_COUNT;
    global $perm, $ADMIN_PATH, $ADMIN_TEMPLATE;

    // удалим права, у которых закончился "срок действия"
    Permission::DeleteObsoletePerm();

    $allPerm = Permission::GetAllPermission($UserID, $PermissionGroupID);
    $count_td_colspan = NC_PERM_COUNT_PERM;
    $module_subscribe = nc_module_check_by_keyword('subscriber', false);
    $module_comments = nc_module_check_by_keyword('comments');

    $module_permissions = nc_get_module_permission_info($UserID, $PermissionGroupID);

    if (!$module_subscribe) {
        $count_td_colspan--;
    }
    if (!$module_comments) {
        $count_td_colspan--;
    }

    if (!empty($allPerm) || !empty($module_permissions)) { // User has rights
        ?>
        <form method='post' action='<?= $action ?>'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
        <tr>
        <td>
        <table class='admin_table permission_table' style='width:100%'>
        <tr>
            <th class='align-center' width='50%' rowspan='2'><?= SECTION_INDEX_USER_RIGHTS_TYPE ?></th>
            <th class='align-center' colspan='<?= $count_td_colspan ?>'>
                <?= SECTION_INDEX_USER_RIGHTS_RIGHTS ?></th>
            <th class='align-center' rowspan='2'>
                <?= CONTROL_USER_RIGHTS_LIVETIME ?>
            </th>
            <th align='center' rowspan='2'>
                <div class='icons icon_delete'
                        title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div>

            </th>

        </tr>
        <tr>
        <?php
        for ($i = 0; $i < NC_PERM_COUNT_PERM; $i++) {
            $name = (($i == NC_PERM_READ_ID) ? CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_VIEW :
                (($i == NC_PERM_COMMENT_ID) ? CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_COMMENT :
                    (($i == NC_PERM_ADD_ID) ? CONTROL_CONTENT_CATALOUGE_ADD :
                        (($i == NC_PERM_EDIT_ID) ? CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CHANGE :
                            (($i == NC_PERM_CHECKED_ID) ? CONTROL_CLASS_ACTIONS_CHECKED :
                                (($i == NC_PERM_DELETE_ID) ? CONTROL_CLASS_ACTIONS_DELETE :
                                    (($i == NC_PERM_SUBSCRIBE_ID) ?
                                        CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SUBSCRIBE :
                                        (($i == NC_PERM_MODERATE_ID) ? CONTROL_CLASS_ACTIONS_MODERATE :
                                            (($i == NC_PERM_ADMIN_ID) ? CONTROL_CLASS_ACTIONS_ADMIN : '')))))))));

            if (!$module_subscribe && $i == NC_PERM_SUBSCRIBE_ID) {
                continue;
            }
            if (!$module_comments && $i == NC_PERM_COMMENT_ID) {
                continue;
            }
            print "<th width='8%' class='align-center'>" . $name . "</th>";
        }
        print "</tr>";

        foreach ((array)$allPerm as $k => $v) {
            /* $k - Permission ID
              $v - array with perm. parametrs: title, live, ..
             */

            $own_mask = $perm->calculateMask($v['AdminType'], $v['Catalogue_ID']);
            $matches_own_mask = $own_mask === false || !($v['PermissionSet'] & ~(int)$own_mask);

            print "<tr>\n";
            print "<td>\n" . $v['title'] . "\n</td>\n";
            if ($v[NC_PERM_READ_ID]['checkbox'] == -1) { // Director, Supervisor, Guest
                print "<td colspan='" . $count_td_colspan . "'>\n";
            } else { // editor, moderator, developer
                for ($i = 0; $i < NC_PERM_COUNT_PERM; $i++) {
                    if (!$module_subscribe && $i == NC_PERM_SUBSCRIBE_ID) {
                        continue;
                    }
                    if (!$module_comments && $i == NC_PERM_COMMENT_ID) {
                        continue;
                    }
                    $hidden = ""; //Скрытые поля требуются для передачи данных недоступных чекбоксов
                    print "<td align='center'>\n";
                    if ($v[$i]['checkbox'] != 3) { // не 3 - это 0, 1 или 2 - показываем чекбокс
                        $checkedAttr = '';
                        if ($v[$i]['checkbox'] == 1) {
                            $checkedAttr = " checked ";
                        }
                        if ($v[$i]['checkbox'] == 2) {
                            $checkedAttr = " checked disabled ";
                            $hidden = "<input type='hidden' name='PermissionID" . $k . "x" . $i . "' value='" .
                                $v[$i]['mask'] . "'>";
                        } else if (!$matches_own_mask || ($own_mask !== false && !($v[$i]['mask'] & $own_mask))) {
                            $checkedAttr .= " disabled";
                        }

                        print nc_admin_checkbox_simple("PermissionID" . $k . "x" . $i, $v[$i]['mask'], '', false, '',
                            "class='wh'" . $checkedAttr);
                        print $hidden;
                    }
                    print "</td>\n";
                }
            }
            print "<td>" . $v['live'] . "</td>\n";
            $disabled = $matches_own_mask ? '' : ' disabled';
            print "<td>" . nc_admin_checkbox_simple("Delete$k", $k, '', false, '', "class='wh'$disabled") . "</td>\n";
            print "</tr>\n";
        }

        foreach ($module_permissions as $row) {
            print "<tr>";
            print "<td colspan='" . ($count_td_colspan + 1) . "'>$row[description]</td>";
            print "<td>$row[valid]</td>";
            $disabled = $row['can_change'] ? '' : ' disabled';
            print "<td>" . nc_admin_checkbox_simple("deleted_module_permissions[]", $row['id'], '', false, '',
                    "class='wh'$disabled") . "</td>\n";
            print "</tr>";
        }

        print "</table><br>";

        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
            "action" => "mainView.submitIframeForm()",
        );
    } else {
        nc_print_status($UserID ? CONTROL_USER_FUNCS_NORIGHTS : CONTROL_USER_FUNCS_GROUP_NORIGHTS, 'info');
    }

    $UI_CONFIG->actionButtons[] = array(
        "id" => "addrights",
        "caption" => CONTROL_USER_FUNCS_ADDNEWRIGHTS,
        "align" => "left",
        "action" => "mainView.loadIframe('" . $ADMIN_PATH . "user/" . $action . "?phase=9&UserID=" . $UserID .
            "&PermissionGroupID=" . $PermissionGroupID . "')",
    );
    ?>
    <?php echo $nc_core->token->get_input(); ?>
    <input type='hidden' name='phase' value='<?= $phase ?>'>
    <input type='hidden' name='UserID' value='<?= $UserID ?>'>
    <input type='hidden' name='PermissionGroupID' value='<?= $PermissionGroupID ?>'>
    <input type='submit' class='hidden'>
    </form>
    <?
}

###############################################################################

function ConfirmDeleteUsers() {
    global $db, $nc_core;

    $num_user = 0;
    $html = "<ul>\n<form action ='index.php' method = 'post'>\n";
    $html .= "<input type='hidden' name='phase' value='3'>\n";
    foreach ($_POST as $key => $val) {
        if (substr($key, 0, 4) === 'User') {
            $html .= "<li>" . $val . ". " . GetLoginByID($val) . "</li>\n";
            $num_user++;
            $html .= "<input type='hidden' name='Delete" . $val . "' value='" . $val . "'>\n";
        }
    }

    $html .= $nc_core->token->get_input();
    $html .= "</form></ul>";
    if ($num_user) {
        nc_print_status(CONTROL_USER_FUNC_CONFIRM_DEL, 'info');
        echo $html;
    } else {
        nc_print_status(CONTROL_USER_FUNC_CONFIRM_DEL_NOT_USER, 'error');
    }

    return $num_user;
}

/**
 * Функция удаляет пользователей
 *
 * @param mixed номер пользователя или массив с индетификаторами
 *
 * @return bool|array массив с id удаленных пользователей
 * @global  $nc_core , $perm
 *
 */
function DeleteUsers($ids) {
    global $nc_core, $perm;

    $ids = (array)$ids;

    if (!$perm instanceof Permission || empty($ids)) {
        return false;
    }

    $db = $nc_core->db;

    $deleted_users = array(); // массив со всеми удаленными пользователями
    $DeleteActionTemplate = $db->get_var("SELECT `DeleteActionTemplate` FROM `Class` WHERE `System_Table_ID`='3'");

    foreach ($ids as $id) {
        $id += 0;
        if (!$id) {
            continue;
        }
        // нельзя удалить себя
        if ($id == $perm->GetUserID()) {
            continue;
        }
        // нельзя удалить пользователя с большими правами
        if (!$perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_DEL, $id)) {
            continue;
        }

        // удаление
        //$db->query("DELETE FROM `Subscriber` WHERE `User_ID` = '".intval($UserID)."'"); // из подписок
        DeleteSystemTableFiles('User', $id); // удалениe файлов

        $message = $id; // чтобы было доступно в действии после удаления

        if ($DeleteActionTemplate) {
            eval(nc_check_eval("echo \"" . $DeleteActionTemplate . "\";")); // действие после удаления
        }

        $deleted_users[] = $id;
    }

    // никого не удалили
    if (empty($deleted_users)) {
        return false;
    }

    // генерируем событие
    $nc_core->event->execute(nc_Event::BEFORE_USER_DELETED, $deleted_users);

    $ids_str = join(',', $deleted_users);

    $db->query("DELETE FROM `User` WHERE `User_ID` IN (" . $ids_str . ")");
    $db->query("DELETE FROM `User_Group`  WHERE `User_ID` IN (" . $ids_str . ")");

    if ($nc_core->modules->get_by_keyword('auth')) {
        nc_auth_delete_all_relation($deleted_users);
        $db->query("DELETE FROM `Auth_ExternalAuth` WHERE `User_ID` IN (" . $ids_str . ")");
    }

    if ($db->get_var("SHOW TABLES LIKE 'Excursion'")) {
        $db->query("DELETE FROM `Excursion` WHERE `User_ID` IN (" . $ids_str . ")");
    }

    // генерируем событие
    $nc_core->event->execute(nc_Event::AFTER_USER_DELETED, $deleted_users);

    return $deleted_users;
}

###############################################################################

/**
 * Удалить группы с прорисовкой дерева
 *
 */
function DeleteGroups() {
    global $db, $UI_CONFIG;

    $deletedGroup = array(); // удаленные группы
    $grp = array();          // группы, которые хотят удалить
    // соберем в массив все группы
    foreach ($_POST as $key => $val) {
        if (strpos($key, 'Delete') === 0) {
            $grp[] = $val;
        }
    }

    // если групп нет - ошибка
    if (empty($grp)) {
        nc_print_status(CONTROL_USER_FUNCS_ERR_CANTREMGROUP, 'error', $Array);
        exit();
    }

    // сообственно, удаление
    $deletedGroup = nc_usergroup_delete($grp);

    // перерисовка дерева
    if (!empty($deletedGroup)) {
        foreach ($deletedGroup as $v) {
            $UI_CONFIG->treeChanges['deleteNode'][] = "usergroup-" . $v;
        }
    }
}

###############################################################################

/**
 * Show form to add new permission
 *
 * @param int User ID
 * @param int phase
 * @param string action in form
 * @param int Permission Group ID
 */
function AddPermissionForm($UserID, $phase = 10, $action = 'index.php', $PermissionGroupID = 0) {
    global $nc_core, $ADMIN_PATH, $MODULE_VARS;
    global $db, $UI_CONFIG;
    global $perm, $user_login;
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    $params = array(
        'AdminType', 'unlimit', 'start_time', 'start_day', 'start_month', 'start_year',
        'start_hour', 'start_minute', 'end_time', 'end_day', 'end_month', 'end_year', 'end_hour', 'end_minute',
        'item', 'site_list', 'sub_list', 'subclass_list', 'Read', 'Comment', 'Add', 'Edit', 'Check', 'Delete',
        'Moderate', 'Administer',
        'across_start', 'across_start_type', 'across_end', 'across_end_type',
    );

    foreach ($_POST as $key => $val) {
        if (!in_array($key, $params)) {
            continue;
        }
        $$key = $val;
    }

    $module_subscriber = 0;
    if (nc_module_check_by_keyword('subscriber', 0)) {
        $module_subscriber = ($MODULE_VARS['subscriber']['VERSION'] > 1) ? 2 : 1;
    }

    if (empty($AdminType)) {
        $AdminType = 0;
    }
    if (empty($unlimit)) {
        $unlimit = 1;
    }
    if (isset($unlimit) && !$unlimit) {
        $unlimit = 0;
    }
    if (empty($start_time)) {
        $start_time = 0;
    }
    if (empty($end_time)) {
        $end_time = 0;
    }

    $site_list_id = $db->get_col("SELECT `Catalogue_ID`, `Catalogue_Name` FROM `Catalogue`");
    $site_list_name = $db->get_col(0, 1);
    ?>

    <script language='javascript'>
        var site_id = new Array();
        var site_name = new Array();

        var module_permissions = <?= nc_array_json(nc_get_all_module_permissions()) ?>;

        <?php
        for ($i = 0; $i < count($site_list_id); $i++) {
            print "site_id[" . $i . "]=" . $site_list_id[$i] . ";";
            print "site_name[" . $i . "]=\"" . addslashes($site_list_name[$i]) . "\";";
        }
        ?>

        var some_const = {
            allclassificator: '<?= (defined('CONTENT_CLASSIFICATORS_NAMEALL') ? CONTENT_CLASSIFICATORS_NAMEALL :
                null); ?>',
            classificator: '<?= (defined('CONTENT_CLASSIFICATORS_NAMEONE') ? CONTENT_CLASSIFICATORS_NAMEONE :
                null); ?>',
            selectsite: '<?= (defined('CONTROL_USER_SELECTSITE') ? CONTROL_USER_SELECTSITE : null);  ?>',
            allsite: '<?= (defined('CONTROL_USER_SELECTSITEALL') ? CONTROL_USER_SELECTSITEALL : null);  ?>',
            siteadmin: '<?= (defined('CONTROL_USER_RIGHTS_SITEADMIN') ? CONTROL_USER_RIGHTS_SITEADMIN : null);  ?>',
            subadmin: '<?= (defined('CONTROL_USER_RIGHTS_SUBDIVISIONADMIN') ? CONTROL_USER_RIGHTS_SUBDIVISIONADMIN :
                null);  ?>',
            contentadmin: '<?= (defined('CONTROL_USER_RIGHTS_CONTENTADMIN') ? CONTROL_USER_RIGHTS_CONTENTADMIN :
                null);  ?>',
            ccadmin: '<?= (defined('CONTROL_USER_RIGHTS_SUBCLASSADMINS') ? CONTROL_USER_RIGHTS_SUBCLASSADMINS :
                null);  ?>',
            site: '<?= (defined('CONTROL_USER_FUNCS_SITE') ? CONTROL_USER_FUNCS_SITE : null);  ?>',
            sub: '<?= (defined('CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION') ?
                CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION : null);  ?>',
            cc: '<?= (defined('CONTROL_USER_FUNCS_CLASSINSECTION') ? CONTROL_USER_FUNCS_CLASSINSECTION : null);  ?>',
            item: '<?= (defined('CONTROL_USER_RIGHTS_ITEM') ? CONTROL_USER_RIGHTS_ITEM : null);  ?>',
            selectitem: '<?= (defined('CONTROL_USER_RIGHTS_SELECT_ITEM') ? CONTROL_USER_RIGHTS_SELECT_ITEM :
                null);  ?>',
            load: '<?= (defined('CONTROL_USER_RIGHTS_LOAD') ? CONTROL_USER_RIGHTS_LOAD : null);  ?>',
            mailer: '<?= (defined('NETCAT_MODULE_SUBSCRIBE_MAILER') ? NETCAT_MODULE_SUBSCRIBE_MAILER : null); ?>',
            module: '<?= (defined('CONTROL_USER_MODULE') ? CONTROL_USER_MODULE : null);  ?>',
            module_permission: '<?= (defined('CONTROL_USER_RIGHTS') ? CONTROL_USER_RIGHTS : null); ?>'
        }

    </script>

    <form action='<?= $action ?>' method='post' name='admin' id='admin'>
        <input name='phase' value='<?= $phase ?>' type='hidden'>
        <input type='hidden' name='UserID' value='<?= $UserID ?>'>
        <input type='hidden' name='PermissionGroupID' value='<?= $PermissionGroupID ?>'>

        <br>
        <table border='0' width='76%' align='left' style='margin-left: 20px'>
            <tr>
                <td width='31%' valign='top'>

                    <fieldset>
                        <legend><?= CONTROL_USER_RIGHTS_TYPE_OF_RIGHT ?></legend>
                        <? /** @var Permission $perm */ ?>
                        <? if ($perm->isDirector()): ?>
                            <?= nc_admin_radio_simple('AdminType', DIRECTOR, CONTROL_USER_RIGHTS_DIRECTOR,
                                $AdminType == DIRECTOR, 'dir', "onclick='nc_user_obj.setType(7)'") ?>
                            <br>
                        <? endif; ?>

                        <? $disabled = ''; // ? ... : ' disabled'; ?>

                        <? if ($perm->isDirector() || $perm->isSupervisor()): ?>
                            <?= nc_admin_radio_simple('AdminType', SUPERVISOR, CONTROL_USER_RIGHTS_SUPERVISOR,
                                $AdminType == SUPERVISOR, 'sv', "onclick='nc_user_obj.setType(6)'" . $disabled) ?>
                            <br>
                            <div style='height: 8px'></div>
                        <? endif; ?>


                        <? if ($perm->hasAnyEditorPermissions()): ?>
                            <?= nc_admin_radio_simple('AdminType', EDITOR, CONTROL_USER_RIGHTS_EDITOR,
                                $AdminType == EDITOR, 'man', "onclick='nc_user_obj.setType(5)'" . $disabled) ?>
                            <br>
                        <? endif; ?>

                        <? if ($perm->hasAnyClassificatorPermissions()): ?>
                            <nobr>
                                <?= nc_admin_radio_simple('AdminType', CLASSIFICATOR_ADMIN,
                                    CONTROL_USER_RIGHTS_CLASSIFICATORADMIN, $AdminType == CLASSIFICATOR_ADMIN, 'devel',
                                    "onclick='nc_user_obj.setType(14)'" . $disabled) ?>
                                <br>
                            </nobr>
                        <? endif; ?>

                        <? if ($perm->hasAnyModulePermission()): ?>
                            <nobr>
                                <?= nc_admin_radio_simple('AdminType', 'module', CONTROL_USER_RIGHTS_MODULE, false,
                                    'module', "onclick='nc_user_obj.setType(\"module\")'") ?>
                                <br>
                            </nobr>
                        <? endif; ?>

                        <?php if ($perm->isUserModerator()): ?>
                            <div style='height: 8px'></div>
                            <nobr>
                                <?= nc_admin_radio_simple('AdminType', MODERATOR, CONTROL_USER_RIGHTS_MODERATOR,
                                    $AdminType == MODERATOR, 'mod', "onclick='nc_user_obj.setType(12)'" . $disabled) ?>
                                <br>
                            </nobr>
                        <? endif; ?>

                        <?php if ($perm->isGroupModerator()): ?>
                            <nobr>
                                <?= nc_admin_radio_simple('AdminType', GROUP_MODERATOR, CONTROL_USER_RIGHTS_USER_GROUP,
                                    $AdminType == GROUP_MODERATOR, 'user_group',
                                    "onclick='nc_user_obj.setType(13)'" . $disabled) ?>
                                <br>
                            </nobr>
                        <? endif; ?>

                        <?php if ($module_subscriber == 2 && $perm->hasAnySubscriptionPermissions()) : ?>
                            <div style='height: 8px'></div>
                            <?= nc_admin_radio_simple('AdminType', SUBSCRIBER, CONTROL_USER_RIGHTS_SUBSCRIBER,
                            $AdminType == SUBSCRIBER, 'subscriber', "onclick='nc_user_obj.setType(30)'" . $disabled) ?>
                            <br>
                        <?php endif; ?>

                        <? if ($perm->isDirector() || $perm->isSupervisor()): ?>
                            <div style='height: 8px'></div>
                            <?= nc_admin_radio_simple('AdminType', BAN, CONTROL_USER_RIGHTS_BAN, $AdminType == BAN,
                            'ban', "onclick='nc_user_obj.setType(20)'" . $disabled) ?>
                            <br>
                            <div style='height: 8px'></div>
                            <?= nc_admin_radio_simple('AdminType', GUEST, CONTROL_USER_RIGHTS_GUESTONE,
                            $AdminType == GUEST, 'guest', "onclick='nc_user_obj.setType(8)'" . $disabled) ?>
                            <br>
                        <? endif; ?>

                        <br><br><br><br><br><br>
                    </fieldset>

                </td>
                <td valign='top'>
                    <?
                    if (!isset($across_start)) {
                        $across_start = null;
                    }
                    if (!isset($across_start_type)) {
                        $across_start_type = null;
                    }
                    if (!isset($across_end)) {
                        $across_end = null;
                    }
                    if (!isset($across_end_type)) {
                        $across_end_type = null;
                    }

                    ?>
                    <div id='div_livetime' name='div_livetime' style='display: none'>
                        <fieldset>
                            <legend><?= CONTROL_USER_RIGHTS_LIVETIME ?></legend>
                            <?= nc_admin_radio_simple('unlimit', 1, CONTROL_USER_RIGHTS_UNLIMITED, $unlimit, '',
                                "onclick='nc_user_obj.disable_livetime(1)'") ?>
                            <br>
                            <?= nc_admin_radio_simple('unlimit', 0, CONTROL_USER_RIGHTS_LIMITED, !$unlimit, '',
                                "onclick='nc_user_obj.disable_livetime(0)'") ?>
                            <br>
                            <div name='div_time' id='div_time' style='min-width:350px'>
                                <br><?= CONTROL_USER_RIGHTS_STARTING_OPERATIONS ?>:<br>
                                <table border='0' cellpadding='2' cellspacing='0'>
                                    <tr>
                                        <td>
                                            <?= nc_admin_radio_simple('start_time', 0, CONTROL_USER_RIGHTS_NOW,
                                                !$start_time, 'start_now', "onclick='nc_user_obj.setStartType(0)'") ?>
                                        </td>
                                        <td colspan='4'></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?= nc_admin_radio_simple('start_time', 1,
                                                CONTROL_USER_RIGHTS_ACROSS . "&nbsp;&nbsp;", $start_time == 1,
                                                'start_across', "onclick='nc_user_obj.setStartType(1)'") ?>
                                        </td>
                                        <td>
                                            <?= nc_admin_input_simple('across_start', $across_start, 2, '',
                                                "id='across_start' maxlength='2'") ?>
                                        </td>
                                        <td>
                                            <?= nc_admin_select_simple('', 'across_start_type', array(
                                                CONTROL_USER_RIGHTS_ACROSS_MINUTES, CONTROL_USER_RIGHTS_ACROSS_HOURS,
                                                CONTROL_USER_RIGHTS_ACROSS_DAYS, CONTROL_USER_RIGHTS_ACROSS_MONTHS,
                                            ), $across_start_type, "id='across_start_type'") ?>
                                        </td>
                                        <td colspan='2'></td>
                                    </tr>
                                    <tr>
                                        <td>

                                            <?= nc_admin_radio_simple('start_time', 2, '', $start_time == 2,
                                                'start_define', "onclick='nc_user_obj.setStartType(2)'") ?>
                                            <?= nc_admin_input_simple('start_day', '', 2, '',
                                                "maxlength='2' id='start_day'") ?>
                                        </td>
                                        <td>
                                            <?= nc_admin_input_simple('start_month', '', 2, '',
                                                "maxlength='2' id='start_month'") ?>
                                        </td>
                                        <td>
                                            <?= nc_admin_input_simple('start_year', '', 4, '',
                                                "maxlength='4' id='start_year'") ?>
                                        </td>
                                        <td>
                                            <?= nc_admin_input_simple('start_hour', '', 2, '',
                                                "maxlength='2' id='start_hour'") ?>
                                            <b> :</b></td>
                                        <td>
                                            <?= nc_admin_input_simple('start_minute', '', 2, '',
                                                "maxlength='2' id='start_minute'") ?>
                                        </td>
                                    </tr>
                                </table>

                                <br><?= CONTROL_USER_RIGHTS_FINISHING_OPERATIONS ?>:<br>
                                <table border='0' cellpadding='2' cellspacing='0'>
                                    <tr>
                                        <td colspan='5'>
                                            <?= nc_admin_radio_simple('end_time', 0, CONTROL_USER_RIGHTS_NONLIMITED,
                                                !$end_time, 'end_now', "onclick='nc_user_obj.setEndType(0)'") ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?= nc_admin_radio_simple('end_time', 1, CONTROL_USER_RIGHTS_ACROSS,
                                                !$end_time, 'end_across', "onclick='nc_user_obj.setEndType(1)'") ?>
                                        </td>
                                        <td>
                                            <?= nc_admin_input_simple('across_end', $across_end, 2, '',
                                                "id='across_end' maxlength='2'") ?>
                                        </td>
                                        <td>
                                            <?=
                                            nc_admin_select_simple('', 'across_end_type', array(
                                                CONTROL_USER_RIGHTS_ACROSS_MINUTES, CONTROL_USER_RIGHTS_ACROSS_HOURS,
                                                CONTROL_USER_RIGHTS_ACROSS_DAYS, CONTROL_USER_RIGHTS_ACROSS_MONTHS,
                                            ), $across_end_type, "id='across_end_type'")
                                            ?>
                                        </td>
                                        <td colspan='2'></td>
                                    </tr>
                                    <tr>
                                        <td>

                                            <?= nc_admin_radio_simple('end_time', 2, '', $end_time == 2, 'end_define',
                                                "onclick='nc_user_obj.setEndType(2)'")
                                            ?>
                                            <?= nc_admin_input_simple('end_day', '', 2, '',
                                                "maxlength='2' id='end_day'")
                                            ?>
                                        </td>
                                        <td>
                                            <?= nc_admin_input_simple('end_month', '', 2, '',
                                                "maxlength='2' id='end_month'")
                                            ?>
                                        </td>
                                        <td>
                                            <?= nc_admin_input_simple('end_year', '', 4, '',
                                                "maxlength='4' id='end_year'")
                                            ?>
                                        </td>
                                        <td>
                                            <?= nc_admin_input_simple('end_hour', '', 2, '',
                                                "maxlength='2' id='end_hour'") ?>
                                            <b> :</b></td>
                                        <td>
                                            <?= nc_admin_input_simple('end_minute', '', 2, '',
                                                "maxlength='2' id='end_minute'") ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </fieldset>
                    </div>

                </td>
            </tr>
            <tr>
                <td colspan='2'>

                    <div name='div_main_right' id='div_main_right' style='display: none'>
                        <fieldset>
                            <legend><?= CONTROL_USER_RIGHTS_RIGHT ?></legend>
                            <div name='userperm' id='userperm' style='display: none'><br>
                                <?= nc_admin_checkbox_simple('user_add', 1, CONTROL_USER_RIGHTS_CONTROL_ADD) ?><br>
                                <?= nc_admin_checkbox_simple('user_edit', 1, CONTROL_USER_RIGHTS_CONTROL_EDIT) ?><br>
                                <?= nc_admin_checkbox_simple('user_moderate', 1,
                                    CONTROL_USER_RIGHTS_CONTROL_MODERATE) ?><br>
                                <?= nc_admin_checkbox_simple('user_admin', 1, CONTROL_USER_RIGHTS_CONTROL_ADMIN) ?><br>
                                <?= nc_admin_checkbox_simple('user_del', 1, CONTROL_USER_RIGHTS_CONTROL_DELETE) ?><br>
                            </div>

                            <div name=group_perm' id='group_perm' style='display: none'><br>
                                <?= nc_admin_checkbox_simple('group_add', 1, CONTROL_USER_RIGHTS_CONTROL_ADD) ?><br>
                                <?= nc_admin_checkbox_simple('group_edit', 1, CONTROL_CLASS_ACTIONS_EDIT) ?><br>
                                <?= nc_admin_checkbox_simple('group_admin', 1, CONTROL_USER_RIGHTS_CONTROL_ADMIN) ?><br>
                                <?= nc_admin_checkbox_simple('group_del', 1, CONTROL_USER_RIGHTS_CONTROL_DELETE) ?><br>
                            </div>

                            <br>

                            <table id='tbl_item' name='tbl_item' cellpadding='4' cellspacing='1' width='75%'
                                    bgcolor='#CCCCCC'>
                                <tbody></tbody>
                            </table>

                            <div name='div_perm' id='div_perm' style='display: none'><br>
                                <?= nc_admin_checkbox_simple('Read', 1, CONTROL_CLASS_ACTIONS_VIEW, false, 'l01')
                                ?><br>
                                <? if (nc_module_check_by_keyword("comments")): ?>
                                    <?= nc_admin_checkbox_simple('Comment', 1,
                                        CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_COMMENT, false, 'l07') ?><br>
                                <? endif; ?>
                                <?= nc_admin_checkbox_simple('Add', 1, CONTROL_CONTENT_CATALOUGE_ADD, false, 'l02') ?>
                                <br>
                                <?= nc_admin_checkbox_simple('Edit', 1, CONTROL_CLASS_ACTIONS_EDIT, false, 'l03') ?><br>
                                <?= nc_admin_checkbox_simple('Check', 1, CONTROL_CLASS_ACTIONS_CHECKED, false,
                                    'l031') ?><br>
                                <?= nc_admin_checkbox_simple('Delete', 1, CONTROL_CLASS_ACTIONS_DELETE, false,
                                    'l032') ?><br>
                                <? if ($module_subscriber == 1): ?>
                                    <?= nc_admin_checkbox_simple('Subscribe', 1, CONTROL_CLASS_ACTIONS_MAIL, false,
                                        'l04') ?><br>
                                <? endif; ?>
                                <?= nc_admin_checkbox_simple('Moderate', 1, CONTROL_CLASS_ACTIONS_MODERATE, false,
                                    'l05', "onclick='nc_user_obj.handler_checkbox(5)'") ?><br>
                                <?= nc_admin_checkbox_simple('Administer', 1, CONTROL_CLASS_ACTIONS_ADMIN, false, 'l06',
                                    "onclick='nc_user_obj.handler_checkbox(6)'") ?><br>
                            </div>

                            <div name='div_perm_ban' id='div_perm_ban' style='display: none'><br>
                                <?= nc_admin_checkbox_simple('Read', 1, CONTROL_CLASS_ACTIONS_VIEW, false, 'l1') ?><br>
                                <? if (nc_module_check_by_keyword("comments")): ?>
                                    <?= nc_admin_checkbox_simple('Comment', 1,
                                        CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_COMMENT, false, 'l7') ?><br>
                                <? endif; ?>
                                <?= nc_admin_checkbox_simple('Add', 1, CONTROL_CONTENT_CATALOUGE_ADD, false, 'l2') ?>
                                <br>
                                <?= nc_admin_checkbox_simple('Edit', 1, CONTROL_CLASS_ACTIONS_EDIT, false, 'l3') ?><br>
                                <?= nc_admin_checkbox_simple('Check', 1, CONTROL_CLASS_ACTIONS_CHECKED, false, 'l31') ?>
                                <br>
                                <?= nc_admin_checkbox_simple('Delete', 1, CONTROL_CLASS_ACTIONS_DELETE, false, 'l32') ?>
                                <br>
                                <? if (nc_module_check_by_keyword("subscriber", false)): ?>
                                    <?= nc_admin_checkbox_simple('Subscribe', 1, CONTROL_CLASS_ACTIONS_MAIL, false,
                                        'l4') ?><br>
                                <? endif; ?>
                            </div>

                            <div name='div_perm_classificator' id='div_perm_classificator' style='display: none'><br>
                                <?= nc_admin_checkbox_simple('Edit', 1, CONTROL_CLASS_ACTIONS_EDIT, false, 'l1') ?><br>
                                <?= nc_admin_checkbox_simple('Add', 1, CONTROL_CONTENT_CATALOUGE_ADD, false, 'l2') ?>
                                <br>
                                <?= nc_admin_checkbox_simple('Moderate', 1, CONTROL_CLASS_ACTIONS_MODERATE, false,
                                    'l3') ?><br>
                            </div>

                            <div name='div_perm_module' id='div_perm_module' style='display: none'><br>
                            </div>

                            <div name='div_perm_subscriber' id='div_perm_subscriber' style='display: none'><br>
                            </div>

                        </fieldset>
                    </div>


                </td>
            </tr>
            <tr>
                <td colspan='2'>
                    <div name="div_help" id="div_help" style='display: none'>
                        <fieldset>
                            <legend><?= CONTROL_USER_RIGHTS_CONTROL_HELP ?></legend>
                            <div id='help' name='help' style='padding: 10px'></div>
                        </fieldset>
                    </div>
                </td>
            </tr>
        </table>
        <?php echo $nc_core->token->get_input(); ?>
    </form>

    <script type="text/javascript" src="<?= nc_add_revision_to_url($ADMIN_PATH . 'js/user.js') ?>"></script>
    <script type="text/javascript">
        nc_user_obj = new nc_user_perm();
        nc_user_obj.setType(<?= $AdminType ?>);
        nc_user_obj.disable_livetime(<?= $unlimit ?>);
        nc_user_obj.setStartType(<?= $start_time ?>);
        nc_user_obj.setEndType(<?= $end_time ?>);
    </script>
    <?
    if ($UserID) {
        $UI_CONFIG->headerText = CONTROL_USER_RIGHT_ADDPERM . " " . addslashes($user_login);
    } else {
        $UI_CONFIG->headerText = CONTROL_USER_RIGHT_ADDPERM_GROUP . " " . GetPermissionGroupName($PermissionGroupID);
    }

    $UI_CONFIG->actionButtons[] = array(
        "id" => "addright",
        "caption" => CONTROL_USER_RIGHT_ADDNEWRIGHTS,
        "action" => "mainView.submitIframeForm()",
    );
}

/**
 * Add permission
 *
 * @return int code error (0 - ok)
 */
function AddPermissionCompleted() {
    global $db, $perm, $AUTH_USER_ID, $nc_core;
    $nc_core = nc_core::get_object();
    
    $params = array(
        'UserID', 'PermissionGroupID',
        'AdminType', 'unlimit', 'start_time', 'start_day', 'start_month', 'start_year', 'start_hour', 'start_minute',
        'end_time', 'end_day', 'end_month', 'end_year', 'end_hour', 'end_minute',
        'item', 'site_list', 'sub_list', 'subclass_list', 'dev_classificator', 'mailer_id',
        'Read', 'Comment', 'Add', 'Edit', 'Check', 'Delete', 'Moderate', 'Administer', 'Subscribe',
        'across_start', 'across_start_type', 'across_end', 'across_end_type',
    );

    foreach ($params as $variable) {
        $$variable = nc_array_value($_POST, $variable);
    }

    $nc_core->event->execute(nc_Event::BEFORE_USER_UPDATED, $UserID);

    $day_now = date("d");
    $month_now = date("m");
    $year_now = date("Y");
    $hour_now = date("H");
    $minute_now = date("i");
    $temp = 0;

    //Если не выбран пользователь или группа
    if (!($UserID + $PermissionGroupID)) {
        return 3;
    }

    if ($UserID && $UserID == $AUTH_USER_ID && $AdminType == GUEST) {
        return 15;
    }

    /** @var Permission $perm */

    // Проверка права на управление правами группы
    if ($PermissionGroupID && !$perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_RIGHT, $UserID)) {
        return 2;
    }

    // Проверка, редактируемый пользователь не стоит ли выше по правам
    if ($UserID && !$perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, $UserID)) {
        return 2;
    }

    # Определение времени жизни
    if ($unlimit) { //бессрочно
        $start_perm = null;
        $end_perm = null;
    } else {
        // начало действия
        switch ($start_time) {
            case 0: // now
                $start_perm = null;
                $temp_start = mktime($hour_now, $minute_now, 0, $month_now, $day_now, $year_now);
                break;
            case 1: // через ...
                $across_start = intval($across_start);
                if ($across_start <= 0) {
                    return 11;
                }

                $across_start_type = intval($across_start_type);
                $start_day = $day_now;
                $start_month = $month_now;
                $start_year = $year_now;
                $start_hour = $hour_now;
                $start_minute = $minute_now;
                if ($across_start_type == 3) {
                    $start_month += $across_start;
                } else if ($across_start_type == 2) {
                    $start_day += $across_start;
                } else if ($across_start_type == 1) {
                    $start_hour += $across_start;
                } else {
                    $start_minute += $across_start;
                }

                $temp_start = mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
                $start_perm = strftime('%Y-%m-%d %H:%M:%S', $temp_start);
                break;
            case 2: // точное время
                $temp_start = mktime(intval($start_hour), intval($start_minute), 0, intval($start_month),
                    intval($start_day), intval($start_year));
                $start_perm = strftime('%Y-%m-%d %H:%M:%S', $temp_start);
                break;
        }
        // конец времени действия
        switch ($end_time) {
            case 0: // бессрочно
                $end_perm = null;
                $temp_end = mktime($hour_now, $minute_now, 0, $month_now, $day_now, $year_now + 10);
                break;
            case 1: // через ...
                $across_end = intval($across_end);
                if ($across_end <= 0) {
                    return 12;
                }

                $across_end_type = intval($across_end_type);
                $end_day = $day_now;
                $end_month = $month_now;
                $end_year = $year_now;
                $end_hour = $hour_now;
                $end_minute = $minute_now;
                if ($across_end_type == 3) {
                    $end_month += $across_end;
                } else if ($across_end_type == 2) {
                    $end_day += $across_end;
                } else if ($across_end_type == 1) {
                    $end_hour += $across_end;
                } else {
                    $end_minute += $across_end;
                }

                $temp_end = mktime($end_hour, $end_minute, 0, $end_month, $end_day, $end_year);
                $end_perm = strftime('%Y-%m-%d %H:%M:%S', $temp_end);
                break;
            case 2: // точное время
                $temp_end = mktime(intval($end_hour), intval($end_minute), 0, intval($end_month), intval($end_day),
                    intval($end_year));
                $end_perm = strftime('%Y-%m-%d %H:%M:%S', $temp_end);
                break;
        }

        if ($temp_end < $temp_start) {
            return 13;
        }
    }

    $insert = array(
        'User_ID' => (int)$UserID,
        'PermissionGroup_ID' => (int)$PermissionGroupID,
        'Catalogue_ID' => 0,
        'PermissionBegin' => $start_perm,
        'PermissionEnd' => $end_perm,
    );

    // Сохранение данных в Module_Permission
    if ($AdminType === 'module') {
        $insert['Catalogue_ID'] = (int)$nc_core->input->fetch_post('site_list');
        $insert['Module_Keyword'] = $nc_core->input->fetch_post('module_keyword');

        return nc_save_module_permissions_form($insert, (array)$nc_core->input->fetch_post('module_permissions'));
    }

    // Сохранение данных в Permission
    $insert['AdminType'] = (int)$AdminType;
    $insert['PermissionSet'] = 0;

    switch ($AdminType) {
        case DIRECTOR:
            // Директора может добавить только директор
            if (!$perm->isDirector()) {
                return 2;
            }
            break;

        case SUPERVISOR:
            if (!$perm->isSupervisor()) {
                return 2;
            }
            break;

        case EDITOR:
            // Чего именно редактор -  сайта, раздела иил сс?
            if ($item == 1) { // редактор сайта
                $at = CATALOGUE_ADMIN;
                $c_id = intval($site_list);
            } else if ($item == 2) { // редактор раздела
                $at = SUBDIVISION_ADMIN;
                $c_id = intval($sub_list);

                if (!$site_list) {
                    return 7;
                }
                if (!$c_id) {
                    return 8;
                }
            } else if ($item == 3) { // редактор сс
                $at = SUB_CLASS_ADMIN;
                $c_id = intval($subclass_list);

                if (!$site_list) {
                    return 7;
                }
                if (!$sub_list) {
                    return 8;
                }
                if (!$c_id) {
                    return 9;
                }
            } else if ($item == 4) { // редактор контента
                $at = CATALOGUE_CONTENT_ADMIN;
                $c_id = intval($site_list);
            } else {
                return 3;
            }

            // возможности

            $PermissionSet = intval($Read * MASK_READ + $Add * MASK_ADD + $Edit * MASK_EDIT + $Delete * MASK_DELETE +
                $Check * MASK_CHECKED + $Comment * MASK_COMMENT +
                $Subscribe * MASK_SUBSCRIBE + $Moderate * MASK_MODERATE + $Administer * MASK_ADMIN);

            if (!$PermissionSet) {
                return 6;
            }

            $insert['AdminType'] = $at;
            $insert['Catalogue_ID'] = $c_id;
            $insert['PermissionSet'] = $PermissionSet;

            break;

        case MODERATOR:
            $PermissionSet =
                MASK_READ |
                MASK_ADD * ($nc_core->input->fetch_post('user_add') ?: 0) |
                MASK_EDIT * ($nc_core->input->fetch_post('user_edit') ?: 0) |
                MASK_MODERATE * ($nc_core->input->fetch_post('user_moderate') ?: 0) |
                MASK_ADMIN * ($nc_core->input->fetch_post('user_admin') ?: 0) |
                MASK_DELETE * ($nc_core->input->fetch_post('user_del') ?: 0);
            if (!$PermissionSet) {
                return 6;
            }

            $insert['PermissionSet'] = $PermissionSet;
            break;

        case GROUP_MODERATOR:
            $PermissionSet =
                MASK_READ |
                MASK_ADD * ($nc_core->input->fetch_post('group_add') ?: 0) |
                MASK_EDIT * ($nc_core->input->fetch_post('group_edit') ?: 0) |
                MASK_ADMIN * ($nc_core->input->fetch_post('group_admin') ?: 0) |
                MASK_DELETE * ($nc_core->input->fetch_post('group_del') ?: 0);
            if (!$PermissionSet) {
                return 6;
            }

            $insert['PermissionSet'] = $PermissionSet;
            break;

        case CLASSIFICATOR_ADMIN:
            $insert['PermissionSet'] = intval(MASK_READ + $Edit * MASK_EDIT + $Add * MASK_ADD +
                $Moderate * MASK_MODERATE); // 1 == view -  default
            $insert['Catalogue_ID'] = intval($dev_classificator);
            break;

        case SUBSCRIBER:
            $mailer_id = intval($mailer_id);
            if (!$mailer_id) {
                return 14;
            }
            $insert['Catalogue_ID'] = $mailer_id;
            break;

        case BAN:
            if ($item == 1) { //  сайт
                $at = BAN_SITE;
                $c_id = intval($site_list);
            } else if ($item == 2) { //раздел
                $at = BAN_SUB;
                $c_id = intval($sub_list);

                if (!$site_list) {
                    return 7;
                }
                if (!$c_id) {
                    return 8;
                }
            } else if ($item == 3) { //  сс
                $at = BAN_CC;
                $c_id = intval($subclass_list);

                if (!$site_list) {
                    return 7;
                }
                if (!$sub_list) {
                    return 8;
                }
                if (!$c_id) {
                    return 9;
                }
            } else if ($item == 4) { // редактирование контента сайта
                $at = BAN_SITE_CONTENT;
                $c_id = (int)$site_list;
            } else {
                return 3;
            }

            $PermissionSet = intval($Read * MASK_READ + $Comment * MASK_COMMENT + $Add * MASK_ADD + $Edit * MASK_EDIT +
                $Delete * MASK_DELETE + $Check * MASK_CHECKED + $Subscribe * MASK_SUBSCRIBE);
            if (!$PermissionSet) {
                return 6;
            }

            $insert['AdminType'] = $at;
            $insert['Catalogue_ID'] = $c_id;
            $insert['PermissionSet'] = $PermissionSet;
            break;

        case GUEST:
            if (!$perm->isSupervisor()) {
                return 2;
            }
            break;

        default:
            return 10;
    }

    $return_value = 0;

    // Присвоить права другому пользователю можно только если у пользователя есть эти права
    $allowed_mask = $perm->getAllowedMask($insert['AdminType'], $insert['Catalogue_ID'], $insert['PermissionSet']);

    if ($allowed_mask === 0) {
        return 16;
    }

    if ($allowed_mask !== false && $allowed_mask !== $insert['PermissionSet']) {
        $insert['PermissionSet'] = $allowed_mask;
        $return_value = -1;
    }

    if (!nc_db_table::make('Permission')->insert($insert)) {
        return 5;
    }

    $nc_core->event->execute(nc_Event::AFTER_USER_UPDATED, $UserID);

    return $return_value;
}

function UpdatePermission() {
    /** @var Permission $perm */
    /** @var nc_db $db */
    global $perm, $UserID, $PermissionGroupID;
    $nc_core = nc_core::get_object();
    $db = $nc_core->db;

    $all_saved = true;

    $UserID = (int)$UserID;
    $PermissionGroupID = (int)$PermissionGroupID;

    $nc_core->event->execute(nc_Event::BEFORE_USER_UPDATED, $UserID);

    $fetch_permission_data = function ($permission_id) {
        static $cache;
        if (!isset($cache[$permission_id])) {
            $cache[$permission_id] = nc_db()->get_row("SELECT * FROM `Permission` WHERE `Permission_ID` = $permission_id",
                ARRAY_A) ?: array();
        }

        return $cache[$permission_id];
    };

    $permissions = array();
    $deleted_permission_ids = array();
    foreach ($_POST as $key => $val) {
        $val = (int)$val;
        if (strpos($key, 'Delete') === 0) {
            $permission_data = $fetch_permission_data($val);
            if ($permission_data['User_ID'] == $perm->getUserID() &&
                ($permission_data['AdminType'] == DIRECTOR || $permission_data['AdminType'] == SUPERVISOR)
            ) {
                continue; // нельзя самовыпилиться из директоров и супервизоров
            }
            if (!$perm->canChangePermission($permission_data)) {
                $all_saved = false;
                continue; // нельзя удалить набор прав, если какого-то права нет у текущего пользователя
            }
            $deleted_permission_ids[] = $val;
        } else if (strpos($key, 'PermissionID') === 0) {
            $permission_id = (int)substr($key, 12, strlen($key) - 14);
            if (isset($permissions[$permission_id])) {
                $permissions[$permission_id] |= $val;
            } else {
                $permissions[$permission_id] = $val;
            }
        }
    }

    foreach ($permissions as $permission_id => $mask) {
        $permission_data = $fetch_permission_data($permission_id);
        $can_change = $perm->canChangePermission($permission_data);
        $allowed_mask = $perm->getAllowedMask($permission_data['AdminType'], $permission_data['Catalogue_ID'], $mask);
        $all_saved = $all_saved && $can_change && $allowed_mask === $mask;
        if ($can_change && $allowed_mask) {
            $db->query("UPDATE `Permission` SET `PermissionSet` = $allowed_mask WHERE `Permission_ID` = $permission_id");
        }
    }

    if ($deleted_permission_ids) {
        $db->query('DELETE FROM `Permission` WHERE Permission_ID IN (' . implode(', ', $deleted_permission_ids) . ')');
    }

    $deleted_module_permissions = (array)$nc_core->input->fetch_post('deleted_module_permissions');
    foreach ($deleted_module_permissions as $id) {
        $all_saved = nc_delete_module_permission($id) && $all_saved;
    }

    if ($all_saved) {
        nc_print_status(CONTROL_USER_RIGHTS_UPDATED_OK, 'ok');
    } else {
        nc_print_status(CONTROL_USER_RIGHTS_ERROR_SOME_AVAILABLE, 'error');
    }
}

function ChangeCheckedForUser($UserID) {
    global $nc_core, $db;
    global $AUTH_USER_ID, $AUTH_USER_GROUP, $perm;

    $UserID = (int)$UserID;
    if (!$UserID || ($AUTH_USER_ID == $UserID)) {
        return false;
    }

    $CheckActionTemplate = $db->get_var('SELECT `CheckActionTemplate` FROM `Class` WHERE `System_Table_ID` = 3 AND `ClassTemplate` = 0');
    $cur_value = $db->get_var("SELECT `Checked` FROM `User` WHERE `User_ID` = '{$UserID}'");

    $nc_core->event->execute($cur_value ? nc_Event::BEFORE_USER_DISABLED : nc_Event::BEFORE_USER_ENABLED, $UserID);
    $db->query("UPDATE `User` SET `Checked` = 1 - `Checked` WHERE `User_ID` ='{$UserID}'");
    $nc_core->event->execute($cur_value ? nc_Event::AFTER_USER_DISABLED : nc_Event::AFTER_USER_ENABLED, $UserID);

    if ($CheckActionTemplate) {
        eval(nc_check_eval("echo \"" . $CheckActionTemplate . "\";"));
    }
}

/**
 * Confirm: all values in array must be +3 or 4
 *
 * @param array array_data
 *
 * @return bool
 */
function ConfirmInputData($array_data) {
    foreach ((array)$array_data as $v) {
        if (!preg_match("/^(()|([+]?[0-9]+))$/", $v)) {
            return 0;
        }
    }

    return 1;
}


/**
 * UI_CONFIG_USER
 */
class ui_config_user extends ui_config {

    /**
     * конструктор
     */
    public function __construct() {
        $this->treeMode = "users";
        $this->treeSelectedNode = "users";
    }

    /**
     * cтраница "список пользователей"
     */
    function user_list_page() {

        $this->headerText = CONTROL_USER_USERSANDRIGHTS;
        $this->tabs = array(
            array(
                'id' => 'list',
                'caption' => SECTION_CONTROL_USER,
                'location' => "user.list()",
            ),
        );
        $this->activeTab = 'list'; // i.e. "tab1"
        $this->locationHash = "user.list()";
    }

    /**
     * страница изменения данных пользователя
     * (вкладки: пользователь; права)
     *
     * @param integer user id
     * @param string login
     * @param string active tab ('user'; 'rights')
     * @param string optinal - адрес страницы (если пустой - сформировать)
     */
    function user_page($user_id, $user_login, $active_tab, $hash = "") {
        global $perm, $nc_core;
        $db = $nc_core->db;
        $MODULE_VARS = $nc_core->modules->get_module_vars();

        $this->headerText = CONTROL_USER . ' ' . addslashes($user_login);
        // пользователь, права
        $this->tabs[0] = array(
            'id' => 'edit',
            'caption' => CONTROL_USER,
            'location' => "user.edit($user_id)",
        );
        if ($perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, $user_id)) {

            $this->tabs[1] = array(
                'id' => 'rights',
                'caption' => SECTION_INDEX_USER_RIGHTS_RIGHTS,
                'location' => "user.rights($user_id)",
            );
            if (nc_module_check_by_keyword('subscriber', 0)) {
                $this->tabs[2] = array(
                    'id' => 'subscribers',
                    'caption' => SECTION_INDEX_USER_SUBSCRIBERS,
                    'location' => "user.subscribers($user_id)",
                );
            }
        }

        $this->activeTab = $active_tab;
        if (!$perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, $user_id)) {
            $this->activeTab = 'user';
        }
        $this->locationHash = ($hash ? $hash : "user.$active_tab($user_id)");
    }

    function new_user_page() {
        $this->headerText = CONTROL_USER_REG;
        $this->tabs = array(
            array(
                'id' => 'add',
                'caption' => CONTROL_USER_REG,
                'location' => "user.add()",
            ),
        );
        $this->activeTab = "add";
        $this->locationHash = "user.add()";
    }

    public function support_user_page($duration, $enable_submit) {
        $this->headerText = CONTROL_USER_SUPPORT_TITLE;
        $this->tabs = array();
        $this->locationHash = "user.support($duration)";
        if ($enable_submit) {
            $this->actionButtons = array(
                array(
                    "id" => "submit",
                    "caption" => CONTROL_USER_SUPPORT_SUBMIT,
                    "action" => "mainView.submitIframeForm()",
                ),
            );
        }
    }

}

/**
 * UI_CONFIG_USERGROUP
 */
class ui_config_usergroup extends ui_config {

    /**
     * конструктор
     */
    public function __construct() {
        $this->treeMode = "users";
    }

    /**
     * cтраница "список пользователей"
     */
    function usergroup_list_page() {

        $this->headerText = CONTROL_USER_GROUPS;
        $this->tabs = array(
            array(
                'id' => 'list',
                'caption' => CONTROL_USER_GROUPS,
                'location' => "usergroup.list()",
            ),
        );
        $this->activeTab = 'list';
        $this->locationHash = "usergroup.list()";
        $this->treeSelectedNode = "usergroup";
    }

    /**
     * страница изменения данных пользователя
     * (вкладки: пользователь; права)
     *
     * @param integer id
     * @param string name
     * @param string active tab ('group'; 'rights')
     * @param string optinal - адрес страницы (если пустой - сформировать)
     */
    function group_page($group_id, $group_name, $active_tab, $hash = "") {
        $this->headerText = addslashes($group_name);
        // пользователь, права
        $this->tabs = array(
            array(
                'id' => 'edit',
                'caption' => CONTROL_USER_MAIL_GROUP,
                'location' => "usergroup.edit($group_id)",
            ),
        );
        /** @var Permission $perm */
        global $perm;
        if ($perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_RIGHT, $group_id, 0)) {
            $this->tabs[] = array(
                'id' => 'rights',
                'caption' => SECTION_INDEX_USER_RIGHTS_RIGHTS,
                'location' => "usergroup.rights($group_id)",
            );
        }
        $this->activeTab = $active_tab;
        $this->locationHash = ($hash ? $hash : "usergroup.$active_tab($group_id)");
        $this->treeSelectedNode = "usergroup-{$group_id}";
    }

    function new_group_page() {
        $this->headerText = CONTROL_USER_GROUPS_ADD;
        $this->tabs = array(
            array(
                'id' => 'add',
                'caption' => CONTROL_USER_GROUPS_ADD,
                'location' => "usergroup.add()",
            ),
        );
        $this->activeTab = "add";
        $this->locationHash = "usergroup.add()";
        $this->treeSelectedNode = "usergroup";
    }

}

/**
 * Список прав, которыми обладает указанный пользователь или группа
 *
 * @param int $user_id
 * @param int $group_id
 *
 * @return array
 */
function nc_get_module_permission_info($user_id, $group_id) {
    /** @var Permission $perm */
    global $perm;
    $nc_core = nc_core::get_object();

    $permissions = array();

    $module_names = nc_db()->get_col("SELECT `Keyword`, `Module_Name` FROM `Module`", 1, 0);
    foreach ($module_names as $keyword => $name) {
        if (defined($name)) {
            $module_names[$keyword] = CONTROL_USER_MODULE . ' ' . sprintf(CONTROL_USER_RIGHTS_QUOTES, constant($name));
        } else {
            $module_names[$keyword] = CONTROL_USER_MODULE . ' ' . sprintf(CONTROL_USER_RIGHTS_QUOTES, $name);
        }
    }
    $module_names[NC_PERM_MODULE_ALL] = CONTROL_USER_MODULE_ALL;

    $data = (array)$nc_core->db->get_results(
        "SELECT * FROM `Module_Permission` WHERE `User_ID` = " . (int)$user_id . " AND `PermissionGroup_ID` = " .
        (int)$group_id,
        ARRAY_A
    );

    foreach ($data as $row) {
        $keyword = $row['Module_Keyword'];
        $permission_type = $row['PermissionType'];
        $site_id = $row['Catalogue_ID'];

        if ($permission_type === NC_PERM_MODULE_ALL) {
            $permissions_constant = 'NC_PERM_MODULE_' . strtoupper($keyword) . '_ALL';
            $permission_description = defined($permissions_constant) ? constant($permissions_constant) :
                CONTROL_USER_RIGHTS_MODULE_ALL;
        } else {
            $constant_name = 'NC_PERM_MODULE_' . strtoupper($keyword) . '_' . strtoupper($permission_type);
            $permission_description = defined($constant_name) ? constant($constant_name) : $permission_type;
        }

        if (!$row['Catalogue_ID']) {
            $permission_description .= ' ' . CONTROL_USER_MODULE_SITE_ANY;
        } else {
            try {
                $site_name = $nc_core->catalogue->get_by_id($site_id, 'Catalogue_Name');
            } catch (Exception $e) {
                $site_name = $site_id;
            }
            $permission_description .= ' ' . sprintf(CONTROL_USER_MODULE_SITE, $site_name);
        }

        $valid = '';
        if ($row['PermissionBegin']) {
            $valid .= NETCAT_COND_DATE_FROM . ' ' .
                date(NETCAT_CONDITION_DATETIME_FORMAT, strtotime($row['PermissionBegin']));
        }

        if ($row['PermissionEnd']) {
            $valid .= ($valid ? ' ' : '') . NETCAT_COND_DATE_TO . ' ' .
                date(NETCAT_CONDITION_DATETIME_FORMAT, strtotime($row['PermissionEnd']));
        }

        $permissions[] = array(
            'id' => $row['Module_Permission_ID'],
            'description' => nc_array_value($module_names, $keyword, $keyword) . ": $permission_description",
            'valid' => $valid ?: CONTROL_USER_RIGHTS_UNLIMITED,
            'can_change' => $perm->hasModulePermission($keyword, $permission_type, $site_id),
        );
    }

    // Сортировка модулей по имени
    usort($permissions, function ($a, $b) {
        return $a['description'] < $b['description'] ? -1 : 1;
    });

    return $permissions;
}

/**
 * Возвращает информацию о возможных правах на модули, которые можно присвоить другому пользователю
 * (с учётом прав, которые имеет текущий пользователь)
 *
 * @return array
 * @internal только для использования в форме присваивания прав
 */
function nc_get_all_module_permissions() {
    static $permissions = array();

    if ($permissions) {
        return $permissions;
    }

    /** @var Permission $perm */
    global $perm;

    if (!$perm->hasAnyModulePermission()) {
        return array();
    }

    $modules = (array)nc_db()->get_results("SELECT `Keyword`, `Module_Name`, `PerSitePermissions` FROM `Module`",
        ARRAY_A, 'Keyword');

    // «полный доступ» для каждого из сайтов
    foreach ($modules as $module_keyword => $row) {
        if ($perm->hasAnyModulePermission($module_keyword)) {
            if (defined($row['Module_Name'])) {
                $permissions[$module_keyword]['name'] = constant($row['Module_Name']);
            } else {
                $permissions[$module_keyword]['name'] = $row['Module_Name'];
            }
            $permissions[$module_keyword]['per_site'] = (bool)$row['PerSitePermissions'];
            $permissions[$module_keyword]['permissions'][NC_PERM_MODULE_ALL] = CONTROL_USER_RIGHTS_MODULE_ALL;
        }
    }

    // Сортировка модулей по имени
    uasort($permissions, function ($a, $b) {
        return $a['name'] < $b['name'] ? -1 : 1;
    });

    // «Все модули» — первым пунктом
    if ($perm->hasAnyModulePermission(NC_PERM_MODULE_ALL)) {
        $permissions = array_merge(array(
            NC_PERM_MODULE_ALL => array(
                'name' => CONTROL_USER_MODULE_ALL,
                'per_site' => true,
                'permissions' => array(NC_PERM_MODULE_ALL => CONTROL_USER_RIGHTS_MODULE_ALL),
            ),
        ), $permissions);
    }

    // Дополнительные права, если есть константы NC_PERM_MODULE_модуль_право
    $constants = get_defined_constants(true);
    $modules_string = strtoupper(implode('|', array_keys($modules)));
    foreach ($constants['user'] as $key => $value) {
        if (preg_match("/^NC_PERM_MODULE_($modules_string)_(.+)$/", $key, $matches)) {
            $module_keyword = strtolower($matches[1]);
            // Константа NC_PERM_MODULE_keyword_ALL может содержать кастомное название права «на всё»
            $permission = ($matches[2] === 'ALL' ? NC_PERM_MODULE_ALL : strtolower($matches[2]));
            if ($perm->hasModulePermission($module_keyword, $permission, null)) {
                $permissions[$module_keyword]['permissions'][$permission] = $value;
            }
        }
    }

    return $permissions;
}

/**
 * Сохранение данных о правах на модули из формы добавления прав
 *
 * @param array $properties
 * @param array $permissions
 *
 * @return int
 * @internal только для сохранения данных из формы добавления прав
 */
function nc_save_module_permissions_form(array $properties, array $permissions) {
    if (!$permissions) {
        return 10; // "Не выбран тип прав"
    }

    $all = NC_PERM_MODULE_ALL; // для краткости
    if ($properties['Module_Keyword'] === $all || (count($permissions) > 1 && in_array($all, $permissions, true))) {
        $permissions = array($all); // есть флаг «все права на модуль» — в таблице сохраняется только оно
    }

    /** @var Permission $perm */
    global $perm;
    $table = nc_db_table::make('Module_Permission');
    $db = nc_db();
    $all_saved = true;
    $has_duplicate_permissions = false;

    $conditions =
        "`User_ID` = '$properties[User_ID]' AND `PermissionGroup_ID` = '$properties[PermissionGroup_ID]'" .
        " AND `Module_Keyword` IN ('$all', '" . $db->escape($properties['Module_Keyword']) . "')" .
        " AND `Catalogue_ID` IN (0, " . (int)nc_array_value($permissions, 'Catalogue_ID') . ")" .
        " AND `PermissionBegin`" .
        ($properties['PermissionBegin'] ? " = '" . $db->escape($properties['PermissionBegin']) . "'" : ' IS NULL') .
        " AND `PermissionEnd`" .
        ($properties['PermissionEnd'] ? " = '" . $db->escape($properties['PermissionEnd']) . "'" : ' IS NULL');

    foreach ($permissions as $permission_type) {
        if ($perm->hasModulePermission($properties['Module_Keyword'], $permission_type, $properties['Catalogue_ID'])) {
            $exists = $db->get_var(
                "SELECT 1 
                   FROM `Module_Permission` 
                  WHERE `PermissionType` = '$all' 
                    AND $conditions
                  LIMIT 1"
            );
            if ($exists) {
                $has_duplicate_permissions = true;
                continue;
            }
            $table->insert(array_merge($properties, array('PermissionType' => $permission_type)));
        } else {
            $all_saved = false;
        }
    }

    if ($has_duplicate_permissions) {
        return -2;
    }

    return $all_saved ? 0 : -1;
}

/**
 * Удаляет запись о правах на модуль по её Module_Permission_ID
 *
 * @param int $id
 *
 * @return bool
 * @internal
 */
function nc_delete_module_permission($id) {
    /** @var Permission $perm */
    global $perm;
    $db = nc_db();

    $id = (int)$id;

    if (!$perm->isSupervisor()) {
        $data = $db->get_row("SELECT * FROM `Module_Permission` WHERE `Module_Permission_ID` = $id", ARRAY_A);
        if (!$perm->hasModulePermission($data['Module_Keyword'], $data['PermissionType'], $data['Catalogue_ID'])) {
            return false;
        }
    }

    $db->query("DELETE FROM `Module_Permission` WHERE `Module_Permission_ID` = $id");

    return true;
}

function nc_get_support_user_info() {
    if (!defined('NC_SUPPORT_LOGIN')) {
        return null;
    }

    $nc_core = nc_core::get_object();
    $db = $nc_core->db;
    $login = $db->escape(NC_SUPPORT_LOGIN);
    $user_data = $db->get_row("SELECT `User_ID`, `Checked` FROM `User` WHERE `$nc_core->AUTHORIZE_BY` = '$login'", ARRAY_A);

    if (!$user_data) {
        return null;
    }

    Permission::DeleteObsoletePerm();
    $user_permissions = Permission::forUser($user_data['User_ID']);

    if (!$user_data['Checked'] || !$user_permissions->isSupervisor()) {
        $is_supervisor = false;
        $expires = 0; // 0 ≈ expired
    } else {
        $is_supervisor = true;
        $admin_types = DIRECTOR . ', ' . SUPERVISOR;
        $max_date = $db->get_var(
            "SELECT MAX(`PermissionEnd`) 
               FROM `Permission` 
              WHERE `User_ID` = $user_data[User_ID]
                AND `AdminType` IN ($admin_types)"
        );
        // На момент написания в Permission даты используются непоследовательно — часть обрабатывается как timestamp на
        // стороне MySQL (чистка прав), часть — на datetime на стороне PHP (отображение в таблице прав).
        $expires = strtotime($max_date) ?: false; // false == never expires
    }

    return array(
        'User_ID' => $user_data['User_ID'],
        'HasSupervisorPermissions' => $is_supervisor,
        'SupervisorPermissionsExpireAt' => $expires,
    );
}

function nc_enable_support_user($duration, $existing_user) {
    if (!defined('NC_SUPPORT_LOGIN')) {
        return null;
    }

    if (nc_array_value($existing_user, 'SupervisorPermissionsExpireAt') === false) {
        return null;
    }

    $user_id = nc_array_value($existing_user, 'User_ID') ?: nc_create_support_user();
    if (!$user_id) {
        return null;
    }

    $duration = (int)$duration ?: 1;

    $db = nc_db();
    $db->query("UPDATE `User` SET `Checked` = 1 WHERE `User_ID` = $user_id");
    $db->query(
        "INSERT INTO `Permission`
            SET `User_ID` = $user_id,
                `AdminType` = " . SUPERVISOR .",
                `PermissionBegin` = NOW(),
                `PermissionEnd` = DATE_ADD(NOW(), INTERVAL $duration DAY)"
    );

    return nc_get_support_user_info();
}

function nc_create_support_user() {
    $nc_core = nc_core::get_object();

    $fields = array($nc_core->AUTHORIZE_BY => NC_SUPPORT_LOGIN);
    $add_fields = array('Checked' => 1);

    return $nc_core->user->add($fields, 1, nc_generate_random_string(20), $add_fields);
}
