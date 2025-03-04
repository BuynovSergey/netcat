/**
 * Класс с функциональностью для миксина netcat_layout_swiper
 * (адаптер для библиотеки Swiper)
 *
 * Использование:
 *   var swiper = new nc_layout_swiper(params)
 *   swiper.destroy();
 *
 */
(function () {

    /**
     * Используется как конструктор
     * @param {Object} params параметры в том же виде, что и для инициализации миксина
     */
    window.nc_layout_swiper = function(params) {
        var list_element = params.list_element;

        if (!list_element) {
            return;
        }

        function for_each_slide(fn) {
            var slides = list_element.children;
            for (var i = 0; i < slides.length; i++) {
                if (slides[i].className !== 'resize-sensor') { // play along with ElementQueries’ ResizeSensor.js
                    fn(slides[i]);
                }
            }
        }

        // В определённых случаях (изображения без фиксированной ширины, например 100%, внутри другого флексбокса) Swiper
        // неправильно вычисляет размер слайдов. Чтобы не происходило расползание слайдера (и, соответственно, родительских
        // блоков) из-за этого, придётся при инициализации и изменении размеров зафиксировать ширину слайдов явным образом.
        function fix_slides_width() {
            var container = swiper_container.style;
            container.position = 'absolute';

            var full_width = params.block_element.clientWidth,
                num_slides = slides_per_view_number || 1,
                slide_width_string = (full_width - space_between * (num_slides - 1)) / num_slides + 'px';

            container.width = full_width + 'px';

            for_each_slide(function (el) {
                el.style.maxWidth = slide_width_string;
                el.style.width = null; // устанавливается Swiper’ом исходя из нашего maxWidth
            });

            container.position = 'relative';
        }

        // Основные настройки слайдера
        var settings = params.settings,
            swiper_container = create_div('container'),
            slides_per_view_original_value = settings.slides_per_view, // string (numeric or 'auto')
            slides_per_view_number = parseInt(slides_per_view_original_value, 10) || 0,
            space_between = parseInt(settings.space_between, 10) || 0,
            swiper_options = {
                slidesPerView: slides_per_view_original_value,
                spaceBetween: space_between,
                speed: parseInt(settings.speed || 300, 10), // ✔: parseInt('0' || 300) === 0
                loopedSlides: slides_per_view_number || 4,
                loop: !!settings.loop,
                effect: settings.effect,
                centerInsufficientSlides: !settings.loop,
                on: {
                    resize: fix_slides_width,
                    // обновление положения тулбаров и т. п. в режиме редактирования
                    transitionEnd: function () {
                        window.$nc && $nc('.swiper-slide-active', list_element).trigger('mouseenter');
                    }
                }
            };

        // Добавление необходимой для swiper разметки и классов
        // <div class="swiper-container">       <!-- добавляется динамически -->
        //     <div class="swiper-wrapper">     <!-- = list_element -->
        //         <div class="swiper-slide">Slide 1</div>
        //     </div>
        //     <div class="swiper-pagination"></div>
        //     <div class="swiper-button-prev"></div>
        //     <div class="swiper-button-next"></div>
        //     <div class="swiper-scrollbar"></div>
        // </div>
        list_element.className += ' swiper-wrapper' +
            // также добавляем собственный класс swiper-nc-auto-slides-per-view,
            // без этих дополнительных стилей не получается получить нужный эффект (Swiper 4.4.6)
            (slides_per_view_original_value === 'auto' ? ' swiper-nc-auto-slides-per-view' : '');
        list_element.parentNode.insertBefore(swiper_container, list_element);
        swiper_container.appendChild(list_element);

        for_each_slide(function (slide) { slide.className += ' swiper-slide'; });

        if (settings.show_arrows) {
            swiper_options.navigation = {
                prevEl: create_div('button-prev', swiper_container),
                nextEl: create_div('button-next', swiper_container)
            };
        }

        if (settings.pagination_type) {
            swiper_options.pagination = {
                el: create_div('pagination', swiper_container),
                type: settings.pagination_type,
                clickable: true
            };
        }

        if (settings.show_scrollbar) {
            swiper_options.scrollbar = {
                el: create_div('scrollbar', swiper_container),
                draggable: true,
                hide: settings.show_scrollbar !== 'always'
            };
        }

        if (settings.autoplay) {
            swiper_options.autoplay = {delay: settings.autoplay_delay * 1000};
        }

        fix_slides_width();

        // Инициализация библиотеки Swiper
        var swiper = new Swiper(swiper_container, swiper_options);

        // ------------------------------------------------------------------------------------------------------------

        // Функция деинициализации
        this.destroy = function() {
            var list_element = params.list_element,
                swiper_container = list_element.parentNode;

            if (!list_element || !swiper_container.swiper) {
                return;
            }

            var swiper_container_parent = swiper_container.parentNode,
                slides = list_element.children;

            // cleanStyles=false: Swiper полностью очищает атрибут style, чего хотелось бы на всякий случай избежать
            swiper_container.swiper.destroy(true, false);
            // так как destroy() вызван с cleanStyles=false, Swiper может оставить transform: translate3d,
            // из-за чего список будет невидим
            list_element.style.transform = '';

            remove_swiper_classes(list_element);
            swiper_container_parent.insertBefore(list_element, swiper_container);
            swiper_container_parent.removeChild(swiper_container); // IE: нет метода Element.remove()

            for (var i = 0; i < slides.length; i++) {
                remove_swiper_classes(slides[i]);
                var style = slides[i].style;
                style.width = style.maxWidth = style.marginRight = null;
            }
        };

        // ------------------------------------------------------------------------------------------------------------

        // ▽▽▽
        // В режиме редактирования выпадающие меню могут быть частично скрыты из-за { overflow: hidden }.
        // Если UI будет переработан так, что элементы управления не будут располагаться внутри блока,
        // необходимо будет убрать этот фрагмент:
        if (window.$nc && $nc('body').is('.nc--mode-edit')) {
            function get_bottom(el) {
                return el.offset().top + el.height();
            }
            function has_overflow(dropdown_menu) {
                var swiper_container = dropdown_menu.closest('.swiper-container');
                return swiper_container.length && get_bottom(dropdown_menu) > get_bottom(swiper_container);
            }
            function on_click() {
                var menu = $nc(this).children('ul');
                setTimeout(function() {
                    has_overflow(menu) && $nc(swiper_container).css('overflow', 'visible');
                }, 5);
            }
            function on_leave() {
                $nc(swiper_container).css('overflow', 'hidden');
            }

            if (/\[m/.test(params.block_selector)) { // селектор с уточнениями для брейкпоинтов?
                $nc('body')
                    .on('click', '$selector .nc-object-toolbar .nc--dropdown', on_click)
                    .on('mouseleave', '$selector .nc-object-toolbar', on_leave);
            } else {
                $nc(params.block_selector + ' .nc-object-toolbar').mouseleave(on_leave)
                    .find('.nc--dropdown').click(on_click);
            }
        }
        // △△△  конец фрагмента, который нужно будет удалить

    };

    // ------------------------------------------------------------------------------------------------------------
    // Вспомогательные функции
    // ------------------------------------------------------------------------------------------------------------

    // создаёт div с указанным 'swiper'-классом
    function create_div(class_name_suffix, parent) {
        var div = document.createElement('div');
        div.className = 'swiper-' + class_name_suffix;
        if (parent) {
            parent.appendChild(div);
        }
        return div;
    }

    // Удаляет из строки названия CSS-классов с префиксом 'swiper-'
    function remove_swiper_classes(element) {
        element.className = element.className.replace(/\bswiper-[\w-]+\s?\b/g, '').trim();
    }


})();