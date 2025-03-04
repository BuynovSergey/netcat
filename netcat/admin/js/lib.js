/* $Id: lib.js 8189 2012-10-11 15:43:20Z vadim $ */

// EVENT BINDING *****************************************************************
var _eventRegistry = [];
var _lastEventId = 0;
/**
 * Добавление обработчика события к объекту
 * @param {Object} object
 * @param {String} eventName без 'on'
 * @param {Object} eventHandler
 * @param {Boolean} НЕ использовать конструкцию eventHandler.apply(object) в IE
 *  использование apply позволяет в IE обращаться к object в eventHandler как
 *  к this (т.е. как в Mozilla)
 * @return {Number} eventId
 */
function bindEvent(object, eventName, eventHandler, dontAddApplyInExplorer) {

    var fn = eventHandler;
    if (object.addEventListener) {
        object.addEventListener(eventName, fn, false);
    }
    else if (object.attachEvent) {
        if (!dontAddApplyInExplorer) fn = function() {
            eventHandler.apply(object);
        }
        object.attachEvent("on" + eventName, fn);
    }
    // добавлен "event": чтобы не "съезжали" id при удалении события из реестра
    var eventId = "event" + _lastEventId++;
    _eventRegistry[eventId] = {
        object: object,
        eventName: eventName,
        eventHandler: fn
    };
    return eventId;
}

/**
 * Удаление обработчика события eventId, добавленного через bindEvent()
 * @param {Object} eventId
 * @return {Boolean}
 */
function unbindEvent(eventId) {

    if (!_eventRegistry[eventId] || typeof _eventRegistry[eventId] != 'object') return false;

    var object = _eventRegistry[eventId].object;
    var eventName = _eventRegistry[eventId].eventName;
    var eventHandler = _eventRegistry[eventId].eventHandler;

    if (object.removeEventListener) {
        object.removeEventListener(eventName, eventHandler, false);
    }
    else if (object.detachEvent) {
        object.detachEvent("on" + eventName, eventHandler);
    }

    _eventRegistry.splice(eventId, 1);

    return true;
}

/**
  * отвязка всех событий
  */
function unbindAllEvents() {
    for (var i in _eventRegistry) {
        try {
            unbindEvent(i);
        } catch(e) {}
    }
}

// remove all events on page unload to prevent memory leaks
bindEvent(window, 'unload', unbindAllEvents);


/**
 * Позиция объекта относительно BODY или объекта с id=STOPID
 * @param {Object} object
 * @param {String} stopObjectId
 * @param {Boolean} addFrameOffset

 * @return {Object} {left: x, top: y}
 */
function getOffset(object, stopObjectId, addFrameOffset) {

    var pos = {
        top: 0,
        left: 0
    };

    // weak chain
    if (addFrameOffset) {
        if (object.ownerDocument.defaultView) {
            pos.top  = object.ownerDocument.defaultView.frameOffset.top -
            object.ownerDocument.body.scrollTop;
            pos.left = object.ownerDocument.defaultView.frameOffset.left -
            object.ownerDocument.body.scrollLeft;
        }
        else {
            pos.top = object.ownerDocument.parentWindow.frameOffset.top -
            object.ownerDocument.body.scrollTop;
            pos.left = object.ownerDocument.parentWindow.frameOffset.left -
            object.ownerDocument.body.scrollLeft;
        }
    }

    var isIE = (document.all ? true : false); // weak chain

    /*
  if (isIE) {
    // баг IE? если высота объекта не задана и он находится внутри
    // iframe, то offset - значение относительно BODY!
    if (ieOffsetBugX) { pos.left += object.offsetLeft; }
    if (ieOffsetBugY) { pos.top  += object.offsetTop; }
    if (ieOffsetBugX && ieOffsetBugY) { return pos; }
  }
*/
    //var str = "";
    while (object && object.tagName!="BODY") {
        if (!isIE || (isIE && object.id != "siteTreeContainer")) {
            pos.left += object.offsetLeft;
        }
        pos.top += object.offsetTop;

        object = object.offsetParent;
        if (stopObjectId && object.id == stopObjectId) break;
    }
    //alert(str);
    return pos;
}


