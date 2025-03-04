<?php

/**
 * Функция выполнения запроса к БД, вывод результатов через $tempate
 *
 * @param string MySQL запрос
 * @param string шаблон для повторения
 * @param string разделитель между строками
 * @return string
 */
function listQuery($query, $template = "", $divider = '') {
    global $db, $SHOW_MYSQL_ERRORS, $perm;

    # скроем ошибки в случае неправильного запроса, чтобы вывести свое сообщение об ошибке
    $db->hide_errors();

    $db->last_error = '';
    $db->num_rows = 0;
    # выполним запрос
    $res = $db->get_results($query, ARRAY_A);

    # покажем ошибку, если есть
    if ($db->last_error && $perm instanceof Permission && $perm->isSupervisor()) {
        $num_error = sizeof($db->captured_errors) - 1; // нужно узнать номер последней ошибки
        $result = "<hr size='1' style='color:#CCCCCC' noshade><b>Query:</b> " . $db->captured_errors[$num_error]['query'] . "<br/><br/>\r\n<b>Error:</b> " . $db->captured_errors[$num_error]['error_str'] . "<hr size='1' style='color:#CCCCCC' noshade><br/>";
    }

    # если показ ошибок MySQL включен
    if ($SHOW_MYSQL_ERRORS === 'on' && $perm instanceof Permission && $perm->isSupervisor()) {
        $db->show_errors();
    }

    # количество записей
    $cnt = $db->num_rows;
    $result = '';
    # основной цикл
    if ($cnt && $template) {
        for ($i = 0; $i < $cnt; $i++) {
            $data = $res[$i];
            eval(nc_check_eval("\$result.= \"$template\";"));
            // для послднего элемента разделитель не нужен
            if ($i <> $cnt - 1) {
                $result .= $divider;
            }
        }
    }

    return $result;
}

/**
 * DEPRECATED - left for compatibility
 * Функция генерации формы добавления, редактирования, поиска, удаления в зависимости от $action
 *
 * @param string $action "add", "change", "search", "message"
 * @param array $fields массив с полями
 * @param int $class_id
 * @param null|array $filter_addition_fields
 * @return string форма
 */
function nc_fields_form($action, $fields = null, $class_id = 0, $filter_addition_fields = null) {
    global $ROOT_FOLDER, $MODULE_VARS, $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_PATH;
    global $AUTHORIZE_BY, $systemTableID, $user_table_mode, $admin_mode, $nc_core;

    if (!in_array($action, array('add', 'change', 'search', 'message'), true)) {
        return false;
    }

    if (!empty($fields)) {
        $GLOBALS['fldCount'] = count($fields);
        $GLOBALS['fldID'] = array();
        $GLOBALS['fld'] = array();
        $GLOBALS['fldName'] = array();
        $GLOBALS['fldType'] = array();
        $GLOBALS['fldFmt'] = array();
        $GLOBALS['fldNotNull'] = array();
        $GLOBALS['fldDefault'] = array();
        $GLOBALS['fldTypeOfEdit'] = array();
        $GLOBALS['fldDoSearch'] = array();
        foreach ($fields as $v) {
            $GLOBALS['fldID'][] = $v['id'];
            $GLOBALS['fld'][] = $v['name'];
            $GLOBALS['fldName'][] = $v['description'];
            $GLOBALS['fldType'][] = $v['type'];
            $GLOBALS['fldFmt'][] = $v['format'];
            $GLOBALS['fldNotNull'][] = $v['not_null'];
            $GLOBALS['fldDefault'][] = $v['default'];
            $GLOBALS['fldTypeOfEdit'][] = $v['edit_type'];
            $GLOBALS['fldDoSearch'][] = $v['search'];
        }
    }
    if (is_array($filter_addition_fields) && count($filter_addition_fields) > 0) {
        foreach ($filter_addition_fields as $v) {
            $fldAddName[] = $v['description'];
            $fldAddType[] = $v['type'];
            $fldAddFmt[] = $v['format'];
            $fldAddDoSearch[] = $v['search'];
        }
    }

    if (isset($GLOBALS['fld']) && is_array($GLOBALS['fld'])) {
        $fldCount = $GLOBALS['fldCount'];
        $fldID = $GLOBALS['fldID'];
        $fld = $GLOBALS['fld'];
        $fldName = $GLOBALS['fldName'];
        $fldValue = isset($GLOBALS['fldValue']) ? $GLOBALS['fldValue'] : '';
        $fldType = $GLOBALS['fldType'];
        $fldFmt = $GLOBALS['fldFmt'];
        $fldNotNull = $GLOBALS['fldNotNull'];
        $fldInheritance = isset($GLOBALS['fldInheritance']) ? $GLOBALS['fldInheritance'] : 0;
        $fldDefault = $GLOBALS['fldDefault'];
        $fldTypeOfEdit = $GLOBALS['fldTypeOfEdit'];
        $fldDoSearch = $GLOBALS['fldDoSearch'];
    }
    //инициализация пустыми строками в случае, если условие не выполнено (для PHP 8.2)
    else {
        $fldCount = '';
        $fldID = '';
        $fld = '';
        $fldName = '';
        $fldValue = '';
        $fldType = '';
        $fldFmt = '';
        $fldNotNull = '';
        $fldInheritance = '';
        $fldDefault = '';
        $fldTypeOfEdit =  '';
        $fldDoSearch =  '';
    }

    $result = '';

    // проверяем принадлежность поля к системной таблице
    if ($systemTableID && !empty($class_id) && isset($fields[0]['id'])) {
        if ($fields[0]['system_table_id'] == $systemTableID) {
            $ignore_system = false;
        } else if ($fields[0]['class_id'] == $class_id) {
            $ignore_system = true;
        }
    }
    if (!isset($ignore_system)) {
        $ignore_system = null;
    }
    if ($action === 'add' && !$ignore_system && $systemTableID && $user_table_mode) {
        $nc_auth = nc_auth::get_object();
        return $nc_auth->add_form();
    }

    switch ($action) {
        case 'add':
        case 'change':
            if (!$systemTableID || ($systemTableID && $user_table_mode)) {
                # начало вывода формы
                $result .= "\".( \$warnText ? \"<div class='warnText'>\$warnText</div>\" : NULL ).\"\r\n";
                $result .= "<form name='adminForm' class='nc-form' id='adminForm' enctype='multipart/form-data' method='post' action='\".\$SUB_FOLDER.\$HTTP_ROOT_PATH.\"" . ($action === 'add' ? 'add' : ($action === 'change' ? 'message' : '')) . ".php'>\r\n";
                # основной префикс формы

                $result .= "<div id='nc_moderate_form'>\r\n<div class='nc_clear'></div>\r\n";

                $result .= "<input name='admin_mode' type='hidden' value='\$admin_mode' />\r\n";
                $result .= "\".\$nc_core->token->get_input().\" \r\n";
                $result .= "<input name='catalogue' type='hidden' value='\$catalogue' />\r\n";
                $result .= "<input name='cc' type='hidden' value='\$cc' />\r\n";
                $result .= "<input name='sub' type='hidden' value='\$sub' />\r\n";
                $result .= ($action === 'change' ? "<input name='message' type='hidden' value='\$message' />\r\n" : "");
                $result .= "<input name='posting' type='hidden' value='1' />\r\n";
                $result .= "<input name='curPos' type='hidden' value='\$curPos' />\r\n";
                $result .= "<input name='f_Parent_Message_ID' type='hidden' value='\$f_Parent_Message_ID' />\r\n";


                # префикс формы для админского режима
                $result .= "\".nc_form_moderate('" . $action . "', \$admin_mode, " . ($user_table_mode + 0) . ", \$systemTableID, \$current_cc, (isset(\$f_Checked) ? \$f_Checked  : null), \$f_Priority , \$f_Keyword, \$f_ncTitle, \$f_ncKeywords, \$f_ncDescription ).\"\r\n";
                $result .= "</div>\r\n\r\n";

            }

            for ($i = 0; $i < $fldCount; $i++) {
                if ($fld[$i] === 'ncSMO_Image') {
                    continue; // не выводится в форме по умолчанию, даже если есть в $fld
                }

                # описание поля
                $fldNameTempl = $fldName[$i] . ($fldNotNull[$i] ? ' (*)' : '') . ":<br />\r\n";

                # редактировать поле могут:
                $no_edit = $fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE;
                $admin_edit = $fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_ADMIN;

                if ($user_table_mode && $action === 'change' && $fld[$i] === $AUTHORIZE_BY && !$nc_core->get_settings('allow_change_login', 'auth')) {
                    $no_edit = true;
                }
                # если поле не для редактирования - хендовер
                if ($no_edit) {
                    continue;
                }

                $field_html = '';
                switch ($fldType[$i]) {
                    case NC_FIELDTYPE_STRING:
                        $format_string = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_STRING);
                        $fldFmt[$i] = $format_string['format'];
                        $field_html .= "<div class='nc-field nc-field-type-string'>\".nc_string_field(\"" . $fld[$i] . "\", \"maxlength='255' size='50'\", " . ($class_id ?: "\$classID") . ", 1, '', false, null, " . ($format_string['protect_email'] ? '1' : '0') . ").\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_INT:
                        $field_html .= "<div class='nc-field nc-field-type-int'>\".nc_int_field(\"" . $fld[$i] . "\", \"maxlength='12' size='12'\", " . ($class_id ?: "\$classID") . ", 1).\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_TEXT:
                        $field_html .= "<div class='nc-field nc-field-type-text'>\".nc_text_field(\"" . $fld[$i] . "\", \"\", " . ($class_id ?: "\$classID") . ", 1).\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_SELECT:
                        $field_html .= "<div class='nc-field nc-field-type-select'>\".nc_list_field(\"" . $fld[$i] . "\", \"\", " . ($class_id ?: "\$classID") . ", 1).\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_BOOLEAN:
                        $field_html .= "<div class='nc-field nc-field-type-boolean'>\".nc_bool_field(\"" . $fld[$i] . "\", \"\", " . ($class_id ?: "\$classID") . ", 1).\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_FILE:
                        $field_html .= "<div class='nc-field nc-field-type-file'>\".nc_file_field(\"" . $fld[$i] . "\", \"size='50'\", " . ($class_id ?: "\$classID") . ", 1).\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_FLOAT:
                        $field_html .= "<div class='nc-field nc-field-type-float'>\".nc_float_field(\"" . $fld[$i] . "\", \"maxlength='12' size='12'\", " . ($class_id ?: "\$classID") . ", 1).\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_DATETIME:
                        $field_html .= "<div class='nc-field nc-field-type-datetime'>\".nc_date_field(\"" . $fld[$i] . "\", \"\", " . ($class_id ?: "\$classID") . ", 1).\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_RELATION:
                        $field_html .= "<div class='nc-field nc-field-type-relation'>\".nc_related_field(\"" . $fld[$i] . "\").\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_MULTISELECT:
                        $field_html .= "<div class='nc-field nc-field-type-multiselect'>\".nc_multilist_field(\"" . $fld[$i] . "\", \"\", \"\", " . ($class_id ?: "\$classID") . ", 1).\"</div>\r\n";
                        break;
                    case NC_FIELDTYPE_MULTIFILE:
                        $field_html .= "<div class='nc-field nc-field-type-multifile'>\".\$f_{$fld[$i]}->form().\"</div>\r\n";
                        break;
                }

                if ($admin_edit) {
                    $field_html = "\".( nc_field_check_admin_perm() ? \"\n" . $field_html . "\" : \"\" ).\"\r\n";
                }

                $result .= $field_html . "\r\n";
            }

            if (!$systemTableID || ($systemTableID && $user_table_mode)) {
                switch ($action) {
                    case 'add':
                        $submitBtnName = 'NETCAT_MODERATION_BUTTON_ADD';
                        break;
                    case 'change':
                        $submitBtnName = 'NETCAT_MODERATION_BUTTON_CHANGE';
                        break;
                }
                $resetBtnName = 'NETCAT_MODERATION_BUTTON_RESET';

                if ($user_table_mode && isset($posting) && $posting == 0 && $action === 'add' && !$ignore_system) {
                    $result .= NETCAT_MODERATION_PASSWORD . ":<br/><input name='Password1' type='password' size='25' maxlength='32' value='' /><br/><br/>";
                    $result .= NETCAT_MODERATION_PASSWORDAGAIN . ":<br/><input name='Password2' type='password' size='25' maxlength='32' value='' /><br/><br/>";
                }

                # защита картинкой
                if ($action === 'add' && $MODULE_VARS['captcha']) {
                    $result .= "\".(!\$AUTH_USER_ID && \$current_cc['UseCaptcha'] && \$MODULE_VARS['captcha'] ? nc_captcha_formfield().\"<br/><br/>\".NETCAT_MODERATION_CAPTCHA.\" (*):<br/><input type='text' name='nc_captcha_code' size='10'><br/><br/>\" : \"\").\"\r\n";
                }

                $result .= "<div class='nc-hint nc-hint-required-fields'>\".NETCAT_MODERATION_INFO_REQFIELDS.\"</div>\r\n";
                $result .= "\".nc_submit_button(" . $submitBtnName . ").\"\r\n";
                $result .= "</form>";
            }
            break;
        case 'search':
            # функция генерации формы поиска из файла "/require/s_list.inc.php"
            # для работы нужны данные из "message_fields.php"
            $srchFrm = showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt);
            if (is_array($filter_addition_fields) && count($filter_addition_fields) > 0) {
                $srchAddFrm = showSearchForm($fldAddName, $fldAddType, $fldAddDoSearch, $fldAddFmt, true);
            }
            // если нет полей для поиска
            if (empty($srchFrm) && empty($srchAddFrm)) {
                return '';
            }
            $form_action = "\" . (\$admin_mode ? \$HTTP_ROOT_PATH . \"index.php?\" : nc_infoblock_path(\$current_cc['Sub_Class_ID'])) . \"";

            $result .= "<form action='" . $form_action . "' method='get'>\r\n";
            $result .= "<input type='hidden' name='action' value='index' />\r\n";
            $result .= "<input type='hidden' name='admin_mode' value='\".\$admin_mode.\"' />\r\n";
            $result .= " \".( \$inside_admin ? \"<input type='hidden' name='inside_admin' value='1' />\r\n<input type='hidden' name='cc' value='\".\$cc.\"' />\r\n<input type='hidden' name='list_mode' value='\".\$list_mode.\"' />\r\n\" : \"\").\" ";
            if (isset($srchAddFrm)) {
                $result .= $srchAddFrm;
            }
            $result .= $srchFrm;
            $result .= "<input value='\".NETCAT_SEARCH_FIND_IT.\"' type='submit' />\r\n";
            $result .= "</form>";
            break;
        case 'message':
            $result = "\";\r\n" .
                "\$f_delete_true = \$admin_mode\r\n" .
                "  ? \$admin_url_prefix.\"message.php?" . ($nc_core->token->is_use('drop') ? "\".\$nc_core->token->get_url().\"&amp;" : "") . "catalogue=\".\$catalogue.\"&sub=\".\$sub.\"&cc=\".\$cc.\"&message=\".\$message.\"&delete=1&posting=1&curPos=\".\$curPos.\"&admin_mode=1\".\$system_env['AdminParameters']\r\n" .
                "  : nc_object_path(\$current_cc['Class_ID'], \$message, 'drop', 'html', false, array('nc_token' => \$nc_core->token->get()));\r\n" .
                "\$result .= sprintf(NETCAT_MODERATION_WARN_COMMITDELETION, \$message).\"<br/><br/>\r\n";
            $result .= "<a href='\".\$f_delete_true.\"'>\".NETCAT_MODERATION_COMMON_KILLONE.\"</a> | <a href='\".\$goBackLink.\$system_env['AdminParameters'].\"'>\".NETCAT_MODERATION_BACKTOSECTION.\"</a>\r\n";
            break;
    }

    return $result;
}

/**
 * Функция генерации формы добавления, редактирования, поиска, удаления в зависимости от $action
 *
 * @param string "add", "change", "search", "message"
 * @param array $fields массив с полями
 * @return string форма
 */
function nc_fields_form_fs($action, $fields = null, $class_id = 0) {
    global $ROOT_FOLDER, $MODULE_VARS, $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_PATH;
    global $AUTHORIZE_BY, $systemTableID, $user_table_mode, $admin_mode, $nc_core;

    if (!in_array($action, array('add', 'change', 'search', 'message'), true)) {
        return false;
    }

    if (!empty($fields)) {
        $GLOBALS['fldCount'] = count($fields);
        $GLOBALS['fldID'] = array();
        $GLOBALS['fld'] = array();
        $GLOBALS['fldName'] = array();
        $GLOBALS['fldType'] = array();
        $GLOBALS['fldFmt'] = array();
        $GLOBALS['fldNotNull'] = array();
        $GLOBALS['fldDefault'] = array();
        $GLOBALS['fldTypeOfEdit'] = array();
        $GLOBALS['fldDoSearch'] = array();
        foreach ($fields as $v) {
            $GLOBALS['fldID'][] = $v['id'];
            $GLOBALS['fld'][] = $v['name'];
            $GLOBALS['fldName'][] = $v['description'];
            $GLOBALS['fldType'][] = $v['type'];
            $GLOBALS['fldFmt'][] = $v['format'];
            $GLOBALS['fldNotNull'][] = $v['not_null'];
            $GLOBALS['fldDefault'][] = $v['default'];
            $GLOBALS['fldTypeOfEdit'][] = $v['edit_type'];
            $GLOBALS['fldDoSearch'][] = $v['search'];
        }
    }

    if (isset($GLOBALS['fld']) && is_array($GLOBALS['fld'])) {
        $fldCount = $GLOBALS['fldCount'];
        $fldID = $GLOBALS['fldID'];
        $fld = $GLOBALS['fld'];
        $fldName = $GLOBALS['fldName'];
        $fldValue = isset($GLOBALS['fldValue']) ? $GLOBALS['fldValue'] : '';
        $fldType = $GLOBALS['fldType'];
        $fldFmt = $GLOBALS['fldFmt'];
        $fldNotNull = $GLOBALS['fldNotNull'];
        $fldInheritance = isset($GLOBALS['fldInheritance']) ? $GLOBALS['fldInheritance'] : 0;
        $fldDefault = $GLOBALS['fldDefault'];
        $fldTypeOfEdit = $GLOBALS['fldTypeOfEdit'];
        $fldDoSearch = $GLOBALS['fldDoSearch'];
    }

    $result = '';

    if ($action === 'add' && $systemTableID && $user_table_mode) {
        $nc_auth = nc_auth::get_object();
        return $nc_auth->add_form_fs();
    }

    switch ($action) {
        case 'add':
        case 'change':
            if (!$systemTableID || ($systemTableID && $user_table_mode)) {
                $result = "<?=( \$warnText ? \"<div class='warnText'>\$warnText</div>\" : NULL )?>
<form name='adminForm' id='adminForm' class='nc-form' enctype='multipart/form-data' method='post' action='<?= \$SUB_FOLDER ?><?= \$HTTP_ROOT_PATH ?>" . ($action === 'add' ? 'add' : ($action === 'change' ? 'message' : '')) . ".php'>
<div id='nc_moderate_form'>
<div class='nc_clear'></div>
<input name='admin_mode' type='hidden' value='<?= \$admin_mode ?>' />
<?= \$nc_core->token->get_input() ?>
<input name='catalogue' type='hidden' value='<?= \$catalogue ?>' />
<input name='cc' type='hidden' value='<?= \$cc ?>' />
<input name='sub' type='hidden' value='<?= \$sub ?>' />";
                $result .= ($action === 'change' ? "<input name='message' type='hidden' value='<?= \$message ?>' />\r\n" : "");
                $result .= "<input name='posting' type='hidden' value='1' />
<input name='curPos' type='hidden' value='<?= \$curPos ?>' />
<input name='f_Parent_Message_ID' type='hidden' value='<?= \$f_Parent_Message_ID ?>' />
<?= nc_form_moderate('" . $action . "', \$admin_mode, " . ($user_table_mode + 0) . ", \$systemTableID, \$current_cc, (isset(\$f_Checked) ? \$f_Checked  : null), \$f_Priority , \$f_Keyword, \$f_ncTitle, \$f_ncKeywords, \$f_ncDescription ) ?>
</div>
";
            }
            for ($i = 0; $i < $fldCount; $i++) {
                # описание поля
                $fldNameTempl = $fldName[$i] . ($fldNotNull[$i] ? ' (*)' : '') . ":<br />\r\n";

                # редактировать поле могут:
                $no_edit = $fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE;
                $admin_edit = $fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_ADMIN;

                if ($user_table_mode && $action === 'change' && $fld[$i] === $AUTHORIZE_BY && !$nc_core->get_settings('allow_change_login', 'auth')) {
                    $no_edit = true;
                }
                # если поле не для редактирования - хендовер
                if ($no_edit) {
                    continue;
                }

                $field_html = '';
                switch ($fldType[$i]) {
                    case NC_FIELDTYPE_STRING:
                        $field_html .= "<div class='nc-field nc-field-type-string'><?= nc_string_field('$fld[$i]', \"maxlength='255' size='50'\", (\$class_id ? \$class_id : \$classID), 1) ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_INT:
                        $field_html .= "<div class='nc-field nc-field-type-int'><?= nc_int_field('$fld[$i]', \"maxlength='12' size='12'\", (\$class_id ? \$class_id : \$classID), 1) ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_TEXT:
                        $field_html .= "<div class='nc-field nc-field-type-text'><?= nc_text_field('$fld[$i]', \"\", (\$class_id ? \$class_id : \$classID), 1) ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_SELECT:
                        $field_html .= "<div class='nc-field nc-field-type-select'><?= nc_list_field('$fld[$i]', \"\", (\$class_id ? \$class_id : \$classID), 1) ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_BOOLEAN:
                        $field_html .= "<div class='nc-field nc-field-type-boolean'><?= nc_bool_field('$fld[$i]', \"\", (\$class_id ? \$class_id : \$classID ), 1) ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_FILE:
                        $field_html .= "<div class='nc-field nc-field-type-file'><?= nc_file_field('$fld[$i]', \"size='50'\", (\$class_id ? \$class_id : \$classID), 1) ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_FLOAT:
                        $field_html .= "<div class='nc-field nc-field-type-float'><?= nc_float_field('$fld[$i]', \"maxlength='12' size='12'\", ( \$class_id ? \$class_id : \$classID), 1) ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_DATETIME:
                        $field_html .= "<div class='nc-field nc-field-type-datetime'><?= nc_date_field('$fld[$i]', \"\", (\$class_id ? \$class_id : \$classID), 1) ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_RELATION:
                        $field_html .= "<div class='nc-field nc-field-type-relation'><?= nc_related_field('$fld[$i]', \"\") ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_MULTISELECT:
                        $field_html .= "<div class='nc-field nc-field-type-multiselect'><?= nc_multilist_field('$fld[$i]', \"\", \"\", (\$class_id ? \$class_id : \$classID), 1) ?></div>\r\n";
                        break;
                    case NC_FIELDTYPE_MULTIFILE:
                        $field_html .= "<div class='nc-field nc-field-type-multifile'><?= \$f_{$fld[$i]}->form() ?></div>\r\n";
                        break;
                }

                if ($admin_edit) {
                    $field_html = "<? if (nc_field_check_admin_perm()) { ?>\n" . $field_html . "<? } ?>\r\n";
                }

                $result .= $field_html . "\r\n";
            }

            if (!$systemTableID || ($systemTableID && $user_table_mode)) {
                switch ($action) {
                    case 'add':
                        $submitBtnName = 'NETCAT_MODERATION_BUTTON_ADD';
                        break;
                    case 'change':
                        $submitBtnName = 'NETCAT_MODERATION_BUTTON_CHANGE';
                        break;
                }
                $resetBtnName = 'NETCAT_MODERATION_BUTTON_RESET';

                if ($user_table_mode && $posting == 0 && $action === 'add') {
                    $result .= NETCAT_MODERATION_PASSWORD . ":<br/><input name='Password1' type='password' size='25' maxlength='32' value='' /><br/><br/>";
                    $result .= NETCAT_MODERATION_PASSWORDAGAIN . ":<br/><input name='Password2' type='password' size='25' maxlength='32' value='' /><br/><br/>";
                }

                # защита картинкой
                if ($action === 'add' && $MODULE_VARS['captcha']) {
                    $result .= "<? if (!\$AUTH_USER_ID && \$current_cc['UseCaptcha'] && \$MODULE_VARS['captcha']) { ?><?= nc_captcha_formfield() ?><br/><br/><?= NETCAT_MODERATION_CAPTCHA ?> (*):<br/><input type='text' name='nc_captcha_code' size='10'><br/><br/><? } ?>\r\n";
                }

                $result .= "<div class='nc-hint nc-hint-required-fields'><?= NETCAT_MODERATION_INFO_REQFIELDS ?></div>\r\n";
                $result .= "<?= nc_submit_button($submitBtnName) ?>\r\n";
                $result .= "</form>";
            }
            break;
        case 'search':
            # функция генерации формы поиска из файла "/require/s_list.inc.php"
            # для работы нужны данные из "message_fields.php"
            $srchFrm = showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt);
            // если нет полей для поиска
            if (!$srchFrm) {
                return '';
            }

            $form_action = "<?= (\$admin_mode ? \$HTTP_ROOT_PATH.'index.php?' : nc_infoblock_path(\$current_cc['Sub_Class_ID']) ) ?>";

            $result .= "<form action='$form_action' method='get'>
<? if (\$admin_mode || \$inside_admin) : ?>
        <input type='hidden' name='admin_mode' value='1' />
        <input name='catalogue' type='hidden' value='<?= \$catalogue ?>' />
        <input name='cc' type='hidden' value='<?= \$cc ?>' />
        <input name='sub' type='hidden' value='<?= \$sub ?>' />
<? endif; ?>
<? if (\$inside_admin) : ?>
        <input type='hidden' name='list_mode' value='<?= \$list_mode; ?>' />
        <input type='hidden' name='inside_admin' value='1' />
<? endif; ?>
<input type='hidden' name='action' value='index' />
<input type='hidden' name='admin_mode' value='<?= \$admin_mode ?>' />
$srchFrm
<input value='<?= NETCAT_SEARCH_FIND_IT ?>' type='submit' />
</form>";
            break;
        case 'message':
            $result = "<? " .
                "\$f_delete_true = \$admin_mode\r\n" .
                "  ? \$admin_url_prefix.\"message.php?" . ($nc_core->token->is_use('drop') ? "\".\$nc_core->token->get_url().\"&amp;" : "") . "catalogue=\".\$catalogue.\"&sub=\".\$sub.\"&cc=\".\$cc.\"&message=\".\$message.\"&delete=1&posting=1&curPos=\".\$curPos.\"&admin_mode=1\".\$system_env['AdminParameters']\r\n" .
                "  : nc_object_path(\$current_cc['Class_ID'], \$message, 'drop', 'html', false, array('nc_token' => \$nc_core->token->get()));?>\r\n" .
                "<?= sprintf(NETCAT_MODERATION_WARN_COMMITDELETION, \$message) ?><br/><br/>\r\n";
            $result .= "<a href='<?= \$f_delete_true ?>'><?= NETCAT_MODERATION_COMMON_KILLONE ?></a> | <a href='<?= \$goBackLink.\$system_env['AdminParameters'] ?>'><?= NETCAT_MODERATION_BACKTOSECTION ?></a>\r\n";
            break;
    }

    return $result;
}


function nc_form_moderate($action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc,
                          $f_Checked = null, $f_Priority = '', $f_Keyword = '',
                          $f_ncTitle = '', $f_ncKeywords = '', $f_ncDescription = '',
                          $f_ncSMO_Title = '', $f_ncSMO_Description = '', $f_ncSMO_Image = '') {
    global $inside_admin, $isNaked;
    if ($isNaked) {
        return null;
    } else {
        if ($f_Checked === null) {
            $f_Checked = 1;
        }
        return "<input type='hidden' name='f_Checked' value='{$f_Checked}' />";
    }
}

/**
 * Функция генерации условия добавления, редактирования от $action
 *
 * @param string $action "addcond", "editcond"
 * @return string код условия
 */
function nc_fields_condition_code($action) {
    if (!in_array($action, array("addcond", "editcond"), true) || $systemTableID || !is_array($GLOBALS['fld'])) {
        return false;
    }

    $fldCount = $GLOBALS['fldCount'];
    $fldID = $GLOBALS['fldID'];
    $fld = $GLOBALS['fld'];
    $fldName = $GLOBALS['fldName'];
    $fldValue = $GLOBALS['fldValue'];
    $fldType = $GLOBALS['fldType'];
    $fldFmt = $GLOBALS['fldFmt'];
    $fldNotNull = $GLOBALS['fldNotNull'];
    $fldInheritance = $GLOBALS['fldInheritance'];
    $fldDefault = $GLOBALS['fldDefault'];
    $fldTypeOfEdit = $GLOBALS['fldTypeOfEdit'];
    $fldDoSearch = $GLOBALS['fldDoSearch'];

    $if_res = array();
    $result = "";

    # проходимся по полям
    for ($i = 0; $i < $fldCount; $i++) {
        # если редактирование недоступно никому или поле необязательное - пропускаем условие
        if ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE || !$fldNotNull[$i]) {
            continue;
        }

        if ($fldType[$i] == NC_FIELDTYPE_DATETIME) {
            $format = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_DATETIME);
            switch ($format['type']) {
                case "event":
                    $date_parts_to_check = array('day', 'month', 'year', 'hours', 'minutes', 'seconds');
                    break;
                case "event_date":
                    $date_parts_to_check = array('day', 'month', 'year');
                    break;
                case "event_time":
                    $date_parts_to_check = array('hours', 'minutes', 'seconds');
                    break;
                default:
                    $date_parts_to_check = array();
                    break;
            }

            if ($date_parts_to_check) {
                $field_parts_to_check = array();

                foreach ($date_parts_to_check as $date_part) {
                    $field_parts_to_check[] = "\$f_$fld[$i]_$date_part";
                }

                $if_res[] = "!(" . join(' && ', $field_parts_to_check) .  ")";
            }
        } elseif ($fldType[$i] == NC_FIELDTYPE_FILE && $action === "editcond") {
            $if_res[] = "(!\$f_" . $fld[$i] . " && !\$f_" . $fld[$i] . "_old)";
        } elseif ($fldType[$i] != NC_FIELDTYPE_BOOLEAN) {
            $if_res[] = "!\$f_" . $fld[$i];
        }
    }

    if (!empty($if_res)) {
        $result .= "if(" . join(" || ", $if_res) . ") {\r\n";
        $result .= "\t\$posting = 0;\r\n";
        $result .= "\t#information text\r\n";
        $result .= "\t\$warnText = NETCAT_MODERATION_INFO_REQFIELDS;\r\n";
        $result .= "}\r\n";
    }

    return $result;
}

/**
 * Функция генерации действий от $action
 *
 * @param string $action "addaction", "editaction", "checkaction", "deleteaction"
 * @return string код действия
 */
function nc_fields_action_code($action) {
    global $MODULE_VARS;

    if (!in_array($action, array("addaction", "editaction", "checkaction", "deleteaction"), true)) {
        return false;
    }

    if (!empty($GLOBALS['fld']) && is_array($GLOBALS['fld'])) {
        $fldCount = $GLOBALS['fldCount'];
        $fld = $GLOBALS['fld'];
    }

    $result = "";


    switch ($action) {
        case "addaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJADD";
            break;
        case "editaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJCHANGED";
            break;
        case "checkaction":
            $msg_const = "(\$checked-1 ? ( count(\$messages) == 1 ? NETCAT_MODERATION_OBJISON : NETCAT_MODERATION_OBJSAREON) :\r\n \t\t\t (count(\$messages) == 1 ? NETCAT_MODERATION_OBJISOFF : NETCAT_MODERATION_OBJSAREOFF) )";
            break;
        case "deleteaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJDELETED";
            $msg_const2 = "NETCAT_MODERATION_MSG_OBJSDELETED";
            break;
    }


    $result .= "\";\r\n";
    $result .= "if(\$inside_admin) {\r\n";
    $result .= "\tob_end_clean();\r\n";
    $result .= "\theader(\"Location: \".\$goBackLink.\"&inside_admin=1\");\r\n";
    $result .= "\texit;\r\n";
    $result .= "}\r\n";
    $result .= "else {\r\n";
    if ($action == "deleteaction") {
        $result .= "\tif ( is_array(\$message) ){\r\n";
        $result .= "\t\techo " . $msg_const2 . ";\r\n";
        $result .= "\t} else {\r\n";
        $result .= "\t\techo " . $msg_const . ";\r\n";
        $result .= "\t}\r\n";
    } else if ($action == "addaction") {
        $result .= "\techo \$IsChecked ? NETCAT_MODERATION_MSG_OBJADD : NETCAT_MODERATION_MSG_OBJADDMOD;\r\n";
    } else {
        $result .= "\techo " . $msg_const . ";\r\n";
    }
    $result .= "\techo \"<br /><br />\".\$goBack;\r\n";
    $result .= "}\r\n";
    $result .= "echo \"";

    return $result;
}

/**
 * Функция генерации действий от $action
 *
 * @param string "addaction", "editaction", "checkaction", "deleteaction"
 * @return string код действия
 */
function nc_fields_action_code_fs($action) {
    global $MODULE_VARS, $systemTableID;

    if (!in_array($action, array("addaction", "editaction", "checkaction", "deleteaction")) || $systemTableID) {
        return false;
    }

    if (is_array($GLOBALS['fld'])) {
        $fldCount = $GLOBALS['fldCount'];
        $fld = $GLOBALS['fld'];
    }

    $result = "";


    switch ($action) {
        case "addaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJADD";
            break;
        case "editaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJCHANGED";
            break;
        case "checkaction":
            $msg_const = "(\$checked-1 ? ( count(\$messages) == 1 ? NETCAT_MODERATION_OBJISON : NETCAT_MODERATION_OBJSAREON) :\r\n \t\t\t (count(\$messages) == 1 ? NETCAT_MODERATION_OBJISOFF : NETCAT_MODERATION_OBJSAREOFF) )";
            break;
        case "deleteaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJDELETED";
            $msg_const2 = "NETCAT_MODERATION_MSG_OBJSDELETED";
            break;
    }


    $result .= "<?\r\nif(\$inside_admin) {
    ob_end_clean();
    header('Location: '.\$goBackLink.'&inside_admin=1');
    exit;
} else { \n";
    if ($action == "deleteaction") {
        $result .= "if (is_array(\$message)){";
        $result .= "echo " . $msg_const2 . ";";
        $result .= "} else {";
        $result .= "echo " . $msg_const . ";";
        $result .= "}";
    } else if ($action == "addaction") {
        $result .= "\techo (\$IsChecked ? NETCAT_MODERATION_MSG_OBJADD : NETCAT_MODERATION_MSG_OBJADDMOD);\r\n";
    } else {
        $result .= "\techo " . $msg_const . ";\r\n";
    }
    $result .= "\techo \"<br /><br />\".\$goBack;\r\n";
    $result .= "}\r\n?>";

    return $result;
}

