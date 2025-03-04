<?php

global $ADMIN_PATH;

const NC_PERM_MODULE_AUTH_SETTINGS_EDIT = 'изменение настроек модуля (кроме шаблонов)';

const NETCAT_MODULE_AUTH_DESCRIPTION = 'Модуль для работы с пользователями. Возможность регистрации внешней группы пользователей, изменение собственной анкеты, пароля, восстановление пароля. Данный модуль может интегрироваться с другими модулями системы.';
const NETCAT_MODULE_AUTH_REG_OK = 'Регистрация подтверждена.';
const NETCAT_MODULE_AUTH_REG_ERROR = 'Ошибка! Регистрация не подтверждена.';
const NETCAT_MODULE_AUTH_REG_INVALIDLINK = 'Неправильная ссылка.';
const NETCAT_MODULE_AUTH_ERR_NEEDAUTH = 'Необходимо авторизоваться.';
const NETCAT_MODULE_AUTH_CHANGEPASS_NOTEQUAL = 'Пароли не совпадают. Попробуйте еще раз.';
const NETCAT_MODULE_AUTH_ERR_NOFIELDSET = 'Поле для Email не определено.';
const NETCAT_MODULE_AUTH_ERR_NOUSERFOUND = 'Пользователь не найден';
const NETCAT_MODULE_AUTH_MSG_FILLFIELD = 'Заполните одно из полей';
const NETCAT_MODULE_AUTH_MSG_BADEMAIL = 'Некорректный E-mail';
const NETCAT_MODULE_AUTH_MSG_NEWPASSSENDED = 'Подтверждающее письмо было выслано на ваш email. Для продолжения процедуры восстановления пароля откройте ссылку, находящуюся в письме.';
const NETCAT_MODULE_AUTH_MSG_INVALID_LOGIN_FORMAT = 'Неверный формат поля &laquo;Логин&raquo;, используйте буквы, цифры, знак подчёркивания, дефис или пробел.';
const NETCAT_MODULE_AUTH_MSG_INVALID_EMAIL_FORMAT = 'Неверный формат поля &laquo;Email&raquo;, используйте буквы, цифры, знак подчёркивания, дефис и точку.';
const NETCAT_MODULE_AUTH_NEWPASS_SUCCESS = 'Пароль успешно изменен.';
const NETCAT_MODULE_AUTH_NEWPASS_ERROR = 'Ошибка при изменения пароля.';

const NETCAT_MODULE_AUTH_FORM_AND_MAIL_TEMPLATES = 'Шаблоны форм и писем';
const NETCAT_MODULE_AUTH_EXTERNAL_AUTH = 'Авторизация через внешние сервисы';

const NETCAT_MODULE_AUTH_LOGIN = 'Логин';
const NETCAT_MODULE_AUTH_ENTER = 'Вход';
const NETCAT_MODULE_AUTH_REGISTER = 'Зарегистрироваться';
const NETCAT_MODULE_AUTH_INCORRECT_LOGIN_OR_RASSWORD = 'Неверно введен логин или пароль';
const NETCAT_MODULE_AUTH_AUTHORIZATION_UPPER = 'АВТОРИЗАЦИЯ';
const NETCAT_MODULE_AUTH_AUTHORIZATION = 'Авторизация';
const NETCAT_MODULE_AUTH_FORGOT = 'Забыли?';
const NETCAT_MODULE_AUTH_PASSWORD = 'Пароль';
const NETCAT_MODULE_AUTH_PASSWORD_CONFIRMATION = 'Введите пароль ещё раз';
const NETCAT_MODULE_AUTH_FIRST_NAME = 'Имя';
const NETCAT_MODULE_AUTH_LAST_NAME = 'Фамилия';
const NETCAT_MODULE_AUTH_NICKNAME = 'Ник';
const NETCAT_MODULE_AUTH_PHOTO = 'Фотография';
const NETCAT_MODULE_AUTH_SAVE = 'Сохранить логин и пароль';
const NETCAT_MODULE_AUTH_REMEMBER_ME = 'Запомнить меня';
const NETCAT_MODULE_AUTH_NOT_NEW_MESSAGE = 'Новых сообщений нет';
const NETCAT_MODULE_AUTH_NEW_MESSAGE = 'Новые сообщения';
const NETCAT_MODULE_AUTH_HELLO = 'Здравствуйте';
const NETCAT_MODULE_AUTH_LOGOUT = 'Завершить сеанс';
const NETCAT_MODULE_AUTH_BY_TOKEN = 'Войти по токену';

