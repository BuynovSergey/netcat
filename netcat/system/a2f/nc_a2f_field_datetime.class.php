<?php

/**
 * Класс для реализации поля типа "Дата и время"
 */
class nc_a2f_field_datetime extends nc_a2f_field {

    protected $can_have_initial_value = false;

    protected $day, $month, $year;
    protected $hours, $minutes, $seconds;

    function render_value_field($html = true) {
        $nc_core = nc_Core::get_object();
        $ret = '';
        if (nc_module_check_by_keyword('calendar', 0)) {
            $ret .= nc_set_calendar();
        }
        $ret .= "<input id='" . $this->get_field_name('day') . "' name='" . $this->get_field_name('day') . "' type='text' value='" . htmlspecialchars(($this->day ? $this->day : $this->default_value), ENT_QUOTES) . "' size='2' maxlength='2'  class='ncf_value_datetime_day'><span>.</span>";
        $ret .= "<input id='" . $this->get_field_name('month') . "' name='" . $this->get_field_name('month') . "' type='text' value='" . htmlspecialchars(($this->month ? $this->month : $this->default_value), ENT_QUOTES) . "' size='2' maxlength='2'  class='ncf_value_datetime_month'><span>.</span>";
        $ret .= "<input id='" . $this->get_field_name('year') . "' name='" . $this->get_field_name('year') . "' type='text' value='" . htmlspecialchars(($this->year ? $this->year : $this->default_value), ENT_QUOTES) . "' size='2' maxlength='4'  class='ncf_value_datetime_year'>";
        $ret .= "<input id='" . $this->get_field_name('hours') . "' name='" . $this->get_field_name('hours') . "' type='text' value='" . htmlspecialchars(($this->hours ? $this->hours : $this->default_value), ENT_QUOTES) . "' size='2' maxlength='2' class ='ncf_value_datetime_hours'><span>:</span>";
        $ret .= "<input id='" . $this->get_field_name('minutes') . "' name='" . $this->get_field_name('minutes') . "' type='text' value='" . htmlspecialchars(($this->minutes ? $this->minutes : $this->default_value), ENT_QUOTES) . "' size='2' maxlength='2'  class='ncf_value_datetime_minutes'><span>:</span>";
        $ret .= "<input id='" . $this->get_field_name('seconds') . "' name='" . $this->get_field_name('seconds') . "' type='text' value='" . htmlspecialchars(($this->seconds ? $this->seconds : $this->default_value), ENT_QUOTES) . "' size='2' maxlength='2' class='ncf_value_datetime_seconds'>";

        if (nc_module_check_by_keyword('calendar', 0)) {
            $ret .= "<div class='calendar'>
                      <img id='nc_calendar_popup_img_" . $this->get_field_name('day') . "' onclick='nc_calendar_popup(\"" . $this->get_field_name('day') . "\",\"" . $this->get_field_name('month') . "\", \"" . $this->get_field_name('year') . "\", \"0\");' src='" . nc_module_path('calendar') . "images/calendar.jpg' />
                    </div>
                   <div class='cl' id='nc_calendar_popup_" . $this->get_field_name('day') . "'></div>";
        }
        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }

    public function save($value) {
        $this->value = '';
        if (is_array($value)) {
            $ar = array('day', 'month', 'year', 'hours', 'minutes', 'seconds');
            foreach ($ar as $v) { $this->$v = $value[$v]; }
            $this->value = $this->year . '-' . $this->month . '-' . $this->day . ' ' . $this->hours . ':' . $this->minutes . ':' . $this->seconds;
        }
    }

    public function set_value($value) {
        $this->value = false;

        if (preg_match("/(\d+)\-(\d+)\-(\d+) (\d*):(\d*):(\d*)/", $value, $match)) {
            $this->year = $match[1];
            $this->month = $match[2];
            $this->day = $match[3];
            $this->hours = $match[4];
            $this->minutes = $match[5];
            $this->seconds = $match[6];
        }

        $this->value['day'] = $this->day;
        $this->value['month'] = $this->month;
        $this->value['year'] = $this->year;
        $this->value['hours'] = $this->hours;
        $this->value['minutes'] = $this->minutes;
        $this->value['seconds'] = $this->seconds;
        if ($this->day) {
            $this->value['date'] = $this->day . "." . $this->month . "." . $this->year;
            $this->is_set = true;
        }
        if ($this->hours) {
            $this->value['time'] = $this->hours . ":" . $this->minutes . ":" . $this->seconds;
            $this->is_set = true;
        }
        $this->value['datetime'] = $this->value['date'] . " " . $this->value['time'];
        return 0;
    }

}
