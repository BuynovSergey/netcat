<?php

/* $Id: ui.php 5946 2012-01-17 10:44:36Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Базовый класс для облегчения формирования UI в модулях
 */
class ui_config_module extends ui_config {

    public function __construct($module_keyword, $active_tab = 'admin', $tree_node = null) {
        global $nc_core;
        global $MODULE_FOLDER, $ADMIN_TEMPLATE_FOLDER;

        $module_data = $nc_core->modules->get_by_keyword($module_keyword, false, false);

        // структура для простейшего модуля
        $this->headerText = constant($module_data['Module_Name']);

        if (file_exists(nc_module_folder($module_data['Keyword']) . 'admin.php')) {
            $this->tabs[] = array(
                    'id' => 'admin',
                    'caption' => constant($module_data['Module_Name']),
                    'location' => "module.".$module_data['Keyword']
            );
        }

        $this->tabs[] = array(
                'id' => 'settings',
                'caption' => TOOLS_MODULES_MOD_PREFS,
                'location' => "module.settings(".$module_data['Keyword'].")"
        );

        $this->activeTab = $active_tab;

        switch ($active_tab) {
            case 'admin':
                $this->locationHash = "module.".$module_data['Keyword'];
                break;
            case 'settings':
                $this->locationHash = "module.settings(".$module_data['Keyword'].")";
                break;
        }

        $this->treeMode = "modules";
        $this->treeSelectedNode = $tree_node ? $tree_node : "module-" . $module_data['Module_ID'];
    }

}
