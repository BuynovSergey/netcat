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
