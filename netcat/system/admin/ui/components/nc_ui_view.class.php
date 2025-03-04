<?php


class nc_ui_view {

    //--------------------------------------------------------------------------

    protected $path      = null;
    protected $view      = null;
    protected $view_path = null;
    protected $view_file = null;
    protected $data      = array();

    //--------------------------------------------------------------------------

    public function __construct($view, $data = array()) {
        // global $NETCAT_FOLDER;
        global $nc_core;

        $this->path      = '';
        $this->view      = $view;
        $this->view_path = dirname($view) . DIRECTORY_SEPARATOR;

        if ($data) $this->data = $data;
        $this->data += $GLOBALS;

        $this->data['nc_core'] =& $nc_core;
        $this->data['ui']      =& $nc_core->ui;

        return $this;
    }

    //--------------------------------------------------------------------------

    public function view($view) {
        $this->view = $this->view_path . $view;
        return $this;
    }

    //--------------------------------------------------------------------------

    /**
     * Рендренг шаблона
     */
    public function make() {
        $this->view_file = $this->path . $this->view;

        if (file_exists($this->view_file . '.view.php')) {
            $this->view_file .= '.view.php';
        }
        elseif (file_exists($this->view_file . '.php')) {
            $this->view_file .= '.php';
        }

        if ($this->data) {
            extract($this->data);
        }

        ob_start();

        include $this->view_file;

        return ob_get_clean();
    }

    //--------------------------------------------------------------------------

    public function __toString() {
        try {
            return $this->make();
        }
        catch (Exception $e) {
            return strval(nc_core::get_object()->ui->alert->error($e->getMessage()));
        }
    }

    //-------------------------------------------------------------------------

    public function __set($name, $value) {
        $this->with($name, $value);
    }

    //-------------------------------------------------------------------------

    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    //--------------------------------------------------------------------------

    /**
     * Присвоение переменной шаблона
     * @param  string $key  Название переменной
     * @param  mixed $value Значение переменой
     * @return $this
     */
    public function with($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }

    //--------------------------------------------------------------------------

    public function include_view($view, $data = array()) {
        $included_view = new self($view, $data ?: $this->data);
        $included_view->view_path = $this->view_path;
        $included_view->view($view);
        return $included_view;
    }

}