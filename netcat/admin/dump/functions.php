<?php

$known_tgz_archive_names_in_the_dump = array('images', 'modules', 'netcat_template', 'netcat_files');

if ($dump_options['mode'] === 'full') {
    $known_tgz_archive_names_in_the_dump = array('everything', 'images', 'modules', 'netcat_template', 'netcat_files');
}

function get_lang() {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    switch ($lang) {
        case 'en':
        case 'ru':
            break;
        default:
            $lang = 'en';
    }
    return $lang;
}

function get_tr() {
    $en['header'] = 'CMS restore';
    $en['mysqlsettings'] = 'Please, specify your MySQL settings:';
    $en['restore_button'] = 'Restore backup';
    $en['hostname'] = 'Hostname';
    $en['username'] = 'User';
    $en['password'] = 'Password';
    $en['dbname'] = 'Database name';
    $en['port'] = 'Port';
    $en['socket'] = 'Socket';
    $en['charset'] = 'Charset';
    $en['nosuchfile'] = 'no such file';
    $en['ismissing'] = 'is missing';
    $en['restorefailed'] = 'Restore failed due to an errors';
    $en['extracting'] = 'Extracting';
    $en['done'] = 'done';
    $en['err_nosettings'] = 'You did not specified all required SQL settings. Please do.';
    $en['err_restore'] = 'Error while restoring SQL dump';
    $en['finish_text'] = 'Restore completed successfully. <br /><br /><b>IMPORTANT:</b> remove restore.php, netcat.sql and *.tgz archives from your site!<br /><br />To open administration panel, open ';
    $en['finish_text_json'] = 'Restore completed successfully.';
    $en['finish_link_title'] = 'this link';
    $en['restore_init'] = 'Running restore, please wait...';
    $en['dbrestore'] = 'Restoring database...';


    $ru['header'] = 'Восстановление CMS';
    $ru['mysqlsettings'] = 'Пожалуйста, укажите настройки MySQL:';
    $ru['restore_button'] = 'Восстановить из резервной копии';
    $ru['hostname'] = 'Хост';
    $ru['username'] = 'Имя пользователя';
    $ru['password'] = 'Пароль';
    $ru['dbname'] = 'Имя базы данных';
    $ru['port'] = 'Порт';
    $ru['socket'] = 'Сокет';
    $ru['charset'] = 'Кодировка';
    $ru['nosuchfile'] = 'файл не найден';
    $ru['ismissing'] = 'отсутствует';
    $ru['restorefailed'] = 'Восстановление прервано из-за ошибок';
    $ru['extracting'] = 'Распаковка';
    $ru['done'] = 'готово';
    $ru['err_nosettings'] = 'Вы не указали все необходимые настройки MySQL. Пожалуйста, сделайте это.';
    $ru['err_restore'] = 'При восстановлении базы данных MySQL произошла ошибка';
    $ru['finish_text'] = 'Восстановление успешно завершено. <br /><br /><b>ВАЖНО:</b> обязательно удалите файлы restore.php, netcat.sql и архивы *.tgz из корня вашего сайта!<br /><br />Чтобы открыть панель администрирования, откройте ';
    $ru['finish_text_json'] = 'Восстановление успешно завершено.';
    $ru['finish_link_title'] = 'эту ссылку';
    $ru['restore_init'] = 'Выполняется восстановление, подождите пожалуйста...';
    $ru['dbrestore'] = 'Восстановление базы данных...';


    $lang = get_lang();

    switch ($lang) {
        case 'en': $str = $en;
            break;
        case 'ru': $str = $ru;
            break;
        default: $str = $en;
            break;
    }
    return $str;
}

function check(array $archive_names) {
    // To restore whole system, we need all of these archives, and an SQL dump
    // Also, we need a working tar to do this.
    // Let's check that:

    $str = get_tr();
    $json = trim($_REQUEST['format'] === 'json');
    $errors = array();
    foreach ($archive_names as $archive_name) {
        if (!file_exists($archive_name . '.tgz')) {
            $errors[] = $archive_name . '.tgz: ' . $str['nosuchfile'];
        }
    }

    if (!file_exists('netcat.sql')) {
        $errors[] = 'netcat.sql ' . $str['ismissing'];
    }

    if (count($errors) > 0) {
        $json_errors = array();
        foreach ($errors as $e) {
            if (!$json) {
                echo $e . "<br />\n";
            } else {
                $json_errors[] = $e;
            }
        }
        return $str['restorefailed'] . ($json ? ": " . implode(", ", $json_errors) : null);
    }
    
    return true;
}

function tgz_check_exec() {
    // check whether to use system() call to tar [faster]
    if (stripos(PHP_OS, 'Windows') !== false) {  // it's not Windows
        $err_code = 127;
        @exec('tar --version', $output, $err_code);
        define('SYSTEM_TAR', ($err_code ? false : true));
    } else {
        define('SYSTEM_TAR', false);
    }
}

