<?php

class nc_landing_preset_netcat_landing_goods_2_block_properties extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_table';
    protected $default_component_template = 'table_with_image';
    protected $default_infoblock_keyword = 'specs';
    protected $default_infoblock_name = 'Характеристики';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = array('text', 'product_image');


    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        $objects = array();
        $item = $landing_data['item'];

        if ($item instanceof nc_netshop_item) {
            $component = nc_core::get_object()->get_component($item['Class_ID']);

            $property_fields = $component->get_fields_by_name_prefix("Property_", null, true);
            foreach ($property_fields as $field) {
                $value = $item[$field['name']];
                if (is_array($value)) {
                    $value = join(', ', $value);
                }
                if ($field['type'] == NC_FIELDTYPE_BOOLEAN) {
                    $value = $value ? 'да' : 'нет';
                }

                $objects[] = array(
                    'Name' => $field['description'],
                    'Value' => $value,
                );
            }
        }
        else {
            $objects = array(
                array('Name' => 'Аккумулятор', 'Value' => '4 000 мА·ч'),
                array('Name' => 'Дисплей', 'Value' => '5,5″'),
                array('Name' => 'Процессор', 'Value' => '1,7 ГГц, 8-ядерный'),
                array('Name' => 'Оперативная память', 'Value' => '2 Гб'),
                array('Name' => 'Количество SIM-карт', 'Value' => '2'),
            );
        }

        return $objects;
    }
    

    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $text = 'Техническое описание ' . $item['Name'];
            $image = $item['BigImage'] ?: $item['Image'];
        }
        else {
            $text = 'Техническое описание NetPhone';
            $image = $this->get_image_path('phone1.png');
        }

        return array(
            'show_header' => 1,
            'header' => 'Характеристики',
            'show_text' => 1,
            'text' => $text,
            'product_image' => array(
                'file' => $image,
            ),
        );
    }
    
}