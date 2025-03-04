<?php


class nc_requests_list_admin_controller extends nc_requests_admin_controller {

    protected $request_infoblock_id;
    protected $request_subdivision_id;
    protected $request_template_id;
    protected $request_component_id;

    /**
     *
     */
    protected function init() {
        parent::init();
        $this->determine_request_infoblock_data();
        $this->ui_config = new nc_requests_list_admin_ui($this->site_id);
    }

    /**
     * @return callable
     */
    protected function get_site_list_filter() {
        return $this->get_site_module_checker('requests', 'requests_view');
    }

    /**
     *
     */
    protected function before_action() {
        $this->check_permissions(array(NC_PERM_MODULE, 'requests'), 'requests_view', $this->site_id);
        return parent::before_action();
    }

    /**
     *
     */
    protected function determine_request_infoblock_data() {
        $request_infoblock_id = $this->requests->get_request_infoblock_id();

        $db = nc_db();

        $sql = "SELECT `Class_ID`, `Subdivision_ID` FROM `Sub_Class` WHERE `Sub_Class_ID` = {$request_infoblock_id}";
        list($request_component_id, $request_subdivision_id) = $db->get_row($sql, ARRAY_N);

        // настройка «Шаблон компонента заказов для панели управления»
        // Если не указано в настройках, в качестве шаблона использовать помеченный как шаблон для режима администрирования
        $sql = "SELECT `Class_ID` FROM `Class` " .
            "WHERE (`Class_ID` = {$request_component_id} OR `ClassTemplate` = {$request_component_id}) " .
            "ORDER BY `Type` = 'inside_admin' DESC " .
            "LIMIT 1";
        $request_template_id = $db->get_var($sql);

        $this->request_component_id = $request_component_id;
        $this->request_template_id = $request_template_id;
        $this->request_subdivision_id = $request_subdivision_id;
        $this->request_infoblock_id = $request_infoblock_id;
    }

    /**
     * @return nc_ui_view
     */
    protected function action_index() {
        $nc_core = nc_core::get_object();
        // установка параметров для правильного вывода списка
        $nc_core->catalogue->set_current_by_id($this->site_id);

        $nc_core->inside_admin = 1;
        $GLOBALS['UI_CONFIG'] = $this->ui_config;
        $this->ui_config->locationHash = "#module.requests.list($this->site_id)";

        $list_vars = array_merge(
            $nc_core->input->fetch_get_post(),
            array(
                "nc_ctpl" => $this->request_template_id,
                "isMainContent" => 1,
                "catalogue" => $this->site_id,
                "inside_requests" => 1,
            )
        );
        $list_vars = http_build_query($list_vars, null, '&');

        // генерирование списка
        $request_list = nc_objects_list($this->request_subdivision_id, $this->request_infoblock_id, $list_vars, true);

        if ($nc_core->input->fetch_get_post('isNaked')) {
            $this->use_layout = false;
            return $request_list;
        } else {
            return $this->view('request_list')
                ->with('request_list', $request_list)
                ->with('site_filter', $this->get_site_list_filter())
                ->with('catalogue_id', $this->site_id);
        }
    }

    /**
     * @return nc_ui_view
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    public function action_view() {
        $nc_core = nc_core::get_object();
        $request_id = (int)$nc_core->input->fetch_get_post('request_id');

        $values = array();
        $component_id = nc_requests::get_instance($this->site_id)->get_request_component_id();
        $component = $nc_core->get_component($component_id);

        $fields = $component->get_fields();

        return $this->view('request_view')
            ->with('fields', $fields)
            ->with('values', $values);
    }
}