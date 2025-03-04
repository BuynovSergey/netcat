<?php

const NETCAT_MODULE_SUBSCRIBES = 'Подписки и рассылки';
const NETCAT_MODULE_SUBSCRIBE_DESCRIPTION = 'Возможность для зарегистрированных пользователей осуществлять подписку на обновления разделов.';

const NETCAT_MODULE_SUBSCRIBE_ADM_GETUSERS = 'Выборка (поиск)';
const NETCAT_MODULE_SUBSCRIBE_ADM_ALLUSERS = 'все';
const NETCAT_MODULE_SUBSCRIBE_ADM_TURNEDON = 'вкл.';
const NETCAT_MODULE_SUBSCRIBE_ADM_TURNEDOFF = 'выкл.';
const NETCAT_MODULE_SUBSCRIBE_ADM_USERID = 'По ID пользователя';
const NETCAT_MODULE_SUBSCRIBE_ADM_CLASSID = 'По ID шаблона в разделе';
const NETCAT_MODULE_SUBSCRIBE_BUT_GETIT = 'Выбрать';
const NETCAT_MODULE_SUBSCRIBE_ADM_CLASSINSECTION = 'Шаблон в разделе';
const NETCAT_MODULE_SUBSCRIBE_ADM_USER = 'Пользователь';
const NETCAT_MODULE_SUBSCRIBE_ADM_STATUS = 'Статус';
const NETCAT_MODULE_SUBSCRIBE_ADM_DELETE = 'Удалить';
const NETCAT_MODULE_SUBSCRIBE_ADM_CANCEL = 'Отменить';
const NETCAT_MODULE_SUBSCRIBE_ADM_SAVE = 'Удалить выбранное';
const NETCAT_MODULE_SUBSCRIBE_MSG_NOSUBSCRIBER = 'Не найдено ни одного подписчика соответствующего вашему запросу.';

const NETCAT_MODULE_SUBSCRIBE_ADM_TURNON = 'включить';
const NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFF = 'выключить';
const NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFFCLR = 'cccccc';

const NETCAT_MODULE_SUBSCRIBE_ADM_ACCESSRIGHTS = 'права доступа';
const NETCAT_MODULE_SUBSCRIBE_ADM_PASSCHANGE = 'Смена пароля';
const NETCAT_MODULE_SUBSCRIBE_ADM_SECSITE = 'Выбор каталога';
const NETCAT_MODULE_SUBSCRIBE_ADM_SECSEL = 'Выбор раздела';
const NETCAT_MODULE_SUBSCRIBE_ADM_ADDING = 'Добавление';
const NETCAT_MODULE_SUBSCRIBE_ADM_SECTION = 'раздел';
const NETCAT_MODULE_SUBSCRIBE_SUCCESS = 'Подписка осуществлена успешно.';

const NETCAT_MODULE_SUBSCRIBER_ALREADY_SUBSCRIBE = 'Пользователь на данную рассылку уже подписан.';
const NETCAT_MODULE_SUBSCRIBER_CONFIRM_SENT_AGAIN = 'Письмо с подтверждением подписки Вам выслано повторно.';
const NETCAT_MODULE_SUBSCRIBER_MAILER_DOES_NOT_EXIST = 'Рассылка не существует.';
const NETCAT_MODULE_SUBSCRIBER_NOT_SUB_FOR_CC = 'Нет раздела для компонента раздела';
const NETCAT_MODULE_SUBSCRIBER_WRONG_EMAIL = 'Некорректный E-mail';


//main
const NETCAT_MODULE_SUBSCRIBER_MAILER_NAME = 'Название рассылки';
const NETCAT_MODULE_SUBJECT = 'Тема письма';
const NETCAT_MODULE_HTML_MAIL = 'HTML-письмо';
const NETCAT_MODULE_SUBSCRIBER_USER = 'Пользователь';
const NETCAT_MODULE_SUBSCRIBE_STATUS = 'Статус';
const NETCAT_MODULE_SUBSCRIBE_MAILER = 'Рассылка';

