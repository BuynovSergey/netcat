<?php

/* $Id: nc_auth_hash.class.php 4257 2011-01-31 16:09:11Z denis $ */

/**
 * class nc_auth_hash
 *
 * @category nc_auth
 */
class nc_auth_hash {

    protected $db;
    protected $hash;
    protected $key, $create_session, $delete_hash, $expire_hash;

    /**
     * Construct
     *
     */
    protected function __construct() {
        global $db;
        $this->db = $db;
    }

    /**
     * Instance self object method
     *
     * @return self object
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
     * Функция авторизовывает пользователя по хэшу
     *
     * @param str hash
     * @return int user id
     */
    public function authorize_by_hash($hash) {

        if (!$hash) return 0;

        $this->hash = $hash;

        // провека на истечене срока и на возможность авторизации из настроек
        if ($this->check_expire() && $this->check_availability()) {
            // проверка на существование хэша и возможности авторизоваться в данном разделе
            if (($user_id = $this->get_user_by_hash()) && $this->check_sub($user_id)) {
                // сообственно, авторизация
                $id = Authorize($user_id, 'authorize', NC_AUTHTYPE_HASH, 0, $this->create_session);
            }
        }

        // возможно, хэш нужно удалить
        $this->_attempt_to_delete();

        return $id;
    }

    /**
     * Создать ссылку для авторизации
     *
     * @param int $user_id
     * @param bool $delete_hash - нужно ли удалять хэш (возможность повторного использвоания)
     * @param int $expire - время жизни хэша ( в часах )
     * @return bool|string hash
     */
    public function create_auth_hash($user_id, $delete_hash = null, $expire = null) {
        $user_id = (int)$user_id;
        // создание хэша
        $hash = $this->_make_hash($user_id, 1, $delete_hash, $expire);
        // добавить в БД
        $this->db->rows_affected = 0;
        $this->db->query("UPDATE `User` SET `Auth_Hash` = '{$this->db->escape($hash)}' WHERE `User_ID` = '{$user_id}'");

        if ($this->db->rows_affected) {
            return $hash;
        }

        return false;
    }

    /**
     * Узнать хэш по пользоваетлю
     *
     * @param int $user_id
     * @return str
     */
    public function get_hash_by_user($user_id) {
        return $this->db->get_var("SELECT `Auth_Hash` FROM `User` WHERE `User_ID` = '".intval($user_id)."'");
    }

    /**
     * Удалить хэш по пользователю
     *
     * @param int $user_id
     */
    public function delete_auth_hash($user_id) {
        $this->db->query("UPDATE `User` SET `Auth_Hash` = '' WHERE `User_ID` = '".intval($user_id)."'");
        return 0;
    }

    /**
     * Проверка по разделу/ админке
     *
     * @param isInsideAdmin - авторизация в админке
     * @param  sub - раздел (по умолчанию - текущий)
     * @return bool
     */
    public function check($isInsideAdmin = 0, $sub = 0) {
        $nc_core = nc_Core::get_object();
        // в админку
        if ($isInsideAdmin)
                return $nc_core->get_settings('authtype_admin', 'auth') & NC_AUTHTYPE_HASH;

        // в раздел
        if (!$sub) $sub = $nc_core->subdivision->get_current('Subdivision_ID');

        if (($subs_str = $nc_core->get_settings('hash_disabled_subs', 'auth'))) {
            $subs = explode(", ", $subs_str);
            if (!is_array($subs)) $subs = array($subs);
            if (empty($subs)) return true;

            if (in_array($sub, $subs)) return false;
        }

        return true;
    }

    /**
     * Получить номер пользователя по хэшу
     *
     * @param string $hh
     * @return int user id
     */
    protected function get_user_by_hash($hh = null) {
        static $storage = array();
        // возможно, результат запроса уже есть
        if ($storage[$hash]) return $storage[$hash];

        if ($hh === null) $hash = $this->db->escape($this->hash);

        $user_id = $this->db->get_var("SELECT `User_ID` FROM `User` WHERE `Auth_Hash` = '".$hash."'");
        // локальный кэш
        $storage[$hash] = $user_id;
        if ($user_id) return $user_id;

        return 0;
    }

    /**
     * распарсить хэш
     * Структура хэша: ключ(32) + создать сессию или просто авторизовать (1) + удалить хэш (1) + время истечения (х)
     *
     * @param str $hash
     * @return
     */
    protected function parse_hash() {
        // для предотвращения повторного вызова
        static $init = 0;
        if ($init) return 1;
        $init = 1;

        $hash = $this->hash;
        //минимальная длина ключа
        if (strlen($hash) < 34) return 0;

        $this->key = substr($hash, 0, 32);        // ключ
        $this->create_session = $hash[32];        // создавать сессию или нет
        $this->delete_hash = $hash[33];           // удалять хэш или нет
        $this->expire_hash = substr($hash, 34);  // время окончания

        return 1;
    }

    /**
     * Проверка на истечение срока
     *
     * @return bool
     */
    protected function check_expire() {
        $this->parse_hash();

        if ($this->expire_hash * 3600 > time()) return 1;
        return 0;
    }

    /**
     * Возможно ли авторизовываться по хэшу (в зависимости от настроек модуля)
     *
     * @return bool
     */
    protected function check_availability() {
        $nc_core = nc_Core::get_object();

        $this->parse_hash();

        if ($this->create_session) {
            return $nc_core->get_settings('authtype_site', 'auth') & NC_AUTHTYPE_HASH;
        }

        return $nc_core->get_settings('pseudo_enabled', 'auth');
    }

