
var start_date;
var end_date;
var period;
var div_width;
var counter_id;

var orig_color;


function load_report() {
    var date = new Date();
    var time_offset = date.getTimezoneOffset() * 60;
    jQuery('#CounterReports').html("<div style='position: absolute; left: 40%; top: 50%;'><img src='"+ICON_PATH+"trash-loader.gif' alt='' /></div");
    jQuery.ajax({
        url: NETCAT_PATH + '/modules/stats/openstat/ajax_reports.php?counter_id='+counter_id+
        "&from="+start_date+"&to="+end_date+"&period="+period+"&time_offset="+time_offset+
        "&width="+div_width,
        success: function (data) {
            jQuery('#CounterReports').html(data);
        }
    });
}


function report_last_month() {
    jQuery('#YesterdayLink').css("border-bottom", "2px dotted");
    jQuery('#YesterdayLink').css("color", orig_color);
    jQuery('#YesterdayLink').css("font-size", "1em");
    jQuery('#LastWeekLink').css("border-bottom", "2px dotted");
    jQuery('#LastWeekLink').css("color", orig_color);
    jQuery('#LastWeekLink').css("font-size", "1em");
    jQuery('#LastMonthLink').css("border", "0");
    jQuery('#LastMonthLink').css("color", "black");
    jQuery('#LastMonthLink').css("font-size", "1.4em");

    period = 'day';

    date = new Date(Date.now()-24*60*60*1000);
    end_date = Math.round(date.getTime()/1000);
    date.setMonth((date.getMonth() > 0 ? date.getMonth()-1 : 11), date.getDate());
    start_date = Math.round(date.getTime()/1000);

    load_report();
}

function report_last_week() {
    jQuery('#YesterdayLink').css("border-bottom", "2px dotted");
    jQuery('#YesterdayLink').css("color", orig_color);
    jQuery('#YesterdayLink').css("font-size", "1em");
    jQuery('#LastWeekLink').css("border", "0");
    jQuery('#LastWeekLink').css("color", "black");
    jQuery('#LastWeekLink').css("font-size", "1.4em");
    jQuery('#LastMonthLink').css("border-bottom", "2px dotted");
    jQuery('#LastMonthLink').css("color", orig_color);
    jQuery('#LastMonthLink').css("font-size", "1em");

    period = 'day';

    end_date = Math.round(Date.now()/1000)-24*60*60;
    start_date = end_date - 7*24*60*60;

    load_report();
}


function report_yesterday() {
    jQuery('#YesterdayLink').css("border", "0");
    jQuery('#YesterdayLink').css("color", "black");
    jQuery('#YesterdayLink').css("font-size", "1.4em");
    jQuery('#LastWeekLink').css("border-bottom", "2px dotted");
    jQuery('#LastWeekLink').css("color", orig_color);
    jQuery('#LastWeekLink').css("font-size", "1em");
    jQuery('#LastMonthLink').css("border-bottom", "2px dotted");
    jQuery('#LastMonthLink').css("color", orig_color);
    jQuery('#LastMonthLink').css("font-size", "1em");

    period = 'hour';

    date = new Date(Date.now()-24*60*60*1000);
    date.setHours(23);
    end_date = Math.round(date.getTime()/1000);
    date.setHours(0);
    start_date = Math.round(date.getTime()/1000);

    load_report();
}


// выкинуть!

function various_dates() {

    period = jQuery('#StatsPeriodList').val();

    if (!(
        (parseInt(jQuery('#YearFrom').val(), 10) > 2000) &&
        (parseInt(jQuery('#YearFrom').val(), 10) < 2100) &&
        (parseInt(jQuery('#MonthFrom').val(), 10) > 0) &&
        (parseInt(jQuery('#MonthFrom').val(), 10) < 13) &&
        (parseInt(jQuery('#DayFrom').val(), 10) > 0) &&
        (parseInt(jQuery('#DayFrom').val(), 10) < 32) &&
        (parseInt(jQuery('#HourFrom').val(), 10) >= 0) &&
        (parseInt(jQuery('#HourFrom').val(), 10) < 24)
        )) {
        alert (INVALID_START_DATE);
        return;
    }

    if (! (
        (parseInt(jQuery('#YearTill').val(), 10) > 2000) &&
        (parseInt(jQuery('#YearTill').val(), 10) < 2100) &&
        (parseInt(jQuery('#MonthTill').val(), 10) > 0) &&
        (parseInt(jQuery('#MonthTill').val(), 10) < 13) &&
        (parseInt(jQuery('#DayTill').val(), 10) > 0) &&
        (parseInt(jQuery('#DayTill').val(), 10) < 32) &&
        (parseInt(jQuery('#HourTill').val(), 10) >= 0) &&
        (parseInt(jQuery('#HourTill').val(), 10) < 24)
        )) {
        alert (INVALID_END_DATE);
        return;
    }
  
    start_date = Date.UTC(jQuery('#YearFrom').val(), jQuery('#MonthFrom').val()-1, jQuery('#DayFrom').val());
    if (start_date == NaN) {
        alert (INVALID_START_DATE);
        return;
    }

    end_date = Date.UTC(jQuery('#YearTill').val(), jQuery('#MonthTill').val()-1, jQuery('#DayTill').val());
    if (start_date == NaN) {
        alert (INVALID_END_DATE);
        return;
    }

    start_date = Math.round(start_date / 1000);
    end_date = Math.round(end_date / 1000);

    if (end_date - start_date <= 0) {
        alert (START_DATE_TOO_BIG);
        return;
    }

    var divider;
    if (period == 'hour') divider = 60*60;
    else if (period == 'month') divider = 60*60*24*30;
    else if (period == 'week') divider = 60*60*24*7;
    else divider = 60*60*24;
    if ((end_date - start_date) / divider > 1000) {
        alert (INVALID_PERIOD);
        return;
    }
}
// выкинуть!
function cange_params() {
    period = jQuery('#StatsPeriodList').val();
    switch (period) {
        case 'hour' :
            jQuery('#TimeFrom').css('display', 'inline');
            jQuery('#TimeTill').css('display', 'inline');
            jQuery('#DayFrom').css('display', 'inline');
            jQuery('#DayTill').css('display', 'inline');
            break;
        case 'day' :
        case 'week' :
            jQuery('#TimeFrom').css('display', 'none');
            jQuery('#TimeTill').css('display', 'none');
            jQuery('#DayFrom').css('display', 'inline');
            jQuery('#DayTill').css('display', 'inline');
            break;
        case 'month' :
            jQuery('#TimeFrom').css('display', 'none');
            jQuery('#TimeTill').css('display', 'none');
            jQuery('#DayFrom').css('display', 'none');
            jQuery('#DayTill').css('display', 'none');
            break;

    }
}



jQuery('document').ready(function() {
    jQuery('#CountersList').change(function () {
        document.location.href = '?phase=' + jQuery('#CountersList').val();
    });
  
    counter_id = jQuery('#CountersList').val();
    div_width = jQuery('#CounterReports').width()-20;
    orig_color = jQuery('#YesterdayLink').css("color");
    jQuery('#YesterdayLink').click(report_yesterday);
    jQuery('#LastWeekLink').click(report_last_week);
    jQuery('#LastMonthLink').click(report_last_month);
  
    report_yesterday();


    jQuery('#StatsPeriodList').change(cange_params);
    jQuery('#ShowStat').click(various_dates);

});
