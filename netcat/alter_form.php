<?php
$NETCAT_FOLDER = join(strstr(__FILE__, '/') ? '/' : '\\', array_slice(preg_split('/[\/\\\]+/', __FILE__), 0, -2)) . (strstr(__FILE__, '/') ? '/' : '\\');
@include_once($NETCAT_FOLDER . 'vars.inc.php');
require_once $ROOT_FOLDER . 'connect_io.php';

if (!isset($_POST['act'])) {
    exit;
}

if (!isset($nc_core)) {
    $nc_core = nc_Core::get_object();
}

# назначаем основное действие
switch ($_POST['act']) {
    case 'AddTemplate':
        # альтернативная форма добавления
        $action = 'add';
        $nc_core->action = 'add';
        $action_file = 'add';
        break;
    case 'EditTemplate':
        # альтернативная форма изменения
        $action = 'change';
        $nc_core->action = 'change';
        $action_file = 'message';
        break;
    case 'FullSearchTemplate':
    case 'SearchTemplate':
        # альтернативные формы поиска
        $action = 'search';
        $nc_core->action = 'search';
        break;
    case 'AddCond':
        # условие добавления объекта
        $action = 'addcond';
        $nc_core->action = 'addcond';
        break;
    case 'EditCond':
        # условие изменения объекта
        $action = 'editcond';
        $nc_core->action = 'editcond';
        break;
    case 'AddActionTemplate':
        # действие после добавления
        $action = 'addaction';
        $nc_core->action = 'addaction';
        break;
    case 'EditActionTemplate':
        # действие после редактирования
        $action = 'editaction';
        $nc_core->action = 'editaction';
        break;
    case 'CheckActionTemplate':
        # действие после удаления
        $action = 'checkaction';
        $nc_core->action = 'checkaction';
        break;
    case 'DeleteTemplate':
        # альтернативная форма удаления
        $action = 'message';
        $nc_core->action = 'message';
        break;
    case 'DeleteActionTemplate':
        # действие после удаления
        $action = 'deleteaction';
        $nc_core->action = 'deleteaction';
        break;
}

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -2)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $INCLUDE_FOLDER . 'index.php';

if (!($_POST['act'] && $_POST['classID']) || !($perm instanceof Permission && $perm->isSupervisor())) {
    exit;
}

# переназначаем $classID, потому что после предыдущего инклуда он слетает на 1, т.к. не задан!
$classID = (int)$_POST['classID'];
# если параметр - системная таблица
if (isset($_POST['systemTableID'])) {
    $systemTableID = (int)$_POST['systemTableID'];
    if ($systemTableID == 3) {
        $user_table_mode = true;
    }
}
# данные о полях в этом компоненте
require $ROOT_FOLDER . 'message_fields.php';

$File_Mode = nc_get_file_mode('Class', $classID);

$result = '';

switch ($action) {
    case 'add':
    case 'change':
    case 'search':
    case 'message':
        # получаем код формы
        if ($File_Mode) {
            $result = nc_fields_form_fs($action);
        } else {
            $result = nc_fields_form($action);
        }
        break;

    case 'addcond':
    case 'editcond':
        # получаем код формы
        $result = nc_fields_condition_code($action);       
        if ($File_Mode) {
            $result = "<?php\r\n{$result}?>";
        }
        break;

    case 'addaction':
    case 'editaction':
    case 'checkaction':
    case 'deleteaction':
        # получаем код формы
        if ($File_Mode) {
            $result = nc_fields_action_code_fs($action);
        } else {
            $result = nc_fields_action_code($action);
        }
        break;
}

# выводим результат в вывод
if ($result) {
    echo $result;
}