function extract_files(array $archive_names) {
    // Check what type of tar to use
    tgz_check_exec();
    $str = get_tr();
    $json = trim($_REQUEST['format'] === 'json');

    // If all OK, let's make some fun :)
    // Extract stuff
    foreach ($archive_names as $archive_name) {
        if (!file_exists("$archive_name.tgz")) {
            continue;
        }
        if (!$json) {
            echo "$str[extracting] $archive_name.tgz...\n";
        }
        flush();
        if (SYSTEM_TAR) {
            nl2br(system('tar xf ' . $archive_name . '.tgz 2>&1', $unpack_result));
        } else {
            $tar_object = new Archive_Tar($archive_name . '.tgz', 'gz');
            $tar_object->setErrorHandling(PEAR_ERROR_PRINT);
            $tar_object->extract('.');
        }
        if (!$json) {
            echo $str['done'] . "<br />\n";
        }
        flush();
    }
}

// Guess DB connection settings from vars.inc.php
function guess_sql_settings() {
    if (!file_exists('vars.inc.php')) {
        system('tar xf everything.tgz ./vars.inc.php >/dev/null 2>&1');
    }
    if (file_exists('vars.inc.php')) {
        require_once 'vars.inc.php';
        /** @var $MYSQL_USER */
        /** @var $MYSQL_PASSWORD */
        /** @var $MYSQL_HOST */
        /** @var $MYSQL_DB_NAME */
        /** @var $MYSQL_CHARSET */
        /** @var $MYSQL_PORT */
        /** @var $MYSQL_SOCKET */
        return array($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME, $MYSQL_PORT, $MYSQL_SOCKET, $MYSQL_CHARSET);
    }

    return NULL;
}

// Import SQL dump
function restore_dump($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME, $MYSQL_PORT, $MYSQL_SOCKET, $MYSQL_CHARSET) {
    // Try via command-line mysql first
    $err_code = 127;

    $path = __DIR__ . DIRECTORY_SEPARATOR;

    $exec_str = 'mysql --host=' . escapeshellarg($MYSQL_HOST) .
      ' --user=' . escapeshellarg($MYSQL_USER) .
      ' --password=' . escapeshellarg($MYSQL_PASSWORD) .
      ' --default-character-set=' . $MYSQL_CHARSET . ' ' .
      '  ' . escapeshellarg($MYSQL_DB_NAME) . ' < ' . $path . 'netcat.sql 2>&1';
    exec($exec_str, $output, $err_code);
    if ($err_code) {
        $mysql_dump = new MYSQL_DUMP($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_PORT, $MYSQL_SOCKET, $MYSQL_CHARSET);

        if ($mysql_dump->restoreDB($path . 'netcat.sql', $MYSQL_DB_NAME) == false) {
            echo 'Failed to import MySQL dump:' . "<br />\n";
            echo $mysql_dump->error();
            echo 'Cmdline output: ' . "\n<br />\n";
            foreach ($output as $l) {
                echo $l . "\n<br />";
            }
            echo "\n<br />\n";
            return false;
        }
    }
    return true;
}

function update_varsinc($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME, $MYSQL_PORT, $MYSQL_SOCKET, $MYSQL_CHARSET) {
    $code = file_get_contents('vars.inc.php');
    $c = array('MYSQL_HOST' => $MYSQL_HOST, 'MYSQL_USER' => $MYSQL_USER, 'MYSQL_PASSWORD' => $MYSQL_PASSWORD, 'MYSQL_DB_NAME' => $MYSQL_DB_NAME, 'MYSQL_PORT' => $MYSQL_PORT, 'MYSQL_SOCKET' => $MYSQL_SOCKET, 'MYSQL_CHARSET' => $MYSQL_CHARSET);
    foreach ($c as $key => $value) {
        $code = preg_replace('/\$' . $key . '\s*=.*/', '$' . $key . ' = "' . $value . '";', $code);
    }
    file_put_contents('vars.inc.php', $code);
}

function show_form($dump_options) {
    global $known_tgz_archive_names_in_the_dump; // Array of archive names that may present in backup.
    $optional_tgz_archive_names_in_the_dump = array('images');
    list($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME, $MYSQL_PORT, $MYSQL_SOCKET, $MYSQL_CHARSET) = guess_sql_settings();

    $str = get_tr();
    $json = trim($_REQUEST['format'] === 'json');

    if (!$json) {
        echo '<h1>' . $str['header'] . '</h1>';
    }

    // Check if files exists
    $check = true;
    if ($dump_options['mode'] !== 'sql') {
        $check = check(array_diff($known_tgz_archive_names_in_the_dump, $optional_tgz_archive_names_in_the_dump));
    }

    if ($check !== true) {
        if (!$json) {
            die($check);
        } else {
            show_json("error", $check);
            exit();
        }
    }

    echo '<p>' . $str['mysqlsettings'] . '</p>';
    echo '<form method="post"><table>';
    $opts = array(
      array($str['hostname'], 'mysql_host', $MYSQL_HOST),
      array($str['username'], 'mysql_user', $MYSQL_USER),
      array($str['password'], 'mysql_password', $MYSQL_PASSWORD),
      array($str['dbname'], 'mysql_db_name', $MYSQL_DB_NAME),
      array($str['port'], 'mysql_port', $MYSQL_PORT),
      array($str['socket'], 'mysql_socket', $MYSQL_SOCKET),
      array($str['charset'], 'mysql_charset', $MYSQL_CHARSET)
    );
    foreach ($opts as $a) {
        echo '<tr><td>' . $a[0] . ':</td><td><input type="text" name="' . $a[1] . '" value="' . htmlspecialchars($a[2]) . '" /></td></tr>';
    }
    echo '</table>';
    echo '<input type="hidden" name="format" value="'.htmlspecialchars($_REQUEST['format']).'">';
    echo '<input type="hidden" name="execute_backup_restore" value="1">';
    echo "<input type='submit' value='{$str['restore_button']}'>";
    echo '</form>';
}

