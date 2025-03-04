<?php

function nc_class_wizard_start($class_name = '', $class_group = '', $class_group_new = '') {

    global $db, $UI_CONFIG, $nc_core;

    echo "<form method='post' action='wizard_class.php'>";
    echo WIZARD_CLASS_FORM_START_TEMPLATE_TYPE . ":<br><select name='Class_Type'>";
    echo("\t<option value='1'>" . WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_SINGLE . "</option>\n");
    echo("\t<option value='2'>" . WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_LIST . "</option>\n");
    echo("\t<option value='3'>" . WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_SEARCH . "</option>\n");
    echo("\t<option value='4'>" . WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_FORM . "</option>\n");
    echo "</select><br><br>";

    echo CONTROL_CLASS_CLASS_NAME . ":<br>" . nc_admin_input_simple('Class_Name', ($class_name ? $class_name : CONTROL_CLASS_NEWCLASS), 50) . "<br><br>";
    echo CONTROL_USER_GROUP . ":<br><select name='Class_Group'>";

    foreach ($db->get_col("SELECT DISTINCT Class_Group FROM Class") as $Class_Group) {
        if ($class_group == $Class_Group)
            echo("\t<option value='" . $Class_Group . "' selected>" . $Class_Group . "</option>\n");
        else
            echo("\t<option value='" . $Class_Group . "'>" . $Class_Group . "</option>\n");
    }

    echo "</select>";
    echo "&nbsp;" . CONTROL_CLASS_NEWGROUP . ":&nbsp;" . nc_admin_input_simple('Class_Group_New', $class_group_new, 8, '', "maxlength='64'") . "<br><br>";
    echo "<input type='hidden' name='phase' value='2'>";
    echo "<input type='hidden' name='fs' value='1'>";
    echo "<input type='submit' class='hidden'>";
    echo $nc_core->token->get_input();
    echo "</form>";

    $UI_CONFIG->actionButtons[] = array("id" => "submit", "caption" => WIZARD_CLASS_BUTTON_NEXT_TO_ADDING_FIELDS, "action" => "mainView.submitIframeForm()");

    return true;
}

/**
 * Print hidden filed and show button "FINISH_ADDING_FIELDS"
 *
 * @param int ClassID
 * @param int ClassType
 */
function nc_class_wizard_fields_end($ClassID, $ClassType) {
    global $UI_CONFIG, $db;
    $ClassID = intval($ClassID);
    $field_count = $db->get_var("SELECT COUNT(Field_ID) FROM `Field` WHERE Class_ID = '" . $ClassID . "'");
    if ($field_count) {
        echo "<form id='Wizard' method='post' action='wizard_class.php'>";

        if ($ClassType == 1)
            echo "<input type='hidden' name='phase' value='5'>";
        else
            echo "<input type='hidden' name='phase' value='4'>";

        echo "<input type='hidden' name='Class_Type' value='" . $ClassType . "'>";
        echo "<input type='hidden' name='ClassID' value='" . $ClassID . "'>";
        echo "<input type='hidden' name='fs' value='1'>";

        $UI_CONFIG->actionButtons[] = array("id" => "finish",
                "caption" => WIZARD_CLASS_BUTTON_FINISH_ADDING_FIELDS,
                "action" => "mainView.submitIframeForm('Wizard')",
                "align" => "right");
        echo "<input type='submit' class='hidden'>";
        echo "</form>";
    }

    return;
}

function nc_class_wizard_field_template($object, $show_description = true, $fullLink = false) {
    $template = '';
    $description = ($show_description ? "<b>" . $object['Description'] . ":</b> " : "");
    $fullLink_prefix = ($fullLink ? "<a href='\$fullLink'>" : "");
    $fullLink_suffix = ($fullLink ? "</a>" : "");

    switch ($object['TypeOfData_ID']) {
        case NC_FIELDTYPE_FILE:
            if (!$fullLink)
                $template .= $description . "<a href='\$f_" . $object['Field_Name'] . "'>\$f_" . $object['Field_Name'] . "_name</a> (\".nc_bytes2size(\$f_" . $object['Field_Name'] . "_size).\")";
            break;
        case NC_FIELDTYPE_DATETIME:
            $template .= $description . $fullLink_prefix . "\$f_" . $object['Field_Name'];
            if ($object['Format'] == 'event_date')
                $template .= "_day.\$f_" . $object['Field_Name'] . "_month.\$f_" . $object['Field_Name'] . "_year" . $fullLink_suffix;
            elseif ($object['Format'] == 'event_time')
                $template .= "_hours:\$f_" . $object['Field_Name'] . "_minutes:\$f_" . $object['Field_Name'] . "_seconds" . $fullLink_suffix;
            $template .= $fullLink_suffix;
            break;
        case NC_FIELDTYPE_MULTISELECT:
            $template .= $description . $fullLink_prefix . "\".nc_array_to_string(\$f_" . $object['Field_Name'] . ", array('element'=>'%ELEMENT', 'divider'=>', ')).\" " . $fullLink_suffix;
            break;
        default :
            $template .= $description . $fullLink_prefix . "\$f_" . $object['Field_Name'] . $fullLink_suffix;
    }
    return $template;
}

