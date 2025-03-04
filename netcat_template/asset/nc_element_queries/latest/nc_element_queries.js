(function () {
    /**
     * Скрипт для применения стилей в зависимости от ширины элемента
     * ("element queries", по аналогии с media queries)
     *
     * Брейкпоинты элемента должны быть заданы в пикселях через пробел в атрибутах:
     *   data-nc-b — если брейкпоинты заданы для этого элемента        ("b" for "breakpoints")
     *
     * В процессе работы устанавливает два атрибута: data-nc-b1 и data-nc-b2, по которым могут быть
     * применены разные стили для разной ширины элементов.
     *   data-nc-b1 — брейкпоинты из data-nc-b меньше или равные текущей ширине элемента
     *   data-nc-b2 — брейкпоинты из data-nc-b больше текущей ширины
     *
     * Например:
     *   <div id="el" data-nc-b="500 1000 1500">...</div>
     * С таким элементом можно использовать стили:
     *   #el[data-nc-b1~="500"] — для ширины элемента от 500px и до ∞
     *   #el[data-nc-b2~="500"] — для ширины элемента от 0 до 499.99px
     *   #el[data-nc-b1~="500"][data-nc-b2~="1000"] — для ширины элемента 500—999.99px
     * Примечание: не нужно напрямую добавлять атрибуты data-nc-b в разметку шаблонов
     * компонентов и т. п., так как в будущих версиях особенности работы этого скрипта могут измениться.
     * В стилях шаблонов компонентов используйте блоки @nc-container и @nc-list-object.
     *
     * Поддерживаются Chrome 64+, Firefox 69+, Safari 13.1+, Edge 79+ (версии примерно начала 2020 года).
     * IE не поддерживается.
     */

    // Брейкпоинты из стилей компонентов — передаются через функцию nc_eq (см. ниже):
    const blockBreakpoints = {};

    // (1) Resize observer:
    const resizeObserver = new ResizeObserver(entries => {
            // циклы for, for..in работают быстрее, чем for..of (Chrome 94)
            const numEntries = entries.length;
            for (let i = 0; i < numEntries; i++) {
                const entry = entries[i],
                    element = entry.target,
                    values = ['', ''],
                    breakpoints = element.ncB,
                    numBreakpoints = breakpoints.length,
                    borderBoxSize = entry.borderBoxSize,
                    // ↓ значение entry.borderBoxSize[0].inlineSize в отличие от element.offsetWidth не округлено до целого
                    width = borderBoxSize ? borderBoxSize[0].inlineSize : element.offsetWidth;
                // попадают в observer только элементы со свойством ncB, см. mutation observer
                for (let i = 0; i < numBreakpoints; i++) {
                    values[breakpoints[i] > width ? 1 : 0] += breakpoints[i] + ' ';
                }
                // [element.dataset.ncB1, element.dataset.ncB2] = values; // медленнее присвоения атрибутов по отдельности
                element.setAttribute('data-nc-b1', values[0]);
                element.setAttribute('data-nc-b2', values[1]);
            }
        }),
        resizeObserverOptions = { box: 'border-box' };

    // Инициализация добавленного элемента: установка свойства ncB на основе data-nc-b, blockBreakpoints
    const initElement = element => {
        let breakpoints = element.getAttribute('data-nc-b') || '', // быстрее, чем element.dataset.ncB
            selector;

        // Поиск брейкпоинтов для элемента в blockBreakpoints (@nc-container, @nc-list-object из стилей компонентов)
        for (selector in blockBreakpoints) {
            if (element.matches(selector)) {
                breakpoints += ' ' + blockBreakpoints[selector];
            }
        }

        breakpoints = breakpoints.trim();

        if (breakpoints) {
            // Брейкпоинты из data-свойств кэшируются в виде массива в кастомном expando-свойстве "ncB" у элемента.
            // Это позволяет не вычислять значения каждый раз при изменении размеров, но убирает возможность
            // добавлять брейкпоинты изменением атрибута data-nc-b (на момент написания такая возможность не требуется).
            element.ncB = breakpoints.split(' ').map(Number);
            resizeObserver.observe(element, resizeObserverOptions);
        }

        // После загрузки страницы при обновлении фрагмента addedNode может иметь дочерние элементы
        // (для которых наблюдатель отдельно не сработает)
        const numChildren = element.childElementCount;
        for (let i = 0; i < numChildren; i++) {
            initElement(element.children[i]);
        }
    };

    // (2) Mutation observer
    const mutationObserver = new MutationObserver(mutationsList => {
        const numMutations = mutationsList.length;
        for (let m = 0; m < numMutations; m++) {
            const nodes = mutationsList[m].addedNodes, 
                numNodes = nodes.length;
            for (let n = 0; n < numNodes; n++) {
                if (nodes[n] instanceof Element) { // в Chrome 94 чуть быстрее, чем проверка свойства nodeType === 1
                    initElement(nodes[n]);
                }
            }
            // removedNodes нет необходимости отвязывать от ResizeObserver (за это отвечает garbage collector браузера)
        }
    });

    // Предположительно вставлено прямо в <head>, то document.body будет недоступно,
    // поэтому наблюдаем за всем <html>:
    mutationObserver.observe(document.documentElement, { childList: true, subtree: true });

    // Глобальная функция nc_eq для инициализации брейкпоинтов из стилей компонентов
    nc_eq = newBreakpoints => Object.assign(blockBreakpoints, newBreakpoints); // "eq" for "element queries"

})();
