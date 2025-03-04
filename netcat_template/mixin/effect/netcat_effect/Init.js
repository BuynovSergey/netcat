(function (params) {
    params.settings.forEach(function (item, i, arr) {
        if (item['type'] == 'fadein') {
            var block_element = params.block_element,
                speed = item['fadein_time'] * 1000 || 300,
                delay = item['fadein_delay'] * 1000 || 2000;
            jQuery(block_element).animate({opacity: 0}, 0);
            jQuery(window).on('scroll', function () {
                if (jQuery(document).scrollTop() + jQuery(window).height() > jQuery(block_element).offset().top && jQuery(document).scrollTop() - jQuery(block_element).offset().top < jQuery(block_element).height()) {
                    setTimeout(function () {
                        jQuery(block_element).animate({opacity: 1}, speed);
                    }, delay);
                }
            }).trigger('scroll');
        }

    });
});