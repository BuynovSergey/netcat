<?php


class nc_ui_helper extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    public function render() {
        return "";
    }

    //--------------------------------------------------------------------------

    public static function get()
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        return self::$obj;
    }

    //--------------------------------------------------------------------------

    public function clearfix() {
        return "<div class='nc--clearfix'></div>";
    }

    //--------------------------------------------------------------------------

    public function h1($text) {
        return nc_ui_html::get('h1')->class_name('nc-h1')->text($text);
    }

    //--------------------------------------------------------------------------

    /**
     * Оборачивает $inner_html в <a> с hash-ссылкой
     *
     * @param $hash
     * @param $inner_html
     * @param string $class_name
     * @param string $target
     * @return string
     */
    public function hash_link($hash, $inner_html, $class_name = '', $target = '_top') {
        $href = nc_core('SUB_FOLDER') . nc_core('HTTP_ROOT_PATH') . "admin/" . ($hash[0] == "#" ? "" : "#") . $hash;
        return "<a href='$href' target='$target'" .
               ($class_name ? " class='$class_name'" : "") .
               ">$inner_html</a>";
    }

}