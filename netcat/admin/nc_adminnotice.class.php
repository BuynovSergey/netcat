<?php

/* $Id: nc_adminnotice.class.php 8216 2012-10-15 11:40:59Z vadim $ */

class nc_AdminNotice {

    const NC_CONNECT_TIMEOUT = 3;
    protected $core;
    protected $notices;
    protected $data;

    public function __construct() {
        $this->core = nc_Core::get_object();

        $this->notices = array(
                array('func' => 'demo'),
                array('func' => 'license'),
                array('func' => 'crpatch'),
                array('func' => 'support'),
                array('func' => 'cron'),
                array('func' => 'writeable'),
                array('func' => 'safe_mode')
        );
    }

    public function check() {
        $this->data = @unserialize($this->core->get_settings('AdminNoticeResponse'));

        foreach ($this->notices as $v) {
            $result = call_user_func(array($this, 'check_'.$v['func']));
            if ($result) return $result;
        }

        return false;
    }

    public function check_demo() {
        if (!$this->core->is_trial) {
            return false;
        }
        $text = str_replace('%DAY', (int) ((strtotime($this->core->get_settings('InstallationDateOut')) - time()) / 86400 + 1), TOOLS_ACTIVATION_DAY);
        $type = 'attention';
        return array('text' => $text, 'type' => $type);
    }

    /**
     * Проверка лицензии
     */
    public function check_license() {
        if (!$this->data['lic']) return false;

        if ($this->data['lic'] == 1) {
            $text = NETCAT_ADMIN_NOTICE_LICENSE_ILLEGAL.' <a href="https://netcat.ru/adminhelp/illegal/">'.NETCAT_ADMIN_NOTICE_MORE.'</a>';
            $type = 'alarm';
        } else if ($this->data['lic'] == 2) {
            $text = NETCAT_ADMIN_NOTICE_LICENSE_MAYBE_ILLEGAL.' <a href="https://netcat.ru/products/about/extend/">'.NETCAT_ADMIN_NOTICE_MORE.'</a>';
            $type = 'attention';
        }

        return array('text' => $text, 'type' => $type);
    }

    /**
     * Проверка важных обновлений
     * @return <type>
     */
    public function check_crpatch() {
        if (!$this->data['crpatch']) return false;

        $text = NETCAT_ADMIN_NOTICE_SECURITY_UPDATE_SYSTEM.' <a href="'.$this->data['crpatchlink'].'">'.NETCAT_ADMIN_NOTICE_MORE.'</a>';
        $type = 'alarm';

        return array('text' => $text, 'type' => $type);
    }

    /**
     * Техническая поддержка
     */
    public function check_support() {
        if (!$this->data['support']) return false;

        $lic = $this->core->get_settings('ProductNumber');
        $link = $this->data['linkcopy'] ? $this->data['linkcopy'] : 'https://netcat.ru/forclients/my/copies/';
        $text = sprintf(NETCAT_ADMIN_NOTICE_SUPPORT_EXPIRED, $lic).' <a href="'.$link.'">'.NETCAT_ADMIN_NOTICE_PROLONG.'</a>';
        $type = 'attention';

        return array('text' => $text, 'type' => $type);
    }

    public function check_cron() {
        //$diff_last_run = $this->core->db->get_var("SELECT MIN(UNIX_TIMESTAMP() - `Cron_Launch`)  FROM `CronTasks`");
        //if ( $diff_last_run < 7*24*60*60 ) return false;

        $text = NETCAT_ADMIN_NOTICE_CRON;
        $type = 'recommend';
        $r = array('text' => $text, 'type' => $type);

        if ($this->core->modules->get_by_keyword('stats', 0)) {
            //if ( $this->core->db->get_var("SELECT `Log_ID` FROM `Stats_Log` LIMIT 1") ) return $r;
        }
        if ($this->core->modules->get_by_keyword('subscriber', 0)) {
            //if ( $this->core->db->get_var("SELECT `ID` FROM `Subscriber_Message` LIMIT 1")) return $r;
            //if ( $this->core->db->get_var("SELECT `ID` FROM `Subscriber_Prepared` LIMIT 1")) return $r;
        }
        if ($this->core->modules->get_by_keyword('search', 0)) {
            //$itable = $this->core->modules->get_vars('search', 'INDEX_TABLE');
            //$last = $this->core->db->get_var("SELECT UNIX_TIMESTAMP() - MAX(UNIX_TIMESTAMP(Created)) from Message".$itable);
            //if ( $last > 7*24*60*60 ) return $r;
        }
        if ($this->core->modules->get_by_keyword('banner', 0)) {
            //if ( $this->core->db->get_var("SELECT `Log_ID` FROM `Banner_Log` LIMIT 1") ) return $r;
        }
        return false;
    }

