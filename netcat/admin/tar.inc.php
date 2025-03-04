<?php

nc_tgz_check_exec();

// проверить, есть ли внешний tar и возможность его запустить
function nc_tgz_check_exec() {
    // Global setting: DISABLE_TGZ_EXEC -- установить в true если не работает system("tar")
    // check whether to use system() call to tar [faster]
    if (empty($GLOBALS["DISABLE_TGZ_EXEC"]) && !preg_match("/Windows/i", php_uname())) {  // it's not Windows
        $err_code = 127;
        $tgz_version = @exec("tar --version", $output, $err_code);
        define("SYSTEM_TAR", !$err_code);
    } else {
        define("SYSTEM_TAR", false);
    }
}

/**
 * Возвращает путь к архиву с расширением .tar
 * @internal
 * @param $dir
 * @return string
 */
function nc_tgz_get_temporary_tar_file_name($dir) {
    do {
        $tar_file_name = $dir . '/' . uniqid() . '.tar';
    } while (file_exists($tar_file_name) || file_exists("$tar_file_name.gz"));
    return $tar_file_name;
}

/**
 * Распаковывает tgz-архив
 * @param string $archive_name
 * @param string $dst_path
 * @return bool
 */
function nc_tgz_extract($archive_name, $dst_path) {
    @set_time_limit(0);
    if (SYSTEM_TAR) {
        return nc_tgz_extract_with_tar($archive_name, $dst_path);
    }

    try {
        return nc_tgz_extract_with_phar($archive_name, $dst_path);
    } catch (Error $e) { // PHP 7 errors
        return nc_tgz_extract_with_pear($archive_name, $dst_path);
    } catch (Exception $e) { // PHP 5 & 7 exceptions
        return nc_tgz_extract_with_pear($archive_name, $dst_path);
    }
}

/**
 * Распаковывает tgz-архив системным tar
 * @internal
 * @param $archive_name
 * @param $dst_path
 * @return bool
 */
function nc_tgz_extract_with_tar($archive_name, $dst_path) {
    $DOCUMENT_ROOT = nc_core::get_object()->DOCUMENT_ROOT;
    exec("cd $DOCUMENT_ROOT; tar -zxf $archive_name -C $dst_path 2>&1", $output, $err_code);
    if ($err_code && !strpos($output[0], "time")) { // ignore "can't utime, permission denied"
        trigger_error("$output[0]", E_USER_WARNING);
        return false;
    }
    return true;
}

/**
 * Распаковывает tgz-архив с использованием PharData
 * @internal
 * @param $archive_name
 * @param $dst_path
 * @return bool
 * @throws Exception
 */
function nc_tgz_extract_with_phar($archive_name, $dst_path) {
    if (!class_exists('PharData')) {
        throw new Exception('Phar library is not installed');
    }

    $nc_core = nc_core::get_object();

    $current_dir = realpath('.');
    chdir($nc_core->DOCUMENT_ROOT);

    // Phar загружает в память .tar целиком :(
    $memory_limit = $nc_core->get_memory_limit();
    if ($memory_limit && filesize($archive_name) > $memory_limit / 3) {
        throw new Exception("Archive is too large");
    }

    try {
        // так будет называться .tar (Phar берёт в качестве основы имя файла до всех расширений):
        $tar_file = dirname($archive_name) . '/' . strstr(basename($archive_name), '.', true) . '.tar';
        if (file_exists($tar_file)) {
            unlink($tar_file);
        }

        // распаковываем .gz
        $phar = new PharData($archive_name, null, null, Phar::GZ);
        $phar->decompress(); // создаёт .tar

        // извлекаем файлы из .tar
        $phar = new PharData($tar_file, null, null, Phar::TAR);
        $result = $phar->extractTo($dst_path, null, true);

        // удаляем .tar
        unset($phar);
        PharData::unlinkArchive($tar_file);
    }
    catch (Exception $e) {
        chdir($current_dir);
        throw $e;
    }

    chdir($current_dir);
    return $result;
}


