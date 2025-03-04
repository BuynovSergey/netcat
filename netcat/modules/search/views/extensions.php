<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_settings_toolbar();

$extensions = nc_search::load('nc_search_extension_rule',
                    "SELECT *
                       FROM `%t%`
                      WHERE `ExtensionInterface` != 'nc_search_provider'
                      ORDER BY `ExtensionInterface`, `Priority`, `Language`"
                )->set_output_encoding(nc_core('NC_CHARSET'));

if (count($extensions)) {

    $show_provider_column = (sizeof(array_unique($extensions->each('get', 'search_provider'))) > 1);
    $show_action_column = (sizeof(array_unique($extensions->each('get', 'action'))) > 1);

?>

    <form method="POST" action="?view=extensions_confirm_delete" onsubmit="return ($nc('input:checked').size() > 0)">
        <?= $nc_core->token->get_input() ?>
        <table class="nc-table nc--large nc--hovered nc--striped list">
            <tr align="left">
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_INTERFACE ?></th>
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_CLASS ?></th>
        <?=($show_provider_column ? "<th>".NETCAT_MODULE_SEARCH_ADMIN_SEARCH_PROVIDER."</th>" : "") ?>
        <?=($show_action_column ? "<th>".NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION."</th>" : "") ?>
            <th><?=NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE ?></th>
            <!-- <th><?=NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_CONTENT_TYPE ?></th> -->
            <th class="nc-text-center"><?=NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_PRIORITY ?></th>
            <th class="nc-text-center"><?=NETCAT_MODULE_SEARCH_ADMIN_EDIT ?></th>
            <th class="nc-text-center"><div class='icons icon_delete' title="<?=NETCAT_MODULE_SEARCH_ADMIN_DELETE ?>"></div></th>
        </tr>
        <?php
        $actions = array(
            '' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION_ANY,
            'searching' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION_SEARCHING,
            'indexing' => NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_ACTION_INDEXING,
        );

        foreach ($extensions as $e) {
            $id = $e->get_id();
            echo "<tr", ($e->get('enabled') ? "" : " class='disabled'"), ">",
                    "<td>{$e->get('extension_interface')}</td>",
                    "<td>{$e->get('extension_class')}</td>",
                    ($show_provider_column ? "<td>".$this->if_null($e->get('search_provider'), NETCAT_MODULE_SEARCH_ADMIN_SEARCH_PROVIDER_ANY)."</td>" : ""),
                    ($show_action_column ? "<td>{$actions[$e->get('action')]}</td>" : ""),
                    "<td>", $this->if_null($e->get('language'), NETCAT_MODULE_SEARCH_ADMIN_LANGUAGE_ANY), "</td>",
                    //"<td>", $this->if_null($e->get('content_type'), NETCAT_MODULE_SEARCH_ADMIN_EXTENSION_CONTENT_TYPE_ANY), "</td>",
                    "<td class='nc-text-center'>{$e->get('priority')}</td>",
                    "<td class='nc-text-center'><a href='?view=extensions_edit&amp;id=$id'>", "<div class='icons icon_pencil' title='".NETCAT_MODULE_SEARCH_ADMIN_EDIT."'></div>", "</a></td>",
                    "<td class='nc-text-center'><input type='checkbox' name='ids[]' value='$id' /></td>",
                "</tr>\n";
        }
        ?>
    </table>
</form>
<?php

    $ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_DELETE_SELECTED);
} else { // no extensions
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_EXTENSIONS_EMPTY_LIST, 'error');
}

$ui->actionButtons[] = array("id" => "add",
        "caption" => NETCAT_MODULE_SEARCH_ADMIN_ADD,
        "location" => "#module.search.extensions_edit",
        "align" => "left");

