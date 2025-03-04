<?php

/**
 * Интеграция с Яндекс.Метрикой и Google Analytics
 */

class nc_stats_analytics {

    const SCRIPT_POSITION_BODY_TOP = 0;
    const SCRIPT_POSITION_BODY_BOTTOM = 1;

    /** @var int  */
    protected $site_id;

    // Некоторые часто используемые настройки кэшируются в свойствах:
    /** @var bool */
    protected $is_enabled;
    /** @var bool */
    protected $is_ecommerce_enabled;
    /** @var string */
    protected $ga_code;
    /** @var string */
    protected $ym_code;
    /** @var string */
    protected $currency_code;

    /** @var string  регвыр, по которому мы ищем <script></script> с гуглоаналитикой */
    protected $ga_anchor_regexp = '/[\'"]UA-\d+-\d+[\'"]/';

    /** @var string  регвыр, по которому мы ищем <script></script> с гугл-тег-менеджером */
    protected $gtm_anchor_regexp = '/[\'"]GTM-\w+[\'"]/';

    /** @var string  регвыр, по которому мы ищем <script></script> с яндексометрикой */
    protected $ym_anchor_regexp = '/\.yaCounter\d+/';

    /** @var int  максимальная длина значения куки */
    protected $cookie_value_length = 2048;
    /** @var int  номер события, отправленного через куку */
    protected $cookie_event_number = 0;

    /**
     * Вспомогательный метод для упрощения формирования атрибута data-analytics-item
     *
     * @param nc_netshop_item $item
     * @param string|null $list_name
     * @param int|null $list_position
     * @return string
     */
    public static function get_item_attribute(nc_netshop_item $item, $list_name = null, $list_position = null) {
        static $add_item_attributes;

        if ($add_item_attributes === false) {
            return '';
        }

        $stats = nc_stats::get_instance();
        if ($add_item_attributes === null) {
            $add_item_attributes =
                $stats->analytics->is_ecommerce_enabled() &&
                $stats->analytics->is_configured();

            if (!$add_item_attributes) {
                return '';
            }
        }

        $item_data = $stats->analytics->get_ecommerce_item_data($item);
        unset($item_data['quantity']);

        if ($list_name) {
            $item_data['list_name'] = $list_name;
        }

        if ($list_position !== null) {
            $item_data['list_position'] = (int)$list_position;
        }

        return " data-analytics-item='" .
               htmlspecialchars(nc_array_json($item_data), ENT_QUOTES) .
               "'";
    }

    /**
     * Вспомогательный метод для упрощения формирования атрибута data-analytics-promo
     *
     * @param array $promo свойства, как для Google Analytics (gtag.js)
     * @return string
     */
    public static function get_promo_attribute(array $promo) {
        static $add_item_attributes;

        if ($add_item_attributes === false) {
            return '';
        }

        if ($add_item_attributes === null) {
            $stats = nc_stats::get_instance();
            $add_item_attributes =
                $stats->analytics->is_ecommerce_enabled() &&
                $stats->analytics->is_configured();

            if (!$add_item_attributes) {
                return '';
            }
        }

            return " data-analytics-promo='" . htmlspecialchars(nc_array_json($promo), ENT_QUOTES) . "'";
    }

    /**
     *
     * @param nc_stats $stats
     */
    public function __construct(nc_stats $stats) {
        $this->site_id = $stats->get_site_id();
    }

    /**
     * @param $setting
     * @return mixed
     */
    protected function get_setting($setting) {
        return nc_stats::get_instance($this->site_id)->get_setting('Analytics_' . $setting);
    }

    /**
     * @param $setting
     * @param $value
     * @return bool
     */
    protected function set_setting($setting, $value) {
        return nc_stats::get_instance($this->site_id)->set_setting('Analytics_' . $setting, $value);
    }

    /**
     * Инициализация слушателей событий для скриптов аналитики.
     * Должна выполняться после загрузки всех модулей.
     */
    public function init_event_listeners() {
        if (!$this->is_enabled() || !$this->is_configured()) {
            return;
        }

        $nc_core = nc_core::get_object();

        // Возможно, когда-то будет добавлено отслеживание возвратов заказов, но сейчас
        // нам делать в режимах администрирования и редактирования нечего
        // (если это будет изменено, нужно будет перепроверить обработчики событий)
        if ($nc_core->admin_mode) {
            return;
        }

        // Обработчик буфера для скриптов аналитики
        $nc_core->add_output_processor(array($this, 'process_page_buffer'));

        // Обработчик событий корзины для аналитики э-коммерции
        if ($this->is_ecommerce_enabled() && nc_module_check_by_keyword('netshop')) {
            $nc_core->event->add_listener(nc_netshop::EVENT_AFTER_CART_CHANGED, array($this, 'on_netshop_cart_change'));
            $nc_core->event->add_listener(nc_netshop::EVENT_AFTER_ORDER_CREATED, array($this, 'on_netshop_checkout'));
        }
    }

