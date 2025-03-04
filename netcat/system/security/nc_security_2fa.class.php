<?php

class nc_security_2fa {

    // Константы для режима работы
    const DISABLED = 0;
    const REQUIRED_FOR_SUPERVISORS = 1;
    const REQUIRED_FOR_ADMIN_MODE_USERS = 2;
    const REQUIRED_FOR_EDITORS = 3;

    /** @var int[] */
    protected static $bypass_users = array();

    /** @var int */
    protected $user_id;
    /** @var string */
    protected $session_id;
    /** @var nc_security_2fa_method */
    protected $method;
    /** @var string[] */
    protected static $available_methods = array(
        'nc_security_2fa_method_totp',
        'nc_security_2fa_method_sms',
        'nc_security_2fa_method_email',
    );
    /** @var array  */
    protected $settings_override = array();

    /**
     * Отключает проверку второго фактора для пользователя с указанным ID или логином.
     * Можно использовать для временного решения проблем со входом в систему, добавив
     * вызов метода в /netcat/modules/default/function.inc.php.
     * Обязательно удалите вызов метода после решения проблем с двухфакторной аутентификацией.
     *
     * @param string|int $user_id_or_login,... Логин или идентификатор пользователя,
     *      или '*' для отключения для всех пользователей. Можно указать несколько через запятую.
     * @return void
     */
    public static function bypass($user_id_or_login) {
        self::$bypass_users += func_get_args();
    }

    /**
     * @return string[]
     */
    public static function get_available_methods() {
        return self::$available_methods;
    }

    /**
     * @param string $class_name
     * @return void
     */
    public static function register_method($class_name) {
        self::check_method_class_name($class_name);
        self::$available_methods[] = $class_name;
    }

    /**
     * @param string $class_name
     * @return void
     */
    protected static function check_method_class_name($class_name) {
        if (!is_a($class_name, 'nc_security_2fa_method', true)) {
            throw new UnexpectedValueException("$class_name is not a descendant of nc_security_2fa_method");
        }
    }

    /**
     * @param Permission $permission
     * @param int $mode self:: constant
     * @param bool $respect_bypass если true, учитывает исключения, добавленные через nc_security_2fa::bypass()
     * @return bool
     */
    public static function will_require_2fa(Permission $permission, $mode = null, $respect_bypass = true) {
        $nc_core = nc_core::get_object();

        if ($mode === null) {
            $mode = (int)$nc_core->get_settings('AuthCodeMode');
        } else {
            $mode = (int)$mode;
        }

        if ($mode === self::DISABLED) {
            return false;
        }

        if ($respect_bypass && self::$bypass_users && self::should_bypass_user($permission)) {
            return false;
        }

        if ($mode === self::REQUIRED_FOR_SUPERVISORS && $permission->isSupervisor()) {
            return true;
        }

        if ($mode === self::REQUIRED_FOR_ADMIN_MODE_USERS && $permission->isInsideAdmin()) {
            return true;
        }

        if ($mode === self::REQUIRED_FOR_EDITORS && $permission->isModeratorOrAdmin()) {
            return true;
        }

        return false;
    }

    protected static function should_bypass_user(Permission $permission) {
        if (in_array('*', self::$bypass_users, true)) {
            return true;
        }
        $user_id = $permission->GetUserID();
        if (in_array($user_id, self::$bypass_users, false)) {
            return true;
        }
        $nc_core = nc_core::get_object();
        $user_login = $nc_core->user->get_by_id($user_id, $nc_core->AUTHORIZE_BY);
        if (in_array($user_login, self::$bypass_users, true)) {
            return true;
        }
        return false;
    }

    public static function for_user($user_id) {
        return new self($user_id, '');
    }

    /**
     * @param int $user_id
     * @param string $session_id
     */
    public function __construct($user_id, $session_id) {
        $this->user_id = (int)$user_id;
        $this->session_id = $session_id;
    }

    /**
     * @param $key
     * @return string|int
     */
    public function get_setting($key) {
        return isset($this->settings_override[$key])
            ? $this->settings_override[$key]
            : nc_core::get_object()->get_settings($key);
    }

    /**
     * @param array $settings
     * @return nc_security_2fa (новый экземпляр)
     */
    public function with_settings(array $settings) {
        $clone = clone $this;
        $clone->settings_override = $settings + $this->settings_override;
        return $clone;
    }

    /**
     * @return nc_security_2fa_method
     */
    protected function get_method() {
        if (!$this->method) {
            $class_name = $this->get_setting('AuthCodeMethod');
            self::check_method_class_name($class_name);
            $this->method = new $class_name($this);
        }
        return $this->method;
    }

    /**
     * @return void
     */
    protected function delete_expired_codes() {
        $expired_timestamp = time() - ($this->get_setting('AuthCodeValidityMinutes') ?: 1) * 60;
        nc_db()->query(
            "DELETE FROM `Security_2FA_Code` WHERE `User_ID` = $this->user_id AND `Created` < $expired_timestamp"
        );
    }

