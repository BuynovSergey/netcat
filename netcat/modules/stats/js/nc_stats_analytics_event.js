/**
 * Отправка событий Netcat в Яндекс.Метрику, Google Analytics
 *
 * 1) Слушает события, отправляет статистику для элементов, которые имеют атрибуты:
 *    data-analytics-click-category — категория события GA / суффикс цели ЯМ — атрибут обязателен для срабатывания события
 *    data-analytics-click-label — ярлык события GA / дополнительный параметр event_label для ЯМ
 *    data-analytics-click-action — действие события GA / префикс цели для ЯМ.
 *          По умолчанию равны типу события, например, 'click'.
 *
 *    Значения category, label могут содержать несколько категорий и ярлыков
 *    соответственно, перечисленных через запятую. При наличии нескольких значений
 *    будут сформированы все возможные варианты событий.
 *
 *    Типы событий:
 *       click
 *       submit
 *       view
 *
 * 2) Отправляет события E-Commerce (через объект dataLayer для GTM и Метрики,
 *    через ec:setAction для расширенной аналитики электронной коммерции analytics.js).
 *    В устаревшую версию Google Analytics (ga.js) события E-Commerce не отправляются.
 *
 *    События просмотра страницы товара, изменения количества в корзине, оформления
 *    заказа передаются из Netcat автоматически через куки.
 *
 *    Для элементов с data-атрибутами будут срабатывать события при их попадании
 *    в видимую область страницы (impression) и в при нажатии на ссылки внутри них.
 *    data-analytics-item="{данные о товаре в формате для gtag.js}"
 *    data-analytics-promo="{данные о внутренней рекламе в формате для gtag.js}"
 *
 *    Информация о просмотрах товаров и рекламы будет отправлена при закрытии страницы.
 *
 * 3) Предоставляет функцию nc_stats_analytics_event()
 *
 *
 * Поддержка IE: 9+
 * Часть функциональности не поддерживается в до IE 11 (наблюдения за изменениями DOM —
 * нужно, например, если на странице динамически добавляются формы)
 *
 */