function add_tmp_dirs() {
    if (!file_exists('netcat_dump')) {
        mkdir('netcat_dump', 0777, true);
        $fp = fopen('netcat_dump/index.html', 'w');
        fwrite($fp, ' ');
        fclose($fp);
    }
    if (!file_exists('netcat/tmp')) {
        mkdir('netcat/tmp', 0777, true);
        $fp = fopen('netcat/tmp/index.html', 'w');
        fwrite($fp, ' ');
        fclose($fp);
    }
}

function execute_backup_restore($dump_options) {
    global $ADMIN_PATH;

    // Disable time limit
    @set_time_limit(0);
    $str = get_tr();
    $json = trim($_POST['format'] === 'json');

    if (!$json) {
        echo '<h1>' . $str['restore_init'] . "</h1>\n";
    }
    flush();

    global $known_tgz_archive_names_in_the_dump; // Array of archive names that may present in backup.
    $optional_tgz_archive_names_in_the_dump = array('images');
    $tgz_archive_names_to_extract = array();

    foreach ($known_tgz_archive_names_in_the_dump as $archive_name) {
        $checking_results = check(array($archive_name));
        $is_optional_archive = in_array($archive_name, $optional_tgz_archive_names_in_the_dump, true);
        $is_archive_missing = $checking_results !== true && strpos($checking_results, $str['nosuchfile']) !== false;
        if ($is_optional_archive && $is_archive_missing) {
            continue;
        }
        $tgz_archive_names_to_extract[] = $archive_name;
    }

    if (!isset($_POST['mysql_host'], $_POST['mysql_user'], $_POST['mysql_password'], $_POST['mysql_db_name'], $_POST['mysql_charset'], $_POST['mysql_port'], $_POST['mysql_socket']) || !$_POST['mysql_db_name']) {
        if (!$json) {
            echo '<p class="error">' . $str['err_nosettings'] . '</p>';
        } else {
            show_json("error", $str['err_nosettings']);
        }
        return show_form($dump_options);
    }

    // Get MySQL settings
    $MYSQL_HOST = trim($_POST['mysql_host']);
    if ($MYSQL_HOST === '') {
        $MYSQL_HOST = 'localhost';
    }
    $MYSQL_USER = trim($_POST['mysql_user']);
    $MYSQL_PASSWORD = $_POST['mysql_password'];
    $MYSQL_DB_NAME = trim($_POST['mysql_db_name']);
    $MYSQL_CHARSET = trim($_POST['mysql_charset']);
    $MYSQL_PORT = trim($_POST['mysql_port']);
    $MYSQL_SOCKET = trim($_POST['mysql_socket']);
    if ($MYSQL_CHARSET === '') {
        $MYSQL_CHARSET = 'utf8';
    }

    // Restore database dump
    if (!$json) {
        echo $str['dbrestore'] . "\n";
    }
    flush();
    $dump_result = restore_dump($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME, $MYSQL_PORT, $MYSQL_SOCKET, $MYSQL_CHARSET);
    if ($dump_result !== true) {
        if (!$json) {
            echo '<p class="error">' . $str['err_restore'] . ': ' . $dump_result . '</p>';
        } else {
            show_json("error", $str['err_restore'] . ': ' . $dump_result);
        }
        return show_form($dump_options);
    }
    if (!$json) {
        echo $str['done'] . '<br />';
    }
    flush();

    if ($dump_options['mode'] !== 'sql') {
        // Extract files
        extract_files($tgz_archive_names_to_extract);

        // Update vars.inc.php
        update_varsinc($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME, $MYSQL_PORT, $MYSQL_SOCKET, $MYSQL_CHARSET);
        add_tmp_dirs();
    }
    if (!$json) {
        echo '<p>' . $str['finish_text'] . ' <a href="' . (!empty($ADMIN_PATH) ? $ADMIN_PATH : "/netcat/admin/") . '">' . $str['finish_link_title'] . '</a>.</p>';
    } else {
        show_json("success", $str['finish_text_json']);
    }

    if ($json) {
        del_tmp_files();
    }
}

//
function show_json($status, $message) {
    $result = array("result" => $status, "message" => $message);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}

// удалить временные файлы
function del_tmp_files() {
    $del_files = array(
        'everything.tgz',
        'modules.tgz',
        'netcat.sql',
        'netcat_files.tgz',
        'netcat_template.tgz',
        'restore.php',
    );
    foreach ($del_files as $file) {
        @unlink($file);
    }
}
?>