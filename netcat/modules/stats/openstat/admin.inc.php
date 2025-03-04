<?php

include_once nc_module_folder('stats') . 'openstat/function.inc.php';
include_once nc_module_folder('stats') . 'openstat/openstat_core_class.php';

/**
 * Рукурсивная функция рисует макет
 *
 * @param нулевой индекс $ParentTemplateID
 * @param массив шаблонов с неустановленными счетчиками
 */
function write_template($ParentTemplateID, &$NoCounterTemplates, $count = 0) {
    global $db, $nc_core;
    global $HTTP_DOMAIN, $HTTP_ROOT_PATH, $ADMIN_PATH, $ADMIN_TEMPLATE;
    $ParentTemplateID = intval($ParentTemplateID);

    $Result = $db->get_results("SELECT Template_ID,Description,File_Mode FROM `Template` where `Parent_Template_ID`='".$ParentTemplateID."' AND `Template_ID`<>'".$nc_core->get_settings('EditDesignTemplateID')."' ORDER BY Priority, Template_ID", ARRAY_N);
    if ($Result) {
        foreach ($Result as $Array) {
        	$fs = $Array[2] == 1 ? '_fs' : '';
            $res.= "<table cellpadding='0' cellspacing='0' class='templateMap'>";
            $res.= "<tr>\n
            <td class='withBorder' width='60px' align='center'><input type='checkbox' name='Templ[".$Array[0]."]'".($NoCounterTemplates[$Array[0]] ? "" : " checked")."></td>
            <td class='withBorder' style='padding-left:".intval($count * ($count == 1 ? 15 : 20) + 5)."px;".(!$ParentTemplateID ? " font-weight: bold;" : "")."'>".($ParentTemplateID ? "<img src='".$ADMIN_PATH."images/arrow_sec.gif' border='0' width='14' height='10' alt='arrow' title='".$Array[0]."'>" : "")."<span>".$Array[0].". </span><a target='_blank' href='".$ADMIN_PATH."/#template".$fs.".edit(".$Array[0].")'><font  ".($NoCounterTemplates[$Array[0]] ? "color='#cccccc'" : "").">".$Array[1]."</font></a></td>";
            $res.= "</tr>";
            $res.= "</table>";
            // children
            $res.= write_template($Array[0], $NoCounterTemplates, $count + 1);
        }
    }

    return $res;
}

function check_counters() {

    global $nc_core, $counters, $sub_view;


    if ($nc_core->get_settings('Openstat_Enabled', 'stats') != 1) {
        nc_print_status(NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_DISABLED, "error");
        echo "<br />";
        return false;
    }

    if (($sub_view != 'counters') && (!$counters)) {
        if (!isset($_GET['catalog_page'])) {
            nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_NO_COUNTERS.
                    " <a style='vertical-align: top;' href=\"?sub_view=counters&phase=1&default_templ_id=0\">".NETCAT_MODULE_STATS_OPENSTAT_ADD_COUNTER."</a>", "info");
        } else {
            nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_NO_COUNTER_IN_CATALOGUE.
                    " <a style='vertical-align: top;' href=\"?sub_view=counters&phase=1&default_templ_id=".intval($_GET['catalog_page'])."\">".NETCAT_MODULE_STATS_OPENSTAT_ADD_COUNTER."</a>", "info");
        }
        return false;
    }

    if (($sub_view != 'templates') && nc_openstat_check_counter_in_templates()) {
        nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_NO_COUNTERS_IN_TEMPL, "info");
        echo "<br />";
    }

    return true;
}

