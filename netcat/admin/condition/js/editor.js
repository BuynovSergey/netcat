/**
 * Редактор условий
 */
window.nc_condition_editor = class {
    // ↑ не просто "class nc_condition_editor" из-за возникающей при загрузке через nc.load_script ошибки
    //   (т. к. класс получается локальным внутри замыкания)

    // ****** Static properties ******
    // Cache results from server (key = url)
    static dataCache = {};

    // Store URLs for which requests are in progress (prevent duplicate requests when editor.load() is called)
    static loadingInProgress = {};

    // ****** PROPERTIES ******
    strings = {};
    // Ключ в url_params равен параметру в пути (%key%); значение либо фиксированное, либо объект { selector: '' } для
    // получения поля в форме, содержащего значение
    urlParams = {};
    root = null;
    inputField = null;
    isInitializing = false;
    defaultChosenParams = { disable_search_threshold: 10, no_results_text: this.str('SEARCH_NO_RESULTS') };
    conditionGroupsToShow = [];
    conditionGroupsToExclude = [];
    conditionsToExclude = [];

    // URLs to fetch miscellaneous data
    urls = {};

    // Object properties (i.e. fields)
    objectProperties = {};

    opEqNe = {
        name: 'op', type: 'SimpleSelect', values: {
            eq: this.str('EQUALS'),
            ne: this.str('NOT_EQUALS')
        }
    };
    opContains = {
        name: 'op', type: 'SimpleSelect', values: {
            contains: this.str('CONTAINS'),
            notcontains: this.str('NOT_CONTAINS')
        }
    };
    opString = {
        name: 'op', type: 'SimpleSelect', values: {
            eq: this.str('EQUALS'),
            ne: this.str('NOT_EQUALS'),
            contains: this.str('CONTAINS'),
            notcontains: this.str('NOT_CONTAINS'),
            begins: this.str('BEGINS_WITH')
        }
    };
    opNumber = {
        name: 'op', type: 'SimpleSelect', values: {
            ge: this.str('GREATER_OR_EQUALS'),
            gt: this.str('GREATER_THAN'),
            le: this.str('LESS_OR_EQUALS'),
            lt: this.str('LESS_THAN'),
            eq: this.str('EQUALS'),
            ne: this.str('NOT_EQUALS')
        }
    };
    opDateTime = this.opNumber;
    opAll = $nc.extend(true, {}, this.opString, this.opNumber);

    /**
     * Типы условий в редакторе
     * NB! записи должны быть отсортированы по группе
     */
    conditionTypes = {};

    /**
     * @constructor
     * @param {Object} options
     *      container: selector for the condition editor container
     *      input_name: name of the hidden input field (will be created)
     *      conditions: conditions to set
     *      url_params: keys to replace in server URLs
     *      groups_to_show: array with the names of the condition groups that will be shown
     *      groups_to_exclude: array with the names of the condition groups that won't be shown
     *      conditions_to_exclude: array with the names of the conditions to exclude
     */
    constructor(options) {
        // Set options
        this.strings = options.strings || {};
        this.urlParams = options.url_params || {};
        this.root = $nc(options.container).addClass('nc-condition-editor');
        this.inputField = $nc('<input type="hidden" name="' + options.input_name + '"/>').appendTo(this.root);

        var data = options.conditions;
        if (data && !$nc.isEmptyObject(data)) {
            this.inputField.val(JSON.stringify(data));
        }
        this.conditionGroupsToShow = options.groups_to_show || [];
        this.conditionGroupsToExclude = options.groups_to_exclude || [];
        this.conditionsToExclude = options.conditions_to_exclude || [];

        // Init (delayed to apply inherited properties which are not available in this constructor)
        setTimeout(() => this.load(data), 1);

        $nc(window).resize($nc.proxy(this, 'updateAllConditionLabelPositions'));

        this.root.data('nc-condition-editor', this);
    }

    /**
     * Возвращает объект, который приводится к строке как this.strings[key]
     */
    str(key) {
        return { toString: () => this.strings[key], isString: true };
    }

    // ****** Helper methods ******

    /**
     * Returns the element's top coordinate relative to the body
     * @param {HTMLElement} el
     * @returns Number
     */
    getOffsetTop(el) {
        if (!el.getBoundingClientRect) {
            return $nc(el).offset();
        }
        var body = document.body,
            docEl = document.documentElement,
            scrollTop = window.pageYOffset || docEl.scrollTop || body.scrollTop,
            clientTop = docEl.clientTop || body.clientTop || 0;
        return Math.round(el.getBoundingClientRect().top + scrollTop - clientTop);
    };

    /**
     * Get default parameter value (stored in the parent $nc(.condition) data)
     * Used in Instance.reinitializeChosenSelects
     */
    getInitialParamValue(input) {
        var values = input.closest('.condition').data('initialValues') || {};
        return values[input.attr('name')];
    };

    /**
     * Scrolls the "Chosen" element into view (handler for the "chosen:showing_dropdown" event)
     */
    scrollChosenIntoView(event, eventData) {
        var chosen = eventData.chosen,
            editor = this;
        setTimeout(function () {
            // .scrollIntoView() results in unwanted behaviour
            var dropdown = chosen.dropdown,
                dropdownBottom = editor.getOffsetTop(dropdown[0]) + dropdown.outerHeight(), // jQuery offset() produces wrong results
                body = $nc('body, html'),
                bodyBottom = body.scrollTop() + $nc(window).height(),
                scrollDelta = dropdownBottom - bodyBottom;

            if (scrollDelta > 0) {
                var newScrollTop = Math.min(
                    body.scrollTop() + scrollDelta,
                    editor.getOffsetTop(chosen.container[0]) // do not scroll further than the "chosen" select itself
                );

                body.animate({ scrollTop: newScrollTop }, 400);
                body.css('height', dropdownBottom + 'px');
            }

        }, 100);
    };

    /**
     *
     */
    calculateHash(str) {
        var hash = 0;
        for (var i = 0, len = str.length; i < len; i++) {
            hash = (hash << 5) - hash + str.charCodeAt(i);
            hash &= hash;
        }
        return hash.toString(36).replace('-', 'N');
    };

    /**
     * @param {Object} condition
     * @returns {boolean} условие является группой (и, или)
     */
    isGroup(condition) {
        return condition.type === 'and' || condition.type === 'or';
    };

    /**
     *
     */
    makeSubdivisionField(fieldName) {
        return {
            name: fieldName || 'value',
            type: 'SelectFromJson',
            source: this.urls.SelectSubdivisionList,
            placeholder: this.str('SELECT_SUBDIVISION')
        };
    };

    /**
     *
     */
    makeObjectField(fieldName) {
        return {
            name: fieldName || 'value',
            type: 'SelectFromJson',
            source: this.urls.SelectObjectList,
            placeholder: this.str('SELECT_OBJECT')
        };
    };

    // ****** METHODS ******
    /**
     *
     */
    load(input) {
        this.isInitializing = true;

        var conditionTree = input || {};
        if (typeof input === 'string') {
            conditionTree = (JSON && JSON.parse) ? JSON.parse(input) : eval('(' + input + ')');
        }
        // clear the editor
        this.root.find('.condition-group').first().remove();
        this.addGroup(this.root, conditionTree);

        this.isInitializing = false;

        var editor = this;

        $nc(function () {
            editor.updateAllConditionLabelPositions();
            editor.root.addClass('nc--enable-transitions');
        });
    };

    /**
     *
     */
    refresh() {
        const jsonResult = JSON.stringify(this.save());
        this.load(jsonResult);
    };

    /**
     *
     */
    save() {
        var rootGroupContainer = this.root.children('.condition-group'),
            result = this.getGroupData(rootGroupContainer),
            jsonResult = result ? JSON.stringify(result) : '';

        this.inputField.val(jsonResult);

        return result;
    };

    /**
     *
     */
    onFormSubmit() {
        if (!this.checkConditions()) {
            return false;
        } else {
            this.save();
            return true;
        }
    };

    /**
     * @return {Object|null}
     */
    getGroupData(groupContainer) {
        var groupType = groupContainer.find('.operator-cell select').val(),
            result = { type: groupType, conditions: [] },
            allConditions = groupContainer.find('.condition-cell').first(),
            conditionsAndGroups = allConditions.children('.condition, .condition-group'),
            editor = this;

        if (conditionsAndGroups.length === 0) {
            return null; // empty group!
        }

        conditionsAndGroups.each(function () {
            var el = $nc(this),
                output = el.hasClass('condition-group')
                         ? editor.getGroupData(el)
                         : editor.getConditionData(el);
            if (output) {
                result.conditions.push(output);
            }
        });

        return result;
    };

    /**
     *
     * @return {Object|null}
     */
    getConditionData(conditionContainer) {
        var values = conditionContainer.data('initialValues'); // default values, in case not all conditions selects are "initialized" (loaded)
        var invalidParams = false;

        conditionContainer.find('.condition-param-value').not('.condition-param-value-initializing').each(function () {
            var $this = $nc(this);
            var value = $this.val();
            if (value === '' || value == null) {
                if (console) {
                    console.warn('Condition parameter %s has no value, the condition is ignored (type=%s)', this.name, values.type);
                }
                invalidParams = true;
                return false;
            } else {
                if ($this.hasClass('condition-param-value-date') && $this.datepicker) {
                    value = $this.datepicker('getISODate') || value;
                }
                values[this.name] = value;
            }
            return true;
        });

        values.type = conditionContainer.data('conditionType');

        if (invalidParams) {
            return null;
        }
        return values;
    };

    /**
     *
     * @return boolean
     */
    checkConditions() {
        var isOk = true;
        var editor = this;
        this.root.find('.condition-param-value').not('.condition-param-value-initializing').each(function () {
            var $this = $nc(this);
            var value = $this.val();
            if (value === '' || value == null) {
                isOk = false;

                // show an ugly alert
                var firstStringField = $this.closest('.condition').find('.condition-string').first().text();
                alert(editor.str('VALUE_REQUIRED').toString().replace('%s', firstStringField));

                // and focus the associated input element
                if ($this.is('select')) {
                    $this.data('chosen').selected_item.focus();
                } else if ($this.is('input:hidden')) {
                    $this.siblings('a.condition-param-popup').focus();
                } else {
                    $this.focus();
                }

                return false; // exit .each()
            }
        });
        return isOk;
    };

    /**
     *
     */
    addGroup(parentContainer, values) {
        var editor = this,
            group = $nc("<div class='condition-group'>" +
                "<div class='condition-group-container'>" +
                "<div class='operator-cell'>" +
                "<div class='operator-label nc-label nc--blue'>" + this.str('AND') + "</div>" +
                "<select class='operator-type'>" +
                "<option value='and'>" + this.str('AND_DESCRIPTION') + "</option>" +
                "<option value='or'>" + this.str('OR_DESCRIPTION') + "</option>" +
                "</select>" +
                "<a class='nc-icon-s nc--remove remove-group-button'></a>" +
                "</div>" +
                "<div class='condition-cell'></div>" +
                "<div class='buttons-cell'>" +
                "<a class='add-condition-button'>" + this.str('ADD') + "</a>" +
                "</div>" +
                "</div>" +
                "</div>"),
            insertionPoint = group.find('.condition-cell'),
            typeSelect = group.find('select');

        group.find('.add-condition-button')
            .click(function () {
                editor.showAddConditionDialog(insertionPoint);
            });

        group.find('.remove-group-button')
            .click(function () {
                editor.removeGroup(group);
            })
            .attr('title', this.str('REMOVE_GROUP'));

        group.find('.operator-type')
            .change(function () {
                editor.updateConditionLabelText($nc(this))
            });

        group.find('.operator-label')
            .click(function () {
                editor.changeOperator($nc(this));
            });

        var parentGroups = parentContainer.parents('.condition-group'),
            isRoot = !parentGroups.length;

        if (isRoot) {
            group.addClass('root');
        }

        if (parentGroups.length % 2) {
            group.find('.condition-group-container').addClass('even-level');
        }

        parentContainer.append(group);

        typeSelect.chosen(this.defaultChosenParams);

        if (!$nc.isEmptyObject(values)) {
            typeSelect.val(values.type).change().trigger('chosen:updated');
            var last = values.conditions.length;
            for (var i = 0; i < last; i++) {
                var condition = values.conditions[i];
                if (this.isGroup(condition)) {
                    this.addGroup(insertionPoint, condition);
                } else {
                    this.addCondition(condition.type, insertionPoint, condition);
                }
            }
            if (isRoot && last === 1 && !this.isGroup(values.conditions[0])) {
                group.find('.operator-cell').hide();
            }
        } else if (isRoot) {
            group.find('.operator-cell').hide();
        }

        this.updateOperatorLabelPosition(parentGroups.first());
    };

    /**
     *
     */
    showAddConditionDialog(target) {
        var options = [
                '<option></option>',
                '<option value="addGroup" class="create-group-option">' + this.str('ADD_GROUP') + '</option>'
            ],
            prevGroup = null;
        for (var typeName in this.conditionTypes) {
            var conditionData = this.conditionTypes[typeName],
                groupsToShow = this.conditionGroupsToShow;

            // skip if the group of this condition is NOT in the this.conditionGroupsToShow
            if (groupsToShow.length && $nc.inArray(conditionData.group, groupsToShow) === -1) {
                continue;
            }

            // skip if the group of this condition is in the this.conditionGroupsToExclude
            if ($nc.inArray(conditionData.group, this.conditionGroupsToExclude) !== -1) {
                continue;
            }

            // skip the condition if it's 'name' is in the this.conditionsToExclude
            if ($nc.inArray(typeName, this.conditionsToExclude) !== -1) {
                continue;
            }

            if (prevGroup !== conditionData.group) {
                if (prevGroup != null) {
                    options.push("</optgroup>");
                }
                options.push('<optgroup label="' + this.str(conditionData.group) + '">');
                prevGroup = conditionData.group;
            }
            options.push('<option value="' + typeName + '">' + this.str(conditionData.label) + '</option>');
        }
        options.push("</optgroup>");

        var select = $nc('<select data-placeholder="' + this.str('SELECT_CONDITION_TYPE') + '">' + options.join('') + '</select>'),
            tmpDiv = $nc('<div class="add-condition-type-select"></div>').append(select).appendTo(target),
            editor = this;

        select.chosen(this.defaultChosenParams)
            .on('chosen:hiding_dropdown', function () {
                var conditionTypeName = select.val(),
                    parentContainer = select.closest('.condition-cell');
                // remove the <select> when the dropdown is closed (in any case)
                select.parents('.add-condition-type-select').remove();
                // add the condition line if something was selected
                if (conditionTypeName === 'addGroup') {
                    editor.addGroup(parentContainer, null);
                } else if (conditionTypeName) {
                    editor.addCondition(conditionTypeName, parentContainer, {});
                }
            })
            .on('chosen:showing_dropdown', $nc.proxy(this, 'scrollChosenIntoView'));

        setTimeout(function () {
            tmpDiv.find('.chosen-container').trigger('mousedown');
        }, 1); // open the <select>
    };

    /**
     *
     */
    updateAllConditionLabelPositions() {
        var editor = this;
        this.root.find('.condition-group').each(function () {
            editor.updateOperatorLabelPosition($nc(this));
        });
    };

    /**
     *
     */
    updateOperatorLabelPosition(groupContainer) {
        if (this.isInitializing) {
            return;
        }

        var conditionCell = groupContainer.find('.condition-cell').first(),
            conditions = conditionCell.children('.condition, .condition-group'),
            operatorCell = groupContainer.find('.operator-cell').first(),
            operatorLabel = operatorCell.find('.operator-label');

        if (conditions.length > 1) {
            if (groupContainer.is('.root') && !operatorCell.is(':visible')) {
                operatorCell.slideDown(400, $nc.proxy(this, 'updateAllConditionLabelPositions')).show();
                return;
            }

            var wasVisible = operatorLabel.hasClass('nc--visible'),
                first = conditions.first(),
                firstTop = first.position().top,
                last = conditions.last(),
                lastTop = last.position().top;

            var top = 20 + firstTop + (lastTop - firstTop) / 2;

            if (first.is('.condition-group') || last.is('.condition-group')) {
                top += 5;
            }

            top = Math.min(firstTop + $nc(window).height() - 100, Math.round(top)) + 'px';

            operatorLabel.addClass('nc--visible');
            if (wasVisible) {
                setTimeout(function () {
                    operatorLabel.css('top', top);
                }, 1); // trigger transition animation
            } else {
                operatorLabel.css('top', top);
            }
        } else {
            operatorLabel.removeClass('nc--visible');
            if (groupContainer.is('.root') && operatorCell.is(':visible')) {
                operatorCell.slideUp(400, $nc.proxy(this, 'updateAllConditionLabelPositions'));
            }
        }
    };

    /**
     *
     */
    updateConditionLabelText(select) {
        var label = this.str(select.val().toUpperCase()).toString();
        select.closest('.operator-cell').find('.operator-label').html(label);
    };

    /**
     *
     */
    changeOperator(operatorLabel) {
        var operatorSelect = operatorLabel.closest('.operator-cell').find('.operator-type');

        operatorSelect
            .val(operatorSelect.val() === 'and' ? 'or' : 'and')
            .change()
            .trigger('chosen:updated');
    };

    /**
     *
     */
    addCondition(conditionType, container, values) {
        /**
         * Markup for the condition:
         * <div class="condition condition-[type]" data-conditionType=[type] data-initialValues=[values]>
         *      <span class="condition-string">String</span>
         *      <span class="condition-param">
         *          ...
         *          <input class="condition-param-value" name="paramName">   ← class is required!
         *              ↑ may have 'data-fieldParams' = { field params }
         *          ...
         *      </span>
         * </div>
         *
         */

        var conditionData = this.conditionTypes[conditionType],
            conditionDiv = $nc('<div />', {
                'class': 'condition condition-' + conditionType,
                data: {
                    conditionType: conditionType,
                    initialValues: values // store values for the fields which require initialization
                }
            }).appendTo(container),
            editor = this;

        this.addFields(conditionDiv, conditionData.params, values);

        $nc('<a class="nc-icon-s nc--remove remove-condition-button"></a>')
            .click(function () {
                editor.removeCondition($nc(this).closest('.condition'));
            })
            .attr('title', this.str('REMOVE_CONDITION'))
            .appendTo(conditionDiv);

        this.updateOperatorLabelPosition(container.closest('.condition-group'));

        if (!this.isInitializing) {
            conditionDiv.find('input, select').not(':hidden').first().focus();
        }
    };

    /**
     *
     * @param target
     * @param {Array}  params
     * @param {Object} values
     */
    addFields(target, params, values) {
        var editor = this,
            hasValues = (values && !$nc.isEmptyObject(values));

        for (var i = 0, len = params.length; i < len; i++) {
            var param = params[i];
            if (typeof param === 'string' || param.isString) {
                target.append('<span class="condition-string">' + param + '</span>');
            } else {
                // call appendXyzField()
                var methodName = 'append' + param.type + 'Field';
                if (this[methodName] === undefined) {
                    console.warn("Incorrect field type '%s': no %s method", param.type, methodName);
                    continue;
                }
                var field = this[methodName](target, param, hasValues ? values[param.name] : param.value);
                field.wrap($nc('<span/>', { 'class': 'condition-param' }));
            }
        }

        // init chosen
        target.find('select')
            .chosen(this.defaultChosenParams)
            .on('chosen:showing_dropdown', $nc.proxy(this, 'scrollChosenIntoView'))
            .each(function () {
                // load select values where needed
                var select = $nc(this);
                if (select.data('loadData')) {
                    var fieldParams = select.data('fieldParams') || {},
                        callback = $nc.proxy(editor, 'on' + fieldParams.type + 'ListReady'),
                        script = fieldParams.source || editor.urls[fieldParams.type + 'List'],
                        url = editor.makeUrl(script, fieldParams.requestParams);

                    editor.loadData(url, callback, select.data('callbackParams'));
                }
            });

        // init datepicker
        target.find('input.condition-param-value-date').datepicker();
    };

    /**
     *
     */
    removeGroup(groupContainer) {
        var hasConditions = groupContainer.find('.condition').length > 0,
            confirmationText = groupContainer.hasClass('root')
                                ? this.str('REMOVE_ALL_CONFIRMATION')
                                : this.str('REMOVE_GROUP_CONFIRMATION');

        if (!hasConditions || confirm(confirmationText)) {
            groupContainer.remove();
            if (!this.root.find('.condition-group').length) {
                this.addGroup(this.root, null);
            } else {
                this.updateAllConditionLabelPositions();
            }
        }
    };

    /**
     *
     */
    removeCondition(conditionContainer) {
        // if not all values have been set, do not bother to confirm
        var skipConfirmation = false;
        conditionContainer.find('.condition-param-value').not('.condition-param-value-initializing').each(function () {
            var value = $nc(this).val();
            if (value === '' || value == null) {
                skipConfirmation = true;
                return false; // exit .each()
            }
        });

        // prepare condition description for the confirmation dialog
        var conditionText = [];
        conditionContainer.find('.condition-string, .condition-param-value:not([type=hidden]), .essence-caption').each(function () {
            var $this = $nc(this);
            if ($this.is('.condition-string, .essence-caption')) {
                conditionText.push($this.text());
            } else if ($this.is('select')) {
                conditionText.push('[' + $nc.trim($this.find('option:selected').text()) + ']');
            } else {
                conditionText.push($this.val());
            }
        });

        if (skipConfirmation || confirm(this.str('REMOVE_CONDITION_CONFIRMATION').toString().replace('%s', conditionText.join(' ')))) {
            conditionContainer.remove();
            this.updateAllConditionLabelPositions();
        }
    };

    /********* Condition field generators *********/
    /**
     * Example: method signature for for field type = 'Xxx':
     * appendXxxField(target, params, value) {}
     */

    /**
     *
     */
    appendSimpleSelectField(target, params, value) {
        var html = ['<select name="' + params.name + '">'];
        for (var optionValue in params.values) {
            html.push('<option value="' + optionValue + '">' + params.values[optionValue] + "</option>");
        }
        html.push("</select>");

        var select = $nc(html.join('')).addClass('condition-param-value');
        if (value || value === 0) {
            select.val(value); // sic (otherwise wrong value might be set)
        }

        return select.appendTo(target);
    };

    /**
     *
     */
    createListSelectField(fieldParams, selectValue, placeholder) {
        return $nc('<select />', {
            name: fieldParams.name,
            'class': 'condition-param-value condition-param-value-initializing condition-param-' + fieldParams.type
        }).data({
            loadData: true,
            fieldParams: fieldParams
            // placeholder: placeholder
        }).attr('data-placeholder', placeholder);
    };

    // Convention for <select> fields which load data from the server:
    // below: [TYPE] = param.type
    //      <select class='condition-param-value condition-param-[TYPE]>
    //      server script:      this.urls.[TYPE]List    (or fieldParams.source if it is set)
    //      ajax data handler:  this.on[TYPE]ListReady

    /**
     *
     */
    appendSelectObjectPropertyField(target, params, value) {
        return this.createListSelectField(params, value, this.str('SELECT_OBJECT_FIELD'))
            .appendTo(target);
    };

    /**
     *
     */
    appendSelectFromClassifierField(target, params, value) {
        var classifier = params.requestParams.classifier;
        return this.createListSelectField(params, value, this.str('SELECT_VALUE'))
            .data('callbackParams', { classifier: classifier })
            .addClass('condition-param-SelectFromClassifier-' + classifier)
            .appendTo(target);
    };

    /**
     *
     */
    appendObjectPropertyOptionsField(target, params, value) {
        return $nc('<span class="condition-objectproperty-variable-part" />').appendTo(target);
    };

    /**
     *
     */
    appendInputField(target, params, value) {
        var input = $nc("<input />", {
            type: params.inputType || 'text',
            name: params.name,
            value: value,
            // скрипт из modules/search/suggest (используется на страницах изменения профиля экспорта на маркетплейсы)
            // конфликтует с атрибутом autocomplete=off, поэтому добавляем его только если нет плагина autocomplete:
            // autocomplete: 'off',
            'class': 'condition-param-value' + (params['class'] ? ' ' + params['class'] : '')
        }).appendTo(target);

        if (!$nc.fn.autocomplete) {
            input.attr('autocomplete', 'off');
        }

        return input;
    };

    /**
     *
     */
    appendDateTimeInputField(target, params, value) {
        return $nc('<input />', {
            type: 'text',
            name: params.name,
            value: value,
            'class': 'condition-param-value condition-param-value-date'
        }).appendTo(target);
    };

    getSelectFromJsonClass(url) {
        return 'condition-param-SelectFromJson-' + this.calculateHash(url);
    };

    /**
     *
     */
    appendSelectFromJsonField(target, params, value) {
        var url = this.makeUrl(params.source, params.requestParams);
        return this.createListSelectField(params, value, params.placeholder)
            .addClass(this.getSelectFromJsonClass(url))
            .appendTo(target);
    };

    /********* Data loaders *********/
    /**
     *
     * @param {String} url
     * @param {Function} [callback]
     * @param {Object} [callbackParams]    will be passed to the callback function
     */
    loadData(url, callback, callbackParams) {
        if (!callback) {
            callback = $nc.noop;
        }

        if (nc_condition_editor.loadingInProgress[url]) {
            return;
        }

        if (typeof nc_condition_editor.dataCache[url] === 'undefined') {
            nc_condition_editor.loadingInProgress[url] = true;
            $nc.getJSON(url, function (data) {
                nc_condition_editor.dataCache[url] = data;
                callback(data, url, callbackParams);
                nc_condition_editor.loadingInProgress[url] = false;
            });
        } else {
            callback(nc_condition_editor.dataCache[url], url, callbackParams);
        }
    };

    /**
     *
     * @param {string} url
     * @param {Object} [params]
     * @returns {string}
     */
    makeUrl(url, params) {
        if (params) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + $nc.param(params);
        }
        for (let [key, value] of Object.entries(this.urlParams)) {
            if (value.selector) {
                value = this.root.closest('form, body').find(value.selector).val();
            }
            url = url.replace('%' + key + '%', value);
        }
        url = url.replace(/%.+?%/g, '');
        return url;
    };

    /**
     *
     */
    fillSelectsWithOptions(selector, data) {
        var selects = this.root.find(selector);

        if (selects.length > 0) {
            var options = ['<option></option>'];
            if ($nc.isArray(data)) {
                for (var i = 0, len = data.length; i < len; i++) {
                    options.push('<option value="' + data[i].key + '">' + data[i].value + '</option>');
                }
            }

            selects.append(options.join(''));
            this.reinitializeChosenSelects(selects);
        }
    };

    /**
     *
     */
    reinitializeChosenSelects(selects) {
        var editor = this;
        selects
            .each(function () {
                var select = $nc(this),
                    chosen = select.data('chosen');
                // check if the field was focused
                select.data('hadFocus',
                    chosen ? chosen.container.hasClass('chosen-container-active')
                           : select.is(':focus')
                );
                select.val(editor.getInitialParamValue(select))
                    .removeClass('condition-param-value-initializing');
            })
            // chosen doesn’t update width when 'chosen:updated' is triggered
            .chosen('destroy').chosen(this.defaultChosenParams)
            // restore focus
            .each(function () {
                var select = $nc(this);
                if (select.data('hadFocus')) {
                    select.data('chosen').selected_item.focus();
                }
                select.removeData('hadFocus');
            });
    };

    /**
     *
     */
    onSelectFromClassifierListReady(data, url, callbackParams) {
        var selector = 'select.condition-param-SelectFromClassifier-' + callbackParams.classifier + ':empty';
        this.fillSelectsWithOptions(selector, data);
    };

    /**
     *
     */
    onSelectObjectPropertyListReady(data, url, callbackParams) {
        this.objectProperties = data; // store info about fields in this.objectProperties to reuse it later

        var options = ['<option></option>'];
        for (var groupName in data) {
            options.push('<optgroup label="' + groupName + '">');
            for (var i = 0, last = data[groupName].length; i < last; i++) {
                var field = data[groupName][i];
                options.push('<option value="' + field.id + '">' + field.description + '</option>');
            }
            options.push('</optgroup>');
        }

        var selects = this.root.find('select.condition-param-SelectObjectProperty:empty');
        selects.append(options.join(''))
            .change($nc.proxy(this, 'onObjectPropertySelect'));
        this.reinitializeChosenSelects(selects);

        // generate variable part if the field is known  [must be after reinitializeChosenSelects]
        selects.trigger('change');
    };

    /**
     *
     */
    generatePropertyFieldConfiguration(property) {
        var fields = [];

        switch (property.type) {
            case 'string':
            case 'text':
                fields.push(this.opString, { name: 'value', type: 'Input' });
                break;
            case 'integer':
                fields.push(this.opNumber, { name: 'value', type: 'Input', inputType: 'number' });
                break;
            case 'float':
                fields.push(this.opNumber, { name: 'value', type: 'Input' });
                break;
            case 'select':
            case 'multiselect':
                fields.push(
                    (property.type === 'multiselect' ? this.opContains : this.opEqNe),
                    {
                        name: 'value',
                        type: 'SelectFromClassifier',
                        requestParams: { classifier: property.classifier }
                    }
                );
                break;
            case 'boolean':
                fields.push(
                    '<input type="hidden" name="op" value="eq" class="condition-param-value">' + this.str('EQUALS'),
                    { name: 'value', type: 'SimpleSelect', values: { 1: this.str('TRUE'), 0: this.str('FALSE') } }
                );
                break;
            case 'datetime':
                fields.push(this.opDateTime, { name: 'value', type: 'DateTimeInput' });
                break;
        }

        return fields;
    };

    /**
     * Handler for object property select value change
     */
    onObjectPropertySelect(event) {
        var select = $nc(event.target),
            property = this.getObjectPropertyData(select.val());

        if (!property) {
            return;
        }

        var conditionContainer = select.closest('.condition'),
            variableContainer = conditionContainer.find('.condition-objectproperty-variable-part'),
            valuesToSet = conditionContainer.data('initialValues'),
            fields = this.generatePropertyFieldConfiguration(property);

        variableContainer.html('');
        this.addFields(variableContainer, fields, valuesToSet);
    };

    /**
     *
     * @param {String} propertyId
     * @returns {Object|null}
     */
    getObjectPropertyData(propertyId) {
        var properties = this.objectProperties;
        for (var groupName in properties) {
            for (var i = 0, last = properties[groupName].length; i < last; i++) {
                var field = properties[groupName][i];
                if (field.id == propertyId) {
                    return field;
                }
            }
        }
        return null;
    };

    /**
     *
     */
    onSelectFromJsonListReady(data, url, callbackParams) {
        var selectClass = this.getSelectFromJsonClass(url);
        this.fillSelectsWithOptions('select.' + selectClass + ':empty', data);
    };

};