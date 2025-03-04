<?php


/**
 *
 * @method nc_ui_alert alert($text = null)
 * @method nc_ui_btn btn($href = NULL, $text = NULL)
 * @method nc_ui_controls controls()
 * @method nc_ui_form form($action = null, $method = 'POST', $enctype = null)
 * @method nc_ui_helper helper()
 * @method nc_ui_html html($type = null, $parent = null, $args = array())
 * @method nc_ui_icon icon($icon = NULL)
 * @method nc_ui_label label($text = NULL)
 * @method nc_ui_navbar navbar()
 * @method nc_ui_table table($data = null)
 * @method nc_ui_tabs tabs()
 * @method nc_ui_toolbar toolbar()
 *
 * @property nc_ui_html $html
 * @property nc_ui_helper $helper
 * @property nc_ui_alert $alert
 * @property nc_ui_btn $btn
 * @property nc_ui_label $label
 * @property nc_ui_icon $icon
 * @property nc_ui_toolbar $toolbar
 * @property nc_ui_navbar $navbar
 * @property nc_ui_tabs $tabs
 * @property nc_ui_table $table
 * @property nc_ui_form $form
 * @property nc_ui_view $view
 * @property nc_ui_controller $controller
 * @property nc_ui_controls $controls
 */
class nc_ui {

    //--------------------------------------------------------------------------

    protected static $obj;
    protected $components = array(
        'html',
        'helper',
        'alert',
        'btn',
        'label',
        'icon',
        'toolbar',
        'navbar',
        'tabs',
        'table',
        'form',
        'view',
        'controller',
        'controls',
    );

    //--------------------------------------------------------------------------

    private function __construct() {
        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        $ext = '.class.php';
        require_once $dir . 'nc_ui_common' . $ext;

        foreach ($this->components as $com) {
            require_once $dir . 'components/nc_ui_' . $com . $ext;
        }
    }

    //--------------------------------------------------------------------------

    private function __clone() {}
    public function __wakeup() {}

    //--------------------------------------------------------------------------

    /**
     * [get_instance description]
     * @return nc_ui [description]
     */
    public static function get_instance() {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }

        return self::$obj;
    }

    //--------------------------------------------------------------------------

    public function __get($name) {
        return call_user_func_array(array($this, $name), array());
    }

    //--------------------------------------------------------------------------

    public function __call($name, $args) {
        return call_user_func_array(array('nc_ui_' . $name, 'get'), $args);
    }

    //--------------------------------------------------------------------------

    public function view($view, $data = array()) {
        return new nc_ui_view($view, $data);
    }

    //--------------------------------------------------------------------------

    public function controller($class, $path, $custom_view_path = null) {
        static $loaded = array();

        if (isset($loaded[$class])) {
            return $loaded[$class];
        }

        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!class_exists($class)) {
            $file = $path . $class . '.class.php';
            if (!file_exists($file)) {
                die('Controller not found');
            }
            require_once $file;
        }


        $loaded[$class] = new $class($custom_view_path ? $custom_view_path : $path . 'views');

        if (!is_a($loaded[$class], 'nc_ui_controller')) {
            die('Controller not instance of nc_ui_controller');
        }

        return $loaded[$class];
    }

    //--------------------------------------------------------------------------

}