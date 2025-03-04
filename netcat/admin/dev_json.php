<?php

const NC_ADMIN_DISABLE_LOGIN_PAGE = true;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -3)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $ADMIN_FOLDER . 'function.inc.php';
$nc_core = nc_Core::get_object();
$nc_core->load('modules');
$File_Mode = +$_REQUEST['fs'];
$has_auth_module = $nc_core->modules->get_by_keyword('auth', 0);
$has_auth_module_installed = $nc_core->modules->get_by_keyword('auth');

// Показываем дерево разработчика, если у пользователя есть на это права
if (!$perm instanceof Permission || (!$perm->isAccessDevelopment() && !$perm->isGuest())) {
    exit(NETCAT_MODERATION_ERROR_NORIGHT);
}

if (strpos($node, '-') ===  false) {
    $node_type = $node;
    $node_id = 0;
} else {
    list($node_type, $node_id) = explode('-', $node);
}

if ($node_type !== 'group' && $node_type !== 'widgetgroup') {
    $node_id = (int)$node_id;
}

if ($_GET['node_action'] !== 'root') {
    list($node_type, $node_id) = explode('-', $_GET['node_action']);
}

if (!isset($_GET['action'])) {
    $_GET['action'] = '';
}

$fs_suffix = $File_Mode ? '_fs' : '';

$field_types = array(
    1 => 'field-string',
    2 => 'field-int',
    3 => 'field-text',
    4 => 'field-select',
    5 => 'field-bool',
    6 => 'field-file',
    7 => 'field-float',
    8 => 'field-date',
    9 => 'field-link',
    10 => 'field-multiselect',
    11 => 'field-multifile'
);

$input = $nc_core->input;
$db = $nc_core->db;

if ($input->fetch_get('action') === 'search') {
    $term = $db->escape($input->fetch_get('term'));

    $term_id = array();

    switch ($node) {
        case 'dataclass.list':
            $term_id = $db->get_col("SELECT `Class_ID` FROM `Class` WHERE `System_Table_ID` = 0 AND `File_Mode` = {$File_Mode} AND `ClassTemplate` = 0 AND `Class_Name` LIKE '%{$term}%'");
            $prefix = 'dataclass-';
            break;
        case 'templates':
            $term_id = $db->get_col("SELECT `Template_ID` FROM `Template` WHERE `File_Mode` = {$File_Mode} AND `Description` LIKE '%{$term}%'");
            $prefix = 'template-';
            break;
        case 'widgetclass.list':
            $term_id = $db->get_col("SELECT `Widget_Class_ID` FROM `Widget_Class` WHERE `File_Mode` = {$File_Mode} AND `Name` LIKE '%{$term}%'");
            $prefix = 'widgetclass-';
            break;
        case 'classificator.list':
            $term_id = $db->get_col("SELECT `Classificator_ID` FROM `Classificator` WHERE `Classificator_Name` LIKE '%{$term}%'");
            $prefix = 'classificator-';
            break;
        default:
            exit();
    }

    $result = array();
    $term_id = (array)$term_id;

    foreach ($term_id as $id) {
        $result[] = $prefix . $id;
    }

    print json_encode($result);
    exit;
}

// Открывать дерево на заданном узле для раздела шаблонов
$allowed_node_types = array('group', 'dataclass', 'field', 'widgetclass', 'template_partials', 'template_partial');

$is_node_set = $node_id && $input->fetch_get('action') === 'get_path';

$ret = array();

