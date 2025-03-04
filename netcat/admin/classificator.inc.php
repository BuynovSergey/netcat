<?php

if (!class_exists("nc_System"))
    die("Unable to load file.");
# вывод списка списков

function ClassificatorList($IsSystem) {

    global $db, $UI_CONFIG, $ADMIN_TEMPLATE, $perm, $ADMIN_FOLDER, $nc_core;

    $UI_CONFIG = new ui_config_classificators('classificator.list');
    $file_mode = intval($nc_core->input->fetch_get_post('fs'));

    $Select = "SELECT `Classificator_ID`, `Classificator_Name`, `Table_Name`, `System` from Classificator"; // where System='".$IsSystem."'";
    $Select .= " ORDER BY `Classificator_ID`";

    $Result = $db->get_results($Select, ARRAY_N);

    if ($countClassif = $db->num_rows) {
        ?>
        <form method='post' action='classificator.php'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td>
                    <table class='nc-table nc--striped nc--hovered' width='100%'>
                        <tr>
                            <th>ID</th>
                            <th width='70%'><?= CONTENT_CLASSIFICATORS_NAMEONE ?></th>
                            <th><?= CONTROL_SCLASS_TABLE ?></th>
                            <? if ($perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_DEL)) : ?>
                                <td align='center'>
                                    <div class='nc-icon nc--remove' title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div>
                                </td>
                            <? endif; ?>
                        </tr>
                        <?
                        foreach ($Result as $Array) {
                            $classificator_name = htmlspecialchars($Array[1], ENT_QUOTES);
                            if (!$Array[3]) { //Список не системный
                                if ($perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_VIEW, $Array[0], 0)) { //Если доступ к данному списку
                                    print "<tr>\n
                                               <td>" . $Array[0] . "</td>\n
                                               <td><a href=\"classificator.php?phase=4&ClassificatorID=" . $Array[0] . "\">" . $classificator_name . " (" . GetClassificatorCountByName($Array[2]) . ")</a></td>
                                               <td>" . $Array[2] . "</td>";
                                    if ($perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_DEL))
                                        print "      <td align=center>" . nc_admin_checkbox_simple("Delete" . $Array[0], $Array[0]) . "</td>";
                                    print "</tr>";
                                }
                            } else { //список системный. нужна еще проверка на доступность
                                if (!$perm->isDirectAccessClassificator(NC_PERM_ACTION_VIEW, $Array[0]))
                                    continue;

                                print "<tr>\n
                 <td >" . $Array[0] . "</td>\n
                 <td ><a href=\"classificator.php?phase=4&ClassificatorID=" . $Array[0] . "\">" . $classificator_name . " (" . GetClassificatorCountByName($Array[2]) . ")</a></td>
                <td >" . $Array[2] . "</td>";
                                if ($perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_DEL))
                                    print "      <td align=center></td>";
                                print "</tr>";
                            }
                        }
                        ?>
                    </table>
                </td>
            </tr>
        </table>
        <br>
    <?
    } else {
        nc_print_status(CONTENT_CLASSIFICATORS_ERR_NONE, 'info') . "<br><br>";
    }

    if ($perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADD)) {
        $UI_CONFIG->actionButtons[] = array("id" => "addClassificator",
            "caption" => CONTENT_CLASSIFICATORS_ADDLIST,
            "action" => "urlDispatcher.load('classificator" . ($file_mode ? '_fs' : '') . ".add(0)')",
            "align" => "left"
        );
        $UI_CONFIG->actionButtons[] = array("id" => "importClassificator",
            "caption" => CLASSIFICATORS_IMPORT_HEADER,
            "action" => "urlDispatcher.load('classificator" . ($file_mode ? '_fs' : '') . ".import(0)')",
            "align" => "left"
        );
    }
    if ($perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_DEL)) {
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTENT_CLASSIFICATORS_LIST_DELETE_SELECTED,
            "action" => "mainView.submitIframeForm()",
            "red_border" => true,
        );

        ?>
        <input type=hidden name=phase value=3>
        <input type='submit' class='hidden'>
        </form>
    <?
    }
}

###############################################################################
# форма добавления списка

function AddClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType = 0, $SortDirection = 0) {
    global $db, $UI_CONFIG, $nc_core;
    $UI_CONFIG = new ui_config_classificator('add', 0);

    $s_t0 = $s_t1 = $s_t2 = $s_d0 = $s_d1 = "";

    if ($SortType == 0)
        $s_t0 = ' selected';
    if ($SortType == 1)
        $s_t1 = ' selected';
    if ($SortType == 2)
        $s_t2 = ' selected';
    if ($SortDirection == 0)
        $s_d0 = ' selected';
    if ($SortDirection == 1)
        $s_d1 = ' selected';
    ?>
    <form method=post action="classificator.php">


        <?= CONTENT_CLASSIFICATORS_ADD_KEYWORD ?>:<br><?= nc_admin_input_simple("ClassificatorTable", $ClassificatorTable, 50, '', "maxlength='32'") ?>
        <br><br>
        <?=
        CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NAME
        ?>:<br><?=
        nc_admin_input_simple("ClassificatorName", $ClassificatorName, 50, '', "maxlength='32'")
        ?><br><br>

        <table cellspacing=0 cellpadding=0>
            <tr>
                <td>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_HEADER . ":<br>", 'SortType', array(0 => CLASSIFICATORS_SORT_TYPE_ID, 1 => CLASSIFICATORS_SORT_TYPE_NAME, 2 => CLASSIFICATORS_SORT_TYPE_PRIORITY), $SortType, "tyle='width:110px;'")
                    ?>
                </td>
                <td width=4>&nbsp;</td>
                <td>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_DIRECTION . ":<br>", 'SortDirection', array(0 => CLASSIFICATORS_SORT_ASCENDING, 1 => CLASSIFICATORS_SORT_DESCENDING), $SortDirection, "tyle='width:160px;'")
                    ?>
                </td>
            </tr>
        </table>

        <hr size=1 color=cccccc>

        <input type=hidden name=phase value=2>
        <?php echo $nc_core->token->get_input(); ?>
        <?
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTENT_CLASSIFICATORS_ADDLIST,
            "action" => "mainView.submitIframeForm()");
        ?>
        <input type='submit' class='hidden'/>
    </form>


<?
}

