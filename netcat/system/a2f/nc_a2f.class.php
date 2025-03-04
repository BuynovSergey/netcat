<?php

/**
 * [Array to Form]
 * Класс для преобразования массива с данными + массива с настройками
 * в элементы html-формы и сохранения результатов формы в виде строки
 */
class nc_a2f {

    /** @var nc_a2f_field[]   поля формы */
    protected $fields = array();

    // ошибки, возникшие при проверке значений
    protected $validation_errors = array();
    /**
     * префикс: использовать у элементов формы в названии элементов
     * (e.g. если prefix='settings', названия полей формы будут
     *  вида name='settings[field1]')
     */
    protected $array_name;
    protected $settings_array;

    /** @var array  параметры полей по умолчанию */
    protected $field_defaults = array();

    /** @var bool  показывать колонку со значениями по умолчанию? */
    protected $show_default_values = true;

    /** @var bool  показывать строку с заголовками столбцов таблицы? */
    protected $show_header = true;

    /** @var bool  может наследовать значения от родительских объектов */
    protected $can_inherit_values = false;

    /** @var mixed  Сущность, к которой относится настройка */
    protected $entity;

    /** @var mixed  Тип сущности (например, инфоблок или макет дизайна) */
    protected $entity_type;

    /**
     * конструктор
     *
     * @param string|array $field_settings $array_name массив с настройками
     *                      или соответствующая строка.
     *                      E.g. $settings_array = array(
     *                             "field1_name" => array("type" => "string")
     *                           );
     * @param string имя массива в форме
     * @param bool $can_inherit_values
     * @param mixed $entity
     */
    public function __construct($field_settings, $array_name = "nc_a2f", $can_inherit_values = false, $entity = null, $entity_type = null) {

        $this->array_name = $array_name;
        $settings_array = is_array($field_settings) ? $field_settings : self::evaluate($field_settings);

        $this->can_inherit_values = $can_inherit_values;

        if (!is_array($settings_array)) {
            trigger_error("nc_a2f::nc_a2f() - first parameter is not an array or cannot be evaluated as an array", E_USER_WARNING);
            return;
        }

        $this->set_entity($entity);
        $this->set_entity_type($entity_type);

        // $this->settings_array = $settings_array;

        foreach ($settings_array as $field_name => $field_settings) {
            $type = $field_settings['type'];
            if (!$type) {
                trigger_error("(nc_a2f) missing type for the field '" . $field_name . "'", E_USER_WARNING);
                continue;
            }
            $class_name = "nc_a2f_field_" . $type;
            if (isset($field_settings['subtype']) && $field_settings['subtype']) {
                $class_name .= '_' . $field_settings['subtype'];
            }

            if (!class_exists($class_name)) {
                trigger_error("(nc_a2f) wrong subtype '" . $field_settings['subtype'] . "' for the field '" . $field_name . "'. " . $class_name . " not found.", E_USER_WARNING);
                continue;
            }

            // push name into settings array
            $field_settings['name'] = $field_name;
            $field_settings['can_inherit_values'] = $can_inherit_values;

            $this->fields[$field_name] = new $class_name($field_settings, $this);
        }
    }

    /**
     * @return $this
     */
    public function set_initial_values() {
        foreach ($this->fields as $field) {
            if ($field->can_have_initial_value()) {
                $field->set_value($field->get_initial_value());
            }
        }
        return $this;
    }

    /**
     * @return nc_a2f_field[]
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * @param $field_type
     * @param array $defaults
     * @return $this
     */
    public function set_field_defaults($field_type, array $defaults) {
        foreach ($this->fields as $field) {
            if ($field->get_type() == $field_type || $field->get_full_type() == $field_type) {
                $field->set_defaults($defaults);
            }
        }
        return $this;
    }

    /**
     * @param bool $new_value
     * @return $this
     */
    public function show_default_values($new_value) {
        $this->show_default_values = (bool)$new_value;
        return $this;
    }

    /**
     * @param $new_value
     * @return $this
     */
    public function show_header($new_value) {
        $this->show_header = (bool)$new_value;
        return $this;
    }

    /**
     * @return bool
     */
    public function should_show_default_values() {
        return $this->show_default_values;
    }