function add_api_counter() {

    global $nc_core, $db;
    global $counters, $default_templ_id, $UI_CONFIG;

    global $CounterCatalogueId, $CounterRating, $CounterAdvert,
    $CounterColor, $CounterPicture, $CounterTrackLinks, $CounterInAllTempl,
    $OpenstatRules, $counter_id;

    if (isset($_POST['CounterCatalogueId'])) {  // непосредственно добавление
        $counter_id = intval($counter_id);

        try {

            // регистрация нового юзера опенстат
            if (!$nc_core->get_settings('Openstat_Login', 'stats')) {
                if (!isset($_POST['OpenstatRules'])) {
                    throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_LICENSE_NOT_AGREE);
                }
                $user = nc_openstat_register_user($err_code);
                if (!is_object($user)) {
                    if ($user === NULL) {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_NO_PRODUCT_NUMBER);  // не отослали № лицензии или ключ
                    } elseif ($err_code != 200) {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_NO_CONN_WITH_NETCAT.$err_code);  //неткэт упал?? О_о
                    } elseif ($user == 0) {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_INVALID_PRODUCT_NUMBER);  // пират (неверная пара № лицензии-ключ)!
                    } elseif ($user == 409) {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_USER_ALREADY_REGISTERED); // уже зареген
                    } else {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_NEW_USER_UNKNOWN_ERROR.$user);  // просто ошибка, походу от сервера Openstat, выводим $result - ее код
                    }
                }
                $nc_core->set_settings("Openstat_Login", $user->login, 'stats');
                $nc_core->set_settings("Openstat_Password", $user->password, 'stats');
            }


            $counter_params['site_url'] = nc_get_scheme() . '://' . $_SERVER['SERVER_NAME'];
            if ($CounterCatalogueId) {
                $counter_catalogue_domain = $nc_core->catalogue->get_by_id($CounterCatalogueId, 'Domain');
                if ($counter_catalogue_domain) {
                    $counter_params['site_url'] = $nc_core->catalogue->get_scheme_by_id($CounterCatalogueId) . '://' . $counter_catalogue_domain;
                }
            }
            $counter_params['title'] = ($CounterCatalogueId ? $nc_core->catalogue->get_by_id($CounterCatalogueId, 'Catalogue_Name') : $nc_core->get_settings("ProjectName", 'system'));
            $counter_params['title'] = ($nc_core->NC_UNICODE ? $counter_params['title'] : $nc_core->utf8->win2utf($counter_params['title']));
            $counter_params['description'] = '';
            $counter_params['participates_in_rating'] = (isset($CounterRating) ? "true" : "false");
            $counter_params['is_advert_publisher'] = (isset($CounterAdvert) ? "true" : "false");

            $openstat = new nc_Openstat_core_class($nc_core->get_settings('Openstat_Login', 'stats'), $nc_core->get_settings('Openstat_Password', 'stats'));
            if (!$counter_id) {  // добавление
                $openstat_counter_id = $openstat->make_counter($counter_params, $err_code);

                if (!$openstat_counter_id) {
                    if ($err_code == 401) {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_COUNTER_AUTH_ERROR);  // 401 ошибка - неверный логин-пароль
                    } else {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_NEW_COUNTER_UNKNOWN_ERROR.$err_code);  // ошибка, выводим ее код
                    }
                }
            } else {  //изменение
                $openstat_counter_id = $counters[$counter_id]->Openstat_Counter_Id;
                $openstat->change_counter($openstat_counter_id, $counter_params, $err_code);

                if ($err_code != 204) {
                    if ($err_code == 401) {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_COUNTER_AUTH_ERROR);  // 401 ошибка - неверный логин-пароль
                    } elseif ($err_code == 404) {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_INVALID_OPENSTAT_COUNTER_ID);  // 404 ошибка - нет такого счетчика
                    } else {
                        throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_EDIT_COUNTER_UNKNOWN_ERROR.$err_code);  // ошибка, выводим ее код
                    }
                }
            }

            $counter_visual_params['color'] = $CounterColor + 0;
            $counter_visual_params['picture'] = $CounterPicture + 0;
            $counter_visual_params['track_links'] = ($CounterTrackLinks ? $CounterTrackLinks : 'none');

            $counter_code = $openstat->get_counter_code($openstat_counter_id, $counter_visual_params, $err_code);

            if (!$counter_code) {
                throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_NEW_CODE_UNKNOWN_ERROR.$err_code);  // ошибка, выводим ее код
            }

            if (!$counter_id) {  // добавление
                $db->query("INSERT INTO `Stats_Openstat_Counters` (`Openstat_Counter_Id`, `Title`, `Rating`, `Advert`, `Color`, `Size`, `TrackLinks`, `Counter_Code`, `User_Counter_Code`, `Catalogue_Id`, `LastUpdated`, `Created`) VALUES ('".
                        $db->escape($openstat_counter_id)."', '".
                        $db->escape($counter_params['title'])."', '".
                        ($counter_params['participates_in_rating'] == 'true' ? '1' : '0')."', '".
                        ($counter_params['is_advert_publisher'] == 'true' ? '1' : '0')."', '".
                        $db->escape($counter_visual_params['color'])."', '".
                        $db->escape($counter_visual_params['picture'])."', '".
                        $db->escape($counter_visual_params['track_links'])."', '".
                        $db->prepare($counter_code)."', '".
                        "0', '".
                        $db->escape($CounterCatalogueId ? $CounterCatalogueId : '0')."', '".
                        date("Y-m-d H:i:s")."', '".
                        date("Y-m-d H:i:s")."')");
            } else {  //изменение
                $db->query("UPDATE `Stats_Openstat_Counters` SET ".
                        "`Openstat_Counter_Id` = '".$db->escape($openstat_counter_id).
                        "', `Title` = '".$db->escape($counter_params['title']).
                        "', `Rating` = '".($counter_params['participates_in_rating'] == 'true' ? '1' : '0').
                        "', `Advert` = '".($counter_params['is_advert_publisher'] == 'true' ? '1' : '0').
                        "', `Color` = '".$db->escape($counter_visual_params['color']).
                        "', `Size` = '".$db->escape($counter_visual_params['picture']).
                        "', `TrackLinks` = '".$db->escape($counter_visual_params['track_links']).
                        "', `Counter_Code` = '".$db->prepare($counter_code).
                        "', `User_Counter_Code` = '0".
                        "', `Catalogue_Id` = '".$db->escape($CounterCatalogueId ? $CounterCatalogueId : '0').
                        "', `LastUpdated` = '".date("Y-m-d H:i:s").
                        "' WHERE `Counter_Id` = '".$counter_id."'");
            }

            if (!$db->rows_affected) {
                $openstat->delete_counter($counter_id);
                throw new Exception(NETCAT_MODULE_STATS_DB_INSERT_ERR);
            }

            $new_counter_id = $db->insert_id;

            if ($CounterInAllTempl) {
                nc_openstat_put_counter_to_templates();
            }

            if ($counter_id) {
                nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
            } else {
                nc_print_status(str_replace("%PASE_ID", $new_counter_id, NETCAT_MODULE_STATS_OPENSTAT_API_COUNTER_CREATED), "ok");
            }

            return false;
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), "error");
        }
    }


    // ------------------------- форма добавления ----------------------------

    echo "<form name='addCounterForm' id='addCounterForm' method='post' action='?sub_view=counters&phase=1'>\n";

    if ($counter_id) {
        echo "<input type='hidden' name='counter_id' value='".$counter_id."'/>\n";
    }

    if (!$sites_ddlist = show_sites_without_counter_ddlist($counters, ($default_templ_id ? $default_templ_id : $CounterCatalogueId))) {
        nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_ERROR_ALREADY_COUNTERS_FOR_ALL_SITES, "error");
    } else {

        echo $sites_ddlist;

        $field_display = new nc_admin_fieldset(NETCAT_MODULE_STATS_OPENSTAT_DISPLAY_SETTINGS);
        // выбор цвета счетчика
        $field_display->add("<p>".NETCAT_MODULE_STATS_OPENSTAT_COUNTER_COLOR."<br />
            <select name='CounterColor'>");
        for ($i = 0; $i < NETCAT_MODULE_STATS_OPENSTAT_COUNTER_COLOR_COUNT; $i++) {
            $field_display->add("<option value='".$i."'".($i == $CounterColor ? " selected" : "").">".constant("NETCAT_MODULE_STATS_OPENSTAT_COUNTER_COLOR_".$i)."</option>\n");
        }
        $field_display->add("</select></p>\n");

        // выбор типа (размера) картинки счетчика
        $field_display->add("<p>".NETCAT_MODULE_STATS_OPENSTAT_COUNTER_PICTURE_TYPE."<br />
            <select name='CounterPicture'>");
        for ($i = 1; $i <= NETCAT_MODULE_STATS_OPENSTAT_COUNTER_PICTURE_TYPE_COUNT; $i++) {
            $field_display->add("<option value='".$i."'".($i == $CounterPicture ? " selected" : "").">".constant("NETCAT_MODULE_STATS_OPENSTAT_COUNTER_PICTURE_TYPE_".$i)."</option>\n");
        }
        $field_display->add("</select></p>\n");

        // посчёт уходов
        $field_display->add("<p>".NETCAT_MODULE_STATS_OPENSTAT_COUNTER_TRACK_LINKS."<br />
            <select name='CounterTrackLinks'>".
                "<option value='none'".($CounterTrackLinks == 'none' ? " selected" : "").">".NETCAT_MODULE_STATS_OPENSTAT_COUNTER_TRACK_LINKS_NONE."</option>\n".
                "<option value='all'".($CounterTrackLinks == 'all' ? " selected" : "").">".NETCAT_MODULE_STATS_OPENSTAT_COUNTER_TRACK_LINKS_ALL."</option>\n".
                "<option value='ext'".($CounterTrackLinks == 'ext' ? " selected" : "").">".NETCAT_MODULE_STATS_OPENSTAT_COUNTER_TRACK_LINKS_EXT."</option>\n".
                "</select></p>\n");

        echo $field_display->result();
        unset($field_display);

        $field_other = new nc_admin_fieldset(NETCAT_MODULE_STATS_OPENSTAT_OTHER_SETTINGS);
        $field_other->add("<p>".nc_admin_checkbox(NETCAT_MODULE_STATS_OPENSTAT_COUNTER_COUNTER_RATING, "CounterRating", $CounterRating)."</p>");
        $field_other->add("<p>".nc_admin_checkbox(NETCAT_MODULE_STATS_OPENSTAT_COUNTER_COUNTER_ADVERT, "CounterAdvert", $CounterAdvert)."</p>");
        $field_other->add("<p>".nc_admin_checkbox(NETCAT_MODULE_STATS_OPENSTAT_COUNTER_INSERT_IN_ALL_TEMPLATES_RECOMMENDED, "CounterInAllTempl", ($CounterCatalogueId ? $CounterInAllTempl : 1))."</p>");
        echo $field_other->result();
        unset($field_other);

        if (!$nc_core->get_settings('Openstat_Login', 'stats')) {
            echo "<p>".nc_admin_checkbox(NETCAT_MODULE_STATS_OPENSTAT_RULES, "OpenstatRules", $OpenstatRules)."</p>";
        }

        echo "</form>";

        $UI_CONFIG->actionButtons[] = array("id" => "add",
                "caption" => NETCAT_MODULE_STATS_SAVE_CHANGES,
                "action" => "mainView.submitIframeForm('')");
    }

    if ($default_templ_id !== NULL) {
        $back_action = "history.back()";
    } elseif ($counter_id) {
        $back_action = "urlDispatcher.load('module.stats.openstat.counters')";
    } else {
        $back_action = "urlDispatcher.load('module.stats.openstat.counters(8)')";
    }

    $UI_CONFIG->actionButtons[] = array("id" => "back",
            "caption" => NETCAT_MODULE_STATS_OPENSTAT_BACK,
            "align" => "left",
            "action" => $back_action);

    return true;
}

function add_user_counter() {

    global $CounterCatalogueId, $UserCounterCode, $counter_id, $CounterInAllTempl;
    global $counters, $default_templ_id, $UI_CONFIG;
    global $db;

    if (isset($_POST['CounterCatalogueId'])) {  // непосредственно добавление
        try {

            if (!$UserCounterCode) {
                throw new Exception(NETCAT_MODULE_STATS_OPENSTAT_ERROR_NO_USER_COUNTER_CODE);
            }
            if (!$counter_id) {
                $db->query("INSERT INTO `Stats_Openstat_Counters` (`Counter_Code`, `User_Counter_Code`, `Catalogue_Id`, `LastUpdated`, `Created`) VALUES ('".
                        $db->escape($UserCounterCode)."', '".
                        "1', '".
                        $db->escape($CounterCatalogueId ? $CounterCatalogueId : '0')."', '".
                        date("Y-m-d H:i:s")."', '".
                        date("Y-m-d H:i:s")."')");
            } else {
                $db->query("UPDATE `Stats_Openstat_Counters` SET ".
                        "`Counter_Code` = '".$db->escape($UserCounterCode).
                        "', `User_Counter_Code` = '1".
                        "', `Catalogue_Id` = '".$db->escape($CounterCatalogueId ? $CounterCatalogueId : '0').
                        "', `LastUpdated` = '".date("Y-m-d H:i:s").
                        "' WHERE `Counter_Id` = '".($counter_id + 0)."'");
            }

            if (!$db->rows_affected) {
                throw new Exception(NETCAT_MODULE_STATS_DB_INSERT_ERR);
            }

            if ($GLOBALS['CounterInAllTempl']) {
                nc_openstat_put_counter_to_templates();
            }

            if ($counter_id) {
                nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
            } else {
                nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_USER_COUNTER_CREATED, "ok");
            }

            return false;
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), "error");
        }
    }



    // ------------------------- вывод формы добавления счетчика ---------------

    echo "<form name='addUserCounterForm' id='addUserCounterForm' method='post' action='?sub_view=counters&phase=5'>\n";

    if (!$sites_ddlist = show_sites_without_counter_ddlist($counters, ($default_templ_id ? $default_templ_id : $CounterCatalogueId))) {
        nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_ERROR_ALREADY_COUNTERS_FOR_ALL_SITES, "error");
    } else {

        if ($counter_id) {
            echo "<input type='hidden' name='counter_id' value='".$counter_id."'/>\n";
        }

        echo $sites_ddlist;

        $field_display = new nc_admin_fieldset(NETCAT_MODULE_STATS_OPENSTAT_DISPLAY_SETTINGS);
        $field_display->add(nc_admin_textarea(NETCAT_MODULE_STATS_OPENSTAT_COUNTER_CODE, "UserCounterCode", $UserCounterCode, 1));
        echo $field_display->result();
        unset($field_display);

        $field_other = new nc_admin_fieldset(NETCAT_MODULE_STATS_OPENSTAT_OTHER_SETTINGS);
        $field_other->add("<p>".nc_admin_checkbox(NETCAT_MODULE_STATS_OPENSTAT_COUNTER_INSERT_IN_ALL_TEMPLATES_RECOMMENDED, "CounterInAllTempl", ($CounterCatalogueId ? $CounterInAllTempl : 1))."</p>");
        echo $field_other->result();
        unset($field_other);

        echo "</form>";

        $UI_CONFIG->actionButtons[] = array("id" => "add",
                "caption" => NETCAT_MODULE_STATS_SAVE_CHANGES,
                "action" => "mainView.submitIframeForm('')");

        if ($default_templ_id !== NULL) {
            $back_action = "history.back()";
        } elseif ($counter_id) {
            $back_action = "urlDispatcher.load('module.stats.openstat.counters')";
        } else {
            $back_action = "urlDispatcher.load('module.stats.openstat.counters(8)')";
        }

        $UI_CONFIG->actionButtons[] = array("id" => "back",
                "caption" => NETCAT_MODULE_STATS_OPENSTAT_BACK,
                "align" => "left",
                "action" => $back_action);

        return true;
    }
}

