<?php

class nc_condition_infoblock_admin_helpers extends nc_condition_admin_helpers {

    /**
     * Возвращает HTML-фрагмент для вставки скриптов редактора условий
     * @return string
     */
    public static function get_condition_editor_js() {
        return
            parent::get_condition_editor_js() .
            self::get_script_tag(nc_core::get_object()->ADMIN_PATH . 'condition/js/editor_infoblock_query.js');
    }

}
