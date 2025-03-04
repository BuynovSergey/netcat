<?php

class nc_redirect_ui extends ui_config {

    public function __construct() {
        parent::__construct(array(
            'treeMode' => 'redirect',
            'headerText' => TOOLS_REDIRECT
        ));
    }

    public function title($header = TOOLS_REDIRECT) {
        $this->headerText = $header;
    }

    public function select_node($group) {
        $this->treeSelectedNode = "redirect-$group";
    }

    public function location($action, $id  = 0) {
        $this->locationHash .= "redirect.$action".($id ? "($id)" : "");
    }

    public function back_button($steps = 1) {
        $this->actionButtons[] = array(
            "id" => "back",
            "align" => "left",
            "caption" => TOOLS_REDIRECT_BACK,
            "action" => "history.go(-$steps)",
        );
    }

    public function submit_button($name = TOOLS_REDIRECT_SAVE, $align = 'right', $red = false) {
        $this->actionButtons[] = array("id" => "submit",
                "caption" => $name,
                "action" => "mainView.submitIframeForm()",
                "align" => $align,
                "red_border" => $red,
        );
    }
    public function submit_action_button($action, $name, $align = 'left', $red = false) {
        $this->actionButtons[] = array("id" => "submit",
                "caption" => $name,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('$action')",
                "align" => $align,
                "red_border" => $red,
        );
    }

    public function tree_add_node($group, $group_name) {
        $redirect_buttons[] = nc_get_array_2json_button(
            TOOLS_REDIRECT_GROUP_EDIT,
            "redirect.group.edit($group)",
            "nc-icon nc--edit nc--hovered");

        $redirect_buttons[] = nc_get_array_2json_button(
            TOOLS_REDIRECT_GROUP_DELETE,
            "redirect.delete($group)",
            "nc-icon nc--remove nc--hovered");

        $this->treeChanges['addNode'][] = array(
            "nodeId" => "redirect-$group",
            "name" => "$group. $group_name",
            "href" => "#redirect.list($group)",
            "sprite" => 'dev-classificator',
            "hasChildren" => false,
            "dragEnabled" => false,
            "buttons" => $redirect_buttons,
        );
    }

    public function tree_change_node($group, $group_name) {
        $this->treeChanges['updateNode'][] = array("nodeId" => "redirect-$group",
                   "name" => "$group. $group_name");
    }

    public function tree_delete_node($group) {
        $this->treeChanges['deleteNode'][] = "redirect-$group";
    }

    public function reload_add_node_button() {
        $this->treeChanges['deleteNode'][] = "bottom-add";
        $this->treeChanges['addNode'][] = array(
            "nodeId" => "bottom-add",
            "name" => TOOLS_REDIRECT_GROUP_ADD,
            "href" => "#redirect.group.add",
            "sprite" => 'plus',
            "hasChildren" => false,
            "dragEnabled" => false,
        );
        ?>
        <script>
            $nc(document).ready(function() {
               if (!(top.$nc('#tree_add_link').length))
                  top.$nc('#tree_mode_name').append('<a class="button icons nc-icon nc--dev-components-add nc--hovered" id="tree_add_link" href="#redirect.group.add" title="<?=TOOLS_REDIRECT_GROUP_ADD?>"></a>');
            });
        </script>
        <?
    }
}

