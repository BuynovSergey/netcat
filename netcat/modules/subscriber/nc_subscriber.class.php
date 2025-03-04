<?php

/**
 * Основной класс для работы
 * с модулем подписок
 *
 */
class nc_subscriber {

    // nc_core и db
    protected $core, $db;
    // имя классификатора
    protected $period_table;
    // массив с рассылками
    protected $mailing_lists;
    // массив с подписками пользователя
    protected $user_subscriptions;
    // подписки пользователя на сообщения
    protected $user_message_subscriptions;
    // объект со вспомогательными функциями
    public $tools;

    /**
     * Конструктор
     */
    protected function __construct() {
        // объекты других классов
        $this->core = nc_Core::get_object();
        $this->db = $this->core->db;
        $this->tools = nc_subscriber_tools::get_object();

        // имя классификатора с периодами
        $this->period_table = 'SubscriberPeriod';
        // массив с рассылками и подписками
        $this->mailing_lists = array();
        $this->user_subscriptions = array();

        //прикручивание событий
        $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_CREATED => 'add_message'));
        $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_UPDATED => 'update_message'));
        $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_ENABLED => 'check_message'));
        $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_DISABLED => 'check_message'));
        $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_DELETED => 'drop_message'));
        $this->core->event->bind($this, array(nc_Event::AFTER_INFOBLOCK_DELETED => 'drop_sub_class'));
        $this->core->event->bind($this, array(nc_Event::AFTER_USER_DELETED => 'drop_user'));
    }

    /**
     * Get or instance self object
     *
     * @example $nc_subscriber = nc_subscriber::get_object();
     * @return nc_subscriber object
     */
    public static function get_object() {
        // call as static
        static $storage;
        // check inited object
        if (!isset($storage)) {
            // init object
            $storage = new self();
        }
        // return object
        return is_object($storage) ? $storage : false;
    }

    /**
     * Добавляет объект в рассылку
     *
     * @param int $cc номер компонента в разделе
     * @param int $message номер объекта
     * @param int $rel_cc номер сс и
     * @param int $rel_message объекта, на который пользователь должен быть подписан, чтобы получить в рассылку
     * объект cc - message
     * @return int
     * @throws ExceptionDB
     * @throws ExceptionParam
     */
    public function new_message($cc, $message, $rel_cc = 0, $rel_message = 0) {
        // валидация
        $cc = intval($cc);
        $message = intval($message);
        $rel_cc = intval($rel_cc);
        $rel_message = intval($rel_message);

        if (!$cc || !$message) {
            throw new ExceptionParam(__CLASS__ . '::' . __FUNCTION__);
        }

        $ex_id = $this->db->get_var("SELECT `ID` FROM `Subscriber_Message`
                                 WHERE `Sub_Class_ID` = '" . $cc . "' AND `Message_ID` = '" . $message . "'
                                 AND `Rel_Sub_Class_ID` = '" . $rel_cc . "' AND `Rel_Message_ID` = '" . $rel_message . "' ");

        if ($ex_id) {
            $this->db->query("UPDATE `Subscriber_Message` SET `Date` = CURRENT_TIMESTAMP WHERE `ID` = '" . $ex_id . "' ");
            return 0;
        }

        $this->db->last_error = '';
        $this->db->query("INSERT INTO `Subscriber_Message`(`Sub_Class_ID`, `Message_ID`, `Rel_Sub_Class_ID`, `Rel_Message_ID`)
                      VALUES('" . $cc . "', '" . $message . "', '" . $rel_cc . "', '" . $rel_message . "') ");
        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }

        return 0;
    }

    /**
     * Подписывает пользователя на рассылку. Может создать псевдопользователя.
     * Если подписка требует подтверждения - отправляет письмо
     *
     * @param int $mailer_id номер рассылки
     * @param int $user_id номер пользователя.
     *        0 - текущий пользователь. Если нет и его - создаётся псевдопользователь.
     *       -1 - создать псевдопользователя игнорируя текущего
     * @param int $period - период рассылки, если false - то берется по умолчанию
     * @param bool|string $status - статус (on, off, wait). Если false - берётся по умолчанию
     * @param array $fields - поля для псевдопользователя вида array('Email'=>$email; 'Name'=> $name)
     * @param int $message - номер объекта, на который нужно подписаться
     * @return bool|string <mixed> false - неудачно. При подписке - возвращает статус
     * @throws Exception
     * @throws ExceptionDB
     * @throws ExceptionEmail
     * @throws ExceptionMailer
     * @throws ExceptionParam
     * @throws nc_Exception_Subscriber_AlreadySubscribe
     * @global int $AUTH_USER_ID
     */
    public function subscription_add($mailer_id, $user_id = 0, $period = 0, $status = false, $fields = array(), $message = 0) {
        $mailer_id = intval($mailer_id);
        // получение информации о рассылке
        $mailer = $this->get($mailer_id);
        // валидация объекта
        $message = intval($message);
        // проверка пользователя
        $user_id = intval($user_id);
        // берем текущего пользователя
        if ($user_id == 0) {
            global $AUTH_USER_ID;
            $user_id = intval($AUTH_USER_ID);
        }
        // текущий пользователь не авторизован или игнорируется, нужно создать псевдопользователя
        if ($user_id <= 0) {
            // если рассылка доступна не всем, либо нет модуля - ИП, то подписаться невозможно
            if ($mailer['Access'] > 1 || !$this->core->modules->get_by_keyword('auth')) {
                throw new Exception(NETCAT_MODERATION_ERROR_NORIGHT);
            }
            // проверка пользователя
            $email = $fields[$this->tools->get_settings('EmailField')];
            if (!$email || !preg_match("/^[a-z0-9\._-]+@[a-z0-9\._-]+\.[a-z]{2,4}\$/i", $email)) {
                throw new ExceptionEmail();
            }
            $nc_auth = nc_auth::get_object();
            // раздел управления подписками
            $control_sub = $this->tools->get_subscribe_sub('Subdivision_ID');
            // создаем псевдопользователя
            $user = $nc_auth->hash->add_pseudo_user($fields, $control_sub);
            // переприсвоение номера пользователя
            $user_id = $user['User_ID'];
        }
        // проверка пользователя
        $this->tools->check_user($user_id);

        // нельзя подписаться на одну и ту же рассылку более чем один раз  (особо обрабатывается подписка на объект)
        $sbs_exist = $this->db->get_var("SELECT `ID` FROM `Subscriber_Subscription` WHERE `User_ID` = '" . $user_id . "' AND `Mailer_ID` = '" . $mailer_id . "' ");
        if ($sbs_exist && ($mailer['Type'] != 1 || !$message)) {
            throw new nc_Exception_Subscriber_AlreadySubscribe($user_id);
        }


        if ($mailer['Type'] == 1 && $message) {
            $msbs_exist = $this->db->get_var("SELECT `ID` FROM `Subscriber_UserMessage`
                                       WHERE `Mailer_ID` = '" . $mailer_id . "'
                                       AND `Message_ID` = '" . $message . "'
                                       AND `User_ID` = '" . $user_id . "'");
            if ($msbs_exist) {
                throw new nc_Exception_Subscriber_AlreadySubscribe($user_id);
            }
            $this->db->query("INSERT INTO `Subscriber_UserMessage`(`User_ID`, `Sub_Class_ID`, `Message_ID`, `Mailer_ID`)
                        VALUES ('" . $user_id . "','" . $mailer['Sub_Class_ID'] . "','" . $message . "', '" . $mailer_id . "')");

            if (isset($this->user_message_subscriptions[$user_id])) {
                $this->user_message_subscriptions[$user_id][] = array(
                    "Sub_Class_ID" => $mailer['Sub_Class_ID'],
                    "Message_ID" => $message,
                );
            }

            if ($sbs_exist) {
                return "on";
            }
        }


        // проверка period
        $period = intval($period);
        if ($period) {
            $this->tools->check_period($period);
        } else { // период по умолчанию
            $period = $this->tools->get_default_period();
        }

        // проверка статуса
        if ($status) {
            if (!in_array($status, array('on', 'off', 'wait'))) {
                throw new ExceptionParam(__CLASS__ . '::' . __FUNCTION__, 'status');
            }
        } else {
            $status = $this->tools->get_default_status($user_id);
        }

        // формирование ссылки для подтверждения / удаления подписки
        $link = md5(rand(0, 100) . $status . "Netcat" . time() . $user_id) . $user_id . "-" . $mailer['Mailer_ID'];
        $link = $this->db->escape($link);

        // собственно, добавление подписки
        $this->db->last_error = '';
        $this->db->insert_id = 0;

        $this->db->query("INSERT INTO `Subscriber_Subscription`(`User_ID`, `Mailer_ID`, `Period`, `Status`, `Hash`, `Created`)
                VALUES ('" . $user_id . "','" . $mailer['Mailer_ID'] . "','" . $period . "','" . $status . "', '" . $link . "', \"" . date("Y-m-d H:i:0") . "\")");
        // если возникла ошибка sql - генерируем исключение
        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__ . ': ' . $this->db->last_error);
        }

        $subscr_id = $this->db->insert_id;
        if (!$subscr_id) {
            return false;
        }

        // сохраним информацию о рассылке во внутреннем кэше
        if (isset($this->user_subscriptions[$user_id])) {
            $this->user_subscriptions[$user_id][] = $mailer['Mailer_ID'];
        }

        // посылка письма с подтверждением
        if ($status == 'wait') {
            $this->send_confirm_mail($mailer['Mailer_ID'], $user_id, $link);
        }
        // запись в лог
        $this->tools->log($mailer['Mailer_ID'], $user_id, 'subscribe');

        return $status;
    }

    /**
     * Подписывает пользователя на рассылку, прикреплённую к компоненту в разделе с номером cc
     * Функция вызывает метод subscription_add
     *
     * @param int $cc номер раздела
     * @param int $user_id номер пользователя
     * @param int $period период
     * @param bool $status статус
     * @param array $fields поля для псевдопользователя
     * @param int $message
     * @return mixed false - неудачно. При подписке - возвращает статус
     */
    public function subscription_add_by_cc($cc, $user_id = 0, $period = 0, $status = false, $fields = array(), $message = 0) {
        // проверка сс
        $this->tools->check_cc($cc);

        $mailer = $this->get_mailer_by_cc($cc);
        return $this->subscription_add($mailer['Mailer_ID'], $user_id, $period, $status, $fields, $message);
    }

    /**
     * Перехватчик события "Добавления объекта".
     * Если на сс объекта кто-то подписан, то добавляет объект в рассылку
     *
     * @param int $catalogue
     * @param int $sub
     * @param int $cc
     * @param int $class
     * @param mixed $message
     * @return bool|int
     */
    public function add_message($catalogue, $sub, $cc, $class, $message) {
        $db = $this->db;
        // если передан массив - то функцию вызовем рекурсивно
        if (is_array($message)) {
            if (!empty($message)) {
                foreach ($message as $v) {
                    $this->add_message($catalogue, $sub, $cc, $class, $v);
                }
            }

            return 0;
        }

        $cc = intval($cc);
        $class = intval($class);
        $message = intval($message);

        if (!$cc || !$class || !$message) {
            return false;
        }

        // есть ли хоть один пользователь, подписавшийся на этот сс или компонент
        $action_type = $db->get_var("SELECT `ActionType`
                                 FROM `Subscriber_Mailer` AS `sm`,
                                      `Subscriber_Subscription` AS `ss`
                                 WHERE (sm.`Sub_Class_ID` = '" . $cc . "' OR sm.`Class_ID` = '" . $class . "')
                                 AND `Active` = '1'
                                 AND `Type` = '1'
                                 AND ss.`Mailer_ID` = sm.`Mailer_ID`
                                 AND ss.`Status` = 'on' ");
        // если первый бит ActionType - 1, то это "добавление включенного"
        // если второй бит ActionType - 1, то это "добавление выключенного"
        if ($action_type) {
            // объект включен/выключен ?
            $checked = $db->get_var("SELECT `Checked`
                               FROM `Message" . $class . "`
                               WHERE `Message_ID` = '" . $message . "'");

            if (($checked && $action_type & 1) || (!$checked && $action_type & 2)) {
                // собственно, добавление
                $this->new_message($cc, $message);
            }
        }

        return 0;
    }

    /**
     * Перехватчик события "Обновление объекта".
     * Если на сс объекта кто-то подписан, то добавляет объект в рассылку
     *
     * @param int $catalogue
     * @param int $sub
     * @param int $cc
     * @param int $class
     * @param mixed $message
     * @return bool|int
     */
    public function update_message($catalogue, $sub, $cc, $class, $message) {
        $db = $this->db;
        // если передан массив - то функцию вызовем рекурсивно
        if (is_array($message)) {
            if (!empty($message)) {
                foreach ($message as $v) {
                    $this->update_message($catalogue, $sub, $cc, $class, $v);
                }
            }

            return 0;
        }

        $cc = intval($cc);
        $class = intval($class);
        $message = intval($message);

        if (!$cc || !$class || !$message) {
            return false;
        }

        // есть ли хоть один пользователь, подписавшийся на этот сс или компонент
        $action_type = $db->get_var("SELECT `ActionType`
                                 FROM `Subscriber_Mailer` AS `sm`,
                                      `Subscriber_Subscription` AS `ss`
                                 WHERE (sm.`Sub_Class_ID` = '" . $cc . "'  OR sm.`Class_ID` = '" . $class . "')
                                 AND `Active` = '1'
                                 AND `Type` = '1'
                                 AND ss.`Mailer_ID` = sm.`Mailer_ID`
                                 AND ss.`Status` = 'on' ");

        if ($action_type) {
            // 4 - изменение включенного, 8 - изменение выключенного
            // объект включен/выключен ?
            $checked = $db->get_var("SELECT `Checked`
                               FROM `Message" . $class . "`
                               WHERE `Message_ID` = '" . $message . "'");

            if (($checked && ($action_type & 4)) || (!$checked && ($action_type & 8))) {
                // собственно, добавление
                $this->new_message($cc, $message);
            }
        }

        return 0;
    }

    /**
     * Перехватчик события "Включение/выключение объекта".
     * Если на сс объекта кто-то подписан, то добавляет объект в рассылку
     *
     * @param int $catalogue
     * @param int $sub
     * @param int $cc
     * @param int $class
     * @param mixed $message
     * @return bool|int
     */
    public function check_message($catalogue, $sub, $cc, $class, $message) {
        $db = $this->db;
        // если передан массив - то функцию вызовем рекурсивно
        if (is_array($message)) {
            if (!empty($message)) {
                foreach ($message as $v) {
                    $this->check_message($catalogue, $sub, $cc, $class, $v);
                }
            }

            return 0;
        }

        $cc = intval($cc);
        $class = intval($class);
        $message = intval($message);

        if (!$cc || !$class || !$message) {
            return false;
        }

        // есть ли хоть один пользователь, подписавшийся на этот сс или компонент
        $action_type = $db->get_var("SELECT `ActionType`
                                 FROM `Subscriber_Mailer` AS `sm`,
                                      `Subscriber_Subscription` AS `ss`
                                 WHERE (sm.`Sub_Class_ID` = '" . $cc . "'  OR sm.`Class_ID` = '" . $class . "')
                                 AND `Active` = '1'
                                 AND `Type` = '1'
                                 AND ss.`Mailer_ID` = sm.`Mailer_ID`
                                 AND ss.`Status` = 'on' ");

        if ($action_type) {
            //16 - включение, 32 - выключение
            // объект включен/выключен?
            $checked = $db->get_var("SELECT `Checked`
                               FROM `Message" . $class . "`
                               WHERE `Message_ID` = '" . $message . "'");

            if (($checked && ($action_type & 16)) || (!$checked && ($action_type & 32))) {
                // собственно, добавление
                $this->new_message($cc, $message);
            }
        }

        return 0;
    }

    /**
     * Вызывается при удалении сообщения
     *
     * @param int $catalogue
     * @param int $sub
     * @param int $cc
     * @param int $class
     * @param array $message
     * @return int
     */
    public function drop_message($catalogue, $sub, $cc, $class, $message) {
        if (!is_array($message)) {
            $message = array($message);
        }
        $message = array_map('intval', $message);
        $cc = intval($cc);

        if (!$cc || empty($message)) {
            return 0;
        }

        $this->db->query("DELETE FROM `Subscriber_Message`
                      WHERE
                     (`Sub_Class_ID` = '" . $cc . "'
                      AND `Message_ID` IN (" . join(', ', $message) . ") )
                     OR (`Rel_Sub_Class_ID` = '" . $cc . "'
                     AND `Rel_Message_ID` IN (" . join(', ', $message) . ") ) ");

        $this->db->query("DELETE FROM `Subscriber_UserMessage`
                      WHERE
                     `Sub_Class_ID` = '" . $cc . "'
                      AND `Message_ID` IN (" . join(', ', $message) . ")  ");

        return 0;
    }

    public function drop_sub_class($catalogue, $sub, $cc) {
        if (!is_array($cc)) {
            $cc = array($cc);
        }


        if (empty($cc)) {
            return false;
        }

        $this->db->query("DELETE FROM `Subscriber_Message`
                      WHERE `Sub_Class_ID` IN (" . join(', ', $cc) . ") OR `Rel_Sub_Class_ID` IN (" . join(', ', $cc) . ") ");

        $this->db->query("DELETE FROM `Subscriber_UserMessage`
                      WHERE `Sub_Class_ID` IN (" . join(', ', $cc) . ")  ");

        $mailers_id = $this->db->get_col("SELECT `Mailer_ID` FROM `Subscriber_Mailer`
                      WHERE `Sub_Class_ID`IN (" . join(', ', $cc) . ")  AND `Type` = '1' ");

        if (!empty($mailers_id)) {
            $this->delete($mailers_id);
        }
    }

    public function drop_user($user_ids) {
        if (!is_array($user_ids)) {
            $user_ids = array($user_ids);
        }

        $this->db->query("DELETE FROM `Subscriber_Log` WHERE `User_ID` IN (" . join(',', $user_ids) . ")");
        $this->db->query("DELETE FROM `Subscriber_Subscription` WHERE `User_ID` IN (" . join(',', $user_ids) . ")");
        $this->db->query("DELETE FROM `Subscriber_UserMessage` WHERE `User_ID` IN (" . join(',', $user_ids) . ")");

        if ($this->core->modules->get_by_keyword('auth')) {
            $nc_auth = nc_auth::get_object();
            $c_sub = $this->tools->get_subscribe_sub('Subdivision_ID');
            $users = $this->db->get_results("SELECT `User_ID`, `UserType` FROM `User` WHERE `User_ID` IN (" . join(',', $user_ids) . ")", ARRAY_A);

            if (!empty($users)) {
                foreach ($users as $v) {
                    if ($v['UserType'] == 'pseudo') {
                        $nc_auth->hash->delete_from_sub($c_sub, $v['User_ID']);
                    }
                }
            }
        }
    }

    /**
     * Функция возвращает информацию о рассылке по сс, к которому он прикручен
     * если не найдена рассылка по cc, то ищется рассылка по компоненту этого инфоблока
     *
     * @param int $cc номер компонента в разделе
     * @param string $item необходимый параметр
     * @return mixed параметр или массив с параметрами
     * @throws ExceptionDB
     * @throws ExceptionMailer
     * @throws ExceptionParam
     */
    public function get_mailer_by_cc($cc, $item = '') {
        // проверка сс
        $cc = intval($cc);
        if (!$cc) {
            throw new ExceptionParam(__CLASS__ . '::' . __FUNCTION__, 'cc');
        }
        $class_id = nc_core('sub_class')->get_by_id($cc, 'Class_ID');
        // поиск в кэше рассылки для данного сс
        $mailer_id = 0;
        $mailer_cc_id = $mailer_class_id = null;
        if (!empty($this->mailing_lists)) {
            foreach ($this->mailing_lists as $v) {
                if (($v['Type'] == 1 || $v['Type'] == 4) && $v['Suv_Class_ID'] == $cc) {
                    $mailer_cc_id = $v['Mailer_ID'];
                }
                if (($v['Type'] == 1 || $v['Type'] == 4) && $class_id && $v['Class_ID'] == $class_id) {
                    $mailer_class_id = $v['Mailer_ID'];
                }
            }
        }
        //если есть подписка на инфоблок, то у нее приоритет выше чем у подписки на компонент
        $mailer_id = $mailer_cc_id ? $mailer_cc_id : $mailer_class_id;

        // если в кэше нет - надо взять из БД
        if (!$mailer_id) {
            $mailer_temp_cc = $this->db->get_row("SELECT * FROM `Subscriber_Mailer`
                        WHERE `Sub_Class_ID` = '" . $cc . "' AND (`Type` = '1' OR `Type` = 4)", ARRAY_A);
            $mailer_temp_class = $this->db->get_row("SELECT * FROM `Subscriber_Mailer`
                            WHERE `Class_ID` = '" . $class_id . "' AND(`Type` = '1' OR `Type` = 4)", ARRAY_A);

            //если есть подписка на инфоблок, то у нее приоритет выше чем у подписки на компонент
            $mailer_temp = !empty($mailer_temp_cc) ? $mailer_temp_cc : $mailer_temp_class;

            // если нет результата - то рассылки не существует
            if (empty($mailer_temp)) {
                throw new ExceptionMailer();
            }

            //при подписке на объект понадобится Class_ID
            if ($mailer_temp['Class_ID']) {
                $mailer_temp['Sub_Class_ID'] = $cc;
            }

            $mailer_id = $mailer_temp['Mailer_ID'];
            // и записать в данные класса
            $this->mailing_lists[$mailer_id] = $mailer_temp;
        }

        return $this->get($mailer_id, $item);
    }

    /**
     * Функция возвращает информацию о рассылке по ее номеру
     *
     * @param int $mailer_id номер рассылки
     * @param string $item
     * @return mixed параметр или массив с параметрами
     * @throws ExceptionDB
     * @throws ExceptionMailer
     * @throws ExceptionParam
     * @internal param $ <string $item необходимый параметр
     */
    public function get($mailer_id, $item = '') {
        // проверка номера рассылки
        $mailer_id = intval($mailer_id);
        if (!$mailer_id) {
            throw new ExceptionParam(__CLASS__ . '::' . __FUNCTION__, 'mailer_id');
        }

        // если в кэше такой рассылки нет - то нужно взять из БД
        if (empty($this->mailing_lists[$mailer_id])) {
            $this->db->last_error = '';
            $this->mailing_lists[$mailer_id] = $this->db->get_row("SELECT * FROM `Subscriber_Mailer`
                        WHERE `Mailer_ID` = '" . $mailer_id . "'", ARRAY_A);
            if ($this->db->last_error) {
                throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
            }
        }

        // рассылки не существует
        if (empty($this->mailing_lists[$mailer_id])) {
            throw new ExceptionMailer();
        }
        // возвращаем значения
        if ($item) {
            return $this->mailing_lists[$mailer_id][$item];
        }
        return $this->mailing_lists[$mailer_id];
    }

    /**
     * Функция для включения рассылок
     *
     * @param mixed $mailer_ids номер рассылки или массив с номерами
     * @return int количество изменённых рассылок
     */
    public function activate($mailer_ids) {
        return $this->change_status($mailer_ids, 1);
    }

    /**
     * Функция для выключения рассылок
     *
     * @param mixed $mailer_ids номер рассылки или массив с номерами
     * @return int количество изменённых рассылок
     */
    public function unactivate($mailer_ids) {
        return $this->change_status($mailer_ids, 0);
    }

    /**
     * Функция для изменения статуса рассылок
     * По существу только изменяет поле Active в таблице с рассылками
     *
     * @param mixed $mailer_ids номер рассылки или массив с номерами
     * @param bool $active / true - включить рассылки. false - выключить
     * @return int количество изменённых рассылок
     * @throws ExceptionDB
     */
    public function change_status($mailer_ids, $active) {
        if (!is_array($mailer_ids)) {
            $mailer_ids = array($mailer_ids);
        }
        if (empty($mailer_ids)) {
            return 0;
        }
        foreach ($mailer_ids as $k => $mailer_id) {
            $mailer_ids[$k] = intval($mailer_id);
        }
        $mailer_ids = array_unique($mailer_ids);

        $active = $active ? 1 : 0;

        $this->db->last_error = '';
        $this->db->rows_affected = 0;

        $this->db->query("UPDATE `Subscriber_Mailer` SET `Active` ='" . $active . "'
                      WHERE `Mailer_ID` IN (" . join(', ', $mailer_ids) . ") ");
        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }

        return $this->db->rows_affected;
    }

    /**
     * Функция для удаления рассылок.
     * Так же удаляет всю информацию о рассылках из соседних таблиц
     *
     * @param mixed $mailer_ids номер рассылки или массив с номерами
     * @return int количество удалённых рассылок
     * @throws ExceptionDB
     */
    public function delete($mailer_ids) {
        if (!is_array($mailer_ids)) {
            $mailer_ids = array($mailer_ids);
        }
        if (empty($mailer_ids)) {
            return 0;
        }
        foreach ($mailer_ids as $k => $mailer_id) {
            $mailer_ids[$k] = intval($mailer_id);
        }
        $mailer_ids = array_unique($mailer_ids);
        $this->db->last_error = '';

        $this->db->query("DELETE FROM  `Subscriber_Subscription`  WHERE `Mailer_ID` IN (" . join(', ', $mailer_ids) . ") ");
        $this->db->query("DELETE FROM  `Subscriber_Log`  WHERE `Mailer_ID` IN (" . join(', ', $mailer_ids) . ") ");
        $this->db->query("DELETE FROM  `Subscriber_UserMessage`  WHERE `Mailer_ID` IN (" . join(', ', $mailer_ids) . ") ");
        $this->db->query("DELETE FROM  `Permission`  WHERE `Catalogue_ID` IN (" . join(', ', $mailer_ids) . ") AND `AdminType` = '" . SUBSCRIBER . "'");
        $this->db->query("DELETE FROM  `Subscriber_Mailer`  WHERE `Mailer_ID` IN (" . join(', ', $mailer_ids) . ") ");

        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }
        // + псевдопользователи
        return $this->db->rows_affected;
    }

    public function add($params) {

        $params = $this->_prepare_mailer_data($params);

        //set default data
        $params['Type'] = (isset($params['Type']) && in_array($params['Type'], array(1, 2, 3, 4))) ? $params['Type'] : 1;
        $params['Access'] = (isset($params['Access']) && in_array($params['Access'], array(1, 2, 3))) ? $params['Access'] : 1;
        $params['Active'] = isset($params['Active']) ? $params['Active'] : 1;

        // для подписок на раздел и серийной нужно проверить сс
        if (($params['Type'] == 1 && !$params['Class_ID']) || $params['Type'] == 4) {
            $this->tools->check_cc(nc_array_value($params, 'Sub_Class_ID'), $params['Type']);
        } // для остальных - период
        else {
            // проверка period
            if ($params['Period']) {
                $this->tools->check_period($params['Period']);
            } else { // период по умолчанию
                $params['Period'] = $this->tools->get_default_period();
            }
        }

        foreach ($params as $key => $val) {
            $query_insert[] = "`" . $key . "`";
            $query_values[] = "'" . $val . "'";
        }

        $this->db->last_error = '';
        $this->db->query("INSERT INTO `Subscriber_Mailer`
                     (" . join(', ', $query_insert) . " )
                     VALUES (" . join(', ', $query_values) . ") ");

        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }

        return $this->db->insert_id;
    }

    public function update($mailer_id, $params = array()) {
        $mailer_id = intval($mailer_id);
        // проверка на существование рассылки, если ее нет, то get выброит исключение
        $mailer = $this->get($mailer_id);

        $values = $this->_prepare_mailer_data($params);

        // для "подписки на раздел" и "серийной" нужно проверить сс
        if (($values['Type'] == 1 && !$values['Class_ID']) || $values['Type'] == 4) {
            $this->tools->check_cc(nc_array_value($values, 'Sub_Class_ID'), $values['Type']);
        }
        // для сервисной - период
        if ($values['Type'] == 3) {
            if ($values['Period']) {
                $this->tools->check_period($values['Period']);
            } else {
                if (!$mailer['Period']) {
                    $values['Period'] = $this->tools->get_default_period();
                }
            }
        }

        $query = "UPDATE `Subscriber_Mailer` SET ";
        foreach ($values as $key => $value) {
            $query .= "  `" . $key . "` = '" . $value . "', ";
        }
        $query .= "`Mailer_ID` = `Mailer_ID` ";
        $query .= "WHERE `Mailer_ID` = '" . $mailer_id . "' ";

        $this->db->last_error = '';
        $this->db->query($query);

        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }

        return 1;
    }

    protected function _prepare_mailer_data($params) {
        // числовые поля
        $keys_int = array('Type', 'Sub_Class_ID', 'Active', 'Access', 'Period',
            'InStat', 'ActionType', 'HTML', 'Class_ID');
        // текстовые поля
        $keys_text = array('Name', 'SubscribeCond', 'SendCond', 'SubscribeAction',
            'Header', 'Footer', 'Record', 'Subject', 'PeriodMask');
        // массив ключ->значение, которые нужно обновить
        $values = array();
        foreach ($keys_int as $key) {
            if (isset($params[$key])) {
                $values[$key] = intval($params[$key]);
            }
        }
        foreach ($keys_text as $key) {
            if (isset($params[$key])) {
                $values[$key] = $this->db->prepare($params[$key]);
            }
        }

        return $values;
    }

    /**
     * Функция отсылает письмо пользователю для подтверждения им подписки
     *
     * @param int $mailer_id номер рассылки
     * @param int $user_id номер пользователя
     * @return int
     * @throws Exception
     * @throws ExceptionDB
     * @throws ExceptionMailer
     * @throws ExceptionParam
     * @global array $current_user массив с информацией о текущем пользователе
     *
     * @todo для получения пользователя использовать nc_core->users->get_by_id
     * @todo проверка на существование рассылки
     *
     */
    public function send_confirm_mail($mailer_id, $user_id) {
        // проверка рассылки и пользователя
        $mailer_id = intval($mailer_id);
        $user_id = intval($user_id);
        if (!$mailer_id || !$user_id) {
            throw new ExceptionParam(__CLASS__ . '::' . __FUNCTION__);
        }
        global $current_user;
        $this->tools->check_user($user_id);
        //nc_core->users->get_by_id
        // если пользователь текущий - то инфу надо взять из массива $current_user
        if ($current_user['User_ID'] == $user_id) {
            $cur_user = $current_user;
        } else {
            $cur_user = $this->db->get_row("SELECT * FROM `User` WHERE `User_ID` = '" . $user_id . "'", ARRAY_A);
        }

        // шаблон письма возьмем из рассылки
        $mail_subject = $this->tools->get_settings('ConfirmSubject');
        $mail_body = $this->tools->get_settings('ConfirmBody');
        $is_html = $this->tools->get_settings('ConfirmHTML');
        // замена макропеременных
        $macro = array('%MAILER_NAME%' => $this->get($mailer_id, 'Name'));
        $mail_body = $this->replace_macrovariables($mail_body, $cur_user, $mailer_id, 1, $macro);
        $mail_subject = $this->replace_macrovariables($mail_subject, $cur_user, $mailer_id, 0, $macro);
        //отправка письма
        $nc_send = nc_subscriber_send::get_object();
        $nc_send->send_mail($cur_user[$this->tools->get_settings('EmailField')], $mail_body, $mail_subject, $is_html, array(), 'subscriber_confirm' . nc_core('catalogue')->id());

        return 0;
    }

    /**
     * Функция подтверждает подписки по заданным номерам.
     * Так же все остальные подписки пользователя(ей) становятся подтверждёнными.
     * Хэш для удаления/подтверждения подписки изменится
     *
     * @param array $ids номер/номера подписок
     * @return bool|int
     * @throws ExceptionDB
     */
    public function subscription_confirm($ids) {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        if (empty($ids)) {
            return false;
        }

        foreach ($ids as $k => $v) {
            $ids[$k] = intval($v);
        }

        $this->db->last_error = '';
        // получим всех пользователей
        $users_id = $this->db->get_col("SELECT `User_ID`
                                     FROM`Subscriber_Subscription`
                                     WHERE `ID` IN (" . join(', ', $ids) . ") ");
        if (empty($users_id)) {
            return 0;
        }
        // подтверждение подписки и замена кода
        $this->db->query("UPDATE `Subscriber_Subscription`
                      SET `Status` = 'on', `Hash` = CONCAT(`Hash`, 'delete')
                      WHERE `User_ID` IN (" . join(', ', $users_id) . ")
                      AND `Status` = 'wait'");
        // для возможной записи в лог нужно узнать id рассылок
        $res = $this->db->get_results("SELECT `Mailer_ID`, `User_ID`
                             FROM `Subscriber_Subscription`
                             WHERE  `ID` IN (" . join(', ', $ids) . ") ", ARRAY_A);

        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }

        if (empty($res)) {
            return false;
        }
        foreach ($res as $v) {
            $this->tools->log($v['Mailer_ID'], $v['User_ID'], 'confirm');
        }
    }

    /**
     * Удаляет подписки пользователя
     *
     * @param mixed $ids номера подписок
     * @return int количество удаленных подписок
     */
    public function subscription_delete($ids) {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        if (empty($ids)) {
            return false;
        }

        foreach ($ids as $k => $v) {
            $ids[$k] = intval($v);
        }

        // получим номера всех рассылок для лога
        $res = $this->db->get_results("SELECT `Mailer_ID`, `User_ID`
                             FROM `Subscriber_Subscription`
                             WHERE  `ID` IN (" . join(', ', $ids) . ") ", ARRAY_A);

        if (!empty($res)) {
            foreach ($res as $v) {
                $this->tools->log($v['Mailer_ID'], $v['User_ID'], 'unsubscribe');
                $this->db->query("DELETE FROM `Subscriber_UserMessage` WHERE `User_ID` = '" . $v['User_ID'] . "' AND `Mailer_ID` = '" . $v['Mailer_ID'] . "'");
            }
        }

        $this->db->query("DELETE FROM `Subscriber_Subscription`  WHERE `ID` IN (" . join(', ', $ids) . ") ");

        return $this->db->rows_affected;
    }

    /**
     * Отписывает пользователя от рассылок
     *
     * @global int $AUTH_USER_ID
     * @param mixed $mailer_ids номер/номера рассылок
     * @param int $user_id номер пользователя
     * @return int количество удалённых подписок
     */
    public function subscription_delete_by_mailer($mailer_ids, $user_id = 0) {
        // если пользователь не задан - то берём текущего
        $user_id = intval($user_id);
        if (!$user_id) {
            global $AUTH_USER_ID;
            $user_id = $AUTH_USER_ID;
        }

        if (!is_array($mailer_ids)) {
            $mailer_ids = array($mailer_ids);
        }
        // если нет параметров, то работу функции нужно прекратить
        if (empty($mailer_ids) || !$user_id) {
            return false;
        }

        foreach ($mailer_ids as $k => $v) {
            $mailer_ids[$k] = intval($v);
            $this->tools->log($mailer_ids[$k], $user_id, 'unsubscribe');
        }
        $this->db->query("DELETE FROM `Subscriber_UserMessage` WHERE `Mailer_ID` IN (" . join(', ', $mailer_ids) . ") AND `User_ID` = '" . $user_id . "'");
        $this->db->query("DELETE FROM `Subscriber_Subscription`  WHERE `Mailer_ID` IN (" . join(', ', $mailer_ids) . ") AND `User_ID` = '" . $user_id . "'");

        return $this->db->rows_affected;
    }

    /**
     *
     * @param mixed $subsc_ids номер/номера подписок
     * @param mixed $status true или 'on' - включает, false или 'off' - выключает
     * @return int количество измененных подписок
     * @throws ExceptionDB
     */
    public function subscription_change_status($subsc_ids, $status) {
        if (!is_array($subsc_ids)) {
            $subsc_ids = array($subsc_ids);
        }
        if (empty($subsc_ids)) {
            return 0;
        }
        foreach ($subsc_ids as $k => $subsc_id) {
            $subsc_ids[$k] = intval($subsc_id);
        }
        $subsc_ids = array_unique($subsc_ids);

        $status = ($status && $status != 'off') ? 'on' : 'off';

        $this->db->last_error = '';
        $this->db->rows_affected = 0;

        $this->db->query("UPDATE `Subscriber_Subscription` SET `Status` ='" . $status . "' WHERE `ID` IN (" . join(', ', $subsc_ids) . ") ");
        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }

        return $this->db->rows_affected;
    }

    /**
     * Функция меняет периоды у подписки
     *
     * @param int $sbs_id номер подписки
     * @param int $period идентификатора периода
     */
    public function subscription_change_period($sbs_id, $period) {
        // проверка входных данных
        $period = intval($period);
        $sbs_id = intval($sbs_id);
        $this->tools->check_period($period);
        // собственно изменение
        $this->db->query("UPDATE `Subscriber_Subscription` SET `Period` = '" . $period . "' WHERE `ID` = '" . $sbs_id . "' ");
    }

    /**
     * Проверка на возможность подписки на рассылку текущего пользователя
     *
     * @global int $AUTH_USER_ID
     * @global array $current_user
     * @global object $perm
     * @param int $mailer_id номер рассылки
     * @return bool. true -подписаться возможно, false - нельзя
     */
    public function check_rights($mailer_id) {
        // тип доступа
        // 1 - всем, 2 - зарегистрированным, 3 - уполномоченным
        $access = $this->get($mailer_id, 'Access');
        if (!$access) {
            return false;
        }
        switch ($access) {
            case 1: // всем
                return true;
            case 2: // зарегистрированным
                global $AUTH_USER_ID, $current_user;
                if ($AUTH_USER_ID && $current_user['UserType'] == 'normal') {
                    return true;
                }
                break;
            case 3: // уполномоченным
                global $perm;
                if ($perm instanceof Permission && $perm->isSubscriber($mailer_id)) {
                    return true;
                }
                break;
            default:
                return false;
        }

        return false;
    }

    public function get_user_subscription($user_id = 0) {
        // определение номера пользователя
        if (!$user_id) {
            global $AUTH_USER_ID;
            $user_id = $AUTH_USER_ID;
        }
        $user_id = intval($user_id);
        if (!$user_id) {
            return false;
        }
        // если подписки уже были определены
        if (isset($this->user_subscriptions[$user_id])) {
            return $this->user_subscriptions[$user_id];
        }

        // все рассылки, на который подписан пользователь
        $mailers = $this->db->get_results("SELECT sm.*
                                       FROM `Subscriber_Subscription` AS `ss`,
                                       `Subscriber_Mailer` AS `sm`
                                       WHERE sm.`Mailer_ID` = ss.`Mailer_ID`
                                       AND `User_ID` = '" . $user_id . "' ", ARRAY_A);
        $this->user_subscriptions[$user_id] = array();
        if (!empty($mailers)) {
            foreach ($mailers as $v) {
                // id рассылки
                $id = $v['Mailer_ID'];
                // сохраним саму рассылку и подписку пользователя
                $this->mailing_lists[$id] = $v;
                $this->user_subscriptions[$user_id][] = $id;
            }
        }

        return $this->user_subscriptions[$user_id];
    }

    public function is_subscribe_to_cc($cc, $user_id = 0) {
        // проверка сс
        $cc = intval($cc);
        if (!$cc) {
            return false;
        }
        $class_id = nc_core('sub_class')->get_by_id($cc, 'Class_ID');
        // получим все подписки пользователя
        $all_subscribers = $this->get_user_subscription($user_id);

        if (empty($all_subscribers)) {
            return false;
        }

        // поиск нужной подписки
        foreach ($all_subscribers as $v) {
            // номер сс, на который подписан пользователь
            $mailer_cc = $this->get($v, 'Sub_Class_ID');
            $mailer_class_id = $this->get($v, 'Class_ID');
            if ($cc == $mailer_cc || $class_id == $mailer_class_id) {
                return true;
            }
        }

        return false;
    }

    public function is_subscribe_to_message($cc, $message, $user_id = 0) {
        // проверка параметров
        $cc = intval($cc);
        $message = intval($message);
        $user_id = intval($user_id);

        if (!$user_id) {
            global $AUTH_USER_ID;
            $user_id = $AUTH_USER_ID;
        }

        if (!$cc || !$message || !$user_id) {
            return false;
        }

        // если запрос еще не выполнялся - то его надо выполнить
        if (!isset($this->user_message_subscriptions[$user_id])) {
            $this->user_message_subscriptions[$user_id] = (array)$this->db->get_results(
                "SELECT `Sub_Class_ID`, `Message_ID`
                   FROM `Subscriber_UserMessage`
                  WHERE `User_ID` = '" . $user_id . "'",
                ARRAY_A);
        }

        // поиск нужного объекта
        if (!empty($this->user_message_subscriptions[$user_id])) {
            foreach ($this->user_message_subscriptions[$user_id] as $v) {
                if ($cc == $v['Sub_Class_ID'] && $message == $v['Message_ID']) {
                    return true;
                }
            }
        }
        // если ничего не нашлось - то значить пользователь не подписан
        return false;
    }

    /**
     * Функция заменяет в тексте макропеременные на реальные значения
     *
     * @param string $text исходный текст
     * @param array $current_user массив с информацией о пользователе
     * @param int $mailer_id номер рассылки
     * @param int $reset
     * @param array $macro
     * @param int $catalogue
     * @return string текст
     * @throws Exception
     * @throws ExceptionParam
     * @throws ExceptionMailer
     * @throws ExceptionDB
     */
    public function replace_macrovariables($text, $current_user, $mailer_id, $reset = 0, $macro = null, $catalogue = 0) {
        // нужно узнать хэш-ссылки для удаления/подтверждения подписки
        static $links = array();
        if (empty($links) || $reset) {
            $res = $this->db->get_results('SELECT `User_ID`, `Mailer_ID`, `Hash` FROM `Subscriber_Subscription`', ARRAY_A);
            if (!empty($res)) {
                foreach ($res as $v) {
                    $links[$v['User_ID']][$v['Mailer_ID']] = $v['Hash'];
                }
            }
        }
        // раздел, где пользователь может управлять подписками
        static $con_sub = array();
        $cc = $this->get($mailer_id, 'Sub_Class_ID');
        $subscription_site_id = $cc ? $this->core->sub_class->get_by_id($cc, 'Catalogue_ID') : $catalogue;
        $subscription_site_url = $this->core->catalogue->get_url_by_id($subscription_site_id);
        if (empty($con_sub[$mailer_id])) {
            $con_sub[$mailer_id] = $this->tools->get_subscribe_sub('', $subscription_site_id);
            $con_sub[$mailer_id]['url'] = nc_folder_url($con_sub[$mailer_id]['Subdivision_ID']);
        }

        // замена макропеременных
        $text = str_replace('%USER_LOGIN%', $current_user[nc_Core::get_object()->AUTHORIZE_BY], $text);
        $text = str_replace(
            '%LINK%',
            $subscription_site_url .
            nc_module_path('subscriber') . 'confirm.php?hash=' .
            $links[$current_user['User_ID']][$mailer_id],
            $text
        );

        $link = $con_sub[$mailer_id]['url'];

        if (nc_module_check_by_keyword('auth')) {
            $auth_hash = $current_user['Auth_Hash'] ?: nc_auth::get_object()->hash->create_auth_hash($current_user['User_ID']);
            $link_with_auth_hash = $link . '?auth_hash=' . $auth_hash;
        } else {
            $link_with_auth_hash = $link;
        }

        $text = str_replace('%SUB_LINK_WITH_AUTH_HASH%', $link_with_auth_hash, $text);
        $text = str_replace('%SUB_LINK%', ($current_user['UserType'] === 'pseudo' ? $link_with_auth_hash : $link), $text);

        foreach ((array)$macro as $key => $value) {
            $text = str_replace($key, $value, $text);
        }

        return $text;
    }

    public function delete_expire() {
        // время жизни неподтвержденной рассылки в секундах
        $expire_time = $this->tools->get_settings('MaxTime') * 60 * 60;
        $sbs_to_delete = $this->db->get_results("SELECT ss.`ID`, u.`User_ID`, u.`UserType`
                                             FROM `Subscriber_Subscription` AS `ss`,
                                                  `User` AS `u`
                                             WHERE u.`User_ID` = ss.`User_ID`
                                             AND `Status` = 'wait'
                                            AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(ss.`Created`)) >= '" . $expire_time . "' ", ARRAY_A);
        // удаление непотвержденных рассылок
        if (!empty($sbs_to_delete)) {
            $c_sub = $this->tools->get_subscribe_sub('Subdivision_ID');
            foreach ($sbs_to_delete as $v) {
                $ids[] = $v['ID'];
                if ($v['UserType'] == 'pseudo' && $this->core->modules->get_by_keyword('auth')) {
                    $nc_auth = nc_auth::get_object();
                    $nc_auth->hash->delete_from_sub($c_sub, $v['User_ID']);
                }
            }

            $this->db->query("DELETE FROM `Subscriber_Subscription`  WHERE `ID` IN (" . join(', ', $ids) . ") ");
        }

        // удаление записей из таблицы Subscribe_Message
        // тип поля Value - текст. MAX(`Value`) может выдать неправильный результат
        $all_period = $this->db->get_col("SELECT `Value` FROM `Classificator_" . $this->period_table . "` ");
        $expire_time = 0;
        if (!empty($all_period)) {
            $expire_time = max($all_period);
        }

        $this->db->query("DELETE FROM `Subscriber_Message`
                      WHERE (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`Date`)) >= '" . $expire_time . "' ");


        return 0;
    }

}

class ExceptionParam extends Exception {

    function __construct($a, $param = '') {
        parent::__construct('Incorrect param ' . ($param ? $param : '') . ' in  ' . $a);
    }

}

;

class ExceptionDB extends Exception {

    function __construct($a) {
        global $db, $perm;
        if ($perm instanceof Permission && $perm->isSupervisor()) {
            $m = "SQL error:<br/>" . $db->last_error . "<br/>Query:<br/>" . $db->last_query . "<br/>in " . $a . "<br/>";
        } else {
            $m = "SQL error";
        }
        parent::__construct($m);
    }

}

;

class ExceptionEmail extends Exception {

    function __construct() {
        parent::__construct();
    }

}

;

class ExceptionMailer extends Exception {

    function __construct() {
        parent::__construct(NETCAT_MODULE_SUBSCRIBER_MAILER_DOES_NOT_EXIST);
    }

}

class nc_Exception_Subscriber_AlreadySubscribe extends Exception {

    protected $user_id;

    function __construct($user_id = 0) {
        $this->user_id = intval($user_id);
        parent::__construct(NETCAT_MODULE_SUBSCRIBER_ALREADY_SUBSCRIBE);
    }

    public function get_user() {
        return $this->user_id;
    }

}
