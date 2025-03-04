<?php

/**
 * Когда-нибудь наступит светлое будущее и сюда переедет весь функционал управления списками.
 * А пока только редактирование пользовательских настроек.
 *
 * Class nc_classificator_controller
 */
class nc_list_controller extends nc_ui_controller {

    protected $use_layout = true;
    private $classificator_id = null;

    function init() {
        global $perm;
        $nc_core = nc_Core::get_object();
        require($nc_core->ADMIN_FOLDER . "classificator.inc.php");

        $this->classificator_id = (int)$nc_core->input->fetch_get('classificator_id');
        if ($nc_core->input->fetch_get('isNaked')) {
            $this->use_layout = false;
        }
        if ($this->classificator_id) {
            $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_VIEW, $this->classificator_id, 0, 0);
        }
    }

    protected function action_custom() {
        global $perm;
        $nc_core = nc_Core::get_object();

        $list = $nc_core->list->get_by_id_or_keyword($this->classificator_id);

        if ($list['System']) {
            if (!$perm->isDirectAccessClassificator(NC_PERM_ACTION_VIEW, $this->classificator_id)) {
                return nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error', null, true);
            }
            $admin_cl = $perm->isDirectAccessClassificator(NC_PERM_ACTION_ADMIN, $this->classificator_id);
            $access_to_add = $perm->isDirectAccessClassificator(NC_PERM_ACTION_ADDELEMENT, $this->classificator_id);
        } else {
            $admin_cl = $perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADMIN, $this->classificator_id);
            $access_to_add = $perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADDELEMENT, $this->classificator_id);
        }

        $this->ui_config = new ui_config_classificator('custom', $this->classificator_id);

        //$this->ui_config->activeTab = 'edit_custom';
        $this->ui_config->actionButtons = array();
        if($access_to_add) {
            $this->ui_config->actionButtons[] = array(
                "id" => "add_field",
                "caption" => CONTROL_FIELD_LIST_ADD,
                "location" => "classificator.custom.add(" . $this->classificator_id . ")",
                "align" => "left"
            );
            $this->ui_config->actionButtons[] = array(
                "id" => "add_manual",
                "caption" => NETCAT_CUSTOM_ONCE_MANUAL_EDIT,
                "location" => "classificator.custom.manual(" . $this->classificator_id . ")",
                "align" => "left"
            );
            $this->ui_config->actionButtons[] = array(
                "id" => "del",
                "caption" => NETCAT_CUSTOM_ONCE_DROP_SELECTED,
                "action" => "mainView.submitIframeForm()",
                "align" => "right",
                "red_border" => true,
            );
        }

        $custom_settings = $list['CustomSettingsTemplate'];

        return $this->view('list/custom', array(
            'saved' => $nc_core->input->fetch_get('saved'),
            'custom_settings' => $custom_settings,
            'view_only' => !$admin_cl,
        ));
    }


    protected function action_custom_edit($error_message = '') {
        global $perm;

        $nc_core = nc_Core::get_object();
        $list = $nc_core->list->get_by_id($this->classificator_id);

        $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADDELEMENT, $this->classificator_id, 0, 0);
        if ($list['System'] && !$perm->isDirectAccessClassificator(NC_PERM_ACTION_ADDELEMENT, $this->classificator_id)) {
            return $this->view('error_message', array(
                'message' => CONTENT_CLASSIFICATORS_ERR_EDITI_GUESTRIGHTS
            ));
        }

        $param = $nc_core->input->fetch_get_post('param');
        $this->ui_config = new ui_config_classificator('custom', $this->classificator_id);
        $this->ui_config->actionButtons = array(
            array(
                "id" => "submit",
                "caption" => (NETCAT_CUSTOM_ONCE_SAVE),
                "action" => "mainView.submitIframeForm()",
                "align" => "right"
            )
        );
        $this->ui_config->locationHash = "#classificator.custom.edit(" . $this->classificator_id . ", " . $param . ")";

        $custom_settings = $nc_core->list->get_by_id_or_keyword($this->classificator_id, 'CustomSettingsTemplate');

        return $this->view('list/custom_add', array(
            'custom_settings' => $custom_settings,
            'error' => $error_message
        ));
    }

    protected function action_custom_add($error_message = '') {
        global $perm;
        $nc_core = nc_Core::get_object();
        $list = $nc_core->list->get_by_id($this->classificator_id);

        $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADDELEMENT, $this->classificator_id, 0, 0);
        if ($list['System'] && !$perm->isDirectAccessClassificator(NC_PERM_ACTION_ADDELEMENT, $this->classificator_id)) {
            return $this->view('error_message', array(
                'message' => CONTENT_CLASSIFICATORS_ERR_EDITI_GUESTRIGHTS
            ));
        }

        $this->ui_config = new ui_config_classificator('custom', $this->classificator_id);
        $this->ui_config->actionButtons = array(
            array(
                "id" => "submit",
                "caption" => NETCAT_CUSTOM_ONCE_ADD,
                "action" => "mainView.submitIframeForm()",
                "align" => "right"
            )
        );
        $this->ui_config->locationHash = "#classificator.custom.add(" . $this->classificator_id . ")";

        $custom_settings = $nc_core->list->get_by_id_or_keyword($this->classificator_id, 'CustomSettingsTemplate');

        return $this->view('list/custom_add', array(
            'custom_settings' => $custom_settings,
            'error' => $error_message
        ));
    }

    protected function action_custom_manual() {
        global $perm;

        $nc_core = nc_Core::get_object();
        $list = $nc_core->list->get_by_id_or_keyword($this->classificator_id);

        $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADDELEMENT, $this->classificator_id, 0, 0);
        if ($list['System'] && !$perm->isDirectAccessClassificator(NC_PERM_ACTION_ADDELEMENT, $this->classificator_id)) {
            return $this->view('error_message', array(
                'message' => CONTENT_CLASSIFICATORS_ERR_EDITI_GUESTRIGHTS
            ));
        }

        $this->ui_config = new ui_config_classificator('custom', $this->classificator_id);
        $this->ui_config->actionButtons = array(
            array(
                "id" => "submit",
                "caption" => NETCAT_CUSTOM_ONCE_SAVE,
                "action" => "mainView.submitIframeForm()",
                "align" => "right"
            ),
            array(
                "id" => "back",
                "align" => "left",
                "caption" => CONTROL_AUTH_HTML_BACK,
                "location" => "classificator.custom(" . $this->classificator_id . ")"
            ),
        );
        $this->ui_config->locationHash = "#classificator.custom.manual(" . $this->classificator_id . ")";

        return $this->view('list/custom_manual', array(
            'custom_settings' => $list['CustomSettingsTemplate'],
        ));
    }

    protected function action_custom_save() {
        $nc_core = nc_Core::get_object();
        $data = $nc_core->input->fetch_post_get();

        $old_name = $data['param'];
        $new_name = trim($data['name']);

        if (!preg_match("/^[a-z0-9_]+$/i", $new_name)) {
            $error_message = NETCAT_CUSTOM_ONCE_ERROR_FIELD_NAME;
        }
        if (!$data['caption']) {
            $error_message = NETCAT_CUSTOM_ONCE_ERROR_CAPTION;
        }

        $custom_settings = $nc_core->list->get_by_id($data['classificator_id'], 'CustomSettingsTemplate');

        if (!$custom_settings) {
            $custom_settings = array();
        } else {
            $a2f = new nc_a2f($custom_settings, '');
            $custom_settings = $a2f->eval_value($custom_settings);
        }

        $keys = empty($custom_settings) ? array() : array_keys($custom_settings);

        if (in_array($new_name, $keys) && $new_name != $old_name) {
            $error_message = NETCAT_CUSTOM_ONCE_ERROR_FIELD_EXISTS;
        }

        if ($error_message) {
            if ($nc_core->input->fetch_get_post('param')) {
                return $this->action_custom_edit($error_message);
            }else{
                return $this->action_custom_add($error_message);
            }
        }

        $param = array('type' => $data['type'], 'subtype' => $data['subtype'], 'caption' => $data['caption']);
        if (strlen($data['default_value'])) {
            $param['default_value'] = $data['default_value'];
        }
        $param['skip_in_form'] = $data['skip_in_form'];

        // загружаем объект для работы с полем
        $classname = "nc_a2f_field_" . $data['type'] . ($data['subtype'] ? "_" . $data['subtype'] : "");
        if (!class_exists($classname)) {
            return false;
        }
        /** @var nc_a2f_field $fl */
        $fl = new $classname();
        $ex_params = $fl->get_extend_parameters();
        if (!empty($ex_params)) {
            foreach ($ex_params as $k => $v) {
                if ($data['type'] == 'select' && $data['subtype'] == 'static' && $k == 'values') {
                    $param['values'] = array();
                    if (!empty($data['select_static_key'])) {
                        foreach ($data['select_static_key'] as $i => $option_key) {
                            $option_key = trim($option_key);
                            $option_value = trim($data['select_static_value'][$i]);

                            if (!strlen($option_key) || !strlen($option_value)) {
                                continue;
                            }
                            $param['values'][$option_key] = $option_value;
                        }
                    }
                } else {
                    if (isset($data['cs_' . $k])) {
                        $param[$k] = $data['cs_' . $k];
                    }
                }
            }
        }

        $custom_settings_new = array();
        if (!empty($custom_settings)) {
            foreach ($custom_settings as $k => $v) {
                if ($k != $old_name && $k != $new_name) {
                    $custom_settings_new[$k] = $v;
                }
                if ($k == $old_name || $k == $new_name) {
                    $custom_settings_new[$new_name] = $param;
                }
            }
        }

        if (!$old_name) {
            $custom_settings_new[$new_name] = $param;
        }


        $s = nc_a2f::array_to_string($custom_settings_new);
        $s = $custom_settings_new ? '$settings_array = ' . $s . ';' : '';

        $nc_core->list->update($data['classificator_id'], array('CustomSettingsTemplate' => $s));

        $redirect = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.list&action=custom&saved=1&classificator_id=' . $data['classificator_id'];
        header("Location: " . $redirect);
        return null;
    }

    protected function action_custom_manual_save() {
        $nc_core = nc_Core::get_object();
        $this->ui_config = new ui_config_classificator('custom', $this->classificator_id);
        $this->ui_config->actionButtons = array(
            array(
                "id" => "submit",
                "caption" => NETCAT_CUSTOM_ONCE_SAVE,
                "action" => "mainView.submitIframeForm()",
                "align" => "right"
            ),
            array(
                "id" => "back",
                "align" => "left",
                "caption" => CONTROL_AUTH_HTML_BACK,
                "location" => "classificator.custom(" . $this->classificator_id . ")"
            ),
        );
        $this->ui_config->locationHash = "#list.custom.manual(" . $this->classificator_id . ")";

        $custom_settings = $nc_core->input->fetch_post('CustomSettingsTemplate');
        $nc_core->list->update($this->classificator_id, array('CustomSettingsTemplate' => $custom_settings));

        return $this->view('list/custom_manual', array(
            'saved' => true,
            'custom_settings' => $custom_settings,
        ));
    }

    protected function action_custom_drop() {
        $nc_core = nc_Core::get_object();
        $this->ui_config = new ui_config_classificator('custom', $this->classificator_id);

        $kill = $nc_core->input->fetch_post('kill');
        $custom_settings = $nc_core->list->get_by_id($this->classificator_id, 'CustomSettingsTemplate');

        if ($custom_settings && !empty($kill)) {
            $a2f = new nc_a2f($custom_settings, '');
            $custom_settings = $a2f->eval_value($custom_settings);

            foreach ($custom_settings as $k => $v) {
                if (in_array($k, $kill)) {
                    unset($custom_settings[$k]);
                }
            }

            $s = '';
            if (!empty($custom_settings)) {
                $s = '$settings_array = ' . nc_a2f::array_to_string($custom_settings) . ';';
            }

            $nc_core->list->update($this->classificator_id, array('CustomSettingsTemplate' => $s));

            $redirect = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.list&action=custom&saved=1&classificator_id=' . $this->classificator_id;
        }else{
            $redirect = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.list&action=custom&classificator_id=' . $this->classificator_id;
        }

        header("Location: " . $redirect);
    }

    protected function action_add_item() {
        global $perm;
        $nc_core = nc_Core::get_object();
        $list = $nc_core->list->get_by_id($this->classificator_id);

        $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADDELEMENT, $this->classificator_id, 0, 0);
        if ($list['System'] && !$perm->isDirectAccessClassificator(NC_PERM_ACTION_ADDELEMENT, $this->classificator_id)) {
            $this->use_layout = false;
            return $this->view('error_message', array(
                'header' => $list['Classificator_Name'],
                'message' => CONTENT_CLASSIFICATORS_ERR_EDITI_GUESTRIGHTS,
            ));
        }

        $max_priority = $nc_core->list->get_max_priority($this->classificator_id);
        $custom_settings = nc_a2f::evaluate($list['CustomSettingsTemplate']);

        return $this->view('list/add_item', array(
            'list' => $list,
            'max_priority' => $max_priority,
            'custom_settings' => $custom_settings
        ));
    }

    protected function action_edit_item() {
        global $perm;
        $nc_core = nc_Core::get_object();
        $list = $nc_core->list->get_by_id($this->classificator_id);

        $perm->ExitIfNotAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_ADDELEMENT, $this->classificator_id, 0, 0);
        if ($list['System'] && !$perm->isDirectAccessClassificator(NC_PERM_ACTION_ADDELEMENT, $this->classificator_id)) {
            $this->use_layout = false;
            return $this->view('error_message', array(
                'header' => $list['Classificator_Name'],
                'message' => CONTENT_CLASSIFICATORS_ERR_EDITI_GUESTRIGHTS,
            ));
        }

        $item_id = $nc_core->input->fetch_get('item_id');

        $list = $nc_core->list->get_by_id($this->classificator_id);
        $item = $nc_core->list->get_item($this->classificator_id, $item_id);

        $custom_settings = nc_a2f::evaluate($list['CustomSettingsTemplate']);

        return $this->view('list/edit_item', array(
            'list' => $list,
            'item' => $item,
            'custom_settings' => $custom_settings
        ));
    }

    protected function action_update_item() {
        $nc_core = nc_Core::get_object();
        $data = $nc_core->input->fetch_post();

        $properties = array(
            'ID' => $data['item_id'],
            'Name' => $data['list_item_name'],
            'Value' => $data['list_item_value'],
        );
        $settings = $nc_core->list->get_by_id($data['classificator_id'], 'CustomSettingsTemplate');

        if ($settings) {
            $a2f = new nc_a2f($settings, 'custom_settings');
            if (!$a2f->validate($data['custom_settings'])) {
                $error = $a2f->get_validation_errors();
                $this->use_layout = false;
                return $this->view('list/error_modal', array(
                    'error' => $error,
                ));
            }
            $a2f->save_from_request_data('custom_settings');

            $properties['CustomSettings'] = $a2f->get_values_as_string();
        }

        $nc_core->list->update_item($data['classificator_id'], $data['item_id'], $properties);
        exit;
    }

    protected function action_save_item() {
        $nc_core = nc_Core::get_object();
        $data = $nc_core->input->fetch_post();

        $properties = array(
            'Name' => $data['list_item_name'],
            'Priority' => $data['list_item_priority'],
            'Value' => $data['list_item_value']
        );
        $settings = $nc_core->list->get_by_id($data['classificator_id'], 'CustomSettingsTemplate');
        if ($settings) {
            $a2f = new nc_a2f($settings, 'custom_settings');
            if (!$a2f->validate($data['custom_settings'])) {
                $error = $a2f->get_validation_errors();
                $this->use_layout = false;
                return $this->view('list/error_modal', array(
                    'error' => $error,
                ));
            }
            $a2f->save_from_request_data('custom_settings');
            $properties['CustomSettings'] = $a2f->get_values_as_string();
        }
        $nc_core->list->create_item($data['classificator_id'], $properties);
        exit;
    }

    protected function after_action($result) {
        if (!$this->use_layout) {
            return $result;
        }
        BeginHtml();
        echo $result;
        EndHtml();
        return '';
    }


}