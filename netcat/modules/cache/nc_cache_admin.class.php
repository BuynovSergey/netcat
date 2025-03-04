<?php

class nc_cache_admin {

    protected $db, $UI_CONFIG, $ADMIN_TEMPLATE;
    protected $EFFICIENCY_LOW, $EFFICIENCY_MID;

    public function __construct() {
        global $db, $UI_CONFIG, $MODULE_VARS, $ADMIN_PATH, $ADMIN_TEMPLATE, $MODULE_FOLDER;

        // global variables to internal
        $this->db = &$db;
        $this->UI_CONFIG = $UI_CONFIG;
        $this->EFFICIENCY_LOW = isset($MODULE_VARS['cache']['EFFICIENCY_LOW']) ?: 0;
        $this->EFFICIENCY_MID = isset($MODULE_VARS['cache']['EFFICIENCY_MID']) ?: 0;
        $this->ADMIN_PATH = $ADMIN_PATH;
        $this->ADMIN_TEMPLATE = $ADMIN_TEMPLATE;
        $this->MODULE_FOLDER = nc_module_folder();
        // superglobal variable
        $this->POST = $_POST;

        // this function must be called only from cache/admin.php file
        $debug_backtrace = debug_backtrace();
        // get file from calling this method
        $deb_value = $debug_backtrace[0];
        // validate file permission
        /* if (
          !( str_replace( array("/", "\\"), "/", $deb_value['file']) == str_replace( array("/", "\\"), "/", $this->MODULE_FOLDER."cache/admin.php") )
          ) {
          throw new Exception (NETCAT_MODULE_CACHE_CLASS_UNRECOGNIZED_OBJECT_CALLING);
          } */

        // objects array
        $this->cache_essence = array(
                "list" => array("nc_cache_list", NETCAT_MODULE_CACHE_ADMIN_TYPE_LIST),
                "full" => array("nc_cache_full", NETCAT_MODULE_CACHE_ADMIN_TYPE_FULL),
                "browse" => array("nc_cache_browse", NETCAT_MODULE_CACHE_ADMIN_TYPE_BROWSE),
                "function" => array("nc_cache_function", NETCAT_MODULE_CACHE_ADMIN_TYPE_FUNCTION),
                "calendar" => array("nc_cache_calendar", NETCAT_MODULE_CACHE_ADMIN_TYPE_CALENDAR)
        );

        return;
    }

    public function settings() {
        $nc_core = nc_Core::get_object();
        $Catalogue_ID = (int) $this->POST['Catalogue_ID'];

        $catalogues = $this->db->get_results("SELECT `Catalogue_ID`, `Catalogue_Name` FROM `Catalogue` ORDER BY `Priority`", ARRAY_A);

        if (empty($catalogues)) {
			// no data
			nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_NONE, 'info');
			// return
			return false;
		}

        if (!$Catalogue_ID) {
            $Catalogue_ID = $catalogues[0]['Catalogue_ID'];
        }

        $memcached_enabled = class_exists('Memcache');

