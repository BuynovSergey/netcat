<?php

class nc_tpl_mixin extends nc_tpl_mixin_record {

    const MAX_BLOCK_WIDTH = 9999;
    const SCOPE_MAIN_AREA = 'MainArea';
    const SCOPE_INDEX = 'Index';
    const SCOPE_INDEX_ITEM = 'IndexItem';

    static protected $mixin_instances = array();

    protected $properties = array(
        'type' => null,
        'keyword' => null,
        'path' => null,
        // from files
        'name' => null,
        'priority' => null,
        'is_disabled' => null,
        'block_styles' => null,
        'init_js' => null,
        'destruct_js' => null,
        'assets' => null,
        'block_settings' => null,
    );

    protected $properties_read_language = array(
        'name' => 'Name',
    );

    protected $properties_read = array(
        'priority' => 'Priority',
        'is_disabled' => 'Disabled',
        'block_settings' => 'BlockSettings.html',
        'block_styles' => 'BlockStyles.html', // содержит PHP-код, поэтому не .css
        'init_js' => 'Init.js',
        'destruct_js' => 'Destruct.js',
    );

    protected $properties_include = array(
        'assets' => 'RequiredAssets.html',
    );

    /**
     * @param string|null $type
     * @param string|null $keyword
     * @return bool|string путь к папке без '/' на конце
     */
    public static function get_path_folder($type = null, $keyword = null) {
        static $base_path;
        if (!$base_path) {
            $nc_core = nc_core::get_object();
            $base_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'mixin/';
        }

        // safety precautions (file system access)
        if (!self::check_path_fragment($type) || !self::check_path_fragment($keyword)) {
            return false;
        }

        if ($keyword) {
            if (!$type) {
                return false;
            }
            $suffix = "$type/$keyword/";
        } else if ($type) {
            $suffix = "$type/";
        } else {
            $suffix = "";
        }

        return realpath($base_path . $suffix);
    }

    /**
     * @param $keyword
     * @return bool
     */
    protected static function check_path_fragment($keyword) {
        if (!$keyword) {
            return true;
        }
        return (bool)preg_match('/^[\w.]+$/', $keyword);
    }

    /**
     * @param string $type
     * @param string $keyword
     * @return nc_tpl_mixin|null
     */
    public static function by_keyword($type, $keyword) {
        $cache_key = "$type/$keyword";
        if (!isset(self::$mixin_instances[$cache_key])) {
            $folder = self::get_path_folder($type, $keyword);
            self::$mixin_instances[$cache_key] =
                $folder
                    ? new self(array(
                            'type' => $type,
                            'keyword' => $keyword,
                            'path' => $folder,
                        ))
                    : false;
            }
        return self::$mixin_instances[$cache_key] ?: null;
    }

    /**
     * @return string
     */
    protected function get_default_name() {
        return $this->get('keyword');
    }

    /**
     * @param string $selector
     * @param string $list_selector
     * @param array $settings
     * @param array $block_properties
     * @param nc_tpl_asset_collector $assets
     * @return string
     */
    public function get_block_styles($selector, $list_selector, array $settings, array $block_properties, nc_tpl_asset_collector $assets) {
        $path = $this->get('path') . '/' . $this->properties_read['block_styles'];
        if (!file_exists($path)) {
            return '';
        }
        // $selector, $list_selector, $settings, $assets используются внутри BlockStyles.html;
        // $block_settings доступен внутри BlockStyles.html, но наличие в нём какой-то
        // определённой информации не гарантируется.
        return include $path;
    }

    /**
     * @return false|string
     */
    public function get_block_settings_form() {
        $path = $this->get('path') . '/' . $this->properties_read['block_settings'];
        if (!file_exists($path)) {
            return '';
        }
        ob_start();
        include $path;
        return ob_get_clean();
    }

}