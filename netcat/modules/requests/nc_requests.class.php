<?php

/**
 *
 */
class nc_requests {

    protected $request_component_keyword = 'netcat_module_requests_request';
    protected $default_fields = array('Name', 'Phone', 'Email', 'Comment', 'Item_VariantName');

    /** @var  int */
    protected $site_id;

    /** @var  int */
    protected $request_infoblock_id;

    /** @var bool */
    protected $form_script_added = false;

    /** @var array */
    protected $form_added_init_selectors = array();

    protected $subdivision_form_settings_cache = array();

    /**
     * @param int|null $site_id
     *
     * @return nc_requests
     */
    public static function get_instance($site_id = null) {
        static $instances = array();
        $site_id = (int)$site_id;

        if (!$site_id) {
            $site_id = nc_core::get_object()->catalogue->get_current('Catalogue_ID');
        }

        if (!isset($instances[$site_id])) {
            $instances[$site_id] = new self($site_id);
        }

        return $instances[$site_id];
    }

    /**
     * Возвращает HTML формы для указанного инфоблока.
     *
     * @param $infoblock_id
     * @param string $form_type
     * @param array $parameters
     *
     * @return string
     */
    public static function make_form($infoblock_id, $form_type = 'default', array $parameters = array()) {
        return nc_requests_form::get_instance($infoblock_id, $form_type)->get_form($parameters);
    }

    /**
     * Возвращает HTML кнопки, открывающей форму для указанного инфоблока.
     *
     * @param $infoblock_id
     * @param string $form_type
     * @param array $parameters
     *
     * @return string
     */
    public static function make_form_popup_button($infoblock_id, $form_type = 'default', array $parameters = array()) {
        return nc_requests_form::get_instance($infoblock_id, $form_type)->get_popup_button($parameters);
    }

    /**
     * @param int $site_id
     */
    protected function __construct($site_id) {
        $this->site_id = $site_id;
    }

    /**
     * @return int
     */
    public function get_request_infoblock_id() {
        if (!$this->request_infoblock_id) {
            $this->request_infoblock_id = $this->find_request_infoblock_id() ?: $this->create_request_infoblock();
        }

        return $this->request_infoblock_id;
    }

    /**
     * @return int
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    public function get_request_component_id() {
        return nc_core::get_object()->component->get_by_id($this->request_component_keyword, 'Class_ID');
    }

    /**
     * @return int
     */
    protected function find_request_infoblock_id() {
        return (int)nc_db()->get_var(
            "SELECT `Sub_Class_ID`
               FROM `Sub_Class`
              WHERE `Catalogue_ID` = " . (int)$this->site_id . "
                AND `Class_ID` = '" . $this->get_request_component_id() . "'
              LIMIT 1"
        );
    }

    /**
     * @return int
     */
    protected function create_request_infoblock() {
        $nc_core = nc_core::get_object();

        // Создание раздела
        $subdivision_id = $nc_core->subdivision->create(array(
            'Catalogue_ID' => $this->site_id,
            'Parent_Sub_ID' => 0,
            'Subdivision_Name' => NETCAT_MODULE_REQUESTS,
            'EnglishName' => 'requests',
            'Template_ID' => 0,
            'Checked' => 0,
            'Priority' => 1,
        ));

        // Создание инфоблока
        $infoblock_id = $nc_core->sub_class->create($this->get_request_component_id(), array(
            'Subdivision_ID' => $subdivision_id,
            'Sub_Class_Name' => NETCAT_MODULE_REQUESTS,
            'EnglishName' => 'request',
            'Write_Access_ID' => 1,
        ));

        return $infoblock_id;
    }

    /**
     * Возвращает список полей, которые могут быть показаны в форме
     * (ключ — имя поля в БД, значение зависит от аргумента $property)
     *
     * @param null|string $property если указано, результат будет содержать только
     *   указанное свойство поля, иначе — все поля
     *
     * @return array
     */
    public function get_request_component_visible_fields($property = null) {
        $nc_core = nc_core::get_object();
        $result = array();

        $skipped_fields = $this->get_request_component_auxiliary_fields();

        $requests_component_id = $this->get_request_component_id();
        $all_fields = $nc_core->get_component($requests_component_id)->get_fields();
        foreach ($all_fields as $k => $f) {
            // не показывать служебные
            if ($f['edit_type'] == 1 && $f['name'] != 'Status' && !in_array($f['name'], $skipped_fields)) {
                $result[$f['name']] = $property ? $f[$property] : $f;
            }
        }

        return $result;
    }

