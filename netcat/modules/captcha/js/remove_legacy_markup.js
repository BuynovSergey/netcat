/**
 * Скрипт для скрытия элементов стандартной каптчи.
 * Работает в IE9+ и других современных браузерах.
 */
(function(document, nodesToHideText, nodesToHideSelectors) {
    if (!document.createTreeWalker) {
        // IE 8 и ниже игнорируются
        return;
    }

    /**
     * Возвращает следующий за элементом <br> или null
     * @param {Node} el
     * @returns {Element|null}
     */
    function getSubsequentBr(el) {
        var next = el.nextSibling;
        if (next && next.tagName == 'BR') {
            return next;
        }
        return null;
    }

    /**
     * Прячет элемент и (опционально) следующие за ним <br>ы
     * @param {Element} el
     * @param {Boolean} hideNextBrs
     */
    function hide(el, hideNextBrs) {
        if (el && el.style) {
            el.style.display = 'none';
            if (hideNextBrs) {
                hide(getSubsequentBr(el), true);
            }
        }
    }

    /**
     * Вызывает callback для каждого узла соответствующего селектору в context
     * (context.querySelectorAll)
     * @param {Document|Element} context
     * @param {String} selector
     * @param {Function} callback
     */
    function forEachNode(context, selector, callback) {
        var nodes = context.querySelectorAll(selector),
            length = nodes.length,
            i = 0;
        for (; i < length; i++) {
            callback(nodes[i]);
        }
    }

    // регвыры для отбора текстовых узлов
    var textNodeRegexps = nodesToHideText.map(function (text) {
        return new RegExp(
            text.trim()
                .replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1") // спецсимволы RegExp
                .replace(/\s+/g, '\\s+'), // пробелы
            'i'
        );
    });
    var numTextNodeRegexps = textNodeRegexps.length;

    // допустимая разница в длине текстового узла при совпадении текста
    // (например, двоеточие в конце, значок "(*)")
    var acceptedTextLengthDifference = 10;
    /**
     * Отбор текстовых узлов по содержимому
     * @param {Text} node
     * @returns {boolean}
     */
    function textNodeFilter(node) {
        var text = node.textContent.trim();
        if (text.length) {
            for (var i = 0; i < numTextNodeRegexps; i++) {
                var match = text.match(textNodeRegexps[i]);
                if (match && match[0].length + acceptedTextLengthDifference >= text.length) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Собственно действия со страницей
     * (таймаут, чтобы скрипт выполнился после построения страницы)
     */
    setTimeout(function() {
        forEachNode(document, 'input[name=nc_captcha_code]', function(input) {
            // --- прячем input[name=nc_captcha_code] и <br>ы после него ---
            hide(input, true);
            // убираем required у input, чтобы не было непонятных сообщений от браузера об обязательности заполнения невидимого поля
            input.removeAttribute('required');

            // --- прячем лишний текст и <br>ы после него ---
            // IE принимает в качестве фильтра функцию, все остальные — массив { acceptNode: function }
            // для «нормализации» не используем этот фильтр в TreeWalker
            var textNodesWalker = document.createTreeWalker(input.form, NodeFilter.SHOW_TEXT, null, false);
            var textNode, textNodes = []; // собираем в массив, иначе итерация может оборваться из-за изменения в DOM
            while (textNode = textNodesWalker.nextNode()) {
                if (textNodeFilter(textNode)) {
                    textNodes.push(textNode);
                }
            }

            textNodes.forEach(function(textNode) {
                hide(getSubsequentBr(textNode), true);
                // IE не умеет node.remove()
                textNode.parentNode.removeChild(textNode);
            });

            // --- прячем лишние элементы по селекторам ---
            nodesToHideSelectors.forEach(function(selector) {
                forEachNode(input.form, selector, hide);
            });
        });
    }, 1);

})(document, REMOVED_LEGACY_TEXT, REMOVED_LEGACY_BLOCKS);