<?php

class nc_version_record_subdivision extends nc_version_record {

    protected $entity_table = 'Subdivision';
    static protected $ignore_field_changes = array(
        'Subdivision_ID' => true,
        'Created' => true,
        'LastUpdated' => true,
        'LastModified' => true,
        'Hidden_URL' => true,
    );

    /**
     * @return string
     */
    public function generate_description() {
        return $this->get_action_description() . ' ' . sprintf(NETCAT_VERSION_OF_SUBDIVISION, $this->get_name_from_snapshot());
    }

    /**
     * @return bool
     */
    protected function parent_entity_exists() {
        $nc_core = nc_core::get_object();
        $subdivision_id = $this->get('Subdivision_ID');
        $snapshot = $this->get('Snapshot');
        $parent_subdivision_id = $snapshot['Subdivision'][$subdivision_id]['Parent_Sub_ID'];
        if ($parent_subdivision_id) {
            try {
                return (bool)$nc_core->subdivision->get_by_id($parent_subdivision_id, 'Subdivision_ID');
            } catch (Exception $e) {
                return false;
            }
        }
        try {
            return (bool)$nc_core->catalogue->get_by_id($this->get('Catalogue_ID'));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function entity_exists() {
        try {
            return (bool)nc_core::get_object()->subdivision->get_by_id($this->get('Subdivision_ID'), 'Subdivision_ID');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     *
     */
    protected function delete_entity() {
        CascadeDeleteSubdivision($this->get('Subdivision_ID'));
    }

    /**
     * @return array
     */
    protected function get_entity_snapshot_data() {
        $subdivision_id = $this->get('Subdivision_ID');
        $subdivision_data = (array)nc_db()->get_results(
            "SELECT * FROM `Subdivision` WHERE `Subdivision_ID` = $subdivision_id",
            ARRAY_A,
            'Subdivision_ID'
        );

        return array(
            'Subdivision' => $subdivision_data,
            'Filetable' => $this->get_entity_snapshot_file_data('Subdivision', $subdivision_id, 'Filetable', NC_FIELDTYPE_FILE),
            'Multifield' => $this->get_entity_snapshot_file_data('Subdivision', $subdivision_id, 'Multifield', NC_FIELDTYPE_MULTIFILE),
        );
    }

    /**
     * @return array
     */
    protected function get_entity_current_files() {
        $nc_core = nc_core::get_object();

        try {
            $subdivision_id = $this->get('Subdivision_ID');
            $settings = $nc_core->subdivision->get_by_id($subdivision_id);
        } catch (Exception $e) {
            return array();
        }

        return array_filter(
            // Файлы полей разделов
            $this->get_entity_files_from_fields('Subdivision', $subdivision_id) +
            // Файлы настроек макета дизайна
            $this->get_entity_files_from_settings('template_settings', $subdivision_id, $nc_core->template->get_custom_settings($settings['Template_ID']), $settings['TemplateSettings']) +
            // Файлы из настроек миксинов
            $this->get_entity_files_from_mixin_settings('Index_Mixin_Settings', nc_array_value($settings, 'Index_Mixin_Settings')) +
            $this->get_entity_files_from_mixin_settings('IndexItem_Mixin_Settings', nc_array_value($settings, 'IndexItem_Mixin_Settings'))
        );
    }

    /**
     *
     */
    protected function prepare_version_restore() {
        $this->remove_file_records_on_version_restore('Subdivision', $this->get('Subdivision_ID'));
    }

    /**
     *
     */
    protected function finalize_version_restore() {
        $id = $this->get('Subdivision_ID');

        // Удаление сгенерированных изображений
        $this->remove_generated_field_images('Subdivision', $id);
        $this->remove_generated_settings_images('Subdivision', $id);

        // Пересборка файлов миксинов
        $changes = $this->get_changes();

        if (!empty($changes['Parent_Sub_ID'])) {
            nc_tpl_mixin_cache::generate_subdivision_files($id, true);
        } else if (!empty($changes['MainArea_Mixin_Settings'])) {
            nc_tpl_mixin_cache::generate_subdivision_files($id, false);
        }
    }

}
