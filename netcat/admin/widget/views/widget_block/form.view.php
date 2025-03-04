<?php
if (!class_exists('nc_core')) {
    die;
}
?>
<div class="nc_admin_form_menu" style='top:-77px'>
    <h2><?=$block_widget_id ? CONTROL_WIDGET_ACTIONS_EDIT : CONTROL_WIDGET_ADD_ACTION ?></h2>
</div>
<div class='nc_admin_form_body'>
    <form id='adminForm' class='nc-form' name='adminForm' method="post" action="<?=$post_url ?>">

        <input type="hidden" name="Widget_ID" id="nc_widget_id">
        <input type="hidden" name="Block_Key" value="<?=$block_key ?>">
        <input type="hidden" name="Catalogue_ID" value="<?=$catalogue_id ?>">
        <? if ($block_widget_id): ?>
            <input type="hidden" name="Block_Widget_ID" value="<?=$block_widget_id ?>">
        <? endif ?>

        <? if ($recomended_widgets): ?>
            <div id="nc_recomended_widgets">
                <?=nc_form::make_field('recomended_widgets', 'select')->set_value($widget_id)->set_caption('Рекомендуемые виджеты (<a href="#" onclick="return show_all_widgets(1)">Все</a>)')->set_options($recomended_widgets) ?>
            </div>
        <? endif ?>

        <div id="nc_all_widgets" style="<?=$recomended_widgets ? 'display:none' : '' ?>">
            <? $toggle = $recomended_widgets ? ' (<a href="#" onclick="return show_all_widgets(0)">Рекомендуемые</a>)' : '' ?>
            <?=nc_form::make_field('all_widgets', 'select')->set_value($widget_id)->set_caption('Все виджеты' . $toggle)->set_options($all_widgets) ?>
        </div>

        <?=nc_form::make_field('Priority', 'string')->set_value($priority)->set_caption(NETCAT_MODERATION_PRIORITY)->set_attr('class', 'nc-input nc--small') ?>

        <hr>
        <div id="nc_widget_settings"></div>
    </form>
</div>

<div class='nc_admin_form_buttons'>
    <button type='button' class='nc_admin_metro_button nc-btn nc--blue' disable><?=$block_widget_id ? NETCAT_CUSTOM_ONCE_SAVE : WIDGETS_LIST_ADD ?></button>
    <button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'><?=CONTROL_BUTTON_CANCEL ?></button>
</div>

<script type='text/javascript'>
var widget_settings_url = '<?=$widget_settings_url ?>';
var block_widget_id     = <?=(int)$block_widget_id ?>;
function show_all_widgets(i) {
    var id = ['#nc_all_widgets','#nc_recomended_widgets'];
    nc(id[i]).fadeOut(50,function(){
        nc(id[i?0:1]).fadeIn(200).find('select').change();
    });
    return false;
}
nc('#nc_recomended_widgets select, #nc_all_widgets select').change(function(){
    var widget_id          = this.value;
    var nc_widget_settings = nc('#nc_widget_settings');

    nc('#nc_widget_id').val(widget_id);

    nc_widget_settings.html('');

    nc.$.ajax({
        url: widget_settings_url + widget_id + (block_widget_id ? '&block_widget_id=' + block_widget_id : ''),
        dataType: 'html',
        success: function(data){
            nc_widget_settings.html(data);
        }
    });

}).first().change();

prepare_message_form();
</script>