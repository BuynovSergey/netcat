<?php

function ShowList() {
    global $db, $loc, $nc_core;
    global $EDIT_DOMAIN, $HTTP_ROOT_PATH;
    global $UI_CONFIG, $ADMIN_PATH, $ADMIN_TEMPLATE, $SUB_FOLDER;

    $Select = "SELECT a.Sub_Class_ID,
                      a.Sub_Class_Name,
                      b.Class_Name,
                      a.Priority,
                      a.Checked,
                      a.Class_ID,
                      a.EnglishName,
                      d.Domain,
                      c.Hidden_URL,
                      b.System_Table_ID,
                      c.UseMultiSubClass,
                      b.File_Mode,
                      c.Catalogue_ID
                   FROM (Sub_Class AS a,
                        Class AS b)
                     LEFT JOIN Subdivision AS c ON a.Subdivision_ID = c.Subdivision_ID
                     LEFT JOIN Catalogue AS d ON c.Catalogue_ID = d.Catalogue_ID
                       WHERE a.Subdivision_ID = {$loc->SubdivisionID}
                         AND a.Catalogue_ID = {$loc->CatalogueID}
                         AND a.Class_ID = b.Class_ID
                         AND b.`ClassTemplate` = 0
                           ORDER BY a.Priority";

    $Result = $db->get_results($Select, ARRAY_N);

    if ($totrows = $db->num_rows) {
        ?>
        <form enctype='multipart/form-data' method='post' action='SubClass.php'>

            <table border=0 cellpadding=0 cellspacing=0 width=100%><tr>

                        <table border=0 cellpadding=0 cellspacing=0 width=100% class='border-bottom'>
                            <tr>
                                <td>ID</td>
                                <td>
                                        <?
                                        if ($loc->SubdivisionID) {
                                            $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
                                        } else {
                                            $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
                                        }
                                        printf(CONTROL_CONTENT_SUBCLASS_CLASSNAME, $wsts);
                                        ?>
                                </td>
                                <td><?= CONTROL_CONTENT_CLASS ?></td>
                                <td align=center><div class='icons icon_prior' title='<?= CONTROL_CONTENT_SUBDIVISION_FUNCS_PRIORITY ?>'></div></td>
                                <td align=center><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_GOTO ?></td>
                                <td align=center><div class='icons icon_delete' title='<?=CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div></td>
                            </tr>
                                <?= CONTROL_CONTENT_SUBCLASS_MULTI_TITLE ?>
                            <?php
                            echo nc_get_modal_radio('UseMultiSubClass', array(
                                array(
                                    'attr' => array(
                                        'value' => '1',
                                    ),
                                    'desc' => CONTROL_CONTENT_SUBCLASS_MULTI_ONONEPAGE,
                                ),
                                array(
                                    'attr' => array(
                                        'value' => '2',
                                    ),
                                    'desc' => CONTROL_CONTENT_SUBCLASS_MULTI_ONTABS,
                                ),
                                array(
                                    'attr' => array(
                                        'value' => '0',
                                    ),
                                    'desc' => CONTROL_CONTENT_SUBCLASS_MULTI_NONE,
                                )
                            ), $Result[0][10]);

                            foreach ($Result as $Array) {
                                $hidden_host = $nc_core->catalogue->get_url_by_id($Array[12]) . $SUB_FOLDER;

                                print "<tr><td ><font " . (!$Array[4] ? " color=cccccc" : "") . ">" . $Array[0] . "</td>";
                                print "<td><a href='SubClass.php?phase=3&SubClassID={$Array[0]}&SubdivisionID={$loc->SubdivisionID}'>" . (!$Array[4] ? "<font color=cccccc>" : "") . $Array[1] . "</a></td>";
                                if (!$Array[9]) {
                                    print "<td><a href=" . $ADMIN_PATH . "class/index.php?phase=4&fs={$Array[11]}&ClassID=" . $Array[5] . ">" . (!$Array[4] ? "<font color=cccccc>" : "") . $Array[2] . "</a></td>";
                                } else {
                                    print "<td><a href=" . $ADMIN_PATH . "field/system.php?phase=2&fs={$Array[11]}&SystemTableID=" . $Array[9] . ">" . (!$Array[4] ? "<font color=cccccc>" : "") . $Array[2] . "</a></td>";
                                }
                                print "<td align=center>" . nc_admin_input_simple("Priority" . $Array[0], $Array[3], 3, '', "class='s'") . "</td>";
                                print "<td align=center>";

                                //setup
                                print "<a href=\"SubClass.php?phase=3&SubdivisionID=" . $loc->SubdivisionID . "&CatalogueID=" . $loc->CatalogueID . "&SubClassID=" . $Array[0] . "\"><div class='icons icon_settings" . (!$Array[4] ? "_disabled" : "") . "' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONSSUBCLASS."'></div></a>";

                                //edit
                                print "<a target=\"_blank\" href=\"" . $nc_core->catalogue->get_scheme_by_id($Array[12]) . '://' . $EDIT_DOMAIN . $SUB_FOLDER . $HTTP_ROOT_PATH . "?catalogue=" . $loc->CatalogueID . "&sub=" . $loc->SubdivisionID . "&cc=" . $Array[0] . (strlen(session_id()) > 0 ? "&" . session_name() . "=" . session_id() . "" : "") . "\"><div class='icons icon_pencil" . (!$Array[4] ? "_disabled" : "") . "' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOEDIT."'></div></a>";

                                //browse
                                print $loc->SubdivisionID ? "<a href=\"" . $hidden_host . $Array[8] . $Array[6] . ".html\" target=_blank><div class='icons icon_preview" . (!$Array[4] ? "_disabled" : "") . "' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOVIEW."'></div></a>" : "<img src='" . $ADMIN_PATH . "images/emp.gif' width=15 height=18 style='margin:0px 2px 0px 2px;'>";

                                print "</td>";
                                print "<td align=center>" . nc_admin_checkbox_simple("Delete" . $Array[0], $Array[0]) . "</td>\n";
                                print "</tr>\n";
                            }
                            ?>

                        </table></td></tr></table><br>
            <?
        } else {
            nc_print_status(CONTROL_CONTENT_SUBCLASS_MSG_NONE, 'info');
        }

        if ($totrows) {
            print $nc_core->token->get_input();
            print "<input type=hidden name=phase VALUE=5>";
            print "<input type=hidden name=CatalogueID VALUE=" . $loc->CatalogueID . ">";
            print "<input type=hidden name=SubdivisionID VALUE=" . $loc->SubdivisionID . ">";
            print "<input type='submit' class='hidden'>";
            print "</form>";
            $UI_CONFIG->actionButtons[] = array("id" => "submit",
                    "caption" => STRUCTURE_TAB_SUBCLASS_ADD,
                    "location" => "subclass.add(" . $loc->SubdivisionID . ")",
                    "align" => "left");

            $UI_CONFIG->actionButtons[] = array("id" => "delete",
                    "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                    "action" => "mainView.submitIframeForm()",
                    "align" => "right");
        }
    }

    function ShowList_for_modal() {
    global $loc;
    global $EDIT_DOMAIN, $HTTP_ROOT_PATH;
    global $ADMIN_PATH, $ADMIN_TEMPLATE, $SUB_FOLDER;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $Select = "SELECT a.Sub_Class_ID,
                      a.Sub_Class_Name,
                      b.Class_Name,
                      a.Priority,
                      a.Checked,
                      a.Class_ID,
                      a.EnglishName,
                      d.Domain,
                      c.Hidden_URL,
                      b.System_Table_ID,
                      c.UseMultiSubClass
                   FROM (Sub_Class AS a,
                        Class AS b)
                     LEFT JOIN Subdivision AS c ON a.Subdivision_ID=c.Subdivision_ID
                     LEFT JOIN Catalogue AS d ON c.Catalogue_ID=d.Catalogue_ID
                       WHERE a.Subdivision_ID = {$loc->SubdivisionID}
                         AND a.Catalogue_ID = {$loc->CatalogueID}
                         AND a.Class_ID = b.Class_ID
                         AND b.`ClassTemplate` = 0
                           ORDER BY a.Priority";

    $Result = $db->get_results($Select, ARRAY_N);
    $totrows = $db->num_rows;
    if ($totrows) {
        ?>
            <style>
        div.nc_sub_class_list_table > div {
            border-bottom: 1px #cccccc solid;
            display: table;
        }

        div.nc_sub_class_list_table > div > div {
            display: inline-block;
            padding-top: 9px;
            padding-bottom: 11px;
        }

        div.nc_sub_class_list_table div.col_1 {
            width: 42px;
        }

        div.nc_sub_class_list_table div.col_2,
        div.nc_sub_class_list_table div.col_3 {
            width: 335px;
        }

        div.nc_sub_class_list_table div.col_4 {
            text-align: center;
            width: 90px;
        }

        div.nc_sub_class_list_table div.col_5 {
            text-align: center;
            width: 76px;
        }

        div.nc_sub_class_list_table > div.row_1 {
            padding-top: 3px;
            padding-bottom: 2px;
        }

        div.nc_sub_class_list_table div.disabled {
            color: #cccccc;
        }

    </style>

    <div id='nc_sub_class_list_div' style="display: none;">
        <div class='nc_sub_class_list_table'>

            <div style="border-bottom: 0px; padding-bottom: 24px; padding-top: 12px;">
                <input style='margin-left: 0px;'type='checkbox' name='UseMultiSubClass' id='UseMultiSubClass' value='1'<?= $Result[0][10] ? " checked" : ""; ?> />
                <?= CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_MULTI_SUB_CLASS; ?>
            </div>

            <div class='row_1'>
                <div class='col_1'>
                    ID
                </div>

                <div class='col_2'>
                    <?php
                        if ($loc->SubdivisionID) {
                            $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
                        } else {
                            $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
                        }
                        printf(CONTROL_CONTENT_SUBCLASS_CLASSNAME, $wsts);
                    ?>
                </div>

                <div class='col_3'>
                    <?= CONTROL_CONTENT_CLASS; ?>
                </div>

                <div class='col_4'>
                    <?= CONTROL_CONTENT_SUBDIVISION_FUNCS_PRIORITY; ?>
                </div>

                <div class='col_5'>
                    <?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE; ?>
                </div>
            </div>

            <?php
                $SubClassID_list = array();

                foreach ($Result as $Array) {
                    $SubClassID_list[] = array(
                            'ID' => $Array[0],
                            'name' => $Array[1]);

                    echo "<div>
                            <div class='col_1" . (!$Array[4] ? " disabled" : "") . "'>
                                $Array[0]
                            </div>

                            <div class='col_2'>
                                <a href='$ADMIN_PATH#subdivision.edit({$loc->SubdivisionID})' >" . (!$Array[4] ? "<font color=cccccc>" : "") . $Array[1] . "</a>
                            </div>

                            <div class='col_3'>
                                ".(!$Array[9] ? "<a href='$ADMIN_PATH#dataclass.edit({$Array[5]})' target='_blank'>" . (!$Array[4] ? "<font color='cccccc'>" : "") . $Array[2] . "</a>"
                                                : "<a href='$ADMIN_PATH#systemclass.edit({$Array[9]})' target='_blank'>" . (!$Array[4] ? "<font color='cccccc'>" : "") . $Array[2] . "</a>")."
                            </div>

                            <div class='col_4'>
                                " . nc_admin_input_simple("Priority" . $Array[0], $Array[3], 3, '', "class='s'") . "
                            </div>

                            <div class='col_5'>
                                " . nc_admin_checkbox_simple("Delete" . $Array[0], $Array[0]) . "
                            </div>
                        </div>";
                }


            ?>
        </div><?

            echo $nc_core->token->get_input();
            echo "<input type='hidden' name='phase' value='5' />";
            echo "<input type='hidden' name='CatalogueID' value='{$loc->CatalogueID}' />";
            echo "<input type='hidden' name='SubdivisionID' value='{$loc->SubdivisionID}' />";
            echo "<input type='submit' style='display: none;' />";

            ?>
    </div>
            <?
        } else {
            nc_print_status(CONTROL_CONTENT_SUBCLASS_MSG_NONE, 'info');
        }
        return $SubClassID_list;
    }

