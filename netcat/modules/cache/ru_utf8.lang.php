<?php

// main
const NETCAT_MODULE_CACHE_DESCRIPTION = 'Модуль предназначен для кэширования результатов отображения данных на сайте.';
// catalogue form
const CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE = 'Кэширование';
const CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_ALLOW = 'Разрешить';
const CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_DENY = 'Запретить';
const CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_LIFETIME = 'Актуальность (минуты)';
const CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_STATUS = 'Состояние кэша';
const CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE_CLEAR = 'Очистить кэш';
// subdivision form
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE = 'Кэширование';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_ALLOW = 'Разрешить';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_DENY = 'Запретить';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_LIFETIME = 'Актуальность (минуты)';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_STATUS = 'Состояние кэша';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_CLEAR = 'Очистить кэш';
// subclass form
const CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE = 'Кэширование';
const CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_ALLOW = 'Разрешить';
const CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_DENY = 'Запретить';
const CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_LIFETIME = 'Актуальность (минуты)';
const CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_STATUS = 'Состояние кэша';
const CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE_CLEAR = 'Очистить кэш';
// admin interface
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS = 'Настройки кэша';
const NETCAT_MODULE_CACHE_ADMIN_CACHE = 'Кэш';
const NETCAT_MODULE_CACHE_ADMIN_INFO = 'Информация';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT = 'Данные аудита';
const NETCAT_MODULE_CACHE_ADMIN_MAINSETTINGS_TITLE = 'Основные настройки';
const NETCAT_MODULE_CACHE_ADMIN_MAINSETTINGS_SAVE_BUTTON = 'Сохранить';
const NETCAT_MODULE_CACHE_ADMIN_SAVE_OK = 'Настройки кэша сохранены';
const NETCAT_MODULE_CACHE_ADMIN_TYPE_LIST = 'список объектов';
const NETCAT_MODULE_CACHE_ADMIN_TYPE_FULL = 'подробное отображение';
const NETCAT_MODULE_CACHE_ADMIN_TYPE_BROWSE = 'функции навигации';
const NETCAT_MODULE_CACHE_ADMIN_TYPE_FUNCTION = 'результаты выполнения функций';
// modules type
const NETCAT_MODULE_CACHE_ADMIN_TYPE_CALENDAR = 'отображение календаря';
// admin interface / cache settings
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CATALOGUE = 'Сайт для настройки';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CACHE_TYPE = 'Тип кэша';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CACHE_ON = 'Включен';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CACHE_OFF = 'Выключен';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT = 'Настройки аудита';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_ON = 'включить режим аудита';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_BEGIN = 'начало аудита';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_END = 'окончание аудита';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_TIME = 'длительность аудита (часы)';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_SAVE_TIME = 'время с момента сохранения';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_TITLE = 'Настройки квот';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_OVERDRAFT = 'Превышение квоты';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_OVERDRAFT_NOCACHE = 'Не кэшировать';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_OVERDRAFT_DROP = 'Удалять неэффективный кэш';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_MAXSIZE_HEADER_CACHE = 'Кэш';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_MAXSIZE_HEADER_SIZE = 'Максимальный размер кэша (MB)';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_MAXSIZE_HEADER_CLEAR = 'Очистить';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_INFO_DELETED = '%SIZE кэша «%TYPE» удалено';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED = 'Memcached';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_ON = 'Использовать memcached';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_HOST = 'адрес сервера';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_PORT = 'порт';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_ERROR = 'Не удалось подключиться к серверу memcached';
const NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_DOESNT_EXIST = 'Невозможно использовать memcached, так как не установлено расширение memcache.';
// admin interface / information
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_TITLE = 'Общая информация (только для файлового кэша)';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_CACHE = 'Кэш';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_FILES = 'Файлы';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_DIRS = 'Директории';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_SIZE = 'Размер';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_TOTAL = 'итоговый';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_CLEAR_TABLE = 'Таблица очистки';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_CACHE_COUNT = 'Количество записей';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_CACHE_AVERAGE_EFFICIENCY = 'Средняя эффективность';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_UPDATE_BUTTON = 'Обновить информацию';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_DROP_CLEAR_BUTTON = 'Удалить данные очистки';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_TYPE = 'Тип кэша';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_COUNT = 'Записей в базе';
const NETCAT_MODULE_CACHE_ADMIN_MAININFO_DROP_CLEAR_OK = 'Данные таблицы очистки удалены';
// admin interface / audit data
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_DATA = 'Данные аудита';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_COUNT = 'Подсчитано значений';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_CATALOGUE = 'Сайт';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_SUBDIVISION = 'Раздел';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_SUBCLASS = 'Компонент в разделе';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_EFFICIENCY = 'Эффективность';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_NODATA = 'нет данных';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_SAVE_CLEAR_BUTTON = 'Сохранить в таблице очистки';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_SAVE_CLEAR_OK = 'Данные аудита сохранены в таблице очистки';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_DROP_BUTTON = 'Удалить данные аудита';
const NETCAT_MODULE_CACHE_ADMIN_AUDIT_DROP_OK = 'Данные аудита удалены';
// classes constants
const NETCAT_MODULE_CACHE_CLASS_UNRECOGNIZED_OBJECT_CALLING = 'Неподдерживаемый вызов объекта кэша';
const NETCAT_MODULE_CACHE_CLASS_UNCORRECT_DATA_FORMAT = 'Неверный формат данных';
const NETCAT_MODULE_CACHE_CLASS_CANNOT_CREATE_FILE = 'Не получается создать или записать файл %FILE';
?>