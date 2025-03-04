<?php

class nc_security_filter_php extends nc_security_filter {

    static protected $filter_type_string = 'php';
    static protected $input_check_types = array(
        'variable',
        'variable_function',
        'quote',
    );

    /**
     * Проверка на наличие переменной в составе строки
     * @param $input
     * @return bool
     */
    protected function check_input_for_variable($input) {
        return preg_match('/\$[a-zA-Z_\x7f-\xff{]/', $input) === 1;
    }

    /**
     * Проверка на наличие переменной переменной
     * E.g.: ${fn()}, ${$fn()}
     * @param $input
     * @return bool
     */
    protected function check_input_for_variable_function($input) {
        return preg_match('/\{\$\s*\{.+\}\s*\}/', $input) === 1;
    }

    /**
     * Проверка на наличие кавычки — возможный выход из строки в шаблонах v4
     * @param $input
     * @return bool
     */
    protected function check_input_for_quote($input) {
        $quote_position = strpos($input, '"');
        if ($quote_position === false) {
            return false;
        }

        // если это простая строка, обрамлённая кавычками, пропускаем её (например, "", "1")
        if ($quote_position === 0 && substr($input, -1) === '"') {
            $tokens = token_get_all("<?php $input");
            // если это единая строка без переменных, будет два токена — '<?php' и собственно строка
            return count($tokens) !== 2;
        }

        // у нас есть кавычка в середине $input — может быть проблема, а может и нет
        return true;
    }

    /**
     * Устанавливает режим работы фильтра
     *
     * @param int $mode одна из констант nc_security_filter::MODE_*
     */
    public function set_mode($mode) {
        // без расширения tokenizer не будет работать
        if (!function_exists('token_get_all')) {
            $mode = self::MODE_DISABLED;
        }
        parent::set_mode($mode);
    }


    /**
     * @param string $checked_string
     * @param array $suspicious_input
     * @param mixed $context
     * @return string
     */
    protected function check_string_against_input($checked_string, $suspicious_input, $context) {
        if (!$suspicious_input || !strlen(trim($checked_string))) {
            return $checked_string;
        }

        // частый случай использования eval — для присвоения значения строке
        if ($context === 'string') {
            $php_string = '"' . $checked_string . '";';
        } else {
            $php_string = $checked_string;
        }

        $original_fingerprint = null;
        foreach ($suspicious_input as $type => $data) {
            foreach ($data as $source => $input) {
                if (strpos($checked_string, $input) === false) {
                    continue;
                }

                // только простая переменная: предотвращение срабатывания при частичном совпадении
                // (например: "$variable" в проверяемой строке, "$v" во входящих данных)
                $input_is_variable_without_full_match = (
                    $type === 'variable' &&
                    preg_match('/^\s*\$[a-zA-Z_\x7f-\xff]+$/', $input) &&
                    $this->has_no_full_variable_match($php_string, $input)
                );

                if ($input_is_variable_without_full_match) {
                    continue;
                }

                // вычисляем отпечаток переданного кода только один раз и только при необходимости
                if ($original_fingerprint === null) {
                    $original_fingerprint = $this->get_code_fingerprint($php_string);
                }

                // наличие строки с кавычкой или переменной не должно нарушить структуру скрипта
                // (нет выхода за пределы строки)
                $string_without_suspicious_input = str_replace($input, '', $php_string);
                if ($original_fingerprint !== $this->get_code_fingerprint($string_without_suspicious_input)) {
                    $this->trigger_error($type, $php_string, $source, $input);
                    // санирование строки можно будет добавить здесь:
                    // $checked_string = ...
                }
            }
        }

        return $checked_string;
    }

    /**
     * @param string $php_string
     * @return string «отпечаток» кода
     */
    protected function get_code_fingerprint($php_string) {
        $tokens = token_get_all("<?php $php_string");
        $fingerprint = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $fingerprint .= $token[0];
            } else {
                $fingerprint .= $token;
            }
        }
        return $fingerprint;
    }

    /**
     * @param $php_string
     * @param $input
     * @return bool
     */
    protected function has_no_full_variable_match($php_string, $input) {
        $position = 0;
        $input_length = strlen($input);
        while (false !== ($position = strpos($php_string, $input, $position + 1))) {
            if (!preg_match('/[a-zA-Z_\x7f-\xff]/', $php_string[$position + $input_length])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $input_source
     * @param string $input_value
     * @param string $check_type
     * @return string
     */
    protected function escape_input($input_source, $input_value, $check_type) {
        $escaped_value = addcslashes($input_value, '"$');
        $this->mark_input_as_escaped_for_redirect($input_source, $escaped_value);
        return $escaped_value;
    }

    /**
     * @return array
     */
    public function get_configuration_errors() {
        if (!function_exists('token_get_all')) {
            return array(NETCAT_SECURITY_FILTER_NO_TOKENIZER);
        } else {
            return array();
        }
    }

}