        $settings = $this->db->get_row("SELECT *, UNIX_TIMESTAMP(`Audit_Begin`) AS Audit_Begin
      FROM `Cache_Settings` WHERE `Catalogue_ID` = '".$Catalogue_ID."'", ARRAY_A);

        echo "<form method='post' action='admin.php' style='padding:0; margin:0;'>\n".
        "<fieldset>\n".
        "<legend>\n".
        "".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CATALOGUE."\n".
        "</legend>\n".
        "<div style='margin:10px 0; _padding:0;'>\n".
        "<select name='Catalogue_ID' onchange='this.form.submit();' style='width:50%'>\n";
        foreach ($catalogues AS $value) {
            echo "<option value='".$value['Catalogue_ID']."' ".($value['Catalogue_ID'] == $Catalogue_ID ? "selected" : "").">".$value['Catalogue_ID'].": ".$value['Catalogue_Name']."</option>\n";
        }
        echo "</select>\n".
        "</div>\n".
        "</fieldset>\n".
        "</form>\n";

        echo "<form method='post' action='admin.php' id='SetCacheMainSettings' style='padding:0; margin:0;'>\n".
        "<fieldset>\n".
        "<legend>\n".
        "".NETCAT_MODULE_CACHE_ADMIN_MAINSETTINGS_TITLE."\n".
        "</legend>\n".
        "<div style='margin:10px 0; _padding:0;'>\n".
        "<table class='admin_table' style='width:50%; ; border:none;'>\n".
        "<col style='width:60%'/><col style='width:20%'/><col style='width:20%'/>\n".
        "<tr>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CACHE_TYPE."</td>\n".
        "<td style='; text-align:center'>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CACHE_OFF."</td>\n".
        "<td style='; text-align:center'>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_CACHE_ON."</td>\n".
        "</tr>\n";
        foreach ($this->cache_essence AS $key => $value) {
            echo "<tr>\n".
            "<td>".$value[1]."</td>\n".
            "<td style='; text-align:center'><input type='radio' name='Status_".$key."' value='0'".($settings['Status_'.$key] == 0 ? " checked" : "")."></td>\n".
            "<td style='; text-align:center'><input type='radio' name='Status_".$key."' value='1'".($settings['Status_'.$key] == 1 ? " checked" : "")."></td>\n".
            "</tr>\n";
        }
        echo "</table>\n".
        "</div>\n".
        "</fieldset>\n";


        // memcached
        echo
        "<fieldset>\n".
        "<legend>\n".
        "".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED."\n".
        "</legend>\n".
        "<div style='margin:10px 0; padding:0;'>\n".
        ( $memcached_enabled ? "" : NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_DOESNT_EXIST."<br/><br/>").
        "<input type='checkbox' name='MemcacheEnabled'".( $settings['IO_Interface'] == 'memcache' ? " checked" : "")." ".( $memcached_enabled ? "" : "disabled")."> ".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_ON."<br/><br/>\n".
        "</div>\n".
        "<div style='margin:10px 0; _padding:0;'>\n".
        "<table  class='admin_table' style='width:100%; ; border:none;'>\n".
        "<col style='width:35%'/><col style='width:65%'/>\n".
        "<tr>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_HOST."</td>\n".
        "<td><input type='text' name='Memcached_Host' style='width:100%' value='".($settings['Memcached_Host'] ? $settings['Memcached_Host'] : "localhost")."' /></td>\n".
        "</tr>\n".
        "<tr>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_PORT."</td>\n".
        "<td><input type='text' name='Memcached_Port' style='width:100%' value='".($settings['Memcached_Port'] ? $settings['Memcached_Port'] : "0")."' /></td>\n".
        "</tr>\n".
        "</table>\n".
        "</div>\n".
        "</fieldset>\n";

        // main settings block
        echo "<input type='hidden' name='Catalogue_ID' value='".$Catalogue_ID."'>\n".
        "<fieldset>\n".
        "<legend>\n".
        "".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT."\n".
        "</legend>\n";

        $AuditEnd = strtotime("+".$settings['Audit_Time']." hours", $settings['Audit_Begin']);
        echo "<div style='margin:10px 0; _padding:0;'>\n".
        "<input type='checkbox' name='CacheAuditMode'".($AuditEnd > time() ? " checked" : "")."> ".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_ON."<br/><br/>\n".
        "</div>\n".
        "<div style='margin:10px 0; _padding:0;'>\n".
        "<table class='admin_table' style='width:100%; ; border:none;'>\n".
        "<col style='width:35%'/><col style='width:35%'/><col style='width:30%'/>\n".
        "<tr>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_BEGIN."</td>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_END."</td>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_TIME."</td>\n".
        "</tr>\n".
        "<tr>\n".
        "<td>".($settings['Audit_Begin'] && $AuditEnd > time() ? date("d-m-Y H:i:s", $settings['Audit_Begin']) : NETCAT_MODULE_CACHE_ADMIN_SETTINGS_AUDIT_SAVE_TIME)."</td>\n".
        "<td>".($settings['Audit_Begin'] && $AuditEnd > time() ? date("d-m-Y H:i:s", $AuditEnd) : NETCAT_MODULE_CACHE_ADMIN_AUDIT_NODATA)."</td>\n".
        "<td><input type='text' name='Audit_Time' style='width:100%' value='".(int) $settings['Audit_Time']."'></td>\n".
        "</table>\n".
        "</div>\n".
        "</fieldset>\n";

        // quota settings block
        echo "<fieldset>\n".
        "<legend>\n".
        "".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_TITLE."\n".
        "</legend>\n".
        "<div style='margin:10px 0; _padding:0;'>\n".
        "<table class='admin_table' style='width:100%; ; border:none;'>\n".
        "<col style='width:25%'/><col style='width:30%'/><col style='width:20%'/><col style='width:20%'/><col style='width:5%'/>\n".
        "<tr>\n".
        "<td rowspan='2'>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_MAXSIZE_HEADER_CACHE."</td>\n".
        "<td rowspan='2'>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_MAXSIZE_HEADER_SIZE."</td>\n".
        "<td style='; text-align:center' colspan='2'>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_OVERDRAFT."</td>\n".
        "<td style='; text-align:center;' rowspan='2'><div class='icons icon_delete' title='".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_MAXSIZE_HEADER_CLEAR."'></div></td>\n".
        "</tr>\n".
        "<tr>\n".
        "<td style='; text-align:center'>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_OVERDRAFT_NOCACHE."</td>\n".
        "<td style='; text-align:center'>".NETCAT_MODULE_CACHE_ADMIN_SETTINGS_QUOTA_OVERDRAFT_DROP."</td>".
        "</tr>";

        $cache_clear = $this->db->get_results("SELECT `Essence`, COUNT(`ID`) AS rows FROM `Cache_Clear` GROUP BY `Essence`", ARRAY_A);
        if (!empty($cache_clear)) {
            $cache_clear_data = array();
            foreach ($cache_clear AS $value) {
                $cache_clear_data[$value['Essence']] = $value['rows'];
            }
        }
        foreach ($this->cache_essence AS $key => $value) {
            echo "<tr>\n".
            "<td>".$value[1]."</td>\n".
            "<td><input type='text' name='Quota_".$key."' style='width:100%' value='".(int) $settings['Quota_'.$key]."'></td>\n".
            "<td style='; text-align:center'><input type='radio' name='Overdraft_".$key."' value='1'".($settings['Overdraft_'.$key] == 1 || !$cache_clear_data[$key] ? " checked" : (!$settings['Overdraft_'.$key] ? " checked" : ""))."></td>\n".
            "<td style='; text-align:center'><input type='radio' name='Overdraft_".$key."' value='2'".($settings['Overdraft_'.$key] == 2 && $cache_clear_data[$key] ? " checked" : "")."".($cache_clear_data[$key] ? "" : " disabled")."></td>\n".
            "<td style='; text-align:center;'><input type='checkbox' name='CacheClear_".$key."' value='1'></td>\n".
            "</tr>\n";
        }

        echo "</table>\n".
        "</div>\n\n";

        // admin buttons
        $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_CACHE_ADMIN_MAINSETTINGS_SAVE_BUTTON,
                "action" => "mainView.submitIframeForm('SetCacheMainSettings')"
        );

