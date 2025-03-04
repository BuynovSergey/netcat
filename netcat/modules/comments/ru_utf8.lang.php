<?php

const NC_PERM_MODULE_COMMENTS_COMMENT_MANAGE = 'управление комментариями';
const NC_PERM_MODULE_COMMENTS_SUBSCRIPTION_MANAGE = 'управление подписками';

// main
const NETCAT_MODULE_COMMENTS_GUEST = 'Гость';
const NETCAT_MODULE_COMMENTS_DESCRIPTION = 'Данный модуль предназначен для подключения функционала комментариев.';
// links
const NETCAT_MODULE_COMMENTS_LINK_REPLY = 'Ответить';
const NETCAT_MODULE_COMMENTS_LINK_EDIT = 'Редактировать';
const NETCAT_MODULE_COMMENTS_LINK_DELETE = 'Удалить';
const NETCAT_MODULE_COMMENTS_LINK_COMMENT = 'Написать отзыв';
const NETCAT_MODULE_COMMENTS_SUBSCRIBE_TO_ALL = 'Подписаться';
const NETCAT_MODULE_COMMENTS_UNSUBSCRIBE_FROM_ALL = 'Отписаться';
const NETCAT_MODULE_COMMENTS_LINK_AUTH = 'Войти';
const NETCAT_MODULE_COMMENTS_MODERATOR_CLOSE = 'Запретить комментарии';
const NETCAT_MODULE_COMMENTS_MODERATOR_OPEN = 'Разрешить комментарии';
const NETCAT_MODULE_COMMENTS_RATING_NOTIFY = 'Ваш голос учтен';

const NETCAT_MODULE_COMMENTS_ADD_FORM_DELETE_QUESTION = 'Удалить этот комментарий?';

// errors
const NETCAT_MODULE_COMMENTS_NO_ACCESS = 'Недостаточно прав для осуществления операции!';
const NETCAT_MODULE_COMMENTS_UNCORRECT_DATA = 'Неверный формат данных!';

const NETCAT_MODULE_COMMENTS_ADD_FORM_ADD_BUTTON = 'Добавить';
const NETCAT_MODULE_COMMENTS_ADD_FORM_SUBSCRIBE_BUTTON = 'Подписать';
const NETCAT_MODULE_COMMENTS_ADD_FORM_APPEND_BUTTON = 'Сохранить';
const NETCAT_MODULE_COMMENTS_ADD_FORM_UPDATE_BUTTON = 'Обновить';
const NETCAT_MODULE_COMMENTS_ADD_FORM_DELETE_BUTTON = 'Удалить';
const NETCAT_MODULE_COMMENTS_ADD_FORM_CANCEL_BUTTON = 'Отменить';
const NETCAT_MODULE_COMMENTS_ADD_FORM_LOADING_TEXT = 'Подождите, идёт загрузка...';

// admin interface
const NETCAT_MODULE_COMMENTS_ADMIN_MAINSETTINGS_SAVE_BUTTON = 'Сохранить';

//tabs
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_LIST_TAB = 'Список комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_TEMPLATE_TAB = 'Шаблон вывода';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_TEMPLATE_MAIN = 'Общие настройки';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SUBSCRIBE_TAB = 'Подписки и оповещения';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_CONVERTER_TAB = 'Конвертер из старых версий';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_OPTIMIZE_TAB = 'Оптимизация данных';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SUBSCRIBES = 'Управление подписками';

//comments list
const NETCAT_MODULE_COMMENTS_ADMIN_CHECK = 'включить';
const NETCAT_MODULE_COMMENTS_ADMIN_CHECK_OK = 'Комментарий включен';
const NETCAT_MODULE_COMMENTS_ADMIN_CHECK_COMMENTS_OK = 'Выбранные комментарии включены';
const NETCAT_MODULE_COMMENTS_ADMIN_UNCHECK = 'выключить';
const NETCAT_MODULE_COMMENTS_ADMIN_UNCHECK_OK = 'Комментарий выключен';
const NETCAT_MODULE_COMMENTS_ADMIN_UNCHECK_COMMENTS_OK = 'Выбранные комментарии выключены';
const NETCAT_MODULE_COMMENTS_ADMIN_NO_SELECTED_COMMENTS = 'Комментарии не выбраны';
const NETCAT_MODULE_COMMENTS_ADMIN_EDIT = 'изменить';
const NETCAT_MODULE_COMMENTS_ADMIN_EDIT_OK = 'Комментарий успешно изменен';
const NETCAT_MODULE_COMMENTS_ADMIN_DEL_OK = 'Выбранные комментарии удалены';
const NETCAT_MODULE_COMMENTS_ADMIN_DEL_ALL_OK = 'Все комментарии удалены';
const NETCAT_MODULE_COMMENTS_ADMIN_DEL_BACK = 'Назад';

