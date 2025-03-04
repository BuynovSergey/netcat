<?

/* $Id: import.php 8592 2013-01-09 12:16:57Z lemonade $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."class/function.inc.php");
require ($ADMIN_FOLDER."modules/function.inc.php");
require ($ADMIN_FOLDER."class/convert.inc.php");


$main_section = "control";
$item_id = 9;
$Delimeter = " &gt ";
$Title2 = CONTROL_CLASS_CONVERT;
$Title3 = "<a href=\"".$ADMIN_PATH."modules/\">".CONTROL_CLASS_CONVERT."</a>";
$File_Mode = intval($nc_core->input->fetch_get_post('fs'));
$ClassID = intval($nc_core->input->fetch_get_post('ClassID'));

$perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);

if (!isset($phase)) $phase = 1;

$converter = new nc_class_converter();

switch ($phase) {
    #конвертация компонента 4 -> 5
    case 1:
        # покажем предупреждение
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/convert/");
        $UI_CONFIG = new ui_config_class('convert', $ClassID, $ClassGroup);
        echo $converter->confirm_form($ClassID);
        break;

    case 2:
        # конвертируем шаблон
        if (!$nc_core->token->verify()) {
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            nc_print_status(NETCAT_TOKEN_INVALID, 'error');
            EndHtml();
            exit;
        }


        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $UI_CONFIG = new ui_config_class('convert', $ClassID, $ClassGroup);
        echo $converter->convert($ClassID);
        
        break;
        
    #отмена конвертации
    case 3:
        # покажем прелупреждение
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/convert/");
        $UI_CONFIG = new ui_config_class('convert', $ClassID, $ClassGroup);
        echo $converter->confirm_form($ClassID, 'undo');
        break;

    case 4:
        # конвертируем шаблон
        if (!$nc_core->token->verify()) {
            BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
            nc_print_status(NETCAT_TOKEN_INVALID, 'error');
            EndHtml();
            exit;
        }


        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $UI_CONFIG = new ui_config_class('convert', $ClassID, $ClassGroup);
        echo $converter->convert($ClassID, 'undo');

        break;

}


EndHtml ();
?>