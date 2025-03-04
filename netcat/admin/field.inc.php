<?php

/* $Id: field.inc.php 7754 2012-07-23 12:03:41Z olegafx $ */

function GetFieldName($FieldID) {
    global $db;
    return $db->get_var("select Field_Name from Field where Field_ID='".intval($FieldID)."'");
}

function GetClassIDByFieldID($FieldID) {
    global $db;
    return $db->get_var("select Class_ID from Field where Field_ID='".intval($FieldID)."'");
}

function GetSystemTableIDByFieldID($FieldID) {
    global $db;
    return $db->get_var("select System_Table_ID from Field where Field_ID='".intval($FieldID)."'");
}

function GetFieldsByClassId($ClassID) {
    $arr = array();
    $result = (array) nc_Core::get_object()->db->get_results("SELECT Field_ID, Field_Name, Description FROM Field WHERE Class_ID=".intval($ClassID), ARRAY_A);
    foreach($result as $ff) {
        $arr[] = "f_".$ff['Field_Name'];
    }
    return join(" ", $arr);
}

function getCompletionDataForClassFields($ClassID) {
    $fields = (array) nc_Core::get_object()->db->get_results("SELECT Field_ID, Field_Name, Description FROM Field WHERE Class_ID=".intval($ClassID), ARRAY_A);
    $result = array();

    if (!$fields) {
        return '';
    }

    foreach ($fields as $f) {
        $result [] = array(
                'completion' => array(
                        'name' => 'f_'.$f['Field_Name'],
                        'value' => 'f_'.$f['Field_Name'],
                        'help' => $f['Description'].'.'
                ),
                'type' => 'variable',
                'areas' => array('ClassForm')
        );
    }
    return $result;
}

function getCompletionDataForClassCustomSettings($ClassID) {
    $fields = nc_Core::get_object()->db->get_var("SELECT `CustomSettingsTemplate` FROM `Class` WHERE `Class_ID` = '".(int) $ClassID."'");
    $result = array();
    
    if(!$fields) {
            return '';
    }
        
    eval('$fields = '.$fields);
    foreach ($fields as $k => $v) {
        $result [] = array(
                'completion' => array(
                        'name' => $k,
                        'value' => $k,
                        'help' => $v['caption'].'.'
                ),
                'type' => 'variable',
                'areas' => array('ClassForm')
        );
    }
    
    return $result;
}

function getCompletionDataForTemplateFields($systemTableID) {
	$fields = (array) nc_Core::get_object()->db->get_results("SELECT Field_ID, Field_Name, Description FROM Field WHERE System_Table_ID=".intval($systemTableID), ARRAY_A);
	$result = array();
        
        if(!$fields) {
            return '';
        }
        
	foreach ($fields as $f) {
		$result []= array(
			'completion' => array(
				'name' => $f['Field_Name'],
				'value' => $f['Field_Name'],
				'help' => $f['Description'].'.'
			),
			'type' => 'macros',
			'areas' => array('TemplateForm')
		);
	}
	return $result;
}