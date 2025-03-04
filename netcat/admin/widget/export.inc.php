<?php

/* $Id: export.inc.php 8384 2012-11-09 10:11:12Z vadim $ */

// Экспорт шаблона
function CascadeExportClass($ClassID) {
    // system superior object
    $nc_core = nc_Core::get_object();
    include ($nc_core->DOCUMENT_ROOT.$nc_core->ADMIN_PATH."tar.inc.php");

    $db = &$nc_core->db;

    $VersionNumber = $nc_core->get_settings("VersionNumber");
    $SystemID = $nc_core->get_settings("SystemID");
    $LastPatch = $nc_core->get_settings("LastPatch");
    $ClassID = intval($ClassID);

    // Блокируем таблицы
    $LockTables = "LOCK TABLES `Class` WRITE, `Field` WRITE, ";
    $LockTables.= "`Message".$ClassID."` WRITE, ";
    $LockTables.= "`Sub_Class` WRITE";

    $LockResult = $db->query($LockTables);

    // Экспортируем данные из таблицы Class
    $SelectClass = $db->get_row("SELECT * FROM `Class` WHERE `Class_ID` = '".$ClassID."'", ARRAY_A);

    $File_Mode = $SelectClass['File_Mode'];

    $FieldsForExport = array_keys($SelectClass);

    $Qry = array();
    foreach ($FieldsForExport as $Field) {
        // skip ID
        if ($Field == "Class_ID") continue;
        // component template export aborted!
        if ($Field == "ClassTemplate" && $SelectClass[$Field] != 0) {
            return false;
        }
        $Qry[] = "`".$Field."` = '".addcslashes($SelectClass[$Field], "\\'\r\n")."'";
    }

    $TempText.= "<class>INSERT INTO `Class` SET ".join(", ", $Qry).";</class>\n";

    // component templates
    $ClassTemplatesArr = $db->get_results("SELECT * FROM `Class` WHERE `ClassTemplate` = '".$ClassID."'", ARRAY_A);

    if (!empty($ClassTemplatesArr)) {
        $TempText.= "<templates>";
        $TempIds .= "<tpl_ids>";
        foreach ($ClassTemplatesArr as $row) {
            $Qry = array();
            foreach ($FieldsForExport as $Field) {
                // skip ID
                if ($Field == "Class_ID") {
                    $TempIds .= "<tpl_id>$row[$Field]</tpl_id>";
                    continue;
                }
                // set `ClassTemplate`
                if ($Field == "ClassTemplate") {
                    $Qry[] = "`ClassTemplate` = '%INSERT_ID%'";
                    continue;
                }
                $Qry[] = "`".$Field."` = '".addcslashes($row[$Field], "\\'\r\n")."'";
            }
            // template str
            $TempText.= htmlspecialchars("INSERT INTO `Class` SET ".join(", ", $Qry).";\n");
        }
        $TempText.= "</templates>";
        $TempIds .= "</tpl_ids>";
    }

    #Экспортируем данные из таблицы Field
    $classFields = $db->get_results("SELECT * FROM `Field` WHERE `Class_ID` = '".$ClassID."'");

    $TempText.= "CREATE TABLE Message%INSERT_ID% (".
            "`Message_ID` int(11) NOT NULL auto_increment, ".
            "`User_ID` int(11) NOT NULL, ".
            "`Subdivision_ID` int(11) NOT NULL, ".
            "`Sub_Class_ID` int(11) NOT NULL, ".
            "`Priority` int(11) NOT NULL default '0', ".
            "`Keyword` char(255) NOT NULL, ".
            "`Checked` tinyint(4) NOT NULL default '1', ".
            "`IP` char(15) default NULL, ".
            "`UserAgent` char(255) default NULL, ".
            "`Parent_Message_ID` int(11) NOT NULL default '0', ".
            "`Created` datetime NOT NULL, ".
            "`LastUpdated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, ".
            "`LastUser_ID` int(11) NOT NULL, ".
            "`LastIP` char(15) default NULL, ".
            "`LastUserAgent` char(255) default NULL, ";
    $insert = "";

    if (!empty($classFields)) {
        $insert = "<fields>";
        foreach ($classFields as $SelectField) {
            $TempText.= "`".$SelectField->Field_Name."`";
            //определяем тип данных
            $alter = " ";

            switch ($SelectField->TypeOfData_ID) {
                case NC_FIELDTYPE_STRING:
                    $alter .= 'CHAR(255)';
                    break;
                case NC_FIELDTYPE_INT:
                    $alter .= 'INT';
                    break;
                case NC_FIELDTYPE_TEXT:
                    $alter .= 'LONGTEXT';
                    break;
                case NC_FIELDTYPE_SELECT:
                    $alter .= 'INT';
                    break;
                case NC_FIELDTYPE_BOOLEAN:
                    $alter .= 'TINYINT';
                    break;
                case NC_FIELDTYPE_FILE:
                    $alter .= 'TEXT';
                    break;
                case NC_FIELDTYPE_FLOAT:
                    $alter .= 'DOUBLE';
                    break;
                case NC_FIELDTYPE_DATETIME:
                    $alter .= 'DATETIME';
                    break;
                case NC_FIELDTYPE_RELATION:
                    $alter .= 'INT';
                    break;
                case NC_FIELDTYPE_MULTISELECT:
                    $alter .= 'TEXT';
                    break;
                case NC_FIELDTYPE_MULTIFILE:
                    $alter .= 'CHAR(255)';
                    break;
            }

            if ($SelectField->DefaultState !== "" && $SelectField->TypeOfData_ID != NC_FIELDTYPE_TEXT) {
                $alter.= " NOT NULL DEFAULT '".$SelectField->DefaultState."'";
            } elseif ($SelectField->NotNull) {
                $alter.= " NOT NULL";
            } else {
                $alter.= " NULL";
            }

            $TempText.= $alter.", ";
            $insert.= "<field>INSERT INTO Field (`Class_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Priority`, `DoSearch`, `DefaultState`, `TypeOfEdit_ID`) VALUES";
            $insert.= " (%INSERT_ID%, '".str_replace("'", "\'", $SelectField->Field_Name)."', '".str_replace("'", "\'", $SelectField->Description)."', ".$SelectField->TypeOfData_ID.", '".str_replace("'", "\'", $SelectField->Format)."', ".$SelectField->NotNull.", ".$SelectField->Priority.", ".$SelectField->DoSearch.", '".str_replace("'", "\'", $SelectField->DefaultState)."', ".$SelectField->TypeOfEdit_ID.");</field>\n";
        }
        $insert = "</fields>";
    }

    $TempText.= "PRIMARY KEY (`Message_ID`), ";
    $TempText.= "UNIQUE KEY `Sub_Class_ID` (`Sub_Class_ID`,`Message_ID`,`Keyword`), ";
    $TempText.= "KEY `User_ID` (`User_ID`), ";
    $TempText.= "KEY `LastUser_ID` (`LastUser_ID`), ";
    $TempText.= "KEY `Subdivision_ID` (`Subdivision_ID`), ";
    $TempText.= "KEY `Parent_Message_ID` (`Parent_Message_ID`) ";
    $TempText.= ") TYPE=MyISAM;\n";
    $TempText.= $insert;

    $db->query("UNLOCK TABLES");

    list($SystemName, $SystemColor) = nc_system_name_by_id($SystemID);

    $export_id_str = "-- Netcat ".$VersionNumber." ".$SystemName." [".$LastPatch."] component file, generated ".date("Y-m-d H:i:s");

    $output = "<?xml version=\"1.0\"?>
<data>
    <version>$VersionNumber</version>
    <export_id>$export_id_str</export_id>
    <class_id>$ClassID</class_id>";

    if (isset($TempIds)) {
       $output .= $TempIds;
    }

$output .= "<sql_data>
        $TempText</sql_data>\n";

    if ($File_Mode) {
        $tmp_file_name = $nc_core->TMP_FOLDER . "netcat_widget_$ClassID.tgz";
        $dump_file = nc_tgz_create($tmp_file_name, $ClassID, $nc_core->HTTP_TEMPLATE_PATH . 'widget/');
        $tar_contents = file_get_contents($tmp_file_name);

        $output .= "<tar_data>".base64_encode($tar_contents)."</tar_data>\n";
    }

    $output .= "</data>";

    // все компоненты в utf-8
    if (!$nc_core->NC_UNICODE) $ret = $nc_core->utf8->win2utf($ret);

    return $ret;
}
?>
