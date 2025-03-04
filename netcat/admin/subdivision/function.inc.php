<?php

if (!class_exists("nc_System")) die("Unable to load file.");

$SubdivisionID = isset($SubdivisionID) ? $SubdivisionID : 0;
$systemMessageID = isset($systemMessageID) ? $systemMessageID : 0;
if (!$systemMessageID) {
    $systemMessageID = $SubdivisionID;
}
$systemTableName = "Subdivision";
$systemTableID = 2;
//ui class
require_once "ui.php";
require_once "favorite.inc.php";


# покажем список разделов в виде таблицы

function ShowSubdivisionList() {
    global $db, $nc_core;
    global $EDIT_DOMAIN, $HTTP_ROOT_PATH, $SUB_FOLDER;
    global $loc, $perm;
    global $ADMIN_PATH, $ADMIN_TEMPLATE, $UI_CONFIG;

    $Result = array();
    $subs_children_count = array();
    $subs_one_regular_infoblock = array();

    if (!empty($loc->CatalogueID) && !empty($loc->ParentSubID)) {
        $Result = (array)$db->get_results("select a.Subdivision_ID,a.Subdivision_Name,a.Priority,a.Checked,a.Hidden_URL,b.Domain,a.Catalogue_ID,a.ExternalURL from Subdivision AS a,Catalogue AS b where a.Parent_Sub_ID=".$loc->ParentSubID." and a.Catalogue_ID=".$loc->CatalogueID." AND a.Catalogue_ID=b.Catalogue_ID ORDER BY a.Priority", ARRAY_N);
        $subs_children_count = GetSubSubsChildrenCount($loc->ParentSubID);
        $subs_one_regular_infoblock = GetSubSubsOneRegularInfoblock($loc->ParentSubID);
    } else {
        if (!empty($loc->ParentSubID)) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_FUNCS_NOSECTIONS, 'info');
        } else {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_FUNCS_NOSUBSECTIONS, 'info');
        }
    }

    if (count($Result)) { ?>
        <form method=post action=index.php>
            <table border=0 cellpadding=0 cellspacing=0 width=100% class='border-bottom'>
                <tr>
                    <td>
                        <table border=0 cellpadding=0 cellspacing=0 width=100%>
                            <tr>
                                <td>ID</td>
                                <td width=60%><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION ?></td>
                                <td><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_SUBSECTIONS ?></td>
                                <td align=center><div class='icons icon_prior' title='<?=CONTROL_CONTENT_SUBDIVISION_FUNCS_PRIORITY ?>'></div></td>
                                <td align=center><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_GOTO ?></td>
                                <td align=center><div class='icons icon_delete' title='<?= CONTROL_CONTENT_SUBDIVISION_FUNCS_DELETE ?>'></div></td>
                            </tr>
                            <?php
                            foreach ($Result as $Array) {
                                $children_count = isset($subs_children_count[$Array[0]]['ChildrenCount']) ? $subs_children_count[$Array[0]]['ChildrenCount'] : ChildrenNumber($Array[0]);
                                $cc_id = isset($subs_one_regular_infoblock[$Array[0]]['Sub_Class_ID']) ? $subs_one_regular_infoblock[$Array[0]]['Sub_Class_ID'] : 0;
                                print "<tr><td bgcolor=white><font size=-2".(!$Array[3] ? " color=cccccc" : "")."><b>".$Array[0]."</td>";
                                print "<td><a href=\"index.php?phase=4&SubdivisionID=".$Array[0]."\">".(!$Array[3] ? "<font color=cccccc>" : "").$Array[1]."</a></td>";
                                print "<td><font size=-2><a href=index.php?phase=1&CatalogueID=".$loc->CatalogueID."&ParentSubID=".$Array[0].">".(!$Array[3] ? "<font color=cccccc>" : "").(!$children_count ? CONTROL_CONTENT_SUBDIVISION_FUNCS_NONE : CONTROL_CONTENT_SUBDIVISION_FUNCS_LIST." (".$children_count.")")."</a></td>";
                                print "<td align=center><input type=text name=Priority".$Array[0]." size=3 class=s value='".$Array[2]."'></td>";

                                print "<td align=center>";

                                //setup
                                print "<a href='index.php?view=edit&phase=5&SubdivisionID=".$Array[0]."''><div class='icons icon_settings' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONS."'></div></a>";

                                //edit

                                if ($cc_id) {
                                    print "<a href='" . nc_get_scheme() . '://' . $EDIT_DOMAIN . $SUB_FOLDER . $HTTP_ROOT_PATH ."?inside_admin=1&cc=" . $cc_id . (strlen(session_id()) > 0 ? "&" . session_name() . "=" . session_id() . "" : "") . "'><div class='icons icon_pencil' title='" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_EDIT . "'></div></a>";
                                } else {
                                    print "<img src=".$ADMIN_PATH."images/emp.gif width=16 height=16>";
                                }

                                //browse
                                $show_url_arr = array("Hidden_URL" => $Array[4], "Domain" => $Array[5], "ExternalURL" => $Array[7], "Subdivision_ID" => $Array[0], "Catalogue_ID" => $Array[6]);
                                print "<a href='".nc_subdivision_preview_link($show_url_arr)."' target='_blank'><div class='icons icon_preview' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOVIEW."'></div></a>";

                                print"</td>";

                                print "<td bgcolor=white align=center><input type=checkbox name=Delete".$Array[0]." value=".$Array[0]."></td>\n";
                                print "</tr>\n";
                            }
                            ?>
                        </table>
                    </td>
                </tr>
            </table>
            <input type='hidden' name='phase' value='7'>
            <input type='hidden' name='CatalogueID' value='<?= $loc->CatalogueID; ?>'>
            <input type='hidden' name='ParentSubID' value='<?= $loc->ParentSubID; ?>'>
            <input type='submit' class='hidden'>
            <?= $nc_core->token->get_input(); ?>
        </form>
    <?
        $UI_CONFIG->actionButtons[] = array(
            "id" => "delete",
            "caption" => CONTROL_CONTENT_SUBDIVISION_FUNCS_SAVE,
            "action" => "mainView.submitIframeForm()",
            "align" => "right"
        );
    }
    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION,
        "location" => "subdivision.add($loc->ParentSubID, $loc->CatalogueID)",
        "align" => "left"
    );
}