    /**
     * Возвращает TRUE, если в настройках модуля включена интеграция с аналитикой
     * @return mixed
     */
    public function is_enabled() {
        if ($this->is_enabled === null) {
            $this->is_enabled = (bool)$this->get_setting('Enabled');
        }
        return $this->is_enabled;
    }

    /**
     * Возвращает TRUE, если в настройках модуля включена отправка событий E-Commerce
     * и включена собственно аналитика
     * @return mixed
     */
    public function is_ecommerce_enabled() {
        if ($this->is_ecommerce_enabled === null) {
            $this->is_ecommerce_enabled = $this->is_enabled() && $this->get_setting('EcommerceEnabled');
        }
        return $this->is_ecommerce_enabled;
    }

    /**
     * @return string
     */
    protected function get_ga_code() {
        if ($this->ga_code === null) {
            $this->ga_code = (string)$this->get_setting('GA_Code');
        }
        return $this->ga_code;
    }

    /**
     * @return string
     */
    protected function get_ym_code() {
        if ($this->ym_code === null) {
            $this->ym_code = (string)$this->get_setting('YM_Code');
        }
        return $this->ym_code;
    }

    /**
     * Возвращает TRUE, если в настройках модуля указан хотя бы один код счётчика
     * @return bool
     */
    public function is_configured() {
        return $this->get_ga_code() || $this->get_ym_code();
    }

    /**
     * Вставляет необходимые скрипты на страницу
     * @param $buffer
     * @return string
     */
    public function process_page_buffer($buffer) {
        $position = $this->get_setting('ScriptPosition');

        // Место вставки
        $insertion_point = false;
        if ($position == self::SCRIPT_POSITION_BODY_TOP) {
            $body_position = stripos($buffer, '<body');
            if ($body_position) {
                $insertion_point = strpos($buffer, '>', $body_position) + 1;
            }
        }
        else if ($position == self::SCRIPT_POSITION_BODY_BOTTOM) {
            $insertion_point = stripos($buffer, '</body');
        }

        // Э-коммерция:
        if ($this->is_ecommerce_enabled() && nc_module_check_by_keyword('netshop')) {
            $netshop = nc_netshop::get_instance();
            // Записываем код валюты в куку для Google Analytics
            if (empty($_COOKIE['nc_stats_currency'])) {
                nc_core::get_object()->cookie->set('nc_stats_currency', $this->get_ecommerce_currency_code());
            }
            // Этом метод «заодно» отслеживает событие «просмотр страницы товара»
            $item_page_event_data = $this->get_ecommerce_view_item_event_data($netshop);
        } else {
            $item_page_event_data = false;
        }

        // Если определено место вставки (есть <body> или </body>), добавляем нужные скрипты
        if ($insertion_point) {
            $ga_code = $this->get_ga_code();
            $ym_code = $this->get_ym_code();

            $code_to_insert = '';
            $additional_js = '';

            // GA: добавляем счётчик, если не видим его на странице
            $add_ga = $ga_code &&
                      !$this->extract_script($buffer, $this->ga_anchor_regexp) &&
                      !$this->extract_script($buffer, $this->gtm_anchor_regexp);

            if ($add_ga) {
                $code_to_insert .= $ga_code;
            }

            // YM: добавляем счётчик, если не видим его на странице
            $add_ym = $ym_code &&
                      !$this->extract_script($buffer, $this->ym_anchor_regexp);

            if ($add_ym) {
                $code_to_insert .= $ym_code;
                if ($this->is_ecommerce_enabled()) {
                    $additional_js .= 'dataLayer=window.dataLayer||[];'; // для Яндекс.Метрики
                }
            }

            if ($item_page_event_data) {
                $additional_js .= "nc_stats_analytics_event('$item_page_event_data[0]'," . nc_array_json($item_page_event_data[1]) . ");";
            }

            $code_to_insert .=
                '<script>' .
                // плейсхолдер для функции nc_stats_analytics_event(), чтобы принимать события
                // сразу, до окончания загрузки скриптов (по аналогии с тем, как работает
                // «массив» dataLayer)
                '(function(){var f=nc_stats_analytics_event=function(){f.E?f.E.push(arguments):f.E=[arguments]}})();' .
                $additional_js .
                '</script>' .
                '<script src="' . nc_module_path('stats') . 'js/nc_stats_analytics_event.min.js" defer></script>';

            $buffer = substr($buffer, 0, $insertion_point) .
                      $code_to_insert .
                      substr($buffer, $insertion_point);
        } else if ($item_page_event_data) {
            // частичная загрузка страницы полного вывода товара (например, переключение варианта товара)
            $this->set_ecommerce_cookie($item_page_event_data[0], $item_page_event_data[1]);
        }

        return $buffer;
    }

