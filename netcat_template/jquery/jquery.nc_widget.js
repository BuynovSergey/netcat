(function($){
    var class_prefix    = 'nc-',
        latency_timeout = false,
        loaded_widgets  = [];

    $(window).resize(function(){
        if (latency_timeout !== false) {
            return;
        }
        latency_timeout = setTimeout(function(){
            latency_timeout = false;
            for (var i=0; i<loaded_widgets.length; i++) {
                var widget    = loaded_widgets[i],
                    className = class_prefix + 'widget-' + widget.widget_id,
                    width     = widget.node.width();

                for (var j=0; j<widget.breakpoints.length; j++) {
                    var breakpoint = widget.breakpoints[j];
                    className += ' ' + class_prefix + (width >= breakpoint ? 'lg' : 'sm') + '-' + breakpoint;
                }

                widget.node[0].className = className;
            }
        }, 100);
    });

    $.fn.nc_widget = function(options) {
        var css_loaded = false,
            widget = $.extend({
                node:        this,
                css:         false,
                breakpoints: [],
                widget_id:   0
            }, options);

        for (var i=0; i<loaded_widgets.length; i++) {
            if (loaded_widgets[i].widget_id == widget.widget_id) {
                css_loaded = true;
                break;
            }
        }

        if (!css_loaded) {
            $('head').append('<link rel="stylesheet" href="' + widget.css + '">');
        }
        
        widget.node.css({overflow:'hidden'});
        loaded_widgets.push(widget);

        $(window).resize();

        return this;
    };
 
}(jQuery));