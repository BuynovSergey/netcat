<?php


class nc_ui_alert extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    public function render() {
        $attr = $this->render_attr();
        $text = $this->render_content();

        return "<div{$attr}>{$text}</div>";
    }

    //--------------------------------------------------------------------------

    public static function get($text = NULL)
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        self::$obj->class_name('nc-alert');
        self::$obj->text($text);
        return self::$obj;
    }

    //--------------------------------------------------------------------------

    public function error($text = null) {
        $this->text($text);
        $this->red();
        $this->icon_large('status-error');
        return $this;
    }

    //--------------------------------------------------------------------------

    public function info($text = null) {
        $this->text($text);
        $this->blue();
        $this->icon_large('status-info');
        return $this;
    }

    //--------------------------------------------------------------------------

    public function success($text = null) {
        $this->text($text);
        $this->green();
        $this->icon_large('status-success');
        return $this;
    }

    //--------------------------------------------------------------------------

    public function warning($text = null) {
        $this->text($text);
        $this->yellow();
        $this->icon_large('status-warning');
        return $this;
    }

    //--------------------------------------------------------------------------

}