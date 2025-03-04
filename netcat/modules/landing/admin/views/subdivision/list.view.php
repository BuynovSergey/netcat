<?php if (!class_exists('nc_core')) { die; } ?>

<?php
$nc_core = nc_core::get_object();
$netcat_folder = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH;
?>

<div class="nc-landing-page-list" style="margin-bottom: 20px">
    <? foreach ($pages as $page) : ?>
        <div class="nc-landing-page">
            <ul class="nc6-toolbar nc-landing-page-toolbar">
                <li>
                    <!-- редактировать -->
                    <a href="<?= $netcat_folder . "?inside_admin=0&sub=$page[id]" ?>" target="_blank">
                        <i class='nc-icon-edit' title="<?= NETCAT_MODERATION_CHANGE ?>"></i>
                    </a>
                </li><li>
                    <!-- параметры раздела -->
                    <a href="<?= $nc_core->ADMIN_PATH . "#subdivision.edit($page[id])" ?>" target="_top">
                        <i class="nc-icon-settings" title="<?= CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONS ?>"></i>
                    </a>
                </li><li>
                    <!-- удаление раздела -->
                    <a href="<?= $nc_core->ADMIN_PATH . "#subdivision.delete($page[id])" ?>" target="_top">
                        <i class="nc-icon-trash" title="<?= CONTROL_CONTENT_SUBDIVISION_FUNCS_DELETE ?>"></i>
                    </a>
                </li>
            </ul>

            <a href="<?= $page['url'] ?>" target="_blank">
                <div class="nc-landing-page-title"><?= $page['name'] ?></div>
                <div class="nc-landing-page-url"><?= $page['path'] ?></div>
            </a>
        </div>
    <? endforeach; ?>
</div>

<script>
    function nc_landing_open_create_dialog() {
        nc.load_dialog('<?= $landing_create_dialog_url ?>')
            .set_option('on_submit_response', function(response, status, event, form) {
                mainView.refreshIframe();
            });
    }
</script>