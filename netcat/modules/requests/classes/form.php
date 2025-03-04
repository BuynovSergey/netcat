<?php

/**
 * Класс для работы с формами на сайте
 */
class nc_requests_form {

    /** @var self[] */
    static protected $instances = array();
    /** @var bool  был ли уже отправлен скрипт forms.js */
    static protected $form_script_added = false;
    /** @var array  селекторы, для которых уже был отправлен JS для инициализации форм */
    static protected $form_added_init_selectors = array();

    protected $site_id;
    protected $source_subdivision_id;
    protected $subdivision_id;
    protected $infoblock_id;
    protected $form_type;

    /** @var  nc_requests_form_settings_infoblock */
    protected $infoblock_settings;

    /** @var  nc_requests_form_settings_subdivision */
    protected $subdivision_settings;

    /** @var  string ID формы для тэга <form> */
    protected $id;

    /** @var array названия полей настроек для интерфейса */
    static protected $settings_caption = array(
        'StandaloneForm_Header' => NETCAT_MODULE_REQUESTS_FORM_HEADER_CAPTION,
        'StandaloneForm_TextAfterHeader' => NETCAT_MODULE_REQUESTS_FORM_TEXT_AFTER_HEADER_CAPTION,
        'EmbeddedForm_Header' => NETCAT_MODULE_REQUESTS_FORM_HEADER_CAPTION,
        'EmbeddedForm_TextAfterHeader' => NETCAT_MODULE_REQUESTS_FORM_TEXT_AFTER_HEADER_CAPTION,
    );

    /** @var bool  флаг того, что выполняется метод get_form() */
    protected $inside_get_form = false;


    /**
     * Возвращает экземпляр nc_requests_form, который можно использовать для
     * вставки форм и кнопок в указанный блок.
     * @param int $infoblock_id  ID инфоблока, в который вставлена форма
     * @param string $form_type  Ключевое слово типа формы на странице (только латинские буквы,
     *      подчёркивание и минус); если не задано, то равно 'default'
     * @return self
     */
    public static function get_instance($infoblock_id = 0, $form_type = 'default') {
        if (empty($form_type)) {
            $form_type = 'default';
        }
        else {
            $form_type = preg_replace('/[^\w-]+/', '_', $form_type);
        }

        $infoblock_id = (int)$infoblock_id;
        if (!$infoblock_id) {
            $infoblock_id = nc_core::get_object()->sub_class->get_current('Sub_Class_ID');
        }

        if (!isset(self::$instances[$infoblock_id][$form_type])) {
            self::$instances[$infoblock_id][$form_type] = new self($infoblock_id, $form_type);
        }

        return self::$instances[$infoblock_id][$form_type];
    }

    /**
     * nc_requests_form constructor.
     *
     * @param $infoblock_id
     * @param $form_type
     */
    protected function __construct($infoblock_id, $form_type) {
        $nc_core = nc_Core::get_object();
        $this->site_id = $nc_core->sub_class->get_by_id($infoblock_id, 'Catalogue_ID');
        $this->subdivision_id = $nc_core->sub_class->get_by_id($infoblock_id, 'Subdivision_ID');
        $this->infoblock_id = $infoblock_id;
        $this->form_type = $form_type;
        $this->id = 'nc_requests_form_' . uniqid();

        // У форм вне контентной области `Subdivision_ID`, полученный от инфоблока, всегда будет равен 0
        if (!$this->subdivision_id) {
            $this->source_subdivision_id = $nc_core->subdivision->get_current('Subdivision_ID');
        } else {
            $this->source_subdivision_id = $this->subdivision_id;
        }
    }

    /**
     * @return nc_requests
     */
    protected function get_module_object() {
        return nc_requests::get_instance($this->site_id);
    }

    /**
     * @return nc_requests_form_settings_infoblock
     */
    protected function get_infoblock_settings() {
        return $this->infoblock_settings ?: nc_requests_form_settings_infoblock::for_infoblock($this->infoblock_id, $this->form_type);
    }

    /**
     * @return nc_requests_form_settings_subdivision
     */
    protected function get_subdivision_settings() {
        // У форм вне контентной области настройки должны быть общие. Они сохраняются с `Subdivision_ID` равным 0
        return $this->subdivision_settings ?: nc_requests_form_settings_subdivision::for_subdivision($this->subdivision_id, $this->form_type, $this->site_id);
    }

