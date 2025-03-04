<?php

/**
 * @method self contains_infoblock_created_from($source_keyword)
 */

class nc_content_changer_subdivision_finder extends nc_content_changer_finder {

    const TABLE_NAME = 'Subdivision';
    const TABLE_PRIMARY_KEY = 'Subdivision_ID';

    protected function apply_contains_infoblock_created_from(nc_db_table $table, $source_keyword) {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        $site_id = $nc_core->catalogue->id();

        $subdivision_ids = $db->get_col(
            "SELECT DISTINCT `Subdivision_ID` 
               FROM `Sub_Class`
              WHERE `Catalogue_ID` = $site_id
                AND `Source_Keyword` = '{$db->escape($source_keyword)}'
                AND `Subdivision_ID` > 0"
        );

        $table->where_in_id($subdivision_ids ?: array(0));
    }

}