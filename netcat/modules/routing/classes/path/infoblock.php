<?php

/**
 *
 */
class nc_routing_path_infoblock extends nc_routing_path {

    protected $resource_type = 'infoblock';
    protected $infoblock_id, $action, $format, $date, $query_variables;


    public function __construct($infoblock_id, $action = '', $format = 'html', $date = null, array $query_variables = null) {
        $this->infoblock_id = $infoblock_id;
        $this->action = $action;
        $this->format = $format;
        $this->date = $date;
        $this->query_variables = $query_variables;
    }

    protected function prepare_resource_parameters() {
        if ($this->resource_parameters) { return $this->resource_parameters; }

        try {
            $infoblock_settings = nc_core::get_object()->sub_class->get_by_id($this->infoblock_id);
        }
        catch (Exception $e) {
            return false;
        }

        $infoblock_params = array(
            'site_id' => $infoblock_settings['Catalogue_ID'],
            'folder' => $infoblock_settings['Hidden_URL'],
            'folder_id' => $infoblock_settings['Subdivision_ID'],
            'infoblock_id' => $infoblock_settings['Sub_Class_ID'],
            'infoblock_keyword' => $infoblock_settings['EnglishName'],
            'action' => $this->action ? $this->action : $infoblock_settings['DefaultAction'],
            'format' => $this->format,
            'date' => $this->date, // проверяется и может быть сброшена в get_path_string()
            'variables' => $this->query_variables,
        );

        return $infoblock_params;
    }

}