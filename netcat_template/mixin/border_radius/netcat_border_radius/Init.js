(function (params) {
// ▽▽▽
// В режиме редактирования выпадающие меню могут быть частично скрыты из-за { overflow: hidden }.
// Если UI будет переработан так, что элементы управления не будут располагаться внутри блока,
// необходимо будет полностью убрать файлы Init.js и Destruct.js этого миксина:
    if (window.$nc && $nc('body').is('.nc--mode-edit')) {
        var block = $nc(params.block_element),
            overflow_target = params.list_element ? block.children('.tpl-block-list') : block,
            events = 'click.nc_border_radius mouseenter.nc_border_radius mouseleave.nc_border_radius',
            buttons = overflow_target.find('.nc-infoblock-toolbar-more, .nc-object-toolbar-more').on(events, delayed_handler);

        function delayed_handler() {
            setTimeout($nc.proxy(toggle, this), 1);
        }

        function remove_hidden() {
            // прямая модификация атрибута, чтобы не чистить стили при деинициализации
            overflow_target.attr('style', (overflow_target.attr('style') || '').replace('overflow: visible;', ''));
        }

        function toggle() {
            if ($nc(this).is('.nc--clicked')) {
                overflow_target.css('overflow', 'visible');
                $nc(this).one('mouseleave.nc_border_radius', function () {
                    setTimeout($nc.proxy(toggle, this), 1010); // 1000 — задержка перед закрытием меню, см. nc_init_toolbar_dropdowns() в nc_admin.js
                })
            } else {
                remove_hidden();
            }
        }

        return {
            destruct: function () {
                buttons.off(events);
                remove_hidden();
            }
        };
    }
// △△△  конец фрагмента, который нужно будет удалить
});
