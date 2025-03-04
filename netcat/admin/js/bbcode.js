/**
 * Вставка текста в textarea
 * @param object oWindow экземпляр класса окна, в котором располженна форма с textarea
 * @param string formId идентификатор формы
 * @param string textareaId идентификатор textarea
 * @param string b_c1 первая часть вставляемого текста
 * @param string b_c2 вторая часть вставляемого текста
 * 
 */
function insert_bbcode(oWindow, formId, textareaId, b_c1, b_c2) {
	var d = eval(oWindow + '.document.forms[\'' + formId + '\'].' + textareaId);
	if (d.setSelectionRange) {
	// Mozilla
		var TextBegin = (d.value).substring(0, d.selectionStart);
		var TextSelected = (d.value).substring(d.selectionStart, d.selectionEnd);
		var TextEnd = (d.value).substring(d.selectionEnd, d.textLength);
		d.value = TextBegin + b_c1 + TextSelected + b_c2 + TextEnd;
		d.focus();
		d.setSelectionRange(TextBegin.length + b_c1.length, TextBegin.length + b_c1.length + TextSelected.length);
	} else if (document.selection ) {
	// Internet Explorer
		// выделенный фрагмент
		d.focus();
		var TextSelected = document.selection.createRange();
		if (TextSelected.text.length > 0) {
			TextSelected.text = b_c1 + TextSelected.text + b_c2;
		} else {
			// если не выделено но нужно поставить именно сюда
			if (d.createTextRange && d.caretPos) {
				var caretPos = d.caretPos;
				caretPos.text = caretPos.text + b_c1 + b_c2;
			} else {
				d.value  += b_c1 + b_c2;
			}
		}
		d.focus();	
	}
	else {
		d.value = d.value + b_c1 + b_c2;
		d.focus();
	}
	makePos(d);
}

function makePos(TextArea) {
	if (TextArea.createTextRange) TextArea.caretPos = document.selection.createRange().duplicate();
}

function getPosition(obj) {
 var x = 0, y = 0, currentObj = obj;
 while(currentObj) {
    x+= currentObj.offsetLeft;
    y+= currentObj.offsetTop;
    currentObj = currentObj.offsetParent;
 }
 return {x:x, y:y};
}

// через jqery, т.к. offset глючит при родительском absolute/relative
function getPositionJQ (obj) {

    var coords = $("#"+obj).position();

    return {x:Math.round(coords.left), y:Math.round(coords.top)};
}

function show_color_buttons(text_area_ID) {
//отображение элемента

var divID=document.getElementById("color_buttons_" + text_area_ID);
if(divID.style.display=="block") divID.style.display="none";
else divID.style.display="block";
//вывод под кнопкой
var ffcb=document.getElementById("nc_bbcode_color_button_" + text_area_ID);
    var pos = getPositionJQ("nc_bbcode_color_button_" + text_area_ID);

    divID.style.left = pos.x + 'px';
    divID.style.top = (pos.y + ffcb.offsetHeight + 2) + 'px';
}

// показ окна выбора названия и url ссылки
function show_url_window (text_area_ID){

var divID=document.getElementById("url_link_" + text_area_ID);
if(divID.style.display=="block") divID.style.display="none";
else divID.style.display="block";
    var ffcb=document.getElementById("nc_bbcode_url_button_" + text_area_ID);
    var pos = getPositionJQ("nc_bbcode_url_button_" + text_area_ID);

    divID.style.left = pos.x + 'px';
    divID.style.top = (pos.y + ffcb.offsetHeight + 2) + 'px';
}

function insert_url_bbcode (win_ID, form_ID, text_area_ID){
    var url_input = document.getElementById("bbcode_url_" + text_area_ID);
    var desc_input = document.getElementById("bbcode_urldesc_" + text_area_ID);
    var url = url_input.value;
    var text = desc_input.value;

    if (url == '') {
        $("#bbcode_url_" + text_area_ID).addClass('wrong');
        return false;
    }

    if (url.substring(0,7) != 'http://' && url.substring(0,8) != 'https://') url = 'http://' + url;

    if (text == '') text = url.substring(7);

    insert_bbcode(win_ID, form_ID, text_area_ID,'[URL=\''+url+'\']'+text+'[/URL]','');

    // закрываем окно
    var divID=document.getElementById("url_link_" + text_area_ID);
    if(divID.style.display=="block") divID.style.display="none";
}
function insert_img_bbcode (win_ID, form_ID, text_area_ID){
    var url_input = document.getElementById("bbcode_img_" + text_area_ID);
    var url = url_input.value;

    if (url == '') {
        $("#bbcode_img_" + text_area_ID).addClass('wrong');
        return false;
    }
    insert_bbcode(win_ID, form_ID, text_area_ID,'[IMG=\''+url+'\']','');

    // закрываем окно
    var divID=document.getElementById("img_" + text_area_ID);
    if(divID.style.display=="block") divID.style.display="none";
}

// показ окна выбора url изображения
function show_img_window (text_area_ID){

    var divID=document.getElementById("img_" + text_area_ID);
    if(divID.style.display=="block") divID.style.display="none";
    else divID.style.display="block";
    var ffcb=document.getElementById("nc_bbcode_img_button_" + text_area_ID);
    var pos = getPositionJQ("nc_bbcode_img_button_" + text_area_ID);

    divID.style.left = pos.x + 'px';
    divID.style.top = (pos.y + ffcb.offsetHeight + 2) + 'px';
}




function show_smile_buttons(text_area_ID) {
//отображение элемента
var divID=document.getElementById("smile_buttons_" + text_area_ID);
if(divID.style.display=="block") divID.style.display="none";
else divID.style.display="block";
//вывод под кнопкой
var ffcb=document.getElementById("nc_bbcode_smile_button_" + text_area_ID);
    var pos = getPositionJQ("nc_bbcode_smile_button_" + text_area_ID);

divID.style.left = pos.x + 'px';
divID.style.top = (pos.y + ffcb.offsetHeight + 2) + 'px';
}

function show_bbcode_tips(oWindow, formId, text_area_ID, tips) {
	var d = eval(oWindow + '.document.forms[\'' + formId + '\'].' + 'bbcode_helpbox_' + text_area_ID);
	d.value = tips;
}

function show_tips(tipz){
	document.forum_message_form.helpbox.value = eval(tipz + "_help");
}

function getArgs() {

    var args = new Object(  );
    var query = location.search.substring(1);
    var pairs = query.split("&");

    for(var i = 0; i < pairs.length; i++) {
        var pos = pairs[i].indexOf('=');          
        if (pos == -1) continue;                  
        var argname = pairs[i].substring(0,pos);  
        var value = pairs[i].substring(pos+1);    
        args[argname] = unescape(value);         

        // In JavaScript 1.5, use decodeURIComponent(  ) instead of escape(  )
    }
    return args;                                 
}

function form_check_submit(errortext) {
	var check=1;
	var args = getArgs(); 
	x=args.Topic_ID;
	y=args.Repl_ID;

	// Subject check
	if ((!x && !y) && !document.forum_message_form.forum_i_subject.value) 
		check=0;

	// Message check
	if (!document.forum_message_form.forum_i_message.value) 
		check=0;
	
	if (check) 
		document.forum_message_form.submit();
	else 
		alert(errortext);
}