<?php

/**
 * Импорт/Экспорт данных раздела в качестве черновика. Данные берутся из REQUEST
 */
class nc_backup_draft extends nc_backup_base
{

    protected $name = "Drafts";

    /** @var  nc_db_table */
    protected $sub_class_table;

    /** @var  nc_db_table */
    protected $field_table;

    protected $file_field_ids = array(6, 11);

    protected function init() {
        $this->sub_class_table = nc_db_table::make('Sub_Class');
        $this->field_table = nc_db_table::make('Field');
    }

    protected function make_message_table($class_id) {
        return nc_db_table::make('Message' . intval($class_id), 'Message_ID');
    }

    protected function export_process() {
        $id = $this->id;
        $sub_class = $this->sub_class_table->where_id($id)->get_row();

        if (!$sub_class) {
            return false;
        }

        $class_id = $sub_class['Class_ID'];
        $message_table = $this->make_message_table($class_id);
        $current_cc = $this->dumper->get_export_settings('current_cc');

        $version_id = $this->dumper->get_export_settings('version_id');
        $fields = $this->dumper->get_export_settings('fields');
        if ($current_cc['Sub_Class_ID'] > 0) {
            $data = array(
              $current_cc['Sub_Class_ID'] => $fields
            );
        }
        $this->dumper->register_dict_field('Sub_Class_ID', 'Subdivision_ID');

        $this->dumper->export_data($message_table->get_table(), 'Message_ID', $data, "Draft-" . $version_id);


        //todo учитывать файлы

        return true;
    }

    public function export_form() {
        
    }

    protected function import_process() {
        $fields = array();
        $data = $this->dumper->get_dump_info('data');
        foreach ($data as $obj) {
            $xmls = $obj['files'];
            if (!$xmls) {
                return false;
            }
            foreach ($xmls as $xml) {
                $xml_file = $this->dumper->get_dump_path($xml);

                if (!file_exists($xml_file)) {
                    throw new Exception("XML file not found: {$xml}", 1);
                }

                $xml_data = $this->dumper->read_data($xml_file);

                foreach ($xml_data as $row) {
                    foreach ($row as $field_name => $field_value) {
                        $fields[$field_name] = !empty($fields[$field_name]) ? $fields[$field_name] : $field_value;
                    }
                }
            }
        }
        $this->dumper->set_import_result('fields', $fields);

        return true;
    }

}
