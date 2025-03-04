<?php class_exists('nc_system') OR die('Unable to load file');


/**
 * Базовый класс полей nc_form
 */
class nc_form_field {

    /**
     * Текущее значение поля
     * @var string|bool
     */
    protected $value            = null;

    /**
     * Ошибки валидации поля
     * @var string
     */
    protected $validation_error = null;

    /**
     * Текущие настройки поля
     * @var array
     */
    protected $settings = array();

    /**
     * Параметры по умолчанию для конкретного типа поля (для дочерних классов)
     * @var array
     */
    protected $default_settings = array();

    /**
     * Общие параметры поля
     * @var array
     */
    protected $global_settings = array(
        'type'              => null, // Тип поля
        'name'              => null, // Имя поля
        'caption'           => null, // Описание поля (label)
        'default_value'     => null, // Значение по умолчанию
        'validation_regexp' => null, // Регулярное выражение для проверки
        'validation_error'  => null, // Сообщение об ошибке
        'rules'             => null, // Список правил для проверки
        'required'          => false, // Поле обязательно для заполнения
        'attr'              => array(),
        'row_attr'          => array(),
    );

    //-------------------------------------------------------------------------

    public function __construct($settings = array()) {

        $this->settings = array_merge($this->global_settings, $this->default_settings);

        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }

        if (!$this->get_type()) {
            $this->set('type', str_replace('nc_form_field', '', get_class($this)));
        }

