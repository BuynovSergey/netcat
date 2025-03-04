<?php

if ( !class_exists("nc_System") ) die("Unable to load file.");
/**
 * @package  Mail_Queue
 * @version  $Revision: 4290 $
 */
require_once 'Mail/Queue/Container.php';

/**
* Mail_Queue_Container_db - Storage driver for fetching mail queue data
* from a PEAR_DB database
*
* @author   Radek Maciaszek <chief@php.net>
* @version  $Id: ezsql.php 4290 2011-02-23 15:32:35Z denis $
* @package  Mail_Queue
* @access   public
*/
class Mail_Queue_Container_ezsql extends Mail_Queue_Container
{
    // {{{ class vars

    /**
     * Reference to the current database connection.
     * @var object PEAR_DB
     */
    var $db;

    /**
     * Table for sql database
     * @var  string
     */
    var $mail_table = 'mail_queue';

    /**
     * @var string  the name of the sequence for this table
     */
    var $sequence = null;

    // }}}
    // {{{ Mail_Queue_Container_db()

    /**
     * Contructor
     *
     * Mail_Queue_Container_db:: Mail_Queue_Container_db()
     *
     * @param mixed $options    An associative array of option names and
     *                          their values. See DB_common::setOption
     *                          for more information about connection options.
     *
     * @access public
     */
    public function __construct($options)
    {
        if (isset($options['mail_table'])) {
            $this->mail_table = $options['mail_table'];
        }
        $this->sequence = (isset($options['sequence']) ? $options['sequence'] : $this->mail_table);

        if (!empty($options['pearErrorMode'])) {
            $this->pearErrorMode = $options['pearErrorMode'];
        }
        $this->db =& $GLOBALS['db'];
        $this->setOption();
    }

    // }}}
    // {{{ _preload()

    /**
     * Preload mail to queue.
     *
     * @return mixed  True on success else Mail_Queue_Error object.
     * @access private
     */
    function _preload()
    {
        $query = sprintf("SELECT * FROM %s WHERE sent_time IS NULL
                            AND try_sent < %d
                            AND '%s' > time_to_send
                            ORDER BY time_to_send
                            LIMIT %d, %d",
                         $this->mail_table,
                         $this->try,
                         date("Y-m-d H:i:s"),
                         $this->offset, $this->limit
                         );


        $this->_last_item = 0;
        $this->queue_data = array(); //reset buffer

        $res = $this->db->get_results($query, ARRAY_A);
        
//        foreach ($res as $row) print "!";
//        print "<pre>"; print_r($res); die("<hr>$query");
        if (!$res) { // queue is empty
            return true;
        }

        foreach ($res as $row)
        {
            $this->queue_data[$this->_last_item] = new Mail_Queue_Body(
                $row['id'],
                $row['create_time'],
                $row['time_to_send'],
                $row['sent_time'],
                $row['id_user'],
                $row['ip'],
                $row['sender'],
                $row['recipient'],
                unserialize($row['headers']),
                unserialize($row['body']),
                $row['delete_after_send'],
                $row['try_sent']
            );
            $this->_last_item++;
        }

        return true;
    }

    // }}}
    // {{{ put()

    /**
     * Put new mail in queue and save in database.
     *
     * Mail_Queue_Container::put()
     *
     * @param string $time_to_send  When mail have to be send
     * @param integer $id_user  Sender id
     * @param string $ip  Sender ip
     * @param string $from  Sender e-mail
     * @param string $to  Reciepient e-mail
     * @param string $hdrs  Mail headers (in RFC)
     * @param string $body  Mail body (in RFC)
     * @param bool $delete_after_send  Delete or not mail from db after send
     *
     * @return mixed  ID of the record where this mail has been put
     *                or Mail_Queue_Error on error
     * @access public
     **/
    function put($time_to_send, $id_user, $ip, $sender,
                $recipient, $headers, $body, $delete_after_send=true)
    {
        $query = sprintf("INSERT INTO %s (create_time, time_to_send, id_user, ip,
                        sender, recipient, headers, body, delete_after_send)
                        VALUES(now(), '%s', %d, '%s', '%s', '%s', '%s', '%s', %d)",
                         $this->mail_table,
                         addslashes($time_to_send),
                         addslashes($id_user),
                         addslashes($ip),
                         addslashes($sender),
                         addslashes($recipient),
                         addslashes($headers),
                         addslashes($body),
                         $delete_after_send
        );

        $res = $this->db->query($query);
        return $db->insert_id;
    }

    // }}}
    // {{{ countSend()

    /**
     * Check how many times mail was sent.
     *
     * @param object   Mail_Queue_Body
     * @return mixed  Integer or Mail_Queue_Error class if error.
     * @access public
     */
    function countSend($mail)
    {
        if (!is_object($mail) || !is_a($mail, 'mail_queue_body')) {
            return new Mail_Queue_Error('Expected: Mail_Queue_Body class',
                __FILE__, __LINE__);
        }
        $count = $mail->_try();
        $query = sprintf("UPDATE %s SET try_sent = %d WHERE id = %d",
                         $this->mail_table,
                         $count,
                         $mail->getId()
        );

        $res = $this->db->query($query);
        return $count;
    }

    // }}}
    // {{{ setAsSent()

    /**
     * Set mail as already sent.
     *
     * @param object Mail_Queue_Body object
     * @return bool
     * @access public
     */
    function setAsSent($mail)
    {
        if (!is_object($mail) || !is_a($mail, 'mail_queue_body')) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_UNEXPECTED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'Expected: Mail_Queue_Body class');
        }
        $query = sprintf("UPDATE %s SET sent_time = now() WHERE id = %d",
                         $this->mail_table,
                         $mail->getId()
        );

        $res = $this->db->query($query);
        return true;
    }

    // }}}
    // {{{ getMailById()

    /**
     * Return mail by id $id (bypass mail_queue)
     *
     * @param integer $id  Mail ID
     * @return mixed  Mail object or false on error.
     * @access public
     */
    function getMailById($id)
    {
        $query = sprintf("SELECT * FROM %s WHERE id = %d",
                         $this->mail_table,
                         (int)$id
        );
        $res = $this->db->get_results($query, ARRAY_A);

        $row = $res[0];
        if (!is_array($row)) {
            return new Mail_Queue_Error(MAILQUEUE_ERROR_QUERY_FAILED,
                $this->pearErrorMode, E_USER_ERROR, __FILE__, __LINE__,
                'DB::query failed - "'.$query);
        }
        return new Mail_Queue_Body(
            $row['id'],
            $row['create_time'],
            $row['time_to_send'],
            $row['sent_time'],
            $row['id_user'],
            $row['ip'],
            $row['sender'],
            $row['recipient'],
            unserialize($row['headers']),
            unserialize($row['body']),
            $row['delete_after_send'],
            $row['try_sent']
        );
    }

    // }}}
    // {{{ deleteMail()

    /**
     * Remove from queue mail with $id identifier.
     *
     * @param integer $id  Mail ID
     * @return bool  True on success else Mail_Queue_Error class
     *
     * @access public
     */
    function deleteMail($id)
    {
        $query = sprintf("DELETE FROM %s WHERE id = %d",
                         $this->mail_table,
                         addslashes($id)
        );
        $res = $this->db->query($query);

        return true;
    }

    // }}}
}
?>