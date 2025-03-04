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
