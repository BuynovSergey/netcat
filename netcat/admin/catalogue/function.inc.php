<?php

if (!class_exists("nc_System"))
    die("Unable to load file.");
$systemTableName = "Catalogue";
$systemTableID = GetSystemTableID($systemTableName);



/**
 * Вывод списка сайтов
 *
 * @return int
 */
function ShowCatalogueList() {
    global $db;
    global $EDIT_DOMAIN, $HTTP_ROOT_PATH, $ADMIN_PATH, $ADMIN_TEMPLATE, $SUB_FOLDER;
    $nc_core = nc_Core::get_object();
    $all_sites = $nc_core->catalogue->get_all();

    if (!empty($all_sites)) {
        echo "
		<form method='post' action='index.php'>

		<table border='0' cellpadding='0' cellspacing='0' width='99%' class='border-bottom'>
		<tr>
		<td>ID</td>
		<td width='60%'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SITE . "</td>
		<td>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SUBSECTIONS . "</td>
		<td>
		  <div class='icons icon_prior' title='".CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_PRIORITY."'></div></td>
		<td>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_GOTO . "</td>
		<td>
		  <div class='icons icon_delete' title='".CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE."'></div></td>
 		</tr>";


        foreach ($all_sites as $site) {
            $scheme = $nc_core->catalogue->get_scheme_by_id($site['Catalogue_ID']);
            $catalogue_domain = $nc_core->catalogue->get_url_by_id($site['Catalogue_ID']);

            print "<tr>";
            print "<td>" . "<font>" . $site['Catalogue_ID'] . "</font></td>";
            print "<td>" . "<a href='{$ADMIN_PATH}subdivision/full.php?CatalogueID={$site['Catalogue_ID']}'>" . (!$site['Checked'] ? "<font color='cccccc'>" : "<font>") . $site['Catalogue_Name'] . "</a></font></td>";
            print "<td>" . "<a href='" . $ADMIN_PATH . "subdivision/?CatalogueID=" . $site['Catalogue_ID'] . "'>" . (!$site['Checked'] ? "<font color=cccccc>" : "") . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_LIST . " (" . HighLevelChildrenNumber($site['Catalogue_ID']) . ")</a></td>\n";

            print "<td>" . nc_admin_input_simple("Priority" . $site['Catalogue_ID'], ($site['Priority'] ? $site['Priority'] : 0), 3, "class='s' maxlength='5'") . "</td>\n";


            print "<td>";

            //setup
            print "<a href='index.php?phase=2&CatalogueID=" . $site['Catalogue_ID'] . "&type=2'><div class='icons icon_settings" . (!$site['Checked'] ? "_disabled" : "") . "' title='".CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_TOOPTIONS."'></div></a>";

            //edit
            print ((!GetSubClassCount($site['Title_Sub_ID'])) ? "<img src=" . $ADMIN_PATH . "images/emp.gif width=18 height=18 style='margin:0px 2px 0px 2px;'>" : "<a target=_blank href='{$scheme}://" . $EDIT_DOMAIN . $SUB_FOLDER . $HTTP_ROOT_PATH . "?catalogue=" . $site['Catalogue_ID'] . "&sub=" . $site['Title_Sub_ID'] . (nc_strlen(session_id()) > 0 ? "&" . session_name() . "=" . session_id() . "" : "") . "'><div class='icons icon_pencil" . (!$site['Checked'] ? "_disabled" : "") . "' title='".CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_EDIT."'></div></a>");

            //browse
            print "<a href='{$catalogue_domain}{$SUB_FOLDER}" . (nc_strlen(session_id()) > 0 ? "?" . session_name() . "=" . session_id() . "" : "") . "' target=_blank><div class='icons icon_preview" . (!$site['Checked'] ? "_disabled" : "") . "' title='".CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SHOW."'></div></a>";

            print "</td>";
            print "<td>" . nc_admin_checkbox_simple("Delete" . $site['Catalogue_ID'], $site['Catalogue_ID']) . "</td>\n";
            print "</tr>\n";
        }
        echo "
 		</table>
		<br />
    " . $nc_core->token->get_input() . "
 		<input type=hidden name=phase value='4' />
 		<input class='hidden' type='submit' />
 		</form>
	  ";
    } else {
        echo CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_NONE . "<br /><br />";
    }

    return 0;
}

/**
 * Форма добавления/изменения сайта
 *
 * @param $CatalogueID
 * @param $phase
 * @param $action
 * @param $type 1 -  insert, 2 -  update
 * @param $bar_action
 *
 * @return int|void
 * @throws Exception
 */
