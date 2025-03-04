/**
 * Инициализация работы с формами:
 * — показ формы в модальном диалоге
 * — отправка формы через XHR и обработка ответа
 * — показ ответа сервера во всплывающем окне
 *
 * Глобальные функции:
 * — nc_requests_form_init('имя-класса-блока-формы можно-несколько-через-пробел')
 * — nc_requests_form_popup_init({параметры})
 *
 * Эти функции обычно не должны использоваться напрямую, следует использовать методы
 * класса nc_requests.
 *
 * Разметка всплывающего окна имеет следующую структуру:
 *   <div class="tpl-block-popup tpl-block-popup-message|tpl-block-popup-form tpl-state-success|tpl-state-error" style="position: fixed">
 *       <div class="tpl-block-popup-background"></div>
 *       <div class="tpl-block-popup-container">
 *           <div class="tpl-block-popup-close-button"></div>
 *           <div class="tpl-block-popup-icon"></div>
 *           <div class="tpl-block-popup-body">(Текст ответа или форма)</div>
 *       </div>
 *   </div>
 * Стили для всплывающего окна могут быть заданы в SiteStyles.css.
 *
 * @param {String} formBlockClasses   Имя класса блока, в котором находится форма (или список классов через пробел).
 *      Также будет также добавлено к блоку всплывающего окна.
 *
 * NB! В nc_requests_form::get_form_script используется минифицированная версия
 * этого скрипта из файла forms.min.js.
 */

