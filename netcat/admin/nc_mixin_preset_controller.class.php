<?php

class nc_mixin_preset_controller extends nc_ui_controller {

    protected $is_naked = false;

    /**
     * @return string|void
     */
    protected function before_action() {
        $this->check_permissions(NC_PERM_TEMPLATE, NC_PERM_ACTION_EDIT, 0, true);
    }

    /**
     * @return nc_db_table
     */
    protected function get_table() {
        return nc_db_table::make('Mixin_Preset');
    }

    /**
     * @return nc_ui_view
     */
    protected function action_show_edit_dialog() {
        $nc_core = nc_core::get_object();
        $data = $nc_core->input->fetch_post('data') ?: array();
        $scope = nc_array_value($data, 'Scope', 'Index');

        $mixin_preset_id =
            (int)$nc_core->input->fetch_get_post('mixin_preset_id') ?:
            (int)nc_array_value($data, 'Mixin_Preset_ID');

        if ($mixin_preset_id) {
            $data = (array)$this->get_table()->get_row($mixin_preset_id);
        }

        $current_component_template_id = (int)(nc_array_value($data, 'Class_Template_ID') ?: $nc_core->input->fetch_post('component_template_id'));
        $component_select_options = $this->get_component_select_options($current_component_template_id);
        $is_default_for_current_component_template =
            $current_component_template_id &&
            $mixin_preset_id &&
            (int)$nc_core->component->get_by_id($current_component_template_id, $scope . '_Mixin_Preset_ID') === $mixin_preset_id;

        return $this->view('mixin/preset_edit_dialog', array(
            'mixin_preset_id' => $mixin_preset_id,
            'data' => $data,
            'component_select_options' => $component_select_options,
            'current_component_template_id' => $current_component_template_id,
            'is_default_for_current_component_template' => $is_default_for_current_component_template,
        ));
    }

    /**
     * @return nc_ui_view
     */
    protected function action_show_delete_dialog() {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        $mixin_preset_id = (int)$nc_core->input->fetch_get_post('mixin_preset_id');
        $mixin_preset_data = $this->get_table()->where_id($mixin_preset_id)->get_row();
        $mixin_preset_field = $mixin_preset_data['Scope'] . '_Mixin_Preset_ID'; // e. g. Index_Mixin_Preset_ID

        $number_of_blocks = $db->get_var("SELECT COUNT(*) FROM `Sub_Class` WHERE `$mixin_preset_field` = $mixin_preset_id");
        $number_of_templates = $db->get_var("SELECT COUNT(*) FROM `Class` WHERE `$mixin_preset_field` = $mixin_preset_id");

        return $this->view('mixin/preset_delete_dialog', array(
            'mixin_preset_id' => $mixin_preset_id,
            'mixin_preset_name' => $mixin_preset_data['Mixin_Preset_Name'],
            'number_of_blocks' => $number_of_blocks,
            'number_of_templates' => $number_of_templates,
        ));
    }

    /**
     *
     */
    protected function action_save() {
        $data = $this->input->fetch_post('data');

        $mixin_preset_table = $this->get_table();
        $preset_id = (int)nc_array_value($data, 'Mixin_Preset_ID');
        if ($preset_id > 0) {
            $mixin_preset_table->where_id($preset_id)->update($data);
        } else {
            $preset_id = $mixin_preset_table->insert($data);
        }

        $component_template_id = (int)$this->input->fetch_post('component_template_id');
        $set_as_default = $this->input->fetch_post('set_as_default');
        if ($component_template_id && $set_as_default !== null) {
            $scope = nc_array_value($data, 'Scope', 'Index');
            nc_db_table::make('Class')->where_id($component_template_id)->update(array(
                $scope . '_Mixin_Preset_ID' => $set_as_default ? $preset_id : null,
            ));
        }

        echo $preset_id;
        die;
    }

    /**
     *
     */
    protected function action_delete() {
        $nc_core = nc_core::get_object();

        $mixin_preset_id = (int)$nc_core->input->fetch_get_post('mixin_preset_id');
        $scope = $this->get_table()->where_id($mixin_preset_id)->get_value('Scope');
        $this->get_table()->where_id($mixin_preset_id)->delete();

        $mixin_preset_field = $scope . '_Mixin_Preset_ID'; // e. g. Index_Mixin_Preset_ID
        $nc_core->db->query("UPDATE `Class`     SET `$mixin_preset_field` = NULL WHERE `$mixin_preset_field` = $mixin_preset_id");
        $nc_core->db->query("UPDATE `Sub_Class` SET `$mixin_preset_field` = -1   WHERE `$mixin_preset_field` = $mixin_preset_id");

        echo $mixin_preset_id;
        die;
    }

    /**
     * @param int $component_template_id
     * @return array
     */
    protected function get_component_select_options($component_template_id) {
        $component_select_options = array(
            '0' => NETCAT_MIXIN_PRESET_FOR_ANY_COMPONENT,
        );

        $component_template_name = '';
        try {
            if ($component_template_id) {
                $nc_core = nc_core::get_object();
                $parent_component_id = $nc_core->component->get_by_id($component_template_id, 'ClassTemplate');
                if ($parent_component_id) {
                    $component_template_name .= sprintf(
                        NETCAT_MIXIN_PRESET_FOR_COMPONENT_TEMPLATE_PREFIX,
                        $nc_core->component->get_by_id($component_template_id, 'Class_Name')
                    ) . ' ';
                }
                $component_template_name .= sprintf(
                    NETCAT_MIXIN_PRESET_FOR_COMPONENT,
                    $nc_core->component->get_by_id($parent_component_id ?: $component_template_id, 'Class_Name')
                );
                $component_select_options[$component_template_id] = $component_template_name;
            }
        } catch (Exception $e) {}

        return $component_select_options;
    }

}
