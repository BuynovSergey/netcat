<?php

class nc_tpl {

    /** @var string символы, допустимые в фрагменте пути */
    protected $path_fragment_regexp = '[\w.]+';

    /** @var nc_tpl_fields  */
    public $fields = null;
    /** @var int */
    public $catalogue_id = null;
    /** @var string  */
    public $relative_path = null;
    /** @var string  */
    public $absolute_path = null;
    /** @var int */
    public $id = null;
    /** @var int */
    public $count_parent = null;
    /** @var string  */
    public $extension = '.html';
    /** @var nc_tpl|null */
    public $child = null;
    /** @var string  */
    public $type = null;
    /** @var nc_db */
    public $db = null;
    /** @var string  */
    public $path_to_root_folder = null;
    /** @var bool */
    protected $use_catalogue = false;

    /**
     * @param string $path
     * @param nc_Db $db
     * @param string $type
     */
    public function __construct($path, nc_Db $db, $type = null) {
        $this->path_to_root_folder = $path;
        $this->db = $db;
        $this->type = $type;

        $this->catalogue_id = nc_core::get_object()->catalogue->id();
    }

    /**
     * @param int $id
     * @param string $type
     * @param string $relative_path
     */
    public function load($id, $type, $relative_path = null) {
        // check path
        #if (!$id) {
        #   nc_print_status(NETCAT_TEMPLATE_FILE_NOT_FOUND, 'error');
        #   exit;
        #}
        $this->id = (int)$id;
        $this->type = $type;
        $this->relative_path = $relative_path ? $relative_path : $this->select_relative_path();
        // check path
        #if (!$this->relative_path) {
        #   nc_print_status(NETCAT_TEMPLATE_FILE_NOT_FOUND, 'error');
        #   exit;
        #}
        $this->absolute_path = $this->get_absolute_path_to_template();
        $this->count_parent = $this->count_parent_template();

        $this->fields = new nc_tpl_fields($this);
    }

    /**
     * Использовать шаблоны "привязанные" к сайту
     *
     * @param int|boolean $catalogue_id Идентификатор сайта | true - текущий сайт
     * @return null
     */
    public function use_catalogue($catalogue_id = true) {
        if ($catalogue_id) {
            if ($catalogue_id !== true) {
                $this->catalogue_id = $catalogue_id;
            }
            $this->absolute_path = $this->get_absolute_path_to_template(true);
        }
    }

    /**
     * @return bool|string|null
     */
    private function select_relative_path() {
        if (!($this->type && $this->id)) {
            return false;
        }
        $SQL = "SELECT File_Path
                  FROM {$this->type}
                 WHERE {$this->type}_ID = {$this->id}";
        return $this->db->get_var($SQL);
    }

    /**
     * @param $new_fragment
     * @return bool
     */
    public function update_relative_path_last_fragment($new_fragment) {
        $chars = $this->path_fragment_regexp;
        if (!$this->type || !preg_match("!^$chars$!", $new_fragment)) {
            return false;
        }

        $old_relative_path = $this->relative_path;
        $old_absolute_path = $this->absolute_path;

        $new_relative_path = preg_replace("!/$chars/$!", "/$new_fragment/", $old_relative_path);
        $new_absolute_path = preg_replace("!/$chars/$!", "/$new_fragment/", $old_absolute_path);

        if (!rename($old_absolute_path, $new_absolute_path)) {
            return false;
        }

        // $old_relative_path, $new_relative_path безопасны (^[/0-9a-z_.]+$)
        $replaced_path_length = strlen($old_relative_path);
        $this->db->query(
            "UPDATE `{$this->type}`
                SET `File_Path` = CONCAT('$new_relative_path', SUBSTRING(`File_Path` FROM $replaced_path_length+1))
              WHERE `File_Path` LIKE '$old_relative_path%'"
        );

        $this->relative_path = $new_relative_path;
        $this->absolute_path = $new_absolute_path;

        return true;
    }

