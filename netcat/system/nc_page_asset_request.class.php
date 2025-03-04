<?php

/**
 * Класс для работы с запросом на добавление библиотеки на страницу.
 * Не является частью публичного API.
 */
class nc_page_asset_request {

    /** @var self[] */
    static protected $instances = array();

    protected $keyword;
    protected $min_version;
    protected $max_version;

    protected $options = array(
        'defer' => true,
        'embed' => false,
    );

    // Кэширование некоторых результатов:
    /** @var  array */
    protected $all_versions;
    /** @var  string|false */
    protected $selected_version;
    /** @var  array */
    protected $selected_version_manifest;
    /** @var  string */
    protected $asset_version_path;

    /**
     * @param $keyword
     * @return nc_page_asset_request
     */
    static public function get_instance($keyword) {
        if (!isset(self::$instances[$keyword])) {
            self::$instances[$keyword] = new self($keyword);
        }
        return self::$instances[$keyword];
    }

    /**
     * Singleton по ключевому слову библиотеки, чтобы не загружать повторно списки
     * версий, манифесты и т. п. — существенно упрощает логику развёртывания зависимостей
     * @param string $keyword
     */
    protected function __construct($keyword) {
        $this->keyword = $keyword;
    }

    /**
     * @return string
     */
    public function get_keyword() {
        return $this->keyword;
    }

    /**
     * @return array
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * @param array $options
     * @return bool
     */
    public function will_require_dependencies_update(array $options) {
        return
            // проверка defer: надо будет обновить его у зависимостей, если сейчас defer=true,
            // а в опциях другого запроса — false
            ($this->options['defer'] && !$options['defer']) ||
            // проверка embed: надо будет обновить его у зависимостей, если сейчас embed=false,
            // а в опциях другого запроса — true
            ($options['embed'] && !$this->options['embed']);
    }

    /**
     * @param string $versions
     * @param array $options
     * @return bool false, если библиотека не была добавлена из-за конфликта версий
     */
    public function add_request_from_string($versions, array $options = array()) {
        list ($min, $max) = $this->parse_versions_string($versions);
        $options = $options + $this->options;
        if ($this->min_version !== null) {
            // add() вызывается повторно для этой библиотеки —
            // проверяем возможность добавить запрошенную версию,
            // обобщаем параметры
            return $this->merge_params($min, $max, $options);
        }

        // вызывается впервые
        $this->min_version = $min;
        $this->max_version = $max;
        $this->options = $options;

        return true;
    }

    /**
     * @param nc_page_asset_request $asset_request
     * @return bool
     */
    public function merge_request_parameters(self $asset_request) {
        return $this->merge_params($asset_request->min_version, $asset_request->max_version, $asset_request->options);
    }

    /**
     * @param string $min
     * @param string $max
     * @param array $options
     * @return bool
     */
    protected function merge_params($min, $max, array $options) {
        // считать 0-999 (отсутствие версии) синонимом latest, если библиотека хотя бы раз запрошена с latest
        if ($min === '0' && $max === '999' && $this->min_version === 'latest') {
            $min = $max = 'latest';
        }
        if ($min === 'latest' && $this->min_version === '0' && $this->max_version === '999') {
            $this->min_version = $this->max_version = 'latest';
        }

        if ($this->min_version !== $min || $this->max_version !== $max) {
            if (
                version_compare($min, $this->max_version, '>') ||
                version_compare($max, $this->min_version, '<')
            ) {
                // запрошенная сейчас версии библиотека за пределами тех,
                // что были запрошены ранее — не можем добавить!
                return false;
            }
            // сужение диапазона допустимых версий при необходимости
            if (version_compare($min, $this->min_version, '>')) {
                $this->min_version = $min;
            }
            if (version_compare($max, $this->max_version, '<')) {
                $this->max_version = $max;
                if ($this->selected_version && version_compare($this->selected_version, $max, '>')) {
                    $this->selected_version = null;
                    $this->selected_version_manifest = null;
                }
            }
        }

        // defer по умолчанию true;
        // если хоть раз использован defer=false, такое значение больше не должно меняться
        if (!$options['defer']) {
            $this->options['defer'] = false;
        }

        // embed по умолчанию false;
        // если хоть раз использован embed=true, такое значение больше не должно меняться
        if ($options['embed']) {
            $this->options['embed'] = true;
        }

        return true;
    }

    /**
     * @param string $versions
     * @return array
     */
    protected function parse_versions_string($versions) {
        if ($versions !== '') {
            if (strpos($versions, '-') === false) {
                $min = $max = $versions;
            } else {
                list($min, $max) = explode('-', $versions, 2);
            }
            $min = str_replace('*', '0', $min);
            $max = str_replace('*', '999', $max);
        } else {
            $min = '0';
            $max = '999';
        }
        return array($min, $max);
    }

    /**
     * Путь к папке с библиотекой от корня сайта
     * @return string
     */
    protected function get_asset_base_path() {
        return nc_core::get_object()->ASSET_PATH . $this->keyword . '/';
    }

    /**
     * Путь к папке с выбранной версией библиотеки от корня сайта
     * @return false|string
     */
    public function get_asset_version_path() {
        if ($this->asset_version_path === null) {
            $version = $this->get_latest_possible_version();
            if ($version === false) {
                $this->asset_version_path = false;
            } else {
                $this->asset_version_path = $this->get_asset_base_path() . $version . '/';
            }
        }
        return $this->asset_version_path;
    }


