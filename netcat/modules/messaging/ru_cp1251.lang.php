<?php

// Общее
const NETCAT_MODULE_MESSAGING_ENABLED = "Отправка сообщений включена";
const NETCAT_MODULE_MESSAGING_PROVIDER = "Служба";
const NETCAT_MODULE_MESSAGING_PROVIDERS = "Службы";
const NETCAT_MODULE_MESSAGING_SETTINGS = "Настройки";
const NETCAT_MODULE_MESSAGING_ADD_NUMBER = "Добавить номер";
const NETCAT_MODULE_MESSAGING_SETTINGS_ALLOWED_PHONE_PREFIXES = "Разрешенные для отправки префиксы телефонов";
const NETCAT_MODULE_MESSAGING_SETTINGS_FORBIDDEN_PHONE_PREFIXES = "Запрещенные для отправки префиксы телефонов";
const NETCAT_MODULE_MESSAGING_AUTH = "Двухфакторная аутентификация";
const NETCAT_MODULE_MESSAGING_DEFAULT = "По умолчанию (если служба не указана)";
const NETCAT_MODULE_MESSAGING_SECURITY = "Срабатывание фильтра входящих данных";
const NETCAT_MODULE_MESSAGING_REQUESTS = "Заявки";
const NETCAT_MODULE_MESSAGING_MESSAGE = "Текст сообщения";
const NETCAT_MODULE_MESSAGING_PHONE = "Номер телефона";
const NETCAT_MODULE_MESSAGING_MESSAGE_SENT_SUCCESS = "Сообщение успешно отправлено";
const NETCAT_MODULE_MESSAGING_SINGLE_FIELD_SELECT_PLACEHOLDER = 'Выберите поле';
const NETCAT_MODULE_MESSAGING_FIELD_SELECT_PLACEHOLDER = 'Выберите нужные поля...';
const NETCAT_MODULE_MESSAGING_FIELD_NOT_FOUND = 'Поля по запросу не найдены';


// Службы/сервисы/поставщики услуг
const NETCAT_MODULE_MESSAGING_PROVIDER_NAME = "Поставщик услуг";
const NETCAT_MODULE_MESSAGING_SERVICE_NAME = "Имя службы";
const NETCAT_MODULE_MESSAGING_SERVICE_API_TOKEN = "Ключ доступа";
const NETCAT_MODULE_MESSAGING_SERVICE_LOGIN = "Логин";
const NETCAT_MODULE_MESSAGING_SERVICE_PASSWORD = "Пароль";
const NETCAT_MODULE_MESSAGING_SERVICE_SENDER_ID = "Имя отправителя";
const NETCAT_MODULE_MESSAGING_SERVICE_MANUAL_SEND = "Отправить сообщение";
define ("NETCAT_MODULE_MESSAGING_SERVICE_CONFIGURE_SERVICE_TEXT",
'Выберите, настройте и включите <a href="'.nc_core::get_object()->ADMIN_PATH.'#module.messaging.service(index)" target="_top">службу</a>'
);

// Ошибки
const NETCAT_MODULE_MESSAGING_UPDATE_ERROR = "Не удалось обновить объект";
const NETCAT_MODULE_MESSAGING_SETTINGS_SAVE_ERROR = "Не удалось сохранить параметры: %s";
const NETCAT_MODULE_MESSAGING_SERVICE_NOT_FOUND_ERROR = "Запрашиваемый сервис не найден";
const NETCAT_MODULE_MESSAGING_SERVICE_AUTH_PARAMETERS_DID_NOT_PROVIDED_ERROR = "Не переданы параметры для авторизации (логин+пароль или api ключ)";
const NETCAT_MODULE_MESSAGING_SERVICE_CONFIRMATION_CODE_LENGTH_VALIDATION_ERROR = "Код подтверждения должен быть не менее %s и не более %s символов";
const NETCAT_MODULE_MESSAGING_API_UNSUPPORTED_IMPLEMENTATION_ERROR = "Класс должен реализовывать nc_messaging_api";
const NETCAT_MODULE_MESSAGING_SERVICE_UNSUPPORTED_IMPLEMENTATION_ERROR = "Класс должен реализовывать интерфейс nc_messaging_api";
const NETCAT_MODULE_MESSAGING_SERVICE_NO_AVAILABLE_SERVICES_ERROR = "Отсутствуют настроенные службы";
const NETCAT_MODULE_MESSAGING_SERVICE_NO_SELECTED_ERROR = "Служба не выбрана";
const NETCAT_MODULE_MESSAGING_SERVICE_FORBIDDEN_PREFIX_ERROR = "Префикс для указанных телефонов: %s находится в списке запрещенных";
const NETCAT_MODULE_MESSAGING_INVALID_PHONE_FORMAT = "Некорректный формат номера телефона";
const NETCAT_MODULE_MESSAGING_UNSUPPORTED_MULTI_MESSAGING = "Служба не поддерживает множественную отправку";
const NETCAT_MODULE_MESSAGING_SERVICE_DISABLED_ERROR = "Служба выключена";
const NETCAT_MODULE_MESSAGING_DISABLED_ERROR = "Отправка сообщений выключена";
const NETCAT_MODULE_MESSAGING_MESSAGE_SENT_ERROR = "По техническим причинам не удалось отправить сообщение";

// Журнал событий
const NETCAT_MODULE_MESSAGING_LOG_STATUS = "Статус";
const NETCAT_MODULE_MESSAGING_LOG_RECIPIENTS = "Получатели";
const NETCAT_MODULE_MESSAGING_LOG_RESPONSE_TEXT = "Сообщение от сервера";
