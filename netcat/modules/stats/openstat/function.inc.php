<?php

/**
 * Проставить счетчики на указанные макеты дизайна
 * @param bool ставить ли в xml и пустые макеты, 0 (по умолчанию) - не ставить
 * @param mixed массив ID макетов дизайна
 * @return int 0 в случае успеха
 */
function nc_openstat_put_counter_to_templates($check_xml = 0, $put_templ_ids=NULL) {

    global $db, $nc_core;


    if (!$put_templ_ids) {
        $templates = $db->get_results("SELECT `Template_ID`, `Parent_Template_ID`, `Header`, `Footer`, `File_Mode`, `File_Path` FROM `Template`WHERE `Template_ID`<>'".$nc_core->get_settings('EditDesignTemplateID')."'");
    } else {

        $where_in = implode("', '", $put_templ_ids);
        $where_in = "'".$where_in."'";

        $templates = $db->get_results("SELECT `Template_ID`, `Parent_Template_ID`, `Header`, `Footer`, `File_Mode`, `File_Path` FROM `Template` WHERE `Template_ID` IN (".$where_in.")");
    }

    if (!$templates) {
        return 1;
    }

    foreach ($templates as $templ) {
    	if ($templ->File_Mode) {
    		$templ->Header = file_get_contents($nc_core->TEMPLATE_FOLDER.$templ->File_Path.'/Header.html');
    		$templ->Footer = file_get_contents($nc_core->TEMPLATE_FOLDER.$templ->File_Path.'/Footer.html');
    	}

        if (!$check_xml) {  // считаем, что в пустых xml макетах все и так хорошо и счетчик не нужен
            if (nc_strpos($templ->Header, "<?xml") !== FALSE) {
                continue;
            }

            if (($templ->Footer == '') && ($templ->Header == '') && ($templ->Parent_Template_ID == 0)) {
                continue;
            }
        }

        if (!(($templ->Footer == '') && ($templ->Parent_Template_ID != 0))) { // если есть родитель и футер пустой, то он же наследуется
            if (nc_strpos($templ->Footer, NC_OPENSTAT_COUNTER) === FALSE) {
                if (nc_preg_match_all("#[ \t]*<[ \t]*\/[ \t]*body[ \t]*>#i", $templ->Footer, $matches, PREG_OFFSET_CAPTURE)) {  //ищем </body>
                    $body_pos = $matches[0][count($matches[0]) - 1][1];  // позиция последнего тега </body>
                    $new_footer = substr_replace($templ->Footer, NC_OPENSTAT_COUNTER."\n", $body_pos, 0);  // вставка подстроки
                } elseif (nc_preg_match_all("#[ \t]*<[ \t]*\/[ \t]*html[ \t]*>#i", $templ->Footer, $matches, PREG_OFFSET_CAPTURE)) {  //ищем </html>
                    $body_pos = $matches[0][count($matches[0]) - 1][1];  // позиция последнего тега </html>
                    $new_footer = substr_replace($templ->Footer, NC_OPENSTAT_COUNTER."\n", $body_pos, 0);  // вставка подстроки
                } else {  // на худой конец вставляем просто в конец футеры
                    $new_footer = $templ->Footer."\n".NC_OPENSTAT_COUNTER;
                }

                if ($templ->File_Mode) {
                	$res = file_put_contents($nc_core->TEMPLATE_FOLDER.$templ->File_Path.'/Footer.html', $new_footer);
                	if (false === $res) {
                		return 1;
                	}
                } else {
                    $db->query("UPDATE `Template` SET `Footer`=\"".$db->prepare($new_footer)."\" WHERE `Template_ID`='".$templ->Template_ID."'");
                }
            }
        } else {
            if (nc_openstat_check_counter_in_templates_sub_check($templ->Template_ID, $tmp, $check_xml)) { // если в родительских нет счетчика
                $new_footer = NC_OPENSTAT_COUNTER."\n%Footer";
                
                if ($templ->File_Mode) {
                	$res = file_put_contents($nc_core->TEMPLATE_FOLDER.$templ->File_Path.'/Footer.html', $new_footer);
                	if (false === $res) {
                		return 1;
                	}
                } else {
                	$db->query("UPDATE `Template` SET `Footer`=\"".$db->prepare($new_footer)."\" WHERE `Template_ID`='".$templ->Template_ID."'");
                }                
            }
        }
    }
    return 0;
}

/**
 * Убрать счетчики с указанных макетов дизайна
 * @param mixed массив ID макетов дизайна
 * @return int 0 в случае успеха
 */
function nc_openstat_delete_counter_from_templates($put_templ_ids=0) {

    global $db, $nc_core;

    if (!$put_templ_ids) {
        $templates = $db->get_results("SELECT `Template_ID`, `Footer` FROM `Template` WHERE `Template_ID`<>'".$nc_core->get_settings('EditDesignTemplateID')."'");
    } else {

        $where_in = implode("', '", $put_templ_ids);
        $where_in = "'".$where_in."'";

        $templates = $db->get_results("SELECT `Template_ID`, `Footer` FROM `Template` WHERE `Template_ID` IN (".$where_in.") AND `Template_ID`<>'".$nc_core->get_settings('EditDesignTemplateID')."'");
    }
    if (!$templates) {
        return 1;
    }

    foreach ($templates as $templ) {
        if (nc_strpos($templ->Footer, NC_OPENSTAT_COUNTER) !== FALSE) {
            $new_footer = str_replace('\n'.NC_OPENSTAT_COUNTER, "", $templ->Footer);
            $new_footer = str_replace(NC_OPENSTAT_COUNTER.'\n', "", $templ->Footer);
            $new_footer = str_replace(NC_OPENSTAT_COUNTER, "", $templ->Footer);
            $db->query("UPDATE `Template` SET `Footer`=\"".$db->prepare($new_footer)."\" WHERE `Template_ID`='".$templ->Template_ID."'");
        }
    }
    return 0;
}

