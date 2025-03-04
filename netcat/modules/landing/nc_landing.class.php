<?php

/**
 *
 */
class nc_landing {

    const RESOURCE_TEMPLATE = 'templates';
    const RESOURCE_COMPONENT = 'components';
    const RESOURCE_LIST = 'lists';

    const LANDING_LIST_COMPONENT_KEYWORD = 'netcat_module_landing_subdivision_list';

    const PRESET_SCREENSHOT = 'screenshot';
    const PRESET_SCREENSHOT_THUMBNAIL = 'screenshot_thumbnail';

    /** @var  int */
    protected $site_id;

    /** @var  string  путь к папке с ресурсами (пресетами, компонентами, макетами, списками) */
    protected $resource_dir;

    /** @var string  */
    protected $user_preset_settings_folder = 'landing/preset_settings/';

    /** @var  nc_landing_preset_collection */
    static protected $presets;

    /** @var self[] */
    static protected $instances = array();

    /**
     * @param int|null $site_id
     * @return nc_landing
     */
    public static function get_instance($site_id = null) {
        static $instances = array();
        $site_id = (int)$site_id;
        if (!$site_id) {
            $site_id = nc_core::get_object()->catalogue->get_current('Catalogue_ID');
        }

        if (!isset($instances[$site_id])) {
            $instances[$site_id] = new self($site_id);
        }

        return $instances[$site_id];
    }

    /**
     *
     */
    protected function __construct($site_id) {
        $this->site_id = $site_id;
        $this->resource_dir = __DIR__ . '/resources/';
    }

    // ---- Методы для формирования элементов интерфейса ----

    /**
     * Адрес диалога создания лендинга не на основе объекта
     * @return string
     */
    public function get_independent_landing_create_dialog_url() {
        return nc_module_path('landing') .
               "admin/?controller=constructor&action=show_independent_landing_create_dialog" .
               "&site_id=$this->site_id";
    }

    /**
     * Адрес диалога создания лендинга для указанного объекта
     * @param $component_id
     * @param $object_id
     * @return string
     */
    public function get_object_landing_create_dialog_url($component_id, $object_id) {
        return nc_module_path('landing') .
               "admin/?controller=constructor&action=show_object_landing_create_dialog" .
               "&site_id=$this->site_id&component_id=$component_id&object_id=$object_id";
    }

    /**
     * Адрес диалога списка лендингов для указанного объекта
     * @param $component_id
     * @param $object_id
     * @return string
     */
    public function get_object_landing_list_dialog_url($component_id, $object_id) {
        return nc_module_path('landing') .
               "admin/?controller=constructor&action=show_object_landing_list_dialog" .
               "&site_id=$this->site_id&component_id=$component_id&object_id=$object_id";
    }

    /**
     * Добавляет пункт меню для сохранения настроек лендинг-страницы
     * (используется в nc_quickbar_in_template_header())
     * @param nc_ui_html $submenu
     * @param int $subdivision_id
     */
    public function add_save_landing_page_settings_menu_item(nc_ui_html $submenu, $subdivision_id) {
        $existing_landing_page = $this->get_landing_page_id_by_subdivision_id($subdivision_id);
        if ($existing_landing_page) {
            $dialog_path = nc_module_path('landing') .
                'admin/?controller=constructor&action=show_preset_settings_save_dialog' .
                '&subdivision_id=' . $subdivision_id;

            $submenu->add_btn($dialog_path, NETCAT_MODULE_LANDING_SAVE_PRESET_SETTINGS)
                ->click('nc.load_dialog(this.href); return false')
                ->icon('mod-landing');
        }
    }

    // ---- Получение пресетов ----

