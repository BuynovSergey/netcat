<?php


class nc_backup_dumper {

    //-------------------------------------------------------------------------

    protected $dump_info   = array();
    protected $dump_path   = '';

    /** @var  nc_backup */
    protected $backup;
    /** @var  nc_backup_base */
    protected $current_object;
    /** @var  nc_backup_base[] */
    protected $current_object_stack = array();

    protected $export_file;
    protected $export_settings = array(
        'compress' => true,
    );

    protected $import_result = array();
    protected $import_settings = array(
        'save_ids' => true,
    );
    // Список полей значения которых сбрасываются в заданное значение при импорте
    protected $reset_field_values = array(
        // все таблицы
        '*' => array(
            //TODO: Нельзя сбрасывать User_ID в "0" т.к. такие объекты не удаляются из-за бага в системе
            // 'User_ID' => '0'
        ),
    );

    //-------------------------------------------------------------------------

    public function __construct() {
        @set_time_limit(0);
        $memory_limit = ini_get('memory_limit');
        $size         = strtoupper(substr($memory_limit, -1));

        if ($size == 'M' && $memory_limit < 512) {
            @ini_set('memory_limit', 512 . 'M');
        }

        $this->backup = nc_core('backup');
    }

    //-------------------------------------------------------------------------

    public function call_event($event, $args, &$replace_result = null) {
        if ($this->current_object) {
            $result = $this->current_object->call_event($event, $args);

            if ($replace_result && $result !== null) {
                $replace_result = $result;
            }
        }
    }

    //-------------------------------------------------------------------------

    public function set_current_object(nc_backup_dumper_listener $object) {
        $this->current_object_stack[] = $object;
        $this->current_object = $object;
    }

    //-------------------------------------------------------------------------

    public function forget_current_object() {
        $this->current_object = array_pop($this->current_object_stack);
    }

    //-------------------------------------------------------------------------

    public function set_dump_path($path) {
        $this->dump_path = rtrim($path, '/\\') . '/';
        return $this->get_dump_path();
    }

    //-------------------------------------------------------------------------

    public function get_dump_path($filename = '') {
        return $this->dump_path . $filename;
    }

    //-------------------------------------------------------------------------

    public function set_dump_info($key, $value) {
        $this->dump_info[$key] = $value;
    }

    //-------------------------------------------------------------------------

    public function remove_table_data($table) {
        if (!isset($this->dump_info['data'][$table])) { return; }

        foreach ($this->dump_info['data'][$table]['files'] as $file) {
            unlink($this->get_dump_path($file));
        }

        unset(
            $this->dump_info['data'][$table],
            $this->dump_info['table'][$table]
        );
    }

    //-------------------------------------------------------------------------