#######################################################################

    function  ActionForm($SubClassID, $phase, $type) {
        global $loc, $perm;
        global $SubdivisionID;
        global $CatalogueID;
        global $UI_CONFIG, $SUB_FOLDER, $HTTP_ROOT_PATH, $MODULE_FOLDER, $ADMIN_FOLDER, $ADMIN_PATH;

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $SubClassID = (int)$SubClassID;
        $SubdivisionID = $SubdivisionID ? intval($SubdivisionID) : $nc_core->sub_class->get_by_id($SubClassID, 'Subdivision_ID');
        $CatalogueID = $CatalogueID ? intval($CatalogueID) : $nc_core->subdivision->get_by_id($SubdivisionID, 'Catalogue_ID');
        $phase = (int)$phase;

        if ($type == 2) {
            $SubEnv = $nc_core->sub_class->get_by_id($SubClassID, 0, 1, 1);
            $ClassEnv = $db->get_row("SELECT * FROM `Class` WHERE `Class_ID` = '" . intval($SubEnv["Class_ID"]) . "'", ARRAY_A);
        } elseif ($type == 1) {
            if (!$SubdivisionID) {
                $SubEnv = $db->get_row("SELECT * FROM `Catalogue` WHERE `Catalogue_ID` = '" . $CatalogueID . "'", ARRAY_A);
            } else {
                $SubEnv = $nc_core->subdivision->get_by_id($SubdivisionID);
            }
            $UI_CONFIG->locationHash = "subclass.add(" . $SubdivisionID . ")";
        }


        if (nc_module_check_by_keyword('calendar', false)) {
            echo nc_set_calendar(0);
        }

        echo "<form enctype='multipart/form-data' method='post' action='SubClass.php' id='adminForm' class='nc-form'>";

        if ($type == 1) {   // insert
            global $ClassID;

            if (!$ClassID) {
                if (empty($selected_value))
                    $selected_value = $db->get_var("SELECT `Class_ID` FROM `Class` ORDER BY `File_Mode` DESC, `Class_Group`, `Class_ID` LIMIT 1");
            } else {
                $selected_value = $ClassID;
            }
            $ClassEnv = $db->get_row("SELECT * FROM `Class` WHERE `Class_ID`='" . intval($selected_value) . "'", ARRAY_A);

            $Array["AllowTags"] = -1;
            $Array["NL2BR"] = -1;
            $Array["UseCaptcha"] = -1;

            global $SubClassName, $Read_Access_ID, $Write_Access_ID, $Edit_Access_ID, $DefaultAction;
            global $Checked_Access_ID, $Delete_Access_ID;
            global $SubscribeAccessID, $Moderation_ID, $Checked, $Priority, $CustomSettings;
            global $EnglishName, $DaysToHold, $AllowTags, $NL2BR, $RecordsPerPage, $MaxRecordsInInfoblock, $MinRecordsInInfoblock, $SortBy, $UseCaptcha, $Class_Template_ID, $isNaked, $Condition, $ConditionOffset, $ConditionLimit;
            if (nc_module_check_by_keyword("cache"))
                global $CacheForUser;

            if ($Priority == "" && $Checked == "")
                $Checked = 1;
            if ($Priority == "") {
                $Priority = $db->get_var("SELECT (`Priority` + 1) FROM `Sub_Class` WHERE `Subdivision_ID` = '" . $loc->SubdivisionID . "' ORDER BY `Priority` DESC LIMIT 1");
            }

            $Array["Sub_Class_Name"] = $SubClassName;
            $Array["Read_Access_ID"] = $Read_Access_ID;
            $Array["Write_Access_ID"] = $Write_Access_ID;
            $Array["Edit_Access_ID"] = $Edit_Access_ID;
            $Array["Checked_Access_ID"] = $Checked_Access_ID;
            $Array["Delete_Access_ID"] = $Delete_Access_ID;
            $Array["Subscribe_Access_ID"] = $SubscribeAccessID;
            if (nc_module_check_by_keyword("cache")) {
                $Array ["Cache_Access_ID"] = $CacheAccessID;
                $Array ["Cache_Lifetime"] = $CacheLifetime;
                $Array["CacheForUser"] = $CacheForUser != "" ? $CacheForUser : -1;
            }
            $Array["Moderation_ID"] = $Moderation_ID;
            $Array["DefaultAction"] = $DefaultAction;
            $Array["Checked"] = $Checked;
            $Array["Priority"] = $Priority;
            $Array["EnglishName"] = $EnglishName . (isset($Sub_Class_count) ? '-'.$Sub_Class_count : '');
            $Array["DaysToHold"] = $DaysToHold;
            if ($AllowTags != "")
                $Array["AllowTags"] = $AllowTags;
            if ($NL2BR != "")
                $Array["NL2BR"] = $NL2BR;
            if ($UseCaptcha != "")
                $Array["UseCaptcha"] = $UseCaptcha;
            $Array["RecordsPerPage"] = $RecordsPerPage;
            $Array["MinRecordsInInfoblock"] = $MinRecordsInInfoblock;
            $Array["MaxRecordsInInfoblock"] = $MaxRecordsInInfoblock;
            $Array["SortBy"] = $SortBy;
            $Array["Class_Template_ID"] = $Class_Template_ID;
            $Array["isNaked"] = $isNaked;
            $Array["SrcMirror"] = isset($SrcMirror) ? $SrcMirror : null;
            $Array["Condition"] = $Condition;
            $Array["ConditionOffset"] = $ConditionOffset;
            $Array["ConditionLimit"] = $ConditionLimit;
            $Array['ncDisallowMoveAndDelete'] = 0;

            // visual settings
            $Array['CustomSettingsTemplate'] = $nc_core->component->get_by_id($selected_value ?: $Class_Template_ID ?: $ClassID, 'CustomSettingsTemplate');

            $classInfo = "<tr><td>";

            $classInfo .= "
                <font color='gray'>" . CONTROL_CONTENT_SUBCLASS_TYPE . ":</font><br/>
                <select name='subclassType' onchange='onchageSubClassType(this.selectedIndex == 1)'>
                    <option value='0'>" . CONTROL_CONTENT_SUBCLASS_TYPE_SIMPLE . "</option>
                    <option value='1'>" . CONTROL_CONTENT_SUBCLASS_TYPE_MIRROR . "</option>
                </select>";

            $sub_class_caption = CONTROL_USER_FUNCS_CLASSINSECTION;
            if (count($nc_core->sub_class->get_by_subdivision_id($SubdivisionID)) > 0) {
                $sub_class_caption = CONTROL_CONTENT_SUBDIVISION_CLASS;
            }
            $classInfo .= "<div id='nc_infoblock_select'>" . nc_subdivision_show_component($CatalogueID, $sub_class_caption) . "</div>";

            $classInfo .= "
                <div id='nc_mirror_select' style='display: none;'>
                    <div>
                        " . CONTROL_CONTENT_SUBCLASS_SRCMIRROR . ":
                    </div>

                    <div>
                        <span id='cs_SrcMirror_caption' style='font-weight:bold;'>" . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_NONE . "</span>
                            <input id='cs_SrcMirror_value' name='SrcMirror' type='hidden' value='' />&nbsp;&nbsp;
                            <a href='#' onclick=\"window.open('" . $ADMIN_PATH . "related/select_subclass.php?cs_type=rel_cc&amp;cs_field_name=SrcMirror', 'nc_popup_SrcMirror', 'width=800,height=500,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes'); return false;\">
								" . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_EDIT . "
							</a>&nbsp;&nbsp;

                            <a href='#' onclick=\"document.querySelector('input[name=MirrorClassID]').value='';document.getElementById('cs_SrcMirror_value').value='';document.getElementById('cs_SrcMirror_caption').innerHTML = '" . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_NONE . "';return false;\">
                                " .CONTROL_CONTENT_SUBCLASS_SRCMIRROR_DELETE . "
                            </a>
                    </div>

                </div>";


            $classInfo.= "
                        <script>
                            var old_val = \$nc('#cs_SrcMirror_value').val();
                            setInterval(function() {
                                var val = \$nc('#cs_SrcMirror_value').val();
                                if (old_val != val) {
                                    if (val) {
                                        loadClassTemplates(val, 0, 0, 1);
                                    }
                                    old_val = val;
                                }
                            }, 200);                            
                        </script>";

            $classInfo.= "</td></tr>\n";
        }

        if ($type == 2) {

            $Array = $db->get_row(
                "SELECT c.`Class_Name`, c.`System_Table_ID`, 
                        s.* 
                   FROM `Sub_Class` AS s
                        JOIN `Class` AS c USING (`Class_ID`)
                  WHERE s.`Sub_Class_ID` = " . (int)$SubClassID,
                ARRAY_A
            );

            $Array['CustomSettingsTemplate'] = $nc_core->component->get_by_id($Array['Class_Template_ID'] ?: $Array['Class_ID'], 'CustomSettingsTemplate');

            if ($db->is_error) {
                throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
            }

            if (empty($Array)) {
                nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBCLASS, 'info');
                return;
            }

            $mobile = $nc_core->catalogue->get_by_id($CatalogueID, 'ncMobile');

            $template_types = $mobile ? array('mobile') : array('useful', 'title', 'mobile', 'responsive', 'multi_edit', 'inside_admin');
            $classTemplatesArr = $nc_core->component->get_component_templates($Array['Class_ID'], $template_types);

            $edit_template_select = nc_sub_class_template_select('edit', $Array['Class_ID'], $Array['Edit_Class_Template']);
            $admin_template_select = nc_sub_class_template_select('admin', $Array['Class_ID'], $Array['Admin_Class_Template']);

            $classInfo = nc_sub_class_get_classInfo($perm, $Array, $classTemplatesArr, $edit_template_select, $admin_template_select);
        }

        $wsts_msg = CONTROL_CONTENT_SUBCLASS_CLASSNAME;

        require_once($ADMIN_FOLDER."related/format.inc.php");
        $field = new field_relation_subclass();

        $fieldsets = new nc_admin_fieldset_collection();
        $fieldsets->set_prefix(
            nc_sub_class_get_checked($SubClassID, $Array, true) .
            $nc_core->token->get_input() . "
            <input type='hidden' name='phase' value='$phase' />
            <input type='hidden' name='SubClassID' value='$SubClassID' />
            <input type='hidden' name='SubdivisionID' value='{$loc->SubdivisionID}' />
            <input type='hidden' name='CatalogueID' value='{$loc->CatalogueID}' />
            <input type='hidden' name='MirrorClassID' value='' />"
        );
        $fieldsets->set_static_prefix(nc_sub_class_get_style_prefix());

        $fieldsets->new_fieldset('main_info', '', false)->add(nc_sub_class_get_main_info($Array, $classInfo, $wsts_msg, $field));

        $fieldsets->new_fieldset('objlist', CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_HOWSHOW, CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_HOWSHOW)->add(nc_sub_class_get_objlist($Array));
        $fieldsets->new_fieldset('access', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ACCESS, CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ACCESS)
            ->add(
                    nc_subdivision_show_access($SubEnv) .
                    ($perm->isSupervisor()
                        ? nc_admin_checkbox_simple('ncDisallowMoveAndDelete', 1, CONTROL_CONTENT_SUB_CLASS_FUNCS_DISALLOW_MOVE_AND_DELETE, $Array['ncDisallowMoveAndDelete'])
                        : ''
                    )
                );

        if ($type == 2) {
            $fieldsets->new_fieldset('rss', 'RSS', 'RSS')->add(nc_subclass_show_export('rss', $SubdivisionID, $SubClassID));
            $fieldsets->new_fieldset('xml', 'XML', 'XML')->add(nc_subclass_show_export('xml', $SubdivisionID, $SubClassID));
        }

        if (nc_module_check_by_keyword('cache')) {
            $fieldsets->new_fieldset('cache', CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE, CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE)->add(nc_subdivision_show_cache($SubEnv));
        }

        if (nc_module_check_by_keyword('comments')) {
            require_once nc_module_folder('comments') . 'function.inc.php';
            $fieldsets->new_fieldset('comments', CONTROL_CONTENT_SUBCLASS_FUNCS_COMMENTS, CONTROL_CONTENT_SUBCLASS_FUNCS_COMMENTS)->add(nc_subdivision_show_comments($SubEnv));
        }

        if ($type == 2 && !$Array['SrcMirror']) {
            $fieldsets->new_fieldset('condition', NETCAT_CONDITION_FIELD, NETCAT_CONDITION_FIELD)->add(nc_sub_class_nc_condition($CatalogueID, $SubClassID, $Array));
        }

        if ($type == 2) {
            $fieldsets->new_fieldset('mixin_index', NETCAT_MIXIN_TITLE_INDEX, NETCAT_MIXIN_TITLE_INDEX)
                ->add(nc_sub_class_get_mixin_editor(nc_tpl_mixin::SCOPE_INDEX));
            $fieldsets->new_fieldset('mixin_index_item', NETCAT_MIXIN_TITLE_INDEX_ITEM, NETCAT_MIXIN_TITLE_INDEX_ITEM)
                ->add(nc_sub_class_get_mixin_editor(nc_tpl_mixin::SCOPE_INDEX_ITEM));
        }

        $fieldsets->new_fieldset('created', '', '')->add(nc_sub_class_get_created($Array));

        echo $fieldsets->to_string();
        echo "</form>";

        if ($type == 1) {
            $UI_CONFIG->actionButtons[] = array("id" => "submit",
                    "caption" => STRUCTURE_TAB_SUBCLASS_ADD,
                    "action" => "mainView.submitIframeForm()",
                    "align" => "right"
            );

        } elseif ($type == 2) {
            $UI_CONFIG->actionButtons[] = array("id" => "submit",
                    "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                    "action" => "mainView.submitIframeForm()",
                    "align" => "right"
            );
        }
}

