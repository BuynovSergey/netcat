<?php

if (!class_exists('nc_System')) {
    die('Unable to load file.');
}

global $MODULE_FOLDER;
include_once nc_module_folder('tagscloud') . 'nc_tags.class.php';

global $nc_tags;
$nc_tags = new nc_tags();

/**
 * функция добавления тегов
 *
 * @deprecated
 */
function nc_tag_add($Sub_ID, $Sub_Class_ID, $Message_ID, $tags_string) {
    return false;
}

/**
 * функция изменения тегов
 *
 * @deprecated
 */
function nc_tag_edit($Sub_Class_ID, $Message_ID, $tag = '') {
    return false;
}

/**
 * функция удаления тегов
 *
 * @deprecated
 */
function nc_tag_drop($Sub_Class_ID, $Message_ID, $tag = '') {
    return false;
}

/**
 * функция сопосоставления тегу размера шрифта в пикселях
 * @param int $Value сколько раз встречается тег во всех инфоблоках
 * @param int $Max сколько максимально раз встречается тег среди всех инфоблоков
 * @param int $Min сколько минимально раз встречается тег среди всех инфоблоков
 * @param int $Sum общее число использования тегов
 * @return
 */
function Tag_Size($Value, $Max, $Min, $Sum) {
    global $MODULE_VARS;

    $maxFont = $MODULE_VARS['tagscloud']['MAX_FONT'];
    $minFont = $MODULE_VARS['tagscloud']['MIN_FONT'];
    $fontrange = ($maxFont - $minFont) ?: 1;
    $px = min($fontrange, ($Value - $Min) / max(1, $Max - $Min) * $fontrange);

    return $minFont + round($px);
}

/**
 * функция генерации облака тегов
 *
 * @param array $Sub_Class_ID массив из ID инфоблоков
 * @param string $design шаблон вывода
 * @param string $address_str query_string для тегов
 * @param string $quantity максимум тегов для вывода
 * @return string облако тегов
 */
function nc_tag_cloud($Sub_Class_ID, $design, $address_str = '', $quantity = '') {
    global $db, $MODULE_VARS;
    $cloud = '';

    if (!$Sub_Class_ID) {
        return $cloud;
    }

    $sub_class_ids = implode(',', (array)$Sub_Class_ID);
    $class_ids = $db->get_col("SELECT DISTINCT Class_ID FROM Sub_Class WHERE Sub_Class_ID IN ({$sub_class_ids})");
    $tags_with_checked_messages = array();

    if ($class_ids) {
        foreach ($class_ids as $class_id) {
            $checked_message_ids = $db->get_col(
                "SELECT Message_ID
                 FROM Message{$class_id}
                 WHERE Message_ID IN (
                     SELECT DISTINCT Message_ID
                     FROM Tags_Message
                     WHERE Class_ID = {$class_id})
                 AND Checked = 1"
            );

            if (!$checked_message_ids) {
                continue;
            }

            foreach ($checked_message_ids as $message_id) {
                $tags_with_checked_messages[] = $class_id . ':' . $message_id;
            }
        }
    }

    $quantity_limit = $MODULE_VARS['tagscloud']['QUANTITY'];

    if ($quantity) {
        $quantity_limit = $quantity;
    }

    if (!$tags_with_checked_messages) {
        return $cloud;
    }

    $tag_variants = "'" . implode("','", $tags_with_checked_messages) . "'";
    $popular_tags = $db->get_results(
        "SELECT T.`Tag_Text`, T.`Tag_ID`, SUM(W.`Tag_Weight`) AS Tag_Count, W.*
         FROM `Tags_Weight` AS W
         LEFT JOIN `Tags_Data` AS T ON T.`Tag_ID` = W.`Tag_ID`
         LEFT JOIN `Tags_Message` AS TM ON TM.`Tag_ID` = W.`Tag_ID`
         WHERE W.`Sub_Class_ID` IN ({$sub_class_ids}) AND CONCAT_WS(':', TM.`Class_ID`, TM.`Message_ID`) IN ({$tag_variants})
         GROUP BY T.`Tag_ID`
         ORDER BY `Tag_Count` DESC
         LIMIT {$quantity_limit}",
        ARRAY_A
    );

    if (!$popular_tags) {
        return $cloud;
    }

    // сортируем по первому значению массива - Tag_Text
    sort($popular_tags);

    $Max_Count = $Min_Count = $popular_tags[0]['Tag_Count'];
    $Sum_Count = 0;

    foreach ($popular_tags AS $popular_tag) {
        $Sum_Count += $popular_tag['Tag_Count'];
        if ($popular_tag['Tag_Count'] > $Max_Count) {
            $Max_Count = $popular_tag['Tag_Count'];
        }

        if ($popular_tag['Tag_Count'] < $Min_Count) {
            $Min_Count = $popular_tag['Tag_Count'];
        }
    }

    $i = 0;

    while ($i < count($popular_tags)) {
        $Tag_Size = Tag_Size($popular_tags[$i]['Tag_Count'], $Max_Count, $Min_Count, $Sum_Count);

        $temp_cloud = str_replace('%TAG_HEIGHT', $Tag_Size, $design);
        $temp_cloud = str_replace('%TAG_LINK', '?tag=' . $popular_tags[$i]['Tag_ID'], $temp_cloud);
        $temp_cloud = str_replace('%TAG_ID', $popular_tags[$i]['Tag_ID'], $temp_cloud);
        $temp_cloud = str_replace('%TAG_SUB_LINK', $address_str, $temp_cloud);
        $temp_cloud = str_replace('%TAG_NAME', $db->escape($popular_tags[$i]['Tag_Text']), $temp_cloud);

        $temp_cloud = str_replace('$', '&#36;', $temp_cloud);
        eval(nc_check_eval("\$cloud.=\"$temp_cloud\";"));

        // разделяем пробелом
        if ((count($popular_tags) - 1) !== $i) {
            $cloud .= ' ';
        }

        ++$i;
    }

    return $cloud;
}

