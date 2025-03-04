// slider, top menu, tree selector

// ===========================================================================
// slider functions
// ===========================================================================
bindEvent(window, 'load', function() {

    var slideBar = document.getElementById('slideBar');
    var leftPane = document.getElementById('leftPane');
    var topLeftPane = document.getElementById('topLeftPane');
    var mainContainer = document.getElementById('mainContainer');
    var topMainContainer = document.getElementById('topMainContainer');
    var minPaneWidth = 200;
    var sliderEventIds = [];

    //FIXME: Файл можно выкинуть т.к. дальше этого условия он отрабатывается!!!
    //Скрипты слайдера тут: admin/js/main.js
    if ( !slideBar || !leftPane || !topLeftPane || !mainContainer || !topMainContainer) {
        return;
    }

    var divOverIFrame = document.createElement('DIV');
    divOverIFrame.style.position = 'absolute';
    divOverIFrame.style.top = '0';
    divOverIFrame.style.bottom = '0';
    divOverIFrame.style.left = '0';
    divOverIFrame.style.right = '0';

    function handleSliderMousedown(e) {
        if (!e) e = window.event;
        slideBar.style.backgroundColor = 'silver';
        slideBar.style.position = 'absolute';
        slideBar.style.top = 'auto';
        slideBar.mousedownOffsetX = e.clientX - slideBar.parentNode.offsetLeft;

        document.body.appendChild(divOverIFrame);

        sliderEventIds.push(bindEvent(document.body, 'mousemove', handleSliderMousemove));
        sliderEventIds.push(bindEvent(document.body, 'mouseup', handleSliderMouseup));

        if (document.attachEvent) {
            document.body.setCapture();
        } // IE

        if(e.preventDefault) {
            e.preventDefault();
        } else {
            e.returnValue = false; // IE branch - do more tests if that's not enough
        }
        return false;
    }

    function handleSliderMousemove(e) {
        if (!e) e = window.event;

        var x = e.clientX;
        var maxPaneWidth = screen.availWidth - minPaneWidth * 2;

        if (e.clientX < minPaneWidth) x = minPaneWidth;
        if (e.clientX > maxPaneWidth) x = maxPaneWidth;

        slideBar.style.left = x + 'px';
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        e.cancelBubble = true;
    }

    function handlerSliderMouseDblClick(e) {
        if (!e) e = window.event;

        var topSelectorBar = document.getElementById('treeSelector');

        if (topSelectorBar.clientWidth) topSelectorBar.style.width = topSelectorBar.clientWidth;
        var x = topSelectorBar.style.width.replace(/px/, '');

        if (!topSelectorBar.clientWidth) {
            topSelectorBar.style.display = 'block';
        }
        else {
            topSelectorBar.style.display = 'none';
            x = 0;
        }

        topLeftPane.style.width = x + 'px';
        leftPane.style.width = x + 'px';

        topMainContainer.style.width = document.body.clientWidth - x - slideBar.clientWidth + 'px';
        mainContainer.style.width = document.body.clientWidth - x - slideBar.clientWidth + 'px';

        //var cwindow = document.getElementById("mainViewIframe").contentWindow;
        //cwindow.resize_all_editareas();
    }

    function handleSliderMouseup(e) {
        // console.log('tada')
        if (!e) e = window.event;
        document.body.removeChild(divOverIFrame);
        if ( leftPane ) {
            // variables
            var x = e.clientX - slideBar.mousedownOffsetX;
            var maxPaneWidth = screen.availWidth - minPaneWidth * 2;
            var topSelectorBar = document.getElementById('treeSelector');
            // min - max width
            if (e.clientX < minPaneWidth) {
                x = x ? minPaneWidth : topSelectorBar.style.width.replace(/px/, '');
            }
            if (e.clientX > maxPaneWidth) x = maxPaneWidth;
            // left block
            topLeftPane.style.width = x + 'px';
            leftPane.style.width = x + 'px';
            // main block
            topMainContainer.style.width = document.body.clientWidth - x - slideBar.clientWidth + 'px';
            mainContainer.style.width = document.body.clientWidth - x - slideBar.clientWidth +'px';
        }
        slideBar.style.backgroundColor = 'transparent';
        slideBar.style.left = 'auto';

        // reset fixed width and display, may exist if slider been hidden or shoween
        topSelectorBar.style.width = '';
        topSelectorBar.style.display = 'block';

        var prevClassName = document.body.className;
        document.body.className = prevClassName;

        if (e.stopPropagation) {
            e.stopPropagation();
        }

        e.cancelBubble = true;
        for (var i=0; i < sliderEventIds.length; i++) {
            unbindEvent(sliderEventIds[i]);
        }

        sliderEventIds = [];
        if (document.detachEvent) {
            document.body.releaseCapture();
        } // IE

        var cwindow = document.getElementById("mainViewIframe").contentWindow;
        cwindow.resize_all_editareas();
    }


    if ( document.addEventListener ) {
        slideBar.addEventListener('mousedown', handleSliderMousedown, false);
        slideBar.addEventListener('dblclick', handlerSliderMouseDblClick, false);
    }

    else if ( document.attachEvent ) {
        slideBar.attachEvent('onmousedown', handleSliderMousedown);
        slideBar.attachEvent('ondblclick', handlerSliderMouseDblClick);
    }

} );


