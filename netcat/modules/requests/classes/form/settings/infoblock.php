<?php

/**
 * Параметры формы в инфоблоке
 */
class nc_requests_form_settings_infoblock extends nc_record {

    protected $table_name = "Requests_Form_InfoblockSetting";
    protected $primary_key = "Requests_Form_InfoblockSetting_ID";
    protected $mapping = false;

    protected $properties = array(
        'Requests_Form_InfoblockSetting_ID' => null,
        'Infoblock_ID' => null,
        'FormType' => 'default',

        'OpenPopupButton_Text' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_DEFAULT_TEXT,
        'OpenPopupButton_BackgroundColor' => null,
        'OpenPopupButton_Type' => 'tpl-button tpl-button-primary',
        'OpenPopupButton_ShowPrice' => false,

        'EmbeddedForm_ComponentTemplate_ID' => 0,
        'EmbeddedForm_Header' => '',
        'EmbeddedForm_TextAfterHeader' => '',
        'EmbeddedForm_SubmitButton_Text' => NETCAT_MODULE_REQUESTS_FORM_BUTTON_DEFAULT_TEXT,
        'EmbeddedForm_SubmitButton_BackgroundColor' => null,
        'EmbeddedForm_SubmitButton_Type' => "tpl-button tpl-button-primary",
        'EmbeddedForm_SubmitButton_ShowPrice' => false,

        'Infoblock_OpenPopupButton_AnalyticsCategories' => '',
        'Infoblock_OpenPopupButton_AnalyticsLabels' => '',
        'Infoblock_SubmitButton_AnalyticsCategories' => '',
        'Infoblock_SubmitButton_AnalyticsLabels' => '',
    );

    /**
     * Загрузка настроек формы для инфоблока (или значения по умолчанию, если настроек нет)
     * @param $infoblock_id
     * @param $form_type
     * @return nc_requests_form_settings_infoblock
     */
    public static function for_infoblock($infoblock_id, $form_type) {
        $infoblock_id = (int)$infoblock_id;

        $result = new self(array('Infoblock_ID' => $infoblock_id, 'FormType' => $form_type));

        $form_type = nc_db()->escape($form_type);
        $result->select_from_database("SELECT * FROM `%t%` WHERE `Infoblock_ID` = $infoblock_id AND `FormType` = '$form_type'");

        return $result;
    }

    /**
     * Делает копию настроек для всех типов форм
     * @param $source_infoblock_id
     * @param $target_infoblock_id
     */
    public static function duplicate_settings_for_all_form_types($source_infoblock_id, $target_infoblock_id) {
        $settings = self::get_all_form_types_settings_in_infoblock($source_infoblock_id);
        $settings->each('set_id', null);
        $settings->each('set', 'Infoblock_ID', $target_infoblock_id);
        $settings->each('save');
    }

    /**
     * Возвращает коллекцию с настройками форм всех типов для указанного инфоблока,
     * проиндексированную по свойству FormType
     * @param $infoblock_id
     * @return nc_record_collection
     * @throws nc_record_exception
     */
    public static function get_all_form_types_settings_in_infoblock($infoblock_id) {
        $infoblock_id = (int)$infoblock_id;

        $collection = new nc_record_collection();
        $collection->set_items_class(__CLASS__)->set_index_property('FormType');
        $collection->select_from_database("SELECT * FROM `%t%` WHERE `Infoblock_ID` = $infoblock_id");

        return $collection;
    }

    /**
     * Возвращает массив c настройками форм всех типов для инфоблока в виде массива
     * @param $infoblock_id
     * @return array
     */
    public static function get_all_form_types_settings_in_infoblock_as_array($infoblock_id) {
        return self::get_all_form_types_settings_in_infoblock($infoblock_id)->each('to_array');
    }

    /**
     * Удаляет настройки формы для указанного инфоблока
     * @param int $catalogue
     * @param int $sub
     * @param int|array $cc
     */
    public static function delete_infoblock_settings($catalogue, $sub, $cc) {
        if (!is_array($cc)) {
            $cc = array($cc);
        }

        foreach ($cc as $id) {
            self::get_all_form_types_settings_in_infoblock($id)->each('delete');
        }
    }

}
