<?php
if (!class_exists("nc_system")) {
    die;
}

/**
 *  input:
 *    — purge — выполнить очистку журнала
 *    — s[] — выбранные типы событий, подлежащие фиксированию
 *    — DaysToKeepEventLog — соответствующая настройка модуля
 *    — offset — при листании страниц
 */
$per_page = 100; // событий на странице
// -----------------------------------------------------------------------------
$ui = $this->get_ui();
$ui->add_lists_toolbar();

$db = $this->get_db();

$can_change_settings = nc_search_permission::check('settings');

if ($can_change_settings) {
    $new_settings = $this->get_input("s");
    if ($new_settings) {
        $nc_core->token->exit_if_invalid();
        $level = 0;
        $level_codes = array_flip(nc_search::get_log_types());
        foreach ($new_settings as $type => $enabled) {
            if ($enabled) {
                $level |= $level_codes[$type];
            }
        }
        nc_search::save_setting('LogLevel', $level);
        nc_search::save_setting('DaysToKeepEventLog', $this->get_input('DaysToKeepEventLog'));
        nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_SETTINGS_SAVED, 'ok');
    } elseif ($this->get_input("purge")) {
        $db->query("TRUNCATE TABLE `Search_Log`");
        nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_DELETED, 'ok');
    }
}
?>

<?php if ($can_change_settings): ?>
<div class="log_settings settings">
    <div id="settings_hidden"><a href="#"><?=NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_SHOW_SETTINGS ?></a></div>
    <div id="settings_visible">
        <fieldset>
            <legend><?=NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_SETTINGS ?></legend>
            <form action="?view=events" method="POST">
                <?php
                echo $nc_core->token->get_input();
                echo "<div class='setting'>",
                sprintf(NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_DELETE_PERIOD,
                        "<input type='text' name='DaysToKeepEventLog' class='i4' value='".
                        (int) nc_search::get_setting('DaysToKeepEventLog')."'>"),
                "</div>";

                echo NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_LEVEL, ":<blockquote>";
                $log_level = nc_search::get_setting('LogLevel');
                foreach (nc_search::get_log_types() as $level => $string) {
                    echo $this->setting_cb($string, constant("NETCAT_MODULE_SEARCH_ADMIN_EVENT_$string"), $log_level & $level);
                }
                echo "</blockquote>";
                ?>
                <div class="submit_row">
                    <input type="submit" title="<?=NETCAT_MODULE_SEARCH_ADMIN_SAVE ?>" value="<?=NETCAT_MODULE_SEARCH_ADMIN_SAVE ?>" />
                    &nbsp;
                    <a href="?view=events&amp;purge=1"><?=NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_DELETE_ALL
                ?></a>
                </div>
            </form>
        </fieldset>
    </div>
</div>
<script type="text/javascript">
    (function() {
        var off = $nc('#settings_hidden'),
            on = $nc('#settings_visible'),
            toggle = function() { off.toggle(); on.toggle(); };

        off.click(toggle);
        on.find("legend").click(toggle);
    })();
</script>
<?php endif; ?>

<!-- END OF SETTINGS / INFO SECTION -->
<?php
    $num_rows = $db->get_var("SELECT COUNT(*) FROM `Search_Log`");
    if ($num_rows) {
        $offset = $this->get_input('offset', false);
        if ($offset === false) { // показать с конца
            $offset = ceil($num_rows / $per_page) * $per_page - $per_page;
        } else {
            $offset = (int) $offset;
        }

        $entries = $db->get_results("SELECT * FROM `Search_Log` ORDER BY `Timestamp` ASC LIMIT $per_page OFFSET $offset", ARRAY_A);

        echo "<table class='nc-table nc--large nc--hovered nc--striped list log_entries'>\n",
        "<tr>",
            "<th>", NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_TIME, "</th>",
            "<th>", NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_TYPE, "</th>",
            "<th width='75%'>", NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_MESSAGE, "</th>",
        "</tr>\n";

        foreach ($entries as $row) {
            echo "<tr><td class='nc--nowrap'>", $row['Timestamp'], "</td><td>", $row['Type'],
            "</td><td align='left'>", nl2br($row['Message']), "</td></tr>\n";
        }

        echo "</table>",
        $this->result_count($offset + 1, $per_page, $num_rows);

        // листалка по страницам
        $page_link = $this->make_page_query(array('offset'), true);
        if ($offset > 0) {
            $prev_page = $page_link."&amp;offset=".($offset - $per_page);
            $ui->actionButtons[] = array("id" => "prev_page",
                    "caption" => NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_PREV_PAGE,
                    "action" => "mainView.loadIframe('$prev_page')",
                    "align" => "left");
        }
        if ($num_rows > $offset + $per_page) {
            $next_page = $page_link."&amp;offset=".($offset + $per_page);
            $ui->actionButtons[] = array("id" => "next_page",
                    "caption" => NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_NEXT_PAGE,
                    "action" => "mainView.loadIframe('$next_page')");
        }
    } else {
        nc_print_status(NETCAT_MODULE_SEARCH_ADMIN_EVENT_LOG_EMPTY, 'info');
    }