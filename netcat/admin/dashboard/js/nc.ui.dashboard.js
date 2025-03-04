(function(nc, $){

    /*global nc */

    var fn = {};

    if (nc.ui.dashboard) {
        nc.ui.dashboard = fn;
    }

    //--------------------------------------------------------------------------
    // PRIVATE:
    //--------------------------------------------------------------------------

    var gridster = null;

    var def = function(obj, obj2, key, def) {
        return nc.key_exists(key, obj) ? obj[key] : (nc.key_exists(key, obj2) ? obj2[key] : def);
    };


    //--------------------------------------------------------------------------
    // PUBLIC:
    //--------------------------------------------------------------------------

    fn.widget_types = {};
    fn.user_widgets = {};

    //--------------------------------------------------------------------------

    /**
     * Инициализация виджетов
     * @param  {json} widget_types массив доступных виджетов
     * @param  {json} widgets      массив отображаемых виджетов
     * @param  {json} opt          настройки сетки виджетов
     */
    fn.init = function(widget_types, widgets, opt) {

        var gm = opt.grid_margin,
            gs = opt.grid_size;

        fn.opt          = opt;
        fn.widget_types = widget_types;
        fn.user_widgets = widgets;

        gridster = $('#nc-dashboard>div').gridster({
            // ie8compatmode: true,
            // add_columns_on_resize: false,

            widget_margins: [gm, gm],
            widget_base_dimensions: [gs, gs],
            widget_selector: 'div',
            // max_cols: 5,
            extra_rows: 0,
            extra_cols: 0,
            // max_size_x: 3,
            // max_size_y: 2,
            autogenerate_stylesheet: true,
            avoid_overlapped_widgets: false, //true
            serialize_params: function($w, wgd) {
                var widget = wgd.el.widget_settings;

                if ( ! widget) {
                    return wgd;
                }

                return {
                    type:  widget.type,
                    col:   wgd.col,
                    row:   wgd.row,
                    size:  [wgd.size_x, wgd.size_y],
                    color: widget.color,
                    query: widget.query
                };
            },
            draggable: {
                stop: function(){
                    nc.event.call(['dashboard','move']);
                }
            }
        }).data('gridster');

        gridster.disable();

        fn.toggle_add_btn = function(){

            nc.root(function(){
            var $btn = nc.root('#mainViewButtons div.nc_dashboard_add_widget');

            var size = function(obj){
                var size = 0;
                for (var key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        size++;
                    }
                }
                return size;
            };
            // console.log( size( fn.widget_types ), size( gridster.serialize() ) );
            // console.log( fn.widget_types , gridster.serialize() );
            if( size( fn.widget_types ) === size( gridster.serialize() ) ) {
                $btn.addClass('nc--disabled');
            }
            else {
                $btn.removeClass('nc--disabled');
            }
            });
        };

        fn.toggle_add_btn();

        // Register events
        nc.event('dashboard', function(e){
            fn.toggle_add_btn();
            fn.save_user_widgets();
            if(e.selector.join('.') === 'dashboard.close') {
                setTimeout(fn.save_user_widgets, 1000);
            }
        });

        // Register widgets
        for (var i=0; i<widgets.length; i++) {
            var $widget = $('#widget_' + i);
            if ($widget.length) {
                fn.register_widget(widgets[i], $widget, false);
            }
            // else {
            //     fn.add_widget(widgets[i], NaN, false);
            // }
        }

        fn.append_widget_tabs();
    };

    //--------------------------------------------------------------------------

    fn.save_user_widgets = function(){

        nc.process_start('dashboard.save_user_widgets()');

        var data = JSON.stringify(gridster.serialize());
        $.ajax({
            type:     "POST",
            url:      nc.config('admin_path') + "dashboard/ajax.php?action=save_user_widgets",
            data:     {'user_widgets': data },
            dataType: 'json',
            success:  function(){
                nc.process_stop('dashboard.save_user_widgets()');
                // console.log('save_user_widgets: [OK]', data);
            }
        });
    };

    //--------------------------------------------------------------------------

    fn.append_widget_tabs = function(){
        var widgets = gridster.serialize();
        // // Сортировка виджетов
        // var widgets = gridster.serialize().sort(function(a,b){
        //     // a < b
        //     if(a<b) {
        //         return -1;
        //     }
        //     // a > b
        //     if(a>b) {
        //         return 1;
        //     }
        //     // ==
        //     return 0;
        // });

        var w,content;
        var $tabs = $('#nc-dashboard-full div.nc-nav-tabs');

        // var ordering_widgets = [];
        for (var i=0; i<widgets.length; i++) {
            w = fn.widget_types[widgets[i].type];
            if ( nc.key_exists('fullscreen', w) && w.fullscreen ) {
                if (nc.key_exists('icon', w) && w.icon) {
                    content = "<i class='"+w.icon+"'></i>";
                }
                else {
                    content = "<span>"+w.title+"</span>";
                }
                $tabs.append("<div title='"+w.title+"' class='nc-widget nc--"+widgets[i].color+"'><div><a href='"+w.fullscreen+"' onclick='return nc.ui.dashboard.fullscreen(this)'>"+content+"</a></div></div>");
            }
        }
    };

    //--------------------------------------------------------------------------

    fn.reset_user_widgets = function(ln){
        $(ln).addClass('nc--disabled');

        $.ajax({
            type:     "POST",
            url:      nc.config('admin_path') + "dashboard/ajax.php?action=reset_user_widgets",
            dataType: 'json',
            success:  function(){
                window.location.reload();
            }
        });

        return false;
    };

    //--------------------------------------------------------------------------

    fn.fullscreen = function(ln, href){

        fn.close_fullscreen(true);

        var $full        = $('#nc-dashboard-full');
        var $full_iframe = $('iframe', $full);
        var $ln          = $(ln);

        $('#nc-dashboard').hide();
        $full.show();

        href = href || $ln.attr('href');
        nc.process_start('dashboard_full');
        $full_iframe.hide();

        $full_iframe[0].contentWindow.$nc('#mainViewIframe').attr('src', href)
            .load(function(){
                nc.process_stop('dashboard_full');
                $full_iframe.fadeIn();

                // Универсальный способ. т.к. во фрейме может не оказаться объектов: $nc или nc
                this.contentWindow.document.getElementsByTagName('body')[0].style.paddingRight = 20+'px';

                $full_iframe[0].contentWindow.nc($full_iframe[0].contentWindow).resize();
            });

        return false;
    };

    //--------------------------------------------------------------------------

    fn.close_fullscreen = function(reset_only){
        reset_only = reset_only || false;

        var $full         = $('#nc-dashboard-full');
        var iframe_window = $('iframe', $full)[0].contentWindow;

        $full.hide();
        iframe_window.nc('#mainViewIframe').attr('src', 'about:blank');

        if ( ! reset_only) {
            $('#nc-dashboard').show();
        }
    };

    //--------------------------------------------------------------------------

    fn.append_controls = function(widget, opt) {
        var gm    = fn.opt.grid_margin,
            gs    = fn.opt.grid_size,
            delta = gs + (gm * 2),
            gh    = 0;

        var $actions = $('<div/>', {'class':'nc-widget-actions'});

        // fullscreen action
        if (opt.fullscreen) {
            $actions.append(
                $('<i/>', {'class':'nc-icon nc--widget-maximize nc--hovered'})
                .click(function(){
                    fn.fullscreen(this, opt.fullscreen);
                    // window.location = opt.fullscreen;
                    // fn.close_widget(widget)
                    return false;
                })
            );
        }

        // edit action
        $actions.append(
                $('<i/>', {'class':'nc-icon nc--widget-edit'})
                .click(function(){
                    fn.widget_dialog(widget, opt);
                    return false;
                })
            );

        // close action
        $actions.append(
                $('<i/>', {'class':'nc-icon nc--widget-close'})
                .click(function(){
                    fn.close_widget(widget);
                    delete(fn.user_widgets[opt.type]);
                    return false;
                })
            );

        $('<div/>', {'class':'nc-widget-overlay'}).appendTo(widget);

        $actions.appendTo(widget);

        if (opt.resizeble) {
            $(widget).resizable({
                grid:      [delta, delta],
                animate:   false,
                minWidth:  gs,
                minHeight: gs,
                maxWidth:  delta*5,
                maxHeight: delta*3,
                autoHide:  true,

                start:     function() {
                    gh = gridster.$el.height();
                },

                resize: function(event) {
                    if (event.offsetY > gridster.$el.height())
                    {
                        var extra = Math.ceil((event.offsetY-gh)/delta+1);
                        var new_height = gh + extra * delta;
                        gridster.$el.css('height', new_height);
                    }
                },

                stop: function() {
                    var obj = $(this);
                    setTimeout(function() {
                        var gw = Math.ceil((obj.width()-gs)/delta+1);
                        var gh = Math.ceil((obj.height()-gs)/delta+1);
                        gridster.resize_widget(obj, gw, gh);
                        gridster.set_dom_grid_height();
                        nc.event.call(['dashboard','resize']);
                    }, 150);
                    fn.resize_content(obj);
                }
            });

            $('div.ui-resizable-handle', widget).hover(function() {
                gridster.disable();
            }, function() {
                gridster.enable();
            });
        }
    };

    //--------------------------------------------------------------------------

    fn.resize_content = function($widget) {
        $widget.find('div.nc-widget>div:first-child').css({
            width:   $widget.width(),
            height:  $widget.height(),
            padding: 0,
            margin:  0
        });
        $(window).trigger('resize');
    };

    //--------------------------------------------------------------------------

    fn.edit_mode = function(ln){
        fn.close_fullscreen();
        var emode = $('#nc-dashboard').toggleClass('nc-edit-mode').hasClass('nc-edit-mode');
        if (ln) {
            $(ln).css({'border-color':emode?'#56be2a':''});
        }
        if (emode) {
            gridster.enable();
            nc.root('#mainViewButtons div.nc_dashboard_reset_widgets').removeClass('nc--disabled').show();
        }
        else {
            gridster.disable();
            nc.root('#mainViewButtons div.nc_dashboard_reset_widgets').hide();
        }
        return false;
    };

    //--------------------------------------------------------------------------

    fn.close_widget = function(obj) {
        obj.animate({opacity:0}, function(){
            setTimeout(function(){
                gridster.remove_widget(obj);
                nc.event.call(['dashboard','close']);
            }, 300);
        });
    };

    //--------------------------------------------------------------------------

    fn.register_widget = function(widget, $widget, call_events) {
        if (call_events !== false) {
            call_events = true;
        }

        if ( ! nc.key_exists(widget.type, fn.widget_types) ) {
            return false;
        }

        fn.user_widgets[widget.type] = widget;

        var widget_settings = fn.widget_types[widget.type];

        var d = {
            type:       widget.type,
            row:        def(widget, widget_settings, 'row', 1),
            col:        def(widget, widget_settings, 'col', 1),
            size:       def(widget, widget_settings, 'size', widget.size),
            color:      def(widget, widget_settings, 'color', 'lighten'),
            query:      def(widget, widget_settings, 'query', 'route=index'),
            fullscreen: def(widget, widget_settings, 'fullscreen', false),
            resizeble:  def(widget, widget_settings, 'resizeble', false)
        };

        // var el = gridster.add_widget($widget.html("<div class='nc-widget nc--loader'></div>"), d.size[0], d.size[1], d.col, d.row);

        var wdg = gridster.serialize($widget);
        wdg[0].el.widget_settings = d;

        fn.resize_content($widget);
        nc.ui.custom_scroll( $widget.find('div.nc-widget-scrolled') );
        $widget.find('>div').show();

        fn.append_controls($widget, d);

        if (call_events) {
            nc.event.call(['dashboard','register']);
        }
    };

    //--------------------------------------------------------------------------

    fn.add_widget = function(widget, query, call_events) {
        if (call_events !== false) {
            call_events = true;
        }

        if ( ! nc.key_exists(widget.type, fn.widget_types) ) {
            return false;
        }

        fn.user_widgets[widget.type] = widget;

        var widget_settings = fn.widget_types[widget.type];

        var d = {
            type:       widget.type,
            row:        def(widget, widget_settings, 'row', 1),
            col:        def(widget, widget_settings, 'col', 1),
            size:       def(widget, widget_settings, 'size', widget.size),
            color:      def(widget, widget_settings, 'color', 'lighten'),
            query:      query || def(widget, widget_settings, 'query', 'route=index'),
            fullscreen: def(widget, widget_settings, 'fullscreen', false),
            resizeble:  def(widget, widget_settings, 'resizeble', false)
        };

        nc.process_start('dashboard.add_widget():' + widget.type);

        var $widget = $("<div class='nc-widget-box'></div>");
        var el = gridster.add_widget($widget.html("<div class='nc-widget nc--loader'></div>"), d.size[0], d.size[1], d.col, d.row);
        el.widget_settings = d;


        $.get(widget_settings.controller + '?' + d.query, function(data){
            $widget.html("<div class='nc-widget nc--"+d.color+"' style='display:none'>"+data+"</div>");

            fn.resize_content($widget);
            nc.ui.custom_scroll( $widget.find('div.nc-widget-scrolled') );

            $widget.find('>div').fadeIn(250);

            nc.process_stop('dashboard.add_widget():' + widget.type);

            fn.append_controls($widget, d);

            fn.user_widgets.push(gridster.serialize($widget));

            if (call_events) {
                nc.event.call(['dashboard','add']);
            }
        });

    };

    //--------------------------------------------------------------------------

    fn.close_widget_dialog = function(){

        // Возвращаем настройки цвета
        if (fn._edited_widget) {
            var widget      = fn._edited_widget;
            var widget_type = fn.widget_types[widget.type];
            fn.select_widget_color( def(widget, widget_type, 'color', null) );
        }

        nc.root.$.modal.close();
    };

    //--------------------------------------------------------------------------

    fn.widget_dialog = function(obj, widget){
        obj    = obj || null;
        widget = widget || null;

        if ( ! obj && nc.root('#mainViewButtons div.nc_dashboard_add_widget').hasClass('nc--disabled')) {
            return false;
        }

        var $root   = nc.root.$;
        var $dialog = $root('#nc_widget_dialog');

        if ( ! $dialog.length) {
            $root('body').append( $('#nc_widget_dialog') );
            $dialog = $root('#nc_widget_dialog');
        }

        var $submit             = $dialog.find('button[type=submit]');
        var $select_widget      = $dialog.find('select[name=widget_type]');
        var $color_widget       = $dialog.find('input[name=widget_color]');
        var $nc_widget_settings = $dialog.find('#nc_widget_settings');

        $select_widget.html('');
        for (var k in fn.widget_types) {
            var disabled = nc.is_undefined(fn.user_widgets[k]) ? '' : " disabled='disabled'";
            $select_widget.append("<option value='"+k+"'"+disabled+">"+fn.widget_types[k].title+"</option>");
        }

        if (widget) {
            $select_widget.val(widget.type);
            // fn.select_widget_color(widget.color);
        }

        fn._edited_widget_obj = obj;
        fn._edited_widget     = widget;


        $dialog.modal({
            closeHTML: "",
            containerId: 'nc_small_modal_container',
            onShow: function (dialog) {
                var $container = dialog.container;
                // $dialog    = dialog.data;
                $container.find('div.simplemodal-wrap').css({padding:0, overflow:'inherit'});

                $submit.click(function(){
                    fn.close_fullscreen();
                    var query = $nc_widget_settings.find('form').serialize();
                    $root.modal.close();

                    var widget_type  = $select_widget.val();
                    var widget_color = $color_widget.val();

                    if (widget) {
                        if (widget_type) {
                            widget.type  = widget_type;
                        }
                        if (widget_color) {
                            widget.color = widget_color;
                        }
                        gridster.remove_widget(obj, function(){
                            fn.add_widget(widget, query);
                        });
                    }
                    else {
                        fn.add_widget({type:widget_type, color:widget_color}, query);
                    }
                    return false;
                });

                $select_widget.change(function(){
                    var widget_type = fn.widget_types[this.value];

                    $nc_widget_settings.html('').hide();
                    $container.css({width:$dialog.width(), height:$dialog.height()});
                    $root(parent ? parent.window : window).resize();

                    fn.select_widget_color( def(widget, widget_type, 'color', null) );

                    if ( nc.key_exists('settings', widget_type) && widget_type.settings ) {
                        $.get(widget_type.controller + '?route=settings', function(data){
                            if (data) {
                                $nc_widget_settings.html(data).show();

                                if (widget && widget.query) {
                                    var form_data = fn.unserialize(widget.query);
                                    $nc_widget_settings.find('input,select').each(function(){
                                        if(this.name && widget_type(this.name, form_data)) {
                                            $(this).val(form_data[this.name]);
                                        }
                                    });
                                }
                            }
                            $container.css({width:$dialog.width(), height:$dialog.height()});
                            $root(parent ? parent.window : window).resize();
                        });
                    }

                }).trigger('change');

                $container.addClass('nc-shadow-large').css({width:$dialog.width(), height:$dialog.height()});
                $root(parent ? parent.window : window).resize();
            }
        });
        return false;
    };

    //--------------------------------------------------------------------------

    fn.select_widget_color = function(color) {
        color = color || 'lighten';
        var $root = nc.root.$;
        var $widget_palette = $root('#nc_widget_color_palette');
        $widget_palette.find('a.nc--selected').removeClass('nc--selected');
        $widget_palette.find('input[name=widget_color]').val(color);
        $widget_palette.find('span.nc--' + color).parent().addClass('nc--selected');

        if (fn._edited_widget_obj) {
            fn._edited_widget_obj.find('.nc-widget').attr('class' ,'nc-widget nc--' + color);
        }
        return false;
    };

    //--------------------------------------------------------------------------

    fn.unserialize = function(str){
        str = decodeURI(str);
        var pairs = str.split('&');
        var obj = {}, p, idx;
        for (var i=0, n=pairs.length; i < n; i++) {
            p = pairs[i].split('=');
            idx = p[0];

            if (idx.indexOf("[]") === (idx.length - 2)) {
                var ind = idx.substring(0, idx.length-2);
                if (obj[ind] === undefined) {
                    obj[ind] = [];
                }
                obj[ind].push(p[1]);
            }
            else {
                obj[idx] = p[1];
            }
        }
        return obj;
    };

    //--------------------------------------------------------------------------

    nc.ui.ext('dashboard', fn);

    //--------------------------------------------------------------------------

})(nc, nc.$);