    /**
     * Извлекает с главной страницы сайта код счётчиков и сохраняет его в настройках модуля
     * (если он там ещё не указан)
     */
    public function extract_and_save_counters_from_title_page() {
        if (!$this->is_enabled()) {
            return;
        }

        $ga_code = $this->get_setting('GA_Code');
        $ym_code = $this->get_setting('YM_Code');

        if ($ga_code && $ym_code) {
            return;
        }

        $title_page = $this->get_site_title_page();
        if (!$title_page) {
            return;
        }

        if (!$ga_code) {
            $code =
                $this->extract_script($title_page, $this->gtm_anchor_regexp, true) ?:
                $this->extract_script($title_page, $this->ga_anchor_regexp);

            if ($code) {
                $this->set_setting('GA_Code', $code);
                $this->ga_code = $code;
            }
        }

        if (!$ym_code) {
            $code = $this->extract_script($title_page, $this->ym_anchor_regexp, true);

            if ($code) {
                $this->set_setting('YM_Code', $code);
                $this->ym_code = $code;
            }
        }
    }

    /**
     * @return bool|string
     */
    protected function get_site_title_page() {
        $url =
            (
                nc_core::get_object()->catalogue->get_url_by_id($this->site_id)
                ?: nc_get_scheme() . '://' . $_SERVER['HTTP_HOST']
            ) . '/';

        return @file_get_contents($url);
    }

