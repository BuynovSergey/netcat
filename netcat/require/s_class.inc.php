<?

function opt($flag, $output) {
    return $flag ? $output : null;
}

function opt_case($flag, $output1, $output2 = "") {
    return $flag ? $output1 : $output2;
}

function is_even($input) {
    if (round($input / 2) == $input / 2) {
        return 1;
    }
    else {
        return 0;
    }
}

/**
 * формирует листинг страниц с объектами
 *
 * @param array $cc_env переменные окружения сс
 * @param int $range количество выводимых страниц
 * @param array|bool $user_template
 *
 * @global $browse_msg, $classPreview
 *
 * Примечание: массив-шаблон $browse_msg должен быть определен
 *
 * @return string html-текст с листингом
 */
function browse_messages($cc_env, $range, $user_template = false) {
    // В сквозных блоках номера страниц не выводятся
    if (isset($cc_env['Subdivision_ID']) && !$cc_env['Subdivision_ID']) {
        return '';
    }

    $env_cur_cc = nc_array_value($cc_env, 'cur_cc');
    $condition_offset = (int)nc_array_value($cc_env, 'ConditionOffset', 0);

    global $classPreview, $admin_mode, $inside_admin;

    if ($user_template) {
        $browse_msg = $user_template;
    }
    else {
        global $browse_msg;
    }

    // system superior object
    $nc_core = nc_Core::get_object();
    if (isset($classPreview)) {
        $classPreview += 0;
    }

    if ($env_cur_cc == $nc_core->input->fetch_get("cur_cc")) {
        $curPos = $cc_env['curPos'] + 0;
    }
    else {
        $curPos = 0;
    }

    $maxRows = $cc_env['maxRows'];
    $totRows = $cc_env['totRows'] - $condition_offset;

    if ($env_cur_cc) {
        $cur_cc = $env_cur_cc;
    }

    if (!$maxRows || !$totRows) {
        return '';
    }

    $page_count = ceil($totRows / $maxRows);
    $half_range = ceil($range / 2);
    $cur_page = ceil($curPos / $maxRows) + 1;

    if ($page_count < 2) {
        return '';
    }

    $maybe_from = $cur_page - $half_range;
    $maybe_to = $cur_page + $half_range - (is_even($range) ? 0 : 1);

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

    // формируем ссылку
    // const_url не меняется для каждой страницы
    $const_url = $cc_env['LocalQuery'];
    if ($const_url == '?') {
        $const_url = '';
    }

    //$const_url = rawurlencode ($const_url);

    $use_routing_module = !$admin_mode && nc_module_check_by_keyword('routing');

    // добавим get-парметры
    $_get_arr = $nc_core->input->fetch_get();
    $get_params = array();
    // добавим в ссылку cur_cc
    if (isset($cur_cc)) { $get_params['cur_cc'] = $cur_cc; }

    if (!empty($_get_arr)) {
        $ignore_arr = array('sid', 'ced', 'inside_admin', 'catalogue', 'sub', 'cc', 'curPos', 'cur_cc', 'REQUEST_URI');
        if ($inside_admin || $admin_mode) {
            $ignore_arr[] = 'isNaked';
        }
        if (!$use_routing_module && $const_url) {
            $ignore_arr[] = 'srchPat';
        }
        foreach ($_get_arr as $k => $v) {
            if (!in_array($k, $ignore_arr)) {
                $get_params[$k] = $nc_core->input->recursive_striptags_escape($v);
            }
        }
    }

    if ($inside_admin) {
        $get_params['inside_admin'] = 1;
    }

    $get_params_cur_cc = nc_array_value($get_params, 'cur_cc');
    unset($get_params['cur_cc']);

    $const_url .= count($get_params)
        ? (strpos($const_url, '?') !== false ? '&' : '?') . $nc_core->url->build_url($get_params)
        : "";
    $const_url_separator = strpos($const_url, '?') !== false ? '&' : '?';

    if ($use_routing_module) {
        $routing_parameters = $nc_core->page->get_routing_result();
        $routing_parameters['variables'] = array_merge(
            nc_array_value($routing_parameters, 'variables', array()),
            $get_params,
            array('curPos' => null, 'nc_page' => null)
        );
        $routing_parameters['page'] = null;
        $routing_resource_type = $routing_parameters['resource_type'];
        if ($routing_resource_type === 'folder' && $routing_parameters['infoblock_id']) {
            $routing_resource_type = 'infoblock';
        }
    }
    else {
        $use_routing_module = $routing_parameters = $routing_resource_type = false;
    }

    // prefix
    $result = '';
    eval(nc_check_eval("\$result = \"" . $browse_msg['prefix'] . "\";"));

    $url = $use_routing_module
        ? $url = nc_routing::get_resource_path($routing_resource_type, $routing_parameters)
        : $nc_core->url->get_parsed_url('path') . $const_url;

    $result = str_replace("%URL", $url, $result);
    $result = str_replace("%FIRST", "1", $result);

    for ($i = $maybe_from; $i < $maybe_to; $i++) {
        $page_number = $i + 1;
        $page_from = $i * $maxRows;
        $page_to = $page_from + $maxRows;

        if ($use_routing_module) {
            $routing_parameters['variables']['curPos'] = ($page_from ?: null);
            $routing_parameters['variables']['cur_cc'] = ($page_number > 1 ? $get_params_cur_cc : null);
            $routing_parameters['page'] = ($page_number > 1 ? $page_number : null);
            $url = nc_routing::get_resource_path($routing_resource_type, $routing_parameters);
        } else {
            $query_cur =
                $const_url_separator . 'curPos=' . $page_from .
                ($get_params_cur_cc ? '&cur_cc=' . $get_params_cur_cc : '');
            if ($page_from && !$admin_mode) { // ссылка не на первую страницу
                $url = $nc_core->SUB_FOLDER . $nc_core->url->get_parsed_url('path') . $const_url . $query_cur;
            } elseif ($page_from && $admin_mode) {
                $url = $const_url . $query_cur;
            } else { // ссылка на первую страницу, curPos и cur_cc не нужен
                $url = $const_url ?: $nc_core->url->get_parsed_url('path');
            }
        }


        // clear already existance &amp; and replace all & to &amp; view
        $url = nc_preg_replace(array("/&amp;/", "/&/"), array("&", "&amp;"), $url);

        if ($curPos == $page_from) {
            eval(nc_check_eval("\$result .= \"" . $browse_msg['active'] . "\";"));
        }
        else {
            eval(nc_check_eval("\$result .= \"" . $browse_msg['unactive'] . "\";"));
        }

        $result = str_replace("%URL", $url, $result);
        $result = str_replace("%PAGE", $page_number, $result);
        $result = str_replace("%FROM", $page_from + 1, $result);
        $result = str_replace("%TO", $page_to, $result);

        if ($i != ($maybe_to - 1)) {
            eval(nc_check_eval("\$result .= \"" . $browse_msg['divider'] . "\";"));
        }
    }

    eval(nc_check_eval("\$result .= \"" . $browse_msg['suffix'] . "\";"));

    $last = $maxRows * ($page_count - 1);
    if ($use_routing_module) {
        $routing_parameters['variables']['curPos'] = $last;
        $routing_parameters['page'] = $page_count;
        $url = nc_routing::get_resource_path($routing_resource_type, $routing_parameters);
    }
    elseif (!$admin_mode) {
        $url = $nc_core->url->get_parsed_url('path') . $const_url . (strpos($const_url, "?") !== false ? "&" : "?") . "curPos=" . $last;
    }
    else {
        $url = $const_url . (strpos($const_url, "?") !== false ? "&" : "?") . "curPos=" . $last;
    }
    $result = str_replace("%URL", $url, $result);
    $result = str_replace("%LAST", $page_count, $result);

    return $result;
}

