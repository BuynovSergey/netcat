<?php

/**
 * "Правило" переиндексации
 * Не путать с nc_search_extension_rule!
 */
class nc_search_rule extends nc_search_data_persistent {

    protected $properties = array(
            'id' => null,
            'name' => null,
            'site_id' => null,
            'area_string' => null,
            'interval' => 1,
            'interval_type' => 'on_request', // on_request, minute, hour, day, day_of_month
            'hour' => 0,
            'minute' => 0,
            // time stored as integer to simplify its processing
            'last_start_time' => null,
            'last_finish_time' => null,
            'last_result' => array(),
    );
    protected $table_name = 'Search_Rule';
    protected $serialized_properties = array('last_result');
    protected $mapping = array(
            'id' => 'Rule_ID',
            'site_id' => 'Catalogue_ID',
            '_generate' => true,
    );

    /**
     * Получить timestamp следующей даты с указанным днём недели
     */
    protected function get_day_of_month_future_timestamp($day_of_month, $hours, $minutes) {
        if ($day_of_month > 31) {
            $day_of_month = 1;
        }   // WTF do you want?
        // (а) день будет в этом месяце
        // (б) в этом месяце уже прошёл этот день
        // (в) в месяце нет этого дня (29, 30, 31 число)
        $now = getdate();
        $month = $now["mon"];
        $year = $now["year"];

        if ($now["mday"] > $day_of_month ||
                ($now["mday"] == $day_of_month &&
                (($now["hours"] * 60 + $now["minutes"]) > ($hours * 60 + $minutes)))) {
            // поезд ушёл
            if ($month == 12) {
                $year++;
                $month = 1;
            } else {
                $month++;
            }
        }

        $days_in_month = array(0, 31, ($year % 4) ? 28 : 29 /* Next exception will be in 2100, hope it gets rewritten before that happens */,
                31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

        if ($days_in_month[$month] < $day_of_month) {
            // к счастью в декабре 31 день, а за коротким месяцем всегда идёт длинный
            $month++;
        }

        return mktime($hours, $minutes, 0, $month, $day_of_month, $year);
    }

    /**
     *
     * @param integer $start_timestamp
     * @param string $interval_string   e.g. "1 day"
     * @return integer
     */
    protected function get_interval_future_timestamp($start_timestamp, $interval_string) {
        $now = time(); // будет проблема в том случае, если выполнение задачи будет занимать менее 1 секунды
        while ($start_timestamp < $now) {
            $start_timestamp = strtotime($interval_string, $start_timestamp);
        }
        return $start_timestamp;
    }

    /**
     * @return nc_search_rule self
     */
    public function schedule_next_run() {
        $type_scheduled = nc_search_scheduler_intent::SCHEDULED;
        $interval_type = $this->get('interval_type');

        $intent = new nc_search_scheduler_intent();
        // remove old scheduled intents
        $where = $intent->make_condition('area_string', $this->get_id(),
                        'type', $type_scheduled);
        nc_db()->query("DELETE FROM `{$intent->get_table_name()}` WHERE $where");

        if ($interval_type != 'on_request') {
            $interval = $this->get('interval');

            if ($interval_type == 'day_of_month') {
                $next_scheduled_time = $this->get_day_of_month_future_timestamp($interval, $this->get('hour'), $this->get('minute'));
            } else { // 'minute', 'hour', 'day'
                // hour, minute содержат время начала применения правила
                $start_timestamp = mktime($this->get('hour'), $this->get('minute'), 0);
                $next_scheduled_time = $this->get_interval_future_timestamp($start_timestamp, "$interval $interval_type");
            }

            $intent->set('start_time', $next_scheduled_time)
                    ->set('type', nc_search_scheduler_intent::SCHEDULED)
                    ->set('area_string', $this->get_id())
                    ->save();
        }

        return $this;
    }

    /**
     * Область индексации.
     * Если опция area_string пуста (=«индексировать весь сайт»), возвращает
     * соответствующую область "siteN".
     * @return string
     */
    public function get_area_string() {
        $area_string = $this->get('area_string');
        if (!$area_string) {
            $area_string = "site".$this->get('site_id');
        }
        return $area_string;
    }

    /**
     *
     * @param string $option
     * @param mixed $value
     * @param boolean $add_new_option
     * @return nc_search_data
     */
    public function set($option, $value, $add_new_option = false) {
        // особенности формы редактирования
        if (in_array($option, array('hour', 'minute', 'interval')) && is_array($value)) {
            foreach ($value as $v) {
                if ($v != '') {
                    $value = $v;
                    break;
                }
            }
            if (is_array($value)) {
                $value = ($option == 'interval' ? 1 : 0);
            }
        }
        return parent::set($option, $value, $add_new_option);
    }

    /**
     *
     */
    public function save() {
        if (!$this->get('interval_type')) {
            throw new nc_search_data_exception(NETCAT_MODULE_SEARCH_ADMIN_RULE_MUST_HAVE_INTERVAL_TYPE);
        }

        $this->set('area_string', trim($this->get('area_string')));
        if ($this->get('interval_type') == 'daily') {
            $this->set('interval', 1);
            $this->set('interval_type', 'day');
        }

        parent::save();
        $this->schedule_next_run();
        return $this;
    }

    /**
     * @throws nc_record_exception
     */
    public function delete() {
        $db = nc_db();

        // delete associated schedule and task entries
        $id = $db->escape($this->get_id());
        $db->query("DELETE FROM `Search_Schedule` WHERE `AreaString` = '$id'");
        $db->query("DELETE FROM `Search_Task` WHERE `Rule_ID` = '$id'");

        return parent::delete();
    }

    /**
     *
     */
    public function get_schedule_string() {
        $nc_core = nc_Core::get_object();

        $intervals = array(
                'hour' => NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_N_HOURS,
                'minute' => NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_N_MINUTES,
                'day' => NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_N_DAYS,
                'day_of_month' => NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_X_DAY,
        );

        $interval = $this->get('interval');
        $type = $this->get('interval_type');
        $time = sprintf('%02d:%02d', $this->get('hour'), $this->get('minute'));

        if ($type === 'on_request') {
            $schedule = NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_ON_REQUEST;
        } elseif ($type === 'day' && $interval == 1) {
            $schedule = sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_DAILY, $time);
        } elseif (isset($intervals[$type])) {
            $not_dom = ($type !== 'day_of_month');
            $schedule = sprintf($intervals[$type], ($interval == 1 && $not_dom ? '' : $interval), $time);

            // Обработка кириллицы
            if ($not_dom && $nc_core->lang->detect_lang(true) === 'ru') {

                // Заменить на корректную форму слово "каждые" и название интервала
                $forms = explode(' ', constant('NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_' . strtoupper($type)));
                $form = $nc_core->lang->get_ru_numerical_inclination($interval, $forms);
                $schedule = str_replace($forms[1], $form, $schedule);

                // Каждые -> каждую/каждый
                if ($form === $forms[0]) {
                    $schedule = str_replace(
                                    NETCAT_MODULE_SEARCH_ADMIN_RULE_EVERY_SEVERAL,
                                    ($type === 'minute' ?
                                            NETCAT_MODULE_SEARCH_ADMIN_RULE_EVERY_SINGLE_FEMININE :
                                            NETCAT_MODULE_SEARCH_ADMIN_RULE_EVERY_SINGLE_MASCULINE),
                                    $schedule);
                }
            }
        } else {
            $schedule = '???';
        }
        return $schedule;
    }

    /**
     *
     */
    public function get_site_name() {
        $nc_core = nc_Core::get_object();
        $cat = $nc_core->catalogue;
        $site_id = $this->get('site_id');
        try {
            $site_name = $cat->get_by_id($site_id, "Catalogue_Name");
            $domain = $cat->get_by_id($site_id, "Domain");
            if ($domain) {
                $domain_decode = nc_search_util::decode_host($domain);
                if (!$nc_core->NC_UNICODE) { $domain_decode = $nc_core->utf8->utf2win($domain_decode); }
                $site_name .= " (".$domain_decode.")";
            }
        } catch (Exception $e) {
            $site_name = sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_NONEXISTENT_SITE, $site_id);
        }
        return $site_name;
    }

    /**
     * @return string
     */
    public function get_site_scheme() {
        return nc_Core::get_object()->catalogue->get_scheme_by_id($this->get('site_id'));
    }

    /**
     * @return array  keys: included, excluded
     */
    public function get_area_description() {
        $area = new nc_search_area($this->get('area_string'), $this->get('site_id'));
        return array(
                'included' => $area->get_description(false),
                'excluded' => $area->get_description(true)
        );
    }

}