    /**
     * Возвращает список полей, которые могут быть показаны в форме по умолчанию
     * (ключ — имя поля в БД, значение зависит от аргумента $property)
     *
     * @param null|string $property если указано, результат будет содержать только
     *   указанное свойство поля, иначе — все поля
     *
     * @return array
     */
    public function get_request_component_default_fields($property = null) {
        $nc_core = nc_Core::get_object();
        $default_fields = array('Name', 'Phone', 'Email', 'Comment', 'Item_VariantName');
        $result = array();

        $class_id = $this->get_request_component_id();
        $fields = $nc_core->get_component($class_id)->get_fields();

        foreach ($fields as $k => $f) {
            if (in_array($f['name'], $default_fields, true)) {
                $result[$f['name']] = $property ? $f[$property] : $f;
            }
        }

        return $result;
    }

    /**
     * Возвращает названия вспомогательных (служебных) полей компонента «Заявки»
     *
     * @return array
     */
    public function get_request_component_auxiliary_fields() {
        return array(
            'Source_Subdivision_ID', 'Source_Infoblock_ID', 'Source_Object_ID',
            'FormType',
            'Item_ID',
            'UTM_Source', 'UTM_Medium', 'UTM_Campaign', 'UTM_Content', 'UTM_Term',
        );


    }

    /**
     * Создаёт заказ на указанный товар
     *
     * @param int $source_subdivision_id
     * @param int $item_id ID записи netcat_page_block_goods_common_data
     * @param $item_quantity
     * @param array $order_properties
     *
     * @return bool|nc_netshop_order
     */
    public function create_netshop_order($source_subdivision_id, $item_id, $item_quantity, array $order_properties) {
        if (!nc_module_check_by_keyword('netshop')) { // отсутствует или не включён модуль netshop
            return false;
        }

        $item_data = nc_subdivision_goods_data::for_subdivision($source_subdivision_id)->get_item_by_id($item_id);
        if (empty($item_data['Item_Component_ID'])) { // нет связки с товаром
            return false;
        }

        $netshop = nc_netshop::get_instance($this->site_id);
        if (!in_array($item_data['Item_Component_ID'], $netshop->get_goods_components_ids())) { // это не товар
            return false;
        }

        $item = nc_netshop_item::by_id($item_data['Item_Component_ID'], $item_data['Item_ID']);

        if (!$item['Subdivision_ID']) { // похоже, что нет товара
            return false;
        }

        // Настройки товара в разделе-источнике (если есть инфоблок компонента netcat_page_block_goods_common_data)
        $item['OriginalPrice'] = $item_data['OriginalPrice'];
        $item['ItemPrice'] = $item_data['ItemPrice'];
        $item['ItemDiscount'] = $item_data['DiscountValue'];
        if ($item_data['DiscountValue']) {
            $item['Discounts'] = array(
                array(
                    'type' => 'item',
                    'name' => NETCAT_MODULE_REQUESTS_ITEM_DISCOUNT,
                    'description' => NETCAT_MODULE_REQUESTS_ITEM_DISCOUNT_DESCRIPTION,
                    'sum' => $item_data['DiscountValue'],
                    'price_minimum' => false,
                ),
            );
        }

        if ($item_quantity) {
            $item['Qty'] = abs($item_quantity);
        }
        $items = new nc_netshop_item_collection(array($item));

        $order_properties['Created'] = strftime('%Y-%m-%d %H:%M:%S');
        $order = $netshop->create_order($order_properties);
        $order->save()->save_items($items, false);

        // Отправка писем о заказе
        $netshop->mailer->checkout($order);

        // Событие для Метрики/Аналитики
        nc_core::get_object()->event->execute(nc_netshop::EVENT_AFTER_ORDER_CREATED, $order, array());

        return $order;
    }

    /**
     * @param $setting
     * @param bool $reload
     *
     * @return mixed
     */
    public function get_setting($setting, $reload = false) {
        $result = nc_core::get_object()->get_settings($setting, 'requests', $reload, $this->site_id);
        if ($result === array()) { // Нет никаких настроек модуля на сайте
            return null;
        }

        return $result;
    }

    /**
     * @param $setting
     * @param $value
     *
     * @return bool
     */
    public function set_setting($setting, $value) {
        return nc_core::get_object()->set_settings($setting, $value, 'requests', $this->site_id);
    }

    /**
     * Высылает уведомления о создании заявки
     *
     * @param $request_id
     *
     * @return bool
     */
    public function send_request_creation_notification($request_id) {
        $request_id = (int)$request_id;
        $request_data = nc_db()->get_row(
            "SELECT * FROM `Message{$this->get_request_component_id()}` WHERE `Message_ID` = '$request_id'",
            ARRAY_A
        );

        if (!$request_data) {
            return false;
        }

        $form = nc_requests_form::get_instance($request_data['Source_Infoblock_ID'], $request_data['FormType']);

        try {
            $this->send_email_notification($form, $request_data);
            $this->send_sms_notification($request_data);

            return true;
        } catch (nc_Exception_Class_Doesnt_Exist $e) {
            return false;
        }
    }

