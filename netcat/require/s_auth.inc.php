<?

#   1 - read
#   2 - add
#   4 - subscribe
#   8 - change
#  16 - moderate

/**
 * @param array $cc_env свойства инфоблока
 * @param string $action read|add|subscribe|change|moderate
 * @param bool $posting
 * @param null|int $object_user_id
 * @return bool
 * @throws Exception
 */
function s_auth($cc_env, $action, $posting, $object_user_id = null) {
    /** @var nc_Core $nc_core */
    /** @var Permission $perm */
    global $nc_core, $perm, $admin_mode, $user_table_mode, $AUTH_USER_ID;

    // редактирование пользователя через лицевую часть
    if ($action === 'change' && $user_table_mode && ($AUTH_USER_ID || Authorize())) {
        return true;
    }

    if ($action === 'index' || $action === 'full' || $action === 'search') {
        $action = 'read';
    }

    $cc = isset($cc_env['Sub_Class_ID']) ? $cc_env['Sub_Class_ID'] : null;

    $MODULE_VARS = $nc_core->modules->get_module_vars();

    // для модуля подписки версии 2 своя проверка прав
    if ($action === 'subscribe' && $MODULE_VARS['subscriber']['VERSION'] > 1) {
        try {
            $nc_s = nc_subscriber::get_object();
            $mailer_id = $nc_s->get_mailer_by_cc($cc, 'Mailer_ID');
            return $nc_s->check_rights($mailer_id);
        } catch (Exception $e) {}
    }
    // параметры текущего раздела
    $sub_env = $nc_core->subdivision->get_current();
    // Если нет сс, то права на доступ нужно взять из раздела
    $instance = (is_array($cc_env) && $cc) ? 'cc_env' : 'sub_env';

    switch ($action) {
        case 'add':
            $f_access = ${$instance}['Write_Access_ID'];
            break;
        case 'change':
            global $delete, $checked; // нужно точно узнать, какое изменение происходит
            switch (true) {
                case isset($delete):
                    $f_access = ${$instance}['Delete_Access_ID'];
                    break;
                case isset($checked):
                    $f_access = ${$instance}['Checked_Access_ID'];
                    break;
                default:
                    $f_access = ${$instance}['Edit_Access_ID'];
                    break;
            }
            break;
        case 'subscribe':
            $f_access = ${$instance}['Subscribe_Access_ID'];
            break;
        case 'comment':
            $f_access = ${$instance}['Comment_Access_ID'];
            break;
        case 'moderate':
            $f_access = 3;
            break; //модерирование, надо проверить, не забанен ли, а потом проверить на наличие соответ. права
        case 'read':
        default:
            $f_access = ${$instance}['Read_Access_ID'];
            break;
    }

    $f_access = (int)$f_access;

    // действия с объектами (изменение, удаление, включение/выключение) не доступно неавторизованным
    if ($f_access === 1 && $action === 'change') {
        $f_access = 2;
    }

    switch ($f_access) {

        case 1:// все
            if ($admin_mode) {
                if (!Authorize()) {
                    return false;
                }
                if (!CheckUserRights($cc, $action, 1, $object_user_id)) {
                    return false;
                }
            }
            break;
        case 2: // только зарегистрированные
            if (!Authorize()) {
                return false;
            }
            if ($perm->isBanned($cc_env, $action)) {
                return false;
            }
            break;
        case 3: // только уполномоченные
            if (!Authorize()) {
                return false;
            }
            if ($perm->isBanned($cc_env, $action)) {
                return false;
            }
            if (!CheckUserRights($cc, $action, $posting, $object_user_id)) {
                return false;
            }
            break;

        default: break;
    }

    return true;
}
