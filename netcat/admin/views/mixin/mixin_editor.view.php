<?php

if (!class_exists('nc_core')) {
    die;
}

/**
 * @var string $field_name_template  шаблон имён полей (по умолчанию: 'data[%s]')
 * @var string $field_name_prefix    префикс имени свойств (например, 'MainArea'; по умолчанию: '')
 * @var string $scope                тип области применения миксинов (по умолчанию равен $field_name_prefix)
 * @var bool $show_preset_select     показывать выбор пресета? (редактирование пресета инфоблока)
 * @var bool $show_breakpoint_type_select показывать выбор типа брейкпоинта? (инфоблок)
 * @var string $container_id         ID div’а, где будет размещён редактор
 *   (по умолчанию генерируется уникальный ID блока — рекомендуется не менять)
 *
 * @var array $data параметры:
 *      — (Prefix_)Mixin_Settings — настройки в виде JSON-строки
 *      Для инфоблока:
 *      — Class_Template_ID || Class_ID
 *      — Sub_Class_ID
 *      — (Prefix_)Mixin_Preset_ID
 *      — (Prefix_)Mixin_BreakpointType
 */


// Структура массива с настройками (пресет):
// селектор → тип (группа) миксинов → ширина «до» : { mixin => ключевое слово миксина, settings => настройки }
// $mixin_settings = array(
//    '' => array(
//        'visibility' => array(
//            600 => array(
//                'mixin' => 'netcat_visibility_hide',
//            ),
//            1200 => array(
//                'mixin' => 'netcat_visibility_hide',
//            ),
//        ),
//        'layout' => array(
//            // ----0----
//            300 => array(
//                'mixin' => '',
//            ),
//            9999 => array(
//                'mixin' => 'netcat_layout_tiles',
//                'settings' => array(
//                    'min_tile_width' => '500',
//                    'objects' => array(
//                        '1' => array('width' => 'MIXIN_SETTINGS_WIDTH')
//                    ),
//                ),
//            ),
//        )
//    ),
//    // АЛЬТ КЛАСС
//    '.tile-wide' => array(),
//);

$nc_core = nc_core::get_object();
$container_id = isset($container_id)
    ? htmlspecialchars($container_id)
    : "nc_mixins_editor_container_{$_SERVER['REQUEST_TIME']}_" . rand(0, PHP_INT_MAX);

$field_name_template = isset($field_name_template) ? $field_name_template : 'data[%s]';
$field_name_prefix = isset($field_name_prefix) ? $field_name_prefix : '';
$settings_prefix = $field_name_prefix ? $field_name_prefix . '_' : '';

$scope = isset($scope) ? $scope : $field_name_prefix;

if (!empty($show_preset_select) && !empty($data['Sub_Class_ID']) && $field_name_prefix) {
    $mixin_presets = $nc_core->sub_class->get_mixin_preset_options($data['Sub_Class_ID'], $field_name_prefix);
} else {
    $mixin_presets = array();
}

// шаблон подключается на странице только один раз (при необходимости используется повторно)
require_once(__DIR__ . '/mixin_editor_template.view.php');

?>

<div id="<?= $container_id ?>"></div>

<script>
(function() {
    // Если в качестве селектора можно будет указать класс, можно будет ограничить область поиска target:
    // var context = nc.ui.modal_dialog.get_current_dialog() || $nc('body');
    new nc_mixin_settings_editor({
        target: '#<?= $container_id ?>',
        field_name_template: <?= json_encode($field_name_template) ?>,
        field_name_prefix: <?= json_encode($field_name_prefix) ?>,
        scope: <?= json_encode($scope ?: 'Index') ?>,
        component_template_id: '<?= nc_array_value($data, 'Class_Template_ID') ?: nc_array_value($data, 'Class_ID') ?>',
        infoblock_id: <?= nc_array_value($data, 'Sub_Class_ID', 'null') ?>,
        own_settings: <?= nc_array_value($data, $settings_prefix . 'Mixin_Settings') ?: '{}' ?>,
        breakpoint_type: '<?= nc_array_value($data, $settings_prefix . 'Mixin_BreakpointType', 'block') ?>',
        mixin_presets: <?= nc_array_json($mixin_presets) ?>,
        show_preset_select: <?= !empty($show_preset_select) ? 'true' : 'false' ?>,
        show_breakpoint_type_select: <?= !empty($show_breakpoint_type_select) ? 'true' : 'false' ?>
    });
})();
</script>
