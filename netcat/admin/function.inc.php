<?php

$NETCAT_FOLDER = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $INCLUDE_FOLDER . 'unicode.inc.php';
require_once $ROOT_FOLDER . 'connect_io.php';

if (!isset($_REQUEST['isNaked'])) {
    $_REQUEST['isNaked'] = $nc_core->input->fetch_post_get('isNaked');
}

if ($nc_core->use_gzip_compression && $nc_core->gzip->check()) {
    ob_start('ob_gzhandler');
    // В некоторых местах используется ob_clean(), при вызове которого почему-то
    // пропадает заголовок Content-Encoding и в итоге сжатый ответ не декодируется...
    // Чтобы этого не было, открываем ещё один буфер:
    ob_start();
}

$nc_core->inside_admin = $inside_admin = true;
$nc_core->admin_mode = $admin_mode = true;

$nc_core->init_admin_mode();

$lang = $nc_core->lang->detect_lang();
require_once($ADMIN_FOLDER . "lang/" . $lang . ".php");
$PHP_AUTH_LANG = $lang;

$nc_core->modules->load_env($nc_core->lang->acronym_from_full($lang));


if ($nc_core->modules->get_by_keyword('auth')) {
    $nc_auth = nc_auth::get_object();
    // доступ только по https
    if ($nc_core->NC_ADMIN_HTTPS && nc_get_scheme() != 'https') {
        header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}
// Admin Interface Initialization
$admin_mode = true;
LoadSettings();

$nc_token_login = $nc_core->input->fetch_get_post('nc_token_login');
$nc_token_signature = $nc_core->input->fetch_get_post('nc_token_signature');
if ($nc_token_login && $nc_token_signature) {
    $nc_core->user->authorize_by_token($nc_token_login, $nc_token_signature, $_SESSION['nc_token_rand']);
}
Authorize(0, null, null, 1);

if (!$AUTH_USER_ID) {
    Refuse();
    exit;
}

// Использовать паттерн POST-REDIRECT-GET после авторизации
if ($nc_core->input->fetch_post('AuthPhase') == 1 && $nc_core->input->fetch_post('AUTH_USER')) {
    header("Location: " . htmlspecialchars_decode($_SERVER['REQUEST_URI']), true, 302);
    exit;
}

# Загрузка файла локализации
if ($lang) {
    $AUTH_LANG = $lang;
    $db->query("UPDATE `User` SET `Language` = '" . $db->escape($lang) . "' WHERE `User_ID` = '" . $AUTH_USER_ID . "'");
}


// установить заголовок с кодировкой, чтобы избежать проблем с
// "автоопределением" в браузерах
header("Content-Type: text/html; charset=" . $nc_core->NC_CHARSET);


// не пускать кого попало в административную часть системы

if (!$perm->isInsideAdmin()) {
    BeginHtml(BEGINHTML_TITLE, BEGINHTML_TITLE, "http://" . $DOC_DOMAIN . "/admin/");
    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');

    $logout_link = $nc_core->ADMIN_PATH . 'unauth.php';

    if (nc_module_check_by_keyword('auth')) {
        $logout_link = nc_module_path('auth') . '?logoff=1&REQUESTED_FROM=/';
    }

    echo "<div><a href='$logout_link'>" . BEGINHTML_LOGOUT . '</a></div>';
    EndHtml();
    exit;
}

//Что показать в карте сайта
$treeMode = $perm->isAccessSiteMap() ? 'sitemap' : ($perm->isAccessDevelopment() ? 'developer' : 'users');

// --- Common functions

function LoadSettings() {
    global $nc_core, $db;
    global $IsInsideAdmin, $system_env;
    global $PROJECT_NAME, $VERSION_ID, $ANY_SYSTEM_MESSAGE, $SYSTEM_ID, $ADMIN_FOLDER;
    global $SPAM_FIELD, $SPAM_MAIL, $SPAM_FROM, $SPAM_FROM_NAME, $PATCH_CHECK_DATE;
    global $SPAM_USE_TRANSPORT, $SPAM_SMTP_HOST, $SPAM_SMTP_PORT, $SPAM_SMTP_AUTH_USE, $SPAM_SMTP_USER, $SPAM_SMTP_PASS, $SPAM_SMTP_ENCRYPTION, $SPAM_SENDMAIL_COMMAND;
    global $LAST_PATCH, $LAST_LOCAL_PATCH, $SYSTEM_NAME, $SYSTEM_COLOR;
    global $HTTP_HOST, $REMOTE_ADDR, $HTTP_USER_AGENT, $HTTP_ROOT_PATH;
    global $EDITOR_TYPE, $INLINE_EDIT_CONFIRMATION, $SUB_FOLDER;
    global $REMIND_SAVE, $PACKET_OPERATIONS, $TEXTAREA_RESIZE, $DISABLE_BLOCK_MARKUP;
    global $AUTOSAVE_USE, $AUTOSAVE_TYPE, $AUTOSAVE_PERIOD, $AUTOSAVE_NO_ACTIVE;
    global $INLINE_IMAGE_CROP_USE, $INLINE_IMAGE_CROP_DIMENSIONS;

    $Array = $nc_core->get_settings();

    // probably system was not installed:
    if (!sizeof($Array)) {
        // probably system was not installed
        if ($this->check_system_install()) {
            // DB error
            print "<p><b>" . NETCAT_ERROR_DB_CONNECT . "</b></p>";
            exit;
        }
    }

    $PROJECT_NAME = $Array['ProjectName'];
    $SYSTEM_ID = $Array['SystemID'];
    $VERSION_ID = $Array['VersionNumber'];
    $SPAM_FIELD = $Array['UserEmailField'];
    $SPAM_MAIL = $Array['UserEmailField'];
    $SPAM_FROM = $Array['SpamFromEmail'];
    $SPAM_FROM_NAME = $Array['SpamFromName'];
    $SPAM_USE_TRANSPORT = nc_array_value($Array, 'SpamUseTransport', 'Mail');
    $SPAM_SMTP_HOST = nc_array_value($Array, 'SpamSmtpHost');
    $SPAM_SMTP_PORT = nc_array_value($Array, 'SpamSmtpPort');
    $SPAM_SMTP_AUTH_USE = nc_array_value($Array, 'SpamSmtpAuthUse');
    $SPAM_SMTP_USER = nc_array_value($Array, 'SpamSmtpUser');
    $SPAM_SMTP_PASS = nc_array_value($Array, 'SpamSmtpPass');
    $SPAM_SMTP_ENCRYPTION = nc_array_value($Array, 'SpamSmtpEncryption');
    $SPAM_SENDMAIL_COMMAND = nc_array_value($Array, 'SpamSendmailCommand');
    $EDITOR_TYPE = $Array['EditorType'];
    $INLINE_EDIT_CONFIRMATION = $Array['InlineEditConfirmation'];
    $REMIND_SAVE = $Array['RemindSave'];
    $PACKET_OPERATIONS = $Array['PacketOperations'];
    $TEXTAREA_RESIZE = $Array['TextareaResize'];
    $DISABLE_BLOCK_MARKUP = $Array['DisableBlockMarkup'];
    $AUTOSAVE_USE = nc_array_value($Array, 'AutosaveUse');
    $AUTOSAVE_TYPE = $Array['AutosaveType'];
    $AUTOSAVE_PERIOD = $Array['AutosavePeriod'];
    $AUTOSAVE_NO_ACTIVE = $Array['AutosaveNoActive'];
    $INLINE_IMAGE_CROP_USE = $Array['InlineImageCropUse'];
    $Array['InlineImageCropDimensions'] = unserialize(nc_array_value($Array, 'InlineImageCropDimensions', ''));
    $INLINE_IMAGE_CROP_DIMENSIONS = $Array['InlineImageCropDimensions'];

    list($SYSTEM_NAME, $SYSTEM_COLOR) = nc_system_name_by_id($SYSTEM_ID);

    $LAST_LOCAL_PATCH = (int)$db->get_var("SELECT `Patch_Name` FROM `Patch` ORDER BY `Patch_Name` DESC LIMIT 1");

    // следующий патч
    $LAST_PATCH = $Array['LastPatch'];
    // время последнего обращения за обновлениями
    $PATCH_CHECK_DATE = $Array['PatchCheck'];
    // спрашиваем раз в неделю
    $PATCH_CHECK_NEEDED = ($PATCH_CHECK_DATE + 2 * 24 * 3600) < time();
    if (!$PATCH_CHECK_DATE || $PATCH_CHECK_NEEDED || $IsInsideAdmin) {
        $an = new nc_AdminNotice();
        $LAST_PATCH = $an->update();
    }

    $ANY_SYSTEM_MESSAGE = $db->get_var("SELECT COUNT(*) FROM `SystemMessage` WHERE `Checked` = 0");

    $system_env = $nc_core->get_settings();
    $system_env['Powered'] = "PHP/" . phpversion();
}

/**
 * Get favorites subdivision
 *
 * @param Return type: object, array_a, array_n
 *
 * @return 2xarray. Keys: id, name, catalogueID, urls, checked. domain, catalogueName
 */
function GetFavorites($returnType = 'ARRAY_A') {
    global $db, $perm;
    $select = "sub.Subdivision_ID, sub.Subdivision_Name, sub.Catalogue_ID, sub.ExternalURL, sub.Hidden_URL, sub.Checked AS SubChecked,
             catalogue.Domain, catalogue.Catalogue_Name, catalogue.Checked AS CatalogueChecked";

    $sql = "SELECT `Catalogue_ID` FROM `Catalogue` ORDER BY `Catalogue_ID`";
    $catalogues = (array)$db->get_col($sql);

    $favorites = array();

    foreach ($catalogues as $catalogue) {
        // показать только те разделы, админом\модер. которго пол-ль является
        $allow_id = $perm->GetAllowSub($catalogue, MASK_ADMIN | MASK_MODERATE, false, true, false);
        $security_limit = is_array($allow_id) ? "Subdivision_ID IN(" . join(',', (array)$allow_id) . ")" : "1";

        $result = (array)$db->get_results("SELECT " . $select . "
                                   FROM Subdivision AS sub
                              LEFT JOIN Catalogue AS catalogue ON catalogue.Catalogue_ID = sub.Catalogue_ID
                                  WHERE sub.Favorite = '1' AND sub.Catalogue_ID = {$catalogue} AND " . $security_limit . "
                               ORDER BY Catalogue_ID,
                                        Subdivision_ID", $returnType);

        $favorites = array_merge($favorites, $result);
    }

    return $favorites;
}

/**
 * Проверка прав на запись в директории
 *
 * @param string $dir_name относительный путь, например, /netcat/tmp/
 * @param string $DOCUMENT_ROOT
 * @param bool $throw_exception
 *
 * @return bool|string
 * @throws Exception
 */
function checkPermissions($dir_name, $DOCUMENT_ROOT, $throw_exception = false) {
    global $SUB_FOLDER;

    $filepath = $DOCUMENT_ROOT . $SUB_FOLDER . $dir_name . 'n123.tmp';

    $h = @fopen($filepath, 'wb');
    if ($h === false) {
        $message = sprintf(NETCAT_FILEUPLOAD_ERROR, $SUB_FOLDER . $dir_name);
        if ($throw_exception) {
            throw new Exception($message);
        }
        nc_print_status($message, 'error', null, $throw_exception);
        $result = false;
    } else {
        fclose($h);
        @unlink($filepath);
        $result = true;
    }

    return $result;
}

/**
 * Варианты (<option>) для списка объектов (<select>)
 *
 * @param array нумерованный массив, содержащий ассоциативные массивы со
 *    значениями (должны содержать ключи value, description; опционально -
 *    parent; если есть parent, результат будет перегруппирован по данному
 *    полю; optgroup; если есть optgroup создается контейнеры, объединяющие группу списка)
 * @param mixed выбранный элемент
 * @param int текущий уровень иерархического списка (для рекурсивного вызова функции)
 *
 * @return string
 */
function nc_select_options(&$data, $selected_value = "", $level = 0, $current_parent = 0, $null_value = 0) {

    if (!is_array($data)) {
        trigger_error("nc_select_options: first parameter is not an array", E_USER_WARNING);

        return "";
    }

    $str = "";
    if (!$level) { // первый вызов функции
        if (array_key_exists('parent', $data[0])) { // перегруппировать по parent
            foreach ((array)$data as $row) {
                $values[$row['parent']][] = $row;
            }
        } else { // чтобы не делить циклы для случаев с группировкой и без нее
            $values = array(&$data);
        }
    } else { // рекурсивный вызов функции
        $values = &$data;
    }

    if ($null_value) {
        $str .= "<option value=\"0\">" . NETCAT_MODERATION_LISTS_CHOOSE . "</option>\n";
    }
    $optgroup = null;
    foreach ((array)$values[$current_parent] as $row) {
        if (!$level && $optgroup !== null && (!isset($row['optgroup']) || $optgroup != $row['optgroup'])) {
            $optgroup = null;
            $str .= "</optgroup>\n";
        }
        if (!$level && !empty($row['optgroup']) && ($optgroup != $row['optgroup'])) {
            $optgroup = $row['optgroup'];
            $str .= "<optgroup label='" . $optgroup . "'>\n";
        }
        $str .= "<option " . (!empty($row['without_cc']) ? 'style=\'color: #cccccc;\'' : '') . " value=\"" .
            htmlspecialchars($row['value']) . "\"" .
            ($row['value'] == $selected_value ? ' selected' : '') .
            ">" .
            str_repeat("&nbsp; &nbsp; &nbsp;", $level) .
            htmlspecialchars($row['description']) . "</option>\n";

        if (!empty($values[$row['value']])) {
            $str .= nc_select_options($values, $selected_value, $level + 1, $row['value']);
        }
    }

    if ($optgroup !== null) {
        $str .= "</optgroup>\n";
    }

    return $str;
}

/**
 * Поиск элемента в многомерном массиве
 *
 * @param string $value искомое значение
 * @param array $array массив, в котором ищется значение $value
 *
 * @return bool
 */
function nc_deep_in_array($value, $array) {
    foreach ($array as $item) {
        if (!is_array($item)) {
            if ($item == $value) {
                return true;
            } else {
                continue;
            }
        }

        if (in_array($value, $item)) {
            return true;
        } else if (nc_deep_in_array($value, $item)) {
            return true;
        }
    }

    return false;
}


/**
 * Устанавливает значение ключей массива из его значений
 *
 * @param array [$array]  исходный массив
 * @param string [$key_name] Название поля значение которого станет ключем
 * @param string|array|null [$value_keys] Название поля(ей) значений для результрующего массива
 *
 * @return array
 */
function nc_array_set_keys($array, $key_name, $value_keys = null) {
    $result = array();

    if (!count($array)) {
        return $result;
    }

    if (is_object(current($array))) {
        if (is_array($value_keys)) {
            foreach ($array as $row) {
                $result[$row->$key_name] = new stdClass;
                foreach ($value_keys as $k)
                    $result[$row->$key_name]->$k = $row->$k;
            }
        } elseif (is_string($value_keys)) {
            foreach ($array as $row)
                $result[$row->$key_name] = $row->$value_keys;
        } else {
            foreach ($array as $row)
                $result[$row->$key_name] = $row;
        }
    } else {
        if (is_array($value_keys)) {
            foreach ($array as $row) {
                $result[$row[$key_name]] = array();
                foreach ($value_keys as $k)
                    $result[$row[$key_name]][$k] = $row[$k];
            }
        } elseif (is_string($value_keys)) {
            foreach ($array as $row)
                $result[$row[$key_name]] = $row[$value_keys];
        } else {
            foreach ($array as $row)
                $result[$row[$key_name]] = $row;
        }
    }


    return $result;
}

/**
 * Формирование ссылки "просмотр раздела" внутри админки с учётом ExternalURL
 * (дерево слева, карта сайта, избранные разделы)
 *
 * @param array $data массив с данными по разделу из Subdivision
 * @param bool $sid_off принудительно выключить id сессии
 *
 * @return string
 * @throws Exception
 */
function nc_subdivision_preview_link($data, $sid_off = false) {
    $nc_core = nc_Core::get_object();

    if (!is_array($data)) {
        if (is_object($data)) {
            $data = (array)$data;
        } else {
            return false;
        }
    }

    $redirect_params = array(
        'ctrl' => 'admin.subdivision',
        'action' => 'redirect_to_preview',
        'subdivision_id' => $data['Subdivision_ID']
    );

    $query = http_build_query($redirect_params, null, '&');
    return "{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}action.php?$query";
}

/**
 * Формирование ссылки "Редактирование раздела" внутри админки
 *
 * @param int $sub ID раздела
 *
 * @return string
 * @throws Exception
 */
function nc_subdivision_objects_link($sub) {
    $nc_core = nc_Core::get_object();

    $redirect_params = array(
        'ctrl' => 'admin.subdivision',
        'action' => 'redirect_to_object_list',
        'subdivision_id' => (int)$sub
    );

    $query = http_build_query($redirect_params, null, '&');
    return "{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}action.php?$query";
}

function nc_system_name_by_id($id) {
    // При добавлении новой редакции см. также: $nc_core->get_edition_name()
    $nc_core = nc_core::get_object();
    switch ($id) {
        case 1:
            $system_name = "Small Business";
            $system_color = "#FF9900";
            break;
        case 2:
            $system_name = "Standard";
            $system_color = "#2690CF";
            break;
        case 3:
            $system_name = "Extra";
            $system_color = "#CC3300";
            break;
        case 4:
            $system_name = "Community";
            $system_color = "#65C11A";
            break;
        case 5:
            $system_name = "Lite";
            $system_color = "#000000";
            break;
        case 6:
            $system_name = "E-Commerce";
            $system_color = "#F07D22";
            break;
        case 7:
            $system_name = "SEO";
            $system_color = "#FDCC6D";
            break;
        case 8:
            $system_name = "Corporate";
            $system_color = "#525F67";
            break;
        case 10:
            $system_name = "Personal";
            $system_color = "#990066";
            break;
        case 12:
            $system_name = "Business";
            $system_color = "#FDCC6D";
            break;
        case 13:
            $system_name = "Standard+";
            $system_color = "#2690CF";
            break;
    }

    if ($nc_core->beta) {
        $system_name .= " <b>BETA</b>";
    }

    return array($system_name, $system_color);
}

/* Возвращает true если версия совместима, иначе false
 * @param string
 *        - номер проверяемой версии
 */

function nc_version_control($tpl_version) {
    $tpl_version = preg_replace('/[^0-9\.]/', '', $tpl_version);
    $nc_core = nc_Core::get_object();
    $VersionNumber = $nc_core->get_settings("VersionNumber");
    $versions = array();
    $versions[$VersionNumber] = array();
    $versions['4.6'] = array('4.5');
    $versions['4.7'] = array('4.5', '4.6');

    return ($tpl_version == $VersionNumber || in_array($tpl_version, $versions[$VersionNumber]));
}


function nc_print_admin_save_script($form_id) {
    ?>
    <script>
        $nc('#<?= $form_id; ?>').ready(function () {
            if ($nc('#<?= $form_id; ?>').html() && parent.mainView) {
                parent.mainView.attachClickHandler("#nc_class_save", function () {
                    CMSaveAll();
                    nc_save_editor_values();
                    formAsyncSave('<?= $form_id; ?>', 0, "formSaveStatus(1);");
                    parent.mainView.chan = 0;
                    parent.mainView.displayStar(0);
                });
            }
        });
    </script>
    <?
}

function nc_naked_action_header() {
    if (!$_REQUEST['isNaked']) {
        echo "<div id='nc_admin_mode_content'>";
    } else {
        ob_clean();
    }
}

function nc_naked_action_footer() {
    if (!$_REQUEST['isNaked']) {
        echo "</div>";
    } else {
        exit;
    }
}

function nc_get_simple_modal_header($name) {
    ob_start();
    ?>
    <div class='nc_admin_form_menu' style='padding-top: 20px;'>
        <h2><?= $name; ?></h2>
        <div id='nc_object_slider_menu' class='slider_block_2' style='padding-top: 0px; padding-bottom: 15px;'>
            <ul>
                <li id='nc_template_form_edit' class=''></li>
            </ul>
        </div>
        <div class='nc_admin_form_menu_hr'></div>
    </div>

    <div class='nc_admin_form_body'>
        <?
        return ob_get_clean();
        }

        function nc_get_simple_modal_footer() {
        ob_start();

        ?>
    </div>

    <div class='nc_admin_form_buttons'>
        <button type='button' class='nc_admin_metro_button nc-btn nc--blue'
                disable><?= NETCAT_REMIND_SAVE_SAVE; ?></button>
        <button type='button'
                class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <style>
        a {
            color: #1a87c2;
        }

        a:hover {
            text-decoration: none;
        }

        a img {
            border: none;
        }

        p {
            margin: 0px;
            padding: 0px 0px 18px 0px;
        }

        h2 {
            font-size: 20px;
            font-family: 'Segoe UI', SegoeWP, Arial;
            color: #333333;
            font-weight: normal;
            margin: 0px;
            padding: 20px 0px 10px 0px;
            line-height: 20px;
        }

        form {
            margin: 0px;
            padding: 0px;
        }

        input {
            outline: none;
        }

        .clear {
            margin: 0px;
            padding: 0px;
            font-size: 0px;
            line-height: 0px;
            height: 1px;
            clear: both;
            float: none;
        }

        select, input, textarea {
            border: 1px solid #dddddd;
        }

        :focus {
            outline: none;
        }

        .input {
            outline: none;
            border: 1px solid #dddddd;
        }
    </style>

    <script>
        var nc_admin_metro_buttons = $nc(".nc_admin_metro_button");

        $nc(function () {
            $nc("#adminForm").html("<div class=\"nc_admin_form_main\">" + $nc("#adminForm").html() + "</div>");
        });

        $nc(".nc_admin_metro_button_cancel").click(function () {
            $nc.modal.close();
        });

        nc_admin_metro_buttons.click(function () {
            $nc("#adminForm").submit();
        });
    </script>
    <?
    return ob_get_clean();
}

function nc_get_array_2json_button($label, $action_hash, $icon_class) {
    return array(
        'label' => $label,
        'action' => "parent.location.hash = '$action_hash'",
        'icon' => "icons $icon_class",
    );
}


/**
 * Удаление директории относительно
 *
 * @param  [string] $path Путь к директории
 *
 * @return [int|bool]     Кол-во удаленных файлов | false - если директория не существует или ее удаление запрещено
 */
function remove_dir($path) {
    static $exclude_pathes;
    static $DS = DIRECTORY_SEPARATOR;

    if (is_null($exclude_pathes)) {
        $nc_core = nc_core::get_object();
        $exclude_pathes = array(
            $nc_core->DOCUMENT_ROOT,
            $nc_core->NETCAT_FOLDER,
            $nc_core->ADMIN_FOLDER,
            $nc_core->SYSTEM_FOLDER,
            $nc_core->INCLUDE_FOLDER,
        );
    }

    $result = 0;
    $path = rtrim($path, $DS);

    if (in_array($path . $DS, $exclude_pathes)) {
        return false;
    }

    if (!file_exists($path)) {
        return false;
    }

    if (is_dir($path)) {
        $dh = opendir($path);
        while ($f = readdir($dh)) {
            if ($f == '.' || $f == '..') {
                continue;
            }
            $result += remove_dir($path . $DS . $f);
        }
        closedir($dh);
        $result += rmdir($path);
    } else {
        $result += unlink($path);
    }

    return $result;
}

/**
 * Возвращает информацию о состоянии технической поддержки
 *
 * @param string|null $product_number
 *
 * @return array
 */
function nc_get_support_info($product_number = null) {
    $core = nc_core::get_object();
    $product_number = $product_number ?: $core->get_settings("ProductNumber");
    $result = array();

    if (!$product_number) {
        return $result;
    }

    try {
        $http = new nc_http();
        $response = $http->make_get_request(
            "https://netcat.ru/support/moya-litsenziya/",
            array(
                "search_word" => $product_number,
                "json" => true,
            )
        );

        return is_array($response) ? $response : array();
    } catch (nc_http_exception $e) {
        return $result;
    }
}

/**
 * Возвращает перечень параметров системы и ее версии
 *
 * @return array
 */
function nc_get_system_info() {
    global $SYSTEM_ID;

    $admin_notice = new nc_AdminNotice();
    $nc_core = nc_core::get_object();

    return array(
        "system_id" => (int)$SYSTEM_ID,
        "system_name" => nc_array_value(nc_system_name_by_id($SYSTEM_ID), 0),
        "system_version" => $nc_core->get_settings("VersionNumber"),
        "product_number" => $nc_core->get_settings("ProductNumber"),
        "actual_version" => $admin_notice->update(),
    );
}
