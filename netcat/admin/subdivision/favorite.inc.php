<?php

/**
 * Показывает список избранных разделов
 */
function ShowFavorites() {
    global $nc_core, $UI_CONFIG;

    $favorites = GetFavorites('OBJECT');

    if ($favorites) { ?>
        <form method='post' action='favorites.php'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <table class='admin_table' width='100%'>
                            <tr>
                                <th>ID</th>
                                <th width='100%'><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION ?></th>
                                <th><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_SUBSECTIONS ?></th>
                                <th class='align-center'><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_GOTO ?></th>
                                <td class='align-center'>
                                    <div class='icons icon_delete' title='<?= CONTROL_CONTENT_SUBDIVISION_FUNCS_DELETE ?>'></div>
                                </td>
                            </tr>

                            <?php
                            $temp_group = "";
                            $session = session_id() ? '&' . session_name() . '=' . session_id() : "";

                            foreach ($favorites as $favorite) {
                                if ($temp_group != $favorite->Catalogue_ID) {
                                    print "<tr>
                                               <td><br></td>
                                               <td colspan='5'>
                                                   <a href='{$nc_core->ADMIN_PATH}subdivision/full.php?CatalogueID={$favorite->Catalogue_ID}'
                                                      title='" . CONTROL_CONTENT_CATALOUGE_ONESITE . "'>{$favorite->Catalogue_Name}</a>
                                               </td>
                                           </tr>";
                                }
                                $child_list_title = CONTROL_CONTENT_SUBDIVISION_FUNCS_NONE;

                                if (ChildrenNumber($favorite->Subdivision_ID)) {
                                    $child_list_title = CONTROL_CONTENT_SUBDIVISION_FUNCS_LIST . ' (' . ChildrenNumber($favorite->Subdivision_ID) . ')';
                                }

                                $subdivision_delete_confirmation = nc_admin_checkbox_simple(
                                    "Delete[{$favorite->Subdivision_ID}]",
                                    $favorite->Subdivision_ID
                                );

                                print "<tr>
                                           <td>{$favorite->Subdivision_ID}</td>
                                           <td><a href='index.php?phase=4&SubdivisionID={$favorite->Subdivision_ID}'>{$favorite->Subdivision_Name}</a></td>
                                           <td><a href='index.php?phase=1&ParentSubID={$favorite->Subdivision_ID}'>{$child_list_title}</a></td>
                                           <td class='align-center' nowrap>
                                               <a href='index.php?phase=5&SubdivisionID={$favorite->Subdivision_ID}'>
                                                   <i class='nc-icon nc--hovered nc--settings'
                                                      title='" . CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONS . "'
                                                      style='margin: 0 2px;'></i>
                                               </a>";
                                if (GetSubClassCount($favorite->Subdivision_ID)) {
                                    $edit_link_scheme = $nc_core->catalogue->get_scheme_by_id($favorite->Catalogue_ID);
                                    $edit_link_query = http_build_query(array(
                                        'catalogue' => $favorite->Catalogue_ID,
                                        'sub'       => $favorite->Subdivision_ID
                                    ), null, '&amp;');
                                    $edit_link = "{$edit_link_scheme}://{$nc_core->EDIT_DOMAIN}{$nc_core->HTTP_ROOT_PATH}?{$edit_link_query}{$session}";

                                    print "<a target='_blank' href='{$edit_link}'>
                                               <i class='nc-icon nc--hovered nc--edit'
                                                  title='" . CONTROL_CONTENT_SUBDIVISION_FUNCS_TOEDIT . "'
                                                  style='margin: 0 2px;'></i>
                                           </a>";
                                } else {
                                    print "<img src='{$nc_core->ADMIN_TEMPLATE}img/px.gif' width='20' height='20' style='margin: 0 2px;'>";
                                }

                                $subdivision_preview_link = nc_subdivision_preview_link($favorite);

                                print "<a href='{$subdivision_preview_link}' target='_blank'>
                                               <i class='nc-icon nc--hovered nc--arrow-right'
                                                  title='" . CONTROL_CONTENT_SUBDIVISION_FUNCS_TOVIEW . "'
                                                  style='margin: 0 2px 0 2px;'></i>
                                       </a>";
                                print "</td><td class='align-center'>{$subdivision_delete_confirmation}</td>\n</tr>\n";

                                $temp_group = $favorite->Catalogue_ID;
                            }
                            ?>
                        </table>
                    </td>
                </tr>
            </table>
            <input type='hidden' name='phase' value='6'>
            <input type='submit' class='hidden'>
            <?php echo $nc_core->token->get_input(); ?>
        </form>
        <?php
        $UI_CONFIG->actionButtons[] = array(
            'id'         => 'delete',
            'caption'    => NETCAT_ADMIN_DELETE_SELECTED,
            'action'     => 'mainView.submitIframeForm()',
            'align'      => 'left',
            'red_border' => true,
        );
    } else {
        nc_print_status(CONTROL_CONTENT_SUBDIVISION_FUNCS_NOONEFAVORITES, 'info');
    }

    $add_subdivision_window_config = implode(',', array(
        'top=50',
        'left=100',
        'directories=no',
        'location=no',
        'menubar=no',
        'resizable=no',
        'scrollbars=yes',
        'status=yes',
        'toolbar=no',
        'width=400',
        'height=600'
    ));

    $UI_CONFIG->actionButtons[] = array(
        'id'      => 'submit',
        'caption' => CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION,
        'action'  => "window.open('{$nc_core->ADMIN_PATH}subdivision/favorites.php?phase=4','LIST','{$add_subdivision_window_config}')"
    );
}

