<?php
if (!class_exists('nc_core')) {
    die;
}
?>

<div class="nc-modal-dialog nc-version-page">
    <div class="nc-modal-dialog-header">
        <h2><?= NETCAT_QUICKBAR_BUTTON_SUBDIVISION_VERSIONS ?></h2>
    </div>
    <div class="nc-modal-dialog-body">
        <form action="<?= $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>action.php?ctrl=admin.version&action=restore_page&subdivision_id=<?=$subdivision_id?>" method="post" class="nc-form">

        <?php
        $table = $ui->table()->wide();
        $thead = $table->thead()
            ->th('')
            ->th(NETCAT_VERSION_TABLE_TIMESTAMP)
            ->th(NETCAT_VERSION_TABLE_USER)
            ->th(NETCAT_VERSION_TABLE_CHANGES);

        /** @var nc_version_record $version */
        foreach ($versions as $version) {
            if (!$version->get_changes()) {
                continue;
            }

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

            $changes_string =
                "<a href='#' class='nc-version-details' data-version-id='{$version->get_id()}' title='" .
                htmlspecialchars(NETCAT_VERSION_TABLE_SHOW_CHANGES, ENT_QUOTES) .
                "'>" . htmlspecialchars($version->get_changeset_description()) . "</a>";

            $table->add_row()
                ->td($ui->html->input('radio', 'id', $version->get_id()))
                ->td($datetime)
                ->td($user)
                ->td($changes_string);

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


        </form>
    </div>
    <div class="nc-modal-dialog-footer">
        <button data-action="submit"><?= NETCAT_VERSION_RESTORE ?></button>
        <button data-action="close"><?= CONTROL_BUTTON_CANCEL ?></button>
    </div>
    <script>
        (function() {
            var dialog = nc.ui.modal_dialog.get_current_dialog();
            dialog.set_option('on_submit_response', function(response) {
                location.reload();
            });
        })();
    </script>
</div>