    /**
     * Возвращает экземпляр пресета
     * @param $keyword
     * @param null|array|int $preset_settings   ID настроек или массив с настройками
     * @return nc_landing_preset|null
     */
    public function get_preset($keyword, $preset_settings = null) {
        if (strpos($keyword, '@')) {
            list($keyword, $preset_settings) = explode('@', $keyword);
            $preset_settings = (int)$preset_settings;
        }

        if (self::$presets && !$preset_settings) {
            return self::$presets[$keyword];
        }

        if (!preg_match('/^\w+$/', $keyword)) {
            return null;
        }

        $preset_path = $this->resource_dir . 'presets/' . $keyword . '/';
        $preset_class_file = $preset_path . 'class.php';
        if (file_exists($preset_class_file)) {
            include_once $preset_class_file;
            $preset_class_name = 'nc_landing_preset_' . $keyword;
            if (class_exists($preset_class_name)) {
                return new $preset_class_name($keyword, $preset_path, $preset_settings);
            }
            else {
                trigger_error("Preset class '$preset_class_name' is not found in '$preset_class_file'");
            }
        }

        return null;
    }

    /**
     * Список ключевых слов пресетов в папке resources/presets/
     * @return array
     */
    protected function get_available_preset_keywords() {
        static $cache;
        if (!$cache) {
            $cache = array_map('basename', (array)glob($this->resource_dir . 'presets/*', GLOB_ONLYDIR));
        }
        return $cache;
    }

    /**
     * Возвращает коллекцию со всеми пресетами
     * @return nc_landing_preset_collection
     */
    public function get_presets() {
        if (!self::$presets) {
            $presets = new nc_landing_preset_collection();
            foreach ($this->get_available_preset_keywords() as $preset_keyword) {
                $preset_instance = $this->get_preset($preset_keyword);
                if ($preset_instance) {
                    $presets->add($preset_instance);
                }
            }

            self::$presets = $presets;
        }

        return self::$presets;
    }

    /**
     * Возвращает коллекцию пресетов с пользовательскими настройками
     * @return nc_landing_preset_collection
     */
    public function get_user_presets() {
        $presets = new nc_landing_preset_collection();

        $user_presets = (array)nc_db()->get_col(
            "SELECT DISTINCT `Landing_Preset_Setting_ID`, `Preset_Keyword` FROM `Landing_Preset_Setting`",
            1, 0
        );

        foreach ($user_presets as $setting_id => $preset_keyword) {
            $presets->add($this->get_preset($preset_keyword, $setting_id));
        }

        return $presets;
    }

    /**
     * Проверяет, есть ли подходящие пресеты для компонента
     * @param $component_id
     * @return bool
     */
    public function has_presets_for_component($component_id) {
        return $this->get_presets()->any('can_be_used_for_component', true, '==', array($component_id));
    }

    // ----

    /**
     * Возвращает ID раздела, в котором создаются лендинги на сайте
     * @param bool $create_if_not_exists   Если true и раздел не найден, создаёт раздел для лендингов
     * @return int|false
     */
    public function get_landings_subdivision_id($create_if_not_exists = false) {
        static $subdivision_id;

        if ($subdivision_id === null) {
            $subdivision_id = $this->find_landings_subdivision();
        }

        if (!$subdivision_id && $create_if_not_exists) {
            $subdivision_id = $this->create_landings_subdivision();
        }

        if (!$subdivision_id) {
            $subdivision_id = false;
        }

        return $subdivision_id;
    }

    /**
     * Возвращает ID инфоблока, выводящего список лендингов в разделе $this->get_landings_subdivision_id()
     * @return int|null
     */
    public function get_landings_list_infoblock_id() {
        static $infoblock_id;

        if ($infoblock_id !== null) {
            return $infoblock_id;
        }

        $subdivision_id = $this->get_landings_subdivision_id();
        if (!$subdivision_id) {
            return false;
        }

        $nc_core = nc_core::get_object();
        $landing_list_component_id = $nc_core->component->get_by_id(self::LANDING_LIST_COMPONENT_KEYWORD, 'Class_ID');

        $infoblock_id = $nc_core->db->get_var(
            "SELECT `Sub_Class_ID` 
               FROM `Sub_Class` 
              WHERE `Subdivision_ID` = $subdivision_id 
                AND `Class_ID` = $landing_list_component_id"
        ) ?: false;

        return $infoblock_id;
    }
    
