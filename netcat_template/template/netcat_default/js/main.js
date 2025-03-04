function isValidEmailAddress(emailAddress) {
    var pattern = new RegExp(/^(('[\w-\s]+')|([\w-]+(?:\.[\w-]+)*)|('[\w-\s]+')([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
    return pattern.test(emailAddress);
}

function get_cookie ( cookie_name )
{
    var results = document.cookie.match ( '(^|;) ?' + cookie_name + '=([^;]*)(;|$)' );

    if ( results )
        return ( unescape ( results[2] ) );
    else
        return null;
}

function delCookie(name) {
    document.cookie = name + "=" + "; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT";
}

function setCookie(name, value, options) {
    options = options || {};
    var expires = options.expires;
    if (typeof expires == "number" && expires) {
        var d = new Date();
        d.setTime(d.getTime() + expires*1000);
        expires = options.expires = d;
    }
    if (expires && expires.toUTCString) {
        options.expires = expires.toUTCString();
    }
    value = encodeURIComponent(value);
    var updatedCookie = name + "=" + value+'; path=/;';
    document.cookie = updatedCookie;
}

function acceptCookie(){
    $('.b-cookie').fadeOut();
    let dateCookie = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toUTCString();
    setCookie('cookie-accept', 1, dateCookie);
}

function morph(n, f1, f2, f5) {
    n = Math.abs(parseInt(n)) % 100;
    if (n>10 && n<20) return f5;
    n = n % 10;
    if (n>1 && n<5) return f2;
    if (n==1) return f1;
    return f5;
}

function formatNum(str) {
    var retstr = '';
    var now = 0; console.log(str);
    for (let i = str.length - 1; i >= 0; i--) {
        if (now < 3) {
            now++;
            retstr = str.charAt(i) + retstr;
        } else {
            now = 1;
            retstr = str.charAt(i) + ' ' + retstr;
        }
    }
    return retstr;
}

function changeFile(el) { console.log(el);
    let val = el.value;
    if(val) {
        var valArr = val.split('\\');
        el.closest('.form-group-file').find('.file-text').text(valArr[valArr.length - 1]);
    } else {
        el.closest('.form-group-file').find('.file-text').text('Прикрепить файл');
    }
}

$(document).ready(function(){
    $(window).scroll(function () {
        if ($(this).scrollTop() > 0) {
            $('.topcontrol').fadeIn();
        } else {
            $('.topcontrol').fadeOut();
        }
    });

    $('.topcontrol').click(function () {$('body,html').animate({scrollTop: 0}, 400); return false;});

    if ($("input[name=f_Phone],input[name=phone]").length > 0) {
        Inputmask("+7 (999) 999-99-99", {
            placeholder: "_",
            greedy: false
        }).mask('input[name=f_Phone]');
    }

    $('body').on('submit', 'form', function (e) {
        //e.preventDefault();
        let form = $(this);
        let error = false;

        form.find('input, textarea').each(function () { //:not([type=hidden])
            $(this).closest('.form-group').removeClass('form-group-error');
            var currentError = false;
            var inputName = $(this).attr('name');

            var val = $(this).val().trim();

            if (val.length == 0 && $(this).hasClass('required') && $(this).is(':visible')) {
                currentError = true;
            }

            if (currentError === false && val.length > 0 && $(this).is(':visible')) {
                /*if(
                    (
                        inputName == 'PHONE'
                        || $(this).hasClass('login-phone')
                    )
                    && val.replace(/[^0-9]/g, '').length !== 11) {
                    currentError = true;
                }*/
                if(
                    (
                        $(this).hasClass('input-email')
                        || inputName == 'EMAIL'
                        || inputName == 'USER_EMAIL'
                    )
                    && !isValidEmailAddress(val)) {
                    currentError = true;
                }
            }

            if($(this).attr('type') === 'checkbox' || $(this).attr('type') === 'radio') {
                if($('input[name="'+$(this).attr('name')+'"]').is(':checked') == false) {
                    currentError = true;
                }
            }

            if(currentError) {
                error = true;
                $(this).closest('.form-group').addClass('form-group-error');
            }

        });

        if(!error) {
            return true;
        } else {
            return false;
        }
    });

});
