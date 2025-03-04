<?php

/**
 * Class nc_security_filter
 *
 * Стандартные фильтры работают следующим образом:
 *  — при первом вызове filter() вызывается метод examine_input()
 *    — examine_input() проходит по static::$patterns, если паттерн совпал
 *      для входящих данных — запоминает их в suspicious_input
 *      (ключ совпавшего регвыра => исходная переменная => значение)
 *  — при проверке вызывается метод filter(), который
 *    — смотрит, не было ли отказа от проверки (вызова skip_next_check())
 *    — составляет список проверяемых значений в зависимости от предшествовавшего
 *      вызова ignore_once()
 *    — вызывает метод check_string_against_input() — здесь реализована конкретная
 *      проверка
 *      — метод check_string_against_input() при обнаружении проблемы должен вызвать
 *        trigger_error(); в нём в зависимости от настроек может быть выброшено
 *        исключение или выполнен редирект
 *    — возвращает исходную или (если включена санация) санированную строку
 */
abstract class nc_security_filter {

    /** фильтр отключён */
    const MODE_DISABLED = 0;
    /** только записывать в лог, не выполнять действий [don’t try this at home] */
    const MODE_LOG_ONLY = 1;
    /** выбросить исключение при срабатывании */
    const MODE_EXCEPTION = 2;
    /** выполнить переадресацию, убрав сработавший параметр */
    const MODE_RELOAD_REMOVE_INPUT = 3;
    /** выполнить переадресацию, экранировав (если возможно) сработавший параметр */
    const MODE_RELOAD_ESCAPE_INPUT = 4;

    // Все static-свойства должны быть переопределены в конкретных классах:
    /** @var string тип фильтра (для логирования) */
    static protected $filter_type_string;
    /** @var string[] названия проверок (для значения 'X' должен существовать метод check_input_for_X()) */
    static protected $input_check_types = array();

    /** @var int текущий режим работы фильтра */
    protected $mode = self::MODE_DISABLED;
    /** @var bool выполнена проверка входящих данных */
    protected $input_checked = false;
    /** @var string[][] входящие данные, показавшиеся подозрительными (совпавшие с self::$patterns) */
    protected $suspicious_input = array();
    /** @var string[][] $suspicious_input после addslashes(), если отличается */
    protected $suspicious_input_escaped = array();
    /** @var string[] источники входящих данных, которые будут проигнорированы при следующей проверке */
    protected $input_sources_ignored_in_next_check = array();
    /** @var bool */
    protected $is_next_check_skipped = false;

    /**
     * Устанавливает режим работы фильтра.
     *
     * @param int $mode одна из констант nc_security_filter::MODE_*
     */
    public function set_mode($mode) {
        $this->mode = (int)$mode;
    }

    /**
     * @return bool что-то нашли
     */
    protected function examine_input() {
        $input = array(
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_COOKIE' => $_COOKIE,
            '_SESSION' => $_SESSION,
        );

        $this->examine_input_recursive($input);
        $this->input_checked = true;

        return count($this->suspicious_input) > 0;
    }

    /**
     * Добавляет данные к проверяемым значениям
     *
     * @param array[] $additional_input (ключ — '_GET', '_POST', '_COOKIE', '_SESSION')
     */
    public function add_checked_input(array $additional_input) {
        if ($this->mode !== self::MODE_DISABLED) {
            $this->examine_input_recursive($additional_input);
        }
    }

    /**
     * @param array $input
     * @param null $parent_source
     */
    protected function examine_input_recursive(array $input, $parent_source = null) {
        foreach ($input as $key => $value) {
            if (is_object($value)) {
                continue;
            }

            $value_source = $parent_source ? $parent_source . "[$key]" : '$' . $key;
            if ($value_source === '$_GET[REQUEST_URI]' || $value_source === '$_SESSION[nc_input_escaped]') {
                continue;
            }

            if (is_array($value)) {
                $this->examine_input_recursive($value, $value_source);
            }
            else {
                $this->examine_input_value($value, $value_source);
            }
        }
    }

