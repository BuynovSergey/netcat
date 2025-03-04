<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_lists_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SAVE);
$ui->add_back_button();

$stopword = $this->data_form('nc_search_language_stopword', 'stopwords_save', false);

$form_description = array(
        'language' => array(
                'type' => 'select',
                'subtype' => 'static',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE.':',
                'values' => $this->get_language_list(),
                'default_value' => nc_Core::get_object()->lang->detect_lang(true)
        ),
        'word' => array(
                'type' => 'string',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_STOPWORD_FIELD_CAPTION.':',
        ),
);
$form = new nc_a2f($form_description, "data");
$form->set_value($stopword);

echo "<fieldset><legend>", NETCAT_MODULE_SEARCH_ADMIN_STOPWORD, "</legend>",
     $form->render("<div>", "", "</div>", ""),
     "</fieldset>";
?>
<script type="text/javascript">
    // :(
    $nc('form').attr('onsubmit', "return ($nc(this).find(\"input[name='data[word]']\").val().length > 0)");
</script>
