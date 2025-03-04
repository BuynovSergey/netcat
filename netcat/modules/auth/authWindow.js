nc_authWindow = {

    width: 500,
    height: 300,

    /**
     * Показываем окно с предложением залогиниться
     * @param login - показ блока логина
     * @param reg - показ ссылки на регистрацию
     * @param ex - показ кнопок авторизации через внешние сервисы
     */
    show: function (login,reg,ex){
        $("#comments_login_form").show();

        var w = parseInt($('#comments_login_form').css('width'));
        var h = parseInt($('#comments_login_form').css('height'));
        var clStr = $("#comments_login_form form").attr('class');
        // если у нас стандартная форма - оформляем окно
        if ( typeof clStr == 'undefined'){
            $('#comments_login_form').css('padding','25px');
            $('#comments_login_form').css('border','1px solid gray');
        };


        $('#comments_login_form').css('margin-left','-'+(w/2)+'px');
        $('#comments_login_form').css('margin-top','-'+(h/2)+'px');
    }

}