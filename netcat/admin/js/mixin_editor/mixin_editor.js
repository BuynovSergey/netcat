// IE9+: Object.keys(), Array.forEach(), Array.indexOf(), JSON

// Зависимости: jquery.ui.sortable; minicolors

(function($) {

    // {a: {b: 1}} → {'a.b': 1}
    function flatten(object) {
        return Object.keys(object).reduce(function(result, key) {
            if ((typeof object[key]) === 'object' && object[key]) {
                var flat_object = flatten(object[key]);
                Object.keys(flat_object).forEach(function(key2) {
                    result[key + '.' + key2] = flat_object[key2];
                });
            } else {
                result[key] = object[key];
            }
            return result;
        }, {});
    }

    // {'a.b': 1} → {a: {b: 1}}
    function unflatten(object) {
        var result = {};
        for (var flat_key in object) {
            set_multilevel_object_value(result, flat_key.split('.'), object[flat_key]);
        }
        return result;
    }

    function set_multilevel_object_value(target, keys, end_value) {
        var recipient = target,
            last_index = keys.length - 1;
        for (var i = 0; i < last_index; i++) {
            if (recipient[keys[i]] === undefined) {
                recipient[keys[i]] = {};
            }
            recipient = recipient[keys[i]];
        }
        recipient[keys[last_index]] = end_value;
        return target;
    }

    function replace_from_to(string, from, to) {
        return string.replace('%from', from).replace('%to', to);
    }

    var font_select_options_cache = '';

    /**
     * Constructor
     */
    var nc_mixin_settings_editor = window.nc_mixin_settings_editor = function(options) {
        if (!(this instanceof nc_mixin_settings_editor)) {
            return new nc_mixin_settings_editor(options);
        }

        for (var setting in options) {
            if (setting in this) {
                this[setting] = options[setting];
            }
        }

        this.scope = this.scope || this.field_name_prefix || 'Index';

        var target_container = $(this.target);
        target_container.html($('.nc-mixins-editor-template').html());
        this.container = target_container.find('.nc-mixins-editor');

        this.lang = this.container.data('lang');
        this.set_field_names();

        this.init_breakpoint_type_container(); // показываем или прячем блок с выбором типа брейкпоинтов
        this.find('.nc-mixins-breakpoint-type-select').val(this.breakpoint_type);
        this.breakpoints = {};
        this.breakpoints[this.MAX_WIDTH] = this.MAX_WIDTH.toString();

        // запоминаем порядок групп миксинов для сохранения параметров в правильном порядке
        this.mixin_type_order = this.get_mixin_type_order();

        // устанавливаем/обрабатываем настройки миксинов из options.own_settings
        this.set_own_settings(this.own_settings);

        this.init_preset_container(); // показываем или прячем блок с выбором пресета
        this.apply_preset_settings(); // применяем выбранный пресет

        this.rebuild_table();
        this.init_event_handlers();

        // инициализация редактора свойств для четырёх сторон с 🔒
        var editor = this;
        this.find('.nc-mixins-mixin-settings-lock-sides-button').each(function() {
            editor.init_lock_sides_button($(this));
        });

        // инициализация селектов выбора шрифтов
        this.load_fonts();
        this.find('select.nc-mixins-mixin-font-select').each(function() {
            editor.init_font_select($(this));
        });

        // инициализация полей выбора цвета
        this.init_color_inputs(this.find('[data-color-input]'));

        // инициализация поля загрузчика файлов
        this.init_uploader(this.find('[data-uploader]'));

        this.is_initialized = true;
        this.update_mixin_json(); // изначально инпут пустой, и его значение не обновляется, пока is_initialized = false
    };

    // "Static" properties:
    /** Событие после инициализации формы с настройками миксина (установки значений) */
    nc_mixin_settings_editor.mixin_settings_set_values_event = 'mixin_settings_set_values_event';
    /** Событие после добавления новой пустой множественной настройки миксина (только после нажатия пользователем на «добавить») */
    nc_mixin_settings_editor.mixin_settings_new_row_event = 'mixin_settings_new_row_event';
    /** Событие после установки типа брейкпоинтов (также срабатывает при инициализации редактора **/
    nc_mixin_settings_editor.breakpoint_type_change_event = 'breakpoint_type_change';

    // Instance properties & methods:
    nc_mixin_settings_editor.prototype = {
        // брейкпоинт «любая ширина»
        MAX_WIDTH: 9999,

        is_initialized: false,

        // приходит снаружи из настроек, передаваемых в конструктор:
        target: '#nc_mixins_editor_container',
        field_name_template: 'data[%s]',
        field_name_prefix: '',
        scope: null, // совпадает с field_name_prefix за исключением некоторых особых случаев (field_name_prefix = 'MainArea')
        mixin_presets: [],
        own_settings: {},
        breakpoint_type: 'block',
        component_template_id: undefined, // ID шаблона компонента или ID компонента, для которого открыт диалог
        infoblock_id: undefined,
        show_preset_select: false,
        show_breakpoint_type_select: false,

        // приходит снаружи из data-атрибутов
        lang: {},
        fonts: [],

        // вычисляется на основании имеющихся данных:
        container: null,
        selectors: {}, // Set бы подошёл лучше, но он есть только в IE11+
        breakpoints: {},
        inherited_settings: {},
        temporary_own_settings: {},
        mixin_type_order: [],
        calculated_colors: {},

        constructor: nc_mixin_settings_editor,

        /**
         * Находит элементы внутри редактора
         * @param selector
         * @return {*}
         */
        find: function(selector) {
            return this.container.find(selector);
        },

        /**
         * Инициализирует обработчики событий редактора
         */
        init_event_handlers: function() {
            var editor = this;

            // выбор пресета
            this.find('.nc-mixins-preset-select').change($.proxy(this, 'on_preset_select_change'));

            // редактирование пресета
            this.find('.nc-mixins-preset-actions .nc--edit').click($.proxy(this, 'open_preset_edit_dialog'));

            // удаление пресета
            this.find('.nc-mixins-preset-actions .nc--remove').click($.proxy(this, 'open_preset_delete_dialog'));

            // изменение типа брейкпоинтов
            this.find('.nc-mixins-breakpoint-type-select').change($.proxy(this, 'on_breakpoint_type_select_change')).change();

            // выбор селектора
            this.find('.nc-mixins-selector-select').change($.proxy(this, 'on_selector_select_change'));

            // заголовок ячеек диапазонов:
            // — добавление брейкпоинта
            this.container.on('click', '.nc-mixins-breakpoint-add-button', $.proxy(this, 'show_new_breakpoint_dialog'));

            // — редактирование брейкпоинта
            this.container.on('click', '.nc-mixins-breakpoint', $.proxy(this, 'show_edit_breakpoint_dialog'));

            // ячейки диапазонов:
            // — добавление настроек
            // — редактирование настроек
            this.container.on('click', '.nc-mixins-add-setting, .nc-mixins-settings-marker', $.proxy(this, 'show_mixin_settings'));

            // щелчок на нераскрытую ячейку настроек
            this.container.on('click', '.nc-mixins-settings-cell:not(.nc--active)', function() {
                var last_width_cell = $(this).prev(),
                    settings_marker = last_width_cell.find('.nc-mixins-settings-marker');
                if (settings_marker.length) {
                    settings_marker.click();
                } else {
                    last_width_cell.find('.nc-mixins-add-setting').click();
                }
            });

            // выбор миксина
            this.container.on('change', '.nc-mixins-mixin-select', function() {
                editor.on_mixin_select_change($(this), false);
            });

            // изменение настроек миксина
            // (также срабатывает каскадом при добавлении настроек для диапазона)
            this.container.on('change input', '.nc-mixins-mixin-settings :input', $.proxy(this, 'update_current_mixin_settings'));

            // удаление настроек
            this.container.on('click', '.nc-mixins-mixin-remove', $.proxy(this, 'on_remove_settings_button_click'));

            // добавление множественных настроек миксина
            this.container.on('click', '.nc-mixins-mixin-settings-row-add', $.proxy(this, 'on_mixin_multiple_settings_add_button_click'));

            // удаление множественных настроек миксина
            this.container.on('click', '.nc-mixins-mixin-settings-row-remove', $.proxy(this, 'on_mixin_multiple_settings_remove_button_click'));
        },

        /**
         * Возвращает имя input'а, исходя из настроек field_name_template и field_name_prefix
         * @param {string} field_name
         * @returns {string}
         */
        get_field_name(field_name) {
            return this.field_name_template.replace('%s', (this.field_name_prefix ? this.field_name_prefix + '_' : '') + field_name);
        },

        /**
         * Устанавливает атрибут name input’ов в соответствии с field_name_template и field_name_prefix
         */
        set_field_names: function() {
            var fields = {
                '.nc-mixins-json': 'Mixin_Settings',
                '.nc-mixins-preset-select': 'Mixin_Preset_ID',
                '.nc-mixins-breakpoint-type-select' : 'Mixin_BreakpointType'
            };
            for (var selector in fields) {
                this.find(selector).attr('name', this.get_field_name(fields[selector]));
            }
        },

        /**
         * Возвращает значения всех полей в указанном контейнере
         * (используется для переноса настроек из формы в own_settings и далее
         * в скрытое поле со всеми настройками в JSON)
         * @param container
         * @returns {{}}
         */
        get_mixin_input_values: function(container) {
            var values = {};
            container.find('input, select, textarea').filter('[name^="mixin."]').each(function() {
                var input = $(this),
                    name_without_prefix = this.name.replace(/^mixin\./, ''),
                    value = input.val();

                if (input.is(':radio') && !input.is(':checked')) {
                    return;
                }

                // Для повторяющихся блоков настроек (например, слоёв фона или настроек колонок)
                // используется следующая структура:
                // div.nc-mixins-mixin-settings-rows > div.nc-mixins-mixin-settings-row
                // '#' в названии input’ов при сохранении заменяется на порядковый номер строки ...-row.
                if (name_without_prefix.indexOf('#') >= 0) {
                    // (а) пропускаем, если это инпут в div.nc-mixins-mixin-settings-row-template
                    if (input.closest('.nc-mixins-mixin-settings-row-template').length) {
                        return;
                    }
                    // (б) заменяем '#' на порядковый индекс строки
                    var row = input.closest('.nc-mixins-mixin-settings-row'),
                        index = row.parent().children().index(row);
                    if (index >= 0) {
                        name_without_prefix = name_without_prefix.replace(/#/g, index);
                    }
                }
                if (input.is(':checkbox')) {
                    values[name_without_prefix] = input.is(':checked') ? value : '';
                } else if (input.is('[data-color-input][data-nc-swatches][data-sync-color^="var"]')) {
                    values[name_without_prefix] = input.attr('data-sync-color');
                } else {
                    values[name_without_prefix] = value;
                }
            });
            return values;
        },

        /**
         * Устанавливает унаследованные от пресета настройки
         * @param inherited_settings
         */
        set_inherited_settings: function(inherited_settings) {
            var had_preset = !$.isEmptyObject(this.inherited_settings),
                has_new_preset = !$.isEmptyObject(inherited_settings);

            if (has_new_preset) {
                // удаляем брейкпоинты, которых нет в пресете
                var existing_breakpoints = Object.keys(this.breakpoints),
                    inherited_breakpoints = this.get_breakpoints_as_array_from_settings(inherited_settings),
                    breakpoints_to_delete = existing_breakpoints.filter(function(value) {
                        return inherited_breakpoints.indexOf(value) < 0;
                    });
                if (breakpoints_to_delete.length) {
                    // @todo предупреждать, что будут потеряны настройки (удалены брейкпоинты)
                    // @todo (но только если это изменение, сделанное пользователем, а не инициализация?)
                    for (var i = 0; i < breakpoints_to_delete.length; i++) {
                        this.remove_breakpoint(breakpoints_to_delete[i]);
                    }
                    this.after_breakpoint_change();
                }
            } else if (had_preset) {
                // если был выбран пресет, но потом он «отвязан», то копируем настройки пресета в блок
                // @todo? спрашивать, переносить ли настройки
                this.set_own_settings($.extend({}, this.inherited_settings, this.own_settings));
            }

            // включаем или отключаем редактирование брейкпоинтов:
            this.container.toggleClass('nc-mixins-editor--with-preset', has_new_preset);

            this.inherited_settings = inherited_settings;
            this.extract_selectors_and_breakpoints(inherited_settings);
        },

        /**
         * Сохраняет собственные (неунаследованные) настройки
         * @param own_settings
         */
        set_own_settings: function(own_settings) {
            this.own_settings = own_settings;
            this.extract_selectors_and_breakpoints(own_settings);
        },

        /**
         * Извлекает и запоминает использованные в настройках брейкпоинты и селекторы
         * @param settings
         */
        extract_selectors_and_breakpoints: function(settings) {
            for (var selector in settings) {
                this.selectors[selector] = selector;
                for (var mixin_type in settings[selector]) {
                    for (var breakpoint in settings[selector][mixin_type]) {
                        this.breakpoints[breakpoint] = breakpoint; // имя свойства объекта — всегда строка
                    }
                }
            }
            this.sort_breakpoints();
            this.on_breakpoint_number_change();
            this.update_selectors_select();
        },

        /**
         * Возвращает массив с используемыми брейкпоинтами
         * @param settings
         * @returns {string[]}
         */
        get_breakpoints_as_array_from_settings: function(settings) {
            var breakpoints = {};
            for (var selector in settings) {
                for (var mixin_type in settings[selector]) {
                    for (var breakpoint in settings[selector][mixin_type]) {
                        breakpoints[breakpoint] = breakpoint; // имя свойства объекта — всегда строка
                    }
                }
            }
            return Object.keys(breakpoints);
        },

        /**
         * Сортирует this.breakpoints по возрастанию (после изменений в нём)
         */
        sort_breakpoints: function() {
            // subtraction automatically casts strings to numbers
            var breakpoints_array = Object.keys(this.breakpoints).sort(function(a, b) { return a - b; });
            this.breakpoints = {};
            for (var i = 0; i < breakpoints_array.length; i++) {
                var b = breakpoints_array[i];
                this.breakpoints[b] = b;
            }
        },

        /**
         * Сортирует группы миксинов по их порядку (приоритетам)
         * @param group_settings
         */
        sort_mixins: function(group_settings) {
            var sorted = {};
            $.each(this.mixin_type_order, function(index, mixin_type) {
                if (mixin_type in group_settings) {
                    sorted[mixin_type] = group_settings[mixin_type];
                }
            });
            return sorted;
        },

        /**
         * Возвращает порядок групп миксинов
         */
        get_mixin_type_order: function() {
            var mixin_type_order = [];
            this.find('.nc-mixins-mixin-row').each(function() {
                mixin_type_order.push($(this).data('mixinType'));
            });
            return mixin_type_order;
        },

        /**
         * Прячет/убирает кнопку добавления брейкпоинта в зависимости от того,
         * сколько брейкпоинтов уже задано
         */
        on_breakpoint_number_change: function() {
            // атрибут data-breakpoint-number у контейнера — прячет кнопку добавления (стилями)
            // при достижении максимального числа диапазонов (задаётся в стилях)
            this.container.attr('data-breakpoint-number', Object.keys(this.breakpoints).length);
        },

        /**
         * Отображает диалог добавления брейкпоинта
         * @param event
         */
        show_new_breakpoint_dialog: function(event) {
            var add_button = $(event.target),
                cell = add_button.closest('td'),
                max = parseInt(cell.data('breakpoint'), 10),
                min = parseInt(cell.prev().data('breakpoint'), 10) || 0,
                lang = this.lang;

            // @todo proper dialog
            var message = lang.BREAKPOINT_ADD_PROMPT;
            if (min || max) {
                message += ' ' + replace_from_to(lang.BREAKPOINT_ADD_PROMPT_RANGE, min, max);
            }
            message += ':';
            var new_breakpoint = parseInt(prompt(message), 10);
            if (new_breakpoint && new_breakpoint > min && new_breakpoint < max) {
                this.add_new_breakpoint(new_breakpoint);
            }
        },

        /**
         * Добавляет новый брейкпоинт
         * @param new_breakpoint
         */
        add_new_breakpoint: function(new_breakpoint) {
            this.breakpoints[new_breakpoint] = new_breakpoint;
            this.sort_breakpoints();
            this.on_breakpoint_number_change();
            this.rebuild_table();
        },

        /**
         * Отображает диалог редактирования брейкпоинта
         * @param event
         */
        show_edit_breakpoint_dialog: function(event) {
            if (this.container.is('.nc-mixins-editor--with-preset')) {
                // если выбран пресет, не даём редактировать брейкпоинты
                return;
            }

            var old_breakpoint = $(event.target).closest('.nc-mixins-width').data('breakpoint');

            // @todo proper dialog and constraints
            var message = this.lang.BREAKPOINT_CHANGE_PROMPT;
            var new_breakpoint = prompt(message, old_breakpoint);
            if (new_breakpoint == old_breakpoint || new_breakpoint === null) {
                return;
            }
            if (new_breakpoint === '' || new_breakpoint === '0') {
                this.remove_breakpoint(old_breakpoint);
            } else {
                this.replace_breakpoint(old_breakpoint, new_breakpoint);
            }
            this.after_breakpoint_change();
        },

        /**
         * Заменяет в настройках значение брейкпоинта после его редактирования
         * @param old_breakpoint
         * @param new_breakpoint
         */
        replace_breakpoint: function(old_breakpoint, new_breakpoint) {
            var settings = this.own_settings;
            for (var selector in settings) {
                for (var mixin_type in settings[selector]) {
                    if (settings[selector][mixin_type][old_breakpoint]) {
                        settings[selector][mixin_type][new_breakpoint] = settings[selector][mixin_type][old_breakpoint];
                    }
                }
            }
            this.breakpoints[new_breakpoint] = new_breakpoint;

            this.remove_breakpoint(old_breakpoint);
        },

        /**
         * Удаляет настройки для указанного брейкпоинта
         * @param breakpoint
         */
        remove_breakpoint: function(breakpoint) {
            var settings = this.own_settings;
            for (var selector in settings) {
                for (var mixin_type in settings[selector]) {
                    delete this.own_settings[selector][mixin_type][breakpoint];
                }
            }
            delete this.breakpoints[breakpoint];
        },

        /**
         * Обновляет редактор после изменения и удаления брейкпоинтов
         */
        after_breakpoint_change: function() {
            this.temporary_own_settings = {};
            this.close_opened_mixin_settings();
            this.sort_breakpoints();
            this.on_breakpoint_number_change();
            this.rebuild_table();
            this.update_mixin_json();
        },

        /**
         * Обновляет select с селекторами
         * @param current_selector
         */
        update_selectors_select: function(current_selector) {
            var select = this.find('.nc-mixins-selector-select'),
                last_option = select.find('option:last'),
                selectors_array = Object.keys(this.selectors).sort();
            if (current_selector === undefined) {
                current_selector = select.val();
            }
            select.find('.nc-mixins-selector-select-selector').remove();
            for (var i = 0; i < selectors_array.length; i++) {
                var selector = selectors_array[i];
                if (selector) {
                    $('<option>', {
                        value: selector,
                        text: selector,
                        'class': 'nc-mixins-selector-select-selector'
                    }).insertBefore(last_option);
                }
            }
            select.val(current_selector in this.selectors ? current_selector : '').change();
        },

        /**
         * Перестраивает таблицу с миксинами
         */
        rebuild_table: function() {
            var breakpoints = Object.keys(this.breakpoints).map(Number);
            this.temporary_own_settings = {};
            this.rebuild_table_head_width_ranges(breakpoints);
            this.rebuild_table_body_width_ranges(breakpoints);
        },

        /**
         * Перестраивает заголовок таблицы с миксинами
         */
        rebuild_table_head_width_ranges: function(breakpoints) {
            var num_ranges = breakpoints.length,
                cells = '';

            this.find('.nc-mixins-width-icon').prop('colspan', num_ranges);
            this.find('.nc-mixins-width').remove();

            for (var i = 0; i < num_ranges; i++) {
                var breakpoint = breakpoints[i];
                cells +=
                    '<td class="nc-mixins-width nc-mixins-width-head" data-breakpoint="' + breakpoint + '">' +
                    '<div class="nc-mixins-breakpoint-add-button-container">' +
                    '<div class="nc-mixins-breakpoint-add-button" title="' + this.lang.BREAKPOINT_ADD + '">+</div>' +
                    '</div>' +
                    '<div class="nc-mixins-breakpoint"><span>' +
                    (breakpoint >= this.MAX_WIDTH ? '&#x2731;' : breakpoint) + // ✱
                    '</span></div>' +
                    '</td>';
            }

            cells = $(cells);
            cells.first().addClass('nc-mixins-width--first');
            cells.last().addClass('nc-mixins-width--last');

            this.find('.nc-mixins-width-ranges').prepend(cells)
        },

        /**
         * Перестраивает тело таблицы с миксинами
         */
        rebuild_table_body_width_ranges: function(breakpoints) {
            var editor = this;

            this.find('.nc-mixins-mixin-row').each(function() {
                var row = $(this),
                    cells = '',
                    prev_breakpoint = 0,
                    num_ranges = breakpoints.length;

                row.find('.nc-mixins-width').remove();

                for (var i = 0; i < num_ranges; i++) {
                    var breakpoint = breakpoints[i];
                    cells +=
                        '<td class="nc-mixins-width" data-breakpoint="' + breakpoint + '">' +
                        '<div class="nc-mixins-add-setting-container"><div class="nc-mixins-add-setting">+</div></div>' +
                        '</td>';
                    prev_breakpoint = breakpoint;
                }

                cells = $(cells);
                cells.first().addClass('nc-mixins-width--first');
                cells.last().addClass('nc-mixins-width--last');

                row.prepend(cells);

                editor.update_row_markers(row);

                // Не показываем строку, если в data-mixin-scopes нет scope этого редактора
                var mixin_scopes = row.data('mixin-scopes') || [];
                row.toggle(mixin_scopes.indexOf(editor.scope) !== -1);
            });
        },

        /**
         * Обновляет маркеры (● и 🡡) в указанной строке
         * @param row
         */
        update_row_markers: function(row) {
            var last_stop = -1,
                previous_existing_breakpoint = 0,
                row_mixin_type_name = row.find('.nc-mixins-mixin-type-name').html(),
                editor = this,
                mixin_type = row.data('mixinType');

            row.find('.nc-mixins-width').each(function(i) {
                var cell = $(this),
                    breakpoint = cell.data('breakpoint'),
                    range_description = editor.make_range_description(previous_existing_breakpoint, breakpoint);

                cell.prop('title', row_mixin_type_name + ' ' + range_description)
                    .data('range', range_description);

                // удаляем старые маркеры
                cell.find('.nc-mixins-settings-marker, .nc-mixins-base-settings-marker').remove();

                // удаляем имеющиеся классы nc-mixins-width--span-X
                cell.removeClass(function(j, className) {
                    return (className.match(/\bnc-mixins-width--span-\S+/g) || []).join(' ');
                });
                // на сколько диапазонов будут распространяться заданные здесь параметры?
                if (i - last_stop > 1) {
                    cell.addClass('nc-mixins-width--span-' + (i - last_stop));
                }

                // наличие настроек по их типу
                var has_own_settings = // собственные настройки
                        !!editor.get_settings(editor.own_settings, mixin_type, breakpoint) ||
                        !!editor.get_settings(editor.temporary_own_settings, mixin_type, breakpoint),
                    has_inherited_preset_settings = // унаследованные от пресета
                        !!editor.get_settings(editor.inherited_settings, mixin_type, breakpoint),
                    has_own_base_settings = // унаследованные от основного состояния (без дополнительного селектора)
                        editor.get_current_selector() &&
                        !!editor.get_settings(editor.own_settings, mixin_type, breakpoint, '');

                if (has_own_settings) {
                    cell.append('<div class="nc-mixins-settings-marker nc-mixins-own-settings-marker">●</div>');
                } else if (has_inherited_preset_settings) {
                    cell.append('<div class="nc-mixins-settings-marker nc-mixins-inherited-settings-marker">🡡</div>');
                } else if (has_own_base_settings) {
                    // Когда выбран дополнительный селектор, показывается индикатор наличия настроек в основном
                    // состоянии (без дополнительного селектора).
                    // Этот индикатор неактивный, поэтому у него нет класса nc-mixins-settings-marker.
                    cell.append('<div class="nc-mixins-base-settings-marker">○</div>');
                }

                cell.toggleClass('nc-mixins-width--with-own-settings', has_own_settings);
                cell.toggleClass('nc-mixins-width--with-inherited-settings', has_inherited_preset_settings);

                if (has_own_settings || has_inherited_preset_settings) {
                    last_stop = i;
                    previous_existing_breakpoint = breakpoint;
                }

            });
        },

        /**
         * Возвращает «координаты» выбранной ячейки маркера ширины/миксина в виде объекта {row,cell}
         * (или null, когда ячейка не выбрана)
         * @returns {null|Object}
         */
        get_active_cell_position: function() {
            var marker = this.container.find('.nc-mixins-settings-marker.nc--active');
            if (!marker.length) {
                return null;
            }
            var cell = marker.closest('.nc-mixins-width'),
                row = cell.closest('.nc-mixins-mixin-row'),
                tbody = row.closest('tbody');
            return {
                row: tbody.children().index(row),
                cell: row.children().index(cell)
            };
        },

        /**
         * Активирует ячейку настроек с заданными «координатами» (см. get_active_cell_position()).
         * @param {null|Object} position
         */
        restore_active_cell_position: function(position) {
            if (!position) {
                return;
            }
            this.container.find('.nc-mixins-table tbody')
                .children().eq(position.row)
                .children().eq(position.cell)
                .find('.nc-mixins-settings-marker, .nc-mixins-add-setting').first().click();
        },

        /**
         * Возвращает выбранный в редакторе селектор
         * @returns {string}
         */
        get_current_selector: function() {
            return this.find('.nc-mixins-selector-select').val();
        },

        /**
         * Действия после изменения select’а с селектором
         */
        on_selector_select_change: function() {
            var select = this.find('.nc-mixins-selector-select');
            if (select.val() === '+') {
                this.show_add_selector_dialog(); // может добавить и выбрать новый селектор
            } else {
                var active_cell_position = this.get_active_cell_position();
                this.rebuild_table();
                this.restore_active_cell_position(active_cell_position);
            }
        },

        /**
         * Показывает диалог добавления селектора
         */
        show_add_selector_dialog: function() {
            // @todo proper dialog
            var selector = prompt(this.lang.SELECTOR_ADD_PROMPT);
            if (selector && !(selector in this.selectors)) {
                this.selectors[selector] = selector;
            }
            this.update_selectors_select(selector || '');
        },

        /**
         * Возвращает значение настроек указанного типа (собственных или наследованных)
         * для указанного типа миксинов и брейкпоинта)
         * @param from
         * @param mixin_type
         * @param breakpoint
         * @param {undefined|string} [selector] по умолчанию — выбранный в редакторе
         * @returns {*}
         */
        get_settings: function(from, mixin_type, breakpoint, selector) {
            var value;
            (value = from[selector === undefined ? this.get_current_selector() : selector]) &&
            (value = value[mixin_type]) &&
            (value = value[breakpoint]);
            return value;
        },

        /**
         * Удаляет настройки для указанного типа миксинов и брейкпоинта
         * @param from
         * @param mixin_type
         * @param breakpoint
         */
        remove_settings: function(from, mixin_type, breakpoint) {
            var selector = this.get_current_selector();
            if (from[selector] && from[selector][mixin_type]) {
                delete from[selector][mixin_type][breakpoint];
                if ($.isEmptyObject(from[selector][mixin_type])) {
                    delete from[selector][mixin_type];
                }
            }
        },

        /**
         * Возвращает строку с описанием ширины диапазона
         * @param from
         * @param to
         * @returns {string}
         */
        make_range_description: function(from, to) {
            if (Object.keys(this.breakpoints).length < 2) {
                return '';
            }
            var type = this.breakpoint_type,
                key = 'FOR_' + (type ? type.toUpperCase() + '_' : '') + 'WIDTH_';
            if (from == 0) {
                key += to == this.MAX_WIDTH ? 'ANY' : 'TO';
            } else if (to == this.MAX_WIDTH) {
                key += 'FROM';
            } else {
                key += 'RANGE';
            }
            return replace_from_to(this.lang[key] || '', from, to - 1);
        },

        /**
         * Показывает или прячет блок выбора типа брейкпоинта в зависимости от
         * опции show_breakpoint_type_select
         */
        init_breakpoint_type_container: function() {
            var breakpoint_type_container = this.find('.nc-mixins-breakpoint-type-container');
            if (this.show_breakpoint_type_select) {
                breakpoint_type_container.show();
            } else {
                breakpoint_type_container
                    .hide()
                    .find('select').attr('name', ''); // чтобы не отправлялись ненужные/некорректные данные
            }
        },

        /**
         * Устанавливает значение this.breakpoint_type из соответствующего селекта (если он есть)
         */
        set_breakpoint_type_from_select: function() {
            this.breakpoint_type = this.find('.nc-mixins-breakpoint-type-select').val() || '';
        },

        /**
         * Обновляет подсказки ширины после изменения селекта типа брейкпоинтов;
         * инициирует событие 'breakpoint_type_change'
         */
        on_breakpoint_type_select_change: function() {
            var editor = this;
            editor.set_breakpoint_type_from_select();
            editor.find('.nc-mixins-mixin-row').each(function() {
                var row = $(this),
                    active_cell_position = editor.get_active_cell_position();
                editor.update_row_markers(row); // пересборка маркеров — другие подсказки диапазонов
                editor.restore_active_cell_position(active_cell_position);
            });
            this.container.trigger(nc_mixin_settings_editor.breakpoint_type_change_event, [{ editor: this }]);
        },

        /**
         * Выполняет действия после изменения select’а выбора пресета
         */
        on_preset_select_change: function() {
            if (this.find('.nc-mixins-preset-select').val() === '+') {
                this.open_preset_edit_dialog();
            } else {
                this.apply_preset_settings();
            }
        },

        /**
         * Открывает диалог редактирования или создания пресета
         */
        open_preset_edit_dialog: function() {
            var preset_select_option = this.get_selected_preset_option(),
                preset_id = preset_select_option.data('id'),
                create_new_preset = preset_select_option.val() === '+',
                options = {
                    component_template_id: this.component_template_id,
                    'data[Scope]': this.scope
                };

            if (preset_id === '0') {
                return;
            }

            if (create_new_preset) {
                options['data[Mixin_Settings]'] = this.find('.nc-mixins-json').val();
            } else {
                options['mixin_preset_id'] = preset_id;
            }

            var opener_editor = this;
            nc.load_dialog(this.container.data('preset-edit-dialog-url'), options, 'POST')
              .set_option('on_submit_response', function(response) {
                  // this = диалог редактирования пресета
                  // response = ID пресета
                  var dialog = this, // PhpStorm без этого «думает», что this используется неправильно
                      preset_id = parseInt(response, 10),
                      preset_name = dialog.find('input[name="data[Mixin_Preset_Name]"]').val(),
                      preset_settings = dialog.find('.nc-mixins-json').val(),
                      set_as_default = dialog.find(':checkbox[name="set_as_default"]').is(':checked');
                  if (preset_id) {
                      opener_editor.update_preset(preset_id, preset_name, preset_settings, set_as_default);
                  }
                  dialog.destroy();
              });

            if (create_new_preset) {
                this.find('.nc-mixins-preset-select').val('-1').change();
            }
        },

        /**
         * Открывает диалог удаления пресета
         */
        open_preset_delete_dialog: function() {
            var options = {
                    mixin_preset_id: this.get_selected_preset_option().data('id')
                };

            var opener_editor = this;
            nc.load_dialog(this.container.data('preset-delete-dialog-url'), options, 'POST')
              .set_option('on_submit_response', function(response) {
                  // this = диалог редактирования пресета
                  // response = ID пресета
                  var preset_id = parseInt(response, 10);
                  if (preset_id) {
                      opener_editor.delete_preset(preset_id);
                  }
                  this.destroy();
              });
        },

        /**
         * Инициализирует и показывает блок с выбором пресета, либо прячет его,
         * в зависимости от опций show_preset_select и
         */
        init_preset_container: function() {
            var preset_container = this.find('.nc-mixins-preset-container');
            if (this.show_preset_select) {
                this.init_preset_select(preset_container); // заполняем select с пресетами из options.mixin_presets
                preset_container.show();
            } else {
                preset_container
                    .hide()
                    .find('select').attr('name', ''); // чтобы не отправлялись ненужные/некорректные данные
            }
        },

        /**
         * Устанавливает опции в селекте пресетов из this.mixin_presets
         */
        init_preset_select: function(preset_container) {
            var select = preset_container.find('.nc-mixins-preset-select');
            select.find('option').remove();
            if (this.mixin_presets.length) {
                $.each(this.mixin_presets, function(i, preset) {
                   $('<option>')
                       .attr({
                           value: preset.value,
                           selected: preset.selected,
                           'data-id': preset.id, // отличается от value для опции «использовать пресет по умолчанию»
                           'data-settings': preset.settings // не через data(), т. к. это JSON-строка
                       })
                       .html(preset.name)
                       .appendTo(select);
                });
            } else {
                select.append('<option>');
            }
            select.append('<option value="+">' + this.lang.PRESET_CREATE);
        },

        /**
         * Обновляет настройки пресета (после изменения в диалоге редактирования пресета)
         * @param preset_id
         * @param preset_name
         * @param preset_settings
         * @param set_as_default
         */
        update_preset: function(preset_id, preset_name, preset_settings, set_as_default) {
            var select = this.find('.nc-mixins-preset-select'),
                options_to_update = select.find('option[data-id="' + preset_id + '"]'),
                default_option = select.find('option[value="-1"]'),
                was_default = default_option.data('id') == preset_id;

            if (!options_to_update.length) {
                options_to_update = $('<option>').val(preset_id).insertBefore(select.find('option:last'));
                select.val(set_as_default ? '-1' : preset_id);
            }

            if (set_as_default) {
                options_to_update = options_to_update.add(default_option);
            }

            options_to_update
                .html(preset_name)
                .attr({'data-id': preset_id, 'data-settings': preset_settings})  // важно, чтобы был атрибут data-id в разметке
                .data({id: preset_id, 'settings': JSON.parse(preset_settings)}); // preset_settings — это JSON-строка

            if (set_as_default) {
                default_option.html(this.lang.PRESET_DEFAULT.replace('%s', preset_name));
            } else if (was_default) {
                this.remove_default_preset(preset_id);
            }

            select.change();
        },

        /**
         * Выполняет действия, необходимые после удаления пресета
         * @param preset_id
         */
        delete_preset: function(preset_id) {
            var select = this.find('.nc-mixins-preset-select');

            select.find('option[value="' + preset_id + '"]').remove();
            this.remove_default_preset(preset_id);

            select.val('-1').change();
        },

        /**
         * Сбрасывает дефолтный пресет в селекте пресетов в «нет»
         * @param preset_id
         */
        remove_default_preset: function(preset_id) {
            this.find('.nc-mixins-preset-select')
                .find('option[value="-1"][data-id="' + preset_id + '"]')
                .attr({'data-id': '0', 'data-settings': ''})
                .data({id: 0, 'settings': {}})
                .html(this.lang.PRESET_DEFAULT_NONE);
        },

        /**
         * Возвращает выбранную в select’е пресетов option
         * @returns {*|jQuery}
         */
        get_selected_preset_option: function() {
            return this.find('.nc-mixins-preset-select option:selected');
        },

        /**
         * Обновляет редактор после выбора пресета
         */
        apply_preset_settings: function() {
            if (this.show_preset_select) {
                var selected_preset_option = this.get_selected_preset_option();
                this.set_inherited_settings(selected_preset_option.data('settings') || {});
                this.rebuild_table();
                this.find('.nc-mixins-preset-actions').toggle(!!selected_preset_option.data('id'));
                this.update_mixin_json();
            }
        },

        /**
         * Прячет настройки для типа миксинов (например, при переходе к редактированию другого типа
         * миксинов)
         */
        close_opened_mixin_settings: function() {
            var editor = this;
            this.find('.nc-mixins-settings-cell.nc--active').removeClass('nc--active').each(function() {
                // если это был новый диапазон, но после добавления в нём ничего не изменено — удаляем его
                var previous_settings_cell = $(this);
                if (previous_settings_cell.is('.nc-mixins-settings-cell--temporary')) {
                    var previous_settings_row = previous_settings_cell.closest('tr');
                    editor.remove_settings(editor.temporary_own_settings, previous_settings_row.data('mixinType'), previous_settings_cell.data('breakpoint'));
                    editor.update_row_markers(previous_settings_row);
                }
            });
        },

        /**
         * Раскрывает настройки в строке типа миксина
         * @param click_event
         */
        show_mixin_settings: function(click_event) {
            var button = $(click_event.target),
                range_cell = button.closest('td'),
                row = range_cell.closest('tr'),
                settings_cell = row.find('.nc-mixins-settings-cell'),
                mixin_type = row.data('mixinType'),
                breakpoint = range_cell.data('breakpoint'),
                mixin_select = settings_cell.find('.nc-mixins-mixin-select');

            // клик на уже выбранном диапазоне — ничего не делаем
            if (button.is('.nc--active')) {
                return;
            }

            // если была открыта другая ячейка с настройками — прячем её
            this.close_opened_mixin_settings();

            // текстовое описание диапазона, для которого действуют настройки
            settings_cell.find('.nc-mixins-mixin-range-description').html(range_cell.data('range'));
            settings_cell.data('breakpoint', breakpoint);

            // раскрываем ячейку с настройками
            settings_cell.addClass('nc--active');

            // устанавливаем настройки
            var settings = this.get_mixin_settings_values(mixin_type, breakpoint),
                mixin_keyword = settings.mixin,
                settings_container = settings_cell.find('.nc-mixins-mixin-settings[data-mixin-keyword="' + mixin_keyword + '"]');
            mixin_select.val(mixin_keyword || '');
            this.on_mixin_select_change(mixin_select, true); // не mixin_select.change() — передаём доп. параметр
            this.set_mixin_input_values(settings_container, settings, true);

            // помечаем, что это новое правило (если ничего не поменяется, удалим его при потере фокуса)
            settings_cell.toggleClass('nc-mixins-settings-cell--temporary', !button.is('.nc-mixins-own-settings-marker'));

            // если это новое правило, обновляем маркеры (добавится маркер собственных настроек)
            if (button.is('.nc-mixins-add-setting')) {
                this.update_current_mixin_settings(true);
            }

            // убираем подсветку предыдущей выбранной ячейки диапазона ширины
            this.find('.nc-mixins-settings-marker').removeClass('nc--active');
            // подсветка ячейки выбранного диапазона
            range_cell.find('.nc-mixins-settings-marker').addClass('nc--active');
        },

        /**
         * Устанавливает значения полей в блоке типа миксина из настроек
         * @param mixin_settings_container
         * @param settings
         * @param trigger_events
         */
        set_mixin_input_values: function(mixin_settings_container, settings, trigger_events) {
            mixin_settings_container.find('.nc-mixins-mixin-settings-rows').children().remove();
            // для редактора всех свойств «с замком» нужно установить значение lock_sides до остальных значений
            for (var key in settings) {
                if (/^settings\.(.+\.)?lock_sides$/.test(key)) {
                    var lock = {};
                    lock[key] = settings[key];
                    settings = $.extend(lock, settings);
                }
            }
            for (var key in settings) {
                var multiple_settings_index = (key.match(/\.(\d+)\./) || [])[1],
                    input_container = (multiple_settings_index !== undefined) // множественные настройки?
                        ? this.get_mixin_multiple_settings_container(mixin_settings_container, multiple_settings_index)
                        : mixin_settings_container,
                    // в форме все инпуты вместо порядковых номеров имеют '#' в названии:
                    input_name = 'mixin.' + key.replace('.' + multiple_settings_index + '.', '.#.'),
                    input = input_container.find('[name="' + input_name + '"]');

                if (input.is(':checkbox')) {
                    input.prop('checked', !!settings[key]);
                } else if (input.is(':radio')) {
                    input.filter('[value="' + settings[key].replace('"', '\\"') + '"]').prop('checked', true);
                } else if (input.is('[data-color-input]')) {
                    input.attr('data-sync-color', settings[key]);
                    this.set_color_input(input, settings[key]);
                } else {
                    input.val(settings[key]);
                }
                if (trigger_events) {
                    input.change();
                }
            }
            mixin_settings_container.trigger(nc_mixin_settings_editor.mixin_settings_set_values_event, [{ editor: this }]);
        },

        /**
         * Возвращает блок с множественными настройками (div.nc-mixins-mixin-settings-row) по номеру
         * (создаёт его при необходимости)
         * @param mixin_settings_container
         * @param {Number|String} index
         * @returns {*}
         */
        get_mixin_multiple_settings_container: function(mixin_settings_container, index) {
            index = parseInt(index, 10);
            var rows_container = mixin_settings_container.find('.nc-mixins-mixin-settings-rows'),
                rows = rows_container.find('.nc-mixins-mixin-settings-row'),
                existing_number_of_rows = rows.length,
                template = mixin_settings_container.find('.nc-mixins-mixin-settings-row-template');

            if (existing_number_of_rows > index) { // уже есть нужный блок
                return rows.eq(index);
            }

            if (!rows_container.length || !template.length) { // не хватает нужных блоков в разметке, мы тут бессильны
                return mixin_settings_container;
            }

            var last = mixin_settings_container;
            while (existing_number_of_rows++ < index + 1) {
                last = template.clone().removeClass('nc-mixins-mixin-settings-row-template').appendTo(rows_container);

                // инициализация полей выбора цвета
                this.init_color_inputs(last.find('[data-color-input]'));

                // инициализация полей выбора цвета
                this.init_uploader(last.find('[data-uploader]'));
            }

            // инициализация перетаскивания
            if (template.find('.nc-mixins-mixin-settings-row-move').length > 0) {
                rows_container.sortable({
                    handle: '.nc-mixins-mixin-settings-row-move',
                    containment: 'parent',
                    update: $.proxy(this, 'update_current_mixin_settings')
                });
            }

            return last;
        },

        /**
         * Обработка нажатия кнопки добавления множественной настройки
         * @param event
         */
        on_mixin_multiple_settings_add_button_click: function(event) {
            var button = $(event.target),
                mixin_settings_container = button.closest('.nc-mixins-mixin-settings'),
                new_index = mixin_settings_container.find('.nc-mixins-mixin-settings-rows .nc-mixins-mixin-settings-row').length;

            var new_row = this.get_mixin_multiple_settings_container(mixin_settings_container, new_index);
            mixin_settings_container.trigger(
                nc_mixin_settings_editor.mixin_settings_new_row_event,
                [{ editor: this, row: new_row }]
            );

            this.update_current_mixin_settings();
            return false;
        },

        /**
         * Обработка нажатия кнопки удаления множественной настройки
         * @param event
         */
        on_mixin_multiple_settings_remove_button_click: function(event) {
            $(event.target).closest('.nc-mixins-mixin-settings-row').remove();
            this.update_current_mixin_settings();
            return false;
        },

        /**
         * Возвращает значения полей для указанного типа миксинов и брейкпоинта
         * @param mixin_type
         * @param breakpoint
         * @returns {*}
         */
        get_mixin_settings_values: function(mixin_type, breakpoint) {
            var settings =
                this.get_settings(this.own_settings, mixin_type, breakpoint) ||
                this.get_settings(this.inherited_settings, mixin_type, breakpoint);

            // если настроек нет — для заполнения формы берём значения следующего брейкпоинта
            if (!settings) {
                var all_breakpoints = Object.keys(this.breakpoints).map(Number),
                    breakpoint_index = all_breakpoints.indexOf(breakpoint);
                if (breakpoint_index < all_breakpoints.length - 1) {
                    settings = this.get_mixin_settings_values(mixin_type, all_breakpoints[breakpoint_index + 1]);
                }
            }

            return flatten(settings || {});
        },

        /**
         * Выполняет действия после выбора миксина
         * @param mixin_select
         * @param is_initializing_mixin_settings_container
         */
        on_mixin_select_change: function(mixin_select, is_initializing_mixin_settings_container) {
            var settings_cell = mixin_select.closest('.nc-mixins-settings-cell');
            settings_cell.find('.nc-mixins-mixin-settings')
                .hide()
                .filter('[data-mixin-keyword="' + mixin_select.val() + '"]')
                .show()
                .trigger(nc_mixin_settings_editor.mixin_settings_set_values_event, [{ editor: this }]);

            if (!is_initializing_mixin_settings_container) {
                settings_cell.removeClass('nc-mixins-settings-cell--temporary');
                this.update_current_mixin_settings();
            }
        },

        /**
         * Выполняет действия после изменений настроек миксина
         * @param is_new_setting
         */
        update_current_mixin_settings: function(is_new_setting) {
            var editor = this;

            editor.find('.nc-mixins-settings-cell.nc--active').each(function() {
                var active_cell = $(this),
                    selector = editor.get_current_selector(),
                    row = active_cell.closest('.nc-mixins-mixin-row'),
                    mixin_type = row.data('mixinType'),
                    mixin_keyword = active_cell.find('.nc-mixins-mixin-select').val(),
                    breakpoint = active_cell.data('breakpoint'),
                    update_markers = false,
                    // для новых диапазонов до изменения настройки считаются «временными» (не сохраняются)
                    // (первый аргумент также может быть информацией о событии)
                    settings_type = 'own_settings';

                if (is_new_setting === true) { // первым аргументом функции [is_new_setting] также может быть eventData
                    update_markers = true;
                    settings_type = 'temporary_own_settings';
                } else if (active_cell.hasClass('nc-mixins-settings-cell--temporary')) { // при любом изменении перестаём считать настройки «временными»
                    active_cell.removeClass('nc-mixins-settings-cell--temporary');
                    update_markers = true;
                }

                set_multilevel_object_value(editor[settings_type], [selector, mixin_type, breakpoint], { mixin: mixin_keyword });

                active_cell.find('.nc-mixins-mixin-settings[data-mixin-keyword="' + mixin_keyword + '"]').each(function() {
                    var settings = editor.get_mixin_input_values($(this));
                    $.extend(editor[settings_type][selector][mixin_type][breakpoint], unflatten(settings));
                });

                if (update_markers) {
                    // перерисовываем маркеры диапазонов (🡡 → ●)
                    editor.update_row_markers(row);
                    row.find('.nc-mixins-width[data-breakpoint="' + breakpoint + '"] .nc-mixins-settings-marker')
                        .addClass('nc--active');
                }
            });

            if (is_new_setting !== true) {
                this.update_mixin_json();
            }
        },

        /**
         * Обновляет значения скрытого поля с JSON с настройками всех миксинов
         */
        update_mixin_json: function() {
            if (!this.is_initialized) { // чтобы не обновлять значение без необходимости при инициализации
                return;
            }

            var saved_settings = $.extend({}, this.own_settings);
            for (var selector in saved_settings) {
                if ($.isEmptyObject(saved_settings[selector])) {
                    delete saved_settings[selector];
                } else {
                    saved_settings[selector] = this.sort_mixins(saved_settings[selector]);
                }
            }
            this.find('.nc-mixins-json').val(JSON.stringify(saved_settings));
        },

        /**
         * Выполняет действия при нажатии на кнопку удаления настроек для типа миксина
         * @param event
         */
        on_remove_settings_button_click: function(event) {
            var remove_button = $(event.target),
                settings_cell = remove_button.closest('.nc-mixins-settings-cell'),
                row = settings_cell.closest('tr'),
                mixin_type = row.data('mixinType'),
                breakpoint = settings_cell.data('breakpoint');
            this.remove_settings(this.temporary_own_settings, mixin_type, breakpoint);
            this.remove_settings(this.own_settings, mixin_type, breakpoint);
            this.update_row_markers(row);
            this.close_opened_mixin_settings();
            this.update_mixin_json();
        },

        /**
         * Инициализирует кнопку одновременного изменения настроек по сторонам top/left/right/bottom.
         * Настройки будут меняться в блоке .nc-mixins-mixin-settings-lock-sides (или, если его нет, то
         * во всём блоке настроек миксина), в котором расположена указанная кнопка.
         * @param lock_button
         */
        init_lock_sides_button: function(lock_button) {
            var container = lock_button.closest('.nc-mixins-mixin-settings-lock-sides, .nc-mixins-mixin-settings'),
                lock_input = container.find(':hidden[name^="mixin.settings"][name$=".lock_sides"]'),
                inputs = container.find(':input');

            lock_input.change(function() {
                lock_button
                    .removeClass('nc-icon-lock nc-icon-unlock')
                    .addClass(lock_input.val() ? 'nc-icon-lock' : 'nc-icon-unlock');
            }).change();

            lock_button.click(function() {
                lock_input.val(lock_input.val() ? '' : '1').change();
                inputs.filter('[name*="top"]').change();
            });

            var trbl = '(top|right|bottom|left)',
                trbl_regexp = new RegExp(trbl);

            inputs.on('change input blur', function() {
                if (lock_input.val() === '1') {
                    var attributes = this.attributes,
                        value = $(this).val(),
                        input_name_regexp = new RegExp(this.name.replace(/\./g, '\\.').replace(trbl_regexp, trbl)),
                        inputs_to_sync = inputs.filter(function() { return (this.name && this.name.match(input_name_regexp)); });
                    inputs_to_sync.val(value);
                    $.each(attributes, function(index, attribute){
                        if (!attribute.name.indexOf('data-sync-')) {
                            inputs_to_sync.attr(attribute.name, attribute.value);
                        }
                    });
                    inputs.filter('.minicolors-input').each(function() {
                        var input = $(this);
                        input.minicolors('value', input.val());
                    });
                }
            });
        },

        /**
         * Возвращает <options> с шрифтами
         * @returns {String}
         */
        get_font_options_html: function() {
            var editor = this;
            if (!font_select_options_cache) {
                $.each(editor.fonts, function(i, font) {
                    font_select_options_cache += "<option name='" + font.name +
                        "' data-asset='" + font.asset +
                        "' data-fallback='" + font.fallback +
                        "' style='font-family:&quot;" + font.name + "&quot;'>" +
                        font.name + "</option>";
                });
            }
            return font_select_options_cache;
        },

        /**
         * Добавляет опции для выбора шрифтов (список шрифтов передан в data-fonts
         * в шаблоне редактора)
         * @param select
         */
        init_font_select: function(select) {
            select.html(select.html() + this.get_font_options_html());
        },

        /**
         * Инициализирует библиотеку для выбора цвета на соответствующих полях
         * @param inputs
         */
        init_color_inputs: function(inputs) {
            var infoblock_id = this.infoblock_id ? this.infoblock_id : 0;
            var block = $('.tpl-block-' + infoblock_id + ', .tpl-container-' + infoblock_id);
            var minicolors_options = {
                format: 'rgb',
                keywords: 'transparent',
                opacity: true,
                swatches: [],
                theme: 'netcat'
            };
            var palette = [
                '--tpl-color-foreground-main',
                '--tpl-color-foreground-accent',
                '--tpl-color-brand',
                '--tpl-color-background-accent',
                '--tpl-color-background-main'
            ];
            var calculated_colors = {};

            if (infoblock_id > 0 && block.length > 0) {
                $.each(palette, function (index, color) {
                    var palette_color = getComputedStyle(block[0]).getPropertyValue(color);
                    if (palette_color) {
                        minicolors_options.swatches.push({
                            name: 'var(' + color + ')',
                            color: palette_color.trim()
                        });
                        calculated_colors['var(' + color + ')'] = palette_color.trim();
                    }
                });

                if (minicolors_options.swatches.length > 0) {
                    inputs.each(function () {
                        var input = $(this);
                        if (calculated_colors.hasOwnProperty(input.attr('data-sync-color'))) {
                            input.val(calculated_colors[input.attr('data-sync-color')]);
                        }
                    });
                }
            }

            this.calculated_colors = calculated_colors;

            inputs
                .minicolors('destroy')
                .minicolors(minicolors_options);

            if (typeof inputs.data('nc-swatches') !== "undefined") {
                $('.minicolors-swatch-color').on('click', function () {
                    var swatch = $(this);

                    if (!swatch.attr('title')) {
                        return true;
                    }

                    var input = swatch.closest('.minicolors-panel').siblings('.minicolors-input');
                    input.attr('data-sync-color', swatch.attr('title'));
                });

                $('.minicolors-panel *').on('click', function (event) {
                    var target = $(event.target);
                    if (target.is('.minicolors-swatches') || target.closest('.minicolors-swatches').length > 0) {
                        return true;
                    }

                    var input = $(this).closest('.minicolors-panel').siblings('.minicolors-input');
                    input.attr('data-sync-color', '').change();
                });
            }
        },

        set_color_input: function(input, value) {
            if (value.indexOf('var') === 0 && this.calculated_colors.hasOwnProperty(value)) {
                input.minicolors('value', { color: this.calculated_colors[value] });
            } else {
                input.minicolors('value', { color: value });
            }
        },

        /**
         * Добавляет в HEAD CSS-файлы шрифтов, указанных в data-fonts
         */
        load_fonts: function() {
            var head = $('head');
            this.fonts = this.container.data('fonts');
            $.each(this.fonts, function(i, font_data) {
                $.each(font_data.css, function(i, file) {
                    if (!$('link[href="' + file + '"]', head).length) {
                        head.append($('<link>', { rel: 'stylesheet', href: file }));
                    }
                });
            });
        },

        /**
         * Возвращает Element (не jQuery-коллекцию!), в котором находится инфоблок
         * (если инфоблок был указан в options.infoblock_id; если нет — то вернёт null)
         * Может использоваться в формах настроек миксинов.
         * @returns {Element|null}
         */
        get_infoblock_element: function() {
            var id = this.infoblock_id;
            return id ? document.querySelector('.tpl-block-' + id + ', .tpl-container-' + id) : null;
        },

        /**
         * Инициализирует загрузчик файлов в поле фона (изображение, видео, праллакс)
         * @param uploaders
         */
        init_uploader: function(uploaders) {
            var _this = this;

            uploaders.each(function() {
                var
                    uploader = $nc(this),
                    allowed_types = uploader.data('uploader-allowed'),
                    ext_filter = uploader.data('uploader-ext'),
                    url_field = uploader.find('input[type="url"]'),
                    file_field = uploader.find('input[type="file"]'),
                    btn = uploader.find('.js-mixin-upload');

                if (ext_filter) {
                    ext_filter = ext_filter.split(/\s*,\s*/);
                }

                btn.on('click', function() {
                    var
                        mixin_keyword = uploader.closest('.nc-mixins-mixin-settings').data('mixin-keyword'),
                        max_file_size = uploader.closest('.nc-mixins-editor').data('upload-max-filesize'),
                        dialog = nc.ui.modal_dialog.get_current_dialog();

                    uploader.dmUploader({
                        url: NETCAT_PATH + 'action.php?ctrl=admin.mixin_file&action=save&isNaked=1&admin_modal=1',
                        maxFileSize: max_file_size,
                        multiple: false,
                        allowedTypes: allowed_types,
                        extFilter: ext_filter,
                        dataType: 'json',
                        extraData: {
                            scope: _this.scope,
                            infoblock_id: _this.infoblock_id,
                            mixin_keyword: mixin_keyword
                        },
                        onDragEnter: function() {
                            this.addClass('active');
                        },
                        onDragLeave: function() {
                            this.removeClass('active');
                        },
                        onInit: function() {
                            file_field.trigger('click');
                        },
                        onComplete: function() {
                            uploader.dmUploader('destroy');
                        },
                        onBeforeUpload: function(id) {
                            url_field.addClass('nc--loading');
                        },
                        onUploadSuccess: function(id, data) {
                            url_field.removeClass('nc--loading').val(data.url).trigger('change');
                        },
                        onUploadError: function(id, xhr, status, message) {
                            uploader.dmUploader('destroy');
                        },
                        onFallbackMode: function() {
                            try {
                                dialog.show_error('Ваш браузер не поддерживает автоматическую загрузку файлов');
                            } catch(e) {
                                alert('Ваш браузер не поддерживает автоматическую загрузку файлов');
                            }
                            uploader.dmUploader('destroy');
                        },
                        onFileSizeError: function(file) {
                            try {
                                dialog.show_error('Файл \'' + file.name + '\' слишком большой');
                            } catch(e) {
                                alert('Файл \'' + file.name + '\' слишком большой');
                            }
                            uploader.dmUploader('destroy');
                        },
                        onFileTypeError: function(file) {
                            try {
                                dialog.show_error('Файл \'' + file.name + '\' не является файлом изображения');
                            } catch(e) {
                                alert('Файл \'' + file.name + '\' не является файлом изображения');
                            }
                            uploader.dmUploader('destroy');
                        },
                        onFileExtError: function(file) {
                            try {
                                dialog.show_error('Файл \'' + file.name + '\' имеет неверный формат');
                            } catch(e) {
                                alert('Файл \'' + file.name + '\' имеет неверный формат');
                            }
                            uploader.dmUploader('destroy');
                        }
                    });
                })
            })
        },
    };

})($nc);
