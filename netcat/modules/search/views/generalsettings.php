<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_settings_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVE);

$stopwords_filter = new nc_search_extension_rule;
$stopwords_filter->load_where('extension_class', 'nc_search_language_filter_stopwords');

$shortwords_filter = new nc_search_extension_rule;
$shortwords_filter->load_where('extension_class', 'nc_search_language_filter_minlength');

// ОБРАБОТАТЬ ВХОДЯЩИЕ ДАННЫЕ
// (1) purge[now] + purge[interval], [interval_value], [interval_type]
// (2) s[]
$new_settings = $this->get_input('s', array());
$purge = $this->get_input('purge', array());
if (isset($purge["now"]) && $purge["now"]) {
    $nc_core->token->exit_if_invalid();
    if ($purge["interval"] == -1) { // грохнуть всё
        $purge["interval_value"] = 1;
        $purge["interval_type"] = "second";
    }
    nc_search::purge_history($purge["interval_value"], $purge["interval_type"]);
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGED, 'ok');
} else if ($new_settings) {
    $nc_core->token->exit_if_invalid();

    foreach ($new_settings as $k => $v) {
        nc_search::save_setting($k, $v);
    }

    // enable/disable extensions depending on the settings
    $remove_stopwords = nc_search::get_setting('RemoveStopwords');
    if ($stopwords_filter && $stopwords_filter->get('enabled') != $remove_stopwords) {
        $stopwords_filter->set('enabled', $remove_stopwords)->save();
    }

    $remove_shortwords = (nc_search::get_setting('MinWordLength') > 1);
    if ($shortwords_filter && $shortwords_filter->get('enabled') != $remove_shortwords) {
        $shortwords_filter->set('enabled', $remove_shortwords)->save();
    }

    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVED, 'ok');
}

// ПОКАЗАТЬ ФОРМУ

$log_purge_interval = nc_search::get_setting('AutoPurgeHistoryIntervalValue');
?>

<form method="POST" class="settings">
<?= $nc_core->token->get_input() ?>
<input type="hidden" name="view" value="generalsettings"/>
<br/>

<div id="enable_search_checkbox">
    <?=$this->setting_cb('EnableSearch', NETCAT_MODULE_SEARCH_ADMIN_SETTING_ENABLE_SEARCH) ?>
</div>
<div style="margin-left: 20px;">
<script type="text/javascript">
    // обработка нажатия на checkbox 'EnableSearch'
    (function () {
        var toggle = function () {
            var enabled = $nc('#cb_EnableSearch').prop('checked');
            $nc('#search_module_settings input, #search_module_settings select, #search_module_settings textarea')
                    .prop('disabled', !enabled);
        }
        $nc('#cb_EnableSearch').click(toggle);
        $nc(toggle);
    })();
</script>
<div id="search_module_settings">
<fieldset>
    <legend><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_INDEXING ?></legend>
    <div class="setting">
        <div class="caption"><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_DISABLE_SECTION_INDEXING ?>:
        </div>
        <div class="textarea"><textarea class="no_cm"
             name="s[ExcludeUrlRegexps]"><?=htmlspecialchars(nc_search::get_setting('ExcludeUrlRegexps'))
            ?></textarea></div>
    </div>

    <div class="setting">
        <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_SETTING_MAX_DOCUMENT_LENGTH,
                   "<input type='text' name='s[CrawlerMaxDocumentSize]' class='i7' value='" .
                   htmlspecialchars(nc_search::get_setting('CrawlerMaxDocumentSize')) . "'>")
        ?>
    </div>

    <div class="setting">
        <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_SETTING_CRAWLER_DELAY,
                   "<input type='text' name='s[CrawlerDelay]' class='i3' value='" .
                   htmlspecialchars(nc_search::get_setting('CrawlerDelay')) . "'>")
        ?>
    </div>
    <?=$this->setting_cb('CrawlerObeyRobotsTxt', NETCAT_MODULE_SEARCH_ADMIN_SETTING_USE_ROBOTS_TXT) ?>
    <?=$this->setting_cb('CrawlerCheckLinks', NETCAT_MODULE_SEARCH_ADMIN_SETTING_CHECK_LINKS) ?>
    <?=$this->setting_cb('CrawlerCheckOutsideLinks', NETCAT_MODULE_SEARCH_ADMIN_SETTING_CHECK_EXTERNAL_LINKS) ?>
    <?=$this->setting_cb('IgnoreNumbers', NETCAT_MODULE_SEARCH_ADMIN_SETTING_IGNORE_NUMBERS) ?>
    <?=$this->setting_cb('RemoveStopwords',
        sprintf(NETCAT_MODULE_SEARCH_ADMIN_SETTING_USE_STOPWORDS, $this->hash_href("#module.search.stopwords")),
                ($stopwords_filter && $stopwords_filter->get('enabled')))
    ?>
    <div class="setting">
        <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_SETTING_MINIMUM_WORD_LENGTH,
                   "<input type='text' name='s[MinWordLength]' class='i3' value='" .
                   ($shortwords_filter && $shortwords_filter->get('enabled')
                           ? htmlspecialchars(nc_search::get_setting('MinWordLength'))
                           : 1) .
                   "'>")
        ?>
    </div>