    /**
     * @param bool $make_catalogue_dir
     * @return string
     */
    private function get_absolute_path_to_template($make_catalogue_dir = false) {
        $abs_path = nc_standardize_path_to_folder($this->path_to_root_folder . '/' . $this->relative_path);

        $module_editor = new nc_tpl_module_subtypes_editor();
        if ($this->catalogue_id && array_key_exists($this->type, $module_editor->get_module_types())) {
            if (!file_exists($abs_path . $this->catalogue_id)) {
                if (!$make_catalogue_dir) {
                    return $abs_path;
                }
                mkdir($abs_path . $this->catalogue_id);
            }

            $abs_path .= $this->catalogue_id . '/';
        }

        return $abs_path;
    }

    /**
     * @return int
     */
    private function count_parent_template() {
        return count(array_diff(explode('/', trim($this->relative_path, '/')), array('')));
    }

    /**
     * @param int $id
     */
    public function load_child($id) {
        $this->child = new nc_tpl(nc_standardize_path_to_folder($this->path_to_root_folder), $this->db);

        $keyword = null;

        if ($this->type == 'Class') {
            $keyword = $nc_core = nc_core::get_object()->component->get_by_id($id, 'Keyword');
        }
        else if ($this->type == 'Template') {
            $keyword = $nc_core = nc_core::get_object()->template->get_by_id($id, 'Keyword');
        }

        if ($keyword) {
            $path_fragment = $keyword;
        }
        else {
            $path_fragment = $id;
        }

        $this->child->load($id, $this->type, nc_standardize_path_to_folder($this->relative_path . '/' . $path_fragment));
    }

    /**
     * @return bool
     */
    public function update_file_path_and_mode() {
        if (!($this->type && $this->id && $this->relative_path)) {
            return false;
        }
        $SQL = "UPDATE {$this->type}
                   SET File_Path = '{$this->relative_path}',
                       File_Mode = 1
                 WHERE {$this->type}_ID = {$this->id}";
        return $this->db->query($SQL);
    }

    /**
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function delete_template_file_and_folder($path = '') {
        // path
        $path = ($path ? $path : $this->absolute_path);
        // check path
        if (!is_dir($path)) {
            return false;
        }

        // error when trying to delete template folder
        if (nc_standardize_path_to_folder($this->path_to_root_folder) == $path) {
            // warning message
            nc_print_status(sprintf(NETCAT_TEMPLATE_DIR_DELETE_ERROR, $path), 'error');
            return false;
        }

        // variables
        $files = nc_double_array_shift(scandir($path));
        $directory = nc_standardize_path_to_folder($path);

        foreach ($files as $file) {
            $full_path = $directory . $file;
            // check file existance
            if (!file_exists($full_path)) {
                continue;
            }
            // file / dir
            if (is_dir($full_path)) {
                $this->delete_template_file_and_folder($full_path);
            }
            else {
                // delete file
                unlink($full_path);
            }
        }
        // delete dir
        if (is_dir($directory)) {
            rmdir($directory);
        }

        return true;
    }

    /**
     * Подключает все библиотеки, перечисленные в RequiredAssets.html шаблона (макета)
     * и его родителей
     * @return bool
     */
    public function include_all_required_assets() {
        static $processed = array();

        if (!$this->fields->has_field('RequiredAssets')) {
            trigger_error("Templates of type '$this->type' have no RequiredAssets field", E_USER_ERROR);
        }

        $result = true;

        $nc_core = nc_core::get_object();
        $file = $this->fields->get_path('RequiredAssets');
        for ($i = 0; $i < $this->count_parent; $i++) {
            if (isset($processed[$file])) {
                // повторный вызов — не идём дальше по иерархии
                break; // exit 'for'
            }
            $processed[$file] = true;
            if (file_exists($file)) {
                // предполагается, что RequiredAssets.html всегда возвращает одинаковый результат,
                // поэтому обрабатываем его только один раз
                $assets = include $file;
                if (is_array($assets)) {
                    $result = $result && $nc_core->page->require_assets($assets);
                }
            }
            $file = nc_get_path_to_parent_file($file);
        }

        return $result;
    }

}