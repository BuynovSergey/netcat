<?php

$nc_core = nc_core::get_object();

/**
 * Функция перевода русских букв в латиницу
 *
 * @param string $text строка;
 * @param bool $use_url_rules использовать фильтр для URL;
 * @return string строка;
 */
function nc_transliterate($text, $use_url_rules = false) {

    $tr = array("А" => "A", "а" => "a", "Б" => "B", "б" => "b",
            "В" => "V", "в" => "v", "Г" => "G", "г" => "g",
            "Д" => "D", "д" => "d", "Е" => "E", "е" => "e",
            "Ё" => "E", "ё" => "e", "Ж" => "Zh", "ж" => "zh",
            "З" => "Z", "з" => "z", "И" => "I", "и" => "i",
            "Й" => "Y", "й" => "y", "КС" => "X", "кс" => "x",
            "К" => "K", "к" => "k", "Л" => "L", "л" => "l",
            "М" => "M", "м" => "m", "Н" => "N", "н" => "n",
            "О" => "O", "о" => "o", "П" => "P", "п" => "p",
            "Р" => "R", "р" => "r", "С" => "S", "с" => "s",
            "Т" => "T", "т" => "t", "У" => "U", "у" => "u",
            "Ф" => "F", "ф" => "f", "Х" => "H", "х" => "h",
            "Ц" => "Ts", "ц" => "ts", "Ч" => "Ch", "ч" => "ch",
            "Ш" => "Sh", "ш" => "sh", "Щ" => "Sch", "щ" => "sch",
            "Ы" => "Y", "ы" => "y", "Ь" => "'", "ь" => "'",
            "Э" => "E", "э" => "e", "Ъ" => "'", "ъ" => "'",
            "Ю" => "Yu", "ю" => "yu", "Я" => "Ya", "я" => "ya");

    $tr_text = strtr($text, $tr);
    if ($use_url_rules) {
      $tr_text = strtolower(trim($tr_text));
      $tr_text = preg_replace('/[^\w\-\ ]/', '', $tr_text);
      $tr_text = str_replace(' ', '-', $tr_text);
      $tr_text = preg_replace('/\-{2,}/', '-', $tr_text);
    }

    return $tr_text;
}

// Include deprecated strings if $NC_DEPRECATED_DISABLED is set to 0
if (isset($NC_DEPRECATED_DISABLED) && $NC_DEPRECATED_DISABLED==0) {
	$deprecated_file = preg_replace('/\.php/', '_old.php', __FILE__);
	if (file_exists($deprecated_file)) {
		include_once $deprecated_file;
	}
}
# MAIN
$ADMIN_PATH = $nc_core->ADMIN_PATH;

const MAIN_DIR = 'ltr';
const MAIN_LANG = 'ru';
const MAIN_NAME = 'Russian';
define("MAIN_ENCODING", $nc_core->NC_CHARSET);

if (!defined("MAIN_EMAIL_ENCODING")) {
    define("MAIN_EMAIL_ENCODING", $nc_core->NC_CHARSET);
}

const CMS_SYSTEM_NAME = 'Netcat';
const CMS_SYSTEM_NAME_GLOBAL = 'Netcat';
const NETCAT_TREE_SITEMAP = 'Карта сайта';
const NETCAT_TREE_MODULES = 'Модули и виджеты';
const NETCAT_TREE_USERS = 'Пользователи';

const NETCAT_TREE_PLUS_TITLE = 'Раскрыть список';
const NETCAT_TREE_MINUS_TITLE = 'Свернуть список';

const NETCAT_TREE_QUICK_SEARCH = 'Быстрый поиск';

// Tabs
const NETCAT_TAB_REFRESH = 'Обновить';

const STRUCTURE_TAB_SUBCLASS_ADD = 'Добавить инфоблок';
const STRUCTURE_TAB_INFO = 'Информация';
const STRUCTURE_TAB_SETTINGS = 'Настройки';
const STRUCTURE_TAB_USED_SUBCLASSES = 'Инфоблоки';
const STRUCTURE_TAB_EDIT = 'Редактирование';
const STRUCTURE_TAB_PREVIEW = 'Просмотр &rarr;';
const STRUCTURE_TAB_PREVIEW_SITE = 'Перейти на сайт &rarr;';


const CLASS_TAB_INFO = 'Настройки';
const CLASS_TAB_EDIT = 'Редактирование компонента';
const CLASS_TAB_CUSTOM_ACTION = 'Шаблоны действий';
const CLASS_TAB_CUSTOM_ADD = 'Добавление';
const CLASS_TAB_CUSTOM_EDIT = 'Изменение';
const CLASS_TAB_CUSTOM_DELETE = 'Удаление';
const CLASS_TAB_CUSTOM_SEARCH = 'Поиск';

# BeginHtml
const BEGINHTML_TITLE = 'Администрирование';
const BEGINHTML_USER = 'Пользователь';
const BEGINHTML_VERSION = 'версия';
const BEGINHTML_PERM_GUEST = 'гостевой доступ';
const BEGINHTML_PERM_DIRECTOR = 'директор';
const BEGINHTML_PERM_SUPERVISOR = 'супервизор';
const BEGINHTML_PERM_CATALOGUEADMIN = 'администратор сайта';
const BEGINHTML_PERM_SUBDIVISIONADMIN = 'администратор раздела';
const BEGINHTML_PERM_SUBCLASSADMIN = 'администратор инфоблока';
const BEGINHTML_PERM_CLASSIFICATORADMIN = 'администратор списка';
const BEGINHTML_PERM_MODERATOR = 'модератор';

const BEGINHTML_LOGOUT = 'выход из системы';
const BEGINHTML_LOGOUT_OK = 'Сеанс завершен.';
const BEGINHTML_LOGOUT_RELOGIN = 'Войти под другим именем';
const BEGINHTML_LOGOUT_IE = 'Для завершения сеанса закройте все окна браузера.';


const BEGINHTML_ALARMON = 'Непрочитанные системные сообщения';
const BEGINHTML_ALARMOFF = 'Системные сообщения: непрочитанных нет';
const BEGINHTML_ALARMVIEW = 'Просмотр системного сообщения';
const BEGINHTML_HELPNOTE = 'подсказка';

# EndHTML
define("ENDHTML_NETCAT", CMS_SYSTEM_NAME === 'Netcat' ? "<div class='bottom_line'><div class='left'>&copy; 1999&#8212;" . date("Y") . " <a href='https://netcat.ru'>Netcat</a></div></div>" : '');

# Common
const NETCAT_ADMIN_DELETE_SELECTED = 'Удалить выбранное';
const NETCAT_SELECT_SUBCLASS_DESCRIPTION = 'В разделе «%s» имеется несколько компонентов типа «%s».<br />
  Выберите компонент раздела, в который нужно перенести объект, нажав на название компонента.';

# INDEX PAGE
const SECTION_INDEX_SITES_SETTINGS = 'Настройки сайтов';
const SECTION_INDEX_MODULES_MUSTHAVE = 'не установленные';
const SECTION_INDEX_MODULES_DESCRIPTION = 'описание';
const SECTION_INDEX_MODULES_TRANSITION = 'Переход на старшие редакции';
const DASHBOARD_WIDGET = 'Панель виджетов';
const DASHBOARD_ADD_WIDGET = 'Добавить виджет';
const DASHBOARD_DEFAULT_WIDGET = 'Виджеты по умолчанию';
const DASHBOARD_WIDGET_SYS_NETCAT = 'О системе';
const DASHBOARD_WIDGET_MOD_AUTH = 'Статистика ЛК';
const DASHBOARD_UPDATES_EXISTS = 'есть обновления';
const DASHBOARD_UPDATES_DONT_EXISTS = 'нет обновлений';
const DASHBOARD_DONT_ACTIVE = 'неактивированных';
const DASHBOARD_TODAY = 'сегодня';
const DASHBOARD_YESTERDAY = 'вчера';
const DASHBOARD_PER_WEEK = 'в неделю';
const DASHBOARD_WAITING = 'ждут';


# MODULES LIST
const NETCAT_MODULE_DEFAULT = 'Интерфейс разработчика';
const NETCAT_MODULE_AUTH = 'Личный кабинет';
const NETCAT_MODULE_SEARCH = 'Поиск по сайту';
const NETCAT_MODULE_SERCH = 'Поиск по сайту (старая версия)';
const NETCAT_MODULE_POLL = 'Голосование (опросник)';
const NETCAT_MODULE_ESHOP = 'Интернет-магазин (старый)';
const NETCAT_MODULE_STATS = 'Статистика посещений';
const NETCAT_MODULE_SUBSCRIBE = 'Подписка и рассылка';
const NETCAT_MODULE_BANNER = 'Управление рекламой';
const NETCAT_MODULE_FORUM = 'Форум';
const NETCAT_MODULE_FORUM2 = 'Форум v2';
const NETCAT_MODULE_NETSHOP = 'Интернет-магазин';
const NETCAT_MODULE_LINKS = 'Управление ссылками';
const NETCAT_MODULE_CAPTCHA = 'Защита форм картинкой';
const NETCAT_MODULE_TAGSCLOUD = 'Облако тегов';
const NETCAT_MODULE_BLOG = 'Блог и сообщество';
const NETCAT_MODULE_CALENDAR = 'Календарь';
const NETCAT_MODULE_COMMENTS = 'Комментарии';
const NETCAT_MODULE_LOGGING = 'Логирование';
const NETCAT_MODULE_FILEMANAGER = 'Файл-менеджер';
const NETCAT_MODULE_CACHE = 'Кэширование';
const NETCAT_MODULE_MINISHOP = 'Минимагазин';
const NETCAT_MODULE_ROUTING = 'Маршрутизация';
const NETCAT_MODULE_AIREE = 'Айри CDN';

const NETCAT_MODULE_NETSHOP_MODULEUNCHECKED = 'Модуль «Интернет-магазин» не установлен или выключен!';
const NETCAT_MODULE_MESSAGING = 'Отправка SMS-сообщений';
# /MODULES LIST

const SECTION_INDEX_USER_STRUCT_CLASSIFICATOR = 'Списки';

const SECTION_INDEX_USER_RIGHTS_TYPE = 'Тип прав';
const SECTION_INDEX_USER_RIGHTS_RIGHTS = 'Права';

const SECTION_INDEX_USER_USER_MAIL = 'Рассылка по базе';
const SECTION_INDEX_USER_SUBSCRIBERS = 'Подписки пользователя';

const SECTION_INDEX_DEV_CLASSES = 'Компоненты';
const SECTION_INDEX_DEV_CLASS_TEMPLATES = 'Шаблоны компонента';
const SECTION_INDEX_DEV_TEMPLATES = 'Макеты дизайна';


const SECTION_INDEX_ADMIN_PATCHES_INFO = 'Системная информация';
const SECTION_INDEX_ADMIN_PATCHES_INFO_VERSION = 'Версия системы';
const SECTION_INDEX_ADMIN_PATCHES_INFO_REDACTION = 'Редакция системы';
const SECTION_INDEX_ADMIN_PATCHES_INFO_LAST_PATCH = 'Последнее обновление';
const SECTION_INDEX_ADMIN_PATCHES_INFO_LAST_PATCH_DATE = 'Последняя проверка обновлений';
const SECTION_INDEX_ADMIN_PATCHES_INFO_CHECK_PATCH = 'Проверить наличие обновлений';

const SECTION_INDEX_REPORTS_STATS = 'Общая статистика проекта';
const SECTION_INDEX_REPORTS_SYSTEM = 'Системные сообщения';



# SECTION CONTROL
const SECTION_CONTROL_CONTENT_CATALOGUE = 'Сайты';
const SECTION_CONTROL_CONTENT_FAVORITES = 'Быстрое редактирование';
const SECTION_CONTROL_CONTENT_CLASSIFICATOR = 'Списки';

# SECTION USER
const SECTION_CONTROL_USER = 'Пользователи';
const SECTION_CONTROL_USER_LIST = 'Список пользователей';
const SECTION_CONTROL_USER_PERMISSIONS = 'Пользователи и права';
const SECTION_CONTROL_USER_GROUP = 'Группы пользователей';
const SECTION_CONTROL_USER_MAIL = 'Рассылка по базе';

# SECTION CLASS
const SECTION_CONTROL_CLASS = 'Компоненты';
const CONTROL_CLASS_USE_CAPTCHA = 'Защищать форму добавления картинкой';
const CONTROL_CLASS_CACHE_FOR_AUTH = 'Кэширование по авторизации';
const CONTROL_CLASS_CACHE_FOR_AUTH_NONE = 'Не использовать';
const CONTROL_CLASS_CACHE_FOR_AUTH_USER = 'Учитывать каждого пользователя';
const CONTROL_CLASS_CACHE_FOR_AUTH_GROUP = 'Учитывать основную группу пользователя';
const CONTROL_CLASS_CACHE_FOR_AUTH_DESCRIPTION = 'Если в компоненте нужно выводить данные уникальные для каждого пользователя, эта настройка позволит выбрать требуемые условия.';
const CONTROL_CLASS_ADMIN = 'Администрирование';
const CONTROL_CLASS_CONTROL = 'Управление';
const CONTROL_CLASS_FIELDSLIST = 'Список полей';
const CONTROL_CLASS_CLASS_GOTOFIELDS = 'Перейти к списку полей компонента';
const CONTROL_CLASS_CLASSFORM_ADDITIONAL_INFO = 'Дополнительная информация';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SORTNOTE = 'Название_поля_1[ DESC][, Название_поля_2[ DESC]][, ...]<br>DESC - сортировка по убыванию';
const CONTROL_CLASS_CLASS_SHOW_VAR_FUNC_LIST = 'Показать список переменных и функций';
const CONTROL_CLASS_CLASS_SHOW_VAR_LIST = 'Показать список переменных';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_AUTODEL = 'Удалять объекты через';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_AUTODELEND = 'дней после добавления';
const CONTROL_CLASS_CLASS_FORMS_YES = 'Да';
const CONTROL_CLASS_CLASS_FORMS_NO = 'Нет';
// NB: константы CONTROL_CLASS_CLASS_* также используются в nc_tpl_fields / nc_tpl_component_view, но через defined(),
// поэтому IDE не будет видеть их использование
const CONTROL_CLASS_CLASS_FORMS_ADDFORM = 'Альтернативная форма добавления объекта';
const CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN = 'сгенерировать код формы';
const CONTROL_CLASS_CLASS_FORMS_ADDRULES = 'Условия добавления объекта';
const CONTROL_CLASS_CLASS_FORMS_ADDCOND_GEN = 'сгенерировать код условия';
const CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION = 'Действие после добавления объекта';
const CONTROL_CLASS_CLASS_FORMS_ADDACTION_GEN = 'сгенерировать код действия';
const CONTROL_CLASS_CLASS_FORMS_EDITFORM = 'Альтернативная форма изменения объекта';
const CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN = 'сгенерировать код формы';
const CONTROL_CLASS_CLASS_FORMS_EDITRULES = 'Условия изменения объекта';
const CONTROL_CLASS_CLASS_FORMS_EDITCOND_GEN = 'сгенерировать код условия';
const CONTROL_CLASS_CLASS_FORMS_EDITLASTACTION = 'Действие после изменения объекта';
const CONTROL_CLASS_CLASS_FORMS_EDITACTION_GEN = 'сгенерировать код действия';
const CONTROL_CLASS_CLASS_FORMS_ONONACTION = 'Действие после включения и выключения объекта';
const CONTROL_CLASS_CLASS_FORMS_CHECKACTION_GEN = 'сгенерировать код действия';
const CONTROL_CLASS_CLASS_FORMS_DELETEFORM = 'Альтернативная форма удаления объекта';
const CONTROL_CLASS_CLASS_FORMS_DELETERULES = 'Условие удаления объекта';
const CONTROL_CLASS_CLASS_FORMS_ONDELACTION = 'Действие после удаления объекта';
const CONTROL_CLASS_CLASS_FORMS_DELETEACTION_GEN = 'сгенерировать код действия';
const CONTROL_CLASS_CLASS_FORMS_QSEARCH = 'Форма поиска перед списком объектов';
const CONTROL_CLASS_CLASS_FORMS_QSEARCH_GEN = 'сгенерировать код формы';
const CONTROL_CLASS_CLASS_FORMS_SEARCH = 'Форма расширенного поиска (на отдельной странице)';
const CONTROL_CLASS_CLASS_FORMS_SEARCH_GEN = 'сгенерировать код формы';
const CONTROL_CLASS_CLASS_FORMS_MAILRULES = 'Условия для подписки';
const CONTROL_CLASS_CLASS_FORMS_MAILTEXT = 'Шаблон письма для подписчиков';
define("CONTROL_CLASS_CLASS_FORMS_QSEARCH_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_QSEARCH."\\\" не пустое! Заменить текст в этом поле на новый?");
define("CONTROL_CLASS_CLASS_FORMS_SEARCH_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_SEARCH."\\\" не пустое! Заменить текст в этом поле на новый?");
define("CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_ADDFORM."\\\" не пустое! Заменить текст в этом поле на новый?");
define("CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_EDITFORM."\\\" не пустое! Заменить текст в этом поле на новый?");
define("CONTROL_CLASS_CLASS_FORMS_ADDCOND_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_ADDRULES."\\\" не пустое! Заменить текст в этом поле на новый?");
define("CONTROL_CLASS_CLASS_FORMS_EDITCOND_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_EDITRULES."\\\" не пустое! Заменить текст в этом поле на новый?");
define("CONTROL_CLASS_CLASS_FORMS_ADDACTION_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION."\\\" не пустое! Заменить текст в этом поле на новый?");
define("CONTROL_CLASS_CLASS_FORMS_EDITACTION_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_EDITLASTACTION."\\\" не пустое! Заменить текст в этом поле на новый?");
define("CONTROL_CLASS_CLASS_FORMS_CHECKACTION_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_ONONACTION."\\\" не пустое! Заменить текст в этом поле на новый?");
define("CONTROL_CLASS_CLASS_FORMS_DELETEACTION_GEN_WARN", "Поле \\\"".CONTROL_CLASS_CLASS_FORMS_ONDELACTION."\\\" не пустое! Заменить текст в этом поле на новый?");
const CONTROL_CLASS_CUSTOM_SETTINGS_ISNOTSET = 'Настройки отображения компонента раздела отсутствуют.';
const CONTROL_CLASS_CUSTOM_SETTINGS_INHERIT_FROM_PARENT = 'Настройки отображения шаблона компонента задаются в самом компоненте.';

# SECTION WIDGET
const WIDGETS = 'Виджеты';
const WIDGETS_LIST_IMPORT = 'Импорт';
const WIDGETS_LIST_ADD = 'Добавить';
const WIDGETS_PARAMS = 'Параметры';
const SECTION_INDEX_DEV_WIDGET = 'Виджет-компоненты';
const CONTROL_WIDGETCLASS_ADD = 'Добавить виджет';
const WIDGET_LIST_NAME = 'Название';
const WIDGET_LIST_CATEGORY = 'Категория';
const WIDGET_LIST_ALL = 'Все';
const WIDGET_LIST_GO = 'Перейти';
const WIDGET_LIST_FIELDS = 'Поля';
const WIDGET_LIST_DELETE = 'Удалить';
const WIDGET_LIST_DELETE_WIDGETCLASS = 'Виджет-компонент:';
const WIDGET_LIST_DELETE_WIDGET = 'Виджеты:';
const WIDGET_LIST_EDIT = 'Редактирование';
const WIDGET_LIST_AT = 'Шаблоны действия';
const WIDGET_LIST_ADDWIDGET = 'Добавить виджет-компонент';
const WIDGET_LIST_DELETE_SELECTED = 'Удалить выбранное';
const WIDGET_LIST_ERROR_DELETE = 'Сначала выберите виджет-компоненты для удаления';
const WIDGET_LIST_INSERT_CODE = 'код для вставки';
const WIDGET_LIST_INSERT_CODE_CLASS = 'Код для вставки в макет/компонент';
const WIDGET_LIST_INSERT_CODE_TEXT = 'Код для вставки в текст';
const WIDGET_LIST_LOAD = 'Загрузка...';
const WIDGET_LIST_PREVIEW = 'превью';
const WIDGET_LIST_EXPORT = 'Экспортировать виджет-компонент в файл';
const WIDGET_ADD_CREATENEW = 'Создать новый виджет-компонент &quot;с нуля&quot;';
const WIDGET_ADD_CONTINUE = 'Продолжить';
const WIDGET_ADD_CREATENEW_BASICOLD = 'Создать новый виджет-компонент на основе существующего';
const WIDGET_ADD_NAME = 'Название';
const WIDGET_ADD_KEYWORD = 'Ключевое слово';
const WIDGET_ADD_UPDATE = 'Обновлять виджеты каждые N минут (0 - не обновлять)';
const WIDGET_ADD_NEWGROUP = 'новая группа';
const WIDGET_ADD_DESCRIPTION = 'Описание виджет-компонента';
const WIDGET_ADD_OBJECTVIEW = 'Шаблон отображения';
const WIDGET_ADD_PAGEBODY = 'Отображение объекта';
const WIDGET_ADD_DOPL = 'Дополнительно';
const WIDGET_ADD_DEVELOP = 'В разработке';
const WIDGET_ADD_SYSTEM = 'Системные настройки';
const WIDGETCLASS_ADD_ADD = 'Добавить виджет-компонент';
const WIDGET_ADD_ADD = 'Добавить виджет';
const WIDGET_ADD_ERROR_NAME = 'Введите название виджет-компонента';
const WIDGET_ADD_ERROR_KEYWORD = 'Введите ключевое слово';
const WIDGET_ADD_ERROR_KEYWORD_EXIST = 'Ключевое слово должно быть уникальным';
const WIDGET_ADD_WK = 'Виджет-компонент';
const WIDGET_ADD_OK = 'Виджет успешно добавлен';
const WIDGET_ADD_DISALLOW = 'Запретить встраивание в объект';
const WIDGET_IS_STATIC = 'Статичный виджет';
const WIDGET_EDIT_SAVE = 'Сохранить изменения';
const WIDGET_EDIT_OK = 'Изменения сохранены';
const WIDGET_INFO_DESCRIPTION = 'Описание виджет-компонента';
const WIDGET_INFO_DESCRIPTION_NONE = 'Описание отсутствует';
const WIDGET_INFO_PREVIEW = 'Превью';
const WIDGET_INFO_INSERT = 'Код для вставки в макет/компонент';
const WIDGET_INFO_INSERT_TEXT = 'Код для вставки в текст';
const WIDGET_INFO_GENERATE = 'Пример синтаксиса для динамической вставки в макет/компонент';
const WIDGET_DELETE_WARNING = 'Внимание: виджет-компонент%s и все с ним%s связанное будет удалено.';
const WIDGET_DELETE_CONFIRMDELETE = 'Подтвердить удаление';
const WIDGET_DELETE = 'Внимание: Виджет будет удалён.';
const WIDGET_ACTION_ADDFORM = 'Альтернативная форма добавления объекта';
const WIDGET_ACTION_EDITFORM = 'Альтернативная форма изменения объекта';
const WIDGET_ACTION_BEFORE_SAVE = 'Действие перед сохранением объекта';
const WIDGET_ACTION_AFTER_SAVE = 'Действие после сохранения объекта';
const WIDGET_IMPORT = 'Импортировать';
const WIDGET_IMPORT_TAB = 'Импорт';
const WIDGET_IMPORT_CHOICE = 'Выберите файл';
const WIDGET_IMPORT_ERROR = 'Ошибка добавления файла';
const WIDGET_IMPORT_OK = 'Виджет-компонент успешно импортирован';

const SECTION_CONTROL_WIDGET = 'Виджеты';
const SECTION_CONTROL_WIDGETCLASS = 'Виджет-компоненты';
const SECTION_CONTROL_WIDGET_LIST = 'Список виджетов';
const CONTROL_WIDGET_ACTIONS_EDIT = 'Редактирование';
const CONTROL_WIDGET_NONE = 'В системе нет ни одного виджет-компонента';
const TOOLS_WIDGET = 'Виджеты';
const CONTROL_WIDGET_ADD_ACTION = 'Добавление виджета';
const CONTROL_WIDGETCLASS_ADD_ACTION = 'Добавление виджет-компонента';
const SECTION_INDEX_DEV_WIDGETS = 'Виджеты';
const CONTROL_WIDGETCLASS_IMPORT = 'Импорт виджета';
define("CONTROL_WIDGETCLASS_FILES_PATH", "Файлы виджет-компонента находятся в папке <a href='%s'>%s</a>");

const WIDGET_TAB_INFO = 'Информация';
const WIDGET_TAB_EDIT = 'Редактирование виджет-компонента';
const WIDGET_TAB_CUSTOM_ACTION = 'Шаблоны действий';
const NETCAT_REMIND_SAVE_TEXT = 'Выйти без сохранения?';
const NETCAT_REMIND_SAVE_SAVE = 'Сохранить';
const SECTION_SECTIONS_INSTRUMENTS_WIDGETS = 'Виджеты';

# SECTION TEMPLATE
const SECTION_CONTROL_TEMPLATE_SHOW = 'Макеты дизайна';

# SECTIONS OPTIONS
const SECTION_SECTIONS_OPTIONS = 'Настройки системы';
const SECTION_SECTIONS_OPTIONS_MODULE_LIST = 'Управление модулями';
const SECTION_SECTIONS_OPTIONS_WYSIWYG = 'Настройки WYSIWYG';
const SECTION_SECTIONS_OPTIONS_SYSTEM = 'Системные таблицы';
const SECTION_SECTIONS_OPTIONS_SECURITY = 'Безопасность';

# SECTIONS OPTIONS
const SECTION_SECTIONS_INSTRUMENTS_SQL = 'Командная строка SQL';
const SECTION_SECTIONS_INSTRUMENTS_TRASH = 'Корзина удаленных объектов';
const SECTION_SECTIONS_INSTRUMENTS_CRON = 'Управление задачами';
const SECTION_SECTIONS_INSTRUMENTS_HTML = 'HTML-редактор';

# SECTIONS MODDING
const SECTION_SECTIONS_MODDING_ARHIVES = 'Архивы проекта';

# REPORTS
const SECTION_REPORTS_TOTAL = 'Общая статистика проекта';
const SECTION_REPORTS_SYSMESSAGES = 'Системные сообщения';

# SUPPORT

# ABOUT
const SECTION_ABOUT_TITLE = 'О программе';
const SECTION_ABOUT_HEADER = 'О программе';
const SECTION_ABOUT_BODY = "Система управления сайтами Netcat <font color=%s>%s</font> версия %s. Все права защищены.<br><br>\nВеб-сайт системы Netcat: <a target=_blank href=https://netcat.ru>www.netcat.ru</a><br>\nEmail службы поддержки: <a href=mailto:support@netcat.ru>support@netcat.ru</a>\n<br><br>\nРазработчик: ООО «НетКэт»<br>\nEmail: <a href=mailto:info@netcat.ru>info@netcat.ru</a><br>\n+7 (495) 783-6021<br>\n<a target=_blank href=https://netcat.ru>www.netcat.ru</a><br>";
const SECTION_ABOUT_DEVELOPER = 'Разработчик проекта';

// ARRAY-2-FORMS


# INDEX
const CONTROL_CONTENT_CATALOUGE_SITE = 'Сайты';
const CONTROL_CONTENT_CATALOUGE_ONESITE = 'Сайт';
const CONTROL_CONTENT_CATALOUGE_ADD = 'добавление';
const CONTROL_CONTENT_CATALOUGE_SITEDELCONFIRM = 'Подтверждение удаления сайта';
const CONTROL_CONTENT_CATALOUGE_ADDSECTION = 'Добавление раздела';
const CONTROL_CONTENT_CATALOUGE_ADDSITE = 'Добавление сайта';
const CONTROL_CONTENT_CATALOUGE_SITEOPTIONS = 'Настройки сайта';

const CONTROL_CONTENT_CATALOUGE_ERROR_CASETREE_ONE = 'Название сайта не может быть пустым!';
const CONTROL_CONTENT_CATALOUGE_ERROR_DUPLICATE_DOMAIN = 'Сайт с таким доменным именем уже существует в системе. Укажите другое доменное имя.';
const CONTROL_CONTENT_CATALOUGE_ERROR_CASETREE_THREE = 'Доменное имя может содержать только латинские буквы, цифры, подчеркивание, дефис и точку! Цифры должны совмещаться с буквами. Возможно указание порта.';
const CONTROL_CONTENT_CATALOUGE_ERROR_DOMAIN_NOT_SET = 'Доменное имя не указано';
const CONTROL_CONTENT_CATALOUGE_ERROR_INCORRECT_DOMAIN = 'Проверьте домен';
define("CONTROL_CONTENT_CATALOUGE_ERROR_INCORRECT_DOMAIN_FULLTEXT", "Проверьте, правильно ли указан домен. " . CMS_SYSTEM_NAME . " должен быть установлен в корневую папку этого домена (или синонима)!");
const CONTROL_CONTENT_CATALOUGE_ERROR_CANNOT_CREATE_MORE = 'На данной копии нельзя добавить ещё один сайт. (Это ограничение отсутствует в коробочной версии системы.)';

const CONTROL_CONTENT_CATALOUGE_SUCCESS_ADD = 'Сайт успешно добавлен!';
const CONTROL_CONTENT_CATALOUGE_SUCCESS_EDIT = 'Настройки сайта успешно изменены!';
const CONTROL_CONTENT_CATALOUGE_SUCCESS_DELETE = 'Сайт успешно удален!';

const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MAININFO = 'Основная информация';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NAME = 'Название';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN = 'Домен';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN_SAAS_SUFFIX = 'Управление доменом - в';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN_SAAS_PERSONAL_ACCOUNT = 'Личном кабинете';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CATALOGUEFORM_LANG = 'Язык сайта (ISO 639-1)';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MIRRORS = 'Зеркала (по одному на строчке)';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_OFFLINE = 'Показывать, когда сайт выключен';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ROBOTS = 'Содержимое файла Robots.txt';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ROBOTS_DONT_CHANGE = 'Не изменяйте содержимое этого раздела.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ROBOTS_FILE_EXIST = 'Внимание! Файл robots.txt присутствует в корне сайта. Правьте его содержимое напрямую.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TEMPLATE = 'Макет дизайна';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TITLEPAGE = 'Титульная страница';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TITLEPAGE_PAGE = 'Титульная страница';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND = 'Страница не найдена (ошибка 404)';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND_PAGE = 'Страница не найдена';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY = 'Приоритет';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ON = 'включен';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_HTTPS_ENABLED = 'использовать HTTPS';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_STORE_VERSIONS = 'хранить версии контента';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_REMOVE_VERSIONS = 'Внимание: информация об истории изменений контента будет удалена!';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_LABEL_COLOR = 'Цвет метки';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DEFAULT_CLASS = 'Компонент по умолчанию';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_POLICY = 'Соглашение об использовании сайта';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SEARCH = 'Поиск';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE = 'Личный кабинет';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE_MODIFY = 'Мой профиль';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE_SIGNUP = 'Регистрация';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_CART = 'Корзина';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_ORDER_SUCCESS = 'Заказ оформлен';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_ORDER_LIST = 'Мои заказы';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_COMPARE = 'Сравнение товаров';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_FAVORITES = 'Избранные товары';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_DELIVERY = 'Условия доставки и возврата';

const CONTROL_CONTENT_SITE_ADD_EMPTY = 'новый пустой сайт';
const CONTROL_CONTENT_SITE_ADD_WITH_CONTENT = 'готовый сайт';
const CONTROL_CONTENT_SITE_CATEGORY = 'Категория';
const CONTROL_CONTENT_SITE_CATEGORY_ANY = 'любая';
const CONTROL_CONTENT_SITE_ADD_DATA_ERROR = 'Не удалось загрузить список доступных готовых сайтов';
const CONTROL_CONTENT_SITE_ADD_PREVIEW = 'демо';
const CONTROL_CONTENT_SITE_ADD_DOWNLOADING = 'Производится скачивание и развёртывание сайта';
const CONTROL_CONTENT_SITE_ADD_DOWNLOADING_ERROR = 'Не удалось загрузить архив с сайтом';

const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE = 'Способ отображения';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_TRADITIONAL = 'Традиционный';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_SHORTPAGE = 'Shortpage';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_LONGPAGE_VERTICAL = 'Longpage';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_LONGPAGE_HORIZONTAL = 'Longpage горизонтальный';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ACCESS = 'Доступ';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_USERS = 'пользователи';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_VIEW = 'просмотр';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_COMMENT = 'комментирование';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CHANGE = 'изменение';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SUBSCRIBE = 'подписка';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_EXTFIELDS = 'Дополнительные поля';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE = 'Сохранить';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_I = 'ы';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_U = 'и';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE = 'Внимание: сайт%s и все с ним%s связанное будет удалено.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CONFIRMDELETE = 'Подтвердить удаление';

const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_SETTINGS = 'Настройки мобильности';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SIMPLE = 'Обычный сайт';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE = 'Мобильный сайт';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ADAPTIVE = 'Адаптивный сайт';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_USE_RESS = 'Использовать технологию RESS';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_FOR = 'Мобильная версия для сайта';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_FOR_NOTICE = 'доступна только для мобильных сайтов';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_REDIRECT = 'Использовать принудительную переадресацию';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_NONE = '[нет]';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_DELETE = 'удалить';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_CHANGE = 'изменить';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_CRITERION = 'Определять мобильность по: ';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_USERAGENT = 'User-agent';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_SCREEN_RESOLUTION = 'Разрешение экрана';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_ALL_CRITERION = 'Обе характеристики';

const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_CREATED = 'Дата создания сайта';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_UPDATED = 'Дата изменения информации о сайте';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SECTIONSCOUNT = 'Количество подразделов';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SITESTATUS = 'Статус сайта';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ON = 'включен';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_OFF = 'выключен';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADD = 'добавить';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS = 'пользователи';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_READACCESS = 'Доступ на чтение';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADDACCESS = 'Доступ на добавление';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_EDITACCESS = 'Доступ на изменение';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SUBSCRIBEACCESS = 'Доступ на подписку';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_PUBLISHACCESS = 'Публикация объектов';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_VIEW = 'Просмотр';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADDING = 'Добавление';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SEARCHING = 'Поиск';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SUBSCRIBING = 'Подписка';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_EDIT = 'Редактирование';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_DELETE = 'Удалить сайт';

const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SITE = 'Сайт';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SUBSECTIONS = 'Подразделы';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_PRIORITY = 'Приоритет';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_GOTO = 'Перейти';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE = 'Удалить';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_LIST = 'список';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_TOOPTIONS = 'изменить настройки сайта';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SHOW = 'посмотреть сайт';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_EDIT = 'изменить информацию';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_NONE = 'В проекте нет ни одного сайта';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE = 'Добавить сайт';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SAVE = 'Сохранить изменения';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DBERROR = 'Ошибка выборки из базы!';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SECTIONWASCREATED = 'Создан раздел %s<br>';

# CONTROL CONTENT SUBDIVISION
const CONTROL_CONTENT_SUBDIVISION_FAVORITES_TITLE = 'Быстрое редактирование';
const CONTROL_CONTENT_SUBDIVISION_FULL_TITLE = 'Карта сайта';

# CONTROL CONTENT SUBDIVISION
const CONTROL_CONTENT_SUBDIVISION_INDEX_SITES = 'Сайты';
const CONTROL_CONTENT_SUBDIVISION_INDEX_SECTIONS = 'Разделы';
const CONTROL_CONTENT_SUBDIVISION_CLASS = 'Инфоблок';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ADDSECTION = 'Добавление раздела';
const CONTROL_CONTENT_SUBDIVISION_INDEX_OPTIONSECTION = 'Настройки раздела';
const CONTROL_CONTENT_SUBDIVISION_INDEX_DELETECONFIRMATION = 'Подтверждение удаления';
const CONTROL_CONTENT_SUBDIVISION_INDEX_MOVESECTION = 'Перенос раздела';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_NAME = 'Введите название раздела!';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_KEYWORD = 'Данное ключевое слово уже используется. Введите другое ключевое слово.';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_PARENTSUB = 'Не выбран родительский раздел!';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR = 'Ошибка добавления раздела';

const CONTROL_CONTENT_SUBDIVISION_SUCCESS_EDIT = 'Настройки раздела сохранены';

const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SECTION = 'Список компонентов раздела';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SITE = 'Список компонентов на сайте';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASS = 'Добавление компонента в раздел';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_OPTIONSCLASS = 'Настройки компонента раздела';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASSSITE = 'Добавление компонента на сайт';
const CONTROL_CONTENT_AREA_SUBCLASS_SETTINGS_TOOLTIP = 'изменить настройки инфоблока';

const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_NAME = 'Название инфоблока не может быть пустым!';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID = 'Ключевое слово содержит недопустимые символы, либо слишком длинное. Оно может содержать только буквы, цифры и символ подчеркивания, и не может быть длиннее 64 символов.';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD = 'Данное ключевое слово уже занято одним из инфоблоков. Введите другое ключевое слово.';

const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_SUCCESS_ADD = 'Инфоблок успешно добавлен';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_ADD = 'Ошибка добавления инфоблока в раздел';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_SUCCESS_EDIT = 'Инфоблок успешно изменен';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_EDIT = 'Ошибка редактирования инфоблока';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_DELETE = 'Ошибка удаления инфоблока';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_LIST_SUCCESS_EDIT = 'Список инфоблоков успешно изменен';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_LIST_ERROR_EDIT = 'Ошибка редактирования списка инфоблоков';

