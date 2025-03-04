(function (params) {
    var slick_container = params.list_element;
    var _params = {
        infinite: !!params.settings.loop,
        slidesToShow: params.settings.slides_per_view || 3,
        speed: params.settings.speed || 300,
        arrows: !!params.settings.show_arrows,
        dots: params.settings.pagination_type === 'dots',
        fade: params.settings.effect === 'fade',
        cssEase: 'linear',
        loop: !!params.settings.loop,
        autoplay: !!params.settings.autoplay,
        autoplaySpeed: params.settings.autoplay_delay,

    };
    jQuery(slick_container).slick(_params);
    jQuery(slick_container).find('.slick-next, .slick-prev').addClass('tpl-button-primary').html("");
});