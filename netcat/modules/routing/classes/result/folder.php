<?php

class nc_routing_result_folder extends nc_routing_result {

    protected $parameters = array(
        'action' => '',
        'format' => 'html',
    );

    protected $match_parameters = array(
        'folder_id',
        'infoblock_id',
        'action',
        'format',
        'date'
    );

    /**
     *
     */
    protected function has_all_resource_parameters() {
        return (bool)$this->get_resource_parameter('folder_id');
    }

}