function nc_class_wizard_settings($class_id, $class_type, $class_name = '', $class_group = '') {
    global $db, $UI_CONFIG;
    $class_id = intval($class_id);
    $fields = $db->get_results("SELECT `Field_ID`, `Field_Name`, `Description` FROM `Field` WHERE `Class_ID` = '" . $class_id . "' ORDER BY `Field_ID`", ARRAY_A);
    echo "<form method='POST' action='wizard_class.php' name='settings'>";
    switch ($class_type) {

        case 1: #one object on page
            nc_print_status(WIZARD_CLASS_FORM_SETTINGS_NO_SETTINGS, 'error');
            break;

        case 4: #Web-form
            echo "<fieldset><legend>" . WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_FIELDS_SETTINGS . "</legend>";
            foreach ($fields as $field) {
                echo nc_admin_checkbox_simple("SettingsFormFields[" . $field['Field_ID'] . "]", $field['Field_ID'], $field['Field_Name'] . " (" . $field['Description'] . ")") . "<br>";
            }
            ?>
            </fieldset><br>
            <?= WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_SENDER_NAME ?>:<br>
            <?= nc_admin_input_simple('SettingsSenderName', '') ?><br><br>
            <?= WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_SENDER_MAIL ?>:<br>
            <?= nc_admin_input_simple('SettingsSenderEmail', '') ?><br><br>
            <?= WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_SUBJECT ?>:<br>
            <?= nc_admin_input_simple('SettingsSenderEmail', '') ?><br><br>
            <?
            break;

        case 3: #Search
            echo "<fieldset><legend>" . WIZARD_CLASS_FORM_SETTINGS_FIELDS_FOR_OBJECT_SEARCH . "</legend>";
            foreach ($fields as $field) {
                echo nc_admin_checkbox_simple("SettingsSearchFields[" . $field['Field_ID'] . "]", $field['Field_ID'], $field['Field_Name'] . " (" . $field['Description'] . ")") . "<br>";
            }

            echo "</fieldset>";

//Здесь break не нужен - сразу идет как и в "Списке объетов"

        case 2: // Список объектов
            echo "<fieldset><legend>" . WIZARD_CLASS_FORM_SETTINGS_FIELDS_FOR_OBJECT_LIST . "</legend>";
            foreach ($fields as $field) {
                echo nc_admin_checkbox_simple("SettingsObjectList[" . $field['Field_ID'] . "]", $field['Field_ID'], $field['Field_Name'] . " (" . $field['Description'] . ")") . "<br>";
            }
            ?>
            </fieldset>
            <br>
            <fieldset>
                <legend><?= WIZARD_CLASS_FORM_SETTINGS_SETTINGS_FOR_LIST_VIEW
            ?></legend>
                <b><?= WIZARD_CLASS_FORM_SETTINGS_OBJECT_NUMBER_ON_THE_PAGE
            ?>:</b><br><?= nc_admin_input_simple('SettingsRecNum', 30)
            ?><br><br>
                <b><?= WIZARD_CLASS_FORM_SETTINGS_SORT_OBJECT_BY_FIELD ?>:</b><br>
                <select name='SettingsSort'>
                    <option id='SettingsSort0' value='0'><?= WIZARD_CLASS_DEFAULT ?>
                        <?
                        foreach ($fields as $field) {
                            echo "<option value='" . $field[Field_Name] . "'>" . $field[Field_Name] . " (" . $field[Description] . ")";
                        }
                        ?>
                </select><br><br>
                <?= nc_admin_radio_simple('SettingsSortDirection', 1, WIZARD_CLASS_FORM_SETTINGS_SORT_ASC, true, 'SettingsSortDirection1') ?>
                <br>
                <?= nc_admin_radio_simple('SettingsSortDirection', 2, WIZARD_CLASS_FORM_SETTINGS_SORT_DESC, false, 'SettingsSortDirection2')
                ?>
                <br><br>
                <b><?= WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION ?>:</b><br>
                <?= nc_admin_radio_simple('SettingsNavigation', 1, WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION_BY_NEXT_PREV, true, 'SettingsNavigation1')
                ?>
                <br>
                <?= nc_admin_radio_simple('SettingsNavigation', 2, WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION_BY_PAGE_NUMBER, false, 'SettingsNavigation2')
                ?>
                <br>
                <?= nc_admin_radio_simple('SettingsNavigation', 3, WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION_BY_BOTH, false, 'SettingsNavigation3') ?>
                <br>
                <br><b><?= WIZARD_CLASS_FORM_SETTINGS_LOCATION_OF_NAVIGATION ?>:</b><br>
                <?= nc_admin_radio_simple('SettingsNavigationPosition', 1, WIZARD_CLASS_FORM_SETTINGS_LOCATION_TOP, true, 'SettingsNavigationPosition1')
                ?>
                <br>
            <?= nc_admin_radio_simple('SettingsNavigationPosition', 2, WIZARD_CLASS_FORM_SETTINGS_LOCATION_BOTTOM, false, 'SettingsNavigationPosition2') ?>
                <br>
                    <?= nc_admin_radio_simple('SettingsNavigationPosition', 3, WIZARD_CLASS_FORM_SETTINGS_LOCATION_BOTH, false, 'SettingsNavigationPosition3') ?>
                <br>

                <br><b><?= WIZARD_CLASS_FORM_SETTINGS_LIST_OBJECT_TYPE
                    ?>:</b><br>
                <?= nc_admin_radio_simple('SettingsObjectListType', 1, WIZARD_CLASS_FORM_SETTINGS_LIST_TYPE, true, 'SettingsObjectListType1')
                ?>
                <br>
            <?= nc_admin_radio_simple('SettingsObjectListType', 2, WIZARD_CLASS_FORM_SETTINGS_TABLE_TYPE, false, 'SettingsObjectListType2')
            ?>
                <br>
            </fieldset>
            <br>
            <fieldset id='SettingsObjectListDelimiter'>
                <legend><?= WIZARD_CLASS_FORM_SETTINGS_LIST_TYPE_SETTINGS
            ?></legend>
                <b><?= WIZARD_CLASS_FORM_SETTINGS_LIST_DELIMITER_TYPE ?>:</b><br>
                <?= nc_admin_radio_simple('SettingsObjectListDelimiter', 1, "&lt;hr&gt", true, 'SettingsObjectListDelimiter1')
                ?>
                <br>
            <?= nc_admin_radio_simple('SettingsObjectListDelimiter', 2, "&lt;br&gt", false, 'SettingsObjectListDelimiter2')
            ?>
                <br>
            </fieldset>
            <fieldset id='SettingsObjectTable'>
                <legend><?= WIZARD_CLASS_FORM_SETTINGS_TABLE_TYPE_SETTINGS ?></legend>
            <?= nc_admin_checkbox_simple('SettingsObjectTableBackground', 1, WIZARD_CLASS_FORM_SETTINGS_TABLE_BACKGROUND) ?><br>
            <?= nc_admin_checkbox_simple('SettingsObjectTableBorder', 1, WIZARD_CLASS_FORM_SETTINGS_TABLE_BORDER) ?><br>
            </fieldset>
            <br>
                <?= nc_admin_checkbox_simple('SettingsIsObjectFull', 1, WIZARD_CLASS_FORM_SETTINGS_FULL_PAGE) ?><br>
            <br>
            <fieldset id='SettingsObjectFullLink' style='display: none;'>
                <legend><?= WIZARD_CLASS_FORM_SETTINGS_FULL_PAGE_LINK_TYPE ?></legend>
                <?= WIZARD_CLASS_FORM_SETTINGS_CHECK_FIELDS_TO_FULL_PAGE ?><br>
                <?
                foreach ($fields as $field) {
                    echo nc_admin_checkbox_simple("SettingsObjectFullLink[" . $field[Field_ID] . "]", $field['Field_ID'], $field['Field_Name'] . " (" . $field['Description'] . ")") . '<br>';
                }
                ?>
            </fieldset>
            <br>
            <fieldset id='SettingsObjectFull' style='display: none;'>
                <legend><?= WIZARD_CLASS_FORM_SETTINGS_FIELDS_TO_SHOW_OBJECT
                ?></legend>
                <?
                foreach ($fields as $field) {
                    echo nc_admin_checkbox_simple("SettingsObjectFull[" . $field['Field_ID'] . "]", $field['Field_ID'], $field['Field_Name'] . " (" . $field['Description'] . ")") . '<br>';
                }
                ?>
            </fieldset>

            <script>
                var settingsIsObjectFull = document.getElementById('SettingsIsObjectFull');
                var settingsObjectFullLink = document.getElementById('SettingsObjectFullLink');
                var settingsObjectFull = document.getElementById('SettingsObjectFull');

                var settingsObjectListDelimiter = document.getElementById('SettingsObjectListDelimiter');
                var settingsObjectTable = document.getElementById('SettingsObjectTable');

                settingsObjectListDelimiter.style.display = 'none';
                settingsObjectTable.style.display = 'none';

                settingsIsObjectFull.onclick =  function() {
                    if(settingsIsObjectFull.checked) {
                        settingsObjectFullLink.style.display = '';
                        settingsObjectFull.style.display = '';
                    } else {
                        settingsObjectFullLink.style.display = 'none';
                        settingsObjectFull.style.display = 'none';
                    }
                    return true;
                }

                //  var settingsObjectListDelimiter = document.getElementById('SettingsObjectListDelimiter');

                var radio = document.forms.settings.SettingsObjectListType;

                for (var i = 0; i < radio.length; i++) {
                    if (radio[i].checked) {
                        if (radio[i].value == 1) {
                            settingsObjectListDelimiter.style.display = '';
                            settingsObjectTable.style.display = 'none';
                        }
                        if (radio[i].value == 2) {
                            settingsObjectTable.style.display = '';
                            settingsObjectListDelimiter.style.display = 'none';
                        }
                    }
                    radio[i].onclick = function() {
                        if (this.checked) {
                            if (this.value == 1) {
                                settingsObjectListDelimiter.style.display = '';
                                settingsObjectTable.style.display = 'none';
                            }
                            if (this.value == 2) {
                                settingsObjectTable.style.display = '';
                                settingsObjectListDelimiter.style.display = 'none';
                            }
                        }
                    }
                }

            </script>
            <?php
            break;
    }

    echo "<input type='hidden' name='phase' value='5'>";
    echo "<input type='hidden' name='Class_Type' value='" . htmlspecialchars($class_type, ENT_QUOTES) . "'>";
    echo "<input type='hidden' name='ClassID' value='$class_id'>";
    echo "<input type='hidden' name='ClassName' value='" . htmlspecialchars($class_name, ENT_QUOTES) . "'>";
    echo "<input type='hidden' name='Class_Group' value='" . htmlspecialchars($class_group, ENT_QUOTES) . "'>";
    echo "<input type='submit' class='hidden'>";
    echo "</form>";
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => WIZARD_CLASS_BUTTON_SAVE_SETTINGS,
            "action" => "mainView.submitIframeForm()"
    );
}