    /**
     * Возвращает параметр формы в инфоблоке (с учётом настроек форм того же типа в разделе)
     * @param $setting_name
     *
     *    Свойства, общие для всех форм данного типа в разделе:
     *      Subdivision_VisibleFields — массив с названиями полей формы
     *      Subdivision_FieldProperties — массив со свойствами полей формы
     *      Subdivision_NotificationEmail — адреса получателей оповещения о заполнении формы (текст, несколько адресов через запятую)
     *
     *     Цели для формы в текущем разделе для Google Analytics и Яндекс.Метрики
     *      Subdivision_OpenPopupButton_AnalyticsCategories — категории целей открытия формы
     *      Subdivision_OpenPopupButton_AnalyticsLabels — ярлыки целей открытия формы
     *      Subdivision_SubmitButton_AnalyticsCategories — категории целей отправки формы
     *      Subdivision_SubmitButton_AnalyticsLabels — ярлыки целей отправки формы
     *
     *    Параметры для формы во всплывающем окне и формы в виде инфоблока с действием «добавление»
     *    (для всех форм данного типа в разделе)
     *      StandaloneForm_ComponentTemplate_ID — ID шаблона компонента формы (0 для шаблона по умолчанию)
     *      StandaloneForm_Header — заголовок формы
     *      StandaloneForm_TextAfterHeader — подзаголовок
     *      StandaloneForm_SubmitButton_Text — текст на кнопке отправки
     *      StandaloneForm_SubmitButton_BackgroundColor — цвет кнопки
     *      StandaloneForm_SubmitButton_ShowPrice — показывать ли цену в кнопке
     *
     *    Параметры кнопки, открывающей форму (для текущего инфоблока)
     *      OpenPopupButton_Text — текст кнопки
     *      OpenPopupButton_BackgroundColor — цвет кнопки
     *      OpenPopupButton_ShowPrice — показывать ли цену в кнопке
     *
     *    Параметры встроенной в текущий инфоблок формы
     *      EmbeddedForm_ComponentTemplate_ID — ID шаблона компонента формы (0 для шаблона по умолчанию)
     *      EmbeddedForm_Header — заголовок
     *      EmbeddedForm_TextAfterHeader — подзаголовок
     *      EmbeddedForm_SubmitButton_Text — текст на кнопке отправки
     *      EmbeddedForm_SubmitButton_BackgroundColor — цвет кнопки
     *      EmbeddedForm_SubmitButton_Type — тип кнопки (основная, дополнительная)
     *      EmbeddedForm_SubmitButton_ShowPrice — показывать ли цену в кнопке
     *
     *     Цели для формы в текущем инфоблоке для Google Analytics и Яндекс.Метрики
     *      Infoblock_OpenPopupButton_AnalyticsCategories — категории целей открытия формы
     *      Infoblock_OpenPopupButton_AnalyticsLabels — ярлыки целей открытия формы
     *      Infoblock_SubmitButton_AnalyticsCategories — категории целей отправки формы
     *      Infoblock_SubmitButton_AnalyticsLabels — ярлыки целей отправки формы
     *
     * @return mixed
     */
    public function get_setting($setting_name) {
        $infoblock_settings = $this->get_infoblock_settings();
        if (isset($infoblock_settings[$setting_name])) {
            return $infoblock_settings[$setting_name];
        }

        $subdivision_settings = $this->get_subdivision_settings();
        if (isset($subdivision_settings[$setting_name])) {
            return $subdivision_settings[$setting_name];
        }

        return null;
    }

    /**
     * Сохраняет значение параметра формы в инфоблоке (если данный параметр
     * действует для инфоблока) или для всех форм данного типа в разделе
     * (если параметр является общим для форм одного типа)
     *
     * @param $setting_name
     * @param $value
     * @return bool
     */
    public function save_setting($setting_name, $value) {
        $infoblock_settings = $this->get_infoblock_settings();
        if ($infoblock_settings->has_property($setting_name)) {
            $infoblock_settings->set($setting_name, $value)->save();
            return true;
        }

        $subdivision_settings = $this->get_subdivision_settings();
        if ($subdivision_settings->has_property($setting_name)) {
            $subdivision_settings->set($setting_name, $value)->save();
            return true;
        }

        return false;
    }

    /**
     * Сохраняет настройки из массива (так же, как метод save_setting())
     * @param array $settings
     */
    public function save_settings(array $settings) {
        foreach ($settings as $k => $v) {
            $this->save_setting($k, $v);
        }
    }


