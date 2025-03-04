<?php

/**
 *
 */
class nc_auth_backup extends nc_backup_extension {

    private $uniq_id;

    /**
     * @param string $type
     * @param int $id
     */
    public function export($type, $id) {
        if ($type != 'site') { return; }

        $auth_template_path = $this->get_template_path_prefix();
        $auth_template_dir = nc_core('DOCUMENT_ROOT') . nc_core('SUB_FOLDER') . $auth_template_path;

        foreach(array('web', 'mobile', 'responsive') as $template_type) {
            if (file_exists($auth_template_dir . '/' . $template_type . '/' . $id)) {
                $this->dumper->export_files($auth_template_path . '/' . $template_type, $id);
            }
        }
    }

    /**
     * @param string $type
     * @param int $id
     */
    public function import($type, $id) {
        if ($type != 'site') { return; }

        $this->uniq_id = md5(uniqid('', true));

        $this->dumper->import_files(array($this->get_template_path_prefix()));

        $auth_template_path = $this->get_template_path_prefix();
        $auth_template_dir = nc_core('DOCUMENT_ROOT') . nc_core('SUB_FOLDER') . $auth_template_path;

        foreach(array('web', 'mobile', 'responsive') as $template_type) {
            @rename($auth_template_dir . '/' . $template_type . '/' . $this->uniq_id, $auth_template_dir . '/' . $template_type . '/' . $id);
        }
    }

    /**
     * @param $path
     * @param $file
     * @param $src
     * @return string new file path
     */
    public function event_before_copy_file($path, $file, $src) {
        if (strpos($path, $this->get_template_path_prefix()) === false) {
            return false;
        }

        return $path . $this->uniq_id;
    }

    /**
     * @return string
     */
    protected function get_template_path_prefix() {
        return nc_core('HTTP_TEMPLATE_PATH') . 'module/auth';
    }
}