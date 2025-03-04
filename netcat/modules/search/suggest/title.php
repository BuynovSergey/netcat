<?php

/**
 * Входящие параметры:
 *  - term
 *  - language
 * 
 * @global $catalogue
 */
$NETCAT_FOLDER = realpath("../../../../");
require_once("$NETCAT_FOLDER/vars.inc.php");
require ($INCLUDE_FOLDER."index.php");

// получение параметров
$input = trim($nc_core->input->fetch_get('term'));

if (!nc_search::should('EnableQuerySuggest') ||
    nc_search::get_setting('SuggestMode') != 'titles' ||
    mb_strlen($input) < nc_search::get_setting('SuggestionsMinInputLength')) { die("[]"); }

$input = $nc_core->utf8->conv($nc_core->NC_CHARSET, 'utf-8', $input);
$language = $nc_core->input->fetch_get('language');

if (!$language) {
    $language = $nc_core->lang->detect_lang(1);
}

// поиск подходящих заголовков is provider-dependent
$suggestions = nc_search::get_provider()->suggest_titles($input, $language, $catalogue);
if (!$nc_core->NC_UNICODE) { $suggestions = $nc_core->utf8->array_utf2win($suggestions); }

print nc_array_json($suggestions);