    /**
     * Возвращает список полей, которые должны быть отображены в форме
     * @return array
     */
    public function get_visible_fields() {
        $fields = (array)$this->get_setting('Subdivision_VisibleFields');
        if (!$this->has_item_variants()) {
            // Если нет вариантов, убираем Item_VariantName
            $variant_name_index = array_search('Item_VariantName', $fields);
            if ($variant_name_index !== false) {
                unset($fields[$variant_name_index]);
            }
        }
        return $fields;
    }

    /**
     * Возвращает ID шаблона компонента «заявки», если используется основной шаблон — ID компонента
     * @return int
     */
    protected function get_request_component_template_id() {
        return $this->get_setting('Infoblock_ComponentTemplate_ID')
            ?: $this->get_module_object()->get_request_component_id();
    }

    /**
     * Возвращает название CSS-класса для блока формы
     * @return string
     */
    protected function get_css_class_name() {
        return nc_core::get_object()->component->get_css_class_name($this->get_request_component_template_id());
    }

    /**
     * Возвращает уникальный ID формы для тега <form>
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @param $button_type
     * @param $event_type
     * @return string
     */
    protected function get_analytics_attribute_value($button_type, $event_type) {
        $subdivision_value = $this->get_setting("Subdivision_{$button_type}_Analytics{$event_type}");
        $infoblock_value   = $this->get_setting("Infoblock_{$button_type}_Analytics{$event_type}");

        return htmlspecialchars(trim("$subdivision_value,$infoblock_value", " ,"), ENT_QUOTES);
    }

    /**
     * @param $event
     * @param $button_type
     * @return string
     */
    protected function get_analytics_attributes($event, $button_type) {
        $result = '';

        $categories = $this->get_analytics_attribute_value($button_type, 'Categories');
        if ($categories) {
            $result .= ' data-analytics-' . $event . '-category="' . $categories . '"';
        }

        $labels = $this->get_analytics_attribute_value($button_type, 'Labels');
        if ($labels) {
            $result .= ' data-analytics-' . $event . '-label="' . $labels . '"';
        }

        return $result;
    }

    /**
     * Возвращает атрибуты, которые использует модуль статистики для передачи
     * целей в Google Analytics и Яндекс.Метрику (при отправке формы)
     * @return string
     */
    public function get_analytics_attributes_for_submit() {
        return $this->get_analytics_attributes('submit', 'SubmitButton');
    }

    /**
     *
     */
    public function get_analytics_attributes_for_popup_button() {
        return $this->get_analytics_attributes('click', 'OpenPopupButton');
    }

    /**
     * Возвращает строку с формой для вставки в блок
     *
     * @param array $parameters
     * @return string
     * @throws Exception
     */
    public function get_form(array $parameters = array()) {
        $this->inside_get_form = true;

        $nc_core = nc_core::get_object();

        $requests = $this->get_module_object();
        $request_infoblock_id = $requests->get_request_infoblock_id();
        $request_infoblock_properties = $nc_core->sub_class->get_by_id($request_infoblock_id);
        $request_component_id = $requests->get_request_component_id();
        $request_component_template_id = $this->get_setting('ComponentTemplate_ID') ?: $request_component_id;

        // Подготовка к загрузке файла шаблона AddForm
        $file_class = new nc_tpl_component_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        $file_class->load(
            $request_component_template_id,
            $nc_core->component->get_by_id($request_component_template_id, 'File_Path'),
            $nc_core->component->get_by_id($request_component_template_id, 'File_Hash')
        );
        $file_class->include_all_required_assets();

        $nc_field_path = $file_class->get_field_path('AddTemplate');

        // Подготовка переменных для шаблона
        $warnText = null;
        $current_cc = $request_infoblock_properties;
        $classID = $request_component_id;
        $nc_ctpl = $request_component_template_id;
        $catalogue = $this->site_id;
        $cc = $request_infoblock_id;
        $sub = $request_infoblock_properties['Subdivision_ID'];
        $nc_parent_field_path = $file_class->get_parent_field_path('AddTemplate');
        $nc_component_css_class = $nc_core->component->get_css_class_name($request_component_template_id, $request_component_id);
        $nc_component_css_selector = '.' . str_replace(' ', '.', $nc_component_css_class);
        $nc_block_id = nc_make_block_id("$this->infoblock_id/$this->form_type/" . json_encode($parameters));

        // + utm-метки
        extract($this->get_utm_field_values());

        // + дополнительные параметры
        $f_Source_Subdivision_ID = $this->source_subdivision_id;
        $f_Source_Infoblock_ID = $this->infoblock_id;
        $f_FormType = $this->form_type;
        if ($parameters) {
            extract($parameters, EXTR_PREFIX_ALL, 'f');
        }

        // + глобальные переменные...
        extract($GLOBALS, EXTR_SKIP);

        // Подключение файла шаблона
        ob_start();
        include $nc_field_path;
        $result = ob_get_clean();

        if ($result) {
            // Регистрация использования стилей из SiteStyles.css
            $nc_core->page->register_component_usage($request_component_id, $request_component_template_id);

            $result = '<div class="tpl-block-add-form ' .  $nc_component_css_class . '" id="'. $nc_block_id .'">' .
                      $result .
                      '</div>' .
                      $this->get_form_init_script($nc_component_css_class);
        }

        $this->inside_get_form = false;
        return $result;
    }

