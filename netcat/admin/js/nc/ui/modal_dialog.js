/**
 * nc.ui.modal_dialog
 *
 * Класс для создания модальных диалогов.
 *
 * Содержимое диалога может быть загружено с сервера (если в конфигурации задан
 * параметр url, см. ниже) или указано при создании диалога.
 *
 * Использование:
 *    var dialog = nc.ui.modal_dialog(options);
 *    dialog.open();     // открывает диалог
 *    dialog.close();    // закрывает диалог; см. описание параметра options.persist
 *    dialog.destroy();  // полностью убирает диалог
 *
 * options: объект
 *    url: путь для загрузки содержимого диалога
 *    parameters: параметры, добавляемые к запросу для загрузки содержимого диалога
 *    method: метод загрузки диалога (если задан url) — 'GET' [по умолчанию] или 'POST'
 *    persist: если true, содержимое диалога не сбрасывается при повторном открытии;
 *             если false [по умолчанию], вызов close() вызывает убирает содержимое диалога из DOM
 *    confirm_close: если true [по умолчанию] и изменено значение элементов форм внутри диалога,
 *             перед закрытием будет выдан запрос на подтверждение закрытия диалога
 *    on_show: функция, которую следует выполнить, когда диалог будет открыт
 *    on_resize: функция, срабатывающая при изменении размеров диалога (при изменении размеров окна)
 *    on_submit_response: функция-обработчик получения ответа от сервера при отправке формы
 *             кнопкой button[data-action=submit].
 *             Будут переданы аргументы: response, status, event, form
 *             Если этот параметр задан, «стандартный» обработчик ответа (используется при
 *             сохранении большинства стандартных форм в системе) не будет вызван.
 *             this в обработчике соответствует объекту nc.ui.modal_dialog.
 *             (Для закрытия диалога используйте this.destroy())
 *    full_markup: полный код диалога (см. ниже). Не учитывается, если задан url.
 *    height: высота в пикселях или 'auto' для подбора по высоте изначального содержимого диалога
 *    width: ширина в пикселях
 *    min_width: минимально возможная ширина (если не задана фиксированная ширина, по умолчанию 600)
 *    max_width: максимально возможная ширина (если не задана фиксированная ширина, по умолчанию 1200)
 *    hidden_tabs: массив с id вкладок (data-tab-id), которые будут скрыты (например: tab-system)
 *    show_close_button: если true [по умолчанию], будет показана кнопка закрытия окна
 *    on_tab_change: объект, где ключом служит id вкладки (data-tab-id), а значением массив из callback-функций,
 *             которые будут выполнены при показе вкладки
 *
 *  Параметры width, height, hidden_tabs, confirm_close при наличии полной разметки
 *  могут быть заданы в виде атрибутов data- у div.nc-modal-dialog (см. пример ниже).
 *
 */

/**

    При загрузке диалога с сервера (параметр url) ответ должен иметь
    следующую структуру (аналогично для параметра full_markup):

     <div class="nc-modal-dialog"
         data-hidden-tabs="id_вкладок_которые_не_будут_показаны через_пробел например tab-system"
         data-width="нестандартная ширина"
         data-height="нестандартная высота"
         data-confirm-close="no">

         <!-- Если есть div.nc-modal-dialog, но нет div.nc-modal-dialog-header", заголовок не будет показан -->
         <div class="nc-modal-dialog-header">
             <h2>Title</h2>
             <!-- следующий div будет создан автоматически, если отсутствует в разметке: -->
             <div class="nc-modal-dialog-header-tabs">
                 <ul>
                     <li data-tab-id="tab1" class="nc--active">Tab 1</li>
                     <li data-tab-id="tab2">Tab 2</li>
                 </ul>
             </div>
         </div>
         <div class="nc-modal-dialog-body">
             <div data-tab-caption="Название Вкладки 1" data-tab-id="опциональный_id_вкладки">
                 (Если вкладок нет, то вложенный div не обязателен)
             </div>
         </div>
         <div class="nc-modal-dialog-footer">
             <div class="nc-modal-dialog-footer-text">Можно вывести дополнительный текст в футере</div>
             <button data-action="close">Закрыть</button>
             <button data-action="submit">Сохранить</button>
             <button data-action="save-draft">Сохранить черновик</button>
             <button class="nc-btn nc--red">Какая-то красная кнопка</button>
         </div>
         <script>
             you_can_add_additional_logic_here();
             var dialog = nc.ui.modal_dialog.get_current_dialog();
         </script>
     </div>

 */
