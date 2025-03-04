// Дополнительные действия для скрытия блока во флексе в строку
(function(params) {
    if (params.breakpoint_type !== 'block') {
        return;
    }

    var block_element = params.block_element,
        parent_element = block_element.parentNode,
        parent_computed_style = getComputedStyle(parent_element);

    if (parent_computed_style.display !== 'flex' || parent_computed_style.flexDirection !== 'row') {
        return;
    }

    function on_parent_resize() {
        block_element.style.display = '';
        setTimeout(() => {
            if (getComputedStyle(block_element).height === '0px') {
                block_element.style.display = 'none';
            }
        }, 9); // даём примениться стилям nc_element_queries
    }


    setTimeout(on_parent_resize, 9); // даём примениться стилям nc_element_queries
    var observer = new ResizeObserver(on_parent_resize);
    observer.observe(parent_element);

    return {
        detach: () => observer.disconnect()
    };
});