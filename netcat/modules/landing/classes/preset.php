<?php

/**
 * Class nc_landing_preset
 * Правило разворачивания лендинга.
 *
 * В классе «ресурсами» называются используемые макеты, компоненты и списки.
 *
 * Требования к классу-наследнику:
 *  — название должно начинаться с nc_landing_preset_
 *  — код, создающий блоки, должен находится в классах nc_landing_preset_PRESET_block_BLOCK
 *    в файлах blocks/BLOCK.php
 */
abstract class nc_landing_preset implements ArrayAccess {

    // ---- Для переопределения в конкретных классах ----

    /** @var array  человекопонятное название пресета */
    protected $name = array(
        'ru' => 'Лендинг',
        'en' => 'Landing',
    );

    protected $description = '';

    /**
     * @var array
     * Описание инфоблоков создаваемой страницы. Должен быть определён в классах пресетов.
     * Каждый элемент может быть:
     *  1) Строкой (ключевое слово блока). Такой формат используется в классе пресета.
     *     В этом случае должен существовать класс "nc_landing_preset_PRESET_block_KEYWORD"
     *     в файле "blocks/KEYWORD.php", создающий блок и его контент.
     *     Ключевое слово, название инфоблока будут взяты из свойств $default_infoblock_keyword,
     *     $default_infoblock_name этого класса.
     *  2) Массивом с параметрами блока. Такой формат используется при создании лендинга
     *     на основе пользовательских настроек пресета.
     *     Ключ массива — ключевое слово инфоблока.
     *     Значение — массив с элементами:
     *          'block_class_keyword' => имя файла класса (без расширения), создающего инфоблок; если не задано,
     *              используется nc_landing_preset_block_generic, должно быть задано свойство 'component'
     *          'component' => компонент (ключевое слово или, если его нет, ID) — если нет значения 'block_class_keyword'
     *          'component_template' => ключевое слово или ID шаблона компонента
     *          'infoblock_name' => название инфоблока
     *          'infoblock_settings' => пользовательские настройки инфоблока
     *          'objects' => многомерный массив со свойствами создаваемых объектов
     */
    protected $blocks = array();

    /** @var string макет дизайна */
    protected $template = 'netcat_landing_basic';

    /**
     * @var bool|array Игнорируемые пользовательские параметры форм
     *    — true, если игнорируются все настройки;
     *    — array() с названиями игнорируемых параметров;
     *    — false, если настройки не игнорируются
     */
    protected $ignore_requests_form_subdivision_settings = false;


    // ---- Устанавливается в процессе работы с пресетом ----

    /** @var  string ключевое слово пресета (или пресета с набором настроек) */
    protected $keyword;

    /** @var  string путь к папке с пресетом */
    protected $path;

    /** @var bool заданы пользовательские настройки */
    protected $has_custom_settings = false;

    /** @var  int|null */
    protected $settings_id;

    /**
     * @var array  параметры форм для раздела
     */
    protected $requests_form_subdivision_settings = array();


    // --- Для переопределения в конкретных классах ---

    /**
     * Проверяет, может ли пресет использоваться для указанного компонента
     * @param int $component_id
     * @return bool
     */
    public function can_be_used_for_component($component_id) {
        return false;
    }

    /**
     * Возвращает true, если пресет может использоваться самостоятельно (без
     * записи компонента)
     * @return bool
     */
    public function can_be_used_independently() {
        return true;
    }

    /**
     * Возвращает параметры создаваемого раздела
     * @param array $landing_data
     * @return array
     */
    abstract protected function get_subdivision_properties(array $landing_data);

    /**
     * Возвращает свойства, специфичные для данного пресета, для использования при
     * создании блоков
     * @param array $params
     * @return array
     * @throws nc_landing_preset_exception
     */
    protected function get_landing_data(array $params = array()) {
        return $params;
    }

    /**
     * Возвращает параметры форм(ы) для раздела
     * @param array $landing_data
     * @return array
     */
    protected function get_requests_form_subdivision_settings(array $landing_data) {
        return array();
    }

    // --- Общие методы ---

    /**
     * @param string $keyword
     * @param string $path
     * @param null|int|array $settings   Пользовательские параметры пресета
     */
    public function __construct($keyword, $path, $settings = null) {
        if ($settings) {
            if (!is_array($settings)) {
                $settings_id = (int)$settings;
                $this->settings_id = $settings_id;
                $serialized_settings = nc_db()->get_var(
                    "SELECT `Preset_Settings` FROM `Landing_Preset_Setting` WHERE `Landing_Preset_Setting_ID` = $settings_id"
                );

                if ($serialized_settings) {
                    $settings = unserialize($serialized_settings);
                }
            }
            else {
                $settings_id = sha1(serialize($settings));
            }

            if (is_array($settings)) {
                foreach (array('name', 'description', 'template', 'blocks', 'requests_form_subdivision_settings') as $k) {
                    if (isset($settings[$k])) {
                        $this->has_custom_settings = true;
                        $this->$k = $settings[$k];
                    }
                }
            }

            $keyword .= '@' . $settings_id;
        }

        $this->keyword = $keyword;

        $this->path = $path;
    }

