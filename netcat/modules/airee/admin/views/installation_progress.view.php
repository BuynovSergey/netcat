<?php if (!class_exists('nc_core')) { die; } ?>

<?php
/** @var nc_ui $ui */
/** @var int $time_left */
/** @var int $timeout */

$UI_CONFIG->actionButtons = array();
?>

<h2><?= NETCAT_MODULE_AIREE_INSTALLATION_IN_PROGRESS; ?></h2>

<div class="nc-progress nc--loading">
    <div id="js-nc-airee-installation-progress-bar"
         data-timeout="<?= $timeout; ?>"
         data-time-left="<?= $time_left; ?>"
         class="nc-progress-bar"
         style="width: <?= floor(($timeout - $time_left) / $timeout * 100); ?>%"></div>
</div>

<script>
    var updateInterval = 50;
    var progressBar = document.getElementById('js-nc-airee-installation-progress-bar');
    var timeout = parseInt(progressBar.getAttribute('data-timeout'), 10);
    var progressTimer = window.setInterval(function() {
        var time_left = parseFloat(progressBar.getAttribute('data-time-left'));
        var progress = (timeout - time_left) / timeout * 100;

        if (progress <= 100) {
            progressBar.style.width = progress + '%';
        } else {
            clearInterval(progressTimer);
            window.top.location.reload();
        }
        progressBar.setAttribute('data-time-left', (time_left - updateInterval / 1000).toString());
    }, updateInterval);
</script>