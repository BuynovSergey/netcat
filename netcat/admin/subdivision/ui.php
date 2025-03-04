<?php

/* $Id */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * настройки раздела
 */
class ui_config_subdivision_settings extends ui_config_subdivision_generic {

    /**
     * @param integer
     */
    function __construct($sub_id, $toolbar_action = 'edit') {

        $this->init($sub_id);

        if (!$this->sub->subdivision_id) {
            trigger_error("Wrong parameters for ui_config_subdivision_settings?", E_USER_ERROR);
        }


        // кнопки на тулбаре
        $this->toolbar = array();
        foreach (array('design', 'edit', 'seo', 'system', 'fields') as $v) {
            $this->toolbar[] = array('id' => $v,
                    'caption' => constant("SUBDIVISION_TAB_INFO_TOOLBAR_EDIT_".strtoupper($v)),
                    'location' => "subdivision.".$v."(".$sub_id.")",
                    'group' => "grp1"
            );
        }


        $this->activeToolbarButtons[] = $toolbar_action;
        $this->locationHash = "subdivision.$toolbar_action($sub_id)";


        $this->activeTab = 'settings';

        $this->actionButtons[] = array("id" => "submit",
                "caption" => CONTROL_CONTENT_SUBDIVISION_FUNCS_SAVE,
                "action" => "mainView.submitIframeForm()",
                "align" => "right"
        );
    }

    /**
     * обновление данных узла в дереве
     * @param integer
     * @param string
     * @param boolean
     */
    function updateTreeSubdivisionNode($node_id="", $node_name="", $checked=null, $label_color=null) {

        if (!$node_id) {
            $node_id = $this->sub->subdivision_id;
        }
        if (!$node_name) {
            $node_name = $this->sub->subdivision_name;
        }
        if ($checked == null) {
            $checked = $this->sub->subdivision_checked;
        }
        if ($label_color == null) {
            $label_color = $this->sub->label_color;
        }

        $subclasses = array();

        foreach ((array) $this->sub->get_moderated_subclasses() as $sc) {
            $subclasses[] = array("classId" => $sc["Class_ID"], "subclassId" => $sc["Sub_Class_ID"]);
        }

        $this->treeChanges['updateNode'][] = array("nodeId" => "sub-$node_id",
                "name"       => "$node_id. $node_name",
                "image"      => "icon_folder",
                "toggleIcon" => $checked,
                "sprite"     => "folder" . ($checked ? "" : " nc--dark") . ($label_color ? " nc--badge-{$label_color}" : ''),
                "checked"    => $checked,
                "subclasses" => $subclasses);
    }

}

// of "ui_config_subdivision_settings"

/**
 * добавление раздела имеет интерфейс, отличный от настроек раздела,
 * нет смысла объединять их
 */
class ui_config_subdivision_add extends ui_config {

    function __construct($parent_sub_id, $catalogue_id=0) {

        $location = "subdivision.add(".($parent_sub_id ? $parent_sub_id : "0,$catalogue_id").")";
        $this->locationHash = $location;
        $this->headerText = CONTROL_CONTENT_SUBDIVISION_INDEX_ADDSECTION;
        $this->tabs = array(
                array('id' => 'subdivisionAdd',
                        'caption' => CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION,
                        'location' => $location)
        );
        $this->activeTab = "subdivisionAdd";
        $this->treeMode = 'sitemap';
        $this->treeSelectedNode = ($parent_sub_id ? "sub-{$parent_sub_id}" : "site-{$catalogue_id}");

        $this->actionButtons[] = array("id" => "submit",
                "caption" => CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION,
                "action" => "mainView.submitIframeForm()"
        );
    }

}

class ui_config_subdivision_delete extends ui_config {