/**
 * Create element
 * @param {String} tagName
 * @param {Object} attributes hash [optional]
 * @param {Object} oParent [optional]
 */
function createElement(tagName, attributes, oParent) {
    var obj = document.createElement(tagName);
    for (var i in attributes) {
        if (i.indexOf('.')) { // e.g. 'style.display'
            eval('obj.'+i+'=attributes[i]');
        } else {
            obj[i] = attributes[i];
        }
    }
    if (oParent) {
        oParent.appendChild(obj);
    }
    return obj;
}

// FADE OUT FUNCTIONS
var fadeIntervals = [];

/**
  * FADE OUT
  * @param {String} ID объекта
  */
function fadeOut(id)
{
    var dst = document.getElementById(id);

    if (dst.filters)
    {
        dst.style.filter="blendTrans(duration=1)";

        if (dst.filters.blendTrans.status != 2)
        {
            dst.filters.blendTrans.apply();
            dst.style.visibility="hidden";
            dst.filters.blendTrans.play();
        }
        return;
    }

    if (dst.style.opacity == 0)
    {
        clearInterval(fadeIntervals[id]);
        fadeIntervals[id] = null;
        dst.style.visibility='hidden';
        dst.style.opacity = 1;
        return;
    }

    dst.style.opacity = (Number(dst.style.opacity) - 0.05);

    // setup interval
    if (!fadeIntervals[id]) fadeIntervals[id] = setInterval("fadeOut('"+id+"')",40);
}



// returns all object property values as a STRING
function dump(object, regexpFilter) {
    var str = '';
    for (i in object) {
        if (!regexpFilter || i.match(regexpFilter)) {
            str += i+' = ' + object[i]+"<br>\n";
        }
    }
    return str;
}


function nc_dump (x,  l) {
    l = l || 0;
    var i, r = '', t = typeof x, tab = '';

    if (x === null) {
        r += "(null)\n";
    }
    else if (t == 'object') {
        l++;
        for (i = 0; i < l; i++) tab += ' ';

        if (x && x.length) t = 'array';

        r += '(' + t + ") :\n";

        for (i in x) {
            try {
                r += tab + '[' + i + '] : ' + nc_dump(x[i], (l + 1));
            } catch(e) {
                return "[error: " + e + "]\n";
            }
        }
    }
    else {
        if (t == 'string') {
            if (x == '') {
                x = '(empty)';
            }
        }

        r += '(' + t + ') ' + x + "\n";

    }

    return r;
}

