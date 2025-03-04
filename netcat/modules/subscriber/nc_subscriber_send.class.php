<?php

class nc_subscriber_send {

    protected $users_id;
 // все ID пользователей, кому нужно отправить письма
    protected $_user_send;
    protected $_settings;
    // информация об объектах и соответствующие им поля
    protected $_message_res, $_message_vars;
    protected $_not_send_message;
    protected $_user_message_send;
    protected $_mailer_count;
    protected $_mail_count;
 // количество отправленных писем
    protected $_sbs_id;
 // номера подписок, которые участвовали в рассылке
    protected $db;
    protected $tools;
    protected $core;
    protected $subscriber;

    /**
     * Конструктор
     */
    protected function __construct() {
        $this->core = nc_Core::get_object();
        // db
        $this->db = $this->core->db;

        $this->tools = nc_subscriber_tools::get_object();
        $this->_settings = $this->tools->get_settings();

        $this->subscriber = nc_subscriber::get_object();
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
     * Отправляет письмо
     * Параметры письма берутся из настроек модуля
     * @param string $to кому
     * @param string $body тело письма
     * @param string $subject тема письма
     * @param bool $isHtml html-письмо или нет
     * @param array $files массив с номерами файлов из таблицы Filetable для аттача
     */
    public function send_mail($to, $body, $subject, $isHtml, $files = array(), $attachment_type = '', $catalogue = null) {
        static $filetable;

        if ($catalogue) {
            $settings = $this->tools->get_settings('', 0, $catalogue);

        }

        if (empty($settings) || !$catalogue) {
            $settings = $this->_settings;
        }

        $nc_core = nc_core::get_object();
        $nc_core->mail->setCharset($settings['Charset'] ? $settings['Charset'] : MAIN_EMAIL_ENCODING);
        $nc_core->mail->clear();

        //принудительно создаем новый message всегда
        $nc_core->mail->init(true);

        if ($attachment_type) {
            $body = $nc_core->mail->attachment_attach($body, $attachment_type);
        }

        // html-письмо ?
        if ($isHtml) {
            $nc_core->mail->mailbody(strip_tags($body), $body);
        } else {
            $nc_core->mail->mailbody($body);
        }
        // файлы
        if (!empty($files)) {
            foreach ($files as $file_id) {
                // номер файла из Filetable
                $file_id = intval($file_id);
                if (!$file_id) continue;

                if (!$filetable[$file_id]) {
                    $filetable[$file_id] = $this->db->get_row("SELECT `Real_Name`, `Virt_Name`, `File_Type` FROM `Filetable` WHERE `ID` = '".$file_id."' ", ARRAY_A);
                }

                $file = $filetable[$file_id];
                // файла нет
                if (!$file) continue;

                $nc_core->mail->attachFile($this->core->FILES_FOLDER.$file['Virt_Name'], $file['Real_Name'], $file['File_Type']);
            }
        }
        // сообственно, отпарвка
        $nc_core->mail->send($to, $settings['FromEmail'], $settings['ReplyEmail'], $subject, $settings['FromName']);
        // количество отправленных писем
        $this->_mail_count++;

        return 0;
    }

    /**
     * Формирование и отправка писем расслыки "подписка на раздел"
     */
    public function send() {
        // максимальное кол-во писем, которое можно отправить
        $max_mail_count = (int)$this->subscriber->tools->get_settings('MaxMailCount');
        $max_mail_count -= (int)$this->_mail_count; // _mail_count - кол-во уже отправленных писем
        // если превышен лимит - то завершаем работу функции
        if ($max_mail_count <= 0) return 0;

        $this->db->last_error = '';

        // выборка подписок ( пользователь - объект, который нужно отправить) - подписок на раздел
        $query = "SELECT s.`Mailer_ID`, s.`User_ID`, sc.`Sub_Class_ID`, sc.`Class_ID`, m.`Message_ID`, s.`ID`
              FROM `Subscriber_Subscription` AS `s`,
                   `Subscriber_Mailer` AS `sm`,
                   `Sub_Class` AS `sc`,
                   `Classificator_SubscriberPeriod` AS p,
                   `Subscriber_Message` as m,
                   `User` AS `u`
              WHERE s.`Mailer_ID` = sm.`Mailer_ID` ".// соединение по полям
                "AND   (sm.`Sub_Class_ID` = sc.`Sub_Class_ID` OR sm.`Class_ID` = sc.`Class_ID`) ".
                "AND   s.`Period` = p.`SubscriberPeriod_ID` ".
                "AND   sc.`Sub_Class_ID` = m.`Sub_Class_ID` ".
                "AND   s.`User_ID` = u.`User_ID` ".
                "AND   sm.`Type` = 1 ".// выборка рассылок типа "Подписка на раздел"
                "AND   sm.`Active` = 1 ".// выборка активных рассылок
                "AND   s.`Status` = 'on' ".//  включенных подписок пользователя
                "AND   m.`Rel_Sub_Class_ID` = 0 ".// исключаем "подписку на объект"
                "AND   u.`Checked` = 1 ".// только включенным мользователям
                "AND  (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(s.`LastSend`) ) >= p.`Value` ".// учет периоды подисок пользваотеля
                "AND  UNIX_TIMESTAMP(m.`Date`) - UNIX_TIMESTAMP(s.`LastSend`) >= 0 ".// выборка объектов, которы пользвоатель еще не получал
                "AND  UNIX_TIMESTAMP(m.`Date`) - UNIX_TIMESTAMP(s.`Created`) >= 0 ".// пользователь не может поулчить объект, который добавился в очередь раньше, чем он подписался
                "LIMIT ".$max_mail_count; // ограничени по кол-ву писем
        // результат первой выборки
        $subscribers1 = $this->db->get_results($query, ARRAY_A);
        // выборка подписок на объекты
        $query = "SELECT s.`Mailer_ID`, s.`User_ID`, m.`Sub_Class_ID`, sc.`Class_ID`, m.`Message_ID`, s.`ID`
              FROM `Subscriber_Subscription` AS `s`,
                   `Subscriber_Mailer` AS `sm`,
                   `Sub_Class` AS `sc`,
                   `Classificator_SubscriberPeriod` AS p,
                   `Subscriber_Message` as m,
                   `Subscriber_UserMessage` as um,
                   `User` AS `u`
              WHERE s.`Mailer_ID` = sm.`Mailer_ID` ".// соединение по полям
                "AND   sm.`Sub_Class_ID` = um.`Sub_Class_ID` ".
                "AND   s.`Period` = p.`SubscriberPeriod_ID` ".
                "AND   m.`Sub_Class_ID` = sc.`Sub_Class_ID` ".
                "AND   um.`Message_ID` = m.`Rel_Message_ID` ".
                "AND   um.`Sub_Class_ID` = m.`Rel_Sub_Class_ID` ".
                "AND   s.`User_ID`=um.`User_ID` ".
                "AND   s.`User_ID` = u.`User_ID` ".
                "AND   sm.`Type` = 1 ".// выборка рассылок типа "Подписка на раздел"
                "AND   sm.`Active` = 1 ".// выборка активных рассылок
                "AND   s.`Status` = 'on' ".//  включенных подписок пользователя
                "AND   u.`Checked` = 1 ".// только включенным мользователям
                "AND  (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(s.`LastSend`) ) >= p.`Value` ".// учет периоды подисок пользваотеля
                "AND  UNIX_TIMESTAMP(m.`Date`) - UNIX_TIMESTAMP(s.`LastSend`) >= 0 ".// выборка объектов, которы пользвоатель еще не получал
                "AND  UNIX_TIMESTAMP(m.`Date`) - UNIX_TIMESTAMP(s.`Created`) >= 0 ".// пользователь не может поулчить объект, который добавился в очередь раньше, чем он подписался
                "LIMIT ".$max_mail_count; // ограничени по кол-ву писем
        // результат второй рассылки
        $subscribers2 = $this->db->get_results($query, ARRAY_A);

        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__, __FUNCTION__);
        }