function nc_class_wizard_select_site($class_id, $class_type, $phase) {
    global $db, $UI_CONFIG;

    echo "<form action='wizard_class.php' method='POST'>";
    echo "Выберите сайт: <select name='CatalogueID'>";
    $sites = $db->get_results("SELECT Catalogue_ID, Catalogue_Name FROM Catalogue ORDER BY Catalogue_ID", ARRAY_A);
    foreach ($sites as $site) {
        echo "<option value=" . $site['Catalogue_ID'] . ">" . $site['Catalogue_ID'] . ": " . $site['Catalogue_Name'] . "";
    }
    echo "</select><br>";
    echo "<input type='hidden' name='phase' value='" . $phase . "'>";
    echo "<input type='hidden' name='ClassID' value='" . $class_id . "'>";
    echo "<input type='hidden' name='Class_Type' value='" . $class_type . "'>";
    echo "<input type='submit' class='hidden'>";
    echo "</form>";
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => WIZARD_CLASS_BUTTON_NEXT_TO_SUBDIVISION_SELECTION,
            "action" => "mainView.submitIframeForm()"
    );
}

function nc_class_wizard_select_subdivision($class_id, $catalogue_id, $class_type, $phase) {
    global $db, $UI_CONFIG;
    $catalogue_id = intval($catalogue_id);
    echo "<form action='wizard_class.php' method='POST'>";
    if (!empty($subdivisions)) {
        echo CONTROL_USER_SELECTSECTION . ": <select name='ParentSubID'>";
        $subdivisions = $db->get_results("SELECT Subdivision_ID as value,
                                             CONCAT(Subdivision_ID, '. ', Subdivision_Name) as description,
                                             Parent_Sub_ID as parent
                                        FROM Subdivision
                                       WHERE Catalogue_ID='" . $catalogue_id . "'
                                    ORDER BY Subdivision_ID", ARRAY_A);
        echo nc_select_options($subdivisions);
        echo "</select><br>";
    } else {
        echo CONTROL_USER_NOONESECSINSITE;
    }
    echo "<input type='hidden' name='phase' value='" . $phase . "'>";
    echo "<input type='hidden' name='ClassID' value='" . $class_id . "'>";
    echo "<input type='hidden' name='Class_Type' value='" . $class_type . "'>";
    echo "<input type='hidden' name='CatalogueID' value='" . $catalogue_id . "'>";
    echo "<input type='submit' class='hidden'>";
    echo "</form>";
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => WIZARD_CLASS_BUTTON_CREATE_SUBDIVISION_WITH_NEW_TEMPLATE,
            "action" => "mainView.submitIframeForm()"
    );
}

