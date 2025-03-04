<?php

class nc_patch_installer extends nc_tgz_installer {

    protected $update_server_url = 'http://update.netcat.ru/';

    protected $id_file_fields = array(
        'patch_name', // версия патча, в актуальных версиях всегда 'current' (заменяется в Patch после установки на число вида "610")
        'system_edition_id', // ID редакции, 0 если подходит для всех редакций
        'system_version', // major-версия системы, например 6.1
        'description', // текстовое описание патча, в UTF-8
    );

    protected $required_files = array(
        'id.txt',
        'required.txt',
        'files.txt',
        'sql.txt',
        // 'symlinks.txt', // не используется в актуальных версиях
        'install.inc.php',
    );

    /**
     * @param string $uploaded_file
     * @return array
     * @throws nc_patch_installer_exception
     * @throws nc_tgz_installer_exception
     */
    public function install_from_uploaded_file($uploaded_file) {
        if (!is_uploaded_file($uploaded_file)) {
            throw new nc_patch_installer_exception(TOOLS_MODULES_ERR_NOTUPLOADED);
        }

        $file = tempnam($this->work_folder, 'patch') . '.tgz';
        move_uploaded_file($uploaded_file, $file);

        return $this->install_from_file($file);
    }

    /**
     * @return array
     * @throws nc_patch_installer_exception
     * @throws nc_tgz_installer_exception
     */
    public function install_from_server() {
        $file = $this->download();

        if ($file) {
            return $this->install_from_file($file);
        }

        // Если установлено последнее обновление, сервер обновлений не вернёт файл ($file = false).
        // Это не является ошибкой при автоматической установке обновлений.
        return array(
            'success' => true,
            'messages' => nc_print_status(TOOLS_PATCH_ERROR_UPDATE_FILE_NOT_AVAILABLE, 'error', null, true),
        );
    }

    /**
     * @return string
     */
    protected function get_update_request_data() {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        $modules = (array)$db->get_col("SELECT `Keyword` FROM `Module`");

        $host = nc_array_value($_SERVER, 'HTTP_HOST');
        if (!$host) {
            $sites = $nc_core->catalogue->get_all();
            if (isset($sites[0]['Domain'])) {
                $host = $sites[0]['Domain'];
            }
        }

        $data =
            "<?xml version='1.0' encoding='UTF-8'?>\n" .
            "<netcat>\n" .
            "<reason>update</reason>\n" .
            "<version>\n" .
            "<patch>" . $db->get_var("SELECT `Patch_Name` FROM `Patch` as `p` ORDER BY p.`Patch_Name` DESC LIMIT 1") . "</patch>\n" .
            "<patchType>" . $nc_core->get_settings('LastPatchType') . "</patchType>\n" .
            "<build>" . $nc_core->get_settings('LastPatchBuildNumber') . "</build>\n" .
            "<name>" . $nc_core->get_settings('SystemID') . "</name>\n" .
            "</version>\n" .
            "<core>" . $nc_core->get_settings('ProductNumber') . "</core>\n" .
            "<code>" . $nc_core->get_settings('Code') . "</code>\n" .
            "<unicode>" . (int)$nc_core->NC_UNICODE . "</unicode>\n" .
            "<host>\n" .
            "<ip>" . nc_array_value($_SERVER, 'SERVER_ADDR') . "</ip>\n" .
            "<url>" . $host . "</url>\n" .
            "</host>\n" .
            "<modules>\n";
        foreach ($modules as $value) {
            $data .= "<module>\n<number></number>\n<name>$value</name>\n</module>\n";
        }

        $data .=
            "</modules>\n" .
            "</netcat>\n";

        return $data;
    }