    public function get_dump_info($key = null, $key2 = null) {
        if (!$key) {
            return $this->dump_info;
        }

        $result = isset($this->dump_info[$key]) ? $this->dump_info[$key] : null;

        if ($key2 && $result) {
            $result = isset($result[$key2]) ? $result[$key2] : null;
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function load_dump_info() {
        $info_file = $this->get_dump_path('info.php');
        if (file_exists($info_file)) {
            $this->dump_info = include $info_file;
        }
        return $this->dump_info;
    }

    //-------------------------------------------------------------------------

    public function save_dump_info() {
        if (!$this->dump_info) {
            return null;
        }

        $info = $this->dump_info;
        unset($info['dict']);

        $file    = $this->get_dump_path('info.php');
        $content = '<?php return ' . var_export($info, true) . ';';

        return file_put_contents($file, $content);
    }

    //-------------------------------------------------------------------------

    public function set_dict($field, $key, $value = null) {
        if ($value === null) {
            $value = $key;
        }
        if (is_numeric($key)) $key = (int)$key;
        if (is_numeric($value)) $value = (int)$value;

        $this->dump_info['dict'][$field][$key] = $value;
    }

    //-------------------------------------------------------------------------

    public function get_dict($field = null, $key = null, $default = null) {
        if (isset($this->dump_info['dict'])) {
            $dict = $this->dump_info['dict'];
        }
        else {
            $dict = null;
        }

        if ($field === null) {
            return $dict;
        }

        if ($key === null) {
            return isset($dict[$field]) ? $dict[$field] : array();
        }

        if ($default === null) {
            $default = $key;
        }

        return isset($dict[$field][$key]) ? $dict[$field][$key] : $default;
    }

    //-------------------------------------------------------------------------

    public function register_dict_field($fields) {
        if (func_num_args() > 1) {
            $fields = func_get_args();
        } else {
            $fields = (array) $fields;
        }

        foreach ($fields as $alias => $field) {
            if (is_numeric($alias)) {
                $alias = $field;
            }
            $this->dump_info['dict_fields'][$alias] = $field;
        }
    }

    //-------------------------------------------------------------------------

    public function search_dict_fields($row) {
        foreach ($this->dump_info['dict_fields'] as $field => $true) {

            if (isset($row[$field])) {
                $this->set_dict($field, $row[$field]);
            }
        }
    }

    /**************************************************************************
        FILE OPERATIONS
    **************************************************************************/

    public function copy_files($src, $dst, $replace = false, &$result = array(), $move = false, $recursive = true) {

        if (!$result) {
            $result = array(
                'copied'   => 0,
                'skipped'  => 0,
                'replaced' => 0,
            );
        }

        if ($replace && file_exists($dst)) {
            $result['replaced'] ++;
            $this->remove_dir($dst);
        }

        if (is_dir($src)) {
            if (!file_exists($dst)) {
                $this->make_directory($dst);
            }
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && ($recursive || !is_dir("$src/$file"))) {
                    $this->copy_files("$src/$file", "$dst/$file", $replace, $result, $move, true);
                }
            }
        }
        elseif (file_exists($src)) {
            if ($replace && file_exists($dst)) {
                $result['replaced'] ++;
                $this->remove_dir($dst);
            }

            if (copy($src, $dst)) {
                $result['copied'] ++;
            } else {
                $result['skipped'] ++;
            }
        }

        return $result['copied'];
    }

    //-------------------------------------------------------------------------

    public function move_files($src, $dst) {
        $result = $this->copy_files($src, $dst);
        $this->remove_dir($src);
        return $result;
    }

    //-------------------------------------------------------------------------

    public function tar_create($dir, $archive_name = null) {
        require_once nc_core('ADMIN_FOLDER') . 'tar.inc.php';

        $additional_path = dirname(str_replace(nc_core('DOCUMENT_ROOT') . nc_core('SUB_FOLDER'), '', $dir));
        $file_name       = basename($dir);

        if (!$archive_name) {
            $archive_name = $file_name . '.tgz';
        } else {
            $archive_name = rtrim($archive_name, '.tgz') . '.tgz';
        }

        $result = nc_tgz_create($archive_name, $file_name, $additional_path);

        if ($result) {
            $this->remove_dir($dir);
        }

        return $additional_path . '/' . $archive_name;
    }

    //-------------------------------------------------------------------------

    public function tar_extract($file) {
        require_once nc_core('ADMIN_FOLDER') . 'tar.inc.php';

        $archive_path = trim(str_replace(nc_core('DOCUMENT_ROOT'), '', $file), '/');
        $tmp_path     = dirname($archive_path) . '/' . uniqid();
        $abs_tmp_path = nc_core('DOCUMENT_ROOT') . '/' . $tmp_path;

        $this->make_directory($abs_tmp_path);

        nc_tgz_extract($archive_path, $tmp_path);

        $files = scandir($abs_tmp_path);

        if (count($files) == 3) {
            $result_path = nc_core('DOCUMENT_ROOT') . '/' . dirname($archive_path) . '/' . $files[2] . '-' . uniqid();
            $this->move_files($abs_tmp_path . '/' . $files[2], $result_path);
            $this->remove_dir($abs_tmp_path);
        } else {
            $result_path = $abs_tmp_path;
        }

        unlink($file);

        return $result_path;
    }

    /**************************************************************************
        EXPORT METHODS
    **************************************************************************/

    public function get_export_file() {
        return $this->export_file;
    }

    //-------------------------------------------------------------------------

    public function set_export_settings($settings) {
        foreach ($settings as $key => $val) {
            $this->export_settings[$key] = $val;
        }
    }

    //-------------------------------------------------------------------------

    public function get_export_settings($key = null, $default = null) {
        if ($key) {
            return isset($this->export_settings[$key]) ? $this->export_settings[$key] : $default;
        }

        return $this->export_settings;
    }


