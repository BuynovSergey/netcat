<?php

$DOCUMENT_ROOT = rtrim(getenv("DOCUMENT_ROOT"), "/\\");
$HTTP_HOST = getenv("HTTP_HOST");

// Директория, в которой установлен Netcat
$SUB_FOLDER = "";

// Если Netcat установлен не в корневой директории, то раскомментируйте следующую строку
// $SUB_FOLDER = str_replace( str_replace("\\", "/", $DOCUMENT_ROOT), "", str_replace("\\", "/", dirname(__FILE__)) );

// Установка переменных окружения
error_reporting(E_ALL & ~(E_NOTICE | E_STRICT | E_DEPRECATED));
@ini_set("session.auto_start", "0");
@ini_set("session.use_trans_sid", "0");
@ini_set("session.use_cookies", "1");
@ini_set("session.use_only_cookies", "1");
@ini_set("url_rewriter.tags", "");               // to disable trans_sid on PHP < 5.0
@ini_set("session.cookie_domain", (strpos(str_replace("www.", "", $HTTP_HOST), ".") !== false) ? str_replace("www.", "", $HTTP_HOST) : "");
@ini_set("session.gc_probability", "1");
@ini_set("session.gc_maxlifetime", "1800");
@ini_set("session.hash_bits_per_character", "5"); // PHP < 7.1.0
@ini_set("session.sid_bits_per_character", "5");  // PHP ≥ 7.1.0
@ini_set("session.sid_length", "32");             // PHP ≥ 7.1.0; максимально поддерживаемая длина SID — 32
@ini_set("mbstring.internal_encoding", "UTF-8");
@ini_set("default_charset", "UTF-8");
@ini_set("session.name", ini_get("session.hash_bits_per_character") >= 5 ? "sid" : "ced");

// Параметры доступа к базе данных
$MYSQL_HOST = "localhost";
$MYSQL_USER = "u0053433_test-ne";
$MYSQL_PASSWORD = "test-netcat156";
$MYSQL_DB_NAME = "u0053433_test-netcat";
$MYSQL_PORT = "3306";
$MYSQL_SOCKET = "/var/run/mysqld/mysqld.sock";
$MYSQL_CHARSET = "utf8";
$MYSQL_ENCRYPT = "MD5";

// Кодировка
$NC_UNICODE = 1;
$NC_CHARSET = "utf-8";

// Настройки авторизации
$AUTHORIZE_BY = "Email";
$AUTHORIZATION_TYPE = "cookie"; // Принимаемые значения: http, session, cookie

// Разрешить вход только по https
$NC_ADMIN_HTTPS = 0;

// Серверные настройки
$PHP_TYPE = "module";           // Принимаемые значения: module, cgi
$REDIRECT_STATUS = "on";

// Настройки безопасности
$SECURITY_XSS_CLEAN = false;

// Инструмент "Переадресация" не доступен
$NC_REDIRECT_DISABLED = 0;

// Не загружать устаревшие файлы и функции
$NC_DEPRECATED_DISABLED = 1;

$ADMIN_LANGUAGE = "Russian";   // Язык административной части Netcat "по-умолчанию"
$FILECHMOD = 0644;             // Права на файл при добавлении через систему
$DIRCHMOD = 0755;              // Права на директории для закачки пользовательских файлов
$SHOW_MYSQL_ERRORS = "off";    // Показ ошибок MySQL на страницах сайта
$ADMIN_AUTHTIME = 2592000;     // Время жизни авторизации в секундах (при $AUTHORIZATION_TYPE = session, cookie)
$ADMIN_AUTHTYPE = "manual";    // Выбор типа авторизации: session, always, manual
$use_gzip_compression = false; // Для включения сжатия вывода установите true

// Настройки проекта
$DOMAIN_NAME = $HTTP_HOST;     // $HTTP_HOST - переменная окружения

// $DOCUMENT_ROOT = "/usr/local/etc/httpd/htdocs/www";

$HTTP_IMAGES_PATH = "/images/";
$HTTP_ROOT_PATH = "/netcat/";
$HTTP_TMP_PATH = $HTTP_ROOT_PATH . "tmp/";
$HTTP_FILES_PATH = "/netcat_files/";
$HTTP_DUMP_PATH = "/netcat_dump/";
$HTTP_CACHE_PATH = "/netcat_cache/";
$HTTP_TEMPLATE_PATH = "/netcat_template/";
$HTTP_TEMPLATE_CACHE_PATH = $HTTP_TEMPLATE_PATH;
$HTTP_TRASH_PATH = "/netcat_trash/";

// Относительный путь в админку сайта, для ссылок
$ADMIN_PATH = $SUB_FOLDER . $HTTP_ROOT_PATH . "admin/";

// Относительный путь к теме админки, для изображений и .css файлов
$ADMIN_TEMPLATE = $ADMIN_PATH . "skins/default/";

// Полный путь к теме админки
$ADMIN_TEMPLATE_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $ADMIN_TEMPLATE;

$SYSTEM_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_ROOT_PATH . "system/";
$ROOT_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_ROOT_PATH;
$FILES_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_FILES_PATH;
$DUMP_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_DUMP_PATH;
$CACHE_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_CACHE_PATH;
$TRASH_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_TRASH_PATH;
$INCLUDE_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_ROOT_PATH . "require/";
$TMP_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_TMP_PATH;
$MODULE_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/";
$ADMIN_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_ROOT_PATH . "admin/";
$EDIT_DOMAIN = $DOMAIN_NAME;
$DOC_DOMAIN = "netcat.ru/developers/docs";

$TEMPLATE_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_TEMPLATE_PATH . "template/";
$CLASS_TEMPLATE_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_TEMPLATE_PATH . "class/";
$WIDGET_TEMPLATE_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_TEMPLATE_PATH . "widget/";
$JQUERY_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_TEMPLATE_PATH . "jquery/";
$MODULE_TEMPLATE_FOLDER = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_TEMPLATE_PATH . "module/";

// set include_path: require/lib folder (PEAR and other 3rd-party libraries)
set_include_path("{$INCLUDE_FOLDER}lib/");

// Название разработчика, отображаемое на странице «О программе»
// $DEVELOPER_NAME = "ООО «НетКэт»";
// $DEVELOPER_URL = "http://www.netcat.ru/";
