nc_user_perm = function () {

    this.type = 0;  // 7-director 6-supervisor 14-developer 5-editor 12-moderator 20-ban 8-guest 30 - subscriber
    this.item = 0;  // 0 - site, 1 - sub, 2 - subclass, 3 - content
    this.dev = 0;  // 1 - classificator; 2 - ??(class) 3 -?? (template)
    this.site = 0;  // selected site
    this.sub = 0;  // selected sub

    this.time_type = 0;  // 0 - не ограничен, 1 - ограничен
    this.start_type = 0; // 0 - "сейчас", 1 - "через", 2 - "точное время"
    this.end_type = 0;   // 0 - "бессрочно", 1 - "через", 2 - "точное время"

    this.div_perm = document.getElementById('div_perm');
    this.div_main_right = document.getElementById('div_main_right');
    this.div_livetime = document.getElementById('div_livetime');
    this.div_usercontrol = document.getElementById('div_usercontrol');
    this.div_help = document.getElementById('help');

    this.xhr = null;
    this.ajax();

    this.showHelp(0);

    instance = this;

};

nc_user_perm.prototype = {

    // for AJAX
    ajax: function () {
        this.xhr = null;

        try {
            this.xhr = new XMLHttpRequest();
        } catch (e) {
            // Mozilla, IE7
            try {
                this.xhr = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                // Old IE
                try {
                    this.xhr = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {
                    return false;
                }
            }
        }

        return true;
    },

    // обновить форму со "Сроком действия"
    updateLivetime: function () {
        var div_id = document.getElementById('div_time'), i, disabled;
        this.div_livetime.style.display = this.type ? 'block' : 'none';

        //
        document.getElementById('start_now').disabled = !this.time_type;
        document.getElementById('start_across').disabled = !this.time_type;
        document.getElementById('start_define').disabled = !this.time_type;
        document.getElementById('end_now').disabled = !this.time_type;
        document.getElementById('end_across').disabled = !this.time_type;
        document.getElementById('end_define').disabled = !this.time_type;

        // включить-выключить поля для ввода начала действия
        disabled = !(this.start_type == 2) || !this.time_type;
        document.forms.admin.start_day.disabled = disabled;
        document.forms.admin.start_month.disabled = disabled;
        document.forms.admin.start_year.disabled = disabled;
        document.forms.admin.start_hour.disabled = disabled;
        document.forms.admin.start_minute.disabled = disabled;

        // включить-выключить выбор "через" старт
        disabled = !(this.start_type == 1) || !this.time_type;
        document.forms.admin.across_start.disabled = disabled;
        document.forms.admin.across_start_type.disabled = disabled;

        // включить-выключить поля для ввода конца действия
        disabled = !(this.end_type == 2) || !this.time_type;
        document.forms.admin.end_day.disabled = disabled;
        document.forms.admin.end_month.disabled = disabled;
        document.forms.admin.end_year.disabled = disabled;
        document.forms.admin.end_hour.disabled = disabled;
        document.forms.admin.end_minute.disabled = disabled;

        // включить-выключить выбор "через" старт
        disabled = !(this.end_type == 1) || !this.time_type;
        document.forms.admin.across_end.disabled = disabled;
        document.forms.admin.across_end_type.disabled = disabled;

        var d = new Date();
        document.admin.start_day.value = d.getDate();
        document.admin.start_month.value = ((d.getMonth() + 1) > 9) ? d.getMonth() + 1 : '0' + (d.getMonth() + 1);
        document.admin.start_year.value = d.getFullYear();
        document.admin.start_hour.value = d.getHours();
        document.admin.start_minute.value = ((d.getMinutes()) > 9) ? d.getMinutes() : '0' + (d.getMinutes());
        document.admin.end_day.value = d.getDate();
        document.admin.end_month.value = ((d.getMonth() + 1) > 9) ? d.getMonth() + 1 : '0' + (d.getMonth() + 1);
        document.admin.end_year.value = d.getFullYear() + 1;
        document.admin.end_hour.value = d.getHours();
        document.admin.end_minute.value = ((d.getMinutes()) > 9) ? d.getMinutes() : '0' + (d.getMinutes());

    },

    // обновить форму для простановления "возможностей"
    updateMainRight: function () {
        // показывать ее или нет
        if (!this.type || this.type == 7 || this.type == 6 || this.type == 8) {
            this.div_main_right.style.display = 'none';
        } else {
            this.div_main_right.style.display = 'block';
        }
        // удалить все строки из таблицы
        this.DeleteRows(0);

        switch (this.type) {
            case 14: // developer
                //this.AddRow('developer');
                if (!document.getElementById('dev_classificator')) {
                    this.AddRow('classificator');
                }

                //document.getElementById('div_perm_classificator').style.display = 'block';
                this.xhr.open("POST", "index.php?phase=20", true);
                this.xhr.onreadystatechange = instance.ResponseClassificator;
                this.xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
                this.xhr.send('getclassificator=1');

                break;
            case 30: // subscriber
                if (!document.getElementById('mailer_id')) {
                    this.AddRow('subscriber');
                }

                //document.getElementById('div_perm_classificator').style.display = 'block';
                this.xhr.open("POST", "index.php?phase=20", true);
                this.xhr.onreadystatechange = instance.ResponseMailer;
                this.xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
                this.xhr.send('getmailers=1');
                break;
            case 5: // editor
            case 20: // ban
                this.AddRow('item_content');
                this.AddRow('site');
                break;
            case 'module':
                this.AddRow('module');
                break;
        }

        this.ShowPerm();
    },

    setType: function (value) {
        this.type = value;
        this.showHelp(value);
        this.updateLivetime();
        this.updateMainRight();
    },

    setStartType: function (value) {
        this.start_type = value;
        this.updateLivetime();
    },

    setEndType: function (value) {
        this.end_type = value;
        this.updateLivetime();
    },

    disable_livetime: function (value) {
        this.time_type = !value;
        this.updateLivetime();
    },

    handler_checkbox: function (value) {
        var temp;
        switch (value) {
            case 1: //press 'User Systemcontrol'
                ;//document.getElementById('userperm').style.display = document.forms.admin.usercontrol.checked ? 'block' : 'none';
                break;
            case 2: //press 'no unlimited'
                document.getElementById('div_time').style.display = document.forms.admin.unlimit.checked ? 'none' : 'block';
                if (document.forms.admin.unlimit.checked == false) {
                    var d = new Date();
                    document.admin.start_day.value = d.getDate();
                    document.admin.start_month.value = d.getMonth() + 1;
                    document.admin.start_year.value = d.getFullYear();
                    document.admin.start_hour.value = d.getHours();
                    document.admin.start_minute.value = d.getMinutes();
                    document.admin.end_day.value = d.getDate();
                    document.admin.end_month.value = d.getMonth() + 1;
                    document.admin.end_year.value = d.getFullYear();
                    document.admin.end_hour.value = d.getHours();
                    document.admin.end_minute.value = d.getMinutes();
                }
                break;
            case 3: //press 'ignore start'
                temp = document.forms.admin.ignore_start.checked ? true : false;
                document.forms.admin.start_day.disabled = temp;
                document.forms.admin.start_month.disabled = temp;
                document.forms.admin.start_year.disabled = temp;
                document.forms.admin.start_hour.disabled = temp;
                document.forms.admin.start_minute.disabled = temp;
                break;
            case 4: //press 'ignore end'
                temp = document.forms.admin.ignore_stop.checked ? true : false;
                document.forms.admin.end_day.disabled = temp;
                document.forms.admin.end_month.disabled = temp;
                document.forms.admin.end_year.disabled = temp;
                document.forms.admin.end_hour.disabled = temp;
                document.forms.admin.end_minute.disabled = temp;
                break;
            case 6: //press 'admin'
                if (!document.forms.admin.l06.checked) {
                    break;
                }
                document.forms.admin.l05.checked = true; // moderate
            // здесь break не нужен
            case 5: // press 'moderate'
                if (!document.forms.admin.l05.checked) {
                    break;
                }
                document.forms.admin.l01.checked = true; // read
                document.forms.admin.l02.checked = true; // add
                document.forms.admin.l03.checked = true; // edit
                document.forms.admin.l031.checked = true; // check
                document.forms.admin.l032.checked = true; // delete
                if (document.forms.admin.l04) // на случай, если модуль не установлен
                {
                    document.forms.admin.l04.checked = true;
                } // subsсribe
                if (document.forms.admin.l07) // на случай, если модуль не установлен
                {
                    document.forms.admin.l07.checked = true;
                } // comment
                break;

        }

    },


    ResponseSub: function () {
        if (instance.xhr.readyState == 4) {
            var full_sub = instance.xhr.responseText;
            var sc = document.getElementById('sub_list');
            var div_sc = document.getElementById('div_sub_list');

            sc.options.length = 0;
            div_sc.innerHTML = "<select name='sub_list' id='sub_list' style='width: 100%' onchange='nc_user_obj.change_sub(); return false;'>" + full_sub + "</select>";
            document.getElementById('sub_list').selectedIndex = 0;
            //instance.change_sub();
            //if (instance.item != 2) { // sub admin
            //  instance.ShowPerm();
            //  instance.div_livetime.style.display = 'block';
            // }

        } // ok in response status
    },


    ResponseSubClass: function () {
        if (instance.xhr.readyState == 4) {
            var full_cc = instance.xhr.responseText;
            var sc = document.getElementById('subclass_list');
            var div_sc = document.getElementById('div_subclass_list');

            sc.options.length = 0;
            div_sc.innerHTML = "<select name='subclass_list' style='width: 100%' id='subclass_list'>" + full_cc + "</select>";

            instance.ShowPerm();
            instance.div_livetime.style.display = 'block';
        }
    },


    ResponseClassificator: function () {
        if (instance.xhr.readyState == 4) {
            var full_cl = instance.xhr.responseText;
            var sc = document.getElementById('dev_classificator');

            sc.options.length = 0;
            sc.innerHTML = full_cl;

            // instance.style.display = 'block';
        }
    },


    ResponseMailer: function () {
        if (instance.xhr.readyState == 4) {
            var full_mailers = instance.xhr.responseText;
            var sc = document.getElementById('mailer_id');

            sc.options.length = 0;
            sc.innerHTML = full_mailers;

        }
    },


    change_item: function () {

        this.item = document.getElementById('item').selectedIndex;
        document.getElementById('site_list').options[0].text =
            (this.item !== 0 && this.item !== 3)
            ? some_const['selectsite']
            : some_const['allsite'];
        document.getElementById('site_list').selectedIndex = 0;

        this.DeleteRows(2);
        this.ShowPerm(this.item);
        //this.div_livetime.style.display = this.item ? 'none' : 'block';
    },

    change_site: function () {
        var list = document.getElementById('site_list');

        this.site = list.options[list.selectedIndex].value;

        if (this.item !== 0 && this.item !== 3) { //sub admin or sub class admin && !content editor
            // Если нет строки "Разделы" - добавим,
            if (!document.getElementById('sub_list')) {
                this.AddRow('sub');
            }
            // Если есть строка Компонеты - удалим ее
            if (document.getElementById('subclass_list')) {
                this.DeleteRows(3);
            }
            // Запрос на полчение всех разделов
            this.xhr.open("POST", "index.php?phase=20", true);
            this.xhr.onreadystatechange = instance.ResponseSub;
            this.xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
            if (this.item == 1) {
                this.xhr.send('getsublist=' + this.site);
            } else {
                this.xhr.send('getsublist_cc=' + this.site);
            }
        }
    },


    change_sub: function () {
        var list = document.getElementById('sub_list');

        this.sub = list.options[list.selectedIndex].value;

        if (this.item == 2) { // sub class admin
            //Еслм нет строки Компоненты - добавим ее
            if (!document.getElementById('subclass_list')) {
                this.AddRow('cc');
            }
            this.xhr.open("POST", "index.php?phase=20", true);
            this.xhr.onreadystatechange = instance.ResponseSubClass;
            this.xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
            this.xhr.send('getsubclasslist=' + this.sub);

        }
    },


    change_developer: function () {
        var list = document.getElementById('dev_item');
        this.dev = list.options[list.selectedIndex].value;

        switch (this.dev) {
            case '1': // classificator admin
                if (!document.getElementById('dev_classificator')) {
                    this.AddRow('classificator');
                }

                //document.getElementById('div_perm_classificator').style.display = 'block';
                this.xhr.open("POST", "index.php?phase=20", true);
                this.xhr.onreadystatechange = instance.ResponseClassificator;
                this.xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");
                this.xhr.send('getclassificator=1');
                break;

        }
    },


    change_module: function() {
        var module_select = document.getElementById('module_keyword');
        var module_keyword = module_select.options[module_select.selectedIndex].value;
        var checkboxes = '';

        nc_user_obj.DeleteRows(1);

        if (module_permissions[module_keyword]) {
            if (module_permissions[module_keyword].per_site) {
                nc_user_obj.AddRow('site');
            }

            for (var permission in module_permissions[module_keyword].permissions) {
                checkboxes += '<br><label>' +
                    '<input type="checkbox" name="module_permissions[]" checked="1" value="' + permission +
                    '" onchange="nc_user_obj.change_module_permissions(this)"> ' +
                     module_permissions[module_keyword].permissions[permission] +
                    '</label>';
            }
        }

        document.getElementById('div_perm_module').innerHTML = checkboxes;
    },

    change_module_permissions: function(changed_checkbox) {
        var checkboxes = document.getElementById('div_perm_module').querySelectorAll('input[type="checkbox"]');
        if (changed_checkbox.value === '*') {
            for (var i = 1; i < checkboxes.length; i++) {
                checkboxes[i].checked = changed_checkbox.checked; // установить состояние чекбоксов как у "*" ("полный доступ к модулю")
            }
        } else if (!changed_checkbox.checked) {
            checkboxes[0].checked = false; // снять отметку с "*", когда отмечены не все чекбоксы
        }
        // обратная логика — включение пункта «полный доступ» при включении всех галочек — не применяется,
        // т. к. «полный доступ» может предоставлять больше возможностей, чем все явно присваиваемые отдельные права
        //
        // Но если будет решено сделать иначе, то вот код для этого:
        //
        // else {
        //     var all_checked = true;
        //     for (var i = 1; i < checkboxes.length; i++) {
        //         if (!checkboxes[i].checked) {
        //             all_checked = false;
        //             break;
        //         }
        //     }
        //     checkboxes[0].checked = all_checked; // поставить отметку на "*", когда отмечены все чекбоксы
        // }
    },

    //Добавляет строку к таблице tbl_item
    AddRow: function (type) {

        var docum = document; //top.frames['mainViewIframe'].document;
        var tbody = docum.getElementById('tbl_item').getElementsByTagName('TBODY')[0];
        var row = docum.createElement("TR");
        var tdName = docum.createElement("TD");
        var tdItem = docum.createElement("TD");
        var sc;


        tdName.style.background = "#EEE";
        tdItem.style.background = "#FFF";

        tdName.style.width = '30%';
        tdItem.style.width = '70%';

        tbody.appendChild(row);
        row.appendChild(tdName);
        row.appendChild(tdItem);

        switch (type) {
            case 'item_content':
                tdName.innerHTML = some_const['item'];
                tdItem.innerHTML = "<select name='item' id='item' style='width: 100%' onchange='nc_user_obj.change_item(); return false;'></select>";

                sc = document.getElementById('item');
                sc.options.length = 0;
                if (this.type == 20) { //Ban
                    sc.options[0] = new Option(some_const['site'], 1);
                    sc.options[1] = new Option(some_const['sub'], 2);
                    sc.options[2] = new Option(some_const['cc'], 3);
                    sc.options[3] = new Option(some_const['contentadmin'], 4);
                } else { //Moderator
                    sc.options[0] = new Option(some_const['siteadmin'], 1);
                    sc.options[1] = new Option(some_const['subadmin'], 2);
                    sc.options[2] = new Option(some_const['ccadmin'], 3);
                    sc.options[3] = new Option(some_const['contentadmin'], 4);
                }


                break;
            case 'site':
                tdName.innerHTML = some_const['site'];
                tdItem.innerHTML = "<select name='site_list' id='site_list' style='width: 100%' onchange='nc_user_obj.change_site(); return false;'></select>";
                sc = document.getElementById('site_list');
                sc.options.length = 0;

                sc.options[0] = new Option(some_const['allsite'], 0);
                for (i = 0; i < site_id.length; i++) {
                    sc.options[i + 1] = new Option(site_name[i], site_id[i]);

                }

                break;
            case 'sub':
                tdName.innerHTML = some_const['sub'];
                tdItem.innerHTML = "<div id='div_sub_list'><select name='sub_list' id='sub_list' style='width: 100%' onchange='nc_user_obj.change_sub(); return false;'></select></div>";
                sc = document.getElementById('sub_list');
                sc.options.length = 0;
                sc.options[0] = new Option(some_const['load'], 1);
                break;
            case 'cc':
                tdName.innerHTML = some_const['cc'];
                tdItem.innerHTML = "<div id='div_subclass_list'><select name='subclass_list' style='width: 100%' id='subclass_list'></select></div>";
                sc = document.getElementById('subclass_list');
                sc.options.length = 0;
                sc.options[0] = new Option(some_const['load'], 1);
                break;

            case 'developer':
                tdName.innerHTML = some_const['item'];
                tdItem.innerHTML = "<select name='dev_item' id='dev_item' style='width: 100%' onchange='nc_user_obj.change_developer(); return false;'></select>";
                sc = document.getElementById('dev_item');
                sc.options.length = 0;
                sc.options[0] = new Option(some_const['selectitem'], 0);
                sc.options[1] = new Option(some_const['classificator'], 1);
                //sc.options[2] = new Option("Компонент", 2);
                //sc.options[3] = new Option("Макет дизайна", 3);
                break;
            case 'classificator':
                tdName.innerHTML = some_const['classificator'];
                tdItem.innerHTML = "<select name='dev_classificator' id='dev_classificator' style='width: 100%'></select>";
                sc = document.getElementById('dev_classificator');
                sc.options.length = 0;
                sc.options[0] = new Option(some_const['load'], 0);
                break;
            case 'subscriber':
                tdName.innerHTML = some_const['mailer'];
                tdItem.innerHTML = "<select name='mailer_id' id='mailer_id' style='width: 100%'></select>";
                sc = document.getElementById('mailer_id');
                sc.options.length = 0;
                sc.options[0] = new Option(some_const['load'], 0);
                break;
            case 'module':
                tdName.innerHTML = some_const['module'];
                tdItem.innerHTML = "<select name='module_keyword' id='module_keyword' style='width: 100%' onchange='nc_user_obj.change_module(); return false;'></select>";
                sc = document.getElementById('module_keyword');
                sc.options.length = 0;
                for (var keyword in module_permissions) {
                    sc.options[sc.options.length] = new Option(module_permissions[keyword].name, keyword);
                }
                nc_user_obj.change_module();
                break;

        }
    },


    DeleteRows: function (begin_id) {
        // var tabl = top.frames['mainViewIframe'].document.getElementById('tbl_item');
        var tabl = document.getElementById('tbl_item');
        var num_rows = tabl.rows.length;
        var i;

        for (i = num_rows - 1; i >= begin_id; i--) {
            tabl.deleteRow(i);
        }
    },


    ShowPerm: function (only_clear) {
        document.getElementById('div_perm').style.display = 'none';
        document.getElementById('div_perm_ban').style.display = 'none';
        document.getElementById('userperm').style.display = 'none';
        document.getElementById('group_perm').style.display = 'none';
        document.getElementById('div_perm_classificator').style.display = 'none';
        document.getElementById('div_perm_subscriber').style.display = 'none';
        document.getElementById('div_perm_module').style.display = 'none';

        //if (only_clear) return; // only clear

        switch (this.type) {
            case 14:
                document.getElementById('div_perm_classificator').style.display = 'block';
                break;
            case 5:
                document.getElementById('div_perm').style.display = 'block';
                break;
            case 12:
                document.getElementById('userperm').style.display = 'block';
                break;
            case 13:
                document.getElementById('group_perm').style.display = 'block';
                break;
            case 20:
                document.getElementById('div_perm_ban').style.display = 'block';
                break;
            case 30:
                document.getElementById('div_perm_subscriber').style.display = 'block';
                break;
            case 'module':
                document.getElementById('div_perm_module').style.display = 'block';
                break;
        }
    },


    showHelp: function (value) {
        document.getElementById('div_help').style.display = 'block';

        switch (value) {
            case 0:
                this.div_help.innerHTML = ncLang.UserSelectRights;
                break;
            case 7:
                this.div_help.innerHTML = ncLang.UserHelpDirector;
                break;
            case 6:
                this.div_help.innerHTML = ncLang.UserHelpSupervisor;
                break;
            case 5:
                this.div_help.innerHTML = ncLang.UserHelpEditor;
                break;
            case 12:
                this.div_help.innerHTML = ncLang.UserHelpModerator;
                break;
            case 13:
                this.div_help.innerHTML = ncLang.UserHelpGroup;
                break;
            case 14:
                this.div_help.innerHTML = ncLang.UserHelpClassificator;
                break;
            case 20:
                this.div_help.innerHTML = ncLang.UserHelpBanned;
                break;
            case 8:
                this.div_help.innerHTML = ncLang.UserHelpGuest;
                break;
            case 30:
                this.div_help.innerHTML = ncLang.UserHelpSubscriber;
                break;
            default:
                document.getElementById('div_help').style.display = 'none';
                break;
        }
    }

};