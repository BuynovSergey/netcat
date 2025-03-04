<?php


class nc_ui_tabs extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    public function render() {
        $attr = $this->render_attr();
        return "<ul{$attr}>\n\t" . implode("\n\t", $this->items) . "\n</ul>";
    }

    //--------------------------------------------------------------------------

    public static function get()
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        self::$obj->class_name('nc-tabs');
        return self::$obj;
    }

    //--------------------------------------------------------------------------

    public function &add_btn($href = NULL, $text = NULL)
    {
        $item = nc_ui_html::get('li');
        $item->href($href)->text($text);
        $this->items[] =& $item;
        return $item;
    }

    //--------------------------------------------------------------------------

}