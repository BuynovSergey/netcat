<?php

/*
 * Скопируем поля из основного шаблона во вновь созданный
 */

function InsertFieldsFromBaseClass($BaseClassID, $NewClassID) {
    global $db;

    $BaseClassID += 0;
    $NewClassID += 0;

    $db->query("INSERT INTO `Field` (`Class_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Priority`, `DoSearch`, `DefaultState`, `Inheritance`, `System_Table_ID`, `TypeOfEdit_ID`)
    SELECT " . $NewClassID . ", `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Priority`, `DoSearch`, `DefaultState`, `Inheritance`, `System_Table_ID`, `TypeOfEdit_ID`
      FROM `Field`
      WHERE `Class_ID` = '" . $BaseClassID . "'");

    $Result = $db->get_results("SELECT `Field_ID` FROM `Field` WHERE `Class_ID` = '" . $NewClassID . "'");
    if (!empty($Result)) {
        foreach ($Result as $Array) {
            ColumnInMessage($Array->Field_ID, 1, $db);
        }
    }
}

/*
 * Скопируем поля действий из основного шаблона во вновь созданный
 */

function InsertActionsFromBaseClass($BaseClassID, $NewClassID) {
    global $nc_core, $db;

    $BaseClassID += 0;
    $NewClassID += 0;

    $ret = false;

    $Result = $db->get_results("SELECT `AddTemplate`, `EditTemplate`, `AddActionTemplate`, `EditActionTemplate`, `SearchTemplate`, `FullSearchTemplate`, `SubscribeTemplate`, `AddCond`, `EditCond`, `SubscribeCond`, `CheckActionTemplate`, `DeleteActionTemplate`
    FROM `Class`
    WHERE `Class_ID` = '" . $BaseClassID . "'", ARRAY_A);

    $q = array();

    if (!empty($Result)) {
        foreach ($Result as $Array) {
            foreach ($Array as $key => $val) {
                if ($val != "") {
                    $q[] = "`" . $key . "` = '" . addslashes($val) . "'";
                    $ret = true;
                }
            }
        }
    }


    if ($ret) {
        $ClassTemplate = $db->get_var("SELECT `ClassTemplate` FROM `Class` WHERE `Class_ID` = '" . $NewClassID . "'");

        // execute core action
        if (!$ClassTemplate) {
            $nc_core->event->execute(nc_Event::BEFORE_COMPONENT_UPDATED, $NewClassID);
        } else {
            // main class, template class
            $nc_core->event->execute(nc_Event::BEFORE_COMPONENT_TEMPLATE_UPDATED, $ClassTemplate, $NewClassID);
        }

        $db->query("UPDATE `Class` SET " . join(',', $q) . " WHERE `Class_ID` = '" . $NewClassID . "';");

        // execute core action
        if (!$ClassTemplate) {
            $nc_core->event->execute(nc_Event::AFTER_COMPONENT_UPDATED, $NewClassID);
        } else {
            // main class, template class
            $nc_core->event->execute(nc_Event::AFTER_COMPONENT_TEMPLATE_UPDATED, $ClassTemplate, $NewClassID);
        }
    }
}

##############################################
# Вывод списка групп шаблонов
##############################################

function ClassGroupList() {
    global $db;

    if (($Result = $db->get_results("SELECT DISTINCT `Class_Group` FROM `Class` WHERE `System_Table_ID` = 0 AND `ClassTemplate` = 0 ORDER BY `Class_Group` ASC"))) {
        ?>
<form method='post' action='index.php' xmlns="http://www.w3.org/1999/html">
            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td>

                        <table class='admin_table'  width='100%'>
                            <tr>
                                <th><?= CONTROL_CLASS_CLASS_GROUPS ?></th>
                            </tr>
                            <?
                            foreach ($Result as $Array) {
                                print "<tr>";
                                print "<td><a href=\"index.php?phase=1&ClassGroup=" . urlencode($Array->Class_Group) . "\">" . $Array->Class_Group . "</a></td>";
                                print "</tr>";
                            }
                            ?>
                        </table>
                    </td></tr></table><br>
            <?
        } else {
            nc_print_status(CONTROL_CLASS_NONE, 'info');
        }
?>
        <a href='index.php?phase=10'><b><?= CONTROL_CLASS_ADD ?></b></a>
<?
    }

/**
 * Вывод списка шаблонов
 *
 * @param string group name
 */
function ClassList ($Class_Group = false) {
	global $db, $ADMIN_PATH, $ADMIN_TEMPLATE, $UI_CONFIG;

	// get nc_core
	$nc_core = nc_Core::get_object();

	$file_mode = intval( $nc_core->input->fetch_get_post('fs') );

	if ($Class_Group) {
		$select = "SELECT c.`Class_ID`,
		c.`Class_Name`,
		COUNT(f.`Field_ID`) AS `Fields`,
		IF(c.`AddTemplate` <> ''
			OR c.`AddCond` <> ''
			OR c.`AddActionTemplate` <> '', 1, 0) AS IsAdd,
		IF(c.`EditTemplate` <> ''
			OR c.`EditCond` <> ''
			OR c.`EditActionTemplate` <> ''
			OR c.`CheckActionTemplate` <> '', 1, 0) AS IsEdit,
		IF(c.`SearchTemplate` <> ''
			OR c.`FullSearchTemplate` <> '', 1, 0) AS IsSearch,
		IF(c.`SubscribeTemplate` <> ''
			OR c.`SubscribeCond` <> '', 1, 0) AS IsSubscribe,
		IF(c.`DeleteActionTemplate` <> ''
			OR c.`DeleteTemplate` <> ''
			OR c.`DeleteCond` <> '', 1, 0) as IsDelete
		FROM `Class` AS c
		LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
		WHERE md5(c.`Class_Group`) = '" . $db->escape($Class_Group) . "'
			AND c.`System_Table_ID` = 0
			AND c.`ClassTemplate` = 0
			AND c.`File_Mode` = '" . $file_mode . "'
		GROUP BY c.`Class_ID`
		ORDER BY c.`Priority`, c.`Class_ID`";
	} else {
		$select = "SELECT c.`Class_ID`,
		c.`Class_Name`,
		COUNT(f.`Field_ID`) AS `Fields`,
		IF(c.`AddTemplate` <> ''
			OR c.`AddCond` <> ''
			OR c.`AddActionTemplate` <> '', 1, 0) AS IsAdd,
		IF(c.`EditTemplate` <> ''
			OR c.`EditCond` <> ''
			OR c.`EditActionTemplate` <> ''
			OR c.`CheckActionTemplate` <> ''
			OR c.`DeleteActionTemplate` <> '', 1, 0) AS IsEdit,
		IF(c.`SearchTemplate` <> ''
			OR c.`FullSearchTemplate` <> '', 1, 0) AS IsSearch,
		IF(c.`SubscribeTemplate` <> ''
			OR c.`SubscribeCond` <> '', 1, 0) AS IsSubscribe,
			c.`Class_Group`
		FROM `Class` AS c
		LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
		WHERE c.`System_Table_ID` = 0
			AND c.`ClassTemplate` = 0
			AND c.`File_Mode` = '" . $file_mode . "'
		GROUP BY c.`Class_ID`
		ORDER BY c.`Priority`, c.`Class_ID`";
	}

	if ( $Result = $db->get_results($select) ):
?>
		<form method='post' action='index.php'>
			<table border='0' cellpadding='0' cellspacing='0' width='100%'>
				<tr>
					<td>
<?php
	$action_map = array(
		// array(myaction, title, icon, check_prop)
		array(1, CONTROL_CLASS_ACTIONS_ADD, 'file-add', 'IsAdd'),
		array(2, CONTROL_CLASS_ACTIONS_EDIT, 'edit', 'IsEdit'),
		array(5, CONTROL_CLASS_ACTIONS_DELETE, 'remove', 'IsDelete'),
		array(3, CONTROL_CLASS_ACTIONS_SEARCH, 'mod-search', 'IsSearch'),
		array(4, CONTROL_CLASS_ACTIONS_MAIL, 'mod-comments', 'IsSubscribe')
	);
?>
						<table class='nc-table nc--striped nc--hovered nc--wide'>
							<tr>
								<th class='nc-text-center nc--compact'>ID</th>
								<th><?= CONTROL_CLASS_CLASS ?></th>
                            <?php if (!$Class_Group): ?>
								<th width='20%'><?=CONTROL_USER_GROUP?></th>
                            <?php endif; ?>
								<th class='nc-text-center nc--compact' colspan='<?=count($action_map)?>' style='padding: 0;'><?= CONTROL_CLASS_ACTIONS ?></th>
								<th class='nc-text-center nc--compact' width='10%'><?= CONTROL_CLASS_FIELDS ?></th>
								<th class='nc-text-center nc--compact'><i class='nc-icon nc--remove nc--hovered' title='<?= CONTROL_CLASS_DELETE ?>'></i></th>
							</tr>
<?php foreach ($Result as $Array): ?>
							<tr>
							<td><?=$Array->Class_ID?></td>
							<td><a href="index.php?fs=<?=$file_mode?>&phase=4&ClassID=<?=$Array->Class_ID . ($Class_Group ? '&ClassGroup=' . md5($Class_Group) : '')?>"><?=$Array->Class_Name?></a></td>
<?php if (!$Class_Group): ?>
							<td><a href="index.php?fs=<?=$file_mode?>&phase=1&ClassGroup=<?=md5($Array->Class_Group)?>"><?=$Array->Class_Group?></a></td>
<?php endif;

	foreach ($action_map as $action_props):
		$action_href = 'index.php?fs='.$file_mode.'&phase=8&ClassID='.$Array->Class_ID.'&myaction='.$action_props[0];
		if ($Class_Group) {
			$action_href .= '&ClassGroup=' . urlencode($Class_Group);
		}
		$check_prop = $action_props[3];
        if (!isset($Array->$check_prop)) {
            $Array->$check_prop = null;
        }
		$is_inactive = !$Array->$check_prop;
?>
							<td width="1%" class="button nc-padding-5">
								<a href="<?=$action_href?>"<?=$is_inactive ? ' style="color:#888;"' : ''?> title="<?=$action_props[1]?>">
									<i class="nc-icon nc--<?=$action_props[2] . ($is_inactive ? ' nc--hovered' : '')?>"></i>
								</a>
							</td>
	<?php endforeach; ?>

							<td class='nc-text-center'><a class="nc-label nc--blue" href="<?=$ADMIN_PATH?>field/?ClassID=<?=$Array->Class_ID?>&fs=<?=$file_mode?>" title="<?=$Array->Fields?> <?=$nc_core->lang->get_numerical_inclination($Array->Fields, array(CONTROL_CLASS_FIELD, CONTROL_CLASS_FIELDS, CONTROL_CLASS_FIELDS_COUNT)); ?>"><?=$Array->Fields?></a></td>
							<td class='nc-text-center'><input type='checkbox' name='Delete<?=$Array->Class_ID?>' value='<?=$Array->Class_ID?>'></td>
							</tr>
<?php endforeach;	?>
						</table>
					</td>
				</tr>
			</table>
			<br />
<?php
	else:
		nc_print_status(CONTROL_CLASS_NONE, 'error');
	endif;

	$UI_CONFIG->actionButtons[] = array(
		"id" => "addClass",
		"caption" => CONTROL_CLASS_FUNCS_SHOWCLASSLIST_ADDCLASS,
		"action" => "urlDispatcher.load('dataclass" . ($file_mode ? '_fs' : '') . ".add($Class_Group)')",
		"align" => "left"
	);

	$UI_CONFIG->actionButtons[] = array(
		"id" => "importClass",
		"caption" => CONTROL_CLASS_FUNCS_SHOWCLASSLIST_IMPORTCLASS,
		"action" => "urlDispatcher.load('#tools.databackup.import')",
		"align" => "left"
	);

	if (!empty($Array)):
		$UI_CONFIG->actionButtons[] = array(
			"id" => "submit",
			"caption" => NETCAT_ADMIN_DELETE_SELECTED,
			"action" => "mainView.submitIframeForm()",
            "red_border" => true,
		);

	if ($Class_Group): ?>
		<input type='hidden' name='ClassGroup' value='<?=$Class_Group?>'>
	<?php endif; ?>
		<input type='hidden' name='fs' value="<?=$file_mode?>">
		<input type='hidden' name='phase' value='6'>
		<input type='submit' class='hidden'>
	</form>
<?php
	endif;
}

    function ClassTemplatesList($Class_ID = 0) {
        global $UI_CONFIG;

        // system superior object
        $nc_core = nc_Core::get_object();
        $Class_ID = intval($Class_ID);
        // system db object
        if (is_object($nc_core->db))
            $db = &$nc_core->db;


        $result = $nc_core->db->get_results("SELECT `Class_ID`, `Class_Name`,
    IF(`AddTemplate` <> '' OR `AddCond` <> '' OR `AddActionTemplate` <> '', 1, 0) AS IsAdd,
    IF(`EditTemplate` <> '' OR `EditCond` <> '' OR `EditActionTemplate` <> '' OR `CheckActionTemplate` <> '', 1, 0) AS IsEdit,
    IF(`SearchTemplate` <> '' OR `FullSearchTemplate` <> '', 1, 0) AS IsSearch,
    IF(`SubscribeTemplate` <> '' OR `SubscribeCond` <> '', 1, 0) AS IsSubscribe,
    IF(`DeleteActionTemplate` <> '' OR `DeleteTemplate` <> '' OR `DeleteCond` <> '', 1, 0) as IsDelete,
    IF( `Type` <> '', `Type`, 'useful') AS `Type`
    FROM `Class`
    WHERE " . ($Class_ID ? "`ClassTemplate` = '" . $Class_ID . "'" : "`ClassTemplate` != 0") .
                "ORDER BY `Priority`, `Class_ID`");

        if (!empty($result)) {
            	$action_map = array(
		// array(myaction, title, icon, check_prop)
		array(1, CONTROL_CLASS_ACTIONS_ADD, 'file-add', 'IsAdd'),
		array(2, CONTROL_CLASS_ACTIONS_EDIT, 'edit', 'IsEdit'),
		array(5, CONTROL_CLASS_ACTIONS_DELETE, 'remove', 'IsDelete'),
		array(3, CONTROL_CLASS_ACTIONS_SEARCH, 'mod-search', 'IsSearch'),
		array(4, CONTROL_CLASS_ACTIONS_MAIL, 'mod-comments', 'IsSubscribe')
                );
            ?>
            <form method='post' action='index.php'>
                <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                    <tr>
                        <td>
                            <table class='nc-table nc--striped nc--hovered nc--wide'>
                                <tr>
                                    <th class='nc-text-center nc--compact'>ID</th>
                                    <th><?= CONTROL_CLASS_CLASS_TEMPLATE ?></th>
                                    <th><?= CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE ?></th>
                                    <th class='nc-text-center nc--compact' colspan='<?=count($action_map)?>' style='padding: 0;'><?= CONTROL_CLASS_ACTIONS ?></th>
                                    <th class='nc-text-center nc--compact'><i class='nc-icon nc--remove nc--hovered' title='<?= CONTROL_CLASS_DELETE ?>'></i></th>
                                </tr>
                                <?php
                                foreach ($result as $Array) {
                                    print "<tr>";
                                    print "<td>" . $Array->Class_ID . "</td>";
                                    print "<td><a href='index.php?fs=" . +$_REQUEST['fs'] . "&phase=16&amp;ClassID=" . $Array->Class_ID . "'>" . $Array->Class_Name . "</a></td>";
                                    print "<td>" . constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_" . strtoupper($Array->Type)) . "</td>";
                                    foreach ($action_map as $action_props):
                                        $action_href = 'index.php?fs='.+$_REQUEST['fs'].'&phase=22&amp;ClassID='.$Array->Class_ID.'&amp;myaction='.$action_props[0];
                                        $check_prop = $action_props[3];
                                        $is_inactive = !$Array->$check_prop;
                                        ?>
                                        <td width="1%" class="button nc-padding-5">
                                            <a href="<?=$action_href?>"<?=$is_inactive ? ' style="color:#888;"' : ''?> title="<?=$action_props[1]?>">
                                                <i class="nc-icon nc--<?=$action_props[2] . ($is_inactive ? ' nc--hovered' : '')?>"></i>
                                            </a>
                                        </td><?
                                    endforeach;
                                    print "<td align='center'><input type='checkbox' name=\"Delete" . $Array->Class_ID . "\" value='" . $Array->Class_ID . "'></td>";
                                    print "</tr>";
                                }
                                ?>
                            </table>
                        </td>
                    </tr>
                </table>
                <br/>
                <?php
            } else {
                nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NOT_FOUND, 'info');
            }

            if (!empty($Array)) {
                $UI_CONFIG->actionButtons[] = array(
                        "id" => "submit",
                        "align" => "left",
                        "caption" => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                        "location" => "classtemplate".(+$_REQUEST['fs'] ? '_fs' : '').".add(" . $Class_ID . ")"
                );
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => NETCAT_ADMIN_DELETE_SELECTED,
                    "action" => "mainView.submitIframeForm()",
                    "red_border" => true,
                );
            }

            if (!empty($Array)) {
                ?>
                <input type='hidden' name='phase' value='18' />
                <input type='hidden' name='ClassTemplate' value='<?= $Class_ID ?>' />
                <input type='hidden' name='fs' value='<?= +$_REQUEST['fs']; ?>' />
                <input type='submit' class='hidden' />
            </form>
            <?php
        }
    }

    /*
     * Component add/edit form
     *
     * @param int component ID (0 if action "add")
     * @param string action, for example "index.php"
     * @param int phase from index.php
     * @param int operation type, 1 - insert, 2 - update, 3 - update for System_Table (ClassID==SystemTableID)
     *
     * @return HTML code into the output buffer
     */
    function ClassForm($ClassID, $action, $phase, $type, $BaseClassID) {
        global $ClassGroup, $UI_CONFIG;

        // system superior object
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        // compile main MySQL query
        $select = "SELECT * FROM `Class` WHERE ";

        if ($BaseClassID) {
            $type = 2;
            $ClassID = $BaseClassID;
        }

        $File_Mode = nc_get_file_mode('Class', $ClassID);

        if ($File_Mode) {
            $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        }
        else {
            $class_editor = null;
        }

        if (isset($_POST['Class_Group_New']) && ($_POST['Class_Group_New'] || !$ClassGroup) && $ClassID) {
            ?>
            <script>
                parent.window.frames[0].window.location.href += '&selected_node=dataclass-<?= $ClassID; ?>';
            </script>
            <?
        }

        ?>
        <form method='post' id='ClassForm' action='<?= $action ?>' enctype='multipart/form-data'>
        <?
        if ($File_Mode) {
            echo '<input type="hidden" value="1" name="fs"/>';
        }
        else {
            echo "<br /><div>" . CONTROL_CLASS_INFO_ADDSLASHES . "</div>";
        }

        if ($type == 1) { // 'insert'
            $data = new stdClass();
            if (!$nc_core->input->fetch_post()) {
                $data->Class_Name = CONTROL_CLASS_NEWCLASS;
                $data->FormPrefix = "\$f_AdminCommon";
                if ($File_Mode) {
                    $data->FormPrefix = '<?= ' . $data->FormPrefix . '; ?>';
                }
                $data->RecordTemplate = "\$f_AdminButtons";
                if ($File_Mode) {
                    $data->RecordTemplate = '<?= ' . $data->RecordTemplate . '; ?>';
                }
                $data->RecordTemplateFull = "\$f_AdminButtons";
                if ($File_Mode) {
                    $data->RecordTemplateFull = '<?= ' . $data->RecordTemplateFull . '; ?>';
                }
                $data->RecordsPerPage = "20";
                $data->MinRecordsInInfoblock = null;
                $data->MaxRecordsInInfoblock = null;
                $data->Class_Group = $db->get_var("SELECT `Class_Group` FROM `Class` WHERE md5(`Class_Group`) = '" . $ClassGroup . "'");
                $data->Keyword = null;
                $data->DisableBlockMarkup = $nc_core->get_settings('DisableBlockMarkup');
                $data->DisableBlockListMarkup = $data->DisableBlockMarkup;
                $data->IsMultipurpose = 0;
                $data->Main_ClassTemplate_ID = 0;
                $data->Class_ID = null;
                $data->System_Table_ID = 0;
                $data->ClassTemplate = 0;
                $data->Settings = '';
                $data->SortBy = '';
                $data->ClassTemplage = null;
                $data->Class_Group_New = '';
                $data->TitleList = '';
                $data->FormSuffix = '';
                $data->TitleTemplate = '';
                $data->UseAltTitle = 0;
                $data->AllowTags = 0;
                $data->NL2BR = 0;
                $data->UseCaptcha = 0;
                $data->CompatibleFields = '';
                $data->SiteStyles = '';

                if ($data->Type === 'object_partial') {
                    $data->IsOptimizedForMultipleMode = 1;
                    $data->DisableBlockMarkup = 0;
                    $data->DisableBlockListMarkup = 0;
                }
            }
            else {
                $data->Keyword = $nc_core->input->fetch_post('Keyword');
                $data->FormPrefix = $nc_core->input->fetch_post('FormPrefix');
                $data->FormSuffix = $nc_core->input->fetch_post('FormSuffix');
                $data->RecordTemplate = $nc_core->input->fetch_post('RecordTemplate');
                $data->RecordTemplateFull = $nc_core->input->fetch_post('RecordTemplateFull');
                $data->Settings = $nc_core->input->fetch_post('Settings');
                $data->Class_Name = $nc_core->input->fetch_post('Class_Name');
                $data->Class_Group = $nc_core->input->fetch_post('Class_Group');
                $data->Class_Group_New = $nc_core->input->fetch_post('Class_Group_New');
                $data->RecordsPerPage = $nc_core->input->fetch_post('RecordsPerPage');
                $data->MinRecordsInInfoblock = $nc_core->input->fetch_post('MinRecordsInInfoblock');
                $data->MaxRecordsInInfoblock = $nc_core->input->fetch_post('MaxRecordsInInfoblock');
                $data->SortBy = $nc_core->input->fetch_post('SortBy');
                $data->AllowTags = $nc_core->input->fetch_post('AllowTags');
                $data->NL2BR = $nc_core->input->fetch_post('NL2BR');
                $data->TitleTemplate = $nc_core->input->fetch_post('TitleTemplate');
                $data->TitleList = $nc_core->input->fetch_post('TitleList');
                $data->UseAltTitle = $nc_core->input->fetch_post('UseAltTitle');
                $data->UseCaptcha = $nc_core->input->fetch_post('UseCaptcha');
                $data->CustomSettingsTemplate = $nc_core->input->fetch_post('CustomSettingsTemplate');
                $data->ClassDescription = $nc_core->input->fetch_post('ClassDescription');
                $data->SiteStyles = $nc_core->input->fetch_post('SiteStyles');
                $data->DisableBlockMarkup = $nc_core->input->fetch_post('DisableBlockMarkup');
                $data->DisableBlockListMarkup = $nc_core->input->fetch_post('DisableBlockListMarkup');
                $data->ObjectName = $nc_core->input->fetch_post('ObjectName');
                $data->IsMultipurpose = $nc_core->input->fetch_post('IsMultipurpose');
                $data->CompatibleFields = $nc_core->input->fetch_post('CompatibleFields');
                $data->Main_ClassTemplate_ID = $nc_core->input->fetch_post('Main_ClassTemplate_ID');

                if ($nc_core->modules->get_by_keyword("cache")) {
                    $data->CacheForUser = $nc_core->input->fetch_post('CacheForUser');
                }
            }
        }
        elseif ($type == 2) { // 'update'
            $select .= " `Class_ID` = '" . $ClassID . "'";
            /** @var stdClass $data */
            $data = $db->get_row($select);
            if ($ClassGroup) {
                $data->Class_Group = $db->get_var("SELECT `Class_Group` FROM `Class` WHERE md5(`Class_Group`) = '" . $ClassGroup . "'");
            }
            if ($phase == 5) {
                if ($ClassGroup) {
                    $data->Class_Group = $ClassGroup;
                }
            }
            if (!$data) {
                nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
            }

            if ($BaseClassID) {
                $data->Keyword = null;
            }
        }
        elseif ($type == 3) { // 'update system table'
            
            $select .= " `System_Table_ID` = '" . $ClassID . "' AND `ClassTemplate` = 0 AND File_Mode = " . +$_REQUEST['fs'];
            /** @var stdClass $data */
            $data = $db->get_row($select);
            if (!$data) {
                nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
                return;
            }
        }
        else {
            return;
        }
        
        if ($File_Mode && ($type == 2 || $type == 3) && !empty($data)) {
            $class_editor->load($data->Class_ID, null, $data->File_Hash);
            $class_editor->fill_fields();
            $class_fields = $class_editor->get_fields();
            $data->FormPrefix = $class_fields['FormPrefix'];
            $data->FormSuffix = $class_fields['FormSuffix'];
            $data->RecordTemplate = $class_fields['RecordTemplate'];
            $data->RecordTemplateFull = $class_fields['RecordTemplateFull'];
            $data->Settings = $class_fields['Settings'];
            $data->SiteStyles = $class_fields['SiteStyles'];

            $class_absolute_path = $class_editor->get_absolute_path();
            $class_filemanager_link = nc_module_path('filemanager') . 'admin.php?page=manager&phase=1&dir=' . $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'class' . $class_editor->get_relative_path();

            echo "<br />" . PHP_EOL . "<div>" . sprintf(CONTROL_CLASS_CLASSFORM_TEMPLATE_PATH, $class_filemanager_link, $class_absolute_path) . "</div>";
        }

        if ($type == 1 && !$data->Settings && $File_Mode) {
            $data->Settings = "<?php\n\n\n?>";
        }

        if ($type == 1 && !$data->SortBy && $File_Mode) {
            $data->SortBy = 'a.`Priority` ASC, a.`Message_ID` ASC';
        }

        $data->RecordTemplate = nc_cleaned_RecordTemplate_of_string_service($data->RecordTemplate);
        if ($type == 1 || $BaseClassID) {
            echo "<h2>" . CONTROL_CLASS_CLASSFORM_INFO_FOR_NEWCLASS . "</h2>";

            echo CONTROL_CLASS_CLASS_NAME . ":<br/>";
            echo "<input type='text' name='Class_Name' size='50' value=\"" . htmlspecialchars($data->Class_Name) . "\"><br/><br/>";

            if ($File_Mode) {
                echo CONTROL_CLASS_CLASS_KEYWORD . ":<br/>";
                echo "<input type='text' name='Keyword' size='50' maxlength='" . nc_component::MAX_KEYWORD_LENGTH . "' value=\"" . htmlspecialchars($data->Keyword) . "\"><br/><br/>";
            }

            // if not component template - show groups
            if (!($data->ClassTemplate || $phase == 15)) {
                $class_groups = $db->get_col("SELECT DISTINCT `Class_Group` FROM `Class` WHERE `Class_Group` <> ''");
                if (!empty($class_groups)) {
                    echo CONTROL_USER_GROUP . ":<br/><select name='Class_Group' style='width:auto;'>\n";
                    foreach ($class_groups as $class_group) {
                        $class_group_value = htmlspecialchars($class_group, ENT_QUOTES);
                        if ($data->Class_Group == $class_group) {
                            echo("\t<option value='" . $class_group_value . "' selected='selected'>" . $class_group_value . "</option>\n");
                        }
                        else {
                            echo("\t<option value='" . $class_group_value . "'>" . $class_group_value . "</option>\n");
                        }
                    }
                    echo "</select>&nbsp;&nbsp;&nbsp;";
                }
                unset($class_groups);

                echo CONTROL_CLASS_NEWGROUP .
                    "&nbsp;&nbsp;&nbsp;<input type='text' name='Class_Group_New' size='25' maxlength='64' value='" .
                    htmlspecialchars(isset($data->Class_Group_New) ? $data->Class_Group_New : '', ENT_QUOTES) .
                    "'><br/><br/>";
            }
            else {
                echo CONTROL_USER_GROUP . ": " . CONTROL_CLASS_CLASS_TEMPLATE_GROUP . "";
                echo "<input type='hidden' name='Class_Group' value='" . CONTROL_CLASS_CLASS_TEMPLATE_GROUP . "'>";
            }

            if ($data->ClassTemplate) {
                if (!$data->Type) {
                    $data->Type = 'useful';
                }
                echo "<br/> " . CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE . ":  ";
                echo constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_" . strtoupper($data->Type));
            }
            if ($nc_core->modules->get_by_keyword("cache")) {
                ?>
                <table border='0' cellpadding='0' cellspacing='0' width='98%'>
                    <tr>
                        <td style='border: none;'>
                            <?= CONTROL_CLASS_CACHE_FOR_AUTH ?>*:<br/>
                            <select name='CacheForUser'
                                style='width:320px; margin-right: 5px;'>
                                <option
                                    value='0'<?= (!$data->CacheForUser ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_NONE ?></option>
                                <option
                                    value='1'<?= ($data->CacheForUser == 1 ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_USER ?></option>
                                <option
                                    value='2'<?= ($data->CacheForUser == 2 ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_GROUP ?></option>
                            </select><br/>
                            * <?= CONTROL_CLASS_CACHE_FOR_AUTH_DESCRIPTION ?>
                        </td>
                    </tr>
                </table>
                <br/>
            <? } ?>
            <br/>
            <?php
        }
        else {
            ?> <input type="hidden"
                value="<?= htmlspecialchars(($data->Class_Name ?: $_GET['Class_Name']), ENT_QUOTES); ?>"
                name="Class_Name"/>
               <input type='hidden' name='DisableBlockMarkup' value='<?= (int)$data->DisableBlockMarkup; ?>'>
               <input type='hidden' name='DisableBlockListMarkup' value='<?= (int)$data->DisableBlockListMarkup; ?>'><?
        }
        ?><input type='hidden' name='IsMultipurpose' value='<?= (int)$data->IsMultipurpose; ?>'><?

        $set = $nc_core->get_settings();
        if ($set['CMEmbeded']) {
            ?>
            <div id="classFields" class="completionData" style="display:none"></div>
            <div id="classCustomSettings" class="completionData"
                style="display:none"></div>
            <script>
                $nc('#classFields').data('completionData', $nc.parseJSON("<?=addslashes(json_safe_encode(getCompletionDataForClassFields($ClassID)))?>"));
                $nc('#classCustomSettings').data('completionData', $nc.parseJSON("<?=addslashes(json_safe_encode(getCompletionDataForClassCustomSettings($ClassID)))?>"));
            </script>
        <?php
        }

        if ($data->Type === 'object_partial') {
            // Для шаблона для полного вывода пропущен ряд полей
            $hidden_fields = array(
                'SortBy' => '',
                'MaxRecordsInInfoblock' => 0,
                'MinRecordsInInfoblock' => 0,
                'RecordsPerPage' => 0,
                'ListSuffix' => '',
                'ListBody' => '',
                'ListPrefix' => '',
                'TitleList' => '',
                'TitleTemplate' => '',
                'UseAltTitle' => 0,
                'AllowTags' => 1,
                'NL2BR' => 0,
                'UseCaptcha' => 0,
                'Settings' => '',
            );
            foreach ($hidden_fields as $k => $v) {
                echo "<input type='hidden' name='$k' value='" . htmlspecialchars($v, ENT_QUOTES) . "'>\n";
            }
        }

        if ($data->Type !== 'object_partial') {
            ob_start();
        ?>
        <table border='0' cellpadding='0' cellspacing='0' width='99%'>
            <tr>
                <td style='border: none;'>
                    <?= CONTROL_CLASS_CLASS_OBJECTSLIST_TITLE ?>:<br>
                    <input type='text' name='TitleList' size='50' maxlength='255'
                        value="<?= htmlspecialchars($data->TitleList) ?>">
                    <br/>&nbsp;
                </td>
            </tr>
            <tr>
                <td style='border: none;'>
                    <? /* <a name='ListPrefixLink' href='#ListPrefix' onclick="window.open('<?= $ADMIN_PATH
                ?>class/index.php?phase=12&formtype=class&window=opener&form=ClassForm&textarea=ListPrefix<?= (($type != 3) ? "&classid=$ClassID&systemclassid=0" : "&classid=0&systemclassid=$ClassID")
                ?>','LIST','top=50, left=100,directories=no,height=600,location=no,menubar=no,resizable=no,scrollbars=yes,status=yes,toolbar=no,width=400'); return false;"><?= CONTROL_CLASS_CLASS_SHOW_VAR_FUNC_LIST
                ?></a><br/> */
                    ?>
                    <?= CONTROL_CLASS_CLASS_OBJECTSLIST_PREFIX ?>:<br/>
                    <textarea id='ListPrefix' wrap='OFF' rows='10' cols='60'
                        name='FormPrefix'><?= htmlspecialchars($data->FormPrefix)
                        ?></textarea>
                    <br/>&nbsp;
                </td>
            </tr>
            <tr>
                <td style='border: none;'>
                    <? /* <a name='ListBodyLink' href='#ListBody' onclick="window.open('<?= $ADMIN_PATH
                ?>class/index.php?phase=12&formtype=class&window=opener&form=ClassForm&textarea=ListBody<?= (($type != 3) ? "&classid=$ClassID&systemclassid=0" : "&classid=0&systemclassid=$ClassID")
                ?>','LIST','top=50, left=100,directories=no,height=600,location=no,menubar=no,resizable=no,scrollbars=yes,status=yes,toolbar=no,width=400'); return false;"><?= CONTROL_CLASS_CLASS_SHOW_VAR_FUNC_LIST
                ?></a><br/> */
                    ?>
                    <?= CONTROL_CLASS_CLASS_OBJECTSLIST_BODY ?>:<br/>
                    <textarea id='ListBody' wrap='OFF' rows='10' cols='60'
                        name='RecordTemplate'><?= htmlspecialchars($data->RecordTemplate)
                        ?></textarea>
                    <br/>&nbsp;
                </td>
            </tr>
            <tr>
                <td style='border: none;'>
                    <? /* <a name='ListSuffixLink' href='#ListSuffix' onclick="window.open('<?= $ADMIN_PATH
                ?>class/index.php?phase=12&formtype=class&window=opener&form=ClassForm&textarea=ListSuffix<?= (($type != 3) ? "&classid=$ClassID&systemclassid=0" : "&classid=0&systemclassid=$ClassID")
                ?>','LIST','top=50, left=100,directories=no,height=600,location=no,menubar=no,resizable=no,scrollbars=yes,status=yes,toolbar=no,width=400'); return false;"><?= CONTROL_CLASS_CLASS_SHOW_VAR_FUNC_LIST
                ?></a><br/> */
                    ?>
                    <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SUFFIX
                    ?>:<br/>
                    <textarea id='ListSuffix' wrap='OFF' rows='10' cols='60'
                        name='FormSuffix'><?= htmlspecialchars($data->FormSuffix)
                        ?></textarea>
                    <br/>&nbsp;
                </td>
            </tr>
            <tr>
                <td style='border: none;'>
                    <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW
                    ?> <input type='text' name='RecordsPerPage' SIZE='4'
                        maxlength='11'
                        value="<?= htmlspecialchars($data->RecordsPerPage)
                        ?>"> <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ
                    ?><br/>&nbsp;
                </td>
            </tr>
            <tr>
                <td style='border: none;'>
                    <label><?= CONTROL_CLASS_CLASS_MIN_RECORDS ?>: &nbsp;
                    <input type='text' name='MinRecordsInInfoblock' size='4'
                        maxlength='11'
                        value="<?= htmlspecialchars($data->MinRecordsInInfoblock) ?>">
                    </label>
                    <br/>&nbsp;
                </td>
            </tr>
            <tr>
                <td style='border: none;'>
                    <label><?= CONTROL_CLASS_CLASS_MAX_RECORDS ?>: &nbsp;
                    <input type='text' name='MaxRecordsInInfoblock' size='4'
                        maxlength='11'
                        value="<?= htmlspecialchars($data->MaxRecordsInInfoblock) ?>">
                    </label>
                    <br/>&nbsp;
                </td>
            </tr>
            <tr>
                <td style='border: none;'>
                    <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SORT
                    ?>*:<br/><input id='SortBy' type='text' name='SortBy' size='50'
                        maxlength='255' value="<?= htmlspecialchars($data->SortBy)
                    ?>"><br/>
                    * <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SORTNOTE
                    ?>
                </td>
            </tr>
        </table>

        <?php
            $fieldset = new nc_admin_fieldset(CONTROL_CLASS_CLASS_OBJECTSLIST);
            echo $fieldset->add(ob_get_clean())->result();
        }

        ob_start();

        ?>

        <table border=0 cellpadding=0 cellspacing=0 width=98%>
            <?php if ($data->Type !== 'object_partial'): ?>
            <tr>
                <td style='border: none;'>
                    <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGETITLE; ?>:<br/>
                    <input type='text' name='TitleTemplate' size='50'
                        maxlength='255'
                        value="<?= htmlspecialchars($data->TitleTemplate)
                        ?>">
                </td>
            </tr>
            <tr>
                <td style='border: none;'>
                    <input type='hidden' name='UseAltTitle' value='0'>
                    <input type='checkbox' name='UseAltTitle' id='UseAltTitle'
                        value='1' <?= ($data->UseAltTitle ? "checked" : ""); ?> />
                    <label for='UseAltTitle'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_USEASALT; ?></label>
                    <br/><br/>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style='border: none;'>
                    <? /* <a name='PageBodyLink' href='#PageBody' onclick="window.open('<?= $ADMIN_PATH
                ?>class/index.php?phase=12&formtype=class&window=opener&form=ClassForm&textarea=PageBody<?= (($type != 3) ? "&classid=$ClassID&systemclassid=0" : "&classid=0&systemclassid=$ClassID")
                ?>','LIST','top=50, left=100,directories=no,height=600,location=no,menubar=no,resizable=no,scrollbars=yes,status=yes,toolbar=no,width=400'); return false;"><?= CONTROL_CLASS_CLASS_SHOW_VAR_FUNC_LIST
                ?></a><br/> */
                    ?>
                    <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGEBODY; ?>:<br/>
                    <textarea id='PageBody' wrap='OFF' rows='10' cols='60'
                        name='RecordTemplateFull'><?= htmlspecialchars($data->RecordTemplateFull); ?></textarea>
                </td>
            </tr>
        </table>
        <?

        $fieldset = new nc_admin_fieldset(CONTROL_CLASS_CLASS_OBJECTVIEW);
        echo $fieldset->add(ob_get_clean())->result();

        ob_start();

        ?>

        <table border='0' cellpadding='0' cellspacing='0' width='99%'>
            <?php if ($data->Type !== 'object_partial'): ?>
            <tr>
                <td colspan='2' style='border: none;'>
                    <div class='nc-form-checkbox-block'>
                        <input type='hidden' name='AllowTags' value='0'>
                        <label>
                            <input type='checkbox'
                                name='AllowTags' <?= ($data->AllowTags ? "checked" : "") ?>
                                value='1'/>
                            <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML ?>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan='2' style='border: none;'>
                    <div class='nc-form-checkbox-block'>
                        <input type='hidden' name='NL2BR' value='0'>
                        <label>
                            <input type='checkbox'
                                name='NL2BR' <?= ($data->NL2BR ? "checked" : "") ?>
                                value='1'/>
                            <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR ?>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan='2' style='border: none;'>
                    <div class='nc-form-checkbox-block'>
                        <input type='hidden' name='UseCaptcha' value='0'>
                        <label>
                            <input type='checkbox'
                                name='UseCaptcha' <?= ($data->UseCaptcha ? "checked" : "") ?>
                                value='1'/>
                            <?= CONTROL_CLASS_USE_CAPTCHA ?>
                        </label>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <? if ($File_Mode && $type == 1): ?>
                <?php if ($data->Type !== 'object_partial'): ?>
                <tr>
                    <td colspan='2' style='border: none;'>
                        <?php nc_print_component_disable_block_markup_field($data->DisableBlockMarkup, $data->DisableBlockListMarkup); ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan='2' style='border: none;'>
                        <?php nc_print_component_multipurpose_fields($data->IsMultipurpose, $data->CompatibleFields); ?>
                        <br/><br/>
                    </td>
                </tr>
            <? endif; ?>
            <?php if ($data->Type !== 'object_partial'): ?>
                <tr>
                    <td colspan='2' style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_SYSTEM ?>:<br/>
                        <textarea id='Settings' wrap='OFF' rows='8' cols='60'
                            name='Settings'><?= htmlspecialchars($data->Settings) ?></textarea>
                        <br/><br/>
                    </td>
                </tr>
            <?php endif; ?>
            <? if ($File_Mode): ?>
            <tr>
                <td colspan="2">
                    <? if (!$data->Class_ID || $nc_core->component->can_add_block_markup($data->Class_ID)): ?>
                        <?= CONTROL_CLASS_SITE_STYLES ?>:<br/>
                        <textarea id="SiteStyles" wrap="OFF" rows="8" cols="60" class="cm-css"
                            name="SiteStyles"><?= htmlspecialchars($data->SiteStyles) ?></textarea>
                    <? else: ?>
                        <div class='nc-alert'>
                            <i class='nc-icon-l nc--status-info'></i>
                            <b><?= CONTROL_CLASS_SITE_STYLES_DISABLED_WARNING ?></b>
                            <br><br>
                            <?= CONTROL_CLASS_SITE_STYLES_ENABLE_WARNING ?><br>
                            <?= sprintf(CONTROL_CLASS_SITE_STYLES_DOCS_LINK, 'https://netcat.ru/developers/docs/components/stylesheets/') ?>
                            <br><br>

                            <div>
                                <button type="submit" name="DisableBlockMarkup" value="0">
                                <?= CONTROL_CLASS_SITE_STYLES_ENABLE_BUTTON ?>
                                </button>
                            </div>
                        </div>
                    <? endif; ?>
                    <br/><br/>
                </td>
            </tr>

            <? if (!$data->System_Table_ID): ?>
            <tr>
                <td colspan="2">
                    <?= CONTROL_CLASS_LIST_PREVIEW ?>:<br/>
                    <input name="ClassPreview" type="file" accept="image/png">
                    <div id="nc_component_list_preview_info">
                    <?

                    if ($ClassID) {
                        $preview_path = $nc_core->component->get_list_preview_relative_path($ClassID);
                        if ($preview_path) {
                            // при изменении этого блока см. также обновление html в ActionClassCompleted()
                            ?>
                                <a href="<?= $preview_path ?>" target="_blank">
                                    <?= NETCAT_MODERATION_FILES_UPLOADED ?>
                                </a>
                                (<?= nc_bytes2size(filesize($nc_core->DOCUMENT_ROOT . $preview_path)) ?>) &nbsp;
                                <label>
                                    <input type="checkbox" name="ClassPreview_delete" value="1">
                                    <?= NETCAT_MODERATION_FILES_DELETE ?>
                                </label>
                            <?
                        }
                    }

                    ?>
                    </div>
                    <br/><br/>
                </td>
            </tr>
            <? endif; // if (not system table) ?>

            <? endif; // if ($File_Mode) ?>

            <tr style="display:none">
                <td colspan='2' style='border: none;'>
                    <input type='hidden' name='DaysToHold' size='4'
                        value="<?= htmlspecialchars(!empty($data->DaysToHold) ? $data->DaysToHold : '') ?>"/>
                </td>
            </tr>

            <?
            if ($type == 2 && !$BaseClassID && !($data->ClassTemplate || $phase == 15)) {
                echo "<tr><td colspan='2'  style='border: none;'>
                      <a href='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "action.php?ctrl=admin.backup&amp;action=export_run&amp;raw=1&amp;type=component&amp;id=" . $ClassID . "&amp;" . $nc_core->token->get_url() . "'>" . CONTROL_CLASS_EXPORT . "</a>
                      </td></tr>";
            }

            if (!+$_REQUEST['fs'] && !$data->ClassTemplate && !$data->System_Table_ID) {
                echo "<tr><td colspan='2'  style='border: none;'>
                      <a href='convert.php?ClassID=" . $ClassID . "&amp;fs=0&amp;phase=1'>" . CONTROL_CLASS_CONVERT_BUTTON . "</a>
                      </td></tr>";
            }

            $file_path = $db->get_var("SELECT `File_Path` FROM `Class` WHERE `Class_ID` = '" . $ClassID . "'");
            $backup_file_exists = file_exists($nc_core->CLASS_TEMPLATE_FOLDER . $file_path . "class_v40_backup.html");

            if ($backup_file_exists && +$_REQUEST['fs'] && !$data->ClassTemplate && !$data->System_Table_ID) {
                echo "<tr><td colspan='2'  style='border: none;'>
                      <a href='convert.php?ClassID=" . $ClassID . "&amp;fs=1&amp;phase=3'>" . CONTROL_CLASS_CONVERT_BUTTON_UNDO . "</a>
                      </td></tr>";

            }
        ?>
        </table>
        <?

        $fieldset = new nc_admin_fieldset(CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_DOPL);
        echo $fieldset->add(ob_get_clean())->result();

        ?>

        <div align='right'>
            <?php
            if ($type == 1 || $BaseClassID) {
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => $phase == 15 ? CONTROL_CLASS_CLASS_TEMPLATE_ADD : CONTROL_CLASS_ADD,
                    "action" => "mainView.submitIframeForm()"
                );
            }
            elseif ($type > 1) {
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                    "action" => 'return false;" id="nc_class_save'
                );
                // add component template button
                if (!($data->ClassTemplate || $phase == 15)) {
                    $UI_CONFIG->actionButtons[] = array(
                        "id" => "submit",
                        "align" => "left",
                        "caption" => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                        "location" => "classtemplate" . (+$_REQUEST['fs'] ? '_fs' : '') . ".add(" . ($type == 3 ? $data->Class_ID : $ClassID) . ")"
                    );
                }
                $UI_CONFIG->actionButtons[] = array(
                    "id" => "preview",
                    "caption" => NETCAT_PREVIEW_BUTTON_CAPTIONCLASS,
                    "align" => "left",
                    "action" => "document.getElementById('mainViewIframe').contentWindow.SendClassPreview('','../../index.php')"
                );
            }
            ?>
        </div>

        <?php

        nc_print_admin_save_script('ClassForm');

        // Используется для мастера создания шаблонов
        global $Class_Type;
        echo "<input type='hidden' name='Class_Type' value='" . $Class_Type . "'>\n";
        if ($BaseClassID) {
            print "<input type='hidden' name='BaseClassID' value='" . $BaseClassID . "'>\n";
        }
        else {
            print "<input type='hidden' name='ClassID' value='" . $ClassID . "'>\n";
        }

        print $nc_core->token->get_input();
        if ($data->System_Table_ID) {
            print "<input type='hidden' name='System_Table_ID' value='" . $data->System_Table_ID . "'>\n";
        }

        ?>
        <input type='hidden' name='ClassGroup' value='<?= $ClassGroup ?>'>
        <input type='hidden' name='phase' value='<?= $phase ?>'>
        <input type='hidden' name='type' value='<?= ($BaseClassID ? 1 : $type) ?>'>
        <?php

        if ($phase == 15) {
            echo "<input type='hidden' name='ClassTemplate' value='" . $BaseClassID . "'>";
        }

        if ($data->ClassTemplate) {
            echo "<input type='hidden' name='ClassTemplate' value='" . $data->ClassTemplate . "'>";
        }

        ?>
        <input type='submit' class='hidden'>
        </form>
        <?

        $UI_CONFIG->remind[] = 'remind_redaction';
        if ($nc_core->get_settings('TextareaResize')) {
            echo '<script type="text/javascript">bindTextareaResizeButtons();</script>';
        }

    }

    function ClassForm_developer_mode($ClassID) {
        global $ROOT_FOLDER, $ADMIN_PATH;

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $SQL = "SELECT * FROM `Class` WHERE `Class_ID` = " . (int)$ClassID;

        $Array = $db->get_row($SQL);
        $sysTable = +$Array->System_Table_ID;
        $File_Mode = $Array->File_Mode;
        $File_input = '';

        if ($File_Mode) {
            $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
            $class_editor->load($ClassID, $Array->File_Path, $Array->File_Hash);
            $class_editor->fill_fields();
            $class_fields = $class_editor->get_fields();

            foreach ($class_fields as $field => $content) {
                $Array->$field = $field == 'RecordTemplate' ? nc_cleaned_RecordTemplate_of_string_service($content) : $content;
            }

            $File_input = "<input type='hidden' value='1' name='fs' />";
        }

        if (!$Array) {
            nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
        }

        if ($GLOBALS["AJAX_SAVER"]) { ?>
			<script>
				var formAsyncSaveEnabled = true;
				var NETCAT_HTTP_REQUEST_SAVING = "<?= str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_SAVING) ?>";
				var NETCAT_HTTP_REQUEST_SAVED  = "<?= str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_SAVED) ?>";
				var NETCAT_HTTP_REQUEST_ERROR  = "<?= str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_ERROR) ?>";
			</script>
		<? } else { ?>
			<script>var formAsyncSaveEnabled = false;</script>
		<? }

        ?>

        <div class='nc_admin_form_menu' style='padding-top: 20px;'>
            <h2><?= $Array->Class_Name; ?></h2>
            <div id='nc_object_slider_menu' class='slider_block_2' style='padding-top: 0px; padding-bottom: 15px;'>
                <ul>
                    <li id='nc_class_main' class='button_on'><?= CONTROL_CLASS_CLASS ?></li>
                    <li id='nc_class_add'><?= CONTROL_CLASS_ACTIONS_ADD ?></li>
                    <li id='nc_class_edit'><?= CONTROL_CLASS_ACTIONS_EDIT ?></li>
                    <li id='nc_class_del'><?= CONTROL_CLASS_ACTIONS_DELETE ?></li>
                    <li id='nc_class_search'><?= CONTROL_CLASS_ACTIONS_SEARCH ?></li>
                </ul>
            </div>
            <div class='nc_admin_form_menu_hr'></div>
        </div>

        <script>
            var nc_slider_li = $nc('div#nc_object_slider_menu ul li');

            nc_slider_li.click(function() {
                nc_slider_li.removeClass('button_on');
                $nc(this).addClass('button_on');
                $nc('form#adminForm > div > div').addClass('nc_class_none');
                $nc('form#adminForm > div > div#' + this.id + '_div').removeClass('nc_class_none').find('textarea').codemirror(nc_cmConfig);
            });
        </script>

        <div class='nc_admin_form_body'>

            <form method='post' id='adminForm' class='ClassForm nc-form' action='<?= $nc_core->ADMIN_PATH; ?>class/index.php'>

                <div id='nc_class_add_div' class='nc_class_none'>

                    <?php
                        print CONTROL_CLASS_CLASS_FORMS_ADDFORM
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddTemplate' id='AddTemplate' "
                            . ">" . htmlspecialchars($Array->AddTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_ADDRULES
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddCond'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_ADDCOND_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddCond' id='AddCond'>"
                            . htmlspecialchars($Array->AddCond) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddActionTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_ADDACTION_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddActionTemplate' id='AddActionTemplate'>"
                            . htmlspecialchars($Array->AddActionTemplate) . "</TEXTAREA><br><br>";
                    ?>
                </div>

                <div id='nc_class_edit_div' class='nc_class_none'>
                    <?php
                        print CONTROL_CLASS_CLASS_FORMS_EDITFORM
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditTemplate' id='EditTemplate' "
                            . ">" . htmlspecialchars($Array->EditTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_EDITRULES
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditCond'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_EDITCOND_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditCond' id='EditCond'>"
                            . htmlspecialchars($Array->EditCond) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_EDITLASTACTION
                            .  " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditActionTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_EDITACTION_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditActionTemplate' id='EditActionTemplate'>"
                            . htmlspecialchars($Array->EditActionTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_ONONACTION
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'CheckActionTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_CHECKACTION_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='CheckActionTemplate' id='CheckActionTemplate'>"
                            . htmlspecialchars($Array->CheckActionTemplate) . "</TEXTAREA><br><br>";
                    ?>
                </div>

                <div id='nc_class_del_div' class='nc_class_none'>
                    <?php
                        print CONTROL_CLASS_CLASS_FORMS_DELETEFORM
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'DeleteTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN . "</a>)" . "<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteTemplate' id='DeleteTemplate'>"
                            . htmlspecialchars($Array->DeleteTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_DELETERULES
                            . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteCond' id='DeleteCond'>"
                            . htmlspecialchars($Array->DeleteCond) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_ONDELACTION
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'DeleteActionTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_DELETEACTION_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteActionTemplate' id='DeleteActionTemplate'>"
                            . htmlspecialchars($Array->DeleteActionTemplate) . "</TEXTAREA><br><br>";
                    ?>
                </div>

                <div id='nc_class_search_div' class='nc_class_none'>
                    <?php
                        print CONTROL_CLASS_CLASS_FORMS_QSEARCH
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'FullSearchTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_QSEARCH_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='FullSearchTemplate' id='FullSearchTemplate'>"
                            . htmlspecialchars($Array->FullSearchTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_SEARCH
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'SearchTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_SEARCH_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS=60 NAME='SearchTemplate' id='SearchTemplate'>"
                            . htmlspecialchars($Array->SearchTemplate) . "</TEXTAREA><br><br>";
                    ?>
                </div>

                <div id='nc_class_main_div'>
                    <?= $File_input; ?>
                    <input type="hidden" value="<?php echo $Array->Class_Name ? $Array->Class_Name : $_GET['Class_Name']; ?>" name="Class_Name" />
                    <input type='hidden' name='DisableBlockMarkup' value='<?= (int)$Array->DisableBlockMarkup; ?>'>
                    <input type='hidden' name='DisableBlockListMarkup' value='<?= (int)$Array->DisableBlockListMarkup; ?>'>
                    <input type='hidden' name='IsMultipurpose' value='<?= (int)$Array->IsMultipurpose; ?>'>
                    <div id="classFields" style="display:none"><?= GetFieldsByClassId($ClassID) ?></div>

                    <h2><?= CONTROL_CLASS_CLASS_OBJECTSLIST ?></h2>
                    <table border='0' cellpadding='0' cellspacing='0' width='99%'>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_TITLE ?>:<br>
                                <input type='text'name='TitleList' size='50' maxlength='255' value="<?= htmlspecialchars($Array->TitleList) ?>"><br />
                                <br />
                            </td>
                        </tr>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_PREFIX ?>:<br/>
                                <textarea id='ListPrefix' wrap='OFF' rows='10' cols='60' name='FormPrefix'><?= htmlspecialchars($Array->FormPrefix)
                                ?></textarea><br />
                            </td>
                        </tr>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_BODY ?>:<br/>
                                <textarea id='ListBody' wrap='OFF' rows='10' cols='60' name='RecordTemplate'><?= htmlspecialchars($Array->RecordTemplate)
                                ?></textarea><br />
                            </td>
                        </tr>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SUFFIX?>:<br/>
                                <textarea id='ListSuffix' wrap='OFF' rows='10' cols='60' name='FormSuffix'><?= htmlspecialchars($Array->FormSuffix)
                                ?></textarea><br />
                            </td>
                        </TR>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW?> <input type='text'name='RecordsPerPage' SIZE='4' maxlength='255' value="<?= htmlspecialchars($Array->RecordsPerPage)
                                ?>"> <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ ?><br/>
                                <br/>
                            </td>
                        </tr>
                        <tr>
                            <td style='border: none;'>
                                <label><?= CONTROL_CLASS_CLASS_MIN_RECORDS ?>: &nbsp;
                                <input type='text' name='MinRecordsInInfoblock' size='4'
                                    maxlength='11'
                                    value="<?= htmlspecialchars($Array->MinRecordsInInfoblock) ?>">
                                </label>
                                <br/>&nbsp;
                            </td>
                        </tr>
                        <tr>
                            <td style='border: none;'>
                                <label><?= CONTROL_CLASS_CLASS_MAX_RECORDS ?>: &nbsp;
                                <input type='text' name='MaxRecordsInInfoblock' size='4'
                                    maxlength='11'
                                    value="<?= htmlspecialchars($Array->MaxRecordsInInfoblock) ?>">
                                </label>
                                <br/>&nbsp;
                            </td>
                        </tr>
                        <tr>
                            <td style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SORT
                                ?>*:<br/><input id='SortBy' type='text'name='SortBy' size='50' maxlength='255' value="<?= htmlspecialchars($Array->SortBy)
                                ?>"><br/>
                                * <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SORTNOTE
                                ?>
                            </td>
                        </tr>
                    </table>
                    <br/>

                    <h2><?= CONTROL_CLASS_CLASS_OBJECTVIEW?></h2>
                    <table border=0 cellpadding=6 cellspacing=0 width=99%>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGETITLE
                                ?>:<br /><input type='text'name='TitleTemplate' size='50' maxlength='255' value="<?= htmlspecialchars($Array->TitleTemplate)
                                ?>"><br />
                            </td>
                        </tr>
                        <tr>
                            <td style='border: none;'>
                                <input type='hidden' name='UseAltTitle' value='0'>
                                <input type='checkbox' name='UseAltTitle' id='UseAltTitle'  value='1' <?= ($Array->UseAltTitle ? "checked" : "")
                                ?>  /><label for='UseAltTitle'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_USEASALT
                                ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGEBODY?>:<br />
                                <textarea id='PageBody' wrap='OFF' rows='10' cols='60' name='RecordTemplateFull'><?= htmlspecialchars($Array->RecordTemplateFull)
                                ?></textarea><br />
                            </td>
                        </tr>
                    </table>

                    <h2><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_DOPL ?></h2>
                    <table border='0' cellpadding='6' cellspacing='0' width='99%'>
                        <tr>
                            <td colspan='2'  style='border: none;'>
                                <input type='hidden' name='AllowTags' value='0'>
                                <input type='checkbox' id='tags' name='AllowTags' <?= ($Array->AllowTags ? "checked" : "") ?> value='1' /> <label for='tags'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='2' style='border: none;'>
                                <input type='hidden' name='NL2BR' value='0'>
                                <input type='checkbox' id='br' name='NL2BR' <?= ($Array->NL2BR ? "checked" : "") ?> value='1' /> <label for='br'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='2' style='border: none;'>
                                <input type='hidden' name='UseCaptcha' value='0'>
                                <input type='checkbox' id='captcha' name='UseCaptcha' <?= ($Array->UseCaptcha ? "checked" : "") ?> value='1' /> <label for='captcha'><?= CONTROL_CLASS_USE_CAPTCHA ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='2' style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_SYSTEM ?>:<br/><textarea id='Settings' wrap='OFF' rows='8' cols='60' name='Settings'><?= htmlspecialchars($Array->Settings) ?></textarea><br />
                            </td>
                        </tr>

                        <tr  style="display:none">
                            <td colspan='2' style='border: none;'>
                                <input type='hidden' name='DaysToHold' size='4' value="<?= htmlspecialchars($Array->DaysToHold) ?>" />
                            </td>
                        </tr>
                    </table>
                    <br/>
                    <?php
                    echo "<input type='hidden' name='Class_Type' value='" . (isset($Class_Type) ? $Class_Type : '') . "'>\n";
                    echo "<input type='hidden' name='ClassID' value='" . $ClassID . "'>\n";
                    echo $nc_core->token->get_input();

                    if ($Array->System_Table_ID)
                        print "<input type='hidden' name='System_Table_ID' value='" . $Array->System_Table_ID . "'>\n";
                    ?>

                    <input type='hidden' name='phase' value='5' />
                    <input type='hidden' name='type' value='2' />
                    <input type='hidden' name='admin_mode' value='1' />
                    <input type='hidden' name='isNaked' value='1' />
                    <?php
                    if ($Array->ClassTemplate)
                        echo "<input type='hidden' name='ClassTemplate' value='" . $Array->ClassTemplate . "'>";
                    ?>
                </div>
            </form>
            <?=include_cd_files()?>
        </div>
        <div class='nc_admin_form_buttons'>
            <button type='button' class='nc_admin_metro_button nc-btn nc--blue' disable><?= NETCAT_REMIND_SAVE_SAVE; ?></button>
            <button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'><?= CONTROL_BUTTON_CANCEL ?></button>
        </div>

        <style>
            a { color:#1a87c2; }
            a:hover { text-decoration:none; }
            a img { border:none; }
            p { margin:0px; padding:0px 0px 18px 0px; }
            h2 { font-size:20px; font-family:'Segoe UI', SegoeWP, Arial; color:#333333; font-weight:normal; margin:0px; padding:20px 0px 10px 0px; line-height:20px; }
            form { margin:0px; padding:0px; }
            input { outline:none; }
            .clear { margin:0px; padding:0px; font-size:0px; line-height:0px; height:1px; clear:both; float:none; }
            select, input, textarea { border:1px solid #dddddd; }
            :focus { outline:none;}
            .input { outline:none; border:1px solid #dddddd; }
        </style>

        <script>
            var nc_admin_metro_buttons = $nc('.nc_admin_metro_button');

            $nc(function() {
                $nc('#adminForm').html('<div class="nc_admin_form_main">' + $nc('#adminForm').html() + '</div>');
            });

            nc_admin_metro_buttons.click(function() {
                $nc('#adminForm').submit();
            });

            $nc('.nc_admin_metro_button_cancel').click(function() {
				$nc.modal.close();
			});
        </script>
        <?
    }

##############################################
# Добавление/изменение шаблона
##############################################

    function ActionClassComleted($type, $parentID = false) {
        global $nc_core, $db;

        $ClassID = $nc_core->input->fetch_get_post('ClassID');
        $Class_Name = $nc_core->input->fetch_get_post('Class_Name');
        $Class_Group_New = $nc_core->input->fetch_get_post('Class_Group_New');
        $Class_Group = $nc_core->input->fetch_get_post('Class_Group');
        $ClassTemplate = $nc_core->input->fetch_get_post('ClassTemplate');
        $params = $nc_core->input->fetch_post();

        if ($type == 1) {
            // создание шаблона на основе другого компонента
            if (false != $parentID) {
                $File_Mode = nc_get_file_mode('Class', $parentID);
                $template = array();
                if ($File_Mode) {
                    $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
                    $class_editor->load($parentID);
                    $class_editor->fill_fields();
                    $template = $class_editor->get_fields();
                    $template['IsAuxiliary'] = $nc_core->component->get_by_id($parentID, 'IsAuxiliary');
                    $template['DisableBlockMarkup'] = $nc_core->component->get_by_id($parentID, 'DisableBlockMarkup');
                    $template['DisableBlockListMarkup'] = $nc_core->component->get_by_id($parentID, 'DisableBlockListMarkup');
                    $template['ObjectName'] = $nc_core->component->get_by_id($parentID, 'ObjectName');
                    $template['IsOptimizedForMultipleMode'] = $nc_core->component->get_by_id($parentID, 'IsOptimizedForMultipleMode');
                    $template['IsMultipurpose'] = $nc_core->component->get_by_id($parentID, 'IsMultipurpose');
                    $template['CompatibleFields'] = $nc_core->component->get_by_id($parentID, 'CompatibleFields');
                    $template['Main_ClassTemplate_ID'] = $nc_core->component->get_by_id($parentID, 'Main_ClassTemplate_ID');
                }
                else {
                    $template = $nc_core->component->get_by_id($parentID);
                }
                $params += $template;
                $params['CustomSettingsTemplate'] = $nc_core->component->get_by_id($parentID, 'CustomSettingsTemplate');
            }

            return $nc_core->component->add($Class_Name, $Class_Group_New ?: $Class_Group, $params, $ClassTemplate ?: 0);
        }

        if ($type == 3) {
            $ClassID = $db->get_var("
                SELECT `Class_ID`
                    FROM `Class`
                        WHERE `System_Table_ID` = '" . intval($ClassID) . "'
                          AND `ClassTemplate` = 0
                          AND `File_Mode` = " . +$_REQUEST['fs']);
        }

        $input = $nc_core->input->fetch_post();

        if (!empty($input['Class_Group_New'])) {
            $input['Class_Group'] = $input['Class_Group_New'];
        }

        $bool_fields = array(
            'UseAltTitle', 'AllowTags', 'NL2BR', 'UseCaptcha',
            'IsAuxiliary', 'IsOptimizedForMultipleMode', 'IsMultipurpose',
            'DisableBlockMarkup', 'DisableBlockListMarkup',
        );

        foreach ($bool_fields as $v) {
            if (isset($input[$v])) {
                $input[$v] += 0;
            }
        }

        $success = $nc_core->component->update($ClassID, $input);
        if ($success && $input['fs']) {
            $update = null;
            if ($nc_core->input->fetch_post('ClassPreview_delete')) {
                $preview_path = $nc_core->component->get_list_preview_relative_path($ClassID);
                if ($preview_path) {
                    unlink($nc_core->DOCUMENT_ROOT . $preview_path);
                    $update = '';
                }
            }

            $uploaded_preview = $nc_core->input->fetch_files('ClassPreview');
            if ($uploaded_preview) {
                $destination_path = $nc_core->component->get_list_preview_relative_path($ClassID, false);
                move_uploaded_file($uploaded_preview['tmp_name'], $nc_core->DOCUMENT_ROOT . $destination_path);
                $update = '<a href="' . $destination_path . '" target="_blank">' .
                          NETCAT_MODERATION_FILES_UPLOADED .
                          '</a> (' . nc_bytes2size($uploaded_preview['size']) . ') &nbsp; ' .
                          '<label>' .
                          '<input type="checkbox" name="ClassPreview_delete" value="1"> ' .
                          NETCAT_MODERATION_FILES_DELETE .
                          '</label>';
            }

            if ($update !== null) {
                $GLOBALS["_RESPONSE"]["update_html"]["#nc_component_list_preview_info"] = $update;
            }
        }

        return $success;
    }

    /*
     * Вывод списка шаблонов при создании нового
     */

    function addNewTemplate($Class_Group = "") {
        global $db, $UI_CONFIG, $ADMIN_PATH;

        $File_Mode = nc_get_file_mode('Class');

        $fs_input = '';
        $SQL_where = '`File_Mode` = ' . $File_Mode;

        if ($File_Mode) {
            $fs_input = "<input type='hidden' name='fs' value='1'>";
        }

        $classes = $db->get_results("SELECT `Class_ID` AS value,
		CONCAT(`Class_ID`, '. ', `Class_Name`) AS description,
		`Class_Group` AS optgroup
		FROM `Class`
                WHERE $SQL_where
                      AND Type != 'trash'
		ORDER BY `Class_Group`, `Priority`, `Class_ID`", ARRAY_A);
        ?>

            <h2><?= CONTROL_CLASS_CLASS_CREATENEW_BASICOLD ?></h2>
            <form method='get' action=''>
                <?= $fs_input ?>
                <table border='0' cellpadding='0' cellspacing='0'>
                     <tr>
                        <td width='80%'>
                            <?
                            echo "<select name='BaseClassID'>";
                            echo "<option value='0'>" . CONTROL_CLASS_CLASS_CREATENEW_CLEARNEW . "</option>";
                            if (!empty($classes))
                                echo nc_select_options($classes);
                            echo "</select>";
                            ?>
                        </td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
                <?
                $UI_CONFIG->actionButtons[] = array(
                        "id" => "submit",
                        "caption" => CONTROL_CLASS_CONTINUE,
                        "action" => "mainView.submitIframeForm()"
                );
                if ($Class_Group) {
                    print "<input type='hidden' name='ClassGroup' value='" . htmlspecialchars($Class_Group, ENT_QUOTES) . "'>";
                }
                ?>
                <input type='hidden' name='action_type' value=3 />
                <input type='hidden' name='phase' value='2'>
                <input type='submit' class='hidden'>
            </form>

        <?
    }

##############################################
# Подтверждение удаления
##############################################

    function ConfirmDeletion($ClassGroup = '') {
        global $db;
        global $UI_CONFIG;
        $ask = false;

        $class_id = 0;
        $class_id_array = array();
        $class_counter = 0;
        print "<form method='post' action='index.php'>";

        $nc_core = nc_Core::get_object();
        $template_class_id_array = array();

        $input = $nc_core->input->fetch_get_post();

        if (!empty($input)) {
            foreach ($input as $key => $val) {
                if (nc_substr($key, 0, 6) == "Delete" && $val) {
                    $ask = true;

                    $class_id = intval($val);

                    $SelectArray = $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID`='" . $class_id . "'");
					// check template existence
					if (!$SelectArray) {
						nc_print_status( sprintf(CONTROL_CLASS_CLASS_NOT_FOUND, $class_id), 'error');
						continue;
					}

					$class_id_array[] = $class_id;

                    print "<input type='hidden' name='" . $key . "' value='" . $val . "'>";
                    $class_counter++;

                    $template_ids = $db->get_col("SELECT Class_ID FROM Class WHERE ClassTemplate = '" . $class_id . "'");
                    if ($template_ids)
                        $template_class_id_array = array_merge($template_class_id_array, $template_ids);
                }
            }
        }

        if (!$ask)
            return false;

        if ($class_counter > 1) {
            $UI_CONFIG = new ui_config_class("delete", "", $ClassGroup);
            $post_f1 = CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_I;
            $post_f2 = CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_U;
        } else {
            print "<input type='hidden' name='ClassGroup' value='" . $db->get_var("SELECT md5(`Class_Group`) FROM `Class` WHERE `Class_ID` = '" . $class_id . "' GROUP BY `Class_Group`") . "'>";
            $UI_CONFIG = new ui_config_class('delete', $class_id, $ClassGroup);
        }

        print $nc_core->token->get_input();
        print "<input type='hidden' name='fs' value='".$_REQUEST['fs']."'>".
            "<input type='hidden' name='phase' value='7'>".
            "</form>";

        if ( !empty($class_id_array) ):
			nc_print_status(CONTROL_CLASS_CLASS_DELETE_WARNING, 'info', array(isset($post_f1) ? $post_f1 : null, isset($post_f2) ? $post_f2 : null));
			nc_list_class_use($class_id_array, 0, 0);
			if ($template_class_id_array) {
				echo "<br/>";
				nc_list_class_template_use($template_class_id_array);
			}
        endif;

        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CONFIRMDELETE,
            "action" => "mainView.submitIframeForm()",
            "red_border" => true,
		);

        return true;
    }

    function ConfirmClassTemplateDeletion($ClassTemplate = 0) {
        global $UI_CONFIG;

        // system superior object
        $nc_core = nc_Core::get_object();

        // system db object
        if (is_object($nc_core->db))
            $db = &$nc_core->db;

        $ask = false;
        $class_id = 0;
        $class_id_array = array();
        $ClassTemplate = intval($ClassTemplate);

        print "<form method='post' action='index.php'>";

        $need_arr = $nc_core->input->fetch_get_post();
        if (!isset($class_counter)) {
            $class_counter = 0;
        } 
        if (!empty($need_arr)) {
            foreach ($need_arr as $key => $val) {
                if (substr($key, 0, 6) == "Delete" && $val) {
                    $ask = true;

                    $class_id = intval($val);
                    $class_id_array[] = $class_id;

                    print "<input type='hidden' name='" . $key . "' value='" . $val . "'>";
                    $class_counter++;
                }
            }
        }

        if (!$ask)
            return false;

        if ($ClassTemplate) {
            $BaseClassName = $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '" . $ClassTemplate . "'");
        } else {
            list($ClassTemplate, $BaseClassName) = $nc_core->db->get_row("SELECT mc.`Class_ID`, mc.`Class_Name`
				FROM `Class` AS tc
				LEFT JOIN `Class` AS mc ON tc.`ClassTemplate` = mc.`Class_ID`
				WHERE tc.`Class_ID` = '" . $class_id . "'", ARRAY_N);
        }

		// check template existence
		if (!$BaseClassName) {
			nc_print_status( sprintf(CONTROL_CLASS_CLASS_TEMPLATE_NOT_FOUND, $class_id), 'error');
		}
		else {
			if ($class_counter > 1) {
				$UI_CONFIG = new ui_config_class_template("delete", 0, $ClassTemplate);
			} else {
				$UI_CONFIG = new ui_config_class_template('delete', $class_id);
			}
			// notice
			nc_print_status(sprintf(CONTROL_CLASS_CLASS_TEMPLATE_DELETE_WARNING, $BaseClassName), 'info');
		}

        print $nc_core->token->get_input();
        ?>
        <input type='hidden' name='phase' value='19' />
        <input type='hidden' name='ClassTemplate' value='<?= $ClassTemplate ?>' />
        <input type='hidden' name='fs' value='<?= +$_REQUEST['fs']; ?>' />
    </form>
    <?php

    if ($BaseClassName) {
		nc_list_class_template_use($class_id_array, 0);
    }

    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CONFIRMDELETE,
        "action" => "mainView.submitIframeForm()",
        "red_border" => true,
    );
}

/*
 * Удаление шаблона
 */

function CascadeDeleteClass($ClassID) {
    global $UI_CONFIG;
    $nc_core = nc_Core::get_object();
    $ClassID = (int)$ClassID;
    $File_Mode = nc_get_file_mode('Class', $ClassID);

    if ($File_Mode) {
        $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        $class_editor->load($ClassID);
        $class_editor->delete_template();
    }

    // удаление шаблонов
    $template_ids = $nc_core->db->get_col("SELECT Class_ID FROM Class WHERE ClassTemplate = '{$ClassID}'");
    if (!empty($template_ids)) {
        foreach ($template_ids as $v) {
            CascadeDeleteClassTemplate($v);
        }
    }

    $ClassGroup = $nc_core->db->get_var("SELECT `Class_Group` FROM `Class` WHERE `Class_ID` = '{$ClassID}'");
    $isMoreClasses = $nc_core->db->get_var("SELECT COUNT(*) - 1 FROM `Class` WHERE `Class_Group` = '{$ClassGroup}'");

    //$LockTables = "LOCK TABLES `Class` WRITE, `Field` WRITE,";
    //$LockTables.= "`Message".$ClassID."` WRITE,";
    //$LockTables.= "`Sub_Class` WRITE";
    //$LockResult = $nc_core->db->query($LockTables.$AddLockTables);
    // get ids
    $messages_data = $nc_core->db->get_results(
        "SELECT sc.`Catalogue_ID`, m.`Subdivision_ID`, m.`Sub_Class_ID`, m.`Message_ID`, m.*
         FROM `Message{$ClassID}` AS m
         LEFT JOIN `Sub_Class` AS sc ON sc.`Sub_Class_ID` = m.`Sub_Class_ID`
         ORDER BY sc.`Catalogue_ID`, m.`Subdivision_ID`, m.`Sub_Class_ID`",
        ARRAY_A,
        'Message_ID'
    );

    $messages = array_values((array)$messages_data);

    // call event
    if (!empty($messages)) {
        list($catalogue, $sub, $cc) = $messages[0];
        $messages_arr = array($messages[0]['Message_ID']);
        $messages_data_arr = array($messages[0]['Message_ID'] => $messages_data[$messages[0]['Message_ID']]);
        foreach ($messages as $value) {
            $message_info = $messages_data[$value['Message_ID']];
            if ($value['Catalogue_ID'] !== $catalogue || $value['Subdivision_ID'] !== $sub || $value['Sub_Class_ID'] !== $cc) {
                $nc_core->event->execute(nc_Event::BEFORE_OBJECT_DELETED, $catalogue, $sub, $cc, $ClassID, $messages_arr, $messages_data_arr);
                list($catalogue, $sub, $cc) = $value;
                $messages_arr = array($value['Message_ID']);
                $messages_data_arr = array($value['Message_ID'] => $message_info);
            } else {
                $messages_arr[] = $value['Message_ID'];
                $messages_data_arr[$value['Message_ID']] = $message_info;
            }
        }

        // Удаление блоков страниц полного вывода (по регулярному выражению ^O_123_[0-9]+$)
        $full_page_area_regexp = $nc_core->get_component($ClassID)->get_full_page_area_keyword_regexp();
        $nc_core->sub_class->delete_by_area_keyword(0, $full_page_area_regexp, true);
    }

    // delete messages
    $nc_core->db->query("DROP TABLE `Message{$ClassID}`");

    // call event
    if (!empty($messages)) {
        list($catalogue, $sub, $cc) = $messages[0];
        $messages_arr = array($messages[0]['Message_ID']);
        $messages_data_arr = array($messages_data[$messages[0]['Message_ID']]);
        foreach ($messages as $value) {
            if ($value['Catalogue_ID'] !== $catalogue || $value['Subdivision_ID'] !== $sub || $value['Sub_Class_ID'] !== $cc) {
                $nc_core->event->execute(nc_Event::AFTER_OBJECT_DELETED, $catalogue, $sub, $cc, $ClassID, $messages_arr, $messages_data_arr);
                list($catalogue, $sub, $cc) = $value;
                $messages_arr = array($value['Message_ID']);
                $messages_data_arr = array($messages_data[$value['Message_ID']]);
            } else {
                $messages_arr[] = $value['Message_ID'];
                $messages_data_arr[] = $messages_data[$value['Message_ID']];
            }
        }
    }

    // delete fields
    $nc_core->db->query("DELETE FROM `Field` WHERE `Class_ID` = '{$ClassID}'");

    $subclasses = $nc_core->db->get_results(
        "SELECT `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID`, `Class_ID`
         FROM `Sub_Class`
         WHERE `Class_ID` = '{$ClassID}'",
        ARRAY_N
    );
    // delete subclasses
    if (!empty($subclasses)) {
        foreach ($subclasses as $subclass) {
            $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_DELETED, $subclass[0], $subclass[1], $subclass[2]);
            DeleteSubClassFiles($subclass[2], $subclass[3]);
            $nc_core->db->query("DELETE FROM `Sub_Class` WHERE `Sub_Class_ID` = '{$subclass[2]}'");
            $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Subdivision` WHERE `Sub_Class_ID` = '{$subclass[2]}'");
            $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Subdivision_Exception` WHERE `Sub_Class_ID` = '{$subclass[2]}'");
            $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_DELETED, $subclass[0], $subclass[1], $subclass[2]);
        }
    }

    $nc_core->event->execute(nc_Event::BEFORE_COMPONENT_DELETED, $ClassID);
    $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Class` WHERE `Class_ID` = '{$ClassID}'");
    $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Class_Exception` WHERE `Class_ID` = '{$ClassID}'");
    $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Message` WHERE `Class_ID` = '{$ClassID}'");
    $nc_core->db->query("DELETE FROM `Class_StyleCache` WHERE `Class_ID` = '{$ClassID}' OR `Class_Template_ID` = '{$ClassID}'");
    $nc_core->db->query("DELETE FROM `Class` WHERE `Class_ID` = '{$ClassID}'");
    $nc_core->event->execute(nc_Event::AFTER_COMPONENT_DELETED, $ClassID);
    $nc_core->component->delete_compatible_components_cache_by_id($ClassID);

    // Удаление сгенерированных изображений для компонента
    nc_image_generator::remove_generated_images($ClassID);

    //$UnlockResult = $nc_core->db->query("UNLOCK TABLES");

    if (!$isMoreClasses) {
        $UI_CONFIG->treeChanges['deleteNode'][] = 'group-' . md5($ClassGroup);
    } else {
        $UI_CONFIG->treeChanges['deleteNode'][] = 'dataclass-' . $ClassID;
    }

    return $isMoreClasses;
}

function CascadeDeleteClassTemplate($ClassTemplateID) {
    global $UI_CONFIG;

    // system superior object
    $nc_core = nc_Core::get_object();

    // system db object
    if (is_object($nc_core->db))
        $db = &$nc_core->db;

    $ClassTemplateID = intval($ClassTemplateID);

    $File_Mode = nc_get_file_mode('Class', $ClassTemplateID);
    if ($File_Mode) {
        $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        $class_editor->load($ClassTemplateID);
        $class_editor->delete_template();
    }

    list($mainComponentID, $type) = $nc_core->db->get_row("SELECT `ClassTemplate`, `Type` FROM `Class`
    WHERE `Class_ID` = '" . $ClassTemplateID . "'", ARRAY_N);
    $isMoreClassTemplates = 0;
    if ($mainComponentID) {
        $isMoreClassTemplates = $db->get_var("SELECT COUNT(*) - 1 FROM `Class` WHERE `ClassTemplate` = '" . $mainComponentID . "'");
    }

    $added_sql = '';
    if ($type == 'rss')
        $added_sql = " `AllowRSS` = 0 ";
    if ($type == 'xml')
        $added_sql = " `AllowXML` = 0 ";

    if ($added_sql) {
        $subclasses = $nc_core->db->get_results("SELECT `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID` FROM `Sub_Class`
    WHERE `Class_ID` = '" . $mainComponentID . "' ", ARRAY_N);
        if (!empty($subclasses))
            foreach ($subclasses as $subclass) {
                // execute core action
                $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $subclass[0], $subclass[1], $subclass[2]);

                $nc_core->db->query("UPDATE `Sub_Class` SET " . $added_sql . "
        WHERE `Sub_Class_ID` = '" . $subclass[2] . "'");
                // execute core action
                $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $subclass[0], $subclass[1], $subclass[2]);
            }
    }

    $subclasses = $nc_core->db->get_results(
        "SELECT `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID`
           FROM `Sub_Class`
           WHERE `Class_Template_ID` = $ClassTemplateID
              OR `Edit_Class_Template` = $ClassTemplateID
              OR `Admin_Class_Template` = $ClassTemplateID",
       ARRAY_N);
    // update subclasses
    if (!empty($subclasses)) {
        foreach ($subclasses as $subclass) {
            // execute core action
            $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $subclass[0], $subclass[1], $subclass[2]);

            foreach (array('Class_Template_ID', 'Edit_Class_Template', 'Admin_Class_Template') as $field) {
                $nc_core->db->query(
                    "UPDATE `Sub_Class`
                        SET `$field` = 0
                      WHERE `Sub_Class_ID` = '" . $subclass[2] . "'
                        AND `$field` = $ClassTemplateID"
                );
            }
            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $subclass[0], $subclass[1], $subclass[2]);
        }
    }

    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_COMPONENT_TEMPLATE_DELETED, $mainComponentID, $ClassTemplateID);

    $nc_core->db->query("DELETE FROM `Class_StyleCache` WHERE `Class_Template_ID` = '$ClassTemplateID'");
    $nc_core->db->query("DELETE FROM `Class` WHERE `Class_ID` = '$ClassTemplateID'");
    // execute core action
    $nc_core->event->execute(nc_Event::AFTER_COMPONENT_TEMPLATE_DELETED, $mainComponentID, $ClassTemplateID);
    $nc_core->component->delete_compatible_components_cache_by_id($ClassTemplateID);

    if (!$isMoreClassTemplates && $mainComponentID) {
        $UI_CONFIG->treeChanges['deleteNode'][] = "classtemplates-" . $mainComponentID;
    } else {
        $UI_CONFIG->treeChanges['deleteNode'][] = "classtemplate-" . $ClassTemplateID;
    }

    return $nc_core->db->rows_affected;
}

/**
 * Форма действий шаблона
 *
 * @param int $ClassID or SystemTableID
 * @param string action -
 * @param int $phase
 * @param int type: 1 - class, 2 - system table
 * @param int myaction: 1 - add, 2 - edit, 3 - search, 4 - subscribe, 5 - delete
 */
function ClassActionForm($ClassID, $action, $phase, $type, $myaction, $isNaked = false) {
    global $ClassGroup, $SystemTableID, $user_table_mode;
    global $UI_CONFIG;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    if (!$ClassID) {
        print nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
        return;
    }
  
    $myaction = (int)$myaction;
    if (!isset($_REQUEST['fs'])) {
        $_REQUEST['fs'] = null;
    }
    if (!$isNaked) {
        ?>
        <form method='post' id="ClassForm" action='<?= $action ?>'><?
    }
?>

        <font color='gray'>

        <?php
        $select = "SELECT `AddTemplate`,
                          `AddCond`,
                          `AddActionTemplate`,
                          `EditTemplate`,
                          `EditCond`,
                          `EditActionTemplate`,
                          `CheckActionTemplate`,
                          `DeleteTemplate`,
                          `DeleteCond`,
                          `DeleteActionTemplate`,
                          `SearchTemplate`,
                          `FullSearchTemplate`,
                          `SubscribeTemplate`,
                          `SubscribeCond`,
                          `System_Table_ID`,
                          `File_Mode`,
                          `File_Path`";


        // class or system table
        $select.= ( $type == 1) ? " FROM `Class` WHERE `Class_ID` = "
                                : " FROM `Class` WHERE `File_Mode` = " . +$_REQUEST['fs'] . " AND `ClassTemplate` = 0 AND `System_Table_ID` = ";
        $select.= "'" . intval($ClassID) . "'";

        if (!$Array = $nc_core->db->get_row($select)) {
            nc_print_status(CONTROL_CLASS_ERRORS_DB, "error");           
            exit();
        }

        $show_generate_link = false;

        if (!$SystemTableID || $SystemTableID == 3) {
            $show_generate_link = true;
            $sysTable = $SystemTableID ? $SystemTableID : 0;
        }

        $SystemTableID != $Array->System_Table_ID && $type == 1 ? $sysTable = $Array->System_Table_ID : "";

        $classTemplate = ($type == 1 ? $nc_core->component->get_by_id($ClassID, "ClassTemplate") : 0);

        $File_Mode = nc_get_file_mode('Class');
        $File_Mode = $File_Mode ? $File_Mode : $Array->File_Mode;

        echo "<input type='hidden' name='fs' value='$File_Mode'>";

        if ($File_Mode) {
            if (true || !$classTemplate) {
                $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
                $class_editor->load($ClassID, $Array->File_Path);
                $class_editor->fill_fields();
                $class_fields = $class_editor->get_fields();
                foreach ($class_fields as $key => $val) {
                    $Array->$key = $val;
                }
            }
        }
        // Add, edit, delete, search or subscribe
        switch ($myaction) {
            // add
            case 1:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customadd', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customadd', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customadd', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_ADDFORM . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddTemplate' id='AddTemplate'>" . htmlspecialchars($Array->AddTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_ADDRULES . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddCond'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_ADDCOND_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddCond' id='AddCond'>" . htmlspecialchars($Array->AddCond) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddActionTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_ADDACTION_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddActionTemplate' id='AddActionTemplate'>" . htmlspecialchars($Array->AddActionTemplate) . "</TEXTAREA><br><br>";
                $UI_CONFIG->remind[] = 'remind_add';
                print_bind();
                break;
            // edit
            case 2:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customedit', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customedit', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customedit', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_EDITFORM . "" . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditTemplate' id='EditTemplate'>" . htmlspecialchars($Array->EditTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_EDITRULES . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditCond'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_EDITCOND_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditCond' id='EditCond'>" . htmlspecialchars($Array->EditCond) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_EDITLASTACTION . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditActionTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_EDITACTION_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditActionTemplate' id='EditActionTemplate'>" . htmlspecialchars($Array->EditActionTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_ONONACTION . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'CheckActionTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_CHECKACTION_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='CheckActionTemplate' id='CheckActionTemplate'>" . htmlspecialchars($Array->CheckActionTemplate) . "</TEXTAREA><br><br>";
                print_bind();
                break;
            // search
            case 3:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customsearch', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customsearch', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customsearch', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_QSEARCH . "" . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'FullSearchTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_QSEARCH_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='FullSearchTemplate' id='FullSearchTemplate'>" . htmlspecialchars($Array->FullSearchTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_SEARCH . "" . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'SearchTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_SEARCH_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS=60 NAME='SearchTemplate' id='SearchTemplate'>" . htmlspecialchars($Array->SearchTemplate) . "</TEXTAREA><br><br>";
                $UI_CONFIG->remind[] = 'remind_search';
                print_bind();
                break;
            // subscribe
            case 4:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customsubscribe', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customsubscribe', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customsubscribe', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_MAILRULES . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='SubscribeCond' id='SubscribeCond'>" . htmlspecialchars($Array->SubscribeCond) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_MAILTEXT . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='SubscribeTemplate' id = 'SubscribeTemplate'>" . htmlspecialchars($Array->SubscribeTemplate) . "</TEXTAREA><br><br>";
                $UI_CONFIG->remind[] = 'remind_subscrib';
                print_bind();
                break;
            // delete
            case 5:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customdelete', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customdelete', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customdelete', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_DELETEFORM . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'DeleteTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN . "</a>)" : "") . "<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteTemplate' id='DeleteTemplate'>" . htmlspecialchars($Array->DeleteTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_DELETERULES . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteCond' id='DeleteCond'>" . htmlspecialchars($Array->DeleteCond) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_ONDELACTION . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'DeleteActionTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_DELETEACTION_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteActionTemplate' id='DeleteActionTemplate'>" . htmlspecialchars($Array->DeleteActionTemplate) . "</TEXTAREA><br><br>";
                $UI_CONFIG->remind[] = 'remind_delete';
                print_bind();
                break;
        }

        if (!$isNaked) {

            $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                    "action" => 'return false;" id="nc_class_save'
            );

            switch ($myaction) {
                case 1:
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "preview",
                            "caption" => NETCAT_PREVIEW_BUTTON_CAPTIONADDFORM,
                            "align" => "left",
                            "action" => "document.getElementById('mainViewIframe').contentWindow.SendClassPreview('','../../add.php')"
                    );
                    break;
                case 2:
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "preview",
                            "caption" => NETCAT_PREVIEW_BUTTON_CAPTIONEDITFORM,
                            "align" => "left",
                            "action" => "document.getElementById('mainViewIframe').contentWindow.SendClassPreview('','../../message.php')"
                    );
                    break;
                case 3:
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "preview",
                            "caption" => NETCAT_PREVIEW_BUTTON_CAPTIONSEARCHFORM,
                            "align" => "left",
                            "action" => "document.getElementById('mainViewIframe').contentWindow.SendClassPreview('','../../search.php')"
                    );
                    break;
            }

            nc_print_admin_save_script('ClassForm');

            print $nc_core->token->get_input();
            print "<input type='hidden' name='ClassID' value='" . $ClassID . "'/>\n";
            print "<input type='hidden' name='phase' value='" . $phase . "'/>";
            print "<input type='hidden' name='myaction' value='" . $myaction . "'/>";
            print "<input type='hidden' name='type' value='" . $type . "'/>";
            print "<input type='hidden' name='ClassGroup' value='" . $ClassGroup . "'/>";
            print "<input type='hidden' name='ClassTemplate' value='" . $classTemplate . "'/>";
            print "
	<input type='submit' class='hidden' /><div style='display:none' id='classFields'>" . GetFieldsByClassId($ClassID) . "</div>";
        }

        if ($nc_core->get_settings('TextareaResize')) {
            echo '<script type="text/javascript">bindTextareaResizeButtons();</script>';
        }

        print "
      </font> ";

        if (!$isNaked) {
            "</form>";
        }
    }


##############################################
# Изменение форм действий шаблона
##############################################

    function ClassActionCompleted($myaction, $type) {
        global $nc_core, $db;
        # type=1 - это шаблон
        # type=2 - это системная таблица
        # при type=2 ClassID - это на самом деле SystemTableID

        $ClassID = intval($nc_core->input->fetch_get_post('ClassID'));

        if ($type == 2) {
            $ClassID = $db->get_var("SELECT `Class_ID` FROM `Class`  WHERE `System_Table_ID` = '" . $ClassID . "' AND `ClassTemplate` = 0 AND File_Mode = " . +$_REQUEST['fs']);
        }

        return $nc_core->component->update($ClassID, $nc_core->input->fetch_post());
    }

    function nc_class_info($class_id, $action, $phase) {
        global $UI_CONFIG;
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $class_id = intval($class_id);

        $info = $db->get_row("SELECT `ClassDescription`, `CustomSettingsTemplate` FROM `Class` WHERE `Class_ID` = '" . $class_id . "'", ARRAY_A);
        $fields = $db->get_results("SELECT `Field_Name`, `Description` FROM `Field` WHERE `Class_ID` = '" . $class_id . "' ORDER BY `Priority`", ARRAY_A);

        echo ClassInformation($class_id, $action, $phase);

        $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                "action" => "mainView.submitIframeForm()"
        );
    }

    /**
     * Show using class
     *
     * @param array | null arrayClassID, if !isset - all class
     * @param int 0 - all, 1 - only used, 2 - only unused
     * @param show "checkbox" for clear subClass
     */
    function nc_list_class_use($arrayClassID, $param_show = 0, $withdeleted = 1) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        $colspan = $withdeleted ? 5 : 4;

        if (!empty($arrayClassID)) {
            $arrayClassID = array_map("intval", $arrayClassID);
        }

        $Result = $db->get_results("
            SELECT Class.`Class_ID` AS ClassID,
                   Class.`Class_Name` AS ClassName,
                   Sub_Class.`Sub_Class_ID` AS SubClassID,
                   Sub_Class.`Sub_Class_Name` AS SubClassName,
                   Sub_Class.`EnglishName` AS SubClassKeyword,
                   Sub_Class.`Catalogue_ID` AS SubClassCatalogueID,
                   Subdivision.`Subdivision_Name` AS SubName,
                   Subdivision.`Subdivision_ID` AS SubID,
                   Subdivision.`Hidden_URL` AS SubURL
                FROM `Class`
                  LEFT JOIN `Sub_Class` ON Sub_Class.`Class_ID` = Class.`Class_ID`
                  LEFT JOIN `Subdivision` ON Subdivision.`Subdivision_ID` = Sub_Class.`Subdivision_ID`
                    WHERE " . (!empty($arrayClassID) ? "Class.`Class_ID` IN(" . join(",", $arrayClassID) . ")" : "Class.`System_Table_ID` = '0'
                      AND Class.`ClassTemplate` = '0'" ), ARRAY_A);

        $SQL = "SELECT s.Subdivision_ID,
                       c.Domain
                    FROM Subdivision as s,
                         Catalogue as c
                        WHERE s.Catalogue_ID = c.Catalogue_ID";

        $_domains = $db->get_results($SQL);
        $domains = array();
        foreach ($_domains as $row) {
            $domains[$row->Subdivision_ID] = $row->Domain;
        }

        $curClass = -1;

        if ($withdeleted)
            echo "<form action='index.php' name='del' method='post' id='del'>\n";

        echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr>\n" .
        "<table class='admin_table' width='100%'>\n" .
        "<tr>\n" .
        "<th width='30%'><font size='-1'>" . CONTROL_CLASS . "</th>\n" .
        "<th width='30%'><font size='-1'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION . "</th>\n" .
        "<th width='30%'><font size='-1'>" . CONTROL_CONTENT_SUBDIVISION_CLASS . "</th>\n" .
        "<th align='center'><font size='-1'>" . CONTROL_CONTENT_SUBCLASS_TOTALOBJECTS . "</th>\n" .
        ($withdeleted ? "<td align='center'><font size='-1'>" . REPORTS_STAT_CLASS_CLEAR . "</th>\n" : "") .
        "</tr>";

        $str = "";
        foreach ($Result as $row) {

            if ($curClass != $row['ClassID']) {
                switch ($param_show) {
                    case 0:
                        print($str);
                        break;
                    case 1:
                        if (!empty($useSubClass)) {
                            print($str);
                        }
                        break;
                    case 2:
                        if (empty($useSubClass)) {
                            print($str);
                        }
                        break;
                }

                //так сделано, чтобы для первого класса не выводился раздилитель
                //$str = $divider;

                $str = "<tr>";
                //$divider = "<tr><td colspan='" . $colspan . "' height='2px' bgcolor='#CCC'  style='padding: 0px; height:2px'></tr>";
                $curClass = $row['ClassID'];
                $db->query("SELECT COUNT(`Message_ID`), `Sub_Class_ID` FROM `Message" . $curClass . "` GROUP BY `Sub_Class_ID`");
                unset($countMes);
                if ($db->get_col(null, 1)) {
                    $countMes = array_combine((array) $db->get_col(null, 1), (array) $db->get_col(null, 0));
                }
                $db->query("SELECT COUNT(`Message_ID`), `Sub_Class_ID` FROM `Message" . $curClass . "` WHERE `Checked` = 0 GROUP BY `Sub_Class_ID`");

                unset($countMes_off);
                if ($db->get_col(null, 1)) {
                    $countMes_off = array_combine((array) $db->get_col(null, 1), (array) $db->get_col(null, 0));
                }
                $useSubClass = false;
                if ($row['SubClassID']) {
                    $useSubClass = true;
                }
                $str.= "<td><a href='../class/index.php?phase=4&amp;ClassID=" . $curClass . "' style='text-decoration:none'>" .
                        "<font color='#000000'>" . $curClass . ". " . $row['ClassName'] . "</font></a></td>";
            }
            else {
                $str.= "<tr><td><font size='-2'></td>";
            }

            if ($row['SubID']) {
                $scheme = $nc_core->catalogue->get_scheme_by_id($row['SubClassCatalogueID']);
                $temp1 = "<a href='{$scheme}://{$domains[$row['SubID']]}{$row['SubURL']}' style='text-decoration:underline' target='_blank'>" . $row['SubID'] . ". " . $row['SubName'] . "</a>";
                $temp2 = "<a href='{$scheme}://{$domains[$row['SubID']]}{$row['SubURL']}{$row['SubClassKeyword']}.html' style='text-decoration:underline' target='_blank'>" . $row['SubClassID'] . ". " . $row['SubClassName'] . "</a>";
            } else {
                $temp1 = $temp2 = "—";
            }

            $str .= "<td><font size='-2'>" . $temp1 . "</td>" .
                    "<td><font size='-2'>" . $temp2 . "</td>";

            $message_total = !empty($countMes[$row['SubClassID']]) ? (int)$countMes[$row['SubClassID']] : 0;
            $disabled_messages = !empty($countMes_off[$row['SubClassID']]) ? (int)$countMes_off[$row['SubClassID']] : 0;
            $str .= "<td align='center'>" . $message_total;
            if ($disabled_messages) {
                $str .= " (" . $disabled_messages . ")";
            }
            $str .= "</td>";
            if ($withdeleted) {
                $str.= "<td align='center'>" . ($message_total ? ("<input type='checkbox' value='" . $row['SubClassID'] . "' name='Delete" . $row['SubClassID'] . "'>") : "") . "</td>";
            }
            $str.= "</tr>\r\n";
        }

        switch ($param_show) {
            case 0:
                print($str);
                break;
            case 1:
                if ($useSubClass)
                    print($str);
                break;
            case 2:
                if (!$useSubClass)
                    print($str);
                break;
        }

        print "</table></td></tr></table>";

        if ($withdeleted) {
            print "<input type='hidden' name='phase' value='3'></form>\n";
            print $nc_core->token->get_input();
        }
    }

    /**
     * Show using class
     *
     * @param array | null arrayClassID, if !isset - all class
     * @param int 0 - all, 1 - only used, 2 - only unused
     */
    function nc_list_class_template_use($arrayClassID, $param_show = 0) {
        // system superior object
        $nc_core = nc_Core::get_object();

        // system db object
        if (is_object($nc_core->db))
            $db = &$nc_core->db;

        if (empty($arrayClassID)) {
            nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NOT_FOUND, "error");
            return false;
        } else {
            $arrayClassID = array_map("intval", $arrayClassID);
        }

        $Result = $nc_core->db->get_results(
            "SELECT Class.`Class_ID` AS ClassID,
                    Class.`Class_Name` AS ClassName,
                    Sub_Class.`Sub_Class_ID` AS SubClassID,
                    Sub_Class.`Sub_Class_Name` AS SubClassName,
                    Subdivision.`Subdivision_Name` AS SubName,
                    Subdivision.`Subdivision_ID` AS SubID
               FROM `Class`
                    LEFT JOIN `Sub_Class`
                        ON Sub_Class.`Class_ID` = Class.`ClassTemplate`
                        AND (Sub_Class.`Class_Template_ID` = Class.`Class_ID`
                            OR Sub_Class.`Edit_Class_Template` = Class.`Class_ID`
                            OR Sub_Class.`Admin_Class_Template` = Class.`Class_ID`)
                    LEFT JOIN `Subdivision`
                        ON Subdivision.`Subdivision_ID` = Sub_Class.`Subdivision_ID`
                WHERE " .
                    (!empty($arrayClassID)
                        ? "Class.`Class_ID` IN(" . join(",", $arrayClassID) . ")"
                        : "Class.`System_Table_ID` = '0'"
                    ) . "
                  AND Class.`ClassTemplate` != '0'", ARRAY_A);

        $curClass = -1;

        echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr>\n" .
        "<table class='admin_table' width='100%'>\n" .
        "<tr>\n" .
        "<th width='40%'>" . CONTROL_CLASS_CLASS_TEMPLATE . "</th>\n" .
        "<th width='30%'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION . "</th>\n" .
        "<th width='30%'>" . CONTROL_CONTENT_SUBDIVISION_CLASS . "</th>\n" .
        "</tr>";

        $str = "";
        foreach ($Result as $row) {

            if ($curClass != $row['ClassID']) {
                switch ($param_show) {
                    case 0:
                        print($str);
                        break;
                    case 1:
                        if ($useSubClass)
                            print($str);
                        break;
                    case 2:
                        if (!$useSubClass)
                            print($str);
                        break;
                }

                //так сделано, чтобы для первого класса не выводился раздилитель
                //$str = $divider;

                $str = "<tr>";
                //$divider = "<tr><td colspan='3' style='background:#CCCCCC; height:2px; padding:0px'></tr>";
                $curClass = $row['ClassID'];
                $useSubClass = false;
                if ($row['SubClassID'])
                    $useSubClass = true;
                $str.= "<td>" .
                        "<a href='" . $nc_core->ADMIN_PATH . "class/index.php?phase=4&amp;ClassID=" . $curClass . "' style='text-decoration:none'>" .
                        "<font color='#000000'>" . $curClass . ". " . $row['ClassName'] . "</font></a>" .
                        "</td>";
            }
            else {
                $str.= "<tr><td></td>";
            }

            if ($row['SubID']) {
                $temp1 = "<a href='" . $nc_core->ADMIN_PATH . "subdivision/index.php?phase=5&amp;SubdivisionID=" . $row['SubID'] . "' style='text-decoration:underline'>" . $row['SubID'] . ". " . $row['SubName'] . "</a>";
                $temp2 = "<a href='" . $nc_core->ADMIN_PATH . "subdivision/SubClass.php?phase=3&amp;SubClassID=" . $row['SubClassID'] . "&amp;SubdivisionID=" . $row['SubID'] . "' style='text-decoration:underline'>" . $row['SubClassID'] . ". " . $row['SubClassName'] . "</a>";
            } else {
                $temp1 = $temp2 = "&mdash;";
            }

            $str.= "<td>" . $temp1 . "</td>";
            $str.= "<td>" . $temp2 . "</td>";
            $str.= "</tr>";
        }

        switch ($param_show) {
            case 0:
                print($str);
                break;
            case 1:
                if ($useSubClass)
                    print($str);
                break;
            case 2:
                if (!$useSubClass)
                    print($str);
                break;
        }

        print "</table></td></tr></table>";
    }

    /**
     * Вывод формы для создания нового шаблона компонента
     *
     * @global object $db
     * @global object $UI_CONFIG
     *
     * @param int $class_id номер компонента, шаблон которого создается
     *
     * @return int 0
     */
    function nc_classtemplate_preadd_from($class_id) {
        global $UI_CONFIG;

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $class_id = intval($class_id);

        // доступные типы шаблонов
        $types = array(
                    'useful',
                    'inside_admin',
                    'admin_mode',
                    'rss',
                    'xml',
                    'title',
                    'trash',
                    'mobile',
                    'responsive',
                    'multi_edit',
                    'object_partial',
                );
        $exist_types = $db->get_col("SELECT DISTINCT `Type` FROM `Class` WHERE `ClassTemplate` ='" . $class_id . "' AND `Type` NOT IN ('useful', 'object_partial')");
        if ($exist_types) { $types = array_diff($types, $exist_types); }

        // определение компонентов, на базе которых можно создать
        $class_name = $db->get_var("SELECT CONCAT(`Class_ID`, '. ', `Class_Name`) FROM `Class` WHERE `Class_ID` = '" . $class_id . "'");
        $base = array('auto' => CONTROL_CLASS_COMPONENT_TEMPLATE_BASE_AUTO,
                'empty' => CONTROL_CLASS_COMPONENT_TEMPLATE_BASE_EMPTY);
        $base['class_' . $class_id] = $class_name;
        $templates = $db->get_results("SELECT `Class_ID` as `id`, CONCAT(`Class_ID`, '. ', `Class_Name`) as `name`
                                       FROM `Class` WHERE `ClassTemplate` = '" . $class_id . "'", ARRAY_A);
        if (!empty($templates))
            foreach ($templates as $k => $v) {
                $base['class_' . $v['id']] = $v['name'];
            }

        echo "<fieldset><legend>" . CONTROL_CLASS_COMPONENT_TEMPLATE_ADD_PARAMETRS . "</legend>\r\n";
        echo "<form action='index.php' method='post'>
                  <input type='hidden' name='fs' value='".+$_REQUEST['fs']."' />";

        $Type = $nc_core->input->fetch_get_post('Type');

        // тип шаблона компонента
        echo CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_CLASSTEMPLATE . ": <br/>\r\n";
        echo " <select name='Type' style='width: 250px;'>\r\n";
        foreach ($types as $v)
            echo "\t<option " . ( $Type === $v ? "selected='selected'" : NULL ) . " value='" . $v . "'>" . constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_" . strtoupper($v)) . "</option>\r\n";
        echo "</select><br/><br/>\r\n";

        // на основе..
        echo CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_BASE . ": <br/>\r\n";
        echo " <select name='base' style='width: 250px;'>\r\n";
        foreach ($base as $k => $v)
            echo "\t<option value='" . $k . "'>" . $v . "</option>\r\n";
        echo "</select>\r\n";

        echo $nc_core->token->get_input();
        echo "<input type='hidden' name='phase' value='141' />\r\n";
        echo "<input type='hidden' name='ClassID' value='" . $class_id . "' />\r\n";
        echo "</form>\r\n";
        echo "</fieldset>\r\n";

        $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                "action" => "mainView.submitIframeForm()"
        );

        return 0;
    }

    /**
     * Создание шаблона компонента
     *
     * @param int $class_id номер исходного класса
     * @param string $type тип шаблона: useful, rss, admin_mode, inside_admin, xml
     * @param string $base создать на основе компонента - class_XX, auto, empty
     *
     * @return int номер созданого шаблона
     */
    function nc_classtempalte_make($class_id, $type = 'useful', $base = 'auto') {
        $nc_core = nc_Core::get_object();

        $class_id = intval($class_id);
        $class_name = $nc_core->component->get_by_id($class_id, 'Class_Name');
        $File_Mode = false;
        $template = array();

		// создание шаблона на основе другого компонента
		if ( preg_match('/class_(\d+)/i', ($base == 'auto' ? 'class_' . $class_id : $base), $match) ) {
			$File_Mode = nc_get_file_mode('Class', $match[1]);
		}

		if ( !is_writable($nc_core->CLASS_TEMPLATE_FOLDER) ) {
			return false;
		}

        if ($type != 'useful' && $type != 'mobile')
            $class_name = constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_" . strtoupper($type));

        if ($type == 'mobile')
            $class_name = $class_name . " (" . constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_" . strtoupper($type)) . ")";

        if ($base == 'empty') {
            return $nc_core->component->add($class_name, CONTROL_CLASS_CLASS_TEMPLATE_GROUP, array(), $class_id, $type);
        }

        if ($base == 'auto' && in_array($type, array('rss', 'xml', 'trash', 'inside_admin'))) {
            $template = call_user_func('nc_classtemplate_make_' . $type, $class_id);
        }

        if ($base == 'auto' && in_array($type, array('useful', 'admin_mode', 'title', 'mobile'))) {
            $base = 'class_' . $class_id;
        }
        // создание шаблона на основе другого компонента
        if (preg_match('/class_(\d+)/i', $base, $match)) {
            if ($File_Mode) {
                $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
                $class_editor->load($match[1]);
                $class_editor->fill_fields();
                $template = array_merge($nc_core->component->get_by_id($match[1]), $class_editor->get_fields());
            }
            else {
                $template = $nc_core->component->get_by_id($match[1]);
            }
        }

        $template['Keyword'] = null;

        return $nc_core->component->add($class_name, CONTROL_CLASS_CLASS_TEMPLATE_GROUP, $template, $class_id, $type);
    }

    function nc_classtemplate_make_inside_admin($class_id) {
        $nc_core = nc_Core::get_object();
        $prefix = "<? require \$nc_parent_field_path; ?> \r\n";
        $prefix .= '<?= ($searchForm ? "
    <div id=\'nc_admin_filter\'>
      <fieldset>
        <legend>" . NETCAT_MODERATION_FILTER . "</legend>
        $searchForm
      </fieldset>
    </div>
    " : "" ); ?>';
        $record = "<? require \$nc_parent_field_path; ?>";
        $suffix = "<? require \$nc_parent_field_path; ?>";
        $full = "<? require \$nc_parent_field_path; ?>";
        $settings = "<? require \$nc_parent_field_path; ?>";
        $custom_settings = $nc_core->component->get_by_id($class_id, 'CustomSettingsTemplate');

        return array('FormPrefix' => $prefix, 'RecordTemplate' => $record, 'FormSuffix' => $suffix,
                'Settings' => $settings, 'RecordTemplateFull' => $full, 'CustomSettingsTemplate' => $custom_settings);
    }

    function nc_classtemplate_make_rss($class_id) {
        $component = new nc_Component($class_id);


        // поля, которые могут попасть в ленту
        $string_fields = $component->get_fields(NC_FIELDTYPE_STRING);
        $text_fields = $component->get_fields(NC_FIELDTYPE_TEXT);
        $date_fields = $component->get_fields(NC_FIELDTYPE_DATETIME);
        $file_fields = $component->get_fields(NC_FIELDTYPE_FILE);

        $File_Mode = $component->get_by_id($class_id, 'File_Mode');

        // префикс

        if ($File_Mode) {
            $prefix =
            '<?= "<?xml version=\'1.0\' encoding=\'{$nc_core->NC_CHARSET}\'?>"; ?>
                <?= "<?xml-stylesheet type=\'text/xsl\' href=\'/images/rss.xsl\'?>"; ?>
                    <rss version="2.0" xml:lang="ru-RU">
                        <channel>
                            <title><?= htmlspecialchars($system_env["ProjectName"], ENT_QUOTES); ?></title>
                            <link><?= nc_get_scheme(); ?>://<?= $_SERVER["HTTP_HOST"]; ?>/</link>
                            <description><?= htmlspecialchars(strip_tags($current_sub["Description"]), ENT_QUOTES); ?></description>
                            <language>ru-RU</language>
                            <copyright>Copyright <?= date("Y"); ?> <?= htmlspecialchars($system_env["ProjectName"], ENT_QUOTES); ?></copyright>
                            <lastBuildDate><?= gmdate("D, d M Y H:i:s", $nc_last_update); ?> GMT</lastBuildDate>
                            <generator>CMS Netcat</generator>
                            <category><?= htmlspecialchars(strip_tags($current_sub["Subdivision_Name"]), ENT_QUOTES); ?></category>
                            <managingEditor><?= $system_env["SpamFromEmail"]; ?> (<?= htmlspecialchars($system_env["SpamFromName"], ENT_QUOTES); ?>)</managingEditor>
                            <webMaster><?= $system_env["SpamFromEmail"]; ?> (<?= htmlspecialchars($system_env["SpamFromName"], ENT_QUOTES); ?>)</webMaster>
                            <ttl>30</ttl>';


        } else {
            $prefix = '<?xml version=\"1.0\" encoding=\"".$nc_core->NC_CHARSET."\"?>
                <?xml-stylesheet type=\"text/xsl\" href=\"/images/rss.xsl\"?>
                <rss version=\"2.0\" xml:lang=\"ru-RU\">
                    <channel>
                    <title>".htmlspecialchars($system_env[\'ProjectName\'], ENT_QUOTES)."</title>
                    <link>".nc_get_scheme()."://".$_SERVER[\'HTTP_HOST\']."/</link>
                    <description>".htmlspecialchars(strip_tags($current_sub[\'Description\']), ENT_QUOTES)."</description>
                    <language>ru-RU</language>
                    <copyright>Copyright ".date("Y")." ".htmlspecialchars($system_env[\'ProjectName\'], ENT_QUOTES)."</copyright>
                    <lastBuildDate>".gmdate("D, d M Y H:i:s", $nc_last_update)." GMT</lastBuildDate>
                    <generator>CMS Netcat</generator>
                    <category>".htmlspecialchars(strip_tags($current_sub[\'Subdivision_Name\']), ENT_QUOTES)."</category>
                    <managingEditor>".$system_env[\'SpamFromEmail\']." (".htmlspecialchars($system_env[\'SpamFromName\'], ENT_QUOTES).")</managingEditor>
                    <webMaster>".$system_env[\'SpamFromEmail\']." (".htmlspecialchars($system_env[\'SpamFromName\'], ENT_QUOTES).")</webMaster>
                    <ttl>30</ttl>';
        }
        // суффикс
        $suffix = '</channel>
             </rss>';

        // ищем поле для titl'a
        $title = "'" . NETCAT_MODERATION_TITLE . "'";
        if (!empty($string_fields)) {
            foreach ($string_fields as $v) {
                if (nc_preg_match('/(titl|caption|name|subject)/i', $v['name'])) {
                    $title = '$f_' . $v['name'];
                    break;
                }
            }
        }

        // ищем поле для description'a
        $description = "'" . NETCAT_MODERATION_DESCRIPTION . "'";
        if (!empty($text_fields)) {
            foreach ($text_fields as $v) {
                if (nc_preg_match('/(text|message|announce|description|content)/i', $v['name'])) {
                    $description = '$f_' . $v['name'];
                    break;
                }
            }
        }

        // ищем поле для даты
        $pubDate = '$f_Created';
        if (!empty($date_fields)) {
            foreach ($date_fields as $v) {
                if (nc_preg_match('/(date)/i', $v['name'])) {
                    $pubDate = '($f_' . $v['name'] . ' ? $f_' . $v['name'] . ' : $f_Created) ';
                    break;
                }
            }
        }

        // картинка
        $enclosure = "";
        if (!empty($file_fields)) {
            foreach ($file_fields as $v) {
                $enclosure .= '".( $f_' . $v['name'] . '  ? "<enclosure url=\"".nc_get_scheme()."://".$_SERVER[\'HTTP_HOST\'].$f_' . $v['name'] . '."\" length=\"$f_' . $v['name'] . '_size\" type=\"$f_' . $v['name'] . '_type\"  />" : "" )."';
                break;
            }
        }

        if($File_Mode) {
            $record =
            '<item>
                <title><?= htmlspecialchars(' . $title . '); ?></title>
                <link><?= nc_get_scheme(); ?>://<?= $_SERVER["HTTP_HOST"] . $fullLink; ?></link>
                <description><?= htmlspecialchars(' . $description . '); ?></description>
                <category><?= htmlspecialchars($current_sub["Subdivision_Name"]); ?></category>
                <pubDate><?= date(DATE_RSS, strtotime(' . $pubDate . ') ); ?></pubDate>
                ' . ($enclosure ? "<?= \"$enclosure\"; ?>": "") . '
                <guid isPermaLink="true"><?= nc_get_scheme(); ?>://<?= $_SERVER["HTTP_HOST"] . $fullLink; ?></guid>
            </item>';
        } else {
            $record = '<item>
                <title>".htmlspecialchars(' . $title . ')."</title>
                <link>".nc_get_scheme()."://".$_SERVER[\'HTTP_HOST\']."$fullLink</link>
                <description>".htmlspecialchars(' . $description . ')."</description>
                <category>".htmlspecialchars($current_sub[\'Subdivision_Name\'])."</category>
                <pubDate>".date(DATE_RSS, strtotime(' . $pubDate . ') )."</pubDate>
                ' . $enclosure . '
                <guid isPermaLink=\"true\">".nc_get_scheme()."://".$_SERVER[\'HTTP_HOST\']."$fullLink</guid>
            </item>';
        }



        // системные настройки
        $settings = '$nc_last_update = $db->get_var("SELECT MAX(UNIX_TIMESTAMP(`Created`)) FROM `Message".$classID."` WHERE `Sub_Class_ID` = \'".$cc."\' AND `Checked` = 1 ");';
        $settings .= "\r\nheader(\"Content-type: text/xml; charset=\".\$nc_core->NC_CHARSET);";

        $rss_template['ClassDescription'] = CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_RSS;
        $rss_template['RecordsPerPage'] = 10;
        $rss_template['FormPrefix'] = $prefix;
        $rss_template['RecordTemplate'] = $record;
        $rss_template['FormSuffix'] = $suffix;
        $rss_template['Settings'] = $File_Mode ? "<?php\n$settings\n?>" : $settings;

        return $rss_template;
    }

    function nc_classtemplate_make_xml($class_id) {
        $component = new nc_Component($class_id);
        $File_Mode = $component->get_by_id($class_id, 'File_Mode');
        // поля, которые могут попасть в ленту
        $fields = $component->get_fields();

        // префикс
        if ($File_Mode) {
            $prefix = '<?= "<?xml version=\'1.0\' encoding=\'{$nc_core->NC_CHARSET}\'?>"; ?>';
        } else {
            $prefix = '<?xml version=\"1.0\" encoding=\"".$nc_core->NC_CHARSET."\"?>';
        }

        $prefix .= "\r\n<messages>";

        if ($File_Mode) {
            $record = '<message id="<?= $f_RowID; ?>">';
        } else {
            $record = '<message id=\"".$f_RowID."\">';
        }
        $record .= "\r\n";

        if (!empty($fields)) {
            foreach ($fields as $v) {
                if (in_array($v['type'], array(1, 2, 3, 4, 5, 7))) {
                    if ($File_Mode) {
                        $record .= "\t" . '<' . strtolower($v['name']) . '><?= htmlspecialchars($f_' . $v['name'] . '); ?></' . strtolower($v['name']) . '>' . "\r\n";
                    } else {
                        $record .= "\t" . '<' . strtolower($v['name']) . '>".htmlspecialchars($f_' . $v['name'] . ')."</' . strtolower($v['name']) . '>' . "\r\n";
                    }
                } else if ($v['type'] == NC_FIELDTYPE_FILE) {
                    if ($File_Mode) {
                        $record .= "\t" . '<' . strtolower($v['name']) . '><?= htmlspecialchars($f_' . $v['name'] . '_name)."</' . strtolower($v['name']) . '>' . "\r\n";
                    } else {
                        $record .= "\t" . '<' . strtolower($v['name']) . '>".htmlspecialchars($f_' . $v['name'] . '_name)."</' . strtolower($v['name']) . '>' . "\r\n";
                    }
                }
            }
        }

        $record .= '</message>' . "\r\n";

        $suffix = "</messages>\r\n";

        $settings = "ob_end_clean(); \r\nheader(\"Content-type: text/xml\");";
        return array(
                'FormPrefix' => $prefix,
                'RecordTemplate' => $record,
                'FormSuffix' => $suffix,
                'Settings' => $File_Mode ? "<?php\n$settings\n?>" : $settings);
    }

    function nc_classtemplate_make_trash($class_id) {
        $component = new nc_Component($class_id);
        $File_Mode = nc_get_file_mode('Class', $class_id);
        // поля, которые могут попасть в ленту
        $fields = $component->get_fields();

        $string_fields = $component->get_fields(NC_FIELDTYPE_STRING);
        $text_fields = $component->get_fields(NC_FIELDTYPE_TEXT);

        // ищем поле для titl'a
        $title = '';
        if (!empty($string_fields))
            foreach ($string_fields as $v) {
                if (nc_preg_match('/(titl|caption|name|subject)/i', $v['name'])) {
                    $title = 'f_' . $v['name'];
                    break;
                }
            }

        if (empty($title) && !empty($string_fields)) {
            $title = 'f_' . $string_fields[0]['name'];
        } elseif (empty($title) && empty($string_fields) && !empty($text_fields)) {
            $title = 'f_' . $text_fields[0]['name'];
        } elseif (empty($title) && !empty($fields)) {
            $title = 'f_' . $fields[0]['name'] . ($fields[0]['type'] == NC_FIELDTYPE_FILE ? '_name' : NULL);
        } elseif (empty($title)) {
            $title = 'f_RowID';
        }
        $record = $File_Mode ? '<?php echo "' : '';
        $record .= '$f_AdminButtons $' . $title . "<br /><br />\r\n";
        $record .= $File_Mode ? '"; ?>' : '';
        return array('RecordTemplate' => $record);
    }

    class ui_config_class extends ui_config {

        function __construct($active_tab = 'edit', $class_id = 0, $class_group = '') {

            global $MODULE_VARS;
            
            $nc_core = nc_Core::get_object();
            $db = $nc_core->db;
            $fs_suffix = +$_REQUEST['fs'] ? '_fs' : '';

            $class_id = intval($class_id);
            $class_group = $db->escape($class_group);
            $active_toolbar = '';
            if ($class_id) {
                $this->headerText = $db->get_var("SELECT Class_Name FROM Class WHERE Class_ID = $class_id");
            } elseif ($class_group) {
                $this->headerText = $db->get_var("SELECT Class_Group FROM Class WHERE md5(Class_Group) = '" . $class_group . "' GROUP BY Class_Group");
            } else {
                $this->headerText = SECTION_INDEX_DEV_CLASSES;
            }

            if (in_array($active_tab, array('customadd', 'customedit', 'customsearch', 'customdelete'))) {
                $active_toolbar = $active_tab;
                $active_tab = 'classaction';
            }

            if ($active_tab == 'add') {
                $this->tabs = array(
                        array('id' => 'add',
                                'caption' => CONTROL_CLASS_ADD_ACTION,
                                'location' => "dataclass$fs_suffix.add($class_group)"));
            } elseif ($active_tab == 'addtemplate') {
                $this->tabs = array(
                        array('id' => 'addtemplate',
                                'caption' => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                                'location' => "dataclass$fs_suffix.addtemplate(" . $class_id . ")"));
            } elseif ($active_tab == 'delete') {
                $this->tabs = array(
                        array('id' => 'delete',
                                'caption' => CONTROL_CLASS_DELETE,
                                'location' => "dataclass$fs_suffix.delete($class_group)"));
            } elseif ($active_tab == 'import') {
                $this->tabs = array(
                        array('id' => 'import',
                                'caption' => CONTROL_CLASS_IMPORT,
                                'location' => "dataclass$fs_suffix.import($class_group)"));
            } elseif ($active_tab == 'convert') {
                $this->tabs = array(
                        array('id' => 'convert',
                                'caption' => 'convert',
                                'location' => "dataclass$fs_suffix.convert(" . $class_id . ")"));
            } else {
                $this->tabs = array(
                        array('id' => 'info',
                                'caption' => CLASS_TAB_INFO,
                                'location' => "dataclass$fs_suffix.info($class_id)"),
                        array('id' => 'edit',
                                'caption' => CLASS_TAB_EDIT,
                                'location' => "dataclass$fs_suffix.edit($class_id)"),
                        array('id' => 'classaction',
                                'caption' => CLASS_TAB_CUSTOM_ACTION,
                                'location' => "dataclass$fs_suffix.customadd($class_id)"),
                        array('id' => 'fields',
                                'caption' => CONTROL_CLASS_FIELDS,
                                'location' => "dataclass$fs_suffix.fields($class_id)"),
                        array('id' => 'custom',
                                'caption' => CONTROL_CLASS_CUSTOM,
                                'location' => "dataclass$fs_suffix.custom($class_id)"));
            }

            // Активная вкладка - "Шаблоны действий"
            if ($active_tab == 'classaction') {
                $this->toolbar = array(
                        array('id' => 'customadd',
                                'caption' => CLASS_TAB_CUSTOM_ADD,
                                'location' => "dataclass$fs_suffix.customadd($class_id)",
                                'group' => "grp1"),
                        array('id' => 'customedit',
                                'caption' => CLASS_TAB_CUSTOM_EDIT,
                                'location' => "dataclass$fs_suffix.customedit($class_id)",
                                'group' => "grp1"),
                        array('id' => 'customdelete',
                                'caption' => CLASS_TAB_CUSTOM_DELETE,
                                'location' => "dataclass$fs_suffix.customdelete($class_id)",
                                'group' => "grp1"),
                        array('id' => 'customsearch',
                                'caption' => CLASS_TAB_CUSTOM_SEARCH,
                                'location' => "dataclass$fs_suffix.customsearch($class_id)",
                                'group' => "grp1"));
            }

            $this->activeTab = $active_tab;
            $this->activeToolbarButtons[] = $active_toolbar;

            if ($active_tab == 'add' || $active_tab == 'import') {
                $this->locationHash = "#dataclass.$active_tab($class_group)";
            } elseif ($active_tab == 'delete') {
                // иначе сбрасывается
            } else {
                if ($active_tab == 'classaction') {
                    $this->locationHash = "#dataclass.$active_toolbar($class_id)";
                } else {
                    $this->locationHash = "#dataclass.$active_tab($class_id)";
                }

                $this->treeSelectedNode = "dataclass-{$class_id}";
            }

            $this->treeMode = 'dataclass' . $fs_suffix;
        }

        function updateTreeClassNode($class_id, $class_name) {

            $this->treeChanges['updateNode'][] = array("nodeId" => "sub-$node_id",
                    "name" => "$node_id. $node_name");
        }

    }

    class ui_config_class_template extends ui_config {

        function __construct($active_tab = 'edit', $class_id = 0, $base_class = '') {

            global $db, $nc_core;
            $class_id = intval($class_id);
            $type = '';

            $sys = nc_Core::get_object()->db->get_var("SELECT System_Table_ID FROM Class WHERE Class_ID = " .($active_tab == 'add' ? $base_class : $class_id));
            $suffix = +$_REQUEST['fs'] ? '_fs' : '';
            $active_toolbar = '';

            if ($class_id) {
                $this->headerText = $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '" . $class_id . "'");
                $type = $db->get_var("SELECT `Type` FROM `Class` WHERE `Class_ID` = '" . $class_id . "' ");
            } else {
                $this->headerText = SECTION_INDEX_DEV_CLASS_TEMPLATES;
            }

            if (in_array($active_tab, array('customadd', 'customedit', 'customsearch', 'customsubscribe', 'customdelete'))) {
                $active_toolbar = $active_tab;
                $active_tab = 'classaction';
            }

            if ($active_tab == 'add') {
                $this->tabs = array(
                        array(
                                'id' => 'add',
                                'caption' => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                                'location' => "classtemplate.add(" . $base_class . ")"
                        )
                );
            } elseif ($active_tab == 'delete') {
                $this->tabs = array(
                        array(
                                'id' => 'delete',
                                'caption' => CLASS_TEMPLATE_TAB_DELETE,
                                'location' => "classtemplate.delete(" . $class_id . ($base_class ? "," . $base_class : "") . ")"
                        )
                );
            } else {
                $this->tabs = array(
                        array(  'id' => 'info',
                                'caption' => CLASS_TEMPLATE_TAB_INFO,
                                'location' => "classtemplate$suffix.info(" . $class_id . ")"
                        ),
                        array(
                                'id' => 'edit',
                                'caption' => CLASS_TEMPLATE_TAB_EDIT,
                                'location' => "classtemplate$suffix.edit(" . $class_id . ")"
                        ));

                if ($type !== 'rss' && $type !== 'xml' && $type !== 'object_partial')
                    $this->tabs[] = array(
                            'id' => 'classaction',
                            'caption' => CLASS_TAB_CUSTOM_ACTION,
                            'location' => "classtemplate$suffix.classaction(" . $class_id . ")"
                    );

                if ($type === 'useful' || $type === 'title' || $type === 'mobile' || $type === 'object_partial')
                // пользовательские настройки
                    $this->tabs[] = array(
                            'id' => 'custom',
                            'caption' => CONTROL_CLASS_CUSTOM,
                            'location' => "classtemplate$suffix.custom(" . $class_id . ")"
                    );
            }

            // Активная вкладка - "Шаблоны действий"
            if ($active_tab == 'classaction') {
                $this->toolbar = array(
                        array(
                                'id' => 'customadd',
                                'caption' => CLASS_TAB_CUSTOM_ADD,
                                'location' => "classtemplate$suffix.customadd(" . $class_id . ")",
                                'group' => "grp1"
                        ),
                        array(
                                'id' => 'customedit',
                                'caption' => CLASS_TAB_CUSTOM_EDIT,
                                'location' => "classtemplate$suffix.customedit(" . $class_id . ")",
                                'group' => "grp1"
                        ),
                        array(
                                'id' => 'customdelete',
                                'caption' => CLASS_TAB_CUSTOM_DELETE,
                                'location' => "classtemplate$suffix.customdelete(" . $class_id . ")",
                                'group' => "grp1"
                        ),
                        array(
                                'id' => 'customsearch',
                                'caption' => CLASS_TAB_CUSTOM_SEARCH,
                                'location' => "classtemplate$suffix.customsearch(" . $class_id . ")",
                                'group' => "grp1"
                        )
                );

            }



            $this->activeTab = $active_tab;
            $this->activeToolbarButtons[] = $active_toolbar;

            if ($active_tab == 'add') {
                $this->locationHash = "#classtemplate." . $active_tab . "(" . $base_class . ")";
            } elseif ($active_tab == 'delete') {
            } else {
                if ($active_tab == 'classaction') {
                    $this->locationHash = "#classtemplate." . $active_toolbar . "(" . $class_id . ")";
                } else {
                    $this->locationHash = "#classtemplate." . $active_tab . "(" . $class_id . ")";
                }
                $this->treeSelectedNode = "classtemplate-" . $class_id;
            }
            $this->treeMode = $sys ? 'systemclass' : 'dataclass';
            $this->treeMode .= $suffix;
        }

        function updateTreeClassNode($class_id, $class_name) {

            $this->treeChanges['updateNode'][] = array(
                    "nodeId" => "sub-" . $node_id,
                    "name" => $node_id . ". " . $node_name
            );
        }

    }

    class ui_config_class_templates extends ui_config {

        function __construct($active_tab = 'edit', $class_id) {

            $this->headerText = CONTROL_CLASS_CLASS_TEMPLATES;
            $this->tabs = array(
                    array(
                            'id' => 'edit',
                            'caption' => CONTROL_CLASS_CLASS_TEMPLATES,
                            'location' => "classtemplates.edit(" . $class_id . ")"
                    )
            );

            $sys = nc_Core::get_object()->db->get_var("SELECT System_Table_ID FROM Class WHERE Class_ID = " . $class_id);

            $this->activeTab = $active_tab;
            $suffix = +$_REQUEST['fs'] ? '_fs' : '';
            $this->locationHash = "classtemplates.edit(" . $class_id . ")";
            $this->treeMode = $sys ? 'systemclass' : 'dataclass';
            $this->treeMode .= $suffix;
            $this->treeSelectedNode = "classtemplates-" . $class_id;
        }

    }

    class ui_config_class_group extends ui_config {

        function __construct($active_tab = 'edit', $class_group) {

            global $db;
            $class_group = $db->escape($class_group);

            $this->headerText = $db->get_var("SELECT Class_Group FROM Class WHERE md5(Class_Group) = '$class_group' GROUP BY Class_Group");
            $this->tabs = array(
                    array('id' => 'edit',
                            'caption' => CONTROL_CLASS_CLASS_GROUPS,
                            'location' => "classgroup.edit($class_group)")
            );

            $this->activeTab = $active_tab;
            $this->locationHash = "classgroup.edit($class_group)";
            $this->treeMode = 'dataclass' . (+$_REQUEST['fs'] ? '_fs' : '');
            $this->treeSelectedNode = "group-$class_group";
        }

    }

    class ui_config_classes extends ui_config {

        function __construct($active_tab = 'dataclass.list') {
            $this->headerText = SECTION_CONTROL_CLASS;
            $this->tabs = array(
                    array(
                            'id' => 'dataclass.list',
                            'caption' => SECTION_CONTROL_CLASS,
                            'location' => "dataclass.list()"));
            $this->activeTab = $active_tab;
            $this->locationHash = "#dataclass.list()";
            $this->treeMode = 'dataclass' . (+$_REQUEST['fs'] ? '_fs' : '');
            $this->treeSelectedNode = "dataclass.list()";
        }

    }

    class ui_config_system_class extends ui_config {

        function __construct($active_tab = 'edit', $system_class_id = 0) {
            $nc_core = nc_Core::get_object();
            $db = $nc_core->db;
            if (!isset($_REQUEST['fs'])) {
                $_REQUEST['fs'] = null;
            }
            $suffix = +$_REQUEST['fs'] ? '_fs' : '';

            $system_class_id = +$system_class_id;
            $system_class = $db->get_row("SELECT a.System_Table_ID, a.System_Table_Rus_Name, b.Class_ID, IF(b.AddTemplate<>'' OR b.AddCond<>'' OR b.AddActionTemplate<>'',1,0) AS IsAdd, IF(b.EditTemplate<>'' OR b.EditCond<>'' OR b.EditActionTemplate<>'' OR b.CheckActionTemplate<>'' OR b.DeleteActionTemplate<>'',1,0) AS IsEdit, IF(b.SearchTemplate<>'' OR b.FullSearchTemplate<>'',1,0) AS IsSearch, IF(b.SubscribeTemplate<>'' OR b.SubscribeCond<>'',1,0) AS IsSubscribe FROM System_Table AS a LEFT JOIN Class AS b ON a.System_Table_ID=b.System_Table_ID WHERE a.System_Table_ID = '" . $system_class_id . "'", ARRAY_A);

            $this->headerText = constant($system_class["System_Table_Rus_Name"]);

            if ($system_class["Class_ID"] || $system_class['System_Table_ID']) {
                if ($system_class_id == 3 && $nc_core->modules->get_by_keyword('auth', 0)) {
                    $this->tabs[] = array(
                            'id' => 'edit',
                            'caption' => CLASS_TAB_EDIT,
                            'location' => "systemclass$suffix.edit(" . $system_class['System_Table_ID'] . ")");

                    $this->tabs[] = array(
                            'id' => 'customadd',
                            'caption' => CLASS_TAB_CUSTOM_ADD,
                            'location' => "systemclass$suffix.customadd(" . $system_class['System_Table_ID'] . ")");

                    $this->tabs[] = array(
                            'id' => 'customedit',
                            'caption' => CLASS_TAB_CUSTOM_EDIT,
                            'location' => "systemclass$suffix.customedit(" . $system_class['System_Table_ID'] . ")");

                    $this->tabs[] = array(
                            'id' => 'customsearch',
                            'caption' => CLASS_TAB_CUSTOM_SEARCH,
                            'location' => "systemclass$suffix.customsearch(" . $system_class['System_Table_ID'] . ")");
                }
                $this->tabs[] = array(
                        'id' => 'fields',
                        'caption' => CONTROL_CLASS_FIELDS,
                        'location' => "systemclass$suffix.fields(" . $system_class['System_Table_ID'] . ")");
            }
            $this->activeTab = $active_tab;
            $this->locationHash = "#systemclass.$active_tab(" . $system_class['System_Table_ID'] . ")";

            $this->treeMode = 'systemclass' . (+$_REQUEST['fs'] ? '_fs' : '');
            $this->treeSelectedNode = "systemclass-" . $system_class['System_Table_ID'];
        }

    }

    class ui_config_system_classes extends ui_config {
        function __construct($active_tab = 'systemclass.list') {
            $this->headerText = SECTION_CONTROL_CLASS;
            $this->tabs = array(
                    array(
                            'id' => 'systemclass.list',
                            'caption' => SECTION_SECTIONS_OPTIONS_SYSTEM,
                            'location' => "systemclass.list"));
            $this->activeTab = $active_tab;
            $this->treeMode = 'systemclass' . (+$_REQUEST['fs'] ? '_fs' : '');
            $this->treeSelectedNode = "systemclass.list";
        }
    }

    function print_bind() {
        return null;
    }

    function print_resizeblock($textarea_id) {
        return null;
    }


    function ClassInformation($ClassID, $action, $phase) {
        global $ROOT_FOLDER, $ClassGroup, $ADMIN_PATH, $UI_CONFIG;
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $ClassID = +$ClassID;

        $select = "SELECT * FROM `Class` WHERE `Class_ID` = '" . $ClassID . "'";

        /** @var stdClass $row */
        $row = $db->get_row($select);

        if (!$row) {
            nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
        }

        $component = new nc_Component($row->ClassTemplate ?: $row->Class_ID);
        $component_string_fields = $component->get_fields(NC_FIELDTYPE_STRING) ?: array();
        $component_multipurpose_templates = $component->get_multipurpose_templates_for_component($row->ClassTemplate ?: $row->Class_ID);

        $fieldsets = new nc_admin_fieldset_collection();

        ob_start();

        ?>

        <script type="text/javascript">
            function refreshTree(){
                var newClassGroup = $nc("#Class_Group option:selected").data('md5');
                var tree = parent.document.getElementById('treeIframe').contentWindow.tree;

                if (classGroup != newClassGroup) {
                    tree.moveNode('dataclass-<?=$ClassID?>','inside','group-'+newClassGroup);
                }
            }
        </script>

        <form method='post' id='ClassForm' action='<?= $action ?>' onsubmit="refreshTree();">
            <input type='hidden' name='ClassID' value='<?= $ClassID ?>' />
            <input type='hidden' name='phase' value='<?= $phase ?>' />
            <input type='hidden' name='action_type' value='1' />
            <input type='hidden' name='fs' value='<?= $row->File_Mode; ?>' />

            <?= $nc_core->token->get_input(); ?>
        <?

        $fieldsets->set_prefix(ob_get_clean())->set_suffix("</form>");

        $fieldsets->new_fieldset('main_info', CONTROL_CLASS_CLASSFORM_MAININFO);
        ob_start();
        ?>
            <div id='maininfoOn'>
                <?

                echo nc_admin_input(CONTROL_CLASS_CLASS_NAME, 'Class_Name', $row->Class_Name, 50);

                if ($row->File_Mode) {
                    echo nc_admin_input(CONTROL_CLASS_CLASS_KEYWORD, 'Keyword', $row->Keyword, 50, '', "maxlength='" . nc_component::MAX_KEYWORD_LENGTH . "'");
                }

                if (!$row->ClassTemplate) {
                    echo "<div class='inf_block'><label>" . CONTROL_CLASS_CLASS_MAIN_CLASSTEMPLATE_LABEL . ":</label><br/>";
                    echo "<select name='Main_ClassTemplate_ID' style='width:320px; margin-right: 5px;'>";
                    $default_field_selected_attribute = !$row->Main_ClassTemplate_ID ? 'selected' : '';
                    echo "<option $default_field_selected_attribute value='0'>{$row->Class_ID}. {$row->Class_Name}</option>";
                    foreach ($component_multipurpose_templates as $multipurpose_template) {
                        $field_selected_attribute = $multipurpose_template['Class_ID'] === $row->Main_ClassTemplate_ID ? 'selected' : '';
                        echo "<option $field_selected_attribute value=\"$multipurpose_template[Class_ID]\">$multipurpose_template[Class_ID]. $multipurpose_template[Class_Name]</option>";
                    }
                    echo '</select></div>';
                }

                if ($row->Type !== 'object_partial') {
                    echo "<div class='inf_block'><label>" . CONTROL_OBJECT_NAME_LABEL . ":</label><br/>";
                    echo "<select name='ObjectName' style='width:320px; margin-right: 5px;'>";
                    $default_field_selected_attribute = !$row->ObjectName ? 'selected' : '';
                    echo "<option $default_field_selected_attribute value=''>" . CONTROL_OBJECT_NAME_NOT_SELECTED . '</option>';
                    foreach ($component_string_fields as $field) {
                        $field_selected_attribute = $field['name'] === $row->ObjectName ? 'selected' : '';
                        echo "<option $field_selected_attribute value=\"$field[name]\">[$field[name]] - $field[description]</option>";
                    }
                    echo '</select></div>';

                    echo nc_admin_input(CONTROL_OBJECT_NAME_ACC, 'ObjectNameSingular', $row->ObjectNameSingular, 50);
                    echo nc_admin_input(CONTROL_OBJECT_NAME_ACC_PLURAL, 'ObjectNamePlural', $row->ObjectNamePlural, 50);
                    echo nc_admin_input(CONTROL_OBJECT_NAME_GEN, 'ObjectNameSingularGenitive', $row->ObjectNameSingularGenitive, 50);
                }

                // if not component template - show groups
                if (!($row->ClassTemplate || $phase == 15 || $phase == 17)) {
                    $classGroups = $db->get_col("SELECT DISTINCT `Class_Group` FROM `Class`");
                    if (!empty($classGroups)) {
                        echo "<div class='inf_block'><label>" . CONTROL_USER_GROUP . "</label>:<br /><select name='Class_Group' id='Class_Group' style='width:320px; margin-right: 5px;'>\n";
                        foreach ($classGroups as $Class_Group) {
                            if ($row->Class_Group == $Class_Group) {
                                echo("\t<option value='" . $Class_Group . "' data-md5='" . md5($Class_Group) . "' selected='selected'>" . $Class_Group . "</option>\n");
                            } else {
                                echo("\t<option value='" . $Class_Group . "' data-md5='" . md5($Class_Group) . "'>" . $Class_Group . "</option>\n");
                            }
                        }
                        echo "</select></div>";
                    }
                    unset($classGroups);

                    echo nc_admin_input(CONTROL_CLASS_NEWGROUP, 'Class_Group_New', isset($row->Class_Group_New) ? $row->Class_Group_New : '', 50);

                    // Переключатель "Служебный компонент"
                    echo "<div class='nc-form-checkbox-block'>" .
                         "<input type='hidden' name='IsAuxiliary' value='0'>" .
                         "<label>" .
                         "<input type='checkbox' name='IsAuxiliary' value='1'" . ($row->IsAuxiliary ? ' checked' : '') . "> " .
                        CONTROL_CLASS_AUXILIARY_SWITCH .
                         "</label></div>";
                } else {
                    echo "<input type='hidden' name='Class_Group' value='" . CONTROL_CLASS_CLASS_TEMPLATE_GROUP . "'>";
                }

                if ($row->Type === 'object_partial') {
                    echo "<input type='hidden' name='DisableBlockMarkup' value='0'>\n";
                    echo "<input type='hidden' name='DisableBlockListMarkup' value='0'>\n";
                    echo "<input type='hidden' name='IsOptimizedForMultipleMode' value='1'>\n";
                } else {
                    nc_print_component_disable_block_markup_field($row->DisableBlockMarkup, $row->DisableBlockListMarkup);

                    // Переключатель "Оптимизирован для использования в качестве «этажа» страницы"
                    echo "<div class='nc-form-checkbox-block'>" .
                         "<input type='hidden' name='IsOptimizedForMultipleMode' value='0'>" .
                         "<label>" .
                         "<input type='checkbox' name='IsOptimizedForMultipleMode' value='1'" .
                         ($row->IsOptimizedForMultipleMode ? ' checked' : '') . "> " .
                         ($row->ClassTemplate ?
                            CONTROL_CLASS_TEMPLATE_MULTIPLE_MODE_SWITCH :
                            CONTROL_CLASS_MULTIPLE_MODE_SWITCH
                         ) . "</label></div>";
                }

                nc_print_component_multipurpose_fields($row->IsMultipurpose, $row->CompatibleFields);

                ?>

                <script type="text/javascript">
                    classGroup = "<?= md5($row->Class_Group); ?>";
                </script>
            </div>
            <?

            $fieldsets->main_info->add(ob_get_clean());

            if ($nc_core->modules->get_by_keyword("cache")) {
                $fieldsets->new_fieldset('cache_info', CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_CACHE);
                ob_start();

                ?>
                <div id='cacheinfoOn'>
                    <?

                    $CacheForUser = $db->get_var("SELECT `CacheForUser` FROM `Class` WHERE `Class_ID` = ".$ClassID);

                    ?>
                    <table border='0' cellpadding='0' cellspacing='0' width='99%'>
                        <tr>
                            <td style='border: none;'>
                                <?= CONTROL_CLASS_CACHE_FOR_AUTH ?>*:<br/>
                                <select name='CacheForUser' style='width:320px; margin-right: 5px;'>
                                    <option value='0'<?= (!$CacheForUser ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_NONE ?></option>
                                    <option value='1'<?= ($CacheForUser == 1 ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_USER ?></option>
                                    <option value='2'<?= ($CacheForUser == 2 ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_GROUP ?></option>
                                </select><br/>
                                * <?= CONTROL_CLASS_CACHE_FOR_AUTH_DESCRIPTION ?>
                            </td>
                        </tr>
                    </table>
                </div>
            <?

            $fieldsets->cache_info->add(ob_get_clean());
        }

        return $fieldsets->to_string();
    }

 /**
  * Выводит поле для переключения свойства компонента DisableBlockMarkup
  * @param int|bool $is_block_markup_disabled
  * @param int|bool $is_list_markup_disabled
  */
function nc_print_component_disable_block_markup_field($is_block_markup_disabled, $is_list_markup_disabled) {
    ?>
    <div class='nc-form-checkbox-block'>
        <input type='hidden' name='DisableBlockMarkup' value='0'>
        <label>
            <input type='checkbox' name='DisableBlockMarkup' value='1'
            <?= ($is_block_markup_disabled ? 'checked' : ''); ?>>
            <?= CONTROL_CLASS_BLOCK_MARKUP_SWITCH; ?>
        </label>
        <?php
        nc_print_status(CONTROL_CLASS_BLOCK_MARKUP_SWITCH_WARNING, 'info');
        ?>
    </div>
    <div class='nc-form-checkbox-block'>
        <input type='hidden' name='DisableBlockListMarkup' value='0'>
        <label>
            <input type='checkbox' name='DisableBlockListMarkup' value='1'
            <?= ($is_list_markup_disabled ? 'checked' : ''); ?>>
            <?= CONTROL_CLASS_BLOCK_LIST_MARKUP_SWITCH; ?>
        </label>
    </div>

    <script>
    $nc(function() {
        $nc(':checkbox[name="DisableBlockMarkup"]').on('change', function() {
            $nc(this).closest('.nc-form-checkbox-block').find('.nc-alert').toggleClass('nc--hide', !this.checked);
            $nc(':checkbox[name="DisableBlockListMarkup"]').closest('label').toggle(!this.checked);
            $nc('textarea[name="SiteStyles"]').closest('tr').toggle(!this.checked);
            $nc(window).resize(); // нужно для правильного размера CodeMirror для SiteStyles
        }).change();
    });
    </script>
    <?php
}

 /**
  * Выводит поля для управления свойствами компонента IsMultipurpose и CompatibleFields
  * @param int|bool $is_multipurpose
  * @param string $compatible_fields
  */
function nc_print_component_multipurpose_fields($is_multipurpose, $compatible_fields = '') {
    ?>
    <div class='nc-form-checkbox-block'>
        <input type='hidden' name='IsMultipurpose' value='0'>
        <label for='IsMultipurpose'>
            <input id='IsMultipurpose' type='checkbox' name='IsMultipurpose' value='1'
            <?= ($is_multipurpose ? 'checked' : ''); ?>>
            <?= CONTROL_CLASS_IS_MULTIPURPOSE_SWITCH; ?>
        </label>
    </div>
    <div class='nc-field'>
        <label for='CompatibleFields'><?= CONTROL_CLASS_COMPATIBLE_FIELDS; ?></label>
        <textarea id='CompatibleFields' name='CompatibleFields' rows='10' wrap='OFF' cols='60'><?= htmlspecialchars($compatible_fields, ENT_QUOTES); ?></textarea>
    </div>
    <script>
    $nc(function() {
        $nc(':checkbox[name="IsMultipurpose"]').on('change', function() {
            $nc(this).closest('.nc-form-checkbox-block').next('.nc-field').toggleClass('nc--hide', !this.checked);
            $nc(window).resize(); // нужно для правильного размера CodeMirror для SiteStyles
        }).change();
    });
    </script>
    <?php
}
