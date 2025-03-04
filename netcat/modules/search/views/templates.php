<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_settings_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVE);

// СОХРАНИТЬ НАСТРОЙКИ
$new_settings = $this->get_input('s', array());
$nc_core = nc_Core::get_object();
if (!$nc_core->NC_UNICODE) {
    $new_settings = $nc_core->utf8->array_utf2win($new_settings);
}

if ($new_settings) {
    $nc_core->token->exit_if_invalid();
    $new_settings["EnableQuerySuggest"] = (strlen($new_settings["SuggestMode"]) > 0);
    foreach ($new_settings as $k => $v) {
        nc_search::save_setting($k, $v);
    }
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVED, 'ok');
}

// ПОКАЗАТЬ ФОРМУ
$suggest_mode = nc_search::get_setting('SuggestMode');
$suggest_enabled = nc_search::should('EnableQuerySuggest');

$component = $nc_core->component->get_by_id(nc_search::get_setting('ComponentID'));
$file_mode = $component["File_Mode"];

/** @var Permission $perm */
global $perm;
$can_view_templates = $perm->isSupervisor() || $perm->isGuest();

?>
<form method="POST" class="settings">
    <?= $nc_core->token->get_input() ?>
    <input type="hidden" name="view" value="templates" />

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_RESULTS ?></legend>

        <?=$this->setting_cb('ShowMatchedFragment', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_SHOW_MATCHED_FRAGMENT) ?>
        <?=$this->setting_cb('HighlightMatchedWords', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_HIGHLIGHT_MATCHED) ?>
        <?=$this->setting_cb('AllowFieldSort', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ALLOW_FIELD_SORT) ?>
        <?=$this->setting_cb('OpenLinksInNewWindow', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_OPEN_LINKS_IN_NEW_WINDOW) ?>

        <div class="setting">
            <?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_RESULTS_TITLE_WORD_COUNT ?>:
            <input type='text' name='s[ResultTitleMaxNumberOfWords]' class='i3'
                   value='<?=htmlspecialchars(nc_search::get_setting('ResultTitleMaxNumberOfWords')) ?>' />
        </div>
        <div class="setting">
            <?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_RESULTS_FRAGMENT_WORD_COUNT ?>:
            <input type='text' name='s[ResultContextMaxNumberOfWords]' class='i3'
                   value='<?=htmlspecialchars(nc_search::get_setting('ResultContextMaxNumberOfWords')) ?>' />
        </div>
        <div class="setting">
            <?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_MAX_PREVIEW_TEXT_LENGTH ?>:
            <input type='text' name='s[MaxDocumentPreviewTextLengthInKbytes]' class='i4'
                   value='<?=htmlspecialchars(nc_search::get_setting('MaxDocumentPreviewTextLengthInKbytes')) ?>' />
            <?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_KBYTES ?>
        </div>
    </fieldset>

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH ?></legend>
        <?=$this->setting_cb('EnableAdvancedSearchForm', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ENABLE_ADVANCED_SEARCH_FORM) ?>
        <div class="setting">
            <div class="caption"><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH_FORM_OPTIONS ?>:</div>
            <blockquote>
                <?=$this->setting_cb('ShowAdvancedFormExcludeField', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH_EXCLUDE) ?>
                <?=$this->setting_cb('ShowAdvancedFormFieldSearch', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH_FIELD) ?>
                <?=$this->setting_cb('ShowAdvancedFormTimeIntervals', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_SEARCH_DATETIME) ?>
            </blockquote>
        </div>
    </fieldset>

    <fieldset id="r_suggest">
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST ?></legend>
        <div class="setting">
            <input type="radio" name="s[SuggestMode]" value="queries" id="r_s1"
              <?=($suggest_enabled && $suggest_mode == 'queries' ? " checked" : "") ?>/>
            <label for="r_s1"><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_QUERIES ?></label>
        </div>
        <div class="setting">
            <input type="radio" name="s[SuggestMode]" value="titles" id="r_s2"
              <?=($suggest_enabled && $suggest_mode == 'titles' ? " checked" : "") ?>/>
            <label for="r_s2"><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_TITLES ?></label>
            <blockquote id="r_s2_options">
                <?=$this->setting_cb('SearchTitleBaseformsForSuggestions', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_TITLES_SEARCH_IN_INDEX) ?>
                <?=$this->setting_cb('SearchTitleAsPhraseForSuggestions', NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_TITLES_SEARCH_AS_PHRASE) ?>
            </blockquote>
       </div>
       <div class="setting">
           <input type="radio" name="s[SuggestMode]" value="" id="r_s3"
             <?=(!$suggest_enabled ? " checked" : "") ?>/>
           <label for="r_s3"><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_DISABLED ?></label>
       </div>
       <div class="setting">
           <?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_MINIMUM_LENGTH ?>:
           <input type='text' name='s[SuggestionsMinInputLength]' class='i3'
                  value='<?=htmlspecialchars(nc_search::get_setting('SuggestionsMinInputLength')) ?>' />
       </div>
       <div class="setting">
           <?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_QUERY_SUGGEST_NUMBER_OF_HITS ?>:
           <input type='text' name='s[NumberOfSuggestions]' class='i3'
                  value='<?=htmlspecialchars(nc_search::get_setting('NumberOfSuggestions')) ?>' />
       </div>
    </fieldset>
    <script type="text/javascript">
        // отключение опций для заголовков страниц в выпадающих подсказках при необходимости
        (function() {
            var toggle = function () {
                var enabled = $nc('#r_s2').prop('checked');
                $nc('#r_s2_options input').prop('disabled', !enabled);
            }
            $nc('#r_suggest :radio').click(toggle).change(toggle);
            $nc(toggle);
        })();
    </script>