// ===========================================================================
// top menu functions
// ===========================================================================

// INIT
bindEvent(window, 'load',  function() {
    var mainMenu = document.getElementById('mainMenu');
    if (mainMenu) {
        menuInit(mainMenu);
    }
} );

var menuHideTimer;
var menuOpened = false;

function menuInit(oNode) {
    var oNodeItems = oNode.childNodes;
    if (!oNodeItems) return;
    for (var i = 0; i < oNodeItems.length; i++) {
        if (oNodeItems[i].nodeType == 1) {
            if (oNodeItems[i].tagName == 'LI') {
                if (oNodeItems[i].parentNode.id=='mainMenu') { // First Level
                    oNodeItems[i].levelOne = true;
                    bindEvent(oNodeItems[i], 'click', menuClickTopLevel);
                }

                bindEvent(oNodeItems[i], 'mouseover', menuNodeOver);
                bindEvent(oNodeItems[i], 'mouseout', menuNodeOut);
            }
            else if (oNodeItems[i].tagName == 'UL') {
                if (oNode.tagName == 'LI' && oNode.parentNode.id != 'mainMenu') {
                    oNode.firstChild.style.background = "url("+ICON_PATH+"arr_right.gif) center right no-repeat";
                }
            }
            else if (oNodeItems[i].tagName=='A') {
                var href = oNodeItems[i].href;
                // если ссылка не пустая, при нажатии на этот пункт закрыть меню
                if (href && href.substring(href.length-1) != '#') {
                    bindEvent(oNodeItems[i], 'click', menuHideOnItemClick);
                }
            }
            menuInit(oNodeItems[i]);
        }
    } // of for
} // of menuInit

var menuOpenedNodes = [];

function menuClickTopLevel(e) {
    // remove focus from link
    try {
        this.firstChild.blur();
    } catch(exptn) {}
    // open/close
    menuOpened ? menuHide() : menuShow(this);
    if (e && e.preventDefault) {
        e.preventDefault();
    }
    else if (window.event) {
        window.event.returnValue = false;
    }
}

function menuHideOnItemClick(e) {
    this.blur();
    menuHide();
    if (e && e.stopPropagation) {
        e.stopPropagation();
    }
    else if (window.event) {
        window.event.cancelBubble = true;
    }
}

function menuNodeOver(e) {
    clearInterval(menuHideTimer);
    if (this.levelOne && !menuOpened) {
        this.className = 'top_level_hover';
        return;
    }
    menuHide();
    menuShow(this);
    if (window.event) {
        window.event.cancelBubble = true;
    }
    else {
        e.stopPropagation();
    }
}

function menuNodeOut(e) {
    if (this.levelOne && !menuOpened) {
        this.className = '';
        return;
    }
    menuHideTimer = setTimeout(menuHide, 1000);
    if (window.event) {
        window.event.cancelBubble = true;
    }
    else {
        e.stopPropagation();
    }
}