function show_reports($counter_id) {

    $counter_id = intval($counter_id);
    global $counters;
    global $nc_core;

    if (!check_counters()) {
        return;
    }

    if (!$counters) {
        return;
    }

    if (!$counters[$counter_id]) {
        if (!isset($_GET['catalog_page'])) {
            $counter_id = current($counters)->Counter_Id;
        } elseif (isset($_GET['catalog_page'])) {
            nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_NO_COUNTER_IN_CATALOGUE.
                    " <a href=\"?sub_view=counters&phase=1&default_templ_id=".intval($_GET['catalog_page'])."\">".NETCAT_MODULE_STATS_OPENSTAT_ADD_COUNTER."</a>", "info");
            return;
        } else {
            nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_INVALID_COUNTER_ID, "error");
            return;
        }
    }

    echo "<p style='float: left;".(isset($_GET['catalog_page']) ? " display: none;" : "")."'>\n".NETCAT_MODULE_STATS_OPENSTAT_SELECT_COUNTER.":&nbsp;\n".
    "<select name='CountersList' id='CountersList'>\n";
    foreach ($counters as $counter) {
        echo "<option value='".$counter->Counter_Id."'".
        ($counter->Counter_Id == $counter_id ? "selected " : "").">".
        ($counter->Catalogue_Id ? NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_CATALOGUE." ".$counter->Catalogue_Id.": ".$nc_core->catalogue->get_by_id($counter->Catalogue_Id, 'Catalogue_Name') : NETCAT_MODULE_STATS_OPENSTAT_ALL_SITES).
        "</option>\n";
    }
    echo "</select>\n</p>\n";

    if ($counters[$counter_id]->User_Counter_Code) {
        echo "<script type='text/javascript' src='" . nc_add_revision_to_url(nc_module_path('stats') . 'openstat/reports.js') . "'></script>\n";
        echo "<div style='clear: both;'><p align='center' style='margin-top: 3em;'><big>".NETCAT_MODULE_STATS_OPENSTAT_SEE_STATS_ON_OPENSTAT." <a href='http://www.openstat.ru/' target='_blank'>Openstat</a></big></p></div>";
    } else {

        echo "<script type='text/javascript' src='" . nc_add_revision_to_url(nc_module_path('stats') . 'openstat/ajax_reports.js') . "'></script>\n";
        echo "<script type='text/javascript'>
        var INVALID_START_DATE = '".NETCAT_MODULE_STATS_OPENSTAT_INVALID_START_DATE."';
        var INVALID_END_DATE = '".NETCAT_MODULE_STATS_OPENSTAT_INVALID_END_DATE."';
        var INVALID_PERIOD = '".NETCAT_MODULE_STATS_OPENSTAT_INVALID_PERIOD."';
        var START_DATE_TOO_BIG = '".NETCAT_MODULE_STATS_OPENSTAT_START_DATE_TOO_BIG."';
        </script>";
        /* echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          ".NETCAT_MODULE_STATS_OPENSTAT_STATS_PERIOD.":&nbsp;
          <select name='StatsPeriodList' id='StatsPeriodList'>
          <option value='hour'>".NETCAT_MODULE_STATS_OPENSTAT_STATS_HOUR."</option>
          <option value='week'>".NETCAT_MODULE_STATS_OPENSTAT_STATS_WEEK."</option>
          <option value='day' selected>".NETCAT_MODULE_STATS_OPENSTAT_STATS_DAY."</option>
          <option value='month'>".NETCAT_MODULE_STATS_OPENSTAT_STATS_MONTH."</option>
          </select>
          &nbsp;&nbsp;&nbsp;
          ".NETCAT_MODULE_STATS_OPENSTAT_STATS_FROM."&nbsp;
          <input id='DayFrom' type='text' style='width: 27px;' maxlength='2' value='".date("j", time())."'>
          <input id='MonthFrom' type='text' style='width: 27px;' maxlength='2' value='".(date("n", time())-1)."'>
          <input id='YearFrom' type='text' style='width: 50px;' maxlength='4' value='".date("Y", time())."'>&nbsp;&nbsp;
          <span id='TimeFrom' style='display: none;'>
          <input id='HourFrom' type='text' style='width: 27px;' maxlength='2' value='00'>:
          <input id='DayFrom' type='text' style='width: 27px;' maxlength='2' disabled value='00'>
          </span>
          &nbsp;
          ".NETCAT_MODULE_STATS_OPENSTAT_STATS_TILL."&nbsp;
          <input id='DayTill' type='text' style='width: 27px;' maxlength='2' value='".date("j", time())."'>
          <input id='MonthTill' type='text' style='width: 27px;' maxlength='2' value='".(date("n", time()))."'>
          <input id='YearTill' type='text' style='width: 50px;' maxlength='4' value='".date("Y", time())."'>&nbsp;&nbsp;
          <span id='TimeTill' style='display: none;'>
          <input id='HourTill' type='text' style='width: 27px;' maxlength='2' value='".date("H", time())."'>:
          <input id='DayTill' type='text' style='width: 27px;' maxlength='2' disabled value='00'>
          </span>&nbsp;&nbsp;
          <input id='ShowStat' type='button' title='".NETCAT_MODULE_STATS_OPENSTAT_STATS_SHOW."' value='".NETCAT_MODULE_STATS_OPENSTAT_STATS_SHOW."'>"; */
        echo "
            <table width='300px' height='1.3em' border='0' align='center' style='text-align: center;'><tr>
            <td width='33%'><h4><a href='#' id='YesterdayLink' style='text-decoration: none;'>".NETCAT_MODULE_STATS_OPENSTAT_YESTERDAY."</a></h4>&nbsp;</td>
            <td width='33%'><h4><a href='#' id='LastWeekLink' style='text-decoration: none;'>".NETCAT_MODULE_STATS_OPENSTAT_LAST_WEEK."</a></h4>&nbsp;</td>
            <td><h4><a href='#' id='LastMonthLink' style='text-decoration: none;'>".NETCAT_MODULE_STATS_OPENSTAT_LAST_MONTH."</a></h4>&nbsp</td>
            </tr></table>
      \n";

        echo "<div id='CounterReports'></div>";
    }
}