// settings
const NETCAT_MODULE_SUBSCRIBER_MAILER_SETTINGS = 'Настройки рассылок';
const NETCAT_MODULE_SUBSCRIBER_MERGE_MAIL = 'Разрешить объединение писем из различных разделов';
const NETCAT_MODULE_SUBSCRIBER_MAX_MAIL = 'Максимальное количество писем, отправляемых за один запуск скрипта';
const NETCAT_MODULE_SUBSCRIBER_FROM_NAME = 'Имя отправителя';
const NETCAT_MODULE_SUBSCRIBER_FROM_EMAIL = 'Email отправителя';
const NETCAT_MODULE_SUBSCRIBER_REPLY_TO = 'Обратный адрес (reply to)';
const NETCAT_MODULE_SUBSCRIBER_TEST_EMAIL = 'Тестовый адрес для рассылок';
const NETCAT_MODULE_SUBSCRIBER_CHARSET = 'Кодировка письма';
const NETCAT_MODULE_SUBSCRIBER_EMAIL_FIELD = "Поле из таблицы «Пользователи» с Email";
define("NETCAT_MODULE_SUBSCRIBER_NONE_EMAIL_FIELD", "Нет поля с email.<br/> <a href='" . $nc_core->ADMIN_PATH . "field/index.php?phase=2&isSys=1&SystemTableID=3'>Создайте</a> поле в системной таблице Пользователи типа \"Строка\" с форматом \"email\". ");
const NETCAT_MODULE_SUBSCRIBER_SUBSCRIPTION_CONFIRM = 'Подтверждение подписки';
const NETCAT_MODULE_SUBSCRIBER_ONLY_UNREGISTERED = 'только незарегистрированным';
const NETCAT_MODULE_SUBSCRIBER_FOR_ONE_SUBSCRIPTION = 'при первой подписке';
const NETCAT_MODULE_SUBSCRIBER_ALWAYS = 'всегда';
const NETCAT_MODULE_SUBSCRIBER_UNCONFIRMED_MAX_TIME = 'Удалять неподтвержденную подписку через (в часах)';
const NETCAT_MODULE_MAIL_BODY = 'Тело письма';
const NETCAT_MODULE_SUBSCRIBER_OTHER = 'Прочее';
const NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE_FORM = 'Форма подписки для незарегистрированного пользователя';
const NETCAT_MODULE_SUBSCRIBER_TEXT_CONFIRM = 'Текст, выводимый при подтверждении подписки';
const NETCAT_MODULE_SUBSCRIBER_TEXT_UNSCRIBE = 'Текст, выводимый при отписке';
const NETCAT_MODULE_SUBSCRIBER_TEXT_ERROR = 'Текст, выводимый при ошибке';

//ui
const NETCAT_MODULE_SUBSCRIBER_SAVE_BUTTON = 'Сохранить';
const NETCAT_MODULE_SUBSCRIBER_CLEAR_BUTTON = 'Очистить';
const NETCAT_MODULE_SUBSCRIBER_MAILER_ADD = 'Добавить рассылку';
const NETCAT_MODULE_SUBSCRIBER_DELETESELECTED = 'Удалить выбранные';
const NETCAT_MODULE_SUBSCRIBER_SELECTEDOFF = 'Выключить выбранные';
const NETCAT_MODULE_SUBSCRIBER_SELECTEDON = 'Включить выбранные';
const NETCAT_MODULE_SUBSCRIBE_ADD_SUBSCRIBE = 'Добавить подписку';
const NETCAT_MODULE_SUBSCRIBE_ACTION_SUBSCRIBE = 'Подписать';
const NETCAT_MODULE_SUBSCRIBE_SEND = 'Послать';
const NETCAT_MODULE_SUBSCRIBE_TESTSEND = 'Послать тестово';
const NETCAT_MODULE_SUBSCRIBE_MAILERS = 'Рассылки';
const NETCAT_MODULE_SUBSCRIBE_STATS = 'Статистика';
const NETCAT_MODULE_SUBSCRIBE_ONCE = 'Разовая рассылка';
const NETCAT_MODULE_SUBSCRIBE_SETTINGS = 'Настройки модуля';

