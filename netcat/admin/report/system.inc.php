<?php

function AddSystemMessage($Message, $Description) {
    global $db;

    $nc_core = nc_Core::get_object();

    if (!$nc_core->NC_UNICODE) {
        $Message = $nc_core->utf8->utf2win($Message);
    }

    $db->query("INSERT INTO `SystemMessage`
		(`Message`, `Description`)
		VALUES
		('".$db->escape($Message)."', '".$db->escape($Description)."')");

    return $db->insert_id;
}
?>