const NETCAT_MODULE_AUTH_LOGIN_WAIT = 'Пожалуйста, подождите';
const NETCAT_MODULE_AUTH_LOGIN_FREE = 'Логин свободен';
const NETCAT_MODULE_AUTH_LOGIN_BUSY = 'Логин занят';
const NETCAT_MODULE_AUTH_LOGIN_INCORRECT = 'Логин содержит запрещенные символы';

const NETCAT_MODULE_AUTH_PASS_LOW = 'Низкая';
const NETCAT_MODULE_AUTH_PASS_MIDDLE = 'Средняя';
const NETCAT_MODULE_AUTH_PASS_HIGH = 'Высокая';
const NETCAT_MODULE_AUTH_PASS_VHIGH = 'Очень высокая';
const NETCAT_MODULE_AUTH_PASS_EMPTY = 'Пароль не может быть пустым';
const NETCAT_MODULE_AUTH_PASS_SHORT = 'Пароль слишком короткий';

const NETCAT_MODULE_AUTH_PASS_COINCIDE = 'Пароли совпадают';
const NETCAT_MODULE_AUTH_PASS_N_COINCIDE = 'Пароли не совпадают';

const NETCAT_MODULE_AUTH_PASS_RELIABILITY = 'Надёжность:';

const NETCAT_MODULE_AUTH_CP_NEWPASS = 'Новый пароль';
const NETCAT_MODULE_AUTH_CP_CONFIRM = 'Повторите ввод пароля';
const NETCAT_MODULE_AUTH_CP_DOBUTT = 'Сменить пароль';

const NETCAT_MODULE_AUTH_PRF_LOGIN = 'Введите логин';
const NETCAT_MODULE_AUTH_PRF_EMAIL = 'Или Email';
const NETCAT_MODULE_AUTH_PRF_EMAIL_2 = 'Email';
const NETCAT_MODULE_AUTH_PRF_DOBUTT = 'Восстановить пароль';

const NETCAT_MODULE_AUTH_BUT_AUTORIZE = 'Авторизоваться';
const NETCAT_MODULE_AUTH_BUT_BACK = 'Вернуться';
const NETCAT_MODULE_AUTH_MSG_AUTHISOK = 'Авторизация прошла успешно.';
const NETCAT_MODULE_AUTH_MSG_AUTHUPISOK = 'Сеанс завершен.';

const NETCAT_MODULE_AUTH_MSG_SESSION_CLOSED = "Сеанс завершен. <a href='%s'>Вернуться</a>";
const NETCAT_MODULE_AUTH_MSG_AUTH_SUCCESS = "Авторизация прошла успешно. <a href='%s'>Вернуться</a>";

const NETCAT_MODULE_AUTH_ADMIN_MAIN_SETTINGS_TITLE = 'Основные настройки';
const NETCAT_MODULE_AUTH_ADMIN_SAVE_OK = 'Настройки успешно изменены';

define("NETCAT_MODULE_AUTH_ADMIN_INFO", "Вы можете настроить отображение списка пользователей, а также структуры таблицы \"Пользователи\" в разделе \"<a href=" . $ADMIN_PATH . "field/system.php>Системные таблицы</a>\".<br/>");

// название вкладкок
const NETCAT_MODULE_AUTH_ADMIN_TAB_INFO = 'Информация';
const NETCAT_MODULE_AUTH_ADMIN_TAB_REGANDAUTH = 'Регистрация и авторизация';
const NETCAT_MODULE_AUTH_ADMIN_TAB_TEMPLATES = 'Шаблоны вывода';
const NETCAT_MODULE_AUTH_ADMIN_TAB_MAIL = 'Шаблоны писем';
const NETCAT_MODULE_AUTH_ADMIN_TAB_SETTINGS = 'Настройки';
const NETCAT_MODULE_AUTH_ADMIN_TAB_CLASSIC = 'По логину и паролю';
const NETCAT_MODULE_AUTH_ADMIN_TAB_EXAUTH = 'Через внешние сервисы';
const NETCAT_MODULE_AUTH_ADMIN_TAB_GENERAL = 'Общие';
const NETCAT_MODULE_AUTH_ADMIN_TAB_SYSTEM = 'Системные';

