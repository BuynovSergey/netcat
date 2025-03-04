<?php

class nc_tpl_exception_include extends Exception {

    public function __construct($file_path, $code = 0, Throwable $previous = null) {
        parent::__construct("Unable to include '$file_path'", $code, $previous);
    }

}