/* для задания соответсвия полей пользователя */
nc_mapping_fields = function ( fields1, fields2, parent_div, name, data_from ) {
    this.nums = 0; // количеcтво соответсвий
    this.fields1 = fields1;
    this.fields2 = fields2;
    this.parent_div = parent_div || 'field_div';
    this.name = name;
    this.data_from = data_from;

}
nc_mapping_fields.prototype = {
    add: function ( val1, val2 ) {
        this.nums++;
        var con_id = this.parent_div+"_con_"+this.nums;

        if ( this.nums == 1 ) {
            $nc('#' + this.parent_div).append("<div id='"+con_id+"title'></div>");
            $nc('#' + con_id + 'title').append("<div  class='mf_fl1'>"+ncLang.FieldFromUser+":</div>");
            $nc('#' + con_id+ 'title').append("<div class='s_img s_img_darrow mf_arrow' style='visibility: hidden; height: 0px;'></div>");
            $nc('#' + con_id+ 'title').append("<div  class='mf_fl2'>"+this.data_from+":</div>");
            $nc('#' + con_id+ 'title').append("<div id='"+this.parent_div+"clear_"+this.nums+"' style='clear:both'></div>");
        }

        $nc('#' + this.parent_div).append("<div id='"+con_id+"'></div>");

        $nc('#' + con_id).append("<div id='"+this.parent_div+"_field1_"+this.nums+"' class='mf_fl1'></div>");
        $nc('#' + con_id).append("<div class='s_img s_img_darrow mf_arrow'></div>");
        $nc('#' + con_id).append("<div id='"+this.parent_div+"_field2_"+this.nums+"' class='mf_fl2'></div>");
        $nc('#' + con_id).append("<div id='"+this.parent_div+"_drop_"+this.nums+"' class='mf_drop' onclick='"+this.name+".drop("+this.nums+")'><div class='icons icon_delete' title='"+ncLang.Drop+"' style='margin-top:-3px'></div> "+ncLang.Drop+"</div>");
        $nc('#' + con_id).append("<div id='"+this.parent_div+"_clear_"+this.nums+"' style='clear:both'></div>");

        $nc("#"+this.parent_div+"_field1_"+this.nums).html("<select id='"+this.parent_div+"_field1_value_"+this.nums+"' name='"+this.parent_div+"_field1_value_"+this.nums+"'></select>");
        $nc("#"+this.parent_div+"_field2_"+this.nums).html("<select id='"+this.parent_div+"_field2_value_"+this.nums+"' name='"+this.parent_div+"_field2_value_"+this.nums+"'></select>");

        for (i in this.fields1) {
            $nc("#"+this.parent_div+"_field1_value_"+this.nums).append("<option value='"+i+"'>" + this.fields1[i] + "</option>");
        }
        if ( val1 ) $nc("#"+this.parent_div+"_field1_value_"+this.nums+" [value='"+val1+"']").attr("selected", "selected");

        for (i in this.fields2) {
            $nc("#"+this.parent_div+"_field2_value_"+this.nums).append("<option value='"+i+"'>" + this.fields2[i] + "</option>");
        }
        if ( val2 ) $nc("#"+this.parent_div+"_field2_value_"+this.nums+" [value='"+val2+"']").attr("selected", "selected");
    },

    drop: function ( id ) {
        $nc("#"+this.parent_div+"_con_"+id).remove();
    }
}

nc_openidproviders = function () {
    this.nums = 0;
    this.div_id = 'openid_providers';
}
nc_oauthproviders = function () {
    this.nums = 0;
    this.div_id = 'oauth_providers';
}
nc_openidproviders.prototype = {
    add: function ( name, url, imglink ) {
        this.nums++;
        if ( !imglink ) imglink = MODULE_AUTH_OPENID_ICON_PATH;
        if ( !name ) name ='';
        if ( !url ) url = '';
        var con_id = this.div_id+"_con_"+this.nums;
        $nc('#' + this.div_id).append("<div id='"+con_id+"'></div>");

        $nc('#' + con_id).append("<div class='img'><img id='openid_providers_img_"+this.nums+"' src='"+imglink+"' alt='' /></div>");
        $nc('#' + con_id).append("<div class='name'><input name='openid_providers_name_"+this.nums+"' type='text' value='"+name+"' /></div>");
        $nc('#' + con_id).append("<div class='imglink'><input id='openid_providers_imglink_"+this.nums+"'  name='openid_providers_imglink_"+this.nums+"' type='text' value='"+imglink+"' /></div>");
        $nc('#' + con_id).append("<div class='url'><input name='openid_providers_url_"+this.nums+"' type='text' value='"+url+"' /></div>");
        $nc('#' + con_id).append("<div class='drop' onclick='op.drop("+this.nums+")'><i class='nc-icon nc--remove'></i> "+ncLang.Drop+"</div>");
        $nc('#' + con_id).append("<div style='clear:both;'></div>");

        $nc('#openid_providers_imglink_'+this.nums).change (
            function() {
                $nc('#' + $nc(this).attr('id').replace('imglink','img') ).attr('src', $nc(this).val());
            }
            );
    },

    drop: function ( id ) {
        $nc("#"+this.div_id+"_con_"+id).remove();
    }
}

