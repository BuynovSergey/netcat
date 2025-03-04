<?php

/**
 * Вывод настроек календаря в шапку макета
 *
 * @param int $id идентификатор темы календаря
 * @return string html-код настроек
 */
function nc_set_calendar($id = 0) {
    global $db, $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_PATH;
    $id = intval($id);
    $result = "";

    $css = $db->get_var("SELECT `CalendarCSS`
		FROM `Calendar_Settings`
		WHERE " . ($id ? "`ID` = '" . $id . "'" : "`DefaultTheme` = 1"));

    if ($css) {
        $result .= "<style type='text/css'>\n" . $css . "</style>\n";
    }

    $result .= "<script type='text/javascript'>var SUB_FOLDER = '" . $SUB_FOLDER . "', NETCAT_PATH = '" . $SUB_FOLDER . $HTTP_ROOT_PATH . "';</script>\n";
    $result .= "<script type='text/javascript' src='" . nc_add_revision_to_url($ADMIN_PATH . 'js/lib.js') . "'></script>\n";
    $result .= "<script type='text/javascript' src='" . nc_add_revision_to_url(nc_module_path('calendar') . 'calendar.js') . "'></script>\n";

    return $result;
}

/**
 * Функция проверки доступа на чтения, для календаря этого достаточно, т.к. он только читает
 *
 * @param int $catalogue идентификатор сайта
 * @param int $sub идентификатор раздела
 * @param int $cc идентификатор компонента в разделе
 * @return bool
 */
function nc_calendar_read_permission($catalogue, $sub, $cc) {
    global $AUTH_USER_ID, $perm;

    # если пользователь не зарегистрирован или не объявлен $perm
    if (!$AUTH_USER_ID || !class_exists('Permission') || !$perm instanceof Permission) {
        return false;
    }
    if (!$perm->isSubClass($cc, MASK_READ)) {
        return false;
    }

    return true;
}

/**
 * Вывод календаря
 *
 * @param int $theme идентификатор темы календаря
 * @param int|int[] $cc идентификатор компонента в разделе
 * @param int|string $setDate дата для отображения
 * @param bool|string $DateField заполнить независимо от маски дней
 * @param bool $filled
 * @param bool $queryDate
 * @param bool $popup
 * @param array $calendar_fields
 * @param bool $cc_ignore
 * @return string html-код календаря
 */
function nc_show_calendar($theme = 0, $cc = 0, $setDate = 0, $DateField = "Date", $filled = false, $queryDate = false, $popup = false, $calendar_fields = array(), $cc_ignore = false, $admin_mode = 0) {
    global $db, $DOCUMENT_ROOT, $SUB_FOLDER, $HTTP_ROOT_PATH, $action, $nc_core, $AUTH_USER_ID;

    $admin_mode = $admin_mode ? $admin_mode : $nc_core->admin_mode;
    $old_cc = $cc;
    # поле для выборки
    if ($DateField) {
        $DateField = $db->escape($DateField);
    }
    # переменные
    $antiJumper = true;
    $theme = intval($theme);
    $curr_year = date("Y");
    $imagesPath = nc_module_path('calendar') . 'images/';
    $lines = 0;
    $first_folder_id = null;
    $mirror_data = array();

    if (!$popup) {
        # если нет параметра $cc, получаем глобальные значения
        if (!$cc) {
            $cc = $nc_core->sub_class->get_current('Sub_Class_ID');
        }

        if (!is_array($cc)) {
            $cc = array($cc);
        }
        $cc_ids = array_map('intval', $cc);

        foreach ($cc_ids as $k => $v) {
            $cc_data[$v] = $nc_core->sub_class->get_by_id($v);

            if (!$cc_data[$v]) {
                trigger_error("<b>nc_show_calendar()</b>: Incorrect \$cc (" . $v . ")", E_USER_WARNING);
                return false;
            }

            if ($cc_data[$v]['SrcMirror'] > 0) {
                $cc_ids[] = $cc_data[$v]['SrcMirror'];
                $mirror_data[$cc_data[$v]['SrcMirror']] = $cc_data[$v];
            }

            if (!$first_folder_id) {
                $first_folder_id = $cc_data[$v]['Subdivision_ID'];
            }

            if (!is_array($DateField)) {
                $cc_date_field[$v] = $DateField;
            }
            else {
                $cc_date_field[$v] = $DateField[$v] ? $DateField[$v] : 'Date';
            }

            // проверка прав
            $read_access = false;
            switch ($cc_data[$v]['Read_Access_ID']) {
                case 1:
                    $read_access = true;
                    break;
                case 2:
                    if ($AUTH_USER_ID) {
                        $read_access = true;
                    }
                    break;
                case 3:
                    $read_access = nc_calendar_read_permission($cc_data[$v]['Catalogue_ID'], $cc_data[$v]['Subdivision_ID'], $v);
                    break;
            }
            if (!$read_access) {
                unset($cc_ids[$k]);
            }
        }

        if (empty($cc_ids)) {
            return false;
        }
    }


    // date from address line
    if ($queryDate || preg_match("/\/(\d{4})\/(?:(\d{2})\/)?(?:(\d{2})\/)?/s", $_SERVER['REQUEST_URI'], $matches)) {
        if (!$queryDate) {
            array_shift($matches);
            $query_date_year = $matches[0];
            $query_date_month = $matches[1] ? $matches[1] : 0;
            $query_date_day = $matches[2] ? $matches[2] : 0;
            $queryDate = join("-", $matches);
        }
        else {
            // query date
            list($query_date_year, $query_date_month, $query_date_day) = explode("-", $queryDate);
            if (!$query_date_month) {
                $query_date_month = 0;
            }
            if (!$query_date_day) {
                $query_date_day = 0;
            }
        }
    }
    else {
        $queryDate = false;
    }

    # проверим входную дату
    $date = $setDate ? $setDate : (isset($GLOBALS['date']) && $GLOBALS['date'] ? $GLOBALS['date'] : false);
    if ($date && preg_match("/^([0-9]{1,4}-?){1,3}$/", $date)) {
        // simply date value in script
        list($year, $month, $day) = explode("-", $date);
        if (!$month) {
            $month = 1;
        }
        if (!$day) {
            $day = 1;
        }
    }
    else {
        list($year, $month, $day) = explode("-", date("Y-m-d"));
    }

    # данные календаря
    $settings = $db->get_row("SELECT * FROM `Calendar_Settings`
		WHERE " . ($theme ? "`ID` = '" . $theme . "'" : "`DefaultTheme` = 1"), ARRAY_A);

    if (empty($settings)) {
        trigger_error("<b>nc_show_calendar()</b>: No calendar theme found (" . $theme . ")", E_USER_WARNING);
        return false;
    }

    # идентификатор темы календаря
    ###_BEGIN: вывод блока дней
    $calendarBody = $settings['dayTemplatePrefix'];

    ###_BEGIN: отображение названий дней
    $calendarBody .= $settings['dayTemplateBegin'];

    # названия дней, если есть
    if ($settings['DaysName']) {
        $DaysNameArray = explode(",", $settings['DaysName']);
        # дополняем массив пустышкой в начале, не из романа братьев Стругацких :)
        if (!empty($DaysNameArray) && is_array($DaysNameArray)) {
            $DaysNameArray = array_map("trim", $DaysNameArray);
            $DaysNameArray = array_pad($DaysNameArray, -8, "");
        }
    }

    # заменяем на названия дней из массива
    for ($i = 1; $i <= 7; $i++) {
        $DayName = $DaysNameArray[$i];
        if (($i % 7) == 6) {
            $calendarBody .= str_replace("%NAME_DAY", $DayName, $settings['daySETTemplateHeader']);
        }
        elseif (!($i % 7)) {
            $calendarBody .= str_replace("%NAME_DAY", $DayName, $settings['daySUNTemplateHeader']);
        }
        else {
            $calendarBody .= str_replace("%NAME_DAY", $DayName, $settings['dayTemplateHeader']);
        }
    }

    $calendarBody .= $settings['dayTemplateEnd'];
    ###_END: отображение названий дней
    ###_BEGIN: отображение дней
    $calendarBody .= $settings['dayTemplateBegin'];

    # номер дня недели за первое число
    $weekDayBehind = date("w", mktime(0, 0, 0, $month, 1, $year));
    # 0 - воскресенье
    if ($weekDayBehind == 0) {
        $weekDayBehind = 7;
    }

    # вставляем пустые дни в начало
    for ($i = 1; $i < $weekDayBehind; $i++) {
        # суббота
        if (($i % 7) == 6) {
            $calendarBody .= $settings['nodaySETTemplate'];
        }
        # воскресенье
        elseif (!($i % 7)) {
            $calendarBody .= $settings['nodaySUNTemplate'];
        }
        # дни с понедельника по пятницу
        else {
            $calendarBody .= $settings['nodayTemplate'];
        }
    }
    if ($weekDayBehind != 1) {
        $lines = $lines + 1;
    }

    # дней в месяце $month
    $daysInMonth = date("t", mktime(0, 0, 0, $month, 1, $year));

    # маска дней, массив "0", в количестве равным дням в месяце $month
    $daysMask = array_fill(0, $daysInMonth, $filled ? $old_cc : 0);

    # получаем данные о "событиях" из базы
    if (!$filled) {
        # проверим, есть ли такое поле
        //$DateFieldExist = nc_calendar_possibility_check($classID, $DateField);
        $query = array();
        foreach ($cc_ids as $cc_id) {
            $query[] = "SELECT DAYOFMONTH(`" . $cc_date_field[$cc_id] . "`) as `d`, `Sub_Class_ID` as `cc`
                FROM `Message" . $cc_data[$cc_id]['Class_ID'] . "`
                WHERE " . (!$cc_ignore ? "`Sub_Class_ID` = '" . intval($cc_id) . "'
                AND" : null) . " MONTH(`" . $cc_date_field[$cc_id] . "`) = '" . $month . "'
					      AND YEAR(`" . $cc_date_field[$cc_id] . "`) = '" . $year . "'
					      AND Checked = '1'
                GROUP BY DAYOFMONTH(`" . $cc_date_field[$cc_id] . "`) ";
        }
        $res = $db->get_results(join(' UNION ', $query), ARRAY_A);

        # заполненяем маску, все дни месяца без событий - "0", с событиями - "1"
        if (!empty($res)) {
            foreach ($res AS $value) {
                $daysMask[$value['d'] - 1] = (!$cc_ignore ? $value['cc'] : $cc[0]);
            }
        }
    } else {
        for ($i = 0; $i < $daysInMonth; $i++) {
            $daysMask[$i] = 1;
        }
    }


    ###_BEGIN: даты календаря
    for ($i = 1; $i <= $daysInMonth; $i++) {
        # шаблон для отрисовки ссылок, если понадобится (если есть событие)
        //замена isset на !empty, поскольку пременная может существовать, но быть пустой, что приводит к багу, когда вообще все даты в календаре являются ссылками
        if (!empty($daysMask[$i - 1])) {
            $dayLinkEnd = "</a>";
            if ($popup) {
                $dayLinkBegin = "<a href='#' onclick='nc_calendar_popup_callback(" . $i . ", " . $month . ", " . $year . ", \"" . nc_array_value($calendar_fields, 0) . "\", \"" . nc_array_value($calendar_fields, 1) . "\",\"" . nc_array_value($calendar_fields, 2) . "\"); return false;'>";
            }
            else {
                if (isset($mirror_data[$daysMask[$i - 1]]) && !empty($mirror_data[$daysMask[$i - 1]])) {
                    $_cc_id = $mirror_data[$daysMask[$i - 1]]['Sub_Class_ID'];
                    $_sub_id = $mirror_data[$daysMask[$i - 1]]['Subdivision_ID'];
                }
                else {
                    $_cc_id = $daysMask[$i - 1];
                    $_sub_id = $cc_data[$daysMask[$i - 1]]['Subdivision_ID'];
                }

                if ($admin_mode) {
                    $path = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . '?sub=' . $_sub_id . '&amp;cc=' . $_cc_id;
                    $dayLinkBegin = "<a href='" . $path . '&amp;date=' . $year . "-" . sprintf("%02d", $month) . "-" . sprintf("%02d", $i) . "'>";
                }
                else {
                    $path = nc_folder_path($_sub_id, sprintf("%d-%02d-%02d", $year, $month, $i));
                    $dayLinkBegin = "<a href='$path'>";
                }
            }
        }
        else {
            $dayLinkBegin = $dayLinkEnd = "";
        }

        # конец строки с днями
        if (($weekDayBehind + $i - 1) % 7 == 1) {
            if ($lines) {
                $calendarBody .= $settings['dayTemplateEnd'] . $settings['dayTemplateBegin'];
            }
            $lines = $lines + 1;
        }

        switch (true) {
            case ($weekDayBehind + $i - 1) % 7 == 6 && $i != $day:
                # суббота
                $calendarBody .= str_replace("%DAY", $dayLinkBegin . $i . $dayLinkEnd, $settings['daySETTemplate']);
                break;
            case ($weekDayBehind + $i - 1) % 7 == 0 && $i != $day:
                # воскресенье
                $calendarBody .= str_replace("%DAY", $dayLinkBegin . $i . $dayLinkEnd, $settings['daySUNTemplate']);
                break;
            case $queryDate && $i == $query_date_day && $query_date_month == $month && $query_date_year == $year:
                # текущий день
                $calendarBody .= str_replace("%DAY", ($action == "full" || $popup ? $dayLinkBegin . $i . $dayLinkEnd : $i), $settings['dayCurTemplate']);
                break;
            case!$queryDate && $i == $day && date("m") == $month && date("Y") == $year:
                # текущий день + ссылка
                $calendarBody .= str_replace("%DAY", $dayLinkBegin . $i . $dayLinkEnd, $settings['dayCurTemplate']);
                break;
            default:
                # дни с понедельника по пятницу
                $calendarBody .= str_replace("%DAY", $dayLinkBegin . $i . $dayLinkEnd, $settings['dayTemplate']);
        }
    }
    ###_END: даты календаря
    # номер дня недели за последнее число
    $weekDayAfter = date("w", mktime(0, 0, 0, $month, $daysInMonth, $year));
    # 0 - воскресенье
    if ($weekDayAfter == 0) {
        $weekDayAfter = 7;
    }

    # система антиджампера, чтобы календарь не "прыгал", прорисовываем пустые строки
    $daysEnd = ($antiJumper ? 7 * ($lines < 6 ? 7 - $lines : 1) : 7);

    #  вставляем пустые дни в конец
    for ($i = $weekDayAfter; $i < $daysEnd; $i++) {
        # конец строки с днями
        if (($i + 1) % 7 == 1 && $daysEnd > 7) {
            $calendarBody .= $settings['dayTemplateEnd'] . $settings['dayTemplateBegin'];
        }
        # суббота
        if ((($i + 1) % 7) == 6) {
            $calendarBody .= $settings['nodaySETTemplate'];
        }
        # воскресенье
        else if (!(($i + 1) % 7)) {
            $calendarBody .= $settings['nodaySUNTemplate'];
        }
        # дни с понедельника по пятницу
        else {
            $calendarBody .= $settings['nodayTemplate'];
        }
    }

    $calendarBody .= $settings['dayTemplateEnd'];
    ###_END: отображение дней

    $calendarBody .= $settings['dayTemplateSuffix'];
    ###_END: вывод блока дней
    # массив с названиями месяцев из языкового файла
    eval("\$MonthArray = " . NETCAT_MODULE_CALENDAR_MONTH_NAME_ARRAY . ";");
    # дополним одним пустым элементом в начале, для удобства
    if (is_array($MonthArray)) {
        $MonthArray = array_pad($MonthArray, -13, "");
    }

    # выпадающий список "месяц"
    if ($popup) {
        $month_select = "<select onchange='nc_calendar_generate_popup(" . $day . ", this.value, " . $year . ", \"" . nc_array_value($calendar_fields, 0) . "\", \"" . nc_array_value($calendar_fields, 1) . "\",\"" . nc_array_value($calendar_fields, 2) . "\",\"" . $theme . "\"); return false;'>";
    }
    else {
        $month_select = "<select onchange='nc_calendar_generate(" . $day . ", this.value, " . $year . "" . ($cc_ignore ? ", true " : ", false ") . ", ".($admin_mode ? 1 : 0)."); return false;'>";
    }
    for ($monthCount = 1; $monthCount <= 12; $monthCount++) {
        $month_select .= "<option value='" . $monthCount . "'" . ($month == $monthCount ? " selected" : "") . ">" . $MonthArray[$monthCount] . "</option>";
    }
    $month_select .= "</select>";

    # выпадающий список "год"
    if ($popup) {
        $year_select = "<select onchange='nc_calendar_generate_popup(" . $day . ", " . $month . ", this.value, \"" . nc_array_value($calendar_fields, 0) . "\", \"" . nc_array_value($calendar_fields, 1) . "\",\"" . nc_array_value($calendar_fields, 2) . "\", \"" . $theme . "\"); return false;'>";
    }
    else {
        $year_select = "<select onchange='nc_calendar_generate(" . $day . ", " . $month . ", this.value" . ($cc_ignore ? ", true " : ", false ") . ", ".($admin_mode ? 1 : 0)."); return false;'>";
    }

    $parallax_year_forward = intval($settings['parallax_year_forward']);
    $parallax_year_backward = intval($settings['parallax_year_backward']);
    $parallax_year_forward = $parallax_year_forward > 0 ? $parallax_year_forward : 10;
    $parallax_year_backward = $parallax_year_backward > 0 ? $parallax_year_backward : 10;

    $limit = $curr_year + $parallax_year_forward;

    for ($yearCount = ($curr_year - $parallax_year_backward); $yearCount <= $limit; $yearCount++) {
        $year_select .= "<option value='" . $yearCount . "'" . ($year == $yearCount ? " selected" : "") . ">" . $yearCount . "</option>";
    }

    $year_select .= "</select>";

    if ($settings['StatusImage'] && file_exists($DOCUMENT_ROOT . $imagesPath . $settings['StatusImage'])) {
        $IMG_STATUS = true;
    }
    else {
        $IMG_STATUS = false;
    }
    if ($settings['PrevImage'] && file_exists($DOCUMENT_ROOT . $imagesPath . $settings['PrevImage'])) {
        $IMG_PREV_MONTH = true;
    }
    else {
        $IMG_PREV_MONTH = false;
    }
    if ($settings['NextImage'] && file_exists($DOCUMENT_ROOT . $imagesPath . $settings['NextImage'])) {
        $IMG_NEXT_MONTH = true;
    }
    else {
        $IMG_NEXT_MONTH = false;
    }
    if ($settings['CloseImage'] && file_exists($DOCUMENT_ROOT . $imagesPath . $settings['CloseImage'])) {
        $IMG_CLOSE = true;
    }
    else {
        $IMG_CLOSE = false;
    }

    $CalendarHeader = str_replace("%SELECT_MONTH", $month_select, $settings['CalendarHeader']);
    $CalendarHeader = str_replace("%SELECT_YEAR", $year_select, $CalendarHeader);

    $CalendarHeader = str_replace("%MONTH_NAME", $MonthArray[(int)$month], $CalendarHeader);
    $CalendarHeader = str_replace("%MONTH_DIGIT", $month, $CalendarHeader);
    $CalendarHeader = str_replace("%YEAR_DIGIT", $year, $CalendarHeader);

    if ($popup) {
        $CalendarHeader = str_replace("%IMG_STATUS", ($IMG_CLOSE ? "<img onclick='document.getElementById(\"nc_calendar_popup_" . nc_array_value($calendar_fields, 0) . "\").style.display=\"none\"; return false;' src='" . $imagesPath . $settings['CloseImage'] . "' style='display:block; cursor: pointer;' id='ImgClose' alt='" . NETCAT_MODULE_CALENDAR_CLOSE . "' title='" . NETCAT_MODULE_CALENDAR_CLOSE . "' />" : ""), $CalendarHeader);
    }
    else {
        $CalendarHeader = str_replace("%IMG_STATUS", ($IMG_STATUS ? "<img src='" . $imagesPath . $settings['StatusImage'] . "' style='display:none;' id='ImgWaiting' alt='waiting' title='waiting' />" : ""), $CalendarHeader);
    }

    if ($popup) {
        $CalendarHeader = str_replace("%IMG_PREV_MONTH", ($IMG_PREV_MONTH ? "<img src='" . $imagesPath . $settings['PrevImage'] . "' onclick='nc_calendar_generate_popup(" . ($day ? $day : 1) . ", " . ($month == 1 ? 12 : $month - 1) . ", " . ($month == 1 ? $year - 1 : $year) . ",  \"" . nc_array_value($calendar_fields, 0) . "\", \"" . nc_array_value($calendar_fields, 1) . "\",\"" . nc_array_value($calendar_fields, 2) . "\", \"" . $theme . "\"); return false;' alt='" . $MonthArray[$month == 1 ? 12 : $month - 1] . "' title='" . $MonthArray[$month == 1 ? 12 : $month - 1] . "' />" : "&lt;"), $CalendarHeader);
        $CalendarHeader = str_replace("%IMG_NEXT_MONTH", ($IMG_NEXT_MONTH ? "<img src='" . $imagesPath . $settings['NextImage'] . "' onclick='nc_calendar_generate_popup(" . ($day ? $day : 1) . ", " . ($month == 12 ? 1 : $month + 1) . ", " . ($month == 12 ? $year + 1 : $year) . ",  \"" . nc_array_value($calendar_fields, 0) . "\", \"" . nc_array_value($calendar_fields, 1) . "\",\"" . nc_array_value($calendar_fields, 2) . "\", \"" . $theme . "\"); return false;' alt='" . $MonthArray[$month == 12 ? 1 : $month + 1] . "' title='" . $MonthArray[$month == 12 ? 1 : $month + 1] . "' />" : "&gt;"), $CalendarHeader);
    }
    else {
        $CalendarHeader = str_replace("%IMG_PREV_MONTH", ($IMG_PREV_MONTH ? "<img src='" . $imagesPath . $settings['PrevImage'] . "' onclick='nc_calendar_generate(" . ($day ? $day : 1) . ", " . ($month == 1 ? 12 : $month - 1) . ", " . ($month == 1 ? $year - 1 : $year) . " " . ($cc_ignore ? ", true " : ", false ") . ", ".($admin_mode ? 1 : 0)."); return false;' alt='" . $MonthArray[$month == 1 ? 12 : $month - 1] . "' title='" . $MonthArray[$month == 1 ? 12 : $month - 1] . "' />" : "&lt;"), $CalendarHeader);
        $CalendarHeader = str_replace("%IMG_NEXT_MONTH", ($IMG_NEXT_MONTH ? "<img src='" . $imagesPath . $settings['NextImage'] . "' onclick='nc_calendar_generate(" . ($day ? $day : 1) . ", " . ($month == 12 ? 1 : $month + 1) . ", " . ($month == 12 ? $year + 1 : $year) . " " . ($cc_ignore ? ", true " : ", false ") . ", ".($admin_mode ? 1 : 0)."); return false;' alt='" . $MonthArray[$month == 12 ? 1 : $month + 1] . "' title='" . $MonthArray[$month == 12 ? 1 : $month + 1] . "' />" : "&gt;"), $CalendarHeader);
        $CalendarHeader = str_replace("%LINK_PREV_MONTH", ($IMG_PREV_MONTH ? "<a href='#' onclick='nc_calendar_generate(" . ($day ? $day : 1) . ", " . ($month == 1 ? 12 : $month - 1) . ", " . ($month == 1 ? $year - 1 : $year) . " " . ($cc_ignore ? ", true " : ", false ") . ", ".($admin_mode ? 1 : 0)."); return false;' title='" . $MonthArray[$month == 1 ? 12 : $month - 1] . "' ></a>" : "&lt;"), $CalendarHeader);
        $CalendarHeader = str_replace("%LINK_NEXT_MONTH", ($IMG_NEXT_MONTH ? "<a href='#' onclick='nc_calendar_generate(" . ($day ? $day : 1) . ", " . ($month == 12 ? 1 : $month + 1) . ", " . ($month == 12 ? $year + 1 : $year) . " " . ($cc_ignore ? ", true " : ", false ") . ", ".($admin_mode ? 1 : 0)."); return false;' title='" . $MonthArray[$month == 12 ? 1 : $month + 1] . "' ></a>" : "&gt;"), $CalendarHeader);
    }
    $DateFieldExist = true;
    $path = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . '?sub=' . (isset($_sub_id) ? $_sub_id : null) . '&amp;cc=' . (isset($_cc_id) ? $_cc_id : null);
    $CalendarHeader = str_replace("%MONTH_LINK",
        ($DateFieldExist && !$popup
            ? ( $admin_mode ?
                 "<a href='" . $path . '&amp;date=' . $year . "-" . sprintf("%02d", $month) . "'>"
               :
                "<a href='" . nc_folder_path($first_folder_id, sprintf("%d-%02d", $year, $month)) . "'>"
              )
            : "") .
        $MonthArray[(int)$month] .
        ($DateFieldExist ? "</a>" : ""),
        $CalendarHeader);

    $CalendarHeader = str_replace("%YEAR_LINK",
        ($DateFieldExist && !$popup
            ? "<a href='" . nc_folder_path($first_folder_id, $year) . "'></a>"
            : "") .
        sprintf("%04d", $year) .
        ($DateFieldExist ? "</a>" : ""),
        $CalendarHeader);


    if (!$setDate) {
        $calendarBlockBegin = "<div id='nc_calendar_block'>";
        $calendarBlockEnd = "</div>\r\n<input type='hidden' id='calendar_cc' value='" . join(',', $cc_ids) . "' />\r\n";
        $calendarBlockEnd .= "<input type='hidden' id='calendar_theme' value='" . $theme . "' />\r\n<input type='hidden' id='calendar_field' value='" . $DateField . "' />\r\n<input type='hidden' id='calendar_filled' value='" . $filled . "' />\r\n<input type='hidden' id='calendar_querydate' value='" . $queryDate . "' />\r\n";
    }

    # результат, html-код календаря
    $result = (isset($calendarBlockBegin) ? $calendarBlockBegin : '') . $settings['CalendarPrefix'] . $CalendarHeader . $calendarBody . $settings['CalendarSuffix'] . (isset($calendarBlockEnd) ? $calendarBlockEnd : '');

    return $result;
}

function nc_show_calendar_by_class($theme = 0, $classes = 0, $setDate = 0, $DateField = "Date", $filled = false, $queryDate = false) {
    global $db;

    if (!is_array($classes)) {
        $classes = array($classes);
    }
    $classes = array_map('intval', $classes);

    $cc_ids = $db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Class_ID` IN (" . join(',', $classes) . ") ");

    return nc_show_calendar($theme, $cc_ids, $setDate, $DateField, $filled, $queryDate);
}

function nc_calendar_cache() {
    global $MODULE_FOLDER;

    if (nc_module_check_by_keyword("cache")) {
        // include need classes
        include_once nc_module_folder('cache') . 'function.inc.php';
    }

    return;
}

/**
 * Проверка наличия в таблице MessageXX поля $DateField
 *
 * @param int $classID идентификатор компонента
 * @param string $DateField имя поля
 * @return bool
 */
function nc_calendar_possibility_check($classID, $DateField = "Date") {
    global $db;

    $classID = intval($classID);
    $DateField = $db->escape($DateField);

    if (!$classID || !$DateField) {
        return false;
    }

    $table_exist = $db->get_var("SHOW TABLES LIKE 'Message" . $classID . "'");
    if (!$table_exist) {
        return false;
    }
    $result = $db->get_var("SHOW COLUMNS FROM `Message" . $classID . "` LIKE '" . $DateField . "'");

    return $result;
}