/**
 * Распаковывает tgz-архив с использованием Archive_Tar
 * @internal
 * @param $archive_name
 * @param $dst_path
 * @return bool
 */
function nc_tgz_extract_with_pear($archive_name, $dst_path) {
    $current_dir = realpath('.');
    chdir(nc_core::get_object()->DOCUMENT_ROOT);

    require_once "Tar.php";  // /netcat/require/lib/
    $tar_object = new Archive_Tar($archive_name, "gz");
    $tar_object->setErrorHandling(PEAR_ERROR_PRINT);
    $result = $tar_object->extract($dst_path);

    chdir($current_dir);

    return $result;
}

/**
 * Создание архива формата .tgz
 *
 * @param $archive_name: имя создаваемого архива
 * @param $file_name: имена файлов и/или директорий, добавляемых в архив
 * @param $additional_path: имя начальной директории при создании архива, задается относительно корня системы ($DOCUMENT_ROOT.$SUB_FOLDER). Значение по умолчанию: пустая строка
 * @param array $exclude: пути к директориям, которые не будут добавлены в архив, относительно папки с netcat. Значение по умолчанию: NULL
 *  В случае использования системного tar создается список аргументов --exclude, содержащих список исключаемых директорий.
 *  В случае использования класса Archive_Tar список игнорируемых директорий устанавливается методом setIgnoreRegexp().
 *  В случае использования Phar используется фильтрующий итератор.
 * @param array $replace файлы для замены в архиве (путь от корня сайта => путь к файлу от корня файловой системы)
 *
 * @return bool:
 *  true в случае удачного создания архива,
 *  false в случае ошибки
 */
function nc_tgz_create($archive_name, $file_name, $additional_path = '', array $exclude = null, array $replace = null) {
    global $DOCUMENT_ROOT, $SUB_FOLDER;

    @set_time_limit(0);

    $path = $DOCUMENT_ROOT . $SUB_FOLDER . $additional_path;

    $additional_folder = null;
    if ($replace) {
        $nc_core = nc_core::get_object();
        $exclude = array_merge($exclude ?: array(), array_keys($replace));

        $additional_folder = sys_get_temp_dir() . '/tar' . mt_rand(0, PHP_INT_MAX);
        foreach ($replace as $destination => $source) {
            $target = "$additional_folder/$destination";
            @mkdir(dirname($target), $nc_core->DIRCHMOD, true);
            // не copy(), чтобы файлы принадлежали текущему пользователю:
            file_put_contents($target, file_get_contents($source));
            chmod($target, $nc_core->FILECHMOD);
        }
    }

    if (SYSTEM_TAR) {
        $result = nc_tgz_create_with_tar($archive_name, $file_name, $path, $exclude, $additional_folder);
    } else {
        // PHAR не подходит для больших дампов, т. к. архив формируется полностью в памяти
        // $result = nc_tgz_create_with_phar($archive_name, $file_name, $path, $exclude, $additional_folder);
        $result = nc_tgz_create_with_pear($archive_name, $file_name, $path, $exclude, $additional_folder);
    }

    if ($additional_folder) {
        nc_delete_dir($additional_folder);
    }

    return $result;
}

/**
 * Создание .tgz системным tar
 * @internal
 * @param $archive_name
 * @param $file_name
 * @param $path
 * @param array|null $exclude
 * @param string|null $additional_folder
 * @return bool
 */
function nc_tgz_create_with_tar($archive_name, $file_name, $path, array $exclude = null, $additional_folder = null) {
    $exclude_cmd = '';
    if ($exclude) {
        $exclude_array = array();
        foreach($exclude as $item) {
            $exclude_array[] = "--exclude='./$item'";
        }
        $exclude_cmd = implode(' ', $exclude_array);
    }

    $archive_name_parts = pathinfo($archive_name);

    $original_dir = getcwd();
    chdir($path);

    $tar = ($archive_name_parts['dirname'] ? "$archive_name_parts[dirname]/" : '') . "$archive_name_parts[filename].tar";

    exec("tar -cf '$tar' $exclude_cmd $file_name 2>&1", $output, $err_code);
    if ($err_code) {
        trigger_error($output[0], E_USER_WARNING);
        return false;
    }

    if ($additional_folder) {
        chdir($additional_folder);
        exec("tar -rf '$tar' * 2>&1", $output, $err_code);
        chdir($path);
        if ($err_code) {
            trigger_error($output[0], E_USER_WARNING);
            return false;
        }
    }

    exec("gzip -9 '$tar' 2>&1", $output, $err_code);
    if ($err_code) {
        trigger_error($output[0], E_USER_WARNING);
        return false;
    }

    rename("$tar.gz", $archive_name);

    chdir($original_dir);
    return true;
}

