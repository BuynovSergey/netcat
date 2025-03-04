<?php

/**
 * Класс для реализации поля типа "Файл"
 */
class nc_a2f_field_file extends nc_a2f_field {

    protected $upload, $filename, $filesize, $filepath, $filetype;

    protected $can_have_initial_value = false;

    public function get_subtypes() {
        return array('any', 'image', 'icon');
    }

    public function render_value_field($html = true) {
        $ret = '<div class="nc-upload"><div class="nc-upload-files">';

        // старый файл
        if ($this->value) {
            $file_size_string = nc_bytes2size($this->value['size']);

            $ret .= "<input type='hidden' name='" . $this->get_field_name('old') . "' value='" . $this->value['all'] . "' />" .
                "<div class='nc-upload-file' data-type='" . htmlspecialchars($this->value['type'], ENT_QUOTES) . "'>" .
                "<div class='nc-upload-file-info'>" .
                "<a class='nc-upload-file-name' href='" . htmlspecialchars($this->value['path'], ENT_QUOTES) . "' target='_blank' title='" .
                htmlspecialchars("{$this->value['name']} ($file_size_string)", ENT_QUOTES) . "'>" .
                htmlspecialchars($this->value['name'], ENT_QUOTES) . "</a> " .
                "<span class='nc-upload-file-size'>$file_size_string</span> " .
                "<a href='#' class='nc-upload-file-remove'>×</a></div>" .
                "<input id='kill" . $this->name . "' class='nc-upload-file-remove-hidden' type='hidden' name='" .
                $this->get_field_name('kill') . "' value='0' />" .
                "</div>";
        }

        $ret .= "</div><input class='nc-upload-input' name='" . $this->get_field_name() . "' type='file' />";
        $ret .= $this->get_additional_html_after_file_field();
        $ret .= "<script>\$nc(document).trigger(\"apply-upload\");</script>";
        $ret .= "</div>";

        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }

    protected function get_additional_html_after_file_field() {
        return '';
    }

    protected function get_displayed_default_value() {
        if (!$this->default_value) {
            return '';
        }

        $file_info = null;

        if (is_string($this->default_value)) {
            $file_info = $this->file_string_to_array($this->default_value);
        }

        if (is_array($this->default_value)) {
            $file_info = $this->default_value;
        }

        if ($file_info) {
            return "<a href='" . $file_info['path'] .
                "' target='_blank'>" .
                $file_info['name'] .
                "</a>";
        }


        return $this->default_value;
    }

    public function set_value($value) {
        $this->value = false;
        if (is_string($value) && $value) {
            $this->value = $this->file_string_to_array($value);
            $this->is_set = true;
        } else if (is_array($value) && isset($value['path']) && isset($value['name'])) {
            $this->value = $value;
            $this->is_set = true;
        }
        return 0;
    }

    public function get_value($as_string = false) {
        if ($as_string && is_array($this->value)) {
            $result = implode(':', array(
                $this->value['name'],
                $this->value['type'],
                $this->value['size'],
                $this->value['raw_path'],
            ));
        } else {
            if ($as_string) {
                $result = $this->value;
            } else {
                $result = nc_a2f_field_file_value::by_field_file($this);
            }
        }
        return $result;
    }

    public function get_value_raw() {
        if (empty($this->value)) {
            return null;
        }
        if (is_string($this->value)) {
            return $this->file_string_to_array($this->value);
        }
        return $this->value;
    }

    protected function file_string_to_array($value) {
        $result = array();
        list($filename, $filetype, $filesize, $filepath) = explode(':', $value);
        if (!$filepath) {
            return false;
        }
        $nc_core = nc_Core::get_object();
        $result_file_path = $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH . $filepath;
        $result['resultpath'] = $result_file_path;
        $result['raw_path'] = $filepath;
        $result['path'] = $result['resultpath'];
        $result['type'] = $filetype;
        $result['size'] = $filesize;
        $result['name'] = $filename;
        $result['all'] = $value;
        return $result;
    }

    public function save($value) {
        $nc_core = nc_Core::get_object();

        $array_name = $this->parent->get_array_name();

        if (is_array($value)) {
            if (!empty($value['old']) && !empty($value['kill'])) {
                list ($filename, $filetype, $filesize, $filepath) = explode(':', $value['old']);
                unlink($nc_core->FILES_FOLDER . $filepath);

                // удаляем превью
                $parent = $this->get_parent();
                $entity_type = $parent->get_entity_type();
                $entity_id = $parent->get_entity();

                if ($entity_type && $entity_id) {
                    nc_image_generator::remove_generated_images($entity_type, $this->name, $entity_id);
                }

                $this->value = $value['old'] = '';
            }
        } elseif ($value) {
            $this->set_value($value);
            $value = $this->value;
        }

        $no_new_file = false;
        if (empty($value['file'])) {
            if (!empty($_FILES[$array_name]['error'][$this->name])) {
                $no_new_file = true;
            }

            $tmp_name = $_FILES[$array_name]['tmp_name'][$this->name];
            $filetype = $_FILES[$array_name]['type'][$this->name];
            $filename = $_FILES[$array_name]['name'][$this->name];
        } else {
            $tmp_name = realpath($nc_core->DOCUMENT_ROOT . $value['file']);
            $filetype = $tmp_name ? nc_file_mime_type($tmp_name) : null;
            $filename = $tmp_name ? pathinfo($tmp_name, PATHINFO_BASENAME) : null;

            if (strpos($tmp_name, realpath($nc_core->TMP_FOLDER)) !== 0) {
                $no_new_file = true;
            }
        }

        if ($no_new_file || !file_exists($tmp_name)) {
            if (!empty($value['old'])) {
                $this->value = $value['old'];
            }
            return;
        }

        // nothing was changed
        if (!empty($value['old']) && empty($value['kill']) && !$filetype) {
            if ($value['old']) {
                $this->value = $value['old'];
            }
            return;
        }

        $folder = $nc_core->FILES_FOLDER . 'cs/';
        $put_file_name = nc_transliterate($filename);
        $put_file_name = nc_get_filename_for_original_fs($put_file_name, $folder, array());

        $nc_core->files->create_dir($folder);
        if (!isset($value['file'])) {
            move_uploaded_file($tmp_name, $folder . $put_file_name);
        } else {
            copy($tmp_name, $folder . $put_file_name);
        }

        if (strtolower(pathinfo($put_file_name, PATHINFO_EXTENSION)) === 'svg') {
            nc_sanitize_svg_file($folder . $put_file_name);
        }

        $filesize = filesize($folder . $put_file_name);
        if ($filesize) {
            $this->value = $filename . ':' . $filetype . ':' . $filesize . ':cs/' . $put_file_name;
        } else {
            $this->value = '';
        }

        $this->upload = true;
        $this->filename = $filename;
        $this->filetype = $filetype;
        $this->filesize = $filesize;
        $this->filepath = $folder . $put_file_name;
    }
}