    /**
     * Получает действующий код. Создаёт новый, если действущего кода нет.
     *
     * @param string $purpose
     * @return nc_security_2fa_code
     */
    public function get_current_code($purpose) {
        $this->delete_expired_codes();
        return nc_security_2fa_code::load_for_user($this->user_id, $purpose) ?: $this->create_code($purpose);
    }

    /**
     * @param string $purpose
     * @return nc_security_2fa_code
     */
    protected function create_code($purpose) {
        $code = $this->get_method()->create_code();

        // Обязательные свойства:
        $code->set('User_ID', $this->user_id);
        $code->set('Purpose', $purpose);

        if ($code->get('IsSent')) {
            $code->save();
        }

        return $code;
    }

    /**
     * @param $purpose
     * @return nc_security_2fa_code
     */
    public function make_new_code($purpose) {
        $db = nc_db();
        $db->query("DELETE FROM `Security_2FA_Code` WHERE `User_ID` = $this->user_id AND `Purpose` = '" . $db->escape($purpose) . "'");
        return $this->create_code($purpose);
    }

    /**
     * @param string $checked_code
     * @param string $purpose
     * @return array
     */
    public function check_code($checked_code, $purpose) {
        $current_code = $this->get_current_code($purpose);
        $passed = $this->get_method()->check_code($checked_code, $current_code);

        if ($passed) {
            $current_code->delete();
        } else {
            $current_code->set_values(array(
                'LastAttempt' => time(),
                'AttemptCount' => $current_code->get('AttemptCount') + 1,
            ));
            if ($current_code->get('IsSent')) {
                $current_code->save();
            }
        }

        return array('passed' => $passed) + $current_code->get_info();
    }

    /**
     * @return int
     */
    public function get_code_length() {
        return $this->get_method()->get_code_length();
    }

    /**
     * @return int
     */
    public function get_user_id() {
        return $this->user_id;
    }

    /**
     * @return bool
     */
    public function is_passed() {
        if ($this->session_id) {
            $db = nc_core::get_object()->db;
            return (bool)$db->get_var(
                "SELECT `Passed2FA` 
                   FROM `Session` 
                  WHERE `Session_ID` = '" . $db->escape($this->session_id) . "'"
            );
        }
        return false;
    }

    /**
     * @return void
     */
    public function set_passed() {
        if ($this->session_id) {
            $db = nc_core::get_object()->db;
            $db->query(
                "UPDATE `Session` 
                    SET `Passed2FA` = 1 
                  WHERE `Session_ID` = '" . $db->escape($this->session_id) . "'"
            );
            $this->unblock_user();
        }
    }

    /**
     * @param int $blocked_permanently
     * @param int $blocked_timestamp
     * @return void
     */
    protected function update_user($blocked_permanently, $blocked_timestamp) {
        $nc_core = nc_core::get_object();
        $block = $blocked_permanently || $blocked_timestamp;

        $nc_core->event->execute($block ? nc_Event::BEFORE_USER_BLOCKED : nc_event::BEFORE_USER_UNBLOCKED, $this->user_id);

        $nc_core->db->query(
            "UPDATE `User` 
                SET `ncBlockedPermanently` = $blocked_permanently,
                    `ncBlockedTimestamp` = $blocked_timestamp,
                    `LastUpdated` = `LastUpdated`
              WHERE `User_ID` = $this->user_id"
        );

        $nc_core->event->execute($block ? nc_Event::AFTER_USER_BLOCKED : nc_event::AFTER_USER_UNBLOCKED, $this->user_id);
    }

    /**
     * @return void
     */
    public function block_user() {
        $this->update_user((int)$this->get_setting('AuthCodeUnblockManually'), time());
    }

    /**
     * @return void
     */
    public function unblock_user() {
        if ($this->is_user_blocked()) {
            $this->update_user(0, 0);

            $nc_core = nc_core::get_object();
            $max_attempts = (int)$this->get_setting('AuthCodeMaxAttempts');
            $nc_core->db->query(
                "DELETE FROM `Security_2FA_Code` WHERE `User_ID` = $this->user_id AND `AttemptCount` >= $max_attempts"
            );
        }
    }

    /**
     * @return bool
     */
    public function is_user_blocked() {
        $nc_core = nc_core::get_object();
        if ($nc_core->user->get_by_id($this->user_id, 'ncBlockedPermanently')) {
            return true;
        }

        $blocked_timestamp = $nc_core->user->get_by_id($this->user_id, 'ncBlockedTimestamp');
        if ($blocked_timestamp) {
            return $blocked_timestamp + $this->get_setting('AuthCodeUnblockMinutes') * 60 > time();
        }

        return false;
    }

    /**
     * @return string|false
     */
    public function get_first_logon_page() {
        return $this->get_method()->get_first_logon_page();
    }

}