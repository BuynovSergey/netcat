<?php

/**
 *
 */
class nc_tags_backup extends nc_backup_extension {

    /**
     * @param string $type
     * @param int $id
     */
    public function export($type, $id) {
        if ($type != 'site') { return; }

        // Tags_Data
        $infoblock_ids = join(", ", $this->dumper->get_dict('Sub_Class_ID'));
        if (!$infoblock_ids) { return; }

        $tags_data = nc_db()->get_results(
            "SELECT `t`.*
               FROM `Tags_Data` as `t`
                    LEFT JOIN `Tags_Message` AS `m` USING (`Tag_ID`)
              WHERE `m`.`Sub_Class_ID` IN ($infoblock_ids)",
            ARRAY_A,
            'Tag_ID'
        );
        if (!$tags_data) { return; }

        $this->dumper->export_data('Tags_Data', 'Tag_ID', $tags_data);

        // Tags_Weight
        $tag_ids = array_keys($tags_data);
        $tags_weight = nc_db_table::make('Tags_Weight')->where_in('Tag_ID', $tag_ids)->get_result();
        $this->dumper->export_data('Tags_Weight', null, $tags_weight);

        // Tags_Message
        $tags_message = nc_db_table::make('Tags_Message')->where_in('Sub_Class_ID', $infoblock_ids)->get_result();
        $this->dumper->export_data('Tags_Message', null, $tags_message);
    }

    /**
     * @param string $type
     * @param int $id
     */
    public function import($type, $id) {
        if ($type != 'site') { return; }

        $this->dumper->import_data('Tags_Data');
        $this->dumper->import_data('Tags_Weight');

        $map_message_id = array('Message_ID' => array($this, 'map_message_id_by_subclass_id'));
        $this->dumper->import_data('Tags_Message', null, $map_message_id);
    }

}