/**
 * Функция рисует поле по $field_name
 *
 * @param string $field_name имя поля
 * @param string $style дополнительные атрибуты
 * @param int $classID идентификатор компонента, его стоит указывать при вызове функции т.к. в функции
 *                           s_list_class() его глобальное значение будет иное
 * @param bool $caption выводить описание поля или нет
 * @return string поле
 * @throws nc_Exception_Class_Doesnt_Exist
 */
function nc_put_field($field_name, $style = '', $classID = 0, $caption = false) {
    global $db, $systemTableID;

    if (!$classID) {
        global $classID;
    }

    if (!$classID) {
        return false;
    }
    $field_name = $db->escape($field_name);

    // данные о поле
    if ($systemTableID == 3) { // Поле из таблицы "Пользователи"
        $field_attr = $db->get_var("SELECT `TypeOfData_ID` FROM `Field` WHERE Class_ID = '0' AND `System_Table_ID` = '3' AND Field_Name = '{$field_name}'");
    } else { // Поле из компонента
        $field_attr = $db->get_var("SELECT `TypeOfData_ID` FROM `Field` WHERE Class_ID = '" . (int)$classID . "' AND Field_Name = '{$field_name}'");
    }

    if (!$field_attr) {
        trigger_error("<b>nc_put_field()</b>: Incorrect field name ({$field_name})", E_USER_WARNING);
        return false;
    }

    switch ($field_attr) {
        case NC_FIELDTYPE_STRING:
            return nc_string_field($field_name, $style, $classID, $caption);
        case NC_FIELDTYPE_INT:
            return nc_int_field($field_name, $style, $classID, $caption);
        case NC_FIELDTYPE_TEXT:
            return nc_text_field($field_name, $style, $classID, $caption);
        case NC_FIELDTYPE_SELECT:
            return nc_list_field($field_name, $style, $classID, $caption, '', '');
        case NC_FIELDTYPE_BOOLEAN:
            return nc_bool_field($field_name, $style, $classID, $caption);
        case NC_FIELDTYPE_FILE:
            return nc_file_field($field_name, $style, $classID, $caption);
        case NC_FIELDTYPE_FLOAT:
            return nc_float_field($field_name, $style, $classID, $caption);
        case NC_FIELDTYPE_DATETIME:
            return nc_date_field($field_name, $style, $classID, $caption);
        case NC_FIELDTYPE_RELATION:
            return nc_related_field($field_name);
        case NC_FIELDTYPE_MULTISELECT:
            return nc_multilist_field($field_name, $style, '', $classID, $caption, '', '');
        case NC_FIELDTYPE_MULTIFILE:
            global ${"f_{$field_name}"};
            return (${"f_{$field_name}"} instanceof nc_multifield ? ${"f_{$field_name}"}->form() : '');
    }

    return '';
}

/**
 * Функция отдаёт массивы полей, для генерации альтернативных форм
 *
 * @param int идентификатор компонента
 * @param string имя поля
 * @param bool принудительно вытащить из базы
 * @return array
 */
function nc_get_field_params($field_name, $classID, $getData = false) {
    global $db, $message, $UserID, $action, $posting, $HTTP_FILES_PATH, $SUB_FOLDER, $systemTableID, $systemMessageID, $user_table_mode, $AUTH_USER_ID; #, $cc
    # если "пользователи" то вот так вот
    if (!($classID || $systemTableID) || !$field_name) {
        return false;
    }

    $classID = (int)$classID;
    $field_name = $db->escape($field_name);
    $fileInfo = array();
    $field_index = 0;

    # если системные таблицы, $message другой
    switch ($systemTableID) {
        case 3:
            # если "пользователи" то вот так вот
            $message = $UserID ? $UserID : $message;
            break;
        case 2:
        case 4:
            # если другие системные таблицы
            $message = $systemMessageID;
            break;
    }

    # если был подключен message_fields.php или объявлен $GLOBALS['fld']
    if (is_array($GLOBALS['fld']) && !$getData) {
        $fldID = $GLOBALS['fldID'];
        $fld = $GLOBALS['fld'];
        $fldName = $GLOBALS['fldName'];
        $fldValue = $GLOBALS['fldValue'];
        $fldType = $GLOBALS['fldType'];
        $fldFmt = $GLOBALS['fldFmt'];
        $fldNotNull = $GLOBALS['fldNotNull'];
        $fldInheritance = $GLOBALS['fldInheritance'];
        $fldDefault = $GLOBALS['fldDefault'];
        $fldTypeOfEdit = $GLOBALS['fldTypeOfEdit'];
        $fldDoSearch = $GLOBALS['fldDoSearch'];
        # дополнительные значения для удобства
        $tmp_array = array_flip($fld);
        $field_index = $tmp_array[$field_name];
        $field_id = $fldID[$field_index];
        # для файла прописываем нужное в один массив
        if (!$systemTableID) {
            $fileInfo = array("f_" . $field_name . "_old" => $GLOBALS["f_" . $field_name . "_old"], "f_" . $field_name => $GLOBALS["f_" . $field_name], "f_" . $field_name . "_url" => $GLOBALS["f_" . $field_name . "_url"], "f_" . $field_name . "_name" => $GLOBALS["f_" . $field_name . "_name"], "f_" . $field_name . "_size" => $GLOBALS["f_" . $field_name . "_size"], "f_" . $field_name . "_type" => $GLOBALS["f_" . $field_name . "_type"]);
        }
    } else {
        # если вызываем не из альтернативных форм нужно выбрать данные о поле
        $FieldRes = $db->get_row("SELECT `Field_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Inheritance`, `DefaultState`, `TypeOfEdit_ID`, " . ($systemTableID ? "1" : "`DoSearch`") . "
                  FROM `Field`
                  WHERE " . ($systemTableID ? "`System_Table_ID` = " . $systemTableID : "`Class_ID` = " . $classID) . "
                  AND `Field_Name` = '" . $field_name . "'
                  LIMIT 1", ARRAY_N);
        if (!empty($FieldRes)) {
            list($fldID[0], $fld[0], $fldName[0], $fldType[0], $fldFmt[0], $fldNotNull[0], $fldInheritance[0], $fldDefault[0], $fldTypeOfEdit[0], $fldDoSearch[0]) = $FieldRes;
        }
        $field_id = $fldID[$field_index = 0];
    }

    # если тип поля файл, действие "изменение" и сообщение не добавлено из-за ошибки в заполнении
    if ($fldType[$field_index] == NC_FIELDTYPE_FILE && (($action == "change" && !$posting) || $systemTableID)) {
        # запрос к файлам
        $fileinfo = $db->get_row("SELECT * FROM `Filetable`
      WHERE `Field_ID` = " . $fldID[$field_index] . " AND `Message_ID` = '" . $message . "'", ARRAY_A);
        # информация о файле
        if ($fileinfo) {
            $file_old = $GLOBALS["f_" . $field_name . "_old"] ? $GLOBALS["f_" . $field_name . "_old"] : $fldValue[$field_index];
            $file_field = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, "/") . $fileinfo['File_Path'] . "h_" . $fileinfo['Virt_Name'];
            $file_url = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, "/") . $fileinfo['File_Path'] . $fileinfo['Virt_Name'];
            $file_name = $fileinfo['Real_Name'];
            $file_size = $fileinfo['File_Size'];
            $file_type = $fileinfo['File_Type'];
        } else {
            # old-style storage
            $file_old = $GLOBALS["f_" . $field_name . "_old"] ? $GLOBALS["f_" . $field_name . "_old"] : $fldValue[$field_index];
            $file_data = explode(':', $file_old);
            $file_name = $file_data[0];
            $ext = substr($file_name, strrpos($file_name, "."));
            $file_type = $file_data[1];
            $file_size = $file_data[2];
            $file_field = $SUB_FOLDER . $HTTP_FILES_PATH;
            $file_field .= ($file_data[3]) ? $file_data[3] : $fldID[$field_index] . "_" . $message . $ext;
        }
        # массив с данными файла
        $fileInfo = array("f_" . $fld[$field_index] . "_old" => $file_old, "f_" . $fld[$field_index] . "" => $file_field, "f_" . $fld[$field_index] . "_url" => $file_url, "f_" . $fld[$field_index] . "_name" => $file_name, "f_" . $fld[$field_index] . "_size" => $file_size, "f_" . $fld[$field_index] . "_type" => $file_type);
    }

    # ассоциативный массив
    $result = array("field_id" => $field_id, "field_index" => $field_index, "fldID" => $fldID, "fld" => $fld, "fldName" => $fldName, "fldValue" => $fldValue, "fileInfo" => $fileInfo, "fldType" => $fldType, "fldFmt" => $fldFmt, "fldNotNull" => $fldNotNull, "fldInheritance" => $fldInheritance, "fldDefault" => $fldDefault, "fldTypeOfEdit" => $fldTypeOfEdit, "fldDoSearch" => $fldDoSearch);

    return $result;
}

/**
 * Функция проверки прав текущего пользователя на администрирование,
 * используется для определения доступности поля
 *
 * @return bool
 */
function nc_field_check_admin_perm() {
    global $perm, $cc, $systemTableID;
    $AdmRights = false;
    # проверим админские права текущего пользователя
    if ($perm instanceof Permission) {
        if ($cc) {
            $AdmRights = $perm->isSubClassAdmin($cc);
        }
        # администратор компонента $cc
        if ($systemTableID) {
            $AdmRights = $perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT);
        }
    }

    return $AdmRights;
}

/**
 * Функция для проверки находимся ли в режиме администрирования, но не в админке
 * Может использоваться в шаблонах компонентов (?)
 *
 * @return bool
 */
function nc_is_edit_mode() {
    $nc_core = nc_core::get_object();
    return !$nc_core->inside_admin && $nc_core->admin_mode;
}

/**
 * Функция поиска в строке атрибутов формата "attribut=..."
 *
 * @param string строка
 * @return array массив названий атрибутов
 */
function nc_reg_search_html_attr($string) {

    # проверим, есть ли в параметре атрибуты формата "attribut=..."
    $string_attr = array();
    $preg_str = $string;
    while (preg_match("/^.*?([[:alpha:]]+(?=[ =]+)){1}(.*)?$/im", $preg_str, $matches)) {
        $preg_str = $matches[2];
        $string_attr[] = $matches[1];
        if (!$matches[1]) {
            break;
        }
        unset($matches);
    }

    return $string_attr;
}

/**
 * Вывод поля типа "Список" в альтернативных формах шаблона
 *
 * @param string имя списка
 * @param string имя поля
 * @param int выбранный элемент списка
 * @param int поле сортировки (не указан – ID, 1 – имя, 2 - приоритет)
 * @param int порядок сортировки (не указан – восходящий, 1 - нисходящий)
 * @param string темплейт префикса списка
 * @param string темплейт элемента списка
 * @param string темплейт суффикса списка
 * @param string темпелейт для первого нулевого элемента списка
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_list_select($classificator_name, $field_name = "", $current_value = false, $sort_type = false, $sort_direction = false, $template_prefix = "", $template_object = "", $template_suffix = "", $template_any = "", $caption = false, $ignore_check = false) {
    global $db, $classID;

    if ($field_name) {
        $fields_params = nc_get_field_params($field_name, $classID);
        if (!empty($fields_params)) {
            extract($fields_params);
        }

        # смотрим тип редактирования поля
        switch ($fldTypeOfEdit[$field_index]) {
            # "Доступно только администраторам"
            case 2:
                $AdmRights = nc_field_check_admin_perm();
                if (!$AdmRights) {
                    return false;
                }
                break;
            # "Недоступно никому"
            case 3:
                return false;
                break;
        }

        // if( is_array($fld) && !in_array($field_name, $fld) ) {
        //  trigger_error("<b>nc_list_select()</b>: Incorrect field name (".$field_name.")", E_USER_WARNING);
        //   return false;
        //  }
    }

    $classificator_name = $db->escape($classificator_name);
    if ($sort_type !== false && $sort_direction !== false) {
        $SortType = $sort_type;
        $SortDirection = $sort_direction;
    } else {
        $res = $db->get_row("SELECT `Classificator_Name`, `Sort_Type`, `Sort_Direction` FROM `Classificator` WHERE Table_Name='" . $classificator_name . "'", ARRAY_A);
        if (!empty($res)) {
            $ClassificatorName = $res['Classificator_Name'];
            $SortType = $res['Sort_Type'];
            $SortDirection = $res['Sort_Direction'];
        }
    }

    # сортировка по полю...
    switch ($SortType) {
        case 1:
            $sort = "`" . $classificator_name . "_Name`";
            break;
        case 2:
            $sort = "`" . $classificator_name . "_Priority`";
            break;
        default:
            $sort = "`" . $classificator_name . "_ID`";
    }

    # выбор данных о списке, цикл ниже
    $res = $db->get_results("SELECT `" . $classificator_name . "_ID`, `" . $classificator_name . "_Name`, `" . $classificator_name . "_Priority`
               FROM `Classificator_" . $classificator_name . "`
               " . ($ignore_check ? "" : "WHERE `Checked` = '1' ") . "
               ORDER BY " . $sort . " " . ($SortDirection == 1 ? "DESC" : "ASC") . "", ARRAY_A);

    # если нет данных о списке - ошибка
    if (empty($res)) {
        trigger_error("<b>nc_list_select()</b>: Incorrect classificator name (" . $classificator_name . ")", E_USER_WARNING);
        return false;
    }

    # вывод Caption, если нужно
    if ($caption) {
        # описание поля из "Field"
        if ($field_name) {
            $result = $fldName[$field_index] . ($fldNotNull[$field_index] ? " (*)" : "") . ":<br />\r\n";
        } # описание поля из "Classificator"
        elseif ($ClassificatorName) {
            $result = $ClassificatorName . ":<br />\r\n";
        }
    }

    # темплейт префикса списка
    if ($template_prefix) {
        eval(nc_check_eval("\$result.= \"" . $template_prefix . "\";"));
    } else {
        $result .= ($field_name ? "<select name='f_" . $field_name . "'>\r\n" : "<select>\r\n");
    }

    if (!$fldNotNull[$field_index]) {
        if (!$template_any) {
            $result .= "<option value=\"\">" . NETCAT_MODERATION_LISTS_CHOOSE . "</option>\r\n";
        } else {
            eval(nc_check_eval("\$result.= \"" . $template_any . "\";"));
        }
    }

    # это значение нужно когда неправильно заполнили поля или когда значение есть в базе
    if ($current_value === false && $fldValue[$field_index]) {
        $current_value = $fldValue[$field_index];
    }

    # темплейт элемента списка
    if ($template_object) {
        foreach ($res as $data) {
            # идентификатор записи OPTION
            $value_id = $data[$classificator_name . "_ID"];
            # выбранный элемент списка
            if ($current_value !== false) {
                $value_selected = ($current_value == $data[$classificator_name . "_ID"] ? " selected='selected'" : "");
            }
            # описание записи OPTION
            $value_name = $data[$classificator_name . "_Name"];
            eval(nc_check_eval("\$result.= \"" . $template_object . "\";"));
        }
    } else {
        foreach ($res as $row) {
            $selected = ($current_value !== false && $current_value == $row[$classificator_name . "_ID"] ? " selected='selected' " : "");
            $result .= "<option value='" . $row[$classificator_name . "_ID"] . "'" . $selected . ">" . $row[$classificator_name . "_Name"] . "</option>\r\n";
        }
    }

    # темплейт суффикса списка
    if ($template_suffix) {
        $result .= eval(nc_check_eval("\$result.= \"" . $template_suffix . "\";"));;
    } else {
        $result .= "</select>";
    }

    return $result;
}

/*
  ".nc_list_field("author", "", 2, "\"; if(\$value_id==2) {\$result.= \" disabled\";}; \$result.=\"")."
 */

/**
 * Вывод поля типа "Список" в альтернативных формах шаблона
 *
 * @param string имя поля
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param mixed выбранный(ые) элемент(ы) списка
 * @param mixed выключенный(ые) элемент(ы) списка
 * @param string дополнительные атрибуты
 * @param bool выводить описание поля или нет
 * @param bool неиспользуется
 * @param bool игнорировать выборку только включенных
 * @param string тип элемента: select или радиокнопки
 * @return string
 */
function nc_list_field($field_name, $style = "", $classID = "", $caption = false, $selected = false, $disabled = false, $unused = null, $ignore_check = false, $type = null) {
    // для получения значения поля
    global $db, $fldValue, $fldID, $systemTableID;
    $message_for_admin = '';

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }

    $show_field_errors = ($classID == $nc_core->sub_class->get_current('Class_ID'));
    if ($classID === false) {
        $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');
    } else {
        $sysTable = $nc_core->component->get_by_id($classID, 'System_Table_ID');
    }

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_SELECT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name) {
            $field = $v;
        }
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_list_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        // nc_print_status() не работает из-за буферизации
        $message_for_admin .= "<div class='nc-alert nc--red'>";
        $message_for_admin .= "<i class='nc-icon-l nc--status-error'></i>";
        $message_for_admin .= sprintf(NETCAT_FUNCTION_OBJECTS_LIST_CLASSIFICATOR_ERROR, $field_name);
        $message_for_admin .= "</div>";

        return $nc_core->InsideAdminAccess() ? $message_for_admin : false;
    }

    if (
        $field['edit_type'] == NC_FIELD_PERMISSION_NOONE ||
        ($field['edit_type'] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
    ) {
        return false;
    }

    // значение поля
    if (is_array($fldID)) {
        $t = array_flip($fldID);
        if (!empty($fldValue)) {
            $value = $fldValue[$t[$field['id']]];
        }
        else {
            $value = null;
        }
    }
    $field_id = $field['id'];

    # если поле обязательно для заполнения
    if (!isset($value)) {
        $value = null;
    }
    if ($value == null && $field['default'] != null && !$selected) {
        $value = $field['default'];
    }

    $format = explode(':', $field['format']);
    $clft_name = $db->escape($format[0]);
    if ($selected !== false) {
        $selected = (array)$selected;
    }
    if ($disabled !== false) {
        $disabled = (array)$disabled;
    }

    if (!$type && nc_array_value($format, 1)) {
        $type = $format[1];
    }
    if (!$type || !in_array($type, array('select', 'radio'))) {
        $type = 'select';
    }


    $res = $db->get_row("SELECT * FROM `Classificator` WHERE Table_Name='" . $clft_name . "'", ARRAY_A);
    if (!empty($res)) {
        $ClassificatorName = $res['Classificator_Name'];
        $SortType = $res['Sort_Type'];
        $SortDirection = $res['Sort_Direction'];
    } else {
        if ($show_field_errors) {
            trigger_error("<b>nc_list_field()</b>: Classificator (" . $clft_name . ") not exist!", E_USER_WARNING);
        }
        // nc_print_status() не работает из-за буферизации
        $message_for_admin .= "<div class='nc-alert nc--red'>";
        $message_for_admin .= "<i class='nc-icon-l nc--status-error'></i>";
        $message_for_admin .= sprintf(NETCAT_FUNCTION_OBJECTS_LIST_CLASSIFICATOR_ERROR, $field_name);
        $message_for_admin .= "</div>";

        return $nc_core->InsideAdminAccess() ? $message_for_admin : false;
    }

    # сортировка по полю...
    switch ($SortType) {
        case 1:
            $sort = "`" . $clft_name . "_Name`";
            break;
        case 2:
            $sort = "`" . $clft_name . "_Priority`";
            break;
        default:
            $sort = "`" . $clft_name . "_ID`";
    }

    # выбор данных о списке, цикл ниже
    $res = $db->get_results("SELECT `" . $clft_name . "_ID`, `" . $clft_name . "_Name`, `" . $clft_name . "_Priority`
               FROM `Classificator_" . $clft_name . "`
               " . ($ignore_check ? "" : "WHERE `Checked` = '1' ") . "
               ORDER BY " . $sort . " " . ($SortDirection == 1 ? "DESC" : "ASC") . "", ARRAY_A);

    # если нет данных о списке - ошибка
    if (empty($res)) {
        if ($show_field_errors) {
            trigger_error("<b>nc_list_field()</b>: Classificator without fields (" . $clft_name . ")", E_USER_WARNING);
        }
        $res = array();

        // nc_print_status() не работает из-за буферизации
        $message_for_admin .= "<div class='nc-alert nc--yellow'>";
        $message_for_admin .= "<i class='nc-icon-l nc--status-warning'></i>";
        $message_for_admin .= "$clft_name: " . CONTENT_CLASSIFICATORS_ERR_ELEMENTNONE;
        $message_for_admin .= "</div>";
    }

    // вывод функции
    $result = $nc_core->InsideAdminAccess() ? $message_for_admin : '';
    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field);
    }

    # префикс списка
    if ($type == 'select') {
        $result .= "<select name='f_" . $field_name . "'" . ($style ? " " . $style : "") . ">\r\n";
    }

    # шапка полей
    if (!$field['not_null'] || !$field['default']) {
        if ($type === 'select') {
            $select0 = ($value == "0" ? " selected" : "");
            $result .= "<option value='0' id='f" . $field_id . "0'" . $select0 . ">" . NETCAT_MODERATION_LISTS_CHOOSE . "</option>\r\n";
        } else if ($type === 'radio') {
            $radio0 = ($value == "0" ? " checked" : "");
            $result .= "<input type='radio' name='f_" . $field_name . "'" . ($style ? " " . $style : "") . " value='0' id='f" . $field_id . "0'" . $radio0 . " />
                     <label for='f" . $field_id . "0'>" . NETCAT_MODERATION_LISTS_CHOOSE . "</label><br/>\r\n";

        }
    }
    # вывод полей списка
    foreach ($res as $row) {
        # для удобства
        $value_id = $row[$clft_name . "_ID"];
        $value_name = $row[$clft_name . "_Name"];

        # выбранные значения
        $selected_str = "";
        if ($value != "0") {
            $s = (($type == 'select') ? 'selected' : 'checked');
            if ($value) {
                $selected_str = ($value == $value_id ? " " . $s . "='" . $s . "' " : "");
            } elseif ($selected !== false && !empty($selected)) {
                $selected_str = (in_array($value_id, $selected) ? " " . $s . "='" . $s . "' " : "");
            } elseif ($value == null && $field['default']) {
                $selected_str = ($field['default'] == $value_id ? " " . $s . "='" . $s . "' " : "");
            }
        }

        # отключенные значения
        $disabled_str = "";
        if ($disabled !== false && !empty($disabled)) {
            $disabled_str = (in_array($value_id, $disabled) ? " disabled='disabled' " : "");
        }

        if ($type == 'select') {
            $result .= "<option value='" . $value_id . "' id='f" . $field_id . $value_id . "'" . $selected_str . $disabled_str . ">" . $value_name . "</option>\r\n";
        } else {
            $result .= "<input type='radio' name='f_" . $field_name . "'" . ($style ? " " . $style : "") . " value='" . $value_id . "' id='f" . $field_id . $value_id . "'" . $selected_str . $disabled_str . " />
                 <label for='f" . $field_id . $value_id . "'>" . $value_name . "</label><br/>\r\n";
        }
    }

    #  суффикс списка
    if ($type == 'select') {
        $result .= "</select>";
    }

    $result .= nc_fields_form_inherited_value_div($systemTableID, $field_name);

    return $result;
}

/**
 * Вывод поля типа "Множественный список" в альтернативных формах шаблона
 *
 * @param string имя списка
 * @param string имя поля
 * @param type формат поля
 * @param string выбранные элемент списка
 * @param int поле сортировки (не указан – ID, 1 – имя, 2 - приоритет)
 * @param int порядок сортировки (не указан – восходящий, 1 - нисходящий)
 * @param string темплейт префикса списка
 * @param string темплейт элемента списка
 * @param string темплейт суффикса списка
 * @param string темпелейт для первого нулевого элемента списка
 * @param bool выводить описание поля или нет
 * @param bool игнорировать выборку только включенных
 * @return string
 */
