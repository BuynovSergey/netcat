<?php

/**
 * Класс для управления сгенерированными файлами стилей применённых к блокам и странице миксинов
 *
 * Для каждого элемента (сайта, раздела, блока; в примере — на сайте 123) может быть создано несколько файлов:
 *   — css-файл со стилями от миксинов  — в /netcat_template/css/123/mixins/
 *   — js-файл со скриптами от миксинов — в /netcat_template/js/123/mixins/
 *   — json-файл с перечислением необходимых библиотек (там же)
 *   (часть пути /netcat_template/ определяется настройкой HTTP_TEMPLATE_CACHE_PATH в vars.inc.php,
 *   а если её нет, то равна HTTP_TEMPLATE_PATH)
 *
 * Файлы создаются при создании или изменении соответствующего элемента.
 * При отсутствии файла стилей для элемента он автоматически не собирается.
 *
 * Все css-файлы от миксинов для сайта собираются в общий файл /netcat_template/css/123/mixins.css.
 * Если файл отсутствует, а стили для отдельных элементов есть, то файл будет собран заново при
 * запросе пути к нему (@see nc_tpl_mixin_cache::get_combined_styles_url())
 */

class nc_tpl_mixin_cache {

    const STYLE_PRIORITY_BODY = 100;
    const STYLE_PRIORITY_BLOCK_OUTSIDE_MAIN_AREA = 300;
    const STYLE_PRIORITY_MAIN_AREA = 500;
    const STYLE_PRIORITY_BLOCK_INSIDE_MAIN_AREA = 700;

    const FOR_SITE = 'site';
    const FOR_BLOCK = 'block';
    const FOR_SUBDIVISION = 'sub';

    /** @var array [ собственная константа => элемент в nc_core ] */
    protected static $core_entities = array(
        self::FOR_SITE => 'catalogue',
        self::FOR_SUBDIVISION => 'subdivision',
        self::FOR_BLOCK => 'sub_class',
    );

    protected static $block_parents = array();

    /**
     * Пересоздаёт файлы для сайта
     *
     * @param int $site_id
     * @param bool $cascade пересоздать файлы для разделов и блоков на сайте
     * @throws Exception
     */
    public static function generate_site_files($site_id, $cascade = true) {
        $site_id = (int)$site_id;
        self::delete_site_files($site_id);

        $nc_core = nc_core::get_object();

        if ($site_id) { // У блоков страниц полного вывода Catalogue_ID равен 0
            $site_settings = $nc_core->catalogue->get_by_id($site_id);
            if (self::has_settings($site_settings, 'MainArea')) {
                $mixin_body_selector = ".tpl-body-site-$site_id";
                $mixin_list_selector = "$mixin_body_selector .tpl-area-main-list";

                $collector = new nc_tpl_mixin_cache_collector();
                $collector
                    ->set_settings($site_settings, array('MainArea' => array($mixin_body_selector, $mixin_list_selector)))
                    ->save($site_id, self::FOR_SITE, $site_id, self::STYLE_PRIORITY_BODY);
            }
        }

        if ($cascade) {
            self::reset_time_limit();

            $block_ids = $nc_core->db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Catalogue_ID` = $site_id AND `Subdivision_ID` = 0");
            foreach ($block_ids as $block_id) {
                self::generate_block_files($block_id, true);
            }

            $subdivision_ids = $nc_core->db->get_col("SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Catalogue_ID` = $site_id AND `Parent_Sub_ID` = 0");
            foreach ($subdivision_ids as $subdivision_id) {
                self::generate_subdivision_files($subdivision_id, true);
            }
        }
    }

    /**
     * Пересоздаёт файлы для раздела
     *
     * @param int $subdivision_id
     * @param bool $cascade пересоздать файлы для блоков на странице и дочерних разделов
     * @throws Exception
     */
    public static function generate_subdivision_files($subdivision_id, $cascade = true) {
        $nc_core = nc_core::get_object();
        $subdivision_id = (int)$subdivision_id;
        $subdivision_data = $nc_core->subdivision->get_by_id($subdivision_id);
        $site_id = $subdivision_data['Catalogue_ID'];
        self::delete_subdivision_files($site_id, $subdivision_id);

        if (self::has_settings($subdivision_data, 'MainArea')) {
            $mixin_body_selector = ".tpl-body-sub-$subdivision_id";
            $mixin_list_selector = "$mixin_body_selector .tpl-area-main-list";

            $priority = self::STYLE_PRIORITY_BODY + substr_count($subdivision_data['Hidden_URL'], '/', 1);

            $collector = new nc_tpl_mixin_cache_collector();
            $collector
                ->set_settings($subdivision_data, array('MainArea' => array($mixin_body_selector, $mixin_list_selector)))
                ->save($site_id, self::FOR_SUBDIVISION, $subdivision_id, $priority);
        }

        if ($cascade) {
            self::reset_time_limit();

            $block_ids = $nc_core->db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = $subdivision_id");
            foreach ($block_ids as $block_id) {
                self::generate_block_files($block_id, false); // и так выбраны все блоки, $cascade не нужен
            }

            $subdivision_ids = $nc_core->db->get_col("SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Parent_Sub_ID` = $subdivision_id");
            foreach ($subdivision_ids as $id) {
                self::generate_subdivision_files($id, true);
            }
        }
    }