function CatalogueForm($CatalogueID, $phase, $action, $type, $bar_action = null) {
    global $ROOT_FOLDER;
    global $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_PATH;
    global $systemTableID, $systemTableName, $admin_mode, $nc_core;
    global $FILES_FOLDER, $INCLUDE_FOLDER, $MODULE_FOLDER, $ADMIN_FOLDER;
    global $UI_CONFIG;

    if (!$bar_action) {
        $bar_action = 'edit';
    }

    $textarea_resize_enabled = true;

    // В настройках дизайна не показывать кнопки ресайза для textarea
    if ($bar_action == 'design') {
        $textarea_resize_enabled = false;
    }

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;
    $lm_type = $nc_core->page->get_field_name('last_modified_type');
    $sm_field = $nc_core->page->get_field_name('sitemap_include');
    $sm_change_field = $nc_core->page->get_field_name('sitemap_changefreq');
    $sm_priority_field = $nc_core->page->get_field_name('sitemap_priority');
    $lang_field = $nc_core->page->get_field_name('language');

    $CatalogueID = (int)$CatalogueID;
    $phase = (int)$phase;

    $params = array('Catalogue_Name', 'Domain', 'Template_ID', 'Read_Access_ID',
            'Write_Access_ID', 'Edit_Access_ID', 'Subscribe_Access_ID',
            'Checked_Access_ID', 'Delete_Access_ID', 'Moderation_ID', 'Checked',
            'Priority', 'Mirrors', 'Robots', 'Cache_Access_ID', 'Cache_Lifetime',
            'TitleSubIDName', 'TitleSubIDKeyword', 'TitleTemplateID',
            'E404SubIDName', 'E404SubIDKeyword', 'E404TemplateID',
            'RulesSubIDName', 'RulesSubIDKeyword', 'RulesTemplateID',
            'CommentsEditRules', 'CommentAccessID', 'CommentsDeleteRules', 'DisplayType',
            'last_modified_type', 'AllowIndexing', $sm_field, $sm_change_field, $sm_priority_field, 'ncOfflineText', 'ncMode');

    foreach ($params as $v) {
        $$v = $nc_core->input->fetch_get_post($v);
    }

    $st = new nc_Component(0, 1);

    foreach ($st->get_fields(0, 0) as $v) {
        $v = 'f_' . $v;
        $$v = $nc_core->input->fetch_get_post($v);
    }

    $showFields = false;

    if ($type == 1) {
        if (!$nc_core->catalogue->can_create()) {
            nc_print_status(CONTROL_CONTENT_CATALOUGE_ERROR_CANNOT_CREATE_MORE, 'error');
            return 0;
        }

        $mandatoryFields = array();

        foreach($st->get_fields() as $f) {
            if ($f['not_null']) {
                $mandatoryFields[] = $f['name'];
            }
        }

        $showFields = count($mandatoryFields) > 0;
    }


    if ($nc_core->modules->get_by_keyword('calendar', 0)) {
        echo nc_set_calendar(0);
    }

    echo "<form id='adminForm' class='nc-form' enctype='multipart/form-data' method='post' name='adminForm' action='" . htmlspecialchars($action, ENT_QUOTES) . "'>";

    if ($type == 1) {
        if ($Priority == "" && $Checked == "")
            $Checked = 1;
        if ($Priority == "")
            $Priority = $db->get_var("SELECT MAX(`Priority`)+1 FROM `Catalogue`");
        foreach ($params as $v)
            $Array[$v] = $$v;

        $Array['Read_Access_ID'] = 1;
        $Array['Write_Access_ID'] = 3;
        $Array['Edit_Access_ID'] = 3;
        $Array['Checked_Access_ID'] = 3;
        $Array['Delete_Access_ID'] = 3;
    }
    else if ($type == 2) {
        try {
            $Array = $nc_core->catalogue->get_by_id($CatalogueID);
        } catch (Exception $e) {
            nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOCATALOGUE, 'info');
            EndHtml();
            exit();
        }
    }

    // По умолчанию: публикация объекта сразу после добавления
    if (!$Array["Moderation_ID"])
        $Array["Moderation_ID"] = 1;
    if (!isset($Array[$lm_type])) {
        $Array[$lm_type] = null;
    }
    if (!$Array[$lm_type])
        $Array[$lm_type] = 1;
    if (!$Array[$sm_change_field])
        $Array[$sm_change_field] = 'daily';
    if (!$Array[$sm_priority_field])
        $Array[$sm_priority_field] = 0.5;

    $fieldsets = new nc_admin_fieldset_collection();

    $access_actions = array('Read', 'Write', 'Edit', 'Checked', 'Delete');

    foreach ($access_actions as $access_action) {
        $Array["_db_{$access_action}_Access_ID"] = $Array[$access_action . "_Access_ID"];
    }

    $fields_hack = array(
            $nc_core->page->get_field_name('last_modified'),
            $nc_core->page->get_field_name('last_modified_type'),
            'Moderation_ID', 'Cache_Access_ID', 'Cache_Lifetime', 'DisallowIndexing', 'Template_ID');

    if ($nc_core->modules->get_by_keyword('search')) {
        $fields_hack[] = $nc_core->page->get_field_name('sitemap_include');
        $fields_hack[] = $nc_core->page->get_field_name('sitemap_changefreq');
        $fields_hack[] = $nc_core->page->get_field_name('sitemap_priority');
    }

    foreach ($fields_hack as $field_name) {
        if (!isset($Array[$field_name])) {
            $Array[$field_name] = null;
        }    
        $Array['_db_' . $field_name] = $Array[$field_name];
    }

    $Array['_db_inherit_'.$sm_change_field] = $Array['_db_'.$sm_change_field];
    $Array['_db_inherit_Template_ID'] = $Array['Template_ID'];

    $bar_all = $bar_action == 'all';
    $display = array (
            'edit' => $bar_all || $bar_action == 'edit' || $bar_action == 'wizard',
            'design' => $bar_action == 'design' || $bar_action == 'wizard',
            'seo' => $bar_action == 'seo' || $bar_action == 'wizard',
            'system' => $bar_action == 'system' || $bar_action == 'wizard',
            'fields' => $bar_action == 'fields' || $bar_action == 'wizard' || $showFields);

    $p_div_bar_action = '';
    $s_div_bar_action = '';

    if ($bar_action == 'all') {
        $p_div_bar_action = "<div style='display: none;'>";
        $s_div_bar_action = '</div>';
    }

    $store_versions = $nc_core->get_settings('StoreVersions', 'system', false, (int)$CatalogueID);

    if (!isset($Array['Created'])) {
        $Array['Created'] = null;
    }

    if (!isset($Array['LastUpdated'])) {
        $Array['LastUpdated'] = null;
    }

    if (!isset($Array["ncHTTPS"])) {
        $Array["ncHTTPS"] = 0;
    }

    $is_saas_site = defined("NC_SAAS_SITE");

    $fieldsets->set_prefix("
        $p_div_bar_action
        <div id='nc_seo_edit_info'".($bar_action != 'edit' ? " style='display:none;'" : "")." class='nc_admin_settings_info'>
            <div class='nc_admin_settings_info_actions'>
                <div>
                    <span>" . CLASS_TAB_CUSTOM_ADD . ":</span> {$Array['Created']}
                </div>
                " . ($Array['LastUpdated'] ?
                "<div>
                    <span>" . CLASS_TAB_CUSTOM_EDIT . ":</span> {$Array['LastUpdated']}
                </div>" : "") . "
            </div>

            <div class='nc_admin_settings_info_priority'>
                <div>
                    " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY . ":
                </div>

                <div>
                    " . nc_admin_input_simple('Priority', intval($Array["Priority"]), 3, '', "maxlength='5'") . "
                </div>
            </div>

            <div class='nc_admin_settings_info_checked'>
                <div>
                    " . nc_admin_checkbox_simple('Checked', 1, CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ON, $Array["Checked"] == 1 || !$CatalogueID, 'turnon') . "
                </div>
            </div>"

            .(!$is_saas_site ? "<div class='nc_admin_settings_info_ssl'>
                <div>
                    " . nc_admin_checkbox_simple('ncHTTPS', 1, CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_HTTPS_ENABLED, $Array["ncHTTPS"] == 1, 'ncHTTPS') . "
                </div>
            </div>" : "")

        ."</div>$s_div_bar_action");

    $fieldsets->set_suffix(
        $nc_core->token->get_input() . "
        <input type='hidden' name='CatalogueID' value='$CatalogueID' />
        <input type='hidden' name='phase' value='$phase' />
        <input type='hidden' name='type' value='" . htmlspecialchars($type, ENT_QUOTES) . "' />
        <input type='hidden' name='posting' value='1' />
        <input type='hidden' name='action' value='" . htmlspecialchars($bar_action, ENT_QUOTES) . "' />
        <input type='submit' class='hidden' />
        </form><br />"
        . ($textarea_resize_enabled ? nc_admin_js_resize(): '')
    );

    $fieldsets->new_fieldset('main_info', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MAININFO)->show($display['edit']);
    $fieldsets->new_fieldset('template', '')->add(nc_subdivision_form_design($Array, $CatalogueID, false))->show($display['design']);
    $fieldsets->new_fieldset('seo', '')->add(nc_subdivision_form_seo($Array, false))->show($display['seo']);

    $fieldsets->new_fieldset('versions', '')->add(
        "<div>
            " . nc_admin_checkbox_simple('StoreVersions', 1, CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_STORE_VERSIONS, (bool)$store_versions, 'StoreVersions') .
            ($store_versions
                ? "<div class='nc_admin_delete_versions' style='margin-left: 1.7em; display: none'>" . $nc_core->ui->alert->info(CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_REMOVE_VERSIONS) . "</div>"
                : '') .
            "
        </div>
        <script>
        \$nc('#StoreVersions').change(function() {
            \$nc('.nc_admin_delete_versions').toggle(!this.checked);
        });
        </script>"
    )->show($display['system']);

    $fieldsets->new_fieldset('access', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ACCESS)->add(nc_subdivision_show_access($Array, false))->show($display['system']);

    if (nc_module_check_by_keyword("cache")) {
        $fieldsets->new_fieldset('cache', CONTROL_CONTENT_CATALOGUE_FUNCS_CACHE)->add(nc_subdivision_show_cache($Array, false))->show($display['system']);
    }

    if (nc_module_check_by_keyword("comments")) {
        $fieldsets->new_fieldset('comments', CONTROL_CONTENT_CATALOGUE_FUNCS_COMMENTS)->add(nc_subdivision_show_comments($Array, false))->show($display['system']);
    }

    $fieldsets->new_fieldset('ncMode', CONTROL_CONTENT_CATALOGUE_FUNCS_MODE)->add(
        nc_get_modal_radio('ncMode', array(
            array(
                'attr' => array('value' => 'template_development'),
                'desc' => CONTROL_CONTENT_CATALOGUE_FUNCS_MODE_TEMPLATE_DEVELOPMENT),
            array(
                'attr' => array('value' => 'normal'),
                'desc' => CONTROL_CONTENT_CATALOGUE_FUNCS_MODE_NORMAL),
            array(
                'attr' => array('value' => 'demo'),
                'desc' => CONTROL_CONTENT_CATALOGUE_FUNCS_MODE_DEMO)), $Array['ncMode'])
    )->show($display['system']);

    ob_start();

    if (!$is_saas_site) {
        echo nc_admin_input(CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NAME, 'Catalogue_Name', $Array['Catalogue_Name'], 32);
    }

    echo nc_admin_input(CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN, 'Domain', $Array["Domain"], 32, '', '', $is_saas_site);

    if ($is_saas_site && defined('NC_SAAS_SITES_LIST_URL')) {
        echo "<div>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN_SAAS_SUFFIX . " <a href='" . NC_SAAS_SITES_LIST_URL .
            "' target='_blank'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DOMAIN_SAAS_PERSONAL_ACCOUNT . "</a></div>";
        echo "<br/>";
    }

    if (!$is_saas_site) {
        echo
            $p_div_bar_action . "<br/>" . nc_admin_textarea_simple(
                'Mirrors',
                $Array["Mirrors"],
                CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MIRRORS,
                4,
                10,
                '',
                '',
                'no_cm'
            ) . "<br /><br />";
    }

    if (file_exists($nc_core->DOCUMENT_ROOT . '/robots.txt')) {
        echo CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ROBOTS;
        nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ROBOTS_FILE_EXIST, 'info');
        echo "<br /><br />";
    } else {
        echo nc_admin_textarea_simple('Robots', $Array["Robots"], CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ROBOTS, 4, 10) . "<br /><br />";
    }
    if (!isset($Array[$lang_field])) {
        $Array[$lang_field] = '';
    }
    echo nc_admin_textarea_simple( 'ncOfflineText', $Array["ncOfflineText"],CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_OFFLINE, 4, 10) . "<br /><br />

    <table border='0' cellpadding=0 cellspacing=0 width=100%>
        <tr>
            <td>
                " . CONTROL_CONTENT_SUBDIVISION_FUNCS_CATALOGUEFORM_LANG . ":<br/>
                " . nc_admin_input_simple('language', $Array[$lang_field], 50) . "<br/>
            </td>
        </tr>";

    if ($type == 2) {
        $subdivisions = $db->get_results("SELECT Subdivision_ID as value,
                                             CONCAT(Subdivision_ID, '. ', Subdivision_Name) as description,
                                             Parent_Sub_ID as parent
                                        FROM Subdivision
                                       WHERE Catalogue_ID='" . $CatalogueID . "'
                                    ORDER BY Subdivision_ID", ARRAY_A);
        echo "
        <tr>
            <td>
                <br />
                <table border='0' cellspacing='0' width='100%' class='border-bottom'>
                    <col width='40%'/><col/>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TITLEPAGE . "
                        </td>
                    <td>";
        if (!empty($subdivisions)) {
            echo "<select name='TitleSubID'>";
            echo nc_select_options($subdivisions, $Array["Title_Sub_ID"]);
            echo "</select>";
        } else {
            echo CONTROL_USER_NOONESECSINSITE;
        }

        echo "          </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND . "
                        </td>
                        <td>";
        if (!empty($subdivisions)) {
            echo "<select name='E404SubID'>";
            echo nc_select_options($subdivisions, $Array["E404_Sub_ID"]);
            echo "</select>";
        } else {
            echo CONTROL_USER_NOONESECSINSITE;
        }

        echo "          </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_POLICY . "
                        </td>
                        <td>";
        if (!empty($subdivisions)) {
            echo "<select name='RulesSubID'>";
            echo "<option value='0'> -- </option>";
            echo nc_select_options($subdivisions, $Array["Rules_Sub_ID"]);
            echo "</select>";
        } else {
            echo CONTROL_USER_NOONESECSINSITE;
        }
        if ($nc_core->modules->get_by_keyword('search')) {
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SEARCH . "
                        </td>
                        <td>";
            if (!empty($subdivisions)) {
                echo "<select name='SearchResultSubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Search_Result_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }
        }

        // Настройки разделов при наличии модуля Личный Кабинет
        if ($nc_core->modules->get_by_keyword('auth')) {
            // Личный кабинет
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE . "
                        </td>
                        <td>";

            if (!empty($subdivisions)) {
                echo "<select name='AuthCabinetSubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Auth_Cabinet_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }

            // Мой профиль
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE_MODIFY . "
                        </td>
                        <td>";

            if (!empty($subdivisions)) {
                echo "<select name='AuthProfileModifySubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Auth_Profile_Modify_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }

            // Регистрация
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_AUTH_PROFILE_SIGNUP . "
                        </td>
                        <td>";

            if (!empty($subdivisions)) {
                echo "<select name='AuthSignupSubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Auth_Signup_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }
        }

        // Настройки разделов при наличии модуля Интернет-магазин
        if ($nc_core->modules->get_by_keyword('netshop')) {
            // Корзина
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_CART . "
                        </td>
                        <td>";

            if (!empty($subdivisions)) {
                echo "<select name='NetshopCartSubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Netshop_Cart_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }

            // Заказ оформлен
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_ORDER_SUCCESS . "
                        </td>
                        <td>";
            if (!empty($subdivisions)) {
                echo "<select name='NetshopOrderSuccessSubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Netshop_Order_Success_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }

            // Условия доставки и возврата
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_DELIVERY . "
                        </td>
                        <td>";

            if (!empty($subdivisions)) {
                echo "<select name='NetshopDeliverySubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Netshop_Delivery_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }

            // Мои заказы
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_ORDER_LIST . "
                        </td>
                        <td>";

            if (!empty($subdivisions)) {
                echo "<select name='NetshopOrderListSubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Netshop_Order_List_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }

            // Сравнение товаров
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_COMPARE . "
                        </td>
                        <td>";

            if (!empty($subdivisions)) {
                echo "<select name='NetshopCompareSubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Netshop_Compare_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }

            // Избранные товары
            echo "      </td>
                    </tr>
                    <tr>
                        <td>
                            " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NETSHOP_FAVORITES . "
                        </td>
                        <td>";

            if (!empty($subdivisions)) {
                echo "<select name='NetshopFavoritesSubID'>";
                echo "<option value='0'> -- </option>";
                echo nc_select_options($subdivisions, $Array["Netshop_Favorites_Sub_ID"]);
                echo "</select>";
            } else {
                echo CONTROL_USER_NOONESECSINSITE;
            }
        }

	echo "           </td>
	    </tr>
	    <tr>
		<td>
		    " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DEFAULT_CLASS . "
		</td>
		<td>";
	$sql = "SELECT `Class_ID` as value, " .
		"IF (`IsAuxiliary`=1,
			CONCAT(`Class_ID`, '. ', `Class_Name`, ' " . $db->escape(CONTROL_CLASS_AUXILIARY) . "'),
			CONCAT(`Class_ID`, '. ', `Class_Name`))
		    as description, " .
		"`Class_Group` as optgroup, `IsAuxiliary` as `is_auxiliary` " .
		"FROM `Class` " .
		"WHERE `ClassTemplate` = 0 AND File_Mode = 0 " .
		"ORDER BY `Class_Group`, `Priority`, `Class_ID`";
	$classesV4 = (array)$db->get_results($sql, ARRAY_A);
	$sql = "SELECT `Class_ID` as value, " .
		"IF (`IsAuxiliary`=1,
			CONCAT(`Class_ID`, '. ', `Class_Name`, ' " . $db->escape(CONTROL_CLASS_AUXILIARY) . "'),
			CONCAT(`Class_ID`, '. ', `Class_Name`))
		    as description, " .
		"`Class_Group` as optgroup, `IsAuxiliary` as `is_auxiliary` " .
		"FROM `Class` " .
		"WHERE `ClassTemplate` = 0 AND File_Mode = 1 " .
		"ORDER BY `Class_Group`, `Priority`, `Class_ID`";
	$classesV5 = (array)$db->get_results($sql, ARRAY_A);
	$classInfo = "<div id='nc_class_select'>";
	if (!empty($classesV4) || !empty($classesV5)) {
	    $classInfo.= "<select id='ClassID' name='Default_Class_ID'>";
	    $classInfo.= "<option value=0> -- </option>";
	    $show_group_label = !empty($classesV4) && !empty($classesV5);
	    if (!empty($classesV5)) {
	        $classInfo.= $show_group_label ? "<option disabled='disabled'>" . CONTROL_CLASS . " v5</option>\n" : '';
	        $classInfo.= nc_select_options($classesV5, $Array["Default_Class_ID"]);
	    }
	    if (!empty($classesV4)) {
	        $classInfo.= $show_group_label ? "<option disabled='disabled'>" . CONTROL_CLASS . " v4</option>\n" : '';
	        $classInfo.= nc_select_options($classesV4, $Array["Default_Class_ID"]);
	    }
	    $classInfo.= "</select>";
	} else {
	    $classInfo.= CONTROL_CLASS_NONE;
	}
	echo $classInfo;

        echo "</td>
</tr>
</table><br><br></td></tr>";

    }
    echo "</table>$s_div_bar_action";

    $fieldsets->main_info->add(ob_get_clean());

    $templates = $db->get_results("SELECT Template_ID as value,
                                        CONCAT(Template_ID, '. ', Description) as description,
                                        Parent_Template_ID as parent
                                   FROM Template
                               ORDER BY Priority, Template_ID", ARRAY_A);

    ob_start();
    echo "<tr><td>";

    if ($type == 1) {
        echo $p_div_bar_action;
        echo WIZARD_SITE_STEP_TWO_DESCRIPTION . "<br/><br/>";
        $title_sub = array();
        $e404_sub = array();
        $rules_sub = array();

        if (!isset($Array["Title_Sub_ID"])) {
            $Array["Title_Sub_ID"] = null;
        }

        if (!isset($Array["E404_Sub_ID"])) {
            $Array["E404_Sub_ID"] = null;
        }
        if (!isset($Array["Rules_Sub_ID"])) {
            $Array["Rules_Sub_ID"] = null;
        }

        if ($Array["Title_Sub_ID"]) {
            $title_sub = $db->get_row("SELECT Subdivision_Name, EnglishName FROM Subdivision WHERE Subdivision_ID = '" . (int)$Array["Title_Sub_ID"] . "'", ARRAY_A);
        }

        if ($Array["E404_Sub_ID"]) {
            $e404_sub = $db->get_row("SELECT Subdivision_Name, EnglishName FROM Subdivision WHERE Subdivision_ID = '" . (int)$Array["E404_Sub_ID"] . "'", ARRAY_A);
        }

        if ($Array["Rules_Sub_ID"]) {
            $rules_sub = $db->get_row("SELECT Subdivision_Name, EnglishName FROM Subdivision WHERE Subdivision_ID = '" . (int)$Array["Rules_Sub_ID"] . "'", ARRAY_A);
        }

        echo "<legend><h3>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TITLEPAGE . "</h3></legend>\n";
        echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME . ":<br>\n";

        if ($title_sub && isset($title_sub['Subdivision_Name'])) {
            echo nc_admin_input_simple('TitleSubIDName', $title_sub['Subdivision_Name']) . "<br><br>\n";
        } else {
            echo nc_admin_input_simple('TitleSubIDName', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_TITLEPAGE) . "<br><br>\n";
        }

        echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD . ":<br>\n";

        if ($title_sub && isset($title_sub['EnglishName'])) {
            echo nc_admin_input_simple('TitleSubIDKeyword', $title_sub['EnglishName']) . "<br><br>\n";
        } else {
            echo nc_admin_input_simple('TitleSubIDKeyword', 'index') . "<br><br>\n";
        }

        if (!empty($templates)) {
            echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE . ":<br>\n";
            echo "<select name='TitleTemplateID'>\n";
            echo "<option value='0'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_N . "</option>";
            echo nc_select_options($templates, $Array["Title_Sub_ID"]);
            echo "</select><br>\n";
        } else {
            echo CONTROL_TEMPLATE_NONE;
        }

        echo "<legend><h3>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND . "</h3></legend>\n";
        echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME . ":<br>\n";

        if ($e404_sub && isset($e404_sub['Subdivision_Name'])) {
            echo nc_admin_input_simple('E404SubIDName', $e404_sub['Subdivision_Name']) . "<br><br>\n";
        } else {
            echo nc_admin_input_simple('E404SubIDName', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_NOTFOUND) . "<br><br>\n";
        }

        echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD . ":<br>\n";

        if ($e404_sub && isset($e404_sub['EnglishName'])) {
            echo nc_admin_input_simple('E404SubIDKeyword', $e404_sub['EnglishName']) . "<br><br>\n";
        } else {
            echo nc_admin_input_simple('E404SubIDKeyword', '404') . "<br><br>\n";
        }

        if (!empty($templates)) {
            echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE . ":<br>\n";
            echo "<select name='E404TemplateID'>\n";
            echo "<option value='0'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_N . "</option>";
            echo nc_select_options($templates, $Array["E404_Sub_ID"]);
            echo "</select><br>\n";
        } else {
            echo CONTROL_TEMPLATE_NONE;
        }

        echo "<legend><h3>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_POLICY . "</h3></legend>\n";
        echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME . ":<br>\n";

        if ($rules_sub && isset($rules_sub['Subdivision_Name'])) {
            echo nc_admin_input_simple('RulesSubIDName', $title_sub['Subdivision_Name']) . "<br><br>\n";
        } else {
            echo nc_admin_input_simple('RulesSubIDName', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_POLICY) . "<br><br>\n";
        }

        echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD . ":<br>\n";

        if ($rules_sub && isset($rules_sub['EnglishName'])) {
            echo nc_admin_input_simple('RulesSubIDKeyword', $rules_sub['EnglishName']) . "<br><br>\n";
        } else {
            echo nc_admin_input_simple('RulesSubIDKeyword', 'policy') . "<br><br>\n";
        }

        if (!empty($templates)) {
            echo CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE . ":<br>\n";
            echo "<select name='RulesTemplateID'>\n";
            echo "<option value='0'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_N . "</option>";
            echo nc_select_options($templates, $Array["Rules_Sub_ID"]);
            echo "</select><br>\n";
        } else {
            echo CONTROL_TEMPLATE_NONE;
        }

        echo $s_div_bar_action;
    }

    $fieldsets->template->add(ob_get_clean());
    $display_type_fieldset = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE);
    ob_start();

    echo nc_get_modal_radio('DisplayType', array(
        array(
            'attr' => array('value' => 'traditional'),
            'desc' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_TRADITIONAL),
        array(
            'attr' => array('value' => 'shortpage'),
            'desc' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_SHORTPAGE),
        array(
            'attr' => array('value' => 'longpage_vertical'),
            'desc' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_DISPLAYTYPE_LONGPAGE_VERTICAL)), $Array['DisplayType']);


    $display_type_fieldset->add(ob_get_clean());
    $fieldsets->template->add($display_type_fieldset->result());

    ob_start();

    if (!isset($Array['ncMobile'])) {
        $Array['ncMobile'] = null;
    }

    if (!isset($Array['ncResponsive'])) {
        $Array['ncResponsive'] = null;
    }

    echo nc_get_modal_radio(
        'ncMobile',
        array(
            array(
                'attr' => array(
                    'value' => '0',
                ),
                'desc' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SIMPLE,
            ),
            array(
                'attr' => array(
                    'value' => '1',
                ),
                'desc' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE,
            ),
            array(
                'attr' => array(
                    'value' => '2',
                ),
                'desc' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ADAPTIVE,
            ),
        ),
        $Array['ncMobile'] ? 1 : ($Array['ncResponsive'] ? 2 : 0)
    );


    require_once($ADMIN_FOLDER . "related/format.inc.php");
    $field = new field_relation_catalogue();

    if (!isset($Array['ncMobileSrc'])) {
        $Array['ncMobileSrc'] = null;
    }

    if (!isset($Array['ncMobileRedirect'])) {
        $Array['ncMobileRedirect'] = null;
    }

    echo "

    <span id='nc_mobilesrc'>
        <br />
        <span id='mobility_text'>
        <font>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_FOR . ":</font>
        <span id='cs_ncMobileSrc_caption' style='font-weight:bold;'>" . ($Array['ncMobileSrc'] ? listQuery($field->get_object_query($Array['ncMobileSrc']), $field->get_full_admin_template()) : '[нет]') . "</span>
        </span>
        <input id='cs_ncMobileSrc_value' name='ncMobileSrc' type='hidden' value='" . $Array['ncMobileSrc'] . "'>&nbsp;&nbsp;
        <span class='mobility_notMobile' style='display: none; color: #aaa'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_FOR_NOTICE . "</span>
        <span class='moblilty_links'>
        <a href='#' onclick='window.open(\"" . $ADMIN_PATH . "related/select_catalogue.php?cs_type=rel_catalogue&amp;cs_field_name=ncMobileSrc\", \"nc_popup_ncMobileSrc\", \"width=800,height=500,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes\"); return false;'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_CHANGE . "</a>&nbsp;&nbsp;
        <a href='#' onclick='document.getElementById(\"cs_ncMobileSrc_value\").value=\"\";document.getElementById(\"cs_ncMobileSrc_caption\").innerHTML = \"" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_NONE . "\";return false;'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_DELETE . "</a></span> <br /><br />
        " . nc_admin_checkbox(CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_REDIRECT, 'ncMobileRedirect', $Array["ncMobileRedirect"], "class='ncMobileIdentity'") . "
    </span>
    <br />" . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_CRITERION . "


    <script type='text/javascipt'>
    function nc_mobile_change() {
        if (\$nc('input[name=ncMobile]').filter(':checked').val() == 1) {
            \$nc('.ncMobileIdentity').each(function() {
                \$nc(this).removeAttr('disabled');
            })
            \$nc('.moblilty_links, #cs_ncMobileSrc_caption').css('display', '');
            \$nc('.mobility_notMobile').css('display', 'none');
            \$nc('#mobility_text').css('color', '#505050');
        } else {
            \$nc('.ncMobileIdentity').each(function() {
                \$nc(this).attr('disabled', 'disabled');
            });
            \$nc('.moblilty_links, #cs_ncMobileSrc_caption').css('display', 'none');
            \$nc('.mobility_notMobile').css('display', '');
            \$nc('#mobility_text').css('color', '#aaa');
        }
    }

    \$nc(document).ready(function() {
        nc_mobile_change();
        \$nc('input[name=ncMobile]').change(function(){
            nc_mobile_change();
        });
    });

    </script>";

    if (!isset($Array['ncMobileIdentity'])) {
        $Array['ncMobileIdentity'] = null;
    }

    echo nc_get_modal_radio(
        'ncMobileIdentity',
        array(
            array(
                'attr' => array(
                    'value' => '1',
                    'class' => 'ncMobileIdentity',
                ),
                'desc' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_USERAGENT,
            ),

            array(
                'attr' => array(
                    'value' => '2',
                    'class' => 'ncMobileIdentity',
                    'id' => 'ncMobileCatalogue',
                ),
                'desc' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_SCREEN_RESOLUTION,
            ),

            array(
                'attr' => array(
                    'value' => '3',
                    'class' => 'ncMobileIdentity',
                ),
                'desc' => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_ALL_CRITERION,
            ),
        ),
        (int)$Array['ncMobileIdentity']
    );

    $mobile_fieldset = new nc_admin_fieldset(CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MOBILE_SETTINGS);
    $mobile_fieldset->add(ob_get_clean());
    $fieldsets->template->add($mobile_fieldset->result());

    if ($type == 1) {
        $action = "add";
        $nc_core->action = "add";
    }

    if ($type == 2) {
        $action = "change";
        $nc_core->action = "change";
        $message = $CatalogueID;
    }

    require $ROOT_FOLDER . "message_fields.php";

    if ($fldCount) {
        $fieldsets->new_fieldset('ext_fields', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_EXTFIELDS);
        ob_start();

        if ($type == 2) {
            $fieldQuery = implode(",", $fld);
            $fldValue = $db->get_row("SELECT " . $fieldQuery . " FROM `Catalogue` WHERE `Catalogue_ID`='" . $CatalogueID . "'", ARRAY_N);
        }

        echo "<table border='0' cellpadding='6' cellspacing='0' width='100%'><tr><td><font>";
        require $ROOT_FOLDER . "message_edit.php";
        echo "</td></tr></table>";
        $fieldsets->ext_fields->add(ob_get_clean())->show($display['fields']);
    }

    echo $fieldsets->to_string();

    if ($type == 1) {
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE,
            "action" => "mainView.submitIframeForm()",
        );
    } elseif ($type == 2) {
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
            "align" => "right",
            "action" => "mainView.submitIframeForm()",
        );
    }

    return 0;
}


/**
 * Обновление информации в БД после добавления/изменения сайта
 *
 * @param $CatalogueID
 * @param $type
 *
 * @return bool
 * @throws nc_Exception_DB_Error
 */
function ActionCatalogueCompleted($CatalogueID, $type) {
    global $perm, $nc_core, $db, $ROOT_FOLDER, $admin_mode;
    global $systemTableID, $systemTableName;
    global $FILES_FOLDER, $INCLUDE_FOLDER;
    global $FILECHMOD, $DIRCHMOD, $ADMIN_FOLDER, $MODULE_FOLDER;
    global $CatalogueID;

    $is_there_any_files = getFileCount(0, $systemTableID);
    $lm_type = $nc_core->page->get_field_name('last_modified_type');

    if ($type == 1) {
        $action = 'add';
        $nc_core->action = "add";
    }

    if ($type == 2) {
        $CatalogueID = (int)$CatalogueID;
        $action = 'change';
        $nc_core->action = "change";
        $message = $CatalogueID;
    }

    $sm_field = $nc_core->page->get_field_name('sitemap_include');
    $sm_change_field = $nc_core->page->get_field_name('sitemap_changefreq');
    $sm_priority_field = $nc_core->page->get_field_name('sitemap_priority');
    $is_saas_site = defined("NC_SAAS_SITE");

    $params = array(
        'Catalogue_Name',
        'Domain',
        'Template_ID',
        'Read_Access_ID',
        'Write_Access_ID',
        'Edit_Access_ID',
        'Subscribe_Access_ID',
        'Checked_Access_ID',
        'Delete_Access_ID',
        'Moderation_ID',
        'Checked',
        'Priority',
        'Mirrors',
        'Robots',
        'Cache_Access_ID',
        'Cache_Lifetime',
        'DisplayType',
        'TitleSubID',
        'TitleSubIDName',
        'TitleSubIDKeyword',
        'TitleTemplateID',
        'E404SubID',
        'E404SubIDName',
        'E404SubIDKeyword',
        'E404TemplateID',
        'RulesSubID',
        'RulesSubIDName',
        'RulesSubIDKeyword',
        'RulesTemplateID',
        'CommentsEditRules',
        'CommentAccessID',
        'CommentsDeleteRules',
        'posting',
        'last_modified_type',
        'DisallowIndexing',
        'ncOfflineText',
        'ncMobile',
        'ncMobileSrc',
        'ncMobileRedirect',
        'ncMobileIdentity',
        'Default_Class_ID',
        'ncMode'
    );

    if ($nc_core->modules->get_by_keyword('search')) {
        $params = array_merge($params, array($sm_field, $sm_change_field, $sm_priority_field));
    }

    foreach ($params as $v) {
        global $$v;
    }

    $st = new nc_Component(0, 1);

    foreach ($st->get_fields() as $v) {
        $name = 'f_' . $v['name'];

        global $$name;

        if ($v['type'] == NC_FIELDTYPE_FILE) {
            global ${$name . '_old'};
            global ${'f_KILL' . $v['id']};
        }

        if ($v['type'] == NC_FIELDTYPE_DATETIME) {
            global ${$name . '_day'};
            global ${$name . '_month'};
            global ${$name . '_year'};
            global ${$name . '_hours'};
            global ${$name . '_minutes'};
            global ${$name . '_seconds'};
        }
    }

    $Checked = (int)$Checked;
    $Mirrors = isset($Mirrors) ? str_replace(array('http://', 'https://', '/'), '', $Mirrors) : "";
    $Priority = (int)$Priority;
    $Template_ID = (int)$Template_ID;
    $posting = 1;

    $settings = $nc_core->template->get_custom_settings($Template_ID);

    if ($settings) {
        $entity = $Template_ID ?: null;
        $entity_type = $Template_ID ? 'template_settings' : null;

        $a2f = new nc_a2f($settings, 'TemplateSettings', false, $entity, $entity_type);

        if ($a2f->has_errors()) {
            $warnText = $a2f->get_validation_errors();
            $posting = 0;
        }

        $a2f->save_from_request_data('TemplateSettings');
        $TemplateSettings = $a2f->get_values_as_string();
    } else {
        $TemplateSettings = '';
    }

    require $ROOT_FOLDER . 'message_fields.php';

    if (!$posting) {
        nc_print_status($warnText, 'error');
        CatalogueForm($CatalogueID, 3, 'index.php', $type, $action);
        return false;
    }

    require $ROOT_FOLDER . 'message_put.php';

    if (nc_module_check_by_keyword('comments')) {
        include_once nc_module_folder('comments') . 'function.inc.php';
    }

    switch ($ncMobile) {
        case 2:
            $ncMobile = 0;
            $ncResponsive = 1;
            break;
        case 1:
            $ncMobile = 1;
            $ncResponsive = 0;
            break;
        default:
            $ncMobile = 0;
            $ncResponsive = 0;
            break;
    }

    if (empty($Robots)) {
        $Robots = "# " . CMS_SYSTEM_NAME_GLOBAL . " Robots file\nUser-agent: *\nDisallow: /install/\nSitemap: /sitemap.xml";

        if (nc_module_check_by_keyword('search')) {
            $nc_search_robots = new nc_search_robots();
            $Robots = $nc_search_robots->fill_autogenerated_section($CatalogueID, $Robots);
        }
    }

    if ($type == 1) {
        $insert = "INSERT INTO `Catalogue` (";

        for ($i = 0; $i < $fldCount; $i++) {
            if ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE) {
                continue;
            }

            $insert.= $fld[$i] . ",";
        }

        if (nc_module_check_by_keyword("cache")) {
            $insert.= "`Cache_Access_ID`, `Cache_Lifetime`,";
        }

        $insert.= "`Catalogue_Name`, `Domain`, `{$nc_core->page->get_field_name('language')}`, `Template_ID`,  `Read_Access_ID`, `Write_Access_ID`, `Edit_Access_ID`, `Checked_Access_ID`, `Delete_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `Checked`, `Priority`, `Created`, `Mirrors`, `Robots`, `{$lm_type}`, `TemplateSettings` , `ncOfflineText`, `ncMobile`, `ncMobileSrc`, `ncMobileRedirect`, `ncMobileIdentity`, `ncResponsive`, `ncHTTPS`, `Default_Class_ID`, `MainArea_Mixin_Settings`) ";
        $insert.= "VALUES (";

        for ($i = 0; $i < $fldCount; $i++) {
            if (
                $fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE ||
                ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
            ) {
                continue;
            }

            if (isset(${$fld[$i] . 'Defined'}) && ${$fld[$i] . 'Defined'}) {
                $insert.= ${$fld[$i] . 'NewValue'} . ',';
            } else {
                $insert .= $fldValue[$i] . ',';
            }
        }

        if (nc_module_check_by_keyword("cache")) {
            $insert .= "'" . (int)$Cache_Access_ID . "',";
            $insert .= "'" . (int)$Cache_Lifetime . "',";
        }

        $insert.= "'" . $db->escape($Catalogue_Name) . "',";
        $insert.= "'" . $db->escape($Domain) . "',";
        $insert.= "'" . $db->escape(($nc_core->input->fetch_get_post('language') != '' ? $nc_core->input->fetch_get_post('language') : MAIN_LANG)) . "',";
        $insert.= "'" . $db->escape($Template_ID) . "',";
        $insert.= "'" . (int)$Read_Access_ID . "',";
        $insert.= "'" . (int)$Write_Access_ID . "',";
        $insert.= "'" . (int)$Edit_Access_ID . "',";
        $insert.= "'" . (int)$Checked_Access_ID . "',";
        $insert.= "'" . (int)$Delete_Access_ID . "',";
        $insert.= "'" . (int)$Subscribe_Access_ID . "',";
        $insert.= "'" . (int)$Moderation_ID . "',";
        $insert.= "'" . (int)$Checked . "',";
        $insert.= "'" . (int)$Priority . "',";
        $insert.= "'" . date('Y-m-d H:i:s') . "',";
        $insert.= "'" . $db->escape($Mirrors) . "',";
        $insert.= "'" . $db->escape($Robots) . "',";
        $insert.= "'" . (int)$last_modified_type . "',";
        $insert.= "'" . $db->prepare($TemplateSettings) . "',";
        $insert.= "'" . $db->escape($ncOfflineText) . "',";
        $insert.= "'" . (int)$ncMobile . "',";
        $insert.= "'" . (int)$ncMobileSrc . "',";
        $insert.= "'" . (int)$ncMobileRedirect . "',";
        $insert.= "'" . (int)$ncMobileIdentity . "',";
        $insert.= "'" . (int)$ncResponsive . "',";
        $insert.= "'" . ($nc_core->input->fetch_get_post('ncHTTPS') ? '1' : '0') . "',";
        $insert.= "'" . (int)$Default_Class_ID . "',";
        $insert.= "'" . $db->prepare($nc_core->input->fetch_post('MainArea_Mixin_Settings')) . "'";
        $insert.= ")";

        $nc_core->event->execute(nc_Event::BEFORE_SITE_CREATED, 0);

        $db->query($insert);

        if ($db->is_error) {
            throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        $CatalogueID = (int)$db->insert_id;

        $nc_core->event->execute(nc_Event::AFTER_SITE_CREATED, $CatalogueID);

        $message = $CatalogueID;

        // Постобработка файлов с учетом нового $message
        $nc_core->files->field_save_file_afteraction($message);

        if (nc_module_check_by_keyword('comments')) {
            if ($CommentAccessID > 0) {
                $CommentRelationID = (int)nc_comments::addRule($db, array($message), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                $db->query("UPDATE `Catalogue` SET `Comment_Rule_ID` = '{$CommentRelationID}' WHERE `Catalogue_ID` = '{$message}'");
            }
        }

        // Проверка названия раздела
        if (!$TitleSubIDName || !$E404SubIDName || !$RulesSubIDName) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_NAME, 'error');
            return false;
        }

        // проверка символов для ключевого слова
        if (
            !$nc_core->subdivision->validate_hidden_url($TitleSubIDKeyword) ||
            !$nc_core->subdivision->validate_hidden_url($E404SubIDKeyword) ||
            !$nc_core->subdivision->validate_hidden_url($RulesSubIDKeyword)
        ) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
            return false;
        }

        // Добавление раздела для титульной страницы
        $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_CREATED, $CatalogueID, 0);

        $db->query("
            INSERT INTO `Subdivision`
            SET `Catalogue_ID` = '{$CatalogueID}',
                `Parent_Sub_ID` = 0,
                `Subdivision_Name` = '" . $db->escape($TitleSubIDName) . "',
                `Template_ID` = '" . (int)$TitleTemplateID . "',
                `Checked` = 0,
                `EnglishName` = '" . $db->escape($TitleSubIDKeyword) . "',
                `Hidden_URL` = '/" . $db->escape($TitleSubIDKeyword) . "/',
                `Priority` = 0
        ");

        $title_sub_id = $db->insert_id;
        $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_CREATED, $CatalogueID, $title_sub_id);

        // Добавление раздела для страницы с 404-ой ошибкой
        $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_CREATED, $CatalogueID, 0);

        $db->query("
            INSERT INTO `Subdivision`
            SET `Catalogue_ID` = '{$CatalogueID}',
                `Parent_Sub_ID` = 0,
                `Subdivision_Name` = '" . $db->escape($E404SubIDName) . "',
                `Template_ID` = '" . (int)$E404TemplateID . "',
                `Checked` = 0,
                `EnglishName` = '" . $db->escape($E404SubIDKeyword) . "',
                `Hidden_URL` = '/" . $db->escape($E404SubIDKeyword) . "/',
                `Priority` = 1
         ");

        $e404_sub_id = $db->insert_id;
        $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_CREATED, $CatalogueID, $e404_sub_id);

        // Добавление раздела для страницы с политикой конфиденциальности
        $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_CREATED, $CatalogueID, 0);
        $db->query("
            INSERT INTO `Subdivision`
            SET `Catalogue_ID` = '{$CatalogueID}',
                `Parent_Sub_ID` = 0,
                `Subdivision_Name` = '" . $db->escape($RulesSubIDName) . "',
                `Template_ID` = '" . (int)$RulesTemplateID . "',
                `Checked` = 0,
                `EnglishName` = '" . $db->escape($RulesSubIDKeyword) . "',
                `Hidden_URL` = '/" . $db->escape($RulesSubIDKeyword) . "/',
                `Priority` = 0
        ");
        $rules_sub_id = $db->insert_id;
        $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_CREATED, $CatalogueID, $rules_sub_id);

        // Для этого обновления не нужно вызывать трансляцию события
        $db->query(
            "UPDATE `Catalogue`
                    SET `Title_Sub_ID` = '{$title_sub_id}',
                        `E404_Sub_ID` = '{$e404_sub_id}',
                        `Rules_Sub_ID` = '{$rules_sub_id}'
                    WHERE `Catalogue_ID` = '{$CatalogueID}'"
        );

        nc_tpl_mixin_cache::generate_site_files($CatalogueID, true);
    }

    if ($type == 2) {
        $cur_checked = $db->get_var("SELECT `Checked` FROM `Catalogue` WHERE `Catalogue_ID` = '{$CatalogueID}'");
        if (nc_module_check_by_keyword('comments')) {
            $CommentData = nc_comments::getRuleData($db, array($CatalogueID));
            $CommentRelationID = $CommentData['ID'];

            switch (true) {
                case $CommentAccessID > 0 && $CommentRelationID:
                    nc_comments::updateRule($db, array($CatalogueID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                    break;
                case $CommentAccessID > 0 && !$CommentRelationID:
                    $CommentRelationID = nc_comments::addRule($db, array($CatalogueID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                    break;
                case $CommentAccessID <= 0 && $CommentRelationID:
                    nc_comments::dropRuleCatalogue($db, $CatalogueID);
                    $CommentRelationID = 0;
                    break;
            }
        }

        $update = " UPDATE `Catalogue` SET ";

        for ($i = 0; $i < $fldCount; $i++) {
            if ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE) {
                continue;
            }

            if (isset(${$fld[$i] . 'Defined'}) && ${$fld[$i] . 'Defined'}) {
                $update .= $fld[$i] . '=' . ${$fld[$i].'NewValue'} . ',';
            } else {
                $update .= $fld[$i] . '=' . $fldValue[$i] . ',';
            }
        }

        if (!$is_saas_site) {
            $update .= "`Catalogue_Name` = '" . $db->escape($Catalogue_Name) . "',";
            $update .= "`Domain` = '" . $db->escape($Domain) . "',";
            $update .= "`Mirrors` = '" . $db->escape($Mirrors) . "',";
            $update .= "`ncHTTPS` = '" . ($nc_core->input->fetch_get_post('ncHTTPS') ? '1' : '0') . "',";
        }

        $update.= "`Template_ID` = '" . (int)$Template_ID . "',";
        $update.= "`Read_Access_ID` = '" . (int)$Read_Access_ID . "',";
        $update.= "`Write_Access_ID` = '" . (int)$Write_Access_ID . "',";
        $update.= "`Edit_Access_ID` = '" . (int)$Edit_Access_ID . "',";
        $update.= "`Checked_Access_ID` = '" . (int)$Checked_Access_ID . "',";
        $update.= "`Delete_Access_ID` = '" . (int)$Delete_Access_ID . "',";
        $update.= "`Subscribe_Access_ID` = '" . (int)$Subscribe_Access_ID . "',";

        if (nc_module_check_by_keyword('cache')) {
            $update.= "`Cache_Access_ID` = '" . (int)$Cache_Access_ID . "',";
            $update.= "`Cache_Lifetime` = '" . (int)$Cache_Lifetime . "',";
        }

        if (nc_module_check_by_keyword('comments')) {
            $update.= "`Comment_Rule_ID` = '" . (int)$CommentRelationID . "',";
        }

        $update.= "`Moderation_ID` = '" . (int)$Moderation_ID . "',";
        $update.= "`Checked` = '" . (int)$Checked . "',";
        $update.= "`Priority` = '" . (int)$Priority . "',";
        $update.= "`Robots` = '" . $db->escape($Robots) . "',";
        $update.= "`Title_Sub_ID` = '" . (int)$TitleSubID . "',";
        $update.= "`E404_Sub_ID` = '" . (int)$E404SubID . "',";
        $update.= "`Rules_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('RulesSubID') . "',";
        $update.= "`{$lm_type}` = '" . (int)$last_modified_type . "',";

        if ($nc_core->modules->get_by_keyword('search')) {
            $update.= "`DisallowIndexing`= '" . (int)$DisallowIndexing . "',";
        }

        $update.= "`" . $db->escape($nc_core->page->get_field_name('language')) . "` = '" . $db->escape($nc_core->input->fetch_get_post('language')) . "',";

        if ($nc_core->modules->get_by_keyword('search')) {
            $update.= "`" . $sm_field . "` = '" . $db->escape($nc_core->input->fetch_get_post('sitemap_include')) . "',";
            $update.= "`" . $sm_change_field . "` = '" . $db->escape($nc_core->input->fetch_get_post('sitemap_changefreq')) . "',";
            $update.= "`" . $sm_priority_field . "` = '" . str_replace(',', '.', sprintf('%.1f', (float)$nc_core->input->fetch_get_post('sitemap_priority'))) . "',";
            $update.= "`Search_Result_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('SearchResultSubID') . "',";
        }

        if ($nc_core->modules->get_by_keyword('auth')) {
            $update.= "`Auth_Cabinet_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('AuthCabinetSubID') . "',";
            $update.= "`Auth_Profile_Modify_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('AuthProfileModifySubID') . "',";
            $update.= "`Auth_Signup_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('AuthSignupSubID') . "',";
        }

        if ($nc_core->modules->get_by_keyword('netshop')) {
            $update.= "`Netshop_Cart_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('NetshopCartSubID') . "',";
            $update.= "`Netshop_Order_Success_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('NetshopOrderSuccessSubID') . "',";
            $update.= "`Netshop_Order_List_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('NetshopOrderListSubID') . "',";
            $update.= "`Netshop_Compare_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('NetshopCompareSubID') . "',";
            $update.= "`Netshop_Favorites_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('NetshopFavoritesSubID') . "',";
            $update.= "`Netshop_Delivery_Sub_ID` = '" . (int)$nc_core->input->fetch_get_post('NetshopDeliverySubID') . "',";
        }

        $update.= "`TemplateSettings` = '" . $db->prepare($TemplateSettings) . "',";
        $update.= "`ncOfflineText` = '" . $db->escape($ncOfflineText) . "',";
        $update.= "`ncMobile` = '" . (int)$ncMobile . "',";
        $update.= "`ncMobileSrc` = '" . (int)$ncMobileSrc . "',";
        $update.= "`ncMobileRedirect` = '" . (int)$ncMobileRedirect . "',";
        $update.= "`ncMobileIdentity` = '" . (int)$ncMobileIdentity . "',";
        $update.= "`ncResponsive` = '" . (int)$ncResponsive . "',";
        $update.= "`DisplayType` = '" . $db->escape($DisplayType) . "',";        
        $update.= "`ncMode` = '" . $db->escape($ncMode) . "',";
        $update.= "`Default_Class_ID` = '" . (int)$Default_Class_ID . "',";
        $update.= "`MainArea_Mixin_Settings` = '" . $db->prepare($nc_core->input->fetch_post('MainArea_Mixin_Settings')) . "'";
        $update.= " WHERE `Catalogue_ID` = " . $CatalogueID;

        $nc_core->event->execute(nc_Event::BEFORE_SITE_UPDATED, $CatalogueID);

        if ($cur_checked != $Checked) {
            $nc_core->event->execute($Checked ? nc_Event::BEFORE_SITE_ENABLED : nc_Event::BEFORE_SITE_DISABLED, $CatalogueID);
        }

        $db->query($update);

        if ($db->is_error) {
            throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        $nc_core->event->execute(nc_Event::AFTER_SITE_UPDATED, $CatalogueID);

        // произошло включение / выключение
        if ($cur_checked != $Checked) {
            $nc_core->event->execute($Checked ? nc_Event::AFTER_SITE_ENABLED : nc_Event::AFTER_SITE_DISABLED, $CatalogueID);
        }

        nc_tpl_mixin_cache::generate_site_files($CatalogueID, false);
    }

    $current_versions_setting = $nc_core->get_settings('StoreVersions', 'system', false, $CatalogueID) ?: '0';
    $new_versions_setting = $nc_core->input->fetch_post('StoreVersions') ?: '0';

    if ($current_versions_setting != $new_versions_setting) {
        $nc_core->set_settings('StoreVersions', $new_versions_setting, 'system', $CatalogueID);
        if ($new_versions_setting) {
            $nc_core->version->save_initial_site_state($CatalogueID);
        } else {
            $nc_core->version->remove_site_versions($CatalogueID);
        }
    }

    return true;
}

function CheckIfDelete() {
    global $db;

    $nc_core = nc_Core::get_object();

    $input = $nc_core->input->fetch_get_post();
    if (!empty($input)) {
        foreach ($input as $key => $val) {
            if (nc_substr($key, 0, 6) == "Delete" && $val)
                return true;
        }
    }

    return false;
}

/*
 * Подтверждение удаления сайта
 */

function AscIfDelete($phase, $action) {
    global $db;
    global $UI_CONFIG;
    $ask = false;

    print "<form method='post' action='" . htmlspecialchars($action, ENT_QUOTES) . "'>";
    print "<ul>";

    $nc_core = nc_Core::get_object();

    $input = $nc_core->input->fetch_get_post();
    $cat_counter = 0;
    if (!empty($input)) {
        foreach ($input as $key => $val) {
            if (nc_substr($key, 0, 6) == "Delete" && $val) {
                $ask = true;
                $cat_id = intval(nc_substr($key, 6, nc_strlen($key) - 6));

                $Catalogue_Name = $db->get_var("SELECT `Catalogue_Name` FROM `Catalogue` WHERE `Catalogue_ID` = '" . $cat_id . "'");

                print "<li>" . $Catalogue_Name . "</li>";
                print "<input type='hidden' name='" . htmlspecialchars($key, ENT_QUOTES) . "' value='" . htmlspecialchars($val, ENT_QUOTES) . "'/>";
                $cat_counter++;
            }
        }
    }

    if (!$ask)
        return false;

    if ($cat_counter > 1) {
        $post_f1 = CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_I;
        $post_f2 = CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_U;
    }
    else {
        $post_f1 = null;
        $post_f2 = null;
    }

    print "</ul>";
    nc_print_status(sprintf(CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE, $post_f1, $post_f2), 'info');

    print "<br/><br/>
  " . $nc_core->token->get_input() . "
	<input type='hidden' name='phase' value='" . (int)$phase . "' />
	</form>";

    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CONFIRMDELETE,
        "action" => "mainView.submitIframeForm()",
        "red_border" => true,
    );

    return true;
}

/*
 * Обновление приоритета у сайта
 */

function UpdateCataloguePriority() {
    global $nc_core, $db;

    if (!empty($_POST)) {
        foreach ($_POST as $key => $val) {
            if (substr($key, 0, 8) == "Priority") {
                $cat_id = intval(substr($key, 8, strlen($key) - 8));
                $val += 0;

                // execute core action
                $nc_core->event->execute(nc_Event::BEFORE_SITE_UPDATED, $cat_id);

                $db->query("UPDATE `Catalogue` SET `Priority` = '" . $val . "', `LastUpdated` = `LastUpdated` WHERE `Catalogue_ID` = '" . $cat_id . "'");

                // execute core action
                $nc_core->event->execute(nc_Event::AFTER_SITE_UPDATED, $cat_id);
            }
        }
    }
}

/*
 * Удаления сайта
 */
function DeleteCatalogue() {
    global $UI_CONFIG, $nc_core;

    $deleted_ids = array();

    if (!empty($_POST)) {
        foreach ($_POST as $key => $site_id) {
            if (nc_substr($key, 0, 6) == "Delete" && $site_id) {
                $site_id += 0;

                try {
                    $site = $nc_core->catalogue->get_by_id($site_id);
                    $nc_core->version->init_changeset(NETCAT_VERSION_ACTION_DELETED . " " . sprintf(NETCAT_VERSION_OF_SITE, $site["Catalogue_Name"]));
                    CascadeDeleteCatalogue($site_id);
                    DeleteSystemTableFiles("Catalogue", $site_id);
                    $UI_CONFIG->treeChanges['deleteNode'][] = "site-" . $site_id;
                    $deleted_ids[] = $site_id;
                    $nc_core->version->commit_changeset();
                } catch (Exception $e) {
                    continue;
                }
            }
        }
    }

    $UI_CONFIG->deleteNavBarCatalogue = $deleted_ids;
    $nc_core->catalogue->load_all();

    nc_print_status(CONTROL_CONTENT_CATALOUGE_SUCCESS_DELETE);
}

/*
 * Страница информации о сайте
 */

function ShowMenu($CatalogueID, $phase1, $action1, $phase2, $action2) {
    global $db, $perm;
    global $EDIT_DOMAIN, $HTTP_ROOT_PATH, $ADMIN_PATH, $SUB_FOLDER;
    global $UI_CONFIG;

    $CatalogueID = intval($CatalogueID);
    $catalogue_url = nc_Core::get_object()->catalogue->get_url_by_id($CatalogueID);

    $is_admin = $perm->isCatalogueAdmin($CatalogueID);

    $Array = $db->get_row("SELECT * FROM `Catalogue` WHERE `Catalogue_ID`='" . $CatalogueID . "'");
    if (!$Array) {
        nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DBERROR, 'error');
        EndHtml();
        exit();
    }

    $countChild = HighLevelChildrenNumber($CatalogueID);
    $ModerationType = ($Array->Moderation_ID == 2) ? CLASSIFICATOR_TYPEOFMODERATION_MODERATION : CLASSIFICATOR_TYPEOFMODERATION_RIGHTAWAY;
    $UserGroupName = array(
            1 => CLASSIFICATOR_USERGROUP_ALL,
            2 => CLASSIFICATOR_USERGROUP_REGISTERED,
            3 => CLASSIFICATOR_USERGROUP_AUTHORIZED
    );

    //  In MySQL 4.1, TIMESTAMP display format changes to be the same as DATETIME.
    if (nc_strpos($Array->LastUpdated[4], '-')) {
        $Array->LastUpdated = nc_substr($Array->LastUpdated, 0, 4) . "-" . nc_substr($Array->LastUpdated, 4, 2) . "-" . nc_substr($Array->LastUpdated, 6, 2) . " " . nc_substr($Array->LastUpdated, 8, 2) . ":" . nc_substr($Array->LastUpdated, 10, 2) . ":" . nc_substr($Array->LastUpdated, 12, 2);
    }

    echo "<br />
	<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td>
	<table border='0' cellpadding='0' cellspacing='1' width='100%'><tr><td>
 	<table border='0' cellpadding='0' cellspacing='0' width='100%' class='border-bottom'>
 	<tr><td width='50%'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_CREATED . ":</td><td>" . $Array->Created . "</td></tr>
 	<tr><td>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_UPDATED . ":</td><td>" . $Array->LastUpdated . "</td></tr>
 	</table>
	</td></tr><tr><td>
 	<table border='0' cellpadding='0' cellspacing='0' width='100%' class='border-bottom'>
  <tr><td width='50%'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SECTIONSCOUNT . ":</td>";
    echo "<td>" . $countChild;

    if ($countChild)
        echo " ( <a href='" . $ADMIN_PATH . "subdivision/index.php?CatalogueID=" . $CatalogueID . "&amp;ParentSubID=0'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_LIST . "</a>
          " . ($is_admin ? ", <a href='" . $ADMIN_PATH . "subdivision/index.php?phase=2&amp;ParentSubID=0&amp;CatalogueID=" . $CatalogueID . "'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADD . "
          </a> )" : ")") . "";

    echo "</td></tr>
 	<tr><td>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SITESTATUS . ":</td><td>" . ($Array->Checked ? CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ON : CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_OFF ) . "</td></tr>
 	</table>
	</td></tr><tr><td>
 	<table border='0' cellpadding='0' cellspacing='0' width='100%' class='border-bottom'>
 	<tr><td width=50%>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_READACCESS . ":</td><td>" . $UserGroupName[$Array->Read_Access_ID] . " " . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS . "</td></tr>
  <tr><td>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADDACCESS . ":</td><td>" . $UserGroupName[$Array->Write_Access_ID] . " " . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS . "</td></tr>
 	<tr><td>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_EDITACCESS . ":</td><td>" . $UserGroupName[$Array->Edit_Access_ID] . " " . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS . "</td></tr>
 	<tr><td>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SUBSCRIBEACCESS . ":</td><td>" . $UserGroupName[$Array->Subscribe_Access_ID] . " " . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS . "</td></tr>
 	<tr><td>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_PUBLISHACCESS . ":</td><td>" . $ModerationType . "</td></tr>
 	</table>
	</td></tr></table></td></tr></table>";

    $UI_CONFIG->actionButtons[] = array(
        "id" => "delete",
        "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_DELETE,
        "location" => "site.delete(" . $CatalogueID . ")",
        "red_border" => true,
    );
    $UI_CONFIG->actionButtons[] = array(
        "id" => "preview",
        "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_VIEW,
        "action" => "urlDispatcher.load('{$catalogue_url}{$SUB_FOLDER}" . (nc_strlen(session_id()) > 0 ? "?" . session_name() . "=" . session_id() . "" : "") . "', '1')"
    );
}

