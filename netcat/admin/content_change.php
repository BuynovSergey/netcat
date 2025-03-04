<?php

require_once "../connect_io.php";
require_once "../admin/lang/Russian_utf8.php";

/** @var nc_core $nc_core */
$nc_core->modules->load_env();

if (!defined("NC_INSTRUCTION_URL")) {
    exit();
}

$http = new nc_http();

try {
    $response = $http->make_get_request(
        NC_INSTRUCTION_URL,
        array(
            "client_id" => $nc_core->get_copy_id(),
        )
    );

    if (!empty($response["data"]["instructions"])) {
        $instructions = array_map(
            function ($instruction) {
                return unserialize($instruction);
            },
            $response["data"]["instructions"]
        );

        $runner = new nc_content_changer_runner($instructions);
        $runner->run();

        nc_search::process_site_indexing(nc_core::get_object()->catalogue->id());
    }

    header("Content-Type: application/json; encoding=utf8;");

    echo json_encode(array("status" => "success"));
} catch (Error $e) {
    echo json_encode(
        array(
            "status" => "error",
            "message" => $e->getMessage(),
        )
    );
}