    /**
     * Возвращает ключевое слово пресета
     * @return string
     */
    public function get_keyword() {
        return $this->keyword;
    }

    /**
     * Возвращает значение name или description
     * @param $property
     * @return mixed
     * @throws Exception
     */
    protected function get_translated_property($property) {
        if (is_array($this->$property)) {
            $lang = nc_core::get_object()->lang->detect_lang(true);
            if (!isset($this->{$property}[$lang])) {
                $lang = 'ru';
            }
            return $this->{$property}[$lang];
        }
        else {
            return $this->$property;
        }
    }

    /**
     * Возвращает человекопонятное название пресета
     * @return string
     */
    public function get_name() {
        return $this->get_translated_property('name');
    }

    /**
     * Возвращает описание пресета
     * @return string
     */
    public function get_description() {
        return $this->get_translated_property('description');
    }

    /**
     * Возвращает true, если данный экземпляр создан на основе пользовательских настроек
     * @return bool
     */
    public function is_user_preset() {
        return $this->has_custom_settings;
    }

    /**
     * Создание лендинга
     * @param array $params
     * @return int  ID созданного раздела
     * @throws nc_landing_preset_exception
     * @throws nc_landing_resource_exception
     */
    public function create_landing_page(array $params = array()) {
        if (isset($params["component_id"]) && $params["component_id"]) {
            if (!$this->can_be_used_for_component($params["component_id"])) {
                throw new nc_landing_preset_exception(NETCAT_MODULE_LANDING_INAPPROPRIATE_PRESET);
            }
        }
        else if (!$this->can_be_used_independently()) {
            throw new nc_landing_preset_exception(NETCAT_MODULE_LANDING_INAPPROPRIATE_PRESET);
        }

        if (!isset($params["site_id"])) {
            throw new nc_landing_preset_exception(NETCAT_MODULE_LANDING_MISSING_PARAMETER . ": site_id");
        }

        $landing_data = $this->get_landing_data($params);

        // Создание раздела
        $subdivision_properties = $this->get_subdivision_properties($landing_data);
        $subdivision_id = $this->create_subdivision($params['site_id'], $subdivision_properties);

        $landing_page_id = nc_db_table::make('Landing_Page')->insert(array(
            'Preset_Keyword' => $this->get_keyword(),
            'Site_ID' => (int)$params['site_id'],
            'Subdivision_ID' => $subdivision_id,
            'Source_Component_ID' => nc_array_value($params, 'component_id'),
            'Source_Object_ID' => nc_array_value($params, 'object_id'),
        ));

        // Создание инфоблоков и контента
        $this->create_blocks($landing_page_id, $subdivision_id, $landing_data);

        // Сохранение параметров форм(ы)
        $this->save_requests_form_subdivision_settings($subdivision_id, $landing_data);

        return $subdivision_id;
    }

    /**
     * Создаёт раздел.
     * В качестве макета дизайна используется первый из $this->required_templates.
     *
     * @param int $site_id
     * @param array $params   Свойства раздела
     *      EnglishName —
     *          Если не указано, транслитерируется из Subdivision_Name.
     *          Если уже существует, добавляется суффикс "-1", "-2" и т.п.
     * @param array $template_settings   Настройки макета дизайна
     *
     * @return int   ID созданного раздела
     * @throws Exception   когда макет дизайна не найден
     */
    protected function create_subdivision($site_id, array $params = array(), array $template_settings = array()) {
        $nc_core = nc_core::get_object();

        $params += array(
            'Catalogue_ID' => $site_id,
            'Parent_Sub_ID' => nc_landing::get_instance($site_id)->get_landings_subdivision_id(true),
            'Template_ID' => $nc_core->template->get_by_id($this->template, 'Template_ID'), // ID макета по ключевому слову,
            'Checked' => 0,
            'UseMultiSubClass' => 1,
        );

        return $nc_core->subdivision->create($params, $template_settings);
    }

    /**
     * Создаёт блоки, описанные в $this->blocks
     *
     * @param $landing_page_id
     * @param $subdivision_id
     * @param array $landing_data
     * @throws nc_landing_preset_exception
     */
    protected function create_blocks($landing_page_id, $subdivision_id, array $landing_data) {
        $block_table = nc_db_table::make('Landing_Page_Block');
        foreach ($this->blocks as $keyword => $block_settings) {
            if (is_numeric($keyword)) {
                $keyword = $block_settings;
                $block_settings = array('block_class_keyword' => $keyword);
            }

            $block = $block_settings['block_class_keyword']
                        ? $this->get_block($block_settings['block_class_keyword'])
                        : new nc_landing_preset_block_default($this->get_folder_path_from_document_root());
            $infoblock_id = $block->create($subdivision_id, $block_settings, $landing_data);

            if (isset($block_settings['block_class_keyword'])) {
                $block_table->insert(array(
                    'Landing_Page_ID' => $landing_page_id,
                    'Infoblock_ID' => $infoblock_id,
                    'Block_Class_Keyword' => $block_settings['block_class_keyword'],
                ));
            }
        }
    }

