<?

###############################################################

function UninstallationAborted() {
    global $TMP_FOLDER;

    print TOOLS_MODULES_ERR_UNINSTALL.".<br>\n";
    DeleteFilesInDirectory($TMP_FOLDER);
    EndHtml ();
    exit;
}

###############################################################

function CheckFiles() {
    global $TMP_FOLDER;

    $files = array("id.txt", "sql.txt", "sql_int.txt", "files.txt", "parameters.txt",
            "index.php", "install.php", "function.inc.php", "en.lang.php", "ru.lang.php");
    $files[] = ( MAIN_LANG != "ru" ) ? "message_int.txt" : "message.txt";

    foreach ($files as $v) {
        if (!is_readable($TMP_FOLDER.$v)) {
            print TOOLS_MODULES_ERR_CANTOPEN." ".$v.".<br />\n";
            InstallationAborted ();
        }
    }
}

###############################################################

/**
 * Вывод списка модулей
 *
 */
function ModuleList() {
    global $nc_core, $UI_CONFIG;

    // загрузка всех модулей
    $language = $nc_core->lang->detect_lang(true);
    $nc_core->modules->load_env($language, false, true, true);

    if ($modules = $nc_core->modules->get_data(1, 1)) {
        $res = "<form name='adminForm' id='adminForm' class='nc-form' method='post'>";
        $res .= module_catalogue_select_field();
        $res .= "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td>
              <table width='100%' class='admin_table'>
                <tr>
                  <th></th>
                  <th width='90%'>" . TOOLS_MODULES_MOD_NAME ."</th>
                  <th>" . TOOLS_MODULES_MOD_PREFS . "</th>
                  <th>" . NETCAT_MODULE_ALWAYS_LOAD . "</th>
                  <th>" . NETCAT_MODULE_ONOFF . "</th>
                </tr>";

        // проход по каждому модулю
        foreach ($modules as $module) {
            $keyword = $module['Keyword'];
            $name = constant($module['Module_Name']);
            $id = $module['Module_ID'];

            $admin_file = nc_module_folder($keyword) . 'admin.php';
            $setup_file = nc_module_folder($keyword) . 'setup.php';
            $custom_icon = nc_module_folder($keyword) . 'icon-20x20.png';
            $web_path_to_admin_file = nc_module_path($keyword) . 'admin.php';
            $web_path_to_setup_file = nc_module_path($keyword) . 'setup.php';
            $web_path_to_custom_icon = nc_module_path($keyword) . 'icon-20x20.png';
            $checkbox_inside_admin = nc_admin_checkbox_simple("inside_admin{$id}", 1, '', $module['Inside_Admin']);
            $checkbox_checked = nc_admin_checkbox_simple("check{$id}", 1, '', $module['Checked']);

            if ($module['Checked']) {
                $colored_name = file_exists($admin_file) ? "<a href='{$web_path_to_admin_file}'>{$name}</a>" : $name;
                $icon_settings_state = $icon_mod_state = '';
            } else {
                $colored_name = "<span class='nc-text-grey'>$name</span>";
                $icon_settings_state = $icon_mod_state = 'nc--disabled';
            }

            if (file_exists($setup_file) && !$module['Installed']) {
                $setup = '&nbsp;|&nbsp;<a href="%s"><span class="nc-text-red"><b>%s &rarr;</b></span></a>';
                $setup = sprintf($setup, $web_path_to_setup_file, TOOLS_MODULES_MOD_GOINSTALL);
            } else {
                $setup = '';
            }

            if (file_exists($custom_icon)) {
                $icon_class = 'nc-icon';
                $icon_style = "background-image: url({$web_path_to_custom_icon}); background-position: 0 0;";
            } else {
                $icon_class = "nc-icon nc--mod-{$keyword}";
                $icon_style = '';
            }

            $icon = '<i class="%s %s" title="%s" style="%s"></i>';
            $icon = sprintf($icon, $icon_class, $icon_mod_state, $name, $icon_style);

            $settings = '<a href="index.php?phase=2&module_name=%s"><i class="nc-icon nc--settings %s" title="%s"></i></a>';
            $settings = sprintf($settings, $keyword, $icon_settings_state, TOOLS_MODULES_MOD_EDIT);

            $res .= "<tr>
                       <td>{$icon}</td>
                       <td>{$colored_name}{$setup}</td>
                       <td class='nc-text-center'>{$settings}</td>
                       <td class='nc-text-center'>{$checkbox_inside_admin}</td>
                       <td class='nc-text-center'>{$checkbox_checked}</td>
                     </tr>";
        }

        $res .= "</table></td></tr></table>";
        $res .= $nc_core->token->get_input();
        $res .= "<input type='hidden' name='phase' value='6'></form>";
        echo $res;
    }

    //кнопка "Сохранить изменения"
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
                                        "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                                        "action" => "mainView.submitIframeForm()");
}

###############################################################

