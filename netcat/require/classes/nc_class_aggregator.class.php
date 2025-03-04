<?php

class nc_class_aggregator {
    private $settings = array();
    private $data = array();
    private $data_constructed  = array();
    private $prepare_settings = array();
    private $multifile_fields = array();

    public function __construct(nc_class_aggregator_setting $settings, array $data) {
        $this->settings = $settings->get_complete_settings();
        $this->prepare_settings = $this->prepare_settings($this->settings);
        $this->data = $data;
        $this->data_constructed();
        $this->fill_data();
    }

    private function prepare_settings($settings) {
        $nc_core = nc_core::get_object();
        $file_folder = rtrim(nc_Core::get_object()->SUB_FOLDER . nc_Core::get_object()->HTTP_FILES_PATH, '/') . '/';
        foreach ($settings as $class_id => $setting) {
            $prepare_setting = array();

            foreach ($setting as $component_field_name => $aggregator_field_name) {
                $field = $nc_core->get_component($class_id)->get_field($component_field_name);
                if ($field['type'] == NC_FIELDTYPE_MULTIFILE) {
                    $this->multifile_fields[$class_id][$field['id']] = $aggregator_field_name;
                }

                $prepare_setting[] = "(IF (@type := (SELECT (IF((POSITION('fs3' IN Format) > 0), 3,
                                                             IF((POSITION('fs2' IN Format) > 0), 2,
                                                             IF((POSITION('fs1' IN Format) > 0), 1, 0))))
                                                    FROM Field WHERE Class_ID = $class_id AND Field_Name = '$component_field_name' AND TypeOfData_ID = " . NC_FIELDTYPE_FILE . "),
                                            (IF(@id := (SELECT Field_ID FROM Field WHERE Class_ID = $class_id AND Field_Name = '$component_field_name' AND TypeOfData_ID = " . NC_FIELDTYPE_FILE . "),
                                                       (IF(@type = 3, (SELECT CONCAT('$file_folder', File_Path, Virt_Name) FROM Filetable WHERE Field_ID = @id AND Message_ID = db_Message_ID),
                                                        IF(@type = 2, (SELECT CONCAT('$file_folder', SUBSTRING($component_field_name, LOCATE(':', $component_field_name, LOCATE(':', $component_field_name, LOCATE(':', $component_field_name) + 1) + 1) + 1))),
                                                                      (SELECT CONCAT('$file_folder', @id, '_', db_Message_ID, SUBSTRING($component_field_name, LOCATE('.', $component_field_name), (LOCATE(':', $component_field_name) - LOCATE('.', $component_field_name)))))))), $component_field_name)), $component_field_name)) AS $aggregator_field_name,
                                      (SELECT Description FROM Field WHERE Class_ID = $class_id AND Field_Name = '$component_field_name') as {$aggregator_field_name}_desc";
            }
            $settings[$class_id] = $prepare_setting;
        }
        return $settings;
    }

    private function data_constructed() {
        foreach($this->data as $item) {
            $this->data_constructed[$item['db_Class_ID']][] = $item['db_Message_ID'];
        }
    }

    private function fill_data() {
        $SQL = $this->get_sql();
        if (!$SQL) { return null; }
        $result = (array) nc_Core::get_object()->db->get_results($SQL, ARRAY_A);
        $indexes = array();
        foreach ($result as $row) {
            if (isset($this->multifile_fields[$row['db_Class_ID']])) {
                foreach ($this->multifile_fields[$row['db_Class_ID']] as $component_field_id => $aggregator_field_name) {
                    $row[$aggregator_field_name] = nc_load_multifield($component_field_id, $row['db_Message_ID']);
                }
            }

            $indexes[] = $index = array_search(array('db_Class_ID' => $row['db_Class_ID'], 'db_Message_ID' => $row['db_Message_ID']), $this->data);
            $this->data[$index] = $row;
        }

        $data = array();

        foreach ($this->data as $key => $row) {
            if (array_search($key, $indexes) === false) {
                unset($this->data[$key]);
            } else {
                $data[] = $this->data[$key];
            }
        }

        $this->data = $data;
    }

    private function get_sql() {
        $SQL = array();
        foreach ($this->data_constructed as $Class_ID => $Message_IDs) {
            if (!isset($this->prepare_settings[$Class_ID]) || !is_array($this->prepare_settings[$Class_ID])) { 
                continue; 
            }
            $SQL[] = "(SELECT $Class_ID as db_Class_ID,
                              Message_ID as db_Message_ID,
                              Subdivision_ID as db_Subdivision_ID,
                              Sub_Class_ID as db_Sub_Class_ID,
                              Keyword as db_Keyword,
                              " . join(', ', $this->prepare_settings[$Class_ID]) . "
                           FROM Message$Class_ID
                               WHERE Message_ID IN (" . join(', ', $Message_IDs) . "))";
        }
        return join(' UNION ', $SQL);
    }

    public function get_full_data() {
        return $this->data;
    }

    /**
     * Проверяет, есть ли в настройках агрегатора хотя бы одно поле
     * «множественная загрузка файлов»
     * @param int|null $component_id
     * @return bool
     */
    public function has_multifile_fields($component_id = null) {
        if ($component_id) {
            return isset($this->multifile_fields[$component_id]);
        }
        else {
            return count($this->multifile_fields) > 0;
        }
    }
}

