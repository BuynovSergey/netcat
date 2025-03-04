<?php

class nc_security_2fa_code extends nc_record {

    protected $table_name = 'Security_2FA_Code';
    protected $primary_key = 'Security_2FA_Code_ID';

    protected $properties = array(
        'Security_2FA_Code_ID' => null,
        'User_ID' => null,
        'Purpose' => '',
        'Code' => '',
        'Created' => null,
        'LastAttempt' => null,
        'AttemptCount' => 0,
        'IsSent' => 1,
        'Hint' => '',
    );

    protected $mapping = false;

    public static function load_for_user($user_id, $purpose) {
        $code = new self;
        $exists = $code->load_where('User_ID', $user_id, 'Purpose', $purpose);
        return $exists ? $code : null;
    }

    public function save() {
        if (!$this->get_id()) {
            $this->set('Created', time());
        }
        return parent::save();
    }

    public function get_info() {
        return array(
            'is_sent' => $this->get('IsSent'),
            'is_dynamic' => !$this->get('Code'),
            'hint' => $this->get('Hint'),
            'refresh_in' => $this->get_seconds_until_refresh(),
            'expires_in' => $this->get_seconds_until_expiration(),
            'attempts_left' => $this->get_number_of_attempts_left(),
            'attempts_hint' => $this->get_number_of_attempts_hint(),
        );
    }

    public function get_seconds_until_refresh() {
        $nc_core = nc_core::get_object();
        $refresh_timestamp = $this->get('Created') + $nc_core->get_settings('AuthCodeRefreshInSeconds');
        return max(0, $refresh_timestamp - time());
    }

    public function get_seconds_until_expiration() {
        $nc_core = nc_core::get_object();
        $expiration_timestamp = $this->get('Created') + ($nc_core->get_settings('AuthCodeValidityMinutes') ?: 100000000) * 60;
        return max(0, $expiration_timestamp - time());
    }

    public function get_number_of_attempts_left() {
        $nc_core = nc_core::get_object();
        $max_tries = $nc_core->get_settings('AuthCodeMaxAttempts');
        return max(0, $max_tries - $this->get('AttemptCount'));
    }

    public function get_number_of_attempts_hint() {
        $nc_core = nc_core::get_object();
        $attempts_left = $this->get_number_of_attempts_left();
        $attempts_hint = $nc_core->lang->get_numerical_inclination($attempts_left, explode('/', NETCAT_2FA_ATTEMPTS_LEFT));

        // "Осталось ... попыток"
        $hint = sprintf($attempts_hint, $attempts_left);

        // "Забаним, если ошибетесь"
        if ($attempts_left === 1) {
            $hint .= "<br>" . NETCAT_2FA_BLOCK_WARNING;
            if (!$nc_core->get_settings('AuthCodeUnblockManually')) {
                $unblock_minutes = $nc_core->get_settings('AuthCodeUnblockMinutes');
                $time_hint = $nc_core->lang->get_numerical_inclination($unblock_minutes, explode('/', NETCAT_2FA_BLOCK_FOR));
                $hint .= ' <nobr>' . sprintf($time_hint, $unblock_minutes) . '</nobr>';
            }
        }
        return $hint;
    }

}