<?

class nc_class_converter {
    private $db;
    private $class_templates = array(
                    'FormPrefix' => null,
                    'RecordTemplate' => null,
                    'FormSuffix' => null,
                    'RecordTemplateFull' => null,
                    'Settings' => null,
                    'AddTemplate' => null,
                    'AddCond' => null,
                    'AddActionTemplate' => null,
                    'EditTemplate' => null,
                    'EditCond' => null,
                    'EditActionTemplate' => null,
                    'CheckActionTemplate' => null,
                    'DeleteTemplate' => null,
                    'DeleteCond' => null,
                    'DeleteActionTemplate' => null,
                    'FullSearchTemplate' => null,
                    'SearchTemplate' => null);

    function __construct() {
        $this->db = nc_Core::get_object()->db;
    }

    public function confirm_form($ClassID, $action = 'convert') {
        global $UI_CONFIG;
        $nc_core = nc_Core::get_object();

        $info = $this->class_info($ClassID);
        if ($info) {
            $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => CONTROL_CLASS_CONTINUE,
                "action" => "mainView.submitIframeForm()"
            );
        }
        else {
            return nc_print_status(sprintf(CONTROL_CLASS_CLASS_NOT_FOUND, $ClassID), 'error', null, 1);;
        }

        if ($action == 'convert') {
            $fs = 0;
            $phase = 2;
            $class_list_title = CONTROL_CLASS_CONVERT_CLASSLIST_TITLE;
            $class_folders_title = CONTROL_CLASS_CONVERT_CLASSFOLDERS_TITLE;
            $convert_notice = CONTROL_CLASS_CONVERT_NOTICE;
        }
        else {
            $fs = 1;
            $phase = 4;
            $class_list_title = CONTROL_CLASS_CONVERT_CLASSLIST_TITLE_UNDO;
            $class_folders_title = CONTROL_CLASS_CONVERT_CLASSFOLDERS_TITLE_UNDO;
            $convert_notice = CONTROL_CLASS_CONVERT_NOTICE_UNDO;
        }

