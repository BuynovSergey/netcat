<?

/* $Id: import.php 7983 2012-08-17 09:34:36Z lemonade $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."template/function.inc.php");
require ($ADMIN_FOLDER."modules/function.inc.php");
require ("nc_ImportTemplateClass.php");

$Title2 = CONTROL_TEMPLATE_IMPORT;
$Title3 = "<a href=\"".$ADMIN_PATH."modules/\">".CONTROL_TEMPLATE_IMPORT."</a>";

$perm->ExitIfNotAccess(NC_PERM_CLASS, 0, 0, 0, 1);
$importHandler = new nc_ImportTemplate();
$UI_CONFIG = new ui_config_template('import');
$nc_core = nc_Core::get_object();
$fs = +$_REQUEST['fs'];

if (!isset($phase)) $phase = 1;

BeginHtml($Title2);

switch ($phase) {
    case 1: //import form
        $importHandler->showImportForm($UI_CONFIG, $fs);
        break;

    case 2: //templates list
        if (!$nc_core->token->verify()) {
            nc_print_status(NETCAT_TOKEN_INVALID, 'error');
            EndHtml();
            exit;
        }        
        
        $file = $_FILES['FilePatch'];
        if (!$file['tmp_name']) {
            nc_print_status(CONTROL_TEMPLATE_IMPORT_ERROR_NOTUPLOADED, 'error');
            EndHtml();
            exit;
        }
        
        $new_location = $nc_core->TMP_FOLDER.'nctpl_'.mktime().'.xml';
        $rename_result = rename($file['tmp_name'], $new_location);
        $parse_result = $importHandler->ParseXmlFile($new_location);
        
        if (!$rename_result || !$parse_result) {  
            nc_print_status(CONTROL_TEMPLATE_IMPORT_ERROR_NOTUPLOADED, 'error');
            EndHtml();
            exit;
        }        
        
        $importHandler->showTplList($UI_CONFIG, $fs);   
        break;
        
    case 3: //upload
        $post = $nc_core->input->fetch_post();

        $parse_result = $importHandler->ParseXmlFile($post['filename']);
        
        if (!$parse_result) { 
            nc_print_status(CONTROL_TEMPLATE_IMPORT_ERROR_NOTUPLOADED, 'error');
            EndHtml();
            exit;
        } 
        
        $result_id = $importHandler->uploadData($post['upload_from']);
        
        if (is_array($result_id)) {  
            nc_print_status($result_id['e'], 'error');
            EndHtml();
            exit;
        } else {
            $AJAX_SAVER = true;
            BeginHtml($Title2); 
            $UI_CONFIG = new ui_config_template('edit', $result_id);
            nc_print_status(CONTROL_TEMPLATE_IMPORT_SUCCESS, 'ok');
            TemplateForm($result_id, 2, 2, $fs, true);
        }
        
        break;
}
EndHtml ();
?>
