<?php

if (!class_exists('nc_core')) {
    die;
}

?>
<div>
    <div><?= CONTROL_CONTENT_SUBCLASS_CLASSNAME ?>:</div>
    <div><?= nc_admin_input_simple('data[Sub_Class_Name]', $infoblock_data["Sub_Class_Name"], 50, '', "maxlength='255'") ?></div>
</div>
<br>
<div>
    <div><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD?>:</div>
    <div><?= nc_admin_input_simple('data[EnglishName]', $infoblock_data["EnglishName"], 50, '', "maxlength='255' data-type='transliterate' data-from='data[Sub_Class_Name]' data-is-url='yes' ") ?></div>
</div>
<br>
<div>
    <div><?=CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW_NUM?></div>
    <div><?=nc_admin_input_simple('data[RecordsPerPage]', $infoblock_data['RecordsPerPage'], 5, '', "maxlength='32'")?></div>
</div>
<br>
<div>
    <div><?=CONTROL_CLASS_CLASS_MIN_RECORDS?></div>
    <div><?=nc_admin_input_simple('data[MinRecordsInInfoblock]', $infoblock_data['MinRecordsInInfoblock'], 5, '', "maxlength='11'")?></div>
</div>
<br>
<div>
    <div><?=CONTROL_CLASS_CLASS_MAX_RECORDS?></div>
    <div><?=nc_admin_input_simple('data[MaxRecordsInInfoblock]', $infoblock_data['MaxRecordsInInfoblock'], 5, '', "maxlength='11'")?></div>
</div>
<?php if ($isSupervisor): ?>
    <br>
    <div>
        <div>
            <input name='data[ncDisallowMoveAndDelete]' type='hidden' value='0' />
            <input id='chkDisallowMoveAndDelete' type='checkbox' name='data[ncDisallowMoveAndDelete]' value='1' <?=($infoblock_data["ncDisallowMoveAndDelete"] == 1 ? "checked='checked' " : "") ?>/>
            <label for='chkDisallowMoveAndDelete'><?=CONTROL_CONTENT_SUB_CLASS_FUNCS_DISALLOW_MOVE_AND_DELETE ?></label>
        </div>    
    </div>
    <br>
<?php endif; ?>