function menuShow(oNode) {
    if (typeof oNode != 'object') return;
    if (dragManager && dragManager.dragInProgress) return;
    oNode.className = oNode.className ? oNode.className + ' on' : 'on';
    var oParentNode = oNode.parentNode;
    if (oParentNode.id != 'mainMenu') menuShow(oParentNode);
    menuOpenedNodes.push(oNode);
    menuOpened = true;
}

function menuHide() {
    for (var i=0; i < menuOpenedNodes.length; i++) {
        if (typeof menuOpenedNodes[i] != 'object') continue;
        menuOpenedNodes[i].className = menuOpenedNodes[i].className.replace(/top_level_hover/,'').replace(/on/,'');
    }
    menuOpenedNodes = [];
    menuOpened = false;
}

// ==========================================================================
// tree mode selector functions
// ==========================================================================
treeSelector = {

    currentMode: null,

    // initialize
    init: function() {

        var selector = document.getElementById('treeSelector');
        if (!selector) return false;

        if (document.getElementById('treeSelectorItems').getElementsByTagName('LI').length < 2) {
            // пользователю не из чего выбирать
            // убрать стрелочку
            document.getElementById('treeSelectorCaption').style.background = 'transparent';
            return true;
        }

        bindEvent(selector, 'click', treeSelector.toggle);

        // bind event handlers to selector items
        var selectorItems = document.getElementById('treeSelectorItems').childNodes;
        for (var i = 0; i < selectorItems.length; i++) {
            if (selectorItems[i].tagName == 'LI') {
                bindEvent(selectorItems[i], 'click', treeSelector.selectItem);
                bindEvent(selectorItems[i], 'mouseover', function() {
                    this.className = 'over';
                });
                bindEvent(selectorItems[i], 'mouseout',  function() {
                    this.className = '';
                });
            }
        }

    }, // of "init()"

    // show or hide
    toggle: function() {
        var oSelector = document.getElementById('treeSelector')
        oSelector.className = oSelector.className ? '' : 'on';
    }, // of "toggle()"

    //select item on click
    selectItem: function(e) {
        if (!e && window.event) {
            var oItem = window.event.srcElement;
        }
        else if (e && e.target) {
            var oItem = e.target;
        }

        var modeName = oItem.id.replace(/^treemode_/,"");
        treeSelector.changeMode(modeName);
    },

    // reload tree iframe
    changeMode: function(modeName, selectedNode) {

        var tree = document.getElementById('treeIframe');

        if (tree) {
            if (treeSelector.currentMode == modeName) {
                if (selectedNode) {
                    tree.contentWindow.tree.selectNode(selectedNode);
                    tree.contentWindow.tree.toggleNode(selectedNode, false, true);
                }
                return;
            }

            tree.contentWindow.location =
            ADMIN_PATH + 'tree_frame.php?mode='+modeName +
            (selectedNode ? '&selected_node='+selectedNode : '');
            treeSelector.currentMode = modeName;

            $nc('#tree_mode_name').html(tree_modes[modeName]);
            $nc(tree).attr('title', tree_modes[modeName]);

            nc.process_start('treeSelector.changeMode()');
            $nc(tree).load(function(){
                nc.process_stop('treeSelector.changeMode()');
            });

        }
    },

    removeTreeHighlight: function() {
        var tree = document.getElementById('treeIframe');

        if (tree && tree.contentWindow.tree) {
            tree.contentWindow.tree.removeHighlight();
        }
    }

}; // of treeSelector

bindEvent(window, 'load', treeSelector.init);


// ============================================================================
// LOGIN FORM
// ============================================================================

var loginFormOnSuccess = null;
/**
 *
 * @param {String} onSuccess js code evaluated on successfull authorization
 */
function loginFormShow(onSuccess) {
    document.getElementById('loginDialog').style.display = '';
    loginFormOnSuccess = onSuccess;
}

function loginFormPost() {
    var form = document.getElementById('loginForm');
    var req = new httpRequest();
    var status = req.request('POST', ADMIN_PATH + 'index.php',
    {
        'AUTH_USER': form.login.value,
        'AUTH_PW': form.password.value,
        'AuthPhase': 1,
        'NC_HTTP_REQUEST': 1
    },

    {
        '200': 'if (loginFormOnSuccess) { eval(loginFormOnSuccess); loginFormHide(); } '
    }
    );
}

