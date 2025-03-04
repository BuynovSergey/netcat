(function(params) {
    jQuery(params.list_element).children()
        .show()
        .filter('.tpl-accordion-header').remove();
});