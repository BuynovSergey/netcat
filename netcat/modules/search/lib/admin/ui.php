<?php

/**
 *
 */
class nc_search_admin_ui extends ui_config {

    public $headerText = NETCAT_MODULE_SEARCH_TITLE;

    /**
     *
     */
    public function __construct($view, $params) {
        $has_indexing_access = nc_search_permission::check('indexing');
        $has_settings_access = nc_search_permission::check('settings');
        $has_queries_access = nc_search_permission::check('queries');
        $has_broken_links_access = nc_search_permission::check('brokenlinks');

        $this->tabs = array(
            array(
                'id' => 'info',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_INFO,
                'location' => 'module.search.info',
            ),
        );

        if ($has_indexing_access) {
            $this->tabs[] = array(
                'id' => 'indexing',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_INDEXING,
                'location' => 'module.search.indexing',
            );
        }

        if ($has_settings_access || $has_queries_access || $has_broken_links_access || $has_indexing_access) {
            $lists_location = 'module.search.';
            if ($has_queries_access) {
                $lists_location .= 'queries';
            } elseif ($has_broken_links_access) {
                $lists_location .= 'brokenlinks';
            } elseif ($has_settings_access) {
                $lists_location .= 'synonyms';
            } elseif ($has_indexing_access) {
                $lists_location .= 'events';
            }

            $this->tabs[] = array(
                'id' => 'lists',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_LISTS,
                'location' => $lists_location,
            );
        }

        if ($has_settings_access || nc_search_permission::check('rules_edit')) {
            $this->tabs[] = array(
                'id' => 'settings',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_SETTINGS,
                'location' => $has_settings_access ? 'module.search.generalsettings' : 'module.search.rules',
            );
        }

        $this->activeTab = $view;
        $this->locationHash = "module.search.$view" . ($params ? "($params)" : "");
        $this->treeMode = "modules";
        $this->toolbar = array();

        $nc_core = nc_Core::get_object();
        $module_settings = $nc_core->modules->get_by_keyword('search');
        $this->treeSelectedNode = "module-$module_settings[Module_ID]";
    }

    /**
     *
     */
    public function add_lists_toolbar() {
        if (nc_search_permission::check('queries')) {
            $this->toolbar[] = array(
                'id' => 'queries',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_QUERIES,
                'location' => 'module.search.queries',
                'group' => 'admin',
            );
        }

        if (nc_search_permission::check('brokenlinks')) {
            $this->toolbar[] = array(
                'id' => 'brokenlinks',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_BROKENLINKS,
                'location' => 'module.search.brokenlinks',
                'group' => 'admin',
            );
        }

        $has_settings_access = nc_search_permission::check('settings');

        if ($has_settings_access) {
            $this->toolbar[] = array(
                'id' => 'synonyms',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS,
                'location' => 'module.search.synonyms',
                'group' => 'admin',
            );
            $this->toolbar[] = array(
                'id' => 'stopwords',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_STOPWORDS,
                'location' => 'module.search.stopwords',
                'group' => 'admin',
            );
        }

        if (nc_search_permission::check('events')) {
            $this->toolbar[] = array(
                'id' => 'events',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG,
                'location' => 'module.search.events',
                'group' => 'admin',
            );
        }

        list($active_button) = explode('_', $this->activeTab);
        $this->activeToolbarButtons[] = $active_button;
        $this->activeTab = 'lists';
    }

    /**
     *
     */
    public function add_settings_toolbar() {
        $has_all_settings_access = nc_search_permission::check('settings');
        $this->toolbar = array();

        if ($has_all_settings_access) {
            $this->toolbar[] = array(
                'id' => 'generalsettings',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_GENERAL_SETTINGS,
                'location' => 'module.search.generalsettings',
                'group' => 'admin',
            );
            $this->toolbar[] = array(
                'id' => 'templates',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_VIEW_SETTINGS,
                'location' => 'module.search.templates',
                'group' => 'admin',
            );
            $this->toolbar[] = array(
                'id' => 'fields',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_FIELDS,
                'location' => 'module.search.fields',
                'group' => 'admin',
            );
        }

        if (nc_search_permission::check('rules_edit')) {
            $this->toolbar[] = array(
                'id' => 'rules',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_RULES_SETTINGS,
                'location' => 'module.search.rules',
                'group' => 'admin',
            );
        }

        if ($has_all_settings_access) {
            $this->toolbar[] = array(
                'id' => 'extensions',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSIONS,
                'location' => 'module.search.extensions',
                'group' => 'admin',
            );
            $this->toolbar[] = array(
                'id' => 'systemsettings',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_SYSTEM_SETTINGS,
                'location' => 'module.search.systemsettings',
                'group' => 'admin',
            );
        }

        list($active_button) = explode('_', $this->activeTab);
        $this->activeToolbarButtons[] = $active_button;
        $this->activeTab = 'settings';
    }

    /**
     *
     */
    public function add_submit_button($caption = NETCAT_MODULE_NETSHOP_BUTTON_SAVE) {
        $this->actionButtons[] = array("id" => "submit_form",
            "caption" => $caption,
            "action" => "mainView.submitIframeForm()");
    }

    /**
     *
     */
    public function add_back_button($caption = NETCAT_MODULE_SEARCH_ADMIN_BACK) {
        $this->actionButtons[] = array("id" => "history_back",
            "caption" => $caption,
            "action" => "history.back(1)",
            "align" => "left");
    }

    /**
     *
     */
    public function add_location_parameters($param_string) {
        if (strpos($this->locationHash, "(")) {
            $this->locationHash = substr($this->locationHash, -1) . "$param_string)";
        } else {
            $this->locationHash .= "($param_string)";
        }
    }

}