function AddClassificator_modal() {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $s_t0 = $s_t1 = $s_t2 = $s_d0 = $s_d1 = "";

    if ($SortType == 0)
        $s_t0 = ' selected';
    if ($SortType == 1)
        $s_t1 = ' selected';
    if ($SortType == 2)
        $s_t2 = ' selected';
    if ($SortDirection == 0)
        $s_d0 = ' selected';
    if ($SortDirection == 1)
        $s_d1 = ' selected';

    echo nc_get_simple_modal_header(CONTENT_CLASSIFICATORS_LIST_ADD);

    ?>

    <form method='post' id='adminForm' class='nc-form' action="classificator.php">


        <?= CONTENT_CLASSIFICATORS_ADD_KEYWORD ?>:<br><?= nc_admin_input_simple("ClassificatorTable", $ClassificatorTable, 50, '', "maxlength='32'") ?>
        <br><br>
        <?=
        CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NAME
        ?>:<br><?=
        nc_admin_input_simple("ClassificatorName", $ClassificatorName, 50, '', "maxlength='32'")
        ?><br><br>

        <table cellspacing=0 cellpadding=0>
            <tr>
                <td>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_HEADER . ":<br>", 'SortType', array(0 => CLASSIFICATORS_SORT_TYPE_ID, 1 => CLASSIFICATORS_SORT_TYPE_NAME, 2 => CLASSIFICATORS_SORT_TYPE_PRIORITY), $SortType, "tyle='width:110px;'")
                    ?>
                </td>
                <td width=4>&nbsp;</td>
                <td>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_DIRECTION . ":<br>", 'SortDirection', array(0 => CLASSIFICATORS_SORT_ASCENDING, 1 => CLASSIFICATORS_SORT_DESCENDING), $SortDirection, "tyle='width:160px;'")
                    ?>
                </td>
            </tr>
        </table>

        <input type=hidden name=phase value='2'/>
        <?php echo $nc_core->token->get_input(); ?>

    </form>
    <?
    echo nc_get_simple_modal_footer();

}

###############################################################################

function IsTableExist($ClassificatorTable) {
    global $db;

    $Result = $db->query("show tables like 'Classificator_" . $db->escape($ClassificatorTable) . "'");
    return ($db->num_rows > 0);
}

###############################################################################

function IsClassificatorExist($ClassificatorTable) {
    global $db;

    $Result = $db->query("select Classificator_ID from Classificator where Table_Name='" . $db->escape($ClassificatorTable) . "'");
    return ($db->num_rows > 0);
}

###############################################################################
# полчение названия списка по его ID

function GetClassificatorNameByID($ClassificatorID, $reset = false) {
    static $storage = array();
    if ($reset) {
        $storage = array();
    }
    $ClassificatorID = +$ClassificatorID;
    if (!isset($storage[$ClassificatorID])) {
        $storage[$ClassificatorID] = nc_Core::get_object()->db->get_var("select Classificator_Name from Classificator where Classificator_ID='" . $ClassificatorID . "'");
    }
    return $storage[$ClassificatorID];

}

###############################################################################
# полчение кол-ва записей в конкретном списке

function GetClassificatorCountByName($ClassificatorName) {
    global $db;

    return $db->get_var("select count(*) from `Classificator_" . $db->escape($ClassificatorName) . "`");
}

###############################################################################
# получение имени таблицы, где хранится список записей списка, по его ID

function GetTableNameByID($ClassificatorID) {
    global $db;
    static $storage = array();
    $ClassificatorID = intval($ClassificatorID);
    if (!isset($storage[$ClassificatorID])) {
        $storage[$ClassificatorID] = $db->get_var("select Table_Name from Classificator where Classificator_ID='" . $ClassificatorID . "'");
    }
    return $storage[$ClassificatorID];

}

###############################################################################

function GetSortTypeByID($ClassificatorID) {
    global $db;

    return $db->get_var("SELECT Sort_Type FROM Classificator WHERE Classificator_ID='" . intval($ClassificatorID) . "'");
}

###############################################################################

function GetSortDirectionByID($ClassificatorID) {
    global $db;
    return $db->get_var("SELECT Sort_Direction FROM Classificator WHERE Classificator_ID='" . intval($ClassificatorID) . "'");
}

###############################################################################

function GetLastPriorityByID($ClassificatorID) {
    global $db;
    $Table_Name = GetTableNameByID($ClassificatorID);
    $Array = $db->get_var("SELECT MAX(${Table_Name}_Priority) FROM Classificator_${Table_Name}");
    if ($Array)
        return $Array;

    return 0;
}

###############################################################################
# добавление списка

function AddClassificatorCompleted($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection) {
    global $db, $perm, $nc_core;

    $System += 0;
    $SortType += 0;
    $SortDirection += 0;
    $new_list_id = 0;

    $data = array(
        'Classificator_Name' => $ClassificatorName,
        'Table_Name' => $ClassificatorTable,
        'System' => $System,
        'Sort_Type' => $SortType,
        'Sort_Direction' => $SortDirection,
    );

    $nc_core->event->execute(nc_Event::BEFORE_LIST_CREATED, 0, $data);

    $err_msg = '';
    if ($ClassificatorName == "") {
        $err_msg = CONTENT_CLASSIFICATORS_ERROR_NAME;
    } else if ($ClassificatorTable == "") {
        $err_msg = CONTENT_CLASSIFICATORS_ERROR_KEYWORD;
    } else if (strspn(strtolower($ClassificatorTable), "abcdefghijklmnopqrstuvwxyz0123456789_") != strlen($ClassificatorTable)) {
        $err_msg = CONTENT_CLASSIFICATORS_ERROR_KEYWORDINV;
    } else if (strspn(strtolower(substr($ClassificatorTable, 0, 1)), "abcdefghijklmnopqrstuvwxyz") != 1) {
        $err_msg = CONTENT_CLASSIFICATORS_ERROR_KEYWORDFL;
    } else if (IsClassificatorExist($ClassificatorTable)) {
        $err_msg = CONTENT_CLASSIFICATORS_ERROR_KEYWORDAE;
    } else if (IsTableExist($ClassificatorTable)) {
        $err_msg = CONTENT_CLASSIFICATORS_ERROR_KEYWORDREZ;
    }
    if ($err_msg) {
        nc_print_status($err_msg, 'error');
        AddClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection);
    } else {

        print "<br>\n";
        $new_list_id = nc_db_table::make('Classificator')->insert($data);

        if (!$new_list_id) {
            return 0;
        }

        $create_table_sql = "CREATE TABLE `Classificator_$ClassificatorTable` (
            `${ClassificatorTable}_ID` int(11) NOT NULL AUTO_INCREMENT,
            `${ClassificatorTable}_Name` char(255) DEFAULT '' NOT NULL,
            `${ClassificatorTable}_Priority` int(11) DEFAULT NULL,
            `Value` text DEFAULT NULL,
            `Checked` int(1) default 1,
            `CustomSettings` text NULL DEFAULT NULL,
            PRIMARY KEY (`${ClassificatorTable}_ID`)
        )";

        global $LinkID;
        if ((float)mysqli_get_server_info($LinkID) >= 4.1) {
            global $MYSQL_CHARSET;
            $create_table_sql .= " DEFAULT CHARSET=$MYSQL_CHARSET";
        }

        $db->query($create_table_sql);

        $nc_core->event->execute(nc_Event::AFTER_LIST_CREATED, $new_list_id, $data);
    }

    return $new_list_id;
}

