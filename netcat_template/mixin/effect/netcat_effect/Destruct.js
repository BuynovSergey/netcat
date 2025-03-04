(function (params) {

    params.settings.forEach(function (item, i, arr) {

        if (item['type'] == 'fadein') {
            var block_element = params.block_element;
            jQuery(block_element).animate({opacity: 1}, 0);
        }

    });
});