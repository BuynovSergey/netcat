<?php

/**
 * Встроенная каптча-картинка + аудиокаптча
 * (перенесено из функций nc_captcha_*)
 *
 * Настройки в Settings:
 *   Image_Characters — строка с символами, использующимися на картинке / в аудиокаптче
 *   Image_Length — число символов (может быть задан диапазон через две точки: 5..10)
 *   Image_ExpiresIn — время жизни каптчи в секундах
 *   Image_Width — ширина картинки
 *   Image_Height — высота картинки
 *   Image_Lines — число линий на фоне
 *   Image_HiddenFieldName — название скрытого поля для передачи хеша
 *          (не настраивается, для совместимости с предыдущими версиями)
 *   Image_Audio — включена аудиокаптча (0/1)
 *   Image_Voice — название папки с файлами аудиокаптчи в voice/
 *
 * Шрифт берётся из modules/captcha/font.ttf, если его нет (по умолчанию) — из require/font/default.ttf.
 * Голос берётся из modules/captcha/voice/[Image_Voice]/*.mp3
 *
 */
class nc_captcha_provider_image extends nc_captcha_provider {

    const DEFAULT_CHARACTERS = 'ABCDEFGHKLMNPQRSTUVWXYZ23456789';
    const DEFAULT_LENGTH = 5;
    const DEFAULT_EXPIRES_IN = 300;
    const DEFAULT_WIDTH = 150;
    const DEFAULT_HEIGHT = 30;
    const DEFAULT_LINES = 30;
    const DEFAULT_HIDDEN_FIELD_NAME = 'nc_captcha_hash';

    /**
     * @return string
     */
    public function generate_challenge_id() {
        return md5(uniqid(mt_rand(), true));
    }

    /**
     * Создаёт задание для каптчи.
     *
     * @param string|null $challenge_id
     * @return string
     */
    public function generate_challenge($challenge_id = null) {
        $nc_core = nc_core::get_object();

        if (!$challenge_id) {
            $challenge_id = $this->generate_challenge_id();
        }

        // Настройки параметров алгоритма генерации кода
        $alphabet = $this->get_setting('Image_Characters') ?: self::DEFAULT_CHARACTERS;
        // Массив с символами (возможно, utf8)
        $alphabet = array_values(array_filter(nc_preg_split('//', $alphabet), 'strlen'));
        $num_chars = $this->get_setting('Image_Length');

        if (preg_match('/^(\d+)\.\.(\d+)$/', trim($num_chars), $matches)) {
            $num_chars = rand($matches[1], $matches[2]);
        } else {
            $num_chars = (int)$num_chars ?: self::DEFAULT_LENGTH;
        }

        // Генерация случайной последовательности символов
        $alphabet_size = count($alphabet);
        $captcha_code = '';
        for ($i = 0; $i < $num_chars; $i++) {
            $captcha_code .= $alphabet[rand(0, $alphabet_size - 1)];
        }

        $escaped_challenge_id = $nc_core->db->escape($challenge_id);
        $escaped_captcha_code = $nc_core->db->escape($captcha_code);

        // Сохранить сгенерированный код
        $nc_core->db->query(
            "REPLACE INTO `Captchas` (`Captcha_Hash`, `Captcha_Code`)
             VALUES ('{$escaped_challenge_id}', '{$escaped_captcha_code}')"
        );

        // Обновление захешированных файлов для аудиокаптчи
        $this->update_audio_captcha_files();

        return $challenge_id;
    }

    /**
     * Создаёт новое задание и возвращает информацию о нём (обновляет капчу)
     * Задание возвращается в виде хеша каптчи и плейлиста аудиокаптчи (если такой есть),
     * разделенных знаком #
     * @see /netcat/modules/captcha/index.php
     *
     * @return string
     */
    public function get_new_challenge_data() {
        $captcha_hash = $this->generate_challenge_id();
        $this->generate_challenge($captcha_hash);
        $playlist = null;

        if ($this->is_audio_captcha_available()) {
            $playlist = $this->get_audio_captcha_playlist_by_hash($captcha_hash);
        }

        return $captcha_hash . ($playlist ? '#' . $playlist : '');
    }