const CONTROL_CONTENT_SUBDIVISION_FIRST_SUBCLASS = 'В данном разделе нет ни одного инфоблока.<br />Для того, чтобы добавлять информацию в раздел, необходимо добавить в него хотя бы один инфоблок.';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION = 'Раздел';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_SUBSECTIONS = 'Подразделы';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_GOTO = 'Перейти';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_NOONEFAVORITES = 'Нет избранных разделов.';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONS = 'изменить настройки раздела';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONSSUBCLASS = 'изменить настройки компонента в разделе';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_TOVIEW = 'посмотреть страницу';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_TOEDIT = 'изменить информацию';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_PRIORITY = 'Приоритет';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_DELETE = 'Удалить';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_NONE = 'нет';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LIST = 'список';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ADD = 'добавить';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_NOSECTIONS = 'У данного сайта нет ни одного раздела.';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_NOSUBSECTIONS = 'В данном разделе нет подразделов.';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION = 'Добавить раздел';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CONTINUE = 'Продолжить';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_SELECT_ROOT_SECTION = 'Выберите раздел, в который хотите добавить новый';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_SAVE = 'Сохранить изменения';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDFAVOTITES = 'показывать раздел в &quot;Избранных разделах&quot;';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_USEEDITDESIGNTEMPLATE = 'Использовать этот макет при редактировании объектов';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA = 'Основная информация';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME = 'Название';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD = 'Ключевое слово';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_EXTURL = 'Внешняя ссылка';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_LANG = 'Язык раздела (ISO 639-1)';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE = 'Макет дизайна';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_CS = 'Настройки макета дизайна';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_EDIT = 'Редактировать код макета';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_N = 'Наследовать';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_MAINAREA_MIXIN_SETTINGS = 'Настройки отображения рабочей области';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON = 'включен';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNOFF = 'выключен';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION = 'добавить подраздел';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_REMSITE = 'удалить сайт';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_MULTI_SUB_CLASS = 'Несколько инфоблоков в разделе';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE = 'Способ отображения';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_INHERIT = 'Наследовать';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_TRADITIONAL = 'Традиционный';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_SHORTPAGE = 'Shortpage';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_LONGPAGE_VERTICAL = 'Longpage';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_LONGPAGE_HORIZONTAL = 'Longpage горизонтальный';

const CONTROL_TEMPLATE_CUSTOM_SETTINGS_NOT_AVAILABLE = 'Данный макет дизайна не имеет дополнительных настроек.';
const CONTROL_TEMPLATE_CUSTOM_SETTINGS = 'Настройки отображения макета дизайна в разделе';
const CONTROL_TEMPLATE_CUSTOM_SETTINGS_ISNOTSET = 'Настройки отображения макета дизайна в разделе отсутствуют';
const CONTROL_TEMPLATE_CUSTOM_SETTINGS_INHERITED_FROM_SITE = "Значения параметров, которые не заданы в настройках этого раздела,
        будут взяты из <a href='%s' target='_top'>настроек макета сайта</a>.";
const CONTROL_TEMPLATE_CUSTOM_SETTINGS_INHERITED_FROM_FOLDER = "Значения параметров, которые не заданы в настройках этого раздела,
        будут взяты из <a href='%s' target='_top'>настроек макета раздела «%s»</a>.";

const CONTROL_CUSTOM_SETTINGS_INHERIT = 'использовать значение, заданное в родительском разделе';
const CONTROL_CUSTOM_SETTINGS_OFF = 'нет';
const CONTROL_CUSTOM_SETTINGS_ON = 'да';

const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_EDIT = 'изменить информацию';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_KILL = 'удалить этот раздел';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW = 'посмотреть страницу';
const CONTROL_CONTENT_CATALOUGE_FUNCS_MSG_OK = 'Раздел успешно добавлен.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_A_ADDCLASSTOSECTION = 'Добавить компонент в раздел';
const CONTROL_CONTENT_CATALOUGE_FUNCS_A_BACKTOSECTIONLIST = 'Вернуться к списку разделов';

const CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOCATALOGUE = 'Сайт не существует.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBDIVISION = 'Раздел не существует.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBCLASS = 'Компонент в разделе не существует.';

const CLASSIFICATOR_COMMENTS_DISABLE = 'Запретить';
const CLASSIFICATOR_COMMENTS_ENABLE = 'Разрешить';
const CLASSIFICATOR_COMMENTS_NOREPLIED = 'разрешить, если нет ответов';

const CONTROL_CONTENT_CATALOGUE_FUNCS_COMMENTS = 'Комментирование';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS = 'Комментирование';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS_ADD = 'Добавление комментариев';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS_AUTHOR_EDIT = 'Редактирование своих комментариев';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS_AUTHOR_DELETE = 'Удаление своих комментариев';

const CONTROL_CONTENT_CATALOGUE_FUNCS_DEMO_MODE = 'Демо-режим';
const CONTROL_CONTENT_CATALOGUE_FUNCS_DEMO_MODE_CHECKBOX = 'Демонстрационный режим работы сайта';

const CONTROL_CONTENT_SUBCLASS_FUNCS_COMMENTS = 'Комментирование';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS = 'Доступ';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT = 'Наследовать';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_PUBLISH = 'Публикация объектов';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_READ = 'Доступ на чтение';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_ADD = 'Доступ на добавление';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_EDIT = 'Доступ на изменение';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_SUBSCRIBE = 'Доступ на подписку';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_PUBLISH = 'Публикация объектов';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_USERS = 'пользователи';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_VIEW = 'просмотр';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_READ = 'Просмотр';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_COMMENT = 'комментирование';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_ADD = 'добавление';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_WRITE = 'Добавление';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_EDIT = 'Изменение';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_CHECKED = 'Включение';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_DELETE = 'Удаление';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_SUBSCRIBE = 'подписка';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_ADVANCEDFIELDS = 'Дополнительные поля';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_HOWSHOW = 'Настройки отображения';
const CONTROL_CONTENT_SUBDIVISION_CUSTOM_SETTINGS_TEMPLATE = 'Настройки отображения компонента';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES = 'Да';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO = 'Нет';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_UPDATED = 'Дата изменения информации о разделе';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_CLASS_COUNT = 'Количество компонентов';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_STATUS = 'Статус раздела';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_SUBSECTIONS_COUNT = 'Количество подразделов';


const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE = 'Удалить раздел';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_ROOT = 'Корневой раздел';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE_CONFIRMATION = 'Подтвердить удаление';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING = 'Внимание: раздел%s и все с н%s связанное будет удалено.';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_ONE_MANY = 'ы';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_ONE_ONE = '';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_TWO_MANY = 'ими';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_TWO_ONE = 'им';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_ERR_NOONESITE = 'Указанного сайта не существует.';

const CONTROL_CONTENT_SUBDIVISION_SYSTEM_FIELDS = 'Системные';
const CONTROL_CONTENT_SUBDIVISION_SYSTEM_FIELDS_NO = 'В системной таблице «Разделы» нет дополнительных полей';

const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_ALWAYS = 'всегда';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_HOURLY = 'ежечасно';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_DAILY = 'ежедневно';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_WEEKLY = 'еженедельно';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_MONTHLY = 'ежемесячно';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_YEARLY = 'ежегодно';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_NEVER = 'никогда';

const CONTROL_CONTENT_SUBDIVISION_SEO_META = 'Мета-тэги для SEO';
const CONTROL_CONTENT_SUBDIVISION_SEO_SMO_META = 'Мета-тэги для социальных сетей';
const CONTROL_CONTENT_SUBDIVISION_SEO_INDEXING = 'Индексирование';
const CONTROL_CONTENT_SUBDIVISION_SEO_CURRENT_VALUE = 'Текущее значение';
define("CONTROL_CONTENT_SUBDIVISION_SEO_VALUE_NOT_SETTINGS", "Значение %s на странице отличное от того, что Вы вводили." . (CMS_SYSTEM_NAME === 'Netcat' ? " <a target='_blank' href='https://netcat.ru/developers/docs/seo/title-keywords-and-description/'>Подробнее</a>." : ''));
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_HEADER = 'Заголовок Last-Modified';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_NONE = 'Не посылать';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_YESTERDAY = 'Предыдущий день';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_HOUR = 'Предыдущий час';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_CURRENT = 'Текущую дату';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_ACTUAL = 'Актуальную дату';
const CONTROL_CONTENT_SUBDIVISION_SEO_DISALLOW_INDEXING = 'Разрешить индексирование';
const CONTROL_CONTENT_SUBDIVISION_SEO_DISALLOW_INDEXING_YES = 'Да';
const CONTROL_CONTENT_SUBDIVISION_SEO_DISALLOW_INDEXING_NO = 'Нет';
const CONTROL_CONTENT_SUBDIVISION_SEO_INCLUDE_IN_SITEMAP = 'Включить раздел в Sitemap';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_PRIORITY = 'Sitemap: приоритет страницы';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ = 'Sitemap: частота изменения страницы';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE_SUCCESS = 'Удаление выполнено успешно.';

const CONTROL_CONTENT_CLASS = 'Компонент';
const CONTROL_CONTENT_SUBCLASS_CLASSNAME = 'Название блока';
const CONTROL_CONTENT_SUBCLASS_SHOW_ALL = 'показать все';
const CONTROL_CONTENT_SUBCLASS_ONSECTION = 'в разделе';
const CONTROL_CONTENT_SUBCLASS_ONSITE = 'на сайте';
const CONTROL_CONTENT_SUBCLASS_MSG_NONE = 'В данном разделе нет инфоблоков.';
const CONTROL_CONTENT_SUBCLASS_DEFAULTACTION = 'Действие по умолчанию';
const CONTROL_CONTENT_SUBCLASS_CREATIONDATE = 'Дата создания компонента %s';
const CONTROL_CONTENT_SUBCLASS_UPDATEDATE = 'Дата изменения информации о компоненте %s';
const CONTROL_CONTENT_SUBCLASS_TOTALOBJECTS = 'Объектов';
const CONTROL_CONTENT_SUBCLASS_CLASSSTATUS = 'Статус компонента';
const CONTROL_CONTENT_SUBCLASS_CHANGEPREFS = 'Изменить настройки компонента %s';
const CONTROL_CONTENT_SUBCLASS_DELETECLASS = 'Удалить компонент %s';
const CONTROL_CONTENT_SUBCLASS_ISNAKED = 'Не использовать макет дизайна';
const CONTROL_CONTENT_SUBCLASS_SRCMIRROR = 'Источник данных';
const CONTROL_CONTENT_SUBCLASS_SRCMIRROR_NONE = '[нет]';
const CONTROL_CONTENT_SUBCLASS_SRCMIRROR_EDIT = 'изменить';
const CONTROL_CONTENT_SUBCLASS_SRCMIRROR_DELETE = 'удалить';
const CONTROL_CONTENT_SUBCLASS_TYPE = 'Тип инфоблока';
const CONTROL_CONTENT_SUBCLASS_TYPE_SIMPLE = 'обычный';
const CONTROL_CONTENT_SUBCLASS_TYPE_MIRROR = 'зеркальный';
const CONTROL_CONTENT_SUBCLASS_MIRROR = 'Зеркальный инфоблок';
const CONTROL_CONTENT_SUBCLASS_MULTI_TITLE = 'Способ отображения инфоблоков на странице';
const CONTROL_CONTENT_SUBCLASS_MULTI_ONONEPAGE = 'выводить на одной странице';
const CONTROL_CONTENT_SUBCLASS_MULTI_ONTABS = 'выводить во вкладках';
const CONTROL_CONTENT_SUBCLASS_MULTI_NONE = 'выводить только первый инфоблок';
const CONTROL_CONTENT_SUBCLASS_EDIT_IN_PLACE = "Данные этого инфоблока необходимо редактировать на странице «<a href='%s'>%s</a>»";
const CONTROL_CONTENT_SUBCLASS_CONDITION_OFFSET = 'Сколько объектов пропустить от начала выборки';
const CONTROL_CONTENT_SUBCLASS_CONDITION_LIMIT = 'Максимальное количество записей в выборке';
const CONTROL_CONTENT_SUBCLASS_FUNCS_SETTINGS_GOTO = 'Перейти';
const CONTROL_CONTENT_SUBCLASS_CONTAINER = 'Контейнер';
const CONTROL_CONTENT_SUBCLASS_AREA = 'Область «%s»';

const CONTROL_SETTINGSFILE_TITLE_ADD = 'Добавление';
const CONTROL_SETTINGSFILE_TITLE_EDIT = 'Редактирование';
const CONTROL_SETTINGSFILE_BASIC_REGCODE = 'Номер лицензии';
const CONTROL_SETTINGSFILE_BASIC_MAIN = 'Основная информация';
const CONTROL_SETTINGSFILE_BASIC_MAIN_NAME = 'Название проекта';

const CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE = 'Макет дизайна, используемый при редактировании объектов';
const CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE_DEFAULT = 'макет редактируемого раздела';

const CONTROL_SETTINGSFILE_SHOW_EXCURSION = 'Показывать экскурсию для текущего пользователя';

const CONTROL_SETTINGSFILE_BASIC_EMAILS = 'Рассылки';
const CONTROL_SETTINGSFILE_BASIC_EMAILS_FILELD = 'Поле (с форматом email) в таблице пользователей';
const CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMNAME = 'Имя отправителя';
const CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMEMAIL = 'Email отправителя';
const CONTROL_SETTINGSFILE_BASIC_EMAILS_TOEMAIL = 'Email для уведомлений по умолчанию';
const CONTROL_SETTINGSFILE_BASIC_CHANGEDATA = 'Изменить настройки системы';


const CONTROL_SETTINGSFILE_BASIC_USE_SMTP = 'Использовать SMTP';
const CONTROL_SETTINGSFILE_BASIC_USE_SENDMAIL = 'Использовать Sendmail';
const CONTROL_SETTINGSFILE_BASIC_USE_MAIL = 'Использовать функцию mail';
const CONTROL_SETTINGSFILE_BASIC_MAIL_PARAMETERS = 'Дополнительные параметры для sendmail (<code>%s</code> для подстановки адреса отправителя)';
const CONTROL_SETTINGSFILE_BASIC_SMTP_HOST = 'SMTP сервер';
const CONTROL_SETTINGSFILE_BASIC_SMTP_PORT = 'Порт';
const CONTROL_SETTINGSFILE_BASIC_SMTP_AUTH_USE = 'Использовать авторизацию';
const CONTROL_SETTINGSFILE_BASIC_SMTP_USERNAME = 'Имя пользователя';
const CONTROL_SETTINGSFILE_BASIC_SMTP_PASSWORD = 'Пароль';
const CONTROL_SETTINGSFILE_BASIC_SMTP_ENCRYPTION = 'Шифрование';
const CONTROL_SETTINGSFILE_BASIC_SMTP_NOENCRYPTION = 'Нет';
const CONTROL_SETTINGSFILE_BASIC_SENDMAIL_COMMAND = "Sendmail команда (например '/usr/sbin/sendmail -bs')";
const CONTROL_SETTINGSFILE_BASIC_MAIL_TRANSPORT_HEADER = 'Вид транспорта';

const CONTROL_SETTINGSFILE_AUTOSAVE = 'Настройки функции «Черновик»';
const CONTROL_SETTINGSFILE_AUTOSAVE_USE = 'Использовать функцию «Черновик»';
const CONTROL_SETTINGSFILE_AUTOSAVE_TYPE_KEYBOARD = 'Сохранять по нажатию клавиш';
const CONTROL_SETTINGSFILE_AUTOSAVE_TYPE_TIMER = 'Сохранять периодически';
const CONTROL_SETTINGSFILE_AUTOSAVE_PERIOD = 'Периодичность, сек';
const CONTROL_SETTINGSFILE_AUTOSAVE_NO_ACTIVE = 'Сохранять только при бездействии';

const CONTROL_SETTINGSFILE_INLINE_IMAGE_CROP = 'Настройки редактирования изображений';
const CONTROL_SETTINGSFILE_INLINE_IMAGE_CROP_USE = 'Использовать быстрое редактирование изображений';
const CONTROL_SETTINGSFILE_INLINE_IMAGE_CROP_DIMENSIONS = 'Предустановленные расширения (Ширина x Высота)';

const CONTROL_SETTINGSFILE_DOCHANGE_ERROR_NAME = 'Название проекта не может быть пустым!';

const NETCAT_AUTH_TYPE_LOGINPASSWORD = 'Вход по логину/паролю';
const NETCAT_AUTH_TYPE_TOKEN = 'Вход по e-token';
const NETCAT_AUTH_TYPE_SSO = 'Вход через %s';
const CONTROL_AUTH_ON_ONE_SITE = 'Авторизовывать на сайте';
const CONTROL_AUTH_ON_ALL_SITES = 'На всех сайтах';
const CONTROL_AUTH_HTML_LOGIN = 'Логин';
const CONTROL_AUTH_HTML_PASSWORD = 'Пароль';
const CONTROL_AUTH_HTML_PASSWORDCONFIRM = 'Пароль еще раз';
const CONTROL_AUTH_HTML_SAVELOGIN = 'Запомнить логин и пароль';
const CONTROL_AUTH_HTML_LANG = 'Язык';
const CONTROL_AUTH_HTML_AUTH = 'Авторизоваться';
const CONTROL_AUTH_HTML_BACK = 'Вернуться';
define("CONTROL_AUTH_FIELDS_NOT_EMPTY", "Поля \"".CONTROL_AUTH_HTML_LOGIN."\" и \"".CONTROL_AUTH_HTML_PASSWORD."\" не могут быть пустыми!");
define("CONTROL_AUTH_LOGIN_NOT_EMPTY", "Поле \"".CONTROL_AUTH_HTML_LOGIN."\" не может быть пустым!");
const CONTROL_AUTH_LOGIN_OR_PASSWORD_INCORRECT = 'Авторизационные данные неверны!';
const CONTROL_AUTH_PIN_INCORRECT = 'Введен неверный PIN код!';
const CONTROL_AUTH_TOKEN_PLUGIN_DONT_INSTALL = 'Плагин не установлен';
const CONTROL_AUTH_KEYPAIR_INCORRECT = 'Ошибка при создании ключевой пары';
const CONTROL_AUTH_USB_TOKEN_NOT_INSERTED = 'USB-токен отсутствует';
const CONTROL_AUTH_TOKEN_CURRENT_TOKENS = 'Текущие привязанные токены пользователя';
const CONTROL_AUTH_TOKEN_NEW = 'Привязать новый токен';
const CONTROL_AUTH_TOKEN_PLUGIN_ERROR = "В браузере не установлен <a href='http://www.rutoken.ru/hotline/download/' target='_blank'>плагин для работы с токеном</a>";
const CONTROL_AUTH_TOKEN_MISS = 'Токен отсутствует';
const CONTROL_AUTH_TOKEN_NEW_BUTTON = 'Привязать';

const CONTROL_AUTH_JS_REQUIRED = 'Для работы в системе администрирования необходимо включить поддержку javascript';

const NETCAT_MODULE_AUTH_INSIDE_ADMIN_ACCESS = 'доступ в зону администрирования';
const CONTROL_AUTH_MSG_MUSTAUTH = 'Для авторизации необходимо ввести логин и пароль.';


const CONTROL_FS_NAME_SIMPLE = 'Простая';
const CONTROL_FS_NAME_ORIGINAL = 'Стандартная';
const CONTROL_FS_NAME_PROTECTED = 'Защищенная';

const CONTROL_CLASS_CLASS_TEMPLATE = 'Шаблон вывода инфоблока';
const CONTROL_CLASS_CLASS_TEMPLATE_CHANGE_LATER = 'Другие настройки инфоблока вы сможете изменить после добавления раздела.';
const CONTROL_CLASS_CLASS_TEMPLATE_DEFAULT = 'Шаблон по умолчанию';
const CONTROL_CLASS_CLASS_TEMPLATE_EDIT_MODE = 'Шаблон вывода в режиме редактирования';
const CONTROL_CLASS_CLASS_TEMPLATE_ADMIN_MODE = 'Шаблон вывода в режиме администрирования';
const CONTROL_CLASS_CLASS_TEMPLATE_EDIT_MODE_DONT_USE = '-- не использовать отдельный шаблон --';
const CONTROL_CLASS_CLASS_TEMPLATE_ADD = 'Добавить шаблон';
const CONTROL_CLASS_CLASS_DONT_USE_TEMPLATE = '-- не использовать шаблон --';
const CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NAME = 'Введите название шаблона компонента';
const CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NOT_FOUND = 'Шаблоны компонента отсутствуют';
const CONTROL_CLASS_CLASS_TEMPLATE_DELETE_WARNING = 'Внимание: вместо шаблонов будет использоваться основной компонент «%s».';
const CONTROL_CLASS_CLASS_TEMPLATE_NOT_FOUND = 'Шаблон с идентификатором %s не найден!';
const CONTROL_CLASS_CLASS_TEMPLATE_ERROR_ADD = 'Ошибка добавления шаблона компонента';
const CONTROL_CLASS_CLASS_TEMPLATE_ERROR_EDIT = 'Ошибка редактирования шаблона компонента';
const CONTROL_CLASS_CLASS_TEMPLATE_SUCCESS_ADD = 'Шаблон компонента успешно добавлен';
const CONTROL_CLASS_CLASS_TEMPLATE_SUCCESS_EDIT = 'Шаблон компонента успешно изменен';
const CONTROL_CLASS_CLASS_TEMPLATE_GROUP = 'Шаблоны компонентов';
const CONTROL_CLASS_CLASS_TEMPLATE_BUTTON_EDIT = 'Редактировать';
const CONTROL_CLASS_CLASS_TEMPLATES = 'Шаблоны компонента';
const CONTROL_CLASS_CLASS_TEMPLATE_RECORD_TEMPLATE_WARNING = 'Внимание! Если вы будете добавлять в этот блок элементы, а не выводить в нем элементы из других блоков, вы не сможете попасть на страницу полного вывода объекта.<br>Уверены, что хотите продолжить?';
const CLASS_TEMPLATE_TAB_EDIT = 'Редактирование шаблона';
const CLASS_TEMPLATE_TAB_DELETE = 'Удаление шаблона';
const CLASS_TEMPLATE_TAB_INFO = 'Настройки';

const CONTROL_CLASS = 'Компоненты';
const CONTROL_CLASS_ADD_ACTION = 'Добавление компонента';
const CONTROL_CLASS_DELETECOMMIT = 'Подтверждение удаления компонента';
const CONTROL_CLASS_DOEDIT = 'Редактирование компонента';
const CONTROL_CLASS_CONTINUE = 'Продолжить';
const CONTROL_CLASS_NONE = 'Компоненты отсутствуют.';
const CONTROL_CLASS_ADD = 'Добавить компонент';
const CONTROL_CLASS_ADD_FS = 'Добавить компонент 5.0';
const CONTROL_CLASS_CLASS = 'Компонент';
const CONTROL_CLASS_SYSTEM_TABLE = 'Системная таблица';
const CONTROL_CLASS_ACTIONS = 'Шаблоны действий';
const CONTROL_CLASS_FIELD = 'Поле';
const CONTROL_CLASS_FIELDS = 'Поля';
const CONTROL_CLASS_FIELDS_COUNT = 'Полей';
const CONTROL_CLASS_CUSTOM = 'Пользовательские настройки';
const CONTROL_CLASS_DELETE = 'Удалить';
const CONTROL_CLASS_NEWCLASS = 'Новый компонент';
const CONTROL_CLASS_NEWTEMPLATE = 'Новый шаблон';
const CONTROL_CLASS_TO_FS = 'Класс в файловую систему';

const CONTROL_CLASS_FUNCS_SHOWCLASSLIST_ADDCLASS = 'Добавить компонент';
const CONTROL_CLASS_FUNCS_SHOWCLASSLIST_IMPORTCLASS = 'Импортировать компонент';

const CONTROL_CLASS_ACTIONS_VIEW = 'просмотр';
const CONTROL_CLASS_ACTIONS_ADD = 'добавление';
const CONTROL_CLASS_ACTIONS_EDIT = 'изменение';
const CONTROL_CLASS_ACTIONS_CHECKED = 'включение';
const CONTROL_CLASS_ACTIONS_SEARCH = 'поиск';
const CONTROL_CLASS_ACTIONS_MAIL = 'подписка';
const CONTROL_CLASS_ACTIONS_DELETE = 'удаление';
const CONTROL_CLASS_ACTIONS_MODERATE = 'модери&shy;рование';
const CONTROL_CLASS_ACTIONS_ADMIN = 'администри&shy;рование';

const CONTROL_OBJECT_NAME_LABEL = 'Поле, содержащее название объекта';
const CONTROL_OBJECT_NAME_NOT_SELECTED = 'Не выбрано';
const CONTROL_OBJECT_NAME_ACC = 'Название объекта в единственном числе винительного падежа («добавить <em>что</em>?»)';
const CONTROL_OBJECT_NAME_ACC_PLURAL = 'Название объекта во множественном числе винительного падежа («удалить все <em>что</em>?»)';
const CONTROL_OBJECT_NAME_GEN = 'Название объекта в единственном числе родительного падежа («редактирование <em>чего</em>?»)';

define("CONTROL_CLASS_INFO_ADDSLASHES", "Настраивая компонент, не забудьте <a href='#' onclick=\"window.open('".$ADMIN_PATH."template/converter.php', 'converter','width=600,height=410,status=no,resizable=yes'); return false;\">экранировать спецсимволы</a>.");
const CONTROL_CLASS_ERRORS_DB = 'Ошибка выборки из базы!';
const CONTROL_CLASS_CLASS_NAME = 'Название';
const CONTROL_CLASS_CLASS_KEYWORD = 'Ключевое слово';
const CONTROL_CLASS_CLASS_MAIN_CLASSTEMPLATE_LABEL = 'Основной шаблон компонента';
const CONTROL_CLASS_CLASS_GROUPS = 'Группы компонентов';
const CONTROL_CLASS_CLASS_NO_GROUP = 'Без группы';
const CONTROL_CLASS_CLASS_OBJECTSLIST = 'Шаблон отображения списка объектов';
const CONTROL_CLASS_CLASS_DESCRIPTION = 'Описание инфоблока';
const CONTROL_CLASS_CLASS_SETTINGS = 'Настройки инфоблока';
const CONTROL_SCLASS_ACTION = 'Шаблоны действий';
const CONTROL_SCLASS_TABLE = 'Таблица';
const CONTROL_SCLASS_TABLE_NAME = 'Название таблицы';
const CONTROL_SCLASS_LISTING_NAME = 'Название списка';
const CONTROL_CLASS_CLASSFORM_INFO_FOR_NEWCLASS = 'Информация о компоненте';
const CONTROL_CLASS_CLASSFORM_MAININFO = 'Основная информация';
const CONTROL_CLASS_CLASSFORM_TEMPLATE_PATH = "Файлы компонента находятся в папке <a href='%s'>%s</a>";
const CONTROL_CLASS_SITE_STYLES = 'Стили для сайта';
define("CONTROL_CLASS_SITE_STYLES_DISABLED_WARNING", "Данный компонент работает в режиме совместимости с " . CMS_SYSTEM_NAME . " 5.6, добавление CSS-стилей в котором невозможно.");
const CONTROL_CLASS_SITE_STYLES_ENABLE_BUTTON = 'Включить стили шаблона компонента';
const CONTROL_CLASS_SITE_STYLES_ENABLE_WARNING = 'После отключения режима совместимости будет добавляться дополнительная разметка
    (блок-обёртка <code>&lt;div&gt;</code>) при выводе блоков с использованием данного шаблона:
    <ul><li>списков объектов из инфоблоков, 
    <li>основной части страницы полного вывода объекта, 
    <li>форм добавления, изменения и поиска.</ul>
    В зависимости от особенностей используемых на существующих сайтах CSS-стилей может 
    понадобиться соответствующее изменение CSS-правил.';
const CONTROL_CLASS_SITE_STYLES_DOCS_LINK = "Подробнее о стилях компонентов см. <a href='%s' target='_blank'>в документации</a>.";
const CONTROL_CLASS_MULTIPLE_MODE_SWITCH = 'Оптимизирован для использования в режиме отображения нескольких блоков на странице';
const CONTROL_CLASS_TEMPLATE_MULTIPLE_MODE_SWITCH = 'Шаблон оптимизирован для использования в режиме отображения нескольких блоков на странице';
const CONTROL_CLASS_LIST_PREVIEW = 'Эскиз отображения списка объектов (.png)';
const CONTROL_CLASS_LIST_PREVIEW_NONE = 'Эскиз отсутствует';

const CONTROL_CLASS_KEYWORD_ONLY_DIGITS = 'Ключевое слово не может состоять только из цифр';
const CONTROL_CLASS_KEYWORD_TOO_LONG = 'Длина ключевого слова не может быть более %d символов';
const CONTROL_CLASS_KEYWORD_INVALID_CHARACTERS = 'Ключевое слово может содержать только буквы латинского алфавита, цифры и символы подчёркивания';
const CONTROL_CLASS_KEYWORD_NON_UNIQUE = 'Ключевое слово «%s» уже присвоено компоненту «%s»';
const CONTROL_CLASS_KEYWORD_TEMPLATE_NON_UNIQUE = 'Ключевое слово «%s» уже присвоено шаблону «%s»';
const CONTROL_CLASS_KEYWORD_RESERVED = 'Невозможно использовать «%s» в качестве ключевого слова, так как оно является зарезервированным';

const CONTROL_CLASS_CLASSFORM_CHECK_ERROR = 'Ошибка кода в поле «%s» компонента';

const CONTROL_CLASS_CLASS_OBJECTSLIST_PREFIX = 'Префикс списка объектов';
const CONTROL_CLASS_CLASS_OBJECTSLIST_BODY = 'Объект в списке';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SUFFIX = 'Суффикс списка объектов';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW = 'Показывать по';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ = 'объектов на странице';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW_NUM = 'Количество объектов на странице';
const CONTROL_CLASS_CLASS_MIN_RECORDS = 'Минимальное количество объектов в инфоблоке';
const CONTROL_CLASS_CLASS_MAX_RECORDS = 'Максимальное количество объектов в инфоблоке';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SORT = 'Сортировать объекты по полю (полям)';
const CONTROL_CLASS_CLASS_OBJECTSLIST_TITLE = 'Заголовок страницы';

const CONTROL_CLASS_CLASS_OBJECTSLIST_WRONG_NC_CTPL = 'В nc_object_list(%s, %s) передан ошибочный nc_ctpl - %s. ';
const CONTROL_CLASS_CLASS_OBJECTFULL_WRONG_NC_CTPL = 'Передан ошибочный nc_ctpl - %s. ';

const CONTROL_CLASS_CLASS_OBJECTVIEW = 'Шаблон отображения одного объекта на отдельной странице';

const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_DOPL = 'Дополнительно';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_CACHE = 'Кэширование';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_SYSTEM = 'Системные настройки';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR = 'Перенос строки — &lt;BR&gt;';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML = 'Разрешать HTML-теги';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGETITLE = 'Заголовок страницы';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_USEASALT = 'Использовать как полностью альтернативный заголовок';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGEBODY = 'Отображение объекта';
const CONTROL_CLASS_CLASS_CREATENEW_BASICOLD = 'Создать новый компонент на основе существующего';
const CONTROL_CLASS_CLASS_CREATENEW_CLEARNEW = 'Создать новый компонент &quot;с нуля&quot;';
const CONTROL_CLASS_CLASS_DELETE_WARNING = 'Внимание: компонент%s и все с ним%s связанное будет удалено.';
const CONTROL_CLASS_CLASS_NOT_FOUND = 'Компонент с идентификатором %s не найден!';

const CONTROL_CLASS_CUSTOM_SETTINGS_TEMPLATE = 'Настройки отображения компонента раздела';
const CONTROL_CLASS_CUSTOM_SETTINGS_PARAMETER = 'Параметр';
const CONTROL_CLASS_CUSTOM_SETTINGS_DEFAULT = 'По умолчанию';
const CONTROL_CLASS_CUSTOM_SETTINGS_VALUE = 'Значение';
const CONTROL_CLASS_CUSTOM_SETTINGS_HAS_ERROR = 'Одно или несколько значений указаны некорректно. Пожалуйста, исправьте ошибку.';

const CONTROL_CLASS_IMPORT = 'Импорт компонента';
const CONTROL_CLASS_IMPORTS = 'Импорт компонентов';
const CONTROL_CLASS_IMPORT_UPLOAD = 'Закачать';
const CONTROL_CLASS_IMPORT_ERROR_NOTUPLOADED = 'Файл не закачан.';
const CONTROL_CLASS_IMPORT_ERROR_CANNOTBEINSTALLED = 'Компонент не может быть установлен.';
const CONTROL_CLASS_IMPORT_ERROR_VERSION_ID = 'Компонент для версии %s, текущая версия %s.';
const CONTROL_CLASS_IMPORT_ERROR_NO_VERSION_ID = 'Версия системы не указана или неверный формат файла.';
const CONTROL_CLASS_IMPORT_ERROR_NO_FILES = 'Отсутствуют данные для создания файлов шаблонов компонента.';
const CONTROL_CLASS_IMPORT_ERROR_CLASS_IMPORT = 'Ошибка создания компонента, данные компонента не добавлены.';
const CONTROL_CLASS_IMPORT_ERROR_CLASS_TEMPLATE_IMPORT = 'Ошибка создания шаблонов компонента, данные шаблонов не добавлены.';
const CONTROL_CLASS_IMPORT_ERROR_MESSAGE_TABLE = 'Ошибка создания таблицы данных компонента.';
const CONTROL_CLASS_IMPORT_ERROR_FIELD = 'Ошибка создания полей компонента.';

const CONTROL_CLASS_CONVERT = 'Конвертирование компонента';
const CONTROL_CLASS_CONVERT_BUTTON = 'Конвертировать в 5.0';
const CONTROL_CLASS_CONVERT_BUTTON_UNDO = 'Отменить конвертирование';
const CONTROL_CLASS_CONVERT_DB_ERROR = 'Ошибка изменения компонентов в базе';
const CONTROL_CLASS_CONVERT_OK = 'Конвертация успешна';
const CONTROL_CLASS_CONVERT_OK_GOEDIT = 'Перейти к редактированию компонента';
const CONTROL_CLASS_CONVERT_CLASSLIST_TITLE = 'Будут сконвертированы следующие компоненты и их шаблоны';
const CONTROL_CLASS_CONVERT_CLASSLIST_TITLE_UNDO = 'Будет отменена конвертация следующих компонентов и их шаблонов';
const CONTROL_CLASS_CONVERT_CLASSFOLDERS_TITLE = 'Будут созданы папки с файлами шаблонов v5, включая дампы шаблона v4 в файлах class_40_backup.html';
const CONTROL_CLASS_CONVERT_CLASSFOLDERS_TITLE_UNDO = 'Необходимо будет удалить папки с файлами шаблонов 5.0(необязательно)';
const CONTROL_CLASS_CONVERT_NOTICE = 'После конвертации компонента могут возникнуть ошибки синтаксиса в его шаблонах!
                    Рекомендуем закрыть сайт на время изменений.';
const CONTROL_CLASS_CONVERT_NOTICE_UNDO = 'После отмены конвертации компонент вернется к состоянию до конвертации, все изменения в режиме 5.0 потеряются!';
const CONTROL_CLASS_CONVERT_UNDO_FILE_ERROR = 'Нет данных для восстановления';

const CONTROL_CLASS_NEWGROUP = 'Новая группа';
const CONTROL_CLASS_EXPORT = 'Экспортировать компонент в файл';
const CONTROL_CLASS_AUXILIARY_SWITCH = 'Служебный компонент';
const CONTROL_CLASS_AUXILIARY = '(служебный)';
const CONTROL_CLASS_BLOCK_MARKUP_SWITCH = "Отключить <a href='https://netcat.ru/developers/docs/components/stylesheets/' target='_blank'>дополнительную разметку</a>";
const CONTROL_CLASS_BLOCK_LIST_MARKUP_SWITCH = 'Отключить разметку вокруг списка объектов (стандартные инструменты оформления блоков будут недоступны)';
const CONTROL_CLASS_BLOCK_MARKUP_SWITCH_WARNING = 'Дополнительная разметка необходима для поддержки стилей шаблона компонента и корректной работы компонента в режиме редактирования.';

const CONTROL_CLASS_COMPONENT_TEMPLATE_FOR_RSS_DOESNT_EXIST = 'Rss-лента %sне доступна, поскольку отсутствует шаблон компонента для rss';
const CONTROL_CLASS_COMPONENT_TEMPLATE_FOR_XML_DOESNT_EXIST = 'Xml-выгрузка %sне доступна, поскольку отсутствует шаблон компонента для xml';
const CONTROL_CLASS_COMPONENT_TEMPLATE_FOR_TRASH_DOESNT_EXIST = 'Вывод корзины не доступен, поскольку отсутствует шаблон компонента для корзины';

const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE = 'Тип';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_CLASSTEMPLATE = 'Тип шаблона компонента';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_MULTI_EDIT = 'Множественное редактирование';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_RSS = 'RSS';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_XML = 'XML-выгрузка';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_TRASH = 'Для корзины удаленных объектов';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_USEFUL = 'Обычный';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_INSIDE_ADMIN = 'Режим администрирования';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_ADMIN_MODE = 'Режим редактирования';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_TITLE = 'Для титульной страницы';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_MOBILE = 'Мобильный';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_RESPONSIVE = 'Адаптивный';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_OBJECT_PARTIAL = 'Блок страницы объекта';

const CONTROL_CLASS_COMPONENT_TEMPLATE_BASE_AUTO = 'Автоматически';
const CONTROL_CLASS_COMPONENT_TEMPLATE_BASE_EMPTY = 'Пустой';
const CONTROL_CLASS_COMPONENT_TEMPLATE_ADD_PARAMETRS = 'Параметры добавления шаблона компонента';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_BASE = 'Создать шаблон компонента на основе';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_FOR_TRASH = 'Создать шаблон для корзины';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_FOR_RSS = 'Создать шаблон для rss';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_FOR_XML = 'Создать шаблон для xml';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TURN_ON_RSS = 'Включить rss-ленту';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TURN_ON_XML = 'Включить xml-выгрузку';
const CONTROL_CLASS_COMPONENT_TEMPLATE_VIEW = 'посмотреть';
const CONTROL_CLASS_COMPONENT_TEMPLATE_EDIT = 'настроить';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_ERROR = 'Ошибка создания шаблона компонента';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_USEFUL = 'Шаблон компонента успешно создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_RSS = 'Шаблон компонента для RSS успешно создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_XML = 'Шаблон компонента успешно для XML создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_TRASH = 'Шаблон компонента для корзины удаленных объектов успешно создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_INSIDE_ADMIN = 'Шаблон компонента для режима редактирования успешно создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_ADMIN_MODE = 'Шаблон компонента для режима администрирования успешно создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_TITLE = 'Шаблон компонента для титульной страницы успешно создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_MOBILE = 'Шаблон компонента для мобильного сайта успешно создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_MULTI_EDIT = 'Шаблон компонента для множественного редактирования успешно создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_RESPONSIVE = 'Шаблон компонента для адаптивного сайта успешно создан';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_OBJECT_PARTIAL = 'Шаблон компонента для страницы объекта успешно создан';

