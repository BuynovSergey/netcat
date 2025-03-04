<?php

class nc_Page extends nc_tpl_asset_collector {

    protected $core;
    protected $metatags = array(); // title, keywords, description, smo_title, smo_description, smo_image
    // поля из таблицы Разделы, используемые для метаданных
    protected $title_field, $keywords_field, $description_field;
    protected $smo_title_field, $smo_description_field, $smo_image_field;
    protected $language_field;
    protected $field_usage = array();
    protected $h1 = null;
    protected $canonical_link;
    /** @var  array|false */
    protected $routing_result;
    /** @var  nc_url */
    protected $url;
    /** @var array  */
    protected $component_template_usage = array();

    /** @var int последнее время изменения контента в основной части страницы */
    protected $last_modified_content = 0;
    /** @var int последнее время изменения шаблонов, макетов и контента, который выводит макет */
    protected $last_modified_template = 0;
    /** @var bool в настоящее время обрабатывается макет? (учитывается при расчёте значения для Last-Modified) */
    protected $is_processing_template_now = false;
    /** @var bool подключать стили сайта с ID 0 (используются для блоков страниц полного вывода) */
    protected $include_custom_full_page_mixin_styles = false;

    /** @var nc_page_asset_request_collection  */
    protected $requested_assets;
    /** @var array */
    protected $requested_assets_once = array();

    /**
     *
     */
    public function __construct() {
        $this->core = nc_Core::get_object();

        $this->requested_assets = new nc_page_asset_request_collection();

        $fieldmap = $this->core->get_settings('FieldUsage');
        if ($fieldmap) {
            $fieldmap = $this->field_usage = unserialize($fieldmap);
        }

        $this->title_field = $fieldmap['title'];
        $this->keywords_field = $fieldmap['keywords'];
        $this->description_field = $fieldmap['description'];
        $this->smo_title_field = $fieldmap['smo_title'];
        $this->smo_description_field = $fieldmap['smo_description'];
        $this->smo_image_field = $fieldmap['smo_image'];
        $this->language_field = $fieldmap['language'];

        $on_subdivision_update_event = implode(',', array(
            nc_Event::AFTER_SUBDIVISION_UPDATED,
            nc_Event::AFTER_SUBDIVISION_ENABLED,
            nc_Event::AFTER_SUBDIVISION_DISABLED,
            nc_Event::AFTER_INFOBLOCK_CREATED,
            nc_Event::AFTER_INFOBLOCK_UPDATED,
            nc_Event::AFTER_INFOBLOCK_ENABLED,
            nc_Event::AFTER_INFOBLOCK_DISABLED,
            nc_Event::AFTER_INFOBLOCK_DELETED,
            nc_Event::AFTER_OBJECT_CREATED,
            nc_Event::AFTER_OBJECT_UPDATED,
            nc_Event::AFTER_OBJECT_ENABLED,
            nc_Event::AFTER_OBJECT_DISABLED,
            nc_Event::AFTER_OBJECT_DELETED
        ));

        $on_component_update = implode(',', array(
            nc_Event::AFTER_COMMENT_UPDATED,
            nc_Event::AFTER_COMPONENT_TEMPLATE_UPDATED
        ));

        $on_template_update = nc_Event::AFTER_TEMPLATE_UPDATED;

        $this->core->event->bind($this, array($on_subdivision_update_event => 'on_subdivision_update'));
        $this->core->event->bind($this, array($on_component_update => 'on_component_update'));
        $this->core->event->bind($this, array($on_template_update => 'on_template_update'));

        $this->core->event->add_listener(nc_event::AFTER_USER_AUTHORIZED, array($this, 'clear_browser_partials_on_next_page_load'));
    }

    /**
     * Старое название метода fetch_page_metatags().
     * (Оставлено, т.к. по какой-то причине метод был описан в публичном API,
     * хотя вряд ли может пригодиться для разработчика сайтов.)
     *
     * @deprecated
     * @param string $url адрес страницы
     * @return array
     */
    public function get_meta_tags($url) {
        return $this->fetch_page_metatags($url);
    }