if ($is_node_set && in_array($node_type, $allowed_node_types, true)) {
    switch ($node_type) {
        case 'dataclass':
            $row = $db->get_row("SELECT MD5(`Class_Group`) AS `Class_Group_md5` FROM `Class` WHERE `Class_ID` = '{$node_id}' AND `System_Table_ID` = 0", ARRAY_A);
            $ret[] = 'group-' . $row['Class_Group_md5'];
            break;
        case 'widgetclass':
            $row = $db->get_row("SELECT MD5(`Category`) AS `Category_md5` FROM `Widget_Class` WHERE `Widget_Class_ID` = '{$node_id}'", ARRAY_A);
            $ret[] = 'widgetgroup-' . $row['Category_md5'];
            break;
        case 'field':
            if ($node === 'widgetclass.list') {
                $row = $db->get_row("SELECT `Widget_Class_ID` FROM `Field` WHERE `Field_ID` = '{$node_id}'", ARRAY_A);
                $ret[] = 'widgetclass-' . $row['Widget_Class_ID'];
                $row = $db->get_row("SELECT MD5(`Category`) AS `Category_md5` FROM `Widget_Class` WHERE `Widget_Class_ID` = '{$row['Widget_Class_ID']}'", ARRAY_A);
                $ret[] = 'widgetgroup-' . $row['Category_md5'];
            } else {
                $row = $db->get_row("SELECT `Class_ID` FROM `Field` WHERE `Field_ID` = '{$node_id}'", ARRAY_A);
                $ret[] = 'dataclass-' . $row['Class_ID'];
                $row = $db->get_row("SELECT MD5(`Class_Group`) AS `Class_Group_md5` FROM `Class` WHERE `Class_ID` = '{$row['Class_ID']}' AND `System_Table_ID` = 0", ARRAY_A);
                $ret[] = 'group-' . $row['Class_Group_md5'];
            }
            break;
        case 'template_partial':
            $ret[] = 'template_partials-' . $node_id;
            break;
        case 'template_partials':
            $ret[] = 'template-' . $node_id;
            break;
    }

    $ret = array_reverse($ret);
    print 'while(1);' . nc_array_json($ret);
    exit;
}

if ($is_node_set && ($node_type === 'classtemplates' || $node_type === 'classtemplate')) {
    switch ($node_type) {
        case 'classtemplates':
            $ret[] = 'classtemplates-' . $node_id;
            $ret[] = 'dataclass-' . $node_id;
            $row = $db->get_row("SELECT MD5(`Class_Group`) AS `Class_Group_md5` FROM `Class` WHERE `Class_ID` = '{$node_id}' AND `System_Table_ID` = 0", ARRAY_A);
            $ret[] = 'group-' . $row['Class_Group_md5'];
            break;
        case 'classtemplate':
            list($class_template, $system_table_id) = $db->get_row("SELECT `ClassTemplate`, `System_Table_ID` FROM `Class` WHERE `Class_ID` = '{$node_id}'", ARRAY_N);
            $ret[] = 'classtemplates-' . $class_template;
            if ($system_table_id) {
                $ret[] = 'systemclass-' . $system_table_id;
            } else {
                $ret[] = 'dataclass-' . $class_template;
                $row = $db->get_row("SELECT MD5(`Class_Group`) AS `Class_Group_md5` FROM `Class` WHERE `Class_ID` = '{$class_template}' AND `System_Table_ID` = 0", ARRAY_A);
                $ret[] = 'group-' . $row['Class_Group_md5'];
            }
            break;
    }

    $ret = array_reverse($ret);
    print 'while(1);' . nc_array_json($ret);
    exit;
}

// Открывать дерево на заданном узле для раздела списков
if ($is_node_set && $node_type === 'classificator') {
    $ret = array_reverse((array)$ret);
    print 'while(1);' . nc_array_json($ret);
    exit;
}

if ($is_node_set && $node_type === 'template') {
    while ($node_id) {
        $templates = (array)$db->get_results(
            "SELECT `Template_ID`,
                    `Description`,
                    `Parent_Template_ID`
                    (SELECT COUNT(*) FROM `Template` WHERE `Parent_Template_ID` = '{$node_id}') AS 'Children_Count'
             FROM `Template`
             WHERE `Template_ID` = '{$node_id}'
             ORDER BY `Priority`, `Template_ID`",
            ARRAY_A
        );
        if (!empty($templates)) {
            foreach ($templates as $template) {
                $hasChildren = $template['Children_Count'];
                $ret[] = 'template-' . $template['Template_ID'];
                $node_id = $template['Parent_Template_ID'];
            }
        } else {
            $node_id = false;
        }
    }
    if (is_array($ret)) {
        $ret = array_reverse($ret);
        array_pop($ret);
        print 'while(1);' . nc_array_json($ret);
    }
    exit;
}

if ($is_node_set && ($node_type === 'systemclass' || $node_type === 'systemfield')) {
    if ($node_type === 'systemfield') {
        $row = $db->get_var("SELECT `System_Table_ID` FROM `Field` WHERE `Field_ID` = '{$node_id}'");
        $ret[] = 'systemclass-' . $row['System_Table_ID'];
    }
    $ret = array_reverse($ret);
    print 'while(1);' . nc_array_json($ret);
    exit;
}

$ret = array();
$ret_dev = array();
$ret_groups = array();
$ret_widgetgroups = array();
$ret_classes = array();
$ret_widgetclasses = array();
$ret_class_group = array();
$ret_class_templates = array();
$ret_fields = array();
$ret_widgetfields = array();
$ret_classificators = array();
$ret_system_class = array();
$ret_system_fields = array();
$ret_templates = array();
$ret_redirects = array();

