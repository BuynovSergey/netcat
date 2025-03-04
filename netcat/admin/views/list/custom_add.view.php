<?php
if (!class_exists('nc_core')) {
    die;
}

if ($error) {
    echo $ui->alert->error($error);
}

$types = array('string', 'textarea', 'int', 'float', 'checkbox', 'datetime', 'select', 'rel', /*'file',*/ 'color', 'divider', 'custom');
$subtypes = array();
$extend_params = array();
$has_default = array();
$has_initial_value = array();
foreach ($types as $v) {
    $classname = "nc_a2f_field_" . $v;
    if (!class_exists($classname)) {
        die('Incorrect type: ' . $v . '. Class ' . $classname . ' not found.');
    }
    /** @var nc_a2f_field $f */
    $f = new $classname();
    $extend_params[$v] = $f->get_extend_parameters();
    $subtypes[$v] = $f->get_subtypes();
    $has_default[$v] = $f->has_default();
    $has_initial_value[$v] = $f->can_have_initial_value();
    foreach ($subtypes[$v] as $k => $val) {
        $classname = "nc_a2f_field_" . $v . "_" . $val;
        if (!class_exists($classname)) {
            die('Incorrect type: ' . $val . '. Class ' . $classname . ' not found.');
        }
        $f = new $classname();
        $extend_params[$v . "_" . $val] = $f->get_extend_parameters();
        $subtypes[$v][$k] = array($val => constant('NETCAT_CUSTOM_TYPENAME_' . strtoupper($v) . '_' . strtoupper($val)));
    }
}

$settings = array();
if ($custom_settings) {
    $a2f = new nc_a2f($custom_settings, '');
    $custom_settings = $a2f->eval_value($custom_settings);
    if (isset($param)) {
        $settings = nc_array_value($custom_settings, $param, array());
    }
}

$data = $nc_core->input->fetch_get_post();

if (!empty($data['type'])) {
    $settings['type'] = $data['type'];
}
if (!empty($data['subtype'])) {
    $settings['subtype'] = $data['subtype'];
}
if (empty($settings['type'])) {
    $settings['type'] = 'string';
}

$param = isset($param) ? $param : '';

$classname = 'nc_a2f_field_' . $settings['type'];
/** @var nc_a2f_field $cs */
$cs = new $classname($settings);

echo "<script type='text/javascript'>" .
    "nc_cs = new nc_customsettings('" .
    $settings['type'] . "', '" .
    (nc_array_value($settings, 'subtype', '')) . "', " .
    nc_array_json($subtypes) . ", " .
    nc_array_json($has_default) . ", " .
    nc_array_json($has_initial_value) .
    ")" .
    "</script>";

echo "<form action='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "action.php?ctrl=admin.list&action=custom_save&classificator_id=" . $classificator_id . "' method='post' >";
echo "<input type='hidden' name='param' value='" . htmlspecialchars($param) . "' />";
echo "<input type='submit' class='hidden'>";
echo "<fieldset style='margin-bottom: 15px;'><legend>" . NETCAT_CUSTOM_ONCE_MAIN_SETTINGS . "</legend><div style='width: 50%;'>";
echo nc_admin_input(NETCAT_CUSTOM_ONCE_FIELD_NAME, 'name', (isset($data['name']) || $error ? $data['name'] : $param), 0, 'width:100%;  margin-bottom: 10px;');
echo nc_admin_input(NETCAT_CUSTOM_ONCE_FIELD_DESC, 'caption', (isset($data['caption']) || $error ? $data['caption'] : nc_array_value($settings, 'caption')), 0, 'width:100%;  margin-bottom: 10px;');

echo "<div id='def' style='display: " . ($cs->has_default() ? "block" : "none") . "'>" .
    nc_admin_input(NETCAT_CUSTOM_ONCE_DEFAULT, 'default_value', (isset($data['default_value']) || $error ? $data['default_value'] : nc_array_value($settings, 'default_value')), 0, 'width:100%;  margin-bottom: 10px;') .
    "</div>";

echo "<div style='margin-bottom: 20px'><input type='hidden' name='skip_in_form' value='0'>" .
    nc_admin_checkbox(
        NETCAT_CUSTOM_ONCE_DONT_SHOW,
        'skip_in_form',
        $nc_core->input->fetch_get_post('skip_in_form') ?: nc_array_value($settings, 'skip_in_form')
    ) .
    "</div>";

echo NETCAT_CUSTOM_TYPE . ": <br/>
        <select id='type' style='width: 100%;  margin-bottom: 10px;' name='type' onchange='nc_cs.changetype()' >";

foreach ($types as $v) {
    echo "<option value='" . $v . "' " . ($v == $settings['type'] ? "selected='selected' " : "") . ">" . constant('NETCAT_CUSTOM_TYPENAME_' . strtoupper($v)) . "</option>";
}

echo "</select><br/>";

echo "<div id='cs_subtypes_caption' style='display:none;'>" . NETCAT_CUSTOM_SUBTYPE . ":</div>";
echo "<div id='cs_subtypes' style=' margin-bottom: 10px;'></div>";
echo "</div></fieldset>";

foreach ($extend_params as $n => $tp) {
    echo "<div id='extend_" . $n . "' class='cs_extends' style='display: none;'>";

    if (!empty($tp)) {
        echo "<fieldset><legend>" . NETCAT_CUSTOM_ONCE_EXTEND . "</legend><div style='width: 50%;'>";
        foreach ($tp as $name => $v) {
            if ($v['type'] == 'checkbox') {
                echo nc_admin_checkbox($v['caption'], 'cs_' . $name, (($pst = $nc_core->input->fetch_get_post('cs_' . $name)) ? $pst : $settings[$name]));
            } else if ($v['type'] == 'text') {
                if (is_array($settings[$name])) {
                    $settings[$name] = nc_a2f::array_to_string($settings[$name]);
                }
                echo nc_admin_textarea($v['caption'], 'cs_' . $name, (($pst = $nc_core->input->fetch_get_post('cs_' . $name)) ? $pst : $settings[$name]), 1);
            } else if ($v['type'] == 'classificator') {
                echo nc_admin_classificators($v['caption'], 'cs_' . $name, (($pst = $nc_core->input->fetch_get_post('cs_' . $name)) ? $pst : $settings[$name]), 'width:100%;');
            } else if ($v['type'] == 'static') {
                echo nc_admin_select_static($v['caption'], 'cs_' . $name, $settings[$name]);
            } else {
                echo nc_admin_input($v['caption'], 'cs_' . $name, (($pst = $nc_core->input->fetch_get_post('cs_' . $name)) ? $pst : nc_array_value($settings, $name)), 0, 'width:100%;  margin-bottom: 10px;');
            }
        }
        echo "</div></fieldset>";
    }
    echo "</div>";
}
echo nc_admin_js_resize();

echo "</form>";

echo "<script type='text/javascript'>
          nc_cs.changetype();
          nc_cs.changesubtype();
        </script>";
