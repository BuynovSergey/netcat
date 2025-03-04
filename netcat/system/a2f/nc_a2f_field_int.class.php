<?php

class nc_a2f_field_int extends nc_a2f_field {

    protected $size;
    protected $min;
    protected $max;
    protected $has_default = 1;

    public function render_value_field($html = true) {
        if (!$this->size) { $this->size = 20; }

        $ret = "<input name='" . $this->get_field_name() . "' type='text' value='" .
               $this->get_value_for_input() .
               "' size='" . $this->size . "' class='ncf_value_text' ".
               (strlen($this->placeholder) ? " placeholder='" . htmlspecialchars($this->placeholder, ENT_QUOTES) . "'" : '') .
               ">";

        if ($html) {
            $ret = "<div class='ncf_value'>".$ret."</div>\n";
        }

        return $ret;
    }

    public function get_extend_parameters() {
        return array('min' => array('type' => 'int', 'caption' => NETCAT_CUSTOM_EX_MIN),
                'max' => array('type' => 'int', 'caption' => NETCAT_CUSTOM_EX_MAX));
    }

    public function validate($value) {
        if (!$value || $value == '') return true;

        if ($value != strval(intval($value))) {
            $this->parent->set_validation_error($this->name, NETCAT_CUSTOM_ERROR_REQUIRED_INT);
            return false;
        }

        if ($this->min && $this->min > $value) {
            $this->parent->set_validation_error($this->name, sprintf(NETCAT_CUSTOM_ERROR_MIN_VALUE, $this->min));
            return false;
        }


        if ($this->max && $this->max < $value) {
            $this->parent->set_validation_error($this->name, sprintf(NETCAT_CUSTOM_ERROR_MAX_VALUE, $this->max));
            return false;
        }

        return true;
    }

}