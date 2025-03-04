<?php

/**
 * Класс для сбора информации о необходимых библиотеках, JS и CSS для nc_tpl_mixin_cache
 */
class nc_tpl_mixin_cache_collector extends nc_tpl_asset_collector {

    protected $requested_assets = array(); // тут собираем информацию о необходимых библиотеках в простой массив
    protected $requested_assets_once = array();

    /** @var array свойства элемента, к которому применяются миксины */
    protected $settings;
    /** @var array [ scope => [ block_selector, list_selector ], ... ] */
    protected $scope_selectors = array();

    /** @var array [ собственное свойство => ключ в json-файле ] */
    static protected $properties_for_json = array(
        'requested_assets_once' => 'assets_once',
        'requested_assets' => 'assets',
        'meta' => 'meta',
    );

    /* ↓↓↓  методы, временно нужные для установки патчей в Netcat 6.1  ↓↓↓ */
    public function set_site_meta($site_id, $key, $value) {}
    public function set_subdivision_meta($subdivision_id, $key, $value) {}
    public function set_block_meta($block_id, $key, $value) {}
    /* ↑↑↑  удалить после выхода следующей после Netcat 6.1 версии     ↑↑↑ */

    // --- Методы nc_tpl_asset_collector ---

    /**
     * @param string $asset_keyword_and_version
     * @param array $options
     */
    public function require_asset($asset_keyword_and_version, array $options = array()) {
        $this->requested_assets[] = array($asset_keyword_and_version, $options);
    }

    /**
     * @param array $assets
     */
    public function require_assets(array $assets) {
        foreach ($assets as $key => $value) {
            // только ключевое слово и версия
            if (is_int($key) && is_string($value)) {
                $this->requested_assets[] = array($value, array());
            } else if (is_array($value)) {
                $this->requested_assets[] = array($key, $value);
            }
        }
    }

    /**
     * @param string $asset_keyword
     * @param array $options
     */
    public function require_asset_once($asset_keyword, array $options = array()) {
        foreach ($this->requested_assets_once as $asset) {
            if ($asset[0] === $asset_keyword) {
                return;
            }
        }
        $this->requested_assets_once[] = array($asset_keyword, $options);
    }

    // --- Собственные методы класса ---

    /**
     * @param array $settings
     * @param array $scopes
     * @return $this
     */
    public function set_settings(array $settings, array $scopes) {
        $this->settings = $settings;
        $this->scope_selectors = $scopes;
        return $this;
    }

    /**
     * @return $this
     */
    protected function assemble() {
        foreach ($this->scope_selectors as $scope => $selectors) {
            nc_tpl_mixin_assembler::assemble($selectors[0], $selectors[1], $scope, $this->settings, $this);
        }
        return $this;
    }

    /**
     * @param int $site_id
     * @param string $entity одна из констант nc_tpl_mixin_cache::FOR_*
     * @param int $id
     * @param int|null $priority
     *      число — добавить приоритет как префикс к имени файла (для сортировки файлов стилей блоков)
     *      null — не добавлять приоритет к имени файла
     * @return bool
     */
    public function save($site_id, $entity, $id, $priority) {
        $this->assemble();

        $this->save_css($site_id, $entity, $id, $priority);
        $this->save_js($site_id, $entity, $id);
        $this->save_json($site_id, $entity, $id);

        return true;
    }

    /**
     * @param int $site_id
     * @param string $entity
     * @param int $id
     * @param int|null $priority
     */
    protected function save_css($site_id, $entity, $id, $priority) {
        if (!$this->styles) {
            return;
        }

        $styles = trim(implode("\n", $this->styles));
        if ($styles !== '') {
            $css_folder = nc_tpl_mixin_cache::get_file_folder($site_id, 'css');
            $css_file_name = nc_tpl_mixin_cache::get_file_name($entity, $id, $priority);
            if (!$this->create_folder($css_folder)) {
                return;
            }
            // комментарии для разграничения стилей разных элементов позволят
            // изымать/заменять эти стили в режиме редактирования без перезагрузки
            $styles_marker = "$entity$id";
            $styles = "/* <$styles_marker> */\n$styles\n/* </$styles_marker> */\n";
            file_put_contents("$css_folder/$css_file_name.css", $styles);
            chmod("$css_folder/$css_file_name.css", nc_core::get_object()->FILECHMOD);
        }
    }

    /**
     * @param int $site_id
     * @param string $entity
     * @param int $id
     */
    protected function save_js($site_id, $entity, $id) {
        if (!$this->scripts_on_dom_loaded) {
            return;
        }

        $js_folder = nc_tpl_mixin_cache::get_file_folder($site_id, 'js');
        $js_file_name = nc_tpl_mixin_cache::get_file_name($entity, $id, null);
        if (!$this->create_folder($js_folder)) {
            return;
        }

        file_put_contents("$js_folder/$js_file_name.js", implode("\n", $this->scripts_on_dom_loaded));
        chmod("$js_folder/$js_file_name.js", nc_core::get_object()->FILECHMOD);
    }

    /**
     * @param int $site_id
     * @param string $entity
     * @param int $id
     */
    protected function save_json($site_id, $entity, $id) {
        $data = array();

        foreach (self::$properties_for_json as $property => $json_key) {
            if (!empty($this->$property)) {
                $data[$json_key] = $this->$property;
            }
        }

        if (!$data) {
            return;
        }

        $js_folder = nc_tpl_mixin_cache::get_file_folder($site_id, 'js');
        $js_file_name = nc_tpl_mixin_cache::get_file_name($entity, $id, null);
        if (!$this->create_folder($js_folder)) {
            return;
        }

        file_put_contents("$js_folder/$js_file_name.json", json_encode($data));
        chmod("$js_folder/$js_file_name.json", nc_core::get_object()->FILECHMOD);
    }

    /**
     * @param $folder
     * @return bool
     */
    protected function create_folder($folder) {
        if (is_dir($folder) || mkdir($folder, nc_core::get_object()->DIRCHMOD, true)) {
            return true;
        }
        trigger_error("Unable to create directory $folder", E_USER_WARNING);
        return false;
    }

}