<?php



class nc_widget_block_controller extends nc_ui_controller {

    //-------------------------------------------------------------------------

    protected $system_widgetclass_ids = array(63);

    protected $field_table;
    protected $widget_table;
    protected $widget_field_table;

    protected $is_naked;

    //-------------------------------------------------------------------------

    protected function init() {
        $this->bind('add',         array('catalogue', 'block', 'width', 'widgets'));
        $this->bind('settings',    array('widget_class_id', 'block_widget_id'));
        $this->bind('edit',        array('block_widget_id', 'width', 'widgets'));
        $this->bind('checked',     array('block_widget_id', 'checked', 'back_link'));
        $this->bind('delete',      array('block_widget_id', 'back_link'));
        $this->bind('delete_post', array('block_widget_id', 'back_link'));

        $this->field_table        = nc_db_table::make('Field');
        $this->widget_table       = nc_db_table::make('Widget');
        $this->widget_class_table = nc_db_table::make('Widget_Class');
        $this->widget_field_table = nc_db_table::make('Widget_Field', 'ID');
        $this->block_widget_table = $this->nc_core->widget->block_widget_table;

        $this->is_naked = (bool) $this->input->fetch_post_get('isNaked');
    }

    //-------------------------------------------------------------------------

    protected function after_action($result) {
        if ($this->is_naked) {
            return $result;
        }

        return BeginHtml() . $result . EndHtml();
    }

    /**************************************************************************
        ACTIONS
    **************************************************************************/

    public function action_add($catalogue_id, $block_key, $width = null, $widget_types = null) {
        $this->check_permissions(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADD, 0, false);

        if (!$catalogue_id || !$block_key) {
            return 'Wrong params!';
        }

        return $this->form(array(
            'catalogue_id' => $catalogue_id,
            'block_key'    => $block_key,
            'width'        => $width,
            'widget_types' => $widget_types,
            'form_action'  => 'add_post',
        ));
    }

    //-------------------------------------------------------------------------

    public function action_add_post() {
        $this->check_permissions(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADD);

        $this->is_naked = true;
        $data = $this->input->fetch_post();
        $data['Widget_Class_ID'] = $this->widget_table->where_id($data['Widget_ID'])->get_value('Widget_Class_ID');
        $data['Widget_Settings'] = empty($data['custom_settings']) ? '' : serialize($data['custom_settings']);
        $this->block_widget_table->insert($data);

        $this->is_naked = true;
        return 'ReloadPage=1';
    }

    //-------------------------------------------------------------------------

    public function action_edit($block_widget_id, $width = null, $widget_types = null) {
        $this->check_permissions(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_EDIT, $block_widget_id, false);

        return $this->form(array(
            'block_widget_id' => $block_widget_id,
            'width'           => $width,
            'widget_types'    => $widget_types,
            'form_action'     => 'edit_post',
        ));
    }

    //-------------------------------------------------------------------------

    public function action_edit_post() {
        $this->check_permissions(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_EDIT);

        $this->is_naked = true;
        $data = $this->input->fetch_post();
        $data['Widget_Settings'] = empty($data['custom_settings']) ? '' : serialize($data['custom_settings']);
        $this->block_widget_table->update($data);
        // return '<div id="nc_error">' . print_r($r,1) . ' ' . $this->block_widget_table->get_last_query() . '</div>';

        $this->is_naked = true;
        return 'ReloadPage=1';
    }

    //-------------------------------------------------------------------------

    public function action_settings($widget_id, $block_widget_id) {
        $this->check_permissions(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_ADMIN, $block_widget_id);

        $widget_class_id = $this->widget_table->where_id($widget_id)->get_value('Widget_Class_ID');

        if (in_array($widget_class_id, $this->system_widgetclass_ids)) {

            $block_widget = $this->block_widget_table->where_id($block_widget_id)->get_row();
            $settings = $block_widget['Widget_Settings'] ? unserialize($block_widget['Widget_Settings']) : array();

            $template_folder      = $this->nc_core->WIDGET_TEMPLATE_FOLDER . 'aggregator/' . $widget_id . '/';
            $custom_settings_file = $template_folder . 'CustomSettings.html';

            if (file_exists($custom_settings_file)) {
                $fields = include $custom_settings_file;
                $form = false;

                $view = $this->view('widget_block/widget_settings');

                if (is_array($fields)) {
                    $form   = new nc_a2f($fields, 'custom_settings');
                    $form->set_values($settings);
                    $view->with('form', $form)->with('fields', $form->get_fields());
                } else {
                    $view->with('form', false)->with('fields', array());
                }

                return $view;
            }

        }

        return $this->nc_core->ui->alert->info(NETCAT_CUSTOM_NONE_SETTINGS);
    }

