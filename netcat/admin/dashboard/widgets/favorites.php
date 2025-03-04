<?php

$NETCAT_FOLDER = realpath(dirname(__FILE__) . '/../../../../') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
require_once $ADMIN_FOLDER . "catalogue/function.inc.php";

$favorites = array();
$catalogues = array();
$subclass_link = array();
$result = array();

if ($perm->isAccessSiteMap() || $perm->isGuest()) {

    $sub_ids = array();
    $all_favorites = GetFavorites();
    $domains_statuses = array();

    if (!empty($all_favorites)) {
        foreach ($all_favorites as $i => $row) {
            if (!array_key_exists($row['Domain'], $domains_statuses)) {
                $domains_statuses[$row['Domain']] = checkDomain($row['Domain'], $row['Catalogue_ID'], true);
            }
            $row['link'] = 'subdivision/SubClass.php?phase=1&SubdivisionID=' . $row['Subdivision_ID'];
            $favorites[$row['Catalogue_ID']][$row['Subdivision_ID']] = $row;
            if (isset($catalogues[$row['Catalogue_ID']]) === false) {
                $catalogues[$row['Catalogue_ID']] = array(
                    'checked' => $row['CatalogueChecked'],
                    'name' => $row['Catalogue_Name'],
                    'link' => 'subdivision/full.php?CatalogueID=' . $row['Catalogue_ID'],
                    'domain_error' => $domains_statuses[$row['Domain']],
                );
            }
            $sub_ids[$row['Subdivision_ID']] = $row['Subdivision_ID'];
        }

        $result = $db->get_results("SELECT Sub_Class_ID, Subdivision_ID
                    FROM Sub_Class
                    WHERE Subdivision_ID IN (" . implode(', ', $sub_ids) . ") ORDER BY Priority");
    }

    if (!empty($result)) {
        foreach ($result as $row) {
            // пропускаем 2,3,... компоненты
            if (isset($sub_classes[$row->Subdivision_ID])) {
                continue;
            }

            $subclass_link[$row->Subdivision_ID] = nc_get_scheme() . '://' . $EDIT_DOMAIN . $SUB_FOLDER . $HTTP_ROOT_PATH . '?inside_admin=1&cc=' . $row->Sub_Class_ID;
        }
    }
}

?>
<div class="nc-widget-scrolled">
    <div class="nc-position-t nc-bg-dark" style="background:rgba(0,0,0,.05)">
        <?= FAVORITE_HEADERTEXT ?>
        <? if ($perm->isAccess(NC_PERM_FAVORITE)): ?>
            <a class="nc--right"
                href="<?= $SUB_FOLDER . $HTTP_ROOT_PATH ?>admin/subdivision/favorites.php?phase=1"
                onclick="return nc.ui.dashboard.fullscreen(this)"><i
                    class="nc-icon nc--settings nc--white"></i> <?= SECTION_INDEX_FAVORITE_SETTINGS ?>
            </a>
        <? endif ?>
    </div>
    <? if (empty($catalogues)): ?>
        <div class="nc-padding-10" style="margin-top:30px">
            <div class="nc-alert nc--blue">
                <i class="nc-icon-l nc--status-info"></i>
                <?= CONTROL_CONTENT_SUBDIVISION_FUNCS_NOONEFAVORITES ?>
            </div>
        </div>
    <? else: ?>
        <table style="margin-top:40px" class="nc-table nc--small nc--wide">
            <? foreach ($catalogues as $cat_id => $cat): ?>
                <tr class="nc-bg-light">
                    <td>
                        <i class="nc-icon nc--site"></i>
                        <a href="<?= $cat['link'] ?>"><?= $cat['name'] ?></a>
                    </td>
                    <td class="nc-text-right">
                        <? if ($cat['domain_error']['text']): ?>
                            <? if ($cat['domain_error']['link']): ?>
                                <a href="<?= $cat['domain_error']['link'] ?>"
                                    class="nc-label nc--light"><?= $cat['domain_error']['text'] ?></a>
                            <? else: ?>
                                <span class="nc-label nc--lighten"><?= $cat['domain_error']['text'] ?></span>
                            <? endif ?>
                        <? endif ?>
                        <a class="nc-label nc--lighten"
                            href="subdivision/full.php?CatalogueID=<?= $cat_id ?>"><?= NETCAT_TREE_SITEMAP ?></a>
                    </td>
                </tr>

                <? foreach ($favorites[$cat_id] as $sub_id => $sub): ?>

                    <? $link = isset($subclass_link[$sub_id]) ? $subclass_link[$sub_id] : $sub['link'] ?>
                    <tr>
                        <td colspan='2'>
                            <i class="nc-icon nc--folder<?= !$sub['SubChecked'] ? ' nc--disabled' : '' ?>"></i>

                            <a href="<?= $link ?>"
                                title="<?= $sub['Subdivision_Name'] ?>"<? (!$sub['SubChecked'] ? ' class="nc--disabled"' : "") ?>>
                                <?= $sub['Subdivision_Name'] ?>
                            </a>

                            <div class="nc--right">
                                <? if (isset($subclass_link[$sub_id])): ?>
                                    <a href="<?= $subclass_link[$sub_id] ?>"><i
                                            class="nc-icon nc--edit nc--white nc--hovered"
                                            title="<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_EDIT ?>"></i></a>
                                <? endif ?>
                                <a href="<?= nc_subdivision_preview_link($sub) ?>"
                                    target="_blank"><i
                                        class="nc-icon nc--arrow-right nc--white nc--hovered"
                                        title="<?= CONTROL_CONTENT_SUBDIVISION_FUNCS_TOVIEW ?>"></i></a>
                            </div>
                        </td>
                    </tr>
                <? endforeach ?>
            <? endforeach ?>
        </table>
    <? endif ?>
</div>