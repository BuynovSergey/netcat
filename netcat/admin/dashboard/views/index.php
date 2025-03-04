<!DOCTYPE html>
<!--[if lt IE 7]><html class="nc-ie6 nc-oldie"><![endif]-->
<!--[if IE 7]><html class="nc-ie7 nc-oldie"><![endif]-->
<!--[if IE 8]><html class="nc-ie8 nc-oldie"><![endif]-->
<!--[if gt IE 8]><!--><html><!--<![endif]-->
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?=$nc_core->NC_CHARSET ?>" />
    <title><?=DASHBOARD_WIDGET ?></title>

    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />

    <script type="text/javascript">
        FIRST_TREE_MODE = 'sitemap';
    </script>

    <?= nc_js(); ?>
    <script>
        nc.register_view('main');
        nc.root('#mainViewIframe').height( nc.root('body').height() );
    </script>
    <script src="<?=$ADMIN_PATH ?>dashboard/js/jquery.gridster.min.js?<?= $LAST_LOCAL_PATCH ?>" type="text/javascript"></script>
    <script src="<?=$ADMIN_PATH ?>dashboard/js/jquery.ui.custom.min.js?<?= $LAST_LOCAL_PATCH ?>" type="text/javascript"></script>
    <script src="<?=$ADMIN_PATH ?>dashboard/js/nc.ui.dashboard.min.js?<?= $LAST_LOCAL_PATCH ?>" type="text/javascript"></script>
    <!--[if lt IE 9]>
    <script src="<?=$ADMIN_PATH ?>js/IE9.js?<?= $LAST_LOCAL_PATCH ?>"></script>
    <![endif]-->
</head>
<body class="nc-admin nc-dashboard-body">

<? /*
<div class="nc-dashboard-toolbar" id="nc-dashboard-toolbar">
    <a id="nc_dashboard_add_widget" href="#" class="nc-btn nc--blue nc--disabled" onclick="return nc.ui.dashboard.widget_dialog()"><?=DASHBOARD_ADD_WIDGET ?></a>
    <a id="nc_dashboard_settings" href="#" class="nc-btn nc--blue" onclick="return nc.ui.dashboard.edit_mode(this)"><?=STRUCTURE_TAB_SETTINGS ?></a>
    <a id="nc_dashboard_reset_widgets" href="#" class="nc-btn nc--grey" style="display:none" onclick="return nc.ui.dashboard.reset_user_widgets(this)"><?=DASHBOARD_DEFAULT_WIDGET ?></a>
</div>
*/ ?>

<? foreach($demo_catalogues as $demo_catalogue) {
    nc_print_status(sprintf(DEMO_MODE_ADMIN_INDEX_MESSAGE, $demo_catalogue['Catalogue_Name'], $ADMIN_PATH . "catalogue/index.php?action=system&phase=2&CatalogueID=" . $demo_catalogue['Catalogue_ID']), 'error');
} ?>


<?php
list($system_name, $system_color) = nc_system_name_by_id( nc_core()->get_settings('SystemID') );
?>

<?php if ($perm->isSupervisor() || $perm->isDirector()): ?>
    <div class="nc-widget-news" style="background: #f3cf3f; width: 490px; min-height: 150px; padding: 10px; margin: 12px 0 0; box-sizing: border-box; display: none;">
        <div></div>
    </div>

    <script>
        $nc(function(){
            $nc.get("https://netcat.ru/announces/info.php", {
                version: "<?= nc_core()->get_settings('VersionNumber'); ?>",
                edition: "<?= $system_name; ?>",
                last_patch: "<?= nc_core()->get_settings('LastPatch'); ?>",
                license: "<?= nc_core()->get_settings('ProductNumber'); ?>"
            }, function(data){
                if (data) {
                    $nc(".nc-widget-news").show().find("DIV").html(data);
                } else {
                    $nc(".nc-widget-news").hide();
                }
            });
        });
    </script>
<?php endif; ?>

