<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_lists_toolbar();

$stopwords = nc_search::load('nc_search_language_stopword', "SELECT * FROM `%t%` ORDER BY `Language`, `Word`")
             ->set_output_encoding(nc_core('NC_CHARSET'));

if (count($stopwords)) {

    // фильтр
    $language_options = array("<option value=''>".NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE_ANY_LANGUAGE."</option>");
    foreach ($this->get_language_list() as $code => $lang) {
        if ($stopwords->first('language', $code)) {
            $language_options[] = "<option value='$code'>$lang</option>";
        }
    }

    echo "<div class='live_filter' id='stopword_filter'>",
         "<select id='filter_language'>", join("\n", $language_options), "</select>",
         "<input type='text' id='filter_word'>",
         "<span class='reset'>", "<div class='icons icon_delete' title='".NETCAT_MODULE_SEARCH_ADMIN_FILTER_RESET."' style='margin-top:5px'></div>", "</span>",
         "</div>";
?>

    <form method="POST" action="?view=stopwords" onsubmit="return ($nc('input:checked').size() > 0)">
        <?= $nc_core->token->get_input() ?>
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="data_class" value="nc_search_language_stopword" />
        <table id="stopword_table" class="nc-table nc--striped nc--hovered nc--small" width="100%">
            <tr align="left">
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE ?></th>
                <th width="75%"><?=NETCAT_MODULE_SEARCH_ADMIN_STOPWORD ?></th>
                <th class="nc-text-center"><?=NETCAT_MODULE_SEARCH_ADMIN_EDIT ?></th>
                <th class="nc-text-center"><i class='nc-icon nc--remove' title="<?=NETCAT_MODULE_SEARCH_ADMIN_DELETE ?>"></i></th>
            </tr>

        <?php
        foreach ($stopwords as $s) {
            $id = $s->get_id();
            echo "<tr>",
            "<td class='language'>", $s->get('language'), "</td>",
            "<td class='word'>", $s->get('word'), "</td>",
            "<td class='nc-text-center'><a href='?view=stopwords_edit&amp;id=$id'>",
            "<i class='nc-icon nc--edit nc--hovered' title='".NETCAT_MODULE_SEARCH_ADMIN_EDIT."'></i>",
            "</a></td>",
            "<td class='nc-text-center'><input type='checkbox' name='ids[]' value='$id' /></td>",
            "</tr>\n";
        }
        ?>

    </table>
</form>

<script type="text/javascript">
    $nc('#stopword_filter').createFilterFor($nc('#stopword_table'));
</script>

<?php
    $ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_DELETE_SELECTED);

} else { // no entries
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_EMPTY_LIST, 'info');
}

$ui->actionButtons[] = array("id" => "add",
        "caption" => NETCAT_MODULE_SEARCH_ADMIN_ADD,
        "location" => "#module.search.stopwords_edit",
        "align" => "left");