</fieldset>

<fieldset>
    <legend><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_CORRECTION ?></legend>
    <?=$this->setting_cb('TryToCorrectQueries', NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_CORRECTION_ENABLED) ?>
    <blockquote id="correction_options">
        <?=$this->setting_cb('RemovePhrasesOnEmptyResult', NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_PHRASES) ?>
        <?=$this->setting_cb('ChangeLayoutOnEmptyResult', NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_LAYOUT) ?>
        <?=$this->setting_cb('BreakUpWordsOnEmptyResult', NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_BREAK_WORDS_UP) ?>
        <?=$this->setting_cb('PerformFuzzySearchOnEmptyResult', NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_FUZZY) ?>
        <blockquote>
            <div class="setting">
                <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_SIMILARITY_FACTOR,
                           "<input type='text' name='s[FuzzySearchOnEmptyResultSimilarityFactor]' class='i3'  id='t_sf' value='" .
                           htmlspecialchars(nc_search::get_setting('FuzzySearchOnEmptyResultSimilarityFactor')) . "'>")
                ?>
            </div>
        </blockquote>

        <div class="setting">
            <?=sprintf(NETCAT_MODULE_SEARCH_ADMIN_SETTING_CORRECTION_MAXIMUM_QUERY_LENGTH,
                       "<input type='text' name='s[MaxQueryLengthForCorrection]' class='i3' value='" .
                       htmlspecialchars(nc_search::get_setting('MaxQueryLengthForCorrection')) . "'>")
            ?>
        </div>
    </blockquote>
    <script type="text/javascript">
        // блок «Удалять запросы»
        // обработка нажатия на checkbox 'TryToCorrectQueries'
        (function () {
            var toggle = function () {
                var enabled = $nc('#cb_TryToCorrectQueries').prop('checked');
                $nc('#correction_options input').prop('disabled', !enabled);
            }
            $nc('#cb_TryToCorrectQueries').click(toggle);
            $nc(toggle);
        })();

        // опция "FuzzySearchOnEmptyResultSimilarityFactor
        (function () {
            var toggle = function () {
                var enabled = $nc('#cb_PerformFuzzySearchOnEmptyResult').prop('checked');
                $nc('#t_sf').prop('disabled', !enabled);
            }
            $nc('#cb_PerformFuzzySearchOnEmptyResult').click(toggle);
            $nc(toggle);
        })();
    </script>
</fieldset>