/**
 * функция вывода всех тегов сайта или сайтов
 *
 * @param int|array $site_ID массив из ID сайтов или ID сайта
 * @param string $design шаблон вывода
 * @param int $quantity максимум тегов для вывода
 * @return string облако тегов
 */
function nc_tag_cloud_all($site_ID = 0, $design, $quantity = 0) {
    global $db;

    $quantity = (int)$quantity;
    $sql_site_condition = '';

    if ($site_ID) {
        $site_ID = array_map('intval', (array)$site_ID);
        $sql_site_condition = 'WHERE Catalogue_ID IN (' . implode(',', $site_ID) . ')';
    }

    $Sub_Class_ID = $db->get_col('SELECT Sub_Class_ID FROM Sub_Class ' . $sql_site_condition);

    return nc_tag_cloud($Sub_Class_ID, $design, '', max(0, $quantity));
}

/**
 * функция вывода облака из разделов по Subdivision_ID
 *
 * @param int|array $Sub_ID массив из ID разделов или ID раздела
 * @param string $design шаблон вывода
 * @param int $quantity максимум тегов для вывода
 * @return bool|string облако тегов
 */
function nc_tag_cloud_subdivision($Sub_ID, $design, $quantity = 0) {
    global $db;

    if (!$Sub_ID) {
        return false;
    }

    $Sub_ID = array_map('intval', (array)$Sub_ID);
    $quantity = (int)$quantity;
    $address_str = $address_str = '&amp;tagsub=' . $Sub_ID[0];

    if (count($Sub_ID) > 1) {
        foreach ($Sub_ID AS $key => $value) {
            $address_str .= "&amp;tagsub[{$key}]=" . $value;
        }
    }

    // проходимся рекурсией по дереву
    $subArray = $Sub_ID;

    while ($tSub = $db->get_col('SELECT Subdivision_ID FROM Subdivision WHERE Parent_Sub_ID IN (' . implode(',', $Sub_ID) . ')')) {
        $subArray = array_merge($subArray, $tSub);
        $Sub_ID = $tSub;
    }

    $subdivision_ids = implode(',', $subArray);
    $Sub_Class_ID = $db->get_col("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID IN ({$subdivision_ids})");

    return nc_tag_cloud($Sub_Class_ID, $design, $address_str, max(0, $quantity));
}

