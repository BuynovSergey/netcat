/**
 * Класс с функциональностью для миксина netcat_visibility_dropdown
 * (скрытие блока и показ его при нажатии или наведении мыши на родительский
 * элемент).
 *
 * Использование:
 *   var dropdown = new nc_visibility_dropdown(params)
 *   dropdown.destroy();
 *
 * В качестве элемента (триггера), вызывающего появление скрытого элемента, используется
 * ближайший родительский элемент с классом tpl-dropdown-trigger, или div инфоблока со списком
 * объектов или контейнера.
 *
 * В процессе работы триггеру присваиваются CSS-классы:
 *   — tpl-dropdown-trigger
 *   — tpl-dropdown-trigger-[opened|closed]
 *   — tpl-dropdown-to-[right|left|bottom|top] — если скрытый элемент показывается
 *     в соответствующую сторону от триггера
 *
 * Выпадающему элементу присваиваются CSS-классы:
 *   — tpl-dropdown
 *   — tpl-dropdown-visible — если элемент виден
 *
 * @param {Object} params параметры в том же виде, что и для инициализации миксина
 *      block_element — элемент, который будет скрыт (выпадающий элемент)
 *      settings:
 *          show_trigger — событие, вызывающее показ скрытого элемента ('hover' — ховер и клик, 'click' — только клик)
 *          trigger_anchor — точка привязки к триггеру (см. параметр 'of' в jQuery UI position)
 *          dropdown_anchor — точка привязки скрытого элемента (см. параметр 'my' в jQuery UI position)
 *          gap_vertical, gap_vertical_unit — отступ по вертикали
 *          gap_horizontal, gap_horizontal_unit — отступ по горизонтали
 *          on_collision — что делать при выходе за границу экрана ('collision' в jQuery UI position)
 *
 */
