<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_settings_toolbar();

$rules = nc_search::load('nc_search_rule', "SELECT * FROM `%t%` ORDER BY `Rule_ID`")
         ->set_output_encoding(nc_core('NC_CHARSET'));

if (count($rules)) { ?>

    <form method="POST" action="?view=rules" onsubmit="return ($nc('input:checked').size() > 0)">
        <?= $nc_core->token->get_input() ?>
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="data_class" value="nc_search_rule" />
        <table class="nc-table nc--large nc--hovered nc--striped list">
            <tr align="left">
                <th class='nc-text-center'><?=NETCAT_MODULE_SEARCH_ADMIN_ID ?></th>
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_NAME ?></th>
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_SITE ?></th>
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA ?></th>
                <th><?=NETCAT_MODULE_SEARCH_ADMIN_RULE_SCHEDULE ?></th>
                <th class="nc-text-center"><?=NETCAT_MODULE_SEARCH_ADMIN_ACTIONS ?></th>
                <th class="nc-text-center"><div class='icons icon_delete' title="<?=NETCAT_MODULE_SEARCH_ADMIN_DELETE ?>"></div></th>
            </tr>
            <?php
            foreach ($rules as $r) {
                $id = $r->get_id();

                echo "<tr><td class='nc-text-center'>$id</td>",
                     "<td>", $this->if_null($r->get('name'), NETCAT_MODULE_SEARCH_ADMIN_UNNAMED_RULE), "</td>",
                     "<td>", $r->get_site_name(), "</td>",
                     "<td>", $this->if_null($r->get('area_string'), NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_WHOLE_SITE), "</td>",
                     "<td>", $r->get_schedule_string(), "</td>",
                     "<td class='nc-text-center'>",
                     "<a href='?view=rules_edit&amp;copy=$id'>",
                      "<div class='icons icon_copy' title='".NETCAT_MODULE_SEARCH_ADMIN_COPY."'></div>",
                     "</a><span class='icon_spacer'></span><a href='?view=rules_edit&amp;id=$id'>",
                     "<div class='icons icon_pencil' title='".NETCAT_MODULE_SEARCH_ADMIN_EDIT."'></div>",
                     "</a>",
                     "</td>",
                     "<td class='nc-text-center'><input type='checkbox' name='ids[]' value='$id' /></td>",
                     "</tr>\n";
            }
            ?>
        </table>
    </form>

<?php

    $ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_DELETE_SELECTED);
}
else { // no entries
   nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_EMPTY_LIST, 'info');
}

$ui->actionButtons[] = array("id" => "add",
        "caption" => NETCAT_MODULE_SEARCH_ADMIN_ADD,
        "location" => "#module.search.rules_edit",
        "align" => "left");
