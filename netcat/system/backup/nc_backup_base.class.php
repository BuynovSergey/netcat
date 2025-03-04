<?php

abstract class nc_backup_base implements nc_backup_dumper_listener {

    //-------------------------------------------------------------------------

    // protected $group_name = '';

    protected $name    = '';
    protected $type    = '';
    protected $id      = 0;
    protected $new_id  = 0;
    protected $file    = '';
    protected $version = '1.4';
    protected $group_name;

    protected $validation_error = '';

    /** @var nc_core */
    protected $nc_core;
    /** @var nc_backup  */
    protected $backup;
    /** @var nc_backup_dumper */
    protected $dumper;

    //-------------------------------------------------------------------------

    public function __construct(nc_backup $backup) {
        $class = get_class($this);

        if (!$this->type) {
            $this->type = substr($class, strlen('nc_backup_'));
        }

        if (!$this->name) {
            $this->name = ucfirst($this->type);
        }

        $this->backup  = $backup;
        $this->nc_core = nc_core();

        $this->init();
    }

    //-------------------------------------------------------------------------

    public function get_row_attributes($ids) {
        static $attributes;

        if ($attributes === null) {
            $attributes = $this->row_attributes($ids);
        }

        return $attributes;
    }


    //-------------------------------------------------------------------------

    protected function reset() {
        $this->id               = 0;
        $this->new_id           = 0;
        $this->validation_error = '';

        // $this->export = null;
        // $this->import = null;
        $this->dumper = null;
    }

    //-------------------------------------------------------------------------

    public function get_name() {
        return $this->name;
    }

    //-------------------------------------------------------------------------

    public function get_type() {
        return $this->type;
    }

    //-------------------------------------------------------------------------

    public function get_version() {
        return $this->version;
    }

    //-------------------------------------------------------------------------

    public function get_group_name() {
        return $this->group_name;
    }

    //-------------------------------------------------------------------------

    public function get_id() {
        return $this->id;
    }

    //-------------------------------------------------------------------------

    public function get_new_id() {
        return $this->new_id;
    }

    //-------------------------------------------------------------------------

    public function get_export_form() {
        return $this->export_form();
    }

    //-------------------------------------------------------------------------

    public function set_validation_error($message) {
        $this->validation_error = $message;
    }

    //-------------------------------------------------------------------------

    public function get_validation_error() {
        return $this->validation_error;
    }

    //-------------------------------------------------------------------------

    public function call_event($event, $attr) {
        $method = 'event_' . $event;

        if (method_exists($this, $method)) {
            switch (count($attr)) {
                case 0: return $this->$method();
                case 1: return $this->$method($attr[0]);
                case 2: return $this->$method($attr[0], $attr[1]);
                case 3: return $this->$method($attr[0], $attr[1], $attr[2]);
                case 4: return $this->$method($attr[0], $attr[1], $attr[2], $attr[3]);
                default: return call_user_func_array(array($this, $method), $attr);
            }
        }

        return null;
    }

    //-------------------------------------------------------------------------

    public function export($id, array $settings = array()) {
        $this->reset();
        $this->id     = $id;
        $this->dumper = $this->backup->get_dumper();

        $this->dumper->set_current_object($this);
        $this->dumper->export_init($this->type, $id, $settings);

        $this->export_init();

        $valid = $this->export_validation();
        $error = $this->get_validation_error();
        if ($valid === false || $error) {
            throw new Exception($error ? $error : 'Export validation error', 1);
        }

        $this->export_process();
        $this->export_modules();

        $this->dumper->export_finish();

        $this->dumper->forget_current_object();

        return $this->dumper->get_export_file();
    }

    //-------------------------------------------------------------------------

    protected function export_modules() {
        foreach ($this->get_extensions() as $extension) {
            $this->dumper->set_current_object($extension);
            $extension->export($this->type, $this->id);
            $this->dumper->forget_current_object();
        }
    }

    //-------------------------------------------------------------------------

