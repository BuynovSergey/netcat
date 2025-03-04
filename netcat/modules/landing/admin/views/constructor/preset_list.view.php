<?php if (!class_exists('nc_core')) { die; } ?>

<?

/**
 * Список пресетов для вставки в форму.
 * В форме должен быть input[name=preset_keyword], значение которого изначально
 * правильно установлено.
 * Извне должно быть передано значение $presets.
 * Может быть предано значение $selected_preset_keyword (в т.ч. пустое или false).
 */

/** @var nc_landing_preset_collection $presets */
/** @var nc_landing_preset $preset */

if (empty($presets) || !count($presets)) {
    return;
}

$selected_preset_keyword = isset($selected_preset_keyword)
    ? $selected_preset_keyword
    : $presets->first()->get_keyword();

?>

<div class="nc-landing-preset-list">
    <? foreach ($presets as $preset): ?>
        <div class="nc-landing-preset<?= ($selected_preset_keyword == $preset->get_keyword() ? " nc--selected" : "") ?>"
         data-preset-keyword="<?= $preset->get_keyword() ?>">
            <? if ($preset->get_screenshot_path()): ?>
                <div class="nc-landing-preset-screenshot"
                 style="background-image: url('<?= $preset->get_screenshot_thumbnail_path() ?>')"
                 data-screenshot-url="<?= $preset->get_screenshot_path() ?>">
                    <div class="nc-landing-preset-screenshot-overlay"
                     title="<?= htmlspecialchars(NETCAT_MODULE_LANDING_SAVE_PRESET_SHOW_SCREENSHOT) ?>">
                        <i class="nc-icon-zoom-in"></i>
                    </div>
                </div>
            <? else: ?>
                <div class="nc-landing-preset-screenshot nc-landing-preset-screenshot-missing"></div>
            <? endif; ?>

            <div class="nc-landing-preset-details">
                <div class="nc-landing-preset-name"><?= $preset->get_name() ?></div>
                <div class="nc-landing-preset-description"><?= $preset->get_description(); ?></div>
            </div>
        </div>
    <? endforeach; ?>
</div>

<script>
(function() {

// Выбор пресета в списке пресетов
var preset_click = 'click.nc_landing_preset',
    presets = $nc('.nc-landing-preset');

presets.off(preset_click).on(preset_click, function() {
    var clicked_preset = $nc(this),
        form = clicked_preset.closest('form');

    form.find('.nc-landing-preset').removeClass('nc--selected');
    clicked_preset.addClass('nc--selected');
    form.find('input[name=preset_keyword]').val(clicked_preset.data('presetKeyword'));
});

// Нажатие на миниатюру скриншота
var screenshot_click = 'click.nc_landing_preset_show_screenshot';
$nc('.nc-landing-preset-screenshot').off(screenshot_click).on(screenshot_click, function() {
    var screenshot_url = $nc(this).data('screenshotUrl');

    if (screenshot_url) {
        var img_container = $nc('<div>').css({
            position: 'absolute',
            'overflow-y': 'auto',
            top: 0, bottom: 0,
            left: '50%', transform: 'translateX(-50%)',
            margin: '20px', 'max-width': '90%',
            cursor: 'zoom-out',
            'background-color': '#fff'
        }).append(
            $nc('<img>', {src: screenshot_url}).css('max-width', '100%')
        );

        $nc('<div>')
            .css({
                position: 'fixed', top: 0, bottom: 0, left: 0, right: 0,
                'z-index': 50000, background: 'rgba(0,0,0,0.5)'
            })
            .appendTo(top.document.body)
            .append(img_container)
            .click(function() { $nc(this).remove(); });
    }
});

})();
</script>