    /**
     * Возвращает код для вставки JavaScript-скрипта для обработки отправки формы
     * (отправка через XHR, показ сообщений об успехе и ошибке)
     * @param bool $inline вставить содержимое скрипта (иначе — ссылка на скрипт)
     * @return string
     */
    protected function get_form_script($inline = true) {
        if (!self::$form_script_added) {
            $nc_core = nc_core::get_object();
            $nc_core->page->require_asset('jquery', array('defer' => false));
            $nc_core->page->require_asset('nc_event_dispatch');
            if ($inline) {
                $script_path = nc_module_folder('requests') . 'js/forms.min.js';
                $result = '<script>' . file_get_contents($script_path) . '</script>';
            }
            else {
                $result = '<script src="' . nc_add_revision_to_url(nc_module_path('requests') . 'js/forms.min.js') . '"></script>';
            }
            self::$form_script_added = true;

            return $result . "\n";
        }
        else {
            return '';
        }
    }

    /**
     * Возвращает код для инициализации встроенной в блок формы
     * @param $form_class_names
     * @return string
     */
    protected function get_form_init_script($form_class_names) {
        if (!isset(self::$form_added_init_selectors[$form_class_names])) {
            self::$form_added_init_selectors[$form_class_names] = true;
            $admin_mode = nc_core::get_object()->admin_mode ? 'true' : 'false';
            return $this->get_form_script() .
                   "<script>$(function(){nc_requests_form_init('$form_class_names','body',$admin_mode)})</script>\n";
        }
        else {
            return '';
        }
    }

    /**
     * Объект с информацией о товарах
     * @return nc_subdivision_goods_data
     */
    protected function get_goods_data() {
        return nc_subdivision_goods_data::for_subdivision($this->source_subdivision_id);
    }

    /**
     * Возвращает скрытые служебные поля заявок для форм (в виде <input type=hidden>).
     * @return string
     */
    public function get_hidden_fields_inputs() {
        $values = array_merge(
            $this->get_utm_field_values(),
            array(
               'f_Source_Subdivision_ID' => $this->source_subdivision_id,
               'f_Source_Infoblock_ID' => $this->infoblock_id,
               'f_FormType' => $this->form_type,
               'f_Item_ID' => $this->get_goods_data()->get_first_item('Message_ID'),
           )
        );

        $result = '';
        foreach ($values as $k => $v) {
            $result .= '<input type="hidden" name="' . $k . '" value="' . htmlspecialchars($v) . '">' . "\n";
        }

        return $result;
    }

    /**
     * Возвращает значения utm_* в виде массива, где ключ — f_UTM_* (как в свойствах форм),
     * а значение берётся из соответствующей utm-метки в $_REQUEST.
     * @return array
     */
    protected function get_utm_field_values() {
        $values = array();
        foreach (array('UTM_Source', 'UTM_Medium', 'UTM_Campaign', 'UTM_Content', 'UTM_Term') as $utm_key) {
            $values['f_' . $utm_key] = nc_array_value($_REQUEST, strtolower($utm_key));
        }
        return $values;
    }

    protected function get_controller_url($action) {
       return nc_module_path('requests') . 'admin/' .
              "?controller=form&action=$action" .
              "&infoblock_id=$this->infoblock_id" .
              "&form_type=$this->form_type";
    }