//filter
const NETCAT_MODULE_COMMENTS_ADMIN_COMMENTS_LIST_SELECT = 'Выборка комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_COMMENTS_SUBSCRIBES_SELECT = 'Выборка подписок';
const NETCAT_MODULE_COMMENTS_ADMIN_NO_COMMENTS = 'Нет комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_NO_SUBSCRIBES = 'Нет подписок';
const NETCAT_MODULE_COMMENTS_ADMIN_ESSENCE = 'Сущность';
const NETCAT_MODULE_COMMENTS_ADMIN_USER = 'Пользователь';
const NETCAT_MODULE_COMMENTS_ADMIN_CATALOGUE = 'Сайт';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBDIVISION = 'Раздел';
const NETCAT_MODULE_COMMENTS_ADMIN_CC = 'Инфоблок';
const NETCAT_MODULE_COMMENTS_ADMIN_MESSAGE = 'Объект';
const NETCAT_MODULE_COMMENTS_ADMIN_NONE_CC = 'Нет инфоблоков';
const NETCAT_MODULE_COMMENTS_ADMIN_NONE_SUB = 'Нет разделов';
const NETCAT_MODULE_COMMENTS_ADMIN_NONE_CATALOGUE = 'Нет сайтов';
const NETCAT_MODULE_COMMENTS_ADMIN_WHOLESUB = 'с подразделами';
const NETCAT_MODULE_COMMENTS_ADMIN_LOAD = '- Загрузка... -';
const NETCAT_MODULE_COMMENTS_ADMIN_CLASS = 'По компоненту';
const NETCAT_MODULE_COMMENTS_ADMIN_ALLUSERS = 'все';
const NETCAT_MODULE_COMMENTS_ADMIN_ONUSERS = 'включенные';
const NETCAT_MODULE_COMMENTS_ADMIN_OFFUSERS = 'выключенные';
const NETCAT_MODULE_COMMENTS_ADMIN_DOGET = 'Выбрать';
const NETCAT_MODULE_COMMENTS_ADMIN_QTY = 'Количество на странице';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBS_QTY = 'Количество подписок на страницу';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBERS_USER = 'Подписчик';
const NETCAT_MODULE_COMMENTS_ADMIN_FROM_LIST = 'Из списка';
const NETCAT_MODULE_COMMENTS_ADMIN_NO_HIERARHY = 'Показывать подписки только для выбранной сущности';
const NETCAT_MODULE_COMMENTS_ADMIN_DATE = 'Дата';
const NETCAT_MODULE_COMMENTS_ADMIN_DATE_FROM = 'За период с ';
const NETCAT_MODULE_COMMENTS_ADMIN_DATE_TO = ' по ';

const NETCAT_MODULE_COMMENTS_ADMIN_CONFIRM_DEL = 'Подтвердите удаление';
const NETCAT_MODULE_COMMENTS_ADMIN_CONFIRM_DEL_ALL = 'Подтвердите удаление всех комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_CONFIRM_DEL_OK = 'Подтверждаю';

//edit
const NETCAT_MODULE_COMMENTS_ADMIN_EDIT_TEXT = 'Текст комментария';
const NETCAT_MODULE_COMMENTS_ADMIN_EDIT_AUTHOR = 'Автор комментария:';
const NETCAT_MODULE_COMMENTS_ADMIN_LIST_AUTHOR = 'Автор';
const NETCAT_MODULE_COMMENTS_ADMIN_LIST_CHECK_ALL = 'Отметить все';
const NETCAT_MODULE_COMMENTS_ADMIN_EDIT_AUTHOR_USER = 'Пользователь:';
const NETCAT_MODULE_COMMENTS_ADMIN_EDIT_AUTHOR_NAME = 'Имя гостя:';
const NETCAT_MODULE_COMMENTS_ADMIN_EDIT_AUTHOR_EMAIL = 'Email гостя:';
const NETCAT_MODULE_COMMENTS_ADMIN_EDIT_SAVE_EMAIL_ERROR = 'Неправильно введен email';
const NETCAT_MODULE_COMMENTS_ADMIN_EDIT_SAVE_OK = 'Изменения сохранены.';


