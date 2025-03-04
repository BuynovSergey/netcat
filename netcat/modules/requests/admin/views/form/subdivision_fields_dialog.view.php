<?php if (!class_exists('nc_core')) { die; } ?>

 <div class="nc-modal-dialog" data-width="840" data-height="auto">
     <div class="nc-modal-dialog-header">
         <h2><?= NETCAT_MODULE_REQUESTS_FORM_SETTINGS_FIELDS_HEADER; ?></h2>
     </div>
     <div class="nc-modal-dialog-body">
         <form class="nc-form nc--vertical" action="<?= $current_url; ?>" method="POST">
             <input type="hidden" name="controller" value="form">
             <input type="hidden" name="action" value="save_settings">
             <input type="hidden" name="infoblock_id" value="<?= $infoblock_id; ?>">
             <input type="hidden" name="form_type" value="<?= $form_type; ?>">

             <table class="nc-table nc--wide nc--hovered nc--bordered">
             	 <thead>
             	 	<tr>
                        <th><?= NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_NAME; ?></th>
                        <th><?= NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_LABEL; ?></th>
                        <th><?= NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_PLACEHOLDER; ?></th>
             	 		<th class="nc-text-center"><?= NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_VISIBILITY; ?></th>
             	 		<th class="nc-text-center"><?= NETCAT_MODULE_REQUESTS_FORM_LABEL_FIELD_REQUIRED; ?></th>
             	 	</tr>
             	 </thead>
             	 <tbody>

                     <?
                     /** @var array $saved_field_properties */
                     /** @var array $selectable_fields */
                     /** @var array $enabled_fields */
                     /** @var bool $has_item_variants */
                     foreach ($selectable_fields as $field_name => $field_properties) {
                         if ($field_name === 'Item_VariantName' && !$has_item_variants) {
                             echo "<tr class='nc--hide'><td colspan='5'><input type='hidden' name='settings[Subdivision_VisibleFields][]' value='$field_name'></td></tr>";
                             continue;
                         }

                         if ($field_name === 'Item_VariantName' || $field_properties['not_null'] || in_array($field_name, $enabled_fields, true)) {
                             echo '<tr class="nc--green">';
                         } else {
                             echo '<tr>';
                         }

                         // Оригинальная подпись к полю
                         echo "<td>$field_properties[description]</td>";

                         if (isset($saved_field_properties[$field_name]) && $saved_field_properties[$field_name]['description']) {
                             $description = $saved_field_properties[$field_name]['description'];
                         } else {
                             $description = $field_properties['description'];
                         }

                         // Пользовательская подпись к полю
                         echo "<td><input type='text' name='settings[Subdivision_FieldProperties][$field_name][description]' value='$description'></td>";

                         if (isset($saved_field_properties[$field_name]) && $saved_field_properties[$field_name]['placeholder']) {
                             $placeholder = $saved_field_properties[$field_name]['placeholder'];
                         } else {
                             $placeholder = $field_properties['extension'];
                         }

                         // Пользовательский placeholder
                         echo "<td><input type='text' name='settings[Subdivision_FieldProperties][$field_name][placeholder]' value='$placeholder'></td>";

                         echo '<td class="nc-text-center">';
                         // Если в компоненте поле отмечено обязательным для заполнения - запрещаем убирать его из формы
                         if ($field_name === 'Item_VariantName' || $field_properties['not_null']) {
                             echo '<input type="checkbox" checked disabled>' .
                                  '<input type="hidden" name="settings[Subdivision_VisibleFields][]" value="' . $field_name . '">';
                         } else {
                             echo '<input type="checkbox" name="settings[Subdivision_VisibleFields][]"' .
                                  ' value="' . $field_name . '"' .
                                  (in_array($field_name, $enabled_fields, true) ? ' checked' : '') .
                                  '>';
                         }
                         echo '</td>';

                         echo '<td class="nc-text-center">';
                         // Если в компоненте поле отмечено обязательным для заполнения - запрещаем менять это поведение
                         if ($field_properties['not_null']) {
                             echo "<input type='hidden' name='settings[Subdivision_FieldProperties][$field_name][required]' value='1'>" .
                                  '<input type="checkbox" checked disabled>';
                         } else {
                             $is_field_required = isset($saved_field_properties[$field_name]) && $saved_field_properties[$field_name]['required'];
                             echo "<input type='hidden' name='settings[Subdivision_FieldProperties][$field_name][required]' value='0'>";
                             echo "<input type='checkbox' name='settings[Subdivision_FieldProperties][$field_name][required]' value='1'" .
                                  (isset($saved_field_properties[$field_name]) && $saved_field_properties[$field_name]['required'] ? ' checked' : '') .
                                  '>';
                         }
                         echo '</td>';
                         echo '</tr>';
                     }
                     ?>
             	 </tbody>
             </table>

             <div class="nc-hint">
                 <?= NETCAT_MODULE_REQUESTS_FORM_SUBDIVISION_SYNC_HINT; ?>
             </div>

         </form>
     </div>
     <div class="nc-modal-dialog-footer">
         <button data-action="submit"><?= NETCAT_REMIND_SAVE_SAVE; ?></button>
         <button data-action="close"><?= CONTROL_BUTTON_CANCEL; ?></button>
     </div>

 </div>
