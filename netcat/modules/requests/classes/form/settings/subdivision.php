<?php

/**
 * Параметры формы в разделе
 */
class nc_requests_form_settings_subdivision extends nc_record {

    static $cache = array();

    protected $table_name = "Requests_Form_SubdivisionSetting";
    protected $primary_key = "Requests_Form_SubdivisionSetting_ID";
    protected $mapping = false;

    protected $properties = array(
        'Requests_Form_SubdivisionSetting_ID' => null,
        'Catalogue_ID' => null,
        'Subdivision_ID' => null,
        'FormType' => 'default',
        
        'StandaloneForm_ComponentTemplate_ID' => 0,
        'StandaloneForm_Header' => '',
        'StandaloneForm_TextAfterHeader' => '',
        'StandaloneForm_SubmitButton_Text' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_DEFAULT_TEXT,
        'StandaloneForm_SubmitButton_BackgroundColor' => null,
        'StandaloneForm_SubmitButton_ShowPrice' => false,

        'Subdivision_VisibleFields' => array(),
        'Subdivision_FieldProperties' => array(),
        'Subdivision_NotificationEmail' => '',

        'Subdivision_OpenPopupButton_AnalyticsCategories' => '',
        'Subdivision_OpenPopupButton_AnalyticsLabels' => '',
        'Subdivision_SubmitButton_AnalyticsCategories' => '',
        'Subdivision_SubmitButton_AnalyticsLabels' => '',
    );

    protected $serialized_properties = array('Subdivision_VisibleFields', 'Subdivision_FieldProperties');
    protected $custom_settings_form;

    /**
     * Загрузка настроек формы для раздела (или значения по умолчанию, если настроек нет)
     * @param int $subdivision_id
     * @param string $form_type
     * @param int $site_id
     * @return nc_requests_form_settings_subdivision
     */
    public static function for_subdivision($subdivision_id, $form_type, $site_id) {
        $site_id = (int)$site_id;
        $subdivision_id = (int)$subdivision_id;

        if (!isset(self::$cache[$subdivision_id][$form_type])) {
            $result = new self(array('Catalogue_ID' => $site_id, 'Subdivision_ID' => $subdivision_id, 'FormType' => $form_type));

            $form_type = nc_db()->escape($form_type);

            if ($subdivision_id) {
                $loaded = $result->select_from_database("SELECT * FROM `%t%` WHERE `Subdivision_ID` = $subdivision_id AND `FormType` = '$form_type'");
            } else {
                $loaded = $result->select_from_database("SELECT * FROM `%t%` WHERE (`Catalogue_ID` = $site_id AND `Subdivision_ID` = 0) AND `FormType` = '$form_type'");
            }

            if (!$loaded) {
                // Записи в БД нет. Для настроек по умолчанию записываем в Fields все
                // отображаемые поля компонента заявок.
                $nc_core = nc_core::get_object();

                // У областей subdivision_id будет не определен
                if ($subdivision_id) {
                    try {
                        $site_id = $nc_core->subdivision->get_by_id($subdivision_id, 'Catalogue_ID');
                        $requests = nc_requests::get_instance($site_id);
                    } catch (Exception $e) {
                        $requests = nc_requests::get_instance();
                    }
                } else {
                    $requests = nc_requests::get_instance();
                }

                $field_names = array_values($requests->get_request_component_default_fields('name'));
                $result->set('Subdivision_VisibleFields', $field_names);
            }

            self::$cache[$subdivision_id][$form_type] = $result;
        }

        return self::$cache[$subdivision_id][$form_type];
    }

    /**
     * Делает копию настроек для всех типов форм
     * @param $source_subdivision_id
     * @param $target_subdivision_id
     * @return bool|int
     */
    public static function duplicate_settings_for_all_form_types($source_subdivision_id, $target_subdivision_id) {
        $settings = self::get_all_form_types_settings_in_subdivision($source_subdivision_id);
        $settings->each('set_id', null);
        $settings->each('set', 'Subdivision_ID', $target_subdivision_id);
        $settings->each('save');
    }

    /**
     * Возвращает коллекцию с настройками форм всех типов для указанного раздела,
     * проиндексированную по свойству FormType
     * @param $subdivision_id
     * @return nc_record_collection
     * @throws nc_record_exception
     */
    public static function get_all_form_types_settings_in_subdivision($subdivision_id) {
        $subdivision_id = (int)$subdivision_id;

        $collection = new nc_record_collection();
        $collection->set_items_class(__CLASS__)->set_index_property('FormType');
        $collection->select_from_database("SELECT * FROM `%t%` WHERE `Subdivision_ID` = $subdivision_id");

        return $collection;
    }

    /**
     * Возвращает массив c настройками форм всех типов для раздела в виде массива
     * @param $subdivision_id
     * @return array
     */
    public static function get_all_form_types_settings_in_subdivision_as_array($subdivision_id) {
        return self::get_all_form_types_settings_in_subdivision($subdivision_id)->each('to_array');
    }

    /**
     * Удаляет настройки формы для указанного раздела
     * @param int $catalogue
     * @param int|array $sub
     */
    public static function delete_subdivision_settings($catalogue, $sub) {
        if (!is_array($sub)) {
            $sub = array($sub);
        }

        foreach ($sub as $id) {
            self::get_all_form_types_settings_in_subdivision($id)->each('delete');
        }
    }

}