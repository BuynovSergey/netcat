<?php

/* $Id: export.inc.php 8594 2013-01-09 13:34:06Z lemonade $ */

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
        if ($Field == "Class_ID") continue;
        // component template export aborted!
        if ($Field == "ClassTemplate" && $SelectClass[$Field] != 0) {
            return false;
        }
        $Qry[] = "`".$Field."` = '".addcslashes($SelectClass[$Field], "\\'\r\n")."'";
    }

    $TempText.= "<class><![CDATA[INSERT INTO `Class` SET ".join(", ", $Qry).";]]></class>\n";
    // component templates
    $ClassTemplatesArr = $db->get_results("SELECT * FROM `Class` WHERE `ClassTemplate` = '".$ClassID."'", ARRAY_A);

    if (!empty($ClassTemplatesArr)) {
        $TempText.= "<templates>";
        $TempIds = "<tpl_ids>";
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

                if (!$File_Mode && strpos($row[$Field], '<![CDATA[')) {
                    $row[$Field] = str_replace('<![CDATA[', '%CDATA_START%', $row[$Field]);
                    $row[$Field] = str_replace(']]>', '%CDATA_END%', $row[$Field]);
                }

                $Qry[] = "`".$Field."` = '".addcslashes($row[$Field], "\\'\r\n")."'";
            }
            // template str
            $TempText.= "<template><![CDATA[INSERT INTO `Class` SET ".join(", ", $Qry).";]]></template>\n";
        }
        $TempText.= "</templates>";
        $TempIds .= "</tpl_ids>";
    }

    #Экспортируем данные из таблицы Field
    $classFields = $db->get_results("SELECT * FROM `Field` WHERE `Class_ID` = '".$ClassID."'");
    $db->query("SET SQL_QUOTE_SHOW_CREATE = 1");
    $temp_result = $db->get_row("SHOW CREATE TABLE `Message".$ClassID."`", ARRAY_N);
    $lastpos = strrpos($temp_result[1], ")");
    $CreateTable = nc_substr($temp_result[1], 0, $lastpos);
    $CreateTable = str_ireplace("CREATE TABLE `message".$ClassID."`", "CREATE TABLE `Message%INSERT_ID%`", $CreateTable);
    $CreateTable = str_ireplace(array("\r", "\n"), "", $CreateTable);
    $CreateTable .= ") ENGINE=MyISAM;\n";


    $TempText.= '<message_tbl>'.$CreateTable.'</message_tbl>';

    // Экспортируем данные из таблицы Field
    $classFields = $db->get_results("SELECT * FROM `Field` WHERE `Class_ID` = '".$ClassID."'");

    if (!empty($classFields)) {
        $insert = "<fields>";

        foreach ($classFields as $SelectField) {
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

            if ($SelectField->DefaultState !== '' && $SelectField->TypeOfData_ID != NC_FIELDTYPE_TEXT) {
                $alter.= " NOT NULL DEFAULT '".$SelectField->DefaultState."'";
            } elseif ($SelectField->NotNull) {
                $alter.= " NOT NULL";
            } else {
                $alter.= " NULL";
            }

            $insert.= "<field>INSERT INTO Field (`Class_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Priority`, `DoSearch`, `DefaultState`, `TypeOfEdit_ID`) VALUES";
            $insert.= " (%INSERT_ID%, '".str_replace("'", "\'", $SelectField->Field_Name)."', '".str_replace("'", "\'", $SelectField->Description)."', ".$SelectField->TypeOfData_ID.", '".str_replace("'", "\'", $SelectField->Format)."', ".$SelectField->NotNull.", ".$SelectField->Priority.", ".$SelectField->DoSearch.", '".str_replace("'", "\'", $SelectField->DefaultState)."', ".$SelectField->TypeOfEdit_ID.");</field>\n";
        }

        $insert .= "</fields>";
    }

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
        $tmp_file_name = $nc_core->TMP_FOLDER . "netcat_class_$ClassID.tgz";
        $dump_file = nc_tgz_create($tmp_file_name, $ClassID, $nc_core->HTTP_TEMPLATE_PATH . 'class/');
        $tar_contents = file_get_contents($tmp_file_name);
        $output .= "<tar_data>".base64_encode($tar_contents)."</tar_data>\n";
        unlink($tmp_file_name);
    }

    $output .= "</data>";

    // все компоненты в utf-8
    if (!$nc_core->NC_UNICODE) $ret = $nc_core->utf8->win2utf($ret);

    return $output;
}

?>