const CONTROL_CLASS_COMPONENT_TEMPLATE_RETURN_TO_SUB = 'Вернуться</a> к настройке раздела';
const CONTROL_CLASS_COMPONENT_TEMPLATE_RETURN_TO_TRASH = 'Вернуться</a> к корзине';
const CONTROL_CLASS_SHOW_AUX = 'Показать системные компоненты';
const CONTROL_CLASS_DEFAULT_CHANGE = 'Компонент по умолчанию можно изменить в настройках сайта.';

const CONTROL_CONTENT_CLASS_SUCCESS_ADD = 'Компонент успешно добавлен';
const CONTROL_CONTENT_CLASS_ERROR_ADD = 'Ошибка добавления компонента';
const CONTROL_CONTENT_CLASS_ERROR_NAME = 'Введите название компонента';
const CONTROL_CONTENT_CLASS_GROUP_ERROR_NAME = 'Название группы не должно начинаться с цифры';
const CONTROL_CONTENT_CLASS_SUCCESS_EDIT = 'Компонент успешно изменен';
const CONTROL_CONTENT_CLASS_ERROR_EDIT = 'Ошибка редактирования компонента';

#TYPE OF DATA
const CLASSIFICATOR_TYPEOFDATA_STRING = 'Строка';
const CLASSIFICATOR_TYPEOFDATA_INTEGER = 'Целое число';
const CLASSIFICATOR_TYPEOFDATA_TEXTBOX = 'Текстовый блок';
const CLASSIFICATOR_TYPEOFDATA_LIST = 'Список';
const CLASSIFICATOR_TYPEOFDATA_BOOLEAN = 'Логическая переменная (истина или ложь)';
const CLASSIFICATOR_TYPEOFDATA_FILE = 'Файл';
const CLASSIFICATOR_TYPEOFDATA_FLOAT = 'Число с плавающей запятой';
const CLASSIFICATOR_TYPEOFDATA_DATETIME = 'Дата и время';
const CLASSIFICATOR_TYPEOFDATA_RELATION = 'Связь с другим объектом';
const CLASSIFICATOR_TYPEOFDATA_MULTILIST = 'Множественный выбор';
const CLASSIFICATOR_TYPEOFDATA_MULTIFILE = 'Множественная загрузка файлов';

const CLASSIFICATOR_TYPEOFFILESYSTEM = 'Тип файловой системы';

const CLASSIFICATOR_TYPEOFEDIT_ALL = 'Доступно всем';
const CLASSIFICATOR_TYPEOFEDIT_ADMINS = 'Доступно только администраторам';
const CLASSIFICATOR_TYPEOFEDIT_NOONE = 'Недоступно никому';

const CLASSIFICATOR_TYPEOFMODERATION_RIGHTAWAY = 'После добавления';
const CLASSIFICATOR_TYPEOFMODERATION_MODERATION = 'После проверки администратором';

const CLASSIFICATOR_USERGROUP_ALL = 'Все';
const CLASSIFICATOR_USERGROUP_REGISTERED = 'Зарегистрированные';
const CLASSIFICATOR_USERGROUP_AUTHORIZED = 'Уполномоченные';

const CONTROL_TEMPLATE_CLASSIFICATOR = 'Экранирование спецсимволов';
const CONTROL_TEMPLATE_CLASSIFICATOR_EKRAN = 'Экранировать';
const CONTROL_TEMPLATE_CLASSIFICATOR_RES = 'Результат';

const CONTROL_FIELD_LIST_NAME = 'Название поля';
const CONTROL_FIELD_LIST_NAMELAT = 'Название поля (латинскими буквами)';
const CONTROL_FIELD_LIST_DESCRIPTION = 'Описание';
const CONTROL_FIELD_LIST_ADD = 'Добавить поле';
const CONTROL_FIELD_LIST_CHANGE = 'Сохранить изменения';
const CONTROL_FIELD_LIST_DELETE = 'Удалить поле';
const CONTROL_FIELD_ADDING = 'Добавление поля';
const CONTROL_FIELD_EDITING = 'Редактирование поля';
const CONTROL_FIELD_DELETING = 'Удаление поля';
const CONTROL_FIELD_FIELDS = 'Поля';
const CONTROL_FIELD_LIST_NONE = 'В данном компоненте нет ни одного поля.';
const CONTROL_FIELD_ONE_FORMAT = 'Формат';
const CONTROL_FIELD_ONE_FORMAT_NONE = 'нет';
const CONTROL_FIELD_ONE_FORMAT_EMAIL = 'email';
const CONTROL_FIELD_ONE_FORMAT_URL = 'URL';
const CONTROL_FIELD_ONE_FORMAT_HTML = 'HTML-строка';
const CONTROL_FIELD_ONE_FORMAT_PASSWORD = 'пароль';
const CONTROL_FIELD_ONE_FORMAT_PHONE = 'телефон';
const CONTROL_FIELD_ONE_FORMAT_TAGS = 'тэги';
const CONTROL_FIELD_ONE_PROTECT_EMAIL = 'Защищать при выводе';
const CONTROL_FIELD_ONE_EXTENSION = 'Связанное поле';
const CONTROL_FIELD_ONE_MUSTBE = 'обязательно для заполнения';
const CONTROL_FIELD_ONE_INDEX = 'возможен поиск по данному полю';
const CONTROL_FIELD_ONE_IN_TABLE_VIEW = 'использовать в табличном списке объектов';
const CONTROL_FIELD_ONE_INHERITANCE = 'наследовать значение поля';
const CONTROL_FIELD_ONE_DEFAULT = 'Значение по умолчанию (устанавливается при записи, если поле не было заполнено)';
define("CONTROL_FIELD_ONE_DEFAULT_NOTE", "для всех типов полей кроме &quot;".CLASSIFICATOR_TYPEOFDATA_TEXTBOX."&quot;, &quot;".CLASSIFICATOR_TYPEOFDATA_FILE."&quot;, &quot;".CLASSIFICATOR_TYPEOFDATA_DATETIME."&quot;, &quot;".CLASSIFICATOR_TYPEOFDATA_MULTILIST."&quot;");
const CONTROL_FIELD_ONE_FTYPE = 'Тип поля';
const CONTROL_FIELD_ONE_ACCESS = 'Тип доступа к полю';
const CONTROL_FIELD_ONE_RESERVED = 'Данное название поля зарезервировано!';
const CONTROL_FIELD_NAME_ERROR = 'Название поля должно содержать только латинские буквы и цифры!';
const CONTROL_FIELD_DIGIT_ERROR = 'Название поля не может начинаться с цифры.';
const CONTROL_FIELD_DB_ERROR = 'Ошибка записи в БД.';
const CONTROL_FIELD_EXITS_ERROR = 'Такое поле уже существует.';
const CONTROL_FIELD_FORMAT_ERROR = 'Такой формат поля не допустим.';
const CONTROL_FIELD_MSG_ADDED = 'Поле добавлено успешно.';
const CONTROL_FIELD_MSG_EDITED = 'Поле успешно изменено.';
const CONTROL_FIELD_MSG_DELETED_ONE = 'Поле успешно удалено.';
const CONTROL_FIELD_MSG_DELETED_MANY = 'Поле успешно удалено.';
const CONTROL_FIELD_MSG_CONFIRM_REMOVAL_ONE = 'Внимание: поле будет удалено.';
const CONTROL_FIELD_MSG_CONFIRM_REMOVAL_MANY = 'Внимание: поля будут удалены.';
const CONTROL_FIELD_MSG_FIELDS_CHANGED = 'Приоритеты полей изменены.';
const CONTROL_FIELD_CONFIRM_REMOVAL = 'Подтвердить удаление';
const CONTROL_FIELD__EDITOR_EMBED_TO_FIELD = 'встроить редактор в поле для редактирования';
const CONTROL_FIELD__TEXTAREA_SIZE = 'Размер текстового блока';
const CONTROL_FIELD_HEIGHT = 'высота';
const CONTROL_FIELD_WIDTH = 'ширина';
const CONTROL_FIELD_ATTACHMENT = 'закачиваемый';
const CONTROL_FIELD_DOWNLOAD_COUNT = 'считать количество скачиваний';
const CONTROL_FIELD_CAN_BE_AN_ICON = 'может быть иконкой';
const CONTROL_FIELD_CAN_BE_ONLY_ICON = 'только иконкой';
const CONTROL_FIELD_PANELS = 'Использовать набор панелей CKEditor';
const CONTROL_FIELD_PANELS_DEFAULT = 'По умолчанию';
const CONTROL_FIELD_TYPO = 'типографировать';
const CONTROL_FIELD_TYPO_BUTTON = 'Типографировать текст';
const CONTROL_FIELD_BBCODE_ENABLED = 'разрешить bb-коды';
const CONTROL_FIELD_USE_CALENDAR = 'использовать календарь для выбора даты';
const CONTROL_FIELD_FILE_UPLOADS_LIMITS = 'Ваша конфигурация PHP имеет следующие ограничения на загрузку файлов:';
const CONTROL_FIELD_FILE_POSTMAXSIZE = 'максимально допустимый размер данных, отправляемых методом POST';
const CONTROL_FIELD_FILE_UPLOADMAXFILESIZE = 'максимальный размер закачиваемого файла';
const CONTROL_FIELD_FILE_MAXFILEUPLOADS = 'разрешенное количество одновременно закачиваемых файлов';
const CONTROL_FIELD_MULTIFIELD_USE_IMAGE_RESIZE = 'Использовать уменьшение изображений';
const CONTROL_FIELD_MULTIFIELD_USE_IMAGE_CROP = 'Использовать обрезку изображений';
const CONTROL_FIELD_MULTIFIELD_CROP_IGNORE = 'Не обрезать, если изображение меньше указанного размера';
const CONTROL_FIELD_MULTIFIELD_USE_IMAGE_PREVIEW = 'Создавать картинку-предпросмотр(превью)';
const CONTROL_FIELD_MULTIFIELD_USE_PREVIEW_RESIZE = 'Использовать уменьшение превью';
const CONTROL_FIELD_MULTIFIELD_PREVIEW_USE_IMAGE_CROP = 'Использовать обрезку превью';
const CONTROL_FIELD_MULTIFIELD_PREVIEW_CROP_IGNORE = 'Не обрезать, если превью меньше указанного размера';
const CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH = 'Ширина';
const CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT = 'Высота';
const CONTROL_FIELD_MULTIFIELD_CROP_CENTER = 'По центру';
const CONTROL_FIELD_MULTIFIELD_CROP_COORD = 'По координатам';
const CONTROL_FIELD_MULTIFIELD_MIN = 'Минимум';
const CONTROL_FIELD_MULTIFIELD_MAX = 'Максимум';
const CONTROL_FIELD_MULTIFIELD_MINMAX = 'Ограничить количество файлов доступное для загрузки';
const CONTROL_FIELD_USE_TRANSLITERATION = 'Транслитерация';
const CONTROL_FIELD_TRANSLITERATION_FIELD = 'Поле для записи результата транслитерации';
const CONTROL_FIELD_USE_URL_RULES = 'Использовать правила для URL';
const CONTROL_FIELD_FILE_WRONG_GD = 'На сервере не включено расширение GD2, уменьшение и обрезка изображений работать не будет';

# SYS
const TOOLS_SYSTABLE_SITES = 'Сайты';
const TOOLS_SYSTABLE_SECTIONS = 'Разделы';
const TOOLS_SYSTABLE_USERS = 'Пользователи';
const TOOLS_SYSTABLE_TEMPLATE = 'Макеты дизайна';


#DATABACKUP
const TOOLS_DATA_BACKUP = 'Экспорт/импорт данных';
const TOOLS_DATA_BACKUP_IMPORT_FILE = 'Файл импорта (*.tgz)';
const TOOLS_DATA_BACKUP_IMPORT_COMPLETE = 'Импорт данных завершен!';
const TOOLS_DATA_BACKUP_IMPORT_ERROR = 'Во время импорта данных произошла ошибка!';
const TOOLS_DATA_BACKUP_IMPORT_DUPLICATE_KEY_ERROR = 'Объекты с такими идентификаторами уже существуют.';
const TOOLS_DATA_BACKUP_EXPORT_COMPLETE = 'Экспорт данных завершен!';
define("TOOLS_DATA_BACKUP_INCOMPATIBLE_VERSION",       "Файл импорта имеет формат, который не поддерживается в текущей версии " . CMS_SYSTEM_NAME . ". Пожалуйста, обновите вашу копию системы.");
const TOOLS_DATA_SAVE_IDS = 'Сохранять идентификаторы импортируемых объектов';
const TOOLS_DATA_BACKUP_SYSTEM = 'Системные';
const TOOLS_DATA_BACKUP_DATATYPE = 'Тип данных';
const TOOLS_DATA_BACKUP_INSERT_OBJECTS = 'Добавлено записей в БД';
const TOOLS_DATA_BACKUP_CREATE_TABLES = 'Создано таблиц в БД';
const TOOLS_DATA_BACKUP_COPIED_FILES = 'Добавлено файлов/папок';
const TOOLS_DATA_BACKUP_SKIPPED_FILES = 'Пропущено файлов/папок';
const TOOLS_DATA_BACKUP_REPLACED_FILES = 'Заменено файлов/папок';
const TOOLS_DATA_BACKUP_EXPORT_DATE = 'Дата экспорта';
const TOOLS_DATA_BACKUP_USED_SPACE = 'использовано';
const TOOLS_DATA_BACKUP_SPACE_FROM = 'из';

const TOOLS_DATA_BACKUP_DELETE_ALL_CONFIRMATION = 'Удалить все файлы?';

const TOOLS_EXPORT = 'Экспорт';
const TOOLS_IMPORT = 'Импорт';
const TOOLS_DOWNLOAD = 'Загрузить';
const TOOLS_DATA_BACKUP_GOTO_OBJECT = 'Перейти к импортированному объекту';


const TOOLS_MODULES = 'Модули';
const TOOLS_MODULES_LIST = 'Список модулей';
const TOOLS_MODULES_INSTALLEDMODULE = 'Установлен модуль';
const TOOLS_MODULES_ERR_INSTALL = 'Установка модуля невозможна';
const TOOLS_MODULES_ERR_UNINSTALL = 'Удаление модуля невозможно';
const TOOLS_MODULES_ERR_CANTOPEN = 'Невозможно открыть файл';
const TOOLS_MODULES_ERR_PATCH = 'Не установлен необходимый патч с номером';
const TOOLS_MODULES_ERR_VERSION = 'Модуль не для существующей версии';
const TOOLS_MODULES_ERR_INSTALLED = 'Модуль уже установлен';
const TOOLS_MODULES_ERR_ITEMS = 'Ошибка: выполнены не все необходимые условия';
const TOOLS_MODULES_ERR_DURINGINSTALL = 'Ошибка при инсталляции';
const TOOLS_MODULES_ERR_NOTUPLOADED = 'Файл не закачан';
define("TOOLS_MODULES_ERR_EXTRACT", "Ошибка при распаковке архива c модулем.<br />Попробуйте распаковать содержимое архива с модулем в папку $nc_core->TMP_FOLDER на Вашем сервере и снова запустить процедуру установки модуля.");

const TOOLS_MODULES_MOD_NAME = 'Название модуля';
const TOOLS_MODULES_MOD_PREFS = 'Настройки';
const TOOLS_MODULES_MOD_GOINSTALL = 'Завершить установку';
const TOOLS_MODULES_MOD_EDIT = 'изменить параметры модуля';
const TOOLS_MODULES_MOD_LOCAL = 'Установка модуля с локального диска';
const TOOLS_MODULES_MOD_INSTALL = 'Установка модуля';
const TOOLS_MODULES_MSG_CHOISESECTION = 'Для завершения установки модуля необходимо создать дополнительные разделы. Вам необходимо выбрать родительский раздел, где будут созданы необходимые подразделы.';
const TOOLS_MODULES_PREFS_SAVED = 'Настройки модулей сохранены';
const TOOLS_MODULES_PREFS_ERROR = 'Ошибка во время сохранения настроек модуля';

# PATCH
const TOOLS_PATCH = 'Обновление системы';
const TOOLS_PATCH_INSTRUCTION_TAB = 'Инструкция';
const TOOLS_PATCH_INSTRUCTION = 'Инструкция по установке обновления';
const TOOLS_PATCH_CHEKING = 'Проверка наличия новых обновлений';
const TOOLS_PATCH_MSG_OK = 'Все необходимые обновления установлены.';
define("TOOLS_PATCH_MSG_NOCONNECTION", "Не удалось соединиться с сервером обновлений." . (CMS_SYSTEM_NAME === 'Netcat' ? " О наличии новых обновлений вы можете узнать на <a href='https://partners.netcat.ru/forclients/update/' target='_blank'>нашем сайте</a>." : ''));
const TOOLS_PATCH_ERR_CANTINSTALL = 'Инсталляция патча невозможна.';
const TOOLS_PATCH_INSTALL_LOCAL = 'Установка обновления с локального диска';
const TOOLS_PATCH_INSTALL_ONLINE = 'Установка обновления с официального сайта';
const TOOLS_PATCH_INFO_NOTINSTALLED = 'Не установлено обновление';
const TOOLS_PATCH_INFO_LASTCHECK = 'Последняя проверка была осуществлена';
const TOOLS_PATCH_INFO_REFRESH = 'обновить сведения';
const TOOLS_PATCH_INFO_DOWNLOAD = 'скачать';
define("TOOLS_PATCH_ERR_EXTRACT", "Ошибка при распаковке архива c обновлением.<br />Попробуйте распаковать содержимое архива с обновлением в папку $nc_core->TMP_FOLDER на Вашем сервере и снова запустить процедуру обновления.");
const TOOLS_PATCH_ERROR_TMP_FOLDER_NOT_WRITABLE = 'Установите права на запись для папки %s.<br />(%s недоступна для записи)';
const TOOLS_PATCH_ERROR_FILELIST_NOT_WRITABLE = 'Некоторые файлы, требующие обновления, нельзя будет автоматически изменить.';
const TOOLS_PATCH_ERROR_AUTOINSTALL = 'Автоматическая установка обновления невозможна, установите обновление вручную, согласно прилагающейся документации или документации на сайте.';
define("TOOLS_PATCH_ERROR_UPDATE_SERVER_NOT_AVAILABLE", "Не удалось соединиться с сервером обновлений, повторите попытку позже.<br />" .
    "Если доступ в глобальную сеть должен осуществляться через прокси-сервер, " .
    "<a href='{$nc_core->ADMIN_PATH}#system.edit' target='_top'>проверьте его настройки</a>.");
const TOOLS_PATCH_ERROR_UPDATE_FILE_NOT_AVAILABLE = 'Файл обновления не может быть получен, повторите попытку позже. Если ошибка повторится, обратитесь в службу поддержки.';
const TOOLS_PATCH_DOWNLOAD_LINK_DESCRIPTION = 'Ссылка на файл обновления';
const TOOLS_PATCH_IS_WRITABLE = 'Доступ на запись';

# patch after install information
const TOOLS_PATCH_INFO_FILES_COPIED = '[%COUNT] файлов скопировано.';
const TOOLS_PATCH_INFO_QUERIES_EXEC = '[%COUNT] MySQL запросов выполненно.';
const TOOLS_PATCH_INFO_SYMLINKS_EXEC = '[%COUNT] символических ссылок создано.';

const TOOLS_PATCH_LIST_DATE = 'Дата установки';
const TOOLS_PATCH_ERROR = 'Ошибка';
const TOOLS_PATCH_ERROR_DURINGINSTALL = 'Ошибка при инсталляции';
const TOOLS_PATCH_INSTALLED = 'Патч установлен';
const TOOLS_PATCH_INVALIDVERSION = 'Патч не предназначен для используемой версии системы, текущая версия %EXIST, патч для версии %REQUIRE.';
const TOOLS_PATCH_ALREDYINSTALLED = 'Патч уже установлен';

const TOOLS_PATCH_NOTAVAIL_DEMO = 'Не доступно в демо-версии';
const NETCAT_DEMO_NOTICE = 'Система управления сайтами Netcat %s DEMO';
const NETCAT_PERSONAL_MODULE_DESCRIPTION = "Возможность подключения дополнительных модулей существует только в полноценной версии.<br />
    Оценить функционал недостающего Вам модуля Вы можете путем скачивания той редакции, где он представлен.<br />
    <a href='https://netcat.ru/products/editions/compare/' target='_blank'>Таблица</a> сравнения редакций. ";

#UPGRADE
const TOOLS_UPGRADE_ERR_NO_PRODUCTNUMBER = 'В системе отсутствует номер лицензии';
const TOOLS_UPGRADE_ERR_INVALID_PRODUCTNUMBER = 'Номер не прошёл проверку на достоверность. Перепроверьте правильность номера вашей лицензии';
const TOOLS_UPGRADE_ERR_NO_MATCH_HOST = 'Используемый в системе ключ активации не прошёл проверку. Подлинность системы на данном домене не установлена.';
const TOOLS_UPGRADE_ERR_NO_ORDER = 'Для данной лицензии не поступало заказа для перехода системы на старшую редакцию.';
const TOOLS_UPGRADE_ERR_NOT_PAID = 'Заказ на переход системы на старшую редакцию не оплачен на netcat.ru.';

#ACTIVATION
const TOOLS_ACTIVATION = 'Активация системы';
const TOOLS_ACTIVATION_INSTRUCTION = 'Инструкция активация системы';
const TOOLS_ACTIVATION_VERB = 'Активировать';
const TOOLS_ACTIVATION_OK = 'Активация прошла успешно';
const TOOLS_ACTIVATION_OK1 = "Активация прошла успешно. Осталось совсем чуть-чуть!<br /><ul style='list-style-type:none'>";
const TOOLS_ACTIVATION_OK2 = "<li>- <a href='https://netcat.ru/' target='_blank'>зарегистрируйтесь</a> на сайте netcat.ru</li>";
const TOOLS_ACTIVATION_OK3 = "<li>- <a href='https://netcat.ru/' target='_blank'>войдите в ваш аккаунт</a> на сайте netcat.ru</li>";
const TOOLS_ACTIVATION_OK4 = "<li>- <a href='https://netcat.ru/forclients/want/zaregistrirovat-litsenziyu/?f_RegNum=%REGNUM&f_code=%REGCODE&f_SystemName=%SYSID' target='_blank'>привяжите лицензию</a> к вашему аккаунту, указав следующие данные:
    <ul style='list-style-type:none'><li>Номер лицензии: %REGNUM</li>
    <li>Ключ активации: %REGCODE</li></ul></li></ul>
    Это необходимо для техподдержки (если она вам понадобится), получения важных сообщений, продления техподдержки и обновления системы до актуальных версий.<br /><br />
    И спасибо, что вы выбрали Неткэт!";
const TOOLS_ACTIVATION_OWNER = 'Владелец лицензии';
const TOOLS_ACTIVATION_LICENSE = 'Номер лицензии';
const TOOLS_ACTIVATION_CODE = 'Ключ активации';
const TOOLS_ACTIVATION_ALREADY_ACTIVE = 'Система уже активирована';
const TOOLS_ACTIVATION_INPUT_KEY_CODE = 'Необходимо ввести номер лицензии и ключ активации';
const TOOLS_ACTIVATION_INVALID_KEY_CODE = 'Лицензия или ключ активации не прошли проверку';
const TOOLS_ACTIVATION_DAY = 'Срок действия демо-версии истекает через %DAY дн.';
const TOOLS_ACTIVATION_FORM = "Для активации системы Вам нужно ввести номер лицензии и ключ активации, которые Вы получите после <a href='https://netcat.ru/products/editions/' target='_blank'>покупки</a>";
const TOOLS_ACTIVATION_DESC = 'В полноценной версии:
<ul>
<li> открый код;</li>
<li> неограниченный срок действия лицензии;</li>
<li> возможность дополнять редакцию необходимым функционалом путем перехода на другие редакции;</li>
<li> автоматическая установка обновлений;</li>
<li>годовая бесплатная оперативная техническая поддержка.</li>
</ul>';
//const TOOLS_ACTIVATION_DEMO_DISABLED = 'Возможность обновления существует только в полноценной версии.<br />';
define("TOOLS_ACTIVATION_REMIND_UNCOMPLETED", "Введены данные о лицензии. Завершите процесс активации в разделе «<a href='%s'>Активация системы</a>».");
const TOOLS_ACTIVATION_LIC_DATA = '<h3>Реквизиты лицензии</h3>';
const TOOLS_ACTIVATION_LIC_OWNER = '<h3>Владелец лицензии</h3>';

const TOOLS_ACTIVATION_FORM_ERR_MANDATORY = 'Заполните все необходимые поля';
const TOOLS_ACTIVATION_FORM_ERR_ORG_EMAIL = 'Неверный формат email организации';
const TOOLS_ACTIVATION_FORM_ERR_PERSON_EMAIL = 'Неверный формат email контактного лица';
const TOOLS_ACTIVATION_FORM_ERR_PRIMARY_EMAIL = 'Неверный формат email';
const TOOLS_ACTIVATION_FORM_ERR_ADDIT_EMAIL = 'Неверный формат дополнительного email';
const TOOLS_ACTIVATION_FORM_ERR_INN = 'ИНН должен содержать 10 или 12 цифр';

const TOOLS_ACTIVATION_PLEASE_CHECK = "<p style='color: #ce655d;'>Внимание! Лицензию необходимо регистрировать на конечного пользователя - владельца сайта.<br />Если вы подрядчик или сотрудник компании-владельца, укажите реквизиты реального владельца/заказчика. Изменить владельца лицензии после активации невозможно.</p>";
const TOOLS_ACTIVATION_FLD_OWNER = 'Владелец лицензии';
const TOOLS_ACTIVATION_FLD_PHIS = 'Физическое лицо';
const TOOLS_ACTIVATION_FLD_UR = 'Юридическое лицо';
const TOOLS_ACTIVATION_FLD_NAME = 'ФИО';
const TOOLS_ACTIVATION_FLD_PHIS_PHONE = 'Контактный телефон';
const TOOLS_ACTIVATION_FLD_PRIMARY_EMAIL = 'Email';
const TOOLS_ACTIVATION_FLD_ADDIT_EMAIL = 'Дополнительный email';
const TOOLS_ACTIVATION_FLD_ORGANIZATION = 'Название организации';
const TOOLS_ACTIVATION_FLD_INN = 'ИНН';
const TOOLS_ACTIVATION_FLD_ORG_EMAIL = 'Email организации';
const TOOLS_ACTIVATION_FLD_PHONE = 'Телефон компании';
const TOOLS_ACTIVATION_FLD_DOMAINS = 'Домены лицензии (включая тестовый, через запятую)';

const REPORTS = 'Общая статистика проекта';
const REPORTS_SECTIONS = '%d раздел(ов) (выключено: %d)';
const REPORTS_USERS = '%d пользователей (выключено: %d)';
const REPORTS_LAST_NAME = 'Название раздела';
const REPORTS_CLASS = 'Статистика компонентов';
const REPORTS_STAT_CLASS_SHOW = 'Показать компоненты';
const REPORTS_STAT_CLASS_ALL = 'Все';
const REPORTS_STAT_CLASS_DOGET = 'Выбрать';
const REPORTS_STAT_CLASS_CLEAR = 'Очистить';
const REPORTS_STAT_CLASS_CLEARED = 'Объекты удалены';
const REPORTS_STAT_CLASS_CONFIRM = 'Подвердите удаление объектов из компонентов раздела';
const REPORTS_STAT_CLASS_CONFIRM_OK = 'Далее';
const REPORTS_STAT_CLASS_NOT_CC = 'Не выбраны компоненты в разделе';
const REPORTS_STAT_CLASS_USE = 'Используемые';
const REPORTS_STAT_CLASS_NOTUSE = 'Неиспользуемые';

const REPORTS_SYSMSG_MSG = 'Сообщение';
const REPORTS_SYSMSG_DATE = 'Дата';
const REPORTS_SYSMSG_NONE = 'Нет ни одного системного сообщения.';
const REPORTS_SYSMSG_MARK = 'Пометить как прочитанное';
const REPORTS_SYSMSG_TOTAL = 'Всего';
const REPORTS_SYSMSG_BACK = 'Вернуться к списку';

const SUPPORT = 'Обращение в техподдержку';
const SUPPORT_HELP_MESSAGE = "
Техническая поддержка доступна только зарегистрированным пользователям системы Netcat.<br />
Для того, чтобы обратиться в техподдержку:
<ol>
 <li style='padding-bottom:10px'><a target=_blank href='https://netcat.ru/forclients/my/copies/add_copies.html'>Зарегистрируйте Вашу копию системы</a>.
 <li style='padding-bottom:10px'>После проверки введенных Вами данных Вы можете создавать и отслеживать обращения<br> в техническую поддержку
   на странице «<a target=_blank href='https://netcat.ru/forclients/support/tickets/'>Поддержка онлайн</a>».
 </li>
</ol>
";

const TOOLS_SQL = 'Командная строка SQL';
const TOOLS_SQL_ERR_NOQUERY = 'Введите запрос!';
const TOOLS_SQL_SEND = 'Отправить запрос';
const TOOLS_SQL_OK = 'Запрос выполнен успешно';
const TOOLS_SQL_TOTROWS = 'Строк, соответствующих запросу';
const TOOLS_SQL_HELP = 'Примеры запросов';
const TOOLS_SQL_HISTORY = 'Последние 15 запросов';
const TOOLS_SQL_HELP_EXPLAIN = 'показать список полей из таблицы %s';
const TOOLS_SQL_HELP_SELECT = 'показать количество строк в таблице %s';
const TOOLS_SQL_HELP_SHOW = 'показать список таблиц';
const TOOLS_SQL_HELP_DOCS = "С подробной документацией по БД MySQL вы можете ознакомиться по адресу:<br>\n<a target=_blank href=http://dev.mysql.com/doc/refman/5.1/en/>http://dev.mysql.com/doc/refman/5.1/en/</a>";
const TOOLS_SQL_BENCHMARK = 'Время выполнения запроса';
const TOOLS_SQL_ERR_OPEN_FILE = 'Не удалось открыть sql-файл: %s';
const TOOLS_SQL_ERR_FILE_QUERY = 'Неудачное выполнение запроса в файле %s MySQL ошибка: %s';

const NETCAT_TRASH_SIZEINFO = 'На данный момент в корзине - %s. <br />Лимит корзины - %s МБ.';
const NETCAT_TRASH_NOMESSAGES = 'Корзина пуста.';
const NETCAT_TRASH_MESSAGES_SK1 = 'объект';
const NETCAT_TRASH_MESSAGES_SK2 = 'объектов';
const NETCAT_TRASH_MESSAGES_SK3 = 'объекта';
const NETCAT_TRASH_RECOVERED_SK1 = 'Восстановлен';
const NETCAT_TRASH_RECOVERED_SK2 = 'Восстановлено';
const NETCAT_TRASH_RECOVERED_SK3 = 'Восстановлено';
const NETCAT_TRASH_RECOVERY = 'Восстановить';
const NETCAT_TRASH_DELETE_FROM_TRASH = 'Удалить из корзины';
const NETCAT_TRASH_OBJECT_WERE_DELETED_TRASHBIN_FULL = 'Объекты не были помещены в корзину, так как она заполнена';
const NETCAT_TRASH_OBJECT_IN_TRASHBIN_AND_CANCEL = "Объекты перемещены в <a href='%s'>корзину</a>. <a href='%s'>Отменить</a>";
const NETCAT_TRASH_TRASHBIN_DISABLED = 'Корзина удаленных объектов выключена';
const NETCAT_TRASH_EDIT_SETTINGS = 'Изменить настройки';
const NETCAT_TRASH_OBJECT_NOT_FOUND = 'Не найдено объектов, удовлетворяющих выборке';
const NETCAT_TRASH_TRASHBIN = 'Корзина';
const NETCAT_TRASH_PRERECOVERYSUB_INFO = 'Некоторые из восстанавливаемых объектов находились в разделах, которых сейчас уже нет. Netcat может восстановить эти разделы с теми параметрами, которые были на момент удаления объектов. Вы можете помнять эти свойства.';
const NETCAT_TRASH_PRERECOVERYSUB_CHECKED = 'включен';
const NETCAT_TRASH_PRERECOVERYSUB_NAME = 'Название';
const NETCAT_TRASH_PRERECOVERYSUB_KEYWORD = 'Ключевое слово';
const NETCAT_TRASH_PRERECOVERYSUB_PARENT = 'Родительский раздел';
const NETCAT_TRASH_PRERECOVERYSUB_ROOT = 'Корневой раздел сайта';
const NETCAT_TRASH_PRERECOVERYSUB_NEXT = 'Далее';
const NETCAT_TRASH_FILTER = 'Выборка удаленных объектов';
const NETCAT_TRASH_FILTER_DATE_FROM = 'Дата удаления с';
const NETCAT_TRASH_FILTER_DATE_TO = 'Дата удаления по';
const NETCAT_TRASH_FILTER_DATE_FORMAT = 'дд-мм-гггг чч:мм';
const NETCAT_TRASH_FILTER_SUBDIVISION = 'Раздел';
const NETCAT_TRASH_FILTER_COMPONENT = 'Компонент';
const NETCAT_TRASH_FILTER_ALL = 'все';
const NETCAT_TRASH_FILTER_APPLY = 'Выбрать';
const NETCAT_TRASH_FILE_DOEST_EXIST = 'Файл %s не найден';
const NETCAT_TRASH_FOLDER_FAIL = 'Директория %s не существует или не доступна для записи';
const NETCAT_TRASH_ERROR_RELOAD_PAGE = "Обнаружены ошибки. Необходимо <a href='index.php'>перезагрузить страницу</a>";
const NETCAT_TRASH_TRASHBIN_IS_FULL = 'Корзина переполнена';
const NETCAT_TRASH_TEMPLATE_DOESNT_EXIST = 'У данного компонента нет шаблона для корзины удаленных объектов';
const NETCAT_TRASH_IDENTIFICATOR = 'Идентификатор';
const NETCAT_TRASH_USER_IDENTIFICATOR = 'ID добавившего пользователя';