    /**
     * @return int
     */
    protected function get_landing_list_component_id() {
        $nc_core = nc_core::get_object();
        $landing_list_component_id = $nc_core->component->get_by_id(self::LANDING_LIST_COMPONENT_KEYWORD, 'Class_ID');

        if (!$landing_list_component_id) {
            $landing_list_archive = $this->resource_dir . self::RESOURCE_COMPONENT . '/' . self::LANDING_LIST_COMPONENT_KEYWORD . 'tgz';
            $landing_list_component_id = $nc_core->backup->import($landing_list_archive, array('save_ids' => false));
        }

        return $landing_list_component_id;
    }

    /**
     * Ищет раздел с привязанным компонентом списка лендинг-страниц,
     * @return int|null
     */
    protected function find_landings_subdivision() {
        $nc_core = nc_core::get_object();

        $landing_list_component_id = $this->get_landing_list_component_id();

        $subdivision_id = $nc_core->db->get_var(
            "SELECT `Subdivision_ID`
               FROM `Sub_Class`
              WHERE `Class_ID` = $landing_list_component_id
                AND `Catalogue_ID` = $this->site_id
              LIMIT 1"
        );

        return $subdivision_id;
    }

    /**
     * Создаёт раздел для лендингов на сайте.
     * @return int
     * @throws Exception
     */
    protected function create_landings_subdivision() {
        $nc_core = nc_core::get_object();
        $landing_list_component_id = $this->get_landing_list_component_id();

        $subdivision_id = $nc_core->subdivision->create(array(
            'Catalogue_ID' => $this->site_id,
            'Subdivision_Name' => NETCAT_MODULE_LANDING_PROMO_SUBDIVISION_NAME,
            'EnglishName' => 'promo',
            'Priority' => 1,
            'Checked' => 0,
        ));

        $nc_core->sub_class->create($landing_list_component_id, array(
            'Subdivision_ID' => $subdivision_id,
            'Sub_Class_Name' => NETCAT_MODULE_LANDING_PROMO_SUBDIVISION_NAME,
            'EnglishName' => 'promo',
        ));

        return $subdivision_id;
    }

    /**
     * Выводит список лендингов на сайте в режиме администрирования
     *
     * @param int
     */
    public function make_landing_list() {
        extract($GLOBALS);
        $nc_core = nc_core::get_object();
        require_once $nc_core->ADMIN_FOLDER . 'function.inc.php';
        $nc_core->ui; /// loads nc_ui classes
        $controller = new nc_landing_subdivision_admin_controller(__DIR__ . "/admin/views/subdivision");
        $controller->set_site_id($this->site_id);

        ob_get_clean();
        echo $controller->execute('show_subdivision_list');
        die;
    }

    // ---- Работа с «ресурсами» ----

    /**
     * Базовый метод для is_*_installed()
     * @param $table
     * @param $field
     * @param $keyword
     * @return bool
     */
    protected function check_resource($table, $field, $keyword) {
        static $cache = array();
        if (!isset($cache[$table][$keyword])) {
            $db = nc_db();
            $cache[$table][$keyword] = (bool)$db->get_var(
                "SELECT 1 FROM `$table` WHERE `$field` = '" . $db->escape($keyword) . "'"
            );
        }
        return $cache[$table][$keyword];
    }

    /**
     * Проверяет, установлен ли в системе указанный ресурс
     * @param string $type  одна из констант self::RESOURCE_*
     * @param string $keyword  ключевое слово
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function is_resource_installed($type, $keyword) {
        switch ($type) {
            case self::RESOURCE_COMPONENT:
                return $this->check_resource('Class', 'Keyword', $keyword);
            case self::RESOURCE_TEMPLATE:
                return $this->check_resource('Template', 'Keyword', $keyword);
            case self::RESOURCE_LIST:
                return $this->check_resource('Classificator', 'Table_Name', $keyword);
            default:
                throw new InvalidArgumentException("Unsupported resource type: $type");
        }
    }

    /**
     * Устанавливает все имеющиеся в папке модуля ресурсы
     * (временная реализация)
     */
    public function install_all_resources() {
        $nc_core = nc_core::get_object();
        foreach (array(self::RESOURCE_TEMPLATE, self::RESOURCE_LIST, self::RESOURCE_COMPONENT) as $resource_type) {
            $files = glob($this->resource_dir . $resource_type . '/*.tgz') ?: array();
            foreach ($files as $file) {
                $resource_keyword = basename($file, '.tgz');
                if (!$this->is_resource_installed($resource_type, $resource_keyword)) {
                    $nc_core->backup->import($file, array('save_ids' => false));
                }
            }
        }
    }