function nc_multilist_select($classificator_name, $field_name = "", $type = "", $current_value = false, $sort_type = false, $sort_direction = false, $template_prefix = "", $template_object = "", $template_suffix = "", $template_any = "", $caption = false, $ignore_check = false) {
    global $db, $classID;

    if ($field_name) {
        $fields_params = nc_get_field_params($field_name, $classID, 0);
        if (!empty($fields_params)) {
            extract($fields_params);
        }

        list($clft_name, $type_element, $type_size) = explode(":", $fldFmt[$field_index]);

        # смотрим тип редактирования поля
        switch ($fldTypeOfEdit[$field_index]) {
            # "Доступно только администраторам"
            case 2:
                $AdmRights = nc_field_check_admin_perm();
                if (!$AdmRights) {
                    return false;
                }
                break;
            # "Недоступно никому"
            case 3:
                return false;
                break;
        }

        //if( is_array($fld) && !in_array($field_name, $fld) ) {
        //  trigger_error("<b>nc_multilist_select()</b>: Incorrect field name (".$field_name.")", E_USER_WARNING);
        //  return false;
        //}
    }

    if ($type) {
        list($type_element, $type_size) = explode(":", $type);
    } else {
        if (!$type_element) {
            $type_element = "select";
        }
    }
    if (!$type_size) {
        $type_size = 3;
    }


    $res = $db->get_row("SELECT `Classificator_Name`, `Sort_Type`, `Sort_Direction`
                       FROM `Classificator`
                       WHERE Table_Name='" . $db->escape($classificator_name) . "'", ARRAY_A);
    $ClassificatorName = $res['Classificator_Name'];

    if ($sort_type !== false && $sort_direction !== false) {
        $SortType = $sort_type;
        $SortDirection = $sort_direction;
    } else {
        if (!empty($res)) {
            $SortType = $res['Sort_Type'];
            $SortDirection = $res['Sort_Direction'];
        }
    }

    # сортировка по полю...
    switch ($SortType) {
        case 1:
            $sort = "`" . $classificator_name . "_Name`";
            break;
        case 2:
            $sort = "`" . $classificator_name . "_Priority`";
            break;
        default:
            $sort = "`" . $classificator_name . "_ID`";
    }

    # выбор данных о списке, цикл ниже
    $res = $db->get_results("SELECT `" . $classificator_name . "_ID`, `" . $classificator_name . "_Name`, `" . $classificator_name . "_Priority`
               FROM `Classificator_" . $classificator_name . "`
               " . ($ignore_check ? "" : "WHERE `Checked` = '1' ") . "
               ORDER BY " . $sort . " " . ($SortDirection == 1 ? "DESC" : "ASC") . "", ARRAY_A);

    # если нет данных о списке - ошибка
    if (empty($res)) {
        trigger_error("<b>nc_multilist_select()</b>: Incorrect classificator name (" . $classificator_name . ")", E_USER_WARNING);
        return false;
    }

    # вывод Caption, если нужно
    if ($caption) {
        # описание поля из "Field"
        if ($field_name) {
            $result = $fldName[$field_index] . ($fldNotNull[$field_index] ? " (*)" : "") . ":<br />\r\n";
        } # описание поля из "Classificator"
        elseif ($ClassificatorName) {
            $result = $ClassificatorName . ":<br />\r\n";
        }
    }

    # темплейт префикса списка
    if ($template_prefix) {
        eval(nc_check_eval("\$result.= \"" . $template_prefix . "\";"));
    } else if ($type_element == 'select') { // тип элемента - select
        $result .= ($field_name ? "<select size='" . $type_size . "' name='f_" . $field_name . "[]' multiple='multiple'>\r\n" : "<select  size='" . $type_size . "' multiple='multiple'>\r\n");
    }


    # элемент "ничего не выбранно"
    if (!$fldNotNull[$field_index] && $type_element == 'select') {
        if ($template_any) {
            eval(nc_check_eval("\$result.= \"" . $template_any . "\";"));
        }
    }

    if ($current_value !== false) {
        if (!is_array($current_value)) {
            if ($current_value) {
                $current_value = explode(',', $current_value);
            }
        } else {
            $current_value = array($current_value);
        }
    }

    # это значение нужно когда неправильно заполнили поля или когда значение есть в базе
    if ($current_value === false && $fldValue[$field_index]) {
        if (is_array($fldValue[$field_index])) {
            $current_value = $fldValue[$field_index];
        } else {
            $temp = explode(',', $fldValue[$field_index]);
            if (!empty($temp)) {
                $current_value = $temp;
            }
        }
    }

    if (!is_array($current_value) || empty($current_value)) {
        $current_value = array();
    }

    # темплейт элемента списка
    if ($template_object) {
        foreach ($res as $data) {
            # идентификатор записи OPTION
            $value_id = $data[$classificator_name . "_ID"];
            # выбранный элемент списка
            if ($current_value !== false) {
                $value_selected = (in_array($data[$classificator_name . "_ID"], $current_value) ? ($type_element == 'select' ? " selected='selected'" : " checked='checked'") : '');
            }
            # описание записи OPTION
            $value_name = $data[$classificator_name . "_Name"];
            eval(nc_check_eval("\$result.= \"" . $template_object . "\";"));
        }
    } else {
        foreach ($res as $row) {
            $id = $row[$classificator_name . "_ID"];
            $selected = (in_array($row[$classificator_name . "_ID"], $current_value) ? ($type_element == 'select' ? " selected='selected'" : " checked='checked'") : "");
            if ($type_element == 'select') { //тип элемента select
                $result .= "<option value='" . $id . "'" . $selected . ">" . $row[$classificator_name . "_Name"] . "</option>\r\n";
            } else { // тип элемента checkbox
                $result .= "<input type='checkbox' value='" . $id . "'" . $selected . " 'name='f_" . $field_name . "[" . $id . "]' />" . $row[$classificator_name . "_Name"] . "<br />\r\n";
            }
        }
    }

    # темплейт суффикса списка
    if ($template_suffix) {
        $result .= eval(nc_check_eval("\$result.= \"" . $template_suffix . "\";"));;
    } else if ($type_element == 'select') { // тип элемента - select
        $result .= "</select>";
    }

    return $result;
}

/**
 * Вывод поля типа "Множественный выбор" в альтернативных формах шаблона
 *
 * @param string $field_name имя поля
 * @param string $style дополнительные атрибуты
 * @param string $type тип элемента (select or checkbox)
 * @param int $classID идентификатор компонента, его стоит указывать при вызове функции т.к. в функции
 *                             s_list_class() его глобальное значение будет иное
 * @param bool $caption выводить описание поля или нет
 * @param mixed $selected выбранный(ые) элемент(ы) списка
 * @param mixed $disabled выключенный(ые) элемент(ы) списка
 * @param bool $getData принудительно вытащить из базы
 * @param bool $ignore_check игнорировать выборку только включенных
 * @return string
 * @throws nc_Exception_Class_Doesnt_Exist
 */
function nc_multilist_field($field_name, $style = "", $type = "", $classID = null, $caption = false, $selected = false, $disabled = false, $getData = false, $ignore_check = false) {
    // для получения значения поля
    global $db, $fldValue, $fldID, $systemTableID;
    $result = '';

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }

    $show_field_errors = ($classID == $nc_core->sub_class->get_current('Class_ID'));

    if ($classID === false) {
        $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');
    } else {
        $sysTable = $nc_core->component->get_by_id($classID, 'System_Table_ID');
    }

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_MULTISELECT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v) {
        if ($v['name'] == $field_name) {
            $field = $v;
        }
    }
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_multilist_field()</b>: Incorrect field name ({$field_name})", E_USER_WARNING);
        }
        return false;
    }

    if (
        $field['edit_type'] == NC_FIELD_PERMISSION_NOONE ||
        ($field['edit_type'] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
    ) {
        return false;
    }

    // значение поля
    if (is_array($fldID)) {
        $t = array_flip($fldID);
        if (isset($t, $fldValue[$t[$field['id']]])) {
            $value = $fldValue[$t[$field['id']]];
        }
        else {
            $value = null;
        }
    }
    $field_id = $field['id'];
    $format_arr = explode(":", $field['format']);
    // дополняем массив, чтоб всегда было нужное количество элементов
    $format_arr = array_pad($format_arr, 3, '');
    list($clft_name, $type_element, $type_size) = $format_arr; // Сначала берем из формата

    if ($type) { // Если передано через параметр, то перезаписываем
        $type_arr = explode(":", $type);
        // дополняем массив, чтоб всегда было нужное количество элементов
        $type_arr = array_pad($type_arr, 2, '');
        list($type_element, $type_size) = $type_arr;
    } else {
        if (!$type_element) {
            $type_element = "select";
        }
    }
    if (!$type_size) {
        $type_size = 3;
    }

    $clft_name = $db->escape($clft_name);


    $res = $db->get_row("SELECT * FROM `Classificator` WHERE Table_Name='{$clft_name}'", ARRAY_A);
    if (!empty($res)) {
        $SortType = $res['Sort_Type'];
        $SortDirection = $res['Sort_Direction'];
    } else {
        if ($show_field_errors) {
            trigger_error("<b>nc_multilist_field()</b>: Classificator ({$clft_name}) not exist!", E_USER_WARNING);
        }
        return false;
    }

    // сортировка по полю...
    switch ($SortType) {
        case 1:
            $sort = "`{$clft_name}_Name`";
            break;
        case 2:
            $sort = "`{$clft_name}_Priority`";
            break;
        default:
            $sort = "`{$clft_name}_ID`";
    }

    // выбор данных о списке, цикл ниже
    $res = $db->get_results("SELECT `{$clft_name}_ID`, `{$clft_name}_Name`, `{$clft_name}_Priority`
               FROM `Classificator_{$clft_name}`
               " . ($ignore_check ? "" : "WHERE `Checked` = '1' ") . "
               ORDER BY {$sort} " . ($SortDirection == 1 ? "DESC" : "ASC"), ARRAY_A);

    // если нет данных о списке - ошибка
    if (empty($res)) {
        if ($show_field_errors) {
            trigger_error("<b>nc_multilist_field()</b>: Classificator without fields ({$clft_name})", E_USER_WARNING);
        }
        return false;
    }


    // вывод Caption, если нужно
    if ($caption) {
        $description = $field['description'] ?: $field['name'];
        $result .= $description . ($field['not_null'] ? " (*)" : "") . ":<br />\r\n";
    }

    $result .= "<input type='hidden' name='f_{$field_name}[]' value='' />";

    // префикс списка
    $result .= ($type_element === "select") ? ("<select name='f_{$field_name}[]' {$style} multiple='multiple' size='{$type_size}'>\r\n") : "";

    // определение массивов с выбранными и недоступными элементами
    $selected = str_replace(array(",", ".", " "), ";", $selected . ";" . join(';', (array)$value));
    $selectedArray = explode(";", $selected);
    $disabled = str_replace(array(",", ".", " "), ";", $disabled);
    $disabledArray = explode(";", $disabled);

    // вывод полей списка
    foreach ($res as $row) {
        // для удобства
        $value_id = $row["{$clft_name}_ID"];
        $value_name = $row["{$clft_name}_Name"];

        $temp_str = "";
        if (in_array($value_id, $selectedArray)) {
            $temp_str .= ($type_element === "select") ? " selected='selected' " : " checked='checked' ";
        }
        if (in_array($value_id, $disabledArray)) {
            $temp_str .= " disabled";
        }

        $result .= ($type_element === "select") ? "<option value='{$value_id}' id='f{$field_id}{$value_id}'{$temp_str}>{$value_name}</option>\r\n" :
            "<input {$style} type='checkbox' value='{$value_id}' id='f_{$field_name}[{$value_id}]' name='f_{$field_name}[{$value_id}]' {$temp_str} /> \r\n" .
            "<label for='f_{$field_name}[{$value_id}]'>{$value_name}</label>\r\n<br />\r\n";
    }

    //  суффикс списка
    $result .= ($type_element === "select" ? "</select>" : "");
    $result .= nc_fields_form_inherited_value_div($systemTableID, $field_name);

    return $result;
}

/**
 * Получение URL метода в контроллере
 *
 * @param string $controller
 * @param string $method
 * @param array $params
 * @return string
 */
function nc_controller_url($controller, $method = 'index', array $params = array(), $encode_ampersands = false) {
    $nc_core = nc_core::get_object();
    $base_url_part = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php';
    $query_url_part = array(
        'ctrl' => $controller,
        'action' => $method,
    );
    $query_url_part += $params;
    $arg_separator = '&' . ($encode_ampersands ? 'amp;' : null);
    $query_url_part = http_build_query($query_url_part, null, $arg_separator);
    return "{$base_url_part}?{$query_url_part}";
}

/**
 * Вывод поля типа Файл в альтернативных формах шаблона
 *
 * @param string $field_name имя поля
 * @param string $style дополнительные свойства для <input type=file>
 * @param int|string $classID идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool $caption выводить описание поля или нет
 * @return string
 */
function nc_file_field($field_name, $style = "", $classID = "", $caption = false) {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;
    global $db, $action, $current_cc, $message, $user_table_mode, $systemMessageID, $UserID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }
    if ($classID === false) {
        $sysTable = $systemTableID ?: $nc_core->component->get_by_id($classID, 'System_Table_ID');
    } else {
        $sysTable = $nc_core->component->get_by_id($classID, 'System_Table_ID');
    }

    # если системные таблицы, $message другой
    switch ($sysTable) {
        case 3:
            # если "пользователи" то вот так вот
            $message = $UserID ? $UserID : $message;
            break;
        case 2:
        case 4:
            # если другие системные таблицы
            $message = $systemMessageID;
            break;
    }

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }

    $show_field_errors = ($classID == $nc_core->sub_class->get_current('Class_ID'));

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_FILE);
    $fields[] = $component->get_smo_image_field();
    $fields[] = $component->get_nc_image_field();
    $fields[] = $component->get_nc_icon_field();
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if (isset($v['name']) && $v['name'] == $field_name) {
            $field = $v;
        }
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_file_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    if (
        $field['edit_type'] == NC_FIELD_PERMISSION_NOONE ||
        ($field['edit_type'] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
    ) {
        return false;
    }

    // вывод функции
    $value = null;
    $result = '';
    if (is_array($fldID)) {
        $t = array_flip($fldID);
        isset($t[$field['id']]) ? $value = $fldValue[$t[$field['id']]] : $value = null;
    }
    $field_id = $field['id'];

    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field);
    }

    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из $style
    $style_opt = "";
    if (!in_array("size", $style_attr)) {
        $style_opt .= "size='50'";
    }
    if ($style_opt) {
        $style_opt = " " . $style_opt;
    }

    $result .= "<div class='nc-upload' id='nc-upload-" . $field_id . "'><div class='nc-upload-files'>";

    # старое значение
    $old = nc_array_value($GLOBALS, "f_{$field_name}_old");

    if ($sysTable == 2) {
        $subdivision_fields = $nc_core->db->get_row("SELECT `ncSMO_Image`, `ncImage`, `ncIcon` FROM `Subdivision` WHERE `Subdivision_ID`='{$message}'", ARRAY_A);
        if ($field_name === 'ncSMO_Image') {
            $old = $subdivision_fields['ncSMO_Image'];
        }
        if ($field_name === 'ncImage') {
            $old = $subdivision_fields['ncImage'];
        }
        if ($field_name === 'ncIcon') {
            $old = $subdivision_fields['ncIcon'];
        }
    }

    $field_format = nc_field_parse_format($field['format'], NC_FIELDTYPE_FILE);
    if ($field_format['type']) {
        $accepted_types = array();
        foreach ($field_format['type'] as $t) {
            $accepted_types[] = join('/', $t);
        }
        $accept = "accept='" . htmlspecialchars(join(',', $accepted_types), ENT_QUOTES) . "' ";
    } else {
        $accept = '';
    }

    // Узнаем полный путь к файлу, может ли быть иконкой и URL изменения картинки, input для сохранения временного файла
    $file_can_be_as_icon = $field_format['icon'];

    // может быть только иконкой
    $file_only_as_icon = $field_format['onlyicon'];
    $file_absolute_path = $image_dialog_url = $load_dialog_js = null;

    if ($nc_core->admin_mode) {
        $load_dialog_js =
            "nc.load_dialog(this.href).set_option('image_dialog_input'," .
            "\$nc(this).parents('.nc-upload').find('.nc-upload-file-server-path').eq(0)" .
            ");return false;";
    }

    if ($message && (($systemTableID && $value) || ($action === 'change' && $old) || (($field_name === 'ncSMO_Image' || $field_name === 'ncImage' || $field_name === 'ncIcon') && $old))) {
        $system_tables = array(1 => "Catalogue", 2 => "Subdivision", 3 => "User", 4 => "Template");
        $component_id = $systemTableID ? $system_tables[$systemTableID] : $classID;
        $file_path = nc_file_path($component_id, $message, $field_id, 'h_');
        if ($file_path) {
            $file_absolute_path = $nc_core->DOCUMENT_ROOT . nc_file_path($component_id, $message, $field_id);
        }
        list ($file_name, $file_type, $file_size) = explode(':', $old ? $old : $value);
        $file_exists = $file_absolute_path && file_exists($file_absolute_path) && !is_dir($file_absolute_path);
        if ($file_can_be_as_icon && $file_exists) {
            $provider = new nc_image_provider_icon();
            $icon_info = $provider->parse_icon_info($file_absolute_path);
            if (!empty($icon_info['library'])) {
                // $icon_info — массив с ключами library, icon, color
                $image_dialog_url = nc_controller_url('admin.image', 'index', $icon_info);
            }
        }

        $result .= "<input type='hidden' name='f_" . $field_name . "_old' value='" . ($old ? $old : $value) . "' />\r\n";
        $file_size_string = nc_bytes2size($file_size);

        // довесок к пути, чтобы вместо нового файла не показывался закэшированный старый с тем же именем
        if ($file_absolute_path && file_exists($file_absolute_path)) {
            $file_query = '?' . filemtime($file_absolute_path);
        } else {
            $file_query = '';
        }
        $file_url = $file_path . $file_query;

        // Обработка нажатия на «удалить файл» указана явно в HTML для случая,
        // когда скрипт работы с файловыми полями (jquery.upload.js) не подключён
        $block_id = 'nc_upload_file_' . $field_name . '_' . $message;
        $delete_input_id = $block_id . '_delete';
        $delete_js = "document.getElementById('$delete_input_id').value=1;" .
            "document.getElementById('$block_id').style.display='none';" .
            "return false;";

        $result .=
            "<div class='nc-upload-file' id='$block_id' data-type='" . htmlspecialchars($file_type, ENT_QUOTES) . "'>" .
            "<div class='nc-upload-file-info'>" .
            "<a class='nc-upload-file-name' href='" . htmlspecialchars($image_dialog_url ?: $file_url) . "'" .
            " data-preview-url='{$file_url}' target='_blank' tabindex='-1'" .
            " title='" . htmlspecialchars("$file_name ($file_size_string)", ENT_QUOTES) . "'" .
            " onclick='" . htmlspecialchars($image_dialog_url ? $load_dialog_js : '', ENT_QUOTES) . "'>" .
            htmlspecialchars($file_name) .
            "</a> " .
            "<span class='nc-upload-file-size'>$file_size_string</span> " .
            "<a href='#' class='nc-upload-file-remove' onclick='" . htmlspecialchars($delete_js, ENT_QUOTES) . "'" .
            " title='" . NETCAT_MODERATION_FILES_DELETE . "' tabindex='-1'>×</a>" .
            "</div>" .
            "<input type='hidden' name='f_KILL" . $field_id . "' value='0'" .
            " id='$delete_input_id' class='nc-upload-file-remove-hidden'/>" .
            "</div>\n";
    }

    // само поле
    $input_id = "nc-upload-input-$field_id";
    $result .=
        "<input class='nc-upload-input' id='$input_id' name='f_" . $field_name . "'" .
        $style_opt . ($style ? " " . $style : "") . " type='file' $accept/>\n" .
        nc_fields_form_inherited_value_div($systemTableID, $field_name) .
        "</div>";

    if ($nc_core->admin_mode && $file_can_be_as_icon) {
        $dialog_url = $image_dialog_url ?: nc_controller_url('admin.image', 'index');
        $upload_file_js = "\$nc('#$input_id').click();return false;";

        $result .=
            "<input type='hidden' name='f_{$field_name}_tmp' value='' class='nc-upload-file-server-path'>" .
            "<div class='nc-upload-hide-if-full'>" .
            NETCAT_FIELD_FILE_ICON_SELECT . ' ' .
            "<a href='" . htmlspecialchars($dialog_url, ENT_QUOTES) . "'" .
            " onclick='" . htmlspecialchars($load_dialog_js, ENT_QUOTES) . "'>" .
            NETCAT_FIELD_FILE_ICON_ICON .
            "</a>";

        if (!$file_only_as_icon) {
            $result .=
                ' ' . NETCAT_FIELD_FILE_ICON_OR . ' ' .
                "<a href='#' onclick='" . htmlspecialchars($upload_file_js, ENT_QUOTES) . "'>" .
                NETCAT_FIELD_FILE_ICON_FILE .
                "</a>";
        }

        $result .= "</div>";
    }

    $result .=
        "</div>\n" .
        "<script>" .
        "/*\".(\$nc='\$nc').\"*/" . // шаблоны v4 и шаблоны действий по умолчанию проходят через eval, '$nc' будет воспринято как переменная PHP
        "window.\$nc && \$nc(document).trigger('apply-upload');" .
        "</script>\n";

    return $result;
}

/**
 * Вывод поля типа "Логическая переменная" в альтернативных формах шаблона
 *
 * @param string $field_name имя поля
 * @param string|array $style дополнительные свойства для <input ...>
 * @param mixed|int $classID идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool $caption выводить описание поля или нет
 * @param mixed $value значение поля
 * @return string
 */
function nc_bool_field($field_name, $style = "", $classID = "", $caption = false, $value = false) {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();

    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }

    $show_field_errors = $classID == $nc_core->sub_class->get_current('Class_ID');

    if ($classID === false) {
        $sysTable = $systemTableID ?: $nc_core->component->get_by_id($classID, 'System_Table_ID');
    } else {
        $sysTable = $nc_core->component->get_by_id($classID, 'System_Table_ID');
    }

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_BOOLEAN);
    $field = 0;

    foreach ($fields as $v) {
        if ($v['name'] === $field_name) {
            $field = $v;
        }
    }

    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_bool_field()</b>: Incorrect field name ({$field_name})", E_USER_WARNING);
        }
        return false;
    }

    if (
        $field['edit_type'] == NC_FIELD_PERMISSION_NOONE ||
        ($field['edit_type'] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
    ) {
        return false;
    }

    // значение поля
    if (!$value && is_array($fldID)) {
        $t = array_flip($fldID);
        if (isset($t, $fldValue[$t[$field['id']]])) {
            $value = $fldValue[$t[$field['id']]];
        } else {
            $value = null;
        }
    }

    $field_id = $field['id'];
    $result = '';
    # вывод Caption, если нужно
    if ($caption && !$field['not_null']) {
        $result .= nc_field_caption($field);
    }

    # если пришла одна строка, то делаем её массивом
    if (!is_array($style)) {
        $style = array(
            'checkbox' => $style,
            'radio1' => $style,
            'radio2' => $style,
            'radio3' => $style,
        );
    }

    $checked = $checked1 = $checked2 = $checked3 = '';
    if ($field['not_null']) {
        #-- CHECKBOX --#
        # если поле помечено обязательным для заполнения, типа "checkbox"
        $result .= "<input type='hidden' name='f_{$field_name}' value='0' />";
        if ($value || ($value == null && $field['default'] && $field['default'] != null)) {
            $checked = "checked='checked'";
        }
        # код
        $description = $field['description'] ?: $field['name'];
        $result .= "<input id='f{$field_id}' type='checkbox' name='f_{$field_name}' value='1' {$style['checkbox']} {$checked} />";
        $result .= $caption ? " <label for='f{$field_id}'>{$description}</label>" : '';
    } else {
        #-- RADIO --#
        # если логическая переменная с 3 значениями
        if (!is_null($value) && $value != 'NULL') {
            # при редактировании выбираем значение из базы
            if ($value) {
                $checked2 = "checked='checked'";
            } elseif ($value == 0) {
                $checked3 = "checked='checked'";
            }
        } else {
            # при добавлении смотрим на умолчания
            if ($field['default'] == '') {
                $checked1 = "checked='checked'";
            } elseif ($field['default']) {
                $checked2 = "checked='checked'";
            } elseif ($field['default'] == 0) {
                $checked3 = "checked='checked'";
            }
        }

        # код
        $result .= "<input id='f{$field_id}1' type='radio' name='f_{$field_name}' value='NULL' {$style['radio1']} {$checked1} />";
        $result .= "<label for='f{$field_id}1'>" . NETCAT_MODERATION_RADIO_EMPTY . '</label>';
        $result .= "<input id='f{$field_id}2' type='radio' name='f_{$field_name}' value='1' {$style['radio2']} {$checked2} />";
        $result .= "<label for='f{$field_id}2'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES . '</label> ';
        $result .= "<input id='f{$field_id}3' type='radio' name='f_{$field_name}' value='0' {$style['radio3']} {$checked3} /> ";
        $result .= "<label for='f{$field_id}3'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO . '</label>';
    }

    $result .= nc_fields_form_inherited_value_div($systemTableID, $field_name);

    return $result;
}

/**
 * Вывод поля типа "Дата и время" в альтернативных формах шаблона
 *
 * @param string имя поля
 * @param array дополнительные свойства для <input ...>. array("", "", "", "", "", "")
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @param string разделитель для даты
 * @param string разделитель для времени
 * @param bool вывести месяц выпадающим списком
 * @param bool использовать календарь
 * @param int шаблон вывода календаря
 * @param string альтернативный шаблон вывода кнопки "Показать календарь"
 * @return string
 */
function nc_date_field($field_name, $style = "", $classID = "", $caption = false, $dateDiv = "-", $timeDiv = ":", $select = false, $use_calendar = null, $calendar_theme = 0, $calendar_template = "") {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();

    // текущее значение компонента
    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }

    $show_field_errors = ($classID == $nc_core->sub_class->get_current('Class_ID'));
    if ($classID === false) {
        $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');
    } else {
        $sysTable = $nc_core->component->get_by_id($classID, 'System_Table_ID');
    }

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_DATETIME);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name) {
            $field = $v;
        }
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_date_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    if (
        $field['edit_type'] == NC_FIELD_PERMISSION_NOONE ||
        ($field['edit_type'] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
    ) {
        return false;
    }

    // значение поля
    if (is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }
    $format = nc_field_parse_format($field['format'], NC_FIELDTYPE_DATETIME);
    if ($use_calendar === null) {
        $use_calendar = $format['calendar'];
    }

    # нужен нумерованный массив с ключами от 0
    if (!empty($style) && is_array($style)) {
        $style = array_values($style);
    }
    # если массив с 3 элементами - удвоить массив
    if (is_array($style) && count($style) === 3) {
        array_push($style, $style[0], $style[1], $style[2]);
        $style_size = 6;
    }
    $style_opt_arr = array();
    # параметры полей
    if (empty($style) || (is_array($style) && $style_size != 6)) {
        $style = array("maxlength='2' size='2'", "maxlength='2' size='2'", "maxlength='4' size='4'", "maxlength='2' size='2'", "maxlength='2' size='2'", "maxlength='2' size='2'");
        if ($select) {
            $style[1] = "";
        }
    } else {
        # если пришла одна строка, то делаем её массивом из 6 элементов
        if (!is_array($style)) {
            $style_arr = (array)$style;
            $style = array_pad($style_arr, 6, $style);
        }

        # проверим, есть ли в параметре "style", атрибуты
        $i = 0;
        foreach ($style as $val) {
            $style_attr[$i] = nc_reg_search_html_attr($val);
            $i++;
        }

        $date_attr = array(array(2, 2), array(2, 2), array(4, 4), array(2, 2), array(2, 2), array(2, 2));
        # прописываем параметры из $style
        $i = 0;

        foreach ($style as $val) {
            $style_opt = "";
            if ($i == 1 && $select == false) {
                if (!in_array("maxlength", $style_attr[$i])) {
                    $style_opt .= "maxlength='" . $date_attr[$i][0] . "'";
                }
                if (!in_array("size", $style_attr[$i])) {
                    $style_opt .= ($style_opt ? " " : "") . "size='" . $date_attr[$i][1] . "'";
                }
            }
            if ($style_opt) {
                $style_opt_arr[] = " " . $style_opt;
            }
            $i++;
        }
    }

    $result = '';
    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field);
    }

    # если поле помечено "event..."
    //$fldNotNull[$field_index]
    if (!isset($action)) {
        $action = null;
    }
    if ($action != "change" && $field['not_null'] && ($format['type'] == "event" || $format['type'] == "event_date" || $format['type'] == "event_time") && !$value) {
        $value = date("Y-m-d H:i:s");
    }

    if ($value) {
        $year = substr($value, 0, 4);
        $month = substr($value, 5, 2);
        $day = substr($value, 8, 2);
        $hours = substr($value, 11, 2);
        $minutes = substr($value, 14, 2);
        $seconds = substr($value, 17, 2);
    } else {
        $year = $month = $day = $hours = $minutes = $seconds = null;
    }

    if ($format['type'] == "event_date") {
        $timeType = "hidden";
        $timeDiv = "";
    } else {
        $timeType = "text";
    }

    if ($format['type'] == "event_time") {
        $dateType = "hidden";
        $dateDiv = "";
        $use_calendar = false;
    } else {
        $dateType = "text";
    }
    $selectMonth = '';
    if (!empty($select) && defined("NETCAT_MODULE_CALENDAR_MONTH_NAME_ARRAY")) {
        eval("\$monthArray = " . NETCAT_MODULE_CALENDAR_MONTH_NAME_ARRAY . ";");
        if (!$field['not_null']) {
            $monthArray = array_pad($monthArray, 13, "");
        }
        if (is_array($monthArray) && !empty($monthArray)) {
            $selectMonth .= "<select name='f_" . $field_name . "_month'" . nc_array_value($style_opt_arr, 1) . ($style[1] ? " " . $style[1] : "") . ">";
            foreach ($monthArray as $key => $value) {
                $selectMonth .= "<option value='" . (($key + 1) <= 12 ? sprintf("%02d", $key + 1) : "") . "'" . ($month ? ($month == ($key + 1) ? " selected='selected' " : "") : ($field['not_null'] ? ($key == 0 ? " selected='selected'" : "") : ($key == 12 ? " selected='selected'" : ""))) . ">" . $value . "</option>";
            }
            $selectMonth .= "</select>";
        }
    } else {
        $selectMonth .= "<input type='" . $dateType . "' name='f_" . $field_name . "_month'" . nc_array_value($style_opt_arr, 1) . ($style[1] ? " " . $style[1] : "") . " value='" . ((int)$month ? sprintf("%02d", (int)$month) : "") . "' placeholder='" . NETCAT_HINT_FIELD_M . "' />";
    }

    $result .= "<input type='" . $dateType . "' name='f_" . $field_name . "_day'" . nc_array_value($style_opt_arr, 0) . ($style[0] ? " " . $style[0] : "") . " value='" . ((int)$day ? sprintf("%02d", (int)$day) : "") . "' placeholder='" . NETCAT_HINT_FIELD_D . "' />" . $dateDiv .
        $selectMonth . $dateDiv .
        "<input type='" . $dateType . "' name='f_" . $field_name . "_year'" . nc_array_value($style_opt_arr, 2) . ($style[2] ? " " . $style[2] : "") . " value='" . ((int)$year ? sprintf("%04d", (int)$year) : "") . "' placeholder='" . NETCAT_HINT_FIELD_Y . "' /> \r\n
               <input type='" . $timeType . "' name='f_" . $field_name . "_hours'" . nc_array_value($style_opt_arr, 3) . ($style[3] ? " " . $style[3] : "") . " value='" . ($hours ? sprintf("%02d", (int)$hours) : "") . "' placeholder='" . NETCAT_HINT_FIELD_H . "' />" . $timeDiv . "
               <input type='" . $timeType . "' name='f_" . $field_name . "_minutes'" . nc_array_value($style_opt_arr, 4) . ($style[4] ? " " . $style[4] : "") . " value='" . ($minutes ? sprintf("%02d", (int)$minutes) : "") . "' placeholder='" . NETCAT_HINT_FIELD_MIN . "' />" . $timeDiv . "
               <input type='" . $timeType . "' name='f_" . $field_name . "_seconds'" . nc_array_value($style_opt_arr, 5) . ($style[5] ? " " . $style[5] : "") . " value='" . ($seconds ? sprintf("%02d", (int)$seconds) : "") . "' placeholder='" . NETCAT_HINT_FIELD_S . "' />";

    if ($use_calendar && nc_module_check_by_keyword('calendar', 0)) {
        $result .= nc_set_calendar($calendar_theme);
        if ($calendar_template) {
            eval(nc_check_eval("\$result.= \"" . $calendar_template . "\";"));
        } else {
            $result .= "<div style='display: inline; position: relative;'>
                    <img  id='nc_calendar_popup_img_f_" . $field_name . "_day' onclick='nc_calendar_popup(\"f_" . $field_name . "_day\",\"f_" . $field_name . "_month\", \"f_" . $field_name . "_year\", \"" . $calendar_theme . "\");' src='" . nc_module_path('calendar') . "images/calendar.jpg' style='cursor: pointer; position: absolute; left: 7px; top: -3px;'/>
                  </div>
                 <div style='display: none; z-index: 10000;' id='nc_calendar_popup_f_" . $field_name . "_day'></div>";
        }
    }

    $result .= nc_fields_form_inherited_value_div($systemTableID, $field_name);
    //$result .= nc_field_validation('input', "f_".$field_name, $field['id'], 'date', $field['not_null']);

    return $result;
}

/**
 * Вывод поля типа "Текстовый блок" в альтернативных формах шаблона
 *
 * @param string имя поля
 * @param string дополнительные свойства для <input ...>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @param bool выводить панельку с BB-кодами (для панельки нужны IDшники формы и поля, а также стили CSS!)
 * @param string значение по умолчанию
 * @return string
 */
function nc_text_field($field_name, $style = "", $classID = "", $caption = false, $bbcode = false, $value = '') {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;
    global $SUB_FOLDER, $HTTP_ROOT_PATH, $ROOT_FOLDER;

    $nc_core = nc_Core::get_object();

    $system_env = $nc_core->get_settings();
    $allowTags = $nc_core->sub_class->get_current('AllowTags');

    // текущее значение компонента
    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }

    $show_field_errors = ($classID == $nc_core->sub_class->get_current('Class_ID'));
    if ($classID === false) {
        $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');
    } else {
        $sysTable = $nc_core->component->get_by_id($classID, 'System_Table_ID');
    }

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_TEXT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name) {
            $field = $v;
        }
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_text_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    if (
        $field['edit_type'] == NC_FIELD_PERMISSION_NOONE ||
        ($field['edit_type'] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
    ) {
        return false;
    }

    // значение поля
    if (!$value && is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }

    # формат поля
    $format = nc_field_parse_format($field['format'], NC_FIELDTYPE_TEXT);
    $rows = $format['rows'];
    $cols = $format['cols'];

    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из формата поля "Текстовый блок", учитывая параметры из $style
    $style_opt = "";
    if (!in_array("rows", $style_attr)) {
        $style_opt .= "rows='" . ($rows ? $rows : "5") . "'";
    }
    if (!in_array("cols", $style_attr)) {
        $style_opt .= ($style_opt ? " " : "") . "cols='" . ($cols ? $cols : "60") . "'";
    }
    if ($style_opt) {
        $style_opt = " " . $style_opt;
    }

    // вывод функции
    $result = '';
    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field);
    }

    # учтем allowTags еще и от самого формата поля
    // $format['html']: 0- наследовать, 1 - разрешить, 2 - запретить
    if ($format['html']) {
        $allowTags = ($format['html'] == 1);
    }

    #редактор встроен или нет?
    $EditorType = $nc_core->get_settings('EditorType');
    $EmbedEditor = false;

    if ($format['fck']) {
        $EmbedEditor = $format['fck'] == 1;
    } else {
        $CkeditorEmbedEditor = $nc_core->get_settings('CkeditorEmbedEditor');
        $FckeditorEmbedEditor = $nc_core->get_settings('FckeditorEmbedEditor');

        if ($EditorType == 2) {
            if ($FckeditorEmbedEditor !== false) {
                $EmbedEditor = $FckeditorEmbedEditor;
            } else {
                $EmbedEditor = $nc_core->get_settings('EmbedEditor');;
            }
        } else if ($EditorType == 3) {
            if ($CkeditorEmbedEditor !== false) {
                $EmbedEditor = $CkeditorEmbedEditor;
            } else {
                $EmbedEditor = $nc_core->get_settings('EmbedEditor');;
            }
        }
    }

    $no_cm = '';
    # если разрешены HTML-теги, вывести кнопку
    if ($nc_core->admin_mode && $allowTags && $system_env['EditorType'] > 1 && $EmbedEditor != 1) {
        global $AUTHORIZATION_TYPE;
        $sess_id = ($AUTHORIZATION_TYPE == "session" ? "&" . session_name() . "=" . session_id() : "");
        $windowWidth = 750;
        $windowHeight = 605;
        switch ($EditorType) {
            default:
            case 2:
                $editor_name = 'FCKeditor';
                break;
            case 3:
                $editor_name = 'ckeditor4';
                $windowWidth = 1100;
                $windowHeight = 420;
                break;
            case 4:
                $editor_name = 'tinymce';
                break;
        }
        $link = "editors/{$editor_name}/neditor.php";
        $result .= "<button type='button' onclick=\"window.open('" . $SUB_FOLDER . $HTTP_ROOT_PATH . $link . "?form=adminForm&control=f_" . $field_name . $sess_id . "', 'Editor', 'width={$windowWidth},height={$windowHeight},resizable=yes,scrollbars=no,toolbar=no,location=no,status=no,menubar=no');\">" . TOOLS_HTML_INFO . "</button><br />";
        $no_cm = " class='no_cm' ";

    } // редактор встроен
    elseif ($allowTags && $system_env['EditorType'] > 1 && $EmbedEditor == 1) {
        include_once($ROOT_FOLDER . "editors/nc_editors.class.php");
        $editor = new nc_Editors($system_env['EditorType'], "f_" . $field_name, $value, $format['panel']);
        $result .= $editor->get_html();
        unset($editor);
    }

    if (!$nc_core->inside_admin && ($format['bbcode'] || $bbcode)) {
        $result .= nc_bbcode_bar('this', 'adminForm', 'f_' . $field_name, true);
    }

    if (!$allowTags || $EmbedEditor != 1) {
        $result .= "<textarea $no_cm id='f_" . $field_name . "' name='f_" . $field_name . "'" . $style_opt . ($style ? " " . $style : "") . ">" . htmlspecialchars($value) . "</textarea>";
    }

    if ($format['typo']) {
        $result .= '<br><input type="button" onclick="nc_typo_field(\'f_' . $field_name . '\'); return false;" value="' . CONTROL_FIELD_TYPO_BUTTON . '">';
    }

    $result .= nc_fields_form_inherited_value_div($systemTableID, $field_name);
    //$result .= nc_field_validation('textarea', 'f_'.$field_name, $field['id'], 'text', $field['not_null']);

    return $result;
}

/**
 * Вывод поля типа "Строка" в альтернативных формах шаблона
 *
 * @param string имя поля
 * @param string дополнительные свойства для <input ...>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_string_field($field_name, $style = "", $classID = "", $caption = false, $value = '', $valid = false, $caption_style = null, $protect = false) {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }

    if ($classID === false) {
        $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');
    } else {
        $sysTable = $nc_core->component->get_by_id($classID, 'System_Table_ID');
    }

    $show_field_errors = ($classID == $nc_core->sub_class->get_current('Class_ID'));

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_STRING);
    // поиск поля
    $field = array();
    $strAdd = "";
    foreach ($fields as $v) {
        $format_string = nc_field_parse_format($v['format'], NC_FIELDTYPE_STRING);
        if (isset($format_string['transliteration_field']) && $format_string['transliteration_field'] == $field_name) {
            $strAdd .= "data-type='transliterate' data-from='f_" . $v['name'] . "' " . (!empty($format_string['use_url_rules']) ? "data-is-url='yes'" : "");
        }
        if ($v['name'] == $field_name) {
            $field = $v;
            $format = $format_string;
        }
    }
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_string_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    if (
        $field['edit_type'] == NC_FIELD_PERMISSION_NOONE ||
        ($field['edit_type'] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
    ) {
        return false;
    }

    // значение поля
    if (!$value && is_array($fldID)) {
        $t = array_flip($fldID);
        $value = isset($field['id'], $t, $t[$field['id']], $fldValue[$t[$field['id']]]) ? $fldValue[$t[$field['id']]] : '';
    }

    // вывод функции
    $result = '';

    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field, $caption_style);
    }
    if ($valid) {
        $result = "<span id='nc_field_$fldID'>$result</span>";
    }
    if (empty($value) && !is_numeric($value)) {
        if ($format['format'] == 'url') {
            $value = (isURL($field['default']) ? $field['default'] : "http://");
        } elseif (isset($field['default'])) {
            $value = $field['default'];
        }
    }

    # формат поля
    $inputType = $format['format'] == 'password' ? 'password' : 'text';

    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из $style
    $style_opt = "";
    if (!in_array("maxlength", $style_attr)) {
        $style_opt .= "maxlength='255'";
    }
    if (!in_array("size", $style_attr)) {
        $style_opt .= ($style_opt ? " " : "") . "size='50'";
    }
    if (!in_array("type", $style_attr)) {
        $style_opt .= ($style_opt ? " " : "") . "type='" . $inputType . "'";
    }
    if ($style_opt) {
        $style_opt = " " . $style_opt;
    }

    $result .= "<input name='f_" . $field_name . "'" .
        $style_opt .
        ($style ? " " . $style : "") .
        (!empty($format['format']) ? " data-format='" . htmlspecialchars($format['format'], ENT_QUOTES) . "'" : '') .
        " value='" . htmlspecialchars($value, ENT_QUOTES, $nc_core->NC_CHARSET, false) . "' " .
        $strAdd .
        " />";

    $result .= nc_fields_form_inherited_value_div($systemTableID, $field_name);
    //$result .= nc_field_validation('input', 'f_'.$field_name, $field['id'], 'string', $field['not_null'], $field['format']);

    if ($protect) {
        $result = json_encode($result);
        $html = "<div id='protect_{$field_name}'></div>";
        $html .= "<script type='text/javascript'>
            var new_div = document.createElement('div');
            new_div.innerHTML = {$result}
            var protected_element = document.getElementById('protect_{$field_name}');
            if (protected_element) {
                protected_element.parentNode.replaceChild(new_div, protected_element);
            }
        </script>";

        $result = $html;
    }

    return $result;
}

/**
 * Вывод поля типа "Целое число" в альтернативных формах шаблона
 *
 * @param string имя поля
 * @param string дополнительные свойства для <input ...>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_int_field($field_name, $style = "", $classID = "", $caption = false, $value = '') {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }

    $show_field_errors = ($classID == $nc_core->sub_class->get_current('Class_ID'));
    if ($classID === false) {
        $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');
    } else {
        $sysTable = $nc_core->component->get_by_id($classID, 'System_Table_ID');
    }

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_INT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name) {
            $field = $v;
        }
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_int_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    if (
        $field['edit_type'] == NC_FIELD_PERMISSION_NOONE ||
        ($field['edit_type'] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
    ) {
        return false;
    }

    // значение поля

    if (!strlen($value) && is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }

    // вывод функции
    $result = '';
    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field);
    }

    # если поле обязательно для заполнения
    if ((empty($value) && !is_numeric($value)) && isset($field['default'])) {
        $value = $field['default'];
    }

    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из $style
    $style_opt = "";
    if (!in_array("maxlength", $style_attr)) {
        $style_opt .= "maxlength='12'";
    }
    if (!in_array("size", $style_attr)) {
        $style_opt .= ($style_opt ? " " : "") . "size='12'";
    }
    if (!in_array("type", $style_attr)) {
        $style_opt .= ($style_opt ? " " : "") . "type='text'";
    }
    if ($style_opt) {
        $style_opt = " " . $style_opt;
    }

    $result .= "<input name='f_" . $field_name . "'" . $style_opt . ($style ? " " . $style : "") . " value='" . htmlspecialchars($value, ENT_QUOTES) . "' />";
    $result .= nc_fields_form_inherited_value_div($systemTableID, $field_name);
    //$result .= nc_field_validation('input', 'f_'.$field_name, $field['id'], 'int', $field['not_null']);

    return $result;
}

/**
 * Вывод поля типа "Число с плавающей запятой" в альтернативных формах шаблона
 *
 * @param string имя поля
 * @param string дополнительные свойства для <input ...>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_float_field($field_name, $style = "", $classID = "", $caption = false, $value = null) {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID) {
        $classID = $nc_core->sub_class->get_current('Class_ID');
    }

    $show_field_errors = ($classID == $nc_core->sub_class->get_current('Class_ID'));
    if ($classID === false) {
        $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');
    } else {
        $sysTable = $nc_core->component->get_by_id($classID, 'System_Table_ID');
    }

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_FLOAT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name) {
            $field = $v;
        }
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_float_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    if (
        $field['edit_type'] == NC_FIELD_PERMISSION_NOONE ||
        ($field['edit_type'] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
    ) {
        return false;
    }

    // значение поля
    if (!$value && is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }

    // вывод функции
    $result = '';
    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field);
    }

    # если поле обязательно для заполнения
    if ((empty($value) && !is_numeric($value)) && isset($field['default'])) {
        $value = $field['default'];
    }

    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из $style
    $style_opt = "";
    if (!in_array("maxlength", $style_attr)) {
        $style_opt .= "maxlength='12'";
    }
    if (!in_array("size", $style_attr)) {
        $style_opt .= ($style_opt ? " " : "") . "size='12'";
    }
    if (!in_array("type", $style_attr)) {
        $style_opt .= ($style_opt ? " " : "") . "type='text'";
    }
    if ($style_opt) {
        $style_opt = " " . $style_opt;
    }

    $result .= "<input name='f_" . $field_name . "'" . $style_opt . ($style ? " " . $style : "") . " value='" . htmlspecialchars($value, ENT_QUOTES) . "' />";
    $result .= nc_fields_form_inherited_value_div($systemTableID, $field_name);
    //$result .= nc_field_validation('input', 'f_'.$field_name, $field['id'], 'float', $field['not_null']);

    return $result;
}

/**
 * Вспомогательная функция, добавляющая подсказку о наследовании значения
 *
 * @param int $system_table_id
 * @param string $field_name
 * @return string
 * @throws Exception
 */
