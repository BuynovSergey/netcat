<?php

class nc_landing_subdivision_admin_controller extends nc_landing_admin_controller {

    /**
     * @param $site_id
     */
    public function set_site_id($site_id) {
        $this->site_id = (int)$site_id;
    }

    /**
     *
     */
    public function action_show_subdivision_list() {
        $nc_core = nc_core::get_object();
        $this->use_layout = true;

        $landing = nc_landing::get_instance($this->site_id);
        $landing_create_dialog_url = $landing->get_independent_landing_create_dialog_url();

        $this->ui_config = new ui_config_objects($landing->get_landings_list_infoblock_id());
        $this->ui_config->tabs = array();
        $this->ui_config->actionButtons = array(
            array(
                'id' => 'create_landing',
                'caption' => NETCAT_MODULE_LANDING_CONSTRUCTOR_BUTTON_TITLE,
                'action' => "mainViewIframe.window.nc_landing_open_create_dialog()",
                'align' => 'left',
            )
        );

        $subdivision_ids = $nc_core->db->get_col(
            "SELECT `Subdivision_ID`
               FROM `Landing_Page`
              WHERE `Site_ID` = $this->site_id"
        );

        $subdivision_data = array();
        foreach ($subdivision_ids as $subdivision_id) {
            try {
                $subdivision_data[] = array(
                    'id' => $subdivision_id,
                    'name' => $nc_core->subdivision->get_by_id($subdivision_id, 'Subdivision_Name'),
                    'path' => nc_folder_path($subdivision_id),
                    'url' => nc_folder_url($subdivision_id),
                );
            }
            catch (Exception $e) {}
        }

        return $this->view('list', array(
            'pages' => $subdivision_data,
            'landing_create_dialog_url' => $landing_create_dialog_url,
        ));
    }

}