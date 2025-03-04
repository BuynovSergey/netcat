<div class="nc_admin_fieldset_head"><?php echo TOOLS_CSV_MAPPING_HEADER; ?></div>
<?php

$form = $ui->form($action_url . 'import_finish')->multipart()->horizontal()->id('import');
if (empty($data['error'])) {
    $form->add()->input('hidden', 'file', $data['file']);
    $form->add()->input('hidden', 'data[site_id]', $data['site_id']);
    $form->add()->input('hidden', 'data[subdivision_id]', $data['subdivision_id']);
    $form->add()->input('hidden', 'data[subclass_id]', $data['subclass_id']);
    foreach ($data['csv_settings'] as $k => $value) {
        $form->add()->input('hidden', 'data[csv]['.$k.']', addslashes($value));
    }
    
    
    $form->add_row("<b>".TOOLS_CSV_COMPONENT_FIELD."</b>")->label("<b>".TOOLS_CSV_FILE_FIELD."</b>")->style('font-size: 12px; color: #646464; display: block;');
    
    foreach ($data['fields'] as $key => $field) {
        $form->add_row($field)->select('data[fields]['.$key.']', $data['csv_head'], $key);
    }
    echo $form;
} else {
    echo $ui->alert->error($data['error']);
}
?>