/**
 * функция вывода облака из шаблонов в разделе по Sub_Class_ID
 * @param int|array $Sub_Class_ID массив из ID инфоблоков или ID инфоблока
 * @param string $design шаблон вывода
 * @param int $quantity максимум тегов для вывода
 * @return bool|string облако тегов
 */
function nc_tag_cloud_sub_class($Sub_Class_ID, $design, $quantity = 0) {
    if (!$Sub_Class_ID) {
        return false;
    }

    $Sub_Class_ID = array_map('intval', (array)$Sub_Class_ID);
    $quantity = (int)$quantity;
    $address_str = '&amp;tagcc=' . $Sub_Class_ID[0];

    if (count($Sub_Class_ID) > 1) {
        foreach ($Sub_Class_ID AS $key => $value) {
            $address_str .= "&amp;tagcc[{$key}]=" . $value;
        }
    }

    return nc_tag_cloud($Sub_Class_ID, $design, $address_str, max(0, $quantity));
}

/**
 * функция вывода результатов выборки по тегу, возвращает массив нужных данных
 *
 * @param int $tag ID тега
 * @param array|int $sub ID разделов
 * @param array|int $cc ID инфоблоков
 * @param int $site ID сайта
 * @return bool|array
 */
function nc_tag_cloud_show_result($tag, $sub = array(), $cc = array(), $site = 0) {
    global $db;

    $site = (int)$site;
    $tag = (int)$tag;
    if ($sub) {
        $sub = (array)$sub;
        $sub = array_map('intval', $sub);
    }
    if ($cc) {
        $cc = (array)$cc;
        $cc = array_map('intval', $cc);
    }
    if (!$tag && !$sub && !$cc) {
        return false;
    }

    $messages_array = null;

    if ($sub) {
        // проходимся рекурсией по дереву
        $subArray = $sub;

        while ($tSub = $db->get_col('SELECT Subdivision_ID FROM Subdivision WHERE Parent_Sub_ID IN (' . implode(',', $sub) . ')')) {
            $subArray = array_merge($subArray, $tSub);
            $sub = $tSub;
        }

        $subdivision_ids = implode(',', $subArray);

        $Sub_Classes = $db->get_col("SELECT DISTINCT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID IN ({$subdivision_ids})");

        if (!$Sub_Classes) {
            return false;
        }

        $sub_class_ids = implode(',', $Sub_Classes);
    } elseif ($cc) {
        $sub_class_ids = implode(',', $cc);
    } else {
        $Sub_Classes = $db->get_col("SELECT DISTINCT Sub_Class_ID FROM Tags_Message WHERE Tag_ID = {$tag}");

        if (!$Sub_Classes) {
            return false;
        }

        $sub_class_ids = implode(',', $Sub_Classes);
    }

    if ($sub_class_ids) {
        $sql_site_condition = $site ? ' AND s.Catalogue_ID = ' . $site : '';
        $message_info = $db->get_results(
            "SELECT sc.Subdivision_ID, s.Subdivision_Name, sc.Sub_Class_ID, ts.Message_ID
			 FROM (Sub_Class AS sc, Tags_Message AS ts)
			 LEFT JOIN Subdivision AS s ON sc.Subdivision_ID = s.Subdivision_ID
			 WHERE sc.Sub_Class_ID IN ($sub_class_ids) AND sc.Sub_Class_ID = ts.Sub_Class_ID AND ts.Tag_ID = $tag
             $sql_site_condition
			 ORDER BY sc.Subdivision_ID, sc.Sub_Class_ID, ts.Message_ID DESC",
            ARRAY_A
        );

        if ($message_info) {
            foreach ($message_info AS $key => $value) {
                $messages_array[] = array(
                    'Subdivision_ID'   => $value['Subdivision_ID'],
                    'Subdivision_Name' => $value['Subdivision_Name'],
                    'Sub_Class_ID'     => $value['Sub_Class_ID'],
                    'Message_ID'       => $value['Message_ID']
                );
            }
        }
    }

    $tagText = $db->get_var("SELECT Tag_Text FROM Tags_Data WHERE Tag_ID = {$tag}");

    return array($messages_array, $tagText);
}

?>