<?php

/**
 *
 */
class nc_routing_path_folder extends nc_routing_path {

    protected $resource_type = 'folder';
    protected $folder_id, $date, $query_variables;


    public function __construct($folder_id, $date = null, array $query_variables = null) {
        $this->folder_id = $folder_id;
        $this->date = $date;
        $this->query_variables = $query_variables;
    }

    public function get_folder_id() {
        return $this->folder_id;
    }

    protected function prepare_resource_parameters() {
        try {
            $folder_settings = nc_core::get_object()->subdivision->get_by_id($this->folder_id);
        }
        catch (Exception $e) {
            return false;
        }

        $folder_params = array(
            'site_id' => $folder_settings['Catalogue_ID'],
            'folder' => $folder_settings['Hidden_URL'],
            'folder_id' => $folder_settings['Subdivision_ID'],
            'action' => 'index',
            'format' => 'html',
            'date' => $this->date, // проверяется и может быть сброшена в get_path_string()
            'variables' => $this->query_variables,
        );

        return $folder_params;
    }
}