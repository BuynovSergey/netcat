<?php

const NETCAT_MODULE_CAPTCHA_DESCRIPTION = 'Защита форм картинкой';

if (nc_core::get_object()->get_settings('Provider', 'captcha') !== 'nc_captcha_provider_image') {
    define('NETCAT_MODULE_CAPTCHA_WRONG_CODE', 'Пожалуйста, подтвердите, что вы не робот');
    define('NETCAT_MODULE_CAPTCHA_WRONG_CODE_SMALL', 'Подтвердите, что вы не робот');
}
else {
    define('NETCAT_MODULE_CAPTCHA_WRONG_CODE', 'Неправильно введены символы, изображенные на картинке');
    define('NETCAT_MODULE_CAPTCHA_WRONG_CODE_SMALL', 'Неправильно введены символы');
}

const NETCAT_MODERATION_CAPTCHA = 'Введите символы, изображенные на картинке';
const NETCAT_MODERATION_CAPTCHA_SMALL = 'Символы<br/>на картинке';
const NETCAT_MODULE_CAPTCHA_AUDIO_LISTEN = 'Прослушать';
const NETCAT_MODULE_CAPTCHA_REFRESH = 'Обновить';

const NETCAT_MODULE_CAPTCHA_SETTINGS_SAVE = 'Сохранить';
const NETCAT_MODULE_CAPTCHA_SETTINGS_SAVED = 'Настройки сохранены';
const NETCAT_MODULE_CAPTCHA_SETTINGS_USE_DEFAULT = 'использовать <a href="%s" target="_top">общие настройки для всех сайтов</a>';
const NETCAT_MODULE_CAPTCHA_SETTINGS_PROVIDER = 'Тип CAPTCHA';
const NETCAT_MODULE_CAPTCHA_SETTINGS_PROVIDER_IMAGE = 'Картинка и аудио';
const NETCAT_MODULE_CAPTCHA_SETTINGS_PROVIDER_RECAPTCHA = 'reCAPTCHA';
const NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_CHARACTERS = 'Символы, используемые на картинке';
const NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_LENGTH = 'Количество символов на картинке';
const NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_EXPIRES = 'Срок действия кода на картинке в секундах';
const NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_WIDTH = 'Ширина картинки';
const NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_HEIGHT = 'Высота картинки';
const NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_LINES = 'Число линий на картинке';
const NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_AUDIO_ENABLED = 'включить аудиокаптчу';
const NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_AUDIO_VOICE = 'Голос для аудиокаптчи';
const NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_NO_GD = 'Библиотека GD не включена в PHP, генерация картинок для защиты форм невозможна.';

const NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_SITE_KEY = 'Ключ';
const NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_SECRET_KEY = 'Секретный ключ';
const NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_ADD_KEYS = 'Укажите публичный и секретный ключи. Получить ключи можно на странице <a href="https://www.google.com/recaptcha/admin" target="_blank">https://www.google.com/recaptcha/admin</a>.';
const NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_INVALID_SECRET = '<a href="https://www.google.com/recaptcha/admin" target="_blank" title="Панель управления reCAPTCHA">Проверьте</a>, правильно ли указан секретный ключ.';
const NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_NO_CURL = 'Библиотека cURL не включена в PHP, использование reCAPTCHA невозможно.';
const NETCAT_MODULE_CAPTCHA_SETTINGS_RECAPTCHA_NO_CONNECTION = 'Не удалось подключиться к серверу Google для проверки параметров.';

const NETCAT_MODULE_CAPTCHA_SETTINGS_LEGACY_MODE = 'Режим совместимости с шаблонами, предназначенными для использования со встроенной каптчей Netcat';
const NETCAT_MODULE_CAPTCHA_SETTINGS_REMOVED_LEGACY_TEXT = 'Текст, убираемый в форме (по одной фразе в строке)';
const NETCAT_MODULE_CAPTCHA_SETTINGS_REMOVED_LEGACY_BLOCKS = 'Блоки, убираемые в форме (по одному CSS-селектору блока в строке)';