    /**
     * Создаёт плейлист для аудиокаптчи на основе ее хеша.
     *
     * @param string $captcha_hash
     * @return string
     */
    public function get_audio_captcha_playlist_by_hash($captcha_hash) {
        $nc_core = nc_core::get_object();
        $escaped_captcha_hash = $nc_core->db->escape($captcha_hash);

        $playlist = array('playlist' => array());
        $captcha_code = $nc_core->db->get_var(
            "SELECT `Captcha_Code` FROM `Captchas` WHERE `Captcha_Hash` = '{$escaped_captcha_hash}'"
        );
        $code_hash = $nc_core->db->get_results(
            'SELECT `Key`, `Value` FROM `Captchas_Settings`', ARRAY_A, 'Key'
        );
        $code_hash = $code_hash ?: array();

        if ($captcha_code && is_string($captcha_code)) {
            $letters = array_values(array_filter(nc_preg_split('//', $captcha_code), 'strlen'));

            foreach ($letters as $letter) {
                $sound_file = strtolower($letter) . '.mp3';

                if (isset($code_hash[$sound_file]) && $code_hash[$sound_file]['Value']) {
                    $playlist['playlist'][] = array(
                        'file' => $nc_core->HTTP_FILES_PATH . 'captcha/current_voice/' . $code_hash[$sound_file]['Value']
                    );
                }
            }
        }

        return json_encode($playlist, true);
    }

    /**
     * Обновление файлов для аудиокаптчи/
     */
    protected function update_audio_captcha_files() {
        if (!$this->is_audio_captcha_available()) {
            return;
        }

        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        $captcha_settings = $nc_core->db->get_col('SELECT `Key`, `Value` FROM `Captchas_Settings`', 1, 0);

        $voice = $this->get_setting('Image_Voice');

        $will_update_settings =
            $captcha_settings &&
            $this->is_given_voice_available($voice) &&
            time() - 3600 >= strtotime($captcha_settings['time']);

        if (!$will_update_settings) {
            return;
        }

        $db->query("UPDATE `Captchas_Settings` SET `Value`= NOW() WHERE `Key` = 'time'");
        $from = nc_module_folder('captcha') . 'voice/' . $voice . '/';
        $to = $nc_core->FILES_FOLDER . 'captcha/current_voice/';
        $nc_core->files->create_dir($to);

        $enc_mp3_files[] = '';
        $enc_mp3_files = glob($to . '*.mp3') ?: array();
        $enc_mp3_files = array_map('basename', $enc_mp3_files);

        $src_mp3_files = glob($from . '*.mp3') ?: array();
        $src_mp3_files = array_map('basename', $src_mp3_files);

        foreach ($src_mp3_files as $src_file) {
            $file_hash = md5(uniqid(mt_rand(), true));
            if ($captcha_settings['current_voice'] !== $voice || !in_array($captcha_settings[$src_file], $enc_mp3_files, true)) {
                $this->set_audio_captcha_setting('current_voice', $voice);
                if ($captcha_settings['current_voice'] !== $voice) {
                    unlink($to . $captcha_settings[$src_file]);
                }
                copy($from . $src_file, $to . $file_hash . '.mp3');
                $this->set_audio_captcha_setting($src_file, $file_hash . '.mp3');
            } else {
                rename($to . $captcha_settings[$src_file], $to . $file_hash . '.mp3');
                $this->set_audio_captcha_setting($src_file, $file_hash . '.mp3');
            }
        }
    }

    /**
     * @param $key
     * @param $value
     */
    protected function set_audio_captcha_setting($key, $value) {
        $nc_core = nc_Core::get_object();
        $escaped_key = $nc_core->db->escape($key);
        $escaped_value = $nc_core->db->escape($value);
        $nc_core->db->query(
            "UPDATE `Captchas_Settings` SET `Value`= '{$escaped_value}' WHERE `Key` = '{$escaped_key}'"
        );

        if (!$nc_core->db->rows_affected) {
            $nc_core->db->query(
                "INSERT INTO `Captchas_Settings` SET `Value`= '{$escaped_value}' WHERE `Key` = '{$escaped_key}'"
            );
        }
    }