    /**
     * @param $folder
     * @return array
     */
    protected function get_all_asset_versions($folder) {
        if ($this->all_versions === null) {
            $this->all_versions = array();
            // glob тут чуть быстрее, чем opendir/readdir или FilesystemIterator;
            // substr чуть быстрее, чем basename
            foreach (glob($folder . '*', GLOB_NOSORT | GLOB_ONLYDIR) as $f) {
                $this->all_versions[] = substr($f, strrpos($f, '/') + 1);
            }
            usort($this->all_versions, 'version_compare');
        }
        return $this->all_versions;
    }

    /**
     * @return bool|string
     */
    protected function get_latest_possible_version() {
        if ($this->selected_version === null) {
            $this->selected_version = false;
            $asset_folder = nc_core::get_object()->DOCUMENT_ROOT . $this->get_asset_base_path();

            if ($this->min_version === $this->max_version) {
                if (is_dir($asset_folder . $this->max_version)) {
                    $this->selected_version = $this->max_version;
                }
            } else {
                $all_versions = $this->get_all_asset_versions($asset_folder);
                $all_versions_count = count($all_versions);
                if ($all_versions_count === 1 && preg_match('/[A-Za-z]/', $all_versions[0])) {
                    $this->selected_version = $all_versions[0];
                } else {
                    for ($i = $all_versions_count - 1; $i >= 0; $i--) {
                        if (
                            version_compare($all_versions[$i], $this->min_version, '>=') &&
                            version_compare($all_versions[$i], $this->max_version, '<=')
                        ) {
                            $this->selected_version = $all_versions[$i];
                            break; // exit "for"
                        }
                    }
                }
            }
        }
        return $this->selected_version;
    }

    /**
     * @return array
     */
    protected function load_asset_manifest() {
        $asset_version_path = $this->get_asset_version_path();
        if ($asset_version_path !== false) {
            $asset_folder = nc_core::get_object()->DOCUMENT_ROOT . $asset_version_path;
            $manifest_file = $asset_folder . 'netcat_manifest.php';
            if (!file_exists($manifest_file)) {
                $this->create_manifest_file($asset_folder);
            }
            $manifest = include $manifest_file ?: array();
        } else {
            $manifest = array();
        }

        return (array)$manifest + array(
                // default values
                'require' => array(),
                'js' => array(),
                'css' => array(),
            );
    }

    /**
     * @param string $asset_folder
     */
    protected function create_manifest_file($asset_folder) {
        $js = $this->get_file_list_for_manifest($asset_folder, 'js');
        $css = $this->get_file_list_for_manifest($asset_folder, 'css');

        // результат var_export выглядит коряво, сформируем код массива сами
        $manifest_content = "<?php\n" .
            "// (auto-generated asset manifest file)\n" .
            "return array(\n" .
            ($js  ? "    'js' => array('" .  join("', '", $js) .  "'),\n" : "") .
            ($css ? "    'css' => array('" . join("', '", $css) . "'),\n" : "") .
            ");\n";
        file_put_contents($asset_folder . 'netcat_manifest.php', $manifest_content);
    }

    /**
     * @param string $asset_folder
     * @param string $extension
     * @return array
     */
    protected function get_file_list_for_manifest($asset_folder, $extension) {
        $all_files = array_map('basename', glob($asset_folder . '*.' . $extension));

        $filtered = array();
        foreach ($all_files as $file_name) {
            if (!strpos($file_name, '.min.')) {
                // для неминифицированных файлов проверяем наличие минифицированного
                $minified_file_name = basename($file_name, ".$extension") . ".min.$extension";
                if (in_array($minified_file_name, $all_files, true)) {
                    continue;
                }
            }
            $filtered[] = $file_name;
        }
        return $filtered;
    }

    /**
     * @param null|string $item
     * @return array|mixed|null
     */
    public function get_asset_manifest($item = null) {
        if ($this->selected_version_manifest === null) {
            $this->selected_version_manifest = $this->load_asset_manifest();
        }
        return $item ? nc_array_value($this->selected_version_manifest, $item) : $this->selected_version_manifest;
    }

    /**
     * @return array[]
     */
    public function get_resource_paths() {
        $resource_paths = array();
        $manifest = $this->get_asset_manifest();

        if ($this->options['embed']) {
            $js_key = 'js_embed';
        } else if ($this->options['defer']) {
            $js_key = 'js_defer';
        } else {
            $js_key = 'js_sync';
        }

        foreach ((array)$manifest['js'] as $resource_path) {
            $resource_paths[$js_key][] = $this->get_absolute_resource_path($resource_path);
        }

        foreach ((array)$manifest['css'] as $resource_path) {
            $resource_paths['css'][] = $this->get_absolute_resource_path($resource_path);
        }

        return $resource_paths;
    }

    /**
     * @param string $resource_path
     * @return string
     */
    protected function get_absolute_resource_path($resource_path) {
        if (preg_match('#^(?:https?:)?//#', $resource_path)) {
            return $resource_path;
        }

        return $this->get_asset_version_path() . $resource_path;
    }

}