<?php if ($can_view_templates && $file_mode): ?>

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_FORM_TEMPLATES ?></legend>
        <textarea class="code"
                  name="s[web_SearchFormTemplate]"><?=htmlspecialchars(nc_search::get_setting('web_SearchFormTemplate'))?></textarea>
    </fieldset>

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_FORM_TEMPLATE ?></legend>
        <textarea class="code"
                  name="s[web_AdvancedSearchFormTemplate]"><?=htmlspecialchars(nc_search::get_setting('web_AdvancedSearchFormTemplate')) ?></textarea>
    </fieldset>

    <fieldset>
       <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_FORM_TEMPLATES_MOBILE ?></legend>
       <textarea class="code"
                 name="s[mobile_SearchFormTemplate]"><?=htmlspecialchars(nc_search::get_setting('mobile_SearchFormTemplate')) ?></textarea>
    </fieldset>

    <fieldset>
       <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_FORM_TEMPLATE_MOBILE ?></legend>
       <textarea class="code"
                 name="s[mobile_AdvancedSearchFormTemplate]"><?=htmlspecialchars(nc_search::get_setting('mobile_AdvancedSearchFormTemplate')) ?></textarea>
    </fieldset>

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_FORM_TEMPLATES_RESPONSIVE ?></legend>
        <textarea class="code"
                  name="s[responsive_SearchFormTemplate]"><?=htmlspecialchars(nc_search::get_setting('responsive_SearchFormTemplate')) ?></textarea>
    </fieldset>

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_FORM_TEMPLATE_RESPONSIVE ?></legend>
        <textarea class="code"
                  name="s[responsive_AdvancedSearchFormTemplate]"><?=htmlspecialchars(nc_search::get_setting('responsive_AdvancedSearchFormTemplate')) ?></textarea>
    </fieldset>

<? elseif ($can_view_templates): ?>
    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_FORM_TEMPLATES ?></legend>
        <textarea class="code"
                  name="s[SearchFormTemplate]"><?=htmlspecialchars(nc_search::get_setting('SearchFormTemplate'))?></textarea>
    </fieldset>

    <fieldset>
        <legend><?=NETCAT_MODULE_SEARCH_ADMIN_INTERFACE_ADVANCED_FORM_TEMPLATE ?></legend>
        <textarea class="code"
                  name="s[AdvancedSearchFormTemplate]"><?=htmlspecialchars(nc_search::get_setting('AdvancedSearchFormTemplate')) ?></textarea>
    </fieldset>
<? endif; ?>

</form>