###############################################################################
# обновление списка

function UpdateClassificatorCompleted($ClassificatorID, $ClassificatorName, $System, $SortType, $SortDirection) {
    global $db, $nc_core;

    $return_value = 0;
    $ClassificatorID = (int)$ClassificatorID;

    $data = array(
        'Classificator_Name' => $db->escape($ClassificatorName),
        'Table_Name' => GetTableNameByID($ClassificatorID),
        'System' => IsSystemClassificator($ClassificatorID),
        'Sort_Type' => (int)$SortType,
        'Sort_Direction' => (int)$SortDirection,
    );

    $nc_core->event->execute(nc_Event::BEFORE_LIST_UPDATED, $ClassificatorID, $data);

    if ($ClassificatorName == "") {
        nc_print_status(CONTENT_CLASSIFICATORS_ERROR_NAME, 'error');
    } else {
        nc_db_table::make('Classificator')->where_id($ClassificatorID)->update($data);
        $return_value = (int)$db->rows_affected;
        $nc_core->event->execute(nc_Event::AFTER_LIST_UPDATED, $ClassificatorID, $data);
    }

    if ($db->captured_errors) {
        $db->vardump($db->captured_errors);
    }

    return $return_value;
}

###############################################################################

function ClassificatorConfirmDelete($ids) {
    global $UI_CONFIG, $nc_core;
    if (!empty($ids)) {
        print "<form action='classificator.php' method='post'>";
        print CONTROL_CONTENT_SUBDIVISION_INDEX_DELETECONFIRMATION . ":";
        print "<ul>";
        foreach ($ids as $id) {
            print "<li>" . $id . " " . GetClassificatorNameByID($id) . "</li>";
            print "<input type='hidden' name='Delete" . $id . "' value='" . $id . "' />";
        }
        print "</ul>";
        print "<input type='hidden' name='phase' value='31' />";
        print $nc_core->token->get_input();
        print "</form>";


        $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTROL_FIELD_CONFIRM_REMOVAL,
            "action" => "mainView.submitIframeForm()",
            "red_border" => true,
        );
    }

    return;
}

# удаление списка

function DeleteClassificator($ClassificatorID) {
    global $db, $nc_core;
    $ClassificatorID = intval($ClassificatorID);

    $data = $nc_core->list->get_by_id($ClassificatorID);
    $nc_core->event->execute(nc_Event::BEFORE_LIST_DELETED, $ClassificatorID, $data);

    if (!IsSystemClassificator($ClassificatorID)) {

        $TableToDrop = GetTableNameByID($ClassificatorID);

        $db->query("drop table Classificator_$TableToDrop");
        $db->query("delete from Classificator where Classificator_ID='" . $ClassificatorID . "'");
        $db->query("DELETE FROM `Permission` WHERE Catalogue_ID='" . $ClassificatorID . "' AND AdminType = '15'");

        $nc_core->event->execute(nc_Event::AFTER_LIST_DELETED, $ClassificatorID, $data);

        return 1;
    } else {
        nc_print_status(sprintf(CLASSIFICATORS_ERROR_DELETEONE_SYS, $ClassificatorID), 'error');
    }

}

###############################################################################
# форма изменения списка, информации по списку

