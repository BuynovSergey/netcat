<?php

$_POST["NC_HTTP_REQUEST"] = true;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

if (!isset($sub_id) || !isset($template_id)) {
    trigger_error("Wrong params", E_USER_ERROR);
}

settype($catalogue_id, "integer");
settype($sub_id, "integer");
settype($parent_sub_id, "integer");
settype($template_id, "integer");

/** @var nc_core $nc_core */

$custom_settings = null;
$can_inherit = false;
$new_item = $sub_id == -1 || $catalogue_id == -1;

if ($new_item) {
    $can_inherit = !$catalogue_id;

    if ($template_id) {
        $custom_settings = $nc_core->template->get_custom_settings($template_id);
    }

    $own_values = null;
}
else {
    $source = null;
    if ($catalogue_id && !$sub_id) {
        $source = $nc_core->catalogue->get_by_id($catalogue_id);
    }
    else if ($sub_id) {
        $source = $nc_core->subdivision->get_by_id($sub_id);
        $can_inherit = true;
    }

    $custom_settings = $nc_core->template->get_custom_settings($template_id ?: $source['Template_ID']);
    $own_values = $source['TemplateSettings'];
}

if (!$custom_settings) {
    print CONTROL_TEMPLATE_CUSTOM_SETTINGS_NOT_AVAILABLE;
    exit;
}

$a2f = new nc_a2f($custom_settings, 'TemplateSettings', $can_inherit);
$a2f->set_initial_values();
$a2f->set_values($own_values);

if ($can_inherit && (!$new_item || $parent_sub_id)) {
    // Ссылка на вышестоящий раздел, у которого заданы настройки макета (или сайт)
    $inheritance_info = null;

    $parent_tree = (array)$nc_core->subdivision->get_parent_tree($new_item ? $parent_sub_id : $sub_id);

    $first = ($new_item ? 0 : 1);
    $last = count($parent_tree) - 1;

    for ($i = $first; $i <= $last; $i++) {
        if ($parent_tree[$i]['TemplateSettings'] || ($i == $last && $nc_core->template->get_by_id($parent_tree[$i]['Template_ID'], 'CustomSettings'))) {
            if ($i == $last) {
                $inheritance_info = sprintf(
                    CONTROL_TEMPLATE_CUSTOM_SETTINGS_INHERITED_FROM_SITE,
                    $nc_core->ADMIN_PATH . '#catalogue.design(' . $parent_tree[$i]['Catalogue_ID'] . ')'
                );
            }
            else {
                $inheritance_info = sprintf(
                    CONTROL_TEMPLATE_CUSTOM_SETTINGS_INHERITED_FROM_FOLDER,
                    $nc_core->ADMIN_PATH . '#subdivision.design(' . $parent_tree[$i]['Subdivision_ID'] . ')',
                    $parent_tree[$i]['Subdivision_Name']
                );
                break;
            }
        }
    }

    if ($inheritance_info) {
        nc_print_status($inheritance_info, 'info');
    }

    // родительские значения = значения по умолчанию для этого раздела
    if ($last == $first) { $defaults = $nc_core->catalogue->get_template_settings($parent_tree[$first]['Catalogue_ID']); }
                    else { $defaults = $nc_core->subdivision->get_template_settings($parent_tree[$first]['Subdivision_ID']); }

    if ($defaults) {
        $a2f->set_default_values($defaults);
    }
}


// this is only for inside_admin mode
$vs_template_header = "<table class='admin_table' style='width: 100%;'><tr><th>%CAPTION</th><th>%VALUE</th><th>%DEFAULT</th></tr>";
$vs_template_object = "<tr><td>%CAPTION&nbsp</td><td>%VALUE&nbsp</td><td><em class='nc-text-grey'>%DEFAULT&nbsp</em></td></tr>";
$vs_template_footer = "</table>";
$vs_template_divider = "<tr><th colspan='3'><strong>%CAPTION</strong></th></tr>";

print $a2f->render($vs_template_header, $vs_template_object, $vs_template_footer, $vs_template_divider);
