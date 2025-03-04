<?php

class nc_redirect_controller extends nc_ui_controller {

    protected $is_naked = false;

    /** @var  nc_redirect_ui */
    protected $ui_config;

    /**
     *
     */
    protected function init() {
        require_once 'nc_redirect.class.php';
        require_once 'ui.php';

        $this->bind('list', array('group'));
        $this->bind('move', array('group'));
        $this->bind('move_finish', array('group'));
        $this->bind('edit', array('group'));
        $this->bind('delete', array('dgroup', 'group'));
        $this->bind('edit_group', array('group'));
        $this->bind('import', array('group'));
        $this->bind('import_run', array('file', 'data'));
        $this->bind('import_finish', array('file', 'data'));

        $this->ui_config = new nc_redirect_ui();

        if (nc_core()->input->fetch_get('naked')) {
            $this->is_naked = true;
        }

        require_once 'nc_redirect.class.php';
    }

    /**
     * @param nc_ui_view $view
     */
    protected function init_view(nc_ui_view $view) {
        $view->with('action_url', nc_core()->NETCAT_FOLDER . 'action.php?ctrl=admin.redirect.redirect&action=');
    }

    /**
     * @return bool|nc_ui_view|null
     */
    public function action_check() {
        $data = nc_core()->input->fetch_get_post();
        $res = '';

        if (count($data['redirect'])) {
            $res = nc_db_table::make('Redirect')->where_in_id($data['redirect'])->set('Checked', $data['check'])->update();
        }

        if ($this->is_naked) {
            return $res;
        }

        return $this->action_list(nc_core()->input->fetch_get('group'));
    }

    /**
     * @param int $group
     * @param string $status
     * @return nc_ui_view
     */
    public function action_list($group = 0, $status = '') {
        $group = (int)$group;

        $group_name = nc_db_table::make('Redirect_Group')->select()->where_id($group)->get_value('Name');

        $this->ui_config->title(REDIRECT_TAB_LIST . ", $group_name");
        $this->ui_config->location('list', $group);
        $this->ui_config->select_node($group);
        $this->ui_config->submit_action_button("edit&group=$group", TOOLS_REDIRECT_ADDONLY);

        $query = "SELECT * FROM `%t%` WHERE `Group_ID` = $group ORDER BY `Redirect_ID`";
        try {
            $redirects = nc_record_collection::load('nc_redirect', $query);
        } catch (nc_record_exception $e) {
            $redirects = array();
        }

        if (count($redirects)) {
            $this->ui_config->submit_action_button("delete&group=$group", NETCAT_ADMIN_DELETE_SELECTED, 'right', true);
            $this->ui_config->submit_action_button("check&group=$group&check=1", NETCAT_MODERATION_SELECTEDON);
            $this->ui_config->submit_action_button("check&group=$group&check=0", NETCAT_MODERATION_SELECTEDOFF);
            $this->ui_config->submit_action_button("move&group=$group", TOOLS_REDIRECT_MOVE);
        } else {
            $status = TOOLS_REDIRECT_NONE;
        }
        $this->ui_config->submit_action_button("import&group=$group", TOOLS_REDIRECT_IMPORT);

        return $this->view('list', array('group' => $group, 'redirects' => $redirects, 'status' => $status));
    }

    /**
     * @param int $group
     * @param string $error
     * @return nc_ui_view|string
     */
    public function action_move($group = 0, $error = '') {
        $groups = array();
        $group = (int)$group;
        $data = nc_core()->input->fetch_get_post();

        $this->ui_config->title(TOOLS_REDIRECT_MOVE_TITLE);
        $this->ui_config->location('move');
        $this->ui_config->select_node($group);
        $this->ui_config->back_button();

        if (empty($data['redirect'])) {
            return TOOLS_REDIRECT_NOTHING_SELECTED;
        }

        $query = "SELECT * FROM `%t%` WHERE
            `Redirect_ID` IN (" . implode(',', $data['redirect']) . ")
            ORDER BY `Redirect_ID`";
        try {
            $redirects = nc_record_collection::load('nc_redirect', $query);
        } catch (nc_record_exception $e) {
            $redirects = array();
        }

        $this->ui_config->submit_button(TOOLS_REDIRECT_MOVE);

        $res = nc_db_table::make('Redirect_Group')->select()->as_array()->get_result();
        foreach ($res as $value) {
            $groups[$value['Redirect_Group_ID']] = $value['Name'];
        }

        return $this->view('move', array('group' => $group, 'groups' => $groups, 'redirects' => $redirects, 'error' => $error));
    }

