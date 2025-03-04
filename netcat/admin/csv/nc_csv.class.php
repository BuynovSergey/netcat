<?php

class nc_csv {

    protected static $instance;
    protected $subdivision_list = array();
    protected $default_fields = array('Keyword', 'ncTitle', 'ncKeywords', 'ncDescription', 'ncSMO_Title', 'ncSMO_Description', 'ncSMO_Image');

    private function __construct() {

    }

    private function __clone() {

    }

    public function __wakeup() {

    }

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function should_include_list_fields(array $csv_settings) {
        return nc_array_value($csv_settings, 'list_id') ||
            nc_array_value($csv_settings, 'list_name') ||
            nc_array_value($csv_settings, 'list_value');
    }

    public function get_allowed_field_types($include_list_fields) {
        $field_types = array(
            NC_FIELDTYPE_STRING,
            NC_FIELDTYPE_INT,
            NC_FIELDTYPE_TEXT,
            NC_FIELDTYPE_BOOLEAN,
            NC_FIELDTYPE_FLOAT,
            NC_FIELDTYPE_FILE,
            NC_FIELDTYPE_DATETIME
        );

        if ($include_list_fields) {
            $field_types[] = NC_FIELDTYPE_SELECT;
        }

        return $field_types;
    }

    public function get_subclass_type_export_form($id = '') {
        $options = array('' => TOOLS_CSV_NOT_SELECTED);

        $result = nc_db_table::make('Catalogue')
            ->select('Catalogue_ID, Catalogue_Name, Domain')
            ->order_by('Priority')
            ->order_by('Catalogue_Name')
            ->order_by('Catalogue_ID')
            ->limit(null)
            ->index_by_id()
            ->as_object()
            ->get_result();

        foreach ($result as $site_id => $row) {
            $options[$site_id] = $site_id . '. ' . $row->Catalogue_Name . ' (' . $row->Domain . ')';
        }

        return nc_core()->ui->form->add_row(TOOLS_CSV_SELECT_SITE)->select('data[site_id]', $options)->attr('id', 'site_id');
    }

    public function get_component_type_export_form($id = '') {
        $options = array('' => TOOLS_CSV_NOT_SELECTED);

        $result = nc_db_table::make('Class')
            ->select('Class_ID, Class_Name, Class_Group, File_Mode')
            ->where('System_Table_ID', 0)->where('ClassTemplate', 0)
            ->order_by('File_Mode', 'DESC')->order_by('Class_Group')->order_by('Class_Name')->limit(null)
            ->as_object()->get_result();

        foreach ($result as $row) {
            $group = $row->Class_Group . ($row->File_Mode ? '' : ' (v4)');
            $options[$group][$row->Class_ID] = $row->Class_ID . '. ' . $row->Class_Name;
        }
        $ret = nc_core()->ui->form->add_row(TOOLS_CSV_SELECT_COMPONENT)->select('data[component_id]', $options)->attr('id', 'component_id');
        $ret .= $this->get_csv_settings_form();
        return $ret;
    }

    /**
     *
     * @param int $id
     * @return string
     */
    public function get_subdivision_export_form($id = '') {
        $options = array('' => TOOLS_CSV_NOT_SELECTED) + $this->get_subdivisions($id);

        $ret = '<div class="nc-form-row"><label>' . TOOLS_CSV_SELECT_SUBDIVISION . '</label>';
        $ret .= '<select name="data[subdivision_id]" id="subdivision_id">';
        foreach ($options as $key => $value) {
            $ret .= '<option value="' . $key . '">';
            $ret .= str_replace("[space]", "&nbsp;", htmlspecialchars($value));
            $ret .= '</option>';
        }
        $ret .= '</select></div>';
        return $ret;
    }

    public function get_component_export_form($id = '') {

        $options = $this->get_subclasses($id);
        if (count($options) > 0) {
            $ret = nc_core()->ui->form->add_row(TOOLS_CSV_SELECT_SUBCLASS)
                ->select('data[subclass_id]', $options)->attr('id', 'subclass_id');
            $ret .= $this->get_csv_settings_form();
            return $ret;
        } else {
            return nc_core()->ui->alert->error(TOOLS_CSV_SUBCLASSES_NOT_FOUND);
        }
    }

