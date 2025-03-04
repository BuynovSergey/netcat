<?php if ( ! defined('NC')) exit ?>
<?/*------------------------------------------------------------------------*/?>

<? example('nc-tooltip') ?>

<div class="nc-tooltip" style="display: block">
    Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nam enim dolorum placeat, quis assumenda esse perferendis, repellat id non odio necessitatibus nulla, aliquid dolor. Distinctio aliquam hic libero voluptate eos?
</div>

<br>
<br>


<? example('nc-popover') ?>

<div id="popovers_div">

    <button class='nc-btn nc--light' data-trigger='load' data-placement='left-center' data-content='Lorem ipsum dolor sit amet, consectetur adipisicing elit'>left-center (load)</button>
    <button class='nc-btn nc--orange' data-trigger='mouseover' data-style='orange' data-placement='top-center' data-content='Lorem ipsum dolor sit amet, consectetur adipisicing elit'>top-center (mouseover)</button>
    <button class='nc-btn nc--blue' data-trigger='click' data-style='blue' data-placement='bottom-left' data-content='Lorem ipsum dolor sit amet, consectetur adipisicing elit'>bottom-left (click)</button>
    <button class='nc-btn nc--red' data-placement='right-top'  data-style='red' data-content='Lorem ipsum dolor sit amet, consectetur adipisicing elit'>right-top (default: click)</button>

</div>

<script type="text/javascript">
    nc.ui.popover('#popovers_div button');
</script>


<? example('Экран с интрукциями') ?>


<button class='nc-btn nc--green' id='enable_help_overlay'>Включить</button>
<script>
    nc(function(){
        var help_overlay = nc.ui.help_overlay({
                padding:    5,
                trigger:    'load',
                placement:  'top-center'
            })
            .add({
                target:    '#tooltip .tabs',
                content:   'Переключиет вкладки что бы посмотреть исходный код',
                placement: 'bottom-right',
                trigger:   'mouseover'
            })
            .add({
                target:    '#enable_help_overlay',
                content:   'Кнопка на которую вы нажали',
                placement: 'bottom-left',
                padding:   10,
                style:     'green'
            })
            .add({
                target: '#popovers_div',
                padding: 10,
                content: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit'
            });

        nc('#enable_help_overlay').click(function(){
            help_overlay.show();
        });
    });
</script>


