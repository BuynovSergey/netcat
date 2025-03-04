<?php

class nc_routing_result_object extends nc_routing_result {

    protected $parameters = array(
        'action' => 'full',
        'format' => 'html',
    );

    protected $match_parameters = array(
        'infoblock_id',
//        'object_id OR object_keyword',
        'action',
        'format',
        'date'
    );

    /**
     *
     */
    protected function has_all_resource_parameters() {
        return $this->get_infoblock_id() &&
               $this->get_resource_parameter('object_id');
    }

}