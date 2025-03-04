function sumbit_form ( action ) {
    document.getElementById('redirect_list').action = document.getElementById('redirect_list').action + action;
    parent.mainView.submitIframeForm('redirect_list');
    return 0;
}

function nc_action_ajax(url, redirect) {
    $nc('#redirect_' + redirect + ' span').html('<i class="nc-icon nc--loading">');
    
    var res = $nc.ajax({
                  url: url + '&naked=1' + '&' + nc_token,
                  type: "GET",
                  async: false
              }).responseText;

    if (res == 1) {
        var check = $nc('#redirect_' + redirect).attr('href').slice(-1);
        $nc('#redirect_' + redirect + ' span').html(check_text[check]);
        $nc('#redirect_' + redirect + ' span').attr('class' , check == 1 ? 'nc-text-green nc-h4' : 'nc-text-red nc-h4' );
        url = $nc('#redirect_' + redirect).attr('href').slice(0, - 1);
        check = check == 1 ?  0 : 1;
        $nc('#redirect_' + redirect).attr('href' , url + check);
    }

    return false;
}

function check_all() {
    $nc('.redirect_box').each(function(){this.checked = $nc('#checkall').is(':checked');});
}