function ActionForm_for_modal_prefix($SubClassID_list, $one = false) {
    $nc_core = nc_Core::get_object();

    $li_array = array();

    if (!$one) {
        $li_array[] = "<li id='nc_sub_class_list' class='button_on'>".SUBDIVISION_TAB_INFO_TOOLBAR_CCLIST."</li>";
        foreach ($SubClassID_list as $SubClass) {
            $li_array[] = "<li id='nc_sub_class_{$SubClass['ID']}'>{$SubClass['name']}</li>";
        }
        $active = 'nc_sub_class_list_div';
    } else {
        $active = "nc_sub_class_{$SubClassID_list[0]['ID']}_div";
    }

?>
    <div class='nc_admin_form_menu' style='padding-top: 20px;'>
            <h2><?= $one ? $SubClassID_list[0]['name'] : STRUCTURE_TAB_USED_SUBCLASSES; ?></h2>
            <div id='nc_object_slider_menu' class='slider_block_2' style='padding-top: 0px; padding-bottom: 15px;'>
                <ul>
                    <?= join("\n                    ", $li_array); ?>
                </ul>
            </div>
            <div class='nc_admin_form_menu_hr'></div>
        </div>

        <script>
            var nc_slider_li = $nc('div#nc_object_slider_menu ul li');

            nc_slider_li.click(function() {
                nc_slider_li.removeClass('button_on');
                $nc(this).addClass('button_on');
                $nc('div.nc_current_content').html($nc('div#' + this.id + '_div').html());
            });

            setTimeout(function () {
                $nc('div.nc_current_content').html($nc('div#<?= $active; ?>').html());
            }, 250);
        </script>

        <div class='nc_admin_form_body'>
            <form id='adminForm' class='nc-form' method='post' action='<?= $nc_core->ADMIN_PATH ?>subdivision/SubClass.php' enctype='multipart/form-data'>
                <div class='nc_current_content'>
<?php

}