function show_counters($phase) {

    global $nc_core, $db;
    global $counters, $default_templ_id, $UI_CONFIG;

    if ($phase == 1) { // Форма добавления счетчика через API
        if (!extension_loaded('curl')) {
            nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_NOT_FOUND_CURL, "error");
            EndHtml();
            exit;
        }

        $ret = add_api_counter();
        if ($ret) {
            return;
        }
    } elseif (($phase == 3) && isset($_POST['DeleteCounter'])) {  // удалить выбранные
        global $DeleteCounter, $counters;

        $openstat = new nc_Openstat_core_class($nc_core->get_settings('Openstat_Login', 'stats'), $nc_core->get_settings('Openstat_Password', 'stats'));
        foreach ($counters as $del_counter) { //$DeleteCounter as $del_counter_id => $del_counter_flag) {
            if ($DeleteCounter[$del_counter->Counter_Id]) {
                if (!$del_counter->User_Counter_Code) {
                    $err_code = $openstat->delete_counter($del_counter->Openstat_Counter_Id);
                    if ($err_code != 204) {
                        if ($err_code == 403) {
                            nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_ERROR_NEW_COUNTER_AUTH_ERROR, "error");
                        } else {
                            nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_ERROR_DEL_COUNTER.$err_code, "error");
                        }
                        $UI_CONFIG->actionButtons[] = array("id" => "back",
                                "caption" => NETCAT_MODULE_STATS_OPENSTAT_BACK,
                                "align" => "left",
                                "action" => "urlDispatcher.load('module.stats.openstat.counters')");
                        return;
                    }
                }

                $db->query("DELETE FROM `Stats_Openstat_Counters` WHERE `Counter_Id` = '".$del_counter->Counter_Id."'");
            }
        }

        nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
    } elseif (($phase == 4) && isset($_POST['DoAction'])) {  //удалить все
        $openstat = new nc_Openstat_core_class($nc_core->get_settings('Openstat_Login', 'stats'), $nc_core->get_settings('Openstat_Password', 'stats'));

        foreach ($counters as $del_counter) {

            if (!$del_counter->User_Counter_Code) {
                $err_code = $openstat->delete_counter($del_counter->Openstat_Counter_Id);

                if ($err_code != 204) {
                    if ($err_code == 403) {
                        nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_ERROR_NEW_COUNTER_AUTH_ERROR, "error");
                    } else {
                        nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_ERROR_DEL_COUNTER.$err_code, "error");
                    }
                    $UI_CONFIG->actionButtons[] = array("id" => "back",
                            "caption" => NETCAT_MODULE_STATS_OPENSTAT_BACK,
                            "align" => "left",
                            "action" => "urlDispatcher.load('module.stats.openstat.counters')");
                    return;
                }
            }

            $db->query("DELETE FROM `Stats_Openstat_Counters` WHERE `Counter_Id` = '".$db->escape($del_counter->Counter_Id)."'");
        }

        nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
    } elseif ($phase == 5) { // Форма добавления своего кода счетчика
        $ret = add_user_counter();
        if ($ret) {
            return;
        }
    } elseif (($phase == 7) && isset($_GET['counter_id'])) {  //  вывод формы изменения
        global $counter_id;

        $counter_id = intval($counter_id);

        $counter_params = $counters[$counter_id];

        if (!$counter_params) {
            nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_INVALID_COUNTER_ID, "error");
        } else {

            if ($counter_params->User_Counter_Code) { // свой код счетчика
                $GLOBALS['CounterCatalogueId'] = $counter_params->Catalogue_Id;
                $GLOBALS['UserCounterCode'] = $counter_params->Counter_Code;
                $GLOBALS['CounterCatalogueId'] = $counter_params->Catalogue_Id;
                $GLOBALS['CounterInAllTempl'] = 1;

                add_user_counter();
            } else {  // Автосгенерированный счетчик
                $GLOBALS['CounterCatalogueId'] = $counter_params->Catalogue_Id;
                $GLOBALS['CounterRating'] = $counter_params->Rating;
                $GLOBALS['CounterAdvert'] = $counter_params->Advert;
                $GLOBALS['CounterColor'] = $counter_params->Color;
                $GLOBALS['CounterPicture'] = $counter_params->Size;
                $GLOBALS['CounterTrackLinks'] = $counter_params->TrackLinks;
                $GLOBALS['CounterInAllTempl'] = 1;

                add_api_counter();
            }
        }

        return;
    } elseif (($phase == 8)) {  // выбираем тип счетчика
        if (!$sites_ddlist = show_sites_without_counter_ddlist($counters)) {
            nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_ERROR_ALREADY_COUNTERS_FOR_ALL_SITES, "error");
        } else {
            echo "<div style='position: absolute; top: 40%; width:95%; text-align: center;'>
          <p><a href='?sub_view=counters&phase=1'>".NETCAT_MODULE_STATS_OPENSTAT_ADD_API_COUNTER."</a></p>
          <br />
          <p><a href='?sub_view=counters&phase=5'>".NETCAT_MODULE_STATS_OPENSTAT_ADD_USER_COUNTER."</a></p>
          </div>\n";
        }

        $UI_CONFIG->actionButtons[] = array("id" => "back",
                "caption" => NETCAT_MODULE_STATS_OPENSTAT_BACK,
                "align" => "left",
                "action" => "urlDispatcher.load('module.stats.openstat.counters')");
        return;
    } elseif (($phase == 9)) {  // подтверждение удаления всех счетчиков
        echo "<p>".NETCAT_MODULE_STATS_OPENSTAT_DEL_ALL_CONFIRM."</p>\n";
        echo "<form name='delAllCountersFormSubmit' id='delAllCountersFormSubmit' method='post' action='?sub_view=counters&phase=4'>
          <input type='hidden' name='DoAction' value='1'>
          </form>\n";

        $UI_CONFIG->actionButtons[] = array("id" => "back",
                "caption" => NETCAT_MODULE_STATS_OPENSTAT_YES,
                "align" => "right",
                "action" => "mainView.submitIframeForm('delAllCountersFormSubmit')");
        $UI_CONFIG->actionButtons[] = array("id" => "back",
                "caption" => NETCAT_MODULE_STATS_OPENSTAT_NO,
                "align" => "left",
                "action" => "urlDispatcher.load('module.stats.openstat.counters')");
        return;
    } elseif (($phase == 10)) {  // подтверждение удаления выбранных счетчиков
        global $DeleteCounter, $counters;

        echo "<p>".NETCAT_MODULE_STATS_OPENSTAT_DEL_SELECTED_CONFIRM."</p>\n";

        echo "<form name='delCountersFormSubmit' id='delCountersFormSubmit' method='post' action='?sub_view=counters&phase=3'>";
        foreach ($DeleteCounter as $del_counter => $value) {
            echo "<input type='hidden' name='DeleteCounter[".$del_counter."]' value='".$value."'>\n";
        }
        echo "</form>\n";


        $UI_CONFIG->actionButtons[] = array("id" => "back",
                "caption" => NETCAT_MODULE_STATS_OPENSTAT_YES,
                "align" => "right",
                "action" => "mainView.submitIframeForm('delCountersFormSubmit')");
        $UI_CONFIG->actionButtons[] = array("id" => "back",
                "caption" => NETCAT_MODULE_STATS_OPENSTAT_NO,
                "align" => "left",
                "action" => "urlDispatcher.load('module.stats.openstat.counters')");
        return;
    }




    // -------------------- Выводим все счетчики -----------------------------

    $counters = $db->get_results("SELECT * FROM Stats_Openstat_Counters");
    if (!check_counters()) {
        return;
    }

    if ($counters) {
        echo "<form name='delCountersForm' id='delCountersForm' method='post' action='?sub_view=counters&phase=10'>
            <table border=0 cellpadding=0 cellspacing=0 width=100%>
            <tr><td >
            <table class='admin_table' width=100%>
            <tr>
            <td  width=3%>ID</td>
            <td  width=82%>".NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_CATALOGUE."</td>
            <td width=5%>".NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_EDIT."</td>
            <td align=center><div class='icons icon_delete' title='".NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_DELETE."'></div></td>
            </tr>\n";

        foreach ($counters as $counter) {
            echo "<tr><td>".$counter->Counter_Id."</td>
                <td>".($counter->Catalogue_Id ? "<a target='blank' href='" . $nc_core->catalogue->get_url_by_id($counter->Catalogue_Id) . "'>" . $counter->Catalogue_Id . ": " . $nc_core->catalogue->get_by_id($counter->Catalogue_Id, 'Catalogue_Name') . "</a>" : NETCAT_MODULE_STATS_OPENSTAT_ALL_SITES) . "</td>
                <td align='center'><a href='?sub_view=counters&phase=7&counter_id=".$counter->Counter_Id."'><div class='icons icon_folder_edit' title='".NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_EDIT."'></div></a></td>
                <td align='center'><input type='checkbox' name='DeleteCounter[".$counter->Counter_Id."]' value='1'></td>";
        }

        echo "</table></td></tr></table></form>";


        $UI_CONFIG->actionButtons[] = array(
            "id" => "delete",
            "align" => "left",
            "caption" => NETCAT_MODULE_STATS_OPENSTAT_DEL_SELECTED,
            "action" => "mainView.submitIframeForm('delCountersForm')",
            "red_border" => true,
        );
        $UI_CONFIG->actionButtons[] = array(
            "id" => "delete_all",
            "align" => "left",
            "caption" => NETCAT_MODULE_STATS_OPENSTAT_DEL_ALL,
            "action" => "urlDispatcher.load('module.stats.openstat.counters(9)')",
            "red_border" => true,
        );
    } else {
        nc_print_status(NETCAT_MODULE_STATS_OPENSTAT_NO_COUNTERS, "info");
    }

    $UI_CONFIG->actionButtons[] = array("id" => "add",
            "caption" => NETCAT_MODULE_STATS_OPENSTAT_ADD_COUNTER,
            "action" => "urlDispatcher.load('module.stats.openstat.counters(8)')");
}

