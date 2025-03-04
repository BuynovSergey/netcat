<?php

class nc_Mail extends nc_System {

    /** @var  Swift_Transport */
    protected $transport;
    /** @var  Swift_Mailer */
    protected $swiftMailer;
    /** @var  Swift_Message */
    protected $message;

    protected $charset;
    protected $priority = 3;
    protected $body_plain;
    protected $body_html;
    protected $isHtml;

    /**
     * nc_Mail constructor
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Устанавливает приоритет письма
     *
     * @param string $priority
     */
    public function setPriority($priority) {
        $this->priority = $priority;
    }

    /**
     * Устанавливает кодировку письма
     *
     * @param string $charset
     */
    public function setCharset($charset) {
        $this->charset = $charset;
    }

    /**
     * Формирует тело письма
     *
     * @param string $plain
     * @param string $html
     */
    public function mailbody($plain, $html = "") {
        $this->init();
        $this->body_plain = $plain;
        $this->body_html = $html;
        $this->isHtml = !empty($html) ? true : false;
    }

    /**
     * Позволяет пользователю прикрепить файл
     *
     * @param string $file_path
     * @param string $original_name
     * @param string $content_type
     */
    public function attachFile($file_path, $original_name = '', $content_type = '') {
        $this->init();
        $attachment = Swift_Attachment::fromPath($file_path, $content_type)->setFilename($original_name);
        $this->message->attach($attachment);
    }

    /**
     * Позволяет пользователю прикрепить файл в тело письма
     *
     * @param string $file_path
     * @param string $original_name
     * @param string $content_type
     * @return string cid для вставки в HTML-код письма
     */
    public function attachFileEmbed($file_path, $original_name, $content_type) {
        $this->init();
        $embedded_file = Swift_EmbeddedFile::fromPath($file_path)->setFilename($original_name)->setContentType($content_type);
        return $this->message->embed($embedded_file);
    }

    /**
     * Формирование и отправка письма
     *
     * @param string $to
     * @param string $from
     * @param string $reply
     * @param string $subject
     * @param string $from_name
     * @param string $to_name
     * @return int
     */
    public function send($to, $from, $reply, $subject, $from_name = '', $to_name = '') {
        if (empty($to)) {
            return 0;
        }

        try {
            $this->init();
            $this->set_subject($subject);
            $this->set_from($from, $from_name);
            $this->set_to($to, $to_name);
            $this->message->setReplyTo($reply);

            if ($this->isHtml && $this->body_html) {
                $this->message->setBody($this->body_html, 'text/html');
                if ($this->body_plain) {
                    $this->message->addPart($this->body_plain, 'text/plain');
                }
            }
            else if ($this->body_plain) {
                $this->message->setBody($this->body_plain, 'text/plain');
            }
            else {
                $this->message->setBody('');
            }

            $result = $this->swiftMailer->send($this->message);
            unset($this->message);
            return $result;
        }
        catch (Exception $e) {
            trigger_error(__CLASS__ . "::" . __METHOD__ . "(): " . $e->getMessage(), E_USER_WARNING);
            unset($this->message);
            return 0;
        }
    }

    /**
     * Устанавливает Тему письма
     *
     * @param string $subject
     */
    public function set_subject($subject = '') {
        $subject = $this->encode_header($subject, $this->charset);
        $this->message->setSubject($subject);
    }

    /**
     *
     * Устанавливает Кому
     *
     * @param string $mail_to
     * @param string $name_to
     */
    public function set_to($mail_to, $name_to = '') {
        $mail_to_arr = array();
        $name_to_arr = array();

        $tmp_to = explode(",", $mail_to);
        if (count($tmp_to) > 1) {
            $mail_to_arr = $tmp_to;
        }
        else {
            $mail_to_arr = array($mail_to);
        }
        if ($name_to != '') {
            $tmp_name_to = explode(",", $name_to);
            if (count($tmp_name_to) > 1) {
                foreach ($tmp_name_to as $value) {
                    array_push($name_to_arr, $this->encode_header($value, $this->charset));
                }
            }
            else {
                $name_to_arr = array($this->encode_header($name_to, $this->charset));
            }
        }
        if (count($name_to_arr) > 0) {
            foreach ($mail_to_arr as $key => $mail_val) {
                $this->message->addTo($mail_val, $name_to_arr[$key]);
            }
        }
        else {
            $this->message->setTo($mail_to_arr);
        }
    }

    /**
     * Устанавливает От кого
     *
     * @param string $mail_from
     * @param string $name_from
     */
    public function set_from($mail_from, $name_from = '') {

        if ($name_from != '') {
            $name_from = $this->encode_header($name_from, $this->charset);
        }
        if ($name_from != '') {
            $this->message->setFrom(array($mail_from => $name_from));
        }
        else {
            $this->message->setFrom($mail_from);
        }
    }

    /**
     * Устанавливает Копия
     *
     * @param string|array $addresses
     * @param string $name
     */
    public function set_cc($addresses, $name = null) {
        $this->init();
        $this->message->setCc($addresses, $name);
    }