function GetSubsForFavorites($section = 0, $mode = 'plain', $catalogue_id = 0) {
    global $db, $_structure_level, $perm;

    /** @var Permission $perm */

    $catalogue_id = (int)$catalogue_id;

    if (!$catalogue_id) { // Если не задан сайт, возьмем первый доступный
        $array_id = $perm->GetAllowSite(MASK_ADMIN | MASK_MODERATE, false);
        $where = is_array($array_id) ? ' WHERE `Catalogue_ID` IN(' . implode(',', $array_id) . ') ' : ' ';
        $sql = "SELECT Catalogue_ID FROM Catalogue {$where} ORDER BY Priority LIMIT 1";
        $catalogue_id = $db->get_var($sql);
    }

    // Определение доступных сайтов
    $allow_id = $perm->GetAllowSub($catalogue_id, MASK_ADMIN, true, true, false);
    $query_where = is_array($allow_id) ? ' AND Subdivision_ID IN(' . implode(',', (array)$allow_id) . ') ' : ' ';

    $ret = array();
    $select = "SELECT * FROM Subdivision AS a
               WHERE a.Catalogue_ID = {$catalogue_id}
               AND a.Parent_Sub_ID = {$section}{$query_where}
               ORDER BY a.Priority";

    if ($result = $db->get_results($select, ARRAY_A)) {
        foreach ($result as $row) {
            $row['level'] = (int)$_structure_level;
            $ret[$row['Subdivision_ID']] = $row;
            $_structure_level++;
            $children = GetSubsForFavorites($row['Subdivision_ID'], 'plain', $catalogue_id);
            $_structure_level--;

            foreach ($children as $idx => $row2) {
                $ret[$idx] = $row2;
            }
        }

        if ($mode === 'get_children') {
            foreach ($ret as $idx => $row) {
                while ($row['Parent_Sub_ID'] != $section) {
                    $ret[$row['Parent_Sub_ID']]['Children'][] = $row['Subdivision_ID'];
                    $row = $ret[$row['Parent_Sub_ID']];
                }
            }
        }
    }

    return $ret;
}

function ShowSubsForFavorites($structure = array(), $parent_section = 0, $catalogue_id = 0, $phase = 0) {
    global $db, $perm;
    static $count, $init, $sub_admin;

    /** @var Permission $perm */

    if (!$init) {
        // разделы, в которых пользователь имеет административные права
        $allow_id = $perm->GetAllowSub($catalogue_id, MASK_ADMIN, false, true, false);

        $query_where = is_array($allow_id) ? ' `Subdivision_ID` IN(' . implode(',', (array)$allow_id) . ') ' : ' 1';
        $sub_admin = (array)$db->get_col(
            'SELECT `Subdivision_ID`
             FROM `Subdivision`
             WHERE ' . $query_where
        );
        $init = true;
    }

    $list_identifier = !$parent_section ? "id='siteTree'" : "";

    echo "<ul {$list_identifier} style='margin-left: 5px; padding-left: 0;'>\n";
    foreach ($structure as $id => $row) {
        if ($row['Parent_Sub_ID'] == $parent_section) {
            $count++;
            $subdivision_state = !$row['Checked'] ? 'nc--disabled' : "";
            if (!$row['Parent_Sub_ID']) {
                $count = 0;
            }
            if (in_array($row['Subdivision_ID'], $sub_admin)) {
                echo "<li class='menu_left_sub'>\n";
                echo "\t<i class='nc-icon nc--folder {$subdivision_state}'></i>";
                echo "\t<span class='node_id'>{$row['Subdivision_ID']}</span>.&nbsp;";
                if ($row['Favorite']) {
                    echo $row['Subdivision_Name'];
                } else {
                    $next_phase = $phase + 1;
                    $add_to_favorites_on_click = "onclick='add_to_favorites({$row['Subdivision_ID']}, {$next_phase}); return false;'";
                    $add_to_favorites_style = (!$row['Checked'] ? 'style="color:#cccccc;"' : "");
                    echo "<a href='#' {$add_to_favorites_on_click} {$add_to_favorites_style}>{$row['Subdivision_Name']}</a>\n";
                }
            } else { //нет доступа - просто показываем
                echo "<li class='menu_left_sub'><i class='nc-icon nc--folder {$subdivision_state}'></i>";
                echo "\t<span class='node_id'>{$row['Subdivision_ID']}</span> {$row['Subdivision_Name']}\n";
            }

            ShowSubsForFavorites($structure, $id, 0, $phase);
            $count--;
        }
    }
    echo "</ul>\n";
}

