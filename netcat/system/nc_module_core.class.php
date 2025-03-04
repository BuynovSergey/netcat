<?php



class nc_module_core {

    //--------------------------------------------------------------------------

    const LOAD_ON_DEMAND  = 0; // Загрузка при обращении
    const LOAD_ON_STARTUP = 1; // Загрузка при старте

    //--------------------------------------------------------------------------

    protected $module_keyword;
    protected $module_folder;
    protected $submodules = array();

    //--------------------------------------------------------------------------

    protected function __construct()
    {
        if ( ! $this->module_keyword) {
            // cut prefix "nc_"
            $this->module_keyword = strtolower(substr(get_class($this), 3));
        }

        if ( ! $this->module_folder) {
            $this->module_folder = nc_module_folder($this->module_keyword);
        }

        // Загрузка подмодулей при старте
        foreach ($this->submodules as $key => $submodule) {
            if ($submodule === self::LOAD_ON_STARTUP) {
                $this->load_submodule($key);
            }
        }

        $this->init();
    }

    //--------------------------------------------------------------------------

    /**
     * Отложенная загрузка подмодулей
     *
     * @param string $key Название подмодуля
     *
     * @return object
     */
    public function __get($submodule)
    {
        $this->load_submodule($submodule);

        return $this->$submodule;
    }

    //--------------------------------------------------------------------------

    /**
     * Настройки модуля (Модули / Интернет Магазин / Настройки)
     *
     * @param string $setting_key Имя параметра
     *
     * @return object|string
     */
    public function module_vars($setting_key = null)
    {
        static $settings;

        if (is_null($settings)) {

            if ( ! $this->module_keyword) {
                return null;
            }

            $module_vars = nc_modules()->get_vars($this->module_keyword);
            $settings = new stdclass;

            foreach ($module_vars as $key => $value) {
                $key = strtolower($key);
                $settings->$key = $value;
            }
        }

        if ($setting_key) {
            return isset($settings->$setting_key) ? $settings->$setting_key : null;
        }

        return $settings;
    }



    /***************************************************************************
        PRIVATE API:
    ***************************************************************************/

    /**
     * Инициализация модуля (Для переопределения в классе модуля)
     *
     * @return null
     */
    protected function init() {}

    //--------------------------------------------------------------------------

    /**
     * Загружает объект подмодуля в $this->{подмодуль}
     *
     * @param string $key Название объекта (пр.: cart, filter)
     *
     * @return bool
     */
    protected function load_submodule($key)
    {
        if (!isset($this->submodules[$key])) {
            return false;
        }

        $class_name = "nc_{$this->module_keyword}_{$key}";
        $class_file = $class_name . '.class.php';

        require_once $this->module_folder . 'class' . DIRECTORY_SEPARATOR . $class_file;

        $this->$key = call_user_func(array($class_name, 'get_instance'));

        return true;
    }

    //--------------------------------------------------------------------------

    private function __clone() {}
    public function __wakeup() {}

    //--------------------------------------------------------------------------
}