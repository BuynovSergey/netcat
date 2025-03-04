<?php

class nc_version_record_infoblock extends nc_version_record {

    protected $entity_table = 'Sub_Class';
    static protected $ignore_field_changes = array(
        'Sub_Class_ID' => true,
        'Created' => true,
        'LastUpdated' => true,
        'ConditionQuery' => true,
        'HasFilter' => true,
    );

    /**
     * @return string
     */
    public function generate_description() {
        $action = $this->get_action_description();
        $name = $this->get_name_from_snapshot();

        if ($this->get_value_from_snapshot('Class_ID')) {
            return $action . ' ' . sprintf(NETCAT_VERSION_OF_BLOCK, $name);
        } else if ($name) {
            return $action . ' ' . sprintf(NETCAT_VERSION_OF_NAMED_CONTAINER, $name);
        } else {
            return $action . ' ' . NETCAT_VERSION_OF_CONTAINER;
        }
    }

    /**
     * @return array
     */
    protected function get_entity_snapshot_data() {
        $db = nc_db();
        $infoblock_id = $this->get('Sub_Class_ID');
        $tables = array(
            'Sub_Class' => 'Sub_Class_ID',
            'Sub_Class_AreaCondition_Subdivision' => 'Condition_ID',
            'Sub_Class_AreaCondition_Subdivision_Exception' => 'Condition_ID',
            'Sub_Class_AreaCondition_Class' => 'Condition_ID',
            'Sub_Class_AreaCondition_Class_Exception' => 'Condition_ID',
            'Sub_Class_AreaCondition_Message' => 'Condition_ID',
        );
        $result = array();
        foreach ($tables as $table => $primary_key) {
            $result[$table] = (array)$db->get_results(
                "SELECT * FROM `$table` WHERE `Sub_Class_ID` = $infoblock_id",
                ARRAY_A,
                $primary_key
            );
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function get_entity_current_files() {
        $nc_core = nc_core::get_object();

        try {
            $infoblock_id = $this->get('Sub_Class_ID');
            $cc_env = $nc_core->sub_class->get_by_id($infoblock_id);
        } catch (Exception $e) {
            return array();
        }

        return array_filter(
            // Файлы из пользовательских настроек инфоблока
            $this->get_entity_files_from_settings('class_settings', $infoblock_id, nc_array_value($cc_env, 'CustomSettingsTemplate'), nc_array_value($cc_env, 'CustomSettings')) +
            // Файлы из настроек миксинов
            $this->get_entity_files_from_mixin_settings('Index_Mixin_Settings', $cc_env['Index_Mixin_Settings']) +
            $this->get_entity_files_from_mixin_settings('IndexItem_Mixin_Settings', $cc_env['IndexItem_Mixin_Settings'])
        );
    }

    /**
     * @return bool
     */
    protected function parent_entity_exists() {
        $nc_core = nc_core::get_object();
        $infoblock_id = $this->get('Sub_Class_ID');

        $snapshot = $this->get('Snapshot');
        $parent_infoblock_id = $snapshot['Sub_Class'][$infoblock_id]['Parent_Sub_Class_ID'];

        if ($parent_infoblock_id) {
            try {
                return (bool)$nc_core->sub_class->get_by_id($parent_infoblock_id, 'Sub_Class_ID');
            } catch (Exception $e) {
                return false;
            }
        }

        try {
            return (bool)$nc_core->subdivision->get_by_id($this->get('Subdivision_ID'), 'Subdivision_ID');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function entity_exists() {
        try {
            return (bool)nc_core::get_object()->sub_class->get_by_id($this->get('Sub_Class_ID'), 'Sub_Class_ID');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     *
     */
    protected function delete_entity() {
        nc_core::get_object()->sub_class->delete($this->get('Sub_Class_ID'));
    }

    /**
     *
     */
    protected function prepare_version_restore() {
        $nc_core = nc_core::get_object();
        $condition_tables = array(
            'Sub_Class_AreaCondition_Subdivision',
            'Sub_Class_AreaCondition_Subdivision_Exception',
            'Sub_Class_AreaCondition_Class',
            'Sub_Class_AreaCondition_Class_Exception',
            'Sub_Class_AreaCondition_Message',
        );
        $infoblock_id = $this->get('Sub_Class_ID');
        foreach ($condition_tables as $table_name) {
            $nc_core->db->query("DELETE FROM `$table_name` WHERE `Sub_Class_ID` = $infoblock_id");
        }
    }

    /**
     *
     */
    protected function finalize_version_restore() {
        $id = $this->get('Sub_Class_ID');

        // Удаление сгенерированных изображений
        $this->remove_generated_settings_images('Sub_Class', $id);

        // Пересборка файлов миксинов
        $changes = $this->get_changes();

        if (!empty($changes['Parent_Sub_Class_ID'])) {
            nc_tpl_mixin_cache::generate_block_files($id, true);
        } else {
            foreach ($changes as $key => $value) {
                if (strpos($key, 'Mixin') !== false) {
                    nc_tpl_mixin_cache::generate_block_files($id, false);
                    break;
                }
            }
        }
    }

}