# USERS
const CONTROL_USER_GROUPS = 'Группы пользователей';
const CONTROL_USER_FUNCS_ALLUSERS = 'все';
const CONTROL_USER_FUNCS_ONUSERS = 'включенные';
const CONTROL_USER_FUNCS_OFFUSERS = 'выключенные';
const CONTROL_USER_FUNCS_DOGET = 'Выбрать';
const CONTROL_USER_FUNCS_VIEWCONTROL = 'Настройка вывода';
const CONTROL_USER_FUNCS_SORTBY = 'Сортировать по полю';
const CONTROL_USER_FUNCS_USER_NUMBER_ON_THE_PAGE = 'пользователей на странице.';
const CONTROL_USER_FUNCS_SORT_ORDER = 'Порядок сортировки';
const CONTROL_USER_FUNCS_SORT_ORDER_ACS = 'По возрастанию';
const CONTROL_USER_FUNCS_SORT_ORDER_DESC = 'По убыванию';
const CONTROL_USER_FUNCS_PREV_PAGE = 'предыдущая страница';
const CONTROL_USER_FUNCS_NEXT_PAGE = 'следующая страница';
const CONTROL_USER_FUNC_CONFIRM_DEL = 'Подтвердите удаление';
const CONTROL_USER_FUNC_CONFIRM_DEL_OK = 'Подтверждаю';
const CONTROL_USER_FUNC_CONFIRM_DEL_NOT_USER = 'Не выбраны пользователи';
const CONTROL_USER_FUNC_GROUP_ERROR = 'Не выбранна группа';
const CONTROL_USER = 'Пользователь';
const CONTROL_USER_FUNCS_EDITACCESSRIGHT = 'Редактировать права доступа';
const CONTROL_USER_ACTIONS = 'Действия';
const CONTROL_USER_RIGHTS_ITEM = 'Сущность';
const CONTROL_USER_RIGHTS_SELECT_ITEM = 'Выберите сущность';
const CONTROL_USER_RIGHTS_TYPE_OF_RIGHT = 'Тип прав';
const CONTROL_USER_RIGHTS = 'Права';
const CONTROL_USER_ERROR_NEWPASS_IS_CURRENT = 'Новый пароль совпадает с текущим!';
const CONTROL_USER_CHANGEPASS = 'сменить пароль';
const CONTROL_USER_EDIT = 'редактировать';
const CONTROL_USER_REG = 'Регистрация пользователя';
const CONTROL_USER_NEWPASSWORD = 'Новый пароль';
const CONTROL_USER_NEWPASSWORDAGAIN = 'Новый пароль еще раз';
const CONTROL_USER_MSG_USERNOTFOUND = 'Не найдено ни одного пользователя, соответствующего Вашему запросу.';
const CONTROL_USER_GROUP = 'Группа';
const CONTROL_USER_GROUP_MEMBERS = 'Участники';
const CONTROL_USER_GROUP_NOMEMBERS = 'Участников нет.';
const CONTROL_USER_GROUP_TOTAL = 'всего';
const CONTROL_USER_GROUPNAME = 'Название группы';
const CONTROL_USER_ERROR_GROUPNAME_IS_EMPTY = 'Название группы не может быть пустым!';
const CONTROL_USER_ADDNEWGROUP = 'Добавить группу';
const CONTROL_USER_CHANGERIGHTS = 'Настроить права доступа';
const CONTROL_USER_NEW_ADDED = 'Пользователь добавлен';
const CONTROL_USER_NEW_NOTADDED = 'Пользователь не добавлен';
const CONTROL_USER_EDITSUCCESS = 'Пользователь успешно изменен';
const CONTROL_USER_REGISTER = 'Регистрация нового пользователя';
const CONTROL_USER_REGISTER_ERROR_NO_LOGIN_FIELD_VALUE = 'Не передано значение для логина';
const CONTROL_USER_REGISTER_ERROR_LOGIN_ALREADY_EXIST = 'Логин уже занят';
const CONTROL_USER_REGISTER_ERROR_LOGIN_INCORRECT = 'Логин содержит недопустимые символы';
const CONTROL_USER_GROUPS_ADD = 'Добавление группы';
const CONTROL_USER_GROUPS_EDIT = 'Редактирование группы';
const CONTROL_USER_ACESSRIGHTS = 'права доступа';
const CONTROL_USER_USERSANDRIGHTS = 'Пользователи и права';
const CONTROL_USER_PASSCHANGE = 'Смена пароля';
const CONTROL_USER_CATALOGUESWITCH = 'Выбор каталога';
const CONTROL_USER_SECTIONSWITCH = 'Выбор раздела';
const CONTROL_USER_TITLE_USERINFOEDIT = 'Редактирование информации о пользователе';
const CONTROL_USER_TITLE_PASSWORDCHANGE = 'Смена пароля пользователю';
const CONTROL_USER_ERROR_EMPTYPASS = 'Пароль не может быть пустым!';
const CONTROL_USER_ERROR_NOTCANGEPASS = 'Пароль не изменен. Ошибка!';
const CONTROL_USER_OK_CHANGEDPASS = 'Пароль успешно изменен.';
const CONTROL_USER_ERROR_RETRY = 'Попробуйте снова!';
const CONTROL_USER_ERROR_PASSDIFF = 'Введенные пароли не совпадают!';
const CONTROL_USER_MAIL = 'Рассылка по базе';
const CONTROL_USER_MAIL_TITLE_COMPOSE = 'Отправление письма';
const CONTROL_USER_MAIL_GROUP = 'Группа пользователей';
const CONTROL_USER_MAIL_ALLGROUPS = 'Все группы';
const CONTROL_USER_MAIL_FROM = 'Отправитель';
const CONTROL_USER_MAIL_BODY = 'Текст письма';
const CONTROL_USER_MAIL_ADDATTACHMENT = 'вложить файл';
const CONTROL_USER_MAIL_SEND = 'Отправить сообщение';
const CONTROL_USER_MAIL_ERROR_EMAILFIELD = 'Не определено поле содержащее Email пользователей.';
const CONTROL_USER_MAIL_OK = 'Письмо отправлено всем пользователям';
const CONTROL_USER_MAIL_ERROR_NOONEEMAIL = 'В указанном поле не найдено ни одного электронного адреса.';
const CONTROL_USER_MAIL_ATTCHAMENT = 'Присоединить файл';
define("CONTROL_USER_MAIL_ERROR_ONE", "Рассылка невозможна, так как в <a href=".$ADMIN_PATH."settings.php?phase=1>системных настройках</a> не указано поле для рассылок.");
define("CONTROL_USER_MAIL_ERROR_TWO", "Рассылка невозможна, так как в <a href=".$ADMIN_PATH."settings.php?phase=1>системных настройках</a> не указано имя отправителя писем.");
define("CONTROL_USER_MAIL_ERROR_THREE", "Рассылка невозможна, так как в <a href=".$ADMIN_PATH."settings.php?phase=1>системных настройках</a> не указан Email отправителя писем.");
const CONTROL_USER_MAIL_ERROR_NOBODY = 'Отсутствует текст письма.';
const CONTROL_USER_MAIL_CHANGE = 'изменить';
const CONTROL_USER_MAIL_CONTENT = 'Содержимое письма';
const CONTROL_USER_MAIL_SUBJECT = 'Тема письма';
const CONTROL_USER_MAIL_RULES = 'Условия рассылки';
const CONTROL_USER_FUNCS_USERSGET = 'Выборка пользователей';
const CONTROL_USER_FUNCS_USERSGET_EXT = 'Расширенный поиск';
const CONTROL_USER_FUNCS_SEARCHEDUSER = 'Найдено пользователей';
const CONTROL_USER_FUNCS_USERCOUNT = 'Всего пользователей';
const CONTROL_USER_FUNCS_ADDUSER = 'Добавить пользователя';
const CONTROL_USER_FUNCS_NORIGHTS = 'Данному пользователю не присвоены права.';
const CONTROL_USER_FUNCS_GROUP_NORIGHTS = 'У данной группы нет прав.';
const CONTROL_USER_RIGHTS_QUOTES = '«%s»';
const CONTROL_USER_RIGHTS_GUESTONE = 'Гость';
const CONTROL_USER_RIGHTS_DIRECTOR = 'Директор';
const CONTROL_USER_RIGHTS_SUPERVISOR = 'Супервизор';
const CONTROL_USER_RIGHTS_SITEADMIN = 'Редактор сайта';
const CONTROL_USER_RIGHTS_CATALOGUEADMINALL = 'Редактор всех сайтов';
const CONTROL_USER_RIGHTS_CONTENTEDITOR = 'Редактор контента сайта';
const CONTROL_USER_RIGHTS_ALLSITESCONTENTEDITOR = 'Редактор контента всех сайтов';
const CONTROL_USER_RIGHTS_SUBDIVISIONADMIN = 'Редактор раздела';
const CONTROL_USER_RIGHTS_CONTENTADMIN = 'Редактор контента сайта';
const CONTROL_USER_RIGHTS_SUBCLASSADMIN = 'Редактор компонента';
const CONTROL_USER_RIGHTS_SUBCLASSADMINS = 'Редактор инфоблока';
const CONTROL_USER_RIGHTS_CLASSIFICATORADMIN = 'Администратор списка';
const CONTROL_USER_RIGHTS_CLASSIFICATORADMINALL = 'Администратор всех списков';
const CONTROL_USER_RIGHTS_MODULE = 'Доступ к модулям';
const CONTROL_USER_RIGHTS_MODULE_ALL = 'полный доступ';
const CONTROL_USER_MODULE = 'Модуль';
const CONTROL_USER_MODULE_ALL = 'Все модули';
const CONTROL_USER_MODULE_SITE = 'на сайте «%s»';
const CONTROL_USER_MODULE_SITE_ANY = 'на всех сайтах';
const CONTROL_USER_RIGHTS_EDITOR = 'Редактор';
const CONTROL_USER_RIGHTS_SUBSCRIBER = 'Подписчик';
const CONTROL_USER_RIGHTS_SUBSCRIBER_OF = 'на рассылку';
const CONTROL_USER_RIGHTS_MODERATOR = 'Управление пользователями';
const CONTROL_USER_RIGHTS_USER_GROUP = 'Управление группами';
const CONTROL_USER_RIGHTS_BAN = 'Ограничение в правах';
const CONTROL_USER_RIGHTS_SITE = 'Ограничение в правах сайта';
const CONTROL_USER_RIGHTS_SITEALL = 'Ограничение в правах на всех сайтах';
const CONTROL_USER_RIGHTS_SUB = 'Ограничение в правах раздела';
const CONTROL_USER_RIGHTS_CC = 'Ограничение в правах компонента';
const CONTROL_USER_RIGHTS_LOAD = 'Загрузка';
const CONTROL_USER_RIGHT_ADDNEWRIGHTS = 'Присвоить права';
const CONTROL_USER_RIGHT_ADDPERM = 'Присвоение права пользователю';
const CONTROL_USER_RIGHT_ADDPERM_GROUP = 'Присвоение права группе';
const CONTROL_USER_FUNCS_FROMCAT = 'на сайте';
const CONTROL_USER_FUNCS_FROMSEC = 'из раздела';
const CONTROL_USER_FUNCS_ADDNEWRIGHTS = 'Присвоить новые права';
const CONTROL_USER_FUNCS_ERR_CANTREMGROUP = 'Не удалось удалить группу %s. Ошибка!';
const CONTROL_USER_SELECTSITE = 'Выберите сайт';
const CONTROL_USER_SELECTSECTION = 'Выберите раздел';
const CONTROL_USER_NOONESECSINSITE = 'В данном сайте нет ни одного раздела.';
const CONTROL_USER_FUNCS_SITE = 'Сайт';
const CONTROL_USER_FUNCS_CLASSINSECTION = 'Инфоблок';
const CONTROL_USER_RIGHTS_UPDATED_OK = 'Права пользователя обновлены.';
const CONTROL_USER_RIGHTS_ERROR_NOSELECTED = 'Не выбрана сущность';
const CONTROL_USER_RIGHTS_ERROR_DATA = 'Ошибка в дате';
const CONTROL_USER_RIGHTS_ERROR_DB = 'Ошибка записи в БД';
const CONTROL_USER_RIGHTS_ERROR_POSSIBILITY = 'Не выбрана возможность';
const CONTROL_USER_RIGHTS_ERROR_NOTSITE = 'Не выбран сайт';
const CONTROL_USER_RIGHTS_ERROR_NOTSUB = 'Не выбран раздел';
const CONTROL_USER_RIGHTS_ERROR_NOTCCINSUB = 'В выбранном разделе нет компонентов';
const CONTROL_USER_RIGHTS_ERROR_NOTTYPEOFRIGHT = 'Не выбран тип прав';
const CONTROL_USER_RIGHTS_ERROR_START = 'Ошибка в дате начала действия права';
const CONTROL_USER_RIGHTS_ERROR_END = 'Ошибка в дате окончания действия права';
const CONTROL_USER_RIGHTS_ERROR_STARTEND = 'Время окончания действия прав не может быть раньше времени начала';
const CONTROL_USER_RIGHTS_ERROR_GUEST = 'Нельзя назначить право «Гость» самому себе';
const CONTROL_USER_RIGHTS_ERROR_NONE_AVAILABLE = 'Права не были сохранены, так как у вас нет этих прав';
const CONTROL_USER_RIGHTS_ERROR_SOME_AVAILABLE = 'Некоторые права не были сохранены: нельзя присвоить или отменить права, которых нет у вас';
const CONTROL_USER_RIGHTS_ERROR_EXISTED = 'Права не были сохранены, так как эти или более широкие права уже присвоены';
const CONTROL_USER_RIGHTS_ADDED = 'Права присвоены';
const CONTROL_USER_RIGHTS_LIVETIME = 'Срок действия';
const CONTROL_USER_RIGHTS_UNLIMITED = 'не ограничен';
const CONTROL_USER_RIGHTS_NONLIMITED = 'без ограничений';
const CONTROL_USER_RIGHTS_LIMITED = 'ограничен';
const CONTROL_USER_RIGHTS_STARTING_OPERATIONS = 'Начало действия';
const CONTROL_USER_RIGHTS_FINISHING_OPERATIONS = 'Конец действия';
const CONTROL_USER_RIGHTS_NOW = 'сейчас';
const CONTROL_USER_RIGHTS_ACROSS = 'через';
const CONTROL_USER_RIGHTS_ACROSS_MINUTES = 'минут';
const CONTROL_USER_RIGHTS_ACROSS_HOURS = 'часов';
const CONTROL_USER_RIGHTS_ACROSS_DAYS = 'дней';
const CONTROL_USER_RIGHTS_ACROSS_MONTHS = 'месяцев';
const CONTROL_USER_RIGHTS_RIGHT = 'Право';
const CONTROL_USER_RIGHTS_CONTROL_ADD = 'добавление';
const CONTROL_USER_RIGHTS_CONTROL_EDIT = 'редактирование пользователей, не имеющих прав в системе';
const CONTROL_USER_RIGHTS_CONTROL_MODERATE = 'модерирование пользователей, у которых есть такие же права в системе';
const CONTROL_USER_RIGHTS_CONTROL_DELETE = 'удаление';
const CONTROL_USER_RIGHTS_CONTROL_ADMIN = 'администрирование (присвоение прав)';
const CONTROL_USER_RIGHTS_CONTROL_HELP = 'Помощь';
const CONTROL_USER_USERS_MOVED_SUCCESSFULLY = 'Пользователи успешно перемещены';
const CONTROL_USER_SELECT_GROUP_TO_MOVE = 'Выберите группы, в которые нужно переместить выбранных пользователей';
const CONTROL_USER_SELECTSITEALL = 'Все сайты';

# SUPPORT USER
const CONTROL_USER_SUPPORT_TITLE = 'Предоставление доступа сотрудникам технической поддержки';
const CONTROL_USER_SUPPORT_SUBMIT = 'Предоставить доступ';
const CONTROL_USER_SUPPORT_GRANTED = 'Доступ предоставлен, сообщите об этом сотруднику поддержки.';
const CONTROL_USER_SUPPORT_WARNING = 'Предоставляйте доступ сотрудникам поддержки только в том случае, если они попросили об этом в разделе поддержки в ответ на ваше обращение.';
const CONTROL_USER_SUPPORT_PERPETUAL = 'У сотрудников поддержки есть бессрочный полный доступ к вашему сайту.';
const CONTROL_USER_SUPPORT_EXPIRES = 'У сотрудников поддержки есть полный доступ к вашему сайту до %s.';
const CONTROL_USER_SUPPORT_MANAGE = 'Управление правами пользователя поддержки';
const CONTROL_USER_SUPPORT_GRANT_FOR = 'Предоставить доступ на';
const CONTROL_USER_SUPPORT_DAYS = 'сутки/суток/суток';
const CONTROL_USER_SUPPORT_ERROR_CREATING = 'Не удалось создать пользователя поддержки.';
const CONTROL_USER_SUPPORT_NO_SSO = 'Автоматическое создание пользователя технической поддержки невозможно, так как на вашем сайте выключен вход с единой учётной записью.';
const CONTROL_USER_SUPPORT_CREATE_MANUALLY = '<a href="%s#user.add" target="_top">Создайте нового пользователя</a> и присвойте ему права супервизора вручную.';

# TEMPLATE
const CONTROL_TEMPLATE = 'Макеты дизайна';
const CONTROL_TEMPLATE_ADD = 'Добавление макета';
const CONTROL_TEMPLATE_EDIT = 'Редактирование макета';
const CONTROL_TEMPLATE_DELETE = 'Удаление макета';
const CONTROL_TEMPLATE_OPT_ADD = 'добавление настройки';
const CONTROL_TEMPLATE_OPT_EDIT = 'редактирование настройки';
const CONTROL_TEMPLATE_ERR_NAME = 'Укажите название макета.';
define("CONTROL_TEMPLATE_INFO_CONVERT", "Настраивая макет дизайна, не забудьте <a href='#' onclick=\"window.open('".$ADMIN_PATH."template/converter.php', 'converter','width=600,height=410,status=no,resizable=yes');\">экранировать спецсимволы</a>.");
const CONTROL_TEMPLATE_TEPL_NAME = 'Название макета';
const CONTROL_TEMPLATE_TEPL_MENU = 'Шаблоны вывода навигации';
const CONTROL_TEMPLATE_TEPL_HEADER = 'Верхняя часть страницы (Header)';
const CONTROL_TEMPLATE_TEPL_FOOTER = 'Нижняя часть страницы (Footer)';
const CONTROL_TEMPLATE_TEPL_CREATE = 'Добавить макет';
const CONTROL_TEMPLATE_NOT_FOUND = 'Макет дизайна с идентификатором %s не найден!';
const CONTROL_TEMPLATE_ERR_USED_IN_SITE = 'Данный макет дизайна используется в следующих сайтах:';
const CONTROL_TEMPLATE_ERR_USED_IN_SUB = 'Данный макет дизайна используется в следующих разделах:';
const CONTROL_TEMPLATE_ERR_CANTDEL = 'Не удалось удалить макет';
const CONTROL_TEMPLATE_INFO_DELETE = 'Вы собираетесь удалить макет';
const CONTROL_TEMPLATE_INFO_DELETE_SOME = 'Эти макеты будут удалены';
const CONTROL_TEMPLATE_DELETED = 'Макет удален';
const CONTROL_TEMPLATE_ADDLINK = 'добавить макет дизайна';
const CONTROL_TEMPLATE_REMOVETHIS = 'удалить этот макет дизайна';
const CONTROL_TEMPLATE_PREF_EDIT = 'изменить настройки';
const CONTROL_TEMPLATE_NONE = 'В системе нет ни одного макета';
const CONTROL_TEMPLATE_TEPL_IMPORT = 'Импорт макета';
const CONTROL_TEMPLATE_IMPORT = 'Импорт макета';
const CONTROL_TEMPLATE_IMPORT_UPLOAD = 'Загрузить';
const CONTROL_TEMPLATE_IMPORT_SELECT = 'Выберите шаблон для импорта (импортируются также дочерние шаблоны)';
const CONTROL_TEMPLATE_IMPORT_CONTINUE = 'Далее';
const CONTROL_TEMPLATE_IMPORT_ERROR_NOTUPLOADED = 'Ошибка импорта макета';
const CONTROL_TEMPLATE_IMPORT_ERROR_SQL = 'Ошибка при добавлении макета в базу данных';
const CONTROL_TEMPLATE_IMPORT_ERROR_EXTRACT = 'Ошибка при извлечении файлов макета %s в директорию %s';
const CONTROL_TEMPLATE_IMPORT_ERROR_MOVE = 'Ошибка копирования файлов из %s в %s';
const CONTROL_TEMPLATE_IMPORT_SUCCESS = 'Макет успешно импортирован';
const CONTROL_TEMPLATE_EXPORT = 'Экспортировать макет в файл';
const CONTROL_TEMPLATE_FILES_PATH = "Файлы макета находятся в папке <a href='%s'>%s</a>";
const CONTROL_TEMPLATE_PARTIALS = 'Врезки';
const CONTROL_TEMPLATE_PARTIALS_NEW = 'Новая врезка';
const CONTROL_TEMPLATE_PARTIALS_ADD = 'Добавить врезку';
const CONTROL_TEMPLATE_PARTIALS_REMOVE = 'Удалить врезку';
const CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD = 'Ключевое слово врезки (латинскими буквами)';
const CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD_ERROR = 'Ключевое слово врезки может содержать только латинские буквы, цифры и знак подчеркивания';
const CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD_REQUIRED_ERROR = 'Ключевое слово врезки обязательно для заполнения';
const CONTROL_TEMPLATE_PARTIALS_DESCRIPTION_FIELD = 'Название';
const CONTROL_TEMPLATE_PARTIALS_ENABLE_ASYNC_LOAD_FIELD = 'разрешить асинхронную загрузку';
const CONTROL_TEMPLATE_PARTIALS_SOURCE_FIELD = 'Шаблон врезки';
const CONTROL_TEMPLATE_PARTIALS_EXISTS_ERROR = 'Врезка с таким ключевым словом уже существует';
const CONTROL_TEMPLATE_PARTIALS_NOT_EXISTS = 'В данном макете нет ни одной врезки';
const CONTROL_TEMPLATE_BASE_TEMPLATE = 'Создать макет дизайна на основе существующего';
const CONTROL_TEMPLATE_BASE_TEMPLATE_CREATE_FROM_SCRATCH = 'Создать макет дизайна «с нуля»';
const CONTROL_TEMPLATE_CONTINUE = 'Продолжить';

const CONTROL_TEMPLATE_KEYWORD = 'Ключевое слово';
const CONTROL_TEMPLATE_KEYWORD_ONLY_DIGITS = 'Ключевое слово не может состоять только из цифр';
const CONTROL_TEMPLATE_KEYWORD_TOO_LONG = 'Длина ключевого слова не может быть более %d символов';
const CONTROL_TEMPLATE_KEYWORD_INVALID_CHARACTERS = 'Ключевое слово может содержать только буквы латинского алфавита, цифры и символы подчёркивания';
const CONTROL_TEMPLATE_KEYWORD_NON_UNIQUE = 'Ключевое слово «%s» уже присвоено макету дизайна «%d. %s»';
const CONTROL_TEMPLATE_KEYWORD_RESERVED = 'Невозможно использовать «%s» в качестве ключевого слова, так как оно является зарезервированным';
const CONTROL_TEMPLATE_KEYWORD_PATH_EXISTS = 'Невозможно использовать «%s» в качестве ключевого слова, так как уже существует папка с таким названием';

# CLASSIFICATORS
const CONTENT_CLASSIFICATORS = 'Списки';
const CONTENT_CLASSIFICATORS_NAMEONE = 'Список';
const CONTENT_CLASSIFICATORS_NAMEALL = 'Все списки';
const CONTENT_CLASSIFICATORS_ELEMENTS = 'элементы';
const CONTENT_CLASSIFICATORS_ELEMENT = 'Элемент';
const CONTENT_CLASSIFICATORS_ELEMENT_NAME = 'Название элемента';
const CONTENT_CLASSIFICATORS_ELEMENT_VALUE = 'Дополнительное значение';
const CONTENT_CLASSIFICATORS_ELEMENTS_ADDONE = 'Добавить элемент';
const CONTENT_CLASSIFICATORS_ELEMENTS_ADD = 'Добавление элемента';
const CONTENT_CLASSIFICATORS_ELEMENTS_ADD_SUCCESS = 'Элемент добавлен';
const CONTENT_CLASSIFICATORS_ELEMENTS_EDIT = 'Редактирование элемента';
const CONTENT_CLASSIFICATORS_LIST_ADD = 'Добавление списка';
const CONTENT_CLASSIFICATORS_LIST_EDIT = 'Редактирование списка';
const CONTENT_CLASSIFICATORS_LIST_CUSTOM = 'Пользовательские настройки';
const CONTENT_CLASSIFICATORS_LIST_DELETE = 'Удаление списка';
const CONTENT_CLASSIFICATORS_LIST_DELETE_SELECTED = 'Удалить выбранные';
const CONTENT_CLASSIFICATORS_ERR_NONE = 'В данном проекте нет ни одного списка.';
const CONTENT_CLASSIFICATORS_ERR_ELEMENTNONE = 'В данном списке нет ни одного элемента.';
const CONTENT_CLASSIFICATORS_ERR_SYSDEL = 'Невозможно удалить элемент из системного классификатора';
const CONTENT_CLASSIFICATORS_ERR_EDITI_GUESTRIGHTS = 'Изменение записи в классификаторе невозможно с гостевыми правами!';
const CONTENT_CLASSIFICATORS_ERROR_NAME = 'Введите название классификатора!';
const CONTENT_CLASSIFICATORS_ERROR_FILE_NAME = 'Выберите CSV-Файл для импортирования!';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORD = 'Введите английское название классификатора (название таблицы)!';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORDINV = 'Английское название (название таблицы) должно содержать только латинские буквы и цифры!';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORDFL = 'Английское название (название таблицы) должно начинаться с латинской буквы!';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORDAE = 'Классификатор с таким английским названием (названием таблицы) уже существует!';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORDREZ = 'Данное имя зарезервировано!';
const CONTENT_CLASSIFICATORS_ADDLIST = 'Добавить список';
const CONTENT_CLASSIFICATORS_ADD_KEYWORD = 'Название таблицы (латинскими буквами)';
const CONTENT_CLASSIFICATORS_SAVE = 'Сохранить изменения';
const CONTENT_CLASSIFICATORS_NO_NAME = '(без названия)';
const CONTENT_CLASSIFICATORS_CUSTOM_BY_DEFAULT = 'по умолчанию:';
const CLASSIFICATORS_SORT_HEADER = 'Тип сортировки';
const CLASSIFICATORS_SORT_PRIORITY_HEADER = 'Приоритет';
const CLASSIFICATORS_SORT_TYPE_ID = 'ID';
const CLASSIFICATORS_SORT_TYPE_NAME = 'Элемент';
const CLASSIFICATORS_SORT_TYPE_PRIORITY = 'Приоритет';
const CLASSIFICATORS_SORT_DIRECTION = 'Направление сортировки';
const CLASSIFICATORS_SORT_ASCENDING = 'Восходящая';
const CLASSIFICATORS_SORT_DESCENDING = 'Нисходящая';
const CLASSIFICATORS_IMPORT_HEADER = 'Импорт списка';
const CLASSIFICATORS_IMPORT_BUTTON = 'Импортировать';
const CLASSIFICATORS_IMPORT_FILE = 'CSV-Файл (*)';
const CLASSIFICATORS_IMPORT_DESCRIPTION = 'Если в импортируемом файле только одна колонка, то она считается полем Элемент, если две - первая колонка это Элемент, а вторая Приоритет.';
const CLASSIFICATORS_SUCCESS_DELETEONE = 'Список успешно удален.';
const CLASSIFICATORS_SUCCESS_DELETE = 'Списки успешно удалены.';
const CLASSIFICATORS_SUCCESS_ADD = 'Список успешно добавлен.';
const CLASSIFICATORS_SUCCESS_EDIT = 'Список успешно изменен.';
const CLASSIFICATORS_ERROR_DELETEONE_SYS = 'Список %s - системный, удаление запрещено.';
const CLASSIFICATORS_ERROR_ADD = 'Ошибка добавления списка.';
const CLASSIFICATORS_ERROR_EDIT = 'Ошибка изменения списка.';

# TOOLS HTML
const TOOLS_HTML = 'HTML-редактор';
const TOOLS_HTML_INFO = 'Редактировать в визуальном редакторе';

const TOOLS_DUMP = 'Архивы проекта';
const TOOLS_DUMP_CREATE = 'Создание архива';
const TOOLS_DUMP_CREATED = 'Архив проекта создан %FILE.';
const TOOLS_DUMP_CREATION_FAILED = 'Ошибка создания архива.';
const TOOLS_DUMP_DELETED = 'Файл %FILE удалён.';
const TOOLS_DUMP_RESTORE = 'Восстановление архива';
const TOOLS_DUMP_MSG_RESTORED = 'Архив восстановлен.';
const TOOLS_DUMP_INC_TITLE = 'Восстановление архива с локального диска';
const TOOLS_DUMP_INC_DORESTORE = 'Восстановить';
const TOOLS_DUMP_INC_DBDUMP = 'дамп базы данных';
const TOOLS_DUMP_INC_FOLDER = 'содержимое папки';
const TOOLS_DUMP_ERROR_CANTDELETE = 'Ошибка! Не могу удалить %FILE.';
const TOOLS_DUMP_INC_ARCHIVE = 'Архив';
const TOOLS_DUMP_INC_DATE = 'Дата';
const TOOLS_DUMP_INC_SIZE = 'Размер';
const TOOLS_DUMP_INC_DOWNLOAD = 'скачать';
const TOOLS_DUMP_NOONE = 'Архивы проекта отсутствуют.';
const TOOLS_DUMP_DATE = 'Дата архива';
const TOOLS_DUMP_SIZE = 'Размер, байт';
const TOOLS_DUMP_CREATEAP = 'Создать архив проекта';
const TOOLS_DUMP_CONFIRM = 'Подтвердите создание архива проекта';
const TOOLS_DUMP_BACKUPLIST_HEADER = 'Имеющиеся архивы проекта';
const TOOLS_DUMP_CREATE_HEADER = 'Создание архива';
const TOOLS_DUMP_CREATE_OPT_FULL = 'Полный архив (включает все файлы, базу данных и скрипт восстановления)';
const TOOLS_DUMP_CREATE_OPT_DATA = 'Архив данных (директории images, netcat_templates, modules, netcat_files и база данных)';
const TOOLS_DUMP_CREATE_OPT_SQL = 'Только база данных';
const TOOLS_DUMP_CREATE_SUBMIT = 'Создать резервную копию';
const TOOLS_DUMP_REMOVE_SELECTED = 'Удалить выбранные архивы';
const TOOLS_DUMP_CREATE_WAIT = 'Производится создание архива. Пожалуйста, подождите.';
const TOOLS_DUMP_RESTORE_WAIT = 'Производится восстановление данных из архива. Пожалуйста, подождите.';
const TOOLS_DUMP_CONNECTION_LOST = 'Потеряна связь с сервером. Если запрошенное действие не было завершено, %s.';
const TOOLS_DUMP_CONNECTION_LOST_SYSTEM_TAR = 'попробуйте разрешить выполнение системной утилиты tar из PHP';
const TOOLS_DUMP_CONNECTION_LOST_INCREASE_PHP_LIMITS = 'проверьте журнал ошибок PHP, и попробуйте увеличить лимит памяти в PHP, таймауты в в конфигурации веб-сервера, а также лимиты использования ресурсов на сервере';
const TOOLS_DUMP_CONNECTION_LOST_INCREASE_SERVER_LIMITS = 'попробуйте увеличить таймауты в в конфигурации веб-сервера и лимиты использования ресурсов на сервере';
const TOOLS_DUMP_CONNECTION_LOST_GO_BACK = 'Вернуться назад';

const TOOLS_REDIRECT = 'Переадресации';
const TOOLS_REDIRECT_OLDURL = 'Старый URL';
const TOOLS_REDIRECT_NEWURL = 'Новый URL';
const TOOLS_REDIRECT_OLDLINK = 'Старая ссылка';
const TOOLS_REDIRECT_NEWLINK = 'Новая ссылка';
const TOOLS_REDIRECT_HEADER = 'Заголовок';
const TOOLS_REDIRECT_HEADERSEND = 'Посылаемый заголовок';
const TOOLS_REDIRECT_SETTINGS = 'Настройки';
const TOOLS_REDIRECT_CHANGEINFO = 'Изменить информацию';
const TOOLS_REDIRECT_NONE = 'В данной группе нет переадресаций.';
const TOOLS_REDIRECT_ADD = 'Добавить переадресацию';
const TOOLS_REDIRECT_EDIT = 'Изменить переадресацию';
const TOOLS_REDIRECT_ADDONLY = 'Добавить';
const TOOLS_REDIRECT_CANTBEEMPTY = 'Поля не могут быть пустыми!';
define("TOOLS_REDIRECT_OLDURL_MUST_BE_UNIQUE", "Уже есть переадресация с такой &quot;старой ссылкой&quot; - <a href='" . nc_core('NETCAT_FOLDER') . "action.php?ctrl=admin.redirect&action=edit&id=%s'>перейти</a>");
const TOOLS_REDIRECT_DISABLED = 'В конфигурационном файле инструмент «Переадресация» выключен.<br/>Чтобы его включть, исправьте в файле vars.inc.php значение параметра $NC_REDIRECT_DISABLED на 0. ';
const TOOLS_REDIRECT_GROUP = 'Группа';
const TOOLS_REDIRECT_GROUP_NAME = 'Название группы';
const TOOLS_REDIRECT_GROUP_ADD = 'Добавить группу';
const TOOLS_REDIRECT_GROUP_EDIT = 'Изменить группу';
const TOOLS_REDIRECT_GROUP_DELETE = 'Удалить группу';
const TOOLS_REDIRECT_BACK = 'Назад';
const TOOLS_REDIRECT_SAVE_OK = 'Переадресация сохранена';
const TOOLS_REDIRECT_GROUP_SAVE_OK = 'Группа сохранена';
const TOOLS_REDIRECT_SAVE_ERROR = 'Ошибка сохранения';
const TOOLS_REDIRECT_DELETE = 'Удалить';
const TOOLS_REDIRECT_DELETE_CONFIRM_REDIRECTS = 'Будут удалены следующие переадресации:';
const TOOLS_REDIRECT_DELETE_CONFIRM_GROUP = 'Будет удалена группа &quot;%s&quot; включая следующие переадресации:';
const TOOLS_REDIRECT_DELETE_OK = 'Удаление выполнено';
const TOOLS_REDIRECT_STATUS = 'Статус';
const TOOLS_REDIRECT_SAVE = 'Сохранить';
const TOOLS_REDIRECT_MOVE = 'Перенести в группу';
const TOOLS_REDIRECT_MOVE_CONFIRM_REDIRECTS = 'Будут перемещены следующие переадресации:';
const TOOLS_REDIRECT_MOVE_OK = 'Перемещение выполнено';
const TOOLS_REDIRECT_NOTHING_SELECTED = 'Не выбрано ни одной переадресации';
const TOOLS_REDIRECT_IMPORT = 'Импорт';
const TOOLS_REDIRECT_FIELDS = 'Поля переадресаций';
const TOOLS_REDIRECT_CONTINUE = 'Продолжить';
const TOOLS_REDIRECT_DO_IMPORT = 'Импортировать';
const TOOLS_REDIRECT_MOVE_TITLE = 'Перемещение переадресаций';
const TOOLS_REDIRECT_DELETE_TITLE = 'Удаление переадресаций';
const TOOLS_REDIRECT_IMPORT_TITLE = 'Импортирование переадресаций';

const TOOLS_CRON = 'Управление задачами';
const TOOLS_CRON_INTERVAL = 'Интервал (м:ч:д)';
const TOOLS_CRON_MINUTES = 'Минуты';
const TOOLS_CRON_HOURS = 'Часы';
const TOOLS_CRON_DAYS = 'Дни';
const TOOLS_CRON_MONTHS = 'Месяцы';
const TOOLS_CRON_LAUNCHED = 'Последний запуск';
const TOOLS_CRON_NEXT = 'Следующая задача';
const TOOLS_CRON_SCRIPTURL = 'Ссылка на скрипт';
const TOOLS_CRON_ADDLINK = 'Добавить задачу';
const TOOLS_CRON_CHANGE = 'Изменить';
const TOOLS_CRON_NOTASKS = 'В данном проекте нет ни одной задачи.';
const TOOLS_CRON_WRONG_DOMAIN = "Некорректно указано доменное имя %s, задачи не будут выполняться, настройте доменное имя в <a href='%s' target='_blank'>настройках сайта</a>";

const TOOLS_COPYSUB = 'Копирование разделов';
const TOOLS_COPYSUB_COPY = 'Копировать';
const TOOLS_COPYSUB_COPY_SUCCESS = 'Копирование успешно выполнено';
const TOOLS_COPYSUB_SOURCE = 'Источник';
const TOOLS_COPYSUB_DESTINATION = 'Приемник';
const TOOLS_COPYSUB_SITE = 'Сайт';
const TOOLS_COPYSUB_SUB = 'Копируемый раздел';
const TOOLS_COPYSUB_KEYWORD_SUB = 'Ключевое слово раздела';
const TOOLS_COPYSUB_TEMPLATE_NAME = 'Шаблоны имён';
const TOOLS_COPYSUB_SETTINGS = 'Параметры копирования';
const TOOLS_COPYSUB_COPY_WITH_CHILD = 'копировать подразделы';
const TOOLS_COPYSUB_COPY_WITH_CC = 'копировать инфоблоки';
const TOOLS_COPYSUB_COPY_WITH_OBJECT = 'копировать объекты';
const TOOLS_COPYSUB_ERROR_KEYWORD_EXIST = 'Раздел с таким ключевым словом уже существует';
const TOOLS_COPYSUB_ERROR_LEVEL_COUNT = 'Нельзя скопировать раздел в сообственный подраздел';
const TOOLS_COPYSUB_ERROR_PARAM = 'Неверные параметры';
const TOOLS_COPYSUB_ERROR_SITE_NOT_FOUND = 'Сайт не найден';

# TOOLS TRASH
const TOOLS_TRASH = 'Корзина удаленных объектов';
const TOOLS_TRASH_CLEAN = 'Очистить корзину';

# MODERATION SECTION
const NETCAT_MODERATION_NO_OBJECTS_IN_SUBCLASS = 'В данном инфоблоке раздела нет данных для вывода.';

const NETCAT_MODERATION_ERROR_NORIGHTS = 'У вас нет доступа для осуществления операции.';
const NETCAT_MODERATION_ERROR_NORIGHT = 'У вас нет прав на эту операцию';
const NETCAT_MODERATION_ERROR_NORIGHTGUEST = 'Гостевое право не позволяет выполнить эту операцию';
const NETCAT_MODERATION_ERROR_NOOBJADD = 'Ошибка добавления объекта.';
const NETCAT_MODERATION_ERROR_NOOBJCHANGE = 'Ошибка изменения объекта.';
const NETCAT_MODERATION_MSG_OBJADD = 'Объект добавлен.';
const NETCAT_MODERATION_MSG_OBJADDMOD = 'Объект будет доступен после проверки администратором.';
const NETCAT_MODERATION_MSG_OBJCHANGED = 'Объект изменен.';
const NETCAT_MODERATION_MSG_OBJDELETED = 'Объект удален.';
const NETCAT_MODERATION_FILES_UPLOADED = 'Закачан';
const NETCAT_MODERATION_FILES_DELETE = 'удалить файл';
const NETCAT_MODERATION_LISTS_CHOOSE = '-- выбрать --';
const NETCAT_MODERATION_RADIO_EMPTY = 'Не отвечать';
const NETCAT_MODERATION_PRIORITY = 'Приоритет объекта';
const NETCAT_MODERATION_TURNON = 'включить';
const NETCAT_MODERATION_DEMO_CONTENT = 'демо-контент';
const NETCAT_MODERATION_OBJADDED = 'Добавление объекта';
const NETCAT_MODERATION_OBJUPDATED = 'Изменение объекта';
const NETCAT_MODERATION_MSG_OBJSDELETED = 'Объекты удалены';
const NETCAT_MODERATION_OBJ_ON = 'вкл';
const NETCAT_MODERATION_OBJ_OFF = 'выкл';
const NETCAT_MODERATION_OBJECT = 'Объект';
const NETCAT_MODERATION_MORE = 'еще';
const NETCAT_MODERATION_MORE_CONTAINER = 'Действия с контейнером...';
const NETCAT_MODERATION_MORE_BLOCK = 'Действия с блоком...';
const NETCAT_MODERATION_MORE_OBJECT = 'Действия с объектом...';
const NETCAT_MODERATION_BLOCK_SETTINGS = 'Настройки блока';
const NETCAT_MODERATION_DELETE_BLOCK = 'Удалить блок';
const NETCAT_MODERATION_ADD_BLOCK = 'Добавить блок';
const NETCAT_MODERATION_ADD_BLOCK_BEFORE = 'до';
const NETCAT_MODERATION_ADD_BLOCK_INSIDE = 'внутрь';
const NETCAT_MODERATION_ADD_BLOCK_AFTER = 'после';
const NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_CONTAINER = 'контейнера';
const NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_CONTAINER = 'контейнера «%s»';
const NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_BLOCK = 'блока «%s»';
const NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_BLOCK = 'этого блока';
const NETCAT_MODERATION_ADD_BLOCK_TITLE = 'Добавление блока';
const NETCAT_MODERATION_ADD_BLOCK_WRAP_TITLE = 'Создание общего контейнера';