    /**
     * Возвращает код для вставки в форму.
     *
     * @param string $challenge_id
     * @param array $parameters
     *      'img_attributes' => атрибуты тэга img
     *      'audio_prefix' => начало блока аудиокаптчи
     *      'audio_button' => текст кнопки (ссылки) блока аудиокаптчи
     *      'audio_suffix' => окончание блока аудиокаптчи
     *      'refresh_button_text' => текст кнопки обновления картинки
     *      'no_refresh_button' => не показывать кнопку обновления картинки (true, false)
     *      'id_suffix' => $id,
     *      'refresh' => это запрос для обновления картинки на уже загруженной странице с каптчей
     * @return string
     */
    public function get_challenge_html($challenge_id = null, array $parameters = array()) {
        static $count = 0;

        // Если нет функции imagegif, каптча отключена
        if (!$this->is_functioning()) {
            // прячем «лишнюю» разметку («Введите символы, изображенные на картинке» и поле)
            return '<script>' . $this->get_remove_legacy_markup_js() . '</script>';
        }

        $id = nc_array_value($parameters, 'id_suffix') ?: $count;
        $btn = nc_array_value($parameters, 'refresh_button_text') ?: NETCAT_MODULE_CAPTCHA_REFRESH;
        $no_refresh_button = nc_array_value($parameters, 'no_refresh_button') ?: false;
        $refresh = nc_array_value($parameters, 'refresh', false);

        $captcha_hash = $challenge_id ?: $this->generate_challenge_id();
        $captcha_img_src = nc_module_path('captcha') . 'img.php?code=' . $captcha_hash;
        $captcha_img_attributes = nc_array_value($parameters, 'img_attributes', '');
        $this->generate_challenge($captcha_hash);

        $result = '';

        if (!$refresh && !$no_refresh_button) {
            $result .= "<div id='nc_captcha_container{$id}' style='display:inline-block'>";
        }

        $result .= "<input type='hidden' name='{$this->get_hash_field_name()}' value='{$captcha_hash}'>";
        $result .= "<img name='nc_captcha_img' src='{$captcha_img_src}' {$captcha_img_attributes}>";
        $result .= $this->get_audio_captcha_html($captcha_hash, $id, $parameters);

        if (!$refresh && !$no_refresh_button) {
            $result .= '</div>';
            $result .= "<button id='nc_captcha_refresh_button{$id}' type='button'>{$btn}</button>";
            $result .= $this->get_captcha_js(
                'nc_captcha_container' . $id,
                'nc_captcha_refresh_button' . $id,
                'nc_captcha' . $id
            );
        }

        $count++;
        return $result;
    }

    /**
     * @param $captcha_hash
     * @param $id
     * @param $parameters
     * @return string
     */
    protected function get_audio_captcha_html($captcha_hash, $id, $parameters) {
        if (!$this->is_audio_captcha_available()) {
            return '';
        }

        $module_path = nc_module_path('captcha');
        $button_prefix = nc_array_value($parameters, 'audio_prefix') ?:
            "<div class='nc_captcha_voice' style='display:inline-block;margin:0 0 0 10px;'>" .
            "<a class='nc_captcha_js' href='#' onclick='nc_captcha{$id}.play();return false;'>&#9834;&nbsp;";
        $button_content = nc_array_value($parameters, 'audio_button') ?: NETCAT_MODULE_CAPTCHA_AUDIO_LISTEN;
        $button_suffix = nc_array_value($parameters, 'audio_suffix') ?:
            "</a><span id='nc_captcha_player' style='display:none;'></span></div>";

        $audio_captcha = $button_prefix . $button_content . $button_suffix;

        $player = "<script src='" . nc_add_revision_to_url($module_path . 'player/swfobject.js') . "'></script>" .
                  "<script src='" . nc_add_revision_to_url($module_path . 'player/uppod_player.js') . "'></script>" .
                  "<script src='" . nc_add_revision_to_url($module_path . 'js/nc_audiocaptcha.js') . "'></script>";
        $playlist = $this->get_audio_captcha_playlist_by_hash($captcha_hash);
        $player_init = "<script>var nc_captcha{$id} = new nc_audiocaptcha('{$module_path}', '{$playlist}');</script>";

        return $player . $player_init . $audio_captcha;
    }

