<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var array $infoblock_data */
/** @var array $visibility_conditions */
/** @var nc_core $nc_core */

$actions = array(
    'index' => NETCAT_INFOBLOCK_VISIBILITY_ACTION_INDEX,
    'full' => NETCAT_INFOBLOCK_VISIBILITY_ACTION_FULL,
    'add' => NETCAT_INFOBLOCK_VISIBILITY_ACTION_ADD,
    'delete' => NETCAT_INFOBLOCK_VISIBILITY_ACTION_DELETE,
    'edit' => NETCAT_INFOBLOCK_VISIBILITY_ACTION_EDIT,
    'search' => NETCAT_INFOBLOCK_VISIBILITY_ACTION_SEARCH,
    'subscribe' => NETCAT_INFOBLOCK_VISIBILITY_ACTION_SUBSCRIBE,
);

?>

<div class="nc-infoblock-settings-dialog-visibility-types">
    <div class="nc-infoblock-settings-dialog-visibility-types-caption">
        <?= ($infoblock_data['Class_ID'] ? NETCAT_INFOBLOCK_VISIBILITY_SHOW_BLOCK : NETCAT_INFOBLOCK_VISIBILITY_SHOW_CONTAINER ) ?>:
    </div>
    <div>
        <label>
            <input type="radio" name="visibility_type" value="all_pages">
            <?= NETCAT_INFOBLOCK_VISIBILITY_ALL_PAGES ?>
        </label>
    </div>
    <div class="nc-infoblock-settings-dialog-visibility-types-this-page">
        <label>
            <input type="radio" name="visibility_type" value="this_page">
            <?= NETCAT_INFOBLOCK_VISIBILITY_THIS_PAGE ?>
        </label>
    </div>
    <div>
        <label>
            <input type="radio" name="visibility_type" value="some_pages">
            <?= NETCAT_INFOBLOCK_VISIBILITY_SOME_PAGES ?>
        </label>
    </div>