function nc_fields_form_inherited_value_div($system_table_id, $field_name) {
    static $script_already_emitted = false;

    if (!$system_table_id) {
        return ''; // works only for system tables
    }

    $nc_core = nc_core::get_object();
    $system_table_name = $nc_core->get_system_table_name_by_id($system_table_id);

    try {
        if ($system_table_name == 'Subdivision') {
            $values = $nc_core->subdivision->get_by_id($GLOBALS['systemMessageID'] ?: $GLOBALS['message']);
        } else if ($system_table_name == 'Template') {
            $values = $nc_core->template->get_by_id($GLOBALS['TemplateID']);
        } else {
            return ''; // unsupported system table type
        }
    } catch (Exception $e) {
        $values = array();
    }

    if (!isset($values['_value_source'][$field_name])) {
        return ''; // no inherited value
    }

    $source = $values['_value_source'][$field_name];
    $field = $nc_core->get_component($system_table_name)->get_field($field_name);

    $result = '<div class="nc-field-inherited-from">';

    if ($source['type'] == 'Catalogue') {
        $site_href = $nc_core->ADMIN_PATH . '#catalogue.fields(' . $source['id'] . ')';
        $result .= sprintf(NETCAT_FIELD_VALUE_INHERITED_FROM_CATALOGUE, $site_href);
    } else if ($source['type'] == 'Subdivision') {
        $subdivision_link =
            '<a href="' . $nc_core->ADMIN_PATH . '#subdivision.fields(' . $source['id'] . ')" target="_top">' .
            $nc_core->subdivision->get_by_id($source['id'], 'Subdivision_Name') .
            '</a>';
        $result .= sprintf(NETCAT_FIELD_VALUE_INHERITED_FROM_SUBDIVISION, $subdivision_link);
    } else if ($source['type'] == 'Template') {
        $template_link =
            '<a href="' . $nc_core->ADMIN_PATH . '#template' . ($values['File_Mode'] ? '_fs' : '') . '.edit(' . $source['id'] . ')" target="_top">' .
            $nc_core->template->get_by_id($source['id'], 'Description') .
            '</a>';
        $result .= sprintf(NETCAT_FIELD_VALUE_INHERITED_FROM_TEMPLATE, $template_link);
    } else {
        return ''; // this shouldn’t happen: unknown source type
    }

    $value = $values[$field_name];
    $shown_value = null;
    switch ($field['type']) {
        case NC_FIELDTYPE_TEXT: // don’t show the value
        case NC_FIELDTYPE_MULTIFILE: // not available for system tables
        case NC_FIELDTYPE_RELATION: // not available for system tables
            break;
        case NC_FIELDTYPE_BOOLEAN:
            $shown_value = $value ? CONTROL_CLASS_CLASS_FORMS_YES : CONTROL_CLASS_CLASS_FORMS_NO;
            break;
        case NC_FIELDTYPE_FILE:
            $shown_value = '<a href="' . $value . '" target="_blank">' . $values[$field_name . "_name"] . '</a>';
            break;
        case NC_FIELDTYPE_MULTISELECT:
            $shown_value = join(", ", $value);
            break;
        default:
            $shown_value = $value;
            break;
    }

    if (strlen($shown_value)) {
        $result .= ': <span class="nc-field-inherited-value">' . $shown_value . '</span>';
    }

    $result .= '</div>';

    if (!$script_already_emitted) {
        $result .= <<<'EOS'
<script>
$nc(function() {
    function handler() {
        var input = $nc(this),
            value = input.val(),
            hasValue = (
                value != null &&
                value.length &&
                !(this.tagName == 'SELECT' && value == '0') &&
                !(input.is(':radio') && value == 'NULL')
            );
        input.closest('.nc-field').find('.nc-field-inherited-from')[hasValue ? 'slideUp' : 'slideDown']();
    }

    $nc('input, textarea').on('input, keyup', handler);
    $nc('input[type="radio"], select', document).on('change', handler);
    $nc(document).on('change', 'input[type="file"]', handler);
});
</script>
EOS;
        $script_already_emitted = true;
    }

    return $result;
}

/**
 * Функция проверка валидности URL
 *
 * @param string URL
 * @return bool
 */
function isURL($url) {
    $first_char = substr($url, 0, 1);
    return ($first_char === '#' || $first_char === '/' || nc_preg_match("/^(https?|ftps?):\/\/[0-9a-z" . NETCAT_RUALPHABET . ";\/\?:@&=\+,\.\-_%'\"\$~!\(\)|#\^]+$/i", $url));
}

/**
 * Возвращает полный путь к объекту по его идентификатору и идентификатору компонента.
 *
 * @param int $message_id идентификатор сообщения
 * @param int $class_id идентификатор компонента
 * @param string $action действие с объектом (edit, checked, delete, drop)
 * @param bool|int
 *         - если TRUE строит ссылку относительно подходящего включенного зеркального инфоблока на текущем сайте
 *         - если число, строит ссылку относительно зеркального инфоблока с этим ID(с любого сайта)
 * @return string|false ссылка на объект
 */
function nc_message_link($message_id, $class_id, $action = '', $to_mirror = false) {
    // nc_message_link не добавлял SUB_FOLDER к пути, в то время как nc_object_path — добавляет
    $object_path = nc_object_path($class_id, $message_id, $action, 'html', false, null, false, $to_mirror);
    $sub_folder_length = strlen(nc_core('SUB_FOLDER'));
    if ($sub_folder_length) {
        $object_path = substr($object_path, $sub_folder_length);
    }
    return $object_path;
}

/**
 * Вспомогательная функция для добавления переменных к путям в nc_*_path().
 *
 * @param array $variables
 * @param string $separator
 * @return string
 */
function nc_array_to_url_query(array $variables = null, $separator = '&') {
    $path_query = ($variables ? '?' . http_build_query($variables, null, $separator) : '');
    if ($path_query === '?') {
        $path_query = '';
    }
    return $path_query;
}

/**
 * Возвращает путь к разделу с указанным идентификатором.
 *
 * @param int $folder_id Идентификатор раздела
 * @param string|null $date Дата в формате YYYY-mm-dd, YYYY-mm, YYYY
 * @param array $variables Дополнительные переменные
 * @param bool $use_external_url Учитывать свойство «внешняя ссылка»
 * @return string|nc_routing_path|false
 */
function nc_folder_path($folder_id, $date = null, array $variables = null, $use_external_url = true) {
    $nc_core = nc_core::get_object();
    if ($use_external_url !== false) {
        try {
            $external_url = $nc_core->subdivision->get_by_id($folder_id, 'ExternalURL');
            if ($external_url) {
                if ($external_url[0] == '/' || preg_match('/^\w+:/', $external_url)) {
                    // абсолютная внешняя ссылка
                    return $external_url;
                } else {
                    // относительная внешняя ссылка
                    return $nc_core->SUB_FOLDER .
                        $nc_core->subdivision->get_by_id($folder_id, 'Hidden_URL') .
                        $external_url;
                }
            }
        } catch (Exception $e) {
            return false;
        }
    }

    if (nc_module_check_by_keyword('routing')) {
        return nc_routing::get_folder_path($folder_id, $date, $variables);
    } else {
        return $nc_core->SUB_FOLDER .
            $nc_core->subdivision->get_by_id($folder_id, 'Hidden_URL') .
            ($date ? str_replace('-', '/', $date) . '/' : '') .
            nc_array_to_url_query($variables);
    }
}

/**
 * Возвращает полный (с доменными именем) URL раздела с указанным идентификатором.
 *
 * @param int $folder_id Идентификатор раздела
 * @param string|null $date Дата в формате YYYY-mm-dd, YYYY-mm, YYYY
 * @param array $variables Дополнительные переменные
 * @param bool $use_external_url Учитывать свойство «внешняя ссылка»
 * @return string|false
 */
function nc_folder_url($folder_id, $date = null, array $variables = null, $use_external_url = true) {
    $folder_path = nc_folder_path($folder_id, $date, $variables, $use_external_url);
    if (!strlen($folder_path)) {
        return false;
    }

    if (strpos($folder_path, "://") || substr($folder_path, 0, 2) == "//") {
        // если у раздела есть абсолютная внешняя ссылка с доменом, то путь уже содержит домен
        return $folder_path;
    }

    try {
        $nc_core = nc_core::get_object();
        $site_id = $nc_core->subdivision->get_by_id($folder_id, 'Catalogue_ID');
        return $nc_core->catalogue->get_url_by_id($site_id) . $folder_path;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Возвращает путь к инфоблоку с указанным идентификатором.
 *
 * @param int $infoblock_id Идентификатор инфоблока
 * @param string $action Действие: index|add|search|subscribe
 * @param string $format Формат ответа: html|rss|xml
 * @param string|null $date Дата, которую следует добавить к пути (формат даты: YYYY, YYYY-mm, YYYY-mm-dd)
 * @param array $variables
 * @return string|nc_routing_path|false
 */
function nc_infoblock_path($infoblock_id, $action = 'index', $format = 'html', $date = null, array $variables = null) {
    if (nc_module_check_by_keyword('routing')) {
        return nc_routing::get_infoblock_path($infoblock_id, $action, $format, $date, $variables);
    } else {
        try {
            $nc_core = nc_core::get_object();
            $infoblock_data = $nc_core->sub_class->get_by_id($infoblock_id);
            if (!$action) {
                $action = $infoblock_data['DefaultAction'];
            }

            $folder_path = $nc_core->SUB_FOLDER . nc_array_value($infoblock_data, 'Hidden_URL');

            if ($date && !$nc_core->get_component($infoblock_data['Class_ID'])->get_date_field()) {
                // у указанного компонента нет поля типа event!
                $date = null;
            }

            return $folder_path .
                ($date ? str_replace("-", "/", $date) . "/" : "") .
                ($action != 'index' ? $action . "_" : "") .
                $infoblock_data['EnglishName'] . "." . $format .
                nc_array_to_url_query($variables);
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Возвращает полный (с доменным именем) URL инфоблока с указанным идентификатором.
 *
 * @param int $infoblock_id Идентификатор инфоблока
 * @param string $action Действие: index|add|search|subscribe
 * @param string $format Формат ответа: html|rss|xml
 * @param string|null $date Дата, которую следует добавить к пути (формат даты: YYYY, YYYY-mm, YYYY-mm-dd)
 * @param array $variables
 * @return string|false
 */
function nc_infoblock_url($infoblock_id, $action = 'index', $format = 'html', $date = null, array $variables = null) {
    $infoblock_path = nc_infoblock_path($infoblock_id, $action, $format, $date, $variables);
    if (!$infoblock_path) {
        return false;
    }

    try {
        $nc_core = nc_core::get_object();
        $site_id = $nc_core->sub_class->get_by_id($infoblock_id, 'Catalogue_ID');
        return $nc_core->catalogue->get_url_by_id($site_id) . $infoblock_path;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Возвращает путь к объекту.
 *
 * @param int $component_id
 * @param int $object_id
 * @param string $action full|edit|delete|drop|checked|subscribe
 * @param string $format html|rss|xml
 * @param bool $add_date Если true и у компонента есть поле формата event, добавляет дату к пути
 * @param array $variables
 * @param bool $add_domain (недокументировано, существует для оптимизации — используйте nc_object_url())
 *                          Если TRUE, возвращает URL с именем домена
 * @param bool|int
 *         - если TRUE строит ссылку относительно подходящего включенного зеркального инфоблока на текущем сайте
 *         - если число, строит ссылку относительно зеркального инфоблока с этим ID(с любого сайта)
 * @return string|nc_routing_path|false
 */
function nc_object_path($component_id, $object_id, $action = 'full', $format = 'html', $add_date = false, array $variables = null, $add_domain = false, $to_mirror = false) {
    $object_id = $object_data = (int)$object_id;
    $component_id = (int)$component_id;
    if (!$action) {
        $action = 'full';
    }

    if (!$object_id || !$component_id) {
        return false;
    }

    $db = nc_db();
    $nc_core = nc_core::get_object();

    $date_field = false;
    if ($add_date) {
        $date_field = $nc_core->get_component($component_id)->get_date_field();
    }

    $to_mirror = (int)$to_mirror;
    if ($to_mirror) {
        //ищем включенный зеркальный инфоблок для объекта на текущем сайте
        $mirror = $db->get_row("
                SELECT sc.`Catalogue_ID`, sc.`Subdivision_ID`, sc.`Sub_Class_ID`, m.`Keyword`
                       " . ($add_date && $date_field ? " , DATE_FORMAT(m.`" . $db->escape($date_field) . "`, '%Y/%m/%d') AS date " : "") . "
                FROM `Message{$component_id}` AS m, `Sub_Class` AS sc, `Subdivision` AS sub
                WHERE m.`Message_ID` = $object_id
                  AND sc.`SrcMirror` = m.`Sub_Class_ID`
                  " . ($to_mirror > 1 ?
                "AND sc.`Sub_Class_ID` = $to_mirror "
                :
                "AND sc.`Catalogue_ID` = '" . nc_core('catalogue')->id() . "'
                            AND sc.`Checked` = 1
                            AND sub.`Subdivision_ID` = sc.`Subdivision_ID`
                            AND sub.`Checked` = 1"
            ) . "
                  LIMIT 1",
            ARRAY_A);
    }

    if (nc_module_check_by_keyword('routing')) {
        if (!empty($mirror)) {
            $object_data = array(
                'site_id' => $mirror['Catalogue_ID'],
                'folder_id' => $mirror['Subdivision_ID'],
                'infoblock_id' => $mirror['Sub_Class_ID'],
                'object_id' => $object_id,
                'object_keyword' => $mirror['Keyword'],
                'date' => $mirror['date'],
            );
        }
        return nc_routing::get_object_path($component_id, $object_data, $action, $format, $add_date, $variables, $add_domain);
    } else {
        // основной запрос для построения пути
        list($site_id, $object_path) = $db->get_row(
            "SELECT sub.`Catalogue_ID`,
                CONCAT(
                    sub.`Hidden_URL`, " .
            ($add_date && $date_field ? "DATE_FORMAT(`" . $db->escape($date_field) . "`, '%Y/%m/%d/'), " : "") .
            ($action && $action != 'full' ? "'" . $db->escape($action) . "_', " : "") . "
                    IF(m.`Keyword` <> '', m.`Keyword`, CONCAT(cc.`EnglishName`, '_', m.`Message_ID`)),
                    '." . $db->escape($format) . "'
                )
             FROM `Message{$component_id}` AS m, `Subdivision` AS sub, `Sub_Class` AS cc
             WHERE
                sub.`Subdivision_ID` = " . (empty($mirror) ? " m.`Subdivision_ID` " : "'" . $mirror['Subdivision_ID'] . "'") . "
                AND cc.`Sub_Class_ID` = " . (empty($mirror) ? " m.`Sub_Class_ID` " : "'" . $mirror['Sub_Class_ID'] . "'") . "
                AND m.`Message_ID` = $object_id "
            , ARRAY_N);

        if (!$object_path) {
            return false;
        }

        $object_path = $nc_core->SUB_FOLDER . $object_path . nc_array_to_url_query($variables);

        if ($add_domain) {
            $object_path = $nc_core->catalogue->get_url_by_id($site_id) . $object_path;
        }

        return $object_path;
    }
}

/**
 * Возвращает полный (с доменным именем) URL объекта.
 *
 * @param int $component_id
 * @param int $object_id
 * @param string $action full|edit|delete|drop|checked|subscribe
 * @param string $format html|rss|xml
 * @param bool $add_date
 * @param array $variables
 * @param bool|int
 *         - если TRUE строит ссылку относительно подходящего включенного зеркального инфоблока на текущем сайте
 *         - если число, строит ссылку относительно зеркального инфоблока с этим ID(с любого сайта)
 * @return string|nc_routing_path|false
 */
function nc_object_url($component_id, $object_id, $action = 'full', $format = 'html', $add_date = false, array $variables = null, $to_mirror = false) {
    return nc_object_path($component_id, $object_id, $action, $format, $add_date, $variables, true, $to_mirror);
}

/**
 * Получить путь к файлу в поле $field_name_or_id объекта $message_id из шаблона $class_id
 *
 * @param string|int $class_id id шаблона/название системной таблицы
 * @param int $message_id id сообщения
 * @param string|int $field_name_or_id имя или ID поля
 * @param string $file_name_prefix использовать префикс для новых файлов (optional).
 *    "h_" для получения ссылки для скачивания файла под оригинальным именем
 * @param bool $preview вернуть путь к файлу preview
 * @return string|false путь до файла
 */
function nc_file_path($class_id, $message_id, $field_name_or_id, $file_name_prefix = "", $preview = false) {
    global $nc_core;

    $file_info = $nc_core->file_info->get_file_info($class_id, $message_id, $field_name_or_id, false, false, true);
    if ($file_info['download_path'] == null) {
        $file_path = false;
    } elseif ($file_name_prefix) {
        $file_path = $file_info['download_path'];
    } else {
        $file_path = $file_info['url'];
    }
    if ($preview) {
        $file_path = $file_info['preview_url'];
    }

    return $file_path;
}

/**
 * Получить идентификаторы всех подразделов раздела с идентификатором $sub
 *
 * @param int $sub идентификатор родительского раздела
 * @param bool $include_given_sub включать или нет переданный идентификатор раздела в результирующий массив
 * @return array массив с идентификаторами подразделов
 *
 */
function nc_get_sub_children($sub, $include_given_sub = true) {
    $nc_core = nc_Core::get_object();
    $sub = (int)$sub;
    $result = array();

    if ($include_given_sub) {
        $result[] = $sub;
    }

    try {
        $subdivision = $nc_core->subdivision->get_by_id($sub);
        $hidden_url = str_replace('_', '\_', $nc_core->db->escape($subdivision['Hidden_URL']));
        $catalogue_id = $subdivision['Catalogue_ID'];
    } catch (Exception $e) {
        return $result;
    }

    return array_map('intval', array_merge(
        $result,
        (array)$nc_core->db->get_col(
            "SELECT Subdivision_ID
             FROM Subdivision
             WHERE Hidden_URL LIKE '{$hidden_url}_%'
             AND Catalogue_ID = $catalogue_id"
        )
    ));
}

/**
 * Получить идентификаторы всех дочерних макетов для макета с идентификатором $template
 *
 * @param int $template идентификатор родительского макета
 * @return array массив с идентификаторами макетов
 *
 */
function nc_get_template_children($template) {
    global $db;
    $template = intval($template);
    $array[] = $template;
    $template_array = $db->get_col("SELECT `Template_ID` FROM `Template` WHERE `Parent_Template_ID` = '" . $template . "'");

    if (!empty($template_array)) {
        foreach ($template_array as $key => $val) {
            $array = array_merge($array, nc_get_template_children($val));
        }
    }

    return $array;
}

/**
 * Возвращает массив с данными для получения заголовка связанного объекта
 * по формату, указанном в формате поля типа "Связь с др. объектом".
 * Для совместного использования с listQuery.
 *
 * @param string формат поля
 * @param string id связанного объекта, если нужно получить данные только
 *   по этому одному объекту. Если не указан, результат query будет содержать
 *   запрос для получения
 * @return array ассоциативный массив. Ключи:
 *   - relation_class - тип связанного объекта
 *   - query - заготовка SQL-запроса для получения строки-описания связанного объекта
 *
 *   - full_template - шаблон для listQuery для вывода названия объекта и ссылки
 *       на него (ссылка - только в режиме администрирования
 *   - name_template - шаблон - только название объекта
 */
function nc_related_parse_format($field_format, $related_item_id = null) {

    global $db, $admin_mode, $inside_admin, $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_PATH;

    // двойные/одинарные второго параметра в Format сейчас не обрабатываются;
    // они добавлены на случай добавления дополнительных параметров
    // поэтому можно переписать следующее регвыр как preg_split с ограничением
    // количества результатов
    preg_match("/^
                 (subdivision|sub[_-]?class|user|catalogue|\d+)  # relation class
                 (?:                  # caption (optional)
                   \s* : \s*          # delimiter from relation class
                   (['\"])?           # opening quote (optional)
                   (.*)               # caption template for listquery
                 )?
               $/xi", $field_format, $regs);

    list(, $relation_class, $quote, $caption_template) = $regs;
    if (!$relation_class) {
        trigger_error("<b>nc_related_parse_format()</b>: incorrect field format (&quot;{$fldFmt[$field_index]}&quot;)", E_USER_WARNING);
        return array();
    }

    if ($caption_template && $quote) {
        $caption_template = nc_preg_replace("/$quote$/", "", $caption_template);
    }

    $query = "";

    if (is_numeric($relation_class)) { // ШАБЛОН ДАННЫХ
        // may require further optimization
        $query = "SELECT * FROM Message$relation_class WHERE Message_ID = \$related_id";
        // использовать заголовок, указанный в настройках макета
        if (!$caption_template) {
            $caption_template = $db->get_var("SELECT TitleTemplate FROM Class WHERE Class_ID=$relation_class");
        }
        // никакого заголовка нет
        if (!$caption_template) {
            $query = "SELECT c.Class_Name, m.Message_ID
                   FROM Message{$relation_class} as m,
                        Sub_Class as sc,
                        Class as c
                  WHERE m.Message_ID = \$related_id
                    AND m.Sub_Class_ID = sc.Sub_Class_ID
                    AND sc.Class_ID = c.Class_ID";
            $caption_template = '$f_Class_Name #$f_Message_ID';
        }

        if ($admin_mode) {
            $link = $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?inside_admin=1&classID={$relation_class}&message={\$related_id}";
        } else {
            $link = "\".nc_message_link(\$f_Message_ID, $relation_class).\"";
        }
    } else { // СИСТЕМНАЯ ТАБЛИЦА
        $relation_class = strtolower($relation_class);
        $relation_class = str_replace(array("_", "-"), "", $relation_class); // sub[_-]class

        if ($relation_class == 'subdivision') {
            $query = "SELECT s.*, " .
                ($admin_mode ? "'" . $SUB_FOLDER . $HTTP_ROOT_PATH . "?sub={\$related_id}' as LinkToObject" : "s.Hidden_URL as LinkToObject") . "
                   FROM Subdivision as s
                  WHERE s.Subdivision_ID = \$related_id";

            if (!$caption_template) {
                $caption_template = '$f_Subdivision_Name';
            }
            $link = ""; // будет взята из LinkToObject
        } elseif ($relation_class == 'user') {
            $query = "SELECT * FROM User WHERE User_ID = \$related_id";
            if (!$caption_template) {
                $caption_template = '$f_' . $GLOBALS['AUTHORIZE_BY'];
            }

            if ($inside_admin) {
                $link = $ADMIN_PATH . "#user.edit(\$f_Message_ID)' target='_top"; // некрасивый хак
            } else {
                $link = "";
            }
        } elseif ($relation_class == 'subclass') {
            $query = "SELECT sc.*, " .
                ($admin_mode ? "'" . $SUB_FOLDER . $HTTP_ROOT_PATH . "?cc=\$related_id'  as LinkToObject" : "CONCAT(sd.Hidden_URL, sc.EnglishName, '.html') as LinkToObject") . "
                   FROM Sub_Class as sc, Subdivision as sd
                  WHERE sc.Sub_Class_ID = $related_id
                    AND sc.Subdivision_ID = sd.Subdivision_ID
                  ";
            if (!$caption_template) {
                $caption_template = '$f_Sub_Class_Name';
            }
        }
    }

    // extract - для эмуляции поведения TitleTemplate
    $caption_with_link_tpl = '";
                            $f_LinkToObject = "' . $link . '";
                            extract($data, EXTR_PREFIX_ALL, "f");
                            $result .= "<a href=\'$f_LinkToObject\'>' . $caption_template . '</a>';

    return array("relation_class" => $relation_class,
        "query" => $query,
        "full_template" => $caption_with_link_tpl,
        "name_template" => $caption_template);
}

/**
 * Элементы для редактирования поля типа "связь с другим объектом"
 * Функция не должна использоваться внутри s_list_class.
 * Работает только в admin_mode.
 *
 * @param string имя поля
 * @param string кнопка/ссылка на изменение связанного объекта
 *   например '<a href="#" onclick="%s">выбрать</a>'
 *   где на место %s будет подставлен Javascript-код.
 *   Обрабатывается через sprintf, поэтому не должно быть неэкранированного "%".
 *   Разработчику следует учитывать, что внутри вставляемого JS-кода
 *   используются одинарные кавычки.
 * @param string удаление (... $action_remove)
 * @return string
 */
function nc_related_field($field_name, $change_template = "", $remove_template = "") {

    require_once($GLOBALS['ADMIN_FOLDER'] . "related/format.inc.php");

    $result = "";

    global $fld, // массив с буквенными идентификаторами полей
           $fldID, // массив с ID полей
           $fldValue, // значения полей
           $fldName, // названия (описания) полей
           $fldFmt, // формат полей
           $fldNotNull, // обязательное
           $fldType, // тип поля
           $message, // текущий объект
           $db, $admin_mode, $inside_admin, $ADMIN_PATH;

    if (!$admin_mode) {
        return "";
    }

    if (is_array($fld) && !in_array($field_name, $fld)) {
        trigger_error("<b>nc_related_field</b>: incorrect field name ($field_name)", E_USER_WARNING);
        return;
    }
    if (!is_array($fld)) {
        return;
    }

    $tmp_array = array_flip($fld);
    $field_index = $tmp_array[$field_name];
    $field_id = $fldID[$field_index];

    if ($fldType[$field_index] != 9) {
        trigger_error("<b>nc_related_field</b>: field '$field_name' is not a link", E_USER_WARNING);
        return;
    }

    // заголовок поля
    $description = $fldName[$field_index] ? $fldName[$field_index] : $fld[$field_index];
    $result .= $description;
    if ($fldNotNull[$field_index]) {
        $result .= " (*)";
    }
    $result .= ": <br />\n";

    $result .= "<ul class='nc-toolbar nc--left'>";

    $result .= "<li><div id='nc_rel_{$field_id}_caption'>";
    $related_id = (int)$fldValue[$field_index];
    /** @var field_relation $field_data */
    $field_data = field_relation_factory::get_instance($fldFmt[$field_index]);
    // вывод значения
    if ($related_id) {
        $related_caption = listQuery($field_data->get_object_query($related_id), $field_data->get_full_admin_template());
        $result .= ($related_caption ? $related_caption : sprintf(NETCAT_MODERATION_RELATED_INEXISTENT, $related_id));
    } else {
        $result .= NETCAT_MODERATION_NO_RELATED;
    }
    $result .= "</div></li>";
    $result .= "<li class='nc-divider'></li>";


    // кнопки действий: заменить и удалить связь
    if (!$change_template) {
        $change_template = "<li><a href=\"#\" onclick=\"%s\"><i class='nc-icon nc--edit'></i> " . NETCAT_MODERATION_CHANGE_RELATED . "</a></li>";
    }

    $change_link = "window.open('" . $ADMIN_PATH . "related/select_" . $field_data->get_relation_type() . ".php?field_id={$fldID[$field_index]}', " .
        "'nc_popup_{$fld[$field_index]}', " .
        "'width={$field_data->popup_width},height={$field_data->popup_height},menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes'); " .
        "return false;";

    $result .= sprintf($change_template, $change_link);

    if (!$fldNotNull[$field_index]) {
        if (!$remove_template) {
            $remove_template = "<li><a href='#' onclick=\"%s\"><i class='nc-icon nc--remove'></i> " . NETCAT_MODERATION_REMOVE_RELATED . "</a></li>";
        }

        $remove_link = "document.getElementById('nc_rel_{$field_id}_value').value='';" .
            "document.getElementById('nc_rel_{$field_id}_caption').innerHTML = '" . NETCAT_MODERATION_NO_RELATED . "';" .
            "return false;";

        $result .= sprintf($remove_template, $remove_link);
    }

    // hidden
    $result .= "<input type='hidden' name='f_{$fld[$field_index]}' id='nc_rel_{$field_id}_value' value='$related_id' />\n";

    // готово
    $result .= "</ul>"; //.nc-toolbar

    $result .= "<br />\n";
    return $result;
}

/**
 * Кнопка "отправить данные" для использования в альтернативных формах.
 * Внутри интерфейса 3.0 рисует кнопку в областе кнопок действий,
 * вне него - обычный <input type=submit>.
 *
 * @param string текст на кнопке
 * @return string;
 */
function nc_submit_button($caption, $button = false) {
    global $admin_mode, $isNaked, $inside_admin;;

    if ($admin_mode && ($inside_admin || $isNaked)) {
        return null;
    }

    $inside_admin = $GLOBALS['inside_admin'];
    $UI_CONFIG = nc_array_value($GLOBALS, 'UI_CONFIG');

    if ($inside_admin && is_object($UI_CONFIG)) {
        $GLOBALS['UI_CONFIG']->actionButtons[] = array("id" => "submit",
            "caption" => $caption,
            "action" => "mainView.submitIframeForm('adminForm')");
        return "<input type='submit' class='hidden' />\r\n";
    } else {
        return $button ? "<button type='submit'>" . htmlspecialchars($caption) . "</button>\r\n"
            : "<input type='submit' value='" . htmlspecialchars($caption) . "' />\r\n";
    }
}

/**
 * Кнопка "отменить" для использования в альтернативных формах.
 * Внутри интерфейса 3.0 НЕрисует кнопку в областе кнопок действий,
 * вне него - обычный <input type=reset>.
 *
 * @param string текст на кнопке
 * @return string;
 */
function nc_reset_button($caption) {

    $inside_admin = $GLOBALS['inside_admin'];
    $UI_CONFIG = $GLOBALS['UI_CONFIG'];

    if ($inside_admin && is_object($UI_CONFIG)) {
        //$GLOBALS['UI_CONFIG']->actionButtons[] = array("id" => "submit",
        //                         "caption" => $caption,
        //                         "action" => "mainView.submitIframeForm('adminForm')");
        //return "<input type='submit' class='hidden'>\r\n";
    } else {
        return "<input type='reset' value='" . htmlspecialchars($caption) . "' />\r\n";
    }
}

/**
 * Convert array to string
 *
 * @param array $arr input array
 * @param array $template template, keys - prefix, suffix, element, divider
 * @return string
 */
function nc_array_to_string($arr, $template) {
    $result = '';
    eval(nc_check_eval("\$result = \"" . $template['prefix'] . "\";"));

    $numElement = count($arr);
    $i = 1;

    if (!empty($arr)) {
        foreach ($arr as $k => $v) {
            $temp = str_replace(array('%ELEMENT', '%I', '%KEY'), array($v, $i, $k), $template['element']);
            eval(nc_check_eval("\$result .= \"" . $temp . "\";"));
            if ($i++ != $numElement) {
                eval(nc_check_eval("\$result .= \"" . $template['divider'] . "\";"));
            }
        }
    }

    eval(nc_check_eval("\$result .= \"" . $template['suffix'] . "\";"));

    return $result;
}

/**
 * Вывод массива в структурированном виде
 *
 * @param array $var массив для вывода
 * @return bool true;
 */
function dump($var) {

    print "<hr><xmp>" . print_r($var, 1) . "</xmp><hr>";

    return true;
}

/**
 * Функция создания массива смалов
 *
 * @no params
 * @return mixed;
 *
 * @todo перенести картинки, их названия и обозначения в базу
 *
 */
function nc_smiles_array() {
    $nc_core = nc_core::get_object();
    $smiles_dir = $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'images/smiles/';

    # массив смайлов
    $smiles = array(
        array(0 => ":)", 1 => "smile.gif", 2 => NETCAT_SMILE_SMILE),
        array(0 => ":D", 1 => "bigsmile.gif", 2 => NETCAT_SMILE_BIGSMILE),
        array(0 => ":grin:", 1 => "grin.gif", 2 => NETCAT_SMILE_GRIN),
        array(0 => ":laugh:", 1 => "laugh.gif", 2 => NETCAT_SMILE_LAUGH),
        array(0 => ":proud:", 1 => "proud.gif", 2 => NETCAT_SMILE_PROUD),
        array(0 => ":yes:", 1 => "yes.gif", 2 => NETCAT_SMILE_YES),
        array(0 => ":wink:", 1 => "wink.gif", 2 => NETCAT_SMILE_WINK),
        array(0 => ":cool:", 1 => "cool.gif", 2 => NETCAT_SMILE_COOL),
        array(0 => ":eyes:", 1 => "rolleyes.gif", 2 => NETCAT_SMILE_ROLLEYES),
        array(0 => ":lookdown:", 1 => "lookdown.gif", 2 => NETCAT_SMILE_LOOKDOWN),
        array(0 => ":(", 1 => "sad.gif", 2 => NETCAT_SMILE_SAD),
        array(0 => ":spy:", 1 => "suspicious.gif", 2 => NETCAT_SMILE_SUSPICIOUS),
        array(0 => ":angry:", 1 => "angry.gif", 2 => NETCAT_SMILE_ANGRY),
        array(0 => ":bad:", 1 => "shakefist.gif", 2 => NETCAT_SMILE_SHAKEFIST),
        array(0 => ":stern:", 1 => "stern.gif", 2 => NETCAT_SMILE_STERN),
        array(0 => ":kiss:", 1 => "kiss.gif", 2 => NETCAT_SMILE_KISS),
        array(0 => ":think:", 1 => "think.gif", 2 => NETCAT_SMILE_THINK),
        array(0 => ":yep:", 1 => "thumbsup.gif", 2 => NETCAT_SMILE_THUMBSUP),
        array(0 => ":sick:", 1 => "sick.gif", 2 => NETCAT_SMILE_SICK),
        array(0 => ":no:", 1 => "no.gif", 2 => NETCAT_SMILE_NO),
        array(0 => ":cantlook:", 1 => "cantlook.gif", 2 => NETCAT_SMILE_CANTLOOK),
        array(0 => ":doh:", 1 => "doh.gif", 2 => NETCAT_SMILE_DOH),
        array(0 => ":out:", 1 => "knockedout.gif", 2 => NETCAT_SMILE_KNOCKEDOUT),
        array(0 => ":eyeup:", 1 => "eyeup.gif", 2 => NETCAT_SMILE_EYEUP),
        array(0 => ":shh:", 1 => "shh.gif", 2 => NETCAT_SMILE_QUIET),
        array(0 => ":evil:", 1 => "evil.gif", 2 => NETCAT_SMILE_EVIL),
        array(0 => ":upset:", 1 => "upset.gif", 2 => NETCAT_SMILE_UPSET),
        array(0 => ":undecided:", 1 => "undecided.gif", 2 => NETCAT_SMILE_UNDECIDED),
        array(0 => ":cry:", 1 => "cry.gif", 2 => NETCAT_SMILE_CRY),
        array(0 => ":unsure:", 1 => "unsure.gif", 2 => NETCAT_SMILE_UNSURE),
    );

    return array($smiles, $smiles_dir);
}

/**
 * Функция вывода панельки с BB-кодами
 *
 * @param string $winID идентификатор окна для JS кода
 * @param string $formID идентификатор формы для JS кода
 * @param string $textareaID идентификатор textarea для JS кода
 * @param bool $help выводить строку с помощью?
 * @param array|string $codes какие коды выводить, по-умолчанию все
 * @param string $prefix префикс вывода панельки с кодами
 * @param string $suffix суффикс вывода панельки с кодами
 * @param bool $noscript
 * @return string ;
 */
function nc_bbcode_bar($winID, $formID, $textareaID, $help = false, $codes = array(), $prefix = "", $suffix = "", $noscript = false) {
    if (!($winID && $formID && $textareaID)) {
        return false;
    }

    $nc_core = nc_core::get_object();
    $icons_folder = $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'images/bbcode_toolbar';

    # массив вывода BB-кодов
    $BBcode = array(
        "SIZE" => "<select class='nc_bbcode_bar_size' onChange=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "', '[SIZE=' + this.value + ']', '[/SIZE]'); this.selectedIndex=0;\"  name='bb_fontsize' title='" . NETCAT_BBCODE_SIZE . "' " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_SIZE . "');\"" : "") . ">\r\n<option value=''>-- " . NETCAT_BBCODE_SIZE_DEF . " --\r\n<option value='8'>8px\r\n<option value='10'>10px\r\n<option value='12'>12px\r\n<option value='14'>14px\r\n<option value='16'>16px\r\n<option value='18'>18px\r\n<option value='20'>20px\r\n<option value='22'>22px\r\n<option value='24'>24px\r\n</select>\r\n",
        "COLOR" => "<a href='#' onClick=\"show_color_buttons('" . $textareaID . "'); return false;\" id='nc_bbcode_color_button_" . $textareaID . "' " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_COLOR . "');\"" : "") . "><img src='$icons_folder/i_color.gif' alt='" . NETCAT_BBCODE_COLOR . "' class='nc_bbcode_wicon'></a>\r\n",
        "SMILE" => "<a href='#' onClick=\"show_smile_buttons('" . $textareaID . "'); return false;\" id='nc_bbcode_smile_button_" . $textareaID . "' " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_SMILE . "');\"" : "") . "><img src='$icons_folder/i_smile.gif' alt='" . NETCAT_BBCODE_SMILE . "' class='nc_bbcode_wicon'></a>\r\n",
        "B" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[B]','[/B]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_B . "');\"" : "") . "><img src='$icons_folder/i_bold.gif' alt='" . NETCAT_BBCODE_B . "' class='nc_bbcode_icon'></a>\r\n",
        "I" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[I]','[/I]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_I . "');\"" : "") . "><img src='$icons_folder/i_italy.gif' alt='" . NETCAT_BBCODE_I . "' class='nc_bbcode_icon'></a>\r\n",
        "U" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[U]','[/U]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_U . "');\"" : "") . "><img src='$icons_folder/i_underline.gif' alt='" . NETCAT_BBCODE_U . "' class='nc_bbcode_icon'></a>\r\n",
        "S" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[S]','[/S]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_S . "');\"" : "") . "><img src='$icons_folder/i_strike.gif' alt='" . NETCAT_BBCODE_S . "' class='nc_bbcode_icon'></a>\r\n",
        "LIST" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[LIST]','[/LIST]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_LIST . "');\"" : "") . "><img src='$icons_folder/i_list.gif' alt='" . NETCAT_BBCODE_LIST . "' class='nc_bbcode_icon'></a>\r\n",
        "QUOTE" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[QUOTE]','[/QUOTE]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_QUOTE . "');\"" : "") . "><img src='$icons_folder/i_quote.gif' alt='" . NETCAT_BBCODE_QUOTE . "' class='nc_bbcode_icon'></a>\r\n",
        "CODE" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[CODE]','[/CODE]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_CODE . "');\"" : "") . "><img src='$icons_folder/i_code.gif' alt='" . NETCAT_BBCODE_CODE . "' class='nc_bbcode_icon'></a>\r\n",
        //"IMG" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[IMG=\'http://\']',''); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_IMG . "');\"" : "") . "><img src='$icons_folder/i_picture.gif' alt='" . NETCAT_BBCODE_IMG . "' class='nc_bbcode_icon'></a>\r\n",
        //"URL" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[URL=\'http://\']','[/URL]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_URL . "');\"" : "") . "><img src='$icons_folder/i_link.gif' alt='" . NETCAT_BBCODE_URL . "' class='nc_bbcode_icon'></a>\r\n",
        "IMG" => "<a href='#' onClick=\"show_img_window('" . $textareaID . "'); return false;\" id='nc_bbcode_img_button_" . $textareaID . "' " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_IMG . "');\"" : "") . "><img src='$icons_folder/i_picture.gif' alt='" . NETCAT_BBCODE_IMG . "' class='nc_bbcode_icon'></a>\r\n",
        "URL" => "<a href='#' onClick=\"show_url_window('" . $textareaID . "'); return false;\" id='nc_bbcode_url_button_" . $textareaID . "' " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_URL . "');\"" : "") . "><img src='$icons_folder/i_link.gif' alt='" . NETCAT_BBCODE_URL . "' class='nc_bbcode_icon'></a>\r\n",
        "CUT" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[CUT=\'" . NETCAT_BBCODE_CUT_MORE . "\']','[/CUT]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_CUT . "');\"" : "") . "><img src='$icons_folder/i_cut.gif' alt='" . NETCAT_BBCODE_CUT . "' class='nc_bbcode_icon'></a>\r\n");

    if ($codes) {
        $codes = (array)$codes;
        $codes = array_map("strtoupper", $codes);
        # ошибка в BB-кодах
        if ($diff = array_diff($codes, array_keys($BBcode))) {
            $result = "<div style='nc_bbcode_error'>" . (count($diff) === 1 ? NETCAT_BBCODE_ERROR_1 : NETCAT_BBCODE_ERROR_2) . " " . implode(", ", $diff) . "</div>";
            return $result;
        }
        # получаем нужные коды в нужном порядке
        # $codes = array_flip($codes);
        # PHP 5: $BBcode_arr = array_intersect_key($BBcode, $codes);
        $BBcode_arr = array();
        foreach ($codes as $value) {
            $BBcode_arr[] = $BBcode[$value];
        }
        $BBcode_str = implode("\r\n", $BBcode_arr); # array_merge($codes, $BBcode_arr)
    } else {
        # получаем все коды
        $BBcode_str = implode("\r\n", $BBcode);
    }

    $result = "<div class='nc_bbcode_bar'>";

    # формируем панельку с кодами
    if (!$noscript) {
        $result .= "<script language='JavaScript' type='text/javascript' src='" .
            nc_add_revision_to_url($nc_core->ADMIN_PATH . 'js/bbcode.js') .
            "'></script>";
    }
    $result .= ($prefix !== false ? $prefix : "<div>") . "
    " . $BBcode_str . "
  " . ($help ? "<input type='text' name='bbcode_helpbox_" . $textareaID . "' value='" . NETCAT_BBCODE_HELP . "' class='nc_bbcode_helpbox nc_no_' />" : "") . "
    " . ($suffix !== false ? $suffix : "</div>");

    if (!$codes || (!empty($codes) && in_array("COLOR", $codes, true))) {
        # палитра безопасных цветов
        $colors = array("770000", "BB0000", "FF0000", "007700", "00BB00", "00FF00", "000077", "0000BB", "0000FF", "000000",
            "779900", "BB9900", "FF9900", "007799", "00BB99", "00FF99", "990077", "9900BB", "9900FF", "FFFFFF",
            "77CC00", "BBCC00", "FFCC00", "0077CC", "00BBCC", "00FFCC", "CC0077", "CC00BB", "CC00FF", "999999");
        # цветов встроке
        $inline = 10;
        $total_colors = count($colors);
        $i = 0;
        # панелька с цветами
        while ($i < $total_colors) {
            if ($i !== 0 && $i !== $total_colors && (int)($i / $inline) == ($i / $inline)) {
                $result .= "</div>\r\n<div class='nc_bbcode_color'>\r\n";
            } elseif ($i === 0) {
                $result .= "<div id='color_buttons_" . $textareaID . "' class='nc_bbcode_colors' style='display:none;'>\n<div class='nc_bbcode_color_top'>\r\n";
            }
            $result .= "<input type='button' value='' class='" . ($colors[$i] === "FFFFFF" ? "nc_bbcode_color_white" : "nc_bbcode_color") . "' style='background:#" . $colors[$i] . ";' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "', '[COLOR=" . $colors[$i] . "]', '[/COLOR]'); show_color_buttons('" . $textareaID . "');\" />\r\n";
            if (($i + 1) === $total_colors) {
                $result .= "</div>\r\n</div>\r\n";
            }
            ++$i;
        }
    }

    if (!$codes || (!empty($codes) && in_array("SMILE", $codes, true))) {
        # панелька со смайлами
        list($smiles, $smiles_dir) = nc_smiles_array();

        $inline = 5;
        $total_smiles = count($smiles);
        $i = 0;

        while ($i < $total_smiles) {
            if ($i !== 0 && $i !== $total_smiles && (int)($i / $inline) == ($i / $inline)) {
                $result .= "</div>\r\n<div class='nc_bbcode_smile'>\r\n";
            } elseif ($i === 0) {
                $result .= "<div id='smile_buttons_" . $textareaID . "' class='nc_bbcode_smiles' style='display:none;'>\n<div class='nc_bbcode_smile_top'>\r\n";
            }
            $result .= "<input type='button' value='' onclick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "', '" . $smiles[$i][0] . "', ''); show_smile_buttons('" . $textareaID . "');\" class='nc_bbcode_smile' style='background:url(" . $smiles_dir . $smiles[$i][1] . ") no-repeat center;' />\r\n";
            if (($i + 1) === $total_smiles) {
                $result .= "</div>\r\n</div>\r\n";
            }
            ++$i;
        }
    }

    // панельки с url и img

    $result .= "<div id='url_link_" . $textareaID . "' class='nc_bbcode_url' style='display: none;'>\n<div class='nc_bbcode_url_top'>\r\n";
    $result .= "<span>" . NETCAT_BBCODE_HELP_URL_URL . ":</span> <input type='text' id='bbcode_url_" . $textareaID . "'>\r\n";
    $result .= "<span>" . NETCAT_BBCODE_HELP_URL_DESC . ":</span> <input type='text' id='bbcode_urldesc_" . $textareaID . "'>\r\n";
    $result .= "</div>\r\n";
    $result .= "<input type='button' value='" . NETCAT_BBCODE_HELP_URL . "' class='' style='' onClick=\"insert_url_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "');\" />\r\n";;
    $result .= "</div>\r\n";


    $result .= "<div id='img_" . $textareaID . "' class='nc_bbcode_img' style='display: none;'>\n<div class='nc_bbcode_img_top'>\r\n";
    $result .= "<span>" . NETCAT_BBCODE_HELP_IMG_URL . ":</span> <input type='text' id='bbcode_img_" . $textareaID . "'>\r\n";
    $result .= "<input type='button' value='" . NETCAT_BBCODE_HELP_IMG . "' class='' style='' onClick=\"insert_img_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "');\" />\r\n";;
    $result .= "</div>\r\n</div>\r\n</div>\r\n";

    return $result;
}

