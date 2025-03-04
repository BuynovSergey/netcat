<?php if (!class_exists('nc_core')) { die; } ?>
<?php if (!empty($filter) || !empty($data)) { ?>
<div class="nc-bg-lighten nc-padding-10 " style="border-bottom: 1px solid #DDD;">
    <a href="#" class="nc-security-log-filter-form-toggle"><?= NETCAT_SECURITY_LOG_FILTER ?></a>

    <form method="get"
          class="nc-form nc--horizontal  nc-security-log-filter-form"
          style="margin-top: 10px; <?php if (empty($filter)): ?> display:none <?php else: ?> display:block <?php endif; ?>">

        <input type="hidden" name="ctrl" value="security_filter_admin_log">
        <input type="hidden" name="action" value="show_log">

        <div class="nc-security-log-filter-fields">

            <div class="nc-security-log-filter-block">
                <div style="display: inline-block">
                    <label><?= NETCAT_SECURITY_INCIDENT_DATE_FILTER ?> <?= NETCAT_SECURITY_INCIDENT_DATE_FILTER_FROM ?></label>
                    <input style="bottom: 5px; position: relative"  type="date" name="filter[Date_From]" value="<?= isset($filter["Date_From"]) ? htmlspecialchars($filter["Date_From"]) : ''; ?>">
                </div>
                <div style="display: inline-block">
                    <label><?= NETCAT_SECURITY_INCIDENT_DATE_FILTER_TO ?></label>
                    <input style="bottom: 5px; position: relative"  type="date" name="filter[Date_To]" value="<?= isset($filter["Date_To"]) ? htmlspecialchars($filter["Date_To"]) : ''; ?>">
                </div>
            </div>
            <div class="nc-security-log-filter-block" style="min-width: 50px; flex-grow: 1">
                <label><?= NETCAT_SECURITY_FILTER_TYPE ?></label>
                <select name="filter[FilterType]" class="nc--wide">
                       <option value="">---</option>
                       <option value="sql" <?= isset($filter["FilterType"]) && $filter["FilterType"] == 'sql'? 'selected' : ''; ?>>sql</option>
                       <option value="php" <?= isset($filter["FilterType"]) && $filter["FilterType"] == 'php'? 'selected' : ''; ?>>php</option>
                       <option value="xss" <?= isset($filter["FilterType"]) && $filter["FilterType"] == 'xss'? 'selected' : ''; ?>>xss</option>
                </select>
            </div>

            <div class="nc-security-log-filter-block" style="min-width: 50px; flex-grow: 1">
                <label><?= NETCAT_SECURITY_FILTER_URL ?></label>
                <input type="text" name="filter[URL]" class="nc--wide"
                       value="<?= isset($filter["URL"]) ? htmlspecialchars($filter["URL"]) : ''; ?>">
            </div>

            <div class="nc-security-log-filter-block" style="min-width: 50px; flex-grow: 1">
                <label><?= NETCAT_SECURITY_FILTER_REFERER ?></label>
                <input type="text" name="filter[Referer]" class="nc--wide"
                       value="<?= isset($filter["Referer"]) ? htmlspecialchars($filter["Referer"]) : ''; ?>">
            </div>

            <div class="nc-security-log-filter-block" style="min-width: 50px; flex-grow: 1">
                <label><?= NETCAT_SECURITY_FILTER_IP ?></label>
                <input type="text" name="filter[IP]" class="nc--wide"
                       value="<?= isset($filter["IP"]) ? htmlspecialchars($filter["IP"]) : ''; ?>">
            </div>

            <div class="nc-security-log-filter-block" style="min-width: 50px; flex-grow: 1">
                <label><?= NETCAT_SECURITY_FILTER_HASH ?></label>
                <input type="text" name="filter[Hash]" class="nc--wide"
                       value="<?= isset($filter["Hash"]) ? htmlspecialchars($filter["Hash"]) : ''; ?>">
            </div>
            <div class="nc-security-log-filter-block">
                <label for="group"><?= NETCAT_SECURITY_FILTER_GROUP_BY_SOURCE ?></label>
                <input id="group" name="filter[group]" <?= ( isset($filter['group']) && $filter['group'] == 'on') ? 'checked':'';?> type="checkbox">
            </div>
        </div>
        <div class="nc-security-log-filter-block" style="margin-top:10px">
            <button type="submit"><?= NETCAT_SECURITY_INCIDENT_FILTER_SUBMIT ?></button>
            <button type="button" id="nc_security_log_form_reset"><?= NETCAT_SECURITY_INCIDENT_FILTER_RESET ?></button>
        </div>
        <script>
            $nc(function () {
                $nc('#nc_security_log_form_reset').click(function() {
                    if (confirm('<?= NETCAT_SECURITY_INCIDENT_FILTER_RESET_CONFIRM ?>')) {
                        $nc(this).closest('FORM').find('INPUT[type=text]').val('');
                        $nc(this).closest('FORM').find('INPUT[type=date]').val('');
                        $nc(this).closest('FORM').find('INPUT[type=checkbox]').val('');
                        $nc(this).closest('FORM').find('SELECT').val(-1);
                    }
                    $('.nc-security-log-filter-form').submit();
                    return true;
                });

                $nc('A.nc-security-log-filter-form-toggle').click(function () {
                    var $this = $nc(this);
                    var $form = $this.next('FORM');
                    $form.slideToggle();
                    return false;
                });
            });
        </script>
    </form>
</div>
<?php
}