// Строим дерево шаблонов
if ($node_type === 'root') {
    exit;
} // Дерево шаблонов данных
elseif ($node_type === 'dataclass.list') {
    // Выборка групп шаблонов
    $class_groups = (array)$db->get_results(
        "SELECT `Class_Group`, MD5(`Class_Group`) AS `Class_Group_md5`
         FROM `Class`
         WHERE `System_Table_ID` = 0 AND `ClassTemplate` = 0 AND `File_Mode` = {$File_Mode}
         GROUP BY `Class_Group`
         ORDER BY `Class_Group`",
        ARRAY_A
    );

    foreach ($class_groups as $class_group) {
        $classgroup_buttons = array();
        $classgroup_buttons[] = nc_get_array_2json_button(
            CONTROL_CLASS_ADD,
            "dataclass{$fs_suffix}.add({$class_group['Class_Group_md5']})",
            'nc-icon nc--dev-components-add nc--hovered'
        );

        $ret_groups[] = array(
            'nodeId'       => 'group-' . $class_group['Class_Group_md5'],
            'name'         => $class_group['Class_Group'] ? : CONTROL_CLASS_CLASS_NO_GROUP,
            'href'         => "#classgroup.edit({$class_group['Class_Group_md5']})",
            'sprite'       => 'dev-components' . ($File_Mode ? '' : '-v4'),
            'acceptDropFn' => 'treeClassAcceptDrop',
            'onDropFn'     => 'treeClassOnDrop',
            'hasChildren'  => true,
            'dragEnabled'  => true,
            'buttons'      => $classgroup_buttons
        );
    }
} elseif ($node_type === 'group' && $node_id) {
    // Выборка шаблонов определенной группы
    $classes = (array)$db->get_results(
        "SELECT `Class`.`Class_ID`,
                `Class`.`Class_Name`,
                `Class`.`ClassTemplate`,
                COUNT(DISTINCT `Field`.`Field_ID`) AS 'Field_Count',
                COUNT(DISTINCT `ClassTemplates`.`Class_ID`) AS 'ClassTemplate_Count'
         FROM `Class`
         LEFT JOIN `Field` ON `Class`.`Class_ID` = `Field`.`Class_ID`
         LEFT JOIN `Class` ClassTemplates ON `Class`.`Class_ID` = `ClassTemplates`.`ClassTemplate`
         WHERE MD5(`Class`.`Class_Group`) = '{$node_id}' AND `Class`.`ClassTemplate` = 0  AND `Class`.`File_Mode` = {$File_Mode} AND `Class`.`System_Table_ID` = 0
         GROUP BY `Class`.`Class_ID`
         ORDER BY `Class`.`Class_Group`, `Class`.`Priority`, `Class`.`Class_ID`",
        ARRAY_A
    );

    foreach ($classes as $class) {
        // count component fields
        $hasChildren = $class['Field_Count'];
        // count component templates
        if (!$hasChildren) {
            $hasChildren = $class['ClassTemplate_Count'];
        }

        $class_buttons = array();

        $class_buttons[] = nc_get_array_2json_button(
            CONTROL_FIELD_LIST_ADD,
            "field{$fs_suffix}.add({$class['Class_ID']})",
            'nc-icon nc--file-add nc--hovered');

        $class_buttons[] = nc_get_array_2json_button(
            CONTROL_CLASS_DELETE,
            "dataclass{$fs_suffix}.delete({$class['Class_ID']})",
            'nc-icon nc--remove nc--hovered');

        $ret_classes[] = array(
            'nodeId'       => 'dataclass-' . $class['Class_ID'],
            'name'         => $class['Class_ID'] . '. ' . $class['Class_Name'],
            'href'         => '#dataclass.edit(' . $class['Class_ID'] . ')',
            'sprite'       => 'dev-components' . ($File_Mode ? '' : '-v4'),
            'acceptDropFn' => 'treeClassAcceptDrop',
            'onDropFn'     => 'treeClassOnDrop',
            'hasChildren'  => $hasChildren,
            'dragEnabled'  => true,
            'buttons'      => $class_buttons
        );
    }
} elseif ($node_type === 'dataclass' && $node_id) {
    // Выборка полей определенного шаблона
    $fields = (array)$db->get_results(
        "SELECT `Field_ID`, `Field_Name`, `TypeOfData_ID`, `Description`, `NotNull`
         FROM `Field`
         WHERE `Class_ID` = '{$node_id}' AND `System_Table_ID` = 0
         ORDER BY `Priority`",
        ARRAY_A
    );

    foreach ($fields as $field) {
        $field_buttons = array();
        $field_buttons[] = nc_get_array_2json_button(
            CONTROL_FIELD_LIST_DELETE,
            "field{$fs_suffix}.delete({$node_id},{$field['Field_ID']})",
            'nc-icon nc--remove nc--hovered');

        $ret_fields[] = array(
            'nodeId'       => 'field-' . $field['Field_ID'],
            'name'         => $field['Field_ID'] . '. ' . $field['Field_Name'],
            'title'        => $field['Description'],
            'href'         => "#field.edit({$field['Field_ID']})",
            'sprite'       => $field_types[$field['TypeOfData_ID']] . ($field['NotNull'] ? ' nc--required' : ''),
            'acceptDropFn' => 'treeFieldAcceptDrop',
            'onDropFn'     => 'treeFieldOnDrop',
            'hasChildren'  => false,
            'dragEnabled'  => true,
            'buttons'      => $field_buttons
        );
    }

    $hasTemplates = $db->get_var("SELECT COUNT(`Class_ID`) FROM `Class` WHERE `ClassTemplate` = '{$node_id}'");

    if ($hasTemplates) {
        $class_template_buttons = array();
        $class_template_buttons[] = nc_get_array_2json_button(
            CONTROL_CLASS_CLASS_TEMPLATE_ADD,
            "classtemplate{$fs_suffix}.add({$node_id})",
            'nc-icon nc--file-add nc--hovered');

        $ret_class_templates[] = array(
            'nodeId'       => 'classtemplates-' . $node_id,
            'name'         => CONTROL_CLASS_CLASS_TEMPLATES,
            'href'         => "#classtemplates.edit({$node_id})",
            'sprite'       => 'dev-templates' . ($File_Mode ? '' : '-v4'),
            'acceptDropFn' => 'treeClassAcceptDrop',
            'onDropFn'     => 'treeClassOnDrop',
            'hasChildren'  => $hasTemplates,
            'dragEnabled'  => false,
            'buttons'      => $class_template_buttons
        );
    }
} // Список шаблонов компонента
elseif ($node_type === 'classtemplates' && $node_id) {
    // get component templates
    $class_templates = (array)$db->get_results(
        "SELECT `Class_ID`, `Class_Name` FROM `Class`
         WHERE `ClassTemplate` = '{$node_id}'
         ORDER BY `Priority`, `Class_ID`",
        ARRAY_A
    );

    foreach ($class_templates as $class_template) {
        $class_templates_buttons = array();
        $class_templates_buttons[] = nc_get_array_2json_button(
            CONTROL_CLASS_DELETE,
            "classtemplate{$fs_suffix}.delete({$class_template['Class_ID']})",
            'nc-icon nc--remove nc--hovered');

        $ret_class_templates[] = array(
            'nodeId'       => 'classtemplate-' . $class_template['Class_ID'],
            'name'         => $class_template['Class_ID'] . '. ' . $class_template['Class_Name'],
            'href'         => "#classtemplate.edit({$class_template['Class_ID']})",
            'sprite'       => 'dev-templates' . ($File_Mode ? '' : '-v4'),
            'acceptDropFn' => 'treeFieldAcceptDrop',
            'onDropFn'     => 'treeFieldOnDrop',
            'hasChildren'  => false,
            'dragEnabled'  => false,
            'buttons'      => $class_templates_buttons
        );
    }
} // Дерево системных таблиц
elseif ($node_type === 'systemclass.list') {
    // Выборка системных таблиц
    $system_class_condition = '';

    if ($has_auth_module) {
        $system_class_condition = "WHERE IF(b.`System_Table_ID` = 3, (b.`File_Mode` = {$File_Mode}) , 1)";
    }

    $system_classes = (array)$db->get_results(
        "SELECT a.`System_Table_ID`, a.`System_Table_Rus_Name`, b.`Class_ID`,
         IF(b.`AddTemplate` <> '' OR b.`AddCond` <> '' OR b.`AddActionTemplate` <> '', 1, 0) AS IsAdd,
         IF(b.`EditTemplate` <> '' OR b.`EditCond` <> '' OR b.`EditActionTemplate` <> '' OR b.`CheckActionTemplate` <> '' OR b.`DeleteActionTemplate` <> '', 1, 0) AS IsEdit,
         IF(b.`SearchTemplate` <> '' OR b.`FullSearchTemplate` <> '', 1, 0) AS IsSearch,
         IF(b.`SubscribeTemplate` <> '' OR b.`SubscribeCond` <> '', 1, 0) AS IsSubscribe
         FROM `System_Table` AS a
         LEFT JOIN `Class` AS b ON (a.`System_Table_ID` = b.`System_Table_ID` AND b.ClassTemplate = 0)
         {$system_class_condition}
         GROUP BY a.`System_Table_ID`
         ORDER BY a.`System_Table_ID`",
        ARRAY_A
    );

    foreach ($system_classes as $system_class) {
        if (!$File_Mode && $system_class['System_Table_ID'] != 3) {
            continue;
        }
        $hasChildren = $db->get_var("SELECT COUNT(`Field_ID`) FROM `Field` WHERE `System_Table_ID` = '{$system_class['System_Table_ID']}'");
        $system_class_buttons = array();
        $system_class_buttons[] = nc_get_array_2json_button(
            CONTROL_FIELD_LIST_ADD,
            "systemfield{$fs_suffix}.add({$system_class['System_Table_ID']})",
            'nc-icon nc--file-add nc--hovered'
        );

        $href = "#systemclass.fields({$system_class['System_Table_ID']})";

        if ($system_class['Class_ID'] && $has_auth_module) {
            $href = "#systemclass.edit({$system_class['System_Table_ID']})";
        }

        $ret_system_class[] = array(
            'nodeId'      => 'systemclass-' . $system_class['System_Table_ID'],
            'name'        => $system_class['System_Table_ID'] . '. ' . constant($system_class['System_Table_Rus_Name']),
            'href'        => $href,
            'sprite'      => 'dev-system-tables' . ($File_Mode ? '' : '-v4'),
            'hasChildren' => $hasChildren,
            'dragEnabled' => false,
            'buttons'     => $system_class_buttons
        );
    }
} elseif ($node_type === 'systemclass' && $node_id) {
    // Выборка полей определенного шаблона
    $system_fields = (array)$db->get_results(
        "SELECT field.`Field_ID`, field.`Field_Name`, field.`TypeOfData_ID`, field.`Description`, field.`NotNull`
         FROM `Field` AS field
         LEFT JOIN `Classificator_TypeOfData` AS type ON type.`TypeOfData_ID` = field.`TypeOfData_ID`
         WHERE field.`System_Table_ID` = '{$node_id}'
         ORDER BY field.`Priority`",
        ARRAY_A
    );

    foreach ($system_fields as $system_field) {
        $system_field_buttons = array();
        $system_field_buttons[] = nc_get_array_2json_button(
            CONTROL_FIELD_LIST_DELETE,
            "systemfield{$fs_suffix}.delete({$node_id},{$system_field['Field_ID']})",
            'nc-icon nc--remove nc--hovered');

        $ret_system_fields[] = array(
            'nodeId'       => 'systemfield-' . $system_field['Field_ID'],
            'name'         => $system_field['Field_ID'] . '. ' . $system_field['Field_Name'],
            'href'         => "#systemfield.edit({$system_field['Field_ID']})",
            'title'        => $system_field['Description'],
            'sprite'       => $field_types[$system_field['TypeOfData_ID']] . ($system_field['NotNull'] ? ' nc--required' : ''),
            'acceptDropFn' => 'treeSystemFieldAcceptDrop',
            'onDropFn'     => 'treeSystemFieldOnDrop',
            'hasChildren'  => false,
            'dragEnabled'  => true,
            'buttons'      => $system_field_buttons
        );
    }

    // count component templates
    $hasTemplates = 0;
    if ($node_id == 3 && $has_auth_module_installed) {
        $hasTemplates = $db->get_var('SELECT COUNT(`Class_ID`) FROM `Class` WHERE `ClassTemplate` > 0 AND `System_Table_ID` = 3 AND File_Mode = ' . $File_Mode);
        $user_class_id = $db->get_var('SELECT `Class_ID` FROM `Class` WHERE `ClassTemplate` = 0 AND `System_Table_ID` = 3 AND File_Mode = ' . $File_Mode);
    }

    if ($hasTemplates) {
        $class_template_buttons = array();
        $class_template_buttons[] = nc_get_array_2json_button(
            CONTROL_CLASS_CLASS_TEMPLATE_ADD,
            "classtemplate{$fs_suffix}.add({$user_class_id})",
            'nc-icon nc--file-add nc--hovered');

        $ret_class_templates[] = array(
            'nodeId'       => 'classtemplates-' . $user_class_id,
            'name'         => CONTROL_CLASS_CLASS_TEMPLATES,
            'href'         => "#classtemplates.edit({$user_class_id})",
            'sprite'       => 'dev-templates' . ($File_Mode ? '' : '-v4'),
            'acceptDropFn' => 'treeClassAcceptDrop',
            'onDropFn'     => 'treeClassOnDrop',
            'hasChildren'  => $hasTemplates,
            'dragEnabled'  => false,
            'buttons'      => $class_template_buttons
        );
    }
} // Дерево макетов
elseif (($node_type === 'template' && $node_id) || ($node_type === 'templates')) {
    // Получение дерева макетов
    if (!$node_id) {
        $node_id = 0;
    }
    $template_table = nc_db_table::make('Template');

    $templates = (array)$template_table->select('`Template_ID`, `Description`')
        ->where('Parent_Template_ID', $node_id)->where('File_Mode', $File_Mode)
        ->order_by('Priority')->order_by('Template_ID')
        ->index_by_id()->get_result();

    $children_count = $template_table->select('COUNT(*) as total, Parent_Template_ID')
        ->where_in('Parent_Template_ID', array_keys($templates))
        ->group_by('Parent_Template_ID')
        ->get_list('Parent_Template_ID', 'total');

    // Представления макета дизайна
    if ($File_Mode && $node_id) {
        $is_root_template = !$template_table->where_id($node_id)->get_value('Parent_Template_ID');

        if ($is_root_template) {
            $ret_templates[] = array(
                'nodeId'          => "template_partials-{$node_id}",
                'name'            => CONTROL_TEMPLATE_PARTIALS,
                'href'            => "#template.partials_list({$node_id})",
                'sprite'          => 'dev-com-templates',
                'hasChildren'     => (bool)$nc_core->template->has_partial($node_id),
                // "dragEnabled"  => true,
                // "acceptDropFn" => "templateAcceptDrop",
                // "onDropFn"     => "templateOnDrop",
                'buttons'         => array(
                    nc_get_array_2json_button(
                        CONTROL_TEMPLATE_PARTIALS_ADD,
                        "template{$fs_suffix}.partials_add({$node_id})",
                        'nc-icon nc--dev-templates-add nc--hovered'
                    )
                )
            );
        }
    }


    foreach ($templates as $id => $template) {
        $template_buttons = array();

        $template_buttons[] = nc_get_array_2json_button(
            CONTROL_TEMPLATE_TEPL_CREATE,
            "template{$fs_suffix}.add({$id})",
            'nc-icon nc--dev-templates-add nc--hovered');

        $template_buttons[] = nc_get_array_2json_button(
            CONTROL_TEMPLATE_DELETE,
            "template{$fs_suffix}.delete({$id})",
            'nc-icon nc--remove nc--hovered');

        // Для корневых макетов v5 всегда показывать "+", т.к. они имеют partials
        if ($node_id == 0 && $File_Mode) {
            $has_children = true;
        }
        else {
            $has_children = !empty($children_count[$id]);
        }

        $ret_templates[] = array(
            'nodeId'       => "template-{$id}",
            'name'         => "{$id}. {$template['Description']}",
            'href'         => "#template.edit({$id})",
            'sprite'       => 'dev-templates' . ($File_Mode ? '' : '-v4'),
            'hasChildren'  => $has_children,
            'dragEnabled'  => true,
            'acceptDropFn' => 'templateAcceptDrop',
            'onDropFn'     => 'templateOnDrop',
            'buttons'      => $template_buttons
        );
    }
} // Врезки (дополнительные шаблоны, partials) макета дизайна
elseif ($node_type === 'template_partials' && $node_id) {
    $template_partials = $nc_core->template->get_partials_data($node_id);

    foreach ($template_partials as $partial => $partial_data) {
        $ret_templates[] = array(
            'nodeId'  => "template_partial-{$node_id}-{$partial}",
            'name'    => $partial_data['Description'] ? "$partial_data[Description] ($partial)": $partial,
            'href'    => "#template.partials_edit({$node_id}, {$partial})",
            'sprite'  => 'dev-com-templates',
            'buttons' => array(
                nc_get_array_2json_button(
                    CONTROL_TEMPLATE_PARTIALS_REMOVE,
                    "template{$fs_suffix}.partials_remove({$node_id}, {$partial})",
                    'nc-icon nc--remove nc--hovered'
                )
            )
        );
    }
} elseif ($node_type === 'widgetclass.list') {
    $widgetclass_groups = (array)$db->get_results(
        "SELECT `Category`, MD5(`Category`) as `Category_md5`
         FROM `Widget_Class`
         WHERE File_Mode = $File_Mode
         GROUP BY `Category`
         ORDER BY `Category`",
        ARRAY_A
    );

    foreach ($widgetclass_groups as $widgetclass_group) {
        $widgetclassgroup_buttons = array();
        $widgetclassgroup_buttons[] = nc_get_array_2json_button(
            CONTROL_WIDGETCLASS_ADD,
            "widgetclass{$fs_suffix}.add({$widgetclass_group['Category_md5']})",
            'nc-icon nc--dev-com-widgets-add nc--hovered'
        );
        $ret_widgetgroups[] = array(
            'nodeId'       => 'widgetgroup-' . $widgetclass_group['Category_md5'],
            'name'         => $widgetclass_group['Category'],
            'href'         => "#widgetgroup.edit({$widgetclass_group['Category_md5']})",
            'sprite'       => 'dev-com-widgets' . ($File_Mode ? '' : '-v4'),
            'acceptDropFn' => 'treeClassAcceptDrop',
            'onDropFn'     => 'treeClassOnDrop',
            'hasChildren'  => true,
            'dragEnabled'  => true,
            'buttons'      => $widgetclassgroup_buttons
        );
    }
} elseif ($node_type === 'widgetgroup' && $node_id) {
    $widgetclasses = (array)$db->get_results(
        "SELECT `Widget_Class`.`Widget_Class_ID`,
                `Widget_Class`.`Name`,
                `Widget_Class`.`Template`,
                COUNT(DISTINCT `Field`.`Field_ID`) AS 'Field_Count'
         FROM `Widget_Class`
         LEFT JOIN `Field` ON `Widget_Class`.`Widget_Class_ID` = `Field`.`Widget_Class_ID`
         WHERE MD5(`Widget_Class`.`Category`) = '{$node_id}' AND `Widget_Class`.`File_Mode` = {$File_Mode}
         GROUP BY `Widget_Class`.`Widget_Class_ID`
         ORDER BY `Widget_Class`.`Category`, `Widget_Class`.`Widget_Class_ID`",
        ARRAY_A
    );

    foreach ($widgetclasses as $widgetclass) {
        $hasChildren = $widgetclass['Field_Count'];

        $widgetclass_buttons = array();
        $widgetclass_buttons[] = nc_get_array_2json_button(
            CONTROL_FIELD_LIST_ADD,
            "widgetfield{$fs_suffix}.add({$widgetclass['Widget_Class_ID']})",
            'nc-icon nc--file-add nc--hovered'
        );

        $widgetclass_buttons[] = nc_get_array_2json_button(
            CONTROL_CLASS_DELETE,
            "widgetclass{$fs_suffix}.drop({$widgetclass['Widget_Class_ID']}, 1)",
            'nc-icon nc--remove nc--hovered'
        );

        $ret_widgetclasses[] = array(
            'nodeId'       => 'widgetclass-' . $widgetclass['Widget_Class_ID'],
            'name'         => $widgetclass['Widget_Class_ID'] . '. ' . $widgetclass['Name'],
            'href'         => "#widgetclass.edit({$widgetclass['Widget_Class_ID']})",
            'sprite'       => 'dev-com-widgets' . ($File_Mode ? '' : '-v4'),
            'acceptDropFn' => 'treeClassAcceptDrop',
            'onDropFn'     => 'treeClassOnDrop',
            'hasChildren'  => $hasChildren,
            'dragEnabled'  => true,
            'buttons'      => $widgetclass_buttons
        );
    }
} elseif ($node_type === 'widgetclass' && $node_id) {
    $fields = (array)$db->get_results(
        "SELECT `Field_ID`, `Field_Name`, `TypeOfData_ID`, `Description`, `NotNull`
         FROM `Field`
         WHERE `Widget_Class_ID` = '{$node_id}'
         ORDER BY `Priority`",
        ARRAY_A
    );
    foreach ($fields as $field) {
        $widgetfield_buttons = array();
        $widgetfield_buttons[] = nc_get_array_2json_button(
            CONTROL_FIELD_LIST_DELETE,
            "widgetfield$fs_suffix.delete({$node_id},{$field['Field_ID']})",
            'nc-icon nc--remove nc--hovered'
        );

        $ret_widgetfields[] = array(
            'nodeId'       => 'field-' . $field['Field_ID'],
            'name'         => $field['Field_ID'] . '. ' . $field['Field_Name'],
            'href'         => "#widgetfield.edit({$field['Field_ID']})",
            'title'        => $field['Description'],
            'sprite'       => $field_types[$field['TypeOfData_ID']] . ($field['NotNull'] ? ' nc--required' : ''),
            'acceptDropFn' => 'treeFieldAcceptDrop',
            'onDropFn'     => 'treeFieldOnDrop',
            'hasChildren'  => false,
            'dragEnabled'  => true,
            'buttons'      => $widgetfield_buttons
        );
    }
} // Дерево списков
elseif ($node_type === 'classificator.list') {
    // получение дерева списков
    $classificators = (array)$db->get_results(
        'SELECT `Classificator_ID`, `Classificator_Name`, `System`
         FROM `Classificator`
         ORDER BY `Classificator_ID`',
        ARRAY_A
    );

    $admin_cl = $perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_DEL, 0, 0);

    foreach ($classificators as $classificator) {
        $c_id = $classificator['Classificator_ID']; //for short
        // Проверка на право
        if (!$classificator['System'] && !$perm->isAccess(NC_PERM_CLASSIFICATOR, NC_PERM_ACTION_VIEW, $c_id)) {
            continue;
        }
        //Системные списки показываем только при наличии соответствующих прав
        if ($classificator['System'] && !$perm->isDirectAccessClassificator(NC_PERM_ACTION_VIEW, $c_id)) {
            continue;
        }

        $classificator_buttons = array();
        // Кнопка удалить только для админа всех списков, при условии что список не системный
        if ($admin_cl && !$classificator['System']) {
            $classificator_buttons[] = nc_get_array_2json_button(
                CONTENT_CLASSIFICATORS_LIST_DELETE,
                "classificator.delete({$c_id})",
                'nc-icon nc--remove nc--hovered');
        }
        $ret_classificators[] = array(
            'nodeId'      => 'classificator-' . $c_id,
            'name'        => $c_id . '. ' . htmlspecialchars($classificator['Classificator_Name'], ENT_QUOTES),
            'href'        => "#classificator.edit({$c_id})",
            'sprite'      => 'dev-classificator',
            'hasChildren' => false,
            'dragEnabled' => false,
            'buttons'     => $classificator_buttons
        );
    }
} elseif ($node_type === 'redirect') {
    $redirect_group_table = nc_db_table::make('Redirect_Group');
    $redirect_groups = $redirect_group_table->select()->as_array()->get_result();

    foreach ($redirect_groups as  $redirect_group) {
        $id = $redirect_group['Redirect_Group_ID'];
        $name = $redirect_group['Name'];

        $redirect_buttons = array();

        $redirect_buttons[] = nc_get_array_2json_button(
            TOOLS_REDIRECT_GROUP_EDIT,
            "redirect.group.edit({$id})",
            'nc-icon nc--edit nc--hovered'
        );
        if ($id != 1) {
            $redirect_buttons[] = nc_get_array_2json_button(
                TOOLS_REDIRECT_GROUP_DELETE,
                "redirect.delete({$id})",
                'nc-icon nc--remove nc--hovered'
            );
        }

        $ret_redirects[] = array(
            'nodeId'      => 'redirect-' . $id,
            'name'        => $id . '. ' . $name,
            'href'        => "#redirect.list({$id})",
            'sprite'      => 'dev-classificator',
            'hasChildren' => false,
            'dragEnabled' => false,
            'buttons'     => $redirect_buttons,
        );
    }

    $ret_redirects[] = array(
        'nodeId'      => 'bottom-add',
        'name'        => TOOLS_REDIRECT_GROUP_ADD,
        'href'        => '#redirect.group.add',
        'sprite'      => 'plus',
        'hasChildren' => false,
        'dragEnabled' => false,
    );
}

$ret = array_merge(
    array_values($ret_dev),
    array_values($ret_groups),
    array_values($ret_widgetgroups),
    array_values($ret_classes),
    array_values($ret_widgetclasses),
    array_values($ret_class_templates),
    array_values($ret_fields),
    array_values($ret_widgetfields),
    array_values($ret_classificators),
    array_values($ret_templates),
    array_values($ret_class_group),
    array_values($ret_system_class),
    array_values($ret_system_fields),
    array_values($ret_redirects)
);

print 'while(1);' . nc_array_json($ret);