    /**
     * @param string|array|ArrayAccess|Iterator $values
     * @return $this
     */
    public function set_value($values) {
        if (!(is_array($values) || (is_object($values) && $values instanceof ArrayAccess && $values instanceof Iterator))) {
            $values = self::evaluate($values);
        }

        if (is_array($values) || $values instanceof ArrayAccess) {
            foreach ($values as $name => $value) {
                if (isset($this->fields[$name])) {
                    $this->fields[$name]->set_value($value);
                }
            }
        }
        return $this;
    }

    public function set_entity($entity) {
        $this->entity = $entity;
    }

    public function get_entity() {
        return $this->entity;
    }

    public function set_entity_type($entity_type) {
        $this->entity_type = $entity_type;
    }

    public function get_entity_type() {
        return $this->entity_type;
    }

    /**
     * Alias for set_value()
     * @param $values
     * @return $this
     */
    final public function set_values($values) {
        return $this->set_value($values);
    }

    /**
     * Используется перед сохранением значений в базе данных.
     * В отличие от set_values() может выполнять дополнительные действия, например,
     * удаление и замену старого файла.
     * @param array $values
     * @return $this
     */
    public function save($values) {
        $values = (array)$values;
        $keys = array_keys($values);

        foreach ($this->fields as $name => $f) {
            if (in_array($name, $keys)) {
                $f->save($values[$name]);
            }
        }
        return $this;
    }

    /**
     * Сохранение данных из указанного массива в $_POST и $_FILES.
     * @param $request_data_key
     * @return nc_a2f
     */
    public function save_from_request_data($request_data_key) {
        $files = array();
        if (isset($_FILES[$request_data_key]['tmp_name'])) {
            foreach ($_FILES[$request_data_key]['tmp_name'] as $field_name => $tmp_file_name) {
                $files[$field_name] = $tmp_file_name;
            }
        }

        $data = array_merge(
            $files,
            nc_array_value($_POST, $request_data_key, array())
        );

        return $this->save($data);
    }

    /**
     * @param $default_values
     */
    public function set_default_values(array $default_values) {
        foreach ($default_values as $field_name => $new_default_value) {
            if (isset($this->fields[$field_name])) {
                $this->fields[$field_name]->set_default_value($new_default_value);
            }
        }
    }

    /**
     * Eval PHP-кода в значение (оставлен для обратной совместимости)
     */
    public function eval_value($value_string) {
        return self::evaluate($value_string);
    }

    /**
     * Eval PHP-кода в значение (статический метод)
     * @param string
     * @return array
     */
    public static function evaluate($value_string) {
        if (!$value_string) { return $value_string; }

        $value_string = preg_replace("/;\s*$/", "", $value_string);
        $ret = "";
        @eval("\$ret = ($value_string);");
        if (strlen($value_string) > 1 && !is_array($ret)) {
            trigger_error("nc_a2f::evaluate - wrong parameter?<pre>" . htmlspecialchars($value_string) . "</pre>", E_USER_WARNING);
        }
        return $ret;
    }

    /**
     * Вывод элементов
     * @param string|bool $header
     * @param string|array|bool $template
     *    Если строка: это шаблон для всех типов полей.
     *    Если массив: ключ — тип поля (например, 'checkbox'; для всех полей по
     *    умолчанию — 'default'), значение — шаблон для данного типа поля.
     *    Если false: используется встроенный шаблон по умолчанию
     * @param string|bool $footer
     * @param string|bool $divider
     * @return string
     */
    public function render($header = "", $template = "", $footer = "", $divider = "") {
        $ret = $this->render_header($header);

        foreach ($this->fields as $field) {
            if ($field->skip_in_form()) {
                continue;
            }

            $field_template = ($field->get_type() == 'divider' && $divider !== false)
                ? $divider
                : $template;

            if (is_array($field_template)) {
                $field_type = $field->get_type();
                if (isset($field_template[$field_type])) {
                    $field_template = $field_template[$field_type];
                }
                else {
                    $field_template = nc_array_value($field_template, 'default', false);
                }
            }

            $ret .= $field->render($field_template);
        }

        $ret .= $this->render_footer($footer);

        return $ret;
    }

