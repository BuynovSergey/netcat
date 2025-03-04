<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_lists_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_SAVE);
$ui->add_back_button();

$synonyms = $this->data_form('nc_search_language_synonyms', 'synonyms');


$form_description = array(
        'language' => array(
                'type' => 'select',
                'subtype' => 'static',
                'caption' => NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE.':',
                'values' => $this->get_language_list(),
                'default_value' => nc_Core::get_object()->lang->detect_lang(true)
        ),
);
$form = new nc_a2f($form_description, "data");
$form->set_value($synonyms);


echo "<fieldset><legend>", NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS, "</legend>",
     $form->render("<div>", "", "</div>", ""),
     "<div class='ncf_row'><div class='ncf_caption'>", NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS_FIELD_CAPTION, ":</div>",
     "<div id='synonym_list'></div>",
     "<div id='add_synonym_row'><span>",
     nc_admin_img("i_obj_add.gif", NETCAT_MODULE_SEARCH_ADMIN_ADD), NETCAT_MODULE_SEARCH_ADMIN_ADD,
     "</span></div>",
     "<div id='synonym_filter_row'><input type='checkbox' name='data[dont_filter]' id='dont_filter' /> ",
     "<label for='dont_filter'>", NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS_DO_NOT_APPLY_FILTERS, "</label> ",
     "<span class='inline_help_mark' id='filters_help'>[ ? ]</span>",
     "<div class='inline_help' id='filters_help_hover'>",
     NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS_DO_NOT_APPLY_FILTERS_HELP,
     "</div>",
     "</div>",
     "</fieldset>";
?>
<script type="text/javascript">
    (function($, words) {
        var tpl = $("<div class='word'><input type='text' name='data[words][]' />" +
            "<span class='delete_word'>" +
            "<?=nc_admin_img("delete", NETCAT_MODULE_SEARCH_ADMIN_DELETE) ?>" +
            "</span>" +
            "</div>");

        var add_word = function(word) {
            var row = tpl.clone();
            row.find("input").val(word || '');
            row.find(".delete_word").click(function() {
                $(this).parent().remove();
                if (!$('#synonym_list .word').size()) { add_word(); }
            });
            $('#synonym_list').append(row);
        }

        ///// init: event handlers
        $('#add_synonym_row > span').click(function() { add_word(); });
        /* положение всплывающего слоя не меняется при смещении значка [?];
           можно рассматривать это как баг, но результат неплохой при списках
           небольшого — до 10 слов — размера */
        var filter_mark = $('#filters_help'),
        filter_hover = $('#filters_help_hover').offset(filter_mark.offset());
        filter_mark.hover(function() { filter_hover.show(); },
        function() { filter_hover.hide(); });

        ///// init: words
        words[''] = ''; // empty line
        for (var w in words) { add_word(words[w]); }

    })($nc, <?=nc_array_json($synonyms->get('words'))
?>);

</script>
