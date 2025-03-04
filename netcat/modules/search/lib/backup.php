<?php

/**
 *
 */
class nc_search_backup extends nc_backup_extension {

    /**
     * @param string $type
     * @param int $id
     */
    public function export($type, $id) {
        if ($type != 'site') { return; }

        $this->dumper->set_dump_info('search_provider', nc_search::get_setting('SearchProvider'));

        // Skipped:
        // - Search_BrokenLink
        // - Search_Extension
        // - Search_Log
        // - Search_Query
        // - Search_Stopword (TODO?)
        // - Search_Synonym  (TODO?)

        // Service tables used when indexing (no need to export):
        // Search_Link, Search_LinkReferrer, Search_Task, (Search_Schedule)

        // Search_Document, индексы: todo?
        // (в значительном числе случаев корректный импорт крайне затруднителен)
        // См. nc_search_provider_zend_backup, nc_search_provider_index_backup

        // Search_Rules
        $rules = nc_db_table::make('Search_Rule')->where('Catalogue_ID', $id)->get_result();
        $this->dumper->export_data('Search_Rule', 'Rule_ID', $rules);

        // Search_Field
        $fields = nc_db_table::make('Search_Field')->get_result();
        $this->dumper->export_data('Search_Field', 'Name', $fields);
    }

    /**
     * @param string $type
     * @param int $id
     */
    public function import($type, $id) {
        if ($type != 'site') { return; }

        $this->dumper->import_data('Search_Rule', null, array('AreaString' => array($this, 'map_area_string')));

        // NB: there is an 'event_before_insert_search_field()' method:
        $this->dumper->import_data('Search_Field');

        // schedule indexing
        $this->schedule_indexing($this->dumper->get_dict('Rule_ID'));
    }

    /**
     * @param array $rule_ids
     */
    protected function schedule_indexing($rule_ids) {
        if (!$rule_ids) { return; }
        foreach ($rule_ids as $rule_id) {
            $rule = new nc_search_rule($rule_id);
            $rule->schedule_next_run();
        }
    }

    /**
     * @param array $row
     * @return bool
     */
    protected function event_before_insert_search_field($row) {
        $field_exists = nc_db_table::make('Search_Field')->where('Name', $row['Name'])->count_all();
        if ($field_exists) { return false; }
        return $row;
    }

    /**
     * @param $row
     * @param $field
     * @return string
     */
    protected function map_area_string($row, $field) {
        $value = $row[$field];
        if (!$value) { return $value; }

        // Area string may contain IDs of sites and sections: http://netcat.ru/developers/docs/module-search/indexing/
        $old_site_id = $row['Catalogue_ID'];
        $value = preg_replace("/site{$old_site_id}\b/",
                              "site" . $this->dumper->get_dict('Catalogue_ID', $old_site_id),
                              $value);

        preg_match_all("/sub(\d+)/", $value, $sub_matches);
        if (isset($sub_matches[1])) {
            foreach ($sub_matches[1] as $old_sub_id) {
                $value = preg_replace("/sub{$old_sub_id}\b/",
                                      "sub" . $this->dumper->get_dict('Subdivision_ID', $old_sub_id),
                                      $value);
            }
        }

        return $value;
    }

}