function nc_class_wizard_select_action($class_id, $class_type) {
    global $db, $UI_CONFIG, $ADMIN_PATH;

    echo "<div>" . WIZARD_CLASS_TAB_SUBSEQUENT_ACTION . ":</div>";
    echo "<ul>";
    echo "<li><a href='" . $ADMIN_PATH . "class/index.php?fs=1&phase=4&ClassID=$class_id'>" . WIZARD_CLASS_LINKS_VIEW_TEMPLATE_CODE . "</a>";
    echo "<li><a href='wizard_class.php?fs=1&phase=6&Class_Type=$class_type&ClassID=$class_id'>" . WIZARD_CLASS_LINKS_CREATE_SUBDIVISION_WITH_NEW_TEMPLATE . "</a>";
    echo "<li><a href='wizard_class.php?fs=1&phase=8&Class_Type=$class_type&ClassID=$class_id'>" . WIZARD_CLASS_LINKS_ADD_TEMPLATE_TO_EXISTENT_SUBDIVISION . "</a>";
    echo "<li><a href='wizard_class.php?fs=1&'>" . WIZARD_CLASS_LINKS_CREATE_NEW_TEMPLATE . "</a>";
    echo "</ul>";

    $UI_CONFIG->actionButtons = '';
}

function nc_class_wizard_subdivision_form($class_id) {
    global $UI_CONFIG, $ADMIN_PATH;
    global $Subdivision_Name, $EnglishName, $Checked, $ParentSubID, $CatalogueID;

    echo "<form id='Subdivision' enctype='multipart/form-data' method='post' action='wizard_class.php'>\n";
    echo "<fieldset>\n";
    echo "<legend>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA . "</legend>\n";
    echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME . ":<br>\n";
    echo nc_admin_input_simple('Subdivision_Name', $Subdivision_Name, 50, '', "maxlength='255'") . "<br><br>\n";
    echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD . ":<br>\n";
    echo nc_admin_input_simple('EnglishName', $EnglishName, 50, '', "maxlength='255'") . "<br><br>\n";
    echo WIZARD_CLASS_FORM_SUBDIVISION_PARENTSUB . ":<br>\n";
    echo nc_admin_input_simple('ParentSubID', $ParentSubID, 0, '', "id='SelectedSub' readonly");
    echo "&nbsp;&nbsp;&nbsp;<a href='#' onclick=\"window.open('" . $ADMIN_PATH . "wizard/select_parentsub.php', 'nc_popup_map', 'width=350,height=500,menubar=no,resizable=no,scrollbars=no,toolbar=no,resizable=yes'); return false;\">" . WIZARD_CLASS_FORM_SUBDIVISION_SELECT_PARENTSUB . "</a><br><br>\n";
//  echo "&nbsp;&nbsp;&nbsp;<a href='#' onclick=\"document.getElementById('SelectedSub').value='';document.getElementById('SelectedCat').value='';return false;\">".WIZARD_CLASS_FORM_SUBDIVISION_DELETE."</a><br><br>\n";
    echo nc_admin_checkbox_simple('Checked', 1, CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON, false, 'turnon', (isset($Checked) ? ($Checked ? "checked" : "") : "checked"));
    echo "</fieldset>\n";
    echo "<input type='hidden' name='TemplateID' value='0'>\n<input type='hidden' name='ReadAccessID' value='0'>\n<input type='hidden' name='WriteAccessID' value='0'>\n<input type='hidden' name='EditAccessID' value='0'>\n<input type='hidden' name='SubscribeAccessID' value='0'>\n<input type='hidden' name='ModerationID' value='0'>\n<input type='hidden' name='Priority' value='0'>\n<input type='hidden' name='Favorite' value='0'>\n<input type='hidden' name='posting' value='1'>\n<input type='hidden' name='type' value='1'>\n<input type='hidden' name='phase' value='7'>\n<input id='SelectedCat' type='hidden' name='CatalogueID' value='" . $CatalogueID . "'>\n<input type='hidden' name='SubdivisionID' value=''>\n<input type='hidden' name='ClassID' value='" . $class_id . "'>\n\n";
    echo "<input type='submit' class='hidden'>";
    echo "</form>\n";

    $UI_CONFIG->actionButtons = array(
            array("id" => "submit",
                    "caption" => WIZARD_CLASS_BUTTON_ADDING_SUBDIVISION_WITH_NEW_TEMPLATE,
                    "action" => "mainView.submitIframeForm()"));
}

