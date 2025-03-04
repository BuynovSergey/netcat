<?php

class nc_landing_preset_netcat_landing_goods_3_block_offer extends nc_landing_preset_block {

    protected $component = 'netcat_page_block_countdown';
    protected $default_component_template = 'plain';
    protected $default_infoblock_keyword = 'offer';
    protected $default_infoblock_name = 'Акция';

    protected $ignore_user_objects = true;
    protected $ignore_user_infoblock_settings = array('text');


    protected function get_offer_end_timestamp() {
        $end = mktime(12, 0, 0, 10, 21, 2016); // October 21st, 2016
        $now = time();
        if ($end > $now) {
            return $end;
        }
        else {
            return $now + 7 * 24 * 60 * 60;
        }
    }

    protected function format_date($timestamp) {
        $months = explode(' ', ' января февраля марта апреля мая июня июля августа сентября октября ноября декабря');
        $time = getdate($timestamp);
        return $time['mday'] . ' ' . $months[$time['mon']] . ' ' . $time['year'] . ' года';
    }

    protected function get_default_infoblock_settings($subdivision_id, $settings, array $landing_data) {
        $item = $landing_data['item'];

        if ($item) {
            $header = "Сделайте заказ до " . $this->format_date($this->get_offer_end_timestamp());
            if ($item['ItemDiscount']) {
                $header .= "<br>и сэкономьте $item[ItemDiscount] $landing_data[DiscountCurrency]!";
            }
        }
        else {
            $header = "Сделайте предзаказ до " . $this->format_date($this->get_offer_end_timestamp()) .
                      "<br>и сэкономьте 5500 рублей!";
        }

        return array(
            'show_header' => 1,
            'header' => $header,
            'show_text' => 0,
            'text' => "До окончания акции осталось",
            'padding_top' => 75,
            'padding_bottom' => 60,
        );
    }

    protected function get_objects_properties($infoblock_id, $settings, array $landing_data) {
        return array(
            array(
                'Deadline' => strftime("%Y-%m-%d 00:00:00", $this->get_offer_end_timestamp()),
                'EndText' => 'Сожалеем, акция завершилась',
            )
        );
    }
    
}
