<?php

/* $Id: ui_config.php 6206 2012-02-10 10:12:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Класс для облегчения формирования UI в модулях
 */
class ui_config_module_cache extends ui_config_module {

    public function __construct($active_tab = 'admin', $toolbar_action = 'settings') {
        global $db;
        global $MODULE_FOLDER;

        parent::__construct('cache', $active_tab);

        if ($active_tab = 'admin') {

            $this->toolbar[] = array(
                    'id' => "settings",
                    'caption' => NETCAT_MODULE_CACHE_ADMIN_SETTINGS,
                    'location' => "module.cache.settings",
                    'group' => "cache"
            );
            $this->toolbar[] = array(
                    'id' => "info",
                    'caption' => NETCAT_MODULE_CACHE_ADMIN_INFO,
                    'location' => "module.cache.info",
                    'group' => "cache"
            );
            $this->toolbar[] = array(
                    'id' => "audit",
                    'caption' => NETCAT_MODULE_CACHE_ADMIN_AUDIT,
                    'location' => "module.cache.audit",
                    'group' => "cache"
            );

            if ($toolbar_action)
                    $this->locationHash = "module.cache.".$toolbar_action;
            $this->activeToolbarButtons[] = $toolbar_action;
        }
    }

}
?>