/**
 * Функция обработки текста с BB-кодами
 * заменяет коды на их HTML эквиваленты
 *
 * @param string $text текст
 * @param string $cut_link ссылка на полный просмотр объекта
 * @param bool $cut_full полный вывод объекта?
 * @param array $codes массив допустимых кодов
 * @return string;
 */
function nc_bbcode($text, $cut_link = "", $cut_full = false, $codes = array()) {
    $nc_core = nc_Core::get_object();
    # массив допустимых BB-кодов
    $allow_codes = array("SIZE", "ALIGN", "COLOR", "FONT", "SMILE", "B", "I", "U", "S", "LIST", "QUOTE", "CODE", "IMG", "URL", "CUT", "OL", "UL", "LI");

    if ($codes) {
        $codes = (array)$codes;
        $codes = array_map("strtoupper", $codes);
        # ошибка в BB-кодах
        if ($diff = array_diff($codes, $allow_codes)) {
            $result = "<div class='nc_bbcode_error'>" . (count($diff) === 1 ? NETCAT_BBCODE_ERROR_1 : NETCAT_BBCODE_ERROR_2) . " " . implode(", ", $diff) . "</div>";
            return $result . $text;
        }
        $codes = array_flip($codes);
    }

    if (!$codes || isset($codes['SMILE'])) {
        list($smiles, $smiles_dir) = nc_smiles_array();

        $i = 0;
        $total_smiles = count($smiles);
        $catalogue_url = $nc_core->catalogue->get_url_by_id($nc_core->catalogue->id());
        # заменяем смайлы
        while ($i < $total_smiles) {
            $smile_url = $catalogue_url . $smiles_dir . $smiles[$i][1];
            # генерация смайлика
            $smile = "<img src='{$smile_url}' alt='" . htmlspecialchars($smiles[$i][2], ENT_QUOTES) . "' class='nc_bbcode_smile_in_text'>";
            $text = str_replace($smiles[$i][0], $smile, $text);
            ++$i;
        }
    }

    $BBcodes = array();
    if (!$codes || isset($codes['B'])) {
        $BBcodes[] = "b";
    }
    if (!$codes || isset($codes['I'])) {
        $BBcodes[] = "i";
    }
    if (!$codes || isset($codes['U'])) {
        $BBcodes[] = "u";
    }
    # parsing
    foreach ($BBcodes as $BBcode) {
        $BBregex = "#\[(/?{$BBcode})\]#si";
        while (nc_preg_match($BBregex, $text)) {
            $text = nc_preg_replace($BBregex, "<\$1>", $text);
        }
    }

    $forbidden_scheme_pattern = '(?:(?:java|vb)script)';
    $scheme_pattern = '[a-z0-9]+(?:[+.-]?[a-z0-9]+)*';
    $url_pattern = '[+\w' . NETCAT_RUALPHABET . '&@#/%\?=~|\!:,.;\[\]\(\)-]*?';
    $link_pattern = "//$url_pattern|$scheme_pattern:(?://)?$url_pattern";
    $optional_quote = "(?:&quot;|&#039;|'|\")?";

    $RegEx = array();
    $HtmlCodes = array();
    # Условия на доступность BB-кодов

    if (!$codes || isset($codes['QUOTE'])) {
        $RegEx[] = "!\[quote=$optional_quote(.*?)$optional_quote\](.*?)\[/quote\]!si";
        $RegEx[] = "!\[quote\](.*?)\[/quote\]!si";
        $HtmlCodes[] = "<div class='nc_bbcode_quote_1_top'><b>\$1 " . NETCAT_BBCODE_QUOTE_USER . ":</b><div class='nc_bbcode_quote_1'>\$2</div></div>";
        $HtmlCodes[] = "<div class='nc_bbcode_quote_2_top'><b>" . NETCAT_BBCODE_QUOTE . ":</b><div class='nc_bbcode_quote_2'>\$1</div></div>";
    }

    if (!$codes || isset($codes['COLOR'])) {
        $RegEx[] = "!\[color=$optional_quote#?([a-f\d]{3}|[a-f\d]{6})$optional_quote\](.*?)\[/color\]!si";
        $HtmlCodes[] = "<span style=\"color: #\$1;\" class='nc_bbcode_color'>\$2</span>";

        $RegEx[] = "!\[color=$optional_quote([a-z]+)$optional_quote\](.*?)\[/color\]!si";
        $HtmlCodes[] = "<span style=\"color: \$1;\" class='nc_bbcode_color'>\$2</span>";
    }

    if (!$codes || isset($codes['FONT'])) {
        $RegEx[] = "!\[font=$optional_quote(.+?)$optional_quote\](.*?)\[/font\]!si";
        $HtmlCodes[] = "<span style=\"font-family: \$1;\" class='nc_bbcode_color'>\$2</span>";
    }

    if (!$codes || isset($codes['SIZE'])) {
        $RegEx[] = "!\[size=$optional_quote([\d]{1,2})$optional_quote\](.*?)\[/size\]!si";
        $HtmlCodes[] = "<span style=\"font-size:\$1px\" class='nc_bbcode_size'>\$2</span>";
    }

    if (!$codes || isset($codes['ALIGN'])) {
        $RegEx[] = "!\[align=(left|center|right|justify)?\](.*?)\[/align\]!si";
        $HtmlCodes[] = "<div style=\"text-align:\$1;\">\$2</div>";
    }

    if (!$codes || isset($codes['URL'])) {
        $RegEx[] = "!\[url\]($forbidden_scheme_pattern:.*?)\[/url\]!si";
        $RegEx[] = "!\[url=($optional_quote)($forbidden_scheme_pattern:.*?)($optional_quote)\](.*?)\[/url\]!si";
        $RegEx[] = "!\[url\]($link_pattern)\[/url\]!si";
        $RegEx[] = "!\[url=$optional_quote($link_pattern)$optional_quote\](.*?)\[/url\]!si";
        $HtmlCodes[] = "&#91;url&#93;\$1&#91;/url&#93;";
        $HtmlCodes[] = "&#91;url=\$1\$2\$3&#93;\$4&#91;/url&#93;";
        $HtmlCodes[] = "<!--noindex--><a href=\"\$1\" class='nc_bbcode_url_1' target='_blank' rel='nofollow'>\$1</a><!--/noindex-->";
        $HtmlCodes[] = "<!--noindex--><a href=\"\$1\" class='nc_bbcode_url_2' target='_blank' rel='nofollow'>\$2</a><!--/noindex-->";
    }

    if (!$codes || isset($codes['IMG'])) {
        $RegEx[] = "!\[img=$optional_quote($link_pattern)$optional_quote\]!si";
        $RegEx[] = "!\[img\]($link_pattern)\[/img\]!si";
        $HtmlCodes[] = "<img src=\"\$1\" alt='" . NETCAT_BBCODE_IMG . "' class='nc_bbcode_img' />";
        $HtmlCodes[] = "<img src=\"\$1\" alt='" . NETCAT_BBCODE_IMG . "' class='nc_bbcode_img' />";
    }

    if (!$codes || isset($codes['CODE'])) {
        $RegEx[] = "!\[code\](.*?)\[/code\]!si";
        $HtmlCodes[] = "<div class='nc_bbcode_code'><b>" . NETCAT_BBCODE_CODE . ":</b><pre>\$1</pre></div>";
    }

    if (!$codes || isset($codes['S'])) {
        $RegEx[] = "!\[s\](.*?)\[/s\]!si";
        $HtmlCodes[] = "<span style='text-decoration:line-through;' class='nc_bbcode_s'>\$1</span>";
    }

    if (!$codes || isset($codes['LIST'])) {
        $RegEx[] = "'\[list\](.*?(?!\[list\]))\[/list\]([^\r\n]*)\r?\n?'si";
        $HtmlCodes[] = "<span class='nc_bbcode_list_closed'>&bull; \$1</span>\$2";
    }

    if (!$codes || isset($codes['OL'])) {
        $RegEx[] = "!\[ol\](.*?)\[/ol\]!si";
        $HtmlCodes[] = "<ol>\$1</ol>";
    }

    if (!$codes || isset($codes['UL'])) {
        $RegEx[] = "!\[ul\](.*?)\[/ul\]!si";
        $HtmlCodes[] = "<ul>\$1</ul>";
    }

    if (!$codes || isset($codes['LI'])) {
        $RegEx[] = "!\[li\](.*?)\[/li\]!si";
        $HtmlCodes[] = "<li>\$1</li>";
    }

    # обработка
    $t = $text;
    $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
    while ($t !== $text) {
        $t = $text;
        $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
    }

    if (!$codes || isset($codes['LIST'])) {
        # поддержка не закрытых кодов списка
        unset($RegEx, $HtmlCodes);
        $RegEx = array("'\\[list\\]([^\r\n]*)\r?\n?'si");
        $HtmlCodes = array("<div class='nc_bbcode_list'>&bull; \$1</div>");
        $t = $text;
        $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
        while ($t !== $text) {
            $t = $text;
            $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
        }
    }

    if (!$codes || isset($codes['OL'])) {
        $text = str_replace(array('[ol]', '[/ol]'), '', $text);
    }

    if (!$codes || isset($codes['UL'])) {
        $text = str_replace(array('[ul]', '[/ul]'), '', $text);
    }

    if (!$codes || isset($codes['LI'])) {
        $text = str_replace(array('[li]', '[/li]'), '', $text);
    }

    if (!$codes || isset($codes['CUT'])) {
        # CUT parsing
        if (!$cut_full) {
            $regex = "|\[cut((=[\"\']?){1}([^\[\]\"\']+)?[\"\']?)?\]((?!.*\[cut([^\[\]]+)?\]).*?)\[/cut\]|is";
            $i = 0;
            while (nc_preg_match($regex, $text, $matches)) {
                $repl = "<a href='$cut_link#nc_cut$i'>" . ($matches[3] ?: NETCAT_BBCODE_CUT_MORE) . "</a>";
                $text = nc_preg_replace($regex, $repl, $text);
                ++$i;
            }
        } else {
            $regex = "|\[cut([^\[\]]+)?\]((?!.*\[cut).*?)|is";
            $i = 0;
            while (nc_preg_match($regex, $text)) {
                $repl = "<a href='#' id='nc_cut$i' class='nc_bbcode_cut_link'></a>\$2";
                $text = nc_preg_replace($regex, $repl, $text);
                ++$i;
            }
        }
        # то что осталось убираем
        $text = nc_preg_replace("|\[cut([^\[\]]+)?\]|i", "", $text);
        $text = nc_preg_replace("|\[/cut\]|i", "", $text);
    }

    //обрезаем слишком длинный URL
    while (nc_preg_match('|\[strcut=(\d+)\](.+)\[/strcut\]|isU', $text, $match)) {
        $strlen = (int)$match[1];
        $original_text = $match[2];

        $cutted_text = strlen($original_text) > $strlen ? (nc_substr($original_text, 0, $strlen) . '...') : $original_text;

        $replace = preg_quote("[strcut={$strlen}]{$original_text}[/strcut]", '|');

        $text = nc_preg_replace('|' . $replace . '|is', $cutted_text, $text);
    }

    return $text;
}

/**
 * Функция очистки текста от BB-кодов (кроме URL)
 *
 * @param string $text текст
 * @return string;
 */
function nc_bbcode_clear($text) {
    list($smiles) = nc_smiles_array();

    $i = 0;
    $total_smiles = count($smiles);
    # заменяем смайлы
    while ($i < $total_smiles) {
        # генерация смайлика
        $smile = "";
        $text = str_replace($smiles[$i][0], $smile, $text);
        ++$i;
    }

    $BBcodes = array("b", "i", "u", "s", "ol", "ul", "li", "list", "code", "cut");
    # parsing
    foreach ($BBcodes as $BBcode) {
        $BBregex = "#\[(/?{$BBcode})\]#si";
        while (nc_preg_match($BBregex, $text)) {
            $text = nc_preg_replace($BBregex, "", $text);
        }
    }

    $forbidden_scheme_pattern = '(?:(?:java|vb)script)';
    $scheme_pattern = '[a-z0-9]+(?:[+.-]?[a-z0-9]+)*';
    $url_pattern = '[+\w' . NETCAT_RUALPHABET . '&@#/%\?=~|\!:,.;\[\]\(\)-]*?';
    $link_pattern = "//$url_pattern|$scheme_pattern:(?://)?$url_pattern";
    $optional_quote = "(?:&quot;|&#039;|'|\")?";

    # RegExp array
    $RegEx = array();
    # replace array
    $HtmlCodes = array();
    # Условия на доступность BB-кодов
    if (isset($codes['QUOTE']) || !$codes) {
        $RegEx[] = "!\[quote=$optional_quote(.*?)$optional_quote\](.*?)\[/quote\]!si";
        $RegEx[] = "!\[quote\](.*?)\[/quote\]!si";
        $HtmlCodes[] = "";
        $HtmlCodes[] = "";
    }
    if (isset($codes['COLOR']) || !$codes) {
        $RegEx[] = "!\[color=$optional_quote#?([a-f\d]{3}|[a-f\d]{6})$optional_quote\](.*?)\[/color\]!si";
        $HtmlCodes[] = "\$2";

        $RegEx[] = "!\[color=$optional_quote([a-z]+)$optional_quote\](.*?)\[/color\]!si";
        $HtmlCodes[] = "\$2";
    }
    if (isset($codes['FONT']) || !$codes) {
        $RegEx[] = "!\[font=$optional_quote(.+?)$optional_quote\](.*?)\[/font\]!si";
        $HtmlCodes[] = "\$2";
    }
    if (isset($codes['SIZE']) || !$codes) {
        $RegEx[] = "!\[size=$optional_quote([\d]{1,2})$optional_quote\](.*?)\[/size\]!si";
        $HtmlCodes[] = "\$2";
    }
    if (isset($codes['ALIGN']) || !$codes) {
        $RegEx[] = "!\[align=(left|center|right|justify)?\](.*?)\[/align\]!si";
        $HtmlCodes[] = "\$2";
    }
    if (isset($codes['URL']) || !$codes) {
        $RegEx[] = "!\[url\]($forbidden_scheme_pattern:.*?)\[/url\]!si";
        $RegEx[] = "!\[url=($optional_quote)($forbidden_scheme_pattern:.*?)($optional_quote)\](.*?)\[/url\]!si";
        $RegEx[] = "!\[url\]($link_pattern)\[/url\]!si";
        $RegEx[] = "!\[url=$optional_quote($link_pattern)$optional_quote\](.*?)\[/url\]!si";
        $HtmlCodes[] = "&#91;url&#93;\$1&#91;/url&#93;";
        $HtmlCodes[] = "&#91;url=\$1\$2\$3&#93;\$4&#91;/url&#93;";
        $HtmlCodes[] = "<!--noindex--><a href=\"\$1\" class='nc_bbcode_url_1' target='_blank' rel='nofollow'>\$1</a><!--/noindex-->";
        $HtmlCodes[] = "<!--noindex--><a href=\"\$1\" class='nc_bbcode_url_2' target='_blank' rel='nofollow'>\$2</a><!--/noindex-->";
    }
    if (isset($codes['IMG']) || !$codes) {
        $RegEx[] = "!\[img=$optional_quote($link_pattern)$optional_quote\]!si";
        $RegEx[] = "!\[img\]($link_pattern)\[/img\]!si";
        $HtmlCodes[] = "";
        $HtmlCodes[] = "";
    }

    if (!$codes || isset($codes['CUT'])) {
        $RegEx[] = "|\[cut((=[\"\']?){1}([^\[\]\"\']+)?[\"\']?)?\]((?!.*\[cut([^\[\]]+)?\]).*?)\[/cut\]|is";
        $RegEx[] = "|\[cut([^\[\]]+)?\]((?!.*\[cut).*?)|is";
        $RegEx[] = "|\[cut([^\[\]]+)?\]|i";
        $RegEx[] = "|\[/cut\]|i";
        $HtmlCodes[] = "";
        $HtmlCodes[] = "";
        $HtmlCodes[] = "";
        $HtmlCodes[] = "";
    }

    # обработка
    $t = $text;
    $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
    while ($t !== $text) {
        $t = $text;
        $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
    }

    while (nc_preg_match('|\[strcut=(\d+)\](.+)\[/strcut\]|isU', $text, $match)) {
        $strlen = (int)$match[1];
        $original_text = $match[2];

        $replace = preg_quote("[strcut={$strlen}]{$original_text}[/strcut]", '|');

        $text = nc_preg_replace('|' . $replace . '|is', $original_text, $text);
    }

    return $text;
}

/**
 * Функция перевода байтов в Kb, Mb, Gb
 *
 * @param int $byte_size число в байтах;
 * @return string строка;
 */
function nc_bytes2size($byte_size) {

    # byte
    if ($byte_size < 1024) {
        return ($byte_size ?: "0") . NETCAT_SIZE_BYTES;
    }
    # Kb
    if ($byte_size >= 1024 && $byte_size < 1048576) {
        return round($byte_size / 1024) . NETCAT_SIZE_KBYTES;
    }
    # Mb
    if ($byte_size >= 1048576 && $byte_size < 1073741824) {
        return round($byte_size / (1024 * 1024), 1) . NETCAT_SIZE_MBYTES;
    }
    # Gb
    if ($byte_size >= 1073741824) {
        return round($byte_size / (1024 * 1024 * 1024), 3) . NETCAT_SIZE_GBYTES;
    }

    return 0;
}

/**
 * Функция получения значения визуальных настроек
 *
 * @param int $cc идентификатор компонента в разделе
 * @return bool|array результат
 */
