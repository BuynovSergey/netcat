<?php

class nc_image_controller extends nc_ui_controller {
    protected $icon_provider = null;
    protected $tmp_icons_folder_path = null;

    public function __construct($view_path = null) {
        parent::__construct($view_path);
        $this->icon_provider = new nc_image_provider_icon();
        $this->tmp_icons_folder_path = $this->nc_core->TMP_FOLDER . 'tmp_icons';
    }

    protected function action_index() {
        $this->remove_unnecessary_tmp_icons();

        // Данные текущей иконки
        $library = $this->input->fetch_post_get('library');
        $icon = $this->input->fetch_post_get('icon');
        $color = $this->input->fetch_post_get('color');

        return $this->view('image/index')
            ->with('libraries', $this->icon_provider->get_libraries())
            ->with('current_library', $library)
            ->with('current_icon', $icon)
            ->with('current_color', $color);
    }

    protected function action_save() {
        // Данные сохраняемой иконки
        $library = $this->input->fetch_post_get('library');
        $icon = $this->input->fetch_post_get('icon');
        $color = $this->input->fetch_post_get('color');

        // Данные куда положить (если данные есть - кладем в объект компонента в поле, иначе - во временную папку)
        $class_id = (int)$this->input->fetch_post_get('class_id');
        $message_id = (int)$this->input->fetch_post_get('message_id');
        $field_id = (int)$this->input->fetch_post_get('field_id');
        $save_to_tmp_folder = true;
        if ($class_id && $message_id && $field_id) {
            $save_to_tmp_folder = false;
        }

        // Проверим данные
        if (!in_array($library, $this->icon_provider->get_libraries_info(true))) {
            echo 'library with given keyword not found';
            exit;
        }
        $icons_data = $this->icon_provider->get_library($library);
        if (!array_key_exists($icon, $icons_data)) {
            echo 'icon with given keyword not found';
            exit;
        }

        // Сохраним временную иконку
        $tmp_icon_absolute_path = $this->icon_provider->save($this->tmp_icons_folder_path, $library, $icon, $color);
        $tmp_icon_http_path = str_replace($this->nc_core->DOCUMENT_ROOT, '', $tmp_icon_absolute_path);
        if (!$save_to_tmp_folder) {
            $result = $this->nc_core->files->field_save_file($class_id, $field_id, $message_id, $tmp_icon_http_path);
            $tmp_icon_http_path = nc_array_value($result, 'download_path');
        }

        exit(nc_array_json(array(
            'path' => $tmp_icon_http_path,
            'name' => pathinfo($tmp_icon_http_path, PATHINFO_BASENAME)
        )));
    }

    /**
     * Удаляет ненужные больше временные иконки.
     */
    protected function remove_unnecessary_tmp_icons() {
        $icon_paths = glob($this->tmp_icons_folder_path . '/*.svg');
        if (!$icon_paths) {
            return;
        }
        $day_in_seconds = 60 * 60 * 24;
        foreach ($icon_paths as $icon_path) {
            $icon_modify_time = filemtime($icon_path);
            if (time() - $icon_modify_time <= $day_in_seconds) {
                continue;
            }
            unlink($icon_path);
        }
    }
}
