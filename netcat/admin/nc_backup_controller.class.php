<?php

class nc_backup_controller extends nc_ui_controller {

    protected $is_naked = false;

    /** @var  nc_backup */
    protected $backup;

    /**
     *
     */
    protected function init() {
        $this->backup = $this->nc_core->backup;

        $this->bind('export',      array());
        $this->bind('export_form', array('type'));
        $this->bind('export_run',  array('type', 'id', 'raw'));

        $this->bind('import', array());
        $this->bind('import_run', array('file', 'save_ids'));
    }

    /**
     * @param nc_ui_view $view
     */
    protected function init_view(nc_ui_view $view) {
        $view->with('backup',     $this->backup);
        $view->with('action_url', $this->nc_core->SUB_FOLDER . $this->nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.backup&action=');
    }

    /**
     * @return string
     */
    protected function before_action() {
        $this->check_permissions(NC_PERM_CLASS, NC_PERM_ACTION_ADD, 0, true); // директор
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
        $redirect_to = $this->nc_core->SUB_FOLDER . $this->nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.backup&action=' . $action;

        ob_get_level() && ob_clean();
        header("Location: {$redirect_to}");
        exit;
    }

    /**************************************************************************
        EXPORT
    **************************************************************************/

    /**
     * @return nc_ui_view
     */
    public function action_export() {
        $this->backup->file_rotation();
        $this->ui_config('export');

        return $this->view('backup/export')
            ->with('types',              array_merge(array(''=>''), $this->backup->get_allowed_types()))
            ->with('export_files',       $this->backup->get_export_files())
            ->with('export_limit_size',  $this->backup->get_settings('export_limit_size') * 1024 * 1024)
            ->with('export_limit_count', $this->backup->get_settings('export_limit_count'));
    }

    /**
     * @param $type
     * @return mixed
     * @throws Exception
     */
    public function action_export_form($type) {
        $this->is_naked = true;

        if (!$type) {
            throw new Exception('Type not set' , 1);
        }

        return $this->backup->$type->get_export_form();
    }

    /**
     * @param $type
     * @param $id
     * @param $raw
     */
    public function action_export_run($type, $id, $raw) {
        if (!$type) {
            return $this->redirect('export');
        }

        $this->is_naked = true;

        if ($raw) {
            return $this->backup->$type->export_download($id);
        }

        $this->backup->$type->export($id);

        return $this->redirect('export');
    }

    /**
     *
     */
    public function action_remove_export_files() {
        $this->backup->remove_export_files();
        return $this->redirect('export');
    }

    /**************************************************************************
        IMPORT
    **************************************************************************/

    public function action_import() {
        $this->ui_config('import');
        return $this->view('backup/import');
    }

    /**
     * @param $type
     * @return null
     * @throws Exception
     */
    public function action_import_form($type) {
        $this->is_naked = true;

        if (!$type) {
            throw new Exception('Type not set' , 1);
        }

        return $this->backup->$type->get_import_form();
    }

    /**
     * @param $file
     * @param $save_ids
     * @return nc_ui_view
     */
    public function action_import_run($file, $save_ids) {
        $this->check_permissions(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADD, 0, true);
        if ($file) {
            // $type = $this->backup->detect_type($file);
            $file = $file['tmp_name'];
        }
        $settings = array(
            'save_ids' => (bool)$save_ids,
            'replace'  => false
        );

        try {
            $result = $this->backup->import($file, $settings);
            $result['success'] = TOOLS_DATA_BACKUP_IMPORT_COMPLETE;
        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage()
            );
        }

        return $this->view('backup/import_result')->with('result', $result);
    }

    /**************************************************************************

    **************************************************************************/

    /**
     * @param $mode
     */
    protected function ui_config($mode) {
        $this->ui_config = new ui_config(array(
            'headerText'   => TOOLS_DATA_BACKUP,
            // 'treeMode'     => 'sitemap',
            'tabs'         => array(
                array('id' => 'index', 'caption' => TOOLS_DATA_BACKUP_SYSTEM),
            ),
            'toolbar'         => array(
                array(
                    'id'       => 'export',
                    'caption'  => TOOLS_EXPORT,
                    'location' => 'tools.databackup.export',
                    'group'    => "group1"
                ),
                array(
                    'id'       => 'import',
                    'caption'  => TOOLS_IMPORT,
                    'location' => 'tools.databackup.import',
                    'group'    => "group1"
                ),
            ),
            // 'activeToolbarButtons' => array('export'),
            'activeTab'    => 'index',
        ));
        $this->ui_config->actionButtons[] = array(
            // 'id'   => 'nc_dashboard_add_widget',
            'caption' => WIDGET_ADD_CONTINUE,
            'action'  => 'nc.view.main(\'form\').submit(); return false;',
            'align'   => 'right',
            'style'   => 'nc_dashboard_add_widget', // className
        );
        $this->ui_config->activeToolbarButtons = array($mode);
        $this->ui_config->locationHash = 'tools.databackup.' . $mode;
    }
}