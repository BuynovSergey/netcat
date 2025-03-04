<?php

if (!class_exists("nc_System")) {
    die("Unable to load file.");
}

if (!isset($nc_core)) {
    $nc_core = nc_Core::get_object();
}

$action = $nc_core->action;

if (!isset($inside_admin)) {
    $inside_admin = null;
}
if (!isset($UI_CONFIG)) {
    $UI_CONFIG = null;
}
if ($inside_admin && $UI_CONFIG) {
    if ($action === "add") {
        $UI_CONFIG->locationHash = "object.add($cc)";
    } else {
        $UI_CONFIG->locationHash = "object.edit($classID,$message)";
    }
}

// если редактируем системные таблицы, функции нужны глобальные значения
if ($systemTableID) {
    $GLOBALS['fldCount'] = $fldCount;
    $GLOBALS['fldID'] = $fldID;
    $GLOBALS['fld'] = $fld;
    $GLOBALS['fldName'] = $fldName;
    $GLOBALS['fldValue'] = $fldValue;
    $GLOBALS['fldType'] = $fldType;
    $GLOBALS['fldFmt'] = $fldFmt;
    $GLOBALS['fldNotNull'] = $fldNotNull;
    $GLOBALS['fldInheritance'] = $fldInheritance;
    $GLOBALS['fldDefault'] = $fldDefault;
    $GLOBALS['fldTypeOfEdit'] = $fldTypeOfEdit;
    $GLOBALS['fldDoSearch'] = $fldDoSearch;
}

// получаем код формы
$result = nc_fields_form($action);

if (!isset($f_Checked)) {
    $f_Checked = null;
} 
if (!isset($f_Priority)) {
    $f_Priority = null;
} 
if (!isset($f_Keyword)) {
    $f_Keyword = null;
} 
if (!isset($f_ncTitle)) {
    $f_ncTitle = null;
} 
if (!isset($f_ncKeywords)) {
    $f_ncKeywords = null;
} 
if (!isset($f_ncDescription)) {
    $f_ncDescription = null;
} 
if (!isset($f_ncSMO_Title)) {
    $f_ncSMO_Title = null;
} 
if (!isset($f_ncSMO_Description)) {
    $f_ncSMO_Description = null;
} 
if (!isset($f_ncSMO_Image)) {
    $f_ncSMO_Image = null;
} 
if (!isset($f_ncDemoContent)) {
    $f_ncDemoContent = null;
} 
if (!isset($f_Parent_Message_ID)) {
    $f_Parent_Message_ID = null;
}

if ($result) {
    if (empty($nc_notmodal) && (!$systemTableID || $systemTableID == 3)) {
        $result = nc_prepare_message_form(
            $result,
            $action,
            $admin_mode,
            $systemTableID,
            $systemTableID,
            $current_cc,
            $f_Checked,
            $f_Priority,
            $f_Keyword,
            $f_ncTitle,
            $f_ncKeywords,
            $f_ncDescription,
            1,
            0,
            $f_ncSMO_Title,
            $f_ncSMO_Description,
            $f_ncSMO_Image,
            $f_ncDemoContent,
            isset($f_ncFullPageMode) ? $f_ncFullPageMode : 'inherit'
        );
    }
    if (!isset($classID)) {
        $classID = null;
    }
    eval(nc_check_eval("echo \"$result\";"));
}