        $this->init();
    }

    //-------------------------------------------------------------------------

    /**
     * Метод инициализации поля (для переопределения)
     */
    protected function init() {}

    //-------------------------------------------------------------------------

    /**
     * Установка параметра настроек поля
     * @param string $key  Название параметра
     * @param mixed $value Значение
     * @return nc_form_field
     */
    public function set($key, $value) {
        switch ($key) {
            case 'value':
                $this->set_value($value);
                break;
        }

        $this->settings[$key] = $value;

        return $this;
    }

    //-------------------------------------------------------------------------

    /**
     * Получение параметра настроек
     * @param  string $key Название параметра
     * @return mixin
     */
    public function get($key) {
        return isset($this->settings[$key]) ? $this->settings[$key] : null;
    }

    //-------------------------------------------------------------------------

    /**
     * Установка текущего значения поля
     * @param mixed $value Значение поля
     */
    public function set_value($value) {
        $this->value = $value;

        return $this;
    }

    //-------------------------------------------------------------------------

    /**
     * Получение текущего значения поля
     * @param  boolean $use_default Вернуть значение по умолчанию (если доступно)
     * @return mixed
     */
    public function get_value($use_default = true) {
        if (is_null($this->value) && $use_default) {
            return $this->get_default_value();
        }

        return $this->value;
    }

    //-------------------------------------------------------------------------

    /**
     * Значение поля по умолчанию
     * @return mixed
     */
    public function get_default_value() {
        return $this->get('default_value');
    }

    //-------------------------------------------------------------------------

    /**
     * Установка значения по умолчанию
     * @param mixed $value
     */
    public function set_default_value($value) {
        return $this->set('default_value', $value);
    }

    //-------------------------------------------------------------------------

    /**
     * Возвращает TRUE если для поля задано значение по умолчанию
     * @return boolean
     */
    public function has_default() {
        return $this->get('default_value') !== null;
    }

    //-------------------------------------------------------------------------

    /**
     * Получение имени поля
     * @return string
     */
    function get_name() {
        return $this->get('name');
    }

    //-------------------------------------------------------------------------

    /**
     * Установка имени поля
     * @param string $name Имя поля
     */
    function set_name($name) {
        return $this->set('name', $name);
    }

    //-------------------------------------------------------------------------

    /**
     * Получение названия (описания) поля
     * @return string
     */
    function get_caption() {
        return $this->get('caption');
    }

    //-------------------------------------------------------------------------

    /**
     * Установливает название (описание) поля
     * @param string $caption Название (описание) поля
     */
    function set_caption($caption) {
        return $this->set('caption', $caption);
    }

    //-------------------------------------------------------------------------

    /**
     * Возвращает тип поля
     * @return string
     */
    function get_type() {
        return $this->get('type');
    }

    //-------------------------------------------------------------------------

    /**
     * Устанавливает значение(я) html-аттрибута(ов) для поля.
     * @param array|string $attr Название или массив аттрибута(ов)
     * @param mixed $value Значение аттрибута
     */
    function set_attr($attr, $value = null) {
        if (!is_array($attr)) {
            $attr = array($attr => $value);
        }

        $this->set('attr', $this->get_attr($attr));

        return $this;
    }

    //-------------------------------------------------------------------------

    /**
     * Возвразает массив html-аттрибутов для поля.
     * @param  array  $attr Массив дополнительных аттрибутов
     * @return array
     */
    function get_attr($attr = array()) {
        if ($attr) {
            return array_merge($this->get('attr'), $attr);
        }
        return (array) $this->get('attr');
    }

    //-------------------------------------------------------------------------

    /**
     * Возвращает TRUE если поля является обязательным для заполнения
     * @return boolean
     */
    public function is_required() {
        return (bool) $this->get('required');
    }

    //-------------------------------------------------------------------------

    /**
     * Установка соообщения об ошибке
     * @param string $message
     */
    public function set_validation_error($message) {
        $this->validate_error = $message;

        return $this;
    }

    //-------------------------------------------------------------------------

    /**
     * Получение сообщения об ошибке
     * @return string
     */
    public function get_validation_error() {
        return $this->validate_error;
    }

    //-------------------------------------------------------------------------

    /**
     * Возвращает TRUE если поле имеет ошибку валидации
     * @return boolean          [description]
     */
    public function has_error() {
        return (bool) $this->get_validation_error();
    }

    //-------------------------------------------------------------------------

    /**
     * Проверка значения поля
     * @param  mixed $value Значени поля
     * @return bool
     */
    public function validate($value = null) {
        if (is_null($value)) {
            $value = $this->get_value(false);
        }

        $regexp = $this->get('validate_regexp');

        if ($value && $regexp && !preg_match($regexp, $value)) {
            $this->set_validation_error($this->get('validate_error'));

            return false;
        }

        return true;
    }

    /**************************************************************************
        Метода для отображения поля
    **************************************************************************/

    //-------------------------------------------------------------------------

    public function __toString() {
        return $this->render();
    }

    //-------------------------------------------------------------------------

    public function render() {
        return "<div class='nc-form-row'>"
            . "<div class='nc-form-label'>" . $this->render_label() . "</div>"
            . "<div class='nc-form-field'>" . $this->render_field() . "</div>"
            . "</div>";
    }

    //-------------------------------------------------------------------------

    public function render_field($attr = array()) {
        $attr = $this->get_attr($attr);

        $attr['name'] = $this->get_name();

        switch ($this->get_type()) {
            case 'password':
                $attr['type'] = 'password';
                return $this->make_html_elem('input', $attr, $this->get_value(false));

            default:
                $attr['type']  = $this->get_type();
                $attr['value'] = $this->get_value(true);
                return $this->make_html_elem('input', $attr, true);
        }
    }

    //-------------------------------------------------------------------------

    public function render_label() {
        $label = $this->get_caption() . ($this->is_required() ? '*' : '') . ':';
        return $this->make_html_elem('label', array('for' => $this->get_name()), $label);
    }

    //-------------------------------------------------------------------------

    // public function render_prefix() {
    //     $attr = (array) $this->get('row_attr');

    //     if (empty($attr['class'])) {
    //         $attr['class'] = 'nc-form-row';
    //     }
    //     return $this->make_html_elem('div', $attr, false);
    // }

    // //-------------------------------------------------------------------------

    // public function render_suffix() {
    //     return '</div>';
    // }

    /**************************************************************************
        PROTECTED PART
    **************************************************************************/

    protected function make_html_elem($tag_name, $attr_array, $close = null) {
        $attr = '';
        foreach ($attr_array as $name => $value) {
            $attr .= ' ' . $name . "='" . htmlspecialchars($value, ENT_QUOTES) . "'";
        }

        if ($close === true) {
            return "<{$tag_name}{$attr} />";
        }
        if ($close === false) {
            return "<{$tag_name}{$attr}>";
        }

        $content = $close;

        return "<{$tag_name}{$attr}>{$content}</{$tag_name}>";
    }

    //-------------------------------------------------------------------------

}