//stats
const NETCAT_MODULE_SUBSCRIBER_STATS_IS_EMPTY = 'Статистика пуста';
const NETCAT_MODULE_SUBSCRIBER_SUBSCRIBERS_COUNT = 'Подписчиков';
const NETCAT_MODULE_SUBSCRIBER_MAIL_SEND_COUNT = 'Выслано писем';
const NETCAT_MODULE_SUBSCRIBER_LAST_SEND = 'Последняя рассылка';
const NETCAT_MODULE_SUBSCRIBER_ACTION = 'Действие';
const NETCAT_MODULE_SUBSCRIBER_DATE_TIME = 'Дата и время';
const NETCAT_MODULE_SUBSCRIBER_UNRESISTRED_USER = 'Незарегистрированный пользователь';
const NETCAT_MODULE_SUBSCRIBER_MAIL_SEND = 'отправка письма';
const NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE = 'подписка';
const NETCAT_MODULE_SUBSCRIBER_CONFIRM = 'подтверждение';
const NETCAT_MODULE_SUBSCRIBER_UNSUBSCRIBE = 'отписка';
const NETCAT_MODULE_SUBSCRIBER_FULL_STATS_MAILER = 'Подробная статистика рассылки';

// mailer
const NETCAT_MODULE_MAILER_NO_ONE_MAILER = 'Не найдено ни одной рассылки';
const NETCAT_MODULE_SUBSCRIBER_MAILER_FILTER = 'Выборка рассылок';
const NETCAT_MODULE_SUBSCRIBER_MAILER_FILTER_TYPE = 'Тип';
const NETCAT_MODULE_SUBSCRIBER_MAILER_FILTER_STATUS = 'Статус';
const NETCAT_MODULE_SUBSCRIBER_ALL = 'все';
const NETCAT_MODULE_SUBSCRIBER_MAILER_CC = 'подписка на раздел';
const NETCAT_MODULE_SUBSCRIBER_MAILER_PERIODICAL = 'регулярная рассылка';
const NETCAT_MODULE_SUBSCRIBER_MAILER_SERVICE = 'сервисная рассылка';
const NETCAT_MODULE_SUBSCRIBER_MAILER_SERIAL = 'cерийная рассылка';
const NETCAT_MODULE_SUBSCRIBER_ONLY_ACTIVE = 'только активные';
const NETCAT_MODULE_SUBSCRIBER_ONLY_UNACTIVE = 'только неактивные';
const NETCAT_MODULE_SUBSCRIBER_FILTER = 'Выбрать';
const NETCAT_MODULE_SUBSCRIBER_TYPE = 'Тип';
const NETCAT_MODULE_SUBSCRIBER_SETTINGS = 'Настройки';
const NETCAT_MODULE_SUBSCRIBER_MAIN_SETTINGS = 'Основные настройки';
const NETCAT_MODULE_SUBSCRIBER_ACCESS_TO = 'Доступно';
const NETCAT_MODULE_SUBSCRIBER_ACCESS_ALL = 'всем';
const NETCAT_MODULE_SUBSCRIBER_ACCESS_REGISTERED = 'зарегистрированным';
const NETCAT_MODULE_SUBSCRIBER_ACCESS_AUTHORIZED = 'уполномоченным';
const NETCAT_MODULE_SUBSCRIBER_ACTIVE = 'Активна';
const NETCAT_MODULE_SUBSCRIBER_IN_STAT = 'Сохранять подробную статистику';
const NETCAT_MODULE_SUBSCRIBER_SPECIFIC_SETTINGS = 'Настройки рассылки';
const NETCAT_MODULE_SUBSCRIBER_SITE = 'Сайт';
const NETCAT_MODULE_SUBSCRIBER_SUBDIVISION = 'Раздел';
const NETCAT_MODULE_SUBSCRIBER_CC = 'Компонент в разделе';
const NETCAT_MODULE_SUBSCRIBER_ONCLASS = 'Подписка на компонент';
const NETCAT_MODULE_SUBSCRIBER_ADD_OBJECT_TO_MAILLIST = 'Добавлять объект в рассылку при';
const NETCAT_MODULE_SUBSCRIBER_ACTION_ADD_ON = 'добавление включенного';
const NETCAT_MODULE_SUBSCRIBER_ACTION_ADD_OFF = 'добавление выключенного';
const NETCAT_MODULE_SUBSCRIBER_ACTION_EDIT_ON = 'изменение включенного';
const NETCAT_MODULE_SUBSCRIBER_ACTION_EDIT_OFF = 'изменение выключенного';
const NETCAT_MODULE_SUBSCRIBER_ACTION_ON = 'включение';
const NETCAT_MODULE_SUBSCRIBER_ACTION_OFF = 'выключение';
const NETCAT_MODULE_SUBSCRIBER_COND_AND_ACTION = 'Условия и действия';
const NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE_COND = 'Условие подписки';
const NETCAT_MODULE_SUBSCRIBER_SEND_COND = 'Условие рассылки';
const NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE_ACTION = 'Действие после подписки';
const NETCAT_MODULE_SUBSCRIBER_MAIL_TEMPLATE = 'Шаблон письма';
const NETCAT_MODULE_SUBSCRIBER_MAIL_TEMPLATE_HEADER = 'Верхняя часть (хэдер)';
const NETCAT_MODULE_SUBSCRIBER_MAIL_TEMPLATE_FOOTER = 'Нижняя часть (футер)';
const NETCAT_MODULE_SUBSCRIBER_MAIL_TEMPLATE_CONTENT = 'Содержательная часть';
const NETCAT_MODULE_SUBSCRIBER_MAILER_TYPE = 'Тип рассылки';
const NETCAT_MODULE_SUBSCRIBER_MAILER_PERIOD = 'Период рассылки';
const NETCAT_MODULE_SUBSCRIBER_CONFIRM_REMOVE_MAILERS = 'Подтвердите удаление рассылок';
const NETCAT_MODULE_SUBSCRIBER_NO_SUBSCRIBERS = 'Нет подписчиков';
const NETCAT_MODULE_SUBSCRIBER_MAILER_SUBSCRIBER_LIST = 'Список подписчиков рассылки';
const NETCAT_MODULE_SUBSCRIBE_TURNEDON = 'включен';
const NETCAT_MODULE_SUBSCRIBE_TURNEDOFF = 'выключен';
const NETCAT_MODULE_SUBSCRIBE_WAIT_CONFIRM = 'требуется подтверждение';
const NETCAT_MODULE_SUBSCRIBE_UNREGISTERED_USER = 'Незарегистрированный пользователь';
const NETCAT_MODULE_SUBSCRIBE_USER_NOT_SUBSCRIBE = 'Пользователь не подписан ни на одну рассылку.';
const NETCAT_MODULE_SUBSCRIBE_NONE_MAILERS_FOR_USER = 'Нет рассылок, на которые пользователь может подписаться.';
const NETCAT_MODULE_SUBSCRIBE_SELECT_MAILER = 'Выберите рассылку';
const NETCAT_MODULE_SUBSCRIBER_NONE_CC = 'Нет компонентов в разделе.';
const NETCAT_MODULE_SUBSCRIBER_MAILER_SUCCESS_EDIT = 'Подписка успешно изменена';
const NETCAT_MODULE_SUBSCRIBER_MAILER_SUCCESS_ADD = 'Подписка успешно добавлена';
const NETCAT_MODULE_SUBSCRIBER_STATS_DROP = 'Статистика успешно удалена';
const NETCAT_MODULE_SUBSCRIBER_SETTINGS_OK = 'Настройки успешно изменены';
const NETCAT_MODULE_SUBSCRIBER_USER_NOT_FOUND = 'Не найдено ни одного пользователя';
const NETCAT_MODULE_SUBSCRIBER_MAIL_SEND_TO_USER = 'Рассылка запущена. Письмо будет отправлено %d пользователям.';