(function() {

    // nc.ui общий между всеми экземплярами nc, поэтому надо
    // добавить «модуль» nc.ui.modal_dialog только один раз (иначе
    // window в modal_dialog будет ссылаться на окно, в котором
    // этот скрипт загружен последним)
    // [убрать следующую строку кода, если будут убраны фреймы]
    if (typeof nc === 'undefined' || nc !== nc.root) {
        return;
    }
    var $ = nc.root.$;

    // --- Constructor ---------------------------------------------------------
    /**
     *
     * @param {Object} options object
     * @returns {modal_dialog}
     */
    function modal_dialog(options) {
        // позволим выполнять конструктор как функцию без new:
        if (!(this instanceof modal_dialog)) {
            return new modal_dialog(options);
        }

        this.options = $.extend(true, {}, this.options);
        this.set_options(options);

        if (!this.options.url) {
            this.is_loaded = true;
        }

        return this;
    }

    // --- Initialize resize handlers ------------------------------------------
    $(window).on('resize.modal_dialog', function(){
        $('.simplemodal-container').each(function() {
            var instance = $(this).data('modal_dialog');
            if (instance && instance.resize) {
                instance.resize();
            }
        })
    });

    // --- Private variables ---------------------------------------------------
    var load_dialog_process_id = 'nc.ui.modal_dialog.load()',
        submit_form_process_id = 'nc.ui.modal_dialog.submit_form()';

    var current_dialog = null,
        // текущий «слой» модального диалога (число открытых диалогов), начиная с 1:
        current_dialog_index = 0;

    // --- Костыли над библиотекой SimpleModal ---------------------------------

    function get_stacked_container_id(index) {
        return 'simplemodal-container--' + (index || current_dialog_index);
    }

    function on_simplemodal_close() {
        // this === $.modal.impl
        var opened_dialog = modal_dialog.get_opened_dialog();
        if (opened_dialog === null) {
            return;
        }

        // --- clean up after simplemodal
        var persist = opened_dialog.options.persist,
            // fixme: не работает для нескольких persistent-диалогов, открытых поверх друг друга
            // (+ см. также close())
            ph = $('#simplemodal-placeholder');

        if (persist && this.d.placeholder) {
            ph.replaceWith(this.d.data.removeClass('simplemodal-data').css('display', this.display));
        } else {
            ph.remove();
            opened_dialog.dialog_container.remove();
        }

        $('#simplemodal-container, #simplemodal-overlay').remove();
        this.d = {};
        // ---

        // Если это был не единственный открытый диалог, восстанавливаем состояние
        // предыдущего диалога
        current_dialog = modal_dialog.get_previous_dialog();
        if (current_dialog) {
            current_dialog_index = current_dialog.index;

            var previous_container = current_dialog.dialog_container.closest('.simplemodal-container')
                    .attr('id', 'simplemodal-container');

            this.d = previous_container.data('modal_d');
            this.o = previous_container.data('modal_o');
            this.occb = false;
            this.unbindEvents();
            this.bindEvents();

            $('.simplemodal-overlay').last().attr('id', 'simplemodal-overlay');
        } else {
            current_dialog_index = 0;
            destruct_autosave();
        }
    }

    // SimpleModal поддерживает только один открытый диалог. Чтобы показать модальный диалог
    // поверх другого модального диалога, запоминаем состояние текущего диалога и делаем
    // для simplemodal видимость, что диалог не открыт
    function stash_simplemodal() {
        $('#simplemodal-container')
            .attr('id', get_stacked_container_id())
            .data({
                modal_d: $.modal.impl.d, // содержит составные части диалога simplemodal
                modal_o: $.modal.impl.o  // содержит настройки simplemodal
            });

        $('#simplemodal-overlay').attr('id', ''); // поверх будет добавлен ещё один оверлей с таким id

        $.modal.impl.d = {};
    }

    // --- Закрытие диалога по нажатию Escape ----------------------------------

    var wait_for_escape_event = 'keydown.modal_dialog';

    function close_modal_on_escape(event) {
        if (event.keyCode === 27 /* Esc */) {
            // blur input to trigger change() if needed
            var target = $(event.target);
            if (target.is(':input')) {
                target.blur();
            }
            modal_dialog.get_opened_dialog().close();
        }
    }

    function add_close_on_escape_handler() {
        $(window).off(wait_for_escape_event).on(wait_for_escape_event, close_modal_on_escape);
    }

    function remove_close_on_escape_handler() {
        $(window).off(wait_for_escape_event);
    }

    // --- Сохранение черновиков -----------------------------------------------

    function is_autosave_applicable(dialog) {
        if (!window.nc_autosave_use || !window.InitAutosave) {
            return false;
        }
        // грубая проверка на то, это диалог редактирования записи или нет...
        // (по наличию обязательных полей для правильной работы черновиков)
        var form = dialog.get_form();
        return (
            form.attr('id') === 'adminForm' &&
            // вообще-то для нового объекта (без идентификатора) черновик сохраняется, но восстановить его нельзя (?):
            !!form.find(':hidden[name="message"]').val() &&
            !!form.find(':hidden[name="cc"]').val()
        );
    }

    function init_autosave(dialog) {
        if (is_autosave_applicable(dialog)) {
            InitAutosave('adminForm');
        }
    }

    function destruct_autosave() {
        // Для правильной работы с несколькими одновременно открытыми диалогами
        // редактирования объектов понадобится дополнительная логика.
        // Сейчас гарантированно работает только с одним такими открытым диалогом.
        if (window.autosave) {
            autosave.stopTimer();
            autosave = null;
        }
    }

    // --- Вспомогательные функции ---------------------------------------------

    function tab_selector(tab_id) {
        return '[data-tab-id="' + tab_id + '"]';
    }

    // Добавляет параметр к пути, если он не задан
    function add_param_to_url(url, param, value) {
        if (url) {
            var re = new RegExp('[?&]' + param + '=');
            if (!re.test(url)) {
                url += (url.indexOf('?') >= 0 ? '&' : '?') + param + '=' + value;
            }
        }
        return url;
    }

    function string_to_boolean(value) {
        if (typeof value !== 'boolean') {
            return !(/^(no|false|0)$/i.test(value.toString()));
        }
        return value;
    }

    // --- "Static" methods ----------------------------------------------------
    modal_dialog.get_opened_dialog = function() {
        return current_dialog && current_dialog.is_open ? current_dialog : null;
    };

    modal_dialog.get_current_dialog = function() {
        return current_dialog;
    };

    modal_dialog.get_previous_dialog = function() {
        var index = current_dialog_index;
        while (--index > 0) { // могут быт пропуски в id, если был закрыт неверхний диалог
            var dialog = $('#' + get_stacked_container_id(index)).data('modal_dialog');
            if (dialog) {
                return dialog;
            }
        }
        return null;
    };

    // -------------------------------------------------------------------------
    // --- Instance methods ----------------------------------------------------
    // -------------------------------------------------------------------------
    modal_dialog.prototype = {
        constructor: modal_dialog,
        options: {
            focus: true,
            url: null,
            parameters: null,
            persist: false,
            confirm_close: true,
            on_show: $.noop,
            on_resize: $.noop,
            on_submit_response: null, // по умолчанию будет добавлена стандартная обработка результата отправки формы
            width: null,
            height: null,
            min_width: 600,
            max_width: 1200,
            full_markup:
                '<div class="nc-modal-dialog">' +
                    '<div class="nc-modal-dialog-header">' +
                        '<h2>&nbsp;</h2>' +
//                        '<div class="nc-modal-dialog-header-tabs"><ul><li></li></ul></div>' +
                    '</div>' +
                    '<div class="nc-modal-dialog-body"></div>' +
                    '<div class="nc-modal-dialog-footer"></div>' +
                '</div>',
            hidden_tabs: null,
            show_close_button: true,
            on_tab_change: {}
        },

        loaded_markup: null,

        dialog_container: null,
        parts: {
            header:      '.nc-modal-dialog-header',
            title:       '.nc-modal-dialog-header h2',
            header_tabs: '.nc-modal-dialog-header-tabs',
            body:        '.nc-modal-dialog-body',
            body_tabs:   '.nc-modal-dialog-body-tab',
            footer:      '.nc-modal-dialog-footer'
        },

        is_loaded: false,
        is_open: false,
        are_tabs_initialized: false,
        has_header: true,

        has_unsaved_changes: false, // значения элементов форм внутри диалога были изменены (если включена опция confirm_close)

        index: 0,

        /**
         * Установить параметры диалога
         * @param {Object} options
         */
        set_options: function(options) {
            this.options = $.extend(this.options, options);
        },

        /**
         * Установить один параметр диалога
         */
        set_option: function(option, value) {
            this.options[option] = value;
        },

        /**
         * Получить значение параметра диалога
         * @param option
         */
        get_option: function(option) {
            return this.options[option];
        },

        set_on_tab_change: function(tab_id, callback) {
            if (typeof callback === 'function') {
                if (this.options['on_tab_change'].hasOwnProperty(tab_id)) {
                    this.options['on_tab_change'][tab_id].push(callback);
                } else {
                    this.options['on_tab_change'][tab_id] = [callback];
                }
            }
        },

        /**
         * Загрузить содержимое диалога. Возвращает Deferred.
         */
        load: function() {
            var dialog = this;
            nc.process_start(load_dialog_process_id);
            return $.ajax({
                        method: this.options.method || 'GET',
                        url: this.options.url,
                        data: $.extend({ isNaked: 1 }, this.options.parameters || {})
                    })
                    .done(function(result) {
                        dialog.loaded_markup = $.trim(result);
                    })
                    .always(function() {
                        dialog.is_loaded = true;
                        nc.process_stop(load_dialog_process_id, 0);
                    });
        },

        /**
         * Создать диалог (загрузка при необходимости, инициализация элементов),
         * без его отображения. Возвращает Deferred.
         */
        create: function() {
            if (!this.is_loaded) {
                return this.load().done($.proxy(this, 'create')); // call create() again when loaded
            }

            var options = this.options,
                dialog;

            if (this.loaded_markup) {
                var loaded_markup = $(this.loaded_markup);
                if (loaded_markup.is('.nc-modal-dialog')) {
                    // ответ по крайней мере отдалённо похож на полный диалог
                    dialog = loaded_markup;
                } else {
                    var loaded_markup_dialog = loaded_markup.find('.nc-modal-dialog');
                    // тело диалога обернуто в какие-то лишние теги, игнорируем их
                    if (loaded_markup_dialog.length) {
                        dialog = loaded_markup_dialog;
                    }
                }
            }

            if (!dialog) {
                // нет полной разметки диалога, за основу берём стандартную разметку из options.full_markup
                dialog = $(options.full_markup);
                if (this.loaded_markup) {
                    // Мы что-то загрузили, но это не полный диалог. Добавим ответ в тело диалога:
                    dialog.find('.nc-modal-dialog-body').append(this.loaded_markup);
                }
            }

            var scripts = dialog.find('script').remove();

            current_dialog = this;

            this.dialog_container = dialog.hide().appendTo('body');

            if (!this.get_part('header').length) {
                this.dialog_container.addClass('nc-modal-dialog-without-header');
                this.has_header = false;
            }

            this.init_options();
            this.init_close_button();
            this.init_tabs();
            this.init_forms();
            this.init_buttons();

            dialog.append(scripts);

            return $.Deferred().resolve();
        },

        /**
         * (Private)
         * Перенос параметров из атрибутов data- в this.options
         */
        init_options: function() {
            var options = this.options,
                dialog_container = this.dialog_container;

            // перенос параметров из атрибутов data- в this.options
            for (var o in options) {
                var data_value = dialog_container.data(o.replace(/_/g, '-'));
                if (data_value !== undefined && data_value.toString().length) {
                    options[o] = data_value;
                }
            }

            // преобразование this.options.hidden_tabs в массив
            var hidden_tabs_value = this.options.hidden_tabs;
            if (hidden_tabs_value === null) {
                options.hidden_tabs = [];
            } else if (!$.isArray(hidden_tabs_value)) {
                options.hidden_tabs = [];
                $.each(hidden_tabs_value.match(/[\w-]+/g), function(i, tabId) {
                    options.hidden_tabs.push(tabId);
                });
            }

            // значение confirm_close, show_close_button и т. п.:
            // допускаются значения "no", "false", "0"
            for (var property in {confirm_close: 1, show_close_button: 1}) {
                options[property] = string_to_boolean(options[property]);
            }
        },

        /**
         * (Private)
         * Добавление кнопки закрытия диалога Ⓧ
         */
        init_close_button: function() {
            if (this.options.show_close_button) {
                $('<a class="nc-modal-dialog-header-close-button" title="' + ncLang.Close + '" />')
                    .click($.proxy(this, 'close'))
                    .appendTo(this.get_part('header'));
            }
        },

        /**
         * (Private)
         * Инициализация «стандартных» кнопок
         */
        init_buttons: function() {
            var dialog = this,
                click_event = 'click.modal_dialog',
                footer_buttons = this.get_part('footer').find('button')
                                     .off(click_event);

            // "Close Dialog" button
            footer_buttons.filter('[data-action=close]')
                .on(click_event, $.proxy(this, 'close'));

            // "Submit Dialog Body Form" button
            footer_buttons.filter('[data-action=submit]')
                .on(click_event, function() {
                    if ($(this).hasClass('nc--loading')) {
                        return;
                    }
                    nc.process_start(submit_form_process_id, this); // will add nc--loading class to the button as well
                    dialog.submit_form();
                });

            // "Save Draft" button
            footer_buttons.filter('[data-action="save-draft"]')
                // вообще-то для нового объекта (без идентификатора) черновик сохраняется, но восстановить его нельзя (?):
                .toggle(is_autosave_applicable(this))
                .on(click_event, function(e) {
                    e.preventDefault();
                    if (window.autosave) {
                        $(this).addClass('nc--loading');
                        autosave.saveAllData(autosave);
                    }
                });
        },

        /**
         * (Private)
         * Инициализация вкладок
         */
        init_tabs: function() {
            if (this.are_tabs_initialized) {
                return;
            }
            this.are_tabs_initialized = true;

            var dialog = this,
                tab_divs = this.get_part('body').find('[data-tab-caption]');

            dialog.dialog_container.toggleClass('nc-modal-dialog-without-tabs', tab_divs.length === 0);

            if (!tab_divs.length) {
                return;
            }

            var header_tabs = this.get_part('header_tabs');

            if (!header_tabs.length) {
                header_tabs = $('<div class="nc-modal-dialog-header-tabs"/>')
                    .appendTo(this.get_part('header'));
            }

            var ul = header_tabs.children('ul'),
                tab_sequence_number = 1;

            if (!ul.length) {
                ul = $('<ul/>').appendTo(header_tabs);
            }

            tab_divs.addClass('nc-modal-dialog-body-tab').hide().each(function() {
                var tab_id = $(this).data('tab-id') || 'tab' + (tab_sequence_number++),
                    tab_div = $(this).attr('data-tab-id', tab_id);

                $('<li>', { 'data-tab-id': tab_id, html: tab_div.data('tab-caption') })
                    .appendTo(ul)
                    .click(function(e) { dialog.change_tab($(e.target).data('tab-id')); });
            });

            $.each(this.options.hidden_tabs, function(i, tab_id) {
                dialog.hide_tab(tab_id);
            });

            this.change_tab(tab_divs.eq(0).data('tab-id'));
        },

        /**
         * Возвращает #adminForm (если есть) или первую form
         */
        get_form: function() {
            var form = this.find('#adminForm');
            if (!form.length) {
                form = this.find('form').first();
            }
            return form;
        },

        /**
         * (Private)
         * Инициализация формы (ajaxForm)
         */
        init_forms: function() {
            InitTransliterate(this.get_part('body'));

            // Для IE нужно указывать javascript:false в iframeSrc при HTTPS-соединении, но такая запись нарушает спецификацию.
            // Более того, подобная запись приводит к отмене выполняемого запроса в браузере Chrome начиная с версии 83.
            // Поэтому в iframeSrc указываем javascript:false только для IE, а в остальных случаях about:blank, как и рекомендуется.
            var isMsie, iframeSrc;
            isMsie = /(MSIE|Trident)/.test(navigator.userAgent || '');
            iframeSrc = (isMsie && /^https/i.test(window.location.href || '')) ? 'javascript:false' : 'about:blank';

            var process_form_submit_response = $.proxy(this, 'process_form_submit_response');
            this.find('form').each(function() {
                var f = $(this);

                f.ajaxForm({
                    beforeSerialize: nc_save_editor_values,
                    success: process_form_submit_response,
                    iframe: true,
                    iframeSrc: iframeSrc
                });

                var action = f.attr('action');
                action = add_param_to_url(action, 'isNaked', 1);
                action = add_param_to_url(action, 'admin_modal', 1);
                f.attr('action', action);
            });
        },

        /**
         * (Private)
         * Включает отслеживание изменений значений элементов форм
         * (установка свойства this.has_unsaved_changes)
         */
        init_change_tracking: function() {
            if (this.options.confirm_close) {
                var dialog = this,
                    update_changes_flag = function() { dialog.has_unsaved_changes = true; };

                this.has_unsaved_changes = false;
                this.dialog_container
                    .one('change', ':input', update_changes_flag)
                    .one('input textinput', 'div[contenteditable]', update_changes_flag);
            }
        },

        /**
         * Отправка формы (через ajaxForm)
         * @param form  если не указано, то отправляется форма this.get_form()
         */
        submit_form: function(form) {
            this.has_unsaved_changes = false;
            form = form ? $(form) : this.get_form();
            form.submit();
        },

        /**
         * Обработка ответа на отправку формы через ajaxForm
         */
        process_form_submit_response: function(response, status, event, form) {
            nc.process_stop(submit_form_process_id);
            var handler = $.isFunction(this.options.on_submit_response)
                                ? this.options.on_submit_response
                                : this.default_form_submit_response_handler;
            return handler.call(this, response, status, event, form);
        },

        /**
         * «Стандартный» обработчик ответа после отправки формы
         */
        default_form_submit_response_handler: function(response, status, event, form) {
            var error = nc_check_error(response);
            if (error) {
                this.show_error(error);
                return;
            }

            var infoblock_id = form.find('input[name=cc]').val() || form.find('input[name=infoblock_id]').val(),
                loc = window.location,
                newUrlMatch = (/^NewHiddenURL=(.+?)$/m).exec(response), // в ответе есть строка "NewHiddenUrl=something"
                newUrl = newUrlMatch ? $.trim(newUrlMatch[1]) : null, // новый HiddenURL страницы
                newUrlHash = (/^SetLocationHash=(.*?)$/m).exec(response); // в ответе есть SetLocationPath=something

            if (newUrlHash) {
                loc.hash = newUrlHash[1];
            }

            if ((/^ReloadPage=1$/m).test(response)) { // в ответе есть строка "ReloadPage=1"
                // не режим "редактирование", изменился путь страницы
                if (newUrl && !(/\.php/.test(loc.pathname))) {
                    // сохранить имя страницы, если оно было (изменение свойств раздела со страницы объекта)
                    var pageNameMatch = /\/([^\/]+)$/.exec(loc.pathname);
                    if (pageNameMatch) {
                        newUrl += pageNameMatch[1];
                    }
                    loc.pathname = newUrl;
                } else {
                    loc.reload(true);
                }
            } else if ((/^ReloadPageIfActionIsFull=1$/m).test(response) && $nc('#nc_page').data('action') === 'full') {
                loc.reload(true);
            } else if (infoblock_id) {
                nc_update_admin_mode_infoblock(infoblock_id, $.proxy(this, 'destroy'));
            }
        },

        /**
         * Отображение ошибки в футере диалога
         */
        show_error: function(error) {
            var footer = this.get_part('footer'),
                err = $("<div class='nc-alert nc--red' />")
                        .append(
                            $("<div class='nc-alert-close nc-icon-s nc--remove'></div>").click(function() { err.remove(); })
                        )
                        .append("<i class='nc-icon-l nc--status-error'></i>")
                        .append(error)
                        .appendTo(footer);
        },

        /**
         * Переключение на вкладку с указанным data-tab-id
         */
        change_tab: function(tab_id) {
            var selector = tab_selector(tab_id);
            var on_tab_change = this.get_option('on_tab_change');

            this.get_part('header_tabs').find('li').removeClass('nc--active')
                .filter(selector).addClass('nc--active').show();
            this.get_part('body_tabs').hide()
                .filter(selector).show();

            if (on_tab_change.hasOwnProperty(tab_id)) {
                $.each(on_tab_change[tab_id], function(index, callback) {
                    callback();
                });
            }

            return this;
        },

        /**
         * Прячет вкладку и её ярлык с указанным data-tab-id
         */
        hide_tab: function(tab_id) {
            var selector = tab_selector(tab_id);
            this.get_part('header_tabs').find('li' + selector).hide();
            this.get_part('body_tabs').filter(selector).hide();
            return this;
        },

        /**
         * Показывает вкладку и её ярлык с указанным data-tab-id
         */
        show_tab: function(tab_id) {
            var selector = tab_selector(tab_id);
            this.get_part('header_tabs').find('li' + selector).show();
            this.get_part('body_tabs').filter(selector).show();
            return this;
        },

        /**
         * Получение содержимого вкладки
         */
        get_tab: function(tab_id) {
            return this.get_part('body').find(tab_selector(tab_id));
        },

        /**
         * Возвращает содержимое открытой вкладки или, если вкладок нет, всю основную часть диалога (body)
         */
        get_current_tab: function() {
            var tabs = this.get_part('body_tabs');
            return tabs.length ? tabs.filter(':visible') : this.get_part('body');
        },

        /**
         * Прячет строку с вкладками
         */
        hide_header_tabs: function() {
            this.dialog_container.addClass('nc-modal-dialog-without-tabs');
            return this;
        },

        /**
         * Показывает строку со вкладками
         */
        show_header_tabs: function() {
            this.dialog_container.removeClass('nc-modal-dialog-without-tabs');
            return this;
        },

        /**
         * Показ диалога
         */
        open: function() {
            if (current_dialog_index) {
                stash_simplemodal();
            }

            current_dialog_index++;
            this.index = current_dialog_index;

            if (!this.dialog_container) {
                this.create().done($.proxy(this, 'when_ready_to_open'));
            } else {
                this.when_ready_to_open();
            }
            return this;
        },

        /**
         * (Private)
         * Инициализация $.modal
         */
        when_ready_to_open: function() {
            $.modal(this.dialog_container, {
                onOpen: null,
                onShow: $.proxy(this, 'on_show'),
                onClose: on_simplemodal_close,
                autoPosition: false,
                persist: this.options.persist,
                closeHTML: '',
                zIndex: 10000 + 10 * current_dialog_index,
                focus: this.options.focus
            });
            $('#simplemodal-container').data('modal_dialog', this);
            add_close_on_escape_handler();
            this.is_open = true;
            this.init_change_tracking();
            this.resize();
        },

        /**
         * (Private)
         * Дополнительные обработчики событий при показе диалога
         */
        on_show: function() {
            this.options.on_show(this);
            init_autosave(this);
        },

        /**
         * Закрытие диалога
         */
        close: function() {
            if (this.is_open && this.is_close_confirmed()) {
                if (this.index === current_dialog_index) {
                    $.modal.close();
                } else {
                    // fixme: не учитывается persistent (+ см. также on_simplemodal_close())
                    var simplemodal_container = $('#' + get_stacked_container_id(this.index));
                    simplemodal_container.prev('.simplemodal-overlay').remove();
                    simplemodal_container.remove();
                }

                this.is_open = false;

                if (!this.options.persist) {
                    this.destroy(); // this.is_open должен быть false для предотвращения бесконечного цикла
                }

                if (this.index <= 1) {
                    remove_close_on_escape_handler();
                }
            }
        },

        /**
         * Запрос (при необходимости) подтверждения закрытия диалога
         * @returns {boolean}
         */
        is_close_confirmed: function() {
            if (!this.options.confirm_close || !this.has_unsaved_changes) {
                return true;
            }

            return confirm(window.TEXT_SAVE || 'Выйти без сохранения?');
        },

        /**
         * Обработчик для изменения размера диалога (например, при по событию
         * window.resize)
         */
        resize: function() {
            if (!this.is_open) {
                return this;
            }

            var $window = $(window.top.window),
                o = this.options,
                window_width = $window.width(),
                // $(window).height() неправильно определяет высоту окна в quirks mode
                window_height =  window.innerHeight || window.clientHeight || $window.height(),
                max_dialog_height = (window_height - 100 * 2),
                dialog_width  = o.width  || (window_width  - 100 * 2),
                dialog_height = o.height || max_dialog_height,
                dialog_auto_height = dialog_height === 'auto',
                simplemodal_container = this.dialog_container.closest('.simplemodal-container'),
                ceil = Math.ceil;

            if (dialog_width > o.max_width) {
                dialog_width = o.max_width;
            }

            if (!o.width && dialog_width < o.min_width) {
                dialog_width = o.min_width;
            }

            simplemodal_container.css({
                width: dialog_width + 'px',
                height: dialog_height + (dialog_auto_height ? '' : 'px'),
                left: ceil((window_width - dialog_width) / 2 - 10) + 'px',
                top: '120px'
            }).find('.simplemodal-wrap').css('overflow', 'auto');

            if (dialog_auto_height) {
                if (simplemodal_container.height() > max_dialog_height) {
                    simplemodal_container.css('height', max_dialog_height + 'px');
                }

                simplemodal_container.css('top', Math.max(
                    ceil((window_height - simplemodal_container.height()) / 2),
                    100
                ) + 'px');
            }

            var fill_height = this.get_part('body').find('.nc--fill');
            if (fill_height.length) {
                fill_height.css('height', simplemodal_container.height() + 'px');
            }

            o.on_resize();
            return this;
        },

        /**
         * Закрытие диалога и уничтожение его содержимого
         */
        destroy: function() {
            this.close();

            if (window.CKEDITOR) {
                this.dialog_container.find('textarea').each(function() {
                    if (this.name in CKEDITOR.instances) {
                        CKEDITOR.remove(CKEDITOR.instances[this.name]);
                    }
                });
            }

            this.dialog_container.remove();
        },

        /**
         * Удаление содержимого частей диалога
         */
        clear_all: function() {
            for (var part in this.parts) {
                this.clear(part);
            }
        },

        /**
         * Удаление содержимого указанной части диалога (header, title etc.)
         * @param part_name
         */
        clear_part: function(part_name) {
            this.get_part(part_name).empty();
        },

        /**
         * Поиск элементов по селектору внутри диалога
         * @param selector
         */
        find: function(selector) {
            if (!this.dialog_container) {
                this.create();
            }
            return this.dialog_container.find(selector);
        },

        /**
         * Возвращает указанную часть диалога (header, title etc.)
         * @param part_name
         */
        get_part: function(part_name) {
            return this.find(this.parts[part_name]);
        }

    };

    nc.ext('modal_dialog', modal_dialog, 'ui');

})(window);
