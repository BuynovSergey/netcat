<?php

/**
 * Слушатель для HTTP_Client. Останавливает запрос, если документ не подходит
 * по размеру или типу
 */
class nc_search_indexer_crawler_listener extends HTTP_Request_Listener {

    protected $max_doc_size;
    protected $accepted_types;

    public function __construct($max_doc_size, array $accepted_types) {
        $this->max_doc_size = (int)$max_doc_size;
        $this->accepted_types = $accepted_types;
    }

    public function update(&$http_response, $event, $headers = null) {
        if ($event == 'gotHeaders') {
            $cancel = false;
            if ($this->max_doc_size && isset($headers["content-length"]) && $headers["content-length"] > $this->max_doc_size) {
                $cancel = true;
            } elseif (isset($headers["content-type"]) && $headers["content-type"]) {
                list($response_content_type) = explode(";", $headers["content-type"]);
                if (!in_array($response_content_type, $this->accepted_types)) {
                    $cancel = true;
                }
            }

            if ($cancel) {
                $response_array = array(
                        'code' => $http_response->_code, /* !!! ACCESSING PROTECTED PROPERTY !!! */
                        'headers' => $headers,
                        'is_cancelled' => true,
                );
                throw new nc_search_indexer_crawler_exception('Download stopped', $response_array);
            }
        }
    }

}
