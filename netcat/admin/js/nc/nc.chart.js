(function(nc) {
    /*
     Обертка для класса генерации диаграмм: http://www.flotcharts.org/
     */

    // JSHint:
    /* global nc */

    //=== PRIVATE ==============================================================

    var flot_folder = nc.config('admin_path') + 'js/flot/';

    var required_js = [
        flot_folder + 'jquery.flot.min.js',
        flot_folder + 'jquery.flot.nc_categories.min.js',
        flot_folder + 'jquery.flot.pie.js'
    ];

    var config = {};
    var $chart, $chart_elem, $table_elem, $legend, $tooltip;
    var first_resize_timeout;

    var default_config = {
        months: 'January February March April May June July August September October November December',
        months_short: 'Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec',
        series: {
            lines: {
                show: false,
                steps: false
            },
            points: {show: false},
            bars: {
                show: true,
                lineWidth: 0,
                barWidth: 0.9,
                align: "center",
                fill: true,
                fillColor: {colors: [{opacity: 0.7}, {opacity: 0.2}]}
            }
        },
        grid: {
            hoverable: true,
            color: "#CCC",
            // backgroundColor: "#FFF",
            borderWidth: 1, //number or object with "top", "right", "bottom" and "left" properties with different widths
            borderColor: '#DDD',
            margin: {top: 20, left: 20, right: 20, bottom: 10},
            // labelMargin: number
            axisMargin: 10
        },
        shadowSize: 0,
        margin: 30,
        legend: {
            // show: true,
            labelFormatter: function(label, series) {
                var html = '<li><i style="background-color:' + series.color + '"></i>' + label + '</li> ';
                $legend.append(html);
            }
        },
        xaxis: {
            mode: 'nc_categories',
            tickLength: 0,
            tickFormatter: function(value) {
                return formatter(config.nc_xaxis_format, value, {'short': true});
            }
        },
        yaxis: {
            tickFormatter: function(value) {
                return formatter(config.nc_yaxis_format, value, {'short': true});
            }
        }
    };

    var def = function(json, key, def) {
        if (nc.key_exists(key, json)) {
            return json[key];
        }
        return def;
    };

    var merge_json = function(a, b) {
        a = a || {};
        b = b || {};
        var result = {};

        // clone
        for (var k in a) {
            result[k] = a[k];
        }

        // merge
        for (k in b) {
            result[k] = b[k];
        }

        return result;
    };

    var weeks_to_date = function(y, w, d) {
        var days = 2 + d + (w - 1) * 7 - (new Date(y, 0, 1)).getDay();
        return new Date(y, 0, days);
    };
    var month_name = function(month, abbreviate) {
        abbreviate = abbreviate || false;
        return config[abbreviate ? 'months_short' : 'months'][parseInt(month) - 1];
    };

    var formatter = function(format, value, options) {
        options = options || {};
        switch (format) {
            case 'price':
                return (value + '').replace(/\d(?=(\d{3})+$)/g, '$&&thinsp;') + '&nbsp;' + def(config, 'nc_currency', '');
            case 'date':
                var parts = value.split('.');
                switch (parts.length) {
                    case 1: // week (36* 2015) * - week no.
                        parts = value.split(' ');
                        var w = parts[0], y = parts[1];
                        var date_a = weeks_to_date(y, w, 0);
                        var date_b = weeks_to_date(y, w, 6);
                        parts = [];
                        if (date_a.getMonth() === date_b.getMonth()) {
                            parts.push(date_a.getDate() + '-' + date_b.getDate());
                            parts.push(month_name(date_a.getMonth() + 1, options['short']));
                        }
                        else {
                            parts.push(date_a.getDate());
                            parts.push(month_name(date_a.getMonth() + 1, options['short']));
                            parts.push('-');
                            parts.push(date_b.getDate());
                            parts.push(month_name(date_b.getMonth() + 1, options['short']));
                        }
                        parts.push('<br>');
                        parts.push(date_b.getFullYear());

                        break;

                    case 2: // month (09.2015)
                        parts[0] = month_name(parts[0], options['short']);
                        break;
                    case 3: // day (17.09.2015)
                        parts[1] = month_name(parts[1], options['short']);
                        break;
                }
                return parts.join(' ');
            default:
                return value;
        }
    };

    //=== PUBLIC ===============================================================

    var _chart = function(obj, data, chart_config) {
        config = merge_json(default_config, chart_config);
        $chart = obj.html('');

        $chart_elem = nc('<div class="nc-chart-canvas"></div>').appendTo($chart);
        $table_elem = nc('<div class="nc-chart-table" style="display:none; position:relative; padding:0"></div>').appendTo($chart);

        config.months = config.months.toLowerCase().split(' ');
        config.months_short = config.months_short.toLowerCase().split(' ');

        if (typeof config.series.pie === "undefined" || config.series.pie.show === false) {
            var $panel = nc("<div class='nc-chart-legend'></div>").appendTo($chart);
            var $toggle = nc('<a href="#" class="nc-icon nc--right nc--dev-system-tables nc--hovered"></a>').appendTo($panel);
            $legend = nc("<ul></ul>").appendTo($panel);

            // Переключатель между таблицей и графиком
            $toggle.click(function() {
                var $chart = nc(this).parents('div.nc-chart');
                //$chart.find('div.nc-chart-table').slideToggle();
                //console.log($chart.find('div.nc-chart-canvas'));

                nc(this).toggleClass('nc--hovered');
                $chart.find('div.nc-chart-canvas').toggle();
                $chart.find('div.nc-chart-table').toggle();
                return false;
            });
        }

        // Размеры графика
        if (nc.key_exists('width', config) || !$chart.width()) {
            $chart.width(def(config, 'width', 600));
        }
        if (nc.key_exists('height', config)) {
            var height = def(config, 'height', 300);
            $chart_elem.height(height);
            $table_elem.css({height: height, overflow: 'auto'});
        }

        // Генерируем таблицу
        if ('data' in data[0]) {
            var table = '<table class="nc-table nc--small nc--striped nc--wide">';
            // thead
            table += '<tr>';
            table += '<th width="1%" class="nc--nowrap"></th>';
            for (var i in data) {
                table += '<th width="1%" class="nc--nowrap">' + data[i].label + '</th>';
            }
            table += '<th></th></tr>';

            // tbody
            var label, value;
            for (i in data[0].data) {
                label = data[0].data[i][0];
                table += '<tr>';
                table += '<td class="nc--nowrap">' + formatter(config.nc_xaxis_format, label).replace('<br>', ' ') + '</td>';
                for (var j in data) {
                    value = data[j].data[i][1];
                    table += '<td class="nc--nowrap nc-text-right">' + formatter(config.nc_yaxis_format, value) + '</td>';
                }

                table += '<td></td></tr>';
            }
            table += '</table>';

            $table_elem.html(table);
        }

        // Инициализация графика
        try {
            $chart_elem.plot(data, config);
        }
        catch (e) {}
        chart.hoverable($chart_elem);
    };

    var chart = function(obj, data, chart_config) {
        var init = function() {
            _chart(obj, data, chart_config);
        };

        init();
        nc(window).resize(init);

        clearTimeout(first_resize_timeout);
        first_resize_timeout = setTimeout(function() {
            nc(window).resize();
        }, 300);
    };

    //--------------------------------------------------------------------------

    chart.__init = function() {
        for (var k in required_js) {
            nc('head').append('<script src="' + required_js[k] + '"></script>');
        }

        nc(function() {
            $tooltip = nc("<div id='nc_chart_tooltip' class='nc-popover nc--bc nc--hidden nc--hide'></div>")
                .css({position: 'absolute', width: 180})
                .appendTo('body');
        });
    };

    //-------------------------------------------------------------------------

    chart.set_defaults = function(defaults) {
        default_config = merge_json(default_config, defaults);
    };

    //-------------------------------------------------------------------------

    chart.hoverable = function(selector) {
        var html;
        var x_format = config.nc_xaxis_format;
        var y_format = config.nc_yaxis_format;
        nc(selector).bind("plothover", function(event, pos, item) {

            if (item) {
                var x_offset = Math.round($tooltip.outerWidth() / 2);
                var y_offset = Math.round($tooltip.outerHeight()) + 15;
                if (typeof item.series.pie === "undefined" || item.series.pie.show === false) {
                    html = item.series.label + ': ' +
                        formatter(y_format, item.datapoint[1].toFixed(0)) + "<br>" +
                        formatter(x_format, item.series.data[item.datapoint[0]][0]).replace('<br>', ' ');
                    $tooltip.html(html)
                        .css({
                            top: item.pageY - y_offset,
                            left: item.pageX - x_offset
                        })
                        .fadeIn(200);
                }
                else {
                    $tooltip.html(item.series.label + ": " + item.datapoint[1][0][1].toFixed(2))
                        .css({
                            top: pos.pageY - y_offset,
                            left: pos.pageX + x_offset
                        })
                        .fadeIn(200);
                }
            }
            else {
                $tooltip.hide();
            }
        });
    };

    //--------------------------------------------------------------------------

    nc.ext('chart', chart);

})(nc);
