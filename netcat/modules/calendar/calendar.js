
var path = NETCAT_PATH;
if(path.replace(/[-\/a-z0-9_]+/, "")!="") path = "/netcat/";
var phpURL = path + "modules/calendar/count.php";
var xmlHttp = false;
var waitTimeout;

function nc_calendar_generate(day, month, year, cc_ignore, admin_mode ) {
    if(!cc_ignore) {
        cc_ignore = false;
    }
    if (!admin_mode) {
        admin_mode = 0;
    }
    if(!document.getElementById("nc_calendar_block")) return false;

    if (document.getElementById("ImgWaiting")) {
        document.getElementById("ImgWaiting").style.display = "block";
        waitTimeout = setTimeout("", 400);
    }
	
    var calendar_cc = document.getElementById("calendar_cc").value;
    var calendar_theme = Math.floor(document.getElementById("calendar_theme").value);
    var calendar_field = document.getElementById("calendar_field").value;
    var calendar_filled = Math.floor(document.getElementById("calendar_filled").value);
    var calendar_querydate = document.getElementById("calendar_querydate").value;

    var xhr = new httpRequest(0);
	
    requestStatus = xhr.request('GET', phpURL, {
        'day':day,
        'month':month,
        'year':year,
        'theme':calendar_theme,
        'needcc':calendar_cc,
        'datefield':calendar_field,
        'filled':calendar_filled,
        'querydate':calendar_querydate,
        'calendar':'1',
	'cc_ignore':cc_ignore,
        'admin_mode':admin_mode
    });
	
    if (requestStatus!='200') {
        return null;
    }

    try {
        response = xhr.getResponseText();
    }
    catch (e) {
        return null;
    }
	
    if(document.getElementById("nc_calendar_block") && response) {
        document.getElementById("nc_calendar_block").innerHTML = response;
    }
	
}


function nc_calendar_generate_popup(day, month, year, field_day, field_month, field_year, theme ) {
    var div_element = document.getElementById("nc_calendar_popup_" + field_day);
    if ( !div_element ) return false;
    if( !theme ) theme = 0;

    var xhr = new httpRequest(0);

    requestStatus = xhr.request('GET', phpURL, {
        'day':day,
        'month':month,
        'year':year,
        'theme':theme,
        'needcc':0,
        'field_day' : field_day,
        'field_month' : field_month,
        'field_year' : field_year,
        'filled':1,
        'calendar':'1',
        'popup':'1',
        'theme' : theme
    });

    if (requestStatus!='200') {
        return null;
    }

    try {
        response = xhr.getResponseText();
    }
    catch (e) {
        return null;
    }

    if( response) {
        div_element.innerHTML = response;
    }

    return true;

}


function nc_calendar_popup ( field_day, field_month, field_year, theme ) {
    var d = 0, m = 0, y = 0;
    var div_element = document.getElementById("nc_calendar_popup_" + field_day);
    if ( !div_element ) return false;

    if( !theme ) theme = 0;

    if ( div_element.style.display == 'block' ) {
        div_element.style.display = 'none';
        return true;
    }


    if (document.getElementById(field_day) ) {
        d = document.getElementById(field_day).value;
    }
    else if ( document.getElementsByName(field_day) ) {
        d = document.getElementsByName(field_day).item(0).value;
    }

    if (document.getElementById(field_month) ) {
        m = document.getElementById(field_month).value;
    }
    else if ( document.getElementsByName(field_month) ) {
        m = document.getElementsByName(field_month).item(0).value;
    }

    if (document.getElementById(field_year) ) {
        y = document.getElementById(field_year).value;
    }
    else if ( document.getElementsByName(field_year) ) {
        y = document.getElementsByName(field_year).item(0).value;
    }

    var xhr = new httpRequest(0);
    requestStatus = xhr.request('GET',  path + "modules/calendar/popup.php",
    {
        'id' : 1,
        'field_day' : field_day,
        'field_month' : field_month,
        'field_year' : field_year,
        'day' : d,
        'month' : m,
        'year' : y,
        'theme' : theme
    });

    if (requestStatus != '200') return false;

    try {
        response = xhr.getResponseText();
    }
    catch (e) {
        return null;
    }
  
    var el = document.getElementById("nc_calendar_popup_img_" + field_day);
    var pos =  getOffset(el);


    div_element.style.display = 'block';
    div_element.style.position = 'absolute';
    //div_element.style.top = (pos.top + el.clientHeight) + 'px';
    //div_element.style.left = pos.left + 'px';
	jQuery(div_element).offset({
		top:jQuery(el).offset().top + jQuery(el).height() + 2,
		left:jQuery(el).offset().left
	});
    div_element.innerHTML = response;

    return false;
}


function nc_calendar_popup_callback ( day, month, year, field_day, field_month, field_year ) {
    document.getElementById("nc_calendar_popup_" + field_day).style.display = 'none';

    if ( day < 10 ) day = '0' + day;
    if ( month < 10 ) month = '0' + month;

    if (document.getElementById(field_day) ) {
        document.getElementById(field_day).value = day;
    }
    else if ( document.getElementsByName(field_day) ) {
        document.getElementsByName(field_day).item(0).value = day;
    }

    if (document.getElementById(field_month) ) {
        document.getElementById(field_month).value = month;
    }
    else if ( document.getElementsByName(field_month) ) {
        document.getElementsByName(field_month).item(0).value = month;
    }
  
    if (document.getElementById(field_year) ) {
        document.getElementById(field_year).value = year;
    }
    else if ( document.getElementsByName(field_year) ) {
        document.getElementsByName(field_year).item(0).value = year;
    }

    return false;
}