    //-------------------------------------------------------------------------

    public function action_checked($block_widget_id, $checked, $back_link) {
        $this->check_permissions(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_EDIT, $block_widget_id);

        $this->block_widget_table->where_id($block_widget_id)->update(array(
            'Checked' => $checked
        ));

        $this->redirect($back_link);
    }

    //-------------------------------------------------------------------------

    public function action_delete($block_widget_id, $back_link) {
        $this->check_permissions(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_DEL, $block_widget_id, false);

        $this->is_naked = true;
        $action_url     = $this->nc_core->SUB_FOLDER . $this->nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.widget.widget_block';

        return $this->view('widget_block/delete', array(
            'block_widget_id' => $block_widget_id,
            'back_link'       => $back_link,
            'post_url'        => $action_url . '&action=delete_post'
        ));
    }

    //-------------------------------------------------------------------------

    public function action_delete_post($block_widget_id, $back_link) {
        $this->check_permissions(NC_PERM_WIDGETCLASS, NC_PERM_ACTION_DEL, $block_widget_id);

        if ($this->input->fetch_post('confirmed')) {
            $this->block_widget_table->where_id($block_widget_id)->delete();
        }

        $this->is_naked = true;
        return 'ReloadPage=1';
        // $this->redirect($back_link);
    }

    /**************************************************************************
        Protected part
    **************************************************************************/

    protected function redirect($link) {
        if (!$link) {
            $link = $_SERVER['HTTP_REFERER'];
        }

        ob_get_level() && ob_end_clean();
        header('Location: ' . $link);
        exit;
    }

    //-------------------------------------------------------------------------

    protected function form($data) {
        $action_url = $this->nc_core->SUB_FOLDER . $this->nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.widget.widget_block';

        if (isset($data['block_widget_id'])) {
            $block_widget         = $this->block_widget_table->where_id($data['block_widget_id'])->get_row();
            $data['priority']     = $block_widget['Priority'];
            $data['catalogue_id'] = $block_widget['Catalogue_ID'];
            $data['block_key']    = $block_widget['Block_Key'];
            $data['widget_id']    = $block_widget['Widget_ID'];
        }
        else {
            $data['block_widget_id'] = 0;
            $data['widget_id']       = 0;
            $data['priority']        = 0;
        }

        $data['width']        = array_map('intval', array_map('trim', explode(',', $data['width'])));
        $data['widget_types'] = array_map('trim', explode(',', $data['widget_types']));

        $data['recomended_widgets'] = $data['widget_types'] ? $this->get_recomended_widget_list($data['widget_types']) : array();
        $data['all_widgets']        = $this->get_all_widget_list();

        $data['widget_settings_url'] = $action_url . '&action=settings&isNaked=1&widget_class_id=';
        $data['post_url']            = $action_url . '&action=' . $data['form_action'];

        return $this->view('widget_block/form', $data);
    }

    //-------------------------------------------------------------------------

    protected function get_recomended_widget_list($widget_type_keywords = array()) {
        static $widget_type_field_ids;

        if ($widget_type_field_ids === null) {
            $widget_type_field_ids = $this->field_table
                ->where_in('Widget_Class_ID', $this->system_widgetclass_ids)
                ->where('Field_Name', 'widget_type_keyword')
                ->get_list('Field_ID');
        }

        $widget_ids = $this->widget_field_table
            ->where_in('Field_ID', $widget_type_field_ids)
            ->where_in('Value', $widget_type_keywords)
            ->get_list('Widget_ID', 'Widget_ID');

        return $this->widget_table->where_in_id($widget_ids)->order_by('Name')->get_list('Name');
    }

    //-------------------------------------------------------------------------

    protected function get_all_widget_list() {
        $widget_class_category = $this->widget_class_table
            // ->where('File_Mode', 1)
            ->order_by('Category')
            ->get_list('Category');

        $result  = $this->widget_table
            ->select('Widget_ID, Widget_Class_ID, Name')
            ->index_by_id()
            ->get_result();

        $widgets = array();

        // Sorting
        foreach ($widget_class_category as $category) {
            $widgets[$category] = array();
        }

        // Fill
        foreach ($result as $id => $widget) {
            $category = $widget_class_category[$widget['Widget_Class_ID']];
            $widgets[$category][$id] = $widget['Name'];
        }

        // Remove empty
        foreach ($widget_class_category as $category) {
            if (!$widgets[$category]) {
                unset($widgets[$category]);
            }
        }

        return $widgets;
    }

    //-------------------------------------------------------------------------
}