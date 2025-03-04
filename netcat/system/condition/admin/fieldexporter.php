<?php

/**
 * Класс, формирующий список полей объектов (admin/condition/data/json/component_field_list.php)
 */
class nc_condition_admin_fieldexporter {
    protected $fields;

    public function __construct($sub_class_id) {
        $this->load_field_data($sub_class_id);
    }

    protected function load_field_data($sub_class_id) {
        try {
            $class_id = nc_Core::get_object()->sub_class->get_by_id($sub_class_id, 'Class_ID');

            /* @var nc_db $db */
            $db = nc_core('db');

            $this->fields = $db->get_results(
                "SELECT `Class_ID`, `Field_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`
                   FROM `Field`
                  WHERE `Class_ID` = $class_id
                    AND `Checked` = 1
                    AND " . nc_condition_admin_helpers::get_field_types_to_export_for_query() . "
                  ORDER BY `Priority`",
                ARRAY_A
            );

            if (!$this->fields) {
                trigger_error("Unable to retrieve field data; check if component exist.", E_USER_ERROR);
            }
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    protected function get_component_fields() {
        // FIELDS BY COMPONENT TYPE
        $result = array();

        foreach ($this->fields as $field) {
            $result[NETCAT_CONDITION_COMMON_FIELDS][] = nc_condition_admin_helpers::export_field($field);
        }

        return $result;
    }

    public function export() {
        return $this->get_component_fields();
    }

}
