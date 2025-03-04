<?php

class nc_csv_controller extends nc_ui_controller {
    /** @var  nc_csv */
    protected $csv;

    protected $is_naked = false;
    protected $export_types = array(
        'subclass_type' => TOOLS_CSV_EXPORT_TYPE_SUBCLASS,
        'component_type' => TOOLS_CSV_EXPORT_TYPE_COMPONENT
    );

    /**
     *
     */
    protected function init() {
        $this->csv = $this->nc_core->csv;

        $this->bind('export', array());
        $this->bind('export_form', array('type'));
        $this->bind('export_run', array('type', 'data'));
        $this->bind('import', array());
        $this->bind('import_run', array('file', 'data'));
        $this->bind('import_finish', array('file', 'data'));
        $this->bind('delete', array('file'));
        $this->bind('import_history', array());
        $this->bind('rollback', array('id'));
    }

    /**
     * @param nc_ui_view $view
     */
    protected function init_view(nc_ui_view $view) {
        $view->with('csv', $this->csv);
        $view->with('action_url', $this->nc_core->SUB_FOLDER . $this->nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.csv.csv&action=');
    }

    /**
     * @return string
     */
    protected function before_action() {
        $nc_core = nc_core::get_object();
        $data = $nc_core->input->fetch_post('data');
        $site_id = nc_array_value($data, 'site_id', 0);
        $component_id = nc_array_value($data, 'component_id', 0);
        if ($site_id) {
            $this->check_permissions(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADMIN, $site_id, true);
        } else if ($component_id) {
            $this->check_permissions(NC_PERM_CLASS, NC_PERM_ACTION_ADMIN, $component_id, true);
        }
        return parent::before_action();
    }

    /**
     * @param $result
     * @return string
     */
    protected function after_action($result) {
        // JSON
        if (is_array($result)) {
            return json_safe_encode($result);
        }
        // With template
        if (!$this->is_naked) {
            return BeginHtml() . $result . EndHtml();
        }

        return $result;
    }

    /**
     * @param $action
     */
    protected function redirect($action) {
        $redirect_to = $this->nc_core->SUB_FOLDER . $this->nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.csv.csv&action=' . $action;

        ob_get_level() && ob_clean();
        header("Location: {$redirect_to}");
        exit;
    }

    /**
     * @return nc_ui_view
     */
    public function action_export() {
        $this->ui_config('export');
        return $this->view('export')
                    ->with('types', array_merge(array("" => TOOLS_CSV_NOT_SELECTED), $this->export_types));
    }

    /**
     * @param $type
     * @return mixed
     * @throws Exception
     */
    public function action_export_form($type) {
        $this->is_naked = true;

        if (!$type) {
            throw new Exception('Type not set', 1);
        }
        $method = "get_" . $type . "_export_form";
        $id = (int)$this->input->fetch_post_get('object_id');
        return $this->csv->$method($id);
    }

    /**
     * @param $type
     * @param $data
     * @return nc_ui_view|mixed
     */
    public function action_export_run($type, $data) {
        if (!$type) {
            return $this->redirect('export');
        }
        $method = "export_" . $type;
        $file = $this->csv->$method($data);
        return $this->view('export_finished')->with('file', $file);
    }

    /**
     * @return nc_ui_view
     */
    public function action_import() {
        $this->ui_config('import');
        return $this->view('import')
                    ->with('settings', $this->csv->get_csv_settings_form());
    }

    /**
     * @param $file
     * @param $data
     * @return nc_ui_view
     */
    public function action_import_run($file, $data) {
        if ($file) {
            $file = $file['tmp_name'];
        }

        try {
            $result = $this->csv->preimport_file($file, $data);
        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage()
            );
        }

        return $this->view('import_result')->with('data', $result);
    }

    /**
     * @param $file
     * @param $data
     * @return nc_ui_view
     */
    public function action_import_finish($file, $data) {
        try {
            $result = $this->csv->import_file($file, $data);
        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage()
            );
        }
        return $this->view('import_finished')->with('data', $result);
    }

    /**
     * @param $file
     * @return nc_ui_view
     */
    public function action_delete($file) {
        $result = array();
        try {
            unlink(nc_core()->backup()->get_export_path() . "csv/" . $file);
        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage()
            );
        }
        return $this->view('deleted')->with('data', $result);
    }

    /**
     * @param $id
     * @return nc_ui_view
     */
    public function action_rollback($id) {
        try {
            $result = $this->csv->rollback($id);
        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage()
            );
        }
        return $this->view('rollback_finished')->with('data', $result);
    }

    /**
     * @return nc_ui_view
     */
    public function action_import_history() {
        $this->ui_config('import_history');
        $result = $this->csv->history_list();
        return $this->view('import_history')->with('data', $result);

    }

    /**
     * @param $mode
     */
    protected function ui_config($mode) {
        $this->ui_config = new ui_config(array(
            'headerText' => TOOLS_CSV,
            'tabs' => array(
                array('id' => 'index', 'caption' => TOOLS_CSV),
            ),
            'toolbar' => array(
                array(
                    'id' => 'export',
                    'caption' => TOOLS_CSV_EXPORT,
                    'location' => 'tools.csv.export',
                    'group' => "group1"
                ),
                array(
                    'id' => 'import',
                    'caption' => TOOLS_CSV_IMPORT,
                    'location' => 'tools.csv.import',
                    'group' => "group1"
                ),
                array(
                    'id' => 'import_history',
                    'caption' => TOOLS_CSV_IMPORT_HISTORY,
                    'location' => 'tools.csv.import_history',
                    'group' => "group1"
                ),
            ),
            'activeTab' => 'index',
        ));
        if ($mode == 'export') {
            $this->ui_config->actionButtons[] = array(
                'caption' => TOOLS_CSV_CREATE_EXPORT,
                'action' => 'return false;',
                'align' => 'right',
                'style' => 'nc_csv_do_export nc--disabled', // className
            );
        } else if ($mode == 'import') {
            $this->ui_config->actionButtons[] = array(
                'caption' => TOOLS_CSV_IMPORT_RUN,
                'action' => 'nc.view.main(\'form\').submit();return false;',
                'align' => 'right',
                'style' => 'nc_csv_do_import', // className
            );
        }
        $this->ui_config->activeToolbarButtons = array($mode);
        $this->ui_config->locationHash = 'tools.csv.' . $mode;
    }

}