function nc_class_wizard_class_form($class_id, $class_type) {
    global $db, $UI_CONFIG, $ADMIN_PATH;
    global $EnglishName, $SubClassName, $Checked, $SubdivisionID, $CatalogueID;


    echo "<form method='post' action='wizard_class.php'>\n";
    echo "<fieldset>\n";
    echo "<legend>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MAININFO . "</legend>\n";
    printf(CONTROL_CONTENT_SUBCLASS_CLASSNAME, ($CatalogueID ? CONTROL_CONTENT_SUBCLASS_ONSITE : CONTROL_CONTENT_SUBCLASS_ONSECTION));
    echo ":<br>" . nc_admin_input_simple('SubClassName', ($class_name ? $class_name : $SubClassName), 50, '', "id='SubClassName' maxlength='255'") . "<br><br>\n";
    echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD . ":<br>" . nc_admin_input_simple('EnglishName', $EnglishName, 50, '', "id='EnglishName' maxlength='255") . "<br><br>\n";
    echo WIZARD_CLASS_FORM_SUBDIVISION_PARENTSUB . ":<br>\n";
    echo nc_admin_input_simple('SubdivisionID', $SubdivisionID, 0, '', "id='SelectedSub' readonly");
    echo "&nbsp;&nbsp;&nbsp;<a href='#' onclick=\"window.open('" . $ADMIN_PATH . "wizard/select_subdivision.php', 'nc_popup_map', 'width=350,height=500,menubar=no,resizable=no,scrollbars=no,toolbar=no,resizable=yes'); return false;\">" . WIZARD_CLASS_FORM_SUBDIVISION_SELECT_SUBDIVISION . "</a><br><br>\n";
//  echo "&nbsp;&nbsp;&nbsp;<a href='#' onclick=\"document.getElementById('SelectedSub').value='';document.getElementById('SelectedCat').value='';return false;\">".WIZARD_CLASS_FORM_SUBDIVISION_DELETE."</a><br><br>\n";
    echo nc_admin_checkbox_simple('Checked', 1, CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON, false, 'turnon', (isset($Checked) ? ($Checked ? " checked" : "") : " checked")) . "<br><br>\n";
    switch ($class_type) {
        case 1:
            $default_action = 'index';
            break;

        case 2:
            $default_action = 'index';
            break;

        case 3:
            $default_action = 'search';
            break;

        case 4:
            $default_action = 'add';
            break;
    }

    echo "<input type='hidden' name='DefaultAction' value='" . $default_action . "' />\n";
    echo "<input type='hidden' name='ReadAccessID' value='0' />\n";
    echo "<input type='hidden' name='WriteAccessID' value='0' />\n";
    echo "<input type='hidden' name='EditAccessID' value='0' />\n";
    echo "<input type='hidden' name='SubscribeAccessID' value='0' />\n";
    echo "<input type='hidden' name='ModerationID' value='0' />\n";
    echo "<input type='hidden' name='AllowTags' value='-1' />\n";
    echo "<input type='hidden' name='NL2BR' value='-1' />\n";
    echo "<input type='hidden' name='UseCaptcha' value='-1' />\n";
    echo "<input type='hidden' name='RecordsPerPage' value='' />\n";
    echo "<input type='hidden' name='SortBy' value='' />\n";
    echo "<input type='hidden' name='ClassID' value='" . $class_id . " /'>\n";
    echo "<input type='hidden' name='phase' value='9' />\n";
    echo "<input type='hidden' name='SubClassID' value='0' />\n";
    echo "<input id='SelectedCat' type='hidden' name='CatalogueID' value='" . $CatalogueID . "' />\n";
    ;
    echo "<input type='submit' class='hidden' />";
    echo "</form>\n";

    $UI_CONFIG->actionButtons = array(
            array("id" => "submit",
                    "caption" => STRUCTURE_TAB_SUBCLASS_ADD,
                    "action" => "mainView.submitIframeForm()"));
}

/*
 *
 */

