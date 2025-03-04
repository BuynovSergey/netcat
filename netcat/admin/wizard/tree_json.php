<?php

// input: mode (wizard_subdivision)

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

if (strpos($node, '-')) {
    list($node_type, $node_id) = explode("-", $node);
} else {
    $node_type = $node;
    $node_id = 0;
}

$node_id = (int) $node_id;

$ret_sites = array();
$ret_sub = array();

$security_limit = "";

// список сайтов
if ($node_type == "root") {

    /**
     * Получить список сайтов
     */
    $sites = $db->get_results("SELECT Catalogue_ID, Catalogue_Name, Domain, Mirrors
                               FROM Catalogue
                             $security_limit
                              ORDER BY Priority ", ARRAY_A);

    $current_site = $nc_core->catalogue->get_by_host_name($nc_core->HTTP_HOST);

    foreach ((array) $sites as $site) {
        $is_this_current_site = $site['Catalogue_ID'] == $current_site['Catalogue_ID'];

        if ($mode === 'wizard_parentsub') {
            $action = 'return false;';
            $buttons = array(
                array(
                    'image'  => 'i_folder_select.gif',
                    'label'  => NETCAT_MODERATION_SELECT_RELATED,
                    'action' => "top.selectItem($site[Catalogue_ID],0)"
                )
            );
        }

        $ret_sites[] = array(
            'nodeId'      => "site-$site[Catalogue_ID]",
            'name'        => $site['Catalogue_ID'] . '. ' . strip_tags($site['Catalogue_Name']),
            'href'        => '#',
            'image'       => 'i_site.gif',
            'hasChildren' => true,
            'buttons'     => $buttons,
            'expand'      => $is_this_current_site
        );
    }
}

// разделы
elseif (($node_type == 'sub' || $node_type == 'site') && $node_id) {

    if ($node_type == 'site') {
        $qry_where = "sub.Catalogue_ID=$node_id AND sub.Parent_Sub_ID=0";
    } else {
        $qry_where = "sub.Parent_Sub_ID=$node_id";
    }

    $subdivisions = $db->get_results("SELECT sub.Subdivision_ID,
                                           sub.Subdivision_Name,
                                           sub.Catalogue_ID,
                                           sub.Hidden_URL,
                                           sub.Parent_Sub_ID,
                                           sub.Checked,
                                           sub.Catalogue_ID,
                                           catalogue.Domain
                                      FROM Subdivision AS sub
                                 	  JOIN Catalogue AS catalogue ON catalogue.Catalogue_ID = sub.Catalogue_ID
                                     WHERE $qry_where
                                     ORDER BY sub.Priority", ARRAY_A);

    foreach ((array) $subdivisions as $sub) {

        $action = "return false;";
        $buttons = array(
                array("image" => "i_folder_select.gif",
                        "label" => NETCAT_MODERATION_SELECT_RELATED,
                        "action" => "top.selectItem($sub[Catalogue_ID],$sub[Subdivision_ID])")
        );

        $tree_image = "icon_folder".($sub["Checked"] ? "" : "_disabled");
        $ret_sub[$sub['Subdivision_ID']] = array("nodeId" => "sub-$sub[Subdivision_ID]",
                "parentNodeId" => $sub['Parent_Sub_ID'] ? "sub-$sub[Parent_Sub_ID]" : "site-$sub[Catalogue_ID]",
                "name" => $sub['Subdivision_ID'] . '. ' . strip_tags($sub['Subdivision_Name']),
                "href" => "#",
                "action" => $action,
                "image" => $tree_image,
                "hasChildren" => false,
                "dragEnabled" => $drag_enabled,
                "buttons" => $buttons,
                "className" => ($sub["Checked"] ? "" : "disabled"));
    }

    // check hasChildren
    if ($ret_sub) {
        $only_allowed = "";

        $children = $db->get_results("SELECT DISTINCT Parent_Sub_ID
                                    FROM Subdivision
                                   WHERE Parent_Sub_ID IN (".join(",", array_keys($ret_sub)).")
                                         $only_allowed", ARRAY_A);
        foreach ((array) $children as $sub) {
            $ret_sub[$sub['Parent_Sub_ID']]['hasChildren'] = true;
        }
    } // of "hasChildren?"
}

$ret = array_merge(array_values($ret_sites), array_values($ret_sub));
print "while(1);".nc_array_json($ret);
?>