        echo "<input type='hidden' name='phase' value='2'>\n".
        "</fieldset>\n".
        $nc_core->token->get_input().
        "</form>\n";

        return;
    }

    public function settingsSave() {

        require_once __DIR__ . '/modules/nc_cache_calendar.class.php';

        $Catalogue_ID = $this->POST['Catalogue_ID'];
        if (!(is_numeric($Catalogue_ID) && $Catalogue_ID > 0)) {
            return false;
        }

        $_fields = $this->db->get_col("SHOW COLUMNS FROM `Cache_Settings`");

        if (!empty($_fields) && !empty($this->POST)) {
            $query_arr = array();
            foreach ($this->POST AS $key => $value) {
                if (!in_array($key, $_fields, true) || $key === 'ID') {
                    continue;
                }
                // append to query array
                $query_arr[] = "`{$key}` = '{$this->db->escape($value)}'";
            }
            if (empty($query_arr)) {
                return false;
            }
            // existence
            $SettingsExist = $this->db->get_var("SELECT `ID` FROM `Cache_Settings` WHERE `Catalogue_ID` = '".$Catalogue_ID."'");
            // concat query string
            // memcached
            $query_arr[] = "`IO_Interface` = 'file'";
            if ($this->POST['MemcacheEnabled']) {
                // проверка адреса/порта сервера
                $memcache = new Memcache();
                if (!@$memcache->connect($this->POST['Memcached_Host'], $this->POST['Memcached_Port'])) {
                    nc_print_status(NETCAT_MODULE_CACHE_ADMIN_SETTINGS_MEMCACHED_ERROR, "info");
                } else {
                    $query_arr[] = "`IO_Interface` = 'memcache'";
                }
            }

            // audit
            if ($this->POST['CacheAuditMode']) {
                if ($this->POST['Audit_Time'] > 0 && is_numeric($this->POST['Audit_Time'])) {
                    $query_arr[] = "`Audit_Begin` = IF( UNIX_TIMESTAMP(`Audit_Begin`) + ".(int) $this->POST['Audit_Time']." * 60 * 60 < UNIX_TIMESTAMP( NOW() ), NOW(), `Audit_Begin`)";
                }
            } else {
                $query_arr[] = "`Audit_Begin` = ''";
            }
            $update_expression = implode(', ', $query_arr);
            // save settings
            if ($SettingsExist) {
                $this->db->query("UPDATE `Cache_Settings` SET {$update_expression} WHERE `Catalogue_ID` = '{$Catalogue_ID}'");
            } else {
                $this->db->query("INSERT INTO `Cache_Settings` SET {$update_expression}");
            }

            // update quota
            foreach ($this->cache_essence AS $essence => $object) {
                // init object
                $currObject = call_user_func(array($object[0], "getObject"));
                // update quota size for this object (in bytes)
                try {
                    // clear cache folder or update quota
                    if ($this->POST["CacheClear_".$essence]) {
                        $total_bytes = $currObject->dropCache();
                        // show info
                        nc_print_status(str_replace(array("%SIZE", "%TYPE"), array(nc_bytes2size($total_bytes), $object[1]), NETCAT_MODULE_CACHE_ADMIN_SETTINGS_INFO_DELETED), "info");
                    }
                } catch (Exception $e) {
                    // for debug
                    $currObject->errorMessage($e);
                }
            }
        }
        // return changes status
        return $this->db->rows_affected;
    }

    public function info() {
        // main information block
        echo "<fieldset>\n".
        "<legend>\n".
        "".NETCAT_MODULE_CACHE_ADMIN_MAININFO_TITLE."\n".
        "</legend>\n".
        "<form method='post' action='admin.php' id='GetCacheMainInfo' style='padding:0; margin:0;'>\n".
        "<div style='margin:10px 0; _padding:0;'>\n".
        "<table  class='admin_table' style='width:100%; ; border:none;'>\n".
        "<col style='width:25%'/><col style='width:25%'/><col style='width:25%'/><col style='width:25%'/>\n".
        "<tr>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_CACHE."</td>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_FILES."</td>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_DIRS."</td>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_MAININFO_HEADER_SIZE."</td>\n".
        "</tr>\n";

        $total_cache_count = array();

        require_once __DIR__ . '/modules/nc_cache_calendar.class.php';

        foreach ($this->cache_essence AS $object) {
            // init objects
            $currObject = call_user_func(array($object[0], "getObject"));
            // update objects stats
            $cache_count = $currObject->dirStat();
            // count total
            foreach ($cache_count AS $key => $value) {
                $total_cache_count[$key][] = $cache_count[$key];
            }
            // info string
            echo "<tr>\n".
            "<td>".$object[1]."</td>\n".
            "<td>".$cache_count[1]."</td>\n".
            "<td>".$cache_count[0]."</td>\n".
            "<td>".nc_bytes2size($cache_count[2])."</td>\n".
            "</tr>";
        }
        // total string
        echo "<tr>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_MAININFO_TOTAL."</td>\n".
        "<td>".array_sum($total_cache_count[1])."</td>\n".
        "<td>".array_sum($total_cache_count[0])."</td>\n".
        "<td>".nc_bytes2size(array_sum($total_cache_count[2]))."</td>\n".
        "</tr>".
        "</table>\n".
        "</div>\n\n";

        $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_CACHE_ADMIN_MAININFO_UPDATE_BUTTON,
                "action" => "mainView.submitIframeForm('GetCacheMainInfo')"
        );

        echo "<input type='hidden' name='phase' value='3'>\n".
        "<input type='hidden' name='page' value='info'>\n".
        "</form>\n".
        "</fieldset>\n";

        // main information block
        echo "<fieldset>\n".
        "<legend>\n".
        "".NETCAT_MODULE_CACHE_ADMIN_MAININFO_CLEAR_TABLE."\n".
        "</legend>\n";

        $cache_clear = $this->db->get_results("SELECT `Essence`, COUNT(`ID`) AS rows, ROUND( AVG(`Efficiency`), 2 ) AS Efficiency FROM `Cache_Clear` GROUP BY `Essence`", ARRAY_A);

        if (!empty($cache_clear)) {
            $cache_clear_data = array();
            foreach ($cache_clear AS $value) {
                $cache_clear_data[$value['Essence']] = array($value['rows'], $value['Efficiency']);
            }
        }

        echo "<div style='margin:10px 0; _padding:0;'>\n".
        "<table  class='admin_table' style='width:100%; ; border:none;'>\n".
        "<col style='width:40%'/><col style='width:40%'/><col style='width:20%'/>\n".
        "<tr>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_CACHE."</td>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_MAININFO_CACHE_COUNT."</td>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_MAININFO_CACHE_AVERAGE_EFFICIENCY."</td>\n".
        "</tr>";

        $total_clear_count = array();
        foreach ($this->cache_essence AS $key => $value) {
            echo "<tr>\n".
            "<td>".$value[1]."</td>\n".
            "<td>".(int) $cache_clear_data[$key][0]."</td>\n".
            "<td>".($cache_clear_data[$key][1] ? $cache_clear_data[$key][1] : 0)."</td>\n".
            "</tr>";
            // count total
            if (!empty($cache_clear) && !empty($cache_clear_data[$key])) {
                foreach ($cache_clear_data[$key] AS $k => $v) {
                    $total_clear_count[$k][] = $v;
                }
            }
        }
        // total string
        echo "<tr>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_MAININFO_TOTAL."</td>\n".
        "<td>".(!empty($total_clear_count) ? array_sum($total_clear_count[0]) : 0)."</td>\n".
        "<td>".(!empty($total_clear_count) ? round(array_sum($total_clear_count[1]) / count($total_clear_count[0]), 2) : 0)."</td>\n".
        "</tr>\n".
        "</table>\n";

        if (!empty($cache_clear)) {
            echo "<form method='post' id='DropCacheClearData' action='admin.php' style='padding:0; margin:0;'>\n";
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_CACHE_ADMIN_MAININFO_DROP_CLEAR_BUTTON,
                "action" => "mainView.submitIframeForm('DropCacheClearData')",
                "align" => "left",
                "red_border" => true,
            );
            echo "<input type='hidden' name='phase' value='4'>\n".
            "<input type='hidden' name='page' value='info'>\n".
            "</form>";
        }
        echo "</div>\n".
        "</fieldset>\n";

        return;
    }

    public function auditInfo() {

        // objects array
        $cache_essence = $this->cache_essence;

        $cache = $this->POST['cache'];
        $cache_sortby = $this->POST['cache_sortby'];

        if (!$cache || !$cache_essence[$cache]) {
            $cache = array_shift(array_keys($cache_essence));
        }

        if (!$cache_sortby || !in_array($cache_sortby, array('desc', 'asc'), true)) {
            $cache_sortby = 'desc';
        }

        $audit_res = $this->db->get_results("SELECT ca.`Catalogue_ID`, ca.`Subdivision_ID`, ca.`Sub_Class_ID`, cat.`Catalogue_Name`, sub.`Subdivision_Name`, sc.`Sub_Class_Name`, ( SUM(ca.`Readed`) / SUM(ca.`Attempt_Read`) ) AS `Efficiency`
      FROM `Cache_Audit` AS ca
      LEFT JOIN `Catalogue` AS cat ON ca.`Catalogue_ID` = cat.`Catalogue_ID`
      LEFT JOIN `Subdivision` AS sub ON ca.`Subdivision_ID` = sub.`Subdivision_ID`
      LEFT JOIN `Sub_Class` AS sc ON ca.`Sub_Class_ID` = sc.`Sub_Class_ID`
      WHERE ca.`Essence` = '".$this->db->escape($cache)."'
      GROUP BY ca.`Catalogue_ID`, ca.`Subdivision_ID`, ca.`Sub_Class_ID`
      ORDER BY `Efficiency` ".strtoupper($cache_sortby).", cat.`Catalogue_Name`, sub.`Subdivision_Name`, sc.`Sub_Class_Name`", ARRAY_A);


        echo "<form method='post' id='GetCacheAuditInfo' action='admin.php' style='padding:0; margin:0;'>\n".
        "<fieldset>\n".
        "<legend>\n".
        "".NETCAT_MODULE_CACHE_ADMIN_CACHE."\n".
        "</legend>\n".
        "<div style='margin:10px 0; _padding:0;'>".
        "<table class='admin_table' style='width:50%; ; border:none;'>\n".
        "<col style='width:70%'/><col style='width:30%'/>\n".
        "<tr>\n".
        "<td>".NETCAT_MODULE_CACHE_ADMIN_MAININFO_TYPE."</td>\n".
        "<td style='; text-align:center'>".NETCAT_MODULE_CACHE_ADMIN_AUDIT_COUNT."</td>\n".
        "</tr>\n".
        "<tr>\n".
        "<td>\n".
        "<select name='cache' onchange='this.form.submit();' style='width:100%'>\n";
        foreach ($this->cache_essence AS $key => $value) {
            echo "<option value='".$key."' ".($cache == $key ? "selected" : "").">".$value[1]."</option>\n";
        }
        echo "</select>\n".
        "</td>".
        "<td style='; text-align:center'>".$this->db->num_rows."</td>\n".
        "</tr>\n".
        "</table>\n".
        "<input type='hidden' id='cache_sortby' name='cache_sortby' value='".$cache_sortby."'>\n".
        "<input type='hidden' name='phase' value='5'>\n".
        "<input type='hidden' name='page' value='audit'>\n".
        "</div>\n".
        "</fieldset>\n";

        $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_CACHE_ADMIN_MAININFO_UPDATE_BUTTON,
                "action" => "mainView.submitIframeForm('GetCacheAuditInfo')"
        );

        echo "<fieldset>\n".
        "<legend>\n".
        "".NETCAT_MODULE_CACHE_ADMIN_AUDIT_DATA."\n".
        "</legend>\n";

        echo "<div style='margin:10px 0; _padding:0;'>".
        "<table class='admin_table' style='width:100%; ; border:none;'>\n".
        "<col style='width:3%'/><col style='width:27%'/><col style='width:3%'/><col style='width:27%'/><col style='width:3%'/><col style='width:27%'/><col style='width:10%'/>\n".
        "<tr>\n".
        "<td colspan='2'>".NETCAT_MODULE_CACHE_ADMIN_AUDIT_CATALOGUE."</td>\n".
        "<td colspan='2'>".NETCAT_MODULE_CACHE_ADMIN_AUDIT_SUBDIVISION."</td>\n".
        "<td colspan='2'>".NETCAT_MODULE_CACHE_ADMIN_AUDIT_SUBCLASS."</td>\n".
        "<td>".(!empty($audit_res) ? "<a href='#' onclick=\"document.getElementById('cache_sortby').value=(document.getElementById('cache_sortby').value=='desc' ? 'asc' : 'desc'); document.getElementById('GetCacheAuditInfo').submit(); return false;\">" : "").NETCAT_MODULE_CACHE_ADMIN_AUDIT_EFFICIENCY.(!empty($audit_res) ? "</a>" : "")."</td>\n".
        "</tr>\n";

        if (!empty($audit_res)) {
            foreach ($audit_res AS $value) {
                switch (true) {
                    case $value['Efficiency'] < 0 || $value['Efficiency'] > 1:
                        $color = '#EEE';
                        break;
                    case $value['Efficiency'] < $this->EFFICIENCY_LOW:
                        $color = '#FCBEBE';
                        break;
                    case $value['Efficiency'] >= $this->EFFICIENCY_LOW && $value['Efficiency'] < $this->EFFICIENCY_MID:
                        $color = '#FFF295';
                        break;
                    default:
                        $color = '#E0FFDD';
                        break;
                }
                echo "<tr style='vertical-align:top'>\n".
                "<td><a href='".$this->ADMIN_PATH."catalogue/index.php?phase=2&amp;type=2&amp;CatalogueID=".$value['Catalogue_ID']."'>".$value['Catalogue_ID']."</a></td>\n".
                "<td>".$value['Catalogue_Name']."</td>\n".
                "<td>".($value['Subdivision_ID'] ? "<a href='".$this->ADMIN_PATH."subdivision/index.php?phase=5&amp;SubdivisionID=".$value['Subdivision_ID']."'>".$value['Subdivision_ID']."</a>" : "—")."</td>\n".
                "<td>".($value['Subdivision_ID'] ? $value['Subdivision_Name'] : "—")."</td>\n".
                "<td>".($value['Sub_Class_ID'] ? "<a href='".$this->ADMIN_PATH."subdivision/SubClass.php?phase=3&amp;SubClassID=".$value['Sub_Class_ID']."&amp;SubdivisionID=".$value['Subdivision_ID']."'>".$value['Sub_Class_ID']."</a>" : "—")."</td>\n".
                "<td>".($value['Sub_Class_ID'] ? $value['Sub_Class_Name'] : "—")."</td>\n".
                "<td style='background:".$color."'>".$value['Efficiency']."</td>\n".
                "</tr>";
            }
        } else {
            echo "<tr style='vertical-align:top'>\n".
            "<td colspan='7'>".NETCAT_MODULE_CACHE_ADMIN_AUDIT_NODATA."</td>\n".
            "</tr>\n";
        }

        echo "</table>\n".
        "</div>\n".
        "</fieldset>\n".
        "</form>\n";

        if (empty($audit_res)) {
            $audit_res_exist = $this->db->get_var("SELECT COUNT(*) FROM `Cache_Audit`");
        }

        if (!empty($audit_res_exist) || !empty($audit_res)) {
            echo "<form method='post' id='SaveCacheAuditInfo' action='admin.php' style='padding:0; margin:0;'>\n";
            $this->UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => NETCAT_MODULE_CACHE_ADMIN_AUDIT_SAVE_CLEAR_BUTTON,
                    "action" => "mainView.submitIframeForm('SaveCacheAuditInfo')",
                    "align" => "left"
            );
            echo "<input type='hidden' name='phase' value='6'>\n".
            "<input type='hidden' name='page' value='audit'>\n".
            "</form>\n".
            "<form method='post' id='ClearCacheAuditInfo' action='admin.php' style='padding:0; margin:0;'>\n";
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_CACHE_ADMIN_AUDIT_DROP_BUTTON,
                "action" => "mainView.submitIframeForm('ClearCacheAuditInfo')",
                "align" => "left",
                "red_border" => true,
            );
            echo "<input type='hidden' name='phase' value='7'>\n".
            "<input type='hidden' name='page' value='audit'>\n".
            "</form>\n";
        }

        return;
    }

    public function auditInfoToClear() {

        $this->db->query("TRUNCATE `Cache_Clear`");

        $audit_res = $this->db->get_results("SELECT `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID`, `Class_ID`, `Message_ID`, `Essence`, ( SUM(`Readed`) / SUM(`Attempt_Read`) ) AS `Efficiency`
      FROM `Cache_Audit`
      GROUP BY `Essence`, `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID`, `Class_ID`, `Message_ID`
      ORDER BY `Essence`, `Efficiency` DESC", ARRAY_A);

        foreach ($audit_res AS $value) {
            $this->db->query("INSERT INTO `Cache_Clear` ( `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID`, `Class_ID`, `Message_ID`, `Essence`, `Efficiency`) VALUES ('".join("', '", $value)."')");
        }
    }

    public function auditInfoClear() {

        $this->db->query("TRUNCATE `Cache_Audit`");

        return $this->db->rows_affected;
    }

    public function clearInfoDrop() {

        $this->db->query("TRUNCATE `Cache_Clear`");
        $this->db->query("UPDATE `Cache_Settings` SET `Overdraft_list` = 1, `Overdraft_full` = 1, `Overdraft_browse` = 1");

        return $this->db->rows_affected;
    }

}
?>