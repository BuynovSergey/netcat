<?php

class nc_version_file_collection extends nc_record_collection {
    protected $items_class = 'nc_version_file';

    /**
     * @param array $files
     * @param nc_version_record $version
     * @return nc_version_file_collection
     */
    public static function from_array(array $files, nc_version_record $version) {
        $version_id = $version->get_id();
        $version_files = array();
        foreach ($files as $field_name => $file_paths) {
            if (is_array($file_paths)) {
                foreach ($file_paths as $file_path) {
                    if ($file_path) { // Пустое значение для файловых полей компонента и пользовательских настроек указывает на то, что файла нет
                        $version_files[] = new nc_version_file(array(
                            'Version_ID' => $version_id,
                            'Field_Name' => $field_name,
                            'File_Path' => $file_path,
                        ));
                    }
                }
            }
            // если File_Path = null — файла в поле нет
        }

        return new self($version_files);
    }

    /**
     * @param nc_version_record $version
     */
    public static function for_version(nc_version_record $version) {
        $version_id = $version->get_id();
        $result = new self();
        return $result->select_from_database("SELECT * FROM `%t%` WHERE `Version_ID` = $version_id");
    }

    /**
     * @param string $relative_path
     * @return string|null
     */
    public function get_file_hash($relative_path) {
        foreach ($this->items as $item) {
            if ($item->get('File_Path') === $relative_path) {
                return $item->get('File_Hash');
            }
        }
        return null;
    }


}