const NETCAT_MODERATION_ADD_BLOCK_WRAP = 'Добавляемый блок будет обёрнут в контейнер.';
const NETCAT_MODERATION_NEW_BLOCK_CAPTION = 'Новый блок';

const NETCAT_MODERATION_ADD_BLOCK_WRAP_CONTAINER = 'Новый блок и контейнер «%s» будут помещены в новый контейнер.';
const NETCAT_MODERATION_ADD_BLOCK_WRAP_BLOCK = 'Новый блок и блок «%s» будут помещены в новый контейнер.';
const NETCAT_MODERATION_ADD_OBJECT = 'Добавить';
const NETCAT_MODERATION_ADD_OBJECT_DEFAULT = 'элемент';
const NETCAT_MODERATION_REMOVE_INFOBLOCK_CONFIRMATION_HEADER = 'Удалить безвозвратно?';
const NETCAT_MODERATION_REMOVE_INFOBLOCK_CONFIRMATION_HEADER_CAN_RESTORE = 'Удалить блок?';
const NETCAT_MODERATION_REMOVE_INFOBLOCK_CONFIRMATION_BODY = 'Блок «%s» и его содержимое будут удалены со страницы. Для подтверждения нажмите «Удалить».';
const NETCAT_MODERATION_COMPONENT_SEARCH_BY_NAME = 'поиск по названию';
const NETCAT_MODERATION_CLEAR_FIELD = 'очистить';
const NETCAT_MODERATION_CLEAR_FORM = 'Очистить форму';

const NETCAT_MODERATION_COMPONENT_NO_TEMPLATE = 'Основной шаблон компонента';
const NETCAT_MODERATION_COMPONENT_TEMPLATE = 'Шаблон';
const NETCAT_MODERATION_COMPONENT_TEMPLATES = 'Шаблоны';
const NETCAT_MODERATION_COMPONENT_TEMPLATE_PREV = 'предыдущий шаблон';
const NETCAT_MODERATION_COMPONENT_TEMPLATE_NEXT = 'следующий шаблон';
const NETCAT_MODERATION_COPY_BLOCK = 'Копировать';
const NETCAT_MODERATION_CUT_BLOCK = 'Вырезать';
const NETCAT_MODERATION_PASTE_BLOCK = 'Вставить скопированный (вырезанный) блок';
const NETCAT_MODERATION_CONTAINER = 'Контейнер';
const NETCAT_MODERATION_MAIN_CONTAINER = 'Контентная область';
const NETCAT_MODERATION_ADD_CONTAINER = 'Добавить контейнер';
const NETCAT_MODERATION_REMOVE_IMAGE = 'Удалить изображение';
const NETCAT_MODERATION_REPLACE_IMAGE = 'Заменить изображение';

const NETCAT_MODERATION_WARN_COMMITDELETION = 'Подтвердите удаление объекта #%s';
const NETCAT_MODERATION_WARN_COMMITDELETIONINCLASS = 'Подтвердите удаление объектов инфоблока #%s';

const NETCAT_MODERATION_PASSWORD = 'Пароль (*)';
const NETCAT_MODERATION_PASSWORDAGAIN = 'Введите пароль ещё раз';
const NETCAT_MODERATION_INFO_REQFIELDS = 'Звездочкой (*) отмечены поля, обязательные для заполнения.';
const NETCAT_MODERATION_BUTTON_ADD = 'Добавить';
const NETCAT_MODERATION_BUTTON_CHANGE = 'Сохранить изменения';
const NETCAT_MODERATION_BUTTON_RESET = 'Сброс';

const NETCAT_MODERATION_COMMON_KILLALL = 'Удалить объекты';
const NETCAT_MODERATION_COMMON_KILLONE = 'Удалить объект';

const NETCAT_MODERATION_MULTIFILE_SIZE = 'В поле «%NAME» загружены файлы с размером, превышающим допустимый (%SIZE)';
const NETCAT_MODERATION_MULTIFILE_TYPE = 'В поле «%NAME» загружены файлы недопустимого типа';
const NETCAT_MODERATION_MULTIFILE_MIN_COUNT = 'В поле «%NAME» должно быть загружено не менее %FILES.';
const NETCAT_MODERATION_MULTIFILE_MAX_COUNT = 'В поле «%NAME» может быть загружено не более %FILES.';
const NETCAT_MODERATION_MULTIFILE_COUNT_FILES = 'файла,файлов,файлов';
const NETCAT_MODERATION_MULTIFILE_DEFAULT_CUSTOM_NAME_CAPTION = 'описание файла';
const NETCAT_MODERATION_ADD = 'добавить еще';

const NETCAT_MODERATION_MSG_ONE = 'Поле «%NAME» является обязательным для заполнения.';
const NETCAT_MODERATION_MSG_TWO = 'В поле «%NAME» введено значение недопустимого типа.';
const NETCAT_MODERATION_MSG_SIX = 'Необходимо закачать файл «%NAME».';
const NETCAT_MODERATION_MSG_SEVEN = 'Файл «%NAME» превышает допустимый размер.';
const NETCAT_MODERATION_MSG_EIGHT = 'Недопустимый формат файла «%NAME».';
const NETCAT_MODERATION_MSG_TWENTYONE = 'Введено недопустимое ключевое слово.';
const NETCAT_MODERATION_MSG_RETRYPASS = 'Введенные пароли не совпадают';
const NETCAT_MODERATION_MSG_PASSMIN = 'Пароль слишком короткий. Минимальная длина пароля %s символов.';
const NETCAT_MODERATION_MSG_NEED_AGREED = 'Необходимо согласиться с пользовательским соглашением';
const NETCAT_MODERATION_MSG_LOGINALREADY = 'Логин %s занят другим пользователем';
const NETCAT_MODERATION_MSG_LOGININCORRECT = 'Логин содержит запрещенные символы';
const NETCAT_MODERATION_BACKTOSECTION = 'Вернуться в раздел';

const NETCAT_MODERATION_ISON = 'Включен';
const NETCAT_MODERATION_ISOFF = 'Выключен';
const NETCAT_MODERATION_OBJISON = 'Объект включен';
const NETCAT_MODERATION_OBJISOFF = 'Объект выключен';
const NETCAT_MODERATION_OBJSAREON = 'Объекты включены';
const NETCAT_MODERATION_OBJSAREOFF = 'Объекты выключены';
const NETCAT_MODERATION_CHANGED = 'ID изменившего пользователя';
const NETCAT_MODERATION_CHANGE = 'Изменить';
const NETCAT_MODERATION_DELETE = 'Удалить';
const NETCAT_MODERATION_TURNTOON = 'Включить';
const NETCAT_MODERATION_TURNTOOFF = 'Выключить';
const NETCAT_MODERATION_ID = 'Идентификатор';
const NETCAT_MODERATION_COPY_OBJECT = 'Копировать / перенести';

const NETCAT_MODERATION_REMALL = 'Удалить все';
const NETCAT_MODERATION_DELETESELECTED = 'Удалить выбранные';
const NETCAT_MODERATION_SELECTEDON = 'Включить выбранные';
const NETCAT_MODERATION_SELECTEDOFF = 'Выключить выбранные';
const NETCAT_MODERATION_NOTSELECTEDOBJ = 'Не выбрано ни одного объекта';
const NETCAT_MODERATION_APPLY_CHANGES_TITLE = 'Применить изменения?';
const NETCAT_MODERATION_APPLY_CHANGES_TEXT = 'Вы действительно хотите применить изменения?';
const NETCAT_MODERATION_CLASSID = 'Номер компонента раздела';
const NETCAT_MODERATION_ADDEDON = 'ID добавившего пользователя';

const NETCAT_MODERATION_MOD_NOANSWER = 'не важно';
const NETCAT_MODERATION_MOD_DON = ' до ';
const NETCAT_MODERATION_MOD_FROM = ' от ';
const NETCAT_MODERATION_MODA = '--------- Не важно ---------';

const NETCAT_MODERATION_FILTER = 'Фильтр';
const NETCAT_MODERATION_TITLE = 'Заголовок';
const NETCAT_MODERATION_DESCRIPTION = 'Описание';

const NETCAT_MODERATION_TRASHED_OBJECTS = 'Удаленные объекты';
const NETCAT_MODERATION_TRASHED_OBJECTS_RESTORE = 'Восстановить объект';

const NETCAT_MODERATION_NO_RELATED = '[нет]';
const NETCAT_MODERATION_RELATED_INEXISTENT = '[несуществующий объект ID=%s]';
const NETCAT_MODERATION_CHANGE_RELATED = 'изменить';
const NETCAT_MODERATION_REMOVE_RELATED = 'удалить';
const NETCAT_MODERATION_SELECT_RELATED = 'выбрать';
const NETCAT_MODERATION_COPY_HERE_RELATED = 'Копировать сюда';
const NETCAT_MODERATION_MOVE_HERE_RELATED = 'Переместить сюда';
const NETCAT_MODERATION_CONFIRM_COPY_RELATED = 'Подтвердите действие';
const NETCAT_MODERATION_RELATED_POPUP_TITLE = 'Выбор связанного объекта (поле &quot;%s&quot;)';
const NETCAT_MODERATION_RELATED_NO_CONCRETE_CLASS_IN_SUB = 'В данном разделе нет инфоблоков «%s».';
const NETCAT_MODERATION_RELATED_NO_ANY_CLASS_IN_SUB = 'В данном разделе нет ни одного подходящего инфоблока.';
const NETCAT_MODERATION_RELATED_ERROR_SAVING = 'Не удалось сохранить выбранное значение (возможно, форма редактирования основного объекта была закрыта). Попробуйте выбрать связанное значение еще раз.';
const NETCAT_MODERATION_COPY_SUCCESS = 'Копирование объекта успешно завершено';
const NETCAT_MODERATION_MOVE_SUCCESS = 'Перемещение объекта успешно завершено';


const NETCAT_MODERATION_SEO_TITLE = 'Заголовок страницы (Title)';
const NETCAT_MODERATION_SEO_H1 = 'Заголовок на странице (H1)';
const NETCAT_MODERATION_SEO_KEYWORDS = 'Ключевые слова для поисковиков';
const NETCAT_MODERATION_SEO_DESCRIPTION = 'Описание страницы для поисковиков';

const NETCAT_MODERATION_SMO_TITLE = 'Заголовок для социальных сетей';
const NETCAT_MODERATION_SMO_TITLE_HELPER = 'Станет заголовком статьи при размещении ссылки на страницу в фейсбуке или вконтакте';
const NETCAT_MODERATION_SMO_DESCRIPTION = 'Описание для социальных сетей';
const NETCAT_MODERATION_SMO_DESCRIPTION_HELPER = 'Станет текстом статьи при размещении ссылки на страницу в фейсбуке или вконтакте';
const NETCAT_MODERATION_SMO_IMAGE = 'Изображение для социальных сетей';

const NETCAT_MODERATION_FULL_PAGE_MODE = 'Отображение страницы объекта';
const NETCAT_MODERATION_FULL_PAGE_INHERIT= 'наследовать (%s)';
const NETCAT_MODERATION_FULL_PAGE_TEMPLATE = 'по выбранному шаблону компонента';
const NETCAT_MODERATION_FULL_PAGE_CUSTOM = 'свободная сборка страницы из блоков';
const NETCAT_MODERATION_FULL_PAGE_NONE = 'отсутствует';
const NETCAT_MODERATION_ERROR_FULL_PAGE_CONTEXT = 'Шаблон для страницы полного вывода объекта не может быть использован на страницах другого типа или вне основной контентной области!';

const NETCAT_MODERATION_STANDART_FIELD_USER_ID = 'ID пользователя';
const NETCAT_MODERATION_STANDART_FIELD_USER = 'Пользователь';
const NETCAT_MODERATION_STANDART_FIELD_PRIORITY = 'Приоритет';
const NETCAT_MODERATION_STANDART_FIELD_KEYWORD = 'Ключевое слово';
const NETCAT_MODERATION_STANDART_FIELD_NC_TITLE = 'SEO Meta Title';
const NETCAT_MODERATION_STANDART_FIELD_NC_KEYWORDS = 'SEO Meta Keywords';
const NETCAT_MODERATION_STANDART_FIELD_NC_DESCRIPTION = 'SEO Meta Description';
const NETCAT_MODERATION_STANDART_FIELD_NC_IMAGE = 'Изображение';
const NETCAT_MODERATION_STANDART_FIELD_NC_ICON = 'Иконка';
const NETCAT_MODERATION_STANDART_FIELD_NC_SMO_TITLE = 'SMO Meta Title';
const NETCAT_MODERATION_STANDART_FIELD_NC_SMO_DESCRIPTION = 'SMO Meta Description';
const NETCAT_MODERATION_STANDART_FIELD_NC_SMO_IMAGE = 'SMO Meta Image';
const NETCAT_MODERATION_STANDART_FIELD_IP = 'IP';
const NETCAT_MODERATION_STANDART_FIELD_USER_AGENT = 'Браузер';
const NETCAT_MODERATION_STANDART_FIELD_CREATED = 'Создан';
const NETCAT_MODERATION_STANDART_FIELD_LAST_UPDATED = 'Обновлен';
const NETCAT_MODERATION_STANDART_FIELD_LAST_USER_ID = 'Посл. ID пользователя';
const NETCAT_MODERATION_STANDART_FIELD_LAST_USER = 'Посл. пользователь';
const NETCAT_MODERATION_STANDART_FIELD_LAST_IP = 'Посл. IP';
const NETCAT_MODERATION_STANDART_FIELD_LAST_USER_AGENT = 'Посл. браузер';

const NETCAT_MODERATION_VERSION = 'черновик';
const NETCAT_MODERATION_VERSION_NOT_FOUND = 'черновик отсутствует';
const NETCAT_SAVE_DRAFT = 'Сохранить черновик';
const NETCAT_MODERATION_ADD_BUTTON = "Добавить";
const NETCAT_MODERATION_PREPOSITION_BEHIND = "за";
const NETCAT_MODERATION_PREPOSITION_AT = "на";

# MODULE
const NETCAT_MODULES = 'Модули';
const NETCAT_MODULES_TUNING = 'Настройка модуля';
const NETCAT_MODULES_PARAM = 'Параметр';
const NETCAT_MODULES_VALUE = 'Значение';
const NETCAT_MODULES_ADDPARAM = 'Добавить параметр';
const NETCAT_MODULE_INSTALLCOMPLIED = 'Установка модуля завершена.';
const NETCAT_MODULE_ALWAYS_LOAD = 'Загружать всегда';
const NETCAT_MODULE_ONOFF = 'Вкл/выкл';
define("NETCAT_MODULE_MODULE_UNCHECKED", "Модуль выключен, его настройка невозможна. Включить модуль можно в <a href='".$ADMIN_PATH."modules/index.php'>списке модулей.</a>");

# MODULE DEFAULT
define("NETCAT_MODULE_DEFAULT_DESCRIPTION", "Данный модуль предназначен для хранения вспомогательных скриптов и функций. Вы можете дописывать собственные функции в " . nc_module_path('default') . "function.inc.php и создавать собственные скрипты, интегрированные с системой по аналогии с " . nc_module_path('default') . "index.php. Также, вы можете задавать переменные окружения данного модуля в расположенном ниже поле.<br><br>Инструкции по созданию собственных модулей вы сможете найти в &quot;Руководстве разработчика&quot; в разделе &quot;Разработка модулей&quot;.");

# MISCELLANEOUS SETTINGS
const NETCAT_SETTINGS_MISC = 'Разное';
const NETCAT_SETTINGS_MISC_SECRET_KEY = 'Секретный ключ для подтверждения операций (требуется обновление страницы Ctrl + F5)';
#CODE MIRROR
const NETCAT_SETTINGS_CODEMIRROR = 'Подсветка синтаксиса';
const NETCAT_SETTINGS_CODEMIRROR_EMBEDED = 'Встроена';
const NETCAT_SETTINGS_CODEMIRROR_EMBEDED_ON = 'Да';
const NETCAT_SETTINGS_CODEMIRROR_DEFAULT = 'Подсветка по умолчанию';
const NETCAT_SETTINGS_CODEMIRROR_DEFAULT_ON = 'Да';
const NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE = 'Автодополнение';
const NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE_ON = 'Да';
const NETCAT_SETTINGS_CODEMIRROR_HELP = 'Окно подсказки';
const NETCAT_SETTINGS_CODEMIRROR_HELP_ON = 'Да';
const NETCAT_SETTINGS_CODEMIRROR_ENABLE = 'Включить редактор';
const NETCAT_SETTINGS_CODEMIRROR_SWITCH = 'Переключить редактор';
const NETCAT_SETTINGS_CODEMIRROR_WRAP = 'Переносить строки';
const NETCAT_SETTINGS_CODEMIRROR_FULLSCREEN = 'На весь экран';

const NETCAT_SETTINGS_DRAG = 'Перетаскивание объектов (разделов, инфоблоков, объектов, компонентов и т. д.)';
const NETCAT_SETTINGS_DRAG_SILENT = 'переносить без подтверждения';
const NETCAT_SETTINGS_DRAG_CONFIRM = 'спрашивать подтверждение перед переносом';
const NETCAT_SETTINGS_DRAG_DISABLED = 'запретить перетаскивание';

# EDITOR
const NETCAT_SETTINGS_EDITOR = 'Функции редактирования';
const NETCAT_SETTINGS_EDITOR_TYPE = 'Тип HTML-редактора';
const NETCAT_SETTINGS_EDITOR_FCKEDITOR = 'FCKeditor';
const NETCAT_SETTINGS_EDITOR_CKEDITOR = 'CKEditor';
const NETCAT_SETTINGS_EDITOR_TINYMCE = 'TinyMCE';
const NETCAT_SETTINGS_EDITOR_CKEDITOR_FILE_SYSTEM = 'Разделять закачиваемые файлы по личным папкам пользователей (CKEditor)';
const NETCAT_SETTINGS_EDITOR_CKEDITOR_CYRILIC_FOLDER = 'Разрешать символы кириллицы в именах папок файлового менеджера (CKEditor)';
const NETCAT_SETTINGS_EDITOR_CKEDITOR_CONTENT_FILTER = 'Включить <a href="http://docs.ckeditor.com/#!/guide/dev_advanced_content_filter" target="_blank">фильтрацию контента</a> (CKEditor)';
const NETCAT_SETTINGS_EDITOR_EMBED_ON = 'Да';
const NETCAT_SETTINGS_EDITOR_EMBED_TO_FIELD = 'Встроить редактор в поле для редактирования';
const NETCAT_SETTINGS_EDITOR_SEND = 'Отправить';
const NETCAT_SETTINGS_EDITOR_STYLES_SAVE = 'Сохранить изменения';
const NETCAT_SETTINGS_EDITOR_STYLES = 'Набор стилей для FCKeditor';
const NETCAT_SETTINGS_EDITOR_SKINS = 'Оформление';
const NETCAT_SETTINGS_EDITOR_ENTER_MODE = 'Режим клавиши Enter';
const NETCAT_SETTINGS_EDITOR_ENTER_MODE_P = 'Тег P';
const NETCAT_SETTINGS_EDITOR_ENTER_MODE_BR = 'Тег BR';
const NETCAT_SETTINGS_EDITOR_ENTER_MODE_DIV = 'Тег DIV';
const NETCAT_SETTINGS_EDITOR_SAVE = 'Настройки успешно изменены';
const NETCAT_SETTINGS_EDITOR_KEYCODE = 'Сохранение данных по Ctrl + %s, требуется обновление страницы Ctrl + F5';

const NETCAT_SEARCH_FIND_IT = 'Искать';
const NETCAT_SEARCH_ERROR = 'Невозможен поиск по данному компоненту.';

# JS settings
const NETCAT_SETTINGS_JS = 'Менеджер загрузки скриптов';
const NETCAT_SETTINGS_JS_FUNC_NC_JS = 'Функция nc_js()';
const NETCAT_SETTINGS_JS_LOAD_JQUERY_DOLLAR = 'Загружать jQuery объект $';
const NETCAT_SETTINGS_JS_LOAD_JQUERY_EXTENSIONS_ALWAYS = 'Всегда загружать расширения jQuery';
const NETCAT_SETTINGS_JS_LOAD_MODULES_SCRIPTS = 'Загружать модульные скрипты';
const NETCAT_SETTINGS_MINIFY_STATIC_FILES = 'Минифицировать CSS и JS файлы в админ-панели';

const NETCAT_SETTINGS_TRASHBIN = 'Корзина удаленных объектов';
const NETCAT_SETTINGS_TRASHBIN_USE = 'Использовать корзину';

const NETCAT_SETTINGS_EXPORT = 'Экспорт/импорт данных';
const NETCAT_SETTINGS_EXPORT_USE = 'Максимальный размер хранимых файлов экспорта';

#Components
const NETCAT_SETTINGS_COMPONENTS = 'Компоненты';
const NETCAT_SETTINGS_REMIND_SAVE = 'Напоминать о сохранении (требуется обновление страницы Ctrl + F5)';
const NETCAT_SETTINGS_PACKET_OPERATIONS = 'Включить групповые действия над объектами';
const NETCAT_SETTINGS_TEXTAREA_RESIZE = 'Включить возможность изменить размер текстового поля при редактировании компонента';

const NETCAT_SETTINGS_QUICKBAR = 'Панель быстрого редактирования';
const NETCAT_SETTINGS_QUICKBAR_ENABLE = 'Включить уполномоченным в системе';
const NETCAT_SETTINGS_QUICKBAR_ON = 'Да';

# ALT ADMIN BLOCKS
const NETCAT_SETTINGS_ALTBLOCKS = 'Альтернативные блоки администрирования';
const NETCAT_SETTINGS_ALTBLOCKS_ON = 'Да';
const NETCAT_SETTINGS_ALTBLOCKS_TEXT = 'Использовать альтернативные блоки администрирования';
const NETCAT_SETTINGS_ALTBLOCKS_PARAMS = 'Дополнительные параметры при удалении (начните с &)';

const NETCAT_SETTINGS_HTTP_PROXY = 'Использовать HTTP-прокси-сервер для доступа к серверу обновлений';
const NETCAT_SETTINGS_HTTP_PROXY_HOST = 'IP-адрес прокси-сервера';
const NETCAT_SETTINGS_HTTP_PROXY_PORT = 'Порт';
const NETCAT_SETTINGS_HTTP_PROXY_USER = 'Пользователь';
const NETCAT_SETTINGS_HTTP_PROXY_PASSWORD = 'Пароль';

const NETCAT_SETTINGS_USETOKEN = 'Использование ключа подтверждения операций';
const NETCAT_SETTINGS_USETOKEN_ADD = 'при добавлении';
const NETCAT_SETTINGS_USETOKEN_EDIT = 'при изменении';
const NETCAT_SETTINGS_USETOKEN_DROP = 'при удалении';
const NETCAT_SETTINGS_OBJECTS_FULLINK = 'Полное отображение объектов';
const CONTROL_SETTINGSFILE_BASIC_VERSION = 'Версия системы';
const CONTROL_SETTINGSFILE_CHANGE_EMAILS_FIELD = 'Поле (с форматом email) в таблице пользователей';
const CONTROL_SETTINGSFILE_CHANGE_EMAILS_NONE = 'Поле отсутствует';
const NETCAT_SETTINGS_CODEMIRROR_EMBEDED_OFF = 'Нет';
const NETCAT_SETTINGS_CODEMIRROR_DEFAULT_OFF = 'Нет';
const NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE_OFF = 'Нет';
const NETCAT_SETTINGS_CODEMIRROR_HELP_OFF = 'Нет';
const NETCAT_SETTINGS_INLINE_EDIT_CONFIRMATION = 'Спрашивать подтверждение сохранения inline-изменений';
const NETCAT_SETTINGS_INLINE_EDIT_CONFIRMATION_ON = 'Подтверждение сохранения inline-изменений включено';
const NETCAT_SETTINGS_INLINE_EDIT_CONFIRMATION_OFF = 'Подтверждение сохранения inline-изменений отключено';
const NETCAT_SETTINGS_EDITOR_EMBEDED = 'Редактор встроен в поле для редактирования';
const NETCAT_SETTINGS_EDITOR_EMBED_OFF = 'Нет';
const NETCAT_SETTINGS_EDITOR_STYLES_CANCEL = 'Отмена';
const NETCAT_SETTINGS_TRASHBIN_MAXSIZE = 'Максимальный размер корзины';
const NETCAT_SETTINGS_REMIND_SAVE_INFO = 'Напоминать о необходимости сохранения';
const NETCAT_SETTINGS_PACKET_OPERATIONS_INFO = 'Включить групповые действия над объектами';
const NETCAT_SETTINGS_TEXTAREA_RESIZE_INFO = 'Включить возможность изменить размер текстового поля при редактировании компонента';
const NETCAT_SETTINGS_DISABLE_BLOCK_MARKUP_INFO = 'Отключить <a href="https://netcat.ru/developers/docs/components/stylesheets/" target="_blank">дополнительную разметку</a> для создаваемых компонентов';
const CONTROL_CLASS_IS_MULTIPURPOSE_SWITCH = 'Многоцелевой шаблон';
const CONTROL_CLASS_COMPATIBLE_FIELDS = 'Список обязательных полей в совместимых компонентах (по одному на строчку)';
const NETCAT_SETTINGS_QUICKBAR_OFF = 'Нет';
const NETCAT_SETTINGS_ALTBLOCKS_OFF = 'Нет';

# Export / Import
const NETCAT_IMPORT_FIELD = 'XML-файл импорта';

const NETCAT_FILEUPLOAD_ERROR = 'Ошибка! У Вас нет прав на директорию %s на этом сервере.';


const NETCAT_HTTP_REQUEST_SAVING = 'Сохранение...';
const NETCAT_HTTP_REQUEST_SAVED = 'Изменения сохранены';
const NETCAT_HTTP_REQUEST_ERROR = 'Ошибка при сохранении';
const NETCAT_HTTP_REQUEST_HINT = 'Вы можете сохранить эту форму, нажав Ctrl + %s';

# Index page menu
const SECTION_INDEX_MENU_TITLE = 'Главное меню';
const SECTION_INDEX_MENU_HOME = 'главная';
const SECTION_INDEX_MENU_SITE = 'сайт';
const SECTION_INDEX_MENU_DEVELOPMENT = 'разработка';
const SECTION_INDEX_MENU_TOOLS = 'инструменты';
const SECTION_INDEX_MENU_SETTINGS = 'настройки';
const SECTION_INDEX_MENU_HELP = 'справка';
const SECTION_INDEX_MENU_SUPPORT = 'поддержка';

define("SECTION_INDEX_HELP_SUBMENU_HELP", "Справка " . CMS_SYSTEM_NAME);
const SECTION_INDEX_HELP_SUBMENU_DOC = 'Документация';
const SECTION_INDEX_HELP_SUBMENU_HELPDESK = 'Онлайн-поддержка';
const SECTION_INDEX_HELP_SUBMENU_SUPPORT = 'Доступ для поддержки';
const SECTION_INDEX_HELP_SUBMENU_FORUM = 'Форум';
const SECTION_INDEX_HELP_SUBMENU_BASE = 'База знаний';
const SECTION_INDEX_HELP_SUBMENU_ABOUT = 'О программе';

const SECTION_INDEX_SITE_LIST = 'Список сайтов';
const SECTION_INDEX_SITE_LIST_SAAS = 'Все сайты (личный кабинет)';

const SECTION_INDEX_WIZARD_SUBMENU_CLASS = 'Мастер создания компонента';
const SECTION_INDEX_WIZARD_SUBMENU_SITE = 'Мастер создания сайта';

const SECTION_INDEX_FAVORITE_ANOTHER_SUB = 'Другой раздел...';
const SECTION_INDEX_FAVORITE_ADD = 'Добавить в это меню';
const SECTION_INDEX_FAVORITE_LIST = 'Редактировать это меню';
const SECTION_INDEX_FAVORITE_SETTINGS = 'Настройки';

const SECTION_INDEX_WELCOME = 'Добро пожаловать';
const SECTION_INDEX_WELCOME_MESSAGE = 'Здравствуйте, %s!<br />Вы находитесь в системе управления проектом «%s».<br />Вам присвоены права: %s.';
define("SECTION_INDEX_TITLE", "Система управления " . CMS_SYSTEM_NAME);

# SITE
## TABS
const SITE_TAB_SITEMAP = 'Карта сайта';
const SITE_TAB_SETTINGS = 'Настройки';
const SITE_TAB_DEMO_CONTENT = 'Демо-контент';
const SITE_TAB_STATS = 'Статистика';
const SITE_TAB_AREA_INFOBLOCKS = 'Сквозные инфоблоки';
## TOOLBAR
const SITE_TOOLBAR_INFO = 'Общая информация';
const SITE_TOOLBAR_SUBLIST = 'Список разделов';


#SUBDIVISION
## TABS
## TOOLBAR
const SUBDIVISION_TAB_INFO_TOOLBAR_INFO = 'Информация о разделе';
const SUBDIVISION_TAB_INFO_TOOLBAR_SUBLIST = 'Список разделов';
const SUBDIVISION_TAB_INFO_TOOLBAR_CCLIST = 'Список инфоблоков';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST = 'Пользователи';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_EDIT = 'Основные';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_DESIGN = 'Оформление';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_SEO = 'SEO/SMO';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_SYSTEM = 'Системные';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_FIELDS = 'Дополнительные настройки';


## BUTTONS
const SUBDIVISION_TAB_PREVIEW_BUTTON_PREVIEW = 'Просмотр в новом окне';

const SITE_SITEMAP_SEARCH = 'Поиск по карте сайта';
const SITE_SITEMAP_SEARCH_NOT_FOUND = 'Не найдено';

## TEXT
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_READ_ACCESS = 'Доступ на чтение';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_COMMENT_ACCESS = 'Доступ на комментирование';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_WRITE_ACCESS = 'Доступ на запись';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_EDIT_ACCESS = 'Доступ на редактирование';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_SUBSCRIBE_ACCESS = 'Доступ в подписку';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_MODERATORS = 'Модераторы';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ADMINS = 'Администраторы';

const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ALL_USERS = 'Все пользователи';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_REGISTERED_USERS = 'Зарегистрированные пользователи';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS = 'Привилегированные пользователи';

# CLASS WIZARD

const WIZARD_CLASS_FORM_SUBDIVISION_PARENTSUB = 'Родительский раздел';

const WIZARD_PARENTSUB_SELECT_POPUP_TITLE = 'Выбор родительского раздела';

const WIZARD_CLASS_FORM_SUBDIVISION_SELECT_PARENTSUB = 'выбрать родительский раздел';
const WIZARD_CLASS_FORM_SUBDIVISION_SELECT_SUBDIVISION = 'выбрать раздел';
const WIZARD_CLASS_FORM_SUBDIVISION_DELETE = 'удалить';

const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE = 'Тип шаблона';
const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_SINGLE = 'Единственный объект на странице';
const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_LIST = 'Список объектов';
const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_SEARCH = 'Поиск по списку объектов';
const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_FORM = 'Веб-форма';

const WIZARD_CLASS_FORM_SETTINGS_NO_SETTINGS = 'Для данного типа шаблона настроек не предусмотренно.';
const WIZARD_CLASS_FORM_SETTINGS_FIELDS_FOR_OBJECT_LIST = 'Поля для списка объектов';
const WIZARD_CLASS_FORM_SETTINGS_SORT_OBJECT_BY_FIELD = 'Сортировать объекты по полю';
const WIZARD_CLASS_FORM_SETTINGS_SORT_ASC = 'По возрастанию';
const WIZARD_CLASS_FORM_SETTINGS_SORT_DESC = 'По убыванию';
const WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION = 'Навигация по страницам';
const WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION_BY_NEXT_PREV = 'переход на другие страницы списка «следующий-предыдущий»';
const WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION_BY_PAGE_NUMBER = 'по номерам страниц';
const WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION_BY_BOTH = 'оба варианта';
const WIZARD_CLASS_FORM_SETTINGS_LOCATION_OF_NAVIGATION = 'Положение вывода навигации';
const WIZARD_CLASS_FORM_SETTINGS_LOCATION_TOP = 'Вверху страницы';
const WIZARD_CLASS_FORM_SETTINGS_LOCATION_BOTTOM = 'Внизу страницы';
const WIZARD_CLASS_FORM_SETTINGS_LOCATION_BOTH = 'оба варианта';
const WIZARD_CLASS_FORM_SETTINGS_LIST_TYPE = 'Список';
const WIZARD_CLASS_FORM_SETTINGS_TABLE_TYPE = 'Таблица';
const WIZARD_CLASS_FORM_SETTINGS_LIST_DELIMITER_TYPE = 'Тип разделителя';
const WIZARD_CLASS_FORM_SETTINGS_TABLE_TYPE_SETTINGS = 'Настройки типа таблицы';
const WIZARD_CLASS_FORM_SETTINGS_TABLE_BACKGROUND = 'Чередовать фон';
const WIZARD_CLASS_FORM_SETTINGS_TABLE_BORDER = 'Граница таблицы';
const WIZARD_CLASS_FORM_SETTINGS_FULL_PAGE = 'Страница с подробной информацией';
const WIZARD_CLASS_FORM_SETTINGS_FULL_PAGE_LINK_TYPE = 'Способ перехода на страницу отображения объекта';
const WIZARD_CLASS_FORM_SETTINGS_CHECK_FIELDS_TO_FULL_PAGE = 'Отметьте поля при нажатии на которые будет производиться переход на страницу отображения объекта';

const WIZARD_CLASS_FORM_SETTINGS_FIELDS_FOR_OBJECT_SEARCH = 'Поля, по которым будет производиться поиск';

const WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_FIELDS_SETTINGS = 'Настройка полей обратной связи';
const WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_SENDER_NAME = 'Имя отправителя';
const WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_SENDER_MAIL = 'Email отправителя';
const WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_SUBJECT = 'Тема письма';

## TABS
const WIZARD_CLASS_TAB_SELECT_TEMPLATE_TYPE = 'Выбор типа шаблона';
const WIZARD_CLASS_TAB_ADDING_TEMPLATE_FIELDS = 'Добавление полей шаблона';
const WIZARD_CLASS_TAB_TEMPLATE_SETTINGS = 'Настройка шаблона';
const WIZARD_CLASS_TAB_SUBSEQUENT_ACTION = 'Дальнейшее действие';
const WIZARD_CLASS_TAB_CREATION_SUBDIVISION_WITH_NEW_TEMPLATE = 'Создание раздела с новым шаблоном';
const WIZARD_CLASS_TAB_ADDING_TEMPLATE_TO_EXISTENT_SUBDIVISION = 'Добавление шаблона к существующему разделу';

## BUTTONS
const WIZARD_CLASS_BUTTON_NEXT_TO_ADDING_FIELDS = 'Перейти к добавлению полей';
const WIZARD_CLASS_BUTTON_FINISH_ADDING_FIELDS = 'Закончить добавление полей';
const WIZARD_CLASS_BUTTON_SAVE_SETTINGS = 'Сохранить настройки';
const WIZARD_CLASS_BUTTON_ADDING_SUBDIVISION_WITH_NEW_TEMPLATE = 'Добавить раздел с новым компонентом';
const WIZARD_CLASS_BUTTON_CREATE_SUBDIVISION_WITH_NEW_TEMPLATE = 'Создать раздел с новым компонентом';
const WIZARD_CLASS_BUTTON_NEXT_TO_SUBDIVISION_SELECTION = 'Перейти к выбору раздела';

## LINKS
const WIZARD_CLASS_LINKS_VIEW_TEMPLATE_CODE = 'Посмотреть программный код шаблона';
const WIZARD_CLASS_LINKS_CREATE_SUBDIVISION_WITH_NEW_TEMPLATE = 'Создать раздел с этим шаблоном';
const WIZARD_CLASS_LINKS_ADD_TEMPLATE_TO_EXISTENT_SUBDIVISION = 'Прикрепить шаблон к существующему разделу';
const WIZARD_CLASS_LINKS_CREATE_NEW_TEMPLATE = 'Создать новый шаблон';

const WIZARD_CLASS_LINKS_RETURN_TO_FIELDS_ADDING = 'Вернуться к добавлению полей';

## COMMON
const WIZARD_CLASS_STEP = 'Шаг';
const WIZARD_CLASS_STEP_FROM = 'Из';

const WIZARD_CLASS_DEFAULT = 'по умолчанию';

const WIZARD_CLASS_ERROR_NO_FIELDS = 'В шаблон необходимо добавить хотя бы одно поле!';

const BLOCK_ORIENTATION_LEFT = "слева";
const BLOCK_ORIENTATION_RIGHT = "справа";
const BLOCK_ORIENTATION_TOP = "сверху";
const BLOCK_ORIENTATION_BOTTOM = "снизу";

# SITE WIZARD
const WIZARD_SITE_FORM_WHICH_MODULES = 'Какие модули вы хотите задействовать на сайте?';

## TABS
const WIZARD_SITE_TAB_NEW_SITE_CREATION = 'Создание нового сайта';
const WIZARD_SITE_TAB_NEW_SITE_ADD_SUB = 'Добавление разделов сайта';

## BUTTONS
const WIZARD_SITE_BUTTON_ADD_SUBS = 'Добавить подразделы';
const WIZARD_SITE_BUTTON_FINISH_ADD_SUBS = 'Завершить';

