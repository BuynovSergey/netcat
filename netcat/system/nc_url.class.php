<?php

class nc_Url extends nc_System {

    private $url;
    private $parsed_url;
    private $remove_sub_folder = true;

    /**
     * Class constructor method
     */
    public function __construct($url = null, $remove_sub_folder = true) {
        // load parent constructor
        parent::__construct();

        if (is_string($url)) { // Передана строка
            $this->url = $url;
        }
        else { // Использовать текущий запрошенный URL
            if ($url) {
                trigger_error("nc_url: URL must be a string, " . gettype($url) . "provided. Using REQUEST_URI as an URL", E_USER_WARNING);
            }

            $nc_core = nc_Core::get_object();

            $request_uri = $nc_core->REQUEST_URI;

            $url = nc_get_scheme() .
                    "://" .
                    getenv("HTTP_HOST") .
                    $request_uri;

            // надо сохранить get-параметры из окружения
            if (($start = strpos(getenv("REQUEST_URI"), '?')) !== false) {
                $env_query_string = substr(getenv("REQUEST_URI"), $start + 1);
                $url_query_start = strpos($url, '?');
                if ($url_query_start !== false) {
                    $url = substr($url, 0, $url_query_start) . '?' . $env_query_string;
                }
                else {
                    $url .= '?' . $env_query_string;
                }
            }
            $this->url = $url;
        }

        $this->remove_sub_folder = $remove_sub_folder;
    }

    /**
     * Build url method
     *
     * @param array $query_arr query parameters
     * @return mixed result
     */
    public function build_url($query_arr) {
        // build query string
        if (!empty($query_arr)) {
            return http_build_query($query_arr, "", "&");
        }
        // no result
        return false;
    }

    /**
     * Parse REQUEST_URI method
     * and set internal variable $parsed_url
     *
     * @return array parsed url
     */

    public function parse_url() {
        if (is_array($this->parsed_url)) { return $this->parsed_url; }

        $nc_core = nc_core::get_object();

        // parse entire url
        $parsed_url = @parse_url($this->url);
        $query_string = (isset($parsed_url['query']) ? $parsed_url['query'] : null);

        // validate query parameter
        if ($query_string) {
            parse_str('&' . $query_string, $parsed_query_arr);
            // validate
            $parsed_query_arr = $nc_core->input->clear_system_vars($parsed_query_arr);
//            // in error_document $_GET is empty, so set them at this line
//            $_GET = $parsed_query_arr ? $parsed_query_arr : array();
            // build new query
            $parsed_url['query'] = $this->build_url($parsed_query_arr);
        }

        if ($this->remove_sub_folder && isset($parsed_url['path']) && $nc_core->SUB_FOLDER) {
            $parsed_url['path'] = nc_preg_replace(
                                      "#^(" . preg_quote($nc_core->SUB_FOLDER) . ")(.*?)$#is",
                                      "\$2",
                                      $parsed_url['path']
                                  );
        }

        $parsed_url['path'] = urldecode($parsed_url['path']);

        // for other methods
        $this->parsed_url = $parsed_url;

        // return array
        return $this->parsed_url;
    }

    /**
     * Get internal variable $_parsed_url method
     *
     * @param string|null $item
     * @return array|string|false parsed url
     */
    public function get_parsed_url($item = "") {
        $this->parse_url();

        if (empty($this->parsed_url)) {
            return false;
        }

        if ($item) {
            return array_key_exists($item, $this->parsed_url) ? $this->parsed_url[$item] : "";
        }

        return $this->parsed_url;
    }

    /**
     * Set URL part
     *
     * @param string $item
     * @param mixed $value - scalar
     * @return bool success
     */
    public function set_parsed_url_item($item, $value) {
        $this->parse_url();

        if (empty($this->parsed_url)) {
            return false;
        }

        $this->parsed_url[$item] = $value;

        return true;
    }

    /**
     * Get date from the URL
     *
     * @param bool $timestamp   return result as a timestamp
     * @return string|int|false  date string (YYYY-mm-dd) or a timestamp; false if no date in URL
     */
    public function get_uri_date($timestamp = false) {
        $date = '';
        // find date in url
        nc_preg_match('~ / ((?:19|20)\d{2}) /
                         (?: (\d{2}) / )?
                         (?: (\d{2}) / )?
                       ~x', $this->get_parsed_url('path'), $regs);

        // date found
        if ($regs) {
            array_shift($regs);
            $date = join("-", $regs);
        }

        // convert to the timestamp
        if ($timestamp) {
            $date = strtotime($date);
        }

        // return result date
        return $date ?: false;
    }

    /**
     * Get protocol, host and port number (without trailing '/'!)
     *
     * @return string
     */
    public function get_host_url() {
        return $this->parsed_url['scheme'] . '://' .
               (isset($this->parsed_url['user']) ? $this->parsed_url['user'] : '') .
               (isset($this->parsed_url['pass']) ? ":" . $this->parsed_url['pass'] : '') .
               ((isset($this->parsed_url['user']) || isset($this->parsed_url['pass'])) ? '@' : '') .
               $this->parsed_url['host'] .
               (isset($this->parsed_url['port']) ? ':' . $this->parsed_url['port'] : '');
    }

    /**
     * Get full URL string
     *
     * @return string source url
     */
    public function source_url() {
        // compile client source url
        return $this->get_host_url() .
               ($this->remove_sub_folder ? nc_core::get_object()->SUB_FOLDER : '') .
               $this->parsed_url['path'];
    }


    /**
     * Возвращает полный путь к странице (с доменом, подпапкой, путём на сервере,
     * query- и fragment-частями)
     *
     * @return string
     */
    public function get_full_url() {
        return $this->get_host_url() . $this->get_local_url();
    }

    /**
     * Возвращает локальный путь (без протокола и домена) с подпапкой (SUB_FOLDER),
     * путём страницы, query- и fragment-частями
     *
     * @return string
     */
    public function get_local_url() {
        return nc_core::get_object()->SUB_FOLDER .
               $this->get_parsed_url('path') .
               ($this->get_parsed_url('query') ? '?' . $this->get_parsed_url('query') : '') .
               ($this->get_parsed_url('fragment') ? '#' . $this->get_parsed_url('fragment') : '');
    }

}
