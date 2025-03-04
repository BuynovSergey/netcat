<?php
const NETCAT_MODULE_MESSAGING_NAME = "messaging";

$MESSAGING_DIRECTORY = __DIR__;

require_once "$MESSAGING_DIRECTORY/nc_messaging.class.php";
nc_core::get_object()->register_class_autoload_path("nc_messaging_", $MESSAGING_DIRECTORY . "/classes");


/***
 * @param array $prefix_meta
 * @param array|null $service
 * @param string|null $key
 *
 * @return string
 */
function nc_messaging_render_number_prefix_option(array $prefix_meta, array $service = null, $key = null) {
    $selected_attribute = $service && $key && isset($service["settings"][$key][$prefix_meta["countryCode"]]) ?
        "selected" : "";

    return "<option name='{$prefix_meta["countryCode"]}' value='{$prefix_meta["code"]}' $selected_attribute>{$prefix_meta["code"]}  {$prefix_meta["name"]}</option>";
}
