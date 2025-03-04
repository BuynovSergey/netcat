
jQuery('document').ready(function() {
    jQuery('#CountersList').change(function () {
        document.location.href = '?phase=' + jQuery('#CountersList').val();
    });
});