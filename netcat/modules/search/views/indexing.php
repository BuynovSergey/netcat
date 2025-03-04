<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();

if (!nc_search::should('EnableSearch')) {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTING_SEARCH_DISABLED, "error",
            array($this->hash_href("#module.search.generalsettings"), "_top"));
}

$can_edit = nc_search_permission::check('rules_edit');

$rules = nc_search::load('nc_search_rule', "SELECT * FROM `%t%` ORDER BY `Rule_ID`")
         ->set_output_encoding(nc_core('NC_CHARSET'));

if (count($rules)) {

    foreach ($rules as $r) {
        // строчка «последняя индексация»
        $last_start_time = $r->get('last_start_time');
        $last_finish_time = $r->get('last_finish_time');
        if (!$last_start_time) {
            $last_run = NETCAT_MODULE_SEARCH_ADMIN_RULE_NEVER_RUN.".";
        } else {
            $last_run = NETCAT_MODULE_SEARCH_ADMIN_RULE_LAST_RUN.": ".
                    nc_search_util::format_time($last_start_time)." (";

            if ($last_finish_time < $last_start_time) {
                $last_run .= NETCAT_MODULE_SEARCH_ADMIN_RULE_LAST_RUN_NOT_FINISHED;
            } else {
                $last_run .= NETCAT_MODULE_SEARCH_ADMIN_RULE_LAST_RUN_DURATION." ".
                        nc_search_util::format_seconds($last_finish_time - $last_start_time);
            }
            $last_run .= ").";
        }
        // конец формирования строчки «последняя индексация»
        // строчка с результатми последней индексации
        $stats = "";
        if ($last_start_time) {
            $result = $r->get('last_result');
            $stats = "<div class='stats'>".
                    sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_STATISTICS,
                            nc_array_value($result, 'processed'),
                            nc_array_value($result, 'deleted'),
                            nc_array_value($result, 'checked')).
                    ".</div>";
        }
        // конец формирования строчки с результатами
        // «подробнее»
        $details = "<div class='site'><strong>".NETCAT_MODULE_SEARCH_ADMIN_RULE_SITE."</strong>: ".
                   $this->hash_link("#site.map({$r->get('site_id')})", $r->get_site_name()).
                   "</div>";

        if ($r->get('area_string')) { // sic, not get_area_string()
            $description = $r->get_area_description();
            if ($description["included"]) {
                $details .= "<div class='header'><strong>".NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_INCLUDED."</strong>:</div><div class='list'>";
                foreach ($description["included"] as $item) {
                    $details .= "<div class='item'>".NETCAT_MODULE_SEARCH_ADMIN_BULLET." $item</div>\n";
                }
                $details .= "</div>";
            }
            if ($description["excluded"]) {
                $details .= "<div class='header'><strong>".NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_EXCLUDED."</strong>:</div><div class='list'>";
                foreach ($description["excluded"] as $item) {
                    $details .= "<div class='item'>".NETCAT_MODULE_SEARCH_ADMIN_BULLET." $item</div>\n";
                }
                $details .= "</div>";
            }

            $details .= "<div class='schedule'><strong>".NETCAT_MODULE_SEARCH_ADMIN_RULE_SCHEDULE."</strong>: ".
                    $r->get_schedule_string()."</div>";
        }
        // конец формирования «подробнее»

        $rule_id = $r->get_id();

        echo "<fieldset class='search_rule'>",
             "<legend>",
                 ($this->if_null($r->get('name'), NETCAT_MODULE_SEARCH_ADMIN_UNNAMED_RULE)),
                 " <span class='show_details'>[<a class='internal' href='javascript:show_details($rule_id)'>",
                 NETCAT_MODULE_SEARCH_ADMIN_RULE_SHOW_DETAILS,
                 "</a>]</span>",
             "</legend>",
             "<div class='details' id='rule_details_$rule_id'>", $details, "</div>",
             "<div class='last_run'>", $last_run, "</div>",
             $stats,
             "<div class='actions'>",
                 "<button class='index_now' onclick='search_index_now($rule_id)'>", NETCAT_MODULE_SEARCH_ADMIN_RULE_RUN_IN_BROWSER, "</button>",
                 " &nbsp; ",
                 "<a class='ajax' href='javascript:search_schedule($rule_id)'>", NETCAT_MODULE_SEARCH_ADMIN_RULE_RUN_IN_BACKGROUND, "</a>",
                 " &nbsp; &nbsp; ",
                 ($can_edit ? $this->hash_link("#module.search.rules_edit($rule_id)", NETCAT_MODULE_SEARCH_ADMIN_RULE_EDIT_LINK) : ''),
             "</div>",
             "</fieldset>";
    }
} else {
    nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_NO_RULES, 'info', array($this->hash_href("#module.search.rules_edit")));
}

if ($can_edit) {
    $ui->actionButtons[] = array("id" => "add",
        "caption" => NETCAT_MODULE_SEARCH_ADMIN_ADD_RULE,
        "location" => "#module.search.rules_edit",
        "align" => "left",
    );
}
?>
<script type="text/javascript">
    function show_details(id) { $nc('#rule_details_' + id).toggle(400); }

    search_msg = {
        rule_queue_loading: '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_RULE_QUEUE_LOADING) ?>',
        rule_queued: '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_RULE_QUEUED) ?>',
        rule_queue_error: '<?=htmlspecialchars(NETCAT_MODULE_SEARCH_ADMIN_RULE_QUEUE_ERROR) ?>'
    }
</script>