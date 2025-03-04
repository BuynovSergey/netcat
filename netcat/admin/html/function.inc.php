<?php

/* $Id: function.inc.php 7333 2012-06-28 15:56:49Z ewind $ */

function ShowHTMLForm() {
    // system superior object
    $nc_core = nc_Core::get_object();

    global $SUB_FOLDER, $HTTP_ROOT_PATH;
    global $UI_CONFIG, $ROOT_FOLDER;

    echo "<form method='post' action='index.php'>";
    echo "<input type='hidden' name='phase' value='2' />";

    // FCKeditor
    echo "<fieldset style='margin-bottom: 15px;'><legend>FCKeditor</legend>";
    if ($nc_core->modules->get_by_keyword('filemanager')) {
        echo "<a href='" . nc_module_path('filemanager') . 'admin.php?page=manager&phase=3&file=' . $SUB_FOLDER . $HTTP_ROOT_PATH . "editors/FCKeditor/fckstyles.nc.xml'>" . NETCAT_SETTINGS_EDITOR_STYLES . "</a>";
    }
    echo "</fieldset>";

    //Ckeditor
    $cur_skin = 'kama';
    $data = @file_get_contents($nc_core->ROOT_FOLDER."editors/nc_settings.xml");
    if ($data) {
        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $data, $values, $indexes);
        xml_parser_free($parser);
        if (!empty($values))
                foreach ($values as $v) {
                if ($v['attributes']['NAME'] == 'ck_skin')
                        $cur_skin = $v['value'];
            }
    }

    echo "<fieldset><legend>CKeditor</legend>";
    echo NETCAT_SETTINGS_EDITOR_SKINS.": <br/><select name='ck_skin' style='margin-top: 4px;'>";
    $dir = $nc_core->ROOT_FOLDER."editors/ckeditor/skins/";
    if (is_dir($dir) && $handle = opendir($dir)) {
        while (($skin = readdir($handle)) !== false) {
            if (file_exists($dir.$skin.'/skin.js'))
                    echo "<option value='".$skin."' ".($cur_skin == $skin ? "selected" : "").">".$skin."</option>";
        }
        closedir($handle);
    }
    echo "</select>";

    echo "</fieldset>";

    echo "</form>";

    $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_SETTINGS_EDITOR_STYLES_SAVE,
            "action" => "mainView.submitIframeForm('StylesForm')"
    );
}

function nc_htmleditor_save() {
    $nc_core = nc_Core::get_object();

    $skin = $_POST['ck_skin'] ? $_POST['ck_skin'] : 'kama';
    $data = "<settings>\r\n\t<param name='ck_skin'>".htmlspecialchars($skin)."</param>\r\n</settings>";

    if (!@file_put_contents($nc_core->ROOT_FOLDER."editors/nc_settings.xml", $data)) {
        print "error";
    }
}
?>