##############################################
# Проверка домена на корректность
##############################################

function getDomainsFromDatabase() {
    return nc_Core::get_object()->db->get_col("SELECT Domain FROM Catalogue");
}

function getCurrentDomainFromDatabase() {
    return nc_Core::get_object()->catalogue->get_by_id($CatalogueID, 'Domain');
}

function checkDomain($domain, $CatalogueID, $result_array=false) {
    $result        = '';
    $data          = array(
        'text' => '',
        'link' => '',
    );
    $uniqueFileUrl = createUniqueFile();

    switch (true) {
        case !isFieldSet($domain):
            $data['text'] = CONTROL_CONTENT_CATALOUGE_ERROR_DOMAIN_NOT_SET;
            break;

        case !$uniqueFileUrl:
            $data['text'] = NETCAT_ADMIN_NOTICE_RIGHTS.' '.nc_Core::get_object()->HTTP_FILES_PATH;
            break;

        case !isDomainCorrect($domain, $uniqueFileUrl):
            $data['text'] = CONTROL_CONTENT_CATALOUGE_ERROR_INCORRECT_DOMAIN;
            $data['link'] = nc_Core::get_object()->ADMIN_PATH."catalogue/index.php?phase=2&type=2&CatalogueID={$CatalogueID}";

        default:
            deleteUniqueFile($uniqueFileUrl);
            break;
    }

    if ($result_array) {
        return $data;
    }

    if ($data['link']) {
        return " ( <a style='vertical-align:top' href='{$data['link']}'>{$data['text']}</a>";
    }

    return $data['text'] ? ' (' . $data['text'] . ')' : '';
}