    public function check_writeable() {
        $text = NETCAT_ADMIN_NOTICE_RIGHTS;
        $type = 'attention';

        $path = $this->core->DOCUMENT_ROOT.$this->core->SUB_FOLDER.$this->core->HTTP_FILES_PATH;
        if (!is_dir($path) || !is_writable($path)) {
            return array('text' => $text.$this->core->HTTP_FILES_PATH, 'type' => $type);
        }

        if (!is_dir($this->core->TMP_FOLDER) || !is_writable($this->core->TMP_FOLDER)) {
            return array('text' => $text.$this->core->HTTP_ROOT_PATH.'tmp/', 'type' => $type);
        }

        $path = $this->core->DOCUMENT_ROOT.$this->core->SUB_FOLDER.$this->core->HTTP_CACHE_PATH;
        if (!is_dir($path) || !is_writable($path)) {
            return array('text' => $text.$this->core->HTTP_CACHE_PATH, 'type' => $type);
        }

        $path = $this->core->DOCUMENT_ROOT.$this->core->SUB_FOLDER.$this->core->HTTP_DUMP_PATH;
        if (!is_dir($path) || !is_writable($path)) {
            return array('text' => $text.$this->core->HTTP_DUMP_PATH, 'type' => $type);
        }
    }

    /**
     * Проверяем на включенный php safe_mode
     */
    public function check_safe_mode() {
        if (!ini_get('safe_mode')) return false;

        $text = NETCAT_ADMIN_NOTICE_SAFE_MODE;
        $type = 'attention';

        return array('text' => $text, 'type' => $type);
    }

    /**
     * Посылка запроса на неткэт.ру
     * @return int следующий патч
     */
    public function update($show_on_error = false) {

        $nc_core = nc_Core::get_object();

        $system_env = $nc_core->get_settings();

        $installed_modules = $nc_core->db->get_col("SELECT `Keyword` FROM `Module`");

        $all_sites = '';
        $cats = $nc_core->catalogue->get_all();
        if ($cats)
                foreach ($cats as $v) {
                if ($v['Domain']) $all_sites .= $v['Domain'].',';
                if ($v['Mirrors'])
                        $all_sites .= str_replace(array("\n\r", "\r", "\n"), ',', $v['Mirrors']).',';
            }

        $url = "check.netcat.ru";

        $params['SystemID']      = $system_env['SystemID'];
        $params['VersionNumber'] = $system_env['VersionNumber'];
        $params['unicode']       = $nc_core->NC_UNICODE;
        $params['lastpatch']     = $nc_core->db->get_var("SELECT `Patch_Name` FROM `Patch` ORDER BY `Patch_Name` DESC LIMIT 1");
        $params['build']         = $system_env['LastPatchBuildNumber'];
        $params['patchType']     = $system_env['LastPatchType'];
        $params['host']          = $_SERVER['HTTP_HOST'];
        $params['product']       = $system_env['ProductNumber'];
        $params['useremail']     = urlencode($system_env['NotificationEmail'] ?: $system_env['SpamFromEmail']);
        $params['adminfolder']   = urlencode($nc_core->ADMIN_FOLDER);
        $params['userip']        = $_SERVER['REMOTE_ADDR'];
        $params['useragent']     = urlencode($_SERVER['HTTP_USER_AGENT']);
        $params['modules']       = join(',', $installed_modules);
        $params['code']          = $system_env['Code'];
        $params['systeminfo']    = $_SERVER['SERVER_SOFTWARE'];
        $params['allsites']      = $all_sites;
        $params['owner']         = urlencode($nc_core->get_settings('Owner'));

        $pr = array();
        foreach ($params as $k => $v)
            $pr[] .= urlencode($k).'='.urldecode($v);
        $data = join('&', $pr);

        $options = array(
                "http" => array(
                        "method" => "POST",
                        "header" => "Content-type: application/x-www-form-urlencoded\n"
                                  . "Content-Length: ".strlen($data)."\n",
                        "content" => $data,
                        'timeout' => nc_AdminNotice::NC_CONNECT_TIMEOUT
                )
        );

        $options = nc_set_stream_proxy_params($options);
        $context = stream_context_create($options);
        $response = @file_get_contents('http://'.$url, false, $context);

        $nc_core->set_settings('PatchCheck', time());

        if ($response) {
            $nc_core->set_settings('AdminNoticeResponse', $response);
            $ar = @unserialize($response);
            $LAST_PATCH = $ar['next_patch'];
            $nc_core->set_settings('LastPatch', $LAST_PATCH);
            return $LAST_PATCH;
        }

        if ($show_on_error) {
            nc_print_status(TOOLS_PATCH_MSG_NOCONNECTION, 'error');
        }

        return null;
    }