//converter
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERTER_RETURN_BUTTON = 'Вернуться';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERTER_SAVE_BUTTON_4 = 'Выбрать каталог';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERTER_SAVE_BUTTON_5 = 'Выбрать раздел';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERTER_SAVE_BUTTON_6 = 'Конвертировать';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERTER_SELECT_CATALOGUE = 'Выбор каталога';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERTER_SELECT_SUBDIVISION = 'Выбор раздела';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERTER_SELECT_SUBCLASS = 'Выбор компонента в разделе';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERTER_DIALOG = 'Диалог выбора';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_OK = 'Комментарии переконвертированы.';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_ERROR = 'Во время конвертирования возникла ошибка.';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_PARENT_ERROR = 'Во время конвертирования возникла ошибка обновления ответов на комментарии. Возможно в базе есть дублирующаяся информация.';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_DATA_ERROR = 'Неверные входные данные, попробуйте ещё раз.';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_CLASS_ERROR = 'Ошибка компонента.';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_NO_CATALOGUE_ERROR = 'Не найдено ни одного сайта.';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_NO_SUBDIVISION = 'Не найдено ни одного раздела на сайте.';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_NO_SUBCLASS = 'В этом разделе нет компонентов.';
const NETCAT_MODULE_COMMENTS_ADMIN_CONVERT_NO_DATA = 'Объекты для конвертирования отсутствуют.';

//settings
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_MAIN_SETTINGS = 'Основные настройки';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_USER_NAME = 'Поле из таблицы «Пользователи», используемое для имени';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_USER_AVATAR = 'Поле из таблицы «Пользователи», используемое для аватара';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_USER_AVATAR_NO = 'Не использовать';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_GUEST_USER_NAME = 'Разрешить неавторизированному пользователю вводить имя';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_GUEST_USER_EMAIL = 'Разрешить неавторизированному пользователю вводить email';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_GUEST_USER_NEED = 'Обязательно';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_QTY = 'Количество комментариев на страницу';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_SHOW_ALL = 'Отображать кнопку «Показать все»';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_ORDER_DESC = 'Обратная сортировка комментариев (новые сверху)';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_BBCODE = 'Использовать bb-коды';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_USE_CAPTCHA = 'Использовать captcha';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_RATING = 'Включить рейтинг комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_PREMODERATION = 'Премодерация';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_ALLOW_PREMODERATION = 'Разрешить премодерацию комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_PREMODERATION_NO = 'Нет';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_PREMODERATION_GUEST = 'Только для неавторизованных пользователей';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_PREMODERATION_ALWAYS = 'Всегда';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_SHOW_ADD_BLOCK = 'Сразу выводить блок добавления';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_ADD_BLOCK_FIRST = 'Выводить блок добавления сверху стены комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_SAVE_OK = 'Настройки комментариев сохранены.';
const NETCAT_MODULE_COMMENTS_ADMIN_SETTINGS_AUTH_BUTTONS = 'Показывать кнопки авторизации';

