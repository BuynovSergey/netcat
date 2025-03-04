<?php

/**
 *
 */
class nc_requests_backup extends nc_backup_extension {

    /**
     * @param string $type
     * @param int $id
     */
    public function export($type, $id) {
        if ($type !== 'site') {
            return;
        }

        $table = 'Requests_Form_SubdivisionSetting';
        $this->dumper->export_data(
            $table,
            $table . '_ID',
            nc_db_table::make($table)->where_in('Subdivision_ID', $this->dumper->get_dict('Subdivision_ID'))->get_result()
        );

        $table = 'Requests_Form_InfoblockSetting';
        $this->dumper->export_data(
            $table,
            $table . '_ID',
            nc_db_table::make($table)->where_in('Infoblock_ID', $this->dumper->get_dict('Sub_Class_ID'))->get_result()
        );
    }

    /**
     * @param string $type
     * @param int $id
     */
    public function import($type, $id) {
        if ($type !== 'site') {
            return;
        }

        $this->dumper->import_data('Requests_Form_SubdivisionSetting');
        $this->dumper->import_data('Requests_Form_InfoblockSetting', null, array('Infoblock_ID' => 'Sub_Class_ID'));
    }
}