    //-------------------------------------------------------------------------

    public function export_init($type, $id, $settings = array()) {
        if (count($this->current_object_stack) > 1) {
            $this->set_dump_info('multiple_mode', 1);
            $this->dump_info['sub_export'][$type][$id] = $id;
            return;
        }

        $this->set_export_settings($settings);

        $path = $this->get_export_settings('path');
        if (!$path) {
            $suffix = date('Ymd');
            $default_folder = $this->backup->make_filename($type, $id, $suffix);
            $path = $this->backup->get_export_path($default_folder);
        }
        $this->set_dump_path($path);

        // сброс значений dump_info, часть I
        $this->set_dump_info('version', $this->current_object ? $this->current_object->get_version() : 0);
        $this->set_dump_info('type', $type);
        $this->set_dump_info('time', time());
        $this->set_dump_info('user', (int)$GLOBALS[ 'AUTH_USER_ID']);
        $this->set_dump_info('host', nc_core()->HTTP_HOST);
        $this->set_dump_info('data', array());
        $this->set_dump_info('files', array());
        $this->set_dump_info('dict_fields', array());
        $this->set_dump_info('dict', array());

        if ($this->get_export_settings('remove_existing', true)) {
            // очистить папку-приёмник
            $this->remove_dir($path);
        }
        else {
            // использовать данные из существующего info.php (если он есть)
            $this->load_dump_info();
            $dump_info_type = $this->get_dump_info('type');
            if ($dump_info_type && $dump_info_type != $type) {
                throw new Exception("Folder $path contains export files of '$dump_info_type', current export type is '$type'");
            }
        }

        // сброс значений dump_info, часть II
        $this->set_dump_info('id', $id);

        // создание отсутствующей папки-приёмника
        if (!file_exists($path)) {
            $this->make_directory($path);
        }
    }

    //-------------------------------------------------------------------------

    public function export_finish() {
        $this->save_dump_info();

        if (count($this->current_object_stack) > 1) {
            return;
        }

        $this->export_file = $this->get_dump_path();

        if ($this->get_export_settings('compress')) {
            $this->export_file = $this->tar_create($this->export_file);
        }
    }

    //-------------------------------------------------------------------------

    public function export_data($table, $primary_key, $data, $filename = false) {
        // if data is row
        if (isset($data[$primary_key])) {
            $data = array($data);
        }

        if ($primary_key) {
            $this->register_dict_field($primary_key);
        }

        if (!$data) {
            return false;
        }

        reset($data);

        $custom_suffix = $this->get_export_settings('file_name_suffix');
        if ($filename) {
            $filename = $this->get_filename($filename);
        }
        else if ($custom_suffix) {
            $filename  = $this->get_filename($table, $custom_suffix);
        }
        else {
            $first_row = current($data);
            $first_id  = $first_row[$primary_key];
            $filename  = $this->get_filename($table, $first_id);
        }

        $data_as_xml = $this->get_export_settings('save_data_as_xml', true);

        $this->dump_info['data'][$table]['pk']      = $primary_key;
        $this->dump_info['data'][$table]['fields']  = $data_as_xml ? null : array_keys(current($data));
        if (!isset($this->dump_info['data'][$table]['files']) || !in_array($filename, $this->dump_info['data'][$table]['files'])) {
            $this->dump_info['data'][$table]['files'][] = $filename;
        }

        $this->write_data($filename, $data, $data_as_xml);

        return true;
    }

    //-------------------------------------------------------------------------

    public function export_table($table) {
        $this->dump_info['table'][$table] = $this->sql_make_create($table);
    }

    //--------------------------------------------------------------------------

    public function export_table_fields($table, $fields) {
        $result = nc_db()->get_results("SHOW FIELDS FROM {$table} WHERE Field IN ('" . implode("', '", $fields) . "')", ARRAY_A);
        $this->dump_info['table_fields'][$table] = $result;
    }

    //-------------------------------------------------------------------------

