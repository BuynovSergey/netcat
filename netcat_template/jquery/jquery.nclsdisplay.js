if (typeof(lsDisplayLibLoaded) == 'undefined') {
    var lsDisplayLibLoaded = true;
    var E_CLICK  = 0,
        E_SUBMIT = 1;

    jQuery(function(){
        var bindEvents = function($container){
            jQuery('[data-nc-ls-display-link]', $container).click(function(){
                eventHandler(this, true, E_CLICK);
                return false;
            });
            jQuery('form[data-nc-ls-display-form]', $container).submit(function(){
                eventHandler(this, true, E_SUBMIT);
                return false;
            });
        }

        var eventHandler = function(element, callBindEvents, event_type){

            switch (event_type) {

                case E_SUBMIT:
                    var url_attr  = 'action';
                    var data_attr = 'data-nc-ls-display-form';
                    break;

                case E_CLICK:
                default:
                    var url_attr  = 'href';
                    var data_attr = 'data-nc-ls-display-link';
                    break;
            }

            var $this    = jQuery(element);
            var url      = $this.attr(url_attr);
            var obj_data = $this.attr(data_attr);

            if (obj_data) {
                obj_data = jQuery.parseJSON(obj_data);
            }
            else {
                return false;
            }

            var replace_content = obj_data.subdivisionId !== false;

            if (url) {
                if (obj_data.displayType == 'shortpage' || (obj_data.displayType == 'longpage_vertical' && typeof(obj_data.subdivisionId) == 'undefined')) {

                    var send_as_post = event_type === E_SUBMIT && $this.attr('method').toLowerCase() === 'post';
                    var send_data    = jQuery.extend({}, obj_data.query); // clone

                    send_data.isNaked       = parseInt(typeof send_data.isNaked !== 'undefined' ? send_data.isNaked : 1);
                    send_data.lsDisplayType = obj_data.displayType;
                    send_data.skipTemplate  = parseInt(send_data.skipTemplate ? send_data.skipTemplate : obj_data.displayType == 'shortpage' && typeof(obj_data.subdivisionId) != 'undefined' ? 1 : 0);

                    if (send_as_post) {
                        url += (url.indexOf('?') >= 0 ? '&' : '?') + jQuery.param(send_data);
                        send_data = $this.serialize();
                    }

                    jQuery.ajax({
                        type:    send_as_post ? 'POST' : 'GET',
                        url:     url,
                        data:    send_data,
                        success: function(data){
                            var $container = [];

                            if (typeof(obj_data.onSubmit) !== 'undefined') {
                                if (data[0] == '{' || data[0] == '[') {
                                    data = jQuery.parseJSON(data);
                                }

                                if ((eval(obj_data.onSubmit)).call($this.get(0), data) === false) {
                                    replace_content = false;
                                }
                            }

                            if ( ! replace_content) {
                                return false;
                            }

                            if (typeof(obj_data.subdivisionId) == 'undefined') {
                                $container = $this.closest('[data-nc-ls-display-container]');
                            } else {
                                jQuery('[data-nc-ls-display-container]').each(function(){
                                    var $element = jQuery(this);
                                    var containerData = $element.attr('data-nc-ls-display-container');
                                    if (containerData) {
                                        containerData = jQuery.parseJSON(containerData);
                                        if (containerData.subdivisionId == obj_data.subdivisionId) {
                                            $container = $element;
                                            return false;
                                        }
                                    }

                                    return true;
                                });
                            }

                            if (!$container.length) {
                                $container = jQuery('[data-nc-ls-display-container]');
                            }

                            $container.html(data);

                            if (callBindEvents) {
                                bindEvents($container);
                            }

                            if (typeof(parent.nc_ls_quickbar) != 'undefined') {
                                var quickbar = parent.nc_ls_quickbar;
                                if (quickbar) {
                                    var $quickbar = jQuery('.nc-navbar').first();
                                    $quickbar.find('.nc-quick-menu LI:eq(0) A').attr('href', quickbar.view_link);
                                    $quickbar.find('.nc-quick-menu LI:eq(1) A').attr('href', quickbar.edit_link);
                                    $quickbar.find('.nc-menu UL LI:eq(0) A').attr('href', quickbar.sub_admin_link);
                                    $quickbar.find('.nc-menu UL LI:eq(1) A').attr('href', quickbar.template_admin_link);
                                    $quickbar.find('.nc-menu UL LI:eq(2) A').attr('href', quickbar.admin_link);
                                }
                            }
                        }
                    });

                } else if (obj_data.displayType == 'longpage_vertical') {
                    var scrolled = false;

                    var scrollToContainer = function(containerData, $element){
                        if (containerData) {
                            containerData = jQuery.parseJSON(containerData);
                            if (containerData.subdivisionId == obj_data.subdivisionId) {
                                jQuery('HTML,BODY').animate({
                                    scrollTop: $element.offset().top - jQuery('BODY').offset().top
                                }, containerData.animationSpeed);
                                return true;
                            }
                        }

                        return false;
                    };

                    jQuery('[data-nc-ls-display-pointer]').each(function(){
                        var $element = jQuery(this);
                        if (scrollToContainer($element.attr('data-nc-ls-display-pointer'), $element)) {
                            scrolled = true;
                            return false;
                        }

                        return true;
                    });

                    if (!scrolled) {
                        jQuery('[data-nc-ls-display-container]').each(function(){
                            var $element = jQuery(this);

                            if (scrollToContainer($element.attr('data-nc-ls-display-container'), $element)) {
                                return false;
                            }

                            return true;
                        });
                    }
                }

                if (replace_content) {
                    if (!!(window.history && history.pushState)) {
                        window.history.pushState({}, '', url);
                    }
                }

                if (event_type === E_CLICK) {
                    if (typeof(obj_data.onClick) == 'undefined') {
                        $this.addClass('active').siblings().removeClass('active');
                    } else {
                        eval('var callback = ' + obj_data.onClick);
                        callback.call($this.get(0));
                    }
                }

                return false;
            }
        }

        jQuery('[data-nc-ls-display-link]').click(function(){
            eventHandler(this, true, E_CLICK);
            return false;
        });

        jQuery('form[data-nc-ls-display-form]').submit(function(){
            eventHandler(this, true, E_SUBMIT);
            return false;
        });

        jQuery('[data-nc-ls-display-pointer]').each(function(){
            var $this = jQuery(this);
            var data = jQuery.parseJSON($this.attr('data-nc-ls-display-pointer'));
            if (data.onReadyScroll) {
                setTimeout(function(){
                    jQuery('HTML,BODY').scrollTop($this.offset().top);
                }, 1000);
                return false;
            }

            return true;
        });
    });
}