    /**
     * Устанавливает Скрытая копия
     *
     * @param string|array $addresses
     * @param string $name
     */
    public function set_bcc($addresses, $name = null) {
        $this->init();
        $this->message->setBcc($addresses, $name);
    }

    /**
     * Инициализируем Swift
     *
     * @param bool $new_message
     */
    public function init($new_message = false) {
        $nc_core = nc_core::get_object();

        //устанавливаем кодировку
        if (empty($this->charset)) {
            $this->charset = ($nc_core->NC_UNICODE || empty($nc_core->NC_CHARSET)) ? "utf-8" : $nc_core->NC_CHARSET;
            if (!defined("MAIN_EMAIL_ENCODING")) {
                define("MAIN_EMAIL_ENCODING", $this->charset);
            }
        }
        if (empty($this->message) || $new_message == true) {
            if ($new_message == false) {
                //подключаем swift library
                require_once $nc_core->INCLUDE_FOLDER . 'lib/Swift/swift_required.php';
                //активируем транспорт, в зависимости от выбранных на сайте настроек
                if ($nc_core->get_settings('SpamUseTransport') == 'Smtp') {
                    $this->transport = Swift_SmtpTransport::newInstance()
                        ->setHost(trim($nc_core->get_settings('SpamSmtpHost')))
                        ->setPort(trim($nc_core->get_settings('SpamSmtpPort')));

                    if ($nc_core->get_settings('SpamSmtpAuthUse') == 1) {
                        $this->transport
                            ->setUsername($nc_core->get_settings('SpamSmtpUser'))
                            ->setPassword($nc_core->get_settings('SpamSmtpPass'));
                    }

                    if ($nc_core->get_settings('SpamSmtpEncryption') != '') {
                        $this->transport->setEncryption($nc_core->get_settings('SpamSmtpEncryption'));
                    }
                }
                else if ($nc_core->get_settings('SpamUseTransport') == 'Sendmail') {
                    $this->transport = Swift_SendmailTransport::newInstance($nc_core->get_settings('SpamSendmailCommand'));
                }
                else {
                    $this->transport = Swift_MailTransport::newInstance($nc_core->get_settings('SpamMailAdditionalParameters'));
                }
                //собственно Swift
                $this->swiftMailer = Swift_Mailer::newInstance($this->transport);
            }
            //объект сообщения
            $this->message = Swift_Message::newInstance(null)->setCharset($this->charset);
            $this->message->setPriority($this->priority);
        }
    }

    /**
     * Функция для корректной работы Mail_Transport с заголовками
     * для SMTP_Transport необязательна
     *
     * @param string $input
     * @param string $charset
     * @return string
     */
    public function encode_header($input, $charset = 'utf-8') {
        //$input = chunk_split($input);

        // add encoding to the beginning of each line
        $str = "=?$charset?B?" . base64_encode($input) . "?=";
        return $str;
    }

    /**
     * Убирает все аттачи из письма
     */
    public function clear() {
        $this->init();
        $parts = $this->message->getChildren();
        if (count($parts) > 0) {
            foreach ($parts as $part) {
                $this->message->detach($part);
            }
        }
    }

    /**
     * Аналог nc_mail_attachment_attach
     *
     * @param string $body
     * @param string|string[] $types
     * @return string
     */
    public function attachment_attach($body, $types) {
        $nc_core = nc_core::get_object();

        $types_escaped = array();
        $attachments = array();

        if (!is_array($types)) {
            $types = array($types);
        }

        foreach ($types as $type) {
            if (!is_string($type)) {
                continue;
            }
            $types_escaped[] = '\'' . $nc_core->db->escape($type) . '\'';
        }

        if ($types_escaped) {
            $sql = 'SELECT `Filename`, `Path`, `Content_Type`, `Extension`
                      FROM `Mail_Attachment`
                     WHERE `Type` IN (' . implode(',', $types_escaped) . ')';
            $attachments = (array)$nc_core->db->get_results($sql, ARRAY_A);
        }

        while (preg_match('/\%FILE_([-_a-z0-9]+)/i', $body, $match)) {
            $filename = $match[1];

            $file = false;

            foreach ($attachments as $index => $attachment) {
                if (strtolower($attachment['Filename']) === strtolower($filename)) {
                    $file = $attachment;
                    unset($attachments[$index]);
                    break;
                }
            }

            $replace = '';
            if ($file) {
                $absolute_path = $nc_core->DOCUMENT_ROOT . $file['Path'];
                $replace = 'cid:' . $this->attachFileEmbed($absolute_path, $filename . '.' . $file['Extension'], $file['Content_Type']);
            }

            $body = preg_replace('/\%FILE_' . preg_quote($filename, '/') . '/', $replace, $body);
        }

        foreach ($attachments as $attachment) {
            $absolute_path = $nc_core->DOCUMENT_ROOT . $attachment['Path'];
            $this->attachFileEmbed($absolute_path, $attachment['Filename'] . '.' . $attachment['Extension'], $attachment['Content_Type']);
        }

        return $body;
    }

}