function ModuleUpdateForm($ModuleID) {
    global $db, $MODULE_FOLDER, $nc_core;
    global $ADMIN_PATH, $ADMIN_TEMPLATE;
    $ModuleID = intval($ModuleID);
    $Array = $db->get_row("SELECT * FROM `Module` WHERE `Module_ID` = '".$ModuleID."'", ARRAY_A);

    if (!$Array['Checked']) {
        print NETCAT_MODULE_MODULE_UNCHECKED;
        return;
    }

    $keyword = $Array["Keyword"];

    if ($Array["Keyword"] !== 'default') {
        if (file_exists(nc_module_folder($keyword) . MAIN_LANG . '.lang.php')) {
            require_once nc_module_folder($keyword) . MAIN_LANG . '.lang.php';
        } else {
            require_once nc_module_folder($keyword) . 'en.lang.php';
        }
    }
?>
    <form method='post' action='index.php'>
        <table class='admin_table' style='width:100%;' id='tableParam'>
            <col style='width:35%'/><col style='width:60%'/><col style='width:5%'/>
            <tbody>
                <tr>
                    <th class='align-center first_col'><?=NETCAT_MODULES_PARAM ?></th>
                    <th class='align-center' ><?=NETCAT_MODULES_VALUE ?></th>
                    <td class='align-center last_col'><div class='icons icon_delete' title='<?=CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div></td>
                </tr>

                <?php
                $ParamArray = ConvertToDiffer($Array["Parameters"]);

                foreach ($ParamArray as $k => $v) {
                    print "<tr>\n";
                    print " <td class='first_col'>".nc_admin_input_simple("Name_".$k."' style = 'width:100%; font-family: \"Courier New\", Courier, monospace'", $k)."</td>\n";
                    print " <td>".nc_admin_input_simple("Value_".$k."' style = 'width:100%; font-family: \"Courier New\",Courier,monospace'", $v)."</td>\n";
                    print " <td class='last_col'>".nc_admin_checkbox_simple("Delete_".$k)."</td>\n";
                    print "</tr>\n";
                }
                print "</tbody></table>\n";
                print "<input type='hidden' name='ModuleID' value=".$ModuleID.">\n";
                print "<input type='hidden' name='phase' value='3'>";
                print $nc_core->token->get_input();

                global $UI_CONFIG, $module_name;
                print "<input type='hidden' name='module_name' value='".$module_name."'></form>";

                $UI_CONFIG = new ui_config_module($module_name, 'settings');
                $UI_CONFIG->actionButtons[] = array("id" => "submit",
                        "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                        "action" => "mainView.submitIframeForm()");
                $UI_CONFIG->actionButtons[] = array("id" => "addparam",
                        "caption" => NETCAT_MODULES_ADDPARAM,
                        "align" => "left",
                        "action" => "document.getElementById('mainViewIframe').contentWindow.ModulesAddNewParam()");
            }

###############################################################

function AddModuleForm() {
    $maxfilesize = ini_get('upload_max_filesize');
    global $db, $UI_CONFIG, $nc_core, $maxfilesize;

    echo "<form enctype='multipart/form-data' action='index.php' method='POST''>";
    echo "<input type='hidden' name='MAX_FILE_SIZE' value='".$maxfilesize."'>";
    echo "<fieldset>";
    echo "<legend>".TOOLS_MODULES_MOD_LOCAL."</legend>";
    echo "<input size='60' name='FilePatch' type='file'>";
    echo "</fieldset>";
    echo "<input type='hidden' name='phase' value='4'>";
    echo "<input type='submit' class='hidden'>";
    echo $nc_core->token->get_input();
    echo "</form>";
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTROL_CLASS_IMPORT_UPLOAD,
            "action" => "mainView.submitIframeForm()");
}

/**
 * Функция сохранения настроек модуля
 *
 * @return boolean true
 */
function ModuleUpdateCompleted() {
    global $db;

    $Parameters = "";

    foreach ($_POST as $key => $val) {
        $key = trim($key);
        $val = trim($val);

        if (substr($key, 0, 4) == "Name") {
            if (isset($add) && isset($tmpParam)) $Parameters .= $tmpParam;
            $add = 1;
            $tmpParam = $val;
            $tmpParam .= "=";
            if (!$val) $add = 0;
        }
        else if (substr($key, 0, 5) == "Value") {
            $tmpParam .= $val;
            $tmpParam .= "\n";
        } else if (substr($key, 0, 6) == "Delete") {
            $add = 0;
        }
    }
    if ($add && $tmpParam) $Parameters .= $tmpParam;

    $ModuleID = intval($_POST['ModuleID']);

    $db->last_error = "";
    $db->query("UPDATE Module SET Parameters='".$db->escape($Parameters)."' WHERE Module_ID='".$ModuleID."'");

    if (!$db->last_error) {
        nc_print_status(TOOLS_MODULES_PREFS_SAVED, 'ok');
    } else {
        nc_print_status(TOOLS_MODULES_PREFS_ERROR, 'error');
    }

    return true;
}