    public function get_import_component_export_form($id = '') {
        $options = $this->get_subclasses($id);
        if (count($options) > 0) {
            $ret = nc_core()->ui->form->add_row(TOOLS_CSV_SELECT_SUBCLASS)
                ->select('data[subclass_id]', $options)->attr('id', 'subclass_id');
            return $ret;
        } else {
            return nc_core()->ui->alert->error(TOOLS_CSV_SUBCLASSES_NOT_FOUND);
        }
    }

    protected function export($options) {
        $nc_core = nc_Core::get_object();

        $component_id = (int)nc_array_value($options, 'component_id');
        if (!$component_id) {
            $component_id = $nc_core->sub_class->get_by_id(nc_array_value($options, 'subclass_id'), 'Class_ID');
        }
        if (!$component_id) {
            throw new Exception("Cannot export data: neither 'component_id' nor 'subclass_id' were provided");
        }

        $options['csv']['terminated'] = PHP_EOL;

        $include_list_ids = nc_array_value($options['csv'], 'list_id');
        $include_list_names = nc_array_value($options['csv'], 'list_name');
        $include_list_values = nc_array_value($options['csv'], 'list_value');
        $include_list_fields = $include_list_ids || $include_list_names || $include_list_values;
        $list_fields = array();

        $db = nc_core::get_object()->db;

        // (1) Get fields

        $component = new nc_Component($component_id);
        $class_fields = $component->get_fields($this->get_allowed_field_types($include_list_fields));

        $fields_in_sql = array();
        $fields_in_csv = array();

        if (isset($options['subdivision_id']) && isset($options['subclass_id'])) {
            $values_query_where = "Subdivision_ID = " . (int)$options['subdivision_id'] .
                " AND Sub_Class_ID = " . (int)$options['subclass_id'];
        } else {
            $values_query_where = "1";
        }

        // Если есть записи с Parent_Message_ID (например, варианты товаров),
        // добавим в результаты поля Message_ID и Parent_Message_ID
        $has_record_hierarchy = $db->get_var("SELECT 1 FROM `Message{$component_id}` WHERE $values_query_where AND `Parent_Message_ID` > 0 LIMIT 1");
        if ($has_record_hierarchy) {
            array_push($fields_in_sql, "`Message_ID`", "`Parent_Message_ID`");
            array_push($fields_in_csv, "Message_ID", "Parent_Message_ID");
        }

        foreach ($class_fields as $class_field) {
            $fields_in_sql[] = "`$class_field[name]`";
            if ($class_field['type'] == NC_FIELDTYPE_SELECT) {
                $list_fields[$class_field['name']] = $class_field['table'];
                if ($include_list_ids) {
                    $fields_in_csv[] = "$class_field[name].ID";
                }
                if ($include_list_names) {
                    $fields_in_csv[] = (string)$class_field['name'];
                }
                if ($include_list_values) {
                    $fields_in_csv[] = "$class_field[name].Value";
                }
            } else {
                $fields_in_csv[] = $class_field['name'];
            }
        }

        // (2) Get data

        $values_in_csv = array();
        $values_query = "SELECT " . implode(", ", (array_merge($fields_in_sql, $this->default_fields))) .
            "  FROM Message" . $component_id .
            " WHERE " . $values_query_where;

        $result = (array)$db->get_results($values_query, ARRAY_A);

        if ($include_list_fields) {
            foreach ($result as $result_row) {
                $values_row = array();
                foreach ($result_row as $field => $value) {
                    if (isset($list_fields[$field])) {
                        $list_item = $this->get_list_element_properties_by_id($list_fields[$field], $value);
                        if ($include_list_ids) {
                            $values_row[] = $value;
                        }
                        if ($include_list_names) {
                            $values_row[] = $list_item['Name'];
                        }
                        if ($include_list_values) {
                            $values_row[] = $list_item['Value'];
                        }
                    } else {
                        $values_row[] = $value;
                    }
                }
                $values_in_csv[] = $values_row;
            }
        } else {
            $values_in_csv[] = array_values($result);
        }

        $csv_header = $this->csv_encode_header(array_merge($fields_in_csv, $this->default_fields), $options['csv']);
        $csv_data = $this->csv_encode_data($values_in_csv, $options['csv']);

        return $this->save_to_file($csv_header . $csv_data, 'export-' . date('YmdHis') . '.csv');
    }

