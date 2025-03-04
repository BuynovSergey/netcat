<?php

class nc_security_2fa_method_sms extends nc_security_2fa_method {

    protected $user_phone_number;

    public function __construct(nc_security_2fa $_2fa) {
        parent::__construct($_2fa);

        $nc_core = nc_core::get_object();
        $phone_field = $this->get_setting('AuthCodePhoneField');
        $this->user_phone_number = $nc_core->user->get_by_id($this->user_id, $phone_field);
    }


    public static function get_method_name() {
        return "SMS";
    }

    public static function get_settings_errors($settings) {
        $nc_core = nc_core::get_object();

        // Модуль не включен
        if (!nc_module_check_by_keyword('messaging')) {
            return array(sprintf(NETCAT_SECURITY_SETTINGS_2FA_SMS_DISABLED, $nc_core->ADMIN_PATH . '#module.list'));
        }

        // Модуль не настроен
        if (!nc_messaging::get_instance()->can_send(nc_messaging_settings::SERVICE_TYPE_AUTH)) {
            return array(sprintf(NETCAT_SECURITY_SETTINGS_2FA_SMS_DISABLED, $nc_core->ADMIN_PATH . '#module.messaging.settings'));
        }

        // Поле не выбрано (не должно случиться, поэтому нет константы)
        if (empty($settings['AuthCodePhoneField'])) {
            return array('No field');
        }

        // Имеет неправильный формат (не должно случиться, поэтому нет константы)
        $field = $nc_core->get_component('User')->get_field($settings['AuthCodePhoneField']);
        if ($field['type'] != NC_FIELDTYPE_STRING || !preg_match('/^phone\b/', $field['format'])) {
            return array('Wrong format');
        }

        // Не заполнено у текущего пользователя
        if (!$nc_core->user->get_current($settings['AuthCodePhoneField'])) {
            return array(sprintf(
                NETCAT_SECURITY_SETTINGS_2FA_USER_HAS_NO_VALUE,
                $field['description'] ?: $field['name'],
                $nc_core->ADMIN_PATH . "#user.edit(" . $nc_core->user->get_current('User_ID') . ")"
            ));
        }

        return array();
    }

    public function create_code() {
        $nc_core = nc_core::get_object();

        if (!$this->user_phone_number) {
            return new nc_security_2fa_code(array(
                'Code' => false,
                'Hint' => NETCAT_2FA_SMS_NO_PHONE,
                'IsSent' => false,
            ));
        }

        $site_domain = $nc_core->catalogue->get_current('Domain') ?: nc_array_value($_SERVER, 'HTTP_HOST');
        $code = $this->generate_random_code();
        $message = sprintf(NETCAT_2FA_SMS_MESSAGE, $code, $site_domain);

        $sms_error_message = '';

        try {
            $result = nc_messaging::get_instance()->send_auth_message($this->user_phone_number, $message);
            $is_sent = (bool)$result->get_message_id();
        } catch (Exception $e) {
            $is_sent = false;
            global $perm;
            if ($perm instanceof Permission && $perm->isSupervisor()) {
                $sms_error_message = '<br>' . $e->getMessage();
            }
        }

        if ($is_sent) {
            $hint = sprintf(NETCAT_2FA_SMS_IS_SENT, $this->user_phone_number);
        } else {
            $hint = sprintf(NETCAT_2FA_SMS_NOT_SENT, $this->user_phone_number) . '<br>' . NETCAT_2FA_REFRESH_HINT . $sms_error_message;
        }

        $code = new nc_security_2fa_code(array(
            'Code' => $code,
            'Hint' => $hint,
            'IsSent' => $is_sent,
        ));

        return $code;
    }

    public function get_first_logon_page() {
        if ($this->user_phone_number) {
            return false;
        }

        return NETCAT_2FA_SMS_NO_PHONE;
    }

}