// информация
const NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT = 'Общее количество пользователей';
const NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT_UNCHECKED = 'Выключенных пользователей';
const NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT_UNCONFIRMED = 'Количество пользователей, еще не подтвердившие регистрацию';
const NETCAT_MODULE_AUTH_ADMIN_INFO_NONE = 'нет';

// по логину и паролю
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_DENY_REG = 'Запретить самостоятельную регистрацию';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_DENY_RECOVERY = 'Запретить самостоятельно восстанавливать пароли';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_CYRILLIC = 'Разрешить кириллицу в логинах';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_SPECIALCHARS = 'Разрешить спецсимволы в логинах';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_CHANGE_LOGIN = 'Разрешить менять логин после регистрации';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_BING_TO_CATALOGUE = 'Привязывать пользователя к сайту';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_WITH_SUBDOMAIN = 'Авторизировать на поддоменах';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_AUTH_CAPTCHA = 'Настройки CAPTCHA при авторизации';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_PASS_MIN = 'Минимальная длина пароля %input символов';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_REGISTRATION_FORM = 'Форма регистрации';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_LOGIN = 'Автоматически проверять доступность логина (может привести к утечке адресов пользователей)';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_PASS = 'Автоматически проверять уровень безопасности пароля';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_PASS2 = 'Автоматически проверять совпадение паролей';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_AGREED = 'Требовать соглашения с пользовательским соглашением';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM = 'Поля в форме регистрации';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM_ALL = 'все';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM_CUSTOM = 'выборочно';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ACTIVATION = 'Активация';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM = 'Требовать подтверждение через почту';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM_AFTER_MAIL = 'Отправлять дополнительное письмо после успешного подтверждения регистрации';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_PREMODARATION = 'Премодерация администратором';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_NOTIFY_ADMIN = 'Отправлять письмо администратору при регистрации пользователя';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_AUTHAUTORIZE = 'Авторизация пользователя сразу после подтверждения';
const NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM_TIME = 'Удалять пользователя, если он не подтвердил регистрацию в течение %input часов';

// через внешние сервисы
const NETCAT_MODULE_AUTH_ADMIN_EX_CURL_REQUIRED = "Для авторизации через внешние сервисы необходима библиотека <a href='http://www.php.net/manual/ru/book.curl.php'>cURL</a>";
const NETCAT_MODULE_AUTH_ADMIN_EX_JSON_REQUIRED = "Для авторизации через внешние сервисы необходима библиотека <a href='http://ru2.php.net/manual/en/book.json.php'>JSON</a>";
const NETCAT_MODULE_AUTH_ADMIN_EX_VK = 'ВКонтакте';
const NETCAT_MODULE_AUTH_ADMIN_EX_FB = 'Facebook';
const NETCAT_MODULE_AUTH_ADMIN_EX_TWITTER = 'Twitter';
const NETCAT_MODULE_AUTH_ADMIN_EX_OPENID = 'OpenID';
const NETCAT_MODULE_AUTH_ADMIN_EX_OAUTH = 'OAuth';
const NETCAT_MODULE_AUTH_ADMIN_EX_VK_ENABLED = 'Включить авторизацию через vkontakte.ru';
const NETCAT_MODULE_AUTH_ADMIN_EX_FB_ENABLED = 'Включить авторизацию через facebook.com';
const NETCAT_MODULE_AUTH_ADMIN_EX_OPENID_ENABLED = 'Включить авторизацию через OpenID';
const NETCAT_MODULE_AUTH_ADMIN_EX_OAUTH_ENABLED = 'Включить авторизацию через OAuth';
const NETCAT_MODULE_AUTH_ADMIN_EX_TWITTER_ENABLED = 'Включить авторизацию через twitter.com';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_VK = 'Данные из ВКонтакте';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_FB = 'Данные из Facebook';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_TWITTER = 'Данные из Twitter';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_OPENID = 'Данные из OpenID';
const NETCAT_MODULE_AUTH_ADMIN_EX_DATA_OAUTH = 'Данные из OAuth';