function nc_get_visual_settings($cc) {
    try {
        return nc_Core::get_object()->sub_class->get_by_id($cc, 'Sub_Class_Settings');
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check installed module by keyword
 *
 * @param string $keyword module keyword
 * @param bool $installed `Installed` column
 * @return int `Module_ID` or false
 */
function nc_module_check_by_keyword($keyword, $installed = true) {
    global $nc_core;
    $module_data = $nc_core->modules->get_by_keyword($keyword, $installed);
    if ($module_data) {
        return $module_data['Module_ID'];
    }

    return false;
}

/**
 * Get data from "from" table and put those data into the "to" table
 *
 * @param string "from" table
 * @param string "to" table
 * @param array equivalence "field_from" => "field_to" array
 * @param bool ignore fields type
 *
 * @return bool true or false
 */
function nc_copy_data($table_from, $table_to, $fieds, $ignore_type = false, $where_str = "") {
    global $db;

    // check startup parameters
    if (empty($fieds)) {
        return false;
    }

    // validate
    $table_from = $db->escape($table_from);
    $table_to = $db->escape($table_to);

    if ($where_str) {
        nc_preg_replace("/^\s*WHERE\s?/is", "", $where_str);
        $where_str = " WHERE " . $where_str;
    }

    // check tables existance
    if (!$db->get_var("SHOW TABLES LIKE '" . $table_from . "'") || !$db->get_var("SHOW TABLES LIKE '" . $table_to . "'")) {
        return false;
    }

    // get columns from table
    $table_from_columns = $db->get_results("SHOW COLUMNS FROM `" . $table_from . "`", ARRAY_N);
    $table_to_columns = $db->get_results("SHOW COLUMNS FROM `" . $table_to . "`", ARRAY_N);

    // one dimension array with fields names from base
    $table_from_fields_arr = array();
    $table_to_fields_arr = array();
    // one dimension array with fields types from base
    $table_from_types_arr = array();
    $table_to_types_arr = array();
    // from and to fields arrays
    $field_from_arr = array_keys($fieds);
    $field_to_arr = array_values($fieds);

    // build one dimension fields and types array for "from" table
    foreach ($table_from_columns as $value) {
        if (!in_array($value[0], $field_from_arr)) {
            continue;
        }
        $table_from_fields_arr[] = $value[0];
        if (!$ignore_type) {
            $table_from_types_arr[$value[0]] = $db->escape($value[1]);
        }
    }

    // in "to" array may be possible using arrays as value, combine them into the one dimension array
    $field_to_simple_arr = $field_to_arr;
    foreach ($field_to_arr as $value) {
        if (is_array($value)) {
            $field_to_simple_arr = array_merge($field_to_simple_arr, $value);
        }
    }

    // build one dimension fields and types array for "to" table
    foreach ($table_to_columns as $value) {
        if (!in_array($value[0], $field_to_simple_arr)) {
            continue;
        }
        $table_to_fields_arr[] = $value[0];
        if (!$ignore_type) {
            $table_to_types_arr[$value[0]] = $db->escape($value[1]);
        }
    }

    // check pair existance and compare fields type
    foreach ($fieds as $field_from => $field_to) {
        // if value into the "to" array is an array
        if (is_array($field_to)) {
            foreach ($field_to as $field_to_value) {
                // fields existed in tables
                if (!in_array($field_from, $table_from_fields_arr) || !in_array($field_to_value, $table_to_fields_arr)) {
                    return false;
                }
                // check fields type
                if (!$ignore_type && $table_from_types_arr[$field_from] != $table_to_types_arr[$field_to_value]) {
                    return false;
                }
                $from_query_arr[] = "`" . $field_from . "` AS " . md5($field_to_value);
                $to_query_arr[] = "`" . $field_to_value . "`";
            }
        } else {
            // fields existed in tables
            if (!in_array($field_from, $table_from_fields_arr) || !in_array($field_to, $table_to_fields_arr)) {
                return false;
            }
            // check fields type
            if (!$ignore_type && $table_from_types_arr[$field_from] != $table_to_types_arr[$field_to]) {
                return false;
            }

            $from_query_arr[] = "`" . $field_from . "`";
            $to_query_arr[] = "`" . $field_to . "`";
        }
    }

    // get data to swap
    $data_from = $db->get_results("SELECT " . join(", ", $from_query_arr) . " FROM `" . $table_from . "`" . $where_str, ARRAY_A);

    if (empty($data_from)) {
        return false;
    }

    $result = array();

    // insert data into table_to
    foreach ($data_from as $data_to_row) {
        $db->query("INSERT INTO `" . $table_to . "` (" . join(", ", $to_query_arr) . ") VALUES ('" . join("', '", $data_to_row) . "')");
        $result[] = $db->insert_id;
    }

    return $result;
}

/**
 * Возвращает протокол, по которому сделан текущий запрос
 *
 * @return string http or https
 */
function nc_get_scheme() {
    static $https;

    if ($https === null) {
        $https =
            (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
            (!empty($_SERVER['HTTP_HTTPS']) && strtolower($_SERVER['HTTP_HTTPS']) !== 'off') ||
            strtolower(nc_array_value($_SERVER, 'HTTP_X_FORWARDED_PROTO')) === 'https' ||
            nc_array_value($_SERVER, 'SERVER_PORT') === '443';
    }

    return $https ? 'https' : 'http';
}

/**
 * @param int $number число для которого выводим склонение
 * @param array $word_forms массив форм слова формата (единственное число, множественное, двойственное)
 * например array('этаж','этажей','этажа') или ('а','','ы') для слова "Квартир".
 * nc_numeral_inclination(20, array('этаж','этажей','этажа') )
 * @return string
 * @deprecated
 *
 */

function nc_numeral_inclination($number, $word_forms) {
    $word_forms_in_correct_order = array($word_forms[0], $word_forms[2], $word_forms[1]);
    return nc_Core::get_object()->lang->get_numerical_inclination($number, $word_forms_in_correct_order);
}

/**
 * Функция преобразует кавычки в спецсимволы html
 *
 * @param string $str
 * @return string
 */
function nc_quote_convert($str) {
    return str_replace(array('"', "'"), array('&quot;', '&#039;'), $str);
}

/**
 * Функция обрабатывает строку для присваения js-переменной
 * заменяет "a \r b " н "a" + "\r\" " "b"
 *
 * @param string $str
 * @return string
 */
function nc_text_for_js($str) {
    return str_replace(array("\r\n", "\r", "\n"), '" + "\n" + "', $str);
}

/**
 * Вставка текста в head
 *
 * @param string $buffer исходный текст страницы
 * @param string $text вставляемый текст
 * @param bool $insert_at_bottom если TRUE, вставить текст в конце head
 * @return string результат
 */
function nc_insert_in_head($buffer, $text, $insert_at_bottom = false) {
    if (!$text) {
        return $buffer;
    }

    //если нужно вставить в конец тега
    if ($insert_at_bottom) {
        return str_ireplace('</head>', $text . '</head>', $buffer);
    }

    //простой случай
    if (stripos($buffer, '<head>') !== false) {
        return str_ireplace('<head>', '<head>' . $text, $buffer);
    }

    switch (true) {
        case preg_match("/\<\s*?\/head\s*?\>/im", $buffer):
            $preg_pattern = "/(\<\s*?\/head\s*?\>){1}/im";
            $preg_replacement = $text . "\n\$1";
            break;
        case preg_match("/\<\s*?html\s*?\>/im", $buffer):
            $preg_pattern = "/(\<\s*?html\s*?\>){1}/im";
            $preg_replacement = "\$1\n<head>" . $text . "</head>";
            break;
        default:
            $preg_pattern = "/(\A)/im";
            $preg_replacement = $text . "\n\$1";
    }
    return preg_replace($preg_pattern, $preg_replacement, $buffer);
}

/**
 * Вставка текста в body
 *
 * @param string $buffer исходный текст страницы
 * @param string $text вставляемый текст
 * @param bool $insert_at_bottom если TRUE, вставить текст в конце body
 * @return string результат
 */
function nc_insert_in_body($buffer, $text, $insert_at_bottom = false) {
    if (!$text) {
        return $buffer;
    }

    if ($insert_at_bottom) {
        if (stripos($buffer, '</body>') !== false) {
            return str_ireplace('</body>', "$text</body>", $buffer);
        }

        if (preg_match("/<\s*?\/body\s*?>/im", $buffer)) {
            $preg_pattern = "/(<\s*?\/body\s*?>)/im";
            $preg_replacement = "$text\n\$1";
        } else {
            $preg_pattern = "/(\A)/im";
            $preg_replacement = "\$1\n$text";
        }

        return preg_replace($preg_pattern, $preg_replacement, $buffer);
    }

    if (stripos($buffer, '<body>') !== false) {
        return str_ireplace('<body>', '<body>' . $text, $buffer);
    }

    switch (true) {
        case preg_match("/<body[^>]*?>/im", $buffer):
            $preg_pattern = "/(<body[^>]*?>)/im";
            $preg_replacement = "\$1\n$text";
            break;
        case preg_match("/<\s*?html\s*?>/im", $buffer):
            $preg_pattern = "/(<\s*?html\s*?>)/im";
            $preg_replacement = "\$1\n<body>$text</body>";
            break;
        default:
            $preg_pattern = "/(\A)/im";
            $preg_replacement = "$text\n\$1";
    }

    return preg_replace($preg_pattern, $preg_replacement, $buffer);
}


function nc_quickbar_permission() {
    global $perm, $catalogue;

    if (!$perm instanceof Permission) {
        return false;
    }

    $allowed_sites = $perm->GetAllowSite(MASK_ADMIN | MASK_MODERATE, true);
    return $allowed_sites === null || in_array($catalogue, $allowed_sites) || nc_core::get_object()->InsideAdminAccess();
}


/**
 * Рекомендуемые скрипты для вставки в макет
 *
 * @return string html
 */
function nc_js() {
    static $released = false;
    global $NC_CHARSET, $AUTH_USER_ID;

    if ($released) {
        return '';
    }

    // get super object
    $nc_core = nc_Core::get_object();

    $add_slashes = nc_check_context_requires_escaping();

    $admin_mode = (
        $nc_core->get_variable("admin_mode") ||
        ($nc_core->get_settings("QuickBar") && nc_quickbar_permission())
    );

    // load jQuery and plugins
    $ret_jquery = nc_jquery(true, $admin_mode);

    // load CSS
    $ret_css = nc_css($add_slashes);

    // system nc variable
    $ret = "<script type='text/javascript'>" .
        "if (typeof(nc_token) == 'undefined') {" .
        "var nc_token = '" . $nc_core->token->get(+$AUTH_USER_ID) . "';" .
        "}" .
        "var nc_save_keycode = " . ($nc_core->get_settings('SaveKeycode') ? $nc_core->get_settings('SaveKeycode') : 83) . ";" . PHP_EOL .
        "var nc_autosave_use = '" . $nc_core->get_settings('AutosaveUse') . "';" . PHP_EOL .
        "var nc_autosave_type = '" . $nc_core->get_settings('AutosaveType') . "';" . PHP_EOL .
        "var nc_autosave_period = '" . $nc_core->get_settings('AutosavePeriod') . "';" . PHP_EOL .
        "var nc_autosave_noactive = '" . $nc_core->get_settings('AutosaveNoActive') . "';" . PHP_EOL .
        "</script>" . PHP_EOL;

    $files = array();

    if ($nc_core->get_settings('JSLoadModulesScripts')) {
        if ($nc_core->modules->get_by_keyword('auth')) {
            $files[] = nc_module_path('auth') . "auth.js";
        }

        if ($nc_core->modules->get_by_keyword('minishop')) {
            $files[] = nc_module_path('minishop') . "minishop.js";
        }
    }

    if ($nc_core->get_variable("inside_admin")) {
        $ret .= "<script type='text/javascript' language='Javascript'>" . PHP_EOL .
            "var NETCAT_PATH = '" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "'," . PHP_EOL .
            "ADMIN_PATH = '" . $nc_core->ADMIN_PATH . "'," . PHP_EOL .
            "ICON_PATH = '" . $nc_core->ADMIN_TEMPLATE . "img/';" . PHP_EOL .
            "</script>" . PHP_EOL;
        $files[] = $nc_core->ADMIN_PATH . "js/jquery.mousewheel.js";
        $files[] = $nc_core->ADMIN_PATH . "js/jquery.jscrollpane.min.js";
    }

    if ($admin_mode) {
        $lang = $nc_core->lang->detect_lang(1);
        if ($lang == 'ru') {
            $lang = $nc_core->NC_UNICODE ? "ru_utf8" : "ru_cp1251";
        }

        $files[] = $nc_core->ADMIN_PATH . "js/jquery.cookie.js";
        $files[] = $nc_core->ADMIN_PATH . "js/lang/" . $lang . ".js";
        $files[] = $nc_core->ADMIN_PATH . "js/nc/nc.min.js";
        $files[] = $nc_core->ADMIN_PATH . "js/nc/ui/modal_dialog.min.js";
        $files[] = $nc_core->ADMIN_PATH . "js/nc/ui/popover.min.js";
        $files[] = $nc_core->ADMIN_PATH . "js/nc/ui/help_overlay.min.js";
        if (in_array(strtolower($NC_CHARSET), array('cp1251', 'windows-1251', 'win-1251', '1251'))) {
            $files[] = $nc_core->ADMIN_PATH . 'js/transliterate-cp1251.js';
            $files[] = $nc_core->ADMIN_PATH . 'js/typofilter-cp1251.js';
        } else {
            $files[] = $nc_core->ADMIN_PATH . 'js/transliterate-utf8.js';
            $files[] = $nc_core->ADMIN_PATH . 'js/typofilter-utf8.js';
        }

        $files[] = $nc_core->ADMIN_PATH . "js/nc_admin.js";
        $files[] = $nc_core->ADMIN_PATH . "js/lib.js";
        $files[] = $nc_core->ADMIN_PATH . "js/forms.js";
        $files[] = $nc_core->ADMIN_PATH . "js/drag.js";
        $files[] = $nc_core->ADMIN_PATH . 'js/datepicker/bootstrap-datepicker.min.js';
        $files[] = $nc_core->ADMIN_PATH . 'js/revisions.js';
        $files[] = $nc_core->ADMIN_PATH . "js/versions.js";
        $files[] = $nc_core->ADMIN_PATH . 'js/image_dialog/jquery.minicolors.min.js';
        $files[] = $nc_core->ADMIN_PATH . 'js/uploader/jquery.dm-uploader.min.js';
        $files[] = $nc_core->ADMIN_PATH . 'js/validate.js';

        // mixins
        $files[] = $nc_core->ADMIN_PATH . 'js/mixin_editor/mixin_editor.min.js';

        // mixins — background: drag and drop reordering, gradients
        $files[] = $nc_core->ASSET_PATH . 'jquery_ui_core/1.12.1/jquery-ui.min.js';
        $files[] = $nc_core->ASSET_PATH . 'jquery_ui_widget_mouse/1.12.1/mouse.min.js';
        $files[] = $nc_core->ASSET_PATH . 'jquery_ui_widget_sortable/1.12.1/sortable.min.js';

        if ($nc_core->get_settings("InlineImageCropUse") == 1 && !$nc_core->get_variable("inside_admin")) {
            $tmp_dimensions = unserialize($nc_core->get_settings('InlineImageCropDimensions'));
            if (isset($tmp_dimensions['X']) && is_array($tmp_dimensions['X']) && count($tmp_dimensions['X']) > 0) {
                $crop_dimensions = array();
                foreach ($tmp_dimensions['X'] as $key => $value) {
                    $crop_dimensions[] = "'" . $tmp_dimensions['X'][$key] . "x" . $tmp_dimensions['Y'][$key] . "'";
                }
            } else {
                //default
                $crop_dimensions = array("'100x100'");
            }

            $ret .= "<script type='text/javascript'>" .
                "var nc_crop_ratio = [" . implode(",", $crop_dimensions) . "];" . PHP_EOL .
                "</script>" . PHP_EOL;

            $files[] = $nc_core->ADMIN_PATH . 'js/crop/jquery.crop.js';
            $files[] = $nc_core->ADMIN_PATH . 'js/nc_image_crop.js';
            $ret_css .= "<link rel='stylesheet' rev='stylesheet' type='text/css' href='" . nc_add_revision_to_url($nc_core->ADMIN_PATH . 'js/crop/jquery.crop.css') . "'>" . PHP_EOL;
            $ret_css .= "<link rel='stylesheet' rev='stylesheet' type='text/css' href='" . nc_add_revision_to_url($nc_core->ADMIN_PATH . "js/crop/jquery.Jcrop.min.css") . "'>" . PHP_EOL;
        }

        $ret .= "<script>var nc_edit_no_image = '" . $nc_core->ADMIN_PATH . "skins/v5/img/transparent-100x100.png';</script>" . PHP_EOL;
    }

    $files[] = $nc_core->ADMIN_PATH . "js/jquery.upload.min.js";

    $http_jquery_folder_path = nc_standardize_path_to_folder($nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . "jquery/");
    $files[] = $http_jquery_folder_path . "jquery.nclsdisplay.js";

    $minified = nc_minify_file($files, 'js');

    foreach ($minified as $file) {
        $ret .= "<script type='text/javascript' src='" . $file . "'></script>" . PHP_EOL;
    }

    if ($nc_core->inside_admin) {
        $ret .= "<script>nc.config('drag_mode', '" . $nc_core->get_settings('DragMode') . "')</script>" . PHP_EOL;
    }

    if ($admin_mode) {
        // (1) Include datepicker:
        $datepicker_html = "<link rel='stylesheet' href='" . nc_add_revision_to_url($nc_core->ADMIN_PATH . 'js/datepicker/datepicker.css') . "' />\n";

        // (2) Configure datepicker
        // datepicker docs: https://github.com/eternicode/bootstrap-datepicker
        $nc = '$nc';
        if (PHP_INT_SIZE == 4) {
            $start_date = date("Y-m-d", -PHP_INT_MAX);
            $end_date = date("Y-m-d", PHP_INT_MAX);
        } else {
            $start_date = "1901-01-01";
            $end_date = "2200-12-31";
        }

        $script = "
                (function(datepicker) {
                    if (datepicker) {
                        datepicker.dates['netcat'] = {
                            days: " . nc_array_json(explode(" ", NETCAT_DATEPICKER_CALENDAR_DAYS)) . ",
                            daysShort: " . nc_array_json(explode(" ", NETCAT_DATEPICKER_CALENDAR_DAYS_SHORT)) . ",
                            daysMin: " . nc_array_json(explode(" ", NETCAT_DATEPICKER_CALENDAR_DAYS_MIN)) . ",
                            months: " . nc_array_json(explode(" ", NETCAT_DATEPICKER_CALENDAR_MONTHS)) . ",
                            monthsShort: " . nc_array_json(explode(" ", NETCAT_DATEPICKER_CALENDAR_MONTHS_SHORT)) . ",
                            today: '" . addcslashes(NETCAT_DATEPICKER_CALENDAR_TODAY, "'") . "'
                        };
                        $nc.extend(datepicker.defaults, {
                            format: '" . NETCAT_DATEPICKER_CALENDAR_DATE_FORMAT . "',
                            language: 'netcat',
                            autoclose: true,
                            weekStart: 1,
                            startDate: '$start_date',
                            endDate: '$end_date'
                        });
                    }
                })($nc.fn.datepicker);
            ";

        $ret .= $datepicker_html . "<script>\n" . str_replace(array(" ", "\n", "\r"), "", $script) . "\n</script>\n";
    }

    $released = true;

    return $ret_jquery . $ret_css . ($add_slashes ? str_replace("\\'", "'", addcslashes($ret, "\"'$")) : $ret);
}


/**
 * Стили для вставки в макет
 *
 * @param bool $add_slashes [внутренний параметр, передача результата nc_check_context_requires_escaping() из nc_js]
 * @return string html
 */
function nc_css($add_slashes = null) {
    static $released = false;

    if ($released) {
        return '';
    }

    // get super object
    $nc_core = nc_Core::get_object();

    if ($add_slashes === null) {
        $add_slashes = nc_check_context_requires_escaping();
    }

    $files = array();
    $ret = '';
    if ($nc_core->modules->get_by_keyword('search')) {
        // TODO FIX THIS (overrides standard ui- styles!)
        //        $files[] = nc_search::get_module_url() . "/suggest/autocomplete.css";
    }

    $files[] = $nc_core->ADMIN_TEMPLATE . "css/upload.css";
    $files[] = $nc_core->ADMIN_PATH . "js/image_dialog/jquery.minicolors.css";

    if ($nc_core->get_variable("admin_mode") || ($nc_core->get_settings("QuickBar") && nc_quickbar_permission())) {
        if ($nc_core->get_variable("inside_admin")) {
            //FIXME: отключить "default/css/style.css" после полного переноса админки на новые стили
            $files[] = $nc_core->ADMIN_TEMPLATE . "css/style.css";
            $files[] = $nc_core->ADMIN_TEMPLATE . "css/sprites.css";
        }

        $files[] = $nc_core->ADMIN_TEMPLATE . "css/nc_admin.css";
        $files[] = $nc_core->ADMIN_TEMPLATE . "../v5/css/netcat.css";
    }

    $minified = nc_minify_file($files, 'css');

    foreach ($minified as $file) {
        $ret .= "<link rel='stylesheet' type='text/css' href='" . $file . "'>" . PHP_EOL;
    }

    $released = true;

    return ($add_slashes ? str_replace("\\'", "'", addslashes($ret)) : $ret);
}


/**
 * Внутренняя функция.
 * Проверка необходимости экранирования PHP-кода (для шаблонов v4)
 */
function nc_check_context_requires_escaping() {
    $nc_core = nc_core::get_object();
    // determine file mode
    $add_slashes = $nc_core->template->get_current() ?
        !$nc_core->template->get_current("File_Mode") :
        false;

    if ($add_slashes) {
        // get backtrace
        $debug_backtrace = (array)debug_backtrace();
        // search eval
        foreach ($debug_backtrace as $row) {
            if ($row['function'] == 'eval') {
                $add_slashes = false;
                break;
            }
        }
    }
    return $add_slashes;
}


/**
 * This function load jQuery and modules, once
 *
 * @param boolean addslashes or not
 * @param boolean load jQuery as $nc object or not
 *
 * @return mixed html text
 */
function nc_jquery($noconflict = false, $extensions = false) {
    static $released = array();
    static $released_mods = 0;

    if (isset($released[$noconflict])) {
        return;
    }

    // get super object
    $nc_core = nc_Core::get_object();
    // determine file mode
    if ($addslashes = (
    $nc_core->template->get_current() ?
        !$nc_core->template->get_current("File_Mode") :
        false
    )
    ) {
        // get backtrace
        $debug_backtrace = (array)debug_backtrace();
        // search eval
        foreach ($debug_backtrace as $row) {
            if ($row['function'] == 'eval') {
                $addslashes = false;
                break;
            }
        }
    }

    $http_jquery_folder_path = nc_standardize_path_to_folder($nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . "jquery/");

    $jquery_file_array = array();
    $jquery_dir = opendir($nc_core->JQUERY_FOLDER);

    $result = PHP_EOL . "<script type='text/javascript' src='" .
        nc_add_revision_to_url($http_jquery_folder_path . 'jquery.min.js') .
        "'></script>" . PHP_EOL;

    if ($noconflict) {
        $result .= "<script type='text/javascript'>var " . ($addslashes && $nc_core->admin_mode ? '\$nc' : '$nc') . " = jQuery.noConflict();</script>" . PHP_EOL;
        if ($nc_core->get_settings('JSLoadjQueryDollar')) {
            $result .= "<script type='text/javascript'>if (typeof $ == 'undefined') $ = jQuery;</script>" . PHP_EOL;
        }
    }

    $released[$noconflict] = 1;

    if (
        ($extensions ||
            $nc_core->get_settings("JSLoadjQueryExtensionsAlways")) &&
        !$released_mods
    ) {
        // modules to load
        while ($file = readdir($jquery_dir)) {
            if ($file == '.' || $file == '..' || strpos($file, '.') === 0) {
                continue;
            }
            if ($file == 'jquery.min.js' || $file == '_jquery.min.js') {
                continue;
            }
            $jquery_file_array[] = $http_jquery_folder_path . $file;
        }
        // sort files
        sort($jquery_file_array);
        // released_mods
        $released_mods++;
    }

    $ret = $result;

    if (count($jquery_file_array) > 0) {
        foreach (nc_minify_file($jquery_file_array, 'js') as $file) {
            $ret .= "<script src='" . $file . "'></script>" . PHP_EOL;
        }
    }

    return ($addslashes ? str_replace(array("\\'", "\\$"), array("'", "\$"), addslashes($ret)) : $ret);
}


function nc_cut_jquery($template) {
    return preg_replace("#<script.*?jquery.*?((/>)|(>.*?</script>))#mi", "", $template);
}


/**
 * Проверка email'a
 *
 * @param string $email
 * @return bool
 */
function nc_check_email($email) {
    return nc_preg_match("/^[a-z" . NETCAT_RUALPHABET . "0-9\._+-]+@[a-z" . NETCAT_RUALPHABET . "0-9\._-]+\.[a-z" . NETCAT_RUALPHABET . "]{2,63}$/i", $email);
}

/**
 * Encodes to punycode
 *
 * @param string $host ONLY the host name, e.g. "испытание.рф"
 * @return string
 */
function encode_host($host) {
    if (!preg_match("/[^\w\-\.]/", $host)) {
        return $host;
    }
    require_once 'Net/IDNA2.php'; // netcat/require/lib
    $encoder = new Net_IDNA2();
    try {
        $host = $encoder->encode(strtolower($host));
    } catch (Net_IDNA2_Exception $e) {
        trigger_error("Cannot convert host name '$host' to punycode: {$e->getMessage()}", E_USER_WARNING);
        return $host;
    }
    return $host;
}

/**
 * Decodes from punycode
 *
 * @param string $host ONLY the host name, e.g. "XN----7SBCPNF2DL2EYA.XN--P1AI"
 * @return string
 */
function decode_host($host) {
    if (stripos($host, "xn--") === false) {
        return $host;
    }
    require_once 'Net/IDNA2.php'; // netcat/require/lib
    $decoder = new Net_IDNA2();
    try {
        $host = $decoder->decode(strtolower($host));
    } catch (Net_IDNA2_Exception $e) {
        trigger_error("Cannot convert host name '$host' from punycode: {$e->getMessage()}", E_USER_WARNING);
        return $host;
    }
    return $host;
}

/**
 * Not part of the API
 *
 * @param string $string
 * @param int $tag
 * @param int $start
 * @return int|bool
 */
function nc_find_closing_tag_position($string, $tag, $start) {
    $string = strtolower($string);
    $tag = strtolower($tag);

    $opened_tag_count = 1; // includes the tag for which we are looking the closing tag

    $opening_tag = "<$tag";
    $closing_tag = "</$tag";

    $opening_tag_length = strlen($opening_tag);
    $closing_tag_length = strlen($closing_tag);

    $current_position = $start;
    $last_position = strlen($string) - $closing_tag_length;

    while ($current_position < $last_position) {
        if (substr($string, $current_position, $opening_tag_length) === $opening_tag) {
            $opened_tag_count++;
            $current_position += $opening_tag_length;
            continue;
        }

        if (substr($string, $current_position, $closing_tag_length) === $closing_tag) {
            if ($opened_tag_count === 1) {
                $tag_end = strpos($string, ">", $current_position + $closing_tag_length);
                return $tag_end !== false ? $current_position : false;
            } else {
                $current_position += $closing_tag_length;
                $opened_tag_count--;
                continue;
            }
        }

        $current_position++;
    }

    return false;
}

function nc_prepare_message_form(
    $form,
    $action,
    $admin_mode,
    $user_table_mode,
    $sys_table_id,
    $current_cc,
    $f_Checked = null,
    $f_Priority = '',
    $f_Keyword = '',
    $f_ncTitle = '',
    $f_ncKeywords = '',
    $f_ncDescription = '',
    $have_seo = true,
    $eval_ready = false,
    $f_ncSMO_Title = '',
    $f_ncSMO_Description = '',
    $f_ncSMO_Image = '',
    $f_ncDemoContent = null,
    $f_ncFullPageMode = 'inherit'
) {

    global $isNaked, $inside_admin, $perm;

    $nc_core = nc_Core::get_object();

    if ($nc_core->input->fetch_get_post('nc_no_form_modification') || ($admin_mode && !$nc_core->input->fetch_get_post('admin_modal'))) {
        return $form;
    }

    if (null === $f_Checked && (1 == $current_cc['Moderation_ID'] || $current_cc['AreaKeyword'])) {
        $f_Checked = 1;
    }

    // Добавление параметра inside_admin в форму
    if ($inside_admin) {
        $form = str_ireplace('</form>', "<input type='hidden' name='inside_admin' value='1'></form>", $form);
    }

    // Форма может
    // (1) содержать полную разметку диалога (см. /admin/js/nc/ui/modal_dialog.js)
    // (2) содержать разметку для вкладок → нужно обернуть в полную разметку
    // (3) быть без разметки диалога → обернуть во вкладку и в полную разметку
    //
    // Разметка для вкладок в теле диалога
    // (а) может присутствовать → нужно добавить вкладку «Дополнительно»
    // (б) может отсутствовать → нужно обернуть тело диалога во вкладку и добавить вкладку «Дополнительно»

    // DOMDocument не используется из-за боязни повредить html-код в существующих формах
    // и опасения, что libxml может быть отключена

    // Определение наличия или отсутствия частей диалога производится по наличию
    // названий css-классов в теле формы
    if (($admin_mode || ($inside_admin && $isNaked)) && !preg_match('/\bnc-modal-dialog\b/', $form)) {
        $form = "<div class='nc-modal-dialog'>\n" .
            "<div class='nc-modal-dialog-header'>" .
            "<h2>" . htmlspecialchars($current_cc['Sub_Class_Name']) . "</h2>" .
            "</div>\n" .
            "<div class='nc-modal-dialog-body'>\n" .
            $form .
            "\n</div>\n" .
            "<div class='nc-modal-dialog-footer'>\n" .
            ($nc_core->get_settings('AutosaveUse') == 1
                ? "<button type='button' class='nc_draft_btn nc-btn nc--blue' data-action='save-draft'>" . NETCAT_SAVE_DRAFT . "</button>"
                : "") .
            "<button data-action='submit'>" . NETCAT_REMIND_SAVE_SAVE . "</button>\n" .
            "<button data-action='close'>" . CONTROL_BUTTON_CANCEL . "</button>\n" .
            "</div>\n" .
            "</div>";
    }

    if (!CheckUserRights($current_cc['Sub_Class_ID'], "change", 0) || !$admin_mode || (!$inside_admin && !$isNaked)) {
        if ($nc_core->component->can_add_block_markup($current_cc['Class_Template_ID'] ?: $current_cc['Class_ID'])) {
            $nc_core->page->register_component_usage($current_cc['Class_ID'], $current_cc['Class_Template_ID']);
            $nc_component_css_class = $nc_core->component->get_css_class_name($current_cc['Class_Template_ID'] ?: $current_cc['Class_ID'], $current_cc['Class_ID']);
            $nc_block_id = nc_make_block_id($form);
            $form = "<div class='tpl-block-$action-form " . $nc_component_css_class . "' id='" . $nc_block_id . "'>" . $form . "</div>";
        }
        return $form;
    }

    // делаем так, чтобы форма была «снаружи» вкладок

    $body_inner_html_start = strpos($form, ">", strpos($form, "nc-dialog-body"));
    $body_inner_html_finish = nc_find_closing_tag_position($form, "div", $body_inner_html_start);
    $body_inner_html = substr($form, $body_inner_html_start, $body_inner_html_finish - $body_inner_html_start);
    $form_tag_start = stripos($body_inner_html, "<form");
    if ($form_tag_start) {
        $form_inner_html_start = strpos($body_inner_html, ">", $form_tag_start) + 1;
        $form_inner_html_finish = stripos($body_inner_html, "</form");
        $form_inner_html = substr($body_inner_html, $form_inner_html_start, $form_inner_html_finish - $form_inner_html_start);

        // Если нет вкладок в разметке формы — оборачиваем содержимое во вкладку «Основное»
        if (!preg_match('/\bdata-tab-caption\b/', $body_inner_html)) {
            $form_inner_html = "<div data-tab-id='tab-main' data-tab-caption='" . htmlspecialchars(NETCAT_MESSAGE_FORM_MAIN, ENT_QUOTES) . "'>" .
                $form_inner_html .
                "</div>";
        }

        // Добавление вкладки «Комплекты» пока что зашито здесь
        $add_itemset_tab = nc_module_check_by_keyword('netshop') &&
            in_array($current_cc['Class_ID'], nc_netshop::get_instance()->get_goods_components_ids()) &&
            $nc_core->get_component($current_cc['Class_ID'])->has_field('ItemSet_ID');

        if ($add_itemset_tab) {
            $itemset_id = $GLOBALS['f_ItemSet_ID'];
            $is_variant = (int)($GLOBALS['f_Parent_Message_ID'] > 0);
            $iframe_url = nc_module_path('netshop') .
                "admin/?controller=itemset&amp;action=item_main" .
                "&amp;itemset_id=$itemset_id&is_variant=$is_variant" .
                "&amp;component_id=$current_cc[Class_ID]&site_id=$GLOBALS[catalogue]";

            $form_inner_html .=
                "<div data-tab-id='tab-itemset' data-tab-caption='" . htmlspecialchars(NETCAT_MODULE_NETSHOP_ITEMSET_TAB, ENT_QUOTES) . "'>\n" .
                "<iframe src='$iframe_url' class='nc-netshop-item-itemset-frame nc--fill'></iframe>\n" .
                "</div>\n" .
                "<input type='hidden' name='f_ItemSet_ID' value='$itemset_id' />\n";
        }

        // Готовим вкладку «Дополнительно»
        if ($have_seo) {
            $seo = "<div data-tab-id='tab-system' data-tab-caption='" . htmlspecialchars(NETCAT_MESSAGE_FORM_ADDITIONAL, ENT_QUOTES) . "'>";

            if ('change' == $action && !$user_table_mode) {
                global $message;

                $SQL = "SELECT `uAdd`.`{$nc_core->AUTHORIZE_BY}` as `user_add`,
                                `uEdit`.`{$nc_core->AUTHORIZE_BY}` as `user_edit`,
                                a.`IP`,
                                a.`LastIP`,
                                UNIX_TIMESTAMP(a.`Created`) as `Created`,
                                UNIX_TIMESTAMP(a.`LastUpdated`) as `LastUpdated`
                            FROM `Message{$current_cc['Class_ID']}` AS `a`
                              LEFT JOIN `User` as `uAdd` ON `uAdd`.`User_ID` = `a`.`User_ID`
                              LEFT JOIN `User` as `uEdit` ON `uEdit`.`User_ID` = `a`.`LastUser_ID`
                                WHERE `Message_ID` = " . +$message;
                $info = $nc_core->db->get_row($SQL, ARRAY_A);

                // @todo TEMPORARY:
                $f_ncDemoContent = $nc_core->db->get_var("SELECT `ncDemoContent` FROM `Message$current_cc[Class_ID]` WHERE `Message_ID` = " . (int)$message);

                $seo .= "<div class='nc_admin_settings_info nc_seo_edit_info'>
                            <div class='nc_admin_settings_info_actions'>
                                <div>
                                    <span>" . CLASS_TAB_CUSTOM_ADD . ":</span> " . date("d.m.Y H:i:s", $info['Created']) . " {$info['user_add']} ({$info['IP']})
                                </div>";

                if ($info['user_edit']) {
                    $seo .= "   <div>
                                    <span>" . CLASS_TAB_CUSTOM_EDIT . ":</span> " . date('d.m.Y H:i:s', $info['LastUpdated']) . " {$info['user_edit']} ({$info['LastIP']})
                                </div>";
                }
                $seo .= '   </div>';
                $seo .= '</div>';
            }

            $nc_can_moderate_infoblock =
                $perm instanceof Permission &&
                ($perm->isSubClass($current_cc['Sub_Class_ID'], MASK_MODERATE) ||
                    $perm->isSubClass($current_cc['Sub_Class_ID'], MASK_ADMIN));

            if ($nc_can_moderate_infoblock || $perm->isSubClass($current_cc['Sub_Class_ID'], MASK_CHECKED)) {
                $seo .= "<div class='nc_admin_settings_info_checked'>
                            <input id='chk' name='f_Checked' type='checkbox' value='1' " . ($f_Checked ? "checked='checked'" : "") . " />
                            <label for='chk'>" . NETCAT_MODERATION_TURNON . "</label>
                        </div>";
            }

            $nc_mode = $nc_core->catalogue->get_by_id($current_cc['Catalogue_ID'], 'ncMode');
            if ($nc_mode === 'template_development' || $nc_mode === 'demo') {
                if ($perm instanceof Permission && $perm->isSupervisor()) {
                    $seo .= "<div class='nc_admin_settings_info_checked'>
                                <input id='chkDemoContent' name='f_ncDemoContent' type='checkbox' value='1' " . ($f_ncDemoContent ? "checked='checked'" : "") . " />
                                <label for='chkDemoContent'>" . NETCAT_MODERATION_DEMO_CONTENT . "</label>
                            </div>";
                } else {
                    if ($f_ncDemoContent) {
                        $seo .= "<div class='nc-alert nc--blue'><i class='nc-icon-l nc--status-info'></i>" . NETCAT_MODERATION_DEMO_CONTENT . "</div>";
                    }
                }
            }

            $seo .= "<div class='nc_admin_settings_info_priority'>
                        <div>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY . ":</div>
                        <div><input name='f_Priority' type='text' size='3' maxlength='10' value='" . ($f_Priority ? +$f_Priority : '') . "' /></div>
                    </div>";

            if (($current_cc['File_Mode'] && is_object($class_view = nc_tpl_component_view::get_instanse())) || !$current_cc['File_Mode']) {
                $seo .= "
                    <div>
                        <div>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD . ":</div>
                        <div><input type='text' name='f_Keyword' style='width: 100%' maxlength='255' value='" . htmlspecialchars($f_Keyword, ENT_QUOTES) . "'></div>
                    </div>
                    <div>
                        <div>" . NETCAT_MODERATION_SEO_TITLE . ":</div>
                        <div><input type='text' name='f_ncTitle' style='width: 100%' value='" . htmlspecialchars($f_ncTitle, ENT_QUOTES) . "' /></div>
                    </div>
                    <div>
                        <div>" . NETCAT_MODERATION_SEO_KEYWORDS . ":</div>
                        <div><textarea name='f_ncKeywords'>" . htmlspecialchars($f_ncKeywords, ENT_QUOTES) . "</textarea></div>
                    </div>
                    <div>
                        <div>" . NETCAT_MODERATION_SEO_DESCRIPTION . ":</div>
                        <div><textarea name='f_ncDescription'>" . htmlspecialchars($f_ncDescription, ENT_QUOTES) . "</textarea></div>
                    </div>";

                $seo .= "
                    <div>
                        <div>" . NETCAT_MODERATION_SMO_TITLE . ":</div>
                        <div><input type='text' name='f_ncSMO_Title' style='width: 100%' value='" . htmlspecialchars($f_ncSMO_Title, ENT_QUOTES) . "' /></div>
                        <div class='nc-field-hint-line'> " . NETCAT_MODERATION_SMO_TITLE_HELPER . "</div>
                    </div>
                    <div>
                        <div>" . NETCAT_MODERATION_SMO_DESCRIPTION . ":</div>
                        <div><input type='text' name='f_ncSMO_Description' style='width: 100%' value='" . htmlspecialchars($f_ncSMO_Description, ENT_QUOTES) . "' /></div>
                        <div class='nc-field-hint-line'> " . NETCAT_MODERATION_SMO_DESCRIPTION_HELPER . "</div>
                    </div>
                    <div>
                        <div>" . NETCAT_MODERATION_SMO_IMAGE . ":</div>
                        <div>" . nc_file_field('ncSMO_Image') . "</div>
                    </div>";
            }

            $form_inner_html .= $seo;

            if ($current_cc['File_Mode']) {
                $full_page_options = array(
// @todo: actual inherited value
                    'inherit' => sprintf(NETCAT_MODERATION_FULL_PAGE_INHERIT, NETCAT_MODERATION_FULL_PAGE_TEMPLATE),
                    'template' => NETCAT_MODERATION_FULL_PAGE_TEMPLATE,
                    'custom' => NETCAT_MODERATION_FULL_PAGE_CUSTOM,
                    'none' => NETCAT_MODERATION_FULL_PAGE_NONE,
                );
                $form_inner_html .=
                    "<div>
                        <div>" . NETCAT_MODERATION_FULL_PAGE_MODE . ":</div>
                        <div>" . nc_admin_select_simple('', 'f_ncFullPageMode', $full_page_options, $f_ncFullPageMode) . "</div>
                    </div>";
            }

            $form_inner_html .= "</div>"; // закончили с подготовкой вкладки «Дополнительно»
        }

        $body_inner_html = substr($body_inner_html, 0, $form_inner_html_start) .
            $form_inner_html .
            substr($body_inner_html, $form_inner_html_finish);
    }

    $form = substr($form, 0, $body_inner_html_start) .
        $body_inner_html .
        substr($form, $body_inner_html_finish);

    return $form;
}


function nc_field_validation($tag, $name, $id, $type, $not_null, $format = null) {
    $v_not_null = $not_null ? "console.log(val); if(val.length == 0) {response = false;}" : "";
    $v_type = '';
    $v_format = '';

    switch ($type) {
        case 'date':
        case 'float':
        case 'int':
            $v_type = "if((val+0) != val) {response = false;}" . $v_type;
            break;
    }

    $regular = '';
    switch ($format) {
        case 'email':
            $regular = "[\w]+@[\w]+\.[\w]+";
            break;
        case 'url':
            $regular = "(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?";
            break;
    }
    $v_format = $regular ? "if(!(/^" . $regular . "$/i.test(val))) {response = false;}" : '';

    if (!$v_not_null && !$v_type && !$v_format) {
        return '';
    }

    $znak = $type = 'date' ? '^=' : '=';

    return "<script>\$nc('" . $tag . "[name" . $znak . "\"" . $name . "\"]').change(function () {
        var val = \$nc(this).val(), name = \$nc(this).attr('name'), form = \$nc(this).closest('form'), response = true;
        " . $v_not_null . $v_type . $v_format . "
        if(response) {
            if(window.nc_object_edit_errors != null) {
                \$nc('#nc_capfld_" . $id . "').css('color', '');
                if(window.nc_object_edit_errors['f" . $id . "'] != null && window.nc_object_edit_errors['f" . $id . "'] != 0) {
                    --window.nc_object_edit_errors['f" . $id . "'];
                }
                var count = 0;
                for(k in window.nc_object_edit_errors) {
                    if(window.nc_object_edit_errors[k] != 0) {
                        count++;
                    }
                }
                if(count == 0) {
                    \$nc('#nc_form_result div.nc_admin_form_buttons button').removeClass('nc--disable');
                }
            }
        }
        else {
            \$nc('#nc_capfld_" . $id . "').css('color', 'red');
            if(window.nc_object_edit_errors == null) {
                window.nc_object_edit_errors = {};
            }
            if(window.nc_object_edit_errors['f" . $id . "'] == null) {
                window.nc_object_edit_errors['f" . $id . "'] = 0;
            }
            ++window.nc_object_edit_errors['f" . $id . "'];
            \$nc('#nc_form_result div.nc_admin_form_buttons button').addClass('nc--disable');
        }
    });</script>";
}

function nc_field_caption($field, $caption_style = null) {
    $description = $field['description'] ? $field['description'] : $field['name'];
    return "<span class='nc-field-caption' style='$caption_style' id='nc_capfld_" . $field['id'] . "'>" . $description . ($field['not_null'] ? " (*)" : "") . ":</span>\r\n";
}

function nc_multiple_changes_string($field_name, $message, $value, $style = "", $classID = "", $caption = false) {
    return nc_replace_name_for_multiple_changes(nc_string_field($field_name, $style, $classID, $caption, $value, $valid), $field_name, $message);
}

function nc_multiple_changes_int($field_name, $message, $value, $style = "", $classID = "", $caption = false) {
    return nc_replace_name_for_multiple_changes(nc_int_field($field_name, $style, $classID, $caption, $value), $field_name, $message);
}

function nc_multiple_changes_text($field_name, $message, $value, $style = "", $classID = "", $caption = false, $bbcode = false) {
    return nc_replace_name_for_multiple_changes(nc_text_field($field_name, $style, $classID, $caption, $bbcode, $value), $field_name, $message);
}

function nc_multiple_changes_list($field_name, $message, $value = false, $style = "", $classID = "", $caption = false, $selected = false, $disabled = false, $unused = null, $ignore_check = false, $type = null) {
    return nc_replace_name_for_multiple_changes(nc_list_field($field_name, $style, $classID, $caption, $value, $disabled, $unused, $ignore_check, $type), $field_name, $message);
}

function nc_multiple_changes_bool($field_name, $message, $value, $style = "", $classID = "", $caption = false) {
    return nc_replace_name_for_multiple_changes(nc_bool_field($field_name, $style, $classID, $caption, $value), $field_name, $message);
}

function nc_multiple_changes_float($field_name, $message, $value, $style = "", $classID = "", $caption = false) {
    return nc_replace_name_for_multiple_changes(nc_float_field($field_name, $style, $classID, $caption, $value), $field_name, $message);
}

/*
function nc_multiple_changes_file($field_name, $message, $value, $style = "", $classID = "", $caption = false, $getData = false) {
    return nc_replace_name_for_multiple_changes(nc_file_field($field_name, $style, $classID, $caption, $getData), $field_name, $message);
}

function nc_multiple_changes_date($field_name, $message, $style = "", $classID = "", $caption = false, $dateDiv = "-", $timeDiv = ":", $select = false, $use_calendar = null, $calendar_theme = 0, $calendar_template = "") {
    return nc_replace_name_for_multiple_changes(nc_date_field($field_name, $style, $classID, $caption, $dateDiv, $timeDiv, $select, $use_calendar, $calendar_theme, $calendar_template), $field_name, $message);
}

function nc_multiple_changes_related($field_name, $message, $change_template = "", $remove_template = "") {
    return nc_replace_name_for_multiple_changes(nc_related_field($field_name, $change_template, $remove_template), $field_name, $message);
}

function nc_multiple_changes_multilist($field_name, $message, $style = "", $type = "", $classID = "", $caption = false, $selected = false, $disabled = false, $getData = false, $ignore_check = false) {
    return nc_replace_name_for_multiple_changes(nc_multilist_field($field_name, $style, $type, $classID, $caption, $selected, $disabled, $getData, $ignore_check), $field_name, $message);
}
*/

function nc_replace_name_for_multiple_changes($input, $name, $message) {
    return preg_replace("/name='.*?'/", "name='nc_multiple_changes[$message][$name]'", $input);
}


function nc_multiple_changes_prefix() {
    global $sub, $cc, $catalogue, $curPos;
    return "
        <form name='adminForm' class='nc-form' id='adminForm' enctype='multipart/form-data' method='post' action='" . nc_Core::get_object()->SUB_FOLDER . nc_Core::get_object()->HTTP_ROOT_PATH . "message.php'>
            <input name='catalogue' type='hidden' value='$catalogue' />
            <input name='cc' type='hidden' value='$cc' />
            <input name='sub' type='hidden' value='$sub' />
            <input name='curPos' type='hidden' value='$curPos' />
            <input name='posting' type='hidden' value='1' />
            <input name='multiple_changes' type='hidden' value='1' />
            <input name='message' type='hidden' value='1' />";
}


function nc_multiple_changes_suffix() {
    return "
        </form>";
}


/**
 * @param int $quantity_items
 * @param string $one
 * @param string $two
 * @param string $many
 * @return string
 * @deprecated
 *
 */
function plural_form($quantity_items, $one, $two, $many) {
    return nc_Core::get_object()->lang->get_numerical_inclination($quantity_items, array($one, $two, $many));
}

function nc_get_http_folder($root_folder) {
    return nc_standardize_path_to_folder('/' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $root_folder));
}


