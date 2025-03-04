<?php

class nc_version_record_site extends nc_version_record {

    protected $entity_table = 'Catalogue';
    static protected $ignore_field_changes = array('Catalogue_ID' => true);

    /**
     * @return string
     */
    public function generate_description() {
        return $this->get_action_description() . ' ' . sprintf(NETCAT_VERSION_OF_SITE, $this->get_name_from_snapshot());
    }

    /**
     * @return bool
     */
    protected function parent_entity_exists() {
        return true;
    }

    /**
     * @return bool
     */
    protected function entity_exists() {
        try {
            return (bool)nc_core::get_object()->catalogue->get_by_id($this->get('Catalogue_ID'), 'Catalogue_ID');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     *
     */
    protected function delete_entity() {
        CascadeDeleteCatalogue($this->get('Catalogue_ID'));
    }

    /**
     * @return array
     * @throws nc_record_exception
     */
    protected function get_entity_snapshot_data() {
        $site_id = $this->get('Catalogue_ID');
        $site_data = (array)nc_db()->get_results(
            "SELECT * FROM `Catalogue` WHERE `Catalogue_ID` = $site_id",
            ARRAY_A,
            'Catalogue_ID'
        );

        return array(
            'Catalogue' => $site_data,
            'Filetable' => $this->get_entity_snapshot_file_data('Catalogue', $site_id, 'Filetable', NC_FIELDTYPE_FILE),
            'Multifield' => $this->get_entity_snapshot_file_data('Catalogue', $site_id, 'Multifield', NC_FIELDTYPE_MULTIFILE),
        );
    }

    /**
     * @return array
     */
    protected function get_entity_current_files() {
        $nc_core = nc_core::get_object();

        try {
            $site_id = $this->get('Catalogue_ID');
            $settings = $nc_core->catalogue->get_by_id($site_id);
        } catch (Exception $e) {
            return array();
        }

        return array_filter(
            // Файлы полей разделов
            $this->get_entity_files_from_fields('Catalogue', $site_id) +
            // Файлы настроек макета дизайна
            $this->get_entity_files_from_settings('template_settings', $site_id, $nc_core->template->get_custom_settings($settings['Template_ID']), $settings['TemplateSettings']) +
            // Файлы из настроек миксинов
            $this->get_entity_files_from_mixin_settings('MainArea_Mixin_Settings', $settings['MainArea_Mixin_Settings'])
        );
    }

    /**
     *
     */
    protected function prepare_version_restore() {
        $this->remove_file_records_on_version_restore('Catalogue', $this->get('Catalogue_ID'));
    }

    /**
     *
     */
    protected function finalize_version_restore() {
        // Пересборка файлов миксинов
        $id = $this->get('Catalogue_ID');
        $changes = $this->get_changes();
        nc_core::get_object()->catalogue->get_by_id($id, null, true);

        if (!empty($changes['MainArea_Mixin_Settings'])) {
            nc_tpl_mixin_cache::generate_site_files($id, false);
        }
    }

}
