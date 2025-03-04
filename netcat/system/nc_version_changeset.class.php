<?php

class nc_version_changeset extends nc_record {

    protected $mapping = false;
    protected $primary_key = 'Version_Changeset_ID';
    protected $table_name = 'Version_Changeset';

    protected $properties = array(
        'Version_Changeset_ID' => null,
        'ChangeCount' => null,
        'Description' => null,
    );

    public function get_records() {
        return nc_version_record_collection::load_records(
            "SELECT * FROM `%t%` WHERE `Version_Changeset_ID` = " . (int)$this->get_id() .
            " ORDER BY `Version_ID` DESC"
        );
    }

    public function restore() {
        return $this->get_records()->restore();
    }

    public function undo() {
        return $this->get_records()->undo();
    }

}