function OneClassificatorList($ClassificatorID, $SortType, $SortDirection) {
    global $db, $UI_CONFIG, $ADMIN_PATH, $ADMIN_TEMPLATE, $perm, $nc_core;
    if (!+$_REQUEST['isNaked']) {
        echo '<br />';
    }

    $UI_CONFIG = new ui_config_classificator('edit', $ClassificatorID);

    $s_t0 = $s_t1 = $s_t2 = $s_d0 = $s_d1 = "";

    if ($SortType == 0)
        $s_t0 = ' selected';
    if ($SortType == 1)
        $s_t1 = ' selected';
    if ($SortType == 2)
        $s_t2 = ' selected';
    if ($SortDirection == 0)
        $s_d0 = ' selected';
    if ($SortDirection == 1)
        $s_d1 = ' selected';

    $TableName = GetTableNameByID($ClassificatorID);
    $Name = GetClassificatorNameByID($ClassificatorID, true);
    $isSystem = IsSystemClassificator($ClassificatorID);
    if ($isSystem) {
        if (!$perm->isDirectAccessClassificator(NC_PERM_ACTION_VIEW, $ClassificatorID)) {
            nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
            EndHtml();
            exit();
        }
        $admin_cl = $perm->isDirectAccessClassificator(NC_PERM_ACTION_ADMIN, $ClassificatorID);
        $access_to_add = $perm->isDirectAccessClassificator(NC_PERM_ACTION_ADDELEMENT, $ClassificatorID);
    } else {
        $admin_cl = $perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADMIN, $ClassificatorID);
        $access_to_add = $perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADDELEMENT, $ClassificatorID);
    }

    if (!$admin_cl): //Есть доступа к измененению названия списка..?
        ?><br /><br/>
        <table cellspacing='3' cellpadding='3'>
            <tr>
                <td><?= CONTROL_SCLASS_TABLE_NAME ?>:</td>
                <td><?= $TableName ?></td>
            </tr>
            <tr>
                <td><br/></td>
                <td></td>
            </tr>
            <tr>
                <td><?=
                    CONTROL_SCLASS_LISTING_NAME
                    ?>:
                </td>
                <td><?=
                    $Name
                    ?></td>
            </tr>
        </table>
        <br>

    <? else : ?>
        <div id='nc_admin_mode_content'>
        <form method='post' action='classificator.php'>
        <?= CONTROL_SCLASS_TABLE_NAME ?>: <?= $TableName ?><br><br>
        <table cellspacing=0 cellpadding=0>
            <tr>
                <td nowrap>
                    <?= CONTROL_SCLASS_LISTING_NAME ?>:<br>
                    <?= nc_admin_input_simple('ClassificatorName', $Name, 50, '', "maxlength='32'") ?>
                </td>
                <td width=4>&nbsp;</td>
                <td nowrap>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_HEADER . ":<br>", 'SortType', array(0 => CLASSIFICATORS_SORT_TYPE_ID, 1 => CLASSIFICATORS_SORT_TYPE_NAME, 2 => CLASSIFICATORS_SORT_TYPE_PRIORITY), $SortType, "tyle='width:110px;'")
                    ?>
                </td>
                <td width=4>&nbsp;</td>
                <td nowrap>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_DIRECTION . ":<br>", 'SortDirection', array(0 => CLASSIFICATORS_SORT_ASCENDING, 1 => CLASSIFICATORS_SORT_DESCENDING), $SortDirection, "tyle='width:160px;'")
                    ?>
                </td>
            </tr>
        </table>
        <hr size=1 color=cccccc>


    <?
    endif;

    $Sort_Order = " ORDER BY ";
    switch ($SortType) {
        case 1:
            $Sort_Order .= "${TableName}_Name";
            break;
        case 2:
            $Sort_Order .= "${TableName}_Priority";
            break;
        default:
            $Sort_Order .= "${TableName}_ID";
            break;
    }

    if ($SortDirection == 1)
        $Sort_Order .= " DESC";

    $Select = "select ${TableName}_ID, ${TableName}_Name, ${TableName}_Priority, Checked from Classificator_${TableName}";
    $Select .= $Sort_Order;
    $db->last_error = '';
    $Result = $db->get_results($Select, ARRAY_N);

    // если произошла ошибка sql, ее можно попробовать исправить
    if ($db->last_error) {
        $db->query("ALTER TABLE `Classificator_" . $TableName . "`
         ADD `Value` text default null,
         ADD `Checked` int(1) default 1");
        $db->last_error = '';
        // делаем запрос снова
        $Result = $db->get_results($Select, ARRAY_N);
        if ($db->last_error) {
            nc_print_status('DB query error', 'error');
            return false;
        }
    }

    if ($countClassif = $db->num_rows) {
        ?>

        <table border=0 cellpadding=0 cellspacing=0 width=100%>
            <tr>
                <td>

                    <table class='nc-table nc--striped nc--hovered nc--small' width=100%>
                        <tr>
                            <th>ID</th>
                            <th width=90%><?= CONTENT_CLASSIFICATORS_ELEMENT ?></th>
                            <? if ($admin_cl) : ?>
                                <th class='nc-text-center'>
                                    <div class='icons icon_type_bool' title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ON ?>'></div>
                                </th>
                                <th class='nc-text-center'>
                                    <div class='icons icon_prior' title='<?= CLASSIFICATORS_SORT_PRIORITY_HEADER ?>'></div>
                                </th>
                                <th class='nc-text-center'>
                                    <div class='icons icon_delete' title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div>
                                </th>
                            <? endif; ?>
                        </tr>

                        <?
                        foreach ($Result as $Array) {
                            $item_name = (empty($Array[1]) && !is_numeric($Array[1])) ? CONTENT_CLASSIFICATORS_NO_NAME : $Array[1];
                            print "<tr>";
                            print "<td>" . $Array[0] . "</td>";
                            print "<td><a onclick='parent.nc_form(this.href); return false;' href='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "action.php?ctrl=admin.list&action=edit_item&classificator_id=" . $ClassificatorID . "&item_id=" . $Array[0] . "'>" . $item_name . "</a></td>";
                            if ($admin_cl) {
                                print "<td align=center>" . nc_admin_checkbox_simple("check_" . $Array[0], '', '', $Array[3]) . "</td>";
                                print "<td align=center>" . nc_admin_input_simple("Priority" . $Array[0], $Array[2], 3, '', "class='s' maxlength='5'") . "</td>";
                                print "<td align=center>" . nc_admin_checkbox_simple("Delete" . $Array[0], $Array[0]) . "</td>";
                            }
                            print "</tr>\r\n";
                        }
                        ?>
                    </table>
                </td>
            </tr>
        </table><br>
    <?
    } else {
        nc_print_status(CONTENT_CLASSIFICATORS_ERR_ELEMENTNONE, 'info');
    }
    if ($access_to_add) { //Показать или нет кнопуку "Добавить элемент"
        $UI_CONFIG->actionButtons[] = array("id" => "addClassificatorItem",
            "caption" => CONTENT_CLASSIFICATORS_ELEMENTS_ADDONE,
            //"action" => "urlDispatcher.load('classificator.item" . ($file_mode ? '_fs' : '') . ".add(".$ClassificatorID.")')",
            //"action" => "parent.nc_form('{$ADMIN_PATH}classificator.php?phase=8&ClassificatorID={$ClassificatorID}')",
            "action" => "parent.nc_form('" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "action.php?ctrl=admin.list&action=add_item&classificator_id={$ClassificatorID}')",
            "align" => "left");
    }
    if ($admin_cl) {

        $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTENT_CLASSIFICATORS_SAVE,
            "action" => "mainView.submitIframeForm()");
        ?>
        <?php echo $nc_core->token->get_input(); ?>
        <input type=hidden name=ClassificatorID value=<?= $ClassificatorID ?>>
        <input type=hidden name=phase value=5>
        <input type='submit' class='hidden'>
        </form>
        </div>
    <?

    }
}

###############################################################################

function DeleteFromOneClassificator($ClassificatorID, $IdInClassificator) {
    global $db, $nc_core;

    $item_data = $nc_core->list->get_item($ClassificatorID, $IdInClassificator);
    $nc_core->event->execute(nc_Event::BEFORE_LIST_ITEM_DELETED, $ClassificatorID, $IdInClassificator, $item_data);

    $TableName = $db->escape(GetTableNameByID($ClassificatorID));
    $IdInClassificator = (int)$IdInClassificator;

    if (!IsSystemClassificator($ClassificatorID)) {
        $query = "delete from `Classificator_" . $TableName . "` where `" . $TableName . "_ID`='" . $IdInClassificator . "'";
        $db->query($query);
        $nc_core->event->execute(nc_Event::AFTER_LIST_ITEM_DELETED, $ClassificatorID, $IdInClassificator, $item_data);
        return 1;
    }

    nc_print_status(CONTENT_CLASSIFICATORS_ERR_SYSDEL, 'error');
    return 0;
}