function createUniqueFile() {
    $DOCUMENT_ROOT = nc_Core::get_object()->DOCUMENT_ROOT;
    $HTTP_FILES_PATH = nc_Core::get_object()->HTTP_FILES_PATH;

    $fileUrl = $HTTP_FILES_PATH.md5(time());

    if (@file_put_contents($DOCUMENT_ROOT.$fileUrl, '') === false) {
        return false;
    }

    return $fileUrl;
}

function deleteUniqueFile($fileUrl) {
    @unlink(nc_Core::get_object()->DOCUMENT_ROOT.$fileUrl);
    return true;
}

function isDomainCorrect($domain, $fileUrl) {
    if (!preg_match("~^([a-zа-яё\d-]+\.)+[a-zа-яё\d-]+$~iu", $domain)) {
        return false;
    }

    $domain = encode_host($domain);

    return checkFile($domain, $fileUrl);
}

function isFieldSet($domain) {
    return !empty($domain);
}

function checkFile($domain, $fileUrl) {
    return $fileUrl ? (extension_loaded('curl') ? checkFileByCurl($domain, $fileUrl) : checkFileByGetHeaders($domain, $fileUrl)) : false;
}

function checkFileByCurl($domain, $fileUrl) {
    if (function_exists('curl_init')) {
        $curlConnection = curl_init();

        curl_setopt($curlConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlConnection, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($curlConnection, CURLOPT_HEADER, 1);
        curl_setopt($curlConnection, CURLOPT_NOBODY, true);
        curl_setopt($curlConnection, CURLOPT_TIMEOUT, '3');
        curl_setopt($curlConnection, CURLOPT_URL, nc_Core::get_object()->catalogue->get_url_by_host_name($domain) . $fileUrl);

        $responseHeaders = curl_exec($curlConnection);

        curl_close($curlConnection);

        return isHeaderOk($responseHeaders);
    }

    return false;
}

function checkFileByGetHeaders($domain, $fileUrl) {
    $responseHeaders = @get_headers(nc_Core::get_object()->catalogue->get_url_by_host_name($domain) . $fileUrl);
    return isHeaderOk($responseHeaders[0]);
}

function isHeaderOk($header) {
    return strpos($header, '200');
}

class ui_config_catalogue extends ui_config {

    /**
     * Constructor
     * @param string [site]
     * @param int catalogue id
     */
    public function __construct($active_tab, $catalogue_id, $action = '', $parent_sub_id = '', $active_toolbar = null) {
        global $perm;

        $catalogue_id = +$catalogue_id;
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $is_admin = $perm->isCatalogueAdmin($catalogue_id);
        if (nc_module_check_by_keyword('stats')) {
            $nc_stat_stat_module = ($nc_core->get_settings('NC_Stat_Enabled', 'stats') == 1) && $perm->isSupervisor();
            $openstat_stat_module = ($nc_core->get_settings('Openstat_Enabled', 'stats') == 1) && $perm->isSupervisor();
        } else {
            $nc_stat_stat_module = $openstat_stat_module = false;
        }

        $catalogue = $db->get_row("SELECT Catalogue_Name
  	  	                         FROM Catalogue
    		                    WHERE Catalogue_ID = $catalogue_id", ARRAY_A);

        if ($active_tab == 'edit' || $active_tab == 'map' || $active_tab == 'demo_content' || $active_tab == 'info' || $active_tab == 'seo' || $active_tab == 'stat' || $active_tab == 'area') {
            global $HTTP_HOST;
            $scheme = $nc_core->catalogue->get_scheme_by_id($catalogue_id);
            $nc_mode = $nc_core->catalogue->get_by_id($catalogue_id, 'ncMode');

            if ($active_tab == 'edit') {
                $this->toolbar = array();
                $toolbar = array('design', 'edit', 'seo', 'system', 'fields');

                if (!$action) {
                    $action = 'edit';
                }

                foreach ($toolbar as $v) {
                    $this->toolbar[] = array(
                            'id' => $v,
                            'caption' => constant("SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_" . strtoupper($v)),
                            'location' => "catalogue." . $v . "(" . $catalogue_id . ")",
                            'group' => "grp1");
                    if ($action === $v) {
                        $this->activeToolbarButtons[] = $v;
                    }
                }
            }

            $this->headerText = $catalogue["Catalogue_Name"];

            $this->tabs[] = array('id' => 'map',
                    'caption' => SITE_TAB_SITEMAP,
                    'location' => "site.map($catalogue_id)");

            if ($nc_mode == 'template_development' || $nc_mode == 'demo') {
                $this->tabs[] = array('id' => 'demo_content',
                        'caption' => SITE_TAB_DEMO_CONTENT,
                        'location' => "site.demo_content($catalogue_id)");
            }

            if ($is_admin) {
                $this->tabs[] = array('id' => 'edit',
                        'caption' => SITE_TAB_SETTINGS,
                        'location' => "catalogue.design($catalogue_id)");

            }
            if ($perm->isSupervisor()) {
                $this->tabs[] = array('id' => 'area',
                    'caption' => SITE_TAB_AREA_INFOBLOCKS,
                    'location' => "site.area($catalogue_id)");
            }

            if ($openstat_stat_module) {
                $this->tabs[] = array('id' => 'stat',
                        'caption' => SITE_TAB_STATS,
                        'location' => "site.stat.openstat(" . $catalogue_id . ")");
            } elseif ($nc_stat_stat_module) {
                $this->tabs[] = array('id' => 'stat',
                        'caption' => SITE_TAB_STATS,
                        'location' => "site.stat.nc_stat(" . $catalogue_id . ")");
            }

            // 5. Просмотр
            $catalogue_object = nc_Core::get_object()->catalogue->get_by_id($catalogue_id);
            $this->tabs[] = array('id' => 'view' . (!$catalogue_object['Domain'] ? '_site_not_active' : ''),
                'caption' => STRUCTURE_TAB_PREVIEW_SITE,
                'action' => "window.open('{$scheme}://" .
                    $catalogue_object['Domain'] .
                    "');"
            );

            if ($active_tab == 'info') {
                $this->toolbar = array(
                        array(
                                'id' => "info",
                                'caption' => SITE_TOOLBAR_INFO,
                                'location' => "site.info($catalogue_id)",
                                'group' => "grp1"),
                        array(
                                'id' => "sublist",
                                'caption' => SITE_TOOLBAR_SUBLIST,
                                'location' => "site.sublist($catalogue_id,0)",
                                'group' => "grp1"));
                if ($action === 'sublist') {
                    $this->locationHash = "site.$action($catalogue_id,$parent_sub_id)";
                    $this->activeToolbarButtons[] = "sublist";
                } else {
                    $this->activeToolbarButtons[] = "info";
                }
            } elseif ($active_tab == 'stat') {
                $this->toolbar = array();
                if ($openstat_stat_module) {
                    $this->toolbar[] = array(
                            'id' => "openstat",
                            'caption' => NETCAT_MODULE_STATS_ADMIN_TAB_OPENSTAT,
                            'location' => "site.stat.openstat(" . $catalogue_id . ")",
                            'group' => "stats");
                }
                if ($nc_stat_stat_module) {
                    $this->toolbar[] = array(
                            'id' => "nc_stat",
                            'caption' => NETCAT_MODULE_STATS_ADMIN_TAB_NC_STAT,
                            'location' => "site.stat.nc_stat(" . $catalogue_id . ")",
                            'group' => "stats");
                }
                $this->activeToolbarButtons[] = $active_toolbar;
            }
        }

        if ($active_tab == 'add') {
            $this->headerText = CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE;
            $this->tabs = array(
                    array(
                            'id' => 'add',
                            'caption' => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE,
                            'location' => "site.add()",
                            'align' => 'left'));
        }

        if ($active_tab == 'delete') {
            $this->headerText = CONTROL_CONTENT_CATALOUGE_SITEDELCONFIRM;
            $this->tabs = array(
                    array(
                            'id' => 'delete',
                            'caption' => CONTROL_CONTENT_CATALOUGE_SITEDELCONFIRM,
                            'location' => "site.delete($catalogue_id)"));
        }

        if ($action != 'sublist') {
            if($active_tab == 'edit') {
                $this->locationHash = "catalogue." . $action . "(" . $catalogue_id . ")";
            } else {
                $this->locationHash = "site." . $active_tab . ($active_toolbar ? "." . $active_toolbar : "") . "($catalogue_id)";
            }
        }

        $this->activeTab = $active_tab;
        $this->treeMode = 'sitemap';
        $this->treeSelectedNode = "site-" . $catalogue_id;
    }

}

class ui_list_catalogue extends ui_config {

    /**
     * Construct
     *
     *
     */
    public function __construct() {
        $this->tabs[] = array('id' => 'list',
                'caption' => SECTION_INDEX_SITE_LIST,
                'location' => "site.list()");
        $this->activeTab = 'list';

        $this->locationHash = "site.list()";
        $this->headerText = SECTION_INDEX_SITE_LIST;
        $this->treeMode = 'sitemap';


        $this->actionButtons[] = array("id" => "submit",
                "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SAVE,
                "action" => "mainView.submitIframeForm()",
                "align" => "right");

        if (nc_core::get_object()->catalogue->can_create()) {
            $this->actionButtons[] = array("id" => "add",
                "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE,
                "action" => "urlDispatcher.load('site.add()')",
                "align" => "left");
        }
    }

}