## COMMON
const WIZARD_SITE_STEP = 'Шаг';
const WIZARD_SITE_STEP_FROM = 'Из';
const WIZARD_SITE_STEP_TWO_DESCRIPTION = 'Создание служебных разделов. Каждый сайт должен иметь титульную страницу и страницу 404. Можете оставить эти поля без изменений.';

#DEMO MODE
const DEMO_MODE_ADMIN_INDEX_MESSAGE = "Сайт «%s» работает в демо-режиме, вы можете выключить его в <a href='%s'>системных настройках сайта</a>.";
const DEMO_MODE_FRONT_INDEX_MESSAGE_GUEST = 'Это не настоящий сайт, он предназначен только для демонстрации.';
const DEMO_MODE_FRONT_INDEX_MESSAGE_ADMIN = "Сайт в демо-режиме, снять его можно <a href='%s'>в настройках</a>.";
const DEMO_MODE_FRONT_INDEX_MESSAGE_CLOSE = 'Закрыть';

# FAVORITE
## HEADER TEXT
const FAVORITE_HEADERTEXT = 'Избранные разделы';


# CRONTAB
##TABS
const CRONTAB_TAB_LIST = 'Список задач';
const CRONTAB_TAB_ADD = 'Добавление задачи';
const CRONTAB_TAB_EDIT = 'Редактирование задачи';

##TRASH
const TRASH_TAB_LIST = 'Корзина удаленных объектов';
const TRASH_TAB_TITLE = 'Список удаленных объектов';
const TRASH_TAB_SETTINGS = 'Настройки';

# REDIRECT
##TABS
const REDIRECT_TAB_LIST = 'Список переадресаций';
const REDIRECT_TAB_ADD = 'Добавление переадресации';
const REDIRECT_TAB_EDIT = 'Редактирование переадресации';


# SYSTEM SETTINGS
##TABS
const SYSTEMSETTINGS_TAB_LIST = 'Базовые настройки системы';
const SYSTEMSETTINGS_TAB_ADD = 'Редактирование базовых настроек';
const SYSTEMSETTINGS_SAVE_OK = 'Настройки системы сохранены';
const SYSTEMSETTINGS_SAVE_ERROR = 'Ошибка сохранения настроек системы';

# WYSIWYG SETTINGS
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TAB_SETTINGS = 'Настройки';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TAB_PANELS = 'Панели';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_SETTINGS = 'Настройки WYSIWYG';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_PANELS = 'Панели редактора WYSIWYG';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_DELETE_CONFIRMATION = 'Подтверждение удаления панелей WYSIWYG редактора';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_EDIT_FORM = ' - редактирование панели WYSIWYG';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_ADD_FORM = 'Добавление панели WYSIWYG';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANEL_NOT_EXISTS = 'Такой панели не существует';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_NO_PANELS = 'Нет ни одной панели';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANEL_EDIT_SUCCESSFUL = 'Панель успешно изменена';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANEL_ADD_SUCCESSFUL = 'Панель успешно добавлена';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANELS_NOT_SELECTED = 'Не выбрано ни одной панели';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANELS_DELETED = 'Панели успешно удалены';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANELS_DELETE_ERROR = 'Ошибка при удалении панелей';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_FILL_PANEL_NAME = 'Укажите имя панели';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_SELECT_TOOLBAR = 'Выберите хотя бы один тулбар';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_DELETE_SELECTED = 'Удалить выбранные';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_CONFIRM_DELETE = 'Подтвердить удаление';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_CANCEL = 'Отмена';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_EDIT_PANEL = 'Изменить панель';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_ADD_PANEL = 'Добавить панель';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_PANEL_NAME = 'Название панели';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_DELETE = 'Удалить';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_ARE_YOU_REALLY_WANT_TO_DELETE_PANELS = 'Вы действительно желаете удалить панели?';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_EDITOR_PANEL_FULL = 'Панель полного редактирования';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_EDITOR_PANEL_INLINE = 'Панель inline редактирования';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_PANEL_NAME = 'Название панели';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_PANEL_PREVIEW = 'Предпросмотр';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_SETTINGS = 'Настройка панели инструментов';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_MODE = 'Переключение типа документа';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_DOCUMENT = 'Операции с документом';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_TOOLS = 'Инструменты';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_DOCTOOLS = 'Шаблоны';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_CLIPBOARD = 'Буфер обмена';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_UNDO = 'Отмена действий';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_FIND = 'Поиск и замена';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_SELECTION = 'Выделение';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_SPELLCHECKER = 'Проверка орфографии';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_FORMS = 'Формы';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_BASICSTYLES = 'Базовые стили';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_CLEANUP = 'Очистка форматирования';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_LIST = 'Списки';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_INDENT = 'Отступы';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_BLOCKS = 'Блоки текста';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_ALIGN = 'Выравнивание';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_LINKS = 'Ссылки';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_INSERT = 'Вставка объектов';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_STYLES = 'Стили';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_COLORS = 'Цвета';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_OTHERS = 'Другие инструменты';

const NETCAT_WYSIWYG_FCKEDITOR_SETTINGS_TITLE_SETTINGS = 'Настройки WYSIWYG';

const NETCAT_WYSIWYG_SETTINGS_PANEL_SETTINGS = 'Настройка панелей';
const NETCAT_WYSIWYG_SETTINGS_THIS_EDITOR_IS_USED_BY_DEFAULT = 'Этот редактор используется по умолчанию';
const NETCAT_WYSIWYG_SETTINGS_USE_BY_DEFAULT = 'Использовать этот редактор по умолчанию';
const NETCAT_WYSIWYG_SETTINGS_BASIC_SETTINGS = 'Основные настройки';
const NETCAT_WYSIWYG_SETTINGS_MESSAGE_EDITOR_ACTIVATED = 'Редактор успешно активирован';
const NETCAT_WYSIWYG_SETTINGS_PANEL_NOT_SELECTED = 'Не выбрана';
const NETCAT_WYSIWYG_SETTINGS_BUTTON_SAVE = 'Сохранить';
const NETCAT_WYSIWYG_SETTINGS_MESSAGE_SETTINGS_SAVED = 'Настройки WYSIWYG сохранены';
const NETCAT_WYSIWYG_SETTINGS_MESSAGE_SETTINGS_SAVE_ERROR = 'Произошла ошибка при сохранении WYSIWYG настроек';
const NETCAT_WYSIWYG_SETTINGS_CONFIG_JS_SETTINGS = 'Настройка config.js';
const NETCAT_WYSIWYG_SETTINGS_CONFIG_JS_FILE = 'Файл config.js';

const NETCAT_WYSIWYG_EDITOR_OUTWORN = 'Этот редактор устарел, рекомендуем переключится на другой редактор и удалить директорию %s с сервера';

# Not Elsewhere Specified
const NOT_ELSEWHERE_SPECIFIED = 'Не указывать';
const NOT_ADD_CLASS = 'Не добавлять инфоблок в раздел';

# BBcodes
const NETCAT_BBCODE_SIZE = 'Размер шрифта';
const NETCAT_BBCODE_COLOR = 'Цвет шрифта';
const NETCAT_BBCODE_SMILE = 'Смайлы';
const NETCAT_BBCODE_B = 'Жирный';
const NETCAT_BBCODE_I = 'Курсив';
const NETCAT_BBCODE_U = 'Подчёркнутый';
const NETCAT_BBCODE_S = 'Зачёркнутый';
const NETCAT_BBCODE_LIST = 'Элемент списка';
const NETCAT_BBCODE_QUOTE = 'Цитата';
const NETCAT_BBCODE_CODE = 'Код';
const NETCAT_BBCODE_IMG = 'Изображение';
const NETCAT_BBCODE_URL = 'Ссылка';
const NETCAT_BBCODE_CUT = 'Сократить текст';

const NETCAT_BBCODE_QUOTE_USER = 'писал(а)';
const NETCAT_BBCODE_CUT_MORE = 'подробнее';
const NETCAT_BBCODE_SIZE_DEF = 'размер';
const NETCAT_BBCODE_ERROR_1 = 'введён BB-код недопустимого формата:';
const NETCAT_BBCODE_ERROR_2 = 'введены BB-коды недопустимого формата:';

# Help messages for BBcode
const NETCAT_BBCODE_HELP_SIZE = 'Размер шрифта: [SIZE=8]маленький текст[/SIZE]';
const NETCAT_BBCODE_HELP_COLOR = 'Цвет шрифта: [COLOR=FF0000]текст[/COLOR]';
const NETCAT_BBCODE_HELP_SMILE = 'Вставить смайлик';
const NETCAT_BBCODE_HELP_B = 'Жирный шрифт: [B]текст[/B]';
const NETCAT_BBCODE_HELP_I = 'Наклонный шрифт: [I]текст[/I]';
const NETCAT_BBCODE_HELP_U = 'Подчёркнутый шрифт: [U]текст[/U]';
const NETCAT_BBCODE_HELP_S = 'Зачёркнутый шрифт: [S]текст[/S]';
const NETCAT_BBCODE_HELP_LIST = 'Элемент списка: [LIST]текст[/LIST]';
const NETCAT_BBCODE_HELP_QUOTE = 'Цитата: [QUOTE]текст[/QUOTE]';
const NETCAT_BBCODE_HELP_CODE = 'Код: [CODE]код[/CODE]';
const NETCAT_BBCODE_HELP_IMG = 'Вставить картинку';
const NETCAT_BBCODE_HELP_IMG_URL = 'Адрес картинки';
const NETCAT_BBCODE_HELP_URL = 'Вставить ссылку';
const NETCAT_BBCODE_HELP_URL_URL = 'Ссылка';
const NETCAT_BBCODE_HELP_URL_DESC = 'Описание';
const NETCAT_BBCODE_HELP_CUT = 'Сократить текст в листинге: [CUT=подробнее]текст[/CUT]';
const NETCAT_BBCODE_HELP = 'Подсказка: выше расположены кнопки быстрого форматирования';

# Smiles
const NETCAT_SMILE_SMILE = 'улыбка';
const NETCAT_SMILE_BIGSMILE = 'большая улыбка';
const NETCAT_SMILE_GRIN = 'усмешка';
const NETCAT_SMILE_LAUGH = 'смех';
const NETCAT_SMILE_PROUD = 'гордый';
#
const NETCAT_SMILE_YES = 'да';
const NETCAT_SMILE_WINK = 'подмигивает';
const NETCAT_SMILE_COOL = 'клево';
const NETCAT_SMILE_ROLLEYES = 'невинный';
const NETCAT_SMILE_LOOKDOWN = 'стыдно';
#
const NETCAT_SMILE_SAD = 'грустный';
const NETCAT_SMILE_SUSPICIOUS = 'подозрительный';
const NETCAT_SMILE_ANGRY = 'сердитый';
const NETCAT_SMILE_SHAKEFIST = 'грозится';
const NETCAT_SMILE_STERN = 'суровый';
#
const NETCAT_SMILE_KISS = 'поцелуй';
const NETCAT_SMILE_THINK = 'думает';
const NETCAT_SMILE_THUMBSUP = 'круто';
const NETCAT_SMILE_SICK = 'тошнит';
const NETCAT_SMILE_NO = 'нет';
#
const NETCAT_SMILE_CANTLOOK = 'не могу смотреть';
const NETCAT_SMILE_DOH = 'ооо';
const NETCAT_SMILE_KNOCKEDOUT = 'в ауте';
const NETCAT_SMILE_EYEUP = 'хммм';
const NETCAT_SMILE_QUIET = 'тихо';
#
const NETCAT_SMILE_EVIL = 'злой';
const NETCAT_SMILE_UPSET = 'огорчен';
const NETCAT_SMILE_UNDECIDED = 'неуверенный';
const NETCAT_SMILE_CRY = 'плачет';
const NETCAT_SMILE_UNSURE = 'не уверен';

# nc_bytes2size
const NETCAT_SIZE_BYTES = ' байт';
const NETCAT_SIZE_KBYTES = ' КБ';
const NETCAT_SIZE_MBYTES = ' МБ';
const NETCAT_SIZE_GBYTES = ' ГБ';

// quickBar
const NETCAT_QUICKBAR_BUTTON_VIEWMODE = 'просмотр';
const NETCAT_QUICKBAR_BUTTON_EDITMODE = 'редактирование';
const NETCAT_QUICKBAR_BUTTON_EDITMODE_UNAVAILABLE_FOR_LONGPAGE = 'Редактирование недоступно в режиме longpage';
const NETCAT_QUICKBAR_BUTTON_MORE = 'еще';
const NETCAT_QUICKBAR_BUTTON_OBJECT_SETTINGS = 'Свойства %s';
const NETCAT_QUICKBAR_BUTTON_SUBDIVISION_SETTINGS = 'Настройки страницы';
const NETCAT_QUICKBAR_BUTTON_SUBDIVISION_VERSIONS = 'Версии страницы';
const NETCAT_QUICKBAR_BUTTON_TEMPLATE_SETTINGS = 'Макет дизайна';
const NETCAT_QUICKBAR_BUTTON_ADMIN = 'Администрирование';
const NETCAT_QUICKBAR_BUTTON_CONTROL_PANEL = 'Панель управления';
const NETCAT_QUICKBAR_BUTTON_DESIGN_MODE = 'Режим дизайна';

#SYNTAX EDITOR
const NETCAT_SETTINGS_SYNTAXEDITOR = 'Он-лайн подсветка синтаксиса';
const NETCAT_SETTINGS_SYNTAXEDITOR_ENABLE = 'Включить использование подсветки синтаксиса в системе (требуется перезагрузка админки Ctrl+F5)';

#SYNTAX CHECK

# LICENSE
const NETCAT_SETTINGS_LICENSE = 'Лицензия';
const NETCAT_SETTINGS_LICENSE_PRODUCT = 'Код продукта';
const NETCAT_SETTINGS_LICENSE_CODE = 'Код активации';

# NETCAT_DEBUG
const NETCAT_DEBUG_ERROR_INSTRING = 'Ошибка в строке : ';
const NETCAT_DEBUG_BUTTON_CAPTION = 'Отладка';

# NETCAT_PREVIEW
const NETCAT_PREVIEW_BUTTON_CAPTIONCLASS = 'Предпросмотр компонента';
const NETCAT_PREVIEW_BUTTON_CAPTIONTEMPLATE = 'Предпросмотр макета';

const NETCAT_PREVIEW_BUTTON_CAPTIONADDFORM = 'Предпросмотр формы добавления';
const NETCAT_PREVIEW_BUTTON_CAPTIONEDITFORM = 'Предпросмотр формы редактирования';
const NETCAT_PREVIEW_BUTTON_CAPTIONSEARCHFORM = 'Предпросмотр формы поиска';

const NETCAT_PREVIEW_ERROR_NODATA = 'Ошибка! Не переданы данные для режима предпросмотра или данные устарели';
const NETCAT_PREVIEW_ERROR_WRONGDATA = 'Ошибка в данных для режима предпросмотра';
const NETCAT_PREVIEW_ERROR_NOSUB = ' Нет ни одного раздела с таким компонентом. Добавьте этот компонент в раздел и режим предпросмотра станет доступен.';
const NETCAT_PREVIEW_ERROR_NOMESSAGE = ' Нет ни одного объекта такого компонента. Добавьте хотя бы один объект такого компонента и режим предпросмотра станет доступен.';
const NETCAT_PREVIEW_INFO_MORESUB = ' Есть несколько разделов с таким компонентом. Выберите раздел для предпросмотра.';
const NETCAT_PREVIEW_INFO_CHOOSESUB = ' Выберите раздел для предпросмотра макета.';

# objects
const NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_SUPERVISOR = "Ошибка SQL запроса в функции nc_objects_list(%s, %s, \"%s\"), %s";
const NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_USER = 'Ошибка в функции вывода объектов.';
const NETCAT_FUNCTION_OBJECTS_LIST_CLASSIFICATOR_ERROR = 'список «%s» не найден';
const NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_UNKNOWN = 'поле «%s» не найдено';
const NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_CLAUSE = 'поле «%s» не найдено в условии';
const NETCAT_FUNCTION_OBJECTS_LIST_CC_ERROR = 'Ошибочный параметр $cc в функции nc_objects_list(XX, %s, "...")';
const NETCAT_FUNCTION_LISTCLASSVARS_ERROR_SUPERVISOR = 'Ошибочный параметр $cc в функции ListClassVars(%s)';
const NETCAT_FUNCTION_FULL_SQL_ERROR_USER = 'Ошибка в функции полного отображения объекта.';

# events





// widgets events

const NETCAT_TOKEN_INVALID = 'Неверный ключ подтверждения';

// Подсказки в сплывающах окнах
const NETCAT_HINT_COMPONENT_FIELD = 'Поля компонента';
const NETCAT_HINT_COMPONENT_SIZE = 'Размер';
const NETCAT_HINT_COMPONENT_TYPE = 'Тип';
const NETCAT_HINT_COMPONENT_ID = 'Номер';
const NETCAT_HINT_COMPONENT_DAY = 'Числовое значение дня';
const NETCAT_HINT_COMPONENT_MONTH = 'Числовое значение месяца';
const NETCAT_HINT_COMPONENT_YEAR = 'Числовое значение года';
const NETCAT_HINT_COMPONENT_HOUR = 'Числовое значение часа';
const NETCAT_HINT_COMPONENT_MINUTE = 'Числовое значение минуты';
const NETCAT_HINT_COMPONENT_SECONDS = 'Числовое значение секунды';
const NETCAT_HINT_OBJECT_PARAMS = 'Переменные, содержащие свойства текущего объекта';
const NETCAT_HINT_CREATED_DESC = 'реквизиты  времени добавления объекта в формате «гггг-мм-дд чч:мм:сс»';
const NETCAT_HINT_LASTUPDATED_DESC = 'реквизиты времени последнего изменения объекта в формате «ггггммддччммсс»';
const NETCAT_HINT_MESSAGE_ID = 'номер (ID) объекта';
const NETCAT_HINT_USER_ID = 'номер (ID) пользователя, добавившего объект';
const NETCAT_HINT_CHECKED = 'включен или выключен объект (0/1)';
const NETCAT_HINT_IP = 'IP-адрес пользователя, добавившего объект';
const NETCAT_HINT_USER_AGENT = 'значение переменной $HTTP_USER_AGENT для пользователя, добавившего объект';
const NETCAT_HINT_LAST_USER_ID = 'номер (ID) последнего пользователя, изменившего объект';
const NETCAT_HINT_LAST_USER_IP = 'IP-адрес последнего пользователя, изменившего объект';
const NETCAT_HINT_LAST_USER_AGENT = 'значение переменной $HTTP_USER_AGENT для последнего пользователя, изменившего объект';
const NETCAT_HINT_ADMIN_BUTTONS = 'в режиме администрирования содержит блок статусной информации о записи и ссылки на действия для данной записи «изменить», «удалить», «включить/выключить» (только в поле «Объект в списке»)';
const NETCAT_HINT_ADMIN_COMMONS = 'в режиме администрирования содержит блок статусной информации о шаблоне и ссылку на добавление объекта в данный шаблон в раздле и удаление всех объектов из этого же шаблона (только в поле «Объект в списке»)';
const NETCAT_HINT_FULL_LINK = 'ссылка на макет полного вывода данной записи';
const NETCAT_HINT_FULL_DATE_LINK = 'ссылка на макет полного вывода с указанием даты в виде «.../2002/02/02/message_2.html» (устанавливается в случае если в шаблоне имеется поле типа «Дата и время» с форматом «event», иначе значение переменной идентично значению $fullLink)';
const NETCAT_HINT_EDIT_LINK = 'ссылка на редактирование объекта';
const NETCAT_HINT_DELETE_LINK = 'ссылка на удаление объекта';
const NETCAT_HINT_DROP_LINK = 'ссылка на удаление объекта без подтверждения';
const NETCAT_HINT_CHECKED_LINK = 'ссылка на включение/выключение объекта';
const NETCAT_HINT_PREV_LINK = 'ссылка на предыдущую страницу в листинге шаблона (если текущее положение в списке – его начало, то переменная пустая)';
const NETCAT_HINT_NEXT_LINK = 'ссылка на следующую страницу в листинге шаблона (если текущее положение в списке – его конец, то переменная пустая)';
const NETCAT_HINT_ROW_NUM = 'номер записи по порядку в списке на текущей странице';
const NETCAT_HINT_REC_NUM = 'максимальное количество записей, выводимых в списке';
const NETCAT_HINT_TOT_ROWS = 'общее количество записей в списке';
const NETCAT_HINT_BEG_ROW = 'номер записи (по порядку), с которой начинается листинг списка на данной странице';
const NETCAT_HINT_END_ROW = 'номер записи (по порядку), которой заканчивается листинг списка на данной странице';
const NETCAT_HINT_ADMIN_MODE = 'истинна, если пользователь находится в режиме администрирования';
const NETCAT_HINT_SUB_HOST = 'адрес текущего домена вида «www.example.com»';
const NETCAT_HINT_SUB_LINK = 'путь к текущему разделу вида «/about/pr/»';
const NETCAT_HINT_CC_LINK = 'ссылка для текущего компонента в разделе вида «news.html»';
const NETCAT_HINT_CATALOGUE_ID = 'Номер текущего каталога';
const NETCAT_HINT_SUB_ID = 'Номер текущего раздела';
const NETCAT_HINT_CC_ID = 'Номер текущего компонента в разделе';
const NETCAT_HINT_CURRENT_CATALOGUE = 'Содержат значения свойств текущего каталога';
const NETCAT_HINT_CURRENT_SUB = 'Содержат значения свойств текущего раздела';
const NETCAT_HINT_CURRENT_CC = 'Содержат значения свойств текущего компонента в разделе';
const NETCAT_HINT_CURRENT_USER = 'Содержат значения свойств текущего авторизованного пользователя.';
const NETCAT_HINT_IS_EVEN = 'Проверяет параметр на четность';
const NETCAT_HINT_OPT = 'Функция opt() выводит $string в случае, если $flag – истина';
const NETCAT_HINT_OPT_CAES = 'Функция opt_case() выводит $string1 в случае, если $flag истина, и $string2, если $flag ложь';
const NETCAT_HINT_LIST_QUERY = 'Функция предназначена для выполнения SQL-запроса $sql_query. Для запроса типа SELECT (или для других случаев, придуманных разработчиком) используется $output_template для вывода результатов выборки. $output_template является необязательным параметром.<br>Последний параметр должен содержать вызов хэш-массива $data, индексы которого соответствуют полям таблицы (знак доллара и двойные кавычки необходимо маскировать). $divider служит для разделения результатов вывода.';
define("NETCAT_HINT_NC_LIST_SELECT", "Функция позволяет генерировать HTML списки из Списков " . CMS_SYSTEM_NAME);
const NETCAT_HINT_NC_MESSAGE_LINK = 'Функция позволяет получить относительный путь к объекту (без домена) по номеру (ID) этого объекта и номеру (ID) компонента, которому он принадлежит';
const NETCAT_HINT_NC_FILE_PATH = 'Функция позволяет получить путь к файлу, указанному в определенном поле, по номеру (ID) этого объекта и номеру (ID) компонента, которому он принадлежит';
const NETCAT_HINT_BROWSE_MESSAGE = 'Функция отображает блок навигации по страницам списка записей в шаблоне';
const NETCAT_HINT_NC_OBJECTS_LIST = 'Выводит содержимое компонента в разделе $cc раздела $sub с параметрами $params в виде параметров, подающихся на скрипты в строке URL';
const NETCAT_HINT_RTFM = 'Все доступные переменные и функции можно посмотреть в руководстве разработчика.';
const NETCAT_HINT_FUNCTION = 'Функции.';
const NETCAT_HINT_ARRAY = 'Хэш-массивы';
const NETCAT_HINT_VARS_IN_COMPONENT_SCOPE = 'Переменные, доступные во всех полях';
const NETCAT_HINT_VARS_IN_LIST_SCOPE = 'Переменные, доступные в списке объектов шаблона';
const NETCAT_HINT_FIELD_D = 'ДД';
const NETCAT_HINT_FIELD_M = 'ММ';
const NETCAT_HINT_FIELD_Y = 'ГГГГ';
const NETCAT_HINT_FIELD_H = 'чч';
const NETCAT_HINT_FIELD_MIN = 'мм';
const NETCAT_HINT_FIELD_S = 'сс';

const NETCAT_CUSTOM_ERROR_REQUIRED_INT = 'необходимо ввести целое число.';
const NETCAT_CUSTOM_ERROR_REQUIRED_FLOAT = 'необходимо ввести число.';
const NETCAT_CUSTOM_ERROR_MIN_VALUE = 'минимально число для ввода: %s.';
const NETCAT_CUSTOM_ERROR_MAX_VALUE = 'максимальное число для ввода: %s.';
const NETCAT_CUSTOM_PARAMETR_UPDATED = 'Изменения успешно сохранены';
const NETCAT_CUSTOM_PARAMETR_ADDED = 'Параметр успешно добавлен';
const NETCAT_CUSTOM_KEY = 'ключ';
const NETCAT_CUSTOM_VALUE = 'значение';
const NETCAT_CUSTOM_USETTINGS = 'Пользовательские настройки';
const NETCAT_CUSTOM_NONE_SETTINGS = 'Нет пользовательских настроек.';
const NETCAT_CUSTOM_ONCE_MAIN_SETTINGS = 'Основные параметры';
const NETCAT_CUSTOM_ONCE_FIELD_NAME = 'Название поля';
const NETCAT_CUSTOM_ONCE_FIELD_DESC = 'Описание';
const NETCAT_CUSTOM_ONCE_DEFAULT = 'Значение по умолчанию (когда значение не указано)';
const NETCAT_CUSTOM_ONCE_DONT_SHOW = 'не показывать в форме редактирования настроек';
const NETCAT_CUSTOM_ONCE_FIELD_INITIAL_VALUE_INFOBLOCK = 'Начальное значение при создании инфоблока';
const NETCAT_CUSTOM_ONCE_FIELD_INITIAL_VALUE_SUBDIVISION = 'Начальное значение при создании раздела';
const NETCAT_CUSTOM_ONCE_EXTEND = 'Дополнительные параметры';
const NETCAT_CUSTOM_ONCE_EXTEND_REGEXP = 'Регулярное выражение для проверки';
const NETCAT_CUSTOM_ONCE_EXTEND_ERROR = 'Текст в случае несоответствия регулярному выражению';
const NETCAT_CUSTOM_ONCE_EXTEND_SIZE_L = 'Длина поля для ввода';
const NETCAT_CUSTOM_ONCE_EXTEND_RESIZE_W = 'Ширина для авторесайза';
const NETCAT_CUSTOM_ONCE_EXTEND_RESIZE_H = 'Высота для авторесайза';
const NETCAT_CUSTOM_ONCE_EXTEND_VIZRED = 'Разрешить редактирование в визуальном редакторе';
const NETCAT_CUSTOM_ONCE_EXTEND_BR = 'Перенос строки - &lt;br>';
const NETCAT_CUSTOM_ONCE_EXTEND_SIZE_H = 'Высота поля для ввода';
const NETCAT_CUSTOM_ONCE_SAVE = 'Сохранить';
const NETCAT_CUSTOM_ONCE_ADD = 'Добавить';
const NETCAT_CUSTOM_ONCE_DROP = 'Удалить';
const NETCAT_CUSTOM_ONCE_DROP_SELECTED = 'Удалить выбранные';
const NETCAT_CUSTOM_ONCE_MANUAL_EDIT = 'Редактировать вручную';
const NETCAT_CUSTOM_ONCE_ERROR_FIELD_NAME = 'Название поля должно содержать только латинские буквы, цифры и знак подчеркивания';
const NETCAT_CUSTOM_ONCE_ERROR_CAPTION = 'Необходимо заполнить поле «Описание»';
const NETCAT_CUSTOM_ONCE_ERROR_FIELD_EXISTS = 'Такой параметр уже есть';
const NETCAT_CUSTOM_ONCE_ERROR_QUERY = 'Ошибка в sql-запросе';
const NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR = 'Классификатор %s не найден';
const NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR_EMPTY = 'Классификатор %s пуст';
const NETCAT_CUSTOM_TYPE = 'Тип';
const NETCAT_CUSTOM_SUBTYPE = 'Подтип';
const NETCAT_CUSTOM_EX_MIN = 'Минимальное значение';
const NETCAT_CUSTOM_EX_MAX = 'Максимальное значение';
const NETCAT_CUSTOM_EX_QUERY = 'SQL-запрос';
const NETCAT_CUSTOM_EX_CLASSIFICATOR = 'Список';
const NETCAT_CUSTOM_EX_ELEMENTS = 'Элементы';
const NETCAT_CUSTOM_TYPENAME_STRING = 'Строка';
const NETCAT_CUSTOM_TYPENAME_TEXTAREA = 'Текст';
const NETCAT_CUSTOM_TYPENAME_CHECKBOX = 'Логическая переменная';
const NETCAT_CUSTOM_TYPENAME_SELECT = 'Список';
const NETCAT_CUSTOM_TYPENAME_SELECT_SQL = 'Динамический';
const NETCAT_CUSTOM_TYPENAME_SELECT_STATIC = 'Статический';
const NETCAT_CUSTOM_TYPENAME_SELECT_CLASSIFICATOR = 'Классификатор';
const NETCAT_CUSTOM_TYPENAME_DIVIDER = 'Разделитель';
const NETCAT_CUSTOM_TYPENAME_INT = 'Целое число';
const NETCAT_CUSTOM_TYPENAME_FLOAT = 'Дробное число';
const NETCAT_CUSTOM_TYPENAME_DATETIME = 'Дата и время';
const NETCAT_CUSTOM_TYPENAME_REL = 'Связь с другой сущностью';
const NETCAT_CUSTOM_TYPENAME_REL_SUB = 'Связь с разделом';
const NETCAT_CUSTOM_TYPENAME_REL_CC = 'Связь с компонентом в разделе';
const NETCAT_CUSTOM_TYPENAME_REL_USER = 'Связь с пользователем';
const NETCAT_CUSTOM_TYPENAME_REL_CLASS = 'Связь с компонентом';
const NETCAT_CUSTOM_TYPENAME_FILE = 'Файл';
const NETCAT_CUSTOM_TYPENAME_FILE_ANY = 'Произвольный файл';
const NETCAT_CUSTOM_TYPENAME_FILE_IMAGE = 'Изображение';
const NETCAT_CUSTOM_TYPENAME_FILE_ICON = 'Иконка';
const NETCAT_CUSTOM_TYPENAME_COLOR = 'Цвет';
const NETCAT_CUSTOM_TYPENAME_CUSTOM = 'HTML-блок';

#exceptions
const NETCAT_EXCEPTION_CLASS_DOESNT_EXIST = 'Компонент %s не найден';
# errors
const NETCAT_ERROR_SQL = 'Ошибка в SQL-запросе.<br/>Запрос:<br/>%s<br/>Ошибка:<br/>%s';
const NETCAT_ERROR_DB_CONNECT = 'Фатальная ошибка: невозможно получить настройки системы. Проверьте, правильно ли указаны параметра доступа к базе данных.';
const NETCAT_ERROR_UNABLE_TO_DELETE_FILES = 'Не удалось удалить файл или директорию %s';

#openstat

# admin notice
const NETCAT_ADMIN_NOTICE_MORE = 'Подробнее.';
const NETCAT_ADMIN_NOTICE_PROLONG = 'Продлить.';
const NETCAT_ADMIN_NOTICE_LICENSE_ILLEGAL = 'Данная копия Netcat не является лицензионной.';
const NETCAT_ADMIN_NOTICE_LICENSE_MAYBE_ILLEGAL = 'Возможно, у Вас используется нелицензионная копия Netcat.';
const NETCAT_ADMIN_NOTICE_SECURITY_UPDATE_SYSTEM = 'Вышло важное обновление безопасности системы.';
const NETCAT_ADMIN_NOTICE_SUPPORT_EXPIRED = 'Срок технической поддержки для лицензии %s истек.';
define("NETCAT_ADMIN_NOTICE_CRON", "Вы давно не использовали инструмент \"Управление задачами\"." . (CMS_SYSTEM_NAME === 'Netcat' ? " <a href='https://netcat.ru/developers/docs/system-tools/task-management/' target='_blank'>Что это?</a>" : ''));
const NETCAT_ADMIN_NOTICE_RIGHTS = 'Неверно выставлены права на директорию';
define("NETCAT_ADMIN_NOTICE_SAFE_MODE", "Включён режим php safe_mode." . (CMS_SYSTEM_NAME === 'Netcat' ? " <a href='https://netcat.ru/adminhelp/safe-mode/' target='_blank'>Что это?</a>" : ''));
const NETCAT_ADMIN_DOMDocument_NOT_FOUND = 'PHP расширение DOMDocument не найдено, работа корзины невозможна';
const NETCAT_ADMIN_TRASH_OBJECT_HAS_BEEN_REMOVED = 'объект удален';
const NETCAT_ADMIN_TRASH_OBJECTS_REMOVED = 'объектов удалено';
const NETCAT_ADMIN_TRASH_OBJECT_IS_REMOVED = 'объекта удалено';
const NETCAT_ADMIN_TRASH_TRASH_HAS_BEEN_SUCCESSFULLY_CLEARNED = 'Корзина была успешно очищена';

const NETCAT_FILE_NOT_FOUND = 'Файл %s не найден';
const NETCAT_DIR_NOT_FOUND = 'Директория %s не найдена';

const NETCAT_TEMPLATE_FILE_NOT_FOUND = 'Файл шаблона не найден';
const NETCAT_TEMPLATE_DIR_DELETE_ERROR = 'Нельзя удалить всю папку %s';
const NETCAT_CAN_NOT_WRITE_FILE = 'Не могу записать файл';
const NETCAT_CAN_NOT_CREATE_FOLDER = 'Не могу создать папку для шаблона';


const NETCAT_ADMIN_AUTH_PERM = 'Ваши права:';
const NETCAT_ADMIN_AUTH_CHANGE_PASS = 'Изменить пароль';
const NETCAT_ADMIN_AUTH_LOGOUT = 'Выход';

const CONTROL_BUTTON_CANCEL = 'Отмена';

const NETCAT_MESSAGE_FORM_MAIN = 'Основное';
const NETCAT_MESSAGE_FORM_ADDITIONAL = 'Дополнительно';
const NETCAT_EVENT_IMPORTCATALOGUE = 'Импорт сайта';
const NETCAT_EVENT_ADDCATALOGUE = 'Добавление сайта';
const NETCAT_EVENT_ADDSUBDIVISION = 'Добавление раздела';
const NETCAT_EVENT_ADDSUBCLASS = 'Добавление компонента в раздел';
const NETCAT_EVENT_ADDCLASS = 'Добавление компонента';
const NETCAT_EVENT_ADDCLASSTEMPLATE = 'Добавление шаблона компонента';
const NETCAT_EVENT_ADDMESSAGE = 'Добавление объекта';
const NETCAT_EVENT_ADDSYSTEMTABLE = 'Добавление системной таблицы';
const NETCAT_EVENT_ADDTEMPLATE = 'Добавление макета';
const NETCAT_EVENT_ADDUSER = 'Добавление пользователя';
const NETCAT_EVENT_ADDCOMMENT = 'Добавление комментария';
const NETCAT_EVENT_UPDATECATALOGUE = 'Редактирование сайта';
const NETCAT_EVENT_UPDATESUBDIVISION = 'Редактирование раздела';
const NETCAT_EVENT_UPDATESUBCLASS = 'Редактирование компонента в разделе';
const NETCAT_EVENT_UPDATECLASS = 'Редактирование компонента';
const NETCAT_EVENT_UPDATECLASSTEMPLATE = 'Редактирование шаблона компонента';
const NETCAT_EVENT_UPDATEMESSAGE = 'Редактирование объекта';
const NETCAT_EVENT_UPDATESYSTEMTABLE = 'Редактирование системной таблицы';
const NETCAT_EVENT_UPDATETEMPLATE = 'Редактирование макета';
const NETCAT_EVENT_UPDATEUSER = 'Редактирование пользователя';
const NETCAT_EVENT_UPDATECOMMENT = 'Редактирование комментария';
const NETCAT_EVENT_DROPCATALOGUE = 'Удаление сайта';
const NETCAT_EVENT_DROPSUBDIVISION = 'Удаление раздела';
const NETCAT_EVENT_DROPSUBCLASS = 'Удаление инфоблока';
const NETCAT_EVENT_DROPCLASS = 'Удаление компонента';
const NETCAT_EVENT_DROPCLASSTEMPLATE = 'Удаление шаблона компонента';
const NETCAT_EVENT_DROPMESSAGE = 'Удаление сообщения';
const NETCAT_EVENT_DROPSYSTEMTABLE = 'Удаление системной таблицы';
const NETCAT_EVENT_DROPTEMPLATE = 'Удаление макета';
const NETCAT_EVENT_DROPUSER = 'Удаление пользователя';
const NETCAT_EVENT_DROPCOMMENT = 'Удаление комментария';
const NETCAT_EVENT_CHECKCOMMENT = 'Включение комментария';
const NETCAT_EVENT_UNCHECKCOMMENT = 'Выключение комментария';
const NETCAT_EVENT_CHECKMESSAGE = 'Включение объекта';
const NETCAT_EVENT_UNCHECKMESSAGE = 'Выключение объекта';
const NETCAT_EVENT_CHECKUSER = 'Включение пользователя';
const NETCAT_EVENT_UNCHECKUSER = 'Выключение пользователя';
const NETCAT_EVENT_CHECKSUBDIVISION = 'Включение раздела';
const NETCAT_EVENT_UNCHECKSUBDIVISION = 'Выключение раздела';
const NETCAT_EVENT_CHECKCATALOGUE = 'Включение сайта';
const NETCAT_EVENT_UNCHECKCATALOGUE = 'Выключение сайта';
const NETCAT_EVENT_CHECKSUBCLASS = 'Включение компонента в разделе';
const NETCAT_EVENT_UNCHECKSUBCLASS = 'Выключение компонента в разделе';
const NETCAT_EVENT_CHECKMODULE = 'Включение модуля';
const NETCAT_EVENT_UNCHECKMODULE = 'Выключение модуля';
const NETCAT_EVENT_AUTHORIZEUSER = 'Авторизация пользователя';
const NETCAT_EVENT_ADDWIDGETCLASS = 'Добавление виджет-компонента';
const NETCAT_EVENT_EDITWIDGETCLASS = 'Редактирование виджет-компонента';
const NETCAT_EVENT_DROPWIDGETCLASS = 'Удаление виджет-компонента';
const NETCAT_EVENT_ADDWIDGET = 'Добавление виджета';
const NETCAT_EVENT_EDITWIDGET = 'Редактирование виджета';
const NETCAT_EVENT_DROPWIDGET = 'Удаление виджета';
const NETCAT_EVENT_DROPFIELD = 'Удаление поля';
const NETCAT_EVENT_ADDFIELD = 'Добавление поля';
const NETCAT_EVENT_UPDATEFIELD = 'Редактирование поля';
const NETCAT_EVENT_DROPCLASSIFICATOR = 'Удаление списка';
const NETCAT_EVENT_ADDCLASSIFICATOR = 'Добавление списка';
const NETCAT_EVENT_UPDATECLASSIFICATOR = 'Редактирование списка';
const NETCAT_EVENT_DROPCLASSIFICATORITEM = 'Удаление элемента списка';
const NETCAT_EVENT_ADDCLASSIFICATORITEM = 'Добавление элемента списка';
const NETCAT_EVENT_UPDATECLASSIFICATORITEM = 'Редактирование элемента списка';
const NETCAT_EVENT_DROPTEMPLATEPARTIAL = 'Удаление врезки шаблона';
const NETCAT_EVENT_ADDTEMPLATEPARTIAL = 'Добавление врезки шаблона';
const NETCAT_EVENT_UPDATETEMPLATEPARTIAL = 'Редактирование врезки шаблона';
const NETCAT_EVENT_BLOCKUSER = 'Блокирование пользователя';
const NETCAT_EVENT_UNBLOCKUSER = 'Разблокирование пользователя';

