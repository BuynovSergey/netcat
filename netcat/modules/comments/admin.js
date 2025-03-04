nc_filter = function () {
    this.some_const = 0;
    this.site = 0;
    this.sub = 0;
    this.cc = 0;

    this.site_list = document.getElementById('site_list');
    this.sub_list = document.getElementById('sub_list');
    this.cc_list = document.getElementById('cc_list');
    this.message_input = document.getElementById('input_message');
    this.message_button = document.getElementById('select_message');
    this.wholesub_cbx = document.getElementById('wholesub');
    instance = this;
};

nc_filter.prototype = {

    change_site : function  () {
        this.site = this.site_list.options[this.site_list.selectedIndex].value;

        this.sub_list.options.length = 0;
        this.cc_list.options.length = 0;
        this.message_input.value = '';
        this.message_input.disabled = true;
        this.message_button.disabled = true;
        this.wholesub_cbx.disabled = true;

        if (this.site > 0) {
            this.sub_list.options[0] = new Option(this.some_const['load'], 1);
            this.load_sub();
        }
        return;
    },

    change_sub : function  () {
        this.sub = this.sub_list.options[this.sub_list.selectedIndex].value;

        this.cc_list.options.length = 0;
        this.message_input.value = '';
        this.message_input.disabled = true;
        this.message_button.disabled = true;

        if (this.sub > 0) {
            this.wholesub_cbx.disabled = false;
            this.cc_list.options[0] = new Option(this.some_const['load'], 1);
            this.load_cc();
        }
        return ;
    },

    change_cc : function  () {
        this.cc = this.cc_list.options[this.cc_list.selectedIndex].value;

        this.message_input.value = '';
        this.message_input.disabled = true;
        this.message_button.disabled = true;

        if (this.cc > 0) {
            this.message_input.disabled = false;
            this.message_button.disabled = false;
        }
        return ;
    },

    select_message : function  () {
        this.cc = this.cc_list.options[this.cc_list.selectedIndex].value;

        window.open('index.php?cc='+this.cc, 'nc_popup_test', 'width=800,height=500,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes');
        return ;
    },

    select_user : function  () {
        window.open('index.php?select_user=1', 'nc_popup_test', 'width=800,height=500,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes');
        return ;
    },

    check_wholesub : function() {
        if (this.wholesub_cbx.checked) {
            this.cc_list.disabled = true;
            this.message_input.disabled = true;
            this.message_button.disabled = true;
        }
        else {
            this.cc_list.disabled = false;
            if (this.cc > 0) {
                this.message_input.disabled = false;
                this.message_button.disabled = false;
            }
        }
    },

    get_data : function (eccence, val) {
        return jQuery.ajax({
            url: "index.php",
            type: "POST",
            data: eccence+'='+val,
            async: false
        }).responseText;
    },

    load_sub : function () {
        var data = this.get_data('site', this.site);

        if ( data ) {
            this.sub_list.innerHTML = data;
        }
        else {
            this.sub_list.options.length = 0;
            this.sub_list.options[0] = new Option(this.some_const['none_sub_text'], 0);
        }
        return 0;
    },

    load_cc : function () {
        var data = this.get_data('subid', this.sub);

        if ( data ) {
            this.cc_list.innerHTML = data;

        }
        else {
            this.cc_list.options.length = 0;
            this.cc_list.options[0] = new Option(this.some_const['none_cc_text'], 0);
        }

        return 0;
    }
};