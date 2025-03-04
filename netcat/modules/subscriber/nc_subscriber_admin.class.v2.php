<?php

/**
 * Класс для работы с модулем в "админке"
 */
class nc_subscriber_admin {

    protected $db;
    protected $UI_CONFIG, $ADMIN_TEMPLATE, $MODULE_FOLDER, $ADMIN_PATH;
    protected $_settings;
    protected $core, $nc_subscriber, $tools;
    protected $_file1_id, $_file_id2, $_file_id3;
    protected $_files_id;

    public function __construct() {
        // global variables
        global $db, $UI_CONFIG, $MODULE_VARS, $ADMIN_PATH, $ADMIN_TEMPLATE;
        $this->core = nc_Core::get_object();
        // global variables to internal
        $this->db = & $db;
        $this->UI_CONFIG = & $UI_CONFIG;
        $this->ADMIN_PATH = $ADMIN_PATH;
        $this->ADMIN_TEMPLATE = $ADMIN_TEMPLATE;
        $this->MODULE_FOLDER = nc_module_folder();
        // superglobal variable
        $this->POST = nc_core('input')->fetch_get_post();

        $this->nc_subscriber = nc_subscriber::get_object();
        $this->tools = $this->nc_subscriber->tools;
        $this->_settings = $this->tools->get_settings();

        $this->_files_id[1] = (int)nc_array_value($this->POST, 'file1_id', 0);
        $this->_files_id[2] = (int)nc_array_value($this->POST, 'file2_id', 0);
        $this->_files_id[3] = (int)nc_array_value($this->POST, 'file3_id', 0);

        return;
    }

