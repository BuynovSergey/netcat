/*
 * $Id: admin.js 8300 2012-10-29 14:42:06Z vadim $
 */

/**
 * Подгружается в админке
 */
(function($) {

    /**
   * IE перезагружает страницу для ссылок target='_top', чтобы этого не происходило
   * такие ссылки перехватываются:
   */
    $(function() {
        $('a[target="_top"]').click(function() {
            var href = $(this).attr('href'), hashPos = href.indexOf('#');
            if (hashPos != -1 && top.urlDispatcher) {
                top.urlDispatcher.load(href.substr(hashPos));
                return false;
            }
        });
    });

    /**
   * containsCI selector - case-insensitive contains()
   */
    $.expr[':'].containsCI = function(el, i, x){
        return $(el).text().toUpperCase().indexOf(x[3].toUpperCase()) >= 0;
    };

    ////////////////////////// Фильтрация строк  ////////////////////////////////
    /**
   * Собственно функция, которая фильтрует строки
   */
    var filterRows = function(table, values, clearFilterIcon) {
        var rows = table.find("tr:has(td)").hide(),
        totalRows = rows.size();
        for (var cls in values) {
            if (values[cls].length) {
                rows = rows.find("td." + cls + ":containsCI('" + values[cls].replace("'", "\'") + "')").parent();
            }
        }
        rows.show();
        if (rows.size() != totalRows) {
            clearFilterIcon.show();
        }
        else {
            clearFilterIcon.hide();
        }
    }

    /**
   * Получение значений инпутов в строке-фильтре
   */
    var getValues = function(inputs) {
        var values = {};
        inputs.each(function() {
            var el = $(this), name = el.attr('id').replace(/^filter_/, '');
            values[name] = el.val();
        });
        return values;
    }

    /**
   * $(filterDiv).createFilterFor($(table))
   *
   * Naming conventions
   * filterDiv должен иметь input, select, название которых начинается на
   * "filter_", остаток имени соответствует классу ячейки в таблице, по которой
   * ведется поиск;
   * span.reset -- содержит кнопку «сбросить фильтр»
   */
    $.fn.extend({
        createFilterFor: function(table) {
            var inputs = this.find("input, select"),
            clearFilterIcon = this.find("span.reset"),
            timeout = 500,
            timeoutId;

            var applyFilter = function() {
                filterRows(table, getValues(inputs), clearFilterIcon);
            }

            inputs.filter("select").change(applyFilter);
            inputs.filter("input").keyup(function() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(applyFilter, timeout);
            });

            clearFilterIcon.click(function() {
                inputs.val('');
                applyFilter();
            });
        }
    })
})(jQuery);

// ------------------------- ГЛОБАЛЬНЫЕ ФУНКЦИИ -------------------------------
function search_schedule(id) {
    var status = jQuery("<div />", {
        id: 'formAsyncSaveStatus',
        html: search_msg.rule_queue_loading,
        className: 'form_save_in_progress',
        css: {
            height: 'auto'
        }
    }
    ).appendTo(jQuery('body'));

    var removeStatus = function() {
        setTimeout(function() {
            status.fadeOut("slow", function() {
                status.remove();
            });
        }, 5000);
    };

    jQuery.ajax({
        url: '?view=indexing_add_schedule&area='+id,
        success: function(response) {
            if (!/^\s*1\s*$/.test(response)) {
                return this.fail();
            }
            status.html(search_msg.rule_queued)
            .addClass('form_save_ok').removeClass('form_save_in_progress');
            removeStatus();
        },
        fail: function(response) {
            status.html(search_msg.rule_queue_error)
            .addClass('form_save_error').removeClass('form_save_in_progress');
            removeStatus();
        }
    });
}

var search_indexer_window; // global (for the mainView frame)
function search_index_now(id) {
    if (search_indexer_window == undefined || search_indexer_window.closed) {
        search_indexer_window = window.open('indexing/?rule_id='+id,'indexing','width=420,height=550,resizable=yes,scrollbars=yes');
    }
    else {
        search_indexer_window.focus();
    }
}
