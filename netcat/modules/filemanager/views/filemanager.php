<?php
if (!class_exists('nc_core')) {
    die;
}
$nc_core = nc_core::get_object();
?>
<script src='<?= nc_add_revision_to_url((isset($self_folder) ? $self_folder : '') . 'filemanager.js') ?>' type='text/javascript'></script>


<div class='block_manager' style="margin-right:15px">
	<? if(count($breadcrumbs)>1): ?>
	<div class="nc-padding-10">
		<? foreach ($breadcrumbs as $i => $row): ?>
            <? $row['href'] = htmlspecialchars($row['link'], ENT_QUOTES); ?>
			<? if($i+1 == count($breadcrumbs)): ?>
				<?=$row['title'] ?>
			<? else: ?>
				<a href="<?= $row['href'] ?>"><?=$row['title'] ?></a> /
			<? endif ?>
		<? endforeach ?>
		&nbsp;<a href="#" onclick="nc_filemanagerObj.show_link_panel('<?= trim($dir, '/') ?>', 1); return false;">
			<i class="nc-icon nc--hovered nc--mod-linkmanager"></i>
		</a>
	</div>
	<br>
	<? endif ?>

	<table class='nc-table nc--wide nc--striped nc--hovered'>

		<? if($parent_is_writable): ?>
		<tr class='nc--blue'>
			<td colspan="8">
			<form method='post' class='nc-form nc--horizontal' action='admin.php' enctype='multipart/form-data' id='FileManagerUpload'>
				<div class='nc-form-row'>
					<label><?=NETCAT_MODULE_FILEMANAGER_ADMIN_UPLOAD_FILE ?></label>
					<input type='file' name='new_file' size='50'/>
				</div>
				<div class='nc-form-row'>
					<label><?=NETCAT_MODULE_FILEMANAGER_ADMIN_CREATE_DIR ?></label>
					<input type='text' name='new_dir' size='50'/>
				</div>
				<input type='hidden' name='dir' value='<?=$dir ?>'>
				<input type='hidden' name='phase' value='21'>
				<?= $nc_core->token->get_input() ?>
			</form>
			</td>
		</tr>
		<? endif ?>

		<tr>
			<th class='tab_header'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_DIR ?></th>
			<th class='nc--compact nc-text-center'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_SIZE ?></th>
			<th class='nc--compact nc-text-center'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PERMISSION ?></th>
			<th class='nc--compact nc-text-center' colspan='5'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_ACTION ?></th>
		</tr>

        <? foreach ($files as $i => $file): ?>
            <? $file['href'] = htmlspecialchars($file['link'], ENT_QUOTES); ?>
            <? $file['element_id'] = 'nc_fm_' . rawurlencode($file['name']); ?>
		<tr>
			<td>
				<a href="<?= $file['href'] ?>" class="nc--blocked <?= !empty($file['link']) ? 'nc-text-darken' : 'nc-text-grey' ?>">
					<i class='nc-icon <?=$file['icon'] ?>'></i>
					<span id='<?= $file['element_id'] ?>'><?=$file['name'] ?></span>
				</a>
			</td>
			<td>
				<? if (!empty($file['size'])): ?>
					<span class="nc-label nc--blue"><?=nc_bytes2size($file['size']) ?></span>
				<? endif ?>
			</td>
			<td>
				<? if (!empty($file['perm'])): ?>
					<span class="nc-label nc--light nc--blocked" id="<?= $file['element_id'] ?>_perm"><?= $file['perm'] ?></span>
				<? endif ?>
			</td>
			<? foreach (array('edit', 'download', 'copy_link', 'settings', 'delete') as $k): ?>
				<? $act = isset($file['actions'][$k]) ? $file['actions'][$k] : array() ?>
				<td class="nc--compact">
					<? if ($act && $module_permissions[$k]): ?>
                        <? $act['href'] = !empty($act['link']) ? htmlspecialchars($act['link'], ENT_QUOTES) : 'javascript:void(0)'; ?>
                        <?= nc_array_value($act, 'prefix') ?>
                        <a id="<?= $file['element_id'] ?>_<?= $k ?>"
                           title="<?= htmlspecialchars($act['title'], ENT_QUOTES) ?>"
                           href="<?= $act['href'] ?>"
                           <?= empty($act['click']) ? '' : 'onclick="' . htmlspecialchars($act['click']) . '"' ?>>
                               <i class="nc-icon <?=$act['icon'] ?> nc--hovered"></i>
                        </a>
                        <?= nc_array_value($act, 'suffix') ?>
				    <? endif ?>
				</td>
			<? endforeach ?>
		</tr>
		<? endforeach ?>
		
		<tr>
			<td class="nc-text-grey">
				<?=NETCAT_MODULE_FILEMANAGER_ADMIN_TOTAL ?>
				<span class="nc-label"><?=$dir_count ?> <?=$fm->format_name("folder", $dir_count) ?></span>
				<span class="nc-label"><?=$file_count ?> <?=$fm->format_name("file", $file_count) ?></span>
			</td>
			<td>
				<span class="nc-label"><?=nc_bytes2size($total_size) ?></span>
			</td>
			<td colspan="6"></td>
		</tr>

	</table>

