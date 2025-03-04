<?php if (!class_exists('nc_core')) { die; } ?>

<?= $ui->controls->site_select($site_id) ?>

<div id="nc_landing_create_page">

    <? if (!empty($created_page_url)): ?>
        <?= $ui->alert->info(sprintf(NETCAT_MODULE_LANDING_CREATED_PAGE_INFO, htmlspecialchars($created_page_url))) ?>
    <? endif; ?>

    <style scoped>
        #nc_landing_create_page h2 { font-size: 120%; font-weight: bold; margin-top: 20px; }
        #nc_landing_create_page h2:first-of-type { margin-top: 0; }
        #nc_landing_create_page { max-width: 1500px; margin-bottom: 30px; }
    </style>

    <form class="nc-form" action="<?= $current_url ?>" method="POST">
        <input type="hidden" name="action" value="create_landing">
        <input type="hidden" name="response_type" value="html">
        <input type="hidden" name="site_id" value="<?= $site_id ?>">
        <input type="hidden" name="component_id" value="">
        <input type="hidden" name="object_id" value="">
        <input type="hidden" name="preset_keyword" value="<?= $presets->first()->get_keyword() ?>">

        <? if (count($user_presets)): ?>
            <h2><?= NETCAT_MODULE_LANDING_PRESETS_BUILT_IN ?></h2>
        <? endif; ?>
        <?= $this->include_view('preset_list')->with('presets', $presets) ?>

        <? if (count($user_presets)): ?>
            <h2><?= NETCAT_MODULE_LANDING_PRESETS_USER_DEFINED ?></h2>
            <?= $this->include_view('preset_list', array('presets' => $user_presets, 'selected_preset_keyword' => false)) ?>
        <? endif; ?>

    </form>


</div>