<?php

function s_browse_template_check($browse_template) {
    if (is_array($browse_template) && !empty($browse_template)) {
        foreach ($browse_template as $key => $value) {
            /*if ( is_array($value) ) {
              $browse_template[$key] = s_browse_template_check($value);
            }*/
            foreach ($_REQUEST as $k => $v) {
                if (is_array($_REQUEST[$k]) && isset($_REQUEST[$k][$key]) && $_REQUEST[$k][$key] == $browse_template[$key]) {
                    $value = '';
                }
            }
            $browse_template[$key] = $value;
        }
    }
    return $browse_template;
}

function s_browse_path($browse_template) {
    global $sub_level_count;
    return s_browse_path_range(-1, $sub_level_count, $browse_template);
}

/**
 * Путь к объекту в виде строки
 *
 * @var integer Какое количество пунктов отсчитать от конца пути.
 *       -1 - вывести в т.ч. текущий путь
 *       0  - до последнего пункта
 *       1  - до предпоследнего etc
 * @var integer ОТ какого пункта от конца начать путь:
 *       $sub_level_count - от корня
 *       $sub_level_count-1 - от первого уровня etc.
 * @var array
 * @var integer Как выводить путь
 *       0 - (по умолчанию) по порядку
 *       1 - в обратном порядке
 * @var integer Выводить ли имя компонента в разделе
 *       0 - (по умолчанию) только если компонент неосновной (не первый) в разделе
 *       1 - выводить всегда
 *       2 - никогда не выводить
 * @return string
 */
