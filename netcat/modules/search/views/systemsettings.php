<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_settings_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SAVE);

$input = $this->get_input('settings');
if ($input) {
    $nc_core->token->exit_if_invalid();
    $search_provider_changed = false;
    foreach ($input as $k => $v) {
        if ($k == 'SearchProvider' && nc_search::get_setting('SearchProvider') != $v) {
            $search_provider_changed = true;
        }
        nc_search::save_setting($k, $v);
    }

    // check SearchProvider
    if ($search_provider_changed) {
        $new_provider = $input['SearchProvider'];
        if (@class_exists($new_provider)) {
            try {
                $provider = new $new_provider;
                if ($provider instanceof nc_search_provider) {
                    $provider->first_run();
                }
                else {
                    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_INCORRECT_PROVIDER_CLASS, 'error', array($new_provider));
                }
            }
            catch (Exception $e) {
                nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_PROVIDER_CLASS_INITIALIZATION_ERROR, 'error', array($new_provider, $e->getMessage()));
            }
        }
        else {
            nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_PROVIDER_CLASS_NOT_FOUND, 'error', array($new_provider));
        }
    }

    // done saving
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVED, 'ok');
}

$settings = array(
        'ComponentID',
        'SearchProvider',
        'IndexerSecretKey',
        'IndexerNormalizeLinks',
        'IndexerSaveTaskEveryNthCycle',
        'IndexerRemoveIdleTasksAfter',
        'IndexerTimeThreshold',
        'IndexerMemoryThreshold',
        'IndexerConsoleMemoryThreshold',
        'IndexerConsoleTimeThreshold',
        'IndexerConsoleDocumentsPerSession',
        'IndexerConsoleSlowdownDelay',
        'IndexerConsoleRestartHungTasks',
        'IndexerInBrowserSlowdownDelay',
        'MinScheduleInterval',
        'CrawlerMaxRedirects',
        'NumberOfEntriesPerSitemap',
        'MaxTermsPerQuery',
        'MaxTermsPerField',
        'ZendSearchLucene_MaxBufferedDocs',
        'ZendSearchLucene_MaxMergeDocs',
        'ZendSearchLucene_MergeFactor',
        'PhpMorphy_LoadDictsDuringIndexing',
        'DatabaseIndex_LoadAllCodesForIndexing',
        'DatabaseIndex_MaxSimilarityCandidates',
        'DatabaseIndex_MaxRewriteTerms',
        'DatabaseIndex_UseUtf8Levenshtein',
        'DatabaseIndex_MaxProximityTerms',
        'DatabaseIndex_MaxProximityDistance',
        'DatabaseIndex_AlwaysGetTotalCount',
        'DatabaseIndex_OptimizationFrequency',
);

$form_description = array();
foreach ($settings as $s) {
    $form_description[$s] = array(
        'type' => 'string',
        'caption' => $s,
        'value' => nc_search::get_setting($s)
    );
}


$form = new nc_a2f($form_description, "settings");
echo "<form class='settings system_settings' method='POST'>",
     $nc_core->token->get_input(),
     "<input type='hidden' name='view' value='systemsettings' />",
     $form->render("<div>", "", "</div>", ""),
     "</form>";
