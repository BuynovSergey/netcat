<?php
/**
 * Слушатель системных событий
 */
class nc_auth_listener {
    public static function init() {
        $listener = new self;
        $event_manager = nc_core::get_object()->event;
        $event_manager->add_listener(nc_event::AFTER_SITE_CREATED, array($listener, 'after_site_created'));
    }
    /**
     * Создание раздела «Личный кабинет» при создании сайта
     * @param $site_id
     */
    public function after_site_created($site_id) {
        nc_auth::get_object()->create_auth_subdivisions($site_id);
    }
}