<?php



abstract class nc_ui_common implements arrayaccess {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    protected $type            = '';
    protected $text            = '';
    protected $hidden_text     = '';
    protected $href            = '';
    protected $icon            = '';
    protected $content_wrapper = array();
    protected $class_name      = array();
    protected $items           = array();
    protected $attr            = array();

    //--------------------------------------------------------------------------

    protected function __construct() {}
    protected function __clone() {}
    public function __wakeup() {}

    //--------------------------------------------------------------------------

    public function __toString()
    {
        return $this->render();
    }

    abstract public function render();

    ############################################################################
    ## ArrayAccess methods:
    ############################################################################

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->items[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    ############################################################################
    ## ...
    ############################################################################

    /**
     * Сбрасывает все установленные ранее свойства
     * @return object $this
     */
    public function reset() {
        $this->content_wrapper = array();
        $this->class_name      = array();
        $this->icon            = '';
        $this->text            = '';
        $this->hidden_text     = '';
        $this->href            = '';
        $this->items           = array();
        $this->attr            = array();

        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Тэг-обертка для текста
     * @param  string $tag  Тэг ("span")
     * @param  array  $attr Атрибуты
     * @return object $this
     */
    public function text_wrapper($tag = 'span', $attr = array()) {
        $this->content_wrapper[$tag] = $attr;
        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Установка атрибутов
     * @param  string $key Название атрибута
     * @param  string $val Значение атрибута
     * @return object $this
     */
    public function attr($key, $val) {
        if ($key == 'href') {
            return $this->href($val);
        }
        elseif ($key == 'class') {
            return $this->class_name($val);
        }
        $this->attr[$key] = $val;
        return $this;
    }

    //--------------------------------------------------------------------------

    public function get_attr($key) {
        return isset($this->attr[$key]) ? $this->attr[$key] : null;
    }

    //--------------------------------------------------------------------------

    public function remove_attr($key) {
        if ($key == 'href') {
            $this->href = '';
        }
        elseif ($key == 'class') {
            $this->class_name = array();
        }
        else {
            unset($this->attr[$key]);
        }

        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Установка атрибута title=""
     *
     * @param  string $title Значение тайтла
     * @param bool $add_hidden_text
     * @return object $this
     */
    public function title($title, $add_hidden_text = false) {
        if ($add_hidden_text) {
            $this->htext($title);
        }
        return $this->attr('title', $title);
    }

    //--------------------------------------------------------------------------

    /**
     * Установка атрибута href=""
     * @param  string $href URL ссылки
     * @return object $this
     */
    public function href($href) {
        if ($href) $this->href = $href;
        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Подготовка атрибутов для создания ссылки, создающей POST-запрос
     * @param array $post_vars    Параметры для POST-запроса
     * @return $this
     */
    public function post_vars(array $post_vars) {
        return $this->href('#')
                    ->attr('data-submit', 1)
                    ->attr('data-post', nc_array_json($post_vars));
    }

    //--------------------------------------------------------------------------

    /**
     * Содержание тега
     * @param  string $text Содержание тега
     * @return object $this
     */
    public function text($text = null) {
        if ( ! is_null($text)) $this->text = $text;
        return $this;
    }

    //--------------------------------------------------------------------------

    public function htext($text) {
        $this->hidden_text = "<span class='nc--hide-text'>{$text}</span>";
        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Установка/сброс класса-модификатора (nc--*)
     * @param  string  $modificator название модификатора
     * @param  boolean $add         Установка/Сброс
     * @param  string  $key         Тип модификатора
     * @return object $this
     */
    public function modificator($modificator, $add = true, $key = null) {
        return $this->class_name('nc--' . $modificator, $add, $key);
    }

    //--------------------------------------------------------------------------

    public function id($id) {
        return $this->attr('id', $id);
    }

    //--------------------------------------------------------------------------

    public function click($action) {
        return $this->attr('onclick', $action);
    }

    //--------------------------------------------------------------------------

    public function style($style) {
        return $this->attr('style', $style);
    }

    //--------------------------------------------------------------------------

    /**
     * Добавление/Удаление класса
     * @param  string  $class_name Имя класса
     * @param  boolean $add        Добавление/Удаление
     * @param  string  $key        Тип класса
     * @return object $this
     */
    public function class_name($class_name, $add = true, $key = null) {
        if (!$key) $key = $class_name;
        if ($add) {
            $this->class_name[$key] = $class_name;
        } else {
            unset($this->class_name[$key]);
        }
        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Добавляет иконку перед текстом
     * @param  string $icon название иконки
     * @return object $this
     */
    public function icon($icon) {
        $this->icon = strval(nc_ui_icon::get($icon));
        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Добавляет большую иконку перед текстом
     * @param  string $icon название иконки
     * @return object $this
     */
    public function icon_large($icon) {

        $this->icon = strval(nc_ui_icon::get($icon)->large());
        return $this;
    }

    ############################################################################
    ## Модификаторы
    ############################################################################

    //--------------------------------------------------------------------------
    // text
    //--------------------------------------------------------------------------

    public function text_center() {
        return $this->class_name('nc-text-center', true, 'text-align');
    }
    public function text_left() {
        return $this->class_name('nc-text-left', true, 'text-align');
    }
    public function text_right() {
        return $this->class_name('nc-text-right', true, 'text-align');
    }
    public function text_blue() {
        return $this->class_name('nc-text-blue', true, 'text-color');
    }
    public function text_red() {
        return $this->class_name('nc-text-red', true, 'text-color');
    }
    public function text_green() {
        return $this->class_name('nc-text-green', true, 'text-color');
    }
    public function text_yellow() {
        return $this->class_name('nc-text-yellow', true, 'text-color');
    }
    public function text_orange() {
        return $this->class_name('nc-text-orange', true, 'text-color');
    }
    public function text_purple() {
        return $this->class_name('nc-text-purple', true, 'text-color');
    }
    public function text_cyan() {
        return $this->class_name('nc-text-cyan', true, 'text-color');
    }
    public function text_olive() {
        return $this->class_name('nc-text-olive', true, 'text-color');
    }
    public function text_white() {
        return $this->class_name('nc-text-white', true, 'text-color');
    }
    public function text_lighten() {
        return $this->class_name('nc-text-lighten', true, 'text-color');
    }
    public function text_light() {
        return $this->class_name('nc-text-light', true, 'text-color');
    }
    public function text_grey() {
        return $this->class_name('nc-text-grey', true, 'text-color');
    }
    public function text_dark() {
        return $this->class_name('nc-text-dark', true, 'text-color');
    }
    public function text_darken() {
        return $this->class_name('nc-text-darken', true, 'text-color');
    }
    public function text_black() {
        return $this->class_name('nc-text-black', true, 'text-color');
    }

    //--------------------------------------------------------------------------
    // bg
    //--------------------------------------------------------------------------

    public function bg_blue() {
        return $this->class_name('nc-bg-blue', true, 'bg');
    }
    public function bg_red() {
        return $this->class_name('nc-bg-red', true, 'bg');
    }
    public function bg_green() {
        return $this->class_name('nc-bg-green', true, 'bg');
    }
    public function bg_yellow() {
        return $this->class_name('nc-bg-yellow', true, 'bg');
    }
    public function bg_white() {
        return $this->class_name('nc-bg-white', true, 'bg');
    }
    public function bg_lighten() {
        return $this->class_name('nc-bg-lighten', true, 'bg');
    }
    public function bg_light() {
        return $this->class_name('nc-bg-light', true, 'bg');
    }
    public function bg_grey() {
        return $this->class_name('nc-bg-grey', true, 'bg');
    }
    public function bg_dark() {
        return $this->class_name('nc-bg-dark', true, 'bg');
    }
    public function bg_darken() {
        return $this->class_name('nc-bg-darken', true, 'bg');
    }
    public function bg_black() {
        return $this->class_name('nc-bg-black', true, 'bg');
    }

    //--------------------------------------------------------------------------
    // state
    //--------------------------------------------------------------------------

    public function clicked() {
        return $this->modificator('clicked');
    }
    public function active($active = true) {
        return $this->modificator('active', $active, 'active');
    }
    public function disabled($disabled = true) {
        return $this->modificator('disabled', $disabled);
    }
    public function dropdown() {
        return $this->modificator('dropdown');
    }
    public function simple() {
        return $this->modificator('simple');
    }
    public function divider($divider = true) {
        return $this->class_name('nc-divider', $divider);
    }

    //--------------------------------------------------------------------------
    // style
    //--------------------------------------------------------------------------

    public function rounded() {
        return $this->modificator('rounded');
    }
    public function bordered() {
        return $this->modificator('bordered');
    }
    public function striped() {
        return $this->modificator('striped');
    }
    public function hovered() {
        return $this->modificator('hovered');
    }
    public function alt() {
        return $this->modificator('alt');
    }

    //--------------------------------------------------------------------------
    // size modificators
    //--------------------------------------------------------------------------

    public function mini() {
        return $this->modificator('mini', true, 'size');
    }
    public function small() {
        return $this->modificator('small', true, 'size');
    }
    public function medium() {
        return $this->modificator('medium', true, 'size');
    }
    public function large() {
        return $this->modificator('large', true, 'size');
    }
    public function xlarge() {
        return $this->modificator('xlarge', true, 'size');
    }

    //--------------------------------------------------------------------------
    // position & display
    //--------------------------------------------------------------------------

    public function left() {
        return $this->modificator('left', true, 'float');
    }
    public function right() {
        return $this->modificator('right', true, 'float');
    }
    public function hide() {
        return $this->modificator('hide', true, 'display');
    }
    public function show() {
        return $this->modificator('show', true, 'display');
    }
    public function fixed() {
        return $this->modificator('fixed', true, 'position');
    }
    public function clearfix() {
        return $this->modificator('clearfix');
    }
    public function blocked() {
        return $this->modificator('blocked');
    }
    public function wide() {
        return $this->modificator('wide');
    }
    public function compact() {
        return $this->modificator('compact');
    }
    public function vertical() {
        return $this->modificator('vertical');
    }
    public function horizontal() {
        return $this->modificator('horizontal');
    }


    //--------------------------------------------------------------------------
    // color modificators
    //--------------------------------------------------------------------------

    public function blue($set = true) {
        return $this->modificator('blue', $set, 'color');
    }
    public function red($set = true) {
        return $this->modificator('red', $set, 'color');
    }
    public function green($set = true) {
        return $this->modificator('green', $set, 'color');
    }
    public function yellow($set = true) {
        return $this->modificator('yellow', $set, 'color');
    }
    public function purple($set = true) {
        return $this->modificator('purple', $set, 'color');
    }
    public function cyan($set = true) {
        return $this->modificator('cyan', $set, 'color');
    }
    public function olive($set = true) {
        return $this->modificator('olive', $set, 'color');
    }
    public function orange($set = true) {
        return $this->modificator('orange', $set, 'color');
    }
    public function white($set = true) {
        return $this->modificator('white', $set, 'color');
    }
    public function lighten($set = true) {
        return $this->modificator('lighten', $set, 'color');
    }
    public function light($set = true) {
        return $this->modificator('light', $set, 'color');
    }
    public function grey($set = true) {
        return $this->modificator('grey', $set, 'color');
    }
    public function dark($set = true) {
        return $this->modificator('dark', $set, 'color');
    }
    public function darken($set = true) {
        return $this->modificator('darken', $set, 'color');
    }
    public function black($set = true) {
        return $this->modificator('black', $set, 'color');
    }

    //--------------------------------------------------------------------------

    public function padding_0() {
        return $this->class_name('nc-padding-0', true, 'padding');
    }
    public function padding_5() {
        return $this->class_name('nc-padding-5', true, 'padding');
    }
    public function padding_10() {
        return $this->class_name('nc-padding-10', true, 'padding');
    }
    public function padding_15() {
        return $this->class_name('nc-padding-15', true, 'padding');
    }
    public function padding_20() {
        return $this->class_name('nc-padding-20', true, 'padding');
    }
    public function padding_25() {
        return $this->class_name('nc-padding-25', true, 'padding');
    }

    //--------------------------------------------------------------------------

    public function margin_0() {
        return $this->class_name('nc-margin-0', true, 'margin');
    }
    public function margin_5() {
        return $this->class_name('nc-margin-5', true, 'margin');
    }
    public function margin_10() {
        return $this->class_name('nc-margin-10', true, 'margin');
    }
    public function margin_15() {
        return $this->class_name('nc-margin-15', true, 'margin');
    }
    public function margin_20() {
        return $this->class_name('nc-margin-20', true, 'margin');
    }
    public function margin_25() {
        return $this->class_name('nc-margin-25', true, 'margin');
    }

    ############################################################################
    ## Вспомогательные функции
    ############################################################################

    protected function render_content() {
        $content = $this->hidden_text . $this->icon . ($this->icon && $this->text ? ' ' : '') . $this->text;
        $attr = '';
        foreach ($this->content_wrapper as $tag => $tag_attr) {
            foreach ((array)$tag_attr as $key => $val) {
                $attr .= " {$key}='{$val}'";
            }
            $content = "<{$tag}{$attr}>{$content}</{$tag}>";
        }

        // если задан href и текущий элемент не является ссылкой, создаем ее
        if ($this->href && $this->type && $this->type !== 'a') {
            $attr = '';
            foreach (array('onclick', 'title') as $key) {
                if ($row = $this->get_attr($key)) {
                    $attr .= ($attr ? ' ' : '') . "{$key}='{$row}'";
                    $this->remove_attr($key);
                }
            }

            $content = "<a href='{$this->href}'{$attr}>{$content}</a>";
        }

        if ($this->items) {
            $content .= implode('', $this->items);
        }

        return $content;
    }

    //--------------------------------------------------------------------------

    protected function render_attr() {
        $attr = '';

        if ($this->class_name) {
            $this->attr['class'] = implode(' ', $this->class_name);
        }

        if ($this->attr) {
            foreach ($this->attr as $key => $val) {
                $attr .= " {$key}='" . htmlspecialchars($val, ENT_QUOTES) . "'";
            }
        }

        return $attr;
    }

    //--------------------------------------------------------------------------

}