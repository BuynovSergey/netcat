<?php

class nc_Template extends nc_Essence {

    const PARTIALS_DIR = 'partials';
    const TEMPLATE_EXT = 'html';
    const MAX_KEYWORD_LENGTH = 64;

    protected $db;
    protected $table;

    protected $ids_by_keyword = array();

    protected $partials_cache;
    protected $template_settings_cache;
    protected $keyword_cache;
    protected $template_views_cache;

    protected $last_modified_type = 'template';

    /**
     * Constructor function
     */
    public function __construct() {
        parent::__construct();

        $this->db    = $this->core->db;
        $this->table = nc_db_table::make('Template');

        $this->register_event_listeners();
    }

    /**
     * Обработчики для обновления и сброса кэша
     */
    protected function register_event_listeners() {
        $event = nc_core::get_object()->event;
        $on_change = array($this, 'update_cache_on_change');
        $event->add_listener(nc_event::AFTER_TEMPLATE_UPDATED, $on_change);
        $event->add_listener(nc_event::AFTER_TEMPLATE_DELETED, $on_change);
    }


    /**
     * Возвращает идентификатор первого в иерархии макета дизайна
     *
     * @param string|int $id_or_keyword проверяемый макет дизайна
     * @return int
     */
    public function get_root_id($id_or_keyword) {
        $parent_template_id = (int)$this->get_by_id($id_or_keyword, 'Template_ID');
        do {
            $template_id = $parent_template_id;
        } while ($parent_template_id = $this->get_by_id($template_id, 'Parent_Template_ID'));

        return $template_id;
    }

    /**
     * @param $id_or_keyword
     * @param string $item
     * @param bool|false $reset
     * @return null|string|array
     */
    public function get_by_id($id_or_keyword, $item = "", $reset = false) {
        $keyword = $id = null;
        if (ctype_digit((string)$id_or_keyword)) {
            $id = (int)$id_or_keyword;
        }
        else if (preg_match('/^\w+$/', $id_or_keyword)) {
            $keyword = $id_or_keyword;
            if (isset($this->ids_by_keyword[$keyword])) {
                $id = $this->ids_by_keyword[$keyword];
            }
            else {
                $this->ids_by_keyword[$keyword] = $id = $this->db->get_var(
                    "SELECT `Template_ID`
                       FROM `Template`
                      WHERE `Keyword` = '" . $this->db->escape($keyword) . "'"
                );
            }
        }

        $result = parent::get_by_id_or_keyword($id, $item, $reset);

        if ($result && !$keyword) {
            $keyword = $this->data[$id]['Keyword'];
            $this->ids_by_keyword[$keyword] = $id;
        }

        if ($result && !empty($result['File_Path'])) {
            $this->core->page->update_last_modified_if_timestamp_is_newer(
                $this->get_last_modified_timestamp_by_path($result['File_Path']),
                'template'
            );
        }

        return $result;
    }


    public function convert_subvariables($template_env) {
        // load system table fields
        $table_fields = $this->core->get_system_table_fields($this->essence);
        // count
        $counted_fileds = count($table_fields);

        // %FIELD replace with inherited template field value
        for ($i = 0; $i < $counted_fileds; $i++) {
            $template_env["Header"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Header"]);
            $template_env["Footer"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Footer"]);
        }

        return $template_env;
    }

    protected function inherit($template_env) {
        global $perm, $AUTH_USER_ID, $templatePreview;

        // Блок для предпросмотра макетов дизайна
        $magic_gpc = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();
        if ($template_env["Template_ID"] == $templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview])) {
            foreach ($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
                $template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
            }
        }

        $parent_template = $template_env["Parent_Template_ID"];

        if ($parent_template) {
            $parent_template_env = $this->get_by_id($parent_template);

            // Если мы вызываем предпросмотр для макета, а он используется в качестве родительского.
            if ($parent_template_env["Template_ID"] == $templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview])) {
                foreach ($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
                    $parent_template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
                }
            }

            $parent_template = $template_env["Parent_Template_ID"];

            if (!$template_env["Header"]) {
                $template_env["Header"] = $parent_template_env["Header"];
            } else {
                if ($parent_template_env["Header"]) {
                    $template_env["Header"] = str_replace("%Header", $parent_template_env["Header"], $template_env["Header"]);
                }
            }
            if (!$template_env["Footer"]) {
                $template_env["Footer"] = $parent_template_env["Footer"];
            } else {
                if ($parent_template_env["Footer"]) {
                    $template_env["Footer"] = str_replace("%Footer", $parent_template_env["Footer"], $template_env["Footer"]);
                }
            }
            $template_env["Settings"] = $parent_template_env["Settings"].$template_env["Settings"];