    /**
     * @param mixed $value
     * @param string $value_source
     */
    protected function examine_input_value($value, $value_source) {
        foreach (static::$input_check_types as $check_type) {
            $pattern_check_method = 'check_input_for_' . $check_type;
            if (!$this->$pattern_check_method($value)) {
                // $value не кажется «подозрительным»
                continue;
            }

            // Метод check_input_for_*() вернул true — это «подозрительное» значение.
            // 1) Сохраняем значение из superglobals:
            $this->suspicious_input[$check_type][$value_source] = $value;
            // 2) Сохраняем значение из $GLOBALS — Netcat эмулирует при extract’е «волшебные кавычки»:
            $escaped_value = addslashes($value);
            if ($escaped_value !== $value) {
                $this->suspicious_input_escaped[$check_type][$value_source] = $escaped_value;
            }
        }
    }

    /**
     * Проверяет строку на наличие инъекций.
     * В зависимости от выбранного режима режима фильтра может завершить работу скрипта,
     * выполнить переадресацию или вернуть санированное значение.
     *
     * @param string $checked_string
     * @param mixed $context
     *
     * @return string
     */
    public function filter($checked_string, $context = null) {
        $no_check_required =
            $this->mode === self::MODE_DISABLED ||
            (!$this->input_checked && !$this->examine_input()) ||
            count($this->suspicious_input) === 0;

        if ($no_check_required) {
            return $checked_string;
        }

        $result = $checked_string;
        if (!$this->is_next_check_skipped) {
            $input = $this->suspicious_input;
            $input_escaped = $this->suspicious_input_escaped;
            if ($this->input_sources_ignored_in_next_check) {
                $input = $this->remove_sources($input, $this->input_sources_ignored_in_next_check);
                $input_escaped = $this->remove_sources($input_escaped, $this->input_sources_ignored_in_next_check);
            }

            $result = $checked_string;
            $result = $this->check_string_against_input($result, $input, $context);
            $result = $this->check_string_against_input($result, $input_escaped, $context);
        }

        $this->is_next_check_skipped = false;
        $this->input_sources_ignored_in_next_check = array();

        return $result;
    }

    /**
     * @param $checked_string
     * @param $suspicious_input
     * @param $context
     *
     * @return string
     */
    abstract protected function check_string_against_input($checked_string, $suspicious_input, $context);

    /**
     * @param array $input
     * @param array $removed_sources
     *
     * @return mixed
     */
    protected function remove_sources(array $input, array $removed_sources) {
        foreach ($input as $type => $data) {
            foreach ($removed_sources as $removed_source) {
                unset($input[$type][$removed_source]);
            }
        }

        return $input;
    }

    /**
     *
     */
    public function ignore_always() {
        $this->suspicious_input = $this->remove_sources($this->suspicious_input, func_get_args());
        $this->suspicious_input_escaped = $this->remove_sources($this->suspicious_input_escaped, func_get_args());
    }

    /**
     *
     */
    public function ignore_once() {
        $this->input_sources_ignored_in_next_check = func_get_args();
    }

    /**
     * @param bool $skip
     */
    public function skip_next_check($skip = true) {
        $this->is_next_check_skipped = (bool)$skip;
    }

    /**
     * @param $check_type
     * @param $checked_string
     * @param $input_source
     * @param $input_value
     *
     * @throws nc_security_filter_exception
     */
    protected function trigger_error($check_type, $checked_string, $input_source, $input_value) {
        $this->log_and_alert($check_type, $checked_string, $input_source, $input_value);

        switch ($this->mode) {
            case self::MODE_EXCEPTION:
                while (@ob_get_clean())
                    ;
                nc_set_http_response_code(500);
                throw new nc_security_filter_exception(get_class($this) . " check failed");
            case self::MODE_RELOAD_REMOVE_INPUT:
                $input = $this->input_source_string_to_array($input_source, null);
                $this->reload($input);
                die;
            case self::MODE_RELOAD_ESCAPE_INPUT:
                $escaped_input_value = $this->escape_input($input_source, $input_value, $check_type);
                $input_value_after_reload = $input_value === $escaped_input_value ? null : $escaped_input_value;
                $input = $this->input_source_string_to_array($input_source, $input_value_after_reload);
                $this->reload($input);
                die;
        }
    }

