<?php

class nc_version_record_collection extends nc_record_collection {

    static protected $table_name = 'Version';
    protected $items_class = 'nc_version_record';

    /**
     * @param array $row
     * @return string
     */
    protected function get_item_class_for_database_row(array $row) {
        return 'nc_version_record_' . $row['Entity'];
    }

    protected function for_each_while_true($method) {
        /** @var nc_version_record $item */
        if (!$this->count()) {
            return false;
        }

        foreach ($this->items as $item) {
            if (!$item->$method()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function restore() {
        return $this->for_each_while_true('restore');
    }

    /**
     * @return bool
     */
    public function undo() {
        return $this->for_each_while_true('undo');
    }

}