<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */
/** @var int $subdivision_id */
/** @var string $area_keyword */
/** @var int $container_id */
/** @var string $position */
/** @var int $infoblock_id */
/** @var array $component_templates */
/** @var string $main_axis */
/** @var string $footer_notice */

// Стили диалога находятся в dialogs/_infoblock_create_with_preview.scss

$nc_core = nc_core::get_object();

$components_by_id = array();
$components_by_group = array();
$component_groups = array();
$component_groups_by_class_id = array();
$templates = array();

// Фильтруем только оптимизированные компоненты
foreach ($component_templates as $component_key => &$component) {
    $component_id = $component['ClassTemplate'] ?: $component['Class_ID'];
    $component['ShowFullViewWarning'] = false;

    if (!empty($component['_IsObjectTemplate'])) {
        if (!in_array($component['Class_Group'], $component_groups, true)) {
            $component_groups[] = $component['Class_Group'];
        }

        $component['Sub_Class_Type'] = 'object_partial';

        $component_groups_by_class_id[$component['Class_ID']] = $component['Class_Group'];
        $components_by_group[$component['Class_Group']][] = $component;
        if ($component['Type'] === 'object_partial') {
            $templates[$component['Class_ID']][] = $component;
        }
    } else {
        if ($area_keyword) {
            $file_class = new nc_tpl_component_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
            $file_class->load(nc_array_value($component, 'Class_ID'), nc_array_value($component, 'File_Path'), nc_array_value($component, 'File_Hash'));

            if (
                nc_check_file($file_class->get_field_path('RecordTemplateFull')) &&
                nc_get_file($file_class->get_field_path('RecordTemplateFull'))
            ) {
                $component['ShowFullViewWarning'] = true;
            }
        }

        $component['Sub_Class_Type'] = 'object_list';

        // Получаем основную информацию (список групп компонентов, список шаблонов компонентов)
        if (!$component['ClassTemplate']) {
            if (!in_array($component['Class_Group'], $component_groups, true)) {
                $component_groups[] = $component['Class_Group'];
            }
            $component_groups_by_class_id[$component['Class_ID']] = $component['Class_Group'];
            $components_by_group[$component['Class_Group']][] = $component;
            if ($component['IsOptimizedForMultipleMode']) {
                $templates[$component['Class_ID']][] = $component;
            }
        } else {
            $templates[$component['ClassTemplate']][] = $component;
        }

        // Получаем список оптимизированных компонентов вместе с картинками и корректными группами
        if ($component['ClassTemplate']) {
            // Нужно скорректировать группу, поскольку у всех шаблонов компонентов она равна "Шаблоны компонентов"
            $component['Class_Group'] = nc_array_value($component_groups_by_class_id, $component['ClassTemplate']);
        }

    }

    $component['Preview'] = $nc_core->component->get_list_preview_relative_path($component['Class_ID']);
    $components_by_id[$component['Class_ID']] = $component;
}

unset($component);

$first_component_group = reset($component_groups);
$first_component = null;