/** @var nc_ui $ui */

// COMMON
/** @var int $site_id */
/** @var string $default_settings_link */
/** @var array $data */

if (!empty($data) && empty($filter['group'])) {

    echo "<table class='nc-table nc-security-log-table' width='100%'>
        <tr>
            <th nowrap>".NETCAT_SECURITY_FILTER_ID."</th>
            <th nowrap>".NETCAT_SECURITY_FILTER_TIME."</th>
            <th nowrap>".NETCAT_SECURITY_FILTER_TYPE."</th>
            <th nowrap>".NETCAT_SECURITY_FILTER_URL."</th>
            <th nowrap>".NETCAT_SECURITY_FILTER_REFERER."</th>
            <th nowrap>".NETCAT_SECURITY_FILTER_IP."</th>
        </tr>";

    foreach ($data as $row) {
        $link = $nc_core->ADMIN_PATH ."#security.filter.log.record($row[Security_FilterLog_ID])";
        echo "<tr>";
        echo "<td><a href='$link' target='_top'>" . $row['Security_FilterLog_ID'] . "</a></td>";
        echo "<td><a href='$link' target='_top'>" . date("d.m.Y H:i:s", strtotime($row['Timestamp'])) . "</a></td>";
        echo "<td>" . $row['FilterType'] . "</td>";
        echo "<td>" . htmlspecialchars($row['URL']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Referer']) . "</td>";
        echo "<td>" . $row['IP'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
elseif (!empty($data) && !empty($filter['group'])) {
    ?>
    <table class='nc-table' width='100%'>
        <tr>
            <th nowrap><?= NETCAT_SECURITY_FILTER_LAST_DATE; ?></th>
            <th nowrap><?= NETCAT_SECURITY_FILTER_TYPE; ?></th>
            <th nowrap><?= NETCAT_SECURITY_FILTER_SOURCE; ?></th>
            <th nowrap><?= NETCAT_SECURITY_FILTER_STRING; ?></th>
            <th nowrap><?php if (isset($hash_from_group)): ?><?= NETCAT_SECURITY_FILTER_ID; ?><?php else: ?><?= NETCAT_SECURITY_FILTER_INCIDENT_COUNT; ?><?php endif; ?></th>
        </tr>
<?php
    foreach ($data as $row) {
        if (isset($hash_from_group)) {
            echo "<tr class=\"nc-security-log-table-group\">";
        }
        else {
            echo "<tr class=\"nc-security-log-table-group\" style=\"cursor:pointer\" 
                onclick=\"javascript:top.urlDispatcher.process('#security.filter.log.group($row[Hash])'); return false\">";
        }
        if (isset($hash_from_group)) {
            echo "<td><a href='$nc_core->ADMIN_PATH#security.filter.log.record($row[Security_FilterLog_ID])'>"
                .date("d.m.Y H:i:s", strtotime($row['LastDate']))."</a></td>";
        }
        else {
            echo "<td><a>".date("d.m.Y H:i:s", strtotime($row['LastDate']))."</a></td>";
        }
        echo "<td>".$row['FilterType']."</td>";
        echo "<td>".$row['ValueSource']."</td>";
        echo "<td style='max-width:800px'><p class='nc-security-log-table-text'>". htmlspecialchars($row['CheckedString'])."</p></td>";
        if (isset($hash_from_group)) {
            echo "<td>".$row['Security_FilterLog_ID']."</td>";
        }
        else {
            echo "<td>".$row['CountElements']."</td>";
        }
        echo "</tr>";
    }
    echo "</table>";    
}
else {
    echo NETCAT_SECURITY_LOG_NO_DATA;
}
?>
<?php
/*пагинация*/
if ($rec_count > $rec_on_page) {
    $col = round($rec_count / $rec_on_page);
    $k = 0;
    if (isset($_GET['filter'])) {
        $filter_array = array_filter($_GET['filter']);
    }
    $filter_str = '';
    if (!empty($filter_array)) {
        foreach($filter_array as $key => $value) {
            $value = htmlspecialchars($value);
            $filter_str .= "&$key=$value";
        }
    }
    ?>
    <div style="text-align:center">
    <?php
    for ($i = 0; $i < $col; $i++ ) {
        ?>x
        <span><a href='?ctrl=security_filter_admin_log&action=show_log<?= $filter_str; ?>&offset=<?= $k; ?>'><?= $i + 1; ?></a>
            <?php if ($i + 1 < $col): ?>|<?php endif;?>
        </span>
        <?php
        $k += $rec_on_page;
    }
    ?>
    </div>
    <?php
}

?> 
<?php if (!empty($data)) { ?>
    <div class="nc-bg-lighten nc-padding-10 " style="border-bottom: 1px solid #DDD;">
        <a href="#" class="nc-security-log-clear-form-toggle"><?= NETCAT_SECURITY_LOG_CLEAR_LABEL ?></a>  
        <form method="get"
              class="nc-form nc--horizontal  nc-security-log-clear-form"
              style="margin-top: 10px; <?php if (empty($clear)): ?> display:none <?php else: ?> display:block <?php endif; ?>">
    
            <input type="hidden" name="ctrl" value="security_filter_admin_log">
            <input type="hidden" name="action" value="clear_log">
    
            <div class="nc-security-log-clear-fields">   
                    <div style="display: inline-block">
                        <label><?= NETCAT_SECURITY_INCIDENT_FILTER_FROM ?></label>
                        <input type="date" name="clear[Date_From]" value="<?= isset($clear["Date_From"]) ? htmlspecialchars($clear["Date_From"]) : ''; ?>">
                    </div>
                    <div style="display: inline-block">
                        <label><?= NETCAT_SECURITY_INCIDENT_FILTER_TO ?></label>
                        <input type="date" name="clear[Date_To]" value="<?= isset($clear["Date_To"]) ? htmlspecialchars($clear["Date_To"]) : ''; ?>">
                    </div>
                    <div style="display: inline-block">
                        <button type="submit"><?= NETCAT_SECURITY_LOG_CLEAR ?></button>
                    </div>
            </div>
            <script>
                $nc(function () {    
                    $nc('A.nc-security-log-clear-form-toggle').click(function () {
                        var $this = $nc(this);
                        var $form = $this.next('FORM');
                        $form.slideToggle();
                        return false;
                    });
                });
            </script>
        </form>
    </div>
    <?php } ?>