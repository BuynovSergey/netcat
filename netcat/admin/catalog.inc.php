<?php

/**
 * Удаляет каталог и все его содержимое
 *
 * @param int $catalogue_id
 * @throws nc_Exception_DB_Error|Exception
 */
function CascadeDeleteCatalogue($catalogue_id) {
    $nc_core = nc_Core::get_object();

    $catalogue_id = (int)$catalogue_id;
    $sub_classes = $nc_core->db->get_col(
        "SELECT `Sub_Class_ID`
         FROM `Sub_Class`
         WHERE `Catalogue_ID` = {$catalogue_id}
         AND `Parent_Sub_Class_ID` = 0"
    );
    $subdivisions = $nc_core->db->get_col("SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Catalogue_ID` = {$catalogue_id}");

    // Необходимо для предотвращения создания отдельных changeset на каждое вложенное действие(удаление раздела, инфоблока и тд), связанное с удалением сайта
    $nc_core->version->prevent_reopen_changeset();
    $nc_core->event->execute(nc_Event::BEFORE_SITE_DELETED, $catalogue_id);

    foreach ($sub_classes as $sub_class_id) {
        // Удаление рекурсивное, нужно указывать корневые инфоблоки
        try {
            $nc_core->sub_class->delete($sub_class_id);
        } catch (Exception $e) {
            continue;
        }
    }
   
    foreach ($subdivisions as $subdivision_id) {
        $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_DELETED, $catalogue_id, $subdivision_id);
        $nc_core->db->query("DELETE FROM `Subdivision` WHERE `Subdivision_ID` = {$subdivision_id}");

        DeleteSubdivisionDir($subdivision_id);
        $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_DELETED, $catalogue_id, $subdivision_id);
    }

    if (nc_module_check_by_keyword('comments')) {
        include_once nc_module_folder('comments') . 'function.inc.php';
        nc_comments::dropRule($nc_core->db, array($catalogue_id));
        nc_comments::dropComments($nc_core->db, $catalogue_id, 'Catalogue');
    }

    $nc_core->db->query("DELETE FROM `Catalogue` WHERE `Catalogue_ID` = {$catalogue_id}");
    $nc_core->event->execute(nc_Event::AFTER_SITE_DELETED, $catalogue_id);
    $nc_core->db->query("DELETE FROM `Module_Catalog` WHERE `Catalogue_ID` = {$catalogue_id}");

    nc_tpl_mixin_cache::delete_all_site_files($catalogue_id);

    if ($catalogue_id > 0) {
        $nc_core->db->query("DELETE FROM `Settings` WHERE `Catalogue_ID` = {$catalogue_id}");
    }
}

/**
 * Подсчитывает число подразделов сайта с заданным $CatalogueID
 *
 * @global type $db
 * @global type $perm
 * @param type $CatalogueID
 * @param type $available
 * @return type
 */
function HighLevelChildrenNumber($CatalogueID, $available = '') {
    global $db, $perm;

    // часть sql-запроса, ограничивающая выборку только объектами, которые пользователь может видеть
    $security_limit = '';

    // id разделов, которые пользователь может администрировать
    $sub_admin = $perm->listItems('subdivision');

    // id шаблонов в разделе, которые пользователь может администрировать
    $cc_admin = $perm->listItems('subclass');

    // id сайтов, которые пользователь видит (на основе $site_admin, $sub_admin, $cc_admin)
    $allowed_sites = array();

    // id разделов, которые администрирует пользователь, на основе $sub_admin + $cc_admin
    $sub_and_cc_admin = $sub_admin;
    if (is_array($cc_admin) && !empty($cc_admin)) {
        $in_str = join(', ', $cc_admin);
        if ($in_str) {
            $res = $db->get_results("SELECT `Subdivision_ID`
        FROM `Sub_Class`
        WHERE `Sub_Class_ID` IN ($in_str)", ARRAY_A);
            if (!empty($res)) {
                foreach ($res as $row) {
                    $sub_and_cc_admin[] = $row['Subdivision_ID'];
                }
            }
        }
    }

    if (is_array($sub_and_cc_admin) && !empty($sub_and_cc_admin)) {
        // получить родительские разделы для разделов, которые пользователь может
        // модерировать или администрировать
        $res = $db->get_results("SELECT parent.`Subdivision_ID`
      FROM `Subdivision` as parent, `Subdivision` as allowed
      WHERE allowed.`Subdivision_ID` IN (" . join(",", array_unique($sub_and_cc_admin)) . ")
      AND allowed.`Hidden_URL` LIKE CONCAT(parent.`Hidden_URL`, '%')", ARRAY_A);

        // разделы, которые пользователь может видеть
        $allowed_subs = array();
        if (!empty($res)) {
            foreach ($res as $row) {
                // flatten array
                $allowed_subs[] = $row['Subdivision_ID'];
            }
        }

        // id разделов, которые являются дочерними для тех разделов, на которые
        // явно указаны права на администрирование -- эти права наследуются (as of 3.0)
        $sub_child_administrator = array();
        // права наследуются для дочерних узлов
        if (is_array($sub_admin) && !empty($sub_admin)) {
            $res = $db->get_results("SELECT child.`Subdivision_ID`, allowed.`Subdivision_ID` as Allowed_Subdivision_ID
          FROM `Subdivision` as child, `Subdivision` as allowed
          WHERE allowed.`Subdivision_ID` IN (" . join(",", array_unique($sub_admin)) . ")
          AND child.`Hidden_URL` LIKE CONCAT(allowed.`Hidden_URL`, '_%')", ARRAY_A);
        }

        if (!empty($res)) {
            foreach ($res as $row) {
                $allowed_subs[] = $row['Subdivision_ID'];
                $sub_child_administrator[$row['Subdivision_ID']] = $row['Allowed_Subdivision_ID'];
            }
        }

        if ($allowed_subs) {
            $qry_where = " AND a.Subdivision_ID IN (" . join(',', $allowed_subs) . ") ";
        }
    }

    return $db->get_var("SELECT COUNT(`Subdivision_ID`) FROM `Subdivision` AS a WHERE `Parent_Sub_ID` = 0
    AND `Catalogue_ID` = '" . intval($CatalogueID) . "'" . ($available && $qry_where ? $qry_where : ''));
}

/**
 * Проверяет домен $Domain на дубль среди сайтов системы, отличных от $CatalogueID
 *
 * @global type $db
 * @param type $Domain
 * @param type $CatalogueID
 * @return type
 */
function IsAllowedDomain($Domain, $CatalogueID) {
    global $db;

    return !$db->get_var("SELECT `Catalogue_ID` FROM `Catalogue` WHERE `Domain` = '" . $db->escape($Domain) . "'
    AND `Catalogue_ID` <> '" . intval($CatalogueID) . "'");
}
