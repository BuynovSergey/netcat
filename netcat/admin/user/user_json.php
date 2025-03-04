<?php

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

if (!$perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_LIST, 0, 0, 0)) {
    die("/* NO RIGHTS */");
}

$arr_for_list = explode("-", $node);
if (count($arr_for_list) > 1) {
    list($node_type, $node_id) = $arr_for_list;
}
else {
    list($node_type) = $arr_for_list;
    $node_id = null;
}
$node_id = (int) $node_id;

$ret = array();

// получение пути (action==get_path) - не требуется
// (все дерево загружается одновременно)

/* * *************************************************************************
 * *  вывод узлов
 */


// Пользователи
// [Группы пользователей] -> группы
// Рассылка по базе
$i = 0;
if ($node == "root") {

    /** ПОЛЬЗОВАТЕЛИ * */
    $ret[$i] = array("nodeId" => "users",
        "name"        => SECTION_CONTROL_USER,
        "href"        => "#user.list()",
        "sprite"      => "user",
        "hasChildren" => false);
    if ($perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_ADD, 0, 0, 0))
        $ret[$i]['buttons'] = array(array("image" => "i_user_add.gif",
            "label" => CONTROL_USER_FUNCS_ADDUSER,
            "href"  => "#user.add()"));


    $i++;

    /** ГРУППЫ ПОЛЬЗОВАТЕЛЕЙ * */
    if ( $perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_LIST, 0, 0, 0) ) {
        $ret[$i] = array("nodeId" => "usergroup",
            "name"        => SECTION_CONTROL_USER_GROUP,
            "href"        => "#usergroup.list()",
            "sprite"      => "user-group",
            "hasChildren" => true,
            "buttons"     => array(
                array("image" => "i_usergroup_add.gif",
                    "label" => CONTROL_USER_ADDNEWGROUP,
                    "href" => "#usergroup.add()")
            )
        );

        // output user groups at once
        $groups = $db->get_results("SELECT PermissionGroup_ID, PermissionGroup_Name
                                  FROM PermissionGroup", ARRAY_A);
        $i++;
        foreach ($groups as $grp) {
            $ret[$i++] = array("nodeId" => "usergroup-$grp[PermissionGroup_ID]",
                "nameId" => "$grp[PermissionGroup_ID]. $grp[PermissionGroup_ID]",
                "name" => $grp["PermissionGroup_Name"],
                "href" => "#usergroup.edit($grp[PermissionGroup_ID])",
                "sprite" => "user-group",
                "hasChildren" => false,
                "parentNodeId" => "usergroup",
            );
        }
    }


    /** РАССЫЛКА ПО БАЗЕ * */
    if ($perm->isAccess(NC_PERM_ITEM_GROUP, NC_PERM_ACTION_MAIL, 0, 0, 0)) {
        $ret[$i] = array("nodeId" => "usermail",
                "name"        => SECTION_CONTROL_USER_MAIL,
                "href"        => "#user.mail()",
                "sprite"      => "mod-subscriber",
                "hasChildren" => false
        );
    }
}


print "while(1);".nc_array_json($ret);
?>