function AddFavorites($subdivision_id) {
    global $nc_core;
    $subdivision_id = (int)$subdivision_id;

    $catalogue = $nc_core->subdivision->get_by_id($subdivision_id, 'Catalogue_ID');
    $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $catalogue, $subdivision_id);
    $nc_core->db->query("UPDATE `Subdivision` SET `Favorite` = 1 WHERE `Subdivision_ID` = '{$subdivision_id}'");
    $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $catalogue, $subdivision_id);
}

function ShowCataloguesForFavorites($catalogue_id = 0, $phase = 0) {
    global $nc_core, $perm;

    /** @var Permission $perm */

    $CatalogueID = (int)$catalogue_id;

    // получим id всех каталогов, в которых пользователь имеет административные права
    // если пользователь имеет доступ к разделам каталога, тоже считаем его админом
    // в избранные разделы пользователь может добавить только те, в которых пользователь имеет административные права
    // если функция вернет не массив, значит у пользователя есть доступ ко всем разделам
    $array_id = $perm->GetAllowSite(MASK_ADMIN, false);
    $query_where = is_array($array_id) ? 'catalogue.Catalogue_ID IN(' . implode(',', (array)$array_id) . ')' : '1';

    $select = "SELECT DISTINCT catalogue.Catalogue_ID,
                               catalogue.Catalogue_Name,
                               catalogue.Domain,
                               catalogue.Title_Sub_ID,
                               catalogue.Checked
               FROM Catalogue AS catalogue
               WHERE {$query_where}
               ORDER BY catalogue.Priority";

    $result = $nc_core->db->get_results($select, ARRAY_A);
    $sites_total = $nc_core->db->num_rows;
    $choose_site_select_on_change = "onchange=\"document.location.href='{$nc_core->ADMIN_PATH}subdivision/favorites.php?phase={$phase}&catid='+this.value;\"";

    echo '<nobr>' . CONTROL_USER_SELECTSITE . ": <select {$choose_site_select_on_change} style='width:250px;'>\n";

    $s_id = "";
    if ($nc_core->AUTHORIZATION_TYPE === 'session') {
        $s_id = '&' . session_name() . '=' . session_id();
    }

    foreach ($result as $row) {
        if ($sites_total > 1) {
            if (!$CatalogueID && ($row['Domain'] === $nc_core->HTTP_HOST || (('www.' . $row['Domain']) === $nc_core->HTTP_HOST))) {
                $CatalogueID = $row['Catalogue_ID'];
            }
        } else {
            $CatalogueID = $row['Catalogue_ID'];
        }
        $option_state = $CatalogueID == $row['Catalogue_ID'] ? 'selected' : "";

        echo "\t<option {$option_state} value='{$row['Catalogue_ID']}{$s_id}'>{$row['Catalogue_ID']}:{$row['Catalogue_Name']}\n";
    }

    echo "</select>\n</nobr>\n<hr>\n";
}

/**
 * Удаляет раздел из списка избранных
 *
 * @param array $favorites список ID избранных разделов
 */
function nc_delete_from_favorite($favorites) {
    global $nc_core, $perm;

    foreach ($favorites as $favorite) {
        $favorite = (int)$favorite;
        if ($perm instanceof Permission && $perm->isSubdivisionAdmin($favorite)) {
            $catalogue = $nc_core->subdivision->get_by_id($favorite, 'Catalogue_ID');

            $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $catalogue, $favorite);
            $nc_core->db->query("UPDATE Subdivision SET Favorite = 0 WHERE Subdivision_ID = '{$favorite}'");
            $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $catalogue, $favorite);
        }
    }
}
