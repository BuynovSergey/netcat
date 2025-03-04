<?php

/**
 * Класс для облегчения формирования UI в модулях
 */
class ui_config_module_stats extends ui_config_module {

    public $headerText = NETCAT_MODULE_STATS;

    public function __construct($view, $sub_view, $phase) {

        $this->tabs[] = array(
                'id' => "openstat",
                'caption' => NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT,
                'location' => "module.stats.openstat"
        );
        $this->tabs[] = array(
                'id' => "nc_stat",
                'caption' => NETCAT_MODULE_STATS_ADMIN_TAB_NC_STAT,
                'location' => "module.stats.nc_stat"
        );
        $this->tabs[] = array(
                'id' => "analytics",
                'caption' => NETCAT_MODULE_STATS_ADMIN_TAB_ANALYTICS,
                'location' => "module.stats.analytics"
        );
        $this->tabs[] = array(
                'id' => "settings",
                'caption' => STRUCTURE_TAB_SETTINGS,
                'location' => "module.stats.settings"
        );

        $this->activeTab = $view;
        $this->locationHash = "module.stats.".$view.($sub_view ? ".".$sub_view : "").($phase ? "(".$phase.")" : "");
        $this->treeMode = "modules";

        $module_settings = nc_Core::get_object()->modules->get_by_keyword('stats');
        $this->treeSelectedNode = "module-".$module_settings['Module_ID'];
    }

    public function add_openstat_toolbar($sub_view) {
        $this->toolbar[] = array(
                'id' => "reports",
                'caption' => NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_TOOLBAR_REPORT,
                'location' => "module.stats.openstat.reports",
                'group' => "openstat"
        );
        $this->toolbar[] = array(
                'id' => "counters",
                'caption' => NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_TOOLBAR_COUNTERS,
                'location' => "module.stats.openstat.counters",
                'group' => "openstat"
        );
        $this->toolbar[] = array(
                'id' => "templates",
                'caption' => NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT_TOOLBAR_TEMPLATES,
                'location' => "module.stats.openstat.templates",
                'group' => "openstat"
        );

        $this->activeToolbarButtons[] = $sub_view;
        $this->activeTab = 'openstat';
    }

}
