<?php

/**
 *
 */
class nc_routing_path_object extends nc_routing_path {

    protected $resource_type = 'object';
    protected $component_id, $object_data, $action, $format, $add_date, $query_variables;


    public function __construct($component_id, $object_data, $action = 'full', $format = 'html', $add_date = false, array $query_variables = null, $add_domain = false) {
        $this->component_id = $component_id;
        $this->object_data = $object_data;
        $this->action = $action;
        $this->format = $format;
        $this->add_date = $add_date;
        $this->query_variables = $query_variables;
        $this->add_domain = $add_domain;
    }

    protected function prepare_resource_parameters() {
        $component_id = $this->component_id;
        $object_data = $this->object_data ;
        $action = $this->action;
        $format = $this->format;
        $add_date = $this->add_date;
        $query_variables = $this->query_variables;

        $object_data_is_numeric = ((int)$object_data == $object_data);
        $object_data_is_array = !$object_data_is_numeric && is_array($object_data);

        if (((int)$component_id != $component_id && $component_id != 'User') || (!$object_data_is_array && !$object_data_is_numeric)) {
            return false;
        }

        if (!$action) {
            $action = 'full';
        }

        if ($object_data_is_array) {
            $object_params = $object_data;

            if (!isset($object_params['action']) || $object_params['action'] != $action) {
                $object_params['action'] = $action;
            }

            if (!isset($object_params['format']) || $object_params['format'] != $format) {
                $object_params['format'] = $format;
            }

            if (!$add_date) {
                $object_params['date'] = null;
            }

        } else { // it must be an ID then
            $nc_core = nc_core::get_object();
            if ($add_date) {
                $date_field = $nc_core->get_component($component_id)->get_date_field();
            } else {
                $date_field = false;
            }

            $db = $nc_core->db;
            $object_id = (int)$object_data;

            $object_data = $db->get_row(
                "SELECT `Message_ID`, `Sub_Class_ID`, `Keyword`" .
                        ($date_field ? ", `" . $db->escape($date_field) . "` AS `date`" : "") . "
                   FROM `Message$component_id`
                  WHERE `Message_ID` = $object_id",
                ARRAY_A);

            if (!is_array($object_data)) {
                return false;
            }

            try {
                $infoblock_settings = $nc_core->sub_class->get_by_id($object_data['Sub_Class_ID']);
            } catch (Exception $e) {
                return false;
            }

            $object_params = array(
                'site_id' => $infoblock_settings['Catalogue_ID'],
                'folder' => $infoblock_settings['Hidden_URL'],
                'folder_id' => $infoblock_settings['Subdivision_ID'],
                'infoblock_id' => $infoblock_settings['Sub_Class_ID'],
                'infoblock_keyword' => $infoblock_settings['EnglishName'],
                'object_id' => $object_data['Message_ID'],
                'object_keyword' => $object_data['Keyword'],
                'action' => $action,
                'format' => $format,
                'date' => $date_field ? $object_data['date'] : null, // проверяется и может быть сброшена в get_path_string()
                'variables' => null,
            );
        }

        if ($query_variables) {
            if (isset($object_params['variables'])) {
                $object_params['variables'] = array_merge($object_params['variables'], $query_variables);
            }
            else {
                $object_params['variables'] = $query_variables;
            }
        }

        return $object_params;
    }

}