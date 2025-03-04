<?php

// Права доступа к модулю
const NC_PERM_MODULE_REQUESTS_SETTINGS_EDIT = 'изменение настроек модуля';
const NC_PERM_MODULE_REQUESTS_REQUESTS_VIEW = 'просмотр заявок';

// Языковые константы
const NETCAT_MODULE_REQUESTS = 'Заявки';
const NETCAT_MODULE_REQUESTS_DESCRIPTION = 'Данный модуль предназначен для просмотра заявок и создания форм.';

const NETCAT_MODULE_REQUESTS_FORM_TYPE = 'Группа формы';
const NETCAT_MODULE_REQUESTS_FORM_SETTINGS_FIELDS_HEADER = 'Поля формы';
const NETCAT_MODULE_REQUESTS_FORM_POPUP_BUTTON_SETTINGS_HEADER = 'Кнопка, открывающая форму';
const NETCAT_MODULE_REQUESTS_FORM_SUBMIT_BUTTON_SETTINGS_HEADER = 'Кнопка отправки формы';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_DEFAULT_TEXT = 'Отправить';
const NETCAT_MODULE_REQUESTS_FORM_OPEN_POPUP_FORM = 'Открыть форму';
const NETCAT_MODULE_REQUESTS_FORM_HAS_NO_FIELDS = 'Для этой формы не выбрано ни одного поля';
const NETCAT_MODULE_REQUESTS_FORM_HEADER_CAPTION = 'Заголовок формы';
const NETCAT_MODULE_REQUESTS_FORM_TEXT_AFTER_HEADER_CAPTION = 'Подзаголовок формы';
const NETCAT_MODULE_REQUESTS_FORM_NOTIFICATION_EMAIL_CAPTION = 'Отправить данные формы на почту (для всех форм на странице)';
const NETCAT_MODULE_REQUESTS_FORM_NOTIFICATION_EMAIL_PLACEHOLDER = 'Например: mail1@pochta.ru, mail2@pochta.ru...';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_TEXT = 'Текст';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_TYPE = 'Тип кнопки';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_TYPE_PRIMARY = 'Основная';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_TYPE_SECONDARY = 'Дополнительная';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_COLOR = 'Цвет (если не задан на уровне блока)';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_PRICE = 'Показывать цену (если есть)';
const NETCAT_MODULE_REQUESTS_FORM_SUBMIT_EVENT_CATEGORIES = 'Категории событий Google Analytics при отправке формы (можно несколько через запятую),<br>для Яндекс.Метрики цели событий — «<i>категория</i>_submit» для каждой из указанных категорий';
const NETCAT_MODULE_REQUESTS_FORM_SUBMIT_EVENT_LABELS = 'Ярлыки событий для Google Analytics при отправке формы (можно несколько через запятую),<br>для Яндекс.Метрики — дополнительный параметр визита «event_label»';
const NETCAT_MODULE_REQUESTS_FORM_OPEN_EVENT_CATEGORIES = 'Категории событий Google Analytics при открытии формы (можно несколько через запятую),<br>для Яндекс.Метрики цели событий — «<i>категория</i>_click» для каждой из указанных категорий';
const NETCAT_MODULE_REQUESTS_FORM_OPEN_EVENT_LABELS = 'Ярлыки событий для Google Analytics при открытии формы (можно несколько через запятую),<br>для Яндекс.Метрики — дополнительный параметр визита «event_label»';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_SUBDIVISION = 'для всей страницы';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_SCOPE_INFOBLOCK = 'дополнительно для этого блока';
const NETCAT_MODULE_REQUESTS_FORM_SUBDIVISION_SYNC_HINT = 'Набор полей будет установлен для всех форм на этой странице.';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_OPEN_POPUP = 'При нажатии на эту кнопку будет показана форма поверх страницы.';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_CREATE_ORDER = 'Эта кнопка создаёт заказ в интернет-магазине.';
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_CREATE_REQUEST = 'Эта кнопка создаёт заявку.';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_NAME = 'Название поля';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_LABEL = 'Подпись';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_PLACEHOLDER = 'Подсказка внутри поля';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_VISIBILITY = 'Вкл.';
const NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_REQUIRED = '*';

