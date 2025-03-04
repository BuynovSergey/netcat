/**
 * Отрисовывает reCAPTCHA во всех формах на странице, где включена капча.
 * (Стандартный способ инициализации добавляет reCAPTCHA только в первую форму.)
 */
function nc_recaptcha_render() {
    function getDataAttributes(attributes) {
        var data = {}, match;
        for (var i = 0; i < attributes.length; i++) {
            if (match = attributes[i].name.match(/^data-(.+)/)) {
                data[match[1]] = attributes[i].value;
            }
        }
        return data;
    }

    var recaptchas = document.querySelectorAll('.g-recaptcha'),
        grecaptcha = window.grecaptcha;

    if (grecaptcha && grecaptcha.render) {
        for (var i = 0; i < recaptchas.length; i++) {
            if (!recaptchas[i].children.length) { // reCAPTCHA ругается в консоль, если <div> не пустой
                grecaptcha.render(recaptchas[i], getDataAttributes(recaptchas[i].attributes));
            }
        }
    }
}

// На случай, если загрузка скрипта recaptcha api.js произошла до загрузки всей страницы,
// пробуем повторно инициализировать формы после готовности DOM
if (document.addEventListener) {
    document.addEventListener("DOMContentLoaded", nc_recaptcha_render);
}

/**
 * Сохраняет значение из g-recaptcha-response в nc_captcha_code
 * (для обратной совместимости со встроенной каптчей Netcat)
 */
function nc_recaptcha_save() {
    var forms = document.forms,
        recaptchaField = 'g-recaptcha-response',
        netcatField = 'nc_captcha_code';

    for (var i = 0; i < forms.length; i++) {
        var formElements = forms[i].elements;
        // в форме есть поле g-recaptcha-response?
        if (recaptchaField in formElements)
        {
            // если нет поля nc_captcha_code, создаём его
            var recaptchaValue = formElements[recaptchaField].value;
            if (!(netcatField in formElements)) {
                var input = document.createElement('input'),
                    attributes = {
                        type: 'hidden',
                        name: netcatField,
                        value: recaptchaValue
                    };
                for (var a in attributes) {
                    input.setAttribute(a, attributes[a]);
                }
                forms[i].appendChild(input);
            }
            else {
                formElements[netcatField].value = recaptchaValue;
            }
        }
    }
}