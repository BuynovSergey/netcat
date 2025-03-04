<?php
if (!class_exists('nc_core')) {
    die;
}

if ($saved) {
    nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
}

echo "<form action='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "action.php?ctrl=admin.list&action=custom_manual_save&classificator_id=" . $classificator_id . "' method='post' >";
echo nc_admin_textarea(NETCAT_CUSTOM_USETTINGS, 'CustomSettingsTemplate', $custom_settings, 1, 0, "height:286px;");
echo nc_admin_js_resize();
echo "</form>";