//рекурсивная
function nc_openstat_check_counter_in_templates_sub_check($el, &$templ = NULL, $check_xml=0) {
    global $nc_core, $db;

    if ($el == 0) {
        return 0;
    }
    if (!$templ) {
        $templates = $db->get_results("SELECT `Template_ID`, `Parent_Template_ID`, `Header`, `Footer` FROM `Template`");
        foreach ($templates as $t) {
            $templ[$t->Template_ID] = $t;
        }
    }

    if (!$check_xml) {  // считаем, что в пустых xml макетах все и так хорошо и счетчик не нужен
        if (nc_strpos($templ[$el]->Header, "<?xml") !== FALSE) {
            return 0;
        }

        if (($templ[$el]->Footer == '') && ($templ[$el]->Header == '') && ($templ[$el]->Parent_Template_ID == 0)) {
            return 0;
        }
    }


    if (!(($templ[$el]->Footer == '') && ($templ[$el]->Parent_Template_ID != 0))) { // если есть родитель и футер пустой, то он же наследуется
        return (nc_strpos($templ[$el]->Footer, NC_OPENSTAT_COUNTER) === FALSE);
    } elseif (!(($templ[$el]->Header == '') && ($templ[$el]->Parent_Template_ID != 0))) { // если есть родитель и футер пустой, то он же наследуется
        return (nc_strpos($templ[$el]->Header, NC_OPENSTAT_COUNTER) === FALSE);
    } else {
        return nc_openstat_check_counter_in_templates_sub_check($templ[$el]->Parent_Template_ID, $templ);
    }
}

/**
 * Проверить наличие счетчиков на всех макетах дизайна
 * @param bool флаг, проверять ли счетчик в пустых и xml макетах, 0 (по умолчанию) - не проверять
 * @param bool флаг, 1 - возвращать макеты с установленными счетчиками, 0 - с неустанговленными
 * @return mixed массив ID макетов дизайна, где не стоят счетчики, макет "Редактирование объектов" не учитывается
 */
function nc_openstat_check_counter_in_templates($check_xml = 0, $neg=0) {

    global $db, $nc_core;

    $templates = $db->get_results("SELECT `Template_ID`, `Parent_Template_ID`, `Header`, `Footer` FROM `Template` WHERE `Template_ID` <> '".$nc_core->get_settings('EditDesignTemplateID')."'");
    $result = null;
    if (!$templates) {
        return $result;
    }

    foreach ($templates as $templ) {
        $templates1[$templ->Template_ID] = $templ;
    }

    foreach ($templates1 as $el => $val) {
        if (nc_openstat_check_counter_in_templates_sub_check($el, $templates1, $check_xml) xor $neg) {
            $result[$el] = $el;
        }
    }
    return $result;
}

/**
 * Возвращает код счетчика для текущего сайта
 * @return string код счетчика
 */
function nc_openstat_get_code($fs = 0) {

    global $nc_core, $db, $current_catalogue;
    static $nc_openstat_already_set;

    if (!$nc_openstat_already_set && ($nc_core->get_settings('Openstat_Enabled', 'stats') == 1)) {
        $nc_openstat_already_set = 1;

        $counter_code = $db->get_var("SELECT `Counter_Code` FROM `Stats_Openstat_Counters` WHERE `Catalogue_Id`='".$current_catalogue["Catalogue_ID"]."' OR `Catalogue_Id`='0' LIMIT 1");
        if ($counter_code) {
            return $fs ? $counter_code : addslashes($counter_code);
        }
    }
}

/**
 * Регистрация нового пользователя на Openstat
 */
function nc_openstat_register_user(&$code = 0) {

    global $db;

    // посмотреть в бд, может уже есть пароль

    $product_number = $db->get_var("SELECT `Value` FROM `Settings` WHERE `Key` = 'ProductNumber'");
    $code = $db->get_var("SELECT `Value` FROM `Settings` WHERE `Key` = 'Code'");

    if (!$product_number || !$code) {
        //todo: не введены номер лицензии или ключ
        return NULL;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // возвращать результат работы
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
        curl_setopt($ch, CURLOPT_URL, "http://openstat.netcat.ru/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            'ProductNumber='.urlencode($product_number).'&Code='.urlencode($code));

        // выполнить запрос
        curl_exec($ch);

        $result = curl_multi_getcontent($ch);
        $responce_header = curl_getinfo($ch);
        curl_close($ch);
    } else {
        $result = null;
        $responce_header = array();
    }

    $code = $responce_header["http_code"];

    if ($code == 200) {
        return json_decode($result);
    } else {
        return 0;
    }
}