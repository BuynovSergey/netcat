<?php

/**
 * Класс для реализации поля типа "Список"
 */
class nc_a2f_field_select extends nc_a2f_field {

    protected $empty_option_text = null;
    protected $has_default = 1;

    //  возможные значения (значение => описание)
    protected $values;

    /**
     * <select multiple>
     * Значение ($this->value, $this->default_value) задаются в виде строки,
     * разделённой запятыми ($this->multiple_delimiter), без пробелов
     */
    protected $multiple = false;
    // разделитель значений для multiple-полей
    protected $multiple_delimiter = ",";
    // size для select multiple
    protected $size;

    /**
     * @param bool $html
     * @return string
     */
    public function render_value_field($html = true) {
        $ret = $this->multiple ? $this->render_value_field_multiple()
                               : $this->render_value_field_single();

        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }

    /**
     * @return string
     */
    protected function render_value_field_single() {
        // текущее значение
        $current_value = $this->get_value_for_input();

        $ret = "<select name='" . $this->get_field_name() . "'  class='ncf_value_select'>\n";

        // если нет значения по умолчанию - выводим пустую строку тоже
        if (!strlen($this->default_value) || $this->can_inherit_values) {
            $ret .= "<option value=''>" .
                    ($this->empty_option_text === null ? NETCAT_MODERATION_LISTS_CHOOSE : $this->empty_option_text) .
                    "</option>\n";
        }

        foreach ((array)$this->values as $k => $v) {
            $k = htmlspecialchars($k, ENT_QUOTES);
            $ret .= "<option value='$k'" . ($k == $current_value ? " selected='selected'" : "") . ">" .
                    htmlspecialchars($v) . "</option>\n";
        }

        $ret .= "</select>";

        return $ret;
    }

    /**
     * @return string
     */
    protected function render_value_field_multiple() {
        $delimiter = $this->multiple_delimiter;
        $current_value = $this->get_value();
        $value_to_compare = $delimiter . $current_value . $delimiter;

        $field_name = $this->get_field_name();
        $select_id = $this->get_multiple_id();

        $ret = "<select multiple id='$select_id' class='ncf_value_select ncf_value_select_multiple'" .
                ($this->size ? " size='$this->size'" : "") .
               ">\n";

        foreach ((array)$this->values as $k => $v) {
            $is_selected = (strpos($value_to_compare, $delimiter . $k . $delimiter) !== false);
            $ret .= "<option value='" . htmlspecialchars($k, ENT_QUOTES) . "'" .
                    ($is_selected ? " selected='selected'" : "") . ">" .
                    htmlspecialchars($v) . "</option>\n";
        }

        $nc = '$nc';
        $js_delimiter = json_encode($delimiter);
        $ret .= "</select>" .
                "<input type='hidden' name='$field_name' value='" .
                htmlspecialchars($current_value, ENT_QUOTES) . "' id='{$select_id}_value'>" .
                "<script>\n" .
                    "$nc('#$select_id').change(function() { " .
                        "var value = $nc(this).val() || [];" .
                        "$nc('#{$select_id}_value').val(value.join($js_delimiter));" .
                    "});\n".
                "</script>";

        return $ret;
    }

    /**
     *
     */
    protected function get_multiple_id() {
        static $last_id = 0;
        return "nc_ncf_multiple_select_" . (++$last_id);
    }

    /**
     *
     */
    protected function get_displayed_default_value() {
        return isset($this->values[$this->default_value]) ? $this->values[$this->default_value] : null;
    }

    /**
     * @return array
     */
    public function get_subtypes() {
        return array('static', 'classificator', 'sql');
    }

}
