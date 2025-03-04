<?php

class nc_permission_listener {

    /**
     * Инициализация слушателей удаления прав
     */
    public static function init() {
        $event = nc_core::get_object()->event;
        $event->add_listener(nc_event::AFTER_SITE_DELETED, array(__CLASS__, 'after_site_deleted'));
        $event->add_listener(nc_event::AFTER_SUBDIVISION_DELETED, array(__CLASS__, 'after_subdivision_deleted'));
        $event->add_listener(nc_event::AFTER_INFOBLOCK_DELETED, array(__CLASS__, 'after_infoblock_deleted'));
        $event->add_listener(nc_event::AFTER_USER_DELETED, array(__CLASS__, 'after_user_deleted'));
    }

    /**
     * Удаление прав, связанных с удаляемым сайтом
     *
     * @param int|int[] $site_ids
     */
    public static function after_site_deleted($site_ids) {
        $db = nc_db();
        $site_ids = array_filter(array_map('intval', (array)$site_ids));
        if ($site_ids) {
            $site_list = implode(', ', $site_ids);
            $admin_types = CATALOGUE_ADMIN . ', ' . CATALOGUE_CONTENT_ADMIN;
            $db->query("DELETE FROM `Permission` WHERE `Catalogue_ID` IN ($site_list) AND `AdminType` IN ($admin_types)");
            $db->query("DELETE FROM `Module_Permission` WHERE `Catalogue_ID` IN ($site_list)");
        }
    }

    /**
     * Удаление прав, связанных с удаляемыми разделами
     *
     * @param int $site_id
     * @param int|int[] $subdivision_ids
     */
    public static function after_subdivision_deleted($site_id, $subdivision_ids) {
        $subdivision_ids = array_filter(array_map('intval', (array)$subdivision_ids));
        if ($subdivision_ids) {
            nc_db()->query(
                'DELETE FROM `Permission` 
                  WHERE `Catalogue_ID` IN (' . implode(', ', $subdivision_ids) . ") 
                    AND `AdminType` = '" . SUBDIVISION_ADMIN . "'"
            );
        }
    }

    /**
     * Удаление прав, связанных с удаляемыми инфоблоками
     *
     * @param int $site_id
     * @param array $subdivision_id
     * @param array $infoblock_ids
     */
    public static function after_infoblock_deleted($site_id, $subdivision_id, $infoblock_ids) {
        $infoblock_ids = array_filter(array_map('intval', (array)$infoblock_ids));
        if ($infoblock_ids) {
            nc_db()->query(
                'DELETE FROM `Permission` 
                  WHERE `Catalogue_ID` IN (' . implode(', ', $infoblock_ids) . ") 
                    AND `AdminType` = '" . SUB_CLASS_ADMIN . "'"
            );
        }
    }

    /**
     * Удаление прав удаляемых пользователей
     *
     * @param int|int[] $user_ids
     */
    public static function after_user_deleted($user_ids) {
        $user_ids = array_filter(array_map('intval', $user_ids));
        if ($user_ids) {
            nc_db()->query(
                'DELETE FROM `Permission` WHERE `User_ID` IN (' . implode(', ', $user_ids) . ')'
            );
        }
    }

}