function s_browse_path_range($from, $to, $browse_template, $reverse = 0, $show = 0) {
    global $REQUEST_URI, $f_title;
    global $admin_mode, $admin_url_prefix;
    global $current_catalogue, $current_sub, $current_cc, $cc_array;
    global $parent_sub_tree, $sub_level_count;
    global $titleTemplate, $action, $message, $classID;
    global $user_table_mode, $db, $SUB_FOLDER, $_db_cc, $nc_core;

    // check php-include vulnerability
    $browse_template = s_browse_template_check($browse_template);

    //FIXME удалить если для полного отображения по ключевому слову будет определен $current_cc не по источнику зеркала
    if ($action == 'full' && $_db_cc != $current_cc['Sub_Class_ID']) {
        $current_cc_old = $current_cc;
        $current_cc = $nc_core->sub_class->get_by_id($_db_cc);
    }

    $result = '';

    if ($to > $sub_level_count) {
        $to = $sub_level_count;
    }

    if ($from < -1) {
        $from = -1;
    }

    if (isset($browse_template['prefix'])) {
        eval(nc_check_eval("\$result = \"" . $browse_template['prefix'] . "\";"));
    }

    $routing_module_enabled = nc_module_check_by_keyword('routing');

    $current_page_path = urldecode(strtok($REQUEST_URI, '?'));

    $result_array_name = array();
    $result_array_url = array();

    if ($show == 0 && $current_catalogue['Title_Sub_ID'] == $current_sub['Subdivision_ID']) {
        $from++;
    }

    for ($i = $to; $i > $from; $i--) {
        $result_array_name[] = $parent_sub_tree[$i]['Subdivision_Name'];
        if ($admin_mode) {
            $result_array_url[] = $admin_url_prefix . "?catalogue=" . $parent_sub_tree[$i]['Catalogue_ID']
                . ($parent_sub_tree[$i]["Subdivision_ID"] ? "&amp;sub=" . $parent_sub_tree[$i]["Subdivision_ID"] : "");
        }
        else {
            if (isset($parent_sub_tree[$i]["ExternalURL"]) && ($ext_url = $parent_sub_tree[$i]["ExternalURL"])) {
                $result_array_url[] = (strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/")
                    ? $ext_url
                    : $SUB_FOLDER . $parent_sub_tree[$i]['Hidden_URL'] . $ext_url;
            }
            else if ($routing_module_enabled && isset($parent_sub_tree[$i]['Subdivision_ID'])) {
                $result_array_url[] = (string)nc_routing::get_folder_path($parent_sub_tree[$i]['Subdivision_ID']);
            }
            else {
                $result_array_url[] = $SUB_FOLDER . $parent_sub_tree[$i]['Hidden_URL'];
            }
        }
    }

    if (!empty($current_cc['Checked'])) {
        if ($show == 0 && $current_cc['Sub_Class_ID'] != $cc_array[0]) {
            $result_array_name[] = $current_cc['Sub_Class_Name'];
            if (isset($current_cc["ExternalURL"]) && ($ext_url = $current_cc["ExternalURL"])) {
                $result_array_url[] = ((strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/")
                        ? $ext_url
                        : $SUB_FOLDER . $current_cc[$i]['Hidden_URL'] . $ext_url) . ".html";
            } else if ($routing_module_enabled) {
                $result_array_url[] = (string)nc_routing::get_infoblock_path($current_cc['Sub_Class_ID']);
            } else {
                $result_array_url[] = $SUB_FOLDER . $current_sub['Hidden_URL'] . $current_cc['EnglishName'] . ".html";
            }
        } elseif ($show == 1) {
            $result_array_name[] = $current_cc['Sub_Class_Name'];
            if (isset($current_cc["ExternalURL"]) && ($ext_url = $current_cc["ExternalURL"])) {
                $result_array_url[] = ((strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/")
                        ? $ext_url
                        : $SUB_FOLDER . $current_cc[$i]['Hidden_URL'] . $ext_url) . ".html";
            } else if ($routing_module_enabled) {
                $result_array_url[] = (string)nc_routing::get_infoblock_path($current_cc['Sub_Class_ID']);
            } else {
                $result_array_url[] = $SUB_FOLDER . $current_sub['Hidden_URL'] . $current_cc['EnglishName'] . ".html";
            }
        }
    }

    if ($titleTemplate && $action == 'full') {
        $result_array_name[] = $f_title;
        $result_array_url[] = $current_page_path;
    }

    if (!$reverse) {
        $result_array_name = array_reverse($result_array_name);
        $result_array_url = array_reverse($result_array_url);
    }

    foreach (array('active_link', 'active', 'unactive', 'divider') as $i) {
        if (!empty($browse_template[$i])) {
            $browse_template[$i] = $nc_core->security->php_filter->filter($browse_template[$i], 'string');
        }
    }

    for ($j = $from, $i = count($result_array_name) - 1; $i > -1; $i--) {

        if ($reverse) {
            $j++;
        }
        else {
            $j = $i + ($from + 1);
        }

        if (isset($parent_sub_tree[$j]["Subdivision_ID"]) && $current_sub["Subdivision_ID"] == $parent_sub_tree[$j]["Subdivision_ID"]) {
            if ($browse_template['active_link'] && ($result_array_url[$j] == $current_page_path)) {
                eval("\$result.= \"" . $browse_template['active_link'] . "\";"); // проверка производится до цикла
            }
            else {
                eval("\$result.= \"" . $browse_template['active'] . "\";"); // проверка производится до цикла
            }
        }
        else {
            eval("\$result.= \"" . $browse_template['unactive'] . "\";"); // проверка производится до цикла
        }

        $result = str_replace("%NAME", $result_array_name[$i], $result);
        $result = str_replace("%URL", $result_array_url[$i], $result);

        if (0 < $i) {
            eval("\$result .= \"" . $browse_template['divider'] . "\";");
        }
    }

    if (isset($browse_template['suffix'])) {
        eval(nc_check_eval("\$result.= \"" . $browse_template['suffix'] . "\";"));
    }
    //FIXME удалить если для полного отображения по ключевому слову будет определен $current_cc не по источнику зеркала
    if (isset($current_cc_old)) {
        $current_cc = $current_cc_old;
    }
    return $result;
}

function nc_browse_path($browse_template) {
    global $sub_level_count;
    return nc_browse_path_range(-1, $sub_level_count, $browse_template);
}

function nc_browse_path_range($from, $to, $browse_template, $reverse = 0, $show = 0) {
    global $REQUEST_URI, $f_title;
    global $admin_mode, $admin_url_prefix;
    global $current_catalogue, $current_sub, $current_cc, $cc_array;
    global $parent_sub_tree, $sub_level_count;
    global $titleTemplate, $action, $message, $classID;
    global $user_table_mode, $db, $SUB_FOLDER, $_db_cc, $nc_core;

    $routing_module_enabled = nc_module_check_by_keyword('routing');

    $current_page_path = urldecode(strtok($REQUEST_URI, '?'));

    //FIXME удалить если для полного отображения по ключевому слову будет определен $current_cc не по источнику зеркала
    if ($action == 'full' && $_db_cc != $current_cc['Sub_Class_ID']) {
        $current_cc_old = $current_cc;
        $current_cc = $nc_core->sub_class->get_by_id($_db_cc);
    }

    if ($to > $sub_level_count) {
        $to = $sub_level_count;
    }

    if ($from < -1) {
        $from = -1;
    }

    $result = $browse_template['prefix'];

    $result_array_name = array();
    $result_array_url = array();

    if ($show == 0 && $current_catalogue['Title_Sub_ID'] == $current_sub['Subdivision_ID']) {
        $from++;
    }

    for ($i = $to; $i > $from; $i--) {
        $result_array_name[] = $parent_sub_tree[$i]['Subdivision_Name'];
        if ($admin_mode) {
            $result_array_url[] = $admin_url_prefix . "?catalogue=" . $parent_sub_tree[$i]['Catalogue_ID']
                . ($parent_sub_tree[$i]["Subdivision_ID"] ? "&amp;sub=" . $parent_sub_tree[$i]["Subdivision_ID"] : "");
        }
        else {
            if (isset($parent_sub_tree[$i]["ExternalURL"]) && ($ext_url = $parent_sub_tree[$i]["ExternalURL"])) {
                $result_array_url[] = (strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/")
                    ? $ext_url
                    : $SUB_FOLDER . $parent_sub_tree[$i]['Hidden_URL'] . $ext_url;
            }
            else if ($routing_module_enabled && isset($parent_sub_tree[$i]['Subdivision_ID'])) {
                $result_array_url[] = (string)nc_routing::get_folder_path($parent_sub_tree[$i]['Subdivision_ID']);
            }
            else {
                $result_array_url[] = $SUB_FOLDER . $parent_sub_tree[$i]['Hidden_URL'];
            }
        }
    }

    if (!empty($current_cc['Checked'])) {
        if ($show == 0 && $current_cc['Sub_Class_ID'] != $cc_array[0]) {
            $result_array_name[] = $current_cc['Sub_Class_Name'];
            if (isset($current_cc["ExternalURL"]) && ($ext_url = $current_cc["ExternalURL"])) {
                $result_array_url[] = ((strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/")
                        ? $ext_url
                        : $SUB_FOLDER . $current_cc[$i]['Hidden_URL'] . $ext_url) . ".html";
            } else if ($routing_module_enabled) {
                $result_array_url[] = (string)nc_routing::get_infoblock_path($current_cc['Sub_Class_ID']);
            } else {
                $result_array_url[] = $SUB_FOLDER . $current_sub['Hidden_URL'] . $current_cc['EnglishName'] . ".html";
            }
        } elseif ($show == 1) {
            $result_array_name[] = $current_cc['Sub_Class_Name'];
            if (isset($current_cc["ExternalURL"]) && ($ext_url = $current_cc["ExternalURL"])) {
                $result_array_url[] = ((strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/")
                        ? $ext_url
                        : $SUB_FOLDER . $current_cc[$i]['Hidden_URL'] . $ext_url) . ".html";
            } else if ($routing_module_enabled) {
                $result_array_url[] = (string)nc_routing::get_infoblock_path($current_cc['Sub_Class_ID']);
            } else {
                $result_array_url[] = $SUB_FOLDER . $current_sub['Hidden_URL'] . $current_cc['EnglishName'] . ".html";
            }
        }
    }

    if ($titleTemplate && $action == 'full') {
        $result_array_name[] = $f_title;
        $result_array_url[] = $current_page_path;
    }

    if (!$reverse) {
        $result_array_name = array_reverse($result_array_name);
        $result_array_url = array_reverse($result_array_url);
    }

    $array_result = array();
    for ($j = $from, $i = count($result_array_name) - 1; $i > -1; $i--) {

        if ($reverse) {
            $j++;
        }
        else {
            $j = $i + ($from + 1);
        }

        if (isset($parent_sub_tree[$j]["Subdivision_ID"]) && $current_sub["Subdivision_ID"] == $parent_sub_tree[$j]["Subdivision_ID"]) {
            if ($browse_template['active_link'] && ($result_array_url[$j] == $current_page_path)) {
                $array_result[$j] = $browse_template['active_link'];
            }
            else {
                $array_result[$j] = $browse_template['active'];
            }
        }
        else {
            $array_result[$j] = $browse_template['unactive'];
        }

        $array_result[$j] = str_replace("%NAME", $result_array_name[$i], $array_result[$j]);
        $array_result[$j] = str_replace("%URL", $result_array_url[$i], $array_result[$j]);

    }

    $result .= implode($browse_template['divider'], $array_result);

    if (isset($browse_template['suffix'])) {
        $result .= $browse_template['suffix'];
    }
    //FIXME удалить если для полного отображения по ключевому слову будет определен $current_cc не по источнику зеркала
    if (isset($current_cc_old)) {
        $current_cc = $current_cc_old;
    }
    return $result;
}

function s_browse_catalogue($browse_template) {
    global $nc_core, $db, $current_catalogue;
    global $DOMAIN_NAME, $REQUEST_URI;
    global $admin_mode, $admin_url_prefix;
    global $system_table_fields, $HTTP_FILES_PATH, $SUB_FOLDER;

    // check php-include vulnerability
    $browse_template = s_browse_template_check($browse_template);

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_catalogue['Cache_Access_ID'] == 1 && $browse_template['nocache'] != true) {
        $nc_cache_browse = nc_cache_browse::getObject();
        try {
            // check cached data
            $cached_data = $nc_cache_browse->read($current_catalogue, $browse_template, $REQUEST_URI, $current_catalogue['Cache_Lifetime']);
            if ($cached_data !== -1) {
                // debug info
                $cache_debug_info = "Read, catalogue[" . $current_catalogue['Catalogue_ID'] . "], Access_ID[" . $current_catalogue['Cache_Access_ID'] . "], Lifetime[" . $current_catalogue['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    $sort_by = nc_array_value($browse_template, 'sortby') ?: '`Priority`';

    $data = $db->get_results(
        "SELECT `Catalogue_ID`, `Catalogue_Name`, `Domain`
         FROM `Catalogue`
         WHERE `Checked` = 1
         ORDER BY {$sort_by}", ARRAY_A
    );

    $data_count = count($data);

    if (!$data_count) {
        return null;
    }

    $result = '';
    eval(nc_check_eval("\$result = \"" . $browse_template['prefix'] . "\";"));

    foreach (array('active_link', 'active', 'unactive', 'divider') as $i) {
        if (!empty($browse_template[$i])) {
            $browse_template[$i] = $nc_core->security->php_filter->filter($browse_template[$i], 'string');
        }
    }

    for ($i = 0; $i < $data_count; $i++) {
        $data[$i] = $nc_core->catalogue->get_by_id($data[$i]['Catalogue_ID']);
        $catalogue_url = $nc_core->catalogue->get_url_by_id($data[$i]['Catalogue_ID']);
        $nav_name = $data[$i]["Catalogue_Name"];

        if ($admin_mode) {
            $nav_url = $admin_url_prefix . "?catalogue=" . $data[$i]["Catalogue_ID"];
        }
        else {
            $nav_url = $catalogue_url . $SUB_FOLDER;
        }

        if ($data[$i]["Catalogue_ID"] === $current_catalogue["Catalogue_ID"]) {
            if ($REQUEST_URI === '/' && $browse_template['active_link']) {
                eval("\$result.= \"" . $browse_template['active_link'] . "\";"); // проверка производится до цикла
            }
            else {
                eval("\$result.= \"" . $browse_template['active'] . "\";"); // проверка производится до цикла
            }
        }
        else {
            eval("\$result.= \"" . $browse_template['unactive'] . "\";"); // проверка производится до цикла
        }

        if ($i !== ($data_count - 1)) {
            eval("\$result.= \"" . $browse_template['divider'] . "\";"); // проверка производится до цикла
        }

        $result = str_replace("%NAME", $nav_name, $result);
        $result = str_replace("%URL", $nav_url, $result);
        $result = str_replace("%CATALOGUE", $data[$i]["Catalogue_ID"], $result);
        $result = str_replace("%COUNTER", $i, $result);

        $result = nc_replace_macro_variables($result, $data[$i]);
    }
    eval(nc_check_eval("\$result.= \"" . $browse_template['suffix'] . "\";"));

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_catalogue['Cache_Access_ID'] == 1 && isset($nc_cache_browse) && is_object($nc_cache_browse) && $browse_template['nocache'] != true) {
        try {
            $bytes = $nc_cache_browse->add($current_catalogue, $browse_template, $REQUEST_URI, $result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Written, catalogue[" . $current_catalogue['Catalogue_ID'] . "], Access_ID[" . $current_catalogue['Cache_Access_ID'] . "], Lifetime[" . $current_catalogue['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

function nc_browse_catalogue($browse_template) {
    global $nc_core, $db, $current_catalogue;
    global $DOMAIN_NAME, $REQUEST_URI;
    global $admin_mode, $admin_url_prefix;
    global $system_table_fields, $HTTP_FILES_PATH, $SUB_FOLDER;

    if (nc_module_check_by_keyword("cache") && $current_catalogue['Cache_Access_ID'] == 1 && $browse_template['nocache'] != true) {
        $nc_cache_browse = nc_cache_browse::getObject();
        try {
            $cached_data = $nc_cache_browse->read($current_catalogue, $browse_template, $REQUEST_URI, $current_catalogue['Cache_Lifetime']);

            if ($cached_data !== -1) {
                $cache_debug_info = "Read, catalogue[" . $current_catalogue['Catalogue_ID'] . "], Access_ID[" . $current_catalogue['Cache_Access_ID'] . "], Lifetime[" . $current_catalogue['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                return $cached_data;
            }
        } catch (Exception $e) {
            $nc_cache_browse->errorMessage($e);
        }
    }

    $sort_by = nc_array_value($browse_template, 'sortby') ?: '`Priority`';

    $data = $db->get_results(
        "SELECT `Catalogue_ID`, `Catalogue_Name`, `Domain`
         FROM `Catalogue`
         WHERE `Checked` = 1
         ORDER BY {$sort_by}", ARRAY_A
    );

    $data_count = count($data);

    if (!$data_count) {
        return null;
    }

    $result = $browse_template['prefix'];
    $array_result = array();

    for ($i = 0; $i < $data_count; $i++) {
        $data[$i] = $nc_core->catalogue->get_by_id($data[$i]['Catalogue_ID']);
        $catalogue_url = $nc_core->catalogue->get_url_by_id($data[$i]['Catalogue_ID']);
        $nav_name = $data[$i]["Catalogue_Name"];

        if ($admin_mode) {
            $nav_url = $admin_url_prefix . "?catalogue=" . $data[$i]["Catalogue_ID"];
        }
        else {
            $scheme = $data[$i]['ncHTTPS'] === '1' ? 'https' : 'http';
            $nav_url = $catalogue_url . $SUB_FOLDER;
        }

        if ($data[$i]["Catalogue_ID"] === $current_catalogue["Catalogue_ID"]) {
            if ($REQUEST_URI === '/' && $browse_template['active_link']) {
                $array_result[$i] = $browse_template['active_link'];
            }
            else {
                $array_result[$i] = $browse_template['active'];
            }
        }
        else {
            $array_result[$i] = $browse_template['unactive'];
        }

        $array_result[$i] = str_replace("%NAME", $nav_name, $array_result[$i]);
        $array_result[$i] = str_replace("%URL", $nav_url, $array_result[$i]);
        $array_result[$i] = str_replace("%CATALOGUE", $data[$i]["Catalogue_ID"], $array_result[$i]);
        $array_result[$i] = str_replace("%COUNTER", $i, $array_result[$i]);

        $array_result[$i] = nc_replace_macro_variables($array_result[$i], $data[$i]);
    }

    $result .= implode($browse_template['divider'], $array_result);
    $result .= $browse_template['suffix'];

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_catalogue['Cache_Access_ID'] == 1 && isset($nc_cache_browse) && is_object($nc_cache_browse) && $browse_template['nocache'] != true) {
        try {
            $bytes = $nc_cache_browse->add($current_catalogue, $browse_template, $REQUEST_URI, $result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Written, catalogue[" . $current_catalogue['Catalogue_ID'] . "], Access_ID[" . $current_catalogue['Cache_Access_ID'] . "], Lifetime[" . $current_catalogue['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

/**
 * Вывод подразделов для меню
 *
 * @param int $browse_parent_sub номер раздела, подразделы которого попадут в меню
 * @param array $browse_template - массив-шаблон
 * @param int $ignore_check - игнорировать только включенные разделы, по умолчанию - 0
 * @param string $where_cond - дополнительное условие в запрос на выбор разделов
 *
 * @return string меню разделов
 */
function s_browse_sub($browse_parent_sub, $browse_template, $ignore_check = 0, $where_cond = "") {
    global $REQUEST_URI;
    global $admin_mode, $admin_url_prefix;
    global $current_sub;
    global $parent_sub_tree, $sub_level_count, $system_table_fields;
    global $db, $nc_core, $HTTP_FILES_PATH, $SUB_FOLDER;

    // check php-include vulnerability
    $browse_template = s_browse_template_check($browse_template);

    // this happens when non-existent sub requested in admin mode
    if (!$current_sub["Subdivision_ID"]) {
        return "";
    }

    $query_string = $REQUEST_URI . $ignore_check . $where_cond;

    // cache section
    $is_cache_enabled = nc_module_check_by_keyword("cache");
    $nc_cache_browse = null;
    if ($is_cache_enabled && $current_sub['Cache_Access_ID'] == 1 && !isset($browse_template['nocache'])) {
        $nc_cache_browse = nc_cache_browse::getObject();
        try {
            // check cached data
            $cached_data = $nc_cache_browse->read($current_sub, $browse_template, $query_string, $current_sub['Cache_Lifetime'], $browse_parent_sub);
            if ($cached_data !== -1) {
                // debug info
                $cache_debug_info = "Read, catalogue[" . $current_sub['Catalogue_ID'] . "], sub[" . $current_sub['Subdivision_ID'] . "], Access_ID[" . $current_sub['Cache_Access_ID'] . "], Lifetime[" . $current_sub['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    $sort_by = nc_array_value($browse_template, 'sortby') ?: '`Priority`';

    $display_type = $nc_core->get_display_type();

    $SQL = "SELECT *
              FROM `Subdivision`
             WHERE `Parent_Sub_ID` = '" . (int)$browse_parent_sub . "'
                " . ($ignore_check ? "" : "AND `Checked` = 1") . "
                " . ($where_cond ? " AND " . $where_cond : "") . "
                " . ($display_type == 'longpage_vertical' ? " AND `DisplayType` IN ('inherit', 'longpage_vertical')" : "") . "
                " . ($display_type == 'shortpage' ? " AND `DisplayType` IN ('inherit', 'shortpage')" : "") . "
               AND `Catalogue_ID` = '" . (int)$current_sub["Catalogue_ID"] . "'
             ORDER BY " . $db->escape($sort_by);

    $data = (array)$db->get_results($SQL, ARRAY_A);
    $data = $nc_core->subdivision->process_raw_data($data);

    // кол-во подразделов
    $data_count = count($data);

    if (!$data_count) {
        return null;
    }

    // prefix
    $result = '';
    eval(nc_check_eval("\$result = \"" . $browse_template['prefix'] . "\";"));

    $current_page_path = urldecode(strtok($REQUEST_URI, '?'));

    $page_sub_id = $nc_core->subdivision->get_current('Subdivision_ID');
    if ($page_sub_id) {
        $current_sub_path = nc_folder_path($page_sub_id);
    } else {
        $current_sub_path = substr($current_page_path, 0, strrpos($current_page_path, "/") + 1);
    }

    $routing_module_enabled = nc_module_check_by_keyword('routing');

    if (!empty($browse_template['divider'])) {
        $browse_template['divider'] = $nc_core->security->php_filter->filter($browse_template['divider'], 'string');
    }

    // Проход по всем подразделам
    for ($i = 0; $i < $data_count; $i++) {
        $row = $data[$i];
        $is_active_sub = 0;
        $nav_name = nc_quote_convert($row["Subdivision_Name"]);

        if ($admin_mode) {
            $nav_url = $admin_url_prefix . "?catalogue=" . $current_sub["Catalogue_ID"] . "&amp;sub=" . $row["Subdivision_ID"];
        }
        else {
            if ($ext_url = $row["ExternalURL"]) {
                $nav_url = (strchr($ext_url, ":") || substr($ext_url, 0, 1) == "/")
                    ? $ext_url
                    : $SUB_FOLDER . $row["Hidden_URL"] . $ext_url;
            }
            else if ($routing_module_enabled) {
                $nav_url = (string)nc_routing::get_folder_path($row["Subdivision_ID"]);
            }
            else {
                $nav_url = $SUB_FOLDER . $row["Hidden_URL"];
            }
        }

        for ($j = 0; $j < $sub_level_count; $j++) {
            if ($parent_sub_tree[$j]["Subdivision_ID"] == $row["Subdivision_ID"]) {
                $is_active_sub = 1;
                break;
            }
        }

        if ($nav_url == $REQUEST_URI || $nav_url == $current_page_path || $SUB_FOLDER . $row['ExternalURL'] == $current_page_path) {
            $current_template = $browse_template['active_link'] ? $browse_template['active_link'] : $browse_template['active'];
        }
        elseif ($is_active_sub || ($SUB_FOLDER . $row['ExternalURL'] == $current_sub_path)) {
            $current_template = $browse_template['active'];
        }
        else {
            $current_template = $browse_template['unactive'];
        }

        $current_template = str_replace("%NAME", $nav_name, $current_template);
        $current_template = str_replace("%URL", $nav_url, $current_template);
        $current_template = str_replace("%PARENT_SUB", $browse_parent_sub, $current_template);
        $current_template = str_replace("%KEYWORD", $row['EnglishName'], $current_template);
        $current_template = str_replace("%SUB", $row["Subdivision_ID"], $current_template);
        $current_template = str_replace("%COUNTER", $i, $current_template);

        $current_template = nc_replace_macro_variables($current_template, $data[$i]);

        eval(nc_check_eval("\$result.= \"" . $current_template . "\";"));

        if ($i <> ($data_count - 1)) {
            eval("\$result .= \"" . $browse_template['divider'] . "\";"); // проверка производится до цикла
        }
    }
    eval(nc_check_eval("\$result.= \"" . $browse_template['suffix'] . "\";"));

    // cache section
    if ($is_cache_enabled && $current_sub['Cache_Access_ID'] == 1 && is_object($nc_cache_browse) && !isset($browse_template['nocache'])) {
        try {
            $bytes = $nc_cache_browse->add($current_sub, $browse_template, $query_string, $result, $browse_parent_sub);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Written, catalogue[" . $current_sub['Catalogue_ID'] . "], sub[" . $current_sub['Subdivision_ID'] . "], Access_ID[" . $current_sub['Cache_Access_ID'] . "], Lifetime[" . $current_sub['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

function nc_browse_sub($browse_parent_sub, $browse_template, $ignore_check = 0, $where_cond = "", $level = 0) {
    global $REQUEST_URI;
    global $admin_mode, $admin_url_prefix;
    global $current_sub;
    global $parent_sub_tree, $sub_level_count, $system_table_fields;
    global $db, $nc_core, $HTTP_FILES_PATH, $SUB_FOLDER;

    $all_browse_template = $browse_template;
    $browse_template = nc_array_value($browse_template, $level);

    if (!is_array($browse_template)) {
        $browse_template = $all_browse_template;
    }

    if (!is_array($browse_template)) {
        return "";
    }
    if (!$current_sub["Subdivision_ID"]) {
        return "";
    }

    $query_string = $REQUEST_URI . $ignore_check . $where_cond;
    $nc_cache_browse = null;
    $is_cache_enabled = nc_module_check_by_keyword('cache');

    if ($is_cache_enabled && $current_sub['Cache_Access_ID'] == 1 && !isset($browse_template['nocache'])) {
        $nc_cache_browse = nc_cache_browse::getObject();
        try {
            // check cached data
            $cached_data = $nc_cache_browse->read($current_sub, $browse_template, $query_string, $current_sub['Cache_Lifetime'], $browse_parent_sub);
            if ($cached_data !== -1) {
                // debug info
                $bytes = strlen($cached_data);
                $cache_debug_info = "Read, catalogue[{$current_sub['Catalogue_ID']}], sub[{$current_sub['Subdivision_ID']}], Access_ID[{$current_sub['Cache_Access_ID']}], Lifetime[{$current_sub['Cache_Lifetime']}], bytes[{$bytes}]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    $sort_by = nc_array_value($browse_template, 'sortby') ?: '`Priority`';
    $display_type = $nc_core->get_display_type();

    $SQL = 'SELECT *
            FROM `Subdivision`
            WHERE `Catalogue_ID` = ' . (int)$current_sub['Catalogue_ID'] . '
            AND `Parent_Sub_ID` = ' . (int)$browse_parent_sub;

    if (!$ignore_check) {
        $SQL .= ' AND `Checked` = 1';
    }

    if ($where_cond) {
        $SQL .= " AND {$where_cond}";
    }

    switch ($display_type) {
        case 'longpage_vertical':
            $SQL .= " AND `DisplayType` IN ('inherit', 'longpage_vertical')";
            break;
        case 'shortpage':
            $SQL .= " AND `DisplayType` IN ('inherit', 'shortpage')";
            break;
    }

    $SQL .= ' ORDER BY ' . $db->escape($sort_by);
    $data = (array)$db->get_results($SQL, ARRAY_A);
    // Получаем все значения полей — с наследованием, доп. полями для файлов/списков и т.п.
    $data = $nc_core->subdivision->process_raw_data($data);
    $data_count = count($data);

    if (!$data_count) {
        return null;
    }

    $result = $browse_template['prefix'];
    $current_page_path = urldecode(strtok($REQUEST_URI, '?'));
    $page_sub_id = $nc_core->subdivision->get_current('Subdivision_ID');

    if ($page_sub_id) {
        $current_sub_path = nc_folder_path($page_sub_id);
    } else {
        $current_sub_path = substr($current_page_path, 0, strrpos($current_page_path, '/') + 1);
    }

    $routing_module_enabled = nc_module_check_by_keyword('routing');

    // Проход по всем подразделам
    $array_result = array();
    for ($i = 0; $i < $data_count; $i++) {
        $row = $data[$i];
        $is_active_sub = 0;
        $nav_name = nc_quote_convert($row['Subdivision_Name']);

        if ($admin_mode) {
            $nav_url = $admin_url_prefix . '?' . http_build_query(array(
                'catalogue' => $current_sub['Catalogue_ID'],
                'sub' => $row['Subdivision_ID']
            ), null, '&');
        } else {
            if ($ext_url = $row['ExternalURL']) {
                $nav_url = (strpos($ext_url, ':') !== false || $ext_url[0] === '/')
                    ? $ext_url
                    : $SUB_FOLDER . $row['Hidden_URL'] . $ext_url;
            } else if ($routing_module_enabled) {
                $nav_url = (string)nc_routing::get_folder_path($row['Subdivision_ID']);
            } else {
                $nav_url = $SUB_FOLDER . $row['Hidden_URL'];
            }
        }

        for ($j = 0; $j < $sub_level_count; $j++) {
            if ($parent_sub_tree[$j]['Subdivision_ID'] == $row['Subdivision_ID']) {
                $is_active_sub = 1;
                break;
            }
        }

        if ($nav_url === $REQUEST_URI || $nav_url === $current_page_path || ($SUB_FOLDER . $row['ExternalURL']) === $current_page_path) {
            $current_template = isset($browse_template['active_link']) ? $browse_template['active_link'] : $browse_template['active'];
        } elseif ($is_active_sub || ($SUB_FOLDER . $row['ExternalURL']) === $current_sub_path) {
            $current_template = $browse_template['active'];
        } else {
            $current_template = $browse_template['unactive'];
        }

        $current_template = str_replace(
            array('%NAME', '%URL', '%PARENT_SUB', '%KEYWORD', '%SUB', '%COUNTER'),
            array($nav_name, $nav_url, $browse_parent_sub, $row['EnglishName'], $row['Subdivision_ID'], $i),
            $current_template
        );

        $current_template = nc_replace_macro_variables($current_template, $data[$i]);

        // Если не делать проверки, будет холостой вызов nc_browse_sub
        if (strpos($current_template, '%NEXT_LEVEL') !== false) {
            $current_template = str_replace('%NEXT_LEVEL', nc_browse_sub($row['Subdivision_ID'], $all_browse_template, $ignore_check, $where_cond, $level + 1), $current_template);
        }
        $array_result[] = $current_template;
    }

    $result .= implode(nc_array_value($browse_template, 'divider'), $array_result);
    $result .= $browse_template['suffix'];

    // cache section
    if ($is_cache_enabled && is_object($nc_cache_browse) && $current_sub['Cache_Access_ID'] == 1 && !isset($browse_template['nocache'])) {
        try {
            $bytes = $nc_cache_browse->add($current_sub, $browse_template, $query_string, $result, $browse_parent_sub);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Written, catalogue[{$current_sub['Catalogue_ID']}], sub[{$current_sub['Subdivision_ID']}], Access_ID[{$current_sub['Cache_Access_ID']}], Lifetime[{$current_sub['Cache_Lifetime']}], bytes[{$bytes}]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, 'ok');
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

function s_browse_level($level, $browse_template) {
    global $parent_sub_tree, $sub_level_count;

    // check php-include vulnerability
    $browse_template = s_browse_template_check($browse_template);

    $level_id = $sub_level_count - $level;
    if ($level_id < 0 || (!isset($parent_sub_tree[$level_id]["Subdivision_ID"]) && $level)) {
        return null;
    }
    $sub = isset($parent_sub_tree[$level_id]["Subdivision_ID"]) ? $parent_sub_tree[$level_id]["Subdivision_ID"] : 0;

    return s_browse_sub($sub, $browse_template);
}

function nc_browse_level($level, $browse_template) {
    global $parent_sub_tree, $sub_level_count;

    $level_id = $sub_level_count - $level;
    if ($level_id < 0 || (!isset($parent_sub_tree[$level_id]["Subdivision_ID"]) && $level)) {
        return null;
    }
    $sub = isset($parent_sub_tree[$level_id]["Subdivision_ID"]) ? $parent_sub_tree[$level_id]["Subdivision_ID"] : 0;

    return nc_browse_sub($sub, $browse_template);
}

function s_browse_cc($browse_template) {
    global $db;
    global $admin_mode, $admin_url_prefix;
    global $current_cc, $current_sub;
    global $cc_in_sub, $cc_array, $cc_keyword, $use_multi_sub_class;
    global $REQUEST_URI, $SUB_FOLDER;
    $nc_core = nc_core::get_object();

    // check php-include vulnerability
    $browse_template = s_browse_template_check($browse_template);

    // this happens when non-existent sub requested in admin mode
    if (!$current_sub["Subdivision_ID"]) {
        return "";
    }

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && $browse_template['nocache'] != true) {
        $nc_cache_browse = nc_cache_browse::getObject();
        try {
            // check cached data
            $cached_data = $nc_cache_browse->read($current_cc, $browse_template, $REQUEST_URI, $current_cc['Cache_Lifetime']);
            if ($cached_data !== -1) {
                // debug info
                $cache_debug_info = "Read, catalogue[" . $current_cc['Catalogue_ID'] . "], sub[" . $current_cc['Subdivision_ID'] . "], cc[" . $current_cc['Sub_Class_ID'] . "], Access_ID[" . $current_cc['Cache_Access_ID'] . "], Lifetime[" . $current_cc['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    $sort_by = nc_array_value($browse_template, 'sortby') ?: '`Priority`';
    $check_cond = !$admin_mode ? " AND `Checked` = 1" : '';

    // cc_in_sub has all templates in sub
    $data = array();

    if ($sort_by == 'Priority' && $GLOBALS['sub'] == $current_sub["Subdivision_ID"]) {
        foreach ((array)$cc_in_sub AS $row) {
            if ($admin_mode || $row["Checked"] == 1) {
                $data[] = $row;
            }
        }
    }

    if (empty($data)) {
        $data = $db->get_results(
            "SELECT `Sub_Class_ID`, `Sub_Class_Name`, `EnglishName`
               FROM `Sub_Class`
              WHERE `Subdivision_ID` = '" . $current_sub["Subdivision_ID"] . "'" .
                    $check_cond .
            " ORDER BY " . $sort_by, ARRAY_A);
    }
    $data_count = count($data);

    if (!$data_count || $data_count < 2) {
        return null;
    }

    $result = '';
    eval(nc_check_eval("\$result.= \"" . $browse_template['prefix'] . "\";"));

    foreach (array('active_link', 'active', 'unactive', 'divider') as $i) {
        if (!empty($browse_template[$i])) {
            $browse_template[$i] = $nc_core->security->php_filter->filter($browse_template[$i], 'string');
        }
    }

    $routing_module_enabled = nc_module_check_by_keyword('routing');
    $current_page_path = urldecode(strtok($REQUEST_URI, '?'));

    for ($i = 0; $i < $data_count; $i++) {
        $nav_name = $data[$i]["Sub_Class_Name"];

        if ($admin_mode) {
            $nav_url = $admin_url_prefix . "?catalogue=" . $current_sub["Catalogue_ID"] . "&amp;sub=" . $current_sub["Subdivision_ID"] . "&amp;cc=" . $data[$i]["Sub_Class_ID"];
        }
        else if ($routing_module_enabled) {
            $nav_url = (string)nc_routing::get_infoblock_path($data[$i]["Sub_Class_ID"]);
        }
        else {
            $nav_url = $SUB_FOLDER .
                       ($i ? $current_sub["Hidden_URL"] . $data[$i]["EnglishName"] . ".html"
                           : $current_sub["Hidden_URL"]);
        }

        if ($data[$i]["Sub_Class_ID"] == $current_cc["Sub_Class_ID"] && ($cc_keyword || !$use_multi_sub_class)) {
            if ($browse_template['active_link'] &&
                ($nav_url == $REQUEST_URI ||
                 $nav_url == $current_page_path ||
                 $SUB_FOLDER . $current_sub['Hidden_URL'] . $current_cc['EnglishName'] . ".html" == $current_page_path))
            {
                $current_template = $browse_template['active_link'];
            }
            else {
                $current_template = $browse_template['active'];
            }
            eval("\$result.= \"" . $current_template . "\";"); // проверка производится до цикла
        }
        else {
            eval("\$result.= \"" . $browse_template['unactive'] . "\";"); // проверка производится до цикла
        }

        $result = str_replace("%NAME", $nav_name, $result);
        $result = str_replace("%URL", $nav_url, $result);

        if ($i != ($data_count - 1)) {
            eval("\$result.= \"" . $browse_template['divider'] . "\";"); // проверка производится до цикла
        }
    }
    eval(nc_check_eval("\$result.= \"" . $browse_template['suffix'] . "\";"));

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && is_object($nc_cache_browse) && $browse_template['nocache'] != true) {
        try {
            $bytes = $nc_cache_browse->add($current_cc, $browse_template, $REQUEST_URI, $result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Written, catalogue[" . $current_cc['Catalogue_ID'] . "], sub[" . $current_cc['Subdivision_ID'] . "], cc[" . $current_cc['Sub_Class_ID'] . "], Access_ID[" . $current_cc['Cache_Access_ID'] . "], Lifetime[" . $current_cc['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

function nc_cond_browse_sub($browse_parent_sub, $browse_template, $where_cond = "") {
    return s_browse_sub($browse_parent_sub, $browse_template, 0, $where_cond);
}

function nc_browse_cc($browse_template) {
    global $db;
    global $admin_mode, $admin_url_prefix;
    global $current_cc, $current_sub;
    global $cc_in_sub, $cc_array, $cc_keyword, $use_multi_sub_class;
    global $REQUEST_URI, $SUB_FOLDER;

    // this happens when non-existent sub requested in admin mode
    if (!$current_sub["Subdivision_ID"]) {
        return "";
    }

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && $browse_template['nocache'] != true) {
        $nc_cache_browse = nc_cache_browse::getObject();
        try {
            // check cached data
            $cached_data = $nc_cache_browse->read($current_cc, $browse_template, $REQUEST_URI, $current_cc['Cache_Lifetime']);
            if ($cached_data !== -1) {
                // debug info
                $cache_debug_info = "Read, catalogue[" . $current_cc['Catalogue_ID'] . "], sub[" . $current_cc['Subdivision_ID'] . "], cc[" . $current_cc['Sub_Class_ID'] . "], Access_ID[" . $current_cc['Cache_Access_ID'] . "], Lifetime[" . $current_cc['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__);
                // return cache
                return $cached_data;
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    $sort_by = nc_array_value($browse_template, 'sortby') ?: '`Priority`';
    $check_cond = !$admin_mode ? " AND `Checked` = 1" : '';

    // cc_in_sub has all templates in sub
    $data = array();

    if (($sort_by == '`Priority`' || $sort_by == 'Priority') && $GLOBALS['sub'] == $current_sub["Subdivision_ID"]) {
        foreach ((array)$cc_in_sub AS $row) {
            if ($admin_mode || $row["Checked"] == 1) {
                $data[] = $row;
            }
        }
    }

    if (empty($data)) {
        $data = $db->get_results(
            "SELECT `Sub_Class_ID`, `Sub_Class_Name`, `EnglishName`
               FROM `Sub_Class`
              WHERE `Subdivision_ID` = '" . $current_sub["Subdivision_ID"] . "'" .
                    $check_cond .
            " ORDER BY " . $sort_by,
            ARRAY_A);
    }
    $data_count = count($data);

    if (!$data_count || $data_count < 2) {
        return null;
    }

    $result = $browse_template['prefix'];
    $array_result = array();

    $routing_module_enabled = nc_module_check_by_keyword('routing');
    $current_page_url = urldecode(strtok($REQUEST_URI, '?'));

    for ($i = 0; $i < $data_count; $i++) {
        $nav_name = $data[$i]["Sub_Class_Name"];

        if ($admin_mode) {
            $nav_url = $admin_url_prefix . "?catalogue=" . $current_sub["Catalogue_ID"] . "&amp;sub=" . $current_sub["Subdivision_ID"] . "&amp;cc=" . $data[$i]["Sub_Class_ID"];
        }
        else if ($routing_module_enabled) {
            $nav_url = (string)nc_routing::get_infoblock_path($data[$i]["Sub_Class_ID"]);
        }
        else {
            $nav_url = $SUB_FOLDER . $current_sub["Hidden_URL"] . $data[$i]["EnglishName"] . ".html";
        }

        if ($data[$i]["Sub_Class_ID"] == $current_cc["Sub_Class_ID"] && ($cc_keyword || !$use_multi_sub_class)) {
            if ($browse_template['active_link'] &&
                ($nav_url == $REQUEST_URI ||
                 $nav_url == $current_page_url ||
                 $SUB_FOLDER . $current_sub['Hidden_URL'] . $current_cc['EnglishName'] . ".html" == $current_page_url)
            ) {
                $current_template = $browse_template['active_link'];
            }
            else {
                $current_template = $browse_template['active'];
            }
            $array_result[$i] = $current_template;
        }
        else {
            $array_result[$i] = $browse_template['unactive'];
        }

        $array_result[$i] = str_replace("%NAME", $nav_name, $array_result[$i]);
        $array_result[$i] = str_replace("%URL", $nav_url, $array_result[$i]);

    }
    $result .= implode($browse_template['divider'], $array_result);
    $result .= $browse_template['suffix'];

    // cache section
    if (nc_module_check_by_keyword("cache") && $current_cc['Cache_Access_ID'] == 1 && is_object($nc_cache_browse) && $browse_template['nocache'] != true) {
        try {
            $bytes = $nc_cache_browse->add($current_cc, $browse_template, $REQUEST_URI, $result);
            // debug info
            if ($bytes) {
                $cache_debug_info = "Written, catalogue[" . $current_cc['Catalogue_ID'] . "], sub[" . $current_cc['Subdivision_ID'] . "], cc[" . $current_cc['Sub_Class_ID'] . "], Access_ID[" . $current_cc['Cache_Access_ID'] . "], Lifetime[" . $current_cc['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                $nc_cache_browse->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
            }
        } catch (Exception $e) {
            // for debug
            $nc_cache_browse->errorMessage($e);
        }
    }

    return $result;
}

/**
 * Функция заменяет переменные вида %VARIABLE в переданном шаблоне данных
 *
 * @param string $template исходный шаблон
 * @param array $data набор данных для замены. Ключ массива - это имя переменной, а значение - это ее содержимое
 * @return mixed $template шаблон с замененными макропеременными или исходный шаблон, в случае ошибки
 */
function nc_replace_macro_variables($template, array $data) {
    if (!preg_match('/%\w/', $template)) {
        return $template;
    }

    uksort($data, function($a, $b){
        return strlen($b) - strlen($a);
    });

    foreach ($data as $key => $value) {
        $replacement = nc_quote_convert($value);
        if (is_array($replacement)) {
            $replacement = implode(', ', $replacement);
        }
        $template = str_replace('%' . $key, $replacement, $template);
    }

    return $template;
}