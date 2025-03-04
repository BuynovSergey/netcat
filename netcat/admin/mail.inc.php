<?php

/**
 * @param array $matches
 * @return string
 */
function nc_quoted_printable_encode_callback($matches) {
    return sprintf("=%02X", ord($matches[1])) . (isset($matches[2]) ? $matches[2] : '');
}

/**
 * Quoted-printable header encoder (split into 76-char chunks)
 *
 * @param string $input
 * @param string $charset
 * @return string
 */
function nc_quoted_printable_encode_header($input, $charset = MAIN_EMAIL_ENCODING) {
    $str = preg_replace_callback("/([^\x09\x21-\x3C\x3E-\x7E])/",
                                 'nc_quoted_printable_encode_callback',
                                 rtrim($input));

    // add encoding to the beginning of each line
    $encoding = "=?$charset?Q?";
    $content_length = 72 - strlen($encoding);

    nc_preg_match_all("/.{1,$content_length}([^=]{0,2})?/", $str, $regs);
    $str = $encoding . join("?=\n\t$encoding", $regs[0]) . "?=";

    return $str;
}

/**
 * base64 header encoder
 *
 * @param string $input
 * @param string $charset
 * @return string
 */
function nc_base64_encode_header($input, $charset = MAIN_EMAIL_ENCODING) {
    // add encoding to the beginning of each line
    $str = "=?$charset?B?" . base64_encode($input) . "?=";
    return $str;
}

/**
 * Quoted-printable string encoder
 *
 * @param string $input
 * @return string
 */
function nc_quoted_printable_encode($input) {
    $str = preg_replace_callback('/([^\x09\x20\x0D\x0A\x21-\x3C\x3E-\x7E])/', 'nc_quoted_printable_encode_callback', $input);
    // encode x20, x09 at the end of lines
    $str = preg_replace_callback('"/([\x20\x09])(\r?\n)/"', 'nc_quoted_printable_encode_callback', $str);
    $str = str_replace("\r", "", $str);

    // split into chunks
    // Из-за разбиения строки по RFC (=CRLF) возникают "лишние" переносы строк на некоторых почтовых серверах

    $lines = explode("\n", $str);
    foreach ($lines as $num => $line) {
        if (strlen($line) > 76) {
            nc_preg_match_all('/.{1,73}([^=]{0,2})?/', $line, $regs);
            $lines[$num] = join("=\n", $regs[0]);
        }
    }
    $str = join("\n", $lines);

    return $str;
}

// ----------------------

/**
 * Положить письмо в очередь
 *
 * @param string $recipient
 * @param string $from
 * @param string $subject
 * @param string $message
 * @param string HTML-сообщение
 *
 * Чтобы отправить сообщение в формате HTML, нужно указать параметр html_message.
 * При этом параметр message должен содержать сообщение в plain text или может быть пустым
 *
 * Чтобы отправить plain text, параметр html_message нужно оставить пустым.
 */

function nc_mail2queue($recipient, $from, $subject, $message, $html_message = "", $attachment_type = "") {

    require_once("Mail/Queue.php");

    $db_options = array('type' => 'ezsql', 'mail_table' => 'Mail_Queue');
    $mail_options = array('driver' => 'mail');

    $mail_queue = new Mail_Queue($db_options, $mail_options);

    $hdrs = array('From' => $from, // email only (no name!)
        'Subject' => nc_base64_encode_header($subject));

    $mime = new Mail_mime("\n");

    if ($attachment_type) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $type_escaped = $db->escape($attachment_type);

        $sql = "SELECT `Filename`, `Path`, `Content_Type`, `Extension` FROM `Mail_Attachment` WHERE `Type` = '{$type_escaped}'";
        $attachments = (array)$db->get_results($sql, ARRAY_A);

        while (preg_match('/\%FILE_([-_a-z0-9]+)/i', $html_message, $match)) {
            $filename = $match[1];

            $file = false;

            foreach($attachments as $index => $attachment) {
                if (strtolower($attachment['Filename']) === strtolower($filename)) {
                    $file = $attachment;
                    unset($attachments[$index]);
                    break;
                }
            }

            $replace = '';
            if ($file) {
                $absolute_path = $nc_core->DOCUMENT_ROOT . $file['Path'];
                $replace = 'file_' . $filename . '.' . $file['Extension'];
                $mime->addHTMLImage(@file_get_contents($absolute_path), $file['Content_Type'], $replace, false);
            }

            $html_message = preg_replace('/\%FILE_' . preg_quote($filename, '/') . '/', $replace, $html_message);
        }

        foreach ($attachments as $attachment) {
            $absolute_path = $nc_core->DOCUMENT_ROOT . $attachment['Path'];
            $mime->addAttachment($absolute_path, $attachment['Content_Type'], $attachment['Filename'] . '.' . $attachment['Extension']);
        }
    }

    if ($message) {
        $mime->setTXTBody($message);
    }

    if ($html_message) {
        $mime->setHTMLBody($html_message);
    }

    $body = $mime->get(array('text_encoding' => '8bit', 'html_charset' => MAIN_EMAIL_ENCODING,
        'text_charset' => MAIN_EMAIL_ENCODING, 'head_charset' => MAIN_EMAIL_ENCODING));
    $hdrs = $mime->headers($hdrs);

    $mail_queue->put($from, $recipient, $hdrs, $body);
}