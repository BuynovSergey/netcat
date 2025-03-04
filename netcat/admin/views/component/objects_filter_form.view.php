<?php
if (!class_exists('nc_core')) {
    die;
}
?>
<div id="nc_form_styles">

<div id='nc_objects_filter'>
    <div class='nc-form nc-panel nc--<?=$is_open ? 'open' : 'close' ?> '>

        <div class='nc-panel-header'>
            <a id='nc_objects_filter_settings_btn' href='#' class='nc-btn nc--white nc--mini nc--right'><i class='nc-icon nc--settings'></i></a>
            <div class="nc-panel-toggle"><i class="nc-caret"></i> <?=NETCAT_MODERATION_FILTER ?></div>
        </div>

        <div id='nc_objects_filter_form' class='nc-panel-content nc-padding-15 nc-bg-lighten<?=$form ? '' : ' nc--hide' ?>'>
            <?=$form ?>
        </div>

        <div id='nc_objects_filter_settings' class='nc-panel-content nc-padding-15 nc-bg-lighten<?=$form ? ' nc--hide' : '' ?>'>
            <form method='post' action='<?=$nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php?ctrl=admin.component&amp;action=set_search_fields&amp;cc=<?=$cc ?>'>
                <?= $nc_core->token->get_input() ?>
                <? foreach ($fields as $field): ?>
                    <div>
                        <label>
                            <input name='fields[]' value='<?=$field['id'] ?>' type="checkbox" <?= $field['search'] ? 'checked="checked"' : '' ?>>
                            <?= $field['description'] ?>
                        </label>
                    </div>
                <? endforeach ?>
                <br>
                <button type='submit' class='nc-btn nc--blue nc--small'><?= NETCAT_CUSTOM_ONCE_SAVE ?></button>
                <button onclick="return nc_objects_filter_toggle_settings()" type='button' class='nc-btn nc--red nc--small nc--bordered'><?= CONTROL_BUTTON_CANCEL ?></button>
            </form>
        </div>
    </div>
</div>

</div>

<script>
function nc_objects_filter_toggle_settings() {
    var $panel = nc('#nc_objects_filter>div.nc-panel');
    if ($panel.hasClass('nc--close')) {
        $panel.toggleClass('nc--open nc--close');
    }
    nc('#nc_objects_filter_form').toggle();
    nc('#nc_objects_filter_settings').toggle();
    return false;
}
nc(document).on('click', '#nc_objects_filter_settings_btn', nc_objects_filter_toggle_settings);
</script>