    /**
     * Посылка запроса на неткэт.ру
     * @return int следующий патч
     */
    public function check_upgrade($to_system) {

        $nc_core = nc_Core::get_object();

        $system_env = $nc_core->get_settings();

        $url = "check.netcat.ru/upgrade.php";

        $installed_modules = $nc_core->db->get_col("SELECT `Keyword` FROM `Module`");

        $all_sites = '';
        $cats = $nc_core->catalogue->get_all();
        if ($cats)
                foreach ($cats as $v) {
                if ($v['Domain']) $all_sites .= $v['Domain'].',';
                if ($v['Mirrors'])
                        $all_sites .= str_replace(array("\n\r", "\r", "\n"), ',', $v['Mirrors']).',';
            }

        $params['SystemID'] = $system_env['SystemID'];
        $params['VersionNumber'] = $system_env['VersionNumber'];
        $params['unicode'] = $nc_core->NC_UNICODE;
        $params['lastpatch'] = $nc_core->db->get_var("SELECT `Patch_Name` FROM `Patch` ORDER BY `Patch_Name` DESC LIMIT 1");
        $params['host'] = $_SERVER['HTTP_HOST'];
        $params['product'] = $system_env['ProductNumber'];
        $params['useremail'] = urlencode($system_env['NotificationEmail'] ?: $system_env['SpamFromEmail']);
        $params['adminfolder'] = urlencode($nc_core->ADMIN_FOLDER);
        $params['userip'] = $_SERVER['REMOTE_ADDR'];
        $params['useragent'] = urlencode($_SERVER['HTTP_USER_AGENT']);
        $params['modules'] = join(',', $installed_modules);
        $params['code'] = $system_env['Code'];
        $params['systeminfo'] = $_SERVER['SERVER_SOFTWARE'];
        $params['allsites'] = $all_sites;
        $params['tosystem'] = $to_system;
        $params['owner']    = urlencode($nc_core->get_settings('Owner'));

        if (!$params['product']) {
            nc_print_status(TOOLS_UPGRADE_ERR_NO_PRODUCTNUMBER, 'error');
            return $result = 0;
        }

        $pr = array();
        foreach ($params as $k => $v)
            $pr[] .= urlencode($k).'='.urldecode($v);
        $data = join('&', $pr);


        $options = array(
                "http" => array(
                        "method" => "POST",
                        "header" => "Content-type: application/x-www-form-urlencoded\n"
                                  . "Content-Length: ".strlen($data)."\n",
                        "content" => $data,
                        'timeout' => nc_AdminNotice::NC_CONNECT_TIMEOUT
                )
        );

        $options = nc_set_stream_proxy_params($options);
        $context = stream_context_create($options);
        $response = @file_get_contents('http://'.$url, false, $context);

        if ($response) {
            $ar = @unserialize($response);
            $error = $ar['error'];
            if ($ar['code']) {
                $nc_core->set_settings('Code', $ar['code']);
                self::update();
            }
            switch ($error) {
                case 0:
                    $result = 1;
                    break;
                case 1:
                    nc_print_status(TOOLS_UPGRADE_ERR_INVALID_PRODUCTNUMBER, 'error');
                    $result = 0;
                    break;
                case 2:
                    nc_print_status(TOOLS_UPGRADE_ERR_NO_MATCH_HOST, 'error');
                    $result = 0;
                    break;
                case 3:
                    nc_print_status(TOOLS_UPGRADE_ERR_NO_ORDER, 'error');
                    $result = 0;
                    break;
                case 4:
                    nc_print_status(TOOLS_UPGRADE_ERR_NOT_PAID, 'error');
                    $result = 0;
                    break;
            }
        } else {
            nc_print_status(TOOLS_PATCH_MSG_NOCONNECTION, 'error');
            $result = 0;
        }
        return $result;
    }

}