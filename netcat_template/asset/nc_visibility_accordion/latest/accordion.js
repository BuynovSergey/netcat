/**
 * Класс с функциональностью для миксина netcat_visibility_accordion
 *
 * Использование:
 *   var accordion = new nc_visibility_accordion(params)
 *   accordion.destroy();
 *
 * В качестве элемента (триггера), вызывающего появление скрытого элемента, используется
 * ближайший родительский элемент с классом tpl-dropdown-trigger (sic), или div инфоблока
 * со списком объектов или контейнера.
 *
 * В процессе работы триггеру присваиваются CSS-классы:
 *   — tpl-accordion-trigger
 *   — tpl-accordion-trigger-opened | tpl-accordion-trigger-closed
 *
 */
(function($) {

    var event_namespace = '.nc_visibility_accordion';

    /**
     * Используется как конструктор
     * @param {Object} params параметры в том же виде, что и для инициализации миксина
     */
    window.nc_visibility_accordion = function(params) {
        var toggled_element = $(params.block_element),
            trigger_element = toggled_element
                .closest('.tpl-dropdown-trigger, .tpl-block-list')
                .addClass('tpl-accordion-trigger tpl-accordion-trigger-closed'),
            is_open = false,
            trigger_toggled_classes = 'tpl-accordion-trigger-opened tpl-accordion-trigger-closed';

        trigger_element.on('click' + event_namespace, function(event) {
            var clicked_element = $(event.target);
            if (clicked_element.closest(toggled_element).length || clicked_element.closest('.nc-infoblock-toolbar').length) {
                return; // click inside toggled ("hidden") element; click inside netcat toolbar
            }
            if (is_open) {
                toggled_element.slideUp();
            } else {
                toggled_element.slideDown();
            }
            is_open = !is_open;
            trigger_element.toggleClass(trigger_toggled_classes);
            return false;
        });

        this.destroy = function() {
            trigger_element
                .off('click' + event_namespace)
                .removeClass('tpl-accordion-trigger ' + trigger_toggled_classes);
        }
    };

})(jQuery);