<?php

class nc_landing_preset_netcat_landing_goods_3_block_properties extends nc_landing_preset_block {

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
                array('Name' => 'Размеры', 'Value' => '41–47'),
                array('Name' => 'Цвет', 'Value' => 'Белый, серый'),
                array('Name' => 'Состав', 'Value' => 'Синтетика, текстиль, резина'),
                array('Name' => 'Год выпуска', 'Value' => '2016'),
            );
        }

        return $objects;
    }
    

    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $header = 'Характеристики ' . $item['Name'];
            $text = $item['Description'];
            $image = $item['BigImage'] ?: $item['Image'];
        }
        else {
            $header = 'Характеристики Nike Air Mag 2016';
            $text = 'В комплекте оригинальная коробка «ящик» и зарядное устройство. 
                Значок Nike и подушка в пятке светятся при активации включателем.';
            $image = $this->get_image_path('image3.png');
        }

        return array(
            'show_header' => 1,
            'header' => '<span style="font-size: 36px;">' . $header . '</span>',
            'show_text' => 1,
            'text' => $text,
            'product_image' => array(
                'file' => $image,
            ),
            'padding_top' => 90,
            'padding_bottom' => 90,
        );
    }
    
}