<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_settings_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SAVE);
$ui->add_back_button();

$rule = $this->data_form('nc_search_rule', 'rules');
if ($this->get_input('copy')) {
    $rule->load($this->get_input('copy')); // ->set_id(null);
}
if (!isset($search_providers)) {
    $search_providers = array();
}
$form_description = array(
        'name' => array(
                'type' => 'string',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_RULE_NAME.':'
        ),
        'site_id' => array(
                'type' => 'select',
                'values' => $search_providers,
                'subtype' => 'sql',
                'sqlquery' => "SELECT `Catalogue_ID` as `id`, ".
                              "       CONCAT(`Catalogue_Name`, ' ', IF(LENGTH(`Domain`)>0, CONCAT('(', `Domain`, ')'), '')) as `name`".
                              "  FROM `Catalogue`",
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_RULE_SITE.':',
                'default_value' => 1
        ),
//  'start_url' => array(
//      'type' => 'string',
//      'caption' => NETCAT_MODULE_SEARCH_ADMIN_RULE_START_URL . ':'
//  )
);

$form = new nc_a2f($form_description, "data");
$form->set_value($rule);

$area = $rule->get('area_string');
$interval = $rule->get('interval');
$type = $rule->get('interval_type');

$time_input = "<span class='rule_time'>".
              "<input type='text' name='data[hour][]' value='".sprintf("%02d", $rule->get('hour'))."' maxlength='2' />:".
              "<input type='text' name='data[minute][]' value='".sprintf("%02d", $rule->get('minute'))."' maxlength='2' />".
              "</span>";
$int_input = "<input type='text' class='rule_interval_input' name='data[interval][]' value='$interval' />";

?>

<!--<fieldset>-->
<!--    <legend>--><?//= NETCAT_MODULE_SEARCH_ADMIN_RULE ?><!--</legend>-->
    <?=$form->render("<div>", "", "</div>", ""); ?>
    <div class="ncf_row space_before">
        <div class="ncf_caption"><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_TO_INDEX ?>:</div>
        <div class="rule_radio">
            <!-- ↓ sic (ignored) -->
            <input type="radio" name="area" value="site" id="r-site" <?=($area ? "" : "checked") ?> />
            <label for="r-site"><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_WHOLE_SITE ?></label>
        </div>
        <div class="rule_radio">
            <input type="radio" name="area" value="area" id="r-area" <?=($area ? " checked" : "") ?> />
            <label for="r-area"><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_AREAS ?></label>
        </div>
        <div>
            <textarea id="r-area-input" rows="5" cols="80" name="data[area_string]" class="rule_area no_cm"><?=htmlspecialchars($area) ?></textarea>
        </div>
    </div>
    <div class="ncf_row space_before">
        <div class="ncf_caption"><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_FREQUENCY ?>:</div>
        <!-- ( ) ежедневно в [  ]:[  ] -->
        <div class="rule_interval_row">
            <input type="radio" name="data[interval_type]" value="daily" <?=(($interval == 1 && $type == "day") ? " checked" : "") ?> />
            <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_DAILY, $time_input) ?>
        </div>
        <!-- ( ) каждые [  ] [ часов/минут [ | ] начиная с [  ]:[  ] -->
        <div class="rule_interval_row">
            <input type="radio" name="data[interval_type]" value="minute" <?=($type == "minute" ? " checked" : "") ?> />
            <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_N_MINUTES, $int_input, $time_input) ?>
        </div>
        <div class="rule_interval_row">
            <input type="radio" name="data[interval_type]" value="hour" <?=($type == "hour" ? " checked" : "") ?> />
            <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_N_HOURS, $int_input, $time_input) ?>
        </div>
        <!-- ( ) каждые [  ] дней в [  ]:[  ]  -->
        <div class="rule_interval_row">
            <input type="radio" name="data[interval_type]" value="day" <?=($type == "day" && $interval > 1 ? " checked" : "") ?> />
            <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_N_DAYS, $int_input, $time_input) ?>
        </div>
        <!-- ( ) каждое [  ] число месяца в [  ]:[  ]  -->
        <div class="rule_interval_row">
            <input type="radio" name="data[interval_type]" value="day_of_month" <?=($type == "day_of_month" ? " checked" : "") ?> />
            <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_EVERY_X_DAY, $int_input, $time_input) ?>
        </div>
        <!-- ( ) только по запросу -->
        <div class="rule_interval_row rule_interval_row_last">
            <input type="radio" name="data[interval_type]" value="on_request" id="r-req" <?=($type == "on_request" ? " checked" : "") ?> />
            <label for='r-req'><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_INTERVAL_ON_REQUEST ?></label>
        </div>

    </div>
<!--</fieldset>-->

<script type="text/javascript">
    (function($) {

        $.fn.extend({
            clear: function() {
                this.each(function() {
                    var el = $(this), val = el.val();
                    if (val != '' && val != undefined) { el.data('value', val); }
                    el.val('');
                });
                return this;
            },
            restore: function() {
                this.each(function() {
                    var el = $(this), val = el.val();
                    if (val == '' || val == undefined) { el.val(el.data('value')); }
                });
                return this;
            }
        });
  
        var row_class = "div.rule_interval_row",
        rows = $(row_class),
        // очищать инпуты в строках, которые не выбраны
        clear_rows = function() {
            rows.each(function() {
                var div = $(this);
                if (div.find("input:radio:checked").size() == 0) { div.find("input:text").clear(); }
            });
        },
        // выбрать строку при изменении инпута в ней
        select_row = function() {
            var el = $(this),
            row = el.is(row_class) ? el : el.parents(row_class);
            row.find("input:radio").prop('checked', 'checked');
            row.find("input:text").restore();
            clear_rows();
        }

        rows.click(select_row);
        rows.find("input:text").change(select_row);

        // init
        clear_rows();

        // очищать textarea с областями при выборе "весь сайт"
        $('#r-site').click(function() { $('#r-area-input').clear(); });
        $('#r-area-input').change(function() { $('#r-area').click(); });
        $('#r-area').click(function() { $('#r-area-input').restore(); });

    })($nc);
</script>