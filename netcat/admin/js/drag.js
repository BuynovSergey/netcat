

var dragLabel = null;

dragManager = {
    dragInProgress: false,
    dragLabel: null,
    draggedObject: null,
    dropTargetObject: null,
    dropPositionIndicator: null,
    dropPositionImages: {},

    // координаты начала перетаскивания
    dragEventX: 0,
    dragEventY: 0,
    // флаг видимости dragLabel (метка видна только если курсор при перетаскивании отклонился на определенное расстояние)
    dragLabelVisible: false,

    // тип и ID перетаскиваемого объекта в netcat
    draggedInstance: {}, // { type: x, id: n }
    // то же для объекта, на который перетаскиваем
    droppedInstance: {},

    init: function() {
        this.dragLabel = createElement('DIV', {
            id: 'dragLabel',
            'style.display': 'none'
        }, document.body);
        this.dropPositionIndicator = createElement('IMG', {
            id: 'dropPositionIndicator',
            'style.display': 'none',
            'style.position': 'absolute',
            'style.zIndex': 5000
        }, document.body);

        // preload drop position indicators
        this.dropPositionImages.arrowRight = new Image();
        this.dropPositionImages.arrowRight.src = ICON_PATH + 'drop_arrow_right.gif';

        this.dropPositionImages.arrowTop = new Image();
        this.dropPositionImages.arrowTop.src = ICON_PATH + 'drop_arrow_top.gif';

        this.dropPositionImages.line = new Image();
        this.dropPositionImages.line.src = ICON_PATH + 'drop_line.gif';

    }, // end of dragManager.init ------

    // init event listeners on drag start
    initHandlers: function() {
        dragManager.initHandlersInWindow(window);

        var frames = document.getElementsByTagName('IFRAME');
        for (var i=0; i<frames.length; i++) {
            if (frames[i].src.search(/^chrome-/) === -1) {
                dragManager.initHandlersInWindow(frames[i].contentWindow);
            }
        }
    },  // end of initHandlers

    initHandlersInWindow: function(targetWindow) {
        // store frame coords
        if (targetWindow.frameElement) {
            targetWindow.frameOffset = getOffset(targetWindow.frameElement);
        }
        else {
            targetWindow.frameOffset = {
                left: 0,
                top: 0
            };
        }

        // (1) onmousemove
        targetWindow.document.onmousemove = function(e) {
            if (targetWindow != top) targetWindow.scroller.scroll(e);

            if (targetWindow.event) { // IE
                e = targetWindow.event;
            }

            var x = e.clientX + targetWindow.frameOffset.left;
            var y = e.clientY + targetWindow.frameOffset.top;
            dragManager.labelMove(x, y);
        };

        targetWindow.document.onmouseout = function(e) {
            targetWindow.scroller.scrollStop(e);
        };

        // (2) onmouseup
        targetWindow.mouseUpEventId = bindEvent(targetWindow.document, 'mouseup', dragManager.dragEnd);
    },

    removeHandlers: function() {
        dragManager.removeHandlersInWindow(window);

        var frames = document.getElementsByTagName('IFRAME');
        for (var i=0; i<frames.length; i++) {
            if (frames[i].src.search(/^chrome-/) === -1) {
                dragManager.removeHandlersInWindow(frames[i].contentWindow);
            }
        }
    },

    removeHandlersInWindow: function(targetWindow) {
        // (1) onmousemove
        targetWindow.document.onmousemove = null;
        targetWindow.document.onmouseout = null;
        if (targetWindow.scroller) targetWindow.scroller.scrollStop();
        // (2) onmouseup
        unbindEvent(targetWindow.mouseUpEventId);
    },


    idRegexp: /_?([a-z]+)(\d+)?\-([a-f\d]+)$/i, // class-123, message12-345

    // получить тип и ID сущности netcat из ID html-элемента
    // напр., "siteTree_sub-348") -> { type: 'sub', id: '348' }
    // "mainViewToolbar_subclass-223" -> { type: 'subclass', id: '223' }
    // в случае message - еще параметр typeNum
    // "message12-345 -> { type : 'message', typeNum: 12, id: 345 }
    /**
     * Получить параметры перетаскиваемого объекта, необходимые для обработки
     * перетаскивания
     * @param object
     * @returns {*}
     */
    getInstanceData: function(object) {
        var result = this.getInstanceDataFromID(object.id);
        if (result.type && object.treeInstanceName) {
            result.treeInstanceName = object.treeInstanceName;
        }
        return result;
    },

    /**
     * Получить тип и ID сущности netcat из ID html-элемента
     * напр., "siteTree_sub-348" → { type: 'sub', id: '348' }
     * "mainViewToolbar_subclass-223" -> { type: 'subclass', id: '223' }
     * в случае message - еще параметр typeNum
     * "message12-345 -> { type : 'message', typeNum: 12, id: 345 }
     */
    getInstanceDataFromID: function(objectId) {
        var matches = objectId.match(dragManager.idRegexp);
        if (matches) {
            return {
                type: matches[1],
                typeNum: matches[2],
                id: matches[3]
            };
        }
        else {
            return {};
        }
    },

    /**
    * Функция-обработчик для объектов, которые объявлены как droppable
    * - определяет, может ли объект выступать в качестве цели для перетаскивания
    * - если да, перемещает индикаторы перетаскивания
    *
    * Должна быть *применена* (applied) к объекту (нужно использовать bindEvent)
    */
    dropTargetMouseOver: function(e) {
        if (!dragManager.dragInProgress) {
            return false;
        }

        if (dragManager.draggedObject.disallowMove === true) {
            var messages = ncLang.DisallowMoveAndDeleteInformation['disallow_move_sub'];
            dragManager.showInforarmation(messages.title, messages.text);
            return false;
        }

        if (!this.acceptsDrop) {
            return false;
        }

        if (dragManager.draggedObject == this) {
            return false;
        }

        var parentObject = this.parentNode;

        while (parentObject) {
            if (parentObject == dragManager.draggedObject) {
                return false;
            }
            parentObject = parentObject.parentNode;
        }

        dragManager.droppedInstance = dragManager.getInstanceData(this.id ? this : this.parentNode);

        if (this.acceptsDrop(this)) {
            // save the object as current target for the drop
            dragManager.dropTargetObject = this;
            if (this.dropIndicator && dragManager.dropPositionImages[this.dropIndicator.name]) {
                var ind = dragManager.dropPositionIndicator;
				if (this.ownerDocument != ind.ownerDocument) {
					var local_ind = $nc(this.ownerDocument.body).data('dropPositionIndicator');
					if (!local_ind) {
						local_ind = $nc(ind).clone(true);
						$nc(this.ownerDocument.body).append(local_ind);
						local_ind = local_ind.get(0);
						$nc(this.ownerDocument.body).data('dropPositionIndicator', local_ind);
					}
					ind  = local_ind;
					dragManager.dropPositionIndicator = ind;
				}
                ind.src = dragManager.dropPositionImages[this.dropIndicator.name].src;
                var pos = getOffset(this, false, false),
                    top = this.dropIndicator.top ? this.dropIndicator.top
                                                 : this.offsetHeight + this.dropIndicator.bottom;
                // положение «индикатора» настраивается в параметрах dropIndicator —
                // см. соответствующие вызовы addDroppable()
                ind.style.top = pos.top + top + 'px';
                ind.style.left = pos.left + this.dropIndicator.left + 'px';
                ind.style.display = '';
            }
        } // of accepts drop

        // stop event propagation
        if (e && e.stopPropagation) {
            e.stopPropagation();
        }
        else {
            if (this.document.parentWindow.event) {
                this.document.parentWindow.event.cancelBubble = true;
            }
        }
    },

    dropTargetMouseOut: function(e) {
        if (!dragManager.dragInProgress) {
            return;
        }
        dragManager.dropTargetObject = null;
        dragManager.droppedInstance = {};
        dragManager.dropPositionIndicator.style.display = 'none';
    },


    labelSetHTML: function(html) {
        this.dragLabel.innerHTML = html;
    },

    //
    labelMove: function(x, y, frameId) {
        // show only if mouse moved already for more than 12px
        if (!this.dragLabelVisible) {
            if (Math.abs(x - this.dragEventX) > 12 || Math.abs(y - this.dragEventY) > 12) {
                this.dragLabelVisible = true;
                this.dragLabel.style.display = '';
            }
        }
        if (!this.dragLabelVisible) return;

        this.dragLabel.style.top = y + 10 + 'px';
        this.dragLabel.style.left = x + 10 + 'px';

    }, // end of dragManager.labelMove


    /**
    * Сделать объект перетаскиваемым
    * @param {Object} handlerObject объект-обработчик перетаскивания ("ручка", за
    *   которую можно "тащить" объект draggedObject)
    * @param {Object} draggedObject объект, который собственно перетаскивается
    *   (если не указан, то это собственно handlerObject)
    */
    addDraggable: function(handlerObject, draggedObject) {
        handlerObject.ondragstart = dragManager.cancelEvent;
        if (!draggedObject) draggedObject = handlerObject;

        if (
            typeof(handlerObject['tagName']) != 'undefined' &&
                typeof(handlerObject['className']) != 'undefined' &&
                handlerObject.tagName == 'I' &&
                handlerObject.className
        ) {
            var classNames = handlerObject.className.split(' ');
            for (var i in classNames) {
                var className = classNames[i];
                if (className.replace(/\s/g, '') == 'nc-icon') {
                    handlerObject.style.cursor = 'move';
                }
            }
        }

        bindEvent(handlerObject, 'mousedown',
            function(e) {
                dragManager.dragStart(e ? e : window.event, draggedObject);
            }, true);
    },

    cancelEvent: function() {
        return false;
    },

    // начало перетаскивания
    // IE: window.event тоже передается первым параметром!
    dragStart: function(e, draggedObject) {
        if (nc.config('drag_mode') == 'disabled') {
            return;
        }

        // check if left button was pressed
        if ((e.stopPropagation && e.button != 0) ||    // DOM (Mozilla)
            (!e.stopPropagation && e.button != 1)      // IE
            ) {
            return;
        } // not a left mouse button

        dragManager.initHandlers();

        dragManager.draggedObject = draggedObject;
        dragManager.dragInProgress = true;

        var dragLabel = draggedObject.dragLabel;
        if (!dragLabel) {
            if (draggedObject.getAttribute('dragLabel')) {
                dragLabel = draggedObject.getAttribute('dragLabel');
            }
            else {
                dragLabel = draggedObject.innerHTML;
            }
        }

        dragManager.labelSetHTML(dragLabel);

        dragManager.draggedInstance = dragManager.getInstanceData(draggedObject);
        // store drag event coordinates
        var windowOffset = draggedObject.ownerDocument.defaultView ?
        draggedObject.ownerDocument.defaultView.frameOffset :  // moz
        draggedObject.ownerDocument.parentWindow.frameOffset;   // IE
        dragManager.dragEventX = e.clientX + windowOffset.left;
        dragManager.dragEventY = e.clientY + windowOffset.top;

        if (e.stopPropagation) {
            e.stopPropagation();
            e.preventDefault();
        }
        else if (e) {
            e.cancelBubble = true;
        }
    },

    // окончание перетаскивания
    dragEnd: function(e) {
        if (dragManager.dropTargetObject) {
            var processDrop = function() {
                dragManager.dropTargetObject.dropHandler();
                dragManager.removeDragData();
            };

            var confirmation = dragManager.getConfirmationMessages();

            if (confirmation) {
                dragManager.showConfirmationDialog(confirmation.title, confirmation.text, confirmation.button, processDrop);
            }
            else {
                processDrop();
            }
        }
        else {
            dragManager.removeDragData();
        }

        dragManager.dragInProgress = false;
        dragManager.dragLabelVisible = false;
        dragManager.dragLabel.style.display = 'none';
        dragManager.dropPositionIndicator.style.display = 'none';
        dragManager.removeHandlers();
    },

    removeDragData: function() {
        dragManager.draggedObject = null;
        dragManager.dropTargetObject = null;
        dragManager.draggedInstance = {};
        dragManager.droppedInstance = {};
    },

    /**
 * сделать объект принимающим перетаскиваемые объекты
 * @param {Object} object объект, который будет принимать перетаскиваемый объект(drop)
 * @param {Function} acceptFn функция проверки возможности сбрасывания объекта на object
 * @param {Function} onDropFn функция, выполняемая при сбрасывании объекта на object
 * @param {Object} dropIndicator см. dragManager.init() - position indicators. Объект со свойствами
 *   { name, top|bottom, left }
 */
    addDroppable: function(object, acceptFn, onDropFn, dropIndicator) {
        object.acceptsDrop = acceptFn;
        object.dropHandler = onDropFn;
        object.dropIndicator = dropIndicator;

        bindEvent(object, 'mouseover', dragManager.dropTargetMouseOver);
        bindEvent(object, 'mouseout',  dragManager.dropTargetMouseOut);
    },

    /**
     * Формирует хэш с данными для показа диалога. Если диалог не будет показываться,
     * возвращает false
     * @return {false|Object}
     */
    getConfirmationMessages: function() {
        if (nc.config('drag_mode') != 'confirm') {
            return false;
        }

        function formatName(string) {
            // Если есть что-то похожее на ID в начале строки, убрать его
            string = string.replace(/^\d+\.\s+/, '');
            var words = string.split(/\s+/),
                maxNumWords = 8,
                result = words.slice(0, maxNumWords).join(' ');
            return $nc.trim(result) + (words.length > maxNumWords ? '…' : '');
        }

        function getNameFromHTML(html) {
            return getNameFromElement('<div>' + html + '</div>');
        }

        function getNameFromElement(el) {
            var $el = $nc(el);
            // уберём тулбар в объектах (на случай, когда нет заголовков — будет взят весь текст в объекте)
            if ($el.find('.nc-toolbar')) {
                $el = $el.clone();
                $el.find('.nc-toolbar').remove();
            }

            return formatName(
                $el.find('h1,h2,h3,h4,h5,h6').first().text().trim() ||  // объект — взять название из первого заголовка
                $el.closest('li').children('a').text().trim() ||  // дерево — "Идентификатор. Название"
                $el.text().trim() || // например: ярлык вкладки — только название; объект без заголовка — всё, кроме тулбаров
                $el.attr('dragLabel') ||
                ''
            );
        }

        function getTreeFromSameWindow(element, treeInstanceName) {
            var doc = element.ownerDocument,
                elWindow = doc.defaultView || doc.parentWindow /* IE8 */;
            return elWindow[treeInstanceName] instanceof elWindow.dynamicTree ? elWindow[treeInstanceName] : null;
        }

        var position = 'inside',
            dropTargetName = getNameFromElement(dragManager.dropTargetObject),
            dropTargetType = dragManager.droppedInstance.type,
            lang = ncLang.DragAndDropConfirm;

        // определение типа перемещения в дереве: inside — внутрь другого узла дерева,
        // below — смена порядка следования элементов, firstIn — первый дочерний узел
        if (dragManager.draggedInstance.treeInstanceName && dragManager.droppedInstance.treeInstanceName) {
            // для упрощения здесь считается, что оба дерева находятся в одном окне
            var tree = getTreeFromSameWindow(dragManager.dropTargetObject, dragManager.droppedInstance.treeInstanceName);
            if (tree) {
                var draggedNodeData = tree.getNodeData(dragManager.draggedInstance),
                    targetNodeData = tree.getNodeData(dragManager.droppedInstance);

                 position = (dragManager.dropTargetObject.tagName == 'I' ? 'below' : 'inside');

                if (position == 'below') {
                    dropTargetName = getNameFromHTML(tree.getNodeData(targetNodeData.nodeId).name);
                    // изменился родитель — это перетаскивание в указанную позицию в другой узел дерева, а не просто изменение сортировки
                    if (targetNodeData.parentNodeId && draggedNodeData.parentNodeId != targetNodeData.parentNodeId) {
                        position = 'inside';
                        var parentNodeId = targetNodeData.parentNodeId;
                        dropTargetName = getNameFromHTML(tree.getNodeData(parentNodeId).name);
                        dropTargetType = dragManager.getInstanceDataFromID(parentNodeId).type;
                    }
                }
                else if (position == 'inside' && draggedNodeData.parentNodeId && draggedNodeData.parentNodeId == targetNodeData.nodeId) {
                    // перетаскивание внутри одного родительского узла на первое место
                    position = 'firstIn';
                }
            }
        }

        var dragType = dragManager.draggedInstance.type + '_' + position + '_' + dropTargetType;

        // Нет языковых констант для этого типа перетаскивания — не будет и диалога
        if (!dragType in lang) {
            return false;
        }

        var messages = lang[dragType];

        return {
            title: messages.title,
            text: messages.text.replace('%1', getNameFromElement(dragManager.draggedObject))
                               .replace('%2', dropTargetName),
            button: messages.button || lang.buttons[position] || lang.buttons.default
        };
    },

    /**
     * Показать диалог подтверждения перетаскивания
     * @param {String} headerText
     * @param {String} messageText
     * @param {String} buttonText
     * @param {Function} onConfirm
     */
    showConfirmationDialog: function(headerText, messageText, buttonText, onConfirm) {
        var dialog = new nc.ui.modal_dialog({ width: 400, height: 'auto', confirm_close: false });
        dialog.get_part('title').html(headerText);
        dialog.get_part('body').html('<div class="nc-drop-confirmation-dialog-body">' + messageText + '</div>');
        dialog.get_part('footer').append(
            $nc('<button>', { html: buttonText }).click(function() {
                onConfirm(); dialog.close();
            }),
            $nc('<button>', { html: ncLang.Cancel, 'data-action': 'close' }).click(function() {
                dragManager.removeDragData(); dialog.close();
            })
        );
        dialog.open();
    },

    /**
     * Показать окно с информацией
     * @param {String} headerText
     * @param {String} messageText
     */
    showInforarmation: function(headerText, messageText) {
        var dialog = new nc.ui.modal_dialog({ width: 400, height: 'auto', confirm_close: false });
        dialog.get_part('title').html(headerText);
        dialog.get_part('body').html('<div class="nc-drop-confirmation-dialog-body">' + messageText + '</div>');
        dialog.get_part('footer').append(
            $nc('<button>', { html: ncLang.Close, 'data-action': 'close' }).click(function() {
                dragManager.removeDragData(); dialog.close();
            })
        );
        dialog.open();
    }

};

bindEvent(window, 'load', function() {
    dragManager.init();
});