// общие настройки
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_SITE = 'Способы авторизации на сайте';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_SITE_NO_METHODS_PROVIDED_ERROR = "Должен быть выбран хотя бы один способ авторизации на сайте";
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_ADMIN = 'Способы авторизации в систему администрирования';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_NO_METHODS_PROVIDED_ERROR = "Должен быть выбран хотя бы один способ авторизации в систему администрирования";
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_LOGIN = 'По логину и паролю';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_TOKEN = 'По usb-токену';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_HASH = 'По хэшу';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_EX = 'Через внешние сервисы';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM = 'Личные сообщения';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM_ALLOW = 'Разрешить отправлять личные сообщения';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM_NOTIFY = 'Оповещать пользователя по email о новом сообщение';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_FRIEND_BANNED = 'Друзья и враги';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_FRIEND_ALLOW = 'Разрешить добавлять пользователей в друзья';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_BANNED_ALLOW = 'Разрешить добавлять пользователей во враги';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA = 'Личный счет';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_ALLOW = 'Ввести личный счет';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_CURRENCY = 'Единица измерения %input';
const NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_START = 'Начислять вновь зарегистрированным %input единиц';

// письма
const NETCAT_MODULE_AUTH_ADMIN_MAIL_SUBJECT = 'Заголовок письма';
const NETCAT_MODULE_AUTH_ADMIN_MAIL_BODY = 'Тело письма';
const NETCAT_MODULE_AUTH_ADMIN_MAIL_HTML = 'HTML-письмо';

// системные настройки
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENTS_SUBS = 'Компоненты, разделы и поля';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_FRIENDS = 'Компонент «Список друзей»';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_PM = 'Компонент для личных сообщениях';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_PA = 'Компонент для личного счета';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_FIELD_PA = 'Поле c балансом для личного счета';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_SUB_MATERIALS = 'Раздел с материалами пользователя';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_SUB_MODIFY = 'Разделы для изменения анкеты, через запятую';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_CC_USER_LIST = 'Инфоблок в разделе со списком пользователей';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO = 'Псевдопользователи';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_ALLOW = 'Доступны псевдопользователи';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_CHECK_IP = 'Проверять IP';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_GROUP = 'Группа псевдопользователей';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_FIELD = 'Поле, по которому идет идентификация';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH = 'Хэш-авторизация';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_DELETE = 'Удалять хэш после авторизации';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_EXPIRE = 'Время жизни хэша в часах %input';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_DISABLED_SUBS = 'Номера разделов, где запрещена хэш-авторизация, через запятую %input';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER = 'Прочее';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER_ONLINE = 'Время, в течение которого пользователь считается online в секундах %input';
const NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER_IP = 'Уровень проверки IP-адреса (0-4) %input';
const NETCAT_MODULE_AUTH_ADMIN_NOTIFICATION_EMAIL = 'E-mail для оповещений администратора %input';

const NETCAT_MODULE_AUTH_SELFREG_DISABLED = 'Самостоятельная регистрация запрещена';



const NETCAT_MODULE_AUTH_FORM_TEMPLATES = 'Шаблоны форм';
const NETCAT_MODULE_AUTH_FORM_AUTH = 'Форма авторизации';
const NETCAT_MODULE_AUTH_RESTORE_DEF = 'Восстановить значения по умолчанию';
const NETCAT_MODULE_AUTH_FORM_DISABLED = 'Не показывать форму при неудачной попытке авторизации';
const NETCAT_MODULE_AUTH_FORM_CHG_PASS = 'Форма изменения пароля';
const NETCAT_MODULE_AUTH_FORM_CHG_PASS_AFTER = 'Текст, выводимый после смены пароля';
const NETCAT_MODULE_AUTH_FORM_REC_PASS_AFTER = 'Текст, выводимый после отправки письма';
const NETCAT_MODULE_AUTH_FORM_CHG_PASS_WARNBLOCK = 'Блок вывода ошибки';
const NETCAT_MODULE_AUTH_FORM_CHG_PASS_DENY = 'Текст, выводимый при запрете восстановления пароля';
const NETCAT_MODULE_AUTH_FORM_REC_PASS = 'Форма восстановления пароля';
const NETCAT_MODULE_AUTH_FORM_CONFIRM_AFTER = 'Текст, выводимый при успешном подтверждении регистрации';
const NETCAT_MODULE_AUTH_FORM_CONFIRM_AFTER_WARNBLOCK = 'Блок вывода ошибки при подтверждении регистрации';
const NETCAT_MODULE_AUTH_MAIL_TEMPLATES = 'Шаблоны писем';
const NETCAT_MODULE_AUTH_REG_CONFIRM = 'Подтверждение регистрации';
const NETCAT_MODULE_AUTH_REG_CONFIRM_AFTER = 'Уведомление после подтверждения регистрации';
const NETCAT_MODULE_AUTH_AS_HTML = 'HTML текст';
const NETCAT_MODULE_AUTH_RECOVERY = 'Восстановление пароля';
const NETCAT_MODULE_AUTH_ADMIN_MAIL_NOTIFY = 'Оповещение администратора о регистрации пользователя';