function nc_site_wizard_main_sub_form($phase, $site_id) {
    global $db, $UI_CONFIG;
    $site_id = intval($site_id);
    $site = $db->get_row("SELECT Title_Sub_ID,
  		                       E404_Sub_ID,
  		                       Rules_Sub_ID,
  		                       Template_ID
  		                  FROM Catalogue
  		                 WHERE Catalogue_ID = '" . $site_id . "'", ARRAY_A);


    $templates = $db->get_results("SELECT Template_ID as value,
                                        CONCAT(Template_ID, '. ', Description) as description,
                                        Parent_Template_ID as parent
                                   FROM Template
                               ORDER BY Priority, Template_ID", ARRAY_A);

    $inherit_template = $db->get_row("SELECT a.Template_ID,
  		                                   b.Description as TemplateName
  		                              FROM Catalogue as a,
  		                                   Template as b
  		                             WHERE Catalogue_ID = '" . $site_id . "' AND
  		                                   b.Template_ID = a.Template_ID", ARRAY_A);

    echo "<form method='post' action='wizard_site.php'>\n";

    echo "<fieldset>\n";
    echo "<legend>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TITLEPAGE . "</legend>\n";
    echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME . ":<br>\n";
    echo nc_admin_input_simple('TitleSubIDName', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TITLEPAGE_PAGE) . "<br><br>\n";
    echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD . ":<br>\n";
    echo nc_admin_input_simple('TitleSubIDKeyword', 'index') . "<br><br>\n";

    if (!empty($templates)) {
        echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE . ":<br>\n";
        echo "<select name='TitleTemplateID'>\n";
        echo "<option " . ($site['Template_ID'] ? "" : "selected ") . "value='0'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_N . " [" . $inherit_template['Template_ID'] . ". " . $inherit_template['TemplateName'] . "]</option>";
        echo nc_select_options($templates, $site['Title_Sub_ID']);
        echo "</select><br>\n";
    } else {
        echo CONTROL_TEMPLATE_NONE;
    }
    echo "</fieldset>\n";

    echo "<fieldset>\n";
    echo "<legend>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND . "</legend>\n";
    echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME . ":<br>\n";
    echo nc_admin_input_simple('E404SubIDName', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND_PAGE) . "<br><br>\n";
    echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD . ":<br>\n";
    echo nc_admin_input_simple('E404SubIDKeyword', '404') . "<br><br>\n";

    if (!empty($templates)) {
        echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE . ":<br>\n";
        echo "<select name='E404TemplateID'>\n";
        echo "<option " . ($site['Template_ID'] ? "" : "selected ") . "value='0'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_N . " [" . $inherit_template['Template_ID'] . ". " . $inherit_template['TemplateName'] . "]</option>";
        echo nc_select_options($templates, $site['Title_Sub_ID']);
        echo "</select><br>\n";
    } else {
        echo CONTROL_TEMPLATE_NONE;
    }
    echo "</fieldset>\n";

// Создание разделов для модулей
    /*
      $modules = $db->get_results("SELECT Keyword,
      Module_Name
      FROM Module
      ORDER BY Keyword", ARRAY_A);

      if ($modules) {
      echo "<fieldset>\n";
      echo "<legend>".WIZARD_SITE_FORM_WHICH_MODULES."</legend>\n";
      foreach ($modules as $module) {
      echo "<input type='checkbox' name='modules[".$module['Keyword']."]' value='1'>&nbsp;".constant($module['Module_Name'])."<br>\n";
      }
      echo "</fieldset><br>\n";
      }
     */
    echo "<input type='hidden' name='posting' value='1'>";
    echo "<input type='hidden' name='phase' value='" . $phase . "'>";
    echo "<input type='hidden' name='CatalogueID' value='" . $site_id . "'>";
    echo "<input type='submit' class='hidden'>";
    echo "</form>\n";

    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => WIZARD_SITE_BUTTON_ADD_SUBS,
            "action" => "mainView.submitIframeForm()");
}

/*
 *
 */

function nc_site_wizard_main_sub_add($phase, $site_id, $title_name, $title_keyword, $title_template_id, $e404_name, $e404_keyword, $e404_template_id, $modules) {
    global $nc_core, $db, $UI_CONFIG;

// проверка названия раздела
    if (!$title_name || !$e404_name) {
        $UI_CONFIG = new ui_config_wizard_site(2, $site_id);
        nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_NAME, 'error');
        nc_site_wizard_main_sub_form(3, $site_id);
        return false;
    }

// проверка уникальности ключевого слова для текущего раздела
    if (!IsAllowedSubdivisionEnglishName($title_keyword, 0, 0, $site_id) || !IsAllowedSubdivisionEnglishName($e404_keyword, 0, 0, $site_id)) {
        $UI_CONFIG = new ui_config_wizard_site(2, $site_id);
        nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_KEYWORD, 'error');
        nc_site_wizard_main_sub_form(3, $site_id);
        return false;
    }

// проверка символов для ключевого слова
    if ($nc_core->subdivision->validate_english_name($title_keyword)) {
        $UI_CONFIG = new ui_config_wizard_site(2, $site_id);
        nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
        nc_site_wizard_main_sub_form(3, $site_id);
        return false;
    }

    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_CREATED, $site_id, 0);

    // Добавление раздела для титульной страницы
    $db->query("INSERT INTO `Subdivision`
    SET `Catalogue_ID` = '" . intval($site_id) . "',
    `Parent_Sub_ID` = 0,
    `Subdivision_Name` = '" . $db->escape($title_name) . "',
    `Checked` = 0,
    `EnglishName` = '" . $title_keyword . "',
    `Hidden_URL` = '/" . $title_keyword . "/',
    `Priority` = 0");

    $title_sub_id = $db->insert_id;

    // execute core action
    $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_CREATED, $site_id, $title_sub_id);

    $UI_CONFIG = new ui_config_wizard_site(3, $site_id);

    $buttons[] = array("image" => "icon_folder_add",
            "label" => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION,
            "href" => "subdivision.add(" . $title_sub_id . ")");

    $buttons[] = array("image" => "icon_folder_delete",
            "label" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_KILL,
            "href" => "subdivision.delete(" . $title_sub_id . ")");

    $UI_CONFIG->treeChanges['addNode'][] = array("nodeId" => "sub-$title_sub_id",
            "parentNodeId" => "site-$site_id",
            "name" => $title_sub_id . ". " . $title_name,
            "href" => "#subclass.add($title_sub_id)",
            "image" => $tree_image = "icon_folder_disabled",
            "hasChildren" => false,
            "dragEnabled" => true,
            "buttons" => $buttons,
            "acceptDropFn" => "treeSitemapAcceptDrop",
            "onDropFn" => "treeSitemapOnDrop",
            "className" => "disabled",
            "subclasses" => array()
    );

    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_CREATED, $site_id, 0);

    // Добавление раздела для 404
    $db->query("INSERT INTO `Subdivision`
    SET `Catalogue_ID` = '" . intval($site_id) . "',
    `Parent_Sub_ID` = 0,
    `Subdivision_Name` = '" . $db->escape($e404_name) . "',
    `Checked` = 0,
    `EnglishName` = '" . $e404_keyword . "',
    `Hidden_URL` = '/" . $e404_keyword . "/',
    `Priority` = 1");
    $e404_sub_id = $db->insert_id;

    // execute core action
    $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_CREATED, $site_id, $e404_sub_id);

    $buttons[] = array("image" => "icon_folder_add",
            "label" => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION,
            "href" => "subdivision.add(" . $title_sub_id . ")");

    $buttons[] = array("image" => "icon_folder_delete",
            "label" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_KILL,
            "href" => "subdivision.delete(" . $title_sub_id . ")");

    $UI_CONFIG->treeChanges['addNode'][] = array("nodeId" => "sub-$e404_sub_id",
            "parentNodeId" => "site-$site_id",
            "name" => $e404_sub_id . ". " . $e404_name,
            "href" => "#subclass.add($e404_sub_id)",
            "image" => $tree_image = "icon_folder_disabled",
            "hasChildren" => false,
            "dragEnabled" => true,
            "buttons" => $buttons,
            "acceptDropFn" => "treeSitemapAcceptDrop",
            "onDropFn" => "treeSitemapOnDrop",
            "className" => "disabled",
            "subclasses" => array()
    );

    $db->query("UPDATE Catalogue
  		         SET Title_Sub_ID = '" . $title_sub_id . "',
  		             E404_Sub_ID = '" . $e404_sub_id . "'
  		       WHERE Catalogue_ID = '" . $site_id . "'");

    nc_tpl_mixin_cache::generate_site_files($site_id);

    if ($title_sub_id && $e404_sub_id && $db->rows_affected) {
        return true;
    } else {
        return false;
    }
}

