<?php


class nc_ui_icon extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    public function render() {
        $attr = $this->render_attr();
        return "<i{$attr}></i>";
    }

    //--------------------------------------------------------------------------

    public static function get($icon = NULL)
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        self::$obj->small();
        if ($icon) self::$obj->class_name('nc--' . $icon);
        return self::$obj;
    }

    //--------------------------------------------------------------------------

    public function large($add = true)
    {
        return $this->class_name('nc-icon-l', $add, 'icon_class');
    }

    //--------------------------------------------------------------------------

    public function xlarge($add = true)
    {
        return $this->class_name('nc-icon-x', $add, 'icon_class');
    }

    //--------------------------------------------------------------------------

    public function small($add = true)
    {
        return $this->class_name('nc-icon', $add, 'icon_class');
    }

    //--------------------------------------------------------------------------

}