// Разовая рассылка
const NETCAT_MODULE_SUBSCRIBER_ONCE_TYPE_USER = 'Типы пользователя';
const NETCAT_MODULE_SUBSCRIBER_ONCE_REGISTRED = 'зарегистрированные';
const NETCAT_MODULE_SUBSCRIBER_ONCE_ALL = 'все имеющиеся в базе';
const NETCAT_MODULE_SUBSCRIBER_USER_CHECK = 'Активность пользователя';
const NETCAT_MODULE_SUBSCRIBER_USER_CHECK_ALL = 'все';
const NETCAT_MODULE_SUBSCRIBER_USER_CHECKED = 'включенные';
const NETCAT_MODULE_SUBSCRIBER_USER_NONECHECK = 'выключенные';
const NETCAT_MODULE_SUBSCRIBER_USER_ATTACH = 'Прикрепленные файлы';
const NETCAT_MODULE_SUBSCRIBER_FILE_1 = 'Первый файл';
const NETCAT_MODULE_SUBSCRIBER_FILE_2 = 'Второй файл';
const NETCAT_MODULE_SUBSCRIBER_FILE_3 = 'Третий файл';
const NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE_USER = 'Подписанные на рассылку';
const NETCAT_MODULE_SUBSCRIBER_ONCE_INVALID_TEST_EMAIL = 'Неверно задан тестовый адрес для рассылок в настройках модуля';
const NETCAT_MODULE_SUBSCRIBER_ONCE_MAIL_SEND = 'Письмо на адрес %s отправлено.';
const NETCAT_MODULE_SUBSCRIBER_ONCE_CLASSIFIER_NAME = "Использовать системный список e-mail'ов (исключает выборку пользователей)";
const NETCAT_MODULE_SUBSCRIBER_ONCE_CLASSIFIER_WRONGNAME = 'Название таблицы(классификатора) должно начинаться с буквы и содержать только латинские буквы и цифры';
const NETCAT_MODULE_SUBSCRIBER_ONCE_CLASSIFIER_EMPTY = 'Классификатор пуст или не существует';