/**
 * Создание .tgz с использованием PharData
 * @internal
 * @param $archive_name
 * @param $file_name
 * @param $path
 * @param array|null $exclude
 * @param string|null $additional_folder
 * @return bool
 * @throws PharException
 */
function nc_tgz_create_with_phar($archive_name, $file_name, $path, array $exclude = null, $additional_folder = null) {
    $file_name = $path . '/' . $file_name;
    $tar_file = nc_tgz_get_temporary_tar_file_name(dirname($archive_name));

    try {
        $phar = new PharData($tar_file);

        // Добавление файлов
        if (is_dir($file_name)) {
            $file_name = realpath($file_name);
            $directory_iterator = new RecursiveDirectoryIterator($file_name, FilesystemIterator::SKIP_DOTS);

            if ($exclude) {
                array_walk($exclude, function ($e) {
                    return realpath($e);
                });
                $filter_iterator = new RecursiveCallbackFilterIterator($directory_iterator, function ($current, $key, $iterator) use ($exclude) {
                    /** @var SplFileInfo $current */
                    return (!in_array($current->getRealPath(), $exclude));
                });

                $recursive_iterator = new RecursiveIteratorIterator($filter_iterator);
            }
            else {
                $recursive_iterator = new RecursiveIteratorIterator($directory_iterator);
            }

            $phar->buildFromIterator($recursive_iterator, dirname($file_name));
        }
        else {
            $phar->addFile($file_name);
        }

        if ($additional_folder) {
            $phar->buildFromDirectory($additional_folder);
        }

        // Создание .gz
        $phar->compress(Phar::GZ);

        // Чистка: удаление .tar
        unset($phar);
        unlink($tar_file);

        // Переименование полученного файла
        rename("$tar_file.gz", $archive_name);

        return true;
    }
    catch (PharException $e) {
        if (file_exists($tar_file)) {
            unlink($tar_file);
        }

        if (file_exists("$tar_file.gz")) {
            unlink("$tar_file.gz");
        }

        throw $e;
    }
}

/**
 * Создание .tgz с использованием Archive_Tar
 * @internal
 * @param $archive_name
 * @param $file_name
 * @param $path
 * @param array|null $exclude
 * @param string|null $additional_folder
 * @return true
 */
function nc_tgz_create_with_pear($archive_name, $file_name, $path, array $exclude = null, $additional_folder = null) {
    require_once "Tar.php";  // /netcat/require/lib/

    $tar_object = new Archive_Tar($archive_name, "gz");
    $tar_object->setErrorHandling(PEAR_ERROR_PRINT);

    if ($exclude) {
        $exclude_regexp_parts = array();
        foreach($exclude as $item) {
            $exclude_regexp_parts[] = preg_quote($item, '#');
        }
        $exclude_regexp = '#^/(?:' . join('|', $exclude_regexp_parts) . ')$#';
        $tar_object->setIgnoreRegexp($exclude_regexp);
    }

    chdir($path);

    if ($file_name === '.') {
        $file_name_array = array_diff(glob('{.,}*', GLOB_BRACE), array('.', '..'));
    } else {
        $file_name_array = explode(' ', $file_name);
    }

    ob_start();
    $res = $tar_object->create($file_name_array);
    if ($additional_folder) {
        $tar_object->addModify(array($additional_folder), '', $additional_folder);
    }
    if (!$res) {
        ob_end_flush();
    } else {
        ob_end_clean();
    }

    return $res;
}