    /**
     * @param nc_requests_form $form
     * @param array $data
     *
     * @return void
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    private function send_email_notification(nc_requests_form $form, array $data) {
        $nc_core = nc_core::get_object();
        $emails = trim($form->get_setting('Subdivision_NotificationEmail'));

        if (!$emails) {
            $emails = trim($this->get_setting('NotificationEmail'));
        }

        if (!$emails) {
            return;
        }

        $emails = preg_split('/[\s,;]+/', $emails);

        try {
            $site_id = $nc_core->subdivision->get_by_id($data['Source_Infoblock_ID'], 'Catalogue_ID');
            $site_domain = $nc_core->catalogue->get_by_id($site_id, 'Domain');
        } catch (Exception $e) {
            $site_domain = $nc_core->catalogue->get_current('Domain');
        }

        if (!$site_domain) {
            $site_domain = $_SERVER['HTTP_HOST'];
        }

        $message_subject = sprintf(NETCAT_MODULE_REQUESTS_NOTIFICATION_EMAIL_SUBJECT, $site_domain,
            $data['FormType']);
        $message_body = $this->generate_notification_message_body($data, $site_domain);

        $from = $nc_core->get_settings('SpamFromEmail') ?: 'no-reply@' . $site_domain;

        foreach ($emails as $email) {
            try {
                $mailer = new nc_mail();
                $mailer->mailbody($message_body);
                $mailer->send($email, $from, $from, $message_subject);
            } catch (Exception $e) {
                trigger_error(get_class($e) . ": " . $e->getMessage(), E_USER_WARNING);
            }
        }
    }

    /**
     * @param $request_data
     * @param $site_domain
     *
     * @return string
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    private function generate_notification_message_body($request_data, $site_domain) {
        $nc_core = nc_core::get_object();

        $result = NETCAT_MODULE_REQUESTS_FORM_TYPE . ": $request_data[FormType]\n";
        $request_fields = $nc_core->get_component($this->get_request_component_id())->get_fields();
        $request_visible_fields = $this->get_request_component_visible_fields();

        foreach ($request_fields as $field) {
            $field_name = $field['name'];

            if (empty($request_data[$field_name]) ||
                (!isset($request_visible_fields[$field_name]) && $field_name != 'Item_ID')
            ) {
                continue;
            }

            if ($field['type'] == NC_FIELDTYPE_BOOLEAN) {
                $result .= "$field[description]: " .
                    ($request_data[$field_name] ? CONTROL_CLASS_CLASS_FORMS_YES : CONTROL_CLASS_CLASS_FORMS_NO) .
                    "\n";
            }
            else if ($field_name == 'Item_ID') {
                $goods_data = nc_subdivision_goods_data::for_subdivision($request_data['Source_Subdivision_ID']);
                if (!$goods_data) {
                    continue;
                }
                $item_data = $goods_data->get_item_by_id($request_data['Item_ID']);

                $item_name = '';
                // (a) товар netshop
                if ($goods_data->are_netshop_items()) {
                    $item = nc_netshop_item::by_id($item_data['Item_Component_ID'], $item_data['Item_ID']);
                    $item_name = trim($item['FullName']);
                }

                // (б) не товар в netshop или не существует?
                if (!$item_name) {
                    $item_name = trim($item_data['Name'] . ' ' . $item_data['VariantName']);
                }

                if ($item_name) {
                    $result .= "$field[description]: $item_name\n";
                }
            }
            else if (strlen(trim($request_data[$field_name]))) {
                $result .= "$field[description]: $request_data[$field_name]\n";
            }
        }

        $result .= "\n" .
            NETCAT_MODULE_REQUESTS_REQUEST_ADMIN_LINK . ": " .
            nc_get_scheme() . "://$site_domain" . $nc_core->ADMIN_PATH .
            "#object.list({$this->get_request_infoblock_id()})\n";

        return $result;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function send_sms_notification(array $data) {
        $nc_core = nc_core::get_object();
        $phones = $this->get_setting('NotificationPhone');

        if (!$phones) {
            return false;
        }

        $site_id = null;

        try {
            $site_id = $nc_core->subdivision->get_by_id($data['Source_Infoblock_ID'], 'Catalogue_ID');
            $site_domain = $nc_core->catalogue->get_by_id($site_id, 'Domain');
        } catch (Exception $e) {
            $site_domain = $nc_core->catalogue->get_current('Domain');
        }

        if (!$site_domain) {
            $site_domain = $_SERVER['HTTP_HOST'];
        }

        try {
            $message = sprintf(NETCAT_MODULE_REQUESTS_NOTIFICATION_EMAIL_SUBJECT, $site_domain, $data['FormType']);

            nc_messaging::get_instance($site_id)->send_requests_message($phones, $message);

            return true;
        } catch (nc_messaging_exception $e) {
            return false;
        }
    }

}
