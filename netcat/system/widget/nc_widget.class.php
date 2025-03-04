<?php

class nc_Widget extends nc_System {

    const MAX_KEYWORD_LENGTH = 64;

    protected $core;

    /** @var array  (значение должно быть true) */
    protected $sub_modules = array(
        'block_widget_table' => 'nc_block_widget_table',
        'aggregator'         => 'nc_widget_aggregator',
    );

    public $widgetclasses        = array();
    public $widgetclasses_keys   = array();
    public $widgetclasses_loaded = false;
    public $widgets              = array();
    public $widgets_keys         = array();
    public $widgets_loaded       = false;

    protected $default_field_values = array();


    public function __construct() {
        parent::__construct();

        $this->core = nc_core::get_object();
        $this->core->register_macrofunc('NC_WIDGET_SHOW', 'show_macrofunc', $this);
    }



    /**
     * Обеспечивает загрузку «подмодулей» по запросу
     * @param $sub_module_name
     * @return null|object
     */
    public function __get($sub_module_name) {

        if (!isset($this->sub_modules[$sub_module_name])) {
            return null;
        }

        if (is_string($this->sub_modules[$sub_module_name])) {
            $class_name = $this->sub_modules[$sub_module_name];
            require_once dirname(__FILE__) . '/classes/' . $class_name . '.class.php';
            $this->sub_modules[$sub_module_name] = new $class_name($this);
        }

        return $this->sub_modules[$sub_module_name];
    }



    public function validate_keyword($keyword) {
        return preg_match('/^\w+$/i', $keyword) &&
               !preg_match('/^\d+$/', $keyword) &&
               strlen($keyword) <= self::MAX_KEYWORD_LENGTH;
    }



