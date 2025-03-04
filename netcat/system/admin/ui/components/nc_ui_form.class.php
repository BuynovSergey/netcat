<?php


class nc_ui_form extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    public function render() {
        $attr = $this->render_attr();
        return "<form{$attr}>\n\t" . implode("\n\t", $this->items) . "\n</form>";
    }

    //--------------------------------------------------------------------------

    public static function get($action = null, $method = 'POST', $enctype = null)
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        self::$obj->class_name('nc-form');
        self::$obj->action($action);
        self::$obj->method($method);
        if ($enctype) { self::$obj->attr('enctype', $enctype); }

        return self::$obj;
    }

    //--------------------------------------------------------------------------

    public function action($action = null) {
        if ($action === null) {
            $action = htmlspecialchars_decode($_SERVER['REQUEST_URI']);
        }
        return $this->attr('action', $action);
    }

    //--------------------------------------------------------------------------

    public function method($method) {
        return $this->attr('method', $method);
    }

    //--------------------------------------------------------------------------

    public function multipart() {
        return $this->attr('enctype', 'multipart/form-data');
    }

    //--------------------------------------------------------------------------

    public function add_row($label = null) {
        $row = nc_ui_html::get('div')->class_name('nc-form-row');
        if ($label) $row->label($label);
        $this->items[] = $row;
        return $row;
    }

    //--------------------------------------------------------------------------

    public function actions() {
        $row = nc_ui_html::get('div')->class_name('nc-form-actions');
        $this->items[] = $row;
        return $row;
    }

    //--------------------------------------------------------------------------

    public function add() {
        return nc_ui_html::get('', $this);
    }

    //--------------------------------------------------------------------------

}