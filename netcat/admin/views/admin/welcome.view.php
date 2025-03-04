<script>
nc(function(){
    var screen_width = nc(window).width();

    var hash = window.location.hash;
    if (hash && hash != '#index') {
        return false;
    }
    var welcome_overlay = nc.ui.help_overlay({
            // style: 'black',
            trigger:  'load',
            padding:  0
        })
        .add({
            axis:      [<?=$nc_core->modules->get_by_keyword('netshop') ? '760' : '662' ?>,11,95,32],
            width:      <?=$nc_core->modules->get_by_keyword('netshop') ? '300' : '200' ?>,
            placement: 'left-top',
            content:   '<?=WELCOME_SCREEN_TOOLTIP_SUPPORT ?>'
        })
        .add({
            axis:      [10,117,245,30],
            placement: 'top-left',
            content:   '<?=WELCOME_SCREEN_TOOLTIP_SIDEBAR ?>'
        })
        .add({
            axis:      [10,117+30+5,245,310],
            placement: 'bottom-left',
            content:   '<?=WELCOME_SCREEN_TOOLTIP_SIDEBAR_SUBS ?>',
            width:     screen_width < 1200 ? 215 : 300
        });

    // if (!is_small_screen) {
        welcome_overlay.add({
            axis:      [805,70,150,150],
            padding:   10,
            width:     screen_width > 1250 ? 200 : 160,
            placement: screen_width > 1190 ? 'right-center' : 'bottom-center',
            content:   '<?=WELCOME_SCREEN_TOOLTIP_TRASH_WIDGET ?>'
        });
    // }


    welcome_overlay.show();

    var createCookie = function(name, value, days) {
        var expires;
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toGMTString();
        }
        else {
            expires = "";
        }
        document.cookie = name + "=" + value + expires + "; path=/";
    }

    nc('#nc_welcome_modal')
        .fadeIn()
        .click(function(){
            createCookie('nc_welcome_is_showed', 1, 356 * 3);
            welcome_overlay.hide();
            nc(this).hide();
            return false;
        });
});

</script>

<div id='nc_welcome_modal' class='nc-shadow-large nc-padding-50' style='display:none; overflow: hidden; position: fixed; z-index: 9999; width: 350px; background: #fff; left:50%; top:50%; margin: -200px 0 0 -225px'>
    <?=WELCOME_SCREEN_MODAL_TEXT ?>
    <button class='nc-btn nc--blue nc--xlarge nc--right nc-long-shadow'><?=WELCOME_SCREEN_BTN_START ?></button>
</div>