nc_oauthproviders.prototype = {
    add: function (imglink, name, provider, appid, pubkey, seckey ) {
        this.nums++;
        if ( !imglink ) imglink = MODULE_AUTH_OAUTH_ICON_PATH;
        if ( !provider ) provider ='';
        if ( !name ) name ='';
        if ( !appid ) appid = '';
        if ( !seckey ) seckey = '';
        if ( !pubkey ) pubkey = '';

        var con_id = this.div_id+"_con_"+this.nums;
        $nc('#' + this.div_id).append("<div id='"+con_id+"'></div>");

        $nc('#' + con_id).append("<div class='img'><img id='oauth_providers_img_"+this.nums+"' src='"+imglink+"' alt='' /></div>");
        $nc('#' + con_id).append("<div class='name'><input name='oauth_providers_name_"+this.nums+"' type='text' value='"+name+"' /></div>");
        $nc('#' + con_id).append("<div class='provider'><input name='oauth_providers_provider_"+this.nums+"' type='text' value='"+provider+"' /></div>");
        $nc('#' + con_id).append("<div class='imglink'><input id='oauth_providers_imglink_"+this.nums+"'  name='oauth_providers_imglink_"+this.nums+"' type='text' value='"+imglink+"' /></div>");
        $nc('#' + con_id).append("<div class='appid'><input id='oauth_providers_appid_"+this.nums+"'  name='oauth_providers_appid_"+this.nums+"' type='text' value='"+appid+"' /></div>");
        $nc('#' + con_id).append("<div class='pubkey'><input id='oauth_providers_pubkey_"+this.nums+"'  name='oauth_providers_pubkey_"+this.nums+"' type='text' value='"+pubkey+"' /></div>");
        $nc('#' + con_id).append("<div class='seckey'><input id='oauth_providers_seckey_"+this.nums+"'  name='oauth_providers_seckey_"+this.nums+"' type='text' value='"+seckey+"' /></div>");
        $nc('#' + con_id).append("<div class='drop' onclick='oap.drop("+this.nums+")'><i class='nc-icon nc--remove'></i> "+ncLang.Drop+"</div>");
        $nc('#' + con_id).append("<div style='clear:both;'></div>");

        $nc('#oauth_providers_imglink_'+this.nums).change (
            function() {
                $nc('#' + $nc(this).attr('id').replace('imglink','img') ).attr('src', $nc(this).val());
            }
        );
    },

    drop: function ( id ) {
        $nc("#"+this.div_id+"_con_"+id).remove();
    }
}


/* создание/редактирование параметра визуальных настроек */
nc_customsettings = function (type, subtype, subtypes, hasdefault, can_have_initial_value) {
    this.subtypes = subtypes;
    this.subtype = subtype || '';
    this.type = type || '';
    this.hasdefault = hasdefault;
    this.can_have_initial_value = can_have_initial_value;
};

nc_customsettings.prototype = {

    changetype: function () {
        this.type = $nc("#type :selected").val();
        $nc('#cs_subtypes').html('');
        $nc('#cs_subtypes_caption').hide();
        var st = this.subtypes[this.type];
        // показать или скрыть "значение по умолчанию"
        if (this.hasdefault[this.type]) {
            $nc('#def').show();
        }
        else {
            $nc('#def').hide();
        }

        $nc('#initial_value').toggle(this.can_have_initial_value[this.type]);

        var k, s_v, s_n;
        if (st.length) {
            $nc('#cs_subtypes_caption').show();
            $nc('#cs_subtypes').html("<select style='width: 100%;' id='subtype' name='subtype' onchange='nc_cs.changesubtype()'></select>");
            for (var i = 0; i < st.length; i++) {
                for (k in st[i]) {
                    s_v = k;
                    s_n = st[i][k];
                }
                $nc('#subtype').append("<option value='" + s_v + "'>" + s_n + "</option>");
            }
            if (this.subtype) {
                $nc("#subtype [value='" + this.subtype + "']").attr("selected", "selected");
            }
            else {
                $nc("#subtype :first").attr("selected", "selected");
            }
        }

        this.show_extends();
        this.changesubtype();
    },

    changesubtype: function () {
        this.subtype = $nc("#subtype :selected").val();
        this.show_extends();
    },

    show_extends: function () {
        var t = this.type;
        if (this.subtype) {
            t += '_' + this.subtype;
        }
        $nc(".cs_extends").hide();
        $nc(".cs_extends :input").attr('disabled', true);
        $nc("#extend_" + t).show();
        $nc("#extend_" + t + " :input").removeAttr('disabled');
    }
};

