(function(params) {
    var $ = jQuery,
        settings = params.settings;

    $(params.list_element).children().before(function(index) {
        var body = $(this);

        function toggle() {
            body.slideToggle(200);
            $(this).toggleClass('tpl-state-opened tpl-state-closed');
        }

        return $(
                '<div class="tpl-accordion-header tpl-state-' +
                (index === 0 && settings.open_first ? 'opened' : 'closed') +
                ' tpl-text-' + settings.header_text_type + '" tabindex="0">' +
                '<div class="tpl-accordion-header-container">' +
                '<div class="tpl-accordion-header-title">' + (body.data('object-name') || '***') +'</div>' +
                // chevron svg
                '<svg class="tpl-accordion-indicator" viewBox="0 0 24 24" fill="none">' +
                '<path d="M8 14L11.8 10L16 14" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
                '</svg>' +
                '</div>' +
                '</div>'
            )
            .click(toggle)
            .keypress(function(event) {
                if ([13, 32].indexOf(event.keyCode) >= 0) {
                    toggle.apply(this);
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
    });

});