<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."field/function.inc.php");
require ($ADMIN_FOLDER."class/function.inc.php");
require ($ADMIN_FOLDER."class/Message.inc.php");

$nc_core = nc_core::get_object();

$main_section = "control";
$item_id = 8;
$Delimeter = " &gt ";
$Title2 = CONTROL_CLASS;
$Title3 = "<a href=\"".$ADMIN_PATH."class/\">".CONTROL_CLASS."</a>";
$Title4 = CONTROL_CLASS_ADD_ACTION;

if (!isset($ClassID)) {
    $ClassID = $nc_core->input->fetch_get_post('ClassID');
}

$Title5 = GetClassNameByID($ClassID);
$Title6 = CONTROL_CLASS_DELETECOMMIT;
$Title7 = CONTROL_CLASS_DOEDIT;
$Title8 = CONTROL_CLASS_CLASS_GROUPS;
$Title9 = "<a href=\"".$ADMIN_PATH."class/\">".CONTROL_CLASS_CLASS_GROUPS."</a>";

if (!isset($phase)) {
    $phase = 1;
}

if (!isset($_REQUEST['fs'])) {
    $_REQUEST['fs'] = null;
}

$File_Mode = +$_REQUEST['fs'];
$ClassID = +$ClassID;

if (!isset($BaseClassID)) {
    $BaseClassID = $nc_core->input->fetch_get_post('BaseClassID');
};
$BaseClassID = +$BaseClassID;

$param = urlencode($nc_core->input->fetch_post_get('param')) ?: '';

