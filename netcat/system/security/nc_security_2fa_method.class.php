<?php

abstract class nc_security_2fa_method {

    /** @var bool код не высылается пользователю, а генерируется им (например, OTP) */
    static protected $is_generated_on_user_side = false;

    /** @var int  */
    protected $code_length = 4;
    /** @var nc_security_2fa  */
    protected $_2fa;
    /** @var int  */
    protected $user_id;

    public static function get_method_name() {
        return get_called_class();
    }

    protected static function get_view($view_name) {
        // Если вы создаётё собственный класс (не являющийся частью дистрибутива Netcat),
        // не располагайте свои шаблоны в ADMIN_FOLDER! Переопределите этот метод полностью,
        // либо не используйте его.
        $nc_core = nc_core::get_object();
        $path = $nc_core->ADMIN_FOLDER . 'views/security/auth/' . get_called_class() . '/' . $view_name;
        return $nc_core->ui->view($path);
    }

    public static function get_settings_form() {
        return self::get_view('settings');
    }

    public static function get_settings_errors($settings) {
        return array();
    }

    public static function get_settings_check_page($settings, $check_page_params) {
        return self::get_view('settings_check')->with('settings', $settings);
    }

    public static function get_settings_check_page_code_part($settings, $check_page_params) {
        if (!static::$is_generated_on_user_side) {
            $nc_core = nc_core::get_object();
            $_2fa = $nc_core->user->get_2fa_for_current_session()->with_settings($settings);

            $code_purpose =
                nc_array_value($check_page_params, 'code_purpose')
                ?: get_called_class() . '::' . __FUNCTION__ . '@' . $_SERVER['REQUEST_TIME'];
            $code = $_2fa->get_current_code($code_purpose);

            return self::get_view('../2fa_settings_check_code')->with('code', $code);
        }
        return '';
    }

    public static function check_settings_code($settings, $params) {
        $errors = array();

        if (!static::$is_generated_on_user_side) {
            $nc_core = nc_core::get_object();
            $_2fa = $nc_core->user->get_2fa_for_current_session()->with_settings($settings);

            $check_result = $_2fa->check_code(nc_array_value($params, 'code'), nc_array_value($params, 'code_purpose'));
            if (!$check_result['passed']) {
                $errors[] = NETCAT_SECURITY_SETTINGS_2FA_WRONG_CODE;
            }
        }

        return $errors;
    }

    public function __construct(nc_security_2fa $_2fa) {
        $this->_2fa = $_2fa;
        $this->user_id = $_2fa->get_user_id();
    }

    protected function get_setting($key) {
        return $this->_2fa->get_setting($key);
    }

    public function get_code_length() {
        return $this->code_length;
    }

    /**
     * @return nc_security_2fa_code
     */
    abstract public function create_code();

    public function check_code($checked_code, nc_security_2fa_code $current_code) {
        if ($current_code->get('Code') == $checked_code) {
            return true;
        }
        return false;
    }

    protected function generate_random_code() {
        $result = '';
        for ($i = 0; $i < $this->code_length; $i++) {
            $result .= mt_rand(0, 9);
        }
        return $result;
    }

    public function get_first_logon_page() {
        return false;
    }

}