    public function export_subclass_type($options) {
        return $this->export($options);
    }

    public function export_component_type($options) {
        return $this->export($options);
    }

    public function preimport_file($file, $data) {
        if (!$file) {
            throw new Exception(TOOLS_CSV_IMPORT_FILE_NOT_FOUND, 1);
        }

        if (!is_dir($file)) {
            $tmp_file = nc_Core::get_object()->TMP_FOLDER . uniqid('', true) . '.csv';
            copy($file, $tmp_file);
        } else {
            $tmp_file = null;
        }

        if (!file_exists($tmp_file)) {
            throw new Exception(TOOLS_CSV_IMPORT_FILE_NOT_FOUND . " " . $tmp_file, 1);
        }

        $head_fields = $this->process_csv_header($tmp_file, $data['csv']);

        $class_id = $this->get_class_id($data['subclass_id']);

        $include_list_fields = $this->should_include_list_fields($data['csv']);
        $class_fields = $this->get_class_fields($class_id, $this->get_allowed_field_types($include_list_fields));
        $component_fields = array();
        foreach ($class_fields as $class_field) {
            $component_fields[$class_field['name']] = $class_field['description'];
        }

        $fields = $component_fields +
            array_combine($this->default_fields, $this->default_fields) +
            array('Message_ID' => TOOLS_CSV_RECORD_ID, 'Parent_Message_ID' => TOOLS_CSV_PARENT_RECORD_ID);

        if ($include_list_fields) {
            // Для полей типа «Список», полученных при экспорте (имеют вид
            // ИмяПоля.ID, ИмяПоля.Name, ИмяПоля.Value) показать в сопоставлении
            // полей только фрагмент ИмяПоля
            $unique_csv_head_fields = array();
            foreach ($head_fields as $value) {
                if (preg_match('/^\w+\.(ID|Name|Value)$/', $value)) {
                    $field_name = strtok($value, ".");
                    if (isset($fields[$field_name])) {
                        $value = $field_name;
                    }
                }
                $unique_csv_head_fields[$value] = $value;
            }
        } else {
            $unique_csv_head_fields = array_combine($head_fields, $head_fields);
        }

        return array(
            'site_id' => $data['site_id'],
            'subdivision_id' => $data['subdivision_id'],
            'subclass_id' => $data['subclass_id'],
            'csv_head' => array('' => TOOLS_CSV_NOT_SELECTED) + $unique_csv_head_fields,
            'fields' => $fields,
            'csv_settings' => $data['csv'],
            'file' => $tmp_file
        );
    }