    /**
     * @param $keyword
     * @return nc_landing_preset_block
     * @throws nc_landing_preset_exception
     */
    protected function get_block($keyword) {
        $block_class_file = $this->path . 'blocks/' . $keyword . '.php';
        if (!file_exists($block_class_file)) {
            throw new nc_landing_preset_exception(NETCAT_MODULE_LANDING_MISSING_RESOURCE . ": $block_class_file");
        }

        include_once $block_class_file;
        $block_class_name = get_class($this) . '_block_' . $keyword;
        if (!class_exists($block_class_name)) {
            throw new nc_landing_preset_exception("Class not found: $block_class_name");
        }

        return new $block_class_name($this->get_folder_path_from_document_root());
    }

    /**
     * Возвращает путь к папке пресета от корня сайта (DOCUMENT_ROOT)
     * @return string   with a trailing '/'
     */
    public function get_folder_path_from_document_root() {
        $path = substr($this->path, strlen(nc_core::get_object()->DOCUMENT_ROOT));
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $path = rtrim($path, '/') . '/';
        return $path;
    }

    /**
     * Возвращает путь к скриншоту (или null, если скриншота нет)
     * @param string $screenshot_type    nc_landing::PRESET_SCREENSHOT, nc_landing::PRESET_SCREENSHOT_THUMBNAIL
     * @return string
     */
    public function get_screenshot_path($screenshot_type = nc_landing::PRESET_SCREENSHOT) {
        $doc_root = nc_core::get_object()->DOCUMENT_ROOT;

        if ($this->settings_id) {
            $file_path = nc_landing::get_instance()->get_user_preset_settings_path() .
                         $this->settings_id . '/' .
                         $screenshot_type;
        }
        else {
            $file_path = $this->get_folder_path_from_document_root() . $screenshot_type;
        }

        if (file_exists($doc_root . $file_path . '.png')) {
            return $file_path . '.png';
        }
        else if (file_exists($doc_root . $file_path . '.jpg')) {
            return $file_path . '.jpg';
        }
        else {
            return null;
        }
    }

    /**
     * Возвращает путь к миниатюре скриншота (или путь к скриншоту; или null, если скриншота нет)
     * @return string
     */
    public function get_screenshot_thumbnail_path() {
        return $this->get_screenshot_path(nc_landing::PRESET_SCREENSHOT_THUMBNAIL) ?:
               $this->get_screenshot_path(nc_landing::PRESET_SCREENSHOT);
    }

    /**
     * Сохраняет параметры форм(ы) для разделов
     * @param $subdivision_id
     * @param array $landing_data
     */
    protected function save_requests_form_subdivision_settings($subdivision_id, array $landing_data) {
        if (!nc_module_check_by_keyword('requests')) {
            return;
        }

        $form_settings = $this->get_requests_form_subdivision_settings($landing_data);

        $user_form_settings = $this->requests_form_subdivision_settings;
        if ($user_form_settings && $this->ignore_requests_form_subdivision_settings !== true) {
            if ($this->ignore_requests_form_subdivision_settings) {
                // часть настроек игнорируется
                foreach ($form_settings as $form_type => $form_type_settings) {
                    foreach ($form_type_settings as $k => $v) {
                        if (!in_array($k, $this->ignore_requests_form_subdivision_settings)) {
                            $form_settings[$form_type][$k] = $v;
                        }
                    }
                }
            }
            else {
                // используются все настройки
                $form_settings = $user_form_settings;
            }
        }

        $site_id = nc_core::get_object()->subdivision->get_by_id($subdivision_id, 'Catalogue_ID');

        foreach ($form_settings as $form_type => $settings) {
            $form_type_settings = new nc_requests_form_settings_subdivision($settings);
            $form_type_settings
                ->set_id(null)
                ->set('Catalogue_ID', $site_id)
                ->set('Subdivision_ID', $subdivision_id)
                ->set('FormType', $form_type)
                ->save();
        }
    }


    // -------- ArrayAccess interface -----------
    public function offsetGet($offset) {
        return $this->$offset;
    }

    public function offsetSet($offset, $value) {
        throw new RuntimeException;
    }

    public function offsetExists($offset) {
        return isset($this->$offset);
    }

    public function offsetUnset($offset) {
        throw new RuntimeException;
    }

}