    /**
     * @param bool|string $template
     * @return string
     */
    protected function render_header($template = "") {
        if ($template === false) { return ""; }

        if (!$template) {
            $ret = "<div class='ncf_container'>\n";
            if ($this->show_header) {
                $ret .=
                    "<div class='ncf_header_row'>" .
                        "<div class='ncf_header_caption'>" . CONTROL_CLASS_CUSTOM_SETTINGS_PARAMETER . "</div>" .
                        ($this->show_default_values
                            ? "<div class='ncf_header_default'>" . CONTROL_CLASS_CUSTOM_SETTINGS_DEFAULT . "</div>"
                            : "").
                        "<div class='ncf_header_value'>" . CONTROL_CLASS_CUSTOM_SETTINGS_VALUE . "</div>" .
                    "</div>\n";
            }
        }
        else {
            $ret = str_replace(
                array("%CAPTION", "%DEFAULT", "%VALUE"),
                array(CONTROL_CLASS_CUSTOM_SETTINGS_PARAMETER, CONTROL_CLASS_CUSTOM_SETTINGS_DEFAULT, CONTROL_CLASS_CUSTOM_SETTINGS_VALUE),
                $template
            );
        }

        return $ret;
    }

    /**
     * @param string|bool $template
     * @return string
     */
    protected function render_footer($template = "") {
        if ($template === false) { return ""; }
        if (!$template) { return "</div>"; }
        else { return $template; }
    }

    /**
     * @param string $header
     * @param string $template
     * @param string $footer
     * @param string $divider
     * @return string
     */
    public function render_settings($header = "", $template = "", $footer = "", $divider = "") {
        $ret = $header;

        foreach ($this->fields as $field) {
            $field_template = $field->get_type() == 'divider' && $divider
                ? $divider
                : ($template ? $template : "");
            $ret .= $field->render_settings($field_template);
        }

        $ret .= $footer;

        return $ret;
    }

    /**
     * Получить строку со значениями (php $values_array)
     * @return string  'f1'=>'value', 'f2=>'value'...
     */
    public function get_values_as_string() {
        $all_values = array();
        foreach ($this->fields as $field_name => $field) {
            $val = $this->fields[$field_name]->get_value(true); // don't use default value
            if (isset($val)) {
                $all_values[$field_name] = $val;
            }
        }

        if (count($all_values)) {
            return '$' . $this->array_name . ' = ' . var_export($all_values, true) . ';';
        }
        return "";
    }

    /**
     * Получить все значения в виде массива
     * @return array
     */
    public function get_values_as_array() {
        $values = array();
        foreach ($this->fields as $field_name => $field) {
            $values[$field_name] = $this->fields[$field_name]->get_value();
        }
        return $values;
    }

    /**
     * @param $ar
     * @return string
     */
    public static function array_to_string($ar) {
        return var_export($ar, true);
    }

    /**
     * принять сообщение об ошибке от поля
     */
    public function set_validation_error($field_name, $error_msg) {
        if (!$error_msg) {
            $error_msg = str_replace("%NAME", $this->fields[$field_name]->get_caption(), NETCAT_MODERATION_MSG_TWO);
        }
        $this->validation_errors[$field_name] = $error_msg;
    }

    /**
     * @return string
     */
    public function get_validation_errors() {
        if (!sizeof($this->validation_errors)) { return ""; }

        $ret = "";
        foreach ($this->validation_errors as $field => $error) {
            $ret .= $this->fields[$field]->get_caption() . ": $error<br>\n";
        }

        return $ret;
    }

    /**
     * @param $field_name
     * @return mixed
     */
    public function get_field_error($field_name) {
        return nc_array_value($this->validation_errors, $field_name);
    }

    /**
     * были ли ошибки при заполнении формы?
     * @return boolean
     */
    public function has_errors() {
        return (sizeof($this->validation_errors) ? true : false);
    }

    /**
     * @return string
     */
    public function get_array_name() {
        return $this->array_name;
    }

    /**
     * @param $values
     * @return bool
     */
    public function validate($values) {
        if (!is_array($values)) {
            $values = self::evaluate($values);
        }

        foreach ($this->fields as $name => $f) {
            if (isset($values[$name]) && !$f->validate($values[$name])) {
                return false;
            }
        }

        return true;
    }

}