<?php

/* $Id: system_table.inc.php 7302 2012-06-25 21:12:35Z alive $ */

function GetSystemTableName($SystemTableID) {
    global $db;
    return $db->get_var("select System_Table_Name from System_Table where System_Table_ID='".intval($SystemTableID)."'");
}

function GetSystemTableRusName($SystemTableID) {
    global $db;
    if (!$SystemTableID) return false;
    return constant($db->get_var("select System_Table_Rus_Name from System_Table where System_Table_ID='".intval($SystemTableID)."'"));
}

function GetSystemTableID($SystemTableName) {
    global $db;
    return $db->get_var("select System_Table_ID from System_Table where System_Table_Name='".$db->escape($SystemTableName)."'");
}