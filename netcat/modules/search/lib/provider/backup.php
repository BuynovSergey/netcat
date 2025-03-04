<?php

abstract class nc_search_provider_backup extends nc_backup_extension {

    /**
     * @param $row
     * @param $field
     * @return string
     */
    public function map_ancestors($row, $field) {
        $ancestors = explode(',', $row[$field]);
        foreach ($ancestors as $k => $v) {
            $old_sub_id = str_replace('sub', '', $v);
            if (!is_numeric($old_sub_id)) { continue; }
            $ancestors[$k] = 'sub' . $this->dumper->get_dict('Subdivision_ID', $old_sub_id);
        }

        return join(',', $ancestors);
    }

}