<?php

/**
 *
 */
class nc_routing_backup extends nc_backup_extension {

    /**
     * @param string $type
     * @param int $id
     */
    public function export($type, $id) {
        if ($type != 'site') { return; }

        $routes = nc_db_table::make('Routing_Route')->where('Site_ID', $id)->get_result();
        $this->dumper->export_data('Routing_Route', 'Route_ID', $routes);
    }

    /**
     * @param string $type
     * @param int $id
     */
    public function import($type, $id) {
        if ($type != 'site') { return; }

        $this->dumper->import_data('Routing_Route', null, array('Site_ID' => 'Catalogue_ID'));
    }

}