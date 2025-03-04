(function(nc){
    // JSHint:
    /* global nc */

    //=== PRIVATE ==============================================================

    var objects = {};
    var config = {
        ajax_path: (window.NETCAT_PATH || '/netcat/') + 'action.php?ctrl=system.form.fields.data_source.data_source'
    };
    var _disabled = 'nc--disabled',
        _active   = 'nc--active';

    //-------------------------------------------------------------------------

    // var log   = function() {return 'console' in window ? console.log.apply(console, arguments) : null;};
    var error = function() {return 'console' in window ? console.error.apply(console, arguments) : null;};
    var log   = function(){};

    //-------------------------------------------------------------------------

    var elem = function(tag, attr, child) {
        var elem = nc(document.createElement(tag));
        for (var k in attr) {
            elem.attr(k, attr[k]);
        }
        if (child) {
            elem.append(child);
        }
        return elem;
    };

    //-------------------------------------------------------------------------

    var html = function(tag, attr_array, content) {
        content = content || '';
        var attr = '';
        if (attr_array) {
            for (var k in attr_array) {
                attr += ' ' + k + '="'+attr_array[k]+'"';
            }
        }

        return '<' + tag + attr + '>' + content + '</' + tag + '>';
    };

    //-------------------------------------------------------------------------

    var data_source = (function(field_name){

        var nodes             = {};
        var field_value       = {};
        var content_is_loaded = {};

        //-------------------------------------------------------------------------

        var node = function() {
            var n = nodes;
            for (var i in arguments) {
                var k = arguments[i];
                if (k in n) {
                    n = n[k];
                } else {
                    return nc.$();
                }
            }

            return n;
        };

        //-------------------------------------------------------------------------

        var ajax = function(query, opt) {
            opt = opt || {};

            if (nc.is_string(query)) {
                query = {action: query};
            }

            var query_string = '';
            for (var k in query) {
                query_string += '&' + k + '=' + query[k];
            }

            opt.url = config.ajax_path + query_string;

            if (!opt.dataType) {
                opt.dataType = 'json';
            }
            if (!opt.type) {
                opt.type = 'POST';
            }

            log('ajax():', opt.url);

            return nc.$.ajax(opt);
        };

        //-------------------------------------------------------------------------

        // constructor
        var fn = function() {};

        //-------------------------------------------------------------------------

        fn.init = function(value) {

            var _selectors = [
                'container',
                'field',
                ['tabs', 'tabs>li'],
                ['contents', 'contents>div'],
                'source_path',
                'source_list',
                'source_subclass',
                'ordering_field',
                'filter_field',
                'bindings_tbody'
            ];

            for (var i in _selectors) {
                var key = _selectors[i];
                var selector = key;

                if (!nc.is_string(key)) {
                    selector = key[1];
                    key      = key[0];
                }
                nodes[key] = nc('#' + field_name + '_' + selector);
            }
            nodes.tab       = {};
            nodes.content   = {};
            nodes.tabs.each(function(index, elem){
                var key = nc(elem).data('keyword');
                if (key) {
                    nodes.tab[key] = nc(elem);
                }
            });
            nodes.contents.each(function(index, elem){
                var key = nc(elem).data('keyword');
                if (key) {
                    nodes.content[key]     = nc(elem);
                    content_is_loaded[key] = false;
                }
            });

            fn.set_value(value);

            fn.process('init', {
                data:{
                    field_value: field_value,
                    field_name:  field_name
                }
            });

            node('ordering_field')
                .val('order_by' in field_value ? field_value.order_by : '')
                .keyup(function(){
                    fn.set_value({order_by:this.value});
                });

            node('filter_field')
                .val('where' in field_value ? field_value.where : '')
                .keyup(function(){
                    fn.set_value({where:this.value});
                });
        };

        //-------------------------------------------------------------------------

        fn.process = function(query, conf) {
            conf = conf || {};

            if (!conf.success) {
                conf.success = fn.process_json;
            }

            conf.error = function(r){
                error(r.responseText);
            };

            ajax(query, conf);
        };

        //-------------------------------------------------------------------------

        fn.process_json = function(data) {
            var k;

            if ('call' in data) {
                for (k in data.call) {
                    var args = data.call[k];
                    if (nc.is_string(args)) {
                        args = [args];
                    }
                    fn[k].apply(fn, args);
                }
            }

            if ('action' in data) {
                for (k in data.action) {
                    fn.process(k, {data: data.action[k]});
                }
            }

            if ('tab_content' in data) {
                for (k in data.tab_content) {
                    fn.set_tab_content(k, data.tab_content[k]);
                }
            }

            if ('enable_tab' in data) {
                if (nc.is_string(data.enable_tab)) {
                    data.enable_tab = [data.enable_tab];
                }
                for (k in data.enable_tab) {
                    fn.enable_tab(data.enable_tab[k]);
                }
            }

            if ('select_tab' in data) {
                fn.select_tab(data.select_tab);
            }

            // Source tab
            if ('source_list' in data) {
                fn.render_list(node('source_list'), data.source_list);
            }

            if ('source_subclass' in data) {
                fn.render_list(node('source_subclass'), data.source_subclass);
            }

            if ('source_path' in data) {
                fn.render_path(node('source_path'), data.source_path);
            }

            // Binding tab
            if ('bindings_fields' in data) {
                fn.render_bindings(node('bindings_tbody'), data.bindings_fields);
            }

        };

        //-------------------------------------------------------------------------

        fn.set_value = function(value, replace) {
            replace = replace || false;
            value   = value || {};

            if (replace) {
                field_value = value;
            } else {
                for (var k in value) {
                    field_value[k] = value[k];
                }
            }

            node('field').val( JSON.stringify(field_value) );
        };

        //-------------------------------------------------------------------------

        fn.select_tab = function(key, elem) {
            if (elem && nc(elem).parents('li').hasClass(_disabled)) {
                return false;
            }

            if (node('tab', key).hasClass(_active)) {
                return false;
            }

            if (!content_is_loaded[key]) {
                fn.process(key, {data:{
                    field_value: field_value,
                    field_name:  field_name
                }});
                content_is_loaded[key] = true;
            }

            node('tabs').removeClass(_active);
            fn.enable_tab(key).addClass(_active);

            node('contents').slideUp();
            node('content', key).slideDown();

            return false;
        };

        //-------------------------------------------------------------------------

        fn.enable_tab = function(key) {
            return node('tab', key).removeClass(_disabled);
        };

        //-------------------------------------------------------------------------

        fn.disable_tab = function(key) {
            return node('tab', key).addClass(_disabled);
        };

        //-------------------------------------------------------------------------

        fn.alert = function(msg) {
            alert(msg);
        };

        //-------------------------------------------------------------------------

        fn.render_list = function(parent, list) {
            var ul = elem('ul', {class:'nc-list nc--hovered'});

            for (var i=0; i<list.length; i++) {
                ul.append(fn.make_list_item(list[i], true));
            }

            parent.html(ul);
        };

        //-------------------------------------------------------------------------

        fn.render_path = function(parent, list) {
            var ul = elem('ul', {class:'nc-path'});

            for (var i=0; i<list.length; i++) {
                ul.append(fn.make_list_item(list[i]));
            }

            parent.html(ul);
        };

        //-------------------------------------------------------------------------

        fn.make_list_item = function(row, actions) {
            if (actions === true) {
                actions = "<span class='nc-actions nc--on-hover'><span><i class='nc-icon nc--arrow-right'></i></span></span>";
            } else {
                actions = actions || '';
            }

            var icon    = 'icon' in row ? html('i', {class:'nc-icon nc--' + row.icon}) + ' ' : '';
            var link    = elem('a', {href:'#', class:'nc--blocked'}, icon + row.title + actions);
            link.click(function(){
                fn.process_json(row);
                return false;
            });
            return elem('li', false, link);
        };

        //-------------------------------------------------------------------------

        fn.render_bindings = function(parent, fields) {
            parent.html('');

            if (!('bindings' in field_value)) {
                field_value.bindings = {};
            }
            var onchange = function(){
                var name = nc(this).data('name');
                if (this.value) {
                    field_value.bindings[name] = this.value;
                } else {
                    delete(field_value.bindings[name]);
                }
                fn.set_value();
            };

            for (var k in fields) {
                var tr    = elem('tr');
                var input = elem('input', {type:'text', 'data-name':k}).keyup(onchange).change(onchange);
                if (k in field_value.bindings) {
                    input.val(field_value.bindings[k]);
                }
                tr.append('<td>' + k + '</td>');
                tr.append('<td>' + fields[k] + '</td>');
                tr.append(elem('td',false, input));
                parent.append(tr);
            }
        };

        //-------------------------------------------------------------------------

        fn.set_tab_content = function(key, content) {
            return node('content', key).html(content);
        };

        //-------------------------------------------------------------------------

        fn();

        return fn;
    });

    //-------------------------------------------------------------------------

    var fn = function(field_name) {
        if (typeof objects[field_name] === 'undefined') {
            objects[field_name] = data_source(field_name);
        }

        return objects[field_name];
    };

    //-------------------------------------------------------------------------

    fn.config = function(conf) {
        config = conf;
    };

    //-------------------------------------------------------------------------

    nc.ext('data_source', fn);

})(nc);
