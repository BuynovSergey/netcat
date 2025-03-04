<?php

/**
 *
 */
class nc_subscriber_backup extends nc_backup_extension {

    /**
     * @param string $type
     * @param int $id
     */
    public function export($type, $id) {
        if ($type != 'site') { return; }

        $infoblock_ids = $this->dumper->get_dict('Sub_Class_ID');

        // Subscribe_Mailer
        $subscriber_mailer = nc_db_table::make('Subscriber_Mailer')->where_in('Sub_Class_ID', $infoblock_ids)->where('Sub_Class_ID', '<>', 0)->get_result();
        $this->dumper->export_data('Subscriber_Mailer', 'Mailer_ID', $subscriber_mailer);
    }

    /**
     * @param string $type
     * @param int $id
     */
    public function import($type, $id) {
        if ($type != 'site') { return; }

        $this->dumper->import_data('Subscriber_Mailer', null);
    }
}