    /**
     * Проверяет, нужно ли выводить оверлей для редактирования настроек формы или кнопки (режим и права)
     * @return bool
     */
    protected function should_show_modal_trigger() {
        /** @var Permission $perm */
        global $perm;
        return nc_core::get_object()->admin_mode && $perm && $perm->isSubClassAdmin($this->infoblock_id);
    }

    /**
     * Возвращает элемент (оверлей), открывающий редактирование состава полей и
     * других свойств формы в пределах раздела
     * @return string
     */
    public function get_subdivision_fields_modal_trigger() {
        if (!$this->should_show_modal_trigger()) {
            return '';
        }

        return nc_modal_dialog_trigger($this->get_controller_url('show_subdivision_fields_dialog'), 'xlarge');
    }

    /**
     * Возвращает элемент (оверлей), открывающий редактирование свойств кнопки
     *
     * @param string $button_type 'StandaloneForm_SubmitButton', 'EmbeddedForm_SubmitButton', 'OpenPopupButton'
     * @param string $button_id
     * @return string
     */
    public function get_button_modal_trigger($button_type, $button_id) {
        if (!$this->should_show_modal_trigger()) {
            return '';
        }

        $button_settings_dialog_url =
            $this->get_controller_url("show_button_settings_dialog") .
            "&button_type=$button_type" .
            "&button_id=$button_id";

        return nc_modal_dialog_trigger($button_settings_dialog_url, 'small');
    }

    /**
     * Проверяет, если в текущем разделе варианты товаров [netcat_page_block_goods_common_data]
     * @return bool
     */
    public function has_item_variants() {
        $subdivision_items = $this->get_goods_data()->get_enabled_items();
        return (is_array($subdivision_items) && count($subdivision_items) > 1);
    }

