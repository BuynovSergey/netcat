<?php

/**
 * Базовый класс для работы с tgz-архивами обновлений, активации и модулей
 */
abstract class nc_tgz_installer {

    /** @var string возможно взаимодействие с пользователем (установка патча в админке) */
    const MODE_INTERACTIVE = 'interactive';
    /** @var string автоматическая установка (консоль или крон) */
    const MODE_AUTOMATED = 'automated';

    /** @var string режим работы установщика */
    protected $mode = self::MODE_INTERACTIVE;

    /** @var string путь к папке, куда будет распакован архив (генерируется автоматически) */
    protected $work_folder;

    /** @var array названия строк в id.txt */
    protected $id_file_fields = array();

    /** @var array файлы, которые обязательно должны быть в архиве */
    protected $required_files = array();

    /**
     * @throws nc_tgz_installer_exception
     */
    public function __construct() {
        $this->work_folder = $this->create_temporary_folder();
    }

    /**
     * Удаляет папку для распакованного архива
     */
    public function __destruct() {
        nc_delete_dir($this->work_folder);
    }

    /**
     * @param string $mode
     */
    public function set_mode($mode) {
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function get_mode() {
        return $this->mode;
    }

    /**
     * @return string
     * @throws nc_tgz_installer_exception
     */
    protected function create_temporary_folder() {
        $nc_core = nc_core::get_object();

        do {
            $path = nc_core::get_object()->TMP_FOLDER . md5(mt_rand(0, PHP_INT_MAX));
        } while (file_exists($path));

        if (!mkdir($path, $nc_core->DIRCHMOD, true) && !is_dir($path) && !is_writable($path)) {
            $message = sprintf(TOOLS_PATCH_ERROR_TMP_FOLDER_NOT_WRITABLE, dirname($path), dirname($path));
            throw new nc_tgz_installer_exception($message);
        }

        return $path;
    }

    /**
     * @param string $tgz_file
     * @throws nc_tgz_installer_exception
     */
    protected function extract($tgz_file) {
        if (!file_exists($tgz_file)) {
            throw new nc_tgz_installer_exception(TOOLS_MODULES_ERR_NOTUPLOADED);
        }

        if (!is_writable($this->work_folder)) {
            throw new nc_tgz_installer_exception(sprintf(NETCAT_FILEUPLOAD_ERROR, $this->work_folder));
        }

        if (!nc_tgz_extract($tgz_file, $this->work_folder)) {
            throw new nc_tgz_installer_exception(TOOLS_PATCH_ERR_EXTRACT);
        }

        $this->check_required_files();
    }

    /**
     * @throws nc_tgz_installer_exception
     */
    protected function check_required_files() {
        foreach ($this->required_files as $file) {
            if (!is_readable("$this->work_folder/$file")) {
                throw new nc_tgz_installer_exception(TOOLS_MODULES_ERR_CANTOPEN . " $file");
            }
        }
    }

    /**
     * @return array
     * @throws nc_tgz_installer_exception
     */
    protected function load_id_txt() {
        $values = (array)array_combine($this->id_file_fields, file("$this->work_folder/id.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

        if (!$values) {
            throw new nc_tgz_installer_exception(TOOLS_MODULES_ERR_CANTOPEN . ' id.txt');
        }

        $nc_core = nc_core::get_object();
        if (!$nc_core->NC_UNICODE) {
            $values = $nc_core->utf8->utf2win($values);
        }
        return $values;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function is_writable($path) {
        if (file_exists($path) && is_writable($path)) {
            return true;
        }
        $path = dirname($path);
        if (strpos($path, nc_core::get_object()->DOCUMENT_ROOT) === 0) {
            return $this->is_writable($path);
        }
        return false;
    }

    /**
     * @return array
     * @throws nc_patch_installer_exception
     */
    protected function check_file_list() {
        $nc_core = nc_core::get_object();

        $no_permissions_files = array();
        $files = file("$this->work_folder/files.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($files as $file) {
            // file and his dir path
            $file_full_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $file;
            if (!$this->is_writable($file_full_path)) {
                $no_permissions_files[] = $file;
            }
        }

        if (count($no_permissions_files)) {
            throw new nc_patch_installer_exception(TOOLS_PATCH_ERROR_FILELIST_NOT_WRITABLE . "<br><br>\n" . implode("<br>\n", $no_permissions_files));
        }

        return $files;
    }

    /**
     * @param array $files
     * @return array ['total' => 0, 'copied' => 0]
     * @throws nc_tgz_installer_exception
     */
    protected function copy_files(array $files) {
        $nc_core = nc_core::get_object();

        $result = array('total' => 0, 'copied' => 0);

        foreach ($files as $path) {
            $source_file_path = $this->work_folder . $path;
            $target_file_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $path;
            $target_folder = dirname($target_file_path);
            if (!is_dir($target_folder) && !mkdir($target_folder, $nc_core->DIRCHMOD, true) && !is_dir($target_folder) && !is_writable($target_folder)) {
                $message = sprintf(TOOLS_PATCH_ERROR_TMP_FOLDER_NOT_WRITABLE, dirname($path), $path);
                throw new nc_tgz_installer_exception($message);
            }
            $file_copied = @copy($source_file_path, $target_file_path);
            if ($file_copied) {
                $result['copied']++;
            }

            $result['total']++;
        }

        return $result;
    }

    /**
     * @return array ['sqls' => 0, 'total' => 0]
     */
    protected function execute_sql() {
        return ExecSQL("$this->work_folder/sql.txt");
    }

}