    /**
     * @return false|string путь к скачанному файлу
     * @throws nc_patch_installer_exception
     */
    protected function download() {
        $nc_core = nc_core::get_object();

        $data = $this->get_update_request_data();
        $result = array();

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' =>
                    "Content-type: application/x-www-form-urlencoded\r\n" .
                    "Content-Length: " . strlen($data) . "\r\n",
                'content' => $data,
            ),
        );

        $options = nc_set_stream_proxy_params($options);
        $context = stream_context_create($options);

        // get data from update server
        $request = @file_get_contents($this->update_server_url, false, $context);

        if ($request) {
            $parser = xml_parser_create();
            xml_parse_into_struct($parser, $request, $values, $indexes);
            xml_parser_free($parser);
        } else {
            throw new nc_patch_installer_exception(TOOLS_PATCH_ERROR_UPDATE_SERVER_NOT_AVAILABLE);
        }

        if (empty($values)) {
            return false;
        }

        foreach ($values as $value) {
            if ($value['type'] !== 'complete') {
                continue;
            }
            $result[$value['tag']] = $value['value'];
        }

        if ($result['OPERATION'] === 'success') {
            if ($result['CODE']) {
                $nc_core->set_settings('Code', $result['CODE']);
            }
        } else {
            $message = $result['MESSAGE'];
            if (!$nc_core->NC_UNICODE) {
                $message = $nc_core->utf8->utf2win($message);
            }
            throw new nc_patch_installer_exception($message);
        }

        $update_file = false;
        if ($result['LINK']) {
            $context = stream_context_create(nc_set_stream_proxy_params());
            $update_file = @file_get_contents($result['LINK'], false, $context);
        }

        // write file on disk
        if ($update_file) {
            $patch_file_name = "$this->work_folder/update_" . md5(microtime()) . '.tgz';
            if (file_put_contents($patch_file_name, $update_file)) {
                return $patch_file_name;
            }
            throw new nc_patch_installer_exception(TOOLS_PATCH_ERROR_AUTOINSTALL . "<br/><br/><a href='" . $result['LINK'] . "'>" . TOOLS_PATCH_DOWNLOAD_LINK_DESCRIPTION . "</a>");
        }

        return false;
    }

    /**
     * @param $patch_file_tgz
     * @return array
     * @throws nc_patch_installer_exception
     * @throws nc_tgz_installer_exception
     */
    public function install_from_file($patch_file_tgz) {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        $result = array();

        // имеющиеся патчи считают, что должны находиться строго в $nc_core->TMP_FOLDER,
        // а установщик так не считает, поскольку в TMP_FOLDER могут находиться несвязанные файлы (например, файлы обмена с 1С)
        $original_tmp_folder = $nc_core->TMP_FOLDER;
        $nc_core->TMP_FOLDER = $GLOBALS['TMP_FOLDER'] = $this->work_folder . '/';

        try {
            ob_start();
            $this->extract($patch_file_tgz);

            $id_txt = $this->load_id_txt();
            $files = $this->check_file_list();

            $this->check_system_version($id_txt);
            $this->check_patch_version($id_txt);

            // Подключение install.inc.php (содержит функции установки патча)
            require "$this->work_folder/install.inc.php";

            $install_possibility = CheckAbilityOfInstallation($this);
            if ((int)$install_possibility['Success'] === 0) {
                throw new nc_patch_installer_exception(TOOLS_PATCH_ERROR . ": " . $install_possibility['ErrorMessage']);
            }

            $install_result = InstallPatch($this);
            if ((int)$install_result['Success'] === 0) {
                throw new nc_patch_installer_exception(TOOLS_PATCH_ERROR_DURINGINSTALL . ": " . $install_result['ErrorMessage']);
            }

            $this->save_patch_info($id_txt); // надо выполнить до execute_sql()

            $this->copy_files($files);
            $this->execute_sql();
            // CreatLinks(); // symlinks.txt в актуальных версиях не используется

            if (function_exists('InstallPatchAfterAction')) {
                $result['after_action'] = InstallPatchAfterAction($this);
            }

            $result['sql_errors'] = $db->captured_errors;
            $result['messages'] = ob_get_clean() . nc_print_status(TOOLS_PATCH_INSTALLED . '.', 'ok', null, true);
            $result['success'] = $result['after_action'] && !$result['sql_errors'];

            $nc_core->TMP_FOLDER = $GLOBALS['TMP_FOLDER'] = $original_tmp_folder;

            // обновление настроек системы для последующего обновления сведений о доступных патчах
            // (в частности — нужен актуальный LastPatchBuildNumber)
            nc_core::get_object()->get_settings(null, null, true);

            // обновление сведений о доступных патчах
            CheckForNewPatch();

            return $result;
        } catch (Exception $exception) { // finally доступен начиная с PHP 5.5
            $nc_core->TMP_FOLDER = $GLOBALS['TMP_FOLDER'] = $original_tmp_folder;
            throw $exception;
        }
    }

    /**
     * @param array $id_txt
     * @throws nc_patch_installer_exception
     */
    protected function check_patch_version(array $id_txt) {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        // (1) Установлен требуемый патч (required.txt)?
        $required_version = trim(file_get_contents("$this->work_folder/required.txt"));

        if (!$db->get_var("SELECT 1 FROM `Patch` WHERE `Patch_Name` = '" . $db->escape($required_version) . "'")) {
            throw new nc_patch_installer_exception(TOOLS_MODULES_ERR_PATCH . ' ' . $required_version);
        }

        // (2) Патч с таким Patch_Name ещё не установлен?
        if ($db->get_var("SELECT `Patch_Name` FROM `Patch` WHERE `Patch_Name` = '" . $db->escape($id_txt['patch_name']) . "'")) {
            throw new nc_patch_installer_exception(TOOLS_PATCH_ALREDYINSTALLED);
        }
    }

    /**
     * @param array $id_txt
     * @throws nc_patch_installer_exception
     */
    protected function check_system_version(array $id_txt) {
        $nc_core = nc_core::get_object();

        // Проверка версии системы
        $actual_minor_version = preg_replace('/^(\d+\.\d+).*$/', '$1', $nc_core->get_settings('VersionNumber'));
        $required_minor_version = preg_replace('/^(\d+\.\d+).*$/', '$1', $id_txt['system_version']);
        if ($required_minor_version !== $actual_minor_version) {
            $message = str_replace(array('%REQUIRE', '%EXIST'), array($required_minor_version, $actual_minor_version), TOOLS_PATCH_INVALIDVERSION);
            throw new nc_patch_installer_exception($message);
        }

        // Проверка редакции системы
        $required_edition_id = (int)$id_txt['system_edition_id'];
        $actual_edition_id = (int)$nc_core->get_settings('SystemID');
        if ($required_edition_id && $required_edition_id !== $actual_edition_id) {
            list($required_edition_name) = nc_system_name_by_id($required_edition_id);
            list($actual_edition_name) = nc_system_name_by_id($actual_edition_id);
            $message = str_replace(array('%REQUIRE', '%EXIST'), array($required_edition_name, $actual_edition_name), TOOLS_PATCH_INVALIDVERSION);
            throw new nc_patch_installer_exception($message);
        }
    }

    /**
     * @param array $id_txt
     */
    protected function save_patch_info(array $id_txt) {
        $db = nc_db();
        $db->query(
            "INSERT INTO `Patch`
             SET `Patch_Name` = '" . $db->escape($id_txt['patch_name']) . "', 
                 `Description` = '" . $db->escape($id_txt['description']) . "',
                 `Created` =  NOW()"
        );
        $this->add_changelog_system_message($id_txt['description']);
    }

    /**
     * @param string $description
     */
    protected function add_changelog_system_message($description) {
        $changelog = nc_patch_changelog();
        if ($changelog) {
            $nc_core = nc_core::get_object();
            if (!function_exists('AddSystemMessage')) {
                include $nc_core->ADMIN_FOLDER . 'report/system.inc.php';
            }

            $message_id = AddSystemMessage($changelog, $description ?: TOOLS_PATCH_INSTALLED);
            if ($message_id) {
                nc_print_status(str_replace('%LINK', $nc_core->ADMIN_PATH . 'report/system.php?SystemMessageID=' . $message_id, TOOLS_PATCH_INFO_SYSTEM_MESSAGE), 'info');
            }
        }
    }

}