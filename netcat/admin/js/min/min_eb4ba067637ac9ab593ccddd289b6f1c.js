nc_auth = function (settings) {
    if (!settings) {
        settings = {};
    }

    this.check_login = settings.check_login;// || true;
    this.pass_min = settings.pass_min || 0;
    this.login_id = "#" + (settings.login_id || "f_Login");
    this.pass1_id = "#" + (settings.pass1_id || "Password1");
    this.pass2_id = "#" + (settings.pass2_id || "Password2");
    this.wait_id = "#" + (settings.wait_id || "nc_auth_wait");
    this.login_ok_id = "#" + (settings.login_ok_id || "nc_auth_login_ok");
    this.login_fail_id = "#" + (settings.login_fail_id || "nc_auth_login_fail");
    this.login_incorrect_id = "#" + (settings.login_incorrect_id || "nc_auth_login_incorrect");
    this.pass1_security = "#" + (settings.pass1_security || "nc_auth_pass1_security");
    this.pass1_empty = "#" + (settings.pass1_empty || "nc_auth_pass1_empty");
    this.pass2_ok_id = "#" + (settings.pass2_ok_id || "nc_auth_pass2_ok");
    this.pass2_fail_id = "#" + (settings.pass2_fail_id || "nc_auth_pass2_fail");

    if (this.check_login && this.check_login != "0") {
        jQuery(this.login_id).change(function () {
            nc_auth_obj.check_loginf()
        });

        jQuery(this.login_id).keypress(function () {
            jQuery(".nc_auth_login_check").hide()
        });

        this.check_loginf();
    }

    if (settings.check_pass && settings.check_pass != "0") {
        jQuery(this.pass1_id).bind("keyup change", function () {
            nc_auth_obj.check_pass()
        });
    }

    if (settings.check_pass2 && settings.check_pass2 != "0") {
        jQuery(this.pass2_id).bind("keyup change", function () {
            nc_auth_obj.check_pass2()
        });
    }

    this.cache_pass = "";
}

nc_auth.prototype = {

    check_loginf: function () {
        if (!jQuery(this.login_id).val().length) {
            jQuery(".nc_auth_login_check").hide();
            jQuery(".nc_auth_pass1_check").hide();
            jQuery(".nc_auth_pass2_check").hide();

            return false;
        }

        jQuery.post(NETCAT_PATH + "modules/auth/ajax.php",
            "act=check_login&login=" + jQuery(this.login_id).val(),
            function (res) {
                nc_auth_obj.check_login_res(res);
            },
            "json");

        this.process = true;
        jQuery(".nc_auth_login_check").hide();
        jQuery(".nc_auth_pass1_check").hide();
        jQuery(".nc_auth_pass2_check").hide();
        jQuery(this.wait_id).show();

        return false;
    },


    check_login_res: function (res) {
        jQuery(".nc_auth_login_check").hide();
        jQuery(".nc_auth_pass1_check").hide();
        jQuery(".nc_auth_pass2_check").hide();

        if (res == 2) {
            jQuery(this.login_fail_id).show();
        } else if (res == 1) {
            jQuery(this.login_incorrect_id).show();
        } else {
            jQuery(this.login_ok_id).show();
        }
    },


    check_pass: function () {
        const password = jQuery(this.pass1_id).val();

        // Кеширование во избежание одинаковых проверок
        if (this.cache_pass == password) {
            return false;
        }

        this.cache_pass = password;

        jQuery(".nc_auth_pass1_check").hide();

        const passwordLength = password.length;

        if (!passwordLength) {
            jQuery(this.pass1_empty).show();
            jQuery(this.pass1_security).hide();

            return false;
        } else {
            jQuery(this.pass1_empty).hide();
        }

        if (passwordLength < this.pass_min) {
            jQuery("#nc_auth_pass_min").show();
            jQuery(this.pass1_security).hide();

            return false;
        }

        jQuery("#nc_auth_pass_min").hide();

        // Количество множеств, из которых составлен пароль (a-z, A-Z, 0-9, остальные)
        let passwordSetsCount = 0;
        const expr1 = new RegExp("[a-z]");
        const expr2 = new RegExp("[A-Z]");
        const expr3 = new RegExp("[0-9]");
        const expr4 = new RegExp("[^a-zA-Z0-9]");

        if (expr1.test(password)) {
            passwordSetsCount++;
        }

        if (expr2.test(password)) {
            passwordSetsCount++;
        }

        if (expr3.test(password)) {
            passwordSetsCount++;
        }

        if (expr4.test(password)) {
            passwordSetsCount++;
        }

        jQuery(this.pass1_security).show();

        if (passwordSetsCount === 4 && passwordLength >= 12) {
            jQuery("#nc_auth_pass1_s4").show();
        } else if (passwordSetsCount >= 3 && passwordLength >= 8) {
            jQuery("#nc_auth_pass1_s3").show();
        } else if (passwordSetsCount >= 2 && passwordLength >= 6) {
            jQuery("#nc_auth_pass1_s2").show();
        } else {
            jQuery("#nc_auth_pass1_s1").show();
        }

        if (jQuery(this.pass2_id).val()) {
            this.check_pass2();
        }

        return false;
    },

    check_pass2: function () {
        jQuery(".nc_auth_pass2_check").hide();

        if (jQuery(this.pass1_id).val() == jQuery(this.pass2_id).val()) {
            jQuery(this.pass2_ok_id).show();
        } else {
            jQuery(this.pass2_fail_id).show();
        }
    }
}

nc_auth_token = function (settings) {
    this.randnum = settings.randnum || 0;
    this.form_id = settings.form_id || "nc_auth_form";
    this.select_id = settings.select_id || "nc_token_login";
    this.login_id = settings.login_id || "nc_token_login";
    this.token_id = settings.token_id || "nc_token_signature";
    this.tokenService = null;
    this.pluginLoaded = false;
}

nc_auth_token.prototype = {
    async $loadPlugin() {
        return await rutoken.ready
            .then(function () {
                if (window.chrome || typeof InstallTrigger !== "undefined") {
                    return rutoken.isExtensionInstalled();
                } else {
                    return Promise.resolve(true);
                }
            })
            .then(function (result) {
                if (result) {
                    return rutoken.isPluginInstalled();
                } else {
                    return Promise.reject("Не удаётся найти расширение 'Адаптер Рутокен Плагина'");
                }
            })
            .then(function (result) {
                if (result) {
                    return rutoken.loadPlugin();
                } else {
                    return Promise.reject("Не удаётся найти Плагин");
                }
            })
            .then(function (plugin) {
                if (!plugin || !plugin.valid) {
                    return Promise.reject("Не удаётся загрузить Плагин");
                }

                return Promise.resolve(plugin);
            })
    },

    async load() {
        if (this.pluginLoaded) {
            return;
        }

        const plugin = await this.$loadPlugin();
        this.tokenService = new RutokenService(plugin);
        this.pluginLoaded = true;
    },

    async show_containers() {
        await this.load();

        const containers = await this.tokenService.getContainers();
        const select = jQuery("#" + this.select_id);
        containers.forEach(container => select.append(jQuery(`<option value='${container}'>${container}</option>`)))
    },


    async sign() {
        await this.load();

        const sign = await this.tokenService.sign(document.getElementById(this.select_id).value, this.randnum);

        document.getElementById(this.token_id).value = sign;
        document.getElementById("AUTH_FORM").submit();
    },

    async reg() {
        await this.load();

        const key = await this.tokenService.generateKeyPair(jQuery("#" + this.login_id).val());
        jQuery("#" + this.token_id).val(key);
    },

    async attempt_delete(containerId) {
        await this.load();

        try {
            await this.tokenService.deleteContainer(containerId);
        } catch (ignored) {}

    }
}


nc_auth_ajax = function (settings) {
    if (!settings) {
        settings = {};
    }
    this.auth_link = "#" + (settings.auth_link || "nc_auth_link");
    this.params = settings.params || "";
    this.params_hash = settings.params_hash;
    this.postlink = settings.postlink || NETCAT_PATH + "modules/auth/ajax.php";
    this.template = settings.template || "";
    this.template_hash = settings.template_hash;
    jQuery(this.auth_link).click(function () {
        nc_auth_ajax_obj.show_layer();
    });
    jQuery("#nc_auth_form_ajax").submit(function () {
        nc_auth_ajax_obj.sign();
        return false;
    });
}

nc_auth_ajax.prototype = {
    show_layer: function () {
        jQuery("#nc_auth_layer").modal();
    },

    sign: function () {
        // collect form values into array
        oForm = document.getElementById("nc_auth_form_ajax");
        var values = "act=auth&params=" + nc_auth_ajax_obj.params + "&template=" + nc_auth_ajax_obj.template +
            "&params_hash=" + nc_auth_ajax_obj.params_hash +
            "&template_hash=" + nc_auth_ajax_obj.template_hash;
        for (var i = 0; i < oForm.length; i++) {
            var el = oForm.elements[i];
            if (el.tagName == "SELECT") {
                values += "&" + el.name + "=" + el.options[el.options.selectedIndex].value;
            } else if (el.tagName == "INPUT" && (el.type == "checkbox" || el.type == "radio")) {
                if (el.checked) {
                    values += "&" + el.name + "=" + el.value;
                }
            } else if (el.name && el.value != undefined) {
                values += "&" + el.name + "=" + el.value;
            }
        }

        jQuery.post(this.postlink, values, function (res) {
            nc_auth_ajax_obj.sign_res(res);
        }, "json");
    },


    sign_res: function (res) {
        if (res.captcha_wrong) {
            jQuery("#nc_auth_captcha_error").show();
            var s = jQuery("#nc_auth_form_ajax img[name='nc_captcha_img']").attr("src");
            s = s.replace(/code=[a-z0-9]+/, "code=" + res.captcha_hash);
            jQuery("#nc_auth_form_ajax img[name='nc_captcha_img']").attr("src", s);
            return false;
        }

        if (!res.user_id) {
            jQuery("#nc_auth_error").show();
            return false;
        }

        jQuery.modal.close();
        jQuery(".auth_block").replaceWith(res.auth_block);

        return false;
    }
}

function nc_auth_openid_select(url) {
    oTxt = document.getElementById("openid_url");
    oTxt.value = url;

    if ((start = url.indexOf("USERNAME")) > 0) {
        length = 8;
        if (oTxt.createTextRange) {
            var oRange = oTxt.createTextRange();
            oRange.moveStart("character", start);
            oRange.moveEnd("character", length - oTxt.value.length);
            oRange.select();
        } else if (oTxt.setSelectionRange) {
            oTxt.setSelectionRange(start, start + length);
        }
        oTxt.focus();
    }


}

/*!
 * jQuery Cookie Plugin v1.4.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD
		define(['jquery'], factory);
	} else if (typeof exports === 'object') {
		// CommonJS
		factory(require('jquery'));
	} else {
		// Browser globals
		factory(jQuery);
	}
}(function ($) {

	var pluses = /\+/g;

	function encode(s) {
		return config.raw ? s : encodeURIComponent(s);
	}

	function decode(s) {
		return config.raw ? s : decodeURIComponent(s);
	}

	function stringifyCookieValue(value) {
		return encode(config.json ? JSON.stringify(value) : String(value));
	}

	function parseCookieValue(s) {
		if (s.indexOf('"') === 0) {
			// This is a quoted cookie as according to RFC2068, unescape...
			s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}

		try {
			// Replace server-side written pluses with spaces.
			// If we can't decode the cookie, ignore it, it's unusable.
			// If we can't parse the cookie, ignore it, it's unusable.
			s = decodeURIComponent(s.replace(pluses, ' '));
			return config.json ? JSON.parse(s) : s;
		} catch(e) {}
	}

	function read(s, converter) {
		var value = config.raw ? s : parseCookieValue(s);
		return $.isFunction(converter) ? converter(value) : value;
	}

	var config = $.cookie = function (key, value, options) {

		// Write

		if (value !== undefined && !$.isFunction(value)) {
			options = $.extend({}, config.defaults, options);

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setTime(+t + days * 864e+5);
			}

			return (document.cookie = [
				encode(key), '=', stringifyCookieValue(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
			].join(''));
		}

		// Read

		var result = key ? undefined : {};

		// To prevent the for loop in the first place assign an empty array
		// in case there are no cookies at all. Also prevents odd result when
		// calling $.cookie().
		var cookies = document.cookie ? document.cookie.split('; ') : [];

		for (var i = 0, l = cookies.length; i < l; i++) {
			var parts = cookies[i].split('=');
			var name = decode(parts.shift());
			var cookie = parts.join('=');

			if (key && key === name) {
				// If second argument (value) is a function it's a converter...
				result = read(cookie, value);
				break;
			}

			// Prevent storing a cookie that we couldn't decode.
			if (!key && (cookie = read(cookie)) !== undefined) {
				result[name] = cookie;
			}
		}

		return result;
	};

	config.defaults = {};

	$.removeCookie = function (key, options) {
		if ($.cookie(key) === undefined) {
			return false;
		}

		// Must not alter options, thus extending a fresh object...
		$.cookie(key, '', $.extend({}, options, { expires: -1 }));
		return !$.cookie(key);
	};

}));

var ncLang = {
  // управление правами пользователя
  UserSelectRights : "Выберите тип прав.",
  UserHelpDirector : "Пользователь с правами «Директор» имеет полные права на управление всеми сайтами, пользователями и инструментами в системе.",
  UserHelpSupervisor : "Пользователь с правами «Супервизор» имеет полные права на управление всеми сайтами, пользователями и инструментами в системе, за исключением управления пользователями, имеющими права «Директор».",
  UserHelpEditor : "Пользователь с правами «Редактор» имеет права на управление только назначенного сайта, раздела или инфоблока. " +
      "Действия изменения, включения/выключения, удаления относятся только ко своим собственным объектам.<br><br>" +
      "Пользователь с правами «Редактор контента сайта» получает соответствующие права только на блоки, привязанные к разделам (расположенные внутри " +
      "основной контентной области); «редактор сайта» получает также права на сквозные блоки (расположенные на всех страницах сайта).",
  UserHelpModerator : "Пользователи с правами «Директор» и «Супервизор» могут производить с пользователями любые действия (создавать, редактировать, присваивать права, удалять).<br><br>" +
      "Права на добавление, редактирование и удаление пользователей дают возможность управлять пользователями, которым не присвоены права в системе.<br><br>" +
      "Права на модерирование позволяют изменять свойства пользователей (в т. ч. пароль) тех пользователей, права которых не шире, чем у пользователя-модератора, а при наличии права на удаление также удалять таких пользователей.<br><br>" +
      "Права на администрирование позволяют назначать другим пользователям права, которые есть у пользователя-администратора.",
  UserHelpGroup: "Права на управление группами пользователей позволяют добавлять, переименовывать, удалять группы. Право на администрирование позволяет изменять права, присвоенные группе (в пределах прав пользователя-администратора).",
  UserHelpClassificator : "Пользователь с правами «Администратор списка» имеет права на управление списками.",
  UserHelpBanned : "Пользователь с правами этой категории будет ограничен в выбранных вами действиях.",
  UserHelpGuest : "Пользователь с правом «Гость» может \"ходить\" по системе администрирования, однако ему запрещено производить какие-либо операции с системой (демонстрационный режим).",
  UserHelpSubscriber : "Пользователь с правом «Подписчик» имеет право подписаться на выбранную рассылку.",
  UserPasswordsMismatch : "Пароли не совпадают!",
  WarnAddTemplate : "Альтернативная форма добавления не пустая! Заменить текст в этом поле на новый?",
  WarnEditTemplate : "Альтернативная форма изменения не пустая! Заменить текст в этом поле на новый?",
  WarnDeleteTemplate : "Альтернативная форма удаления не пустая! Заменить текст в этом поле на новый?",
  WarnSearchTemplate : "Форма поиска  объектов не пустая! Заменить текст в этом поле на новый?",
  WarnFullSearchTemplate : "Форма поиска перед списком объектов не пустая! Заменить текст в этом поле на новый?",
  WarnAddCond : "Поле \"условие добавления\" не пустое! Заменить текст в этом поле на новый?",
  WarnEditCond : "Поле \"условие изменения\" не пустое! Заменить текст в этом поле на новый?",
  WarnAddActionTemplate : "Поле \"действие после добавления\" не пустое! Заменить текст в этом поле на новый?",
  WarnEditActionTemplate : "Поле \"действие после изменения\" не пустое! Заменить текст в этом поле на новый?",
  WarnCheckActionTemplate : "Поле \"действие после включения\" не пустое! Заменить текст в этом поле на новый?",
  WarnDeleteActionTemplatee : "Поле \"действие после удаления\" не пустое! Заменить текст в этом поле на новый?",
  WarnAuthMail : "Восстановить значение по умолчанию?",
  WarnReplace : "Текущее значение шаблона будет заменено. Продолжить?",
  FieldFromUser : "Поля из системной таблицы Пользователи",
  Drop : "Удалить",
  DropHard : "Удалить безвозвратно?",
  Cancel : "Отмена",
  AddSubsection: "добавить подраздел",
  DebugCheckData: "Проверка данных",
  MessageError: "Ошибка",
  SystemName: "Netcat",
  NetcatCMS: "Система управления сайтами " + this.SystemName,
  RemindSaveWarning: "У вас есть несохраненные изменения. Продолжить без сохранения?",
  Close: "Закрыть",
  DragAndDropConfirm: {
    buttons: { inside: 'Переместить', default: 'Всё верно' },
    site_below_site: { title: 'Изменение сортировки сайтов', text: 'Сайт «%1» в списках сайтов будет располагаться после сайта «%2».' },
    sub_inside_sub: { title: 'Перемещение раздела', text: 'Раздел «%1» будет подразделом в разделе «%2».' },
    sub_below_sub: { title: 'Изменение сортировки разделов', text: 'Раздел «%1» в списках разделов будет располагаться после раздела «%2».' },
    sub_firstIn_sub: { title: 'Изменение сортировки разделов', text: 'Раздел «%1» будет первым подразделом в разделе «%2».' },
    sub_inside_site: { title: 'Перемещение раздела', text: 'Раздел «%1» будет располагаться в корне сайта «%2».' },
    sub_firstIn_site: { title: 'Изменение сортировки разделов', text: 'Раздел «%1» будет первым в корне сайта «%2».' },
    subclass_inside_sub: { title: 'Перемещение инфоблока', text: 'Инфоблок «%1» будет перемещён в раздел «%2».' },
    message_inside_sub: { title: 'Перемещение объекта', text: '«%1» будет перемещён в инфоблок в разделе «%2».' },
    message_inside_message: { title: 'Изменение сортировки объектов', text: '«%1» при сортировке по умолчанию будет располагаться после «%2».', button: 'Всё верно' },
    template_inside_template: { title: 'Изменение иерархии макетов', text: 'Макет «%1» станет наследником макета «%2».', button: 'Всё верно' },
    template_below_template: { title: 'Изменение сортировки макетов', text: 'Макет «%1» в списках макетов будет располагаться после макета «%2».' },
    template_firstIn_template: { title: 'Изменение сортировки макетов', text: 'Макет «%1» в списках макетов будет располагаться первым в списке дочерних макетов «%2».' },
    field_below_field: { title: 'Изменение сортировки полей', text: 'Поле «%1» в списке полей и стандартных формах будет располагаться ниже поля «%2».' },
    systemfield_below_systemfield: { title: 'Изменение сортировки полей', text: 'Поле «%1» в списке полей и стандартных формах будет располагаться ниже поля «%2».' },
    dataclass_below_dataclass: { title: 'Изменение сортировки компонентов', text: 'Компонент «%1» в списках компонентов будет располагаться ниже компонента «%2».' },
    dataclass_inside_group: { title: 'Изменение группы компонента', text: 'Компонент «%1» будет перемещён в группу «%2».' }
  },
  DisallowMoveAndDeleteInformation: {
    disallow_move_sub: { title: 'Не удалось переместить раздел', text: 'На разделе или его дочернем разделе стоит запрет на перемещение.' }
  }
};
(function(e,k){var h=function(b,c){return typeof b===c};if(h(e.nc,"function"))return e.nc;"object"!==typeof JSON&&(JSON={});JSON.stringify=JSON.stringify||function(b){var c=typeof b;if("object"!==c||null===b)return"string"===c&&(b='"'+b+'"'),String(b);var d,p=[],n=b&&b.constructor===Array;for(d in b){var m=b[d];c=typeof m;"string"===c?m='"'+m+'"':"object"===c&&null!==m&&(m=JSON.stringify(m));p.push((n?"":'"'+d+'":')+String(m))}return(n?"[":"{")+String(p)+(n?"]":"}")};var g=!1,f={netcat_path:"undefined"===
typeof NETCAT_PATH?"/netcat/":NETCAT_PATH,admin_path:"undefined"===typeof ADMIN_PATH?"/netcat/admin/":ADMIN_PATH,custom_scroll:!0},l=!1,a=function(){return k.apply(null,arguments)};a.$=k;a.window=e;a.root=a;a.view={};a.process_list={};h(e.parent.nc,"function")&&(a.root=e.parent.nc.root);a.is=h;a.debug=function(b,c){a.is_bool(b)&&!a.is_undefined(b)?g=b:g&&console.log(b+":",c);return g};a.key_exists=function(b,c){return c?c.hasOwnProperty(b):!1};a.is_object=function(b){return h(b,"object")};a.is_undefined=
function(b){return h(b,"undefined")};a.is_bool=function(b){return h(b,"boolean")};a.is_string=function(b){return h(b,"string")};a.is_function=function(b){return h(b,"function")};a.is_empty=function(b){switch(!0){case !b:return!0;case b.length&&0<b.length:return!1;case 0===b.length:return!0}for(var c in b)if(b.hasOwnProperty(c))return!1;return!0};a.is_touch=function(){var b=navigator.userAgent.toLowerCase(),c=["iphone","ipad","ipod","android"],d;for(d in c)if(-1!==b.indexOf(c[d]))return c[d];return!1};
a.is_root=function(){return a.root===a};a.ext=function(b,c,d){d=d||"root";a.is_undefined(a[d][b])?(a[d][b]=c,a.key_exists("__init",c)&&a.is_function(c.__init)&&c.__init()):"root"!==d&&(a[d][b]=c);"root"===d&&a.set_global(b)};a.set_global=function(b,c){c&&(a.root[b]=c);a[b]=a.root[b];for(var d in a.view)a.view[d][b]=a.root[b]};a._view="";a.register_view=function(b){for(var c in a.root.process_list)a.root.process_list[c].view===b&&delete a.root.process_list[c];a.process_stop();l=b;a.ext(b,a,"view");
a.event.call(["nc","register_view"],b)};a.process_start=function(b,c){a.is_empty(a.root.process_list)&&a.event.call(["nc","loading","start"]);a.root.process_list[b]={view:l,obj:c};a.ui.loader_show(c);a.event.call(["nc","process","start"],b,c)};a.process_stop=function(b,c){var d;b&&a.key_exists(b,a.root.process_list)&&((d=a(a.root.process_list[b].obj))&&a.ui.loader_hide(d,c),a.event.call(["nc","process","stop"],b,d),delete a.root.process_list[b]);a.is_empty(a.root.process_list)&&(a.ui.loader_hide(null,
c),a.event.call(["nc","loading","stop"]))};a.config=function(b,c){if(a.is_undefined(b))return f;if(!a.is_undefined(c))f[b]=c;else if(a.is_object(b)){for(var d in b)f[d]=b[d];return f}return f[b]};a.load_dialog=function(b,c,d){return(new a.ui.modal_dialog({url:b,parameters:c||{admin_modal:1,isNaked:1},method:d})).open()};a.get_closest_editor_parent=function(b){return k(b).closest(".nc-infoblock-object, .tpl-block-full, .nc-infoblock")};a.save_inline_then_load_dialog=function(b,c){var d=k(b).data("ncDialogOpenStart");
a.get_closest_editor_parent(b).hasClass("nc--with-active-editor")&&(!d||5E3>Date.now()-d)?(d||k(b).data("ncDialogOpenStart",Date.now()),a.process_start("dialog_waiting_for_editor_release"),setTimeout(function(){a.save_inline_then_load_dialog(b,c)},10)):(k(b).removeData("ncDialogOpenStart"),a.process_stop("dialog_waiting_for_editor_release"),a.load_dialog(c||b.href))};a.load_script=function(b,c){return k.ajax({url:b,dataType:"script",cache:!0,async:!!c})};a.load_script_if=function(b,c,d){return b?
a.load_script(c,d):k.when()};a.load_class=function(b,c,d){return e[b]&&"function"===typeof e[b]&&/^\s*class\b/.test(e[b].toString())?k.when():a.load_script(c,d)};a.set_global("debug");a.set_global("view");a.set_global("config");e.nc=a})(window,jQuery);
(function(e){var k={},h=function(g,f){e.is_undefined(k[g])&&(k[g]=[]);k[g].push(f)};h.selector=[];h.call=function(g){h.selector=g.slice();var f,l,a=[],b="";for(f in g)b=(b?b+".":"")+g[f],a.unshift(b);b=[].slice.call(arguments);b[0]=h;for(f in a)if(k[a[f]])for(l in k[a[f]])k[a[f]][l].apply(null,b)};e.ext("event",h)})(nc);
(function(e){var k=!1,h=function(){};h.__init=function(){e.event(["nc.process.stop"],function(g,f){if("loadIframe()"===f)(g=e("#mainViewIframe"))&&g.attr("title",e("#mainViewHeader").text());else if("tree"===f.split(".")[0]&&e.view&&e.view.tree)return h.accessibility_link(e.view.tree("a"))})};h.loader_show=function(g){g&&e(g).addClass("nc--loading");k&&clearTimeout(k);e.root("#nc-navbar-loader").show()};h.loader_hide=function(g,f){f=f||300;g?setTimeout(function(){g.removeClass("nc--loading")},f):
k=setTimeout(function(){e.root("#nc-navbar-loader").hide()},f)};h.ext=function(g,f){e.root.ui[g]=f};h.custom_scroll=function(g){return e.config("custom_scroll")&&g.length?g.jScrollPane({autoReinitialise:!0}):!1};h.accessibility_link=function(g){g.each(function(){var f=e(this),l=f.attr("title"),a=this.innerText;a=l?l:a;if(!l){if(!a)return e.debug("badlink",f);l||f.attr("title",a)}})};e(function(){h.accessibility_link(e("a"));e(document).on("click","div.nc-panel-toggle",function(){e(this).parents("div.nc-panel").toggleClass("nc--open nc--close")})});
e.ext("ui",h)})(nc);

(function(){function g(a){if(!(this instanceof g))return new g(a);this.options=c.extend(!0,{},this.options);this.set_options(a);this.options.url||(this.is_loaded=!0);return this}function m(a){return"simplemodal-container--"+(a||f)}function q(){var a=g.get_opened_dialog();if(null!==a){var b=a.options.persist,d=c("#simplemodal-placeholder");b&&this.d.placeholder?d.replaceWith(this.d.data.removeClass("simplemodal-data").css("display",this.display)):(d.remove(),a.dialog_container.remove());c("#simplemodal-container, #simplemodal-overlay").remove();
this.d={};(h=g.get_previous_dialog())?(f=h.index,a=h.dialog_container.closest(".simplemodal-container").attr("id","simplemodal-container"),this.d=a.data("modal_d"),this.o=a.data("modal_o"),this.occb=!1,this.unbindEvents(),this.bindEvents(),c(".simplemodal-overlay").last().attr("id","simplemodal-overlay")):(f=0,window.autosave&&(autosave.stopTimer(),autosave=null))}}function r(a){27===a.keyCode&&(a=c(a.target),a.is(":input")&&a.blur(),g.get_opened_dialog().close())}function n(a){if(!window.nc_autosave_use||
!window.InitAutosave)return!1;a=a.get_form();return"adminForm"===a.attr("id")&&!!a.find(':hidden[name="message"]').val()&&!!a.find(':hidden[name="cc"]').val()}function k(a){return'[data-tab-id="'+a+'"]'}function p(a,b,c){a&&((new RegExp("[?&]"+b+"=")).test(a)||(a+=(0<=a.indexOf("?")?"&":"?")+b+"="+c));return a}function t(a){return"boolean"!==typeof a?!/^(no|false|0)$/i.test(a.toString()):a}if("undefined"!==typeof nc&&nc===nc.root){var c=nc.root.$;c(window).on("resize.modal_dialog",function(){c(".simplemodal-container").each(function(){var a=
c(this).data("modal_dialog");a&&a.resize&&a.resize()})});var h=null,f=0;g.get_opened_dialog=function(){return h&&h.is_open?h:null};g.get_current_dialog=function(){return h};g.get_previous_dialog=function(){for(var a=f;0<--a;){var b=c("#"+m(a)).data("modal_dialog");if(b)return b}return null};g.prototype={constructor:g,options:{focus:!0,url:null,parameters:null,persist:!1,confirm_close:!0,on_show:c.noop,on_resize:c.noop,on_submit_response:null,width:null,height:null,min_width:600,max_width:1200,full_markup:'<div class="nc-modal-dialog"><div class="nc-modal-dialog-header"><h2>&nbsp;</h2></div><div class="nc-modal-dialog-body"></div><div class="nc-modal-dialog-footer"></div></div>',
hidden_tabs:null,show_close_button:!0,on_tab_change:{}},loaded_markup:null,dialog_container:null,parts:{header:".nc-modal-dialog-header",title:".nc-modal-dialog-header h2",header_tabs:".nc-modal-dialog-header-tabs",body:".nc-modal-dialog-body",body_tabs:".nc-modal-dialog-body-tab",footer:".nc-modal-dialog-footer"},is_loaded:!1,is_open:!1,are_tabs_initialized:!1,has_header:!0,has_unsaved_changes:!1,index:0,set_options:function(a){this.options=c.extend(this.options,a)},set_option:function(a,b){this.options[a]=
b},get_option:function(a){return this.options[a]},set_on_tab_change:function(a,b){"function"===typeof b&&(this.options.on_tab_change.hasOwnProperty(a)?this.options.on_tab_change[a].push(b):this.options.on_tab_change[a]=[b])},load:function(){var a=this;nc.process_start("nc.ui.modal_dialog.load()");return c.ajax({method:this.options.method||"GET",url:this.options.url,data:c.extend({isNaked:1},this.options.parameters||{})}).done(function(b){a.loaded_markup=c.trim(b)}).always(function(){a.is_loaded=!0;
nc.process_stop("nc.ui.modal_dialog.load()",0)})},create:function(){if(!this.is_loaded)return this.load().done(c.proxy(this,"create"));var a=this.options;if(this.loaded_markup){var b=c(this.loaded_markup);if(b.is(".nc-modal-dialog"))var d=b;else b=b.find(".nc-modal-dialog"),b.length&&(d=b)}d||(d=c(a.full_markup),this.loaded_markup&&d.find(".nc-modal-dialog-body").append(this.loaded_markup));a=d.find("script").remove();h=this;this.dialog_container=d.hide().appendTo("body");this.get_part("header").length||
(this.dialog_container.addClass("nc-modal-dialog-without-header"),this.has_header=!1);this.init_options();this.init_close_button();this.init_tabs();this.init_forms();this.init_buttons();d.append(a);return c.Deferred().resolve()},init_options:function(){var a=this.options,b=this.dialog_container,d;for(d in a){var e=b.data(d.replace(/_/g,"-"));void 0!==e&&e.toString().length&&(a[d]=e)}b=this.options.hidden_tabs;null===b?a.hidden_tabs=[]:c.isArray(b)||(a.hidden_tabs=[],c.each(b.match(/[\w-]+/g),function(b,
c){a.hidden_tabs.push(c)}));for(var l in{confirm_close:1,show_close_button:1})a[l]=t(a[l])},init_close_button:function(){this.options.show_close_button&&c('<a class="nc-modal-dialog-header-close-button" title="'+ncLang.Close+'" />').click(c.proxy(this,"close")).appendTo(this.get_part("header"))},init_buttons:function(){var a=this,b=this.get_part("footer").find("button").off("click.modal_dialog");b.filter("[data-action=close]").on("click.modal_dialog",c.proxy(this,"close"));b.filter("[data-action=submit]").on("click.modal_dialog",
function(){c(this).hasClass("nc--loading")||(nc.process_start("nc.ui.modal_dialog.submit_form()",this),a.submit_form())});b.filter('[data-action="save-draft"]').toggle(n(this)).on("click.modal_dialog",function(a){a.preventDefault();window.autosave&&(c(this).addClass("nc--loading"),autosave.saveAllData(autosave))})},init_tabs:function(){if(!this.are_tabs_initialized){this.are_tabs_initialized=!0;var a=this,b=this.get_part("body").find("[data-tab-caption]");a.dialog_container.toggleClass("nc-modal-dialog-without-tabs",
0===b.length);if(b.length){var d=this.get_part("header_tabs");d.length||(d=c('<div class="nc-modal-dialog-header-tabs"/>').appendTo(this.get_part("header")));var e=d.children("ul"),l=1;e.length||(e=c("<ul/>").appendTo(d));b.addClass("nc-modal-dialog-body-tab").hide().each(function(){var b=c(this).data("tab-id")||"tab"+l++,d=c(this).attr("data-tab-id",b);c("<li>",{"data-tab-id":b,html:d.data("tab-caption")}).appendTo(e).click(function(b){a.change_tab(c(b.target).data("tab-id"))})});c.each(this.options.hidden_tabs,
function(b,c){a.hide_tab(c)});this.change_tab(b.eq(0).data("tab-id"))}}},get_form:function(){var a=this.find("#adminForm");a.length||(a=this.find("form").first());return a},init_forms:function(){InitTransliterate(this.get_part("body"));var a=/(MSIE|Trident)/.test(navigator.userAgent||"")&&/^https/i.test(window.location.href||"")?"javascript:false":"about:blank";var b=c.proxy(this,"process_form_submit_response");this.find("form").each(function(){var d=c(this);d.ajaxForm({beforeSerialize:nc_save_editor_values,
success:b,iframe:!0,iframeSrc:a});var e=d.attr("action");e=p(e,"isNaked",1);e=p(e,"admin_modal",1);d.attr("action",e)})},init_change_tracking:function(){if(this.options.confirm_close){var a=this,b=function(){a.has_unsaved_changes=!0};this.has_unsaved_changes=!1;this.dialog_container.one("change",":input",b).one("input textinput","div[contenteditable]",b)}},submit_form:function(a){this.has_unsaved_changes=!1;a=a?c(a):this.get_form();a.submit()},process_form_submit_response:function(a,b,d,e){nc.process_stop("nc.ui.modal_dialog.submit_form()");
return(c.isFunction(this.options.on_submit_response)?this.options.on_submit_response:this.default_form_submit_response_handler).call(this,a,b,d,e)},default_form_submit_response_handler:function(a,b,d,e){if(b=nc_check_error(a))this.show_error(b);else{b=e.find("input[name=cc]").val()||e.find("input[name=infoblock_id]").val();e=window.location;d=(d=/^NewHiddenURL=(.+?)$/m.exec(a))?c.trim(d[1]):null;var l=/^SetLocationHash=(.*?)$/m.exec(a);l&&(e.hash=l[1]);/^ReloadPage=1$/m.test(a)?d&&!/\.php/.test(e.pathname)?
((a=/\/([^\/]+)$/.exec(e.pathname))&&(d+=a[1]),e.pathname=d):e.reload(!0):/^ReloadPageIfActionIsFull=1$/m.test(a)&&"full"===$nc("#nc_page").data("action")?e.reload(!0):b&&nc_update_admin_mode_infoblock(b,c.proxy(this,"destroy"))}},show_error:function(a){var b=this.get_part("footer"),d=c("<div class='nc-alert nc--red' />").append(c("<div class='nc-alert-close nc-icon-s nc--remove'></div>").click(function(){d.remove()})).append("<i class='nc-icon-l nc--status-error'></i>").append(a).appendTo(b)},change_tab:function(a){var b=
k(a),d=this.get_option("on_tab_change");this.get_part("header_tabs").find("li").removeClass("nc--active").filter(b).addClass("nc--active").show();this.get_part("body_tabs").hide().filter(b).show();d.hasOwnProperty(a)&&c.each(d[a],function(a,b){b()});return this},hide_tab:function(a){a=k(a);this.get_part("header_tabs").find("li"+a).hide();this.get_part("body_tabs").filter(a).hide();return this},show_tab:function(a){a=k(a);this.get_part("header_tabs").find("li"+a).show();this.get_part("body_tabs").filter(a).show();
return this},get_tab:function(a){return this.get_part("body").find(k(a))},get_current_tab:function(){var a=this.get_part("body_tabs");return a.length?a.filter(":visible"):this.get_part("body")},hide_header_tabs:function(){this.dialog_container.addClass("nc-modal-dialog-without-tabs");return this},show_header_tabs:function(){this.dialog_container.removeClass("nc-modal-dialog-without-tabs");return this},open:function(){f&&(c("#simplemodal-container").attr("id",m()).data({modal_d:c.modal.impl.d,modal_o:c.modal.impl.o}),
c("#simplemodal-overlay").attr("id",""),c.modal.impl.d={});f++;this.index=f;this.dialog_container?this.when_ready_to_open():this.create().done(c.proxy(this,"when_ready_to_open"));return this},when_ready_to_open:function(){c.modal(this.dialog_container,{onOpen:null,onShow:c.proxy(this,"on_show"),onClose:q,autoPosition:!1,persist:this.options.persist,closeHTML:"",zIndex:1E4+10*f,focus:this.options.focus});c("#simplemodal-container").data("modal_dialog",this);c(window).off("keydown.modal_dialog").on("keydown.modal_dialog",
r);this.is_open=!0;this.init_change_tracking();this.resize()},on_show:function(){this.options.on_show(this);n(this)&&InitAutosave("adminForm")},close:function(){if(this.is_open&&this.is_close_confirmed()){if(this.index===f)c.modal.close();else{var a=c("#"+m(this.index));a.prev(".simplemodal-overlay").remove();a.remove()}this.is_open=!1;this.options.persist||this.destroy();1>=this.index&&c(window).off("keydown.modal_dialog")}},is_close_confirmed:function(){return this.options.confirm_close&&this.has_unsaved_changes?
confirm(window.TEXT_SAVE||"\u0412\u044b\u0439\u0442\u0438 \u0431\u0435\u0437 \u0441\u043e\u0445\u0440\u0430\u043d\u0435\u043d\u0438\u044f?"):!0},resize:function(){if(!this.is_open)return this;var a=c(window.top.window),b=this.options,d=a.width(),e=window.innerHeight||window.clientHeight||a.height(),g=e-200,f=b.width||d-200,h=b.height||g,k="auto"===h;a=this.dialog_container.closest(".simplemodal-container");var m=Math.ceil;f>b.max_width&&(f=b.max_width);!b.width&&f<b.min_width&&(f=b.min_width);a.css({width:f+
"px",height:h+(k?"":"px"),left:m((d-f)/2-10)+"px",top:"120px"}).find(".simplemodal-wrap").css("overflow","auto");k&&(a.height()>g&&a.css("height",g+"px"),a.css("top",Math.max(m((e-a.height())/2),100)+"px"));d=this.get_part("body").find(".nc--fill");d.length&&d.css("height",a.height()+"px");b.on_resize();return this},destroy:function(){this.close();window.CKEDITOR&&this.dialog_container.find("textarea").each(function(){this.name in CKEDITOR.instances&&CKEDITOR.remove(CKEDITOR.instances[this.name])});
this.dialog_container.remove()},clear_all:function(){for(var a in this.parts)this.clear(a)},clear_part:function(a){this.get_part(a).empty()},find:function(a){this.dialog_container||this.create();return this.dialog_container.find(a)},get_part:function(a){return this.find(this.parts[a])}};nc.ext("modal_dialog",g,"ui")}})(window);

!function(t,$){var n=0,e=300,a=function(t){var a=t.data("content"),r;if(a){var i=t.data("id");if(i){if(r=$("#"+i),"none"===r.css("display"))return r.fadeIn(e),!0;if(r.length)return!1}var d=20,c={t:"b",b:"t",r:"l",l:"r"},l=t.data("style"),u=t.data("z-index")||1,f=t.data("width"),s=o(t.data("placement"),"right-center"),p=t.offset();r=$(document.createElement("DIV")).addClass("nc-popover"+(l?" nc--"+l:"")).html(a).css({"z-index":u,position:"absolute",display:"none"}),f&&r.width(f),n++,i="nc_popover_"+n,r.attr("id",i),t.attr("data-id",i),$("body").append(r);var v=s[0],h=s[1],b=t.outerWidth(),g=t.outerHeight(),m=r.outerWidth(),y=r.outerHeight();return"r"===v&&(p.left+=b+d),"l"===v&&(p.left-=m+d),"t"===v&&(p.top-=y+d),"b"===v&&(p.top+=g+d),("rc"===s||"lc"===s)&&(p.top-=y/2-g/2),("rb"===s||"lb"===s)&&(p.top-=y-g),("tc"===s||"bc"===s)&&(p.left-=m/2-b/2),("tr"===s||"br"===s)&&(p.left-=m-b),r.addClass("nc--"+c[v]+h).css(p).fadeIn(e),r}},r=function(t){var n=t.data("id");n&&$("#"+n).fadeOut(e)},o=function(t,n){t||(t=n);var e=t.split(/[- .]/);for(var a in e)e[a]=e[a][0];return e=e.join(""),1===e.length&&(e+="c"),e},i=function(t){$(t).each(function(){var t=$(this),n=t.data("trigger")||"click";"load"===n&&(n+=" click"),t.on(n,function(){return a(t)===!1&&r(t),!1}).load(),"mouseover"===n&&t.mouseout(function(){r(t)})})};t.ext("popover",i,"ui")}(nc,nc.$);
!function(t,$){var e=0,i={padding:10},o=function(t,e){var i={},o;for(o in t)i[o]=t[o];for(o in e)i[o]=e[o];return i},a=function(t,e){var i,o=t.padding;if(t.axis)i={left:t.axis[0]-o,top:t.axis[1]-o},i.width=t.axis[2]+2*o,i.height=t.axis[3]+2*o;else if(e){var a=$(e);i=a.offset(),i.top-=o,i.left-=o,i.width=a.outerWidth()+2*o,i.height=a.outerHeight()+2*o}i.position="absolute",i["z-index"]=999,n.ctx.clearRect(i.left,i.top,i.width,i.height);var r=$(document.createElement("div")).css(i);r.data("z-index",t["z-index"]||999);for(var s in t)r.data(s,t[s]);n.$overlay_objet.append(r),n.popovers.push(r)},n={};n.overlay_id=0,n.$overlay_objet=!1,n.$canvas=!1,n.ctx=!1,n.popovers=[],n.init=function(t){this.settings=o(i,t),e++;var a=$("html"),n=$(document.createElement("canvas")),r=n[0].getContext("2d");return this.$canvas=n,this.ctx=r,this.overlay_id="nc_help_overlay_"+e,this.$overlay_objet=$(document.createElement("div")).attr("id",this.overlay_id).hide(),this.$overlay_objet.append(this.$canvas),n.css({position:"absolute",top:0,left:0,"z-index":999}).attr("width",a.outerWidth()).attr("height",a.outerHeight()),r.fillStyle="rgba(0, 0, 0, 0.25)",r.fillRect(0,0,n[0].width,n[0].height),$("body").append(this.$overlay_objet),this},n.add=function(t){return t=o(this.settings,t),t.target?$(t.target).each(function(){a(t,this)}):t.axis&&a(t),this},n.show=function(){return this.$overlay_objet.show(function(){for(var e in n.popovers)t.ui.popover(n.popovers[e])}),this},n.hide=function(){for(var t in n.popovers)$("#"+n.popovers[t].data("id")).hide();return this.$overlay_objet.hide(),this},t.ext("help_overlay",function(t){return n.init(t)},"ui")}(nc,nc.$);


function transliterate(word, isUrl) {

    var tr = {"А": "A", "а": "a", "Б": "B", "б": "b",
        "В": "V", "в": "v", "Г": "G", "г": "g",
        "Д": "D", "д": "d", "Е": "E", "е": "e",
        "Ё": "E", "ё": "e", "Ж": "Zh", "ж": "zh",
        "З": "Z", "з": "z", "И": "I", "и": "i",
        "Й": "Y", "й": "y", "КС": "X", "кс": "x",
        "К": "K", "к": "k", "Л": "L", "л": "l",
        "М": "M", "м": "m", "Н": "N", "н": "n",
        "О": "O", "о": "o", "П": "P", "п": "p",
        "Р": "R", "р": "r", "С": "S", "с": "s",
        "Т": "T", "т": "t", "У": "U", "у": "u",
        "Ф": "F", "ф": "f", "Х": "H", "х": "h",
        "Ц": "Ts", "ц": "ts", "Ч": "Ch", "ч": "ch",
        "Ш": "Sh", "ш": "sh", "Щ": "Sch", "щ": "sch",
        "Ы": "Y", "ы": "y", "Ь": "'", "ь": "'",
        "Э": "E", "э": "e", "Ъ": "'", "ъ": "'",
        "Ю": "Yu", "ю": "yu", "Я": "Ya", "я": "ya"};


    var result = "";

    result = word.split('').map(function(char) {
        return tr[char] || char;
    }).join("");

    if (isUrl == 'yes') {
        result = result
                .toLowerCase() // change everything to lowercase
                .replace(/^\s+|\s+$/g, "") // trim leading and trailing spaces
                .replace(/[_|\s]+/g, "-") // change all spaces and underscores to a hyphen
                .replace(/[^a-z\u0400-\u04FF0-9-]+/g, "") // remove all non-cyrillic, non-numeric characters except the hyphen
                .replace(/[-]+/g, "-") // replace multiple instances of the hyphen with a single instance
                .replace(/^-+|-+$/g, "") // trim leading and trailing hyphens
                .replace(/[-]+/g, "-")
    }
    return result;
}

function InitTransliterate(target) {
    $nc("[data-type='transliterate']", target || 'body').each(function() {
        var targetInput = $nc(this),
            button = $nc('<span class="nc-transliterate-action nc-icon nc--refresh" title="Транслитерация названия"></span>');
        button.click(function() {
            var sourceValue = $nc('[name="' + targetInput.data('from') + '"]', target || 'body').val();
            targetInput.val(transliterate(sourceValue, targetInput.data('isUrl')));
        }).insertAfter(targetInput);
    });
}
$nc(function() { InitTransliterate('body'); });
/**
 * Original PHP Engine: http://rmcreative.ru/blog/post/tipograf
 * JS Lite Version
 */

//typographus object
var Typographus_Lite = new Object();
//special characters
Typographus_Lite.sp_chars = {
  nbsp     : '&nbsp;',
  lnowrap  : '<span style="white-space:nowrap">',
  rnowrap  : '</span>',
  lquote   : '«',
  rquote   : '»',
  lquote2  : '„',
  rquote2  : '“',
  mdash    : '—',
  ndash    : '–',
  minus    : '–', // width equals to +, present in every font
  hellip   : '…',
  copy     : '©',
  trade    : '™',
  apos     : '&#39;',   // см. http://fishbowl.pastiche.org/2003/07/01/the_curse_of_apos
  reg      : '®',
  multiply : '&times;',
  frac_12  : '&frac12;',
  frac_14  : '&frac14;',
  frac_34  : '&frac34;',
  plusmn   : '±',
  rarr     : '→',
  larr     : '←',
  rsquo    : '&rsquo;'
};

//safeblocks (as parts of regular expressions)
//ADD YOUR SAFEBLOCKS HERE AS 'start' : 'end' PAIR
Typographus_Lite.safeblocks = {
  '<safeblock>' : '<\\/safeblock>',
  '<pre[^>]*>' : '<\\/pre>',
  '<style[^>]*>' : '<\\/style>',
  '<script[^>]*>' : '<\\/script>',
  '<code[^>]*>' : '<\\/code>',
  '<!--' : '-->',
  '<\\?php' : '\\?>',
  '<drupal6>' : '<\\/drupal6>',
  '<php>' : '<\\/php>',
  '<cpp>' : '<\\/cpp>',
  '<object>' : '<\\/object>',
  '<javascript>' : '<\\/javascript>',
  '<qt>' : '<\\/qt>'
};


Typographus_Lite.safeblock_storage = [];

//safeblock stacking
__stack = function (match) {
  //get length
  var i = Typographus_Lite.safeblock_storage.length;
  //add match
  Typographus_Lite.safeblock_storage[i] = match;
  //return replacement
  return "<" + i + ">";
}

//safeblock processing
Typographus_Lite.remove_safeblocks = function(str) {
  //empty storage
  this.safeblock_storage = [];
  var pattern = '(';
  for (var key in this.safeblocks) {
    pattern += "(" + key + "(.|\\n)*?" + this.safeblocks[key] + ")|";
  }
  pattern += '<[^>]*[\\s][^>]*>)';
  str = str.replace(RegExp(pattern, "gim"), __stack);
  return str;
}

//safeblock returning
Typographus_Lite.return_safeblocks = function(str) {
  for (var i=0; i<this.safeblock_storage.length; i++) {
    var block = "<" + i + ">";
    str = str.replace(block, this.safeblock_storage[i]);
  }
  return str;
}


/**
 *
 *  This function calls typo_text to process string str
 *
 */
Typographus_Lite.process = function(str) {
  str = this.remove_safeblocks(str);
  str = this.typo_text(str);
  str = this.return_safeblocks(str);
  return str;
}


/**
 *
 * This function applies array of rules to string str
 *
 */
Typographus_Lite.apply_rules = function(rules, str) {
  for (var key in rules) {
    var rule = new RegExp(key, "gim"); //with global, case-insensitive, multiline flags
    var newstr = rules[key];
    str = str.replace(rule, newstr);
  }
  return str;
}	


/**
 *
 * The main typographus function
 *
 */
Typographus_Lite.typo_text = function(str) {
  var sym = this.sp_chars;
  var html_tag = '(?:<.*?>)';
  var hellip = '\\.{3,5}';
  var word = '[a-zA-Z_абвгдеёжзийклмнопрстуфхцчшщьыъэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЫЪЭЮЯ0123456789]';
  var phrase_begin = "(?:" + hellip + "|" + word + '|\\n)';
  var phrase_end = '(?:[)!?.:;#*\\\]|$|'+ word + '|' + sym['rquote'] + '|' + sym['rquote2'] + '|&quot;|"|' + sym['hellip'] + '|' + sym['copy'] + '|' + sym['trade'] + '|' + sym['apos'] + '|' + sym['reg'] + '|\\\')';
  var any_quote = "(?:" + sym['lquote'] + "|" + sym['rquote'] + "|" + sym['lquote2'] + "|" + sym['rquote2'] + "|&quot;|\\\")";
  //symbols
  var rules_symbols = {};
  //(c)
  rules_symbols['\\((c|с)\\)'] = sym['copy'];
  //(r)
  rules_symbols['\\(r\\)'] = sym['reg'];
  //tm
  rules_symbols['\\(tm\\)'] = sym['trade'];
  //hellip
  rules_symbols[hellip] = sym['hellip'];
  //+-
  rules_symbols['([^\\+]|^)\\+-'] = '$1' + sym['plusmn'];
  //->
  rules_symbols['([^-]|^)-(>|&gt;)'] = '$1' + sym['rarr'];
  //<-
  rules_symbols['([^<]|^)(<|&lt;)-'] = '$1' + sym['larr'];
  //quotes
  var rules_quotes = {};
  rules_quotes['([^"]\\w+)"(\\w+)"'] = '$1 "$2"';
  rules_quotes['"(\\w+)"(\\w+)'] = '"$1" $2';
  rules_quotes["(" + html_tag + "*?)(" + any_quote + ")(" + html_tag + "*" + phrase_begin + html_tag + "*)"] = '$1' + sym['lquote'] + '$3';
  rules_quotes["(" + html_tag + "*(?:" + phrase_end + "|[0-9]+)" + html_tag + "*)(" + any_quote + ")(" + html_tag + "*" + phrase_end + html_tag + "*|\\s|$$|\\n|[,<-])"] = '$1' + sym['rquote'] + '$3';

  //main rules
  var rules_main = {};
  //fix dashes
  rules_main[' +(?:--?|—|&mdash;)(?=\\s)'] = sym['nbsp'] + sym['mdash'];
  rules_main['^(?:--?|—|&mdash;)(?=\\s)'] = sym['mdash'];
  rules_main['(?:^|\\s+)(?:--?|—|&mdash;)(?=\\s)'] = "\n" + sym['nbsp'] + sym['mdash'];
  //fix digit-dash
  rules_main['(\\d{1,})(-)(?=\\d{1,})'] = '$1' + sym['ndash'];
  //glue percent
  rules_main['([0-9]+)\\s+%'] = '$1%';

  //apply different rules
  str = this.apply_rules(rules_quotes, str);
  str = this.apply_rules(rules_main, str);
  str = this.apply_rules(rules_symbols, str);

  return str;
}

// Resize modal on window.resize
//
// Если у элемента, из которого был создан modal, в качестве data-свойства onResize
// установлена функция [.data('onResize', someFunction)], она будет выполнена при
// событии resize
function nc_register_modal_resize_handler() {
    if (!$nc._resize_modal_event) {
        $nc(window).resize(function () {
            var modal = $nc("#simplemodal-container").not(".simplemodal-container-fixed-size");
            if (modal.length !== 0 && !modal.find(".nc-modal-dialog-body").length) {
                var w = $nc(window).width() - 100 * 2;
                var h = $nc(window).height() - 100 * 2;
                w = w > 1200 ? 1200 : (w < 600 ? 600 : w);

                modal.css({ width: w, height: h });

                var modalResizeHandler = modal.find(".simplemodal-data").data("onResize");
                if (modalResizeHandler && typeof modalResizeHandler === "function") {
                    modalResizeHandler(modal);
                }
            }
        });

        $nc._resize_modal_event = true;
    }
}

function nc_save_editor_values() {
    // в случае удаления nc_form() перенести эту функцию в nc.ui.modal_dialog (?)

    if (typeof CKEDITOR !== "undefined" && CKEDITOR.instances) {
        for (var instance_name in CKEDITOR.instances) {
            var editor = CKEDITOR.instances[instance_name], $textarea = $nc(editor.element.$), value = editor.getData();

            if ($textarea.length) {
                // CKEditor не фильтрует контент, если редактор находится не в режиме
                // WYSIWIG (as of version 4.4.1)
                if (editor.mode !== "wysiwyg" && (!("allowedContent" in editor.config) || editor.config.allowedContent !== true)) {
                    var fragment = CKEDITOR.htmlParser.fragment.fromHtml(value),
                        writer = new CKEDITOR.htmlParser.basicWriter();

                    editor.filter.applyTo(fragment);
                    fragment.writeHtml(writer);
                    value = writer.getHtml();
                }
                $textarea.val(value);
            }
        }
    }

    if (window.FCKeditorAPI) {
        for (fckeditorName in FCKeditorAPI.Instances) {
            var editor = FCKeditorAPI.GetInstance(fckeditorName);
            if (editor.IsDirty()) {
                $nc("#" + fckeditorName).val(editor.GetHTML());
            }
        }
    }

    CMSaveAll();
}

function nc_form(url, backurl, target, modalWindowSize, httpMethod, httpData) {
    var path_re = new RegExp("^\\w+://[^/]+" + NETCAT_PATH + "(add|message)\\.php");
    if (path_re.test(url)) {
        return nc.load_dialog(url);
    }

    if (!target && window.event) {
        target = window.event.target || window.event.srcElement;
    }

    if (!modalWindowSize) {
        modalWindowSize = null;
    }

    nc_register_modal_resize_handler();

    var $target = target ? $nc(target) : false;
    if ($target) {
        if ($target.hasClass("nc--disabled")) {
            return;
        }
        $target.addClass("nc--disabled");
    }

    if (!backurl) {
        backurl = "";
    }

    nc.process_start("nc_form()");

    if (!httpMethod) {
        httpMethod = "GET";
    }

    if (!httpData) {
        httpData = {};
    }

    $nc.ajax({
        "type": httpMethod, "url": url + "&isNaked=1", "data": httpData, "success": function (response) {

            nc.process_stop("nc_form()");
            if ($target) {
                $target.removeClass("nc--disabled");
            }

            nc_remove_content_for_modal();
            $nc("body").append("<div style=\"display: none;\" id=\"nc_form_result\"></div>");
            $nc("#nc_form_result").html(response).modal({
                position: [120, null], onShow: function (dialog) {
                    $nc("#nc_form_result").children().not(".nc_admin_form_menu, .nc_admin_form_body, .nc_admin_form_buttons").hide();

                    var container = dialog.container;

                    if (modalWindowSize) {
                        var currentLeft = parseInt(container.css("left"));
                        var currentWidth = container.width();

                        var currentTop = parseInt(container.css("top"));
                        var currentHeight = container.height();

                        container.css({
                            width: modalWindowSize.width, height: modalWindowSize.height, left: currentLeft + (currentWidth - modalWindowSize.width) / 2, top: currentTop + (currentHeight - modalWindowSize.height) / 2
                        }).addClass("simplemodal-container-fixed-size");
                    } else {
                        container.removeClass("simplemodal-container-fixed-size");
                        $nc(window).resize();
                    }

                    $nc("#nc_form_result #adminForm").append("<input type='hidden' name='nc_token' value='" + nc_token + "' />");
                }, closeHTML: "<a class='modalCloseImg'></a>", onClose: function (e) {
                    if (typeof CKEDITOR !== "undefined" && CKEDITOR.instances) {
                        for (var instance_name in CKEDITOR.instances) {
                            if (!/_edit_inline$/i.test(instance_name)) {
                                CKEDITOR.instances[instance_name].destroy();
                            } else {
                                var $element = $nc("#" + instance_name);
                                var oldValue = $element.attr("data-oldvalue");
                                $element.html(oldValue);
                            }
                        }
                    }
                    $nc.modal.close();
                    if (typeof nc_autosave_use !== "undefined" && nc_autosave_use == 1 && autosave !== null && typeof autosave !== "undefined" && autosave.timeout != 0) {
                        autosave.stopTimer();
                    }
                    $nc(document).unbind("keydown.simplemodal");
                    nc_remove_content_for_modal();
                }
            });

            $nc("#nc_form_result #adminForm").ajaxForm({
                beforeSerialize: nc_save_editor_values,

                // modal layer button submit
                success: function (response, status, event, form) {

                    nc.process_stop("nc_form()");
                    var error = nc_check_error(response);
                    if (error) {
                        var $form_buttons = $nc(".nc_admin_form_buttons");
                        $form_buttons.append("<div id='nc_modal_error' class='nc-alert nc--red' style='position:absolute; z-index:3000; width:" + ($form_buttons.width() - 55) + "px; bottom:70px; text-align:left; line-height:20px '>" + "<div class='simplemodal_error_close'></div>" + "<i class='nc-icon-l nc--status-error'></i>" + error + "</div>");
                        $nc(".simplemodal_error_close").click(function () {
                            $nc("#nc_modal_error").remove();
                        });
                        return false;
                    }

                    // if (response == 'OK') {
                    //     window.location.reload(true);
                    //     return false;
                    // }

                    var cc = form.find("input[name=cc]").val();

                    var loc = window.location, newUrlMatch = (/^NewHiddenURL=(.+?)$/m).exec(response), // в ответе есть строка "NewHiddenUrl=something"
                        newUrl = newUrlMatch ? $nc.trim(newUrlMatch[1]) : null; // новый HiddenURL страницы

                    if ((/^ReloadPage=1$/m).test(response)) { // в ответе есть строка "ReloadPage=1"
                        // не режим "редактирование", изменился путь страницы
                        if (newUrl && !(/\.php/.test(window.location.pathname))) {
                            // сохранить имя страницы, если оно было (изменение свойств раздела со страницы объекта)
                            var pageNameMatch = /\/([^\/]+)$/.exec(loc.pathname);
                            if (pageNameMatch) {
                                newUrl += pageNameMatch[1];
                            }
                            loc.pathname = newUrl;
                        } else {
                            loc.reload(true);
                        }
                        return false;
                    } else {
                        $nc.ajax({
                            "type": "GET", "url": (backurl ? backurl : nc_page_url()) + "&isNaked=1&admin_modal=1&cc_only=" + cc, success: function (response) {
                                nc_update_admin_mode_content(response, null, cc);
                                $nc.modal.close();
                            }
                        });
                    }
                }
            });
            return false;
        }
    });
}

function nc_action_message(url, httpMethod, httpData) {
    var ajax_url = url + "&isNaked=1&posting=1" + "&nc_token=" + nc_token, cc_match = url.match(/\bcc=(\d+)/),
        cc = cc_match ? cc_match[1] : 0;

    if (!httpMethod) {
        httpMethod = "GET";
    }

    if (!httpData) {
        httpData = {};
    }

    $nc.ajax({
        "type": httpMethod, "data": httpData, "url": ajax_url, "success": function (response) {
            response = $nc.trim(response);
            if (response === "deleted") {
                $nc("body", nc_get_current_document()).append("<div id='formAsyncSaveStatus'>Объект помещен в корзину</div>");
                $nc("div#formAsyncSaveStatus", nc_get_current_document()).css({
                    backgroundColor: "#39B54A"
                });
                setTimeout(function () {
                    $nc("div#formAsyncSaveStatus", nc_get_current_document()).remove();
                }, 1000);
            }

            if (response.indexOf("trashbin_disabled") > -1) {

                nc_print_custom_modal();

                $nc("div#nc_cart_confirm_footer button.nc_admin_metro_button").click(function () {
                    $nc.modal.close();
                    nc_action_message(url + "&force_delete=1")
                });

                return null;
            }

            var $status_message = $nc("<div />").html(response).find("#statusMessage");

            if (cc) {
                nc_update_admin_mode_infoblock(cc);
                return;
            }

            $nc.ajax({
                "type": "GET", "url": nc_page_url() + "&isNaked=1", "success": function (response) {
                    response ? nc_update_admin_mode_content(response, $status_message, cc) : nc_page_url(nc_get_back_page_url());
                }
            });
        }
    });
}

function nc_is_frame() {
    return typeof mainView !== "undefined";
}

function nc_has_frame() {
    return "mainView" in top.window && top.window.mainView.oIframe;
}

function nc_get_back_page_url() {
    return NETCAT_PATH + "?" + nc_page_url().match(/sub=[0-9]+/) + (nc_is_frame() ? "&inside_admin=1" : "");
}

function nc_page_url(url) {
    return nc_correct_page_url(url ? nc_get_location().href = url : nc_get_location().href);
}

function nc_correct_page_url(url) {
    url = url.replace(/#.*$/, "");
    return url.indexOf("?") == -1 ? url + "?" : url;
}

function nc_update_admin_mode_infoblock(infoblock_id, callback) {
    var is_full_page = $nc("#nc_page").data('action') === 'full';
    if (is_full_page) {
        location.reload();
        return;
    }

    if (window.nc_partial_load && !/full\.php/.test(location)) {
        try { // при неправильной разметке возможна ошибка
// @todo partial reload for full page blocks
            nc_partial_load(infoblock_id, callback || $nc.noop);
            return;
        } catch (e) {}
    }

    $nc.ajax({
        "type": "GET", "url": nc_page_url() + "&isNaked=1&admin_modal=1&cc_only=" + infoblock_id, success: function (response) {
            nc_update_admin_mode_content(response, null, infoblock_id);
            if ($nc.isFunction(callback)) {
                callback();
            }
        }
    });
}

// используется в шаблонах компонента netcat_page_block_table!
function nc_update_admin_mode_content(content, $status_message, cc) {
    var scope = nc_has_frame() ? top.window.mainView.oIframe.contentDocument : document,
        block_id_selector = "#nc_admin_mode_content" + (cc || ""),
        $nc_admin_mode_content = $nc(block_id_selector, scope),
        new_content_block_by_id = $nc(content).filter(block_id_selector);

    if ($nc_admin_mode_content.length && new_content_block_by_id.length) {
        $nc_admin_mode_content.replaceWith(new_content_block_by_id);
        $nc_admin_mode_content = new_content_block_by_id;
    } else {
        if (!$nc_admin_mode_content.length) {
            $nc_admin_mode_content = $nc("div.nc_admin_mode_content", scope);
        }
        $nc_admin_mode_content.html(content);
    }

    $nc_admin_mode_content.find("LINK[rel=stylesheet]").appendTo($nc("HEAD", scope));

    $nc_admin_mode_content.prev("#statusMessage").remove();

    if (typeof ($status_message) !== "undefined" && $status_message) {
        $nc_admin_mode_content.before($status_message);
    }

    if ($nc.fn.addImageEditing) {
        $nc(".cropable").addImageEditing();
    }
}

function nc_get_current_document() {
    return nc_is_frame() ? mainView.oIframe.contentDocument : document;
}

function nc_get_location() {
    return nc_is_frame() ? mainView.oIframe.contentWindow.location : location;
}

function nc_remove_content_for_modal() {
    $nc("#nc_form_result").remove();
    if (typeof (resize_layout) !== "undefined") {
        resize_layout();
    }
}

function nc_password_change() {
    var $password_change = $nc("#nc_password_change");
    $password_change.modal({
        closeHTML: "", containerId: "nc_small_modal_container", onShow: function () {
            $nc("div.simplemodal-wrap").css({ padding: 0, overflow: "inherit" });
            var $form = $password_change.find("form");
            $nc("#nc_small_modal_container").addClass("nc-shadow-large").css({
                width: $form.width(), height: $form.height()
            });
            $nc(window).resize();
        }
    });

    // $nc('.password_change_simplemodal_container').css({
    //       backgroundColor: 'white',
    // });

    //FIXME: проверка формы изменения пароля перед отправкой
    if (false) {
        var $submit = $password_change.find("button[type=submit]");
        // var button = $nc('div#nc_password_change_footer button.nc_admin_metro_button');
        $submit.unbind();
        $submit.click(function () {
            if ($nc("input[name=Password1]").val() !== $nc("input[name=Password2]").val()) {
                $nc("div#nc_password_change_footer").append("<div id='nc_modal_error' style='position: absolute; z-index: 3000; width: 200px; border: 2px solid red;background-color: white; bottom: 190px; text-align: left; padding: 10px;'>" + "<div class='simplemodal_error_close'></div>" + ncLang.UserPasswordsMismatch + "</div>");
                return false;
            }
            $nc("div#nc_password_change_body form").submit();
        });
    }

    $nc("div#nc_password_change form").ajaxForm({
        success: function () {
            $nc.modal.close();
        }
    });
}

$nc("button.nc_admin_metro_button_cancel").click(function () {
    $nc.modal.close();
});

function nc_check_error(response) {
    var div = document.createElement("div");
    div.innerHTML = response;
    return $nc(div).find("#nc_error").html();
}

$nc(".simplemodal_error_close").click(function () {
    $nc("#nc_modal_error").remove();
});

function CMSaveAll() {
    /* // pre method
    var editors = null;

    if ( nc_is_frame() ) {
        editors = mainView.oIframe.contentWindow.CMEditors;
    } else {
        editors = window.CMEditors;
    }
    if ( typeof(editors) != 'undefined' ) {
        for(var key in editors) {
            editors[key].save();
        }
    }*/

    $nc("textarea.has_codemirror").each(function () {
        $nc(this).data("codemirror").save();
    });
}

function nc_print_custom_modal() {
    $nc("body").append("<div id='nc_cart_confirm' style='display: none;'></div>");

    var cart_confirm = $nc("#nc_cart_confirm");

    cart_confirm.append("<div id='nc_cart_confirm_header'></div>");
    cart_confirm.append("<div id='nc_cart_confirm_body'></div>");
    cart_confirm.append("<div id='nc_cart_confirm_footer'></div>");

    $nc("#nc_cart_confirm_header").append("<div><h2 style='padding: 0px;'>" + ncLang.DropHard + "</h2></div>");
    $nc("#nc_cart_confirm_footer").append("<button type='button' class='nc_admin_metro_button nc-btn nc--blue'>" + ncLang.Drop + "</button>");
    $nc("#nc_cart_confirm_footer").append("<button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'>" + ncLang.Cancel + "</button>");

    cart_confirm.modal({
        closeHTML: "", containerId: "cart_confirm_simplemodal_container", onShow: function () {
            $nc(".simplemodal-wrap").css({
                backgroundColor: "white"
            });
        }, onClose: function () {
            $nc.modal.close();
            $nc("#nc_cart_confirm").remove();
        }
    });

    $nc("div#nc_cart_confirm_footer button.nc_admin_metro_button_cancel").click(function () {
        $nc.modal.close();
    });

    $nc("div#nc_cart_confirm_footer button.nc_admin_metro_button").click(function () {
        if (typeof callback_on_confirm === "function") {
            callback_on_confirm();
            $nc.modal.close();
        }
    });

}


function nc_print_custom_modal_callback(callback) {
    $nc("body").append("<div id='nc_cart_confirm' style='display: none;'></div>");

    var cart_confirm = $nc("#nc_cart_confirm");

    cart_confirm.append("<div id='nc_cart_confirm_header'></div>");
    cart_confirm.append("<div id='nc_cart_confirm_body'></div>");
    cart_confirm.append("<div id='nc_cart_confirm_footer'></div>");

    $nc("#nc_cart_confirm_header").append("<div><h2 style='padding: 0px;'>" + ncLang.DropHard + "</h2></div>");
    $nc("#nc_cart_confirm_footer").append("<button type='button' class='nc_admin_metro_button_cancel nc-btn nc--bordered nc--blue'>" + ncLang.Cancel + "</button>");
    $nc("#nc_cart_confirm_footer").append("<button type='button' class='nc_admin_metro_button nc-btn nc--red nc--bordered nc--right'>" + ncLang.Drop + "</button>");

    cart_confirm.modal({
        closeHTML: "", containerId: "cart_confirm_simplemodal_container", onShow: function () {
            $nc(".simplemodal-wrap").css({
                backgroundColor: "white"
            });
        }, onClose: function () {
            $nc.modal.close();
            $nc("#nc_cart_confirm").remove();
        }
    });

    $nc("div#nc_cart_confirm_footer button.nc_admin_metro_button_cancel").click(function () {
        $nc.modal.close();
    });

    $nc("div#nc_cart_confirm_footer button.nc_admin_metro_button").click(function () {
        if (typeof callback === "function") {
            callback();
            $nc.modal.close();
        }
    });
}

function prepare_message_form() {
    $nc(function () {
        $nc("#adminForm").wrapInner("<div class=\"nc_admin_form_main\">");
        $nc("#adminForm").append($nc("#nc_seo_append").html());
        $nc("#adminForm").append("<input type=\"hidden\" name=\"isNaked\" value=\"1\" />");
        $nc("#nc_seo_append").remove();
    });

    //var nc_admin_form_values = $nc('#adminForm').serialize();

    $nc("#nc_show_main").click(function () {
        $nc(".nc_admin_form_main").show();
        $nc(".nc_admin_form_seo").hide();
    });

    $nc("#nc_show_seo").click(function () {
        $nc(".nc_admin_form_main").hide();
        $nc(".nc_admin_form_seo").show();
    });

    $nc("#nc_object_slider_menu li").click(function () {
        $nc("#nc_object_slider_menu li").removeClass("button_on");
        $nc(this).addClass("button_on");
    });

    $nc(".nc_admin_metro_button_cancel").click(function () {
        $nc.modal.close();
    });

    $nc(".nc_admin_metro_button").click(function () {
        if ($nc(this).hasClass("nc--loading")) {
            return;
        }
        nc.process_start("nc_form()", this);
        $nc("#adminForm").submit();
    });
    InitTransliterate($nc("#adminForm"));
}

function nc_typo_field(field) {
    var string;
    if (typeof CKEDITOR !== "undefined" && CKEDITOR.instances && typeof (CKEDITOR.instances[field]) !== "undefined") {
        string = CKEDITOR.instances[field].getData();
        string = Typographus_Lite.process(string);
        CKEDITOR.instances[field].setData(string);
    } else if (typeof FCKeditorAPI !== "undefined" && FCKeditorAPI.Instances && typeof (FCKeditorAPI.Instances[field]) !== "undefined") {
        var editor = FCKeditorAPI.GetInstance(field);
        string = editor.GetHTML();
        string = Typographus_Lite.process(string);
        editor.SetHTML(string);
    } else {
        var $textarea = $nc("TEXTAREA[name=" + field + "]");
        string = $textarea.val();
        string = Typographus_Lite.process(string);
        $textarea.val(string);
    }
}

function nc_infoblock_controller_request(el, action, params) {
    return $nc.post(NETCAT_PATH + "action.php", $nc.extend({
        ctrl: "admin.infoblock", action: action, infoblock_id: $nc(el).closest(".nc-infoblock-toolbar").data("infoblockId")
    }, params));
}

function nc_infoblock_toggle(el) {
    nc_infoblock_controller_request(el, "toggle")
        .success(function (response) {
            if (response === "OK") {
                $nc(el).closest(".nc-infoblock-toolbar").toggleClass("nc--disabled");
            } else {
                // todo: request: process errors
                alert(response);
            }
        });

    return false;
}

function nc_infoblock_place_before(el, other_infoblock_id) {
    return nc_infoblock_change_order(el, "before", other_infoblock_id);
}

function nc_infoblock_place_after(el, other_infoblock_id) {
    return nc_infoblock_change_order(el, "after", other_infoblock_id);
}

function nc_infoblock_change_order(el, position, other_infoblock_id) {
    nc_infoblock_controller_request(el, "change_order", {
        position: position, other_infoblock_id: other_infoblock_id
    })
        .success(function (response) {
            if (response === "OK") {
                window.location.hash = el.href.split("#")[1];
                window.location.reload(true);
            } else {
                // todo: request: process errors
                alert(response);
            }
        });

    return false;
}

function nc_infoblock_set_template(subdivision_id, infoblock_id, template_id) {
    nc_infoblock_controller_request(null, "set_component_template", {
        subdivision_id: subdivision_id, infoblock_id: infoblock_id, template_id: template_id
    }).success(function () {
        nc_update_admin_mode_infoblock(infoblock_id);
    });
    return false;
}

function nc_infoblock_get_main_axis(button_element) {
    return $nc(button_element).closest(".nc-infoblock-insert").is(".nc--vertical.nc-infoblock-insert-transverse") ? "vertical" : "horizontal";
}

function nc_infoblock_show_add_dialog(button) {
    nc.load_dialog(button.href + "&main_axis=" + nc_infoblock_get_main_axis(button));
    return false;
}

function nc_infoblock_buffer_get_id() {
    return $nc.cookie("nc_admin_buffer_infoblock_id");
}

function nc_infoblock_buffer_get_mode() {
    return $nc.cookie("nc_admin_buffer_infoblock_mode");
}

function nc_infoblock_buffer_update_page() {
    var infoblock_id = nc_infoblock_buffer_get_id();
    $nc("body").toggleClass("nc-page-buffer-has-infoblock", !!infoblock_id);
    $nc(".nc--in-buffer").removeClass("nc--in-buffer nc--in-buffer-cut nc--in-buffer-copy");
    if (infoblock_id) {
        $nc(".tpl-block-" + infoblock_id + ", .tpl-container-" + infoblock_id)
            .addClass("nc--in-buffer nc--in-buffer-" + nc_infoblock_buffer_get_mode());
    }
}

$nc(nc_infoblock_buffer_update_page);
$nc(window).on("focus", nc_infoblock_buffer_update_page);


function nc_infoblock_buffer_add(infoblock_id, cut) {
    $nc.cookie("nc_admin_buffer_infoblock_id", infoblock_id);
    $nc.cookie("nc_admin_buffer_infoblock_mode", cut ? "cut" : "copy");
    nc_infoblock_buffer_update_page();
}

function nc_infoblock_buffer_paste(paste_button) {
    var infoblock_id = nc_infoblock_buffer_get_id(), mode = nc_infoblock_buffer_get_mode(),
        controller_link = paste_button.href;
    if (!infoblock_id) {
        return false;
    }

    $nc.ajax({
        method: "POST", url: NETCAT_PATH + "action.php", data: controller_link.substr(controller_link.indexOf("?") + 1) + "&paste_mode=" + mode + "&pasted_infoblock_id=" + infoblock_id + "&main_axis=" + nc_infoblock_get_main_axis(paste_button), success: function (response) {
            $nc.cookie("nc_admin_buffer_infoblock_id", "");
            $nc.cookie("nc_admin_buffer_infoblock_mode", "");
            nc_infoblock_buffer_update_page();
            if (response === "OK") {
                location.reload();
            } else if (response === "ERROR: infoblock disallow move") {
                nc.load_dialog(NETCAT_PATH + "action.php" + "?ctrl=admin.infoblock&action=show_error_infoblock&type_action=cut");
            } else if (response) {
                // todo: request: process errors
                alert(response);
            }
        }
    });
    return false;
}

function nc_init_toolbar_dropdowns() {
    // dropdown inside nc-toolbar: open on click, close on mouseleave or click inside the dropdown
    var event_ns = ".nc_toolbar_dropdown", toolbar_class = ".nc6-toolbar", close_timeout_id,
        clear_close_timeout = function () {
            clearTimeout(close_timeout_id);
        };

    $nc("body").on("click" + event_ns, toolbar_class + " .nc--dropdown", function (e) {
        e.preventDefault();
        var el = $nc(this), close = function () {
            el.removeClass("nc--clicked");
            el.closest(".nc6-toolbar").removeClass("nc--with-visible-dropdown");
            clear_close_timeout();
        };

        if (el.hasClass("nc--clicked")) {
            close();
        } else {
            $nc(".nc--clicked").removeClass("nc--clicked");
            clearTimeout(close_timeout_id);

            el.addClass("nc--clicked")
                .off(event_ns)
                .on("mouseenter" + event_ns, clear_close_timeout)
                .on("mouseleave" + event_ns, function () {
                    close_timeout_id = setTimeout(close, 1000);
                });
            el.closest(".nc6-toolbar").addClass("nc--with-visible-dropdown");

            // проверяем, чтобы выпадающее меню не попадало за пределы экрана по горизонтали
            var body_width = $nc("body").outerWidth(true), dropdown = el.children("ul").css("transform", ""),
                dropdown_left = dropdown.offset().left, overflow = body_width - dropdown_left - dropdown.outerWidth();
            if (dropdown_left < 0) {
                dropdown.css("transform", "translateX(" + Math.ceil(-dropdown_left + 5) + "px)");
            } else if (overflow < 0) {
                dropdown.css("transform", "translateX(" + Math.ceil(overflow) + "px)");
            }

            // проверяем, чтобы второй уровень не попадал за пределы экрана
            el.find("li.nc--dropdown")
                .off(event_ns)
                .on("mouseenter" + event_ns, function () {
                    var dropdown = $nc(this).find("ul").removeClass("nc--on-left");
                    if ($nc(window).width() < (dropdown.offset().left + dropdown.outerWidth())) {
                        dropdown.addClass("nc--on-left");
                    }
                });
        }
    });
}

$nc(nc_init_toolbar_dropdowns);

/**
 *
 */
function nc_editable_image_init(c) {
    c = $nc(c);
    var file_input = c.find("input[type=file]");
    file_input.change(nc_editable_image_upload);
    c.find(".nc-editable-image-remove").click(nc_editable_image_remove);
    c.parents("a").prop("href", "#____"); // не получилось остановить переход по ссылке в FF
    c.find("form").mouseover(function () {
        if (nc_should_show_controls(c)) {
            file_input.prop('disabled', false);
            c.addClass("nc--hover");
        } else {
            file_input.prop('disabled', true);
        }
    });
    c.mouseleave(function () {
        c.removeClass("nc--hover");
    });
}

/**
 * Удаление изображения при in-place редактировании
 */
function nc_editable_image_remove(event) {
    event.stopPropagation();
    var c = $nc(event.target).closest(".nc-editable-image-container").addClass("nc--empty"), form = c.find("form");
    c.find("img:not(.icon)").prop("src", nc_edit_no_image);
    form.find("input[name^=f_KILL]").val(1);
    nc.process_start("nc_editable_image_remove");

    function done() {
        nc.process_stop("nc_editable_image_remove");
    }

    form.ajaxSubmit({ success: done, error: done });
}

/**
 * Замена изображения при in-place редактировании
 */
function nc_editable_image_upload(event) {
    var input = $nc(event.target), form = input.closest("form"), image = form.find("img"),
        image_source = image.data("source"), cc = form.find("input[name=cc]").val();

    image.css("opacity", 0.2);
    form.closest(".nc-editable-image-container").removeClass("nc--empty");
    nc.process_start("nc_editable_image_upload");

    function preload_image(src, callback) {
        if (src) {
            var image_loader = $nc("<img/>", { src: src })
                .css({
                    display: "block", position: "absolute", top: 0, left: -5000
                })
                .appendTo("body")
                .on("load error", function () {
                    image_loader.remove();
                    callback();
                });
        } else {
            callback();
        }
    }

    function done() {
        $nc.ajax({
            "type": "GET", "url": nc_page_url() + "&isNaked=1&admin_modal=1&cc_only=" + cc, success: function (response) {
                // preload image to prevent visible height jerk
                response = $nc(response);
                var new_image = response.find("img[data-source=\"" + image_source + "\"]").attr("src");
                preload_image(new_image, function () {
                    nc_update_admin_mode_content(response, null, cc);
                    nc.process_stop("nc_editable_image_upload");
                });
                $nc(document).trigger('ncContentUpdate');
            }
        });
    }

    if (input.is(":file")) {
        form.ajaxSubmit({ success: done, error: done });
    } else {
        done();
    }

    return false;
}

/**
 * Включен режим «дизайна»?
 * @returns {Boolean}
 */
function nc_is_design_mode() {
    return $nc('body').hasClass('nc--mode-edit-design');
}

/**
 * Нужно ли показывать элементы управления? (Если режим дизайна — для всех элементов, иначе только для элементов
 * внутри контентной области.)
 * @param $element
 * @returns {Boolean}
 */
function nc_should_show_controls($element) {
    // Если не включен режим «редактирования дизайна», то элементы управления
    // показываются только для основной контентной области:
    return nc_is_design_mode() || $element.parent().closest('.tpl-container-main').length;
}

/**
 * Определение направления блоков в контейнере и вида тулбара
 */
$nc(function () {
    // инициализация только в режиме редактирования
    if (!$nc("#nc_page").length) {
        return;
    }

    var body = $nc("body");
    var overlay = $nc("<div class=\"nc-page-overlay\">" + "<div class=\"nc-page-overlay-top\"></div>" + "<div class=\"nc-page-overlay-left\"></div>" + "<div class=\"nc-page-overlay-right\"></div>" + "<div class=\"nc-page-overlay-bottom\"></div>" + "</div>").appendTo(body);

    // Расстояние между тулбарами после их автоматического смещения в случае наложения друг на друга
    var toolbar_spacing = 4;

    // Высота, меньше которой тулбары инфоблока и объекта будет выводиться в строку
    // Если высота меньше, будет добавлен класс nc--is-short, иначе — nc--is-tall
    var undersized_height = 60;
    // Ширина блока, меньше которой кнопки добавления блоков не будут раздвигаться
    // Если ширина меньше, будет добавлен класс nc--is-narrow, иначе — nc--is-wide
    // (название класса nc--wide тут использовать нельзя, т.к. этот модификатор устанавливает width: 100%)
    var undersized_width = 60;

    // Установка класса nc--scroll-top у body для того, чтобы первые тулбары были над навбаром
    var scroll_at_top_threshold = 3, is_window_scroll_at_top;

    function set_scroll_top_class() {
        if (window.scrollY < scroll_at_top_threshold) {
            body.addClass("nc--scroll-at-top");
            is_window_scroll_at_top = true;
        } else if (is_window_scroll_at_top) {
            body.removeClass("nc--scroll-at-top");
            is_window_scroll_at_top = false;
        }
    }

    set_scroll_top_class();
    $nc(window).on("scroll", set_scroll_top_class);


    function has_row_flex(container) {
        return container.css("display") === "flex" && container.css("flex-direction") === "row";
    }

    function are_children_inline(container) {
        var inline = false;
        container.children().each(function () {
            if (/inline|table-cell/.test($nc(this).css("display"))) {
                inline = true;
                return false;
            }
        });
        return inline;
    }

    var stacking_context_properties = ["transform", "filter", "perspective"];

    function has_stacking_context(element) {
        var position = element.css("position"),
            has_own_stacking_context = /^fixed|sticky|absolute$/.test(position) || element.css("opacity") < 1;
        if (!has_own_stacking_context) {
            for (var i = 0; i < stacking_context_properties.length; i++) {
                if (element.css(stacking_context_properties[i]) !== "none") {
                    has_own_stacking_context = true;
                    break;
                }
            }
        }
        // ↑ тут проверяются не все свойства; может понадобиться добавить другие проверки для будущих миксинов
        // (https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Positioning/Understanding_z_index/The_stacking_context)
        return has_own_stacking_context;
    }

    function translate(element, x, y) {
        element.css("transform", "translate(" + (x ? x + "px" : 0) + ", " + (y ? y + "px" : 0) + ")")
        //.data({ translateX: x || 0, translateY: y || 0 });
    }

    function shift_to_avoid_collision(axis, stationary_toolbar, moved_toolbar, move_after_even_when_not_colliding) {
        translate(moved_toolbar, 0, 0);

        var d1_offset = stationary_toolbar.offset();
        if (!d1_offset) {
            return false;
        }
        var d1_height = stationary_toolbar.outerHeight(false);
        var d1_width = stationary_toolbar.outerWidth(false);
        var d1_distance_from_top = d1_offset.top + d1_height;
        var d1_distance_from_left = d1_offset.left + d1_width;

        var d2_offset = moved_toolbar.offset();
        if (!d2_offset) {
            return false;
        }
        var d2_height = moved_toolbar.outerHeight(false);
        var d2_width = moved_toolbar.outerWidth(false);
        var d2_distance_from_top = d2_offset.top + d2_height;
        var d2_distance_from_left = d2_offset.left + d2_width;

        if (move_after_even_when_not_colliding) {
            d1_offset[axis === "x" ? "left" : "top"] = 0;
        }

        var not_colliding = (d1_distance_from_top < d2_offset.top || d1_offset.top > d2_distance_from_top || d1_distance_from_left < d2_offset.left || d1_offset.left > d2_distance_from_left);

        if (!not_colliding) {
            var x, y;

            if (axis === "x") {
                x = Math.ceil(d1_distance_from_left - d2_offset.left) + toolbar_spacing;
            } else if (axis === "y") {
                y = Math.ceil(d1_distance_from_top - d2_offset.top) + toolbar_spacing;
            } else {
                throw "Wrong axis";
            }

            translate(moved_toolbar, x, y);

            return true;
        }

        return false;
    }

    function update_container_toolbar_size(container, toolbar) {
        toolbar.removeClass("nc--compact");
        var toolbar_overflown = toolbar.width() > container.width() / 2;
        toolbar.toggleClass("nc--compact", toolbar_overflown);
    }

    function update_toolbar_size(container, toolbar, max_width_ratio) {
        toolbar.removeClass("nc--compact");
        //                                                    дополнительный пиксель — рамка блока ↓
        var container_unavailable_width = max_width_ratio ? container.width() / max_width_ratio : -1,
            toolbar_overflown = toolbar.length > 0 && toolbar.offset().left < container.offset().left + container_unavailable_width;
        toolbar.toggleClass("nc--compact", toolbar_overflown);
    }

    function get_container_toolbar(container) {
        return $nc(container).children(".nc-infoblock-toolbar");  // sic: children, CSS class
    }

    function distribute_container_toolbars(container) {
        var all_parent_containers = nc_is_design_mode()
                ? container.parents(".nc-container")
                : container.parentsUntil(".tpl-container-main", ".nc-container");
        if (all_parent_containers.length) {
            all_parent_containers = all_parent_containers.get().reverse();
            all_parent_containers.push(container);
            var outer = all_parent_containers.shift(), inner;
            translate(get_container_toolbar(outer), 0, 0);
            while (inner = all_parent_containers.shift()) {
                shift_to_avoid_collision("x", get_container_toolbar(outer), get_container_toolbar(inner), true);
                outer = inner;
            }
        }
    }

    function adjust_insert_toolbar_margins(container, insert_toolbars) {
        var top = parseFloat(container.css("margin-top")), right = parseFloat(container.css("margin-right")),
            bottom = parseFloat(container.css("margin-bottom")), left = parseFloat(container.css("margin-left"));

        insert_toolbars.css({
            "margin-top": (-1 * top) + "px", "margin-right": (-1 * right) + "px", "margin-bottom": (-1 * bottom) + "px", "margin-left": (-1 * left) + "px",
        });

        insert_toolbars.each(function () {
            var insert_toolbar = $nc(this);
            if (insert_toolbar.is(".nc--vertical")) {
                insert_toolbar.css("height", "calc(100% + " + (top + bottom) + "px)");
            } else {
                insert_toolbar.css("width", "calc(100% + " + (left + right) + "px)");
            }
        });
    }

    function show_inside(toolbar) {
        toolbar.filter(":not(.nc--outside)").addClass("nc--half nc--inside");
    }

    function show_outside(toolbar) {
        toolbar.filter(":not(.nc--inside)").addClass("nc--half nc--outside");
    }

    function adjust_blocks_insert_toolbars(blocks, is_vertical) {
        blocks.each(function (i) {
            var block = $nc(this),
                insert_toolbars = block.children(".nc-infoblock-insert:not(.nc-infoblock-insert-first)");

            // направление кнопок добавления
            insert_toolbars.each(function () {
                var toolbar = $nc(this);
                if (toolbar.is(".nc-infoblock-insert-transverse")) {
                    toolbar.toggleClass("nc--vertical", !is_vertical)
                        .toggleClass("nc--horizontal", is_vertical);
                    show_inside(toolbar);
                } else {
                    toolbar.toggleClass("nc--vertical", is_vertical)
                        .toggleClass("nc--horizontal", !is_vertical);
                }
            });

            adjust_insert_toolbar_margins(block, insert_toolbars);

            // кнопка добавления до первого блока — уменьшенная кнопка внутри контейнера
            if (i === 0) {
                show_inside(insert_toolbars.filter(".nc-infoblock-insert-between.nc-infoblock-insert-before"));
            }
            // кнопка добавления после последнего блока — уменьшенная кнопка внутри контейнера
            if (i === blocks.length - 1) {
                show_inside(insert_toolbars.filter(".nc-infoblock-insert-between.nc-infoblock-insert-after"));
            }
        });
    }

    // Блок, над которым мышь, считается блоком «в фокусе».
    // Когда мышь уходит из блока, блок остаётся «в фокусе» в течение 1 с.
    var focused = null, next_to_focus = null;

    function add_hover_class(element) {
        clearTimeout(element.data("mouseleaveTimer"));
        element
            .removeClass("nc--hover-timeout")
            .addClass("nc--hover")
            .one("mouseleave", remove_hover_class_after_delay);
        if (!focused || !focused.is(".nc--hover")) {
            element.addClass("nc--focus");
            focused = element;
        } else {
            next_to_focus = element;
        }
    }

    function remove_hover_class_after_delay() {
        var element = $nc(this).addClass("nc--hover-timeout");
        element.data("mouseleaveTimer", setTimeout(function () {
            element
                .removeClass("nc--hover nc--hover-timeout nc--focus")
                .parents(".nc-infoblock, .nc-container").removeClass("nc--focus");
            if (next_to_focus) {
                if (next_to_focus.is(".nc--hover")) {
                    next_to_focus
                        .addClass("nc--focus")
                        .parents(".nc-infoblock, .nc-container").addClass("nc--focus");
                    focused = next_to_focus;
                } else {
                    focused = null;
                }
                next_to_focus = null;
            } else {
                focused = null;
            }
        }, 1000));
    }

    // Наведение мыши на контейнер
    $nc(document).on("mouseenter", ".nc-container", function (event) {
        var container = $nc(this);

        if (!nc_should_show_controls(container)) {
            return;
        }

        // направление блоков в контейнере
        var container_toolbar = get_container_toolbar(container),
            is_short = container.outerHeight() < undersized_height,
            is_narrow = container.outerWidth() < undersized_width;

        add_hover_class(container);

        container.toggleClass("nc--is-short", is_short).toggleClass("nc--is-tall", !is_short)
            .toggleClass("nc--is-narrow", is_narrow).toggleClass("nc--is-wide", !is_narrow);

        // кнопка добавления до и после блока — уменьшенная кнопка вне контейнера
        if (!container.is(".nc--empty")) {
            show_outside(container.children(".nc-infoblock-insert-between"));
        }

        // вид тулбара — полный или компактный
        update_container_toolbar_size(container, container_toolbar);

        // наложение тулбаров вложенных друг в друга контейнеров? сдвигаем вправо
        if (!container.find(".nc-container").length) {
            // Выполняем распределение тулбаров только когда событие сработало для контейнера с наибольшей вложенностью
            // (событие mouseenter сработает для всех контейнеров начиная снизу [bubbling], это нужно для определения
            // должен ли быть тулбар компактным, поэтому остановить propagation нельзя)
            distribute_container_toolbars(container);
        }

        // вид кнопок добавления у блоков в контейнере
        var list_div = container.children(".tpl-block-list").children(".tpl-block-list-objects"),
            list_is_vertical = has_row_flex(list_div) || are_children_inline(list_div),
            list_div_blocks = list_div.children(".nc-infoblock, .nc-container");
        adjust_blocks_insert_toolbars(list_div_blocks, list_is_vertical);
    });

    // Наведение мыши на инфоблок
    $nc(document).on("mouseenter", ".nc-infoblock", function () {
        var infoblock = $nc(this);

        if (!nc_should_show_controls(infoblock)) {
            return;
        }

        var infoblock_toolbar = infoblock.find(".nc-infoblock-toolbar"),
            is_short = infoblock.outerHeight() < undersized_height,
            is_narrow = infoblock.outerWidth() < undersized_width;

        add_hover_class(infoblock);

        infoblock.toggleClass("nc--is-short", is_short).toggleClass("nc--is-tall", !is_short)
            .toggleClass("nc--is-narrow", is_narrow).toggleClass("nc--is-wide", !is_narrow);

        // вид тулбара — полный или компактный
        if (is_short) {
            // в зависимости от высоты блока
            infoblock_toolbar.addClass("nc--compact");
        } else {
            // в зависимости от [половины] ширины блока
            update_toolbar_size(infoblock, infoblock_toolbar, 2);
        }
    });

    // Наведение мыши на объект
    $nc(document).on("mouseenter", ".nc-infoblock-object", function () {
        var object = $nc(this);

        if (!nc_should_show_controls(object)) {
            return;
        }

        add_hover_class(object);
        // Нужно, чтобы этот обработчик сработал после mouseenter в .nc-infoblock,
        // что бывает не всегда; поэтому откладываем выполнение действий
        setTimeout(function () {
            var object_toolbar = object.find(".nc-object-toolbar"), infoblock = object.closest(".nc-infoblock"),
                infoblock_toolbar = infoblock.find(".nc-infoblock-toolbar"), is_short = infoblock.is(".nc--is-short");

            // наложение тулбара объекта на тулбар инфоблока? сдвигаем вниз
            shift_to_avoid_collision("y", infoblock_toolbar, object_toolbar);

            // вид тулбара — полный или компактный
            if (is_short) {
                object_toolbar
                    .removeClass("nc--compact")
                    .toggleClass("nc--compact", object_toolbar.width() > infoblock.width() / 2);
            } else {
                update_toolbar_size(object, object_toolbar);
            }
        }, 1);
    });

    var navbar_height = $nc(".nc-navbar.nc--fixed").outerHeight();
    // Подсветка блока при наведении мыши на тулбар
    $nc(document).on("mouseenter", ".nc-infoblock-toolbar, .nc-object-toolbar, .nc-infoblock-insert-buttons", function () {
        var toolbar = $nc(this),
            highlighted_parent = toolbar.closest(".nc-infoblock-object, .nc-infoblock, .nc-container, .nc-infoblock-insert"),
            grid_columns = "0 0 1fr", // старый IE не знает 'auto', поэтому 'fr'
            grid_rows = grid_columns, all_parents = toolbar.parents(),
            parent_list_containers = all_parents.filter(".nc-infoblock, .nc-container"), no_overlay = false;

        if (!highlighted_parent) {
            return;
        }

        // для блоков с собственным stacking context оверлей показан не будет
        // (так как перекроет выпадающие меню и т. п.)
        all_parents.each(function () {
            if (has_stacking_context($nc(this))) {
                no_overlay = true;
                return false;
            }
        });

        if (no_overlay) {
            return;
        }

        if (!highlighted_parent.is(".nc-infoblock-insert")) {
            var offset = highlighted_parent.offset();
            grid_columns = Math.ceil(offset.left) + "px " + Math.floor(highlighted_parent.outerWidth()) + "px 1fr";
            grid_rows = Math.ceil(offset.top - navbar_height + 1) + "px " + Math.floor(highlighted_parent.outerHeight()) + "px 1fr";
        }

        overlay.css({
            "grid-template-columns": grid_columns, "grid-template-rows": grid_rows, "-ms-grid-columns": grid_columns, "-ms-grid-rows": grid_rows
        }).addClass("nc--active");

        parent_list_containers.addClass("nc--hover-on-toolbar");

        toolbar.one("mouseleave", function () {
            overlay.removeClass("nc--active");
            parent_list_containers.removeClass("nc--hover-on-toolbar");
        });
    });

    show_outside($nc(".nc-infoblock-insert-between:not(.nc-infoblock-insert-first)").filter(":first, :last"));

    setTimeout(function () {
        next_to_focus = null; // при загрузке страницы срабатывает mouseenter (?!)
    }, 100);
});

async function nc_http_get(url, payload) {
    try {
        return await $nc.get(url, payload)
    } catch (e) {
        return Promise.reject(e)
    }
}

async function nc_http_post(url, payload) {
    try {
        return await $nc.post(url, payload)
    } catch (e) {
        return Promise.reject(e)
    }
}

function nc_print_status(parent, message, type = "success") {
    const status_colors = {
        "error": "red", "info": "blue", "ok": "green", "success": "green"
    }

    $nc(parent).prepend(`<div class="nc-alert nc-alert-js nc--${status_colors[type]}" bis_skin_checked="1">
                                <div class="nc-alert-close nc-icon-s nc--remove" bis_skin_checked="1" onclick="$nc(this).parent().fadeOut(250, function () {
            $nc(this).remove();})"></div>
                                <i class="nc-icon-l nc--status-${type}"></i>
                                <div class="nc-alert-message">${message}</div>
                            </div>`).hide().fadeIn(250)
}

function nc_show_input_loader(element, state = true) {
    state ? $nc(element).addClass("nc--loading") : $nc(element).removeClass("nc--loading");
}

function nc_show_page_loader(state = true) {
    state ? $nc(".nc--page-loader").show() : $nc(".nc--page-loader").hide();
}


/**
 *
 * @param {Object} formElement - HTML-элемент формы.
 * @param {Object} rules - Правила валидации вида:
 * ```
 *   [<attribute>: {
 *     <validator name>: <validator options>
 *   }]
 * ```
 *
 * Для вложенных свойств используется точечная нотация <code>settings.api.key</code>
 *
 * @param {Object} options - Стандартные библиотечные опции для validate.js, и дополнительные опции для валидации:
 *
 * - highlightErrors - подсвечивать рамку над не прошедшими валидацию элементами формы ( по умолчанию true)
 * - showErrors - отображать ошибки под элементами, не прошедшими валидацию ( по умолчанию true)
 * - defaultMessage - текст ошибки для валидации полей с пустыми значениями
 * - fullMessages - выводить ключ из свойства rules в тексте ошибки (по умолчанию false)
 *
 * @returns {Promise<Object>} - возвращает структуру данных вида:
 *  - в случае успеха: <code>[{<attribute>: <value>}]</code>
 *  - в случае ошибки: <code>[{<attribute>: <errors[]>}]</code>.
 *
 * @see https://validatejs.org/
 */
async function nc_form_validate(formElement, rules, options = {}) {
    function resetHighlights() {
        if (options.showErrors) {
            $(".nc-validation-error").remove();
        }

        if (options.highlightErrors) {
            for (const k in rules) {
                formElement.find(`[name='${k}']`).removeClass("nc-validation-error-field")
            }
        }
    }

    function configureSettings() {
        validate.async.options = options;
        validate.validators.presence.options = { message: options.defaultMessage || "Поле обязательное для заполнения" };
        validate.async.options.fullMessages = options.fullMessages || false;
        options.highlightErrors = options.highlightErrors || true;
        options.showErrors = options.showErrors || true;
    }

    configureSettings();
    resetHighlights();

    try {
        const validatedFields = await validate.async(validate.collectFormValues(formElement), rules);

        return Promise.resolve(validatedFields);
    } catch (error) {
        if (options.highlightErrors || options.showErrors) {
            for (let key in error) {
                const errorMessage = error[key];

                if (key.includes(".")) {
                    key = key.split(".")
                    const firstKey = key.shift()
                    key = firstKey + key.map(k => `[${k}]`).join()
                }

                const formInput = formElement.find(`[name='${key}']`)

                if (options.highlightErrors) {
                    formInput.addClass("nc-validation-error-field")
                }

                if (options.showErrors) {
                    const message = $("<div>", { class: "nc-validation-error" }).text(errorMessage).hide();
                    formInput.after(message)
                    message.fadeIn(200)
                }
            }
        }
        return Promise.reject(error);
    }
}

/**
 * Склонение русских слов
 * @param {Number} itemQuantity  Количество товаров
 * @param {String} one           «товар»
 * @param {String} two           «товара»
 * @param {String} many          «товаров»
 * @returns {String}
 */
function nc_plural_form(itemQuantity, one, two, many) {
    itemQuantity = Math.abs(itemQuantity) % 100;
    const underHundred = itemQuantity % 10;
    let result = many;

    if (underHundred > 1 && underHundred < 5) {
        result = two;
    }

    if (underHundred == 1) {
        result = one;
    }

    if (itemQuantity > 10 && itemQuantity < 20) {
        result = many;
    }

    return result;
}

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

/**
 *
 * - Обработчики нажатий в формах внутри основного фрейма.
 * - messageInitDrag
 *
 */

/**
 * Обработка нажатия на элементы с атрибутом data-submit="1":
 * — если установлен атрибут data-confirm-message, будет создан запрос
 *   на подтверждение действия с указанным текстом;
 * — будет создана форма (метод POST) со значениями, перечисленными
 *   в параметре data-post в формате JSON
 */
$nc(function() {
    var buttons = $nc('[data-submit=1]');
    buttons.click(function(e) {
        var button = $nc(this),
            data = button.data('post'),
            message = button.data('confirmMessage');
        if (!message || confirm(message)) {
            var form = $nc('<form/>', {
                method: 'post',
                action: '?'
            }).hide().appendTo('body');
            for (var k in data) {
                form.append($nc('<input/>', {
                    type: 'hidden',
                    name: k,
                    value: data[k]
                }));
            }
            form.submit();
        }
    });
    // prevent middle click on these "buttons":
    $nc(document).on('click', buttons, function(e) {  // other ways do not work (jQ 1.10)
        if ($nc(e.target).closest('a').data('submit')) {
            e.preventDefault();
        }
    });
});

if (typeof formAsyncSaveEnabled === 'undefined') {
    formAsyncSaveEnabled = false;
}

$nc(function() {
    if ('nc_save_keycode' in window) {
        var e = 'keydown.nc_admin_form_save';
        $nc(document.body).on(e, formKeyHandler);
        // remove in Netcat 6 if that handler is still used
        if (window != top) {
            $nc(top.document.body).off(e).on(e, formKeyHandler);
        }
    }
});

/**
 * Form keyhandler (submits on enter, saves with XHR on Ctrl+Shift+S
 * @global {Boolean} formAsyncSaveEnabled
 */
function formKeyHandler(e) {
    //var kEnter = (e.keyCode==13),  // Enter pressed

    // Ctrl + (Shift +) S
    var bAutosave = (typeof nc_autosave_use !== "undefined" && nc_autosave_use == 1 && typeof nc_autosave_type !== "undefined" && nc_autosave_type === 'keyboard' && typeof autosave !== "undefined" && autosave !== null),
        kSave = (
            /* e.shiftKey && */
            e.ctrlKey &&
            e.keyCode == (nc_save_keycode ? Math.round(nc_save_keycode) : 83)
        );

    // SUBMIT on <ENTER>
    /*if (kEnter) {
     if (srcElement.tagName == 'INPUT' && srcElement.type=='text' && !srcElement.getAttribute('nosubmit')) {
     srcElement.form.submit();
     return;
     }
     else {
     return;
     }
     }*/

    if (!(kSave && (formAsyncSaveEnabled || bAutosave))) {
        return;
    }

    // SAVE on <CTRL+(SHIFT+)S>
    if (bAutosave) {
        autosave.saveAllData(autosave);
    } else {
        // update CodeMirror layers
        CMSaveAll();

        var iframe = false;
        $nc('iframe', parent.document).each(function() {
            if ($nc(this).attr('id') === 'mainViewIframe') {
                iframe = true;
            }
        });

        var $form = $nc(e.target).closest('form');
        if (!$form.length) {
            $form = top.$nc('form', nc_get_current_document());
        }
        if (!$form.length) {
            return;
        }

        formAsyncSave($form.eq(0), 0, 'formSaveStatus(1);');

        // inside_admin
        if (iframe) {
            parent.mainView.chan = 0;
            parent.mainView.displayStar(0);
        }
    }

    var originalEvent = e.originalEvent;
    if (originalEvent.stopPropagation) {
        originalEvent.stopPropagation();
        originalEvent.preventDefault();
    } else {
        try {
            originalEvent.keyCode = 0;
        } catch (exception) {}
        originalEvent.cancelBubble = true;
        originalEvent.returnValue = false;
    }

    return false;
}

/**
 * Form ajax saver
 * @param,String or object
 */
function formAsyncSave(form, statusHandlers, posteval) {
    if (!formAsyncSaveEnabled) {
        return;
    }

    var oForm;

    // object
    if (typeof form === 'object' && form.tagName === 'FORM') {
        oForm = form;
    }
    // get the form by ID
    if (typeof form === 'string') {
        oForm = document.getElementById(form);
    }
    // if it is not clear yet - save the FIRST form
    if (typeof oForm !== 'object') {
        oForm = document.getElementsByTagName("FORM")[0];
    }
    // no form!
    if (typeof oForm !== 'object') {
        return false;
    }

    if (oForm.onsubmit) {
        oForm.onsubmit();
    }

    var $form = $nc(oForm),
        flag = $nc('<input type="hidden" name="NC_HTTP_REQUEST" value="1">').appendTo($form),
        statusCode = {};

    // Эмуляция statusHandlers старого httpRequest, нужно будет убрать,
    // когда старые классы/функции будут убраны везде
    if ($nc.isEmptyObject(statusHandlers)) {
        statusHandlers = {
            '*': 'formSaveStatus(xhr);'
        };
    } else {
        for (var i in statusHandlers) {
            var body = statusHandlers[i].replace(/\bthis\b/, 'xhr');
            statusCode[i] = new Function('xhr', body);
        }
    }

    $form.ajaxSubmit({
        statusCode: statusCode,
        complete: new Function('xhr', statusHandlers['*'])
    });
    flag.remove();

    if (posteval) {
        eval(posteval);
    }
}

/**
 * Показать результат сохранения при помощи XHR
 * @param {Object} xhr   XHR object
 */
function formSaveStatus(xhr) {
    var dst = document.getElementById("formAsyncSaveStatus");
    if (!dst) {
        dst = createElement("DIV", {
            "id": "formAsyncSaveStatus"
        }, document.body);
    }

    dst.style.visibility = 'visible';
    dst.style.opacity = 1;
    dst.style.zIndex = 20000;

    dst.className = 'form_save_in_progress';
    dst.innerHTML = NETCAT_HTTP_REQUEST_SAVING;

    dst.style.top = Math.round(($nc('body').height() - $nc(dst).height()) / 2) + 'px';

    if (xhr.readyState && xhr.readyState > 3) {
        var errorMessage = "";

        var iframe = false;
        $nc('iframe', parent.document).each(function() {
            if ($nc(this).attr('id') === 'mainViewIframe') {
                iframe = true;
            }
        });

        // modal layer update
        if (!iframe) {
            $nc.ajax({
                'type': 'GET',
                'url': nc_page_url() + '&isNaked=1',
                success: function(response) {
                    nc_update_admin_mode_content(response);
                    $nc.modal.close();
                }
            });
        }

        if (xhr.status == "200") {
            var result = {};

            try {
                eval("var result = " + xhr.responseText);
            } catch (e) {
                if (xhr.responseText) {
                    errorMessage = xhr.responseText;
                }
            }

            if (result.error) {
                alert(result.error);
                errorMessage = result.error;
            } else {
                if (typeof(result.ui_config) !== 'undefined' && typeof(parent.mainView) !== 'undefined') {
                    var newSettings = result.ui_config;
                    parent.mainView.setHeader(newSettings.headerText, newSettings.subheaderText);

                    var tree;
                    if (newSettings.treeChanges && (tree = parent.document.getElementById('treeIframe').contentWindow.tree)) {
                        for (var method in newSettings.treeChanges) {
                            if (typeof tree[method] === 'function' && newSettings.treeChanges[method].length) {
                                for (var i = 0; i < newSettings.treeChanges[method].length; i++) {
                                    // call method in the tree
                                    tree[method](newSettings.treeChanges[method][i]);
                                }
                            }
                        }
                    }
                }

                dst.className = 'form_save_ok';
                dst.innerHTML = NETCAT_HTTP_REQUEST_SAVED;
                setTimeout(function() {
                    $nc(dst).remove();
                }, 2500);
            }

            if (result.update_html) {
                if (result.update_html) {
                    for (var selector in result.update_html) {
                        $nc(selector).html(result.update_html[selector]);
                    }
                }
            }

        } else {
            errorMessage = xhr.status + ". " + xhr.statusText;
        }

        if (errorMessage) {
            dst.className = 'form_save_error';
            dst.innerHTML = NETCAT_HTTP_REQUEST_ERROR;
            dst.error = errorMessage;
            setTimeout(function() {
                $nc(dst).remove();
            }, 5000);
        }
    }
}

function showFormSaveError() {
    alert(document.getElementById('formAsyncSaveStatus').error);
}

function loadCustomTplSettings(catalogueId, subdivisionId, templateId, parentSubdivisionId) {
    var is_parent_template = $nc('select[name=Template_ID] option:first').html() === $nc('select[name=Template_ID] option').filter(':selected').html();
    $nc('input[name=is_parent_template]').val(is_parent_template);
    $nc("#customTplSettings").html("");
    $nc("#loadTplWait").show();
    var xhr = new httpRequest;
    xhr.request('GET', top.ADMIN_PATH + 'template/custom_settings.php', {
        catalogue_id: catalogueId,
        sub_id: subdivisionId,
        parent_sub_id: parentSubdivisionId,
        template_id: templateId,
        is_parent_template: is_parent_template
    });
    // synchronous HTML-HTTP-request:
    $nc('#customTplSettings').html('').append(xhr.getResponseText());
    if (templateId != 0) {
        document.getElementById('templateEditLink').onclick = function() {
            var suffix = File_Mode_IDs.indexOf('|' + templateId + '|') != -1 ? '_fs' : '';
            window.open(top.ADMIN_PATH + '#template' + suffix + '.edit(' + templateId + ')', 1)
        };
        $nc("#templateEditLink").removeAttr("disabled");
    }
    $nc(document).trigger("apply-upload");

    $nc("#loadTplWait").hide();
}

/**
 * ?
 * @param classId
 * @see /files/netcat/admin/subdivision/function.inc.php
 */
function loadClassDescription(classId) {
    var loadClassDescription = $nc('#loadClassDescription');
    if (classId && classId != '0') {
        $nc.ajax({
            url: top.ADMIN_PATH + 'class/get_class_description.php',
            method: 'GET',
            data: {
                class_id: classId
            },
            success: function(data) {
                loadClassDescription
                    .html(data);
            },
            error: function(error) {
                console.error(error);
            }

        });
    } else {
        loadClassDescription.empty();
    }
}

/**
 * Подгружает шаблон
 * @param classId
 * @param selectedId
 * @param catalogueId
 * @param is_mirror
 * @param source
 * @see /files/netcat/admin/subdivision/function.inc.php
 */
function loadClassTemplates(classId, selectedId, catalogueId, is_mirror, source) {
    var loadClassTemplates = $nc('#loadClassTemplates');

    if (source == undefined) {
        source = 'class';
    }
    if (classId && classId != '0') {
        $nc.ajax({
            url: top.ADMIN_PATH + source + '/get_class_templates.php',
            method: 'GET',
            data: {
                class_id: classId,
                selected_id: selectedId,
                catalogue_id: catalogueId,
                is_mirror: is_mirror
            },
            success: function(data) {
                loadClassTemplates
                    .html(data);
            },
            error: function(error) {
                console.error(error);
            }
        });
    } else {
        loadClassTemplates.empty();
    }
}

/***
 * Отображать "пользовательские настройки", которые были указаны при создании компонента
 * @param classId Class ID
 * @param infoblockId
 * @see /files/netcat/admin/subdivision/function.inc.php
 */
function loadClassCustomSettings(classId, infoblockId) {
    var loadClassCustomSettings = $nc('#loadClassCustomSettings');
    if (classId && classId != '0') {
        $nc.ajax({
            url: top.ADMIN_PATH + 'class/get_class_custom_settings.php',
            method: 'GET',
            data: {
                class_id: classId,
                infoblock_id: infoblockId || ''
            },
            success: function(data) {
                loadClassCustomSettings
                    .html(data);
            },
            error: function(error) {
                console.error(error);
            }
        });
    } else {
        loadClassCustomSettings.empty();
    }
}

function setInfoblockName(infoblockItem) {
    var ClassName = $nc('input[name="SubClassName"]'),
        EnglishName = $nc('input[name="EnglishName"][data-from="SubClassName"]'),
        toRemove = $nc(infoblockItem).val() + '. ';

    if (!ClassName.val() || (ClassName.attr('data-changed') !== 'yes')) {
        ClassName.val($nc(infoblockItem).text().replace(toRemove, ''));
    }

    if (!EnglishName.val() || (EnglishName.attr('data-changed') !== 'yes')) {
        EnglishName.val(transliterate($nc(infoblockItem).text().replace(toRemove, ''), 'yes'));
    }
}

function inputTextClassName() {
    var optionFirstSelect = 'select[name="Class_ID"] > option:visible:first',
        listInput = [
            'input[name="SubClassName"]',
            'input[name="EnglishName"][data-from="SubClassName"]'
        ],
        textFirst = $nc(optionFirstSelect)
            .text()
            .replace($nc(optionFirstSelect).val() + '.', '')
            .trim();

    listInput.forEach(function(val) {
        $nc(val).on('input', function() {
            var self = $nc(this);
            if (!self.val()) {
                self.attr('data-changed', 'no');
            } else {
                self.attr('data-changed', 'yes');
            }
        });
    });

    if (!$nc(listInput[0]).val() || ($nc(listInput[0]).attr('data-changed') !== 'yes')) {
        $nc(listInput[0]).val(textFirst);
    }

    if (!$nc(listInput[1]).val() || ($nc(listInput[1]).attr('data-changed') !== 'yes')) {
        $nc(listInput[1]).val(transliterate(textFirst, 'yes'));
    }
}

function onchageSubClassType(conditions) {
    if (conditions) {
        $nc("#nc_class_select").hide();
        $nc("#loadClassCustomSettings").hide();
        $nc("#nc_infoblock_select").hide();
        $nc("#nc_mirror_select").show();
        $nc("#loadClassTemplates").html("");
        $nc('.tableComponent').hide();
        $nc('input[name="EnglishName"][data-from="SubClassName"]').val('');
        $nc('input[name="SubClassName"]').val('');
    } else {
        $nc("#nc_class_select").show();
        $nc("#nc_infoblock_select").show();
        $nc("#nc_mirror_select").hide();
        $nc("#loadClassTemplates").html("");
        $nc('.tableComponent').show();
    }
}

function loadSubdivisionAddForm(catalogueId, subId) {
    var oFormDiv;
    if (subId) {
        oFormDiv = document.getElementById('sub-' + subId);
    } else {
        oFormDiv = document.getElementById('site-' + catalogueId);
    }

    if (oFormDiv.innerHTML) {
        oFormDiv.innerHTML = '';
    } else {
        var xhr = new httpRequest;
        xhr.request('GET', top.ADMIN_PATH + 'wizard/subdivision_add_form.php', {
            catalogue_id: catalogueId,
            sub_id: subId
        });
        // synchronous HTML-HTTP-request:
        var oForm = document.createElement("form");
        oForm.id = 'ajaxSubdivisionAdd';
        oForm.name = 'ajaxSubdivisionAdd';
        oForm.innerHTML = xhr.getResponseText();
        oFormDiv.appendChild(oForm);
    }
}

//Subdivision_Name, EnglishName, TemplateID, ClassID
function saveSubdivisionAddForm() {
    var oSubdivisionForm = document.getElementById('ajaxSubdivisionAdd');

    var subdivisionName = oSubdivisionForm.Subdivision_Name.value,
        englishName = oSubdivisionForm.EnglishName.value,
        templateId = oSubdivisionForm.TemplateID.value,
        classId = oSubdivisionForm.ClassID.value,
        catalogueId = oSubdivisionForm.CatalogueID.value,
        subId = oSubdivisionForm.SubdivisionID.value,
        token = oSubdivisionForm.nc_token.value;

    var xhr = new httpRequest;
    xhr.request('GET', top.ADMIN_PATH + 'wizard/subdivision_add.php', {
        subdivision_name: subdivisionName,
        english_name: englishName,
        template_id: templateId,
        class_id: classId,
        catalogue_id: catalogueId,
        sub_id: subId,
        nc_token: token
    });
    // synchronous HTML-HTTP-request:

    var result = xhr.getResponseText();
    if (isNaN(result)) {
        var dst = document.getElementById("formAsyncSaveStatus");
        if (!dst) {
            dst = createElement("DIV", {
                "id": "formAsyncSaveStatus"
            }, document.body);
        }
        dst.style.visibility = 'visible';
        dst.style.opacity = 1;
        dst.className = 'form_save_error';
        dst.innerHTML = result;
        setTimeout("fadeOut('formAsyncSaveStatus')", 5000);
        return;
    }

    var oFormDiv, oInsertBeforeTr;

    if (subId != 0) {
        oFormDiv = document.getElementById('sub-' + subId);
        oInsertBeforeTr = document.getElementById('tr-' + subId);
    } else {
        oFormDiv = document.getElementById('site-' + catalogueId);
        oInsertBeforeTr = document.getElementById('site_tr-' + catalogueId);
    }

    var oTr1 = document.createElement('tr');
    oTr1.id = 'tr-' + result;
    oTr1.setAttribute('parentsub', subId);

    var oTr2 = document.createElement('tr');

    var oTd1 = document.createElement('td');
    oTd1.className = 'name active';

    var oTd2 = document.createElement('td');
    oTd2.className = 'button';

    var oTd3 = document.createElement('td');
    oTd3.colSpan = 2;
    oTd3.style.backgroundColor = '#FFFFFF';

    if (isNaN(parseInt(oInsertBeforeTr.firstChild.style.paddingLeft))) {
        oTd1.style.paddingLeft = 16;
        oTd3.style.padding = '0 0 0 16';
    } else {
        oTd1.style.paddingLeft = parseInt(oInsertBeforeTr.firstChild.style.paddingLeft) + 20;
        oTd3.style.paddingLeft = parseInt(oInsertBeforeTr.firstChild.style.paddingLeft) + 20;
        oTd3.style.paddingRight = 0;
        oTd3.style.paddingTop = 0;
        oTd3.style.paddingBottom = 0;
    }

    var oA1 = document.createElement('a');
    oA1.href = 'index.php?phase=4&SubdivisionID=' + result;
    oA1.innerHTML = subdivisionName;

    var oA2 = document.createElement('a');
    oA2.href = '#';
    oA2.onclick = function() {
        loadSubdivisionAddForm(catalogueId, result);
    };

    var oImg1 = document.createElement('img');
    oImg1.src = ADMIN_PATH + 'images/arrow_sec.gif';
    oImg1.width = '14';
    oImg1.height = '10';
    oImg1.alt = '';
    oImg1.title = '';

    var oImg2 = document.createElement('img');
    oImg2.src = ICON_PATH + 'i_folder_add.gif';
    oImg2.alt = ncLang.addSubsection;
    oImg2.title = ncLang.addSubsection;

    var oSpan = document.createElement('span');
    oSpan.innerHTML = result + '. ';

    oTd1.appendChild(oImg1);
    oTd1.appendChild(oSpan);
    oTd1.appendChild(oA1);

    oA2.appendChild(oImg2);

    oTd2.appendChild(oA2);

    oTr1.appendChild(oTd1);
    oTr1.appendChild(oTd2);

    var oDiv = document.createElement('div');
    oDiv.id = 'sub-' + result;

    oTr2.appendChild(oTd3);
    oTd3.appendChild(oDiv);

    bindEvent(oTr1, 'mouseover', siteMapMouseOver);
    bindEvent(oTr1, 'mouseout', siteMapMouseOut);

    bindEvent(oTr2, 'mouseover', siteMapMouseOver);
    bindEvent(oTr2, 'mouseout', siteMapMouseOut);

    oInsertBeforeTr.parentNode.insertBefore(oTr2, oInsertBeforeTr.nextSibling.nextSibling);
    oInsertBeforeTr.parentNode.insertBefore(oTr1, oInsertBeforeTr.nextSibling.nextSibling);
    oForm.parentNode.removeChild(oForm);
}

/**
 * привязать драг-дроп к s_list_class
 */
function messageInitDrag(messageList, allowChangePriority) {
    if (!messageList) {
        return;
    }

    var current_document = nc_get_current_document();

    for (var classId in messageList) {
        for (var i = 0; i < messageList[classId].length; i++) {
            var messageId = messageList[classId][i];
            var container = current_document.getElementById('message' + classId + '-' + messageId),
                handler = current_document.getElementById('message' + classId + '-' + messageId + '_handler');

            if (!container || !handler || !top.dragManager) {
                continue;
            }

            top.dragManager.addDraggable(handler, container);

            if (allowChangePriority) {
                top.dragManager.addDroppable(container, messageAcceptDrop, messageOnDrop, {
                    name: 'arrowRight',
                    bottom: 2,
                    left: 0
                });
            }

            // убрать selectstart с плашки с ID и кнопками (IE)
            handler.parentNode.onselectstart = top.dragManager.cancelEvent;
        }
    }
}

/**
 *
 */
function messageAcceptDrop(e) {
    var //dragged = top.dragManager.draggedInstance,
        target = top.dragManager.droppedInstance;

    // объект можно бросить на другой объект (если это не родительский) - сменить проритет
    // перемещать только в пределах того же родителя
    if (target.type === 'message' && this.getAttribute('messageParent') === top.dragManager.draggedObject.getAttribute('messageParent')) {
        return true;
    }

    return false;
}

function messageOnDrop(e) {
    var dragged = top.dragManager.draggedInstance,
        target = top.dragManager.droppedInstance,
        xhr = new httpRequest();

    var res = xhr.getJson(top.ADMIN_PATH + 'subdivision/drag_manager_message.php',
        {
            'dragged_type': dragged.type,
            'dragged_class': dragged.typeNum,
            'dragged_id': dragged.id,
            'target_type': target.type,
            'target_class': target.typeNum,
            'target_id': target.id
        });

    // (смена проритета)
    if (res && target.type === 'message') {
        var oParent = top.dragManager.draggedObject.parentNode;

        oParent.removeChild(top.dragManager.draggedObject);
        // если this.nextSibling не определен, то insertBefore вставляет в конец родительского элемента
        oParent.insertBefore(top.dragManager.draggedObject, this.nextSibling);
    }
}

function SendClassPreview(form, oTarget) {
    var oForm;
    // object
    if (typeof form === 'object' && form.tagName === 'FORM') {
        oForm = form;
    }
    // get the form by ID
    if (typeof form === 'string') {
        oForm = document.getElementById(form);
    }
    // if it is not clear yet - save the FIRST form
    if (typeof oForm !== 'object' || oForm == null) {
        oForm = document.getElementsByTagName("FORM")[0];
    }
    // no form!
    if (typeof oForm !== 'object') {
        return false;
    }

    if (typeof oTarget === 'undefined' || oTarget == null) {
        oTarget = '';
    }
    if (typeof oTarget !== 'string') {
        oTarget = oTarget.toString();
    }

    if (isFinite(oForm.ClassID.value)) {
        var old_action = oForm.getAttribute("action");
        var old_target = oForm.getAttribute("target");
        oForm.setAttribute("action", oTarget + "?classPreview=" + oForm.ClassID.value);
        oForm.setAttribute("target", "_blank");
        oForm.submit();
        oForm.setAttribute("action", old_action);
        oForm.setAttribute("target", old_target);
    }
}

function SendTemplatePreview(form, oTarget) {
    var oForm;
    // object
    if (typeof form === 'object' && form.tagName === 'FORM') {
        oForm = form;
    }
    // get the form by ID
    if (typeof form === 'string') {
        oForm = document.getElementById(form);
    }
    // if it is not clear yet - save the FIRST form
    if (typeof oForm !== 'object' || oForm == null) {
        oForm = document.getElementsByTagName("FORM")[0];
    }
    // no form!
    if (typeof oForm !== 'object') {
        return false;
    }

    if (typeof oTarget === 'undefined' || oTarget == null) {
        oTarget = '';
    }
    if (typeof oTarget !== 'string') {
        oTarget = oTarget.toString();
    }

    if (isFinite(oForm.TemplateID.value)) {
        var old_action = oForm.getAttribute("action");
        var old_target = oForm.getAttribute("target");
        oForm.setAttribute("action", oTarget + "?templatePreview=" + oForm.TemplateID.value);
        oForm.setAttribute("target", "_blank");
        oForm.submit();
        oForm.setAttribute("action", old_action);
        oForm.setAttribute("target", old_target);
    }
}

function generateForm(classID, sysTable, act, confirmation) {
    if (!classID || !act) {
        return false;
    }

    var values = [];
    var res, confirmText;
    var url = NETCAT_PATH + 'alter_form.php';
    var needTextArea = document.getElementById(act);

    // выгружаем данные из редактора
    if (typeof $nc(needTextArea).codemirror === 'function') {
        $nc(needTextArea).codemirror('save');
    }

    // если поле не пустое - вызываем диалог
    if (needTextArea.value && !confirmation) {
        var dlgValue = confirm(ncLang["Warn" + act]);

        if (dlgValue) {
            generateForm(classID, sysTable, act, 1);
        }
        return false;
    }

    // предупредить сервер, что данные переданы через Ajax в кодировке utf8
    values["NC_HTTP_REQUEST"] = 1;

    // инициализируем
    var xhr = new httpRequest();

    xhr.request('POST', url, {
        'classID': classID,
        'act': act,
        'systemTableID': sysTable,
        'fs': $nc('input[name=fs]', nc_get_current_document()).val()
    });

    res = xhr.getResponseText();

    needTextArea.value = res;
    if (typeof $nc(needTextArea).codemirror === 'function') {
        $nc(needTextArea).codemirror('setValue');
    }

    return false;
}

function generate_widget_form(widgetclass_id, action, confirm) {
    var textarea = document.getElementById(action);
    var url = NETCAT_PATH + 'admin/widget/index.php?phase=90';

    var xhr = new httpRequest(false);
    xhr.request('POST', url, {
        'Widget_Class_ID': widgetclass_id,
        'action': action
    });
    textarea.value = xhr.getResponseText();
    if (typeof $nc(textarea).codemirror === 'function') {
        $nc(textarea).codemirror('setValue');
    }

    return false;
}

/**
 * Привязать к textarea кнопки изменения размера
 */
function bindTextareaResizeButtons() {
    $nc('TEXTAREA').each(function() {
        var $this = $nc(this);
        if (!$this.prev().is('.resize_block')) {
            $nc('<div class="resize_block"><a class="textarea_shrink nc-label nc--lighten" href="#" >&#x25B2;</a> <a class="textarea_grow nc-label nc--lighten" href="#">&#x25BC;</a></div>').insertBefore($this);
        }
        return true;
    });

    $nc('.resize_block A.textarea_shrink, .resize_block A.textarea_grow').bind('click', function() {
        var $this = $nc(this);
        var $textarea = $this.closest('.resize_block').next();
        var height;
        var heightModifier = $this.hasClass('textarea_shrink') ? -50 : 50;
        if (!$textarea.is('TEXTAREA')) {
            $textarea = $textarea.find('TEXTAREA');
        }

        if ($textarea.is('TEXTAREA')) {
            if ($textarea.hasClass('has_codemirror')) {
                var cmEditor = $textarea.data('codemirror');
                if (cmEditor) {
                    var $scrollElement = $nc(cmEditor.getScrollerElement());
                    height = $scrollElement.height() + heightModifier;
                    if (height >= 100) {
                        cmEditor.setSize(null, height);
                    }
                }
            } else {
                height = $textarea.height() + heightModifier;
                if (height >= 100) {
                    $textarea.height(height);
                }
            }
        }
        return false;
    });
}

/**
 * Блок выбора компонента и шаблона компонента (диалог добавления инфоблока;
 * может использоваться и на других страницах)
 */
function nc_component_select_init(form) {
    var component_select = form.find('select.nc-infoblock-component-select'),
        template_select_div = form.find('.nc-infoblock-template-select'),
        template_select_buttons = form.find('.nc-infoblock-template-list-buttons .nc-btn'),
        custom_settings_div = form.find('.nc-infoblock-template-custom-settings'),
        preview_div = form.find('.nc-infoblock-template-preview'),
        show_all_components_checkbox = form.find('input.nc-infoblock-show-all-components'),
        current_component_id,
        component_filter_input = form.find('.nc-infoblock-component-filter input');

    // выбор первого видимого компонента
    function select_first_component() {
        component_select.find('option').first().prop('selected', true);
        component_select.change();
    }

    // Нажатие ↑↓ в поле фильтра
    function on_component_filter_arrows(keycode) {
        var options = component_select.find('option'),
            selected_index = options.index(component_select.find('option:selected')),
            new_selected_option;

        if (keycode == 38 && selected_index > 0) { // up key
            new_selected_option = -1;
        }
        if (keycode == 40 && selected_index != options.length - 1) { // down key
            new_selected_option = +1;
        }
        if (new_selected_option) {
            options.eq(selected_index + new_selected_option).prop('selected', true);
            component_select.change();
        }
    }

    // Загрузка информации о компоненте
    var last_template_data_request;

    function request_template_data(component_id) {
        if (last_template_data_request) {
            last_template_data_request.abort();
        }

        preview_div.css('background-image', '').find('.nc--loading').show();
        preview_div.find('span').hide();

        last_template_data_request = $nc.getJSON(NETCAT_PATH + 'action.php', {
            ctrl: 'admin.infoblock',
            action: 'get_component_template_settings',
            component_id: component_id
        }, function(result) {
            if (!result || !result.length) {
                return;
            }

            if (!show_all_components_checkbox.is(':checked')) {
                result = $nc.grep(result, function(item) {
                    return item.multiple_mode == '1';
                });
            }

            if (result.length < 2) {
                set_single_template(result[0]);
            } else {
                set_templates(result);
            }
        });

        return last_template_data_request;
    }

    // Обновление данных, когда нет выбора шаблона компонента
    function set_single_template(template_data) {
        template_select_div.html(
            template_data.name +
            '<input type="hidden" name="data[Class_Template_ID]" value="' + template_data.id + '">'
        );
        template_select_buttons.hide();
        set_current_template_data(template_data);
    }

    // Обновление данных о шаблонах компонента
    function set_templates(templates) {
        // <select>
        var select = $nc('<select name="data[Class_Template_ID]" />').change(on_template_select_change);
        $nc.each(templates, function(i, template_data) {
            $nc('<option />')
                .val(template_data.id)
                .html(template_data.name)
                .data('template_data', template_data)
                .appendTo(select);
        });
        template_select_div.empty().append(select);

        // кнопки
        template_select_buttons.show();
        template_select_buttons.eq(0).addClass('nc--disabled');
        template_select_buttons.eq(1).removeClass('nc--disabled');

        // скриншот и настройки
        set_current_template_data(templates[0]);
    }

    // Установка данных шаблона компонента
    function set_current_template_data(template_data) {
        // сохранение существующих настроек шаблона
        var values = {};
        custom_settings_div.find('input,select,textarea').each(function() {
            values[this.name] = $nc(this).val();
        });

        preview_div.find('span').toggle(!template_data.preview);
        if (template_data.preview) {
            preview_div.css('background-image', 'url(' + template_data.preview + ')');
        }
        preview_div.find('.nc--loading').hide();
        custom_settings_div.html(template_data.settings);

        // восстановление настроек шаблона
        for (var k in values) {
            custom_settings_div.find('[name="' + k + '"]').val(values[k]);
        }
    }

    // Событие при изменении шаблона компонента в списке (this == select)
    function on_template_select_change() {
        var options = $nc(this).find('option'),
            selected_option = options.filter(':selected'),
            selected_index = options.index(selected_option);

        set_current_template_data(selected_option.data('template_data'));
        // обновить состояние кнопок
        template_select_buttons.eq(0).toggleClass('nc--disabled', selected_index == 0);
        template_select_buttons.eq(1).toggleClass('nc--disabled', selected_index == options.length - 1);
    }

    // Вспомогательная функция для кнопок «предыдущий/следующий шаблон»
    function set_template_select_index(shift) {
        var select = template_select_div.find('select'),
            options = select.find('option'),
            selected_option = options.filter(':selected'),
            new_index = options.index(selected_option) + shift;
        if (new_index >= 0 && new_index < options.length) {
            options.eq(new_index).prop('selected', true);
            select.change();
        }
    }

    // В IE нельзя спрятать <option> стилями, в Chrome есть отдельные проблемы
    // с этим (не работает option:visible).
    // Для переключения списков компонентов придётся манипулировать DOM’ом
    function update_component_select() {
        var show_all = show_all_components_checkbox.is(':checked'),
            name_filter = component_filter_input.val(),
            selected_option_value = component_select.val();

        if (!component_select.data('all_options')) {
            component_select.data('all_options', component_select.html());
        }

        component_select.html(component_select.data('all_options')).val(selected_option_value);

        if (!show_all || name_filter) {
            var name_regexp = name_filter && name_filter.length ? new RegExp(name_filter, 'i') : null;
            // remove <option>s that do not match criteria
            component_select.find('option').each(function() {
                var option = $nc(this),
                    remove =
                        (!show_all && !option.hasClass('nc--component-multiple')) ||
                        (name_regexp && !name_regexp.test(option.html()));
                if (remove) {
                    option.remove();
                }
            });
            // remove empty <optgroup>s
            component_select.find('optgroup:not(:has(option))').remove();
        }

        if (!component_select.find('option[value="' + selected_option_value + '"]').length) {
            select_first_component();
        }
    }

    // --- Инициализация обработчиков событий ---

    // обработка нажатий в поле фильтра
    component_filter_input.on('keyup', function(e) {
        if (e.which == 38 || e.which == 40) { // up & down
            on_component_filter_arrows(e.which);
        } else {
            update_component_select();
        }
    });

    // Нажатие на × в поле фильтра
    form.find('.nc-infoblock-component-filter .nc--remove').click(function() {
        if (component_filter_input.val() != '') {
            component_filter_input.val('');
            update_component_select();
        }
    });

    // Выбор компонента в списке
    component_select.change(function() {
        var component_id = component_select.val();

        if (current_component_id == component_id) {
            return;
        }

        current_component_id = component_id;

        // Название для инфоблока по умолчанию возьмём из названия компонента
        var infoblock_name = component_select.find('option:selected').html().replace(/^\d+\. /, '');
        form.find('input[name="data[Sub_Class_Name]"]').val(infoblock_name);

        request_template_data(component_id);
    });

    // Кнопка «предыдущий шаблон»
    template_select_buttons.eq(0).click(function() {
        set_template_select_index(-1);
    });

    // Кнопка «следующий шаблон»
    template_select_buttons.eq(1).click(function() {
        set_template_select_index(+1);
    });

    // Чекбокс «показать все»
    show_all_components_checkbox.change(function() {
        update_component_select();
        request_template_data(component_select.val()); // reload to update template list
    });

    // Фильтрация списка компонентов
    update_component_select();

    // Выбор первой позиции (после выполнения прочих действий — например, после открытия диалога)
    setTimeout(select_first_component, 1);
}

function nc_infoblock_on_component_change_init() {
    $nc(function () {
        const event = 'change.nc_component_change';
        $nc("select[name=\'Class_ID\']").off(event).on(event, function () {
            nc_infoblock_on_component_change(this.options[this.selectedIndex], $nc(this).data("catalogue-id"));
        });
    });
}

function nc_infoblock_on_component_change(option, catalogue_id) {
    catalogue_id = catalogue_id || 0;

    if (option) {
        var class_id = option.value;
        loadClassCustomSettings(class_id);
        loadClassDescription(class_id);
        loadClassTemplates(class_id, 0, catalogue_id);
        setInfoblockName(option);
    }
}

function nc_component_reload_options(selected_group) {
    var show_all = $nc('#hide_aux').is(':checked');
    var catalogue_id = $nc('#Class_ID').data('catalogue-id');
    var action = 'subdivision.add';

    if (/^#subclass\.add/.test(parent.location.hash)) {
        action = 'subclass.add';
    }
    var query_string = '?catalogue_id=' + catalogue_id + '&action=' + action;
    $nc.getJSON(ADMIN_PATH + 'class/get_class_list.php' + query_string, {}, function(class_list) {
        var class_groups_select = $nc('select[name=Class_Groups]');
        var class_select = $nc('select[name=Class_ID]');
        class_groups_select.html('');

        for (var group in class_list['groups']) {
            var group_info = class_list['groups'][group];
            var is_skippable_auxiliary_group = group_info['is_auxiliary'] && !group_info['selected'] && !show_all;
            if (is_skippable_auxiliary_group) {
                continue;
            }
            var group_option;

            if (group_info['is_delimiter']) {
                group_option = $nc("<option disabled='disabled' data-delimiter='true'>" + group_info['text'] + "</option>");
            } else {
                var is_preselected = !selected_group && group_info['selected'];
                var is_matched_with_selected_group = selected_group && (group_info['value'] === selected_group);

                group_option = $nc("<option/>")
                    .attr('value', group_info['value'])
                    .attr('class', group_info['is_dummy'] || group_info['is_auxiliary'] ? 'nc-text-grey' : '')
                    .attr('data-property', true)
                    .attr('data-group-name', group_info['name'])
                    .html(group_info['text']);

                if (is_preselected || is_matched_with_selected_group) {
                    group_option.prop('selected', 'selected');
                }
            }

            if (group_option) {
                class_groups_select.append(group_option);
            }
        }

        if (class_groups_select.find('option[data-property="true"]:checked').length === 0) {
            class_groups_select.find('option[data-property="true"]:first').prop('selected', 'selected');
            class_groups_select.val(class_groups_select.find('option[data-property="true"]:first').val());
        }

        class_select.html('');

        class_list['components'].forEach(function(component_info) {
            var target_group = class_groups_select.find('option:checked').attr('data-group-name');
            var is_skippable_auxiliary_class = component_info['is_auxiliary'] && !component_info['selected'] && !show_all;

            if (is_skippable_auxiliary_class || component_info['group'] !== target_group) {
                return;
            }

            var component_option = $nc("<option/>")
                .attr('value', component_info['value'])
                .attr('class', component_info['is_dummy'] || component_info['is_auxiliary'] ? 'nc-text-grey' : '')
                .text(component_info['text']);
            if (component_info['selected']) {
                component_option.prop('selected', 'selected');
            }
            class_select.append(component_option);
        });

        if (class_select.find('option:checked').length === 0) {
            class_select.find('option:first').prop('selected', 'selected');
            class_select.val(class_select.find('option:first').val());
        }

        class_select.change();
        inputTextClassName();
    });
}



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

(function(d){function p(){return new Date(Date.UTC.apply(Date,arguments))}function t(a,b){var c=d(a).data(),f={},h,g=RegExp("^"+b.toLowerCase()+"([A-Z])"),b=RegExp("^"+b.toLowerCase()),e;for(e in c)b.test(e)&&(h=e.replace(g,function(a,b){return b.toLowerCase()}),f[h]=c[e]);return f}function u(a){var b={};if(!l[a]&&(a=a.split("-")[0],!l[a]))return;var c=l[a];d.each(v,function(a,d){d in c&&(b[d]=c[d])});return b}var q=d(window),o=function(a,b){this._process_options(b);this.element=d(a);this.isInline=
!1;this.isInput=this.element.is("input");this.hasInput=(this.component=this.element.is(".date")?this.element.find(".add-on, .btn"):!1)&&this.element.find("input").length;if(this.component&&0===this.component.length)this.component=!1;this.picker=d(k.template);this._buildEvents();this._attachEvents();this.isInline?this.picker.addClass("datepicker-inline").appendTo(this.element):this.picker.addClass("datepicker-dropdown dropdown-menu");this.o.rtl&&(this.picker.addClass("datepicker-rtl"),this.picker.find(".prev i, .next i").toggleClass("icon-arrow-left icon-arrow-right"));
this.viewMode=this.o.startView;this.o.calendarWeeks&&this.picker.find("tfoot th.today").attr("colspan",function(a,b){return parseInt(b)+1});this._allow_update=!1;this.setStartDate(this._o.startDate);this.setEndDate(this._o.endDate);this.setDaysOfWeekDisabled(this.o.daysOfWeekDisabled);this.fillDow();this.fillMonths();this._allow_update=!0;this.update();this.showMode();this.element.val()&&this.setValue();this.isInline&&this.show()};o.prototype={constructor:o,_process_options:function(a){this._o=d.extend({},
this._o,a);var a=this.o=d.extend({},this._o),b=a.language;if(!l[b]&&(b=b.split("-")[0],!l[b]))b=r.language;a.language=b;switch(a.startView){case 2:case "decade":a.startView=2;break;case 1:case "year":a.startView=1;break;default:a.startView=0}switch(a.minViewMode){case 1:case "months":a.minViewMode=1;break;case 2:case "years":a.minViewMode=2;break;default:a.minViewMode=0}a.startView=Math.max(a.startView,a.minViewMode);a.weekStart%=7;a.weekEnd=(a.weekStart+6)%7;b=k.parseFormat(a.format);if(-Infinity!==
a.startDate)a.startDate=a.startDate?a.startDate instanceof Date?this._local_to_utc(this._zero_time(a.startDate)):k.parseDate(a.startDate,b,a.language):-Infinity;if(Infinity!==a.endDate)a.endDate=a.endDate?a.endDate instanceof Date?this._local_to_utc(this._zero_time(a.endDate)):k.parseDate(a.endDate,b,a.language):Infinity;a.daysOfWeekDisabled=a.daysOfWeekDisabled||[];if(!d.isArray(a.daysOfWeekDisabled))a.daysOfWeekDisabled=a.daysOfWeekDisabled.split(/[,\s]*/);a.daysOfWeekDisabled=d.map(a.daysOfWeekDisabled,
function(a){return parseInt(a,10)});var b=(""+a.orientation).toLowerCase().split(/\s+/g),c=a.orientation.toLowerCase(),b=d.grep(b,function(a){return/^auto|left|right|top|bottom$/.test(a)});a.orientation={x:"auto",y:"auto"};if(c&&"auto"!==c)if(1===b.length)switch(b[0]){case "top":case "bottom":a.orientation.y=b[0];break;case "left":case "right":a.orientation.x=b[0]}else c=d.grep(b,function(a){return/^left|right$/.test(a)}),a.orientation.x=c[0]||"auto",c=d.grep(b,function(a){return/^top|bottom$/.test(a)}),
a.orientation.y=c[0]||"auto"},_events:[],_secondaryEvents:[],_applyEvents:function(a){for(var b=0,c,d;b<a.length;b++)c=a[b][0],d=a[b][1],c.on(d)},_unapplyEvents:function(a){for(var b=0,c,d;b<a.length;b++)c=a[b][0],d=a[b][1],c.off(d)},_buildEvents:function(){this.isInput?this._events=[[this.element,{focus:d.proxy(this.show,this),keyup:d.proxy(this.update,this),keydown:d.proxy(this.keydown,this)}]]:this.component&&this.hasInput?this._events=[[this.element.find("input"),{focus:d.proxy(this.show,this),
keyup:d.proxy(this.update,this),keydown:d.proxy(this.keydown,this)}],[this.component,{click:d.proxy(this.show,this)}]]:this.element.is("div")?this.isInline=!0:this._events=[[this.element,{click:d.proxy(this.show,this)}]];this._secondaryEvents=[[this.picker,{click:d.proxy(this.click,this)}],[d(window),{resize:d.proxy(this.place,this)}],[d("body"),{scroll:d.proxy(this.place,this)}],[d(document),{"mousedown touchstart":d.proxy(function(a){!this.element.is(a.target)&&!this.element.find(a.target).length&&
!this.picker.is(a.target)&&!this.picker.find(a.target).length&&this.hide()},this)}]]},_attachEvents:function(){this._detachEvents();this._applyEvents(this._events)},_detachEvents:function(){this._unapplyEvents(this._events)},_attachSecondaryEvents:function(){this._detachSecondaryEvents();this._applyEvents(this._secondaryEvents)},_detachSecondaryEvents:function(){this._unapplyEvents(this._secondaryEvents)},_trigger:function(a,b){var c=b||this.date,f=this._utc_to_local(c);this.element.trigger({type:a,
date:f,format:d.proxy(function(a){return k.formatDate(c,a||this.o.format,this.o.language)},this)})},show:function(a){this.isInline||this.picker.appendTo("body");this.picker.show();this.height=this.component?this.component.outerHeight():this.element.outerHeight();this.place();this._attachSecondaryEvents();a&&a.preventDefault();this._trigger("show")},hide:function(){if(!this.isInline&&this.picker.is(":visible"))this.picker.hide().detach(),this._detachSecondaryEvents(),this.viewMode=this.o.startView,
this.showMode(),this.o.forceParse&&(this.isInput&&this.element.val()||this.hasInput&&this.element.find("input").val())&&this.setValue(),this._trigger("hide")},remove:function(){this.hide();this._detachEvents();this._detachSecondaryEvents();this.picker.remove();delete this.element.data().datepicker;this.isInput||delete this.element.data().date},_utc_to_local:function(a){return new Date(a.getTime()+6E4*a.getTimezoneOffset())},_local_to_utc:function(a){return new Date(a.getTime()-6E4*a.getTimezoneOffset())},
_zero_time:function(a){return new Date(a.getFullYear(),a.getMonth(),a.getDate())},_zero_utc_time:function(a){return new Date(Date.UTC(a.getUTCFullYear(),a.getUTCMonth(),a.getUTCDate()))},getDate:function(){return this._utc_to_local(this.getUTCDate())},getUTCDate:function(){return this.date},isoDateFormat:{separators:["","-","-",""],parts:["yyyy","mm","dd"]},getISODate:function(){return this.element.val()?this.getFormattedDate(this.isoDateFormat):null},setDate:function(a){this.setUTCDate(this._local_to_utc(a))},
setUTCDate:function(a){this.date=a;this.setValue()},setValue:function(){var a=this.getFormattedDate();this.isInput?this.element.val(a).change():this.component&&this.element.find("input").val(a).change()},getFormattedDate:function(a){if(void 0===a)a=this.o.format;return k.formatDate(this.date,a,this.o.language)},setStartDate:function(a){this._process_options({startDate:a});this.update();this.updateNavArrows()},setEndDate:function(a){this._process_options({endDate:a});this.update();this.updateNavArrows()},
setDaysOfWeekDisabled:function(a){this._process_options({daysOfWeekDisabled:a});this.update();this.updateNavArrows()},place:function(){if(!this.isInline){var a=this.picker.outerWidth(),b=this.picker.outerHeight(),c=q.width(),f=q.height(),h=q.scrollTop(),g=parseInt(this.element.parents().filter(function(){return"auto"!=d(this).css("z-index")}).first().css("z-index"))+10,e=this.component?this.component.parent().offset():this.element.offset(),j=this.component?this.component.outerHeight(!0):this.element.outerHeight(!1),
k=this.component?this.component.outerWidth(!0):this.element.outerWidth(!1),i=e.left,l=e.top;this.picker.removeClass("datepicker-orient-top datepicker-orient-bottom datepicker-orient-right datepicker-orient-left");"auto"!==this.o.orientation.x?(this.picker.addClass("datepicker-orient-"+this.o.orientation.x),"right"===this.o.orientation.x&&(i-=a-k)):(this.picker.addClass("datepicker-orient-left"),0>e.left?i-=e.left-10:e.left+a>c&&(i=c-a-10));a=this.o.orientation.y;"auto"===a&&(a=-h+e.top-b,f=h+f-(e.top+
j+b),a=Math.max(a,f)===f?"top":"bottom");this.picker.addClass("datepicker-orient-"+a);l="top"===a?l+j:l-(b+parseInt(this.picker.css("padding-top")));this.picker.css({top:l,left:i,zIndex:g})}},_allow_update:!0,update:function(){if(this._allow_update){var a=new Date(this.date),b,c=!1;arguments&&arguments.length&&("string"===typeof arguments[0]||arguments[0]instanceof Date)?(b=arguments[0],b instanceof Date&&(b=this._local_to_utc(b)),c=!0):(b=this.isInput?this.element.val():this.element.data("date")||
this.element.find("input").val(),delete this.element.data().date);this.date=k.parseDate(b,this.o.format,this.o.language);c?this.setValue():b?a.getTime()!==this.date.getTime()&&this._trigger("changeDate"):this._trigger("clearDate");this.date<this.o.startDate?(this.viewDate=new Date(this.o.startDate),this.date=new Date(this.o.startDate)):this.date>this.o.endDate?(this.viewDate=new Date(this.o.endDate),this.date=new Date(this.o.endDate)):(this.viewDate=new Date(this.date),this.date=new Date(this.date));
this.fill()}},fillDow:function(){var a=this.o.weekStart,b="<tr>";this.o.calendarWeeks&&(b+='<th class="cw">&nbsp;</th>',this.picker.find(".datepicker-days thead tr:first-child").prepend('<th class="cw">&nbsp;</th>'));for(;a<this.o.weekStart+7;)b+='<th class="dow">'+l[this.o.language].daysMin[a++%7]+"</th>";this.picker.find(".datepicker-days thead").append(b+"</tr>")},fillMonths:function(){for(var a="",b=0;12>b;)a+='<span class="month">'+l[this.o.language].monthsShort[b++]+"</span>";this.picker.find(".datepicker-months td").html(a)},
setRange:function(a){!a||!a.length?delete this.range:this.range=d.map(a,function(a){return a.valueOf()});this.fill()},getClassNames:function(a){var b=[],c=this.viewDate.getUTCFullYear(),f=this.viewDate.getUTCMonth(),h=this.date.valueOf(),g=new Date;a.getUTCFullYear()<c||a.getUTCFullYear()==c&&a.getUTCMonth()<f?b.push("old"):(a.getUTCFullYear()>c||a.getUTCFullYear()==c&&a.getUTCMonth()>f)&&b.push("new");this.o.todayHighlight&&a.getUTCFullYear()==g.getFullYear()&&a.getUTCMonth()==g.getMonth()&&a.getUTCDate()==
g.getDate()&&b.push("today");a.valueOf()==h&&b.push("active");(a.valueOf()<this.o.startDate||a.valueOf()>this.o.endDate||-1!==d.inArray(a.getUTCDay(),this.o.daysOfWeekDisabled))&&b.push("disabled");this.range&&(a>this.range[0]&&a<this.range[this.range.length-1]&&b.push("range"),-1!=d.inArray(a.valueOf(),this.range)&&b.push("selected"));return b},fill:function(){var a=new Date(this.viewDate),b=a.getUTCFullYear(),c=a.getUTCMonth(),a=-Infinity!==this.o.startDate?this.o.startDate.getUTCFullYear():-Infinity,
f=-Infinity!==this.o.startDate?this.o.startDate.getUTCMonth():-Infinity,h=Infinity!==this.o.endDate?this.o.endDate.getUTCFullYear():Infinity,g=Infinity!==this.o.endDate?this.o.endDate.getUTCMonth():Infinity,e;this.picker.find(".datepicker-days thead th.datepicker-switch").text(l[this.o.language].months[c]+" "+b);this.picker.find("tfoot th.today").text(l[this.o.language].today).toggle(!1!==this.o.todayBtn);this.picker.find("tfoot th.clear").text(l[this.o.language].clear).toggle(!1!==this.o.clearBtn);
this.updateNavArrows();this.fillMonths();var j=p(b,c-1,28,0,0,0,0),c=k.getDaysInMonth(j.getUTCFullYear(),j.getUTCMonth());j.setUTCDate(c);j.setUTCDate(c-(j.getUTCDay()-this.o.weekStart+7)%7);var m=new Date(j);m.setUTCDate(m.getUTCDate()+42);for(var m=m.valueOf(),c=[],i;j.valueOf()<m;){if(j.getUTCDay()==this.o.weekStart&&(c.push("<tr>"),this.o.calendarWeeks)){i=new Date(+j+864E5*((this.o.weekStart-j.getUTCDay()-7)%7));i=new Date(+i+864E5*((11-i.getUTCDay())%7));var o=new Date(+(o=p(i.getUTCFullYear(),
0,1))+864E5*((11-o.getUTCDay())%7));c.push('<td class="cw">'+((i-o)/864E5/7+1)+"</td>")}i=this.getClassNames(j);i.push("day");if(this.o.beforeShowDay!==d.noop){var n=this.o.beforeShowDay(this._utc_to_local(j));void 0===n?n={}:"boolean"===typeof n?n={enabled:n}:"string"===typeof n&&(n={classes:n});!1===n.enabled&&i.push("disabled");n.classes&&(i=i.concat(n.classes.split(/\s+/)));if(n.tooltip)e=n.tooltip}i=d.unique(i);c.push('<td class="'+i.join(" ")+'"'+(e?' title="'+e+'"':"")+">"+j.getUTCDate()+"</td>");
j.getUTCDay()==this.o.weekEnd&&c.push("</tr>");j.setUTCDate(j.getUTCDate()+1)}this.picker.find(".datepicker-days tbody").empty().append(c.join(""));e=this.date&&this.date.getUTCFullYear();c=this.picker.find(".datepicker-months").find("th:eq(1)").text(b).end().find("span").removeClass("active");e&&e==b&&c.eq(this.date.getUTCMonth()).addClass("active");(b<a||b>h)&&c.addClass("disabled");b==a&&c.slice(0,f).addClass("disabled");b==h&&c.slice(g+1).addClass("disabled");c="";b=10*parseInt(b/10,10);f=this.picker.find(".datepicker-years").find("th:eq(1)").text(b+
"-"+(b+9)).end().find("td");b-=1;for(g=-1;11>g;g++)c+='<span class="year'+(-1==g?" old":10==g?" new":"")+(e==b?" active":"")+(b<a||b>h?" disabled":"")+'">'+b+"</span>",b+=1;f.html(c)},updateNavArrows:function(){if(this._allow_update){var a=new Date(this.viewDate),b=a.getUTCFullYear(),a=a.getUTCMonth();switch(this.viewMode){case 0:-Infinity!==this.o.startDate&&b<=this.o.startDate.getUTCFullYear()&&a<=this.o.startDate.getUTCMonth()?this.picker.find(".prev").css({visibility:"hidden"}):this.picker.find(".prev").css({visibility:"visible"});
Infinity!==this.o.endDate&&b>=this.o.endDate.getUTCFullYear()&&a>=this.o.endDate.getUTCMonth()?this.picker.find(".next").css({visibility:"hidden"}):this.picker.find(".next").css({visibility:"visible"});break;case 1:case 2:-Infinity!==this.o.startDate&&b<=this.o.startDate.getUTCFullYear()?this.picker.find(".prev").css({visibility:"hidden"}):this.picker.find(".prev").css({visibility:"visible"}),Infinity!==this.o.endDate&&b>=this.o.endDate.getUTCFullYear()?this.picker.find(".next").css({visibility:"hidden"}):
this.picker.find(".next").css({visibility:"visible"})}}},click:function(a){a.preventDefault();a=d(a.target).closest("span, td, th");if(1==a.length)switch(a[0].nodeName.toLowerCase()){case "th":switch(a[0].className){case "datepicker-switch":this.showMode(1);break;case "prev":case "next":a=k.modes[this.viewMode].navStep*("prev"==a[0].className?-1:1);switch(this.viewMode){case 0:this.viewDate=this.moveMonth(this.viewDate,a);this._trigger("changeMonth",this.viewDate);break;case 1:case 2:this.viewDate=
this.moveYear(this.viewDate,a),1===this.viewMode&&this._trigger("changeYear",this.viewDate)}this.fill();break;case "today":a=new Date;a=p(a.getFullYear(),a.getMonth(),a.getDate(),0,0,0);this.showMode(-2);this._setDate(a,"linked"==this.o.todayBtn?null:"view");break;case "clear":var b;this.isInput?b=this.element:this.component&&(b=this.element.find("input"));b&&b.val("").change();this._trigger("changeDate");this.update();this.o.autoclose&&this.hide()}break;case "span":if(!a.is(".disabled")){this.viewDate.setUTCDate(1);
if(a.is(".month")){b=1;var c=a.parent().find("span").index(a),f=this.viewDate.getUTCFullYear();this.viewDate.setUTCMonth(c);this._trigger("changeMonth",this.viewDate);1===this.o.minViewMode&&this._setDate(p(f,c,b,0,0,0,0))}else f=parseInt(a.text(),10)||0,b=1,c=0,this.viewDate.setUTCFullYear(f),this._trigger("changeYear",this.viewDate),2===this.o.minViewMode&&this._setDate(p(f,c,b,0,0,0,0));this.showMode(-1);this.fill()}break;case "td":a.is(".day")&&!a.is(".disabled")&&(b=parseInt(a.text(),10)||1,
f=this.viewDate.getUTCFullYear(),c=this.viewDate.getUTCMonth(),a.is(".old")?0===c?(c=11,f-=1):c-=1:a.is(".new")&&(11==c?(c=0,f+=1):c+=1),this._setDate(p(f,c,b,0,0,0,0)))}},_setDate:function(a,b){if(!b||"date"==b)this.date=new Date(a);if(!b||"view"==b)this.viewDate=new Date(a);this.fill();this.setValue();this._trigger("changeDate");var c;this.isInput?c=this.element:this.component&&(c=this.element.find("input"));c&&c.change();this.o.autoclose&&(!b||"date"==b)&&this.hide()},moveMonth:function(a,b){if(!b)return a;
var c=new Date(a.valueOf()),d=c.getUTCDate(),h=c.getUTCMonth(),g=Math.abs(b),e,b=0<b?1:-1;if(1==g){if(g=-1==b?function(){return c.getUTCMonth()==h}:function(){return c.getUTCMonth()!=e},e=h+b,c.setUTCMonth(e),0>e||11<e)e=(e+12)%12}else{for(var j=0;j<g;j++)c=this.moveMonth(c,b);e=c.getUTCMonth();c.setUTCDate(d);g=function(){return e!=c.getUTCMonth()}}for(;g();)c.setUTCDate(--d),c.setUTCMonth(e);return c},moveYear:function(a,b){return this.moveMonth(a,12*b)},dateWithinRange:function(a){return a>=this.o.startDate&&
a<=this.o.endDate},keydown:function(a){if(this.picker.is(":not(:visible)"))27==a.keyCode&&this.show();else{var b=!1,c,d,h;switch(a.keyCode){case 27:this.hide();a.preventDefault();break;case 37:case 39:if(!this.o.keyboardNavigation)break;c=37==a.keyCode?-1:1;a.ctrlKey?(d=this.moveYear(this.date,c),h=this.moveYear(this.viewDate,c),this._trigger("changeYear",this.viewDate)):a.shiftKey?(d=this.moveMonth(this.date,c),h=this.moveMonth(this.viewDate,c),this._trigger("changeMonth",this.viewDate)):(d=new Date(this.date),
d.setUTCDate(this.date.getUTCDate()+c),h=new Date(this.viewDate),h.setUTCDate(this.viewDate.getUTCDate()+c));if(this.dateWithinRange(d))this.date=d,this.viewDate=h,this.setValue(),this.update(),a.preventDefault(),b=!0;break;case 38:case 40:if(!this.o.keyboardNavigation)break;c=38==a.keyCode?-1:1;a.ctrlKey?(d=this.moveYear(this.date,c),h=this.moveYear(this.viewDate,c),this._trigger("changeYear",this.viewDate)):a.shiftKey?(d=this.moveMonth(this.date,c),h=this.moveMonth(this.viewDate,c),this._trigger("changeMonth",
this.viewDate)):(d=new Date(this.date),d.setUTCDate(this.date.getUTCDate()+7*c),h=new Date(this.viewDate),h.setUTCDate(this.viewDate.getUTCDate()+7*c));if(this.dateWithinRange(d))this.date=d,this.viewDate=h,this.setValue(),this.update(),a.preventDefault(),b=!0;break;case 13:this.hide();a.preventDefault();break;case 9:this.hide()}if(b){this._trigger("changeDate");var g;this.isInput?g=this.element:this.component&&(g=this.element.find("input"));g&&g.change()}}},showMode:function(a){if(a)this.viewMode=
Math.max(this.o.minViewMode,Math.min(2,this.viewMode+a));this.picker.find(">div").hide().filter(".datepicker-"+k.modes[this.viewMode].clsName).css("display","block");this.updateNavArrows()}};var s=function(a,b){this.element=d(a);this.inputs=d.map(b.inputs,function(a){return a.jquery?a[0]:a});delete b.inputs;d(this.inputs).datepicker(b).bind("changeDate",d.proxy(this.dateUpdated,this));this.pickers=d.map(this.inputs,function(a){return d(a).data("datepicker")});this.updateDates()};s.prototype={updateDates:function(){this.dates=
d.map(this.pickers,function(a){return a.date});this.updateRanges()},updateRanges:function(){var a=d.map(this.dates,function(a){return a.valueOf()});d.each(this.pickers,function(b,c){c.setRange(a)})},dateUpdated:function(a){var b=d(a.target).data("datepicker").getUTCDate(),a=d.inArray(a.target,this.inputs),c=this.inputs.length;if(-1!=a){if(b<this.dates[a])for(;0<=a&&b<this.dates[a];)this.pickers[a--].setUTCDate(b);else if(b>this.dates[a])for(;a<c&&b>this.dates[a];)this.pickers[a++].setUTCDate(b);this.updateDates()}},
remove:function(){d.map(this.pickers,function(a){a.remove()});delete this.element.data().datepicker}};var w=d.fn.datepicker;d.fn.datepicker=function(a){var b=Array.apply(null,arguments);b.shift();var c;this.each(function(){var f=d(this),h=f.data("datepicker"),g="object"==typeof a&&a;if(!h){var h=t(this,"date"),e=d.extend({},r,h,g),e=u(e.language),g=d.extend({},r,e,h,g);f.is(".input-daterange")||g.inputs?(h={inputs:g.inputs||f.find("input").toArray()},f.data("datepicker",h=new s(this,d.extend(g,h)))):
f.data("datepicker",h=new o(this,g))}if("string"==typeof a&&"function"==typeof h[a]&&(c=h[a].apply(h,b),void 0!==c))return!1});return void 0!==c?c:this};var r=d.fn.datepicker.defaults={autoclose:!1,beforeShowDay:d.noop,calendarWeeks:!1,clearBtn:!1,daysOfWeekDisabled:[],endDate:Infinity,forceParse:!0,format:"mm/dd/yyyy",keyboardNavigation:!0,language:"en",minViewMode:0,orientation:"auto",rtl:!1,startDate:-Infinity,startView:0,todayBtn:!1,todayHighlight:!1,weekStart:0},v=d.fn.datepicker.locale_opts=
["format","rtl","weekStart"];d.fn.datepicker.Constructor=o;var l=d.fn.datepicker.dates={en:{days:"Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday".split(","),daysShort:"Sun,Mon,Tue,Wed,Thu,Fri,Sat,Sun".split(","),daysMin:"Su,Mo,Tu,We,Th,Fr,Sa,Su".split(","),months:"January,February,March,April,May,June,July,August,September,October,November,December".split(","),monthsShort:"Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec".split(","),today:"Today",clear:"Clear"}},k={modes:[{clsName:"days",
navFnc:"Month",navStep:1},{clsName:"months",navFnc:"FullYear",navStep:1},{clsName:"years",navFnc:"FullYear",navStep:10}],isLeapYear:function(a){return 0===a%4&&0!==a%100||0===a%400},getDaysInMonth:function(a,b){return[31,k.isLeapYear(a)?29:28,31,30,31,30,31,31,30,31,30,31][b]},validParts:/dd?|DD?|mm?|MM?|yy(?:yy)?/g,nonpunctuation:/[^ -\/:-@\[\u3400-\u9fff-`{-~\t\n\r]+/g,parseFormat:function(a){var b=a.replace(this.validParts,"\x00").split("\x00"),a=a.match(this.validParts);if(!b||!b.length||!a||
0===a.length)throw Error("Invalid date format.");return{separators:b,parts:a}},parseDate:function(a,b,c){if(a instanceof Date)return a;"string"===typeof b&&(b=k.parseFormat(b));if(/^\d{4}-\d{2}-\d{2}$/.test(a)){var f=a.split("-");return p(f[0],parseInt(f[1],10)-1,f[2],0,0,0)}if(/^[\-+]\d+[dmwy]([\s,]+[\-+]\d+[dmwy])*$/.test(a)){for(var h=/([\-+]\d+)([dmwy])/,f=a.match(/([\-+]\d+)([dmwy])/g),g,a=new Date,e=0;e<f.length;e++)switch(b=h.exec(f[e]),g=parseInt(b[1]),b[2]){case "d":a.setUTCDate(a.getUTCDate()+
g);break;case "m":a=o.prototype.moveMonth.call(o.prototype,a,g);break;case "w":a.setUTCDate(a.getUTCDate()+7*g);break;case "y":a=o.prototype.moveYear.call(o.prototype,a,g)}return p(a.getUTCFullYear(),a.getUTCMonth(),a.getUTCDate(),0,0,0)}var f=a&&a.match(this.nonpunctuation)||[],a=new Date,h={},j="yyyy,yy,M,MM,m,mm,d,dd".split(",");g={yyyy:function(a,b){return a.setUTCFullYear(70>b?2E3+b:b)},yy:function(a,b){return a.setUTCFullYear(2E3+b)},m:function(a,b){if(isNaN(a))return a;for(b-=1;0>b;)b+=12;
b%=12;for(a.setUTCMonth(b);a.getUTCMonth()!=b;)a.setUTCDate(a.getUTCDate()-1);return a},d:function(a,b){return a.setUTCDate(b)}};var m;g.M=g.MM=g.mm=g.m;g.dd=g.d;var a=p(a.getFullYear(),a.getMonth(),a.getDate(),0,0,0),i=b.parts.slice();f.length!=i.length&&(i=d(i).filter(function(a,b){return-1!==d.inArray(b,j)}).toArray());if(f.length==i.length){for(var e=0,q=i.length;e<q;e++){m=parseInt(f[e],10);b=i[e];if(isNaN(m))switch(b){case "MM":m=d(l[c].months).filter(function(){var a=this.slice(0,f[e].length),
b=f[e].slice(0,a.length);return a==b});m=d.inArray(m[0],l[c].months)+1;break;case "M":m=d(l[c].monthsShort).filter(function(){var a=this.slice(0,f[e].length),b=f[e].slice(0,a.length);return a==b}),m=d.inArray(m[0],l[c].monthsShort)+1}h[b]=m}for(e=0;e<j.length;e++)c=j[e],c in h&&!isNaN(h[c])&&(b=new Date(a),g[c](b,h[c]),isNaN(b)||(a=b))}return a},formatDate:function(a,b,c){"string"===typeof b&&(b=k.parseFormat(b));c={d:a.getUTCDate(),D:l[c].daysShort[a.getUTCDay()],DD:l[c].days[a.getUTCDay()],m:a.getUTCMonth()+
1,M:l[c].monthsShort[a.getUTCMonth()],MM:l[c].months[a.getUTCMonth()],yy:a.getUTCFullYear().toString().substring(2),yyyy:a.getUTCFullYear()};c.dd=(10>c.d?"0":"")+c.d;c.mm=(10>c.m?"0":"")+c.m;for(var a=[],f=d.extend([],b.separators),h=0,g=b.parts.length;h<=g;h++)f.length&&a.push(f.shift()),a.push(c[b.parts[h]]);return a.join("")},headTemplate:'<thead><tr><th class="prev">&laquo;</th><th colspan="5" class="datepicker-switch"></th><th class="next">&raquo;</th></tr></thead>',contTemplate:'<tbody><tr><td colspan="7"></td></tr></tbody>',
footTemplate:'<tfoot><tr><th colspan="7" class="today"></th></tr><tr><th colspan="7" class="clear"></th></tr></tfoot>'};k.template='<div class="datepicker"><div class="datepicker-days"><table class=" table-condensed">'+k.headTemplate+"<tbody></tbody>"+k.footTemplate+'</table></div><div class="datepicker-months"><table class="table-condensed">'+k.headTemplate+k.contTemplate+k.footTemplate+'</table></div><div class="datepicker-years"><table class="table-condensed">'+k.headTemplate+k.contTemplate+k.footTemplate+
"</table></div></div>";d.fn.datepicker.DPGlobal=k;d.fn.datepicker.noConflict=function(){d.fn.datepicker=w;return this};d(document).on("focus.datepicker.data-api click.datepicker.data-api",'[data-provide="datepicker"]',function(a){var b=d(this);b.data("datepicker")||(a.preventDefault(),b.datepicker("show"))});d(function(){d('[data-provide="datepicker-inline"]').datepicker()})})(window.jQuery);

var autosave = null;

function InitAutosave(form_id) {

    var restore = false;
    var fields_to_restore = null;
    if (typeof restoredFields !== 'undefined' && restoredFields !== null) { 
        restore = true;
        fields_to_restore = restoredFields;
        restoredFields = null;
    }
    autosave = $nc("#" + form_id).autosave({
        timeout: ((nc_autosave_type === 'timer' && nc_autosave_period > 0) ? nc_autosave_period : 0),
        noactive: ((typeof nc_autosave_noactive !== 'undefined') ? nc_autosave_noactive : 0),
        restore: restore,
        fields_to_restore: fields_to_restore,
        customKeySuffix: 'nc_',
        // чтобы избежать автозаполнения черновыми данными
        onBeforeRestore: function() {
            return false;
        },
        onSave: function(obj) {
            var self = this;
            var post_data = {};
            self.targets.each(function() {
                var targetId = $nc(this).attr("id");
                var multiCheckboxCache = {};

                self.findFieldsToProtect($nc('#' + targetId)).each(function() {
                    var field = $nc(this);
                    if ($nc.inArray(this, self.options.excludeFields) !== -1 || field.attr("name") === undefined) {
                        // Returning non-false is the same as a continue statement in a for loop; it will skip immediately to the next iteration.
                        return true;
                    }
                    var value = field.val();

                    if (field.is(":checkbox")) {
                        if (field.attr("name").indexOf("[") !== -1) {
                            if (multiCheckboxCache[ field.attr("name") ] === true) {
                                return;
                            }
                            value = [];
                            $nc("[name='" + field.attr("name") + "']:checked").each(function() {
                                value.push($nc(this).val());
                            });
                            multiCheckboxCache[ field.attr("name") ] = true;
                        } else {
                            value = field.is(":checked");
                        }
                        post_data[field.attr("name")] = value;
                    } else if (field.is(":radio")) {
                        if (field.is(":checked")) {
                            value = field.val();
                            post_data[field.attr("name")] = value;
                        }
                    } else {
                        if (self.isCKEditorExists()) {
                            var editor;
                            if (editor = CKEDITOR.instances[ field.attr("name") ] || CKEDITOR.instances[ field.attr("id") ]) {
                                editor.updateElement();
                                post_data[field.attr("name")] = field.val();
                            } else {
                                post_data[field.attr("name")] = value;
                            }
                        } else {
                            post_data[field.attr("name")] = value;
                        }
                    }
                });
            });
            $nc.ajax({
                'type': 'POST',
                'url': NETCAT_PATH + 'message.php?isVersion=1&cc=' + post_data.cc,
                'data': post_data,
                success: function(response) {
                    if ($nc('.nc_draft_btn').length) {
                        $nc('.nc_draft_btn').removeClass('nc--loading');
                    }
                }
            });
        }
    });

}

document.addEventListener('DOMContentLoaded', function() {

    const page = $nc('meta#nc_page').data();
    if (!page) {
        return; // в режиме редактирования должен быть <meta id="nc_page"> с данными о странице
    }

    const bothButtons = $nc('.nc-navbar .nc-version-button');
    const buttons = {
        undo: bothButtons.filter('[data-type="undo"]'),
        redo: bothButtons.filter('[data-type="redo"]'),
    }
    const title = {
        undo: buttons.undo.find('.nc-version-title'),
        redo: buttons.redo.find('.nc-version-title'),
    };

    const navbar = $nc('.nc-navbar');

    const url = NETCAT_PATH + 'action.php';

    let baseParams = {
        page_action: page.action,
        page_areas: getAreas(),
        site_id: page.siteId,
        subdivision_id: page.subdivisionId,
        infoblock_id: page.infoblockId,
        component_id: page.componentId,
        object_id: page.objectId,
        ctrl: 'admin.version',
        // also need to set controller 'action' for a request
    };

    function getAreas() {
        let areas = [];
        $nc('.nc-area').each((i, el) => areas.push(el.dataset.keyword))
        return areas.join(' ');
    }

    function update() {
        const params = {
            ... baseParams,
            action: 'get_undo_and_redo'
        };
        $nc.get(url, params, function(response) {
            for (let type of ['undo', 'redo']) {
                // показ и скрытие 'title' определяется стилями по .nc--disabled и .nc--hovered
                if (response[type]) {
                    title[type].html(response[type].title)
                    buttons[type].removeClass('nc--disabled').data('changeset_id', response[type].changeset_id);
                } else {
                    title[type].html('');
                    buttons[type].addClass('nc--disabled').data('changeset_id', null);
                }
            }
        }, 'json');
    }

    bothButtons.hover(
        function () { // mouse over
            $nc(this).addClass('nc--hovered');
            navbar.addClass('nc--clicked'); // меняет z-index навбара на максимальный
        },
        function () { // mouse out
            $nc(this).removeClass('nc--hovered');
            navbar.removeClass('nc--clicked');
        }
    );
    bothButtons.click(function (e) {
        const button = $nc(this);
        if (!button.is('.nc--disabled')) {
            const changeset_id = button.data('changeset_id');
            if (changeset_id) {
                const params = {
                    ...baseParams,
                    action: button.data('type') + '_changeset',
                    changeset_id: changeset_id,
                };
                $nc.post(url, params, function (response) {
                    window.location.reload();
                });
            }
        }
        e.preventDefault();
    });

    update();
    bothButtons.parent().hover(update);
    $nc(document).on('ncContentUpdate', update);

});
//
// jQuery MiniColors: A tiny color picker built on jQuery
//
// Developed by Cory LaViska for A Beautiful Site, LLC
//
// Licensed under the MIT license: http://opensource.org/licenses/MIT
//
!function(i){"function"==typeof define&&define.amd?define(["jquery"],i):"object"==typeof exports?module.exports=i(require("jquery")):i(jQuery)}(function(C){"use strict";function o(i){var t=i.parent();i.removeData("minicolors-initialized").removeData("minicolors-settings").removeProp("size").removeClass("minicolors-input"),t.before(i).remove()}function s(i){var t=i.parent(),o=t.find(".minicolors-panel"),s=i.data("minicolors-settings");!i.data("minicolors-initialized")||i.prop("disabled")||t.hasClass("minicolors-inline")||t.hasClass("minicolors-focus")||(a(),t.addClass("minicolors-focus"),o.animate?o.stop(!0,!0).fadeIn(s.showSpeed,function(){s.show&&s.show.call(i.get(0))}):(o.css("opacity",1),s.show&&s.show.call(i.get(0))))}function a(){C(".minicolors-focus").each(function(){var i=C(this),t=i.find(".minicolors-input"),o=i.find(".minicolors-panel"),s=t.data("minicolors-settings");o.animate?o.fadeOut(s.hideSpeed,function(){s.hide&&s.hide.call(t.get(0)),i.removeClass("minicolors-focus")}):(o.css("opacity",0),s.hide&&s.hide.call(t.get(0)),i.removeClass("minicolors-focus"))})}function n(i,t,o){var s,a,n,e,r,c=i.parents(".minicolors").find(".minicolors-input"),l=c.data("minicolors-settings"),h=i.find("[class$=-picker]"),d=i.offset().left,p=i.offset().top,u=Math.round(t.pageX-d),g=Math.round(t.pageY-p),m=o?l.animationSpeed:0;t.originalEvent.changedTouches&&(u=t.originalEvent.changedTouches[0].pageX-d,g=t.originalEvent.changedTouches[0].pageY-p),u<0&&(u=0),g<0&&(g=0),u>i.width()&&(u=i.width()),g>i.height()&&(g=i.height()),i.parent().is(".minicolors-slider-wheel")&&h.parent().is(".minicolors-grid")&&(s=75-u,a=75-g,n=Math.sqrt(s*s+a*a),(e=Math.atan2(a,s))<0&&(e+=2*Math.PI),75<n&&(u=(n=75)-75*Math.cos(e),g=75-75*Math.sin(e)),u=Math.round(u),g=Math.round(g)),r={top:g+"px"},i.is(".minicolors-grid")&&(r.left=u+"px"),h.animate?h.stop(!0).animate(r,m,l.animationEasing,function(){f(c,i)}):(h.css(r),f(c,i))}function f(i,t){function o(i,t){var o,s;return i.length&&t?(o=i.offset().left,s=i.offset().top,{x:o-t.offset().left+i.outerWidth()/2,y:s-t.offset().top+i.outerHeight()/2}):null}var s,a,n,e,r,c,l,h=i.val(),d=i.attr("data-opacity"),p=i.parent(),u=i.data("minicolors-settings"),g=p.find(".minicolors-input-swatch"),m=p.find(".minicolors-grid"),f=p.find(".minicolors-slider"),v=p.find(".minicolors-opacity-slider"),b=m.find("[class$=-picker]"),w=f.find("[class$=-picker]"),y=v.find("[class$=-picker]"),C=o(b,m),k=o(w,f),M=o(y,v);if(t.is(".minicolors-grid, .minicolors-slider, .minicolors-opacity-slider")){switch(u.control){case"wheel":e=m.width()/2-C.x,r=m.height()/2-C.y,c=Math.sqrt(e*e+r*r),(l=Math.atan2(r,e))<0&&(l+=2*Math.PI),75<c&&(c=75,C.x=69-75*Math.cos(l),C.y=69-75*Math.sin(l)),a=F(c/.75,0,100),h=q({h:s=F(180*l/Math.PI,0,360),s:a,b:n=F(100-Math.floor(k.y*(100/f.height())),0,100)}),f.css("backgroundColor",q({h:s,s:a,b:100}));break;case"saturation":h=q({h:s=F(parseInt(C.x*(360/m.width()),10),0,360),s:a=F(100-Math.floor(k.y*(100/f.height())),0,100),b:n=F(100-Math.floor(C.y*(100/m.height())),0,100)}),f.css("backgroundColor",q({h:s,s:100,b:n})),p.find(".minicolors-grid-inner").css("opacity",a/100);break;case"brightness":h=q({h:s=F(parseInt(C.x*(360/m.width()),10),0,360),s:a=F(100-Math.floor(C.y*(100/m.height())),0,100),b:n=F(100-Math.floor(k.y*(100/f.height())),0,100)}),f.css("backgroundColor",q({h:s,s:a,b:100})),p.find(".minicolors-grid-inner").css("opacity",1-n/100);break;default:h=q({h:s=F(360-parseInt(k.y*(360/f.height()),10),0,360),s:a=F(Math.floor(C.x*(100/m.width())),0,100),b:n=F(100-Math.floor(C.y*(100/m.height())),0,100)}),m.css("backgroundColor",q({h:s,s:100,b:100}))}x(i,h,d=u.opacity?parseFloat(1-M.y/v.height()).toFixed(2):1)}else g.find("span").css({backgroundColor:h,opacity:d}),I(i,h,d)}function x(i,t,o){var s,a=i.parent(),n=i.data("minicolors-settings"),e=a.find(".minicolors-input-swatch");n.opacity&&i.attr("data-opacity",o),"rgb"===n.format?(s=T(t)?S(t,!0):L(M(t,!0)),o=""===i.attr("data-opacity")?1:F(parseFloat(i.attr("data-opacity")).toFixed(2),0,1),!isNaN(o)&&n.opacity||(o=1),t=i.minicolors("rgbObject").a<=1&&s&&n.opacity?"rgba("+s.r+", "+s.g+", "+s.b+", "+parseFloat(o)+")":"rgb("+s.r+", "+s.g+", "+s.b+")"):(T(t)&&(t=j(t)),t=k(t,n.letterCase)),i.val(t),e.find("span").css({backgroundColor:t,opacity:o}),I(i,t,o)}function d(i,t){var o,s,a,n,e,r,c,l,h,d,p=i.parent(),u=i.data("minicolors-settings"),g=p.find(".minicolors-input-swatch"),m=p.find(".minicolors-grid"),f=p.find(".minicolors-slider"),v=p.find(".minicolors-opacity-slider"),b=m.find("[class$=-picker]"),w=f.find("[class$=-picker]"),y=v.find("[class$=-picker]");switch(T(i.val())?(o=j(i.val()),(e=F(parseFloat(D(i.val())).toFixed(2),0,1))&&i.attr("data-opacity",e)):o=k(M(i.val(),!0),u.letterCase),o||(o=k(z(u.defaultValue,!0),u.letterCase)),s=function(i){var t=function(i){var t={h:0,s:0,b:0},o=Math.min(i.r,i.g,i.b),s=Math.max(i.r,i.g,i.b),a=s-o;t.b=s,t.s=0!==s?255*a/s:0,0!==t.s?i.r===s?t.h=(i.g-i.b)/a:i.g===s?t.h=2+(i.b-i.r)/a:t.h=4+(i.r-i.g)/a:t.h=-1;t.h*=60,t.h<0&&(t.h+=360);return t.s*=100/255,t.b*=100/255,t}(L(i));0===t.s&&(t.h=360);return t}(o),n=u.keywords?C.map(u.keywords.split(","),function(i){return C.trim(i.toLowerCase())}):[],r=""!==i.val()&&-1<C.inArray(i.val().toLowerCase(),n)?k(i.val()):T(i.val())?S(i.val()):o,t||i.val(r),u.opacity&&(a=""===i.attr("data-opacity")?1:F(parseFloat(i.attr("data-opacity")).toFixed(2),0,1),isNaN(a)&&(a=1),i.attr("data-opacity",a),g.find("span").css("opacity",a),l=F(v.height()-v.height()*a,0,v.height()),y.css("top",l+"px")),"transparent"===i.val().toLowerCase()&&g.find("span").css("opacity",0),g.find("span").css("backgroundColor",o),u.control){case"wheel":h=F(Math.ceil(.75*s.s),0,m.height()/2),d=s.h*Math.PI/180,c=F(75-Math.cos(d)*h,0,m.width()),l=F(75-Math.sin(d)*h,0,m.height()),b.css({top:l+"px",left:c+"px"}),l=150-s.b/(100/m.height()),""===o&&(l=0),w.css("top",l+"px"),f.css("backgroundColor",q({h:s.h,s:s.s,b:100}));break;case"saturation":c=F(5*s.h/12,0,150),l=F(m.height()-Math.ceil(s.b/(100/m.height())),0,m.height()),b.css({top:l+"px",left:c+"px"}),l=F(f.height()-s.s*(f.height()/100),0,f.height()),w.css("top",l+"px"),f.css("backgroundColor",q({h:s.h,s:100,b:s.b})),p.find(".minicolors-grid-inner").css("opacity",s.s/100);break;case"brightness":c=F(5*s.h/12,0,150),l=F(m.height()-Math.ceil(s.s/(100/m.height())),0,m.height()),b.css({top:l+"px",left:c+"px"}),l=F(f.height()-s.b*(f.height()/100),0,f.height()),w.css("top",l+"px"),f.css("backgroundColor",q({h:s.h,s:s.s,b:100})),p.find(".minicolors-grid-inner").css("opacity",1-s.b/100);break;default:c=F(Math.ceil(s.s/(100/m.width())),0,m.width()),l=F(m.height()-Math.ceil(s.b/(100/m.height())),0,m.height()),b.css({top:l+"px",left:c+"px"}),l=F(f.height()-s.h/(360/f.height()),0,f.height()),w.css("top",l+"px"),m.css("backgroundColor",q({h:s.h,s:100,b:100}))}i.data("minicolors-initialized")&&I(i,r,a)}function I(i,t,o){var s,a,n,e=i.data("minicolors-settings"),r=i.data("minicolors-lastChange");if(!r||r.value!==t||r.opacity!==o){if(i.data("minicolors-lastChange",{value:t,opacity:o}),e.swatches&&0!==e.swatches.length){for(s=T(t)?S(t,!0):L(t),a=-1,n=0;n<e.swatches.length;++n)if(s.r===e.swatches[n].r&&s.g===e.swatches[n].g&&s.b===e.swatches[n].b&&s.a===e.swatches[n].a){a=n;break}i.parent().find(".minicolors-swatches .minicolors-swatch").removeClass("selected"),-1!==a&&i.parent().find(".minicolors-swatches .minicolors-swatch").eq(n).addClass("selected")}e.change&&(e.changeDelay?(clearTimeout(i.data("minicolors-changeTimeout")),i.data("minicolors-changeTimeout",setTimeout(function(){e.change.call(i.get(0),t,o)},e.changeDelay))):e.change.call(i.get(0),t,o)),i.trigger("change").trigger("input")}}function k(i,t){return"uppercase"===t?i.toUpperCase():i.toLowerCase()}function M(i,t){return(i=i.replace(/^#/g,"")).match(/^[A-F0-9]{3,6}/gi)?3!==i.length&&6!==i.length?"":(3===i.length&&t&&(i=i[0]+i[0]+i[1]+i[1]+i[2]+i[2]),"#"+i):""}function S(i,t){var o=i.replace(/[^\d,.]/g,"").split(",");return o[0]=F(parseInt(o[0],10),0,255),o[1]=F(parseInt(o[1],10),0,255),o[2]=F(parseInt(o[2],10),0,255),void 0!==o[3]&&(o[3]=F(parseFloat(o[3],10),0,1)),t?void 0!==o[3]?{r:o[0],g:o[1],b:o[2],a:o[3]}:{r:o[0],g:o[1],b:o[2]}:void 0!==o[3]&&o[3]<=1?"rgba("+o[0]+", "+o[1]+", "+o[2]+", "+o[3]+")":"rgb("+o[0]+", "+o[1]+", "+o[2]+")"}function z(i,t){return T(i)?S(i):M(i,t)}function F(i,t,o){return i<t&&(i=t),o<i&&(i=o),i}function T(i){var t=i.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);return!(!t||4!==t.length)}function D(i){return(i=i.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+(\.\d{1,2})?|\.\d{1,2})[\s+]?/i))&&6===i.length?i[4]:"1"}function j(i){return(i=i.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i))&&4===i.length?"#"+("0"+parseInt(i[1],10).toString(16)).slice(-2)+("0"+parseInt(i[2],10).toString(16)).slice(-2)+("0"+parseInt(i[3],10).toString(16)).slice(-2):""}function p(i){var o=[i.r.toString(16),i.g.toString(16),i.b.toString(16)];return C.each(o,function(i,t){1===t.length&&(o[i]="0"+t)}),"#"+o.join("")}function q(i){return p(function(i){var t={},o=Math.round(i.h),s=Math.round(255*i.s/100),a=Math.round(255*i.b/100);if(0===s)t.r=t.g=t.b=a;else{var n=a,e=(255-s)*a/255,r=o%60*(n-e)/60;360===o&&(o=0),o<60?(t.r=n,t.b=e,t.g=e+r):o<120?(t.g=n,t.b=e,t.r=n-r):o<180?(t.g=n,t.r=e,t.b=e+r):o<240?(t.b=n,t.r=e,t.g=n-r):o<300?(t.b=n,t.g=e,t.r=e+r):o<360?(t.r=n,t.g=e,t.b=n-r):(t.r=0,t.g=0,t.b=0)}return{r:Math.round(t.r),g:Math.round(t.g),b:Math.round(t.b)}}(i))}function L(i){return{r:(i=parseInt(-1<i.indexOf("#")?i.substring(1):i,16))>>16,g:(65280&i)>>8,b:255&i}}C.minicolors={defaults:{animationSpeed:50,animationEasing:"swing",change:null,changeDelay:0,control:"hue",defaultValue:"",format:"hex",hide:null,hideSpeed:100,inline:!1,keywords:"",letterCase:"lowercase",opacity:!1,position:"bottom",show:null,showSpeed:100,theme:"default",swatches:[]}},C.extend(C.fn,{minicolors:function(i,t){switch(i){case"destroy":return C(this).each(function(){o(C(this))}),C(this);case"hide":return a(),C(this);case"opacity":return void 0===t?C(this).attr("data-opacity"):(C(this).each(function(){d(C(this).attr("data-opacity",t))}),C(this));case"rgbObject":return function(i){var t,o=C(i).attr("data-opacity");if(T(C(i).val()))t=S(C(i).val(),!0);else{var s=M(C(i).val(),!0);t=L(s)}if(!t)return null;void 0!==o&&C.extend(t,{a:parseFloat(o)});return t}(C(this));case"rgbString":case"rgbaString":return function(i,t){var o,s=C(i).attr("data-opacity");if(T(C(i).val()))o=S(C(i).val(),!0);else{var a=M(C(i).val(),!0);o=L(a)}if(!o)return null;void 0===s&&(s=1);return t?"rgba("+o.r+", "+o.g+", "+o.b+", "+parseFloat(s)+")":"rgb("+o.r+", "+o.g+", "+o.b+")"}(C(this),"rgbaString"===i);case"settings":return void 0===t?C(this).data("minicolors-settings"):(C(this).each(function(){var i=C(this).data("minicolors-settings")||{};o(C(this)),C(this).minicolors(C.extend(!0,i,t))}),C(this));case"show":return s(C(this).eq(0)),C(this);case"value":return void 0===t?C(this).val():(C(this).each(function(){"object"==typeof t&&null!==t?(void 0!==t.opacity&&C(this).attr("data-opacity",F(t.opacity,0,1)),t.color&&C(this).val(t.color)):C(this).val(t),d(C(this))}),C(this));default:return"create"!==i&&(t=i),C(this).each(function(){!function(t,i){var o,s,a,n,e,r,c,l=C('<div class="minicolors" />'),h=C.minicolors.defaults;if(t.data("minicolors-initialized"))return;i=C.extend(!0,{},h,i),l.addClass("minicolors-theme-"+i.theme).toggleClass("minicolors-with-opacity",i.opacity),void 0!==i.position&&C.each(i.position.split(" "),function(){l.addClass("minicolors-position-"+this)});s="rgb"===i.format?i.opacity?"25":"20":i.keywords?"11":"7";t.addClass("minicolors-input").data("minicolors-initialized",!1).data("minicolors-settings",i).prop("size",s).wrap(l).after('<div class="minicolors-panel minicolors-slider-'+i.control+'"><div class="minicolors-slider minicolors-sprite"><div class="minicolors-picker"></div></div><div class="minicolors-opacity-slider minicolors-sprite"><div class="minicolors-picker"></div></div><div class="minicolors-grid minicolors-sprite"><div class="minicolors-grid-inner"></div><div class="minicolors-picker"><div></div></div></div></div>'),i.inline||(t.after('<span class="minicolors-swatch minicolors-sprite minicolors-input-swatch"><span class="minicolors-swatch-color"></span></span>'),t.next(".minicolors-input-swatch").on("click",function(i){i.preventDefault(),t.focus()}));if((r=t.parent().find(".minicolors-panel")).on("selectstart",function(){return!1}).end(),i.swatches&&0!==i.swatches.length)for(r.addClass("minicolors-with-swatches"),a=C('<ul class="minicolors-swatches"></ul>').appendTo(r),c=0;c<i.swatches.length;++c)"object"===C.type(i.swatches[c])?(o=i.swatches[c].name,n=i.swatches[c].color):(o="",n=i.swatches[c]),n=T(e=n)?S(n,!0):L(M(n,!0)),C('<li class="minicolors-swatch minicolors-sprite"><span class="minicolors-swatch-color" title="'+o+'"></span></li>').appendTo(a).data("swatch-color",e).find(".minicolors-swatch-color").css({backgroundColor:p(n),opacity:n.a}),i.swatches[c]=n;i.inline&&t.parent().addClass("minicolors-inline");d(t,!1),t.data("minicolors-initialized",!0)}(C(this),t)}),C(this)}}}),C([document]).on("mousedown.minicolors touchstart.minicolors",function(i){C(i.target).parents().add(i.target).hasClass("minicolors")||a()}).on("mousedown.minicolors touchstart.minicolors",".minicolors-grid, .minicolors-slider, .minicolors-opacity-slider",function(i){var t=C(this);i.preventDefault(),C(i.delegateTarget).data("minicolors-target",t),n(t,i,!0)}).on("mousemove.minicolors touchmove.minicolors",function(i){var t=C(i.delegateTarget).data("minicolors-target");t&&n(t,i)}).on("mouseup.minicolors touchend.minicolors",function(){C(this).removeData("minicolors-target")}).on("click.minicolors",".minicolors-swatches li",function(i){i.preventDefault();var t=C(this),o=t.parents(".minicolors").find(".minicolors-input"),s=t.data("swatch-color");x(o,s,D(s)),d(o)}).on("mousedown.minicolors touchstart.minicolors",".minicolors-input-swatch",function(i){var t=C(this).parent().find(".minicolors-input");i.preventDefault(),s(t)}).on("focus.minicolors",".minicolors-input",function(){var i=C(this);i.data("minicolors-initialized")&&s(i)}).on("blur.minicolors",".minicolors-input",function(){var i,t,o,s,a,n=C(this),e=n.data("minicolors-settings");n.data("minicolors-initialized")&&(i=e.keywords?C.map(e.keywords.split(","),function(i){return C.trim(i.toLowerCase())}):[],a=""!==n.val()&&-1<C.inArray(n.val().toLowerCase(),i)?n.val():null===(o=T(n.val())?S(n.val(),!0):(t=M(n.val(),!0))?L(t):null)?e.defaultValue:"rgb"===e.format?e.opacity?S("rgba("+o.r+","+o.g+","+o.b+","+n.attr("data-opacity")+")"):S("rgb("+o.r+","+o.g+","+o.b+")"):p(o),s=e.opacity?n.attr("data-opacity"):1,"transparent"===a.toLowerCase()&&(s=0),n.closest(".minicolors").find(".minicolors-input-swatch > span").css("opacity",s),n.val(a),""===n.val()&&n.val(z(e.defaultValue,!0)),n.val(k(n.val(),e.letterCase)))}).on("keydown.minicolors",".minicolors-input",function(i){var t=C(this);if(t.data("minicolors-initialized"))switch(i.which){case 9:a();break;case 13:case 27:a(),t.blur()}}).on("keyup.minicolors",".minicolors-input",function(){var i=C(this);i.data("minicolors-initialized")&&d(i,!0)}).on("paste.minicolors",".minicolors-input",function(){var i=C(this);i.data("minicolors-initialized")&&setTimeout(function(){d(i,!0)},1)})});

/*
 * dmUploader - jQuery Ajax File Uploader Widget
 * https://github.com/danielm/uploader
 *
 * Copyright Daniel Morales <daniel85mg@gmail.com>
 * Released under the MIT license.
 * https://github.com/danielm/uploader/blob/master/LICENSE.txt
 *
 * @preserve
 */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery"],e):"undefined"!=typeof exports?module.exports=e(require("jquery")):e(window.jQuery)}(function(e){"use strict";var t="dmUploader",n=0,i=1,s=2,o=3,r=4,u={auto:!0,queue:!0,dnd:!0,hookDocument:!0,multiple:!0,url:document.URL,method:"POST",extraData:{},headers:{},dataType:null,fieldName:"file",maxFileSize:0,allowedTypes:"*",extFilter:null,onInit:function(){},onComplete:function(){},onFallbackMode:function(){},onNewFile:function(){},onBeforeUpload:function(){},onUploadProgress:function(){},onUploadSuccess:function(){},onUploadCanceled:function(){},onUploadError:function(){},onUploadComplete:function(){},onFileTypeError:function(){},onFileSizeError:function(){},onFileExtError:function(){},onDragEnter:function(){},onDragLeave:function(){},onDocumentDragEnter:function(){},onDocumentDragLeave:function(){}},a=function(e,t){this.data=e,this.widget=t,this.jqXHR=null,this.status=n,this.id=Math.random().toString(36).substr(2)};a.prototype.upload=function(){var t=this;if(!t.canUpload())return t.widget.queueRunning&&t.status!==i&&t.widget.processQueue(),!1;var n=new FormData;n.append(t.widget.settings.fieldName,t.data);var s=t.widget.settings.extraData;return"function"==typeof s&&(s=s.call(t.widget.element,t.id)),e.each(s,function(e,t){n.append(e,t)}),t.status=i,t.widget.activeFiles++,t.widget.settings.onBeforeUpload.call(t.widget.element,t.id),t.jqXHR=e.ajax({url:t.widget.settings.url,type:t.widget.settings.method,dataType:t.widget.settings.dataType,data:n,headers:t.widget.settings.headers,cache:!1,contentType:!1,processData:!1,forceSync:!1,xhr:function(){return t.getXhr()},success:function(e){t.onSuccess(e)},error:function(e,n,i){t.onError(e,n,i)},complete:function(){t.onComplete()}}),!0},a.prototype.onSuccess=function(e){this.status=s,this.widget.settings.onUploadSuccess.call(this.widget.element,this.id,e)},a.prototype.onError=function(e,t,n){this.status!==r&&(this.status=o,this.widget.settings.onUploadError.call(this.widget.element,this.id,e,t,n))},a.prototype.onComplete=function(){this.widget.activeFiles--,this.status!==r&&this.widget.settings.onUploadComplete.call(this.widget.element,this.id),this.widget.queueRunning?this.widget.processQueue():this.widget.settings.queue&&0===this.widget.activeFiles&&this.widget.settings.onComplete.call(this.element)},a.prototype.getXhr=function(){var t=this,n=e.ajaxSettings.xhr();return n.upload&&n.upload.addEventListener("progress",function(e){var n=0,i=e.loaded||e.position,s=e.total||e.totalSize;e.lengthComputable&&(n=Math.ceil(i/s*100)),t.widget.settings.onUploadProgress.call(t.widget.element,t.id,n)},!1),n},a.prototype.cancel=function(e){e=void 0!==e&&e;var t=this.status;return!!(t===i||e&&t===n)&&(this.status=r,this.widget.settings.onUploadCanceled.call(this.widget.element,this.id),t===i&&this.jqXHR.abort(),!0)},a.prototype.canUpload=function(){return this.status===n||this.status===o};var l=function(t,n){return this.element=e(t),this.settings=e.extend({},u,n),this.checkSupport()?(this.init(),this):(e.error("Browser not supported by jQuery.dmUploader"),this.settings.onFallbackMode.call(this.element),!1)};l.prototype.checkSupport=function(){if(void 0===window.FormData)return!1;return!new RegExp("/(Android (1.0|1.1|1.5|1.6|2.0|2.1))|(Windows Phone (OS 7|8.0))|(XBLWP)|(ZuneWP)|(w(eb)?OSBrowser)|(webOS)|(Kindle/(1.0|2.0|2.5|3.0))/").test(window.navigator.userAgent)&&!e('<input type="file" />').prop("disabled")},l.prototype.init=function(){var n=this;this.queue=[],this.queuePos=-1,this.queueRunning=!1,this.activeFiles=0,this.draggingOver=0,this.draggingOverDoc=0;var i=n.element.is("input[type=file]")?n.element:n.element.find("input[type=file]");return i.length>0&&(i.prop("multiple",this.settings.multiple),i.on("change."+t,function(t){var i=t.target&&t.target.files;i&&i.length&&(n.addFiles(i),e(this).val(""))})),this.settings.dnd&&this.initDnD(),0!==i.length||this.settings.dnd?(this.settings.onInit.call(this.element),this):(e.error("Markup error found by jQuery.dmUploader"),null)},l.prototype.initDnD=function(){var n=this;n.element.on("drop."+t,function(e){e.preventDefault(),n.draggingOver>0&&(n.draggingOver=0,n.settings.onDragLeave.call(n.element));var t=e.originalEvent&&e.originalEvent.dataTransfer;if(t&&t.files&&t.files.length){var i=[];n.settings.multiple?i=t.files:i.push(t.files[0]),n.addFiles(i)}}),n.element.on("dragenter."+t,function(e){e.preventDefault(),0===n.draggingOver&&n.settings.onDragEnter.call(n.element),n.draggingOver++}),n.element.on("dragleave."+t,function(e){e.preventDefault(),n.draggingOver--,0===n.draggingOver&&n.settings.onDragLeave.call(n.element)}),n.settings.hookDocument&&(e(document).off("drop."+t).on("drop."+t,function(e){e.preventDefault(),n.draggingOverDoc>0&&(n.draggingOverDoc=0,n.settings.onDocumentDragLeave.call(n.element))}),e(document).off("dragenter."+t).on("dragenter."+t,function(e){e.preventDefault(),0===n.draggingOverDoc&&n.settings.onDocumentDragEnter.call(n.element),n.draggingOverDoc++}),e(document).off("dragleave."+t).on("dragleave."+t,function(e){e.preventDefault(),n.draggingOverDoc--,0===n.draggingOverDoc&&n.settings.onDocumentDragLeave.call(n.element)}),e(document).off("dragover."+t).on("dragover."+t,function(e){e.preventDefault()}))},l.prototype.releaseEvents=function(){this.element.off("."+t),this.element.find("input[type=file]").off("."+t),this.settings.hookDocument&&e(document).off("."+t)},l.prototype.validateFile=function(t){if(this.settings.maxFileSize>0&&t.size>this.settings.maxFileSize)return this.settings.onFileSizeError.call(this.element,t),!1;if("*"!==this.settings.allowedTypes&&!t.type.match(this.settings.allowedTypes))return this.settings.onFileTypeError.call(this.element,t),!1;if(null!==this.settings.extFilter){var n=t.name.toLowerCase().split(".").pop();if(e.inArray(n,this.settings.extFilter)<0)return this.settings.onFileExtError.call(this.element,t),!1}return new a(t,this)},l.prototype.addFiles=function(e){for(var t=0,n=0;n<e.length;n++){var i=this.validateFile(e[n]);if(i){!1!==this.settings.onNewFile.call(this.element,i.id,i.data)&&(this.settings.auto&&!this.settings.queue&&i.upload(),this.queue.push(i),t++)}}return 0===t?this:(this.settings.auto&&this.settings.queue&&!this.queueRunning&&this.processQueue(),this)},l.prototype.processQueue=function(){return this.queuePos++,this.queuePos>=this.queue.length?(0===this.activeFiles&&this.settings.onComplete.call(this.element),this.queuePos=this.queue.length-1,this.queueRunning=!1,!1):(this.queueRunning=!0,this.queue[this.queuePos].upload())},l.prototype.restartQueue=function(){this.queuePos=-1,this.queueRunning=!1,this.processQueue()},l.prototype.findById=function(e){for(var t=!1,n=0;n<this.queue.length;n++)if(this.queue[n].id===e){t=this.queue[n];break}return t},l.prototype.cancelAll=function(){var e=this.queueRunning;this.queueRunning=!1;for(var t=0;t<this.queue.length;t++)this.queue[t].cancel();e&&this.settings.onComplete.call(this.element)},l.prototype.startAll=function(){if(this.settings.queue)this.restartQueue();else for(var e=0;e<this.queue.length;e++)this.queue[e].upload()},l.prototype.methods={start:function(t){if(this.queueRunning)return!1;var i=!1;return void 0===t||(i=this.findById(t))?i?(i.status===r&&(i.status=n),i.upload()):(this.startAll(),!0):(e.error("File not found in jQuery.dmUploader"),!1)},cancel:function(t){var n=!1;return void 0===t||(n=this.findById(t))?n?n.cancel(!0):(this.cancelAll(),!0):(e.error("File not found in jQuery.dmUploader"),!1)},reset:function(){return this.cancelAll(),this.queue=[],this.queuePos=-1,this.activeFiles=0,!0},destroy:function(){this.cancelAll(),this.releaseEvents(),this.element.removeData(t)}},e.fn.dmUploader=function(n){var i=arguments;if("string"!=typeof n)return this.each(function(){e.data(this,t)||e.data(this,t,new l(this,n))});this.each(function(){var s=e.data(this,t);s instanceof l?"function"==typeof s.methods[n]?s.methods[n].apply(s,Array.prototype.slice.call(i,1)):e.error("Method "+n+" does not exist in jQuery.dmUploader"):e.error("Unknown plugin data found by jQuery.dmUploader")})}});
/*!
 * validate.js 0.13.1
 * http://validatejs.org/
 * (c) 2013-2015 Nicklas Ansman, 2013 Wrapp
 * validate.js may be freely distributed under the MIT license.
*/

(function(a,b,c){"use strict";var d=function(a,b,c){c=e.extend({},e.options,c);var f=e.runValidations(a,b,c);if(f.some(function(a){return e.isPromise(a.error)}))throw new Error("Use validate.async if you want support for promises");return d.processValidationResults(f,c)},e=d;e.extend=function(a){return[].slice.call(arguments,1).forEach(function(b){for(var c in b)a[c]=b[c]}),a},e.extend(d,{version:{major:0,minor:13,patch:1,metadata:null,toString:function(){var a=e.format("%{major}.%{minor}.%{patch}",e.version);return e.isEmpty(e.version.metadata)||(a+="+"+e.version.metadata),a}},Promise:"undefined"!=typeof Promise?Promise:null,EMPTY_STRING_REGEXP:/^\s*$/,runValidations:function(a,b,c){var d,f,g,h,i,j,k,l=[];(e.isDomElement(a)||e.isJqueryElement(a))&&(a=e.collectFormValues(a));for(d in b){g=e.getDeepObjectValue(a,d),h=e.result(b[d],g,a,d,c,b);for(f in h){if(i=e.validators[f],!i)throw k=e.format("Unknown validator %{name}",{name:f}),new Error(k);j=h[f],j=e.result(j,g,a,d,c,b),j&&l.push({attribute:d,value:g,validator:f,globalOptions:c,attributes:a,options:j,error:i.call(i,g,j,d,a,c)})}}return l},processValidationResults:function(a,b){a=e.pruneEmptyErrors(a,b),a=e.expandMultipleErrors(a,b),a=e.convertErrorMessages(a,b);var c=b.format||"grouped";if("function"!=typeof e.formatters[c])throw new Error(e.format("Unknown format %{format}",b));return a=e.formatters[c](a),e.isEmpty(a)?void 0:a},async:function(a,b,c){c=e.extend({},e.async.options,c);var d=c.wrapErrors||function(a){return a};c.cleanAttributes!==!1&&(a=e.cleanAttributes(a,b));var f=e.runValidations(a,b,c);return new e.Promise(function(g,h){e.waitForResults(f).then(function(){var i=e.processValidationResults(f,c);i?h(new d(i,c,a,b)):g(a)},function(a){h(a)})})},single:function(a,b,c){return c=e.extend({},e.single.options,c,{format:"flat",fullMessages:!1}),e({single:a},{single:b},c)},waitForResults:function(a){return a.reduce(function(a,b){return e.isPromise(b.error)?a.then(function(){return b.error.then(function(a){b.error=a||null})}):a},new e.Promise(function(a){a()}))},result:function(a){var b=[].slice.call(arguments,1);return"function"==typeof a&&(a=a.apply(null,b)),a},isNumber:function(a){return"number"==typeof a&&!isNaN(a)},isFunction:function(a){return"function"==typeof a},isInteger:function(a){return e.isNumber(a)&&a%1===0},isBoolean:function(a){return"boolean"==typeof a},isObject:function(a){return a===Object(a)},isDate:function(a){return a instanceof Date},isDefined:function(a){return null!==a&&void 0!==a},isPromise:function(a){return!!a&&e.isFunction(a.then)},isJqueryElement:function(a){return a&&e.isString(a.jquery)},isDomElement:function(a){return!!a&&(!(!a.querySelectorAll||!a.querySelector)&&(!(!e.isObject(document)||a!==document)||("object"==typeof HTMLElement?a instanceof HTMLElement:a&&"object"==typeof a&&null!==a&&1===a.nodeType&&"string"==typeof a.nodeName)))},isEmpty:function(a){var b;if(!e.isDefined(a))return!0;if(e.isFunction(a))return!1;if(e.isString(a))return e.EMPTY_STRING_REGEXP.test(a);if(e.isArray(a))return 0===a.length;if(e.isDate(a))return!1;if(e.isObject(a)){for(b in a)return!1;return!0}return!1},format:e.extend(function(a,b){return e.isString(a)?a.replace(e.format.FORMAT_REGEXP,function(a,c,d){return"%"===c?"%{"+d+"}":String(b[d])}):a},{FORMAT_REGEXP:/(%?)%\{([^\}]+)\}/g}),prettify:function(a){return e.isNumber(a)?100*a%1===0?""+a:parseFloat(Math.round(100*a)/100).toFixed(2):e.isArray(a)?a.map(function(a){return e.prettify(a)}).join(", "):e.isObject(a)?e.isDefined(a.toString)?a.toString():JSON.stringify(a):(a=""+a,a.replace(/([^\s])\.([^\s])/g,"$1 $2").replace(/\\+/g,"").replace(/[_-]/g," ").replace(/([a-z])([A-Z])/g,function(a,b,c){return""+b+" "+c.toLowerCase()}).toLowerCase())},stringifyValue:function(a,b){var c=b&&b.prettify||e.prettify;return c(a)},isString:function(a){return"string"==typeof a},isArray:function(a){return"[object Array]"==={}.toString.call(a)},isHash:function(a){return e.isObject(a)&&!e.isArray(a)&&!e.isFunction(a)},contains:function(a,b){return!!e.isDefined(a)&&(e.isArray(a)?a.indexOf(b)!==-1:b in a)},unique:function(a){return e.isArray(a)?a.filter(function(a,b,c){return c.indexOf(a)==b}):a},forEachKeyInKeypath:function(a,b,c){if(e.isString(b)){var d,f="",g=!1;for(d=0;d<b.length;++d)switch(b[d]){case".":g?(g=!1,f+="."):(a=c(a,f,!1),f="");break;case"\\":g?(g=!1,f+="\\"):g=!0;break;default:g=!1,f+=b[d]}return c(a,f,!0)}},getDeepObjectValue:function(a,b){if(e.isObject(a))return e.forEachKeyInKeypath(a,b,function(a,b){if(e.isObject(a))return a[b]})},collectFormValues:function(a,b){var c,d,f,g,h,i,j={};if(e.isJqueryElement(a)&&(a=a[0]),!a)return j;for(b=b||{},g=a.querySelectorAll("input[name], textarea[name]"),c=0;c<g.length;++c)if(f=g.item(c),!e.isDefined(f.getAttribute("data-ignored"))){var k=f.name.replace(/\./g,"\\\\.");i=e.sanitizeFormValue(f.value,b),"number"===f.type?i=i?+i:null:"checkbox"===f.type?f.attributes.value?f.checked||(i=j[k]||null):i=f.checked:"radio"===f.type&&(f.checked||(i=j[k]||null)),j[k]=i}for(g=a.querySelectorAll("select[name]"),c=0;c<g.length;++c)if(f=g.item(c),!e.isDefined(f.getAttribute("data-ignored"))){if(f.multiple){i=[];for(d in f.options)h=f.options[d],h&&h.selected&&i.push(e.sanitizeFormValue(h.value,b))}else{var l="undefined"!=typeof f.options[f.selectedIndex]?f.options[f.selectedIndex].value:"";i=e.sanitizeFormValue(l,b)}j[f.name]=i}return j},sanitizeFormValue:function(a,b){return b.trim&&e.isString(a)&&(a=a.trim()),b.nullify!==!1&&""===a?null:a},capitalize:function(a){return e.isString(a)?a[0].toUpperCase()+a.slice(1):a},pruneEmptyErrors:function(a){return a.filter(function(a){return!e.isEmpty(a.error)})},expandMultipleErrors:function(a){var b=[];return a.forEach(function(a){e.isArray(a.error)?a.error.forEach(function(c){b.push(e.extend({},a,{error:c}))}):b.push(a)}),b},convertErrorMessages:function(a,b){b=b||{};var c=[],d=b.prettify||e.prettify;return a.forEach(function(a){var f=e.result(a.error,a.value,a.attribute,a.options,a.attributes,a.globalOptions);return e.isString(f)?("^"===f[0]?f=f.slice(1):b.fullMessages!==!1&&(f=e.capitalize(d(a.attribute))+" "+f),f=f.replace(/\\\^/g,"^"),f=e.format(f,{value:e.stringifyValue(a.value,b)}),void c.push(e.extend({},a,{error:f}))):void c.push(a)}),c},groupErrorsByAttribute:function(a){var b={};return a.forEach(function(a){var c=b[a.attribute];c?c.push(a):b[a.attribute]=[a]}),b},flattenErrorsToArray:function(a){return a.map(function(a){return a.error}).filter(function(a,b,c){return c.indexOf(a)===b})},cleanAttributes:function(a,b){function c(a,b,c){return e.isObject(a[b])?a[b]:a[b]=!!c||{}}function d(a){var b,d={};for(b in a)a[b]&&e.forEachKeyInKeypath(d,b,c);return d}function f(a,b){if(!e.isObject(a))return a;var c,d,g=e.extend({},a);for(d in a)c=b[d],e.isObject(c)?g[d]=f(g[d],c):c||delete g[d];return g}return e.isObject(b)&&e.isObject(a)?(b=d(b),f(a,b)):{}},exposeModule:function(a,b,c,d,e){c?(d&&d.exports&&(c=d.exports=a),c.validate=a):(b.validate=a,a.isFunction(e)&&e.amd&&e([],function(){return a}))},warn:function(a){"undefined"!=typeof console&&console.warn&&console.warn("[validate.js] "+a)},error:function(a){"undefined"!=typeof console&&console.error&&console.error("[validate.js] "+a)}}),d.validators={presence:function(a,b){if(b=e.extend({},this.options,b),b.allowEmpty!==!1?!e.isDefined(a):e.isEmpty(a))return b.message||this.message||"can't be blank"},length:function(a,b,c){if(e.isDefined(a)){b=e.extend({},this.options,b);var d,f=b.is,g=b.maximum,h=b.minimum,i=b.tokenizer||function(a){return a},j=[];a=i(a);var k=a.length;return e.isNumber(k)?(e.isNumber(f)&&k!==f&&(d=b.wrongLength||this.wrongLength||"is the wrong length (should be %{count} characters)",j.push(e.format(d,{count:f}))),e.isNumber(h)&&k<h&&(d=b.tooShort||this.tooShort||"is too short (minimum is %{count} characters)",j.push(e.format(d,{count:h}))),e.isNumber(g)&&k>g&&(d=b.tooLong||this.tooLong||"is too long (maximum is %{count} characters)",j.push(e.format(d,{count:g}))),j.length>0?b.message||j:void 0):b.message||this.notValid||"has an incorrect length"}},numericality:function(a,b,c,d,f){if(e.isDefined(a)){b=e.extend({},this.options,b);var g,h,i=[],j={greaterThan:function(a,b){return a>b},greaterThanOrEqualTo:function(a,b){return a>=b},equalTo:function(a,b){return a===b},lessThan:function(a,b){return a<b},lessThanOrEqualTo:function(a,b){return a<=b},divisibleBy:function(a,b){return a%b===0}},k=b.prettify||f&&f.prettify||e.prettify;if(e.isString(a)&&b.strict){var l="^-?(0|[1-9]\\d*)";if(b.onlyInteger||(l+="(\\.\\d+)?"),l+="$",!new RegExp(l).test(a))return b.message||b.notValid||this.notValid||this.message||"must be a valid number"}if(b.noStrings!==!0&&e.isString(a)&&!e.isEmpty(a)&&(a=+a),!e.isNumber(a))return b.message||b.notValid||this.notValid||this.message||"is not a number";if(b.onlyInteger&&!e.isInteger(a))return b.message||b.notInteger||this.notInteger||this.message||"must be an integer";for(g in j)if(h=b[g],e.isNumber(h)&&!j[g](a,h)){var m="not"+e.capitalize(g),n=b[m]||this[m]||this.message||"must be %{type} %{count}";i.push(e.format(n,{count:h,type:k(g)}))}return b.odd&&a%2!==1&&i.push(b.notOdd||this.notOdd||this.message||"must be odd"),b.even&&a%2!==0&&i.push(b.notEven||this.notEven||this.message||"must be even"),i.length?b.message||i:void 0}},datetime:e.extend(function(a,b){if(!e.isFunction(this.parse)||!e.isFunction(this.format))throw new Error("Both the parse and format functions needs to be set to use the datetime/date validator");if(e.isDefined(a)){b=e.extend({},this.options,b);var c,d=[],f=b.earliest?this.parse(b.earliest,b):NaN,g=b.latest?this.parse(b.latest,b):NaN;return a=this.parse(a,b),isNaN(a)||b.dateOnly&&a%864e5!==0?(c=b.notValid||b.message||this.notValid||"must be a valid date",e.format(c,{value:arguments[0]})):(!isNaN(f)&&a<f&&(c=b.tooEarly||b.message||this.tooEarly||"must be no earlier than %{date}",c=e.format(c,{value:this.format(a,b),date:this.format(f,b)}),d.push(c)),!isNaN(g)&&a>g&&(c=b.tooLate||b.message||this.tooLate||"must be no later than %{date}",c=e.format(c,{date:this.format(g,b),value:this.format(a,b)}),d.push(c)),d.length?e.unique(d):void 0)}},{parse:null,format:null}),date:function(a,b){return b=e.extend({},b,{dateOnly:!0}),e.validators.datetime.call(e.validators.datetime,a,b)},format:function(a,b){(e.isString(b)||b instanceof RegExp)&&(b={pattern:b}),b=e.extend({},this.options,b);var c,d=b.message||this.message||"is invalid",f=b.pattern;if(e.isDefined(a))return e.isString(a)?(e.isString(f)&&(f=new RegExp(b.pattern,b.flags)),c=f.exec(a),c&&c[0].length==a.length?void 0:d):d},inclusion:function(a,b){if(e.isDefined(a)&&(e.isArray(b)&&(b={within:b}),b=e.extend({},this.options,b),!e.contains(b.within,a))){var c=b.message||this.message||"^%{value} is not included in the list";return e.format(c,{value:a})}},exclusion:function(a,b){if(e.isDefined(a)&&(e.isArray(b)&&(b={within:b}),b=e.extend({},this.options,b),e.contains(b.within,a))){var c=b.message||this.message||"^%{value} is restricted";return e.isString(b.within[a])&&(a=b.within[a]),e.format(c,{value:a})}},email:e.extend(function(a,b){b=e.extend({},this.options,b);var c=b.message||this.message||"is not a valid email";if(e.isDefined(a))return e.isString(a)&&this.PATTERN.exec(a)?void 0:c},{PATTERN:/^(?:[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])$/i}),equality:function(a,b,c,d,f){if(e.isDefined(a)){e.isString(b)&&(b={attribute:b}),b=e.extend({},this.options,b);var g=b.message||this.message||"is not equal to %{attribute}";if(e.isEmpty(b.attribute)||!e.isString(b.attribute))throw new Error("The attribute must be a non empty string");var h=e.getDeepObjectValue(d,b.attribute),i=b.comparator||function(a,b){return a===b},j=b.prettify||f&&f.prettify||e.prettify;return i(a,h,b,c,d)?void 0:e.format(g,{attribute:j(b.attribute)})}},url:function(a,b){if(e.isDefined(a)){b=e.extend({},this.options,b);var c=b.message||this.message||"is not a valid url",d=b.schemes||this.schemes||["http","https"],f=b.allowLocal||this.allowLocal||!1,g=b.allowDataUrl||this.allowDataUrl||!1;if(!e.isString(a))return c;var h="^(?:(?:"+d.join("|")+")://)(?:\\S+(?::\\S*)?@)?(?:",i="(?:\\.(?:[a-z\\u00a1-\\uffff]{2,}))";if(f?i+="?":h+="(?!(?:10|127)(?:\\.\\d{1,3}){3})(?!(?:169\\.254|192\\.168)(?:\\.\\d{1,3}){2})(?!172\\.(?:1[6-9]|2\\d|3[0-1])(?:\\.\\d{1,3}){2})",h+="(?:[1-9]\\d?|1\\d\\d|2[01]\\d|22[0-3])(?:\\.(?:1?\\d{1,2}|2[0-4]\\d|25[0-5])){2}(?:\\.(?:[1-9]\\d?|1\\d\\d|2[0-4]\\d|25[0-4]))|(?:(?:[a-z\\u00a1-\\uffff0-9]-*)*[a-z\\u00a1-\\uffff0-9]+)(?:\\.(?:[a-z\\u00a1-\\uffff0-9]-*)*[a-z\\u00a1-\\uffff0-9]+)*"+i+")(?::\\d{2,5})?(?:[/?#]\\S*)?$",g){var j="\\w+\\/[-+.\\w]+(?:;[\\w=]+)*",k="[A-Za-z0-9-_.!~\\*'();\\/?:@&=+$,%]*",l="data:(?:"+j+")?(?:;base64)?,"+k;h="(?:"+h+")|(?:^"+l+"$)"}var m=new RegExp(h,"i");return m.exec(a)?void 0:c}},type:e.extend(function(a,b,c,d,f){if(e.isString(b)&&(b={type:b}),e.isDefined(a)){var g=e.extend({},this.options,b),h=g.type;if(!e.isDefined(h))throw new Error("No type was specified");var i;if(i=e.isFunction(h)?h:this.types[h],!e.isFunction(i))throw new Error("validate.validators.type.types."+h+" must be a function.");if(!i(a,g,c,d,f)){var j=b.message||this.messages[h]||this.message||g.message||(e.isFunction(h)?"must be of the correct type":"must be of type %{type}");return e.isFunction(j)&&(j=j(a,b,c,d,f)),e.format(j,{attribute:e.prettify(c),type:h})}}},{types:{object:function(a){return e.isObject(a)&&!e.isArray(a)},array:e.isArray,integer:e.isInteger,number:e.isNumber,string:e.isString,date:e.isDate,"boolean":e.isBoolean},messages:{}})},d.formatters={detailed:function(a){return a},flat:e.flattenErrorsToArray,grouped:function(a){var b;a=e.groupErrorsByAttribute(a);for(b in a)a[b]=e.flattenErrorsToArray(a[b]);return a},constraint:function(a){var b;a=e.groupErrorsByAttribute(a);for(b in a)a[b]=a[b].map(function(a){return a.validator}).sort();return a}},d.exposeModule(d,this,a,b,c)}).call(this,"undefined"!=typeof exports?exports:null,"undefined"!=typeof module?module:null,"undefined"!=typeof define?define:null);
//# sourceMappingURL=validate.min.map

(function(f){function w(a){return Object.keys(a).reduce(function(b,c){if("object"===typeof a[c]&&a[c]){var d=w(a[c]);Object.keys(d).forEach(function(e){b[c+"."+e]=d[e]})}else b[c]=a[c];return b},{})}function x(a,b,c){for(var d=a,e=b.length-1,g=0;g<e;g++)void 0===d[b[g]]&&(d[b[g]]={}),d=d[b[g]];d[b[e]]=c;return a}function y(a,b,c){return a.replace("%from",b).replace("%to",c)}var v="",q=window.nc_mixin_settings_editor=function(a){if(!(this instanceof q))return new q(a);for(var b in a)b in this&&(this[b]=
a[b]);this.scope=this.scope||this.field_name_prefix||"Index";a=f(this.target);a.html(f(".nc-mixins-editor-template").html());this.container=a.find(".nc-mixins-editor");this.lang=this.container.data("lang");this.set_field_names();this.init_breakpoint_type_container();this.find(".nc-mixins-breakpoint-type-select").val(this.breakpoint_type);this.breakpoints={};this.breakpoints[this.MAX_WIDTH]=this.MAX_WIDTH.toString();this.mixin_type_order=this.get_mixin_type_order();this.set_own_settings(this.own_settings);
this.init_preset_container();this.apply_preset_settings();this.rebuild_table();this.init_event_handlers();var c=this;this.find(".nc-mixins-mixin-settings-lock-sides-button").each(function(){c.init_lock_sides_button(f(this))});this.load_fonts();this.find("select.nc-mixins-mixin-font-select").each(function(){c.init_font_select(f(this))});this.init_color_inputs(this.find("[data-color-input]"));this.init_uploader(this.find("[data-uploader]"));this.is_initialized=!0;this.update_mixin_json()};q.mixin_settings_set_values_event=
"mixin_settings_set_values_event";q.mixin_settings_new_row_event="mixin_settings_new_row_event";q.breakpoint_type_change_event="breakpoint_type_change";q.prototype={MAX_WIDTH:9999,is_initialized:!1,target:"#nc_mixins_editor_container",field_name_template:"data[%s]",field_name_prefix:"",scope:null,mixin_presets:[],own_settings:{},breakpoint_type:"block",component_template_id:void 0,infoblock_id:void 0,show_preset_select:!1,show_breakpoint_type_select:!1,lang:{},fonts:[],container:null,selectors:{},
breakpoints:{},inherited_settings:{},temporary_own_settings:{},mixin_type_order:[],calculated_colors:{},constructor:q,find:function(a){return this.container.find(a)},init_event_handlers:function(){var a=this;this.find(".nc-mixins-preset-select").change(f.proxy(this,"on_preset_select_change"));this.find(".nc-mixins-preset-actions .nc--edit").click(f.proxy(this,"open_preset_edit_dialog"));this.find(".nc-mixins-preset-actions .nc--remove").click(f.proxy(this,"open_preset_delete_dialog"));this.find(".nc-mixins-breakpoint-type-select").change(f.proxy(this,
"on_breakpoint_type_select_change")).change();this.find(".nc-mixins-selector-select").change(f.proxy(this,"on_selector_select_change"));this.container.on("click",".nc-mixins-breakpoint-add-button",f.proxy(this,"show_new_breakpoint_dialog"));this.container.on("click",".nc-mixins-breakpoint",f.proxy(this,"show_edit_breakpoint_dialog"));this.container.on("click",".nc-mixins-add-setting, .nc-mixins-settings-marker",f.proxy(this,"show_mixin_settings"));this.container.on("click",".nc-mixins-settings-cell:not(.nc--active)",
function(){var b=f(this).prev(),c=b.find(".nc-mixins-settings-marker");c.length?c.click():b.find(".nc-mixins-add-setting").click()});this.container.on("change",".nc-mixins-mixin-select",function(){a.on_mixin_select_change(f(this),!1)});this.container.on("change input",".nc-mixins-mixin-settings :input",f.proxy(this,"update_current_mixin_settings"));this.container.on("click",".nc-mixins-mixin-remove",f.proxy(this,"on_remove_settings_button_click"));this.container.on("click",".nc-mixins-mixin-settings-row-add",
f.proxy(this,"on_mixin_multiple_settings_add_button_click"));this.container.on("click",".nc-mixins-mixin-settings-row-remove",f.proxy(this,"on_mixin_multiple_settings_remove_button_click"))},get_field_name:function(a){return this.field_name_template.replace("%s",(this.field_name_prefix?this.field_name_prefix+"_":"")+a)},set_field_names:function(){var a={".nc-mixins-json":"Mixin_Settings",".nc-mixins-preset-select":"Mixin_Preset_ID",".nc-mixins-breakpoint-type-select":"Mixin_BreakpointType"},b;for(b in a)this.find(b).attr("name",
this.get_field_name(a[b]))},get_mixin_input_values:function(a){var b={};a.find("input, select, textarea").filter('[name^="mixin."]').each(function(){var c=f(this),d=this.name.replace(/^mixin\./,""),e=c.val();if(!c.is(":radio")||c.is(":checked")){if(0<=d.indexOf("#")){if(c.closest(".nc-mixins-mixin-settings-row-template").length)return;var g=c.closest(".nc-mixins-mixin-settings-row");g=g.parent().children().index(g);0<=g&&(d=d.replace(/#/g,g))}c.is(":checkbox")?b[d]=c.is(":checked")?e:"":c.is('[data-color-input][data-nc-swatches][data-sync-color^="var"]')?
b[d]=c.attr("data-sync-color"):b[d]=e}});return b},set_inherited_settings:function(a){var b=!f.isEmptyObject(this.inherited_settings),c=!f.isEmptyObject(a);if(c){b=Object.keys(this.breakpoints);var d=this.get_breakpoints_as_array_from_settings(a);b=b.filter(function(g){return 0>d.indexOf(g)});if(b.length){for(var e=0;e<b.length;e++)this.remove_breakpoint(b[e]);this.after_breakpoint_change()}}else b&&this.set_own_settings(f.extend({},this.inherited_settings,this.own_settings));this.container.toggleClass("nc-mixins-editor--with-preset",
c);this.inherited_settings=a;this.extract_selectors_and_breakpoints(a)},set_own_settings:function(a){this.own_settings=a;this.extract_selectors_and_breakpoints(a)},extract_selectors_and_breakpoints:function(a){for(var b in a){this.selectors[b]=b;for(var c in a[b])for(var d in a[b][c])this.breakpoints[d]=d}this.sort_breakpoints();this.on_breakpoint_number_change();this.update_selectors_select()},get_breakpoints_as_array_from_settings:function(a){var b={},c;for(c in a)for(var d in a[c])for(var e in a[c][d])b[e]=
e;return Object.keys(b)},sort_breakpoints:function(){var a=Object.keys(this.breakpoints).sort(function(d,e){return d-e});this.breakpoints={};for(var b=0;b<a.length;b++){var c=a[b];this.breakpoints[c]=c}},sort_mixins:function(a){var b={};f.each(this.mixin_type_order,function(c,d){d in a&&(b[d]=a[d])});return b},get_mixin_type_order:function(){var a=[];this.find(".nc-mixins-mixin-row").each(function(){a.push(f(this).data("mixinType"))});return a},on_breakpoint_number_change:function(){this.container.attr("data-breakpoint-number",
Object.keys(this.breakpoints).length)},show_new_breakpoint_dialog:function(a){var b=f(a.target).closest("td");a=parseInt(b.data("breakpoint"),10);b=parseInt(b.prev().data("breakpoint"),10)||0;var c=this.lang,d=c.BREAKPOINT_ADD_PROMPT;if(b||a)d+=" "+y(c.BREAKPOINT_ADD_PROMPT_RANGE,b,a);(c=parseInt(prompt(d+":"),10))&&c>b&&c<a&&this.add_new_breakpoint(c)},add_new_breakpoint:function(a){this.breakpoints[a]=a;this.sort_breakpoints();this.on_breakpoint_number_change();this.rebuild_table()},show_edit_breakpoint_dialog:function(a){if(!this.container.is(".nc-mixins-editor--with-preset")){a=
f(a.target).closest(".nc-mixins-width").data("breakpoint");var b=prompt(this.lang.BREAKPOINT_CHANGE_PROMPT,a);b!=a&&null!==b&&(""===b||"0"===b?this.remove_breakpoint(a):this.replace_breakpoint(a,b),this.after_breakpoint_change())}},replace_breakpoint:function(a,b){var c=this.own_settings,d;for(d in c)for(var e in c[d])c[d][e][a]&&(c[d][e][b]=c[d][e][a]);this.breakpoints[b]=b;this.remove_breakpoint(a)},remove_breakpoint:function(a){var b=this.own_settings,c;for(c in b)for(var d in b[c])delete this.own_settings[c][d][a];
delete this.breakpoints[a]},after_breakpoint_change:function(){this.temporary_own_settings={};this.close_opened_mixin_settings();this.sort_breakpoints();this.on_breakpoint_number_change();this.rebuild_table();this.update_mixin_json()},update_selectors_select:function(a){var b=this.find(".nc-mixins-selector-select"),c=b.find("option:last"),d=Object.keys(this.selectors).sort();void 0===a&&(a=b.val());b.find(".nc-mixins-selector-select-selector").remove();for(var e=0;e<d.length;e++){var g=d[e];g&&f("<option>",
{value:g,text:g,"class":"nc-mixins-selector-select-selector"}).insertBefore(c)}b.val(a in this.selectors?a:"").change()},rebuild_table:function(){var a=Object.keys(this.breakpoints).map(Number);this.temporary_own_settings={};this.rebuild_table_head_width_ranges(a);this.rebuild_table_body_width_ranges(a)},rebuild_table_head_width_ranges:function(a){var b=a.length,c="";this.find(".nc-mixins-width-icon").prop("colspan",b);this.find(".nc-mixins-width").remove();for(var d=0;d<b;d++){var e=a[d];c+='<td class="nc-mixins-width nc-mixins-width-head" data-breakpoint="'+
e+'"><div class="nc-mixins-breakpoint-add-button-container"><div class="nc-mixins-breakpoint-add-button" title="'+this.lang.BREAKPOINT_ADD+'">+</div></div><div class="nc-mixins-breakpoint"><span>'+(e>=this.MAX_WIDTH?"&#x2731;":e)+"</span></div></td>"}c=f(c);c.first().addClass("nc-mixins-width--first");c.last().addClass("nc-mixins-width--last");this.find(".nc-mixins-width-ranges").prepend(c)},rebuild_table_body_width_ranges:function(a){var b=this;this.find(".nc-mixins-mixin-row").each(function(){var c=
f(this),d="",e=a.length;c.find(".nc-mixins-width").remove();for(var g=0;g<e;g++)d+='<td class="nc-mixins-width" data-breakpoint="'+a[g]+'"><div class="nc-mixins-add-setting-container"><div class="nc-mixins-add-setting">+</div></div></td>';d=f(d);d.first().addClass("nc-mixins-width--first");d.last().addClass("nc-mixins-width--last");c.prepend(d);b.update_row_markers(c);d=c.data("mixin-scopes")||[];c.toggle(-1!==d.indexOf(b.scope))})},update_row_markers:function(a){var b=-1,c=0,d=a.find(".nc-mixins-mixin-type-name").html(),
e=this,g=a.data("mixinType");a.find(".nc-mixins-width").each(function(h){var k=f(this),l=k.data("breakpoint"),m=e.make_range_description(c,l);k.prop("title",d+" "+m).data("range",m);k.find(".nc-mixins-settings-marker, .nc-mixins-base-settings-marker").remove();k.removeClass(function(r,t){return(t.match(/\bnc-mixins-width--span-\S+/g)||[]).join(" ")});1<h-b&&k.addClass("nc-mixins-width--span-"+(h-b));m=!!e.get_settings(e.own_settings,g,l)||!!e.get_settings(e.temporary_own_settings,g,l);var p=!!e.get_settings(e.inherited_settings,
g,l),n=e.get_current_selector()&&!!e.get_settings(e.own_settings,g,l,"");m?k.append('<div class="nc-mixins-settings-marker nc-mixins-own-settings-marker">\u25cf</div>'):p?k.append('<div class="nc-mixins-settings-marker nc-mixins-inherited-settings-marker">\ud83e\udc61</div>'):n&&k.append('<div class="nc-mixins-base-settings-marker">\u25cb</div>');k.toggleClass("nc-mixins-width--with-own-settings",m);k.toggleClass("nc-mixins-width--with-inherited-settings",p);if(m||p)b=h,c=l})},get_active_cell_position:function(){var a=
this.container.find(".nc-mixins-settings-marker.nc--active");if(!a.length)return null;a=a.closest(".nc-mixins-width");var b=a.closest(".nc-mixins-mixin-row");return{row:b.closest("tbody").children().index(b),cell:b.children().index(a)}},restore_active_cell_position:function(a){a&&this.container.find(".nc-mixins-table tbody").children().eq(a.row).children().eq(a.cell).find(".nc-mixins-settings-marker, .nc-mixins-add-setting").first().click()},get_current_selector:function(){return this.find(".nc-mixins-selector-select").val()},
on_selector_select_change:function(){if("+"===this.find(".nc-mixins-selector-select").val())this.show_add_selector_dialog();else{var a=this.get_active_cell_position();this.rebuild_table();this.restore_active_cell_position(a)}},show_add_selector_dialog:function(){var a=prompt(this.lang.SELECTOR_ADD_PROMPT);!a||a in this.selectors||(this.selectors[a]=a);this.update_selectors_select(a||"")},get_settings:function(a,b,c,d){var e;(e=a[void 0===d?this.get_current_selector():d])&&(e=e[b])&&(e=e[c]);return e},
remove_settings:function(a,b,c){var d=this.get_current_selector();a[d]&&a[d][b]&&(delete a[d][b][c],f.isEmptyObject(a[d][b])&&delete a[d][b])},make_range_description:function(a,b){if(2>Object.keys(this.breakpoints).length)return"";var c=this.breakpoint_type;c="FOR_"+(c?c.toUpperCase()+"_":"")+"WIDTH_";c=0==a?c+(b==this.MAX_WIDTH?"ANY":"TO"):b==this.MAX_WIDTH?c+"FROM":c+"RANGE";return y(this.lang[c]||"",a,b-1)},init_breakpoint_type_container:function(){var a=this.find(".nc-mixins-breakpoint-type-container");
this.show_breakpoint_type_select?a.show():a.hide().find("select").attr("name","")},set_breakpoint_type_from_select:function(){this.breakpoint_type=this.find(".nc-mixins-breakpoint-type-select").val()||""},on_breakpoint_type_select_change:function(){var a=this;a.set_breakpoint_type_from_select();a.find(".nc-mixins-mixin-row").each(function(){var b=f(this),c=a.get_active_cell_position();a.update_row_markers(b);a.restore_active_cell_position(c)});this.container.trigger(q.breakpoint_type_change_event,
[{editor:this}])},on_preset_select_change:function(){"+"===this.find(".nc-mixins-preset-select").val()?this.open_preset_edit_dialog():this.apply_preset_settings()},open_preset_edit_dialog:function(){var a=this.get_selected_preset_option(),b=a.data("id");a="+"===a.val();var c={component_template_id:this.component_template_id,"data[Scope]":this.scope};if("0"!==b){a?c["data[Mixin_Settings]"]=this.find(".nc-mixins-json").val():c.mixin_preset_id=b;var d=this;nc.load_dialog(this.container.data("preset-edit-dialog-url"),
c,"POST").set_option("on_submit_response",function(e){e=parseInt(e,10);var g=this.find('input[name="data[Mixin_Preset_Name]"]').val(),h=this.find(".nc-mixins-json").val(),k=this.find(':checkbox[name="set_as_default"]').is(":checked");e&&d.update_preset(e,g,h,k);this.destroy()});a&&this.find(".nc-mixins-preset-select").val("-1").change()}},open_preset_delete_dialog:function(){var a={mixin_preset_id:this.get_selected_preset_option().data("id")},b=this;nc.load_dialog(this.container.data("preset-delete-dialog-url"),
a,"POST").set_option("on_submit_response",function(c){(c=parseInt(c,10))&&b.delete_preset(c);this.destroy()})},init_preset_container:function(){var a=this.find(".nc-mixins-preset-container");this.show_preset_select?(this.init_preset_select(a),a.show()):a.hide().find("select").attr("name","")},init_preset_select:function(a){var b=a.find(".nc-mixins-preset-select");b.find("option").remove();this.mixin_presets.length?f.each(this.mixin_presets,function(c,d){f("<option>").attr({value:d.value,selected:d.selected,
"data-id":d.id,"data-settings":d.settings}).html(d.name).appendTo(b)}):b.append("<option>");b.append('<option value="+">'+this.lang.PRESET_CREATE)},update_preset:function(a,b,c,d){var e=this.find(".nc-mixins-preset-select"),g=e.find('option[data-id="'+a+'"]'),h=e.find('option[value="-1"]'),k=h.data("id")==a;g.length||(g=f("<option>").val(a).insertBefore(e.find("option:last")),e.val(d?"-1":a));d&&(g=g.add(h));g.html(b).attr({"data-id":a,"data-settings":c}).data({id:a,settings:JSON.parse(c)});d?h.html(this.lang.PRESET_DEFAULT.replace("%s",
b)):k&&this.remove_default_preset(a);e.change()},delete_preset:function(a){var b=this.find(".nc-mixins-preset-select");b.find('option[value="'+a+'"]').remove();this.remove_default_preset(a);b.val("-1").change()},remove_default_preset:function(a){this.find(".nc-mixins-preset-select").find('option[value="-1"][data-id="'+a+'"]').attr({"data-id":"0","data-settings":""}).data({id:0,settings:{}}).html(this.lang.PRESET_DEFAULT_NONE)},get_selected_preset_option:function(){return this.find(".nc-mixins-preset-select option:selected")},
apply_preset_settings:function(){if(this.show_preset_select){var a=this.get_selected_preset_option();this.set_inherited_settings(a.data("settings")||{});this.rebuild_table();this.find(".nc-mixins-preset-actions").toggle(!!a.data("id"));this.update_mixin_json()}},close_opened_mixin_settings:function(){var a=this;this.find(".nc-mixins-settings-cell.nc--active").removeClass("nc--active").each(function(){var b=f(this);if(b.is(".nc-mixins-settings-cell--temporary")){var c=b.closest("tr");a.remove_settings(a.temporary_own_settings,
c.data("mixinType"),b.data("breakpoint"));a.update_row_markers(c)}})},show_mixin_settings:function(a){a=f(a.target);var b=a.closest("td"),c=b.closest("tr"),d=c.find(".nc-mixins-settings-cell"),e=c.data("mixinType"),g=b.data("breakpoint");c=d.find(".nc-mixins-mixin-select");if(!a.is(".nc--active")){this.close_opened_mixin_settings();d.find(".nc-mixins-mixin-range-description").html(b.data("range"));d.data("breakpoint",g);d.addClass("nc--active");e=this.get_mixin_settings_values(e,g);g=e.mixin;var h=
d.find('.nc-mixins-mixin-settings[data-mixin-keyword="'+g+'"]');c.val(g||"");this.on_mixin_select_change(c,!0);this.set_mixin_input_values(h,e,!0);d.toggleClass("nc-mixins-settings-cell--temporary",!a.is(".nc-mixins-own-settings-marker"));a.is(".nc-mixins-add-setting")&&this.update_current_mixin_settings(!0);this.find(".nc-mixins-settings-marker").removeClass("nc--active");b.find(".nc-mixins-settings-marker").addClass("nc--active")}},set_mixin_input_values:function(a,b,c){a.find(".nc-mixins-mixin-settings-rows").children().remove();
for(var d in b)if(/^settings\.(.+\.)?lock_sides$/.test(d)){var e={};e[d]=b[d];b=f.extend(e,b)}for(d in b){var g=(d.match(/\.(\d+)\./)||[])[1];e=void 0!==g?this.get_mixin_multiple_settings_container(a,g):a;g="mixin."+d.replace("."+g+".",".#.");e=e.find('[name="'+g+'"]');e.is(":checkbox")?e.prop("checked",!!b[d]):e.is(":radio")?e.filter('[value="'+b[d].replace('"','\\"')+'"]').prop("checked",!0):e.is("[data-color-input]")?(e.attr("data-sync-color",b[d]),this.set_color_input(e,b[d])):e.val(b[d]);c&&
e.change()}a.trigger(q.mixin_settings_set_values_event,[{editor:this}])},get_mixin_multiple_settings_container:function(a,b){b=parseInt(b,10);var c=a.find(".nc-mixins-mixin-settings-rows"),d=c.find(".nc-mixins-mixin-settings-row"),e=d.length,g=a.find(".nc-mixins-mixin-settings-row-template");if(e>b)return d.eq(b);if(!c.length||!g.length)return a;for(d=a;e++<b+1;)d=g.clone().removeClass("nc-mixins-mixin-settings-row-template").appendTo(c),this.init_color_inputs(d.find("[data-color-input]")),this.init_uploader(d.find("[data-uploader]"));
0<g.find(".nc-mixins-mixin-settings-row-move").length&&c.sortable({handle:".nc-mixins-mixin-settings-row-move",containment:"parent",update:f.proxy(this,"update_current_mixin_settings")});return d},on_mixin_multiple_settings_add_button_click:function(a){a=f(a.target).closest(".nc-mixins-mixin-settings");var b=a.find(".nc-mixins-mixin-settings-rows .nc-mixins-mixin-settings-row").length;b=this.get_mixin_multiple_settings_container(a,b);a.trigger(q.mixin_settings_new_row_event,[{editor:this,row:b}]);
this.update_current_mixin_settings();return!1},on_mixin_multiple_settings_remove_button_click:function(a){f(a.target).closest(".nc-mixins-mixin-settings-row").remove();this.update_current_mixin_settings();return!1},get_mixin_settings_values:function(a,b){var c=this.get_settings(this.own_settings,a,b)||this.get_settings(this.inherited_settings,a,b);if(!c){var d=Object.keys(this.breakpoints).map(Number),e=d.indexOf(b);e<d.length-1&&(c=this.get_mixin_settings_values(a,d[e+1]))}return w(c||{})},on_mixin_select_change:function(a,
b){var c=a.closest(".nc-mixins-settings-cell");c.find(".nc-mixins-mixin-settings").hide().filter('[data-mixin-keyword="'+a.val()+'"]').show().trigger(q.mixin_settings_set_values_event,[{editor:this}]);b||(c.removeClass("nc-mixins-settings-cell--temporary"),this.update_current_mixin_settings())},update_current_mixin_settings:function(a){var b=this;b.find(".nc-mixins-settings-cell.nc--active").each(function(){var c=f(this),d=b.get_current_selector(),e=c.closest(".nc-mixins-mixin-row"),g=e.data("mixinType"),
h=c.find(".nc-mixins-mixin-select").val(),k=c.data("breakpoint"),l=!1,m="own_settings";!0===a?(l=!0,m="temporary_own_settings"):c.hasClass("nc-mixins-settings-cell--temporary")&&(c.removeClass("nc-mixins-settings-cell--temporary"),l=!0);x(b[m],[d,g,k],{mixin:h});c.find('.nc-mixins-mixin-settings[data-mixin-keyword="'+h+'"]').each(function(){var p=b.get_mixin_input_values(f(this)),n=f.extend,r=b[m][d][g][k],t={},u;for(u in p)x(t,u.split("."),p[u]);n.call(f,r,t)});l&&(b.update_row_markers(e),e.find('.nc-mixins-width[data-breakpoint="'+
k+'"] .nc-mixins-settings-marker').addClass("nc--active"))});!0!==a&&this.update_mixin_json()},update_mixin_json:function(){if(this.is_initialized){var a=f.extend({},this.own_settings),b;for(b in a)f.isEmptyObject(a[b])?delete a[b]:a[b]=this.sort_mixins(a[b]);this.find(".nc-mixins-json").val(JSON.stringify(a))}},on_remove_settings_button_click:function(a){var b=f(a.target).closest(".nc-mixins-settings-cell");a=b.closest("tr");var c=a.data("mixinType");b=b.data("breakpoint");this.remove_settings(this.temporary_own_settings,
c,b);this.remove_settings(this.own_settings,c,b);this.update_row_markers(a);this.close_opened_mixin_settings();this.update_mixin_json()},init_lock_sides_button:function(a){var b=a.closest(".nc-mixins-mixin-settings-lock-sides, .nc-mixins-mixin-settings"),c=b.find(':hidden[name^="mixin.settings"][name$=".lock_sides"]'),d=b.find(":input");c.change(function(){a.removeClass("nc-icon-lock nc-icon-unlock").addClass(c.val()?"nc-icon-lock":"nc-icon-unlock")}).change();a.click(function(){c.val(c.val()?"":
"1").change();d.filter('[name*="top"]').change()});var e=/(top|right|bottom|left)/;d.on("change input blur",function(){if("1"===c.val()){var g=this.attributes,h=f(this).val(),k=new RegExp(this.name.replace(/\./g,"\\.").replace(e,"(top|right|bottom|left)")),l=d.filter(function(){return this.name&&this.name.match(k)});l.val(h);f.each(g,function(m,p){p.name.indexOf("data-sync-")||l.attr(p.name,p.value)});d.filter(".minicolors-input").each(function(){var m=f(this);m.minicolors("value",m.val())})}})},
get_font_options_html:function(){v||f.each(this.fonts,function(a,b){v+="<option name='"+b.name+"' data-asset='"+b.asset+"' data-fallback='"+b.fallback+"' style='font-family:&quot;"+b.name+"&quot;'>"+b.name+"</option>"});return v},init_font_select:function(a){a.html(a.html()+this.get_font_options_html())},init_color_inputs:function(a){var b=this.infoblock_id?this.infoblock_id:0,c=f(".tpl-block-"+b+", .tpl-container-"+b),d={format:"rgb",keywords:"transparent",opacity:!0,swatches:[],theme:"netcat"},
e=["--tpl-color-foreground-main","--tpl-color-foreground-accent","--tpl-color-brand","--tpl-color-background-accent","--tpl-color-background-main"],g={};0<b&&0<c.length&&(f.each(e,function(h,k){var l=getComputedStyle(c[0]).getPropertyValue(k);l&&(d.swatches.push({name:"var("+k+")",color:l.trim()}),g["var("+k+")"]=l.trim())}),0<d.swatches.length&&a.each(function(){var h=f(this);g.hasOwnProperty(h.attr("data-sync-color"))&&h.val(g[h.attr("data-sync-color")])}));this.calculated_colors=g;a.minicolors("destroy").minicolors(d);
"undefined"!==typeof a.data("nc-swatches")&&(f(".minicolors-swatch-color").on("click",function(){var h=f(this);if(!h.attr("title"))return!0;h.closest(".minicolors-panel").siblings(".minicolors-input").attr("data-sync-color",h.attr("title"))}),f(".minicolors-panel *").on("click",function(h){h=f(h.target);if(h.is(".minicolors-swatches")||0<h.closest(".minicolors-swatches").length)return!0;f(this).closest(".minicolors-panel").siblings(".minicolors-input").attr("data-sync-color","").change()}))},set_color_input:function(a,
b){0===b.indexOf("var")&&this.calculated_colors.hasOwnProperty(b)?a.minicolors("value",{color:this.calculated_colors[b]}):a.minicolors("value",{color:b})},load_fonts:function(){var a=f("head");this.fonts=this.container.data("fonts");f.each(this.fonts,function(b,c){f.each(c.css,function(d,e){f('link[href="'+e+'"]',a).length||a.append(f("<link>",{rel:"stylesheet",href:e}))})})},get_infoblock_element:function(){var a=this.infoblock_id;return a?document.querySelector(".tpl-block-"+a+", .tpl-container-"+
a):null},init_uploader:function(a){var b=this;a.each(function(){var c=$nc(this),d=c.data("uploader-allowed"),e=c.data("uploader-ext"),g=c.find('input[type="url"]'),h=c.find('input[type="file"]'),k=c.find(".js-mixin-upload");e&&(e=e.split(/\s*,\s*/));k.on("click",function(){var l=c.closest(".nc-mixins-mixin-settings").data("mixin-keyword"),m=c.closest(".nc-mixins-editor").data("upload-max-filesize"),p=nc.ui.modal_dialog.get_current_dialog();c.dmUploader({url:NETCAT_PATH+"action.php?ctrl=admin.mixin_file&action=save&isNaked=1&admin_modal=1",
maxFileSize:m,multiple:!1,allowedTypes:d,extFilter:e,dataType:"json",extraData:{scope:b.scope,infoblock_id:b.infoblock_id,mixin_keyword:l},onDragEnter:function(){this.addClass("active")},onDragLeave:function(){this.removeClass("active")},onInit:function(){h.trigger("click")},onComplete:function(){c.dmUploader("destroy")},onBeforeUpload:function(n){g.addClass("nc--loading")},onUploadSuccess:function(n,r){g.removeClass("nc--loading").val(r.url).trigger("change")},onUploadError:function(n,r,t,u){c.dmUploader("destroy")},
onFallbackMode:function(){try{p.show_error("\u0412\u0430\u0448 \u0431\u0440\u0430\u0443\u0437\u0435\u0440 \u043d\u0435 \u043f\u043e\u0434\u0434\u0435\u0440\u0436\u0438\u0432\u0430\u0435\u0442 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0438\u0447\u0435\u0441\u043a\u0443\u044e \u0437\u0430\u0433\u0440\u0443\u0437\u043a\u0443 \u0444\u0430\u0439\u043b\u043e\u0432")}catch(n){alert("\u0412\u0430\u0448 \u0431\u0440\u0430\u0443\u0437\u0435\u0440 \u043d\u0435 \u043f\u043e\u0434\u0434\u0435\u0440\u0436\u0438\u0432\u0430\u0435\u0442 \u0430\u0432\u0442\u043e\u043c\u0430\u0442\u0438\u0447\u0435\u0441\u043a\u0443\u044e \u0437\u0430\u0433\u0440\u0443\u0437\u043a\u0443 \u0444\u0430\u0439\u043b\u043e\u0432")}c.dmUploader("destroy")},
onFileSizeError:function(n){try{p.show_error("\u0424\u0430\u0439\u043b '"+n.name+"' \u0441\u043b\u0438\u0448\u043a\u043e\u043c \u0431\u043e\u043b\u044c\u0448\u043e\u0439")}catch(r){alert("\u0424\u0430\u0439\u043b '"+n.name+"' \u0441\u043b\u0438\u0448\u043a\u043e\u043c \u0431\u043e\u043b\u044c\u0448\u043e\u0439")}c.dmUploader("destroy")},onFileTypeError:function(n){try{p.show_error("\u0424\u0430\u0439\u043b '"+n.name+"' \u043d\u0435 \u044f\u0432\u043b\u044f\u0435\u0442\u0441\u044f \u0444\u0430\u0439\u043b\u043e\u043c \u0438\u0437\u043e\u0431\u0440\u0430\u0436\u0435\u043d\u0438\u044f")}catch(r){alert("\u0424\u0430\u0439\u043b '"+
n.name+"' \u043d\u0435 \u044f\u0432\u043b\u044f\u0435\u0442\u0441\u044f \u0444\u0430\u0439\u043b\u043e\u043c \u0438\u0437\u043e\u0431\u0440\u0430\u0436\u0435\u043d\u0438\u044f")}c.dmUploader("destroy")},onFileExtError:function(n){try{p.show_error("\u0424\u0430\u0439\u043b '"+n.name+"' \u0438\u043c\u0435\u0435\u0442 \u043d\u0435\u0432\u0435\u0440\u043d\u044b\u0439 \u0444\u043e\u0440\u043c\u0430\u0442")}catch(r){alert("\u0424\u0430\u0439\u043b '"+n.name+"' \u0438\u043c\u0435\u0435\u0442 \u043d\u0435\u0432\u0435\u0440\u043d\u044b\u0439 \u0444\u043e\u0440\u043c\u0430\u0442")}c.dmUploader("destroy")}})})})}}})($nc);
(function(b){"function"===typeof define&&define.amd?define(["jquery"],b):b(jQuery)})(function(b){b.ui=b.ui||{};b.ui.version="1.12.1";var y=0,v=Array.prototype.slice;b.cleanData=function(a){return function(c){var d,e,f;for(f=0;null!=(e=c[f]);f++)try{(d=b._data(e,"events"))&&d.remove&&b(e).triggerHandler("remove")}catch(g){}a(c)}}(b.cleanData);b.widget=function(a,c,d){var e,f,g,h={},k=a.split(".")[0];a=a.split(".")[1];var l=k+"-"+a;d||(d=c,c=b.Widget);b.isArray(d)&&(d=b.extend.apply(null,[{}].concat(d)));
b.expr[":"][l.toLowerCase()]=function(a){return!!b.data(a,l)};b[k]=b[k]||{};e=b[k][a];f=b[k][a]=function(a,b){if(!this._createWidget)return new f(a,b);arguments.length&&this._createWidget(a,b)};b.extend(f,e,{version:d.version,_proto:b.extend({},d),_childConstructors:[]});g=new c;g.options=b.widget.extend({},g.options);b.each(d,function(a,d){b.isFunction(d)?h[a]=function(){function b(){return c.prototype[a].apply(this,arguments)}function e(b){return c.prototype[a].apply(this,b)}return function(){var a=
this._super,c=this._superApply,f;this._super=b;this._superApply=e;f=d.apply(this,arguments);this._super=a;this._superApply=c;return f}}():h[a]=d});f.prototype=b.widget.extend(g,{widgetEventPrefix:e?g.widgetEventPrefix||a:a},h,{constructor:f,namespace:k,widgetName:a,widgetFullName:l});e?(b.each(e._childConstructors,function(a,c){var d=c.prototype;b.widget(d.namespace+"."+d.widgetName,f,c._proto)}),delete e._childConstructors):c._childConstructors.push(f);b.widget.bridge(a,f);return f};b.widget.extend=
function(a){for(var c=v.call(arguments,1),d=0,e=c.length,f,g;d<e;d++)for(f in c[d])g=c[d][f],c[d].hasOwnProperty(f)&&void 0!==g&&(b.isPlainObject(g)?a[f]=b.isPlainObject(a[f])?b.widget.extend({},a[f],g):b.widget.extend({},g):a[f]=g);return a};b.widget.bridge=function(a,c){var d=c.prototype.widgetFullName||a;b.fn[a]=function(e){var f="string"===typeof e,g=v.call(arguments,1),h=this;f?this.length||"instance"!==e?this.each(function(){var c,f=b.data(this,d);if("instance"===e)return h=f,!1;if(!f)return b.error("cannot call methods on "+
a+" prior to initialization; attempted to call method '"+e+"'");if(!b.isFunction(f[e])||"_"===e.charAt(0))return b.error("no such method '"+e+"' for "+a+" widget instance");c=f[e].apply(f,g);if(c!==f&&void 0!==c)return h=c&&c.jquery?h.pushStack(c.get()):c,!1}):h=void 0:(g.length&&(e=b.widget.extend.apply(null,[e].concat(g))),this.each(function(){var a=b.data(this,d);a?(a.option(e||{}),a._init&&a._init()):b.data(this,d,new c(e,this))}));return h}};b.Widget=function(){};b.Widget._childConstructors=
[];b.Widget.prototype={widgetName:"widget",widgetEventPrefix:"",defaultElement:"<div>",options:{classes:{},disabled:!1,create:null},_createWidget:function(a,c){c=b(c||this.defaultElement||this)[0];this.element=b(c);this.uuid=y++;this.eventNamespace="."+this.widgetName+this.uuid;this.bindings=b();this.hoverable=b();this.focusable=b();this.classesElementLookup={};c!==this&&(b.data(c,this.widgetFullName,this),this._on(!0,this.element,{remove:function(a){a.target===c&&this.destroy()}}),this.document=
b(c.style?c.ownerDocument:c.document||c),this.window=b(this.document[0].defaultView||this.document[0].parentWindow));this.options=b.widget.extend({},this.options,this._getCreateOptions(),a);this._create();this.options.disabled&&this._setOptionDisabled(this.options.disabled);this._trigger("create",null,this._getCreateEventData());this._init()},_getCreateOptions:function(){return{}},_getCreateEventData:b.noop,_create:b.noop,_init:b.noop,destroy:function(){var a=this;this._destroy();b.each(this.classesElementLookup,
function(b,d){a._removeClass(d,b)});this.element.off(this.eventNamespace).removeData(this.widgetFullName);this.widget().off(this.eventNamespace).removeAttr("aria-disabled");this.bindings.off(this.eventNamespace)},_destroy:b.noop,widget:function(){return this.element},option:function(a,c){var d=a,e,f,g;if(0===arguments.length)return b.widget.extend({},this.options);if("string"===typeof a)if(d={},e=a.split("."),a=e.shift(),e.length){f=d[a]=b.widget.extend({},this.options[a]);for(g=0;g<e.length-1;g++)f[e[g]]=
f[e[g]]||{},f=f[e[g]];a=e.pop();if(1===arguments.length)return void 0===f[a]?null:f[a];f[a]=c}else{if(1===arguments.length)return void 0===this.options[a]?null:this.options[a];d[a]=c}this._setOptions(d);return this},_setOptions:function(a){for(var b in a)this._setOption(b,a[b]);return this},_setOption:function(a,b){"classes"===a&&this._setOptionClasses(b);this.options[a]=b;"disabled"===a&&this._setOptionDisabled(b);return this},_setOptionClasses:function(a){var c,d,e;for(c in a)e=this.classesElementLookup[c],
a[c]!==this.options.classes[c]&&e&&e.length&&(d=b(e.get()),this._removeClass(e,c),d.addClass(this._classes({element:d,keys:c,classes:a,add:!0})))},_setOptionDisabled:function(a){this._toggleClass(this.widget(),this.widgetFullName+"-disabled",null,!!a);a&&(this._removeClass(this.hoverable,null,"ui-state-hover"),this._removeClass(this.focusable,null,"ui-state-focus"))},enable:function(){return this._setOptions({disabled:!1})},disable:function(){return this._setOptions({disabled:!0})},_classes:function(a){function c(c,
g){var h,k;for(k=0;k<c.length;k++)h=e.classesElementLookup[c[k]]||b(),h=a.add?b(b.unique(h.get().concat(a.element.get()))):b(h.not(a.element).get()),e.classesElementLookup[c[k]]=h,d.push(c[k]),g&&a.classes[c[k]]&&d.push(a.classes[c[k]])}var d=[],e=this;a=b.extend({element:this.element,classes:this.options.classes||{}},a);this._on(a.element,{remove:"_untrackClassesElement"});a.keys&&c(a.keys.match(/\S+/g)||[],!0);a.extra&&c(a.extra.match(/\S+/g)||[]);return d.join(" ")},_untrackClassesElement:function(a){var c=
this;b.each(c.classesElementLookup,function(d,e){-1!==b.inArray(a.target,e)&&(c.classesElementLookup[d]=b(e.not(a.target).get()))})},_removeClass:function(a,b,d){return this._toggleClass(a,b,d,!1)},_addClass:function(a,b,d){return this._toggleClass(a,b,d,!0)},_toggleClass:function(a,b,d,e){e="boolean"===typeof e?e:d;var f="string"===typeof a||null===a;a={extra:f?b:d,keys:f?a:b,element:f?this.element:a,add:e};a.element.toggleClass(this._classes(a),e);return this},_on:function(a,c,d){var e,f=this;"boolean"!==
typeof a&&(d=c,c=a,a=!1);d?(c=e=b(c),this.bindings=this.bindings.add(c)):(d=c,c=this.element,e=this.widget());b.each(d,function(d,h){function k(){if(a||!0!==f.options.disabled&&!b(this).hasClass("ui-state-disabled"))return("string"===typeof h?f[h]:h).apply(f,arguments)}"string"!==typeof h&&(k.guid=h.guid=h.guid||k.guid||b.guid++);var l=d.match(/^([\w:-]*)\s*(.*)$/),m=l[1]+f.eventNamespace;if(l=l[2])e.on(m,l,k);else c.on(m,k)})},_off:function(a,c){c=(c||"").split(" ").join(this.eventNamespace+" ")+
this.eventNamespace;a.off(c).off(c);this.bindings=b(this.bindings.not(a).get());this.focusable=b(this.focusable.not(a).get());this.hoverable=b(this.hoverable.not(a).get())},_delay:function(a,b){var d=this;return setTimeout(function(){return("string"===typeof a?d[a]:a).apply(d,arguments)},b||0)},_hoverable:function(a){this.hoverable=this.hoverable.add(a);this._on(a,{mouseenter:function(a){this._addClass(b(a.currentTarget),null,"ui-state-hover")},mouseleave:function(a){this._removeClass(b(a.currentTarget),
null,"ui-state-hover")}})},_focusable:function(a){this.focusable=this.focusable.add(a);this._on(a,{focusin:function(a){this._addClass(b(a.currentTarget),null,"ui-state-focus")},focusout:function(a){this._removeClass(b(a.currentTarget),null,"ui-state-focus")}})},_trigger:function(a,c,d){var e,f=this.options[a];d=d||{};c=b.Event(c);c.type=(a===this.widgetEventPrefix?a:this.widgetEventPrefix+a).toLowerCase();c.target=this.element[0];if(a=c.originalEvent)for(e in a)e in c||(c[e]=a[e]);this.element.trigger(c,
d);return!(b.isFunction(f)&&!1===f.apply(this.element[0],[c].concat(d))||c.isDefaultPrevented())}};b.each({show:"fadeIn",hide:"fadeOut"},function(a,c){b.Widget.prototype["_"+a]=function(d,e,f){"string"===typeof e&&(e={effect:e});var g,h=e?!0===e||"number"===typeof e?c:e.effect||c:a;e=e||{};"number"===typeof e&&(e={duration:e});g=!b.isEmptyObject(e);e.complete=f;e.delay&&d.delay(e.delay);if(g&&b.effects&&b.effects.effect[h])d[a](e);else if(h!==a&&d[h])d[h](e.duration,e.easing,f);else d.queue(function(c){b(this)[a]();
f&&f.call(d[0]);c()})}});(function(){function a(a,b,c){return[parseFloat(a[0])*(m.test(a[0])?b/100:1),parseFloat(a[1])*(m.test(a[1])?c/100:1)]}function c(a){var c=a[0];return 9===c.nodeType?{width:a.width(),height:a.height(),offset:{top:0,left:0}}:b.isWindow(c)?{width:a.width(),height:a.height(),offset:{top:a.scrollTop(),left:a.scrollLeft()}}:c.preventDefault?{width:0,height:0,offset:{top:c.pageY,left:c.pageX}}:{width:a.outerWidth(),height:a.outerHeight(),offset:a.offset()}}var d,e=Math.max,f=Math.abs,
g=/left|center|right/,h=/top|center|bottom/,k=/[\+\-]\d+(\.[\d]+)?%?/,l=/^\w+/,m=/%$/,v=b.fn.position;b.position={scrollbarWidth:function(){if(void 0!==d)return d;var a,c,e=b("<div style='display:block;position:absolute;width:50px;height:50px;overflow:hidden;'><div style='height:100px;width:auto;'></div></div>");c=e.children()[0];b("body").append(e);a=c.offsetWidth;e.css("overflow","scroll");c=c.offsetWidth;a===c&&(c=e[0].clientWidth);e.remove();return d=a-c},getScrollInfo:function(a){var c=a.isWindow||
a.isDocument?"":a.element.css("overflow-x"),d=a.isWindow||a.isDocument?"":a.element.css("overflow-y"),c="scroll"===c||"auto"===c&&a.width<a.element[0].scrollWidth;return{width:"scroll"===d||"auto"===d&&a.height<a.element[0].scrollHeight?b.position.scrollbarWidth():0,height:c?b.position.scrollbarWidth():0}},getWithinInfo:function(a){var c=b(a||window),d=b.isWindow(c[0]),e=!!c[0]&&9===c[0].nodeType;return{element:c,isWindow:d,isDocument:e,offset:d||e?{left:0,top:0}:b(a).offset(),scrollLeft:c.scrollLeft(),
scrollTop:c.scrollTop(),width:c.outerWidth(),height:c.outerHeight()}}};b.fn.position=function(d){if(!d||!d.of)return v.apply(this,arguments);d=b.extend({},d);var r,t,u,w,n,q,z=b(d.of),B=b.position.getWithinInfo(d.within),C=b.position.getScrollInfo(B),m=(d.collision||"flip").split(" "),A={};q=c(z);z[0].preventDefault&&(d.at="left top");t=q.width;u=q.height;w=q.offset;n=b.extend({},w);b.each(["my","at"],function(){var a=(d[this]||"").split(" "),b,c;1===a.length&&(a=g.test(a[0])?a.concat(["center"]):
h.test(a[0])?["center"].concat(a):["center","center"]);a[0]=g.test(a[0])?a[0]:"center";a[1]=h.test(a[1])?a[1]:"center";b=k.exec(a[0]);c=k.exec(a[1]);A[this]=[b?b[0]:0,c?c[0]:0];d[this]=[l.exec(a[0])[0],l.exec(a[1])[0]]});1===m.length&&(m[1]=m[0]);"right"===d.at[0]?n.left+=t:"center"===d.at[0]&&(n.left+=t/2);"bottom"===d.at[1]?n.top+=u:"center"===d.at[1]&&(n.top+=u/2);r=a(A.at,t,u);n.left+=r[0];n.top+=r[1];return this.each(function(){var c,h,g=b(this),k=g.outerWidth(),q=g.outerHeight(),l=parseInt(b.css(this,
"marginLeft"),10)||0,v=parseInt(b.css(this,"marginTop"),10)||0,y=k+l+(parseInt(b.css(this,"marginRight"),10)||0)+C.width,D=q+v+(parseInt(b.css(this,"marginBottom"),10)||0)+C.height,p=b.extend({},n),x=a(A.my,g.outerWidth(),g.outerHeight());"right"===d.my[0]?p.left-=k:"center"===d.my[0]&&(p.left-=k/2);"bottom"===d.my[1]?p.top-=q:"center"===d.my[1]&&(p.top-=q/2);p.left+=x[0];p.top+=x[1];c={marginLeft:l,marginTop:v};b.each(["left","top"],function(a,e){if(b.ui.position[m[a]])b.ui.position[m[a]][e](p,{targetWidth:t,
targetHeight:u,elemWidth:k,elemHeight:q,collisionPosition:c,collisionWidth:y,collisionHeight:D,offset:[r[0]+x[0],r[1]+x[1]],my:d.my,at:d.at,within:B,elem:g})});d.using&&(h=function(a){var b=w.left-p.left,c=b+t-k,r=w.top-p.top,h=r+u-q,n={target:{element:z,left:w.left,top:w.top,width:t,height:u},element:{element:g,left:p.left,top:p.top,width:k,height:q},horizontal:0>c?"left":0<b?"right":"center",vertical:0>h?"top":0<r?"bottom":"middle"};t<k&&f(b+c)<t&&(n.horizontal="center");u<q&&f(r+h)<u&&(n.vertical=
"middle");e(f(b),f(c))>e(f(r),f(h))?n.important="horizontal":n.important="vertical";d.using.call(this,a,n)});g.offset(b.extend(p,{using:h}))})};b.ui.position={fit:{left:function(a,b){var c=b.within,d=c.isWindow?c.scrollLeft:c.offset.left,f=c.width,h=a.left-b.collisionPosition.marginLeft,c=d-h,g=h+b.collisionWidth-f-d;b.collisionWidth>f?0<c&&0>=g?(d=a.left+c+b.collisionWidth-f-d,a.left+=c-d):a.left=0<g&&0>=c?d:c>g?d+f-b.collisionWidth:d:a.left=0<c?a.left+c:0<g?a.left-g:e(a.left-h,a.left)},top:function(a,
b){var c=b.within,d=c.isWindow?c.scrollTop:c.offset.top,f=b.within.height,h=a.top-b.collisionPosition.marginTop,c=d-h,g=h+b.collisionHeight-f-d;b.collisionHeight>f?0<c&&0>=g?(d=a.top+c+b.collisionHeight-f-d,a.top+=c-d):a.top=0<g&&0>=c?d:c>g?d+f-b.collisionHeight:d:a.top=0<c?a.top+c:0<g?a.top-g:e(a.top-h,a.top)}},flip:{left:function(a,b){var c=b.within,d=c.offset.left+c.scrollLeft,e=c.width,h=c.isWindow?c.scrollLeft:c.offset.left,g=a.left-b.collisionPosition.marginLeft,c=g-h,k=g+b.collisionWidth-e-
h,g="left"===b.my[0]?-b.elemWidth:"right"===b.my[0]?b.elemWidth:0,l="left"===b.at[0]?b.targetWidth:"right"===b.at[0]?-b.targetWidth:0,m=-2*b.offset[0];if(0>c){if(d=a.left+g+l+m+b.collisionWidth-e-d,0>d||d<f(c))a.left+=g+l+m}else 0<k&&(d=a.left-b.collisionPosition.marginLeft+g+l+m-h,0<d||f(d)<k)&&(a.left+=g+l+m)},top:function(a,b){var c=b.within,d=c.offset.top+c.scrollTop,e=c.height,g=c.isWindow?c.scrollTop:c.offset.top,h=a.top-b.collisionPosition.marginTop,c=h-g,k=h+b.collisionHeight-e-g,h="top"===
b.my[1]?-b.elemHeight:"bottom"===b.my[1]?b.elemHeight:0,l="top"===b.at[1]?b.targetHeight:"bottom"===b.at[1]?-b.targetHeight:0,m=-2*b.offset[1];if(0>c){if(d=a.top+h+l+m+b.collisionHeight-e-d,0>d||d<f(c))a.top+=h+l+m}else 0<k&&(d=a.top-b.collisionPosition.marginTop+h+l+m-g,0<d||f(d)<k)&&(a.top+=h+l+m)}},flipfit:{left:function(){b.ui.position.flip.left.apply(this,arguments);b.ui.position.fit.left.apply(this,arguments)},top:function(){b.ui.position.flip.top.apply(this,arguments);b.ui.position.fit.top.apply(this,
arguments)}}}})();b.extend(b.expr[":"],{data:b.expr.createPseudo?b.expr.createPseudo(function(a){return function(c){return!!b.data(c,a)}}):function(a,c,d){return!!b.data(a,d[3])}});b.fn.extend({disableSelection:function(){var a="onselectstart"in document.createElement("div")?"selectstart":"mousedown";return function(){return this.on(a+".ui-disableSelection",function(a){a.preventDefault()})}}(),enableSelection:function(){return this.off(".ui-disableSelection")}});b.ui.focusable=function(a,c){var d,
e;d=a.nodeName.toLowerCase();if("area"===d){d=a.parentNode;e=d.name;if(!a.href||!e||"map"!==d.nodeName.toLowerCase())return!1;d=b("img[usemap='#"+e+"']");return 0<d.length&&d.is(":visible")}/^(input|select|textarea|button|object)$/.test(d)?(d=!a.disabled)&&(e=b(a).closest("fieldset")[0])&&(d=!e.disabled):d="a"===d?a.href||c:c;if(d=d&&b(a).is(":visible")){d=b(a);for(e=d.css("visibility");"inherit"===e;)d=d.parent(),e=d.css("visibility");d="hidden"!==e}return d};b.extend(b.expr[":"],{focusable:function(a){return b.ui.focusable(a,
null!=b.attr(a,"tabindex"))}});b.fn.form=function(){return"string"===typeof this[0].form?this.closest("form"):b(this[0].form)};b.ui.formResetMixin={_formResetHandler:function(){var a=b(this);setTimeout(function(){var c=a.data("ui-form-reset-instances");b.each(c,function(){this.refresh()})})},_bindFormResetHandler:function(){this.form=this.element.form();if(this.form.length){var a=this.form.data("ui-form-reset-instances")||[];if(!a.length)this.form.on("reset.ui-form-reset",this._formResetHandler);
a.push(this);this.form.data("ui-form-reset-instances",a)}},_unbindFormResetHandler:function(){if(this.form.length){var a=this.form.data("ui-form-reset-instances");a.splice(b.inArray(this,a),1);a.length?this.form.data("ui-form-reset-instances",a):this.form.removeData("ui-form-reset-instances").off("reset.ui-form-reset")}}};"1.7"===b.fn.jquery.substring(0,3)&&(b.each(["Width","Height"],function(a,c){function d(a,c,d,f){b.each(e,function(){c-=parseFloat(b.css(a,"padding"+this))||0;d&&(c-=parseFloat(b.css(a,
"border"+this+"Width"))||0);f&&(c-=parseFloat(b.css(a,"margin"+this))||0)});return c}var e="Width"===c?["Left","Right"]:["Top","Bottom"],f=c.toLowerCase(),g={innerWidth:b.fn.innerWidth,innerHeight:b.fn.innerHeight,outerWidth:b.fn.outerWidth,outerHeight:b.fn.outerHeight};b.fn["inner"+c]=function(a){return void 0===a?g["inner"+c].call(this):this.each(function(){b(this).css(f,d(this,a)+"px")})};b.fn["outer"+c]=function(a,e){return"number"!==typeof a?g["outer"+c].call(this,a):this.each(function(){b(this).css(f,
d(this,a,!0,e)+"px")})}}),b.fn.addBack=function(a){return this.add(null==a?this.prevObject:this.prevObject.filter(a))});b.ui.keyCode={BACKSPACE:8,COMMA:188,DELETE:46,DOWN:40,END:35,ENTER:13,ESCAPE:27,HOME:36,LEFT:37,PAGE_DOWN:34,PAGE_UP:33,PERIOD:190,RIGHT:39,SPACE:32,TAB:9,UP:38};b.ui.escapeSelector=function(){var a=/([!"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g;return function(b){return b.replace(a,"\\$1")}}();b.fn.labels=function(){var a,c,d;if(this[0].labels&&this[0].labels.length)return this.pushStack(this[0].labels);
d=this.eq(0).parents("label");if(c=this.attr("id"))a=this.eq(0).parents().last(),a=a.add(a.length?a.siblings():this.siblings()),c="label[for='"+b.ui.escapeSelector(c)+"']",d=d.add(a.find(c).addBack(c));return this.pushStack(d)};b.fn.scrollParent=function(a){var c=this.css("position"),d="absolute"===c,e=a?/(auto|scroll|hidden)/:/(auto|scroll)/;a=this.parents().filter(function(){var a=b(this);return d&&"static"===a.css("position")?!1:e.test(a.css("overflow")+a.css("overflow-y")+a.css("overflow-x"))}).eq(0);
return"fixed"!==c&&a.length?a:b(this[0].ownerDocument||document)};b.extend(b.expr[":"],{tabbable:function(a){var c=b.attr(a,"tabindex"),d=null!=c;return(!d||0<=c)&&b.ui.focusable(a,d)}});b.fn.extend({uniqueId:function(){var a=0;return function(){return this.each(function(){this.id||(this.id="ui-id-"+ ++a)})}}(),removeUniqueId:function(){return this.each(function(){/^ui-id-\d+$/.test(this.id)&&b(this).removeAttr("id")})}});b.ui.ie=!!/msie [\w.]+/.exec(navigator.userAgent.toLowerCase());b.ui.plugin=
{add:function(a,c,d){var e;a=b.ui[a].prototype;for(e in d)a.plugins[e]=a.plugins[e]||[],a.plugins[e].push([c,d[e]])},call:function(a,b,d,e){if((b=a.plugins[b])&&(e||a.element[0].parentNode&&11!==a.element[0].parentNode.nodeType))for(e=0;e<b.length;e++)a.options[b[e][0]]&&b[e][1].apply(a.element,d)}};b.ui.safeActiveElement=function(a){var b;try{b=a.activeElement}catch(d){b=a.body}b||(b=a.body);b.nodeName||(b=a.body);return b};b.ui.safeBlur=function(a){a&&"body"!==a.nodeName.toLowerCase()&&b(a).trigger("blur")}});

(function(b){"function"===typeof define&&define.amd?define(["jquery","../ie","../version","../widget"],b):b(jQuery)})(function(b){var d=!1;b(document).on("mouseup",function(){d=!1});return b.widget("ui.mouse",{version:"1.12.1",options:{cancel:"input, textarea, button, select, option",distance:1,delay:0},_mouseInit:function(){var a=this;this.element.on("mousedown."+this.widgetName,function(b){return a._mouseDown(b)}).on("click."+this.widgetName,function(c){if(!0===b.data(c.target,a.widgetName+".preventClickEvent"))return b.removeData(c.target,
a.widgetName+".preventClickEvent"),c.stopImmediatePropagation(),!1});this.started=!1},_mouseDestroy:function(){this.element.off("."+this.widgetName);this._mouseMoveDelegate&&this.document.off("mousemove."+this.widgetName,this._mouseMoveDelegate).off("mouseup."+this.widgetName,this._mouseUpDelegate)},_mouseDown:function(a){if(!d){this._mouseMoved=!1;this._mouseStarted&&this._mouseUp(a);this._mouseDownEvent=a;var c=this,e=1===a.which,f="string"===typeof this.options.cancel&&a.target.nodeName?b(a.target).closest(this.options.cancel).length:
!1;if(!e||f||!this._mouseCapture(a))return!0;this.mouseDelayMet=!this.options.delay;if(!this.mouseDelayMet)this._mouseDelayTimer=setTimeout(function(){c.mouseDelayMet=!0},this.options.delay);if(this._mouseDistanceMet(a)&&this._mouseDelayMet(a)&&(this._mouseStarted=!1!==this._mouseStart(a),!this._mouseStarted))return a.preventDefault(),!0;!0===b.data(a.target,this.widgetName+".preventClickEvent")&&b.removeData(a.target,this.widgetName+".preventClickEvent");this._mouseMoveDelegate=function(a){return c._mouseMove(a)};
this._mouseUpDelegate=function(a){return c._mouseUp(a)};this.document.on("mousemove."+this.widgetName,this._mouseMoveDelegate).on("mouseup."+this.widgetName,this._mouseUpDelegate);a.preventDefault();return d=!0}},_mouseMove:function(a){if(this._mouseMoved){if(b.ui.ie&&(!document.documentMode||9>document.documentMode)&&!a.button)return this._mouseUp(a);if(!a.which)if(a.originalEvent.altKey||a.originalEvent.ctrlKey||a.originalEvent.metaKey||a.originalEvent.shiftKey)this.ignoreMissingWhich=!0;else if(!this.ignoreMissingWhich)return this._mouseUp(a)}if(a.which||
a.button)this._mouseMoved=!0;if(this._mouseStarted)return this._mouseDrag(a),a.preventDefault();if(this._mouseDistanceMet(a)&&this._mouseDelayMet(a))(this._mouseStarted=!1!==this._mouseStart(this._mouseDownEvent,a))?this._mouseDrag(a):this._mouseUp(a);return!this._mouseStarted},_mouseUp:function(a){this.document.off("mousemove."+this.widgetName,this._mouseMoveDelegate).off("mouseup."+this.widgetName,this._mouseUpDelegate);if(this._mouseStarted)this._mouseStarted=!1,a.target===this._mouseDownEvent.target&&
b.data(a.target,this.widgetName+".preventClickEvent",!0),this._mouseStop(a);this._mouseDelayTimer&&(clearTimeout(this._mouseDelayTimer),delete this._mouseDelayTimer);d=this.ignoreMissingWhich=!1;a.preventDefault()},_mouseDistanceMet:function(a){return Math.max(Math.abs(this._mouseDownEvent.pageX-a.pageX),Math.abs(this._mouseDownEvent.pageY-a.pageY))>=this.options.distance},_mouseDelayMet:function(){return this.mouseDelayMet},_mouseStart:function(){},_mouseDrag:function(){},_mouseStop:function(){},
_mouseCapture:function(){return!0}})});

(function(e){"function"===typeof define&&define.amd?define("jquery,./mouse,../data,../ie,../scroll-parent,../version,../widget".split(","),e):e(jQuery)})(function(e){return e.widget("ui.sortable",e.ui.mouse,{version:"1.12.1",widgetEventPrefix:"sort",ready:!1,options:{appendTo:"parent",axis:!1,connectWith:!1,containment:!1,cursor:"auto",cursorAt:!1,dropOnEmpty:!0,forcePlaceholderSize:!1,forceHelperSize:!1,grid:!1,handle:!1,helper:"original",items:"> *",opacity:!1,placeholder:!1,revert:!1,scroll:!0,
scrollSensitivity:20,scrollSpeed:20,scope:"default",tolerance:"intersect",zIndex:1E3,activate:null,beforeStop:null,change:null,deactivate:null,out:null,over:null,receive:null,remove:null,sort:null,start:null,stop:null,update:null},_isOverAxis:function(a,b,c){return a>=b&&a<b+c},_isFloating:function(a){return/left|right/.test(a.css("float"))||/inline|table-cell/.test(a.css("display"))},_create:function(){this.containerCache={};this._addClass("ui-sortable");this.refresh();this.offset=this.element.offset();
this._mouseInit();this._setHandleClassName();this.ready=!0},_setOption:function(a,b){this._super(a,b);"handle"===a&&this._setHandleClassName()},_setHandleClassName:function(){var a=this;this._removeClass(this.element.find(".ui-sortable-handle"),"ui-sortable-handle");e.each(this.items,function(){a._addClass(this.instance.options.handle?this.item.find(this.instance.options.handle):this.item,"ui-sortable-handle")})},_destroy:function(){this._mouseDestroy();for(var a=this.items.length-1;0<=a;a--)this.items[a].item.removeData(this.widgetName+
"-item");return this},_mouseCapture:function(a,b){var c=null,d=!1,f=this;if(this.reverting||this.options.disabled||"static"===this.options.type)return!1;this._refreshItems(a);e(a.target).parents().each(function(){if(e.data(this,f.widgetName+"-item")===f)return c=e(this),!1});e.data(a.target,f.widgetName+"-item")===f&&(c=e(a.target));if(!c||this.options.handle&&!b&&(e(this.options.handle,c).find("*").addBack().each(function(){this===a.target&&(d=!0)}),!d))return!1;this.currentItem=c;this._removeCurrentsFromItems();
return!0},_mouseStart:function(a,b,c){var d,b=this.options;this.currentContainer=this;this.refreshPositions();this.appendTo=e("parent"!==b.appendTo?b.appendTo:this.currentItem.parent());this.helper=this._createHelper(a);this._cacheHelperProportions();this._cacheMargins();this.offset=this.currentItem.offset();this.offset={top:this.offset.top-this.margins.top,left:this.offset.left-this.margins.left};e.extend(this.offset,{click:{left:a.pageX-this.offset.left,top:a.pageY-this.offset.top},relative:this._getRelativeOffset()});
this.helper.css("position","absolute");this.cssPosition=this.helper.css("position");b.cursorAt&&this._adjustOffsetFromHelper(b.cursorAt);this.domPosition={prev:this.currentItem.prev()[0],parent:this.currentItem.parent()[0]};this.helper[0]!==this.currentItem[0]&&this.currentItem.hide();this._createPlaceholder();this.scrollParent=this.placeholder.scrollParent();e.extend(this.offset,{parent:this._getParentOffset()});b.containment&&this._setContainment();if(b.cursor&&"auto"!==b.cursor)d=this.document.find("body"),
this.storedCursor=d.css("cursor"),d.css("cursor",b.cursor),this.storedStylesheet=e("<style>*{ cursor: "+b.cursor+" !important; }</style>").appendTo(d);if(b.zIndex){if(this.helper.css("zIndex"))this._storedZIndex=this.helper.css("zIndex");this.helper.css("zIndex",b.zIndex)}if(b.opacity){if(this.helper.css("opacity"))this._storedOpacity=this.helper.css("opacity");this.helper.css("opacity",b.opacity)}if(this.scrollParent[0]!==this.document[0]&&"HTML"!==this.scrollParent[0].tagName)this.overflowOffset=
this.scrollParent.offset();this._trigger("start",a,this._uiHash());this._preserveHelperProportions||this._cacheHelperProportions();if(!c)for(c=this.containers.length-1;0<=c;c--)this.containers[c]._trigger("activate",a,this._uiHash(this));if(e.ui.ddmanager)e.ui.ddmanager.current=this;e.ui.ddmanager&&!b.dropBehaviour&&e.ui.ddmanager.prepareOffsets(this,a);this.dragging=!0;this._addClass(this.helper,"ui-sortable-helper");if(!this.helper.parent().is(this.appendTo))this.helper.detach().appendTo(this.appendTo),
this.offset.parent=this._getParentOffset();this.position=this.originalPosition=this._generatePosition(a);this.originalPageX=a.pageX;this.originalPageY=a.pageY;this.lastPositionAbs=this.positionAbs=this._convertPositionTo("absolute");this._mouseDrag(a);return!0},_scroll:function(a){var b=this.options,c=!1;if(this.scrollParent[0]!==this.document[0]&&"HTML"!==this.scrollParent[0].tagName){if(this.overflowOffset.top+this.scrollParent[0].offsetHeight-a.pageY<b.scrollSensitivity)this.scrollParent[0].scrollTop=
c=this.scrollParent[0].scrollTop+b.scrollSpeed;else if(a.pageY-this.overflowOffset.top<b.scrollSensitivity)this.scrollParent[0].scrollTop=c=this.scrollParent[0].scrollTop-b.scrollSpeed;if(this.overflowOffset.left+this.scrollParent[0].offsetWidth-a.pageX<b.scrollSensitivity)this.scrollParent[0].scrollLeft=c=this.scrollParent[0].scrollLeft+b.scrollSpeed;else if(a.pageX-this.overflowOffset.left<b.scrollSensitivity)this.scrollParent[0].scrollLeft=c=this.scrollParent[0].scrollLeft-b.scrollSpeed}else a.pageY-
this.document.scrollTop()<b.scrollSensitivity?c=this.document.scrollTop(this.document.scrollTop()-b.scrollSpeed):this.window.height()-(a.pageY-this.document.scrollTop())<b.scrollSensitivity&&(c=this.document.scrollTop(this.document.scrollTop()+b.scrollSpeed)),a.pageX-this.document.scrollLeft()<b.scrollSensitivity?c=this.document.scrollLeft(this.document.scrollLeft()-b.scrollSpeed):this.window.width()-(a.pageX-this.document.scrollLeft())<b.scrollSensitivity&&(c=this.document.scrollLeft(this.document.scrollLeft()+
b.scrollSpeed));return c},_mouseDrag:function(a){var b,c,d,f;b=this.options;this.position=this._generatePosition(a);this.positionAbs=this._convertPositionTo("absolute");if(!this.options.axis||"y"!==this.options.axis)this.helper[0].style.left=this.position.left+"px";if(!this.options.axis||"x"!==this.options.axis)this.helper[0].style.top=this.position.top+"px";this._contactContainers(a);if(null!==this.innermostContainer){b.scroll&&!1!==this._scroll(a)&&(this._refreshItemPositions(!0),e.ui.ddmanager&&
!b.dropBehaviour&&e.ui.ddmanager.prepareOffsets(this,a));this.dragDirection={vertical:this._getDragVerticalDirection(),horizontal:this._getDragHorizontalDirection()};for(b=this.items.length-1;0<=b;b--)if(c=this.items[b],d=c.item[0],(f=this._intersectsWithPointer(c))&&c.instance===this.currentContainer&&d!==this.currentItem[0]&&this.placeholder[1===f?"next":"prev"]()[0]!==d&&!e.contains(this.placeholder[0],d)&&("semi-dynamic"===this.options.type?!e.contains(this.element[0],d):1)){this.direction=1===
f?"down":"up";if("pointer"===this.options.tolerance||this._intersectsWithSides(c))this._rearrange(a,c);else break;this._trigger("change",a,this._uiHash());break}}e.ui.ddmanager&&e.ui.ddmanager.drag(this,a);this._trigger("sort",a,this._uiHash());this.lastPositionAbs=this.positionAbs;return!1},_mouseStop:function(a,b){if(a){e.ui.ddmanager&&!this.options.dropBehaviour&&e.ui.ddmanager.drop(this,a);if(this.options.revert){var c=this,d=this.placeholder.offset(),f=this.options.axis,g={};if(!f||"x"===f)g.left=
d.left-this.offset.parent.left-this.margins.left+(this.offsetParent[0]===this.document[0].body?0:this.offsetParent[0].scrollLeft);if(!f||"y"===f)g.top=d.top-this.offset.parent.top-this.margins.top+(this.offsetParent[0]===this.document[0].body?0:this.offsetParent[0].scrollTop);this.reverting=!0;e(this.helper).animate(g,parseInt(this.options.revert,10)||500,function(){c._clear(a)})}else this._clear(a,b);return!1}},cancel:function(){if(this.dragging){this._mouseUp(new e.Event("mouseup",{target:null}));
"original"===this.options.helper?(this.currentItem.css(this._storedCSS),this._removeClass(this.currentItem,"ui-sortable-helper")):this.currentItem.show();for(var a=this.containers.length-1;0<=a;a--)if(this.containers[a]._trigger("deactivate",null,this._uiHash(this)),this.containers[a].containerCache.over)this.containers[a]._trigger("out",null,this._uiHash(this)),this.containers[a].containerCache.over=0}this.placeholder&&(this.placeholder[0].parentNode&&this.placeholder[0].parentNode.removeChild(this.placeholder[0]),
"original"!==this.options.helper&&this.helper&&this.helper[0].parentNode&&this.helper.remove(),e.extend(this,{helper:null,dragging:!1,reverting:!1,_noFinalSort:null}),this.domPosition.prev?e(this.domPosition.prev).after(this.currentItem):e(this.domPosition.parent).prepend(this.currentItem));return this},serialize:function(a){var b=this._getItemsAsjQuery(a&&a.connected),c=[],a=a||{};e(b).each(function(){var b=(e(a.item||this).attr(a.attribute||"id")||"").match(a.expression||/(.+)[\-=_](.+)/);b&&c.push((a.key||
b[1]+"[]")+"="+(a.key&&a.expression?b[1]:b[2]))});!c.length&&a.key&&c.push(a.key+"=");return c.join("&")},toArray:function(a){var b=this._getItemsAsjQuery(a&&a.connected),c=[],a=a||{};b.each(function(){c.push(e(a.item||this).attr(a.attribute||"id")||"")});return c},_intersectsWith:function(a){var b=this.positionAbs.left,c=b+this.helperProportions.width,d=this.positionAbs.top,e=d+this.helperProportions.height,g=a.left,j=g+a.width,i=a.top,k=i+a.height,h=this.offset.click.top,l=this.offset.click.left,
l="y"===this.options.axis||b+l>g&&b+l<j,h=("x"===this.options.axis||d+h>i&&d+h<k)&&l;return"pointer"===this.options.tolerance||this.options.forcePointerForContainers||"pointer"!==this.options.tolerance&&this.helperProportions[this.floating?"width":"height"]>a[this.floating?"width":"height"]?h:g<b+this.helperProportions.width/2&&c-this.helperProportions.width/2<j&&i<d+this.helperProportions.height/2&&e-this.helperProportions.height/2<k},_intersectsWithPointer:function(a){var b;b="x"===this.options.axis||
this._isOverAxis(this.positionAbs.top+this.offset.click.top,a.top,a.height);a="y"===this.options.axis||this._isOverAxis(this.positionAbs.left+this.offset.click.left,a.left,a.width);if(!b||!a)return!1;b=this.dragDirection.vertical;a=this.dragDirection.horizontal;return this.floating?"right"===a||"down"===b?2:1:b&&("down"===b?2:1)},_intersectsWithSides:function(a){var b=this._isOverAxis(this.positionAbs.top+this.offset.click.top,a.top+a.height/2,a.height),a=this._isOverAxis(this.positionAbs.left+this.offset.click.left,
a.left+a.width/2,a.width),c=this.dragDirection.vertical,d=this.dragDirection.horizontal;return this.floating&&d?"right"===d&&a||"left"===d&&!a:c&&("down"===c&&b||"up"===c&&!b)},_getDragVerticalDirection:function(){var a=this.positionAbs.top-this.lastPositionAbs.top;return 0!==a&&(0<a?"down":"up")},_getDragHorizontalDirection:function(){var a=this.positionAbs.left-this.lastPositionAbs.left;return 0!==a&&(0<a?"right":"left")},refresh:function(a){this._refreshItems(a);this._setHandleClassName();this.refreshPositions();
return this},_connectWith:function(){var a=this.options;return a.connectWith.constructor===String?[a.connectWith]:a.connectWith},_getItemsAsjQuery:function(a){function b(){g.push(this)}var c,d,f,g=[],j=[],i=this._connectWith();if(i&&a)for(a=i.length-1;0<=a;a--){d=e(i[a],this.document[0]);for(c=d.length-1;0<=c;c--)(f=e.data(d[c],this.widgetFullName))&&f!==this&&!f.options.disabled&&j.push([e.isFunction(f.options.items)?f.options.items.call(f.element):e(f.options.items,f.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"),
f])}j.push([e.isFunction(this.options.items)?this.options.items.call(this.element,null,{options:this.options,item:this.currentItem}):e(this.options.items,this.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"),this]);for(a=j.length-1;0<=a;a--)j[a][0].each(b);return e(g)},_removeCurrentsFromItems:function(){var a=this.currentItem.find(":data("+this.widgetName+"-item)");this.items=e.grep(this.items,function(b){for(var c=0;c<a.length;c++)if(a[c]===b.item[0])return!1;return!0})},_refreshItems:function(a){this.items=
[];this.containers=[this];var b,c,d,f,g,j=this.items,i=[[e.isFunction(this.options.items)?this.options.items.call(this.element[0],a,{item:this.currentItem}):e(this.options.items,this.element),this]];if((g=this._connectWith())&&this.ready)for(b=g.length-1;0<=b;b--){d=e(g[b],this.document[0]);for(c=d.length-1;0<=c;c--)if((f=e.data(d[c],this.widgetFullName))&&f!==this&&!f.options.disabled)i.push([e.isFunction(f.options.items)?f.options.items.call(f.element[0],a,{item:this.currentItem}):e(f.options.items,
f.element),f]),this.containers.push(f)}for(b=i.length-1;0<=b;b--){a=i[b][1];d=i[b][0];for(c=0,g=d.length;c<g;c++)f=e(d[c]),f.data(this.widgetName+"-item",a),j.push({item:f,instance:a,width:0,height:0,left:0,top:0})}},_refreshItemPositions:function(a){var b,c,d;for(b=this.items.length-1;0<=b;b--)if(c=this.items[b],!this.currentContainer||!(c.instance!==this.currentContainer&&c.item[0]!==this.currentItem[0])){d=this.options.toleranceElement?e(this.options.toleranceElement,c.item):c.item;if(!a)c.width=
d.outerWidth(),c.height=d.outerHeight();d=d.offset();c.left=d.left;c.top=d.top}},refreshPositions:function(a){this.floating=this.items.length?"x"===this.options.axis||this._isFloating(this.items[0].item):!1;null!==this.innermostContainer&&this._refreshItemPositions(a);var b;if(this.options.custom&&this.options.custom.refreshContainers)this.options.custom.refreshContainers.call(this);else for(a=this.containers.length-1;0<=a;a--)b=this.containers[a].element.offset(),this.containers[a].containerCache.left=
b.left,this.containers[a].containerCache.top=b.top,this.containers[a].containerCache.width=this.containers[a].element.outerWidth(),this.containers[a].containerCache.height=this.containers[a].element.outerHeight();return this},_createPlaceholder:function(a){var a=a||this,b,c,d=a.options;if(!d.placeholder||d.placeholder.constructor===String)b=d.placeholder,c=a.currentItem[0].nodeName.toLowerCase(),d.placeholder={element:function(){var d=e("<"+c+">",a.document[0]);a._addClass(d,"ui-sortable-placeholder",
b||a.currentItem[0].className)._removeClass(d,"ui-sortable-helper");"tbody"===c?a._createTrPlaceholder(a.currentItem.find("tr").eq(0),e("<tr>",a.document[0]).appendTo(d)):"tr"===c?a._createTrPlaceholder(a.currentItem,d):"img"===c&&d.attr("src",a.currentItem.attr("src"));b||d.css("visibility","hidden");return d},update:function(e,g){if(!b||d.forcePlaceholderSize){if(!g.height()||d.forcePlaceholderSize&&("tbody"===c||"tr"===c))g.height(a.currentItem.innerHeight()-parseInt(a.currentItem.css("paddingTop")||
0,10)-parseInt(a.currentItem.css("paddingBottom")||0,10));g.width()||g.width(a.currentItem.innerWidth()-parseInt(a.currentItem.css("paddingLeft")||0,10)-parseInt(a.currentItem.css("paddingRight")||0,10))}}};a.placeholder=e(d.placeholder.element.call(a.element,a.currentItem));a.currentItem.after(a.placeholder);d.placeholder.update(a,a.placeholder)},_createTrPlaceholder:function(a,b){var c=this;a.children().each(function(){e("<td>&#160;</td>",c.document[0]).attr("colspan",e(this).attr("colspan")||1).appendTo(b)})},
_contactContainers:function(a){var l;var b,c,d,f,g,j,i,k,h=f=null;for(b=this.containers.length-1;0<=b;b--)if(!e.contains(this.currentItem[0],this.containers[b].element[0]))if(this._intersectsWith(this.containers[b].containerCache)){if(!f||!e.contains(this.containers[b].element[0],f.element[0]))f=this.containers[b],h=b}else if(this.containers[b].containerCache.over)this.containers[b]._trigger("out",a,this._uiHash(this)),this.containers[b].containerCache.over=0;if(this.innermostContainer=f)if(1===this.containers.length){if(!this.containers[h].containerCache.over)this.containers[h]._trigger("over",
a,this._uiHash(this)),this.containers[h].containerCache.over=1}else{b=1E4;d=null;l=(c=f.floating||this._isFloating(this.currentItem))?"left":"top",f=l;g=c?"width":"height";k=c?"pageX":"pageY";for(c=this.items.length-1;0<=c;c--)if(e.contains(this.containers[h].element[0],this.items[c].item[0])&&this.items[c].item[0]!==this.currentItem[0]&&(j=this.items[c].item.offset()[f],i=!1,a[k]-j>this.items[c][g]/2&&(i=!0),Math.abs(a[k]-j)<b))b=Math.abs(a[k]-j),d=this.items[c],this.direction=i?"up":"down";if(d||
this.options.dropOnEmpty)if(this.currentContainer===this.containers[h]){if(!this.currentContainer.containerCache.over)this.containers[h]._trigger("over",a,this._uiHash()),this.currentContainer.containerCache.over=1}else{d?this._rearrange(a,d,null,!0):this._rearrange(a,null,this.containers[h].element,!0);this._trigger("change",a,this._uiHash());this.containers[h]._trigger("change",a,this._uiHash(this));this.currentContainer=this.containers[h];this.options.placeholder.update(this.currentContainer,this.placeholder);
this.scrollParent=this.placeholder.scrollParent();if(this.scrollParent[0]!==this.document[0]&&"HTML"!==this.scrollParent[0].tagName)this.overflowOffset=this.scrollParent.offset();this.containers[h]._trigger("over",a,this._uiHash(this));this.containers[h].containerCache.over=1}}},_createHelper:function(a){var b=this.options,a=e.isFunction(b.helper)?e(b.helper.apply(this.element[0],[a,this.currentItem])):"clone"===b.helper?this.currentItem.clone():this.currentItem;a.parents("body").length||this.appendTo[0].appendChild(a[0]);
if(a[0]===this.currentItem[0])this._storedCSS={width:this.currentItem[0].style.width,height:this.currentItem[0].style.height,position:this.currentItem.css("position"),top:this.currentItem.css("top"),left:this.currentItem.css("left")};(!a[0].style.width||b.forceHelperSize)&&a.width(this.currentItem.width());(!a[0].style.height||b.forceHelperSize)&&a.height(this.currentItem.height());return a},_adjustOffsetFromHelper:function(a){"string"===typeof a&&(a=a.split(" "));e.isArray(a)&&(a={left:+a[0],top:+a[1]||
0});if("left"in a)this.offset.click.left=a.left+this.margins.left;if("right"in a)this.offset.click.left=this.helperProportions.width-a.right+this.margins.left;if("top"in a)this.offset.click.top=a.top+this.margins.top;if("bottom"in a)this.offset.click.top=this.helperProportions.height-a.bottom+this.margins.top},_getParentOffset:function(){this.offsetParent=this.helper.offsetParent();var a=this.offsetParent.offset();"absolute"===this.cssPosition&&this.scrollParent[0]!==this.document[0]&&e.contains(this.scrollParent[0],
this.offsetParent[0])&&(a.left+=this.scrollParent.scrollLeft(),a.top+=this.scrollParent.scrollTop());if(this.offsetParent[0]===this.document[0].body||this.offsetParent[0].tagName&&"html"===this.offsetParent[0].tagName.toLowerCase()&&e.ui.ie)a={top:0,left:0};return{top:a.top+(parseInt(this.offsetParent.css("borderTopWidth"),10)||0),left:a.left+(parseInt(this.offsetParent.css("borderLeftWidth"),10)||0)}},_getRelativeOffset:function(){if("relative"===this.cssPosition){var a=this.currentItem.position();
return{top:a.top-(parseInt(this.helper.css("top"),10)||0)+this.scrollParent.scrollTop(),left:a.left-(parseInt(this.helper.css("left"),10)||0)+this.scrollParent.scrollLeft()}}return{top:0,left:0}},_cacheMargins:function(){this.margins={left:parseInt(this.currentItem.css("marginLeft"),10)||0,top:parseInt(this.currentItem.css("marginTop"),10)||0}},_cacheHelperProportions:function(){this.helperProportions={width:this.helper.outerWidth(),height:this.helper.outerHeight()}},_setContainment:function(){var a,
b,c;b=this.options;if("parent"===b.containment)b.containment=this.helper[0].parentNode;if("document"===b.containment||"window"===b.containment)this.containment=[0-this.offset.relative.left-this.offset.parent.left,0-this.offset.relative.top-this.offset.parent.top,"document"===b.containment?this.document.width():this.window.width()-this.helperProportions.width-this.margins.left,("document"===b.containment?this.document.height()||document.body.parentNode.scrollHeight:this.window.height()||this.document[0].body.parentNode.scrollHeight)-
this.helperProportions.height-this.margins.top];if(!/^(document|window|parent)$/.test(b.containment))a=e(b.containment)[0],b=e(b.containment).offset(),c="hidden"!==e(a).css("overflow"),this.containment=[b.left+(parseInt(e(a).css("borderLeftWidth"),10)||0)+(parseInt(e(a).css("paddingLeft"),10)||0)-this.margins.left,b.top+(parseInt(e(a).css("borderTopWidth"),10)||0)+(parseInt(e(a).css("paddingTop"),10)||0)-this.margins.top,b.left+(c?Math.max(a.scrollWidth,a.offsetWidth):a.offsetWidth)-(parseInt(e(a).css("borderLeftWidth"),
10)||0)-(parseInt(e(a).css("paddingRight"),10)||0)-this.helperProportions.width-this.margins.left,b.top+(c?Math.max(a.scrollHeight,a.offsetHeight):a.offsetHeight)-(parseInt(e(a).css("borderTopWidth"),10)||0)-(parseInt(e(a).css("paddingBottom"),10)||0)-this.helperProportions.height-this.margins.top]},_convertPositionTo:function(a,b){if(!b)b=this.position;var c="absolute"===a?1:-1,d="absolute"===this.cssPosition&&!(this.scrollParent[0]!==this.document[0]&&e.contains(this.scrollParent[0],this.offsetParent[0]))?
this.offsetParent:this.scrollParent,f=/(html|body)/i.test(d[0].tagName);return{top:b.top+this.offset.relative.top*c+this.offset.parent.top*c-("fixed"===this.cssPosition?-this.scrollParent.scrollTop():f?0:d.scrollTop())*c,left:b.left+this.offset.relative.left*c+this.offset.parent.left*c-("fixed"===this.cssPosition?-this.scrollParent.scrollLeft():f?0:d.scrollLeft())*c}},_generatePosition:function(a){var b,c,d=this.options;c=a.pageX;b=a.pageY;var f="absolute"===this.cssPosition&&!(this.scrollParent[0]!==
this.document[0]&&e.contains(this.scrollParent[0],this.offsetParent[0]))?this.offsetParent:this.scrollParent,g=/(html|body)/i.test(f[0].tagName);if("relative"===this.cssPosition&&!(this.scrollParent[0]!==this.document[0]&&this.scrollParent[0]!==this.offsetParent[0]))this.offset.relative=this._getRelativeOffset();this.originalPosition&&(this.containment&&(a.pageX-this.offset.click.left<this.containment[0]&&(c=this.containment[0]+this.offset.click.left),a.pageY-this.offset.click.top<this.containment[1]&&
(b=this.containment[1]+this.offset.click.top),a.pageX-this.offset.click.left>this.containment[2]&&(c=this.containment[2]+this.offset.click.left),a.pageY-this.offset.click.top>this.containment[3]&&(b=this.containment[3]+this.offset.click.top)),d.grid&&(b=this.originalPageY+Math.round((b-this.originalPageY)/d.grid[1])*d.grid[1],b=this.containment?b-this.offset.click.top>=this.containment[1]&&b-this.offset.click.top<=this.containment[3]?b:b-this.offset.click.top>=this.containment[1]?b-d.grid[1]:b+d.grid[1]:
b,c=this.originalPageX+Math.round((c-this.originalPageX)/d.grid[0])*d.grid[0],c=this.containment?c-this.offset.click.left>=this.containment[0]&&c-this.offset.click.left<=this.containment[2]?c:c-this.offset.click.left>=this.containment[0]?c-d.grid[0]:c+d.grid[0]:c));return{top:b-this.offset.click.top-this.offset.relative.top-this.offset.parent.top+("fixed"===this.cssPosition?-this.scrollParent.scrollTop():g?0:f.scrollTop()),left:c-this.offset.click.left-this.offset.relative.left-this.offset.parent.left+
("fixed"===this.cssPosition?-this.scrollParent.scrollLeft():g?0:f.scrollLeft())}},_rearrange:function(a,b,c,d){c?c[0].appendChild(this.placeholder[0]):b.item[0].parentNode.insertBefore(this.placeholder[0],"down"===this.direction?b.item[0]:b.item[0].nextSibling);var e=this.counter=this.counter?++this.counter:1;this._delay(function(){e===this.counter&&this.refreshPositions(!d)})},_clear:function(a,b){function c(a,b,c){return function(d){c._trigger(a,d,b._uiHash(b))}}this.reverting=!1;var d,e=[];!this._noFinalSort&&
this.currentItem.parent().length&&this.placeholder.before(this.currentItem);this._noFinalSort=null;if(this.helper[0]===this.currentItem[0]){for(d in this._storedCSS)if("auto"===this._storedCSS[d]||"static"===this._storedCSS[d])this._storedCSS[d]="";this.currentItem.css(this._storedCSS);this._removeClass(this.currentItem,"ui-sortable-helper")}else this.currentItem.show();this.fromOutside&&!b&&e.push(function(a){this._trigger("receive",a,this._uiHash(this.fromOutside))});(this.fromOutside||this.domPosition.prev!==
this.currentItem.prev().not(".ui-sortable-helper")[0]||this.domPosition.parent!==this.currentItem.parent()[0])&&!b&&e.push(function(a){this._trigger("update",a,this._uiHash())});this!==this.currentContainer&&!b&&(e.push(function(a){this._trigger("remove",a,this._uiHash())}),e.push(function(a){return function(b){a._trigger("receive",b,this._uiHash(this))}}.call(this,this.currentContainer)),e.push(function(a){return function(b){a._trigger("update",b,this._uiHash(this))}}.call(this,this.currentContainer)));
for(d=this.containers.length-1;0<=d;d--)if(b||e.push(c("deactivate",this,this.containers[d])),this.containers[d].containerCache.over)e.push(c("out",this,this.containers[d])),this.containers[d].containerCache.over=0;this.storedCursor&&(this.document.find("body").css("cursor",this.storedCursor),this.storedStylesheet.remove());this._storedOpacity&&this.helper.css("opacity",this._storedOpacity);this._storedZIndex&&this.helper.css("zIndex","auto"===this._storedZIndex?"":this._storedZIndex);this.dragging=
!1;b||this._trigger("beforeStop",a,this._uiHash());this.placeholder[0].parentNode.removeChild(this.placeholder[0]);if(!this.cancelHelperRemoval)this.helper[0]!==this.currentItem[0]&&this.helper.remove(),this.helper=null;if(!b){for(d=0;d<e.length;d++)e[d].call(this,a);this._trigger("stop",a,this._uiHash())}this.fromOutside=!1;return!this.cancelHelperRemoval},_trigger:function(){!1===e.Widget.prototype._trigger.apply(this,arguments)&&this.cancel()},_uiHash:function(a){var b=a||this;return{helper:b.helper,
placeholder:b.placeholder||e([]),position:b.position,originalPosition:b.originalPosition,offset:b.positionAbs,item:b.currentItem,sender:a?a.element:null}}})});

!function(e){if(e){var n=5242880,a="FileReader"in window;e.fn.upload=function(){return this.each(function(){var n=e(this);if(!n.hasClass("nc-upload-applied")){n.addClass("nc-upload-applied");var a,d=t(n),m=n.find(".nc-upload-files"),g=m.find(".nc-upload-file");if(g.length>0&&!d&&n.find(".nc-upload-input").hide(),g.each(function(){var n=e(this),a=n.data("type"),i=n.find(".nc-upload-file-name");l(n,a,i.html(),i.data("preview-url")?i.data("preview-url"):i.prop("href"),!1)}),setTimeout(function(){s(n)},10),n.on("change",".nc-upload-input",function(){var n,a,l,t,p,s,v;d?(a=e(n=this),l=u(a),t=l.data("maxFiles"),p=l.data("fieldName"),s=function(e){return"multifile_"+e+"["+p+"][]"},v=o(n),a.hide().clone().val("").appendTo(l).show(),a.removeClass("nc-upload-input").appendTo(l.find(".nc-upload-new-files")),e.each(v,function(d){var o=function(e){var n=e.data("nextUploadIndex")||0;return e.data("nextUploadIndex",n+1),n}(l);1==v.length&&a.addClass("nc-upload-file-index--"+o);var u=i(n,d).hide().data("uploadIndex",o).append(f(s("id")),f(s("upload_index"),o),f(s("delete"),0).addClass("nc-upload-file-remove-hidden"));if(l.data("customName")){var p=l.data("customNameCaption"),m=r("text",s("name"),"",p);e('<div class="nc-upload-file-custom-name"/>').append(m).appendTo(u)}t&&"0"!=t&&c(l)>=t?h(u):u.slideDown(100)})):function(e){e.value&&i(e,0).hide().slideDown(100).find(".nc-upload-file-custom-name input:text").focus()}(this)}),m.find(".nc-upload-file-remove").attr("onclick",""),m.on("click",".nc-upload-file-remove",v),p(n).change(function(){i(this,0)}),d&&n.append(f("multifile_js["+n.data("fieldName")+"]",1)),d)m.on("mousedown",".nc-upload-file-drag-handle",function(n){n.preventDefault(),m.addClass("nc--dragging"),a=e(this).closest(".nc-upload-file").addClass("nc--dragged"),e(window).on("mouseup.nc-upload",function(){m.removeClass("nc--dragging"),a.removeClass("nc--dragged"),e(window).off("mouseup.nc-upload"),a=null})}),m.on("mousemove",".nc-upload-file",function(n){var i,l=e(this);if(a&&!l.hasClass("nc--dragged")){if("none"==l.css("float"))i=n.pageY-l.offset().top<l.height()/2;else i=n.pageX-l.offset().left<l.width()/2;i?a.insertBefore(l):a.insertAfter(l)}})}})},e(document).on("apply-upload",function(n){e(".nc-upload").upload()})}function i(i,t){var p;if("hidden"===i.type){var c=i.value,f=c.split("/").pop();p={name:f,type:f.split(".").pop(),url:c+"?"+Math.random().toString().substr(2),size:0}}else p=o(i)[t];var r=e('<div class="nc-upload-file"><div class="nc-upload-file-info"><span class="nc-upload-file-name">'+p.name+'</span> <a href="#" class="nc-upload-file-remove" tabindex="-1">×</a></div></div>'),h=u(i),m=h.find(".nc-upload-files");if(r.appendTo(m),r.find(".nc-upload-file-remove").off("click.nc-upload").on("click.nc-upload",v),d(p.type)){if(p.url)l(r,p.type,p.name,p.url,!0);else if(a&&p.size<n){var g=new FileReader;g.onload=function(e){l(r,p.type,p.name,e.target.result,!0)},g.readAsDataURL(p)}}else l(r,p.type,p.name,null,!0);return s(h),r}function l(n,i,l,o,t){var p=e("<div/>").addClass("nc-upload-file-preview nc-upload-file-drag-handle"),c=o&&d(i);if(c&&a)p.addClass("nc-upload-file-preview-image").append('<img src="'+o+'" />'),u(n).addClass("nc-upload-with-preview");else{var s=l.lastIndexOf(".");p.addClass("nc-upload-file-preview-other"),s>0&&p.append('<span class="nc-upload-file-extension">'+l.substr(s+1)+"</span>")}return n.prepend('<div class="nc-upload-file-drag-icon nc-upload-file-drag-handle"><i class="nc-icon nc--file-text"></i></div>',p),(c||n.closest(".nc-upload-with-preview").length)&&a&&(t?p.slideDown(100):p.show()),p}function d(e){return/^(image\/)?(jpe?g|png|gif|bmp|webp|svg([+-]xml)?)$/i.test(e)}function o(e){return a?e.files:[{name:e.value.substr(e.value.lastIndexOf("\\")+1),type:"",size:0}]}function t(e){return e.hasClass("nc-upload-multifile")}function u(n){return e(n).closest(".nc-upload")}function p(e){return u(e).find(".nc-upload-file-server-path")}function c(n){return n.find(".nc-upload-file").filter(function(){return"none"!==e(this).css("display")}).length}function s(e){var n=t(e)?e.data("maxFiles"):1;if(n){var a=p(e),i=e.find(".nc-upload-input"),l=c(e)<n;a.length?(i.hide(),e.find(".nc-upload-hide-if-full").toggle(l)):i.toggle(l)}}function f(e,n){return r("hidden",e,n)}function r(n,a,i,l){return e("<input />",{type:n,name:a,value:void 0===i?"":i,placeholder:l||""})}function v(n){n.preventDefault();var a=e(n.target).closest(".nc-upload-file"),i=u(a),l=i.find(".nc-upload-input"),d=l;h(a),t(i)?i.find(".nc-upload-file-index--"+a.data("uploadIndex")).val(""):(d=l.clone().val(""),l.replaceWith(d),p(i).val("")),a.slideUp(100,function(){var n="nc-upload-with-preview";i.hasClass(n)&&0==i.find(".nc-upload-file:visible .nc-upload-file-preview-image").length&&i.removeClass(n);var a=e.Event("change");a.target=d[0],e(document).trigger(a),s(i)})}function h(e){e.find(".nc-upload-file-remove-hidden").val(1).trigger("change")}}(window.$nc||window.jQuery);

if (typeof(lsDisplayLibLoaded) == 'undefined') {
    var lsDisplayLibLoaded = true;
    var E_CLICK  = 0,
        E_SUBMIT = 1;

    jQuery(function(){
        var bindEvents = function($container){
            jQuery('[data-nc-ls-display-link]', $container).click(function(){
                eventHandler(this, true, E_CLICK);
                return false;
            });
            jQuery('form[data-nc-ls-display-form]', $container).submit(function(){
                eventHandler(this, true, E_SUBMIT);
                return false;
            });
        }

        var eventHandler = function(element, callBindEvents, event_type){

            switch (event_type) {

                case E_SUBMIT:
                    var url_attr  = 'action';
                    var data_attr = 'data-nc-ls-display-form';
                    break;

                case E_CLICK:
                default:
                    var url_attr  = 'href';
                    var data_attr = 'data-nc-ls-display-link';
                    break;
            }

            var $this    = jQuery(element);
            var url      = $this.attr(url_attr);
            var obj_data = $this.attr(data_attr);

            if (obj_data) {
                obj_data = jQuery.parseJSON(obj_data);
            }
            else {
                return false;
            }

            var replace_content = obj_data.subdivisionId !== false;

            if (url) {
                if (obj_data.displayType == 'shortpage' || (obj_data.displayType == 'longpage_vertical' && typeof(obj_data.subdivisionId) == 'undefined')) {

                    var send_as_post = event_type === E_SUBMIT && $this.attr('method').toLowerCase() === 'post';
                    var send_data    = jQuery.extend({}, obj_data.query); // clone

                    send_data.isNaked       = parseInt(typeof send_data.isNaked !== 'undefined' ? send_data.isNaked : 1);
                    send_data.lsDisplayType = obj_data.displayType;
                    send_data.skipTemplate  = parseInt(send_data.skipTemplate ? send_data.skipTemplate : obj_data.displayType == 'shortpage' && typeof(obj_data.subdivisionId) != 'undefined' ? 1 : 0);

                    if (send_as_post) {
                        url += (url.indexOf('?') >= 0 ? '&' : '?') + jQuery.param(send_data);
                        send_data = $this.serialize();
                    }

                    jQuery.ajax({
                        type:    send_as_post ? 'POST' : 'GET',
                        url:     url,
                        data:    send_data,
                        success: function(data){
                            var $container = [];

                            if (typeof(obj_data.onSubmit) !== 'undefined') {
                                if (data[0] == '{' || data[0] == '[') {
                                    data = jQuery.parseJSON(data);
                                }

                                if ((eval(obj_data.onSubmit)).call($this.get(0), data) === false) {
                                    replace_content = false;
                                }
                            }

                            if ( ! replace_content) {
                                return false;
                            }

                            if (typeof(obj_data.subdivisionId) == 'undefined') {
                                $container = $this.closest('[data-nc-ls-display-container]');
                            } else {
                                jQuery('[data-nc-ls-display-container]').each(function(){
                                    var $element = jQuery(this);
                                    var containerData = $element.attr('data-nc-ls-display-container');
                                    if (containerData) {
                                        containerData = jQuery.parseJSON(containerData);
                                        if (containerData.subdivisionId == obj_data.subdivisionId) {
                                            $container = $element;
                                            return false;
                                        }
                                    }

                                    return true;
                                });
                            }

                            if (!$container.length) {
                                $container = jQuery('[data-nc-ls-display-container]');
                            }

                            $container.html(data);

                            if (callBindEvents) {
                                bindEvents($container);
                            }

                            if (typeof(parent.nc_ls_quickbar) != 'undefined') {
                                var quickbar = parent.nc_ls_quickbar;
                                if (quickbar) {
                                    var $quickbar = jQuery('.nc-navbar').first();
                                    $quickbar.find('.nc-quick-menu LI:eq(0) A').attr('href', quickbar.view_link);
                                    $quickbar.find('.nc-quick-menu LI:eq(1) A').attr('href', quickbar.edit_link);
                                    $quickbar.find('.nc-menu UL LI:eq(0) A').attr('href', quickbar.sub_admin_link);
                                    $quickbar.find('.nc-menu UL LI:eq(1) A').attr('href', quickbar.template_admin_link);
                                    $quickbar.find('.nc-menu UL LI:eq(2) A').attr('href', quickbar.admin_link);
                                }
                            }
                        }
                    });

                } else if (obj_data.displayType == 'longpage_vertical') {
                    var scrolled = false;

                    var scrollToContainer = function(containerData, $element){
                        if (containerData) {
                            containerData = jQuery.parseJSON(containerData);
                            if (containerData.subdivisionId == obj_data.subdivisionId) {
                                jQuery('HTML,BODY').animate({
                                    scrollTop: $element.offset().top - jQuery('BODY').offset().top
                                }, containerData.animationSpeed);
                                return true;
                            }
                        }

                        return false;
                    };

                    jQuery('[data-nc-ls-display-pointer]').each(function(){
                        var $element = jQuery(this);
                        if (scrollToContainer($element.attr('data-nc-ls-display-pointer'), $element)) {
                            scrolled = true;
                            return false;
                        }

                        return true;
                    });

                    if (!scrolled) {
                        jQuery('[data-nc-ls-display-container]').each(function(){
                            var $element = jQuery(this);

                            if (scrollToContainer($element.attr('data-nc-ls-display-container'), $element)) {
                                return false;
                            }

                            return true;
                        });
                    }
                }

                if (replace_content) {
                    if (!!(window.history && history.pushState)) {
                        window.history.pushState({}, '', url);
                    }
                }

                if (event_type === E_CLICK) {
                    if (typeof(obj_data.onClick) == 'undefined') {
                        $this.addClass('active').siblings().removeClass('active');
                    } else {
                        eval('var callback = ' + obj_data.onClick);
                        callback.call($this.get(0));
                    }
                }

                return false;
            }
        }

        jQuery('[data-nc-ls-display-link]').click(function(){
            eventHandler(this, true, E_CLICK);
            return false;
        });

        jQuery('form[data-nc-ls-display-form]').submit(function(){
            eventHandler(this, true, E_SUBMIT);
            return false;
        });

        jQuery('[data-nc-ls-display-pointer]').each(function(){
            var $this = jQuery(this);
            var data = jQuery.parseJSON($this.attr('data-nc-ls-display-pointer'));
            if (data.onReadyScroll) {
                setTimeout(function(){
                    jQuery('HTML,BODY').scrollTop($this.offset().top);
                }, 1000);
                return false;
            }

            return true;
        });
    });
}