function show_sites_without_counter_ddlist($counters, $selected_id = NULL) {

    global $nc_core;
    if (isset($selected_id)) {
        $selected_id = intval($selected_id);
    }
    $all_catalogues = $nc_core->catalogue->get_all();

    $field_common = new nc_admin_fieldset(NETCAT_MODULE_STATS_OPENSTAT_COMMON_SETTINGS);

    // выбор сайта (CatalogueId)
    $field_common->add("<p>".NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_CATALOGUE."<br />
            <select name='CounterCatalogueId'>");
    if (!$counters || ($selected_id === 0) || (count($counters) == 1 && $selected_id)) {
        $field_common->add("<option selected value='0'>".NETCAT_MODULE_STATS_OPENSTAT_ALL_SITES."</option>\n");
    } elseif (($selected_id === NULL) && ((current($counters)->Catalogue_Id == 0) || (count($counters) == count($all_catalogues)))) {
        return;
    } else {
        foreach ($counters as $counter) {
            if ($counter->Catalogue_Id != $selected_id)
                    $used_catalogues[$counter->Catalogue_Id] = $counter->Catalogue_Id;
        }
    }

    foreach ($all_catalogues as $catalogue) {
        if (!isset($used_catalogues[$catalogue['Catalogue_ID']])) {
            $field_common->add("<option ".($selected_id == $catalogue['Catalogue_ID'] ? "selected " : "")."value='".$catalogue['Catalogue_ID']."'>".$catalogue['Catalogue_ID'].". ".$catalogue['Catalogue_Name']."</option>\n");
        }
    }
    $field_common->add("</select></p>\n");
    return $field_common->result();
}

function show_templates($phase = 0) {

    global $UI_CONFIG;
    global $PutInAllTemplates;
    global $Templ, $DoAction;

    if ($DoAction !== NULL) {

        if ($phase == 1) { //изменение по "галкам"
            change_templates();
            nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
        } elseif (($phase == 2)) {  // ставим во все макеты
            nc_openstat_put_counter_to_templates();
            nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
        } elseif (($phase == 3)) {  // удаляем из всех макетов
            nc_openstat_delete_counter_from_templates();
            nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
        }
    }


    if (!check_counters()) {
        return;
    }

    // Все макеты
    $result = write_template(0, nc_openstat_check_counter_in_templates(1));
    if ($result) {
        echo "<form name='TemplatesForm' id='TemlatesForm' method='post' action='?sub_view=templates&phase=1'>\n
                  <input type='hidden' name='DoAction' value='1'>
                  <table cellpadding='0' cellspacing='0' class='templateMap'><tr>\n
                  <td width='60px' align='center'>".NETCAT_MODULE_STATS_OPENSTAT_INSERT_COUNTER."</td>
                  <td style='padding-left:15px;'>".NETCAT_MODULE_STATS_OPENSTAT_TEMPLATE."</td>\n
                  </tr></table>";
        echo $result;
        echo "</form>\n";
        echo "<form name='addAllTemplatesForm' id='addAllTemplatesForm' method='post' action='?sub_view=templates&phase=2'>\n
                <input type='hidden' name='DoAction' value='1'>
                </form>";
        echo "<form name='delAllTemplatesForm' id='delAllTemplatesForm' method='post' action='?sub_view=templates&phase=3'>\n
                <input type='hidden' name='DoAction' value='1'>
                </form>";

        $UI_CONFIG->actionButtons[] = array("id" => "submit_all",
                "caption" => NETCAT_MODULE_STATS_SAVE_CHANGES,
                "action" => "mainView.submitIframeForm('TemlatesForm')");
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
                "align" => 'left',
                "caption" => NETCAT_MODULE_STATS_OPENSTAT_INS_IN_ALL_TEMPLATES,
                "action" => "mainView.submitIframeForm('addAllTemplatesForm')");
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "align" => 'left',
            "caption" => NETCAT_MODULE_STATS_OPENSTAT_DEL_FROM_ALL_TEMPLATES,
            "action" => "mainView.submitIframeForm('delAllTemplatesForm')",
            "red_border" => true,
        );
    } else {
        nc_print_status(CONTROL_TEMPLATE_NONE, "info");
    }
}

function change_templates() {

    global $db, $nc_core;
    global $Templ;

    if ($Templ) {
        foreach ($Templ as $TemplID => $TemplVal) {
            $PutTempl[] = intval($TemplID);
        }

        if ($PutTempl) {
            nc_openstat_put_counter_to_templates(0, $PutTempl);
            $DelTempl = $db->get_col("SELECT `Template_ID` FROM `Template` WHERE (`Template_ID`<>'".$nc_core->get_settings('EditDesignTemplateID')."') AND (`Template_ID` NOT IN (".join($PutTempl, ",")."))");
            if ($DelTempl) {
                nc_openstat_delete_counter_from_templates($DelTempl);
            }
        }
    } else {
        nc_openstat_delete_counter_from_templates();
    }
}