    /**
     * Возвращает список страниц для
     * @param $component_id
     * @param $object_id
     * @return array
     */
    public function get_landing_subdivision_ids_for_object($component_id, $object_id) {
        return (array)nc_db()->get_col(
            "SELECT `Subdivision_ID`
               FROM `Landing_Page`
              WHERE `Source_Component_ID` = " . (int)$component_id . "
                AND `Source_Object_ID` = " . (int)$object_id
        );
    }

    /**
     * Возвращает ID страницы лендинга по ID раздела
     * @param $subdivision_id
     * @return mixed|null
     */
    public function get_landing_page_id_by_subdivision_id($subdivision_id) {
        return nc_db_table::make('Landing_Page')->where('Subdivision_ID', $subdivision_id)->get_value('Landing_Page_ID');
    }

    /**
     * Сохраняет параметры лендинга для повторного использования
     * @param $landing_page_id
     * @param string $name
     * @param string $description
     * @throws nc_landing_preset_exception
     * @return int
     */
    public function save_landing_page_settings($landing_page_id, $name, $description = '') {
        $nc_core = nc_core::get_object();

        $landing_page_data = nc_db_table::make('Landing_Page')->where_id($landing_page_id)->get_row();
        if (!$landing_page_data) {
            throw new nc_landing_preset_exception("Cannot save landing page settings: landing page with ID '$landing_page_id' does not exist");
        }
        $landing_page_block_table = nc_db_table::make('Landing_Page_Block');

        $subdivision_id = $landing_page_data['Subdivision_ID'];
        if (!strlen(trim($name))) {
            $name = $nc_core->subdivision->get_by_id($subdivision_id, 'Subdivision_Name');
        }

        // Параметры форм в разделе
        $is_requests_module_enabled = nc_module_check_by_keyword('requests');
        $requests_form_subdivision_settings = $is_requests_module_enabled
            ? nc_requests_form_settings_subdivision::get_all_form_types_settings_in_subdivision_as_array($subdivision_id)
            : array();

        $blocks = array();
        $source_infoblocks = $nc_core->sub_class->get_by_subdivision_id($subdivision_id);
        foreach ($source_infoblocks as $infoblock) {
            $infoblock_id = $infoblock['Sub_Class_ID'];
            $block_class_keyword = $landing_page_block_table
                ->where('Infoblock_ID', $infoblock_id)
                ->get_value('Block_Class_Keyword');

            $requests_form_infoblock_settings = $is_requests_module_enabled
                ? nc_requests_form_settings_infoblock::get_all_form_types_settings_in_infoblock_as_array($infoblock_id)
                : array();

            $blocks[$infoblock['EnglishName']] = array(
                'block_class_keyword' => $block_class_keyword,
                'component' => $nc_core->component->get_by_id($infoblock['Class_ID'], 'Keyword') ?: $infoblock['Class_ID'],
                'component_template' => $nc_core->component->get_by_id($infoblock['Class_Template_ID'], 'Keyword') ?: $infoblock['Class_Template_ID'],
                'infoblock_name' => $infoblock['Sub_Class_Name'],
                'infoblock_settings' => $infoblock['CustomSettings'],
                'objects' => $this->get_infoblock_objects_properties($infoblock['Class_ID'], $infoblock_id),
                'requests_form_infoblock_settings' => $requests_form_infoblock_settings,
            );
        }

        $template_id = $nc_core->subdivision->get_by_id($subdivision_id, 'Template_ID');
        $preset_settings = array(
            'name' => $name,
            'description' => $description,
            'template' => $nc_core->template->get_by_id($template_id, 'Keyword') ?: $template_id,
            'requests_form_subdivision_settings' => $requests_form_subdivision_settings,
            'blocks' => $blocks,
        );

        list($preset_keyword) = explode('@', $landing_page_data['Preset_Keyword']);

        $setting_id = nc_db_table::make('Landing_Preset_Setting')->insert(array(
            'Name' => $name,
            'Preset_Keyword' => $preset_keyword,
            'Preset_Settings' => serialize($preset_settings),
            'Source_Subdivision_ID' => $subdivision_id,
        ));

        return $setting_id;
    }

