<?php

/**
 * Класс, создающий блок.
 * В конкретном классе должны быть переопределены:
 *  — свойства $component, $default_infoblock_keyword, $default_infoblock_name
 *  — если создаются объекты — метод get_objects_properties()
 *  — если компонент имеет пользовательские настройки — метод get_default_infoblock_settings()
 *  — если в компоненте используется форма — метод get_requests_form_infoblock_settings()
 */
abstract class nc_landing_preset_block {

    // ---- Для переопределения в конкретных классах ----

    /** @var  string Ключевое слово компонента, используемого для блока */
    protected $component;

    /** @var int  Ключевое слово шаблона компонента по умолчанию (если не указано в настройках) */
    protected $default_component_template = 0;

    /** @var  string  EnglishName инфоблока по умолчанию (если не указано в настройках) */
    protected $default_infoblock_keyword;

    /** @var  string  Название инфоблока по умолчанию (если не указано в настройках) */
    protected $default_infoblock_name;

    /** @var bool  Если true, всегда генерировать объекты вместо сохранённых в пользовательском пресете */
    protected $ignore_user_objects = false;

    /** @var bool|array
     *  Игнорировать пользовательские настройки при создании нового лендинга:
     *    — true, если игнорируются все настройки;
     *    — array() с названиями игнорируемых параметров;
     *    — false, если настройки не игнорируются
     */
    protected $ignore_user_infoblock_settings = false;

    /** @var bool|array  Игнорируемые пользовательские параметры форм, аналогично $ignore_user_infoblock_settings */
    protected $ignore_requests_form_infoblock_settings = false;


    // ---- Устанавливается при работе с классом ----

    /** @var  string  путь к директории пресета от DOCUMENT_ROOT */
    protected $preset_folder_path_from_document_root;

    // ----

    /**
     * nc_landing_preset_block constructor.
     *
     * @param string $preset_folder_path_from_document_root
     */
    public function __construct($preset_folder_path_from_document_root) {
        $this->preset_folder_path_from_document_root = $preset_folder_path_from_document_root;
    }

    /**
     * Создание инфоблока и контента в нём (template method)
     *
     * @param $subdivision_id
     * @param array $settings   Настройки блока, см. описание $blocks в nc_landing_preset
     * @param array $landing_data   Данные, специфичные для пресета (например, может содержать товар)
     * @return int  ID инфоблока
     * @throws nc_landing_preset_exception
     */
    public function create($subdivision_id, array $settings, array $landing_data) {
        $nc_core = nc_core::get_object();

        // создание инфоблока
        $infoblock_id = $this->create_infoblock($subdivision_id, $settings, $landing_data);

        // создание объектов
        if (isset($settings['objects']) && !$this->ignore_user_objects) {
            $objects = $settings['objects'];
        }
        else {
            $objects = $this->get_objects_properties($infoblock_id, $settings, $landing_data);
        }

        if ($objects) {
            foreach ($objects as $object_properties) {
                $nc_core->message->create($infoblock_id, $object_properties);
            }
        }

        // настройки форм
        $this->save_requests_form_infoblock_settings($infoblock_id, $landing_data);

        return $infoblock_id;
    }

    /**
     * @param string $file
     * @return string
     */
    public function get_image_path($file) {
        return $this->preset_folder_path_from_document_root . "images/$file";
    }

    // ---- Для переопределения в конкретных классах ----

    /**
     * Возвращает пользовательские настройки инфоблока по умолчанию (hook method)
     *
     * @param $subdivision_id
     * @param $settings
     * @param array $landing_data
     * @return array
     */
    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        return array();
    }

    /**
     * Возвращает массив массивов со свойствами объектов, которые нужно создать (hook method)
     *
     * @param $infoblock_id
     * @param $settings
     * @param array $landing_data
     * @return array
     */
    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array();
    }

    /**
     * Возвращает настройки кнопки формы для инфоблока
     * @param array $landing_data
     * @return null|array
     */
    protected function get_requests_form_infoblock_settings(array $landing_data) {
        return array();
    }

    // ---- Общие методы ----

    /**
     * Создаёт инфоблок
     * @param $subdivision_id
     * @param array $settings
     * @param array $landing_data
     * @return int
     * @throws nc_landing_preset_exception
     */
    protected function create_infoblock($subdivision_id, array $settings, array $landing_data) {
        $nc_core = nc_core::get_object();

        $component = nc_array_value($settings, 'component', $this->component);
        $component_template = nc_array_value($settings, 'component_template', $this->default_component_template);
        if ($component_template) {
            $template_id = $nc_core->component->get_component_template_by_keyword($component, $component_template, 'Class_ID');
        } else {
            $template_id = null;
        }

        // Свойства инфоблока
        $infoblock_properties = array(
            'Subdivision_ID' => $subdivision_id,
            'Sub_Class_Name' => nc_array_value($settings, 'infoblock_name', $this->default_infoblock_name),
            'EnglishName' => nc_array_value($settings, 'infoblock_keyword', $this->default_infoblock_keyword),
            'Checked' => 1,
            'Class_Template_ID' => $template_id,
        );

        // Пользовательские настройки инфоблока
        $default_infoblock_settings = $this->get_default_infoblock_settings($subdivision_id, $settings, $landing_data);
        $user_infoblock_settings = nc_array_value($settings, 'infoblock_settings');

        if ($user_infoblock_settings && $this->ignore_user_infoblock_settings !== true) {
            $user_infoblock_settings = nc_a2f::evaluate($user_infoblock_settings);
            if ($this->ignore_user_infoblock_settings !== false) {
                // использовать только часть настроек, заданных пользователем
                $custom_settings = $default_infoblock_settings;
                foreach ($user_infoblock_settings as $k => $v) {
                    if (!in_array($k, $this->ignore_user_infoblock_settings)) {
                        $custom_settings[$k] = $v;
                    }
                }
            }
            else {
                // использовать все настройки, заданные пользователем
                $custom_settings = $user_infoblock_settings;
            }
        }
        else {
            // использовать настройки для блока по умолчанию
            $custom_settings = $default_infoblock_settings;
        }

        // Создаём инфоблок
        return $nc_core->sub_class->create($component, $infoblock_properties, $custom_settings);
    }


    /**
     * Сохранение настроек форм
     * @param $infoblock_id
     * @param array $landing_data
     */
    protected function save_requests_form_infoblock_settings($infoblock_id, array $landing_data) {
        if (!nc_module_check_by_keyword('requests')) {
            return;
        }

        $form_settings = $this->get_requests_form_infoblock_settings($landing_data);

        if (!empty($settings['requests_form_infoblock_settings']) && $this->ignore_requests_form_infoblock_settings !== true) {
            $user_form_settings = $settings['requests_form_infoblock_settings'];
            if ($this->ignore_requests_form_infoblock_settings) {
                // часть настроек игнорируется
                foreach ($form_settings as $form_type => $form_type_settings) {
                    foreach ($form_type_settings as $k => $v) {
                        if (!in_array($k, $this->ignore_requests_form_infoblock_settings)) {
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

        if ($form_settings) {
            foreach ($form_settings as $form_type => $form_type_settings) {
                $settings = new nc_requests_form_settings_infoblock($form_type_settings);
                $settings
                    ->set_id(null)
                    ->set('Infoblock_ID', $infoblock_id)
                    ->set('FormType', $form_type)
                    ->save();
            }
        }
    }

}