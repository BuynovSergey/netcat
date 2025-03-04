<?php

class nc_security_2fa_method_email extends nc_security_2fa_method {

    public static function get_method_name() {
        return "E-mail";
    }

    public static function get_settings_errors($settings) {
        $nc_core = nc_core::get_object();

        // Поле не выбрано (не должно случиться, поэтому нет константы)
        if (empty($settings['AuthCodeEmailField'])) {
            return array('No field');
        }

        // Имеет неправильный формат (не должно случиться, поэтому нет константы)
        $field = $nc_core->get_component('User')->get_field($settings['AuthCodeEmailField']);
        if ($field['type'] != NC_FIELDTYPE_STRING || !preg_match('/^email\b/', $field['format'])) {
            return array('Wrong format');
        }

        // Не заполнено у текущего пользователя
        if (!$nc_core->user->get_current($settings['AuthCodeEmailField'])) {
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

        $email_to = $nc_core->user->get_by_id($this->user_id, 'Email');
        if (!$email_to) {
            return new nc_security_2fa_code(array(
                'Code' => false,
                'Hint' => NETCAT_2FA_EMAIL_NO_ADDRESS,
                'IsSent' => false,
            ));
        }

        $email_from = $this->get_setting('SpamFromEmail');

        $site_domain = $nc_core->catalogue->get_current('Domain') ?: nc_array_value($_SERVER, 'HTTP_HOST');
        $subject = sprintf(NETCAT_2FA_EMAIL_SUBJECT, $site_domain);

        $code = $this->generate_random_code();

        $body = sprintf(NETCAT_2FA_EMAIL_BODY, $site_domain, $code);

        $mail = new nc_mail();
        $mail->mailbody($body);
        $is_sent = (bool)$mail->send($email_to, $email_from, $email_from, $subject, $this->get_setting('SpamFromName'));

        if ($is_sent) {
            $hint = sprintf(NETCAT_2FA_EMAIL_IS_SENT, $email_to);
        } else {
            $hint = sprintf(NETCAT_2FA_EMAIL_NOT_SENT, $email_to) . '<br>' . NETCAT_2FA_REFRESH_HINT;
        }

        $code = new nc_security_2fa_code(array(
            'Code' => $code,
            'Hint' => $hint,
            'IsSent' => $is_sent,
        ));

        return $code;
    }

}