    public function load($load = 'widgetclasses') {
        switch ($load) {
            case 'widgetclasses':
                $widgetclasses = $this->core->db->get_results("
                    SELECT
                        wc.`Widget_Class_ID`,
                        wc.`Name`,
                        wc.`Keyword`,
                        wc.`Description`,
                        wc.`Category`,
                        wc.`InDevelop`,
                        wc.`Template`,
                        wc.`Settings`,
                        wc.`AddForm`,
                        wc.`EditForm`,
                        wc.`AfterSaveAction`,
                        wc.`BeforeSaveAction`,
                        wc.`User_ID`,
                        wc.`LastUser_ID`,
                        wc.`Created`,
                        wc.`LastUpdated`,
                        wc.`WidgetDisallow`,
                        wc.`Update`,
                        wc.`File_Path`,
                        wc.`File_Mode`,
                        wc.`IsStatic`,
                        count(f.`Field_ID`) AS `Fields`
                    FROM `Widget_Class` AS wc
                    LEFT JOIN `Field` AS f ON wc.`Widget_Class_ID` = f.`Widget_Class_ID`
                    GROUP BY wc.`Widget_Class_ID`");

                if (!empty($widgetclasses)) {
                    $wc_edit = new nc_tpl_widget_editor($this->core->WIDGET_TEMPLATE_FOLDER, $this->core->db);
                    foreach ($widgetclasses as $wc) {
                        if ($wc->File_Mode) {
                            $wc_edit->load($wc->Widget_Class_ID, $wc->File_Path)->fill_fields();
                        }
                        $this->widgetclasses[$wc->Widget_Class_ID] = array(
                            'Widget_Class_ID'  => $wc->Widget_Class_ID,
                            'Name'             => $wc->Name,
                            'Keyword'          => $wc->Keyword,
                            'Description'      => $wc->Description,
                            'Category'         => $wc->Category,
                            'InDevelop'        => $wc->InDevelop,
                            'Template'         => $wc->File_Mode ? $wc_edit->get_field('Template') : $wc->Template,
                            'Settings'         => $wc->File_Mode ? $wc_edit->get_field('Settings') : $wc->Settings,
                            'AddForm'          => $wc->File_Mode ? $wc_edit->get_field('AddForm') : $wc->AddForm,
                            'EditForm'         => $wc->File_Mode ? $wc_edit->get_field('EditForm') : $wc->EditForm,
                            'AfterSaveAction'  => $wc->File_Mode ? $wc_edit->get_field('AfterSaveAction') : $wc->AfterSaveAction,
                            'BeforeSaveAction' => $wc->File_Mode ? $wc_edit->get_field('BeforeSaveAction') : $wc->BeforeSaveAction,
                            'User_ID'          => $wc->User_ID,
                            'LastUser_ID'      => $wc->LastUser_ID,
                            'Created'          => $wc->Created,
                            'LastUpdated'      => $wc->LastUpdated,
                            'WidgetDisallow'   => $wc->WidgetDisallow,
                            'Update'           => $wc->Update,
                            'File_Path'        => $wc->File_Path,
                            'File_Mode'        => $wc->File_Mode,
                            'Fields'           => $wc->Fields,
                            'IsStatic'         => $wc->IsStatic
                        );

                        if (!in_array($wc->Keyword, $this->widgetclasses_keys)) {
                            $this->widgetclasses_keys[$wc->Keyword] = (int) $wc->Widget_Class_ID;
                        }
                    }
                    $this->widgetclasses_loaded = true;
                }
                break;

            case 'widgets':
                $widgets = $this->core->db->get_results("SELECT * FROM `Widget`");
                if (!empty($widgets)) {
                    foreach ($widgets as $w) {
                        $this->widgets[$w->Widget_ID] = array(
                                'Widget_ID'       => $w->Widget_ID,
                                'Widget_Class_ID' => $w->Widget_Class_ID,
                                'Name'            => $w->Name,
                                'Keyword'         => $w->Keyword,
                                'Result'          => $w->Result,
                                'User_ID'         => $w->User_ID,
                                'LastUser_ID'     => $w->LastUser_ID,
                                'Created'         => $w->Created,
                                'LastUpdated'     => $w->LastUpdated);

                        if (!in_array($w->Keyword, $this->widgets_keys)) {
                            $this->widgets_keys[$w->Keyword] = (int) $w->Widget_ID;
                        }
                    }
                    $this->widgets_loaded = true;
                }
                break;
        }
    }



    public function get_where($id, $widget = 0) {
        return " WHERE " . (is_int($id) ? ($widget ? "`Widget_ID`='" . $id . "'" : "`Widget_Class_ID`='" . $id . "'") : "`Keyword`='" . $this->core->db->escape($id) . "'");
    }



    public function get_id($id, $widget = 0) {
        return (is_int($id) ? $id : ($widget ? $this->widgets_keys[$id] : $this->widgetclasses_keys[$id]));
    }



    public function assistant($action, $params = '', $widget = 0) {
        global $AUTH_USER_ID;
        $result = null;
        switch ($action) {
            case 'select':
                if ($params[1] == '') {
                    $result = ($widget ? (is_int($params[0]) ? $this->widgets[$params[0]] : $this->widgets[$this->widgets_keys[$params[0]]]) : (is_int($params[0]) ? $this->widgetclasses[$params[0]] : $this->widgetclasses[$this->widgetclasses_keys[$params[0]]]));
                } else {
                    $result = ($widget ? (is_int($params[0]) ? $this->widgets[$params[0]][$params[1]] : $this->widgets[$this->widgets_keys[$params[0]]][$params[1]]) : (is_int($params[0]) ? $this->widgetclasses[$params[0]][$params[1]] : $this->widgetclasses[$this->widgetclasses_keys[$params[0]]][$params[1]]));
                }
                return $result;
                break;
            case 'add':
                if (!$widget) {
                    $i = 0;
                    foreach ($params[1] as $p) {
                        $this->widgetclasses[$params[0]][$p] = $params[2][$i];
                        $i++;
                    }
                    $this->widgetclasses[$params[0]]['User_ID'] = $AUTH_USER_ID;
                    $this->widgetclasses[$params[0]]['LastUser_ID'] = $AUTH_USER_ID;
                    $this->widgetclasses[$params[0]]['Created'] = date('Y-m-d H:i:s');
                    $this->widgetclasses[$params[0]]['LastUpdated'] = date('Y-m-d H:i:s');
                } else {
                    $i = 0;
                    foreach ($params[1] as $p) {
                        $this->widgets[$params[0]][$p] = $params[2][$i];
                        $i++;
                    }
                    $this->widgets[$params[0]]['User_ID'] = $AUTH_USER_ID;
                    $this->widgets[$params[0]]['LastUser_ID'] = $AUTH_USER_ID;
                    $this->widgets[$params[0]]['Created'] = date('Y-m-d H:i:s');
                    $this->widgets[$params[0]]['LastUpdated'] = date('Y-m-d H:i:s');
                }
                break;
            case 'edit':
                if (!$widget) {
                    foreach ($params[1] as $p => $value) {
                        if (is_int($params[0])) {
                            $this->widgetclasses[$params[0]][$p] = $value;
                        } else {
                            $this->widgetclasses[$this->widgetclasses_keys[$params[0]]][$p] = $value;
                        }
                    }
                } else {
                    foreach ($params[1] as $p => $value) {
                        if (is_int($params[0])) {
                            $this->widgets[$params[0]][$p] = $value;
                        } else {
                            $this->widgets[$this->widgets_keys[$params[0]]][$p] = $value;
                        }
                    }
                }
                break;
            case 'drop':
                if (!$widget) {
                    if (is_int($params)) {
                        unset($this->widgetclasses[$params]);
                    } else {
                        unset($this->widgetclasses[$this->widgetclasses_keys[$params]]);
                    }
                    unset($this->widgetclasses_keys[$params]);
                } else {
                    if (is_int($params)) {
                        unset($this->widgets[$params]);
                    } else {
                        unset($this->widgets[$this->widgets_keys[$params]]);
                    }
                    unset($this->widgets_keys[$params]);
                }
                break;
            case 'list':
                if (!$widget) {
                    foreach ($this->widgetclasses as $wc) {
                        if ($wc['File_Mode'] == +$_REQUEST['fs']) {
                            $result[$wc['Widget_Class_ID']] = array(
                                'Name'             => $wc['Name'],
                                'Keyword'          => $wc['Keyword'],
                                'Description'      => $wc['Description'],
                                'Category'         => $wc['Category'],
                                'InDevelop'        => $wc['InDevelop'],
                                'Template'         => $wc['Template'],
                                'Settings'         => $wc['Settings'],
                                'AddForm'          => $wc['AddForm'],
                                'EditForm'         => $wc['EditForm'],
                                'AfterSaveAction'  => $wc['AfterSaveAction'],
                                'BeforeSaveAction' => $wc['BeforeSaveAction'],
                                'User_ID'          => $wc['User_ID'],
                                'LastUser_ID'      => $wc['LastUser_ID'],
                                'Created'          => $wc['Created'],
                                'LastUpdated'      => $wc['LastUpdated'],
                                'WidgetDisallow'   => $wc['WidgetDisallow'],
                                'Update'           => $wc['Update'],
                                'Fields'           => $wc['Fields'],
                                'IsStatic'         => $wc['IsStatic'],
                            );
                        }
                    }
                } else {
                    foreach ($this->widgets as $w) {
                        if ($wc->File_Mode == +$_REQUEST['fs']) {
                            $result[$w['Widget_Class_ID']] = array('Widget_Class_ID' => $w['Widget_Class_ID'], 'Name' => $w['Name'], 'Keyword' => $w['Keyword'], 'Result' => $w['Result'], 'User_ID' => $w['User_ID'], 'LastUser_ID' => $w['LastUser_ID'], 'Created' => $w['Created'], 'LastUpdated' => $w['LastUpdated']);
                        }
                    }
                }
                return $result;
        };
    }



    /**
     * Добавление нового виджета-компонента
     * @param array поля виджет-компонентв
     * @param bool импортируем ли?
     * @return integer id винджет-компонента
     */
    public function add_widgetclass($params, $import = 0, $base_widgetclass_id = '') {
        global $AUTH_USER_ID;
        $File_Mode = +$_REQUEST['fs'];
        $nc_core = $this->core;
        $db = $this->core->db;

        if (!$this->widgetclasses_loaded)
            $this->load();

        if (isset($this->widgetclasses_keys[$params['Keyword']]) && $import) {
            $params['Keyword'] = $params['Keyword'] . "_import";
            return $this->add_widgetclass($params, 1);
        }

        if (isset($this->widgetclasses_keys[$params['Keyword']]) && !$import) {
            return false;
        }

        $this->core->event->execute(nc_Event::BEFORE_WIDGET_COMPONENT_CREATED, 0);

        if($File_Mode) {
            $fs_params = array(
                'Template'         => $params['Template'],
                'Settings'         => $params['Settings'],
                'AddForm'          => nc_array_value($params, 'AddForm'),
                'EditForm'         => nc_array_value($params, 'EditForm'),
                'AfterSaveAction'  => nc_array_value($params, 'AfterSaveAction'),
                'BeforeSaveAction' => nc_array_value($params, 'BeforeSaveAction'),
            );
            $params['Template'] = $params['Settings'] = $params['AddForm'] = $params['EditForm'] = $params['AfterSaveAction'] = $params['BeforeSaveAction'] = '';
            $params['File_Mode'] = 1;
            $params['File_Path'] = $params['Keyword'] ? "/$params[Keyword]/" : '';
        }

        $params['IsStatic'] = (int) $params['IsStatic'];

        foreach ($params as $p => $value) {
            $query_key[] = "`" . $p . "`";
            $query_key_assist[] = $p;
            if ($p == 'Keyword' && $import) {
                $query_par[] = "'" . $db->escape(addslashes($params[$p])) . "'";
                $query_par_assist[] = $db->escape(addslashes($params[$p]));
            } else {
                $query_par[] = "'" . $db->escape(addslashes($params[$p])) . "'";
                $query_par_assist[] = $db->escape(addslashes($params[$p]));
            }
        }
        $query = "INSERT INTO `Widget_Class` (" . implode(',', $query_key) . ",`User_ID`,`LastUser_ID`,`Created`,`LastUpdated`) VALUES (" . implode(',', $query_par) . ",'" . $AUTH_USER_ID . "','" . $AUTH_USER_ID . "','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "')";

        $db->query($query);
        $insert_id = $db->insert_id;
        $this->assistant('add', array($db->insert_id, $query_key_assist, $query_par_assist));

        if ($base_widgetclass_id > 0) {
            $fields = $db->get_results("SELECT * FROM `Field` WHERE `Widget_Class_ID`='" . $base_widgetclass_id . "'");
            $query = "INSERT INTO `Field` (`Class_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Priority`, `DoSearch`, `DefaultState`, `Inheritance`, `System_Table_ID`, `Widget_Class_ID`, `TypeOfEdit_ID`, `Checked`) VALUES ";
            foreach ($fields as $f) {
                $query_arr[] = "(" . $f->Class_ID . ", '" . $f->Field_Name . "', '" . $f->Description . "', " . $f->TypeOfData_ID . ", '" . $f->Format . "', " . $f->NotNull . ", " . $f->Priority . ", " . $f->DoSearch . ", '" . $f->DefaultState . "', " . $f->Inheritance . ", " . $f->System_Table_ID . ", " . $insert_id . ", " . $f->TypeOfEdit_ID . ", " . $f->Checked . ")";
            }
            $query .= implode(",", $query_arr);
            $db->query($query);
        }


        if($File_Mode) {
            if (!$params['File_Path']) {
                $db->query("UPDATE `Widget_Class` SET `File_Path` = '/$insert_id/' WHERE `Widget_Class_ID` = $insert_id");
            }

            $widget_editor = new nc_tpl_widget_editor($nc_core->WIDGET_TEMPLATE_FOLDER, $nc_core->db);
            $widget_editor->load($insert_id);
            $widget_editor->save_new($fs_params);
        }

        $this->core->event->execute(nc_Event::AFTER_WIDGET_COMPONENT_CREATED, $db->insert_id);

        return $insert_id;
    }



    /**
     * Изменение существующего виджета-компонента
     * @param mixed номер виджет-компонента или его keyword
     * @param array поля виджет-компонента
     * @return bool
     */
    public function edit_widgetclass($id, $params) {
        global $AUTH_USER_ID;

        $nc_core = $this->core;
        $db = $nc_core->db;

        if (!$this->widgetclasses_loaded) {
            $this->load();
        }

        $File_Mode = $this->widgetclasses[$id]['File_Mode'];

        if ($File_Mode) {
            $old_path = $this->get_widgetclass($id, 'File_Path');

            $widget_editor = new nc_tpl_widget_editor($nc_core->WIDGET_TEMPLATE_FOLDER, $db);
            $widget_editor->load($id);
            $widget_editor->save_fields(null, true);

            if (isset($params['Keyword']) && $old_path != "/$params[Keyword]/") {
                $widget_editor->update_keyword($params['Keyword'] ?: $id);
                $this->widgetclasses[$id]['File_Path'] = $widget_editor->get_relative_path();
            }

            $fs_params = $params;
            $params['Template'] = $params['Settings'] = $params['AddForm'] = $params['EditForm'] = $params['AfterSaveAction'] = $params['BeforeSaveAction'] = '';
        }

        foreach ($params as $p => $value) {
            $query[] = "`" . $p . "`='" . $this->core->db->escape(addslashes($params[$p])) . "'";
        }

        $this->core->event->execute(nc_Event::BEFORE_WIDGET_COMPONENT_UPDATED, $this->get_id($id));
        $query = "UPDATE `Widget_Class` SET " . implode(',', $query) . $this->get_where($id) . "";
        $this->assistant('edit', array($id, $File_Mode ? $fs_params : $params));
        $db->query($query);
        $this->core->event->execute(nc_Event::AFTER_WIDGET_COMPONENT_UPDATED, $this->get_id($id));

        if (!$this->widgets_loaded) {
            $this->load('widgets');
        }

        foreach ($this->widgets as $w) {
            if ($w['Widget_Class_ID'] == $id) {
                $this->generate(intval($w['Widget_Class_ID']), '', 1, intval($w['Widget_ID']));
            }
        }

        return true;
    }



    /**
     * Удаление виджета-компонента
     * @param mixed номер виджет-компонента или его keyword
     * @return bool
     */
    public function drop_widgetclass($id) {
        if (!$this->widgetclasses_loaded)
            $this->load();
        $this->core->db->query("DELETE FROM Widget_Class" . $this->get_where($id) . "");
        $this->assistant('drop', $id);

        $widgets = $this->core->db->get_results("SELECT `Widget_ID` FROM `Widget` WHERE `Widget_Class_ID`='" . intval($id) . "'", ARRAY_A);
        $this->core->event->execute(nc_Event::BEFORE_WIDGET_COMPONENT_DELETED, $this->get_id($id));
        if ($widgets) {
            foreach ($widgets as $w) {
                $this->drop_widget(intval($w['Widget_ID']));
            }
        }

        $this->core->event->execute(nc_Event::AFTER_WIDGET_COMPONENT_DELETED, $this->get_id($id));
        return true;
    }



    /**
     * Получение информации о виджете-компоненте
     * @param mixed номер виджет-компонента или его keyword
     * @param string выводит одно поле по названию
     * @return array поля виджет-компонента
     */
    public function get_widgetclass($id, $item = '') {
        if (!$this->widgetclasses_loaded)
            $this->load();
        return $this->assistant('select', array($id, $item));
    }



    /**
     * Список всех виджет-компонентов
     * @return array все виджет-компоненты
     */
    public function list_widgetclass() {
        if (!$this->widgetclasses_loaded) {
            $this->load();
        }

        return $this->assistant('list');
    }


    public function get_widgetclass_list() {
        $system_widgets = array();

        foreach ($this->system_widgets as $name => $opt) {
            $system_widgets[] = array(
                'value'       => $name,
                'description' => $this->$name->get_info('name'),
                'optgroup'    => 'Netcat',
            );
        }

        $db_widgets = nc_db()->get_results("SELECT `Widget_Class_ID` AS value, CONCAT(`Widget_Class_ID`, '. ', `Name`) AS description, `Category` AS optgroup FROM `Widget_Class` WHERE `InDevelop`='0' ORDER BY `Category`, `Widget_Class_ID`", ARRAY_A);

        return array_merge($system_widgets, $db_widgets);
    }


    /**
     * Добавление нового виджета
     * @param mixed номер виджет-компонента или его keyword
     * @param string выводит одно поле по названию
     * @return integer
     */
    public function add_widget($params, $fields = 0) {
        global $AUTH_USER_ID;

        $db = $this->core->db;

        if (!$this->widgets_loaded) {
            $this->load('widgets');
        }

        $query = "INSERT INTO `Widget` (`Widget_Class_ID`,`Name`,`Keyword`,`User_ID`,`LastUser_ID`,`Created`,`LastUpdated`) VALUES ('" . $params['Widget_Class_ID'] . "','" . $params['Name'] . "','" . $params['Keyword'] . "','" . $AUTH_USER_ID . "','" . $AUTH_USER_ID . "','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "')";

        $this->core->event->execute(nc_Event::BEFORE_WIDGET_CREATED, intval($params['Widget_Class_ID']), 0);

        $db->query($query);
        $widget_id = $db->insert_id;

        foreach ($params as $p => $value) {
            $query_key_assist[] = $p;
            $query_par_assist[] = $db->escape(addslashes($params[$p]));
        }
        $this->assistant('add', array($widget_id, $query_key_assist, $query_par_assist), 1);

        if ($fields) {
            foreach ($fields as $id => $value) {
                $db->query("INSERT INTO `Widget_Field` (`Widget_ID`, `Field_ID`, `Value`) VALUES ('" . $widget_id . "', '" . $id . "', '" . $value . "')");
                $generate_where[] = "`Field_ID`='" . $id . "'";
                $generate_values[$id] = $value;
            }

            $field_names = $db->get_results("SELECT `Field_Name`, `Field_ID` FROM `Field` WHERE (" . implode(' OR ', $generate_where) . ")");
            foreach ($field_names as $fn) {
                $generate_fields[$fn->Field_Name] = $generate_values[$fn->Field_ID];
            }
        }

        $this->generate(intval($params['Widget_Class_ID']), $generate_fields, 1, $widget_id);

        $this->core->event->execute(nc_Event::AFTER_WIDGET_CREATED, intval($params['Widget_Class_ID']), $widget_id);
        return $widget_id;
    }



    /**
     * Редактирование виджета
     * @param mixed номер виджета или его keyword
     * @param array поля виджета
     * @param bool есть ли поля для редактирования?
     * @return bool
     */
    public function edit_widget($widget_id, $params, $fields = 0) {
        global $AUTH_USER_ID;
        $db = $this->core->db;
        if (!$this->widgets_loaded)
            $this->load('widgets');
        $query = "UPDATE `Widget` SET `Widget_Class_ID`='" . $params['Widget_Class_ID'] . "', `Name`='" . $params['Name'] . "', `Keyword`='" . $params['Keyword'] . "'" . $this->get_where($widget_id, 1) . "";

        $this->core->event->execute(nc_Event::BEFORE_WIDGET_UPDATED, intval($params['Widget_Class_ID']), $this->get_id($widget_id, 1));

        $db->query($query);
        $this->assistant('edit', array($widget_id, $params));

        $generate_where = array();
        $generate_values = array();

        if ($fields) {
            $widget_field_table = nc_db_table::make('Widget_Field');
            foreach ($fields as $id => $value) {
                // Текущие данные поля
                $field_data = $widget_field_table->select('ID', 'Value')->where('Widget_ID', $widget_id)->where('Field_ID', $id)->get_result();
                $field_data = !empty($field_data) ? $field_data[0] : null;
                $data = array('Value'=>$value);
                if (!empty($field_data)) {
                    // Если значение поля изменилось, то обновим
                    if ($field_data['Value'] != $value) {
                        $widget_field_table->where('ID', $field_data['ID'])->update($data);
                    }
                } else {
                    $data['Widget_ID'] = $widget_id;
                    $data['Field_ID'] = $id;
                    $widget_field_table->insert($data);
                }

                $generate_where[] = "`Field_ID`='" . $id . "'";
                $generate_values[$id] = $value;
            }
            // Удалим из базы значения тех полей, данные для которых не были получены (например, выключенные checkboxes)
            $affected_fields_ids = array_keys($fields);
            $affected_fields_ids = implode(',', $affected_fields_ids);
            nc_db()->query("DELETE FROM `Widget_Field` WHERE `Widget_ID`='{$widget_id}' AND `Field_ID` NOT IN ({$affected_fields_ids})");
        }

        $generate_fields = array();

        if ($generate_where) {
            $field_names = $db->get_results("SELECT `Field_Name`, `Field_ID` FROM `Field` WHERE (" . implode(' OR ', $generate_where) . ")");
            foreach ($field_names as $fn) {
                $generate_fields[$fn->Field_Name] = $generate_values[$fn->Field_ID];
            }
        }

        $this->generate(intval($params['Widget_Class_ID']), $generate_fields, 1, $widget_id);
        $this->core->event->execute(nc_Event::AFTER_WIDGET_UPDATED, intval($params['Widget_Class_ID']), $this->get_id($widget_id, 1));

        return true;
    }



    /**
     * Удаление виджета
     * @param mixed номер виджета или его keyword
     * @return bool
     */
    public function drop_widget($id) {
        if (!$this->widgets_loaded)
            $this->load('widgets');
        $new_id = is_int($id) ? "`Widget_ID`='" . $id . "'" : "`Keyword`='" . $this->core->db->escape($id) . "'";
        $wc_event_id = $this->core->db->get_var("SELECT `Widget_Class_ID` FROM `Widget` WHERE " . $new_id);
        $where = "WHERE `Widget_ID`='" . $this->core->db->get_var("SELECT `Widget_ID` FROM `Widget` WHERE " . $new_id);
        $this->core->event->execute(nc_Event::BEFORE_WIDGET_DELETED, $wc_event_id, $this->get_id($id, 1));
        $this->core->db->query("DELETE FROM `Widget` " . $where . "'");
        $this->core->db->query("DELETE FROM `Widget_Field` " . $where . "'");
        $this->assistant('drop', $id, 1);
        $this->core->event->execute(nc_Event::AFTER_WIDGET_DELETED, $wc_event_id, $this->get_id($id, 1));
        return true;
    }



    /**
     * Получение информации о виджете
     * @param integer номер виджета
     * @return array поля виджета
     */
    public function get_widget($id) {
        $db = $this->core->db;
        if (!$this->widgets_loaded)
            $this->load('widgets');
        if (!isset($item)) {
            $item = null;
        }
        return $this->assistant('select', array($id, $item), 1);
    }

    /**
     * @param int $widget_class_id
     * @return stdClass[]
     */
    protected function get_default_field_values($widget_class_id) {
        if (!isset($this->default_field_values[$widget_class_id])) {
            $query = "SELECT `Field_Name`, `DefaultState`
                        FROM `Field`
                       WHERE `Widget_Class_ID` = " . (int)$widget_class_id;
            $this->default_field_values[$widget_class_id] = (array)$this->core->db->get_results($query);
        }
        return $this->default_field_values[$widget_class_id];
    }


    /**
     * Генерация виджета
     * @param mixed $id номер виджета или его keyword
     * @param array $fields поля виджета
     * @param bool $static обновлять ли экземпляр виджета
     * @param integer $widget_id id обновляемого виджета
     * @return string html-код виджета
     */
    public function generate($id, $fields = null, $static = false, $widget_id = 0) {
        $nc_core = $this->core;
        $db = $nc_core->db;
        $params = $this->get_widgetclass($id);

        $default_values = $this->get_default_field_values($params['Widget_Class_ID']);

        $result = $new_fields = '';

        if ($params['IsStatic']) {
            foreach ($default_values as $r) {
                $new_fields .= "\$f_" . $r->Field_Name . "= '" . $r->DefaultState . "';";
            }
            if (is_array($fields)) {
                foreach ($fields as $f => $val) {
                    $new_fields .= "\$f_" . $f . "= '" . $val . "';";
                }
            }
            if ($new_fields) {
                eval(nc_check_eval($new_fields));
            }
        }
        else {
            foreach ($default_values as $r) {
                ${'f_' . $r->Field_Name} = $r->DefaultState;
            }
            if (!empty($fields)) {
                extract($fields, EXTR_PREFIX_ALL, "f");
            }
        }

        if ($params['File_Mode']) {
            $widget_view = new nc_tpl_widget_view($nc_core->WIDGET_TEMPLATE_FOLDER, $db);
            $widget_view->load($params['Widget_Class_ID'], $params['File_Path']);
            $widget_class_id = $id;
            include $widget_view->get_field_path('Settings');
            ob_start();
            include $widget_view->get_field_path('Template');
            $result .= ob_get_clean();
        }
        else {
            eval(nc_check_eval($params['Settings']));
            eval(nc_check_eval("\$result .= \"" . $params['Template'] . "\";"));
        }

        if ($static) {
            $db->query("UPDATE `Widget` SET `LastUpdated` = NOW(), `Result`='" . $this->core->db->escape($result) . "'" . $this->get_where($widget_id, 1) . "");
        }

        return $result;
    }



    /**
     * Получить код виджета
     * @param mixed $id номер виджета или его keyword
     * @param bool $for_macrofunc показ для макрофункции?
     * @return string html-код виджета
     */
    public function show($id, $for_macrofunc = false) {

        $db = $this->core->db;
        $where = is_int($id) ? "`Widget_ID` = " . $id : "`Keyword` = '" . $db->escape($id) . "'";

        $SQL = "SELECT `w`.`Result`,
                       `w`.`Widget_Class_ID`,
                       `w`.`Widget_ID`,
                       `w`.`LastUpdated`,
                       `wc`.`Update`,
                       `wc`.`Keyword`,
                       `wc`.`WidgetDisallow`,
                       `wc`.`IsStatic`
                     FROM `Widget` AS w,
                          `Widget_Class` AS wc
                    WHERE `wc`.`Widget_Class_ID` = `w`.`Widget_Class_ID`
                      AND `w`." . $where . "
                    LIMIT 1";
        $result = $db->get_row($SQL, ARRAY_A);

        if (!$result) {
            return "";
        }

        // запрет на встраивание в текст виджета
        if ($for_macrofunc && $result['WidgetDisallow']) {
            return "";
        }

        $ret_val = $result['Result'];

        // виджет надо обновить
        if (!$result['IsStatic'] || ($result['Update'] && (time() >= strtotime($result['LastUpdated']) + (int)$result['Update'] * 60))) {
            $fields = (array) $db->get_results("SELECT `f`.`Field_Name`, `wf`.`Value`
                FROM `Widget_Field` AS wf, `Field` AS f
                WHERE `wf`.`Field_ID` = `f`.`Field_ID` AND `wf`.`Widget_ID`= " . (int)$result['Widget_ID'] . "", ARRAY_A);

            $new_fields = array();
            foreach ($fields as $f) {
                $new_fields[$f['Field_Name']] = $f['Value'];
            }

            // Динамический виджет (генерируется при каждом вызове)
            //TODO: В будущем можно сделать кэширование в поле Widget.Result (добавить поле LifeTime)
            if ($result['Keyword'] == 'nc_aggregator' || $result['Keyword'] == 'netcat_aggregator') {
                $ret_val = $this->generate_aggregator($result['Widget_ID'], array());
            } else {
                $ret_val = $this->generate(intval($result['Widget_Class_ID']), $new_fields, !$result['IsStatic'], intval($result['Widget_ID']));
            }
        }

        return $ret_val;
    }



    /**
     * Метод, соответсвующий NC_WIDGET_SHOW
     * @param mixed номер виджета или его keyword
     * @return string html-код виджета
     */
    public function show_macrofunc($id) {
        return $this->show($id, 1);
    }


    /**************************************************************************
        Widgets block
    **************************************************************************/

    public function get_block_widgets($block_key, $params = array()) {
        $moderation_is_allowed = $this->moderation_is_allowed();

        if (!$moderation_is_allowed) {
            $this->block_widget_table->where('Checked', 1);
        }

        $block_widgets = $this->block_widget_table->for_key($block_key)->order_by_priority()->get_result();
        $widget_ids    = array();
        $result        = array();

        foreach ($block_widgets as $row) {
            $widget_ids[$row['Widget_ID']] = $row['Widget_ID'];
        }

        if ($moderation_is_allowed) {
            $widget_names = nc_db_table::make('Widget')->where_in_id($widget_ids)->index_by_id()->get_list('Name');
            $action       = $this->core->SUB_FOLDER . $this->core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.widget.widget_block';
            $onclick      = 'parent.nc_form(this.href,false,false,{width:500,height:350});return false';
            $onclick_del  = 'parent.nc_form(this.href,false,false,{width:400,height:200});return false';
            $back_link    = '&back_link=' . urlencode($_SERVER['REQUEST_URI']);
            $params_query = '';

            foreach ((array)$params as $key => $value) {
                $params_query .= "&{$key}={$value}";
            }
        }

        foreach ($block_widgets as $row) {
            $widget_id = $row['Widget_ID'];
            $toolbar   = '';

            if ($moderation_is_allowed) {
                $widget_name    = $widget_names[$widget_id];
                $id             = $row['Block_Widget_ID'];
                $checked        = (bool) $row['Checked'];
                $row_action     = $action . '&block_widget_id=' . $id . '&action=';
                $action_checked = $row_action . 'checked&checked=' . ((int)!$checked) . $back_link;
                $action_edit    = $row_action . 'edit' . $params_query;
                $action_delete  = $row_action . 'delete' . $back_link;
                $checked_label  = $checked ? NETCAT_MODERATION_OBJ_ON : NETCAT_MODERATION_OBJ_OFF;

                $toolbar = $this->core->ui->toolbar()->left();
                $toolbar->add_text( mb_substr($widget_name, 0, 20) . (mb_strlen($widget_name) > 20 ? '...' : '') );
                $toolbar->add_btn($action_checked, $checked_label)->on($checked)->off(!$checked);
                $toolbar->add_btn($action_edit)->click($onclick)->icon('edit');
                $toolbar->add_btn($action_delete)->click($onclick_del)->icon('remove');
                $toolbar .= '<div class="nc--clearfix"></div>';
            }

            $result[] = $toolbar . $this->generate_aggregator((int)$widget_id, $row['Widget_Settings']);
        }

        if ($moderation_is_allowed) {
            echo $this->widgets_block_actions($block_key, $params);
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function widgets_block_actions($block_key, $params = array()) {
        if ($this->moderation_is_allowed()) {
            $current_sub = $this->core->subdivision->get_current();
            $action      = $this->core->SUB_FOLDER . $this->core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.widget.widget_block&catalogue=' . $current_sub['Catalogue_ID'];
            $action_add  = $action . '&action=add&block=' . $block_key;

            foreach ((array)$params as $key => $value) {
                $action_add .= "&{$key}={$value}";
            }

            $toolbar = $this->core->ui->toolbar()->right();
            $toolbar->add_text()->icon('dev-com-widgets nc--disabled')->title($widget_key);
            $toolbar->add_btn($action_add, WIDGET_ADD_ADD)->click('parent.nc_form(this.href,false,false,{width:500,height:350});return false');

            return $toolbar;
        }

        return;
    }

    //-------------------------------------------------------------------------

    protected function moderation_is_allowed() {
        global $perm;
        static $allowed;

        if (is_null($allowed)) {
            $admin_mode   = $this->core->admin_mode;
            $inside_admin = $this->core->inside_admin;
            $allowed      = false;

            if ($admin_mode && !$inside_admin) {
                $current_sub = $this->core->subdivision->get_current();
                $allowed = $perm->isSubdivision($current_sub['Subdivision_ID'], MASK_MODERATE);
            }
        }

        return $allowed;
    }

    //-------------------------------------------------------------------------

    public function render_widgets_block($block_key, $params = array()) {
        $widgets = $this->get_block_widgets($block_key, $params);

        return $result . implode("\n", $widgets);
    }

    //-------------------------------------------------------------------------

    protected function generate_aggregator($widget_id, $settings) {
        if (is_string($settings) && $settings[0] == 'a') {
            $settings = unserialize($settings);
        } else {
            $settings = (array) $settings;
        }

        $widget = nc_db_table::make('Widget')->where_id($widget_id)->get_row();

        $fields          = array();
        $nc_core         = nc_core();
        $db              = nc_db();
        $widget_class_id = $widget['Widget_Class_ID'];

        $field_values = nc_db_table::make('Widget_Field')->where('Widget_ID', $widget_id)->get_list('Field_ID', 'Value');
        $result       = $db->get_results("SELECT `Field_ID`, `Field_Name`, `DefaultState` FROM `Field` WHERE `Widget_Class_ID`='" . $widget_class_id . "'", ARRAY_A);
        foreach ($result as $row) {
            $field_id = $row['Field_ID'];
            $fields[$row['Field_Name']] = isset($field_values[$field_id]) ? $field_values[$field_id] : $row['DefaultState'];
        }

        extract($fields, EXTR_PREFIX_ALL, 'f');

        $widget_view = new nc_tpl_widget_view($this->core->WIDGET_TEMPLATE_FOLDER, nc_db());
        $widget_view->load($widget['Widget_Class_ID'], $widget['File_Path']);


        include $widget_view->get_field_path('Settings');
        ob_start();
        include $widget_view->get_field_path('Template');
        return ob_get_clean();
    }

    //-------------------------------------------------------------------------
}
