<?php

class nc_security_2fa_method_totp extends nc_security_2fa_method {

    static protected $is_generated_on_user_side = true;
    protected static $SECRET_FIELD = 'ncOtpSecret';

    protected $code_length = 6;

    public static function get_method_name() {
        return NETCAT_2FA_TOTP;
    }

    protected static function generate_secret() {
        $base32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $base32[mt_rand(0, 31)];
        }
        return $secret;
    }

    protected static function get_user_secret($user_id = null) {
        $nc_core = nc_core::get_object();
        if (!$user_id) {
            $user_id = $nc_core->user->get_current('User_ID');
        }
        return $nc_core->user->get_by_id($user_id, self::$SECRET_FIELD);
    }

    protected static function get_totp($secret) {
        return new nc_security_2fa_method_totp_adapter($secret);
    }

    public static function get_settings_form() {
        return parent::get_settings_form()
            ->with('user_has_secret', (bool)self::get_user_secret());
    }

    public static function get_settings_check_page($settings, $check_page_params) {
        if (PHP_VERSION_ID < 50600) {
            return '';
        }

        $user_secret = self::get_user_secret();

        $make_new_secret = !empty($check_page_params['make_new_secret']) || !$user_secret;
        if ($make_new_secret) {
            $secret = nc_array_value($check_page_params, 'secret') ?: self::generate_secret();
        } else {
            $secret = $user_secret;
        }

        $totp = self::get_totp($secret);

        return parent::get_settings_check_page($settings, $check_page_params)
            ->with('make_new_secret', (int)$make_new_secret)
            ->with('secret', $secret)
            ->with('qr_uri', $totp->get_qr_uri())
            ->with('errors', array());
    }

    protected static function check_and_save_secret_for_current_user(array $params) {
        $nc_core = nc_core::get_object();
        $user_id = $nc_core->user->get_current('User_ID');
        $errors = array();

        if (!empty($params['make_new_secret']) || !self::get_user_secret($user_id)) {
            if (empty($params['secret'])) {
                $errors[] = "No secret value"; // не должно случиться
            } else if (empty($params['code'])) {
                $errors[] = NETCAT_SECURITY_SETTINGS_2FA_TOTP_ENTER_CODE; // не введён код
            } else {
                $totp = self::get_totp($params['secret']);

                if ($totp->verify($params['code'])) {
                    // Успех. Сохраняем secret в User и переходим к странице настроек.
                    nc_db_table::make('User')->update(array(
                        self::$SECRET_FIELD => $params['secret'],
                        'User_ID' => $user_id,
                    ));
                    $nc_core->user->get_by_id($user_id, null, true);
                    $nc_core->user->get_2fa_for_current_session()->set_passed();
                } else {
                    $errors[] = NETCAT_SECURITY_SETTINGS_2FA_TOTP_WRONG_CODE; // введён некорректный код
                }
            }
        }

        return $errors;
    }

    public static function check_settings_code($settings, $params) {
        return self::check_and_save_secret_for_current_user($params);
    }

    public function get_first_logon_page() {
        if (self::get_user_secret($this->user_id)) {
            return false; // у пользователя есть код
        }

        $nc_core = nc_core::get_object();
        $params = $nc_core->input->fetch_post('params') ?: array();

        $secret = nc_array_value($params, 'secret') ?: self::generate_secret();
        $totp = self::get_totp($secret);

        if (!empty($params['code'])) {
            $errors = self::check_and_save_secret_for_current_user($params);
            if (!$errors) {
                return '<script>location.reload()</script>'; // сохранили секрет
            }
        } else {
            $errors = array();
        }

        return self::get_view('first_logon')
            ->with('secret', $secret)
            ->with('qr_uri', $totp->get_qr_uri())
            ->with('errors', $errors);
    }

    public function check_code($checked_code, nc_security_2fa_code $current_code) {
        try {
            return self::get_totp(self::get_user_secret($this->user_id))->verify($checked_code);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function get_settings_errors($settings) {
        if (PHP_VERSION_ID < 70100) {
            return array("PHP 7.1.0 is required");
        }

        return array();
    }

    public function create_code() {
        return new nc_security_2fa_code(array(
            'Hint' => NETCAT_2FA_TOTP_APP_HINT,
            'Code' => 0,
            'IsSent' => 1,
        ));
    }

}