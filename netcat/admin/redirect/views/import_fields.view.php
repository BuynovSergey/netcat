<h3><?= TOOLS_CSV_MAPPING_HEADER ?></h3>

<?php

$form = $ui->form($action_url . 'import_finish')->multipart()->horizontal()->id('import');
if (empty($data['error'])) {
    $form->add()->input('hidden', 'file', $data['file']);
    $form->add()->input('hidden', 'data[group]', $data['group']);
    $form->add()->input('hidden', 'data[checked]', $data['checked']);
    foreach ($data['csv_settings'] as $k => $value) {
        $form->add()->input('hidden', 'data[csv]['.$k.']', addslashes($value));
    }
    $form->add_row()->div(nc_core('token')->get_input());
    $form->add_row("<b>".TOOLS_REDIRECT_FIELDS."</b>")->label("<b>".TOOLS_CSV_FILE_FIELD."</b>")->style('font-size: 12px; color: #646464; display: block;');


    foreach ($data['fields'] as $key => $field) {
        $form->add_row($field)->select('data[fields]['.$key.']', $data['csv_head'], $key);
    }
    echo $form;
} else {
    echo $ui->alert->error($data['error']);
}
