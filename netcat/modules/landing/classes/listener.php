<?php

/**
 * Слушатель системных событий
 */
class nc_landing_listener {

    public static function init() {
        $listener = new self;
        $event_manager = nc_core::get_object()->event;
        $event_manager->add_listener(nc_event::AFTER_SITE_IMPORTED, array($listener, 'after_site_created'));
        $event_manager->add_listener(nc_event::AFTER_SITE_CREATED, array($listener, 'after_site_created'));
        $event_manager->add_listener(nc_event::AFTER_SUBDIVISION_DELETED, array($listener, 'after_subdivision_deleted'));
        $event_manager->add_listener(nc_event::AFTER_INFOBLOCK_DELETED, array($listener, 'after_infoblock_deleted'));
    }

    /**
     * Создание раздела «Промо-страницы» при создании сайта
     * @param $site_id
     */
    public function after_site_created($site_id) {
        nc_landing::get_instance($site_id)->get_landings_subdivision_id(true);
    }

    /**
     * Удаление записи о лендинг-странице при удалении раздела с ней
     * @param $site_id
     * @param $subdivision_id
     */
    public function after_subdivision_deleted($site_id, $subdivision_id) {
        $db = nc_db();
        foreach ((array)$subdivision_id as $id) {
            $landing_page_id = $db->get_var("SELECT `Landing_Page_ID` FROM `Landing_Page` WHERE `Subdivision_ID` = " . (int)$id);                   
            if ($landing_page_id) {
                $db->query("DELETE FROM `Landing_Page_Block` WHERE `Landing_Page_ID` = " . (int)$landing_page_id);
                $db->query("DELETE FROM `Landing_Page` WHERE `Landing_Page_ID` = " . (int)$landing_page_id);
            }
        }
    }

    /**
     * Удаление записи о блоке лендинг-страницы при его удалении
     * @param $site_id
     * @param $subdivision_id
     * @param $infoblock_id
     */
    public function after_infoblock_deleted($site_id, $subdivision_id, $infoblock_id) {
        nc_db()->query("DELETE FROM `Landing_Page_Block` WHERE `Infoblock_ID` IN (" . implode(', ', (array)$infoblock_id) . ")");
    }

}
