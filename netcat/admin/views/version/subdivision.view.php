<?php
if (!class_exists('nc_core')) {
    die;
}

$nc_core = nc_core::get_object();

if (!empty($restored)) {
    nc_print_status(constant('NETCAT_VERSION_' . nc_strtoupper($entity) . '_RESTORED'), 'ok');
}
if (!empty($error)) {
    nc_print_status(constant('NETCAT_VERSION_' . nc_strtoupper($entity) . '_RESTORE_ERROR_PARENT'), 'error');
}

$table = $ui->table()->wide();
$table->thead()
    ->th(NETCAT_VERSION_TABLE_TIMESTAMP)
    ->th(NETCAT_VERSION_TABLE_USER)
    ->th($entity_name)
    ->th(NETCAT_VERSION_TABLE_CHANGES)
    ->th('');

/** @var nc_version_record $version */
foreach ($versions as $version) {
    $url_params = array(
        'ctrl' => 'admin.subdivision',
        'action' => 'restore',
        'subdivision_id' => $version->get('Subdivision_ID'),
        'entity' => $version->get('Entity'),
        'id' => $version->get_id(),
    );

    $datetime = date("d.m.Y  H:i:s", strtotime($version->get('Timestamp')));

    $user_id = $version->get('User_ID');
    $user_name = $nc_core->user->get_by_id($user_id, $nc_core->AUTHORIZE_BY);
    $user = $ui->helper->hash_link("#user.edit($user_id)", "$user_id. $user_name", '', '_blank');

    $snapshot_record_name = $version->get_name_from_snapshot();
    $record_name = $version->get_entity_id() . ($snapshot_record_name ? ". $snapshot_record_name" : '');

    $changes = $version->get_changes();
    $action = $version->get('Action');
    if ($action === nc_version_record::UPDATED) {
        $changes_string = join(', ', array_keys($changes));
    } else {
        $action_constant = 'NETCAT_VERSION_ACTION_' . strtoupper($action);
        $changes_string = defined($action_constant)  ? constant($action_constant) : '';
    }
    if ($changes_string) {
        $changes_string =
            "<a href='#' class='nc-version-details' data-version-id='{$version->get_id()}' title='" .
            htmlspecialchars(NETCAT_VERSION_TABLE_SHOW_CHANGES, ENT_QUOTES) .
            "'>$changes_string</a>";
    } else {
        $changes_string = NETCAT_VERSION_TABLE_NO_CHANGES;
    }

    if ($action !== nc_version_record::INITIAL) {
        $changeset = $nc_core->db->get_row("SELECT * FROM `Version_Changeset` WHERE `Version_Changeset_ID` = " . (int)$version->get('Version_Changeset_ID'), ARRAY_A);
        if ($changeset['ChangeCount'] > 1 && $changeset['Description']) {
            $changes_string .= ' &mdash; ' . $changeset['Description'];
        }
    }

    $row = $table->add_row()
        ->td($datetime)
        ->td($user)
        ->td($record_name)
        ->td($changes_string);

    if (!$version->get('IsActual')) {
        $row->td()->href($nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'action.php?' . http_build_query($url_params, null, '&'))->text(NETCAT_VERSION_RESTORE);
    } else {
        $row->td();
    }

    $table
        ->add_row()->hide()
        ->td()->attr('colspan', 5)->padding_10();

}

echo $table;

?>
<script>
(function() {
    $nc('.nc-version-details').click(function() {
        var link = $nc(this),
            row = link.closest('tr'),
            next_row = row.next(),
            changes_cell = next_row.find('td');
        next_row.toggle();
        if (!next_row.data('loaded')) {
            changes_cell.html('<i class="nc-icon nc--loading"></i>');
            $nc.get('<?= nc_controller_url('admin.subdivision', 'show_diff')  ?>&version_id=' + link.data('version-id'), function(response) {
                changes_cell.html(response);
            });
            next_row.data('loaded', true);
        }
        return false;
    });
})();
</script>