</div>
<br>
<br>

<? /* Modal: Copy link */ ?>
<div id='nc_filemanager_link_panel' style='display:none; padding-right:25px'>
	<div id='nc_filemanager_link_panel_body' class='nc-form'>
		<br>
		<input type="text" id='nc_filemanager_link_absolute' class="nc--blocked" onfocus='this.select()'>
		<input type="text" id='nc_filemanager_link_global' class="nc--blocked" onfocus='this.select()'>
		<input type="text" id='nc_filemanager_link_server' class="nc--blocked" onfocus='this.select()'>
		<br><br>
	</div>
	<div class='nc_admin_form_buttons'>
		<button type='button' id='nc_filemanager_panel_close' class='nc-btn nc--left' onclick='$nc.modal.close()'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_CLOSE ?></button>
	</div>
</div>

<? /* Modal: Settings */ ?>
<div id='nc_filemanager_panel' style='display:none; padding-right:15px'>
	<div id='nc_filemanager_panel_header'>
		<div><h2><?= NETCAT_MODULE_FILEMANAGER_ADMIN_SETTINGS ?></h2></div>
	</div>
	<div id='nc_filemanager_panel_body' class='nc-form'>
		<br>
	    <legend><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_OWNER ?></legend>
	    <input type='checkbox' id='nc_filemanager_panel_1r' disabled/> <label for='nc_filemanager_panel_1r'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_READ ?></label>
	    <input type='checkbox' id='nc_filemanager_panel_1w' disabled/> <label for='nc_filemanager_panel_1w'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_WRITE ?></label>
	    <input type='checkbox' id='nc_filemanager_panel_1x' disabled/> <label for='nc_filemanager_panel_1x'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_EXECUTE ?></label>
	    <hr>

	    <legend><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_GROUP ?></legend>
	    <input type='checkbox' id='nc_filemanager_panel_2r' disabled/> <label for='nc_filemanager_panel_2r'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_READ ?></label>
	    <input type='checkbox' id='nc_filemanager_panel_2w' disabled/> <label for='nc_filemanager_panel_2w'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_WRITE ?></label>
	    <input type='checkbox' id='nc_filemanager_panel_2x' disabled/> <label for='nc_filemanager_panel_2x'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_EXECUTE ?></label>
	    <hr>

	    <legend><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_PUBLIC ?></legend>
	    <input type='checkbox' id='nc_filemanager_panel_3r' disabled/> <label for='nc_filemanager_panel_3r'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_READ ?></label>
	    <input type='checkbox' id='nc_filemanager_panel_3w' disabled/> <label for='nc_filemanager_panel_3w'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_WRITE ?></label>
	    <input type='checkbox' id='nc_filemanager_panel_3x' disabled/> <label for='nc_filemanager_panel_3x'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_EXECUTE ?></label>
	    <hr>

	    <legend><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_RENAME ?></legend>
	    <input type='text' id='nc_filemanager_panel_name' name='nc_filemanager_panel_name' style='width:276px' disabled/>
	    <input type='hidden' id='nc_filemanager_panel_path' name='nc_filemanager_panel_path'/>
	    <input type='hidden' id='nc_token' name='nc_token' value="<?= $nc_core->token->get() ?>"/>
	    <div id='nc_filemanager_panel_loader' style='background:url(<?=$this->self_folder ?>images/loader.gif) no-repeat'></div>
	</div>
	<div class='nc_admin_form_buttons'>
		<button type='button' id='nc_filemanager_panel_save' class='nc-btn nc--blue nc--right' style="margin-right:16px" onclick='(window.frames[1]?window.frames[1]:window).nc_filemanagerObj.save_panel()' disabled><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_SAVE ?></button>
		<button type='button' id='nc_filemanager_panel_close' class='nc-btn nc--left' onclick='$nc.modal.close()'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_CLOSE ?></button>
	</div>
	<br class='clear' />
	<script type='text/javascript'>
	nc_filemanagerObj = new nc_Filemanager({
		MODULE_PATH:'<?=$this->MODULE_PATH ?>',
		url_prefix: '<?=$this->url_prefix ?>',
		DOCUMENT_ROOT: '<?=$nc_core->DOCUMENT_ROOT ?>',
		HTTP_HOST: '<?=$nc_core->HTTP_HOST ?>'
	});
	</script>
</div>