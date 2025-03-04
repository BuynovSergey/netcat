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