    /**
     * @return nc_backup_extension[]
     */
    protected function get_extensions() {
        $extensions = array();
        $module_settings = $this->backup->get_settings('module_settings');
        foreach ($module_settings as $module => $settings) {
            if (isset($settings['extensions'][$this->type])) {
                foreach ($settings['extensions'][$this->type] as $extension_class_name) {
                    if (!$extension_class_name) { continue; }

                    if (!class_exists($extension_class_name)) {
                        trigger_error(__CLASS__ . ": cannot find extension class $extension_class_name (module: $module)", E_USER_WARNING);
                        continue;
                    }

                    if (!is_subclass_of($extension_class_name, 'nc_backup_extension')) {
                        trigger_error(__CLASS__ . ": extension class $extension_class_name must extend nc_backup_extension", E_USER_WARNING);
                        continue;
                    }

                    $extensions[] = new $extension_class_name($this->dumper);
                }
            }
        }
        return $extensions;
    }

    //-------------------------------------------------------------------------

    public function export_download($id) {
        $file = $this->export($id);

        ob_get_level() && ob_clean();
        header("Content-type: application/x-compressed");
        header("Content-Disposition: attachment; filename=" . basename($file));
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        echo file_get_contents(nc_core('DOCUMENT_ROOT') . $file);
        exit;
    }

    //-------------------------------------------------------------------------

    public function get_import_form() {
        return $this->import_form();
    }

    //-------------------------------------------------------------------------

    public function import($file, array $settings = array()) {
        try {
            $this->reset();
            $this->dumper = $this->backup->get_dumper();

            $this->dumper->set_current_object($this);
            $this->dumper->import_init($file, $settings);

            $multiple_mode = false;
            if ($this->dumper->get_dump_info('multiple_mode') && is_numeric($file)) {
                $multiple_mode = true;
                $this->id = $file;
            } else {
                $this->id = $this->dumper->get_dump_info('id');
                $type = $this->dumper->get_dump_info('type');
                // Check type
                if ($this->type != $type) {
                    throw new Exception("Type not match: '{$type}' != '{$this->type}'");
                }
            }

            $this->dumper->import_validation();

            $valid = $this->import_validation();
            $error = $this->get_validation_error();
            if ($valid === false || $error) {
                throw new Exception($error ? $error : 'Export validation error', 1);
            }

            $this->import_process();
            $this->import_modules();

            if (!$multiple_mode) {
                $this->dumper->import_finish();

                $this->dumper->set_import_result('new_id', $this->new_id);
            }

            $this->dumper->forget_current_object();

            return $this->dumper->get_import_result();

        } catch (Exception $e) {
            $this->dumper->import_finish();
            throw new Exception($e->getMessage(), 1);
        }
    }

    //-------------------------------------------------------------------------

    protected function import_modules() {
        foreach ($this->get_extensions() as $extension) {
            $this->dumper->set_current_object($extension);
            $extension->import($this->type, $this->new_id);
            $this->dumper->forget_current_object();
        }
    }

    //-------------------------------------------------------------------------

    /**
     * Проверяет и при необходимости исправляет структуру таблицы Message*
     * @param int $component_id
     * @return void
     */
    protected function check_message_table_structure($component_id) {
        $table = 'Message' . $component_id;
        $db = nc_db();

        $fields = array(
            'ncFullPageMode' => "enum('inherit', 'template', 'custom', 'none') NOT NULL DEFAULT 'inherit'",
            'ncFullPageCreated' => "tinyint NOT NULL DEFAULT 0 AFTER `ncFullPageMode`",
            'ncDemoContent' => "tinyint(4) NOT NULL DEFAULT 0 AFTER `ncFullPageCreated`",
        );

        foreach ($fields as $field => $definition) {
            if (!$db->get_var("SHOW COLUMNS FROM `$table` LIKE '$field'")) {
                $db->query("ALTER TABLE `$table` ADD COLUMN `$field` $definition");
            }
        }
    }

    //-------------------------------------------------------------------------

    /**************************************************************************
        METHODS TO EXTEND IN DESCENDANT CLASSES
    **************************************************************************/

    protected function init() {}
    protected function row_attributes($ids) { return null; }

    protected function export_form() { return null; }
    protected function export_init() {}
    protected function export_validation() { return true; }

    abstract protected function export_process();

    protected function import_form() { return null; }
    protected function import_init() {}
    protected function import_validation() { return true; }

    abstract protected function import_process();
}
