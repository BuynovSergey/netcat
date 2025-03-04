/**
 * Constructor function
 */
nc_Filemanager = function(options){
    this.DOCUMENT_ROOT = options.DOCUMENT_ROOT;
    this.HTTP_HOST = options.HTTP_HOST;
    this.MODULE_PATH = options.MODULE_PATH || NETCAT_PATH + 'modules/filemanager/';
    this.url_prefix = options.url_prefix || 'admin.php?page=manager';
}

nc_Filemanager.prototype = {
    /*
     * Get panel document (parent or iframe)
     */
    get_panel_document: function(){
        var panel = document.getElementById('nc_filemanager_panel');
        return panel ? document : parent.document;
    },

    /*
     * Get element by id from panel document
     */
    get_element: function(id){
        return this.get_panel_document().getElementById(id);
    },

    /*
     * Get permission elements
     */
    get_panel_perm: function(){
        var self = this;

        return [
            self.get_element('nc_filemanager_panel_1r'),
            self.get_element('nc_filemanager_panel_1w'),
            self.get_element('nc_filemanager_panel_1x'),
            self.get_element('nc_filemanager_panel_2r'),
            self.get_element('nc_filemanager_panel_2w'),
            self.get_element('nc_filemanager_panel_2x'),
            self.get_element('nc_filemanager_panel_3r'),
            self.get_element('nc_filemanager_panel_3w'),
            self.get_element('nc_filemanager_panel_3x')
        ];
    },

    /*
     * Show filemanager panel
     */
    show_panel: function(path){
        path = decodeURIComponent(path);
        // redraw
        this.close_panel();

        // file/dir path
        var path_arr = path.split('/');

        nc_Filemanager.obj = this;

        // loader
        this.get_element('nc_filemanager_panel_loader').style.display = 'block';

        // send request
        this.ajax_request([
            { name: 'action', value: 'show' },
            { name: 'path', value: path }
        ]);

        // file/dir name
        var name = path_arr[path_arr.length - 1];
        // show panel
        //this.panel.style.display = 'block';


        // center panel
        this.center_panel();
        // show close button
        this.get_element('nc_filemanager_panel_close').disabled = false;
        // panel data
        this.get_element('nc_filemanager_panel_path').value = path;
        this.get_element('nc_filemanager_panel_name').value = name;

        this.show_updated_panel();

    },

    /*
     * Show copy link panel
     */
    show_link_panel: function(path, is_dir){
        path = decodeURIComponent(path);
        var d = document;
        var w = window.parent ? window.parent : window;
        var endslash = is_dir ? '/' : '';

        $nc('#nc_filemanager_link_absolute').val('/' + path + endslash)
        $nc('#nc_filemanager_link_global').val('http://' + this.HTTP_HOST + '/' + path + endslash)
        $nc('#nc_filemanager_link_server').val(this.DOCUMENT_ROOT + '/' + path + endslash)

        w.$nc('#nc_filemanager_link_panel', d).modal({
            appendTo: $nc(window.parent.document).find('body'),
            closeHTML: "",
            containerId: 'fm_simplemodal_container',
            onClose: function(modal){
                var orig = modal.orig;
                parent.$nc.modal.close();
                $nc('body', d).append( orig );
            },
            onShow: function(modal){
                var $modal_body = w.$nc('#nc_filemanager_link_panel_body');
                w.$nc('#fm_simplemodal_container').addClass('nc-shadow-large').css({width:500, height:$modal_body.height()});
                w.$nc(w).resize();
            }
        });

    },

    show_updated_panel: function(){
        var d = document;
        var w = window.parent ? window.parent : window;
        w.$nc('#nc_filemanager_panel', d).modal({
            appendTo: $nc(window.parent.document).find('body'),
            closeHTML: "",
            containerId: 'fm_simplemodal_container',
            onClose: function(modal){
                var orig = modal.orig;
                parent.$nc.modal.close();
                $nc('body', d).append( orig );
            },
            onShow: function(modal){
                var $modal_body = $nc('#nc_filemanager_panel_body');
                w.$nc('#fm_simplemodal_container').addClass('nc-shadow-large').css({width:300, height:$modal_body.height()});
                w.$nc(w).resize();
            }
        });
    },

    /*
     * Close filemanager panel
     */
    close_panel: function(){
        // stop ajax request
        if (this.xhr) this.xhr.abort();

        // close panel
        this.get_element('nc_filemanager_panel').style.display = 'none';

        // clear permissions
        var panel_perm = this.get_panel_perm();
        for (i = 0; i < panel_perm.length; i++) {
            panel_perm[i].checked = false;
        }
        // clear name
        this.get_element('nc_filemanager_panel_name').value = "";

        // disable panel elements
        this.disable_panel();
        (window.parent ? window.parent : window).$nc.modal.close();
    },

    /*
     * Save filemanager panel data
     */
    save_panel: function(){
        // check parameters
        if (!(this.get_element('nc_filemanager_panel_path').value && this.get_element('nc_filemanager_panel_name').value)) return false;

        var perm_arr = new Array;

        // permissions
        var panel_perm = this.get_panel_perm();
        for (i = 0; i < panel_perm.length; i++) {
            perm_arr[i] = (panel_perm[i].checked ? 1 : 0);
        }

        // send request
        this.ajax_request([
            { name: 'action', value: 'save' },
            { name: 'path', value: this.get_element('nc_filemanager_panel_path').value },
            { name: 'rename', value: this.get_element('nc_filemanager_panel_name').value },
            { name: 'permissions', value: perm_arr.join(',') },
            { name: 'nc_token', value: this.get_element('nc_token').value }
        ]);

        // disable panel elements
        this.disable_panel();
        (window.parent ? window.parent : window).$nc.modal.close();
    },

    /*
     * Center panel
     */
    center_panel: function(){
        // variables
        var x = 0;
        var y = 0;
        var page_margin_x = 0;
        var page_margin_y = 0;

        // determine coordinates
        if (document.getElementById && !document.all) {
            x = parseInt(window.innerWidth / 2) - parseInt(this.get_element('nc_filemanager_panel').offsetWidth / 2);
            y = parseInt(window.innerHeight / 2) - parseInt(this.get_element('nc_filemanager_panel').offsetHeight / 2);
            // page margin
            //page_margin_x = parseInt(window.pageXOffset);
            //page_margin_y = parseInt(window.pageYOffset);
        }
        else {
            x = parseInt(document.body.clientWidth / 2) - parseInt(this.get_element('nc_filemanager_panel').offsetWidth / 2);
            //y = parseInt(document.body.clientHeight / 2) - parseInt(this.get_element('nc_filemanager_panel').offsetHeight / 2);
            // page margin
            page_margin_x = parseInt(document.body.scrollLeft);
            //page_margin_y = parseInt(document.body.scrollTop);
        }

        // center panel
        if (!document.all) this.get_element('nc_filemanager_panel').style.top = (y + page_margin_y) + 'px'; // for IE CSS expression used
        this.get_element('nc_filemanager_panel').style.left = (x + page_margin_x) + 'px';
    },

    /*
     * Disable filemanager panel elements
     */
    disable_panel: function(){
        var panel_perm = this.get_panel_perm();
        for (i = 0; i < panel_perm.length; i++) {
            panel_perm[i].disabled = true;
        }

        this.get_element('nc_filemanager_panel_loader').style.display = 'block';
        this.get_element('nc_filemanager_panel_name').disabled = true;
        this.get_element('nc_filemanager_panel_close').disabled = true;
        this.get_element('nc_filemanager_panel_save').disabled = true;
    },

    /*
     * Update filemanager panel
     */
    update_panel: function(options){
        var panel_perm = nc_Filemanager.obj.get_panel_perm();

        for (i = 0; i < options.permissions.length; i++) {
            panel_perm[i].checked = options.permissions[i];
            panel_perm[i].disabled = false;
        }

        nc_Filemanager.obj.get_element('nc_filemanager_panel_name').disabled = false;
        nc_Filemanager.obj.get_element('nc_filemanager_panel_close').disabled = false;
        nc_Filemanager.obj.get_element('nc_filemanager_panel_save').disabled = false;
        nc_Filemanager.obj.get_element('nc_filemanager_panel_loader').style.display = 'none';

    },

    /*
     * Update filemanager element
     */
    update_manager: function(options){
        //
        var old_path = options.path;
        // file/dir path
        var old_path_arr = old_path.split('/');
        // file/dir name
        var old_name = old_path_arr[old_path_arr.length - 1];
        var old_name_escaped = encodeURIComponent(old_name);
        // get element ids
        var element = document.getElementById('nc_fm_' + old_name_escaped);
        var element_permissions = document.getElementById('nc_fm_' + old_name_escaped + '_perm');
        var element_settings = document.getElementById('nc_fm_' + old_name_escaped + '_settings');
        var element_edit = document.getElementById('nc_fm_' + old_name_escaped + '_edit');
        var element_download = document.getElementById('nc_fm_' + old_name_escaped + '_download');
        var element_delete = document.getElementById('nc_fm_' + old_name_escaped + '_delete');
        var element_copy_link = document.getElementById('nc_fm_' + old_name_escaped + '_copy_link');

        element.className = (options.readable == 1 ? "link" : "");

        if (options.mode) {

            if (element_permissions) {
                // replace HTML code
                element_permissions.innerHTML = options.mode;
            }

            // change element link
            element.onclick = options.readable == 1 ? function(event){
                document.location.href = nc_Filemanager.obj.url_prefix + (options.dir ? "&dir=" : "&phase=2&file=") + old_path;
            } : "return false";
            if (element_edit) element_edit.onclick = options.writable == 1 ? function(event){
                document.location.href = nc_Filemanager.obj.url_prefix + "&phase=3&file=" + old_path;
            } : "return false";
            if (element_download) {
                element_download.onclick = "return " + (options.readable == 1 ? "true" : "false");
                //element_download.target = options.readable==1 ? "_blank" : "";
                element_download.href = options.readable == 1 ? nc_Filemanager.obj.url_prefix + "&phase=5&file=" + old_path : "#";
            }
        }

        // read/edit links
        if (element_download) element_download.style.display = options.readable == 1 ? "block" : "none";
        if (element_edit) element_edit.style.display = options.writable == 1 ? "block" : "none";

        if (options.renamed) {
            // rename parameters
            var new_path = options.renamed;

            // file/dir path
            var new_path_arr = new_path.split('/');

            // file/dir name
            var new_name = new_path_arr[new_path_arr.length - 1];
            var new_name_escaped = encodeURIComponent(new_name);

            // update elements ids
            element.id = 'nc_fm_' + new_name_escaped;
            if (element_permissions) element_permissions.id = 'nc_fm_' + new_name_escaped + '_perm';
            if (element_download) element_download.id = 'nc_fm_' + new_name_escaped + '_preview';
            if (element_edit) element_edit.id = 'nc_fm_' + new_name_escaped + '_edit';
            if (element_settings) element_settings.id = 'nc_fm_' + new_name_escaped + '_settings';
            if (element_delete) {
                element_delete.id = 'nc_fm_' + new_name_escaped + '_delete';
            }
            if (element_copy_link) {
                element_copy_link.id = 'nc_fm_' + new_name_escaped + '_copy_link';
            }

            // update element
            element.innerHTML = new_name;
            element.onclick = options.readable == 1 ? function(event){
                document.location.href = nc_Filemanager.obj.url_prefix + (options.dir ? "&dir=" : "&phase=2&file=") + new_path;
            } : "return false";

            if (element_permissions) {
                jQuery(element_permissions).unbind().attr('onclick', '');
                jQuery(element_permissions).on('click', function(){
                    nc_filemanagerObj.show_panel(encodeURIComponent(new_path));
                });
            }
            if (element_edit) element_edit.onclick = options.writable == 1 ? function(event){
                event.preventDefault();
                document.location.href = nc_Filemanager.obj.url_prefix + "&phase=3&file=" + new_path;
            } : "return false";

            if (element_delete) {
                jQuery(element_delete).unbind().attr('onclick', '');
                jQuery(element_delete).on('click', function(event){
                    event.preventDefault();
                    document.location.href = nc_Filemanager.obj.url_prefix + "&phase=4&path=" + new_path;
                });
            }

            if (element_download) {
                element_download.onclick = "return " + (options.readable == 1 ? "true" : "false");
                //element_download.target = options.readable==1 ? "_blank" : "";
                element_download.href = options.readable == 1 ? nc_Filemanager.obj.url_prefix + "&phase=5&file=" + new_path : "#";
            }

            if (element_settings) {
                jQuery(element_settings).unbind().attr('onclick', '');
                jQuery(element_settings).on('click', function(){
                    nc_Filemanager.obj.show_panel(encodeURIComponent(new_path));
                });
            }

            if (element_copy_link) {
                jQuery(element_copy_link).unbind().attr('onclick', '');
                jQuery(element_copy_link).on('click', function(){
                    nc_Filemanager.obj.show_link_panel(encodeURIComponent(new_path), options.dir);
                });
            }
        }
    },

    bin_to_dec: function(binary){
        // only 1 or 0
        binary = (binary + '').replace(/[^01]/gi, '');
        // convert
        return parseInt(binary_string, 2);
    },

    /*
     * Create ajax object
     */
    ajax_obj: function(){

        this.xhr = null;

        // Standart method
        try {
            this.xhr = new XMLHttpRequest();
        }
        catch (e) {
            // Mozilla, IE7
            try {
                this.xhr = new ActiveXObject("Msxml2.XMLHTTP");
            }
            catch (e) {
                // Old IE
                try {
                    this.xhr = new ActiveXObject("Microsoft.XMLHTTP");
                }
                catch (e) {
                    return false;
                }
            }
        }

        return true;
    },

    ajax_request: function(params){
        // variables
        var toSendArr = new Array;
        // make array
        for (var i = 0; i < params.length; i++) {
            if (params[i].name && params[i].value) {
                toSendArr[i] = params[i].name + '=' + encodeURIComponent(params[i].value);
            }
        }
        // get ajax object
        this.ajax_obj();
        // action script
        this.xhr.open('POST', SUB_FOLDER + this.MODULE_PATH + 'request.php', true);
        this.xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
        // set callback
        this.xhr.onreadystatechange = this.ajax_get_status;
        // send request
        this.xhr.send(toSendArr.join('&'));
    },

    /*
     * Ajax callback function
     */
    ajax_get_status: function(){

        var ready = nc_Filemanager.obj.xhr.readyState;
        var responseJson = "";
        var status = 0;

        // no initialized, open() not executed
        if (ready == 0) {
            return 0;
        }
        // in progress, open() executed
        if (ready == 1) {
            return 1;
        }
        // in progress, send() executed
        if (ready == 2) {
            return 2;
        }
        // interacive, part of data geted from server
        if (ready == 3) {
            return 3;
        }
        // operation completed
        if (ready == 4) {
            status = nc_Filemanager.obj.xhr.status;
            if (status >= 200 && status < 300) {
                // response text from PHP file
                var responseJson = nc_Filemanager.obj.xhr.responseText;
                // return if no result
                if (!responseJson) return;

                var updData = eval('(' + responseJson + ')');

                if (!updData.error) {
                    if (updData.action == "show") nc_Filemanager.obj.update_panel(updData);
                    if (updData.action == "save") {
                        nc_Filemanager.obj.update_manager(updData);
                        nc_Filemanager.obj.close_panel();
                    }
                }
                else {
                    nc_Filemanager.obj.close_panel();
                    alert(updData.error);
                }
            }
        }
    }

}