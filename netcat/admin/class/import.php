<?

/* $Id: import.php 8592 2013-01-09 12:16:57Z lemonade $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."class/function.inc.php");
require ($ADMIN_FOLDER."modules/function.inc.php");
require ($ADMIN_FOLDER."class/import.inc.php");


$main_section = "control";
$item_id = 9;
$Delimeter = " &gt ";
$Title2 = CONTROL_CLASS_IMPORTS;
$Title3 = "<a href=\"".$ADMIN_PATH."modules/\">".CONTROL_CLASS_IMPORTS."</a>";
$File_Mode = $nc_core->input->fetch_get_post('fs');

$perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);

if (!isset($phase)) $phase = 1;

switch ($phase) {
    case 1:
        # покажем форму закачки

        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/import/");
        $UI_CONFIG = new ui_config_class('import', $ClassID, $ClassGroup);
        AddClassForm();

        break;

    case 2:
        # добавим шаблон
        if (!$nc_core->token->verify()) {
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            nc_print_status(NETCAT_TOKEN_INVALID, 'error');
            EndHtml();
            exit;
        }


        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $UI_CONFIG = new ui_config_class('import', $ClassID, $ClassGroup);
        if (!$FilePatch['tmp_name']) {
            nc_print_status(CONTROL_CLASS_IMPORT_ERROR_NOTUPLOADED, 'error');
            break;
            #InstallationAborted(CONTROL_CLASS_IMPORT_ERROR_NOTUPLOADED);
        }
        
        $res = ParseClassFile($FilePatch['tmp_name']);
        
        if ($res) {
            $AJAX_SAVER = true;
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            $UI_CONFIG = new ui_config_class('edit', $res, '');
            nc_print_status(CONTROL_CONTENT_CLASS_SUCCESS_ADD, 'ok');
            ClassForm($res, "index.php", 5, 2, 0);
        } else {
            nc_print_status(CONTROL_CLASS_IMPORT_ERROR_CANNOTBEINSTALLED, 'error');
        }
        break;
}


EndHtml ();
?>