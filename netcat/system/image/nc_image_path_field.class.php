<?php

class nc_image_path_field extends nc_image_path {

    protected static $counter = 0;

    /** @var int|null ID инфоблока объекта */
    protected $object_infoblock_id = null;
    /** @var int|null ID создателя объекта (MessageX.User_ID) */
    protected $object_user_id = null;

    /**
     * Проверка на возможность редактирования пользователем изображений
     * @return bool
     * @throws Exception
     */
    protected function can_user_edit_image() {
        if (!$this->object_infoblock_id) {
            return false;
        }
        $infoblock_data = nc_core::get_object()->sub_class->get_by_id($this->object_infoblock_id);
        return s_auth($infoblock_data, 'change', true, $this->object_user_id);
    }

    /**
     * @param $object_infoblock_id
     * @return $this
     */
    public function set_object_infoblock_id($object_infoblock_id) {
        $this->object_infoblock_id = $object_infoblock_id;
        return $this;
    }

    /**
     * @param $object_user_id
     * @return $this
     */
    public function set_object_user_id($object_user_id) {
        $this->object_user_id = $object_user_id;
        return $this;
    }

    /**
     * Генерирует форму редактирования изображения
     * @return string
     * @throws Exception
     */
    protected function get_editable_image_form($file_path) {
        $nc_core = nc_Core::get_object();
        $attributes = $this->tag_attributes;
        $attributes = (is_array($attributes)) ? $attributes : array();
        $component_id = (int)$this->entity;
        $field_id = (int)$this->field;
        $object_id = (int)$this->object;
        if (!$component_id || !$field_id || !$object_id) {
            return $this->get_img_tag();
        }
        $infoblock_id = $this->object_infoblock_id;
        if (!$infoblock_id) {
            return $this->get_img_tag();
        }
        $for_v4 = !$nc_core->sub_class->get_by_id($infoblock_id, 'File_Mode');
        $field = $nc_core->get_component($component_id)->get_field($field_id);
        if (!$field) {
            return $this->get_img_tag();
        }
        $field_name = $field['name'];
        $file_info = $nc_core->file_info->get_file_info($component_id, $object_id, $field_name, false);
        $is_file_present = !empty($file_info["url"]);
        $root_class = 'nc-editable-image-container';
        if (!$is_file_present) {
            $root_class .= ' nc--empty';
        }
        $root_id = "nc_editable_image_{$component_id}_{$field_name}_{$object_id}_" . (self::$counter++);
        $html = "<span class='$root_class' id='$root_id'>";
        $image_placeholder = $nc_core->ADMIN_PATH . 'skins/v5/img/transparent-100x100.png';
        $file_path = $is_file_present ? $this->get_path() : $image_placeholder;
        if ($nc_core->get_settings("InlineImageCropUse") == 1) {
            // нажатие на кнопку открывает диалог «Редактирование изображений»
            $attributes['src'] = $file_path;
            if (isset($attributes['class'])) {
                $attributes['class'] .= ' cropable' . ($is_file_present ? '' : ' nc--placeholder');
            } else {
                $attributes['class'] = 'cropable' . ($is_file_present ? '' : ' nc--placeholder');
            }
            $attributes['data-classid'] = $component_id;
            $attributes['data-messageid'] = $object_id;
            $attributes['data-fieldname'] = $field_name;
            $processed_attributes = nc_make_attribute_string_from_array($attributes, $for_v4);
            $html .= $processed_attributes['warning'];
            $html .= '<img ' . $processed_attributes['result'] . ' />';
        }
        else {
            // нажатие на кнопку должно открыть диалог выбора файла
            // если картинка не является обязательной, показываем тулбар с кнопкой удаления картинки
            if (!$field['not_null']) {
                $html .= "<span class='nc-editable-image-container-toolbar-bridge'></span>" .
                    "<ul class='nc6-toolbar nc-editable-image-container-toolbar'>" .
                    "<li class='nc--strike-diagonal nc-editable-image-remove'>".
                    "<i class='nc-icon-image' title='" . NETCAT_MODERATION_REMOVE_IMAGE . "'></i></li>" .
                    "</ul>";
            }
            $form_action = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'message.php';
            $html .= "<form action='$form_action' method='post' enctype='multipart/form-data' onsubmit='return false'>" .
                $nc_core->token->get_input() .
                "<input name='posting' type='hidden' value='1'>" .
                "<input name='partial' type='hidden' value='1'>" .
                "<input name='admin_mode' type='hidden' value='1'>" .
                "<input name='isNaked' type='hidden' value='1'>" .
                "<input name='sub' type='hidden' value='" . $nc_core->subdivision->get_current('Subdivision_ID') . "'>" .
                "<input name='cc' type='hidden' value='$infoblock_id'>" .
                "<input name='message' type='hidden' value='$object_id'>" .
                "<input name='f_KILL{$field['id']}' type='hidden' value=''>";

            $attributes['src'] = $file_path;
            $attributes['data-source'] = "$component_id:$object_id:$field_name";
            $processed_attributes = nc_make_attribute_string_from_array($attributes, $for_v4);
            $html .= $processed_attributes['warning'];
            $html .= '<img ' . $processed_attributes['result'] . ' />';
            $html .= "<span class='nc-editable-image-container-file-input'>" .
                "<input type='file' name='f_{$field_name}' accept='image/*'" .
                " title='" . NETCAT_MODERATION_REPLACE_IMAGE . "'>" .
                "</span>";

            $field_format = nc_field_parse_format($field['format'], NC_FIELDTYPE_FILE);
            $file_can_be_icon = $field_format['icon'];
            $file_absolute_path = $nc_core->DOCUMENT_ROOT . nc_file_path($component_id, $object_id, $field_name);
            $file_exists = $file_absolute_path && file_exists($file_absolute_path) && !is_dir($file_absolute_path);
            if ($file_can_be_icon) {
                $image_dialog_query = array();
                if ($file_exists) {
                    $provider = new nc_image_provider_icon();
                    $icon_info = $provider->parse_icon_info($file_absolute_path);
                    if ($icon_info) {
                        $image_dialog_query['library'] = $icon_info['library'];
                        $image_dialog_query['icon'] = $icon_info['icon'];
                        $image_dialog_query['color'] = $icon_info['color'];
                    }
                }
                $image_dialog_url = nc_controller_url('admin.image', 'index', $image_dialog_query);
                $icon_path_input_id = "f_{$field_name}_{$object_id}_tmp";
                $icon_buttons_path = $nc_core->ADMIN_PATH . 'skins/default/img';
                $nc = '$nc';
                $html .= <<<HTML
                <div class='image-type-selector'>
                    <img src="$icon_buttons_path/upload.svg" class="icon icon-upload"
                         onclick="
                                 $nc(this).parent()
                                        .siblings('.nc-editable-image-container-file-input')
                                        .find('input').trigger('click')
                        ">
                    <a href="$image_dialog_url"
                       onclick="
                               nc.load_dialog(this.href).set_options({
                                   image_dialog_input: $nc(this).siblings('input').eq(0),
                                   class_id: $component_id,
                                   message_id: $object_id,
                                   field_id: $field[id]
                               }); return false;">
                        <img src="$icon_buttons_path/choose.svg" class="icon icon-choose">
                    </a>
                    <input type="hidden" name="$icon_path_input_id" id="$icon_path_input_id">
                    <script>
                        $nc(function() {
                            $nc('#$icon_path_input_id').change(nc_editable_image_upload);
                        });
                    </script>
                </div>
HTML;
            }
            $html .= "</form>";
        }
        $html .= "</span>";
        $html .= "<script>nc_editable_image_init('#$root_id')</script>";
        return $html;
    }

}