    public function export_files($path, $file = null, $include_sub_folders = true, array $meta = null) {
        $nc_core = nc_Core::get_object();

        if ($file === null) {
            $file = basename($path);
            $path = dirname($path);
        }

        $dump_folder = $path;

        $path     = rtrim($path, '/\\');
        $file     = trim($file, '/\\');
        $src_path = $nc_core->DOCUMENT_ROOT . $path . '/' . $file;

        $dump_path = is_dir($src_path)
                        ? str_replace('/', '.', trim($dump_folder . '.' . $file, '/\\'))
                        : str_replace('/', '.', trim($dump_folder, '/\\')) . '/' . $file;

        $dest_path = $this->get_dump_path($dump_path);
        $dest_folder = dirname($dest_path);
        if (!file_exists($dest_folder)) {
            $this->make_directory($dest_folder);
        }

        $result = array();
        $this->copy_files($src_path, $dest_path, false, $result, false, $include_sub_folders);

        $this->dump_info['files'][$path][$file] = $dump_path;

        if ($meta) {
            if (isset($this->dump_info['files_meta'][$dump_path]) && is_array($this->dump_info['files_meta'][$dump_path])) {
                $this->dump_info['files_meta'][$dump_path] = array_merge_recursive($this->dump_info['files_meta'][$dump_path], $meta);
            } else {
                $this->dump_info['files_meta'][$dump_path] = $meta;
            }
        }
    }

    /**************************************************************************
        IMPORT METHODS
    **************************************************************************/

    public function import_init($file, $settings = array()) {
        if (!$file) {
            throw new Exception("Import file not set", 1);
        }


        if (count($this->current_object_stack) > 1) {
            return null;
        }

        if (!is_dir($file)) {
            $tmp_archive = nc_core()->TMP_FOLDER . uniqid() . '.tgz';
            copy($file, $tmp_archive);
            $file = $this->tar_extract($tmp_archive);
        }

        $this->set_import_settings($settings);

        if (!file_exists($file)) {
            throw new Exception("Import file not found: {$file}", 1);
        }

        $this->import_result = new nc_backup_result;
        $this->set_dump_path($file);

        return $this->load_dump_info();
    }

    //-------------------------------------------------------------------------