function ActionForm_for_modal_suffix() {
?>
                </div>
                <input type='hidden' name='isNaked' value='1' />
            </form>
    </div>
        <div class='nc_admin_form_buttons'>
            <button type='button' class='nc_admin_metro_button nc-btn nc--blue' disable><?= NETCAT_REMIND_SAVE_SAVE; ?></button>
            <button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'><?= CONTROL_BUTTON_CANCEL ?></button>
        </div>

        <style>
            a { color:#1a87c2; }
            a:hover { text-decoration:none; }
            a img { border:none; }
            p { margin:0px; padding:0px 0px 18px 0px; }
            h2 { font-size:20px; font-family:'Segoe UI', SegoeWP, Arial; color:#333333; font-weight:normal; margin:0px; padding:20px 0px 10px 0px; line-height:20px; }
            form { margin:0px; padding:0px; }
            input { outline:none; }
            .clear { margin:0px; padding:0px; font-size:0px; line-height:0px; height:1px; clear:both; float:none; }
            select, input, textarea { border:1px solid #dddddd; }
            :focus { outline:none;}
            .input { outline:none; border:1px solid #dddddd; }
        </style>

        <script>
            var nc_admin_metro_buttons = $nc('.nc_admin_metro_button');

            $nc(function() {
                $nc('#adminForm').html('<div class="nc_admin_form_main">' + $nc('#adminForm').html() + '</div>');
            });

            nc_admin_metro_buttons.click(function() {
                $nc('#adminForm').submit();
            });

            $nc('.nc_admin_metro_button_cancel').click(function() {
				$nc.modal.close();
			});
        </script>
<?php
}

function ActionForm_for_modal($SubClassID) {
        global $CatalogueID, $SubdivisionID, $loc, $perm;
        global $SUB_FOLDER, $HTTP_ROOT_PATH, $MODULE_FOLDER, $ADMIN_FOLDER, $ADMIN_PATH;

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        $type = 2;

        $SubdivisionID = $SubdivisionID ? +$SubdivisionID : $nc_core->sub_class->get_by_id($SubClassID, 'Subdivision_ID');
        $CatalogueID = $CatalogueID ? +$CatalogueID : $nc_core->subdivision->get_by_id($SubdivisionID, 'Catalogue_ID');

        $SubEnv = $nc_core->sub_class->get_by_id($SubClassID);
        $ClassEnv = $db->get_row("SELECT * FROM `Class` WHERE `Class_ID` = '" . intval($SubEnv["Class_ID"]) . "'", ARRAY_A);

        $select = "SELECT s.*,
                          c.`Class_Name`,
                          c.`System_Table_ID`
                       FROM `Sub_Class` as s,
                            `Class` as c
                           WHERE `Sub_Class_ID` = " . +$SubClassID . "
                             AND c.`Class_ID` = s.`Class_ID`";

        $Array = $db->get_row($select, ARRAY_A);
        $Array['CustomSettingsTemplate'] = $nc_core->component->get_by_id($Array['Class_Template_ID'] ?: $Array['Class_ID'], 'CustomSettingsTemplate');

        if ($db->is_error) {
            throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        if (empty($Array)) {
            nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBCLASS, 'info');
            return;
        }

        $mobile = $nc_core->catalogue->get_by_id($CatalogueID, 'ncMobile');

        $template_types = $mobile ? array('mobile') : array('useful', 'title', 'mobile', 'multi_edit');
        $classTemplatesArr = $nc_core->component->get_component_templates($Array['Class_ID'], $template_types);

        $edit_template_select = nc_sub_class_template_select('edit', $Array['Class_ID'], $Array['Edit_Class_Template']);
        $admin_template_select = nc_sub_class_template_select('admin', $Array['Class_ID'], $Array['Admin_Class_Template']);

        $classInfo = nc_sub_class_get_classInfo($perm, $Array, $classTemplatesArr, $edit_template_select, $admin_template_select);

        if ($loc->SubdivisionID) {
            $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
        } else {
            $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
        }

        $wsts_msg = CONTROL_CONTENT_SUBCLASS_CLASSNAME;

        require_once($ADMIN_FOLDER."related/format.inc.php");
        $field = new field_relation_subclass();

        $fieldsets = new nc_admin_fieldset_collection();
        $fieldsets->set_prefix(nc_sub_class_get_checked($SubClassID, $Array));
        $fieldsets->set_static_prefix(nc_sub_class_get_style_prefix());
        $fieldsets->set_suffix("
            </div>
            " . $nc_core->token->get_input() . "
            <input type='hidden' name='phase' value='4' />
            <input type='hidden' name='SubClassID' value='$SubClassID' />
            <input type='hidden' name='SubdivisionID' value='{$loc->SubdivisionID}' />
            <input type='hidden' name='CatalogueID' value='{$loc->CatalogueID}' />
            <input type='hidden' name='MirrorClassID' value='' />
            <input type='submit' style='display: none;' />
            ");
        if ($Array['CustomSettingsTemplate']) {
            $a2f = new nc_a2f($Array['CustomSettingsTemplate'], 'CustomSettings');
            $a2f->set_value($Array['CustomSettings']);
            $fieldsets->new_fieldset('CustomSettings', CONTROL_CONTENT_SUBDIVISION_CUSTOM_SETTINGS_TEMPLATE)->add(nc_sub_class_get_CustomSettings($a2f));
        }

        $fieldsets->new_fieldset('records', "")->add(nc_sub_class_get_RecordsPerPage($Array));
        $fieldsets->new_fieldset('min_records', "")->add(nc_sub_class_get_MinRecordsInInfoblock($Array));
        $fieldsets->new_fieldset('max_records', "")->add(nc_sub_class_get_MaxRecordsInInfoblock($Array));

        $fieldsets->new_fieldset('main_info', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MAININFO, false)->add(nc_sub_class_get_main_info($Array, $classInfo, $wsts_msg, $field));
        $fieldsets->new_fieldset('objlist', CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_HOWSHOW, CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_HOWSHOW)->add(nc_sub_class_get_objlist($Array));

        $fieldsets->new_fieldset('access', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ACCESS, CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ACCESS)->add(nc_subdivision_show_access($SubEnv));
        #$fieldsets->new_fieldset('rss', 'RSS')->add(nc_subclass_show_export('rss', $SubdivisionID, $SubClassID));
        #$fieldsets->new_fieldset('xml', 'XML')->add(nc_subclass_show_export('xml', $SubdivisionID, $SubClassID));

        if (nc_module_check_by_keyword('cache')) {
            $fieldsets->new_fieldset('cache', CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE, CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE)->add(nc_subdivision_show_cache($SubEnv));
        }

        if (nc_module_check_by_keyword('comments')) {
            $fieldsets->new_fieldset('comments', CONTROL_CONTENT_SUBCLASS_FUNCS_COMMENTS, CONTROL_CONTENT_SUBCLASS_FUNCS_COMMENTS)->add(nc_subdivision_show_comments($SubEnv));
        }

        echo $fieldsets->to_string();
}

function ActionSubClassCompleted($type) {
    global $nc_core, $db, $ClassID;
    global $loc, $ADMIN_FOLDER, $MODULE_FOLDER, $CustomSettings, $perm;

    $params = array('Priority', 'Checked', 'SubClassName', 'EnglishName', 'Class_Template_ID', 'Class_ID', 'MirrorClassID',
            'DefaultAction', 'isNakedCC', 'AllowTags', 'NL2BR', 'UseCaptcha',
            'RecordsPerPage', 'MinRecordsInInfoblock', 'MaxRecordsInInfoblock', 'SortBy', 'Read_Access_ID', 'Write_Access_ID', 'Cache_Lifetime',
            'Edit_Access_ID', 'Checked_Access_ID', 'Delete_Access_ID', 'Moderation_ID',
            'CacheAccessID', 'CacheLifetime', 'CacheForUser', 'CommentAccessID', 'Edit_Class_Template', 'Admin_Class_Template',
            'CommentsEditRules', 'CommentsDeleteRules', 'SubClassID', 'SubdivisionID', 'CatalogueID', 'SrcMirror', 'Cache_Access_ID', 'Condition', 'ConditionOffset', 'ConditionLimit', 'ncDisallowMoveAndDelete');

    foreach ($params as $v) {
        $$v = $nc_core->input->fetch_get_post($v);
    }

    //транслитерация, если пустой EnglishName
    if (empty($EnglishName)) {
      $EnglishName = nc_transliterate($SubClassName, true);
    }
    // проверка на валидность
    $EnglishName = nc_check_english_name($loc->CatalogueID, (int) $SubClassID, $EnglishName, 2, $SubdivisionID);

    if (nc_module_check_by_keyword("comments")) {
        include_once nc_module_folder('comments') . 'function.inc.php';
    }

    if (+nc_array_value($_POST,'is_mirror')) {
        $Class_ID = $nc_core->sub_class->get_by_id(+$SrcMirror, 'Class_ID');
    }

    $Class_ID = $MirrorClassID ?: $Class_ID;

    if ($Class_Template_ID == $Class_ID) {
        $Class_Template_ID = 0;
    }

    if (!isset($Priority) || $Priority === '') {
        $Priority = $db->get_var("SELECT (`Priority` + 1) FROM `Sub_Class` WHERE `Subdivision_ID` = '" . $loc->SubdivisionID . "' ORDER BY `Priority` DESC LIMIT 1");
    }


    if ($type == 1) {
        if (nc_module_check_by_keyword("cache")) {
            $cache_insert_fields = "`Cache_Access_ID`, `Cache_Lifetime`, `CacheForUser`,";
            $cache_insert_values = "'" . $Cache_Access_ID . "', '" . $Cache_Lifetime . "', '" . $CacheForUser . "',";
        } else {
            $cache_insert_fields = "";
            $cache_insert_values = "";
        }
        if (!isset($SubscribeAccessID)) {
            $SubscribeAccessID = null;
        }
        $insert = "INSERT INTO `Sub_Class` (" . $cache_insert_fields . "`Subdivision_ID`, `Catalogue_ID`, `Class_ID`, `Sub_Class_Name`, `Read_Access_ID`, `Write_Access_ID`, `Edit_Access_ID`, `Checked_Access_ID`, `Delete_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `Checked`, `Priority`, `EnglishName`, `DaysToHold`, `AllowTags`, `NL2BR`, `RecordsPerPage`, `MinRecordsInInfoblock`, `MaxRecordsInInfoblock`, `SortBy`, `Created`, `DefaultAction`, `UseCaptcha`, `CustomSettings`, `Class_Template_ID`, `isNaked`, `SrcMirror`, `Condition`, `ConditionOffset`, `ConditionLimit`, `ncDisallowMoveAndDelete`)";
        $insert.= " VALUES (" . $cache_insert_values . "'" . $loc->SubdivisionID . "', '" . $loc->CatalogueID . "', '" . $Class_ID . "', '" . $db->escape($SubClassName) . "', '" . $Read_Access_ID . "', '" . $Write_Access_ID . "', '" . $Edit_Access_ID . "', '" . $Checked_Access_ID . "','" . $Delete_Access_ID . "','" . $SubscribeAccessID . "', '" . $Moderation_ID . "', '" . $Checked . "', '" . $Priority . "', '" . $EnglishName . "', ";
        $insert.= empty($DaysToHold) ? "NULL, " : "'".$DaysToHold."', ";
        $insert.= "'".$AllowTags."', ";
        $insert.= "'".$NL2BR."', ";
        $insert.= ($RecordsPerPage == "" ? "NULL" : "'" . $RecordsPerPage . "'") . ", ";
        $insert.= ($MinRecordsInInfoblock == "" ? "NULL" : "'".(int)$MinRecordsInInfoblock."'") . ", ";
        $insert.= ($MaxRecordsInInfoblock == "" ? "NULL" : "'".(int)$MaxRecordsInInfoblock."'") . ", ";
        $insert.= "'$SortBy','".date("Y-m-d H:i:s")."','".$DefaultAction."', '".$UseCaptcha."', '".addcslashes($CustomSettings, "'")."', '".$Class_Template_ID."', '".$isNakedCC."', '".$SrcMirror."', '" . $db->prepare($Condition) . "', '" . (int)$ConditionOffset . "', " . (strlen($ConditionLimit) ? (int)$ConditionLimit : 'NULL') . ", '" . (int)$ncDisallowMoveAndDelete . "')";

        // execute core action
        $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_CREATED, $loc->CatalogueID, $loc->SubdivisionID, 0);

        $db->query($insert);
        // inserted ID
        $insertedSubClassID = $db->insert_id;

        if ($Condition) {
            $infoblock_condition_translator = new nc_condition_infoblock_translator($Condition, $insertedSubClassID);
            $infoblock_condition_query = $db->prepare($infoblock_condition_translator->get_sql_condition());
            $db->query("UPDATE `Sub_Class` SET `ConditionQuery` = '$infoblock_condition_query' WHERE `Sub_Class_ID` = '$insertedSubClassID'");
        }

        // execute core action
        $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_CREATED, $loc->CatalogueID, $loc->SubdivisionID, $insertedSubClassID);

        if (nc_module_check_by_keyword("comments")) {
            if ($CommentAccessID > 0) {
                // add comment relation
                $CommentRelationID = nc_comments::addRule($db, array($loc->CatalogueID, $loc->SubdivisionID, $insertedSubClassID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                // update inserted data
                $db->query("UPDATE `Sub_Class` SET `Comment_Rule_ID` = '" . (int) $CommentRelationID . "' WHERE `Sub_Class_ID` = '" . (int) $insertedSubClassID . "'");
            }
        }

        $nc_core->sub_class->create_mock_objects($insertedSubClassID);

        nc_tpl_mixin_cache::generate_block_files($insertedSubClassID, false);

        return $insertedSubClassID;
    }

    if ($type == 2) {
        $cur_checked = $db->get_var("SELECT `Checked` FROM `Sub_Class` WHERE `Sub_Class_ID` = '" . $SubClassID . "'");
        $cur_nc_disallow_move_and_delete = $db->get_var("SELECT `ncDisallowMoveAndDelete` FROM `Sub_Class` WHERE `Sub_Class_ID` = '" . $SubClassID . "'");
        if (nc_module_check_by_keyword("comments")) {
            $CommentData = nc_comments::getRuleData($db, array($loc->CatalogueID, $loc->SubdivisionID, $SubClassID));
            $CommentRelationID = $CommentData['ID'];

            switch (true) {
                case $CommentAccessID > 0 && $CommentRelationID:
                    // update comment rules
                    nc_comments::updateRule($db, array($loc->CatalogueID, $loc->SubdivisionID, $SubClassID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                    break;
                case $CommentAccessID > 0 && !$CommentRelationID:
                    // add comment relation
                    $CommentRelationID = nc_comments::addRule($db, array($loc->CatalogueID, $loc->SubdivisionID, $SubClassID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                    break;
                case $CommentAccessID <= 0 && $CommentRelationID:
                    // delete comment rules
                    nc_comments::dropRuleSubClass($db, $SubClassID);
                    $CommentRelationID = 0;
                    break;
            }
        }

        if ($cur_nc_disallow_move_and_delete && !$Checked && $cur_checked) {
            $Checked = 1;
        }

        $update = "UPDATE `Sub_Class` SET ";
        $update.= "`Sub_Class_Name` = '" . $db->escape($SubClassName) . "',";
        $update.= "`Read_Access_ID` = '" . $Read_Access_ID . "',";
        $update.= "`Write_Access_ID` = '" . $Write_Access_ID . "',";
        $update.= "`Edit_Access_ID` = '" . $Edit_Access_ID . "',";
        $update.= "`Checked_Access_ID` = '" . $Checked_Access_ID . "',";
        $update.= "`Delete_Access_ID` = '" . $Delete_Access_ID . "',";
        $update.= "`Subscribe_Access_ID` = '" . (!empty($SubscribeAccessID) ? $SubscribeAccessID : 0) . "',";
        if (nc_module_check_by_keyword("cache")) {
            $update.= "`Cache_Access_ID` = '" . $Cache_Access_ID . "',";
            $update.= "`Cache_Lifetime` = '" . $Cache_Lifetime . "',";
            $update.= "`CacheForUser` = '" . $CacheForUser . "',";
        }
        if (nc_module_check_by_keyword("comments")) {
            $update.= "`Comment_Rule_ID` = '" . $CommentRelationID . "',";
        }
        $update.= "`Moderation_ID` = '" . $Moderation_ID . "',";
        $update.= "`Checked` = '" . $Checked . "',";
        //$update.= "`Priority` = '" . $Priority . "',";
        $update.= "`EnglishName` = '" . $EnglishName . "',";
        $update.= "`DefaultAction` = '" . $DefaultAction . "',";
        $update.= empty($DaysToHold) ? "`DaysToHold` = NULL," : "`DaysToHold` = '" . $DaysToHold . "',";
        $update.= "`AllowTags` = '" . $AllowTags . "',";
        $update.= "`NL2BR` = '" . $NL2BR . "',";
        $update.= $RecordsPerPage == "" ? "`RecordsPerPage` = NULL," : "`RecordsPerPage` = '" . $RecordsPerPage . "',";
        $update.= $MinRecordsInInfoblock == "" ? "`MinRecordsInInfoblock` = NULL," : "`MinRecordsInInfoblock` = '" . (int)$MinRecordsInInfoblock . "',";
        $update.= $MaxRecordsInInfoblock == "" ? "`MaxRecordsInInfoblock` = NULL," : "`MaxRecordsInInfoblock` = '" . (int)$MaxRecordsInInfoblock . "',";
        $update.= "`SortBy` = '" . $SortBy . "',";
        $update.= "`UseCaptcha` = '" . $UseCaptcha . "', ";
        $update.= "`CustomSettings` = '" . $db->escape(addcslashes($CustomSettings, "'")) . "', ";
        $update.= "`Class_Template_ID` = '" . $Class_Template_ID . "', ";
        $update.= "`Edit_Class_Template` = '" . $Edit_Class_Template . "', ";
        $update.= "`Admin_Class_Template` = '" . $Admin_Class_Template . "', ";
        $update.= "`isNaked` = '" . $isNakedCC . "', ";
        $update.= "`SrcMirror` = '" . $SrcMirror . "', ";
        $update.= "`Condition` = '" . $db->prepare($Condition) . "', ";
        $update.= "`ConditionOffset` = '" . (int)$ConditionOffset . "', ";
        $update.= "`ConditionLimit` = " . (strlen($ConditionLimit) ? (int)$ConditionLimit : 'NULL') . ", ";
        if ($perm->isSupervisor()) {
            $update.= "`ncDisallowMoveAndDelete` = '" . (int)$ncDisallowMoveAndDelete . "', ";
        }

        $post_data = $nc_core->input->fetch_get_post('data');

        foreach (array('Index', 'IndexItem') as $scope) {
            if (isset($post_data[$scope . '_Mixin_Settings'])) { // e. g. Index_Mixin_Settings
                $update .= "`{$scope}_Mixin_Preset_ID` = '" . (int)$post_data[$scope . '_Mixin_Preset_ID'] . "', ";
                $update .= "`{$scope}_Mixin_Settings` = '" . $db->prepare($post_data[$scope . '_Mixin_Settings']) . "', ";
                $update .= "`{$scope}_Mixin_BreakpointType` = '" . $db->escape($post_data[$scope . '_Mixin_BreakpointType']) . "', ";
            }
        }

        if ($Condition) {
            $infoblock_condition_translator = new nc_condition_infoblock_translator($Condition, $SubClassID);
            $update .= "`ConditionQuery` = '" . $db->prepare($infoblock_condition_translator->get_sql_condition()) . "', ";
        } else {
            $update .= "`ConditionQuery` = '', ";
        }

        $update.= "`AllowRSS` = '" . intval($nc_core->input->fetch_get_post('AllowRSS' . $SubClassID)) . "',";
        $update.= "`AllowXML` = '" . intval($nc_core->input->fetch_get_post('AllowXML' . $SubClassID)) . "'";
        $update.= " WHERE `Sub_Class_ID` = '" . $SubClassID . "'";

        $subclass_data = $nc_core->sub_class->get_by_id($SubClassID);

        $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $subclass_data['Catalogue_ID'], $subclass_data['Subdivision_ID'], $SubClassID);
        if ($cur_checked != $Checked) {
            $nc_core->event->execute($Checked ? nc_Event::BEFORE_INFOBLOCK_ENABLED : nc_Event::BEFORE_INFOBLOCK_DISABLED, $subclass_data['Catalogue_ID'], $subclass_data['Subdivision_ID'], $SubClassID);
        }

        $db->query($update);

        if ($db->is_error) {
            throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        // execute core action
        $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $subclass_data['Catalogue_ID'], $subclass_data['Subdivision_ID'], $SubClassID);

        // произошло включение / выключение
        if ($cur_checked != $Checked) {
            $nc_core->event->execute($Checked ? nc_Event::AFTER_INFOBLOCK_ENABLED : nc_Event::AFTER_INFOBLOCK_DISABLED, $subclass_data['Catalogue_ID'], $subclass_data['Subdivision_ID'], $SubClassID);
        }

        nc_tpl_mixin_cache::generate_block_files($SubClassID, false);

        return $db->rows_affected;
    }
}

###############################################################################

function UpdateSubClassPriority() {
    $nc_core = nc_Core::get_object();

    foreach($_POST as $key => $val) {
        // this cc must be deleted
        if (strpos($key, 'Deleted') === 0) {
            continue;
        }

        if (strpos($key, 'Priority') === 0) {
            $subclass_id = (int)substr($key, 8);
            $val = (int)$val;

            $data = $nc_core->sub_class->get_by_id($subclass_id);

            if ($data['Priority'] != $val && $nc_core->sub_class->disallow_move_and_delete($subclass_id) === true) {
                nc_print_status(sprintf(CONTROL_CONTENT_SUBDIVISION_DISALLOW_ERROR_CUT_SUB_CLASS, " ".$nc_core->sub_class->get_by_id($subclass_id, 'Sub_Class_Name')), 'error');
                continue;
            }

            $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $data['Catalogue_ID'], $data['Subdivision_ID'], $subclass_id);

            $nc_core->db->query("UPDATE `Sub_Class` SET `Priority` = '{$val}', `LastUpdated` = `LastUpdated` WHERE `Sub_Class_ID` = '{$subclass_id}'");
            if ($nc_core->db->last_error) {
                return false;
            }

            // execute core action
            $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $data['Catalogue_ID'], $data['Subdivision_ID'], $subclass_id);
        }
    }

    return true;
}

function nc_sub_class_get_main_info($Array, $classInfo, $wsts_msg, $field) {
    $nc_core = nc_Core::get_object();

    return "
        <div id='main_info'>
            <input type='hidden' name='SrcMirror' value='{$Array['SrcMirror']}' />
                <div>
                    <div>
                        $classInfo
                    </div>
                </div><br />

                <div>
                    <div>
                        $wsts_msg:
                    </div>

                    <div>
                        ". nc_admin_input_simple('SubClassName', $Array["Sub_Class_Name"], 50, '', "maxlength='255'") . "
                    </div>
                </div><br />

                <div>
                    <div>
                        " . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD.":
                    </div>

                    <div>
                        " . nc_admin_input_simple('EnglishName', $Array["EnglishName"], 50, '', "maxlength='255' data-type='transliterate' data-from='SubClassName' data-is-url='yes' ") ."
                    </div>
                    <script>nc_infoblock_on_component_change_init();</script>
                </div><br />

                <div>
                    <div>
                        " . CONTROL_CONTENT_SUBCLASS_DEFAULTACTION . ":
                    </div>

                    <div>
                        <select name='DefaultAction'>
                            <option " . ($Array["DefaultAction"] == "index" ? "selected " : "") . " value='index'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_VIEW . " </option>
                            <option " . ($Array["DefaultAction"] == "add" ? "selected " : "") . " value='add'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADDING . " </option>
                            <option " . ($Array["DefaultAction"] == "search" ? "selected " : "") ." value='search'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SEARCHING . " </option>
                            " . ($nc_core->modules->get_by_keyword('subscriber', 0) ? "<option " . ($Array["DefaultAction"] == "subscribe" ? "selected " : "") . " value='subscribe'>". CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SUBSCRIBING ." </option>" : "") . "
                        </select>
                    </div>
                </div>
                ".(false && $Array["SrcMirror"] ? "<br />
                <div>
                    <div>
                        " . CONTROL_CONTENT_SUBCLASS_SRCMIRROR . ":
                    </div>

                    <div>
                        <span id='cs_SrcMirror_caption' style='font-weight:bold;'>
                            " . listQuery($field->get_object_query($Array["SrcMirror"]), $field->get_full_admin_template()) . "
                        </span>
                            <input id='cs_SrcMirror_value' name='SrcMirror' type='hidden' value='{$Array['SrcMirror']}' />&nbsp;&nbsp;
                            <a href='#' onclick=\"window.open('" . $nc_core->ADMIN_PATH . "related/select_subclass.php?cs_type=rel_cc&amp;cs_field_name=SrcMirror', 'nc_popup_SrcMirror', 'width=800,height=500,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes'); return false;\">
                                " . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_EDIT . "
                            </a>&nbsp;&nbsp;

                            <a href='#' onclick=\"document.querySelector('input[name=MirrorClassID]').value='';document.getElementById('cs_SrcMirror_value').value='';document.getElementById('cs_SrcMirror_caption').innerHTML = '" . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_NONE . "';return false;\">
                                " . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_DELETE . "
                            </a>
                    </div>
                </div>" : "")."
            </div>";
}

function nc_sub_class_get_objlist($Array) {
    $checked_html = " checked='checked'";
    $td_checked   = " nc-bg-lighten";
    $html = "<div id='loadClassTemplates'></div><br />";
    if ($Array['CustomSettingsTemplate']) {
        $a2f = new nc_a2f($Array['CustomSettingsTemplate'], 'CustomSettings');
        $a2f->set_value(nc_array_value($Array, 'CustomSettings'));
        $html .= nc_sub_class_get_CustomSettings($a2f);
    } else {
        $html .= "<div id='loadClassCustomSettings'></div>";
    }
    $html .= "<script>nc_infoblock_on_component_change_init();</script>";
    $html .= nc_sub_class_get_RecordsPerPage($Array);
    $html .= nc_sub_class_get_MinRecordsInInfoblock($Array);
    $html .= nc_sub_class_get_MaxRecordsInInfoblock($Array);
    $html .= "
        <table class='nc-table' id='objlist_table'>
            <tr>
                <th style='width:180px'></th>
                <th class='nc-text-center' style='min-width:80px'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT . "</th>
                <th class='nc-text-center' style='min-width:80px'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES . "</th>
                <th class='nc-text-center' style='min-width:80px'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO . "</th>
            </tr>

            <tr>
                <td class='col_1 nc-text-right'>
                    " . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML . "
                </td>
                <td class='col_2".($Array['AllowTags'] == -1 ? $td_checked : '')."'>
                    <label class='nc--blocked nc-text-center'><input type='radio' name='AllowTags' value='-1'".($Array['AllowTags'] == -1 ? $checked_html : '')." /></label>
                </td>
                <td class='col_3".($Array['AllowTags'] == 1 ? $td_checked : '')."'>
                    <label class='nc--blocked nc-text-center'><input type='radio' name='AllowTags' value='1'".($Array['AllowTags'] == 1 ? $checked_html : '')." /></label>
                </td>
                <td class='col_4".($Array['AllowTags'] == 0 ? $td_checked : '')."'>
                    <label class='nc--blocked nc-text-center'><input type='radio' name='AllowTags' value='0'".($Array['AllowTags'] == 0 ? $checked_html : '')." /></label>
                </td>
            </tr>
            <tr>
                <td class='col_1 nc-text-right'>
                    " . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR . "
                </td>
                <td class='col_2".($Array['NL2BR'] == -1 ? $td_checked : '')."'>
                    <label class='nc--blocked nc-text-center'><input type='radio' name='NL2BR' value='-1'".($Array['NL2BR'] == -1 ? $checked_html : '')." /></label>
                </td>
                <td class='col_3".($Array['NL2BR'] == 1 ? $td_checked : '')."'>
                    <label class='nc--blocked nc-text-center'><input type='radio' name='NL2BR' value='1'".($Array['NL2BR'] == 1 ? $checked_html : '')." /></label>
                </td>
                <td class='col_4".($Array['NL2BR'] == 0 ? $td_checked : '')."'>
                    <label class='nc--blocked nc-text-center'><input type='radio' name='NL2BR' value='0'".($Array['NL2BR'] == 0 ? $checked_html : '')." /></label>
                </td>
            </tr>
            <tr>
                <td class='col_1 nc-text-right'>
                    " . CONTROL_CLASS_USE_CAPTCHA . "
                </td>
                <td class='col_2".($Array['UseCaptcha'] == -1 ? $td_checked : '')."'>
                    <label class='nc--blocked nc-text-center'><input type='radio' name='UseCaptcha' value='-1'".($Array['UseCaptcha'] == -1 ? $checked_html : '')." /></label>
                </td>
                <td class='col_3".($Array['UseCaptcha'] == 1 ? $td_checked : '')."'>
                    <label class='nc--blocked nc-text-center'><input type='radio' name='UseCaptcha' value='1'".($Array['UseCaptcha'] == 1 ? $checked_html : '')." /></label>
                </td>
                <td class='col_4".($Array['UseCaptcha'] == 0 ? $td_checked : '')."'>
                    <label class='nc--blocked nc-text-center'><input type='radio' name='UseCaptcha' value='0'".($Array['UseCaptcha'] == 0 ? $checked_html : '')." /></label>
                </td>
            </tr>
        </table>
        <br>
        <div>
            " . CONTROL_CLASS_CLASS_OBJECTSLIST_SORT . ":
        </div>

        <div>
            " . nc_admin_input_simple('SortBy', $Array['SortBy'], 50, '', "maxlength='255'") . "
        </div>";
    $html .= "<div><br />
        ".nc_admin_checkbox_simple('isNakedCC', 1, '', $Array["isNaked"])."
	<font color='gray'>".CONTROL_CONTENT_SUBCLASS_ISNAKED."</font>
	</div>";
    if (nc_array_value($Array,'Class_ID')) {
	$edit_template_select = nc_sub_class_template_select('edit', $Array['Class_ID'], $Array['Edit_Class_Template']);
	$admin_template_select = nc_sub_class_template_select('admin', $Array['Class_ID'], $Array['Admin_Class_Template']);
	$html .= ($edit_template_select ? "<div>$edit_template_select</div><br><div>$admin_template_select</div>" : '');
    }

    return $html;
}

function nc_sub_class_get_RecordsPerPage($Array) {
    return "<div>
            <div>" . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW_NUM . "</div>
            <div>" . nc_admin_input_simple('RecordsPerPage', $Array['RecordsPerPage'], 5, '', "maxlength='32'") . "</div>
        </div>
        <br>";
}

function nc_sub_class_get_MinRecordsInInfoblock($Array) {
    return "<div>
            <div>" . CONTROL_CLASS_CLASS_MIN_RECORDS . "</div>
            <div>" . nc_admin_input_simple('MinRecordsInInfoblock', $Array['MinRecordsInInfoblock'], 5, '', "maxlength='11'") . "</div>
        </div>
        <br>";
}

function nc_sub_class_get_MaxRecordsInInfoblock($Array) {
    return "<div>
            <div>" . CONTROL_CLASS_CLASS_MAX_RECORDS . "</div>
            <div>" . nc_admin_input_simple('MaxRecordsInInfoblock', $Array['MaxRecordsInInfoblock'], 5, '', "maxlength='11'") . "</div>
        </div>
        <br>";
}

function nc_sub_class_get_query_offset($cc_settings) {
    return "<div>
            <div>" . CONTROL_CONTENT_SUBCLASS_CONDITION_OFFSET . "</div>
            <div>" . nc_admin_input_simple('ConditionOffset', $cc_settings['ConditionOffset'], 5, '', "maxlength='11'") . "</div>
        </div>";
}

function nc_sub_class_get_query_limit($cc_settings) {
    return "<div>
            <div>" . CONTROL_CONTENT_SUBCLASS_CONDITION_LIMIT . "</div>
            <div>" . nc_admin_input_simple('ConditionLimit', $cc_settings['ConditionLimit'], 5, '', "maxlength='11'") . "</div>
        </div>";
}

function nc_sub_class_get_CustomSettings(nc_a2f $a2f) {
    return $a2f->render(
                    "<div id='loadClassCustomSettings'>",
                    array(
                        'checkbox' => '<div class="nc-field"><label>%VALUE %CAPTION</label></div>',
                        'default' => '<div class="nc-field"><span class="nc-field-caption">%CAPTION:</span>%VALUE</div>',
                    ),
                    "</div><script>nc_infoblock_on_component_change_init();</script>",
                    false
            );
}

function nc_sub_class_get_style_prefix() {
    return "
        <style>
            div.nc_table_objlist > div {
                border-bottom: 1px #cccccc solid;
                display: table;
            }

            div.nc_table_objlist > div > div {
                display: inline-block;
                padding-top: 9px;
                padding-bottom: 11px;
            }

            div.nc_table_objlist div.col_1 {
                width: 261px;
            }

            div.nc_table_objlist div.col_2 {
                width: 134px;
                text-align: center;
            }

            div.nc_table_objlist div.col_3 {
                width: 75px;
                text-align: center;
            }

            div.nc_table_objlist div.col_4 {
                text-align: center;
                width: 81px;
            }


            div.nc_table_objlist > div.row_1 {
                padding-top: 3px;
                padding-bottom: 2px;
            }

            div.nc_table_objlist div.checked {
                background-color: #eeeeee;
            }
        </style>";
}

function nc_sub_class_get_checked($SubClassID, $Array, $display = false) {
    return "<div class='nc_admin_settings_info_checked'>
                    " . nc_admin_checkbox_simple('Checked', 1, CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON, $Array['Checked'], 'turnon') . "
                </div><br />";
}

function nc_sub_class_get_created($Array) {
    if (empty($Array['Created']) && empty($Array['LastUpdated'])) {
        return '';
    }

    return "<div class='nc_admin_settings_info'>
                <div class='nc_admin_settings_info_actions'>" .
                    ($Array['Created'] ? "<div><span>" . CLASS_TAB_CUSTOM_ADD . ":</span> $Array[Created]</div>" : '') .
                    ($Array['LastUpdated'] ? "<div><span>" . CLASS_TAB_CUSTOM_EDIT . ":</span> $Array[LastUpdated]</div>" : '') .
            "</div></div>";
}

function nc_sub_class_nc_condition($catalogue_id, $sub_class_id, $cc_settings) {
    $result = nc_condition_infoblock_admin_helpers::get_condition_editor_js();
    $result .= "<input type='hidden' name='Condition' value='" . htmlspecialchars($cc_settings['Condition'], ENT_QUOTES) . "'>";

    $condition_json = $cc_settings['Condition'] ?: '{}';
    $condition_groups = nc_array_json(array('GROUP_OBJECTS'));

    $result .= "<div id='nc_condition_editor_infoblock_query'></div>
<script>
    (function() {
        var container = \$nc('#nc_condition_editor_infoblock_query'),
            fieldset = container.closest('.nc_admin_fieldset'),
            spoilerHeader = fieldset.find('.nc_admin_fieldset_head');
        
        spoilerHeader.on('click', function() {
            if (container.data('initialized')) {
                return;
            }
    
            var condition_editor = new nc_condition_editor_infoblock_query({
                container: container,
                strings: " . nc_condition_admin_helpers::get_strings() . ",
                input_name: 'Condition',
                conditions: $condition_json,
                url_params: {
                    site_id: $catalogue_id,
                    infoblock_id: $sub_class_id
                },
                groups_to_show: $condition_groups
            });

            container.closest('.ncf_value').removeClass('ncf_value');
            container.closest('form').get(0).onsubmit = function() {
                return condition_editor.onFormSubmit();
            };
            container.data('initialized', true);
        });
    })();
</script>";

    $result .= '<br>' . nc_sub_class_get_query_offset($cc_settings);
    $result .= nc_sub_class_get_query_limit($cc_settings);

    return $result;
}

function nc_sub_class_get_mixin_editor($scope) {
    $nc_core = nc_Core::get_object();
    $controller_path  = $nc_core->ROOT_FOLDER . 'admin';
    $controller = $nc_core->ui->controller('nc_infoblock_controller', $controller_path);
    return $controller->execute('show_mixin_editor', array($scope));
}

function nc_sub_class_get_classInfo($perm, $Array, $classTemplatesArr, $edit_template_select, $admin_template_select) {
    $nc_core = nc_Core::get_object();
    $classInfo = '';

    if ($perm->isSupervisor()) {
        $class_href = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "admin/#";
        if (!$Array['SrcMirror']) {
            $fspref = (nc_get_file_mode('Class', $Array['Class_ID']) ? '_fs' : '');
            $class_href .= !$Array['System_Table_ID'] ? "dataclass".$fspref.".edit(" . $Array['Class_ID'] . ")" : "systemclass".$fspref.".edit(" . $Array['System_Table_ID'] . ")";
        } else {
            $class_href .= "object.list({$Array['SrcMirror']})";
        }
        $classInfo = "
                <div>
                    " . (!$Array['System_Table_ID'] ? ($Array['SrcMirror'] ? CONTROL_CONTENT_SUBCLASS_MIRROR : CONTROL_CLASS_CLASS) : CONTROL_CLASS_SYSTEM_TABLE) . ":
                    <b>
                        <a href='$class_href' target='_blank'>
                            " . $Array["Class_Name"] . "
                        </a>
                    </b>
                </div><br />";
    }

    if (!empty($classTemplatesArr)) {
        $classInfo .= "
                <div>
                    <div>
                        " . CONTROL_CLASS_CLASS_TEMPLATE_DEFAULT . ":
                    </div>

                    <div>
                        <select name='Class_Template_ID' id='Class_Template_ID'>
                            <option value='$Array[Class_ID]'" .
                            " data-can-use-mixins='" . (int)$nc_core->component->can_add_block_list_markup($Array['Class_ID']) . "'" .
                            ">" . CONTROL_CLASS_CLASS_DONT_USE_TEMPLATE . "</option>";

        foreach ($classTemplatesArr as $classTemplate) {
            $classInfo .= "<option value='{$classTemplate['Class_ID']}'" .
                ($Array['Class_Template_ID'] == $classTemplate['Class_ID'] ? " selected" : "") .
                " data-can-use-mixins='" . (int)$nc_core->component->can_add_block_list_markup($classTemplate['Class_ID']) . "'" .
                ">{$classTemplate['Class_Name']}</option>";
        }

        $classInfo .= "</select>
                        <button type='button' onclick=\"window.open('{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}admin/#classtemplate".(nc_get_file_mode('Class', $Array['Class_ID']) ? '_fs' : '').".edit(' + document.getElementById('Class_Template_ID').value + ')', 1)\" id='classtemplateEditLink'" . (!$Array['Class_Template_ID'] ? " disabled" : "") . ">" . CONTROL_CLASS_CLASS_TEMPLATE_BUTTON_EDIT . "</button></a>
                    </div><br />                    
                </div>";

        $classInfo .=
            '<script>
            $nc("#Class_Template_ID").on("change", function() {
                var selected_option = $nc(this).find("option:selected"),
                    selected_value = selected_option.val();
                if (selected_value) {
                    loadClassCustomSettings(selected_value, ' . (int)$Array['Sub_Class_ID'] . '); 
                    $nc("#classtemplateEditLink").prop("disabled", selected_value == ' . (int)$Array['Class_ID'] . ');
                }
                $nc(".nc-mixins-editor").closest(".nc_admin_fieldset").toggle(!!selected_option.data("canUseMixins"));
            });
            </script>';

    }

    return $classInfo;
}

/**
 * Возвращает <select> для выбора шаблона в режиме редактирования или администрирования
 *
 * @param $template_type 'edit' или 'admin'
 * @param int $component_id ID компонента
 * @param int $current_component_template_id шаблон, выбранный сейчас
 * @return string
 */
function nc_sub_class_template_select($template_type, $component_id, $current_component_template_id) {
    $template_info = nc_sub_class_get_template_info($component_id);
    $component_template_names = $template_info['template_names'];

    if (!$component_template_names) {
        return '';
    }

    if ($template_type == 'edit') {
        // Режим редактирования
        $field_caption = CONTROL_CLASS_CLASS_TEMPLATE_EDIT_MODE;
        $select_name = "Edit_Class_Template";
        $default_template_id = $template_info['edit_template'];
    }
    else if ($template_type == 'admin') {
        // Режим администрирования
        $field_caption = CONTROL_CLASS_CLASS_TEMPLATE_ADMIN_MODE;
        $select_name = "Admin_Class_Template";
        $default_template_id = $template_info['admin_template'];

        // Если нет шаблона для режима администрирования, но есть шаблон для режима
        // редактирования, будет использован шаблон для режима редактирования
        if (!$default_template_id) {
            $default_template_id = $template_info['edit_template'];
        }
    }
    else {
        return __FUNCTION__ . "(): INVALID \$template_type '$template_type'";
    }

    $result = "<div>$field_caption:</div>";

    $result .= "\n<div>\n";
    $result .= "    <select id='$select_name' name='$select_name'>\n";
    if (!$default_template_id) {
        $result .= "        <option value='0'>" . CONTROL_CLASS_CLASS_TEMPLATE_EDIT_MODE_DONT_USE . "</option>\n";
    }

    foreach ($component_template_names as $template_id => $template_name) {
        $option_value = $template_id;
        if ($template_id == $default_template_id) { $option_value = 0; }
        $selected = ($current_component_template_id == $option_value ? ' selected' : '');
        $result .= "        <option$selected value='$option_value' data-id='$template_id'>$template_name</option>\n";
    }

    $result .= "    </select> <button id='nc_button_$select_name' type='button'>" .
                CONTROL_CLASS_CLASS_TEMPLATE_BUTTON_EDIT . " </button><br />\n";

    $result .= "</div>\n";

    // Обработчик нажатия на кнопку «Редактировать»
    $nc_core = nc_core::get_object();
    $template_edit_link = "{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}admin/#classtemplate" .
                          (nc_get_file_mode('Class', $component_id) ? '_fs' : '') .
                          ".edit";
    $result .=
        "<script>
            \$nc('#nc_button_$select_name').click(function() {
                var id = \$nc('#$select_name').find('option:selected').data('id');
                window.open('$template_edit_link(' +  id + ')', 'edit_component_template_' + id);
            });
        </script>";

    if (!$default_template_id) {
        $nc = '$nc';
        $result .=
            "<script type='text/javascript'>
                \$nc(function() {
                    var template_select = \$nc('#$select_name'),
                        edit_button = \$nc('#nc_button_$select_name');

                    function update_button_state() {
                        edit_button.prop('disabled', template_select.val() == '0');
                    }

                    template_select.change(update_button_state);
                    update_button_state();
                });
            </script>";
    }

    return $result;
}

/**
 * Вспомогательная функция для nc_infoblock_template_select
 * @param $component_id
 * @return array
 */
function nc_sub_class_get_template_info($component_id) {
    static $info;

    if (!$info) {
        $info = array(
            "template_names" => array(),
            "edit_template" => null,
            "admin_template" => null,
        );

        $component_id = (int)$component_id;
        $result = nc_db()->get_results("SELECT `Class_ID`,
                                               `Class_Name`,
                                               `Type`
                                          FROM `Class`
                                         WHERE `Class_ID` = $component_id
                                            OR `ClassTemplate` = $component_id
                                         ORDER BY `Priority`, `Class_ID`",
                                    ARRAY_A);
        foreach ($result as $row) {
            $id = $row['Class_ID'];
            $info['template_names'][$id] = $row['Class_Name'];
            switch ($row['Type']) {
                case 'admin_mode':
                    $info['edit_template'] = $id;
                    break;
                case 'inside_admin':
                    $info['admin_template'] = $id;
                    break;
            }
        }
    }

    return $info;
}
