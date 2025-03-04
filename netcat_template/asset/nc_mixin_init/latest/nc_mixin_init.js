(function() {

    // Информация о добавленных слушателях
    const all_blocks_handlers = new Map;

    // Главный наблюдатель за изменением размеров блоков
    const resize_observer = new ResizeObserver(
        entries => {
            // циклы for, for..in работают быстрее, чем for..of (Chrome 94)
            const numEntries = entries.length;
            for (let i = 0; i < numEntries; i++) {
                execute_block_handlers(entries[i].target, entries[i].borderBoxSize[0].inlineSize);
                //       в отличие от element.offsetWidth значение не округлено до целого ↑↑↑
            }
        }
    );

    // Выполнение destruct, init для блока; если блок больше не в DOM, удаляет обработчики событий для него
    const execute_block_handlers = (element, precalculated_block_width) => {
        const handlers = all_blocks_handlers.get(element);

        if (handlers) {
            if (document.contains(element)) {
                if (handlers.block_resize && precalculated_block_width === undefined) {
                    precalculated_block_width = element.offsetWidth;
                }
                for (const destruct of handlers.destruct) {
                    destruct(precalculated_block_width);
                }
                for (const init of handlers.init) {
                    init(precalculated_block_width);
                }
            } else {
                // При удалении блока из DOM удаляем его из all_block_handlers, убираем слушателей событий
                resize_observer.unobserve(element);
                if (handlers.mutation) {
                    handlers.mutation.disconnect();
                }
                if (handlers.window_resize) {
                    removeEventListener('resize', handlers.window_resize);
                }
                all_blocks_handlers.delete(element);
            }
        }
    };

    const resize_observer_options = { box: 'border-box' };

    /**
     * Инициализация JS миксина
     * @param {String} block_selector селектор блока, к которому применён миксин
     * @param {String} list_selector селектор элемента со списком в блоке
     * @param {String} mixin_selector дополнительный селектор для применения миксина
     * @param {Number} from брейкпоинт начала диапазона действия миксина (включительно)
     * @param {Number} to брейкпоинт конца диапазона действия миксина (менее указанного значения)
     * @param {Function} init_handler функция, выполняющаяся при инициализации миксина
     *   Будет передан объект с ключами:
     *      block_element: {Element} — элемент, содержащий блок
     *      list_element: {Element} — элемент, содержащий список записей
     *      settings: {Object} — настройки миксина
     *      breakpoint_type: {String} — к чему применяются брейкпоинты (block, viewport)
     * @param {Function} destruct_handler функция, выполняющаяся при прекращении действия миксина
     *   Будет передан объект как в init_handler, дополнительно может присутствовать ключ init_result с
     *   возвращёнными init-функцией данными (если они были)
     * @param {Object} settings объект с настройками
     * @param {String} breakpoint_type block|viewport
     */
    nc_mixin_init = function(block_selector, list_selector, mixin_selector, from, to, init_handler, destruct_handler, settings, breakpoint_type) {
        const block_element = document.querySelector(block_selector),
            handler_params = {
                block_element: block_element,
                list_element: list_selector ? block_element.querySelector(list_selector) : null,
                settings: settings,
                breakpoint_type: breakpoint_type
            },
            is_viewport_breakpoint = breakpoint_type !== 'block', // есть только два вида брейкпоинтов (block, viewport)
            has_complex_selector = /[\s>+~]/.test(mixin_selector); // задан «сложный» (иерархический) селектор, например ".parent &"

        if (!block_element) {
            return;
        }
        
        let was_inside_range, // при предыдущем вызове блок был в диапазоне from..to и соответствовал mixin_selector’у
            check_width, // функция проверки ширины
            init; // функция вызова init_handler

        // Проверка совпадения с диапазоном ширины и селектором
        const matches_range = width => check_width(width) && (!mixin_selector || block_element.matches(mixin_selector));
        // Выполнение всех (не только для этого диапазона) обработчиков (сначала всех destruct, затем всех init)
        const execute_this_block_handlers = () => execute_block_handlers(block_element);

        // Обработчики init, destruct для каждого элемента и диапазона сохраняются в all_blocks_handlers,
        // чтобы можно было вызвать обработчики в необходимом порядке (сначала destruct, затем init):
        //   init — массив с функциями инициализации
        //   destruct — массив с функциями деинициализации
        //   block_resize — флаг, указывающий на то, что блок был добавлен к resize_observer
        //   window_resize — слушатель события window.onresize (добавляется только один раз)
        //   mutation — MutationObserver для отслеживания классов этого и родительских элементов (создаётся только один раз)
        if (!all_blocks_handlers.has(block_element)) {
            all_blocks_handlers.set(block_element, {
                init: [],
                destruct: [],
                block_resize: false,
                window_resize: false,
                mutation: false
            });
        }

        const this_block_handlers = all_blocks_handlers.get(block_element);

        // Инициализация при попадании в диапазон
        if (init_handler) {
            init = block_width => {
                const is_inside_range = matches_range(block_width);
                if (!was_inside_range && is_inside_range) {
                    handler_params.init_result = init_handler(handler_params);
                }
                was_inside_range = is_inside_range;
            };
            this_block_handlers.init.push(init);
        }

        // Деинициализация при выходе из диапазона
        if (destruct_handler) {
            this_block_handlers.destruct.push(block_width => {
                if (was_inside_range && !matches_range(block_width)) {
                    destruct_handler(handler_params);
                }
            });
        }

        // Функция проверки ширины — зависит от типа брейкпоинта (для ширины окна или ширины элемента)
        if (is_viewport_breakpoint) { // breakpoint_type === 'viewport'
            check_width = () => matchMedia(`(min-width:${from}px) and (max-width:${to - 0.01}px)`).matches;
        } else { // breakpoint_type === 'block'
            check_width = width => width >= from && width < to;

            if (!this_block_handlers.block_resize) { // добавляем к наблюдателю только один раз
                resize_observer.observe(block_element, resize_observer_options);
                this_block_handlers.block_resize = true;
            }
        }

        if ((is_viewport_breakpoint || has_complex_selector) && !this_block_handlers.window_resize) {
            //                            ↑
            // window.resize можно использовать как триггер для переинициализации при использовании сложных
            // (иерархических — заданных с '&' в настройках) селекторов миксинов с псевдо-классами (пример:
            // ".block:first_child > &"). Автоматически переинициализация для таких селекторов не производится.
            addEventListener('resize', execute_this_block_handlers);
            this_block_handlers.window_resize = execute_this_block_handlers; // добавляем наблюдателя только один раз
        }

        // если есть дополнительный селектор для применения миксина, следим также за изменением атрибута class
        if (mixin_selector && !this_block_handlers.mutation) {
            const mutation_observer = new MutationObserver(execute_this_block_handlers);
            let target = block_element;
            while (target) {
                mutation_observer.observe(target, { attributeFilter: ['class'] });
                // Для иерархических селекторов следим за изменением class всех родительских элементов
                target = has_complex_selector && target.parentElement;
            }
            this_block_handlers.mutation = mutation_observer; // добавляем наблюдателя только один раз
        }

        init && init(block_element.offsetWidth);
    }

})();