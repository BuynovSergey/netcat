<?php
/*$Id */

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");

include_once($NETCAT_FOLDER . "vars.inc.php");
require($ROOT_FOLDER . "connect_io.php");
if (!headers_sent()) {
    header('Content-Type: text/html; charset=' . $nc_core->NC_CHARSET);
}

$form = htmlspecialchars(stripcslashes($nc_core->input->fetch_get('form')), ENT_QUOTES);
$control = htmlspecialchars(stripcslashes($nc_core->input->fetch_get('control')), ENT_QUOTES);

if (!$form || !$control) {
    die("Incorrect params");
}

$language = $nc_core->lang->detect_lang();
include($ADMIN_FOLDER . "lang/" . $language . ".php");
$language = $nc_core->lang->acronym_from_full($language);
$language = $language == 'ru' ? 'ru' : 'en';

if (!class_exists("CKEditor")) {
    include_once($nc_core->ROOT_FOLDER . "editors/ckeditor4/ckeditor.php");
}
$CKEditor = new CKEditor();

?>
<html>
<head>
    <title><?= CMS_SYSTEM_NAME; ?></title>
    <meta http-equiv='Content-Type' content='text/html; charset=<?= $nc_core->NC_CHARSET ?>'>
    <style type='text/css'>
        body {
            margin: 2px;
            background-color: #EEEEEE;
        }
    </style>
    <script type='text/javascript' src='<?= $SUB_FOLDER . $HTTP_TEMPLATE_PATH ?>jquery/jquery.min.js'></script>
    <script type='text/javascript'>var CKEDITOR_BASEPATH = '<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'editors/ckeditor4/';?>';</script>
    <script type='text/javascript' src='<?= $CKEditor->getScriptPath(); ?>'></script>
    <script type='text/javascript'><?= $CKEditor->getInstanceReadyHandler(); ?></script>
</head>
<body>

<form name='EditorBackForm' style='margin:0;'>
    <textarea id='nc_editor'></textarea>
    <input type='button' value='<?= NETCAT_SETTINGS_EDITOR_SEND ?>' onclick="OnCloseWindow();">
</form>

<script type='text/javascript'>
    var user_text = opener.document.forms['<?= $form; ?>'].elements['<?= $control; ?>'].value;
    var el = opener.document.forms['<?= $form; ?>'].elements['<?= $control; ?>'];
    if (typeof(window.opener.$nc(el).codemirror == 'function')
        && window.opener.$nc(el).data('codemirror')) {
        user_text = window.opener.$nc(el).data('codemirror').getValue();
    }
    document.getElementById('nc_editor').value = user_text;

    <?php echo $CKEditor->getWindowFormScript($language); ?>

    function OnCloseWindow() {
        var return_value = CKEDITOR.instances.nc_editor.getData();

        var el = opener.document.forms['<?= $form; ?>'].elements['<?= $control; ?>'];
        el.value = return_value;
        if (typeof window.opener.$nc(el).codemirror == 'function') {
            window.opener.$nc(el).codemirror('setValue');
        }
        window.close();
    }


</script>
</body>
</html>