    /**
     * @param $group
     * @return string
     */
    public function action_move_finish($group) {
        $group = (int)$group;
        $data = nc_core()->input->fetch_get_post();

        nc_db_table::make('Redirect')->where_in_id($data['redirect'])->set('Group_ID', $group)->update();

        $status = nc_print_status(TOOLS_REDIRECT_MOVE_OK, 'ok', null, 1);
        return $status . $this->action_list($group);
    }

    /**
     * @param int $group
     * @param nc_redirect|null $redirect
     * @return nc_ui_view|string
     */
    public function action_edit($group = 0, nc_redirect $redirect = null) {
        $groups = array();
        $group = (int)$group;
        $id = (int)nc_core()->input->fetch_get('id');

        $this->ui_config->back_button($redirect ? 2 : 1);

        if ($redirect == null) {
            $redirect = new nc_redirect();
        }

        if ($id) {
            $this->ui_config->location('edit', $id);
            try {
                $redirect->load($id);
            } catch (nc_record_exception $e) {
                return $e->getMessage();
            }
        }
        if (!$redirect->get_id()) {
            $this->ui_config->title(TOOLS_REDIRECT_ADD);
            $this->ui_config->location('add', $group);
        } else {
            $this->ui_config->title(TOOLS_REDIRECT_EDIT);
            $group = $redirect['group'];
            $this->ui_config->select_node($group);
        }

        $this->ui_config->submit_button();

        $res = nc_db_table::make('Redirect_Group')->select()->as_array()->get_result();
        foreach ($res as $value) {
            $groups[$value['Redirect_Group_ID']] = $value['Name'];
        }

        return $this->view('edit', array('redirect' => $redirect, 'group' => $group, 'groups' => $groups));
    }

    /**
     * @return nc_ui_view|string
     */
    public function action_save() {
        $data = nc_core()->input->fetch_post();
        $group = $data['group'];

        $redirect = new nc_redirect();
        $redirect->set_values_from_form($data);

        if (!$redirect->validate()) {
            return $this->action_edit($group, $redirect);
        }

        $redirect->save();

        $status = nc_print_status(TOOLS_REDIRECT_SAVE_OK, 'ok', null, 1);

        return $status . $this->action_list($group);
    }

    /**
     * @param int $group
     * @param string $error
     * @return nc_ui_view|string
     */
    public function action_edit_group($group = 0, $error = '') {
        $group = (int)$group;

        $this->ui_config->back_button($error ? 2 : 1);

        if (!$group) {
            $this->ui_config->title(TOOLS_REDIRECT_GROUP_ADD);
            $this->ui_config->location('group.add');
            $group_name = '';
        } else {
            $this->ui_config->title(TOOLS_REDIRECT_GROUP_EDIT);
            $this->ui_config->location('group.edit', $group);
            $result = nc_db_table::make('Redirect_Group')->select()->where_id($group)->as_array()->get_row();
            if (!$result) {
                return 'wrong group';
            }
            $group_name = $result['Name'];

            $this->ui_config->select_node($group);
        }
        $this->ui_config->submit_button();

        return $this->view('edit_group', array('group' => $group, 'group_name' => $group_name, 'error' => $error));
    }

    /**
     * @return nc_ui_view|string
     */
    public function action_save_group() {
        $data = nc_core()->input->fetch_post();
        $group = $data['group'];
        $group_name = $data['group_name'];

        if (!$group_name) {
            return $this->action_edit_group($group, TOOLS_REDIRECT_CANTBEEMPTY);
        }

        $redirect_group_table = nc_db_table::make('Redirect_Group');
        $redirect_group_table->set('Name', $group_name);
        if ($group) {
            $redirect_group_table->where_id($group)->update();
            $this->ui_config->tree_change_node($group, $group_name);
        } else {
            $group = $redirect_group_table->insert();
            $this->ui_config->tree_add_node($group, $group_name);
        }

        $status = nc_print_status(TOOLS_REDIRECT_GROUP_SAVE_OK, 'ok', null, 1);

        return $status . $this->action_list($group);
    }

    /**
     * @param int $dgroup
     * @param int $group
     * @param string $error
     * @return nc_ui_view|string
     */
    public function action_delete($dgroup = 0, $group = 0, $error = '') {
        $group = (int)$group;
        $dgroup = (int)$dgroup;
        $data = nc_core()->input->fetch_post();
        $group_name = '';

        $this->ui_config->title(TOOLS_REDIRECT_DELETE_TITLE);
        $this->ui_config->location('delete', $dgroup);
        $this->ui_config->back_button();

        if ($dgroup) {
            $result = nc_db_table::make('Redirect_Group')->select()->where_id($dgroup)->as_array()->get_row();
            if (!$result) {
                return 'wrong group';
            }
            $group_name = $result['Name'];
            $this->ui_config->select_node($dgroup);
        }

        if (!$dgroup && empty($data['redirect'])) {
            return TOOLS_REDIRECT_NOTHING_SELECTED;
        }

        $query = "SELECT * FROM `%t%` WHERE
            " . ($dgroup ? "`Group_ID` = $dgroup" : "`Redirect_ID` IN (" . implode(',', $data['redirect']) . ")") . "
            ORDER BY `Redirect_ID`";
        try {
            $redirects = nc_record_collection::load('nc_redirect', $query);
        } catch (nc_record_exception $e) {
            $redirects = array();
        }

        $this->ui_config->submit_button(TOOLS_REDIRECT_DELETE, 'right', true);

        return $this->view('delete', array('dgroup' => $dgroup, 'group' => $group, 'group_name' => $group_name, 'redirects' => $redirects, 'error' => $error));
    }

