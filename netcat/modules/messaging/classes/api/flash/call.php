<?php


interface nc_messaging_api_flash_call {


    /**
     * @param string $phone
     * @param string $confirmation_code
     *
     * @return array
     * @throws nc_messaging_api_exception
     * @throws nc_validator_exception
     */
    public function push_flash_call($phone, $confirmation_code);

}
