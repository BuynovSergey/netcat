<?php

/* $Id: openstat_core_class.php 4290 2011-02-23 15:32:35Z denis $ */

class nc_Openstat_core_class {

    public $base_addr;
    public $useragent;
    public $login;
    public $ignore_ssl_check;
    public $debug = 0;
    protected $password;
    protected $ch;

    /**
     * "Ядро" модуля интеграции с Openstat
     * @param string логин пользователя
     * @param string пароль пользователя
     * @return object
     */
    public function __construct($login, $password) {
        $this->useragent = CMS_SYSTEM_NAME_GLOBAL . ' bot';
        $this->base_addr = 'https://www.openstat.ru/rest/v0.3';
        $this->login = $login;
        $this->password = $password;
        $this->ignore_ssl_check = 1;
    }

    /**
     * Инициализация соединения, общая для всех методов
     */
    protected function init_curl() {
        if (function_exists('curl_init')) {
            $this->ch = curl_init();
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);  // возвращать результат работы
            curl_setopt($this->ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($this->ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
            curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
            if ($this->ignore_ssl_check) {
                curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
            }
            if ($this->debug) {
                curl_setopt($this->ch, CURLINFO_HEADER_OUT, 1);
                curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
            }
            curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->ch, CURLOPT_USERPWD, $this->login.':'.$this->password);

            return true;
        }

        return false;
    }

    /**
     * Отправить GET-запрос и получить ответ
     * @param string адрес (без учета базового адреса)
     * @param int 0 -конвертировать ответ из json в массив, в не конвертировать
     * @return mixed результат запроса (массив, возвращаемый curl_getinfo() + элемент 'content')
     */
    protected function get_data($url, $simple_responce=0) {

        if (!$this->init_curl()) {
            return null;
        }

        if (!$simple_responce) {
            if (nc_strpos($url, '?')) {
                $url .= '&format=json';
            } else {
                $url .= '?format=json';
            }
        }

        curl_setopt($this->ch, CURLOPT_URL, $this->base_addr.$url);

        // выполнить запрос
        curl_exec($this->ch);

        $result = curl_getinfo($this->ch);
        if ($simple_responce) {
            $result['content'] = curl_multi_getcontent($this->ch);
        } else {
            $result['content'] = json_decode(curl_multi_getcontent($this->ch), 1);
        }

        curl_close($this->ch);

        if ($this->debug) var_dump($result);

        return $result;
    }

    /**
     * Отправить POST-запрос и получить заголовки и ответ
     * @param string адрес (без учета базового адреса)
     * @param string данные для передачи через POST
     * @return mixed результат запроса (массив, возвращаемый curl_getinfo() + элемент 'content')
     */
    protected function post_data($url, $post) {

        if (!$this->init_curl()) {
            return null;
        }

        curl_setopt($this->ch, CURLOPT_HEADER, 1);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($this->ch, CURLOPT_URL, $this->base_addr.$url);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);

        // выполнить запрос
        curl_exec($this->ch);

        $result = curl_getinfo($this->ch);
        $result['content'] = curl_multi_getcontent($this->ch);

        curl_close($this->ch);

        if ($this->debug) var_dump($result);