    /**
     * @param $landing_page_id
     * @param string $file_type    nc_landing::PRESET_SCREENSHOT, nc_landing::PRESET_SCREENSHOT_THUMBNAIL
     * @param $base64_data
     */
    public function save_landing_page_settings_screenshot($landing_page_id, $file_type, $base64_data) {
        if ($file_type != self::PRESET_SCREENSHOT && $file_type != self::PRESET_SCREENSHOT_THUMBNAIL) {
            throw new UnexpectedValueException("Unexpected file type '$file_type'");
        }

        $folder = $this->get_user_preset_settings_folder() . $landing_page_id;
        if (!file_exists($folder)) {
            mkdir($folder, nc_core::get_object()->DIRCHMOD, true);
        }

        // убираем префикс data URL
        if (substr($base64_data, 0, 5) == 'data:') {
            $base64_data = substr($base64_data, strpos($base64_data, ','));
        }

        file_put_contents($folder . '/' . $file_type . '.png', base64_decode($base64_data));
    }

    /**
     * Возвращает путь к папке с файлами от корня веб-сервера
     * @return string
     */
    public function get_user_preset_settings_path() {
        $nc_core = nc_core::get_object();
        return $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH . $this->user_preset_settings_folder;
    }

    /**
     * Возвращает абсолютный путь к папке с файлами для пользовательских настроек пресетов в файловой системе
     * @return string
     */
    public function get_user_preset_settings_folder() {
        $nc_core = nc_core::get_object();
        $folder = $nc_core->FILES_FOLDER . $this->user_preset_settings_folder;
        return $folder;
    }

    /**
     * Возвращает массив со свойствами объектов в инфоблоке
     * (для сохранения в настройках пресета)
     * @param $component_id
     * @param $infoblock_id
     * @return array
     */
    protected function get_infoblock_objects_properties($component_id, $infoblock_id) {
        static $unset_object_fields = array(
            'Message_ID', 'Parent_Message_ID',
            'User_ID', 'LastUser_ID', 'IP', 'LastUser_IP', 'UserAgent', 'LastUserAgent',
            'Created', 'LastUpdated',
            'Subdivision_ID', 'Sub_Class_ID',
        );

        $nc_core = nc_core::get_object();
        $objects_properties = array();

        $object_records = nc_db_table::make("Message" . (int)$component_id)
            ->where('Sub_Class_ID', (int)$infoblock_id)
            ->get_result();

        if ($object_records) {
            foreach ($object_records as $object_record) {
                // file fields
                $file_fields = $nc_core->get_component($component_id)->get_fields(NC_FIELDTYPE_FILE);
                if ($file_fields) {
                    $nc_core->file_info->cache_object_data($component_id, $object_record);
                    foreach ($file_fields as $file_field) {
                        $file_field_name = $file_field['name'];
                        $file_info = $nc_core->file_info->get_file_info($component_id, $object_record['Message_ID'], $file_field_name, false, false, true);
                        $object_record[$file_field_name] = $file_info['url'];
                    }
                }

                foreach ($unset_object_fields as $f) {
                    unset($object_record[$f]);
                }

                $objects_properties[] = $object_record;
            }
        }

        return $objects_properties;
    }
}