?>
<div class="nc-modal-dialog" data-confirm-close="false" data-width="1000" data-focus="false">

    <div class="nc-modal-dialog-header">
        <h2><?= NETCAT_MODERATION_ADD_BLOCK_TITLE; ?></h2>
    </div>
    <div class="nc-modal-dialog-body nc-infoblock-create-simple-dialog">
        <form action="<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH; ?>action.php" method="post" class="nc-form">

            <input type="hidden" name="ctrl" value="admin.infoblock">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="subdivision_id" value="<?= $subdivision_id; ?>">
            <input type="hidden" name="position_infoblock_id" value="<?= $infoblock_id; ?>">
            <input type="hidden" name="position" value="<?= htmlspecialchars($position); ?>">
            <input type="hidden" name="area_keyword" value="<?= htmlspecialchars($area_keyword); ?>">
            <input type="hidden" name="main_axis" value="<?= htmlspecialchars($main_axis); ?>">

            <input type="hidden" name="data[Parent_Sub_Class_ID]" value="<?= $container_id; ?>">
            <input type="hidden" name="data[Class_ID]" value="">
            <input type="hidden" name="data[Class_Template_ID]" value="">
            <input type="hidden" name="data[Sub_Class_Type]" value="object_list">

            <div class="nc-infoblock-create-simple-dialog-container">
                <div class="nc-infoblock-create-simple-dialog-content-block">
                    <div class="nc-infoblock-create-simple-dialog-groups">
                        <? foreach ($component_groups as $component_group): ?>
                            <? $is_group_selected = $component_group === $first_component_group; ?>
                            <div class="nc-infoblock-create-simple-dialog-component-group <?= $is_group_selected ? 'nc--selected' : ''; ?>"
                                 data-group-name="<?= $component_group; ?>">
                                <?= $component_group; ?>
                            </div>
                        <? endforeach; ?>
                        <div class="nc-infoblock-create-simple-dialog-component-group nc-infoblock-create-simple-dialog-component-group-container <?= !count($components_by_group) ? 'nc--selected' : ''; ?>"
                             data-group-name="<?= NETCAT_MODERATION_CONTAINER; ?>">
                            <?= NETCAT_MODERATION_CONTAINER; ?>
                        </div>
                    </div>

                    <div class="nc-infoblock-create-simple-dialog-components">
                        <? foreach ($components_by_group as $group_key => $group): ?>
                            <? $is_group_selected = $group_key === $first_component_group; ?>
                            <? foreach ($group as $component_key => $component): ?>
                                <?
                                $is_component_selected = false;

                                if ($is_group_selected && $component_key === 0) {
                                    $is_component_selected = true;
                                    $first_component = $component;
                                }

                                $preview = nc_array_value($component, 'Preview');
                                if (isset($templates[$component['Class_ID']]) && count($templates[$component['Class_ID']])) {
                                    $first_template_id = $templates[$component['Class_ID']][0]['Class_ID'];
                                    $preview = $components_by_id[$first_template_id]['Preview'];
                                }

                                ?>
                                <div class="nc-infoblock-create-simple-dialog-component <?= $is_component_selected ? 'nc--selected' : ''; ?>"
                                     data-group-name="<?= $component['Class_Group']; ?>"
                                     data-component-id="<?= $component['Class_ID']; ?>"
                                     data-block-type="<?= $component['Sub_Class_Type'] ?>"
                                     data-component-show-full-view-warning="<?= $component['ShowFullViewWarning'] ? 'true' : 'false'; ?>">
                                    <? if ($preview): ?>
                                        <div class="nc-infoblock-create-simple-dialog-component-preview"
                                             style="background-image: url('<?= $preview; ?>')">
                                        </div>
                                    <? else: ?>
                                        <div class="nc-infoblock-create-simple-dialog-component-preview"></div>
                                    <? endif; ?>
                                    <div class="nc-infoblock-create-simple-dialog-component-name">
                                        <?= $component['Class_Name']; ?>
                                    </div>
                                </div>
                            <? endforeach; ?>
                        <? endforeach; ?>
                        <div class="nc-infoblock-create-simple-dialog-component <?= !count($components_by_group) ? 'nc--selected' : ''; ?>"
                             data-group-name="<?= NETCAT_MODERATION_CONTAINER; ?>"
                             data-component-id="0"
                             data-block-type="container">
                            <div class="nc-infoblock-create-simple-dialog-component-preview nc--default"></div>
                            <div class="nc-infoblock-create-simple-dialog-component-name">
                                <?= NETCAT_MODERATION_ADD_CONTAINER; ?>
                            </div>
                        </div>
                        <div class="nc-infoblock-create-simple-dialog-component nc-infoblock-create-simple-container-main"
                             data-group-name="<?= NETCAT_MODERATION_CONTAINER; ?>"
                             data-component-id="0"
                             data-block-type="main">
                            <div class="nc-infoblock-create-simple-dialog-component-preview"></div>
                            <div class="nc-infoblock-create-simple-dialog-component-name">
                                <?= NETCAT_MODERATION_MAIN_CONTAINER; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <? if ($first_component_group && $first_component): ?>
                    <div class="nc-infoblock-create-simple-dialog-form-block">
                        <div id="nc-record-template-warning" class="nc-alert nc--blue" style="display: none;"><?= CONTROL_CLASS_CLASS_TEMPLATE_RECORD_TEMPLATE_WARNING; ?></div>
                        <div class="nc--left" style="margin-right: 15px;">
                            <p><?= CONTROL_CONTENT_SUBCLASS_CLASSNAME ?></p>
                            <input name="data[Sub_Class_Name]" type="text" title="<?= CONTROL_CONTENT_SUBCLASS_CLASSNAME ?>">
                        </div>
                        <div class="nc--left" style="max-width: 100%;">
                            <p><?= CONTROL_CLASS_CLASS_TEMPLATE; ?></p>
                            <select name="data[Class_Template_ID]" style="max-width: 100%;">
                                <? if (!empty($templates[$first_component['Class_ID']])): ?>
                                    <? foreach ($templates[$first_component['Class_ID']] as $key => $template): ?>
                                        <option <?= $key === 0 ? 'selected=""' : ''; ?>
                                                value="<?= $template['Class_ID']; ?>"
                                                data-component-show-full-view-warning="<?= $template['ShowFullViewWarning'] ? 'true' : 'false'; ?>"><?= $template['Class_Name']; ?></option>
                                    <? endforeach; ?>
                                <? else: ?>
                                    <option value="<?= $first_component['Class_ID']; ?>"
                                            selected=""
                                            data-component-show-full-view-warning="<?= $first_component['ShowFullViewWarning'] ? 'true' : 'false'; ?>"><?= $first_component['Class_Name']; ?></option>
                                <? endif; ?>
                            </select>
                        </div>
                    </div>
                <? endif; ?>
            </div>
        </form>
    </div>

    <div class="nc-modal-dialog-footer">
        <?php if (!empty($footer_notice)): ?>
            <div class="nc-text-left" style="margin-right: 20px">
                <?= $footer_notice ?>
            </div>
        <?php endif; ?>
        <button data-action="submit"><?= NETCAT_MODERATION_BUTTON_ADD; ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>

    <script>
        (function() {
            function update_class_template_id_select_control(id, name) {
                template_select.find('option').remove();

                if (templates && templates.hasOwnProperty(id)) {
                    var component_templates = templates[id];

                    if (!component_templates.length) {
                        return;
                    }

                    var is_first_template = false;

                    component_templates.forEach(function(template) {
                        var is_selected = false;
                        if (!is_first_template) {
                            is_first_template = true;
                            is_selected = true;
                        }
                        template_select.append(
                            '<option ' + (is_selected ? 'selected=""' : '') + ' name="'+ template['Class_Name'] +'" value="' + template['Class_ID'] + '" data-component-show-full-view-warning="' + (template['ShowFullViewWarning'] ? "true" : "false") + '">' + template['Class_Name'] + '</option>'
                        );
                    });
                } else {
                    template_select.append('<option name="'+ name +'" value="' + id + '" selected="" data-component-show-full-view-warning="false">'+ name + '</option>');
                }

                template_select.trigger('change');
            }

            function update_class_template_image(selected_template) {
                if (component_templates && component_templates.hasOwnProperty(selected_template)) {
                    var component = component_templates[selected_template],
                        image = components.filter('.nc--selected').find('.nc-infoblock-create-simple-dialog-component-preview');
                    if (component['Preview']) {
                        image.css('background-image', 'url("' + component['Preview'] + '")');
                    } else {
                        // transparent pixel
                        image.css('background-image', 'url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII="');
                    }
                }
            }

            var component_templates = <?= $components_by_id ? json_encode($components_by_id, true) : '{}'; ?>,
                templates = <?= $templates ? json_encode($templates, true) : '{}'; ?>,
                dialog = nc.ui.modal_dialog.get_current_dialog(),
                groups = dialog.find('.nc-infoblock-create-simple-dialog-component-group'),
                components = dialog.find('.nc-infoblock-create-simple-dialog-component'),
                template_select = dialog.find('select[name="data[Class_Template_ID]"]');

            groups.click(function() {
                var $this = $nc(this),
                    group_components = components.filter('[data-group-name="' + $this.data('group-name') + '"]');
                groups.removeClass('nc--selected');
                $this.addClass('nc--selected');
                components.removeClass('nc--visible');
                group_components.addClass('nc--visible');
                group_components.eq(0).click();

                // Не показывать возможность добавления основной контентной области дважды на страницу:
                // (а) внутрь основной области (в т. ч. в контейнеры внутри неё)
                // (б) если на странице уже есть основная контентная область (.nc-container-main)
                if ($this.is('.nc-infoblock-create-simple-dialog-component-group-container')) {
                    var inside_main_area = <?= strlen($area_keyword) ? 'false' : 'true' ?>;
                    if (inside_main_area || $nc('.nc-container-main').length) {
                        group_components.filter('.nc-infoblock-create-simple-container-main').removeClass('nc--visible');
                    }
                }
            });

            dialog.set_option('on_resize', function() {
                dialog.find('.nc-infoblock-create-simple-dialog-container')
                    .height($nc('#simplemodal-container').height());
            });

            components.click(function() {
                var $this = $nc(this),
                    id = $this.data('component-id'),
                    name = $this.find('.nc-infoblock-create-simple-dialog-component-name').text(),
                    warning = $nc('#nc-record-template-warning'),
                    block_type = $this.data('blockType');
                components.removeClass('nc--selected');
                $this.addClass('nc--selected');
                dialog.find('input[name="data[Class_ID]"]').val(id);
                // Шаблон по умолчанию
                dialog.find('input[name="data[Class_Template_ID]"]').val(id);
                // Тип создаваемого блока
                dialog.find('input[name="data[Sub_Class_Type]"]').val(block_type);

                if ($this.data('component-show-full-view-warning') && block_type !== 'object_partial') {
                    warning.show();
                } else {
                    warning.hide();
                }

                update_class_template_id_select_control(id, name);
            });

            template_select.on('change', function() {
                var $this = $nc(this),
                    warning = $nc('#nc-record-template-warning');

                if ($this.find(':selected').data('component-show-full-view-warning')) {
                    warning.show();
                } else {
                    warning.hide();
                }

                update_class_template_image($nc(this).val());
            });

            groups.eq(0).click();
        })();
    </script>

</div>