    /**
     * @param $check_type
     * @param $checked_string
     * @param $input_source
     * @param $input_value
     */
    protected function log_and_alert($check_type, $checked_string, $input_source, $input_value) {
        $nc_core = nc_core::get_object();

        $backtrace = $this->get_backtrace();
        $serialized_backtrace = serialize($backtrace);
        $site_id = isset($nc_core->catalogue) ? $nc_core->catalogue->get_current('Catalogue_ID') : 0;
        $hash = md5(
            $site_id . "\n" .
            $nc_core->url->get_parsed_url('path') . "\n" .
            $input_source . "\n" .
            $serialized_backtrace . "\n"
        );

        $logged_data = array(
            'FilterType' => static::$filter_type_string,
            'CheckType' => $check_type,
            'Catalogue_ID' => $site_id,
            'URL' => $nc_core->url->get_full_url(),
            'Referer' => nc_array_value($_SERVER, 'HTTP_REFERER'),
            'PostData' => serialize($_POST),
            'Backtrace' => $serialized_backtrace,
            'Hash' => $hash,
            'IP' => $_SERVER['REMOTE_ADDR'],
            'ForwardedForIP' => nc_array_value($_SERVER, 'HTTP_X_FORWARDED_FOR', ''),
            'CheckedString' => $checked_string,
            'ValueSource' => $input_source,
            'Value' => $input_value,
            'EmailAlertSent' => 0,
            'SmsAlertSent' => 0,
        );

        if ($nc_core->get_settings('SecurityFilterEmailAlertEnabled') == '1') {
            $logged_data['EmailAlertSent'] = (int)$this->send_email_alert($logged_data, $backtrace);
        }

        if ($nc_core->get_settings('SecurityFilterSmsAlertEnabled') == '1') {
            $logged_data['SmsAlertSent'] = (int)$this->send_sms_alert($site_id, $logged_data);
        }

        nc_db_table::make('Security_FilterLog')->insert($logged_data);
    }

    /**
     * @param array $data
     * @param array $backtrace
     *
     * @return bool
     */
    protected function send_email_alert(array $data, array $backtrace) {
        $nc_core = nc_core::get_object();
        $from_email = $nc_core->get_settings('NotificationEmail');
        $to_email = $nc_core->get_settings('SecurityFilterEmailAlertAddress') ?: $from_email;

        if (!$to_email) {
            return false;
        }

        if ($this->event_already_logged($data['Hash'], 'email')) {
            return false;
        }

        $host = $nc_core->url->get_parsed_url('host');
        $url_without_query = $nc_core->url->source_url();
        $noreply_email = 'no-reply@' . $host;

        $subject = "$host: " . NETCAT_SECURITY_FILTER_EMAIL_SUBJECT . " ($data[FilterType])";
        $body =
            sprintf(NETCAT_SECURITY_FILTER_EMAIL_PREFIX, $url_without_query, "$data[FilterType] $data[CheckType]") .
            "\r\n\r\n" .
            $this->get_plaintext_email_block(sprintf(NETCAT_SECURITY_FILTER_EMAIL_INPUT_VALUE, $data['ValueSource']),
                $data['Value']) .
            $this->get_plaintext_email_block(NETCAT_SECURITY_FILTER_EMAIL_CHECKED_STRING, $data['CheckedString']) .
            $this->get_plaintext_email_block(NETCAT_SECURITY_FILTER_EMAIL_IP, $this->get_ip_addresses($data)) .
            $this->get_plaintext_email_block(NETCAT_SECURITY_FILTER_EMAIL_URL, $data['URL']) .
            ($data['Referer'] ?
                $this->get_plaintext_email_block(NETCAT_SECURITY_FILTER_EMAIL_REFERER, $data['Referer']) : '') .
            ($_GET ? $this->get_plaintext_email_block(NETCAT_SECURITY_FILTER_EMAIL_GET, print_r($_GET, true)) : '') .
            ($_POST ? $this->get_plaintext_email_block(NETCAT_SECURITY_FILTER_EMAIL_POST, print_r($_POST, true)) : '') .
            $this->get_plaintext_email_block(NETCAT_SECURITY_FILTER_EMAIL_BACKTRACE, print_r($backtrace, true)) .
            NETCAT_SECURITY_FILTER_EMAIL_SUFFIX . "\r\n";

        $mail = new nc_mail();
        $mail->mailbody($body);
        $result = $mail->send($to_email, $from_email ?: $noreply_email, $noreply_email, $subject,
            CMS_SYSTEM_NAME_GLOBAL . ' Alert');

        return (bool)$result;
    }

