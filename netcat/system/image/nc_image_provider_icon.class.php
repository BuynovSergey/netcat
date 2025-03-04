<?php

class nc_image_provider_icon extends nc_image_provider {
    protected $library_dir = null;

    public function __construct() {
        parent::__construct();
        $this->library_dir = $this->nc_core->DOCUMENT_ROOT . $this->nc_core->HTTP_TEMPLATE_PATH . 'icon';
    }

    public function save($dir, $library, $icon, $color) {
        // Есть ли такая библиотека?
        if (!in_array($library, $this->get_libraries_info(true))) {
            return null;
        }
        // Есть ли такая иконка?
        $icons_data = $this->get_library($library);
        if (!array_key_exists($icon, $icons_data)) {
            return null;
        }
        // Сохраним иконку по адресу
        $icon_data = $icons_data[$icon];
        $icon_absolute_path = $icon_data['path'];
        if (!file_exists($dir)) {
            mkdir($dir, nc_core::get_object()->DIRCHMOD, true);
        }
        $icon_path = $dir . '/' . $icon . '.' . $icon_data['extension'];
        if (!copy($icon_absolute_path, $icon_path)) {
            return null;
        }
        $this->svg_file_inject_data($icon_path, $library, $icon, $color);
        return $icon_path;
    }

    public function get_libraries() {
        $data = array();
        $libraries_info = $this->get_libraries_info();
        foreach ($libraries_info as $library_keyword => $library_name) {
            $data[$library_keyword] = array(
                'name' => $library_name,
                'icons' => $this->get_library($library_keyword)
            );
        }
        return $data;
    }

    public function get_libraries_info($only_keywords = false) {
        $result = array();
        foreach (glob($this->library_dir . '/*', GLOB_ONLYDIR) as $dir_path) {
            $library_keyword = pathinfo($dir_path, PATHINFO_FILENAME);
            if ($this->is_library_disabled($library_keyword)) {
                continue;
            }
            $result[$library_keyword] = $this->get_library_name_by_keyword($library_keyword);
        }
        if ($only_keywords) {
            $result = array_keys($result);
        }
        return $result;
    }

    public function get_library($library_name) {
        $icons = array();
        $files_paths = glob($this->library_dir . '/' . $library_name . '/*.svg');
        foreach ($files_paths as $index => $file_path) {
            $file_info = pathinfo($file_path);
            $keyword = $file_info['filename'];
            $icons[$keyword] = array(
                'keyword' => $keyword,
                'path' => $file_path,
                'extension' => $file_info['extension'],
                'http' => nc_get_http_folder($file_info['dirname']) . $file_info['basename'],
            );
        }
        return $icons;
    }

    protected function svg_file_inject_data($icon_file_path, $library, $icon, $color) {
        $xml = simplexml_load_file($icon_file_path);
        $xml['data-library'] = $library;
        $xml['data-icon'] = $icon;
        $xml['data-color'] = $color;
        $xml['fill'] = $color;
        $xml->saveXML($icon_file_path);
    }

    protected function get_library_name_by_keyword($keyword) {
        $keyword = explode('_', $keyword);
        foreach ($keyword as $i => $part) {
            $keyword[$i] = ucfirst($part);
        }
        return implode(' ', $keyword);
    }

    private function get_disabled_library_keywords() {
        $file_path = $this->library_dir . '/disabled';
        if (!file_exists($file_path) || is_dir($file_path)) {
            return array();
        }
        $result = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $result;
    }

    private function is_library_disabled($library) {
        /** @var array $disabled_library_keywords */
        static $disabled_library_keywords = null;
        if (!$disabled_library_keywords) {
            $disabled_library_keywords = $this->get_disabled_library_keywords();
        }
        return in_array($library, $disabled_library_keywords);
    }

    public function parse_icon_info($icon_file_path) {
        if (!file_exists($icon_file_path)) {
            return null;
        }
        $xml = @simplexml_load_file($icon_file_path);
        if (!$xml) {
            return null;
        }
        return array(
            'library' => (string)$xml['data-library'],
            'icon' => (string)$xml['data-icon'],
            'color' => (string)$xml['data-color']
        );
    }
}