//template
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_TEMPLATE = 'Шаблон вывода комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_TEMPLATE_NEW = 'Новый шаблон вывода';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS = 'Настройки шаблона вывода';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_USE_DEFAULT = 'использовать по умолчанию';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_NAME = 'Название шаблона';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_PREFIX = 'Префикс «стенки» комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_COMMENT_BLOCK = 'Блок вывода комментария';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_REPLY_BLOCK = 'Блок вывода ответа на комментарий';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_LINK_COMMENT = 'Ссылка на комментирование';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_LINK_REPLY = 'Ссылка на ответ';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_LINK_EDIT = 'Ссылка на редактирование';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_LINK_DROP = 'Ссылка на удаление';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_APPEND_BLOCK = 'Блок добавления комментария или ответа';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_EDIT_BLOCK = 'Блок редактирования комментария или ответа';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_DROP_BLOCK = 'Блок удаления комментария или ответа';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_SUFFIX = 'Суффикс «стенки» комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_PAGINATION = 'Шаблон вывода листинга комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_SHOW_ALL = 'Кнопка показа всех комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT = 'Сообщение об ошибке';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_NAME = 'Вы не ввели имя';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_EMAIL = 'Вы не ввели или неправильно ввели email';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_NAME_EMAIL = 'Вы не ввели имя и email';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_PARENT = 'Комментарий, к которому вы написали ответ, был удален или выключен';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_WARNTEXT_CAPTCHA = 'Символы на картинке не соответствуют введённым';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_PREMODERATION = 'Сообщение о премодерации комментариев';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_NEW_COMMENT_BUTTON = 'Кнопка перехода к новому комментарию';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_SAVE_OK = 'Настройки шаблона комментариев сохранены.';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_RATING_BLOCK = 'Блок рейтинга комментария';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_MODERATOR_BLOCK = 'Блок модератора';
const NETCAT_MODULE_COMMENTS_ADMIN_TEMPLATE_SETTINGS_NOT_USER_AUTH_BLOCK = 'Блок выводимый для неавторизированных пользователей';

//subscribe
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_ADD = 'Добавление подписки';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_ADD_EXPLAIN = 'Необходимо выбрать сущность(сайт, раздел, инфоблок или объект) и пользователя.';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_ADD_NOUSER = 'Не выбран пользователь';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_ADD_NOESSENCE = 'Не выбрана сущность';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_ADD_SUBSCRIBED = 'Пользователь уже подписан на эту сущность';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_ADD_OK = 'Пользователь %s успешно подписан';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_DELETE_CONFIRM = 'Будут удалены сделующие подписки';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_DELETE_NOTSELECT = 'Не выбраны подписки для удаления';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_DELETE_OK = 'Удалено подписок: %s';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_ALLOW = 'доступна подписка на комментарии';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_AUTO = 'Автоматически подписывать комментатора';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_BLOCK = 'Ссылка на подписку';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_MAIL_TEMPLATE = 'Шаблон тела письма';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_MAIL_SUBJECT = 'Тема письма';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_SAVE_OK = 'Настройки подписок и оповещений на комментарии сохранены.';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_ADMIN = 'Оповещение администратора';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_ADMIN_ALLOW = 'Уведомлять администратора о новых комментариях';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_SETTINGS_ADMIN_EMAIL = 'Email администратора';

const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_LIST_MESSAGE = 'Объект %s в инфоблоке %s';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_LIST_CC = 'Инфоблок %s';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_LIST_SUB = 'Раздел %s';
const NETCAT_MODULE_COMMENTS_ADMIN_SUBSCRIBE_LIST_CAT = 'Сайт %s';

//optimize
const NETCAT_MODULE_COMMENTS_ADMIN_OPTIMIZE = 'Оптимизация данных';
const NETCAT_MODULE_COMMENTS_ADMIN_OPTIMIZE_DO = 'Пересчитать комментарии и ответы';
const NETCAT_MODULE_COMMENTS_ADMIN_OPTIMIZE_DO_BUTTON = 'Оптимизировать';
const NETCAT_MODULE_COMMENTS_ADMIN_OPTIMIZE_OK = 'Пересчитано записей: %COUNT';
const NETCAT_MODULE_COMMENTS_ADMIN_OPTIMIZE_NO_DATA = 'Нет данных для пересчёта';

const NETCAT_MODULE_COMMENTS_CLASS_UNRECOGNIZED_OBJECT_CALLING = 'Неподдерживаемый вызов объекта';
const NETCAT_MODULE_COMMENTS_CLASS_UNCORRECT_DATA_FORMAT = 'Неверный формат данных';

const NETCAT_MODULE_COMMENTS_SPAM_FROM_EMAIL_NOTICE = 'Email отправителя не заполнено. Для корректной работы оповещений необходимо заполнить поле Email отправителя в Настройках системы';

const NETCAT_MODULE_COMMENTS_ADD_COMMENT_USER_AUTH = 'Для добавления комментариев вам необходимо авторизоваться';
