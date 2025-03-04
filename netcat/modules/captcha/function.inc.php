<?php

require_once __DIR__ . '/nc_captcha.class.php';
nc_core::get_object()->register_class_autoload_path('nc_captcha_', __DIR__ . '/classes/', false);

/**
 * Функция для генерации хэша
 * @return string хэш
 */
function nc_captcha_generate_hash() {
    return nc_captcha::get_instance()->get_provider()->generate_challenge_id();
}

/**
 * Формирование кода для картинки
 * @param string $captcha_hash соответствующий хэш-код
 * @return string
 */
function nc_captcha_generate_code($captcha_hash) {
    return nc_captcha::get_instance()->get_provider()->generate_challenge($captcha_hash);
}

/**
 * Проверка кода
 * @param string $user_code символы, введенные с картинки
 * @param string $user_hash соответствующий ему хэш
 * @param bool $delete_hash удалить хеш после проверки или нет
 * @return bool прошла проверка или нет
 */
function nc_captcha_verify_code($user_code, $user_hash = '', $delete_hash = true) {
    return nc_captcha::get_instance()->get_provider()->verify_user_response($user_code, $user_hash, $delete_hash);
}

/**
 * Функция для вывода формы с картинкой и скрытым полем
 * @param string $img_attributes атрибуты, которые попадут в тэг img
 * @param string $beg открывающий html-код кнопки аудиокапчи
 * @param string $mid текст кнопки аудиокапчи
 * @param string $end закрывающий html-код кнопки аудиокапчи
 * @param string $btn текст кнопки обновления капчи
 * @param bool $norefresh не выводить кнопку обновления
 * @param int|string $id суффикс для работы нескольких капч на странице
 * @param array $parameters дополнительные параметры для класса капчи
 * См. также: nc_comments::captchaFormField();
 * @return string html-код
 */
function nc_captcha_formfield($img_attributes = '', $beg = '', $mid = '', $end = '', $btn = '', $norefresh = false, $id = 0, array $parameters = array()) {
    $parameters = array_merge(array(
        'img_attributes' => $img_attributes,
        'audio_prefix' => $beg,
        'audio_button' => $mid,
        'audio_suffix' => $end,
        'refresh_button_text' => $btn,
        'no_refresh_button' => $norefresh,
        'id_suffix' => $id,
        'refresh' => !empty($_GET['nc_get_new_captcha']),
    ), $parameters);
    return nc_captcha::get_instance()->get_provider()->get_challenge_html(null, $parameters);
}