function loginFormHide() {
    document.getElementById('loginDialog').style.display = 'none';
}

// ============================================================================
// Toolbars
// ============================================================================
toolbar = function(toolbarId, buttons) {

    this.groups = {};
    this.buttons = [];
    this.toolbarId = toolbarId;

    if (buttons) {
    // create buttons
    }
    else {
        this.makeEmptyToolbar();
    }

    toolbar.toolbarList[toolbarId] = this;
}

toolbar.toolbarList = {};

// toolbar class methods
toolbar.prototype.clear = function() {
    this.groups = [];
    document.getElementById(this.toolbarId).innerHTML = '';
}

toolbar.prototype.makeEmptyToolbar = function() {
    this.groups = [];
    // this need for clear block content in opera
    document.getElementById(this.toolbarId).innerHTML = "<img src='"+ICON_PATH+"px.gif' height='1' width='1' border='0' alt=''>";
}

/**
  * Добавить кнопку на тулбар
  * @param {Object} параметры кнопки
  *   id
  *   image
  *   caption
  *   title ----- (пока не используется)
  *   action
  *   className
  *   group - Кнопки из одной группы действуют вместе - активной
  *           может быть только одна кнопка из группы.
  *           Если группа не задана, действует как простая кнопка (выполняет
  *           действие, но не переходит в состояние "нажата")
  *   dragEnabled
  *   acceptDropFn
  *   onDropFn
  *   metadata: key->value (свойство key со значением value будет присвоено кнопке)
  *
  */
toolbar.prototype.addButton = function(btnParams) {

    var btn = document.createElement('div');

    if (btnParams.metadata) {
        for (var i in btnParams.metadata) {
            btn[i] = btnParams.metadata[i];
        }
    }

    btn.id = this.toolbarId + '_' + btnParams.id;
    btn.toolbarId = this.toolbarId;
    btn.className = 'button' + (btnParams.className ? ' ' + btnParams.className : '');


    //  btn.href = "#";
    btn.state = 'off'; // current state, on or off
    btn.action = btnParams.action;

    if (btnParams.dragEnabled) {
        top.dragManager.addDraggable(btn);
    }

    if (btnParams.acceptDropFn && btnParams.onDropFn) {
        var arrow = {
            name: 'arrowRight',
            bottom: -9,
            left: 4
        };
        top.dragManager.addDroppable(btn, eval(btnParams.acceptDropFn), eval(btnParams.onDropFn), arrow);
    }

    if (btnParams.group) {
        btn.groupId = btnParams.group;
        if (!this.groups[btnParams.group]) {
            this.groups[btnParams.group] = [];
        }
        this.groups[btnParams.group].push(btn.id);
    }

    btn.onselectstart = top.dragManager.cancelEvent;

    btn.turnOff = function() {
        this.className = this.className.replace(/\s*button_on\s*/, '');
        this.state = 'off';
        this.innerHTML = btnParams.caption ? "<li class='" + this.className + "'>" + btnParams.caption + "</li>" : "";
    }

    btn.turnOn = function() {
        if (this.groupId) {
            if (this.state == 'on') {
                return false;
            }
            var tb = toolbar.toolbarList[this.toolbarId];
            for (var i in tb.groups[this.groupId]) {
                var btnId = tb.groups[this.groupId][i];
                if (btnId!=this.id) {
                    var btn = document.getElementById(btnId);
                    if (btn) btn.turnOff();
                }
            }
        }
        this.className += " button_on";
        this.state = 'on';
        this.innerHTML = btnParams.caption ? "<li class='" + this.className + "'>" + btnParams.caption + "</li>" : "";
    }

    btn.onclick =  function() {
        if (this.groupId) {
            this.turnOn();
        }
        eval(this.action);
        return false;
    }

    document.getElementById(this.toolbarId).appendChild(btn);
    this.buttons.push(btn.id);
}

toolbar.prototype.getButton = function(btnId) {
    return document.getElementById(this.toolbarId + '_' + btnId);
}

toolbar.prototype.buttonIsActive = function(btnId) {
    var btn = document.getElementById(this.toolbarId + '_' + btnId);
    if (!btn) return false;
    return (btn.state=='on');
}

