<?php

/* $Id: nc_gzip.class.php 5960 2012-01-17 17:25:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Gzip extends nc_System {

    public function __construct() {
        // load parent constructor
        parent::__construct();
    }

    public function check() {
        // check "ob_gzhandler" existion
        $gzip_exist = false;
        if (ob_list_handlers ()) {
            $gzip_exist = in_array("ob_gzhandler", ob_list_handlers());
        }
        // if compression not enabled yet
        if (!$gzip_exist) {
            // get HTTP_ACCEPT_ENCODING string
            $encode_string = explode(",", $_SERVER['HTTP_ACCEPT_ENCODING']);
            $result = false;
            foreach ($encode_string as $value) {
                // parse value
                $value = trim($value);
                if ($value === "gzip" || $value === "x-gzip") {
                    $result = $value;
                    break;
                }
            }
        }

        return $result;
    }

}
?>