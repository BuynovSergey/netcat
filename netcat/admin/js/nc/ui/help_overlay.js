(function(nc, $){

    /*global nc */

    //--------------------------------------------------------------------------
    // PRIVATE:
    //--------------------------------------------------------------------------

    var overlays_counter = 0;

    var default_settings = {
        padding: 10
    };
    // var animation_speed = 300;

    //-------------------------------------------------------------------------

    var merge_object = function(a, b) {
        var result = {}, k;

        for (k in a) {
            result[k] = a[k];
        }
        for (k in b) {
            result[k] = b[k];
        }

        return result;
    };

    //-------------------------------------------------------------------------

    var make_popover = function(settings, obj) {
        var css;
        var padding = settings.padding;

        if (settings.axis) {
            css        = {
                left: settings.axis[0] - padding,
                top:  settings.axis[1] - padding,
            };
            css.width  = settings.axis[2] + padding*2;
            css.height = settings.axis[3] + padding*2;
        } else if (obj) {
            var $this  = $(obj);
            css        = $this.offset();
            css.top   -= padding;
            css.left  -= padding;
            css.width  = $this.outerWidth() + padding*2;
            css.height = $this.outerHeight() + padding*2;
        }

        css.position   = 'absolute';
        css['z-index'] = 999;
        // css.background   = 'rgba(255,0,0,.5)';

        fn.ctx.clearRect(css.left, css.top, css.width, css.height);
        var $popover = $(document.createElement('div')).css(css);

        // if (settings.style) {
        //     $popover.addClass('nc-border-' + settings.style);
        // }

        $popover.data('z-index', settings['z-index'] || 999);

        for (var k in settings) {
            $popover.data(k, settings[k]);
        }

        fn.$overlay_objet.append($popover);

        fn.popovers.push($popover);
    };

    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------

    var fn = {};


    //--------------------------------------------------------------------------
    // PUBLIC:
    //--------------------------------------------------------------------------

    fn.overlay_id     = 0;
    fn.$overlay_objet = false;
    fn.$canvas        = false;
    fn.ctx            = false;
    fn.popovers       = [];

    //-------------------------------------------------------------------------

    fn.init = function(settings) {
        this.settings = merge_object(default_settings, settings);

        overlays_counter++;

        var $html   = $('html');
        var $canvas = $(document.createElement('canvas'));
        var ctx     = $canvas[0].getContext('2d');

        this.$canvas = $canvas;
        this.ctx     = ctx;

        this.overlay_id     = 'nc_help_overlay_' + overlays_counter;
        this.$overlay_objet = $(document.createElement('div'))
            .attr('id', this.overlay_id)
            .hide();
        this.$overlay_objet.append(this.$canvas);


        $canvas
            .css({
                position:  'absolute',
                top:       0,
                left:      0,
                "z-index": 999,
            })
            .attr('width', $html.outerWidth())
            .attr('height', $html.outerHeight());

        ctx.fillStyle = "rgba(0, 0, 0, 0.25)";
        ctx.fillRect(0, 0, $canvas[0].width, $canvas[0].height);

        $('body').append(this.$overlay_objet);

        return this;
    };

    //-------------------------------------------------------------------------

    fn.add = function(settings) {
        settings = merge_object(this.settings, settings);

        if (settings.target) {
            $(settings.target).each(function(){
                make_popover(settings, this);
            });
        } else if (settings.axis) {
            make_popover(settings);
        }

        return this;
    };

    //-------------------------------------------------------------------------

    fn.show = function() {
        this.$overlay_objet.show(function(){
            for (var i in fn.popovers) {
                nc.ui.popover(fn.popovers[i]);
            }
        });
        return this;
    };

    //-------------------------------------------------------------------------

    fn.hide = function() {
        for (var k in fn.popovers) {
            $('#' + fn.popovers[k].data('id')).hide();
        }
        this.$overlay_objet.hide();
        return this;
    };

    //--------------------------------------------------------------------------

    nc.ext('help_overlay', function(settings) {
        return fn.init(settings);
    }, 'ui');

    //--------------------------------------------------------------------------

})(nc, nc.$);