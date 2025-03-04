<?php

const DIRECTOR = 7;                      // директор
const SUPERVISOR = 6;                    // супервизор
const GUEST = 8;                         // гость

const EDITOR = 5;                        // редактор
const SUBDIVISION_ADMIN = 3;             // редактор раздела
const CATALOGUE_ADMIN = 4;               // редактор сайта
const SUB_CLASS_ADMIN = 9;               // редактор инфоблока
const CATALOGUE_CONTENT_ADMIN = 16;      // редактор контента сайта

const MODERATOR = 12;                    // управляет пользователями
const GROUP_MODERATOR = 13;              // управляет группами пользователей

const CLASSIFICATOR_ADMIN = 14;          // админ списка
const DEVELOPER = 15;                    // разработчик

const BAN = 20;                          // ограничение
const BAN_SITE = 21;                     // ограничение на сайте
const BAN_SITE_CONTENT = 24;             // ограничение на сайте для редактора контента
const BAN_SUB = 22;                      // ограничение в разделе
const BAN_CC = 23;                       // ограничение в сс

const SUBSCRIBER = 30;                   // подписчик

const MASK_DELETE = 256;
const MASK_CHECKED = 128;
const MASK_COMMENT = 64;
const MASK_ADMIN = 32;
const MASK_MODERATE = 16;
const MASK_SUBSCRIBE = 8;
const MASK_EDIT = 4;
const MASK_ADD = 2;
const MASK_READ = 1;

// возможности по порядку
// в таком порядке будут отображаться при просмотре прав
const NC_PERM_READ_ID = 0;
const NC_PERM_COMMENT_ID = 1;
const NC_PERM_ADD_ID = 2;
const NC_PERM_EDIT_ID = 3;
const NC_PERM_CHECKED_ID = 4;
const NC_PERM_DELETE_ID = 5;
const NC_PERM_MODERATE_ID = 6;
const NC_PERM_ADMIN_ID = 7;
const NC_PERM_SUBSCRIBE_ID = 8;
const NC_PERM_COUNT_PERM = 9;


# Сущности
const NC_PERM_ITEM_SITE = 0;
const NC_PERM_ITEM_SUB = 1;
const NC_PERM_ITEM_CC = 2;
const NC_PERM_ITEM_USER = 3;
const NC_PERM_ITEM_GROUP = 4;
const NC_PERM_CLASSIFICATOR = 5;
const NC_PERM_FAVORITE = 6;
const NC_PERM_SQL = 7;
const NC_PERM_CLASS = 8;
const NC_PERM_FIELD = 9;
const NC_PERM_SYSTABLE = 10;
const NC_PERM_MODULE = 11;
const NC_PERM_PATCH = 12;
const NC_PERM_REPORT = 13;
const NC_PERM_TEMPLATE = 14;
const NC_PERM_CRON = 15;
const NC_PERM_TOOLSHTML = 16;
const NC_PERM_SEO = 17;
const NC_PERM_REDIRECT = 18;
const NC_PERM_WIDGETCLASS = 19;


# Действия
const NC_PERM_ACTION_ADD = 1;
const NC_PERM_ACTION_DEL = 2;
const NC_PERM_ACTION_EDIT = 3;
const NC_PERM_ACTION_ADMIN = 4;
const NC_PERM_ACTION_LIST = 5;
const NC_PERM_ACTION_VIEW = 6;
const NC_PERM_ACTION_INFO = 7;
const NC_PERM_ACTION_MAIL = 8;
const NC_PERM_ACTION_RIGHT = 9;
const NC_PERM_ACTION_ADDSUB = 10;
const NC_PERM_ACTION_DELSUB = 11;
const NC_PERM_ACTION_SUBCLASSLIST = 12;
const NC_PERM_ACTION_SUBCLASSDEL = 13;
const NC_PERM_ACTION_SUBCLASSADD = 14;
const NC_PERM_ACTION_ADDELEMENT = 15;
const NC_PERM_ACTION_WIZARDCLASS = 16;

const NC_PERM_MODULE_ALL = '*'; // доступ к модулю — все действия

# Типы файловой системы
const NC_FS_SIMPLE = 1;
const NC_FS_ORIGINAL = 2;
const NC_FS_PROTECTED = 3;

# Типы полей компонента
const NC_FIELDTYPE_STRING = 1;
const NC_FIELDTYPE_INT = 2;
const NC_FIELDTYPE_TEXT = 3;
const NC_FIELDTYPE_SELECT = 4;
const NC_FIELDTYPE_BOOLEAN = 5;
const NC_FIELDTYPE_FILE = 6;
const NC_FIELDTYPE_FLOAT = 7;
const NC_FIELDTYPE_DATETIME = 8;
const NC_FIELDTYPE_RELATION = 9;
const NC_FIELDTYPE_MULTISELECT = 10;
const NC_FIELDTYPE_MULTIFILE = 11;

//
const NC_FIELD_PERMISSION_EVERYONE = 1;
const NC_FIELD_PERMISSION_ADMIN = 2;
const NC_FIELD_PERMISSION_NOONE = 3;


const NC_LANG_ACRONYM = 1;

const NC_CLASS_TYPE_RSS = 'rss';

const NC_TOKEN_ADD = 1;
const NC_TOKEN_EDIT = 2;
const NC_TOKEN_DROP = 4;

const NC_FCKEDITOR = 2;
const NC_CKEDITOR = 3;
const NC_TINYMCE = 4;


const NC_AUTH_LOGIN_INCORRECT = 1;
const NC_AUTH_LOGIN_EXISTS = 2;
const NC_AUTH_LOGIN_OK = 0;

// типы авторизации
const NC_AUTHTYPE_LOGIN = 1;
const NC_AUTHTYPE_HASH = 2;
const NC_AUTHTYPE_EX = 4;
const NC_AUTHTYPE_TOKEN = 8;
const NC_AUTHTYPE_AD = 16;

// Last-Modified
const NC_LASTMODIFIED_NONE = 1;
const NC_LASTMODIFIED_YESTERDAY = 2;
const NC_LASTMODIFIED_HOUR = 3;
const NC_LASTMODIFIED_CURRENT = 4;
const NC_LASTMODIFIED_ACTUAL = 5;

// Символы (для регулярного выражения), которые можно использовать в ключевых словах разделов, инфоблоков и объектов
define('NETCAT_RUALPHABET', !empty($GLOBALS['NC_UNICODE']) ? 'А-Яа-яЁё' : '\xC0-\xFF\xA8\xB8');
