<?php

class nc_subscriber_tools {

    protected $core, $db, $period_table;

    protected function __construct() {
        $this->core = nc_Core::get_object();
        $this->db = $this->core->db;
        $this->period_table = 'SubscriberPeriod';
    }

    /**
     * @return self
     */
    public static function get_object() {
        // call as static
        static $storage;
        // check initiated object
        if (!isset($storage)) {
            // init object
            $storage = new self();
        }

        // return object
        return $storage;
    }

    /**
     * Проверяет сс на существование.
     * При ошибке выбрасывает исключение.
     * @param int $cc номер компонента в разделе
     * @param int $mailer_type
     * @return bool
     * @throws InvalidArgumentException|Exception
     */
    public function check_cc($cc, $mailer_type = 1) {
        $cc = (int)$cc;
        if (!$cc) {
            throw new InvalidArgumentException("<br>Incorrect param \$cc = {$cc}.<br>");
        }
        $item_exist = $this->db->get_var("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Sub_Class_ID` = '{$cc}'");
        if (!$item_exist) {
            throw new Exception("<br>Sub class ({$cc}) does not exist.<br>");
        }
        if ($mailer_type == 4) { //для серийной рассылки нужно проверить обязательное поле.
            $field_exist = $this->db->get_var(
                "SELECT f.`Field_ID`
                 FROM `Field` as f, `Sub_Class` AS sc
                 WHERE f.`Class_ID` = sc.`Class_ID`
                 AND sc.`Sub_Class_ID` = '{$cc}' 
                 AND f.`Field_Name` = 'ncDuration'"
            );
            if (!$field_exist) {
                throw new Exception("<br>Sub class ({$cc}) does not have field 'ncDuration'.<br>");
            }
        }
        return true;
    }

    /**
     * Проверяет пользователя на существование.
     * При ошибке выбрасывает исключение.
     * @param int $user_id номер пользователя
     * @return bool
     * @throws InvalidArgumentException|Exception
     */
    public function check_user($user_id) {
        $user_id = (int)$user_id;
        if (!$user_id) {
            throw new InvalidArgumentException("<br>Incorrect param \$user_id = {$user_id}.<br>");
        }
        $item_exist = $this->db->get_var(
            "SELECT `User_ID`
             FROM `User`
             WHERE `User_ID` = '{$user_id}'"
        );
        if (!$item_exist) {
            throw new Exception("<br>User ({$user_id}) does not exist.<br>");
        }

        return true;
    }

    /**
     * Проверяет период на существование.
     * При ошибке выбрасывает исключение.
     * @param int $period период
     * @return bool
     * @throws Exception
     */
    public function check_period($period) {
        $period = (int)$period;
        $item_exist = $this->db->get_var(
            "SELECT `{$this->period_table}_ID`
             FROM `Classificator_{$this->period_table}`
             WHERE `{$this->period_table}_ID` = '{$period}'"
        );
        if (!$item_exist) {
            throw new Exception("<br>Period ({$period}) does not exist.<br>");
        }

        return true;
    }

    /**
     * Возвращает период по умолчанию.
     * При ошибках выбрасывает исключение.
     * @return int период
     * @throws Exception
     */
    public function get_default_period() {
        $this->db->last_error = '';
        // классификатор с периодами
        $classificator = $this->db->get_row(
            "SELECT `Sort_Type`, `Sort_Direction`
             FROM `Classificator`
             WHERE `Table_Name` = '{$this->period_table}'",
             ARRAY_A
        );
        // проверка на ошибки
        if ($this->db->last_error) {
            throw new Exception($this->db_error(__CLASS__, __FUNCTION__));
        }
        if (empty($classificator)) {
            throw new Exception("Classificator {$this->period_table} does not exist");
        }

        $sort_direction = $classificator['Sort_Direction'] ? 'DESC' : 'ASC';

        // сортировка по полю...
        switch ($classificator['Sort_Type']) {
            case 1:
                $sort = "`{$this->period_table}_Name`";
                break;
            case 2:
                $sort = "`{$this->period_table}_Priority`";
                break;
            default:
                $sort = "`{$this->period_table}_ID`";
                break;
        }

        // выбор первого элемента
        $period = $this->db->get_var(
            "SELECT `{$this->period_table}_ID`
             FROM `Classificator_{$this->period_table}`
             WHERE `Checked` = '1'
             ORDER BY {$sort} {$sort_direction}
             LIMIT 1"
        );

        // проверка на ошибки
        if ($this->db->last_error) {
            throw new Exception($this->db_error(__CLASS__, __FUNCTION__));
        }
        if (!$period) { // таблица пустая
            throw new Exception('There is no period');
        }

        return $period;
    }
    
    /**
     * Собирает настройки периодической рассылки в строку для записи в БД, по ней будет вестить поиск
     * @param array $data - данные из формы изменения периода
     * @return string
     */
    public function prepare_period_mask($data) {
        $rules = array();
        foreach ((array) nc_array_value($data,'period_day') as $day => $value) {
            $rules[] = $day . ' ' . $data['period_time_' . $day];
        }
        if (nc_array_value($data, 'period_mask')) {
            $month_rules = explode(',' , $data['period_mask']);
            foreach ($month_rules as $key => $value) {
                if (preg_match('/(first|last|day)+/i', $value)) {
                    $rules[] = preg_replace('/(\s){2,}/', ' ', strtolower(trim($value)));
                }
            }
        }

        return implode(', ' , $rules) . ',';
    }
    
