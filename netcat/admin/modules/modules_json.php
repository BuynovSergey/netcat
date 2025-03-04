<?php

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;

$NETCAT_FOLDER = realpath(dirname(__FILE__) . '/../../..') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . "vars.inc.php";
require $ADMIN_FOLDER . "function.inc.php";

/** @var Permission $perm */
// Показываем дерево разработчика, если у пользователя есть на это права
if (!$perm->hasAnyModulePermission()) {
    exit(NETCAT_MODERATION_ERROR_NORIGHT);
}

//--------------------------------------------------------------------------

$nc_core = nc_core::get_object();

@list($node_type, $node_id) = explode("-", $node);
$module_list = $nc_core->modules->get_data();


switch ($node_type) {

    case 'root':
        if ($perm->isSupervisor()) {
            // widgets
            $ret_modules[] = array(
                "nodeId" => "widgets",
                "name" => WIDGETS,
                "href" => "#widgets",
                "sprite" => "mod-widgets",
                "hasChildren" => false,
                "dragEnabled" => false
            );
        }

        foreach ($module_list as $module) {
            $module_keyword = $module['Keyword'];
            if (!$perm->hasAnyModulePermission($module_keyword)) {
                continue;
            }

            $module_path = nc_module_folder($module_keyword);

            if (file_exists($module_path . MAIN_LANG . '.lang.php')) {
                require_once $module_path . MAIN_LANG . '.lang.php';
            } else {
                require_once $module_path . 'en.lang.php';
            }

            $custom_location = $nc_core->modules->get_vars($module_keyword, 'ADMIN_SETTINGS_LOCATION');

            $ret_modules[] = array(
                "nodeId"      => "module-{$module['Module_ID']}",
                "name"        => constant($module["Module_Name"]),
                "href"        => file_exists($module_path . 'admin.php') && $module['Checked'] ? "#module.{$module_keyword}" : "#module.settings({$module_keyword})",
                "sprite"      => "mod-{$module_keyword}",
                "hasChildren" => file_exists($module_path . 'admin_tree.php'),
                "dragEnabled" => false,
                "buttons"     => array(
                    array(
                        "image" => "i_settings.gif",
                        "label" => TOOLS_MODULES_MOD_PREFS,
                        "href"  => $custom_location ? $custom_location : "module.settings({$module_keyword})"
                    ),
                ),
            );
        }

        $ret = array_reverse($ret_modules);
        print "while(1);" . nc_array_json($ret_modules);
        break;

    default:
        if ($node_type == 'module') {
            foreach ($module_list as $module) {
                if ($module['Module_ID'] == $node_id) {
                    break;
                }
                $module = false;
            }
        }
        // пробуем найти $node_type == $module_keyword
        else {
            foreach ($module_list as $module) {
                if ($module['Keyword'] == $node_type) {
                    break;
                }
                $module = false;
            }
        }

        if ( ! $module) {
            return;
        }

        $module_keyword = $module['Keyword'];
        $module_path    = nc_module_folder($module_keyword);

        if (file_exists($module_path . MAIN_LANG . '.lang.php')) {
            require_once $module_path . MAIN_LANG . '.lang.php';
        } else {
            require_once $module_path . 'en.lang.php';
        }

        if (file_exists($module_path . 'admin_tree.php')) {
            require $module_path . 'admin_tree.php';
        }
        break;
}