###############################################################################

function UpdatePriorityForOneClassificator($ClassificatorID, $IdInClassificator, $Priority) {
    global $db;
    $Priority = intval($Priority);
    $IdInClassificator = intval($IdInClassificator);
    $TableName = GetTableNameByID($ClassificatorID);
    $TableName = $db->escape($TableName);

    $s = "update `Classificator_" . $TableName . "` set `" . $TableName . "_Priority`='" . $Priority . "'";
    $s .= " where `" . $TableName . "_ID`='" . $IdInClassificator . "'";
    $db->query($s);
}

###############################################################################

function IsSystemClassificator($ClassificatorID) {
    global $db;
    return $db->get_var("SELECT `System` FROM `Classificator` WHERE `Classificator_ID` = '" . (int)$ClassificatorID . "'");
}

###############################################################################
# форма добавления записи в список

function InsertInOneClassificator($ClassificatorID) {
    global $db, $UI_CONFIG, $nc_core;

    $UI_CONFIG = new ui_config_classificator_item('item.add', $ClassificatorID, 0);

    $Name = GetClassificatorNameByID($ClassificatorID);
    $LastPriority = GetLastPriorityByID($ClassificatorID);
    $LastPriority++;
    ?>

    <form method='post' action='classificator.php'>
        <div><?= CONTENT_CLASSIFICATORS_ELEMENT_NAME; ?>:</div>
        <div><?= nc_admin_input_simple('NameInClassificator', '', 50, '', "maxlength='256'"); ?></div>
        <div><?= CLASSIFICATORS_SORT_PRIORITY_HEADER; ?>:</div>
        <div><?= nc_admin_input_simple('Priority', $LastPriority, 6, '', "maxlength='5'"); ?></div>
        <div><?= nc_admin_textarea_simple('ValueInClassificator', '', "<div>" . CONTENT_CLASSIFICATORS_ELEMENT_VALUE . ":</div>", 7, 0, '', 'soft'); ?></div>

        <?php echo $nc_core->token->get_input(); ?>
        <input type='hidden' name='phase' value='9'/>
        <input type='hidden' name='ClassificatorID' value='<? print $ClassificatorID; ?>'/>
        <input type='submit' class='hidden'/>
    </form>

    <?
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
        "caption" => CONTENT_CLASSIFICATORS_ELEMENTS_ADDONE,
        "action" => "mainView.submitIframeForm()"
    );
}


