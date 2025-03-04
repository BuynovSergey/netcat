<?php

class nc_routing_result_infoblock extends nc_routing_result {

    protected $parameters = array(
        'action' => '',
        'format' => 'html',
    );

    protected $match_parameters = array(
        'infoblock_id',
        'action',
        'format',
        'date'
    );

    /**
     *
     */
    protected function has_all_resource_parameters() {
        return (bool)$this->get_infoblock_id();
    }


}