// ---------------------------------------------------------------------------
// HTTP REQUEST
// ---------------------------------------------------------------------------
// Create XMLHttpRequest object

/**
 * This XMLHttpRequest is NOT ASYNCHRONOUS by default
 * @param {Boolean} isAsync
 */
function httpRequest(isAsync) {
    this.xhr = null;

    try {
        this.xhr = new XMLHttpRequest();
    } catch(e) { // Mozilla, IE7
        try {
            this.xhr = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(e) {
            try {
                this.xhr = new ActiveXObject("Microsoft.XMLHTTP");
            } catch(e) {
                return false;
            }
        }
    }

this.isAsync = isAsync ? true : false;
this.statusHandlers = {};
}

// ----------------------------------------------------------------------------
/**
 * Make request
 * @param {String} method GET|POST
 * @param {String} url
 * @param {Object|String} urlParams { hash }
 * @param {Object} statusHandlers  e.g. { '200': 'alert(200)'. '403': 'alert("NO RIGHTS") }
 *    { '*': 'alert("Обработчик всех ответов - с любым статусом")' }
 * @return {String} status ('200', '404' etc) -- only if isAsync==false
 */
httpRequest.prototype.request = function(method, url, urlParams, statusHandlers) {
    this.statusHandlers = statusHandlers;
    if (method!='POST') method = 'GET';

    var encParams = (typeof urlParams == 'string') ? urlParams : urlEncodeArray(urlParams);

    if (encParams && method=='GET') {
        url += (url.match(/\?/) ?  "&" : "?") + encParams;
    }

    this.xhr.open(method, url, this.isAsync);
    if (method=='POST') {
        this.xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
    }
    this.xhr.send(encParams);

    if (this.isAsync) {
        var oXhr = this;
        this.xhr.onreadystatechange = function() {
            oXhr.trackStatus();
        };
    }
    else {
        this.trackStatus();
        return this.xhr.status;
    }
}

httpRequest.prototype.trackStatus = function() {

    try {
        if (!this.statusHandlers) return;

        var handler = this.statusHandlers[this.xhr.status];

        // DEFAULT STATUS HANDLER (fires on all status codes)
        if (!handler && this.statusHandlers['*']) {
            handler = this.statusHandlers['*'];
        }

        if (handler) {
            try {
                eval(handler);
            }
            catch(e) {
                alert('Failed ['+this.xhr.status+']: '+handler);
            }
        }
    } catch (outerException) {}
}

// getJson requests are always synchronous
httpRequest.prototype.getJson = function(url, urlParams, statusHandlers) {
    var isAsync = this.isAsync;
    this.isAsync = false;
    this.request('GET', url, urlParams, statusHandlers);
    this.isAsync = isAsync;

    if (this.xhr.status!='200' || !this.xhr.responseText.length) {
        return null;
    }
    try {
        return eval(this.xhr.responseText.replace("while(1);", ""));
    }
    catch (e) {
        return null;
    }
}

httpRequest.prototype.getResponseText = function() {
    return this.xhr.responseText;
}

// ----------------------------------------------------------------------------
// string to use with POST requests (recursive!)
function urlEncodeArray(data, parent)
{
    if (data==null) return '';

    if (!parent) parent = "";
    var query = [];

    if (data instanceof Object) {
        for (var k in data) {
            var key = parent ? parent+"["+k+"]" : k;

            query.push( data[k] instanceof Object
                ? urlEncodeArray(data[k], key)
                : encodeURIComponent(key) + "=" + encodeURIComponent(data[k]));
        }
        return query.join('&');
    }
    else {
        return encodeURIComponent(data);
    }
}

// Скроллер: прокручивает экран при приближении курсора мыши к краю экрана
var scroller = {
    scrollInterval: null, // для хранения ID интервала (setInterval)
    scrollDelay: 15,
    scrollAmount: 5,
    scrollAreaHeight: 60,
    scrollBottomK: 150, // ??? неправильно определяет body.scrollHeight?

    scroll: function(e) {
        if (!e) e = event;

        // высота окна
        var windowHeight = document.body.clientHeight;
        // место положения мыши
        var mouseY = e.clientY ? e.clientY : e.y;

        if (mouseY < scroller.scrollAreaHeight && scroller.canScrollUp()) {
            if (!scroller.scrollInterval) {
                scroller.scrollInterval = setInterval(scroller.scrollUp, scroller.scrollDelay);
            }
        }
        else if (mouseY > (windowHeight - scroller.scrollAreaHeight) && scroller.canScrollDown()) {
            if (!scroller.scrollInterval) {
                scroller.scrollInterval = setInterval(scroller.scrollDown, scroller.scrollDelay);
            }
        }
        else {
            scroller.scrollStop();
        }
    },

    canScrollUp: function() {
        return (document.body.scrollTop > 0);
    },

    canScrollDown: function() {
        return ((document.body.scrollHeight) > (document.body.scrollTop + document.body.clientHeight));
    },

    scrollUp: function() {
        if (scroller.canScrollUp()) {
            document.body.scrollTop -= scroller.scrollAmount;
        }
        else {
            scroller.scrollStop();
        }
    },

    scrollDown: function() {
        if (scroller.canScrollDown()) {
            document.body.scrollTop += scroller.scrollAmount;
        }
        else {
            scroller.scrollStop();
        }
    },

    scrollStop: function() {
        if (scroller.scrollInterval) {
            clearInterval(scroller.scrollInterval);
            scroller.scrollInterval = null;
        }
    }
}


/**
  * Add new parameter for module settings
  */
function ModulesAddNewParam () {
    var oIframe = top.frames['mainViewIframe'];

    var docum = (oIframe.contentWindow || oIframe.contentDocument || oIframe.document);
    if (docum.document) docum = docum.document;

    var tbody = docum.getElementById('tableParam').getElementsByTagName('TBODY')[0];
    var row = docum.createElement("TR");
    var tdName = docum.createElement("TD");
    var tdValue = docum.createElement("TD");
    var tdDelete = docum.createElement("TD");

    tdName.style.background = "#FFF";
    tdValue.style.background = "#FFF";
    tdDelete.style.background = "#FFF";

    var dat = new Date();
    var id = dat.getMinutes() + '' + dat.getSeconds() + '' + Math.floor(Math.random()*51174);

    tbody.appendChild(row);
    row.appendChild(tdName);
    row.appendChild(tdValue);
    row.appendChild(tdDelete);

    tdName.innerHTML  = '<textarea rows="1" style = "width:100%" name="Name_' + id + '"></textarea>';
    tdValue.innerHTML = '<textarea rows="1" style = "width:100%" name="Value_' + id + '"></textarea>';
    tdDelete.align = 'center';
    tdDelete.innerHTML = '<input type="checkbox" name="Delete_' + id + '" />';
    return;
}

// срабатывает при выборе объекта при пакетной обработке
function nc_message_select ( id ) {
    var frm = document.getElementById('nc_delete_selected');

    if ( !frm ) return false;

    if ( nc_message_selected[id] ) {
        nc_message_selected[id] = 0;
        frm.removeChild(document.getElementById('nc_hidden_'+id));
    }
    else {
        nc_message_selected[id] = id;
        frm.innerHTML += "<input id='nc_hidden_"+id+"'type='hidden' name='message["+id+"]' value='"+id+"' />";
    }

    return false;
}

// Пакетная обработка объектов
// Добавить скрытые поля и отправить форму при непосредственном нажатии "Удалить" или "включить\выключить"
function nc_package_click ( action ) {
    // id формы
    var frm = document.getElementById('nc_delete_selected');

    if ( !frm ) return false;

    if ( action == 'delete') { // delete
        frm.innerHTML += "<input type='hidden' name='delete' value='1' />";
    }
    else { //checked
        frm.innerHTML += "<input type='hidden' name='checked' value='1' />";
        frm.innerHTML += "<input type='hidden' name='posting' value='1' />"
    }

    frm.submit();
    return false;
}

function toggle(Obj) {
    if(Obj){
        Obj.style.display = (Obj.style.display != 'none') ? 'none':'block';
        return true;
    }
    return false;
}

function nc_toggle ( obj ) {
    var l = document.getElementById(obj);
    if ( l ) toggle(l);

}

function nc_trash_get_objects(cc, date_b, date_e, type_id) {
    if(!cc) return false;
    type_id = type_id || 0;

    var values = [];
    var res;
    var url = SUB_FOLDER + NETCAT_PATH + 'admin/trash/get_trash.php';
    // var needTextArea = document.getElementById(act);

    values["NC_HTTP_REQUEST"] = 1;
    var cc_div = document.getElementById('cc_'+cc+'_'+type_id);
    console.log(cc_div);

    if(cc_div.rel != 'updated'){
        cc_div.rel = 'updated';

        cc_div.innerHTML = "<img src='"+ICON_PATH+"trash-loader.gif' alt='' />";
        var xhr = new httpRequest();


        req = xhr.request('POST', url, {
            'cc':      cc,
            'date_b':  date_b,
            'date_e':  date_e,
            'type_id': type_id
        });

        res = xhr.getResponseText();

        // needTextArea.value = res;
        if(res){
            cc_div.innerHTML = res;
        }
    }
    else{
        toggle(cc_div);
    }

    return false;
}

/**
 * Отмечает все элементы типа checkbox в форме
 */
function nc_check_all() {
    var oIframe = top.frames['mainViewIframe'];
    var docum = (oIframe.contentWindow || oIframe.contentDocument || oIframe.document);

    if ( !docum.forms.length ) return true;
    var f = ( docum.forms.length == 1 ) ? docum.forms[0] : docum.forms['mainForm'];

    for (var i=0; i < f.length; i++) {
        var el = f.elements[i];
        if (el.tagName == "INPUT" && el.type=="checkbox") {
            el.checked = 'checked';
        }
    }

    return true;
}


nc_selectstatic = function () {
    this.nums = 0;
    this.div_id = 'select_static';
}
nc_selectstatic.prototype = {
    add: function (key, value) {
        this.nums++;
        if (key === undefined || key === null) { key = ''; }
        if (value === undefined || value === null) { value = ''; }

        if (this.nums == 1) { $nc('#select_static_head').show(); }

        var con_id = this.div_id + "_con_" + this.nums;
        $nc('#' + this.div_id).append("<div id='"+con_id+"'></div>");

        $nc('#' + con_id).append("<div class='key'><input name='select_static_key[" + this.nums + "]' type='text' value='" + key + "' /></div>");
        $nc('#' + con_id).append("<div class='value'><input name='select_static_value[" + this.nums + "]' type='text' value='" + value + "' /></div>");
        $nc('#' + con_id).append("<div class='drop' onclick='nc_s.drop(" + this.nums + ")'><div class='icons icon_delete' title='" + ncLang.Drop + "' style='margin-top:-3px'></div> " + ncLang.Drop + "</div>");
        $nc('#' + con_id).append("<div style='clear:both;'></div>");
    },

    drop: function ( id ) {
        $nc("#"+this.div_id+"_con_"+id).remove();
        this.nums--;
        if ( !this.nums  ) $nc('#select_static_head').hide();
    }
}