function InsertInOneClassificator_modal($ClassificatorID) {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $Name = GetClassificatorNameByID($ClassificatorID);
    $LastPriority = GetLastPriorityByID($ClassificatorID);
    $LastPriority++;

    echo nc_get_simple_modal_header($Name);
    ?>

    <form method='post' id='adminForm' class='nc-form' action='classificator.php'>

        <div><?= CONTENT_CLASSIFICATORS_ELEMENT_NAME; ?>:</div>
        <div><?= nc_admin_input_simple('NameInClassificator', '', 50, '', "maxlength='256'"); ?></div>
        <div><?= CLASSIFICATORS_SORT_PRIORITY_HEADER; ?>:</div>
        <div><?= nc_admin_input_simple('Priority', $LastPriority, 6, '', "maxlength='5'"); ?></div>
        <div><?= nc_admin_textarea_simple('ValueInClassificator', '', "<div>" . CONTENT_CLASSIFICATORS_ELEMENT_VALUE . ":</div>", 7, 0, '', 'soft'); ?></div>

        <?= $nc_core->token->get_input(); ?>
        <input type='hidden' name='phase' value='9'/>
        <input type='hidden' name='ClassificatorID' value='<? print $ClassificatorID; ?>'/>
    </form>
    <script>prepare_message_form();</script>
    </div>

    <div class='nc_admin_form_buttons'>
        <button type='button' class='nc_admin_metro_button nc-btn nc--blue' disable><?= NETCAT_REMIND_SAVE_SAVE; ?></button>
        <button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <style>
        a { color: #1a87c2; }
        a:hover { text-decoration: none; }
        a img { border: none; }
        p { margin: 0px; padding: 0px 0px 18px 0px; }
        h2 { font-size: 20px; font-family: 'Segoe UI', SegoeWP, Arial; color: #333333; font-weight: normal; margin: 0px; padding: 20px 0px 10px 0px; line-height: 20px; }
        form { margin: 0px; padding: 0px; }
        input { outline: none; }
        .clear { margin: 0px; padding: 0px; font-size: 0px; line-height: 0px; height: 1px; clear: both; float: none; }
        select, input, textarea { border: 1px solid #dddddd; }
        :focus { outline: none; }
        .input { outline: none; border: 1px solid #dddddd; }
    </style>
<?
}


###############################################################################
#добавление записи в список

function InsertInOneClassificatorCompleted($ClassificatorID, $NameInClassificator, $Priority, $ValueInClassificator) {
    global $db;

    $TableName = GetTableNameByID($ClassificatorID);

    $NameInClassificator = $db->escape($NameInClassificator);
    $ValueInClassificator = $db->escape($ValueInClassificator);
    $Priority += 0;

    $TableName = GetTableNameByID($ClassificatorID);
    $Insert = "INSERT INTO `Classificator_" . $TableName . "` (`" . $TableName . "_Name`, `" . $TableName . "_Priority`, `Value`)
    values ('" . $NameInClassificator . "', '" . $Priority . "', '" . $ValueInClassificator . "')";

    return $db->query($Insert);
}

###############################################################################

function UpdateOneClassificator($ClassificatorID, $IdInClassificator) {
    global $db, $UI_CONFIG, $perm, $nc_core;
    $IdInClassificator = intval($IdInClassificator);
    $UI_CONFIG = new ui_config_classificator_item('item.edit', $ClassificatorID, $IdInClassificator);

    $TableName = $db->escape(GetTableNameByID($ClassificatorID));
    $Name = GetClassificatorNameByID($ClassificatorID);
    $isSystem = IsSystemClassificator($ClassificatorID);

    if ($isSystem) {
        if (!$perm->isDirectAccessClassificator(NC_PERM_ACTION_VIEW, $ClassificatorID)) {
            nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
            EndHtml();
            exit();
        }
        $edit_element = $perm->isDirectAccessClassificator(NC_PERM_ACTION_EDIT, $ClassificatorID);
    } else {
        $edit_element = $perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_EDIT, $ClassificatorID);
    }

    print "
    <FORM METHOD=\"POST\" ACTION=\"classificator.php\">\n";

    $Array = $db->get_row("select `" . $TableName . "_Name`, `Value`
                         from `Classificator_" . $TableName . "`
                         where `" . $TableName . "_ID` = '" . $IdInClassificator . "'", ARRAY_N);

    if (!$edit_element) {
        print "" . CONTENT_CLASSIFICATORS_ELEMENT_NAME . ": " . $Array;
        return;
    }

    echo "" . CONTENT_CLASSIFICATORS_ELEMENT_NAME . ":<br>" .
        nc_admin_input_simple('NameInClassificator', $Array[0], 0, 'width: 30%;', "maxlength='256'") . "
  <br><br>" . nc_admin_textarea_simple('ValueInClassificator', $Array[1], "" . CONTENT_CLASSIFICATORS_ELEMENT_VALUE . ":<br>", 7, 0, "style='width: 30%;'", 'soft') . "

  <input type='hidden' name='phase' value='11'>
  <input type='hidden' name='ClassificatorID' value='" . $ClassificatorID . "'>
  <input type='hidden' name='IdInClassificator' value='" . $IdInClassificator . "'>
  <input type='submit' class='hidden'>
  " . $nc_core->token->get_input() . "
  </form>";


    $UI_CONFIG->actionButtons[] = array("id" => "submit",
        "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
        "action" => "mainView.submitIframeForm()"
    );
}

function UpdateOneClassificator_modal($ClassificatorID, $IdInClassificator) {
    global $UI_CONFIG, $perm;
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $IdInClassificator = +$IdInClassificator;

    $TableName = $db->escape(GetTableNameByID($ClassificatorID));
    $Name = GetClassificatorNameByID($ClassificatorID);
    $isSystem = IsSystemClassificator($ClassificatorID);

    if ($isSystem) {
        if (!$perm->isDirectAccessClassificator(NC_PERM_ACTION_VIEW, $ClassificatorID)) {
            nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
            EndHtml();
            exit();
        }
        $edit_element = $perm->isDirectAccessClassificator(NC_PERM_ACTION_EDIT, $ClassificatorID);
    } else {
        $edit_element = $perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_EDIT, $ClassificatorID);
    }

    echo nc_get_simple_modal_header($Name);

    echo "<form method='post' id='adminForm' class='nc-form' action='classificator.php'>";

    $SQL = "select `" . $TableName . "_Name`,
        `Value`
        from `Classificator_" . $TableName . "`
        where `" . $TableName . "_ID` = '" . $IdInClassificator . "'";

    $Array = $db->get_row($SQL, ARRAY_N);

    if (!$edit_element) {
        print "" . CONTENT_CLASSIFICATORS_ELEMENT_NAME . ": " . $Array;
        return;
    }

    echo "
        <div>" . CONTENT_CLASSIFICATORS_ELEMENT_NAME . ":</div>
        " .
        "
        <div>" . nc_admin_input_simple('NameInClassificator', $Array[0], 0, 'width: 30%;', "maxlength='256'") . "</div>
        <div>" . nc_admin_textarea_simple('ValueInClassificator', $Array[1], "
            <div>" . CONTENT_CLASSIFICATORS_ELEMENT_VALUE . ":</div>
            ", 7, 0, "", 'soft') . "
        </div>

        <input type='hidden' name='phase' value='11'>
        <input type='hidden' name='ClassificatorID' value='" . $ClassificatorID . "'>
        <input type='hidden' name='IdInClassificator' value='" . $IdInClassificator . "'>
        " . $nc_core->token->get_input() . "
    </form>
    <script>prepare_message_form();</script>";
    ?>
    </div>

    <div class='nc_admin_form_buttons'>
        <button type='button' class='nc_admin_metro_button nc-btn nc--blue' disable><?= NETCAT_REMIND_SAVE_SAVE; ?></button>
        <button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <style>
        a { color: #1a87c2; }
        a:hover { text-decoration: none; }
        a img { border: none; }
        p { margin: 0px; padding: 0px 0px 18px 0px; }
        h2 { font-size: 20px; font-family: 'Segoe UI', SegoeWP, Arial; color: #333333; font-weight: normal; margin: 0px; padding: 20px 0px 10px 0px; line-height: 20px; }
        form { margin: 0px; padding: 0px; }
        input { outline: none; }
        .clear { margin: 0px; padding: 0px; font-size: 0px; line-height: 0px; height: 1px; clear: both; float: none; }
        select, input, textarea { border: 1px solid #dddddd; }
        :focus { outline: none; }
        .input { outline: none; border: 1px solid #dddddd; }
    </style>
<?
}


###############################################################################

function UpdateOneClassificatorCompleted($ClassificatorID, $IdInClassificator, $NameInClassificator, $ValueInClassificator) {
    global $db;

    $TableName = GetTableNameByID($ClassificatorID);
    $IdInClassificator = intval($IdInClassificator);

    $Update = "update Classificator_$TableName
             set ${TableName}_Name='" . $db->escape($NameInClassificator) . "',
             Value='" . $db->escape($ValueInClassificator) . "'";
    $Update .= " where ${TableName}_ID=$IdInClassificator";

    $db->query($Update);
}

###############################################################################

function GetOneClassificatorName($ClassificatorID, $IdInClassificator) {
    global $db;
    $TableName = $db->escape(GetTableNameByID($ClassificatorID));
    $IdInClassificator = intval($IdInClassificator);
    @$Array = $db->get_var("select `" . $TableName . "_Name` from `Classificator_" . $TableName . "` where `" . $TableName . "_ID`='" . $IdInClassificator . "'");
    return $Array;
}

###############################################################################

function ImportClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType = 0, $SortDirection = 0) {
    global $db, $UI_CONFIG, $nc_core;
    $UI_CONFIG = new ui_config_classificator('import', $ClassificatorID);
    $s_t0 = $s_t1 = $s_t2 = $s_d0 = $s_d1 = "";

    if ($SortType == 0)
        $s_t0 = ' selected';
    if ($SortType == 1)
        $s_t1 = ' selected';
    if ($SortType == 2)
        $s_t2 = ' selected';
    if ($SortDirection == 0)
        $s_d0 = ' selected';
    if ($SortDirection == 1)
        $s_d1 = ' selected';
    ?>
    <br/>
    <form enctype='multipart/form-data' action=classificator.php method=post>
        <input type=hidden name=MAX_FILE_SIZE value=1000000>
        <input type=hidden name=phase value=13>

        <?=
        CONTENT_CLASSIFICATORS_ADD_KEYWORD
        ?>:<br><?= nc_admin_input_simple('ClassificatorTable', $ClassificatorTable, 50, '', "maxlength='32'") ?><br><br>
        <?= CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NAME ?>:<br><?= nc_admin_input_simple('ClassificatorName', $ClassificatorName, 50, '', "maxlength='32'") ?>
        <br><br>
        <?=
        CLASSIFICATORS_IMPORT_FILE
        ?>:<br><input size=40 name=FileCSV type=file><br><br>
        <table cellspacing=0 cellpadding=0>
            <tr>
                <td>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_HEADER . ":<br>", 'SortType', array(CLASSIFICATORS_SORT_TYPE_ID, CLASSIFICATORS_SORT_TYPE_NAME, CLASSIFICATORS_SORT_TYPE_PRIORITY), $SortType, "style='width: 100%;'")
                    ?>
                </td>
                <td width=4>&nbsp;</td>
                <td>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_DIRECTION . ":<br>", 'SortDirection', array(CLASSIFICATORS_SORT_ASCENDING, CLASSIFICATORS_SORT_DESCENDING), $SortDirection, "style='width: 100%;'")
                    ?>
                </td>
            </tr>
        </table>
        <input type='submit' class='hidden'>
        <?php echo $nc_core->token->get_input(); ?>
    </form>
    <br>* <?= CLASSIFICATORS_IMPORT_DESCRIPTION ?>

    <?
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
        "caption" => CLASSIFICATORS_IMPORT_BUTTON,
        "action" => "mainView.submitIframeForm()");
}

function ImportClassificator_modal() {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $s_t0 = $s_t1 = $s_t2 = $s_d0 = $s_d1 = "";

    if ($SortType == 0)
        $s_t0 = ' selected';
    if ($SortType == 1)
        $s_t1 = ' selected';
    if ($SortType == 2)
        $s_t2 = ' selected';
    if ($SortDirection == 0)
        $s_d0 = ' selected';
    if ($SortDirection == 1)
        $s_d1 = ' selected';

    echo nc_get_simple_modal_header(CLASSIFICATORS_IMPORT_HEADER);
    ?>

    <form id='adminForm' class='nc-form' enctype='multipart/form-data' action='classificator.php' method='post'>
        <input type=hidden name=MAX_FILE_SIZE value=1000000>
        <input type=hidden name=phase value=13>

        <?=
        CONTENT_CLASSIFICATORS_ADD_KEYWORD
        ?>:<br><?= nc_admin_input_simple('ClassificatorTable', $ClassificatorTable, 50, '', "maxlength='32'") ?><br><br>
        <?= CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NAME ?>:<br><?= nc_admin_input_simple('ClassificatorName', $ClassificatorName, 50, '', "maxlength='32'") ?>
        <br><br>
        <?=
        CLASSIFICATORS_IMPORT_FILE
        ?>:<br><input size=40 name=FileCSV type=file><br><br>
        <table cellspacing=0 cellpadding=0>
            <tr>
                <td>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_HEADER . ":<br>", 'SortType', array(CLASSIFICATORS_SORT_TYPE_ID, CLASSIFICATORS_SORT_TYPE_NAME, CLASSIFICATORS_SORT_TYPE_PRIORITY), $SortType, "style='width: 100%;'")
                    ?>
                </td>
                <td width=4>&nbsp;</td>
                <td>
                    <?=
                    nc_admin_select_simple(CLASSIFICATORS_SORT_DIRECTION . ":<br>", 'SortDirection', array(CLASSIFICATORS_SORT_ASCENDING, CLASSIFICATORS_SORT_DESCENDING), $SortDirection, "style='width: 100%;'")
                    ?>
                </td>
            </tr>
        </table>
        <?php echo $nc_core->token->get_input(); ?>
    </form>
    <?
    echo nc_get_simple_modal_footer();
}

function UpdateCheckedForOneClassificator($ClassificatorID) {
    $nc_core = nc_Core::get_object();
    $db = & $nc_core->db;

    $TableName = GetTableNameByID($ClassificatorID);

    $id = array();
    foreach ($_POST as $k => $v) {
        if (substr($k, 0, 5) == 'check') {
            $id[] = intval(substr($k, 6));
        }
    }
    $db->query("UPDATE `Classificator_" . $TableName . "` SET `Checked` = '0' ");
    if (!empty($id)) {
        $db->query("UPDATE `Classificator_" . $TableName . "`
                SET `Checked` = '1'
                WHERE `" . $TableName . "_ID` IN (" . join(', ', $id) . ") ");
    }
}

###############################################################################

function ImportClassificatorCompleted($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection, $FileCSV) {
    global $db;

    $System += 0;
    $SortType += 0;
    $SortDirection += 0;
    $ReturnValue = 0;

    if ($ClassificatorName == "") {
        nc_print_status(CONTENT_CLASSIFICATORS_ERROR_NAME, 'error');
        ImportClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection);
    } else if ($FileCSV['name'] == "") {
        nc_print_status(CONTENT_CLASSIFICATORS_ERROR_FILE_NAME, 'error');
        ImportClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection);
    } else if ($ClassificatorTable == "") {
        nc_print_status(CONTENT_CLASSIFICATORS_ERROR_KEYWORD, 'error');
        ImportClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection);
    } else if (strspn(strtolower($ClassificatorTable), "abcdefghijklmnopqrstuvwxyz0123456789_") != strlen($ClassificatorTable)) {
        nc_print_status(CONTENT_CLASSIFICATORS_ERROR_KEYWORDINV, 'error');
        ImportClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection);
    } else if (strspn(strtolower(substr($ClassificatorTable, 0, 1)), "abcdefghijklmnopqrstuvwxyz") != 1) {
        nc_print_status(CONTENT_CLASSIFICATORS_ERROR_KEYWORDFL, 'error');
        ImportClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection);
    } else if (IsClassificatorExist($ClassificatorTable)) {
        nc_print_status(CONTENT_CLASSIFICATORS_ERROR_KEYWORDAE, 'error');
        ImportClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection);
    } else if (IsTableExist($ClassificatorTable)) {
        nc_print_status(CONTENT_CLASSIFICATORS_ERROR_KEYWORDREZ, 'error');
        ImportClassificator($ClassificatorName, $ClassificatorTable, $System, $SortType, $SortDirection);
    } else {

        print "<br>\n";

        $Insert = "INSERT INTO `Classificator` (`Classificator_Name`, `Table_Name`, `System`, `Sort_Type`, `Sort_Direction`)";
        $Insert .= " VALUES ('$ClassificatorName', '$ClassificatorTable', '$System', '$SortType', '$SortDirection')";
        $db->query($Insert);

        $Creat = " CREATE TABLE `Classificator_$ClassificatorTable` (";
        $Creat .= "`${ClassificatorTable}_ID` int(11) NOT NULL auto_increment,";
        $Creat .= "`${ClassificatorTable}_Name` char(255) DEFAULT '' NOT NULL,";
        $Creat .= "`${ClassificatorTable}_Priority` int(11) DEFAULT NULL,";
        $Creat .= "`Value` text DEFAULT NULL,";
        $Creat .= "`Checked` int(1) default 1,";
        $Creat .= "`CustomSettings` text NULL DEFAULT NULL,";
        $Creat .= "PRIMARY KEY (`${ClassificatorTable}_ID`)";
        $Creat .= ")";
        $db->query($Creat);

        $FileTmp = $FileCSV['tmp_name'];

        $fp = @fopen($FileTmp, "r");
        if ($fp != FALSE) {
            while ($csv_data = fgetcsv($fp, 2048, ";")) {
                $cnt = count($csv_data);

                $Insert = "INSERT INTO Classificator_$ClassificatorTable (";
                $Insert .= "${ClassificatorTable}_Name";
                if ($cnt == 2)
                    $Insert .= ", ${ClassificatorTable}_Priority";
                $Insert .= ") VALUES (";

                if ($cnt == 1)
                    $Insert .= "'" . $csv_data[0] . "'";
                else
                    $Insert .= "'" . $csv_data[0] . "', '" . $csv_data[1] . "'";

                $Insert .= ")";

                $db->query($Insert);
            }

            fclose($fp);
            unlink($FileTmp);
        }


        $ReturnValue = 1;
    }

    return ($ReturnValue);
}