    /**
     * Функция выводит форму с настройками модуля
     */
    public function settings() {
        $catalogue_id = nc_core('catalogue')->id();
        echo "
    <form action='admin.php' method='post' enctype='multipart/form-data'>
      ".$this->catalogue_select_field(5)."
      <fieldset>
        <legend>" . NETCAT_MODULE_SUBSCRIBER_MAILER_SETTINGS . ":</legend>";
        $this->_checkbox(NETCAT_MODULE_SUBSCRIBER_MERGE_MAIL, 'MergeMail', $this->_settings['MergeMail']);
        $this->_input_text(NETCAT_MODULE_SUBSCRIBER_MAX_MAIL, 'MaxMailCount', $this->_settings['MaxMailCount']);
        $this->_input_text(NETCAT_MODULE_SUBSCRIBER_FROM_NAME, 'FromName', $this->_settings['FromName']);
        $this->_input_text(NETCAT_MODULE_SUBSCRIBER_FROM_EMAIL, 'FromEmail', $this->_settings['FromEmail']);
        $this->_input_text(NETCAT_MODULE_SUBSCRIBER_REPLY_TO, 'ReplyEmail', $this->_settings['ReplyEmail']);
        $this->_input_text(NETCAT_MODULE_SUBSCRIBER_TEST_EMAIL, 'TestEmail', $this->_settings['TestEmail']);
        $this->_input_text(NETCAT_MODULE_SUBSCRIBER_CHARSET, 'Charset', $this->_settings['Charset']);

        // вывод парметра "поле с адресом"
        echo " <div style='margin:10px 0; _padding:0;'>\n
      " . NETCAT_MODULE_SUBSCRIBER_EMAIL_FIELD . ": <br>";
        $fields = $this->db->get_results("SELECT  `Field_Name` AS `name`
                                        FROM `Field`
                                        WHERE `System_Table_ID` = '3'
                                        AND `TypeOfData_ID` = " . NC_FIELDTYPE_STRING . "
                                        AND `Format` LIKE 'email%'
                                        ORDER BY `Priority` ", ARRAY_A);
        if (empty($fields)) {
            echo NETCAT_MODULE_SUBSCRIBER_NONE_EMAIL_FIELD;
        } else {
            echo "<select name='EmailField'  style='width:50%'>\n";
            foreach ($fields as $field) {
                echo "<option value='" . $field['name'] . "' " . ($field['name'] == $this->_settings['EmailField'] ? "selected" : "") . ">" . $field['name'] . "</option>\n";
            }
            echo "</select>";
        }

        echo "</div>
      </fieldset>

      <fieldset>
        <legend>" . NETCAT_MODULE_SUBSCRIBER_SUBSCRIPTION_CONFIRM . ":</legend>
        <div style='margin:10px 0; _padding:0;'>\n
          " . NETCAT_MODULE_SUBSCRIBER_SUBSCRIPTION_CONFIRM . ": <br>
          <select name='ConfirmType'  style='width:50%'>\n
            <option value='0' " . ($this->_settings['ConfirmType'] == 0 ? "selected" : "") . ">" . NETCAT_MODULE_SUBSCRIBER_ONLY_UNREGISTERED . "</option>
            <option value='1' " . ($this->_settings['ConfirmType'] == 1 ? "selected" : "") . " >" . NETCAT_MODULE_SUBSCRIBER_FOR_ONE_SUBSCRIPTION . " </option>
            <option value='2' " . ($this->_settings['ConfirmType'] == 2 ? "selected" : "") . ">" . NETCAT_MODULE_SUBSCRIBER_ALWAYS . "</option>
          </select>\n
        </div>\n";

        $this->_input_text(NETCAT_MODULE_SUBSCRIBER_UNCONFIRMED_MAX_TIME, 'MaxTime', $this->_settings['MaxTime']);
        $this->_input_text(NETCAT_MODULE_SUBJECT, 'ConfirmSubject', $this->_settings['ConfirmSubject']);
        $this->_checkbox(NETCAT_MODULE_HTML_MAIL, 'ConfirmHTML', $this->_settings['ConfirmHTML']);
        $this->_textarea(NETCAT_MODULE_MAIL_BODY, 'ConfirmBody', $this->_settings['ConfirmBody'], 1);
        echo nc_mail_attachment_form('subscriber_confirm'.$catalogue_id);

        echo "
      </fieldset>

      <fieldset>
        <legend>" . NETCAT_MODULE_SUBSCRIBER_OTHER . "</legend>";
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE_FORM, 'FormSubscribe', $this->_settings['FormSubscribe'], 1);
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_TEXT_CONFIRM, 'TextConfirm', $this->_settings['TextConfirm'], 1);
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_TEXT_UNSCRIBE, 'TextUnscribe', $this->_settings['TextUnscribe'], 1);
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_TEXT_ERROR, 'TextError', $this->_settings['TextError'], 1);

        echo "
      </fieldset>
      <input type='submit' class='hidden'>
      <input type='hidden' name='phase' value='51' />
      </form>\n";

        $this->_js_resize();

        // admin buttons
        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_SUBSCRIBER_SAVE_BUTTON,
            "action" => "mainView.submitIframeForm()"
        );

        return 0;
    }

    /**
     * Функция для сохранения настройек модуля
     * @return <type>
     */
    public function settings_save() {
        $catalogue_id = nc_core('catalogue')->id();
        // валидация данных
        $settings = array('MergeMail', 'FromName', 'FromEmail', 'ReplyEmail', 'TestEmail', 'Charset', 'EmailField',
            'ConfirmType', 'ConfirmSubject', 'ConfirmBody', 'ConfirmHTML', 'ConfirmHTML', 'TextConfirm',
            'TextUnscribe', 'TextError', 'MaxMailCount', 'MaxTime', 'FormSubscribe');

        foreach ($settings as $v) {
            $this->core->set_settings($v, nc_array_value($this->POST, $v), 'subscriber', $catalogue_id);
        }
        $this->_settings = $this->tools->get_settings('', 1, $catalogue_id);

        nc_mail_attachment_form_save('subscriber_confirm'.$catalogue_id);

        return 0;
    }

    /**
     * Функция выводит статистику всех рассылок
     */
    public function stats() {
        // выполнение основого запроса на статистику всех рассылок
        $this->db->last_error = '';
        $res = $this->db->get_results("SELECT sm.`Mailer_ID`, sm.`MailCount`, sm.`LastSend`, sm.`Name`,
                                          COUNT(su.`User_ID`) AS `UserCount`
                                   FROM ( `Subscriber_Mailer` AS `sm`)
                                   LEFT JOIN `Subscriber_Subscription` AS `su` ON su.`Mailer_ID` = sm.`Mailer_ID`
                                   GROUP BY sm.`Mailer_ID` ", ARRAY_A);
        // если в ходе запроса произошла ошибка - то выбросить исключение
        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }
        // нет статистики
        if (empty($res)) {
            nc_print_status(NETCAT_MODULE_SUBSCRIBER_STATS_IS_EMPTY, 'info');
            return false;
        }

        echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr><td >
               <table border='0' cellpadding='4' cellspacing='1' width='100%'>
                <tr>
                 <td >ID</td>
                 <td >" . NETCAT_MODULE_SUBSCRIBER_MAILER_NAME . "</td>
                 <td  align='center'>" . NETCAT_MODULE_SUBSCRIBER_SUBSCRIBERS_COUNT . "</td>
                 <td  align='center'>" . NETCAT_MODULE_SUBSCRIBER_MAIL_SEND_COUNT . "</td>
                 <td  align='center'>" . NETCAT_MODULE_SUBSCRIBER_LAST_SEND . "</td>
                </tr>";


        foreach ($res as $row) {
            // существует ли полная статистика для рассылки?
            $row['exist_full'] = $this->db->get_var("SELECT COUNT(`Mailer_ID`) FROM `Subscriber_Log` WHERE `Mailer_ID` = '" . $row['Mailer_ID'] . "'");
            echo "<tr>\n" .
                "<td font size=-2>" . $row['Mailer_ID'] . "</font></td>\n" .
                ($row['exist_full'] ?
                    "<td ><a href='admin.php?phase=4&amp;mailer_id=" . $row['Mailer_ID'] . "'>" . $row['Name'] . "</a></td>\n" :
                    "<td >" . $row['Name'] . "</td>\n"
                ) .
                "<td  align='center'>" . $row['UserCount'] . "</td>\n" .
                "<td  align='center'>" . $row['MailCount'] . "</td>\n" .
                "<td  align='center'>" . $row['LastSend'] . "</td>\n" .
                "</tr>";
        }

        echo "</table> </td> </tr></table>";

        return 0;
    }

    /**
     * Фуннкция выводит полную статистику одной рассылки
     * @param int $mailer_id
     */
    public function stats_mailer($mailer_id) {
        $mailer = intval($mailer);

        $actions = array('mail'         => NETCAT_MODULE_SUBSCRIBER_MAIL_SEND,
                         'subscribe'    => NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE,
                         'confirm'      => NETCAT_MODULE_SUBSCRIBER_CONFIRM,
                         'unsubscribe'  => NETCAT_MODULE_SUBSCRIBER_UNSUBSCRIBE);

        $post = nc_core('input')->fetch_get_post();

        // проверка, есть ли статистика для рассылки

        $this->db->last_error = '';
        $res = $this->db->get_var("SELECT COUNT(`Mailer_ID`)
                                   FROM `Subscriber_Log`
                                   WHERE `Mailer_ID` = '" . intval($mailer_id) . "' ");

        // если в ходе запроса произошла ошибка - то выбросить исключение
        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }
        // нет статистики
        if (empty($res)) {
            nc_print_status(NETCAT_MODULE_SUBSCRIBER_STATS_IS_EMPTY, 'info');
            return false;
        }

        $name = $this->nc_subscriber->get($mailer_id, 'Name');
        echo "<fieldset>
                <legend>" .NETCAT_MODULE_SUBSCRIBER_FULL_STATS_MAILER . " <a href='admin.php?phase=2&mailer_id=" . $mailer_id . "'>" . $name . "</a>. </legend>";

        echo "<fieldset>
                <legend>".NETCAT_MODULE_SUBSCRIBER_STATS_FILTER.":
                </legend>";
        echo "<form action='admin.php' method='post'>
                <table class='admin_table'>
                <tr>
                  <td class='left'>".NETCAT_MODULE_SUBSCRIBER_STATS_FROM."</td>
                  <td class='right' style='padding-right: 4px;'>
                     ". $this->_input_date("" , 'date_from', date("d.m.Y", strtotime("-1 week")))."
                  </td>
                  <td style='padding: 4px;'>".NETCAT_MODULE_SUBSCRIBER_STATS_TO."</td>
                  <td style='padding: 4px;'>
                     ". $this->_input_date("" , 'date_to', date("d.m.Y"))."
                  </td>
                </tr>
                <tr>
                  <td class='left'>".NETCAT_MODULE_SUBSCRIBER_ACTION."</td>
                  <td class='right' colspan=3>
                    <select name='action_type'>
                    <option value='0'>" . NETCAT_MODERATION_LISTS_CHOOSE . "</option>";
                        foreach ($actions as $key => $value) {
                            echo "<option value='".$key."' " . ($post['action_type'] == $key ? "selected" : "") . ">" . $value . "</option>";
                        }
                    echo "</select>
                  </td>
                </tr>
                </table>
                <input type='hidden' name='mailer_id' value='" . $mailer_id . "' />
                <input type='hidden' name='phase' value='4' /><br/>
                <input style='background: #EEE; padding: 8px 6px 12px 6px; font-size: 15px; color: #333; border: 2px solid #1A87C2;' type='submit' name='submit' value='".NETCAT_MODULE_SUBSCRIBER_FILTER."' title='".NETCAT_MODULE_SUBSCRIBER_FILTER."' />
              </form>
              </fieldset>";

        if ($post['action_type']) {
            $search_query .= " AND `ActionType` = '".$this->db->escape($post['action_type'])."' ";
        }
        if ($post['date_from']) {
            $date_from = nc_prepare_data($post['date_from']);
            $search_query .= " AND DATE(`ActionTime`) >= '".$date_from."' ";
        }
        else{
            $search_query .= " AND `ActionTime` > DATE_SUB(CURDATE(), INTERVAL 1 WEEK) ";
        }
        if ($post['date_to']) {
            $date_to = nc_prepare_data($post['date_to']);
            $search_query .= " AND DATE(`ActionTime`) <= '".$date_to."' ";
        }

        // выполнение основого запроса на статистику всех рассылок
        $this->db->last_error = '';
        $res = $this->db->get_results("SELECT sl.`Mailer_ID`, sl.`User_ID`, sl.`ActionTime`, sl.`ActionType`,
                                   u.`Email`, u.`UserType`, u.`" . $this->_settings['EmailField'] . "`
                                   FROM `Subscriber_Log` AS `sl` , `User` AS `u`
                                   WHERE sl.`Mailer_ID` = '" . intval($mailer_id) . "'
                                   AND u.`User_ID` = sl.`User_ID`
                                   ".$search_query."
                                   ORDER BY sl.`ID` DESC ", ARRAY_A);

        // если в ходе запроса произошла ошибка - то выбросить исключение
        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }
        // нет статистики
        if (empty($res)) {
            nc_print_status(NETCAT_MODULE_SUBSCRIBER_STATS_NOTHING, 'info');
            return false;
        }

        echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr><td >
               <table border='0' cellpadding='4' cellspacing='1' width='100%'>
                <tr>
                 <td >" . NETCAT_MODULE_SUBSCRIBER_USER . "</td>
                 <td >" . NETCAT_MODULE_SUBSCRIBER_ACTION . "</td>
                 <td >" . NETCAT_MODULE_SUBSCRIBER_DATE_TIME . "</td>
                </tr>";


        foreach ($res as $row) {
            $user = (($row['UserType'] == 'normal') ? ("<a href='" . $this->ADMIN_PATH . "user/index.php?phase=4&UserID=" . $row['User_ID'] . "'>" . $row['Email'] . "</a>") :
                "" . NETCAT_MODULE_SUBSCRIBER_UNRESISTRED_USER . " (" . $row[$this->_settings['EmailField']] . ") ");

            $action = $actions[$row['ActionType']];
            echo "<tr>\n" .
                "<td >" . $user . "</td>\n" .
                "<td >" . $action . "</td>\n" .
                "<td >" . $row['ActionTime'] . "</td>\n" .
                "</tr>";
        }

        echo " </table>
          </td></tr> </table>";

        echo "<form action='admin.php' method='post'>
          <input type='hidden' name='mailer_id' value='" . $mailer_id . "' />
          <input type='hidden' name='phase' value='41' />
           </form>";

        echo "</fieldset>";
        // admin buttons
        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_SUBSCRIBER_CLEAR_BUTTON,
            "action" => "mainView.submitIframeForm('')",
            "red_border" => true,
        );

        return 0;
    }

    /**
     * Функция выводит список рассылок
     */
    public function mailer_list() {
        $nc_core = nc_Core::get_object();

        // валидация фильтров
        $filter_status = intval(nc_array_value($this->POST,'filter_status'));
        $filter_type = intval(nc_array_value($this->POST,'filter_type'));

        $this->db->last_error = '';
        $mailers = $this->db->get_results("SELECT sm.`Mailer_ID`, sm.`Name`, sm.`Type`, sm.`Active`,
                                              sc.`Sub_Class_Name`, sc.`Sub_Class_ID`, sc.`Subdivision_ID`, COUNT(us.`User_ID`) AS `Count`
                                        FROM (`Subscriber_Mailer` as sm)
                                        LEFT JOIN `Sub_Class` AS sc ON sc.`Sub_Class_ID` = sm.`Sub_Class_ID`
                                        LEFT JOIN `Subscriber_Subscription` as us  ON us.`Mailer_ID` = sm.`Mailer_ID`
                                        WHERE 1
                                        " . ($filter_status ? " AND sm.`Active` = '" . ($filter_status == 1 ? "1" : "0") . "'" : "") . "
                                        " . ($filter_type ? " AND sm.`Type` = '" . $filter_type . "'" : "") . "
                                        GROUP BY sm.`Mailer_ID` ", ARRAY_A);

        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }

        // add button
        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "add",
            "caption" => NETCAT_MODULE_SUBSCRIBER_MAILER_ADD,
            "location" => 'module.subscriber.mailer.add'
        );

        // если рассылок нет - то вывести плашку с сообщением, но не выходить из функции
        if (empty($mailers)) {
            nc_print_status(NETCAT_MODULE_MAILER_NO_ONE_MAILER, 'info');
        }

        // в любом случае нужно вывести "Выборка рассылок"
        echo "
        <fieldset>\n" .
            "<legend>" . NETCAT_MODULE_SUBSCRIBER_MAILER_FILTER . "</legend>\n" .
            "<form action='admin.php' method='post'>
            <table border='0'>
            <tr>
              <td>" . NETCAT_MODULE_SUBSCRIBER_MAILER_FILTER_TYPE . "</td>
          <td style='width:70%'>
            <select name='filter_type' style='width: 100%'>
              <option value='0' " . ($filter_type == 0 ? 'selected' : '') . ">" . NETCAT_MODULE_SUBSCRIBER_ALL . "</option>
              <option value='1' " . ($filter_type == 1 ? 'selected' : '') . ">" . NETCAT_MODULE_SUBSCRIBER_MAILER_CC . "</option>
              <option value='2' " . ($filter_type == 2 ? 'selected' : '') . ">" . NETCAT_MODULE_SUBSCRIBER_MAILER_PERIODICAL . "</option>
              <option value='4' " . ($filter_type == 4 ? 'selected' : '') . ">" . NETCAT_MODULE_SUBSCRIBER_MAILER_SERIAL . "</option>
              <option value='3' " . ($filter_type == 3 ? 'selected' : '') . ">" . NETCAT_MODULE_SUBSCRIBER_MAILER_SERVICE . "</option>
            </select></td>
        </tr>
        <tr>
          <td>" . NETCAT_MODULE_SUBSCRIBER_MAILER_FILTER_STATUS . "</td>
          <td>
            <select name='filter_status' style='width: 100%'>
              <option value='0' " . ($filter_status == 0 ? 'selected' : '') . ">" . NETCAT_MODULE_SUBSCRIBER_ALL . "</option>
              <option value='1' " . ($filter_status == 1 ? 'selected' : '') . ">" . NETCAT_MODULE_SUBSCRIBER_ONLY_ACTIVE . "</option>
              <option value='2' " . ($filter_status == 2 ? 'selected' : '') . ">" . NETCAT_MODULE_SUBSCRIBER_ONLY_UNACTIVE . "</option>
            </select></td>
        </tr>
        <tr>
          <td colspan='2' align='center'>
            <input type='submit' title='" . NETCAT_MODULE_SUBSCRIBER_FILTER . "' value='" . NETCAT_MODULE_SUBSCRIBER_FILTER . "' />
          </td>
        </table>
        <input type='hidden' name='phase' value='1' />
        </form>
        
        </fieldset></div>";

        // если нет рассылок - то не имеет смысла выводить таблицу
        if (empty($mailers)) {
            return 0;
        }

        echo "
      <form action='admin.php' method='post' name='mainForm' id='mainForm'>
      <input type='hidden' name='phase' value='11' />
      <input type='hidden' name='action_type' value='' />
      <table border='0' cellpadding='0' cellspacing='0' width='100%'>
        <tr><td >
            <table class='admin_table' width='100%'>
              <tr>
                <td >ID</td>
                <td >" . NETCAT_MODULE_SUBSCRIBER_MAILER_NAME . "</td>
                <td >" . CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION . "</td>
                <td >" . NETCAT_MODULE_SUBSCRIBER_TYPE . "</td>
                <td  align='center'>" . NETCAT_MODULE_SUBSCRIBER_SUBSCRIBERS_COUNT . "</td>
                <td  align='center'>" . NETCAT_MODULE_SUBSCRIBER_SETTINGS . "</td>
                <td  align='center'>" . ($nc_core->get_settings('PacketOperations') ? "<div class='icons icon_type_bool'></div>" : "") . "</td>
                </tr>";


        foreach ($mailers as $mailer) {
            $mailer['TypeName'] = ($mailer['Type'] == 1 ? NETCAT_MODULE_SUBSCRIBER_MAILER_CC : ($mailer['Type'] == 2 ? NETCAT_MODULE_SUBSCRIBER_MAILER_PERIODICAL : ($mailer['Type'] == 3 ? NETCAT_MODULE_SUBSCRIBER_MAILER_SERVICE : NETCAT_MODULE_SUBSCRIBER_MAILER_SERIAL)));
            if ($mailer['Type'] == 3) $mailer['Count'] = '';
            if (!$mailer['Type'] != 3 && $mailer['Count']) {
                $mailer['Count'] = "<a href='admin.php?phase=6&amp;mailer_id=" . $mailer['Mailer_ID'] . "'>" . $mailer['Count'] . "</a>";
            }
            echo "<tr>\n" .
                "<td >" . $mailer['Mailer_ID'] . "</td>\n" .
                "<td ><font " . (!$mailer['Active'] ? "style='color:#cccccc;'" : "") . ">" . $mailer['Name'] . "</font></td>\n" .
                "<td >" . ($mailer['Type'] == 1 || $mailer['Type'] == 4 ? "<a href='" . $this->ADMIN_PATH . "subdivision/SubClass.php?phase=3&SubClassID=" . $mailer['Sub_Class_ID'] . "&amp;SubdivisionID=" . $mailer['Subdivision_ID'] . "'>" . $mailer['Sub_Class_Name'] . "</a>" : "") . "</font></td>\n" .
                "<td >" . $mailer['TypeName'] . "</td>\n" .
                "<td  align='center' >" . $mailer['Count'] . "</td>\n" .
                "<td  align='center'><a href='admin.php?phase=2&amp;mailer_id=" . $mailer['Mailer_ID'] . "'><div class='icons icon_settings" . (!$mailer['Active'] ? "_disabled" : "") . "' title='" . NETCAT_MODULE_SUBSCRIBER_SETTINGS . "'></div></a></td>\n" .
                "<td  align='center'>" . ($nc_core->get_settings('PacketOperations') ? "<input type='checkbox' name='mailer_" . $mailer['Mailer_ID'] . "' value='" . $mailer['Mailer_ID'] . "' />" : "") . "</td>\n" .
                "</tr>";
        }
        echo "


          </table>
          </td>
        </tr>
      </table>
      </form>";
        echo "
    <script type='text/javascript'>\n
      function sumbit_form ( action_type ) {\n
        document.getElementById('mainForm').action_type.value =  action_type;\n
        parent.mainView.submitIframeForm('mainForm');\n
        return 0;\n
      }\n
    </script>\n";


        if ($nc_core->get_settings('PacketOperations')) {
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_SUBSCRIBER_DELETESELECTED,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('delete')",
                "align" => "left",
                "red_border" => true,
            );
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit1",
                "caption" => NETCAT_MODULE_SUBSCRIBER_SELECTEDOFF,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('unactive')",
                "align" => "left"
            );
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit2",
                "caption" => NETCAT_MODULE_SUBSCRIBER_SELECTEDON,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('active')",
                "align" => "left"
            );
        }
    }

    /**
     * Функция выводит форму добавления/редактрования рассылки
     * @param int $mailer_id номер рассылки ( 0 при добавлении )
     */
    public function mailer_form($mailer_id = 0) {
        // нужны функции, типа  nc_list_select
        require_once($this->core->INCLUDE_FOLDER . 's_common.inc.php');

        $mailer_id = intval($mailer_id);

        if ($mailer_id) { // редактирование существующей рассылки

            $mailer = $this->nc_subscriber->get($mailer_id);
            // для подписки на раздел и серийной нужно узнать раздел и сайт
            if (((nc_array_value($mailer,'Type') == 1 && !nc_array_value($mailer, 'Class_ID')) || nc_array_value($mailer, 'Type') == 4) && nc_array_value($mailer, 'Sub_Class_ID')) {
                $res = $this->db->get_row("SELECT s.`Subdivision_ID`, s.`Catalogue_ID`
                                   FROM `Subdivision` AS `s`, `Sub_Class` AS `sc`
                                   WHERE sc.`Subdivision_ID` = s.`Subdivision_ID`
                                   AND sc.`Sub_Class_ID` = '" . intval(nc_array_value($mailer, 'Sub_Class_ID')) . "'", ARRAY_A);
                if (empty($res)) {
                    throw new Exception(NETCAT_MODULE_SUBSCRIBER_NOT_SUB_FOR_CC . "<br/> Sub class id: " . nc_array_value($mailer, 'Sub_Class_ID'));
                }

                $mailer = array_merge($mailer, $res);
                unset($res);
            }

        } else { // добавление новой рассылки - данные могут прийти из post'a
            $mailer = $this->POST;
        }

        echo "<form name='main' id='main' method='post' enctype='multipart/form-data'  action='admin.php' style='padding:0; margin:0;' >\n" .
            "<!-- Основные настройки -->
      <fieldset>
       <legend>" . NETCAT_MODULE_SUBSCRIBER_MAIN_SETTINGS . "</legend>\n";
        $this->_input_text(NETCAT_MODULE_SUBSCRIBER_MAILER_NAME, 'Name', nc_array_value($mailer, 'Name'));

        echo "<div style='margin:10px 0; _padding:0;'>\n" .
            "" . NETCAT_MODULE_SUBSCRIBER_MAILER_TYPE . " <br>" .
            "<select name='Type' id='Type' style='width:50%' onchange='nc_subs.change_type(); return false;' >\n" .
            "<option value='1' " . (nc_array_value($mailer, 'Type') == 1 ? "selected" : "") . ">" . NETCAT_MODULE_SUBSCRIBER_MAILER_CC . "</option>\n" .
            "<option value='2' " . (nc_array_value($mailer, 'Type') == 2 ? "selected" : "") . ">" . NETCAT_MODULE_SUBSCRIBER_MAILER_PERIODICAL . "</option>\n" .
            "<option value='4' " . (nc_array_value($mailer, 'Type') == 4 ? "selected" : "") . ">" . NETCAT_MODULE_SUBSCRIBER_MAILER_SERIAL . "</option>\n" .
            "<option value='3' " . (nc_array_value($mailer, 'Type') == 3 ? "selected" : "") . ">" . NETCAT_MODULE_SUBSCRIBER_MAILER_SERVICE . "</option>\n" .
            "</select>\n" .
            "</div>\n" .
            "<div style='margin:10px 0; _padding:0;'>\n" .
            "" . NETCAT_MODULE_SUBSCRIBER_ACCESS_TO . ": <br>" .
            "<select name='Access'  style='width:50%'>\n" .
            "<option value='1' " . (nc_array_value($mailer, 'Access') == 1 ? "selected" : "") . ">" . NETCAT_MODULE_SUBSCRIBER_ACCESS_ALL . "</option>\n" .
            "<option value='2' " . (nc_array_value($mailer, 'Access') == 2 ? "selected" : "") . ">" . NETCAT_MODULE_SUBSCRIBER_ACCESS_REGISTERED . "</option>\n" .
            "<option value='3' " . (nc_array_value($mailer, 'Access') == 3 ? "selected" : "") . ">" . NETCAT_MODULE_SUBSCRIBER_ACCESS_AUTHORIZED . "</option>\n" .
            "</select>\n" .
            "</div>\n";

        $this->_checkbox(NETCAT_MODULE_SUBSCRIBER_ACTIVE, 'Active', nc_array_value($mailer, 'Active'));
        $this->_checkbox(NETCAT_MODULE_SUBSCRIBER_IN_STAT, 'InStat', nc_array_value($mailer, 'InStat'));

        echo "</fieldset></div>\n";
        echo "<fieldset>\n" .
            "<legend>" . NETCAT_MODULE_SUBSCRIBER_SPECIFIC_SETTINGS . "</legend>\n" .

            "
            <div id='div_selector_cc' style='display: none;'>
            <input type='checkbox' id='onclass' name='onclass'   value='1'" . (nc_array_value($mailer, 'Class_ID') ? " checked" : "") . "/>
                " . NETCAT_MODULE_SUBSCRIBER_ONCLASS . "
            <div id='div_class'>
                ".$this->get_class_select(nc_array_value($mailer, 'Class_ID'))."
            </div>

            <div id='div_cc'>" .
            "<div style='margin:10px 0; _padding:0;'>\n" .
            "<table width='50%'>\n
            <tr>
                <td width='30%'>" . NETCAT_MODULE_SUBSCRIBER_SITE . "</td>
                <td width='70%'>\n<select name='site_list' id='site_list' style='width: 100%;' onchange='nc_subs.change_site(); return false;'>\n";

        $sites = $this->db->get_results("SELECT `Catalogue_ID` as `id`, `Catalogue_Name` as `name` FROM `Catalogue` ORDER BY `Priority`", ARRAY_A);
     
        if (!empty($sites)) {
            foreach ($sites as $site) {
                if (empty($first_site)) {
                    $first_site = $site['id'];
                }
                echo "<option value='" . $site['id'] . "' " . (nc_array_value($mailer, 'Catalogue_ID') == $site['id'] ? "selected" : "") . ">" . $site['id'] . ". " . $site['name'] . "</option>\n";
            }
        }

        $action_type = (int)nc_array_value($mailer, 'ActionType');

        echo "</select></td></tr>\n" .
            "<tr><td>" . NETCAT_MODULE_SUBSCRIBER_SUBDIVISION . "</td>" .
            "<td><div id='div_sub_list'><select name='sub_list' id='sub_list' style='width: 100%;'>" .
            "</select></div></td></tr>\n" .
            "<tr><td>" . NETCAT_MODULE_SUBSCRIBER_CC . "</td>" .
            "<td><div id='div_subclass_list'><select name='subclass_list' style='width: 100%' id='subclass_list'>" .
            "</select></div></td></tr>\n" .
            "</table>" .
            "</div>\n" .
            "</div>\n" .
            "</div>\n" .

            "<div id='div_selector_action' style='display: none;'>" .
            "<div style='margin:10px 0; _padding:0;'>\n" .
            "" . NETCAT_MODULE_SUBSCRIBER_ADD_OBJECT_TO_MAILLIST . ":  <br/>" .
            "<input type='checkbox' name='rule_add_on'   value='1'" . (($action_type & 1) ? " checked" : "") . "/> " . NETCAT_MODULE_SUBSCRIBER_ACTION_ADD_ON . " <br/>" .
            "<input type='checkbox' name='rule_add_off'  value='2'" . (($action_type & 2) ? " checked" : "") . "/> " . NETCAT_MODULE_SUBSCRIBER_ACTION_ADD_OFF . " <br/>" .
            "<input type='checkbox' name='rule_edit_on'  value='4'" . (($action_type & 4) ? " checked" : "") . "/> " . NETCAT_MODULE_SUBSCRIBER_ACTION_EDIT_ON . " <br/>" .
            "<input type='checkbox' name='rule_edit_off' value='8'" . (($action_type & 8) ? " checked" : "") . "/> " . NETCAT_MODULE_SUBSCRIBER_ACTION_EDIT_OFF . " <br/>" .
            "<input type='checkbox' name='rule_on'       value='16'" . (($action_type & 16) ? " checked" : "") . "/> " . NETCAT_MODULE_SUBSCRIBER_ACTION_ON . " <br/>" .
            "<input type='checkbox' name='rule_off'      value='32'" . (($action_type & 32) ? " checked" : "") . "/> " . NETCAT_MODULE_SUBSCRIBER_ACTION_OFF . " <br/>" .
            "</div>\n" .
            "</div>" .

            "<div id='div_selector_periodmask' style='display: none;'>" .
            "<div style='margin:5px 0; _padding:0;'>\n
             <style>
               #period_table td { height: 25px; }
               #period_table input { margin:0; }
             </style>
             <div style='float:left'>
             ".NETCAT_MODULE_SUBSCRIBER_PERIOD_WEEKLY.": <br/>";
        echo "<table id='period_table'><tr>"
                . "<td>".NETCAT_MODULE_SUBSCRIBER_PERIOD_WEEKLY_ON."</td>"
                . "<td>".NETCAT_MODULE_SUBSCRIBER_PERIOD_WEEKLY_DAY."</td>"
                . "<td>".NETCAT_MODULE_SUBSCRIBER_PERIOD_WEEKLY_TIME."</td>"
                . "</tr>";

        $periodmask = $this->tools->parse_period_mask(nc_array_value($mailer, 'PeriodMask'));

        foreach (array(1,2,3,4,5,6,0) as $day) {
            echo "<tr>
               <td><input class='period_weekly' type='checkbox' name='period_day[$day]' value='1'" . (isset($periodmask['period_day_'.$day]) ? " checked" : "") . "/></td>
               <td>".day_of_week_name($day)."</td>
               <td><input type='text' size='2' name='period_time_$day' value='" . (nc_array_value($periodmask, 'period_time_'.$day, "10")) . "'/></td>";
        }

        echo "</table><br/>";
        echo "<input id='period_monthly' type='checkbox' name='period_monthly' value='1' " . (isset($periodmask['period_mask']) ? " checked" : "") . "/></td>
              ".NETCAT_MODULE_SUBSCRIBER_PERIOD_MONTHLY.":</span><br/>
             </div>
             <div id='period_help' style='padding: 10px; margin:0px 0px 0px 220px;  width:600px; border: 2px solid #1A87C2; display:none;'>" . NETCAT_MODULE_SUBSCRIBER_PERIOD_HELP . "</div>
             <div class='nc_clear'></div>
             <input type='text' size='100' name='period_mask' value='" . nc_array_value($periodmask, 'period_mask', '') . "' id='period_mask' disabled '/>";
        echo "</div>\n" .
            "</div>\n" .

            "<div id='div_selector_period' style='display: none;'>".
            "<div style='margin:10px 0; _padding:0;'>\n 
            ".NETCAT_MODULE_SUBSCRIBER_PERIOD_EACH.": <br/>".
              nc_list_select("SubscriberPeriod", "Period", nc_array_value($mailer, 'Period'), null, null,
                "<select name='Period' style='width:30%'>", null, null, true) .
            "</div>\n" .
            "</div>\n" .
            "</fieldset></div>\n";

        echo "
     <fieldset>\n" .
            "<legend>" . NETCAT_MODULE_SUBSCRIBER_COND_AND_ACTION . "</legend>\n";
        // условия и дейсвтия
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE_COND, 'SubscribeCond', nc_array_value($mailer, 'SubscribeCond'), 1);
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_SEND_COND, 'SendCond', nc_array_value($mailer, 'SendCond'), 1);
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE_ACTION, 'SubscribeAction', nc_array_value($mailer, 'SubscribeAction'), 1);


        echo "</fieldset></div>\n
            <fieldset>\n" .
            "<legend>" . NETCAT_MODULE_SUBSCRIBER_MAIL_TEMPLATE . "</legend>\n";

        $this->_input_text(NETCAT_MODULE_SUBJECT, 'Subject', nc_array_value($mailer, 'Subject'));
        $this->_checkbox(NETCAT_MODULE_HTML_MAIL, 'HTML', nc_array_value($mailer, 'HTML'));
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_MAIL_TEMPLATE_HEADER, 'Header', nc_array_value($mailer, 'Header'), 1, 0);
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_MAIL_TEMPLATE_CONTENT, 'Record', nc_array_value($mailer, 'Record'), 1, 0);
        $this->_textarea(NETCAT_MODULE_SUBSCRIBER_MAIL_TEMPLATE_FOOTER, 'Footer', nc_array_value($mailer, 'Footer'), 1, 0);
        echo nc_mail_attachment_form('subscriber_template_' . ($mailer_id ? $mailer_id : 'new'));
        echo "</fieldset></div>\n";
        // js for resize
        $this->_js_resize();
        // admin buttons
        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_SUBSCRIBER_SAVE_BUTTON,
            "action" => "mainView.submitIframeForm('main')"
        );

        if ($mailer_id) {
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "delete",
                "caption" => NETCAT_MODULE_SUBSCRIBE_ADM_DELETE,
                "align" => "left",
                "action" => "mainView.submitIframeForm('deleteForm')",
                "red_border" => true,
            );
        }

        echo "<input type='hidden' name='phase' value='21' />\n" .
            "<input type='hidden' name='mailer_id' value='" . $mailer_id . "' />" .
            "<input type='submit' class='hidden' />" .
            "</form><br/>\n";

        if ($mailer_id) {
            echo "<form name='deleteForm' id='deleteForm' method='post' action='admin.php'>\n" .
                "<input type='hidden' name='phase' value='11'>\n" .
                "<input type='hidden' name='action_type' value='delete'>\n" .
                "<input type='hidden' name='mailer_{$mailer_id}' value='{$mailer_id}'>\n" .
                "</form>";
        }

    ?>
    <script type='text/javascript' src='<?= nc_add_revision_to_url('admin.js') ?>'></script>
    <script type='text/javascript'>
      var none_cc_text = '<?= NETCAT_MODULE_SUBSCRIBER_NONE_CC ?>';
      nc_subs = new nc_subscriber();
      nc_subs.set_site(<?= (nc_array_value($mailer, 'Catalogue_ID') ? : $first_site) ?>);
      nc_subs.set_sub(<?= (nc_array_value($mailer, 'Subdivision_ID') ? : 0) ?>);
      nc_subs.set_cc(<?= (nc_array_value($mailer, 'Sub_Class_ID') ? : 0) ?>);
      nc_subs.change_type();
      nc_subs.init();
    </script>
    <?

        return 0;
    }

    /**
     * Сохранение данных от формы добавления/изменения рассылки
     * @param int $mailer_id номер рассылки ( 0 - добавление )
     */
    public function mailer_save($mailer_id) {
        $result = $this->POST;

        $result['ActionType'] = nc_array_value($result, 'rule_add_on') + nc_array_value($result, 'rule_add_off') +
        nc_array_value($result, 'rule_edit_on') + nc_array_value($result, 'rule_edit_off') +
        nc_array_value($result, 'rule_on') + nc_array_value($result, 'rule_off');

        $result['PeriodMask'] = $this->tools->prepare_period_mask($result);
        $result['Active'] = nc_array_value($result, 'Active') + 0;
        $result['InStat'] = nc_array_value($result, 'InStat') + 0;
        $result['HTML'] = nc_array_value($result, 'HTML') + 0;
        if ($result['Class_ID'] = nc_array_value($result, 'classid')) $result['Sub_Class_ID'] = 0;
        if (!nc_array_value($result, 'onclass')) $result['Class_ID'] = 0;

        if ($mailer_id) {
            $this->nc_subscriber->update($mailer_id, $result);
            nc_mail_attachment_form_save('subscriber_template_' . $mailer_id);
        } else {
            $mailer_id = $this->nc_subscriber->add($result);
            nc_mail_attachment_form_save('subscriber_template_' . $mailer_id, 'subscriber_template_new');
        }

        return 0;
    }

    /**
     * Обработка массовых дейсвий (вкл, выкл, удаление) с рассылками
     */
    public function mailer_list_update() {
        $nc_s = nc_subscriber::get_object();
        $mailer_ids = array();
        foreach ($this->POST as $k => $v) {
            if (substr($k, 0, 6) == 'mailer') $mailer_ids[] = intval($v);
        }
        if (empty($mailer_ids)) return false;

        switch ($this->POST['action_type']) {
            case 'active': // включение
                $nc_s->activate($mailer_ids);
                break;
            case 'unactive': // выключение
                $nc_s->unactivate($mailer_ids);
                break;
            case 'drop': // удаление
                $nc_s->delete($mailer_ids);
                break;
        }
    }

    /**
     * Форма для подтверждения удаления рассылок
     */
    public function mailer_confirm_deletion() {
        $mailer_ids = array();
        foreach ($this->POST as $k => $v) {
            if (substr($k, 0, 6) == 'mailer') $mailer_ids[] = intval($v);
        }
        if (empty($mailer_ids)) return false;

        nc_print_status(NETCAT_MODULE_SUBSCRIBER_CONFIRM_REMOVE_MAILERS, 'info');
        echo "<form action='admin.php' method='post'><ul>";
        $res = $this->db->get_results("SELECT `Mailer_ID`, `Name` FROM `Subscriber_Mailer`
                             WHERE `Mailer_ID` IN (" . join(',', $mailer_ids) . ") ", ARRAY_A);
        foreach ($res as $row) {
            echo "<li>" . $row['Name'] . " <input type='hidden' name='mailer_" . $row['Mailer_ID'] . "' value='" . $row['Mailer_ID'] . "' /></li>\n";
        }
        echo "</ul><input type='hidden' name='phase' value='11' />\n
          <input type='hidden' name='action_type' value='drop' />
          </form>";

        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => CONTROL_USER_FUNC_CONFIRM_DEL_OK,
            "action" => "mainView.submitIframeForm()",
            "red_border" => true,
        );
    }

    /**
     * Функция показывает подписчиков на данныую рассылку
     * @param int $mailer_id номер рассылки
     */
    public function sbs_show($mailer_id) {
        $nc_core = nc_Core::get_object();

        $mailer_id = intval($mailer_id);
        $mailer = $this->nc_subscriber->get($mailer_id);

        // выполнение запроса
        $this->db->last_error = '';
        $subscriptions = $this->db->get_results("SELECT ss.`ID`, ss.`Status`,
                                                   u.`User_ID`, u.`Email`, u.`UserType`, u.`" . $this->_settings['EmailField'] . "`
                                             FROM `Subscriber_Subscription`  AS `ss`,
                                                  `User` AS `u`
                                             WHERE u.`User_ID` = ss.`User_ID`
                                             AND `Mailer_ID` = '" . $mailer_id . "'
                                             ORDER BY `UserType`, `User_ID`", ARRAY_A);
        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }
        // если нет подписчиков
        if (empty($subscriptions)) {
            nc_print_status(NETCAT_MODULE_SUBSCRIBER_NO_SUBSCRIBERS, 'info');
            return 0;
        }
        echo NETCAT_MODULE_SUBSCRIBER_MAILER_SUBSCRIBER_LIST . " <a href='admin.php?phase=2&mailer_id=" . $mailer_id . "'>" . $mailer['Name'] . "</a>. <br/><br>";

        // вывод формы
        echo "
      <form action='admin.php' method='post' name='mainForm' id='mainForm'>
      <input type='hidden' name='phase' value='61' />
      <input type='hidden' name='action_type' value='' />
      <input type='hidden' name='mailer_id' value='" . $mailer_id . "' />
      <table border='0' cellpadding='0' cellspacing='0' width='100%'>
        <tr><td >
            <table border='0' cellpadding='4' cellspacing='1' width='100%'>
              <tr>
                <td >" . NETCAT_MODULE_SUBSCRIBER_USER . "</td>
                <td >" . NETCAT_MODULE_SUBSCRIBE_STATUS . "</td>
                <td  align='center'>" . ($nc_core->get_settings('PacketOperations') ? "<div class='icons icon_type_bool'></div>" : NETCAT_MODULE_SUBSCRIBER_ACTION) . "</td>
              </tr>";


        foreach ($subscriptions as $sbs) {
            switch ($sbs['Status']) {
                case 'on':
                    $status = "" . NETCAT_MODULE_SUBSCRIBE_TURNEDON . "";
                    break;
                case 'off':
                    $status = "" . NETCAT_MODULE_SUBSCRIBE_TURNEDOFF . "";
                    break;
                case 'wait':
                    $status = "" . NETCAT_MODULE_SUBSCRIBE_WAIT_CONFIRM . "";
                    break;
                default:
                    $status = '';
                    break;
            }
            $user = ($sbs['UserType'] == 'normal' ? $sbs['User_ID'] . ". <a  href='" . $this->ADMIN_PATH . "user/index.php?phase=4&UserID=" . $sbs['User_ID'] . "'>" . $sbs['Email'] . "</a>" :
                NETCAT_MODULE_SUBSCRIBE_UNREGISTERED_USER . " (" . $sbs['Email'] . ")");

            echo "<tr>\n" .
                "<td >" . $user . "</td>\n" .
                "<td >" . $status . "</td>\n" .
                "<td  align='center'>";

            $oper_url = "admin.php?phase=61&sbs_" . $sbs['ID'] . "=" . $sbs['ID'] . "&mailer_id=" . $mailer_id . "";
            if ($nc_core->get_settings('PacketOperations')) {
                echo    "<input type='checkbox' name='sbs_" . $sbs['ID'] . "' value='" . $sbs['ID'] . "' />";
            } else {
                echo "<a  href='".$oper_url."&action_type=drop'>".NETCAT_MODULE_SUBSCRIBE_ADM_DELETE."</a>&nbsp;&nbsp;";
                echo $sbs['Status'] == 'on' ?
                     "<a  href='".$oper_url."&action_type=off'>".NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFF."</a> " :
                     "<a  href='".$oper_url."&action_type=on'>".NETCAT_MODULE_SUBSCRIBE_ADM_TURNON."</a>";
            }
            echo "</td>\n" .
                "</tr>";
        }

        echo "</table></td></tr></table></form>";
        // скрипт для смены действия
        echo "
    <script type='text/javascript'>\n
      function sumbit_form ( action_type ) {\n
        document.getElementById('mainForm').action_type.value =  action_type;\n
        parent.mainView.submitIframeForm('mainForm');\n
        return 0;\n
      }\n
    </script>\n";

        if ($nc_core->get_settings('PacketOperations')) {
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_SUBSCRIBER_DELETESELECTED,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('drop')",
                "align" => "left",
                "red_border" => true,
            );
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit1",
                "caption" => NETCAT_MODULE_SUBSCRIBER_SELECTEDOFF,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('off')",
                "align" => "left"
            );
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit2",
                "caption" => NETCAT_MODULE_SUBSCRIBER_SELECTEDON,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('on')",
                "align" => "left"
            );
        }

        return 0;
    }

    /**
     * Массовая обработка подписок на определенную рассылку
     * @return <type>
     */
    public function sbs_update() {

        $nc_s = nc_subscriber::get_object();

        if ($this->POST['action_type'] == 'add') {
            $nc_s->subscription_add($this->POST['mailer_id'], $this->POST['UserID'], 0, 'on');
            return 0;
        }

        $sbs_ids = array();
        foreach ($this->POST as $k => $v) {
            if (substr($k, 0, 3) == 'sbs') $sbs_ids[] = intval($v);
        }
        if (empty($sbs_ids)) return false;

        switch ($this->POST['action_type']) {
            case 'on':
                $nc_s->subscription_change_status($sbs_ids, 'on');
                break;
            case 'off':
                $nc_s->subscription_change_status($sbs_ids, 'off');
                break;
            case 'drop':
                $nc_s->subscription_delete($sbs_ids);
                break;
        }
    }

    /**
     * Функция показывает все подписки пользователя
     * @param int $user_id номер пользователя
     */
    public function user_show($user_id) {
        $nc_core = nc_Core::get_object();

        $nc_subscriber = nc_subscriber::get_object();
        $user_id = intval($user_id);

        if (!$user_id) {
            throw new ExceptionParam(__CLASS__ . '::' . __FUNCTION__, 'user_id');
        }

        $this->db->last_error = '';
        //  выполнение запроса
        $subscriptions = $this->db->get_results("SELECT ss.`ID`, ss.`Status`, sm.`Name`, sm.`Mailer_ID`
                                            FROM `Subscriber_Subscription`  AS `ss`,
                                                 `Subscriber_Mailer`  AS `sm`
                                            WHERE ss.`User_ID` = '" . $user_id . "'
                                            AND ss.`Mailer_ID` = sm.`Mailer_ID`
                                            ORDER BY sm.`Mailer_ID`", ARRAY_A);

        if ($this->db->last_error) {
            throw new ExceptionDB(__CLASS__ . '::' . __FUNCTION__);
        }

        echo "
      <form action='" . $this->ADMIN_PATH . "user/index.php' method='post' name='mainForm' id='mainForm'>
      <input type='hidden' name='phase' value='16' />
      <input type='hidden' name='action_type' value='' />
      <input type='hidden' name='UserID' value='" . $user_id . "' />";

        if (empty($subscriptions)) {
            nc_print_status(NETCAT_MODULE_SUBSCRIBE_USER_NOT_SUBSCRIBE, 'info');
        } else {
            echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>
        <tr><td >
            <table border='0' cellpadding='4' cellspacing='1' width='100%'>
              <tr>
                <td  width='50%'>" . NETCAT_MODULE_SUBSCRIBE_MAILER . "</td>
                <td  width='40%'>" . NETCAT_MODULE_SUBSCRIBE_STATUS . "</td>
                <td  width='10%' align='center'>" . ($nc_core->get_settings('PacketOperations') ? "<div class='icons icon_type_bool'></div>" : NETCAT_MODULE_SUBSCRIBER_ACTION) . "</td>
              </tr>";


            foreach ($subscriptions as $sbs) {
                // статус
                switch ($sbs['Status']) {
                    case 'on':
                        $status = "" . NETCAT_MODULE_SUBSCRIBE_TURNEDON . "";
                        break;
                    case 'off':
                        $status = "" . NETCAT_MODULE_SUBSCRIBE_TURNEDOFF . "";
                        break;
                    case 'wait':
                        $status = "" . NETCAT_MODULE_SUBSCRIBE_WAIT_CONFIRM . "";
                        break;
                    default:
                        $status = '';
                        break;
                }
                echo "<tr>\n" .
                    "<td>" . $sbs['Mailer_ID'] . ". <a href='" . nc_module_path('subscriber') . "admin.php?phase=2&amp;mailer_id=$sbs[Mailer_ID]'>$sbs[Name]</a></td>\n" .
                    "<td>" . $status . "</td>\n" .
                "<td  align='center'>";

                $oper_url = $this->ADMIN_PATH . "user/index.php?phase=16&sbs_" . $sbs['ID'] . "=" . $sbs['ID'] . "&UserID=" . $user_id . "&".$this->core->token->get_url();
                if ($nc_core->get_settings('PacketOperations')) {
                    echo    "<input type='checkbox' name='sbs_" . $sbs['ID'] . "' value='" . $sbs['ID'] . "' />";
                } else {
                    echo "<a  href='".$oper_url."&action_type=drop'>".NETCAT_MODULE_SUBSCRIBE_ADM_DELETE."</a>&nbsp;&nbsp;";
                    echo $sbs['Status'] == 'on' ?
                         "<a  href='".$oper_url."&action_type=off'>".NETCAT_MODULE_SUBSCRIBE_ADM_TURNOFF."</a> " :
                         "<a  href='".$oper_url."&action_type=on'>".NETCAT_MODULE_SUBSCRIBE_ADM_TURNON."</a>";
                }
                echo "</td>\n" .
                    "</tr>";
            }

            echo "</table></td></tr></table>";
        }

        echo $this->core->token->get_input();
        echo "</form>";
        echo "
    <script type='text/javascript'>\n
      function sumbit_form ( action_type ) {\n
        document.getElementById('mainForm').action_type.value =  action_type;\n
        parent.mainView.submitIframeForm('mainForm');\n
        return 0;\n
      }\n
    </script>\n";


        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit3",
            "caption" => NETCAT_MODULE_SUBSCRIBE_ADD_SUBSCRIBE,
            "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('add_ask')",
            "align" => "right"
        );

        if (!empty($subscriptions) && $nc_core->get_settings('PacketOperations')) {
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_SUBSCRIBER_DELETESELECTED,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('drop')",
                "align" => "left",
                "red_border" => true,
            );
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit1",
                "caption" => NETCAT_MODULE_SUBSCRIBER_SELECTEDOFF,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('off')",
                "align" => "left"
            );
            $this->UI_CONFIG->actionButtons[] = array(
                "id" => "submit2",
                "caption" => NETCAT_MODULE_SUBSCRIBER_SELECTEDON,
                "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form('on')",
                "align" => "left"
            );
        }

        return 0;
    }

    /**
     * Работа с подписками пользователя
     * @param int $user_id номер пользователя
     */
    public function user_add_form($user_id) {
        $user_id = intval($user_id);
        $mailers = $this->db->get_results("SELECT sm.`Mailer_ID`, sm.`Name`
                            FROM (`Subscriber_Mailer` AS `sm`)
                            LEFT JOIN `Subscriber_Subscription` AS `ss` ON (sm.`Mailer_ID` = ss.`Mailer_ID`
                            AND  ss.`User_ID` = '" . $user_id . "')
                            WHERE ss.`ID` IS NULL
                            AND sm.`Type` <> 3
                            ORDER BY sm.`Mailer_ID`", ARRAY_A);
        if (empty($mailers)) {
            nc_print_status(NETCAT_MODULE_SUBSCRIBE_NONE_MAILERS_FOR_USER, 'info');
            return 0;
        }

        echo "
      <form action='" . $this->ADMIN_PATH . "user/index.php' method='post' name='mainForm' id='mainForm'>
      <input type='hidden' name='phase' value='16' />
      <input type='hidden' name='action_type' value='add' />
      <input type='hidden' name='UserID' value='" . $user_id . "' />";

        echo "<div style='margin:10px 0; _padding:0;'>\n
        " . NETCAT_MODULE_SUBSCRIBE_SELECT_MAILER . ": <br>
        <select name='mailer_id'  style='width:50%'>\n";

        foreach ($mailers as $mailer) {
            echo "<option value='" . $mailer['Mailer_ID'] . "'>" . $mailer['Name'] . "</option>";
        }

        echo "</select>";
        echo $this->core->token->get_input();
        echo "</form>";

        // admin buttons
        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_SUBSCRIBE_ACTION_SUBSCRIBE,
            "action" => "mainView.submitIframeForm()"
        );

        return 0;
    }

    /**
     * Вывод формы для единоразовой рассылки
     */
    public function once_show() {
        // подписанные на рассылку
        $mailer_id = nc_array_value($this->POST, 'mailer_id');
        // группы пользователя
        $grpID = nc_array_value($this->POST, 'grpID');
        if (!$grpID) $grpID = array();
        // тип пользователя
        $user_type = intval(nc_array_value($this->POST, 'user_type'));
        // включен/выключен
        $checked = intval(nc_array_value($this->POST, 'checked'));

        echo "<form name='main' id='main' action='admin.php' method='post' enctype='multipart/form-data' >";

        // выборка пользвотелей
        echo "<fieldset>
       <legend>" . CONTROL_USER_FUNCS_USERSGET . "</legend>";

        echo $this->_input_text(NETCAT_MODULE_SUBSCRIBER_ONCE_CLASSIFIER_NAME, 'Classificator', nc_array_value($this->POST, 'Classificator'));
        echo "<table border='0' cellpadding='6' cellspacing='0' width='100%'><tr><td colspan=2'> ";

        // подписанные на рассылку
        $mailers = $this->db->get_results("SELECT DISTINCT sm.`Mailer_ID` as `id`, sm.`Name` AS `name`
                                      FROM `Subscriber_Mailer` AS `sm`, `Subscriber_Subscription` as s
                                      WHERE sm.Mailer_ID = s.Mailer_ID
                                      ORDER BY sm.`Mailer_ID`", ARRAY_A);
        if (!empty($mailers)) {
            echo "<div style='margin:10px 0; _padding:0;'>\n
       " . NETCAT_MODULE_SUBSCRIBER_SUBSCRIBE_USER . ":<br/>";
            echo "<select name='mailer_id'>";
            echo "<option value='0'>" . NETCAT_MODERATION_MOD_NOANSWER . "</option>";
            foreach ($mailers as $mailer) {
                echo "<option value='" . $mailer['id'] . "' " . ($mailer_id == $mailer['id'] ? "selected='selected'" : "") . ">" . $mailer['name'] . "</option>";
            }
            echo "</select></div>";
        }


        // группа пользователя
        $groups = $this->db->get_results("SELECT `PermissionGroup_ID` AS `id`, `PermissionGroup_Name` AS `name`
                                      FROM `PermissionGroup`", ARRAY_A);
        echo "" . CONTROL_USER_GROUP . ":<br/>";
        echo "<select name='grpID[]' multiple size='3'>";
        if (!empty($groups)) {
            foreach ($groups AS $group)
                echo "<option value='" . $group['id'] . "' " . (in_array($group['id'], $grpID) ? 'selected' : '') . ">" . $group['id'] . ": " . $group['name'];
        }
        echo "</select>";

        echo "</td></tr><tr><td width='20%'>";
        echo "" . NETCAT_MODULE_SUBSCRIBER_ONCE_TYPE_USER . " : </td><td><input type='radio' name='user_type' value='0' id='user_type_0' " . ($user_type == 0 ? "checked" : "") . "/>
                            <label for='user_type_0'>" . NETCAT_MODULE_SUBSCRIBER_ONCE_REGISTRED . "</label>
                            &nbsp;&nbsp;
                            <input type='radio' name='user_type' value='1' id='user_type_1' " . ($user_type == 1 ? "checked" : "") . "/>
                            <label for='user_type_1'>" . NETCAT_MODULE_SUBSCRIBER_ONCE_ALL . "</label>";

        echo "</td></tr><tr><td>";
        echo "" . NETCAT_MODULE_SUBSCRIBER_USER_CHECK . " : </td><td> <input type='radio' name='checked' value='0' id='checked_0' " . ($checked == 0 ? "checked" : "") . "/>
                            <label for='checked_0'>" . NETCAT_MODULE_SUBSCRIBER_USER_CHECK_ALL . "</label>
                            &nbsp;&nbsp;
                            <input type='radio' name='checked' value='1' id='checked_1' " . ($checked == 1 ? "checked" : "") . "/>
                            <label for='checked_1'>" . NETCAT_MODULE_SUBSCRIBER_USER_CHECKED . "</label>
                            &nbsp;&nbsp;
                            <input type='radio' name='checked' value='2' id='checked_2' " . ($checked == 2 ? "checked" : "") . "/>
                            <label for='checked_2'>" . NETCAT_MODULE_SUBSCRIBER_USER_NONECHECK . "</label>";
        echo "</td></tr><tr><td colspan='2'><br/>";

        $systemTableID = 3;
        require_once $this->core->INCLUDE_FOLDER . "s_list.inc.php";
        $is_there_any_files = 0;
        global $db;
        $nc_core = $this->core;
        require $this->core->ROOT_FOLDER . "message_fields.php";

        echo showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt);


        echo "</td></tr></table>";
        echo "</fieldset></div>\n";


        // Формирование письма
        echo "<fieldset>
       <legend>" . NETCAT_MODULE_SUBSCRIBER_MAIL_TEMPLATE . "</legend>";

        echo $this->_input_text(NETCAT_MODULE_SUBJECT, 'Subject', stripslashes(nc_array_value($this->POST, 'Subject')));
        echo $this->_checkbox(NETCAT_MODULE_HTML_MAIL, 'HTML', nc_array_value($this->POST, 'HTML'));
        echo $this->_textarea(NETCAT_MODULE_MAIL_BODY, 'MailBody', stripslashes(nc_array_value($this->POST, 'MailBody')), 1, 1);


        echo "</fieldset></div>\n";

        // Файлы
        echo "<fieldset>
       <legend>" . NETCAT_MODULE_SUBSCRIBER_USER_ATTACH . "</legend>";

        echo $this->_input_file(NETCAT_MODULE_SUBSCRIBER_FILE_1, 'file1', nc_array_value($this->_files_id, 1));
        echo $this->_input_file(NETCAT_MODULE_SUBSCRIBER_FILE_2, 'file2', nc_array_value($this->_files_id, 2));
        echo $this->_input_file(NETCAT_MODULE_SUBSCRIBER_FILE_3, 'file3', nc_array_value($this->_files_id, 3));


        echo "</fieldset></div>\n";


        $this->_js_resize();


        echo "
    <input type='hidden' name='phase' value='72' />
    <script type='text/javascript'>\n
      function sumbit_form ( phase ) {\n
        document.getElementById('main').phase.value =  phase;\n
        parent.mainView.submitIframeForm('main');\n
        return 0;\n
      }\n
    </script>\n";


        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_SUBSCRIBE_TESTSEND,
            "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form(71)",
            "align" => "left"
        );
        $this->UI_CONFIG->actionButtons[] = array(
            "id" => "submit1",
            "caption" => NETCAT_MODULE_SUBSCRIBE_SEND,
            "action" => "document.getElementById('mainViewIframe').contentWindow.sumbit_form(72)",
        );
    }

    public function once_test_send() {
        // проверка адреса для тестовой рассылки
        $nc_email = $this->_settings['TestEmail'];
        if (!$nc_email || !preg_match("/^[0-9a-z-._]+@([0-9a-z-]+\.)+[0-9a-z]+$/i", $nc_email)) {
            nc_print_status(NETCAT_MODULE_SUBSCRIBER_ONCE_INVALID_TEST_EMAIL, 'error');
            return;
        }

        $this->once_file_save();

        // посылаем письмо
        $s = nc_subscriber_send::get_object();
        $s->send_mail($nc_email, stripslashes(nc_array_value($this->POST, 'MailBody')), stripslashes(nc_array_value($this->POST, 'Subject')), nc_array_value($this->POST, 'HTML'), $this->_files_id);

        // завершаем работу функции
        nc_print_status(sprintf(NETCAT_MODULE_SUBSCRIBER_ONCE_MAIL_SEND, $nc_email), 'ok');
        return 0;
    }

    public function once_send() {
        // обработка входных файлов
        $this->once_file_save();
        // выборка пользователя
        $systemTableID = 3;
        $db = $this->db;
        require($this->core->ROOT_FOLDER . "message_fields.php");
        require_once($this->core->INCLUDE_FOLDER . "s_list.inc.php");

        if (nc_array_value($this->POST, 'Classificator')) {
            if (!preg_match("/^[a-z][0-9a-z]+?$/i", $this->POST['Classificator'])) {
                nc_print_status(NETCAT_MODULE_SUBSCRIBER_ONCE_CLASSIFIER_WRONGNAME, 'error');
            }
            $query = "SELECT `".$this->POST['Classificator']."_Name`
                        FROM `Classificator_".$this->POST['Classificator']."`";
            $emails = $db->get_col($query);
            if (empty($emails)) nc_print_status(NETCAT_MODULE_SUBSCRIBER_ONCE_CLASSIFIER_EMPTY, 'error');
        }
        else {
            $nc_core = nc_core::get_object();
            $search_params = $nc_core->get_component('User')->get_search_query($this->POST['srchPat']);
            $where = '';

            // рассылка, на которую должен быть подписан пользователь
            $mailer_id = intval(nc_array_value($this->POST, 'mailer_id'));
            // условия выборки пользователя
            if ($this->POST['user_type'] == 0) $where .= " AND `UserType` = 'normal' ";
            if ($this->POST['checked'] == 1) $where .= " AND `Checked` = '1' ";
            if ($this->POST['checked'] == 2) $where .= " AND `Checked` = '0' ";
            if ($mailer_id)
                $where .= " AND a.User_ID = s.User_ID AND s.Mailer_ID = '" . $mailer_id . "' ";
            if (!empty($this->POST['grpID']))
                $where .= "AND `ug`.PermissionGroup_ID IN ( " . join(', ', array_map('intval', $this->POST['grpID'])) . ") ";
            $where .= $search_params['query'];


            $query = "SELECT DISTINCT a.`" . $this->_settings['EmailField'] . "`
                  FROM `User` AS `a`, `User_Group` AS `ug` " . ($mailer_id ? ", `Subscriber_Subscription` as s " : "") . "
                  WHERE `ug`.`User_ID` = `a`.`User_ID`
                  " . $where . "
                  GROUP BY a.`User_ID` ";
            $emails = $db->get_col($query);
        }
        if (empty($emails)) return 0;


        $subject = $db->escape(stripslashes(nc_array_value($this->POST, 'Subject')));
        $body = $db->escape(stripslashes(nc_array_value($this->POST, 'MailBody')));
        $html = intval(nc_array_value($this->POST, 'HTML'));
        $files = "";
        for ($i = 1; $i < 4; $i++)
            if ($this->_files_id[$i] == 0) unset($this->_files_id[$i]);

        if (!empty($this->_files_id)) $files = join(',', $this->_files_id);

        foreach ($emails as $email) {
            $email = $db->escape($email);
            $db->query("INSERT INTO `Subscriber_Prepared` (`Email`, `Subject`, `Body`, `HTML`, `Files`)
        VALUES ('" . $email . "','" . $subject . "','" . $body . "','" . $html . "', '" . $files . "')");
        }

        return count($emails);
    }

    protected function once_file_save() {
        // файлы
        for ($i = 1; $i < 4; $i++) {
            if ($_FILES['file' . $i]['size'] && !$_FILES['file' . $i]['error']) {
                // возможно, нужно удалить старый файл
                $id = $this->_files_id[$i];
                if ($id) {
                    $filename = $this->db->get_var("SELECT `Virt_Name` FROM `Filetable` WHERE `ID`='" . $id . "' ");
                    if ($filename) unlink($this->core->FILES_FOLDER . $filename);
                    $this->db->query("DELETE FROM `Filetable` WHERE `ID`='" . $id . "' LIMIT 1");
                    $this->_files_id[$i] = 0;
                }
                $name = md5(rand(0, 100) . $_FILES['file' . $i]['name']);
                if (move_uploaded_file($_FILES['file' . $i]['tmp_name'], $this->core->FILES_FOLDER . $name)) {
                    $filename = $this->db->escape($_FILES['file' . $i]['name']);
                    $filetype = $this->db->escape($_FILES['file' . $i]['type']);
                    $filesize = intval($_FILES['file' . $i]['size']);
                    $this->db->query("INSERT INTO `Filetable` (`Real_Name`, `Virt_Name`, `File_Path`, `File_Type`, `File_Size`)
            VALUES ('" . $filename . "','" . $name . "','/','" . $filetype . "','" . $filesize . "') ");
                    $this->_files_id[$i] = $this->db->insert_id;
                }
            }
        }

        return 0;
    }

    /**
     * Функция рисует элемент для ввода текста
     * @param string $disc описание
     * @param string $name имя
     * @param string $value  значение
     */
    protected function _input_text($disc, $name, $value) {
        echo "<div style='margin:10px 0; _padding:0;'>\n
       " . $disc . ":<br/>
       <input type='text' name='" . $name . "' style='width:50%' value='" . htmlentities($value, ENT_QUOTES, MAIN_ENCODING) . "'/>
       </div>\n";
    }

    /**
     * Функция рисует элемент для ввода текстового блока
     * @param string $disc описание
     * @param string $name имя
     * @param string $value  значение
     * @param bool $is_resizeble разрешить изменение высоты
     * @param bool $editor показывать кнопку для редактирования в визуальном редакторе
     */
    protected function _textarea($disc, $name, $value, $is_resizeble = 0, $editor = 0) {
        global $system_env;
        echo "<div style='margin:10px 0; _padding:0;'>\n" . $disc . ": <br/>";
        if ($editor) {
            $windowWidth = 750;
            $windowHeight = 605;
            switch (nc_Core::get_object()->get_settings('EditorType')) {
                default:
                case 2:
                    $editor_name = 'FCKeditor';
                    break;
                case 3:
                    $editor_name = 'ckeditor4';
                    $windowWidth = 1100;
                    $windowHeight = 420;
                    break;
                case 4:
                    $editor_name = 'tinymce';
                    break;
            }
            $editor_path = "editors/{$editor_name}/neditor.php";
            echo "<button type='button' onclick=\"window.open('" . $this->core->SUB_FOLDER . $this->core->HTTP_ROOT_PATH . $editor_path . "?form=main&control=" . $name . "', 'Editor', 'width={$windowWidth},height={$windowHeight},resizable=yes,scrollbars=no,toolbar=no,location=no,status=no,menubar=no');\">" . TOOLS_HTML_INFO . "</button><br>";
        }

        echo
            "<textarea name='" . $name . "' id='" . $name . "' style='width:100%; height:20em; line-height:1em'>" . htmlentities($value, ENT_COMPAT, MAIN_ENCODING) . "</textarea>" .
            "</div>\n";
    }

    /**
     * Функция рисует элемент-checkbox
     * @param string $disc описание
     * @param string $name имя
     * @param bool $value  включен/выключен
     */
    protected function _checkbox($disc, $name, $value) {
        echo " <div style='margin:10px 0; _padding:0;'>\n
        <input type='checkbox' name='" . $name . "' id='" . $name . "' value='1'" . ($value ? " checked" : "") . "/>
        <label for='" . $name . "'>" . $disc . "</label>
        </div>\n";
    }

    protected function _input_file($disc, $name, $id = 0) {
        $id = intval($id);
        if ($id) {
            $filename = $this->db->get_var("SELECT `Real_Name` FROM `Filetable` WHERE `ID`='" . $id . "' ");
        }
        else {
            $filename = '';
        }
        echo "<div style='margin:10px 0; _padding:0;'>\n
       " . $disc . ":<br/>
       <input type='file' name='" . $name . "' id='" . $name . "' style='width: 50%'/>
       " . ($filename ? "<br/> Закачан: " . $filename : "") . "
       " . ($id ? "<input type='hidden' name='" . $name . "_id' value='" . $id . "' /> " : "") . "
</div>\n";
    }

    /**
     * Вывод js для ресайза текстовых блоков
     */
    protected function _js_resize() {
        echo "<script type='text/javascript'>\$nc(function() {bindTextareaResizeButtons();} )</script>";
    }

    public function _input_date($disc, $name, $value = null) {

        if (nc_core('input')->fetch_get_post($name)) $value = nc_core('input')->fetch_get_post($name);

        if (!$value) {
            $value = date("d.m.Y");
        }

        $result = "<div>\n";

        if ($disc) {
            $result .= $disc . ": ";
        }

        $result .= "<input type='text' name='" . $name . "' size='8' value='" . htmlentities($value, ENT_QUOTES, MAIN_ENCODING) . "' /> \r\n";

        $result .= "<script type='text/javascript'>\n
        \$nc('INPUT[name=$name]').datepicker();
   	</script>\n";

        $result .= "</div>\n";

        return $result;
    }

    public function get_class_select($selected_value) {
        $db = nc_core('db');

        $sql = "SELECT `Class_ID` as value, " .
            "CONCAT(`Class_ID`, '. ', `Class_Name`) as description, " .
            "`Class_Group` as optgroup " .
            "FROM `Class` " .
            "WHERE `ClassTemplate` = 0 AND File_Mode = 0 " .
            "ORDER BY `Class_Group`, `Priority`, `Class_ID`";
        $classesV4 = (array)$db->get_results($sql, ARRAY_A);

        $sql = "SELECT `Class_ID` as value, " .
            "CONCAT(`Class_ID`, '. ', `Class_Name`) as description, " .
            "`Class_Group` as optgroup " .
            "FROM `Class` " .
            "WHERE `ClassTemplate` = 0 AND File_Mode = 1 " .
            "ORDER BY `Class_Group`, `Priority`, `Class_ID`";
        $classesV5 = (array)$db->get_results($sql, ARRAY_A);

        $classInfo = "<select id='classid' name='classid'>";
        if (!empty($classesV5)) {
            $classInfo.= "<option disabled='disabled'>" . CONTROL_CLASS . " v5</option>\n";
            $classInfo.= nc_select_options($classesV5, $selected_value);
        }
        if (!empty($classesV4)) {
            $classInfo.= "<option disabled='disabled'>" . CONTROL_CLASS . " v4</option>\n";
            $classInfo.= nc_select_options($classesV4, $selected_value);
        }
        $classInfo.= "</select>";

        return $classInfo;
    }


    /**
     * Создает письмо в очереди отправки, прикрепит файлы отправленные через файловые поля file1 - file3
     *
     * @param string $email - адресат
     * @param string $subject - заголовок письма
     * @param string $message - тело письма
     * @param int $html - указывает что письмо с html тегами
     *
     */

    public function mail2queue($email, $subject, $message, $html = 1) {
        // обработка входных файлов
        $this->once_file_save();
        $db = $this->db;

        $email = $db->escape($email);
        $subject = $db->escape($subject);
        $body = $db->escape($message);
        $html = intval($html);
        $files = "";
        for ($i = 1; $i < 4; $i++)
            if ($this->_files_id[$i] == 0) unset($this->_files_id[$i]);

        if (!empty($this->_files_id)) $files = join(',', $this->_files_id);

        $db->query("INSERT INTO `Subscriber_Prepared` (`Email`, `Subject`, `Body`, `HTML`, `Files`)
        VALUES ('" . $email . "','" . $subject . "','" . $body . "','" . $html . "', '" . $files . "')");

        return;
    }

    //FIXME: После ввода глобального переключателя сайтов метод и его вызовы (выше) можно удалять
    /**
     * Поле выбора редактируемого сайта
     *
     * @param string $view текущее представление настроек
     *
     * @return string
     */
    protected function catalogue_select_field($phase) {
        static $options;

        if (is_null($options)) {
            $options = array('' => CONTROL_USER_SELECTSITEALL);
            $catalogues = nc_core('catalogue')->get_all();

            foreach ($catalogues as $id => $row) {
                $options[$id] = $id . '. ' . $row['Catalogue_Name'];
            }
        }

        $url = "admin.php?phase={$phase}&current_catalogue_id=";
        $field = nc_core('ui')->html->select('current_catalogue_id', $options, nc_core('catalogue')->id())
            ->attr('onchange', 'window.location.href="' . $url . '"+this.value');

        return "<div>{$field}</div>";
    }
}
