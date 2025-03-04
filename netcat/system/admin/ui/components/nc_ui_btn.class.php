<?php


class nc_ui_btn extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    public function render() {
        $attr = $this->render_attr();
        $text = $this->render_content();
        return "<a href='{$this->href}'{$attr}>{$text}</a>";
    }

    //--------------------------------------------------------------------------

    public static function get($href = NULL, $text = NULL)
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        self::$obj->class_name('nc-btn');
        self::$obj->href($href);
        self::$obj->text($text);
        return self::$obj;
    }

    //--------------------------------------------------------------------------

}