</div>
<div class="nc-infoblock-settings-dialog-visibility-conditions nc-infoblock-settings-dialog-visibility-conditions-in-page-edit-mode">
    <div class="nc-infoblock-settings-dialog-visibility-block nc-infoblock-settings-dialog-visibility-block-subdivision">
        <div class="nc-infoblock-settings-dialog-visibility-block-caption">
            <?= NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISIONS ?>
        </div>
        <div class="nc-infoblock-settings-dialog-visibility-subdivision-any"<?php
        if (!empty($visibility_conditions['subdivision'])) {
            echo ' style="display: none"';
        }
        ?>><?= NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISIONS_ANY ?>
        </div>
        <?php
        $print_subdivisions = function ($type) use ($nc_core, $infoblock_data, $visibility_conditions) {
            ?>
            <div class="nc-infoblock-settings-dialog-visibility-subdivision-template" style="display: none">
                <div class="nc-infoblock-settings-dialog-visibility-subdivision">
                    <input type="hidden" name="visibility[<?= $type ?>][#][Condition_ID]" value="">
                    <input type="hidden" name="visibility[<?= $type ?>][#][Sub_Class_ID]" value="<?= $infoblock_data['Sub_Class_ID'] ?>">
                    <input type="hidden" name="visibility[<?= $type ?>][#][Subdivision_ID]" id="cs_visibility_<?= $type ?>_#_value" value="">
                    <!-- ID и название -->
                    <strong id="cs_visibility_<?= $type ?>_#_caption" class="nc-infoblock-settings-dialog-visibility-subdivision-caption"><?= NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_NOT_SELECTED ?></strong>
                    <!-- чекбокс «подразделы» -->
                    <input type="hidden" name="visibility[<?= $type ?>][#][IncludeChildren]" value="0">
                    <label class="nc-infoblock-settings-dialog-visibility-subdivision-children">
                        <input type="checkbox" name="visibility[<?= $type ?>][#][IncludeChildren]" value="1">
                        <?= NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_INCLUDE_CHILDREN ?>
                    </label>
                    <a class="nc-infoblock-settings-dialog-visibility-subdivision-remove" href="#"
                            data-hidden-input-name="visibility[<?= $type ?>][#][_remove]"><?= NETCAT_INFOBLOCK_VISIBILITY_REMOVE_CONDITION ?></a>
                </div>
            </div>
            <div class="nc-infoblock-settings-dialog-visibility-subdivision-list" data-type="<?= $type ?>">
                <?php foreach ((array)$visibility_conditions[$type] as $i => $condition): ?>
                    <div class="nc-infoblock-settings-dialog-visibility-subdivision">
                        <?php $sub = $condition['Subdivision_ID']; ?>
                        <input type="hidden" name="visibility[<?= $type ?>][<?= $i ?>][Condition_ID]" value="<?= $condition['Condition_ID'] ?>">
                        <input type="hidden" name="visibility[<?= $type ?>][<?= $i ?>][Sub_Class_ID]" value="<?= $condition['Sub_Class_ID'] ?>">
                        <input type="hidden" name="visibility[<?= $type ?>][<?= $i ?>][Subdivision_ID]" id="cs_visibility_<?= $type ?>_<?= $i ?>_value" value="<?= $sub ?>">
                        <!-- ID и название -->
                        <strong id="cs_visibility_<?= $type ?>_<?= $i ?>_caption" class="nc-infoblock-settings-dialog-visibility-subdivision-caption">
                            <a href="<?= $nc_core->ADMIN_PATH . "#subdivision.edit($sub)" ?>" target="_blank"><?php
                                try {
                                    echo $sub . '. ' . htmlspecialchars($nc_core->subdivision->get_by_id($sub, 'Subdivision_Name'));
                                } catch (Exception $e) {
                                    echo '(' . NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_DOESNT_EXIST . ')';
                                }
                                ?></a>
                        </strong>
                        <!-- чекбокс «подразделы» -->
                        <input type="hidden" name="visibility[<?= $type ?>][<?= $i ?>][IncludeChildren]" value="0">
                        <label class="nc-infoblock-settings-dialog-visibility-subdivision-children">
                            <input type="checkbox" name="visibility[<?= $type ?>][<?= $i ?>][IncludeChildren]" value="1"<?=
                            ($condition['IncludeChildren'] ? ' checked' : '') ?>>
                            <?= NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_INCLUDE_CHILDREN ?>
                        </label>
                        <a class="nc-infoblock-settings-dialog-visibility-subdivision-remove" href="#"
                                data-hidden-input-name="visibility[<?= $type ?>][<?= $i ?>][_remove]"><?= NETCAT_INFOBLOCK_VISIBILITY_REMOVE_CONDITION ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div>
                <a href="#" class="nc-infoblock-settings-dialog-visibility-subdivision-add" data-type="<?= $type ?>"><?= NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISION_SELECT ?></a></div>
            <?php
        };

        $print_subdivisions('subdivision');
        ?>
    </div>

    <div class="nc-infoblock-settings-dialog-visibility-block nc-infoblock-settings-dialog-visibility-block-subdivision">
        <div class="nc-infoblock-settings-dialog-visibility-block-caption">
            <?= NETCAT_INFOBLOCK_VISIBILITY_SUBDIVISIONS_EXCLUDED ?>
        </div>
        <?php $print_subdivisions('subdivision_exception') ?>
    </div>

    <div class="nc-infoblock-settings-dialog-visibility-block nc-infoblock-settings-dialog-visibility-block-action">
        <div class="nc-infoblock-settings-dialog-visibility-block-caption">
            <?= NETCAT_INFOBLOCK_VISIBILITY_ACTIONS ?>
        </div>
        <div>
            <?php foreach ($actions as $action => $label): ?>
                <?php $key = 'AreaCondition_Action_' . ucfirst($action); ?>
                <input type="hidden" name="data[<?= $key ?>]" value="0">
                <label>
                    <input type="checkbox" name="data[<?= $key ?>]" value="1"<?= $infoblock_data[$key] ? ' checked' : '' ?> data-action="<?= $action ?>">
                    <?= $label ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="nc-infoblock-settings-dialog-visibility-block">
        <div class="nc-infoblock-settings-dialog-visibility-block-caption">
            <?= NETCAT_INFOBLOCK_VISIBILITY_COMPONENTS ?>
        </div>
        <div class="nc-infoblock-settings-dialog-visibility-component-any"<?php
        if (!empty($visibility_conditions['component'])) {
            echo ' style="display: none"';
        }
        ?>><?= NETCAT_INFOBLOCK_VISIBILITY_COMPONENTS_ANY ?>
        </div>
        <?php

        $print_components = function ($type) use ($nc_core, $infoblock_data, $visibility_conditions) {
            ?>
            <div class="nc-infoblock-settings-dialog-visibility-component-template" style="display: none">
                <div class="nc-infoblock-settings-dialog-visibility-component">
                    <input type="hidden" name="visibility[<?= $type ?>][#][Condition_ID]" value="">
                    <input type="hidden" name="visibility[<?= $type ?>][#][Sub_Class_ID]" value="<?= $infoblock_data['Sub_Class_ID'] ?>">
                    <input type="hidden" name="visibility[<?= $type ?>][#][Class_ID]" id="cs_visibility_<?= $type ?>_#_value" value="">
                    <!-- ID и название -->
                    <strong id="cs_visibility_<?= $type ?>_#_caption" class="nc-infoblock-settings-dialog-visibility-component-caption"><?= NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_NOT_SELECTED ?></strong>
                    <a class="nc-infoblock-settings-dialog-visibility-component-remove" href="#"
                            data-hidden-input-name="visibility[<?= $type ?>][#][_remove]"><?= NETCAT_INFOBLOCK_VISIBILITY_REMOVE_CONDITION ?></a>
                </div>
            </div>
            <div class="nc-infoblock-settings-dialog-visibility-component-list" data-type="<?= $type ?>">
                <?php foreach ((array)$visibility_conditions[$type] as $i => $condition): ?>
                    <div class="nc-infoblock-settings-dialog-visibility-component">
                        <?php $component = $condition['Class_ID']; ?>
                        <input type="hidden" name="visibility[<?= $type ?>][<?= $i ?>][Condition_ID]" value="<?= $condition['Condition_ID'] ?>">
                        <input type="hidden" name="visibility[<?= $type ?>][<?= $i ?>][Sub_Class_ID]" value="<?= $condition['Sub_Class_ID'] ?>">
                        <input type="hidden" name="visibility[<?= $type ?>][<?= $i ?>][Class_ID]" id="cs_visibility_<?= $type ?>_<?= $i ?>_value" value="<?= $component ?>">
                        <!-- ID и название -->
                        <strong id="cs_visibility_<?= $type ?>_<?= $i ?>_caption" class="nc-infoblock-settings-dialog-visibility-component-caption">
                            <a href="<?= $nc_core->ADMIN_PATH . "#dataclass_fs.edit($component)" ?>" target="_blank"><?php
                                try {
                                    echo $component . '. ' . htmlspecialchars($nc_core->component->get_by_id($component, 'Class_Name'));
                                } catch (Exception $e) {
                                    echo '(' . NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_DOESNT_EXIST . ')';
                                }
                                ?></a>
                        </strong>
                        <a class="nc-infoblock-settings-dialog-visibility-component-remove" href="#"
                                data-hidden-input-name="visibility[<?= $type ?>][<?= $i ?>][_remove]"><?= NETCAT_INFOBLOCK_VISIBILITY_REMOVE_CONDITION ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div>
                <a href="#" class="nc-infoblock-settings-dialog-visibility-component-add" data-type="<?= $type ?>"><?= NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_SELECT ?></a></div>
            <?php
        };

        $print_components('component');

        ?>
    </div>

    <div class="nc-infoblock-settings-dialog-visibility-block">
        <div class="nc-infoblock-settings-dialog-visibility-block-caption">
            <?= NETCAT_INFOBLOCK_VISIBILITY_COMPONENTS_EXCLUDED ?>
        </div>
        <?php $print_components('component_exception'); ?>
    </div>

    <div class="nc-infoblock-settings-dialog-visibility-block nc-infoblock-settings-dialog-visibility-block-object">
        <div class="nc-infoblock-settings-dialog-visibility-block-caption">
            <?= NETCAT_INFOBLOCK_VISIBILITY_OBJECTS ?>
        </div>
        <div class="nc-infoblock-settings-dialog-visibility-object-any"<?php
        if (!empty($visibility_conditions['object'])) {
            echo ' style="display: none"';
        }
        ?>><?= NETCAT_INFOBLOCK_VISIBILITY_OBJECTS_ANY ?>
        </div>
        <div class="nc-infoblock-settings-dialog-visibility-object-template" style="display: none">
            <div class="nc-infoblock-settings-dialog-visibility-object">
                <input type="hidden" name="visibility[object][#][Condition_ID]" value="">
                <input type="hidden" name="visibility[object][#][Sub_Class_ID]" value="<?= $infoblock_data['Sub_Class_ID'] ?>">
                <input type="hidden" name="visibility[object][#][Class_ID]" value="%COMPONENT_ID">
                <input type="hidden" name="visibility[object][#][Message_ID]" id="cs_visibility_object_#_value" value="">
                <!-- компонент -->
                [%COMPONENT_NAME] &nbsp;
                <!-- название объекта -->
                <strong id="cs_visibility_object_#_caption"  class="nc-infoblock-settings-dialog-visibility-object-caption"><?= NETCAT_INFOBLOCK_VISIBILITY_OBJECT_NOT_SELECTED ?></strong>
                <a class="nc-infoblock-settings-dialog-visibility-object-remove" href="#"
                        data-hidden-input-name="visibility[object][#][_remove]"><?= NETCAT_INFOBLOCK_VISIBILITY_REMOVE_CONDITION ?></a>
            </div>
        </div>
        <div class="nc-infoblock-settings-dialog-visibility-object-list" data-type="object">
            <?php foreach ((array)$visibility_conditions['object'] as $i => $condition): ?>
                <div class="nc-infoblock-settings-dialog-visibility-object">
                    <input type="hidden" name="visibility[object][<?= $i ?>][Condition_ID]" value="<?= $condition['Condition_ID'] ?>">
                    <input type="hidden" name="visibility[object][<?= $i ?>][Sub_Class_ID]" value="<?= $infoblock_data['Sub_Class_ID'] ?>">
                    <input type="hidden" name="visibility[object][<?= $i ?>][Class_ID]" value="<?= $condition['Class_ID'] ?>">
                    <input type="hidden" name="visibility[object][<?= $i ?>][Message_ID]" value="<?= $condition['Message_ID'] ?>">
                    <!-- компонент -->
                    [<?php
                    try {
                        echo $condition['Class_ID'] . '. ' . $nc_core->component->get_by_id($condition['Class_ID'], 'Class_Name');
                    } catch (Exception $e) {
                        echo NETCAT_INFOBLOCK_VISIBILITY_COMPONENT_DOESNT_EXIST;
                    }
                    ?>] &nbsp;
                    <!-- название объекта -->
                    <strong id="cs_visibility_object_#_caption" class="nc-infoblock-settings-dialog-visibility-object-caption"><?= NETCAT_MODERATION_OBJECT . ' #' . $condition['Message_ID'] ?></strong>
                    <a class="nc-infoblock-settings-dialog-visibility-object-remove" href="#"
                            data-hidden-input-name="visibility[object][<?= $i ?>][_remove]"><?= NETCAT_INFOBLOCK_VISIBILITY_REMOVE_CONDITION ?></a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="nc-infoblock-settings-dialog-visibility-object-add">
            <?= nc_admin_select_component('', 'component_id', '') ?>
        </div>
    </div>
