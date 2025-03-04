<?
/* $Id: save.php 5946 2012-01-17 10:44:36Z denis $ */
// данный файл существует только потому, что в s_list_class (select_message_list.php)
// и в дереве (tree_json.php mode=select_subdivision)
// неэффективно вычислять название связанного объекта [для формы, открывшей это окно]

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require_once ($INCLUDE_FOLDER."s_common.inc.php");

$sub_id = (int) $sub_id;
$cat_id = (int) $cat_id;
if (!isset($sub_id) || !$cat_id) {
    trigger_error("Wrong params", E_USER_ERROR);
}
?>
<html>
    <head>
        <title></title>
        <script>
            try {
          <?="
          window.opener.document.getElementById('SelectedSub').value = $sub_id;
          window.opener.document.getElementById('SelectedCat').value = $cat_id;
          "
?>
              }
              catch(e) {
                  alert("<?=addslashes(NETCAT_MODERATION_RELATED_ERROR_SAVING)
?>");
              }
              window.close();
        </script>
    </head>

    <body></body>

</html>