        return "<form enctype='multipart/form-data' action='convert.php' method='post'>
                    <fieldset>
                        <legend>" . CONTROL_CLASS_CONVERT . "</legend>
                            ".nc_print_status($convert_notice, 'info', null, 1)."
                        <p>".$class_list_title.":<br /><br />
                        ".$info['class_list']."</p>
                        <p>".$class_folders_title.":<br /><br />
                        ".$info['folder_list']."</p>
                    </fieldset><br/>
                    <input type='hidden' name='ClassID' value='".$ClassID."'/>
                    <input type='hidden' name='phase' value='".$phase."'/>
                    <input type='hidden' name='fs' value='".$fs."'/>
                    <input type='submit' class='hidden'/>
                    " . $nc_core->token->get_input() . "
                    </form>";
    }






    public function class_info($ClassID) {
        $class_name = $this->db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = ".$ClassID);
        $class_templates_info = $this->db->get_results("SELECT `Class_ID`, `Class_Name` FROM `Class` WHERE `ClassTemplate` = ".$ClassID, ARRAY_A);

        if (!$class_name) return FALSE;

        $info['class_list'] .= $ClassID.". ".$class_name."<br />";
        if (!empty($class_templates_info) && is_array($class_templates_info) ) {
            foreach ($class_templates_info as $class_template) {
                $info['class_list'] .= "&nbsp;&nbsp;-&nbsp;".$class_template['Class_ID'].". ".$class_template['Class_Name']."<br />";
            }
        }

        $info['folder_list'] = $nc_core->HTTP_TEMPLATE_PATH."class/".$ClassID."/<br />";
        if (!empty($class_templates_info) && is_array($class_templates_info) ) {
            foreach ($class_templates_info as $class_template) {
                $info['folder_list'] .= $nc_core->HTTP_TEMPLATE_PATH."class/".$ClassID."/".$class_template['Class_ID']."/<br />";
            }
        }

        return $info;
    }

    public function convert($ClassID, $action = 'convert') {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        $fs = ($action == 'convert' ? 1 : 0);

        if ($action == 'convert') {
            $this->db->query("UPDATE `Class` SET `File_Mode` = 1, `File_Path` = IF(`ClassTemplate` > 0, CONCAT('/', `ClassTemplate`, '/', `Class_ID`,'/'), CONCAT('/', `Class_ID`,'/')) WHERE `Class_ID` = ".$ClassID." OR `ClassTemplate` = ".$ClassID);
        }

        if ($this->db->errno) {
            return nc_print_status(CONTROL_CLASS_CONVERT_DB_ERROR, 'error', null, 1);
        }

        $classes_data = $this->db->get_results("SELECT * FROM `Class` WHERE `Class_ID` = ".$ClassID." OR `ClassTemplate` = ".$ClassID." ORDER BY `ClassTemplate` ASC", ARRAY_A);

        foreach ($classes_data AS $class_data) {
            $templates = $this->get_class_templates_list();
            //save tpls
            $class_editor = new nc_tpl_component_editor($nc_core->CLASS_TEMPLATE_FOLDER, $db);
            $class_editor->load($class_data['ClassTemplate']);
            $path = $class_editor->get_absolute_path().$class_data['Class_ID']."/class_v40_backup.html";
            if ($action == 'convert') {
                $new_temlates = $this->convert_class_templates($class_data);
                $class_editor->save_new_class($class_data['Class_ID'], $new_temlates);

                foreach ($templates as $field_name => $tmp) {
                    $fields[$field_name] = "<!-- $field_name -->" . $class_data[$field_name] . "<!-- /$field_name -->";
                }
                $backup_content = join("\n\n", $fields);
                nc_save_file($path, $backup_content);
            }
            else {
                $restore_content = nc_check_file($path) ? nc_get_file($path) : false;
                if (!$restore_content) {
                    return nc_print_status(CONTROL_CLASS_CONVERT_UNDO_FILE_ERROR, 'error', null, 1);
                }

                foreach ($templates as $field_name => $tmp) {
                    if(preg_match("#<!-- ?$field_name ?-->(.*)<!-- ?/ ?$field_name ?-->#is", $restore_content, $matches)) {
                        $restored_templates[$field_name] = $matches[1];
                    }
                }
                if ($restored_templates && is_array($restored_templates)) {
                    $query = array();
                    foreach ($restored_templates as $field_name => $tmp) {
                        $query[] = "`".$field_name."` = '".$db->prepare($tmp)."'";
                    }
                    // сообственно, добавление
                    $SQL =  "\nUPDATE `Class`";
                    $SQL .= "\n    SET `File_Mode` = 0 , " . join(",\n        ", $query);
                    $SQL .= "\n        WHERE `Class_ID` = " . $class_data['Class_ID'];
                    $this->db->query($SQL);
                    if ($this->db->errno) {
                        return nc_print_status(CONTROL_CLASS_CONVERT_DB_ERROR, 'error', null, 1);
                    }
                }
            }

            $message = nc_print_status(CONTROL_CLASS_CONVERT_OK, 'OK', null, 1);
            $message .= "<a href='index.php?phase=4&ClassID=".$ClassID."&fs=".$fs."'>".CONTROL_CLASS_CONVERT_OK_GOEDIT."</a>";
        }
        return $message; //no errors
    }

    function convert_class_templates($class_data) {
        $temlates = $this->get_class_templates_list();
        $tpls['php'] = array(
                        'Settings' => null,
                        'AddCond' => null,
                        'EditCond' => null,
                        'DeleteCond' => null);
        $tpls['echo'] = array(
                        'RecordTemplateFull' => null,
                        'AddActionTemplate' => null,
                        'EditTemplate' => null,
                        'EditActionTemplate' => null,
                        'CheckActionTemplate' => null,
                        'SearchTemplate' => null,
                        'DeleteActionTemplate' => null);
        $tpls['result'] = array(
                        'FormPrefix' => null,
                        'RecordTemplate' => null,
                        'FormSuffix' => null,
                        'DeleteTemplate' => null);

        //convert tpls
        if ($class_data['AddTemplate']) {
            $temlates['AddTemplate'] = "<?php echo \" ". $class_data['AddTemplate']." \"; echo \$addForm;  ?>";
        }
        if ($class_data['FullSearchTemplate']) {
            $temlates['FullSearchTemplate'] = "<?php \$searchForm = \" " .$class_data['FullSearchTemplate']." \"; echo \$searchForm; ?>";
        }
        foreach ($tpls['result'] as $tpl_name => $value) {
            if ($class_data[$tpl_name]) {
                $temlates[$tpl_name] = "<?php \$result .= \" ".$class_data[$tpl_name]." \"; ?>";
            }
        }
        foreach ($tpls['echo'] as $tpl_name => $value) {
            if ($class_data[$tpl_name]) {
                $temlates[$tpl_name] = "<?php echo \" ".$class_data[$tpl_name]." \"; ?>";
            }
        }
        foreach ($tpls['php'] as $tpl_name => $value) {
            if ($class_data[$tpl_name]) {
                $temlates[$tpl_name] = "<?php ".$class_data[$tpl_name]." ?>";
            }
        }
        return $temlates;
    }

    /*ordered class templates list*/
    public function get_class_templates_list() {
        return $this->class_templates;
    }
}

?>