toolbar.prototype.removeButton = function(btnId) {
    var btn = this.getButton(btnId), classNames = [];
    if (!btn) return;
    // move divider classes to the next button   /* to be refined if needed */
    if ((classNames = btn.className.match(/(divider\w+)/g)) && btn.nextSibling) {
        for (var i=0; i < classNames.length; i++) {
            btn.nextSibling.className += ' ' + classNames[i];
        }
    }

    if (btn.groupId) {
        for (var i=0; i < this.groups.length; i++) {
            if (this.groups[btn.groupId][i] == btn.id) {
                this.groups[btn.groupId].splice(i, 0);
            }
        }

    }
    btn.parentNode.removeChild(btn);
}


// ============================================================================
// Resize in mozilla and opera
// ============================================================================

function triggerResize() {
    setTimeout(resizeApp, 500);
}

function resizeApp() {
    var h = window.innerHeight, w = window.innerWidth;
    if (h && w) { // mozilla

        var oMainView = document.getElementById("mainView");
        if (!oMainView) return;

        // determine header height
        var headerRows = ['trDummyTop', 'trHeader', 'trMainMenu'];
        var headerHeight = 0;
        for (var i in headerRows) {
            var headerRow = document.getElementById(headerRows[i]);
            if (headerRow) {
                headerHeight += headerRow.offsetHeight;
            }
        }

        oMainView.style.height = (h - headerHeight) + 'px';
        document.body.style.width = w + 'px';
    }
    else { // IE

    }

}

/**
  * DROP HANDLERS
  */
function subclassAcceptDrop(e) {
    var dragged = top.dragManager.draggedInstance,
    target  = top.dragManager.droppedInstance;

    // перемещение объекта в другой шаблон-в-разделе того же типа
    // нельзя 'переместить' объект в шаблон-в-разделе, в котором он уже находится
    if (dragged.type=='message' && dragged.typeNum==target.typeNum &&
        top.dragManager.draggedObject.getAttribute('messageSubclass')!=target.id)
        {
        return true;
    }

    return false;
}

function subclassOnDrop(e) {
    var dragged = top.dragManager.draggedInstance,
    target  = top.dragManager.droppedInstance;

    // move message to another subclass
    if (dragged.type == 'message') {
        moveMessage(dragged.typeNum, dragged.id, target.id);
    }

}


/**
  * переместить объект в другой раздел.
  * используется в d&d: message-to-subclass (container.js),
  * message-to-subdivision (tree_frame.js)
  */
function moveMessage(classId, messageId, destinationSubdivisionId) {

    var xhr = new httpRequest(),
    res = xhr.getJson(top.ADMIN_PATH + 'subdivision/drag_manager_message.php',
    {
        'dragged_type': 'message',
        'dragged_class': classId,
        'dragged_id': messageId,
        'target_type': 'subclass',
        'target_id': destinationSubdivisionId
    } );

    if (res==1) {
        // reload iframe
        top.mainView.refreshIframe();
    }
}

/**
  * Если объект был перетащен на раздел, в котором более одного подходящего
  * шаблона-в-разделе, показать варианты
  */
function showMessageToSubdivisionDialog(messageClass, messageId, subdivisionId) {
    document.getElementById('messageToSubdivisionIframe').contentWindow.location.href =
    top.ADMIN_PATH + 'subdivision/subclass_list.php?class_id='+messageClass+'&message_id='+messageId+'&sub_id='+subdivisionId;
    document.getElementById('messageToSubdivisionDialog').style.display = '';
}


// END OF DRAG HANDLERS
function updateUpdateIndicator(active) {
    firstChild = document.getElementById('mainMenuUpdate').firstChild.firstChild;
    if ( firstChild )
        firstChild.src = top.ICON_PATH + 'i_update' + (active ? '_active' : '') + '.gif';
}

function updateSysMsgIndicator(active) {
    var trayMessagesIcon = document.getElementById('trayMessagesIcon');
    if ( trayMessagesIcon ) {
        trayMessagesIcon.className = active ? '' : 'nc--disabled';
    }
}



// Init'n

bindEvent(window, 'resize', triggerResize);

if (window.opera) {
    bindEvent(window, 'load', function() {
        resizeApp();
    });
}