###############################################################################

class ui_config_classificators extends ui_config {

    public function __construct($active_tab = 'classificator.list') {

        $this->headerText = SECTION_INDEX_USER_STRUCT_CLASSIFICATOR;
        $this->tabs = array(
            array(
                'id' => 'classificator.list',
                'caption' => SECTION_INDEX_USER_STRUCT_CLASSIFICATOR,
                'location' => "classificator.list"));

        $this->activeTab = $active_tab;
        $this->treeMode = 'classificator';
//        $this->treeSelectedNode = "classificator.list";
        $this->locationHash = "classificator.list";
    }

}

class ui_config_classificator extends ui_config {

    public function __construct($active_tab = 'edit', $classificator_id) {

        global $db;
        $classificator_id = intval($classificator_id);
        if ($active_tab == 'add') {
            $this->headerText = CONTENT_CLASSIFICATORS_LIST_ADD;
            $this->tabs = array(
                array('id' => 'add',
                    'caption' => CONTENT_CLASSIFICATORS_LIST_ADD,
                    'location' => "classificator.add()")
            );
//            $this->treeSelectedNode = "classificator.list";
        }
        if ($active_tab == 'edit' || $active_tab == 'custom' ) {
            $classificator = $db->get_col("SELECT Classificator_Name FROM Classificator WHERE Classificator_ID = '" . $classificator_id . "'");
            $this->headerText = $classificator;
            $this->tabs = array(
                array('id' => 'edit',
                    'caption' => CONTENT_CLASSIFICATORS_LIST_EDIT,
                    'location' => "classificator.edit($classificator_id)"),
                array('id' => 'custom',
                    'caption' => CONTENT_CLASSIFICATORS_LIST_CUSTOM,
                    'location' => 'classificator.custom(' . $classificator_id. ')'),
            );
            $this->treeSelectedNode = "classificator-$classificator_id";
        }
        if ($active_tab == 'delete') {
            $classificator = $db->get_col("SELECT Classificator_Name FROM Classificator WHERE Classificator_ID = '" . $classificator_id . "'");
            $this->headerText = $classificator;
            $this->tabs = array(
                array('id' => 'edit',
                    'caption' => CONTENT_CLASSIFICATORS_LIST_DELETE,
                    'location' => "classificator.delete($classificator_id)")
            );
            $this->treeSelectedNode = "classificator-$classificator_id";
        }
        if ($active_tab == 'import') {
            $this->headerText = CLASSIFICATORS_IMPORT_HEADER;
            $this->tabs = array(
                array('id' => 'import',
                    'caption' => CLASSIFICATORS_IMPORT_HEADER,
                    'location' => "classificator.import()")
            );
//            $this->treeSelectedNode = "classificator.list";
        }
        $this->activeTab = $active_tab;

        $this->treeMode = 'classificator';
        $this->locationHash = "#classificator.$active_tab($classificator_id)";
    }

}

class ui_config_classificator_item extends ui_config {

    public function __construct($active_tab = 'item.edit', $classificator_id, $id_in_classificator) {

        global $db;
        $classificator_id = intval($classificator_id);
        $classificator = $db->get_col("SELECT Classificator_Name FROM Classificator WHERE Classificator_ID = $classificator_id");

        $this->headerText = $classificator;
        if ($active_tab == 'item.add') {
            $this->tabs = array(
                array('id' => 'item.add',
                    'caption' => CONTENT_CLASSIFICATORS_ELEMENTS_ADD,
                    'location' => "classificator.item.add($classificator_id)")
            );
            $this->locationHash = "#classificator.$active_tab($classificator_id)";
        }
        if ($active_tab == 'item.edit') {
            $this->tabs = array(
                array('id' => 'item.edit',
                    'caption' => CONTENT_CLASSIFICATORS_ELEMENTS_EDIT,
                    'location' => "classificator.item.edit($classificator_id,$id_in_classificator)")
            );
            $this->locationHash = "#classificator.$active_tab($classificator_id,$id_in_classificator)";
        }
        $this->activeTab = $active_tab;

        $this->treeMode = 'classificator';
        $this->treeSelectedNode = "classificator-$classificator_id";
    }

}
