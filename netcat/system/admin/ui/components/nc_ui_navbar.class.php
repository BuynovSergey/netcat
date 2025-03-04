<?php

/**
 * @property nc_ui_html quickmenu
 * @property nc_ui_html menu
 * @property nc_ui_html toolbar
 * @property nc_ui_html tray
 */
class nc_ui_navbar extends nc_ui_common {

    protected static $obj;

    public function render() {
        $attr = $this->render_attr();
        $content = '';
        $content .= isset($this->quickmenu) ? $this->quickmenu : null;
        $content .= isset($this->menu) ? $this->menu : null;
        $content .= isset($this->toolbar) ? $this->toolbar : null;
        $content .= isset($this->tray) ? $this->tray : null;
        return "<div{$attr}>{$content}</div>";
    }

    public function reset() {
        parent::reset();
        unset(
            $this->quickmenu,
            $this->menu,
            $this->toolbar,
            $this->tray
        );
    }

    public static function get()
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        self::$obj->class_name('nc-navbar');
        return self::$obj;
    }

    public function menu() {
        if (empty($this->menu)) {
            $this->menu = nc_ui_html::get('ul')->class_name('nc-menu');
        }
        return $this->menu;
    }

    public function tray() {
        if (empty($this->tray)) {
            $this->tray = nc_ui_html::get('ul')->class_name('nc-tray');
        }
        return $this->tray;
    }

    public function toolbar() {
        if (empty($this->toolbar)) {
            $this->toolbar = nc_ui_html::get('ul')->class_name('nc-navbar-toolbar');
        }
        return $this->toolbar;
    }

    public function quickmenu() {
        if (empty($this->quickmenu)) {
            $this->quickmenu = nc_ui_html::get('ul')->class_name('nc-quick-menu');
        }
        return $this->quickmenu;
    }

    public function __get($name) {
        if (method_exists($this, $name)) {
            return $this->$name();
        }
        return $this;
    }

}