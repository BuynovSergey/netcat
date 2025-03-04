(function(nc, $){

    /*global nc */

    //--------------------------------------------------------------------------
    // PRIVATE:
    //--------------------------------------------------------------------------

    // var popover_stack   = {};
    var popover_counter = 0;
    var animation_speed = 300;

    //-------------------------------------------------------------------------

    var make_popover_elem = function($obj) {
        var content = $obj.data('content');
        var $popover;

        if (!content) {
            return;
        }

        var popover_id = $obj.data('id');

        if (popover_id) {
            $popover = $('#' + popover_id);
            if ($popover.css('display') === 'none') {
                $popover.fadeIn(animation_speed);
                return true;
            }
            if ($popover.length) {
                return false;
            }
        }

        var offset       = 20;
        var invert_place = {t:'b',b:'t',r:'l',l:'r'};
        var style        = $obj.data('style');
        var z_index      = $obj.data('z-index') || 1;
        var width        = $obj.data('width');
        var placement    = get_placement_code($obj.data('placement'), 'right-center');
        var css          = $obj.offset();

        $popover = $(document.createElement('DIV'))
            .addClass('nc-popover' + (style ? ' nc--' + style : ''))
            .html(content)
            .css({
                'z-index': z_index,
                position:  'absolute',
                display:   'none'
            });

        if (width) {
            $popover.width(width);
        }

        popover_counter++;
        popover_id = 'nc_popover_' + popover_counter;

        $popover.attr('id', popover_id);
        $obj.attr('data-id', popover_id);

        $('body').append($popover);


        var x_place = placement[0];
        var y_place = placement[1];

        var obj_width  = $obj.outerWidth();
        var obj_height = $obj.outerHeight();
        var pop_width  = $popover.outerWidth();
        var pop_height = $popover.outerHeight();

        if (x_place === 'r') {
            css.left += obj_width + offset;
        }
        if (x_place === 'l') {
            css.left -= pop_width + offset;
        }
        if (x_place === 't') {
            css.top -= pop_height + offset;
        }
        if (x_place === 'b') {
            css.top += obj_height + offset;
        }

        if (placement === 'rc' || placement === 'lc') {
            css.top -= pop_height/2 - obj_height/2;
        }
        if (placement === 'rb' || placement === 'lb') {
            css.top -= pop_height - obj_height;
        }
        if (placement === 'tc' || placement === 'bc') {
            css.left -= pop_width/2 - obj_width/2;
        }
        if (placement === 'tr' || placement === 'br') {
            css.left -= pop_width - obj_width;
        }


        $popover
            .addClass('nc--' + invert_place[x_place] + y_place)
            .css(css)
            .fadeIn(animation_speed);


        return $popover;
    };

    //-------------------------------------------------------------------------

    var close_popover_elem = function($obj) {
        var popover_id = $obj.data('id');
        if (popover_id) {
            $('#' + popover_id).fadeOut(animation_speed);
        }
    };

    //-------------------------------------------------------------------------

    // convert "top-left" => "tl"; "right" => "rc"
    var get_placement_code = function(placement_string, def) {
        if (!placement_string) {
            placement_string = def;
        }

        var code = placement_string.split(/[- .]/);

        for (var i in code) {
            code[i] = code[i][0];
        }
        code = code.join('');

        if (code.length === 1) {
            code += 'c';
        }

        return code;
    };

    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------

    var fn = function(selector) {
        $(selector).each(function(){
            var $this = $(this);
            var trigger = $this.data('trigger') || 'click';

            if (trigger === 'load') {
                trigger += ' click';
            }

            $this.on(trigger, function() {
                if (make_popover_elem($this) === false) {
                    close_popover_elem($this);
                }

                return false;
            }).load();

            if (trigger === 'mouseover') {
                $this.mouseout(function() {
                    close_popover_elem($this);
                });
            }
        });
    };


    //--------------------------------------------------------------------------
    // PUBLIC:
    //--------------------------------------------------------------------------

    // fn.varname = {};

    //--------------------------------------------------------------------------

    nc.ext('popover', fn, 'ui');

    //--------------------------------------------------------------------------

})(nc, nc.$);