<div class="nc-dashboard" id="nc-dashboard">
    <div>
    <? foreach ($user_widgets as $i => $widget): ?>
        <div id="widget_<?=$i ?>" class="nc-widget-box" data-col="<?=$widget['col'] ?>" data-row="<?=$widget['row'] ?>" data-sizex="<?=$widget['size'][0] ?>" data-sizey="<?=$widget['size'][1] ?>">
            <div class="nc-widget nc--<?=$widget['color'] ?>" style="display:none">
                <?=$widget['content'] ?>
            </div>
        </div>
    <? endforeach ?>
    </div>
</div>


<div class="nc-dashboard-full" id="nc-dashboard-full" style="display:none">
    <div class="nc-nav">
        <div class="nc-nav-tabs"></div>
    </div>
    <div class="nc-content">
        <a href="#" class="nc-close-fullscreen" onclick="nc.ui.dashboard.close_fullscreen(); return false"><i class="nc-icon nc--minimize"></i></a>
        <iframe src="<?=$ADMIN_PATH ?>dashboard/ajax.php?action=full#blank" style='width:100%; height:100%; overflow: hidden;' frameborder="0"></iframe>
    </div>
</div>


<?
// Диалог добавления/редактирования виджета:
?>
<div id="nc_widget_dialog" style="width:400px; display:none">
    <div class="nc-form nc-padding-20 nc-bg-lighten" style="border-bottom:1px solid #DDD">
        <div class="nc-select nc--blocked">
            <select name="widget_type"></select>
            <i class='nc-caret'></i>
        </div>

        <div class="nc-widget-color-palette" id="nc_widget_color_palette">
            <input type="hidden" name="widget_color" value="<?=$default_color ?>" />
            <? foreach (array('lighten', 'light','grey','dark','cyan','green','blue','purple','yellow','orange','red') as $c): ?>
            <a href="#" onclick="return nc.ui.dashboard.select_widget_color('<?=$c ?>')" class="<?=$c==$default_color ? 'nc--selected' : '' ?>">
                <span class="nc-widget nc--<?=$c ?>"><span></span></span>
            </a>
            <? endforeach ?>
        </div>

        <div class="nc--clearfix"></div>
    </div>
    <div id="nc_widget_settings" style="display:none" class="nc-padding-20"></div>
    <div class="nc-form-actions" style="padding:10px 20px">
        <button class="nc-btn nc--bordered nc--red nc--right" onclick="nc.ui.dashboard.close_widget_dialog()" type="button"><?=CONTROL_BUTTON_CANCEL ?></button>
        <button class="nc-btn nc--blue nc--right" type="submit"><?=NETCAT_REMIND_SAVE_SAVE ?></button>
    </div>
</div>

<?=$ui_config ?>

<script type="text/javascript">
(function(){

    // перерисовываем дерево
    if (nc.view.tree) {
        var treeSelector = nc.root.window.treeSelector;
        if (treeSelector) {
            treeSelector.changeMode(FIRST_TREE_MODE);
            treeSelector.removeTreeHighlight();
        }
    }

    var allowed_widgets = <?=($allowed_widgets_json) ?>;
    var user_widgets    = <?=($user_widgets_json) ?>;
    var settings = {
        grid_margin: 10,
        grid_size:   150
    };
    nc.ui.dashboard.init(allowed_widgets, user_widgets, settings);

    // var $toolbar   = nc('#nc-dashboard-toolbar');
    var $dashboard = nc('#nc-dashboard');
    var resize_timeout;

    nc.root('#mainViewButtons div.nc_dashboard_reset_widgets').hide();
    nc.root('#mainViewButtons div.nc_dashboard_settings').css({'border-color':''});
    <? /*
    // Изменяем размер области с виджетами при ресайзе
    // timeout - избаляет от "лагов" в IE<7,8
    nc(window).resize(function(){
        clearTimeout( resize_timeout );
        resize_timeout = setTimeout(function(){
            $dashboard.height( nc('body').height() - 54 );
        }, 100);
    })

    // добавляем тень под тулбар при скроле:
    $dashboard.on('scroll', function() {
        $dashboard.scrollTop() ? $toolbar.addClass('nc-show-border') : $toolbar.removeClass('nc-show-border');
    });
    */ ?>
})();
</script>
</body>
</html>