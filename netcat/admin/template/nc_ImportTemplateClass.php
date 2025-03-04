<?php
class nc_ImportTemplate {
    private $ncCore;
    private $xmlData;
    private $filename;

    public function __construct() {
        $this->ncCore = nc_Core::get_object();
        include_once ($this->ncCore->DOCUMENT_ROOT.$this->ncCore->ADMIN_PATH."tar.inc.php");

        $this->idFlag = "%INSERT_ID%";
        $this->db = &$this->ncCore->db;
        $this->db->captured_errors = array();
    }

    public function parseXmlFile($xmlFileName) {
        $this->filename = $xmlFileName;
        $xml2array = $this->xml2array(file_get_contents($this->filename), true, 'attr');

        if (!$xml2array || empty($xml2array) || !isset($xml2array['data'])) {
            return false;
        }

        if (!isset($xml2array['data']['template_id'])) {
            return false;
        }

        $this->xmlData = $xml2array['data'];
        return true;
    }

    /* STEP 1: Import form */
    public function showImportForm(&$UI_CONFIG, $fs) {
      echo "
      <form enctype='multipart/form-data' action='import.php' method='post'>
      <fieldset>
            <legend>" . CONTROL_TEMPLATE_IMPORT . "</legend>
            <input size='40' name='FilePatch' type='file' />
      </fieldset><br/>
      <input type='hidden' name='phase' value='2'/>
      <input type='hidden' name='fs' value='".$fs."'/>
      <input type='submit' class='hidden'/>
      " . $this->ncCore->token->get_input() . "
      </form>";


        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => CONTROL_TEMPLATE_IMPORT_UPLOAD,
            "action" => "mainView.submitIframeForm()"
        );
    }

    /* STEP 2: Templates listing */
    public function showTplList(&$UI_CONFIG, $fs) {
        $tplData = $this->getTplData();

        echo "<p>".CONTROL_TEMPLATE_IMPORT_SELECT."</p>";
        echo "<form id='adminForm' class='nc-form' method='post'>";
        $this->printTplByRow($tplData, 0);
        echo "
        <input type='hidden' name='phase' value='3'/>
        <input type='hidden' name='fs' value='".$fs."'/>
        <input type='hidden' name='filename' value='".$this->filename."'/>
        <input type='submit' class='hidden'/>
        " . $this->ncCore->token->get_input() . "
        </form>";

        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => CONTROL_TEMPLATE_IMPORT_CONTINUE,
            "action" => "mainView.submitIframeForm()"
        );
    }

    /* STEP 3: Upload data & copy files */
    public function uploadData($level) {
        $db->captured_errors = array();
        $VersionNumber = $this->ncCore->get_settings("VersionNumber");

// STEP 3.1: System version check
        $tpl_version = $this->xmlData['version']['value'];
        $tpl_root_id = $this->xmlData['template_id']['value'];

        if (!nc_version_control($tpl_version)) {
            return array('e' => sprintf(CONTROL_CLASS_IMPORT_ERROR_VERSION_ID, $tpl_version, $VersionNumber));
        }

// STEP 3.2: Upload templates
        $tplData = $this->getTplData();
        $handleData = array();
        $relations = array();
        $children = false;

        foreach ($tplData as $id => $item) {
            if ($id == $level) {
                $handleData[$id] = $item;
                $children = $item['children'];
            }
        }

        if (false != $children) {
            $children_array = explode(',', $children);

            foreach ($tplData as $id => $item) {
                if (in_array($id, $children_array)) {
                    $handleData[$id] = $item;
                }
            }
        }

        $result = $this->insertTplByRow($handleData, $relations, $level, 0);

        if (!empty($db->captured_errors)) {
            return array('e' => CONTROL_TEMPLATE_IMPORT_ERROR_SQL);
        } else if (!$result) {
            return array('e' => CONTROL_TEMPLATE_IMPORT_ERROR_NOTUPLOADED);
        }

        $parent_template = $relations[$level];

// STEP 3.3: Unpack, rename and copy files
        if (isset($this->xmlData['tar'])) {
            $tar = $this->xmlData['tar']['value'];
            $tar_name = $this->ncCore->TMP_FOLDER.'netcat_template_'.$tpl_root_id.'.tgz';
            $put_result = @file_put_contents($tar_name, base64_decode(trim($tar)));
            $extract_result = @nc_tgz_extract($tar_name, $this->ncCore->TMP_FOLDER);

            if (!$put_result || !$extract_result) {
                return array('e' => sprintf(CONTROL_TEMPLATE_IMPORT_ERROR_EXTRACT, $tar_name, $this->ncCore->TMP_FOLDER));
            }

            foreach ($relations as $old_id => $new_id) {
                $parent_path = $this->ncCore->template->get_parent($old_id, true);
                $parent_path = array_reverse($parent_path);
                $ready_path = implode('/', $parent_path);
                $ready_path = str_replace(array_keys($relations), array_values($relations), $ready_path);

                if (!is_dir($this->ncCore->TMP_FOLDER.$ready_path.'/'.$old_id)) {
                    continue;
                }

                $rename_result = rename($this->ncCore->TMP_FOLDER.$ready_path.'/'.$old_id, $this->ncCore->TMP_FOLDER.$ready_path.'/'.$new_id);

                if (!$rename_result) {
                    return array('e' => sprintf(CONTROL_TEMPLATE_IMPORT_ERROR_MOVE, $this->ncCore->TMP_FOLDER.$ready_path.'/'.$old_id, $this->ncCore->TMP_FOLDER.$ready_path.'/'.$new_id));
                }
            }

            $parent_path = $this->ncCore->template->get_parent($level, true);
            $parent_path = array_reverse($parent_path);
            $ready_path = implode('/', $parent_path);

            $rename_result = rename($this->ncCore->TMP_FOLDER.$ready_path.'/'.$parent_template, $this->ncCore->TEMPLATE_FOLDER.$parent_template);

            if (!$rename_result) {
                return array('e' => sprintf(CONTROL_TEMPLATE_IMPORT_ERROR_MOVE, $this->ncCore->TMP_FOLDER.$ready_path.'/'.$parent_template, $this->ncCore->TEMPLATE_FOLDER.$parent_template));
            }

            @unlink($tar_name);
        }

        if (is_dir($this->ncCore->TMP_FOLDER.$tpl_root_id))
            @unlink($this->ncCore->TMP_FOLDER.$tpl_root_id);

        @unlink($this->ncCore->TMP_FOLDER.$this->filename);
        return $parent_template;
    }

    /* Recursive helper for step 2 */
    private function printTplByRow(&$data, $node_id, $level = 0)
    {
        static $counter = 0;

        global $ADMIN_PATH;

        if (0 != $level) {
            echo "<table cellspacing='0' cellpadding='0' class='templateMap'><tbody><tr>
                  <td class='button withBorder'><input type='radio' ";
            if (0 == $counter) {
                echo "checked='checked' ";
                $counter = 1;
            }
            echo "name='upload_from' value='".$data[$node_id]['id']."'></td>
                  <td style='padding-left:".($level*10)."px; font-weight: bold;' class='withBorder'><img width='14' border='0' height='10' alt='arrow' src='" . $ADMIN_PATH . "images/arrow_sec.gif'><span>".$data[$node_id]['id'].". </span>".$data[$node_id]['desc']."</td>
                  </tr></tbody></table>";
        }

        foreach ($data as $row) {
            if ($row['parent'] == $node_id) {
                $this->printTplByRow($data, $row['id'], $level + 1);
            }
        }
    }

    /**
     * Recursive heplper for step 3
     * Inserts template data into database (recursively)
     * @param type $data - templates array
     * @param array $relations - array of relation pairs (array($old_id => $newly_inserted_id)) needed for further renaming of folders
     * @param type $node_id
     * @param type $level
     * @param type $prev_level
     * @param type $parent_id
     * @param string $tpl_path
     * @return boolean
     */
    private function insertTplByRow(&$data, &$relations, $node_id, $level = 0, $prev_level = 0, $parent_id = 0, $tpl_path = '/')
    {
        $query = $data[$node_id]['sql'];
        $query = str_replace($this->idFlag, $parent_id, $query);
        $res = $this->db->query($query);

        if ($res) {
            $insert_id = $this->db->insert_id;
        } else {
            return false;
        }

        $tpl_path .= $insert_id.'/';
        $relations[$node_id] = $insert_id;

        $SQL = "UPDATE `Template`
                    SET `File_Path` = '$tpl_path'
                        WHERE `Template_ID` = $insert_id";
        $this->db->query($SQL);

        foreach ($data as $row) {
            if ($row['parent'] == $node_id) {
                $this->insertTplByRow($data, $relations, $row['id'], $level + 1, $level, $insert_id, $tpl_path);
            }
        }

        return true;
    }

    private function getTplData() {
        $xmlTemplates = $this->xmlData['sql']['templates']['template'];

        if (isset($xmlTemplates['value'])) $templates[0] = $xmlTemplates;
        else $templates = $xmlTemplates;

        $tplData = array();
        if ( !empty($templates) ):
			foreach($templates as $id => $tpl) {
				$tpl_id = $tpl['attr']['id'];
				$tplData[$tpl_id]['sql'] = $tpl['value'];
				$tplData[$tpl_id] += $tpl['attr'];
			}
        endif;

        return $tplData;
    }

    private function xml2array($contents, $get_attributes = 1, $priority = 'tag') {
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
                        $repeated_tag_index[$tag.'_'.$level]++;
                    }
                }
            } elseif ($type == 'close') {
                $current = & $parent[$level - 1];
            }
        }
        return ($xml_array);
    }
}
?>