const NETCAT_MODULE_AUTH_ADD_FRIEND = 'Добавить в друзья';
const NETCAT_MODULE_AUTH_REMOVE_FRIEND = 'Удалить из друзей';

// OpenID
const NETCAT_MODULE_AUTH_OPEN_ID_ERROR = 'Неизвестный OpenID';
const NETCAT_MODULE_AUTH_OPEN_ID_INVALID = 'Неправильный OpenID';
const NETCAT_MODULE_AUTH_OPEN_ID_ALREADY_EXIST_IN_BASE = 'Такой OpenID уже зарегистрирован в базе';
const NETCAT_MODULE_AUTH_OPEN_ID_COULD_NOT_REDIRECT_TO_SERVER = 'Could not redirect to server: %s';
const NETCAT_MODULE_AUTH_OPEN_ID_CHECK_CANCELED = 'Проверка отменена';
const NETCAT_MODULE_AUTH_OPEN_ID_AUTH_FAILED = 'OpenID авторизация не удалась: %s';
const NETCAT_MODULE_AUTH_OPEN_ID_AUTH_COMPLETE_NAME = "Вы успешно авторизовались как <a href='%s'>%s</a> ";
const NETCAT_MODULE_AUTH_OPEN_ID_AUTH_COMPLETE_LOGIN = 'Ваш логин на сайте: %s';


const NETCAT_MODULE_AUTH_SETUP_PROFILE = 'Личный кабинет';
const NETCAT_MODULE_AUTH_SETUP_REGISTRATION = 'Регистрация';
const NETCAT_MODULE_AUTH_SETUP_PASSWORD_RECOVERY = 'Восстановление пароля';
const NETCAT_MODULE_AUTH_SETUP_MODIFY = 'Изменение анкеты';
const NETCAT_MODULE_AUTH_SETUP_PASSWORD = 'Изменение пароля';
const NETCAT_MODULE_AUTH_SETUP_PM = 'Личные сообщения';


const NETCAT_MODULE_AUTH_APPLICATION_ID = 'ID приложения';
const NETCAT_MODULE_AUTH_APPLICATION_ID_VK = 'ID приложения';
const NETCAT_MODULE_AUTH_APPLICATION_ID_FB = 'ID приложения (Application ID)';
const NETCAT_MODULE_AUTH_APPLICATION_ID_TWITTER = 'ID приложения (Consumer key)';
const NETCAT_MODULE_AUTH_SECRET_KEY = 'Защищенный ключ';
const NETCAT_MODULE_AUTH_PUBLIC_KEY = 'Публичный ключ';
const NETCAT_MODULE_AUTH_SECRET_KEY_VK = 'Защищенный ключ';
const NETCAT_MODULE_AUTH_SECRET_KEY_FB = 'Защищенный ключ (Application Secret)';
const NETCAT_MODULE_AUTH_SECRET_KEY_TWITTER = 'Защищенный ключ (Consumer secret)';

const NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE = 'Группы, куда попадёт пользователь';
const NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE_EMPTY = 'Не выбрано ни одной группы!';
const NETCAT_MODULE_AUTH_ACTION_BEFORE_FIRST_AUTHORIZATION = 'Действие до первой авторизации пользователя';
const NETCAT_MODULE_AUTH_ACTION_AFTER_FIRST_AUTHORIZATION = 'Действие после первой авторизации пользователя';
const NETCAT_MODULE_AUTH_ACTION_FIELDS_MAPPING = 'Соответствие полей';
const NETCAT_MODULE_AUTH_ACTION_FIELDS_MAPPING_ADD = 'Добавить соответствие';
const NETCAT_MODULE_AUTH_PROVIDER_ADD = 'Добавить провайдера';
const NETCAT_MODULE_AUTH_PROVIDER = 'Провайдер';
const NETCAT_MODULE_AUTH_PROVIDER_ICON = 'Иконка провайдера';


const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_SUBJECT = 'Подтверждение регистрации на сайте %SITE_NAME';
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_BODY =
"Здравствуйте, %USER_LOGIN

Вы успешно зарегистрировались на сайте <a href='%SITE_URL'>%SITE_NAME</a>
Ваш логин: 	%USER_LOGIN
Ваш пароль: 	%PASSWORD

Чтобы активировать Ваш аккаунт откройте, пожалуйста, данную ссылку:

<a href='%CONFIRM_LINK'>%CONFIRM_LINK</a>

Вы получили это сообщение, потому что Ваш email адрес был зарегистрирован на сайте %SITE_URL
Если Вы не регистрировались на этом сайте, пожалуйста, проигнорируйте это письмо.

С наилучшими пожеланиями, администрация сайта <a href='%SITE_URL'>%SITE_NAME</a>.
";

const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_AFTER_SUBJECT = 'Регистрация на сайте %SITE_NAME успешно подтверждена';
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_AFTER_BODY =
"Здравствуйте, %USER_LOGIN

Ваш аккаунт успешно активирован, теперь вы можете в полной мере использовать сервисы нашего сайта.

С наилучшими пожеланиями, администрация сайта <a href='%SITE_URL'>%SITE_NAME</a>.
";

const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_PASSWORDRECOVERY_SUBJECT = 'Восстановление пароля на сайте %SITE_NAME';
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_PASSWORDRECOVERY_BODY =
"Здравствуйте, %USER_LOGIN

Для восстановления пароля для пользователя %USER_LOGIN на сайте <a href='%SITE_URL'>%SITE_NAME</a> откройте, пожалуйста, данную ссылку:

<a href='%CONFIRM_LINK'>%CONFIRM_LINK</a>

Если Вы не запрашивали восстановление пароля, пожалуйста, проигнорируйте это письмо.

С наилучшими пожеланиями, администрация сайта <a href='%SITE_URL'>%SITE_NAME</a>.
";
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_ADMIN_NOTIFY_SUBJECT = 'Новый пользователь на сайте %SITE_NAME';
const NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_ADMIN_NOTIFY_BODY =
"Здравствуйте, администратор.

На сайте <a href='%SITE_URL'>%SITE_NAME</a> зарегистрирован новый пользователь с логином %USER_LOGIN

С наилучшими пожеланиями, сайт <a href='%SITE_URL'>%SITE_NAME</a>.
";
const NETCAT_MODULE_AUTH_USER_AGREEMENT = "Я принимаю условия <a href='%USER_AGR' target='_blank'>пользовательского соглашения</a>";
const NETCAT_MODULE_AUTH_AUTHENTICATION_FAILED = 'Возникла ошибка во время аутентификации.';
const NETCAT_MODULE_AUTH_RETRY = 'Пожалуйста, обновите страницу и попробуйте еще раз, сейчас или позже.';

// названия создаваемых разделов
const NETCAT_MODULE_AUTH_PROFILE_SUBDIVISION_NAME = 'Личный кабинет';
const NETCAT_MODULE_AUTH_EDIT_PROFILE_SUBDIVISION_NAME = 'Мой профиль';
const NETCAT_MODULE_AUTH_CHANGE_PASS_SUBDIVISION_NAME = 'Сменить пароль';
const NETCAT_MODULE_AUTH_RECOVERY_PASS_SUBDIVISION_NAME = 'Восстановление пароля';
const NETCAT_MODULE_AUTH_REGISTRATION_SUBDIVISION_NAME = 'Регистрация';
const NETCAT_MODULE_AUTH_REG_ADMIN = 'Ожидайте активации Вашего аккаунта администратором';
const NETCAT_MODULE_AUTH_REG_AGAIN = 'Ошибка! Регистрация уже была подтверждена ранее';