    function __construct($sub_id) {

        $this->locationHash = "subdivision.delete($sub_id)";
        $this->headerText = CONTROL_CONTENT_SUBDIVISION_INDEX_DELETECONFIRMATION;
        $this->tabs = array(
                array('id' => 'subdivisionDelete',
                        'caption' => CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE,
                        'location' => "subdivision.delete($sub_id)")
        );
        $this->activeTab = "subdivisionDelete";
        $this->treeMode = 'sitemap';
        $this->treeSelectedNode = "sub-".$sub_id;
    }

}

class ui_config_subdivision_delete_service_subs extends ui_config_subdivision_delete {

    function __construct($sub_id) {

        $nc_core = nc_Core::get_object();
        $cat_id = $nc_core->subdivision->get_by_id($sub_id, "Catalogue_ID");
        $this->locationHash = "subdivision.delete($sub_id)";
        $this->headerText = CONTROL_CONTENT_SUBDIVISION_INDEX_DELETECONFIRMATION;
        $this->tabs = array(
                array('id' => 'subdivisionDelete',
                        'caption' => CONTROL_CONTENT_SUBDIVISION_FUNCS_LINEADD_DELETE,
                        'location' => "subdivision.delete($sub_id)")
        );
        $this->activeTab = "subdivisionDelete";
        $this->treeMode = 'sitemap';
        $this->treeSelectedNode = "sub-".$sub_id;
        $this->actionButtons[] = array("id" => "sitemap",
                "align" => "left",
                "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_A_BACKTOSECTIONLIST,
                "location" => "#site.map($cat_id)"
        );
    }

}

class ui_config_subdivision_info extends ui_config_subdivision_generic {

    function __construct($sub_id, $toolbar_action) {
        $this->init($sub_id, 0);
        $this->activeTab = "info";

        // кнопки на тулбаре
        $this->toolbar = array();
        // информация о разделе
        $this->toolbar[] = array('id' => "info",
                'caption' => SUBDIVISION_TAB_INFO_TOOLBAR_INFO,
                'location' => "subdivision.info($sub_id)",
                'group' => "grp1"
        );
        // список подразделов
        $this->toolbar[] = array('id' => "sublist",
                'caption' => SUBDIVISION_TAB_INFO_TOOLBAR_SUBLIST,
                'location' => "subdivision.sublist($sub_id)",
                'group' => "grp1"
        );
        // Пользователи
        $this->toolbar[] = array('id' => "userlist",
                'caption' => SUBDIVISION_TAB_INFO_TOOLBAR_USERLIST,
                'location' => "subdivision.userlist($sub_id)",
                'group' => "grp1"
        );

        $this->activeToolbarButtons[] = $toolbar_action;
        $this->locationHash = "subdivision.$toolbar_action($sub_id)";
    }

}

class ui_config_subdivision_subclass extends ui_config_subdivision_generic {

    function __construct($sub_id, $toolbar_action, $cc_id=0) {
        $this->init($sub_id, 0);
        $this->activeTab = "subclass";

        // кнопки на тулбаре
        $this->toolbar = array();

        $cc_subs = array();
        // настройки шаблонов в разделе
        if ($this->sub->is_subclass_admin()) {
            $i = 0;
            foreach ($this->sub->get_administered_subclasses() as $sc) {
                $this_class = ($i++ == 0 && sizeof($this->toolbar)) ? 'divider_left' : '';
                $this->toolbar[] = array('id' => "subclass$sc[Class_ID]-$sc[Sub_Class_ID]",
                        'caption' => $sc["Sub_Class_Name"],
                        'location' => "subclass.edit($sc[Sub_Class_ID],$sc[Subdivision_ID])",
                        'group' => "grp1",
                        'className' => $this_class,
                        'dragEnabled' => true,
                        'metadata' => array("subdivisionId" => $sc['Subdivision_ID'])
                );
                $cc_subs[$sc["Sub_Class_ID"]] = $sc["Class_ID"];
            }
        }

        // список шаблонов в разделе
        if ($cc_subs && $this->sub->is_subdivision_admin())
                array_unshift($this->toolbar, array('id' => "list",
                    'caption' => SUBDIVISION_TAB_INFO_TOOLBAR_CCLIST,
                    'location' => "subclass.list($sub_id)",
                    'group' => "grp1"
            ));

        if ($cc_id) {
            $this->locationHash = "subclass.$toolbar_action($cc_id,$sub_id)";
            $this->activeToolbarButtons[] = "subclass{$cc_subs[$cc_id]}-{$cc_id}";
        } else {
            $this->locationHash = "subclass.$toolbar_action($sub_id)";
            $this->activeToolbarButtons[] = $toolbar_action;
        }
    }

}