function nc_browse_messages($cc_env, $range, $user_template = false) {
    // В сквозных блоках номера страниц не выводятся
    if (isset($cc_env['Subdivision_ID']) && !$cc_env['Subdivision_ID']) {
        return '';
    }

    $env_cur_cc = nc_array_value($cc_env, 'cur_cc');
    $condition_offset = (int)nc_array_value($cc_env, 'ConditionOffset', 0);

    if ($user_template) {
        $browse_msg = $user_template;
    }
    else {
        global $browse_msg;
    }

    global $classPreview, $admin_mode, $inside_admin;
    $nc_core = nc_Core::get_object();

    if (isset($classPreview)) {
        $classPreview += 0;
    }

    if ($env_cur_cc == $nc_core->input->fetch_get("cur_cc")) {
        $curPos = $cc_env['curPos'] + 0;
    }
    else {
        $curPos = 0;
    }

    $maxRows = $cc_env['maxRows'];
    $totRows = $cc_env['totRows'] - $condition_offset;

    if (!$maxRows || !$totRows) {
        return '';
    }

    if ($env_cur_cc) {
        $cur_cc = $env_cur_cc;
    }

    $page_count = ceil($totRows / $maxRows);
    $half_range = ceil($range / 2);
    $cur_page = ceil($curPos / $maxRows) + 1;

    if ($page_count < 2) {
        return '';
    }

    $maybe_from = $cur_page - $half_range;
    $maybe_to = $cur_page + $half_range - (is_even($range) ? 0 : 1);

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

    // формируем ссылку
    // const_url не меняется для каждой страницы
    $const_url = $cc_env['LocalQuery'];
    if ($const_url == '?') {
        $const_url = '';
    }

    //$const_url = rawurlencode ($const_url);

    $use_routing_module = !$admin_mode && nc_module_check_by_keyword('routing');

    // добавим get-парметры
    $_get_arr = $nc_core->input->fetch_get();
    $get_params = array();

    // добавим в ссылку cur_cc
    if (isset($cur_cc)) { $get_params['cur_cc'] = $cur_cc; }

    if (!empty($_get_arr)) {
        $ignore_arr = array('sid', 'ced', 'inside_admin', 'catalogue', 'sub', 'cc', 'curPos', 'cur_cc', 'REQUEST_URI');
        if ($inside_admin || $admin_mode) {
            $ignore_arr[] = 'isNaked';
        }
        if (!$use_routing_module && $const_url) {
            $ignore_arr[] = 'srchPat';
        }
        foreach ($_get_arr as $k => $v) {
            if (!in_array($k, $ignore_arr)) {
                $get_params[$k] = $nc_core->input->recursive_striptags_escape($v);
            }
        }
    }

    if ($inside_admin) {
        $get_params['inside_admin'] = 1;
    }

    $get_params_cur_cc = nc_array_value($get_params, 'cur_cc');
    unset($get_params['cur_cc']);

    $const_url .= count($get_params)
        ? (strpos($const_url, '?') !== false ? '&' : '?') . $nc_core->url->build_url($get_params)
        : "";
    $const_url_separator = strpos($const_url, '?') !== false ? '&' : '?';

    if ($use_routing_module) {
        $routing_parameters = $nc_core->page->get_routing_result();
        $routing_parameters['variables'] = array_merge(
            nc_array_value($routing_parameters, 'variables', array()),
            $get_params,
            array('curPos' => null, 'nc_page' => null)
        );
        $routing_parameters['page'] = null;
        $routing_resource_type = $routing_parameters['resource_type'];
        if ($routing_resource_type === 'folder' && $routing_parameters['infoblock_id']) {
            $routing_resource_type = 'infoblock';
        }
    }
    else {
        $use_routing_module = $routing_parameters = $routing_resource_type = false;
    }

    $array_result = array();

    if ($cc_env['prevLink'] && isset($browse_msg['prev'])) {
        $array_result[] = str_replace('%URL', $cc_env['prevLink'], $browse_msg['prev']);
    } else if (!$cc_env['prevLink'] && isset($browse_msg['prev_none'])) {
        $array_result[] = $browse_msg['prev_none'];
    }

    for ($i = $maybe_from; $i < $maybe_to; $i++) {
        $page_number = $i + 1;
        $page_from = $i * $maxRows;
        $page_to = $page_from + $maxRows;

        if ($use_routing_module) {
            $routing_parameters['variables']['curPos'] = ($page_from ?: null);
            $routing_parameters['variables']['cur_cc'] = ($page_number > 1 ? $get_params_cur_cc : null);
            $routing_parameters['page'] = ($page_number > 1 ? $page_number : null);
            $url = nc_routing::get_resource_path($routing_resource_type, $routing_parameters);
        } else {
            $query_cur =
                $const_url_separator . 'curPos=' . $page_from .
                ($get_params_cur_cc ? '&cur_cc=' . $get_params_cur_cc : '');
            if ($page_from && !$admin_mode) { // ссылка не на первую страницу
                $url = $nc_core->SUB_FOLDER . $nc_core->url->get_parsed_url('path') . $const_url . $query_cur;
            } elseif ($page_from && $admin_mode) {
                $url = $const_url . $query_cur;
            } else { // ссылка на первую страницу, curPos и cur_cc не нужен
                $url = $const_url ?: $nc_core->url->get_parsed_url('path');
            }
        }

        // replace existing &amp; with & then replace all & to &amp;
        $url = nc_preg_replace(array("/&amp;/", "/&/"), array("&", "&amp;"), $url);

        $array_result[] = strtr(
            $curPos == $page_from ? $browse_msg['active'] : $browse_msg['unactive'],
            array(
                '%URL' => $url,
                '%PAGE' => $page_number,
                '%FROM' => $page_from + 1,
                '%TO' => $page_to,
            )
        );
    }

    if ($cc_env['nextLink'] && isset($browse_msg['next'])) {
        $array_result[] = str_replace('%URL', $cc_env['nextLink'], $browse_msg['next']);
    } else if (!$cc_env['nextLink'] && isset($browse_msg['next_none'])) {
        $array_result[] = $browse_msg['next_none'];
    }

    $result = $browse_msg['prefix'];
    $result .= join($browse_msg['divider'], $array_result);
    $result .= $browse_msg['suffix'];

    $result = str_replace(array("%FIRST", "%LAST"), array("1", $page_count), $result);

    return $result;
}


function parentofmessage($message, $classID) {
    global $db;

    $parent = $message;
    $classID = intval($classID);
    $parent = intval($parent);
    $old_parent = null;

    while ($parent) {
        $old_parent = $parent;
        $parent = $db->get_var("SELECT Parent_Message_ID FROM Message" . $classID . " WHERE Message_ID='" . $parent . "'");
    }

    return $old_parent;
}
