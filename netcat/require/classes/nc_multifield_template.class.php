<?php

class nc_multifield_template {

    /** @var nc_multifield */
    private $multifield = null;
    private $template = array();

    /** @var array Названия колонок Multifield, которые подставляются в шаблоны записей */
    protected $record_field_names = array('ID', 'Field_ID', 'Message_ID', 'Priority', 'Name', 'Size', 'Path', 'Preview');

    /**
     * @param nc_multifield $multifield
     */
    public function __construct(nc_multifield $multifield) {
        $this->multifield = $multifield;
    }

    /**
     * @param $template
     * @return $this
     */
    public function set($template) {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function get_html() {
        return !empty($this->template) && isset($this->multifield->records[0])
            ? $this->template['prefix'] . $this->create_record_template() . $this->template['suffix']
            : '';
    }

    /**
     * @return string
     */
    private function create_record_template() {
        $records = array();
        $i = (int)nc_array_value($this->template, 'i', 1);
        foreach ($this->multifield->records as $record) {
            $records[] = str_replace('%i%', $i, $this->apply_record_tpl($record));
            $i++;
        }
        return join($this->template['divider'], $records);
    }

    /**
     * @param $record
     * @return mixed
     */
    private function apply_record_tpl(nc_multifield_file $record) {
        $record_tpl = $this->template['record'];
        foreach ($this->record_field_names as $field_name) {
            $record_tpl = str_replace("%$field_name%", $record->{$field_name}, $record_tpl);
        }
        return $record_tpl;
    }

    /**
     * @return string
     */
    public function get_form() {
        $html = $this->multifield->desc ? "<div class='nc-field-caption'>{$this->multifield->desc}:</div>" : "<div>{$this->multifield->name}:</div>";
        $html .= "<div class='nc-upload nc-upload-multifile'" .
                 " data-field-name='{$this->multifield->name}'" .
                 " data-max-files='{$this->multifield->settings->max}'";

        if ($this->multifield->settings->use_name) {
            $html .= " data-custom-name='1'" .
                     " data-custom-name-caption='" . htmlspecialchars(strip_tags($this->multifield->settings->custom_name), ENT_QUOTES) . "'";
        }

        $html .= ">";
        $html .= "<div class='nc-upload-new-files' style='display: none'></div>";
        $html .= "<div class='nc-upload-files'>";
        $html .= $this->get_edit_form();
        $html .= "</div>";
        $html .= $this->get_setting_html('use_name');
        $html .= $this->get_setting_html('path');
        $html .= $this->get_setting_html('use_preview');
        $html .= $this->get_img_settings_html('preview');
        $html .= $this->get_img_settings_html('resize');
        $html .= $this->get_crop_settings_html();
        $html .= $this->get_crop_settings_html('preview');
        $html .= $this->get_setting_html('min');
        $html .= $this->get_setting_html('max');
        $html .= "<input type='hidden' name='settings_{$this->multifield->name}_hash' value='" . $this->multifield->settings->get_setting_hash() . "'/>";
        $html .= "<input class='nc-upload-input' type='file' name='f_{$this->multifield->name}_file[]' multiple />";
        $html .= "<script>window.\$nc && \$nc(document).trigger('apply-upload');</script>";
        $html .= "</div>";

        return $html;
    }

    /**
     * @return null|string
     */
    private function get_edit_form() {
        $result = null;

        if (isset($this->multifield->records[0])) {
            $nc_core = nc_core::get_object();
            $field_name = $this->multifield->name;
            $has_custom_name = $this->multifield->settings->use_name;
            $custom_name_caption = htmlspecialchars(strip_tags($this->multifield->settings->custom_name), ENT_QUOTES);

            foreach ($this->multifield->records as $record) {
                $file_name = $this->get_file_name($record->Path);

                $file_size_string = nc_bytes2size($record->Size);
                $file_type = nc_file_mime_type($nc_core->DOCUMENT_ROOT . $record->Path);

                // Обработка нажатия на «удалить файл» указана явно в HTML для случая,
                // когда скрипт работы с файловыми полями (jquery.upload.js) не подключён
                $block_id = 'nc_upload_file_' . $field_name . '_' . $record->ID;
                $delete_input_id = $block_id . '_delete';
                $delete_js = "document.getElementById('$delete_input_id').value=1;" .
                             "document.getElementById('$block_id').style.display='none';" .
                             "return false;";

                $result .= "<div class='nc-upload-file' data-type='$file_type' id='$block_id'>" .
                    "<div class='nc-upload-file-info'>" .
                    "<a class='nc-upload-file-name' href='{$record->Path}?id={$record->ID}' target='_blank' tabindex='-1'" .
                    " title='" . htmlspecialchars("$file_name ($file_size_string)", ENT_QUOTES) . "'>" .
                    htmlspecialchars($file_name) .
                    "</a> <span class='nc-upload-file-size'>$file_size_string</span> " .
                    "<a href='#' class='nc-upload-file-remove' onClick='". htmlspecialchars($delete_js, ENT_QUOTES) . "'" .
                    " title='" . NETCAT_MODERATION_FILES_DELETE . "' tabindex='-1'>×</a>" .
                    "</div>" .
                    ($has_custom_name
                        ? "<div class='nc-upload-file-custom-name'>" .
                            "<input type='text' name='multifile_name[$field_name][]'" .
                            " value='" . htmlspecialchars($record->Name, ENT_QUOTES) . "'" .
                            " placeholder='$custom_name_caption'></div>"
                        : ""
                    ) .
                    "<input type='hidden' name='multifile_delete[$field_name][]' value='0'" .
                    " id='$delete_input_id' class='nc-upload-file-remove-hidden'/>" .
                    "<input type='hidden' name='multifile_id[$field_name][]' value='{$record->ID}'>" .
                    "<input type='hidden' name='multifile_upload_index[$field_name][]' value='-1'>" .
                    "</div>";
            }
        }
        return $result;
    }

    /**
     * @param $type
     * @return string
     */
    private function get_setting_html($type) {
        return "<input type='hidden' name='settings_{$this->multifield->name}[$type]' " . "
                value='" . htmlspecialchars($this->multifield->settings->{$type}, ENT_QUOTES) . "' />";
    }

    /**
     * @param $path
     * @return mixed
     */
    private function get_file_name($path) {
        $file_name = explode('/', $path);
        return $file_name[count($file_name) - 1];
    }

    /**
     * @param $type
     * @return string
     */
    private function get_img_settings_html($type) {
        return $this->get_setting_html($type . '_width') .
               $this->get_setting_html($type . '_height') .
               $this->get_setting_html($type . '_mode');
    }

    /**
     * @param string $type
     * @return string
     */
    private function get_crop_settings_html($type = '') {
        $type .= $type ? '_' : '';

        return $this->get_setting_html($type . 'crop_x0') .
               $this->get_setting_html($type . 'crop_y0') .
               $this->get_setting_html($type . 'crop_x1') .
               $this->get_setting_html($type . 'crop_y1') .
               $this->get_setting_html($type . 'crop_mode') .
               $this->get_setting_html($type . 'crop_width') .
               $this->get_setting_html($type . 'crop_height') .
               $this->get_setting_html($type . 'crop_ignore_width') .
               $this->get_setting_html($type . 'crop_ignore_height');
    }

    /**
     * @param $name
     * @return bool
     */
    public function __get($name) {
        return isset($this->$name) ? $this->$name : false;
    }

}