    /**
     * @param string $html  markup to examine
     * @param string $regexp  regexp for what we are looking for inside <script></script>
     * @param bool $include_no_script  add following <noscript></noscript> fragment if there is any
     * @param int $max_length  maximum length of the <script></script> contents
     * @return bool|string
     */
    protected function extract_script($html, $regexp, $include_no_script = false, $max_length = 2048) {
        if (!$html) {
            return false;
        }

        if (!preg_match($regexp, $html, $regs, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $offset = $regs[0][1];

        $html_length = strlen($html);
        $script_tag_start = strrpos($html, '<script', $offset - $html_length);
        if (!$script_tag_start) {
            return false;
        }

        $script_tag_end = strpos($html, '</script>', $offset);
        if (!$script_tag_end) {
            return false;
        }

        $script_tag_end += 9; // 9 is the length of '</script>'
        if ($script_tag_end - $script_tag_start > $max_length) {
            return false;
        }

        if ($include_no_script) {
            // Try to find sibling </noscript> tag (immediately before or after the <script>)
            $next = trim(substr($html, $script_tag_end, 30));
            if (strpos($next, '<noscript>') === 0) {
                $noscript_tag_end = strpos($html, '</noscript>', $script_tag_end);
                if ($noscript_tag_end) {
                    $script_tag_end = $noscript_tag_end + 11; // 11 is the length of '</noscript>'
                }
            }
            else {
                $previous = substr($html, $script_tag_start - 30, $script_tag_start);
                $noscript_tag_end = strrpos($previous, '</noscript>');
                if ($noscript_tag_end !== false) {
                    $noscript_tag_start = strrpos($html, '<noscript>', $script_tag_start - $html_length);
                    if ($noscript_tag_start) {
                        $script_tag_start = $noscript_tag_start;
                    }
                }
            }
        }

        return substr($html, $script_tag_start, $script_tag_end - $script_tag_start);
    }

    /**
     * Возвращает массив с данными о товаре в виде, используемом в dataLayer
     * @param nc_netshop_item $item
     * @param null|int $qty количество (если не указано, берётся из $item['Qty'])
     * @return array
     */
    public function get_ecommerce_item_data(nc_netshop_item $item, $qty = null) {
        return array(
            'name' => $item['Name'] ?: $item['FullName'],
            'id' => $item['_ItemKey'],
            'brand' => $item['Vendor'],
            'category' => $this->get_ecommerce_category_string($item['Subdivision_ID']),
            'variant' =>
                // название варианта берём из VariantName, если оно есть
                $item['VariantName'] ?:
                // а если нет VariantName, используем для вариантов товара FullName
                ($item->has_parent() ? $item['FullName'] : ''),
            'price' => $item['ItemPrice'] ?: $item['ItemPriceMin'],
            // Аналитика/Метрика поддерживают только целые количества (март 2018)
            'quantity' => (int)($qty === null ? $item['Qty'] : abs($qty)),
        );
    }

    /**
     * @param int $subdivision_id
     * @return string
     */
    protected function get_ecommerce_category_string($subdivision_id) {
        $nc_core = nc_core::get_object();
        $subdivision_names = array();

        while ($subdivision_id) {
            try {
                $subdivision = $nc_core->subdivision->get_by_id($subdivision_id);
            } catch (Exception $e) {
                break;
            }
            $subdivision_id = $subdivision['Parent_Sub_ID'];
            if ($subdivision_id) { // раздел верхнего уровня пропускаем — обычно это раздел «Каталог»
                $subdivision_names[] = $subdivision['Subdivision_Name'];
            }

            // Аналитика и Метрика поддерживают до 5 уровней (март 2018)
            if (count($subdivision_names) >= 5) {
                break;
            }
        }

        return join('/', array_reverse($subdivision_names));
    }

    /**
     * @return string
     */
    protected function get_ecommerce_currency_code() {
        if (!$this->currency_code) {
            $code = nc_netshop::get_instance($this->site_id)->get_currency_code();
            $this->currency_code = ($code === 'RUR' ? 'RUB' : $code);
        }
        return $this->currency_code;
    }

    /**
     * Передаёт данные о событии e-commerce в браузер через куки
     * @param string $event
     * @param array $data
     */
    protected function set_ecommerce_cookie($event, array $data) {
        $nc_core = nc_core::get_object();
        $event_number = $this->cookie_event_number++;
        $time = $_SERVER['REQUEST_TIME'];

        $cookie_value = array($event, $data);

        $json_chunks = str_split(nc_array_json($cookie_value), $this->cookie_value_length);
        foreach ($json_chunks as $i => $chunk) {
            $nc_core->cookie->set("nc_stats_event_{$time}_{$event_number}_{$i}", $chunk);
        }

        // домен — чтобы можно удалить куку nc_stats_event_* в JS
        if (empty($_COOKIE['nc_stats_domain'])) {
            $nc_core->cookie->set('nc_stats_domain', $nc_core->cookie->get_domain());
        }
    }

    /**
     * Слушатель события nc_netshop::EVENT_AFTER_CART_CHANGED
     * @param nc_netshop_item $item
     * @param $qty_change
     */
    public function on_netshop_cart_change(nc_netshop_item $item, $qty_change) {
        if ($qty_change > 0) {
            $event = 'add_to_cart';
        } else if ($qty_change < 0) {
            $event = 'remove_from_cart';
        } else {
            return;
        }

        $event_data = array(
            // Когда категория не добавлена, gtag() добавляет категорию события такую же,
            // как у предыдущего события, что может давать некорректный результат.
            // Устанавливаем дефолтную категорию
            // https://developers.google.com/analytics/devguides/collection/gtagjs/events#default_google_analytics_events
            'event_category' => 'ecommerce',
            'items' => array($this->get_ecommerce_item_data($item, $qty_change)),
        );

        $this->set_ecommerce_cookie($event, $event_data);
    }

    /**
     * Слушатель события nc_netshop::EVENT_AFTER_ORDER_CREATED
     *
     * @param nc_netshop_order $order
     * @param array $coupon_codes
     */
    public function on_netshop_checkout(nc_netshop_order $order, array $coupon_codes = array()) {
        $site_id = $order->get_catalogue_id();

        $items = array();
        foreach ($order->get_items() as $item) {
            $items[] = $this->get_ecommerce_item_data($item);
        }

        $event_data = array(
            'event_category' => 'ecommerce', // дефолтная категория
            'transaction_id' => 'site' . $site_id . '_' . $order->get_id(),
            'affiliation' => nc_netshop::get_instance($site_id)->get_setting('ShopName'),
            'value' => $order->get_totals(),
            'shipping' => $order['DeliveryCost'] ?: 0,
            'currency' => $this->get_ecommerce_currency_code(),
            'coupon' => join(', ', $coupon_codes),
            'items' => $items,
        );

        $this->set_ecommerce_cookie('purchase', $event_data);
    }

    /**
     * Возвращает данные о событии «просмотр страницы товара», если это сейчас
     * происходит просмотр такой страницы
     *
     * @param nc_netshop $netshop
     * @return array|false
     */
    protected function get_ecommerce_view_item_event_data(nc_netshop $netshop) {
        if ($GLOBALS['action'] !== 'full' || empty($GLOBALS['resMsg'])) {
            return false;
        }

        // берём данные из resMsg, чтобы не загружать информацию о товаре повторно...
        try {
            $component_id = nc_core::get_object()->sub_class->get_by_id($GLOBALS['resMsg']['Sub_Class_ID'], 'Class_ID');
        } catch (Exception $e) {
            return false;
        }

        if (!in_array($component_id, $netshop->get_goods_components_ids())) {
            return false;
        }

        return array('view_item', array(
            'event_category' => 'engagement', // дефолтная категория
            'items' => array(
                $this->get_ecommerce_item_data(new nc_netshop_item($GLOBALS['resMsg']))
            )
        ));
    }

}
