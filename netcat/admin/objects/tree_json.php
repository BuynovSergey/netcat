<?php

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $ADMIN_FOLDER . 'function.inc.php';

list($node_type, $node_id) = explode('-', $node);
$node_id = (int)$node_id;

$ret_sites = array();
$ret_sub = array();
$nc_core = nc_Core::get_object();

/**
 * @var Permission $perm
 */

// список сайтов
if ($node_type === 'root') {

    // получим id всех каталогов, к которому пользователь имеет доступ админа\модер
    // или имеет доступ к его разделам, тоже админ\модер
    // если ф-ция вернет не массив, то значит есть доступ ко всем
    $array_id = $perm->GetAllowSite(MASK_READ, true);

    /**
     * Получить список сайтов
     */
    $sites = $nc_core->db->get_results(
        'SELECT Catalogue_ID, Catalogue_Name, Domain, Mirrors, Checked, ncMobile, ncResponsive
         FROM Catalogue
         WHERE ' . ((is_array($array_id) && !$perm->isGuest()) ? 'Catalogue_ID IN(' . implode(',', (array)$array_id) . ')' : '1') . '
         ORDER BY Priority',
        ARRAY_A
    );

    $current_site = $nc_core->catalogue->get_by_host_name($nc_core->HTTP_HOST);

    foreach ((array)$sites as $site) {
        $is_this_current_site = $site['Catalogue_ID'] == $current_site['Catalogue_ID'];

        $image = 'icon_site';
        $image .= $site['ncMobile'] ? '_mobile' : '';
        $image .= $site['ncResponsive'] ? '_adapt' : '';
        $image .= $site['Checked'] ? '' : '_disabled';

        $ret_sites[] = array(
            'nodeId'      => "site-{$site['Catalogue_ID']}",
            'name'        => $site['Catalogue_ID'] . '. ' . $site['Catalogue_Name'],
            'href'        => '#',
            'image'       => $image,
            'hasChildren' => true,
            'expand'      => $is_this_current_site
        );
    }
}
// разделы
elseif (($node_type === 'sub' || $node_type === 'site') && $node_id) {

    $qry_where = "sub.Parent_Sub_ID={$node_id}";

    if ($node_type === 'site') {
        $qry_where = "sub.Catalogue_ID={$node_id} AND sub.Parent_Sub_ID=0";
        $current_site = $node_id;
    } else {
        $current_site = $nc_core->subdivision->get_by_id($node_id, 'Catalogue_ID');
    }

    // Получить разделы, которые пользователь может видеть
    $allow_id = $perm->GetAllowSub($current_site, MASK_ADMIN | MASK_MODERATE | MASK_READ, true, true, true);
    $qry_where .= (is_array($allow_id) && !$perm->isGuest()) ? ' AND sub.Subdivision_ID IN(' . implode(',', (array)$allow_id) . ') ' : ' AND 1';

    $subdivisions = $nc_core->db->get_results(
        "SELECT sub.Subdivision_ID,
                sub.Subdivision_Name,
                sub.Catalogue_ID,
                sub.ExternalURL,
                sub.Hidden_URL,
                sub.Parent_Sub_ID,
                sub.Checked,
                sub.LabelColor,
                catalogue.Domain,
                child.Subdivision_ID as hasChildren
         FROM Subdivision AS sub
         LEFT JOIN Subdivision as child ON sub.Subdivision_ID = child.Parent_Sub_ID
         LEFT JOIN Catalogue AS catalogue ON catalogue.Catalogue_ID = sub.Catalogue_ID
         WHERE {$qry_where}
         GROUP BY sub.Subdivision_ID
         ORDER BY sub.Priority",
        ARRAY_A
    );

    $nc_core = nc_Core::get_object();
    foreach ((array)$subdivisions as $sub) {
        $site_url = $nc_core->catalogue->get_url_by_id($sub['Catalogue_ID']);
        $action = "top.loadSubClasses({$sub['Subdivision_ID']}, {$cc}, {$classID}, {$message});tree.selectNode('sub-{$sub['Subdivision_ID']}');";

        $buttons = array();
        $buttons[] = array(
            'label'  => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW,
            'action' => "window.open('{$site_url}{$nc_core->SUB_FOLDER}');",
            'icon'   => 'arrow-right',
            'sprite' => true
        );

        $tree_image = 'icon_folder' . ($sub['Checked'] ? '' : '_disabled') . '';
        $ret_sub[$sub['Subdivision_ID']] = array(
            'nodeId'       => "sub-{$sub['Subdivision_ID']}",
            'parentNodeId' => $sub['Parent_Sub_ID'] ? "sub-{$sub['Parent_Sub_ID']}" : "site-{$sub['Catalogue_ID']}",
            'name'         => $sub['Subdivision_ID'] . '. ' . strip_tags($sub['Subdivision_Name']),
            'href'         => '#',
            'action'       => $action,
            'image'        => $tree_image,
            'hasChildren'  => (bool)$sub['hasChildren'],
            'dragEnabled'  => $drag_enabled,
            'buttons'      => $buttons,
            'className'    => $sub['Checked'] ? '' : 'disabled'
        );
    }
}

$ret = array_merge(array_values($ret_sites), array_values($ret_sub));
print nc_array_json($ret);
?>