function day_of_week_name($day) {
    $day_names = array(1=>'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            0 => 'Воскресенье');
    return $day_names[$day];
}

const NETCAT_MODULE_SUBSCRIBER_PERIOD_WEEKLY = 'Рассылать еженедельно в дни';
const NETCAT_MODULE_SUBSCRIBER_PERIOD_WEEKLY_ON = 'Вкл';
const NETCAT_MODULE_SUBSCRIBER_PERIOD_WEEKLY_DAY = 'День';
const NETCAT_MODULE_SUBSCRIBER_PERIOD_WEEKLY_TIME = 'Время старта';
const NETCAT_MODULE_SUBSCRIBER_PERIOD_MONTHLY = 'Отправка по формуле';

const NETCAT_MODULE_SUBSCRIBER_PERIOD_HELP =
    "С помощью &quot;Отправки по формуле&quot; вы можете создавать гибкие расписания рассылок.<br/><br/>
    Правила формулы состоят из префикса, обозначения дня и времени старта рассылки.<br/>
    Префикс позволяет задавать относительную точку отсчёта (начало месяца, конец месяца), а также указывать конкретный день месяца:<br/>
    &nbsp;&nbsp;&nbsp;<b>first</b> - от начала месяца, <b>last</b> - от конца месяца, <b>15</b> - день месяца<br/>
    Обозначение дня указывает в какой день недели выполнять рассылку<br/>
    &nbsp;&nbsp;&nbsp;<b>day</b> - любой день недели<br/>
    &nbsp;&nbsp;&nbsp;<b>wday</b> - любой будний день<br/>
    &nbsp;&nbsp;&nbsp;<b>mon, tue, wed, thu, fri, sat</b> или <b>sun</b> - конкретный день недели<br/>
    Правила перечисляются через запятую:<br/>
    &nbsp;&nbsp;&nbsp;<b>first thu 10, 15 day 17 , last day 23</b> - разослать в первый четверг в 10 часов, 15 числа в 17 часов и в последний день месяца(28, 30 или 31 в зависимости от месяца) в 23 часа.";

const NETCAT_MODULE_SUBSCRIBER_STATS_NOTHING = 'По заданным условиям ничего не найдено';
const NETCAT_MODULE_SUBSCRIBER_STATS_FROM = 'Дата события от';
const NETCAT_MODULE_SUBSCRIBER_STATS_TO = 'до';
const NETCAT_MODULE_SUBSCRIBER_STATS_FILTER = 'Выборка статистики';
const NETCAT_MODULE_SUBSCRIBER_PERIOD_EACH = 'рассылать';