        return $result;
    }

    /**
     * Отправить DELETE-запрос и получить заголовки ответа
     * @param string адрес (без учета базового адреса)
     * @return mixed результат запроса (массив, возвращаемый curl_getinfo())
     */
    protected function delete_data($url) {

        if (!$this->init_curl()) {
            return null;
        }

        curl_setopt($this->ch, CURLOPT_HEADER, 1);
        curl_setopt($this->ch, CURLOPT_URL, $this->base_addr.$url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE ');

        // выполнить запрос
        curl_exec($this->ch);

        $result = curl_getinfo($this->ch);

        curl_close($this->ch);

        if ($this->debug) var_dump($result);

        return $result;
    }

    /**
     * Отправить PUT-запрос и получить заголовки и ответ
     * @param string адрес (без учета базового адреса)
     * @param string данные для передачи через PUT
     * @return mixed результат запроса (массив, возвращаемый curl_getinfo() + элемент 'content')
     */
    protected function put_data($url, $put) {

        if (!$this->init_curl()) {
            return null;
        }

        $tmp_f = tmpfile();
        $len = fwrite($tmp_f, $put);
        fseek($tmp_f, 0);

        curl_setopt($this->ch, CURLOPT_HEADER, 1);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($this->ch, CURLOPT_URL, $this->base_addr.$url);
        curl_setopt($this->ch, CURLOPT_PUT, 1);
        curl_setopt($this->ch, CURLOPT_INFILE, $tmp_f);
        curl_setopt($this->ch, CURLOPT_INFILESIZE, $len);

        // выполнить запрос
        curl_exec($this->ch);

        fclose($tmp_f);

        $result = curl_getinfo($this->ch);
        $result['content'] = curl_multi_getcontent($this->ch);

        if ($this->debug) var_dump($result);

        curl_close($this->ch);

        return $result;
    }

    /**
     * Создать счетчик
     * @param mixed - Ассоциативный массив с элементами:
     * - site_url является обязательным
     * - title, description являются опциональными
     * - participates_in_rating, is_advert_publisher являются опциональными, и по умолчанию равны false
     * @param int HTTP-код ответа сервера (201 - успех)
     * @return int ID нового счетчика
     */
    public function make_counter($counter_params, &$http_code = 0) {

        $result_str = $this->post_data("/counters", json_encode($counter_params));
        $http_code = $result_str['http_code'];

        if ($http_code == 201) {
            // ищем Location в заголовках и выдергиваем ID счетчика
            if (nc_preg_match('#Location: .*/(\d+)/?\r#', $result_str['content'], $location)) {
                return $location[1];
            }
        }
    }

    /**
     * Получить список всех счетчиков
     * @param int HTTP-код ответа сервера (200 - успех)
     * @return mixed массив с параметрами счетчиков
     */
    public function get_all_counters_info(&$http_code = 0) {

        $result_str = $this->get_data("/counters");
        $http_code = $result_str['http_code'];

        if ($http_code == 200) {
            return $result_str['content'];
        }
    }

    /**
     * Получить параметры счетчика
     * @param int ID счетчика
     * @param int HTTP-код ответа сервера (200 - успех)
     * @return mixed массив с параметрами счетчика
     */
    public function get_counter_info($id, &$http_code = 0) {

        $result_str = $this->get_data("/counter/".$id);
        $http_code = $result_str['http_code'];

        if ($http_code == 200) {
            return $result_str['content'];
        }
    }

    /**
     * Изменить параметры счетчика
     * @param int ID счетчика
     * @param mixed - Ассоциативный массив с элементами:
     * - site_url является обязательным
     * - title, description являются опциональными
     * - participates_in_rating, is_advert_publisher являются опциональными, и по умолчанию равны false
     * @param int HTTP-код ответа сервера (204 - успех)
     * @return HTTP-код ответа сервера (204 - успех)
     */
    public function change_counter($id, $counter_params, &$http_code = 0) {

        $result_str = $this->put_data("/counter/".$id, json_encode($counter_params));
        $http_code = $result_str['http_code'];
        return $http_code;
    }

    /**
     * Удалить счетчик
     * @param int ID счетчика
     * @param int HTTP-код ответа сервера (204 - успех, 200 - уже был удален ранее)
     * @return int HTTP-код ответа сервера (204 - успех, 200 - уже был удален ранее)
     */
    public function delete_counter($id, &$http_code = 0) {

        $result_str = $this->delete_data("/counter/".$id);
        $http_code = $result_str['http_code'];
        return $http_code;
    }

    /**
     * Получить код счетчика
     * @param int ID счетчика
     * @param mixed параметры счетчика:
     * - color int - (optional) цвет
     * - picture int - (optional) Тип картинки счётчика. Имеет смысл только если color не равен 0
     * - track_links {none, all, ext} - (optional) флаг для посчёта уходов
     * @param int HTTP-код ответа сервера (200 - успех)
     * @return string код счетчика
     */
    public function get_counter_code($id, $params=NULL, &$http_code = 0) {

        $url = "/counter/".$id."/code";

        if ($params) {
            $params_str = http_build_query($params);
            $url = $url."?".$params_str;
        }

        $result_str = $this->get_data($url, 1);
        $http_code = $result_str['http_code'];

        if ($http_code == 200) {
            return $result_str['content'];
        }
    }

    /**
     * Получить код счетчика
     * @param int ID счетчика
     * @param string ID отчета
     * @param int дата начала отчета (UNIX TIME)
     * @param int дата окончания отчета (UNIX TIME)
     * @param string уровень детализации:
     * "month" - по месяцам;
     * "week" - по неделям;
     * "day" - по дням;
     * "hour" - по часам.
     * @param mixed массив имен столбцов вида: <segment>%0D<set_name>_<set_metrics_id>
     * @param string язык (2 буквы)
     * @param int HTTP-код ответа сервера (200 - успех)
     * @return mixed массив значений
     */
    public function get_counter_report($id, $report_id, $start_date, $end_date, $level_of_detailing, $columns, $limit=0, $lang="en", $params=NULL, &$http_code = 0) {

        $max_transfer_limit = 500;
        $offset = 0;
        $columns = (array) $columns;
        $items = array();

        if ($start_date > $end_date) {
            return FALSE;
        }
        switch ($level_of_detailing) {
            case "month" :
                $period = date("Ym", $start_date)."-".date("Ym", $end_date);
                break;
            case "week" :
                $period = date("Ymd", $start_date)."-".date("Ymd", $end_date);
                break;
            case "hour" :
                $period = date("YmdH", $start_date)."-".date("YmdH", $end_date);
                break;
            default :  // "day" is default
                $period = date("Ymd", $start_date)."-".date("Ymd", $end_date);
                break;
        }

        $columns_str = "column=".join("&column=", $columns);
        $url = "/".$id."/".strtolower($report_id)."/columns/".$period."?".$columns_str.
                "&locale=".$lang.($level_of_detailing == "week" ? "&week=1" : "").
                "&primary_column=0".($params ? "&".$params : "");

        do {
            $transfer_limit = ($limit && ($limit > $max_transfer_limit)) ? $max_transfer_limit : $limit;
            $limit = $limit - $transfer_limit;

            $result = $this->get_data($url."&limit=".$transfer_limit."&offset=".$offset);
            $http_code = $result['http_code'];

            if ($http_code == 200) {
                if (($result['content']['report']['item'][0]['v'] == "-") ||
                        ($result['content']['report']['item'][0]['v'] == "0")) {
                    $result['content']['report']['sum'] = $result['content']['report']['item'][0]['c'];
                    unset($result['content']['report']['item'][0]);
                }
                $items = array_merge($items, $result['content']['report']['item']);
            } else {
                return $http_code;
            }
            $offset += $transfer_limit;
        } while (($limit > 0) && ($result['content']['report']['pages_more'] != 0));

        $result['content']['report']['item'] = $items;
        //var_dump ($result); die;
        return $result['content']['report'];
    }

}