function nc_site_wizard_map() {
    global $nc_core, $db;
    global $HTTP_DOMAIN, $HTTP_HOST, $EDIT_DOMAIN, $HTTP_ROOT_PATH, $CatalogueID;
    global $AUTHORIZATION_TYPE, $first, $perm;
    global $UI_CONFIG, $ADMIN_PATH, $ADMIN_TEMPLATE;

    $is_supervisor = ($perm->isSupervisor());
    if ($is_supervisor) {
        $select = "select Catalogue_ID, Catalogue_Name, Domain, Title_Sub_ID, Checked from Catalogue where Catalogue_ID=" . $CatalogueID;
        $Array = $db->get_row($select, ARRAY_N);
        if (!$db->num_rows) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_ERR_NOONESITE, 'error');
            return;
        }

        echo "<form name='siteMapForm' id='siteMapForm' method='get' action='index.php'>";
//    echo "<input type='hidden' name='phase' value='7'>";

        echo "<table border=0 cellpadding=0 cellspacing=0 id='siteMap'>";
        echo "<tr id='site_tr-" . $CatalogueID . "'>";
        echo "<td id='siteTitle' class='name " . ($Array[4] ? "active" : "unactive") . "'><span>" . $Array[0] . ". </span><a href='" . $ADMIN_PATH . "catalogue/?phase=6&CatalogueID=" . $Array[0] . "'>" . $Array[1] . "</a></td>";
        echo "<td class='button'><a href='#' onclick='loadSubdivisionAddForm($CatalogueID, 0)'><div class='icons icon_folder_add' title='" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION . "'></div></a></td>";
        echo "</tr>";

        echo "<tr><td colspan='2' style='padding: 0; background: #FFFFFF;'><div id='site-" . $CatalogueID . "'></div></td></tr>";

        $count = 1;
        echo nc_site_wizard_map_write_sub(0, $Array[0]);

        echo "</table>";
        echo "</form>\n";
    }

    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => WIZARD_SITE_BUTTON_FINISH_ADD_SUBS,
            "location" => "site.map(" . $CatalogueID . ")"
    );
    /*
      $UI_CONFIG->actionButtons[] = array("id" => "submit",
      "caption" => NETCAT_ADMIN_DELETE_SELECTED,
      "action" => "mainView.submitIframeForm('siteMapForm')"
      );
     */
}

function nc_site_wizard_map_write_sub($ParentSubID, $CatalogueID, $count = 1) {
    global $db, $nc_core;
    global $HTTP_ROOT_PATH, $EDIT_DOMAIN, $SUB_FOLDER;
    global $perm, $ADMIN_PATH, $ADMIN_TEMPLATE;

    $select = "SELECT a.Subdivision_ID, a.Subdivision_Name,a.Priority,a.Checked,a.Hidden_URL,b.Domain,a.Catalogue_ID,a.ExternalURL, a.Parent_Sub_ID FROM Subdivision AS a, Catalogue AS b";
    $select .= " where a.Catalogue_ID=b.Catalogue_ID AND a.Catalogue_ID=" . $CatalogueID;
    $select .= " AND a.Parent_Sub_ID=" . $ParentSubID . " ORDER BY a.Priority";

    $result = '';
    if ($Result = $db->get_results($select, ARRAY_N)) {

        foreach ($Result as $Array) {
            $hidden_host = $nc_core->catalogue->get_url_by_id($Array[6]) . $SUB_FOLDER;

            $result .= "<tr id='tr-" . $Array[0] . "' parentSub='" . $Array[8] . "'>";
            $result .= "<td class='name " . ($Array[3] ? "active" : "unactive") . "' style='padding-left: " . ($count + 15) . "px;'><img src='" . $ADMIN_PATH . "images/arrow_sec.gif' width='14' height='10' alt='' title=''><span>" . $Array[0] . ". </span><a href='../subdivision/index.php?phase=4&SubdivisionID=" . $Array[0] . "'>" . $Array[1] . "</a></td>";
            $result .= "<td class='button'><a href='#' onclick='loadSubdivisionAddForm($CatalogueID, " . $Array[0] . ")'><div class='icons icon_folder_add' title='" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION . "'></div></a></td>";
            $result .= "</tr>";

            $result .= "<tr><td colspan='2' style='padding: 0 0 0 " . ($count + 15) . "px; background: #FFFFFF;'><div id='sub-" . $Array[0] . "'></div></td></tr>";

            $result .= nc_site_wizard_map_write_sub($Array[0], $CatalogueID, $count + 20);
        }
    }
    return $result;
}

/**
 * Мастер шаблонов
 */
class ui_config_wizard_class extends ui_config {

