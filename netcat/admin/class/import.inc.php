<?php
/* $Id: import.inc.php 8613 2013-01-15 14:37:56Z lemonade $ */

if (!class_exists("nc_System"))
    die("Unable to load file.");

// Форма выбора файла для импорта
function AddClassForm() {
    global $UI_CONFIG;

    $nc_core = nc_Core::get_object();
    echo "<form enctype='multipart/form-data' action='import.php' method='post'>
		<fieldset>
		<legend>" . CONTROL_CLASS_IMPORT . "</legend>
		<input size='40' name='FilePatch' type='file' />
		</fieldset><br/>
		<input type='hidden' name='phase' value='2'/>
		<input type='hidden' name='fs' value='".+$_REQUEST['fs']."'/>
		<input type='submit' class='hidden'/>
		" . $nc_core->token->get_input() . "
		</form>";


    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => CONTROL_CLASS_IMPORT_UPLOAD,
        "action" => "mainView.submitIframeForm()"
    );
}

/**
 * Парсинг файла с импортированным шаблоном
 *
 * @param str file
 *
 * @return int component id
 */
function ParseClassFile4($file) {
    // system superior object
    $nc_core = nc_Core::get_object();

    $db = &$nc_core->db;
    $db->captured_errors = array();
    $VersionNumber = $nc_core->get_settings("VersionNumber");

    $insert_flag = "%INSERT_ID%";
    $f = fopen($file, "r");

    // STEP 1: System version check
    $tpl_version = trim(fgets($f, 256));

    if (!nc_version_control($tpl_version)) {
        nc_print_status(sprintf(CONTROL_CLASS_IMPORT_ERROR_VERSION_ID, $tpl_version, $VersionNumber), 'info');
        return false;
    }

    // STEP 2: skip export info
    fgets($f, 256);
    
    $path_for_file_mode = array();
    
    // STEP 3: import component data
    $insert_into_class = trim(fgets($f, 65536));
    // все компоненты в utf-8
    if (!$nc_core->NC_UNICODE)
        $insert_into_class = $nc_core->utf8->utf2win($insert_into_class);

    $res1 = $db->query($insert_into_class);

    if ($res1) {
        $insert_id = $db->insert_id;
        $path_for_file_mode[$insert_id] = "/$insert_id/";
    } else {
        nc_print_status(CONTROL_CLASS_IMPORT_ERROR_CLASS_IMPORT, 'info');
        return false;
    }

    // STEP 4: import component templates
    $tpl_id = array();
    $next_data = trim(fgets($f, 65536));
    while (preg_match("/^INSERT INTO/is", $next_data)) {
        // все компоненты в utf-8
        if (!$nc_core->NC_UNICODE)
            $next_data = $nc_core->utf8->utf2win($next_data);
        // replace ID
        $next_data = str_replace($insert_flag, $insert_id, $next_data);
        
        if (!$db->query($next_data)) {
            nc_print_status(CONTROL_CLASS_IMPORT_ERROR_CLASS_TEMPLATE_IMPORT, 'info');
            $db->query("DELETE FROM `Class` WHERE `Class_ID` = '" . $insert_id . "'");
            $nc_core->component->delete_compatible_components_cache_by_id($insert_id);
            return false;
        }
        $path_for_file_mode[$db->insert_id] = "/$insert_id/$db->insert_id/";
        $tpl_id[] = $db->insert_id;
        $next_data = trim(fgets($f, 65536));
    }

    foreach ($path_for_file_mode as $id => $path) {
        $SQL = "UPDATE `Class` 
                    SET `File_Path` = '$path' 
                        WHERE `Class_ID` = $id";
        $db->query($SQL);        
    } 
    
    // STEP 5: create MessageXX table
    $create_message = $next_data;
    $create_message = str_replace($insert_flag, $insert_id, $create_message);
    $create_message = str_replace('%%MYSQL_CHARSET%%', $nc_core->MYSQL_CHARSET, $create_message);

    $res2 = $db->query($create_message);

    if ($res2 != 0) {
        nc_print_status(CONTROL_CLASS_IMPORT_ERROR_MESSAGE_TABLE, 'info');
        $db->query("DELETE FROM `Class` WHERE `Class_ID` = '" . $insert_id . "' OR `ClassTemplate` = '" . $insert_id . "'");
        $nc_core->component->delete_compatible_components_cache_by_id($insert_id);
        return false;
    }

    // STEP 6: append component fields
    while (!feof($f) && ($insert_into_field = preg_replace("/FileMode.*/m", '', trim(fgets($f, 4096))))) {
        if (!$nc_core->NC_UNICODE) {
            $insert_into_field = $nc_core->utf8->utf2win($insert_into_field);
        }
        $insert_into_field = str_replace($insert_flag, $insert_id, $insert_into_field);

        if ($insert_into_field) {
            $res3 = $db->query($insert_into_field);

            if ($db->captured_errors) {
                nc_print_status(CONTROL_CLASS_IMPORT_ERROR_FIELD, 'info');
                $db->query("DELETE FROM `Class` WHERE `Class_ID` = '" . $insert_id . "' OR `ClassTemplate` = '" . $insert_id . "'");
                $db->query("DROP TABLE `Message" . $insert_id . "`");
                $db->query("DELETE FROM `Field` WHERE `Class_ID` = '" . $insert_id . "'");
                $nc_core->component->delete_compatible_components_cache_by_id($insert_id);
                return false;
            }
        }
    }
    $import_all_text = file_get_contents($file);
    $serialize_field = str_replace('FileMode ', '', strstr($import_all_text, 'FileMode'));
    
    if ($serialize_field) {
        $serialize_field = explode('___class_templates___', $serialize_field);
        
        $fields = unserialize($serialize_field[0]);
        $_POST = array_merge($_POST, $fields);
        $file_class = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db, 'Class');
        $file_class->save_new_class($insert_id);
        
        if($serialize_field[1]) {
            $serialize_remplates = explode('___class_templates_separator___', $serialize_field[1]);
            $file_class->load($insert_id);
            for ($i = 0, $count = count($serialize_remplates); $i < $count; ++$i) {
                $fields = unserialize($serialize_remplates[$i]);
                $_POST = array_merge($_POST, $fields);
                $file_class->save_new_class($tpl_id[$i]);
            }
        }
    }

    return $insert_id;
}

