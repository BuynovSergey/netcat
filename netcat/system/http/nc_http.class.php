<?php

class nc_http {

    const HTTP_GET = "GET";
    const HTTP_POST = "POST";
    const DEFAULT_REQUEST_TIMEOUT = 60;
    const DEFAULT_USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)
     Chrome/91.0.4472.77 Safari/537.36";

    const HTTP_STATUS_OK = 200;
    const HTTP_STATUS_CREATED = 201;
    const HTTP_STATUS_ACCEPTED = 202;
    const HTTP_STATUS_NO_CONTENT = 204;
    const HTTP_STATUS_BAD_REQUEST = 400;
    const HTTP_STATUS_UNAUTHORIZED = 401;
    const HTTP_STATUS_FORBIDDEN = 403;
    const HTTP_STATUS_NOT_FOUND = 404;
    const HTTP_STATUS_METHOD_NOT_ALLOWED = 405;
    const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;
    const HTTP_STATUS_NOT_IMPLEMENTED = 501;
    const HTTP_STATUS_BAD_GATEWAY = 502;

    private static $EXCEPTION_HTTP_CODES = array(
        self::HTTP_STATUS_BAD_REQUEST,
        self::HTTP_STATUS_UNAUTHORIZED,
        self::HTTP_STATUS_FORBIDDEN,
        self::HTTP_STATUS_NOT_FOUND,
        self::HTTP_STATUS_METHOD_NOT_ALLOWED,
        self::HTTP_STATUS_INTERNAL_SERVER_ERROR,
        self::HTTP_STATUS_NOT_IMPLEMENTED,
        self::HTTP_STATUS_BAD_GATEWAY,
    );


    private $base_url;
    private $headers;
    private $request_timeout;
    private $current_url;


    /**
     * @param string|null $base_url
     * @param array $headers
     * @param int $request_timeout
     */
    public function __construct($base_url = null, array $headers = array(),
        $request_timeout = nc_http::DEFAULT_REQUEST_TIMEOUT) {

        $this->base_url = $base_url;
        $this->headers = $headers;
        $this->request_timeout = $request_timeout;
    }

    /**
     * @return string
     */
    public function get_base_url() {
        return $this->base_url;
    }

    /**
     * @return string
     */
    public function get_current_url() {
        return $this->current_url;
    }

    /**
     * @param string $base_url
     */
    public function set_base_url($base_url) {
        $this->base_url = $base_url;
    }

    /**
     * @return array
     */
    public function get_headers() {
        return $this->convert_headers($this->headers);
    }

    /**
     * @param array $headers
     */
    public function set_headers(array $headers) {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * @return int
     */
    public function get_request_timeout() {
        return $this->request_timeout;
    }

    /**
     * @param int $request_timeout
     */
    public function set_request_timeout($request_timeout) {
        $this->request_timeout = $request_timeout;
    }

    /**
     * @param string $url
     * @param array|null $params
     * @param array|null $headers
     *
     * @return array|string
     * @throws nc_http_exception
     */
    public function make_get_request($url, array $params = null, array $headers = null) {
        return $this->make_http_request($url, $params, $headers);
    }

    /**
     * @param string $url
     * @param array|null $params
     * @param array|null $headers
     *
     * @return array|string
     * @throws nc_http_exception
     */
    public function make_post_request($url, array $params = null, array $headers = null) {
        return $this->make_http_request($url, $params, $headers, nc_http::HTTP_POST);
    }

    /**
     * @param string $url
     * @param array|null $params
     * @param array|null $headers
     * @param $method
     *
     * @return array|string
     * @throws nc_http_exception
     */
    public function make_http_request($url, array $params = null, array $headers = null, $method = nc_http::HTTP_GET) {
        $headers = $this->configure_headers($method, $params, $headers);

        return $this->fetch_with_curl($this->configure_url($url), $method, $params, $headers);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     *
     * @return array|string
     * @throws nc_http_exception
     */
    private function fetch_with_curl($url, $method, array $params = null, array $headers = null) {
        $this->current_url = $url;

        $curl = curl_init();
        $curl_parameters = array(
            CURLOPT_URL => $url,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->request_timeout,
            CURLOPT_CONNECTTIMEOUT => $this->request_timeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->convert_headers($headers),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        );


        if (ini_get("open_basedir") == "") {
            $curl_parameters[CURLOPT_FOLLOWLOCATION] = true;
        }

        curl_setopt_array($curl, $curl_parameters);

        if ($method == nc_http::HTTP_POST) {
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($params) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
            }
        }

        if ($method == nc_http::HTTP_GET && $params) {
            $query_string = "?" . http_build_query($params, "", "&");
            $url = $url . $query_string;
            $this->current_url = $url;

            curl_setopt($curl, CURLOPT_URL, $url);
        }

        $response = curl_exec($curl);

        if ($response === false) {
            curl_close($curl);
            throw new nc_http_exception();
        }

        $result = $this->is_json($response) ? json_decode($response, true) : $response;
        $info = curl_getinfo($curl);

        if (isset($info["http_code"]) && in_array($info["http_code"], self::$EXCEPTION_HTTP_CODES)) {
            curl_close($curl);

            throw new nc_http_exception(nc_http_exception::DEFAULT_ERROR_MESSAGE, $info["http_code"],
                is_array($result) ? $result : array());
        }

        curl_close($curl);

        return $result;
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws nc_http_exception
     */
    private function configure_url($url) {
        $url = $this->base_url ? $this->base_url . $url : $url;
        $this->validate_url($url);

        return $url;
    }

    /**
     * @throws nc_http_exception
     */
    private function validate_url($url) {
        if (!$url || !is_string($url) ||
            !preg_match('/^http(s)?:\/\/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?$/i', $url)
        ) {
            throw new nc_http_exception("Invalid url passed: '$url'");
        }
    }

    /**
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     *
     * @return array
     */
    private function configure_headers($method, array $params = null, array $headers = null) {
        if ($headers == null) {
            $headers = array();
        }

        if ($method == self::HTTP_POST && $params) {
            $this->set_content_length_header($params);
        }

        if ($params) {
            $this->set_content_type_header();
        }

        if (!nc_array_value($headers, "User-Agent")) {
            $this->set_user_agent_header();
        }

        $this->headers = array_merge($this->headers, $headers);

        return $this->headers;
    }

    /**
     * @param string $response
     *
     * @return bool
     */
    private function is_json($response) {
        json_decode($response);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    private function convert_headers(array $headers) {
        $result = array();

        foreach ($headers as $k => $v) {
            $result[] = sprintf("%s: %s", $k, $v);
        }

        return $result;
    }

    /**
     * @param string|null $value
     *
     * @return void
     */
    private function set_content_type_header($value = "application/json") {
        $this->headers["Content-Type"] = $value;
    }

    /**
     * @param array $params
     *
     * @return void
     */
    private function set_content_length_header(array $params) {
        $this->headers["Content-Length"] = strlen(json_encode($params));
    }

    /**
     * @return void
     */
    private function set_user_agent_header() {
        $this->headers["User-Agent"] = nc_array_value($_SERVER, "HTTP_USER_AGENT", nc_http::DEFAULT_USER_AGENT);
    }

}
