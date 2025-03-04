<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_lists_toolbar();
$nc_core = nc_Core::get_object();
// предупредить, если мы сохранили не то, что ввёл пользователь
$crud_record = $this->get_action_record();
if ($crud_record && !$crud_record->get('dont_filter')) {
    $input = $this->get_input('data');
    $saved_value = (!$nc_core->NC_UNICODE ? $nc_core->utf8->array_utf2win($crud_record->get('words')) : $crud_record->get('words'));
    if ($input['words'] != $saved_value) {
        nc_print_status(
                NETCAT_MODULE_SEARCH_ADMIN_SYNONYM_SAVE_RESULT,
                'info',
                array(join(' ', $saved_value), $this->hash_href("#module.search.synonyms_edit({$crud_record->get_id()})"))
        );
    }
}
// end of "show a notice"


$synonyms = nc_search::load('nc_search_language_synonyms', "SELECT * FROM `%t%` ORDER BY `Language`")
            ->set_output_encoding(nc_core('NC_CHARSET'));

if (count($synonyms)) {

    // фильтр
    $language_options = array("<option value=''>".NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE_ANY_LANGUAGE."</option>");
    foreach ($this->get_language_list() as $code => $lang) {
        if ($synonyms->first('language', $code)) {
            $language_options[] = "<option value='$code'>$lang</option>";
        }
    }

    echo "<div class='live_filter' id='synonym_filter'>",
         "<select id='filter_language'>", join("\n", $language_options), "</select>",
         "<input type='text' id='filter_words'>",
         "<span class='reset'>","<div class='icons icon_delete' title='".NETCAT_MODULE_SEARCH_ADMIN_FILTER_RESET."' style='margin-top:5px'></div>", "</span>",
         "</div>";
?>

    <form method="POST" action="?view=synonyms" onsubmit="return ($nc('input:checked').size() > 0)">
        <?= $nc_core->token->get_input() ?>
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="data_class" value="nc_search_language_synonyms" />
        <table id="synonym_table" class="nc-table nc--striped nc--hovered nc--small" width="100%">
            <tr>
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE ?></th>
                <th width="75%"><?=NETCAT_MODULE_SEARCH_ADMIN_SYNONYMS ?></th>
                <th class="nc-text-center"><?=NETCAT_MODULE_SEARCH_ADMIN_EDIT ?></th>
                <th class="nc-text-center"><i class='nc-icon nc--remove' title="<?=NETCAT_MODULE_SEARCH_ADMIN_DELETE ?>"></i></th>
            </tr>
        <?php
        foreach ($synonyms as $s) {
            $id = $s->get_id();
            echo "<tr>",
                 "<td class='language'>", $s->get('language'), "</td>",
                 "<td class='words'>", join(" ", $s->get('words')), "</td>",
                 "<td class='nc-text-center'><a href='?view=synonyms_edit&amp;id=$id'>",
                 "<i class='nc-icon nc--edit nc--hovered' title='".NETCAT_MODULE_SEARCH_ADMIN_EDIT."'></i>",
                 "</a></td>",
                 "<td class='nc-text-center'><input type='checkbox' name='ids[]' value='$id' style='border:none' /></td>",
                 "</tr>\n";
        }
        ?>
    </table>
</form>

<script type="text/javascript">
    $nc('#synonym_filter').createFilterFor($nc('#synonym_table'));
</script>

<?php

    $ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_DELETE_SELECTED);

} else { // no entries
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_EMPTY_LIST, 'info');
}

$ui->actionButtons[] = array(
        "id" => "add",
        "caption" => NETCAT_MODULE_SEARCH_ADMIN_ADD,
        "location" => "#module.search.synonyms_edit",
        "align" => "left");