    public function import_file($file, $data) {
        if (!$file) {
            throw new Exception(TOOLS_CSV_IMPORT_FILE_NOT_FOUND, 1);
        }
        if (!file_exists($file)) {
            throw new Exception(TOOLS_CSV_IMPORT_FILE_NOT_FOUND . " " . $file, 1);
        }


        $csv_result = $this->process_csv($file, $data['csv']);
        $csv_data = $csv_result['data'];

        unlink($file);

        // $data['fields'] содержит соответствие [sql_field => csv_field]
        $fields = array_filter($data['fields'], 'strlen');

        // [csv_id => db_id]
        $id_csv_to_db = array();
        // [csv_id => csv_parent_id]
        $id_csv_to_csv_parent = array();

        // колонки Message_ID, Parent_Message_ID в файле
        $csv_id_field = nc_array_value($fields, 'Message_ID');
        $csv_parent_id_field = nc_array_value($fields, 'Parent_Message_ID');
        unset($fields['Message_ID'], $fields['Parent_Message_ID']);
        // есть хоть одна запись с Parent_Message_ID?
        $has_records_with_parent_id = false;

        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        global $AUTH_USER_ID, $HTTP_USER_AGENT;
        $default_fields = array(
            'User_ID' => $AUTH_USER_ID,
            'Subdivision_ID' => $data['subdivision_id'],
            'Sub_Class_ID' => $data['subclass_id'],
            'Created' => date("Y-m-d H:i:s"),
            'LastUpdated' => date("Y-m-d H:i:s"),
            'UserAgent' => $HTTP_USER_AGENT,
            'LastUserAgent' => $HTTP_USER_AGENT,
            'IP' => getenv("REMOTE_ADDR"),
            'LastIP' => getenv("REMOTE_ADDR"),
        );
        $component_id = (int)$this->get_class_id($data['subclass_id']);
        $priority = $this->get_max_priority($component_id);

        $include_list_ids = nc_array_value($data['csv'], 'list_id');
        $include_list_names = nc_array_value($data['csv'], 'list_name');
        $include_list_values = nc_array_value($data['csv'], 'list_value');
        $include_list_fields = $include_list_ids || $include_list_names || $include_list_values;
        $list_fields = array();

        if ($include_list_fields) {
            $list_fields_info = $this->get_class_fields($component_id, NC_FIELDTYPE_SELECT);
            foreach ($list_fields_info as $f) {
                $list_fields[$f['name']] = $f['table'];
            }
        }

        $values = array();
        foreach ($csv_data as $csv_data_row) {
            $row_values = array();
            foreach ($fields as $sql_field => $csv_field) {
                if ($include_list_fields && isset($list_fields[$sql_field])) {
                    // Поле типа «Список»
                    $row_values[] = $this->get_list_item_id_for_csv_row(
                        $csv_field,
                        $list_fields[$sql_field],
                        $csv_data_row,
                        $include_list_ids,
                        $include_list_names,
                        $include_list_values,
                        $data['csv']['null']
                    ) ?: 'NULL';
                } else {
                    $value = $csv_data_row[$csv_field];
                    if ($value == $data['csv']['null']) {
                        $row_values[] = 'NULL';
                    } else {
                        $row_values[] = "'" . $db->escape($value) . "'";
                    }
                }
            }

            foreach ($default_fields as $field) {
                $row_values[] = "'" . $db->escape($field) . "'";
            }

            $row_values[] = ++$priority;
            $values[] = $row_values;

            // эта запись имеет Parent_Message_ID?
            if ($csv_parent_id_field && $csv_data_row[$csv_parent_id_field]) {
                $has_records_with_parent_id = true;
            }
        }

        $sql_fields = "`" . implode("`, `", array_merge(array_keys($fields), array_keys($default_fields))) . "`, `Priority`";

        $imported_record_count = 0;
        $is_logged = false;
        $history_id = 0;

        foreach ($values as $i => $row) {
            $db->query("INSERT INTO Message{$component_id} ($sql_fields) VALUES (" . implode(", ", $row) . ")");
            if ($db->insert_id > 0) {
                $imported_record_count++;
                $message_id = $db->insert_id;
                if ($is_logged == false) {
                    $db->query("INSERT INTO Csv_Import_History (Class_ID, Created) VALUES ($component_id,  NOW())");
                    $history_id = $db->insert_id;
                    $is_logged = true;
                }
                $db->query("INSERT INTO Csv_Import_History_Ids (History_ID, Message_ID) VALUES ($history_id, $message_id)");

                // Сохранение соответствия csv_id и $message_id
                if ($has_records_with_parent_id) {
                    $row_csv_id = $csv_data[$i][$csv_id_field];
                    $id_csv_to_db[$row_csv_id] = $message_id;

                    // если есть csv_parent_id, сохраняем в отдельный массив для облегчения перебора ниже
                    $row_csv_parent_id = $csv_data[$i][$csv_parent_id_field];
                    if ($row_csv_parent_id) {
                        $id_csv_to_csv_parent[$row_csv_id] = $row_csv_parent_id;
                    }
                }
            }
        }

        // обновление Parent_Message_ID
        if ($has_records_with_parent_id) {
            foreach ($id_csv_to_csv_parent as $row_csv_id => $row_csv_parent_id) {
                $db_id = nc_array_value($id_csv_to_db, $row_csv_id, 0);
                $db_parent_id = nc_array_value($id_csv_to_db, $row_csv_parent_id, 0);
                if ($db_id && $db_parent_id) {
                    $db->query("UPDATE `Message{$component_id}` SET `Parent_Message_ID` = $db_parent_id WHERE `Message_ID` = $db_id");
                }
            }
        }

        return array(
            'success' => $imported_record_count,
            'warnings' => $csv_result['warnings'],
        );

    }

