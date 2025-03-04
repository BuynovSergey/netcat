<?php

/* $Id: ExportToFile.php 7983 2012-08-17 09:34:36Z lemonade $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

$template_id = intval($_GET['TemplateID']);

require ($ADMIN_FOLDER."function.inc.php");

if (!$nc_core->token->verify()) {
    BeginHtml("", "");
    nc_print_status(NETCAT_TOKEN_INVALID, 'error');
    EndHtml();
    exit;
}

// Выдача файла с шаблоном при экспорте
header("Content-type: text/xml");
header("Content-Disposition: attachment; filename=NetCat_".$template_id."_template.xml");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
header("Pragma: public");

require ($ADMIN_FOLDER."template/function.inc.php");

echo CascadeExportTemplate($TemplateID);

function CascadeExportTemplate($TemplateID) {
    // system superior object
    $nc_core = nc_Core::get_object();
    include ($nc_core->DOCUMENT_ROOT.$nc_core->ADMIN_PATH."tar.inc.php");

    $db = &$nc_core->db;

    $VersionNumber = $nc_core->get_settings("VersionNumber");
    $SystemID = $nc_core->get_settings("SystemID");
    $LastPatch = $nc_core->get_settings("LastPatch");
    $TemplateID = intval($TemplateID);

    // Блокируем таблицы
    $LockTables = "LOCK TABLES `Template` WRITE";

    $LockResult = $db->query($LockTables);

    // Экспортируем данные из таблицы Template
    $SelectTemplate = $db->get_row("SELECT * FROM `Template` WHERE `Template_ID` = '".$TemplateID."'", ARRAY_A);
    $File_Mode = $SelectTemplate['File_Mode'];

    $FieldsForExport = array_keys($SelectTemplate);

    $Qry = array();
    foreach ($FieldsForExport as $Field) {
        if ($Field == "Template_ID") {
            continue;
        }
        if ($Field == "Description") {
            $ptpl_desc = $SelectTemplate[$Field];
        }
        // component template export aborted!
        $Qry[] = "`".$Field."` = '".addcslashes($SelectTemplate[$Field], "\\'\r\n")."'";
    }

    $TemplatesArr = $db->get_results("SELECT * FROM `Template` WHERE `File_Path` LIKE '".$SelectTemplate['File_Path']."%'", ARRAY_A);

    if (!empty($TemplatesArr)) {
        $TempText.= "<templates>";
        $Path = "";
        foreach ($TemplatesArr as $row) {
            $path = substr($row['File_Path'], 1, -1);
            $current_id = $row["Template_ID"];
            $current_desc = $row["Description"];
            $Path .= "<path>{$row["File_Path"]}</path>";
            $level = count(explode('/', $path));
            $tplChildren = implode(',',$nc_core->template->get_childs($current_id));
            $Qry = array();
            foreach ($FieldsForExport as $Field) {
                // skip ID
                if ($Field == "Template_ID") {                    
                    continue;
                }
                // set `Parent_Template_ID`
                if ($Field == "Parent_Template_ID") {
                    $Qry[] = "`Parent_Template_ID` = '%INSERT_ID%'";
                    $parent = $row[$Field];
                    continue;
                }
                $Qry[] = "`".$Field."` = '".addcslashes($row[$Field], "\\'\r\n")."'";
            }
            
            if ($current_id == $TemplateID) {
                $parent = 0;
            }
            
            // template str
            $TempText.= "<template id='".$current_id."' level='".$level."' parent='".$parent."' desc='".$current_desc."' children='".$tplChildren."'><![CDATA[INSERT INTO `Template` SET ".join(", ", $Qry).";]]></template>\n";
        }
        $TempText.= "</templates>";
    }

    $db->query("UNLOCK TABLES");

    list($SystemName, $SystemColor) = nc_system_name_by_id($SystemID);

    $export_id_str = "-- Netcat ".$VersionNumber." ".$SystemName." [".$LastPatch."] component file, generated ".date("Y-m-d H:i:s");
    
    $output = "<?xml version=\"1.0\"?>
<data>
    <version>$VersionNumber</version>
    <export_id>$export_id_str</export_id>
    <template_id>$TemplateID</template_id>";
    
    if (isset($TempIds)) {
       $output .= $TempIds; 
    }
    
$output .= "<sql>
        $TempText</sql>\n";

    if ($File_Mode) {
        $tmp_file_name = $nc_core->TMP_FOLDER . "netcat_template_$TemplateID.tgz";
        $dump_file = @nc_tgz_create($tmp_file_name, substr($SelectTemplate['File_Path'], 1, -1), $nc_core->HTTP_TEMPLATE_PATH . 'template/');
        $tar_contents = @file_get_contents($tmp_file_name);
        
        if ($dump_file && $tar_contents) {
            $output .= "<tar>".base64_encode($tar_contents)."</tar>\n";
            unlink($tmp_file_name);
        }
    }

    $output .= "</data>";
    
    // все компоненты в utf-8
    if (!$nc_core->NC_UNICODE) $ret = $nc_core->utf8->win2utf($ret);

    return $output;
}


function getChildTemplates($tpl_id, &$res) {
    $res += $db->get_results("SELECT * FROM `Template` WHERE `Parent_Template_ID` = '".$tpl_id."'", ARRAY_A);
    
    foreach ($res as $tpl) {
        if ($res = $db->get_results("SELECT * FROM `Template` WHERE `Parent_Template_ID` = '".$tpl."'", ARRAY_A)) {
            getChildTemplates($tpl);
        }
    }
}
?>
