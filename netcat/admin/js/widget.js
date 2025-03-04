nc_widget = function () {
    this.widgetclass_id = 0;

    this.xhr = null;
    this.ajax();
    instance = this;
    this.change();
}

nc_widget.prototype = {

    // for AJAX
    ajax: function () {
        this.xhr = null;

        try {
            this.xhr = new XMLHttpRequest();
        }
        catch(e) {
            // Mozilla, IE7
            try {
                this.xhr = new ActiveXObject("Msxml2.XMLHTTP");
            }
            catch(e) {
                // Old IE
                try {
                    this.xhr = new ActiveXObject("Microsoft.XMLHTTP");
                }
                catch(e) {
                    return false;
                }
            }
        }

        return true;
    },

    change: function () {
        var list = document.getElementById('Widget_Class_ID');
        var widget_id = document.getElementById('widget_id').value;
        this.site = list.options[list.selectedIndex].value;
        this.xhr.open("POST", ADMIN_PATH + "widget/index.php?phase=90", true);
        this.xhr.onreadystatechange = instance.response;
        this.xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
        var send_str = 'Widget_Class_ID='+this.site+'&Widget_ID='+widget_id;
        if (document.getElementById('__old_values'))
            send_str += document.getElementById('__old_values').value + "&show_old=1";
        this.xhr.send(send_str);
        return;
    },

    response : function () {
        if (instance.xhr.readyState == 4) {
            $nc('#widget_fields').html(instance.xhr.responseText);
        }
        return;
    }

}
