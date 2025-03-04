<?php

/**
 *
 */
abstract class nc_captcha_provider {

    /** @var int|null  */
    protected $site_id;

    /**
     * nc_captcha_provider constructor.
     *
     * @param int $site_id
     */
    public function __construct($site_id = null) {
        $this->site_id = $site_id; // null = текущий сайт для nc_core::get_settings()
    }

    /**
     * Возвращает идентификатор каптчи.
     * (для встроенной каптчи для работы функции nc_captcha_generate_hash(),
     * которая является частью публичного API)
     *
     * @return string|null
     */
    abstract public function generate_challenge_id();

    /**
     * Создаёт задание для каптчи.
     *
     * @param string|null $challenge_id
     * @return string|null null
     */
    abstract public function generate_challenge($challenge_id = null);

    /**
     * Создаёт новое задание и возвращает информацию о нём (обновляет капчу)
     * Задание возвращается в виде, необходимом для работы конкретной капчи
     * @see /netcat/modules/captcha/index.php
     *
     * @return string|null
     */
    abstract public function get_new_challenge_data();

    /**
     * Возвращает код для вставки в форму (с картинкой или другим тестом).
     *
     * @param string $challenge_id
     * @param array $parameters
     * @return string
     */
    abstract public function get_challenge_html($challenge_id = null, array $parameters = array());

    /**
     * Проверяет ответ пользователя на задание.
     *
     * @param string $user_response
     * @param string $challenge_id
     * @param bool $invalidate_challenge
     * @return bool
     */
    abstract public function verify_user_response($user_response, $challenge_id = null, $invalidate_challenge = true);

    /**
     * Проверяет настройки
     *
     * @return array  массив с сообщениями об ошибках
     */
    public function get_configuration_errors() {
        return array();
    }

    /**
     * Возвращает, нужно ли для работы каптчи в шаблонах отдельное поле для ввода результата
     *
     * @return bool
     */
    public function requires_separate_input_field() {
        return false;
    }

    /**
     * @param $setting
     * @return mixed
     */
    protected function get_setting($setting) {
        return nc_core::get_object()->get_settings($setting, 'captcha', false, $this->site_id);
    }

    /**
     * Возвращает содержимое файла с указанным именем из папки js,
     * для совместимости с шаблонами v4 меняет кавычки на одинарные
     *
     * @param string $file_name
     * @param bool $replace_double_quotes
     * @return mixed|string
     */
    protected function get_js_file($file_name, $replace_double_quotes = true) {
        $script_path = nc_module_folder('captcha') . 'js/' . $file_name;
        $js = file_get_contents($script_path);

        // (предполагается, что все кавычки в .min.js — двойные)
        if ($replace_double_quotes) {
            $js = str_replace('"', "'", $js);
        }

        return $js;
    }

    /**
     * Возвращает скрипт для удаления «дополнительной» разметки
     *
     * @return string
     */
    protected function get_remove_legacy_markup_js() {
        $js = '';

        $removed_text = $this->get_remove_legacy_markup_js_setting_as_array('RemovedLegacyText');
        $removed_blocks = $this->get_remove_legacy_markup_js_setting_as_array('RemovedLegacyBlocks');

        if ($removed_text || $removed_blocks) {
            $legacy_markup_script = $this->get_js_file('remove_legacy_markup.min.js');

            $js = strtr($legacy_markup_script, array(
                'REMOVED_LEGACY_TEXT' => nc_array_json($removed_text),
                'REMOVED_LEGACY_BLOCKS' => nc_array_json($removed_blocks),
            ));
        }

        return $js;
    }

    /**
     * @param $setting
     * @return array
     */
    protected function get_remove_legacy_markup_js_setting_as_array($setting) {
        $value = trim($this->get_setting($setting));
        $array = preg_split('/(?:\s*\r?\n\s*)+/', $value);
        $array = array_filter($array, 'strlen');
        return $array;
    }

}