function ParseClassFile($file) {
    // system superior object
    $nc_core = nc_Core::get_object();
    include_once ($nc_core->DOCUMENT_ROOT.$nc_core->ADMIN_PATH."tar.inc.php");

    $db = &$nc_core->db;
    $db->captured_errors = array();
    $VersionNumber = $nc_core->get_settings("VersionNumber");
    $fs = $nc_core->input->fetch_get_post('fs');

    $insert_flag = "%INSERT_ID%";

    $xml2array = xml2array(file_get_contents($file));
    $xml_data = $xml2array['data'];

    // STEP 1: System version check
    $tpl_version = $xml_data['version'];

    if (!$tpl_version) {
        nc_print_status(CONTROL_CLASS_IMPORT_ERROR_NO_VERSION_ID, 'info');
        return false;
    }
    
    if (!nc_version_control($tpl_version)) {
        nc_print_status(sprintf(CONTROL_CLASS_IMPORT_ERROR_VERSION_ID, $tpl_version, $VersionNumber), 'info');
        return false;
    }
    
    if ($fs && !isset($xml_data['tar_data'])) {
        nc_print_status(CONTROL_CLASS_IMPORT_ERROR_NO_FILES, 'info');
        return false;
    }
    
    $path_for_file_mode = array();

    $sql_data = $xml_data['sql_data'];

    $class_query = $sql_data['class'];
    if (!$nc_core->NC_UNICODE)
        $query = $nc_core->utf8->utf2win($class_query);

    $res1 = $db->query($class_query);
    if ($res1) {
        $insert_id = $db->insert_id;
        $path_for_file_mode[$insert_id] = "/$insert_id/";
    } else {
        nc_print_status(CONTROL_CLASS_IMPORT_ERROR_CLASS_IMPORT, 'info');
        return false;
    }

    $new_tpl_ids = array();    
    $xmlTemplates = $sql_data['templates']['template'];
    if (isset($xmlTemplates['value'])) $templates[0] = $xmlTemplates;
    else $templates = $xmlTemplates;
	
    if ( !empty($templates) )
    foreach ($templates as $query) {
        if (!$nc_core->NC_UNICODE)
            $query = $nc_core->utf8->utf2win($query);
        // replace ID
        $query = str_replace($insert_flag, $insert_id, $query);
                                
        if (!$fs && strpos($query, '%CDATA_START%')) {
            $query = str_replace('%CDATA_START%', '<![CDATA[', $query);
            $query = str_replace('%CDATA_END%', ']]>', $query);
        }
   
        if (!$db->query($query)) {
            nc_print_status(CONTROL_CLASS_IMPORT_ERROR_CLASS_TEMPLATE_IMPORT, 'info');
            $db->query("DELETE FROM `Class` WHERE `Class_ID` = '" . $insert_id . "'");
            $nc_core->component->delete_compatible_components_cache_by_id($insert_id);
            return false;
        }
        $path_for_file_mode[$db->insert_id] = "/$insert_id/$db->insert_id/";
        $new_tpl_ids[] = $db->insert_id;
    }

    foreach ($path_for_file_mode as $id => $path) {
        $SQL = "UPDATE `Class` 
                    SET `File_Path` = '$path' 
                        WHERE `Class_ID` = $id";
        $db->query($SQL);  
    } 
    
    // STEP 5: create MessageXX table
    $create_message = $sql_data['message_tbl'];
    $create_message = str_replace($insert_flag, $insert_id, $create_message);
    $create_message = str_replace('%%MYSQL_CHARSET%%', $nc_core->MYSQL_CHARSET, $create_message);

    $res2 = $db->query($create_message);

    if ($res2 != 0) {
        nc_print_status(CONTROL_CLASS_IMPORT_ERROR_MESSAGE_TABLE, 'info');
        $db->query("DELETE FROM `Class` WHERE `Class_ID` = '" . $insert_id . "' OR `ClassTemplate` = '" . $insert_id . "'");
        $nc_core->component->delete_compatible_components_cache_by_id($insert_id);
        return false;
    }
    $xmlFields = $sql_data['fields']['field'];
    if (!is_array($xmlFields)) $xmlFields = (array)$xmlFields;
    foreach ($xmlFields as $query) {
        if (!$nc_core->NC_UNICODE) {
            $query = $nc_core->utf8->utf2win($query);
        }
        $query = str_replace($insert_flag, $insert_id, $query);

        if ($query) {
            $res3 = $db->query($query);

            if ($db->captured_errors) {
                nc_print_status(CONTROL_CLASS_IMPORT_ERROR_FIELD, 'info');
                $db->query("DELETE FROM `Class` WHERE `Class_ID` = '" . $insert_id . "' OR `ClassTemplate` = '" . $insert_id . "'");
                $db->query("DROP TABLE `Message" . $insert_id . "`");
                $db->query("DELETE FROM `Field` WHERE `Class_ID` = '" . $insert_id . "'");
                $nc_core->component->delete_compatible_components_cache_by_id($insert_id);
                return false;
            }
        }
    }

    if (isset($xml_data['tar_data'])) {
        $tar = $xml_data['tar_data'];
        $tar_name = $nc_core->TMP_FOLDER.'import_class_'.$insert_id.'_'.mktime().'.tgz';
        file_put_contents($tar_name, base64_decode(trim($tar)));
        nc_tgz_extract($tar_name, $nc_core->TMP_FOLDER);

        rename($nc_core->TMP_FOLDER.$xml_data['class_id'], $nc_core->TMP_FOLDER.$insert_id);
        $xmlTplIds = $xml_data['tpl_ids']['tpl_id'];
        if (!is_array($xmlTplIds)) $xmlTplIds = (array)$xmlTplIds;
        foreach ($xmlTplIds as $i => $tpl_id) {
            rename($nc_core->TMP_FOLDER.$insert_id.'/'.$tpl_id, $nc_core->TMP_FOLDER.$insert_id.'/'.$new_tpl_ids[$i]);
        }
        rename($nc_core->TMP_FOLDER.$insert_id, $nc_core->CLASS_TEMPLATE_FOLDER.$insert_id);
        unlink($tar_name);
    }
    
    return $insert_id;
}

