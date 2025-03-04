<?php

$action = 'add';

$NETCAT_FOLDER = join(strstr(__FILE__, '/') ? '/' : '\\', array_slice(preg_split('/[\/\\\]+/', __FILE__), 0, -2)) . (strstr(__FILE__, '/') ? '/' : '\\');
@include_once($NETCAT_FOLDER . 'vars.inc.php');
require_once $ROOT_FOLDER . 'connect_io.php';

$nc_core = nc_Core::get_object();
$nc_core->action = 'add';

require($INCLUDE_FOLDER . 'index.php');

ob_start();

do {
    // Выводить данные только одного инфоблока
    $cc_only = (int) $nc_core->input->fetch_get('cc_only');

    // security section
    $catalogue = (int) $catalogue;
    // $sub здесь — текущий раздел, с которого происходит редактирование — используется для загрузки настроек макета и т. п.;
    // для сквозного инфоблока не соответствует разделу, к которому относится инфоблок $cc (раздел 0)
    $sub = (int) $sub;
    $cc = (int) $cc;
    $classID = $class_id = (int) $classID;
    $curPos = (int) (isset($curPos) ? $curPos : null);

    $cc_env = $current_cc;

    $_db_cc = $cc;
    $_db_sub = $sub;
    $_db_catalogue = $catalogue;

    if ($current_cc['SrcMirror']) {
        $mirror_data = $nc_core->sub_class->get_by_id($current_cc['SrcMirror']);
        $cc = (int) $mirror_data['Sub_Class_ID'];
        $catalogue = (int) $mirror_data['Catalogue_ID'];
    }

    // $to_sub: фактический раздел инфоблока $cc (0 для сквозного инфоблока)
    $to_sub = $nc_core->sub_class->get_by_id($cc, 'Subdivision_ID');

    if (((int)$cc_env['Edit_Class_Template'] !== 0) && $admin_mode) {
        try {
            $cc_env = $nc_core->sub_class->get_by_id($cc, null, $cc_env['Edit_Class_Template']);
        } catch (Exception $e) {
            $warnText = $e->getMessage();
        }
    }
    if (((int)$cc_env['Admin_Class_Template'] !== 0) && $inside_admin) {
        try {
            $cc_env = $nc_core->sub_class->get_by_id($cc, null, $cc_env['Admin_Class_Template']);
        } catch (Exception $e) {
            $warnText = $e->getMessage();
        }
    }

    if (!isset($use_multi_sub_class)) {
        // subdivision multisubclass option
        $use_multi_sub_class = $nc_core->subdivision->get_current('UseMultiSubClass');
    }
    if ($use_multi_sub_class == 2) { //во вкладках
        $use_multi_sub_class = 0;
    }

    if ($classPreview == ($current_cc['Class_Template_ID'] ? $current_cc['Class_Template_ID'] : $current_cc['Class_ID'])) {
        $magic_gpc = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();
        $addTemplate = $magic_gpc ? stripslashes($_SESSION['PreviewClass'][$classPreview]['AddTemplate']) : $_SESSION['PreviewClass'][$classPreview]['AddTemplate'];
        $addCond = $magic_gpc ? stripslashes($_SESSION['PreviewClass'][$classPreview]['AddCond']) : $_SESSION['PreviewClass'][$classPreview]['AddCond'];
        $addActionTemplate = $magic_gpc ? stripslashes($_SESSION['PreviewClass'][$classPreview]['AddActionTemplate']) : $_SESSION['PreviewClass'][$classPreview]['AddActionTemplate'];
    }

    $alter_goBackLink = '';
    $alter_goBackLink_true = false;

    if (isset($_REQUEST['goBackLink'])) {
        $alter_goBackLink = $_REQUEST['goBackLink'];
        if ($admin_mode && preg_match('/^[\/a-z0-9_-]+\?catalogue=[[:digit:]]+&sub=[[:digit:]]+&cc=[[:digit:]]+(&curPos=[[:digit:]]{0,12})?$/i', $alter_goBackLink)) {
            $alter_goBackLink_true = true;
        }
        if (!$admin_mode && preg_match('/^[\/a-z0-9_-]+(\.html)?(\?curPos=[[:digit:]]{0,12})?$/i', $alter_goBackLink)) {
            $alter_goBackLink_true = true;
        }
    }

    if (!$alter_goBackLink_true) {
        if ($admin_mode) {
            $goBackLink = $admin_url_prefix . '?catalogue=' . $catalogue . '&sub=' . $sub . '&cc=' . $cc . '&curPos=' . $curPos;
        } else {
            $goBackLink = ($user_table_mode ? nc_folder_url($current_sub['Subdivision_ID']) : nc_infoblock_url($current_cc['Sub_Class_ID'])
              ) .
              ($curPos ? '?curPos=' . $curPos : '');
        }
    } else {
        $goBackLink = $alter_goBackLink;
    }

    $goBack = '<a href="' . $goBackLink . '">' . NETCAT_MODERATION_BACKTOSECTION . '</a>';

    $cc_settings = nc_array_value($cc_env, 'Sub_Class_Settings');

    $nc_core->page->set_current_metatags($current_sub);
    if (!isset($posting)) {
        $posting = null;
    }
    if ($posting && $nc_core->token->is_use($action)) {
        if (!$nc_core->token->verify()) {
            echo NETCAT_TOKEN_INVALID;
            break;
        }
    }

    if (!isset($cc_env['File_Mode'])) {
        try {
            $Class_Template_ID = nc_Core::get_object()->sub_class->get_by_id($cc, 'Class_Template_ID');
        } catch (Exception $e) {
            $posting = 0;
        }
        if (is_array($cc_env)) {
            $cc_env = array_merge($cc_env, nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID));
        } else {
            $cc_env = nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID);
        }
    }

    if ($cc_env['File_Mode']) {
        $file_class = new nc_tpl_component_view($CLASS_TEMPLATE_FOLDER, $db);
        $file_class->load($cc_env['Real_Class_ID'], $cc_env['File_Path'], $cc_env['File_Hash']);
        $file_class->include_all_required_assets();

        require $INCLUDE_FOLDER . 'classes/nc_class_aggregator_editor.class.php';
        $nc_class_aggregator = nc_class_aggregator_editor::init($file_class);
        if (is_object($nc_class_aggregator) && !empty($_REQUEST['nc_get_message_select'])) {
            if (!$nc_class_aggregator->ignore_catalogue) {
                $nc_class_aggregator->catalogue_id = $cc_env['Catalogue_ID'];
            }

            ob_clean();
            echo $nc_class_aggregator->get_message_select(+$_REQUEST['db_Class_ID'], (array) $_POST['nc_select_attrs'], (array) $_POST['nc_option_attrs'], +$_REQUEST['db_selected']);
            exit;
        }
    }

    if ($posting) {
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_field_path('AddCond');
            $nc_field_path = $file_class->get_field_path('AddCond');

            try {
                if (PHP_MAJOR_VERSION === 5) {
                    nc_check_php_file($nc_field_path);
                }
                if ((include $nc_field_path) === false) {
                    throw new nc_tpl_exception_include($nc_field_path);
                }
            } catch (Throwable $e) { // PHP 7
                // do not post this
                $posting = 0;
                $file_class->print_error_message($nc_field_path, $e);
            } catch (Exception $e) { // PHP 5
                // do not post this
                $posting = 0;
                $file_class->print_error_message($nc_field_path, $e);
            }

            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
            eval(nc_check_eval($addCond));
        }
    }

    require $ROOT_FOLDER . 'message_fields.php';

    if (!isset($f_Priority)) { $f_Priority = null; }
    if (!isset($f_ncTitle)) { $f_ncTitle = null; }
    if (!isset($f_ncKeywords)) { $f_ncKeywords = null; }
    if (!isset($f_ncDescription)) { $f_ncDescription = null; }

    if (isset($f_Parent_Message_ID)){
        $f_Parent_Message_ID = (int) $f_Parent_Message_ID;
    }
    else {
        $f_Parent_Message_ID = 0;
    }

    if (!$cc_only) {
        if (!$posting) {
            if ($cc_env['File_Mode']) {
                $addTemplate = file_get_contents($file_class->get_field_path('AddTemplate'));
            }

            if (!empty($addTemplate)) {
                if ($cc_env['File_Mode']) {
                    // обертка для вывода ошибки в админке
                    if (!empty($warnText) && ($nc_core->inside_admin || !empty($isNaked))) {
                        ob_start();
                        nc_print_status($warnText, 'error');
                        $warnText = ob_get_clean();
                    }

                    $nc_parent_field_path = $file_class->get_parent_field_path('AddTemplate');
                    $nc_field_path = $file_class->get_field_path('AddTemplate');

                    ob_start();
                    try {
                        if (PHP_MAJOR_VERSION === 5) {
                            nc_check_php_file($nc_field_path);
                        }
                        if ((include $nc_field_path) === false) {
                            throw new nc_tpl_exception_include($nc_field_path);
                        }
                    } catch (Throwable $e) { // PHP 7
                        $file_class->print_error_message($nc_field_path, $e);
                    } catch (Exception $e) { // PHP 5
                        $file_class->print_error_message($nc_field_path, $e);
                    }
                    $addForm = ob_get_clean();

                    $nc_parent_field_path = null;
                    $nc_field_path = null;
                } else {
                    eval(nc_check_eval("\$addForm = \"" . $addTemplate . "\";"));
                }
                if (!isset($sys_table_id)) {
                    $sys_table_id = null;
                }
                echo nc_prepare_message_form($addForm, $action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc, $f_Checked = null, $f_Priority = '', $f_Keyword = '', $f_ncTitle = '', $f_ncKeywords = '', $f_ncDescription = '');
            } else {
                require($ROOT_FOLDER . 'message_edit.php');
            }


            if ($inside_admin && !empty($UI_CONFIG) && $goBackLink) {
                $UI_CONFIG->actionButtons[] = array('id' => 'goback',
                  'caption' => CONTROL_AUTH_HTML_BACK,
                  'align' => 'left',
                  'action' => "mainView.loadIframe('" . $goBackLink . "&inside_admin=1')");
            }
        } else {
            if ($systemTableID == '3') {
                $message = null;
            }

            include($ROOT_FOLDER . 'message_put.php');
            $IsChecked = 2 - $moderationID;

            if ($admin_mode) {
                $IsChecked = $f_Checked ? 1 : 0;
            }

            $nc_multifield_field_names = $nc_core
                ->get_component($user_table_mode ? 'User' : $classID)
                ->get_fields(NC_FIELDTYPE_MULTIFILE, false);

            if (!$user_table_mode) {
                // check permission
                if (!(
                  $cc_env['Write_Access_ID'] == 1 ||
                  ($cc_env['Write_Access_ID'] == 2 && $AUTH_USER_ID) ||
                  ($perm instanceof Permission && $perm->isSubClass($cc, MASK_ADD))
                  )
                ) {
                    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
                } else {
                    $fieldString .= '`Created`, `Parent_Message_ID`, `IP`, `UserAgent`, ';
                    $valueString .= "\"" . date("Y-m-d H:i:s") . "\", \"" . $f_Parent_Message_ID . "\", \"" . $db->escape($REMOTE_ADDR) . "\", \"" . $db->escape($HTTP_USER_AGENT) . "\", ";

                    if ($admin_mode && isset($f_Keyword) && strlen($f_Keyword)) {
                        $KeywordStr = "'" . nc_check_keyword_name(0, $f_Keyword, $classID, $sub) . "'";
                    } else if (isset($KeywordDefined) && isset($KeywordNewValue)) {
                        $KeywordStr = $KeywordNewValue; // уже содержит кавычки
                    } else {
                        $KeywordStr = "''";
                    }
                    if (!isset($f_ncDemoContent)) {
                        $f_ncDemoContent = null;
                    }
                    $f_ncDemoContent = (int)$f_ncDemoContent;
                    $nc_mode = $nc_core->catalogue->get_by_id((int)$catalogue, 'ncMode');
                    if ($f_ncDemoContent != 1 || $perm->isSupervisor() != true || ($nc_mode != 'template_development' && $nc_mode != 'demo')) {
                        $f_ncDemoContent = 0;
                    }

                    $f_ncFullPageMode = isset($f_ncFullPageMode) ? $db->escape($f_ncFullPageMode) : 'inherit';

                    $SQL = "INSERT INTO `Message" . $classID . "`
                            (`Subdivision_ID`, `Sub_Class_ID`, " . $fieldString . " `Checked`, `Keyword`, `User_ID`, `ncDemoContent`, `ncFullPageMode`)
                            VALUES
                            ($to_sub, $cc, $valueString $IsChecked, $KeywordStr, $AUTH_USER_ID, $f_ncDemoContent, '$f_ncFullPageMode')";

                    // execute core action
                    $nc_core->event->execute(nc_Event::BEFORE_OBJECT_CREATED, $catalogue, $to_sub, $cc, $classID, 0);

                    $resMsg = $db->query($SQL);
                    $msgID = $db->insert_id;

                    foreach ($nc_multifield_field_names as $nc_multifield_field_name) {
                        nc_multifield_saver::save_from_post_data($classID, $msgID, ${"f_{$nc_multifield_field_name}"}, true);
                    }

                    if (!empty($f_Priority)) {
                        $f_Priority = $f_Priority + 0;
                        if ($admin_mode) {
                            // get ids
                            $_messages = $db->get_col("SELECT `Message_ID` FROM `Message" . $classID . "`
				  WHERE `Priority`>=" . $f_Priority . " AND `Subdivision_ID` = '" . $to_sub . "' AND `Sub_Class_ID` = '" . $cc . "'");
                            // update info
                            if (!empty($_messages)) {
                                // execute core action
                                $nc_core->event->execute(nc_Event::BEFORE_OBJECT_UPDATED, $catalogue, $to_sub, $cc, $classID, $_messages);

                                $res = $db->query("UPDATE `Message" . $classID . "`
					SET `Priority` = `Priority` + 1, `LastUpdated` = `LastUpdated`
					WHERE `Message_ID` IN (" . join(", ", $_messages) . ")");
                                // execute core action
                                $nc_core->event->execute(nc_Event::AFTER_OBJECT_UPDATED, $catalogue, $to_sub, $cc, $classID, $_messages);
                            }
                            // for current message
                            $res = $db->query("UPDATE `Message" . $classID . "`
				  SET `Priority` = '" . $f_Priority . "', `LastUpdated` = `LastUpdated`
				  WHERE `Message_ID` = '" . $msgID . "'");
                        }
                    } else {
                        $maxPriority = $db->get_var("SELECT MAX(`Priority`) FROM `Message" . $classID . "`
						WHERE `Subdivision_ID` = '" . $to_sub . "' AND `Sub_Class_ID` = '" . $cc . "' AND `Parent_Message_ID` = '" . $f_Parent_Message_ID . "'");
                        $res = $db->query("UPDATE `Message" . $classID . "`
						SET `Priority` = " . ($maxPriority + 1) . ", `LastUpdated` = `LastUpdated`
						WHERE `Message_ID` = '" . $msgID . "'");
                    }
                    // execute core action
                    $nc_core->event->execute(nc_Event::AFTER_OBJECT_CREATED, $catalogue, $to_sub, $cc, $classID, $msgID);
                }
            } else {
                $RegistrationCode = md5(uniqid(rand()));
                $IsChecked = ($nc_core->get_settings('premoderation', 'auth') || $nc_core->get_settings('confirm', 'auth')) ? 0 : 1;
                $groups = explode(',', $nc_core->get_settings('group', 'auth'));
                $mainGroup = intval(min((array) $groups));

                // execute core action
                $nc_core->event->execute(nc_Event::BEFORE_USER_CREATED, 0);

                $resMsg = $db->query("INSERT INTO `User`
    			(" . $fieldString . "`Password`, `PermissionGroup_ID`, `Checked`, `Created`, `RegistrationCode`" . ($nc_core->get_settings('confirm', 'auth') ? ", `Confirmed`" : "") . ", Catalogue_ID)
    			VALUES
    			(" . $valueString . " " . $nc_core->MYSQL_ENCRYPT . "('" . $Password . "'), '" . $mainGroup . "', '" . $IsChecked . "', \"" . date("Y-m-d H:i:s") . "\", '" . $RegistrationCode . "'" . ($nc_core->get_settings('confirm', 'auth') ? ",'0'" : "") . ", " . $catalogue . ")");
                $msgID = $db->insert_id;

                foreach ($nc_multifield_field_names as $nc_multifield_field_name) {
                    nc_multifield_saver::save_from_post_data('User', $msgID, ${"f_{$nc_multifield_field_name}"}, true);
                }

                //add user to group
                if ($msgID) {
                    foreach ((array) $groups as $group_id) {
                        nc_usergroup_add_to_group($msgID, $group_id);
                    }
                }

                // execute core action
                $nc_core->event->execute(nc_Event::AFTER_USER_CREATED, $msgID);

                $ConfirmationLink = nc_get_scheme() . '://' . $HTTP_HOST . nc_module_path('auth') . 'confirm.php?id=' . $msgID . '&code=' . $RegistrationCode;
            }
            if (!$message) {
                $message = $msgID;
            }

            unset($nc_multifield_field_names);

            //постобработка файлов с учетом нового $message
            $extract_fields = $nc_core->files->field_save_file_afteraction($message);
            extract($extract_fields); //f_field_url для простой ФС

            if (nc_module_check_by_keyword('comments')) {
                // get rule id
                $CommentData = nc_comments::getRuleData($db, array($catalogue, $to_sub, $cc, $message));
                $CommentRelationID = nc_array_value($CommentData, 'ID');
                $comm_env = array($catalogue, $to_sub, $cc, $message);
                if (!isset($CommentAccessID)) {
                    $CommentAccessID = null;
                }
                // do something
                switch (true) {
                    case $CommentAccessID > 0 && $CommentRelationID:
                        // update comment rules
                        nc_comments::updateRule($db, $comm_env, $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                        break;
                    case $CommentAccessID > 0 && !$CommentRelationID:
                        // add comment relation
                        $CommentRelationID = nc_comments::addRule($db, $comm_env, $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                        break;
                    case $CommentAccessID <= 0 && $CommentRelationID:
                        // delete comment rules
                        nc_comments::dropRule($db, $comm_env);
                        $CommentRelationID = 0;
                        break;
                }
            }

            if ($resMsg) {
                if ($cc && !$user_table_mode && $IsChecked && $MODULE_VARS['subscriber'] && (!$MODULE_VARS['subscriber']['VERSION'] || $MODULE_VARS['subscriber']['VERSION'] == 1)
                ) {
                    eval(nc_check_eval("\$mailbody = \"" . $subscribeTemplate . "\";"));
                    subscribe_sendmail($cc, $mailbody);
                }

                if ($cc_env['File_Mode']) {
                    $nc_parent_field_path = $file_class->get_parent_field_path('AddActionTemplate');
                    $nc_field_path = $file_class->get_field_path('AddActionTemplate');
                    $action_exists = filesize($nc_field_path) > 0 ? true : false;
                }

                if ($nc_core->subdivision->get_current('Template_ID') == $template) {
                    $template_settings = $nc_core->subdivision->get_template_settings($sub);
                } else if (isset($nc_use_site_template_settings) && $nc_use_site_template_settings) {
                    // используется в скриптах modules/auth
                    $template_settings = $nc_core->catalogue->get_template_settings($catalogue);
                } else {
                    $template_settings = $nc_core->template->get_settings_default_values($template);
                }

                if ($cc_env['File_Mode'] && $action_exists) {
                    try {
                        if (PHP_MAJOR_VERSION === 5) {
                            nc_check_php_file($nc_field_path);
                        }
                        if ((include $nc_field_path) === false) {
                            throw new nc_tpl_exception_include($nc_field_path);
                        }
                    } catch (Throwable $e) { // PHP 7
                        $file_class->print_error_message($nc_field_path, $e);
                    } catch (Exception $e) { // PHP 5
                        $file_class->print_error_message($nc_field_path, $e);
                    }
                    $nc_parent_field_path = null;
                    $nc_field_path = null;
                } else if ($addActionTemplate) {
                    eval(nc_check_eval("echo \"" . $addActionTemplate . "\";"));
                } else {
                    if ($inside_admin) {
                        ob_end_clean();
                        header('Location: ' . $goBackLink . '&inside_admin=1');
                        exit;
                    } else {
                        echo ($IsChecked ? NETCAT_MODERATION_MSG_OBJADD : NETCAT_MODERATION_MSG_OBJADDMOD) . '<br/><br/>' . $goBack;
                    }
                }
            } else {
                echo NETCAT_MODERATION_ERROR_NOOBJADD . '<br/><br/>' . $goBack;
            }
        }
    }
    $cc_add = $cc;
    if (count($cc_array) > 1 && $use_multi_sub_class && !$inside_admin && !$nc_core->input->fetch_get_post('admin_modal')) {
        foreach ($cc_array AS $cc) {
            if ($cc_only && $cc_only != $cc) {
                continue;
            }
            if (($cc && $cc != $cc_add) || $user_table_mode) {
                $current_cc = $nc_core->sub_class->set_current_by_id($cc);
                echo s_list_class($sub, $cc, $parsed_url['query'] . ($date ? '&date=' . $date : '') . '&isMainContent=1&isSubClassArray=1');
            }
        }
        $current_cc = $nc_core->sub_class->set_current_by_id($cc_add);
    }
} while (false);

$nc_result_msg = ob_get_clean();
$nc_core->page->is_processing_template_now();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER . 'index_fs.inc.php';
}

if (!$File_Mode || $templatePreview) {
    nc_evaluate_template($template_header, $template_footer, $File_Mode);
}

// выполнить необходимую обработку кода страницы и отдать результат пользователю:
$nc_core->output_page($template_header, $nc_result_msg, $template_footer);