    /**
     * @param array $data
     * @param int $site_id
     *
     * @return bool
     */
    protected function send_sms_alert($site_id, array $data) {
        $nc_core = nc_core::get_object();
        $alert_phone = $nc_core->get_settings('SecurityFilterSmsAlertPhone');

        if (!$alert_phone) {
            return false;
        }

        if ($this->event_already_logged($data['Hash'], 'sms')) {
            return false;
        }

        $url_without_query = $nc_core->url->source_url();
        $message = sprintf(NETCAT_SECURITY_FILTER_SMS_PREFIX, $url_without_query);

        try {
            nc_messaging::get_instance($site_id)->send_security_message($alert_phone, $message);

            return true;
        } catch (nc_messaging_exception $e) {
            return false;
        }
    }

    /**
     * @param string $hash
     * @param string $log_type
     *
     * @return bool
     */
    protected function event_already_logged($hash, $log_type = 'email') {
        $already_logged = nc_db_table::make('Security_FilterLog')
            ->where('Hash', $hash)
            ->where($log_type == 'email' ? 'EmailAlertSent' : 'SmsAlertSent', 1)
            ->get_value('1');

        return $already_logged == '1';
    }

    /**
     * При прохождении через прокси ForwardedForIP может (при неправильных настройках?) включать IP;
     * в этом случае убираем дублирование адресов
     *
     * @param array $logged_data
     *
     * @return string
     */
    protected function get_ip_addresses($logged_data) {
        return trim($logged_data['IP'] . str_replace(" $logged_data[IP] ", '', " $logged_data[ForwardedForIP] "));
    }

    /**
     * @param $caption
     * @param $content
     *
     * @return string
     */
    protected function get_plaintext_email_block($caption, $content) {
        $divider = str_repeat("-", 60);

        return "$caption:\r\n$divider\r\n$content\r\n$divider\r\n\r\n";
    }

    /**
     * Возвращает backtrace без последних элементов о вызове nc_security_filter и nc_db
     *
     * @return array
     */
    protected function get_backtrace() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $skipped_classes = array('nc_security_filter', 'nc_db');

        $i = -1;
        while ($backtrace) {
            $skipped = false;
            $i++;

            foreach ($skipped_classes as $skipped_class) {
                $skipped = isset($backtrace[$i]['class']) && (
                        strtolower($backtrace[$i]['class']) === $skipped_class ||
                        is_subclass_of($backtrace[$i]['class'], $skipped_class)
                    );
                if ($skipped) {
                    unset($backtrace[$i]);
                    continue 2; // while
                }
            }

            if (!$skipped) {
                break;
            }
        }