###############################################################################
# добавим/изменим раздел

    function ActionSubdivisionCompleted($type) {
        global $HTTP_ROOT_PATH, $HTTP_DOMAIN, $SUB_FOLDER;
        global $loc, $perm, $admin_mode, $nc_core;
        global $db, $ROOT_FOLDER, $FILECHMOD, $DIRCHMOD;
        global $systemTableID, $systemTableName, $systemMessageID;
        global $FILES_FOLDER, $INCLUDE_FOLDER, $MODULE_FOLDER, $ADMIN_FOLDER;

        $is_there_any_files = getFileCount(0, $systemTableID);
        $lm_type = $nc_core->page->get_field_name('last_modified_type');
        $sm_field = $nc_core->page->get_field_name('sitemap_include');
		$sm_change_field = $nc_core->page->get_field_name('sitemap_changefreq');
		$sm_priority_field = $nc_core->page->get_field_name('sitemap_priority');

        $params = array('Subdivision_Name', 'EnglishName', 'TemplateID', 'ReadAccessID',
                'WriteAccessID', 'EditAccessID', 'SubscribeAccessID',
                'CheckedAccessID', 'DeleteAccessID', 'ModerationID', 'Checked',
                'Priority', 'ExternalURL', 'UseMultiSubClass', 'CacheAccessID', 'CacheLifetime', 'CatalogueID',
                'SubdivisionID', 'ParentSubID', 'UseEditDesignTemplate', 'Title',
                'Keywords', 'Description', 'CommentsEditRules', 'CommentAccessID', 'CommentsDeleteRules',
                'posting', 'last_modified_type', 'language', 'title', 'keywords', 'description',
                'DisallowIndexing', $sm_field, $sm_change_field, $sm_priority_field);

        foreach ($params as $v)
            $$v = $nc_core->input->fetch_get_post($v);

        $st = new nc_Component(0, 2);
        foreach ($st->get_fields() as $v) {
            $name = 'f_'.$v['name'];
            global $$name;
            if ($v['type'] == NC_FIELDTYPE_FILE) {
                global ${$name."_old"};
                global ${"f_KILL".$v['id']};
            }
        }

        $Priority += 0;

        if ($type == 1) {
            $action = "add";
            $nc_core->action = "add";
        }

        if ($type == 2) {
            $action = "change";
            $nc_core->action = "change";
            $message = $loc->SubdivisionID;
        }

        $component = new nc_Component(0, 2);
        $fl = $component->get_fields();

        // prepare template custom settings
        $settings = $nc_core->template->get_custom_settings($TemplateID);
        if ($settings) {
            $entity = $Template_ID ?: null;
            $entity_type = $Template_ID ? 'template_settings' : null;

            $a2f = new nc_a2f($settings, 'TemplateSettings', false, $entity, $entity_type);
            if (!$a2f->validate($_POST['TemplateSettings'])) {
                $warnText = $a2f->get_validation_errors();
                $posting = 0;
            }
            $a2f->save_from_request_data('TemplateSettings');
            $TemplateSettings = $a2f->get_values_as_string();
        } else {
            $TemplateSettings = "";
        }

        require $ROOT_FOLDER."message_fields.php";

        if ($posting == 0) {
            nc_print_status($warnText, 'error');
            SubdivisionForm($phase, "index.php", $type);
            return false;
        }

        if (nc_module_check_by_keyword("comments")) {
            include_once nc_module_folder('comments') . 'function.inc.php';
        }

        require $ROOT_FOLDER."message_put.php";

        $db->last_error = '';

        if ($type == 1) {
            $insert = "INSERT INTO `Subdivision` SET ";
            // fields from system table component (2)
            for ($i = 0; $i < $fldCount; $i++) {
                if (
                    $fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE ||
                    ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_ADMIN && !nc_field_check_admin_perm())
                ) {
                    continue;
                }

// quotes added into the message_put.php!
                if (isset(${$fld[$i].'Defined'}) && ${$fld[$i].'Defined'} == true) {
                  $insert.= "`".$fld[$i]."` = ".${$fld[$i].'NewValue'}.", ";
                } else {
                  $insert.= "`".$fld[$i]."` = ".$fldValue[$i].", ";
                }
            }
            if (nc_module_check_by_keyword("cache")) {
                $insert .= "`Cache_Access_ID` = '".$CacheAccessID."', ";
                $insert .= "`Cache_Lifetime` = '".$CacheLifetime."',";
            }


            $insert.= "`Catalogue_ID`  = '".$loc->CatalogueID."',";
            $insert.= "`Parent_Sub_ID` = '".$loc->ParentSubID."',";
            $insert.= "`Subdivision_Name` = '".$Subdivision_Name."',";
            $insert.= "`Template_ID` = '".$TemplateID."',";
            $insert.= "`Read_Access_ID` = '".$ReadAccessID."',";
            $insert.= "`Write_Access_ID` = '".$WriteAccessID."',";
            $insert.= "`Edit_Access_ID` = '".$EditAccessID."',";
            $insert.= "`Checked_Access_ID` = '".$CheckedAccessID."',";
            $insert.= "`Delete_Access_ID` = '".$DeleteAccessID."',";
            $insert.= "`Subscribe_Access_ID` = '".$SubscribeAccessID."',";
            $insert.= "`Moderation_ID` = '".$ModerationID."',";
            $insert.= "`Checked` = '".$Checked."',";
            $insert.= "`ExternalURL` = '".$ExternalURL."',";
            $insert.= "`EnglishName` = '".$EnglishName."',";
            $insert.= "`Favorite` = '".$Favorite."',";
            $insert.= "`Created` = '".date("Y-m-d H:i:s")."',";
            $insert.= "`Priority` = '".$Priority."',";
            $insert.= "`UseMultiSubClass` = '".$UseMultiSubClass."',";
            $insert.= "`UseEditDesignTemplate` = '".$UseEditDesignTemplate."',";
            $insert.= "`".$lm_type."` = '".intval($last_modified_type)."',";
            $insert.= "`TemplateSettings` = '".$db->prepare($TemplateSettings)."'";

            // execute core action
            $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_CREATED, $loc->CatalogueID, 0);

            $Result = $db->query($insert);
            $systemMessageID = $db->insert_id;

            //sql error
            if ($db->last_error) return false;

            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_CREATED, $loc->CatalogueID, $systemMessageID);

            $message = $systemMessageID;

            //постобработка файлов с учетом нового $message
            $nc_core->files->field_save_file_afteraction($message);

            // end set insert_id block

            if (nc_module_check_by_keyword("comments")) {
                if ($CommentAccessID > 0) {
                    // add comment relation
                    $CommentRelationID = nc_comments::addRule($db, array($loc->CatalogueID, $message), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                    // update inserted data
                    $db->query("UPDATE `Subdivision` SET `Comment_Rule_ID` = '".(int) $CommentRelationID."' WHERE `Subdivision_ID` = '".(int) $message."'");
                }
            }

            nc_tpl_mixin_cache::generate_subdivision_files($message, false);
        }
        if ($type == 2) {
            $cur_checked = $db->get_var("SELECT `Checked` FROM `Subdivision` WHERE `Subdivision_ID` = '".$loc->SubdivisionID."'");
            if (nc_module_check_by_keyword("comments")) {
                // get rule id
                $CommentData = nc_comments::getRuleData($db, array($loc->CatalogueID, $loc->SubdivisionID));
                $CommentRelationID = $CommentData['ID'];
                // do something
                switch (true) {
                    case $CommentAccessID > 0 && $CommentRelationID:
                        // update comment rules
                        nc_comments::updateRule($db, array($loc->CatalogueID, $loc->SubdivisionID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                        break;
                    case $CommentAccessID > 0 && !$CommentRelationID:
                        // add comment relation
                        $CommentRelationID = nc_comments::addRule($db, array($loc->CatalogueID, $loc->SubdivisionID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                        break;
                    case $CommentAccessID <= 0 && $CommentRelationID:
                        // delete comment rules
                        nc_comments::dropRuleSubdivision($db, $loc->SubdivisionID);
                        $CommentRelationID = 0;
                        break;
                }
            }

            $update = "UPDATE `Subdivision` SET ";
            for ($i = 0; $i < $fldCount; $i++) {
                if ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE) {
                    continue;
                }
                $update.= "`".$fld[$i]."` = ".$fldValue[$i].", ";
            }

            if (!empty($fl)) {
                foreach ($fl as $field) {
                    if ($field['usage']) {
                        $update .="`".$db->escape($field['name'])."` = '".$db->escape($nc_core->input->fetch_get_post($field['name']))."', ";
                    }
                }
            }

            $update.= "`Subdivision_Name`= '".$Subdivision_Name."',";
            $update.= "`ExternalURL`= '".$ExternalURL."',";
            $update.= "`EnglishName` = '".$EnglishName."',";
            $update.= "`Template_ID` = ".$TemplateID.",";
            $update.= "`Read_Access_ID` = '".$ReadAccessID."',";
            $update.= "`Write_Access_ID` = '".$WriteAccessID."',";
            $update.= "`Edit_Access_ID` = '".$EditAccessID."',";
            $update.= "`Checked_Access_ID` = '".$CheckedAccessID."',";
            $update.= "`Delete_Access_ID` = '".$DeleteAccessID."',";
            $update.= "`Subscribe_Access_ID` = '".$SubscribeAccessID."',";
            if (nc_module_check_by_keyword("cache")) {
                $update.= "`Cache_Access_ID` = '".$CacheAccessID."',";
                $update.= "`Cache_Lifetime` = '".$CacheLifetime."',";
            }
            if (nc_module_check_by_keyword("comments")) {
                $update.= "`Comment_Rule_ID` = '".$CommentRelationID."',";
            }
            $update.= "`Moderation_ID` = '".$ModerationID."',";
            $update.= "`Checked` = '".$Checked."',";
            $update.= "`Priority`= ".$Priority.",";
            $update.= "`Favorite`= '".$Favorite."',";
            $update.= "`UseMultiSubClass`= '".$UseMultiSubClass."',";
            $update.= "`UseEditDesignTemplate`= '".$UseEditDesignTemplate."',";
            $update.= "`".$sm_field."`= '".$nc_core->input->fetch_get_post('sitemap_include')."',";
            $update.= "`".$nc_core->page->get_field_name('language')."` = '".$db->escape($language)."',";
            $update.= "`".$nc_core->page->get_field_name('title')."` = '".$db->escape($title)."',";
            $update.= "`".$nc_core->page->get_field_name('keywords')."` = '".$db->escape($keywords)."',";
            $update.= "`".$nc_core->page->get_field_name('description')."` = '".$db->escape($description)."',";
            $update.= "`".$lm_type."` = '".intval($last_modified_type)."',";
            if ($nc_core->modules->get_by_keyword('search')) {
				$update.= "`DisallowIndexing`= '".intval($DisallowIndexing)."',";
				$update.= "`" . $sm_field . "` = '" . $nc_core->input->fetch_get_post('sitemap_include') . "',";
				$update.= "`" . $sm_change_field . "` = '" . $nc_core->input->fetch_get_post('sitemap_changefreq') . "',";
				$update.= "`" . $sm_priority_field . "` = '" . str_replace(',', '.', sprintf("%.1f", doubleval($nc_core->input->fetch_get_post('sitemap_priority')))) . "',";
			}
            $update.= "`TemplateSettings` = '".$db->prepare($TemplateSettings)."'";
            $update.= " WHERE `Subdivision_ID` = ".$loc->SubdivisionID;
            // execute core action
            $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $loc->CatalogueID, $loc->SubdivisionID);
            $nc_core->event->execute($Checked ? nc_Event::BEFORE_SUBDIVISION_ENABLED : nc_Event::BEFORE_SUBDIVISION_DISABLED, $loc->CatalogueID, $loc->SubdivisionID);

            $Result = $db->query($update);

            //sql error
            if ($db->last_error) return false;

            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $loc->CatalogueID, $loc->SubdivisionID);

            // произошло включение / выключение
            if ($cur_checked != $Checked) {
                $nc_core->event->execute($Checked ? nc_Event::AFTER_SUBDIVISION_ENABLED : nc_Event::AFTER_SUBDIVISION_DISABLED, $loc->CatalogueID, $loc->SubdivisionID);
            }

            nc_tpl_mixin_cache::generate_subdivision_files($loc->SubdivisionID, false);

            $changed_cc = array();

            // RSS
            $cc_in_sub = $db->get_results("SELECT `Sub_Class_ID` as `id`, `AllowRSS` as `cur` FROM `Sub_Class` WHERE `Subdivision_ID` = '".$loc->SubdivisionID."' ", ARRAY_A);
            if (!empty($cc_in_sub))
                    foreach ($cc_in_sub as $v) {
                    // значение, пришедшие из формы
                    $allow_rss = intval($nc_core->input->fetch_get_post('AllowRSS'.$v['id']));
                    // в случае, если значение изменилось
                    if ($allow_rss != $v['cur']) {
                        $db->query("UPDATE `Sub_Class` SET `AllowRSS` = '".$allow_rss."' WHERE `Sub_Class_ID` = '".$v['id']."' ");
                        $changed_cc[] = $v['id'];
                    }
                }

            // визуальные настройки
            $CustomSettings = "";
            if ($nc_core->input->fetch_get_post('custom_subclass_id')) {
                $settings = $db->get_var("SELECT `CustomSettingsTemplate` FROM `Class`
                                WHERE `Class_ID` = '".intval($nc_core->input->fetch_get_post('custom_class_id'))."'");
                if ($settings) {
                    $entity = $nc_core->input->fetch_get_post('custom_subclass_id') ?: null;
                    $entity_type = $nc_core->input->fetch_get_post('custom_subclass_id') ? 'class_settings' : null;

                    $a2f = new nc_a2f($settings, 'CustomSettings', false, $entity, $entity_type);
                    if (!$a2f->validate($_POST['CustomSettings'])) {
                        $error = $a2f->get_validation_errors();
                        nc_print_status($error, 'error');
                    } else {
                        $a2f->save_from_request_data('CustomSettings');
                        $CustomSettings = $a2f->get_values_as_string();
                        $cur_settings = $db->get_var("SELECT `CustomSettings` FROM `Sub_Class`
                                    WHERE `Sub_Class_ID` = '".intval($nc_core->input->fetch_get_post('custom_subclass_id'))."'");
                        if ($CustomSettings <> $cur_settings) {
                            $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $loc->CatalogueID, $loc->SubdivisionID, $changed_cc);

                            $db->query("UPDATE `Sub_Class` SET `CustomSettings` = '".$db->prepare($CustomSettings)."'
                    WHERE `Sub_Class_ID` = '".intval($nc_core->input->fetch_get_post('custom_subclass_id'))."'");
                            $changed_cc[] = intval($nc_core->input->fetch_get_post('custom_subclass_id'));
                        }
                    }
                }
            }

            // трансляция события для компонент в разделе
            if (!empty($changed_cc)) {
                $changed_cc = array_unique($changed_cc);
                $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $loc->CatalogueID, $loc->SubdivisionID, $changed_cc);
            }
        }


        if ($type == 1 || $type == 2) {
            $hidden_url = GetHiddenURL($loc->ParentSubID);
            UpdateHiddenURL($hidden_url ? $hidden_url : "/", $loc->ParentSubID, $loc->CatalogueID);
        }


        // поисковая оптимизация, проверка
        if (!empty($fl)) {
            $real_value = $nc_core->page->fetch_page_metatags($nc_core->catalogue->get_url_by_id($loc->CatalogueID) . nc_folder_path($message));

            foreach ($fl as $field) {
                if ($real_value[$field['usage']] && $field['usage'] && $nc_core->input->fetch_get_post($field['name']) && $nc_core->input->fetch_get_post($field['name']) != $real_value[$field['usage']]) {
                    nc_print_status(sprintf(CONTROL_CONTENT_SUBDIVISION_SEO_VALUE_NOT_SETTINGS, $field['usage']), 'info');
                }
            }
        }

        return ( $type == 1 && $message ? $message : ($type == 2 && $loc->SubdivisionID ? $loc->SubdivisionID : false) );
    }

###############################################################################
# покажем информацию по разделу

    function ShowSubdivisionMenu($SubdivisionID, $phase1, $action1, $phase2, $action2, $phase3, $action3) {
        global $db;
        global $EDIT_DOMAIN, $HTTP_ROOT_PATH, $SUB_FOLDER;
        global $UI_CONFIG;

        $nc_core = nc_Core::get_object();

        $Array = $nc_core->subdivision->get_by_id($SubdivisionID);

        $SubClassCount = $db->get_var("SELECT COUNT(*) FROM `Sub_Class` WHERE `Subdivision_ID` = '".intval($SubdivisionID)."'");

        $info = $nc_core->template->get_by_id($Array['Template_ID']);
        $children_count = ChildrenNumber($SubdivisionID);

        //  In My SQL 4.1, TIMESTAMP display format changes to be the same as DATETIME.
        if ($Array['LastUpdated'][4] != '-') {
            $Array['LastUpdated'] = substr($Array['LastUpdated'], 0, 4)."-".substr($Array['LastUpdated'], 4, 2)."-".substr($Array['LastUpdated'], 6, 2)." ".substr($Array['LastUpdated'], 8, 2).":".substr($Array['LastUpdated'], 10, 2).":".substr($Array['LastUpdated'], 12, 2);
        }
        ?>

                <table class='nc-table nc--wide'>
                        <tr>
                            <td><?=CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_UPDATED ?>:</td>
                            <td><?=$Array['LastUpdated'] ?></td>
                        </tr>
                        <tr>
                            <td><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_SUBSECTIONS_COUNT ?>:</td>
                            <td><?= $children_count ?>
                                (<?= ($children_count ? "<a href=index.php?phase=1&ParentSubID=".$SubdivisionID.">".CONTROL_CONTENT_SUBDIVISION_FUNCS_LIST."</a>, " : "")?>
                                <a href="index.php?phase=2&ParentSubID=<? print $SubdivisionID; ?>"><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_ADD ?></a>)
                            </td>
                        </tr>
                        <tr>
                            <td><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_CLASS_COUNT ?>:</td>
                            <td><?= $SubClassCount ?> (<?= $SubClassCount ? "<a href=SubClass.php?SubdivisionID=".$SubdivisionID.">".CONTROL_CONTENT_SUBDIVISION_FUNCS_LIST."</a>, " : "" ?><a href=SubClass.php?phase=1&SubdivisionID=<?= $SubdivisionID ?>><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_ADD ?></a>)</td>
                        </tr>
                        <tr>
                            <td><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_INFO_STATUS ?>:</td>
                            <td><?= $Array["Checked"] ? CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON : CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNOFF ?></td>
                        </tr>
                        <tr>
                            <td><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_MULTI_SUB_CLASS ?>:</td>
                            <td><?= $Array["UseMultiSubClass"] ? CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON : CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNOFF ?></td>
                        </tr>
                        <tr>
                            <td><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE ?>:</td>
                            <? if ($info["Template_ID"]): ?>
                                <td><?= $info["Template_ID"] ?>. <?= $info['Description'] ?></td>
                            <? else: ?>
                                <td></td>
                            <? endif; ?>
                        </tr>
                        <tr>
                            <td><?= CONTROL_TEMPLATE_CUSTOM_SETTINGS ?>:</td>
                            <td>
                                <? if ($info['CustomSettings']): ?>
                                    <? eval($info['CustomSettings']) ?>
                                        <? foreach ($settings_array as $settings): ?>
                                            <?=$settings['caption'] ?><br>
                                        <? endforeach ?>
                                <? else: ?>
                                    <?=CONTROL_TEMPLATE_CUSTOM_SETTINGS_ISNOTSET ?>
                                <? endif ?>
                            </td>
                        </tr>
                    </table>

    <?php
}

###############################################################################

function DeleteSubdivision() {
    global $UI_CONFIG;

    $nc_core = nc_Core::get_object();

    // Помним, что функция вызывается рекурсивно
    if (!is_object($UI_CONFIG)) {
        $UI_CONFIG = new ui_config_subdivision_delete(0);
        $UI_CONFIG->headerText = CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE;
    }

    $cat_fields = $nc_core->catalogue->get_all();
    $subs_serv = array();

    foreach ($cat_fields as  $fields) {
        foreach ($fields as $key => $value) {
            if (in_array($key, array('Title_Sub_ID', 'E404_Sub_ID'), true)) {
                $subs_serv[] = $value;
            }
        }
    }

    $sub_ids = array();

    foreach ($nc_core->input->fetch_get_post() as $key => $val) {
        if (substr($key, 0, 6) == "Delete") {
            $sub_ids[] = substr($key, 6, strlen($key) - 6) + 0;
        }
    }

    $changeset_description = "";

    try {
        $parent_subdivision = $nc_core->subdivision->get_by_id(end($sub_ids));
        $changeset_description = $parent_subdivision["Subdivision_Name"];
    } catch (Exception $ignored) {
    }

    $nc_core->version->init_changeset(NETCAT_VERSION_ACTION_DELETED . ' ' . sprintf(NETCAT_VERSION_OF_SUBDIVISION, $changeset_description));

    foreach ($sub_ids as $id){
        if (!in_array($sub_ids, $subs_serv)) {
            DeleteSystemTableFiles('Subdivision', $id);
            CascadeDeleteSubdivision($id);
            $UI_CONFIG->treeChanges['deleteNode'][] = "sub-{$id}";
        }
    }

    $nc_core->version->commit_changeset();

    nc_print_status(CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE_SUCCESS, 'ok');
}

###############################################################################

function AscIfDeleteSubdivision($phase, $action) {
    global $db, $nc_core;
    global $ParentSubID, $CatalogueID;
    global $UI_CONFIG;

    $ask = false;
    $disallow_delete = false;
    $cat_counter = 0;

    print "<form method=post action=\"".$action."\"><ul>";
    foreach ($nc_core->input->fetch_get_post() as $key => $val) {
        if (substr($key, 0, 6) == "Delete" && $val) {
            $ask = true;
            $sub_id = substr($key, 6, strlen($key) - 6) + 0;
            $sub_array = nc_get_sub_children($sub_id);
            /*
             * Надо гарантированно получить дочерние разделы раньше родительских, иначе родительский удалится первым и будет ошибка.
             * Для этого отсортируем разделы по длине Hidden_URL (у родителей он гарантированно короче)
             */
            $SelectArray = $db->get_results("SELECT Subdivision_ID, Subdivision_Name, ncDisallowMoveAndDelete FROM Subdivision WHERE Subdivision_ID IN (".join(',', $sub_array).") ORDER BY CHAR_LENGTH(Hidden_URL) DESC", ARRAY_A);

            if (!empty($SelectArray))
                    foreach ($SelectArray as $temp) {
                    print "<li>".$temp['Subdivision_Name'];
                    if ($temp['ncDisallowMoveAndDelete']) {
                        $disallow_delete = true;
                        print " <span class='nc--error-label'>(" . CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE_ITEM . ")</span>";
                    } elseif ($nc_core->sub_class->get_disallow_sub_class_by_subdivision_id($temp['Subdivision_ID'])) {
                        $disallow_delete = true;
                        print " <span class='nc--error-label'>(" . CONTROL_CONTENT_SUB_CLASS_DISALLOW_ERROR_DELETE_ITEM . ")</span>";
                    }
                    print "<br>";
                    print "<INPUT TYPE=HIDDEN NAME=Delete".$temp['Subdivision_ID']." VALUE=".$temp['Subdivision_ID'].">";
                    $cat_counter++;
                }
        }
    }

    if (!$ask) return false;

    if ($cat_counter > 1) {
        $post_f1 = CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_ONE_MANY;
        $post_f2 = CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_TWO_MANY;
    } else {
        $post_f1 = CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_ONE_ONE;
        $post_f2 = CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING_TWO_ONE;
    }
    ?>
    </ul>
    <?
        if ($disallow_delete) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_DELETE, 'error');
            return false;
        }
    ?>
        <? nc_print_status(sprintf(CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_WARNING, $post_f1, $post_f2), 'info'); ?>
    <input type=hidden name=phase value=<?= (int)$phase ?>>
    <input type=hidden name=ParentSubID value=<?= ($ParentSubID + 0) ?>>
    <input type=hidden name=CatalogueID value=<?= ($CatalogueID + 0) ?>>
    <?php
    print $nc_core->token->get_input();
    print "</form>";
    $UI_CONFIG->actionButtons[] = array(

        "caption" => CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE_CONFIRMATION,
        "action" => "mainView.submitIframeForm()",
        "red_border" => true,
    );
    return true;
}