const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_STATS_DISABLED =
    "Модуль «Статистика посещений» отключён, события не будут отправляться в Яндекс.Метрику и Google Analytics.
    <a href='%s' target='_blank'>Изменить настройки модулей</a>";
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_ANALYTICS_DISABLED =
    "Интеграция с Яндекс.Метрикой и Google Analytics отключена в настройках модуля «Статистика посещений», информация о событиях не будет отправляться.
    <a href='%s' target='_blank'>Изменить настройки</a>";
const NETCAT_MODULE_REQUESTS_FORM_BUTTON_ACTION_HINT_ANALYTICS_NO_COUNTERS =
    "В настройках модуля «Статистика посещений» не указаны коды счётчиков Яндекс.Метрики и Google Analytics, информация о событиях не будет отправляться.
    <a href='/netcat/admin/#module.stats.analytics' target='_blank'>Указать параметры счётчиков</a>";

const NETCAT_MODULE_REQUESTS_REQUEST_FILTER = 'Фильтр заявок';

const NETCAT_MODULE_REQUESTS_REQUEST_NEW = 'новый';
const NETCAT_MODULE_REQUESTS_REQUEST_ANY = 'любой';

const NETCAT_MODULE_REQUESTS_REQUEST_SEARCH = 'Номер, имя, телефон, e-mail или источник';
const NETCAT_MODULE_REQUESTS_DATE_FILTER = 'Дата';
const NETCAT_MODULE_REQUESTS_DATE_FILTER_FROM = 'с';
const NETCAT_MODULE_REQUESTS_DATE_FILTER_TO = 'по';
const NETCAT_MODULE_REQUESTS_REQUEST_FILTER_SUBMIT = 'Искать';
const NETCAT_MODULE_REQUESTS_REQUEST_FILTER_RESET = 'Очистить форму';
const NETCAT_MODULE_REQUESTS_REQUEST_FILTER_RESET_CONFIRM = 'Вы уверены, что хотите очистить форму?';
const NETCAT_MODULE_REQUESTS_REQUEST_DELETE_SELECTED = 'Удалить отмеченные';
const NETCAT_MODULE_REQUESTS_REQUEST_DELETE_SELECTED_CONFIRM = 'Удалить отмеченные заявки?';

const NETCAT_MODULE_REQUESTS_REQUEST_STATUS = 'Статус заявки';
const NETCAT_MODULE_REQUESTS_CONFIRM_STATUS_CHANGE = 'Подтвердите смену заявки';
const NETCAT_MODULE_REQUESTS_CONFIRM_STATUS_CHANGE_TO = 'Изменить статус заявки на «%s»?';

const NETCAT_MODULE_REQUESTS_REQUEST_NUMBER = 'Заявка №';
const NETCAT_MODULE_REQUESTS_REQUEST_EDIT = 'Редактирование заявки';

const NETCAT_MODULE_REQUESTS_ITEM_DISCOUNT = 'Скидка (промо-страница)';
const NETCAT_MODULE_REQUESTS_ITEM_DISCOUNT_DESCRIPTION = 'Размер скидки взят из настроек промо-страницы';

const NETCAT_MODULE_REQUESTS_DEFAULT_NOTIFICATION_EMAIL = 'Адреса электронной почты по умолчанию для уведомлений о новых заявках (если не заданы в настройках формы)';
const NETCAT_MODULE_REQUESTS_NOTIFICATION_EMAIL_SUBJECT = 'Новая заявка на сайте %s (%s)';
const NETCAT_MODULE_REQUESTS_DEFAULT_NOTIFICATION_PHONE = 'Номера телефонов по умолчанию для уведомлений о новых заявках';
const NETCAT_MODULE_REQUESTS_REQUEST_ADMIN_LINK = 'Заявки в панели управления';
const NETCAT_MODULE_REQUESTS_SETTINGS_HEADER = 'Настройки';
const NETCAT_MODULE_REQUESTS_SAVE = 'Сохранить';
const NETCAT_MODULE_REQUESTS_SETTINGS_SAVED = 'Настройки сохранены';
