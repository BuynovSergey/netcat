<?php

/**
 * Класс для реализации поля типа "Логическая переменная"
 */
class nc_a2f_field_checkbox extends nc_a2f_field {

    protected $value_for_on = 'on';
    protected $value_for_off = '';
    protected $value_for_inherit = '#INHERIT#';

    public function render_value_field($html = true) {
        $field_name = $this->get_field_name();

        if ($this->can_inherit_values) { // "наследовать (#INHERIT#) / нет / да ("on")
            $inherit = ($this->value == $this->value_for_inherit || !$this->is_set);
            $is_off = (!$inherit && $this->value == $this->value_for_off);
            $is_on = (!$inherit && ($this->value == $this->value_for_on || ($this->value && !$is_off)));
            $inherit_option_state = $inherit ? 'selected' : '';
            $off_option_state = $is_off ? 'selected' : '';
            $on_option_state = $is_on ? 'selected' : '';


            $ret = "<select name='{$field_name}'>";
            $ret .= "<option value='{$this->value_for_inherit}' {$inherit_option_state}>" . CONTROL_CUSTOM_SETTINGS_INHERIT . '</option>';
            $ret .= "<option value='{$this->value_for_off}' {$off_option_state}>" . CONTROL_CUSTOM_SETTINGS_OFF . '</option>';
            $ret .= "<option value='{$this->value_for_on}' {$on_option_state}>" . CONTROL_CUSTOM_SETTINGS_ON . '</option>';
            $ret .= '</select>';
        } else {
            $checkbox_state = $this->value && $this->value != $this->value_for_off ? "checked='checked'" : '';
            $ret = "<input name='{$field_name}' type='hidden' value='{$this->value_for_off}'>";
            $ret .= "<input name='{$field_name}' type='checkbox' value='{$this->value_for_on}' {$checkbox_state} class='ncf_value_checkbox'>";
        }

        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }

    /**
     *
     */
    protected function get_displayed_default_value() {
        return $this->default_value ? CONTROL_CUSTOM_SETTINGS_ON : CONTROL_CUSTOM_SETTINGS_OFF;
    }

}