<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_settings_toolbar();
$ui->add_back_button();

$extension = $this->data_form('nc_search_extension_rule', 'extensions');

$registered_providers = $this->get_db()->get_col(
                "SELECT `ExtensionClass` FROM `Search_Extension`".
                " WHERE `ExtensionInterface` = 'nc_search_provider'");

$search_providers = array('' => NETCAT_MODULE_SEARCH_ADMIN_SEARCH_PROVIDER_ANY);
foreach ($registered_providers as $p) {
    $search_providers[$p] = $p;
}

$form_description = array(
        'extension_interface' => array(
                'type' => 'select',
                'subtype' => 'static',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_INTERFACE.':',
                'values' => array(
                        'nc_search_language_analyzer' => 'nc_search_language_analyzer',
                        'nc_search_language_corrector' => 'nc_search_language_corrector',
                        'nc_search_language_filter' => 'nc_search_language_filter',
                        'nc_search_document_parser' => 'nc_search_document_parser',
                ),
                'default_value' => 'nc_search_language_filter'
        ),
        'extension_class' => array(
                'type' => 'string',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_CLASS.':'
        ),
        'search_provider' => array(
                'type' => 'select',
                'values' => $search_providers,
//          'subtype' => 'sql',
//          'sqlquery' => "SELECT `ExtensionClass` as `id`, `ExtensionClass` as `name`".
//                        "  FROM `Search_Extensions`" .
//                        " WHERE `ExtensionInterface` = 'nc_search_provider'",
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_SEARCH_PROVIDER.':',
                'default_value' => ' '
        //'default_value' => nc_search::get_setting("SearchProvider")
        ),
        'action' => array(
                'type' => 'select',
                'subtype' => 'static',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION.':',
                'values' => array(
                        '' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION_ANY,
                        'searching' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION_SEARCHING,
                        'indexing' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION_INDEXING
                ),
                'default_value' => ' '
        ),
        'language' => array(
                'type' => 'select',
                'subtype' => 'static',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE.':',
                'values' => $this->get_language_list(true),
                'default_value' => ' '
        ),
        'content_type' => array(
                'type' => 'string',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_CONTENT_TYPE.':'
        ),
        'priority' => array(
                'type' => 'int',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_PRIORITY.':',
                'min' => 0,
                'max' => 255,
                'default' => 127
        ),
);

$form = new nc_a2f($form_description, "data");
$form->set_value($extension);

echo "<fieldset><legend>", NETCAT_MODULE_SEARCH_ADMIN_EXTENSION, "</legend>",
     $form->render("<div>", "", "</div>", ""),
     "<input type='hidden' name='data[enabled]' value='0' />\n",
     "<div class='extension_cb_row'><input type='checkbox' name='data[enabled]' value='1'",
     ($extension->get('enabled') ? " checked='checked'" : ""),
     " id='cb_enabled' /> <label for='cb_enabled'>",
     NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ENABLED, "</label></div>\n",
     "</fieldset>";

$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SAVE);