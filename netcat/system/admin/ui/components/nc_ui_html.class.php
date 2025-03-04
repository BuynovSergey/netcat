<?php


class nc_ui_html extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    protected $extends    = null;
    protected $root       = null;
    protected $parent     = null;
    protected $type       = '';
    protected $text_class = '';

    //--------------------------------------------------------------------------

    public function __toString() {
        if ($this->root && empty($this->root->is_rendered)) {
            $this->root->is_rendered = true;
            $result = $this->root->render();
            $this->root->is_rendered = false;
            return $result;
        }
        return $this->render();
    }

    //--------------------------------------------------------------------------

    public function render() {
        $text = $this->render_content();
        $attr = $this->render_attr();

        switch ($this->type) {
            case 'input': return "<input{$attr} />";
            case 'a': return "<a href='{$this->href}'{$attr}>{$text}</a>";
            default:  return $this->type ? "<{$this->type}{$attr}>{$text}</{$this->type}>" : $text;
        }
    }

    //--------------------------------------------------------------------------

    public static function get($type = null, $parent = null, $args = array()) {
        self::$obj = new self();

        if ( ! is_null($parent)) {
            $parent->items[] = self::$obj;
            self::$obj->parent =& $parent;
        }
        self::$obj->root = $parent && isset($parent->root) ? $parent->root : self::$obj;
        self::$obj->type = $type;
        self::$obj->text( current($args) );

        return self::$obj;
    }

    ############################################################################
    ## ...
    ############################################################################

    public function li($text = null) {
        if ($this->parent && $this->parent->type == 'ul')
            return self::get('li', $this->parent, array($text));
        return self::get('li', $this, array($text));
    }

    //--------------------------------------------------------------------------

    public function __call($name, $arguments) {
        return self::get($name, $this, $arguments);
    }

    ############################################################################
    #
    ############################################################################

    public function off($off = true) {
        if ($off) {
            $this->text_wrapper('span', array('class'=>'nc-text-red'));
        }
        return $this;
    }

    //--------------------------------------------------------------------------

    public function on($on = true) {
        if ($on) {
            $this->text_wrapper('span', array('class'=>'nc-text-green'));
        }
        return $this;
    }

    //--------------------------------------------------------------------------

    public function add_divider() {
        return $this->li()->divider();
    }

    //--------------------------------------------------------------------------

    public function add_btn($href = '', $text = NULL)
    {
        return $this->li($text)->href($href);
    }

    //--------------------------------------------------------------------------

    public function add_text($text = NULL)
    {
        return $this->li($text)->text_wrapper('span');
    }

    //--------------------------------------------------------------------------

    public function submenu() {
        return $this->dropdown()->ul();
    }

    //--------------------------------------------------------------------------
    // HTML FORM:
    //--------------------------------------------------------------------------

    public function name($name) {
        return $this->attr('name', $name);
    }

    public function value($value) {
        return $this->attr('value', $value);
    }

    public function checked($checked) {
        return $checked ? $this->attr('checked', $checked) : $this->remove_attr('checked');
    }

    public function type($type) {
        return $this->attr('type', $type);
    }

    public function placeholder($placeholder) {
        return $this->attr('placeholder', $placeholder);
    }

    public function rows($rows) {
        return $this->attr('rows', $rows);
    }

    //--------------------------------------------------------------------------

    public function input($type, $name = null, $value = null) {
        $input = self::get('input', $this)->type($type);
        if ($name) {
            $input->name($name);
        }
        if ($value !== null) {
            $input->value($value);
        }
        return $input;
    }

    //--------------------------------------------------------------------------

    public function select($name, $options=array(), $selected=null) {
        $div = $this->div()->class_name('nc-select');

        $select = self::get('select', $div)->name($name);
        $text = '';

        foreach ($options as $key => $title) {
            if (is_array($title)) {
                $text .= "<optgroup label='" . htmlspecialchars($key, ENT_QUOTES) . "'>";
                foreach ($title as $k => $t) {
                    $attr = $k == $selected ? " selected='selected'" : '';
                    $text .= "<option value='" . htmlspecialchars($k, ENT_QUOTES) . "'{$attr}>" .
                              htmlspecialchars($t) . "</option>";
                }
                $text .= "</optgroup>";
            }
            else {
                $attr = $key == $selected ? " selected='selected'" : '';
                $text .= "<option value='" . htmlspecialchars($key, ENT_QUOTES) . "'{$attr}>" .
                         htmlspecialchars($title) . "</option>";
            }
        }
        $select->text($text);
        $div->i()->class_name('nc-caret');
        return $select;
    }

    //--------------------------------------------------------------------------

    public function multiple($name, $options=array(), $selected=array()) {
        $select = self::get('select', $this)->name($name)->attr('multiple', 'multiple');
        $text = '';
        foreach ($options as $key => $title) {
            $attr = in_array($key, $selected) ? " selected='selected'" : '';
            $text .= "<option value='" . htmlspecialchars($key, ENT_QUOTES) . "'{$attr}>" .
                     htmlspecialchars($title) . "</option>";
        }
        $select->text($text);
        return $select;
    }

    //--------------------------------------------------------------------------

    public function checkbox($name = null, $checked = false, $label_text = null) {
        if ($label_text) {
            $label = $this->label();
            $input = $label->input('checkbox');
            $label->items[] = ' ' . $label_text;
            // $label->items[] = $input;
        }
        else {
            $input = $this->input('checkbox');
        }
        if ($name) $input->name($name);
        if ($checked) $input->checked(true);
        return $input;
    }

    //--------------------------------------------------------------------------

    public function radiogroup($name, $options, $checked = null) {
        foreach ($options as $key => $title) {
            $label = self::get('label', $this);
            $label->input('radio', $name)->checked($key == $checked);
            $label[] = ' ' . $title;
        }
        return $this;
    }

    //--------------------------------------------------------------------------

    public function string($name = null, $value = null) {
        $input = $this->input('text');
        if ($name) $input->name($name);
        if ($value) $input->value($value);
        // if ($placeholder) $input->placeholder($placeholder);
        return $input;
    }

    //--------------------------------------------------------------------------

    public function file($name = null) {
        $input = $this->input('file');
        if ($name) $input->name($name);
        return $input;
    }

    //--------------------------------------------------------------------------

    public function textarea($name = null, $value = null) {
        $input = self::get('textarea', $this);
        if ($name) $input->name($name);
        if ($value) $input->text(htmlspecialchars($value));
        // if ($placeholder) $input->placeholder($placeholder);
        return $input;
    }

    //--------------------------------------------------------------------------

    public function button($type, $text) {
        $button = self::get('button', $this)->class_name('nc-btn');
        $button->type($type);
        $button->text($text);
        return $button;
    }

    //--------------------------------------------------------------------------

}