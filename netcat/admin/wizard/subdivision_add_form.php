<?php

/* $Id: subdivision_add_form.php 7935 2012-08-09 14:50:10Z ewind $ */

$_POST["NC_HTTP_REQUEST"] = true;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

if (!isset($sub_id) || !isset($catalogue_id)) {
    trigger_error("Wrong params", E_USER_ERROR);
}

$subdivision = $db->get_row("SELECT Parent_Sub_ID,
   	                                  Template_ID
                                 FROM Subdivision
                                WHERE Subdivision_ID = '".$sub_id."'", ARRAY_A);

$templates = $db->get_results("SELECT Template_ID as value,
                                        CONCAT(Template_ID, '. ', Description) as description,
                                        Parent_Template_ID as parent
                                   FROM Template
                               ORDER BY Priority, Template_ID", ARRAY_A);

$classes = $db->get_results("SELECT Class_ID as value,
                                      CONCAT(Class_ID, '. ', Class_Name) as description,
                                      Class_Group as optgroup
                                 FROM Class
                             ORDER BY Class_Group,
                                      Class_ID", ARRAY_A);

#выясним, какой макет наследовать
if ($subdivision['Template_ID']) {
    while (!$watch_templ['Template_ID']) {
        $watch_templ = $db->get_row("SELECT a.Subdivision_ID,a.Parent_Sub_ID, a.Template_ID, b.Description as TemplateName FROM Subdivision as a LEFT JOIN Template as b ON b.Template_ID=a.Template_ID WHERE Subdivision_ID=".$sub_id."", ARRAY_A);
        if (!$watch_templ['Template_ID'] && !$watch_templ['Parent_Sub_ID']) {
            $watch_templ = $db->get_row("SELECT a.Template_ID, b.Description as TemplateName  FROM Catalogue as a, Template as b WHERE Catalogue_ID=".$catalogue_id." AND b.Template_ID=a.Template_ID", ARRAY_A);
            break; // exit from loop
        }
    }
} else {
    $watch_templ = $db->get_row("SELECT a.Template_ID, b.Description as TemplateName FROM Catalogue as a, Template as b WHERE Catalogue_ID=".$catalogue_id." AND b.Template_ID=a.Template_ID", ARRAY_A);
}
#/выясним, какой макет наследовать
echo "<br>";
echo "<fieldset>\n";
echo "<legend>".CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION."</legend>\n";
echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME.":<br>\n";
echo nc_admin_input_simple('Subdivision_Name', '', 50, '', "maxlength='255'")."<br><br>\n";
echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD.":<br>\n";
echo nc_admin_input_simple('EnglishName', '', 50, '', "maxlength='255'")."<br><br>\n";

if (!empty($templates)) {
    echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE.":<br>\n";
    echo "<select name='TemplateID'>\n";
    echo "<option ".($subdivision['Parent_Sub_ID'] ? "" : "selected ")."value='0'>".CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_N." [".$watch_templ['Template_ID'].". ".$watch_templ['TemplateName']."]</OPTION>";
    echo nc_select_options($templates, $site['Title_Sub_ID']);
    echo "</select><br>";
} else {
    echo CONTROL_TEMPLATE_NONE;
}
echo "<br>\n";

if (!empty($classes)) {
    echo CONTROL_CLASS_CLASS.":<br>\n";
    echo "<select name='ClassID'>\n";
    echo "<option value='0'>".NOT_ELSEWHERE_SPECIFIED."</option>\n";
    echo nc_select_options($classes);
    echo "</select><br>";
} else {
    echo CONTROL_CLASS_NONE;
}
echo "<br>\n";

echo "<input type='hidden' name='CatalogueID' value='".$catalogue_id."'>";
echo "<input type='hidden' name='SubdivisionID' value='".$sub_id."'>";
echo $nc_core->token->get_input();
echo "<input type='button' name='addSubdivision' onclick='saveSubdivisionAddForm()' value='".CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION."' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION."'><br><br>\n";

echo "</fieldset>\n";
?>