            $template_env = $this->inherit_system_fields($this->essence, $parent_template_env, $template_env);
            $parent_template = $parent_template_env["Parent_Template_ID"];
        }

        return $template_env;
    }

    public function update($template_id, $params = array()) {
        $nc_core = nc_core::get_object();

        $db = $this->db;

        $nc_core->event->execute(nc_Event::BEFORE_TEMPLATE_UPDATED, $template_id, $params);

        $template_id = intval($template_id);
        if (!$template_id || !is_array($params)) {
            return false;
        }

        $query = array();
        foreach ($params as $k => $v) {
            $query[] = "`".$k."` = '".(preg_match('/validate_regexp/', $v) ? $db->prepare($v) : $db->escape($v))."'";
        }

        if (!empty($query)) {
            $db->query("UPDATE `Template` SET ".join(', ', $query)." WHERE `Template_ID` = '".$template_id."' ");
            if ($db->is_error)
                    throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        $nc_core->event->execute(nc_Event::AFTER_TEMPLATE_UPDATED, $template_id, $params);

        //unset($this->data[$template_id]);
        $this->data = array();
        return true;
    }

    public function get_parent($id, $all = 0) {
        $id = intval($id);
        $ret = array();
        $parent_id = $this->db->get_var("SELECT `Parent_Template_ID` FROM `Template` WHERE `Template_ID` = '".$id."' ");

        if (!$all) return intval($parent_id);

        if ($parent_id) {
            $ret[] = $parent_id;
            $ret = array_merge($ret, $this->get_parent($parent_id, 1));
        }

        return $ret;
    }

    public function get_childs($id) {
        $ret = array();
        $childs = $this->db->get_col("SELECT `Template_ID` FROM `Template` WHERE `Parent_Template_ID` = '".intval($id)."'");

        if (!empty($childs))
                foreach ($childs as $v) {
                $ret[] = $v;
                $ret = array_merge($ret, $this->get_childs($v));
            }


        return $ret;
    }

    /**
     * Возвращает абсолютный путь к папке с шаблоном
     * @param  integer $template_id
     * @return string
     */
    public function get_path($template_id) {
        return rtrim($this->core->TEMPLATE_FOLDER, '/') . $this->get_by_id($template_id, 'File_Path');
    }

    /**
     * Возвращает абсолютный путь к папке с врезками (дополнительными шаблонами, partials)
     * или к конкретной врезке, если задан параметр $partial.
     *
     * @param  integer $template_id
     * @param  string $partial_keyword       ключевое слово врезки
     * @return string
     */
    public function get_partials_path($template_id, $partial_keyword = null) {
        if ($partial_keyword) {
            if (!$this->is_valid_partial_keyword($partial_keyword)) {
                trigger_error('Invalid partial keyword: ' . htmlspecialchars($partial_keyword), E_USER_WARNING);
                return null;
            }
            $partial_keyword .= '.' . self::TEMPLATE_EXT;
        }
        return $this->get_path($template_id) . self::PARTIALS_DIR . '/' . $partial_keyword;
    }

    /**
     * Возвращает true если макет имеет врезки (дополнительные шаблоны, partials) (только при File_Mode = 1)
     *
     * @param  integer $template_id
     * @param  string $partial_keyword
     * @return boolean
     */
    public function has_partial($template_id, $partial_keyword = null) {
        $partials = $this->get_partials_data($template_id);

        return $partial_keyword ? isset($partials[$partial_keyword]) : count($partials);
    }

    /**
     * Возвращает объект partial по идентификатору макета дизайна и ключевому слову
     * врезки
     *
     * @param string|int $template_id идентификатор или ключевое слово макета дизайна
     * @param string $partial_keyword ключевое слово врезки
     * @param array|null $data данные, передаваемые во врезку
     * @return nc_partial_template_partial|null
     */
    public function get_partial($template_id, $partial_keyword, array $data = array()) {
        $template_view = $this->get_file_template($template_id);
        if ($template_view) {
            return $template_view->partial($partial_keyword, $data);
        }
        return null;
    }

    /**
     * Возвращает nc_tpl_template_view для макета
     *
     * @param $template_id_or_keyword
     * @return nc_tpl_template_view|null
     * @internal Не является частью публичного API
     */
    public function get_file_template($template_id_or_keyword) {
        $template_id = $this->get_by_id($template_id_or_keyword, 'Template_ID');

        if (!isset($this->template_views_cache[$template_id])) {
            $nc_core = nc_core::get_object();
            $template_path = $template_id ? $this->get_by_id($template_id, 'File_Path') : null;
            if (!$template_path) {
                trigger_error("Template '" . htmlspecialchars($template_id_or_keyword) . "' does not exist or is not a file template", E_USER_WARNING);
                return null;
            }

            if (!isset($this->template_views_cache[$template_id])) {
                $template_view = new nc_tpl_template_view($nc_core->TEMPLATE_FOLDER, $nc_core->db);
                $template_view->load_template($template_id, $template_path);
                $this->template_views_cache[$template_id] = $template_view;
            }
        }

        return $this->template_views_cache[$template_id];
    }

    /**
     * Возвращает данные о врезках (дополнительных шаблонах) макета дизайна
     *
     * @param integer $template_id
     * @param string|null $partial_keyword если задано, возвращает только данные для указанной
     *     врезки (иначе — массив)
     * @param string|null $partial_property если задано, возвращает только указанное свойство
     * @return array|string|null
     */
    public function get_partials_data($template_id, $partial_keyword = null, $partial_property = null) {
        $template_id = $this->get_root_id($template_id);
        if (!isset($this->partials_cache[$template_id])) {
            $this->partials_cache[$template_id] = array();
            $partials_folder = $this->get_partials_path($template_id);
            if (file_exists($partials_folder)) {
                $files = glob($partials_folder . '*.' . self::TEMPLATE_EXT) ?: array();
                $meta = $files 
                    ? nc_db()->get_results("SELECT * FROM `Template_Partial` WHERE `Template_ID` = $template_id", ARRAY_A, 'Keyword') 
                    : array();

                foreach ($files as $file) {
                    // у partials, созданных в предыдущих версиях, может не быть записи в Template_Partial
                    $keyword = pathinfo($file, PATHINFO_FILENAME);
                    $data = nc_array_value($meta, $keyword, array(
                        'Template_ID' => $template_id,
                        'Keyword' => $keyword,
                        'Description' => '',
                        'EnableAsyncLoad' => '0',
                    ));
                    $this->partials_cache[$template_id][$keyword] = $data;
                }
            }
        }

        $result = $this->partials_cache[$template_id];

        if ($partial_keyword) {
            $result = nc_array_value($result, $partial_keyword);
            if ($partial_property) {
                $result = nc_array_value($result, $partial_property);
            }
        }

        return $result;
    }


    /**
     * Возвращает массив с параметрами пользовательских настроек макета дизайна,
     * с учётом наследования по иерархии макетов
     * @param int $template_id
     * @return array
     */
    public function get_custom_settings($template_id) {
        $settings_hierarchy = array(); // настройки макетов, начиная снизу
        $template = array('Parent_Template_ID' => $template_id);
        while ($template['Parent_Template_ID']) {
            $template = $this->get_by_id($template['Parent_Template_ID']);
            $template_settings = nc_a2f::evaluate($template['CustomSettings']);
            if ($template_settings) { $settings_hierarchy[] = $template_settings; }
        }

        $i = count($settings_hierarchy);
        // для начала положим в результат настройки текущего макета, чтобы они были сверху списка:
        $inherited_settings = nc_array_value($settings_hierarchy, 0, array());
        while (--$i >= 0) {
            $inherited_settings = array_merge($inherited_settings, $settings_hierarchy[$i]);
        }

        return (array)$inherited_settings;
    }

    /**
     * Возвращает значения по умолчанию, указанные для настроек макета дизайна
     * @param $template_id
     * @return array
     */
    public function get_settings_default_values($template_id) {
        $template_id = $this->get_by_id($template_id, 'Template_ID');
        if (!isset($this->template_settings_cache[$template_id])) {
            $nc_a2f = new nc_a2f($this->get_custom_settings($template_id));
            $this->template_settings_cache[$template_id] = $nc_a2f->get_values_as_array();
        }

        return $this->template_settings_cache[$template_id];
    }

    /**
     * @param string $keyword
     * @param int|null $template_id    ID макета дизайна, которому предназначается ключевое слово
     * @param int|null $parent_template_id
     * @return bool|string   возвращает true или текст ошибки
     */
    public function validate_keyword($keyword, $template_id = null, $parent_template_id = null) {
        $keyword = trim($keyword);
        $length = strlen($keyword);
        $template_id = (int)$template_id;

        // Пустое ключевое слово — OK
        if ($length == 0) {
            return true;
        }

        // Длина больше 64 — не ОК
        if ($length > self::MAX_KEYWORD_LENGTH) {
            return sprintf(CONTROL_TEMPLATE_KEYWORD_TOO_LONG, self::MAX_KEYWORD_LENGTH);
        }

        // Только цифры — не ОК
        if (preg_match('/^\d+$/', $keyword)) {
            return CONTROL_TEMPLATE_KEYWORD_ONLY_DIGITS;
        }

        // В ключевом слове допустимы только a-z 0-9 _
        if (!preg_match('/^\w+$/', $keyword)) {
            return CONTROL_TEMPLATE_KEYWORD_INVALID_CHARACTERS;
        }

        // Зарезервированные слова
        if (preg_match('/^(?:partials|assets|images?|styles?|scripts?|fonts?|img|css|js)$/', $keyword)) {
            return sprintf(CONTROL_CLASS_KEYWORD_RESERVED, $keyword);
        }

        // Определяем родительский шаблон, если не указан
        if ($parent_template_id === null && $template_id) {
            $parent_template_id = (int)$this->get_by_id($template_id, 'Parent_Template_ID');
        }
        else {
            $parent_template_id = (int)$parent_template_id;
        }

        // Уникальность ключевого слова в пределах родителя
        $existing_component = $this->db->get_row(
            "SELECT `Template_ID`, `Description`
               FROM `Template`
              WHERE `Keyword` = '$keyword'
                AND `Parent_Template_ID` = $parent_template_id
                AND `Template_ID` != '$template_id'", ARRAY_A); // $keyword безопасен

        // Ключевое слово уже используется
        if ($existing_component) {
            return sprintf(CONTROL_TEMPLATE_KEYWORD_NON_UNIQUE, $keyword, $existing_component['Template_ID'], htmlspecialchars($existing_component['Description']));
        }

        // Ключевое слово уже присвоено этому макету (т. е. не изменилось)
        if ($template_id && $this->get_by_id($template_id, 'Keyword') == $keyword) {
            return true;
        }

        // Не должно быть папки с таким именем
        $parent_path = $parent_template_id ? $this->get_by_id($parent_template_id, 'File_Path') : "";
        $template_path = $parent_path . $keyword . '/';

        if (!$template_id || $template_path != $this->get_by_id($template_id, 'File_Path')) {
            $target_path = nc_core::get_object()->TEMPLATE_FOLDER . ltrim($parent_path, '/') . $keyword;
            if (file_exists($target_path)) {
                return sprintf(CONTROL_TEMPLATE_KEYWORD_PATH_EXISTS, $keyword);
            }
        }

        // Претензий не имеем
        return true;
    }

    /**
     * Проверка допустимости ключевого слова для врезки (без проверки того, существует ли врезка)
     * @param $partial_keyword
     * @return bool
     */
    public function is_valid_partial_keyword($partial_keyword) {
        return (bool)preg_match('/^[a-z0-9_-]+$/i', $partial_keyword);
    }

    /**
     * Получение ID макета по ключевому слову (для «корневых» макетов)
     * @param string $keyword
     * @return int
     */
    protected function get_id_by_keyword($keyword) {
        if (!isset($this->keyword_cache[$keyword])) {
            $this->keyword_cache[$keyword] = (int)$this->db->get_var(
                "SELECT `Template_ID`
                   FROM `Template`
                  WHERE `Parent_Template_ID` = 0
                    AND `Keyword` = '" . $this->db->escape($keyword) . "'"
            );
        }
        return $this->keyword_cache[$keyword];
    }

    /**
     *
     */
    public function clear_cache() {
        unset($this->data, $this->partials_cache, $this->template_settings_cache, $this->keyword_cache);
    }

    /**
     * @param $template_id
     */
    public function update_cache_on_change($template_id) {
        nc_core::get_object()->file_info->clear_object_cache('Template', $template_id);
        $this->clear_cache();
    }

    /**
     * @param string $relative_template_path
     * @return int
     */
    protected function get_last_modified_timestamp_by_path($relative_template_path) {
        $full_template_path = nc_core::get_object()->TEMPLATE_FOLDER . ltrim($relative_template_path, '/');

        $template_files = array('Settings.html', 'Template.html', 'Header.html', 'Footer.html');
        $last_modified_timestamp = 0;

        foreach ($template_files as $template_file) {
            $template_file_path = $full_template_path . $template_file;

            if (file_exists($template_file_path) && filemtime($template_file_path) > $last_modified_timestamp) {
                $last_modified_timestamp = filemtime($template_file_path);
            }
        }

        return (int)$last_modified_timestamp;
    }
}