    /**
     * Формирует <select> со списком вариантов товаров [netcat_page_block_goods_common_data]
     * в указанном разделе для использования в форме заявки.
     * Если вариантов товаров нет или есть только один товар, метод возвращает
     * пустую строку.
     *
     * @return string
     */
    public function get_item_variant_select() {
        $subdivision_items = $this->get_goods_data()->get_enabled_items();

        if (!is_array($subdivision_items) || count($subdivision_items) < 2) {
            return '';
        }

        $field_input = "<select name='f_Item_VariantName'>\n";
        foreach ($subdivision_items as $item) {
            $field_input .= "<option data-item-price='" . htmlspecialchars($item['ItemPrice'], ENT_QUOTES) .
                            "' data-item-id='" . htmlspecialchars($item['Message_ID']) . "'>" .
                            htmlspecialchars($item['VariantName']) .
                            "</option>\n";
        }
        $field_input .= "</select>\n";

        $field_script = "<script>$(function(){
            var f = $('#{$this->get_id()}');
            f.find('select[name=f_Item_VariantName]').change(function() {
                f.find('input[name=f_Item_ID]').val($(this).data('itemId'));
            });
        });</script>";

        $field_input .= $field_script;

        return $field_input;
    }

    /**
     * Возвращает класс для кнопки в зависимости от выбранного цвета:
     *  — tpl-layout-background-dark
     *  — tpl-layout-background-bright
     *  — tpl-layout-background-transparent
     * @param $button_color
     * @return string
     */
    protected function get_button_css_class($button_color) {
        if ($button_color == 'transparent') {
            return 'tpl-layout-background-transparent';
        }
        else if ($button_color) {
            return 'tpl-layout-background-' . (nc_is_bright_color($button_color) ? 'bright' : 'dark');
        }
        else {
            return '';
        }
    }

    /**
     * Генерирует кнопку
     *
     * @param string $button_id ID кнопки в DOM
     * @param string $settings_prefix Тип кнопки: StandAloneForm_SubmitButton, EmbeddedForm_SubmitButton, OpenPopupButton
     * @param array $parameters массив с дополнительными параметрами:
     *  - button_caption - текст на кнопке
     *  - button_custom_class_list - собственный класс кнопки(если задано, стандартный набор классов будет перезаписан переданным)
     *
     * @return string
     */
    protected function get_button($button_id, $settings_prefix, array $parameters = array()) {
        $price = '';
        $style = '';

        if ($this->get_setting($settings_prefix . '_ShowPrice')) {
            $subdivision_first_item = $this->get_goods_data()->get_first_item();
            if ($subdivision_first_item) {
                $price = "<span class='tpl-block-button-title tpl-field-item-price'>" .
                        htmlspecialchars("$subdivision_first_item[ItemPrice] $subdivision_first_item[ItemPriceCurrency]") .
                        "</span>" .
                        "<span class='tpl-block-button-divider'></span>";
            }
        }

        $has_custom_class_list = !empty($parameters["button_custom_class_list"]);
        $button_background_color = $this->get_setting($settings_prefix . '_BackgroundColor');
        $button_class = $has_custom_class_list
            ? $parameters["button_custom_class_list"]
            : "tpl-block-button" . $this->get_setting($settings_prefix . "_Type") . $this->get_button_css_class($button_background_color);

        if (!$has_custom_class_list && $button_background_color) {
            $style = '<style>.tpl-component-netcat-module-requests-request .tpl-block-button { background-color: ' . htmlspecialchars($button_background_color) . '; }</style>';
        }

        $button =
            "<button id='$button_id' class='$button_class' type='submit'" .
            ($settings_prefix == 'OpenPopupButton' ? $this->get_analytics_attributes_for_popup_button() : '') .
            ">" .
                "<span class='tpl-block-button-content'>" .
                    $price .
                    "<span class='tpl-block-button-title'>" .
                        htmlspecialchars(nc_array_value($parameters, "button_caption", $this->get_setting($settings_prefix . '_Text'))) .
                    "</span>" .
                "</span>" .
            "</button>";

        return $style . $button;
    }

    /**
     * @return string
     */
    protected function generate_button_id() {
        return $this->id . '_' . uniqid();
    }

    /**
     * Возвращает стандартную кнопку, отправляющую форму заявки.
     *
     * Разметка стандартной кнопки:
     *  <div class='tpl-component-netcat-module-requests-request tpl-link-form-submit'>
     *      <button class='tpl-block-button' type='submit'>
     *          <span class='tpl-block-button-content'>
     *              <span class='tpl-block-button-title tpl-field-item-price'>Цена товара (если включена и есть)</span>
     *              <span class='tpl-block-button-divider'></span>
     *              <span class='tpl-block-button-title'>Текст кнопки</span>
     *          </span>
     *      </button>
     *  </div>
     *
     * В зависимости от цвета фона кнопки <button> может иметь класс:
     *  — tpl-layout-background-dark (тёмный фон, белый текст)
     *  — tpl-layout-background-bright (светлый фон, чёрный текст)
     *  — tpl-layout-background-transparent (прозрачный фон, чёрный текст)
     *
     * @param array $parameters
     * @return string
     */
    public function get_submit_button(array $parameters = array()) {
        $settings_prefix = $this->inside_get_form ? 'EmbeddedForm_SubmitButton' : 'StandaloneForm_SubmitButton';

        $button_id = $this->generate_button_id();
        $button = $this->get_button($button_id, $settings_prefix, $parameters);

        $result =
            "<div class='tpl-link-form-submit'>" .
            $button .
            $this->get_button_modal_trigger($settings_prefix, $button_id) .
            "</div>";

        return $result;

    }

    /**
     * Возвращает стандартную кнопку, открывающую форму в виде модального диалога.
     *
     * Разметка стандартной кнопки:
     *  <div class='tpl-component-netcat-module-requests-request tpl-link-form-open'>
     *      <button class='tpl-block-button' type='submit'>
     *          <span class='tpl-block-button-content'>
     *              <span class='tpl-block-button-title tpl-field-item-price'>Цена товара (если включена и есть)</span>
     *              <span class='tpl-block-button-divider'></span>
     *              <span class='tpl-block-button-title'>Текст кнопки</span>
     *          </span>
     *      </button>
     *  </div>
     *
     * В зависимости от цвета фона кнопки <button> может иметь класс:
     *  — tpl-layout-background-dark (тёмный фон, белый текст)
     *  — tpl-layout-background-bright (светлый фон, чёрный текст)
     *  — tpl-layout-background-transparent (прозрачный фон, чёрный текст)
     *
     * @param array $parameters
     * @return string
     */
    public function get_popup_button(array $parameters = array()) {
        $button_id = $this->generate_button_id();
        $button = $this->get_button($button_id, 'OpenPopupButton', $parameters);

        $result =
            "<div class='{$this->get_css_class_name()} tpl-link-form-open'>" .
            $button .
            "</div>" .
            $this->get_popup_script($button_id, $parameters, true);

        return $result;
    }


    /**
     * Возвращает код для инициализации кнопки, открывающей форму в виде модального диалога.
     * Метод может пригодиться вне этого класса в редких случаях, когда есть необходимость
     * в нестандартной кнопке. В обычных случаях следует пользоваться методом get_popup_button().
     *
     * @param string $button_id ID кнопки в DOM
     * @param array $parameters
     * @param bool $show_overlay
     * @return string
     * @throws Exception
     * @internal param int $source_infoblock_id
     * @internal param string $form_type
     */
    public function get_popup_script($button_id, array $parameters = array(), $show_overlay = false) {
        $nc_core = nc_core::get_object();

        $requests = $this->get_module_object();
        $request_infoblock_id = $requests->get_request_infoblock_id();
        $hidden_inputs = array(
            'catalogue' => $this->site_id,
            'cc' => $request_infoblock_id,
            'sub' => $nc_core->sub_class->get_by_id($request_infoblock_id, 'Subdivision_ID'),
            'f_Source_Subdivision_ID' => $this->source_subdivision_id,
            'f_Source_Infoblock_ID' => $this->infoblock_id,
            'f_FormType' => $this->form_type,
        );

        foreach ($parameters as $k => $v) {
            $hidden_inputs['f_' . $k] = $v;
        }

        $nc_component_css_class = $this->get_css_class_name();

        if ($nc_core->admin_mode) {
            $requests_subdivision_id = $nc_core->sub_class->get_by_id($request_infoblock_id, 'Subdivision_ID');
            $form_action = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH .
                "add.php?sub=$requests_subdivision_id&cc=$request_infoblock_id" .
                "&nc_no_form_modification=1";
            $button_overlay = $show_overlay ? $this->get_button_modal_trigger('OpenPopupButton', $button_id) : '';
        }
        else {
            $form_action = nc_infoblock_path($request_infoblock_id, 'add');
            $button_overlay = '';
        }

        // Регистрация использования стилей из SiteStyles.css
        $nc_core->page->register_component_usage($this->get_request_component_template_id());
        $jquery_variable = $nc_core->admin_mode ? '$nc' : '$';

        return $this->get_form_script() .
               "<script>$jquery_variable(function(){nc_requests_form_popup_init(" .
                     "'$nc_component_css_class', '#$button_id', '$form_action', " .
                     nc_array_json($hidden_inputs) . ", " .
                     nc_array_json($button_overlay) .
                ")});</script>\n";
    }

    /**
     * В режиме редактирования возвращает поле для in-place-редактирования свойства
     * формы в инфоблоке, а в режиме просмотра — значение этого свойства
     * (аналог nc_edit_inline для свойств формы).
     * Функция автоматически выбирает настройку StandaloneForm_$setting для форме
     * в попапе (или в инфоблоке компонента заявок с действием «добавление») или
     * EmbeddedForm_$setting для формы, встроенной в другой инфоблок.
     *
     * @param string $setting  Название параметра без префикса типа настройки, например:
     *    'Header', 'TextAfterHeader'
     * @return string
     */
    public function get_setting_inline_editor($setting) {
        $setting = ($this->inside_get_form ? 'EmbeddedForm_' : 'StandaloneForm_') . $setting;

        $value = $this->get_setting($setting);
        if (nc_core::get_object()->admin_mode) {
            if (!class_exists('CKEditor')) { // arrrgh
                include_once(nc_core::get_object()->ROOT_FOLDER . 'editors/ckeditor4/ckeditor.php');
            }

            $title = nc_array_value(self::$settings_caption, $setting, '');
            $save_url = $this->get_controller_url('save_setting');

            $CKEditor = new CKEditor();
            return $CKEditor->getInlineScript($title, $value, $save_url, array('setting' => $setting), 'value', CKEditor::NO_TOOLBAR | CKEditor::SINGLE_LINE);
        }
        else {
            return $value;
        }
    }

    /**
     * Проверяет, есть ли в форме поля указанного типа
     * @param int $field_type  Константа NC_FIELDTYPE_*
     * @return bool
     */
    protected function has_fields_of_type($field_type) {
        $field_types = $this->get_module_object()->get_request_component_visible_fields('type');
        $form_field_names = $this->get_visible_fields();
        foreach ($form_field_names as $field_name) {
            if ($field_types[$field_name] == $field_type) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверяет, есть ли в форме
     * @return bool
     */
    public function has_text_fields() {
        return $this->has_fields_of_type(NC_FIELDTYPE_TEXT);
    }


}