    protected function check_sub($user_id) {
        $nc_core = nc_Core::get_object();

        $this->parse_hash();

        // авторизация по хэшу
        if ($this->create_session) {
            return $this->check();
        } else { // авторизация псевдопользователя
            $sub = $nc_core->subdivision->get_current('Subdivision_ID');
            $check_ip = $nc_core->get_settings('pseudo_check_ip', 'auth');
            return $this->db->get_var("SELECT `ID` FROM `Auth_Pseudo`
                                 WHERE `User_ID` = '".intval($user_id)."' AND `Subdivision_ID` = '".intval($sub)."'
                                 ".( $check_ip ? " AND `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."' " : "")."");
        }
    }

    protected function _make_hash($user_id, $create_session, $delete_hash = null, $expire = null) {
        $nc_core = nc_Core::get_object();
        // key - постоянный ключ, используемый при создании хэша
        $key = md5(substr($nc_core->get_settings('SecretKey'), 10, 10));

        $hash = substr(sha1($key.rand().time().$user_id.$expire), rand(0, 5), 32);

        $hash .= $create_session ? '1' : '0';

        if ($delete_hash === null)
                $delete_hash = $nc_core->get_settings('hash_delete', 'auth') ? 1 : 0;
        $hash .= $delete_hash ? '1' : '0';

        if ($expire === null) {
            $expire = $nc_core->get_settings('hash_expire', 'auth') ? $nc_core->get_settings('hash_expire', 'auth') : 120;
        }
        $hash .= intval(time() / 3600 + $expire);

        // чтобы не было одинковых хэшей
        //if ( $this->get_user_by_hash($hash ) ) return $this->_make_hash($user_id, $create_session, $delete_hash, $expire);

        return $hash;
    }

    protected function _attempt_to_delete() {
        if ($this->delete_hash) {
            $this->db->query("UPDATE `User` SET `Auth_Hash` = '' WHERE `Auth_Hash` = '".$this->db->escape($this->hash)."'");
        }

        return 0;
    }

    // Псевдопользователи

    public function add_pseudo_user($fields, $sub, $ip ='', $expire = null) {
        $sub = intval($sub);

        $nc_core = nc_Core::get_object();

        $group = $nc_core->get_settings('pseudo_group', 'auth');

        $uniq_field = $nc_core->get_settings('pseudo_field', 'auth');
        if ($uniq_field) {
            $user = $this->db->get_row("SELECT `User_ID`, `Auth_Hash` FROM `User`
                                  WHERE `".$uniq_field."` = '".$this->db->escape($fields[$uniq_field])."' 
                                  ", ARRAY_A);
            if (!empty($user)) {
                return array('User_ID' => $user['User_ID'], 'Hash' => $user['Auth_Hash']);
            }
        }

        $fields['PermissionGroup_ID'] = intval($group);
        $fields['Checked'] = 1;
        $fields['UserType'] = 'pseudo';
        $fields['Created'] = date("Y-m-d H:i:s");

        foreach ($fields as $k => $v) {
            $sql_field[] = "`".$this->db->escape($k)."`";
            $sql_value[] = "'".$this->db->escape($v)."'";
        }
        $this->db->insert_id = 0;

        $this->db->query("INSERT INTO `User` (".join(',', $sql_field).") VALUES (".join(',', $sql_value).")");

        if ($user_id = $this->db->insert_id) {

            if (!$ip) $ip = $_SERVER['REMOTE_ADDR'];
            $ip = ip2long($ip);

            $this->db->query("INSERT INTO `Auth_Pseudo` ( `User_ID`, `Subdivision_ID`, `IP`) VALUES ('".$user_id."','".$sub."', '".$ip."')");

            $hash = $this->_make_hash($user_id, 0, 0);

            $this->db->query("UPDATE `User` SET `Auth_Hash` = '".$this->db->escape($hash)."' WHERE `User_ID` = '".intval($user_id)."'");

            nc_usergroup_add_to_group($user_id, $group);
        }

        return array('User_ID' => $user_id, 'Hash' => $hash);
    }

    public function delete_pseudo_user($users_id) {
        if (!is_array($users_id)) $users_id = array($users_id);
        foreach ($users_id as $k => $v) {
            $users_id[$k] = intval($v);
        }

        $ids_str = join(',', $users_id);

        $this->db->query("DELETE FROM `User` WHERE `User_ID` IN (".$ids_str.") ");
        $this->db->query("DELETE FROM `User_Group`  WHERE `User_ID` IN (".$ids_str.") ");
        $this->db->query("DELETE FROM `Auth_Pseudo`  WHERE `User_ID` IN (".$ids_str.") ");
    }

    public function delete_from_sub($sub, $user_id) {
        $sub = intval($sub);
        $user_id = intval($user_id);
        if (!$sub || !$user_id) return false;

        $this->db->query("DELETE FROM `Auth_Pseudo` WHERE `User_ID` = '".$user_id."' AND `Subdivision_ID` = '".$sub."'");
        // Если пользвоатель нигде уже не может авторизоваться - то его надо удалить
        if (!$this->db->get_var("SELECT `User_ID` FROM `Auth_Pseudo` WHERE `User_ID` = '".$user_id."' ")) {
            $this->delete_pseudo_user($user_id);
        }

        return 0;
    }

    public function delete_expire_user() {
        $nc_core = nc_Core::get_object();

        $expire = $nc_core->modules->get_vars('auth', 'PSEUDOUSERS_USER_EXPIRE');
        if (!$expire) $expire = 120;
        $expire *= 60 * 60; // в секундах
        $ids = $this->db->get_col("SELECT `User_ID` FROM `User`
                      WHERE (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`Created`)) >= '".$expire."'
                      AND `UserType` = 'pseudo' ");
        if (!empty($ids)) $this->delete_pseudo_user($ids);

        return 0;
    }

}
?>