</div>

<script>
(function($) {
    var dialog = nc.ui.modal_dialog.get_current_dialog(),
        form_container = dialog ? dialog.get_form() : $('body');

    var page_data = $('meta#nc_page').data(),
        has_page_data = true;

    if (!page_data) {
        page_data = {};
        has_page_data = false;
    }

    var not_empty = function() { return !!this.value; },
        empty = function() { return !this.value; };

    // функции для определения типа выбранных условий (все страницы / условия / только текущая)

    /**
     * Возвращает все неудалённые блоки условий указанного типа
     * @param {string} type
     * @param {boolean|undefined} [whitelisted]
     *      если не указано (undefined), то и включённые и исключённые; true — только включённые; false — только исключённые
     * @returns {boolean}
     */
    function get_list_items(type, whitelisted) {
        var data_type = whitelisted !== undefined ? '[data-type="' + type + (whitelisted ? '' : '_exception') + '"]' : '';
        return form_container.find(
            '.nc-infoblock-settings-dialog-visibility-' + type + '-list' + data_type + ' ' +
            '.nc-infoblock-settings-dialog-visibility-' + type + ':not(.nc-infoblock-settings-dialog-visibility-condition-removed)'
        );
    }

    // возвращает все чекбоксы action
    function get_action_checkboxes() {
        return form_container.find('.nc-infoblock-settings-dialog-visibility-block-action :checkbox[name^="data[AreaCondition_Action_"]');
    }

    // есть условия указанного типа?
    function has_conditions(type, input_name, whitelisted) {
        var inputs = get_list_items(type, whitelisted).find('input[name$="[' + input_name + ']"]');
        return inputs.filter(empty).length !== inputs.length;
    }

    // есть условия по разделам?
    function has_subdivision_conditions(whitelisted) {
        return has_conditions('subdivision', 'Subdivision_ID', whitelisted);
    }

    // есть условия по компонентам?
    function has_component_conditions(whitelisted) {
        return has_conditions('component', 'Class_ID', whitelisted);
    }

    // есть условия по объектам?
    function has_object_conditions() {
        return has_conditions('object', 'Message_ID');
    }

    // есть условия по типу страницы (action)?
    function has_action_conditions() {
        var inputs = get_action_checkboxes();
        return inputs.filter(':checked').length !== inputs.length;
    }

    // есть только один конкретный тип страницы?
    function has_only_action(action) {
        var inputs = get_action_checkboxes().filter(':checked');
        return inputs.length === 1 && inputs.data('action') === action;
    }

    // есть только условие для указанного раздела?
    function has_only_subdivision(subdivision_id) {
        var inputs = get_list_items('subdivision', true).find('input[name$="[Subdivision_ID]"]').filter(not_empty);
        return inputs.length === 1 &&
               inputs.val() == subdivision_id &&
               !inputs.closest('.nc-infoblock-settings-dialog-visibility-subdivision').find(':checkbox[name$="[IncludeChildren]"]').prop('checked');
    }

    // есть только условие для указанного объекта?
    function has_only_object(component_id, object_id) {
        var all_objects = get_list_items('object').filter(function() {
                return !!$(this).find('input[name$="[Message_ID]"]').val();
            }),
            this_object = all_objects.filter(function() {
                var block = $(this);
                return block.find('input[name$="[Class_ID]"]').val() == component_id &&
                       block.find('input[name$="[Message_ID]"]').val() == object_id;
            });
        return all_objects.length === 1 && this_object.length;
    }

    // типы страниц, которые не привязаны к объектам
    function is_subdivision_action(action) {
        return action === 'index' || action === 'subscribe' || action === 'search' || action === 'add';
    }

    // получение типа набора условий в зависимости от выбора в форме
    function get_current_visibility_type() {
        var has_sub = has_subdivision_conditions(),
            has_action = has_action_conditions(),
            has_component = has_component_conditions(),
            has_object = has_object_conditions();

        if (!has_sub && !has_action && !has_component && !has_object) {
            return 'all_pages';
        }

        if (has_only_action(page_data.action)) {
            if (is_subdivision_action(page_data.action)) {
                // привязка к разделу
                if (has_only_subdivision(page_data.subdivisionId) && !has_subdivision_conditions(false) && !has_object) {
                    return 'this_page';
                }
            } else {
                // привязка к объекту
                if (has_only_object(page_data.componentId, page_data.objectId)) {
                    return 'this_page';
                }
            }
        }
        return 'some_pages';
    }

    // обновление переключателя страниц, на которых отображается блок
    function update_visibility_type() {
        form_container.find('input[name="visibility_type"][value="' + get_current_visibility_type() + '"]').prop('checked', true);
    }

    form_container.find('.nc-infoblock-settings-dialog-visibility-conditions').on('change', 'input, select, textarea', function() {
        var has_object_action = false;
        get_action_checkboxes().filter(':checked').each(function () {
            var action = $(this).data('action');
            if (action !== 'add' && !is_subdivision_action(action)) {
                has_object_action = true;
                return false;
            }
        });
        $('.nc-infoblock-settings-dialog-visibility-block-object').toggle(has_object_action);
        update_visibility_type();
    });

    update_visibility_type();

    // прячем радиокнопку «только на этой странице», если диалог открыт не в режиме редактирования
    // (когда нет информации о текущей странице)
    form_container.find('.nc-infoblock-settings-dialog-visibility-types-this-page').toggle(has_page_data);

    // обновление полей при переключении visibility_type
    form_container.find('input[name="visibility_type"]').change(function() {
        var visibility_type = $(this).val();
        if (visibility_type === 'some_pages') {
            return;
        }

        form_container.find('.nc-infoblock-settings-dialog-visibility-subdivision-remove').click();
        form_container.find('.nc-infoblock-settings-dialog-visibility-component-remove').click();
        form_container.find('.nc-infoblock-settings-dialog-visibility-object-remove').click();

        if (visibility_type === 'all_pages') {
            get_action_checkboxes().prop('checked', true).change();
        } else if (visibility_type === 'this_page') {
            get_action_checkboxes().prop('checked', false)
                .filter(':checkbox[data-action="' + page_data.action + '"]').prop('checked', true);
            if (is_subdivision_action(page_data.action)) {
                add_current_subdivision_condition();
            } else {
                add_current_object_condition();
            }
        }
    });

    // добавления блока с условием
    function add_condition(type, replacements) {
        var list_block = form_container.find('.nc-infoblock-settings-dialog-visibility-' + type + '-list[data-type="' + type + '"]'),
            template = list_block.closest('.nc-infoblock-settings-dialog-visibility-block').find('.nc-infoblock-settings-dialog-visibility-' + type + '-template').html(),
            next_index = list_block.children().length;
        template = template.replace(/#/g, next_index);
        for (var key in replacements || {}) {
            template = template.replace(new RegExp(key, 'g'), replacements[key]);
        }
        return $(template).appendTo(list_block);
    }

    // добавление условия для текущего раздела
    function add_current_subdivision_condition() {
        var subdivision_block = add_condition('subdivision'),
            caption = page_data.subdivisionId +
                        '. <a href="' + NETCAT_PATH + 'admin/#subdivision.edit(' + page_data.subdivisionId + ')" target="_blank">' +
                        page_data.subdivisionName +
                        '</a>';
        subdivision_block.find('.nc-infoblock-settings-dialog-visibility-subdivision-caption').html(caption);
        subdivision_block.find('input[name$="[Subdivision_ID]"]').val(page_data.subdivisionId).change();
        toggle_any_label('subdivision', subdivision_block.closest('.nc-infoblock-settings-dialog-visibility-block'));
    }

    // добавление условия для текущего объекта
    function add_current_object_condition() {
        var object_block = add_condition('object', {
            '%COMPONENT_ID': page_data.componentId,
            '%COMPONENT_NAME': page_data.componentId + '. ' + page_data.componentName
        });
        object_block.find('.nc-infoblock-settings-dialog-visibility-object-caption').html(page_data.objectName);
        object_block.find('input[name$="[Class_ID]"]').val(page_data.componentId);
        object_block.find('input[name$="[Message_ID]"]').val(page_data.objectId).change();
        toggle_object_any_label(object_block.closest('.nc-infoblock-settings-dialog-visibility-block'));
    }

    // открытие попапа выбора связанного объекта
    function open_popup(script_path, width) {
        var popup = window.open(
            '<?= $nc_core->ADMIN_PATH ?>related/' + script_path,
            'nc_popup_visibility',
            'width=' + width + ',height=600,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes'
        );
        popup.focus();
    }

    // показ или скрытие «любые ...» в зависимости от того, есть ли что-то в списке
    function toggle_any_label(type, container) {
        container.find('.nc-infoblock-settings-dialog-visibility-' + type + '-any').toggle(
            container.find('.nc-infoblock-settings-dialog-visibility-' + type + ':visible').length === 0
        );
    }

    // добавление и удаление разделов и компонентов
    function init_type(type, script_path) {
        // добавление
        form_container.find('.nc-infoblock-settings-dialog-visibility-' + type + '-add').click(function(e) {
            var $this = $(this),
                condition_block = $this.closest('.nc-infoblock-settings-dialog-visibility-block'),
                list_block = condition_block.find('.nc-infoblock-settings-dialog-visibility-' + type + '-list'),
                template = condition_block.find('.nc-infoblock-settings-dialog-visibility-' + type + '-template').html(),
                next_index = list_block.children().length,
                field_name = 'visibility_' + $this.data('type') + '_' + next_index;
            list_block.append(template.replace(/#/g, next_index));
            open_popup(script_path + '&cs_field_name=' + field_name, 400);
            toggle_any_label(type, condition_block);
            e.preventDefault();
        });

        // удаление
        form_container.find('.nc-infoblock-settings-dialog-visibility-' + type + '-list')
        .on('click', '.nc-infoblock-settings-dialog-visibility-' + type + '-remove', function(e) {
            var $this = $(this),
                block = $this.closest('.nc-infoblock-settings-dialog-visibility-' + type);
            block.hide().addClass('nc-infoblock-settings-dialog-visibility-condition-removed').append(
                $('<input>', { type: 'hidden', name: $this.data('hidden-input-name'), value: 1 })
            );
            toggle_any_label(type, block.closest('.nc-infoblock-settings-dialog-visibility-block'));
            update_visibility_type();
            e.preventDefault();
        });
    }

    init_type('subdivision', 'select_subdivision.php?cs_type=rel_sub');
    init_type('component', 'select_class.php?cs_type=rel_class');


    //  показывает/прячет блок «любые объекты» в зависимости от того, есть ли что-то в списке
    function toggle_object_any_label(container) {
        container.find('.nc-infoblock-settings-dialog-visibility-object-any').toggle(
            container.find('.nc-infoblock-settings-dialog-visibility-object:visible').length === 0
        );
    }

    // Выбор компонента при добавлении правила для объекта
    var component_select = form_container.find('.nc-infoblock-settings-dialog-visibility-object-add select');
    component_select.prepend($('<option>', { value: 0, text: 'выбрать объект...'})).val(0);
    component_select.change(function() {
        var component_id = component_select.val(),
            component_name = component_select.find('option:selected').text();

        var $this = $(this),
            condition_block = $this.closest('.nc-infoblock-settings-dialog-visibility-block'),
            list_block = condition_block.find('.nc-infoblock-settings-dialog-visibility-object-list'),
            template = condition_block.find('.nc-infoblock-settings-dialog-visibility-object-template').html(),
            next_index = list_block.children().length,
            field_name = 'visibility_object_' + next_index;

        list_block.append(
            template.replace(/#/g, next_index)
                    .replace(/%COMPONENT_ID/g, component_id)
                    .replace(/%COMPONENT_NAME/g, component_name)
        );

        open_popup('select_message.php?component_id=' + component_id + '&cs_type=rel_message&cs_field_name=' + field_name, 950);
        toggle_object_any_label(condition_block);
        component_select.val(0);
    });

    // Удаление правила для объекта
    form_container.find('.nc-infoblock-settings-dialog-visibility-object-list')
    .on('click', '.nc-infoblock-settings-dialog-visibility-object-remove', function(e) {
        var $this = $(this),
            block = $this.closest('.nc-infoblock-settings-dialog-visibility-object');
        block.hide().addClass('nc-infoblock-settings-dialog-visibility-condition-removed').append(
            $('<input>', { type: 'hidden', name: $this.data('hidden-input-name'), value: 1 })
        );
        toggle_object_any_label($this.closest('.nc-infoblock-settings-dialog-visibility-block'));
        update_visibility_type();
        e.preventDefault();
    });

})($nc);
</script>