class ui_config_subdivision_trashed_objects extends ui_config_subdivision_generic {

    function __construct($sub_id) {
        $this->init($sub_id, 0);
        $this->activeTab = "trashed_objects";
        $this->actionButtons = array();
    }

}

class ui_config_subdivision_versions extends ui_config_subdivision_generic {

    function __construct($sub_id, $toolbar_action = '') {

        $this->init($sub_id);
        $this->activeTab = "versions";
        $this->actionButtons = array();

        // кнопки на тулбаре
        $this->toolbar = array();
        foreach (array('subdivision', 'infoblock', 'object') as $v) {
            $this->toolbar[] = array('id' => $v,
                'caption' => constant('NETCAT_VERSION_TAB_TOOLBAR_' . strtoupper($v)),
                'location' => "subdivision.versions" . ($v != 'subdivision' ? '.' . $v: NULL) . "(".$sub_id.")",
                'group' => "versions"
            );
        }

        $this->activeToolbarButtons[] = $toolbar_action ?: 'subdivision';
        $this->locationHash = "subdivision.versions" . ($toolbar_action != 'subdivision' ? "." . $toolbar_action : NULL) . "(" . $sub_id .")";
    }

}


class ui_config_subdivision_preview extends ui_config_subdivision_generic {

    function __construct($sub_id, $cc_id) {
        $this->init($sub_id, $cc_id);
        $this->activeTab = "view";

        // кнопки на тулбаре
        $this->toolbar = array();

        $cc_subs = array();
        // настройки шаблонов в разделе
        if ($this->sub->is_subclass_admin() && sizeof($this->sub->subclasses) > 1) {
            $i = 0;
            foreach ($this->sub->get_administered_subclasses() as $sc) {
                $this_class = ($i++ == 0 && sizeof($this->toolbar)) ? 'divider_left' : '';
                $this->toolbar[] = array('id' => "subclass-$sc[Sub_Class_ID]",
                        'caption' => $sc["Sub_Class_Name"],
                        'location' => "subclass.view(0,$sc[Sub_Class_ID])",
                        'group' => "grp1"
                );
            }
        }

        if ($cc_id) {
            $this->activeToolbarButtons[] = "subclass-$cc_id";
            $this->locationHash = "subclass.view(0,$cc_id)";
        }
    }

}

class ui_config_favorite extends ui_config {

    function __construct($active_tab) {
        $this->headerText = FAVORITE_HEADERTEXT;
        switch ($active_tab) {
            case "other":
                $this->tabs[] = array('id' => 'other',
                        'caption' => SECTION_INDEX_FAVORITE_ANOTHER_SUB,
                        'location' => "favorite.other()");
                $this->activeTab = "other";
                $this->locationHash = "#favorite.other()";
                break;
            case "add":
                $this->tabs[] = array('id' => 'add',
                        'caption' => SECTION_INDEX_FAVORITE_ADD,
                        'location' => "#favorite.add()");
                $this->activeTab = "add";
                $this->locationHash = "favorite.add()";
                break;
            case "list":
                $this->tabs[] = array('id' => 'list',
                        'caption' => SECTION_INDEX_FAVORITE_LIST,
                        'location' => "favorite.list()");
                $this->activeTab = "list";
                $this->locationHash = "#favorite.list()";
                break;
        }

        $this->treeMode = 'sitemap';
//    $this->treeSelectedNode = "sub-".$sub_id;
    }

}