function ParseClassFileFs($file) {
    return ParseClassFile($file);
}

function xml2array($contents, $get_attributes = 1, $priority = 'tag') {
    if (!function_exists('xml_parser_create')) {
        return array();
    }

    $parser = xml_parser_create('');

    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);

    if (!$xml_values) return; //Hmm...

    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();
    $current = & $xml_array;
    $repeated_tag_index = array();

    foreach ($xml_values as $data) {
        unset($attributes, $value);
        extract($data);
        $result = array();
        $attributes_data = array();
        if (isset($value)) {
            if ($priority == 'tag') $result = $value;
            else $result['value'] = $value;
        }
        if (isset($attributes) and $get_attributes) {
            foreach ($attributes as $attr => $val) {
                if ($priority == 'tag') $attributes_data[$attr] = $val;
                else
                        $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'

            }
        }
        if ($type == "open") {
            $parent[$level - 1] = & $current;
            if (!is_array($current) or (!in_array($tag, array_keys($current)))) {
                $current[$tag] = $result;
                if ($attributes_data)
                        $current[$tag.'_attr'] = $attributes_data;
                $repeated_tag_index[$tag.'_'.$level] = 1;
                $current = & $current[$tag];
            }
            else {
                if (isset($current[$tag][0])) {
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    $repeated_tag_index[$tag.'_'.$level]++;
                } else {
                    $current[$tag] = array(
                            $current[$tag],
                            $result
                    );
                    $repeated_tag_index[$tag.'_'.$level] = 2;
                    if (isset($current[$tag.'_attr'])) {
                        $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                        unset($current[$tag.'_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag.'_'.$level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        } elseif ($type == "complete") {
            if (!isset($current[$tag])) {
                $current[$tag] = $result;
                $repeated_tag_index[$tag.'_'.$level] = 1;
                if ($priority == 'tag' and $attributes_data)
                        $current[$tag.'_attr'] = $attributes_data;
            }
            else {
                if (isset($current[$tag][0]) and is_array($current[$tag])) {
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level].'_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag.'_'.$level]++;
                } else {
                    $current[$tag] = array(
                            $current[$tag],
                            $result
                    );
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if ($priority == 'tag' and $get_attributes) {
                        if (isset($current[$tag.'_attr'])) {
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }
                        if ($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level].'_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                }
            }
        } elseif ($type == 'close') {
            $current = & $parent[$level - 1];
        }
    }
    return ($xml_array);
}


class ui_config_class_import extends ui_config {

    public function __construct($active_tab) {

        $this->headerText = CONTROL_CLASS_IMPORT;

        $this->tabs = array(
            array(
                'id' => 'import',
                'caption' => CONTROL_CLASS_IMPORT,
                'location' => "dataclass.import()"
            )
        );

        $this->activeTab = $active_tab;

        $this->locationHash = "#dataclass." . $active_tab . "()";
        $this->treeSelectedNode = "dataclass.list";

        $this->treeMode = 'developer';
    }

}

?>