    /**
     * Функция получения title и мета-данных страниц.
     *
     * @param string $url адрес страницы
     * @param float $timeout таймаут в секундах
     * @return array
     */
    public function fetch_page_metatags($url, $timeout = 5) {
        $result = array();

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, array('http', 'https'), true)) {
            return $result;
        }

        $stream_context_options = array(
            $scheme => array(
                'timeout' => $timeout,
            )
        );

        $context = stream_context_create($stream_context_options);

        $contents = @file_get_contents($url, false, $context);

        if (!$contents) {
            return $result;
        }

        nc_preg_match('/<title>([^>]*)<\/title>/si', $contents, $match);

        if (isset($match) && is_array($match) && count($match) > 0) {
            $result['title'] = strip_tags($match[1]);
        }
		
        nc_preg_match('/<h1.*?>([^>]*)<\/h1>/si', $contents, $match);

        if (isset($match) && is_array($match) && count($match) > 0) {
            $result['h1'] = strip_tags($match[1]);
        }		

        nc_preg_match_all(
            '/<[\s]*meta[\s]*name=["\']?' . '([^>\'"]*)["\']?[\s]*' . 'content=["\']?([^>"\']*)["\']?[\s]*[\/]?[\s]*>/si',
            $contents,
            $match
        );

        if (isset($match) && is_array($match) && count($match) == 3) {
            $originals = $match[0];
            $names = $match[1];
            $values = $match[2];

            if (count($originals) === count($names) && count($names) === count($values)) {
                for ($i = 0, $limit = count($names); $i < $limit; $i++) {
                    $result[strtolower($names[$i])] = $values[$i];
                }
            }
        }

        return $result;
    }

    /**
     * Установить мета-тег для страницы
     *
     * @param string $item title, keywords, description, smo_title, smo_description, smo_image
     * @param string $value value
     */
    public function set_metatags($item, $value) {
        if ($item === 'smo_image') {
            // Пока что поле SMO_Image не обрабатывается как все прочие файловые
            // поля, а тип файловой системы — всегда NC_FS_ORIGINAL.
            // Преобразование raw-значения в путь файла производится здесь.
            $file_info = explode(':', $value);
            if (isset($file_info[3])) {
                $nc_core = nc_core::get_object();
                $files_path = $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH;
                $value = $files_path . $file_info[3];
            }
        }

        $this->metatags[$item] = $value;
    }

    /**
     * Получить title для страницы
     * Если title не установлен в настройках, то возвращает название раздела
     *
     * @return string
     */
    public function get_title() {
        return nc_array_value($this->metatags, 'title') ?: $this->core->subdivision->get_current('Subdivision_Name');
    }

    /**
     * Получить keywords для страницы
     *
     * @return string|false
     */
    public function get_keywords() {
        return nc_array_value($this->metatags, 'keywords', false);
    }

    /**
     * Получить description для страницы
     *
     * @return string|false
     */
    public function get_description() {
        return nc_array_value($this->metatags, 'description', false);
    }

    /**
     * Получить SMO title для страницы
     *
     * @return string|false
     */
    public function get_smo_title() {
        return nc_array_value($this->metatags, 'smo_title', false);
    }

    /**
     * Получить SMO description для страницы
     *
     * @return string|false
     */
    public function get_smo_description() {
        return nc_array_value($this->metatags, 'smo_description', false);
    }

    /**
     * Получить путь к файлу SMO image для страницы (от корня сайта)
     *
     * @return string|false
     */
    public function get_smo_image() {
        return nc_array_value($this->metatags, 'smo_image', false);
    }

    /**
     * Получить блок мета-тэгов seo/smo для страницы
     *
     * @return string
     */
    public function get_metatags() {
        $meta_seo = $meta_smo_og = $meta_smo_twitter = '';
        $add_meta_smo_url = false;

        // SEO: keywords, description
        $keywords_value = $this->get_keywords();
        if ($keywords_value) {
            $meta_seo .= "\t<meta name=\"keywords\" content=\"" . htmlspecialchars($keywords_value, ENT_QUOTES) . "\" />\n";
        }

        $description_value = $this->get_description();
        if ($description_value) {
            $meta_seo .= "\t<meta name=\"description\" content=\"" . htmlspecialchars($description_value, ENT_QUOTES) . "\" />\n";
        }

        // SMO: title, description
        $smo_title_value = $this->get_smo_title();
        if ($smo_title_value) {
            $content = htmlspecialchars($smo_title_value, ENT_QUOTES);
            $meta_smo_og .= "\t<meta property=\"og:title\" content=\"" . $content . "\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:title\" content=\"" . $content . "\" />\n";
            $add_meta_smo_url = true;
        }

        $smo_description_value = $this->get_smo_description();
        if ($smo_description_value) {
            $content = htmlspecialchars($smo_description_value, ENT_QUOTES);
            $meta_smo_og .= "\t<meta property=\"og:description\" content=\"" . $content . "\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:description\" content=\"" . $content . "\" />\n";
            $add_meta_smo_url = true;
        }

        // SMO: image
        $smo_image_value = $this->get_smo_image();
        if ($smo_image_value) {
            $image_url = htmlspecialchars($this->get_url()->get_host_url() . $smo_image_value);
            $meta_smo_og .= "\t<meta property=\"og:image\" content=\"" . $image_url . "\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:image\" content=\"" . $image_url . "\" />\n";
            $add_meta_smo_url = true;
        }

        // SMO: url
        if ($add_meta_smo_url) {
            $url = htmlspecialchars($this->get_url()->get_full_url());
            $meta_smo_og .= "\t<meta property=\"og:url\" content=\"" . $url . "\" />\n";
            $meta_smo_og .= "\t<meta property=\"og:type\" content=\"article\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:url\" content=\"" . $url . "\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:card\" content=\"summary\" />\n";
        }

        return $meta_seo . "\n" . $meta_smo_og . "\n" . $meta_smo_twitter;
    }

    /**
     * Установить метаданные по данным текущего раздела
     *
     * @param int $current_sub
     */
    public function set_current_metatags($current_sub) {
        if ($current_sub[$this->title_field]) {
            $this->set_metatags('title', $current_sub[$this->title_field]);
        }
        if ($current_sub[$this->keywords_field]) {
            $this->set_metatags('keywords', $current_sub[$this->keywords_field]);
        }
        if ($current_sub[$this->description_field]) {
            $this->set_metatags('description', $current_sub[$this->description_field]);
        }
        if ($current_sub[$this->smo_title_field]) {
            $this->set_metatags('smo_title', $current_sub[$this->smo_title_field]);
        }
        if ($current_sub[$this->smo_description_field]) {
            $this->set_metatags('smo_description', $current_sub[$this->smo_description_field]);
        }
        if ($current_sub[$this->smo_image_field]) {
            $this->set_metatags('smo_image', $current_sub[$this->smo_image_field]);
        }
    }

    /**
     * Имя поля, которое используется для языка
     */
    public function get_language_field() {
        return $this->language_field;
    }

    public function get_field_name($usage) {
        return $this->field_usage[$usage];
    }

    /**
     * Обновление Last-Modified у разделов
     *
     * @param int|array $sub_ids номер раздела или массив с номерами, если 0 - то все
     * @return bool
     */
    protected function save_subdivision_last_modified($sub_ids = 0) {
        if (is_int($sub_ids) && $sub_ids === 0) {
            $where = '';
        }
        else {
            if (!is_array($sub_ids)) {
                $sub_ids = array($sub_ids);
            }
            $sub_ids = array_unique(array_map('intval', $sub_ids));
            if (empty($sub_ids)) {
                return false;
            }
            $where = " WHERE Subdivision_ID IN (" . join(',', $sub_ids) . ") ";
        }

        $this->core->db->query("UPDATE `Subdivision` SET `" . $this->get_field_name('last_modified') . "` = NOW() " . $where);
    }

    /**
     * Перехват события "изменение раздела" для обновления Last-Modified
     *
     * @param int $catalogue_id
     * @param int|array $sub_id
     */
    public function on_subdivision_update($catalogue_id, $sub_id) {
        $res = array();
        if (is_array($sub_id)) {
            foreach ($sub_id as $v) {
                $res = array_merge($res, nc_get_sub_children($v));
            }
        }
        else {
            $res = nc_get_sub_children($sub_id);
        }

        $this->save_subdivision_last_modified($res);
    }

    /**
     * Перехват события "изменение инфоблока" для обновления Last-Modified
     *
     * @param int|array $class_id
     */
    public function on_component_update($class_id) {
        $db = $this->core->db;
        if (!is_array($class_id)) {
            $class_id = array($class_id);
        }
        $class_id = array_map('intval', $class_id);
        $subs = $db->get_col(
            "SELECT sc.Subdivision_ID
               FROM `Sub_Class` AS `sc`, `Class` AS `c`
              WHERE sc.Class_ID = c.Class_ID
                AND (
                        c.Class_ID IN (" . join(',', $class_id) . ")
                        OR c.ClassTemplate IN (" . join(',', $class_id) . ")
                    )"
        );

        $this->save_subdivision_last_modified($subs);
    }

    /**
     * Перехват события "изменение макета дизайна"
     * @param int $id
     */
    public function on_template_update($id) {
        $db = $this->core->db;
        $id = intval($id);

        $childs = $this->core->template->get_childs($id);

        $t = array_merge(array($id), $childs);

        $cat = $db->get_var("SELECT `Catalogue_ID` FROM `Catalogue` WHERE `Template_ID` IN (" . join(',', $t) . ") ");

        if ($cat) {
            $this->save_subdivision_last_modified();
        }
        else {
            $subs = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Template_ID` IN (" . join(',', $t) . ") ");
            if ($subs) {
                $this->on_subdivision_update(0, $subs);
            }
        }
    }

    /**
     * Устанавливает заголовки валидации кэша ETag и Last-Modified;
     * проверяет If-None-Match и If-Modified-Since.
     *
     * @param string $content значение, на основе которого формируется ETag (контент страницы).
     *    Если не передан, заголовок ETag не отсылается.
     */
    public function send_and_check_cache_validator_headers($content = null) {
        if (!headers_sent()) {
            // Значение параметра «Заголовок Last-Modified» в настройках текущего раздела
            $last_modified_subdivision_field = $this->get_field_name('last_modified_type');
            $last_modified_mode = $this->core->subdivision->get_current($last_modified_subdivision_field);

            // Посылаем заголовки Last-Modified, ETag
            $last_modified_value = $this->send_last_modified($last_modified_mode);
            $etag_value = $this->send_etag($last_modified_mode, $content);

            if ($last_modified_value !== '' || $etag_value !== '') {
                // При значении session.cache_limiter по умолчанию (nocache) при старте сессии
                // (т.е. всегда) отсылаются заголовки:
                //   Expires: Thu, 19 Nov 1981 08:52:00 GMT
                //   Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
                //   Pragma: no-cache
                // В итоге браузер не кэширует страницы, не передаёт заголовки If-Modified-Since
                // и If-None-Match.
                // Устанавливаем заголовки так, чтобы была возможность использовать ответ "304 Not Modified":
                header_remove('Expires');
                header_remove('Pragma');
                header('Cache-Control: private, max-age=0'); // кэширование только в браузере, проверка при каждом запросе

                // Отдаём 304, если страница не изменилась со времени предыдущего запроса
                $this->check_if_modified($last_modified_value, $etag_value);
            }
        }
    }

    /**
     * Посылает заголовок Last-Modified для текущей страницы.
     * В зависимости от свойства раздела ncLastModifiedType заголовок может не посылаться,
     * посылаться текущее время или актуальное.
     *
     * @param int $last_modified_mode константа NC_LASTMODIFIED_*
     * @return string отправленное значение заголовка (отформатированная дата или пустая строка)
     */
    protected function send_last_modified($last_modified_mode) {
        switch ($last_modified_mode) {
            case NC_LASTMODIFIED_NONE:
                return '';
            case NC_LASTMODIFIED_ACTUAL:
                // если нет контента, вернём время изменения шаблонов
                $timestamp = $this->last_modified_content ?: $this->last_modified_template;
                break;
            case NC_LASTMODIFIED_CURRENT:
                $timestamp = time();
                break;
            case NC_LASTMODIFIED_YESTERDAY:
                $timestamp = strtotime('-1 day midnight');
                break;
            case NC_LASTMODIFIED_HOUR:
                $timestamp = strtotime(date('Y-m-d H:00:00', strtotime('-1 hour')));
                break;
            default: // значение может отсутствовать (быть равным false) при отправке файлов «защищённой файловой системы»
                $timestamp = (int)max($this->last_modified_content, $this->last_modified_template);
                break;
        }

        if (!$timestamp) {
            return '';
        }

        $last_modified = nc_timestamp_to_gmt($timestamp);
        header("Last-Modified: $last_modified");
        return $last_modified;
    }

    /**
     * Посылает заголовок ETag в подходящих режимах $last_modified_mode и при наличии $content.
     *
     * @param int $last_modified_mode константа NC_LASTMODIFIED_*
     * @param string|null content контент страницы
     * @return string значение ETag или пустая строка, если ETag не отправлен
     */
    protected function send_etag($last_modified_mode, $content) {
        // Вычисляем хэш для ETag от $content только если кэширование может зависеть от Last-Modified
        if ($content && $last_modified_mode != NC_LASTMODIFIED_NONE && $last_modified_mode != NC_LASTMODIFIED_CURRENT) {
            // Для ETag используется MD4 как наиболее быстрый способ вычисления длинного хэша
            $etag = '"' . hash('md4', $content) . '"';
            header("ETag: $etag");
        }
        else {
            $etag = '';
        }

        return $etag;
    }

    /**
     * Проверяет If-None-Match и If-Modified-Since, отсылает 304 Not Modified при совпадении.
     *
     * @param string $last_modified отправленное значение заголовка Last-Modified для страницы
     *      (отформатированная дата или пустая строка)
     * @param string $etag отправленное значение заголовка ETag для страницы
     *      (хэш в кавычках или пустая строка)
     */
    protected function check_if_modified($last_modified, $etag) {
        $if_none_match = nc_array_value($_SERVER, 'HTTP_IF_NONE_MATCH');
        $if_modified_since = nc_array_value($_SERVER, 'HTTP_IF_MODIFIED_SINCE');

        // Только на Last-Modified полагаться нельзя, так как содержимое страницы может
        // отличаться даже при одинаковой дате последнего изменения (например, пользователь
        // залогинился или разлогинился, имеется какой-либо динамический контент и т. п.).
        // Приоритет должен иметь ETag.
        if ($etag && $if_none_match) {
            // nginx при сжатии добавляет 'W/' к ETag (но не меняет значение)
            $not_modified = $if_none_match === $etag || $if_none_match === "W/$etag";
        }
        else if (!$if_none_match && $last_modified && $if_modified_since) {
            $not_modified =
                // браузеры обычно передают точное совпадение, as advised in RFC 2616
                $last_modified === $if_modified_since ||
                // last-modified.com передаёт дату, не совпадающую с переданной в предыдущем запросе
                strtotime($last_modified) <= strtotime($if_modified_since);
        }
        else {
            $not_modified = false;
        }

        if ($not_modified) {
            nc_set_http_response_code(304);
            exit;
        }
    }

    /**
     * Устанавливает флаг «сейчас идёт формирование частей страницы, за которые отвечает макет дизайна».
     * (Влияет на формирование времени Last-Modified, см. метод update_last_modified_if_newer();
     * на порядок стилей блоков, см. метод add_styles())
     *
     * @param bool $value
     */
    public function is_processing_template_now($value = true) {
        $this->is_processing_template_now = $value;
    }

    /**
     * Запоминает самое позднее время изменения объектов на текущей странице.
     *
     * @param int $timestamp unix timestamp; значение должно иметь тип int
     * @param string $type одно из значений: content или template
     */
    public function update_last_modified_if_timestamp_is_newer($timestamp, $type = 'content') {
        if (!is_int($timestamp) || $timestamp > 7258107600) { // работает до даты '2200-01-01'
            return;
        }

        if ($this->is_processing_template_now) {
            $type = 'template';
        }
        $last_modified_param = 'last_modified_' . $type;

        if ($timestamp > $this->$last_modified_param) {
            $this->$last_modified_param = $timestamp;
        }
    }

    /**
     * @return string
     */
    public function get_h1() {
        return $this->h1;
    }

    /**
     * @param string $h1
     */
    public function set_h1($h1) {
        $this->h1 = $h1;
    }

    /**
     * @param $routing_result
     */
    public function set_routing_result($routing_result) {
        $this->routing_result = $routing_result;
    }

    /**
     * @param string|null $item
     * @return array|false|null|string
     */
    public function get_routing_result($item = null) {
        if ($item) {
            return isset($this->routing_result[$item]) ? $this->routing_result[$item] : null;
        }
        return $this->routing_result;
    }

    /**
     * @param mixed $canonical_link
     */
    public function set_canonical_link($canonical_link) {
        $this->canonical_link = $canonical_link;
    }

    /**
     * @return mixed
     */
    public function get_canonical_link() {
        return $this->canonical_link;
    }

    /**
     * Возвращает тэг <link rel="canonical"> для текущей страницы
     *
     * @return string
     */
    public function get_canonical_link_tag() {
        if (!$this->canonical_link) {
            return '';
        }
        else {
            return '<link rel="canonical" href="' . htmlspecialchars($this->canonical_link) . '" />';
        }
    }

    /**
     * @return nc_url
     */
    protected function get_url() {
        return $this->url ?: nc_core::get_object()->url;
    }

    /**
     * @param nc_url $url
     */
    public function set_url(nc_url $url) {
        $this->url = $url;
    }

    /**
     * Фиксирует использование компонента и шаблона компонента в пределах текущей страницы
     * @param int $component_id
     * @param int $component_template_id
     */
    public function register_component_usage($component_id, $component_template_id = 0) {
        $component_id = (int)$component_id;
        $component_template_id = (int)$component_template_id;

        if ($component_template_id) {
            $nc_core = nc_core::get_object();
            if ($nc_core->component->get_by_id($component_template_id, 'IsMultipurpose')) {
                $component_id = $component_template_id;
            }
        } else {
            $component_template_id = $component_id;
        }

        $this->component_template_usage[$component_id][$component_id] = $component_id;
        $this->component_template_usage[$component_template_id][$component_id] = $component_id;
    }

    /**
     * Возвращает тэг <link> для подключения стилей компонентов. Если на
     * сайте не используются компоненты со стилями, возвращает пустую строку.
     * Если не указан аргумент $always_return, выдаёт результат только при первом
     * вызове (это используется для того, чтобы не добавлять стили автоматически
     * в заголовок страницы тогда, когда они добавлены в макете дизайна).
     * @param bool $always_return
     * @return string
     */
    public function get_site_component_styles_tag($always_return = false) {
        static $already_returned = false;
        if (!$already_returned || $always_return) {
            $already_returned = true;
            $site_id = nc_core::get_object()->catalogue->get_current('Catalogue_ID');
            $styles = nc_tpl_stylesheet_assembler::get_site_component_styles($site_id, $this->component_template_usage);
            if ($styles) {
                $result = '<link rel="stylesheet" href="' . $styles['path'] . '" />';
                if ($styles['breakpoints']) {
                    $this->require_asset_once('nc_element_queries', array('embed' => true));
                    $result .= "\n<script>\nnc_eq(" . json_encode($styles['breakpoints']) . ");\n</script>";
                }
                return $result;
            }
        }
        return '';
    }

    /**
     * Добавляет в <head> тэги:
     * - rel canonical
     * - style для подключения стилей компонентов
     * - script/style для assets
     *
     * Служебный метод, не является частью публичного API.
     *
     * @param $buffer
     * @return string
     */
    public function add_tags_to_output($buffer) {
        // добавляем что-то только если есть <head>
        if (stripos($buffer, '<head') === false) {
            return $buffer;
        }

        $clear_browser_partials = $this->clear_browser_partials();

        // get_site_component_styles_tag() нужно выполнить до get_asset_tags(), т. к. там может подключаться nc_element_queries:
        $component_styles_tag = $this->get_site_component_styles_tag();

        $tags = array();
        $tags[] = $this->get_canonical_link_tag();
        $tags[] = $this->get_asset_tags();
        $tags[] = $this->get_partials_script($clear_browser_partials);
        $tags[] = $component_styles_tag;
        $tags[] = $this->get_mixin_styles_tag();

        if ($nc_core = nc_core::get_object()->admin_mode) {
            $tags[] = $this->get_admin_mode_meta_tag();
        }

        if ($this->scripts) {
            $tags[] = "<script>" . join("</script>\n<script>", $this->scripts) . "</script>\n";
        }

        if ($this->scripts_on_dom_loaded) {
            $try = 'try{';
            $catch = '}catch(e){console.log(e)}';
            $tags[] =
                "<script>\ndocument.addEventListener('DOMContentLoaded', function() {\n$try" .
                join("$catch\n$try", $this->scripts_on_dom_loaded) . "$catch\n})\n</script>\n";
        }

        $tags = array_filter($tags);

        if ($tags) {
            $buffer = nc_insert_in_head($buffer, join("\n", $tags) . "\n", true);
        }

        return $buffer;
    }

    /**
     * (Служебный метод, не является частью публичного API)
     * @return string
     */
    public function get_tags_for_partials() {
        $mixin_scripts = $this->scripts_on_dom_loaded
            ? "<script>" . join("\n", $this->scripts_on_dom_loaded) . "</script>\n"
            : '';
        return $this->get_asset_tags() .
               $this->get_site_component_styles_tag(true) .
               $this->get_mixin_styles_tag() .
               $mixin_scripts;
    }

    /**
     * Выставляет флаг для удаления при следующей загрузке страницы partials,
     * закэшированных в браузере (см. nc_core::output_page_buffer(),
     * nc_page::insert_partials_script())
     * @internal не является частью публичного API
     */
    public function clear_browser_partials_on_next_page_load() {
        $_SESSION['nc_remove_partials_in_browser'] = true;
    }

    /**
     * @return bool
     */
    protected function clear_browser_partials() {
        // флаг в сессии, отвечающий за сброс SessionStorage
        $clear_browser_partials = !empty($_SESSION['nc_remove_partials_in_browser']);
        unset($_SESSION['nc_remove_partials_in_browser']);

        if ($clear_browser_partials) {
            $this->require_asset_once('nc_partial_load', array('embed' => true));
        }

        return $clear_browser_partials;
    }

    /**
     * JS для работы с загружаемыми фрагментами (nc_partial_load)
     *
     * @param bool $clear_browser_partials
     * @return string
     */
    protected function get_partials_script($clear_browser_partials) {
        $partials_script = '';

        if ($this->requested_assets->has('nc_partial_load')) {
            $nc_core = nc_core::get_object();
            $partials_script .=
                "<script>" .
                "var NETCAT_PATH = '{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}';" .
                ($clear_browser_partials ? "nc_partial_clear_cache();" : '') .
                "</script>";
        }

        return $partials_script;
    }

    /**
     * Добавляет на страницу библиотеки, перечисленные в массиве.
     * Возможные способы указания библиотек:
     * array(
     *     'asset_keyword@ver',
     *     'asset_keyword@ver' => array('defer' => false),
     * )
     *
     * Этот же формат используется в файлах RequiredAssets.html.
     *
     * Не является частью публичного API. Для подключения библиотек в компонентах
     * и макетах дизайна используйте файл RequiredAssets.html.
     *
     * @param array $assets
     * @return bool false, если библиотека не была добавлена из-за конфликта версий
     */
    public function require_assets(array $assets) {
        return $this->requested_assets->add_asset_requests_from_array($assets);
    }

    /**
     * Добавляет библиотеку на страницу
     *
     * Для подключения библиотек в компонентах и макетах дизайна используйте файл RequiredAssets.html.
     *
     * @param $asset_keyword_and_version
     *    Ключевое слово и версия библиотеки через @, например: 'jquery@1.*'
     *    Допустимые способы задания версий:
     *      (пустая строка — использовать последнюю имеющуюся версию)
     *      1.1
     *      1.0-1.3
     *      1.*
     *      1.0-2.*
     *    Пробелы не допускаются
     * @param array $options
     *    'defer' — использовать defer для скриптов (по умолчанию true)
     * @return bool false, если библиотека не была добавлена из-за конфликта версий
     */
    public function require_asset($asset_keyword_and_version, array $options = array()) {
        return $this->requested_assets->add_asset_request_from_string($asset_keyword_and_version, $options);
    }

    /**
     * Добавляет библиотеку; при повторном вызове, в отличие от self::require_asset(),
     * не производит анализ и объединение версий и параметров, поэтому может использоваться
     * только со служебными библиотеками, параметры подключения которых везде одинаковые.
     *
     * Если библиотека уже была добавлена через require_asset(), то однократно будет произведено
     * объединение параметров (defer, embed).
     *
     * @internal Не является частью публичного API — может быть изменён в будущих версиях.
     * Для подключения библиотек в прочих случаях используйте RequiredAssets.html,
     * или, где это невозможно, $nc_core->page->require_asset().
     *
     * @param string $asset_keyword  только ключевое слово, без указания версий
     * @param array $options
     */
    public function require_asset_once($asset_keyword, array $options = array()) {
        if (empty($this->requested_assets_once[$asset_keyword])) {
            $this->require_asset($asset_keyword, $options);
            $this->requested_assets_once[$asset_keyword] = true;
        }
    }

    /**
     * Проверяет, была ли уже запрошена указанная библиотека
     *
     * @internal Не является частью публичного API — может быть изменён в будущих версиях.
     *
     * @param string $asset_keyword
     * @return bool
     */
    public function has_asset($asset_keyword) {
        return $this->requested_assets->has($asset_keyword);
    }

    /**
     * @return string
     */
    protected function get_asset_tags() {
        $tags = array();
        $resources = $this->requested_assets->with_dependencies()->get_resource_paths();

        if ($resources['js_embed']) {
            $nc_core = nc_core::get_object();
            $embed = '';
            foreach ($resources['js_embed'] as $script) {
                $embed .= "<script>\n" . file_get_contents($nc_core->DOCUMENT_ROOT . $script) . "</script>\n";
            }
            $tags[] = $embed;
        }

        foreach ($resources['js_sync'] as $script) {
            $tags[] = '<script src="' . $script . '"></script>';
        }

        foreach ($resources['js_defer'] as $script) {
            $tags[] = '<script src="' . $script . '" defer></script>';
        }

        foreach ($resources['css'] as $style) {
            $tags[] = '<link rel="stylesheet" href="' . $style . '" />';
        }

        return join("\n", $tags);
    }

    /**
     * Подключает стили сайта с ID 0 (используются для блоков страниц полного вывода).
     * @param bool $do
     * @return void
     */
    public function include_custom_full_page_mixin_styles($do = true) {
        $this->include_custom_full_page_mixin_styles = $do;
    }

    /**
     * Возвращает тег с данными о текущей странице (используется в диалоге выбора
     * областей видимости блоков, см. visibility_conditions.view.php)
     * @return string
     */
    protected function get_admin_mode_meta_tag() {
        $nc_core = nc_core::get_object();

        $subdivision_id = (int)$GLOBALS['sub'];
        $subdivision_name = $subdivision_id ? $nc_core->subdivision->get_by_id($subdivision_id, 'Subdivision_Name') : null;

        $action = nc_array_value($GLOBALS, 'action', 'index');

        $component_id = $action !== 'index' ? (int)nc_array_value($GLOBALS, 'classID') : null;
        $component_name = $component_id ? $nc_core->component->get_by_id($component_id, 'Class_Name') : null;

        $object_id = (int)nc_array_value($GLOBALS, 'message');
        if ($component_id && $object_id) {
            $object_name = NETCAT_MODERATION_OBJECT . ' #' . $object_id;
        } else {
            $object_name = null;
        }

        $routing_result = $this->get_routing_result();

        $infoblock_id = $routing_result['infoblock_id'];
        if (isset($routing_result['variables']['cc_only'])) {
            $infoblock_id = $routing_result['variables']['cc_only'];
        } elseif (isset($routing_result['variables']['cc'])) {
            $infoblock_id = $routing_result['variables']['cc'];
        }

        $meta = array(
            'site-id' => $routing_result['site_id'],
            'subdivision-id' => $routing_result['folder_id'],
            'subdivision-name' => $subdivision_name,
            'infoblock-id' => $infoblock_id,
            'component-id' => $component_id ?: null,
            'component-name' => $component_name,
            'object-id' => $routing_result['object_id'],
            'object-name' => $object_name,
            'action' => $routing_result['action'],
        );
        $tag = "<meta id='nc_page'"; // HTML5 позволяет иметь id у <meta>
        foreach ($meta as $key => $value) {
            $tag .= " data-$key='$value'";
        }
        $tag .= '>';
        return $tag;
    }

    /**
     * @return string|null
     */
    protected function get_mixin_styles_tag() {
        $nc_core = nc_core::get_object();

        if ($nc_core->inside_admin) {
            return '';
        }

        $result = '';

        $mixins_css = array(
            nc_tpl_mixin_cache::get_combined_styles_url($nc_core->catalogue->get_current('Catalogue_ID'))
        );

        if ($this->include_custom_full_page_mixin_styles) {
            $mixins_css[] = nc_tpl_mixin_cache::get_combined_styles_url(0);
        }

        foreach ($mixins_css as $path) {
            if ($path) {
                $result .= '<link rel="stylesheet" href="' . $path . '" />' . "\n";
            }
        }

        if ($this->styles) {
            ksort($this->styles);
            $result .= "<style>\n" . join("\n", $this->styles) . "\n</style>\n";
        }

        return $result;
    }

    /**
     * (Не является частью API)
     * @param string $buffer
     * @return string
     */
    public function add_page_mixins($buffer) {
        $nc_core = nc_core::get_object();
        $site_id = $nc_core->catalogue->get_current('Catalogue_ID');
        $body_css_classes = array();
        $parents = (array)$nc_core->subdivision->get_parent_tree($nc_core->subdivision->get_current('Subdivision_ID'));
        $breakpoints = array();
        foreach (array_reverse($parents) as $item) {
            $mixin_settings = $item['MainArea_Mixin_Settings'];
            if ($mixin_settings && $mixin_settings !== '{}') {
                if (isset($item['Subdivision_ID'])) {
                    $body_css_classes[] = "tpl-body-sub-$item[Subdivision_ID]";
                    nc_tpl_mixin_cache::load_subdivision_data($site_id, $item['Subdivision_ID']);
                    $breakpoints[] = $nc_core->subdivision->get_meta($item['Subdivision_ID'], 'MainArea_Breakpoints');
                } else {
                    $body_css_classes[] = "tpl-body-site-$site_id";
                    nc_tpl_mixin_cache::load_site_data($site_id);
                    $breakpoints[] = $nc_core->catalogue->get_meta($site_id, 'MainArea_Breakpoints');
                }
            }
        }

        $breakpoints = implode(' ', $breakpoints);
        $breakpoints_attribute = $breakpoints ? 'data-nc-b="' . $breakpoints . '"' : '';

        if ($nc_core->admin_mode && !$nc_core->inside_admin) {
            // ".nc--mode-edit" используется в миксинах layout/swiper, border_radius
            $body_css_classes[] = 'nc--mode-edit';
        }

        if ($body_css_classes) {
            $buffer = $this->alter_body_tag($buffer, join(' ', $body_css_classes), $breakpoints_attribute);
        }
        
        return $buffer;
    }

    /**
     * @param string $html
     * @param string $class
     * @param string $attributes
     * @return string
     * @internal не является частью публичного API
     */
    public function alter_body_tag($html, $class, $attributes = '') {
        if (!preg_match('/<body[^>]*>/si', $html, $matches)) {
            return $html; // no <body>
        }

        $original_body_tag = $matches[0];
        if (preg_match('/(\bclass\s*=\s*[\'"]?)/si', $original_body_tag, $matches)) {
            $replacement_body_tag = str_replace($matches[1], $matches[1] . $class . ' ', $original_body_tag);
            if ($attributes) {
                $replacement_body_tag = str_replace('>', " $attributes>", $replacement_body_tag);
            }
        } else {
            $replacement_body_tag = str_replace('>', ' class="' . $class . '"' . ($attributes ? " $attributes" : '') . '>', $original_body_tag);
        }

        return str_replace($original_body_tag, $replacement_body_tag, $html);
    }

}