<fieldset>
    <legend><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG ?></legend>
    <?=$this->setting_cb('SaveQueryHistory', NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_ENABLED) ?>
    <div class="subgroup">
        <div class="caption"><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE ?>:
        </div>
        <blockquote>
            <div class="setting">
                <input type="hidden" name="s[AutoPurgeHistoryIntervalValue]"
                       value="0"/>
                <input type="radio" name="s[AutoPurgeHistory]" value="0"
                       id="r_ph0"
                       <?=(!$log_purge_interval ? " checked" : "") ?>
                />
                <label for="r_ph0"><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_NEVER ?></label>
            </div>
            <div class="setting">
                <input type="radio" name="s[AutoPurgeHistory]" value="1"
                       id="r_ph1"
                       <?=($log_purge_interval ? " checked" : "") ?>/>
                <label for="r_ph1"><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_BEFORE ?></label>
                <input type="text" name="s[AutoPurgeHistoryIntervalValue]"
                       class="i3"
                       id="purge_interval"
                       value="<?=htmlspecialchars($log_purge_interval) ?>"/>
                <select name="s[AutoPurgeHistoryIntervalUnit]">
                    <?php
                    $value = nc_search::get_setting('AutoPurgeHistoryIntervalUnit');
                    foreach (array('months', 'days', 'hours') as $i) {
                        print "<option value='$i'" . ($i == $value ? ' selected' : '') . ">" .
                            constant("NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_BEFORE_" . strtoupper($i));
                    }
                    ?>
                </select>
            </div>
            <script type="text/javascript">
                // обработка выбора радиокнопки «удалять запросы никогда»
                $nc('#r_ph0').click(function () { $nc('#purge_interval').val(''); });
                // обработка ввода в поле «раньше...»
                $nc('#purge_interval').keyup(function () {
                    var radio = ($nc(this).val() ? '#r_ph1' : '#r_ph0');
                    $nc(radio).prop('checked', 'checked');
                });
            </script>
        </blockquote>
    </div>
    <div class="subgroup">
        <div class="caption">
            <a class="internal" href="#"
               onclick="$nc('#purge_now').toggle(300); return false">
                <?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_NOW ?>
            </a>
        </div>
        <blockquote id="purge_now">
            <div class="setting">
                <input type="radio" name="purge[interval]" value="-1"
                       id="r_pnow0" <?=(!$log_purge_interval ? " checked" : "") ?>/>
                <label for="r_pnow0"><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_NOW_EVERYTHING ?></label>
            </div>
            <div class="setting">
                <input type="radio" name="purge[interval]" value="1"
                       id="r_pnow1"/>
                <label for="r_pnow1"><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_BEFORE
                    ?></label>
                <input type="text" name="purge[interval_value]" class="i3"
                       id="purge_now_interval"/>
                <select name="purge[interval_type]">
                    <?php
                        foreach (array('months', 'days', 'hours') as $i) {
                            print "<option value='$i'>" . constant("NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_BEFORE_" . strtoupper($i));
                        }
                    ?>
                </select>
            </div>
            <script type="text/javascript">
                // блок «Очистить список поисковых запросов»
                // обработка выбора радиокнопки «полностью»
                $nc('#r_pnow0').click(function () { $nc('#purge_now_interval').val(''); });
                // обработка ввода в поле «раньше...»
                $nc('#purge_now_interval').keyup(function () {
                    var radio = ($nc(this).val() ? '#r_pnow1' : '#r_pnow0');
                    $nc(radio).prop('checked', 'checked');
                });
            </script>
            <div class="submit_button">
                <input type="submit" name="purge[now]"
                       title="<?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_NOW_SUBMIT ?>"
                       value="<?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_LOG_PURGE_NOW_SUBMIT ?>"/>
            </div>
        </blockquote>
    </div>
</fieldset>

<fieldset>
    <legend><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_FEATURES ?></legend>
    <div class="setting">
        <?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_DEFAULT_OPERATOR ?>:
        <select name="s[DefaultBooleanOperator]">
            <option value="AND"><?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_DEFAULT_OPERATOR_AND ?></option>
            <option value="OR"<?=(nc_search::get_setting('DefaultBooleanOperator') == 'OR' ? ' selected' : '') ?>>
                <?=NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_DEFAULT_OPERATOR_OR ?>
            </option>
        </select>
    </div>
    <?=$this->setting_cb('AllowTermBoost', NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_TERM_BOOST) ?>
    <?=$this->setting_cb('AllowProximitySearch', NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_PROXIMITY_SEARCH) ?>
    <?=$this->setting_cb('AllowWildcardSearch', NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_WILDCARD_SEARCH) ?>
    <?=$this->setting_cb('AllowRangeSearch', NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_RANGE_SEARCH) ?>
    <a name="fuzzy"></a>
    <?=$this->setting_cb('AllowFuzzySearch', NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_FUZZY_SEARCH) ?>
    <?=$this->setting_cb('AllowFieldSearch', NETCAT_MODULE_SEARCH_ADMIN_SETTING_QUERY_FIELD_SEARCH) ?>
</fieldset>

<script type="text/javascript">
    // скроллинг и подсветка при переходе по «внутренней» ссылке (anchor)
    $nc('a.internal.on_page').click(function () {
        var name = $nc(this).attr('href').substr(1),
                target = $nc("a[name='" + name + "']").next('div').find('label');

        $nc('body,html').animate({scrollTop:target.offset().top - 100}, 500);
        setTimeout(function () {
            target.css({opacity:0, backgroundColor:'#B6D0E8'})
                    .animate({opacity:1}, 300)
        }, 600);
        setTimeout(function () { target.css('backgroundColor', ''); }, 2600);
        return false;
    });

    // prevent purge button submit activation when enter is pressed
    $nc('input').keypress(function (e) {
        if (e.which == 13) { e.preventDefault(); }
    })
</script>
</div>
</div>
</form>