const NETCAT_EVENT_IMPORTCATALOGUEPREP = 'Подготовка к импорту сайта';
const NETCAT_EVENT_ADDCATALOGUEPREP = 'Подготовка к добавлению сайта';
const NETCAT_EVENT_ADDSUBDIVISIONPREP = 'Подготовка к добавлению раздела';
const NETCAT_EVENT_ADDSUBCLASSPREP = 'Подготовка к добавлению компонента в раздел';
const NETCAT_EVENT_ADDCLASSPREP = 'Подготовка к добавлению компонента';
const NETCAT_EVENT_ADDCLASSTEMPLATEPREP = 'Подготовка к добавлению шаблона компонента';
const NETCAT_EVENT_ADDMESSAGEPREP = 'Подготовка к добавлению объекта';
const NETCAT_EVENT_ADDSYSTEMTABLEPREP = 'Подготовка к добавлению системной таблицы';
const NETCAT_EVENT_ADDTEMPLATEPREP = 'Подготовка к добавлению макета';
const NETCAT_EVENT_ADDUSERPREP = 'Подготовка к добавлению пользователя';
const NETCAT_EVENT_ADDCOMMENTPREP = 'Подготовка к добавлению комментария';
const NETCAT_EVENT_UPDATECATALOGUEPREP = 'Подготовка к редактированию сайта';
const NETCAT_EVENT_UPDATESUBDIVISIONPREP = 'Подготовка к редактированию раздела';
const NETCAT_EVENT_UPDATESUBCLASSPREP = 'Подготовка к редактированию компонента в разделе';
const NETCAT_EVENT_UPDATECLASSPREP = 'Подготовка к редактированию компонента';
const NETCAT_EVENT_UPDATECLASSTEMPLATEPREP = 'Подготовка к редактированию шаблона компонента';
const NETCAT_EVENT_UPDATEMESSAGEPREP = 'Подготовка к редактированию объекта';
const NETCAT_EVENT_UPDATESYSTEMTABLEPREP = 'Подготовка к редактированию системной таблицы';
const NETCAT_EVENT_UPDATETEMPLATEPREP = 'Подготовка к редактированию макета';
const NETCAT_EVENT_UPDATEUSERPREP = 'Подготовка к редактированию пользователя';
const NETCAT_EVENT_UPDATECOMMENTPREP = 'Подготовка к редактированию комментария';
const NETCAT_EVENT_DROPCATALOGUEPREP = 'Подготовка к удалению сайта';
const NETCAT_EVENT_DROPSUBDIVISIONPREP = 'Подготовка к удалению раздела';
const NETCAT_EVENT_DROPSUBCLASSPREP = 'Подготовка к удалению компонента в разделе';
const NETCAT_EVENT_DROPCLASSPREP = 'Подготовка к удалению компонента';
const NETCAT_EVENT_DROPCLASSTEMPLATEPREP = 'Подготовка к удалению шаблона компонента';
const NETCAT_EVENT_DROPMESSAGEPREP = 'Подготовка к удалению сообщения';
const NETCAT_EVENT_DROPSYSTEMTABLEPREP = 'Подготовка к удалению системной таблицы';
const NETCAT_EVENT_DROPTEMPLATEPREP = 'Подготовка к удалению макета';
const NETCAT_EVENT_DROPUSERPREP = 'Подготовка к удалению пользователя';
const NETCAT_EVENT_DROPCOMMENTPREP = 'Подготовка к удалению комментария';
const NETCAT_EVENT_CHECKCOMMENTPREP = 'Подготовка к включению комментария';
const NETCAT_EVENT_UNCHECKCOMMENTPREP = 'Подготовка к выключению комментария';
const NETCAT_EVENT_CHECKMESSAGEPREP = 'Подготовка к включению объекта';
const NETCAT_EVENT_UNCHECKMESSAGEPREP = 'Подготовка к выключению объекта';
const NETCAT_EVENT_CHECKUSERPREP = 'Подготовка к включению пользователя';
const NETCAT_EVENT_UNCHECKUSERPREP = 'Подготовка к выключению пользователя';
const NETCAT_EVENT_CHECKSUBDIVISIONPREP = 'Подготовка к включению раздела';
const NETCAT_EVENT_UNCHECKSUBDIVISIONPREP = 'Подготовка к выключению раздела';
const NETCAT_EVENT_CHECKCATALOGUEPREP = 'Подготовка к включению сайта';
const NETCAT_EVENT_UNCHECKCATALOGUEPREP = 'Подготовка к выключению сайта';
const NETCAT_EVENT_CHECKSUBCLASSPREP = 'Подготовка к включению компонента в разделе';
const NETCAT_EVENT_UNCHECKSUBCLASSPREP = 'Подготовка к выключению компонента в разделе';
const NETCAT_EVENT_CHECKMODULEPREP = 'Подготовка к включению модуля';
const NETCAT_EVENT_UNCHECKMODULEPREP = 'Подготовка к выключению модуля';
const NETCAT_EVENT_AUTHORIZEUSERPREP = 'Подготовка к авторизации пользователя';
const NETCAT_EVENT_ADDWIDGETCLASSPREP = 'Подготовка к добавлению виджет-компонента';
const NETCAT_EVENT_EDITWIDGETCLASSPREP = 'Подготовка к редактированию виджет-компонента';
const NETCAT_EVENT_DROPWIDGETCLASSPREP = 'Подготовка к удалению виджет-компонента';
const NETCAT_EVENT_ADDWIDGETPREP = 'Подготовка к добавлению виджета';
const NETCAT_EVENT_EDITWIDGETPREP = 'Подготовка к редактированию виджета';
const NETCAT_EVENT_DROPWIDGETPREP = 'Подготовка к удалению виджета';
const NETCAT_EVENT_DROPFIELDPREP = 'Подготовка к удалению поля';
const NETCAT_EVENT_ADDFIELDPREP = 'Подготовка к добавлению поля';
const NETCAT_EVENT_UPDATEFIELDPREP = 'Подготовка к редактированию поля';
const NETCAT_EVENT_DROPCLASSIFICATORPREP = 'Подготовка к удалению списка';
const NETCAT_EVENT_ADDCLASSIFICATORPREP = 'Подготовка к добавлению списка';
const NETCAT_EVENT_UPDATECLASSIFICATORPREP = 'Подготовка к редактированию списка';
const NETCAT_EVENT_DROPCLASSIFICATORITEMPREP = 'Подготовка к удалению элемента списка';
const NETCAT_EVENT_ADDCLASSIFICATORITEMPREP = 'Подготовка к добавлению элемента списка';
const NETCAT_EVENT_UPDATECLASSIFICATORITEMPREP = 'Подготовка к редактированию элемента списка';
const NETCAT_EVENT_DROPTEMPLATEPARTIALPREP = 'Подготовка к удалению врезки шаблона';
const NETCAT_EVENT_ADDTEMPLATEPARTIALPREP = 'Подготовка к добавлению врезки шаблона';
const NETCAT_EVENT_UPDATETEMPLATEPARTIALPREP = 'Подготовка к редактированию врезки шаблона';
const NETCAT_EVENT_BLOCKUSERPREP = 'Подготовка к блокированию пользователя';
const NETCAT_EVENT_UNBLOCKUSERPREP = 'Подготовка к разблокированию пользователя';

const TITLE_WEB = 'Обычный шаблон';
const TITLE_MOBILE = 'Мобильный шаблон';
const TITLE_RESPONSIVE = 'Адаптивный шаблон';
const TITLE_OLD = 'Обычный шаблон v4';

const TOOLS_PATCH_INSTALL_ONLINE_NOTIFY = 'Перед установкой обновления настоятельно рекомендуется сделать резервную копию системы. Запустить процесс обновления?';
const TOOLS_PATCH_INFO_INSTALL = 'Установить обновление';
const TOOLS_PATCH_INFO_SYSTEM_MESSAGE = "Добавлено новое системное сообщение, <a href='%LINK'>читать сообщение</a>.";
define("TOOLS_PATCH_ERROR_SET_FILEPERM_IN_HTTP_ROOT_PATH", "Установите права на запись для ВСЕХ файлов в папке $nc_core->HTTP_ROOT_PATH.<br />(%FILE недоступен для записи)");
define("TOOLS_PATCH_ERROR_SET_DIRPERM_IN_HTTP_ROOT_PATH", "Установите права на запись для папки $nc_core->HTTP_ROOT_PATH и всех поддиректорий.<br />(%DIR недоступна для записи)");
const TOOLS_PATCH_ERROR_UNCORRECT_PHP_VERSION = 'Для работы системы после обновления требуется версия PHP %NEED, текущая версия PHP %CURRENT.';

const SQL_CONSTRUCT_TITLE = 'Конструктор запросов';
const SQL_CONSTRUCT_CHOOSE_OP = 'Выберите операцию';
const SQL_CONSTRUCT_SELECT_TABLE = 'Выбрать данные из таблицы';
const SQL_CONSTRUCT_SELECT_CC = 'Выбрать данные из компонента';
const SQL_CONSTRUCT_ENTER_CODE = 'Ввести код активации и номер лицензии';
const SQL_CONSTRUCT_VIEW_SETTINGS = 'Посмотреть настройки системы';
const SQL_CONSTRUCT_TABLE_NAME = 'Название таблицы';
const SQL_CONSTRUCT_FIELDS = 'Поля';
const SQL_CONSTRUCT_FIELDS_NOTE = '(опционально, через запятую, без пробелов)';
const SQL_CONSTRUCT_CC_ID = 'ID компонента';
const SQL_CONSTRUCT_REGNUM = 'Номер лицензии';
const SQL_CONSTRUCT_REGCODE = 'Активационный код';
const SQL_CONSTRUCT_CHOOSE_MOD = 'Выберите модуль';
const SQL_CONSTRUCT_GENERATE = 'Сгенерировать запрос';

const NETCAT_MAIL_ATTACHMENT_FORM_ATTACHMENTS = 'Вложения:';
const NETCAT_MAIL_ATTACHMENT_FORM_DELETE = 'Удалить';
const NETCAT_MAIL_ATTACHMENT_FORM_FILENAME = 'Название файла:';
const NETCAT_MAIL_ATTACHMENT_FORM_ADD = 'Добавить еще';

const NETCAT_DATEPICKER_CALENDAR_DATE_FORMAT = 'dd.mm.yyyy';
const NETCAT_DATEPICKER_CALENDAR_DAYS = 'Воскресенье Понедельник Вторник Среда Четверг Пятница Суббота Воскресенье';
const NETCAT_DATEPICKER_CALENDAR_DAYS_SHORT = 'Вск Пнд Втр Срд Чтв Птн Суб Вск';
const NETCAT_DATEPICKER_CALENDAR_DAYS_MIN = 'Вс Пн Вт Ср Чт Пт Сб Вс';
const NETCAT_DATEPICKER_CALENDAR_MONTHS = 'Январь Февраль Март Апрель Май Июнь Июль Август Сентябрь Октябрь Ноябрь Декабрь';
const NETCAT_DATEPICKER_CALENDAR_MONTHS_SHORT = 'Янв Фев Мар Апр Май Июн Июл Авг Сен Окт Ноя Дек';
const NETCAT_DATEPICKER_CALENDAR_TODAY = 'Сегодня';

const TOOLS_CSV = 'Экспорт/импорт CSV';
const TOOLS_CSV_EXPORT = 'Экспорт CSV';
const TOOLS_CSV_IMPORT = 'Импорт CSV';
const TOOLS_CSV_EXPORT_TYPE = 'Тип экспорта';
const TOOLS_CSV_EXPORT_TYPE_SUBCLASS = 'Из инфоблока';
const TOOLS_CSV_EXPORT_TYPE_COMPONENT = 'Из компонента';
const TOOLS_CSV_SELECT_SITE = 'Выберите сайт';
const TOOLS_CSV_SELECT_SUBDIVISION = 'Выберите подраздел';
const TOOLS_CSV_SELECT_SUBCLASS = 'Выберите инфоблок';
const TOOLS_CSV_SELECT_COMPONENT = 'Выберите компонент';
const TOOLS_CSV_SUBCLASSES_NOT_FOUND = 'Не найдено подходящих инфоблоков';
const TOOLS_CSV_NOT_SELECTED = 'Не выбрано';
const TOOLS_CSV_CREATE_EXPORT = 'Экспорт';
const TOOLS_CSV_RECORD_ID = 'Идентификатор записи в файле';
const TOOLS_CSV_PARENT_RECORD_ID = 'Идентификатор записи-родителя';

const TOOLS_CSV_SELECT_SETTINGS = 'Параметры CSV';

const TOOLS_CSV_OPT_ENCLOSED = 'Значения полей обрамлены';
const TOOLS_CSV_OPT_ESCAPED = 'Символ экранирования';
const TOOLS_CSV_OPT_SEPARATOR = 'Разделитель полей';
const TOOLS_CSV_OPT_CHARSET = 'Кодировка';
const TOOLS_CSV_OPT_CHARSET_UTF8 = 'Юникод (utf-8)';
const TOOLS_CSV_OPT_CHARSET_CP1251 = 'Microsoft Excel (windows-1251)';
const TOOLS_CSV_OPT_NULL = 'Заменить NULL на';
const TOOLS_CSV_OPT_LISTS = '<nobr>Значения полей типа «Список»</nobr>';
const TOOLS_CSV_OPT_LISTS_NAME = 'название элемента';
const TOOLS_CSV_OPT_LISTS_VALUE = 'дополнительное значение (поле.Value)';
const TOOLS_CSV_EXPORT_DONE = 'Экспорт завершен. Вы можете скачать файл по ссылке <a href="%s" target="_blank">%s</a>. Чтобы удалить файл, нажмите <a href="%s" target="_top">здесь</a>.';

const TOOLS_CSV_MAPPING_HEADER = 'Соответствия полей';
const TOOLS_CSV_IMPORT_FILE = 'Файл импорта (*.csv)';
const TOOLS_CSV_IMPORT_RUN = 'Импортировать';
const TOOLS_CSV_IMPORT_FILE_NOT_FOUND = 'Файл для импорта не найден';
const TOOLS_CSV_IMPORT_COLUMN_COUNT_MISMATCH = 'Строка %d не была импортирована из-за неправильного количества колонок (в заголовке файла&nbsp;&mdash; %d, в пропущенной строке&nbsp;&mdash; %d).';

const TOOLS_CSV_COMPONENT_FIELD = 'Поле компонента';
const TOOLS_CSV_FILE_FIELD = 'Поле CSV-файла';
const TOOLS_CSV_FINISHED_HEADER = 'Импорт завершился';
const TOOLS_CSV_EXPORT_FINISHED_HEADER = 'Экспорт завершился';
const TOOLS_CSV_IMPORT_SUCCESS = 'Импорт завершился, импортировано записей: ';
const TOOLS_CSV_DELETE_FINISHED_HEADER = 'Удаление файла';
const TOOLS_CSV_DELETE_FINISHED = 'Файл удален. <a href="%s" target="_top">Нажмите, чтобы вернуться</a>';
const TOOLS_CSV_IMPORT_HISTORY = 'История Импорта';
const TOOLS_CSV_HISTORY_ID = 'ID';
const TOOLS_CSV_HISTORY_CREATED = 'Создан';
const TOOLS_CSV_HISTORY_ROLLBACK = 'Откатить';
const TOOLS_CSV_HISTORY_EMPTY = 'История импорта пуста';
const TOOLS_CSV_HISTORY_CLASS_NAME = 'Раздел';
const TOOLS_CSV_HISTORY_ROWS = 'Записей';
const TOOLS_CSV_HISTORY_ROLLBACKED = 'Отменено';
const TOOLS_CSV_ROLLBACK_FINISHED_HEADER = 'Отмена импорта завершена';
const TOOLS_CSV_ROLLBACK_SUCCESS = 'Отмена импорта завершена успешно. Отменено записей: ';