function TitleModule() {
    global $perm;

    if (($perm->isSupervisor() || $perm->isGuest()))
            AddModuleForm ();
    ModuleList ();
}

/**
 * Convert parametr to diffrent parametrs
 * @param str
 * @return array, hash - name of param
 */
function ConvertToDiffer($parametrs) {
    $ret = Array();
    $parametr = explode("\n", $parametrs);

    foreach ($parametr as $v) {
        $param_name = trim(strtok($v, "="));
        $parm_value = trim(strtok(""));
        if ($param_name && substr($param_name, 0, 1) != '/' && substr($param_name, 0, 1) != '#')
                $ret[$param_name] = $parm_value;
    }

    return $ret;
}

/**
 * Включение\выключение модулей
 *
 * Порядок действий:
 * - загрузка всех модулей ( даже выключенных )
 * - определение, какие модули включились, какие выключились
 * - посылка событий
 *
 */
function ActionModulesCompleted() {
    global $nc_core, $language;
    $db = $nc_core->db;

    $nc_core->modules->load_env($language, false, true, true);
    //список всех модулей
    $modules = $nc_core->modules->get_data(1, 1);
    //настройки для текущего сайта
    $catalogue_id = nc_core("catalogue")->id();
    $modules_catalogue = $db->get_results("SELECT * FROM `Module_Catalog` WHERE `Catalogue_ID` = $catalogue_id", ARRAY_A);

    $had_module_status_changes = false;

    if (!empty($modules)) {
        foreach ($modules as $module) {
            // старое и новое значение Checked
            $old_value = (bool)$module['Checked'];
            $new_value = (bool)$nc_core->input->fetch_get_post('check'.$module['Module_ID']);
            $inside_admin = (int)$nc_core->input->fetch_get_post('inside_admin'.$module['Module_ID']);
            $inside_admin = $inside_admin ? 1 : 0;

            if ($catalogue_id) {
                //если нет настройки модуля для текущего каталога, то нужно создать
                $module_catalogue_exist = false;
                $module_catalogue_id = 0;
                foreach ((array)$modules_catalogue as $module_catalogue) {
                    if ($module_catalogue['Module_ID'] == $module['Module_ID']) {
                        $module_catalogue_exist = true;
                        $module_catalogue_id = $module_catalogue['ID'];
                    }
                }
                if (!$module_catalogue_exist) {
                    $db->query("INSERT INTO `Module_Catalog` SET
                        `Module_ID` = '{$module['Module_ID']}',
                        `Catalogue_ID` = $catalogue_id,
                        `Checked` = '".intval($new_value)."',
                        `Inside_Admin` = $inside_admin");
                        $module_catalogue_id = $db->insert_id;
                }

                $db->query("UPDATE `Module_Catalog` SET `Inside_Admin` = {$inside_admin} WHERE `ID` = '".$module_catalogue_id."'");
            } else {
                // Для "всех сайтов" нужно сохранить в таблице module
                $db->query("UPDATE `Module` SET `Inside_Admin` = {$inside_admin} WHERE `Module_ID` = '{$module['Module_ID']}'");
            }

            if ($old_value != $new_value) {
                $had_module_status_changes = true;
                // событие в(ы)ключение модуля
                $nc_core->event->execute($new_value ? nc_Event::BEFORE_MODULE_ENABLED : nc_Event::BEFORE_MODULE_DISABLED, $module['Keyword'], $catalogue_id);
                if ($catalogue_id) {
                    $db->query("UPDATE `Module_Catalog` SET `Checked` = '".intval($new_value)."' WHERE `ID` = '".$module_catalogue_id."'");
                } else {// Для "всех сайтов" нужно сохранить в таблице module
                    $db->query("UPDATE `Module` SET `Checked` = '".intval($new_value)."' WHERE `Module_ID` = '{$module['Module_ID']}'");
                }
                // событие в(ы)ключение модуля
                $nc_core->event->execute($new_value ? nc_Event::AFTER_MODULE_ENABLED : nc_Event::AFTER_MODULE_DISABLED, $module['Keyword'], $catalogue_id);
            }
        }
    }
    return $had_module_status_changes;
}

//FIXME: После ввода глобального переключателя сайтов метод и его вызовы (выше) можно удалять
/**
 * Поле выбора редактируемого сайта
 *
 * @param string $view текущее представление настроек
 *
 * @return string
 */
function module_catalogue_select_field() {
    static $options;

    if (is_null($options)) {
        $options = array('' => CONTROL_USER_SELECTSITEALL);
        $catalogues = nc_core("catalogue")->get_all();

        foreach ($catalogues as $id => $row) {
            $options[$id] = $id . '. ' . $row['Catalogue_Name'];
        }
    }

    $url = "index.php?phase=1&current_catalogue_id=";
    $field = nc_core("ui")->html->select('current_catalogue_id', $options, nc_core("catalogue")->id())
        ->attr('onchange', 'window.location.href="' . $url . '"+this.value');

    return "<div>{$field}</div>";
}