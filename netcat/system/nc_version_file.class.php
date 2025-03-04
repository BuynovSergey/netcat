<?php

class nc_version_file extends nc_record {

    protected $mapping = false;
    protected $primary_key = 'Version_File_ID';
    protected $table_name = 'Version_File';

    protected $properties = array(
        'Version_File_ID' => null,
        'Version_ID' => null,
        'Field_Name' => null,
        'File_Path' => null,
        'File_Hash' => null,
    );

    /**
     * @param string $relative_path
     * @return string|null
     */
    public static function get_file_hash($relative_path) {
        $full_path = nc_core::get_object()->DOCUMENT_ROOT . $relative_path;
        if ($relative_path && file_exists($full_path) && is_file($full_path)) {
            return hash_file('sha256', $full_path);
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function get_version_file_path() {
        $hash = $this->get('File_Hash');
        if (!$hash) {
            return null;
        }
        preg_match('/^(..)(..)(..)(.+)$/', $hash, $parts);
        array_shift($parts);
        return nc_core::get_object()->FILES_FOLDER . 'version/' . implode('/', $parts);
    }

    /**
     * @return string|null
     */
    protected function calculate_and_set_file_hash() {
        $file_hash = self::get_file_hash($this->get('File_Path'));
        if ($file_hash) {
            $this->properties['File_Hash'] = $file_hash;
        }
        return $this->properties['File_Hash'];
    }

    /**
     *
     */
    protected function save_file_copy() {
        $hash = $this->calculate_and_set_file_hash();
        if (!$hash) {
            return;
        }

        $copy_path = $this->get_version_file_path();
        if (!file_exists($copy_path)) {
            $nc_core = nc_core::get_object();
            $copy_folder = dirname($copy_path);
            if (!file_exists($copy_folder)) {
                mkdir($copy_folder, $nc_core->DIRCHMOD, true);
            }
            copy($nc_core->DOCUMENT_ROOT . $this->get('File_Path'), $copy_path);
        }
    }

    /**
     * @return nc_version_file
     */
    public function save() {
        $this->save_file_copy();
        parent::save();
        return $this;
    }

}
