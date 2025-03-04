<?php

class nc_routing_listener {

    public static function init() {
        $listener = new self;
        $event_manager = nc_core::get_object()->event;
        $event_manager->bind($listener, array(nc_Event::AFTER_SITE_IMPORTED => 'create_site'));
        $event_manager->bind($listener, array(nc_Event::AFTER_SITE_CREATED => 'create_site'));
        $event_manager->bind($listener, array(nc_Event::AFTER_SITE_DELETED => 'delete_site'));
    }

    public function create_site($site_id) {
        nc_core('catalogue')->load_all();
        nc_routing_route_defaults::create($site_id);
    }

    public function delete_site($site_id) {
        nc_routing::get_routes($site_id, true)->each('delete');
    }

}