function ShowFullSubdivisionList() {
    global $db, $nc_core;
    global $HTTP_DOMAIN, $HTTP_HOST, $EDIT_DOMAIN, $HTTP_ROOT_PATH, $SUB_FOLDER, $CatalogueID;
    global $AUTHORIZATION_TYPE, $first, $perm;
    global $UI_CONFIG, $ADMIN_PATH, $ADMIN_TEMPLATE;

    $is_supervisor = ($perm->isSupervisor() || $perm->isCatalogueAdmin($CatalogueID) || $perm->isGuest() );

    try {
        $catalogue_info = $nc_core->catalogue->get_by_id($CatalogueID);
    } catch (Exception $e) {
        nc_print_status(CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_ERR_NOONESITE, 'error');
        return;
    }

    $catalogue_url = $nc_core->catalogue->get_url_by_id($CatalogueID);

    ?>
    <form name='siteMapSearch' id='siteMapSearch' onsubmit='return false'>
        <div id='nc_sitemap_quicksearch_form'>
            <div class='nc_clear'></div>
            <div id='nc_sitemap_quicksearch'>
                <?= SITE_SITEMAP_SEARCH ?> &nbsp; <input type='text' name='searchKeyword' onsubmit='return false;' disabled>
            </div>
            <div class='nc_clear'></div>
        </div>
    </form>
    <?php
    echo "<form name='siteMapForm' id='siteMapForm' method='get' action='index.php'>";
    echo "<input type='hidden' name='phase' value='7'>";

    echo "<table class='nc-table nc--wide nc--hovered' id='nc_site_map'>";
    echo "<tr>";
    echo "<td id='siteTitle' class='name " . ($catalogue_info['Checked'] ? "active" : "unactive") . "'>
            <span>$CatalogueID. </span>
            <a href='".$ADMIN_PATH."catalogue/index.php?phase=2&type=2&CatalogueID=$CatalogueID'>$catalogue_info[Catalogue_Name]</a>
        </td>";


    // edit
    echo "<td class='nc--compact'>";
    if ($is_supervisor && GetSubClassCount($catalogue_info['Title_Sub_ID'])) {
        $cc_id = $db->get_var("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID = '$catalogue_info[Title_Sub_ID]'");
        echo "<a href='" . nc_get_scheme() . '://' . $EDIT_DOMAIN . $SUB_FOLDER . $HTTP_ROOT_PATH . "?inside_admin=1&cc=" . $cc_id . (strlen(session_id()) > 0 ? "&" . session_name() . "=" . session_id() . "" : "") . "'><div class='icons icon_pencil' title='" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_EDIT . "'></div></a>";
    } else {
        echo "<img src='".$ADMIN_TEMPLATE."img/px.gif' alt='' />";
    }
    echo "</td>";

    // view
    echo "<td class='nc--compact'><a href='{$catalogue_url}{$SUB_FOLDER}" . (strlen(session_id()) > 0 ? "?" . session_name() . "=" . session_id() . "" : "") . "' target=_blank><i class='nc-icon nc--arrow-right nc--hovered' title='" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW . "'></i></a></td>";

    // add
    $can_add_root_subdivision = $is_supervisor || $perm->isAccess(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADDSUB, $CatalogueID);
    echo "<td class='nc--compact'>".($can_add_root_subdivision ? "<a href='index.php?phase=2&CatalogueID=$CatalogueID&ParentSubID=0'><i class='nc-icon nc--folder-add nc--hovered' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION."'></i></a>" : "")."</td>";

    // settings
    echo "<td class='nc--compact'>".($is_supervisor ? "<a href='".$ADMIN_PATH."catalogue/index.php?phase=2&CatalogueID=$CatalogueID&type=2'><i class='nc-icon nc--settings nc--hovered' title='".CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_TOOPTIONS."'></i></a>" : "")."</td>";

    // remove
    echo "<td class='nc--compact nc-padding-10 nc-text-center'>".($is_supervisor ? "<a href='".$ADMIN_PATH."catalogue/?".$nc_core->token->get_url()."&phase=4&Delete$CatalogueID=$CatalogueID'><i class='nc-icon nc--remove nc--hovered' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_REMSITE."'></i></a>" : "")."</td>";

    echo "</tr>";

    echo write_sub(0, $CatalogueID);

    echo "</table>";
    echo "</form>\n";
    echo "<div id='siteMapNotFound' style='display:none'>".SITE_SITEMAP_SEARCH_NOT_FOUND."</div>\n";

    $modal_dialog_options = array(
        'width' => 300,
        'height' => 'auto',
        'url' => "{$nc_core->ADMIN_PATH}subdivision/index.php?phase=16&CatalogueID=$CatalogueID&inside_admin=1&admin_modal=1",
        'show_close_button' => 1
    );

    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION,
        "action" => "(new nc.ui.modal_dialog(" . str_replace('"', "'", json_encode($modal_dialog_options, true)) . ")).open()",
        "align" => "left",
    );

    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => NETCAT_ADMIN_DELETE_SELECTED,
        "action" => "mainView.submitIframeForm('siteMapForm')",
        "red_border" => true,
    );
}

###############################################################################
# покажем список разделов в виде дерева: каждый элемент

function write_sub($ParentSubID, $CatalogueID, $count = 1) {
    global $db, $nc_core;
    global $HTTP_ROOT_PATH, $SUB_FOLDER, $ADMIN_PATH;
    global $perm;

    $CatalogueID = (int)$CatalogueID;
    $ParentSubID = (int)$ParentSubID;

    static $security_limit, $initialized;

    if (!$initialized) {
        $initialized = true;
        $allow_id = $perm->GetAllowSub($CatalogueID, MASK_ADMIN | MASK_MODERATE);
        $security_limit = is_array($allow_id) && !$perm->isGuest() ? " `a`.`Subdivision_ID` IN (" . implode(', ', (array)$allow_id) . ")" : " 1";
    }

    $subs_list = (array)$db->get_results(
        "SELECT `a`.`Subdivision_ID`, `a`.`Subdivision_Name`, `a`.`Checked`, COUNT(`b`.`Subdivision_ID`) AS `ChildrenCount`
         FROM `Subdivision` AS `a`
         LEFT JOIN `Subdivision` AS `b` ON `a`.`Subdivision_ID` = `b`.`Parent_Sub_ID`
         WHERE `a`.`Catalogue_ID` = '$CatalogueID'
         AND `a`.`Parent_Sub_ID` = '$ParentSubID'
         AND $security_limit
         GROUP BY `a`.`Subdivision_ID`
         ORDER BY `a`.`Priority`",
        ARRAY_A
    );

    if (empty($subs_list)) {
        return "";
    }

    $cat_fields = $nc_core->catalogue->get_all();
    $subs_serv = array();

    foreach ($cat_fields as $vc) {
        foreach ($vc as $ks => $vs) {
            if (in_array($ks, array('Title_Sub_ID', 'E404_Sub_ID'), true)) {
                $subs_serv[] = $vs;
            }
        }
    }

    $result = '';

    foreach ($subs_list as $sub_row) {
        $result .= "<tr>";

        $result .= "<td class='name " . ($sub_row['Checked'] ? "active" : "unactive") . "' style='padding-left: " . ($count + 15) .  "px;'>
            <img src='{$ADMIN_PATH}images/arrow_sec.gif' width='14' height='10' alt='' title=''><span>{$sub_row['Subdivision_ID']}. </span>
            <a href='{$ADMIN_PATH}subdivision/index.php?phase=5&SubdivisionID={$sub_row['Subdivision_ID']}&view=edit'>$sub_row[Subdivision_Name]</a>
            </td>";

        // edit
        $result .= "<td class='nc--compact'>";
        $result .= "<a href='" . nc_subdivision_objects_link($sub_row['Subdivision_ID']) . "'><i class='nc-icon nc--edit nc--hovered' title='" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_EDIT . "'></i></a>";
        $result .= "</td>";

        // view
        $result .= "<td class='nc--compact'><a href='" . nc_subdivision_preview_link($sub_row) . "' target='_blank'><i class='nc-icon nc--arrow-right nc--hovered' title='" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW . "'></i></a></td>";

        // add
        $result .= "<td class='nc--compact'><a href='{$ADMIN_PATH}subdivision/index.php?phase=2&ParentSubID={$sub_row['Subdivision_ID']}'><i class='nc-icon nc--folder-add nc--hovered' title='" . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION . "'></i></a></td>";

        // settings
        $result .= "<td class='nc--compact'><a href='{$ADMIN_PATH}subdivision/index.php?view=edit&phase=5&SubdivisionID={$sub_row['Subdivision_ID']}'><i class='nc-icon nc--settings nc--hovered' title='" . CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONS . "'></i></a></td>";

        // checkbox
        $disabled = in_array($sub_row['Subdivision_ID'], $subs_serv) ? " disabled='disabled'" : '';
        $result .= "<td class='nc--compact nc-padding-10 nc-text-center'><input name='Delete{$sub_row['Subdivision_ID']}' value='{$sub_row['Subdivision_ID']}' type='checkbox'" . $disabled . "></td>";

        $result .= "</tr>";

        if ($sub_row['ChildrenCount']) {
            $result .= write_sub($sub_row['Subdivision_ID'], $CatalogueID, $count + 20);
        }
    }

    return $result;
}

###############################################################################
# начало класса
# построение хлебных крошек - пути по структуре

class SubdivisionHierarchy {

    var $Link, $NoLink;

    function __construct($Delimeter, $CatalogueURL, $SubdivisionURL) {
        global $nc_core, $db;
        global $loc;
        if (!isset($CurrentSubdivisionID)) {
            $CurrentSubdivisionID = null;
        }
        $CurrentSubdivisionID+=0;
        $loc->CatalogueID+=0;
        $loc->ParentSubID+=0;
        $loc->SubdivisionID+=0;
        $Hierarchy = "";
        $Hierarchy = "<a href=\"".$CatalogueURL.$loc->CatalogueID."\">";
        if ($loc->CatalogueID) $Hierarchy .= $nc_core->catalogue->get_by_id($loc->CatalogueID, "Catalogue_Name")."</a>";

        if ($loc->SubdivisionID) $SubdivisionID = $loc->SubdivisionID;
        else $SubdivisionID = $loc->ParentSubID;


        $LastSubdivisionAsLink = 1;

        if ($SubdivisionID) {
            $SubdivisionArray[] = $SubdivisionID;
            $CurrentSubdivisionID = $SubdivisionID;

            while ($CurrentSubdivisionID) {
                $Array = $db->get_var("select Parent_Sub_ID from Subdivision where Subdivision_ID=".$CurrentSubdivisionID);
                $CurrentSubdivisionID = $Array;
                if ($CurrentSubdivisionID) $SubdivisionArray[] = $Array;
            }

            $this->Link = $this->NoLink = $Hierarchy;
            for ($i = sizeof($SubdivisionArray) - 1; $i > -1; $i--) {
                $this->Link .= $Delimeter;
                $this->NoLink .= $Delimeter;
                if ($i || (!$i && $LastSubdivisionAsLink))
                        $this->Link .= "<a href=\"".$SubdivisionURL.$SubdivisionArray[$i]."\">";
                $Hierarchy = $nc_core->subdivision->get_by_id($SubdivisionArray[$i], "Subdivision_Name");
                $this->Link .= $Hierarchy;
                $this->NoLink .= $Hierarchy;
                if ($i || (!$i && $LastSubdivisionAsLink)) $this->Link .= "</a>";
            }
        } else {
            $this->Link = $this->NoLink = $Hierarchy;
        }
    }

    function printVars() {
        print "Link=".$this->Link."<br>\n";
        print "NoLink=".$this->NoLink."<br>\n";
    }

}

#конец класса
###############################################################################

function UpdateSubdivisionPriority() {
    global $nc_core, $db;

    foreach ($_POST as $key => $val) {
        if (strpos($key, 'Priority') === 0) {
            $sub_id = substr($key, 8) + 0;
            $val += 0;

            $catalogue = $nc_core->subdivision->get_by_id($sub_id, "Catalogue_ID");

            // execute core action
            $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $catalogue, $sub_id);

            $db->query("UPDATE `Subdivision` SET `Priority` = '".$val."', `LastUpdated` = `LastUpdated` WHERE `Subdivision_ID` = '".$sub_id."'");

            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $catalogue, $sub_id);
        }
    }
}

###############################################################################

function AskForSubClassAddition() {
    global $db, $loc, $systemMessageID;
    echo CONTROL_CONTENT_CATALOUGE_FUNCS_MSG_OK."<br><br>";
    echo "<b><a href=SubClass.php?phase=1&SubdivisionID=".$systemMessageID.">".CONTROL_CONTENT_CATALOUGE_FUNCS_A_ADDCLASSTOSECTION."</a> | <a href=index.php?phase=1&".($loc->ParentSubID ? "ParentSubID=".$loc->ParentSubID : "CatalogueID=".$loc->CatalogueID).">".CONTROL_CONTENT_CATALOUGE_FUNCS_A_BACKTOSECTIONLIST."</a></b>";
}

function nc_show_subdivision_rights($subdivision_id) {
    global $db, $UI_CONFIG, $loc, $perm, $ADMIN_TEMPLATE, $nc_core;

    $is_supervisor = $perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_LIST, -1);

    $catalogue_id = $loc->CatalogueID;
    $subdivision_id = intval($subdivision_id);

    $perm_array = $nc_core->subdivision->get_by_id($subdivision_id);

    if ($perm_array['Read_Access_ID'] == 3 || $perm_array['Write_Access_ID'] == 3 || $perm_array['Edit_Access_ID'] == 3 || $perm_array['Subscribe_Access_ID'] == 3) {
        $cub_class_array = $db->get_col("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID = '".$subdivision_id."'", 0);

        $sql = "SELECT perm.User_ID, user.Email, perm.PermissionSet
            FROM Permission as perm
            LEFT JOIN User as user
            ON user.User_ID = perm.User_ID
            WHERE (perm.AdminType = '".SUBDIVISION_ADMIN."'
            AND perm.Catalogue_ID = '".$subdivision_id."')
            OR (perm.AdminType = '".CATALOGUE_ADMIN."' AND (perm.Catalogue_ID = '".$catalogue_id."' OR perm.Catalogue_ID = 0))";
        if ($cub_class_array)
                $sql .= " OR (perm.AdminType = '".SUB_CLASS_ADMIN."' AND perm.Catalogue_ID IN (".join(',', $cub_class_array)."))";
        $sql .= " GROUP BY User_ID";

        $users = $db->get_results($sql, ARRAY_A);
    }
    ?>
    <table class='subPermissions'>
        <tr>
            <td><b><?=SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_READ_ACCESS ?>:</b><br>
            <ul>
    <?
    switch ($perm_array['Read_Access_ID']) {
        case 1:
            echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ALL_USERS."\n";
            break;
        case 2:
            echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_REGISTERED_USERS."\n";
            break;
        case 3:
            $read_access = false;
            if ($users) {
                foreach ($users as $user) {
                    if ($user['PermissionSet'] & MASK_READ) {
                        $read_access = true;
                        echo "<i class='nc-icon nc--user-group'></i> ".($is_supervisor ? "<a href='".$ADMIN_PATH."user/index.php?phase=4&UserID=".$user['User_ID']."'>".$user['Email']."</a>" : $user['Email'])." \n";
                    }
                }
            }
            if (!$read_access) {
                echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS."\n";
            }
            break;
    }
    echo "</ul>\n";

    if (nc_module_check_by_keyword("comments")) {
        echo "<b>".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_COMMENT_ACCESS.":</b><br>\n<ul>\n";
        // get comments access from inherited access ID
        $comment_access = $db->get_var("SELECT `Access_ID` FROM `Comments_Rules` WHERE `ID` = '".$perm_array['Comment_Rule_ID']."'");

        switch ($comment_access) {
            case 1:
                echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ALL_USERS."\n";
                break;
            case 2:
                echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_REGISTERED_USERS."\n";
                break;
            case 3:
                $read_access = false;
                if ($users) {
                    foreach ($users as $user) {
                        if ($user['PermissionSet'] & MASK_COMMENT) {
                            $read_access = true;
                            echo "<li><i class='nc-icon nc--user-group'></i> ".($is_supervisor ? "<a href='".$ADMIN_PATH."user/index.php?phase=4&UserID=".$user['User_ID']."'>".$user['Email']."</a>" : $user['Email'])." \n";
                        }
                    }
                }
                if (!$read_access) {
                    echo "<li><div class='icons icon_usergroups' title='".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS."'></div>&nbsp&nbsp".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS."\n";
                }
                break;
        }
        echo "</ul>\n";
    }

    echo "<b>".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_WRITE_ACCESS.":</b><br>\n<ul>\n";
    switch ($perm_array['Write_Access_ID']) {
        case 1:
            echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ALL_USERS."\n";
            break;
        case 2:
            echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_REGISTERED_USERS."\n";
            break;
        case 3:
            $write_access = false;
            if ($users) {
                foreach ($users as $user) {
                    if ($user['PermissionSet'] & MASK_ADD) {
                        $write_access = true;
                        echo "<li><i class='nc-icon nc--user-group'></i> ".($is_supervisor ? "<a href='".$ADMIN_PATH."user/index.php?phase=4&UserID=".$user['User_ID']."'>".$user['Email']."</a>" : $user['Email'])." \n";
                    }
                }
            }
            if (!$write_access) {
                echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS."\n";
            }
            break;
    }
    echo "</ul>\n";

    echo "<b>".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_EDIT_ACCESS.":</b><br>\n<ul>\n";
    switch ($perm_array['Edit_Access_ID']) {
        case 1:
            echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ALL_USERS."\n";
            break;
        case 2:
            echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_REGISTERED_USERS."\n";
            break;
        case 3:
            $edit_access = false;
            if ($users) {
                foreach ($users as $user) {
                    if ($user['PermissionSet'] & MASK_EDIT) {
                        $edit_access = true;
                        echo "<li><i class='nc-icon nc--user-group'></i> ".($is_supervisor ? "<a href='".$ADMIN_PATH."user/index.php?phase=4&UserID=".$user['User_ID']."'>".$user['Email']."</a>" : $user['Email'])." \n";
                    }
                }
            }
            if (!$edit_access) {
                echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS."\n";
            }
            break;
    }
    echo "</ul>\n";

    echo "</td><td>";

    echo "<b>".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_SUBSCRIBE_ACCESS.":</b><br>\n<ul>\n";
    switch ($perm_array['Subscribe_Access_ID']) {
        case 1:
            echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ALL_USERS."\n";
            break;
        case 2:
            echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_REGISTERED_USERS."\n";
            break;
        case 3:
            $subscribe_access = false;
            if ($users) {
                foreach ($users as $user) {
                    if ($user['PermissionSet'] & MASK_SUBSCRIBE) {
                        $subscribe_access = true;
                        echo "<li><i class='nc-icon nc--user-group'></i> ".($is_supervisor ? "<a href='".$ADMIN_PATH."user/index.php?phase=4&UserID=".$user['User_ID']."'>".$user['Email']."</a>" : $user['Email'])." \n";
                    }
                }
            }
            if (!$subscribe_access) {
                echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS."\n";
            }
            break;
    }
    echo "</ul>\n";

    echo "<b>".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_MODERATORS.":</b><br>\n<ul>\n";
    $object_moderator = false;
    if ($users) {
        foreach ($users as $user) {
            if ($user['PermissionSet'] & MASK_MODERATE) {
                $object_moderator = true;
                echo "<li><i class='nc-icon nc--user-group'></i> ".($is_supervisor ? "<a href='".$ADMIN_PATH."user/index.php?phase=4&UserID=".$user['User_ID']."'>".$user['Email']."</a>" : $user['Email'])." \n";
            }
        }
    }
    if (!$object_moderator) {
        echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS."\n";
    }
    echo "</ul>\n";

    echo "<b>".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_ADMINS.":</b><br>\n<ul>\n";
    $object_admin = false;
    if ($users) {
        foreach ($users as $user) {
            if ($user['PermissionSet'] & MASK_ADMIN) {
                $object_admin = true;
                echo "<li><i class='nc-icon nc--user-group'></i> ".($is_supervisor ? "<a href='".$ADMIN_PATH."user/index.php?phase=4&UserID=".$user['User_ID']."'>".$user['Email']."</a>" : $user['Email'])." \n";
            }
        }
    }
    if (!$object_admin) {
        echo "<li><i class='nc-icon nc--user-group'></i> ".SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST_TEXT_PRIVILEGED_USERS."\n";
    }
    echo "</ul>\n";
}

