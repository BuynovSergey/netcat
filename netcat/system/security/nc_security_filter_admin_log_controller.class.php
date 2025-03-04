<?php

class nc_security_filter_admin_log_controller extends nc_security_admin_controller {

    protected function action_show_log() {
        $this->ui_config->set_action('filter', 'log');
        $this->ui_config->actionButtons = array(
            array(
                'id' => 'goback',
                'caption' => NETCAT_SECURITY_LOG_GOBACK,
                'action' => 'window.history.back()',
                'align' => 'left',
            ),
            array(
                'id' => 'submit',
                'caption' => NETCAT_SECURITY_LOG_REFRESH,
                'action' => 'mainView.refreshIframe()',
                'align' => 'right',
            ),
        );

        $rec_on_page = 50;
        $nc_core = nc_core::get_object();
        $filter_data = $nc_core->input->fetch_get("filter");
        $hash_from_group = $nc_core->input->fetch_get("hash_from_group");
        $offset = (int)$nc_core->input->fetch_get("offset");

        if (!empty($filter_data) && array_filter($filter_data)) {

            foreach ($filter_data as $key => $value) {
                $filter_data[$key] = mysqli_real_escape_string($nc_core->db->dbh, $value);
            }

            $where_string = "";
            foreach ($filter_data as $key => $value) {

                if (!empty($value)) {
                    if ($key == 'Date_From') {
                        $value = date("Y-m-d H:i:s", strtotime($value));
                        $where_string .= " `Timestamp` >= '$value' ";
                    } elseif ($key == 'Date_To') {
                        $value = date("Y-m-d H:i:s", strtotime($value) + 86400);
                        $where_string .= " `Timestamp` <= '$value' ";
                    } elseif ($key != 'group' && $key != 'Hash') {
                        $where_string .= " `$key` LIKE '%$value%' ";
                    } elseif ($key == 'Hash') {
                        $where_string .= " `$key` = '$value' ";
                    }
                    if ($key != 'group') {
                        $where_string .= "AND";
                    }
                }
            }
            $where_string = substr($where_string, 0, -3);
            $group_by_string = '';

            if (!empty($filter_data['group'])) {
                $group_by_string = " GROUP BY `Hash` ORDER BY `Timestamp` DESC";
            }

            $filter_query = '';
            if (!empty($where_string) && empty($hash_from_group)) {

                if ($group_by_string) {
                    $rec_count = (int)$nc_core->db->get_var("SELECT COUNT(DISTINCT `Hash`) FROM `Security_FilterLog` WHERE $where_string");
                    $filter_query = "SELECT `Security_FilterLog_ID`, MAX(`Timestamp`) as `LastDate`,
                                `FilterType`, `ValueSource`, `CheckedString`, COUNT(*) as `CountElements`, `Hash` 
                                 FROM `Security_FilterLog` WHERE $where_string $group_by_string ";
                } else {
                    $rec_count = (int)$nc_core->db->get_var("SELECT COUNT(*) FROM `Security_FilterLog` WHERE $where_string ");
                    $filter_query = "SELECT `Security_FilterLog_ID`, `Timestamp`,
                                  `FilterType`, `URL`, `Referer`, `IP`, `Hash` 
                                  FROM `Security_FilterLog` WHERE $where_string";
                }
            } elseif (!empty($hash_from_group)) {
                $rec_count = (int)$nc_core->db->get_var("SELECT COUNT(*) FROM `Security_FilterLog` WHERE `Hash` = '$hash_from_group'");
                $filter_query = "SELECT `Security_FilterLog_ID`, `Timestamp` as `LastDate`,
                                `FilterType`, `ValueSource`, `CheckedString`, `Hash` 
                                FROM `Security_FilterLog` 
                                WHERE `Hash` = '$hash_from_group' ";

            } else {
                $rec_count = (int)$nc_core->db->get_var("SELECT COUNT(DISTINCT `Hash`) FROM `Security_FilterLog`");

                $filter_query = "SELECT `Security_FilterLog_ID`, MAX(`Timestamp`) as `LastDate`,
                                `FilterType`, `ValueSource`, `CheckedString`, COUNT(*) as `CountElements`, `Hash` 
                                FROM `Security_FilterLog` $group_by_string ";
            }

            $data = $nc_core->db->get_results($filter_query . "LIMIT " . $offset . ", $rec_on_page", ARRAY_A);
        } else {
            $rec_count = (int)$nc_core->db->get_var("SELECT COUNT(*) FROM `Security_FilterLog`");
            $data = $nc_core->db->get_results("SELECT `Security_FilterLog_ID`, `Timestamp`,
                                  `FilterType`, `URL`, `Referer`, `IP`, `Hash` FROM `Security_FilterLog`"
                . "LIMIT " . $offset . ", $rec_on_page", ARRAY_A);
        }

        $view = $this->view('filter/log', array(
            'saved' => false,
            'data' => $data,
            'rec_on_page' => $rec_on_page,
            'rec_count' => $rec_count,
        ));

        return $view;
    }

    protected function action_clear_log() {
        $this->ui_config->set_action('filter', 'log');
        $nc_core = nc_core::get_object();
        $clear_data = $nc_core->input->fetch_get("clear");

        if (!empty($clear_data) && array_filter($clear_data)) {
            if (!empty($clear_data['Date_From']) && !empty($clear_data['Date_To'])) {
                $value_from = date("Y-m-d H:i:s", strtotime($clear_data['Date_From']));
                $value_to = date("Y-m-d H:i:s", strtotime($clear_data['Date_To']) + 86400);
                $nc_core->db->query("DELETE FROM `Security_FilterLog` WHERE `Timestamp` >= '$value_from' AND `Timestamp`<= '$value_to'");
            } else {
                ?>
                <script>
                    alert('<?= NETCAT_SECURITY_ONE_DATE_NOT_SET; ?>');
                    top.urlDispatcher.load('#security.filter.log');
                </script>
                <?php
                die();
            }
        } else {
            ?>
            <script>
                alert('<?= NETCAT_SECURITY_TWO_DATES_NOT_SET; ?>');
                top.urlDispatcher.load('#security.filter.log');
            </script>
            <?php
            die();
        }

        header("Location: {$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}action.php?ctrl=security_filter_admin_log&action=show_log");
        die;
    }

    protected function action_log_record() {
        $this->ui_config->actionButtons = array(
            array(
                'id' => 'goback',
                'caption' => NETCAT_SECURITY_LOG_GOBACK,
                'action' => 'window.history.back()',
                'align' => 'left',
            ),
            array(
                'id' => 'submit',
                'caption' => NETCAT_SECURITY_LOG_REFRESH,
                'action' => 'mainView.refreshIframe()',
                'align' => 'right',
            ),
        );

        $nc_core = nc_core::get_object();
        $security_filterlog_id = $nc_core->input->fetch_get("security_filterlog_id");
        $this->ui_config->set_action('filter', 'log');
        $this->ui_config->locationHash = "security.filter.log.record($security_filterlog_id)";
        $data = $nc_core->db->get_results("SELECT * FROM `Security_FilterLog` WHERE `Security_FilterLog_ID` = $security_filterlog_id", ARRAY_A);

        $view = $this->view('filter/log_record', array(
            'saved' => false,
            'data' => $data,
        ));


        return $view;
    }

}
