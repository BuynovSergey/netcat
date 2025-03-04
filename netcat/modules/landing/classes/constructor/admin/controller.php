<?php

class nc_landing_constructor_admin_controller extends nc_landing_admin_controller {

    protected $use_layout = false;

    /**
     *
     */
    protected function init() {
        parent::init();
        $this->bind('show_independent_landing_create_dialog', array('site_id'));
        $this->bind('show_object_landing_create_dialog', array('site_id', 'component_id', 'object_id'));
        $this->bind('show_object_landing_list_dialog', array('site_id', 'component_id', 'object_id'));
        $this->bind('show_preset_settings_save_dialog', array('subdivision_id'));
    }

    /**
     * @return nc_ui_view
     */
    protected function action_index() {
        return $this->action_show_landing_create();
    }

    /**
     * @return nc_ui_view
     */
    protected function action_show_landing_create() {
        $this->use_layout = true;
        $this->ui_config = new nc_landing_admin_ui();
        $this->ui_config->subheaderText = NETCAT_MODULE_LANDING_CREATE_LANDING_HEADER;

        if (!$this->site_id) {
            return $this->view('error_message')->with('message', CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_NONE);
        }
        
        $this->ui_config->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_LANDING_CREATE_LANDING_BUTTON,
            "action" => "mainView.submitIframeForm()"
        );
        
        $landing = nc_landing::get_instance($this->site_id);
        $presets = $landing->get_presets()->where('can_be_used_independently', true);
        $user_presets = $landing->get_user_presets()->where('can_be_used_independently', true);

