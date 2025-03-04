(function(params) {
    var background_url = params.settings.background_url;
    if (background_url) {
        var block = jQuery(params.block_element);
        if (!parseInt(block.css('zIndex'), 10)) {
            block.css('zIndex', 1);
        }
        block.parallax({
            mirrorContainer: block,
            imageSrc: background_url,
            speed: params.settings.speed || 0.5
        });
    }
});