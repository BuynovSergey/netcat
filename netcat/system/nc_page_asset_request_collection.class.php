<?php

/**
 * Класс для работы с набором запросов для подключения библиотек.
 * Не является частью публичного API.
 *
 *
 * На первом этапе (обработка макетов дизайна, шаблонов компонентов)
 * производится сбор информации о запрошенных библиотеках (необходимые версии,
 * возможность defer-загрузки скриптов) без попытки определения конкретной
 * версии библиотеки. (Поэтому это "asset requests", а не просто "assets".)
 * Если последовательно запрошена одна и та же библиотека с непересекающимися
 * диапазонами версий в разных запросах, второй и последующие такие запросы
 * будут отклонены (т. е. будет подключена первая запрошенная версия библиотеки).
 *
 * На втором этапе (непосредственно перед отдачей страницы) производится
 * вычисление конкретных версий и зависимостей библиотек.
 * В случае, если библиотека требует зависимую библиотеку, которая запорошена
 * явным образом (через метод require_asset()), но в конфликтующей версии,
 * будет загружена библиотека в версии из явного запроса.
 *
 * Циклические зависимости не отслеживаются (предполагается, что их не должно
 * быть в netcat_manifest.php).
 *
 *
 * (При выполнении методов коллекции меняются свойства объектов в коллекции —
 * они не рассматриваются как неизменяемые/immutable)
 */
class nc_page_asset_request_collection {

    /** @var nc_page_asset_request[] */
    protected $asset_requests = array();

    /**
     * @param string $keyword
     * @return bool
     */
    public function has($keyword) {
        return isset($this->asset_requests[$keyword]);
    }

    /**
     * Добавляет на страницу библиотеки, перечисленные в массиве.
     * Возможные способы указания библиотек:
     * array(
     *     'asset_keyword@ver',
     *     'asset_keyword@ver' => array('defer' => false),
     * )
     *
     * Этот формат используется в файлах RequiredAssets.html.
     *
     * @param array $assets
     * @return bool
     */
    public function add_asset_requests_from_array(array $assets) {
        $result = true;
        foreach ($assets as $key => $value) {
            // только ключевое слово и версия
            if (is_int($key) && is_string($value)) {
                $result = $result && $this->add_asset_request_from_string($value, array());
            } else if (is_array($value)) {
                $result = $result && $this->add_asset_request_from_string($key, $value);
            }
        }
        return $result;
    }

    /**
     * @param string|nc_page_asset_request $asset_keyword_and_version
     * @param array $options
     * @return bool mixed
     */
    public function add_asset_request_from_string($asset_keyword_and_version, array $options) {
        list($keyword, $versions) = explode('@', $asset_keyword_and_version . '@');
        if (!isset($this->asset_requests[$keyword])) {
            $this->asset_requests[$keyword] = nc_page_asset_request::get_instance($keyword);
        }
        return $this->asset_requests[$keyword]->add_request_from_string($versions, $options);
    }

    /**
     * @param nc_page_asset_request $request
     * @return bool
     */
    protected function add_asset_request(nc_page_asset_request $request) {
        $keyword = $request->get_keyword();
        if (!isset($this->asset_requests[$keyword])) {
            $this->asset_requests[$keyword] = $request;
            return true;
        }
        return $this->asset_requests[$keyword]->merge_request_parameters($request);
    }

    /**
     * @param nc_page_asset_request_collection|null $collected_result
     * @return nc_page_asset_request_collection
     */
    public function with_dependencies(nc_page_asset_request_collection $collected_result = null) {
        if ($collected_result === null) {
            $collected_result = new self;
        }

        foreach ($this->asset_requests as $asset_keyword => $asset_request) {
            $asset_request_options = $asset_request->get_options();

            $dependency_strings = (array)$asset_request->get_asset_manifest('require');
            foreach ($dependency_strings as $dependency_string) {
                list($dependency_keyword) = explode('@', $dependency_string, 2);

                $can_skip_dependency_update =
                    isset($collected_result->asset_requests[$dependency_keyword]) &&
                    !$collected_result->asset_requests[$dependency_keyword]->will_require_dependencies_update($asset_request_options);

                if ($can_skip_dependency_update) {
                    $collected_result->add_asset_request_from_string($dependency_string, $asset_request_options);
                } else {
                    // для рекурсивного получения зависимостей используем этот же класс и метод
                    // (проверки на цикличность нет!)
                    $dependency_assets = new self;
                    $dependency_assets->add_asset_request_from_string($dependency_string, $asset_request_options);
                    $expanded_dependencies = $dependency_assets->with_dependencies($collected_result);
                    foreach ($expanded_dependencies->asset_requests as $dependency) {
                        $collected_result->add_asset_request($dependency);
                    }
                }
            }

            $collected_result->add_asset_request($asset_request);
        }

        return $collected_result;
    }

    /**
     * @return array
     */
    public function get_resource_paths() {
        $all_resource_paths = array(
            'js_embed' => array(),
            'js_sync' => array(),
            'js_defer' => array(),
            'css' => array(),
        );
        foreach ($this->asset_requests as $asset) {
            $asset_resource_paths = $asset->get_resource_paths();
            foreach ($asset_resource_paths as $type => $paths) {
                foreach ($paths as $path) {
                    $all_resource_paths[$type][] = $path;
                }
            }
        }
        return $all_resource_paths;
    }

    /**
     * Возвращает пути к папкам запрошенных версий библиотек от корня сайта
     * @return array
     */
    public function get_asset_version_paths() {
        $paths = array();
        foreach ($this->asset_requests as $asset) {
            $path = $asset->get_asset_version_path();
            if ($path) {
                $paths[] = $path;
            }
        }
        return $paths;
    }

}