/**
 * Check PHP file in PHP 5.*
 *
 * @param string $file file path
 * @return boolean checking result
 * @throws Exception
 */
function nc_check_php_file($file) {
    // get file data
    @$code = file_get_contents($file);

    // file existence
    if ($code === false) {
        throw new Exception('File ' . $file . ' does not exist');
    }

    // tokenizer not installed
    if (!function_exists('token_get_all')) {
        return true;
    }

    $braces = 0;
    $inString = 0;
    $phpTagIsOpened = 0;
    foreach (token_get_all($code) as $token) {
        if (is_array($token)) {
            switch ($token[0]) {
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_START_HEREDOC:
                    ++$inString;
                    break;
                case T_END_HEREDOC:
                    --$inString;
                    break;
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    ++$phpTagIsOpened;
                    break;
                case T_CLOSE_TAG:
                    --$phpTagIsOpened;
                    break;
            }
        } else if ($inString & 1) {
            switch ($token) {
                case '`':
                case '"':
                    --$inString;
                    break;
            }
        } else {
            switch ($token) {
                case '`':
                case '"':
                    ++$inString;
                    break;

                case '{':
                    ++$braces;
                    break;
                case '}':
                    if ($inString) {
                        --$inString;
                    } else {
                        --$braces;
                        if ($braces < 0) {
                            throw new Exception('Braces problem!');
                        }
                    }
                    break;
            }
        }
    }

    if ($braces) {
        throw new Exception('Braces problem!');
    }

    $res = false;

    ob_start();
    @ini_set('display_errors', 'on');

    if ($phpTagIsOpened) {
        $res = eval('if (0) {?>' . $code . ' ?><?php }; return true;');
    } else {
        $res = eval('if (0) {?>' . $code . '<?php }; return true;');
    }

    @ini_set('display_errors', 'off');
    $error_text = ob_get_clean();

    if (!$res) {
        throw new Exception($error_text);
    }

    return true;
}

/**
 * Добавляет WYSIWYG-редактор для inline-редактирования текстового поля в режиме
 * редактирования; в остальных режимах выводит содержимое поля.
 *
 * @param string $field_name Имя поля
 * @param int $object_id ID записи в таблице Message
 * @param int|null $infoblock_id ID инфоблока (если не задано — текущий инфоблок)
 * @return string
 * @throws Exception
 */
function nc_edit_inline($field_name, $object_id, $infoblock_id = null) {
    $nc_core = nc_Core::get_object();

    $infoblock_data = $infoblock_id ? $nc_core->sub_class->get_by_id($infoblock_id) : $nc_core->sub_class->get_current();

    $component_id = $infoblock_data['Class_ID'];
    $object_data = $nc_core->message->get_by_id($component_id, $object_id);
    $value = $object_data[$field_name];

    $field = $nc_core->get_component($component_id)->get_field($field_name);
    $format = nc_field_parse_format($field['format'], $field['type']);
    $format_html = nc_array_value($format, 'html');
    $html_disabled = (!$infoblock_data['AllowTags'] && !$format_html) || $format_html == 2;

    if ($html_disabled) {
        $value = htmlspecialchars($value, ENT_QUOTES, $nc_core->NC_CHARSET, false);
    }

    if (!$nc_core->inside_admin && $nc_core->admin_mode && s_auth($infoblock_data, 'change', true, $object_data['User_ID'])) {
        if (!class_exists("CKEditor")) {
            include_once($nc_core->ROOT_FOLDER . "editors/ckeditor4/ckeditor.php");
        }

        // Для /netcat/index.php для областей нужно передать sub, чтобы загрузить окружение.
        // Передаём ID текущего раздела
        $subdivision_id = $infoblock_data['AreaKeyword'] ? $nc_core->subdivision->get_current('Subdivision_ID') : $infoblock_data['Subdivision_ID'];

        $title = $nc_core->get_component($component_id)->get_field($field_name, 'description');
        $type = $nc_core->get_component($component_id)->get_field($field_name, 'type');
        $single_line = $type != NC_FIELDTYPE_TEXT;
        $save_url = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'message.php?cc=' . $infoblock_id;
        $data = array(
            'token' => $nc_core->token->get(),
            'catalogue' => $infoblock_data['Catalogue_ID'],
            'sub' => $subdivision_id,
            'cc' => $infoblock_id,
            'message' => $object_id,
            'posting' => 1,
            'partial' => 1,
            'isNaked' => 1,
            'f_Checked' => $object_data['Checked'],
        );

        $new_value_field = 'f_' . $field_name;

        $editor_flags = 0;
        if ($html_disabled) {
            $editor_flags |= CKEditor::NO_HTML | CKEditor::NO_TOOLBAR;
        }
        if ($field['type'] == NC_FIELDTYPE_STRING) {
            $editor_flags |= CKEditor::SINGLE_LINE | CKEditor::NO_TOOLBAR;
        }

        $CKEditor = new CKEditor();
        $html = $CKEditor->getInlineScript($field['description'], $value, $save_url, $data, $new_value_field, $editor_flags);
    } else {
        $html = $value;
    }

    return $html;
}

/**
 * Добавляет WYSIWYG-редактор для inline-редактирования значения пользовательской
 * настройки компонента в режиме редактирования; в остальных режимах выводит
 * значение данной настройки.
 *
 * @param string $field_name Название параметра пользовательских настроек инфоблока
 * @param int|null $infoblock_id ID инфоблока (если не задано — текущий инфоблок)
 * @return string
 * @throws Exception
 */
function nc_infoblock_custom_setting_edit_inline($field_name, $infoblock_id = null) {
    $nc_core = nc_Core::get_object();

    $infoblock_data = $infoblock_id ? $nc_core->sub_class->get_by_id($infoblock_id) : $nc_core->sub_class->get_current();

    $settings = $infoblock_data['Sub_Class_Settings'];

    if (!$nc_core->inside_admin && $nc_core->admin_mode && CheckUserRights($infoblock_data['Sub_Class_ID'], 'admin', true)) {
        if (!class_exists('CKEditor')) {
            include_once($nc_core->ROOT_FOLDER . 'editors/ckeditor4/ckeditor.php');
        }

        $custom_settings_fields = nc_a2f::evaluate($infoblock_data['CustomSettingsTemplate']);
        if (!isset($custom_settings_fields[$field_name])) {
            trigger_error(__FUNCTION__ . "(): field '$field_name' does not exist in the component custom settings", E_USER_WARNING);
            return '';
        }
        $title = $custom_settings_fields[$field_name]['caption'];
        $save_url = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?cc=' . $infoblock_id;
        $data = array(
            'ctrl' => 'admin.infoblock',
            'action' => 'update_custom_setting',
            'infoblock_id' => $infoblock_data['Sub_Class_ID'],
            'key' => $field_name,
            'token' => $nc_core->token->get(),
        );

        $editor_flags = 0;

        if ($custom_settings_fields[$field_name]['type'] != 'textarea') {
            $editor_flags |= CKEditor::SINGLE_LINE | CKEditor::NO_TOOLBAR;
        }

        $CKEditor = new CKEditor();
        $html = $CKEditor->getInlineScript($title, $settings[$field_name], $save_url, $data, 'value', $editor_flags);
    } else {
        $html = $settings[$field_name];
    }

    return $html;
}

/**
 * Выводит код для in-place замены изображения в режиме редактирования,
 * в других режимах выводит тег <img>.
 *
 * @param string $field_name
 * @param int $object_id
 * @param int $infoblock_id
 * @param array $attributes
 * @return string
 * @throws Exception
 */
function nc_image_edit_inline($field_name, $object_id, $infoblock_id = null, $attributes = array()) {
    $nc_core = nc_Core::get_object();
    $infoblock_data = $infoblock_id ? $nc_core->sub_class->get_by_id($infoblock_id) : $nc_core->sub_class->get_current();
    $component_id = $infoblock_data['Class_ID'];
    $file_info = $nc_core->file_info->get_file_info($component_id, $object_id, $field_name, false);

    /** @var nc_image_path_field $file */
    $file = $file_info['download_path'];
    return $file ? $file->as_img($attributes) : '';
}


/**
 * Добавляет оверлей, нажатие на который открывает указанный диалог
 *
 * @param string $dialog_url путь к открываемому при нажатии диалогу
 * @param string $icon_size размер иконки в оверлее: '' (по умолчанию), 'small', 'large', 'xlarge'
 * @return string
 */
function nc_modal_dialog_trigger($dialog_url, $icon_size = '') {
    static $script_emitted = false;
    $nc_core = nc_core::get_object();
    if ($nc_core->admin_mode) {
        $extra_classes = '';
        if ($icon_size) {
            $extra_classes = " nc--$icon_size";
        }

        $result = "<div class='nc-modal-trigger-overlay{$extra_classes}' data-edit-url='" .
            htmlspecialchars($dialog_url, ENT_QUOTES, $nc_core->NC_CHARSET, false) .
            "'></div>";
        if (!$script_emitted) {
            $result .= <<<'SCRIPT'
<script>
function nc_modal_dialog_trigger_init() {
    var c = '.nc-modal-trigger-overlay';
    $nc(c).off('click'+c).on('click'+c, function() {
        nc.load_dialog($nc(this).data('edit-url') + '&_=' + new Date().getTime());
    });
}
$nc(nc_modal_dialog_trigger_init);
</script>
SCRIPT;
            $script_emitted = true;
        }
        return $result;
    }
    return "";
}

/**
 * Вычисляет воспринимаемую яркость цвета, возвращает true для «светлых» цветов
 *
 * @param string $hex_color Цвет в виде hex-строки
 * @param int $cutoff_value Условная граница тёмных и светлых цветов
 * @return string
 */
function nc_is_bright_color($hex_color, $cutoff_value = 161) {
    $hex_color = trim($hex_color, "#");

    $red = hexdec(substr($hex_color, 0, 2));
    $green = hexdec(substr($hex_color, 2, 2));
    $blue = hexdec(substr($hex_color, 4, 2));

    $brightness = (int)sqrt($red * $red * 0.241 + $green * $green * 0.691 + $blue * $blue * 0.068);

    return $brightness > $cutoff_value;
}

/**
 * Возвращает тип цвета
 *
 * @param $color
 * @param string $dark_value
 * @param string $bright_value
 * @param string $transparent_value
 * @param string $default_value
 * @param int $cutoff_value
 * @return string
 */
function nc_get_color_type($color, $dark_value = 'dark', $bright_value = 'bright', $transparent_value = 'transparent', $default_value = 'bright', $cutoff_value = 161) {
    if (!$color) {
        return $default_value;
    }

    if ($color == 'transparent') {
        return $transparent_value;
    }

    return nc_is_bright_color($color, $cutoff_value) ? $bright_value : $dark_value;
}

/**
 * Возвращает специальный атрибут для ссылки
 * в shortpage или longpage
 *
 * @return string
 */
function nc_ls_display_link($subdivisionId = null, $animationSpeed = 0, $displayType = null, $onClick = null, $query_data = array()) {
    $ncCore = nc_Core::get_object();

    $data['query'] = $query_data;

    if ($ncCore->inside_admin || $ncCore->admin_mode) {
        return '';
    }
    if (!$displayType) {
        $inputDisplayType = $ncCore->input->fetch_get('lsDisplayType');
        $subdivisionDisplayType = $ncCore->get_display_type();

        $displayType = $inputDisplayType ? $inputDisplayType : $subdivisionDisplayType;
    }

    $result = '';

    if ($displayType != 'traditional') {
        $data['displayType'] = $displayType;
        $data['animationSpeed'] = $animationSpeed;

        if ($subdivisionId !== null) {
            $data['subdivisionId'] = $subdivisionId;
        }

        if ($onClick !== null) {
            $data['onClick'] = $onClick;
        }

        $data = htmlentities(json_encode($data));

        return "data-nc-ls-display-link='{$data}'";
    }

    return $result;
}

/**
 * Возвращает специальный атрибут для html-форм
 * в shortpage или longpage
 *
 * @return string
 */
function nc_ls_display_form($subdivisionId = null, $animationSpeed = 0, $displayType = null, $onSubmit = null, $query_data = array()) {
    $ncCore = nc_Core::get_object();
    $result = '';
    $data = array();

    $data['query'] = $query_data;

    if ($ncCore->inside_admin || $ncCore->admin_mode) {
        return $result;
    }

    if (!$displayType) {
        $inputDisplayType = $ncCore->input->fetch_get('lsDisplayType');
        $subdivisionDisplayType = $ncCore->get_display_type();

        $displayType = $inputDisplayType ? $inputDisplayType : $subdivisionDisplayType;
    }


    if ($displayType != 'traditional') {
        $data['displayType'] = $displayType;
        $data['animationSpeed'] = $animationSpeed;

        if ($subdivisionId !== null) {
            $data['subdivisionId'] = $subdivisionId;
        }

        if ($onSubmit !== null) {
            $data['onSubmit'] = $onSubmit;
        }

        $data = htmlentities(json_encode($data));

        return "data-nc-ls-display-form='{$data}'";
    }

    return $result;
}

function nc_ls_display_container($subdivisionId = null, $onReadyScroll = false) {
    $data = htmlentities(json_encode(array(
        'subdivisionId' => $subdivisionId,
        'onReadyScroll' => $onReadyScroll,
    )));

    return "data-nc-ls-display-container='{$data}'";
}

function nc_ls_display_pointer($subdivisionId = null, $onReadyScroll = false) {
    $data = htmlentities(json_encode(array(
        'subdivisionId' => $subdivisionId,
        'onReadyScroll' => $onReadyScroll,
    )));

    return "data-nc-ls-display-pointer='{$data}'";
}

function nc_include_quickbar_updates() {
    require_once(nc_Core::get_object()->get_variable("INCLUDE_FOLDER") . "quickbar.inc.php");
    $quickbar = nc_quickbar_in_template_header('', false, true);
    $quickbar['view_link'] = html_entity_decode($quickbar['view_link']);
    $quickbar['edit_link'] = html_entity_decode($quickbar['edit_link']);
    $quickbar = json_encode($quickbar);
    return "<script type='text/javascript'>
        parent.nc_ls_quickbar = {$quickbar}
    </script>";
}

if (!function_exists('iconv_deep')) {

    /**
     * Преобразование элементов массива или объекта в требуемую кодировку
     *
     * @param string $in_charset Кодировка входной строки.
     * @param string $out_charset Требуемая на выходе кодировка.
     * @param mixed $obj Массив или объект, который необходимо преобразовать.
     * @return string              Возвращает преобразованную строку или FALSE в случае возникновения ошибки.
     */
    function iconv_deep($in_charset, $out_charset, $var) {
        if (is_array($var)) {
            $new = array();
            foreach ($var as $k => $v) {
                $new[iconv_deep($in_charset, $out_charset, $k)] = iconv_deep($in_charset, $out_charset, $v);
            }
            $var = $new;
        } elseif (is_object($var)) {
            $vars = get_object_vars($var);
            foreach ($vars as $m => $v) {
                $var->$m = iconv_deep($in_charset, $out_charset, $v);
            }
        } elseif (is_string($var)) {
            $var = iconv($in_charset, $out_charset, $var);
        }

        return $var;
    }

}


//--------------------------------------------------------------------------

if (!function_exists('json_safe_encode')) {

    /**
     * Возвращает JSON-представление данных (безопасный режим)
     *
     * @param mixed $obj Массив или объект, который необходимо преобразовать.
     * @return string      Возвращает JSON закодированную строку (string) в случае успеха или FALSE в случае возникновения ошибки.
     */
    function json_safe_encode($obj) {
        $nc_core = nc_Core::get_object();
        if ($nc_core->NC_UNICODE) {
            return json_encode($obj, 256); // JSON_UNESCAPED_UNICODE=256 (constant is available since PHP 5.4.0)
        }

        return json_encode($nc_core->utf8->array_win2utf($obj));
    }

}

/**
 * json_last_error_msg() для PHP < 5.5
 */
if (!function_exists('json_last_error_msg')) {
    function json_last_error_msg() {
        static $ERRORS = array(
            JSON_ERROR_NONE => 'No error',
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX => 'Syntax error',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        );

        $error = json_last_error();
        return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
    }
}

//-------------------------------------------------------------------------

function nc_array_except($array, $keys) {
    foreach ($keys as $key) {
        if (isset($array[$key])) {
            unset($array[$key]);
        }
    }
    return $array;
}

//-------------------------------------------------------------------------

function nc_array_only($array, $keys) {
    $new_array = array();
    foreach ($keys as $key) {
        if (isset($array[$key])) {
            $new_array[$key] = $array[$key];
        }
    }
    return $new_array;
}

//-------------------------------------------------------------------------

/**
 * Returns file form for
 * email attachments
 *
 * @param string $type
 * @return string
 */
function nc_mail_attachment_form($type) {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $type_escaped = $db->escape($type);

    $html = NETCAT_MAIL_ATTACHMENT_FORM_ATTACHMENTS . "<br>";

    $sql = "SELECT * FROM `Mail_Attachment` WHERE `Type` = '{$type_escaped}' ORDER BY `Mail_Attachment_ID` ASC";
    $files = (array)$db->get_results($sql, ARRAY_A);
    foreach ($files as $file) {
        $html .= "<div>
    <a href='{$file['Path']}' target='_blank'>{$file['Filename']}</a> <input type='checkbox' name='mail_attachment_{$type}_delete[]' value='{$file['Mail_Attachment_ID']}' id='mail_attachment_{$type}_delete_{$file['Mail_Attachment_ID']}'/> <label for='mail_attachment_{$type}_delete_{$file['Mail_Attachment_ID']}'>" . NETCAT_MAIL_ATTACHMENT_FORM_DELETE . "</label>
</div>";
    }

    $html .= "<div> " . NETCAT_MAIL_ATTACHMENT_FORM_FILENAME .
        " <input type='text' name='mail_attachment_{$type}_file_name[]'/> <input type='file' name='mail_attachment_{$type}_file[]'/>
</div>
<a href='#' onclick='\$nc(this).prev(\"DIV\").clone().find(\"INPUT\").val(\"\").closest(\"DIV\").insertBefore(this); return false;'>" . NETCAT_MAIL_ATTACHMENT_FORM_ADD . "</a>";

    return $html;
}

/**
 * Saves mail attachment form
 *
 * @param string $type
 * @return bool
 */
function nc_mail_attachment_form_save($type, $from_type = '') {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    if (!$from_type) {
        $from_type = $type;
    }

    $type_escaped = $db->escape($type);

    $delete = (array)$nc_core->input->fetch_post('mail_attachment_' . $from_type . '_delete');

    foreach ($delete as $item) {
        $item = (int)$item;
        $sql = "SELECT `Path` FROM `Mail_Attachment` WHERE `Type` = '{$type_escaped}' AND `Mail_Attachment_ID` = {$item}";
        $path = $db->get_var($sql);
        if ($path) {
            @unlink($path);
            $sql = "DELETE FROM `Mail_Attachment` WHERE `Type` = '{$type_escaped}' AND `Mail_Attachment_ID` = {$item}";
            $db->query($sql);
        }
    }

    if (isset($_FILES['mail_attachment_' . $from_type . '_file'])) {
        $file_names = $nc_core->input->fetch_post('mail_attachment_' . $from_type . '_file_name');
        foreach ($_FILES['mail_attachment_' . $from_type . '_file']['tmp_name'] as $index => $tmp_name) {
            if ($file_names[$index]) {
                $http_path = nc_standardize_path_to_folder($nc_core->HTTP_FILES_PATH . "/mail_attachment/{$type}/");
                $path = nc_standardize_path_to_folder($nc_core->DOCUMENT_ROOT . '/' . $nc_core->SUB_FOLDER . '/' . $http_path);
                $file_name = $file_names[$index];
                $name_parts = explode('.', $_FILES['mail_attachment_' . $from_type . '_file']['name'][$index]);
                $file_ext = $name_parts[count($name_parts) - 1];

                $fs_file_name = nc_get_filename_for_original_fs($file_name . '.' . $file_ext, $path);

                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }

                if (move_uploaded_file($tmp_name, $path . $fs_file_name)) {
                    $type = $db->escape($type);
                    $file_name = $db->escape($file_name);
                    $path = $db->escape($http_path . $fs_file_name);
                    $extension = $db->escape($file_ext);
                    $content_type = $db->escape($_FILES['mail_attachment_' . $from_type . '_file']['type'][$index]);

                    $sql = "INSERT INTO `Mail_Attachment` (`Type`, `Filename`, `Path`, `Extension`, `Content_Type`) " .
                        "VALUES ('{$type}', '{$file_name}', '{$path}', '{$extension}', '{$content_type}')";
                    $db->query($sql);
                }
            }
        }
    }

    return true;
}

/**
 * Attaches files to mail
 *
 * @param CMIMEMail $mailer
 * @param string $body
 * @param string|array $types
 * @return string
 */
function nc_mail_attachment_attach(CMIMEMail $mailer, $body, $types) {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $types_escaped = array();
    $attachments = array();

    if (!is_array($types)) {
        $types = array($types);
    }

    foreach ($types as $type) {
        $types_escaped[] = '\'' . $db->escape($type) . '\'';
    }

    if ($types_escaped) {
        $sql = 'SELECT `Filename`, `Path`, `Content_Type`, `Extension`
                  FROM `Mail_Attachment`
                 WHERE `Type` IN (' . implode(',', $types_escaped) . ')';
        $attachments = (array)$db->get_results($sql, ARRAY_A);
    }

    while (preg_match('/\%FILE_([-_a-z0-9]+)/i', $body, $match)) {
        $filename = $match[1];

        $file = false;

        foreach ($attachments as $index => $attachment) {
            if (strtolower($attachment['Filename']) === strtolower($filename)) {
                $file = $attachment;
                unset($attachments[$index]);
                break;
            }
        }

        $replace = '';
        if ($file) {
            $absolute_path = $nc_core->DOCUMENT_ROOT . $file['Path'];
            $replace = 'cid:' . $mailer->attachFileEmbed($absolute_path, $filename . '.' . $file['Extension'], $file['Content_Type']);
        }

        $body = preg_replace('/\%FILE_' . preg_quote($filename, '/') . '/', $replace, $body);
    }

    foreach ($attachments as $attachment) {
        $absolute_path = $nc_core->DOCUMENT_ROOT . $attachment['Path'];
        $mailer->attachFileEmbed($absolute_path, $attachment['Filename'] . '.' . $attachment['Extension'], $attachment['Content_Type']);
    }

    return $body;
}

/**
 * Возвращает имя записи в списке (классификаторе) $classifier с ID = $id.
 *
 * @param string $classifier
 * @param int $id
 * @param bool $should_cache
 * @return null|string
 */
function nc_get_list_item_name($classifier, $id, $should_cache = true) {
    static $local_cache = array();
    $cache_key = "$classifier:$id";

    $id = (int)$id;
    if (!$id || !preg_match("/^\w+$/", $classifier)) {
        return '';
    }

    if (!array_key_exists($cache_key, $local_cache)) {
        $name = nc_db()->get_var("SELECT `{$classifier}_Name`
                                    FROM `Classificator_{$classifier}`
                                   WHERE `{$classifier}_ID` = $id");
        if (!$should_cache) {
            return $name;
        }
        $local_cache[$cache_key] = $name;
    }
    return $local_cache[$cache_key];
}

/*
 * Recursively moves directory
 *
 * @param string $source
 * @param string $destination
 */
function nc_move_directory($source, $destination) {
    $dir = opendir($source);
    if ($dir) {
        @mkdir($destination);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($source . '/' . $file)) {
                    nc_move_directory($source . '/' . $file, $destination . '/' . $file);
                } else {
                    if (copy($source . '/' . $file, $destination . '/' . $file)) {
                        @unlink($source . '/' . $file);
                    }
                }
            }
        }
        closedir($dir);
        @rmdir($source);
    }
}

/**
 * Возвращает совпадающие поля
 * в перечисленных классах
 *
 * @param array $classes
 * @param array $exclude_fields
 * @return array
 */
