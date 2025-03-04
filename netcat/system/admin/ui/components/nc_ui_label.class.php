<?php


class nc_ui_label extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    public function render() {
        $attr = $this->render_attr();
        $text = $this->render_content();
        if ($this->href)
        {
            return "<a href='{$this->href}'{$attr}>{$text}</a>";
        }
        return "<span{$attr}>{$text}</span>";
    }

    //--------------------------------------------------------------------------

    public static function get($text = NULL)
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        self::$obj->class_name('nc-label');
        self::$obj->text($text);
        return self::$obj;
    }

    //--------------------------------------------------------------------------

}