if (in_array($phase, array(3, 5, 7, 9, 141, 15, 17, 19, 23))) {

    if (!$nc_core->token->verify()) {
        if (defined('NC_ADMIN_DISABLE_LOGIN_PAGE') || $nc_core->input->fetch_post('NC_HTTP_REQUEST')) { // AJAX call
            nc_set_http_response_code(401);
            exit;
        }

        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}
if (!isset($ClassGroup)) {
    $ClassGroup = $nc_core->input->fetch_get_post('ClassGroup');
}

$type = isset($type) ? (int)$type : null;

try {
    switch ($phase) {
        case 1:
            # покажем список шаблонов
            if (!$ClassGroup) {
                BeginHtml(SECTION_CONTROL_CLASS, SECTION_CONTROL_CLASS, "http://".$DOC_DOMAIN."/management/class/");
            } else {
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            }

            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);

            if ($ClassGroup) {
                $UI_CONFIG = new ui_config_class_group('edit', $ClassGroup);
            } else {
                $UI_CONFIG = new ui_config_classes();
            }
            ClassList($ClassGroup);
            break;

        case 2:
            # покажем форму добавления шаблона
            $UI_CONFIG = new ui_config_class('add', $ClassID, $ClassGroup);
            BeginHtml($Title2, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/form/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_ADD, 0, 0, 0);
            ClassForm(0, "index.php", 3, 1, $BaseClassID);
            break;

        case 3:
            # собственно добавление шаблона
            if (!$Class_Name) {
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
                nc_print_status(CONTROL_CONTENT_CLASS_ERROR_NAME, 'error');
                ClassForm(0, "index.php", 3, 1, $BaseClassID);
                EndHtml();
                exit;
            }

            if ($Class_Group_New) {
                if (preg_match('/^\d+$/', $Class_Group_New)) {
                    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
                    nc_print_status(CONTROL_CONTENT_CLASS_GROUP_ERROR_NAME, 'error');
                    ClassForm(0, "index.php", 3, 1, $BaseClassID);
                    exit;
                }
            }

            $keyword = $nc_core->input->fetch_post_get('Keyword');
            $keyword_validation_result = $nc_core->component->validate_keyword($keyword, null, 0);

            if ($keyword_validation_result !== true) {
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
                nc_print_status($keyword_validation_result, 'error');
                ClassForm(0, "index.php", 3, 1, $BaseClassID);
                EndHtml();
                exit;
            }

            $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_ADD, 0, 0, 1);
            $NewID = ActionClassComleted($type, $BaseClassID);

            if (!$NewID) {
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
                nc_print_status(CONTROL_CONTENT_CLASS_ERROR_ADD, 'error');
                ClassForm(0, "index.php", 3, 1, $BaseClassID);
                EndHtml();
                exit;
            } else {
                $AJAX_SAVER = true;
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
                nc_print_status(CONTROL_CONTENT_CLASS_SUCCESS_ADD, 'ok');
            }

            if ($BaseClassID) {
                InsertActionsFromBaseClass($BaseClassID, $NewID);
                InsertFieldsFromBaseClass($BaseClassID, $NewID);
            }

            $ClassGroup = $db->escape($ClassGroup);
            $NewID = intval($NewID);

            if (!$ClassGroup) {
                $ClassGroup = $db->get_var("SELECT Class_Group FROM Class WHERE Class_ID = '".$NewID."'");
            } else {
                $ClassGroup = $db->get_var("SELECT Class_Group FROM Class WHERE md5(Class_Group) = '".$ClassGroup."'");
            }

            $ClassGroup = $db->get_var("SELECT Class_Group FROM Class WHERE Class_ID = '".$NewID."'");
            $UI_CONFIG = new ui_config_class('edit', $NewID, md5($ClassGroup));

            $isNewGroup = $db->get_var("SELECT COUNT(Class_Group) FROM Class WHERE Class_Group = '".$ClassGroup."'");

            $suffix = +$_REQUEST['fs'] ? '_fs' : '';

            if ($isNewGroup == 1) {
                $classgroup_buttons[] = array(
                        "image" => "i_class_add.gif",
                        "label" => CONTROL_CLASS_ADD,
                        "href" => "dataclass.add(".md5($ClassGroup).")"
                );
                $classgroup_buttons[] = array(
                        "image" => "i_class_add.gif",
                        "label" => CONTROL_CLASS_TO_FS,
                        "href" => "dataclass.addfs(".md5($ClassGroup).")"
                );

                $UI_CONFIG->treeChanges['addNode'][] = array(
                        "parentNodeId" => "dataclass.list",
                        "nodeId" => "group-".md5($ClassGroup),
                        "name" => $ClassGroup ? $ClassGroup : CONTROL_CLASS_CLASS_NO_GROUP,
                        "href" => "#classgroup".$suffix.".edit(".md5($ClassGroup).")",
                        "image" => 'i_classgroup.gif',
                        "buttons" => $classgroup_buttons,
                        "hasChildren" => 1,
                        "dragEnabled" => false
                );
            }

            if ($NewID) {
                $class_buttons = array();

                $class_buttons[] = nc_get_array_2json_button(
                    CONTROL_FIELD_LIST_ADD,
                    "field{$suffix}.add({$NewID})",
                    "nc-icon nc--file-add nc--hovered");

                $class_buttons[] = nc_get_array_2json_button(
                    CONTROL_CLASS_DELETE,
                    "dataclass{$suffix}.delete({$NewID})",
                    "nc-icon nc--remove nc--hovered");

                $UI_CONFIG->treeChanges['addNode'][] = array(
                    "nodeId"       => "dataclass-{$NewID}",
                    "name"         => "{$NewID}. {$Class_Name}",
                    "href"         => "#dataclass".$suffix.".edit({$NewID})",
                    "sprite"       => 'dev-components' . ($suffix ? '' : '-v4'),
                    "acceptDropFn" => "treeClassAcceptDrop",
                    "onDropFn"     => "treeClassOnDrop",
                    "hasChildren"  => false,
                    "dragEnabled"  => true,
                    "buttons"      => $class_buttons,
                    "parentNodeId" => "group-".md5($ClassGroup),
                );
            }

            ClassForm($NewID, "index.php", 5, 2, 0);

            if (+$_REQUEST['isNaked']) {
                ob_clean();
                echo 'OK';
                exit;
            }

            break;

        case 4:
            # покажем форму редактирования шаблона
            if (+$nc_core->input->fetch_get('isNaked')) {
                $AJAX_SAVER = true;

                if ($perm->isGuest()) {
                    $AJAX_SAVER = false;
                }

                $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);
                ClassForm_developer_mode($ClassID);
                exit;
            }

            $AJAX_SAVER = true;

            if ($perm->isGuest()) {
                $AJAX_SAVER = false;
            }

            if (!isset($developer_mode)) {
                $developer_mode = $nc_core->input->fetch_get_post('developer_mode');
            }

            BeginHtml($Title7, $Title3.$Delimeter.$Title5, "http://".$DOC_DOMAIN."/management/class/form/", '', $developer_mode);
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);
            $UI_CONFIG = new ui_config_class('edit', $ClassID);

            ClassForm($ClassID, "index.php", 5, 2, 0);
            break;

        case 5:
            # собственно проапдейтим шаблон
            $AJAX_SAVER = true;

            if ($perm->isGuest()) {
                $AJAX_SAVER = false;
            }    

            if (isset($action_type) && $action_type == 1) {
                BeginHtml($Title8, $Title8, "http://".$DOC_DOMAIN."/management/class/groupofclass/");
            } else {
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            }

            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);

            if (!$Class_Name) {
                nc_print_status(CONTROL_CONTENT_CLASS_ERROR_NAME, 'error');
                $AJAX_SAVER = true;
                ClassForm(0, "index.php", 3, 1, $BaseClassID);
                EndHtml();
                exit;
            }

            $keyword = $nc_core->input->fetch_post_get('Keyword');
            $keyword_validation_result = $nc_core->component->validate_keyword($keyword, $ClassID, null);
            if ($keyword_validation_result !== true) {
                nc_print_status($keyword_validation_result, 'error');
                $AJAX_SAVER = true;
                ClassForm(0, "index.php", 3, 1, $BaseClassID);
                EndHtml();
                exit;
            }

            if (!empty($Class_Group_New)) {
                nc_preg_match('/[0-9]+/', $Class_Group_New, $matches);
                if (nc_strlen($Class_Group_New) == nc_strlen($matches[0])) {
                    nc_print_status(CONTROL_CONTENT_CLASS_GROUP_ERROR_NAME, 'error');
                    ClassForm(0, "index.php", 3, 1, $BaseClassID);
                    exit;
                }
            }

            $OldClass = $db->get_row("SELECT Class_Name, Class_Group FROM Class WHERE Class_ID = '".$ClassID."'", ARRAY_A);
            if (!empty($Class_Group_New))
                    $isNewGroup = $db->get_var("SELECT COUNT(Class_Group) FROM Class WHERE Class_Group = '".$Class_Group_New."'");

            if (ActionClassComleted($type) === false) {
                nc_print_status(CONTROL_CONTENT_CLASS_ERROR_EDIT, 'error');
                $AJAX_SAVER = true;
                ClassForm($ClassID, "index.php", 5, 2, 0);
                EndHtml();
                exit;
            } else {
                nc_print_status(CONTROL_CONTENT_CLASS_SUCCESS_EDIT, 'ok');
            }

            $NewClass = $db->get_row("SELECT Class_Name, Class_Group FROM Class WHERE Class_ID = '".$ClassID."'", ARRAY_A);
            if (isset($action_type) && $action_type == 1) {
                $UI_CONFIG = new ui_config_class('info', $ClassID);
            } else {
                $UI_CONFIG = new ui_config_class('edit', $ClassID, md5(nc_array_value($NewClass,'Class_Group')));
            }
            if (!empty($Class_Group_New) && !$isNewGroup) {
                $classgroup_buttons[] = array(
                        "image" => "i_class_add.gif",
                        "label" => CONTROL_CLASS_ADD,
                        "href" => "dataclass.add(".md5($NewClass['Class_Group']).")");
                $classgroup_buttons[] = array(
                        "image" => "i_class_add.gif",
                        "label" => CONTROL_CLASS_ADD_FS,
                        "href" => "dataclass.addfs(".md5($NewClass['Class_Group']).")"
                );
                $suffix = +$_REQUEST['fs'] ? '_fs' : '';
                $UI_CONFIG->treeChanges['addNode'][] = array(
                        "parentNodeId" => "dataclass.list",
                        "nodeId" => "group-".md5($NewClass['Class_Group']),
                        "name" => $NewClass['Class_Group'],
                        "href" => "#classgroup".$suffix.".edit(".md5($NewClass['Class_Group']).")",
                        "image" => 'i_classgroup.gif',
                        "buttons" => $classgroup_buttons,
                        "hasChildren" => 1,
                        "dragEnabled" => false
                );

                ?>
                <script>
                    parent.window.frames[0].window.location.href += '&selected_node=dataclass-<?= $ClassID; ?>';
                </script>
            <?
            }

            if (nc_array_value($OldClass, 'Class_Group') != nc_array_value($NewClass, 'Class_Group')) {

                $isEmptyOldGroup = $db->get_var("SELECT COUNT(Class_Group) FROM Class WHERE Class_Group = '".$OldClass['Class_Group']."'");
                if (!$isEmptyOldGroup) {
                    $UI_CONFIG->treeChanges['deleteNode'][] = "group-".md5($OldClass['Class_Group']);
                } else { // отменяем удаление элемента в дереве, т.к. это перемещение и оно сделано в js
                    //$UI_CONFIG->treeChanges['deleteNode'][] = "dataclass-".$ClassID;
                }
            }

            if (nc_array_value($OldClass, 'Class_Name') != nc_array_value($NewClass, 'Class_Name')) {
                $UI_CONFIG->treeChanges['updateNode'][] = array("nodeId" => "dataclass-$ClassID", "name" => "$ClassID. ".$NewClass['Class_Name']);
            }

            if (isset($action_type) && $action_type == 1) {
                nc_class_info($ClassID, "index.php", 5);
            } else {
                if (!empty($ClassID)) {
                    ClassForm($ClassID, "index.php", 5, 2, 0);
                }
            }

            /*if (+$_REQUEST['isNaked']) {
                ob_clean();
                echo 'OK';
                exit;
            }*/
            break;

        case 6:
            # спросить, действительно ли удалять шаблон
            BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);
            ConfirmDeletion($ClassGroup);
            break;

        case 7:
            # собственно удалить шаблоны
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            if ($ClassGroup) {
                $UI_CONFIG = new ui_config_class_group('edit', $ClassGroup);
            } else {
                $UI_CONFIG = new ui_config_classes();
            }

            foreach ($_POST as $key => $val) {
                if (nc_substr($key, 0, 6) == "Delete" && $val) {
                    $isMoreClasses = CascadeDeleteClass($val);
                }
            }

            if (!$isMoreClasses) {
                $UI_CONFIG->headerText = SECTION_INDEX_DEV_CLASSES;
                ClassList();
            } else {
                ClassList($ClassGroup);
            }
            break;

        case 8:
            # покажем форму редактирования нескольких полей шаблона
            $AJAX_SAVER = true;
            if ($perm->isGuest()) $AJAX_SAVER = false;

            BeginHtml($Title7, $Title3.$Delimeter.$Title5, "http://".$DOC_DOMAIN."/management/class/actions/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);
            ClassActionForm($ClassID, "index.php", 9, 1, $myaction);
            break;

        case 9:
            # собственно проапдейтим шаблон
            $AJAX_SAVER = true;
            if ($perm->isGuest()) $AJAX_SAVER = false;

            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            nc_print_status(CONTROL_CONTENT_CLASS_SUCCESS_EDIT, 'ok');
            ClassActionCompleted($myaction, $type);
            ClassActionForm($ClassID, "index.php", 9, 1, $myaction);
            if (+$_REQUEST['isNaked']) {
                ob_clean();
                echo 'OK';
                exit;
            }
            break;

        case 10:
            #добавим шаблон
            $UI_CONFIG = new ui_config_class('add', $ClassID, $ClassGroup);
            BeginHtml($Title4, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_ADD, 0, 0, 1);
            addNewTemplate($ClassGroup);
            $UI_CONFIG->treeSelectedNode = "group-{$ClassGroup}";
            break;

        case 11:
            #список групп шаблонов
            $item_id = 81;
            BeginHtml($Title8, $Title8, "http://".$DOC_DOMAIN."/management/class/groupofclass/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);
            ClassGroupList();
            break;

        case 12:
            # вывод переменных и функций шаблона
            $BBCODE = true;
            BeginHtml($Title8, $Title8, "http://".$DOC_DOMAIN."/management/class/groupofclass/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            nc_form_data_insert($formtype, $window, $form, $textarea);
            break;

        case 13:
            # настройки шаблона(переименовать...)
            BeginHtml($Title8, $Title8, "http://".$DOC_DOMAIN."/management/class/groupofclass/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class('info', $ClassID);
            nc_class_info($ClassID, "index.php", 5);
            break;

        case 131:
            # настройки шаблона компонента(переименовать...)
            BeginHtml($Title8, $Title8, "http://".$DOC_DOMAIN."/management/class/groupofclass/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class_template('info', $ClassID);
            nc_class_info($ClassID, "index.php", 17);
            break;

        case 14:
            // форма добавления шаблона компонента
            BeginHtml($Title2, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/form/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_ADD, 0, 0, 0);

            $UI_CONFIG = new ui_config_class_template('add', 0, $ClassID);
            // $ClassID - на основе какого компонента делать шаблон
            nc_classtemplate_preadd_from($ClassID);
            break;

        case 141:

            $AJAX_SAVER = true;
            if ($perm->isGuest()) $AJAX_SAVER = false;

            BeginHtml($Title2, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/form/");
            $componentClassId = $ClassID;
            if ($ClassID = nc_classtempalte_make($ClassID, $Type, $base)) {
                nc_print_status(constant("CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_".strtoupper($Type)), 'ok');
                if (isset($from_cc) && isset($from_sub)) {
                    nc_print_status("<a href='".$ADMIN_PATH."subdivision/SubClass.php?phase=3&SubClassID=".$from_cc."&SubdivisionID=".$from_sub."'>".CONTROL_CLASS_COMPONENT_TEMPLATE_RETURN_TO_SUB, 'info');
                }
                if (!isset($from_cc) && isset($from_sub)) {
                    nc_print_status("<a href='".$ADMIN_PATH."subdivision/index.php?view=system&phase=5&SubdivisionID=".$from_sub."'>".CONTROL_CLASS_COMPONENT_TEMPLATE_RETURN_TO_SUB, 'info');
                }
                if (isset($from_trash)) {
                    nc_print_status("<a href='".$ADMIN_PATH."trash/index.php'>".CONTROL_CLASS_COMPONENT_TEMPLATE_RETURN_TO_TRASH, 'info');
                }

                $UI_CONFIG = new ui_config_class_template('edit', $ClassID);

                $className = $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '" . $ClassID . "'");

                $sql = "SELECT COUNT(`Class_ID`) FROM `Class` WHERE `ClassTemplate` = '{$componentClassId}'";
                $templatesCount = $db->get_var($sql);

                $fs_suffix = $_REQUEST['fs'] ? '_fs' : '';
                if ($templatesCount == 1) {
                    $class_template_buttons = array();


                    $class_template_buttons[] = nc_get_array_2json_button(
                        CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                        "classtemplate$fs_suffix.add(".$componentClassId.")",
                        "nc-icon nc--file-add nc--hovered");

                    $UI_CONFIG->treeChanges['addNode'][] = array(
                        "nodeId"       => "classtemplates-".$componentClassId,
                        "parentNodeId" => "dataclass-{$componentClassId}",
                        "name"         => CONTROL_CLASS_CLASS_TEMPLATES,
                        "href"         => "#classtemplates".$fs_suffix.".edit(".$componentClassId.")",
                        "sprite"       => 'dev-templates' . ($fs == 1 ? '' : '-v4'),
                        "acceptDropFn" => "treeClassAcceptDrop",
                        "onDropFn"     => "treeClassOnDrop",
                        "hasChildren"  => true,
                        "dragEnabled"  => false,
                        "buttons"      => $class_template_buttons);
                }

                $UI_CONFIG->treeChanges['addNode'][] = array(
                    "nodeId"       => "classtemplate-".$ClassID,
                    "parentNodeId" => "classtemplates-{$componentClassId}",
                    "name"         => $ClassID.". ".$className,
                    "href"         => "#classtemplate".$fs_suffix.".edit(".$ClassID.")",
                    "sprite"       => 'dev-templates' . ($fs == 1 ? '' : '-v4'),
                    "acceptDropFn" => "treeFieldAcceptDrop",
                    "onDropFn"     => "treeFieldOnDrop",
                    "hasChildren"  => false,
                    "dragEnabled"  => false,
                    "buttons"      => array(nc_get_array_2json_button(
                        CONTROL_CLASS_DELETE,
                        "classtemplate".$fs_suffix.".delete(".$ClassID.")",
                        "nc-icon nc--remove nc--hovered")
                ));

                ClassForm($ClassID, "index.php", 17, 2, 0);
            } else {
                nc_print_status(CONTROL_CLASS_COMPONENT_TEMPLATE_CREATED_FOR_ERROR, 'error');
                exit;
            }
            break;
        case 1411:
            //добавление шаблона и вывод в модалку
            $AJAX_SAVER = true;
            if ($perm->isGuest()) $AJAX_SAVER = false;
            if ($ClassID = nc_classtempalte_make($ClassID, $Type, $base) ){
		        ClassForm_developer_mode($ClassID);
            }
            else{
                exit;
            }
            break;
        case 15:
            // добавление шаблона компонента
            if (!$Class_Name) {
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
                nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NAME, 'error');
                ClassForm(0, "index.php", 15, 1, $ClassTemplate);
                EndHtml();
                exit;
            }

            $perm->ExitIfNotAccess(NC_PERM_CLASS, NC_PERM_ACTION_ADD, 0, 0, 1);
            // get component ID
            $NewID = ActionClassComleted($type);

            if (!$NewID) {
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
                nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_ERROR_ADD, 'error');
                ClassForm(0, "index.php", 15, 1, $ClassTemplate);
                EndHtml();
                exit;
            } else {
                $AJAX_SAVER = true;
                BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
                $UI_CONFIG = new ui_config_class_template('edit', $NewID);
                nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_SUCCESS_ADD, 'ok');
            }

            if ($ClassTemplate) {
                InsertActionsFromBaseClass($ClassTemplate, $NewID);
            }

            ClassForm($NewID, "index.php", 17, 2, 0);
            break;

        case 16:
            // покажем форму редактирования шаблона компонента
            $AJAX_SAVER = true;
            if ($perm->isGuest()) $AJAX_SAVER = false;

            BeginHtml($Title7, $Title3.$Delimeter.$Title5, "http://".$DOC_DOMAIN."/management/class/form/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);
            $UI_CONFIG = new ui_config_class_template('edit', $ClassID);

            ClassForm($ClassID, "index.php", 17, 2, 0);
            break;

        case 17:
            // обновление шаблона компонента
            $AJAX_SAVER = true;
            if ($perm->isGuest()) $AJAX_SAVER = false;

            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);

            if (!$Class_Name) {
                nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NAME, 'error');
                $AJAX_SAVER = true;
                ClassForm($ClassID, "index.php", 17, 2, 0);
                EndHtml();
                exit;
            }

            $keyword = $nc_core->input->fetch_post_get('Keyword');
            $keyword_validation_result = $nc_core->component->validate_keyword($keyword, $ClassID, null);
            if ($keyword_validation_result !== true) {
                nc_print_status($keyword_validation_result, 'error');
                $AJAX_SAVER = true;
                ClassForm($ClassID, "index.php", 17, 2, 0);
                EndHtml();
                exit;
            }

            if (ActionClassComleted($type) === false) {
                nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_ERROR_EDIT, 'error');
                $AJAX_SAVER = true;
                ClassForm($ClassID, "index.php", 17, 2, 0);
                EndHtml();
                exit;
            } else {
                nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_SUCCESS_EDIT, 'ok');
            }

            $classData = $db->get_row("SELECT `Class_Name`, `Class_Group` FROM `Class` WHERE `Class_ID` = '".$ClassID."'", ARRAY_A);

            if (isset($action_type) && $action_type == 1) {
                $UI_CONFIG = new ui_config_class_template('info', $ClassID);
            } else {
                $UI_CONFIG = new ui_config_class_template('edit', $ClassID);
            }

            $UI_CONFIG->treeChanges['updateNode'][] = array(
                    "nodeId" => "classtemplate-".$ClassID,
                    "name" => $ClassID.". ".$classData['Class_Name']
            );

            if (isset($action_type) && $action_type == 1) {
                nc_class_info($ClassID, "index.php", 17);
            } else {
                ClassForm($ClassID, "index.php", 17, 2, 0);
            }

            break;

        case 18:
            // спросить, действительно ли удалять шаблон компонента
            BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);

            ConfirmClassTemplateDeletion($ClassTemplate);
            break;

        case 19:
            // удаление шаблонов компонента
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class_templates('edit', $ClassTemplate);

            foreach ($_POST as $key => $val) {
                if (nc_substr($key, 0, 6) == "Delete" && $val) {
                    $isMoreClasses = CascadeDeleteClassTemplate($val);
                }
            }

            ClassTemplatesList($ClassTemplate);
            break;

        case 20:
            // список шаблонов компонента
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);

            if ($ClassID) {
                $UI_CONFIG = new ui_config_class_templates('edit', $ClassID);
            }

            ClassTemplatesList($ClassID);
            break;

        case 22:
            // алтернативные блоки шаблона компонента
            $AJAX_SAVER = true;
            if ($perm->isGuest()) $AJAX_SAVER = false;

            BeginHtml($Title7, $Title3.$Delimeter.$Title5, "http://".$DOC_DOMAIN."/management/class/actions/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 0);
            ClassActionForm($ClassID, "index.php", 23, 1, $myaction);
            break;

        case 23:
            // сохраним альтернативные блоки
            $AJAX_SAVER = true;
            if ($perm->isGuest()) $AJAX_SAVER = false;

            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);

            nc_print_status(CONTROL_CONTENT_CLASS_SUCCESS_EDIT, 'ok');

            ClassActionCompleted($myaction, $type);
            if (+$_REQUEST['isNaked']) {
                ob_clean();
                echo 'OK';
                exit;
            }
            ClassActionForm($ClassID, "index.php", 23, 1, $myaction);
            break;

        case 24:
            # список пользовательских настроек
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class('custom', $ClassID);
            $custom_settings = $nc_core->component->get_by_id($ClassID, 'CustomSettingsTemplate');
            nc_customsettings_show($ClassID, 0, $custom_settings);
            break;

        case 240:
            # список пользовательских настроек
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class_template('custom', $ClassID);
            $custom_settings = $nc_core->component->get_by_id($ClassID, 'CustomSettingsTemplate');
            nc_customsettings_show($ClassID, 0, $custom_settings, 1);
            break;

        case 241:
            # массовое удаление пользовательских настроек
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class('custom', $ClassID);
            $custom_settings = $nc_core->component->get_by_id($ClassID, 'CustomSettingsTemplate');
            $custom_settings = nc_customsettings_drop($ClassID, 0, $custom_settings);
            nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
            nc_customsettings_show($ClassID, 0, $custom_settings);
            break;

        case 2410:
            # массовое удаление пользовательских настроек
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class_template('custom', $ClassID);
            $custom_settings = $nc_core->component->get_by_id($ClassID, 'CustomSettingsTemplate');
            $custom_settings = nc_customsettings_drop($ClassID, 0, $custom_settings);
            nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
            nc_customsettings_show($ClassID, 0, $custom_settings, 1);
            break;

        case 25:
            # форма редактирования одного параметра
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class('custom', $ClassID);
            $UI_CONFIG->locationHash = isset($param) ? '#dataclass.custom.edit('.$ClassID.', '.$param.')' : '#dataclass.custom.new('.$ClassID.')';
            nc_customsettings_show_once($ClassID, isset($TemplateID) ? $TemplateID : null, isset($param) ? $param : null);
            break;

        case 250:
            # форма редактирования одного параметра
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class_template('custom', $ClassID);
            $UI_CONFIG->locationHash = $param ? '#classtemplate.custom.edit('.$ClassID.', '.$param.')' : '#classtemplate.custom.new('.$ClassID.')';
            nc_customsettings_show_once($ClassID, isset($TemplateID) ? $TemplateID : null, $param, 1);
            break;

        case 251:
            # добавлние/измнение одного параметра
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class('custom', $ClassID);

            try {
                nc_customsettings_save_once();
            } catch (Exception $e) {
                nc_print_status($e->getMessage(), 'error');
                nc_customsettings_show_once($ClassID, 0, $param);
                break;
            }

            nc_print_status($param ? CONTROL_FIELD_MSG_ADDED : CONTROL_FIELD_MSG_ADDED, 'ok');
            $custom_settings = $nc_core->component->get_by_id($ClassID, 'CustomSettingsTemplate');
            nc_customsettings_show($ClassID, 0, $custom_settings);
            break;

        case 2510:
            # добавлние/измнение одного параметра
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class_template('custom', $ClassID);

            try {
                nc_customsettings_save_once();
            } catch (Exception $e) {
                nc_print_status($e->getMessage(), 'error');
                nc_customsettings_show_once($ClassID, 0, $param, 1);
                break;
            }

            nc_print_status($param ? CONTROL_FIELD_MSG_ADDED : CONTROL_FIELD_MSG_ADDED, 'ok');
            $custom_settings = $nc_core->component->get_by_id($ClassID, 'CustomSettingsTemplate');
            nc_customsettings_show($ClassID, 0, $custom_settings, 1);
            break;

        case 26:
            # ручное редактирование
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class('custom', $ClassID);
            $UI_CONFIG->locationHash = '#dataclass.custom.manual('.$ClassID.')';
            nc_customsettings_show_manual($ClassID);
            break;

        case 260:
            # ручное редактирование
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class_template('custom', $ClassID);
            $UI_CONFIG->locationHash = '#classtemplate.custom.manual('.$ClassID.')';
            nc_customsettings_show_manual($ClassID, 0, 1);
            break;

        case 261:
            # ручное редактирование
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class('custom', $ClassID);
            $UI_CONFIG->locationHash = '#dataclass.custom.manual('.$ClassID.')';
            $nc_core->component->update($ClassID, array('CustomSettingsTemplate' => $nc_core->input->fetch_get_post('CustomSettings')));
            nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
            nc_customsettings_show_manual($ClassID);
            break;

        case 2610:
            # ручное редактирование
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
            $UI_CONFIG = new ui_config_class_template('custom', $ClassID);
            $UI_CONFIG->locationHash = '#classtemplate.custom.manual('.$ClassID.')';
            $nc_core->component->update($ClassID, array('CustomSettingsTemplate' => $nc_core->input->fetch_get_post('CustomSettings')));
            nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
            nc_customsettings_show_manual($ClassID, 0, 1);
            break;
    }
} catch (Exception $e) {
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
    nc_print_status($e->getMessage(), 'error');
}

EndHtml();
