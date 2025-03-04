<?php

class nc_infoblock_filter_listener {

    public static function init() {
        nc_core::get_object()->event->add_listener(nc_event::AFTER_INFOBLOCK_DELETED, array(new self, 'infoblock_delete'));
    }

    public function infoblock_delete($site_id, $subdivision_id, $infoblock_ids) {
        $db = nc_db();
        foreach ((array)$infoblock_ids as $infoblock_id) {
            $infoblock_id = (int)$infoblock_id;
            $filtered_infoblock_id = $db->get_var("SELECT `Data_Sub_Class_ID` FROM `Filter` WHERE `Filter_Sub_Class_ID` = $infoblock_id");

            $db->query("DELETE FROM `Filter` WHERE `Filter_Sub_Class_ID` = $infoblock_id OR `Data_Sub_Class_ID` = $infoblock_id");

            if ($filtered_infoblock_id) { // удалён инфоблок с фильтром; обновляем HasFilter блока, к которому был применён фильтр
                nc_infoblock_filter::update_filtered_infoblock($filtered_infoblock_id);
            }
        }
    }

}