    /**
     * @param $id_captcha_container
     * @param $id_refresh_button
     * @param $play_object
     * @return string
     */
    protected function get_captcha_js($id_captcha_container, $id_refresh_button, $play_object) {
        $module_path = nc_module_path('captcha');
        $captcha_hash_field_name = $this->get_hash_field_name();
        return "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var module_path = '{$module_path}';
            jQuery('#{$id_refresh_button}').click(function() {
                jQuery.ajax({
                    url: module_path + 'index.php?nc_get_new_captcha=1',
                    success: function(result) {
                        var res = result.split('#');
                        var container = jQuery('#{$id_captcha_container}');
                        container.find('img[name=nc_captcha_img]').attr('src', module_path + 'img.php?code=' + res[0]);
                        container.find('input[name={$captcha_hash_field_name}]').attr('value', res[0]);
                        if (typeof res[1] !== 'undefined') {
                           {$play_object} = new nc_audiocaptcha(module_path, res[1]);
                        }
                    }
                });
                 return false;
            });
        });
        </script>";
    }

    /**
     * Удаляет протухшие каптчи из таблицы Captchas.
     */
    public function delete_expired_challenges() {
        $nc_core = nc_Core::get_object();
        $captcha_expiration = (int)$this->get_setting('Image_ExpiresIn') ?: self::DEFAULT_EXPIRES_IN;
        $nc_core->db->query(
            "DELETE FROM `Captchas`
             WHERE `Captcha_Created` <= DATE_SUB(NOW(), INTERVAL {$captcha_expiration} SECOND)"
        );
    }

    /**
     * @param string $user_response
     * @param string $challenge_id
     * @param bool $invalidate_challenge
     * @return bool
     */
    public function verify_user_response($user_response, $challenge_id = null, $invalidate_challenge = true) {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        // Если нет функции imagegif, то каптча отключена
        if (!$this->is_functioning()) {
            return true;
        }

        // если код не задан — он заведомо неверный
        if (!$user_response) {
            return false;
        }

        // время жизни каптчи
        $captcha_expiration = (int)$this->get_setting('Image_ExpiresIn') ?: self::DEFAULT_EXPIRES_IN;

        // имя скрытого поля, через которое передаётся хэш-код
        $hidden_field_name = $this->get_hash_field_name();

        // Если хэш не передан, то получить его из GET или POST параметров
        if (!$challenge_id) {
            $challenge_id_from_request = $nc_core->input->fetch_get_post($hidden_field_name);
            if (!is_scalar($challenge_id_from_request)) {
                return false;
            }
            $challenge_id = (string)$challenge_id_from_request;
        }

        $escaped_challenge_id = $db->escape($challenge_id);
        $escaped_user_response = $db->escape($user_response);

        // проверка каптчи
        $response_is_correct = (bool)$db->get_var(
            "SELECT 1
             FROM `Captchas`
             WHERE `Captcha_Hash` = '{$escaped_challenge_id}'
             AND UPPER(`Captcha_Code`) = UPPER('{$escaped_user_response}')
             AND `Captcha_Created` > DATE_SUB(NOW(), INTERVAL {$captcha_expiration} SECOND)"
        );

        if ($invalidate_challenge) {
            $db->query("DELETE FROM `Captchas` WHERE `Captcha_Hash` = '{$escaped_challenge_id}'");
        }

        return $response_is_correct;
    }

    /**
     * Выводит изображение в браузер
     *
     * @param string $captcha_hash хэш
     * @return bool возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
     */
    public function output_image($captcha_hash) {
        $nc_core = nc_core::get_object();

        $ttf_font_file = nc_module_folder('captcha') . 'font.ttf';
        if (!file_exists($ttf_font_file)) {
            $ttf_font_file = $nc_core->INCLUDE_FOLDER . 'font/default.ttf';
        }

        $escaped_captcha_hash = $nc_core->db->escape($captcha_hash);
        $captcha_code = $nc_core->db->get_var(
            "SELECT `Captcha_Code` FROM `Captchas` WHERE `Captcha_Hash` = '{$escaped_captcha_hash}'"
        );

        // Функция завершает работу, если для заданного хэша не найден соответствующий код
        // Это предотвращает деление на 0
        if (!$captcha_code || !is_string($captcha_code)) {
            return false;
        }

        // Если пользователем определена собственная функция для создания картинки,
        // вернуть ее результат
        include_once nc_module_folder('captcha') . 'user_functions.inc.php';
        if (function_exists('nc_captcha_user_image')) {
            return nc_captcha_user_image($captcha_code);
        }

        // "Стандартный" алгоритм
        // Функция работает только при наличии библиотеки GD с поддержкой GIF
        if (!$this->is_functioning()) {
            return false;
        }

        $captcha_code = array_values(array_filter(nc_preg_split('//', $captcha_code), 'strlen'));
        $num_chars = count($captcha_code);

        // Настройки параметров алгоритма генерации картинки

        $img_width = $this->get_setting('Image_Width') ?: self::DEFAULT_WIDTH;
        $img_height = $this->get_setting('Image_Height') ?: self::DEFAULT_HEIGHT;
        $num_lines = $this->get_setting('Image_Lines');
        if ($num_lines === false) {
            $num_lines = self::DEFAULT_LINES;
        }

        $letter_width = (int)($img_width / ($num_chars + 1));
        $captcha_image = imagecreate($img_width, $img_height);

        // Белый фон
        imagecolorallocate($captcha_image, 255, 255, 255);
        $grey_color = mt_rand(165, 225);
        $noise_color = imagecolorallocate($captcha_image, $grey_color, $grey_color, $grey_color);

        for ($i = 0; $i < $num_lines; $i++) {
            imageline(
                $captcha_image,
                mt_rand(0, $img_width),
                mt_rand(0, $img_height),
                mt_rand(0, $img_width),
                mt_rand(0, $img_height),
                $noise_color
            );
        }

        for ($i = 0; $i < $num_chars; $i++) {
            $font_size = $img_height * round(mt_rand(45, 70) / 100, 2);
            $text_color = imagecolorallocate($captcha_image, mt_rand(0, 128), mt_rand(0, 128), mt_rand(0, 128));
            if (function_exists('imagettftext')) {
                $rand_x = rand(0, $letter_width - $font_size);
                $rand_y = $img_height - round(($img_height - $font_size) / 2);
                imagettftext(
                    $captcha_image,
                    $font_size,
                    mt_rand(-30, 30),
                    $i * $letter_width + $rand_x,
                    $rand_y,
                    $text_color,
                    $ttf_font_file,
                    $captcha_code[$i]
                );
            } else {
                $rand_font = mt_rand(2, 5);
                $rand_x = rand(0, $letter_width - imagefontwidth($rand_font));
                $rand_y = rand(0, $img_height - imagefontheight($rand_font));
                imagestring(
                    $captcha_image,
                    $rand_font,
                    $i * $letter_width + $rand_x,
                    $rand_y,
                    $captcha_code[$i],
                    $text_color
                );
            }
        }

        imagefilter($captcha_image, IMG_FILTER_GAUSSIAN_BLUR);

        return imagegif($captcha_image);
    }

    /**
     * @return array
     */
    public function get_configuration_errors() {
        $result = array();
        if (!$this->is_functioning()) {
            $result[] = NETCAT_MODULE_CAPTCHA_SETTINGS_IMAGE_NO_GD;
        }
        return $result;
    }

    /**
     * Для работы нужна библиотека GD с поддержкой GIF.
     * Если нет ни функции imagegif(), ни пользовательской функции генерации картинки,
     * методы класса отвечают так, как если бы каптча была отключена.
     *
     * @return bool
     */
    protected function is_functioning() {
        return function_exists('imagegif') || function_exists('nc_captcha_user_image');
    }

    /**
     * Проверяет, доступен ли выбранный голос
     *
     * @param string $voice
     *
     * @return bool
     */
    protected function is_given_voice_available($voice) {
        if (!is_string($voice)) {
            return false;
        }

        $nc_core = nc_Core::get_object();

        return is_writable($nc_core->FILES_FOLDER) &&
            is_dir(nc_module_folder('captcha') . 'voice/' . $voice . '/');
    }

    /**
     * Проверяет, включена ли аудиокаптча в настройках и доступен ли выбранный голос
     *
     * @return bool
     */
    public function is_audio_captcha_available() {
        return $this->get_setting('Image_Audio') &&
            $this->is_given_voice_available($this->get_setting('Image_Voice'));
    }

    /**
     * Возвращает, нужно ли для работы каптчи в шаблонах отдельное поле для ввода результата
     *
     * @return bool
     */
    public function requires_separate_input_field() {
        return true;
    }

    /**
     * Возвращает имя поля для передачи хеша каптчи
     * (до версии 5.8 могло указываться в настройках модуля, оставлено для совместимости)
     *
     * @return string
     */
    protected function get_hash_field_name() {
        return $this->get_setting('Image_HiddenFieldName') ?: self::DEFAULT_HIDDEN_FIELD_NAME;
    }
}