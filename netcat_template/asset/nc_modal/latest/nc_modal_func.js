/**
 * Все всплывающеи окна
 * @returns {*}
 */
function nc_popups() {
    return jQuery('.nc-popup');
}

/**
 * показать окно
 * @param id
 */
function nc_popup_open(id) {
    var $popups = nc_popups();
    $popups.removeClass('active').hide();
    $popup = jQuery('#' + id);
    $popup.fadeIn(200, function () {
        jQuery(this).addClass('active');
    });
}

/**
 * Закрыть все окна
 */
function nc_popup_close() {
    var $popups = nc_popups();
    $popups.fadeOut(200, function () {
        jQuery(this).removeClass('active');
    });
}

jQuery(document).on('click', '.js-popup-close', function (e) {
    e.preventDefault();
    nc_popup_close();
});
jQuery(document).on('keypress', 'body', function (e) {
    if (e.which == 27) {
        nc_popup_close();
    }
});
jQuery(document).on('click', 'body', function (e) {
    var hasActivePopup = false;
    var $popups = nc_popups();
    $popups.each(function () {
        $popup = $(this);
        if ($popup.hasClass('active')) {
            hasActivePopup = true;
        }
    });
    if (hasActivePopup && !jQuery(e.target).closest('.js-popup-inner').length) {
        nc_popup_close();
    }
});