function nc_get_same_fields($classes, $exclude_fields = array()) {
    $db = nc_core('db');

    $same_fields = array();
    $analized_fields = array();

    foreach ($exclude_fields as $index => $value) {
        $exclude_fields[$index] = "'" . $db->escape($value) . "'";
    }

    $exclude_fields_condition = implode(',', $exclude_fields);

    foreach ($classes as $class_id) {
        $class_id = (int)$class_id;
        if ($analized_fields && in_array($class_id, $analized_fields)) {
            continue;
        }

        $sql = "SELECT `Field_Name`, `Description`, `TypeOfData_ID` FROM `Field` " .
            "WHERE `Class_ID` = {$class_id}";
        $sql .= ($exclude_fields_condition ? " AND `Field_Name` NOT IN ({$exclude_fields_condition})" : "");
        $sql .= " AND `TypeOfData_ID` NOT IN (" . NC_FIELDTYPE_FILE . ", " . NC_FIELDTYPE_MULTIFILE . ")";
        $sql .= " ORDER BY `Priority` ASC";

        $fields = (array)$db->get_results($sql, ARRAY_A);

        foreach ($fields as $index => $field) {
            $fields[$index] = serialize($field);
        }

        if (!$analized_fields) {
            $same_fields = $fields;
        } else {
            $same_fields = array_intersect($same_fields, $fields);
        }

        $analized_fields[] = $class_id;
    }

    foreach ($same_fields as $index => $field) {
        $same_fields[$index] = unserialize($field);
    }

    return $same_fields;
}

/**
 * Возвращает связанное поле
 *
 * @param string $field_name
 * @param int $class_id
 * @param int $system_table_id
 * @return string
 */
function nc_field_extension($field_name, $class_id = null, $system_table_id = null) {
    $nc_core = nc_core::get_object();
    $component = $class_id ?: $nc_core->get_system_table_name_by_id($system_table_id);
    $result = $nc_core->get_component($component)->get_field($field_name, 'extension');
    return is_null($result) ? '' : $result;
}


/**
 * Возвращает элемент $key массива $array, или $default, если такого
 * элемента в массиве нет или он равен null.
 *
 * @param $array
 * @param $key
 * @param $default
 * @return mixed
 */
function nc_array_value($array, $key, $default = null) {
    if (isset($array[$key])) {
        return $array[$key];
    }
    return $default;
}

/**
 * Добавляет «хвост» к пути для предотвращения использования закэшированной в браузере
 * старой версии файла.
 *
 * @param string $file имя файла без query-части
 * @return string  путь с добавлением "?номер_сборки"
 * @internal не является частью публичного API
 */
function nc_add_revision_to_url($file) {
    static $cache_limiter = null;
    if (!$cache_limiter) {
        $cache_limiter = nc_core::get_object()->get_settings('LastPatchBuildNumber') ?: mt_rand(100000, 999999);
    }

    return $file . '?' . $cache_limiter;
}

/**
 * Minifies CSS and
 * returns URL path to
 * minified file
 *
 * @param array $files
 * @param string $type
 * @param bool $debug
 * @return array
 */
function nc_minify_file($files, $type, $debug = false) {
    $nc_core = nc_core();

    if ($debug || !$nc_core->get_settings('MinifyStaticFiles')) {
        return array_map('nc_add_revision_to_url', $files);
    }

    $time_limit = ini_get("max_execution_time");
    @set_time_limit(0);
    switch ($type) {
        case 'css':
            $class_name = 'CSS';
            $extension = 'css';
            break;
        case 'js';
            $class_name = 'JS';
            $extension = 'js';
            break;
        default:
            return array_map('nc_add_revision_to_url', $files);
            break;
    }

    require_once($nc_core->INCLUDE_FOLDER . "lib/simple_minify/{$class_name}.php");

    $root = $nc_core->DOCUMENT_ROOT;
    $minified_url_path = $nc_core->ADMIN_PATH . 'js/min';
    $minified_path = $root . $minified_url_path;


    if (!file_exists($minified_path)) {
        @mkdir($minified_path, $nc_core->DIRCHMOD);
    }

    // Удаление файлов в папке с минифицированными файлами: только один раз
    // за выполнение скрипта, в среднем каждые 50 запусков
    // (приводит к удалению файлов, к которым не обращались более 7 дней)
    static $already_called = array();
    if (!isset($already_called[$extension]) && mt_rand(0, 49) === 0) {
        $old_files_time = time() - 604800; // 7 дней
        $created_files = (array)glob("$minified_path/min_*.$extension");
        foreach ($created_files as $file) {
            $file_access_time = fileatime($file); // что будет, если файловая система не поддерживает?
            if ($file_access_time && $file_access_time < $old_files_time) {
                @unlink($file);
            }
        }
    }
    $already_called[$extension] = true;


    $minify = new $class_name();

    $checksum = array();

    foreach ($files as $file) {
        $checksum[] = md5_file($root . $file);
        $minify->add($root . $file);
    }

    $checksum = md5(implode(':', $checksum));
    $output_filename = $minified_path . '/min_' . $checksum . '.' . $extension;

    try {
        if (!file_exists($output_filename)) {
            $minify->minify($output_filename);
        }

        $output_files = array($minified_url_path . '/min_' . $checksum . '.' . $extension);
    } catch (Exception $e) {
        $output_files = array_map('nc_add_revision_to_url', $files);
    }

    @set_time_limit($time_limit);

    return $output_files;
}

function nc_minify_all_js_files($js_dir = null) {
    set_time_limit(0);
    $nc_core = nc_core();
    $ignore_list = array(
        $nc_core->ROOT_FOLDER . 'admin/js/min',
        $nc_core->ROOT_FOLDER . 'admin/js/codemirror',
        $nc_core->ROOT_FOLDER . 'admin/js/datepicker',
        $nc_core->ROOT_FOLDER . 'admin/js/flot',
    );

    require_once($nc_core->INCLUDE_FOLDER . "lib/JSMin/JSMin.php");

    $js_dir = $js_dir ? $js_dir : $nc_core->ROOT_FOLDER . 'admin/js';

    $files = array_diff(scandir($js_dir), array('.', '..'));
    foreach ($files as $file) {
        if (in_array("{$js_dir}/{$file}", $ignore_list)) {
            continue;
        }

        if (is_dir("{$js_dir}/{$file}")) {
            nc_minify_all_js_files("{$js_dir}/{$file}");
        } else if (preg_match('/\.js$/i', $file) && !preg_match('/\.min\.js$/i', $file)) {
            $file_content = file_get_contents("{$js_dir}/{$file}");
            $file_name = preg_replace('/\.js$/', '', $file);
            $file_content = JSMin::minify($file_content);
            file_put_contents("{$js_dir}/{$file_name}.min.js", $file_content);
        }
    }

    return;
}

/**
 * Removes directory
 *
 * @param string $dir
 * @return bool
 */
function nc_delete_dir($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? nc_delete_dir("$dir/$file") : unlink("$dir/$file");
    }

    return rmdir($dir);
}

/**
 * Подготавливает дату из datepicker для использования в SQL запросах
 *
 * @param string $date_raw
 * @return string type
 */
function nc_prepare_data($date_raw) {
    $date = '';
    $date_raw = explode('.', $date_raw);
    if (count($date_raw) == 3) {
        $date_raw = "{$date_raw[2]}-{$date_raw[1]}-{$date_raw[0]}";
        $date_raw = strtotime($date_raw);
        if ($date_raw) {
            $date = date('Y-m-d', $date_raw);
        }
    }
    return $date;
}


/**
 * Инициализирует объект произвольного поля мультифайла
 *
 * @param int $field_id - номер поля
 * @param int $message - номер объекта/записи
 * @param array $template - шаблон отображения
 * @return \nc_multifield|null
 */
function nc_load_multifield($field_id, $message, $template = null) {
    $field_id = (int)$field_id;
    $message = (int)$message;
    $db = nc_core::get_object()->db;

    $field = $db->get_row(
        "SELECT `Field_Name`, `Description`, `Format`, `Class_ID` FROM `Field` WHERE `Field_ID` = $field_id",
        ARRAY_A
    );

    if (empty($field)) {
        return null;
    }

    $query = "SELECT `Name`, `Size`, `Path`, `Field_ID`, `Message_ID`, `Preview`, `ID`, `Priority`
                FROM `Multifield`
               WHERE `Field_ID` = $field_id 
                 AND `Message_ID` = $message
               ORDER BY `Priority`";
    $field_data = $db->get_results($query, ARRAY_A);

    $field_object = new nc_multifield($field['Field_Name'], $field['Description'], $field['Format'], $field_id, $field['Class_ID']);
    if (!empty($field_data)) {
        $field_object->set_data($field_data);
    }

    if ($template) {
        $field_object->set_template($template);
    }
    return $field_object;
}

/**
 * Возвращает текст $string без тэгов, обрезанный по границе слов, предшествующей
 * указанной длине $max_length. Если возвращаемая строка является подстрокой
 * исходной, добавляется $suffix.
 *
 * @param string $string
 * @param int $max_length
 * @param string $suffix
 * @return string
 */
function nc_truncate_plain_string($string, $max_length, $suffix = "...") {
    $result = strip_tags($string);

    if (nc_strlen($result) <= $max_length) {
        return $result;
    }

    nc_preg_match("/^.{" . ($max_length + 1) . "}/s", $result, $matches);
    $result = $matches[0];
    $result = nc_preg_replace('/\S+$/s', '', $result);
    $result = nc_preg_replace('/[\s~`!@#$%^&*()_=+{}\[\]\\|;:"\'<>,.\/?-]+$/s', '', $result);

    return $result . $suffix;
}

/**
 * Переводит строку CamelCase в dash-case
 */
function nc_camelcase_to_dashcase($id) {
    $id = preg_replace_callback('/[A-Z]/', 'nc_camelcase_to_dashcase_callback', $id);
    $id = preg_replace('/[_\/-]+/', '-', $id);
    $id = preg_replace('/^[-]+/', '', $id);
    return $id;
}

/**
 *
 */
function nc_camelcase_to_dashcase_callback($matches) {
    return '-' . strtolower($matches[0]);
}

/**
 * Inserts demo mode message
 *
 * @param string $buffer
 * @return string
 */
function nc_insert_demo_mode_message($buffer) {
    global $perm, $File_Mode;

    $current_catalogue = nc_core('catalogue')->get_current();
    $catalogue_id = $current_catalogue['Catalogue_ID'];
    $message_seen = isset($_SESSION['nc_demo_site_message_' . $catalogue_id]) && $_SESSION['nc_demo_site_message_' . $catalogue_id];

    if ($current_catalogue['ncMode'] === 'demo' && !$message_seen) {
        $_SESSION['nc_demo_site_message_' . $catalogue_id] = true;
        $message = $perm instanceof Permission && $perm->isInsideAdmin() ?
            sprintf(DEMO_MODE_FRONT_INDEX_MESSAGE_ADMIN, nc_core('ADMIN_PATH') . "catalogue/index.php?action=system&phase=2&CatalogueID=" . $catalogue_id) :
            DEMO_MODE_FRONT_INDEX_MESSAGE_GUEST;
        $html = "<style type='text/css'>#nc-demo-mode-overlay { opacity: 0.5; position: fixed; left: 0; top: 0; right: 0; bottom: 0; background-color: #9C9294; z-index: 1001; }    #nc-demo-mode-modal { z-index: 1002; background: #fff; position: fixed; left: 50%; top: 50%; width: 400px; margin-left: -200px; margin-top: -100px; }    #nc-demo-mode-modal-header { line-height: 20px; font-family: Helvetica Neue, Helvetica, Arial, sans-serif; font-size: 14px; color: #333; text-align: left; font-weight: normal; font-style: normal; padding: 16px; }    #nc-demo-mode-modal-footer { text-align: right; background-color: #EEE; padding: 16px; }    #nc-demo-mode-modal-footer BUTTON { display: inline-block; text-align: center; padding: 7px 14px; margin: 0; background: #1a87c2; color: #FFF; font-size: 14px; cursor: pointer; line-height: 20px; height: 38px; border: none; text-decoration: none; }    #nc-demo-mode-modal-footer BUTTON:hover { opacity: 0.8; }</style><div id='nc-demo-mode-overlay'></div><div id='nc-demo-mode-modal'>    <div id='nc-demo-mode-modal-header'>{$message}</div>    <div id='nc-demo-mode-modal-footer'>        <button type='button' onclick='document.getElementById(\\\"nc-demo-mode-overlay\\\").remove(); document.getElementById(\\\"nc-demo-mode-modal\\\").remove();'>" . DEMO_MODE_FRONT_INDEX_MESSAGE_CLOSE . "</button>    </div></div>";
        $quote = $File_Mode ? '"' : '\\"';
        return nc_insert_in_head($buffer, '<script>document.write(' . $quote . str_replace("\r\n", "", $html) . $quote . ');</script>', true);
    }

    return $buffer;
}

/**
 * Добавляет параметры http-прокси в массив настроек для создания контекста
 *
 * @param array $stream_context_options
 * @return array
 */
function nc_set_stream_proxy_params(array $stream_context_options = array()) {
    $nc_core = nc_core::get_object();

    if ($nc_core->get_settings('HttpProxyEnabled')) {
        $proxy_host = trim($nc_core->get_settings('HttpProxyHost'));
        if (!isset($stream_context_options['http'])) {
            $stream_context_options['http'] = array();
        }

        $proxy_port = $nc_core->get_settings('HttpProxyPort');
        $stream_context_options['http']['proxy'] = "tcp://$proxy_host:$proxy_port";
        $stream_context_options['http']['request_fulluri'] = true;

        $proxy_user = trim($nc_core->get_settings('HttpProxyUser'));
        $proxy_pass = trim($nc_core->get_settings('HttpProxyPassword'));

        if ($proxy_user) {
            $stream_context_options['http']['header'] =
                "Proxy-Authorization: Basic " . base64_encode("$proxy_user:$proxy_pass") . "\r\n" .
                nc_array_value($stream_context_options['http'], 'header');
        }

    }

    return $stream_context_options;
}

/**
 * Функция проверки ключевого слова объекта на уникальность, в случае совпадения
 * возвращает с уникальным числовым постфиксом "-номер"
 *
 * @param int $message_id
 * @param string $keyword
 * @param int $component_id
 * @param int $subdivision_id
 * @return string|null
 */
function nc_check_keyword_name($message_id, $keyword, $component_id, $subdivision_id) {
    if (!$keyword) {
        return null;
    }

    $component_id = (int)$component_id;
    $message_id = (int)$message_id;
    $subdivision_id = (int)$subdivision_id;

    $db = nc_core::get_object()->db;

    $query_template =
        "(SELECT `Keyword`
           FROM `Message{$component_id}`
          WHERE `Subdivision_ID` = $subdivision_id
            AND `Keyword` #keyword_condition#
            AND `Message_ID` != $message_id)
         UNION DISTINCT
         (SELECT `EnglishName` AS `Keyword`
           FROM `Sub_Class`
          WHERE `Subdivision_ID` = $subdivision_id
            AND `EnglishName` #keyword_condition#)";

    $has_object_with_same_keyword_query = str_replace(
        '#keyword_condition#',
        "= '" . $db->escape($keyword) . "'",
        $query_template
    );
    $has_object_with_same_keyword = $db->get_var($has_object_with_same_keyword_query);

    if ($has_object_with_same_keyword) {
        // если уже заканчивается на "-число" — убираем его
        $keyword_without_postfix = preg_replace('/(-\d+)$/', '', $keyword);

        // выбираем ключевое слово с максимальной цифрой
        $max_existing_keyword_query = str_replace(
            '#keyword_condition#',
            "REGEXP '^" . $db->escape(preg_quote($keyword_without_postfix)) . "-[0-9]+$'",
            $query_template
        );

        $max_existing_keyword_query .= " ORDER BY LENGTH(`Keyword`) DESC, `Keyword` DESC LIMIT 1";
        $max_existing_keyword = $db->get_var($max_existing_keyword_query);
        if ($max_existing_keyword) {
            preg_match('/-(\d+)$/', $max_existing_keyword, $match);
            $keyword = $keyword_without_postfix . "-" . ($match[1] + 1);
        } else {
            $keyword = $keyword_without_postfix . "-1";
        }
    }

    return $keyword;
}

/**
 * Функция проверяет имя HTML-атрибута на соответствие стандарту W3C
 * See https://www.w3.org/TR/html-markup/syntax.html#syntax-attributes
 *
 * @param string $string Имя атрибута
 * @return bool Результат проверки соответствия имя атрибута стандарту W3C
 */
function nc_is_valid_html_attribute_name($string) {
    $nc_core = nc_Core::get_object();
    if ($nc_core->NC_UNICODE) {
        $length = mb_strlen($string, 'UTF-8');
        $is_valid_byte_sequence = $string === nc_replace_invalid_utf8_byte_sequence($string);
        return $is_valid_byte_sequence && !preg_match('/[\x00-\x20\'">=\/\p{Cc}]/', $string) && $length > 0;
    }
    return strlen($string) > 0 && !preg_match('/[\x00-\x20\x7F\'">=\/]/', $string);
}

/**
 * Функция заменяет некорректные UTF-8 символы на символ, указанный пользователем
 * See http://stackoverflow.com/a/13695364/2486051
 *
 * @param string $string Строка для проверки
 * @param mixed $replacement Замещающий символ
 * @return string Обработанная строка
 */
function nc_replace_invalid_utf8_byte_sequence($string, $replacement = 0xFFFD) {
    mb_substitute_character($replacement);
    return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
}

/**
 * Функция преобразует переданный массив в строку из html-атрибутов, согласно стандарту W3C
 * See https://www.w3.org/TR/html-markup/syntax.html#syntax-attributes
 * Атрибуты с именем, несоотвествующим стандарту W3C, будут проигнорированы.
 *
 * @param array $attributes Массив атрибутов
 * @param bool $for_v4 Нужно ли экранировать данные для использования в шаблонах v4
 * @return array Подробности ниже.
 * Ключ result - Строка, состоящая из html-атрибутов
 * Ключ warning - Предупреждение о нарушении конвенции именования атрибутов. Только для имеющих доступ в админ. панель.
 */
function nc_make_attribute_string_from_array(array $attributes, $for_v4 = false) {
    $nc_core = nc_Core::get_object();
    $encoding = $nc_core->NC_UNICODE ? 'UTF-8' : 'cp1251';
    $normalized_attributes = array();
    $message_for_admin = '';
    $incorrect_attribute_names = array();

    foreach ($attributes as $attribute_name => $attribute_value) {
        $normalized_attribute_name = mb_strtolower($attribute_name, $encoding);
        $normalized_attribute_value = htmlspecialchars($attribute_value, ENT_QUOTES, $encoding);

        if (!nc_is_valid_html_attribute_name($attribute_name)) {
            $incorrect_attribute_names[] = $attribute_name;
            continue;
        }

        $attribute = "{$normalized_attribute_name}='{$normalized_attribute_value}'";
        if ($for_v4) {
            $attribute = addcslashes($attribute, '\\$');
        }
        $normalized_attributes[] = $attribute;
    }

    if ($nc_core->InsideAdminAccess() && $incorrect_attribute_names) {
        /** @todo replace with nc_print_status() */
        $incorrect_attribute_names_string = '"' . join('", "', $incorrect_attribute_names) . '"';
        $incorrect_attribute_names_string = htmlspecialchars($incorrect_attribute_names_string, ENT_QUOTES, $encoding);
        $message_for_admin =
            "<div class='nc-alert nc--yellow'>" .
            "<i class='nc-icon-l nc--status-warning'></i>" .
            sprintf(NETCAT_USER_BREAK_ATTRIBUTE_NAMING_CONVENTION, $incorrect_attribute_names_string) .
            "</div>";
    }

    return array(
        'result' => implode(' ', $normalized_attributes),
        'warning' => $message_for_admin,
    );
}


/**
 * Возвращает уникальный HTML ID для блока, сформированный на основе адреса страницы
 * и переданной строки
 *
 * @param string $additional_data
 * @return string
 */
function nc_make_block_id($additional_data = '') {
    return 'nc-block-' . md5("$_SERVER[REQUEST_URI]\n$additional_data");
}


/**
 * Устанавливает статус ответа.
 *
 * Данная функция существует потому, что стандартная функция http_response_code()
 * (PHP 5.4+) в некоторых случаях не срабатывает (например, когда закрыт буфер
 * в nc_core::output_page_buffer(), проверено в PHP 5.4 — 7.1),
 * а способ установки заголовка вызовом функции, подобным
 * header('X-PHP-Response-Code: 304', true, 304) не работает на некоторых серверах
 * (было обнаружено на связке nginx/1.2.1 + Apache/2.2.22 + PHP 5.4.36 CGI, HTTP/1.0)
 *
 * @param int $code
 */
function nc_set_http_response_code($code) {
    $code_with_description = $code . ' ' . nc_get_http_response_description($code);
    header(nc_array_value($_SERVER, 'SERVER_PROTOCOL', 'HTTP/1.0') . ' ' . $code_with_description, true, $code);
}

/** Проверка редиректа на принадлежность пути сайту, под управлением системы
 * если путь не соответствует - редирект на домашнюю страницу
 * @return void
 */
function nc_check_redirect($redirect, $fallback_url = '/') {
    $nc_core = nc_Core::get_object();

    if ($nc_core->security->url_matches_local_site($redirect)) {
        header('Location: ' . $redirect);
    } else {
        header('Location: ' . $fallback_url); //$fallback_url = '/'
    }
}

/**
 * @param int $code
 * @return string
 * @internal
 */
function nc_get_http_response_description($code) {
    switch ($code) {
        case 100:
            return 'Continue';
        case 101:
            return 'Switching Protocols';
        case 200:
            return 'OK';
        case 201:
            return 'Created';
        case 202:
            return 'Accepted';
        case 203:
            return 'Non-Authoritative Information';
        case 204:
            return 'No Content';
        case 205:
            return 'Reset Content';
        case 206:
            return 'Partial Content';
        case 300:
            return 'Multiple Choices';
        case 301:
            return 'Moved Permanently';
        case 302:
            return 'Moved Temporarily';
        case 303:
            return 'See Other';
        case 304:
            return 'Not Modified';
        case 305:
            return 'Use Proxy';
        case 400:
            return 'Bad Request';
        case 401:
            return 'Unauthorized';
        case 402:
            return 'Payment Required';
        case 403:
            return 'Forbidden';
        case 404:
            return 'Not Found';
        case 405:
            return 'Method Not Allowed';
        case 406:
            return 'Not Acceptable';
        case 407:
            return 'Proxy Authentication Required';
        case 408:
            return 'Request Time-out';
        case 409:
            return 'Conflict';
        case 410:
            return 'Gone';
        case 411:
            return 'Length Required';
        case 412:
            return 'Precondition Failed';
        case 413:
            return 'Request Entity Too Large';
        case 414:
            return 'Request-URI Too Large';
        case 415:
            return 'Unsupported Media Type';
        case 500:
            return 'Internal Server Error';
        case 501:
            return 'Not Implemented';
        case 502:
            return 'Bad Gateway';
        case 503:
            return 'Service Unavailable';
        case 504:
            return 'Gateway Time-out';
        case 505:
            return 'HTTP Version not supported';
        default:
            return '';
    }
}

/**
 * Отсылает заголовок "404 Not Found"; показывает раздел, указанный как
 * «Страница не найдена» в настройках сайта; завершает работу скрипта.
 */
function nc_send_page_404() {
    $nc_core = nc_core::get_object();

    if ($nc_core->inside_admin) {
        require_once $nc_core->ADMIN_FOLDER . 'template.inc.php';
        BeginHtml(CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND_PAGE);
        echo CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND_PAGE;
        EndHtml();
        die;
    }

    // set [global] variables
    $action = 'index';
    $sub = $nc_core->catalogue->get_current('E404_Sub_ID');
    $use_multi_sub_class = $nc_core->subdivision->get_by_id($sub, 'UseMultiSubClass');
    $nc_core->subdivision->set_current_by_id($sub);
    $parent_sub_tree = $nc_core->subdivision->get_parent_tree($sub);
    $sub_level_count = $nc_core->subdivision->get_level_count($sub);
    $passed_thru_404 = true;

    $cc_in_sub = $nc_core->sub_class->get_by_subdivision_id($sub);
    $current_cc = null;
    $cc_array = array();
    if (!empty($cc_in_sub)) {
        foreach ($cc_in_sub as $row) {
            $cc_array[] = $row['Sub_Class_ID'];
        }
    }

    // определение сс
    if (isset($cc_array[0]) && !$use_multi_sub_class) {
        $_db_cc = $cc = $cc_array[0];
        $current_cc = $nc_core->sub_class->get_current($cc);
        $cc_keyword = $cc_in_sub[0]['EnglishName'];
        $classID = $cc_in_sub[0]['Class_ID'];

        if (empty($GLOBALS['isNaked'])) {
            $isNaked = $cc_in_sub[0]['isNaked'];
        }
    } else {
        $_db_cc = $cc = null;
        $current_cc = null;
        $cc_keyword = null;
        $classID = null;
    }

    $nc_core->sub_class->set_current_by_id($cc);

    unset($GLOBALS['template'], $GLOBALS['date']);

    // export to globals
    foreach (get_defined_vars() as $k => $v) {
        $GLOBALS[$k] = $v;
    }

    // import globals
    foreach ($GLOBALS as $k => $v) {
        if ($k === 'GLOBALS' || preg_match('/^_[A-Z]+$/', $k)) {
            continue;
        }
        ${$k} = &$GLOBALS[$k];
    }

    // clean buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // 404 header
    nc_set_http_response_code(404);

    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
        require $nc_core->ROOT_FOLDER . 'index.php';
    }

    exit;
}

/**
 * Приводит номер телефона к международному формату (E164 — с «+», без пробелов).
 * Если нормализация телефона не удалась, либо номер не является корректным,
 * возвращает null.
 *
 * @param $phone_number
 * @return null|string
 */
function nc_normalize_phone_number($phone_number) {
    $phone_util = \libphonenumber\PhoneNumberUtil::getInstance();
    try {
        // Настройка системы PhoneNormalizationRegion не имеет UI, по умолчанию отсутствует
        $region = nc_core::get_object()->get_settings('PhoneNormalizationRegion') ?: 'RU';
        $number_proto = $phone_util->parse($phone_number, $region);
        if ($phone_util->isValidNumber($number_proto)) {
            return $phone_util->format($number_proto, \libphonenumber\PhoneNumberFormat::E164);
        }
    } catch (\libphonenumber\NumberParseException $ignored) {
    }

    return null;
}

/**
 * Возвращает код страны по ISO 3166-1
 *
 * @param string $phone_number
 * @param string $region_code
 *
 * @return string|null
 */
function nc_get_country_code_by_number($phone_number, $region_code = null) {
    $phone_util = \libphonenumber\PhoneNumberUtil::getInstance();

    try {
        $region = $region_code ?: nc_core::get_object()->get_settings("PhoneNormalizationRegion") ?: "RU";
        $number_proto = $phone_util->parse($phone_number, $region);

        if ($phone_util->isValidNumber($number_proto)) {
            return $phone_util->getRegionCodeForNumber($number_proto);
        }
    } catch (\libphonenumber\NumberParseException $ignored) {
    }

    return null;
}

/**
 * Вставляет изображение-значок из поля объекта: SVG-файлы возвращаются в виде тэга SVG,
 * остальные картинки — как <img>. К тэгу добавляется атрибут class="tpl-icon".
 * Если в SVG-картинки отсутствуют атрибуты fill, то также добавляется
 * class="tpl-state-default-color".
 *
 * У значений файловых полей есть метод as_icon(), который делает то же самое, но не только для компонентов.
 * Предпочтительнее использовать метод as_icon().
 *
 * @param string|int $component_id
 * @param int $object_id
 * @param string|int $field_name
 * @param array $tag_attributes дополнительные атрибуты для <img> или <svg>
 * @return string пустая строка (если нет изображения), либо HTML-строка с тэгом <img> или <svg>
 */
function nc_embed_icon($component_id, $object_id, $field_name, array $tag_attributes = array()) {
    $file_info = nc_core::get_object()->file_info->get_file_info($component_id, $object_id, $field_name, false, false, true);
    if (empty($file_info['url'])) {
        return '';
    }
    return (string)$file_info['download_path']->as_icon();
}

/**
 * Возвращает <svg> из библиотеки иконок в /netcat_template/icon/
 *
 * @param string $icon_library название библиотеки иконок, например: 'font_awesome'
 * @param string $icon название иконки (без ".svg"), например: 'anchor'
 * @return string
 */
function nc_get_icon_from_library($icon_library, $icon, array $attributes = array()) {
    if (!preg_match('/^[\w-]+$/', "$icon_library$icon")) { // sic
        // в $icon_library, $icon должны быть только буквы, цифры, '_' или '-'
        trigger_error("Wrong icon library ('$icon_library') or icon name ('$icon')", E_USER_WARNING);
        return '';
    }

    $nc_core = nc_core::get_object();
    $icon_library_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'icon/' . $icon_library;

    return nc_get_svg_as_icon("$icon_library_path/$icon.svg", $attributes);
}

/**
 * Возвращает <svg> с добавлением атрибутов tpl-icon, tpl-state-default-color
 *
 * @param string $path путь до иконки
 * @param array $tag_attributes дополнительные атрибуты для <svg>
 * @param bool $sanitize анализировать SVG на наличие опасных тэгов
 * @return bool|mixed|string
 * @internal не является частью API. Не используйте в шаблонах!
 *           Используйте метод as_icon() значений файловых полей.
 */
function nc_get_svg_as_icon($path, array $tag_attributes = array(), $sanitize = false) {
    if (!isset($tag_attributes['class'])) {
        $tag_attributes['class'] = 'tpl-icon';
    } else if (!preg_match('/\btpl-icon\b/', $tag_attributes['class'])) {
        $tag_attributes['class'] .= ' tpl-icon';
    }

    $svg = nc_get_svg_for_embedding($path, $sanitize);

    if (!preg_match('/\bfill="[^"]+"/', $svg)) {
        $tag_attributes['class'] .= ' tpl-state-default-color';
    }

    $attributes = nc_make_attribute_string_from_array($tag_attributes);
    $svg = str_ireplace('<svg', '<svg ' . $attributes['result'], $svg);

    return $svg;
}

/**
 * Возвращает <svg> для вставки в HTML
 *
 * @param string $path
 * @param bool $sanitize
 * @return string
 * @internal не является частью API!
 *      Но в какой-то момент была использована в шаблонах, поэтому поведение не должно меняться.
 *      Используйте вместо этой функции: nc_get_icon_from_library(), nc_embed_icon()
 *      Используйте метод as_icon() значений файловых полей.
 */
function nc_get_svg_for_embedding($path, $sanitize = false) {
    if (file_exists($path)) {
        $svg = file_get_contents($path);
    }
    if (empty($svg)) {
        return '';
    }

    if ($sanitize) {
        // очистка SVG от потенциально опасных тэгов (для файлов, загруженных пользователями)
        $sanitizer = new \enshrined\svgSanitize\Sanitizer();
        $sanitizer->minify(true);
        $sanitizer->removeXMLTag(true);
        $svg = $sanitizer->sanitize($svg);
    } else {
        // только удаление BOM и <?xml
        $svg = str_replace("\xEF\xBB\xBF", '', $svg); // убрать UTF-8 BOM
        $svg = preg_replace('/<\?.+?\?>/s', '', $svg); // убрать <?xml
        $svg = trim($svg);
    }

    return $svg;
}

/**
 * Очищает SVG-файл от потенциально опасных тэгов (для файлов, загруженных пользователями)
 *
 * @param string $path
 * @return bool
 */
function nc_sanitize_svg_file($path) {
    if (!is_writable($path)) {
        trigger_error("File $path is not writable", E_USER_WARNING);
        return false;
    }
    $sanitizer = new \enshrined\svgSanitize\Sanitizer();
    $svg = $sanitizer->sanitize(file_get_contents($path));

    if ($svg === false) { // not a valid svg; nothing to clean
        return false;
    }

    return file_put_contents($path, $svg) !== false;
}

/**
 * Выполняет код макета (хедера и футера) с использованием глобальных переменных;
 * результат по ссылке записывается в переданные аргументы.
 *
 * Не является частью публичного API.
 *
 * @param string $__header PHP-код хедера (без 'echo ""' или '?>')
 * @param string $__footer PHP-код футера
 * @param bool|int $__eval_file_mode true для предпросмотра макета v5, false для макетов v4
 */
function nc_evaluate_template(&$__header, &$__footer, $__eval_file_mode) {
    $__header = $__eval_file_mode ? "?>$__header" : "echo \"$__header\";";
    $__footer = $__eval_file_mode ? "?>$__footer" : "echo \"$__footer\";";
    extract($GLOBALS, EXTR_SKIP);

    ob_start();
    eval(nc_check_eval($__header));
    $__header = ob_get_clean();

    ob_start();
    eval(nc_check_eval($__footer));
    $__footer = ob_get_clean();
}

/**
 * Конвертация размера из параметра upload_max_filesize в байты (1K, 1M, 1G)
 *
 * @param string $value значение параметра upload_max_filesize
 * @return int
 */
function nc_size2bytes($value) {
    $value = trim($value);
    if (is_numeric($value)) {
        return (int)$value;
    }

    $letter = strtolower($value[strlen($value) - 1]);
    $number = substr($value, 0, -1);

    switch ($letter) {
        case 'g':
            $number *= 1024;
        case 'm':
            $number *= 1024;
        case 'k':
            $number *= 1024;
    }

    return $number;
}

/**
 * Удаляет ключи с пустыми или null значениями
 *
 * @param array $arr
 *
 * @return array
 */
 function nc_remove_empty_fields_recursively(array $arr) {
    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            $arr[$key] = nc_remove_empty_fields_recursively($value);
        }
    }

    return array_filter($arr, function ($val) {
        return !is_null($val) && $val !== "";
    });
}

/**
 * Проверяет, является массив вложенным
 *
 * @param array $arr
 *
 * @return bool
 */
function nc_is_nested_array(array $arr) {
    $filtered = array_filter($arr, 'is_array');

    return count($filtered) === count($arr);
}

/**
 * @param int $length
 *
 * @return string
 */
function nc_generate_random_string($length = 10) {
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $characters_length = strlen($characters);
    $result = "";

    for ($i = 0; $i < $length; $i++) {
        $result .= $characters[rand(0, $characters_length - 1)];
    }

    return $result;
}