    /**
     * Пересоздаёт файлы для блока
     *
     * @param int $block_id
     * @param bool $cascade пересоздать файлы для вложенных блоков
     * @throws Exception
     */
    public static function generate_block_files($block_id, $cascade = true) {
        $nc_core = nc_core::get_object();
        $block_id = (int)$block_id;
        $block_data = $nc_core->sub_class->get_by_id($block_id);
        $site_id = $block_data['Catalogue_ID'];

        self::delete_block_files($site_id, $block_id);

        if (self::has_settings($block_data, 'Index') || self::has_settings($block_data, 'IndexItem')) {
            if ($block_data['Sub_Class_Type'] === 'object_partial') {
                $scopes = array(
                    'Index' => array(".tpl-block-$block_id", ''),
                );
            } elseif ($block_data['Class_ID']) {
                $scopes = array(
                    'Index' => array(".tpl-block-$block_id", ".tpl-block-$block_id^^ .tpl-block-$block_id-list"),
                    'IndexItem' => array(".tpl-block-$block_id^^ .tpl-block-$block_id-list > *", ''),
                );
            } else {
                $scopes = array(
                    'Index' => array(".tpl-container-$block_id", ".tpl-container-$block_id^^ .tpl-container-$block_id-list"),
                );
            }

            if ($block_data['Sub_Class_Type'] === 'main') {
                $priority = self::STYLE_PRIORITY_MAIN_AREA;
            } else {
                $priority =
                    ($block_data['Subdivision_ID'] ? self::STYLE_PRIORITY_BLOCK_INSIDE_MAIN_AREA : self::STYLE_PRIORITY_BLOCK_OUTSIDE_MAIN_AREA) +
                    self::get_block_depth($site_id, $block_id);
            }

            $collector = new nc_tpl_mixin_cache_collector();
            $collector
                ->set_settings($block_data, $scopes)
                ->save($site_id, self::FOR_BLOCK, $block_id, $priority);
        }

        if ($cascade) {
            $block_ids = $nc_core->db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Parent_Sub_Class_ID` = $block_id");
            foreach ($block_ids as $child_block_id) {
                self::generate_block_files($child_block_id, true);
            }
        }
    }

    /**
     * Возвращает количество блоков-родителей указанного блока
     *
     * @param int $site_id
     * @param int $block_id
     * @return int
     */
    protected static function get_block_depth($site_id, $block_id) {
        if (!isset(self::$block_parents[$site_id])) {
            self::preload_block_hierarchy($site_id);
        }

        $depth = 0;
        while (isset(self::$block_parents[$site_id][$block_id]) && $depth < 200) {
            $block_id = self::$block_parents[$site_id][$block_id];
            ++$depth;
        }

        return $depth;
    }

    /**
     * Загружает информацию о родителях всех (!) блоков на сайте (для подсчёта глубины расположения блоков)
     *
     * @param int $site_id
     */
    protected static function preload_block_hierarchy($site_id) {
        self::$block_parents[$site_id] = nc_db()->get_col(
            "SELECT `Sub_Class_ID`, `Parent_Sub_Class_ID` FROM `Sub_Class` WHERE `Catalogue_ID` = " . (int)$site_id,
            1, 0
        );
    }

    /**
     * Удаляет файлы, связанные с указанной сущностью (но не для вложенных элементов)
     *
     * @param int $site_id
     * @param string $entity одна из констант self::FOR_*
     * @param int $id ID сайта / раздела / блока
     */
    protected static function delete_files($site_id, $entity, $id) {
        $masks = array(
            self::get_file_folder($site_id, 'css') . '/' . self::get_file_name($entity, $id, false) . '.*',
            self::get_file_folder($site_id, 'js') . '/' . self::get_file_name($entity, $id, null) . '.*',
        );

        foreach ($masks as $mask) {
            foreach ((array)glob($mask) as $file) {
                unlink($file);
            }
        }

        self::delete_combined_styles_file($site_id);
    }

    /**
     * Удаляет файлы для сайта (без удаления файлов разделов и блоков).
     * Для удаления всех файлов для сайта существует метод self::delete_all_site_files().
     *
     * @param int $site_id
     */
    public static function delete_site_files($site_id) {
        self::delete_files($site_id, self::FOR_SITE, $site_id);
    }

    /**
     * Удаляет файлы для раздела (без удаления файлов подразделов и блоков)
     *
     * @param $site_id
     * @param $subdivision_id
     */
    public static function delete_subdivision_files($site_id, $subdivision_id) {
        self::delete_files($site_id, self::FOR_SUBDIVISION, $subdivision_id);
    }

    /**
     * Удаляет файлы для блока
     *
     * @param int $site_id
     * @param int $block_id
     */
    public static function delete_block_files($site_id, $block_id) {
        self::delete_files($site_id, self::FOR_BLOCK, $block_id);
    }

    /**
     * Возвращает путь (от корня диска) к папке для записи файлов указанного типа
     *
     * @param int $site_id
     * @param string $file_type 'css', 'js'
     * @return string
     */
    public static function get_file_folder($site_id, $file_type) {
        $nc_core = nc_core::get_object();
        return
            //  E.g.: /netcat_template/css/1/mixins; /netcat_template/js/1/mixins
            $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_CACHE_PATH .
            $file_type . '/' . (int)$site_id . '/mixins'; // важно: без '/' на конце (см. self::delete_combined_styles_file())
    }

    /**
     * Возвращает имя файла (без расширения) для указанной сущности
     *
     * @param string $entity одна из констант self::FOR_*
     * @param int $id
     * @param int|null|false $priority
     *      число — добавить приоритет как префикс к имени файла (для сортировки файлов стилей блоков)
     *      false — вернуть маску для поиска файлов через glob()
     *      null — не добавлять приоритет к имени файла
     * @return string
     */
    public static function get_file_name($entity, $id, $priority) {
        $id = (int)$id;
        if ($priority === false) {
            return "???_{$entity}_{$id}"; // mask for glob()
        }
        if ($priority === null) {
            return "{$entity}_{$id}"; // no priority
        }
        return sprintf('%03d_%s_%d', $priority, $entity, $id); // priority (for css files)
    }

    /**
     * Проверяет, есть ли в массиве настройки (или пресет) для миксинов для указанного типа элемента страницы
     *
     * @param array $settings
     * @param string $type  например: 'MainArea', 'Index', 'IndexItem'
     * @return bool
     */
    protected static function has_settings(array $settings, $type) {
        $own_settings = nc_array_value($settings, $type . '_Mixin_Settings');
        if ($own_settings && $own_settings !== '{}') {
            return true;
        }
        $preset = nc_array_value($settings, $type . '_Mixin_Preset_ID');
        return (bool)$preset;
    }

    /**
     * Удаляет общий файл mixins.css для указанного сайта
     *
     * @param int $site_id
     */
    protected static function delete_combined_styles_file($site_id) {
        $file_path = self::get_file_folder($site_id, 'css') . '.css';
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    /**
     * Удаляет все файлы для сайта
     *
     * @param int $site_id
     */
    public static function delete_all_site_files($site_id) {
        $css_base_path = self::get_file_folder($site_id, 'css');
        if (is_dir($css_base_path)) {
            nc_delete_dir($css_base_path);
        }
        if (file_exists("$css_base_path.css")) {
            unlink("$css_base_path.css");
        }

        $js_folder = self::get_file_folder($site_id, 'js');
        if (is_dir($js_folder)) {
            nc_delete_dir($js_folder);
        }
    }

    /**
     * Проверяет наличие общего файла mixins.css, и при его отсутствии создаёт его
     *
     * @param int $site_id
     * @return false|int время изменения файла
     */
    protected static function make_combined_styles_file($site_id) {
        $base_path = self::get_file_folder($site_id, 'css');
        $file_path = "$base_path.css"; // E.g. /netcat_template/css/1/mixins.css

        // Если файл существует — используем его
        if (file_exists($file_path)) {
            return filemtime($file_path);
        }

        $files = (array)glob("$base_path/*.css");
        if ($files) {
            foreach ($files as $file) {
                file_put_contents($file_path, file_get_contents($file), FILE_APPEND);
            }
            chmod($file_path, nc_core::get_object()->FILECHMOD);
            return filemtime($file_path);
        }

        return false;
    }

    /**
     * Возвращает время последнего изменения файлов в указанной папке
     *
     * @param string $folder
     * @return int
     */
    protected static function get_max_timestamp_in_folder($folder) {
        $max = 0;
        foreach ((array)glob("$folder/*") as $file) {
            if (is_dir($file)) {
                $timestamp = self::get_max_timestamp_in_folder($file);
            } else {
                $timestamp = filemtime($file);
            }
            if ($timestamp > $max) {
                $max = $timestamp;
            }
        }
        return $max;
    }

    /**
     * Возвращает путь к общему файлу mixins.css от корня сайта.
     * Запускает пересборку файла mixins.css при его отсутствии.
     *
     * При изменении файлов миксинов файл mixins.css будет пересобран только если режим работы сайта — «разработка шаблонов».
     *
     * @param int $site_id
     * @return string|null
     * @throws Exception
     */
    public static function get_combined_styles_url($site_id) {
        if ($timestamp = self::make_combined_styles_file($site_id)) {
            $nc_core = nc_core::get_object();

            // Форсированная пересборка всех файлов сайта, если изменились файлы миксинов (только в режиме «разработки шаблона» (template_development)
            $should_rebuild_cache =
                $nc_core->catalogue->get_current('ncMode') === 'template_development' &&
                self::get_max_timestamp_in_folder(rtrim(nc_tpl_mixin::get_path_folder(), '/')) >= $timestamp;
            if ($should_rebuild_cache) {
                self::generate_site_files($site_id, true);
                clearstatcache();
                $timestamp = self::make_combined_styles_file($site_id);
            }

            $nc_core->page->update_last_modified_if_timestamp_is_newer($timestamp);
            return $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_CACHE_PATH . 'css/' . (int)$site_id . '/mixins.css?' . $timestamp;
        }
        return null;
    }

    /**
     * Добавляет на страницу необходимые библиотеки и скрипты (применяет закэшированные для блока сведения
     * о необходимых для миксинов блоков библиотеках и вызовы nc_mixin_init)
     *
     * @param int $site_id
     * @param string $entity
     * @param int $id
     */
    protected static function apply_to_page($site_id, $entity, $id) {
        $nc_core = nc_core::get_object();

        if ($nc_core->inside_admin) {
            return;
        }

        $base_file_path = self::get_file_folder($site_id, 'js') . '/' . self::get_file_name($entity, $id, null);

        $js_file_path = "$base_file_path.js";

        if (file_exists($js_file_path)) {
            $nc_core->page->add_javascript(file_get_contents($js_file_path));
        }

        $json_file_path = "$base_file_path.json";
        if (file_exists($json_file_path)) {
            $data = json_decode(file_get_contents($json_file_path), true);

            if (!empty($data['assets_once'])) {
                foreach ($data['assets_once'] as $params) {
                    $nc_core->page->require_asset_once($params[0], $params[1]);
                }
            }

            if (!empty($data['assets'])) {
                foreach ($data['assets'] as $params) {
                    $nc_core->page->require_asset($params[0], $params[1]);
                }
            }

            if (!empty($data['meta'])) {
                $core_entity = self::get_core_entity($entity);
                foreach ($data['meta'] as $key => $value) {
                    $core_entity->set_meta($id, $key, $value);
                }
            }
        }
    }

    /**
     * Применяет к nc_page скрипты, зависимости и настройки миксинов указанного сайта.
     *
     * @param int $site_id
     */
    public static function load_site_data($site_id) {
        self::apply_to_page($site_id, self::FOR_SITE, $site_id);
    }

    /**
     * Применяет к nc_page скрипты, зависимости и настройки миксинов указанного раздела.
     *
     * @param int $site_id
     * @param int $subdivision_id
     */
    public static function load_subdivision_data($site_id, $subdivision_id) {
        self::apply_to_page($site_id, self::FOR_SUBDIVISION, $subdivision_id);
    }

    /**
     * Применяет к nc_page скрипты, зависимости и настройки миксинов указанного блока.
     *
     * @param int $site_id
     * @param int $block_id
     */
    public static function load_block_data($site_id, $block_id) {
        self::apply_to_page($site_id, self::FOR_BLOCK, $block_id);
    }


    /**
     * @param $entity
     * @throws UnexpectedValueException
     * @return nc_essence
     */
    public static function get_core_entity($entity) {
        if (isset(self::$core_entities[$entity])) {
            $core_entity = self::$core_entities[$entity];
            return nc_core::get_object()->$core_entity;
        }
        throw new UnexpectedValueException("'$entity' is not set in " . __CLASS__ . '::$core_entities');
    }

    /**
     * Снимает ограничения на время работы скрипта и работу скрипта при отключении клиента
     */
    protected static function reset_time_limit() {
        static $time_limit_set = false;
        if (!$time_limit_set) {
            @set_time_limit(0);
            @ignore_user_abort(true);
            $time_limit_set = true;
        }
    }

}