    public function __construct($phase, $class_type, $class_id) {

        $this->headerText = SECTION_INDEX_WIZARD_SUBMENU_CLASS;
        $this->treeMode = 'dataclass_fs';

        $this->tabs = array(
                array(
                        'id' => 'step1',
                        'caption' => ($phase == 1 ? WIZARD_CLASS_TAB_SELECT_TEMPLATE_TYPE : WIZARD_CLASS_STEP . " 1 " . WIZARD_CLASS_STEP_FROM . " 5"),
                        'location' => "dataclass.wizard(" . $phase . ", " . $class_type . ", " . $class_id . ")",
                        'unactive' => true
                ),
                array(
                        'id' => 'step2',
                        'caption' => ($phase == 2 ? WIZARD_CLASS_TAB_ADDING_TEMPLATE_FIELDS : WIZARD_CLASS_STEP . " 2 " . WIZARD_CLASS_STEP_FROM . " 5"),
                        'location' => "dataclass.wizard(" . $phase . ", " . $class_type . ", " . $class_id . ")",
                        'unactive' => true
                ),
                array(
                        'id' => 'step3',
                        'caption' => ($phase == 3 ? WIZARD_CLASS_TAB_TEMPLATE_SETTINGS : WIZARD_CLASS_STEP . " 3 " . WIZARD_CLASS_STEP_FROM . " 5"),
                        'location' => "dataclass.wizard(" . $phase . ", " . $class_type . ", " . $class_id . ")",
                        'unactive' => true
                ),
                array(
                        'id' => 'step4',
                        'caption' => ($phase == 4 ? WIZARD_CLASS_TAB_SUBSEQUENT_ACTION : WIZARD_CLASS_STEP . " 4 " . WIZARD_CLASS_STEP_FROM . " 5"),
                        'location' => "dataclass.wizard(" . $phase . ", " . $class_type . ", " . $class_id . ")",
                        'unactive' => true
                )
        );
        if (!in_array($phase, array(6, 7, 8))) {
            $this->tabs[] = array(
                    'id' => 'step5',
                    'caption' => WIZARD_CLASS_STEP . " 5 " . WIZARD_CLASS_STEP_FROM . " 5",
                    'location' => "dataclass.wizard(" . $phase . ", " . $class_type . ", " . $class_id . ")",
                    'unactive' => true
            );
        }
        if ($phase == 6) {
            $this->tabs[] = array(
                    'id' => 'step6',
                    'caption' => ($phase == 6 ? WIZARD_CLASS_TAB_CREATION_SUBDIVISION_WITH_NEW_TEMPLATE : WIZARD_CLASS_STEP . " 5 " . WIZARD_CLASS_STEP_FROM . " 5"),
                    'location' => "dataclass.wizard(" . $phase . ", " . $class_type . ", " . $class_id . ")",
                    'unactive' => true
            );
        }
        if ($phase == 7) {
            $this->tabs[] = array(
                    'id' => 'step7',
                    'caption' => ($phase == 7 ? WIZARD_CLASS_TAB_CREATION_SUBDIVISION_WITH_NEW_TEMPLATE : WIZARD_CLASS_STEP . " 5 " . WIZARD_CLASS_STEP_FROM . " 5"),
                    'location' => "dataclass.wizard(" . $phase . ", " . $class_type . ", " . $class_id . ")",
                    'unactive' => true
            );
        }
        if ($phase == 8) {
            $this->tabs[] = array(
                    'id' => 'step8',
                    'caption' => ($phase == 8 ? WIZARD_CLASS_TAB_ADDING_TEMPLATE_TO_EXISTENT_SUBDIVISION : WIZARD_CLASS_STEP . " 5 " . WIZARD_CLASS_STEP_FROM . " 5"),
                    'location' => "dataclass.wizard(" . $phase . ", " . $class_type . ", " . $class_id . ")",
                    'unactive' => true
            );
        }
        if ($phase == 9) {
            $this->tabs[] = array(
                    'id' => 'step9',
                    'caption' => ($phase == 9 ? WIZARD_CLASS_TAB_ADDING_TEMPLATE_TO_EXISTENT_SUBDIVISION : WIZARD_CLASS_STEP . " 5 " . WIZARD_CLASS_STEP_FROM . " 5"),
                    'location' => "dataclass.wizard(" . $phase . ", " . $class_type . ", " . $class_id . ")",
                    'unactive' => true
            );
        }

        $this->activeTab = "step" . $phase;

        if ($class_id) {
            $this->treeSelectedNode = "dataclass-" . $class_id;
        } else {
            $this->treeSelectedNode = "dataclass.list";
        }
// при переадресации теряются POST данные и поэтому выползает предупреждение об отсутствии переменных
//$this->locationHash = "#dataclass.wizard(".$phase.", ".$class_type.", ".$class_id.")";
    }

}

/**
 * Мастер сайтов
 */
class ui_config_wizard_site extends ui_config {

    public function __construct($phase, $site_id) {

        global $db;

        $this->headerText = SECTION_INDEX_WIZARD_SUBMENU_SITE;
        $this->treeMode = 'sitemap';
        if (!isset($class_type)) {
            $class_type = null;
        }
        if (!isset($class_id)) {
            $class_id = null;            
        }        
        $this->tabs = array(
                array('id' => 'step1',
                        'caption' => ($phase == 1 ? WIZARD_SITE_TAB_NEW_SITE_CREATION : WIZARD_SITE_STEP . " 1 " . WIZARD_SITE_STEP_FROM . " 2"),
                        'location' => "dataclass.wizard($phase,$class_type,$class_id)",
                        'unactive' => true),
                array('id' => 'step2',
                        'caption' => ($phase == 2 ? WIZARD_SITE_TAB_NEW_SITE_ADD_SUB : WIZARD_SITE_STEP . " 2 " . WIZARD_SITE_STEP_FROM . " 2"),
                        'location' => "dataclass.wizard($phase,$class_type,$class_id)",
                        'unactive' => true)
        );

        $this->activeTab = "step" . $phase;
        if ($phase != 1)
            $this->treeSelectedNode = "site-$site_id";
        $this->locationHash = "site.wizard($phase,$site_id)";
    }

}
