<?php

class nc_subdivision_controller extends nc_ui_controller {

    protected $use_layout = true;


    protected function init() {
        $nc_core = nc_Core::get_object();
        require_once $nc_core->ADMIN_FOLDER . "subdivision/ui.php";
        if ($nc_core->input->fetch_get('isNaked')) {
            $this->use_layout = false;
        }
    }

    protected function action_show_versions() {
        $nc_core = nc_Core::get_object();
        $subdivision_id = (int)$nc_core->input->fetch_get('subdivision_id');
        $entity = $nc_core->input->fetch_get('entity');
        if (!$entity) {
            $entity = 'subdivision';
        }

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $subdivision_id);

        $this->ui_config = new ui_config_subdivision_versions($subdivision_id, $entity);

        // если был восстановлен раздел, то надо обновить узел в дереве сайта
        if ($nc_core->input->fetch_get('restored')  && $entity == 'subdivision') {
            $subdivision_data = new ui_subdivision_data();
            $subdivision_data->fetch_by_subdivision_id($subdivision_id);
            $subclasses = array();
            foreach ((array) $subdivision_data->get_moderated_subclasses() as $sc) {
                $subclasses[] = array("classId" => $sc["Class_ID"], "subclassId" => $sc["Sub_Class_ID"]);
            }

            $this->ui_config->treeChanges['updateNode'][] = array("nodeId" => "sub-" . $subdivision_id,
                "name"       => $subdivision_id . "." . $subdivision_data->subdivision_name,
                "image"      => "icon_folder",
                "toggleIcon" => $subdivision_data->subdivision_checked,
                "sprite"     => "folder" . ($subdivision_data->subdivision_checked ? "" : " nc--dark") . ($subdivision_data->label_color ? " nc--badge-" . $subdivision_data->label_color : ''),
                "checked"    => $subdivision_data->subdivision_checked,
                "subclasses" => $subclasses);
        }

        $versions = nc_record_collection::load(
            'nc_version_record',
            "SELECT * FROM Version WHERE `Entity` = '" . $nc_core->db->escape($entity) . "' AND `Subdivision_ID` = " . $subdivision_id . " ORDER BY `Version_ID` DESC"
        );

        $entity_name_constant = 'NETCAT_VERSION_ENTITY_' . strtoupper($entity);
        $entity_name = defined($entity_name_constant) ? constant($entity_name_constant) : '';

        return $this->view('version/subdivision', array(
            'versions' => $versions,
            'entity_name' => $entity_name,
        ));
    }

    protected function action_restore() {
        $nc_core = nc_Core::get_object();
        $subdivision_id = (int)$nc_core->input->fetch_get('subdivision_id');
        $entity = $nc_core->input->fetch_get('entity');
        $id = (int)$nc_core->input->fetch_get('id');

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $subdivision_id);

        $redirect_params = array(
            'ctrl' => 'admin.subdivision',
            'action' => 'show_versions',
            'subdivision_id' => $subdivision_id,
            'entity' => $entity,
        );

        try {
            $version = $nc_core->version->load_by_id($id);
        } catch (nc_record_exception $e) {
            return  $this->view('error_message', array(
                'message' => 'NETCAT_VERSION_' . nc_strtoupper($entity) . '_RESTORE_ERROR_PARENT'
            ));
        }

        if ($version->restore()) {
            $redirect_params['restored'] = 1;
        } else {
            $redirect_params['error'] = 1;
        }
        header("Location: " . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?' . http_build_query($redirect_params, null, '&'));
        return null;
    }

    protected function action_show_diff() {
        $this->use_layout = false;

        $nc_core = nc_core::get_object();
        $version_id = $nc_core->input->fetch_get('version_id');
        if (!$version_id) {
            throw new InvalidArgumentException;
        }

        $version = $nc_core->version->load_by_id($version_id);
        $subdivision_id = $version->get('Subdivision_ID');

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $subdivision_id);

        return $this->view('version/diff', array(
            'version' => $version,
        ));

    }

    protected function action_redirect_to_preview() {
        $nc_core = nc_Core::get_object();
        $subdivision_id = (int)$nc_core->input->fetch_get('subdivision_id');

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_VIEW, $subdivision_id);

        header("Location: " . nc_folder_url($subdivision_id));
        return null;
    }

    protected function action_redirect_to_object_list() {
        $nc_core = nc_Core::get_object();
        $subdivision_id = (int)$nc_core->input->fetch_get('subdivision_id');

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $subdivision_id);

        $sub_class_id = (int)$nc_core->db->get_var(
            "SELECT `Sub_Class_ID`
             FROM `Sub_Class`
             WHERE `Subdivision_ID` = '$subdivision_id'
             AND `Class_ID` > 0
             ORDER BY `Priority`"
        );

        if ($sub_class_id) {
            header("Location: {$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}?inside_admin=1&cc=$sub_class_id");
        } else {
            header("Location: {$nc_core->ADMIN_PATH}subdivision/SubClass.php?phase=1&SubdivisionID=$subdivision_id");
        }

        return null;
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