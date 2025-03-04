<?php

class nc_Subdivision extends nc_Essence {

    const MAX_KEYWORD_LENGTH = 64;

    protected $db;
    protected $real_value;
    protected $_level_count;
    protected $_parent_tree;

    /**
     * Constructor function
     */
    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $nc_core = nc_Core::get_object();
        // system db object
        if (is_object($nc_core->db)) {
            $this->db = $nc_core->db;
        }

        $page = nc_Core::get_object()->page;
        $lm_type = $page->get_field_name('last_modified_type');
        $sm_field = $page->get_field_name('sitemap_include');
        $sm_change_field = $page->get_field_name('sitemap_changefreq');
        $sm_priority_field = $page->get_field_name('sitemap_priority');
        $lang_field = $page->get_field_name('language');
        $this->real_value = array('Template_ID', 'DisallowIndexing', $lm_type, $sm_field, $sm_change_field, $sm_priority_field,
            $lang_field, 'Read_Access_ID', 'Write_Access_ID', 'Edit_Access_ID', 'Delete_Access_ID', 'Checked_Access_ID', 'Moderation_ID',
            'Cache_Access_ID', 'Cache_Lifetime');

        $this->register_event_listeners();
    }

    /**
     * Обработчики для обновления и сброса кэша
     */
    protected function register_event_listeners() {
        $event = nc_core::get_object()->event;
        $on_change = array($this, 'update_cache_on_change');
        $event->add_listener(nc_event::AFTER_SUBDIVISION_UPDATED, $on_change);
        $event->add_listener(nc_event::AFTER_SUBDIVISION_ENABLED, $on_change);
        $event->add_listener(nc_event::AFTER_SUBDIVISION_DISABLED, $on_change);
        $event->add_listener(nc_event::AFTER_SUBDIVISION_DELETED, $on_change);
    }

    /**
     * @param int $id
     * @param string $item
     * @param bool $reset
     * @return null|string|array
     * @throws Exception
     */
    public function get_by_id($id, $item = "", $reset = false) {
        $res = array();
        if (isset($this->data[$id])) {
            $res = $this->data[$id];
        }

        if (empty($res) || $reset) {
            nc_core::get_object()->clear_cache_on_low_memory();

            $res = $this->db->get_row("SELECT * FROM `Subdivision` WHERE `Subdivision_ID` = '" . intval($id) . "'", ARRAY_A);

            if (empty($res)) {
                //return false;
                throw new Exception("Subdivision with id  " . $id . " does not exist");
            }

            $this->data[$id] = $res;

            foreach ($this->real_value as $v) {
                $this->data[$id]['_db_' . $v] = $this->data[$id][$v];
            }
            $this->data[$id]['_nc_final'] = 0;
        }

        if (!$this->data[$id]['_nc_final']) {
            $this->data[$id] = $this->convert_system_vars($this->data[$id]);
            $this->data[$id] = $this->convert_system_image_fields($this->data[$id]);
            $this->data[$id] = $this->inherit($this->data[$id]);
            $this->data[$id]['_nc_final'] = 1;
        }

        if ($item) {
            return array_key_exists($item, $this->data[$id]) ? $this->data[$id][$item] : "";
        }

        return $this->data[$id];
    }

    /**
     * @param int $id
     * @param bool $reset
     * @return bool|null|string
     */
    public function set_current_by_id($id, $reset = false) {
        $nc_core = nc_Core::get_object();
        try {
            $this->current = $this->get_by_id($id, "", $reset);
        } catch (Exception $e) {
            $this->current = $this->get_by_id($nc_core->catalogue->get_current("Title_Sub_ID"));
        }

        // return result
        return $this->current;
    }

    /**
     * Получить информацию о разделе по url
     *
     * @param string $uri
     * @param int $catalogue номер сайта, по умолчанию определяется по домену
     * @param string $item требуемый параметр, если не задан - функция возврщает массив
     * @param bool $remove_date
     * @param bool $return_null_when_not_found если false и раздел не найден, возвращает информацию для раздела 404, иначе - null
     * @return mixed
     */
    public function get_by_uri($uri, $catalogue = 0, $item = '', $remove_date = true, $return_null_when_not_found = false) {
        $nc_core = nc_Core::get_object();
        // определение сайта
        if (!$catalogue) {
            $catalogue = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
            $catalogue = $catalogue['Catalogue_ID'];
        }

        $uri = rtrim(nc_substr($uri, 0, nc_strrpos($uri, '/')), '/') . '/';

        if ($remove_date) {
            // find date in url
            $uri = preg_replace('|/[1-2]\d{3}/(?:\d{2}/)?(?:\d{2}/)?$|', '/', $uri);
        }

        // титульная страница
        if ($uri == "/" || $uri == "") {
            $res = $this->get_by_id($nc_core->catalogue->get_by_id($catalogue, "Title_Sub_ID"));
        }

        // поиск в кэше
        if (empty($res) && !empty($this->data)) {
            foreach ($this->data as $id => $values) {
                if ($values['Catalogue_ID'] == $catalogue && $values['Hidden_URL'] == $uri) {
                    $res = $this->data[$id];
                }
            }
        }

        // из базы
        if (empty($res)) {
            $res = $this->db->get_row("SELECT * FROM `Subdivision`
                                        WHERE `Catalogue_ID` = '" . intval($catalogue) . "'
                                          AND `Hidden_URL` = '" . $this->db->escape($uri) . "'", ARRAY_A);
            if ($res) {
                $res['_nc_final'] = 0;
                $this->data[$res['Subdivision_ID']] = $res;
            }
            else if (!$return_null_when_not_found) {
                $res = $this->get_by_id($nc_core->catalogue->get_current("E404_Sub_ID"));
            }
            else {
                return null;
            }
        }

        // processing system fields, inherit
        if (!$res['_nc_final']) {
            $res = $this->get_by_id($res['Subdivision_ID']);
        }

        if ($item) {
            return array_key_exists($item, $res) ? $res[$item] : "";
        }

        return $res;
    }

    /**
     * Get subdivision data by the URI parameter
     *
     * @param URI string
     * @param bool use returned value as current catalogue data
     * @param bool reset stored data in the static variable
     *
     * @return subdivision data associative array
     */
    public function set_current_by_uri($reset = false) {
        // system superior object
        $nc_core = nc_Core::get_object();
        $catalogue = $nc_core->catalogue->get_current("Catalogue_ID");

        // URI
        $uri = $nc_core->url->get_parsed_url('path');

        $this->current = $this->get_by_uri($uri, $catalogue);
        return $this->current;
    }

    public function validate_hidden_url($url) {
        // validate Hidden_URL
        return nc_preg_match("/^[\/_a-z" . NETCAT_RUALPHABET . "0-9-]+$/i", $url);
    }

    protected function inherit($sub_env) {
        // system superior object
        $nc_core = nc_Core::get_object();
        $sub = $sub_env["Subdivision_ID"];

        $lm = $nc_core->page->get_field_name('last_modified_type');

        $inherited_params = array('Template_ID', /*'TemplateSettings',*/ 'Read_Access_ID', 'Write_Access_ID', 'Edit_Access_ID', 'Checked_Access_ID',
            'Delete_Access_ID', 'Subscribe_Access_ID', 'Moderation_ID', $lm,
            $nc_core->page->get_field_name('sitemap_priority'), $nc_core->page->get_field_name('language'));
        $inherited_params_minus = array($nc_core->page->get_field_name('sitemap_include'), $nc_core->page->get_field_name('sitemap_changefreq'),
            'DisallowIndexing');

        if ($nc_core->modules->get_by_keyword("cache")) {
            $inherited_params[] = 'Cache_Access_ID';
            $inherited_params[] = 'Cache_Lifetime';
        }

        $parent_sub = $sub_env["Parent_Sub_ID"];

        if ($parent_sub) {
            $parent_sub_env = $this->get_by_id($parent_sub);
        }
        else {
            $parent_sub_env = $nc_core->catalogue->get_by_id($sub_env["Catalogue_ID"]);
            $sub_env = $this->inherit_system_fields("Catalogue", $parent_sub_env, $sub_env);
            $parent_sub_env["Subdivision_Name"] = $parent_sub_env["Catalogue_Name"];
            $parent_sub_env["Hidden_URL"] = "/";
        }

        foreach ($inherited_params as $v) {
            if ($parent_sub_env[$v] && !isset($sub_env['_db_inherit_' . $v])) {
                $sub_env['_db_inherit_' . $v] = $parent_sub_env[$v];
            }
            if (!$sub_env[$v]) {
                $sub_env[$v] = $parent_sub_env[$v];
            }
        }
        foreach ($inherited_params_minus as $v) {
            if ($parent_sub_env[$v] != -1 && !isset($sub_env['_db_inherit_' . $v])) {
                $sub_env['_db_inherit_' . $v] = $parent_sub_env[$v];
            }
            if ($sub_env[$v] == -1) {
                $sub_env[$v] = $parent_sub_env[$v];
            }
        }

        $sub_env = $this->inherit_system_fields($this->essence, $parent_sub_env, $sub_env, ($parent_sub ? 'Subdivision' : 'Catalogue'));


        $this->_parent_tree[$sub] = $parent_sub ? $this->_parent_tree[$parent_sub] : array($parent_sub_env);
        $this->_level_count[$sub] = $parent_sub ? $this->_level_count[$parent_sub] + 1 : 1;

        array_unshift($this->_parent_tree[$sub], $sub_env);

        return $sub_env;
    }

    /**
     * Конвертирование значений полей типа Файл в объект nc_image_path_field
     *
     * @param $sub_env
     * @return array
     */
    protected function convert_system_image_fields($sub_env) {
        $sub_id = $sub_env['Subdivision_ID'];
        $file_info = nc_Core::get_object()->file_info;
        $sub_env = array_merge(
            $sub_env,
            $file_info->get_file_info('Subdivision', $sub_id, 'ncIcon', true, false, true),
            $file_info->get_file_info('Subdivision', $sub_id, 'ncImage', true, false, true),
            $file_info->get_file_info('Subdivision', $sub_id, 'ncSMO_Image', true, false, true)
        );
        return $sub_env;
    }
    public function get_parent_tree($sub) {
        // get data
        $this->get_by_id($sub);
        // check
        if (is_array($this->_parent_tree[$sub]) && !empty($this->_parent_tree[$sub])) {
            return $this->_parent_tree[$sub];
        }
        // parent sub array not found
        return false;
    }


    public function is_parent_of($child_subdivision_id, $parent_subdivision_id) {
        $current = $child_subdivision_id;
        while ($current && $current = $this->get_by_id($current, 'Parent_Sub_ID')) {
            if ($current == $parent_subdivision_id) {
                return true;
            }
        }
        return false;
    }

    public function get_level_count($sub) {
        // get data
        $this->get_by_id($sub);
        // check
        if (isset($this->_level_count[$sub])) {
            return $this->_level_count[$sub];
        }
        // level count array not found
        return false;
    }

    public function validate_english_name($str) {
        // Check string length: database scheme stores up to 64 characters
        if (mb_strlen($str) > self::MAX_KEYWORD_LENGTH) {
            return 0;
        }
        // validate Hidden_URL
        return nc_preg_match('/^[\w' . NETCAT_RUALPHABET . '-]+$/', $str);
    }

    public function get_lang($id) {
        return $this->get_by_id($id, nc_Core::get_object()->page->get_language_field());
    }

    public function update($id, $params = array()) {
        $db = $this->db;

        $id = intval($id);
        if (!$id || !is_array($params)) {
            return false;
        }

        $query = array();
        foreach ($params as $k => $v) {
            if ($k === 'TemplateSettings') {
                $query[] = "`" . $db->prepare($k) . "` = '" . $db->prepare($v) . "'";
            } else {
                $query[] = "`" . $db->escape($k) . "` = '" . $db->prepare($v) . "'";
            }
        }

        if (!empty($query)) {
            $this->core->event->execute(nc_Event::BEFORE_SUBDIVISION_UPDATED, $this->get_by_id($id, 'Catalogue_ID'), $id);

            $db->query("UPDATE `Subdivision` SET " . join(', ', $query) . " WHERE `Subdivision_ID` = '" . $id . "' ");
            if ($db->is_error) {
                throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
            }

            $this->core->event->execute(nc_Event::AFTER_SUBDIVISION_UPDATED, $this->get_by_id($id, 'Catalogue_ID'), $id);
            nc_tpl_mixin_cache::generate_subdivision_files($id, true);
        }

        $this->data = array();
        return true;
    }


    /**
     * Возвращает значения пользовательских настроек для указанного раздела
     * с учётом иерархии разделов и значений по умолчанию
     * @param int $subdivision_id
     * @return array
     * @throws Exception
     */
    public function get_template_settings($subdivision_id) {
        static $template_settings = array();

        if (!isset($template_settings[$subdivision_id])) {
            $template_id = $this->get_by_id($subdivision_id, 'Template_ID');
            $custom_fields = nc_core()->template->get_custom_settings($template_id);
            if (!$custom_fields) { return array(); }

            $parent_tree = array_reverse((array)$this->get_parent_tree($subdivision_id));

            // Проверка принадлежности макетов разделов к той же группе, что и $template_id,
            // не сделана намеренно — для наследования значений общих «стандартизированных»
            // настроек макетов
            $values = array();
            foreach ($parent_tree as $sub) {
                $sub_settings = nc_a2f::evaluate($sub['TemplateSettings']);
                if (is_array($sub_settings)) {
                    foreach ($sub_settings as $k => $v) {
                        // у чекбоксов '' означает 'ложь'
                        if ($v !== null && ($v !== '' || $custom_fields[$k]['type'] == 'checkbox') && $v !== '#INHERIT#') {
                            $values[$k] = $v;
                        }
                    }
                }
            }

            $nc_a2f = new nc_a2f($custom_fields, 'nc_a2f', false, $subdivision_id, 'template_settings');
            $nc_a2f->set_value($values);
            $template_settings[$subdivision_id] = $nc_a2f->get_values_as_array();
        }

        return $template_settings[$subdivision_id];
    }

    /**
     * Создаёт раздел.
     *
     * @param array $properties   Свойства раздела
     *      Catalogue_ID — должен быть указан
     *      EnglishName —
     *          Если не указано, транслитерируется из Subdivision_Name.
     *          Если уже существует, добавляется суффикс "-1", "-2" и т.п.
     *      Parent_Sub_ID — если не указан или 0, раздел создаётся в корне сайта
     *      Priority — если не указано, то следующий по порядку приоритет в родительском разделе
     * @param array $template_settings   Настройки макета дизайна
     *
     * @return int   ID созданного раздела
     * @throws Exception   когда сайт или родительский раздел не найден, при ошибке создания раздела
     */
    public function create(array $properties = array(), array $template_settings = null) {
        $nc_core = nc_core::get_object();

        $site_id       = $properties['Catalogue_ID']  = (int)$properties['Catalogue_ID'];
        $parent_sub_id = $properties['Parent_Sub_ID'] = (int)$properties['Parent_Sub_ID'];

        $site_id = $nc_core->catalogue->get_by_id($site_id, 'Catalogue_ID'); // гарантирует существование сайта

        if ($parent_sub_id) {
            $parent_sub_id = $this->get_by_id($parent_sub_id, 'Subdivision_ID'); // гарантирует существование родительского раздела
        }

        // Значения по умолчанию
        $now = new nc_db_expression("NOW()");
        $default_name = CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION;
        $defaults = array(
            'Subdivision_Name' => $default_name,
            'EnglishName' => nc_transliterate(nc_array_value($properties, 'Subdivision_Name', $default_name), true),
            'Template_ID' => 0,
            'LastUpdated' => $now,
            'Created' => $now,
            'LastModified' => $now,
            'Checked' => 1,
        );

        foreach ($defaults as $field => $default_value) {
            if (!isset($properties[$field]) || !strlen($properties[$field])) {
                $properties[$field] = $default_value;
            }
        }

        // Установка EnglishName и Hidden_URL
        $properties['EnglishName'] = $this->get_available_english_name($site_id, $parent_sub_id, $properties['EnglishName']);
        $properties['Hidden_URL'] =
            ($parent_sub_id
                ? $nc_core->subdivision->get_by_id($parent_sub_id, 'Hidden_URL')
                : '/') .
            $properties['EnglishName'] . '/';

        // Установка приоритета
        if (isset($properties['Priority'])) {
            // задан приоритет, сдвигаем имеющиеся разделы «вниз»
            $properties['Priority'] = (int)$properties['Priority'];
            $nc_core->db->query(
                "UPDATE `Subdivision`
                    SET `Priority` = `Priority` + 1
                  WHERE `Catalogue_ID` = $site_id
                    AND `Parent_Sub_ID` = $parent_sub_id
                    AND `Priority` >= " . $properties['Priority']
            );
        }
        else {
            $properties['Priority'] = 1 + $this->get_max_subdivision_priority($site_id, $parent_sub_id);
        }

        // Пользовательские настройки макета дизайна в разделе
        if ($template_settings) {
            $template_id = $properties['Template_ID'] ?: $this->get_by_id($parent_sub_id, 'Template_ID');
            $a2f = new nc_a2f($nc_core->template->get_custom_settings($template_id), 'TemplateSettings');
            $a2f->set_initial_values()->set_values($template_settings);
            $properties['TemplateSettings'] = $a2f->get_values_as_string();
        }

        // Вызов обработчиков событий, сохранение раздела
        $nc_core->event->execute(nc_event::BEFORE_SUBDIVISION_CREATED, $site_id, null);
        $subdivision_id = nc_db_table::make('Subdivision')->insert($properties);

        if (!$subdivision_id) {
            throw new Exception("Unable to create subdivision\n" . $nc_core->db->last_error);
        }

        $nc_core->event->execute(nc_event::AFTER_SUBDIVISION_CREATED, $site_id, $subdivision_id);

        nc_tpl_mixin_cache::generate_subdivision_files($subdivision_id, false);

        return $subdivision_id;
    }

    /**
     * Возвращает доступное ключевое слово раздела в указанном разделе, добавляя
     * при необходимости суффикс "-1", "-2" и т. п.
     * @param $site_id
     * @param $parent_subdivision_id
     * @param $desired_english_name
     * @return string
     */
    public function get_available_english_name($site_id, $parent_subdivision_id, $desired_english_name) {
        $english_name = nc_substr($desired_english_name, 0, self::MAX_KEYWORD_LENGTH);
        $suffix = 1;

        $nc_core = nc_core::get_object();
        while ($nc_core->db->get_var(
            "SELECT 1
               FROM `Subdivision`
              WHERE `Catalogue_ID` = " . (int)$site_id . "
                AND `Parent_Sub_ID` = " . (int)$parent_subdivision_id . "
                AND `EnglishName` = '" . $nc_core->db->escape($english_name) . "'"
        )) {
            $english_name = nc_substr($desired_english_name, 0, self::MAX_KEYWORD_LENGTH - 1 - strlen($suffix)) . '-' . ($suffix++);
        }

        return $english_name;
    }

    /**
     * Возвращает максимальный приоритет подразделов в указанном разделе
     * @param $site_id
     * @param $parent_subdivision_id
     * @return int
     */
    protected function get_max_subdivision_priority($site_id, $parent_subdivision_id) {
        return (int)nc_db()->get_var(
            "SELECT MAX(`Priority`)
               FROM `Subdivision`
              WHERE `Catalogue_ID` = " . (int)$site_id . "
                AND `Parent_Sub_ID` = " . (int)$parent_subdivision_id);
    }

    /**
     *
     */
    public function clear_cache() {
        unset($this->data, $this->_parent_tree, $this->_level_count);
    }

    /**
     * @param $site_id
     * @param $subdivision_id
     */
    public function update_cache_on_change($site_id, $subdivision_id) {
        $nc_core = nc_core::get_object();
        $nc_core->file_info->clear_object_cache('Subdivision', $subdivision_id);
        $nc_core->sub_class->clear_cache();
        $this->clear_cache();
    }

    /**
     * Метод проверяет у раздела, всех подразделов раздела, и инфоблоков разделов наличия запрета на удаление и перемещение
     * @param int $subdivision_id идентификатор раздела
     * @return boolean
     */
    public function is_disallow_sub_class_subdivision_id($subdivision_id) {
        $subdivision_id = (int)$subdivision_id;
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        $sub_array = nc_get_sub_children($subdivision_id, true);

        if ($db->get_var(
            "SELECT 1
                FROM `Subdivision`
                WHERE `Subdivision_ID` in (" . implode(",", $sub_array) . ") and `ncDisallowMoveAndDelete` = '1'")) {
            return true;
        }

        if ($db->get_var(
            "SELECT 1
                FROM `Sub_Class`
                WHERE `Subdivision_ID` in (" . implode(",", $sub_array) . ") and `ncDisallowMoveAndDelete` = '1'")) {
            return true;
        }

        return false;
    }
}
