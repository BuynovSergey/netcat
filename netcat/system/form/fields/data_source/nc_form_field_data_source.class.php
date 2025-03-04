<?php class_exists('nc_system') OR die('Unable to load file');



class nc_form_field_data_source extends nc_form_field {

    //-------------------------------------------------------------------------

    protected $default_settings = array(
        // 'mode'                 => 'advanced', // advanced, simple
        'tabs' => array(
            'source'   => 'Источник',
            'filter'   => 'Фильтр',
            'ordering' => 'Сортировка',
            'bindings' => 'Привязка полей',
        ),
    );

    //-------------------------------------------------------------------------

    protected function init() {
        require_once nc_core('SYSTEM_FOLDER') . 'form/fields/data_source/nc_data_source.class.php';
    }

    //-------------------------------------------------------------------------

    public function render_field($attr = array()) {
        $nc_core = nc_Core::get_object();
        $view = $nc_core->ui->view(__DIR__ . '/views/field.view.php');

        $view->with('field',              $this);
        $view->with('field_name',         $this->get_name());
        $view->with('field_value_json',   $this->get_value());
        $view->with('data_source_config', array(
            'ajax_path' => $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=system.form.fields.data_source.data_source',
        ));

        foreach ($this->default_settings as $key => $value) {
            $view->with($key, $this->get($key));
        }

        return $view;
    }

    //-------------------------------------------------------------------------
}