        if (empty($subscribers1) && empty($subscribers2)) return 0;

        // объединение рассылок
        if (empty($subscribers1)) {
            $subscribers = $subscribers2;
        } else if (empty($subscribers2)) {
            $subscribers = $subscribers1;
        } else {
            $subscribers = array_merge($subscribers1, $subscribers2);
        }

        // $subscribers - общий массив с рассылками
        // если нет результата
        if (empty($subscribers)) {
            return 0;
        }

        $messages = array(); // messages[Class_ID] = массив с объектами
        $users = array(); // users[user_id][sub_class_id] = массив с объектами. Содержит номера всех объектов, которые нужно отправить пользователю
        $cc_mailer = array(); // cc_mailer[sub_class_id] = номер рассылки

        foreach ($subscribers as $v) {
            $messages[$v['Class_ID']][] = $v['Message_ID'];
            $users[$v['User_ID']][$v['Sub_Class_ID']][] = $v['Message_ID'];
            $cc_mailer[$v['Sub_Class_ID']] = $v['Mailer_ID'];
            $this->users_id[] = $v['User_ID'];
            $this->_sbs_id[] = $v['ID'];
        }

        $this->users_id = array_unique($this->users_id);



        // Получение данных о объектах для каждого класса
        foreach ($messages as $class_id => $v) {
            $res = $this->get_select($class_id);
            // выбор объектов определеннго класса
            $message_select = "SELECT ".$res['select']."
                         FROM (`Message".$class_id."` AS a)
                         ".($res['joins'] ? $res['joins'] : "" )."
                         WHERE `Message_ID` IN (".join(',', $v).") ";
            $result = $this->db->get_results($message_select, ARRAY_N);
            $msg_id_index = $res['msg_id_index'];
            foreach ($result as $k => $v) {
                // хранить удобно в таком виде
                $this->_message_res[$class_id][$v[$msg_id_index]] = $result[$k];
            }
            $this->_message_vars[$class_id] = $res['vars'];

            unset($result);
        }


        // формирование письма каждому подписанному пользователю
        foreach ($users as $user_id => $cc_messages) {
            // пользователь, которому отправляется письмо
            $current_user = $this->get_user($user_id);

            // сс_num - общее кол-во сс, участвующих в рассылке для данного пользователя
            // сс_array - массив с номерами этих сс
            $cc_num = 0;
            $cc_array = array();
            foreach ($cc_messages as $cc => $v) {
                $cc_array[] = $cc;
                $cc_num++;
            }

            // тема письма, футер и хэдер.
            $mail_subject = array();
            $mail_header = array();
            $mail_footer = array();
            // формирование основной части письма
            $cc_i = -1;
            foreach ($cc_messages as $cc => $messages) {
                $cc_i++; // номер сс по порядку
                $available_vars['cc'] = $cc;
                $available_vars['cc_i'] = $cc_i;
                $available_vars['cc_array'] = $cc_array;
                $available_vars['cc_num'] = $cc_num;
                $available_vars['current_user'] = $current_user;

                $mail_not_send[$cc_i] = 0; // по умолчанию письмо отправляем
                // выполняем условие рассылки
                if (!$this->get_eval_template($cc_mailer[$cc], 'SendCond', 0, $available_vars, $mail_not_send[$cc_i])) {
                    $mail_not_send[$cc_i] = 1;
                    continue;
                }
                $mail_subject[$cc_i] = $this->get_eval_template($cc_mailer[$cc], 'Subject', 0, $available_vars, $mail_not_send[$cc_i]);
                $mail_header[$cc_i] = $this->get_eval_template($cc_mailer[$cc], 'Header', 0, $available_vars, $mail_not_send[$cc_i]);
                $mailbody[$cc_i] = '';

                foreach ($messages as $message) {
                    $mailbody[$cc_i] .= $this->get_eval_template($cc_mailer[$cc], 'Record', $message, $available_vars, $mail_not_send[$cc_i]);
                }

                $mail_footer[$cc_i] = $this->get_eval_template($cc_mailer[$cc], 'Footer', 0, $available_vars, $mail_not_send[$cc_i]);
            }

            $catalogue = nc_core('sub_class')->get_by_id($cc_array[0], 'Catalogue_ID');

            // отправляем письма
            // письма можно объединить
            if ($this->_settings['MergeMail']) {
                $m = '';
                for ($i = 0; $i < $cc_num; $i++) {
                    if ($mail_not_send[$i]) continue;
                    $m .= $mail_header[$i].$mailbody[$i].$mail_footer[$i];
                    $this->_mailer_count[$cc_mailer[$cc_array[$i]]]++;
                    $this->_stats_full_update($cc_mailer[$cc_array[$i]], $user_id);
                }
                $m = $this->subscriber->replace_macrovariables($m, $current_user, $cc_mailer[$cc_array[0]], false, null, $catalogue);
                $isHtml = $this->subscriber->get($cc_mailer[$cc_array[0]], 'HTML');
                $this->send_mail($current_user[$this->_settings['EmailField']], $m, $mail_subject[0], $isHtml, array(), 'subscriber_template_' . $cc_mailer[$cc_array[0]], $catalogue);
            }
            else {
                for ($i = 0; $i < $cc_num; $i++) {
                    if ($mail_not_send[$i]) continue;
                    $m = $mail_header[$i].$mailbody[$i].$mail_footer[$i];
                    $m = $this->subscriber->replace_macrovariables($m, $current_user, $cc_mailer[$cc_array[$i]], false, null, $catalogue);
                    $isHtml = $this->subscriber->get($cc_mailer[$cc_array[$i]], 'HTML');
                    $this->send_mail($current_user[$this->_settings['EmailField']], $m, $mail_subject[$i], $isHtml, array(), 'subscriber_template_' . $cc_mailer[$cc_array[$i]], $catalogue);
                    $this->_mailer_count[$cc_mailer[$cc_array[$i]]]++;
                    $this->_stats_full_update($cc_mailer[$cc_array[$i]], $user_id);
                }
            }
        }