        return array_values($backtrace);
    }

    /**
     * Экранирует подозрительную строку.
     *
     * @param $input_source
     * @param $input_value
     * @param string $check_type тип проверки (контекст), вызвавший необходимость экранирования
     *
     * @return string
     * @internal param $value
     * @internal param string $input строка, вызвавшая срабатывание фильтра
     */
    abstract protected function escape_input($input_source, $input_value, $check_type);

    /**
     * Обратное преобразование строки вида '$_GET[x][y]' в массив [_GET => [x => [y => value]]]
     * (для MODE_RELOAD_*)
     *
     * @param $input_source
     * @param mixed $value
     *
     * @return array
     */
    protected function input_source_string_to_array($input_source, $value = null) {
        $input_source = str_replace('][', '|', $input_source);
        $input_source = str_replace('[', '|', $input_source);
        $input_source = str_replace(']', '', $input_source);
        $input_source = ltrim($input_source, '$');
        $parts = explode('|', $input_source);
        $result = array();
        $result_reference = &$result;

        foreach ($parts as $part) {
            $result_reference[$part] = array();
            $result_reference = &$result_reference[$part];
        }
        $result_reference = $value;

        return $result;
    }

    /**
     * @param $input_substitute
     */
    protected function reload($input_substitute) {
        $nc_core = nc_core::get_object();

        $redirect_url_can_be_same = false;

        if (!empty($input_substitute['_SESSION'])) {
            $redirect_url_can_be_same = true;
            $_SESSION = array_merge($_SESSION, $input_substitute['_SESSION']);
        }

        if (isset($input_substitute['_COOKIE'])) {
            $redirect_url_can_be_same = true;
            foreach ($input_substitute['_COOKIE'] as $k => $v) {
                $nc_core->cookie->set($k, $v);
            }
        }

        $get = array_merge($_GET, nc_array_value($input_substitute, '_GET', array()));
        unset($get['REQUEST_URI']);
        if (!$_POST) {
            // Токен переадресации — используется в nc_input::add_slashes_recursive().
            // Для POST-запроса передаётся через $_POST.
            $get['nc_input_token'] = $this->get_input_token();
        }

        $query = http_build_query($get, null, '&');
        $url = $nc_core->url->get_parsed_url('path') . ($query ? '?' . $query : '');

        while (@ob_end_clean())
            ;

        if ($_POST) {
            $post = array_merge($_POST, nc_array_value($input_substitute, '_POST', array()));
            $post['nc_input_token'] = $this->get_input_token();

            echo '<form action="' . htmlspecialchars($url) . '" method="POST">';
            foreach ($post as $k => $v) {
                echo $this->make_form_input($k, $v);
            }
            echo '</form>',
            '<script>document.forms[0].submit();</script>';
        }
        else if ($redirect_url_can_be_same || $url !== $nc_core->url->get_local_url()) {
            header("Location: $url");
        }
        else {
            trigger_error('Unable to clear _GET', E_USER_WARNING);
        }

        die;
    }

    /**
     * Токен, использующийся для идентификации страницы, перезагруженной с экранированием значений
     * (для предотвращения двойного экранирования в nc_input)
     *
     * @return string
     */
    protected function get_input_token() {
        static $token;
        if (!$token) {
            $nc_core = nc_core::get_object();
            $token = $nc_core->input->fetch_post_get('nc_input_token')
                ?: sprintf('%u', crc32($nc_core->url->get_parsed_url('path') . microtime()));
        }

        return $token;
    }

    /**
     * Помечает, что значение входящего параметра экранировано слешами.
     * Используется для того, чтобы избежать двойного экранирования в nc_input::recursive_add_slashes()
     * при работе фильтров nc_security_filter_* в режиме переадресации с экранированием.
     *
     * @param $input_source
     * @param $escaped_value
     */
    protected function mark_input_as_escaped_for_redirect($input_source, $escaped_value) {
        nc_core::get_object()->input->mark_input_as_escaped($this->get_input_token(), $input_source, $escaped_value);
    }

    /**
     * @param $name
     * @param $value
     *
     * @return string
     */
    protected function make_form_input($name, $value) {
        if (is_array($value)) {
            $result = "";
            foreach ($value as $k => $v) {
                $result .= $this->make_form_input($name . "[$k]", $v);
            }

            return $result;
        }

        return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '">';
    }

    /**
     * Проверяет, есть ли в строке неэкранированная обратным слешем кавычка указанного типа
     *
     * @param $string
     * @param $quote
     *
     * @return bool
     */
    protected function string_has_unescaped_quote($string, $quote) {
        if (strpos($string, $quote) === false) {
            return false;
        }

        $escaped = false;
        $string_length = strlen($string);
        // Чтобы учесть экранирование слешей, перебираем всю строку по 1 символу...   \\'
        for ($i = 0; $i < $string_length; $i++) {
            $char = $string[$i];
            if ($char === $quote && !$escaped) {
                return true;
            }

            // экранирование дублированием (SQL) здесь не учитывается
            if ($char === '\\') {
                $escaped = !$escaped;
            }
            else {
                $escaped = false;
            }
        }

        return false;
    }

    /**
     * @return array список сообщений о проблемах в конфигурации сервера
     */
    public function get_configuration_errors() {
        return array();
    }

}