const WELCOME_SCREEN_TOOLTIP_SUPPORT = 'В случае затруднений, можно обратиться к документации или получить ответ от техподдержки.';
const WELCOME_SCREEN_TOOLTIP_SIDEBAR = 'Основные настройки сайта осуществляется через панель управления сайтом.';
const WELCOME_SCREEN_TOOLTIP_SIDEBAR_SUBS = 'Когда вы <a href="#site.add">создадите сайт</a>, здесь будут показаны разделы, из которых он состоит. Черные повторяют структуру сайта, а серые несут служебный характер и напрямую не отображаются на сайте.';
const WELCOME_SCREEN_TOOLTIP_TRASH_WIDGET = 'Для ускорения работы вы можете настраивать виджеты. Например, в «Корзине» можно восстановить удаленные объекты.';
define('WELCOME_SCREEN_MODAL_TEXT', '<h2>Добро пожаловать в систему управления сайтами ' . CMS_SYSTEM_NAME . '!</h2>
    <p>Для вашего удобства мы собрали самые важные операции на отдельной странице — <b>панели управления сайтом.</b> Попасть на нее можно кликнув на название вашего сайта в «дереве» слева.</p>
    <p>Более глубокие настройки производятся в соответствующих разделах административного интерфейса.</p>
    <p>Большое спасибо за использование нашей системы и <b>удачи в работе.</b></p>');
const WELCOME_SCREEN_BTN_START = 'Начать работу';

const NETCAT_FILTER_FIELD_MESSAGE_ID = 'ID записи';
const NETCAT_FILTER_FIELD_CREATED = 'Время создания';
const NETCAT_FILTER_FIELD_LAST_UPDATED = 'Время редактирования';

const NETCAT_FIELD_VALUE_INHERITED_FROM_SUBDIVISION = 'Значение поля наследуется из раздела «%s»';
const NETCAT_FIELD_VALUE_INHERITED_FROM_CATALOGUE = 'Значение поля наследуется из <a href="%s" target="_top">свойств сайта</a>';
const NETCAT_FIELD_VALUE_INHERITED_FROM_TEMPLATE = 'Значение поля наследуется из макета «%s»';
const NETCAT_FIELD_FILE_ICON_SELECT = 'Выбрать';
const NETCAT_FIELD_FILE_ICON_ICON = 'иконку';
const NETCAT_FIELD_FILE_ICON_OR = 'или';
const NETCAT_FIELD_FILE_ICON_FILE = 'файл';

const NETCAT_USER_BREAK_ATTRIBUTE_NAMING_CONVENTION = 'Некоторые из имен атрибутов нарушают <a href="https://www.w3.org/TR/html-markup/syntax.html#syntax-attributes" target="_blank">конвенцию именования</a> и были проигнорированы: %s.';

const NETCAT_SECURITY_SETTINGS_SAVED = 'Настройки сохранены';
const NETCAT_SECURITY_SETTINGS_USE_DEFAULT = 'использовать <a href="%s" target="_top">общие настройки для всех сайтов</a>';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER = 'Фильтр входящих данных';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE = 'Действие при обнаружении входящего параметра, используемого для&nbsp;инъекции';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_DISABLED = 'отключено (не проверять входящие данные)';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_LOG_ONLY = 'не выполнять действий на странице';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_RELOAD_ESCAPE_INPUT = 'экранировать параметр и перезагрузить страницу';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_RELOAD_REMOVE_INPUT = 'сбросить параметр и перезагрузить страницу';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_EXCEPTION = 'остановить выполнение скрипта';

const NETCAT_SECURITY_FILTER_NO_TOKENIZER = 'Код PHP не будет проверяться, так как отключено расширение <i>tokenizer</i>.';
const NETCAT_SECURITY_FILTER_EMAIL_ENABLED = 'Высылать письмо при срабатывании фильтра на электронную почту';
const NETCAT_SECURITY_FILTER_SMS_ENABLED = 'Высылать оповещение при срабатывании фильтра на телефон';
const NETCAT_SECURITY_FILTER_SMS_PLACEHOLDER = 'Номера телефонов, разделенные запятой';
const NETCAT_SECURITY_FILTER_EMAIL_PLACEHOLDER = 'Адрес электронной почты';
const NETCAT_SECURITY_FILTER_EMAIL_SUBJECT = 'срабатывание фильтра входящих данных';
const NETCAT_SECURITY_FILTER_EMAIL_PREFIX = 'На странице %s сработал фильтр входящих данных (%s).';
const NETCAT_SECURITY_FILTER_SMS_PREFIX = 'На странице %s сработал фильтр входящих данных.';
const NETCAT_SECURITY_FILTER_EMAIL_INPUT_VALUE = 'Значение входящего параметра %s';
const NETCAT_SECURITY_FILTER_EMAIL_CHECKED_STRING = 'Строка, в которой найдено неэкранированное значение';
const NETCAT_SECURITY_FILTER_EMAIL_IP = 'IP-адрес, с которого выполнен запрос';
const NETCAT_SECURITY_FILTER_EMAIL_URL = 'Адрес страницы';
const NETCAT_SECURITY_FILTER_EMAIL_REFERER = 'Адрес ссылающейся страницы';
const NETCAT_SECURITY_FILTER_EMAIL_GET = 'GET-параметры';
const NETCAT_SECURITY_FILTER_EMAIL_POST = 'POST-параметры';
const NETCAT_SECURITY_FILTER_EMAIL_BACKTRACE = 'Стек вызовов';
const NETCAT_SECURITY_FILTER_EMAIL_SUFFIX = 'Рекомендуем незамедлительно предпринять меры для исправления данной проблемы, чтобы устранить угрозу взлома сайта через данную уязвимость.';

const NETCAT_SECURITY_SETTINGS_AUTH = 'Защита входа в систему';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA = 'CAPTCHA';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_RECOMMEND_DEFAULT = '(рекомендуем использовать одинаковые настройки на всех сайтах)';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_MODE_DISABLED = 'отключена';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_MODE_ALWAYS = 'показывать CAPTCHA всегда';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_MODE_COUNT = 'показывать CAPTCHA после неправильного ввода логина или пароля';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_ATTEMPTS = 'число попыток без CAPTCHA';

const NETCAT_SECURITY_LOG = 'Лог срабатывания фильтра';
const NETCAT_SECURITY_LOG_NO_DATA = 'Данных о срабатывании фильтра нет';
const NETCAT_SECURITY_SETTINGS_SAVE = 'Применить';
const NETCAT_SECURITY_LOG_REFRESH = 'Обновить';
const NETCAT_SECURITY_FILTER_ID = 'ID';
const NETCAT_SECURITY_FILTER_TIME = 'Дата, время';
const NETCAT_SECURITY_FILTER_TYPE = 'Тип';
const NETCAT_SECURITY_FILTER_URL = 'URL';
const NETCAT_SECURITY_FILTER_REFERER = 'Реферер';
const NETCAT_SECURITY_FILTER_IP = 'IP';
const NETCAT_SECURITY_LOG_FILTER = 'Фильтр';
const NETCAT_SECURITY_FILTER_HASH = 'Хэш (точное совпадение)';
const NETCAT_SECURITY_FILTER_GROUP_BY_SOURCE = 'Группировать по возможной инъекции';
const NETCAT_SECURITY_FILTER_LAST_DATE = 'Дата последнего инцидента';
const NETCAT_SECURITY_FILTER_SOURCE = 'Источник';
const NETCAT_SECURITY_FILTER_STRING = 'Строка';
const NETCAT_SECURITY_FILTER_INCIDENT_COUNT = 'Количество инцидентов';
const NETCAT_SECURITY_LOG_GOBACK = 'Назад';
const NETCAT_SECURITY_LOG_CLEAR_LABEL = 'Очистка лога';
const NETCAT_SECURITY_LOG_CLEAR = 'Очистить лог';
const NETCAT_SECURITY_ONE_DATE_NOT_SET = 'Одно из полей даты не заполнено!';
const NETCAT_SECURITY_TWO_DATES_NOT_SET = 'Заполните оба поля даты' ;
const NETCAT_SECURITY_INCIDENT_NUMBER = 'Инцидент №' ;
const NETCAT_SECURITY_INCIDENT_DATA_TIME = 'Дата/время';
const NETCAT_SECURITY_INCIDENT_TYPE = 'Тип сработавшего фильтра входящих данных';
const NETCAT_SECURITY_INCIDENT_CHECK_TYPE = 'Тип проверки';
const NETCAT_SECURITY_INCIDENT_SITE = 'Сайт';
const NETCAT_SECURITY_INCIDENT_POST = 'Содержимое $_POST';
const NETCAT_SECURITY_INCIDENT_BACKTRACE = 'Место срабатывания (backtrace)';
const NETCAT_SECURITY_INCIDENT_IP = 'IP-адрес клиента';
const NETCAT_SECURITY_INCIDENT_CHECKEDSTRING = 'Строка, в которой найдена возможная инъекция';
const NETCAT_SECURITY_INCIDENT_SOURCE = 'Источник возможной инъекции';
const NETCAT_SECURITY_INCIDENT_VALUE = 'Возможная инъекция';
const NETCAT_SECURITY_INCIDENT_SEND_ADMIN = 'Уведомление отправлено администратору';
const NETCAT_SECURITY_INCIDENT_COUNT_ANALOGUE = 'Количество таких же инцидентов';
const NETCAT_SECURITY_INCIDENT_SEND_ADMIN_YES = 'Да';
const NETCAT_SECURITY_INCIDENT_SEND_ADMIN_NO = 'Нет';
const NETCAT_SECURITY_INCIDENT_SEND_ADMIN_NO_ALREADY_SENT = 'Нет, так как аналогичное срабатывание фильтра было ранее';
const NETCAT_SECURITY_INCIDENT_VARILABLE = 'инъекция переменной';
const NETCAT_SECURITY_INCIDENT_VARILABLE_FUNCTION = 'возможная инъекция вызова функции';
const NETCAT_SECURITY_INCIDENT_QUOTE = 'инъекция PHP-кода';
const NETCAT_SECURITY_INCIDENT_STATEMENT= 'инъекция SQL-кода';
const NETCAT_SECURITY_INCIDENT_COMMENT = 'инъекция комментария';
const NETCAT_SECURITY_INCIDENT_DANGEROUS_TAG = 'инъекция тэга script или style';
const NETCAT_SECURITY_INCIDENT_ATTRIBUTE = 'инъекция атрибута тэга';
const NETCAT_SECURITY_INCIDENT_JAVASCRIPT = 'возможная инъекция Javascript-кода';
const NETCAT_SECURITY_INCIDENT_DATE_FILTER = "Дата";
const NETCAT_SECURITY_INCIDENT_DATE_FILTER_FROM = "с";
const NETCAT_SECURITY_INCIDENT_DATE_FILTER_TO = "по";
const NETCAT_SECURITY_INCIDENT_FILTER_SUBMIT = 'Искать';
const NETCAT_SECURITY_INCIDENT_FILTER_RESET = 'Очистить форму';
const NETCAT_SECURITY_INCIDENT_FILTER_RESET_CONFIRM = 'Вы уверены, что хотите очистить форму?';
const NETCAT_SECURITY_INCIDENT_FILTER_FROM = 'с';
const NETCAT_SECURITY_INCIDENT_FILTER_TO = 'по';


const NETCAT_SECURITY_SETTINGS_2FA = 'Двухфакторная аутентификация';
const NETCAT_SECURITY_SETTINGS_2FA_MODE_DISABLED = 'отключена';
const NETCAT_SECURITY_SETTINGS_2FA_MODE_FOR_SUPERVISORS = 'требовать для пользователей с правами супервизора и директора';
const NETCAT_SECURITY_SETTINGS_2FA_MODE_FOR_ADMIN_MODE_USERS = 'требовать для пользователей с доступом в панель управления';
const NETCAT_SECURITY_SETTINGS_2FA_MODE_FOR_EDITORS = 'требовать для пользователей с правами модератора и выше';
const NETCAT_SECURITY_SETTINGS_2FA_MAX_ATTEMPTS_ALLOW = 'Позволять вводить неправильный код не более';
const NETCAT_SECURITY_SETTINGS_2FA_MAX_ATTEMPTS_TIMES = 'раз';
const NETCAT_SECURITY_SETTINGS_2FA_MAX_THEN_BLOCK = 'после чего блокировать пользователя';
const NETCAT_SECURITY_SETTINGS_2FA_UNBLOCK_MANUALLY = 'до разблокировки вручную';
const NETCAT_SECURITY_SETTINGS_2FA_UNBLOCK_IN = 'на';
const NETCAT_SECURITY_SETTINGS_2FA_MINUTES = 'минут';
const NETCAT_SECURITY_SETTINGS_2FA_SECONDS = 'секунд';
const NETCAT_SECURITY_SETTINGS_2FA_VALIDITY = 'Срок действия кода';
const NETCAT_SECURITY_SETTINGS_2FA_REFRESH_IN = 'Повторная отправка кода доступна через';
const NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_USER = 'доступно для редактирования пользователями';
const NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_ADMIN = 'доступно для редактирования администраторами';
const NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_NO_ONE = 'недоступно для редактирования';
const NETCAT_SECURITY_SETTINGS_2FA_VALUE_NOT_SET = 'не указан';
const NETCAT_SECURITY_SETTINGS_2FA_METHOD = 'Способ проверки';
const NETCAT_SECURITY_SETTINGS_2FA_ALLOW_SET_ON_LOGON_WITHIN = 'в течение';
const NETCAT_SECURITY_SETTINGS_2FA_ALLOW_SET_ON_LOGON_WITHIN_DAYS = 'дней';
const NETCAT_SECURITY_SETTINGS_2FA_USER_LIST = 'Пользователи, для которых после сохранения настроек будет включена двухфакторная аутентификация';
const NETCAT_SECURITY_SETTINGS_2FA_USER_HAS_NO_VALUE = 'В вашем профиле поле «%s» пустое. Для включения двухфакторной аутентификации <a href="%s" target="_top">заполните его</a>.';
const NETCAT_SECURITY_SETTINGS_2FA_WRONG_CODE = 'Неправильный код';
// method_email
const NETCAT_SECURITY_SETTINGS_2FA_EMAIL_FIELD = 'Поле в таблице «Пользователи» с адресом электронной почты';
const NETCAT_SECURITY_SETTINGS_2FA_EMAIL_NO_POSSIBLE_FIELD = 'Необходимо <a href="%s" target="_top">добавить в таблицу «Пользователи» поле</a> типа «строка» с форматом «email».';
const NETCAT_SECURITY_SETTINGS_2FA_EMAIL_ADD_FIELD = 'Если нужного поля нет, <a href="%s" target="_top">добавьте в таблицу «Пользователи» поле</a> типа «строка» с форматом «email».';
// method_sms
const NETCAT_SECURITY_SETTINGS_2FA_SMS_DISABLED = 'Отправка SMS недоступна. <a href="%s" target="_top">Настройте модуль «Отправка сообщений»</a> для корректной работы двухфакторной аутентификации.';
const NETCAT_SECURITY_SETTINGS_2FA_SMS_FIELD = 'Поле в таблице «Пользователи» с номером телефона';
const NETCAT_SECURITY_SETTINGS_2FA_SMS_NO_POSSIBLE_FIELD = 'Необходимо <a href="%s" target="_top">добавить в таблицу «Пользователи» поле</a> типа «строка» с форматом «телефон».';
const NETCAT_SECURITY_SETTINGS_2FA_SMS_ADD_FIELD = 'Если нужного поля нет, <a href="%s" target="_top">добавьте в таблицу «Пользователи» поле</a> типа «строка» с форматом «телефон».';
const NETCAT_SECURITY_SETTINGS_2FA_SMS_ALLOW_SET_ON_LOGON = 'разрешать пользователям самим вводить телефон при первом входе';
// method_totp
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_QR = 'Отсканируйте QR-код на телефоне в приложении для аутентификации (Google Authenticator, Яндекс Ключ, FreeOTP или другое для работы с одноразовыми паролями TOTP)';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_SECRET = 'Секретный ключ (для добавления вручную)';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_ENTER_CODE = 'Введите код из приложения';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_WRONG_CODE = 'Неправильный код. Попробуйте ещё раз. Если ошибка сохраняется, проверьте синхронизацию времени в приложении и на сервере.';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_MAKE_NEW = 'пересоздать секретный ключ для текущего пользователя и показать QR-код для добавления его в приложение';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_NEED_SECRET = 'Для входа на сайт необходимо ввести код';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_USE_APP = 'Для последующих входов на сайт потребуется код из выбранного вами приложения.';

// Используется в админке (форма изменения пользователя):
const NETCAT_USER_2FA_BLOCKED = 'Пользователь заблокирован из-за неправильного ввода кода';
const NETCAT_USER_2FA_BLOCKED_UNTIL = 'до';
const NETCAT_USER_2FA_UNBLOCK = 'разблокировать';

// Используются на пользовательской части сайта:
const NETCAT_2FA_ENTER_CODE = 'Введите код';
const NETCAT_2FA_SEND_AGAIN = 'Получить новый код';
const NETCAT_2FA_ERROR = 'Ошибка';
const NETCAT_2FA_REFRESH_IN = 'можно будет через';
const NETCAT_2FA_REFRESH_HINT = 'Попробуйте повторить отправку кода ещё раз или обратитесь к администратору сайта.';
const NETCAT_2FA_ATTEMPTS_LEFT = 'Осталась %s попытка/Осталось %s попытки/Осталось %s попыток';
const NETCAT_2FA_BLOCK_WARNING = 'Если вы введёте код неправильно, ваша учётная запись будет заблокирована';
const NETCAT_2FA_BLOCK_FOR = 'на %s минуту/на %s минуты/на %s минут';
const NETCAT_2FA_BLOCKED_ADMIN = 'Доступ в панель управления сайтом заблокирован %s из-за повторных неправильных вводов кода.';
const NETCAT_2FA_BLOCKED_PERMANENTLY = 'Обратитесь к администратору сайта, чтобы разблокировать доступ.';
const NETCAT_2FA_CODE_VALID_FOR = 'Код действителен в течение';
const NETCAT_2FA_CODE_NOT_VALID = 'Высланный ранее код больше не действителен. Получите новый код.';
// method_email
const NETCAT_2FA_EMAIL_NO_ADDRESS = 'Не указан адрес электронной почты для получения кода';
const NETCAT_2FA_EMAIL_SUBJECT = 'Код для сайта %s';
const NETCAT_2FA_EMAIL_BODY = "<p>Ваш код для сайта %s:</p>\n<h1>%s</h1>";
const NETCAT_2FA_EMAIL_IS_SENT = 'Код отправлен на электронную почту %s';
const NETCAT_2FA_EMAIL_NOT_SENT = 'Не удалось отправить код на электронную почту %s.';
// method_sms
const NETCAT_2FA_SMS_NO_PHONE = 'Не указан телефон для получения кода. Обратитесь к администратору сайта.';
const NETCAT_2FA_SMS_MESSAGE = '%s - код для %s';
const NETCAT_2FA_SMS_IS_SENT = 'Код отправлен на телефон %s';
const NETCAT_2FA_SMS_NOT_SENT = 'Не удалось отправить код на телефон %s.';
// method_totp
const NETCAT_2FA_TOTP = 'Приложение (TOTP)';
const NETCAT_2FA_TOTP_APP_HINT = 'Введите код из приложения';


// _CONDITION_
const NETCAT_CONDITION_DATETIME_FORMAT = 'd.m.Y H:i';
const NETCAT_CONDITION_DATE_FORMAT = 'd.m.Y';

// Фрагменты для составления текстового описания условий
const NETCAT_COND_OP_EQ = '%s';
const NETCAT_COND_OP_EQ_IS = '— %s';
const NETCAT_COND_OP_NE = 'не %s';
const NETCAT_COND_OP_GT = 'более %s';
const NETCAT_COND_OP_GE = 'не менее %s';
const NETCAT_COND_OP_LT = 'менее %s';
const NETCAT_COND_OP_LE = 'не более %s';
const NETCAT_COND_OP_GT_DATE = 'позднее %s';
const NETCAT_COND_OP_GE_DATE = 'не ранее %s';
const NETCAT_COND_OP_LT_DATE = 'ранее %s';
const NETCAT_COND_OP_LE_DATE = 'позднее %s';
const NETCAT_COND_OP_CONTAINS = 'содержит «%s»';
const NETCAT_COND_OP_NOTCONTAINS = 'не содержит «%s»';
const NETCAT_COND_OP_BEGINS = 'начинается с «%s»';

const NETCAT_COND_QUOTED_VALUE = '«%s»';
const NETCAT_COND_OR = ', или '; // spaces are important
const NETCAT_COND_AND = '; ';
const NETCAT_COND_OR_SAME = ', ';
const NETCAT_COND_AND_SAME = ' и ';
const NETCAT_COND_DUMMY = '(тип условий, недоступный в текущей редакции)';
const NETCAT_COND_DATE_FROM = 'с';
const NETCAT_COND_DATE_TO = 'по';
const NETCAT_COND_TIME_INTERVAL = '%s&#x200A;—&#x200A;%s';
const NETCAT_COND_BOOLEAN_TRUE = '«истина»';
const NETCAT_COND_BOOLEAN_FALSE = '«ложь»';
const NETCAT_COND_DAYOFWEEK_ON_LIST = 'в понедельник/во вторник/в среду/в четверг/в пятницу/в субботу/в воскресенье';
const NETCAT_COND_DAYOFWEEK_EXCEPT_LIST = 'кроме понедельника/кроме вторника/кроме среды/кроме четверга/кроме пятницы/кроме субботы/кроме воскресенья';
const NETCAT_COND = 'Условия: ';

const NETCAT_COND_NONEXISTENT_COMPONENT = '[НЕСУЩЕСТВУЮЩИЙ КОМПОНЕНТ]';
const NETCAT_COND_NONEXISTENT_FIELD = '[ОШИБКА В УСЛОВИИ: ПОЛЕ НЕ СУЩЕСТВУЕТ]';
const NETCAT_COND_NONEXISTENT_VALUE = '[НЕСУЩЕСТВУЮЩЕЕ ЗНАЧЕНИЕ]';
const NETCAT_COND_NONEXISTENT_SUB = '[НЕСУЩЕСТВУЮЩИЙ РАЗДЕЛ]';
const NETCAT_COND_NONEXISTENT_ITEM = '[НЕСУЩЕСТВУЮЩИЙ ОБЪЕКТ]';

// Строки, используемые в редакторе условий
const NETCAT_CONDITION_FIELD = 'Условия выборки из других блоков';
const NETCAT_CONDITION_AND = 'и';
const NETCAT_CONDITION_OR = 'или';
const NETCAT_CONDITION_AND_DESCRIPTION = 'Все условия верны:';
const NETCAT_CONDITION_OR_DESCRIPTION = 'Любое из условий верно:';
const NETCAT_CONDITION_REMOVE_GROUP = 'Удалить группу условий';
const NETCAT_CONDITION_REMOVE_CONDITION = 'Удалить условие';
const NETCAT_CONDITION_REMOVE_ALL_CONFIRMATION = 'Удалить все условия?';
const NETCAT_CONDITION_REMOVE_GROUP_CONFIRMATION = 'Удалить группу условий?';
const NETCAT_CONDITION_REMOVE_CONDITION_CONFIRMATION = 'Удалить условие «%s»?';
const NETCAT_CONDITION_ADD = 'Добавить...';
const NETCAT_CONDITION_ADD_GROUP = 'Добавить группу условий';

const NETCAT_CONDITION_EQUALS = 'равно';
const NETCAT_CONDITION_NOT_EQUALS = 'не равно';
const NETCAT_CONDITION_LESS_THAN = 'менее';
const NETCAT_CONDITION_LESS_OR_EQUALS = 'не более';
const NETCAT_CONDITION_GREATER_THAN = 'более';
const NETCAT_CONDITION_GREATER_OR_EQUALS = 'не менее';
const NETCAT_CONDITION_CONTAINS = 'содержит';
const NETCAT_CONDITION_NOT_CONTAINS = 'не содержит';
const NETCAT_CONDITION_BEGINS_WITH = 'начинается с';
const NETCAT_CONDITION_TRUE = 'да';
const NETCAT_CONDITION_FALSE = 'нет';
const NETCAT_CONDITION_INCLUSIVE = 'включительно';

const NETCAT_CONDITION_SELECT_CONDITION_TYPE = 'выберите тип условия';
const NETCAT_CONDITION_SEARCH_NO_RESULTS = 'Не найдено: ';

const NETCAT_CONDITION_GROUP_OBJECTS = 'Параметры объекта'; // 'Свойства объекта'

const NETCAT_CONDITION_TYPE_OBJECT = 'Объект';
const NETCAT_CONDITION_SELECT_OBJECT = 'выберите объект';
const NETCAT_CONDITION_NONEXISTENT_ITEM = '(Несуществующий объект)';
const NETCAT_CONDITION_ITEM_WITHOUT_NAME = 'Объект без названия';
const NETCAT_CONDITION_ITEM_SELECTION = 'Выбор объекта';
const NETCAT_CONDITION_DIALOG_CANCEL_BUTTON = 'Отмена';
const NETCAT_CONDITION_DIALOG_SELECT_BUTTON = 'Выбрать';
const NETCAT_CONDITION_SUBDIVISION_HAS_LIST_NO_COMPONENTS_OR_OBJECTS = 'В выбранном разделе отсутствуют подходящие компоненты или объекты.';
const NETCAT_CONDITION_TYPE_SUBDIVISION = 'Раздел';
const NETCAT_CONDITION_TYPE_SUBDIVISION_DESCENDANTS = 'Раздел и его подразделы';
const NETCAT_CONDITION_SELECT_SUBDIVISION = 'выберите раздел';
const NETCAT_CONDITION_TYPE_OBJECT_FIELD = 'Свойство объекта';
const NETCAT_CONDITION_COMMON_FIELDS = 'Все компоненты';
const NETCAT_CONDITION_SELECT_OBJECT_FIELD = 'выберите свойство объекта';
const NETCAT_CONDITION_SELECT_VALUE = '...'; // sic

const NETCAT_CONDITION_VALUE_REQUIRED = 'Необходимо указать значение условия или удалить условие «%s»';

// Infoblock settings dialog; mixin editor
const NETCAT_INFOBLOCK_SETTINGS_CONTAINER = 'Настройки контейнера';
const NETCAT_INFOBLOCK_DELETE_CONTAINER = 'Удалить контейнер';
const NETCAT_INFOBLOCK_SETTINGS_TITLE_CONTAINER = 'Настройки контейнера блоков';
const NETCAT_INFOBLOCK_SETTINGS_TAB_CUSTOM = 'Настройки';
const NETCAT_INFOBLOCK_SETTINGS_TAB_VISIBILITY = 'Страницы';
const NETCAT_INFOBLOCK_SETTINGS_TAB_OTHERS = 'Другое';
const NETCAT_INFOBLOCK_VISIBILITY_SHOW_BLOCK = 'Показывать блок';
const NETCAT_INFOBLOCK_VISIBILITY_SHOW_CONTAINER = 'Показывать контейнер';
const NETCAT_INFOBLOCK_VISIBILITY_ALL_PAGES = 'везде';
const NETCAT_INFOBLOCK_VISIBILITY_THIS_PAGE = 'только на этой странице';
const NETCAT_INFOBLOCK_VISIBILITY_SOME_PAGES = 'выбрать страницы';
const NETCAT_INFOBLOCK_VISIBILITY_REMOVE_CONDITION = 'удалить';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISIONS = 'Разделы, в которых отображается блок';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISIONS_EXCLUDED = 'Разделы, в которых блок не отображается';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISIONS_ANY = 'любые разделы';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_NOT_SELECTED = '(Раздел не выбран)';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_INCLUDE_CHILDREN = 'включая подразделы';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_DOESNT_EXIST = 'Несуществующий раздел';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_SELECT = 'выбрать раздел';
const NETCAT_INFOBLOCK_VISIBILITY_ACTIONS = 'Тип страниц';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_INDEX = 'список объектов';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_FULL = 'страница объекта';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_ADD = 'добавление';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_DELETE = 'удаление';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_EDIT = 'редактирование';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_SEARCH = 'выборка объектов';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_SUBSCRIBE = 'подписка';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENTS = 'Компоненты в основной контентной области, которые должны присутствовать на странице';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENTS_EXCLUDED = 'Компоненты в основной контентной области, при наличии которых блок не выводится';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENTS_ANY = 'любые компоненты';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_NOT_SELECTED = '(Компонент не выбран)';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_DOESNT_EXIST = 'Несуществующий компонент';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_SELECT = 'выбрать компонент';
const NETCAT_INFOBLOCK_VISIBILITY_OBJECTS = 'Объекты, на страницах которых выводится блок';
const NETCAT_INFOBLOCK_VISIBILITY_OBJECTS_ANY = 'любые объекты';
const NETCAT_INFOBLOCK_VISIBILITY_OBJECT_NOT_SELECTED = '(Объект не выбран)';
const NETCAT_MIXIN_TITLE = 'Оформление';
const NETCAT_MIXIN_TITLE_INDEX = 'Оформление списка объектов';
const NETCAT_MIXIN_TITLE_INDEX_ITEM = 'Оформление объекта в списке';
const NETCAT_MIXIN_INDEX = 'Список объектов';
const NETCAT_MIXIN_INDEX_ITEM = 'Объекты в списке';
const NETCAT_MIXIN_BREAKPOINT_TYPE = 'Применять точки перехода';
const NETCAT_MIXIN_BREAKPOINT_TYPE_BLOCK = 'к ширине блока';
const NETCAT_MIXIN_BREAKPOINT_TYPE_VIEWPORT = 'к ширине страницы';
const NETCAT_MIXIN_BREAKPOINT_ADD = 'Добавить диапазон ширины';
const NETCAT_MIXIN_BREAKPOINT_ADD_PROMPT = 'Новая граница ширины блока';
const NETCAT_MIXIN_BREAKPOINT_ADD_PROMPT_RANGE = '(укажите значение от %from до %to пикс.)';
const NETCAT_MIXIN_BREAKPOINT_CHANGE = 'Изменить границу диапазона';
const NETCAT_MIXIN_BREAKPOINT_CHANGE_PROMPT = 'Изменить границу диапазона (0 или пустая строка для удаления):';
const NETCAT_MIXIN_FOR_WIDTH_FROM = 'при ширине от %from пикс.';
const NETCAT_MIXIN_FOR_WIDTH_TO = 'при ширине до %to пикс.';
const NETCAT_MIXIN_FOR_WIDTH_RANGE = 'при ширине от %from до %to пикс.';
const NETCAT_MIXIN_FOR_WIDTH_ANY = '';
const NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_FROM = 'при ширине страницы от %from пикс.';
const NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_TO = 'при ширине страницы до %to пикс.';
const NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_RANGE = 'при ширине страницы от %from до %to пикс.';
const NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_ANY = 'на странице с любой шириной';
const NETCAT_MIXIN_FOR_BLOCK_WIDTH_FROM = 'для блоков шириной от %from пикс.';
const NETCAT_MIXIN_FOR_BLOCK_WIDTH_TO = 'для блоков шириной до %to пикс.';
const NETCAT_MIXIN_FOR_BLOCK_WIDTH_RANGE = 'для блоков шириной %from—%to пикс.';
const NETCAT_MIXIN_FOR_BLOCK_WIDTH_ANY = 'для блоков с любой шириной';
const NETCAT_MIXIN_PRESET_REMOVE_BUTTON = 'удалить';
const NETCAT_MIXIN_NONE = 'нет';
const NETCAT_MIXIN_WIDTH = 'Ширина';
const NETCAT_MIXIN_SELECTOR = 'Дополнительный CSS-селектор';
const NETCAT_MIXIN_SELECTOR_ADD = '-- добавить селектор --';
const NETCAT_MIXIN_SELECTOR_ADD_PROMPT = 'Добавить CSS-селектор:';
const NETCAT_MIXIN_SETTINGS_REMOVE = 'Удалить настройки';
const NETCAT_MIXIN_PRESET_SELECT = 'Базовые настройки оформления';
const NETCAT_MIXIN_PRESET_DEFAULT = 'по умолчанию («%s»)';
const NETCAT_MIXIN_PRESET_DEFAULT_NONE = 'по умолчанию (нет)';
const NETCAT_MIXIN_PRESET_NONE_EXPLICIT = 'не использовать настройки по умолчанию';
const NETCAT_MIXIN_PRESET_CREATE = '-- создать новый набор настроек --';
const NETCAT_MIXIN_PRESET_EDIT_BUTTON = 'редактировать';
const NETCAT_MIXIN_PRESET_TITLE_EDIT = 'Редактирование набора настроек';
const NETCAT_MIXIN_PRESET_TITLE_ADD = 'Добавление набора настроек';
const NETCAT_MIXIN_PRESET_NAME = 'Название набора настроек';
const NETCAT_MIXIN_PRESET_AVAILABILITY = 'Настройки доступны для';
const NETCAT_MIXIN_PRESET_FOR_ANY_COMPONENT = 'шаблонов всех компонентов';
const NETCAT_MIXIN_PRESET_FOR_COMPONENT_TEMPLATE_PREFIX = 'шаблона «%s»';
const NETCAT_MIXIN_PRESET_FOR_COMPONENT = 'компонента «%s»';
const NETCAT_MIXIN_PRESET_USE_AS_DEFAULT_FOR = 'применять по умолчанию для';
const NETCAT_MIXIN_PRESET_TITLE_DELETE = 'Удаление набора настроек';
const NETCAT_MIXIN_PRESET_DELETE_WARNING = 'Набор настроек «%s» будет удалён.';
const NETCAT_MIXIN_PRESET_USED_FOR_COMPONENT_TEMPLATES = 'Данный набор настроек используется по умолчанию для';
const NETCAT_MIXIN_PRESET_COMPONENT_TEMPLATES_COUNT_FORMS = 'шаблона компонента/шаблонов компонентов/шаблонов компонентов';
const NETCAT_MIXIN_PRESET_USED_FOR_BLOCKS = 'Данный набор настроек используется в';
const NETCAT_MIXIN_PRESET_BLOCKS_COUNT_FORMS = 'блоке/блоках/блоках';

const NETCAT_MODAL_DIALOG_IMAGE_HEADER = 'Добавление картинки';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_EDIT_CAPTION = 'Редактировать';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_EDIT_COLORPICKER_INPUT_PLACEHOLDER = 'Значение RGB';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_CAPTION = 'Иконки';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_ICONS_NOT_FOUND = 'Не найдено';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_ICONS_SEARCH_INPUT_PLACEHOLDER = 'Поиск...';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_LIBRARY_CHOOSE = 'Все иконки';
const NETCAT_MODAL_DIALOG_IMAGE_BUTTON_SAVE = 'Сохранить';
const NETCAT_MODAL_DIALOG_IMAGE_BUTTON_CLOSE = 'Отмена';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_UPLOAD_CAPTION = 'Закачать';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_WEB_CAPTION = 'Из веба';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_SHOW_ALL = 'Показать все';

const NETCAT_MODAL_DIALOG_WRAP_CONTAINER_SUBMIT_BUTTON_CAPTION = "Выбрать блок";
const NETCAT_MODAL_DIALOG_WRAP_CONTAINER_BODY_CONTENT = "<p>Вы добавляете новый блок <strong>%s</strong>.
Автоматически будет создан общий контейнер с <strong>%s компоновкой</strong>.</p>
<p>Учитывайте данную особенность вёрстки при дальнейшем редактировании дизайна блоков.</p>" ;
const NETCAT_MODAL_DIALOG_WRAP_CONTAINER_LAYOUT_Y_AXIS = "вертикальной";
const NETCAT_MODAL_DIALOG_WRAP_CONTAINER_LAYOUT_X_AXIS = "горизонтальной";

const NC_FILTER_TYPE_STRING_NAME = 'Текстовый';
const NC_FILTER_TYPE_NUMBER_NAME = 'Числовой (от...до)';
const NC_FILTER_TYPE_RANGE_NAME = 'Диапазон значений';
const NC_FILTER_TYPE_LIST_NAME = 'Список с единственным выбором';
const NC_FILTER_TYPE_MULTIPLE_NAME = 'Список со множественным выбором (чекбоксы)';
const NC_FILTER_TYPE_DATE_NAME = 'Дата';
const NC_FILTER_TYPE_DATE_RANGE_NAME = 'Даты (от...до)';
const NC_FILTER_TABLE_HEADER_FIELD = 'Поле';
const NC_FILTER_TABLE_HEADER_FORMAT = 'Формат фильтра';
const NC_FILTER_ADD_FIELD = 'Добавить поле...';
const NC_FILTER_SELECT_FIELD = '-- выберите поле --';
const NC_FILTER_SUBDIVISION = 'Раздел';
const NC_FILTER_INFOBLOCKS = 'Инфоблок';
const NC_FILTER_NOT_SELECTED = 'Не выбрано';
const NC_FILTER_NOT_CONFIGURED = 'Фильтр еще не настроен';
const NC_FILTER_SUBMIT = 'Показать';
const NC_FILTER_FORM_NUMBER_FROM = 'от';
const NC_FILTER_FORM_NUMBER_TO = 'до';
const NC_FILTER_FORM_DATE_FROM = 'с';
const NC_FILTER_FORM_DATE_TO = 'по';

//Catalogue mode
const CONTROL_CONTENT_CATALOGUE_FUNCS_MODE = 'Режим работы сайта';
const CONTROL_CONTENT_CATALOGUE_FUNCS_MODE_TEMPLATE_DEVELOPMENT = 'Разработка шаблона';
const CONTROL_CONTENT_CATALOGUE_FUNCS_MODE_NORMAL = 'Нормальный';
const CONTROL_CONTENT_CATALOGUE_FUNCS_MODE_DEMO = 'Демо';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_DISALLOW_MOVE_AND_DELETE = 'запрет выключения, перемещения и удаления раздела';
const CONTROL_CONTENT_SUB_CLASS_FUNCS_DISALLOW_MOVE_AND_DELETE = 'запрет выключения, перемещения и удаления блока';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE = 'Ошибка удаления раздела: включен запрет на удаление раздела или инфоблока';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_HIDE = 'Ошибка выключения раздела: включен запрет на выключение раздела';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE_ITEM = 'запрет на удаление раздела';
const CONTROL_CONTENT_SUB_CLASS_DISALLOW_ERROR_DELETE_ITEM = 'запрет на удаление инфоблока';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_ACTION_TITLE = 'Действие запрещено';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_CUT_SUB_CLASS = 'На инфоблоке%s или его дочернем инфоблоке стоит запрет на перемещение.';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE_SUB_CLASS = 'На инфоблоке%s или его дочернем инфоблоке стоит запрет на удаление.';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_HIDE_SUB_CLASS = 'На инфоблоке или его дочернем инфоблоке стоит запрет на отключение.';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_HIDE_SUBDIVISION_SUB_CLASS = 'Ошибка выключения раздела: на одном из инфоблоков стоит запрет на удаление и выключение';

const NETCAT_VERSION_TAB_NAME = 'История';
const NETCAT_VERSION_TAB_TOOLBAR_SUBDIVISION = 'Раздел';
const NETCAT_VERSION_TAB_TOOLBAR_INFOBLOCK = 'Инфоблоки';
const NETCAT_VERSION_TAB_TOOLBAR_OBJECT = 'Объекты';
const NETCAT_VERSION_TABLE_TIMESTAMP = 'Дата и время';
const NETCAT_VERSION_TABLE_USER = 'Пользователь';
const NETCAT_VERSION_TABLE_CHANGES = 'Изменения';
const NETCAT_VERSION_TABLE_SHOW_CHANGES = 'показать изменения';
const NETCAT_VERSION_TABLE_NO_CHANGES = 'изменений нет';
const NETCAT_VERSION_OBJECT_RESTORED = 'Версия объекта восстановлена';
const NETCAT_VERSION_INFOBLOCK_RESTORED = 'Версия инфоблока восстановлена';
const NETCAT_VERSION_SUBDIVISION_RESTORED = 'Версия раздела восстановлена';
const NETCAT_VERSION_OBJECT_RESTORE_ERROR = 'Ошибка при восстановлении версии объекта';
const NETCAT_VERSION_INFOBLOCK_RESTORE_ERROR = 'Ошибка при восстановлении версии инфоблока';
const NETCAT_VERSION_SUBDIVISION_RESTORE_ERROR = 'Ошибка при восстановлении версии раздела';
const NETCAT_VERSION_OBJECT_RESTORE_ERROR_PARENT = 'Невозможно восстановить версию объекта, т.к. отсутствует родительский инфоблок';
const NETCAT_VERSION_INFOBLOCK_RESTORE_ERROR_PARENT = 'Невозможно восстановить версию объекта инфоблока, т.к. отсутствует родительский раздел';
const NETCAT_VERSION_SUBDIVISION_RESTORE_ERROR_PARENT = 'Невозможно восстановить версию раздела, т.к. отсутствует родительский сайт';
const NETCAT_VERSION_UNDO = 'Отменить';
const NETCAT_VERSION_REDO = 'Вернуть';
const NETCAT_VERSION_RESTORE = 'Восстановить';
const NETCAT_VERSION_CHANGESET_INITIAL = 'первая сохранённая версия';
const NETCAT_VERSION_CHANGESET_RESTORE_PAGE = 'восстановление версии страницы от %s';
const NETCAT_VERSION_CHANGESET_SITE_IMPORT = 'импорт сайта';
const NETCAT_VERSION_ENTITY = 'Сущность';
const NETCAT_VERSION_ENTITY_SITE = 'Сайт';
const NETCAT_VERSION_ENTITY_SUBDIVISION = 'Раздел';
const NETCAT_VERSION_ENTITY_INFOBLOCK = 'Инфоблок';
const NETCAT_VERSION_ENTITY_OBJECT = 'Объект';
const NETCAT_VERSION_ACTION_INITIAL = '(Первая версия)';
const NETCAT_VERSION_ACTION_CREATED = 'создание';
const NETCAT_VERSION_ACTION_UPDATED = 'изменение';
const NETCAT_VERSION_ACTION_SUBCLASS_PRIORITIES_UPDATED = 'изменение приоритетов инфоблоков';
const NETCAT_VERSION_ACTION_MOVED = 'перемещение';
const NETCAT_VERSION_ACTION_COPIED = 'копирование';
const NETCAT_VERSION_ACTION_DELETED = 'удаление';
const NETCAT_VERSION_ACTION_ENABLED = 'включение';
const NETCAT_VERSION_ACTION_DISABLED = 'выключение';
const NETCAT_VERSION_OF_SITE = 'сайта «%s»';
const NETCAT_VERSION_OF_SUBDIVISION = 'раздела «%s»';
const NETCAT_VERSION_OF_BLOCK = 'блока «%s»';
const NETCAT_VERSION_OF_CONTAINER = 'контейнера';
const NETCAT_VERSION_OF_NAMED_CONTAINER = 'контейнера «%s»';
const NETCAT_VERSION_OF_OBJECT = 'объекта';
const NETCAT_VERSION_OF_OBJECT_WITH_NAME = '%s «%s»';
const NETCAT_VERSION_TO_BLOCK = 'в блок «%s»';
const NETCAT_VERSION_IN_BLOCK = 'в блоке «%s»';
const NETCAT_VERSION_TIME_FORMAT = 'd.m.Y H:i:s';
const NETCAT_VERSION_TITLE_BY_USER = 'пользователем';
const NETCAT_VERSION_TITLE_GLOBAL_WARNING = 'Отмена этого действия отразится не только на этой странице!';

const MINIMAL_PHP_SUPPORT_ALERT = "С %s будет <strong>ограничена поддержка</strong> продуктов Netcat на <strong>PHP версии ниже 7.4.</strong></br>Вы используете версию PHP %s. Пожалуйста, запланируйте обновление PHP.";
const SITE_UNICODE_SUPPORT_ALERT = "С %s будет <strong>прекращена поддержка</strong> продуктов Netcat на сайтах <strong>с кодировкой WINDOWS-1251.</strong>
</br>Пожалуйста, запланируйте перевод сайта в кодировку UTF-8.";

// Selftest
const TOOLS_SELFTEST = "Мониторинг работы сайта";
const TOOLS_SELFTEST_HEADING = "Проверка работоспособности компонентов сайта";
const TOOLS_SELFTEST_SUBHEADING = "Мониторинг работы сайта";
const TOOLS_SELFTEST_DESCRIPTION = "В этом разделе собраны наиболее частые и актуальные проблемы, связанные с разработкой,
    дизайном, поддержкой, безопасностью, продвижением и другими аспектами жизни сайта.";
const TOOLS_SELFTEST_RUN = "Запустить";
const TOOLS_SELFTEST_PROBLEM_DETECTED_1 = "Обнаружена <strong><span id='problems-count'>%s</span> проблема</strong>.";
const TOOLS_SELFTEST_PROBLEM_DETECTED_2 = "Обнаружено <strong><span id='problems-count'>%s</span> проблемы</strong>.";
const TOOLS_SELFTEST_PROBLEM_DETECTED_3 = "Обнаружено <strong><span id='problems-count'>%s</span> проблем</strong>.";
const TOOLS_SELFTEST_PROBLEM_DETECTED_4 = "<span id='problems-count'>%s</span><span> проблема обнаружена</span>";
const TOOLS_SELFTEST_PROBLEM_DETECTED_5 = "<span id='problems-count'>%s</span><span> проблемы обнаружено</span>";
const TOOLS_SELFTEST_PROBLEM_DETECTED_6 = "<span id='problems-count'>%s</span><span> проблем обнаружено</span>";
const TOOLS_SELFTEST_HAS_NO_PROBLEMS = "Проблем не обнаружено";
const TOOLS_SELF_TEST_ALL_WORKS_CLEAN = "Основные параметры сайта работают корректно";
const TOOLS_SELFTEST_IN_PROCESS = "Выполняется проверка";
const TOOLS_SELFTEST_DONE = "Проверка завершена";
const TOOLS_SELFTEST_DONE_SUCCESSFULLY = "Проверка выполнена успешно.";

const TOOLS_SELFTEST_STATUS_CRITICAL = "Критично";
const TOOLS_SELFTEST_STATUS_RECOMMENDED = "Рекомендовано";
const TOOLS_SELFTEST_STATUS_ERROR = "Не удалось выполнить проверку";

const TOOLS_SELFTEST_HTTPS_CAPTION = "Поддержка HTTPS";
const TOOLS_SELFTEST_HTTPS_FAIL_DESCRIPTION = "Сайт не работает по HTTPS";
const TOOLS_SELFTEST_HTTPS_HAS_NO_SSL = "<p>На сайте нет сертификата SSL. Это может создать проблемы с открытием сайта и индексацией поисковыми машинами. Спросите о возможности установки сертификата у вашего администратора или хостинг-провайдера</p>";
const TOOLS_SELFTEST_HTTPS_HAS_NO_HTTPS_DB_PARAMETER = "<p>На сайте не установлено правило работы по протоколу HTTPS. Установите его в <a href='/netcat/admin/#catalogue.edit(%s)' target='_blank'>настройках сайта.</a></p>";
const TOOLS_SELFTEST_HTTPS_TEST_SUCCESS = "Сайт корректно работает по протоколу HTTPS.";

const TOOLS_SELFTEST_DEMO_MODE_CAPTION = "Режим работы сайта";
const TOOLS_SELFTEST_DEMO_MODE_FAIL_DESCRIPTION_SHORT = "Сайт работает в демо-режиме";
const TOOLS_SELFTEST_DEMO_MODE_FAIL_DESCRIPTION = "Сайт работает в демо-режиме, который предназначен для подготовки сайта к запуску. Если вы уже запустили сайт, установите <a href='/netcat/admin/#catalogue.system(%s)' target='_blank'>нормальный режим.</a>";
const TOOLS_SELFTEST_DEMO_MODE_TEST_SUCCESS = "Сайт работает в нормальном режиме.";

const TOOLS_SELFTEST_DEMO_CONTENT_CAPTION = "Наличие демо-контента";
const TOOLS_SELFTEST_DEMO_CONTENT_FAIL_DESCRIPTION_SHORT = "На сайте есть демо-контент";
const TOOLS_SELFTEST_DEMO_CONTENT_FAIL_DESCRITION = "<p>Демо-контент — это  типовой текст или другой вид контента из шаблона сайта.</p><p>Отредактируйте или удалите его.</p>Найти его на сайте поможет <a href='/netcat/admin/#site.demo_content(%s)' target='_blank'>эта страница</a>";
const TOOLS_SELFTEST_DEMO_CONTENT_TEST_SUCCESS = "Демо-контент на сайте не найден.";

const TOOLS_SELFTEST_INSTALL_DIRECTORY_CAPTION = "Наличие папки install";
const TOOLS_SELFTEST_INSTALL_DIRECTORY_FAIL_DESCRIPTION_SHORT = "На сайте не удалена папка install";
const TOOLS_SELFTEST_INSTALL_DIRECTORY_FAIL_DESCRIPTION = "<p>Эта папка содержит скрипт (программу) для установки системы.</p>Ее обязательно надо удалить после установки, иначе есть риски безопасности.";
const TOOLS_SELFTEST_INSTALL_DIRECTORY_TEST_SUCCESS = "Папка install отсутствует, это хорошо.";

const TOOLS_SELFTEST_PERMISSIONS_CAPTION = "Права в файловой системе";
const TOOLS_SELFTEST_PERMISSIONS_FAIL_DESCRIPTION_SHORT = "Права в файловой системе установлены неправильно";
const TOOLS_SELFTEST_PERMISSIONS_FAIL_DESCRIPTION = "<p>Проверьте установку прав для следующих файлов/каталогов:</p>%s<p><p>См. <a href='https://netcat.ru/developers/docs/install-and-settings/installation/' target='_blank'>инструкцию по установке прав.</a></p>";
const TOOLS_SELFTEST_PERMISSIONS_TEST_SUCCESS = "Права на файлы и папки выставлены корректно.";

const TOOLS_SELFTEST_INDEXES_CAPTION = "Поисковый индекс";
const TOOLS_SELFTEST_INDEXES_FAIL_DESCRIPTION_SHORT = "Сайт не проиндексирован";
const TOOLS_SELFTEST_INDEXES_FAIL_DESCRIPTION = "<p>Сайт не проиндексирован внутренней поисковой машиной, поиск будет выдавать пустой результат.</p>Исправьте это на <a href='/netcat/admin/#module.search.indexing' target='_blank'>странице управления модулем поиска.</a>";
const TOOLS_SELFTEST_INDEXES_PERF_LONG_TIME_AGO = "<p>Сайт давно не индексировался внутренней поисковой машиной (последняя переиндексация была %s)</p>>Проиндексируйте его <a href='/netcat/admin/#module.search.indexing' target='_blank'>вручную</a> или включите <a href='https://netcat.ru/developers/docs/modules/module-search/schedule/' target='_blank'>индексацию по расписанию.</a>";
const TOOLS_SELFTEST_INDEXES_TEST_SUCCESS = "Сайт проиндексирован внутренней поисковой машиной.";

const TOOLS_SELFTEST_UPDATES_CAPTION = "Обновления системы";
const TOOLS_SELFTEST_UPDATES_FAIL_DESCRIPTION_SHORT = "Обнаружены неустановленные обновления";
const TOOLS_SELFTEST_UPDATES_FAIL_DESCRIPTION = "<p>Обнаружены неустановленные обновления.</p>Для корректной работы сайта рекомендуется устанавливать актуальные обновления.";
const TOOLS_SELFTEST_UPDATES_TEST_SUCCESS = "Установлены все обновления";

const TOOLS_SELFTEST_CRONTAB_CAPTION = "Корректная работа крона";
const TOOLS_SELFTEST_CRONTAB_FAIL_DESCRIPTION = "Планировщик задач не выполняется";
const TOOLS_SELFTEST_CRONTAB_FAIL_DESCRIPTION_SHORT = "Настройте crontab на вашем сервере в соответствии с <a href='https://netcat.ru/developers/docs/system-tools/task-management/' target='_blank'>инструкцией.</a>";
const TOOLS_SELFTEST_CRONTAB_TEST_SUCCESS = "Планировщик задач (crontab) настроен корректно.";

const TOOLS_SELFTEST_REQUESTS_CAPTION = "Заявки";
const TOOLS_SELFTEST_REQUESTS_FAIL_DESCRIPTION_SHORT = "Обнаружены необработанные заявки";
const TOOLS_SELFTEST_REQUESTS_FAIL_DESCRIPTION = "Обнаружено заявок: <strong>%s</strong>, их можно <a href='/netcat/admin/#module.requests.list(%s)' target='_blank'>обработать</a>.";
const TOOLS_SELFTEST_REQUESTS_TEST_SUCCESS = "Новых необработанных заявок нет.";

const TOOLS_SELFTEST_NETSHOP_ORDERS_CAPTION = "Заказы модуля «Магазин»";
const TOOLS_SELFTEST_NETSHOP_ORDERS_FAIL_DESCRIPTION_SHORT = "У вас есть необработанные заказы";
const TOOLS_SELFTEST_NETSHOP_ORDERS_FAIL_DESCRIPTION = "Обнаружено заказов: <strong>%s</strong>, их можно <a href='/netcat/admin/#module.netshop.order(%s)' target='_blank'>обработать.</a>";
const TOOLS_SELFTEST_NETSHOP_ORDERS_TEST_SUCCESS = "Новых необработанных заказов нет.";

const TOOLS_SELFTEST_CONTENT_VERSIONING_CAPTION = "Версии страниц";
const TOOLS_SELFTEST_CONTENT_VERSIONING_FAIL_DESCRIPTION_SHORT = "Не сохраняются версии измененных страниц";
const TOOLS_SELFTEST_CONTENT_VERSIONING_FAIL_DESCRIPTION = "<p>Без сохранения изменений не будет работать их отмена.</p>Включить сохранение версий можно в <a href='/netcat/admin/#catalogue.system(%s)' target='_blank'>настройках сайта.</a>";
const TOOLS_SELFTEST_CONTENT_VERSIONING_TEST_SUCCESS = "Изменения на страницах сохраняются, функция отмены изменений доступна.";

const TOOLS_SEFLTEST_SEO_ROBOTS_CAPTION = "Доступность для индексации";
const TOOLS_SEFLTEST_SEO_ROBOTS_FAIL_DESCRIPTION_SHORT = "Сайт не доступен для индексации поисковыми машинами.";
const TOOLS_SEFLTEST_SEO_ROBOTS_DESCRIPTION = "<p>В файле robots.txt установлена директива Disallow для сайта.</p><a href='/netcat/admin/#catalogue.edit(%s)' target='_blank'>Уберите её</a>, если хотите, чтобы сайт был доступен поисковым машинам.";
const TOOLS_SELFTEST_SEO_HAS_ROBOTS_FILE = "<p>В корне сайта присутствует файл robots.txt.</p>Рекомендуется настраивать директивы robots на странице <a href='/netcat/admin/#catalogue.edit(%s)' target='_blank'>настроек сайта</a>";
const TOOLS_SELFTEST_SEO_ROBOTS_TEST_SUCCESS = "Сайт доступен для индексации поисковыми машинами.";


// Support
const SUPPORT_LABEL = "Техподдержка";
const SUPPORT_UPDATES_LABEL = "Обновления системы";
const SUPPORT_STATUS_ACTIVE = "Активная";
const SUPPORT_STATUS_INACTIVE = "Неактивная";
const SUPPORT_STATUS_OVER = "Закончилась";
const SUPPORT_STATUS_ENDS_CLOSE = "Заканчивается через %s %s";
const SUPPORT_DAY_DECL_1 = "день";
const SUPPORT_DAY_DECL_2 = "дня";
const SUPPORT_DAY_DECL_3 = "дней";
const SUPPORT_STATUS_INCORRECT_LICENSE = "Лицензия указана некорректно";
const SUPPORT_PAID_UNDER = "Оплачено до";
const SUPPORT_PROLONGATION = "Продлить на год за %s ";
const SUPPORT_PROLONGATION_PRICE_AFTER_DISCOUNTS = "С %s стоимость продления составит %s";
const SUPPORT_MORE_DETAILS = "Подробнее";
const SUPPORT_INCORRECT_NOTICE = "Эксплуатация системы может быть опасной, обратитесь в поддержку, поможем разобраться и исправить";
const SUPPORT_SEND_MESSAGE = "Написать в техподдержку";
const SUPPORT_CURRENT_NETCAT_VERSION = "Текущая версия Netcat";
const SUPPORT_CURRENT_NETCAT_VERSION_ACTUAL = "Установлена последняя версия, обновление не требуется";
const SUPPORT_OUTDATED_NETCAT_VERSION = "Устаревшая версия";
const SUPPORT_UPDATE_SYSTEM_FREE = "Обновить систему бесплатно";
const SUPPORT_UPDATE_SYSTEM = "Обновить систему";
const SUPPORT_UPDATE_SYSTEM_FOR_MONEY = "Обновить систему за %s";
const SUPPORT_PAYED_CAPTION = "оплачен";
const SAAS_SUPPORT_WIDGET_CAPTION = "Статус сайта";
const SAAS_SUPPORT_WIDGET_CANNOT_GET_SITE_INFO = "Не удалось получить информацию о сайте. Обратитесь в техподдержку";
const SAAS_SUPPORT_WIDGET_PAYMENT_NOTICE_BEFORE_SITE_DELETE = "Успейте оплатить до %s, иначе сайт будет отключен";
const SAAS_SUPPORT_WIDGET_PAYMENT_RENEWAL_CAPTION = "Продлить";
const SAAS_SUPPORT_WIDGET_IN_TRIAL_MODE = "в демо-режиме";
const SAAS_SUPPORT_WIDGET_TRIAL_MODE_ENDS = "Демо-режим закончился";
const SAAS_SUPPORT_WIDGET_SITE_IS_DISABLED_NOTICE = "Сайт отключен, продлите подписку";