(function($, window) {
    var SUCCESS = '-message tpl-state-success',
        ERROR =   '-message tpl-state-error',
        closeOnEscapeEvent = 'keyup.popup-close',
        styleDataProperty = 'ncRequestsStyle';

    // ---- PRIVATE FUNCTIONS --------------------------------------------------

    /**
     * Создаёт <элемент> c указанным классом (к классу добавляется 'tpl-block-popup')
     * @param tagName
     * @param classNameSuffix
     * @param children
     * @returns {void|*|jQuery}
     */
    function el(tagName, classNameSuffix, children) {
        return $('<' + tagName + '>')
            .addClass('tpl-block-popup' + classNameSuffix)
            .append(children);
    }

    function div(classNameSuffix, children) {
        return el('div', classNameSuffix, children);
    }

    function addHidden(form, name, value) {
        return $('<input>', { type: 'hidden', name: name, value: value }).appendTo(form);
    }

    // ширина полос прокрутки
    var scrollbarWidth = -1;
    function getScrollbarWidth() {
        if (scrollbarWidth < 0) {
            var $div = $('<div/>').css({
                    position: 'absolute',
                    top: -1000,
                    left: -1000,
                    width: 100,
                    height: 100,
                    overflow: 'auto'
                })
                .prependTo('body').append('<div/>').find('div')
                .css({width: '100%', height: 200});
            scrollbarWidth = 100 - $div.width();
            $div.parent().remove();
        }
        return scrollbarWidth;
    }

    /**
     * Создаёт и показывает попап
     * @param {String} formBlockClasses  класс блока формы
     * @param {String} popupTypeClassPrefix  тип попапа ('-form', '-message'), будет добавлено 'tpl-block-popup' перед этой строкой
     * @param {String} popupBody  тело попапа
     * @param {Boolean} closeAll  закрывать все попапы при закрытии этого попапа
     * @param {Boolean} disableAnimation  не показывать анимацию при открытии/закрытии
     */
    function openPopup(formBlockClasses, popupTypeClassPrefix, popupBody, closeAll, disableAnimation) {
        // корневой блок
        var popup = div(popupTypeClassPrefix + ' tpl-block-popup ' + formBlockClasses)
                        .data({closeAll: closeAll, disableAnimation: disableAnimation});

        // функция, закрывающая попап(ы)
        var close = createClosePopupHandler(formBlockClasses);

        // блок, содержащий текст сообщения или форму
        var container = div('-container' + (disableAnimation ? '' : ' tpl-state-animated tpl-animation-fade-in-down'), [
                    el('a', '-close-button').click(close),
                    div('-icon'),
                    div('-body').html(popupBody)
                ]);

        popup.append([
            div('-background').click(close),
            container
        ]).appendTo('body');

        // центрирование контейнера по вертикали
        $(window).off('resize', centerPopups).on('resize', centerPopups);
        popup.find('img').on('load', centerPopups);
        centerPopups();

        // убираем полосы прокрутки на время показа попапа,
        // чтобы не появилось две полосы прокрутки, если содержимое попапа
        // не влезает на страницу
        var body = $('body'),
            $window = $(window);

        if (!body.data(styleDataProperty)) {
            body.data(styleDataProperty, body.attr('style') || ' ');
        }

        // есть полоса прокрутки?
        if ($(document).height() > $window.height()) {
            var currentBodyMarginLeft = body.offset().left,
                currentBodyMarginRight = $window.width() - body.outerWidth(true) - currentBodyMarginLeft;
            body.css({
                    'overflow-y': 'hidden',
                    'margin-left': currentBodyMarginLeft + 'px',
                    'margin-right': currentBodyMarginRight + getScrollbarWidth() + 'px'
                });
        }

        // закрытие попапа при нажатии Esc
        body.off(closeOnEscapeEvent).on(closeOnEscapeEvent, function(e) {
            if (e.keyCode == 27) { close(); }
        });

        // fade in; return popup
        if (disableAnimation) {
            popup.show();
        }
        else {
            popup.fadeIn();
        }

        return popup;
    }

    /**
     * Возвращает функцию, уничтожающие попап(ы) с указанными классами
     */
    function createClosePopupHandler(formBlockClasses) {
        return function() {
            var popupSelector = '.tpl-block-popup.' + formBlockClasses,
                allPopups = $(popupSelector),
                currentPopup = allPopups.last(),
                popupsToClose = currentPopup.data('closeAll') ? allPopups : currentPopup,
                fadeOutTime = currentPopup.data('disableAnimation') ? 1 : 400;

            popupsToClose.fadeOut(fadeOutTime, function () {
                $(this).remove();
            });

            // в Chrome нижеприведённый код иногда (?!) в обработчике fadeOut.done
            // может не обновлять атрибут style, setTimeout позволяет обойти это
            setTimeout(function() {
                // если все попапы закрыты:
                if (!$(popupSelector).length) {
                    // убираем обработчик прокрутки
                    $(window).off('resize', centerPopups);

                    var body = $('body');
                    body
                        // возвращаем полосы прокрутки
                        .attr('style', body.data(styleDataProperty))
                        // сбрасываем параметр, в котором записан оригинальное значение style (см. openPopup())
                        .data(styleDataProperty, '')
                        // убираем слушатель нажатий кнопок
                        .off(closeOnEscapeEvent);
                }
            }, fadeOutTime + 200);
        }
    }

    /**
     * Центрирует попап по вертикали (с использованием margin-top)
     */
    function centerPopups() {
        $('.tpl-block-popup').each(function() {
            var parent = $(this),
                container = parent.find('.tpl-block-popup-container').css('margin-top', ''),
                minMarginTop = parseInt(container.css('margin-top'), 10),
                newMarginTop = Math.round((parent.innerHeight() - container.outerHeight()) / 2);

            if (newMarginTop > minMarginTop) {
                container.css('margin-top', newMarginTop + 'px');
            }
        })
    }

    /**
     * Обработчик ошибок XHR
     * @param formBlockClasses
     * @returns {Function}
     */
    function createAjaxErrorHandler(formBlockClasses) {
        return function(xhr, textStatus, error) {
            if (textStatus != 'abort') {
                var message =
                    '<h4>' + textStatus + '</h4>' +
                    '<p>' + error + '</p>';
                openPopup(formBlockClasses, ERROR, message, false);
            }
        };
    }

    // ---- GLOBAL PUBLIC FUNCTIONS --------------------------------------------

    // nb: used in popup_button_settings_dialog.view.php
    var popupLoadedEvent = 'nc_requests_form_popup_loaded';

    /**
     * Инициализирует форму (отправка через XHR)
     * @global
     * @param formBlockClasses
     * @param context
     * @param adminMode
     */
    window.nc_requests_form_init = function(formBlockClasses, context, adminMode) {
        var form = $(context || 'body').parent().find('.' + formBlockClasses.replace(/\s+/, '.') + ' form'),
            submit = 'submit.ajax-submit';

        form.off(submit).on(submit, function(e) {
            e.preventDefault();
            if (adminMode) { return false; }

            var $form = $(this);
            $.ajax({
                type: $form.attr('method'),
                url: $form.attr('action'),
                data: $form.serialize() + '&isNaked=1',
                success: function(response) {
                    var success = response.indexOf('error') < 0;
                    openPopup(formBlockClasses, success ? SUCCESS : ERROR, response, success, false);
                },
                error: createAjaxErrorHandler(formBlockClasses)
            });
        });

        if (adminMode) {
            form.find('input,select,textarea,button').prop('tabindex', -1);
        }
    };

    /**
     * Инициализирует кнопку, открывающую форму в модальном диалоге
     * @global
     * @param formBlockClasses
     * @param buttonSelector
     * @param formAction
     * @param hiddenInputs
     * @param overlayTriggerHtml
     */
    window.nc_requests_form_popup_init = function(formBlockClasses, buttonSelector, formAction, hiddenInputs, overlayTriggerHtml) {
        var button = $(buttonSelector);
        if (!button.length) {
            throw 'Button not found: ' + buttonSelector;
        }

        var form = button.closest('form');
        if (!form.length) {
            form = $('<form/>');
            button.wrap(form);
        }

        for (var key in hiddenInputs) {
            addHidden(form, key, hiddenInputs[key]);
        }

        if (overlayTriggerHtml) {
            button
                .wrap($('<div/>').css({ position: 'relative', display: 'inline-block' }))
                .after(overlayTriggerHtml);
        }

        var submitEvent = 'click.ajax-load',
            adminMode = !!overlayTriggerHtml,
            currentRequest;

        button.off(submitEvent).on(submitEvent, function(e) {
            e.preventDefault();
            if (currentRequest) {
                currentRequest.abort();
            }

            var requestParameters = {
                type: 'POST',
                url: form.attr('action') || formAction,
                data: form.serialize() + '&isNaked=1',
                success: function(response) {
                    nc_event_dispatch(popupLoadedEvent); //$(document).trigger(popupLoadedEvent); почему-то не срабатывала повторно
                    var popup = openPopup(formBlockClasses, '-form', response, true, adminMode)
                                    .data({ requestParameters: requestParameters, formBlockClasses: formBlockClasses });
                    nc_requests_form_init(formBlockClasses, popup, adminMode);
                    if (!adminMode) {
                        setTimeout(function() {
                            popup.find('input,select,textarea').filter(':visible').first().focus();
                        }, 450);
                    }
                },
                error: createAjaxErrorHandler(formBlockClasses)
            };

            currentRequest = $.ajax(requestParameters);
        });
    };

    /**
     * Перезагружает содержимое попапа с формой.
     * Функция используется при редактировании свойств кнопки.
     */
    window.nc_requests_form_popup_reload = function() {
        $('.tpl-block-popup').each(function() {
            var popup = $(this),
                formBlockClasses = popup.data('formBlockClasses'),
                requestParameters = popup.data('requestParameters');

            if (formBlockClasses && requestParameters) {
                $.ajax(requestParameters);
                $(document).one(popupLoadedEvent, createClosePopupHandler(formBlockClasses));
            }
        });
    }

})(jQuery, window);
