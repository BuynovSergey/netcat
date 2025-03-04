<?php

/* $id$ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

$nc_core = nc_core::get_object();

/**
 * Функция перевода русских букв в латинницу
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
if (isset($NC_DEPRECATED_DISABLED) && $NC_DEPRECATED_DISABLED == 0) {
    $deprecated_file = preg_replace('/\.php/', '_old.php', __FILE__);
    if (file_exists($deprecated_file)) {
        include_once $deprecated_file;
    }
}

// MAIN
$ADMIN_PATH = $nc_core->ADMIN_PATH;

const MAIN_DIR = 'ltr';
const MAIN_LANG = 'en';
const MAIN_NAME = 'English';
const MAIN_ENCODING = 'UTF-8';

if (!defined("MAIN_EMAIL_ENCODING")) {
    define("MAIN_EMAIL_ENCODING", $nc_core->NC_CHARSET);
}

const CMS_SYSTEM_NAME = 'Netcat';
const CMS_SYSTEM_NAME_GLOBAL = 'Netcat';
const NETCAT_TREE_SITEMAP = 'Site map';
const NETCAT_TREE_MODULES = 'Modules';
const NETCAT_TREE_USERS = 'Users';

const NETCAT_TREE_PLUS_TITLE = 'Expand list';
const NETCAT_TREE_MINUS_TITLE = 'Collapse list';

const NETCAT_TREE_QUICK_SEARCH = 'Quick search';

// Tabs
const NETCAT_TAB_REFRESH = 'Refresh';

const STRUCTURE_TAB_SUBCLASS_ADD = 'Add data component';
const STRUCTURE_TAB_INFO = 'Information';
const STRUCTURE_TAB_SETTINGS = 'Settings';
const STRUCTURE_TAB_USED_SUBCLASSES = 'Used components';
const STRUCTURE_TAB_EDIT = 'Edit';
const STRUCTURE_TAB_PREVIEW = 'View';
const STRUCTURE_TAB_PREVIEW_SITE = 'View site';


const CLASS_TAB_INFO = 'Information';
const CLASS_TAB_EDIT = 'Edit';
const CLASS_TAB_CUSTOM_ACTION = 'Custom action';
const CLASS_TAB_CUSTOM_ADD = 'Add';
const CLASS_TAB_CUSTOM_EDIT = 'Edit';
const CLASS_TAB_CUSTOM_DELETE = 'Delete';
const CLASS_TAB_CUSTOM_SEARCH = 'Search';

// BeginHtml
const BEGINHTML_TITLE = 'Administration';
const BEGINHTML_USER = 'User';
const BEGINHTML_VERSION = 'version';
const BEGINHTML_PERM_GUEST = 'guest';
const BEGINHTML_PERM_DIRECTOR = 'director';
const BEGINHTML_PERM_SUPERVISOR = 'supervisor';
const BEGINHTML_PERM_CATALOGUEADMIN = 'site administrator';
const BEGINHTML_PERM_SUBDIVISIONADMIN = 'section administrator';
const BEGINHTML_PERM_SUBCLASSADMIN = 'section content template administrator';
const BEGINHTML_PERM_CLASSIFICATORADMIN = 'classificator administrator';
const BEGINHTML_PERM_MODERATOR = 'moderator';

const BEGINHTML_LOGOUT = 'log out';
const BEGINHTML_LOGOUT_OK = 'Session is closed';
const BEGINHTML_LOGOUT_RELOGIN = 'Re-login';
const BEGINHTML_LOGOUT_IE = 'For end of a session close all Internet browsers.';


const BEGINHTML_ALARMON = 'Unread system messages';
const BEGINHTML_ALARMOFF = 'System messages: no unread';
const BEGINHTML_ALARMVIEW = 'Read system message';
const BEGINHTML_HELPNOTE = 'Help';

// EndHTML
define("ENDHTML_NETCAT", CMS_SYSTEM_NAME === 'Netcat' ?
    "<div class='bottom_line'><div class='left'>&copy; 1999&#8212;" . date("Y") .
    " <a href='https://netcat.ru'>Netcat</a></div></div>" : '');

// Common
const NETCAT_ADMIN_DELETE_SELECTED = 'Delete the selected section';
const NETCAT_SELECT_SUBCLASS_DESCRIPTION = 'There are several &quot;%s&quot; section components in the %s subdivision.<br />
  Select the destination section content template by clicking on its name.<br />';

// INDEX PAGE
const SECTION_INDEX_SITES_SETTINGS = 'Sites settings';
const SECTION_INDEX_MODULES_MUSTHAVE = "you don't have";
const SECTION_INDEX_MODULES_DESCRIPTION = 'description';
const SECTION_INDEX_MODULES_TRANSITION = 'Transition to older version';
const DASHBOARD_WIDGET = 'Dashboard';
const DASHBOARD_ADD_WIDGET = 'Add widget';
const DASHBOARD_DEFAULT_WIDGET = 'Default widget';
const DASHBOARD_WIDGET_SYS_NETCAT = 'About system';
const DASHBOARD_WIDGET_MOD_AUTH = 'User statistics';
const DASHBOARD_UPDATES_EXISTS = 'Updates exists';
const DASHBOARD_UPDATES_DONT_EXISTS = 'No updates';
const DASHBOARD_DONT_ACTIVE = 'Dont active';
const DASHBOARD_TODAY = 'today';
const DASHBOARD_YESTERDAY = 'yesterday';
const DASHBOARD_PER_WEEK = 'per week';
const DASHBOARD_WAITING = 'waiting';

// MODULES LIST
const NETCAT_MODULE_DEFAULT = 'Developer interface';
const NETCAT_MODULE_AUTH = 'User Interface';
const NETCAT_MODULE_SEARCH = 'Site search';
const NETCAT_MODULE_SERCH = 'Site search (old version)';
const NETCAT_MODULE_POLL = 'Polls';
const NETCAT_MODULE_ESHOP = 'NetShop (old)';
const NETCAT_MODULE_STATS = 'Statistics';
const NETCAT_MODULE_SUBSCRIBE = 'Subscribe';
const NETCAT_MODULE_BANNER = 'Advertising managment';
const NETCAT_MODULE_FORUM = 'Forum';
const NETCAT_MODULE_FORUM2 = 'Forum v2';
const NETCAT_MODULE_NETSHOP = 'NetShop';
const NETCAT_MODULE_LINKS = 'Link manager';
const NETCAT_MODULE_CAPTCHA = 'CAPTCHA';
const NETCAT_MODULE_TAGSCLOUD = 'Tag Cloud';
const NETCAT_MODULE_BLOG = 'Blog';
const NETCAT_MODULE_CALENDAR = 'Calendar';
const NETCAT_MODULE_COMMENTS = 'Comments';
const NETCAT_MODULE_LOGGING = 'Logging';
const NETCAT_MODULE_FILEMANAGER = 'File manager';
const NETCAT_MODULE_CACHE = 'Cache';
const NETCAT_MODULE_MINISHOP = 'Minishop';
const NETCAT_MODULE_ROUTING = 'Routing';
const NETCAT_MODULE_AIREE = 'Airee CDN';

const NETCAT_MODULE_NETSHOP_MODULEUNCHECKED = 'Module "NetShop" is not installed';
const NETCAT_MODULE_MESSAGING = 'Messaging';
// /MODULES LIST

const SECTION_INDEX_USER_STRUCT_CLASSIFICATOR = 'Classificators';

const SECTION_INDEX_USER_RIGHTS_TYPE = 'User Roles';
const SECTION_INDEX_USER_RIGHTS_RIGHTS = 'Permissions';

const SECTION_INDEX_USER_USER_MAIL = 'Email users';
const SECTION_INDEX_USER_SUBSCRIBERS = 'User subscribers';

const SECTION_INDEX_DEV_CLASSES = 'Components';
const SECTION_INDEX_DEV_CLASS_TEMPLATES = 'Component templates';
const SECTION_INDEX_DEV_TEMPLATES = 'Design templates';


const SECTION_INDEX_ADMIN_PATCHES_INFO = 'System information';
const SECTION_INDEX_ADMIN_PATCHES_INFO_VERSION = 'System version';
const SECTION_INDEX_ADMIN_PATCHES_INFO_REDACTION = 'System redaction';
const SECTION_INDEX_ADMIN_PATCHES_INFO_LAST_PATCH = 'Last patch number';
const SECTION_INDEX_ADMIN_PATCHES_INFO_LAST_PATCH_DATE = 'Last system patch check';
const SECTION_INDEX_ADMIN_PATCHES_INFO_CHECK_PATCH = 'Check for updates now';

const SECTION_INDEX_REPORTS_STATS = 'General statistics';
const SECTION_INDEX_REPORTS_SYSTEM = 'System messages';


// SECTION CONTROL
const SECTION_CONTROL_CONTENT_CATALOGUE = 'Sites';
const SECTION_CONTROL_CONTENT_FAVORITES = 'Quick edit';
const SECTION_CONTROL_CONTENT_CLASSIFICATOR = 'Classificators';

// SECTION USER
const SECTION_CONTROL_USER = 'Users';
const SECTION_CONTROL_USER_LIST = 'List of users';
const SECTION_CONTROL_USER_PERMISSIONS = 'Users and permissions';
const SECTION_CONTROL_USER_GROUP = 'User groups';
const SECTION_CONTROL_USER_MAIL = 'Email users';

// SECTION CLASS
const SECTION_CONTROL_CLASS = 'Components';
const CONTROL_CLASS_USE_CAPTCHA = 'protect add form with image';
const CONTROL_CLASS_CACHE_FOR_AUTH = 'caching authorization';
const CONTROL_CLASS_CACHE_FOR_AUTH_NONE = 'Not used';
const CONTROL_CLASS_CACHE_FOR_AUTH_USER = 'Each user';
const CONTROL_CLASS_CACHE_FOR_AUTH_GROUP = 'User main group';
const CONTROL_CLASS_CACHE_FOR_AUTH_DESCRIPTION = 'If you need to display unique data to each user in component, this option allows you to select the required conditions.';
const CONTROL_CLASS_ADMIN = 'Administration';
const CONTROL_CLASS_CONTROL = 'Control';
const CONTROL_CLASS_FIELDSLIST = 'Fields';
const CONTROL_CLASS_CLASS_GOTOFIELDS = 'Go to component fields';
const CONTROL_CLASS_CLASSFORM_ADDITIONAL_INFO = 'Additional information';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SORTNOTE = 'Field_name_1[ DESC][, Field_name_2[ DESC]][, ...]<br>DESC - decrease sort';
const CONTROL_CLASS_CLASS_SHOW_VAR_FUNC_LIST = 'Show variables and functions list';
const CONTROL_CLASS_CLASS_SHOW_VAR_LIST = 'Show variables list';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_AUTODEL = 'Delete objects in';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_AUTODELEND = 'days after addition';
const CONTROL_CLASS_CLASS_FORMS_YES = 'Yes';
const CONTROL_CLASS_CLASS_FORMS_NO = 'No';
const CONTROL_CLASS_CLASS_FORMS_ADDFORM = 'Alternate object addition form';
// NB: константы CONTROL_CLASS_CLASS_* также используются в nc_tpl_fields / nc_tpl_component_view, но через defined(),
// поэтому IDE не будет видеть их использование
const CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN = 'generate form code';
const CONTROL_CLASS_CLASS_FORMS_ADDRULES = 'Object addition conditions';
const CONTROL_CLASS_CLASS_FORMS_ADDCOND_GEN = 'generate condition code';
const CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION = 'Action after addition of object';
const CONTROL_CLASS_CLASS_FORMS_ADDACTION_GEN = 'generate action code';
const CONTROL_CLASS_CLASS_FORMS_EDITFORM = 'Alternate object modification form';
const CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN = 'generate form code';
const CONTROL_CLASS_CLASS_FORMS_EDITRULES = 'Object modification conditions';
const CONTROL_CLASS_CLASS_FORMS_EDITCOND_GEN = 'generate condition code';
const CONTROL_CLASS_CLASS_FORMS_EDITLASTACTION = 'Action after modification of object';
const CONTROL_CLASS_CLASS_FORMS_EDITACTION_GEN = 'generate action code';
const CONTROL_CLASS_CLASS_FORMS_ONONACTION = 'Action after turning object ON or OFF';
const CONTROL_CLASS_CLASS_FORMS_CHECKACTION_GEN = 'generate action code';
const CONTROL_CLASS_CLASS_FORMS_DELETEFORM = 'Alternate object deleting form';
const CONTROL_CLASS_CLASS_FORMS_DELETERULES = 'Object deleting conditions';
const CONTROL_CLASS_CLASS_FORMS_ONDELACTION = 'Action after deleting object';
const CONTROL_CLASS_CLASS_FORMS_DELETEACTION_GEN = 'generate action code';
const CONTROL_CLASS_CLASS_FORMS_QSEARCH = 'Search form in object list';
const CONTROL_CLASS_CLASS_FORMS_QSEARCH_GEN = 'generate form code';
const CONTROL_CLASS_CLASS_FORMS_SEARCH = 'Search form (individual page)';
const CONTROL_CLASS_CLASS_FORMS_SEARCH_GEN = 'generate form code';
const CONTROL_CLASS_CLASS_FORMS_MAILRULES = 'Subscription conditions';
const CONTROL_CLASS_CLASS_FORMS_MAILTEXT = 'Email template for subscribers';
define("CONTROL_CLASS_CLASS_FORMS_QSEARCH_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_QSEARCH . "\\\" not empty! Replace old text?");
define("CONTROL_CLASS_CLASS_FORMS_SEARCH_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_SEARCH . "\\\" not empty! Replace old text?");
define("CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_ADDFORM . "\\\" not empty! Replace old text?");
define("CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_EDITFORM . "\\\" not empty! Replace old text?");
define("CONTROL_CLASS_CLASS_FORMS_ADDCOND_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_ADDRULES . "\\\" not empty! Replace old text?");
define("CONTROL_CLASS_CLASS_FORMS_EDITCOND_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_EDITRULES . "\\\" not empty! Replace old text?");
define("CONTROL_CLASS_CLASS_FORMS_ADDACTION_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION . "\\\" not empty! Replace old text?");
define("CONTROL_CLASS_CLASS_FORMS_EDITACTION_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_EDITLASTACTION . "\\\" not empty! Replace old text?");
define("CONTROL_CLASS_CLASS_FORMS_CHECKACTION_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_ONONACTION . "\\\" not empty! Replace old text?");
define("CONTROL_CLASS_CLASS_FORMS_DELETEACTION_GEN_WARN",
    "Field \\\"" . CONTROL_CLASS_CLASS_FORMS_ONDELACTION . "\\\" not empty! Replace old text?");
const CONTROL_CLASS_CUSTOM_SETTINGS_ISNOTSET = 'There is no view settings template in current component.';
const CONTROL_CLASS_CUSTOM_SETTINGS_INHERIT_FROM_PARENT = 'View settings are defined in the component.';

// SECTION WIDGET
const WIDGETS = 'Widget List';
const WIDGETS_LIST_IMPORT = 'Import';
const WIDGETS_LIST_ADD = 'Add';
const WIDGETS_PARAMS = 'Parameters';
const SECTION_INDEX_DEV_WIDGET = 'Widget-class';
const CONTROL_WIDGETCLASS_ADD = 'Add widget';
const WIDGET_LIST_NAME = 'Name';
const WIDGET_LIST_CATEGORY = 'Category';
const WIDGET_LIST_ALL = 'All';
const WIDGET_LIST_GO = 'Continue';
const WIDGET_LIST_FIELDS = 'Fields';
const WIDGET_LIST_DELETE = 'Delete';
const WIDGET_LIST_DELETE_WIDGETCLASS = 'Widget-class:';
const WIDGET_LIST_DELETE_WIDGET = 'Widget List:';
const WIDGET_LIST_EDIT = 'Edit';
const WIDGET_LIST_AT = 'Action templates';
const WIDGET_LIST_ADDWIDGET = 'Add widget-class';
const WIDGET_LIST_DELETE_SELECTED = 'Delete selected';
const WIDGET_LIST_ERROR_DELETE = 'First, select a widget-class to remove';
const WIDGET_LIST_INSERT_CODE = 'embed';
const WIDGET_LIST_INSERT_CODE_CLASS = 'Embed for template/class';
const WIDGET_LIST_INSERT_CODE_TEXT = 'Embed for text';
const WIDGET_LIST_LOAD = 'Loading...';
const WIDGET_LIST_PREVIEW = 'Preview';
const WIDGET_LIST_EXPORT = 'Export widget-class into file';
const WIDGET_ADD_CREATENEW = 'Create new widget-class &quot;from scratch&quot;';
const WIDGET_ADD_CONTINUE = 'Continue';
const WIDGET_ADD_CREATENEW_BASICOLD = 'Create a new widget-class of the existing';
const WIDGET_ADD_NAME = 'Name';
const WIDGET_ADD_KEYWORD = 'Keyword';
const WIDGET_ADD_UPDATE = 'Update widget list every N minutes (0 - do not update)';
const WIDGET_ADD_NEWGROUP = 'New group';
const WIDGET_ADD_DESCRIPTION = 'Widget-class description';
const WIDGET_ADD_OBJECTVIEW = 'Template view';
const WIDGET_ADD_PAGEBODY = 'Display object';
const WIDGET_ADD_DOPL = 'Extra';
const WIDGET_ADD_DEVELOP = 'In the pipeline';
const WIDGET_ADD_SYSTEM = 'System preferences';
const WIDGETCLASS_ADD_ADD = 'Add widget-class';
const WIDGET_ADD_ADD = 'Add widget';
const WIDGET_ADD_ERROR_NAME = 'Input widget-class name';
const WIDGET_ADD_ERROR_KEYWORD = 'Input keyword';
const WIDGET_ADD_ERROR_KEYWORD_EXIST = 'Keyword must be unique';
const WIDGET_ADD_WK = 'Widget-class';
const WIDGET_ADD_OK = 'Widget successfully added';
const WIDGET_ADD_DISALLOW = 'Disallow embedding into the object';
const WIDGET_IS_STATIC = 'Static content';
const WIDGET_EDIT_SAVE = 'Save';
const WIDGET_EDIT_OK = 'Changes saved';
const WIDGET_INFO_DESCRIPTION = 'Widget-class description';
const WIDGET_INFO_DESCRIPTION_NONE = 'No description available';
const WIDGET_INFO_PREVIEW = 'Preview';
const WIDGET_INFO_INSERT = 'Embed for template/class';
const WIDGET_INFO_INSERT_TEXT = 'Embed for text';
const WIDGET_INFO_GENERATE = 'Syntax example for dynamic embedding in the template/class';
const WIDGET_DELETE_WARNING = 'Warning: widget-class%s will be deleted.';
const WIDGET_DELETE_CONFIRMDELETE = 'Confirm delete';
const WIDGET_DELETE = 'Warning: Widget will be deleted.';
const WIDGET_ACTION_ADDFORM = 'Alternative adding form';
const WIDGET_ACTION_EDITFORM = 'Alternative edit form';
const WIDGET_ACTION_BEFORE_SAVE = 'Before save action';
const WIDGET_ACTION_AFTER_SAVE = 'After save action';
const WIDGET_IMPORT = 'Import';
const WIDGET_IMPORT_TAB = 'Import';
const WIDGET_IMPORT_CHOICE = 'Select the file';
const WIDGET_IMPORT_ERROR = 'File import error';
const WIDGET_IMPORT_OK = 'Widget-class imported successfully';

const SECTION_CONTROL_WIDGET = 'Widget List';
const SECTION_CONTROL_WIDGETCLASS = 'Widget-classes';
const SECTION_CONTROL_WIDGET_LIST = 'Widgets List';
const CONTROL_WIDGET_ACTIONS_EDIT = 'Editing';
const CONTROL_WIDGET_NONE = 'No widgets found';
const TOOLS_WIDGET = 'Widget List';
const CONTROL_WIDGET_ADD_ACTION = 'Adding widget';
const CONTROL_WIDGETCLASS_ADD_ACTION = 'Adding widget-class';
const SECTION_INDEX_DEV_WIDGETS = 'Widget List';
const CONTROL_WIDGETCLASS_IMPORT = 'Widget import';
const CONTROL_WIDGETCLASS_FILES_PATH = "Files dir <a href='%s'>%s</a>";

const WIDGET_TAB_INFO = 'Information';
const WIDGET_TAB_EDIT = 'Edit widget-class';
const WIDGET_TAB_CUSTOM_ACTION = 'Action templates';
const NETCAT_REMIND_SAVE_TEXT = 'Exit without saving?';
const NETCAT_REMIND_SAVE_SAVE = 'Save';
const SECTION_SECTIONS_INSTRUMENTS_WIDGETS = 'Widget List';

// SECTION TEMPLATE
const SECTION_CONTROL_TEMPLATE_SHOW = 'Templates';

// SECTIONS OPTIONS
const SECTION_SECTIONS_OPTIONS = 'Generel settings';
const SECTION_SECTIONS_OPTIONS_MODULE_LIST = 'Module manager';
const SECTION_SECTIONS_OPTIONS_WYSIWYG = 'WYSIWYG settings';
const SECTION_SECTIONS_OPTIONS_SYSTEM = 'System tables';
const SECTION_SECTIONS_OPTIONS_SECURITY = 'Security';

// SECTIONS OPTIONS
const SECTION_SECTIONS_INSTRUMENTS_SQL = 'Execute SQL-query';
const SECTION_SECTIONS_INSTRUMENTS_TRASH = 'Trash bin';
const SECTION_SECTIONS_INSTRUMENTS_CRON = 'Task manager';
const SECTION_SECTIONS_INSTRUMENTS_HTML = 'WYSIWYG editor';

// SECTIONS MODDING
const SECTION_SECTIONS_MODDING_ARHIVES = 'Backups';

// REPORTS
const SECTION_REPORTS_TOTAL = 'General statistics';
const SECTION_REPORTS_SYSMESSAGES = 'System messages';

// SUPPORT

// ABOUT
const SECTION_ABOUT_TITLE = 'About';
const SECTION_ABOUT_HEADER = 'About';
const SECTION_ABOUT_BODY = "Netcat content management system <font color=%s>%s</font> version %s. All rights reserved.<br><br>\nWeb-site: <a target=_blank href=https://netcat.ru>www.netcat.ru</a><br>\nEmail for support: <a href=mailto:support@netcat.ru>support@netcat.ru</a>\n<br><br>\nDeveloper: &laquo;Netcat&raquo; LLC<br>\nEmail: <a href=mailto:info@netcat.ru>info@netcat.ru</a><br>\n+7 (495) 783-6021<br>\n<a target=_blank href=https://netcat.ru>www.netcat.ru</a><br>";
const SECTION_ABOUT_DEVELOPER = 'Project developer';

// ARRAY-2-FORMS


// INDEX
const CONTROL_CONTENT_CATALOUGE_SITE = 'Sites';
const CONTROL_CONTENT_CATALOUGE_ONESITE = 'Site';
const CONTROL_CONTENT_CATALOUGE_ADD = 'adding';
const CONTROL_CONTENT_CATALOUGE_SITEDELCONFIRM = 'Confirm removal';
const CONTROL_CONTENT_CATALOUGE_ADDSECTION = 'Add section';
const CONTROL_CONTENT_CATALOUGE_ADDSITE = 'Add site';
const CONTROL_CONTENT_CATALOUGE_SITEOPTIONS = 'Site settings';

const CONTROL_CONTENT_CATALOUGE_ERROR_CASETREE_ONE = 'Site name can not be empty!';
const CONTROL_CONTENT_CATALOUGE_ERROR_DUPLICATE_DOMAIN = 'Duplicate site domain. Please change domain name.';
const CONTROL_CONTENT_CATALOUGE_ERROR_CASETREE_THREE = 'Domain name can contain only latin characters, numbers, hyphen or dot! Do not use only numbers. Can contain port.';
const CONTROL_CONTENT_CATALOUGE_ERROR_DOMAIN_NOT_SET = 'Domain name is not specified!';
const CONTROL_CONTENT_CATALOUGE_ERROR_INCORRECT_DOMAIN = 'Incorrect domain name!';
define("CONTROL_CONTENT_CATALOUGE_ERROR_INCORRECT_DOMAIN_FULLTEXT",
    "Please, check domain name. " . CMS_SYSTEM_NAME . " must be installed in the root directory of domain!");
const CONTROL_CONTENT_CATALOUGE_ERROR_CANNOT_CREATE_MORE = 'Cannot add one more site on this copy of the system. (There is no such limitation in the self-hosted version of the CMS.)';

const CONTROL_CONTENT_CATALOUGE_SUCCESS_ADD = 'Site successfully added!';
const CONTROL_CONTENT_CATALOUGE_SUCCESS_EDIT = 'Site settings successfully modified!';
const CONTROL_CONTENT_CATALOUGE_SUCCESS_DELETE = 'Site successfully deleted!';

const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MAININFO = 'General Information';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NAME = 'Name';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN = 'Domain';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN_SAAS_SUFFIX = 'Domain management -';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN_SAAS_PERSONAL_ACCOUNT = 'in your personal account';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CATALOGUEFORM_LANG = 'Site language';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MIRRORS = 'Mirrors (one per row)';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_OFFLINE = 'Show when site is offline';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ROBOTS = 'Robots.txt file contents';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ROBOTS_FILE_EXIST = 'Attention! File robots.txt already exist. Please, edit it directly.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ROBOTS_DONT_CHANGE = 'Do not change this section';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TEMPLATE = 'Design template';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TITLEPAGE = 'Index section';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TITLEPAGE_PAGE = 'Index section';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND = '&quot;Page not found&quot; section (error 404)';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND_PAGE = 'Page not found';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY = 'Priority';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ON = 'turned on';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_HTTPS_ENABLED = 'enable HTTPS';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_STORE_VERSIONS = 'store content versions';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_REMOVE_VERSIONS = 'Attention: all content changes history will be deleted!';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_LABEL_COLOR = 'Label color';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DEFAULT_CLASS = 'Default class';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_POLICY = 'Site Agreement';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SEARCH = 'Search';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE = 'Personal';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE_MODIFY = 'Profile';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE_SIGNUP = 'Signup';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_CART = 'Cart';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_ORDER_SUCCESS = 'Success';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_ORDER_LIST = 'My orders';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_COMPARE = 'Goods compare';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_FAVORITES = 'Favorite goods';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_DELIVERY = 'Delivery';

const CONTROL_CONTENT_SITE_ADD_EMPTY = 'new empty site';
const CONTROL_CONTENT_SITE_ADD_WITH_CONTENT = 'new site with content';
const CONTROL_CONTENT_SITE_CATEGORY = 'Category';
const CONTROL_CONTENT_SITE_CATEGORY_ANY = 'any';
const CONTROL_CONTENT_SITE_ADD_DATA_ERROR = 'Unable to retrieve available sites list';
const CONTROL_CONTENT_SITE_ADD_PREVIEW = 'demo';
const CONTROL_CONTENT_SITE_ADD_DOWNLOADING = 'Downloading and deploying the site, please wait';
const CONTROL_CONTENT_SITE_ADD_DOWNLOADING_ERROR = 'Unable to download the site archive';

const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE = 'Display type';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_TRADITIONAL = 'Traditional';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_SHORTPAGE = 'Shortpage';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_LONGPAGE_VERTICAL = 'Longpage';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_LONGPAGE_HORIZONTAL = 'Longpage horizontal';

const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ACCESS = 'Access';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_USERS = 'users';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_VIEW = 'view';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_COMMENT = 'comment';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CHANGE = 'modify';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SUBSCRIBE = 'subscribe';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_EXTFIELDS = 'Extra';

const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE = 'Save changes';

const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_I = '&amp;s';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_U = '';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE = 'Warning: site%s will be deleted%s.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CONFIRMDELETE = 'Confirm removal';

const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_SETTINGS = 'Mobile Settings';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SIMPLE = 'Simple site';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE = 'Mobile site';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ADAPTIVE = 'Adaptive site';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_USE_RESS = 'Use RESS technology';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_FOR = 'Mobile version for the site';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_FOR_NOTICE = 'available only for mobile sites';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_REDIRECT = 'Use a forced redirection';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_NONE = '[no]';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_DELETE = 'delete';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_CHANGE = 'change';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_CRITERION = 'Define mobility by: ';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_USERAGENT = 'User-agent';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_SCREEN_RESOLUTION = 'Screen resolution';
const CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_ALL_CRITERION = 'All criteria';

const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_CREATED = 'Creation date';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_UPDATED = 'Modification date';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SECTIONSCOUNT = 'Subsection amount';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SITESTATUS = 'Site status';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ON = 'on';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_OFF = 'off';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADD = 'add';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS = 'users';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_READACCESS = 'Read access';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADDACCESS = 'Write access';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_EDITACCESS = 'Modify access';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SUBSCRIBEACCESS = 'Subscribe access';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_PUBLISHACCESS = 'Object publication';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_VIEW = 'Browse';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADDING = 'Write';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SEARCHING = 'Search';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SUBSCRIBING = 'Subscribe';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_EDIT = 'Manage';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_DELETE = 'Delete site';

const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SITE = 'Site';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SUBSECTIONS = 'Subsections';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_PRIORITY = 'Priority';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_GOTO = 'Go to';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE = 'Delete';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_LIST = 'list';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_TOOPTIONS = 'change settings';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SHOW = 'view the site';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_EDIT = 'edit the content';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_NONE = 'No site in project';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE = 'Add new site';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SAVE = 'Save changes';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DBERROR = 'Database error!';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SECTIONWASCREATED = 'Section created: %s<br>';

// CONTROL CONTENT SUBDIVISION
const CONTROL_CONTENT_SUBDIVISION_FAVORITES_TITLE = 'Quick edit';
const CONTROL_CONTENT_SUBDIVISION_FULL_TITLE = 'Site tree';

// CONTROL CONTENT SUBDIVISION
const CONTROL_CONTENT_SUBDIVISION_INDEX_SITES = 'Sites';
const CONTROL_CONTENT_SUBDIVISION_INDEX_SECTIONS = 'Sections';
const CONTROL_CONTENT_SUBDIVISION_CLASS = 'Component in subdivision';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ADDSECTION = 'New section';
const CONTROL_CONTENT_SUBDIVISION_INDEX_OPTIONSECTION = 'Change settings';
const CONTROL_CONTENT_SUBDIVISION_INDEX_DELETECONFIRMATION = 'Confirm removal';
const CONTROL_CONTENT_SUBDIVISION_INDEX_MOVESECTION = 'Move section to';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_NAME = 'Enter Name!';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_KEYWORD = 'Invalid Keyword!';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_PARENTSUB = 'Parent subdivision is not selected!';
const CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR = 'Error adding section';


const CONTROL_CONTENT_SUBDIVISION_SUCCESS_EDIT = 'Section settings were saved.';


const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SECTION = 'Section components';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_CLASSLIST_SITE = 'Site components';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASS = 'New component';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_OPTIONSCLASS = 'Section component settings';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ADDCLASSSITE = 'Add new component to site';
const CONTROL_CONTENT_AREA_SUBCLASS_SETTINGS_TOOLTIP = 'Infoblock settings';

const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_NAME = 'Infoblock name can not be empty';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID = 'The keyword contains forbidden symbols or is too long. It must contain letters, numbers and underscores only. Maximum keyword length is 64 characters.';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD = 'Invalid Keyword!';

const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_SUCCESS_ADD = 'Infoblock added successfully';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_ADD = 'Error adding infoblock';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_SUCCESS_EDIT = 'Infoblock successfully changed';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_EDIT = 'Error changing infoblock';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_DELETE = 'Error deleting infoblock';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_LIST_SUCCESS_EDIT = 'Infoblock list successfully changed';
const CONTROL_CONTENT_SUBDIVISION_SUBCLASS_LIST_ERROR_EDIT = 'Error changing infoblock list';

const CONTROL_CONTENT_SUBDIVISION_FIRST_SUBCLASS = 'There are no infoblocks in this section.<br>There should be at least one in order to be able to add information to it.';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION = 'Sections';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_SUBSECTIONS = 'Subsections';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_GOTO = 'Go to';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_NOONEFAVORITES = 'No favorite sections.';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONS = 'change settings';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONSSUBCLASS = 'change component in subdivision';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_TOVIEW = 'view the site';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_TOEDIT = 'edit the content';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_PRIORITY = 'Priority';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_DELETE = 'Delete';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_NONE = 'none';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LIST = 'list';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ADD = 'add';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_NOSECTIONS = 'Current site has no sections.';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_NOSUBSECTIONS = 'Current section has no subsections.';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION = 'Add new section';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_CONTINUE = 'Continue';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_SELECT_ROOT_SECTION = 'Select root for new section';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_SAVE = 'Save changes';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDFAVOTITES = 'show this section in &quot;Favorites&quot;';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_USEEDITDESIGNTEMPLATE = 'Use this template when edit objects';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA = 'General Information';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME = 'Name';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD = 'Keyword';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_EXTURL = 'External URL';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_LANG = 'Section language';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE = 'Design template';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_CS = 'Custom settings';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_EDIT = 'Edit template source';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_N = 'Inherit';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_MAINAREA_MIXIN_SETTINGS = 'Main area display settings';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON = 'turn on';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNOFF = 'turn off';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION = 'add subsection';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_REMSITE = 'delete site';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_MULTI_SUB_CLASS = 'Multiple conponents';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE = 'Display type';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_INHERIT = 'Inherit';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_TRADITIONAL = 'Traditional';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_SHORTPAGE = 'Shortpage';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_LONGPAGE_VERTICAL = 'Longpage';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_LONGPAGE_HORIZONTAL = 'Longpage horizontal';

const CONTROL_TEMPLATE_CUSTOM_SETTINGS_NOT_AVAILABLE = "The selected design template doesn't have additional settings.";
const CONTROL_TEMPLATE_CUSTOM_SETTINGS = 'Section view settings';
const CONTROL_TEMPLATE_CUSTOM_SETTINGS_ISNOTSET = 'There are no additional design template settings in current section.';
const CONTROL_TEMPLATE_CUSTOM_SETTINGS_INHERITED_FROM_SITE = "Values of the parameters not set here will be inherited
        from the <a href='%s' target='_top'>site template settings</a>.";
const CONTROL_TEMPLATE_CUSTOM_SETTINGS_INHERITED_FROM_FOLDER = "Values of the parameters not set here will be inherited
        from the <a href='%s' target='_top'>template settings of the &ldquo;%s&rdquo; section</a>.";

const CONTROL_CUSTOM_SETTINGS_INHERIT = 'inherit value from parent section';
const CONTROL_CUSTOM_SETTINGS_OFF = 'no';
const CONTROL_CUSTOM_SETTINGS_ON = 'yes';

const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_EDIT = 'edit the content';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_KILL = 'remove this section';
const CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW = 'browse the page';
const CONTROL_CONTENT_CATALOUGE_FUNCS_MSG_OK = 'Section added.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_A_ADDCLASSTOSECTION = 'Add data template to section';
const CONTROL_CONTENT_CATALOUGE_FUNCS_A_BACKTOSECTIONLIST = 'Back to section list';

const CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOCATALOGUE = 'Catalogue does not exist.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBDIVISION = 'Section does not exist.';
const CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBCLASS = 'Sub class does not exist.';

const CLASSIFICATOR_COMMENTS_DISABLE = 'disable';
const CLASSIFICATOR_COMMENTS_ENABLE = 'enable';
const CLASSIFICATOR_COMMENTS_NOREPLIED = 'enable if not replies';

const CONTROL_CONTENT_CATALOGUE_FUNCS_COMMENTS = 'Comments';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS = 'Comments';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS_ADD = 'Add comments';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS_AUTHOR_EDIT = "Edit user's own comments";
const CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS_AUTHOR_DELETE = "Delete user's own comments";

const CONTROL_CONTENT_CATALOGUE_FUNCS_DEMO_MODE = 'Demo mode';
const CONTROL_CONTENT_CATALOGUE_FUNCS_DEMO_MODE_CHECKBOX = 'Turn on site demo mode';

const CONTROL_CONTENT_SUBCLASS_FUNCS_COMMENTS = 'Comments';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS = 'Access';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_READ = 'Read access';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_ADD = 'Write access';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_EDIT = 'Modify access';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_SUBSCRIBE = 'Subscribe access';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INFO_PUBLISH = 'Object publication';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT = 'inherit';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_PUBLISH = 'Object publishing';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_USERS = 'users';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_VIEW = 'view';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_READ = 'view';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_COMMENT = 'comment';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_ADD = 'add';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_WRITE = 'add';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_EDIT = 'edit';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_CHECKED = 'checked';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_DELETE = 'delete';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_SUBSCRIBE = 'subscribe';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_ADVANCEDFIELDS = 'Extra';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_HOWSHOW = 'Display settings';
const CONTROL_CONTENT_SUBDIVISION_CUSTOM_SETTINGS_TEMPLATE = "Component's visual settings";
const CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES = 'Yes';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO = 'No';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_UPDATED = 'Modification date';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_CLASS_COUNT = 'Component mount';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_STATUS = 'Section status';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_SUBSECTIONS_COUNT = 'Subsection amount';


const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE = 'Delete section';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_ROOT = 'Root';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE_CONFIRMATION = 'Confirm removal';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING = 'Warning: site%s will be deleted%s.';
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_ONE_MANY = "'s";
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_ONE_ONE = "";
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_TWO_MANY = "";
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_TWO_ONE = "";
const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_ERR_NOONESITE = 'Specified site does not exist.';

const CONTROL_CONTENT_SUBDIVISION_SYSTEM_FIELDS = 'System';
const CONTROL_CONTENT_SUBDIVISION_SYSTEM_FIELDS_NO = 'No system fields';

const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_ALWAYS = 'Always';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_HOURLY = 'Hourly';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_DAILY = 'Daily';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_WEEKLY = 'Weekly';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_MONTHLY = 'Monthly';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_YEARLY = 'Yearly';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_NEVER = 'Never';

const CONTROL_CONTENT_SUBDIVISION_SEO_META = 'SEO meta tags';
const CONTROL_CONTENT_SUBDIVISION_SEO_SMO_META = 'SMO meta tags';
const CONTROL_CONTENT_SUBDIVISION_SEO_INDEXING = 'Indexing';
const CONTROL_CONTENT_SUBDIVISION_SEO_CURRENT_VALUE = 'Current value';
define("CONTROL_CONTENT_SUBDIVISION_SEO_VALUE_NOT_SETTINGS",
    "The value of %s on the page is different from what you entered." . (CMS_SYSTEM_NAME === 'Netcat' ?
        " <a target='_blank' href='https://netcat.ru/developers/docs/seo/title-keywords-and-description/'>More</a>." :
        ''));
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_HEADER = 'Last modified headline';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_NONE = 'Don&#039;t send';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_YESTERDAY = 'Send previous day';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_HOUR = 'Send previous hour';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_CURRENT = 'Send the current date';
const CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_ACTUAL = 'Send the actual date';
const CONTROL_CONTENT_SUBDIVISION_SEO_DISALLOW_INDEXING = 'Enable indexing';
const CONTROL_CONTENT_SUBDIVISION_SEO_DISALLOW_INDEXING_YES = 'yes';
const CONTROL_CONTENT_SUBDIVISION_SEO_DISALLOW_INDEXING_NO = 'no';
const CONTROL_CONTENT_SUBDIVISION_SEO_INCLUDE_IN_SITEMAP = 'Include section in sitemap';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_PRIORITY = 'Sitemap: priority';
const CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ = 'Sitemap: Change frequency';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE_SUCCESS = 'Removing success.';

const CONTROL_CONTENT_CLASS = 'Class';
const CONTROL_CONTENT_SUBCLASS_CLASSNAME = 'Block name';
const CONTROL_CONTENT_SUBCLASS_SHOW_ALL = 'show all';
const CONTROL_CONTENT_SUBCLASS_ONSECTION = 'to section';
const CONTROL_CONTENT_SUBCLASS_ONSITE = 'to site';
const CONTROL_CONTENT_SUBCLASS_MSG_NONE = 'Section has no component.';
const CONTROL_CONTENT_SUBCLASS_DEFAULTACTION = 'Default action';
const CONTROL_CONTENT_SUBCLASS_CREATIONDATE = 'Template creation date %s';
const CONTROL_CONTENT_SUBCLASS_UPDATEDATE = 'Template modification update %s';
const CONTROL_CONTENT_SUBCLASS_TOTALOBJECTS = 'Total objects';
const CONTROL_CONTENT_SUBCLASS_CLASSSTATUS = 'Component status';
const CONTROL_CONTENT_SUBCLASS_CHANGEPREFS = 'Change component settings %s';
const CONTROL_CONTENT_SUBCLASS_DELETECLASS = 'Delete component %s';
const CONTROL_CONTENT_SUBCLASS_ISNAKED = 'Do not use design template';
const CONTROL_CONTENT_SUBCLASS_SRCMIRROR = 'Data source';
const CONTROL_CONTENT_SUBCLASS_SRCMIRROR_NONE = '[no]';
const CONTROL_CONTENT_SUBCLASS_SRCMIRROR_EDIT = 'edit';
const CONTROL_CONTENT_SUBCLASS_SRCMIRROR_DELETE = 'delete';
const CONTROL_CONTENT_SUBCLASS_TYPE = 'Subclass type';
const CONTROL_CONTENT_SUBCLASS_TYPE_SIMPLE = 'simple';
const CONTROL_CONTENT_SUBCLASS_TYPE_MIRROR = 'mirror';
const CONTROL_CONTENT_SUBCLASS_MIRROR = 'Mirror subclass';
const CONTROL_CONTENT_SUBCLASS_MULTI_TITLE = 'Type of displaying';
const CONTROL_CONTENT_SUBCLASS_MULTI_ONONEPAGE = 'display on single page';
const CONTROL_CONTENT_SUBCLASS_MULTI_ONTABS = 'display in tabs';
const CONTROL_CONTENT_SUBCLASS_MULTI_NONE = 'display only first subclass';
const CONTROL_CONTENT_SUBCLASS_EDIT_IN_PLACE = 'This subclass data should be edited here: "<a href=\'%s\'>%s</a>"';
const CONTROL_CONTENT_SUBCLASS_CONDITION_OFFSET = 'How many objects should be skipped from the start of selection';
const CONTROL_CONTENT_SUBCLASS_CONDITION_LIMIT = 'Maximum number of objects in the selection';
const CONTROL_CONTENT_SUBCLASS_FUNCS_SETTINGS_GOTO = 'Go to';
const CONTROL_CONTENT_SUBCLASS_CONTAINER = 'Container';
const CONTROL_CONTENT_SUBCLASS_AREA = 'Area &quot;%s&quot;';

const CONTROL_SETTINGSFILE_TITLE_ADD = 'New';
const CONTROL_SETTINGSFILE_TITLE_EDIT = 'Edit';
const CONTROL_SETTINGSFILE_BASIC_REGCODE = 'Registration code';
const CONTROL_SETTINGSFILE_BASIC_MAIN = 'Main info';
const CONTROL_SETTINGSFILE_BASIC_MAIN_NAME = 'Project name';

const CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE = 'Design template used to edit objects';
const CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE_DEFAULT = 'design template assigned to the edited section';

const CONTROL_SETTINGSFILE_SHOW_EXCURSION = 'Show excursion for current user';

const CONTROL_SETTINGSFILE_BASIC_EMAILS = 'Email sender';
const CONTROL_SETTINGSFILE_BASIC_EMAILS_FILELD = 'Field (with email) in user table';
const CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMNAME = 'Sender name';
const CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMEMAIL = 'Sender email';
const CONTROL_SETTINGSFILE_BASIC_EMAILS_TOEMAIL = 'Default notification email';
const CONTROL_SETTINGSFILE_BASIC_CHANGEDATA = 'Change system settings';

const CONTROL_SETTINGSFILE_BASIC_USE_SMTP = 'Use SMTP';
const CONTROL_SETTINGSFILE_BASIC_USE_SENDMAIL = 'Use Sendmail';
const CONTROL_SETTINGSFILE_BASIC_USE_MAIL = 'Use mail() function';
const CONTROL_SETTINGSFILE_BASIC_MAIL_PARAMETERS = 'Additional parameters for sendmail (<code>%s</code> for sender address)';
const CONTROL_SETTINGSFILE_BASIC_SMTP_HOST = 'SMTP host';
const CONTROL_SETTINGSFILE_BASIC_SMTP_PORT = 'Port';
const CONTROL_SETTINGSFILE_BASIC_SMTP_AUTH_USE = 'Use authorization';
const CONTROL_SETTINGSFILE_BASIC_SMTP_USERNAME = 'Username';
const CONTROL_SETTINGSFILE_BASIC_SMTP_PASSWORD = 'Password';
const CONTROL_SETTINGSFILE_BASIC_SMTP_ENCRYPTION = 'Encryption';
const CONTROL_SETTINGSFILE_BASIC_SMTP_NOENCRYPTION = 'None';
const CONTROL_SETTINGSFILE_BASIC_SENDMAIL_COMMAND = "Sendmail command (for example: '/usr/sbin/sendmail -bs')";
const CONTROL_SETTINGSFILE_BASIC_MAIL_TRANSPORT_HEADER = 'Transport type';

const CONTROL_SETTINGSFILE_AUTOSAVE = '"Drafts" settings';
const CONTROL_SETTINGSFILE_AUTOSAVE_USE = 'Use "Drafts" option';
const CONTROL_SETTINGSFILE_AUTOSAVE_TYPE_KEYBOARD = 'Save on keys above';
const CONTROL_SETTINGSFILE_AUTOSAVE_TYPE_TIMER = 'Save periodically';
const CONTROL_SETTINGSFILE_AUTOSAVE_PERIOD = 'Periodicity, sec';
const CONTROL_SETTINGSFILE_AUTOSAVE_NO_ACTIVE = 'Save while inactivity only';

const CONTROL_SETTINGSFILE_INLINE_IMAGE_CROP = 'Image crop settings';
const CONTROL_SETTINGSFILE_INLINE_IMAGE_CROP_USE = 'Use image crop';
const CONTROL_SETTINGSFILE_INLINE_IMAGE_CROP_DIMENSIONS = 'Predefined dimensions (Width x Height)';

const CONTROL_SETTINGSFILE_DOCHANGE_ERROR_NAME = 'Project name cannot be empty!';

const NETCAT_AUTH_TYPE_LOGINPASSWORD = 'by login';
const NETCAT_AUTH_TYPE_TOKEN = 'by token';
const NETCAT_AUTH_TYPE_SSO = 'Log in using %s';
const CONTROL_AUTH_ON_ONE_SITE = 'Authorize on site';
const CONTROL_AUTH_ON_ALL_SITES = 'On all sites';
const CONTROL_AUTH_HTML_LOGIN = 'Login';
const CONTROL_AUTH_HTML_PASSWORD = 'Password';
const CONTROL_AUTH_HTML_PASSWORDCONFIRM = 'Password again';
const CONTROL_AUTH_HTML_SAVELOGIN = 'Save login and password';
const CONTROL_AUTH_HTML_LANG = 'Language';
const CONTROL_AUTH_HTML_AUTH = 'Log In';
const CONTROL_AUTH_HTML_BACK = 'Back';
define("CONTROL_AUTH_FIELDS_NOT_EMPTY",
    "Fields \"" . CONTROL_AUTH_HTML_LOGIN . "\" and \"" . CONTROL_AUTH_HTML_PASSWORD . "\" must be not empty!");
define("CONTROL_AUTH_LOGIN_NOT_EMPTY", "Field \"" . CONTROL_AUTH_HTML_LOGIN . "\" must be not empty!");
const CONTROL_AUTH_LOGIN_OR_PASSWORD_INCORRECT = 'Authorize data incorrect!';
const CONTROL_AUTH_PIN_INCORRECT = 'PIN-code incorrect';
const CONTROL_AUTH_TOKEN_PLUGIN_DONT_INSTALL = "Plugin isn't installed";
const CONTROL_AUTH_KEYPAIR_INCORRECT = 'Error';
const CONTROL_AUTH_USB_TOKEN_NOT_INSERTED = 'Token not inserted';
const CONTROL_AUTH_TOKEN_CURRENT_TOKENS = 'Current authorized user tokens';
const CONTROL_AUTH_TOKEN_NEW = 'Authorize new token';
const CONTROL_AUTH_TOKEN_PLUGIN_ERROR = "<a href='http://www.rutoken.ru/hotline/download/' target='_blank'>Browser plugin</a> for work with token is not installed";
const CONTROL_AUTH_TOKEN_MISS = 'Token missing';
const CONTROL_AUTH_TOKEN_NEW_BUTTON = 'Authorize';

const CONTROL_AUTH_JS_REQUIRED = 'To work in administration system you need to turn on javascript support.';

const NETCAT_MODULE_AUTH_INSIDE_ADMIN_ACCESS = 'Inside admin access';
const CONTROL_AUTH_MSG_MUSTAUTH = 'Enter valid Username and Password.';


const CONTROL_FS_NAME_SIMPLE = 'Simple';
const CONTROL_FS_NAME_ORIGINAL = 'Standard';
const CONTROL_FS_NAME_PROTECTED = 'Protected';

const CONTROL_CLASS_CLASS_TEMPLATE = 'Component mapping template';
const CONTROL_CLASS_CLASS_TEMPLATE_CHANGE_LATER = 'You can change other infoblock settings after subdivision is added.';
const CONTROL_CLASS_CLASS_TEMPLATE_DEFAULT = 'Default template';
const CONTROL_CLASS_CLASS_TEMPLATE_EDIT_MODE = 'Template to use in edit mode';
const CONTROL_CLASS_CLASS_TEMPLATE_ADMIN_MODE = 'Template to use in administrative mode';
const CONTROL_CLASS_CLASS_TEMPLATE_EDIT_MODE_DONT_USE = '-- do not use custom template --';
const CONTROL_CLASS_CLASS_TEMPLATE_ADD = 'Add component template';
const CONTROL_CLASS_CLASS_DONT_USE_TEMPLATE = '-- do not use template --';
const CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NAME = 'Enter the name of the template component';
const CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NOT_FOUND = 'Templates component missing';
const CONTROL_CLASS_CLASS_TEMPLATE_DELETE_WARNING = 'Note: instead of templates will be used by the main component "%s".';
const CONTROL_CLASS_CLASS_TEMPLATE_NOT_FOUND = 'Template %s not found!';
const CONTROL_CLASS_CLASS_TEMPLATE_ERROR_ADD = 'Error adding template component';
const CONTROL_CLASS_CLASS_TEMPLATE_ERROR_EDIT = 'Error editing a template component';
const CONTROL_CLASS_CLASS_TEMPLATE_SUCCESS_ADD = 'Template component successfully added';
const CONTROL_CLASS_CLASS_TEMPLATE_SUCCESS_EDIT = 'Template component successfully changed';
const CONTROL_CLASS_CLASS_TEMPLATE_GROUP = 'Components templates';
const CONTROL_CLASS_CLASS_TEMPLATE_BUTTON_EDIT = 'Edit';
const CONTROL_CLASS_CLASS_TEMPLATES = 'Component templates';
const CONTROL_CLASS_CLASS_TEMPLATE_RECORD_TEMPLATE_WARNING = 'Attention! If you plan to add elements to this block and not plan to display elements from other blocks in it, you will not be able to go to the full view page of the object.<br>Do you want to continue anyway?';
const CLASS_TEMPLATE_TAB_EDIT = 'Edit template';
const CLASS_TEMPLATE_TAB_DELETE = 'Delete template';
const CLASS_TEMPLATE_TAB_INFO = 'Information';

const CONTROL_CLASS = 'Components';
const CONTROL_CLASS_ADD_ACTION = 'Add component';
const CONTROL_CLASS_DELETECOMMIT = 'Confirm removal of content template';
const CONTROL_CLASS_DOEDIT = 'Edit component';
const CONTROL_CLASS_CONTINUE = 'Continue';
const CONTROL_CLASS_NONE = 'No components.';
const CONTROL_CLASS_ADD = 'New component';
const CONTROL_CLASS_ADD_FS = 'New component 5.0';
const CONTROL_CLASS_CLASS = 'Component';
const CONTROL_CLASS_SYSTEM_TABLE = 'System table';
const CONTROL_CLASS_ACTIONS = 'Component actions';
const CONTROL_CLASS_FIELD = 'Field';
const CONTROL_CLASS_FIELDS = 'Fields';
const CONTROL_CLASS_FIELDS_COUNT = 'Fields';
const CONTROL_CLASS_CUSTOM = 'Custom settings';
const CONTROL_CLASS_DELETE = 'Delete';
const CONTROL_CLASS_NEWCLASS = 'New template';
const CONTROL_CLASS_NEWTEMPLATE = 'New class template';
const CONTROL_CLASS_TO_FS = 'Class to the FS';

const CONTROL_CLASS_FUNCS_SHOWCLASSLIST_ADDCLASS = 'New component';
const CONTROL_CLASS_FUNCS_SHOWCLASSLIST_IMPORTCLASS = 'Import component';

const CONTROL_CLASS_ACTIONS_VIEW = 'view';
const CONTROL_CLASS_ACTIONS_ADD = 'add';
const CONTROL_CLASS_ACTIONS_EDIT = 'modify';
const CONTROL_CLASS_ACTIONS_CHECKED = 'checked in';
const CONTROL_CLASS_ACTIONS_SEARCH = 'search';
const CONTROL_CLASS_ACTIONS_MAIL = 'subscribe';
const CONTROL_CLASS_ACTIONS_DELETE = 'delete';
const CONTROL_CLASS_ACTIONS_MODERATE = 'moderate';
const CONTROL_CLASS_ACTIONS_ADMIN = 'administrate';

const CONTROL_OBJECT_NAME_LABEL = 'Field with object name';
const CONTROL_OBJECT_NAME_NOT_SELECTED = 'Not selected';
const CONTROL_OBJECT_NAME_ACC = 'Object name in singular («add one <em>what</em>?»)';
const CONTROL_OBJECT_NAME_ACC_PLURAL = 'Object name in plural («delete all <em>what</em>?»)';
const CONTROL_OBJECT_NAME_GEN = 'Object name in singular genitive («edit a <em>what</em>?»)';

define("CONTROL_CLASS_INFO_ADDSLASHES", "Remember to <a href='#' onclick=\"window.open('".$ADMIN_PATH."template/converter.php', 'converter','width=600,height=410,status=no,resizable=yes');\">add slashes to template code</a>.");
const CONTROL_CLASS_ERRORS_DB = 'Database select error!';
const CONTROL_CLASS_CLASS_NAME = 'Name';
const CONTROL_CLASS_CLASS_KEYWORD = 'Keyword';
const CONTROL_CLASS_CLASS_MAIN_CLASSTEMPLATE_LABEL = 'Main class template';
const CONTROL_CLASS_CLASS_GROUPS = 'Component groups';
const CONTROL_CLASS_CLASS_NO_GROUP = 'Without group';
const CONTROL_CLASS_CLASS_OBJECTSLIST = 'Object list template';
const CONTROL_CLASS_CLASS_DESCRIPTION = 'Component description';
const CONTROL_CLASS_CLASS_SETTINGS = 'Component settings';
const CONTROL_SCLASS_ACTION = 'Template actions';
const CONTROL_SCLASS_TABLE = 'Table';
const CONTROL_SCLASS_TABLE_NAME = 'Table name';
const CONTROL_SCLASS_LISTING_NAME = 'List name';
const CONTROL_CLASS_CLASSFORM_INFO_FOR_NEWCLASS = "Component's information";
const CONTROL_CLASS_CLASSFORM_MAININFO = 'Main information';
const CONTROL_CLASS_CLASSFORM_TEMPLATE_PATH = "Files dir <a href='%s'>%s</a>";
const CONTROL_CLASS_SITE_STYLES = 'Styles for use on the site';
define("CONTROL_CLASS_SITE_STYLES_DISABLED_WARNING",
    "This component works in " . CMS_SYSTEM_NAME . " 5.6 compatibility mode. Component stylesheets are disabled.");
const CONTROL_CLASS_SITE_STYLES_ENABLE_BUTTON = 'Enable component template stylesheets';
const CONTROL_CLASS_SITE_STYLES_ENABLE_WARNING = 'When compatibility mode is off, additional markup (<code>&lt;div&gt;</code> wrapper) 
    will be added to the output of the following blocks:
    <ul><li>object lists from the infoblocks, 
    <li>main part of the object full view page, 
    <li>add, edit and search forms.</ul>
    It is possible that site templates CSS rules may require appropriate modifications
    if the compatibility mode is disabled.';
const CONTROL_CLASS_SITE_STYLES_DOCS_LINK = "See details <a href='%s' target='_blank'>in the manual</a>.";
const CONTROL_CLASS_MULTIPLE_MODE_SWITCH = 'Component is optimized for use in multiple infoblock view mode';
const CONTROL_CLASS_TEMPLATE_MULTIPLE_MODE_SWITCH = 'Component template is optimized for use in multiple infoblock view mode';
const CONTROL_CLASS_LIST_PREVIEW = 'Object list preview image (.png)';
const CONTROL_CLASS_LIST_PREVIEW_NONE = 'No preview image';

const CONTROL_CLASS_KEYWORD_ONLY_DIGITS = 'Keyword cannot consist of digits only';
const CONTROL_CLASS_KEYWORD_TOO_LONG = 'Keyword cannot be longer than %d characters';
const CONTROL_CLASS_KEYWORD_INVALID_CHARACTERS = 'Keyword can contain only latin letters, digits and underscores';
const CONTROL_CLASS_KEYWORD_NON_UNIQUE = 'Keyword &ldquo;%s&rdquo; is already assigned to the &ldquo;%s&rdquo; component';
const CONTROL_CLASS_KEYWORD_TEMPLATE_NON_UNIQUE = 'Keyword &ldquo;%s&rdquo; is already assigned to the &ldquo;%s&rdquo; template';
const CONTROL_CLASS_KEYWORD_RESERVED = 'Cannot use &ldquo;%s&rdquo; as a keyword, because it is a reserved word';

const CONTROL_CLASS_CLASSFORM_CHECK_ERROR = 'Error in the &quot;%s&quot; component field';

const CONTROL_CLASS_CLASS_OBJECTSLIST_PREFIX = 'Object list prefix';
const CONTROL_CLASS_CLASS_OBJECTSLIST_BODY = 'Object in list';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SUFFIX = 'Object list suffix';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW = 'List';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ = 'objects per page';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW_NUM = 'Objects per page';
const CONTROL_CLASS_CLASS_MIN_RECORDS = 'Minimum number of objects in an infoblock';
const CONTROL_CLASS_CLASS_MAX_RECORDS = 'Maximum number of objects in an infoblock';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SORT = 'Sort objects by field(s)';
const CONTROL_CLASS_CLASS_OBJECTSLIST_TITLE = 'Title';

const CONTROL_CLASS_CLASS_OBJECTSLIST_WRONG_NC_CTPL = 'nc_ctpl passed to nc_object_list(%s, %s) is wrong(%s). ';
const CONTROL_CLASS_CLASS_OBJECTFULL_WRONG_NC_CTPL = 'Wrong nc_ctpl - %s. ';

const CONTROL_CLASS_CLASS_OBJECTVIEW = 'Single object at individual page template';

const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_DOPL = 'Extra';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_CACHE = 'Cache';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_SYSTEM = 'Extra settings';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR = 'Replace line breaks with &lt;BR&gt;';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML = 'Allow HTML tags';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGETITLE = 'Page header';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_USEASALT = 'Use as alternate title';
const CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGEBODY = 'Object template';
const CONTROL_CLASS_CLASS_CREATENEW_BASICOLD = 'Create new component based on extended component';
const CONTROL_CLASS_CLASS_CREATENEW_CLEARNEW = 'Create new component';
const CONTROL_CLASS_CLASS_DELETE_WARNING = 'Warning: component%s will be deleted.';
const CONTROL_CLASS_CLASS_NOT_FOUND = 'Component %s not found!';

const CONTROL_CLASS_CUSTOM_SETTINGS_TEMPLATE = 'View settings component';
const CONTROL_CLASS_CUSTOM_SETTINGS_PARAMETER = 'Parameter';
const CONTROL_CLASS_CUSTOM_SETTINGS_DEFAULT = 'Default value';
const CONTROL_CLASS_CUSTOM_SETTINGS_VALUE = 'Local value';
const CONTROL_CLASS_CUSTOM_SETTINGS_HAS_ERROR = 'There are errors in some fields. Please fix the values and submit the form again.';

const CONTROL_CLASS_IMPORT = 'Import';
const CONTROL_CLASS_IMPORTS = 'Import';
const CONTROL_CLASS_IMPORT_UPLOAD = 'Upload';
const CONTROL_CLASS_IMPORT_ERROR_NOTUPLOADED = 'The file is not uploaded.';
const CONTROL_CLASS_IMPORT_ERROR_CANNOTBEINSTALLED = 'Cannot install component.';
const CONTROL_CLASS_IMPORT_ERROR_VERSION_ID = 'Component version %s, current system version %s.';
const CONTROL_CLASS_IMPORT_ERROR_NO_VERSION_ID = 'System version could not be defined or wrong file format.';
const CONTROL_CLASS_IMPORT_ERROR_NO_FILES = 'Templates file data is missing.';
const CONTROL_CLASS_IMPORT_ERROR_CLASS_IMPORT = 'Error creating component.';
const CONTROL_CLASS_IMPORT_ERROR_CLASS_TEMPLATE_IMPORT = 'Error creating template component.';
const CONTROL_CLASS_IMPORT_ERROR_MESSAGE_TABLE = 'Error creating data table.';
const CONTROL_CLASS_IMPORT_ERROR_FIELD = 'Error creating component fields.';

const CONTROL_CLASS_CONVERT = 'Component conversion';
const CONTROL_CLASS_CONVERT_BUTTON = 'Convert v4 -> v5';
const CONTROL_CLASS_CONVERT_BUTTON_UNDO = 'Undo conversion';
const CONTROL_CLASS_CONVERT_DB_ERROR = 'Error editing component data table';
const CONTROL_CLASS_CONVERT_OK = 'Successful conversion';
const CONTROL_CLASS_CONVERT_OK_GOEDIT = 'Go to component edit screen';
const CONTROL_CLASS_CONVERT_CLASSLIST_TITLE = 'Components to convert';
const CONTROL_CLASS_CONVERT_CLASSLIST_TITLE_UNDO = 'Conversion will be undone for the following components';
const CONTROL_CLASS_CONVERT_CLASSFOLDERS_TITLE = 'Folders containing 5.0 template files, and v4 template backup files(class_40_backup.html) will be created';
const CONTROL_CLASS_CONVERT_CLASSFOLDERS_TITLE_UNDO = 'Folders can be deleted';
const CONTROL_CLASS_CONVERT_NOTICE = 'Syntax errors may appear after component conversion.
    We strongly recommend to temporarily disable your web-site.';
const CONTROL_CLASS_CONVERT_NOTICE_UNDO = "After the conversion cancellation, component will return to it's initial state. Any changes made in 5.0 mode will be lost!";
const CONTROL_CLASS_CONVERT_UNDO_FILE_ERROR = 'No data to restore';

const CONTROL_CLASS_NEWGROUP = 'new group';
const CONTROL_CLASS_EXPORT = 'Export component to file';
const CONTROL_CLASS_AUXILIARY_SWITCH = 'Is an auxiliary (non-content) component';
const CONTROL_CLASS_AUXILIARY = '(auxiliary)';
const CONTROL_CLASS_BLOCK_MARKUP_SWITCH = "Disable <a href='https://netcat.ru/developers/docs/components/stylesheets/' target='_blank'>additional block markup</a>";
const CONTROL_CLASS_BLOCK_LIST_MARKUP_SWITCH = 'Disable object list markup (block style and layout tools will not be available)';
const CONTROL_CLASS_BLOCK_MARKUP_SWITCH_WARNING = 'Additional block markup is essential for the component template stylesheets and edit mode support.';

const CONTROL_CLASS_COMPONENT_TEMPLATE_FOR_RSS_DOESNT_EXIST = 'Rss-feed %s not available because there is no template component for the rss';
const CONTROL_CLASS_COMPONENT_TEMPLATE_FOR_XML_DOESNT_EXIST = 'Xml-unloading %s not available because there is no template component for xml';
const CONTROL_CLASS_COMPONENT_TEMPLATE_FOR_TRASH_DOESNT_EXIST = 'Trash can not available because there is no template component for trash can';

const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE = 'Type';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_CLASSTEMPLATE = 'Template component type';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_MULTI_EDIT = 'Multiple edit';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_RSS = 'RSS';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_XML = 'XML-unloading';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_TRASH = 'Trash can';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_USEFUL = 'Useful';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_INSIDE_ADMIN = 'Administrative mode';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_ADMIN_MODE = 'Edit mode';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_TITLE = 'For title page';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_MOBILE = 'Mobile';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_RESPONSIVE = 'Responsive';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_OBJECT_PARTIAL = 'Object full page block';

const CONTROL_CLASS_COMPONENT_TEMPLATE_BASE_AUTO = 'Automatically';
const CONTROL_CLASS_COMPONENT_TEMPLATE_BASE_EMPTY = 'Empty';
const CONTROL_CLASS_COMPONENT_TEMPLATE_ADD_PARAMETRS = 'Component template add parameters';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_BASE = 'Create component template based on';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_FOR_TRASH = 'Create template for trash can';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_FOR_RSS = 'Create template for rss';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_FOR_XML = 'Create template for xml';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TURN_ON_RSS = 'On rss-feed';
const CONTROL_CLASS_COMPONENT_TEMPLATE_TURN_ON_XML = 'On xml-unloading';
const CONTROL_CLASS_COMPONENT_TEMPLATE_VIEW = 'View';
const CONTROL_CLASS_COMPONENT_TEMPLATE_EDIT = 'Edit';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_ERROR = 'Template can not be created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_USEFUL = 'Template component successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_RSS = 'Template component for RSS successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_XML = 'Template component for XML successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_TRASH = 'Template component for trash bin successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_INSIDE_ADMIN = 'Template component to edit mode successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_ADMIN_MODE = 'Template component for administrative operations successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_TITLE = 'Template component for title page successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_MOBILE = 'Template for mobile site successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_MULTI_EDIT = 'Multi-edit component template successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_RESPONSIVE = 'Component template for responsive site successfully created';
const CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_OBJECT_PARTIAL = 'Component template for object pages successfully created';

const CONTROL_CLASS_COMPONENT_TEMPLATE_RETURN_TO_SUB = 'Return</a> to subdivision settings';
const CONTROL_CLASS_COMPONENT_TEMPLATE_RETURN_TO_TRASH = 'Return</a> to trash bin';
const CONTROL_CLASS_SHOW_AUX = 'Show auxiliary';
const CONTROL_CLASS_DEFAULT_CHANGE = 'Default class can be changed in site settings';

const CONTROL_CONTENT_CLASS_SUCCESS_ADD = 'Component added successfully';
const CONTROL_CONTENT_CLASS_ERROR_ADD = 'Error adding component';
const CONTROL_CONTENT_CLASS_ERROR_NAME = 'Enter component Name';
const CONTROL_CONTENT_CLASS_GROUP_ERROR_NAME = 'Group name must not begin from digit';
const CONTROL_CONTENT_CLASS_SUCCESS_EDIT = 'Component successfully changed';
const CONTROL_CONTENT_CLASS_ERROR_EDIT = 'Error changing component';

#TYPE OF DATA
const CLASSIFICATOR_TYPEOFDATA_STRING = 'String';
const CLASSIFICATOR_TYPEOFDATA_INTEGER = 'Integer';
const CLASSIFICATOR_TYPEOFDATA_TEXTBOX = 'Text box';
const CLASSIFICATOR_TYPEOFDATA_LIST = 'Classificator';
const CLASSIFICATOR_TYPEOFDATA_BOOLEAN = 'Boolean (true or false)';
const CLASSIFICATOR_TYPEOFDATA_FILE = 'File';
const CLASSIFICATOR_TYPEOFDATA_FLOAT = 'Float';
const CLASSIFICATOR_TYPEOFDATA_DATETIME = 'Date and time';
const CLASSIFICATOR_TYPEOFDATA_RELATION = 'Relation to other object';
const CLASSIFICATOR_TYPEOFDATA_MULTILIST = 'MultiClassificator';
const CLASSIFICATOR_TYPEOFDATA_MULTIFILE = 'Multifile upload';

const CLASSIFICATOR_TYPEOFFILESYSTEM = 'Type of filesystem';

const CLASSIFICATOR_TYPEOFEDIT_ALL = 'all users';
const CLASSIFICATOR_TYPEOFEDIT_ADMINS = 'administrators';
const CLASSIFICATOR_TYPEOFEDIT_NOONE = 'no one';

const CLASSIFICATOR_TYPEOFMODERATION_RIGHTAWAY = 'after addition';
const CLASSIFICATOR_TYPEOFMODERATION_MODERATION = 'after moderation check';

const CLASSIFICATOR_USERGROUP_ALL = 'all';
const CLASSIFICATOR_USERGROUP_REGISTERED = 'registered';
const CLASSIFICATOR_USERGROUP_AUTHORIZED = 'authorized';

const CONTROL_TEMPLATE_CLASSIFICATOR = 'Add slashes';
const CONTROL_TEMPLATE_CLASSIFICATOR_EKRAN = 'Add slashes';
const CONTROL_TEMPLATE_CLASSIFICATOR_RES = 'Result';

const CONTROL_FIELD_LIST_NAME = 'Name';
const CONTROL_FIELD_LIST_NAMELAT = 'Name';
const CONTROL_FIELD_LIST_DESCRIPTION = 'Description';
const CONTROL_FIELD_LIST_ADD = 'New field';
const CONTROL_FIELD_LIST_CHANGE = 'Save changes';
const CONTROL_FIELD_LIST_DELETE = 'Delete field';
const CONTROL_FIELD_ADDING = 'New field';
const CONTROL_FIELD_EDITING = 'Edit field settings';
const CONTROL_FIELD_DELETING = 'Field deleting';
const CONTROL_FIELD_FIELDS = 'Fields';
const CONTROL_FIELD_LIST_NONE = 'No fields.';
const CONTROL_FIELD_ONE_FORMAT = 'Format';
const CONTROL_FIELD_ONE_FORMAT_NONE = 'none';
const CONTROL_FIELD_ONE_FORMAT_EMAIL = 'email';
const CONTROL_FIELD_ONE_FORMAT_URL = 'URL';
const CONTROL_FIELD_ONE_FORMAT_HTML = 'HTML-string';
const CONTROL_FIELD_ONE_FORMAT_PASSWORD = 'password';
const CONTROL_FIELD_ONE_FORMAT_PHONE = 'phone';
const CONTROL_FIELD_ONE_FORMAT_TAGS = 'tags';
const CONTROL_FIELD_ONE_PROTECT_EMAIL = 'Protect on output';
const CONTROL_FIELD_ONE_EXTENSION = 'Connected field';
const CONTROL_FIELD_ONE_MUSTBE = 'cannot be empty';
const CONTROL_FIELD_ONE_INDEX = 'ability to search by this field';
const CONTROL_FIELD_ONE_IN_TABLE_VIEW = 'show in table view mode';
const CONTROL_FIELD_ONE_INHERITANCE = 'inherit field value';
const CONTROL_FIELD_ONE_DEFAULT = 'Default state (sets if field has no value)';
define("CONTROL_FIELD_ONE_DEFAULT_NOTE",
    "for all types of fields except &quot;" . CLASSIFICATOR_TYPEOFDATA_TEXTBOX . "&quot;, &quot;" .
    CLASSIFICATOR_TYPEOFDATA_FILE . "&quot;, &quot;" . CLASSIFICATOR_TYPEOFDATA_DATETIME . "&quot;, &quot;" .
    CLASSIFICATOR_TYPEOFDATA_MULTILIST . "&quot;");
const CONTROL_FIELD_ONE_FTYPE = 'Type';
const CONTROL_FIELD_ONE_ACCESS = 'Access type';
const CONTROL_FIELD_ONE_RESERVED = 'This field name is reserved!';
const CONTROL_FIELD_NAME_ERROR = 'Invalid field name!';
const CONTROL_FIELD_DIGIT_ERROR = 'The field name can not begin with numbers';
const CONTROL_FIELD_DB_ERROR = 'DB error'; //TODO
const CONTROL_FIELD_EXITS_ERROR = 'Field already exists';
const CONTROL_FIELD_FORMAT_ERROR = 'Incorrect field format';
const CONTROL_FIELD_MSG_ADDED = 'Field was added successfully';
const CONTROL_FIELD_MSG_EDITED = 'Field modification was successful';
const CONTROL_FIELD_MSG_DELETED_ONE = 'Field was deleted successfully';
const CONTROL_FIELD_MSG_DELETED_MANY = 'Fields were deleted successfully';
const CONTROL_FIELD_MSG_CONFIRM_REMOVAL_ONE = 'Attention: field will be deleted.';
const CONTROL_FIELD_MSG_CONFIRM_REMOVAL_MANY = 'Attention: fields will be deleted.';
const CONTROL_FIELD_MSG_FIELDS_CHANGED = 'Fields priorities changed.';
const CONTROL_FIELD_CONFIRM_REMOVAL = 'Confirm removal';
const CONTROL_FIELD__EDITOR_EMBED_TO_FIELD = 'Embed editor into text area field';
const CONTROL_FIELD__TEXTAREA_SIZE = 'Size of textarea';
const CONTROL_FIELD_HEIGHT = 'height';
const CONTROL_FIELD_WIDTH = 'width';
const CONTROL_FIELD_ATTACHMENT = 'attachment';
const CONTROL_FIELD_DOWNLOAD_COUNT = 'count the number of downloads';
const CONTROL_FIELD_CAN_BE_AN_ICON = 'can be an icon';
const CONTROL_FIELD_CAN_BE_ONLY_ICON = 'only as icon';
const CONTROL_FIELD_PANELS = 'Use CKEditor panels set';
const CONTROL_FIELD_PANELS_DEFAULT = 'Default';
const CONTROL_FIELD_TYPO = 'typo';
const CONTROL_FIELD_TYPO_BUTTON = 'Typo text';
const CONTROL_FIELD_BBCODE_ENABLED = 'Enable bb-code';
const CONTROL_FIELD_USE_CALENDAR = 'Using calendar for select date';
const CONTROL_FIELD_FILE_UPLOADS_LIMITS = 'Your PHP configuration has the following limitations on uploading files:';
const CONTROL_FIELD_FILE_POSTMAXSIZE = 'max size of post data allowed';
const CONTROL_FIELD_FILE_UPLOADMAXFILESIZE = 'the maximum size of an uploaded file';
const CONTROL_FIELD_FILE_MAXFILEUPLOADS = 'the maximum number of files allowed to be uploaded simultaneously';
const CONTROL_FIELD_MULTIFIELD_USE_IMAGE_RESIZE = 'Use image resize';
const CONTROL_FIELD_MULTIFIELD_USE_IMAGE_CROP = 'Use image crop';
const CONTROL_FIELD_MULTIFIELD_CROP_IGNORE = 'Don\t crop if image is smaller than filled size';
const CONTROL_FIELD_MULTIFIELD_USE_IMAGE_PREVIEW = 'Create image preview';
const CONTROL_FIELD_MULTIFIELD_USE_PREVIEW_RESIZE = 'Use preview resize';
const CONTROL_FIELD_MULTIFIELD_PREVIEW_USE_IMAGE_CROP = 'Use preview crop';
const CONTROL_FIELD_MULTIFIELD_PREVIEW_CROP_IGNORE = 'Don\t crop if preview is smaller than filled size';
const CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH = 'Width';
const CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT = 'Height';
const CONTROL_FIELD_MULTIFIELD_CROP_CENTER = 'By center';
const CONTROL_FIELD_MULTIFIELD_CROP_COORD = 'By coordinates';
const CONTROL_FIELD_MULTIFIELD_MIN = 'Min';
const CONTROL_FIELD_MULTIFIELD_MAX = 'Max';
const CONTROL_FIELD_MULTIFIELD_MINMAX = 'Limit the number of files available for download';
const CONTROL_FIELD_USE_TRANSLITERATION = 'Transliteration';
const CONTROL_FIELD_TRANSLITERATION_FIELD = 'Transliteration result field';
const CONTROL_FIELD_USE_URL_RULES = 'Use URL syntax rules';
const CONTROL_FIELD_FILE_WRONG_GD = 'Server not including the extension GD2, resize and cropping images will not work';


// SYS
const TOOLS_SYSTABLE_SITES = 'Sites';
const TOOLS_SYSTABLE_SECTIONS = 'Sections';
const TOOLS_SYSTABLE_USERS = 'Users';
const TOOLS_SYSTABLE_TEMPLATE = 'Templates';


#DATABACKUP
const TOOLS_DATA_BACKUP = 'Import/Export data';
const TOOLS_DATA_BACKUP_IMPORT_FILE = 'Import file (*.tgz)';
const TOOLS_DATA_BACKUP_IMPORT_COMPLETE = 'Import complete!';
const TOOLS_DATA_BACKUP_IMPORT_ERROR = 'Import error!';
const TOOLS_DATA_BACKUP_IMPORT_DUPLICATE_KEY_ERROR = 'Duplicate primary keys for table.';
const TOOLS_DATA_BACKUP_EXPORT_COMPLETE = 'Export complete!';
define("TOOLS_DATA_BACKUP_INCOMPATIBLE_VERSION",
    "Import file has incompatible format. Please update your copy of " . CMS_SYSTEM_NAME . ".");
const TOOLS_DATA_SAVE_IDS = 'Save id';
const TOOLS_DATA_BACKUP_SYSTEM = 'System';
const TOOLS_DATA_BACKUP_DATATYPE = 'Data type';
const TOOLS_DATA_BACKUP_INSERT_OBJECTS = 'Added database records';
const TOOLS_DATA_BACKUP_CREATE_TABLES = 'Created database tables';
const TOOLS_DATA_BACKUP_COPIED_FILES = 'Added files/folders';
const TOOLS_DATA_BACKUP_SKIPPED_FILES = 'Skipped files/folders';
const TOOLS_DATA_BACKUP_REPLACED_FILES = 'Replaced files/folders';
const TOOLS_DATA_BACKUP_EXPORT_DATE = 'Export date';
const TOOLS_DATA_BACKUP_USED_SPACE = 'used';
const TOOLS_DATA_BACKUP_SPACE_FROM = 'from';

const TOOLS_DATA_BACKUP_DELETE_ALL_CONFIRMATION = 'Delete all files?';

const TOOLS_EXPORT = 'Export';
const TOOLS_IMPORT = 'Import';
const TOOLS_DOWNLOAD = 'Download';
const TOOLS_DATA_BACKUP_GOTO_OBJECT = 'Go to imported object';


const TOOLS_MODULES = 'Modules';
const TOOLS_MODULES_LIST = 'Module list';
const TOOLS_MODULES_INSTALLEDMODULE = 'Installed module';
const TOOLS_MODULES_ERR_INSTALL = 'Cannot install';
const TOOLS_MODULES_ERR_UNINSTALL = 'Cannot uninstall';
const TOOLS_MODULES_ERR_CANTOPEN = 'Cannot open a file';
const TOOLS_MODULES_ERR_PATCH = 'Required patch #';
const TOOLS_MODULES_ERR_VERSION = 'Module is not for current version';
const TOOLS_MODULES_ERR_INSTALLED = 'Module already installed';
const TOOLS_MODULES_ERR_ITEMS = 'Error';
const TOOLS_MODULES_ERR_DURINGINSTALL = 'Error processed during installation';
const TOOLS_MODULES_ERR_NOTUPLOADED = 'File not uploaded';
define("TOOLS_MODULES_ERR_EXTRACT",
    "Error while module archive extracting.Please try to extract archive to $nc_core->TMP_FOLDER on your server, and run module install once again.<br />");

const TOOLS_MODULES_MOD_NAME = 'Module';
const TOOLS_MODULES_MOD_PREFS = 'Settings';
const TOOLS_MODULES_MOD_GOINSTALL = 'Finish installation';
const TOOLS_MODULES_MOD_EDIT = 'change settings';
const TOOLS_MODULES_MOD_LOCAL = 'Install from local disc';
const TOOLS_MODULES_MOD_INSTALL = 'Module installation';
const TOOLS_MODULES_MSG_CHOISESECTION = 'To complete installation allow system to create necessary sections. Select parent section to create these sections in.';
const TOOLS_MODULES_PREFS_SAVED = 'Module settings saved';
const TOOLS_MODULES_PREFS_ERROR = 'Error while saving module settings';

// PATCH
const TOOLS_PATCH = 'Updates';
const TOOLS_PATCH_INSTRUCTION_TAB = 'Instruction';
const TOOLS_PATCH_INSTRUCTION = 'Update instruction';
const TOOLS_PATCH_CHEKING = 'Check for new patch';
const TOOLS_PATCH_MSG_OK = 'All existent patches are installed';
define("TOOLS_PATCH_MSG_NOCONNECTION", "Unable to connect to the update server." . (CMS_SYSTEM_NAME === 'Netcat' ?
        " The presence of the new updates can be found on <a href='https://partners.netcat.ru/forclients/update/' target='_blank'>our site</a>." :
        ''));
const TOOLS_PATCH_ERR_CANTINSTALL = 'Cannot install patch';
const TOOLS_PATCH_INSTALL_LOCAL = 'Patch installation';
const TOOLS_PATCH_INSTALL_ONLINE = 'Online installation';
const TOOLS_PATCH_INFO_NOTINSTALLED = 'Patch is not installed';
const TOOLS_PATCH_INFO_LASTCHECK = 'Last system patch check';
const TOOLS_PATCH_INFO_REFRESH = 'check now';
const TOOLS_PATCH_INFO_DOWNLOAD = 'download';
define("TOOLS_PATCH_ERR_EXTRACT",
    "Error while patch extracting.Please try to extract patch archive to $nc_core->TMP_FOLDER on your server, and run patch install once again.<br />");
const TOOLS_PATCH_ERROR_TMP_FOLDER_NOT_WRITABLE = 'Set writing permissiions for directory %s.<br><small>(%s not available for writing)</small>';
const TOOLS_PATCH_ERROR_FILELIST_NOT_WRITABLE = 'Some files for update not writable.';
const TOOLS_PATCH_ERROR_AUTOINSTALL = "Automatic installation can't be executed, install the update manually.";
define("TOOLS_PATCH_ERROR_UPDATE_SERVER_NOT_AVAILABLE", "Update server not available, try later.<br />" .
    "If the connection to the Internet is only available via a proxy server, " .
    "<a href='{$nc_core->ADMIN_PATH}#system.edit' target='_top'>check its settings</a>.");
const TOOLS_PATCH_ERROR_UPDATE_FILE_NOT_AVAILABLE = 'Update file not available, try later. if the error is repeated, please contact support';
const TOOLS_PATCH_DOWNLOAD_LINK_DESCRIPTION = 'Patch file download link';
const TOOLS_PATCH_IS_WRITABLE = 'Write access';

// patch after install information
const TOOLS_PATCH_INFO_FILES_COPIED = '[%COUNT] files copied.';
const TOOLS_PATCH_INFO_QUERIES_EXEC = '[%COUNT] MySQL queries executed.';
const TOOLS_PATCH_INFO_SYMLINKS_EXEC = '[%COUNT] symlinks created.';

const TOOLS_PATCH_LIST_DATE = 'Installation date';
const TOOLS_PATCH_ERROR = 'Error';
const TOOLS_PATCH_ERROR_DURINGINSTALL = 'Error processed during installation';
const TOOLS_PATCH_INSTALLED = 'Patch installed';
const TOOLS_PATCH_INVALIDVERSION = 'Patch is not for current version. Installed %EXIST, need for patch %REQUIRE.';
const TOOLS_PATCH_ALREDYINSTALLED = 'Patch already installed';

const TOOLS_PATCH_NOTAVAIL_DEMO = 'Patches are not available in the demo version';
const NETCAT_DEMO_NOTICE = 'Content Management System Netcat %s DEMO';
const NETCAT_PERSONAL_MODULE_DESCRIPTION = "The possibility of connecting additional modules exist only in full version.<br />
    To assess the functional which you are missing in a module you can by downloading the version where he presented.<br />
    <a href='https://netcat.ru/products/editions/compare/' target='_blank'>Table</a> compare editions. ";

#UPGRADE
const TOOLS_UPGRADE_ERR_NO_PRODUCTNUMBER = 'No production number in system';
const TOOLS_UPGRADE_ERR_INVALID_PRODUCTNUMBER = 'Production number is invalid. Check your license number again';
const TOOLS_UPGRADE_ERR_NO_MATCH_HOST = 'Activation key is invalid. You may be using unlicensed copy of the system';
const TOOLS_UPGRADE_ERR_NO_ORDER = 'There was no order for this license to upgrade system.';
const TOOLS_UPGRADE_ERR_NOT_PAID = 'The order for system upgrade is not paid on netcat.ru.';

#ACTIVATION
const TOOLS_ACTIVATION = 'Activation system';
const TOOLS_ACTIVATION_INSTRUCTION = 'Activation system instruction';
const TOOLS_ACTIVATION_VERB = 'Activation';
const TOOLS_ACTIVATION_OK = 'Activation was successful';
const TOOLS_ACTIVATION_OK1 = "Activation was successful. Only a few things left to do!<br /><ul style='list-style-type:none'>";
const TOOLS_ACTIVATION_OK2 = "<li>- <a href='https://netcat.ru/' target='_blank'>register</a> an account on netcat.ru</li>";
const TOOLS_ACTIVATION_OK3 = "<li>- <a href='https://netcat.ru/' target='_blank'>login</a> to your account on netcat.ru</li>";
const TOOLS_ACTIVATION_OK4 = "<li>- <a href='https://netcat.ru/forclients/want/zaregistrirovat-litsenziyu/?f_RegNum=%REGNUM&f_code=%REGCODE&f_SystemName=%SYSID' target='_blank'>bind license</a> to your account with this data:
  <ul style='list-style-type:none'><li>License: %REGNUM</li>
  <li>Activation code: %REGCODE</li></ul></li></ul>
  It needs to be done so you will get full access to the updates, important messages and technical support period extension.<br /><br />
  Thank you for choosing Netcat!";
const TOOLS_ACTIVATION_OWNER = 'License owner';
const TOOLS_ACTIVATION_LICENSE = 'License number';
const TOOLS_ACTIVATION_CODE = 'Activation code';
const TOOLS_ACTIVATION_ALREADY_ACTIVE = 'System is activated';
const TOOLS_ACTIVATION_INPUT_KEY_CODE = 'Enter a registration code and an activation key';
const TOOLS_ACTIVATION_INVALID_KEY_CODE = 'Invalid a registration code or an activation key';
const TOOLS_ACTIVATION_DAY = 'The validity of the demo version expires after %DAY days';
define("TOOLS_ACTIVATION_FORM",
    "To activate the system you need to enter your registration code and activation key that you will receive after <a href='https://netcat.ru/products/editions/' target='_blank'>purchase</a>");
const TOOLS_ACTIVATION_DESC = 'In full version:
<ul>
<li> open source;</li>
<li> Unlimited period of licence validity;</li>
<li> To add necessary functionality, you can upgrade your redaction;</li>
<li> Automatic installation of updates;</li>
<li> Annual free online support.</li>
</ul>';
//const TOOLS_ACTIVATION_DEMO_DISABLED = 'Ability to update only exists in full version .<br />';
const TOOLS_ACTIVATION_REMIND_UNCOMPLETED = "Complete activation process &laquo;<a href='%s'>Activation system</a>&raquo;.";
const TOOLS_ACTIVATION_LIC_DATA = '<h3>License requisites</h3>';
const TOOLS_ACTIVATION_LIC_OWNER = '<h3>License owner</h3>';

const TOOLS_ACTIVATION_FORM_ERR_MANDATORY = 'Please fill in all required fields';
const TOOLS_ACTIVATION_FORM_ERR_ORG_EMAIL = 'The format of the email address is incorrect';
const TOOLS_ACTIVATION_FORM_ERR_PERSON_EMAIL = 'The format of the email address is incorrect';
const TOOLS_ACTIVATION_FORM_ERR_PRIMARY_EMAIL = 'The format of the email address is incorrect';
const TOOLS_ACTIVATION_FORM_ERR_ADDIT_EMAIL = 'The format of the additional email address is incorrect';
const TOOLS_ACTIVATION_FORM_ERR_INN = 'ITN should contain 10 or 12 digits';


const TOOLS_ACTIVATION_PLEASE_CHECK = "<p style='color: #ce655d;'>Attention! License must be registered with end-user data.<br />According to the License agreement, license owner data couldn't be changed later.</p>";
const TOOLS_ACTIVATION_FLD_OWNER = 'License owner';
const TOOLS_ACTIVATION_FLD_PHIS = 'Individual';
const TOOLS_ACTIVATION_FLD_UR = 'Legal person';
const TOOLS_ACTIVATION_FLD_NAME = 'Full name';
const TOOLS_ACTIVATION_FLD_PHIS_PHONE = 'Phone';
const TOOLS_ACTIVATION_FLD_PRIMARY_EMAIL = 'Email';
const TOOLS_ACTIVATION_FLD_ADDIT_EMAIL = 'Additional email';
const TOOLS_ACTIVATION_FLD_ORGANIZATION = 'Organization';
const TOOLS_ACTIVATION_FLD_INN = 'INN';
const TOOLS_ACTIVATION_FLD_ORG_EMAIL = 'Email';
const TOOLS_ACTIVATION_FLD_PHONE = 'Phone';
const TOOLS_ACTIVATION_FLD_DOMAINS = 'Домены лицензии (включая тестовый, через запятую)';

const REPORTS = 'General statistics';
const REPORTS_SECTIONS = '%d section(s) (turned off: %d)';
const REPORTS_USERS = '%d user(s) (turned off: %d)';
const REPORTS_LAST_NAME = 'Name';
const REPORTS_CLASS = 'Component statistics';
const REPORTS_STAT_CLASS_SHOW = 'Show components';
const REPORTS_STAT_CLASS_ALL = 'All';
const REPORTS_STAT_CLASS_DOGET = 'Perform selection';
const REPORTS_STAT_CLASS_CLEAR = 'Clear';
const REPORTS_STAT_CLASS_CLEARED = 'Object deleted';
const REPORTS_STAT_CLASS_CONFIRM = 'todo';
const REPORTS_STAT_CLASS_CONFIRM_OK = 'Next';
const REPORTS_STAT_CLASS_NOT_CC = 'todo';
const REPORTS_STAT_CLASS_USE = 'Used';
const REPORTS_STAT_CLASS_NOTUSE = 'Unused';

const REPORTS_SYSMSG_MSG = 'Message';
const REPORTS_SYSMSG_DATE = 'Date';
const REPORTS_SYSMSG_NONE = 'No system messages. ';
const REPORTS_SYSMSG_MARK = 'Mark as read';
const REPORTS_SYSMSG_TOTAL = 'Total';
const REPORTS_SYSMSG_BACK = 'Back to the list';

const SUPPORT = 'Contact developer';
const SUPPORT_HELP_MESSAGE = "
Technical support is available only to registered Netcat users.<br />
To get help from the system developer you should:
<ol>
 <li style='padding-bottom:10px'><a target=_blank href='https://netcat.ru/forclients/copies/'>Register your Netcat copy</a>.
 <li style='padding-bottom:10px'>Since data you've entered had been verified, you'll be able to
   open and track your questions in our <a target=_blank href='https://netcat.ru/forclients/helpdesk/'>helpdesk</a>.
 </li>
</ol>
";

const TOOLS_SQL = 'SQL command line';
const TOOLS_SQL_ERR_NOQUERY = 'Enter a query!';
const TOOLS_SQL_SEND = 'Query';
const TOOLS_SQL_OK = 'Query result';
const TOOLS_SQL_TOTROWS = 'Rows of query correspond';
const TOOLS_SQL_HELP = 'Query example';
const TOOLS_SQL_HISTORY = 'Last 15 queries';
const TOOLS_SQL_HELP_EXPLAIN = 'showing field list of table %s';
const TOOLS_SQL_HELP_SELECT = 'showing a row number in table %s';
const TOOLS_SQL_HELP_SHOW = 'showing a table list';
const TOOLS_SQL_HELP_DOCS = "For more information check MySQL documentation at URL:<br>\n<a target=_blank href=http://dev.mysql.com/doc/mysql/en/>http://dev.mysql.com/doc/mysql/en/</a>";
const TOOLS_SQL_BENCHMARK = 'Query execution time';
const TOOLS_SQL_ERR_OPEN_FILE = "Can't open sql-file: %s";
const TOOLS_SQL_ERR_FILE_QUERY = 'MySQL query error into the file %s, error: %s';

const NETCAT_TRASH_SIZEINFO = 'Size of trash bin - %s. <br />Limit - %s РњР‘.';
const NETCAT_TRASH_NOMESSAGES = 'Trash bin is empty';
const NETCAT_TRASH_MESSAGES_SK1 = 'object';
const NETCAT_TRASH_MESSAGES_SK2 = 'objects';
const NETCAT_TRASH_MESSAGES_SK3 = 'objects';
const NETCAT_TRASH_RECOVERED_SK1 = 'Recovered';
const NETCAT_TRASH_RECOVERED_SK2 = 'Recovered';
const NETCAT_TRASH_RECOVERED_SK3 = 'Recovered';
const NETCAT_TRASH_RECOVERY = 'Recovery';
const NETCAT_TRASH_DELETE_FROM_TRASH = 'Delete from trash';
const NETCAT_TRASH_OBJECT_WERE_DELETED_TRASHBIN_FULL = 'rash bin is fullT';
const NETCAT_TRASH_OBJECT_IN_TRASHBIN_AND_CANCEL = "Object is moved in <a href='%s'>trash</a>. <a href='%s'>Cancel</a>";
const NETCAT_TRASH_TRASHBIN_DISABLED = 'Trash bin is turned off';
const NETCAT_TRASH_EDIT_SETTINGS = 'Edit settings';
const NETCAT_TRASH_OBJECT_NOT_FOUND = 'Object not found';
const NETCAT_TRASH_TRASHBIN = 'Trash bin';
const NETCAT_TRASH_PRERECOVERYSUB_INFO = 'Some of the recovered objects are in the section, which is now gone.';
const NETCAT_TRASH_PRERECOVERYSUB_CHECKED = 'checked';
const NETCAT_TRASH_PRERECOVERYSUB_NAME = 'Name';
const NETCAT_TRASH_PRERECOVERYSUB_KEYWORD = 'Keyword';
const NETCAT_TRASH_PRERECOVERYSUB_PARENT = 'Parent';
const NETCAT_TRASH_PRERECOVERYSUB_ROOT = 'Root';
const NETCAT_TRASH_PRERECOVERYSUB_NEXT = 'Next';
const NETCAT_TRASH_FILTER = 'Filter';
const NETCAT_TRASH_FILTER_DATE_FROM = 'From';
const NETCAT_TRASH_FILTER_DATE_TO = 'To';
const NETCAT_TRASH_FILTER_DATE_FORMAT = 'dd-mm-yyyy h:i';
const NETCAT_TRASH_FILTER_SUBDIVISION = 'Section';
const NETCAT_TRASH_FILTER_COMPONENT = 'Component';
const NETCAT_TRASH_FILTER_ALL = 'all';
const NETCAT_TRASH_FILTER_APPLY = 'apply';
const NETCAT_TRASH_FILE_DOEST_EXIST = 'File %s not found';
const NETCAT_TRASH_FOLDER_FAIL = "Folder %s doesn't exists";
const NETCAT_TRASH_ERROR_RELOAD_PAGE = "<a href='index.php'>Reload page</a>";
const NETCAT_TRASH_TRASHBIN_IS_FULL = 'Trash bin is full';
const NETCAT_TRASH_TEMPLATE_DOESNT_EXIST = "Template for trash bin doest't exist";
const NETCAT_TRASH_IDENTIFICATOR = 'ID';
const NETCAT_TRASH_USER_IDENTIFICATOR = 'User ID';

// USERS
const CONTROL_USER_GROUPS = 'User groups';
const CONTROL_USER_FUNCS_ALLUSERS = 'all';
const CONTROL_USER_FUNCS_ONUSERS = 'turned on';
const CONTROL_USER_FUNCS_OFFUSERS = 'turned off';
const CONTROL_USER_FUNCS_DOGET = 'Perform selection';
const CONTROL_USER_FUNCS_VIEWCONTROL = 'View';
const CONTROL_USER_FUNCS_SORTBY = 'Sort by';
const CONTROL_USER_FUNCS_USER_NUMBER_ON_THE_PAGE = 'users on page.';
const CONTROL_USER_FUNCS_SORT_ORDER = 'Sort order';
const CONTROL_USER_FUNCS_SORT_ORDER_ACS = 'Acs';
const CONTROL_USER_FUNCS_SORT_ORDER_DESC = 'Desc';
const CONTROL_USER_FUNCS_PREV_PAGE = 'previous page';
const CONTROL_USER_FUNCS_NEXT_PAGE = 'next page';
const CONTROL_USER_FUNC_CONFIRM_DEL = 'Confirm deleting user';
const CONTROL_USER_FUNC_CONFIRM_DEL_OK = 'Confirm';
const CONTROL_USER_FUNC_CONFIRM_DEL_NOT_USER = 'Not users';
const CONTROL_USER_FUNC_GROUP_ERROR = 'Group is not selected';
const CONTROL_USER = 'User';
const CONTROL_USER_FUNCS_EDITACCESSRIGHT = 'Change user rights';
const CONTROL_USER_ACTIONS = 'Actions';
const CONTROL_USER_RIGHTS = 'Permissions';
const CONTROL_USER_ERROR_NEWPASS_IS_CURRENT = 'New password such as a current!';
const CONTROL_USER_CHANGEPASS = 'change password';
const CONTROL_USER_EDIT = 'edit';
const CONTROL_USER_REG = 'New user';
const CONTROL_USER_NEWPASSWORD = 'New password';
const CONTROL_USER_NEWPASSWORDAGAIN = 'New password again';
const CONTROL_USER_MSG_USERNOTFOUND = 'No users found.';
const CONTROL_USER_GROUP = 'Group';
const CONTROL_USER_GROUP_MEMBERS = 'Members';
const CONTROL_USER_GROUP_NOMEMBERS = 'No members';
const CONTROL_USER_GROUP_TOTAL = 'total';
const CONTROL_USER_GROUPNAME = 'Group name';
const CONTROL_USER_ERROR_GROUPNAME_IS_EMPTY = "Group name can't be empty!";
const CONTROL_USER_ADDNEWGROUP = 'Add group';
const CONTROL_USER_CHANGERIGHTS = 'User access rights';
const CONTROL_USER_NEW_ADDED = 'User was added';
const CONTROL_USER_NEW_NOTADDED = 'User was not added';
const CONTROL_USER_EDITSUCCESS = 'User is edited success';
const CONTROL_USER_REGISTER = 'Register new user';
const CONTROL_USER_REGISTER_ERROR_NO_LOGIN_FIELD_VALUE = 'No value for login field is given';
const CONTROL_USER_REGISTER_ERROR_LOGIN_ALREADY_EXIST = 'This login is already taken';
const CONTROL_USER_REGISTER_ERROR_LOGIN_INCORRECT = 'This login contains invalid characters';
const CONTROL_USER_GROUPS_ADD = 'Add group';
const CONTROL_USER_GROUPS_EDIT = 'Edit group';
const CONTROL_USER_ACESSRIGHTS = 'access rights';
const CONTROL_USER_USERSANDRIGHTS = 'Users and permissions';
const CONTROL_USER_PASSCHANGE = 'Change password';
const CONTROL_USER_CATALOGUESWITCH = 'Site selection';
const CONTROL_USER_SECTIONSWITCH = 'Section selection';
const CONTROL_USER_TITLE_USERINFOEDIT = 'Edit user information';
const CONTROL_USER_TITLE_PASSWORDCHANGE = 'Change a user password';
const CONTROL_USER_ERROR_EMPTYPASS = 'Password cannot be empty!';
const CONTROL_USER_ERROR_NOTCANGEPASS = 'Password is not changed. Error!';
const CONTROL_USER_OK_CHANGEDPASS = 'Password is successfully changed.';
const CONTROL_USER_ERROR_RETRY = 'Try again!';
const CONTROL_USER_ERROR_PASSDIFF = 'Entered passwords did not match!';
const CONTROL_USER_MAIL = 'Mailing list';
const CONTROL_USER_MAIL_TITLE_COMPOSE = 'Send a email';
const CONTROL_USER_MAIL_GROUP = 'User group';
const CONTROL_USER_MAIL_ALLGROUPS = 'All groups';
const CONTROL_USER_MAIL_FROM = 'Sender';
const CONTROL_USER_MAIL_BODY = 'Mail body';
const CONTROL_USER_MAIL_ADDATTACHMENT = 'attach a file';
const CONTROL_USER_MAIL_SEND = 'Send';
const CONTROL_USER_MAIL_ERROR_EMAILFIELD = 'Email field is not set.';
const CONTROL_USER_MAIL_OK = 'Message has been sent to all users';
const CONTROL_USER_MAIL_ERROR_NOONEEMAIL = 'You forgot to specify email.';
const CONTROL_USER_MAIL_ATTCHAMENT = 'Attach a file';
define("CONTROL_USER_MAIL_ERROR_ONE", "Cannot mail: in <a href=" . $ADMIN_PATH .
    "settings.php?phase=1>system settings</a> is not set a user email field.");
define("CONTROL_USER_MAIL_ERROR_TWO",
    "Cannot mail: in <a href=" . $ADMIN_PATH . "settings.php?phase=1>system settings</a> is  not set a From name.");
define("CONTROL_USER_MAIL_ERROR_THREE",
    "Cannot mail: in <a href=" . $ADMIN_PATH . "settings.php?phase=1>system settings</a> is  not set a From email.");
const CONTROL_USER_MAIL_ERROR_NOBODY = 'You forgot to specify email massage.';
const CONTROL_USER_MAIL_CHANGE = 'change';
const CONTROL_USER_MAIL_CONTENT = 'Mail content';
const CONTROL_USER_MAIL_SUBJECT = 'Mail subject';
const CONTROL_USER_MAIL_RULES = 'Conditions of dispatch';
const CONTROL_USER_FUNCS_USERSGET = 'Selection of users';
const CONTROL_USER_FUNCS_USERSGET_EXT = 'Extended search';
const CONTROL_USER_FUNCS_SEARCHEDUSER = 'Found users';
const CONTROL_USER_FUNCS_USERCOUNT = 'Total Users';
const CONTROL_USER_FUNCS_ADDUSER = 'Add new user';
const CONTROL_USER_FUNCS_NORIGHTS = 'The user has no rights.';
const CONTROL_USER_FUNCS_GROUP_NORIGHTS = 'The group has no rights.';
const CONTROL_USER_RIGHTS_QUOTES = '‘%s’';
const CONTROL_USER_RIGHTS_ITEM = 'Item';
const CONTROL_USER_RIGHTS_SELECT_ITEM = 'Select item';
const CONTROL_USER_RIGHTS_TYPE_OF_RIGHT = 'User Roles';
const CONTROL_USER_RIGHTS_GUESTONE = 'Guest';
const CONTROL_USER_RIGHTS_DIRECTOR = 'Director';
const CONTROL_USER_RIGHTS_SUPERVISOR = 'Supervisor';
const CONTROL_USER_RIGHTS_SITEADMIN = 'Access to Site';
const CONTROL_USER_RIGHTS_CATALOGUEADMINALL = 'All site editor';
const CONTROL_USER_RIGHTS_CONTENTEDITOR = 'Content editor on';
const CONTROL_USER_RIGHTS_ALLSITESCONTENTEDITOR = 'All sites content editor';
const CONTROL_USER_RIGHTS_SUBDIVISIONADMIN = 'Access to Section';
const CONTROL_USER_RIGHTS_CONTENTADMIN = 'Access to Content';
const CONTROL_USER_RIGHTS_SUBCLASSADMIN = 'Access to Component';
const CONTROL_USER_RIGHTS_SUBCLASSADMINS = 'Section component editor';
const CONTROL_USER_RIGHTS_CLASSIFICATORADMIN = 'Classificator administrator';
const CONTROL_USER_RIGHTS_CLASSIFICATORADMINALL = 'All classificators administrator';
const CONTROL_USER_RIGHTS_MODULE = 'Access to modules';
const CONTROL_USER_RIGHTS_MODULE_ALL = 'full access';
const CONTROL_USER_MODULE = 'Module';
const CONTROL_USER_MODULE_ALL = 'All modules';
const CONTROL_USER_MODULE_SITE = 'on the ‘%s’ site';
const CONTROL_USER_MODULE_SITE_ANY = 'on all sites';
const CONTROL_USER_RIGHTS_EDITOR = 'Editor';
const CONTROL_USER_RIGHTS_SUBSCRIBER = 'Subscriber';
const CONTROL_USER_RIGHTS_SUBSCRIBER_OF = 'of the mailing list';
const CONTROL_USER_RIGHTS_MODERATOR = 'User administrator';
const CONTROL_USER_RIGHTS_USER_GROUP = 'Group administrator';
const CONTROL_USER_RIGHTS_BAN = 'Ban';
const CONTROL_USER_RIGHTS_SITE = 'Ban on site';
const CONTROL_USER_RIGHTS_SITEALL = 'Ban on all sites';
const CONTROL_USER_RIGHTS_SUB = 'Ban on section';
const CONTROL_USER_RIGHTS_CC = 'Ban on section component';
const CONTROL_USER_RIGHTS_LOAD = 'Loading';
const CONTROL_USER_RIGHT_ADDNEWRIGHTS = 'Add Permissions';
const CONTROL_USER_RIGHT_ADDPERM = 'Add new permission to user';
const CONTROL_USER_RIGHT_ADDPERM_GROUP = 'Add new permission to group';
const CONTROL_USER_FUNCS_FROMCAT = 'from site';
const CONTROL_USER_FUNCS_FROMSEC = 'from section';
const CONTROL_USER_FUNCS_ADDNEWRIGHTS = 'Add Permissions';
const CONTROL_USER_FUNCS_ERR_CANTREMGROUP = 'Cannot delete group %s. Error!';
const CONTROL_USER_SELECTSITE = 'Choose a site';
const CONTROL_USER_SELECTSECTION = 'Choose section';
const CONTROL_USER_NOONESECSINSITE = 'Site has no sections.';
const CONTROL_USER_FUNCS_SITE = 'Site';
const CONTROL_USER_FUNCS_CLASSINSECTION = 'Infoblock';
const CONTROL_USER_RIGHTS_UPDATED_OK = 'User rights updated.';
const CONTROL_USER_RIGHTS_ERROR_NOSELECTED = 'Item not selected';
const CONTROL_USER_RIGHTS_ERROR_DATA = 'Error in date';
const CONTROL_USER_RIGHTS_ERROR_DB = 'Error';
const CONTROL_USER_RIGHTS_ERROR_POSSIBILITY = 'Not selected opportunity';
const CONTROL_USER_RIGHTS_ERROR_NOTSITE = 'Select site';
const CONTROL_USER_RIGHTS_ERROR_NOTSUB = 'Select subdivision';
const CONTROL_USER_RIGHTS_ERROR_NOTCCINSUB = 'This section has no components';
const CONTROL_USER_RIGHTS_ERROR_NOTTYPEOFRIGHT = 'Select type of right';
const CONTROL_USER_RIGHTS_ERROR_START = 'Error in start date of permission';
const CONTROL_USER_RIGHTS_ERROR_END = 'An error in the date of expiry of rights';
const CONTROL_USER_RIGHTS_ERROR_STARTEND = 'End time of the rights can not be earlier than the beginning';
const CONTROL_USER_RIGHTS_ERROR_GUEST = 'Error';
const CONTROL_USER_RIGHTS_ERROR_NONE_AVAILABLE = 'User rights weren’t saved because you don’t have them';
const CONTROL_USER_RIGHTS_ERROR_SOME_AVAILABLE = 'Some permissions weren’t saved: permissions that you don’t have can’t be assigned to (or removed from) other user';
const CONTROL_USER_RIGHTS_ERROR_EXISTED = 'Permissions weren’t saved because these (or wider) permissions already were assigned';
const CONTROL_USER_RIGHTS_ADDED = 'User rights was added';
const CONTROL_USER_RIGHTS_LIVETIME = 'Lifetime';
const CONTROL_USER_RIGHTS_UNLIMITED = 'unlimited';
const CONTROL_USER_RIGHTS_NONLIMITED = 'unlimited';
const CONTROL_USER_RIGHTS_LIMITED = 'limited';
const CONTROL_USER_RIGHTS_STARTING_OPERATIONS = 'Activate permissions';
const CONTROL_USER_RIGHTS_FINISHING_OPERATIONS = 'Deactivate permissions';
const CONTROL_USER_RIGHTS_NOW = 'now';
const CONTROL_USER_RIGHTS_ACROSS = 'in';
const CONTROL_USER_RIGHTS_ACROSS_MINUTES = 'minutes';
const CONTROL_USER_RIGHTS_ACROSS_HOURS = 'hours';
const CONTROL_USER_RIGHTS_ACROSS_DAYS = 'days';
const CONTROL_USER_RIGHTS_ACROSS_MONTHS = 'months';
const CONTROL_USER_RIGHTS_RIGHT = 'Permissions';
const CONTROL_USER_RIGHTS_CONTROL_ADD = 'add';
const CONTROL_USER_RIGHTS_CONTROL_EDIT = 'edit users without permissions';
const CONTROL_USER_RIGHTS_CONTROL_MODERATE = 'moderate users with same permissions that this user has';
const CONTROL_USER_RIGHTS_CONTROL_DELETE = 'delete';
const CONTROL_USER_RIGHTS_CONTROL_ADMIN = 'administrate (change permissions)';
const CONTROL_USER_RIGHTS_CONTROL_HELP = 'Help';
const CONTROL_USER_USERS_MOVED_SUCCESSFULLY = 'Users moved successfully';
const CONTROL_USER_SELECT_GROUP_TO_MOVE = 'Select groups';
const CONTROL_USER_SELECTSITEALL = 'All sites';

# SUPPORT USER
const CONTROL_USER_SUPPORT_TITLE = 'Grant access to support';
const CONTROL_USER_SUPPORT_SUBMIT = 'Grant access';
const CONTROL_USER_SUPPORT_GRANTED = 'Access granted. Please inform the support stuff.';
const CONTROL_USER_SUPPORT_WARNING = 'Grant access to support only if they asked for it in reply to your request.';
const CONTROL_USER_SUPPORT_PERPETUAL = 'Support has full perpetual access to your site.';
const CONTROL_USER_SUPPORT_EXPIRES = 'Support has full access to your site until %s.';
const CONTROL_USER_SUPPORT_MANAGE = 'Manage support access rights';
const CONTROL_USER_SUPPORT_GRANT_FOR = 'Grant access for';
const CONTROL_USER_SUPPORT_DAYS = 'day/days/days';
const CONTROL_USER_SUPPORT_ERROR_CREATING = 'Error occurred while creating support user.';
const CONTROL_USER_SUPPORT_NO_SSO = 'Automatic support user creation is not available because single sign-on is disabled on your site.';
const CONTROL_USER_SUPPORT_CREATE_MANUALLY = 'Please <a href="%s#user.add" target="_top">create a new user</a> and grant them supervisor permissions manually.';

# TEMPLATE
const CONTROL_TEMPLATE = 'Design templates';
const CONTROL_TEMPLATE_ADD = 'Add new template';
const CONTROL_TEMPLATE_EDIT = 'Template edit';
const CONTROL_TEMPLATE_DELETE = 'Template delete';
const CONTROL_TEMPLATE_OPT_ADD = 'add options';
const CONTROL_TEMPLATE_OPT_EDIT = 'edit options';
const CONTROL_TEMPLATE_ERR_NAME = 'Specify the name of a design template name.';
define("CONTROL_TEMPLATE_INFO_CONVERT", "Remember to <a href='#' onclick=\"window.open('" . $ADMIN_PATH .
    "template/converter.php', 'converter','width=600,height=410,status=no,resizable=yes');\">add slashes</a>.");
const CONTROL_TEMPLATE_TEPL_NAME = 'Name';
const CONTROL_TEMPLATE_TEPL_MENU = 'Navigation templates';
const CONTROL_TEMPLATE_TEPL_HEADER = 'Header';
const CONTROL_TEMPLATE_TEPL_FOOTER = 'Footer';
const CONTROL_TEMPLATE_TEPL_CREATE = 'Add template design';
const CONTROL_TEMPLATE_NOT_FOUND = 'Design template %s not found!';
const CONTROL_TEMPLATE_ERR_USED_IN_SITE = 'This template is used in following sites:';
const CONTROL_TEMPLATE_ERR_USED_IN_SUB = 'This template is used in following sections:';
const CONTROL_TEMPLATE_ERR_CANTDEL = 'Cannot delete design template';
const CONTROL_TEMPLATE_INFO_DELETE = 'You want deleting template';
const CONTROL_TEMPLATE_INFO_DELETE_SOME = 'These templates will be deleted';
const CONTROL_TEMPLATE_DELETED = 'Template was deleted';
const CONTROL_TEMPLATE_ADDLINK = 'New template';
const CONTROL_TEMPLATE_REMOVETHIS = 'Delete this template';
const CONTROL_TEMPLATE_PREF_EDIT = 'edit setting';
const CONTROL_TEMPLATE_NONE = 'No templates found';
const CONTROL_TEMPLATE_TEPL_IMPORT = 'Import template';
const CONTROL_TEMPLATE_IMPORT = 'Import template';
const CONTROL_TEMPLATE_IMPORT_UPLOAD = 'Upload';
const CONTROL_TEMPLATE_IMPORT_SELECT = 'Choose a template for import (all child templates are included)';
const CONTROL_TEMPLATE_IMPORT_CONTINUE = 'Continue';
const CONTROL_TEMPLATE_IMPORT_ERROR_NOTUPLOADED = 'Error importing template';
const CONTROL_TEMPLATE_IMPORT_ERROR_SQL = 'Error inserting SQL';
const CONTROL_TEMPLATE_IMPORT_ERROR_EXTRACT = 'Error extracting file %s to %s';
const CONTROL_TEMPLATE_IMPORT_ERROR_MOVE = 'Error copying files from %s to %s';
const CONTROL_TEMPLATE_IMPORT_SUCCESS = 'Template import succeeded';
const CONTROL_TEMPLATE_EXPORT = 'Export template to file';
const CONTROL_TEMPLATE_FILES_PATH = "Files dir <a href='%s'>%s</a>";
const CONTROL_TEMPLATE_PARTIALS = 'Partials';
const CONTROL_TEMPLATE_PARTIALS_NEW = 'New partial';
const CONTROL_TEMPLATE_PARTIALS_ADD = 'Add partial';
const CONTROL_TEMPLATE_PARTIALS_REMOVE = 'Remove partial';
const CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD = 'Keyword (english)';
const CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD_ERROR = 'Keyword can contain only latin characters';
const CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD_REQUIRED_ERROR = 'Keyword is required';
const CONTROL_TEMPLATE_PARTIALS_DESCRIPTION_FIELD = 'Name';
const CONTROL_TEMPLATE_PARTIALS_ENABLE_ASYNC_LOAD_FIELD = 'enable asynchronous load';
const CONTROL_TEMPLATE_PARTIALS_SOURCE_FIELD = 'Partial source';
const CONTROL_TEMPLATE_PARTIALS_EXISTS_ERROR = 'Partial already exists';
const CONTROL_TEMPLATE_PARTIALS_NOT_EXISTS = 'Template (partial) not exists';
const CONTROL_TEMPLATE_BASE_TEMPLATE = 'Create template from base template';
const CONTROL_TEMPLATE_BASE_TEMPLATE_CREATE_FROM_SCRATCH = 'Create template from scratch';
const CONTROL_TEMPLATE_CONTINUE = 'Continue';

const CONTROL_TEMPLATE_KEYWORD = 'Keyword';
const CONTROL_TEMPLATE_KEYWORD_ONLY_DIGITS = 'Keyword cannot consist of digits only';
const CONTROL_TEMPLATE_KEYWORD_TOO_LONG = 'Keyword cannot be longer than %d characters';
const CONTROL_TEMPLATE_KEYWORD_INVALID_CHARACTERS = 'Keyword can contain only latin letters, digits and underscores';
const CONTROL_TEMPLATE_KEYWORD_NON_UNIQUE = 'Keyword &ldquo;%s&rdquo; is already assigned to the &ldquo;%d. %s&rdquo; template';
const CONTROL_TEMPLATE_KEYWORD_RESERVED = 'Cannot use &ldquo;%s&rdquo; as a keyword, because it is a reserved word';
const CONTROL_TEMPLATE_KEYWORD_PATH_EXISTS = 'Cannot use &ldquo;%s&rdquo; as a keyword, because a folder with this name already exists';

// CLASSIFICATORS
const CONTENT_CLASSIFICATORS = 'Classificators';
const CONTENT_CLASSIFICATORS_NAMEONE = 'Classificator';
const CONTENT_CLASSIFICATORS_NAMEALL = 'All classificators';
const CONTENT_CLASSIFICATORS_ELEMENTS = 'elements';
const CONTENT_CLASSIFICATORS_ELEMENT = 'Element';
const CONTENT_CLASSIFICATORS_ELEMENT_NAME = 'Element name';
const CONTENT_CLASSIFICATORS_ELEMENT_VALUE = 'Added value';
const CONTENT_CLASSIFICATORS_ELEMENTS_ADDONE = 'Add element';
const CONTENT_CLASSIFICATORS_ELEMENTS_ADD = 'Add element';
const CONTENT_CLASSIFICATORS_ELEMENTS_ADD_SUCCESS = 'Element successfully added';
const CONTENT_CLASSIFICATORS_ELEMENTS_EDIT = 'Edit element';
const CONTENT_CLASSIFICATORS_LIST_ADD = 'Add classificator';
const CONTENT_CLASSIFICATORS_LIST_EDIT = 'Edit classificator';
const CONTENT_CLASSIFICATORS_LIST_CUSTOM = 'Custom settings';
const CONTENT_CLASSIFICATORS_LIST_DELETE = 'Delete classificator';
const CONTENT_CLASSIFICATORS_LIST_DELETE_SELECTED = 'Delete selected';
const CONTENT_CLASSIFICATORS_ERR_NONE = 'Project has no classificators.';
const CONTENT_CLASSIFICATORS_ERR_ELEMENTNONE = 'Classificator has no elements.';
const CONTENT_CLASSIFICATORS_ERR_SYSDEL = "You can't remove a element from system classificator";
const CONTENT_CLASSIFICATORS_ERR_EDITI_GUESTRIGHTS = 'You have no permission to edit element in classificator!';
const CONTENT_CLASSIFICATORS_ERROR_NAME = 'Enter classificator name!';
const CONTENT_CLASSIFICATORS_ERROR_FILE_NAME = 'Select CVS-file';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORD = 'Enter classificator keyword name!';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORDINV = 'Classificator keyword name can contain only latin characters';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORDFL = 'Classificator keyword name can contain only latin characters!';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORDAE = 'Classificator with such keyword name already exists!';
const CONTENT_CLASSIFICATORS_ERROR_KEYWORDREZ = 'This word is reserved!';
const CONTENT_CLASSIFICATORS_ADDLIST = 'New classificator';
const CONTENT_CLASSIFICATORS_ADD_KEYWORD = 'Table name';
const CONTENT_CLASSIFICATORS_SAVE = 'Save changes';
const CONTENT_CLASSIFICATORS_NO_NAME = '(no name)';
const CONTENT_CLASSIFICATORS_CUSTOM_BY_DEFAULT = 'by default:';
const CLASSIFICATORS_SORT_HEADER = 'Sorting pattern';
const CLASSIFICATORS_SORT_PRIORITY_HEADER = 'Priority';
const CLASSIFICATORS_SORT_TYPE_ID = 'ID';
const CLASSIFICATORS_SORT_TYPE_NAME = 'Element';
const CLASSIFICATORS_SORT_TYPE_PRIORITY = 'Priority';
const CLASSIFICATORS_SORT_DIRECTION = 'Sorting direction';
const CLASSIFICATORS_SORT_ASCENDING = 'ASC';
const CLASSIFICATORS_SORT_DESCENDING = 'DESC';
const CLASSIFICATORS_IMPORT_HEADER = 'Import';
const CLASSIFICATORS_IMPORT_BUTTON = 'Import';
const CLASSIFICATORS_IMPORT_FILE = 'CSV-file (*)';
const CLASSIFICATORS_IMPORT_DESCRIPTION = 'If imported file has only one column, this column will be considered as Element field, if it has two columns - first will be considered as Element and another as Priority field.';
const CLASSIFICATORS_SUCCESS_DELETEONE = 'Classificator successfully deleted.';
const CLASSIFICATORS_SUCCESS_DELETE = 'Classificators successfully deleted.';
const CLASSIFICATORS_SUCCESS_ADD = 'Classificator successfully added.';
const CLASSIFICATORS_SUCCESS_EDIT = 'Classificator successfully changed.';
const CLASSIFICATORS_ERROR_DELETEONE_SYS = 'Classificator %s is system, deletion forbidden.';
const CLASSIFICATORS_ERROR_ADD = 'Cannot add classificator.';
const CLASSIFICATORS_ERROR_EDIT = 'Cannot edit classificator.';

// TOOLS HTML
const TOOLS_HTML = 'HTML-editor';
const TOOLS_HTML_INFO = 'Edit in WYSIWYG HTML-editor';

const TOOLS_DUMP = 'Project backups';
const TOOLS_DUMP_CREATE = 'Create backup';
const TOOLS_DUMP_CREATED = 'Backup created: %FILE.';
const TOOLS_DUMP_CREATION_FAILED = 'Backup creation failed.';
const TOOLS_DUMP_DELETED = 'File %FILE deleted.';
const TOOLS_DUMP_RESTORE = 'Restore backup';
const TOOLS_DUMP_MSG_RESTORED = 'Backup restored.';
const TOOLS_DUMP_INC_TITLE = 'Restore backup from local drive';
const TOOLS_DUMP_INC_DORESTORE = 'Restore';
const TOOLS_DUMP_INC_DBDUMP = 'database dump';
const TOOLS_DUMP_INC_FOLDER = 'folder content';
const TOOLS_DUMP_ERROR_CANTDELETE = 'Error! Cannot delete %FILE.';
const TOOLS_DUMP_INC_ARCHIVE = 'Archive name';
const TOOLS_DUMP_INC_DATE = 'Date';
const TOOLS_DUMP_INC_SIZE = 'Size';
const TOOLS_DUMP_INC_DOWNLOAD = 'download';
const TOOLS_DUMP_NOONE = 'No backups yet.';
const TOOLS_DUMP_DATE = 'Backup date';
const TOOLS_DUMP_SIZE = 'Size';
const TOOLS_DUMP_CREATEAP = 'Create project archive';
const TOOLS_DUMP_CONFIRM = 'Confirm project archive creation';
const TOOLS_DUMP_BACKUPLIST_HEADER = 'Available backups';
const TOOLS_DUMP_CREATE_HEADER = 'Create new archive';
const TOOLS_DUMP_CREATE_OPT_FULL = 'Complete archive (includes all files, database dump and restore script)';
const TOOLS_DUMP_CREATE_OPT_DATA = 'Project data (images, netcat_templates, modules, netcat_files directories and database dump';
const TOOLS_DUMP_CREATE_OPT_SQL = 'Database only';
const TOOLS_DUMP_CREATE_SUBMIT = 'Create new backup';
const TOOLS_DUMP_REMOVE_SELECTED = 'Remove selected backups';
const TOOLS_DUMP_CREATE_WAIT = 'Creating the backup archive. Please wait.';
const TOOLS_DUMP_RESTORE_WAIT = 'Restoring data from the backup archive. Please wait.';
const TOOLS_DUMP_CONNECTION_LOST = 'Lost connection to the server. If the requested action was not completed, %s.';
const TOOLS_DUMP_CONNECTION_LOST_SYSTEM_TAR = 'try enabling system tar utility execution from PHP';
const TOOLS_DUMP_CONNECTION_LOST_INCREASE_PHP_LIMITS = 'check PHP error log, and try to increase PHP memory limit, web server timeouts and server resource limits';
const TOOLS_DUMP_CONNECTION_LOST_INCREASE_SERVER_LIMITS = 'try to increase web server timeouts and server resource limits';
const TOOLS_DUMP_CONNECTION_LOST_GO_BACK = 'Back';

const TOOLS_REDIRECT = 'Redirect';
const TOOLS_REDIRECT_OLDURL = 'Old URL';
const TOOLS_REDIRECT_NEWURL = 'New URL';
const TOOLS_REDIRECT_OLDLINK = 'Old URL';
const TOOLS_REDIRECT_NEWLINK = 'New URL';
const TOOLS_REDIRECT_HEADER = 'Header';
const TOOLS_REDIRECT_HEADERSEND = 'Header';
const TOOLS_REDIRECT_SETTINGS = 'Settings';
const TOOLS_REDIRECT_CHANGEINFO = 'Change';
const TOOLS_REDIRECT_NONE = 'No redirects.';
const TOOLS_REDIRECT_ADD = 'New redirect';
const TOOLS_REDIRECT_EDIT = 'Edit redirect';
const TOOLS_REDIRECT_ADDONLY = 'Add redirect';
const TOOLS_REDIRECT_CANTBEEMPTY = "This fields can't be empty!";
define("TOOLS_REDIRECT_OLDURL_MUST_BE_UNIQUE", "Same redirect Old URL exist - <a href='" . nc_core('NETCAT_FOLDER') .
    "action.php?ctrl=admin.redirect&action=edit&id=%s'>go to</a>");
const TOOLS_REDIRECT_DISABLED = 'In the configuration file tools "Forward" disabled. <br /> To include it, edit the file vars.inc.php value of $NC_REDIRECT_DISABLED to 0.';
const TOOLS_REDIRECT_GROUP = 'Group';
const TOOLS_REDIRECT_GROUP_NAME = 'Group name';
const TOOLS_REDIRECT_GROUP_ADD = 'Add group';
const TOOLS_REDIRECT_GROUP_EDIT = 'Edit group';
const TOOLS_REDIRECT_GROUP_DELETE = 'Delete group';
const TOOLS_REDIRECT_BACK = 'Back';
const TOOLS_REDIRECT_SAVE_OK = 'Redirect saved';
const TOOLS_REDIRECT_GROUP_SAVE_OK = 'Group saved';
const TOOLS_REDIRECT_SAVE_ERROR = "Can't save";
const TOOLS_REDIRECT_DELETE = 'Delete';
const TOOLS_REDIRECT_DELETE_CONFIRM_REDIRECTS = 'Redirects will be deleted:';
const TOOLS_REDIRECT_DELETE_CONFIRM_GROUP = 'Group &quot;%s&quot; will be deleted, including redirects:';
const TOOLS_REDIRECT_DELETE_OK = 'Deleted';
const TOOLS_REDIRECT_STATUS = 'Status';
const TOOLS_REDIRECT_SAVE = 'Save';
const TOOLS_REDIRECT_MOVE = 'Move to group';
const TOOLS_REDIRECT_MOVE_CONFIRM_REDIRECTS = 'Redirects will be moved:';
const TOOLS_REDIRECT_MOVE_OK = 'Moved';
const TOOLS_REDIRECT_NOTHING_SELECTED = 'No redirects selected';
const TOOLS_REDIRECT_IMPORT = 'Import';
const TOOLS_REDIRECT_FIELDS = 'Redirect fields';
const TOOLS_REDIRECT_CONTINUE = 'Continue';
const TOOLS_REDIRECT_DO_IMPORT = 'Import';
const TOOLS_REDIRECT_MOVE_TITLE = 'Redirects move';
const TOOLS_REDIRECT_DELETE_TITLE = 'Redirects deletion';
const TOOLS_REDIRECT_IMPORT_TITLE = 'Redirects import';

const TOOLS_CRON = 'Task manager';
const TOOLS_CRON_INTERVAL = 'Interval (m:h:d)';
const TOOLS_CRON_MINUTES = 'Minutes';
const TOOLS_CRON_HOURS = 'Hours';
const TOOLS_CRON_DAYS = 'Days';
const TOOLS_CRON_MONTHS = 'Months';
const TOOLS_CRON_LAUNCHED = 'Last Launch';
const TOOLS_CRON_NEXT = 'Next Launch';
const TOOLS_CRON_SCRIPTURL = 'Script URL';
const TOOLS_CRON_ADDLINK = 'Add task';
const TOOLS_CRON_CHANGE = 'Settings';
const TOOLS_CRON_NOTASKS = 'No tasks.';
const TOOLS_CRON_WRONG_DOMAIN = "The domain name %s is incorrectly specified, tasks will not be performed, configure the domain name in <a href='%s' target='_blank'>site settings</a>";

const TOOLS_COPYSUB = 'Copy sub';
const TOOLS_COPYSUB_COPY = 'Copy';
const TOOLS_COPYSUB_COPY_SUCCESS = 'Copying successful';
const TOOLS_COPYSUB_SOURCE = 'Source';
const TOOLS_COPYSUB_DESTINATION = 'Destination';
const TOOLS_COPYSUB_SITE = 'Site';
const TOOLS_COPYSUB_SUB = 'Sub';
const TOOLS_COPYSUB_KEYWORD_SUB = 'Section keyword';
const TOOLS_COPYSUB_TEMPLATE_NAME = 'Template name';
const TOOLS_COPYSUB_SETTINGS = 'Copy settings';
const TOOLS_COPYSUB_COPY_WITH_CHILD = 'copy sub';
const TOOLS_COPYSUB_COPY_WITH_CC = 'copy infoblocks';
const TOOLS_COPYSUB_COPY_WITH_OBJECT = 'copy objects';
const TOOLS_COPYSUB_ERROR_KEYWORD_EXIST = 'Sub with the keyword already exists';
const TOOLS_COPYSUB_ERROR_LEVEL_COUNT = 'You can not copy a section in its own sub';
const TOOLS_COPYSUB_ERROR_PARAM = 'Invalid parameters';
const TOOLS_COPYSUB_ERROR_SITE_NOT_FOUND = 'Site not found';

// TOOLS TRASH
const TOOLS_TRASH = 'Trash bin';
const TOOLS_TRASH_CLEAN = 'Clean trash bin';

// MODERATION SECTION
const NETCAT_MODERATION_NO_OBJECTS_IN_SUBCLASS = 'There are no data in this component.';

const NETCAT_MODERATION_ERROR_NORIGHTS = ' You have no permission to perform an operation!';
const NETCAT_MODERATION_ERROR_NORIGHT = 'You have no permission to perform an operation!';
const NETCAT_MODERATION_ERROR_NORIGHTGUEST = 'Guest can not perform this operation';
const NETCAT_MODERATION_ERROR_NOOBJADD = 'Cannot add object.';
const NETCAT_MODERATION_ERROR_NOOBJCHANGE = 'Cannot change object.';
const NETCAT_MODERATION_MSG_OBJADD = 'Object added.';
const NETCAT_MODERATION_MSG_OBJADDMOD = 'Object will be added after moderation.';
const NETCAT_MODERATION_MSG_OBJCHANGED = 'Object changed.';
const NETCAT_MODERATION_MSG_OBJDELETED = 'Object deleted.';
const NETCAT_MODERATION_FILES_UPLOADED = 'Uploaded';
const NETCAT_MODERATION_FILES_DELETE = 'delete file';
const NETCAT_MODERATION_LISTS_CHOOSE = '-- choose --';
const NETCAT_MODERATION_RADIO_EMPTY = 'No answer';
const NETCAT_MODERATION_PRIORITY = 'Object priority';
const NETCAT_MODERATION_TURNON = 'turn on';
const NETCAT_MODERATION_DEMO_CONTENT = 'demo content';
const NETCAT_MODERATION_OBJADDED = 'Object addition';
const NETCAT_MODERATION_OBJUPDATED = 'Object change';
const NETCAT_MODERATION_MSG_OBJSDELETED = 'Objects deleted';
const NETCAT_MODERATION_OBJ_ON = 'on';
const NETCAT_MODERATION_OBJ_OFF = 'off';
const NETCAT_MODERATION_OBJECT = 'Object';
const NETCAT_MODERATION_MORE = 'more';
const NETCAT_MODERATION_MORE_CONTAINER = 'Container actions...';
const NETCAT_MODERATION_MORE_BLOCK = 'Block actions...';
const NETCAT_MODERATION_MORE_OBJECT = 'Object actions...';
const NETCAT_MODERATION_BLOCK_SETTINGS = 'Block settings';
const NETCAT_MODERATION_DELETE_BLOCK = 'Remove this block';
const NETCAT_MODERATION_ADD_BLOCK = 'Add a block';
const NETCAT_MODERATION_ADD_BLOCK_BEFORE = 'before';
const NETCAT_MODERATION_ADD_BLOCK_INSIDE = 'inside';
const NETCAT_MODERATION_ADD_BLOCK_AFTER = 'after';
const NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_CONTAINER = 'the container';
const NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_CONTAINER = 'the ‘%s’ container';
const NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_BLOCK = 'the ‘%s’ block';
const NETCAT_MODERATION_ADD_BLOCK_RELATIVE_TO_THIS_BLOCK = 'this block';
const NETCAT_MODERATION_ADD_BLOCK_TITLE = 'New block';
const NETCAT_MODERATION_NEW_BLOCK_TITLE = 'New block';
const NETCAT_MODERATION_ADD_BLOCK_WRAP_TITLE = 'Creating a shared container';
const NETCAT_MODERATION_ADD_BLOCK_WRAP_BLOCK = 'New block will be put into a new container together with the ‘%s’ block.';
const NETCAT_MODERATION_ADD_OBJECT = 'Add';
const NETCAT_MODERATION_ADD_OBJECT_DEFAULT = 'element';
const NETCAT_MODERATION_REMOVE_INFOBLOCK_CONFIRMATION_HEADER = 'Page block removal';
const NETCAT_MODERATION_REMOVE_INFOBLOCK_CONFIRMATION_HEADER_CAN_RESTORE = 'Page block removal';
const NETCAT_MODERATION_REMOVE_INFOBLOCK_CONFIRMATION_BODY = 'Block &ldquo;%s&rdquo; and its content will be removed from the page. Press &ldquo;Delete&rdquo; to confirm this action';
const NETCAT_MODERATION_COMPONENT_SEARCH_BY_NAME = 'search by name';
const NETCAT_MODERATION_CLEAR_FIELD = 'reset';
const NETCAT_MODERATION_CLEAR_FORM = 'Reset form';

const NETCAT_MODERATION_COMPONENT_NO_TEMPLATE = 'Base component template';
const NETCAT_MODERATION_COMPONENT_TEMPLATE = 'Template';
const NETCAT_MODERATION_COMPONENT_TEMPLATES = 'Templates';
const NETCAT_MODERATION_COMPONENT_TEMPLATE_PREV = 'previous template';
const NETCAT_MODERATION_COMPONENT_TEMPLATE_NEXT = 'next template';
const NETCAT_MODERATION_COPY_BLOCK = 'Copy';
const NETCAT_MODERATION_CUT_BLOCK = 'Cut';
const NETCAT_MODERATION_PASTE_BLOCK = 'Paste copied (or cut) block here';
const NETCAT_MODERATION_CONTAINER = 'Container';
const NETCAT_MODERATION_MAIN_CONTAINER = 'Main content';
const NETCAT_MODERATION_ADD_CONTAINER = 'Add block container';
const NETCAT_MODERATION_REMOVE_IMAGE = 'Remove image';
const NETCAT_MODERATION_REPLACE_IMAGE = 'Replace image';

const NETCAT_MODERATION_WARN_COMMITDELETION = 'Confirm removal of object #%s';
const NETCAT_MODERATION_WARN_COMMITDELETIONINCLASS = 'Confirm removal of object in component #%s';

const NETCAT_MODERATION_PASSWORD = 'Password (*)';
const NETCAT_MODERATION_PASSWORDAGAIN = 'Enter password again';
const NETCAT_MODERATION_INFO_REQFIELDS = 'Fields highlighted with (*) cannot be empty.';
const NETCAT_MODERATION_BUTTON_ADD = 'Add';
const NETCAT_MODERATION_BUTTON_CHANGE = 'Save changes';
const NETCAT_MODERATION_BUTTON_RESET = 'Reset';

const NETCAT_MODERATION_COMMON_KILLALL = 'Delete objects';
const NETCAT_MODERATION_COMMON_KILLONE = 'Delete object';

const NETCAT_MODERATION_MULTIFILE_SIZE = 'Files exceeding allowed file size (%SIZE) in <em>%NAME</em>';
const NETCAT_MODERATION_MULTIFILE_TYPE = 'Files of wrong type in <em>%NAME</em>';
const NETCAT_MODERATION_MULTIFILE_MIN_COUNT = 'At least %FILES must be uploaded to <em>%NAME</em>';
const NETCAT_MODERATION_MULTIFILE_MAX_COUNT = 'No more than %FILES can be uploaded to <em>%NAME</em>';
const NETCAT_MODERATION_MULTIFILE_COUNT_FILES = 'file,files';
const NETCAT_MODERATION_MULTIFILE_DEFAULT_CUSTOM_NAME_CAPTION = 'file description';
const NETCAT_MODERATION_ADD = 'add';

const NETCAT_MODERATION_MSG_ONE = 'Field %NAME is required.';
const NETCAT_MODERATION_MSG_TWO = 'Wrong value type in <em>%NAME</em>.';
const NETCAT_MODERATION_MSG_SIX = 'It is necessary to upload a file <em>%NAME</em>.';
const NETCAT_MODERATION_MSG_SEVEN = 'File <em>%NAME</em> is too big.';
const NETCAT_MODERATION_MSG_EIGHT = 'Wrong file format for <em>%NAME</em>.';
const NETCAT_MODERATION_MSG_TWENTYONE = 'Invalid keyword.';
const NETCAT_MODERATION_MSG_RETRYPASS = 'Re-enter password';
const NETCAT_MODERATION_MSG_PASSMIN = 'Minimal password length is %s symbols.';
const NETCAT_MODERATION_MSG_NEED_AGREED = 'Agree';
const NETCAT_MODERATION_MSG_LOGINALREADY = 'Login %s is already in use';
const NETCAT_MODERATION_MSG_LOGININCORRECT = 'Login incorrect';
const NETCAT_MODERATION_BACKTOSECTION = 'Return back to section';

const NETCAT_MODERATION_ISON = 'Turned on';
const NETCAT_MODERATION_ISOFF = 'Turned off';
const NETCAT_MODERATION_OBJISON = 'Object turned on';
const NETCAT_MODERATION_OBJISOFF = 'Object turned off';
const NETCAT_MODERATION_OBJSAREON = 'Objects turned on';
const NETCAT_MODERATION_OBJSAREOFF = 'Objects turned off';
const NETCAT_MODERATION_CHANGED = 'ID of the changed user';
const NETCAT_MODERATION_CHANGE = 'Edit';
const NETCAT_MODERATION_DELETE = 'Delete';
const NETCAT_MODERATION_TURNTOON = 'Turn on';
const NETCAT_MODERATION_TURNTOOFF = 'Turn off';
const NETCAT_MODERATION_ID = 'Identifier';
const NETCAT_MODERATION_COPY_OBJECT = 'Copy / move';

const NETCAT_MODERATION_REMALL = 'Delete all';
const NETCAT_MODERATION_DELETESELECTED = 'Delete selected';
const NETCAT_MODERATION_SELECTEDON = 'Turn selected objects on';
const NETCAT_MODERATION_SELECTEDOFF = 'Turn selected objects off';
const NETCAT_MODERATION_NOTSELECTEDOBJ = 'Objects are not selected';
const NETCAT_MODERATION_APPLY_CHANGES_TITLE = 'Apply changes?';
const NETCAT_MODERATION_APPLY_CHANGES_TEXT = 'Do you really want to apply changes?';
const NETCAT_MODERATION_CLASSID = 'Number of component in section';
const NETCAT_MODERATION_ADDEDON = 'ID of the added user';

const NETCAT_MODERATION_MOD_NOANSWER = 'no value';
const NETCAT_MODERATION_MOD_DON = ' to ';
const NETCAT_MODERATION_MOD_FROM = ' from ';
const NETCAT_MODERATION_MODA = '--------- No value ---------';

const NETCAT_MODERATION_FILTER = 'Filter';
const NETCAT_MODERATION_TITLE = 'Title';
const NETCAT_MODERATION_DESCRIPTION = 'Description';

const NETCAT_MODERATION_TRASHED_OBJECTS = 'Trashed objects';
const NETCAT_MODERATION_TRASHED_OBJECTS_RESTORE = 'Restore object';

const NETCAT_MODERATION_NO_RELATED = '[none]';
const NETCAT_MODERATION_RELATED_INEXISTENT = '[nonexistent object ID=%s]';
const NETCAT_MODERATION_CHANGE_RELATED = 'change';
const NETCAT_MODERATION_REMOVE_RELATED = 'remove';
const NETCAT_MODERATION_SELECT_RELATED = 'select';
const NETCAT_MODERATION_COPY_HERE_RELATED = 'Copy here';
const NETCAT_MODERATION_MOVE_HERE_RELATED = 'Move here';
const NETCAT_MODERATION_CONFIRM_COPY_RELATED = 'Confirm action';
const NETCAT_MODERATION_RELATED_POPUP_TITLE = "Select related item for '%s'";
const NETCAT_MODERATION_RELATED_NO_CONCRETE_CLASS_IN_SUB = 'There are no &quot;%s&quot; components in this section.';
const NETCAT_MODERATION_RELATED_NO_ANY_CLASS_IN_SUB = 'There are no components in this section.';
const NETCAT_MODERATION_RELATED_ERROR_SAVING = "Cannot save the value you've selected (probably the main object form was closed). Please try to select value once again.";
const NETCAT_MODERATION_COPY_SUCCESS = 'Object successfully copied';
const NETCAT_MODERATION_MOVE_SUCCESS = 'Object successfully moved';


const NETCAT_MODERATION_SEO_TITLE = 'Title';
const NETCAT_MODERATION_SEO_H1 = 'Page header - H1';
const NETCAT_MODERATION_SEO_KEYWORDS = 'SEO keywords';
const NETCAT_MODERATION_SEO_DESCRIPTION = 'SEO description';

const NETCAT_MODERATION_SMO_TITLE = 'SMO title';
const NETCAT_MODERATION_SMO_TITLE_HELPER = 'Will header article when posting links to a page on Facebook or VKontakte';
const NETCAT_MODERATION_SMO_DESCRIPTION = 'SMO description';
const NETCAT_MODERATION_SMO_DESCRIPTION_HELPER = 'Will the text of the article when posting links to a page on Facebook or VKontakte';
const NETCAT_MODERATION_SMO_IMAGE = 'SMO image';

const NETCAT_MODERATION_FULL_PAGE_MODE = 'Object page mode';
const NETCAT_MODERATION_FULL_PAGE_INHERIT= 'inherit (%s)';
const NETCAT_MODERATION_FULL_PAGE_TEMPLATE = 'use selected component template';
const NETCAT_MODERATION_FULL_PAGE_CUSTOM = 'custom page from blocks';
const NETCAT_MODERATION_FULL_PAGE_NONE = 'no object page';
const NETCAT_MODERATION_ERROR_FULL_PAGE_CONTEXT = 'Full page template cannot be used neither on pages of other type nor outside main content area!';

const NETCAT_MODERATION_STANDART_FIELD_USER_ID = 'User ID';
const NETCAT_MODERATION_STANDART_FIELD_USER = 'User';
const NETCAT_MODERATION_STANDART_FIELD_PRIORITY = 'Priority';
const NETCAT_MODERATION_STANDART_FIELD_KEYWORD = 'Keyword';
const NETCAT_MODERATION_STANDART_FIELD_NC_TITLE = 'SEO Meta Title';
const NETCAT_MODERATION_STANDART_FIELD_NC_KEYWORDS = 'SEO Meta Keywords';
const NETCAT_MODERATION_STANDART_FIELD_NC_DESCRIPTION = 'SEO Meta Description';
const NETCAT_MODERATION_STANDART_FIELD_NC_IMAGE = 'Image';
const NETCAT_MODERATION_STANDART_FIELD_NC_ICON = 'Icon';
const NETCAT_MODERATION_STANDART_FIELD_NC_SMO_TITLE = 'SMO Meta Title';
const NETCAT_MODERATION_STANDART_FIELD_NC_SMO_DESCRIPTION = 'SMO Meta Description';
const NETCAT_MODERATION_STANDART_FIELD_NC_SMO_IMAGE = 'SMO Meta Image';
const NETCAT_MODERATION_STANDART_FIELD_IP = 'IP';
const NETCAT_MODERATION_STANDART_FIELD_USER_AGENT = 'User agent';
const NETCAT_MODERATION_STANDART_FIELD_CREATED = 'Created';
const NETCAT_MODERATION_STANDART_FIELD_LAST_UPDATED = 'Updated';
const NETCAT_MODERATION_STANDART_FIELD_LAST_USER_ID = 'Last user ID';
const NETCAT_MODERATION_STANDART_FIELD_LAST_USER = 'Last user';
const NETCAT_MODERATION_STANDART_FIELD_LAST_IP = 'Last IP';
const NETCAT_MODERATION_STANDART_FIELD_LAST_USER_AGENT = 'Last user agent';

const NETCAT_MODERATION_VERSION = 'draft';
const NETCAT_MODERATION_VERSION_NOT_FOUND = 'draft not found';
const NETCAT_SAVE_DRAFT = 'Save draft';
const NETCAT_MODERATION_ADD_BUTTON = "Add";
const NETCAT_MODERATION_PREPOSITION_BEHIND = "behind";
const NETCAT_MODERATION_PREPOSITION_AT = "on";

// MODULE
const NETCAT_MODULES = 'Modules';
const NETCAT_MODULES_TUNING = 'Module settings';
const NETCAT_MODULES_PARAM = 'Condition';
const NETCAT_MODULES_VALUE = 'Value';
const NETCAT_MODULES_ADDPARAM = 'Add condition';
const NETCAT_MODULE_INSTALLCOMPLIED = 'Module installation is complete.';
const NETCAT_MODULE_ALWAYS_LOAD = 'Always load';
const NETCAT_MODULE_ONOFF = 'On/off';
define("NETCAT_MODULE_MODULE_UNCHECKED",
    "The module is off, his adjusting is not possible. You can switch on the module  in the <a href='" . $ADMIN_PATH .
    "modules/index.php'> list of modules. </a>");

// MODULE DEFAULT
define("NETCAT_MODULE_DEFAULT_DESCRIPTION", "You can place your own functions in  " . nc_module_path('default') .
    "function.inc.php. You may create a own scripts integrated with CMS. You may find a sample script in " .
    nc_module_path('default') . "index.php. In the bottom field you may define a module environment variables.");

// MISCELLANEOUS SETTINGS
const NETCAT_SETTINGS_MISC = 'Miscellaneous';
const NETCAT_SETTINGS_MISC_SECRET_KEY = 'Secret key used for verifying transactions (page refresh required Ctrl + F5)';
#CODE MIRROR
const NETCAT_SETTINGS_CODEMIRROR = 'Code Mirror';
const NETCAT_SETTINGS_CODEMIRROR_EMBEDED = 'Embeded';
const NETCAT_SETTINGS_CODEMIRROR_EMBEDED_ON = 'Yes';
const NETCAT_SETTINGS_CODEMIRROR_DEFAULT = 'Highlight on default';
const NETCAT_SETTINGS_CODEMIRROR_DEFAULT_ON = 'Yes';
const NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE = 'Autocomplete';
const NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE_ON = 'Yes';
const NETCAT_SETTINGS_CODEMIRROR_HELP = 'Doc dialog box';
const NETCAT_SETTINGS_CODEMIRROR_HELP_ON = 'Yes';
const NETCAT_SETTINGS_CODEMIRROR_ENABLE = 'Enable editor';
const NETCAT_SETTINGS_CODEMIRROR_SWITCH = 'Switch editor';
const NETCAT_SETTINGS_CODEMIRROR_WRAP = 'Wrap lines';
const NETCAT_SETTINGS_CODEMIRROR_FULLSCREEN = 'Fullscreen';

const NETCAT_SETTINGS_DRAG = 'Drag and drop of elements (subdivisions, infoblocks, objects, components etc)';
const NETCAT_SETTINGS_DRAG_SILENT = 'move without confirmation';
const NETCAT_SETTINGS_DRAG_CONFIRM = 'ask for a confirmation before moving';
const NETCAT_SETTINGS_DRAG_DISABLED = 'disable drag and drop';

// EDITOR
const NETCAT_SETTINGS_EDITOR = 'Editor functions';
const NETCAT_SETTINGS_EDITOR_TYPE = 'Type of HTML-editor';
const NETCAT_SETTINGS_EDITOR_FCKEDITOR = 'FCKeditor';
const NETCAT_SETTINGS_EDITOR_CKEDITOR = 'CKEditor';
const NETCAT_SETTINGS_EDITOR_TINYMCE = 'TinyMCE';
const NETCAT_SETTINGS_EDITOR_CKEDITOR_FILE_SYSTEM = 'Divide loadable files into user&#039;s individual directories';
const NETCAT_SETTINGS_EDITOR_CKEDITOR_CYRILIC_FOLDER = 'Allow cyrillic symbols in folder names in file manager (CKEditor)';
const NETCAT_SETTINGS_EDITOR_CKEDITOR_CONTENT_FILTER = 'Enable <a href="http://docs.ckeditor.com/#!/guide/dev_advanced_content_filter" target="_blank">content filter</a> (CKEditor)';
const NETCAT_SETTINGS_EDITOR_EMBED_ON = 'Yes';
const NETCAT_SETTINGS_EDITOR_EMBED_TO_FIELD = 'Embed editor into text area field';
const NETCAT_SETTINGS_EDITOR_SEND = 'Send';
const NETCAT_SETTINGS_EDITOR_STYLES_SAVE = 'Save changes';
const NETCAT_SETTINGS_EDITOR_STYLES = 'Styles for editor';
const NETCAT_SETTINGS_EDITOR_SKINS = 'Theme';
const NETCAT_SETTINGS_EDITOR_ENTER_MODE = 'Enter key mode';
const NETCAT_SETTINGS_EDITOR_ENTER_MODE_P = 'P tag';
const NETCAT_SETTINGS_EDITOR_ENTER_MODE_BR = 'BR tag';
const NETCAT_SETTINGS_EDITOR_ENTER_MODE_DIV = 'DIV tag';
const NETCAT_SETTINGS_EDITOR_SAVE = 'Settings updated successfully';
const NETCAT_SETTINGS_EDITOR_KEYCODE = 'You can save this form by pressing Ctrl + %s; page reload (Ctrl + F5) is required';

const NETCAT_SEARCH_FIND_IT = 'Search';
const NETCAT_SEARCH_ERROR = 'Search error.';

// JS settings
const NETCAT_SETTINGS_JS = 'Scripts loader';
const NETCAT_SETTINGS_JS_FUNC_NC_JS = 'Function nc_js()';
const NETCAT_SETTINGS_JS_LOAD_JQUERY_DOLLAR = "Load jQuery's object $";
const NETCAT_SETTINGS_JS_LOAD_JQUERY_EXTENSIONS_ALWAYS = 'Always load jQuery extensions';
const NETCAT_SETTINGS_JS_LOAD_MODULES_SCRIPTS = "Preload modules' scripts";
const NETCAT_SETTINGS_MINIFY_STATIC_FILES = 'Minify CSS and JS files in admin panel';

const NETCAT_SETTINGS_TRASHBIN = 'Trash bin';
const NETCAT_SETTINGS_TRASHBIN_USE = 'Use trash bin';

const NETCAT_SETTINGS_EXPORT = 'Import/Export data';
const NETCAT_SETTINGS_EXPORT_USE = 'Maximum size of stored export files';

#Components
const NETCAT_SETTINGS_COMPONENTS = 'Components';
const NETCAT_SETTINGS_REMIND_SAVE = 'Remind to save (page refresh required Ctrl + F5)';
const NETCAT_SETTINGS_PACKET_OPERATIONS = 'Enable packet operations with objects';
const NETCAT_SETTINGS_TEXTAREA_RESIZE = 'Enable resizing of textarea on component edit pages';

define('NETCAT_SETTINGS_QUICKBAR', CMS_SYSTEM_NAME . ' QuickBar');
const NETCAT_SETTINGS_QUICKBAR_ENABLE = 'Enable for permitted users';
const NETCAT_SETTINGS_QUICKBAR_ON = 'Yes';

// ALT ADMIN BLOCKS
const NETCAT_SETTINGS_ALTBLOCKS = 'Alternative blocks of administration';
const NETCAT_SETTINGS_ALTBLOCKS_ON = 'Yes';
const NETCAT_SETTINGS_ALTBLOCKS_TEXT = 'User alternative blocks of administration';
const NETCAT_SETTINGS_ALTBLOCKS_PARAMS = 'Additional parameters for delete (begin with &)';

const NETCAT_SETTINGS_HTTP_PROXY = 'Use HTTP proxy server to connect to the update server';
const NETCAT_SETTINGS_HTTP_PROXY_HOST = 'Proxy server IP address';
const NETCAT_SETTINGS_HTTP_PROXY_PORT = 'Port';
const NETCAT_SETTINGS_HTTP_PROXY_USER = 'User';
const NETCAT_SETTINGS_HTTP_PROXY_PASSWORD = 'Password';

const NETCAT_SETTINGS_USETOKEN = 'Use token';
const NETCAT_SETTINGS_USETOKEN_ADD = 'add';
const NETCAT_SETTINGS_USETOKEN_EDIT = 'edit';
const NETCAT_SETTINGS_USETOKEN_DROP = 'drop';

const NETCAT_SETTINGS_OBJECTS_FULLINK = 'Objects full view';
const CONTROL_SETTINGSFILE_BASIC_VERSION = 'System version';
const CONTROL_SETTINGSFILE_CHANGE_EMAILS_FIELD = 'Field (with email) in user table';
const CONTROL_SETTINGSFILE_CHANGE_EMAILS_NONE = 'Field is undefined';
const NETCAT_SETTINGS_CODEMIRROR_EMBEDED_OFF = 'No';
const NETCAT_SETTINGS_CODEMIRROR_DEFAULT_OFF = 'No';
const NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE_OFF = 'No';
const NETCAT_SETTINGS_CODEMIRROR_HELP_OFF = 'No';
const NETCAT_SETTINGS_INLINE_EDIT_CONFIRMATION = 'Confirm inline editor changes';
const NETCAT_SETTINGS_INLINE_EDIT_CONFIRMATION_ON = 'Confirmation of inline editor changes enabled';
const NETCAT_SETTINGS_INLINE_EDIT_CONFIRMATION_OFF = 'Confirmation of inline editor changes disabled';
const NETCAT_SETTINGS_EDITOR_EMBEDED = 'Embed editor into text area field';
const NETCAT_SETTINGS_EDITOR_EMBED_OFF = 'No';
const NETCAT_SETTINGS_EDITOR_STYLES_CANCEL = 'Cancel';
const NETCAT_SETTINGS_TRASHBIN_MAXSIZE = 'Maximum size of trash bin';
const NETCAT_SETTINGS_REMIND_SAVE_INFO = 'Remind to save';
const NETCAT_SETTINGS_PACKET_OPERATIONS_INFO = 'Enable packet operations with objects';
const NETCAT_SETTINGS_TEXTAREA_RESIZE_INFO = 'Enable resizing of textarea on component edit pages';
const NETCAT_SETTINGS_DISABLE_BLOCK_MARKUP_INFO = 'Disable <a href="https://netcat.ru/developers/docs/components/stylesheets/" target="_blank">additional block markup</a> for new classes';
const CONTROL_CLASS_IS_MULTIPURPOSE_SWITCH = 'Multipurpose template';
const CONTROL_CLASS_COMPATIBLE_FIELDS = 'List of required fields in compatible components (one per line)';
const NETCAT_SETTINGS_QUICKBAR_OFF = 'No';
const NETCAT_SETTINGS_ALTBLOCKS_OFF = 'No';


// Export / Import
const NETCAT_IMPORT_FIELD = 'XML file for import';

const NETCAT_FILEUPLOAD_ERROR = "Error! You don't have permissions to access %s on this server.";


const NETCAT_HTTP_REQUEST_SAVING = 'Sending to server...';
const NETCAT_HTTP_REQUEST_SAVED = 'Changes were saved';
const NETCAT_HTTP_REQUEST_ERROR = "Error saving data (<a href='javascript:showFormSaveError()'>details</a>)";
const NETCAT_HTTP_REQUEST_HINT = 'Press Ctrl + %s to save this form without page reload';

// Index page menu
const SECTION_INDEX_MENU_TITLE = 'Main menu';
const SECTION_INDEX_MENU_HOME = 'home';
const SECTION_INDEX_MENU_SITE = 'site';
const SECTION_INDEX_MENU_DEVELOPMENT = 'development';
const SECTION_INDEX_MENU_TOOLS = 'tools';
const SECTION_INDEX_MENU_SETTINGS = 'settings';
const SECTION_INDEX_MENU_HELP = 'help';
const SECTION_INDEX_MENU_SUPPORT = 'support';

define("SECTION_INDEX_HELP_SUBMENU_HELP", CMS_SYSTEM_NAME . " help");
const SECTION_INDEX_HELP_SUBMENU_DOC = 'Documentation';
const SECTION_INDEX_HELP_SUBMENU_HELPDESK = 'Online support';
const SECTION_INDEX_HELP_SUBMENU_SUPPORT = 'Support access';
const SECTION_INDEX_HELP_SUBMENU_FORUM = 'Forum';
const SECTION_INDEX_HELP_SUBMENU_BASE = 'Knowledge base';
const SECTION_INDEX_HELP_SUBMENU_ABOUT = 'About';

const SECTION_INDEX_SITE_LIST = 'Sites';
const SECTION_INDEX_SITE_LIST_SAAS = 'Sites (personal account)';

const SECTION_INDEX_WIZARD_SUBMENU_CLASS = 'Component wizard';
const SECTION_INDEX_WIZARD_SUBMENU_SITE = 'Site wizard';

const SECTION_INDEX_FAVORITE_ANOTHER_SUB = 'Another section...';
const SECTION_INDEX_FAVORITE_ADD = 'Add into current menu';
const SECTION_INDEX_FAVORITE_LIST = 'Edit menu';
const SECTION_INDEX_FAVORITE_SETTINGS = 'Settings';

const SECTION_INDEX_WELCOME = 'Welcome';
const SECTION_INDEX_WELCOME_MESSAGE = 'Hello, %s!<br>You are in management system of &laquo;%s&raquo; project.<br>Your role is: %s.';
define("SECTION_INDEX_TITLE", "Content Management System " . CMS_SYSTEM_NAME);

// SITE
#// TABS
const SITE_TAB_SITEMAP = 'Site map';
const SITE_TAB_SETTINGS = 'Settings';
const SITE_TAB_DEMO_CONTENT = 'Demo content';
const SITE_TAB_STATS = 'Statistics';
const SITE_TAB_AREA_INFOBLOCKS = 'Site area infoblocks';
#// TOOLBAR
const SITE_TOOLBAR_INFO = 'General Information';
const SITE_TOOLBAR_SUBLIST = 'Subdivision list';


// SUBDIVISION
#// TABS
#// TOOLBAR
const SUBDIVISION_TAB_INFO_TOOLBAR_INFO = 'Subdivision information';
const SUBDIVISION_TAB_INFO_TOOLBAR_SUBLIST = 'Subdivisions list';
const SUBDIVISION_TAB_INFO_TOOLBAR_CCLIST = 'Components list';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST = 'Users';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_EDIT = 'Main';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_DESIGN = 'Design';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_SEO = 'SEO/SMO';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_SYSTEM = 'System';
const SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_FIELDS = 'Fields';

#// BUTTONS
const SUBDIVISION_TAB_PREVIEW_BUTTON_PREVIEW = 'Preview in new window';

const SITE_SITEMAP_SEARCH = 'Site map search';
const SITE_SITEMAP_SEARCH_NOT_FOUND = 'Not found';

#// TEXT
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_READ_ACCESS = 'Read access';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_COMMENT_ACCESS = 'Comment access';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_WRITE_ACCESS = 'Write access';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_EDIT_ACCESS = 'Edit access';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_SUBSCRIBE_ACCESS = 'Subscribe access';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_MODERATORS = 'Moderators';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ADMINS = 'Administrators';

const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ALL_USERS = 'All users';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_REGISTERED_USERS = 'Registered users';
const SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS = 'Privileged users';

// CLASS WIZARD

const WIZARD_CLASS_FORM_SUBDIVISION_PARENTSUB = 'Parent subdivision';

const WIZARD_PARENTSUB_SELECT_POPUP_TITLE = 'Parent subdivision selection';

const WIZARD_CLASS_FORM_SUBDIVISION_SELECT_PARENTSUB = 'select parent subdivision';
const WIZARD_CLASS_FORM_SUBDIVISION_SELECT_SUBDIVISION = 'select subdivision';
const WIZARD_CLASS_FORM_SUBDIVISION_DELETE = 'delete';

const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE = 'Template type';
const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_SINGLE = 'Single object on the page';
const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_LIST = 'Object list';
const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_SEARCH = 'Object list search';
const WIZARD_CLASS_FORM_START_TEMPLATE_TYPE_FORM = 'Web form';

const WIZARD_CLASS_FORM_SETTINGS_NO_SETTINGS = 'There are no settings for this type of template.';
const WIZARD_CLASS_FORM_SETTINGS_FIELDS_FOR_OBJECT_LIST = 'Fields for object list';
const WIZARD_CLASS_FORM_SETTINGS_SORT_OBJECT_BY_FIELD = 'Sort objects by field';
const WIZARD_CLASS_FORM_SETTINGS_SORT_ASC = 'Ascending';
const WIZARD_CLASS_FORM_SETTINGS_SORT_DESC = 'Descending';
const WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION = 'Page navigation';
const WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION_BY_NEXT_PREV = 'Page navigation by next and previous links';
const WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION_BY_PAGE_NUMBER = 'Page navigation by page numbers';
const WIZARD_CLASS_FORM_SETTINGS_PAGE_NAVIGATION_BY_BOTH = 'Page navigation by both links and page numbers';
const WIZARD_CLASS_FORM_SETTINGS_LOCATION_OF_NAVIGATION = 'Location of page navigation';
const WIZARD_CLASS_FORM_SETTINGS_LOCATION_TOP = 'Page top';
const WIZARD_CLASS_FORM_SETTINGS_LOCATION_BOTTOM = 'Page bottom';
const WIZARD_CLASS_FORM_SETTINGS_LOCATION_BOTH = 'Both page top and page bottom';
const WIZARD_CLASS_FORM_SETTINGS_LIST_TYPE = 'List';
const WIZARD_CLASS_FORM_SETTINGS_TABLE_TYPE = 'Table';
const WIZARD_CLASS_FORM_SETTINGS_LIST_DELIMITER_TYPE = 'List delimiter type';
const WIZARD_CLASS_FORM_SETTINGS_TABLE_TYPE_SETTINGS = 'Table type settings';
const WIZARD_CLASS_FORM_SETTINGS_TABLE_BACKGROUND = 'Alternate table rows background';
const WIZARD_CLASS_FORM_SETTINGS_TABLE_BORDER = 'Table border';
const WIZARD_CLASS_FORM_SETTINGS_FULL_PAGE = 'Full information page';
const WIZARD_CLASS_FORM_SETTINGS_FULL_PAGE_LINK_TYPE = 'Full information page link type';
const WIZARD_CLASS_FORM_SETTINGS_CHECK_FIELDS_TO_FULL_PAGE = 'Check fields that would link to full information page.';

const WIZARD_CLASS_FORM_SETTINGS_FIELDS_FOR_OBJECT_SEARCH = 'Object search fields';

const WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_FIELDS_SETTINGS = 'Feedback fields settings';
const WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_SENDER_NAME = 'Sender name';
const WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_SENDER_MAIL = 'Sender Email';
const WIZARD_CLASS_FORM_SETTINGS_FEEDBACK_SUBJECT = 'Subject';

#// TABS
const WIZARD_CLASS_TAB_SELECT_TEMPLATE_TYPE = 'Select template type';
const WIZARD_CLASS_TAB_ADDING_TEMPLATE_FIELDS = 'Adding template fields';
const WIZARD_CLASS_TAB_TEMPLATE_SETTINGS = 'Template settings';
const WIZARD_CLASS_TAB_SUBSEQUENT_ACTION = 'Subsequent action';
const WIZARD_CLASS_TAB_CREATION_SUBDIVISION_WITH_NEW_TEMPLATE = 'Creation subdivision with new template';
const WIZARD_CLASS_TAB_ADDING_TEMPLATE_TO_EXISTENT_SUBDIVISION = 'Adding template to existent subdivision';

#// BUTTONS
const WIZARD_CLASS_BUTTON_NEXT_TO_ADDING_FIELDS = 'Next to adding fields';
const WIZARD_CLASS_BUTTON_FINISH_ADDING_FIELDS = 'Finish adding fields';
const WIZARD_CLASS_BUTTON_SAVE_SETTINGS = 'Save settings';
const WIZARD_CLASS_BUTTON_ADDING_SUBDIVISION_WITH_NEW_TEMPLATE = 'Add subdivision with new template';
const WIZARD_CLASS_BUTTON_CREATE_SUBDIVISION_WITH_NEW_TEMPLATE = 'Create subdivision with new template';
const WIZARD_CLASS_BUTTON_NEXT_TO_SUBDIVISION_SELECTION = 'Next to subdivision selection';

#// LINKS
const WIZARD_CLASS_LINKS_VIEW_TEMPLATE_CODE = 'View template code';
const WIZARD_CLASS_LINKS_CREATE_SUBDIVISION_WITH_NEW_TEMPLATE = 'Create subdivision with new template';
const WIZARD_CLASS_LINKS_ADD_TEMPLATE_TO_EXISTENT_SUBDIVISION = 'Add template to existent subdivision';
const WIZARD_CLASS_LINKS_CREATE_NEW_TEMPLATE = 'Create new template';

const WIZARD_CLASS_LINKS_RETURN_TO_FIELDS_ADDING = 'Return to fields adding';

#// COMMON
const WIZARD_CLASS_STEP = 'Step';
const WIZARD_CLASS_STEP_FROM = 'from';

const WIZARD_CLASS_DEFAULT = 'default';

const WIZARD_CLASS_ERROR_NO_FIELDS = 'You must add one field in template at least!';

const BLOCK_ORIENTATION_LEFT = "left";
const BLOCK_ORIENTATION_RIGHT = "right";
const BLOCK_ORIENTATION_TOP = "top";
const BLOCK_ORIENTATION_BOTTOM = "bottom";

// SITE WIZARD
const WIZARD_SITE_FORM_WHICH_MODULES = 'Which modules do you want to use?';

#// TABS
const WIZARD_SITE_TAB_NEW_SITE_CREATION = 'Creation of new site';
const WIZARD_SITE_TAB_NEW_SITE_ADD_SUB = 'Adding subdivisions';

#// BUTTONS
const WIZARD_SITE_BUTTON_ADD_SUBS = 'Add subdivisions';
const WIZARD_SITE_BUTTON_FINISH_ADD_SUBS = 'Finish adding';

#// COMMON
const WIZARD_SITE_STEP = 'Step';
const WIZARD_SITE_STEP_FROM = 'from';
const WIZARD_SITE_STEP_TWO_DESCRIPTION = 'Creation of service divisions. There must be front page and &#034;not found&#034; page on every site. You may leave these fields without changing.';

#DEMO MODE
const DEMO_MODE_ADMIN_INDEX_MESSAGE = "Site \"%s\" is in demo-mode, you can turn it off in <a href='%s'>system site settings</a>.";
const DEMO_MODE_FRONT_INDEX_MESSAGE_GUEST = "It's demo site only for demonstration.";
const DEMO_MODE_FRONT_INDEX_MESSAGE_ADMIN = "This site is in demo-mode, you can turn it off <a href='%s'>in settings panel</a>.";
const DEMO_MODE_FRONT_INDEX_MESSAGE_CLOSE = 'Close';

// FAVORITE
#// HEADER TEXT
const FAVORITE_HEADERTEXT = 'Favorites';


// CRONTAB
##TABS
const CRONTAB_TAB_LIST = 'Task manager';
const CRONTAB_TAB_ADD = 'Add task';
const CRONTAB_TAB_EDIT = 'Edit task';

##TRASH
const TRASH_TAB_LIST = 'Trash bin';
const TRASH_TAB_TITLE = 'Trash list';
const TRASH_TAB_SETTINGS = 'Settings';

// REDIRECT
##TABS
const REDIRECT_TAB_LIST = 'Redirect';
const REDIRECT_TAB_ADD = 'Add redirect';
const REDIRECT_TAB_EDIT = 'Edit redirect';


// SYSTEM SETTINGS
##TABS
const SYSTEMSETTINGS_TAB_LIST = 'General Settings';
const SYSTEMSETTINGS_TAB_ADD = 'Edit system settings';
const SYSTEMSETTINGS_SAVE_OK = 'Settings saved';
const SYSTEMSETTINGS_SAVE_ERROR = 'Error';

// WYSIWYG SETTINGS
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TAB_SETTINGS = 'Settings';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TAB_PANELS = 'Panels';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_SETTINGS = 'WYSIWYG settings';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_PANELS = 'WYSIWYG editor panels';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_DELETE_CONFIRMATION = 'Confirmation of deleting WYSIWYG editor panels';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_EDIT_FORM = ' - WYSIWYG panel edit';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_ADD_FORM = 'WYSIWYG panel add';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANEL_NOT_EXISTS = 'Panel not exists';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_NO_PANELS = 'No panels';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANEL_EDIT_SUCCESSFUL = 'Panel updated successful';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANEL_ADD_SUCCESSFUL = 'Panel added successful';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANELS_NOT_SELECTED = 'Panels not selected';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANELS_DELETED = 'Panels deleted successful';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_PANELS_DELETE_ERROR = 'Panels not deleted';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_FILL_PANEL_NAME = 'Fill panel name';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_SELECT_TOOLBAR = 'Select at least one toolbar';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_DELETE_SELECTED = 'Delete selected';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_CONFIRM_DELETE = 'Confirm delete';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_CANCEL = 'Cancel';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_EDIT_PANEL = 'Edit panel';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_ADD_PANEL = 'Add panel';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_PANEL_NAME = 'Panel name';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_PANEL_PREVIEW = 'Preview';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_DELETE = 'Delete';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_ARE_YOU_REALLY_WANT_TO_DELETE_PANELS = 'Are you really want to delete panels?';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_EDITOR_PANEL_FULL = 'Full edit panel';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_EDITOR_PANEL_INLINE = 'Inline edit panel';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_PANEL_NAME = 'Panel name';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_SETTINGS = 'Toolbars settings';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_MODE = 'Document mode';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_DOCUMENT = 'Document operations';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_TOOLS = 'Tools';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_DOCTOOLS = 'Templates';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_CLIPBOARD = 'Clipboard';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_UNDO = 'Undo actions';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_FIND = 'Find and replace';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_SELECTION = 'Selection';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_SPELLCHECKER = 'Spellchecker';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_FORMS = 'Forms';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_BASICSTYLES = 'Basic styles';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_CLEANUP = 'Format cleanup';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_LIST = 'Lists';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_INDENT = 'Indents';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_BLOCKS = 'Text blocks';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_ALIGN = 'Align';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_LINKS = 'Links';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_INSERT = 'Insert objects';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_STYLES = 'Styles';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_COLORS = 'Colors';
const NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_OTHERS = 'Others';

const NETCAT_WYSIWYG_FCKEDITOR_SETTINGS_TITLE_SETTINGS = 'WYSIWYG settings';

const NETCAT_WYSIWYG_SETTINGS_PANEL_SETTINGS = 'Panels settings';
const NETCAT_WYSIWYG_SETTINGS_THIS_EDITOR_IS_USED_BY_DEFAULT = 'This editor is used by default';
const NETCAT_WYSIWYG_SETTINGS_USE_BY_DEFAULT = 'Use this editor by default';
const NETCAT_WYSIWYG_SETTINGS_BASIC_SETTINGS = 'Basic settings';
const NETCAT_WYSIWYG_SETTINGS_MESSAGE_EDITOR_ACTIVATED = 'Editor activated successful';
const NETCAT_WYSIWYG_SETTINGS_PANEL_NOT_SELECTED = 'Not selected';
const NETCAT_WYSIWYG_SETTINGS_BUTTON_SAVE = 'Save';
const NETCAT_WYSIWYG_SETTINGS_MESSAGE_SETTINGS_SAVED = 'WYSIWYG settings saved';
const NETCAT_WYSIWYG_SETTINGS_MESSAGE_SETTINGS_SAVE_ERROR = 'WYSIWYG settings not saved';
const NETCAT_WYSIWYG_SETTINGS_CONFIG_JS_SETTINGS = 'config.js settings';
const NETCAT_WYSIWYG_SETTINGS_CONFIG_JS_FILE = 'config.js file';

const NETCAT_WYSIWYG_EDITOR_OUTWORN = 'This editor is out of date, be sure to switch to a different editor and delete directory %s from your server';

// Not Elsewhere Specified
const NOT_ELSEWHERE_SPECIFIED = 'Not Elsewhere Specified';
const NOT_ADD_CLASS = 'Do not add infoblock';

// BBcodes
const NETCAT_BBCODE_SIZE = 'Font size';
const NETCAT_BBCODE_COLOR = 'Font color';
const NETCAT_BBCODE_SMILE = 'Smiles';
const NETCAT_BBCODE_B = 'Bold';
const NETCAT_BBCODE_I = 'Italic';
const NETCAT_BBCODE_U = 'Underline';
const NETCAT_BBCODE_S = 'Strike';
const NETCAT_BBCODE_LIST = 'List element';
const NETCAT_BBCODE_QUOTE = 'Quote';
const NETCAT_BBCODE_CODE = 'Code';
const NETCAT_BBCODE_IMG = 'Picture';
const NETCAT_BBCODE_URL = 'Link';
const NETCAT_BBCODE_CUT = 'Cut text in listing';

const NETCAT_BBCODE_QUOTE_USER = 'wrote';
const NETCAT_BBCODE_CUT_MORE = 'read more';
const NETCAT_BBCODE_SIZE_DEF = 'Size';
const NETCAT_BBCODE_ERROR_1 = 'wrong format BBcode:';
const NETCAT_BBCODE_ERROR_2 = 'wrong format BBcodes:';

// Help messages for BBcode
const NETCAT_BBCODE_HELP_SIZE = 'Font size: [SIZE=8]small text[/SIZE]';
const NETCAT_BBCODE_HELP_COLOR = 'Font color: [COLOR=FF0000]text[/COLOR]';
const NETCAT_BBCODE_HELP_SMILE = 'Insert smile';
const NETCAT_BBCODE_HELP_B = 'Bold text: [B]text[/B]';
const NETCAT_BBCODE_HELP_I = 'Italic text: [I]text[/I]';
const NETCAT_BBCODE_HELP_U = 'Underline text: [U]text[/U]';
const NETCAT_BBCODE_HELP_S = 'Strike text: [S]text[/S]';
const NETCAT_BBCODE_HELP_LIST = 'List element: [LIST]text[/LIST]';
const NETCAT_BBCODE_HELP_QUOTE = 'Quote text: [QUOTE]text[/QUOTE]';
const NETCAT_BBCODE_HELP_CODE = 'Code display: [CODE]code[/CODE]';
const NETCAT_BBCODE_HELP_URL = 'Insert link';
const NETCAT_BBCODE_HELP_URL_URL = 'URL';
const NETCAT_BBCODE_HELP_URL_DESC = 'Description';
const NETCAT_BBCODE_HELP_IMG = 'Insert image';
const NETCAT_BBCODE_HELP_IMG_URL = 'Image URL';
const NETCAT_BBCODE_HELP_CUT = 'Cut big text in listing: [CUT=more]text[/CUT]';
const NETCAT_BBCODE_HELP = 'Tip: Styles can be applied quickly to selected text';

// Smiles
const NETCAT_SMILE_SMILE = 'smile';
const NETCAT_SMILE_BIGSMILE = 'big smile';
const NETCAT_SMILE_GRIN = 'grin';
const NETCAT_SMILE_LAUGH = 'laugh';
const NETCAT_SMILE_PROUD = 'proud';

//
const NETCAT_SMILE_YES = 'yes';
const NETCAT_SMILE_WINK = 'winked';
const NETCAT_SMILE_COOL = 'cool';
const NETCAT_SMILE_ROLLEYES = 'innocent';
const NETCAT_SMILE_LOOKDOWN = 'shame';

//
const NETCAT_SMILE_SAD = 'sad';
const NETCAT_SMILE_SUSPICIOUS = 'suspicious';
const NETCAT_SMILE_ANGRY = 'angry';
const NETCAT_SMILE_SHAKEFIST = 'threaten';
const NETCAT_SMILE_STERN = 'stern';

//
const NETCAT_SMILE_KISS = 'kiss';
const NETCAT_SMILE_THINK = 'think';
const NETCAT_SMILE_THUMBSUP = 'thumbs up';
const NETCAT_SMILE_SICK = 'sick';
const NETCAT_SMILE_NO = 'no';

//
const NETCAT_SMILE_CANTLOOK = "can't look";
const NETCAT_SMILE_DOH = 'ooo';
const NETCAT_SMILE_KNOCKEDOUT = 'knocked out';
const NETCAT_SMILE_EYEUP = 'eye up';
const NETCAT_SMILE_QUIET = 'quiet';

//
const NETCAT_SMILE_EVIL = 'evil';
const NETCAT_SMILE_UPSET = 'upset';
const NETCAT_SMILE_UNDECIDED = 'undecided';
const NETCAT_SMILE_CRY = 'cry';
const NETCAT_SMILE_UNSURE = 'unsure';

// nc_bytes2size
const NETCAT_SIZE_BYTES = ' byte';
const NETCAT_SIZE_KBYTES = ' KB';
const NETCAT_SIZE_MBYTES = ' MB';
const NETCAT_SIZE_GBYTES = ' GB';

// QuickBar
const NETCAT_QUICKBAR_BUTTON_VIEWMODE = 'View mode';
const NETCAT_QUICKBAR_BUTTON_EDITMODE = 'Edit mode';
const NETCAT_QUICKBAR_BUTTON_EDITMODE_UNAVAILABLE_FOR_LONGPAGE = 'Edit mode unavailable in longpage';
const NETCAT_QUICKBAR_BUTTON_MORE = 'more';
const NETCAT_QUICKBAR_BUTTON_OBJECT_SETTINGS = '%s properties';
const NETCAT_QUICKBAR_BUTTON_SUBDIVISION_SETTINGS = 'Subdivision settings';
const NETCAT_QUICKBAR_BUTTON_SUBDIVISION_VERSIONS = 'Page versions';
const NETCAT_QUICKBAR_BUTTON_TEMPLATE_SETTINGS = 'Template settings';
const NETCAT_QUICKBAR_BUTTON_ADMIN = 'Admin mode';
const NETCAT_QUICKBAR_BUTTON_CONTROL_PANEL = 'Control panel';
const NETCAT_QUICKBAR_BUTTON_DESIGN_MODE = 'Design mode';


// SYNTAX EDITOR
const NETCAT_SETTINGS_SYNTAXEDITOR = 'Online syntax highlighting';
const NETCAT_SETTINGS_SYNTAXEDITOR_ENABLE = 'Enable system syntax highlighting (need frame reload Ctrl + F5)';

// LICENSE
const NETCAT_SETTINGS_LICENSE = 'License';
const NETCAT_SETTINGS_LICENSE_PRODUCT = 'Product number';
const NETCAT_SETTINGS_LICENSE_CODE = 'Code';

// NETCAT_DEBUG
const NETCAT_DEBUG_ERROR_INSTRING = 'Error in string : ';
const NETCAT_DEBUG_BUTTON_CAPTION = 'Debug';

// NETCAT_PREVIEW
const NETCAT_PREVIEW_BUTTON_CAPTIONCLASS = 'Class preview';
const NETCAT_PREVIEW_BUTTON_CAPTIONTEMPLATE = 'Template preview';

const NETCAT_PREVIEW_BUTTON_CAPTIONADDFORM = 'Add Form preview ';
const NETCAT_PREVIEW_BUTTON_CAPTIONEDITFORM = 'Edit Form preview';
const NETCAT_PREVIEW_BUTTON_CAPTIONSEARCHFORM = 'Search Form preview';

const NETCAT_PREVIEW_ERROR_NODATA = 'Error ! There are no data for generating preview mode or data too old';
const NETCAT_PREVIEW_ERROR_WRONGDATA = 'Error in preview data';
const NETCAT_PREVIEW_ERROR_NOSUB = ' There is no any subdivision with such class. Add at least one and preview mode will be available.';
const NETCAT_PREVIEW_ERROR_NOMESSAGE = ' There is no any object of such class. Add at least one object and preview mode will be available.';
const NETCAT_PREVIEW_INFO_MORESUB = ' There are some subdivisions with such class. Please choose one.';
const NETCAT_PREVIEW_INFO_CHOOSESUB = ' Select to preview the layout.';

// Objects
const NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_SUPERVISOR = "Error in SQL query into the nc_objects_list(%s, %s, \"%s\") function, %s";
const NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_USER = 'Error into the objects list function.';
const NETCAT_FUNCTION_OBJECTS_LIST_CLASSIFICATOR_ERROR = "Classificator \"%s\" does't exist";
const NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_UNKNOWN = "unknown column \"%s\" in field list";
const NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_CLAUSE = "unknown column \"%s\" in order clause";
const NETCAT_FUNCTION_OBJECTS_LIST_CC_ERROR = "Wrong parameter \$cc into the nc_objects_list(XX, %s, \"...\") function";
const NETCAT_FUNCTION_LISTCLASSVARS_ERROR_SUPERVISOR = "Wrong parameter \$cc into the ListClassVars(%s) function";
const NETCAT_FUNCTION_FULL_SQL_ERROR_USER = 'Error in the function of the full object display .';

// Events

// widgets events
const NETCAT_TOKEN_INVALID = 'Invalid confirmation key';

// Подсказки в сплывающах окнах
const NETCAT_HINT_COMPONENT_FIELD = 'Fields of the component';
const NETCAT_HINT_COMPONENT_SIZE = 'Size';
const NETCAT_HINT_COMPONENT_TYPE = 'Type';
const NETCAT_HINT_COMPONENT_ID = 'ID';
const NETCAT_HINT_COMPONENT_DAY = 'The numerical value of the day';
const NETCAT_HINT_COMPONENT_MONTH = 'The numerical value of the month';
const NETCAT_HINT_COMPONENT_YEAR = 'The numerical value of the year';
const NETCAT_HINT_COMPONENT_HOUR = 'The numerical value of the hour';
const NETCAT_HINT_COMPONENT_MINUTE = 'The numerical value of the minute';
const NETCAT_HINT_COMPONENT_SECONDS = 'The numerical value of the second';
const NETCAT_HINT_OBJECT_PARAMS = 'Variables that containing properties of the current object';
const NETCAT_HINT_CREATED_DESC = 'details of time adding the object format &laquo;yyyy-mm-dd hh:mm:ss&raquo;';
const NETCAT_HINT_LASTUPDATED_DESC = 'details of the last change of the object in the format &laquo;yyyymmddhhmmss&raquo;';
const NETCAT_HINT_MESSAGE_ID = 'ID of the object';
const NETCAT_HINT_USER_ID = 'ID of the user who added the object';
const NETCAT_HINT_CHECKED = 'switched on or off site (0/1)';
const NETCAT_HINT_IP = 'IP-address of the user who added the object';
const NETCAT_HINT_USER_AGENT = "value of the variable \$ HTTP_USER_AGENT for the user, who added an object";
const NETCAT_HINT_LAST_USER_ID = 'ID of the last user who modified the object';
const NETCAT_HINT_LAST_USER_IP = 'IP-address of the user who last changed the object';
const NETCAT_HINT_LAST_USER_AGENT = "value of the variable \$ HTTP_USER_AGENT for the last user, who changed the object";
const NETCAT_HINT_ADMIN_BUTTONS = 'in administrative mode block contains status information about the record and links to action for this record&laquo;change&raquo;, &laquo;delete&raquo;, &laquo;turn on / off&raquo; (only in the field &laquo;Object list&raquo;)';
const NETCAT_HINT_ADMIN_COMMONS = 'in administrative mode block contains status information about the template and add link  to an object in the template section and remove all objects from the same template (only in the &laquo;Object in list&raquo;)';
const NETCAT_HINT_FULL_LINK = 'link to layout the complete withdrawal of this record';
const NETCAT_HINT_FULL_DATE_LINK = "link to layout the full withdrawal from the date in the form of &laquo;.../2002/02/02/message_2.html &raquo;(installed if the template has a field type &laquo; Date and Time format &laquo;event&raquo;, otherwise variable is identical to the value of \$fullLink)";
const NETCAT_HINT_EDIT_LINK = 'link to edit an object';
const NETCAT_HINT_DELETE_LINK = 'link to remove an object';
const NETCAT_HINT_DROP_LINK = 'link to delete an object without asking';
const NETCAT_HINT_CHECKED_LINK = 'link to the on/off an object';
const NETCAT_HINT_PREV_LINK = 'link to the previous page in Listing template (if the current position in the list - its beginning, then the variable is empty)';
const NETCAT_HINT_NEXT_LINK = 'link to the next page in Listing template (if the current position in the list - its beginning, then the variable is empty)';
const NETCAT_HINT_ROW_NUM = 'record number in the order listed on the current page';
const NETCAT_HINT_REC_NUM = 'maximum number of entries displayed in the list';
const NETCAT_HINT_TOT_ROWS = 'the total number of entries in the list';
const NETCAT_HINT_BEG_ROW = 'record number (in order), which begins listing the list on this page';
const NETCAT_HINT_END_ROW = 'record number (in order), which ends with a list of listing on this page';
const NETCAT_HINT_ADMIN_MODE = 'true if the user is in the administrative mode';
const NETCAT_HINT_SUB_HOST = 'address the current domain as &laquo;www.example.com&raquo;';
const NETCAT_HINT_SUB_LINK = 'path to the current sub as &laquo;/about/pr/&raquo;';
const NETCAT_HINT_CC_LINK = 'link for the current component in the sub as &laquo;news.html&raquo;';
const NETCAT_HINT_CATALOGUE_ID = 'Number of the current directory';
const NETCAT_HINT_SUB_ID = 'Number of the current sub';
const NETCAT_HINT_CC_ID = 'Number of the current component in the sub';
const NETCAT_HINT_CURRENT_CATALOGUE = 'Contain the property values of the current directory';
const NETCAT_HINT_CURRENT_SUB = 'Contain the property values of the current sub';
const NETCAT_HINT_CURRENT_CC = 'It contains the property values of the current component in the sub';
const NETCAT_HINT_CURRENT_USER = 'Contain the property values of the current authorized user.';
const NETCAT_HINT_IS_EVEN = 'Checks the value parity';
const NETCAT_HINT_OPT = "Function opt() prints \$string if \$flag - true";
const NETCAT_HINT_OPT_CAES = "Function opt_case() prints \$string1, if \$flag true, and \$string2, if \$flag false";
const NETCAT_HINT_LIST_QUERY = "The function is intended to perform SQL-query \$sql_query. To request a type of SELECT (or for other cases, invented by the developer) is used \$output_template to display the results of sampling. \$output_template is optional. <br /> Last parameter should contain a call to a hash array \$data, indices of which correspond to the table fields (dollar sign and double quotes must be escaped). \$divider to split the results output.";
define("NETCAT_HINT_NC_LIST_SELECT",
    "This function allows you to generate HTML lists of " . CMS_SYSTEM_NAME . " lists");
const NETCAT_HINT_NC_MESSAGE_LINK = 'This function allows you to get the relative path to the object (without domain) by number (ID) of the site and number (ID) component, to which he belongs';
const NETCAT_HINT_NC_FILE_PATH = 'This function allows you to get the file path specified in a particular field, by number (ID) of the site and number (ID) component, to which he belongs';
const NETCAT_HINT_BROWSE_MESSAGE = 'The function displays a listing of pages';
const NETCAT_HINT_NC_OBJECTS_LIST = "Displays contents of the component in the section \$cc partition \$sub with parameters \$params as parameters, fed to the scripts in the URL line";
const NETCAT_HINT_RTFM = "All available variables and functions can be found in Developer's Guide.";
const NETCAT_HINT_FUNCTION = 'Functions.';
const NETCAT_HINT_ARRAY = 'Hash-arrays';
const NETCAT_HINT_VARS_IN_COMPONENT_SCOPE = 'Variables are available in all fields';
const NETCAT_HINT_VARS_IN_LIST_SCOPE = 'Variables available in the object list template';
const NETCAT_HINT_FIELD_D = 'DD';
const NETCAT_HINT_FIELD_M = 'MM';
const NETCAT_HINT_FIELD_Y = 'YYYY';
const NETCAT_HINT_FIELD_H = 'hh';
const NETCAT_HINT_FIELD_MIN = 'mm';
const NETCAT_HINT_FIELD_S = 'ss';

const NETCAT_CUSTOM_ERROR_REQUIRED_INT = 'Enter a integer';
const NETCAT_CUSTOM_ERROR_REQUIRED_FLOAT = 'Enter a float';
const NETCAT_CUSTOM_ERROR_MIN_VALUE = 'Min value: %s.';
const NETCAT_CUSTOM_ERROR_MAX_VALUE = 'Max value: %s.';
const NETCAT_CUSTOM_PARAMETR_UPDATED = 'Settings updated';
const NETCAT_CUSTOM_PARAMETR_ADDED = 'Parameter added';
const NETCAT_CUSTOM_KEY = 'key';
const NETCAT_CUSTOM_VALUE = 'value';
const NETCAT_CUSTOM_USETTINGS = 'Custom settings';
const NETCAT_CUSTOM_NONE_SETTINGS = 'None';
const NETCAT_CUSTOM_ONCE_MAIN_SETTINGS = 'Main settings';
const NETCAT_CUSTOM_ONCE_FIELD_NAME = 'Field name';
const NETCAT_CUSTOM_ONCE_FIELD_DESC = 'Description';
const NETCAT_CUSTOM_ONCE_DEFAULT = 'Default value (when field is not filled';
const NETCAT_CUSTOM_ONCE_DONT_SHOW = "don't display in the settings edit form";
const NETCAT_CUSTOM_ONCE_FIELD_INITIAL_VALUE_INFOBLOCK = 'Initial value after infoblock creation';
const NETCAT_CUSTOM_ONCE_FIELD_INITIAL_VALUE_SUBDIVISION = 'Initial value after subdivision creation';
const NETCAT_CUSTOM_ONCE_EXTEND = 'Extend parameters';
const NETCAT_CUSTOM_ONCE_EXTEND_REGEXP = 'Regular expression';
const NETCAT_CUSTOM_ONCE_EXTEND_ERROR = 'Error message';
const NETCAT_CUSTOM_ONCE_EXTEND_SIZE_L = 'Input field width';
const NETCAT_CUSTOM_ONCE_EXTEND_RESIZE_W = 'Automatically resize field width';
const NETCAT_CUSTOM_ONCE_EXTEND_RESIZE_H = 'Automatically resize field height';
const NETCAT_CUSTOM_ONCE_EXTEND_VIZRED = 'Allow WYSIWYG editor';
const NETCAT_CUSTOM_ONCE_EXTEND_BR = 'Line break - &lt;br>';
const NETCAT_CUSTOM_ONCE_EXTEND_SIZE_H = 'Input field height';
const NETCAT_CUSTOM_ONCE_SAVE = 'Save';
const NETCAT_CUSTOM_ONCE_ADD = 'Add';
const NETCAT_CUSTOM_ONCE_DROP = 'Delete';
const NETCAT_CUSTOM_ONCE_DROP_SELECTED = 'Delete selected';
const NETCAT_CUSTOM_ONCE_MANUAL_EDIT = 'Manual edit';
const NETCAT_CUSTOM_ONCE_ERROR_FIELD_NAME = 'Field name can contain only latin characters';
const NETCAT_CUSTOM_ONCE_ERROR_CAPTION = 'Filed description';
const NETCAT_CUSTOM_ONCE_ERROR_FIELD_EXISTS = 'Such parameter already exists';
const NETCAT_CUSTOM_ONCE_ERROR_QUERY = 'SQL error';
const NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR = "Classificator %s doesn't exist";
const NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR_EMPTY = 'Classificator %s is empty';
const NETCAT_CUSTOM_TYPE = 'Type';
const NETCAT_CUSTOM_SUBTYPE = 'Subtype';
const NETCAT_CUSTOM_EX_MIN = 'Min value';
const NETCAT_CUSTOM_EX_MAX = 'Max value';
const NETCAT_CUSTOM_EX_QUERY = 'SQL query';
const NETCAT_CUSTOM_EX_CLASSIFICATOR = 'List';
const NETCAT_CUSTOM_EX_ELEMENTS = 'Elements';
const NETCAT_CUSTOM_TYPENAME_STRING = 'String';
const NETCAT_CUSTOM_TYPENAME_TEXTAREA = 'Text';
const NETCAT_CUSTOM_TYPENAME_CHECKBOX = 'Boolean';
const NETCAT_CUSTOM_TYPENAME_SELECT = 'List';
const NETCAT_CUSTOM_TYPENAME_SELECT_SQL = 'Dynamic';
const NETCAT_CUSTOM_TYPENAME_SELECT_STATIC = 'Static';
const NETCAT_CUSTOM_TYPENAME_SELECT_CLASSIFICATOR = 'Classificator';
const NETCAT_CUSTOM_TYPENAME_DIVIDER = 'Divider';
const NETCAT_CUSTOM_TYPENAME_INT = 'Integer';
const NETCAT_CUSTOM_TYPENAME_FLOAT = 'Float';
const NETCAT_CUSTOM_TYPENAME_DATETIME = 'Date and time';
const NETCAT_CUSTOM_TYPENAME_REL = 'Relation';
const NETCAT_CUSTOM_TYPENAME_REL_SUB = 'Relation with subdivision';
const NETCAT_CUSTOM_TYPENAME_REL_CC = 'Relation with subclass';
const NETCAT_CUSTOM_TYPENAME_REL_USER = 'Relation with user';
const NETCAT_CUSTOM_TYPENAME_REL_CLASS = 'Relation with component';
const NETCAT_CUSTOM_TYPENAME_FILE = 'File';
const NETCAT_CUSTOM_TYPENAME_FILE_ANY = 'File';
const NETCAT_CUSTOM_TYPENAME_FILE_IMAGE = 'Image';
const NETCAT_CUSTOM_TYPENAME_FILE_ICON = 'Icon';
const NETCAT_CUSTOM_TYPENAME_COLOR = 'Color';
const NETCAT_CUSTOM_TYPENAME_CUSTOM = 'HTML block';

#exceptions
const NETCAT_EXCEPTION_CLASS_DOESNT_EXIST = 'Component %s not found';
#errors
const NETCAT_ERROR_SQL = 'Error in SQL-query.<br/>Query:%s<br/>Error:%s';
const NETCAT_ERROR_DB_CONNECT = 'Fatal error: cannot retrieve system settings. Please check database settings in configuration file.';
const NETCAT_ERROR_UNABLE_TO_DELETE_FILES = 'Unable to delete a file or directory %s';

#openstat

// admin notice
const NETCAT_ADMIN_NOTICE_MORE = 'More.';
const NETCAT_ADMIN_NOTICE_PROLONG = 'Prolong.';
const NETCAT_ADMIN_NOTICE_LICENSE_ILLEGAL = 'This copy Netcat is not licensed.';
const NETCAT_ADMIN_NOTICE_LICENSE_MAYBE_ILLEGAL = 'You may have used unlicensed copy Netcat.';
const NETCAT_ADMIN_NOTICE_SECURITY_UPDATE_SYSTEM = 'Came an important security update system.';
const NETCAT_ADMIN_NOTICE_SUPPORT_EXPIRED = 'Support for license %s expired.';
define("NETCAT_ADMIN_NOTICE_CRON", "You have not used the \"CronTasking\"." . (CMS_SYSTEM_NAME === 'Netcat' ?
        " <a href='https://netcat.ru/developers/docs/system-tools/task-management/' target='_blank'>More</a>" : ''));
const NETCAT_ADMIN_NOTICE_RIGHTS = 'Directory permissions are wrong';
define("NETCAT_ADMIN_NOTICE_SAFE_MODE", "Option php safe_mode is active." .
    (CMS_SYSTEM_NAME === 'Netcat' ? " <a href='https://netcat.ru/adminhelp/safe-mode/' target='_blank'>More</a>" : ''));
const NETCAT_ADMIN_DOMDocument_NOT_FOUND = 'DOMDocument PHP extension is not found, can not work basket';
const NETCAT_ADMIN_TRASH_OBJECT_HAS_BEEN_REMOVED = 'object has been deleted';
const NETCAT_ADMIN_TRASH_OBJECTS_REMOVED = 'objects removed';
const NETCAT_ADMIN_TRASH_OBJECT_IS_REMOVED = 'object is removed';
const NETCAT_ADMIN_TRASH_TRASH_HAS_BEEN_SUCCESSFULLY_CLEARNED = 'Cart has been successfully cleared';

const NETCAT_FILE_NOT_FOUND = 'File %s not found';
const NETCAT_DIR_NOT_FOUND = 'Dir %s not found';

const NETCAT_TEMPLATE_FILE_NOT_FOUND = 'Template file not found';
const NETCAT_TEMPLATE_DIR_DELETE_ERROR = 'It is not possible to delete %s';
const NETCAT_CAN_NOT_WRITE_FILE = "Can't write file";
const NETCAT_CAN_NOT_CREATE_FOLDER = "Can't create folder";


const NETCAT_ADMIN_AUTH_PERM = 'Role:';
const NETCAT_ADMIN_AUTH_CHANGE_PASS = 'Change password';
const NETCAT_ADMIN_AUTH_LOGOUT = 'Logout';

const CONTROL_BUTTON_CANCEL = 'Cancel';

const NETCAT_MESSAGE_FORM_MAIN = 'Main settings';
const NETCAT_MESSAGE_FORM_ADDITIONAL = 'Additional settings';
const NETCAT_EVENT_IMPORTCATALOGUE = 'Importing a site';
const NETCAT_EVENT_ADDCATALOGUE = 'Adding a site';
const NETCAT_EVENT_ADDSUBDIVISION = 'Adding a subdivision';
const NETCAT_EVENT_ADDSUBCLASS = 'Adding a component into the subdivision';
const NETCAT_EVENT_ADDCLASS = 'Adding a component';
const NETCAT_EVENT_ADDCLASSTEMPLATE = 'Adding a components template';
const NETCAT_EVENT_ADDMESSAGE = 'Adding a message';
const NETCAT_EVENT_ADDSYSTEMTABLE = 'Adding a system table';
const NETCAT_EVENT_ADDTEMPLATE = 'Adding a template';
const NETCAT_EVENT_ADDUSER = 'Adding a user';
const NETCAT_EVENT_ADDCOMMENT = 'Adding comment';
const NETCAT_EVENT_UPDATECATALOGUE = 'Updating a site';
const NETCAT_EVENT_UPDATESUBDIVISION = 'Updating a subdivision';
const NETCAT_EVENT_UPDATESUBCLASS = 'Updating a component into the subdivision';
const NETCAT_EVENT_UPDATECLASS = 'Updating a component';
const NETCAT_EVENT_UPDATECLASSTEMPLATE = 'Updating a components template';
const NETCAT_EVENT_UPDATEMESSAGE = 'Updating a message';
const NETCAT_EVENT_UPDATESYSTEMTABLE = 'Updating a system table';
const NETCAT_EVENT_UPDATETEMPLATE = 'Updating a template';
const NETCAT_EVENT_UPDATEUSER = 'Updating a user';
const NETCAT_EVENT_UPDATECOMMENT = 'Updating a comment';
const NETCAT_EVENT_DROPCATALOGUE = 'Deleting a site';
const NETCAT_EVENT_DROPSUBDIVISION = 'Deleting a subdivision';
const NETCAT_EVENT_DROPSUBCLASS = 'Deleting an infoblock';
const NETCAT_EVENT_DROPCLASS = 'Deleting a component';
const NETCAT_EVENT_DROPCLASSTEMPLATE = 'Deleting a components template';
const NETCAT_EVENT_DROPMESSAGE = 'Deleting a message';
const NETCAT_EVENT_DROPSYSTEMTABLE = 'Deleting a system table';
const NETCAT_EVENT_DROPTEMPLATE = 'Deleting a template';
const NETCAT_EVENT_DROPUSER = 'Deleting a user';
const NETCAT_EVENT_DROPCOMMENT = 'Deleting a comment';
const NETCAT_EVENT_CHECKCOMMENT = 'On comment';
const NETCAT_EVENT_UNCHECKCOMMENT = 'Off comment';
const NETCAT_EVENT_CHECKMESSAGE = 'On object';
const NETCAT_EVENT_UNCHECKMESSAGE = 'Off object';
const NETCAT_EVENT_CHECKUSER = 'On user';
const NETCAT_EVENT_UNCHECKUSER = 'Off user';
const NETCAT_EVENT_CHECKSUBDIVISION = 'On sub';
const NETCAT_EVENT_UNCHECKSUBDIVISION = 'Off sub';
const NETCAT_EVENT_CHECKCATALOGUE = 'On site';
const NETCAT_EVENT_UNCHECKCATALOGUE = 'Off site';
const NETCAT_EVENT_CHECKSUBCLASS = 'On component in sub';
const NETCAT_EVENT_UNCHECKSUBCLASS = 'Off component in sub';
const NETCAT_EVENT_CHECKMODULE = 'On module';
const NETCAT_EVENT_UNCHECKMODULE = 'Off module';
const NETCAT_EVENT_AUTHORIZEUSER = 'User authorization';
const NETCAT_EVENT_ADDWIDGETCLASS = 'Add widget-class';
const NETCAT_EVENT_EDITWIDGETCLASS = 'Edit widget-class';
const NETCAT_EVENT_DROPWIDGETCLASS = 'Drop widget-class';
const NETCAT_EVENT_ADDWIDGET = 'Add widget';
const NETCAT_EVENT_EDITWIDGET = 'Edit widget';
const NETCAT_EVENT_DROPWIDGET = 'Delete widget';
const NETCAT_EVENT_DROPFIELD = 'Delete field';
const NETCAT_EVENT_ADDFIELD = 'Add field';
const NETCAT_EVENT_UPDATEFIELD = 'Edit field';
const NETCAT_EVENT_DROPCLASSIFICATOR = 'Delete classificator';
const NETCAT_EVENT_ADDCLASSIFICATOR = 'Add classificator';
const NETCAT_EVENT_UPDATECLASSIFICATOR = 'Edit classificator';
const NETCAT_EVENT_DROPCLASSIFICATORITEM = 'Delete item classificator';
const NETCAT_EVENT_ADDCLASSIFICATORITEM = 'Add item classificator';
const NETCAT_EVENT_UPDATECLASSIFICATORITEM = 'Edit item classificator';
const NETCAT_EVENT_DROPTEMPLATEPARTIAL = 'Delete partial template';
const NETCAT_EVENT_ADDTEMPLATEPARTIAL = 'Add partial template';
const NETCAT_EVENT_UPDATETEMPLATEPARTIAL = 'Edit partial template';
const NETCAT_EVENT_BLOCKUSER = 'Block user';
const NETCAT_EVENT_UNBLOCKUSER = 'Unblock user';

const NETCAT_EVENT_IMPORTCATALOGUEPREP = 'Importing a site: preparation';
const NETCAT_EVENT_ADDCATALOGUEPREP = 'Adding a site: preparation';
const NETCAT_EVENT_ADDSUBDIVISIONPREP = 'Adding a subdivision: preparation';
const NETCAT_EVENT_ADDSUBCLASSPREP = 'Adding a component into the subdivision: preparation';
const NETCAT_EVENT_ADDCLASSPREP = 'Adding a component: preparation';
const NETCAT_EVENT_ADDCLASSTEMPLATEPREP = 'Adding a components template: preparation';
const NETCAT_EVENT_ADDMESSAGEPREP = 'Adding a message: preparation';
const NETCAT_EVENT_ADDSYSTEMTABLEPREP = 'Adding a system table: preparation';
const NETCAT_EVENT_ADDTEMPLATEPREP = 'Adding a template: preparation';
const NETCAT_EVENT_ADDUSERPREP = 'Adding a user: preparation';
const NETCAT_EVENT_ADDCOMMENTPREP = 'Adding comment: preparation';
const NETCAT_EVENT_UPDATECATALOGUEPREP = 'Updating a site: preparation';
const NETCAT_EVENT_UPDATESUBDIVISIONPREP = 'Updating a subdivision: preparation';
const NETCAT_EVENT_UPDATESUBCLASSPREP = 'Updating a component into the subdivision: preparation';
const NETCAT_EVENT_UPDATECLASSPREP = 'Updating a component: preparation';
const NETCAT_EVENT_UPDATECLASSTEMPLATEPREP = 'Updating a components template: preparation';
const NETCAT_EVENT_UPDATEMESSAGEPREP = 'Updating a message: preparation';
const NETCAT_EVENT_UPDATESYSTEMTABLEPREP = 'Updating a system table: preparation';
const NETCAT_EVENT_UPDATETEMPLATEPREP = 'Updating a template: preparation';
const NETCAT_EVENT_UPDATEUSERPREP = 'Updating a user: preparation';
const NETCAT_EVENT_UPDATECOMMENTPREP = 'Updating a comment: preparation';
const NETCAT_EVENT_DROPCATALOGUEPREP = 'Deleting a site: preparation';
const NETCAT_EVENT_DROPSUBDIVISIONPREP = 'Deleting a subdivision: preparation';
const NETCAT_EVENT_DROPSUBCLASSPREP = 'Deleting a component from the subdivision: preparation';
const NETCAT_EVENT_DROPCLASSPREP = 'Deleting a component: preparation';
const NETCAT_EVENT_DROPCLASSTEMPLATEPREP = 'Deleting a components template: preparation';
const NETCAT_EVENT_DROPMESSAGEPREP = 'Deleting a message: preparation';
const NETCAT_EVENT_DROPSYSTEMTABLEPREP = 'Deleting a system table: preparation';
const NETCAT_EVENT_DROPTEMPLATEPREP = 'Deleting a template: preparation';
const NETCAT_EVENT_DROPUSERPREP = 'Deleting a user: preparation';
const NETCAT_EVENT_DROPCOMMENTPREP = 'Deleting a comment: preparation';
const NETCAT_EVENT_CHECKCOMMENTPREP = 'On comment: preparation';
const NETCAT_EVENT_UNCHECKCOMMENTPREP = 'Off comment: preparation';
const NETCAT_EVENT_CHECKMESSAGEPREP = 'On object: preparation';
const NETCAT_EVENT_UNCHECKMESSAGEPREP = 'Off object: preparation';
const NETCAT_EVENT_CHECKUSERPREP = 'On user: preparation';
const NETCAT_EVENT_UNCHECKUSERPREP = 'Off user: preparation';
const NETCAT_EVENT_CHECKSUBDIVISIONPREP = 'On sub: preparation';
const NETCAT_EVENT_UNCHECKSUBDIVISIONPREP = 'Off sub: preparation';
const NETCAT_EVENT_CHECKCATALOGUEPREP = 'On site: preparation';
const NETCAT_EVENT_UNCHECKCATALOGUEPREP = 'Off site: preparation';
const NETCAT_EVENT_CHECKSUBCLASSPREP = 'On component in sub: preparation';
const NETCAT_EVENT_UNCHECKSUBCLASSPREP = 'Off component in sub: preparation';
const NETCAT_EVENT_CHECKMODULEPREP = 'On module: preparation';
const NETCAT_EVENT_UNCHECKMODULEPREP = 'Off module: preparation';
const NETCAT_EVENT_AUTHORIZEUSERPREP = 'User authorization: preparation';
const NETCAT_EVENT_ADDWIDGETCLASSPREP = 'Add widget-class: preparation';
const NETCAT_EVENT_EDITWIDGETCLASSPREP = 'Edit widget-class: preparation';
const NETCAT_EVENT_DROPWIDGETCLASSPREP = 'Drop widget-class: preparation';
const NETCAT_EVENT_ADDWIDGETPREP = 'Add widget: preparation';
const NETCAT_EVENT_EDITWIDGETPREP = 'Edit widget: preparation';
const NETCAT_EVENT_DROPWIDGETPREP = 'Delete widget: preparation';
const NETCAT_EVENT_DROPFIELDPREP = 'Delete field: preparation';
const NETCAT_EVENT_ADDFIELDPREP = 'Add field: preparation';
const NETCAT_EVENT_UPDATEFIELDPREP = 'Edit field: preparation';
const NETCAT_EVENT_DROPCLASSIFICATORPREP = 'Delete classificator: preparation';
const NETCAT_EVENT_ADDCLASSIFICATORPREP = 'Add classificator: preparation';
const NETCAT_EVENT_UPDATECLASSIFICATORPREP = 'Edit classificator: preparation';
const NETCAT_EVENT_DROPCLASSIFICATORITEMPREP = 'Delete item classificator: preparation';
const NETCAT_EVENT_ADDCLASSIFICATORITEMPREP = 'Add item classificator: preparation';
const NETCAT_EVENT_UPDATECLASSIFICATORITEMPREP = 'Edit item classificator: preparation';
const NETCAT_EVENT_DROPTEMPLATEPARTIALPREP = 'Delete partial template: preparation';
const NETCAT_EVENT_ADDTEMPLATEPARTIALPREP = 'Add partial template: preparation';
const NETCAT_EVENT_UPDATETEMPLATEPARTIALPREP = 'Edit partial template: preparation';
const NETCAT_EVENT_BLOCKUSERPREP = 'Block user: preparation';
const NETCAT_EVENT_UNBLOCKUSERPREP = 'Unblock user: preparation';

const TITLE_WEB = 'Web template';
const TITLE_MOBILE = 'Mobile template';
const TITLE_RESPONSIVE = 'Responsive template';
const TITLE_OLD = 'Web template v4';

const TOOLS_PATCH_INSTALL_ONLINE_NOTIFY = 'Before installing the update is strongly recommended that you back up your system. Run the update process?';
const TOOLS_PATCH_INFO_INSTALL = 'Install patch';
const TOOLS_PATCH_INFO_SYSTEM_MESSAGE = "New system message added, <a href='%LINK'>read message</a>.";
define("TOOLS_PATCH_ERROR_SET_FILEPERM_IN_HTTP_ROOT_PATH",
    "Set writing permissions for all files in $nc_core->HTTP_ROOT_PATH directory.<br><small>(%FILE not available for writing)</small>");
define("TOOLS_PATCH_ERROR_SET_DIRPERM_IN_HTTP_ROOT_PATH",
    "Set writing permissions for directory $nc_core->HTTP_ROOT_PATH and all subdirectories.<br><small>(%DIR not available for writing)</small>");
const TOOLS_PATCH_ERROR_UNCORRECT_PHP_VERSION = 'Next system version require PHP %NEED, at this time PHP %CURRENT.';

const SQL_CONSTRUCT_TITLE = 'Query builder';
const SQL_CONSTRUCT_CHOOSE_OP = 'Choose an action';
const SQL_CONSTRUCT_SELECT_TABLE = 'Select data from table';
const SQL_CONSTRUCT_SELECT_CC = 'Select data from component';
const SQL_CONSTRUCT_ENTER_CODE = 'Enter registry number';
const SQL_CONSTRUCT_VIEW_SETTINGS = 'View system settings';
const SQL_CONSTRUCT_TABLE_NAME = 'Table name';
const SQL_CONSTRUCT_FIELDS = 'Fields';
const SQL_CONSTRUCT_FIELDS_NOTE = '(optional, comma separated, no spaces)';
const SQL_CONSTRUCT_CC_ID = 'Cc ID';
const SQL_CONSTRUCT_REGNUM = 'License number';
const SQL_CONSTRUCT_REGCODE = 'Activation code';
const SQL_CONSTRUCT_CHOOSE_MOD = 'Select a module';
const SQL_CONSTRUCT_GENERATE = 'Generate query';

const NETCAT_MAIL_ATTACHMENT_FORM_ATTACHMENTS = 'Attachments:';
const NETCAT_MAIL_ATTACHMENT_FORM_DELETE = 'Delete';
const NETCAT_MAIL_ATTACHMENT_FORM_FILENAME = 'File title:';
const NETCAT_MAIL_ATTACHMENT_FORM_ADD = 'Add more';

const NETCAT_DATEPICKER_CALENDAR_DATE_FORMAT = 'dd.mm.yyyy';
const NETCAT_DATEPICKER_CALENDAR_DAYS = 'Sunday Monday Tuesday Wednesday Thursday Friday Saturday Sunday';
const NETCAT_DATEPICKER_CALENDAR_DAYS_SHORT = 'Sun Mon Tue Wed Thu Fri Sat Sun';
const NETCAT_DATEPICKER_CALENDAR_DAYS_MIN = 'Su Mo Tu We Th Fr Sa Su';
const NETCAT_DATEPICKER_CALENDAR_MONTHS = 'January February March April May June July August September October November December';
const NETCAT_DATEPICKER_CALENDAR_MONTHS_SHORT = 'Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec';
const NETCAT_DATEPICKER_CALENDAR_TODAY = 'Today';

const TOOLS_CSV = 'CSV Export/Import';
const TOOLS_CSV_EXPORT = 'Export CSV';
const TOOLS_CSV_IMPORT = 'Import CSV';
const TOOLS_CSV_EXPORT_TYPE = 'Export Source';
const TOOLS_CSV_EXPORT_TYPE_SUBCLASS = 'Data component';
const TOOLS_CSV_EXPORT_TYPE_COMPONENT = 'Component';
const TOOLS_CSV_SELECT_SITE = 'Select catalogue';
const TOOLS_CSV_SELECT_SUBDIVISION = 'Select subdivision';
const TOOLS_CSV_SELECT_SUBCLASS = 'Select subclass';
const TOOLS_CSV_SELECT_COMPONENT = 'Select data component';
const TOOLS_CSV_SUBCLASSES_NOT_FOUND = 'No suitable subclasses found';
const TOOLS_CSV_NOT_SELECTED = 'Not selected';
const TOOLS_CSV_CREATE_EXPORT = 'Export';
const TOOLS_CSV_RECORD_ID = 'Record identifier in the file';
const TOOLS_CSV_PARENT_RECORD_ID = 'Parent record identifier';

const TOOLS_CSV_SELECT_SETTINGS = 'CSV settings';

const TOOLS_CSV_OPT_ENCLOSED = 'Columns enclosed by';
const TOOLS_CSV_OPT_ESCAPED = 'Columns escaped by';
const TOOLS_CSV_OPT_SEPARATOR = 'Columns terminated by';
const TOOLS_CSV_OPT_CHARSET = 'Charset';
const TOOLS_CSV_OPT_CHARSET_UTF8 = 'Unicode (utf-8)';
const TOOLS_CSV_OPT_CHARSET_CP1251 = 'Microsoft Excel (windows-1251)';
const TOOLS_CSV_OPT_NULL = 'Replace NULL with';
const TOOLS_CSV_OPT_LISTS = '<nobr>Values from the classifier fields</nobr>';
const TOOLS_CSV_OPT_LISTS_NAME = 'element name';
const TOOLS_CSV_OPT_LISTS_VALUE = 'additional value (field.Value)';
const TOOLS_CSV_EXPORT_DONE = 'Export done. You can download file by link <a href="%s" target="_blank">%s</a>. To delete file click <a href="%s" target="_top">here</a>.';

const TOOLS_CSV_MAPPING_HEADER = 'Fields mapping';
const TOOLS_CSV_IMPORT_FILE = 'Import file (*.csv)';
const TOOLS_CSV_IMPORT_RUN = 'Import';
const TOOLS_CSV_IMPORT_FILE_NOT_FOUND = 'Import file not found';
const TOOLS_CSV_IMPORT_COLUMN_COUNT_MISMATCH = 'Line %d was not imported because of the incorrect column count (%d columns in the file header, %d columns in the skipped line).';

const TOOLS_CSV_COMPONENT_FIELD = 'Component field';
const TOOLS_CSV_FILE_FIELD = 'CSV-file field';
const TOOLS_CSV_FINISHED_HEADER = 'Import finished';
const TOOLS_CSV_EXPORT_FINISHED_HEADER = 'Export finished';
const TOOLS_CSV_IMPORT_SUCCESS = 'Import finished, rows added: ';
const TOOLS_CSV_DELETE_FINISHED_HEADER = 'File deleted';
const TOOLS_CSV_DELETE_FINISHED = 'File deleted successfully. <a href="%s" target="_top">Back to export</a>';
const TOOLS_CSV_IMPORT_HISTORY = 'Import history';
const TOOLS_CSV_HISTORY_ID = 'ID';
const TOOLS_CSV_HISTORY_CREATED = 'Created';
const TOOLS_CSV_HISTORY_ROLLBACK = 'Rollback';
const TOOLS_CSV_HISTORY_EMPTY = 'Import history is empty';
const TOOLS_CSV_HISTORY_CLASS_NAME = 'Component';
const TOOLS_CSV_HISTORY_ROWS = 'Rows';
const TOOLS_CSV_HISTORY_ROLLBACKED = 'Canceled';
const TOOLS_CSV_ROLLBACK_FINISHED_HEADER = 'Rollback finished';
const TOOLS_CSV_ROLLBACK_SUCCESS = 'Rollback finished successfully. Rows canceled: ';

const WELCOME_SCREEN_TOOLTIP_SUPPORT = 'In case of difficulty you can refer to the documentation or get a response from our technical support.';
const WELCOME_SCREEN_TOOLTIP_SIDEBAR = 'You can change main settings in the site management panel.';
const WELCOME_SCREEN_TOOLTIP_SIDEBAR_SUBS = 'The site consists of sections, which will be shown here when you <a href="#site.add">create a site</a>. Black sections follow the structure of the site and gray sections are not displayed on the site, but they have service purpose.';
const WELCOME_SCREEN_TOOLTIP_TRASH_WIDGET = 'To speed up your work you can customize the widgets. For example, in the "Trash bin" widget you can restore deleted objects.';
define('WELCOME_SCREEN_MODAL_TEXT', '<h2>Welcome to ' . CMS_SYSTEM_NAME . ' site management system!</h2>
    <p>For your convenience, we have assembled the most important operations on a separate page — <b>site management panel.</b> You can get to it by clicking on the name of your site in the "tree" on the left.</p>
    <p>Other site settings are made in the relevant sections of the administrative interface.</p>
    <p>Thank you very much for using our system and <b>good luck.</b></p>');
const WELCOME_SCREEN_BTN_START = 'Start working';

const NETCAT_FILTER_FIELD_MESSAGE_ID = 'Row ID';
const NETCAT_FILTER_FIELD_CREATED = 'Created';
const NETCAT_FILTER_FIELD_LAST_UPDATED = 'Last Updated';

const NETCAT_FIELD_VALUE_INHERITED_FROM_SUBDIVISION = 'Value is inherited from the subdivision &ldquo;%s&rdquo;';
const NETCAT_FIELD_VALUE_INHERITED_FROM_CATALOGUE = 'Value is inherited from the <a href="%s" target="_top">site properties</a>';
const NETCAT_FIELD_VALUE_INHERITED_FROM_TEMPLATE = 'Value is inherited from the template &ldquo;%s&rdquo;';
const NETCAT_FIELD_FILE_ICON_SELECT = 'Select';
const NETCAT_FIELD_FILE_ICON_ICON = 'icon';
const NETCAT_FIELD_FILE_ICON_OR = 'or';
const NETCAT_FIELD_FILE_ICON_FILE = 'file';

const NETCAT_USER_BREAK_ATTRIBUTE_NAMING_CONVENTION = 'Some of the attribute names are breaking <a href="https://www.w3.org/TR/html-markup/syntax.html#syntax-attributes" target="_blank">the naming convention</a> and were ignored: %s.';

const NETCAT_SECURITY_SETTINGS_SAVED = 'Settings saved';
const NETCAT_SECURITY_SETTINGS_USE_DEFAULT = 'use <a href="%s" target="_top">common settings</a>';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER = 'Incoming data filter';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE = 'Action on finding incoming data used for&nbsp;injection';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_DISABLED = 'disable (do not check incoming data)';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_LOG_ONLY = 'no action ';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_RELOAD_ESCAPE_INPUT = 'escape incoming parameter and reload the page';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_RELOAD_REMOVE_INPUT = 'reset incoming parameter and reload the page';
const NETCAT_SECURITY_SETTINGS_INPUT_FILTER_MODE_EXCEPTION = 'halt execution';

const NETCAT_SECURITY_FILTER_NO_TOKENIZER = 'PHP code will not be checked because <i>tokenizer</i> extension is disabled.';
const NETCAT_SECURITY_FILTER_EMAIL_ENABLED = 'Send email notification when filter is triggered';
const NETCAT_SECURITY_FILTER_SMS_ENABLED = 'Send phone notification when filter is triggered';
const NETCAT_SECURITY_FILTER_EMAIL_PLACEHOLDER = 'Email address';
const NETCAT_SECURITY_FILTER_SMS_PLACEHOLDER = 'Comma separated phones';
const NETCAT_SECURITY_FILTER_EMAIL_SUBJECT = 'incoming data alert';
const NETCAT_SECURITY_FILTER_EMAIL_PREFIX = 'Input filter was triggered on %s (%s).';
const NETCAT_SECURITY_FILTER_SMS_PREFIX = 'Input filter was triggered on %s.';
const NETCAT_SECURITY_FILTER_EMAIL_INPUT_VALUE = 'Incoming parameter value – %s';
const NETCAT_SECURITY_FILTER_EMAIL_CHECKED_STRING = 'String with unescaped input';
const NETCAT_SECURITY_FILTER_EMAIL_IP = 'Remote IP-address';
const NETCAT_SECURITY_FILTER_EMAIL_URL = 'Page URL';
const NETCAT_SECURITY_FILTER_EMAIL_REFERER = 'Referring page URL';
const NETCAT_SECURITY_FILTER_EMAIL_GET = 'GET parameters';
const NETCAT_SECURITY_FILTER_EMAIL_POST = 'POST parameters';
const NETCAT_SECURITY_FILTER_EMAIL_BACKTRACE = 'Call backtrace';
const NETCAT_SECURITY_FILTER_EMAIL_SUFFIX = 'Please fix this vulnerability as soon as possible, because it can be exploited to hack your site.';

const NETCAT_SECURITY_SETTINGS_AUTH = 'Logon protection';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA = 'CAPTCHA';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_RECOMMEND_DEFAULT = '(we recommend to use same settings on all sites)';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_MODE_DISABLED = 'disabled';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_MODE_ALWAYS = 'always show CAPTCHA';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_MODE_COUNT = 'show CAPTCHA after incorrect login or password';
const NETCAT_SECURITY_SETTINGS_AUTH_CAPTCHA_ATTEMPTS = 'number of attempts without CAPTCHA';

const NETCAT_SECURITY_LOG = 'Filter triggering log';
const NETCAT_SECURITY_LOG_NO_DATA = 'No security filter data';
const NETCAT_SECURITY_SETTINGS_SAVE = 'Apply';
const NETCAT_SECURITY_LOG_REFRESH = 'Refresh';
const NETCAT_SECURITY_FILTER_ID = 'ID';
const NETCAT_SECURITY_FILTER_TIME = 'Date, time';
const NETCAT_SECURITY_FILTER_TYPE = 'Type';
const NETCAT_SECURITY_FILTER_URL = 'URL';
const NETCAT_SECURITY_FILTER_REFERER = 'Referer';
const NETCAT_SECURITY_FILTER_IP = 'IP';
const NETCAT_SECURITY_LOG_FILTER = 'Filter';
const NETCAT_SECURITY_FILTER_HASH = 'Hash (exact match)';
const NETCAT_SECURITY_FILTER_GROUP_BY_SOURCE = 'Group by possible injection';
const NETCAT_SECURITY_FILTER_LAST_DATE = 'Last incident date';
const NETCAT_SECURITY_FILTER_SOURCE = 'Source';
const NETCAT_SECURITY_FILTER_STRING = 'String';
const NETCAT_SECURITY_FILTER_INCIDENT_COUNT = 'Count of incidents';
const NETCAT_SECURITY_LOG_GOBACK = 'Back';
const NETCAT_SECURITY_LOG_CLEAR_LABEL = 'Log clearing';
const NETCAT_SECURITY_LOG_CLEAR = 'Clear log';
const NETCAT_SECURITY_ONE_DATE_NOT_SET = 'One date field is not set!';
const NETCAT_SECURITY_TWO_DATES_NOT_SET = 'Please, set two date fields' ;
const NETCAT_SECURITY_INCIDENT_NUMBER = 'Incident №' ;
const NETCAT_SECURITY_INCIDENT_DATA_TIME = 'Date/time';
const NETCAT_SECURITY_INCIDENT_TYPE = 'Type of incoming data filter';
const NETCAT_SECURITY_INCIDENT_CHECK_TYPE = 'Check type';
const NETCAT_SECURITY_INCIDENT_SITE = 'Site';
const NETCAT_SECURITY_INCIDENT_POST = '$_POST content';
const NETCAT_SECURITY_INCIDENT_BACKTRACE = 'Triggering place (backtrace)';
const NETCAT_SECURITY_INCIDENT_IP = 'Client IP';
const NETCAT_SECURITY_INCIDENT_CHECKEDSTRING = 'Possible injection string';
const NETCAT_SECURITY_INCIDENT_SOURCE = 'Possible source of injection';
const NETCAT_SECURITY_INCIDENT_VALUE = 'Poossible injection';
const NETCAT_SECURITY_INCIDENT_SEND_ADMIN = 'Notification was sent to administrator';
const NETCAT_SECURITY_INCIDENT_COUNT_ANALOGUE = 'Count of analogue incidents';
const NETCAT_SECURITY_INCIDENT_SEND_ADMIN_YES = 'Yes';
const NETCAT_SECURITY_INCIDENT_SEND_ADMIN_NO = 'No';
const NETCAT_SECURITY_INCIDENT_SEND_ADMIN_NO_ALREADY_SENT = 'No, analogue filter trigging was previously';
const NETCAT_SECURITY_INCIDENT_VARILABLE = 'varilable injection';
const NETCAT_SECURITY_INCIDENT_VARILABLE_FUNCTION = 'possible injection of function call';
const NETCAT_SECURITY_INCIDENT_QUOTE = 'injection of PHP code';
const NETCAT_SECURITY_INCIDENT_STATEMENT= 'injection of SQL code';
const NETCAT_SECURITY_INCIDENT_COMMENT = 'comment injection';
const NETCAT_SECURITY_INCIDENT_DANGEROUS_TAG = 'script or style tag injection';
const NETCAT_SECURITY_INCIDENT_ATTRIBUTE = 'tag attribute injection';
const NETCAT_SECURITY_INCIDENT_JAVASCRIPT = 'possible injection of Javascript code';
const NETCAT_SECURITY_INCIDENT_DATE_FILTER = "Date";
const NETCAT_SECURITY_INCIDENT_DATE_FILTER_FROM = "from";
const NETCAT_SECURITY_INCIDENT_DATE_FILTER_TO = "to";
const NETCAT_SECURITY_INCIDENT_FILTER_SUBMIT = 'Search';
const NETCAT_SECURITY_INCIDENT_FILTER_RESET = 'Reset form';
const NETCAT_SECURITY_INCIDENT_FILTER_RESET_CONFIRM = 'Do you want to reset the form?';
const NETCAT_SECURITY_INCIDENT_FILTER_FROM = 'from';
const NETCAT_SECURITY_INCIDENT_FILTER_TO = 'to';


const NETCAT_SECURITY_SETTINGS_2FA = 'Two-factor authentication';
const NETCAT_SECURITY_SETTINGS_2FA_MODE_DISABLED = 'disabled';
const NETCAT_SECURITY_SETTINGS_2FA_MODE_FOR_SUPERVISORS = 'required for users with supervisor and director privileges';
const NETCAT_SECURITY_SETTINGS_2FA_MODE_FOR_ADMIN_MODE_USERS = 'required for users with admin panel access';
const NETCAT_SECURITY_SETTINGS_2FA_MODE_FOR_EDITORS = 'required for users with moderator and higher permissions';
const NETCAT_SECURITY_SETTINGS_2FA_MAX_ATTEMPTS_ALLOW = 'Allow to enter incorrect code for no more than';
const NETCAT_SECURITY_SETTINGS_2FA_MAX_ATTEMPTS_TIMES = 'times';
const NETCAT_SECURITY_SETTINGS_2FA_MAX_THEN_BLOCK = 'then block user';
const NETCAT_SECURITY_SETTINGS_2FA_UNBLOCK_MANUALLY = 'until unblocked manually';
const NETCAT_SECURITY_SETTINGS_2FA_UNBLOCK_IN = 'for';
const NETCAT_SECURITY_SETTINGS_2FA_MINUTES = 'minutes';
const NETCAT_SECURITY_SETTINGS_2FA_SECONDS = 'seconds';
const NETCAT_SECURITY_SETTINGS_2FA_VALIDITY = 'Codes expire in';
const NETCAT_SECURITY_SETTINGS_2FA_REFRESH_IN = 'Code can be sent again in';
const NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_USER = 'editable by users';
const NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_ADMIN = 'editable by admins';
const NETCAT_SECURITY_SETTINGS_2FA_FIELD_EDITABLE_BY_NO_ONE = 'cannot be edited by anyone';
const NETCAT_SECURITY_SETTINGS_2FA_VALUE_NOT_SET = 'not set';
const NETCAT_SECURITY_SETTINGS_2FA_METHOD = 'Code delivery method';
const NETCAT_SECURITY_SETTINGS_2FA_ALLOW_SET_ON_LOGON_WITHIN = 'within';
const NETCAT_SECURITY_SETTINGS_2FA_ALLOW_SET_ON_LOGON_WITHIN_DAYS = 'days';
const NETCAT_SECURITY_SETTINGS_2FA_USER_LIST = 'After saving new settings two-factor authentication will be required for the following users';
const NETCAT_SECURITY_SETTINGS_2FA_USER_HAS_NO_VALUE = 'Field “%s” is empty in your profile. <a href="%s" target="_top">Fill it</a> to enable two-factor authentication.';
const NETCAT_SECURITY_SETTINGS_2FA_WRONG_CODE = 'Wrong code';
// method_email
const NETCAT_SECURITY_SETTINGS_2FA_EMAIL_FIELD = 'Field in the “User” table which contains e-mail address';
const NETCAT_SECURITY_SETTINGS_2FA_EMAIL_NO_POSSIBLE_FIELD = 'It is necessary to <a href="%s" target="_top">add a field to the “User” table</a> with a type “string” and a format “email”.';
const NETCAT_SECURITY_SETTINGS_2FA_EMAIL_ADD_FIELD = 'If there’s no appropriate field above, <a href="%s" target="_top">add a field to the “User” table</a> with a type “string” and a format “email”.';
// method_sms
const NETCAT_SECURITY_SETTINGS_2FA_SMS_DISABLED = 'Cannot send SMS messages. <a href="%s" target="_top">Set up the “Messaging” module</a> for two-factor authentication to work correctly.';
const NETCAT_SECURITY_SETTINGS_2FA_SMS_FIELD = 'Field in the “User” table which contains phone number';
const NETCAT_SECURITY_SETTINGS_2FA_SMS_NO_POSSIBLE_FIELD = 'It is necessary to <a href="%s" target="_top">add a field to the “User” table</a> with a type “string” and a format “phone”.';
const NETCAT_SECURITY_SETTINGS_2FA_SMS_ADD_FIELD = 'If there’s no appropriate field above, <a href="%s" target="_top">add a field to the “User” table</a> with a type “string” and a format “phone”.';
const NETCAT_SECURITY_SETTINGS_2FA_SMS_ALLOW_SET_ON_LOGON = 'allow phone number to be set by users on first logon';
// method_totp
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_QR = 'Scan this QR code on the phone in an authentication app (Google Authenticator, FreeOTP or any other for TOTP one-time passwords)';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_SECRET = 'Setup key (for adding to an app manually)';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_ENTER_CODE = 'Enter code from an app';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_WRONG_CODE = 'Wrong code. Please try again. If the problem persists, check time synchronization in the app and on the server.';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_MAKE_NEW = 'create a new secret key for the current user and show a QR code to add it to an app';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_NEED_SECRET = 'Code is required to logon';
const NETCAT_SECURITY_SETTINGS_2FA_TOTP_USE_APP = 'Code from the app will be required on subsequent logons.';

// Используется в админке (форма изменения пользователя):
const NETCAT_USER_2FA_BLOCKED = 'User is blocked because the code was incorrect';
const NETCAT_USER_2FA_BLOCKED_UNTIL = 'until';
const NETCAT_USER_2FA_UNBLOCK = 'unblock';

// Используются на пользовательской части сайта:
const NETCAT_2FA_ENTER_CODE = 'Enter code';
const NETCAT_2FA_SEND_AGAIN = 'Receive new code';
const NETCAT_2FA_ERROR = 'Error';
const NETCAT_2FA_REFRESH_IN = 'will be available in';
const NETCAT_2FA_REFRESH_HINT = 'Try to request a new code or contact the site administrator.';
const NETCAT_2FA_ATTEMPTS_LEFT = '%s attempt left/%s attempts left/%s attempts left';
const NETCAT_2FA_BLOCK_WARNING = 'If you will enter code incorrectly, your account will be blocked';
const NETCAT_2FA_BLOCK_FOR = 'for %s minute/for %s minutes/for %s minutes';
const NETCAT_2FA_BLOCKED_ADMIN = 'Access to the site admin panel is blocked %s due to repeating incorrect authentication attempts.';
const NETCAT_2FA_BLOCKED_PERMANENTLY = 'Contact the site administrator to unblock your account.';
const NETCAT_2FA_CODE_VALID_FOR = 'Code is valid for';
const NETCAT_2FA_CODE_NOT_VALID = 'Code that was sent previously is no longer valid. Please, request a new code.';
// method_email
const NETCAT_2FA_EMAIL_NO_ADDRESS = 'No email is set in your profile to receive the code';
const NETCAT_2FA_EMAIL_SUBJECT = 'Code for %s';
const NETCAT_2FA_EMAIL_BODY = "<p>Your code for %s:</p>\n<h1>%s</h1>";
const NETCAT_2FA_EMAIL_IS_SENT = 'Code was sent to %s';
const NETCAT_2FA_EMAIL_NOT_SENT = 'Error sending code to %s.';
// method_sms
const NETCAT_2FA_SMS_NO_PHONE = 'No phone number is set in your profile to receive the code. Please contact the site administator.';
const NETCAT_2FA_SMS_MESSAGE = '%s - code for %s';
const NETCAT_2FA_SMS_IS_SENT = 'Code was sent to %s';
const NETCAT_2FA_SMS_NOT_SENT = 'Error sending code to %s.';
// method_totp
const NETCAT_2FA_TOTP = 'Application (TOTP)';
const NETCAT_2FA_TOTP_APP_HINT = 'Enter a code from the application';


// _CONDITION_
const NETCAT_CONDITION_DATETIME_FORMAT = 'd.m.Y H:i';
const NETCAT_CONDITION_DATE_FORMAT = 'd.m.Y';

// Фрагменты для составления текстового описания условий
const NETCAT_COND_OP_EQ = '%s';
const NETCAT_COND_OP_EQ_IS = 'is %s';
const NETCAT_COND_OP_NE = 'not %s';
const NETCAT_COND_OP_GT = 'greater than %s';
const NETCAT_COND_OP_GE = 'not less than %s';
const NETCAT_COND_OP_LT = 'less than %s';
const NETCAT_COND_OP_LE = 'not greater than %s';
const NETCAT_COND_OP_GT_DATE = 'later than %s';
const NETCAT_COND_OP_GE_DATE = 'not later than %s';
const NETCAT_COND_OP_LT_DATE = 'earlier than %s';
const NETCAT_COND_OP_LE_DATE = 'not earlier than %s';
const NETCAT_COND_OP_CONTAINS = 'contains &ldquo;%s&rdquo;';
const NETCAT_COND_OP_NOTCONTAINS = 'does not contain &ldquo;%s&rdquo;';
const NETCAT_COND_OP_BEGINS = 'begins with &ldquo;%s&rdquo;';

const NETCAT_COND_QUOTED_VALUE = '&ldquo;%s&rdquo;';
const NETCAT_COND_OR = ', or '; // spaces are important
const NETCAT_COND_AND = '; ';
const NETCAT_COND_OR_SAME = ', ';
const NETCAT_COND_AND_SAME = ' and ';
const NETCAT_COND_DUMMY = '(condition type not available in this edition)';
const NETCAT_COND_DATE_FROM = 'from';
const NETCAT_COND_DATE_TO = 'to';
const NETCAT_COND_TIME_INTERVAL = '%s-%s';
const NETCAT_COND_BOOLEAN_TRUE = 'true';
const NETCAT_COND_BOOLEAN_FALSE = 'false';
const NETCAT_COND_DAYOFWEEK_ON_LIST = 'on Monday/on Tuesday/on Wednesday/on Thursday/on Friday/on Saturday/on Sunday';
const NETCAT_COND_DAYOFWEEK_EXCEPT_LIST = 'except Monday/except Tuesday/except Wednesday/except Thursday/except Friday/except Saturday/except Sunday';
const NETCAT_COND = 'Conditions: ';

const NETCAT_COND_NONEXISTENT_COMPONENT = '[NONEXISTENT COMPONENT]';
const NETCAT_COND_NONEXISTENT_FIELD = '[ERROR IN CONDITION: NONEXISTENT FIELD]';
const NETCAT_COND_NONEXISTENT_VALUE = '[NONEXISTENT VALUE]';
const NETCAT_COND_NONEXISTENT_SUB = '[NONEXISTENT FOLDER]';
const NETCAT_COND_NONEXISTENT_ITEM = '[NONEXISTENT ITEM]';

// Строки, используемые в редакторе условий
const NETCAT_CONDITION_FIELD = 'Selection conditions from other blocks';
const NETCAT_CONDITION_AND = 'and';
const NETCAT_CONDITION_OR = 'or';
const NETCAT_CONDITION_AND_DESCRIPTION = 'All conditions are met:';
const NETCAT_CONDITION_OR_DESCRIPTION = 'Any of the conditions is met:';
const NETCAT_CONDITION_REMOVE_GROUP = 'Remove condition group';
const NETCAT_CONDITION_REMOVE_CONDITION = 'Remove condition';
const NETCAT_CONDITION_REMOVE_ALL_CONFIRMATION = 'Remove all conditions?';
const NETCAT_CONDITION_REMOVE_GROUP_CONFIRMATION = 'Remove the condition group?';
const NETCAT_CONDITION_REMOVE_CONDITION_CONFIRMATION = 'Remove &ldquo;%s&rdquo;?';
const NETCAT_CONDITION_ADD = 'Add...';
const NETCAT_CONDITION_ADD_GROUP = 'Add condition group';

const NETCAT_CONDITION_EQUALS = 'equals';
const NETCAT_CONDITION_NOT_EQUALS = 'not equals';
const NETCAT_CONDITION_LESS_THAN = 'less than';
const NETCAT_CONDITION_LESS_OR_EQUALS = 'not greater than';
const NETCAT_CONDITION_GREATER_THAN = 'greater than';
const NETCAT_CONDITION_GREATER_OR_EQUALS = 'not less than';
const NETCAT_CONDITION_CONTAINS = 'contains';
const NETCAT_CONDITION_NOT_CONTAINS = 'does not contain';
const NETCAT_CONDITION_BEGINS_WITH = 'begins with';
const NETCAT_CONDITION_TRUE = 'yes';
const NETCAT_CONDITION_FALSE = 'no';
const NETCAT_CONDITION_INCLUSIVE = 'inclusive';

const NETCAT_CONDITION_SELECT_CONDITION_TYPE = 'select a condition type';
const NETCAT_CONDITION_SEARCH_NO_RESULTS = 'Not found: ';

const NETCAT_CONDITION_GROUP_OBJECTS = 'Object properties'; // 'Свойства объекта'

const NETCAT_CONDITION_TYPE_OBJECT = 'Object';
const NETCAT_CONDITION_SELECT_OBJECT = 'choose an object';
const NETCAT_CONDITION_NONEXISTENT_ITEM = '(Object does not exist)';
const NETCAT_CONDITION_ITEM_WITHOUT_NAME = 'Object without a name';
const NETCAT_CONDITION_ITEM_SELECTION = 'Object Selection';
const NETCAT_CONDITION_DIALOG_CANCEL_BUTTON = 'Cancel';
const NETCAT_CONDITION_DIALOG_SELECT_BUTTON = 'Select';
const NETCAT_CONDITION_SUBDIVISION_HAS_LIST_NO_COMPONENTS_OR_OBJECTS = 'There are no components or no objects in the selected site section.';
const NETCAT_CONDITION_TYPE_SUBDIVISION = 'Site section';
const NETCAT_CONDITION_TYPE_SUBDIVISION_DESCENDANTS = 'Site section and its’ descendants';
const NETCAT_CONDITION_SELECT_SUBDIVISION = 'choose a site section';
const NETCAT_CONDITION_TYPE_OBJECT_FIELD = 'Object property';
const NETCAT_CONDITION_COMMON_FIELDS = 'All components';
const NETCAT_CONDITION_SELECT_OBJECT_FIELD = 'select an object property';
const NETCAT_CONDITION_SELECT_VALUE = '...'; // sic

const NETCAT_CONDITION_VALUE_REQUIRED = 'Please specify a value or delete the &ldquo;%s&rdquo; condition';

// Infoblock settings dialog; mixin editor
const NETCAT_INFOBLOCK_SETTINGS_CONTAINER = 'Container settings';
const NETCAT_INFOBLOCK_DELETE_CONTAINER = 'Remove container';
const NETCAT_INFOBLOCK_SETTINGS_TITLE_CONTAINER = 'Block container settings';
const NETCAT_INFOBLOCK_SETTINGS_TAB_CUSTOM = 'Settings';
const NETCAT_INFOBLOCK_SETTINGS_TAB_VISIBILITY = 'Pages';
const NETCAT_INFOBLOCK_SETTINGS_TAB_OTHERS = 'Others';
const NETCAT_INFOBLOCK_VISIBILITY_SHOW_BLOCK = 'Show this block';
const NETCAT_INFOBLOCK_VISIBILITY_SHOW_CONTAINER = 'Show this container';
const NETCAT_INFOBLOCK_VISIBILITY_ALL_PAGES = 'everywhere';
const NETCAT_INFOBLOCK_VISIBILITY_THIS_PAGE = 'only on the current page';
const NETCAT_INFOBLOCK_VISIBILITY_SOME_PAGES = 'select pages';
const NETCAT_INFOBLOCK_VISIBILITY_REMOVE_CONDITION = 'remove';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISIONS = 'Subdivisions in which this block will be shown';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISIONS_EXCLUDED = 'Subdivisions in which this block will not be shown';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISIONS_ANY = 'any subdivision';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_NOT_SELECTED = '(Subdivision is not selected)';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_INCLUDE_CHILDREN = 'including descendants';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_DOESNT_EXIST = 'Nonexistent subdivision';
const NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_SELECT = 'choose a subdivision';
const NETCAT_INFOBLOCK_VISIBILITY_ACTIONS = 'Page types';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_INDEX = 'object list page';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_FULL = 'object page';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_ADD = 'object add page';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_DELETE = 'object delete page';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_EDIT = 'object edit page';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_SEARCH = 'search page';
const NETCAT_INFOBLOCK_VISIBILITY_ACTION_SUBSCRIBE = 'subscribe page';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENTS = 'Components in the main content area which must be present on the page';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENTS_EXCLUDED = 'Components in the main content area which must not be present on the page';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENTS_ANY = 'any component';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_NOT_SELECTED = '(Component is not selected)';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_DOESNT_EXIST = 'Nonexistent component';
const NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_SELECT = 'choose a component';
const NETCAT_INFOBLOCK_VISIBILITY_OBJECTS = 'Objects for which the block is shown';
const NETCAT_INFOBLOCK_VISIBILITY_OBJECTS_ANY = 'any object';
const NETCAT_INFOBLOCK_VISIBILITY_OBJECT_NOT_SELECTED = '(Object is not selected)';
const NETCAT_MIXIN_TITLE = 'Styles and layout';
const NETCAT_MIXIN_TITLE_INDEX = 'List styles and layout';
const NETCAT_MIXIN_TITLE_INDEX_ITEM = 'List items styles and layout';
const NETCAT_MIXIN_INDEX = 'List';
const NETCAT_MIXIN_INDEX_ITEM = 'List item';
const NETCAT_MIXIN_BREAKPOINT_TYPE = 'Apply breakpoints';
const NETCAT_MIXIN_BREAKPOINT_TYPE_BLOCK = 'to block width';
const NETCAT_MIXIN_BREAKPOINT_TYPE_VIEWPORT = 'to page width';
const NETCAT_MIXIN_BREAKPOINT_ADD = 'Add width range';
const NETCAT_MIXIN_BREAKPOINT_ADD_PROMPT = 'New block width breakpoint';
const NETCAT_MIXIN_BREAKPOINT_ADD_PROMPT_RANGE = '(specify value from %from to %to px)';
const NETCAT_MIXIN_BREAKPOINT_CHANGE = 'Change breakpoint';
const NETCAT_MIXIN_BREAKPOINT_CHANGE_PROMPT = 'Change breakpoint (0 or an empty string to delete):';
const NETCAT_MIXIN_FOR_WIDTH_FROM = 'when wider than %from px';
const NETCAT_MIXIN_FOR_WIDTH_TO = 'when narrower than %to px';
const NETCAT_MIXIN_FOR_WIDTH_RANGE = 'when width is between %from and %to px';
const NETCAT_MIXIN_FOR_WIDTH_ANY = '';
const NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_FROM = 'when viewport is wider than %from px';
const NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_TO = 'when viewport is narrower than %to px';
const NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_RANGE = 'when viewport width is between %from and %to px';
const NETCAT_MIXIN_FOR_VIEWPORT_WIDTH_ANY = 'for any viewport width';
const NETCAT_MIXIN_FOR_BLOCK_WIDTH_FROM = 'for blocks wider than %from px';
const NETCAT_MIXIN_FOR_BLOCK_WIDTH_TO = 'for blocks narrower than %to px';
const NETCAT_MIXIN_FOR_BLOCK_WIDTH_RANGE = 'for blocks that have width from %from to %to px';
const NETCAT_MIXIN_FOR_BLOCK_WIDTH_ANY = 'for blocks of any width';
const NETCAT_MIXIN_PRESET_REMOVE_BUTTON = 'remove';
const NETCAT_MIXIN_NONE = 'none';
const NETCAT_MIXIN_WIDTH = 'Width';
const NETCAT_MIXIN_SELECTOR = 'Additional CSS-selector';
const NETCAT_MIXIN_SELECTOR_ADD = '-- add selector --';
const NETCAT_MIXIN_SELECTOR_ADD_PROMPT = 'Add CSS selector:';
const NETCAT_MIXIN_SETTINGS_REMOVE = 'Remove settings';
const NETCAT_MIXIN_PRESET_SELECT = 'Base settings preset';
const NETCAT_MIXIN_PRESET_DEFAULT = 'default preset (“%s”)';
const NETCAT_MIXIN_PRESET_DEFAULT_NONE = 'default (none)';
const NETCAT_MIXIN_PRESET_NONE_EXPLICIT = 'do not use default preset';
const NETCAT_MIXIN_PRESET_CREATE = '-- add preset --';
const NETCAT_MIXIN_PRESET_EDIT_BUTTON = 'edit';
const NETCAT_MIXIN_PRESET_TITLE_EDIT = 'Edit preset';
const NETCAT_MIXIN_PRESET_TITLE_ADD = 'Add preset';
const NETCAT_MIXIN_PRESET_NAME = 'Preset name';
const NETCAT_MIXIN_PRESET_AVAILABILITY = 'This preset can be used for';
const NETCAT_MIXIN_PRESET_FOR_ANY_COMPONENT = 'any template of any component';
const NETCAT_MIXIN_PRESET_FOR_COMPONENT_TEMPLATE_PREFIX = 'template &ldquo;%s&rdquo; of the';
const NETCAT_MIXIN_PRESET_FOR_COMPONENT = 'component &ldquo;%s&rdquo;';
const NETCAT_MIXIN_PRESET_USE_AS_DEFAULT_FOR = 'use as default for ';
const NETCAT_MIXIN_PRESET_TITLE_DELETE = 'Remove preset';
const NETCAT_MIXIN_PRESET_DELETE_WARNING = 'Preset &ldquo;%s&rdquo; will be deleted.';
const NETCAT_MIXIN_PRESET_USED_FOR_COMPONENT_TEMPLATES = 'This preset is used as default for';
const NETCAT_MIXIN_PRESET_COMPONENT_TEMPLATES_COUNT_FORMS = 'component template/component_templates';
const NETCAT_MIXIN_PRESET_USED_FOR_BLOCKS = 'This preset is used for';
const NETCAT_MIXIN_PRESET_BLOCKS_COUNT_FORMS = 'block/blocks';

const NETCAT_MODAL_DIALOG_IMAGE_HEADER = 'Add image';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_EDIT_CAPTION = 'Edit';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_EDIT_COLORPICKER_INPUT_PLACEHOLDER = 'RGB value';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_CAPTION = 'Icons';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_ICONS_NOT_FOUND = 'Not found';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_ICONS_SEARCH_INPUT_PLACEHOLDER = 'Search...';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_LIBRARY_CHOOSE = 'All libraries';
const NETCAT_MODAL_DIALOG_IMAGE_BUTTON_SAVE = 'Save';
const NETCAT_MODAL_DIALOG_IMAGE_BUTTON_CLOSE = 'Cancel';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_UPLOAD_CAPTION = 'Upload';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_WEB_CAPTION = 'From Web';
const NETCAT_MODAL_DIALOG_IMAGE_TAB_ICONS_SHOW_ALL = 'Show all';

const NETCAT_MODAL_DIALOG_WRAP_CONTAINER_SUBMIT_BUTTON_CAPTION = "Select block";
const NETCAT_MODAL_DIALOG_WRAP_CONTAINER_BODY_CONTENT = "<p>You are adding a new block <strong>%s from %s</strong>.
A shared container with <strong>%s layout</strong> will be created automatically.</p>
<p>Keep this layout feature in mind when further editing the block design.</p>" ;
const NETCAT_MODAL_DIALOG_WRAP_CONTAINER_LAYOUT_Y_AXIS = "vertical";
const NETCAT_MODAL_DIALOG_WRAP_CONTAINER_LAYOUT_X_AXIS = "horizontal";

const NC_FILTER_TYPE_STRING_NAME = 'Text';
const NC_FILTER_TYPE_NUMBER_NAME = 'Numeric (from...to)';
const NC_FILTER_TYPE_RANGE_NAME = 'Range of values';
const NC_FILTER_TYPE_LIST_NAME = 'Single choice list';
const NC_FILTER_TYPE_MULTIPLE_NAME = 'Multiple choice list (checkboxes)';
const NC_FILTER_TYPE_DATE_NAME = 'Date';
const NC_FILTER_TYPE_DATE_RANGE_NAME = 'Dates range (from...to)';
const NC_FILTER_NOT_SELECTED = 'Not selected';
const NC_FILTER_NOT_CONFIGURED = 'Filter is not configured yet';
const NC_FILTER_SUBMIT = 'Submit';
const NC_FILTER_FORM_NUMBER_FROM = 'from';
const NC_FILTER_FORM_NUMBER_TO = 'to';
const NC_FILTER_FORM_DATE_FROM = 'from';
const NC_FILTER_FORM_DATE_TO = 'to';

//Catalogue mode
const CONTROL_CONTENT_CATALOGUE_FUNCS_MODE = 'Mode catalogue';
const CONTROL_CONTENT_CATALOGUE_FUNCS_MODE_TEMPLATE_DEVELOPMENT = 'Template development';
const CONTROL_CONTENT_CATALOGUE_FUNCS_MODE_NORMAL = 'Normal';
const CONTROL_CONTENT_CATALOGUE_FUNCS_MODE_DEMO = 'Demo';

const CONTROL_CONTENT_SUBDIVISION_FUNCS_DISALLOW_MOVE_AND_DELETE = 'disallow hide, move and delete subdivision';
const CONTROL_CONTENT_SUB_CLASS_FUNCS_DISALLOW_MOVE_AND_DELETE = 'disallow hide, move and delete conponents';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE = 'Disallow deleting subdivision or infoblock';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_HIDE = 'Disallow hide subdivision';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE_ITEM = 'disallow deleting subdivision';
const CONTROL_CONTENT_SUB_CLASS_DISALLOW_ERROR_DELETE_ITEM = 'disallow deleting infoblock';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_ACTION_TITLE = 'Disallow action';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_CUT_SUB_CLASS = 'Disallow cut infoblock.';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE_SUB_CLASS = 'Disallow delete infoblock%s.';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_HIDE_SUB_CLASS = 'Disallow hide infoblock.';
const CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_HIDE_SUBDIVISION_SUB_CLASS = 'Disallow hide subdivision: on one of the infoblocks there is a disallow on deleting and disabling it';

const NETCAT_VERSION_TAB_NAME = 'History';
const NETCAT_VERSION_TAB_TOOLBAR_SUBDIVISION = 'Section';
const NETCAT_VERSION_TAB_TOOLBAR_INFOBLOCK = 'Infoblocks';
const NETCAT_VERSION_TAB_TOOLBAR_OBJECT = 'Objects';
const NETCAT_VERSION_TABLE_TIMESTAMP = 'Date and time';
const NETCAT_VERSION_TABLE_USER = 'User';
const NETCAT_VERSION_TABLE_CHANGES = 'Changes';
const NETCAT_VERSION_TABLE_SHOW_CHANGES = 'show changes';
const NETCAT_VERSION_TABLE_NO_CHANGES = 'no changes';
const NETCAT_VERSION_OBJECT_RESTORED = 'Object version restored';
const NETCAT_VERSION_INFOBLOCK_RESTORED = 'Infoblock version restored';
const NETCAT_VERSION_SUBDIVISION_RESTORED = 'Section version restored';
const NETCAT_VERSION_OBJECT_RESTORE_ERROR = 'Error restoring object version';
const NETCAT_VERSION_INFOBLOCK_RESTORE_ERROR = 'Error restoring infoblock version';
const NETCAT_VERSION_SUBDIVISION_RESTORE_ERROR = 'Error restoring section version';
const NETCAT_VERSION_OBJECT_RESTORE_ERROR_PARENT = 'Unable to restore object version because parent infoblock is absent';
const NETCAT_VERSION_INFOBLOCK_RESTORE_ERROR_PARENT = 'Unable to restore infoblock version because parent section is absent';
const NETCAT_VERSION_SUBDIVISION_RESTORE_ERROR_PARENT = 'Unable to restore section version because parent site is absent';
const NETCAT_VERSION_UNDO = 'Undo';
const NETCAT_VERSION_REDO = 'Redo';
const NETCAT_VERSION_RESTORE = 'Restore';
const NETCAT_VERSION_CHANGESET_INITIAL = 'first saved version';
const NETCAT_VERSION_CHANGESET_RESTORE_PAGE = 'restored page version from %s';
const NETCAT_VERSION_CHANGESET_SITE_IMPORT = 'site import';
const NETCAT_VERSION_ENTITY = 'Entity';
const NETCAT_VERSION_ENTITY_SITE = 'Site';
const NETCAT_VERSION_ENTITY_SUBDIVISION = 'Section';
const NETCAT_VERSION_ENTITY_INFOBLOCK = 'Infoblock';
const NETCAT_VERSION_ENTITY_OBJECT = 'Object';
const NETCAT_VERSION_ACTION_INITIAL = '(First version)';
const NETCAT_VERSION_ACTION_CREATED = 'creating';
const NETCAT_VERSION_ACTION_UPDATED = 'updating';
const NETCAT_VERSION_ACTION_SUBCLASS_PRIORITIES_UPDATED = 'updating infoblocks priority';
const NETCAT_VERSION_ACTION_MOVED = 'moving';
const NETCAT_VERSION_ACTION_COPIED = 'copying';
const NETCAT_VERSION_ACTION_DELETED = 'deleting';
const NETCAT_VERSION_ACTION_ENABLED = 'enabling';
const NETCAT_VERSION_ACTION_DISABLED = 'disabling';
const NETCAT_VERSION_OF_SITE = 'of the site ‘%s’';
const NETCAT_VERSION_OF_SUBDIVISION = 'of the subdivision ‘%s’';
const NETCAT_VERSION_OF_BLOCK = 'of the block ‘%s’';
const NETCAT_VERSION_OF_CONTAINER = 'of the container';
const NETCAT_VERSION_OF_NAMED_CONTAINER = 'of the container ‘%s’';
const NETCAT_VERSION_OF_OBJECT = 'of the object';
const NETCAT_VERSION_OF_OBJECT_WITH_NAME = '%s ‘%s’';
const NETCAT_VERSION_TO_BLOCK = 'to the block ‘%s’';
const NETCAT_VERSION_IN_BLOCK = 'in the block ‘%s’';
const NETCAT_VERSION_TITLE_BY_USER = 'by user';
const NETCAT_VERSION_TITLE_GLOBAL_WARNING = 'Undoing this action will cause changes on other pages!';

const MINIMAL_PHP_SUPPORT_ALERT = "From %s, support for Netcat products running PHP versions below 7.4 <strong>will be limited.</strong></br>You are using PHP version %s. Please schedule your PHP update.";
const SITE_UNICODE_SUPPORT_ALERT = "As %s, support for Netcat products on sites <strong>with WINDOWS-1251 encoding will be discontinued.</strong>
</br>Please plan to convert the site to UTF-8 encoding.";

//Selftest
const TOOLS_SELFTEST = "Site performance monitoring";
const TOOLS_SELFTEST_HEADING = "Checking the functionality of site components";
const TOOLS_SELFTEST_SUBHEADING = "Site performance monitoring";
const TOOLS_SELFTEST_DESCRIPTION = "This section contains the most common and current problems related to development,
     design, support, security, promotion and other aspects of the site.";
const TOOLS_SELFTEST_RUN = "Run";
const TOOLS_SELFTEST_PROBLEM_DETECTED_1 = "<strong><span id='problems-count'>%s</span> problem</strong> detected.";
const TOOLS_SELFTEST_PROBLEM_DETECTED_2 = "<strong><span id='problems-count'>%s</span> problems</strong> detected.";
const TOOLS_SELFTEST_PROBLEM_DETECTED_3 = "<strong><span id='problems-count'>%s</span> problems</strong> detected.";
const TOOLS_SELFTEST_PROBLEM_DETECTED_4 = "<span id='problems-count'>%s</span><span> problem detected</span>";
const TOOLS_SELFTEST_PROBLEM_DETECTED_5 = "<span id='problems-count'>%s</span><span> problems detected</span>";
const TOOLS_SELFTEST_PROBLEM_DETECTED_6 = "<span id='problems-count'>%s</span><span> problems detected</span>";
const TOOLS_SELFTEST_HAS_NO_PROBLEMS = "No problems found";
const TOOLS_SELF_TEST_ALL_WORKS_CLEAN = "Basic site parameters are working correctly";
const TOOLS_SELFTEST_IN_PROCESS = "Test in progress";
const TOOLS_SELFTEST_DONE = "Test complete";
const TOOLS_SELFTEST_DONE_SUCCESSFULLY = "Test completed successfully.";

const TOOLS_SELFTEST_STATUS_CRITICAL = "Critical";
const TOOLS_SELFTEST_STATUS_RECOMMENDED = "Recommended";
const TOOLS_SELFTEST_STATUS_ERROR = "Test failed";

const TOOLS_SELFTEST_HTTPS_CAPTION = "HTTPS Support";
const TOOLS_SELFTEST_HTTPS_FAIL_DESCRIPTION = "The site does not work over HTTPS";
const TOOLS_SELFTEST_HTTPS_HAS_NO_SSL = "<p>The site does not have an SSL certificate. This may create problems with opening the site and indexing by search engines. Ask your administrator or hosting provider about the possibility of installing a certificate</p>";
const TOOLS_SELFTEST_HTTPS_HAS_NO_HTTPS_DB_PARAMETER = "<p>The site does not have a rule for using the HTTPS protocol. Set it in <a href='/netcat/admin/#catalogue.edit(%s)' target='_blank'>site settings.</ a></p>";
const TOOLS_SELFTEST_HTTPS_TEST_SUCCESS = "The site works correctly using the HTTPS protocol.";

const TOOLS_SELFTEST_DEMO_MODE_CAPTION = "Site operating mode";
const TOOLS_SELFTEST_DEMO_MODE_FAIL_DESCRIPTION_SHORT = "The site is running in demo mode";
const TOOLS_SELFTEST_DEMO_MODE_FAIL_DESCRIPTION = "The site is running in demo mode, which is intended to prepare the site for launch. If you have already launched the site, set <a href='/netcat/admin/#catalogue.system(%s)' target='_blank' >normal mode.</a>";
const TOOLS_SELFTEST_DEMO_MODE_TEST_SUCCESS = "The site is working in normal mode.";

const TOOLS_SELFTEST_DEMO_CONTENT_CAPTION = "Availability of demo content";
const TOOLS_SELFTEST_DEMO_CONTENT_FAIL_DESCRIPTION_SHORT = "The site has demo content";
const TOOLS_SELFTEST_DEMO_CONTENT_FAIL_DESCRITION = "<p>Demo content is sample text or other type of content from a site template.</p><p>Edit or delete it.</p>Helps you find it on the site <a href='/netcat /admin/#site.demo_content(%s)' target='_blank'>this page</a>";
const TOOLS_SELFTEST_DEMO_CONTENT_TEST_SUCCESS = "Demo content was not found on the site.";

const TOOLS_SELFTEST_INSTALL_DIRECTORY_CAPTION = "Presence of install folder";
const TOOLS_SELFTEST_INSTALL_DIRECTORY_FAIL_DESCRIPTION_SHORT = "The install folder has not been deleted on the site";
const TOOLS_SELFTEST_INSTALL_DIRECTORY_FAIL_DESCRIPTION = "<p>This folder contains a script (program) for installing the system.</p>It must be deleted after installation, otherwise there are security risks.";
const TOOLS_SELFTEST_INSTALL_DIRECTORY_TEST_SUCCESS = "The install folder is missing, that's good.";

const TOOLS_SELFTEST_PERMISSIONS_CAPTION = "File system permissions";
const TOOLS_SELFTEST_PERMISSIONS_FAIL_DESCRIPTION_SHORT = "File system permissions are not set correctly";
const TOOLS_SELFTEST_PERMISSIONS_FAIL_DESCRIPTION = "<p>Check the permission settings for the following files/directories:</p>%s<p><p>See <a href='https://netcat.ru/developers/docs/install-and -settings/installation/' target='_blank'>instructions for installing rights.</a></p>";
const TOOLS_SELFTEST_PERMISSIONS_TEST_SUCCESS = "Permissions on files and folders are set correctly.";

const TOOLS_SELFTEST_INDEXES_CAPTION = "Search index";
const TOOLS_SELFTEST_INDEXES_FAIL_DESCRIPTION_SHORT = "The site is not indexed";
const TOOLS_SELFTEST_INDEXES_FAIL_DESCRIPTION = "<p>The site is not indexed by the internal search engine, the search will return an empty result.</p>Fix this to <a href='/netcat/admin/#module.search.indexing' target='_blank'> search module management page.</a>";
const TOOLS_SELFTEST_INDEXES_PERF_LONG_TIME_AGO = "<p>The site has not been indexed by an internal search engine for a long time (last re-indexing was %s)</p>>Index it <a href='/netcat/admin/#module.search.indexing' target='_blank'>manually</a> or enable <a href='https://netcat.ru/developers/docs/modules/module-search/schedule/' target='_blank '>indexing according to schedule.</a>";
const TOOLS_SELFTEST_INDEXES_TEST_SUCCESS = "The site has been indexed by the internal search engine.";

const TOOLS_SELFTEST_UPDATES_CAPTION = "System updates";
const TOOLS_SELFTEST_UPDATES_FAIL_DESCRIPTION_SHORT = "Uninstalled updates found";
const TOOLS_SELFTEST_UPDATES_FAIL_DESCRIPTION = "<p>Uninstalled updates were detected.</p>For the site to function correctly, it is recommended to install the latest updates.";
const TOOLS_SELFTEST_UPDATES_TEST_SUCCESS = "All updates installed";

const TOOLS_SELFTEST_CRONTAB_CAPTION = "Crontab works correctly";
const TOOLS_SELFTEST_CRONTAB_FAIL_DESCRIPTION = "The task scheduler is not running";
const TOOLS_SELFTEST_CRONTAB_FAIL_DESCRIPTION_SHORT = "Set up crontab on your server according to <a href='https://netcat.ru/developers/docs/system-tools/task-management/' target='_blank'>instructions.</a> ";
const TOOLS_SELFTEST_CRONTAB_TEST_SUCCESS = "The task scheduler (crontab) is configured correctly.";

const TOOLS_SELFTEST_REQUESTS_CAPTION = "Requests";
const TOOLS_SELFTEST_REQUESTS_FAIL_DESCRIPTION_SHORT = "Unprocessed requests detected";
const TOOLS_SELFTEST_REQUESTS_FAIL_DESCRIPTION = "Requests found: <strong>%s</strong>, they can be <a href='/netcat/admin/#module.requests.list(%s)' target='_blank'>processed</a >.";
const TOOLS_SELFTEST_REQUESTS_TEST_SUCCESS = "There are no new pending requests.";

const TOOLS_SELFTEST_NETSHOP_ORDERS_CAPTION = "Orders of the 'Store' module";
const TOOLS_SELFTEST_NETSHOP_ORDERS_FAIL_DESCRIPTION_SHORT = "You have pending orders";
const TOOLS_SELFTEST_NETSHOP_ORDERS_FAIL_DESCRIPTION = "Orders found: <strong>%s</strong>, they can be <a href='/netcat/admin/#module.netshop.order(%s)' target='_blank'>processed.</ a>";
const TOOLS_SELFTEST_NETSHOP_ORDERS_TEST_SUCCESS = "There are no new pending orders.";

const TOOLS_SELFTEST_CONTENT_VERSIONING_CAPTION = "Page versions";
const TOOLS_SELFTEST_CONTENT_VERSIONING_FAIL_DESCRIPTION_SHORT = "Changed page versions are not saved";
const TOOLS_SELFTEST_CONTENT_VERSIONING_FAIL_DESCRIPTION = "<p>Without saving changes, undoing them will not work.</p>You can enable saving versions in <a href='/netcat/admin/#catalogue.system(%s)' target='_blank'> site settings.</a>";
const TOOLS_SELFTEST_CONTENT_VERSIONING_TEST_SUCCESS = "Changes to pages are saved, undo changes are available.";

const TOOLS_SEFLTEST_SEO_ROBOTS_CAPTION = "Availability for indexing";
const TOOLS_SEFLTEST_SEO_ROBOTS_FAIL_DESCRIPTION_SHORT = "The site is not available for indexing by search engines.";
const TOOLS_SEFLTEST_SEO_ROBOTS_DESCRIPTION = "<p>The robots.txt file has a Disallow directive for the site.</p><a href='/netcat/admin/#catalogue.edit(%s)' target='_blank'>Remove it< /a> if you want the site to be accessible to search engines.";
const TOOLS_SELFTEST_SEO_HAS_ROBOTS_FILE = "<p>There is a robots.txt file in the root of the site.</p>It is recommended to configure robots directives on the page <a href='/netcat/admin/#catalogue.edit(%s)' target='_blank' >site settings</a>";
const TOOLS_SELFTEST_SEO_ROBOTS_TEST_SUCCESS = "The site is available for indexing by search engines.";

// Support
const SUPPORT_LABEL = "Technical Support";
const SUPPORT_UPDATES_LABEL = "System Updates";
const SUPPORT_STATUS_ACTIVE = "Active";
const SUPPORT_STATUS_INACTIVE = "Inactive";
const SUPPORT_STATUS_OVER = "Over";
const SUPPORT_STATUS_END_CLOSE = "Ends in %s %s";
const SUPPORT_DAY_DECL_1 = "day";
const SUPPORT_DAY_DECL_2 = "days";
const SUPPORT_DAY_DECL_3 = "days";
const SUPPORT_STATUS_ENDS_CLOSE = "Ends close";
const SUPPORT_STATUS_INCORRECT_LICENSE = "The license is specified incorrectly";
const SUPPORT_PAID_UNDER = "Paid before";
const SUPPORT_PROLONGATION = "Extend for a year for %s";
const SUPPORT_PROLONGATION_PRICE_AFTER_DISCOUNTS = "With% the cost of the extension will be %s";
const SUPPORT_MORE_DETAILS = "More details";
const SUPPORT_INCORRECT_NOTICE = "The operation of the system can be dangerous, contact support, we will help you figure it out and fix it";
const SUPPORT_SEND_MESSAGE = "Write to tech support";
const SUPPORT_CURRENT_NETCAT_VERSION = "Current version of Netcat";
const SUPPORT_CURRENT_NETCAT_VERSION_ACTUAL = "The latest version is installed, no update required";
const SUPPORT_OUTDATED_NETCAT_VERSION = "Outdated version";
const SUPPORT_UPDATE_SYSTEM_FREE = "Upgrade the system for free";
const SUPPORT_UPDATE_SYSTEM = "Update the system";
const SUPPORT_UPDATE_SYSTEM_FOR_MONEY = "Update the system by %s";
const SUPPORT_PAYED_CAPTION = "paid";
const SAAS_SUPPORT_WIDGET_CAPTION = "Site status";
const SAAS_SUPPORT_WIDGET_CANNOT_GET_SITE_INFO = "Failed to get information about the site. Contact technical support";
const SAAS_SUPPORT_WIDGET_PAYMENT_NOTICE_BEFORE_SITE_DELETE = "Have time to pay before %s, otherwise the site will be disabled";
const SAAS_SUPPORT_WIDGET_PAYMENT_RENEWAL_CAPTION = "Renew";
const SAAS_SUPPORT_WIDGET_IN_TRIAL_MODE = "in demo-mode";
const SAAS_SUPPORT_WIDGET_TRIAL_MODE_ENDS = "The demo mode is over";
const SAAS_SUPPORT_WIDGET_SITE_IS_DISABLED_NOTICE = "The site is disabled, renew your subscription";