/**
 * Return all sub childrens
 *
 * @param mixed $sub parent sub
 * @return array array with sub id
 */
function GetChildrenSub($sub) {
    global $db;

    $sub = (array) $sub;
    if (empty($sub)) {
        return array();
    }

    $ret = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Parent_Sub_ID` IN (" . implode(", ", $sub) . ")");

    if (empty($ret)) {
        return array();
    }

    $ret = array_merge($ret, (array) GetChildrenSub($ret));

    return $ret;
}

function nc_subdivision_show_edit_form($sub_id, $view) {
    $nc_core = nc_Core::get_object();
    $sub_env = $nc_core->subdivision->get_by_id($sub_id);
    // if ($view == 'all') {
    //     echo "<style>#simplemodal-container { background-color: white; max-width: 1200px; height: 70%; margin:0px; padding:0px; font-family:'Segoe UI', SegoeWP, Arial; font-size:12px; color:#444444; line-height:14px; background:#fff; }</style>";
    // }

    echo "<form id='adminForm' class='nc-form' name='adminForm' action='{$nc_core->ADMIN_PATH}subdivision/index.php' method='post' enctype='multipart/form-data' >";
    echo "<input type='hidden' name='SubdivisionID' value='".(int)$sub_id."' />";
    echo "<input type='hidden' name='view' value='".htmlspecialchars($view)."' />";
    echo "<input type='hidden' name='phase' value='6' />";
    echo "<input type='hidden' name='posting' value='1' />";
    echo "<input type='submit' class='hidden' />";


    echo $nc_core->token->get_input();

    $views = $view === 'all' ? array('edit', 'design', 'seo', 'system', 'fields') : (array) $view;

    foreach ($views as $view_variant) {
        $tab_caption_constant = 'SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_' . strtoupper($view_variant);
        if (!defined($tab_caption_constant)) {
            trigger_error('No constant corresponds to the view', E_USER_ERROR);
        }
        echo '<div data-tab-caption="' .htmlspecialchars(constant($tab_caption_constant)) . '" data-tab-id="' . $view_variant . '">';
        echo call_user_func('nc_subdivision_form_' . $view_variant, $sub_env);
        echo "</div>";
    }

    echo "</form>";
}

function nc_subdivision_print_modal_prefix($sub_id) {
    ?>
    <div class="nc-modal-dialog" data-confirm-close="no">
        <div class="nc-modal-dialog-header">
            <h2><?= nc_core::get_object()->subdivision->get_by_id($sub_id, 'Subdivision_Name') ?></h2>
        </div>
        <div class="nc-modal-dialog-body">
    <?php
}

function nc_subdivision_print_modal_suffix() {
    ?>
        </div>
        <div class="nc-modal-dialog-footer">
             <button data-action="submit"><?= NETCAT_REMIND_SAVE_SAVE ?></button>
             <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
        </div>
        <script>
            nc.ui.modal_dialog.get_current_dialog().change_tab('design');
        </script>
    </div>
    <?php
}

function nc_subdivision_save($view) {
    $result = true;
    $views = $view === 'all' ? array('edit', 'design', 'seo', 'system', 'fields') : (array) $view;

    foreach ($views as $view_variant) {
        try {
            $result = call_user_func('nc_subdivision_form_' . $view_variant . '_save') && $result;
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), 'error');
            return null;
        }
    }

    return $result;
}

function nc_subdivision_show_access($SubEnv, $have_default = true) {
    $nc_core = nc_Core::get_object();

    $AccessType = array(
        0 => array(
            0 => 1,
            1 => CLASSIFICATOR_USERGROUP_ALL
        ),
        1 => array(
            0 => 2,
            1 => CLASSIFICATOR_USERGROUP_REGISTERED
        ),
        2 => array(
            0 => 3,
            1 => CLASSIFICATOR_USERGROUP_AUTHORIZED
        )
    );

    $actions = array(
            'Read',
            'Write',
            'Edit',
            'Checked',
            'Delete');

    if ($nc_core->modules->get_vars('subscriber', 'VERSION') == 1) {
        $actions[] = 'Subscribe';
    }


    $table = $nc_core->ui->table();
    $table->thead = $table->thead();

    $table->thead->th();
    if ($have_default) {
        $table->thead->th(CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT);
    }
    $table->thead
        ->th(CLASSIFICATOR_USERGROUP_ALL)->text_center()->style('min-width:80px')
        ->th(CLASSIFICATOR_USERGROUP_REGISTERED)
        ->th(CLASSIFICATOR_USERGROUP_AUTHORIZED);

    foreach ($actions as $v) {


        $trow = $table->add_row();
        $trow->td( constant("CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_".strtoupper($v)) )->text_right();

        if($have_default) {
            $trow->td()
                ->label()->blocked()->text_center()
                    ->input('radio')->name("{$v}_Access_ID")->value(0)
                        ->checked( !$SubEnv['_db_'.$v."_Access_ID"] );
        }

        for ($i = 0; $i < count($AccessType); $i++) {
            $trow->td()->class_name("col_".($i + 3).($AccessType[$i][0] == $SubEnv[$v."_Access_ID"] ? ' nc-bg-lighten' : ''))
                ->label()->blocked()->text_center()
                    ->input('radio')
                        ->name("{$v}_Access_ID")
                        ->value($AccessType[$i][0])
                        ->checked( $AccessType[$i][0] == $SubEnv['_db_'.$v."_Access_ID"] );
        }
    }

    $radio_array = array();
    if($have_default) {
        $radio_array[] = array(
            'attr' => array('value' => '0'),
            'desc' => CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT
        );
    }
    $radio_array[] = array(
        'attr' => array('value' => '1'),
        'desc' => CLASSIFICATOR_TYPEOFMODERATION_RIGHTAWAY
    );
    $radio_array[] = array(
        'attr' => array('value' => '2'),
        'desc' => CLASSIFICATOR_TYPEOFMODERATION_MODERATION
    );

    return (string)$table
        . '<br><div>' . CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_PUBLISH . '</div>'
        . nc_get_modal_radio('Moderation_ID', $radio_array, +$SubEnv["_db_Moderation_ID"]);
}

function nc_subclass_show_export($type, $sub_id, $cc_id, $show_name = 0, $from_sub = 0) {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $sub_id = +$sub_id;
    $cc_id = +$cc_id;

    if (!in_array($type, array('rss', 'xml'))) return;

    $Array = $db->get_row("SELECT `Allow".strtoupper($type)."`, `Sub_Class_Name`,`Class_ID`, `EnglishName` FROM `Sub_Class` WHERE `Sub_Class_ID` = '".$cc_id."' ", ARRAY_A);
    $class_id = $Array['Class_ID'];

    $name = $show_name ? "<b>".$Array['Sub_Class_Name']."</b> " : "";

    $export_class_id = $db->get_var("SELECT `Class_ID` FROM `Class` WHERE `Type` = '".$type."' AND `ClassTemplate` = '".$class_id."' ");
    $File_Mode = nc_get_file_mode('Class', $class_id);
    $html = "<div>";

    if (!$export_class_id) {
        $html .= "<font>".sprintf(constant("CONTROL_CLASS_COMPONENT_TEMPLATE_FOR_".strtoupper($type)."_DOESNT_EXIST"), $name)."</font>";
        $html .= "<br/>";
        $html .= "<a  onClick='parent.nc_form(this.href); location.reload(); return false;' href='".$nc_core->ADMIN_PATH."class/index.php?fs=$File_Mode&".$nc_core->token->get_url()."&amp;from_sub=".$sub_id.( $from_sub ? "" : "&amp;from_cc=".$cc_id)."&amp;Type=".$type."&amp;base=auto&amp;phase=1411&amp;ClassID=".$class_id."'>".constant("CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_FOR_".strtoupper($type))."</a>";
    } else {
        $host = $nc_core->catalogue->get_by_id($nc_core->sub_class->get_by_id($cc_id, 'Catalogue_ID'), 'Domain');
        if (nc_module_check_by_keyword('routing')) {
            $link = nc_get_scheme() . '://' . $host . nc_routing::get_infoblock_path($cc_id, 'index', $type);
        }
        else {
            $link = $nc_core->SUB_FOLDER.$db->get_var("SELECT `Hidden_URL` FROM `Subdivision` WHERE `Subdivision_ID` = '".$sub_id."' ");
            $link = nc_get_scheme() . '://' . $host . $link . $Array['EnglishName'] . '.' . $type;
        }
        $html .= "<input type='checkbox' id='Allow".strtoupper($type)."".$cc_id."' name='Allow".strtoupper($type)."".$cc_id."' value='1' ".( $Array["Allow".strtoupper($type).""] ? " checked" : "")." /><label for='Allow".strtoupper($type)."".$cc_id."'>".constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TURN_ON_".strtoupper($type))." ".$name."</label>";

        $html .= "  ( ";
        if ($Array["Allow".strtoupper($type).""])
                $html .= "<a target='_blank' href='".$link."'>".CONTROL_CLASS_COMPONENT_TEMPLATE_VIEW."</a>, ";
        $html .= " <a  onClick='parent.nc_form(this.href); return false; ' href='".$nc_core->ADMIN_PATH."class/index.php?fs=".$File_Mode."&phase=4&amp;ClassID=".$export_class_id."'>".CONTROL_CLASS_COMPONENT_TEMPLATE_EDIT."</a> )";
    }

    $html .= "</div>";


    return $html;
}

function nc_subdivision_form_edit($sub_env) {
    global $perm;
    $nc_core = nc_Core::get_object();
    $sub_id = $sub_env['Subdivision_ID'];

    $html = nc_subdivision_moderate_form($sub_env);

    $field_main = new nc_admin_fieldset();
    $field_main->add(nc_admin_input(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME, 'Subdivision_Name', $sub_env, 50));
    $field_main->add(nc_admin_input(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD, 'EnglishName', $sub_env, 50, "", " data-type='transliterate' data-from='Subdivision_Name' data-is-url='yes' "));
    $field_main->add("<div class='img_block'>".nc_file_field('ncImage','','',true)."</div>");
    $field_main->add("<div class='img_block'>".nc_file_field('ncIcon','','',true)."</div>");
    $field_main->add("<div class='mark_block'>".nc_admin_label_color_field($sub_env['LabelColor'])."</div>");
    $field_main->add(nc_subdivision_addfavorites_form($sub_env));
    if ($perm->isSupervisor()) {
        $field_main->add(nc_subdivision_disallowmoveanddelete_form($sub_env));
    }
    $html .= $field_main->result();

    if (!nc_array_value($_REQUEST,'isNaked') && nc_array_value($_POST,'view') != 'all') {
        global $UI_CONFIG;
        $UI_CONFIG->updateTreeSubdivisionNode($sub_id, $sub_env["Subdivision_Name"], $sub_env["Checked"], $sub_env["LabelColor"]);
    }
    return $html;
}

function nc_subdivision_form_design($sub_env, $CatalogueID = null, $display_type_settings = true, $parent_sub_id = null) {
    global $perm, $loc;
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;
    $html = $result = '';
    $loc_sub_id = is_object($loc) ? $loc->SubdivisionID : 0;

    $Result = $db->get_results(
            "SELECT `Template_ID` as `value`,
                    CONCAT(`Template_ID`, '. ', `Description`) as `description`,
                    `Parent_Template_ID` as `parent`,
                    `File_Mode`
                 FROM `Template`
                     ORDER BY `Priority`, `Template_ID` ", ARRAY_A);
    if (empty($Result)) {
        $html .= CONTROL_USER_NOONESECSINSITE;
    } else {
        $field_design = new nc_admin_fieldset($CatalogueID ? null : CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE);
        // поиск названия используемого макета
        $File_Mode_IDs = array();
        foreach ($Result as $v) {
            if ($v['File_Mode']) {
                $File_Mode_IDs[] = $v['value'];
            }
            if ($v['value'] == $sub_env["_db_inherit_Template_ID"]) {
                $cur_template = CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_N." [".$v['description']."]";
            }
        }

        $html .= "<select name='Template_ID' onchange='loadCustomTplSettings(0, ".($loc_sub_id ?: -1).", this.options[this.selectedIndex].value)'>\n";
	if (!isset($cur_template)) {
            $cur_template = null;
        }
        $html .= "<option ".(nc_array_value($sub_env, "_db_Template_ID") ? "" : "selected='selected' ")."value='{$sub_env["_db_inherit_Template_ID"]}'>".$cur_template."</option>";
        $html .= nc_select_options($Result, nc_array_value($sub_env, "_db_Template_ID"));
        $html .= "</select>\n";
        // edit template in new window

        $html .= "<input disabled='disabled' style='margin-left: 0px; position: relative; top: -2px; height: 36px; ".($perm->isSupervisor() ? "" : "display: none")."' type='button' id='templateEditLink' style='margin-top: -25px; margin-left: 300px;' value='".CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_EDIT."' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_EDIT."'/>";
        $html .= "
                <input name='is_parent_template' type='hidden' value='' />
                <script>
                    var File_Mode_IDs = '|".join('|', $File_Mode_IDs)."|';
                </script>";
        $html .= nc_admin_checkbox(CONTROL_CONTENT_SUBDIVISION_FUNCS_USEEDITDESIGNTEMPLATE, 'UseEditDesignTemplate', nc_array_value($sub_env, 'UseEditDesignTemplate'));
        $field_design->add($html);
        $result = $field_design->result();

        $loc_id = $CatalogueID !== null ? $CatalogueID : $loc_sub_id;
        $loc_id = $loc_id ?: -1;

        if ($CatalogueID !== null) {
            $js_load_custom_settings = "loadCustomTplSettings($loc_id, 0, ". +$sub_env["Template_ID"].");";
        } else {
            $parent_template_id = $sub_env["_db_inherit_Template_ID"] ?: 0;
            $custom_template_id = +nc_array_value($sub_env, "Template_ID") ?: $parent_template_id;
            $parent_sub_id = (int)$parent_sub_id;
            $js_load_custom_settings = "loadCustomTplSettings(0, $loc_id, ". $custom_template_id .", $parent_sub_id);";
        }

        $field_cs = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DTEMPLATE_CS, 'on', true);
        $html = "<br/><div id='customTplSettings'></div>";
        $html .= "<br/><div id='loadTplWait'><img src='".$nc_core->ADMIN_TEMPLATE."img/trash-loader.gif' alt='' /></div>";
        $html .= "<script type='text/javascript'>
                      \$nc(function() { $js_load_custom_settings });
                  </script>\n";

        $field_cs->add($html);
        $result .= $field_cs->result();
    }

    if ($loc_sub_id) {
        $mixin_settings = $nc_core->subdivision->get_by_id($loc_sub_id, 'MainArea_Mixin_Settings');
    } else if ($CatalogueID) {
        $mixin_settings = $nc_core->catalogue->get_by_id($CatalogueID, 'MainArea_Mixin_Settings');
    } else {
        $mixin_settings = '';
    }

    $result .= nc_subdivision_form_mixin_settings($mixin_settings);

    if ($display_type_settings) {
        $display_type_fieldset = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE);

        $postParentSubID = is_object($loc) ? $loc->ParentSubID : 0;
        $postParentCatalogue = is_object($loc) ? $loc->CatalogueID : 0;
        $parent_display_type_const = false;

        if ($postParentSubID) {
            $parent_sub_env = $nc_core->subdivision->get_by_id($postParentSubID);
            $parent_display_type = $parent_sub_env['DisplayType'];
            if ($parent_display_type !== 'inherit') {
              $parent_display_type_const = "CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_".strtoupper($parent_display_type);
            }
        }
        if (!$parent_display_type_const) {
            $parent_sub_env = $nc_core->catalogue->get_by_id($postParentCatalogue);
            $parent_display_type = $parent_sub_env['DisplayType'];
            $parent_display_type_const = "CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_".strtoupper($parent_display_type);
        }

        $displayTypeData = array(
            array(
                'value' => 'inherit',
                'description' => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_INHERIT . ( $parent_display_type ? " (".constant($parent_display_type_const).")" : "" )
                ),
            array(
                'value' => 'traditional',
                'description' => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_TRADITIONAL
                ),
            array(
                'value' => 'shortpage',
                'description' => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_SHORTPAGE
                ),
            array(
                'value' => 'longpage_vertical',
                'description' => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_DISPLAYTYPE_LONGPAGE_VERTICAL
                )
        );

        $displayTypeControls = "<select name='DisplayType'>\n";
        $displayTypeControls .= nc_select_options($displayTypeData, nc_array_value($sub_env, 'DisplayType'));
        $displayTypeControls .= "</select>\n";
        $display_type_fieldset->add($displayTypeControls);

        $result .= $display_type_fieldset->result();
    }

    return $result;
}

/**
 * @param string $mixin_settings
 * @return string
 * @throws Exception
 */
function nc_subdivision_form_mixin_settings($mixin_settings = '{}') {
    $nc_core = nc_core::get_object();
    $editor_data = array(
        'field_name_template' => '%s',
        'field_name_prefix' => 'MainArea',
        'data' => array(
            'MainArea_Mixin_Settings' => $mixin_settings,
        ),
    );

    $fieldset = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_MAINAREA_MIXIN_SETTINGS, 'on', true);
    $editor = $nc_core->ui->view($nc_core->ADMIN_FOLDER . 'views/mixin/mixin_editor', $editor_data);
    $fieldset->add($editor);

    return $fieldset->result();
}

function nc_subdivision_form_seo($sub_env, $have_default = true) {
    $nc_core = nc_Core::get_object();
    $sub_id = nc_array_value($sub_env, 'Subdivision_ID', 0);
    $field_index = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_SEO_INDEXING);

    $lm_type = $nc_core->page->get_field_name('last_modified_type');
    $h1 = '';
    $title = '';
    $meta = '';

    if ($have_default) {
        $field_meta = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_SEO_META);
        $mt = $nc_core->page->fetch_page_metatags(nc_folder_url($sub_id));
        // title
        $title = "<style> div#nc_subdivision_form_seo_div div.inf_block {padding: 3px; padding-top: 13px;} </style>";
        $title .= nc_admin_input(NETCAT_MODERATION_SEO_TITLE, 'title', $sub_env[$nc_core->page->get_field_name('title')], 50, 'width:100%;width:calc(100% + 10px);');

        if (nc_array_value($mt,'title')) {
            $title .= CONTROL_CONTENT_SUBDIVISION_SEO_CURRENT_VALUE.": <b>".htmlspecialchars($mt['title'])."</b><br/><br />";
        }

        $h1 = nc_admin_input(NETCAT_MODERATION_SEO_H1, 'h1', $sub_env['ncH1'], 50, 'width:100%;width:calc(100% + 10px);');
        if (nc_array_value($mt,'h1')) {
            $h1 .= CONTROL_CONTENT_SUBDIVISION_SEO_CURRENT_VALUE.": <b>".htmlspecialchars($mt['h1'])."</b><br/><br />";
        }

        // keywords
        $meta_html = nc_admin_textarea(NETCAT_MODERATION_SEO_KEYWORDS, 'keywords', $sub_env[$nc_core->page->get_field_name('keywords')]);
        if (nc_array_value($mt,'keywords')) {
            $meta_html .= CONTROL_CONTENT_SUBDIVISION_SEO_CURRENT_VALUE.": <b>".htmlspecialchars($mt['keywords'])."</b><br/>";
        }

        // description
        $meta_html .= nc_admin_textarea(NETCAT_MODERATION_SEO_DESCRIPTION, 'description', $sub_env[$nc_core->page->get_field_name('description')]);
        if (nc_array_value($mt,'description')) {
            $meta_html .= CONTROL_CONTENT_SUBDIVISION_SEO_CURRENT_VALUE.": <b>".htmlspecialchars($mt['description'])."</b><br/>";
        }

        $field_meta->add($meta_html);

        // SMO
        $meta_smo_html = "
            <style>
            div.helper {
                font-size:12px;
                color:gray;
                margin-top:-8px;
            }
            </style>";

        $field_meta_smo = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_SEO_SMO_META);

        $meta_smo_html .= nc_admin_input(NETCAT_MODERATION_SMO_TITLE, 'smo_title', $sub_env[$nc_core->page->get_field_name('smo_title')], 50);
        if (nc_array_value($mt,'smo_title')) {
            $meta_smo_html .= "<div class=\"helper\"> ". NETCAT_MODERATION_SMO_TITLE_HELPER ."</div>";
            $meta_smo_html .= CONTROL_CONTENT_SUBDIVISION_SEO_CURRENT_VALUE.": <b>".htmlspecialchars($mt['smo_title'])."</b><br/>";
        }
        $meta_smo_html .= nc_admin_input(NETCAT_MODERATION_SMO_DESCRIPTION, 'smo_description', $sub_env[$nc_core->page->get_field_name('smo_description')], 50);
        if (nc_array_value($mt,'smo_description')) {
            $meta_smo_html .= "<div class=\"helper\"> ". NETCAT_MODERATION_SMO_DESCRIPTION_HELPER ."</div>";
            $meta_smo_html .= CONTROL_CONTENT_SUBDIVISION_SEO_CURRENT_VALUE.": <b>".htmlspecialchars($mt['smo_description'])."</b><br/><br/><br/>";
        }
        // Картинка для SMO
        $meta_smo_html.="<div class='nc-field nc-field-type-file'>
                    <div class='nc-field-caption'>" . NETCAT_MODERATION_SMO_IMAGE . ":</div>
                    ". nc_file_field('ncSMO_Image') ."
                </div>";

        $field_meta_smo->add($meta_smo_html);
        $meta = $field_meta->result() . $field_meta_smo->result();
    }

    $html = "<div>" . CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_HEADER . ":</div>";

    $radio_array = array();

    if ($have_default) {
        $radio_array[] =  array(
            'attr' => array('value' => '0'),
            'desc' => CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT
        );
    }

    $radio_array[] = array(
        'attr' => array('value' => '1'),
        'desc' => CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_NONE
    );
    $radio_array[] = array(
        'attr' => array('value' => '2'),
        'desc' => CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_YESTERDAY
    );
    $radio_array[] = array(
        'attr' => array('value' => '3'),
        'desc' => CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_HOUR
    );
    $radio_array[] = array(
        'attr' => array('value' => '4'),
        'desc' => CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_CURRENT
    );
    $radio_array[] = array(
        'attr' => array('value' => '5'),
        'desc' => CONTROL_CONTENT_SUBDIVISION_SEO_LAST_MODIFIED_ACTUAL
    );

    $html .= nc_get_modal_radio('last_modified_type', $radio_array, +$sub_env['_db_'.$lm_type]);

    if ($nc_core->modules->get_by_keyword('search')) {
        $sm_field = $nc_core->page->get_field_name('sitemap_include');
        $sm_change_field = $nc_core->page->get_field_name('sitemap_changefreq');
        $sm_priority_field = $nc_core->page->get_field_name('sitemap_priority');
        $changefreq = '';
        $prioritysel = '';

        // Запрет индексации
        $html .= "
            <style>
            div.nc_table_indexing > div {
                border-bottom: 1px #cccccc solid;
                display: table;
            }
    
            div.nc_table_indexing > div > div {
                display: inline-block;
                padding-top: 9px;
                padding-bottom: 11px;
            }
    
            div.nc_table_indexing div.col_1 {
                width: 210px;
            }
    
            div.nc_table_indexing div.col_2 {
                text-align: center;
                width: 134px;
            }
    
            div.nc_table_indexing div.col_3 {
                text-align: center;
                width: 75px;
            }
    
            div.nc_table_indexing div.col_4 {
                text-align: center;
                width: 81px;
            }
    
            div.nc_table_indexing > div.row_1 {
                padding-top: 3px;
                padding-bottom: 2px;
            }
    
            div.nc_table_indexing div.col_checked {
                background-color: #eeeeee;
            }
    
            </style>
    
            <div class='nc_table_indexing'>
                <div class='row_1'>
                    <div class='col_1'>
                    </div>";

        if ($have_default) {
            $html .= "  <div class='col_2'>
                            ".CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT."
                        </div>";
        }

        $html .= "  <div class='col_3'>
                        ".CONTROL_CONTENT_SUBDIVISION_SEO_DISALLOW_INDEXING_YES."
                    </div>
    
                    <div class='col_4'>
                        ".CONTROL_CONTENT_SUBDIVISION_SEO_DISALLOW_INDEXING_NO."
                    </div>
                </div>
    
                <div class='row_2'>
                    <div class='col_1'>
                        ".CONTROL_CONTENT_SUBDIVISION_SEO_DISALLOW_INDEXING."
                    </div>";

        if ($have_default) {
            $html .= "  <div class='col_2".($sub_env['_db_DisallowIndexing'] == -1 ? " col_checked" : "")."'>
                            <input name='DisallowIndexing' type='radio' value='-1' ".($sub_env['_db_DisallowIndexing'] == -1 ? " checked='checked'" : "")." />
                        </div>";
        }

        $html .= "  <div class='col_3".($sub_env['_db_DisallowIndexing'] == 0 ? " col_checked" : "")."'>
                        <input name='DisallowIndexing' type='radio' value='0' ".($sub_env['_db_DisallowIndexing'] == 0 ? " checked='checked'" : "")." />
                    </div>
    
                    <div class='col_4".($sub_env['_db_DisallowIndexing'] == 1 ? " col_checked" : "")."'>
                        <input name='DisallowIndexing' type='radio' value='1' ".($sub_env['_db_DisallowIndexing'] == 1 ? " checked='checked'" : "")." />
                    </div>
                </div>";

        $html .= "
            <div class='row_3'>
                <div class='col_1'>
                    ".CONTROL_CONTENT_SUBDIVISION_SEO_INCLUDE_IN_SITEMAP."
                </div>";

        if ($have_default) {
            $html .= "
                <div class='col_2".($sub_env['_db_'.$sm_field] == -1 ? " col_checked" : "")."'>
                    <input name='sitemap_include' type='radio' value='-1' ".($sub_env['_db_'.$sm_field] == -1 ? " checked='checked'" : "")." />
                </div>";
        }

        $html .= "
                <div class='col_3".($sub_env['_db_'.$sm_field] == 1 ? " col_checked" : "")."'>
                    <input name='sitemap_include' type='radio' value='1' ".($sub_env['_db_'.$sm_field] == 1 ? " checked='checked'" : "")." />
                </div>

                <div class='col_4".($sub_env['_db_'.$sm_field] == 0 ? " col_checked" : "")."'>
                    <input name='sitemap_include' type='radio' value='0' ".($sub_env['_db_'.$sm_field] == 0 ? " checked='checked'" : "")." />
                </div>
            </div>
        </div><br/>";

        if ($have_default && $sub_env['_db_inherit_'.$sm_change_field]) {
            $changefreq .= "<option value='-1'>".CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT." [".constant("CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_".strtoupper($sub_env['_db_inherit_'.$sm_change_field]))."]</option>";
            $prioritysel .= "<option value=''>".CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT." [".sprintf("%.1f", $sub_env['_db_inherit_'.$sm_priority_field])."]</option>";
        }

        foreach (array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never') as $v) {
            $changefreq .= "<option value='".$v."' ".( $sub_env['_db_'.$sm_change_field] == $v ? "selected" : "").">".constant("CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ_".strtoupper($v))."</option>";
        }

        for ($i = 1; $i > 0.1; $i-= 0.1) {
            $prioritysel .= "<option value='".str_replace(',', '.', sprintf("%.1f", $i))."' ".( $sub_env['_db_'.$sm_priority_field] >= 0 && abs(doubleval($sub_env['_db_'.$sm_priority_field]) - $i) < 0.01 ? "selected" : "").">".sprintf("%.1f", $i)."</option>";
        }

        $html .= "
        <style>
            div.nc_table_sitemap > div {
                display: table;
            }

            div.nc_table_sitemap > div > div {
                display: inline-block;
            }

            div.nc_table_sitemap div.col_1 {
                width: 247px;
            }

            div.nc_table_sitemap div.col_2 {
                text-align: center;
            }
        </style>

        <div class='nc_table_sitemap'>
            <div class='row_1'>
                <div class='col_1'>
                    ".CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_CHANGEFREQ."
                </div>

                <div class='col_2'>
                    <select name='sitemap_changefreq'>$changefreq</select>
                </div>
            </div>

            <div class='row_2'>
                <div class='col_1'>
                    ".CONTROL_CONTENT_SUBDIVISION_SEO_SITEMAP_PRIORITY."
                </div>

                <div class='col_2'>
                    <select name='sitemap_priority'>$prioritysel</select>
                </div>
            </div>
        </div>";
    }
    $field_index->add($html);

    return $title . $h1 . $meta . $field_index->result();
}

function nc_subdivision_moderate_form($env) {
    return "
        <div id='nc_seo_edit_info' class='nc_admin_settings_info'>
            <div class='nc_admin_settings_info_actions'>
                " . (nc_array_value($env, 'Created') ?
                "<div>
                    <span>" . CLASS_TAB_CUSTOM_ADD . ":</span> {$env['Created']}
                </div>" : "") . "
                " . (nc_array_value($env, 'LastUpdated') ?
                "<div>
                    <span>" . CLASS_TAB_CUSTOM_EDIT . ":</span> {$env['LastUpdated']}
                </div>" : "") . "
            </div>

            " . (nc_array_value($env, 'Created') ?
                "<div class='nc_admin_settings_info_priority'>
                <div>
                    " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY . ":
                </div>

                <div>" : "") . "
                    <input type='" . (nc_array_value($env, 'Created') ? 'text' : 'hidden') . "' name='Priority' size='3' value='{$env["Priority"]}'/>
                " . (nc_array_value($env, 'Created') ? "
                </div>
            </div>" : "") . "

            <div class='nc_admin_settings_info_checked'>
                <div>
                    <input id='turnon' type='checkbox' name='Checked' value='1' ".($env["Checked"] ? "checked='checked' " : "")." />
                    <label for='turnon'>".CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON."</label>
                </div>
            </div>
        </div>";
}

function nc_subdivision_addfavorites_form($env = array()) {
    return "
        <div id='nc_seo_edit_info' class='nc_admin_settings_info'>
            <div class='nc_admin_settings_info_favorites'>
                <div>
                    <input id='fav' type='checkbox' name='Favorite' value='1' ".(nc_array_value($env, "Favorite") == 1 ? "checked='checked' " : "")."/>
                    <label for='fav'>".CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDFAVOTITES."</label>
                </div>
            </div>
        </div>";
}

function nc_subdivision_disallowmoveanddelete_form($env = array()) {
    return "
        <div id='nc_seo_edit_info' class='nc_admin_settings_info'>
            <div class='nc_admin_settings_info_favorites'>
                <div>
                    <input id='chkDisallowMoveAndDelete' type='checkbox' name='ncDisallowMoveAndDelete' value='1' ".(nc_array_value($env, "ncDisallowMoveAndDelete") == 1 ? "checked='checked' " : "")."/>
                    <label for='chkDisallowMoveAndDelete'>".CONTROL_CONTENT_SUBDIVISION_FUNCS_DISALLOW_MOVE_AND_DELETE."</label>
                </div>
            </div>
        </div>";
}

function nc_subdivision_form_system($sub_env) {
    $nc_core = nc_Core::get_object();
    $sub_id = $sub_env['Subdivision_ID'];
    $lang_field = $nc_core->page->get_field_name('language');
    $sub_env = $nc_core->subdivision->get_by_id($sub_id);

    $field_main = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_SYSTEM_FIELDS);
    $html = nc_admin_input(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_EXTURL, 'ExternalURL', $sub_env['ExternalURL'], 50);
    $html .= nc_admin_input(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_LANG, 'language', $sub_env['_db_'.$lang_field], 50);
    $html .= CONTROL_CONTENT_SUBDIVISION_SEO_CURRENT_VALUE.": <b>".$sub_env[$lang_field]."</b></font><br/><br/>";

    $field_main->add($html);

    // доступ
    $field_access = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS);
    $field_access->add(nc_subdivision_show_access($sub_env));

    $result = $field_main->result().$field_access->result();

    //rss
    global $isNaked;
    if ( !$isNaked ) {
		$all_cc_ids = $nc_core->db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = '".intval($sub_id)."' ");
		$count_cc = count($all_cc_ids);
		if ($count_cc) {
			$field_rss = new nc_admin_fieldset('RSS');
			foreach ($all_cc_ids as $cc_id) {
				$field_rss->add(nc_subclass_show_export('rss', $sub_id, $cc_id, ($count_cc > 1), 1));
			}
			$result .= $field_rss->result();
		}
	}

    // cache
    if ($nc_core->modules->get_by_keyword('cache')) {
        $field_cache = new nc_admin_fieldset(CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE);
        $field_cache->add(nc_subdivision_show_cache($sub_env));
        $result .= $field_cache->result();
    }

    // comments
    if ($nc_core->modules->get_by_keyword('comments')) {
        include_once nc_module_folder('comments') . 'function.inc.php';
        $field_comments = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS);
        $field_comments->add(nc_subdivision_show_comments($sub_env));
        $result .= $field_comments->result();
    }

    return $result;
}

function nc_subdivision_form_fields($sub_env) {
    $nc_core = nc_Core::get_object();
    $sub_id = $sub_env['Subdivision_ID'];
    global $nc_core, $db;
    global $systemTableID, $systemMessageID, $systemTableName;
    $action = "change";
    $nc_core->action = "change";
    $systemMessageID = $message = $sub_id;
    echo "<br/>";
    require $nc_core->ROOT_FOLDER."message_fields.php";

    if ($fldCount) {
        $fieldQuery = implode(",", $fld);
        $fldValue = $nc_core->db->get_row("SELECT ".$fieldQuery." FROM `Subdivision` WHERE `Subdivision_ID` = '".intval($sub_id)."'", ARRAY_N);
    }
    else {
		nc_print_status(CONTROL_CONTENT_SUBDIVISION_SYSTEM_FIELDS_NO, 'info');
	}

    require $nc_core->ROOT_FOLDER."message_edit.php";
}

function nc_subdivision_show_cache($env, $have_default = true) {
    $html = "<div>".CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_STATUS."</div>";

    $radio_array = array();

    if ($have_default) {
        $radio_array[] = array(
                'attr' => array(
                        'value' => '0'),
                'desc' => CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT
            );
    }

    $radio_array['ca'] = array(
            'attr' => array(
                    'value' => '1'
                ),
            'desc' => CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_ALLOW);

    $radio_array['cd'] = array(
            'attr' => array(
                    'value' => '2'
                ),
            'desc' => CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_DENY);

    $html .= nc_get_modal_radio('Cache_Access_ID', $radio_array, +$env["Cache_Access_ID"]);

    $html .= "<div>
                  ".CONTROL_CONTENT_SUBDIVISION_FUNCS_CACHE_LIFETIME.":
              </div>";
    $html .= "<div class='col_input_text'>
                  <input name='Cache_Lifetime' id='Cache_Lifetime' type='text' value='".($env['Cache_Lifetime'] ? $env['Cache_Lifetime'] : ($env['_db_Cache_Lifetime'] ? $env['_db_Cache_Lifetime'] : 0))."' ".((!$env["_db_Cache_Access_ID"] || ('1' != $env['Cache_Access_ID'])) ? " disabled" : "")." />
              </div>";

    return $html;
}

function nc_subdivision_show_comments($env) {
    require_once nc_module_folder('comments') . 'function.inc.php';

    $db = nc_Core::get_object()->db;
    $AccessType = array(
            0 => array(
                    0 => 1,
                    1 => CLASSIFICATOR_USERGROUP_ALL),
            1 => array(
                    0 => 2,
                    1 => CLASSIFICATOR_USERGROUP_REGISTERED),
            2 => array(
                    0 => 3,
                    1 => CLASSIFICATOR_USERGROUP_AUTHORIZED),
            3 => array(
                    0 => 4,
                    1 => CLASSIFICATOR_COMMENTS_DISABLE)
    );

    $comments_data = nc_comments::getRuleData($db, array(
            nc_array_value($env, 'Catalogue_ID'),
            nc_array_value($env, 'Subdivision_ID'),
            nc_array_value($env, 'Sub_Class_ID'),
            nc_array_value($env, 'Message_ID')
        ));

    $parent_comment_rule = $env['Comment_Rule_ID'] ? $db->get_row("SELECT * FROM `Comments_Rules` WHERE `ID` = ".$env['Comment_Rule_ID'], ARRAY_A) : array();

    $comments_change_variants = array(
            'disable' => CLASSIFICATOR_COMMENTS_DISABLE,
            'enable' => CLASSIFICATOR_COMMENTS_ENABLE,
            'unreplied' => CLASSIFICATOR_COMMENTS_NOREPLIED);

    //правила в любом случае наследуются, inherit_radio позволяет удалить правило текщей сущности
    //$hide_inherit_radio = !isset($env['Parent_Sub_ID']) && !$env['Message_ID'];

    $html = "<script type='text/javascript'>
        \$nc(function() {
         \$nc('input[name=CommentAccessID]').click(function(){
             var radioValue = \$nc('input[name=CommentAccessID]:checked').val();
             var inputs = \$nc('#CommentsEditRules, #CommentsDeleteRules');

             if (0 == radioValue) {
                 inputs.attr('disabled', 'disabled');
             } else {
                 inputs.removeAttr('disabled');
             }

         });

         ";
    if ($comments_data["Access_ID"] == 0) {
        $html .= "
            \$nc('#CommentsEditRules, #CommentsDeleteRules').attr('disabled', 'disabled');
        ";
    }

    $html .= "
     });
    </script>
    <style>
        div.nc_table_comments > div {
            border-bottom: 1px #cccccc solid;
            display: table;
        }

        div.nc_table_comments > div > div {
            display: inline-block;
            padding-top: 9px;
            padding-bottom: 11px;
        }

        div.nc_table_comments div.col_1 {
            width: 214px;
        }

        div.nc_table_comments div.col_2 {
            text-align: center;
            width: 134px;
        }

        div.nc_table_comments div.col_3 {
            text-align: center;
            width: 61px;
        }

        div.nc_table_comments div.col_4 {
            text-align: center;
            width: 180px;
        }

        div.nc_table_comments div.col_5 {
            text-align: center;
            width: 158px;
        }

        div.nc_table_comments div.col_6 {
            text-align: center;
            width: 116px;
        }

        div.nc_table_comments > div.row_1 {
            padding-top: 3px;
            padding-bottom: 2px;
        }

        div.nc_table_comments div.col_checked {
            background-color: #eeeeee;
        }

    </style>";

    $comments_radio = array();
    $comments_radio['inherit'] = array(
                    'attr' => array(
                            'value' => '0'),
                    'desc' => CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT);
    $comments_radio += array(
            array(
                    'attr' => array(
                            'value' => '1'),
                    'desc' => CLASSIFICATOR_USERGROUP_ALL),

            array(
                    'attr' => array(
                            'value' => '2'),
                    'desc' => CLASSIFICATOR_USERGROUP_REGISTERED),
            array(
                    'attr' => array(
                            'value' => '3'),
                    'desc' => CLASSIFICATOR_USERGROUP_AUTHORIZED),
            array(
                    'attr' => array(
                            'value' => '4'),
                    'desc' => CLASSIFICATOR_COMMENTS_DISABLE));

    if (!empty($hide_inherit_radio)) {
        unset($comments_radio['inherit']);
    }

    $html .= "<div>".CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS_ADD."</div>";
    $html .= nc_get_modal_radio('CommentAccessID', $comments_radio, +$comments_data["Access_ID"]);

    $html .= "<br/>";

    $html .= "
        <style>
        div.nc_table_content > div > div {
            display: inline-block;
        }

        div.nc_table_content div.col_1 {
            width: 244px;
        }
        </style>

        <div class='nc_table_content'>
            <div>
                <div class='col_1'>
                    ".CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS_AUTHOR_EDIT."
                </div>

                <div class='col_2'>";

    $html .= "      <select name='CommentsEditRules' id='CommentsEditRules' ". ($comments_data["Access_ID"] > 0 ? '' : 'disabled ') .">";
    foreach ($comments_change_variants AS $key => $value) {
        switch (true) {
            case empty($comments_data) && empty($parent_comment_rule) && $key == "disable":
                $opt_selected = " selected";
                break;
            case empty($comments_data) && $parent_comment_rule['Edit_Rule'] == $key:
                $opt_selected = " selected";
                break;
            case $comments_data['Edit_Rule'] == $key:
                $opt_selected = " selected";
                break;
            default:
                $opt_selected = "";
        }
        $html .= "      <option value='$key'$opt_selected>$value</option>";
    }

    $html .= "      </select>

                </div>
            </div>

            <div>
                <div class='col_1'>
                    ".CONTROL_CONTENT_SUBDIVISION_FUNCS_COMMENTS_AUTHOR_DELETE."
                </div>

                <div class='col_2'>";
    $html .= "      <select name='CommentsDeleteRules' id='CommentsDeleteRules'". ($comments_data["Access_ID"] > 0 ? '' : 'disabled ') .">";
    foreach ($comments_change_variants AS $key => $value) {
        switch (true) {
            case empty($comments_data) && empty($parent_comment_rule) && $key == "disable":
                $opt_selected = " selected";
                break;
            case empty($comments_data) && $parent_comment_rule['Delete_Rule'] == $key:
                $opt_selected = " selected";
                break;
            case $comments_data['Delete_Rule'] == $key:
                $opt_selected = " selected";
                break;
            default:
                $opt_selected = "";
        }
        $html .= "      <option value='$key'$opt_selected>$value</option>\r\n";
    }
    $html .= "      </select>
                </div>
            </div>
        </div>";
    return $html;
}

function nc_subdivision_form_edit_save() {
    // глобальные переменные нужны в файлах message_put, message_fields
    global $perm, $systemTableID, $systemTableName, $systemMessageID, $message, $nc_core, $db;
    global $FILES_FOLDER, $HTTP_FILES_PATH, $SUB_FOLDER, $DIRCHMOD;

    $nc_core = nc_Core::get_object();
    $sub_id = (int)$nc_core->input->fetch_get_post('SubdivisionID');
    // информация о разделе
    $parent = $nc_core->subdivision->get_by_id($sub_id, 'Parent_Sub_ID');
    $cat_id = $nc_core->subdivision->get_by_id($sub_id, 'Catalogue_ID');
    $cur_checked = $nc_core->subdivision->get_by_id($sub_id, 'Checked');
    $cur_nc_disallow_move_and_delete = $nc_core->subdivision->get_by_id($sub_id, 'ncDisallowMoveAndDelete');

    // проверка названия раздела
    $Subdivision_Name = trim($nc_core->input->fetch_get_post('Subdivision_Name'));
    if (!$Subdivision_Name) {
        throw new Exception(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_NAME);
    }
    // проверка ключевого слова
    $EnglishName = trim($nc_core->input->fetch_get_post('EnglishName'));
    if (empty($EnglishName)) {
      $EnglishName = nc_transliterate($Subdivision_Name, true);
    }
    // проверка на валидность
    $EnglishName = nc_check_english_name($cat_id, $sub_id, $EnglishName, 1, $parent);

    if (!$nc_core->subdivision->validate_english_name($EnglishName)) {
        throw new Exception(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID);
    }
    // проверка уникальности ключевого слова
    if (!IsAllowedSubdivisionEnglishName($EnglishName, $parent, $sub_id, $cat_id)) {
        throw new Exception(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_KEYWORD);
    }

    // закачка файлов
    $posting = 1;
    $systemMessageID = $message = $sub_id;
    $action = 'change';
    $nc_core->action = 'change';
    $component = new nc_Component(0, 2);
    $nc_image = $component->get_nc_image_field();
    $nc_icon = $component->get_nc_icon_field();

    $nc_image_name = 'f_' . $nc_image['name'];
    global $$nc_image_name;
    $nc_icon_name = 'f_' . $nc_icon['name'];
    global $$nc_icon_name;
    if ($nc_image['type'] == NC_FIELDTYPE_FILE) {
        global ${$nc_image_name . '_old'};
        global ${'f_KILL' . $nc_image['name']};
    }
    if ($nc_icon['type'] == NC_FIELDTYPE_FILE) {
        global ${$nc_icon_name . '_old'};
        global ${'f_KILL' . $nc_icon['name']};
    }
    $should_delete_nc_image = ${'f_KILL' . $nc_image['name']};
    $nc_image_file = $nc_core->input->fetch_files($nc_image_name);
    $is_new_nc_image_uploaded = $nc_image_name && $nc_image_file['tmp_name'];
    $should_update_nc_image = !$should_delete_nc_image || ($should_delete_nc_image && $is_new_nc_image_uploaded);

    $should_delete_nc_icon = ${'f_KILL' . $nc_icon['name']};
    $nc_icon_file = $nc_core->input->fetch_files($nc_icon_name);
    $is_new_nc_icon_uploaded = $nc_icon_name && $nc_icon_file['tmp_name'];
    $should_update_nc_icon = !$should_delete_nc_icon || ($should_delete_nc_icon && $is_new_nc_icon_uploaded);

    require $nc_core->ROOT_FOLDER . 'message_fields.php';
    if (!$posting) {
        echo $warnText;
        return false;
    }

    require $nc_core->ROOT_FOLDER . 'message_put.php';

    $update_fields = '';
    $update_inherited = array();

    for ($i = 0; $i < $fldCount; $i++) {
        // апдейтим только ncImage||ncIcon
        if ( (!$should_update_nc_image || $fld[$i] !== $nc_image['id']) && (!$should_update_nc_icon || $fld[$i] !== $nc_icon['id']) ) {
            continue;
        }
        if ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE) {
            continue; // поле недоступно никому
        }
        if ($fldInheritance[$i] == 1) {
            $update_inherited[$fld[$i]] = $fldValue[$i];
        }
        if (isset(${$fld[$i] . 'Defined'}) && ${$fld[$i] . 'Defined'}) {
          $update_fields .= "`{$fld[$i]}` = {${$fld[$i] . 'NewValue'}},";
        } else {
          $update_fields .= "`{$fld[$i]}` = {$fldValue[$i]}, ";
        }
    }

    if ($update_fields) {
        $db->query("UPDATE `Subdivision` SET {$update_fields} `Checked` = `Checked` WHERE `Subdivision_ID` = '{$sub_id}'");
    }

    // визуальные настройки

    $CustomSettings = "";
    $custom_subclass_id = intval($nc_core->input->fetch_get_post('custom_subclass_id'));
    $custom_class_id = intval($nc_core->input->fetch_get_post('custom_class_id'));

    if ($custom_subclass_id) {
        $SQL = "SELECT `CustomSettingsTemplate`
                    FROM `Class`
                        WHERE `Class_ID` = " . +$custom_class_id;
        $settings = $nc_core->db->get_var($SQL);
        if ($settings) {
            $a2f = new nc_a2f($settings, 'CustomSettings', false, $custom_subclass_id, 'class_settings');

            if (!$a2f->validate($_POST['CustomSettings'])) {
                throw new Exception($a2f->get_validation_errors());
            } else {
                $a2f->save_from_request_data('CustomSettings');
                $CustomSettings = $a2f->get_values_as_string();
                $SQL = "SELECT `CustomSettings` FROM `Sub_Class`
                                  WHERE `Sub_Class_ID` = " . +$custom_subclass_id;
                $cur_settings = $nc_core->db->get_var($SQL);

                if ($CustomSettings <> $cur_settings) {
                    $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $cat_id, $sub_id, $custom_subclass_id);

                    $nc_core->db->query("UPDATE `Sub_Class` SET `CustomSettings` = '".$nc_core->db->prepare($CustomSettings)."'
                  WHERE `Sub_Class_ID` = '".$custom_subclass_id."'");
                    $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $cat_id, $sub_id, $custom_subclass_id);
                }
            }
        }
    }

    // параметры для обновления
    $params = array('Priority', 'Checked', 'Favorite', 'LabelColor');
    if ($perm->isSupervisor()) {
        $params[] = 'ncDisallowMoveAndDelete';
    }
    $fields = array('EnglishName' => $EnglishName, 'Subdivision_Name' => $Subdivision_Name);
    foreach ($params as $v) {
        $fields[$v] = $nc_core->input->fetch_get_post($v);
    }

    $Checked = intval($nc_core->input->fetch_get_post('Checked'));

    if ($cur_nc_disallow_move_and_delete && !$Checked && $cur_checked) {
        $Checked = $fields['Checked'] = 1;
        nc_print_status(CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_HIDE, 'error');
    }

    if (!$Checked && $cur_checked && $nc_core->subdivision->is_disallow_sub_class_subdivision_id($sub_id)) {
        $Checked = $fields['Checked'] = 1;
        nc_print_status(CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_HIDE_SUBDIVISION_SUB_CLASS, 'error');
    }

    if ($cur_checked != $Checked) {
        $nc_core->event->execute($Checked ? nc_Event::BEFORE_SUBDIVISION_ENABLED : nc_Event::BEFORE_SUBDIVISION_DISABLED, $cat_id, $sub_id);
    }

    $nc_core->subdivision->update($sub_id, $fields);

    // обновим Hidden_URL
    $hidden_url = GetHiddenURL($parent);
    UpdateHiddenURL($hidden_url ? $hidden_url : "/", $parent, $cat_id);

    // произошло включение / выключение
    if ($cur_checked != $Checked) {
        $nc_core->event->execute($Checked ? nc_Event::AFTER_SUBDIVISION_ENABLED : nc_Event::AFTER_SUBDIVISION_DISABLED, $cat_id, $sub_id);
    }

    return true;
}

function nc_subdivision_form_design_save() {
    $nc_core = nc_Core::get_object();
    $sub_id = intval($nc_core->input->fetch_get_post('SubdivisionID'));
    $Template_ID = intval($nc_core->input->fetch_get_post('Template_ID'));
    $UseEditDesignTemplate = intval($nc_core->input->fetch_get_post('UseEditDesignTemplate'));

    // визуальные настройки
    $TemplateSettings = "";
    if ($Template_ID) {
        $settings = $nc_core->template->get_custom_settings($Template_ID);
        if ($settings && ($_POST['TemplateSettings'] || $_FILES['TemplateSettings'])) {
            $a2f = new nc_a2f($settings, 'TemplateSettings', false, $Template_ID, 'template_settings');
            if (!$a2f->validate($_POST['TemplateSettings'])) {
                throw new Exception($a2f->get_validation_errors());
            }
            $a2f->save_from_request_data('TemplateSettings');
            $TemplateSettings = $a2f->get_values_as_string();
        }
    }

    $DisplayType = $nc_core->input->fetch_get_post('DisplayType');

    // обновление раздела
    $is_parent_template = $_POST['is_parent_template'] == 'true' ? true : false;
    $fields = array(
            'Template_ID' => $is_parent_template ? '' : $Template_ID,
            'TemplateSettings' => $TemplateSettings,
            'UseEditDesignTemplate' => $UseEditDesignTemplate,
            'DisplayType' => $DisplayType,
            'MainArea_Mixin_Settings' => $nc_core->input->fetch_post('MainArea_Mixin_Settings'),
    );
    $nc_core->subdivision->update($sub_id, $fields);

    return true;
}

function nc_subdivision_form_seo_save() {
    // глобальные переменные нужны в файлах message_put, message_fields
    global $perm, $systemTableID, $systemTableName, $systemMessageID, $message, $nc_core, $db;
    global $FILES_FOLDER, $HTTP_FILES_PATH, $SUB_FOLDER, $DIRCHMOD;

    $nc_core = nc_Core::get_object();
    $sub_id = (int)$nc_core->input->fetch_get_post('SubdivisionID');
    $posting = 1;
    $systemMessageID = $message = $sub_id;
    $action = 'change';
    $nc_core->action = 'change';

    $component = new nc_Component(0, 2);
    $smo_image = $component->get_smo_image_field();

    $smo_image_name = 'f_' . $smo_image['name'];
    global $$smo_image_name;
    if ($smo_image['type'] == NC_FIELDTYPE_FILE) {
        global ${$smo_image_name . '_old'};
        global ${'f_KILL' . $smo_image['name']};
    }

    $should_delete_smo_image = ${'f_KILL' . $smo_image['name']};
    $smo_image_file = $nc_core->input->fetch_files($smo_image_name);
    $is_new_smo_image_uploaded = $smo_image_file && $smo_image_file['tmp_name'];
    $should_update_smo_image = !$should_delete_smo_image || ($should_delete_smo_image && $is_new_smo_image_uploaded);

    require $nc_core->ROOT_FOLDER . 'message_fields.php';
    if (!$posting) {
        echo $warnText;
        return false;
    }

    require $nc_core->ROOT_FOLDER . 'message_put.php';

    $update_fields = '';
    $update_inherited = array();

    for ($i = 0; $i < $fldCount; $i++) {
        //в случае SEO вкладки апдейтим только SMO_Image
        if (!$should_update_smo_image || $fld[$i] !== $smo_image['id']) {
            continue;
        }
        if ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE) {
            continue; // поле недоступно никому
        }
        if ($fldInheritance[$i] == 1) {
            $update_inherited[$fld[$i]] = $fldValue[$i];
        }
        if (isset(${$fld[$i] . 'Defined'}) && ${$fld[$i] . 'Defined'}) {
          $update_fields .= "`{$fld[$i]}` = {${$fld[$i] . 'NewValue'}},";
        } else {
          $update_fields .= "`{$fld[$i]}` = {$fldValue[$i]}, ";
        }
    }

    if ($update_fields) {
        $db->query("UPDATE `Subdivision` SET {$update_fields} `Checked` = `Checked` WHERE `Subdivision_ID` = '{$sub_id}'");
    }

    if(!empty($update_inherited)) {
        foreach ($update_inherited as $key => $value) {
            $update_inherited[$key] = "`{$key}` = {$value}";
        }
        $db->query('UPDATE `Subdivision` SET ' . implode(', ', $update_inherited) . " WHERE `Parent_Sub_ID` = '{$sub_id}'");
    }

    $nc_core->subdivision->update($sub_id, array('Subdivision_ID' => $sub_id));

    // метатэги
    $meta_tags = array('title', 'keywords', 'description', 'smo_title', 'smo_description');
    foreach ($meta_tags as $v) {
        $user_value[$v] = $nc_core->input->fetch_get_post($v);
        $fields[$nc_core->page->get_field_name($v)] = $user_value[$v];
    }

    // запрет индексации и настройки sitemap'a
    $fields[$nc_core->page->get_field_name('last_modified_type')] = $nc_core->input->fetch_get_post('last_modified_type');
    if ($nc_core->modules->get_by_keyword('search')) {
        $fields['DisallowIndexing'] = (int)$nc_core->input->fetch_get_post('DisallowIndexing');
        foreach (array('sitemap_include', 'sitemap_changefreq', 'sitemap_priority') as $v) {
            $fields[$nc_core->page->get_field_name($v)] = $nc_core->input->fetch_get_post($v);
        }
    }

	$fields['ncH1'] = $nc_core->input->fetch_get_post('h1');
    $nc_core->subdivision->update($sub_id, $fields);

    $site_url = $nc_core->catalogue->get_url_by_id($nc_core->subdivision->get_by_id($sub_id, 'Catalogue_ID'));
    // проверка
    $real_value = $nc_core->page->fetch_page_metatags($site_url . nc_folder_path($sub_id));
    foreach ($meta_tags as $v) {
        if (nc_array_value($real_value, $v) && nc_array_value($user_value, $v) && nc_array_value($user_value, $v) != nc_array_value($real_value, $v)) {
            nc_print_status(sprintf(CONTROL_CONTENT_SUBDIVISION_SEO_VALUE_NOT_SETTINGS, $v, $v), 'info');
        }
    }

    return true;
}

function nc_subdivision_form_system_save() {
    $nc_core = nc_Core::get_object();
    $sub_id = intval($nc_core->input->fetch_get_post('SubdivisionID'));
    $cat_id = $nc_core->subdivision->get_by_id($sub_id, 'Catalogue_ID');

    $params = array('ExternalURL', 'Read_Access_ID', 'Write_Access_ID',
            'Edit_Access_ID', 'Checked_Access_ID', 'Delete_Access_ID', 'Moderation_ID');
    if ($nc_core->modules->get_by_keyword('cache')) {
        $params[] = 'Cache_Access_ID';
        $params[] = 'Cache_Lifetime';
    }

    foreach ($params as $v) {
        $fields[$v] = $nc_core->input->fetch_get_post($v);
    }
    // язык
    $fields[$nc_core->page->get_field_name('language')] = $nc_core->input->fetch_get_post('language');

    // комментарии
    if ($nc_core->modules->get_by_keyword("comments")) {
        include_once nc_module_folder('comments') . 'function.inc.php';
        $CommentAccessID = $nc_core->input->fetch_get_post('CommentAccessID');
        $CommentsEditRules = $nc_core->input->fetch_get_post('CommentsEditRules');
        $CommentsDeleteRules = $nc_core->input->fetch_get_post('CommentsDeleteRules');
        // get rule id
        $CommentData = nc_comments::getRuleData($nc_core->db, array($cat_id, $sub_id));
        $CommentRelationID = $CommentData['ID'];
        // do something
        switch (true) {
            case $CommentAccessID > 0 && $CommentRelationID:
                // update comment rules
                nc_comments::updateRule($nc_core->db, array($cat_id, $sub_id), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                break;
            case $CommentAccessID > 0 && !$CommentRelationID:
                // add comment relation
                $CommentRelationID = nc_comments::addRule($nc_core->db, array($cat_id, $sub_id), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                break;
            case $CommentAccessID <= 0 && $CommentRelationID:
                // delete comment rules
                nc_comments::dropRuleSubdivision($nc_core->db, $sub_id);
                $CommentRelationID = 0;
                break;
        }
        $fields['Comment_Rule_ID'] = $CommentRelationID;
    }
    // RSS
    $cc_in_sub = $nc_core->db->get_results("SELECT `Sub_Class_ID` as `id`, `AllowRSS` as `cur` FROM `Sub_Class` WHERE `Subdivision_ID` = '".$sub_id."' ", ARRAY_A);
    if (!empty($cc_in_sub))
            foreach ($cc_in_sub as $v) {
            // значение, пришедшие из формы
            $allow_rss = intval($nc_core->input->fetch_get_post('AllowRSS'.$v['id']));
            // в случае, если значение изменилось
            if ($allow_rss != $v['cur']) {
                $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $cat_id, $sub_id, $v['id']);
                $nc_core->db->query("UPDATE `Sub_Class` SET `AllowRSS` = '".$allow_rss."' WHERE `Sub_Class_ID` = '".$v['id']."' ");
                $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $cat_id, $sub_id, $v['id']);
            }
        }

    $nc_core->subdivision->update($sub_id, $fields);
    return true;
}

function nc_subdivision_form_fields_save() {
    // глобальные переменные нужны в файлах message_put, message_fields
    global $perm, $systemTableID, $systemTableName, $systemMessageID, $message, $nc_core, $db;
    global $FILES_FOLDER, $HTTP_FILES_PATH, $SUB_FOLDER, $DIRCHMOD;

    $nc_core = nc_Core::get_object();
    $sub_id = intval($nc_core->input->fetch_get_post('SubdivisionID'));
    $posting = 1;
    $systemMessageID = $message = $sub = $sub_id;
    $action = 'change';
    $nc_core->action = 'change';

    $st = new nc_component(0, 2);
    $system_file_fields = array();
    foreach ($st->get_nc_file_fields() as $nc_file_field) {
        $system_file_fields[] = $nc_file_field['name'];
    }

    foreach ($st->get_fields() as $v) {
        $name = 'f_' . $v['name'];
        global $$name;

        if ($v['type'] == NC_FIELDTYPE_FILE) {
            global ${$name . '_old'};
            global ${'f_KILL' . $v['id']};
        }

        if ($v['type'] == NC_FIELDTYPE_DATETIME) {
            global ${$name . '_day'},
                   ${$name . '_month'},
                   ${$name . '_year'},
                   ${$name . '_hours'},
                   ${$name . '_minutes'},
                   ${$name . '_seconds'};
         }
    }

    /** @var array $fldCount */
    /** @var array $fldTypeOfEdit */
    /** @var array $fldInheritance */
    /** @var array $fldValue */
    /** @var array $fldType */
    /** @var array $fld */
    /** @var string $warnText */

    require $nc_core->ROOT_FOLDER . "message_fields.php";

    if (!$posting) {
        echo $warnText;
        return false;
    }


    require $nc_core->ROOT_FOLDER . "message_put.php";

    $update = "UPDATE `Subdivision` SET ";
    for ($i = 0; $i < $fldCount; $i++) {
        if ($fldTypeOfEdit[$i] == NC_FIELD_PERMISSION_NOONE) {
            // поле недоступно никому
            continue;
        }

        // в случае вкладки Дополнительные настройки исключаем ncSMO_Image, ncImage, ncIcon
        if (in_array($fld[$i], $system_file_fields, true)) {
            continue;
        }

        $field_name = $fld[$i];

        if (isset(${$field_name . 'Defined'}) && ${$field_name . 'Defined'} == true) {
            $quoted_new_field_value = ${$field_name . 'NewValue'};
        } else {
            $quoted_new_field_value = $fldValue[$i];
        }

        $update .= "`$field_name` = $quoted_new_field_value, ";
    }

    $update .= " `Checked` = `Checked` WHERE `Subdivision_ID` = '" . $sub_id . "'";

    $site_id = intval($nc_core->subdivision->get_by_id($sub_id, 'Catalogue_ID'));

    $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $site_id, $sub_id);
    $db->query($update);
    $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $site_id, $sub_id);

    return true;
}

function nc_subdivision_show_add_form($catalogue_id = 0, $parent_sub_id = 0) {
    global $perm;
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    if (($parent_sub_id = intval($parent_sub_id))) {
        $catalogue_id = $nc_core->subdivision->get_by_id($parent_sub_id, 'Catalogue_ID');
    }
    $catalogue_id = intval($catalogue_id);

    echo "<form action='index.php' method='post' enctype='multipart/form-data' >";
    echo "<input type='hidden' name='CatalogueID' value='".intval($catalogue_id)."' />";
    echo "<input type='hidden' name='ParentSubID' value='".intval($parent_sub_id)."' />";
    echo "<input type='hidden' name='phase' value='3' />";
    echo "<input type='hidden' name='posting' value='1' />";
    echo "<input type='submit' class='hidden' />";
    echo nc_Core::get_object()->token->get_input();

    $Priority = $db->get_var("SELECT (Priority+1) FROM Subdivision WHERE Parent_Sub_ID='".$parent_sub_id."' AND Catalogue_ID='".$catalogue_id."' ORDER BY Priority DESC LIMIT 1");
    echo nc_subdivision_moderate_form(array('Checked' => 1, 'Priority' => $Priority + 0));

    $field_main = new nc_admin_fieldset();
    echo "<br />";
    $field_main->add(nc_admin_input(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_NAME, 'Subdivision_Name', $nc_core->input->fetch_get_post('Subdivision_Name'), 50));
    $field_main->add(nc_admin_input(CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD, 'EnglishName', $nc_core->input->fetch_get_post('EnglishName'), 50, "", " data-type='transliterate' data-from='Subdivision_Name' data-is-url='yes' "));
    echo $field_main->result();

    echo nc_subdivision_show_component($catalogue_id);

    $template_id = $parent_sub_id ? $nc_core->subdivision->get_by_id($parent_sub_id, 'Template_ID') : $nc_core->catalogue->get_by_id($catalogue_id, 'Template_ID');
    echo nc_subdivision_form_design(array('_db_inherit_Template_ID' => $template_id), null, true, $parent_sub_id);
    echo nc_subdivision_addfavorites_form();
    if ($perm->isSupervisor()) {
        echo nc_subdivision_disallowmoveanddelete_form();
    }
    echo "</form>";
}

function nc_subdivision_show_component($catalogue_id = 0, $fieldset_caption = CONTROL_USER_FUNCS_CLASSINSECTION) {
    $nc_core = nc_Core::get_object();
    $Class_ID = (int)$nc_core->input->fetch_get_post('Class_ID');
    $Class_Template_ID = (int)$nc_core->input->fetch_get_post('Class_Template_ID');
    $CatalogueID = (int)$nc_core->input->fetch_get_post('CatalogueID') ?: $catalogue_id;

    $db = $nc_core->db;

    $classes = (array)$db->get_results(
        "SELECT `Class_ID` as `value`,
                IF (`IsAuxiliary` = 1,
                    CONCAT(`Class_ID`, '. ', `Class_Name`, ' {$db->escape(CONTROL_CLASS_AUXILIARY)}'),
                    CONCAT(`Class_ID`, '. ', `Class_Name`))
                AS `description`,
                `Class_Group` as `optgroup`,
                `IsAuxiliary` as `is_auxiliary`,
                `File_Mode`
                FROM `Class`
                WHERE `ClassTemplate` = 0
                ORDER BY `File_Mode` DESC, `Class_Group`, `Priority`, `Class_ID`",
        ARRAY_A
    );

    foreach ($classes as $class) {
        if ($class['File_Mode']) {
            $classesV5[] = $class;
        } else {
            $classesV4[] = $class;
        }
    }

    $default_class = $nc_core->catalogue->get_default_component_info($CatalogueID);

    $field_class = new nc_admin_fieldset($fieldset_caption);

    $html = "\t<table border='0' cellpadding='6' cellspacing='0' width='100%'><tr><td>\n";
    $html .= "\t<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td>\n";

    if (!empty($classesV4) || !empty($classesV5)) {
        $html .= "<font color=gray>" . CONTROL_CLASS_CLASS . ":<br>";
        $class_group_select_style = ((empty($classesV5) || empty($classesV4)) ? 'padding: 7px 0' : '');

        $html .= "<select size='10' style='width:30%;{$class_group_select_style}' name='Class_Groups'></select>";
        $html .= "&nbsp;<select size='10' style='width: 65%' data-catalogue-id='{$CatalogueID}' id='Class_ID' name='Class_ID'></select>";
	    if (!empty($default_class)) {
            $html .= "<p>" . CONTROL_CLASS_DEFAULT_CHANGE . "</p>";
        }
        $html .= "<div style='margin:5px 0;'>
                      <input name='hide_aux' id='hide_aux' value='0' type='checkbox'>
                      <label for='hide_aux'>" . CONTROL_CLASS_SHOW_AUX . "</label>
                  </div>";
        $html .= "<div id='loadClassDescription'></div>";
    } else {
        $html .= CONTROL_CLASS_NONE;
    }
    $html .= "\t</table>\n";

    $html .= "\t</td></tr></table>\n";
    $html .= "</fieldset>\n";

    $html .= '<script>
                $nc(function() {
                    const class_groups_select = $nc("select[name=\'Class_Groups\']");
                    nc_component_reload_options(null, false);

                    $nc("#hide_aux").change(function() {
                        nc_component_reload_options(class_groups_select.val());
                    });
                    
                    class_groups_select.change(function() {
                        nc_component_reload_options($nc(this).val());
                    });
                
                    nc_infoblock_on_component_change_init();
                });
              </script>';

    $field_class->add($html);

    return $field_class->result($html);
}

/**
 * @throws nc_Exception_DB_Error
 * @throws nc_Exception_Class_Doesnt_Exist
 * @throws Exception
 */
function nc_subdivision_add($input = null) {
    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    if (!$input) {
        $input = $nc_core->input->fetch_get_post();
    }

    $CatalogueID = intval($input['CatalogueID']);
    $ParentSubID = intval($input['ParentSubID']);
    $Template_ID = intval($input['Template_ID']);
    $Class_ID    = intval($input['Class_ID']);
    $Class_Template_ID = (int)nc_array_value($input, 'Class_Template_ID', 0);

    // проверка названия раздела
    $Subdivision_Name = trim($input['Subdivision_Name']);

    if (!$Subdivision_Name) {
        throw new Exception(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_NAME);
    }
    // проверка ключевого слова
    $EnglishName = trim($input['EnglishName']);

    if (empty($EnglishName)) {
      $EnglishName = nc_transliterate($Subdivision_Name, true);
    }
    // проверка на валидность
    $EnglishName = nc_check_english_name($CatalogueID, 0, $EnglishName, 1, $ParentSubID);

    if (!$nc_core->subdivision->validate_english_name($EnglishName)) {
        throw new Exception(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID);
    }

    // проверка уникальности ключевого слова
    if (!IsAllowedSubdivisionEnglishName($EnglishName, $ParentSubID, 0, $CatalogueID)) {
        throw new Exception(CONTROL_CONTENT_SUBDIVISION_INDEX_ERROR_THREE_KEYWORD);
    }

    // визуальные настройки
    $TemplateSettings = "";

    if ($input['is_parent_template'] == 'true') {
        $Template_ID = 0;
    }

    if ($Template_ID) {
        $settings = $nc_core->template->get_custom_settings($Template_ID);

        if ($settings) {
            $a2f = new nc_a2f($settings, 'TemplateSettings', false, $Template_ID, 'template_settings');
            $a2f->set_initial_values();

            if (!$a2f->validate($input['TemplateSettings'])) {
                throw new Exception($a2f->get_validation_errors());
            }

            if (!empty($input['TemplateSettings'])) {
                $a2f->save_from_request_data('TemplateSettings');
                $TemplateSettings = $a2f->get_values_as_string();
            }
        }
    }

      if ($Class_ID) {
        // визуальные настройки
        $CustomSettings = "";
        $settings_array = $nc_core->component->get_by_id($Class_Template_ID ?: $Class_ID, 'CustomSettingsTemplate');

        if ($settings_array) {
            $a2f = new nc_a2f($settings_array, 'CustomSettings');
            $a2f->set_initial_values();

            if (!empty($input['CustomSettings'])) {
                if (!$a2f->validate($input['CustomSettings'])) {
                    throw new Exception($a2f->get_validation_errors());
                }

                $a2f->set_values($input['CustomSettings']);
            }

            if (!empty($_FILES)) {
                $a2f->save_from_request_data('CustomSettings');
            }

            $CustomSettings = $a2f->get_values_as_string();
        }
    }

    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_SUBDIVISION_CREATED, $CatalogueID, 0);

    // добавление раздела
    $favorite = isset($input['Favorite']) ? (int)$input['Favorite'] : 0;
    $user_edit_design_template = isset($input['UseEditDesignTemplate']) ? (int)($input['UseEditDesignTemplate']) : 0;
    $disallow_move_and_delete = isset($input['ncDisallowMoveAndDelete']) ? (int)($input['ncDisallowMoveAndDelete']) : 0;

    $db->query("
        INSERT INTO `Subdivision`
            SET `Created` = NOW(),
                `Subdivision_Name` = '".$db->escape($Subdivision_Name)."',
                `EnglishName` = '".$db->escape($EnglishName)."',
                `Parent_Sub_ID` = '".$ParentSubID."',
                `Catalogue_ID` = '".$CatalogueID."',
                `Checked` = '".intval($input['Checked'])."',
                `Priority` = '".intval($input['Priority'])."',
                `Favorite` = '$favorite',
                `UseMultiSubClass` = 1,
                `Template_ID` = '".$Template_ID."',
                `TemplateSettings` = '".$db->prepare($TemplateSettings)."',
                `UseEditDesignTemplate` = '$user_edit_design_template',
                `DisplayType` = '" . $db->escape($input['DisplayType']) . "',
                `ncDisallowMoveAndDelete` = '$disallow_move_and_delete',
                `MainArea_Mixin_Settings` = '" . $db->escape(nc_array_value($input, 'MainArea_Mixin_Settings')) . "'");

    if ($db->is_error) {
        throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
    }

    $SubdivisionID = $db->insert_id;

    // обновим Hidden_URL
    $hidden_url = GetHiddenURL($ParentSubID);
    UpdateHiddenURL($hidden_url ? $hidden_url : "/", $ParentSubID, $CatalogueID);

    $nc_core->event->execute(nc_Event::AFTER_SUBDIVISION_CREATED, $CatalogueID, $SubdivisionID);

    if ($Class_ID) {
        $Sub_Class_Name = $db->escape($Subdivision_Name);
        $Sub_Class_EnglishName = nc_transliterate($Sub_Class_Name, true);
        $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_CREATED, $CatalogueID, $SubdivisionID, 0);

        $db->query("INSERT INTO `Sub_Class`
      (`Subdivision_ID`, `Catalogue_ID`, `Class_ID`, `Sub_Class_Name`, `Checked`, `EnglishName`, `Created`, `CustomSettings`, `Class_Template_ID`)
       VALUES
       ('".$SubdivisionID."', '".$CatalogueID."', '".$Class_ID."', '".$Sub_Class_Name."', 1, '".$Sub_Class_EnglishName."',  '".date("Y-m-d H:i:s")."',  '".addcslashes($CustomSettings, "'")."', '".$Class_Template_ID."')");

        if (($SubClassID = $db->insert_id)) {
            $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_CREATED, $CatalogueID, $SubdivisionID, $SubClassID);
        }
    }

    nc_tpl_mixin_cache::generate_subdivision_files($SubdivisionID, true);

    return $SubdivisionID;
}

function nc_get_modal_radio($name, array $items, $checked_value) {
    static $first_use = true;
    $html = '';

    if ($first_use) {
        $html .= "
            <style>
                div.nc_radio_modal > div > div {
                    display: inline-block;
                    height: 20px;
                }

                div.nc_radio_modal > div {
                    padding-bottom: 8px;
                }

                div.nc_radio_modal_input {
                    padding-right: 15px;
                    vertical-align: middle;
                }

                div.nc_radio_modal {
                    padding-top: 13px;
                }
            </style>";
        $first_use = false;
    }

    $html .= "<div class='nc_radio_modal'>";

    foreach ($items as $item) {
        if (!isset($item['attr']['name'])) {
            $item['attr']['name'] = $name;
        }

        $item['attr']['type'] = 'radio';
        $item['attr']['style'] = !empty($item['attr']['style']) ? $item['attr']['style'] : '';

        if ($item['attr']['value'] == $checked_value) {
            $item['attr']['checked'] = 'checked';
        }

        $html .= "
            <div>
                <label>
                    <input ".nc_create_attr_str($item['attr'])." />
                    ".$item['desc']."
                </label>
            </div>";
    }

    $html .= "</div>";
    return $html;
}

function nc_create_attr_str(array $attr) {
    $result = array();
    foreach ($attr as $name => $value) {
        $result[] = $name . "='".$value."'";
    }
    return join(' ', $result);
}

function nc_print_root_subdivisions($CatalogueID, $ParentSubID = 0, $level = 0) {
    global $db, $nc_core;
    global $perm;

    $CatalogueID = intval($CatalogueID);
    $ParentSubID = intval($ParentSubID);

    static $security_limit, $initialized;

    if (!$initialized) {
        $initialized = true;
        $allow_id = $perm->GetAllowSub($CatalogueID, MASK_ADMIN | MASK_MODERATE);
        $security_limit = is_array($allow_id) && !$perm->isGuest() ? " Subdivision_ID IN (".join(', ', (array) $allow_id).")" : " 1";
    }

    $result = $db->get_results("SELECT a.Subdivision_ID,a.Subdivision_Name,a.Priority,a.Checked,a.Hidden_URL,b.Domain,a.Catalogue_ID,a.ExternalURL FROM Subdivision AS a, Catalogue AS b
    WHERE a.Catalogue_ID=b.Catalogue_ID AND a.Catalogue_ID = {$CatalogueID} AND a.Parent_Sub_ID = {$ParentSubID} AND {$security_limit} ORDER BY a.Priority", ARRAY_A);

    if (!$result) {
        return false;
    }

    $spacer = '&nbsp;&nbsp;';

    for ($i = 0; $i < $level; $i++) {
        $spacer .= '&nbsp;&nbsp;';
    }

    foreach($result as $row) {
        echo "<option value='{$row['Subdivision_ID']}'>{$spacer}{$row['Subdivision_ID']}. {$row['Subdivision_Name']}</option>";
        nc_print_root_subdivisions($CatalogueID, $row['Subdivision_ID'], $level + 1);
    }

    return true;
}


/**
 *
 * Функция проверки English name на уникальность, в случае совпадения
 * возвращает с числовым постфиксом "-номер", увеличенным на 1
 *
 * @param type $obj_id
 * @param type $string
 * @param type $type
 * @return string
 */
function nc_check_english_name($Catalogue_ID=0, $obj_id=0, $string = "", $type = 1, $add_value=0) {
  global $db;
  switch ($type) {
    case 1:
      $table = "Subdivision";
      $table_id = "Subdivision_ID";
      $add_id = "Parent_Sub_ID";
      break;
    case 2:
      $table = "Sub_Class";
      $table_id = "Sub_Class_ID";
      $add_id = "Subdivision_ID";
      break;
    default :
      return $string;
      break;
  }
  $sql = "SELECT COUNT(*) as matches FROM ".$table." "
    . "WHERE Catalogue_ID = '".$Catalogue_ID."' AND EnglishName = '".$db->escape($string)."' AND ".$add_id."='".intval($add_value)."' AND ".$table_id." <> ".intval($obj_id)." ";
  $result = $db->get_row($sql, ARRAY_A);

  if ($result['matches'] > 0) {
        if (preg_match('/(-\d+)$/', $string, $match)) {
            $clean = str_replace("-".$match, "", $string);
        } else {
            $clean = $string;
        }

        $sql = "SELECT EnglishName FROM ".$table." "
          . "WHERE Catalogue_ID = '".$Catalogue_ID."' AND EnglishName LIKE '" . $db->escape($clean) . "-%' AND ".$table_id." <> " . intval($obj_id) . " ORDER BY ".$table_id." DESC LIMIT 1";
        $result = $db->get_row($sql, ARRAY_A);
        if (!empty($result['EnglishName']))  {
            preg_match('/(-\d+)$/', $result['EnglishName'], $match);
            $old_num = intval(str_replace("-", "", end($match)));
            $string = $clean."-".($old_num + 1);
        } else {
            $string = $clean."-1";
        }
  }
  return $string;
}
