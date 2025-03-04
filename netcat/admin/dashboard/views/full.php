<?php
if (!class_exists('nc_core')) {
    die;
}

$nc_core = nc_core::get_object();

?>
<!DOCTYPE html>
<!--[if lt IE 7]><html style="overflow-y:hidden" class="nc-ie6 nc-oldie"><![endif]-->
<!--[if IE 7]><html style="overflow-y:hidden" class="nc-ie7 nc-oldie"><![endif]-->
<!--[if IE 8]><html style="overflow-y:hidden" class="nc-ie8 nc-oldie"><![endif]-->
<!--[if gt IE 8]><!--><html style="overflow-y:hidden"><!--<![endif]-->
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= $nc_core->NC_CHARSET ?>" />
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <title><?= isset($title) ? htmlspecialchars($title, ENT_QUOTES) : SECTION_INDEX_TITLE ?></title>
    <script type='text/javascript'>
        var FIRST_TREE_MODE = '<?= $treeMode ?>';
    </script>
    <?= nc_js(); ?>
    <script type="text/javascript" src="<?= nc_add_revision_to_url($ADMIN_PATH . 'js/main.js') ?>"></script>
    <script type="text/javascript" src="<?= nc_add_revision_to_url($ADMIN_PATH . 'js/container.js') ?>"></script>
    <script type="text/javascript" src="<?= nc_add_revision_to_url($ADMIN_PATH . 'js/dispatcher.js') ?>"></script>
    <script type="text/javascript" src="<?= nc_add_revision_to_url($ADMIN_PATH . 'js/url_routes.js') ?>"></script>

    <?
    // MODULE URL DISPATCHERS
    $modules = $nc_core->modules->get_data();
    //ADMIN_LANGUAGE
    if ( !empty($modules) ) {
        foreach ($modules as $module) {
            $route_files = array();
            if (file_exists(nc_module_folder($module['Keyword']) . 'url_routes.js')) {
                $route_files[] = nc_module_path($module['Keyword']) . 'url_routes.js';
            }
            foreach (nc_minify_file($route_files, 'js') as $file) {
                echo "<script src='" . $file . "'></script>\n";
            }
        }
    }

    include($ADMIN_FOLDER."modules/module_list.inc.php");
    ?>
    <script type='text/javascript' src='<?= nc_add_revision_to_url($ADMIN_PATH . 'js/main_view.js') ?>'></script>
    <script type='text/javascript' src='<?= nc_add_revision_to_url($ADMIN_PATH . 'js/drag.js') ?>'></script>
    <script type='text/javascript'>
        var REMIND_SAVE = '<?= $REMIND_SAVE ?>';
        var TEXT_SAVE = '<?= NETCAT_REMIND_SAVE_TEXT ?>';
        var TEXT_REFRESH = '<?= NETCAT_TAB_REFRESH ?>';
    </script>
</head>
<body style="overflow:hidden">

<div class="wrap_block" style="padding-left:20px; height:100%">

    <div class="wrap_block_2" style="padding-top:6px">

        <div class="header_block">
            <span id='mainViewHeader'></span>

            <div class="slider_block slider_block_1" id="tabs" style="display: none;">
                <div class="left_gradient"><div class="gradient"></div></div>
                <div class="right_gradient"><div class="gradient"></div></div>
                <a href="#" onclick="return false;" class="arrow left_arrow"></a><a href="#" onclick="return false;" class="arrow right_arrow"></a>
                <div class="overflow">
                    <div class="slide">
                        <ul id='mainViewTabs'></ul>
                    </div>
                </div>
            </div>

            <div class="slider_block slider_block_2" id="sub_tabs" style="display: none;">
                <div class="left_gradient"><div class="gradient"></div></div>
                <div class="right_gradient"><div class="gradient"></div></div>
                <a href="#" onclick="return false;" class="arrow left_arrow"></a><a href="#" onclick="return false;" class="arrow right_arrow"></a>
                <div class="overflow">
                    <div class="slide">
                        <div class='toolbar'>
                            <ul id='mainViewToolbar'></ul>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        <div class="content_block">
            <div id='mainViewContent'>
                <iframe src='about:blank' id='mainViewIframe' name='mainViewIframe' style='width:100%; height:100%;' frameborder='0'></iframe>
            </div>
        </div>

        <div class="clear clear_footer"></div>
    </div>
</div>

<div class="nc_footer" style="margin-left:0">
    <div class='main_view_buttons' id='mainViewButtons'></div>
</div>

</body>
</html>