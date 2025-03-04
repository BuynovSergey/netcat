// Поддерживается IE9+

(
/**
 * Параметры, передаваемые внутрь замыкания для улучшения сжатия (~ +10%):
 * @param {Window} window
 * @param {Document} document
 * @param {Storage} sessionStorage
 * @param {Function} encodeURIComponent
 * @param {Function} isArray
 * @param {Function} parseJson
 * @param {undefined} [undefined]
 */
function(window, document, sessionStorage, encodeURIComponent, isArray, parseJson, undefined) {

    /**
     * Загружает с сервера часть страницы: врезку макета (template partial), область макета (area)
     * или инфоблок (infoblock).
     *
     * (Далее в комментариях части страницы, которые можно загружать асинхронно, обобщённо
     * называются «фрагментами».)
     *
     * @param {String|Array} [partialConditions] правила выбора фрагментов для обновления.
     *      Если правила не указаны (undefined, null, false, пустая строка),
     *      то будут обновлены все фрагменты на странице.
     *
     *      Все фрагменты, удовлетворяющие условиям, будут получены с сервера одним запросом.
     *
     *      Источники фрагмента (врезка, область или инфоблок) задаются строками.
     *      Тип фрагмента определяется форматом строки:
     *      — если строка начинается с '@', строка после '@' считается названием области макета;
     *      — если строка состоит только из цифр, то это идентификатор инфоблока;
     *      — все прочие строки считаются названиями врезок макета дизайна.
     *
     *      Правила выбора фрагментов могут быть указаны:
     *      — в виде строки с указанием одного источника, например: 'footer'
     *        (будут обновлены все врезки с ключевым словом footer, независимо от
     *        наличия или отсутствия у них дополнительных параметров data)
     *      — в виде строки с несколькими источниками через пробел или запятую, например: 'header footer'
     *      — в виде массива, где каждый элемент также представляет из себя массив:
     *        1) Первый элемент — источник фрагмента (или несколько через пробел или запятую)
     *        2) Второй элемент (опционально) — параметры для выборки по дополнительным параметрам (data).
     *           Если значение — null, то будут обновлены только фрагменты без параметров.
     *           Если передан объект со значениями, будут обновлены фрагменты, у которых параметры
     *           имеют соответствующие значения. При выборке по значению параметров не поддерживаются
     *           многомерные массивы.
     *        3) Третий элемент (опционально) — объект с параметрами data, которые будет изменены у
     *           фрагмента.
     *
     *        Например:
     *        [
     *          'header',
     *          ['footer', { show_menu: true, page_type: 'front' }],
     *          ['aside, null]
     *        ]
     *        Будут обновлены:
     *        1) врезки 'header' c любыми параметрами или без них
     *        2) врезки 'footer', у которых одновременно есть параметры show_menu = true и page_type = 'front'
     *        3) врезки 'aside' без параметров
     *
     *        [
     *          [ 'cart', { nc_ctpl: 'plain' }, { nc_ctpl: 'fancy' } ]
     *        ]
     *        Будет обновлена врезка 'cart' с параметром nc_ctpl = 'plain', перед загрузкой
     *        значение параметра nc_ctpl будет заменено на 'fancy'.
     *
     * @param {Function} [successCallback]  обработчик, который будет выполнен
     *      при успешном выполнении запроса (аргумент: полученные в виде JSON данные)
     * @param {Function} [failureCallback]  обработчик, который будет выполнен
     *      при ошибке (аргумент: объект XMLHttpRequest)
     */
    window.nc_partial_load = function(partialConditions, successCallback, failureCallback) {
        reloadPartials(getPartials(partialConditions), successCallback || noOp, failureCallback || noOp);
    };

    /**
     * Удаляет сохранённые в sessionStorage врезки макетов
     */
    window.nc_partial_clear_cache = function() {
        for (var key in sessionStorage) {
            if (sessionStorage.hasOwnProperty(key) && !key.indexOf(sessionStoragePrefix)) { // == 0
                sessionStorage.removeItem(key);
            }
        }
    };

    /**
     * Загрузка фрагментов при готовности страницы
     */
    document.addEventListener('DOMContentLoaded', function() {
        var partialsToLoad = [], restoredPartials = {}, hasRestoredPartials;
        forEach(getPartials(), function(partial) {
            var param = partial.param, restoredHTML, shouldLoadDeferred;

            if (param.defer) {
                shouldLoadDeferred = true;
                if (param.store && (restoredHTML = sessionStorage.getItem(partial.key)) !== null) {
                    replacePartialContent(partial, restoredHTML);
                    restoredPartials[partial.query] = restoredHTML;
                    hasRestoredPartials = true;
                    shouldLoadDeferred = false;
                }
            }

            if (shouldLoadDeferred || param.reload) {
                partialsToLoad.push(partial);
            }
        });

        if (hasRestoredPartials) {
            dispatchTemplatePartialUpdateEvent(restoredPartials);
        }

        reloadPartials(partialsToLoad, noOp, noOp);
    });

    /**
     * Событие document.ncPartialUpdate — завершена загрузка фрагментов страницы.
     * Вызывается после добавления содержимого фрагментов в DOM.
     * В event.detail.newTemplateContent будет доступен объект с ключевыми словами
     * источников фрагментов в качестве ключа и содержимым соответствующих фрагментов
     * в качестве значения.
     * (В jQuery-событии — event.originalEvent.detail.newTemplateContent)
     *
     * @param newTemplateContent
     */
    function dispatchTemplatePartialUpdateEvent(newTemplateContent) {
        nc_event_dispatch('ncPartialUpdate', { newTemplateContent: newTemplateContent });
    }

    var partialStartRegExp = /nc_partial (\S+) ({.+})/;
    var sessionStoragePrefix = 'nc_partial_';
    var dataProperty = sessionStoragePrefix + 'data';

    var console = window.console; // может не быть в IE9, см. ниже вызов console.log()

    // --- Функции для получения (выборки) массивов с объектами с информацией о фрагментах ---

    /**
     *
     * @param {Array} [partialConditions]
     * @returns {Object[]}
     */
    function getPartials(partialConditions) {
        var partials = [],
            // NodeIterator доступен в IE9+. Четвёртый аргумент нужен для IE.
            nodeIterator = document.createNodeIterator(document.body, NodeFilter.SHOW_COMMENT, null, false),
            commentNode,
            commentNodeParts,
            partial,
            // локальные переменные для свойств, используемых несколько раз (для сжатия)
            partialParams,
            partialData,
            partialSrc,
            partialTemplate,
            partialQuery,
            matchedConditionData;

        if (partialConditions) {
            partialConditions = normalizePartialConditions(partialConditions);
        }
        
        while (commentNode = nodeIterator.nextNode()) {
            commentNodeParts = commentNode.nodeValue.match(partialStartRegExp);
            if (commentNodeParts) {
                try {
                    // параметры фрагмента парсятся один раз и сохраняются в свойстве nc_partial_data
                    // узла комментария (для возможности изменения параметров)
                    partialParams =
                        commentNode[dataProperty] ||
                        (commentNode[dataProperty] = parseJson(commentNodeParts[2]));

                    partialData = partialParams.data;
                    partialSrc = partialParams.src;

                    // прежде чем что-то делать дальше, проверим условия
                    if (partialConditions) {
                        matchedConditionData = getMatchedCondition(partialConditions, partialSrc, partialData);
                        if (!matchedConditionData) {
                            continue;
                        }
                        // применяем новые значения data из условия выборки (partialConditions[i][2])
                        if (matchedConditionData !== true) { // i.e. is an object
                            for (var key in matchedConditionData) {
                                // заменяем значения в partialData (соответственно и в commentNode[commentDataProperty]):
                                partialData[key] = matchedConditionData[key];
                            }
                        }
                    }

                    partialQuery = partialSrc + makeUrlQueryString(partialData || {});
                    partialTemplate = partialParams.template;

                    partial = {
                        // начальный узел
                        start: commentNode,
                        // идентификатор комментария в разметке страницы (стандартно состоит из префикса
                        // типа фрагмента и числовой последовательности)
                        seq: commentNodeParts[1],
                        // параметры фрагмента из разметки страницы
                        param: partialParams,
                        // источник фрагмента + data в виде query-строки (partial?param1=1&param2=2)
                        query: partialQuery,
                        // ключ для sessionStorage
                        key: sessionStoragePrefix + (partialTemplate ? partialTemplate + '#' : '') + partialQuery
                    };

                    // проверяем, есть ли конец фрагмента (бросит ошибку, если его нет)
                    getPartialNodes(partial);

                    // итак, мы нашли корректный и удовлетворяющий условиям фрагмент
                    partials.push(partial);
                } catch (e) {
                    // [убрать условие 'console && ' при отказе от поддержки IE9]
                    console && console.log('Partial error:', e.message, commentNode);
                }
            }
        }

        return partials;
    }

    /**
     *
     * @param {Array|String} conditions
     * @return {Array[]}
     *   [
     *      [ ['keyword1', 'keyword2'], data ],
     *      ...
     *   ]
     */
    function normalizePartialConditions(conditions) {
        if (!isArray(conditions)) {
            // 'keyword' → [['keyword', undefined]]
            conditions = [[conditions]];
        }

        forEach(conditions, function(condition, n) {
            if (!isArray(condition)) {
                // ['keyword'] → [['keyword', undefined]]
                conditions[n] = condition = [ condition ];
            }
            // [['keyword1 keyword2', data]] → [[['keyword1', 'keyword2], data]]
            condition[0] = ('' + condition[0]).split(/[\s,]+/); // конкатенация вместо .toString() для сжатия
        });

        return conditions;
    }

    // --- функции для работы с объектами фрагментов ---
    // (если сделать через Partial.prototype, сжатая версия будет немного [~10%] больше)

    /**
     *
     * @param {Object} partial
     * @returns {Node[]}
     */
    function getPartialNodes(partial) {
        var nodes = [],
            sibling = partial.start;

        while (sibling = sibling.nextSibling) {
            if (sibling.nodeType == 8 && sibling.nodeValue.indexOf('/nc_partial ' + partial.seq) != -1) {
                return nodes;
            }
            nodes.push(sibling);
        }

        throw new Error; // ('No end comment');
    }

    /**
     *
     * @param {Object} partial
     * @param {String} newHTML
     */
    function replacePartialContent(partial, newHTML) {
        var parent = partial.start.parentNode;

        // remove old
        forEach(getPartialNodes(partial), function(node) {
            parent.removeChild(node);
        });

        // add new
        var tmpElement = parent.insertBefore(document.createElement('DIV'), partial.start.nextSibling);
        tmpElement.insertAdjacentHTML('afterend', newHTML);
        parent.removeChild(tmpElement);

        // scripts are not executed when added this way...
        evaluateScripts(getPartialNodes(partial));

        // cache
        if (partial.param.store) {
            sessionStorage.setItem(partial.key, newHTML);
        }
    }

    /**
     *
     * @param conditions
     * @param {String} partialSrc
     * @param {Object} partialData
     * @returns {Object|false} возвращает:
     *   false, если условия не совпали
     *   true, если совпали условия
     *   объект с новыми значениями partialData, если в условиях задан третий элемент
     */
    function getMatchedCondition(conditions, partialSrc, partialData) {
        var result = false,
            noPartialData = !Object.keys(partialData).length;

        forEach(conditions, function(condition) {
            var dataCondition = condition[1],
                conditionMatch =
                    condition[0].indexOf(partialSrc) != -1 &&
                    (
                        dataCondition === undefined ||
                        (dataCondition === null && noPartialData) ||
                        (dataCondition && compareObjectProperties(partialData || {}, dataCondition))
                    );

            if (conditionMatch) {
                result = condition[2] || true;
                return false; // exit forEach
            }
        });

        return result;
    }

    // --- Функции для массивов объектов с информацией о фрагментах ---

    /**
     *
     * @param {Object[]} partials
     * @param {Function} successCallback
     * @param {Function} failureCallback
     */
    function reloadPartials(partials, successCallback, failureCallback) {
        if (!partials.length) {
            return;
        }

        var template,
            requestData = {
                partial: [],
                referer: location.toString(),
                json: 1
            };

        forEach(partials, function(partial) {
            template = partial.param.template || template;
            var query = partial.query,
                requestedPartialsArray = requestData.partial;
            if (requestedPartialsArray.indexOf(query) == -1) {
                requestedPartialsArray.push(query);
            }
        });

        if (template) {
            requestData.template = template;
        }

        var xhr = new XMLHttpRequest(),
            // nc_partial_data: см. netcat_manifest.php
            url = (window.NETCAT_PATH || '/netcat/') + 'partial.php' + (window[dataProperty] || '');

        xhr.open('POST', url);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    try {
                        var newContent = parseJson(xhr.responseText);
                        updatePartials(partials, newContent);
                        newContent.$ && updateHeadContent(newContent.$); // контент-для-head передаётся в элементе '$'
                        dispatchTemplatePartialUpdateEvent(newContent);
                        successCallback(newContent);
                    } catch (e) {
                        console && console.log(e);
                        failureCallback(xhr);
                    }
                } else {
                    failureCallback(xhr);
                }
            }
        };
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(makeUrlQueryString(requestData).slice(1)); // результат makeUrlQueryString() начинается с '?'
    }

    /**
     *
     * @param {Object[]} partials
     * @param {Object} newContent
     */
    function updatePartials(partials, newContent) {
        forEach(partials, function(partial) {
            if (partial.query in newContent) {
                replacePartialContent(partial, newContent[partial.query]);
            }
        });
    }

    var headContentTags = {
        SCRIPT: 'src',
        LINK: 'href'
    };

    /**
     *
     * @param {String} newHeadContent
     */
    function updateHeadContent(newHeadContent) {
        // Все элементы из $ ответа
        var allNewElements = document.createElement('DIV');
        allNewElements.innerHTML = newHeadContent;

        // Элементы, которые будут добавлены на страницу
        var addedElements = document.createElement('DIV');

        // Проверяем, есть ли на странице такой элемент (SCRIPT или LINK); добавляем только отсутствующие:
        forEach(allNewElements.childNodes, function(newElement) {
            var tagName = newElement.tagName,
                attributeName = headContentTags[tagName],
                attributeValue;

            if (attributeName) { // это SCRIPT или LINK; всё остальное (может быть ещё nodeType=TEXT_NODE) игнорируется!
                attributeValue = newElement.getAttribute(attributeName);
                if (!attributeValue || !document.querySelector(tagName + '[' + attributeName + '="' + attributeValue + '"]')) {
                    addedElements.appendChild(newElement);
                }
            }
        });

        // Если что-то осталось, добавляем на страницу
        // [для упрощения сейчас] теги script, styles вставляются в низ BODY, а не в HEAD
        if (addedElements.childNodes.length) {
            addedElements.style.display = 'none';
            document.body.appendChild(addedElements);
            evaluateScripts(addedElements.childNodes);
        }
    }

    // --- Вспомогательные функции ---

    /**
     *
     * @param {Object} inspectedObject
     * @param {Object} referenceValues
     * @returns {boolean} true, если в inspectedObject все свойства из referenceValues
     *      имеют указанные в последнем значения
     */
    function compareObjectProperties(inspectedObject, referenceValues) {
        for (var key in referenceValues) {
            if (inspectedObject[key] != referenceValues[key]) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param {Object} data
     * @param {String} [prefix]
     * @returns {String|Array}
     */
    function makeUrlQueryString(data, prefix) {
        var result = [], key, value;
        for (key in data) {
            value = data[key];
            key = encodeURIComponent(key);
            if (prefix) {
                key = prefix + '[' + key + ']';
            }

            if (isArray(value)) {
                // сейчас поддерживаются только одномерные массивы с последовательными индексами
                forEach(value, function(v) {
                    result.push(key + '[]=' + encodeURIComponent(v));
                });
            } else if (typeof value == 'object') {
                result = result.concat(makeUrlQueryString(value, key));
            } else {
                // прочие значения расцениваются как скалярные
                result.push(key + '=' + encodeURIComponent(value));
            }
        }

        if (!prefix) {
            result = result.length ? '?' + result.join('&') : '';
        }

        return result;
    }

    /**
     *
     * @param nodes
     */
    function evaluateScripts(nodes) {
        // Если просто задать async у <script>, скрипты с src и без него всё равно будут выполняться не по порядку.
        // Поэтому сначала загружаем все скрипты с src, а потом выполняем инлайн-скрипты (потому что, например, скрипты
        // инициализации миксинов при обычной загрузке страницы выполняются по DOMContentLoaded — скрипты assets к 
        // этому моменту загружены).
        var external = [],
            embedded = [],
            numLoaded = -1; // функция incrementLoadedCounterAndExecuteEmbeddedScriptsWhenAllLoaded() вызывается один раз без загрузки внешнего скрипта

        // не getElementsByTagName или querySelectorAll, так как nodes тоже могут быть <script>
        function getScripts(nodes) {
            forEach(nodes, function (node) {
                if (node.tagName == 'SCRIPT') {
                    if (node.src) {
                        external.push(node.src);
                    } else {
                        embedded.push(node.text);
                    }
                } else if (node.childNodes.length) {
                    getScripts(node.childNodes);
                }
            });
        }

        function addScript(src, text) {
            var head = document.head,
                script = document.createElement('SCRIPT');
            if (src) {
                script.onload = script.onerror = incrementLoadedCounterAndExecuteEmbeddedScriptsWhenAllLoaded;
                script.src = src;
            } else {
                script.appendChild(document.createTextNode(text));
            }
            head.appendChild(script);
            head.removeChild(script);
        }

        function incrementLoadedCounterAndExecuteEmbeddedScriptsWhenAllLoaded() {
            if (++numLoaded >= external.length) {
                addScript(false, embedded.join('\n'));
            }
        }

        getScripts(nodes);
        forEach(external, addScript);
        incrementLoadedCounterAndExecuteEmbeddedScriptsWhenAllLoaded();
    }

    /**
     *
     */
    function noOp() {}

    /**
     * (В IE нет NodeList.forEach)
     *
     * @param {Array|NodeList} arrayLikeObject
     * @param {Function} fn
     */
    function forEach(arrayLikeObject, fn) {
        for (var i = 0; i < arrayLikeObject.length; i++) {
            if (fn(arrayLikeObject[i], i) === false) {
                break;
            }
        }
    }

})(window, document, sessionStorage, encodeURIComponent, Array.isArray, JSON.parse);
