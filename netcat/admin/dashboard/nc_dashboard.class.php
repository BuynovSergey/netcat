<?php


class nc_dashboard {

	protected static $instance;

    //--------------------------------------------------------------------------

    protected $allowed_widgets = array();

	//--------------------------------------------------------------------------

    private function __construct() {

    }

    //--------------------------------------------------------------------------

    private function __clone() {}
    public function __wakeup() {}

    //--------------------------------------------------------------------------

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    //--------------------------------------------------------------------------

    /**
     * Регистрация виджета в системе
     * @param  [string] $keyword Уникальное название виджета (a-z0-9_)
     * @param  [array]  $opt     Параметры виджета
     * @return [null]
     */
    public function register($keyword, $opt) {
    	$this->allowed_widgets[$keyword] = $opt;
    }

    //--------------------------------------------------------------------------

    /**
     * Генерирет JSON массив всех зарегистрированных виджетов
     * @return [string] JSON
     */
    public function allowed_widgets() {
        // if ($json) {
        //     return json_safe_encode($this->allowed_widgets);
        // }

        return $this->allowed_widgets;
    }

    //--------------------------------------------------------------------------

    public function user_widgets() {
        global $AUTH_USER_ID, $nc_core,$catalogue_id,$catalogue;

        $settings_ket    = 'user_widgets_' . $AUTH_USER_ID;
        $settings_module = 'system';

        $data = $nc_core->get_settings($settings_ket, $settings_module);

        if ( ! $data) {
            $data = $this->default_widgets();
        }
        else {
            $data = json_decode($data, true);
        }

        $allowed_widgets = $this->allowed_widgets();

        $result = array();
        foreach ($data as $k => $row) {
            if ( isset($allowed_widgets[$row['type']]) ) {
                $result[] = $row;
            }
        }
        return $result;
    }

    //--------------------------------------------------------------------------

    public function save_user_widgets($data = null) {
        global $AUTH_USER_ID, $nc_core,$catalogue_id,$catalogue;
        $settings_ket    = 'user_widgets_' . $AUTH_USER_ID;
        $settings_module = 'system';
        if ($data) {
            $nc_core->set_settings($settings_ket, $data, $settings_module);
        }
        elseif ($data === false){
            $nc_core->drop_settings($settings_ket, $settings_module);
        }
    }

    //--------------------------------------------------------------------------

    public function default_widgets() {
        return array(
            array('type' => 'sys_favorites', 'row' => 1, 'col' => 1, 'size' => array(3, 2)),
            array('type' => 'sys_netcat', 'row' => 1, 'col' => 4),
            array('type' => 'sys_tools_trash', 'row' => 1, 'col' => 5),
            array('type' => 'sys_user', 'row' => 1, 'col' => 6),
            array('type' => 'mod_blog', 'row' => 1, 'col' => 7),
            array('type' => 'sys_tools_cron', 'row' => 2, 'col' => 4),
            array('type' => 'mod_comments', 'row' => 2, 'col' => 5),
            array('type' => 'mod_netshop', 'row' => 2, 'col' => 6),
            array('type' => 'mod_search', 'row' => 3, 'col' => 1, 'size' => array(2, 2)),
            array('type' => 'sys_selftest', 'row' => 3, 'col' => 3),
            array('type' => 'mod_auth', 'row' => 3, 'col' => 5),
            array('type' => 'mod_logging', 'row' => 5, 'col' => 1, 'size' => array(5, 1)),
        );
    }

    //--------------------------------------------------------------------------


}