        return $this->view('landing_create_admin_page', array(
            'presets' => $presets,
            'user_presets' => $user_presets,
            'created_page_url' => nc_core::get_object()->input->fetch_get_post('created_page_url'),
        ));
    }

   /**
     * @param $site_id
     * @return nc_ui_view
     */
    protected function action_show_independent_landing_create_dialog($site_id) {
        $landing = nc_landing::get_instance($this->site_id);

        $presets = $landing->get_presets()->where('can_be_used_independently', true);
        $user_presets = $landing->get_user_presets()->where('can_be_used_independently', true);

        return $this->view('landing_create_dialog', array(
            'site_id' => (int)$site_id,
            'component_id' => null,
            'object_id' => null,
            'has_existing_landing_pages' => false,
            'object_landing_list_dialog_url' => null,
            'presets' => $presets,
            'user_presets' => $user_presets,
        ));
    }

    /**
     * @param $site_id
     * @param $component_id
     * @param $object_id
     * @return nc_ui_view
     */
    protected function action_show_object_landing_create_dialog($site_id, $component_id, $object_id) {
        $site_id = (int)$site_id;
        $component_id = (int)$component_id;
        $object_id = (int)$object_id;

        $landing = nc_landing::get_instance($this->site_id);

        $presets = $landing->get_presets();
        $user_presets = $landing->get_user_presets();
        $has_existing_landing_pages = false;
        if ($component_id && $object_id) {
            $has_existing_landing_pages = count($landing->get_landing_subdivision_ids_for_object($component_id, $object_id));
            $presets = $presets->for_component($component_id);
            $user_presets = $user_presets->for_component($component_id);
        }

        return $this->view('landing_create_dialog', array(
            'site_id' => $site_id,
            'component_id' => $component_id,
            'object_id' => $object_id,
            'has_existing_landing_pages' => $has_existing_landing_pages,
            'object_landing_list_dialog_url' => $landing->get_object_landing_list_dialog_url($component_id, $object_id),
            'presets' => $presets,
            'user_presets' => $user_presets,
        ));
    }

    /**
     * @param $site_id
     * @param $component_id
     * @param $object_id
     * @return nc_ui_view
     * @throws Exception
     */
    protected function action_show_object_landing_list_dialog($site_id, $component_id, $object_id) {
        $site_id = (int)$site_id;
        $component_id = (int)$component_id;
        $object_id = (int)$object_id;

        $nc_core = nc_core::get_object();
        $landing = nc_landing::get_instance($this->site_id);

        $existing_landing_pages = array();
        $existing_landing_subdivisions_id = $landing->get_landing_subdivision_ids_for_object($component_id, $object_id);
        foreach ($existing_landing_subdivisions_id as $subdivision_id) {
            $subdivision = $nc_core->subdivision->get_by_id($subdivision_id);
            $existing_landing_pages[] = array(
                'name' => $subdivision['Subdivision_Name'],
                'href' => nc_folder_url($subdivision_id),
            );
        }

        return $this->view('landing_list_dialog', array(
            'site_id' => $site_id,
            'component_id' => $component_id,
            'object_id' => $object_id,
            'existing_landing_pages' => $existing_landing_pages,
            'object_landing_create_dialog_url' => $landing->get_object_landing_create_dialog_url($component_id, $object_id),
        ));
    }

    /**
     *
     */
    protected function action_create_landing() {
        $input = nc_core::get_object()->input;
        $preset_keyword = $input->fetch_post_text('preset_keyword');
        $site_id = (int)$input->fetch_post('site_id');
        $component_id = (int)$input->fetch_post('component_id');
        $object_id = (int)$input->fetch_post('object_id');
        $response_type = $input->fetch_post('response_type');

        try {
            $landing = nc_landing::get_instance($site_id);
            $landing->install_all_resources();

            $preset = $landing->get_preset($preset_keyword);
            if (!$preset_keyword) {
                throw new nc_landing_resource_exception(NETCAT_MODULE_LANDING_MISSING_PRESET . ": " . htmlspecialchars($preset_keyword));
            }

            $subdivision_id = $preset->create_landing_page(array(
                'site_id' => $site_id,
                'component_id' => $component_id,
                'object_id' => $object_id,
            ));

            if (!$subdivision_id) {
                throw new nc_landing_preset_exception("No ID was returned from the preset::create_landing()");
            }

            $nc_core = nc_core::get_object();
            $domain = $nc_core->catalogue->get_by_id($site_id, 'Domain');

            $landing_url = ($domain ? '//' . $domain : '') . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . '?sub=' . $subdivision_id;

            if ($response_type === 'json') {
                return nc_array_json(array('url' => $landing_url));
            }
            else {
                $this->redirect_to_index_action('index', 'created_page_url=' . urlencode($landing_url));
            }
        }
        catch (Exception $e) {
            $message = $e->getMessage();
            if ($response_type === 'json') {
                return nc_array_json(array('error' => $message));
            }
            else {
                return $this->view('error_message')->with('message', $message);
            }
        }
    }

    /**
     * @param $subdivision_id
     * @return nc_ui_view
     */
    protected function action_show_preset_settings_save_dialog($subdivision_id) {
        $subdivision_id = (int)$subdivision_id;
        $landing = nc_landing::get_instance($this->site_id);
        return $this->view('preset_settings_save_dialog', array(
            'landing_page_id' => $landing->get_landing_page_id_by_subdivision_id($subdivision_id),
            'page_path' => nc_folder_path($subdivision_id)
        ));
    }

    /**
     *
     */
    protected function action_save_preset_settings() {
        $input = nc_core::get_object()->input;
        $landing_page_id = (int)$input->fetch_post('landing_page_id');
        $name = $input->fetch_post_text('name');
        $description = $input->fetch_post_text('description');
        $screenshot = $input->fetch_post('screenshot');
        $screenshot_thumbnail = $input->fetch_post('screenshot_thumbnail');

        $landing = nc_landing::get_instance($this->site_id);
        $settings_id = $landing->save_landing_page_settings($landing_page_id, $name, $description);
        $landing->save_landing_page_settings_screenshot($settings_id, nc_landing::PRESET_SCREENSHOT, $screenshot);
        $landing->save_landing_page_settings_screenshot($settings_id, nc_landing::PRESET_SCREENSHOT_THUMBNAIL, $screenshot_thumbnail);
    }

}