    /**
     * @return string
     */
    public function action_delete_process() {
        $data = nc_core()->input->fetch_post();
        $group = (int)$data['group'];
        $dgroup = (int)$data['dgroup'];

        if ($dgroup) {
            nc_db_table::make('Redirect_Group')->where_id($dgroup)->delete();
            nc_db_table::make('Redirect')->where('Group_ID', $dgroup)->delete();
            $this->ui_config->tree_delete_node($dgroup);
        }

        if (!$dgroup && !empty($data['redirect'])) {
            nc_db_table::make('Redirect')->where_in_id($data['redirect'])->delete();
        }

        $status = nc_print_status(TOOLS_REDIRECT_DELETE_OK, 'ok', null, 1);
        return $status . $this->action_list($dgroup ? 1 : $group);
    }

    /**
     * @param $group
     * @return nc_ui_view
     */
    public function action_import($group) {
        $group = (int)$group;
        $groups = array();

        $this->ui_config->title(TOOLS_REDIRECT_IMPORT_TITLE);
        $this->ui_config->location('import');
        $this->ui_config->select_node($group);
        $this->ui_config->back_button(nc_core()->input->fetch_post('nc_token') ? 1 : 2);

        $this->ui_config->submit_button(TOOLS_REDIRECT_CONTINUE);

        $res = nc_db_table::make('Redirect_Group')->select()->as_array()->get_result();
        foreach ($res as $value) {
            $groups[$value['Redirect_Group_ID']] = $value['Name'];
        }

        return $this->view('import', array('group' => $group, 'groups' => $groups, 'settings' => $this->csv()->get_csv_settings_form()));
    }

    /**
     * @param $file
     * @param $data
     * @return nc_ui_view
     */
    public function action_import_run($file, $data) {
        $this->ui_config->title(TOOLS_REDIRECT_IMPORT_TITLE);
        $this->ui_config->location('import_fields');
        $this->ui_config->select_node($data['group']);
        $this->ui_config->back_button();

        $csv = $this->csv();
        if ($file) {
            $file = $file['tmp_name'];
        }

        try {
            $result = $csv->preimport_file($file, $data);
        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage()
            );
        }
        if (!$result['error']) {
            $this->ui_config->submit_button(TOOLS_REDIRECT_DO_IMPORT);
        }

        return $this->view('import_fields')->with('data', $result);
    }

    /**
     * @param $file
     * @param $data
     * @return string
     */
    public function action_import_finish($file, $data) {
        $csv = $this->csv();
        try {
            $result = $csv->import_file($file, $data);
            $status = nc_print_status(TOOLS_CSV_IMPORT_SUCCESS . $result['success'], 'ok', null, 1);
        } catch (Exception $e) {
            $status = nc_print_status($e->getMessage(), 'error', null, 1);
        }

        return $status . $this->action_list($data['group']);
    }

    /**
     * @return string
     */
    protected function before_action() {
        if (!$this->is_naked) {
            BeginHtml();
        }

        if (in_array($this->current_action, array('save', 'save_group', 'delete_process', 'check', 'move_finish', 'import_finish'))
            && !nc_core('token')->verify()
        ) {
            nc_print_status(NETCAT_TOKEN_INVALID, 'error');
            exit;
        }

        $this->check_permissions(NC_PERM_REDIRECT, NC_PERM_ACTION_ADMIN, 0, true);

        if ($this->nc_core->NC_REDIRECT_DISABLED) {
            nc_print_status(TOOLS_REDIRECT_DISABLED, 'info');
        }
        return '';
    }

    /**
     * @param $result
     * @return string
     */
    protected function after_action($result) {
        echo $result;
        if (!$this->is_naked) {
            $this->ui_config->reload_add_node_button();
            EndHtml();
        }

        return '';
    }

    /**
     * @return nc_csv|nc_redirect_csv
     */
    protected function csv() {
        $this->nc_core->csv;
        require_once 'nc_redirect_csv.class.php';
        return nc_redirect_csv::get_instance();
    }
}
