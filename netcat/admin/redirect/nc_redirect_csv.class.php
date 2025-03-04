<?php

class nc_redirect_csv extends nc_csv {
    protected static $redirect_instance;
    private function __construct() {}
    private function __clone(){}
    public function __wakeup(){}
    
    public static function get_instance() {
        if (is_null(self::$redirect_instance)) {
            self::$redirect_instance = new self();
        }
        return self::$redirect_instance;
    }

    public function preimport_file($file, $data) {
        if (!$file) {
            throw new Exception(TOOLS_CSV_IMPORT_FILE_NOT_FOUND, 1);
        }
        if (!is_dir($file)) {
            $tmp_file = nc_core()->TMP_FOLDER . uniqid() . '.csv';
            copy($file, $tmp_file);
        }
        if (!file_exists($tmp_file)) {
            throw new Exception(TOOLS_CSV_IMPORT_FILE_NOT_FOUND . " " . $tmp_file, 1);
        }

        $head_fields = $this->process_csv_header($tmp_file, $data['csv']);

        $fields = array(
            "old_url" => TOOLS_REDIRECT_OLDURL,
            "new_url" => TOOLS_REDIRECT_NEWURL,
            "header" => TOOLS_REDIRECT_HEADER,
        );

        return array(
          'group' => $data['group'], 'checked' => $data['checked'],
          'csv_head' => array('' => TOOLS_CSV_NOT_SELECTED) + array_combine($head_fields, $head_fields),
          'fields' => $fields,
          'csv_settings' => $data['csv'],
          'file' => $tmp_file);
    }

    public function import_file($file, $data)
    {
        if (!$file) {
            throw new Exception(TOOLS_CSV_IMPORT_FILE_NOT_FOUND, 1);
        }
        if (!file_exists($file)) {
            throw new Exception(TOOLS_CSV_IMPORT_FILE_NOT_FOUND . " " . $file, 1);
        }

        $csv_result = $this->process_csv($file, $data['csv']);
        $csv_data_fields = $csv_result['data'];
        unlink($file);

        $i = 0;
        foreach ($csv_data_fields as $csvValues) {

            $redirect = new nc_redirect();
            $redirect->set_values_from_form(array(
                'old_url' => $csvValues[$data['fields']['old_url']],
                'new_url' => $csvValues[$data['fields']['new_url']],
                'header' => $csvValues[$data['fields']['header']],
                'group' => $data['group'],
                'checked' => $data['checked'],
            ));

            if ($redirect->validate()) {
                $redirect->save();
                $i++;
            }
        }

        return array('success' => $i);

    }

}

