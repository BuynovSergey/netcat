<?php

require("./function.inc.php");
$cc = isset($cc) ? $cc : null;
$classID = isset($classID) ? $classID : null;
$message = isset($message) ? $message : null;

$sources = array(
    'sitemap' => $ADMIN_PATH . "subdivision/tree_json.php?node=",
    'developer' => $ADMIN_PATH . "dev_json.php?node=",
    'modules' => $ADMIN_PATH . "modules/modules_json.php?node=",
    'users' => $ADMIN_PATH . "user/user_json.php?node=",
    // выбор -связанных-

    'related_message' => $ADMIN_PATH . "related/tree_json.php?mode=related_message&node=",
    'related_subdivision' => $ADMIN_PATH . "related/tree_json.php?mode=related_subdivision&node=",
    'related_subclass' => $ADMIN_PATH . "related/tree_json.php?mode=related_subclass&node=",
    'wizard_subdivision' => $ADMIN_PATH . "wizard/tree_json.php?mode=wizard_subdivision&node=",
    'wizard_parentsub' => $ADMIN_PATH . "wizard/tree_json.php?mode=wizard_parentsub&node=",
    'copy_message' => $ADMIN_PATH . "objects/tree_json.php?mode=copy_message&cc=" . $cc . "&classID=" . $classID . "&message=" . $message . "&node=",

    'classificator' => $ADMIN_PATH . 'dev_json.php?fs=0&node=classificator.list&node_action=',

    'dataclass' => $ADMIN_PATH . 'dev_json.php?fs=0&node=dataclass.list&node_action=',
    'dataclass_fs' => $ADMIN_PATH . 'dev_json.php?fs=1&node=dataclass.list&node_action=',

    'systemclass' => $ADMIN_PATH . 'dev_json.php?fs=0&node=systemclass.list&node_action=',
    'systemclass_fs' => $ADMIN_PATH . 'dev_json.php?fs=1&node=systemclass.list&node_action=',

    'template' => $ADMIN_PATH . 'dev_json.php?fs=0&node=templates&node_action=',
    'template_fs' => $ADMIN_PATH . 'dev_json.php?fs=1&node=templates&node_action=',

    'widgetclass' => $ADMIN_PATH . 'dev_json.php?fs=0&node=widgetclass.list&node_action=',
    'widgetclass_fs' => $ADMIN_PATH . 'dev_json.php?fs=1&node=widgetclass.list&node_action=',

    'redirect' => $ADMIN_PATH . 'dev_json.php?fs=0&node=redirect&node_action='
);

$source = $sources[$_GET['mode']];

if (!$source) {
    exit('Wrong params');
}

$selected_node = nc_array_value($_GET, 'selected_node', '');
$mode = nc_array_value($_GET, 'mode', '');
$fs = nc_array_value($_REQUEST, 'fs');

if (isset($fs) && $fs == 1) {
    $mode = preg_replace('/_fs$/i', '', $mode);
}

$selected_node = $selected_node === $mode . '.list' ? false : htmlspecialchars($selected_node);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tree Frame</title>
    <script type='text/javascript'>
        var File_Mode = <?php echo +$fs; ?>;
        var lang = lang || {};
        lang.tree_plus_title = '<?=NETCAT_TREE_PLUS_TITLE ?>';
        lang.tree_minus_title = '<?=NETCAT_TREE_MINUS_TITLE ?>';
    </script>
    <?= nc_js(); ?>
    <script>nc.register_view('tree');</script>
    <script type='text/javascript' src='<?= nc_add_revision_to_url($ADMIN_PATH . 'js/tree_frame.js') ?>'></script>
    <style>
        /* fix ios width */
        body { width: 100%; position: absolute; }
    </style>
</head>
<body>
<div id='menu_left'>
    <div class='menu_left_block'>
        <?php if (false && in_array($_GET['mode'], array('sitemap', 'classificator', 'dataclass', 'dataclass_fs', 'template', 'template_fs', 'widgetclass', 'widgetclass_fs'))) { ?>
            <input type="text" name="quick_search" class="nc-input" placeholder="<?= NETCAT_TREE_QUICK_SEARCH; ?>"/>
        <? } ?>
        <ul id='siteTree' style='margin-top: 0px; padding-left: 20px; margin-bottom: 0px;'></ul>
    </div>
</div>
<script type='text/javascript'>
    new dynamicTree('tree', 'siteTree', '<?= $source ?>');
    <?php echo $selected_node ? "setTimeout( function() {tree.selectNode('" . $selected_node . "'); tree.toggleNode('" . $selected_node . "', false, true);}, 500);" : ""; ?>
</script>
</body>
</html>