        return $this->_mail_count;
    }

    /**
     * Обновляет время старта рассылок которые должны начаться сегодня в текущем часу.
     */
    public function start_periodical_mailers() {
        $mailers = array();

        $day = date('j');
        $day_of_week = date('w');
        $work_day = $day_of_week > 0 && $day_of_week < 6; //будний день
        $first_day = $day == 1;
        $first_day_of_week = $day <= 7;
        if ($first_day_of_week && $work_day && //если этот день недели первый в месяце и он будний
             ($first_day || ($day  <= 3 && $day_of_week == 1))) { //если это первое число или понедельник второго/третьего числа
                $first_work_day = true;

        }
        $days_in_month = date('t');
        $last_day = $day == $days_in_month;
        $days_to_end = $days_in_month - $day;
        $last_day_of_week = $days_to_end <= 6;
        if ($last_day_of_week && $work_day && //если этот день недели последний в месяце и он будний
             ($last_day || ($days_to_end  <= 2 && $day_of_week == 5))) { //если это последнее число или пятница пред и пред-пред последнего числа
                $last_work_day = true;

        }

        //поиск рассылок
        $today[] = $day_of_week;
        $today[] = $day.' day'; //число месяца
        if ($first_day)         $today[] = 'first day';
        if ($last_day)          $today[] = 'last day';
        if ($first_day_of_week) $today[] = 'first '. strtolower(date('D'));
        if ($last_day_of_week)  $today[] = 'last '. strtolower(date('D'));
        if ($first_work_day)    $today[] = 'first wday';
        if ($last_work_day)     $today[] = 'last wday';


        foreach ($today as $key => $value) {
            $serch_query[] = "`PeriodMask` LIKE '%" . $value ." ". date('G'). ",%'";
        }
        $serch_query_str = " (".implode(" OR " , $serch_query).") ";

        //обновим дату только если в текущем часу она не обновлялась
        $res = $this->db->get_results("UPDATE `Subscriber_Mailer` 
                                        SET `LastSend` = NOW() 
                                        WHERE 
                                        `Type` = '2'
                                        AND `Active` = 1
                                        AND (DATE_FORMAT(`LastSend`, '%d-%m-%Y %H') != DATE_FORMAT(NOW(), '%d-%m-%Y %H') OR `LastSend` IS NULL)
                                        AND ".$serch_query_str , ARRAY_A);

        return false;
    }

    public function send_periodical() {
        $max_mail_count = $this->subscriber->tools->get_settings('MaxMailCount');
        $max_mail_count -= $this->_mail_count;
        if ($max_mail_count <= 0) return 0;

        $this->db->last_error = '';

        //запустим рассылки если они сейчас должны начаться, поменяется их время последней рассылки
        $this->start_periodical_mailers();

        $subscribers = $this->db->get_results("SELECT s.`Mailer_ID`, s.`User_ID`, s.`ID`
                                    FROM `Subscriber_Subscription` AS `s`,
                                         `Subscriber_Mailer` AS `sm`,
                                         `User` AS `u`
                                    WHERE s.`Mailer_ID` = sm.`Mailer_ID`
                                    AND   s.`User_ID` = u.`User_ID`
                                    AND   sm.`Type` = 2
                                    AND   sm.`Active` = 1
                                    AND   s.`Status` = 'on'
                                    AND   u.`Checked` = 1 
                                    AND   UNIX_TIMESTAMP(s.`LastSend`) < UNIX_TIMESTAMP(sm.`LastSend`)
                                    AND   UNIX_TIMESTAMP(s.`Created`) <= UNIX_TIMESTAMP(sm.`LastSend`)
                                    LIMIT ".$max_mail_count, ARRAY_A);

        if ($this->db->last_error) {
            throw new Exception($this->tools->db_error(__CLASS__, __FUNCTION__, 1));
        }

        if (empty($subscribers)) return false;

        foreach ($subscribers as $v) {
            $users[$v['User_ID']][] = $v['Mailer_ID'];
            $this->users_id[] = $v['User_ID'];
            $this->_sbs_id[] = $v['ID'];
        }
        $this->users_id = array_unique($this->users_id);


        // формирование письма каждому подписанному пользователю
        foreach ($users as $user_id => $mailers) {
            // пользователь, которому отправляется письмо
            $current_user = $this->get_user($user_id);
            $available_vars['current_user'] = $current_user;

            foreach ($mailers as $mailer_id) {
                if (!$this->get_eval_template($mailer_id, 'SendCond', 0, $available_vars, $mail_not_send)) {
                    continue;
                }

                $mail_subject = $this->get_eval_template($mailer_id, 'Subject', 0, $available_vars, $mail_not_send);
                $mail_header = $this->get_eval_template($mailer_id, 'Header', 0, $available_vars, $mail_not_send);
                $mailbody = $this->get_eval_template($mailer_id, 'Record', 0, $available_vars, $mail_not_send);
                $mail_footer = $this->get_eval_template($mailer_id, 'Footer', 0, $available_vars, $mail_not_send);

                if ($mail_not_send) continue;

                $m = $mail_header.$mailbody.$mail_footer;
                $m = $this->subscriber->replace_macrovariables($m, $current_user, $mailer_id);
                $isHtml = $this->subscriber->get($mailer_id, 'HTML');
                $this->send_mail($current_user[$this->_settings['EmailField']], $m, $mail_subject, $isHtml, array(), 'subscriber_template_' . $mailer_id);
                $this->_mailer_count[$mailer_id]++;
                $this->_stats_full_update($mailer_id, $user_id);
            }
        }

        return $this->_mail_count;
    }

    public function send_prepared() {
        $nc_subscriber = nc_subscriber::get_object();
        $max_mail_count = $nc_subscriber->tools->get_settings('MaxMailCount');
        $max_mail_count -= $this->_mail_count;
        if ($max_mail_count <= 0) return 0;

        $this->db->last_error = '';


        $mails = $this->db->get_results("SELECT `ID`, `Email`, `Subject`, `Body`, `HTML`, `Files`, `Mailer_ID`
                                          FROM `Subscriber_Prepared`
                                          WHERE UNIX_TIMESTAMP(`SendTime`) <=  UNIX_TIMESTAMP()
                                          LIMIT ".$max_mail_count, ARRAY_A);


        if (empty($mails)) return 0;

        foreach ($mails as $mail) {
            $files = explode(',', $mail['Files']);
            if (!$files[0]) $files = array();
            try {
                $this->send_mail($mail['Email'], $mail['Body'], $mail['Subject'], $mail['HTML'], $files, 'subscriber_template_' . $mail['Mailer_ID']);
                $delete_id[] = $mail['ID'];
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
                //del to prevent endless sending
                $this->db->query("DELETE FROM `Subscriber_Prepared` WHERE `ID` IN (".join(',', $delete_id).") ");
            }
        }
        $this->db->query("DELETE FROM `Subscriber_Prepared` WHERE `ID` IN (".join(',', $delete_id).") ");

        // возможно, файл нужно удалить, если он больше нигде не используется
        if (!empty($files)) {
            foreach ($files as $id) {
                $where = "`Files` = '".$id."' OR `Files` LIKE CONCAT('".$id."',',%') OR `Files` LIKE CONCAT('%,','".$id."',',%') OR `Files` LIKE CONCAT('%,','".$id."')";
                $exist = $this->db->get_var("SELECT `ID` FROM `Subscriber_Prepared` WHERE ".$where." LIMIT 1");
                // файл еще используется - удалять не надо
                if ($exist) continue;
                // удаление файла
                $filename = $this->db->get_var("SELECT `Virt_Name` FROM `Filetable` WHERE `ID`='".$id."' ");
                if ($filename) unlink($this->core->FILES_FOLDER.$filename);
                $this->db->query("DELETE FROM `Filetable` WHERE `ID`='".$id."' LIMIT 1");
            }
        }
        return $this->_mail_count;
    }

    public function send_serial() {
        $max_mail_count = $this->subscriber->tools->get_settings('MaxMailCount');
        $max_mail_count -= $this->_mail_count;
        if ($max_mail_count <= 0) return 0;

        $this->db->last_error = '';

        $subscribers = $this->db->get_results("SELECT s.`Mailer_ID`, s.`User_ID`, s.`ID`, s.`Count`, sc.`Class_ID`, sc.`Sub_Class_ID`
                                    FROM `Subscriber_Subscription` AS `s`,
                                         `Subscriber_Mailer` AS `sm`,
                                         `Sub_Class` AS `sc`,
                                         `User` AS `u`
                                    WHERE s.`Mailer_ID` = sm.`Mailer_ID`
                                    AND   sc.`Sub_Class_ID` = sm.`Sub_Class_ID`
                                    AND   s.`User_ID` = u.`User_ID`
                                    AND   sm.`Type` = 4
                                    AND   sm.`Active` = 1
                                    AND   s.`Status` = 'on'
                                    AND   u.`Checked` = 1 
                                    AND  (ISNULL(s.`NextSend`) OR ( UNIX_TIMESTAMP() - UNIX_TIMESTAMP(s.`LastSend`) ) >= s.`NextSend`)
                                    LIMIT ".$max_mail_count, ARRAY_A);

        if ($this->db->last_error) {
            throw new Exception($this->tools->db_error(__CLASS__, __FUNCTION__, 1));
        }

        if (empty($subscribers)) return false;

        $messages = array(); // messages[Class_ID] = массив с объектами
        $users = array(); // users[user_id][sub_class_id] = массив с объектами. Содержит номера всех объектов, которые нужно отправить пользователю
        $cc_mailer = array(); // cc_mailer[sub_class_id] = номер рассылки

        foreach ($subscribers as $v) {
            //получение объекта для отправки, для новой подписки это 0 - первая строка
            $message = $this->db->get_row("SELECT m.`Message_ID`, m.`ncDuration`
                                    FROM `Message".$v['Class_ID']."` AS `m`
                                    WHERE `Sub_Class_ID` = $v[Sub_Class_ID]
                                    ORDER BY m.`Priority` ASC, m.`LastUpdated` ASC
                                    LIMIT ".intval($v['Count']).",1", ARRAY_A);

            if (!$message) continue;
            $messages[$v['Class_ID']][] = $message['Message_ID'];
            $users[$v['User_ID']][$v['Sub_Class_ID']] = $message['Message_ID'];
            $cc_mailer[$v['Sub_Class_ID']] = $v['Mailer_ID'];
            $this->users_id[] = $v['User_ID'];
            $this->_sbs_id[] = $v['ID'];

            //дефолтное время следующей отправки через 24 часа
            $next_send = $message['ncDuration'] ? $message['ncDuration'] * 3600 : 24 * 3600;
            $count = $v['Count'] + 1;
            //запись в подписку счетчика и времени до отправки следующего объекта
            $this->db->query("UPDATE `Subscriber_Subscription` 
                                SET `Count` = '".$count."', `NextSend` = '".$next_send."'
                                WHERE `ID` = '".$v['ID']."'");

        }

        if (empty($this->users_id)) return false;
        $this->users_id = array_unique($this->users_id);


        // Получение данных о объектах для каждого класса
        foreach ($messages as $class_id => $v) {
            $res = $this->get_select($class_id);
            // выбор объектов определеннго класса
            $message_select = "SELECT ".$res['select']."
                         FROM (`Message".$class_id."` AS a)
                         ".($res['joins'] ? $res['joins'] : "" )."
                         WHERE `Message_ID` IN (".join(',', $v).") ";

            $result = $this->db->get_results($message_select, ARRAY_N);
            $msg_id_index = $res['msg_id_index'];
            foreach ($result as $k => $v) {
                // хранить удобно в таком виде
                $this->_message_res[$class_id][$v[$msg_id_index]] = $result[$k];
            }
            $this->_message_vars[$class_id] = $res['vars'];

            unset($result);
        }

        // формирование письма каждому подписанному пользователю
        foreach ($users as $user_id => $cc_messages) {
            // пользователь, которому отправляется письмо
            $current_user = $this->get_user($user_id);

            // тема письма, футер и хэдер.
            $mail_subject = $mail_header = $mail_footer = $mailbody = '';

            // формирование основной части письма, рассылаем по каждому разделу на который пользователь подписан
            foreach ($cc_messages as $cc => $message) {
                $available_vars['cc'] = $cc;
                $available_vars['current_user'] = $current_user;
                $mail_not_send = 0;

                // выполяняем условие рассылки
                if (!$this->get_eval_template($cc_mailer[$cc], 'SendCond', 0, $available_vars, $mail_not_send)) {
                    continue;
                }
                $mail_subject = $this->get_eval_template($cc_mailer[$cc], 'Subject', 0, $available_vars, $mail_not_send);
                $mail_header = $this->get_eval_template($cc_mailer[$cc], 'Header', 0, $available_vars, $mail_not_send);
                $mailbody = $this->get_eval_template($cc_mailer[$cc], 'Record', $message, $available_vars, $mail_not_send);
                $mail_footer = $this->get_eval_template($cc_mailer[$cc], 'Footer', 0, $available_vars, $mail_not_send);

                if ($mail_not_send) continue;

                //отправляем письма
                $m = $mail_header.$mailbody.$mail_footer;
                $m = $this->subscriber->replace_macrovariables($m, $current_user, $cc_mailer[$cc]);
                $isHtml = $this->subscriber->get($cc_mailer[$cc], 'HTML');
                $this->send_mail($current_user[$this->_settings['EmailField']], $m, $mail_subject, $isHtml, array(), 'subscriber_template_' . $cc_mailer[$cc]);
                $this->_mailer_count[$cc_mailer[$cc]]++;
                $this->_stats_full_update($cc_mailer[$cc], $user_id);

            }

        }

        return $this->_mail_count;
    }

    public function formation_service() {
        $limit = 100;
        $begin_user = 0;

        $res = $this->db->get_results("SELECT sm.`Mailer_ID`, p.`Value`
                                    FROM `Subscriber_Mailer` AS `sm`,
                                         `Classificator_SubscriberPeriod` AS p
                                    WHERE sm.`Period` = p.`SubscriberPeriod_ID`
                                    AND   sm.`Type` = 3
                                    AND   sm.`Active` = 1
                                    AND  ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(sm.`LastSend`) ) >=  p.`Value` OR ISNULL(sm.`LastSend`))", ARRAY_A);


        if (empty($res)) return 0;
        $all_hash = $this->db->get_col("SELECT MD5(CONCAT(`Email`, ',', `Body`)) FROM `Subscriber_Prepared`");
        if (!is_array($all_hash)) $all_hash = array();

        while (1) {
            $this->users_id = $this->db->get_col("SELECT `User_ID`
                                              FROM `User`
                                              WHERE `User_ID` > '".$begin_user."'
                                              LIMIT ".$limit." ");
            if (empty($this->users_id)) break;

            $begin_user = max($this->users_id);

            foreach ($this->users_id as $user_id) {
                $current_user = $this->get_user($user_id);
                $available_vars['current_user'] = $current_user;

                foreach ($res as $row) {
                    $mailer_id = $row['Mailer_ID'];

                    // сервисные рассылки с отправкой при добавлении нужно выключить
                    if (!$row['Value']) $mailer_to_uncheck[] = $mailer_id;

                    if (!$this->get_eval_template($mailer_id, 'SendCond', 0, $available_vars, $mail_not_send)) {
                        continue;
                    }
                    $mail_subject = $this->get_eval_template($mailer_id, 'Subject', 0, $available_vars, $mail_not_send);
                    $mail_header = $this->get_eval_template($mailer_id, 'Header', 0, $available_vars, $mail_not_send);
                    $mailbody = $this->get_eval_template($mailer_id, 'Record', 0, $available_vars, $mail_not_send);
                    $mail_footer = $this->get_eval_template($mailer_id, 'Footer', 0, $available_vars, $mail_not_send);

                    if ($mail_not_send) continue;

                    $m = $mail_header.$mailbody.$mail_footer;
                    $m = $this->subscriber->replace_macrovariables($m, $current_user, $mailer_id);
                    $isHtml = $this->subscriber->get($mailer_id, 'HTML');
                    $hash = md5($current_user[$this->_settings['EmailField']].",".$m);
                    if (in_array($hash, $all_hash)) continue;
                    $this->db->query("INSERT INTO `Subscriber_Prepared` ( `Email`, `Subject`, `Body`, `HTML`, `Mailer_ID`)
                          VALUES('".$current_user[$this->_settings['EmailField']]."','".$mail_subject."','".$m."','".$isHtml."', '$mailer_id')");
                    $this->_mailer_count[$mailer_id]++;
                    $this->_stats_full_update($mailer_id, $user_id);
                }
            }

            // сервисные рассылки с отправкой при добавлении нужно выключить
            if (!empty($mailer_to_uncheck)) {
                $mailer_to_uncheck = array_unique($mailer_to_uncheck);
                $this->db->query("UPDATE `Subscriber_Mailer` SET `Active` = '0' WHERE `Mailer_ID` IN (".join(', ', $mailer_to_uncheck).") ");
            }
        }
    }

    public function is_blocked() {
        if (!$this->_settings['Block']) return false;
        // максимум 20 минут на блокировку
        if (time() - $this->_settings['Block'] > 20 * 60) {
            $this->unlock();
            return false;
        }

        return true;
    }

    public function block() {
        $this->core->set_settings('Block', time(), 'subscriber');
    }

    public function unlock() {
        $this->core->set_settings('Block', 0, 'subscriber');
    }

    protected function get_select($class_id) {
        $db = $this->db;
        $class_id = intval($class_id);
        $fields = $db->get_results("SELECT `Field_ID`, `Field_Name`, `TypeOfData_ID`, `Format`
                                FROM `Field`
                                WHERE `Class_ID` = '".$class_id."'
                                ORDER BY `Priority`", ARRAY_A);


        $field_count = $db->num_rows;
        $field_names = "";
        $field_vars = "";
        $convert2txt = "";
        $i = 0;
        $multilist_fileds = array(); //массив с полями типа multiselect
        //$allowTags = $this->core->get_by_id()
        //$allowTags = ${$instance}["AllowTags"];
        //$NL2BR = ${$instance}["NL2BR"];

        if (!empty($fields)) {
            foreach ($fields AS $field_array) {
                // skip OpenID type
                if ($field_array["TypeOfData_ID"] == NC_FIELDTYPE_MULTIFILE) continue;

                $field_name_query = "";
                $field_var = "";

                $field_name[$i] = $field_array["Field_Name"];
                $field_type[$i] = $field_array["TypeOfData_ID"];
                $field_format[$i] = $field_array['Format'];


                switch ($field_type[$i]) {
                    case NC_FIELDTYPE_TEXT:
                        $field_name_query = "a.".$field_array['Field_Name'];
                        $field_var = "\$f_".$field_array['Field_Name'];
                        $format = nc_field_parse_format($field_array['Format'], NC_FIELDTYPE_TEXT);
                        // разрешить тэги?
                        if (!$allowTags && !$format['html'] || $format['html'] == 2)
                                $convert2txt .= "\$f_".$field_name[$i]." = htmlspecialchars(\$f_".$field_name[$i].");";
                        // перено строки - <br>
                        if ($NL2BR && !$format['br'] || $format['br'] == 1)
                                $convert2txt .= "\$f_".$field_name[$i]." = nl2br(\$f_".$field_name[$i].");";
                        unset($format);
                        break;
                    case NC_FIELDTYPE_SELECT:
                        $joins .= " LEFT JOIN `Classificator_".$field_array["Format"]."` AS tbl".$field_array["Field_ID"]." ON a.`".$field_array["Field_Name"]."` = tbl".$field_array["Field_ID"].".".$field_array["Format"]."_ID ";

                        $field_name_query = "tbl".$field_array["Field_ID"].".".$field_array["Format"]."_Name AS tbl".$field_array["Field_ID"]."name";
                        $field_name_query .= ", tbl".$field_array["Field_ID"].".".$field_array["Format"]."_ID AS tbl".$field_array["Field_ID"]."id";
                        $field_name_query .= ", tbl".$field_array["Field_ID"].".`Value` AS tbl".$field_array["Field_ID"]."value";

                        $field_var = "\$f_".$field_array["Field_Name"];
                        $field_var .= ", \$f_".$field_array["Field_Name"]."_id";
                        $field_var .= ", \$f_".$field_array["Field_Name"]."_value";
                        $msg_id_index += 2;
                        break;
                    case NC_FIELDTYPE_DATETIME:
                        $field_name_query = "a.".$field_array["Field_Name"];
                        $field_var = "\$f_".$field_array["Field_Name"];

                        $field_name_query .= ", DATE_FORMAT(a.".$field_array["Field_Name"].",'%Y')";
                        $field_var .= ", \$f_".$field_array["Field_Name"]."_year";
                        $field_name_query .= ", DATE_FORMAT(a.".$field_array["Field_Name"].",'%m')";
                        $field_var .= ", \$f_".$field_array["Field_Name"]."_month";
                        $field_name_query .= ", DATE_FORMAT(a.".$field_array["Field_Name"].",'%d')";
                        $field_var .= ", \$f_".$field_array["Field_Name"]."_day";

                        $field_name_query .= ", DATE_FORMAT(a.".$field_array["Field_Name"].",'%H')";
                        $field_var .= ", \$f_".$field_array["Field_Name"]."_hours";
                        $field_name_query .= ", DATE_FORMAT(a.".$field_array["Field_Name"].",'%i')";
                        $field_var .= ", \$f_".$field_array["Field_Name"]."_minutes";
                        $field_name_query .= ", DATE_FORMAT(a.".$field_array["Field_Name"].",'%s')";
                        $field_var .= ", \$f_".$field_array["Field_Name"]."_seconds";

                        if ($field_array["Format"] == "event" || $field_array["Format"] == "event_date")
                                $date_field = $field_array["Field_Name"];
                        $msg_id_index += 6;
                        break;
                    case NC_FIELDTYPE_MULTISELECT:
                        $multilist_fileds[] = array('name' => $field_array["Field_Name"], 'table' => strtok($field_array["Format"], ':'));
                        $field_name_query = "a.".$field_array['Field_Name'];
                        $field_var = "\$f_".$field_array['Field_Name'];
                        break;
                    default:
                        $field_name_query = "a.".$field_array["Field_Name"];
                        $field_var = "\$f_".$field_array["Field_Name"];
                        break;
                }
                $msg_id_index += 1;
                if ($field_name_query && $field_var) {
                    $field_names .= $field_name_query.",";
                    $field_vars .= $field_var.",";
                }

                if (!$allowTags && $field_type[$i] == 1)
                        $convert2txt .= "\$f_".$field_name[$i]." = htmlspecialchars(\$f_".$field_name[$i].");";
                if ($NL2BR && $field_type[$i] == 1)
                        $convert2txt .= "\$f_".$field_name[$i]." = nl2br(\$f_".$field_name[$i].");";
                $i++;
            }
        }


        $table_extra = "a.Message_ID, a.User_ID, a.IP, a.UserAgent, a.LastUser_ID, a.LastIP, a.LastUserAgent, a.Priority, a.Keyword, a.Parent_Message_ID";
        $extra_vars = "\$f_RowID, \$f_UserID, \$f_IP, \$f_UserAgent, \$f_LastUserID, \$f_LastIP, \$f_LastUserAgent, \$f_Priority, \$f_Keyword, \$f_Parent_Message_ID, ";
        $table_extra .= ",a.Checked, a.Created, a.LastUpdated+0 AS LastUpdated ";

        return ( array('select' => $field_names.$table_extra,
        'vars' => $field_vars.$extra_vars."\$f_Checked, \$f_Created, \$f_LastUpdated",
        'joins' => $joins,
        'msg_id_index' => $msg_id_index) );
    }

    protected function get_user($user_id) {
        static $storage = array();
        if (!empty($storage[$user_id])) {
            return $storage[$user_id];
        }

        $db = $this->db;
        $nc_core = $this->core;

        if (empty($this->users_id)) return false;

        // получим номера пользователей, которые уже есть в кэше
        $exist_id = array();
        if (!empty($storage)) {
            foreach ($storage as $v) {
                $exist_id[] = $v['User_ID'];
            }
        }
        // номера пользоватлей, данные о которых нужно получить
        // не нужно повторно обрабатывать одного и того же пользователя
        $required_ids = array_diff($this->users_id, $exist_id);

        if (empty($required_ids)) {
            return false;
        }

        // дополнительные поля
        $table_fields = $nc_core->get_system_table_fields('User');
        $counted_fields = count($table_fields);
        // данные о пользователях
        $users = $db->get_results("SELECT * FROM `User`
                               WHERE `User_ID` IN (".join(',', $required_ids).")",
                        ARRAY_A);
        // данные о вхождении пользователя в группу
        $res = $db->get_results("SELECT `User_ID`, `PermissionGroup_ID`
                             FROM `User_Group`
                             WHERE `User_ID` IN (".join(',', $required_ids).")",
                        ARRAY_A);
        if (!empty($res)) {
            foreach ($res as $v) {
                $user_group[$v['User_ID']][] = $v['PermissionGroup_ID'];
            }
        }

        // поля типа файл
        $file_field = array();
        $filetable = array();
        // найдем все поля типа файл
        for ($i = 0; $i < $counted_fields; $i++) {
            if ($table_fields[$i]['type'] == NC_FIELDTYPE_FILE) {
                $file_field[$table_fields[$i]['id']] = $table_fields[$i]['id'];
            }
        }

        // если есть поля типа файл, то выполним запрос к Filetable
        if (!empty($file_field)) {
            $file_in_table = $db->get_results("SELECT `Virt_Name`, `File_Path`, `Message_ID`, `Field_ID`
        FROM `Filetable`
        WHERE `Field_ID` IN (".join(',', $file_field).")
        AND `Message_ID` IN (".join(',', $required_ids).") ", ARRAY_A);

            if (!empty($file_in_table)) {
                foreach ($file_in_table as $v) {
                    $filetable[$v['Message_ID']][$v['Field_ID']] = array($v['Virt_Name'], $v['File_Path']);
                }
            }
        }

        // обработка каждого пользователя
        foreach ($users as $user) {

            for ($i = 0; $i < $counted_fields; $i++) {
                $field_id = $table_fields[$i]['id'];
                $field_name = $table_fields[$i]['name'];
                $field_type = $table_fields[$i]['type'];
                $field_format = $db->escape($table_fields[$i]['format']);

                if ($user[$field_name]) {
                    switch ($field_type) {
                        case NC_FIELDTYPE_SELECT:
                            $user[$field_name."_id"] = $user[$field_name];
                            $user[$field_name] = $this->get_element($field_format, $user[$field_name]);
                            break;
                        case NC_FIELDTYPE_FILE:
                            //file_data - массив с ориг.названием, типом, размером, [именем_файла_на_диске]
                            $file_data = explode(':', $user[$field_name]);

                            $user[$field_name."_name"] = $file_data[0]; // оригинальное имя
                            $user[$field_name."_type"] = $file_data[1]; // тип
                            $user[$field_name."_size"] = $file_data[2]; // размер
                            $ext = substr($file_data[0], strrpos($file_data[0], "."));  // расширение
                            // запись в таблице Filetable
                            $row = $filetable[$user['User_ID']][$field_id];
                            if ($row) {
                                // Proteced FileSystem
                                $user[$field_name] = $nc_core->get_variable("SUB_FOLDER").$nc_core->get_variable("HTTP_FILES_PATH").ltrim($row[1], '/')."h_".$row[0];
                                $user[$field_name."_url"] = $nc_core->get_variable("SUB_FOLDER").$nc_core->get_variable("HTTP_FILES_PATH").ltrim($row[1], '/').$row[0];
                            } else {
                                if ($file_data[3]) {
                                    // Original FileSystem
                                    $user[$field_name] = $user[$field_name."_url"] = $nc_core->get_variable("SUB_FOLDER").$nc_core->get_variable("HTTP_FILES_PATH").$file_data[3];
                                } else {
                                    // Simple FileSysytem
                                    $user[$field_name] = $user[$field_name."_url"] = $nc_core->get_variable("SUB_FOLDER").$nc_core->get_variable("HTTP_FILES_PATH").$field_id."_".$user["User_ID"].$ext;
                                }
                            }
                            break;
                        case NC_FIELDTYPE_DATETIME:
                            $user[$field_name."_year"] = substr($user[$field_name], 0, 4);
                            $user[$field_name."_month"] = substr($user[$field_name], 5, 2);
                            $user[$field_name."_day"] = substr($user[$field_name], 8, 2);
                            $user[$field_name."_hours"] = substr($user[$field_name], 11, 2);
                            $user[$field_name."_minutes"] = substr($user[$field_name], 14, 2);
                            $user[$field_name."_seconds"] = substr($user[$field_name], 17, 2);
                            break;
                        case NC_FIELDTYPE_MULTISELECT:
                            $array_with_id = explode(',', $user[$field_name]);

                            if (!$array_with_id[0]) unset($array_with_id[0]);
                            if (!$array_with_id[count($array_with_id)])
                                    unset($array_with_id[count($array_with_id)]);
                            if (empty($array_with_id)) break;
                            // латинское имя списка
                            $table_name = strtok($field_format, ':');
                            // получим сами элементы
                            $user[$field_name."_id"] = array();
                            $user[$field_name] = array();
                            foreach ($array_with_id as $id) {
                                $user[$field_name."_id"][] = $id;
                                $user[$field_name][] = $this->get_element($table_name, $id);
                            }
                            break;
                    }// switch - тип поля
                } // if - поле не пустое
            } // for - по полю

            $user['Permission_Group'] = $user_group[$user['User_ID']];
            $storage[$user['User_ID']] = $user;
        } // foreach каждый пользователь




        return $storage[$user_id];
    }

    protected function get_element($table, $id) {
        static $cache = array();

        if (!isset($cache[$table][$id])) {

            $table = $this->db->escape($table);
            $id = intval($id);

            $res = $this->db->get_results("SELECT `".$table."_ID` AS `ID`, `".$table."_Name` AS `Name`
                               FROM `Classificator_".$table."`", ARRAY_A);
            if (!empty($res)) {
                foreach ($res as $v) {
                    $cache[$table][$v['ID']] = $v['Name'];
                }
            }
        }
        return $cache[$table][$id];
    }

    protected function get_eval_template($mailer_id, $item, $message = 0, $available_vars = array(), &$mail_not_send = 0) {
        static $cache = array();

        static $static_vars = array();
        // импортируем переменные и массивы, такие как:
        // cuurent_user, cc, cc_array, cc_num, cc_i
        extract($available_vars);
        $db = $this->db;
        $MODULE_VARS = $this->core->modules->get_module_vars();

        if ($item != 'Record') $message = 0;

        // Возможно, уже есть в кэше
        if (isset($cache[$item][$mailer_id][$cc][$message])) {
            return $cache[$item][$mailer_id][$cc][$message];
        }


        // рассылка
        $mailer = $this->subscriber->get($mailer_id);

        // Подписка на раздел
        if ($mailer['Type'] == 1 || $mailer['Type'] == 4) {
            if (!$cc) $cc = $mailer['Sub_Class_ID'];
            // получить $current_xx;
            $current_cc = $this->core->sub_class->get_by_id($cc);
            $sub = $current_cc['Subdivision_ID'];
            $catalogue = $current_cc['Catalogue_ID'];
            $current_catalogue = $this->core->catalogue->get_by_id($catalogue);
            $current_sub = $this->core->subdivision->get_by_id($sub);
            $classID = $current_cc['Class_ID'];
        }

        // обработка объекта - нужно получить все переменные объекта
        if ($mailer['Type'] == 1 || $mailer['Type'] == 4) {
            eval("list(".$this->_message_vars[$classID].") = \$this->_message_res[\$classID][\$message];");
        }

        // условие рассылки обрабатывается отдельно
        if ($item == 'SendCond') {
            // по умолчанию - рассылаем
            $posting = 1;
            if ($mailer[$item]) eval(nc_check_eval($mailer[$item].";"));
            $result = $posting;
        }
        else {
            eval(nc_check_eval("\$result = \"".$mailer[$item]."\";"));
        }

        // если  не используется current_user,
        // то его можно закэшировать
        if (strpos($mailer[$item], '$current_user') === false) {
            $cache[$item][$mailer_id][$cc][$message] = $result;
        }

        return $result;
    }

    public function update_stats() {
        $this->db->last_error = '';

        if (!empty($this->_mailer_count)) {
            foreach ($this->_mailer_count as $mailer_id => $count) {
                $mailer_id = intval($mailer_id);
                $count = intval($count);
                if (!$mailer_id || !$count) {
                    continue;
                }
                //у периодической рассылки задано время начала рассылки, его нужно сохранить
                $this->db->query("UPDATE `Subscriber_Mailer`
                          SET `MailCount` = `MailCount` +  '".$count."', `LastSend` = IF (`Type`= 2 , `LastSend` , NOW())
                          WHERE `Mailer_ID` = '".$mailer_id."' ");
            }
        }

        if (empty($this->_sbs_id)) return 0;

        $this->_sbs_id = array_unique($this->_sbs_id);


        $this->db->query("UPDATE `Subscriber_Subscription` SET `LastSend` = NOW() WHERE ID IN (".join(',', $this->_sbs_id).") ");


        if ($this->db->last_error) {
            throw new Exception($this->tools->db_error(__CLASS__, __FUNCTION__, 1));
        }

        return 0;
    }

    protected function _stats_full_update($mailer_id, $user_id) {
        $mailer_id = intval($mailer_id);
        $user_id = intval($user_id);
        if (!$mailer_id || !$user_id) {
            return false;
        }

        if ($this->subscriber->get($mailer_id, 'InStat')) {
            $this->db->query("INSERT INTO `Subscriber_Log`(`Mailer_ID`, `User_ID`, `ActionType`)
                       VALUES ('".$mailer_id."','".$user_id."','mail') ");
        }
    }

}