(function ($) {

    var left = /left/, right = /right/, top = /top/, bottom = /bottom/,
        body = $('body'),
        next_menu_show_timeout,
        event_namespace = '.nc_visibility_dropdown',
        HOVER_DELAY = 300;

    // Определение класса с названием направления выпадающего меню относительно
    // trigger_element (только для основных вариантов; может использоваться для
    // выбора вида индикатора наличия выпадающего меню)
    function get_direction_class_name(trigger_anchor, dropdown_anchor) {
        var direction;
        if (right.test(trigger_anchor) && left.test(dropdown_anchor)) {
            direction = 'right';
        } else if (left.test(trigger_anchor) && right.test(dropdown_anchor)) {
            direction = 'left';
        } else if (bottom.test(trigger_anchor) && top.test(dropdown_anchor)) {
            direction = 'bottom';
        } else if (top.test(trigger_anchor) && bottom.test(dropdown_anchor)) {
            direction = 'top';
        } else {
            return '';
        }
        return 'tpl-dropdown-to-' + direction;
    }

    /**
     * Используется как конструктор
     * @param {Object} params
     */
    window.nc_visibility_dropdown = function(params) {
        var dropdown_element = $(params.block_element),
            trigger_element = dropdown_element.parent().closest('.tpl-dropdown-trigger, .tpl-block-list, .tpl-container'),
            settings = params.settings,
            position_block_level = params.settings.position_block_level || false, // в старых версиях значение отсутствовало
            parent_blocks = dropdown_element.parents('.tpl-dropdown-trigger, .tpl-block-list'),
            position_element = position_block_level ? parent_blocks.eq(Math.min(position_block_level, parent_blocks.length) - 1) : trigger_element,
            trigger_anchor = settings.trigger_anchor,
            dropdown_anchor = settings.dropdown_anchor,
            gap_vertical = settings.gap_vertical,
            gap_horizontal = settings.gap_horizontal,
            can_be_triggered_by_mouseover = settings.show_trigger === 'hover',
            direction_class_name = get_direction_class_name(trigger_anchor, dropdown_anchor),
            is_this_menu_visible = false,
            is_this_menu_opened_by_click = false,
            this_menu_hide_timeout;

        trigger_element.addClass('tpl-dropdown-trigger tpl-dropdown-trigger-closed ' + direction_class_name);
        dropdown_element.addClass('tpl-dropdown').hide()
            .data('hide' + event_namespace, hide);

        // Добавление отступов
        if (parseInt(gap_horizontal)) {
            dropdown_anchor = dropdown_anchor.replace(' ', (gap_horizontal >= 0 ? '+' : '') + gap_horizontal + settings.gap_horizontal_unit + ' ');
        }

        if (parseInt(gap_vertical)) {
            dropdown_anchor += (gap_vertical >= 0 ? '+' : '') + gap_vertical + settings.gap_vertical_unit;
        }

        function get_all_visible_dropdowns() {
            return body.find('.tpl-dropdown-visible');
        }

        // Показ выпадающего элемента
        function show(event) {
            dropdown_element
                .css({
                    visibility: 'hidden',
                    top: 0,
                    left: 0,
                    zIndex: 9000,
                    overflow: 'visible'
                })
                .show() // нужно до .position() для правильного позиционирования для админа
                .position({
                    my: dropdown_anchor,
                    at: trigger_anchor,
                    of: position_element,
                    collision: settings.on_collision
                })
                .css('visibility', '')
                .addClass('tpl-dropdown-visible');

            trigger_element.removeClass('tpl-dropdown-trigger-closed').addClass('tpl-dropdown-trigger-opened');

            is_this_menu_visible = true;

            if (event && event.type === 'click') {
                event.preventDefault();
                is_this_menu_opened_by_click = true;
            } else {
                is_this_menu_opened_by_click = false;
            }

            body.on('click', hide_on_click);

            // форсированное закрытие всех остальных выпадашек
            get_all_visible_dropdowns().each(function() {
                var dropdown_to_check_and_hide = $(this), hide_checked = dropdown_to_check_and_hide.data('hide' + event_namespace);
                if (hide_checked && !dropdown_element.closest(dropdown_to_check_and_hide).length) {
                    hide_checked();
                }
            });
        }

        // Показ выпадающего элемента (с задержкой) при наведении на триггер
        function show_on_mouseenter(event) {
            if (can_be_triggered_by_mouseover || get_all_visible_dropdowns().length) {
                clearTimeout(next_menu_show_timeout);
                clearTimeout(this_menu_hide_timeout);
                next_menu_show_timeout = setTimeout(function() { show(event); }, HOVER_DELAY + 1);
            }
        }

        // Скрытие выпадающего элемента
        function hide() {
            dropdown_element.hide().removeClass('tpl-dropdown-visible');
            trigger_element.removeClass('tpl-dropdown-trigger-opened').addClass('tpl-dropdown-trigger-closed');
            is_this_menu_visible = false;
            this_menu_hide_timeout = false;
            body.off('click', hide_on_click);
        }

        // Скрытие выпадающего элемента при нажатии за пределами скрытого слоя
        function hide_on_click(event) {
            var clicked_element = $(event.target);
            if (is_this_menu_visible && event.type === 'click' && !clicked_element.closest(trigger_element).length) {
                hide();
                if (is_this_menu_opened_by_click) {
                    event.preventDefault();
                }
            }
        }

        // Скрытие выпадающего элемента при отведении мыши
        function hide_on_mouseleave() {
            if (is_this_menu_visible) {
                this_menu_hide_timeout = setTimeout(function() { hide(); }, HOVER_DELAY);
            }
        }

        // Инициализация слушателей событий
        trigger_element
            .on('click' + event_namespace, function(event) {
                is_this_menu_visible ? hide_on_click(event) : show(event);
            })
            .on('mouseenter' + event_namespace, show_on_mouseenter)
            .on('mouseleave' + event_namespace, hide_on_mouseleave);

        // Деинициализация выпадающего элемента
        this.destroy = function() {
            clearTimeout(next_menu_show_timeout);
            clearTimeout(this_menu_hide_timeout);
            hide();

            trigger_element
                .off(event_namespace)
                .removeClass(direction_class_name + ' tpl-dropdown-trigger-opened tpl-dropdown-trigger-closed');

            dropdown_element
                .css({
                    top: '',
                    left: '',
                    zIndex: '',
                    overflow: '',
                    display: ''
                })
                .removeClass('tpl-dropdown')
                .removeData('hide' + event_namespace);
        }
    }

})(jQuery);
