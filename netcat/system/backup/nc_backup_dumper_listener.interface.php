<?php

interface nc_backup_dumper_listener {
    public function call_event($event, $args);
}