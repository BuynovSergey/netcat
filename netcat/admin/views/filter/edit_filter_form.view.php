<?php
if (!class_exists('nc_core')) {
    die;
}
?>

<table class="nc-filter-component-fields nc-table nc--borderless nc--wide nc--hovered">
    <thead>
        <tr class="nc-bg-lighten">
            <th class="nc--compact">&nbsp;</th>
            <th style="width: 50%"><?= NC_FILTER_TABLE_HEADER_FIELD ?></th>
            <th><?= NC_FILTER_TABLE_HEADER_FORMAT ?></th>
            <th class="nc--compact">&nbsp;</th>
        </tr>
        </thead>
    <tbody>
    </tbody>
    <tfoot>
        <tr class="nc-filter-component-fields-template" style="display: none">
            <td><i class="nc-icon nc--move" style="cursor: move"></i></td>
            <td class="nc-filter-component-fields-description"></td>
            <td>
                <input type="hidden" data-property="field_type" value="" class="nc-data-fields-type">
                <select style="max-width: 100%" data-property="filter_type" class="nc-filter-fields-type">
                </select>
            </td>
            <td><a href="#"><i class="nc-icon nc--remove"></i></a></td>
        </tr>
    </tfoot>
</table>

<!-- Добавление поля в фильтр -->
<table class="nc-filter-component-fields-add nc-table nc--borderless nc--wide">
    <tr>
        <td style="width: 1px; height: 56px">
            <i class="nc-icon nc--plus"></i>
            <i class="nc-icon nc--plus" style="display: none"></i>
        </td>
        <td colspan="3">
            <a href="#" class="nc-filter-component-fields-add-link"><?= NC_FILTER_ADD_FIELD ?></a>
            <select style="width: 100%; display: none" class="nc-filter-component-fields-add-select">
                <option value=""><?= NC_FILTER_SELECT_FIELD ?></option>
                <?php

                    $field_types = array(
                        NC_FIELDTYPE_STRING,
                        NC_FIELDTYPE_INT,
                        NC_FIELDTYPE_SELECT,
                        NC_FIELDTYPE_BOOLEAN,
                        NC_FIELDTYPE_FLOAT,
                        NC_FIELDTYPE_MULTISELECT,
                    );
                    foreach ($fields as $field) {
                        if ($field['edit_type'] != NC_FIELD_PERMISSION_EVERYONE) {
                            continue;
                        }
                        echo "<option value='" . $field['name'] . "' data-type='" . $field['type'] . "'>" . $field['description'] . "</option>";
                    }
                    ?>
            </select>
        </td>
        <td style="width: 1px">
            <a href="#" style="display: none"><i class="nc-icon nc--remove"></i></a>
        </td>
    </tr>
</table>

<script>
    (function ($) {
        var dialog = nc.ui.modal_dialog.get_current_dialog(),
            target_input = dialog.find('input[name="filter_settings"]'),
            fields_table = dialog.find('table.nc-filter-component-fields'),
            template_row = fields_table.find('.nc-filter-component-fields-template'),
            new_field_table = dialog.find('.nc-filter-component-fields-add'),
            new_field_select = new_field_table.find('.nc-filter-component-fields-add-select'),
            event_ns = '.nc-filter-component';

        // инициализация событий в таблице с полями фильтра после изменений в ней
        function init_event_listeners() {
            // Изменения в параметрах полей
            fields_table.find('input, select')
                .off('change' + event_ns)
                .on('change' + event_ns, update_settings);

            // Удаление поля фильтра
            fields_table.find('.nc-icon.nc--remove')
                .off('click' + event_ns)
                .on('click' + event_ns, function () {
                    $(this).closest('tr').remove();
                    update_settings();
                    return false;
                });

            fields_table.tableDnDUpdate();
        }

        // Перетаскивание полей фильтра
        fields_table.tableDnD({
            onDrop: update_settings,
            dragHandle: '.nc-icon.nc--move',
            onDragClass: 'nc--dragged'
        });

        // Переключение строки для добавления поля между ссылкой и селектом
        function toggle_add_row() {
            new_field_table.find('td > *').toggle();
            new_field_table.find('select:visible').focus();
            return false;
        }

        new_field_table.find('.nc-filter-component-fields-add-link').click(toggle_add_row);
        new_field_table.find('.nc-icon.nc--remove').click(toggle_add_row);


        // Добавление строки
        function add_row(name, params, type) {
            var new_row = template_row.clone()
                .removeClass('nc-filter-component-fields-template')
                .appendTo(fields_table.find('tbody'))
                .show();


            // для срабатывания события onDrop в tableDnD нужны id у <tr>
            new_row.attr('id', 'nc_netshop_filter_field_' + name);
            var description = new_field_select.find('option[value=' + name + ']').html();
            new_row.data('name', name);
            new_row.find('.nc-filter-component-fields-description').html(description);

            new_row.find('.nc-data-fields-type').val(type);
            new_row.find('.nc-filter-fields-type').html(get_types_options(type));

            $nc.each(params, function (key, value) {
                new_row.find('[data-property="' + key + '"]').val(value || '');
            });

            return false;
        }

        function get_types_options(field_type){
            var types = <?= nc_array_json($filter_types) ?>,
                type_names = <?= nc_array_json($filter_type_names) ?>;

            var options = '';
            if (field_type) {
                for (var k in types[field_type]) {
                    options += '<option value="' + types[field_type][k] + '">' + type_names[types[field_type][k]] + '</option>';
                }
            }
            return options;
        }

        // Добавление строки при выборе поля в селекте
        new_field_select.on('click blur', function () {
            var select = $nc(this);
            if (!select.val()) {
                return;
            }
            var option = select.find('option:selected');
            add_row(option.attr('value'), {}, option.data('type'));
            init_event_listeners();
            update_settings();
            toggle_add_row();
            select.val('');
        });

        // Обновление настроек в поле custom_settings[filter_settings] (JSON-строка)
        function update_settings() {
            var fields = {};
            fields_table.find('tbody tr').each(function () {
                var row = $(this),
                    field_name = row.data('name');
                fields[field_name] = {};
                row.find('input, select').each(function () {
                    var input = $(this);
                    fields[field_name][input.data('property')] = input.val();
                });
            });
            target_input.val(JSON.stringify(fields));
        }

        // После отправки данных нужно перезагрузить инфоблок с фильтром (если есть)
        dialog.set_option('on_submit_response', function () {
            var filter_infoblock_id = dialog.find('input[name="filter_infoblock_id"]').val();
            window.nc_partial_load && nc_partial_load(filter_infoblock_id);
            dialog.destroy();
        });

        // Инициализация формы (добавление выбранных в настройках полей)
        var fields = [];
        try {
            fields = JSON.parse(target_input.val());
        } catch (e) {
        }

        if ($.isEmptyObject(fields)) {
            toggle_add_row();
        } else {
            for (var i in fields) {
                add_row(i, fields[i], fields[i]['field_type']);
            }
        }

        // Слушатели событий для сгенерированных строк таблицы с полями фильтра
        init_event_listeners();
    })($nc);
</script>