    public function import_validation() {

        $version = $this->current_object ? $this->current_object->get_version() : 0;

        if (version_compare($this->get_dump_info('version'), $version, '>')) {
            throw new Exception(TOOLS_DATA_BACKUP_INCOMPATIBLE_VERSION, 1);
        }

        if ($this->get_import_settings('save_ids') && !$this->get_import_settings('disable_id_check')) {
            $data = $this->get_dump_info('data');

            foreach ($data as $table => $settings) {
                $pk       = $settings['pk'];
                $db_table = nc_db_table::make($table, $pk);
                $dict     = $this->get_dict($pk);

                if ($dict) {
                    if ($result = $db_table->where_in_id($dict)->get_list($pk)) {
                        $ids = implode(', ', $result);
                        throw new Exception(TOOLS_DATA_BACKUP_IMPORT_DUPLICATE_KEY_ERROR . "<br>Table: {$table}<br>IDs: {$ids}", 1);
                    }
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    public function set_import_result($key, $value = null, $append = false) {
//        if (is_array($key)) {
//            $this->import_result += $key;
//        }

        if ($append && isset($this->import_result[$key])) {
            $this->import_result[$key] += $value;
        } else {
            $this->import_result[$key] = $value;
        }
    }

    //-------------------------------------------------------------------------

    public function get_import_result($key = null, $default = null) {
        if ($key) {
            return isset($this->import_result[$key]) ? $this->import_result[$key] : $default;
        }

        return $this->import_result;
    }

    //-------------------------------------------------------------------------

    public function set_import_settings($settings) {
        foreach ($settings as $key => $val) {
            $this->import_settings[$key] = $val;
        }
    }

    //-------------------------------------------------------------------------

    public function get_import_settings($key = null, $default = null) {
        if ($key) {
            return isset($this->import_settings[$key]) ? $this->import_settings[$key] : $default;
        }

        return $this->import_settings;
    }

    //-------------------------------------------------------------------------

    /**
     * @param string $table
     * @param string|null $new_table
     * @param array $additional_dict_fields  Each array item can be one of the following:
     *    (a) 'table_field' => 'dict_field' — get value from the dictionary
     *    (b) 'dict_field' — get value from the dictionary (same as field name)
     *    (c) 'table_field' => array($object, 'method') — call specified method which should return the mapped value
     *            (arguments: $row, $field_name)
     * @return bool
     * @throws Exception
     */
    public function import_data($table, $new_table = null, array $additional_dict_fields = array()) {
        $save_ids    = $this->get_import_settings('save_ids');
        $data        = $this->get_dump_info('data', $table);
        $dict_fields = array_merge($this->get_dump_info('dict_fields'), $additional_dict_fields);
        $new_table   = $new_table ?: $table;

        $pk     = nc_array_value($data, 'pk');
        $fields = nc_array_value($data, 'fields', array());
        $xmls   = nc_array_value($data, 'files');

        $reset_field_values = $this->get_reset_field_values($table);

        if (!$xmls) {
            return false;
        }
        $db_table = nc_db_table::make($new_table, $pk);
        $db_table->set_fields_determination(true);

        $message_table = substr($new_table, 0, 7) == 'Message' ? substr($new_table, 7) : false;

        $lower_table         = $message_table ? 'message' : strtolower($new_table);
        $event_before_insert = 'before_insert_' . $lower_table;
        $event_after_insert  = 'after_insert_' . $lower_table;

        $possibly_replace = $pk && $save_ids && $this->get_import_settings('disable_id_check');

        foreach ($xmls as $xml) {
            $xml_file = $this->get_dump_path($xml);

            if (!file_exists($xml_file)) {
                throw new Exception("XML file not found: {$xml}", 1);
            }

            $data = $this->read_data($xml_file);

            foreach ($data as $row) {
                if (!is_array($row)) {
                    $row = array_combine($fields, unserialize(base64_decode($row)));
                }
                $original_row = $row;
                $id = ($pk ? $row[$pk] : null);

                foreach ($dict_fields as $alias => $field) {
                    if (isset($row[$alias])) {
                        if ($field === true) {
                            $field = $alias;
                        }

                        if (is_array($field) && is_object($field[0]) && method_exists($field[0], $field[1])) {
                            // array with object instance and method name ('callable')
                            $row[$alias] = $field[0]->{$field[1]}($original_row, $alias);
                        }
                        else {
                            // dict entry name
                            $row[$alias] = $this->get_dict($field, $row[$alias]);
                        }
                    }
                }

                // Сброс значений определенных полей
                foreach ($reset_field_values as $reset_field_name => $reset_value) {
                    if (isset($row[$reset_field_name])) {
                        $row[$reset_field_name] = $reset_value;
                    }
                }

                $event_args = $message_table ? array($message_table, &$row) : array(&$row);
                $this->call_event($event_before_insert, $event_args, $row);

                if ($row === false) {
                    continue;
                }

                if (!$save_ids) {
                    unset($row[$pk]);
                }

                if ($possibly_replace && $db_table->where_id($row[$pk])->limit(1)->get_value($pk)) {
                    $new_id = $row[$pk];
                    $db_table->where_id($new_id)->update($row);
                }
                elseif ($new_id = $db_table->set($row)->insert()) {
                    $this->set_import_result('total_insert_rows', +1, true);
                }
                elseif ($error = $db_table->get_last_error()) {
                    $error_table = $db_table->get_table();
                    $error = 'TABLE: ' . $error_table . ($error_table != $table ? " ({$table})" : '')
                        . PHP_EOL . 'ERROR: ' . $error
                        . PHP_EOL . 'SQL: ' . $db_table->get_last_query();
                        // . PHP_EOL . 'ROW: ' . htmlspecialchars(print_r($row, true));

                    throw new Exception($error, 1);
                }

                if ($pk) {
                    $this->set_dict("$new_table.$pk", $id, $new_id);
                    if ($pk != 'Message_ID') {
                        $this->set_dict($pk, $id, $new_id);
                    }
                }

                $event_args[] = $new_id;
                $event_args[] = $id; // старый ID (до импорта)
                $this->call_event($event_after_insert, $event_args);
            }
        }

        return true;
    }

    //-------------------------------------------------------------------------

    public function import_table($table, $new_table = null) {
        $sql = $this->get_dump_info('table', $table);

        if ($new_table) {
            $sql = str_ireplace("`{$table}`", "`{$new_table}`", $sql);
        }

        nc_db()->query($sql);
        $this->set_import_result('total_create_tables', +1, true);
    }

    //--------------------------------------------------------------------------

    public function import_table_fields($table) {
        $new_fields = $this->get_dump_info('table_fields', $table);
        if ($new_fields) {
            $db = nc_db();
            $existing_fields = array();
            foreach ($db->get_results("DESCRIBE `$table`") as $row) {
                $existing_fields[$row->Field] = $row->Field;
            }
            foreach ($new_fields as $row) {
                $field_name = $row['Field'];
                if (!isset($existing_fields[$field_name])) {
                    $field_type = $row['Type'];
                    $null = $row['Null'] === 'YES' ? '' : ' NOT NULL';
                    $default = is_null($row['Default']) || !$row['Default'] ? '' : " DEFAULT '" . $db->escape($row['Default']) . "'";

                    $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$field_name}` {$field_type}{$null}{$default};");
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    public function import_files(array $path_prefixes = null, $use_remove_files_setting = true) {
        $import_files = $this->get_dump_info('files');
        $import_files_meta = $this->get_dump_info('files_meta');

        $doc_root = nc_core('DOCUMENT_ROOT');

        // префиксы путей, которые будут импортированы
        $path_regexp = $path_prefixes ? "!^(?:" . join("|", $path_prefixes) . ")!" : null;

        foreach ($import_files as $path => $files) {
            if ($path_regexp && !preg_match($path_regexp, "$path/")) {
                continue;
            }

            $path = rtrim($path, '/') . '/';

            foreach ($files as $file => $src) {
                $file = trim($file, '/');
                $file_path = $original_file_path = $path . $file;
                $meta = nc_array_value($import_files_meta, $src);

                $this->call_event('before_copy_file', array($path, $file, $src, $meta), $file_path);
                if (!$file_path) { continue; }

                $dest = $doc_root . $file_path;
                $src  = $this->get_dump_path($src);

                // $replace = false;
                // if ($this->copy_files($src, $dest, $replace, $result)) {
                //     $this->set_import_result('total_copied_files',   $result['copied'], true);
                //     $this->set_import_result('total_skipped_files',  $result['skipped'], true);
                //     $this->set_import_result('total_replaced_files', $result['replaced'], true);
                // }

                if (file_exists($dest) && is_dir($dest) && $use_remove_files_setting && $this->get_import_settings('remove_existing', true)) {
                    $this->remove_dir($dest);
                }

                $parent_dir = dirname($dest);
                if (!file_exists($parent_dir)) {
                    $this->make_directory($parent_dir);
                }

                if (@rename($src, $dest)) {
                    $this->set_import_result('total_copied_files', +1, true);
                }
                else {
                    $this->set_import_result('total_skipped_files', +1, true);
                }

                $this->call_event('after_copy_file', array($dest, $original_file_path, $meta));
            }
        }
    }

    //-------------------------------------------------------------------------

    public function import_finish() {
        $this->remove_dir($this->get_dump_path());
        // $this->save_dump_info();
        // $this->tar_create($this->get_dump_path());
    }

    /**************************************************************************
        XML DRIVER PART
    **************************************************************************/

    public function get_filename() {
        $args = func_get_args();
        return implode(nc_backup::FILENAME_DIVIDER, $args) . '.xml';
    }

    //-------------------------------------------------------------------------

    protected function make_domdocument() {
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput       = true;
        $xml->encoding           = 'utf-8';
        return $xml;
    }

    //-------------------------------------------------------------------------

    protected function write_data($filename, $data, $as_xml = false) {
        $xml_filepath = $this->get_dump_path($filename);

        if (file_exists($xml_filepath)) {
            unlink($xml_filepath);
        }

        $xml = $this->make_domdocument();

        $xml_root = $xml->createElement('root');
        $xml->appendChild($xml_root);

        foreach ($data as $row) {
            $this->search_dict_fields($row);

            $xml_row = $xml->createElement('row');
            if ($as_xml) {
                $xml_row = $this->array_to_xml($xml_row, $row);
            }
            else {
                $row = base64_encode(serialize(array_values($row)));
                $xml_row->appendChild($xml->createTextNode($row));
            }

            $xml_root->appendChild($xml_row);
        }

        file_put_contents($xml_filepath, $xml->saveXML());
    }

    //-------------------------------------------------------------------------

    protected function array_to_xml(DOMNode $target_element, array $data) {
        $dom = $target_element->ownerDocument;
        foreach ($data as $k => $v) {
            // недопустимые названия для тэгов: начинаются с "XML" или цифры
            if (preg_match('/^(?:xml|\d)/i', $k)) {
                $tag_name = "__$k";
            }
            else {
                $tag_name = $k;
            }
            $value_element = $dom->createElement($tag_name);

            if ($v === null) {
                $value_element->setAttribute('type', 'null');
            }
            else if (!is_scalar($v)) {
                // сериализация многомерных массивов в xml не реализована, пока не нужна
                // throw new UnexpectedValueException();
            }
            else {
                if (is_float($v)) {
                    $v = str_replace(',', '.', $v);
                }

                if (preg_match('/[&<>"\'\r]/u', $v) || !preg_match('//u', $v)) {
                    $node_content = $dom->createCDATASection($v);
                }
                else {
                    $node_content = $dom->createTextNode($v);
                }

                $value_element->appendChild($node_content);
            }

            $target_element->appendChild($value_element);
        }

        return $target_element;
    }

    //-------------------------------------------------------------------------

    public function read_data($xmlfile) {
        $data = array();

        $xml = $this->make_domdocument();
        $xml->load(realpath($xmlfile));
        $rows = $xml->documentElement->childNodes;

        /** @var DOMElement $row */
        foreach ($rows as $row) {
            if ($row->firstChild instanceof DOMText || $row->firstChild instanceof DOMCdataSection) {
                $data[] = $row->nodeValue;
            }
            else {
                $data[] = $this->xml_to_array($row);
            }
        }

        return $data;
    }

    //-------------------------------------------------------------------------

    protected function xml_to_array(DOMElement $row) {
        $data = array();
        /** @var DOMElement $item */
        foreach ($row->childNodes as $item) {
            $key = $item->tagName;
            if (substr($key, 0, 2) == '__') {
                $key = substr($key, 2);
            }

            if ($item->getAttribute('type') === 'null') {
                $value = null;
            }
            else {
                // многомерные массивы не поддерживаются, нет необходимости в этом пока
                $value = $item->nodeValue;
            }
            $data[$key] = $value;
        }

        return $data;
    }

    //-------------------------------------------------------------------------

    protected function sql_make_create($table) {
        $db = nc_db();
        $db->query("SET SQL_QUOTE_SHOW_CREATE = 1;");

        $result = $db->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);

        if (!$result) {
            return false;
        }

        // Учитываем имя таблицы в дампе, сделанном с lower_case_table_names=1
        $dump = str_ireplace("CREATE TABLE `{$table}`", "CREATE TABLE `{$table}`", $result[1]);

        return preg_replace(array('/ AUTO_INCREMENT=\d+/', '/ENGINE=Aria/', '/ PAGE_CHECKSUM=1/'), array('', 'ENGINE=MyISAM', ''), $dump);
    }

    //-------------------------------------------------------------------------
    //

    protected function remove_dir($path) {

        $exclude_paths = array(
          nc_core('DOCUMENT_ROOT') . '/',
          nc_core('NETCAT_FOLDER'),
          nc_core('ADMIN_FOLDER'),
          nc_core('SYSTEM_FOLDER'),
          nc_core('INCLUDE_FOLDER'),
        );

        $result = 0;
        $path = rtrim($path, '/\\');

        if (in_array("$path/", $exclude_paths)) {
            return false;
        }

        if (!file_exists($path)) {
            return false;
        }

        if (is_dir($path)) {
            $dh = opendir($path);
            while ($f = readdir($dh)) {
                if ($f == '.' || $f == '..')
                    continue;
                $result += $this->remove_dir($path . '/' . $f);
            }
            closedir($dh);
            $result += rmdir($path);
        }
        else {
            $result += unlink($path);
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function get_reset_field_values($table) {
        $fields = array();

        if (!empty($this->reset_field_values['*'])) {
            $fields = $this->reset_field_values['*'];
        }

        if (!empty($this->reset_field_values[$table])) {
            $fields = array_merge($fields, $this->reset_field_values[$table]);
        }

        return $fields;
    }

    //-------------------------------------------------------------------------

    protected function make_directory($path) {
        return mkdir($path, nc_core('DIRCHMOD'), true);
    }

}