(
/**
 * @param {Window} window
 * @param {Document} document
 * @param {'addEventListener'} addEventListenerMethod
 * @param {'getAttribute'} getAttributeMethod
 * @param {'data-analytics-'} dataAttributePrefix
 * @param {'nc_stats_'} cookieNamePrefix
 * @param {'nc_stats_analytics_event'} globalSendEventFunctionName
 * @param {undefined} [undefined]
 */
function(window, document, addEventListenerMethod, getAttributeMethod, dataAttributePrefix, cookieNamePrefix, globalSendEventFunctionName, undefined) {
    if (!document[addEventListenerMethod]) {
        // IE8 and below is not supported
        return;
    }

    // -------------------------------------------------------------------------
    // Вспомогательные функции

    /**
     * Возвращает атрибут data-analytics-*
     * @param {Element} target
     * @param {String} attributeNameSuffix
     * @returns {string | undefined}
     */
    function getDataAttribute(target, attributeNameSuffix) {
        return target[getAttributeMethod](dataAttributePrefix + attributeNameSuffix);
    }

    /**
     * Устанавливает атрибут data-analytics-*
     * @param {Element} target
     * @param {String} attributeNameSuffix
     * @param {String|Number} attributeValue
     */
    function setDataAttribute(target, attributeNameSuffix, attributeValue) {
        target.setAttribute(dataAttributePrefix + attributeNameSuffix, attributeValue);
    }

    /**
     * Добавляет слушателя событий
     * (вызов этой функции вместо метода .addEventListener позволяет уменьшить размер .min.js)
     * @param {Element|Document|Window} target
     * @param {String} event
     * @param {Function} listener
     * @param {Boolean|Object} [options]
     */
    function addEventListener(target, event, listener, options) {
        target[addEventListenerMethod](event, listener, options);
    }

    /**
     * Выполняет функцию с каждым элементом массива или массивоподобного объекта
     *
     * @param {Array|NodeList} arrayLikeObject
     * @param {Function} fn
     */
    function forEach(arrayLikeObject, fn) {
        for (var i = 0; i < arrayLikeObject.length; i++) {
            fn(arrayLikeObject[i], i);
        }
    }

    /**
     * Добавляет в массив объект из JSON-строки
     * @param {Array} array
     * @param {String} jsonString
     * @returns {Array}
     */
    function pushIfIsValidJsonObject(array, jsonString) {
        if (jsonString) {
            try {
                 var data = JSON.parse(jsonString);
                 if (data) {
                     array.push(data);
                 }
             } catch (e) {
                log('Invalid JSON', jsonString);
            }
        }
        return array;
    }

    /**
     * Работа с куками с префиксом "nc_stats_"
     * (усечённая версия библиотеки MDN https://github.com/madmurphy/cookies.js)
     */
    function getCookie(cookieNameSuffix) {
        return decodeURIComponent(
            document.cookie.replace(
                new RegExp(
                    '(?:(?:^|.*;)\\s*' +
                    cookieNamePrefix + cookieNameSuffix +
                    '\\s*\\=\\s*([^;]*).*$)|^.*$'
                ),
                '$1')
                .replace(/\+/g, ' ') // PHP кодирует пробелы в куках плюсами, а decodeURIComponent как %20
        );
    }

    function setCookie(cookieNameSuffix, value, maxAgeInSeconds) {
        var domain = getCookie('domain'); // хост из куки nc_stats_domain
        document.cookie =
            cookieNamePrefix + cookieNameSuffix + '=' + encodeURIComponent(value) +
            ';path=/' +
            (domain ? ';domain=' + domain : '') +
            // у IE 9-11 есть проблемы с удалением куки с max-age, поэтому придётся использовать expire
            (maxAgeInSeconds ? ';expires=' + (new Date(Date.now() + (maxAgeInSeconds * 1000)).toUTCString()) : '');
    }

    function removeCookie(cookieNameSuffix) {
        setCookie(cookieNameSuffix, '', -1);
    }

    function getAllCookieNames() {
        var statCookieNames = [],
            prefixLength = cookieNamePrefix.length,
            allKeys = document.cookie
                .replace(/((?:^|\s*;)[^=]+)(?=;|$)|^\s*|\s*(?:=[^;]*)?(?:\1|$)/g, '')
                .split(/\s*(?:=[^;]*)?;\s*/);
        forEach(allKeys, function(cookieName) {
            if (!cookieName.indexOf(cookieNamePrefix)) { // == 0
                statCookieNames.push(cookieName.substr(prefixLength));
            }
        });
        return statCookieNames;
    }

    /**
     * Возвращает функцию, которая ждёт немного (500 мс) повторного события
     * (на случай, если оно снова произойдёт — например, скролл или изменения DOM)
     * и затем выполняет обработчик
     * @param {Function} handler
     * @returns {Function}
     */
    function waitForMoreChangesThen(handler) {
        var timeout;
        return function() {
            clearTimeout(timeout); // имя свойство будет из handler.toString()
            timeout = setTimeout(handler, 500);
        };
    }

    /**
     * Копирует массив объектов с заменой указанных ключей
     * @param {Array} sourceArray
     * @param {Object} map
     * @returns {Array}
     */
    function mapArrayOfObjects(sourceArray, map) {
        var resultArray = [];
        forEach(sourceArray, function(object) {
            resultArray.push(mapObjectProperties(object, map));
        });
        return resultArray;
    }

    /**
     * Копирует свойства объекта с заменой указанных ключей
     * @param {Object} object
     * @param {Object} map
     * @param {Function|undefined} [convertKeysFunction]
     * @returns {Object}
     */
    function mapObjectProperties(object, map, convertKeysFunction) {
        var objectCopy = {};
        for (var key in object) {
            objectCopy[map[key] || (convertKeysFunction ? convertKeysFunction(key) : key)] = object[key];
        }
        return objectCopy;
    }

    /**
     * camelCase (где первая буква строчная) → snake_case
     * @param {string} string
     * @returns {string}
     */
    function camelToSnake(string) {
        return string.replace(/[A-Z]/g, function(match) {
            return '_' + match.toLowerCase();
        });
    }

    /**
     * snake_case → camelCase
     * @param {string} string
     * @returns {string}
     */
    function snakeToCamel(string) {
        return string.replace(/_([a-z])/g, function(match) {
            return match[1].toUpperCase();
        });
    }

    // -------------------------------------------------------------------------
    // Вывод отладочной информации в console.log — срабатывает, если есть кука
    // nc_stats_debug на момент загрузки страницы

    var enableLog = !!(getCookie('debug') && window.console);

    /**
     * Выводит отладочную информацию в консоль браузера
     * @param {String} type
     * @param {Array} args
     */
    function log(type, args) {
        if (enableLog) {
            args = (Array.isArray(args) ? args : [args]).map(function(value) {
                // вывод объектов в виде JSON позволяет сохранить информацию
                // после перехода на следующую страницу (например, при нажатии
                // на ссылку) — свойства объектов могут стать недоступными для
                // изучения в консоли
                return (typeof value === 'object') ? JSON.stringify(value) : value;
            });
            console.log.apply(console, [globalSendEventFunctionName, type].concat(args));
        }
    }

    /**
     * Оборачивает функцию в функцию логирования в консоль (если включён режим дебага)
     * @param {String} logPrefix
     * @param {Function} fn
     * @returns {Function}
     */
    function makeGlobalFunctionLogDecorator(logPrefix, fn) {
        return enableLog ? function() {
            var args = [].slice.call(arguments);
            log(logPrefix, args);
            fn.apply(window, args)
        } : fn;
    }

    // -------------------------------------------------------------------------
    // Общие функции для скриптов аналитики

    var yandexMetrikaObject;
    /**
     * Возвращает первый имеющийся на странице объект yaCounter
     * @returns {undefined|object}
     */
    function getYandexMetrikaObject() {
        if (!yandexMetrikaObject) {
            for (var i in window) {
                if (/yaCounter\d+/.test(i) && window[i].reachGoal) {
                    yandexMetrikaObject = window[i];
                    break;
                }
            }
        }
        return yandexMetrikaObject;
    }

    /**
     * Возвращает объект GoogleAnalytics, если он есть и загружен
     * (если включен debug-режим, добавляет функцию-обёртку для логирования)
     * @returns {undefined|Function}
     */
    function getGaFunction() {
        var ga = window.GoogleAnalyticsObject ? window[GoogleAnalyticsObject] : undefined;
        if (ga) {
            // при включённой отладке возвращаем обёртку, которая логирует все вызовы ga()
            return enableLog ? makeGlobalFunctionLogDecorator('ga', ga) : ga;
        }
        // otherwise returns undefined
    }

    function getGtagFunction() {
        if (window.gtag) {
            return enableLog ? makeGlobalFunctionLogDecorator('gtag', gtag) : gtag;
        }
        // otherwise returns undefined
    }

    var gtmDataLayer = 'dataLayer';
    var defaultEventCategory = 'event';
    var valueSplitRegexp = /\s*,\s*/;

    var hasGtmJsScriptTag;
    var gtmEventNamePrefix = defaultEventCategory + '_';
    var wasCurrencyPropertySet;

    // -------------------------------------------------------------------------

    /**
     * Отправка события в Google Analytics и Яндекс.Метрику.
     * Поддерживаются события взаимодействия и электронной коммерции.
     *
     * Доступна как глобальная функция nc_stats_analytics_event().
     *
     * @param {String} eventAction  Действие события (например, 'click'), как для gtag
     * @param {Object} eventData    Данные для события, как для gtag
     */
    function sendEvent(eventAction, eventData) {
        if (!eventAction) {
            return;
        }
        eventData = eventData || {};

        // Google Analytics...
        var gtag = getGtagFunction(); // gtag.js
        var googleAnalytics = (gtag || hasGtmJsScriptTag) ? undefined : getGaFunction(); // analytics.js
        var _gaq = window._gaq; // ga.js
        var dataLayer = window[gtmDataLayer]; // dataLayer (используется для GTM и коммерции Яндекс.Метрики)

        // В дополнение или вместо Google Analytics может использоваться Яндекс.Метрика
        var yandexMetrika = getYandexMetrikaObject();

        // Устанавливаем валюту в «глобальном» свойстве, но только при первом вызове этой функции
        var currencyCode = wasCurrencyPropertySet ? undefined : getCookie('currency');
        wasCurrencyPropertySet = true;

        if (gtag) {
            // В заглушке для gtag() используется всё тот же dataLayer.push, так что используем его.
            currencyCode && gtag('set', { currency: currencyCode });

            // Отправка события.
            // API gtag и этой функции полностью совпадают
            gtag('event', eventAction, eventData);

            if (!yandexMetrika) {
                // Если нет Метрики, то делать тут больше нечего — в Google мы всё отправили
                return;
            }
        }

        var gtmEventName = gtmEventNamePrefix + eventAction;

        // Гугловские скрипты кроме gtag.js и Яндекс.Метрика — конвертируем данные о событии
        // (на самом деле внутри gtag.js происходит обратное преобразование — в формат analytics.js,
        // но за основу выбран API gtag.js как более новый/полный/последовательный)
        var gtmEcommerceEvent = convertEcommerceEventForGTM(eventAction, eventData);
        if (gtmEcommerceEvent.action) {
            // это событие e-commerce, про которое мы знаем...

            // Google Tag Manager, Яндекс.Метрика через dataLayer
            if (yandexMetrika || hasGtmJsScriptTag) {
                pushToDataLayer(dataLayer, {
                    event: gtmEventName, // "event_[ДЕЙСТВИЕ]"
                    event_category: gtmEcommerceEvent.category,
                    event_action: eventAction,
                    ecommerce: gtmEcommerceEvent.event
                });
            }

            // analytics.js
            if (googleAnalytics) {
                currencyCode && googleAnalytics('set', 'currencyCode', currencyCode);
                sendAnalyticsEcommerceEvent(googleAnalytics, gtmEcommerceEvent, eventAction);
            }

            // ga.js — отправка событий э-коммерции не поддерживается
            return;
        }

        // прочие события (не электронная коммерция)

        // Если категория не задана, то будет равна "event"
        var eventCategory = eventData.event_category || defaultEventCategory;

        // Google Tag Manager
        if (hasGtmJsScriptTag) { // gtm.js
            // в dataLayer будет помещён объект со всеми свойствами eventData
            // (как для gtag) и event = 'event_[ДЕЙСТВИЕ]'
            var eventDataCopy = mapObjectProperties(eventData, {});
            eventDataCopy.event = gtmEventName;
            eventDataCopy.event_action = eventDataCopy.event_action || eventAction;
            pushToDataLayer(dataLayer, eventDataCopy);
        }

        if (googleAnalytics) { // analytics.js
            sendAnalyticsEvent(googleAnalytics, eventAction, eventData);
        } else if (_gaq) { // ga.js
            var gaParams = ['_trackEvent', eventCategory, eventAction, eventData.event_label];
            log('_gaq', gaParams);
            _gaq.push(gaParams);
        }

        // Яндекс.Метрика
        if (yandexMetrika) {
            // Цель будет равна "категория_действие"; все параметры события будут
            // переданы как дополнительные параметры визита
            var ymGoalId = eventCategory + '_' + eventAction;
            log('YM.reachGoal', [ymGoalId, eventData]);
            yandexMetrika.reachGoal(ymGoalId, eventData);
        }
    }

    /**
     * Для использования в forEach() для массива с аргументами функции sendEvent
     * @param {Arguments|Array} args
     */
    function applySendEventArguments(args) {
        sendEvent.apply(window, args);
    }

    /**
     * Обработка события взаимодействия с элементом (с категорией, действием и ярлыком).
     *
     * @param {String} eventCategories   Категории события (можно несколько через запятую)
     * @param {String} eventAction       Действие события, например, 'click'
     * @param {String} eventLabels       Ярлыки события (можно несколько через запятую)
     */
    var processInteractionEvent = function(eventCategories, eventAction, eventLabels) {
        forEach((eventCategories || '').split(valueSplitRegexp), function(eventCategory) {
            forEach((eventLabels || '').split(valueSplitRegexp), function(eventLabel) {
                sendEvent(eventAction, {
                    event_category: eventCategory,
                    event_label: eventLabel
                });
            })
        });
    };

    /**
     * Слушатель событий для элементов с атрибутами data-analytics-*-category,
     * data-analytics-item, data-analytics-promo
     *
     * @param {Object} event — может быть ненастоящим event-объектом: см. слушатель скролла processViewportChanges()
     */
    function analyticsEventListener(event) {
        var element = event.target,
            eventType = event.type,
            isClick = eventType === 'click',
            isClickOnLink;

        do {
            var eventCategories = getDataAttribute(element, eventType + '-category');
            if (eventCategories) {
                processInteractionEvent(
                    eventCategories,
                    getDataAttribute(element, eventType + '-action') || eventType,
                    getDataAttribute(element, eventType + '-label')
                );
            }

            if (isClick) {
                // события э-коммерции сработают, если клик произошёл внутри ссылки
                // (даже если это #-ссылка на эту же страницу)
                isClickOnLink = isClickOnLink || element.tagName === 'A';
                if (isClickOnLink) {
                    sendEcommerceClickEvent(element, 'item');
                    sendEcommerceClickEvent(element, 'promo');
                }
            }

        } while (isClick && (element = element.parentNode) && element[getAttributeMethod]);
    }

    /**
     * Отправка события через analytics.js
     *
     * @param {Function} googleAnalytics
     * @param {String} eventName
     * @param {Object} eventData
     */
    function sendAnalyticsEvent(googleAnalytics, eventName, eventData) {
        var gaHitType = 'event',
            eventFieldsMap = {
                value: 'eventValue',
                description: 'exDescription',
                fatal: 'exFatal'
            },
            gaEventData;

        if (eventName === 'timing_complete') {
            eventFieldsMap.name = 'timingVar';
            gaHitType = 'timing';
        } else if (eventName === 'exception') {
            gaHitType = eventName;
        }

        gaEventData = mapObjectProperties(eventData, eventFieldsMap, snakeToCamel);
        gaEventData.eventAction = gaEventData.eventAction || eventName;
        gaEventData.eventCategory = gaEventData.eventCategory || defaultEventCategory;

        googleAnalytics('send', gaHitType, gaEventData);
    }

    /**
     *
     * @param dataLayer
     * @param eventData
     */
    function pushToDataLayer(dataLayer, eventData) {
        log(gtmDataLayer, [eventData]);
        dataLayer.push(eventData);
    }

    // -------------------------------------------------------------------------
    // Блок e-commerce
    // -------------------------------------------------------------------------

    // Google Analytics позволяет делать 10 млн обращений в месяц.
    // Чтобы случайно не израсходовать их все на события типа «просмотр отдельного
    // товара в списке», отправляем такие события скопом при уходе со страницы
    var itemViews = [],
        promoViews = [];

    /**
     * Отправка события E-commerce через analytics.js
     *
     * @param {Function} googleAnalytics
     * @param {Object} gtmEcommerceEvent
     * @param {String} eventAction
     */
    function sendAnalyticsEcommerceEvent(googleAnalytics, gtmEcommerceEvent, eventAction) {
        // действия analytics.js такие же, как в GTM, только в snake_case,
        // но нет действий promoView и impressions

        // promoClick → promo_click, checkoutOption → checkout_option:
        var analyticsEcAction = camelToSnake(gtmEcommerceEvent.action);

        googleAnalyticsAddEcommerceItems(
            googleAnalytics,
            (analyticsEcAction === 'impressions' ? 'Impression' : 'Product'),
            gtmEcommerceEvent.items
        );

        googleAnalyticsAddEcommerceItems(googleAnalytics, 'Promo', gtmEcommerceEvent.promos);

        if (!/promo_view|impressions/.test(analyticsEcAction)) {
            googleAnalytics('ec:setAction', analyticsEcAction, gtmEcommerceEvent.field);
        }

        googleAnalytics('send', 'event', gtmEcommerceEvent.category, eventAction);
    }

    /**
     * Вспомогательная функция для добавления товаров/баннеров в событие,
     * передаваемое через analytics.js
     *
     * @param {Function} googleAnalytics
     * @param {String} type "Product", "Promo", "Impression" (с заглавной буквы)
     * @param {Array|undefined} items
     * @returns {boolean}
     */
    function googleAnalyticsAddEcommerceItems(googleAnalytics, type, items) {
        if (!items || !items.length) {
            return false;
        }
        forEach(items, function(item) {
            googleAnalytics('ec:add' + type, item);
        });
        return true;
    }

    // -------------------------------------------------------------------------
    // Функции конвертации данных событий э-коммерции в формате для gtag.js
    // в формат для GTM и analytics.js

    /**
     * @param {String} event
     * @param {Object} data
     * @returns {{
     *              action: undefined|string,
     *              category: undefined|string
     *              event: undefined|object
     *              field: undefined|object,
     *              items: undefined|object
     *              promos: undefined|object
     *          }}
     */
    function convertEcommerceEventForGTM(event, data) {
        // структура события для dataLayer:
        // {
        //   ecommerce: {    // переменная ecommerce; возвращается в свойстве 'e'
        //     currencyCode: "XYZ",
        //     actionField: <actionField>,
        //     <actionType>: <actionData>, // (или список товаров для impression)
        //   },
        //   event: "eventName"
        // }

        /** @var {object} значение event.ecommerce */
        var ecommerce = {};
        /** @var {boolean|string|Number}  тип события (название свойства в event.ecommerce)*/
        var actionType;
        /** @var {undefined|object} значение свойства event.ecommerce.[ТИП] */
        var actionData = {};
        /** @var {undefined|object} значение свойства event.ecommerce.[ТИП].actionField*/
        var actionField;
        /** @var {undefined|object} значение свойства event.ecommerce.[ТИП].products */
        var products;
        /** @var {undefined|object} значение свойства event.ecommerce.[ТИП].promotions */
        var promotions;

        var simpleActionTypeMapping = {
            view_item_list: 'impressions',
            view_item: 'detail',
            add_to_cart: 'add',
            remove_from_cart: 'remove',
            view_promotion: 'promoView',
            begin_checkout: 'checkout',
            checkout_progress: 'checkout',
            set_checkout_option: 'checkoutOption'
        };

        if (event in simpleActionTypeMapping) {
            actionType = simpleActionTypeMapping[event];
        } else if (event === 'select_content') {
            // select_content (+item) -> click (+actionField.list)
            // select_content (+promotions) -> promoClick
            if (data.items) {
                actionType = 'click';
                if (data.items[0] && data.items[0].list_name) {
                    actionField = { list: data.items[0].list_name };
                }
            } else if (data.promotions) {
                actionType = 'promoClick';
            }
        } else if (/purchase|refund/.test(event)) {
            actionType = event;
            actionField = {
                id: data.transaction_id,
                affiliation: data.affiliation,
                revenue: data.value,
                tax: data.tax,
                shipping: data.shipping,
                coupon: data.coupon
            };
        } else {
            // остальное не преобразовываем в события э-коммерции
            return {};
        }

        if (/checkout/.test(actionType)) {
            actionField = {
                step: data.checkout_step,
                option: data.checkout_option + (data.value ? ': ' + data.value : '')
            };
        }

        if (data.non_interaction) {
            actionField = actionField || {};
            actionField.nonInteraction = 1;
        }

        // записываем actionField в ecommerce[ТИП].actionField
        if (actionField) {
            actionData.actionField = actionField;
        }

        // * items -> products
        if (data.items) {
            products = actionData.products = mapArrayOfObjects(data.items, {
                list_name: 'list',
                list_position: 'position'
            });

            if (actionType === 'impressions') {
                actionData = products;
            }
        }
        // * promotions (id, name, creative_name, creative_slot) -> promotions (id, name, creative, position)
        if (data.promotions) {
            promotions = actionData.promotions = mapArrayOfObjects(data.promotions, {
                creative_name: 'creative',
                creative_slot: 'position'
            });
        }

        // Сформированный event.ecommerce
        /** @var {undefined|string} */
        var currencyCode = data.currency || getCookie('currency');
        if (currencyCode) {
            ecommerce.currencyCode = currencyCode;
        }
        ecommerce[actionType] = actionData;

        return {
            action: actionType,
            // Категории и действия как дефолтные для gtag.js
            // (https://developers.google.com/analytics/devguides/collection/gtagjs/events#default_google_analytics_events)
            category: /select_|view_/.test(event) ? 'engagement' : 'ecommerce',
            // всё вместе для GTM/Метрики
            event: ecommerce,
            // и по частям для analytics.js
            field: actionField,
            items: products,
            promos: promotions
        };
    }

    // -------------------------------------------------------------------------
    // Работа со списками товаров и рекламой

    /**
     * Отправляет информацию о клике на товар в списке или внутреннюю рекламу
     *
     * @param {Element} target
     * @param {'item'|'promo'} type
     */
    function sendEcommerceClickEvent(target, type) {
        var itemJson = getDataAttribute(target, type), itemData, eventData = {}, listName;
        if (itemJson) {
            itemData = pushIfIsValidJsonObject([], itemJson);
            if (itemData.length) {
                if (type === 'item') {
                    eventData.items = itemData;
                    eventData.content_type = 'product';
                    // Не соответствует документации gtag.js, но единственный работающий
                    // (март 2018) способ передачи названия списка для события select_content
                    listName = itemData[0].list_name;
                    if (listName) {
                        eventData.list_name = listName;
                    }
                } else {
                    eventData.promotions = itemData;
                }

                sendEvent('select_content', eventData);
            }
        }
    }

    /**
     * Отправляет просмотры товаров, накопленные в itemViews
     */
    function sendItemViews() {
        if (itemViews.length) {
            sendEvent('view_item_list', {
                event_category: 'engagement',
                items: itemViews
            });
            itemViews = [];
        }
    }

    /**
     * Отправляет просмотры рекламы, накопленные в promoViews
     */
    function sendPromoViews() {
        if (promoViews.length) {
            sendEvent('view_promotion', {
                event_category: 'engagement',
                promotions: promoViews
            });
            promoViews = [];
        }
    }

    // -------------------------------------------------------------------------
    // Функции для работы с событиями, передаваемыми через куки

    // Блокировка параллельного выполнения событий из кук — попытка подстраховаться от
    // отправки одной и той же информации в разных вкладках
    var lockCookieName = 'lock',
        lockCookieTimeoutSeconds = 5;

    var prevCookieCount = 0;
    function processCommerceCookies(isCalledOnUnload) {
        if (getAllCookieNames().length === prevCookieCount) {
            return;
        }

        // если стоит кука блокировки 'nc_stats_lock', перезапустимся через lockCookieTimeoutSeconds
        if (getCookie(lockCookieName)) {
            if (!isCalledOnUnload) {
                setTimeout(processCommerceCookies, lockCookieTimeoutSeconds * 1000);
            }
            return;
        }
        setCookie(lockCookieName, 1, lockCookieTimeoutSeconds); // ставим куку 'nc_stats_lock=1; max-age:5'

        // данные из всех кук вернутся nc_stats_event_* в виде массива с элементами:
        // [ 'eventAction', { eventDataObject } ]
        forEach(extractEventDataFromCookies(), applySendEventArguments);

        removeCookie(lockCookieName);
        prevCookieCount = getAllCookieNames().length;
    }

    /**
     * Возвращает данные из кук nc_stats_event_* в виде массива; куки удаляются.
     * @returns {Array}
     */
    function extractEventDataFromCookies() {
        var collectedJson = {};
        forEach(getAllCookieNames(), function(cookieNameSuffix) {
            var matches = cookieNameSuffix.match(/^event_(\d+_\d+)_(\d+)$/);
            if (!matches) {
                return;
            }

            var key = matches[1];
            if (!collectedJson[key]) {
                collectedJson[key] = [];
            }
            collectedJson[key][matches[2]] = getCookie(cookieNameSuffix);
            removeCookie(cookieNameSuffix);
        });

        var data = [], value;
        for (var i in collectedJson) {
            value = collectedJson[i].join('');
            log('cookie', [value]);
            pushIfIsValidJsonObject(data, value);
        }

        return data;
    }

    // -------------------------------------------------------------------------
    // Функции, связанные с определением видимости элементов

    var observedScrollItems = [],
        // «показанным» будет считаться элемент, от которого видна elementViewThreshold часть
        elementViewThreshold = 0.7; // если хотя бы 70% на экране → считается увиденным

    /**
     * Добавляет элементы с data-analytics-item, -promo, -view-category
     * к списку тех, для которых отслеживается показ на экране
     */
    function initScrollElements() {
        var elements = document.querySelectorAll(
                '[' + dataAttributePrefix + 'item],' +
                '[' + dataAttributePrefix + 'promo],' +
                '[' + dataAttributePrefix + 'view-category]'
            );

        forEach(elements, function(element) {
            if (getDataAttribute(element, '-init')) { // атрибут data-analytics--init
                return;
            }
            observedScrollItems.push(element);
            setDataAttribute(element, '-init', 1);
        });
    }

    /**
     * @param {Element} element
     * @returns {boolean}
     */
    function isShown(element) {
        var rect = element.getBoundingClientRect(),
            doc = document.documentElement; // window.innerWidth, innerHeight не учитывают зум в браузере

        return rect.height > 0 && // ≈ is visible
               rect.right >= 0 &&
               rect.bottom >= 0 &&
               (rect.left + rect.width  * elementViewThreshold) <= doc.clientWidth &&
               (rect.top  + rect.height * elementViewThreshold) <= doc.clientHeight;
    }

    /**
     * Выполняет отправку соответствующих событий при появлении элемента на экране
     */
    function processViewportChanges() {
        forEach(observedScrollItems, function(element, index) {
            if (element && isShown(element)) {
                delete observedScrollItems[index];
                // data-analytics-view-category
                if (getDataAttribute(element, 'view-category')) {
                    analyticsEventListener({ target: element, type: 'view' });
                }
                // data-analytics-item
                pushIfIsValidJsonObject(itemViews,  getDataAttribute(element, 'item'));
                // data-analytics-promo
                pushIfIsValidJsonObject(promoViews, getDataAttribute(element, 'promo'));
            }
        });
    }

    // -------------------------------------------------------------------------
    // Инициализация основных обработчиков событий

    /**
     * Добавление слушателей событий для отправки форм
     */
    var prevFormCount = 0; // dummy way to determine if there's a need to attach submit event listener
    function initFormsListeners() {
        var forms = document.forms,
            formCount = forms.length;

        if (formCount === prevFormCount) {
            return;
        }

        for (var i = 0; i < formCount; i++) {
            // no need to remove existing listeners: https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener#Multiple_identical_event_listeners
            addEventListener(forms[i], 'submit', analyticsEventListener, true);
        }
        prevFormCount = formCount;
    }

    /**
     * Запуск всех обработчиков
     */
    function processDomChanges() {
        // делаем что-то только на активной вкладке
        if (!(document.visibilityState !== 'visible')) { // undefined в IE9, поэтому условие с двойным отрицанием
            // Добавление слушателя событий для отправки форм
            initFormsListeners();
            // Просмотр товаров в списках и рекламы
            initScrollElements();
            // Проверка видимости элементов, за показом которых мы следим
            processViewportChanges();
            // Проверка наличия кук со сведениями о действиях э-коммерции
            processCommerceCookies();
        }
    }

    /**
     * Инициализация по готовности DOM
     */
    function initAfterDomLoaded() {
        // инициализация слушателей событий изменения прокрутки, лейаута, DOM’а
        var onViewportChange = waitForMoreChangesThen(processViewportChanges);
        var onDomChange = waitForMoreChangesThen(processDomChanges);

        addEventListener(window, 'scroll', onViewportChange);
        addEventListener(window, 'resize', onViewportChange);
        addEventListener(window, 'focus', onDomChange);

        onDomChange();

        // при изменениях в DOM добавляем слушателей отправки форм,
        // проверяем изменения кук с информацией о действиях с товарами
        if (window.MutationObserver) {
            new MutationObserver(onDomChange).observe(document, { childList: true, subtree: true });
        } else {
            // IE 9, 10: проверяем куки каждые 10 с:
            setInterval(processCommerceCookies, 10000);
        }
    }

    addEventListener(document, 'click', analyticsEventListener, true);
    addEventListener(document, 'DOMContentLoaded', function() {
        // поиск скрипта gtm.js, подключённого через <script>
        forEach(document.getElementsByTagName('script'), function(script) {
            if (/\/gtm.js\?/.test(script.src)) {
                log('found', ['gtm.js']);
                hasGtmJsScriptTag = true;
            }
        });

        // замена временной функции nc_stats_analytics_event в window на «настоящую»
        var eventPlaceholderFunction = window[globalSendEventFunctionName];
        if (eventPlaceholderFunction && eventPlaceholderFunction.E) {
            forEach(eventPlaceholderFunction.E, applySendEventArguments);
        }
        window[globalSendEventFunctionName] = sendEvent;

        // инициализация слушателей событий с небольшой задержкой, чтобы не
        // замедлять первоначальную отрисовку из-за MutationObserver
        setTimeout(initAfterDomLoaded, 1000);
    });

    addEventListener(window, 'beforeunload', function() {
        // в документации как GA, так и ЯМ не рекомендуется посылать события
        // при уходе со страницы, но, кажется, в современных браузерах всё в GA
        // нормально отправляется и так (используется navigator.sendBeacon либо
        // синхронные запросы, поэтому всё успевает отправиться), так что
        // пока не городим сложные обёртки для обработчиков событий;
        // а в ЯМ такие события всё равно не регистрируются
        sendItemViews();
        sendPromoViews();
        processCommerceCookies(true);
    });

})(window, document, 'addEventListener', 'getAttribute', 'data-analytics-', 'nc_stats_', 'nc_stats_analytics_event');
