<?php

class nc_Sub_Class extends nc_Essence {

    protected $db;
    private $_current_id;

    const MAX_KEYWORD_LENGTH = 64;

    protected $ctpl_cache;
    protected $subdivision_cache;
    protected $container_cache = array();
    protected $subdivision_first_checked_cache;
    protected $infoblock_system_settings_cache;

    /**
     * Constructor function
     */
    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $nc_core = nc_core::get_object();
        // system db object
        if (is_object($nc_core->db)) {
            $this->db = $nc_core->db;
        }
        $this->register_event_listeners();
    }

    /**
     * Обработчики для обновления и сброса кэша
     */
    protected function register_event_listeners() {
        $event = nc_core::get_object()->event;
        $clear_cache = array($this, 'clear_cache');
        $event->add_listener(nc_event::AFTER_INFOBLOCK_UPDATED, $clear_cache);
        $event->add_listener(nc_event::AFTER_INFOBLOCK_ENABLED, $clear_cache);
        $event->add_listener(nc_event::AFTER_INFOBLOCK_DISABLED, $clear_cache);
        $event->add_listener(nc_event::AFTER_INFOBLOCK_DELETED, $clear_cache);
    }

    /**
     * @param string $query_condition
     * @param array $data
     * @return array
     */
    protected function set_cache($query_condition, array $data) {
        // Кэш для выборки по родительскому контейнеру $this->container_cache
        // хранится в многомерном массиве:
        // — первый ключ равен $query_condition:
        //   — для инфоблоков разделов — ID раздела;
        //   — для сквозных инфоблоков — строка с параметрами запроса, см. метод get_area_container_cache_key();
        //   — для инфоблоков, выбранных по ID контейнера — строка 'C'
        // — второй ключ равен идентификатору контейнера (Parent_Sub_Class_ID инфоблока)

        if (!isset($this->container_cache[$query_condition])) {
            // это позволит определить, что такой запрос уже был
            $this->container_cache[$query_condition] = array();
        }

        $processed_container_ids = array();
        foreach ($data as $row) {
            $id = $row['Sub_Class_ID'];
            $container_id = $row['Parent_Sub_Class_ID'];

            // это то же, что делает метод set_data()
            $this->data[$id] = $row;
            $this->data[$id]['_nc_final'] = 0;

            // установить все данные...
            $this->get_by_id($id);

            // Этот метод сбрасывает кэш для контейнера, если в $data есть данные
            // для этого контейнера:
            if (!isset($processed_container_ids[$container_id])) {
                $this->container_cache[$query_condition][$container_id] = array();
                $processed_container_ids[$container_id] = true;
            }

            $this->container_cache[$query_condition][$container_id][] = &$this->data[$id];

        }

        return $this->container_cache[$query_condition];
    }

    /**
     * @param $area_keyword
     * @param array $routing_data
     * @return string
     */
    protected function get_area_container_cache_key($area_keyword, array $routing_data) {
        unset($routing_data['variables']);
        return "$area_keyword;" . join(';', $routing_data);
    }

    /**
     * @param int $subdivision_id
     * @throws Exception
     * @return array
     */
    protected function load_all_by_subdivision_id($subdivision_id) {
        $subdivision_id = (int)$subdivision_id;
        // $site_id нужен для использования существующего индекса EnglishName (Catalogue_ID, Subdivision_ID, EnglishName)
        $site_id = nc_core::get_object()->subdivision->get_by_id($subdivision_id, 'Catalogue_ID');
        $rows = (array)$this->db->get_results(
            "SELECT b.*, 
                    c.`System_Table_ID` AS sysTbl,
                    IF(`b`.`Class_Template_ID` != 0 AND `b`.`Class_ID` != `b`.`Class_Template_ID`, `b`.`Class_Template_ID`, `c`.`Main_ClassTemplate_ID`) AS `Class_Template_ID`       
               FROM `Sub_Class` AS b
               LEFT JOIN `Class` AS c ON b.`Class_ID` = c.`Class_ID`
              WHERE b.`Catalogue_ID` = $site_id AND b.`Subdivision_ID` = $subdivision_id
              ORDER BY b.`Priority`, b.`Sub_Class_ID`",
            ARRAY_A);
        return $this->set_cache($subdivision_id, $rows);
    }

    /**
     * @param int $subdivision_id
     * @param bool $reset
     */
    public function get_all_by_subdivision_id($subdivision_id, $reset = false) {
        $infoblocks_by_container = $this->load_all_by_subdivision_id($subdivision_id);
        $result = array();
        foreach ((array)$infoblocks_by_container as $row) {
            $result = array_merge($result, $row);
        }
        return $result;
    }

    /**
     * Get subclass data with system table flag from `Class` table
     *
     * @param int $subdivision_id Subdivision_ID, if not set - use current value in query
     * @param bool $reset reset stored data in the static variable
     *
     * @return array|bool subclass data associative array
     */
    public function get_by_subdivision_id($subdivision_id = 0, $reset = false) {
        $nc_core = nc_core::get_object();
        $subdivision_id = (int)$subdivision_id;
        if (!$subdivision_id && is_object($nc_core->subdivision)) {
            $subdivision_id = $nc_core->subdivision->get_current("Subdivision_ID");
        }

        if (!$subdivision_id) {
            return false;
        }

        if ($reset || !isset($this->container_cache[$subdivision_id])) {
            // Загружаем все уровни (вложенные контейнеры), так как для вывода
            // содержимого всё равно потребуются данные уровнем глубже
            $this->load_all_by_subdivision_id($subdivision_id);
        }

        return isset($this->container_cache[$subdivision_id][0])
            ? $this->container_cache[$subdivision_id][0]
            : array(); // до Netcat 6 возвращался null
    }

    protected function get_current_page_routing_data() {
        $action = nc_array_value($GLOBALS, 'action', 'index');
        if ($action === 'change') {
            $action = 'edit'; // в routing_result используется 'edit', а в режиме редактирования $action == 'change'
        }
        return array(
            'site_id' => (int)nc_array_value($GLOBALS, 'catalogue') ?: null,
            'folder_id' => (int)nc_array_value($GLOBALS, 'sub') ?: null,
            'infoblock_id' => (int)nc_array_value($GLOBALS, 'cc') ?: null,
            'object_id' => (int)nc_array_value($GLOBALS, 'message') ?: null,
            'object_keyword' => null,
            'action' => $action,
        );
    }

    /**
     * Пока не является частью публичного API: возможен рефакторинг.
     *
     * Возвращает все (в т. ч. выключенные) блоки в области с указанным ключевым словом
     * с учётом условий показа блоков.
     *
     * @param string $area_keyword
     * @param array|null $routing_data массив с информацией о текущей странице (как в nc_page::get_routing_result)
     * @param int $container_id
     * @param bool $reset
     * @return array
     */
    public function get_by_area_keyword($area_keyword, $routing_data = array(), $container_id = 0, $reset = false) {
        $nc_core = nc_core::get_object();

        if (empty($routing_data)) { // например, режим редактирования
            $routing_data = $this->get_current_page_routing_data();
        }

        $cache_key = $this->get_area_container_cache_key($area_keyword, $routing_data);

        $reload =
            $reset || // запрошена перезагрузка
            !isset($this->container_cache[$cache_key][$container_id]) || // нет необходимых данных
            !isset($this->container_cache[$cache_key][0]); // запрос к корневому уровню загружает данные полностью

        if ($reload) {
            $db = $nc_core->db;

            // Для $container_id = 0 загружаем все блоки в области (они понадобятся)
            $container_id = (int)$container_id;
            $where_container = $container_id ? "AND `block`.`Parent_Sub_Class_ID` = $container_id" : '';

            if ($nc_core->component->is_full_page_area($area_keyword)) {
                // Особый запрос для области свободной сборки страницы полного вывода объектов:
                // игнорируется привязка к сайту (на случай $ignore_catalogue), условия видимости блоков
                $where_site = "`block`.`Catalogue_ID` = 0";
                $join_conditions = '';
                $where_conditions = '';
            } else {
                $where_site = "`block`.`Catalogue_ID` = " . (int)$routing_data['site_id'];

                $component_ids = $this->get_main_content_component_ids($routing_data);
                $sub = nc_array_value($routing_data, 'folder_id');
                $parent_subdivision_ids = $this->get_parent_subdivision_ids_for_area_conditions($sub);

                $join_conditions =
                    "LEFT JOIN `Sub_Class_AreaCondition_Class_Exception` AS `not_component` 
                           ON (
                                `block`.`Sub_Class_ID` = `not_component`.`Sub_Class_ID` 
                                AND `not_component`.`Class_ID` IN ($component_ids)
                              )
                    LEFT JOIN `Sub_Class_AreaCondition_Class` AS `component` 
                           ON (`block`.`Sub_Class_ID` = `component`.`Sub_Class_ID`)
                    LEFT JOIN `Sub_Class_AreaCondition_Subdivision_Exception` AS `not_sub` 
                           ON (
                                `block`.`Sub_Class_ID` = `not_sub`.`Sub_Class_ID`
                                {$this->generate_area_excluded_subdivision_join_condition($sub, $parent_subdivision_ids)}
                              )
                    LEFT JOIN `Sub_Class_AreaCondition_Subdivision` AS `sub` 
                           ON (`block`.`Sub_Class_ID` = `sub`.`Sub_Class_ID`)
                    LEFT JOIN `Sub_Class_AreaCondition_Message` AS `object` 
                           ON (`block`.`Sub_Class_ID` = `object`.`Sub_Class_ID`)";

                $where_conditions =
                    $this->generate_area_action_condition($routing_data) .
                    $this->generate_area_component_condition($component_ids) .
                    $this->generate_area_subdivision_condition($sub, $parent_subdivision_ids) .
                    $this->generate_area_object_condition($routing_data);
            }

            $query =
                "SELECT DISTINCT 
                        `block`.*, 
                        IF(
                            `block`.`Class_Template_ID` != 0 AND `block`.`Class_ID` != `block`.`Class_Template_ID`, 
                            `block`.`Class_Template_ID`, 
                            `class`.`Main_ClassTemplate_ID`
                        ) AS `Class_Template_ID`
                   FROM `$this->essence` AS `block`
                        $join_conditions
                        LEFT JOIN `Class` AS `class`
                               ON (`block`.`Class_ID` = `class`.`Class_ID`)                   
                  WHERE $where_site
                    AND `block`.`AreaKeyword` = '" . $db->escape($area_keyword) . "'
                    $where_container
                    $where_conditions
                  ORDER BY `block`.`Priority`, `block`.`Sub_Class_ID`";
            $raw_data = (array)$db->get_results($query, ARRAY_A);
            $this->set_cache($cache_key, $raw_data);
        }

        return isset($this->container_cache[$cache_key][$container_id])
                   ? $this->container_cache[$cache_key][$container_id]
                   : array();
    }

    /**
     * Удаляет все блоки в указанной области
     * @param int $site_id
     * @param string $area_keyword
     * @param bool $use_regexp_for_area_keyword использовать $area_keyword как регулярное выражение (используйте якоря ^ и $!)
     * @return void
     * @throws nc_Exception_DB_Error
     */
    public function delete_by_area_keyword($site_id, $area_keyword, $use_regexp_for_area_keyword = false) {
        $db = nc_db();
        $site_id = (int)$site_id;
        $area_keyword = $db->escape($area_keyword);
        $block_ids = $db->get_col(
            "SELECT `Sub_Class_ID`
             FROM `Sub_Class`
             WHERE `Catalogue_ID` = $site_id
               AND `AreaKeyword` " . ($use_regexp_for_area_keyword ? "REGEXP" : "=") . " '$area_keyword'
               AND `Parent_Sub_Class_ID` = 0"
        );

        foreach ($block_ids as $block_id) {
            $this->delete_recursively($block_id, false);
        }
    }

    /**
     * Вспомогательный метод для получения ID компонентов в основной контентной
     * области по массиву с данными о запрошенной странице
     * @param array $routing_data
     * @return string ID компонентов через запятую — для использования в условии IN()
     *
     */
    protected function get_main_content_component_ids(array $routing_data) {
        $action = nc_array_value($routing_data, 'action', 'index');
        $component_ids = array();
        if ($action === 'index' && !$routing_data['infoblock_id'] && $routing_data['folder_id']) {
            $main_content_blocks = $this->get_by_subdivision_id($routing_data['folder_id']) ?: array();
            foreach ($main_content_blocks as $main_content_block) {
                $component_ids[] = $main_content_block['Class_ID'];
            }
        } else if ($routing_data['infoblock_id']) {
            $component_ids[] = $this->get_by_id($routing_data['infoblock_id'], 'Class_ID');
        }
        return join(', ', $component_ids) ?: '0';
    }

    /**
     * @param int|null $sub
     * @return string
     */
    protected function get_parent_subdivision_ids_for_area_conditions($sub) {
        $parent_subdivision_ids = array();
        if ($sub) {
            $parent_tree = (array)nc_core::get_object()->subdivision->get_parent_tree($sub);
            foreach ($parent_tree as $parent) {
                if (!empty($parent['Subdivision_ID'])) {
                    $parent_subdivision_ids[] = $parent['Subdivision_ID'];
                }
            }
        }
        return join(', ', $parent_subdivision_ids) ?: '0';
    }

    /**
     * @param array $routing_data
     * @return string
     */
    protected function generate_area_action_condition(array $routing_data) {
        $action = nc_array_value($routing_data, 'action', 'index');
        if (!in_array($action, array('add', 'delete', 'edit', 'full', 'index', 'search', 'subscribe'))) {
            return '';
        }
        return " AND `block`.`AreaCondition_Action_$action` = 1 ";
    }

    /**
     * @param string $component_ids
     * @return string
     */
    protected function generate_area_component_condition($component_ids) {
        // (1) компоненты, которые не должны находиться в основной контентной области, чтобы блок был показан:
        $sql_condition = ' AND `not_component`.`Condition_ID` IS NULL';
        // (2) компоненты, которые должны быть в основной контентной области:
        $sql_condition .= " AND (`component`.`Condition_ID` IS NULL OR `component`.`Class_ID` IN ($component_ids)) ";

        return $sql_condition;
    }

    /**
     * @param int|null $sub
     * @param string $parent_subdivision_ids
     * @return string
     */
    protected function generate_area_excluded_subdivision_join_condition($sub, $parent_subdivision_ids) {
        if (!$sub) {
            return '';
        }

        return "AND (" .
               "`not_sub`.`Subdivision_ID` = $sub " .
               " OR (`not_sub`.`Subdivision_ID` IN ($parent_subdivision_ids) AND `not_sub`.`IncludeChildren` = 1)" .
               ")";
    }

    /**
     * @param int|null $sub
     * @param string $parent_subdivision_ids
     * @return string
     */
    protected function generate_area_subdivision_condition($sub, $parent_subdivision_ids) {
        if (!$sub) {
            return '';
        }

        return " AND `not_sub`.`Condition_ID` IS NULL" .
               " AND (" .
               "`sub`.`Condition_ID` IS NULL" .
               " OR `sub`.`Subdivision_ID` = $sub" .
               " OR (`sub`.`Subdivision_ID` IN ($parent_subdivision_ids) AND `sub`.`IncludeChildren` = 1)" .
               ") ";
    }

    /**
     * @param array $routing_data
     * @return string
     */
    protected function generate_area_object_condition(array $routing_data) {
        if (empty($routing_data['object_id']) || empty($routing_data['infoblock_id'])) {
            return '';
        }

        $component_id = $this->get_by_id($routing_data['infoblock_id'], 'Class_ID');

        return " AND (`object`.`Condition_ID` IS NULL OR (" .
               "`object`.`Class_ID` = $component_id AND `object`.`Message_ID` = $routing_data[object_id]" .
               ")) ";
    }

    /**
     * Пока не является частью публичного API: возможен рефакторинг.
     *
     * Возвращает все (в т. ч. выключенные) блоки в контейнере с указанным идентификатором
     * Для блоков в области с учётом условий можно также использовать метод
     * get_by_area_keyword с указанием ID родительского контейнера.
     *
     * @param int $container_id
     * @param bool $reset
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function get_by_container_id($container_id, $reset = false) {
        $nc_core = nc_core::get_object();

        $container_id = (int)$container_id;
        if (!$container_id) {
            // без указания родителя не работает
            throw new InvalidArgumentException('$container_id must have a non-zero value');
        }

        $area_keyword = $this->get_by_id($container_id, 'AreaKeyword');
        if ($area_keyword) {
            return $this->get_by_area_keyword($area_keyword, $nc_core->page->get_routing_result(), $container_id, $reset);
        }

        $result = false;
        if (!$reset) {
            foreach ($this->container_cache as $condition => $rows) {
                if (isset($this->container_cache[$condition][$container_id])) {
                    $result = $this->container_cache[$condition][$container_id];
                    break;
                }
            }
        }

        if ($result === false) { // $reset = true, либо в кэше нет информации по этому контейнеру
            $raw_data = (array)$nc_core->db->get_results(
                "SELECT *, IF(`b`.`Class_Template_ID` != 0 AND `b`.`Class_ID` != `b`.`Class_Template_ID`, `b`.`Class_Template_ID`, `c`.`Main_ClassTemplate_ID`) AS `Class_Template_ID` 
                   FROM `Sub_Class` AS `b`
                   LEFT JOIN `Class` AS `c` ON `b`.`Class_ID` = `c`.`Class_ID`
                  WHERE `b`.`Parent_Sub_Class_ID` = $container_id
                  ORDER BY `b`.`Priority`, `b`.`Sub_Class_ID`",
                ARRAY_A
            );
            $this->set_cache('C', $raw_data);
            $result = isset($this->container_cache['C'][$container_id])
                          ? $this->container_cache['C'][$container_id]
                          : array();
        }

        return $result;
    }

    /**
     * Get first 'checked' subclass ID in a subdivision
     */
    public function get_first_checked_id_by_subdivision_id($id = 0, $reset = false) {
        $id = intval($id);

        if (!isset($this->subdivision_first_checked_cache[$id]) || $reset) {
            $this->subdivision_first_checked_cache[$id] = false;

            $subclasses = $this->get_by_subdivision_id($id, $reset);
            if ($subclasses) {
                foreach ($subclasses as $subclass) {
                    if ($subclass['Checked']) {
                        $this->subdivision_first_checked_cache[$id] = $subclass['Sub_Class_ID'];
                        break;
                    }
                }
            }
        }

        return $this->subdivision_first_checked_cache[$id];
    }

    /**
     * Set current subclass data by the id
     *
     * @param int $id subclass id
     * @param bool $reset reset stored data in the static variable
     *
     * @return array|false current cc id that was set
     */

    public function set_current_by_id($id, $reset = false) {

        // validate
        $id = intval($id);
        if (!$id) {
            return ($this->current = array());
        }
        try {
            //if ($id) {
            $this->current = $this->get_by_id($id, "");
            // set additional data
            $this->_current_id = $id;
            // return result
            return $this->current;
            //}
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), 'error');
        }

        // reject
        return false;
    }

    /**
     * @param $id
     * @param string $item
     * @param int $nc_ctpl
     * @param int $reset
     * @param string $type
     * @return null|string|array
     * @throws Exception
     */
    public function get_by_id($id, $item = "", $nc_ctpl = 0, $reset = 0, $type = '') {
        $storage = &$this->ctpl_cache;

        // validate parameters
        $id = intval($id);
        //$nc_ctpl = intval($nc_ctpl);
        // check initialized object
        if (empty($storage[$id][$nc_ctpl]) || $reset) {
            if (!empty($this->data[$id]) && !$reset) {
                $storage[$id][$nc_ctpl] = $this->data[$id];
            }
            else {
                nc_core::get_object()->clear_cache_on_low_memory();
                $this->data[$id] = $this->db->get_row(
                    "SELECT `b`.*, IF(`b`.`Class_Template_ID` != 0 AND `b`.`Class_ID` != `b`.`Class_Template_ID`, `b`.`Class_Template_ID`, `c`.`Main_ClassTemplate_ID`) AS `Class_Template_ID`
                     FROM `" . $this->essence . "` AS `b`
                     LEFT JOIN `Class` AS `c` ON `b`.`Class_ID` = `c`.`Class_ID`
                     WHERE `" . $this->essence . "_ID` = '" . $id . "'",
                    ARRAY_A
                );
                if (empty($this->data[$id])) {
                    //return false;
                    throw new Exception("Sub_Class with id  " . $id . " does not exist");
                }
                $real_value = array('Read_Access_ID', 'Write_Access_ID', 'Edit_Access_ID', 'Delete_Access_ID', 'Checked_Access_ID', 'Moderation_ID', 'Cache_Access_ID', 'Cache_Lifetime');
                foreach ($real_value as $v) {
                    $this->data[$id]['_db_' . $v] = $this->data[$id][$v];
                }
                $storage[$id][$nc_ctpl] = $this->data[$id];
            }

            $storage[$id][$nc_ctpl] = $this->inherit($storage[$id][$nc_ctpl], $nc_ctpl, $type);
        }
        else {
            // Если указан другой тип шаблона, чем тот, что уже был найден, нужно подобрать шаблон заново
            if ($type && $type != $storage[$id][$nc_ctpl]['Type']) {
                $properties_to_reset = array(
                    'FormPrefix', 'FormSuffix', 'RecordTemplate', 'RecordTemplateFull',
                    'TitleTemplate', 'TitleList', 'UseAltTitle', 'AddTemplate', 'EditTemplate',
                    'AddActionTemplate', 'EditActionTemplate', 'SearchTemplate',
                    'FullSearchTemplate', 'SubscribeTemplate', 'Settings',
                    'AddCond', 'EditCond', 'DeleteCond', 'CheckActionTemplate',
                    'DeleteActionTemplate', 'CustomSettingsTemplate',
                    'ClassDescription', 'DeleteTemplate', 'ClassTemplate',
                    'Type', 'File_Mode', 'File_Path', 'File_Hash', 'Real_Class_ID'
                );

                foreach ($properties_to_reset as $k) {
                    unset($storage[$id][$nc_ctpl][$k]);
                }

                $storage[$id][$nc_ctpl] = $this->inherit($storage[$id][$nc_ctpl], $nc_ctpl, $type);
            }
        }

        // if item requested return item value
        if ($item && is_array($storage[$id][$nc_ctpl])) {
            return array_key_exists($item, $storage[$id][$nc_ctpl]) ? $storage[$id][$nc_ctpl][$item] : "";
        }

        return $storage[$id][$nc_ctpl];
    }

    /**
     * Добавляет к переданному массиву $cc_env параметры шаблона компонента.
     * Должно быть передано три аргумента:
     *     array $cc_env — массив с параметрами инфоблока
     *     string|int $nc_ctpl — идентификатор или ключевое слово шаблона компонента
     *     string $type — тип шаблона компонента (значение в поле Class.Type)
     *
     * @param $cc_env
     * @return array|null
     */
    protected function inherit($cc_env) {
        if (empty($cc_env)) {
            global $perm;
            if ($perm instanceof Permission && $perm->isSupervisor()) {
                $debug_backtrace_info = debug_backtrace();
                if (isset($debug_backtrace_info[2]['function']) && $debug_backtrace_info[2]['function'] === 'nc_objects_list') {
                    trigger_error(sprintf(NETCAT_FUNCTION_OBJECTS_LIST_CC_ERROR, $debug_backtrace_info[2]['args'][1]), E_USER_WARNING);
                } else {
                    trigger_error(sprintf(NETCAT_FUNCTION_LISTCLASSVARS_ERROR_SUPERVISOR, nc_array_value($GLOBALS, 'cc', '?')), E_USER_WARNING);
                }
            }
            return null;
        }

        $nc_core = nc_core::get_object();

        if ($cc_env['Class_ID']) {
            $args = func_get_args();
            if (count($args) !== 3) {
                trigger_error(__METHOD__ . ' requires 3 arguments', E_USER_WARNING);
            }
            list($cc_env, $nc_ctpl, $type) = func_get_args();

            $nc_tpl_in_cc = 0;
            if ($cc_env['Class_Template_ID'] && !$nc_ctpl) {
                $nc_tpl_in_cc = $cc_env['Class_Template_ID'];
            }

            $class_env = $nc_core->component->get_for_cc($cc_env['Sub_Class_ID'], $cc_env['Class_ID'], $nc_ctpl, $nc_tpl_in_cc, $type);

            foreach ((array)$class_env as $key => $val) {
                if (!array_key_exists($key, $cc_env) || $cc_env[$key] == "") {
                    $cc_env[$key] = $val;
                }
            }

            if ($cc_env["NL2BR"] == -1) {
                $cc_env["NL2BR"] = nc_array_value($class_env, "NL2BR");
            }

            if ($cc_env["AllowTags"] == -1) {
                $cc_env["AllowTags"] = nc_array_value($class_env, "AllowTags");
            }

            if ($cc_env["UseCaptcha"] == -1) {
                $cc_env["UseCaptcha"] = nc_array_value($class_env, "UseCaptcha");
            }

            foreach (array('Index', 'IndexItem') as $mixin_scope) {
                if ($cc_env["{$mixin_scope}_Mixin_Preset_ID"] == -1) {
                    $cc_env["{$mixin_scope}_Mixin_Preset_ID"] = nc_array_value($class_env, "{$mixin_scope}_Mixin_Preset_ID");
                }
            }

            if ($cc_env['MinRecordsInInfoblock'] === null) {
                $cc_env['sysTbl'] = (int)nc_array_value($class_env, 'System_Table_ID');
                $cc_env['MinRecordsInInfoblock'] = nc_array_value($class_env, 'MinRecordsInInfoblock');
            }

            if ($cc_env['MaxRecordsInInfoblock'] === null) {
                $cc_env['MaxRecordsInInfoblock'] = nc_array_value($class_env, 'MaxRecordsInInfoblock');
            }

            if ($nc_core->modules->get_by_keyword("cache")) {
                if ($cc_env["CacheForUser"] == -1) {
                    $cc_env["CacheForUser"] = nc_array_value($class_env, "CacheForUser");
                }
            }

            if (nc_array_value($class_env, 'CustomSettingsTemplate')) {
                $a2f = new nc_a2f($class_env['CustomSettingsTemplate'], 'CustomSettings', false, $cc_env['Sub_Class_ID'], 'class_settings');
                $a2f->set_value($cc_env['CustomSettings']);
                $cc_env["Sub_Class_Settings"] = $a2f->get_values_as_array();
            }

            $cc_env['sysTbl'] = (int)nc_array_value($class_env, 'System_Table_ID');
        }

        if ($cc_env['Subdivision_ID']) {
            $parent_env = $nc_core->subdivision->get_by_id($cc_env['Subdivision_ID']);
            $cc_env['Subdivision_Name'] = $parent_env['Subdivision_Name'];
            $cc_env['Hidden_URL'] = $parent_env['Hidden_URL'];
        } else if ($cc_env['Catalogue_ID']) {
            $parent_env = $nc_core->catalogue->get_by_id($cc_env['Catalogue_ID']);
        }

        $inherited_params = array(
            'Read_Access_ID', 'Write_Access_ID', 'Edit_Access_ID', 'Checked_Access_ID',
            'Delete_Access_ID', 'Subscribe_Access_ID', 'Moderation_ID',
            'Cache_Access_ID', 'Cache_Lifetime'
        );

        if (!empty($parent_env)) {
            foreach ($inherited_params as $v) {
                if (!$cc_env[$v]) {
                    $cc_env[$v] = $parent_env[$v];
                }
            }
        }

        try {
            $cc_env['Hidden_Host'] = $nc_core->catalogue->get_by_id($cc_env['Catalogue_ID'], 'Domain') ?: $_SERVER['HTTP_HOST'];
        } catch (Exception $e) {
            $cc_env['Hidden_Host'] = $_SERVER['HTTP_HOST'];
        }

        return $cc_env;
    }

    /**
     * @param $str
     * @return int
     */
    public function validate_english_name($str) {
        // Check string length: database scheme stores up to 64 characters.
        if (mb_strlen($str) > self::MAX_KEYWORD_LENGTH) {
            return 0;
        }
        // validate Hidden_URL
        return nc_preg_match('/^[\w' . NETCAT_RUALPHABET . '-]+$/', $str);
    }


    /**
     * Проверяет, является ли EnglishName уникальным для инфоблока в указанном разделе
     *
     * @param $subdivision_id
     * @param $infoblock_id
     * @param $english_name
     * @return bool
     */
    public function is_english_name_unique_in_subdivision($subdivision_id, $infoblock_id, $english_name) {
        $db = nc_db();
        return !$db->get_var(
            "SELECT 1
               FROM `Sub_Class`
              WHERE `EnglishName` = '" . $db->escape($english_name) . "'
                AND `Subdivision_ID` = " . (int)$subdivision_id . "
                AND `Sub_Class_ID` != " . (int)$infoblock_id
        );
    }

    /**
     * @param $id
     * @param string $item
     * @return array|string
     */
    public function get_mirror($id, $item = '') {
        $res = array();
        foreach ($this->data as $v) {
            if ($v['SrcMirror'] == $id) {
                if ($item) {
                    return array_key_exists($item, $v) ? $v[$item] : "";
                }
                $res = $v;
            }
        }

        return $res;
    }

    /**
     * Возвращает Priority для создаваемого или перемещаемого блока;
     * «раздвигает» приоритеты блоков-сиблингов по необходимости
     *
     * @param array $destination_and_priority данные инфоблока. Обязательно должны присутствовать:
     *     Catalogue_ID, Subdivision_ID, Parent_Sub_Class_ID, AreaKeyword (расположение блока).
     *     Если Priority не указан, будет возвращён максимальный приоритет в расположении блока + 1.
     *     Если Priority — число, блоки с таким и бо́льшим приоритетом будут сдвинуты на 1.
     *     Если Priority — массив с элементами ['Position' => 'before' или 'after', 'Sub_Class_ID'],
     *     то соответствующим образом будет вычислен приоритет и сдвинуты блоки.
     * @return int приоритет блока
     * @throws InvalidArgumentException если указано неизвестное значение в $destination_and_priority['Priority']['Position']
     * @throws Exception если инфоблок, указанный в $destination_and_priority['Priority']['Sub_Class_ID'] не найден
     */
    protected function get_priority_and_prepare_place_for_infoblock(array $destination_and_priority) {
        $nc_core = nc_core::get_object();
        $area_keyword = $nc_core->db->escape(nc_array_value($destination_and_priority, 'AreaKeyword'));
        $is_full_page_mode = $nc_core->component->is_full_page_area($area_keyword);
        $catalogue_ids = array((int)$destination_and_priority['Catalogue_ID']);

        if ($is_full_page_mode) {
            $catalogue_ids[] = 0;
        }

        // Установка приоритета
        $location_condition =
            '`Catalogue_ID` IN (' . implode(', ', array_unique($catalogue_ids)) . ')' . '
            AND `Subdivision_ID` = ' . (int)nc_array_value($destination_and_priority, 'Subdivision_ID') . '
            AND `Parent_Sub_Class_ID` = ' . (int)nc_array_value($destination_and_priority, 'Parent_Sub_Class_ID') . "
            AND `AreaKeyword` = '" . $area_keyword . "'";

        $priority = nc_array_value($destination_and_priority, 'Priority');

        // Если в качестве Priority передан массив с элементами 'Position', 'Sub_Class_ID',
        // то вычисляем приоритет блока на основе этих данных
        if (is_array($priority)) {
            $position = nc_array_value($priority, 'Position');
            if ($position !== 'before' && $position !== 'after') {
                throw new InvalidArgumentException("Invalid \$destination_and_priority['Priority']['Position'] value ('$position')");
            }

            // Получаем приоритет reference-блока, попутно проверив его расположение
            $reference_priority = $this->db->get_var(
                'SELECT `Priority` 
                   FROM `Sub_Class`
                  WHERE `Sub_Class_ID` = ' . (int)nc_array_value($priority, 'Sub_Class_ID') . "
                    AND $location_condition
                  LIMIT 1"
            );

            if ($reference_priority === null) {
                throw new Exception("Infoblock '$priority[Sub_Class_ID]' does not exist or is not located at the specified subdivision or container");
            }

            // Фактический приоритет = приоритету reference (position = before) или приоритету reference + 1 (position = after)
            // (далее $priority будет integer, а не array)
            $priority = $reference_priority + ($position === 'after' ? 1 : 0);
        }

        if (is_numeric($priority)) {
            $nc_core->db->query(
                "UPDATE `Sub_Class` 
                    SET `Priority` = `Priority` + 1 
                  WHERE $location_condition
                    AND `Priority` >= " . (int)$priority);
        } else {
            $priority = 1 + $nc_core->db->get_var(
                "SELECT MAX(`Priority`) FROM `Sub_Class` WHERE $location_condition"
            );
        }

        return $priority;
    }

    /**
     * Сдвигает на 1 «вверх» приоритеты блоков, следовавших за перемещённым (или удалённым) блоком
     *
     * @param array $removed_infoblock_data свойства инфоблока, удалённого из старого расположения
     */
    protected function update_priorities_after_block_removal(array $removed_infoblock_data) {
        $table = nc_db_table::make('Sub_Class');

        $location = array(
            'Catalogue_ID' => $removed_infoblock_data['Catalogue_ID'],
            'Subdivision_ID' => $removed_infoblock_data['Subdivision_ID'],
            'Parent_Sub_Class_ID' => $removed_infoblock_data['Parent_Sub_Class_ID'],
            'AreaKeyword' => $removed_infoblock_data['AreaKeyword'],
        );

        // Если в старом месте по какой-то причине есть другой блок с тем же приоритетом,
        // то ничего не трогаем, так как станет ещё хуже
        $has_block_with_same_priority =
            $table
                ->select(1)
                ->where($location)
                ->where('Priority', $removed_infoblock_data['Priority'])
                ->limit(1)
                ->get_value();

        if (!$has_block_with_same_priority) {
            $table
                ->where($location)
                ->where('Priority', '>', $removed_infoblock_data['Priority'])
                ->update(array('Priority' => new nc_db_expression('`Priority` - 1')));
        }
    }

    /**
     * Создаёт инфоблок.
     *
     * @param int|string $component_id_or_keyword Идентификатор или ключевое слово компонента
     * @param array $properties  Свойства инфоблока
     *      Subdivision_ID — должно быть указано
     *      EnglishName —
     *          Если не указано, транслитерируется из Sub_Class_Name.
     *          Если уже существует, добавляется суффикс "-1", "-2" и т.п.
     *      Priority —
     *          Если не указано, то следующий по порядку приоритет в родительском разделе.
     *          Если указано число, у последующих блоков приоритет будет сдвинут на 1.
     *          Если массив с элементами ['Position' => 'before' или 'after', 'Sub_Class_ID' => ID],
     *          то блок будет расположен до или после указанного инфоблока
     * @param array $custom_settings  Пользовательские настройки компонента для инфоблока
     * @return int  ID созданного инфоблока
     * @throws Exception когда не найден родительский раздел или компонент; при ошибке создания инфоблока;
     *      при неправильном значении 'Priority'
     */
    public function create($component_id_or_keyword, array $properties, array $custom_settings = null) {
        $nc_core = nc_core::get_object();

        $subdivision_id = $properties['Subdivision_ID'] = (int)nc_array_value($properties, 'Subdivision_ID');
        $parent_infoblock_id = (int)nc_array_value($properties, 'Parent_Sub_Class_ID');

        $site_id = 0;
        if ($subdivision_id) {
            $site_id = $nc_core->subdivision->get_by_id($subdivision_id, 'Catalogue_ID');
        } else if ($parent_infoblock_id) {
            $site_id = $nc_core->sub_class->get_by_id($parent_infoblock_id, 'Catalogue_ID');
        }

        if ($component_id_or_keyword) {
            // преобразование ключевого слова в ID; гарантирует существование компонента
            $component_id = $nc_core->component->get_by_id($component_id_or_keyword, 'Class_ID');
            $default_name = $nc_core->component->get_by_id($component_id, 'Class_Name');
            $default_keyword =
                nc_array_value($properties, 'Sub_Class_Name')
                ?: $nc_core->component->get_by_id($component_id, 'Keyword')
                ?: 'block';
            $default_keyword = strtolower(nc_transliterate($default_keyword, true));
        } else {
            // нет $component_id — значит создаём контейнер
            $component_id = 0;
            $default_name = '';
            // значение связки (Subdivision_ID, EnglishName) должно быть уникальным:
            $default_keyword = '_' . str_replace('.', '_', uniqid('', true));
        }

        $nc_core->version->update_changeset_description(NETCAT_VERSION_ACTION_CREATED . " " . sprintf(NETCAT_VERSION_OF_BLOCK, $default_name));

        if (empty($properties['EnglishName'])) {
            unset($properties['EnglishName']);
        }

        // Значения по умолчанию
        $now = new nc_db_expression("NOW()");

        $defaults = array(
            'Sub_Class_Type' => ($component_id ? 'object_list' : 'container'),
            'Class_ID' => $component_id,
            'Catalogue_ID' => $site_id,
            'Subdivision_ID' => 0,
            'AreaKeyword' => '',
            'Parent_Sub_Class_ID' => 0,
            'Sub_Class_Name' => $default_name,
            'EnglishName' => $default_keyword,
            'Class_Template_ID' => 0,
            'LastUpdated' => $now,
            'Created' => $now,
            'Checked' => 1,
        );

        if ($parent_infoblock_id) {
            $parent_infoblock_data = $this->get_by_id($parent_infoblock_id);
            $defaults['Subdivision_ID'] = $parent_infoblock_data['Subdivision_ID'];
            $defaults['AreaKeyword'] = $parent_infoblock_data['AreaKeyword'];
        }

        foreach ($defaults as $field => $default_value) {
            if (!isset($properties[$field])) {
                $properties[$field] = $default_value;
            }
        }

        // Если есть AreaKeyword, то Subdivision_ID должен быть 0
        if (!empty($properties['AreaKeyword'])) {
            $subdivision_id = $properties['Subdivision_ID'] = 0;
        }

        // Если область полного вывода объекта, то Catalogue_ID должен быть 0
        if ($nc_core->component->is_full_page_area($properties['AreaKeyword'])) {
            $properties['Catalogue_ID'] = 0;
        }

        // Установка EnglishName
        $properties['EnglishName'] = $this->get_available_english_name($subdivision_id, $properties['EnglishName']);

        if (!$this->validate_english_name($properties['EnglishName'])) {
            throw new Exception(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID);
        }

        // Установка приоритета
        $properties['Priority'] = $this->get_priority_and_prepare_place_for_infoblock($properties);

        // Пользовательские настройки шаблона компонента
        if ($component_id) {
            $component_custom_settings = $nc_core->component->get_by_id($properties['Class_Template_ID'] ?: $component_id, 'CustomSettingsTemplate');
            if ($component_custom_settings) {
                $a2f = new nc_a2f($component_custom_settings, 'CustomSettings');
                $a2f->set_initial_values();

                if (isset($properties['CustomSettings'])) {
                    $a2f->set_values($properties['CustomSettings']);
                }

                if ($custom_settings) {
                    $a2f->set_values($custom_settings);
                }

                if (!$a2f->validate($a2f->get_values_as_array())) {
                    throw new Exception($a2f->get_validation_errors());
                }

                $a2f->save($custom_settings);

                $properties['CustomSettings'] = $a2f->get_values_as_string();
            }
        }

        $nc_core->event->execute(nc_event::BEFORE_INFOBLOCK_CREATED, $site_id, $subdivision_id, null);
        $infoblock_id = nc_db_table::make('Sub_Class')->insert($properties);

        if (!$infoblock_id) {
            throw new Exception("Unable to create infoblock\n" . $nc_core->db->last_error);
        }

        if (!empty($properties['Condition'])) {
            $infoblock_condition_translator = new nc_condition_infoblock_translator($properties['Condition'], $infoblock_id);
            $infoblock_condition_query = $nc_core->db->escape($infoblock_condition_translator->get_sql_condition());
            $nc_core->db->query("UPDATE `Sub_Class` SET `ConditionQuery` = '$infoblock_condition_query' WHERE `Sub_Class_ID` = '$infoblock_id'");
        }

        $nc_core->event->execute(nc_event::AFTER_INFOBLOCK_CREATED, $site_id, $subdivision_id, $infoblock_id);
        nc_tpl_mixin_cache::generate_block_files($infoblock_id, false);

        return $infoblock_id;
    }

    /**
     * Возвращает доступное ключевое слово инфоблока в указанном разделе, добавляя
     * при необходимости суффикс "-1", "-2" и т. п.
     * @param $subdivision_id
     * @param $desired_english_name
     * @return string
     */
    public function get_available_english_name($subdivision_id, $desired_english_name) {
        $english_name = nc_substr($desired_english_name, 0, self::MAX_KEYWORD_LENGTH);
        // В старых версиях могли (например, при копировании блоков) создаваться ключевые слова
        // вида -5-1-1-1 (практически никогда не доходило до двузначных):
        $english_name_without_suffix = preg_replace('/(-\d)*(-\d+)?$/', '', $english_name);
        $suffix = 1;

        $nc_core = nc_core::get_object();
        while ($nc_core->db->get_var(
            "SELECT 1
               FROM `Sub_Class`
              WHERE `Subdivision_ID` = " . (int)$subdivision_id . "
                AND `EnglishName` = '" . $nc_core->db->escape($english_name) . "'"
        )) {
            $english_name = nc_substr($english_name_without_suffix, 0, self::MAX_KEYWORD_LENGTH - 1 - strlen($suffix)) . '-' . ($suffix++);
        }

        return $english_name;
    }

    /**
     * Копирование инфоблока
     * @param int $source_infoblock_id
     * @param array $destination массив с элементами Catalogue_ID, Subdivision_ID, Parent_Sub_Class_ID, AreaKeyword, Priority.
     *      Элемент Priority может быть:
     *      — массивом с ключами 'Position' ('before' или 'after'), 'Sub_Class_ID', если нужно разместить копируемый
     *        блок относительно другого блока;
     *      — числом, если надо блок разместить в этом положении (блоки с бо́льшим приоритетом будут сдвинуты);
     *      — false, если не нужно менять приоритет копируемого блока;
     *      — не указан, если блок должен быть последним в родительском контейнере.
     *
     * @param bool $copy_objects
     * @return int ID созданного инфоблока
     * @throws Exception
     */
    public function duplicate($source_infoblock_id, array $destination, $copy_objects = true) {
        global $perm;

        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        $source_infoblock_id = (int)$source_infoblock_id;
        $source_infoblock_properties = $db->get_row(
            "SELECT * FROM `Sub_Class` WHERE `Sub_Class_ID` = $source_infoblock_id",
            ARRAY_A
        );

        if (!$source_infoblock_properties) {
            throw new Exception("Infoblock $source_infoblock_id does not exist");
        }

        $component_id = $source_infoblock_properties['Class_ID'];

        $new_infoblock_data = $source_infoblock_properties;
        unset(
            $new_infoblock_data['Sub_Class_ID'],
            $new_infoblock_data['Priority'],
            $new_infoblock_data['ncDisallowMoveAndDelete']
        );

        if (strpos($source_infoblock_properties['EnglishName'], '_library_') === 0) {
            $new_infoblock_data['Source_Keyword'] = $source_infoblock_properties['EnglishName'];
            unset($new_infoblock_data['EnglishName']);
        }

        foreach ($destination as $key => $value) {
            $new_infoblock_data[$key] = $value;
        }

        // Если блок копирует пользователь без админских прав, то сбрасываем флаг запрета удаления
        if ($perm instanceof Permission && !$perm->isSupervisor()) {
            $new_infoblock_data['ncDisallowMoveAndDelete'] = 0;
        }

        $new_infoblock_id = $this->create($component_id, $new_infoblock_data);

        if ($component_id) {
            if ($copy_objects) {
                $object_ids = $db->get_col("SELECT `Message_ID` FROM `Message{$component_id}` WHERE `Sub_Class_ID` = $source_infoblock_id");
                foreach ($object_ids as $object_id) {
                    $nc_core->message->duplicate($component_id, $object_id, $new_infoblock_id);
                }
            }
        } else {
            // копирование содержимого контейнера:
            $children_ids = $db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Parent_Sub_Class_ID` = $source_infoblock_id ORDER BY `Priority`, `Sub_Class_ID`");
            $child_destination = $destination;
            $child_destination['Parent_Sub_Class_ID'] = $new_infoblock_id;
            $child_destination['Priority'] = false; // не менять приоритеты блоков внутри контейнера
            foreach ($children_ids as $child_id) {
                $this->duplicate($child_id, $child_destination, $copy_objects);
            }
        }

        // Копирование областей, привязанных к инфоблоку
        $area_blocks = (array)$db->get_results(
            "SELECT `Sub_Class_ID`, `AreaKeyword`
               FROM `Sub_Class`
              WHERE `Catalogue_ID` = $source_infoblock_properties[Catalogue_ID]
                AND `Parent_Sub_Class_ID` = 0
                AND `AreaKeyword` LIKE '%_cc_{$source_infoblock_id}'",
            ARRAY_A
        );

        foreach ($area_blocks as $area_block) {
            $area_block_destination = array(
                'Catalogue_ID' => $new_infoblock_data['Catalogue_ID'],
                'Subdivision_ID' => 0,
                'Parent_Sub_Class_ID' => 0,
                'AreaKeyword' => str_replace("_cc_{$source_infoblock_id}", "_cc_{$new_infoblock_id}", $area_block['AreaKeyword']),
                'Priority' => false,
            );
            $this->duplicate($area_block['Sub_Class_ID'], $area_block_destination, $copy_objects);
        }

        // Копирование настроек форм модуля заявок
        if (nc_module_check_by_keyword('requests')) {
            nc_requests_form_settings_infoblock::duplicate_settings_for_all_form_types($source_infoblock_id, $new_infoblock_id);
        }

        return $new_infoblock_id;
    }

    /**
     * Изменение расположения инфоблока
     *
     * @param int $infoblock_id
     * @param array $destination массив с элементами Catalogue_ID, Subdivision_ID, Parent_Sub_Class_ID, AreaKeyword, Priority.
     *      Внимание: переданные значения сейчас никак не проверяются в отношении их целостности,
     *      неправильные значения могут привести к нарушению структуры в БД.
     *      Элемент Priority может быть:
     *      — массивом с ключами 'Position' ('before' или 'after'), 'Sub_Class_ID', если нужно разместить перемещаемый
     *        блок относительно другого блока;
     *      — числом, если надо блок разместить в этом положении (блоки с бо́льшим приоритетом будут сдвинуты);
     *      — false, если не нужно менять приоритет перемещаемого блока;
     *      — не указан, если блок должен быть последним в родительском контейнере.
     *
     * @param bool $update_sibling_priorities_at_old_location сдвигать ли приоритеты последующих блоков в старом
     *      расположении блока
     * @throws Exception
     */
    public function move($infoblock_id, array $destination, $update_sibling_priorities_at_old_location = true) {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        $table = nc_db_table::make('Sub_Class');

        $old_values = (array)$table->where_id($infoblock_id)->get_row();

        if (!$old_values) {
            throw new Exception("Infoblock $infoblock_id does not exist");
        }

        // Определение полных данных о новом расположении блока.
        // Если есть Subdivision_ID или Parent_Sub_Class_ID, сайт определяется по ним;
        // иначе должен быть передан элемент Catalogue_ID (сквозной инфоблок первого уровня)
        if (!empty($destination['Subdivision_ID'])) {
            $site_id = $nc_core->subdivision->get_by_id($destination['Subdivision_ID'], 'Catalogue_ID');
        } else if (!empty($destination['Parent_Sub_Class_ID'])) {
            $site_id = $nc_core->sub_class->get_by_id($destination['Parent_Sub_Class_ID'], 'Catalogue_ID');
        } else {
            $site_id = (int)nc_array_value($destination, 'Catalogue_ID') ?: $nc_core->catalogue->get_current('Catalogue_ID');
        }

        if (!empty($destination['AreaKeyword'])) {
            $destination['Subdivision_ID'] = 0;
        }

        $area_keyword = nc_array_value($destination, 'AreaKeyword', '');
        $is_full_page_mode = $nc_core->component->is_full_page_area($area_keyword);

        // Массив со свойствами, которые будут обновлены у перемещаемого блока
        $new_values = array(
            'Catalogue_ID' => $is_full_page_mode ? 0 : $site_id,
            'Subdivision_ID' => (int)nc_array_value($destination, 'Subdivision_ID', 0),
            'Parent_Sub_Class_ID' => (int)nc_array_value($destination, 'Parent_Sub_Class_ID', 0),
            'AreaKeyword' => nc_array_value($destination, 'AreaKeyword', ''),
        );

        $nc_core->version->update_changeset_description(
            NETCAT_VERSION_ACTION_MOVED . " " . sprintf(NETCAT_VERSION_OF_BLOCK, $old_values['Sub_Class_Name'])
        );

        // Новый приоритет
        if (nc_array_value($destination, 'Priority') !== false) {
            $new_values['Priority'] = $this->get_priority_and_prepare_place_for_infoblock($destination);
        }

        // Проверка возможности использования ключевого слова в новом месте
        if ($new_values['Subdivision_ID'] != $old_values['Subdivision_ID']) {
            $new_values['EnglishName'] = $this->get_available_english_name($new_values['Subdivision_ID'], $old_values['EnglishName']);
        }

        // Событие «до»
        $nc_core->event->execute(nc_event::BEFORE_INFOBLOCK_UPDATED, $old_values['Catalogue_ID'], $old_values['Subdivision_ID'], $infoblock_id);

        // Собственно обновление данных
        $table->where_id($infoblock_id)->update($new_values);

        // Событие «после»
        $nc_core->event->execute(nc_event::AFTER_INFOBLOCK_UPDATED, $new_values['Catalogue_ID'], $new_values['Subdivision_ID'], $infoblock_id);

        if ($site_id != $old_values['Catalogue_ID']) {
            nc_tpl_mixin_cache::delete_block_files($old_values['Catalogue_ID'], $infoblock_id);
        }
        nc_tpl_mixin_cache::generate_block_files($infoblock_id, false);

        if (!$old_values['Class_ID']) {
            // Перемещение блоков внутри контейнера при перемещении контейнера
            $children_ids = $db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Parent_Sub_Class_ID` = $infoblock_id");
            $child_destination = $destination;
            $child_destination['Parent_Sub_Class_ID'] = $infoblock_id;
            $child_destination['Priority'] = false; // не менять приоритеты блоков внутри контейнера
            foreach ($children_ids as $child_id) {
                $this->move($child_id, $child_destination, false);
            }
        } else if ($new_values['Subdivision_ID'] != $old_values['Subdivision_ID']) {
            // Изменение Subdivision_ID у объектов (событие не вызывается, время изменения не меняется)
            $db->query(
                "UPDATE `Message$old_values[Class_ID]`
                    SET `Subdivision_ID` = $new_values[Subdivision_ID],
                        `LastUpdated` = `LastUpdated`
                  WHERE `Sub_Class_ID` = $infoblock_id"
            );
        }

        // Сдвиг приоритетов сиблингов в старом месте
        if ($update_sibling_priorities_at_old_location) {
            $this->update_priorities_after_block_removal($old_values);
        }

        $this->clear_cache();
    }

    /**
     * Удаление инфоблока
     * @param int $infoblock_id
     * @return bool
     * @throws Exception
     */
    public function delete($infoblock_id) {
        $nc_core = nc_core::get_object();

        $nc_core->version->init_changeset(
            NETCAT_VERSION_ACTION_DELETED . ' ' .
            sprintf(NETCAT_VERSION_OF_BLOCK, $this->get_by_id($infoblock_id, 'Sub_Class_Name'))
        );

        $result = $this->delete_recursively($infoblock_id, true);

        $nc_core->version->commit_changeset();

        return $result;
    }

    /**
     * Удаление инфоблока (метод для того, чтобы спрятать внутренний аргумент $update_sibling_priorities)
     * @param int $infoblock_id
     * @param bool $update_sibling_priorities
     * @return bool
     * @throws nc_Exception_DB_Error
     */
    protected function delete_recursively($infoblock_id, $update_sibling_priorities) {
        $infoblock_id = (int)$infoblock_id;
        $nc_core = nc_core::get_object();
        $previous_db_error = $nc_core->db->last_error;
        $sub_class_info = $nc_core->sub_class->get_by_id($infoblock_id);
        $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_DELETED, $sub_class_info['Catalogue_ID'], $sub_class_info['Subdivision_ID'], $infoblock_id);

        // Для контейнера удалим все его содержимое
        if (!$sub_class_info['Class_ID']) {
            $child_infoblock_ids = $nc_core->db->get_col("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Parent_Sub_Class_ID` = $infoblock_id");
            foreach ($child_infoblock_ids as $child_infoblock_id) {
                $this->delete_recursively($child_infoblock_id, false);
            }
        }

        // Удаление областей, привязанных к инфоблоку
        $area_infoblock_ids = $nc_core->db->get_col(
            "SELECT `Sub_Class_ID` 
               FROM `Sub_Class` 
              WHERE `AreaKeyword` LIKE '%_cc_{$infoblock_id}'
                AND `Parent_Sub_Class_ID` = 0"
        );

        foreach ($area_infoblock_ids as $area_infoblock_id) {
            $this->delete_recursively($area_infoblock_id, false);
        }
        
        if ($sub_class_info['Class_ID']) {
            //проверка существования таблицы данных MessageXXX , исправляет косяк при удалении раздела с системной таблицей
            if ($nc_core->db->get_var("SHOW TABLES FROM `$nc_core->MYSQL_DB_NAME` LIKE 'Message{$sub_class_info['Class_ID']}'")) {
                $messages = $nc_core->db->get_col(
                    "SELECT `Message_ID`
                     FROM `Message{$sub_class_info['Class_ID']}`
                     WHERE `Sub_Class_ID` = $infoblock_id"
                );
            }
            else {
                $messages = null;
            }
            if (nc_module_check_by_keyword('comments')) {
                nc_comments::dropRuleSubClass($nc_core->db, $infoblock_id);
                nc_comments::dropComments($nc_core->db, $infoblock_id, 'Sub_Class');
            }

            if (!$nc_core->db->get_var("SELECT `System_Table_ID` FROM `Class` WHERE `Class_ID` = $sub_class_info[Class_ID]")) {
                $file_fields = $nc_core->db->get_results(
                    "SELECT `Field_ID`, `Field_Name`
                     FROM `Field`
                     WHERE `Class_ID` = '$sub_class_info[Class_ID]'
                     AND `System_Table_ID` = 0
                     AND `TypeOfData_ID` = " . NC_FIELDTYPE_FILE,
                    ARRAY_A
                );

                if ($file_fields && $messages) {
                    foreach ($file_fields as $field) {
                        foreach ($messages as $message_id) {
                            DeleteFile($field['Field_ID'], $field['Field_Name'], $sub_class_info['Class_ID'], '', $message_id);
                        }
                    }
                }

                // delete dir
                DeleteSubClassDir($infoblock_id);

                if ($sub_class_info['Sub_Class_Type'] === 'object_list') {
                    $nc_core->message->delete_by_id($messages, $sub_class_info['Class_ID'], $nc_core->get_settings('TrashUse'));
                }
            }
        }

        $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Subdivision` WHERE `Sub_Class_ID` = $infoblock_id");
        $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Subdivision_Exception` WHERE `Sub_Class_ID` = $infoblock_id");
        $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Class` WHERE `Sub_Class_ID` = $infoblock_id");
        $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Class_Exception` WHERE `Sub_Class_ID` = $infoblock_id");
        $nc_core->db->query("DELETE FROM `Sub_Class_AreaCondition_Message` WHERE `Sub_Class_ID` = $infoblock_id");
        $nc_core->db->query("DELETE FROM `Sub_Class` WHERE `Sub_Class_ID` = $infoblock_id");

        if ($update_sibling_priorities) {
            $this->update_priorities_after_block_removal($sub_class_info);
        }

        if ($nc_core->db->last_error && $nc_core->db->last_error !== $previous_db_error) {
            return false;
        }

        $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_DELETED, $sub_class_info['Catalogue_ID'], $sub_class_info['Subdivision_ID'], $infoblock_id);

        nc_tpl_mixin_cache::delete_block_files($sub_class_info['Catalogue_ID'], $infoblock_id);

        return true;
    }

    /**
     * @param $infoblock_id
     */
    public function create_mock_objects($infoblock_id) {
        $nc_core = nc_core::get_object();
        $component_id = $this->get_by_id($infoblock_id, 'Class_ID');
        $min_records = (int)$this->get_by_id($infoblock_id, 'MinRecordsInInfoblock');
        $num_records = nc_db()->get_var(
            "SELECT COUNT(*) FROM `Message{$component_id}` WHERE `Sub_Class_ID` = " . (int)$infoblock_id
        );
        for ($i = $num_records; $i < $min_records; $i++) {
            $nc_core->message->create_mock($infoblock_id);
        }
    }

    /**
     * Возвращает свойства первого инфоблока в разделе из указанного компонента
     *
     * @param $subdivision_id
     * @param $component_id_or_keyword
     * @param null|string $property
     * @return mixed
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    public function get_first_subdivision_infoblock_by_component_id($subdivision_id, $component_id_or_keyword, $property = null) {
        $nc_core = nc_core::get_object();

        $component_id = $nc_core->component->get_by_id($component_id_or_keyword, 'Class_ID');

        $infoblocks_in_subdivision = $this->get_by_subdivision_id($subdivision_id);
        foreach ($infoblocks_in_subdivision as $infoblock) {
            if ($infoblock['Class_ID'] == $component_id) {
                return $property ? $infoblock[$property] : $infoblock;
            }
        }

        return null;
    }

    /**
     * Возвращает массив с данными о пресетах, которые могут быть применены к указанному инфоблоку для селекта пресета миксинов:
     * использовать пресет шаблона по умолчанию + не использовать дефолтный пресет + все подходящие пресеты.
     * Используется для формирования списка пресетов в редакторе условий.
     *
     * @param int $infoblock_id
     * @param string $scope константа nc_tpl_mixin::SCOPE_*
     * @return array[]
     *      id          идентификатор пресета
     *      value       значение для списка (-1 для пункта «использовать дефолтный пресет»)
     *      name        название пресета
     *      settings    JSON-строка
     */
    public function get_mixin_preset_options($infoblock_id, $scope) {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        $mixin_preset_field = $scope . '_Mixin_Preset_ID'; // e.g. Index_Mixin_Preset_ID

        $infoblock_data = $this->get_by_id($infoblock_id);
        $current_preset_id = (int)$infoblock_data[$mixin_preset_field];
        if ($current_preset_id > 0) {
            $current_preset_where = "OR `Mixin_Preset_ID` = $current_preset_id";
        } else {
            $current_preset_where = '';
        }

        $options =
            // явное значение «не использовать пресет»
            array('0' => array('id' => '0', 'name' => NETCAT_MIXIN_PRESET_NONE_EXPLICIT, 'settings' => '')) +
            // плюс подходящие пресеты
            (array)$db->get_results(
                "SELECT `Mixin_Preset_ID` AS `id`,
                        `Mixin_Preset_ID` AS `value`,
                        `Mixin_Preset_Name` AS `name`,
                        `Mixin_Settings` AS `settings`
                  FROM `Mixin_Preset`
                  WHERE (`Class_Template_ID` IN (0, " . (int)$infoblock_data['Class_ID'] . ", " . (int)$infoblock_data['Class_Template_ID'] . ")
                        $current_preset_where)
                        AND `Scope` = '{$db->escape($scope)}'
                  ORDER BY `Class_Template_ID` > 0 DESC, `Mixin_Preset_Name`",
                ARRAY_A,
                'id'
            );

        if ($infoblock_data['Class_ID']) { // это не контейнер: шаблон или компонент может иметь дефолтный пресет
            // для селекта пресета важно различать собственное (≥0) и унаследованное (-1) значения
            $own_preset_id = $db->get_var(
                "SELECT `$mixin_preset_field` 
                   FROM `Sub_Class` 
                   WHERE `Sub_Class_ID` = $infoblock_data[Sub_Class_ID]"
            );

            $component_template_id = $infoblock_data['Class_Template_ID'] ?: $infoblock_data['Class_ID'];
            $default_preset_id = $nc_core->component->get_by_id($component_template_id, $mixin_preset_field);

            $default_preset_data = nc_array_value($options, $default_preset_id);
            if ($default_preset_data) {
                $default_preset_option = array(
                    'value' => '-1',
                    'id' => $default_preset_id,
                    'name' => sprintf(NETCAT_MIXIN_PRESET_DEFAULT, $default_preset_data['name']),
                    'settings' => $default_preset_data['settings'],
                );
            } else {
                $default_preset_option = array(
                    'value' => '-1',
                    'id' => 0,
                    'name' => NETCAT_MIXIN_PRESET_DEFAULT_NONE,
                    'settings' => '',
                );
            }
            $options = array('-1' => $default_preset_option) + $options;
        } else {
            $own_preset_id = $infoblock_data[$mixin_preset_field];
        }

        if (isset($options[$own_preset_id])) {
            $options[$own_preset_id]['selected'] = true;
        }

        return array_values($options);
    }

    /**
     * @param int $infoblock_id
     * @param string $main_axis 'vertical', 'horizontal'
     *
     * @return int
     * @throws Exception
     */
    public function wrap_with_flexbox($infoblock_id, $main_axis) {
        $nc_core = nc_core::get_object();
        $wrapped_block_data = $nc_core->sub_class->get_by_id($infoblock_id);
        $main_axis_is_vertical = $main_axis === 'vertical';

        $mixin_settings = array(
            '' => array(
                'layout' => array(
                    9999 => array(
                        'mixin' => 'netcat_layout_flexbox',
                        'settings' =>
                            array(
                                'flexbox_direction' => ($main_axis_is_vertical ? 'row' : 'column'),
                                'flexbox_wrap' => 'nowrap',
                                'list_min_height' => '',
                                'list_min_height_unit' => 'px',
                                'list_height' => '',
                                'list_height_unit' => 'px',
                                'list_max_height' => '',
                                'list_max_height_unit' => 'px',
                                'flexbox_justify' => ($main_axis_is_vertical ? 'stretch' : 'flex-start'),
                                'horizontal_spacing' => '',
                                'flexbox_align' => ($main_axis_is_vertical ? 'center' : 'stretch'),
                                'vertical_spacing' => '',
                            ),
                    ),
                ),
            ),
        );

        // Данные о расположении, остающиеся неизменными:
        $location = array(
            'Catalogue_ID' => $wrapped_block_data['Catalogue_ID'],
            'Subdivision_ID' => $wrapped_block_data['Subdivision_ID'],
            'AreaKeyword' => $wrapped_block_data['AreaKeyword'],
        );

        // Создание контейнера
        $container_id = $nc_core->sub_class->create(
            0,
            $location + array(
                'Parent_Sub_Class_ID' => $wrapped_block_data['Parent_Sub_Class_ID'],
                'Checked' => 1,
                'Index_Mixin_Settings' => nc_array_json($mixin_settings),
            )
        );

        // Перенос блока в контейнер
        $nc_core->sub_class->move($infoblock_id, $location + array('Parent_Sub_Class_ID' => $container_id), false);

        // Приоритет устанавливаем отдельно после создания, чтобы nc_sub_class::create() не сдвинул приоритеты
        $nc_core->db->query("UPDATE `Sub_Class` SET `Priority` = {$wrapped_block_data['Priority']} WHERE `Sub_Class_ID` = $container_id");

        return $container_id;
    }

    /**
     *
     */
    public function clear_cache() {
        unset($this->data, $this->ctpl_cache, $this->subdivision_cache, $this->subdivision_first_checked_cache);
        $this->container_cache = array();
    }

    public function set_system_settings_cache($infoblock_id, $data){
        $this->infoblock_system_settings_cache[$infoblock_id] = $data;
    }

    public function get_system_settings_cache($infoblock_id){
        return $this->infoblock_system_settings_cache[$infoblock_id];
    }

    /**
     * Проверка запрета на перемещение и удаление инфоблока и его дочерних объектов
     * @param int $infoblock_id идентификатор инфоблока
     * @param bool $is_children_only проверяем только детей
     * @return bool
     */
    public function disallow_move_and_delete($infoblock_id, $is_children_only = false) {
        $infoblock_id = (int)$infoblock_id;
        $nc_core = nc_core::get_object();

        //Проверяем текущий элемент
        if (!$is_children_only) {
            $sub_class_info = $nc_core->sub_class->get_by_id($infoblock_id);
            if ($sub_class_info['ncDisallowMoveAndDelete']) {
                return true;
            }
        }

        //Проверяем дочерние элементы
        $array_sub_class = $nc_core->sub_class->get_by_container_id($infoblock_id);
        foreach ($array_sub_class as $sub_class_info) {
            if ($sub_class_info['ncDisallowMoveAndDelete']) {
                return true;
            }
            //Запускаем рекурсию для дочерних элементов
            if ($this->disallow_move_and_delete($sub_class_info['Sub_Class_ID'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Метод возвращает идентификаторы инфоблоков с запретом удаления и перемещения по идентификатору раздела
     * @param int $subdivision_id идентификатор раздела
     * @return array
     */
    public function get_disallow_sub_class_by_subdivision_id($subdivision_id) {
        $subdivision_id = (int)$subdivision_id;
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        $rows = $db->get_results(
            "SELECT `Sub_Class_ID`
                FROM `Sub_Class`
                WHERE `Subdivision_ID` = '" . $subdivision_id . "' and `ncDisallowMoveAndDelete` = '1'",
            ARRAY_A);

        return $rows;
    }

}
