<?
/* $Id: subclass_list.php 7529 2012-07-09 16:30:06Z lemonade $
 *
 * Диалог переноса объекта $class_id:$message_id в раздел $sub_id,
 * когда в разделе больше одного шаблона типа $class_id
 */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

settype($class_id, "integer");
settype($message_id, "integer");
settype($sub_id, "integer");

if (!$class_id || !$message_id || !$sub_id) {
    die("Wrong params for ".__FILE__);
}

$sub_data = new ui_subdivision_data;
$sub_data->fetch_by_subdivision_id($sub_id);
$class_name = $db->get_var("SELECT Class_Name FROM Class WHERE Class_ID=$class_id");
?>
<html>
    <head>
        <title><?=SECTION_INDEX_DEV_CLASSES
?></title>
        <link rel='stylesheet' type='text/css' media='screen' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/admin.css') ?>'>
        <link rel='stylesheet' type='text/css' media='screen' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/main.css') ?>'>
    </head>

    <body>
        <table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%" id="wrapperTable">
            <tr height="1%">
                <td class="popup_frame_description" id="ccDescription"><div class="text_block">
<?
nc_print_status(printf(NETCAT_SELECT_SUBCLASS_DESCRIPTION, $sub_data->subdivision_name, $class_name), 'info');
?>
                    </div></td>
            </tr>
            <tr>
                <td>
                    <div style="overflow-y: auto; overflow-x: hidden; margin: 9px" id='ccList' class='related_list related_list_subclass'>
                        <?
                        foreach ($sub_data->get_moderated_subclasses() as $sc) {
                            if ($sc["Class_ID"] == $class_id) {
                                print "<a href='#' onclick='moveThisMessage($sc[Sub_Class_ID])'>$sc[Sub_Class_Name]</a>\n";
                            }
                        }
                        ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td id="ccButtons" class="main_view_buttons">
                    <table cellspacing="0" cellpadding="0" border="0" onclick="closeDialog()"
                           style="float: right;" class="bottom_button">
                        <tr><td class="bottom_button_left"> </td>
                            <td class="bottom_button_body"><?=CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CANCEL
                        ?></td>
                            <td class="bottom_button_right"> </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <script>
            var hBody = (window.innerHeight ? window.innerHeight : document.body.offsetHeight),
            hHdr = document.getElementById('ccDescription').offsetHeight,
            hFtr = document.getElementById('ccButtons').offsetHeight;

            document.getElementById('ccList').style.height = (hBody - hHdr - hFtr - 20)+'px';

            function closeDialog() {
                window.parent.document.getElementById('messageToSubdivisionDialog').style.display = 'none';
                window.location.href="about:blank";
            }

            function moveThisMessage(destinationSubclassId) {
                window.parent.moveMessage(<?="$class_id, $message_id, destinationSubclassId"
                        ?>);
                closeDialog();
            }
        </script>

    </body>
</html>