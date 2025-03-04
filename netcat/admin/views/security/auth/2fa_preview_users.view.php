<?php

if (!class_exists('nc_core')) {
    die;
}

/** @var nc_core $nc_core */
/** @var array $users */
/** @var string $field_name */
/** @var string $field_description */
/** @var int $max_users */
/** @var int $mode */

?>

<div>
    <?= NETCAT_SECURITY_SETTINGS_2FA_USER_LIST ?>:
</div>
<table class="nc-table nc--wide nc-margin-vertical-medium">
    <tr>
        <th><?= NETCAT_VERSION_TABLE_USER ?></th>
        <th><?= SECTION_INDEX_USER_RIGHTS_RIGHTS ?></th>
        <?php if ($field_name): ?>
            <th><?= $field_description ?></th>
        <?php endif; ?>
    </tr>
    <?php foreach ($users as $user): ?>
        <?php
            $permissions = new Permission($user['User_ID']); // forUser() кэширует данные, тут нам этого не надо
            if (!nc_security_2fa::will_require_2fa($permissions, $mode, false)) {
                continue;
            }
        ?>
        <tr>
            <td>
                <a href="<?= $nc_core->ADMIN_PATH . "#user.edit($user[User_ID])" ?>" target="_blank">
                    <?= $user[$nc_core->AUTHORIZE_BY] ?>
                </a>
            </td>
            <td><?= implode(', ', Permission::get_all_permission_names_by_id($user['User_ID'])) ?></td>
            <?php if ($field_name): ?>
                <td><?= $user[$field_name] ?: '<span class="nc-text-red">' . NETCAT_SECURITY_SETTINGS_2FA_VALUE_NOT_SET . '</span>' ?></td>
            <?php endif; ?>
        </tr>
    <?php endforeach ?>
</table>
