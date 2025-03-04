<?php class_exists('nc_system') OR die('Unable to load file');


class nc_form implements ArrayAccess {

    //-------------------------------------------------------------------------

    protected $fields = array();

    //-------------------------------------------------------------------------

    /**
     * Создает и возвращает объект поля (nc_form_field)
     * @param string $name     Имя поля
     * @param string $type     Тип поля
     * @param array  $settings Прочие параметры поля
     *
     * @return nc_form_field|object
     */
    public static function make_field($name, $type, $settings = array()) {
        static $fields_path  = null;
        static $loaded_class = array();

        if (empty($loaded_class[$type])) {
            if (is_null($fields_path)) {
                $fields_path = dirname(__FILE__) . '/fields/';
            }

            // Ищем файл класса
            $field_class_name = 'nc_form_field_' . $type;
            $field_class_file = $fields_path . $field_class_name . '.class.php';
            if ( ! file_exists($field_class_file)) {
                $field_class_file = $fields_path . $type . '/' . $field_class_name . '.class.php';
                if ( ! file_exists($field_class_file)) {
                    $field_class_name = 'nc_form_field';
                    $field_class_file = false;
                }
            }

            if ($field_class_file) {
                require_once $field_class_file;
            }

            $loaded_class[$type] = $field_class_name;
        }

        $class_name = $loaded_class[$type];

        $settings['type'] = $type;
        $settings['name'] = $name;

        return new $class_name($settings);
    }

    //-------------------------------------------------------------------------

    public function __construct($fields = array()) {

        if ($fields) {
            $this->fields = $fields;
        }

        foreach ($this->fields as $name => $settings) {
            $type = $settings['type'];
            if ($type) {
                $this->set_field($name, $type, $settings);
            } else {
                unset($this->fields[$name]);
            }
        }

    }

    //-------------------------------------------------------------------------

    /**
     * Возвращает массив объектов полей
     * @return array
     */
    public function get_fields() {
        return (array) $this->fields;
    }

    //-------------------------------------------------------------------------

    /**
     * Возвращает объект поля по его имени
     * @param  string $name Имя поля
     * @return nc_form_field|object
     */
    public function get_field($name) {
        return isset($this->fields[$name]) ? $this->fields[$name] : null;
    }

    //-------------------------------------------------------------------------

    /**
     * Установка (добавление) поля в форму
     * @param string $name     Имя поля
     * @param string $type     Тип поля
     * @param array  $settings Прочие параметры поля
     */
    public function set_field($name, $type, $settings = array()) {
        $this->fields[$name] = self::make_field($name, $type, $settings);
    }

    //-------------------------------------------------------------------------

    /**
     * Устанавливает значений для элементов форм
     * @param array $values
     */
    public function set_values($values) {
        foreach ($values as $name => $value) {
            if (!empty($this->fields[$name])) {
                $this->fields[$name]->set_value($values[$name]);
            }
        }
    }

    /**************************************************************************
        Методы ArrayAccess
    **************************************************************************/

    public function offsetExists($offset) {
        return isset($this->fields[$offset]);
    }

    //-------------------------------------------------------------------------

    public function offsetGet($offset) {
        return $this->fields[$offset];
    }

    //-------------------------------------------------------------------------

    public function offsetSet($offset, $value) {
        $set_value = false;

        if (is_array($value)) {
            if (isset($value['type']) && isset($value['name'])) {
                $set_value = self::make_field($value['name'], $value['type'], $value);
            }
        } elseif (is_object($value)) {
            if (is_a($value, 'nc_form_field')) {
                $set_value = $value;
            }
        }

        if ($set_value) {
            $this->fields[$offset] = $set_value;
        }
    }

    //-------------------------------------------------------------------------

    public function offsetUnset($offset) {
        unset($this->fields[$offset]);
    }

    /**************************************************************************
        Методы для отображения формы
    **************************************************************************/

    public function __toString() {
        return $this->render();
    }

    //-------------------------------------------------------------------------

    /**
     * Генерирует HTML-код формы
     * @return string
     */
    public function render() {
        // return $this->render_header()
        //     . $this->render_fields()
        //     . $this->render_footer();
        return $this->render_fields();
    }

    //-------------------------------------------------------------------------

    /**
     * Генерирует HTML-код всех полей формы
     * @return string
     */
    public function render_fields() {
        $html = '';

        foreach ($this->fields as $field) {
            $html .= $field->render();
        }

        return $html;
    }

    //-------------------------------------------------------------------------

}