    public function get_csv_settings_form() {
        $form = nc_core::get_object()->ui->form;
        return $form->add_row('<strong>' . TOOLS_CSV_SELECT_SETTINGS . '</strong>') .
            $form->add_row(TOOLS_CSV_OPT_CHARSET)->select('data[csv][charset]', array('utf8' => TOOLS_CSV_OPT_CHARSET_UTF8, 'cp1251' => TOOLS_CSV_OPT_CHARSET_CP1251)) .
            $form->add_row(TOOLS_CSV_OPT_SEPARATOR)->string('data[csv][separator]', ";") .
            $form->add_row(TOOLS_CSV_OPT_ENCLOSED)->string('data[csv][enclosed]', "\"") .
            $form->add_row(TOOLS_CSV_OPT_ESCAPED)->string('data[csv][escaped]', "\"") .
            $form->add_row(TOOLS_CSV_OPT_NULL)->string('data[csv][null]', "NULL") .
            $form->add_row(TOOLS_CSV_OPT_LISTS)->div(
                '<label><input type="checkbox" name="data[csv][list_name]" value="1" id="nc_csv_list_names" checked> ' . TOOLS_CSV_OPT_LISTS_NAME . "</label><br>" .
                '<label><input type="checkbox" name="data[csv][list_value]" value="1" id="nc_csv_list_values"> ' . TOOLS_CSV_OPT_LISTS_VALUE . "</label><br>" .
                '<script>$nc(function() {
                       $nc("#nc_csv_list_names").change(function() {
                           var v = $nc("#nc_csv_list_values");
                           if ($nc(this).is(":checked")) { v.prop("disabled", false); }
                           else { v.prop({disabled: true, checked: false}); }
                       });
                    });
                    </script>'
            )->attr("style", "padding-top: 6px");
    }

    public function history_list() {
        $list = array();

        $result = nc_core()->db->get_results("SELECT cih.History_ID, cih.Created, "
            . "c.Class_Name, COUNT(cihi.History_ID), cih.Rollbacked "
            . "FROM Csv_Import_History AS cih "
            . "LEFT JOIN Class AS c ON c.Class_ID = cih.Class_ID "
            . "LEFT JOIN Csv_Import_History_Ids AS cihi ON cihi.History_ID = cih.History_ID "
            . "GROUP BY cih.History_ID "
            . "ORDER BY cih.History_ID DESC", ARRAY_N);
        foreach ((array)$result as $Array) {
            $list[$Array[0]] = array('Created' => date('d-m-Y H:i', strtotime($Array[1])), 'Class_Name' => $Array[2], 'Rows' => $Array[3], 'Rollbacked' => $Array[4]);
        }
        return $list;
    }

    public function rollback($id = 0) {
        $result = nc_core()->db->get_results("SELECT cihi.Message_ID, cih.Class_ID "
            . "FROM Csv_Import_History AS cih "
            . "LEFT JOIN Csv_Import_History_Ids AS cihi ON cihi.History_ID = cih.History_ID "
            . "WHERE cih.History_ID='" . intval($id) . "' AND cih.Rollbacked='0' ", ARRAY_N);
        $remove = array();
        foreach ($result as $Array) {
            array_push($remove, $Array[0]);
        }
        if (count($remove) > 0) {
            nc_core()->db->query("DELETE FROM Message{$result[0][1]} WHERE Message_ID IN (" . implode(",", $remove) . ")");
            nc_core()->db->query("UPDATE Csv_Import_History SET Rollbacked=1 WHERE History_ID='" . intval($id) . "'");
        }
        return array('rollbacked' => count($remove));

    }

    /**
     *
     * @param int $CatalogueID
     * @return array
     */
    protected function get_subdivisions($CatalogueID = 0) {
        $this->subdivision_list = array();
        $this->get_subdivisions_tree(0, $CatalogueID);

        return $this->subdivision_list;
    }

    /**
     *
     * @global Permission $perm
     * @param int $ParentSubID
     * @param int $CatalogueID
     * @param int $count
     */
    protected function get_subdivisions_tree($ParentSubID, $CatalogueID, $count = 1) {
        global $perm;

        $CatalogueID = intval($CatalogueID);
        $ParentSubID = intval($ParentSubID);

        $security_limit = "";

        if (empty($initialized)) {
            $initialized = true;
            $allow_id = $perm->GetAllowSub($CatalogueID, MASK_ADMIN | MASK_MODERATE);
            $security_limit = is_array($allow_id) && !$perm->isGuest() ? " Subdivision_ID IN (" . join(', ', (array)$allow_id) . ")" : " 1";
        }

        $Result = nc_core()->db->get_results(
            "SELECT a.Subdivision_ID,a.Subdivision_Name FROM Subdivision AS a, Catalogue AS b
             WHERE a.Catalogue_ID=b.Catalogue_ID AND a.Catalogue_ID=" . $CatalogueID . "
             AND a.Parent_Sub_ID='" . $ParentSubID . "' AND " . $security_limit . " ORDER BY a.Priority",
            ARRAY_N
        );

        if (!empty($Result)) {
            foreach ($Result as $Array) {
                $this->subdivision_list[$Array[0]] = str_repeat('[space]→[space]', $count) . $Array[1];
                $this->get_subdivisions_tree($Array[0], $CatalogueID, $count + 1);
            }
        }
    }

    protected function get_subclasses($Subdivision_ID = 0) {
        $ret = array();
        $Select = "SELECT a.Sub_Class_ID, a.Sub_Class_Name
                   FROM (Sub_Class AS a,
                        Class AS b)
                     LEFT JOIN Subdivision AS c ON a.Subdivision_ID = c.Subdivision_ID
                     LEFT JOIN Catalogue AS d ON c.Catalogue_ID = d.Catalogue_ID
                       WHERE a.Subdivision_ID = " . intval($Subdivision_ID) . "
                         AND a.Class_ID = b.Class_ID
                         AND b.System_Table_ID = 0
                         AND b.ClassTemplate = 0
                           ORDER BY a.Priority";

        $Result = nc_core()->db->get_results($Select, ARRAY_N);

        if (nc_core()->db->num_rows > 0) {
            foreach ($Result as $Array) {
                $ret[$Array[0]] = $Array[1];
            }
        }
        return $ret;
    }

    protected function csv_encode_header($fields, $settings) {
        $charset = $settings['charset'];
        $ret = "";
        end($fields);
        $lastElementFields = key($fields);
        foreach ($fields as $k => $title) {
            if ($charset == 'cp1251') {
                $title = nc_Core::get_object()->utf8->utf2win($title);
            }
            if ($settings['enclosed'] == '') {
                $ret .= stripslashes($title);
            } else {
                $ret .= $settings['enclosed']
                    . str_replace(
                        $settings['enclosed'], $settings['escaped'] . $settings['enclosed'], stripslashes($title)
                    )
                    . $settings['enclosed'];
            }

            if ($k != $lastElementFields) {
                $ret .= $settings['separator'];
            }
        }
        $ret .= $settings['terminated'];
        return $ret;
    }

    protected function csv_encode_data($rows, $settings) {
        $charset = $settings['charset'];
        $ret = "";
        end($rows);
        $lastElementRows = key($rows);

        foreach ($rows as $k => $fields) {
            end($fields);
            $lastElementFields = key($fields);
            foreach ($fields as $j => $value) {
                if (!isset($value) || is_null($value)) {
                    $ret .= $settings['null'];
                } else {
                    $value = str_replace("\n", "", str_replace("\r", "", $value));
                    if ($charset == 'cp1251') {
                        $value = nc_Core::get_object()->utf8->utf2win($value);
                    }
                    if ($settings['enclosed'] == '') {
                        $ret .= $value;
                    } else {
                        // also double the escape string if found in the data
                        if ($settings['escaped'] != $settings['enclosed']) {
                            $ret .= $settings['enclosed']
                              . str_replace(
                                $settings['enclosed'], $settings['escaped'] . $settings['enclosed'], str_replace(
                                  $settings['escaped'], $settings['escaped'] . $settings['escaped'], $value
                                )
                              )
                              . $settings['enclosed'];
                        } else {
                            $ret .= $settings['enclosed']
                              . str_replace(
                                $settings['enclosed'], $settings['escaped'] . $settings['enclosed'], $value
                              )
                              . $settings['enclosed'];
                        }
                    }
                }
                if ($j != $lastElementFields) {
                    $ret .= $settings['separator'];
                }
            }
            if ($k != $lastElementRows) {
                $ret .= $settings['terminated'];
            }
        }
        return $ret;
    }

    protected function save_to_file($content, $file_name) {
        $export_dir = nc_core()->backup->get_export_path();

        $folder = "csv";
        $path = $export_dir . "/" . $folder;

        if (!file_exists($path)) {
            if (!file_exists($export_dir)) {
                mkdir($export_dir);
            }
            mkdir($path);
        }

        $fp = fopen($path . "/" . $file_name, "w+");

//        if (nc_core()->NC_UNICODE || empty(nc_core()->NC_CHARSET)) {
//            fwrite($fp, "\xEF\xBB\xBF");
//        }
        fwrite($fp, $content);
        fclose($fp);
        return array(
            $file_name,
            nc_core()->backup->get_export_http_path() . $folder . "/" . $file_name,
            nc_core()->ADMIN_PATH . "/#tools.csv.delete(" . urlencode($file_name) . ")"
        );
    }

    protected function process_csv_header($file, $settings) {
        $nc_core = nc_core::get_object();
        $convert = ($settings['charset'] == 'cp1251' && $nc_core->NC_UNICODE);

        $fp = fopen($file, 'r+');
        $data = fgetcsv($fp, 0, $settings['separator'], $settings['enclosed']);
        fclose($fp);
        $header = array();
        foreach ($data as $key => $value) {
            if ($convert) {
                $header[$key] = $nc_core->utf8->win2utf($value);
            } else {
                $header[$key] = $value;
            }
        }
        return $header;
    }

    /**
     * @param $file
     * @param $settings
     * @return array[] массив с элементами data (данные из файла) и warnings (строки
     *   с предупреждениями о возникших проблемах)
     */
    protected function process_csv($file, $settings) {
        foreach ($settings as $key => $value) {
            $settings[$key] = stripcslashes($value);
        }

        $warnings = array();

        $charset = $settings['charset'];

        $rowcount = 0;
        $csv = array();
        $fp = fopen($file, 'r+');
        $header = fgetcsv($fp, 0, $settings['separator'], $settings['enclosed']);
        if ($charset == 'cp1251') {
            foreach ($header as $key => $value) {
                $header[$key] = nc_Core::get_object()->utf8->win2utf($value);
            }
        }
        $header_colcount = count($header);
        while (($row = fgetcsv($fp, 0, $settings['separator'], $settings['enclosed'])) !== false) {
            if ($charset == 'cp1251') {
                foreach ($row as $key => $value) {
                    $row[$key] = nc_Core::get_object()->utf8->win2utf($value);
                }
            }
            $row_colcount = count($row);
            if ($row_colcount == $header_colcount) {
                foreach ($row as $k => $value) {
                    $row[$k] = str_replace($settings['escaped'] . $settings['escaped'], $settings['escaped'], $value);
                }
                $entry = array_combine($header, $row);
                $csv[] = $entry;
            } else {
                $warnings[] = sprintf(
                    TOOLS_CSV_IMPORT_COLUMN_COUNT_MISMATCH,
                    $rowcount + 2,
                    $header_colcount,
                    $row_colcount
                );
            }
            $rowcount++;
        }

        fclose($fp);

        return array(
            'data' => $csv,
            'warnings' => $warnings,
        );
    }

    protected function get_class_id($sub_class_id = 0) {
        return nc_core()->db->get_var(
            'SELECT Class.Class_ID
             FROM Sub_Class
             LEFT JOIN Class ON Sub_Class.Class_ID = Class.Class_ID
             WHERE Class.System_Table_ID = 0
             AND Class.ClassTemplate = 0
             AND Sub_Class.Sub_Class_ID = ' . (int)$sub_class_id);
    }

    protected function get_class_fields($class_id, $types = null) {
        try {
            nc_core::get_object()->component->get_by_id($class_id);
        } catch (nc_Exception_Class_Doesnt_Exist $e) {
            return array();
        }

        $component = new nc_Component($class_id);

        return $component->get_fields($types);
    }

    protected function get_max_priority($class_id = 0) {
        return nc_Core::get_object()->db->get_var("SELECT MAX(Priority) FROM Message{$class_id}");
    }

    /**
     * Возвращает информацию (Name, Value) о элементе списка с указанным ID,
     * кеширует данные
     *
     * @param $list_name
     * @param $element_id
     * @return array|false
     */
    protected function get_list_element_properties_by_id($list_name, $element_id) {
        static $cache = array();
        $key = "$list_name:$element_id";
        if (!isset($cache[$key])) {
            $data = nc_db()->get_row(
                "SELECT {$list_name}_Name AS Name, Value
                   FROM Classificator_{$list_name}
                  WHERE {$list_name}_ID = " . (int)$element_id,
                ARRAY_A);
            $cache[$key] = ($data ?: false);
        }
        return $cache[$key];
    }

    /**
     * Возвращает ID элемента списка по названию и дополнительному значению элемента
     *
     * @param $list_name
     * @param $element_name
     * @internal param $element_value
     * @return int|false
     */
    protected function get_list_element_id_by_name($list_name, $element_name) {
        static $cache = array();
        $element_name = trim($element_name);
        $key = "$list_name:$element_name";
        if (!isset($cache[$key])) {
            $db = nc_db();
            $list_name = $db->escape($list_name);
            $data = $db->get_var(
                "SELECT {$list_name}_ID
                   FROM Classificator_{$list_name}
                  WHERE {$list_name}_Name = '" . $db->escape($element_name) . "'"
            );
            $cache[$key] = ($data ?: false);
        }
        return $cache[$key];
    }

    /**
     * @param $list_name
     * @param $element_name
     * @param $element_value
     * @return int
     */
    protected function create_list_element($list_name, $element_name, $element_value) {
        $db = nc_db();
        $list_name = $db->escape($list_name);
        $priority = $db->get_var("SELECT MAX({$list_name}_Priority) FROM Classificator_{$list_name}") + 1;
        $db->query(
            "INSERT INTO Classificator_{$list_name}
                SET {$list_name}_Name = '" . $db->escape($element_name) . "',
                    {$list_name}_Priority = $priority,
                    Value = " . (strlen($element_value) ? "'" . $db->escape($element_value) . "'" : "NULL")
        );
        return $db->insert_id;
    }

    /**
     * Возвращает значение для поля типа «список»:
     * если указано сопоставление ID, пытается найти элемент с этим ID;
     * если указано сопоставление имён элементов, пытается найти элемент с таким именем и дополнительным значением
     * если подходящий элемент не найден, добавляет запись в список
     *
     * @param $csv_field
     * @param $list_name
     * @param $csv_data_row
     * @param $includes_id
     * @param $includes_name
     * @param $includes_value
     * @param $null_value
     * @return int|null
     */
    protected function get_list_item_id_for_csv_row($csv_field, $list_name, $csv_data_row, $includes_id, $includes_name, $includes_value, $null_value) {
        $element_id = $element_name = $element_value = null;

        if ($includes_id) { // ID?
            // Если в CSV есть колонка "ИмяПоля.ID", берём ID оттуда, иначе берём из "ИмяПоля"
            if (isset($csv_data_row["$csv_field.ID"]) && (int)$csv_data_row["$csv_field.ID"]) {
                $element_id = (int)$csv_data_row["$csv_field.ID"];
            } elseif (isset($csv_data_row[$csv_field]) && (int)$csv_data_row[$csv_field]) {
                $element_id = (int)$csv_data_row[$csv_field];
            }

            if ($element_id) {
                $list_item_exists = $this->get_list_element_properties_by_id($list_name, $element_id);
                if (!$list_item_exists) {
                    $element_id = null;
                }
            }
        }

        if (!$element_id && $includes_name) { // Name?
            // Если в CSV есть колонка "ИмяПоля.Name", берём ID оттуда, иначе берём из "ИмяПоля"
            if (isset($csv_data_row["$csv_field.Name"]) && strlen($csv_data_row["$csv_field.Name"])) {
                $element_name = $csv_data_row["$csv_field.Name"];
            } elseif (isset($csv_data_row[$csv_field]) && !$includes_id && strlen($csv_data_row[$csv_field])) {
                $element_name = $csv_data_row[$csv_field];
            }

            if ($includes_value && isset($csv_data_row["$csv_field.Value"]) && strlen($csv_data_row["$csv_field.Value"]) && $csv_data_row["$csv_field.Value"] != $null_value) {
                $element_value = $csv_data_row["$csv_field.Value"];
            }

            if ($element_name == $null_value) {
                $element_name = null;
            }

            if (strlen($element_name)) {
                $element_id = $this->get_list_element_id_by_name($list_name, $element_name);
                if (!$element_id) {
                    $element_id = $this->create_list_element($list_name, $element_name, $element_value);
                }
            }
        }

        return $element_id;
    }

}