    /**
     * Разбирает строку полученную из базы данных для установки значений формы изменения периода
     * @param string $mask
     * @return bool|array
     */
    public function parse_period_mask($mask) {
        if (!$mask) {
            return false;
        }
        $mask = trim($mask, ',');
        $rules = explode(',' , $mask);
        foreach ($rules as $key => $rule) {
            $rule = trim($rule);
            if (preg_match('/^\d\s\d{1,2}$/', $rule)) {
                $day = explode(' ', $rule);
                $data['period_day_' . $day[0]] = 1;
                $data['period_time_' . $day[0]] = $day[1];
            } else {
                $month_rules[] = $rule;
            }
        }
        $data['period_mask'] = !empty($month_rules) ? implode(', ' , $month_rules) : '';
        return $data;
    }

    public function get_default_status($user_id = 0) {
        global $current_user;
        // определение пользователя
        $user_id = (int)$user_id;
        if (!$user_id) {
            $user_id = $current_user['User_ID'];
            if (!$user_id) {
                return 'wait';
            }
        }

        $default_status = $this->get_settings('ConfirmType');
        switch ($default_status) {
            case 0: // только незарегистрированным
            default:
                // пользователь зарегистрирован
                if ($this->db->get_var("SELECT `UserType` FROM `User` WHERE `User_ID` = '{$user_id}'") === 'normal') {
                    return 'on';
                }
            // break не нужен
            // Идет работа с незарегистрированным, нужно определить, был ли он ранее подписан, как в случае 1
            case 1: // только при первой подписке
                $res = $this->db->get_var("SELECT `ID` FROM `Subscriber_Subscription` WHERE `User_ID` = '{$user_id}' AND `Status` = 'on'");
                $status = $res ? 'on' : 'wait';
                break;
            case 2: // всегда
                $status = 'wait';
                break;
        }

        return $status;
    }

    public function log($mailer_id, $user_id, $action) {
        $user_id = (int)$user_id;
        $mailer_id = (int)$mailer_id;
        $action = $this->db->escape($action);
        $nc_s = nc_subscriber::get_object();
        $in_stat = $nc_s->get($mailer_id, 'InStat');
        if ($mailer_id && $user_id && $action && $in_stat) {
            $this->db->query(
                "INSERT INTO `Subscriber_Log`(`Mailer_ID`, `User_ID`, `ActionType`)
                 VALUES('{$mailer_id}', '{$user_id}', '{$action}')"
            );
        }
    }

    public function db_error($cl, $fn, $cron = 0) {
        global $perm;
        if ($cron || ($perm instanceof Permission && $perm->isSupervisor())) {
            return "Ошибка <br><b>{$this->db->last_error}</b><br>в запросе <br><b>{$this->db->last_query}</b><br><b>{$cl}</b>::<b>{$fn}</b><br>";
        } else {
            return 'Ошибка в запросе<br>';
        }
    }

    public function get_settings($item ='', $reset = 0, $catalogue = NULL) {
        //перенести настройки из `Subscriber_Settings` в `Settings`
        $settings = $this->core->get_settings('', 'subscriber', 0, 0);
        if (empty($settings)) {
            $res = $this->db->get_results('SELECT `Key`, `Value` FROM `Subscriber_Settings`', ARRAY_A);
            if (!empty($res)) {
                foreach ($res as $v) {
                    $this->core->set_settings($v['Key'], $v['Value'], 'subscriber', 0);
                }
            }
            $this->core->get_settings('', 'subscriber', 1, 0);
        }

        return $this->core->get_settings($item, 'subscriber', $reset, $catalogue);
    }

    /**
     * @param string $item
     * @param int $catalogue
     * @return mixed
     */
    public function get_subscribe_sub($item = '', $catalogue = 0) {
        static $subscription_subdivisions;

        if (!isset($subscription_subdivisions)) {
            // выборка сайтов в которых есть раздел c компонентом "список подписок"
            $subscription_subdivisions = (array)$this->db->get_results(
                "SELECT c.`Catalogue_ID`, 
                        s.`Subdivision_ID`, 
                        s.`Hidden_URL`, 
                        c.`Domain`
                 FROM `Catalogue` AS `c`, `Subdivision` AS `s`, `Sub_Class` AS `sc`
                 WHERE sc.`Class_ID` = '{$this->core->modules->get_vars('subscriber', 'SUBSCRIBER_CLASS_ID')}'
                 AND sc.`Subdivision_ID` = s.`Subdivision_ID`
                 AND s.`Catalogue_ID` = c.`Catalogue_ID`",
                ARRAY_A,
                'Catalogue_ID' // предполагается, что раздел управления подписками на каждом сайте только один
            );
        }

        $nc_core = nc_core::get_object();
        $current_catalogue = $nc_core->catalogue->get_current('Catalogue_ID');
        if (!$catalogue) {
            $catalogue = (int)$current_catalogue;
        }

        if (!isset($subscription_subdivisions[$catalogue])) {
            reset($subscription_subdivisions);
            $catalogue = key($subscription_subdivisions);
        }

        if (!$catalogue) {
            return null;
        }

        if (strlen($item)) {
            return nc_array_value($subscription_subdivisions[$catalogue], $item);
        }

        return $subscription_subdivisions[$catalogue];
    }
}