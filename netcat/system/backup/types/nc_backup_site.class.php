<?php

class nc_backup_site extends nc_backup_base {

    //--------------------------------------------------------------------------

    protected $name = TOOLS_SYSTABLE_SITES;

    /** @var  nc_db_table */
    protected $site_table;
    /** @var  nc_db_table */
    protected $classificator_table;
    /** @var  nc_db_table */
    protected $multifield_table;
    /** @var  nc_db_table */
    protected $subdivision_table;
    /** @var  nc_db_table */
    protected $subclass_table;
    /** @var  nc_db_table */
    protected $template_table;
    /** @var  nc_db_table */
    protected $template_partial_table;
    /** @var  nc_db_table */
    protected $settings_table;
    /** @var  nc_db_table */
    protected $class_table;
    /** @var  nc_db_table */
    protected $field_table;
    /** @var  nc_db_table */
    protected $system_table;

    protected $template_paths_dict       = array();
    protected $template_paths            = array();
    protected $custom_settings_relations = array('template' => array(), 'component' => array());
    protected $custom_settings_relation_fields = array(
        'sub'   => 'Subdivision_ID',
        'cc'    => 'Sub_Class_ID',
        'class' => 'Class_ID',
        // user => UserID
    );
    protected $new_components            = array();
    protected $file_fields               = array();
    protected $relation_fields           = array();
    protected $relation_message_fields   = array();
    protected $imported_component_keywords = array();
    protected $imported_full_page_blocks = array();
    protected $imported_parent_sub_class_ids = array();

    protected $exported_classificators = array();
    protected $preexisting_classificators = array();

    protected $not_imported_components = array();


    protected $renamed_files = array();

    //--------------------------------------------------------------------------

    protected function init() {
        $this->site_table = nc_db_table::make('Catalogue');
        $this->classificator_table = nc_db_table::make('Classificator');
        $this->subdivision_table = nc_db_table::make('Subdivision');
        $this->multifield_table = nc_db_table::make('Multifield');
        $this->subclass_table = nc_db_table::make('Sub_Class');
        $this->template_table = nc_db_table::make('Template');
        $this->template_partial_table = nc_db_table::make('Template_Partial');
        $this->settings_table = nc_db_table::make('Settings');
        $this->class_table = nc_db_table::make('Class');
        $this->field_table = nc_db_table::make('Field');
        $this->system_table = nc_db_table::make('System_Table');
    }

    //-------------------------------------------------------------------------

    protected function reset() {
        parent::reset();
        $this->template_paths_dict     = array();
        $this->template_paths          = array();
        $this->new_components          = array();
        $this->file_fields             = array();
        $this->relation_fields         = array();
        $this->relation_message_fields = array();
        $this->imported_component_keywords = array();
        $this->renamed_files = array();
    }

    //-------------------------------------------------------------------------

    protected function row_attributes($ids) {
        $titles = $this->site_table->select('Catalogue_ID, Catalogue_Name, Domain')->where_in_id((array)$ids)->index_by_id()->get_result();

        $result = array();
        foreach ($titles as $id => $row) {
            $result[$id] = array(
                'title'       => $row['Catalogue_Name'] . ' (' . $row['Domain'] . ')',
                'sprite'      => 'nc--site',
                'netcat_link' => $this->nc_core->ADMIN_PATH . "subdivision/full.php?CatalogueID={$id}"
            );
        }

        return $result;
    }

    //--------------------------------------------------------------------------
    // EXPORT
    //--------------------------------------------------------------------------

    protected function export_form() {
        $options    = array(''=>'');

        $result = $this->site_table
            ->select('Catalogue_ID, Catalogue_Name, Domain')
            ->order_by('Priority')
            ->order_by('Catalogue_ID')
            ->order_by('Catalogue_Name')
            ->index_by_id()
            ->as_object()
            ->get_result();


        foreach ($result as $site_id => $row) {
            $options[$site_id] = $site_id . '. ' . $row->Catalogue_Name . ' (' . $row->Domain . ')';
        }

        return $this->nc_core->ui->form->add_row(SECTION_CONTROL_CLASS)->select('id', $options);
    }

    //-------------------------------------------------------------------------

    protected function export_validation() {
        if (!$this->id) {
            $this->set_validation_error('Site not selected');
            return false;
        }
        return true;
    }

    //-------------------------------------------------------------------------

    protected function export_process() {
        global $SUB_FOLDER, $HTTP_FILES_PATH, $DOCUMENT_ROOT;
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        $id   = $this->id;
        $site = $this->site_table->where_id($id)->get_row();

        if (!$site) {
            return false;
        }

        // Если задан параметр экспорта named_entities_path, «системные» сущности:
        //  — макеты, у которых указано ключевое слово,
        //  — компоненты и их шаблоны, у которых указано ключевое слово,
        //  — списки
        // будут экспортированы отдельно в указанную папку (в подпапки components/KEYWORD/,
        // templates/KEYWORD, lists/TABLE_NAME), а
        $export_settings = $this->dumper->get_export_settings();
        $named_entities_path = nc_array_value($export_settings, 'named_entities_path');
        $keyword_map = array();
        $filetable_data = array();

        $this->exported_classificators = array();

        $this->dumper->register_dict_field('Catalogue_ID', 'Class_ID', 'Sub_Class_ID', 'Template_ID', 'Class_Template_ID', 'Subdivision_ID', 'Mixin_Preset_ID');
        $this->dumper->register_dict_field(array(
            'Index_Mixin_Preset_ID' => 'Mixin_Preset_ID',
            'IndexItem_Mixin_Preset_ID' => 'Mixin_Preset_ID',
        ));

        // Catalogue
        $this->dumper->export_data('Catalogue', 'Catalogue_ID', array($id => $site));

        $site_file_fields_data = $nc_core->get_component('Catalogue')->get_fields(NC_FIELDTYPE_FILE);
        foreach ($site_file_fields_data as $file_field_data) {
            $field_name = $file_field_data['name'];
            if (!$site[$field_name]) {
                continue;
            }
            $this->export_file('Catalogue', $id, $field_name, $filetable_data);
        }

        // Settings
        $settings_data = $this->settings_table->where('Catalogue_ID', $id)->index_by_id()->get_result();
        $this->dumper->export_data('Settings', 'Settings_ID', $settings_data);

        // System table fields
        $system_table_fields = $this->field_table->where('System_Table_ID', '!=', 0)->index_by_id()->get_result();
        $this->dumper->export_data('Field', 'Field_ID', $system_table_fields);

        $system_table_fields_by_table = array();
        foreach ($system_table_fields as $field) {
            $system_table_name = $nc_core->get_system_table_name_by_id($field['System_Table_ID']);
            $system_table_fields_by_table[$system_table_name][] = $field['Field_Name'];
        }
        foreach ($system_table_fields_by_table as $system_table => $system_table_fields) {
            $this->dumper->export_table_fields($system_table, $system_table_fields);
        }


        ##### SUBDIVISIONS #####

        $subdivisions_data = $this->subdivision_table->where('Catalogue_ID', $id)->where('Parent_Sub_ID', 0)->index_by_id()->get_result();
        $parent_ids = array_keys($subdivisions_data);

        while ($parent_ids) {
            $result = $this->subdivision_table->where_in('Parent_Sub_ID', $parent_ids)->index_by_id()->get_result();
            $parent_ids = array_keys($result);
            $subdivisions_data += $result;
        }

        $this->dumper->export_data('Subdivision', 'Subdivision_ID', $subdivisions_data);

        // Site and subdivision template settings
        $template_settings = array();
        if ($site['TemplateSettings']) {
            $template_settings[0] = $site['TemplateSettings']; // zero index for the catalogue (site)
        }

        foreach ($subdivisions_data as $sub_id => $sub) {
            if ($sub['TemplateSettings']) {
                $template_settings[$sub_id] = $sub['TemplateSettings'];
            }

            foreach ($this->get_subdivision_file_field_names() as $field_name) {
                if (!$sub[$field_name]) {
                    continue;
                }
                $this->export_file('Subdivision', $sub_id, $field_name, $filetable_data);
            }
        }


        ##### INFOBLOCKS #####

        // Sub_Class
        $sub_classes = $this->subclass_table->where('Catalogue_ID', $id)->index_by_id()->get_result();
        $this->dumper->export_data('Sub_Class', 'Sub_Class_ID', $sub_classes);
        $this->export_infoblock_custom_settings_files($sub_classes);
        $this->export_infoblock_mixin_settings_files($sub_classes);

        $sub_class_ids = array_keys($sub_classes);

        // Filter
        $filters = nc_db_table::make('Filter', 'Filter_ID')->where_in('Filter_Sub_Class_ID', $sub_class_ids)->get_result();
        $this->dumper->export_data('Filter', 'Filter_ID', $filters);
        unset($filters);

        // AreaCondition tables
        $area_condition_tables = array(
            'Sub_Class_AreaCondition_Subdivision',
            'Sub_Class_AreaCondition_Subdivision_Exception',
            'Sub_Class_AreaCondition_Class',
            'Sub_Class_AreaCondition_Class_Exception',
            'Sub_Class_AreaCondition_Message',
        );

        foreach ($area_condition_tables as $t) {
            $conditions = nc_db_table::make($t, 'Condition_ID')
                                ->where_in('Sub_Class_ID', $sub_class_ids)
                                ->get_result();
            $this->dumper->export_data($t, 'Condition_ID', $conditions);
        }


        ##### OBJECTS #####

        foreach ($sub_classes as $sub_class_id => $sub_class) {
            $this->export_objects($sub_class, $filetable_data);
        }


        ##### TEMPLATES #####

        $tpl_settings_file_fields = array();
        $template_ids = $this->dumper->get_dict('Template_ID');
        unset($template_ids[0]);
        if ($template_ids) {
            do {
                $template_ids = array_unique($this->template_table->where_in_id($template_ids)->get_list('Parent_Template_ID'));
                $template_ids[0] = 0;
            } while(call_user_func_array('max', $template_ids));
            unset($template_ids[0]);
            $template_ids = array_keys($template_ids);

            // Template
            $templates = $this->template_table->where_in_id($template_ids)->index_by_id()->get_result();
            $data = $templates;
            while ($template_ids) {
                $result       = $this->template_table->where_in('Parent_Template_ID', $template_ids)->index_by_id()->get_result();
                $template_ids = array_keys($result);
                $data        += $result;
            }

            $all_data = $data;
            if ($named_entities_path) {
                foreach ($data as $template_id => $template_data) {
                    if ($template_data['Parent_Template_ID'] == 0 && $template_data['Keyword']) {
                        $template_export_settings = array_merge($export_settings, array(
                            'path' => $named_entities_path . '/templates/' . $template_data['Keyword'],
                            'file_name_suffix' => $template_data['Keyword'],
                        ));
                        $this->export_named_entity('template', $template_id, $template_export_settings);
                    }

                    if (preg_match('!^/\d*[a-zA-Z_]!', $template_data['File_Path'])) {
                        // это макет с ключевым словом или «подмакет» такого макета,
                        // он будет записан отдельно (предыдущий if)
                        unset(
                            $data[$template_id],
                            $templates[$template_id]
                        );
                        $keyword_map['Template'][trim($template_data['File_Path'], '/')] = $template_id;
                    }
                }
            }

            $this->dumper->export_data('Template', 'Template_ID', $data);

            // Custom settings files
            foreach ($all_data as $tpl_id => $tpl) {
                if ($tpl['CustomSettings']) {
                    $settings_array = eval("return $tpl[CustomSettings]");
                    if (is_array($settings_array)) {
                        foreach ($settings_array as $sfield_name => $settings_field) {
                            if ($settings_field['type'] == 'file') {
                                $tpl_settings_file_fields[$sfield_name] = $sfield_name;
                            }
                        }
                    }
                }
            }
            unset($all_data);

            // Export files
            foreach ($templates as $tpl) {
                if ($tpl['File_Mode']) {
                    $this->dumper->export_files(nc_core('HTTP_TEMPLATE_PATH') . 'template', $tpl['File_Path']);
                }
            }

            // Template partials
            $template_ids = array_keys($data);
            if ($template_ids) {
                $data = $this->template_partial_table->where_in('Template_ID', $template_ids)->get_result();
                if ($data) {
                    $this->dumper->export_data('Template_Partial', 'Template_Partial_ID', $data);
                }
            }
        }

        // Export template settings files
        if ($tpl_settings_file_fields && $template_settings) {
            foreach ($template_settings as $settings_string) {
                $TemplateSettings = eval("return $settings_string");
                if ($TemplateSettings) {
                    foreach ($tpl_settings_file_fields as $sfield_name) {
                        if (!empty($TemplateSettings[$sfield_name])) {
                            $file = explode(':', $TemplateSettings[$sfield_name]);
                            $file = $HTTP_FILES_PATH . $file[3];
                            if (file_exists($DOCUMENT_ROOT . $file) && !is_dir($DOCUMENT_ROOT . $file)) {
                                $this->dumper->export_files($file);
                            }
                        }
                    }
                }
            }
        }
        unset($tpl_settings_file_fields, $template_settings);


        ##### COMPONENTS #####

        $component_ids = $this->dumper->get_dict('Class_ID');
        $component_template_ids = $this->dumper->get_dict('Class_Template_ID');

        $components = (array)$db->get_results(
            "SELECT *
               FROM `Class`
              WHERE `Class_ID` IN (" . implode(', ', $component_ids ?: array(0)) . ")
                 OR (
                        `Class_ID` IN (" . implode(', ', $component_template_ids ?: array(0)) . ")
                        AND `ClassTemplate` = 0
                    )
                 OR (
                        `Class_ID` IN (
                            SELECT `ClassTemplate`
                              FROM `Class`
                             WHERE `Class_ID` IN (" . implode(', ', $component_template_ids ?: array(0)) . ")
                               AND `IsMultipurpose` > 0
                               AND `ClassTemplate` > 0
                        )
                    )",
            ARRAY_A,
            'Class_ID'
        );

        // Добавить в архив шаблоны компонентов, которые используются на сайте,
        // а также специальные шаблоны (шаблоны для административной части,
        // корзины, rss и т.д.)
        $component_templates = (array)$db->get_results(
            "SELECT *
               FROM `Class`
              WHERE `ClassTemplate` > 0
                AND (
                    `Class_ID` IN (" . implode(',', $component_template_ids) . ")
                    OR (`ClassTemplate` IN (" . implode(',', $component_ids) . ")
                        AND `Type` NOT IN ('useful', 'mobile', 'responsive', 'object_partial')
                       )
                    )",
            ARRAY_A,
            'Class_ID');

        $all_component_ids = $component_ids;
        $components_and_templates = $components + $component_templates;
        if ($named_entities_path) {
            // Экспорт компонентов в отдельную папку
            foreach ($components_and_templates as $component_id => $component_data) {
                $components_and_templates[$component_id]['Main_ClassTemplate_ID'] = 0;
                $component_keyword = $component_data['Keyword'];
                if ($component_keyword) {
                    unset(
                        $components_and_templates[$component_id],
                        $component_ids[$component_id],
                        $components[$component_id],
                        $component_templates[$component_id]
                    );
                    if ($component_data['ClassTemplate'] == 0) {
                        $component_export_settings = array_merge($export_settings, array(
                            // на случай, если там если у компонента разные шаблоны на разных сайтах,
                            // чтобы не затереть шаблоны с другого сайта:
                            'remove_existing' => false,
                            'path' => $named_entities_path . '/components/' . $component_keyword,
                            'file_name_suffix' => $component_keyword,
                        ));
                        $this->export_named_entity('component', $component_id, $component_export_settings);

                        $keyword_map['Field'][$component_keyword] = $this->field_table
                            ->where('Class_ID', $component_id)
                            ->get_list('Field_Name', 'Field_ID');
                    }
                    $keyword_map['Class'][trim($component_data['File_Path'], '/')] = $component_id;
                }
            }
        }

        if (count($components_and_templates)) {
            $this->dumper->export_data('Class', 'Class_ID', $components_and_templates);
        }

        // For tables marked as 'auxiliary', save field names in dump_info['auxiliary_component_fields_$ID']
        // as a comma-separated string
        $db->query("SET group_concat_max_len=16384");
        $auxiliary_component_fields = $db->get_col("
            SELECT IFNULL(GROUP_CONCAT(`f`.`Field_Name` ORDER BY `f`.`Field_Name`), ''),
                   `c`.`Class_ID`
              FROM `Class` AS `c`  LEFT JOIN `Field` AS `f` USING (`Class_ID`)
             WHERE `c`.`Class_ID` IN (" . implode(', ', $all_component_ids) . ")
               AND `c`.`IsAuxiliary` = 1
             GROUP BY `c`.`Class_ID`",
            0, 1);

        foreach ($auxiliary_component_fields as $aux_component_id => $aux_component_fields) {
            $this->dumper->set_dump_info("auxiliary_component_fields_" . $aux_component_id, $aux_component_fields);
        }

        // Field
        $fields_to_export = $this->field_table->where_in('Class_ID', $component_ids)->index_by_id()->get_result();
        $this->dumper->export_data('Field', 'Field_ID', $fields_to_export);
        unset($fields_to_export);

        $all_fields = $this->field_table->where_in('Class_ID', $all_component_ids)->get_result();

        // Classifiers
        foreach ($all_fields as $field) {
            if ($field['TypeOfData_ID'] == NC_FIELDTYPE_SELECT || $field['TypeOfData_ID'] == NC_FIELDTYPE_MULTISELECT) {
                list($classificator_table) = explode(':', $field['Format']);

                if ($classificator_table && !isset($this->exported_classificators[$classificator_table])) {
                    // Do that only once for each classifier
                    $this->exported_classificators[$classificator_table] = true;
                    $classificator = $this->classificator_table->where('Table_Name', $classificator_table)->get_row();

                    if (!$classificator) {
                        trigger_error(__CLASS__ . ": classifier '$classificator_table' does not exist (field: '$field[Field_Name]')", E_USER_WARNING);
                        continue;
                    }

                    if ($named_entities_path) {
                        $classificator_export_settings = array_merge($export_settings, array(
                            'path' => $named_entities_path . '/lists/' . $classificator_table,
                            'file_name_suffix' => $classificator_table,
                        ));
                        $this->export_named_entity('classificator', $classificator['Classificator_ID'], $classificator_export_settings);
                    }
                    else {
                        $data = array($classificator['Classificator_ID'] => $classificator);
                        $this->dumper->export_data('Classificator', 'Classificator_ID', $data);

                        // Export data: Classificator_{Table_Name}
                        $c_table = 'Classificator_' . $classificator['Table_Name'];
                        $c_pk    = $classificator['Table_Name'] . '_ID';

                        $classificator_data_table = nc_db_table::make($c_table, $c_pk);
                        $data = $classificator_data_table->get_result();

                        $this->dumper->export_data($c_table, $c_pk, $data);

                        // Export table: Classificator_{Table_Name}
                        $this->dumper->export_table($c_table);
                    }
                }
            }
        }

        $default_mixin_preset_ids = array();

        foreach ($components as $component_id => $component) {
            if ($component['Index_Mixin_Preset_ID']) {
                $default_mixin_preset_ids[] = $component['Index_Mixin_Preset_ID'];
            }

            if ($component['IndexItem_Mixin_Preset_ID']) {
                $default_mixin_preset_ids[] = $component['Index_Mixin_Preset_ID'];
            }

            // Do not export the /sys/ (`User`) component templates
            if ($component['System_Table_ID']) { continue; }

            // Message*
            $this->dumper->export_table('Message' . $component_id);

            // Export component files
            if ($component['File_Mode']) {
                $this->dumper->export_files(nc_core('HTTP_TEMPLATE_PATH') . 'class', $component['File_Path'], false);
            }
        }

        // Export component template files (including /sys/*)
        foreach ($component_templates as $component_template_id => $component_template) {
            if ($component_template['Index_Mixin_Preset_ID']) {
                $default_mixin_preset_ids[] = $component_template['Index_Mixin_Preset_ID'];
            }

            if ($component_template['IndexItem_Mixin_Preset_ID']) {
                $default_mixin_preset_ids[] = $component_template['Index_Mixin_Preset_ID'];
            }

            if ($component_template['File_Mode']) {
                // Убираем последнюю часть пути к шаблону компонента:
                $component_path_parts = explode('/', rtrim($component_template['File_Path'], '/'));
                $last_fragment = array_pop($component_path_parts);
                $folder = implode('/', $component_path_parts);
                $this->dumper->export_files(nc_core('HTTP_TEMPLATE_PATH') . 'class' . $folder, $last_fragment, false);
            }
        }

        ##### MIXIN PRESETS #####
        $all_infoblock_ids_string = implode(', ', $this->dumper->get_dict('Sub_Class_ID'));
        $used_mixin_preset_ids = $nc_core->db->get_col(
            "SELECT DISTINCT `Index_Mixin_Preset_ID`
               FROM `Sub_Class`
              WHERE `Sub_Class_ID` IN ($all_infoblock_ids_string)
                AND `Index_Mixin_Preset_ID` > 0
             UNION
             SELECT DISTINCT `IndexItem_Mixin_Preset_ID`
               FROM `Sub_Class`
              WHERE `Sub_Class_ID` IN ($all_infoblock_ids_string)
                AND `IndexItem_Mixin_Preset_ID` > 0            
            "
        );
        $all_mixin_preset_ids = array_merge($default_mixin_preset_ids, $used_mixin_preset_ids);
        if ($all_mixin_preset_ids) {
            $all_mixins_data = nc_db_table::make('Mixin_Preset', 'Mixin_Preset_ID')->where_in_id($all_mixin_preset_ids)->get_result();
            $this->dumper->export_data('Mixin_Preset', 'Mixin_Preset_ID', $all_mixins_data);
        }

        ##### FILETABLE #####
        $this->dumper->export_data('Filetable', 'ID', $filetable_data);

        ##### KEYWORDS #####
        if ($named_entities_path) {
            $this->dumper->set_dump_info('keywords', $keyword_map);
            $this->dumper->set_dump_info('required_lists', $this->exported_classificators);
        }

        ##### ASSETS #####
        $required_assets_files = array();

        foreach ($template_ids as $template_id) {
            $required_assets_files[] = $nc_core->template->get_path($template_id) . 'RequiredAssets.html';
        }

        foreach ($components_and_templates as $component) {
            $required_assets_files[] = $nc_core->component->get_template_file_path(nc_component::FILE_REQUIRED_ASSETS, $component['Class_ID']);
        }

        $all_assets = new nc_page_asset_request_collection();
        foreach ($required_assets_files as $required_assets_file) {
            if (file_exists($required_assets_file)) {
                $all_assets->add_asset_requests_from_array((array)include($required_assets_file));
            }
        }

        $all_asset_paths = $all_assets->with_dependencies()->get_asset_version_paths();
        foreach ($all_asset_paths as $asset_path) {
            $this->dumper->export_files($asset_path);
        }

        return true;
    }

    protected function export_objects(array $infoblock_data, array &$filetable_data) {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        $file_info = $nc_core->file_info;

        $component_id = $infoblock_data['Class_ID'];
        $infoblock_id = $infoblock_data['Sub_Class_ID'];

        if (!$component_id || $nc_core->component->get_by_id($component_id, 'System_Table_ID')) {
            return;
        }

        $component = $nc_core->get_component($component_id);
        $message_table = nc_db_table::make('Message' . $component_id, 'Message_ID');

        // Data
        $objects_data = $message_table
            ->where('Sub_Class_ID', $infoblock_id)
            ->order_by('Parent_Message_ID')
            ->index_by_id()
            ->get_result();
        $this->dumper->export_data($message_table->get_table(), 'Message_ID', $objects_data);
        $object_ids = array_keys($objects_data);

        if ($objects_data) {
            // Files uploaded to the HTML fields (actually now there’s no check whether field actually has HTML format)
            $text_fields = $component->get_fields(NC_FIELDTYPE_TEXT, false);
            if ($text_fields) {
                $this->export_files_from_html_fields($component_id, $objects_data, $text_fields);
            }

            // Files
            $file_fields = $component->get_fields(NC_FIELDTYPE_FILE, false);
            if ($file_fields) {
                $file_info->cache_object_list_data($component_id, $objects_data);
                $file_info->preload_filetable_values($component_id, $object_ids);

                foreach ($objects_data as $row) {
                    foreach ($file_fields as $field_name) {
                        if (!$row[$field_name]) {
                            continue;
                        }
                        $this->export_file($component_id, $row['Message_ID'], $field_name, $filetable_data);
                    }
                }

                $file_info->clear_cache();
            }

            // Multiple files
            $multiple_file_fields = $component->get_fields(NC_FIELDTYPE_MULTIFILE, false);
            if ($multiple_file_fields) {
                $multiple_files_data = $this->multifield_table
                    ->where_in('Message_ID', $object_ids)
                    ->where_in('Field_ID', array_keys($multiple_file_fields))->get_result();
                $this->dumper->export_data($this->multifield_table->get_table(), 'ID', $multiple_files_data);

                foreach ($multiple_file_fields as $field_id => $field_name) {
                    $this->dumper->export_files($nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH . 'multifile', $field_id);
                }
            }
        }

        // Full page blocks (only if not already running a full page block export, i.e. for the object in infoblock itself)
        if (!$nc_core->component->is_full_page_area($infoblock_data['AreaKeyword'])) {
            $all_component_full_page_block_areas = $db->get_col(
                "SELECT DISTINCT `AreaKeyword` 
                   FROM `Sub_Class` 
                  WHERE `Catalogue_ID` = 0
                    AND `AreaKeyword` REGEXP '{$component->get_full_page_area_keyword_regexp()}'"
            );

            if ($all_component_full_page_block_areas) {
                foreach ($object_ids as $object_id) {
                    $object_area_keyword = $component->get_full_page_area_keyword($object_id);
                    if (in_array($object_area_keyword, $all_component_full_page_block_areas)) {
                        $full_page_blocks_data = nc_db_table::make('Sub_Class')
                            ->where('Catalogue_ID', 0)
                            ->where('AreaKeyword', $object_area_keyword)
                            ->index_by_id()
                            ->get_result();

                        $this->dumper->export_data('Sub_Class', 'Sub_Class_ID', $full_page_blocks_data);
                        $this->export_infoblock_custom_settings_files($full_page_blocks_data);
                        $this->export_infoblock_mixin_settings_files($full_page_blocks_data);

                        foreach ($full_page_blocks_data as $full_page_block_data) {
                            $this->export_objects($full_page_block_data, $filetable_data);
                        }
                    }
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    protected function export_files_from_html_fields($component_id, array $all_data, array $field_names) {
        $nc_core = nc_core::get_object();
        $quote = '[\'"]';
        $userfiles_regexp = "@$quote(" . $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH . "userfiles/[^'\"]+)$quote@";
        foreach ($all_data as $row) {
            foreach ($field_names as $field_name) {
                if (preg_match_all($userfiles_regexp, $row[$field_name], $matches)) {
                    $meta = array();
                    $meta['userfiles'][$component_id][$field_name][$row['Message_ID']] = (int)$row['Message_ID'];
                    foreach ($matches[1] as $file_path) {
                        $this->dumper->export_files($file_path, null, false, $meta + array('src' => $file_path));
                    }
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    protected function export_file($component, $id, $field_name, &$filetable_data) {
        $nc_core = nc_core::get_object();
        $file_data = $nc_core->file_info->get_file_info($component, $id, $field_name, false, false, true);

        if (is_numeric($component)) {
            $file_meta = null;
        } else {
            $file_meta = array(
                'fs_type' => $file_data['fs_type'],
                'table' => $component,
                'field_name' => $field_name,
            );
        }

        foreach (array('url', 'preview_url') as $file_key) {
            if (file_exists($nc_core->DOCUMENT_ROOT . $file_data[$file_key])) {
                $this->dumper->export_files($file_data[$file_key], null, false, $file_meta);

                if ($file_key == 'url' && $file_data['fs_type'] == NC_FS_PROTECTED) {
                    $filetable_values = $nc_core->file_info->get_filetable_values($component, $id, $field_name, true);
                    if ($filetable_values) {
                        $filetable_data[] = $filetable_values;
                    }
                }
            }
        }

    }

    //-------------------------------------------------------------------------

    protected function export_infoblock_custom_settings_files(array $infoblocks_data) {
        $nc_core = nc_core::get_object();
        $files_path = $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH;
        foreach ($infoblocks_data as $infoblock_id => $infoblock_data) {
            if ($infoblock_data['CustomSettings']) {
                $custom_settings = (array)eval("return $infoblock_data[CustomSettings]");
                foreach ($custom_settings as $key => $value) {
                    // duck typing... а можно было бы смотреть настройки полей (Class.CustomSettingsTemplate)
                    if (preg_match('~:(cs/[^:]+)$~', $value, $matches) && file_exists($nc_core->FILES_FOLDER . $matches[1])) {
                        $this->dumper->export_files($files_path . $matches[1]);
                    }
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    protected function export_infoblock_mixin_settings_files(array $infoblocks_data) {
        foreach ($infoblocks_data as $infoblock_id => $infoblock_data) {
            foreach (array('Index_Mixin_Settings', 'IndexItem_Mixin_Settings') as $field) {
                $mixin_files = $this->extract_infoblock_mixin_file_paths($infoblock_data, $field);
                foreach ($mixin_files as $real_path) {
                    $this->dumper->export_files($real_path);
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    protected function extract_infoblock_mixin_file_paths($infoblock_data, $field) {
        if ($infoblock_data[$field] === '{}' || !$infoblock_data[$field]) {
            return array();
        }

        $nc_core = nc_core::get_object();
        $result = array();

        // экспортируются файлы только из /images/ (использовалось до добавления загрузки
        // файлов в миксинах и netcat_files/mixin/
        // SUB_FOLDER на момент написания явно зашит в путях... (переделать)

        $pattern =
            '~"(' .
            $nc_core->SUB_FOLDER .
            "(?:$nc_core->HTTP_IMAGES_PATH|{$nc_core->HTTP_FILES_PATH}mixin/)" .
            '[^"]+' .
            ')"~';
        preg_match_all($pattern, $infoblock_data[$field], $matches);
        if ($matches[1]) {
            foreach ($matches[1] as $path) {
                if (file_exists($nc_core->DOCUMENT_ROOT . $path)) {
                    $result[] = $path;
                }
            }
        }
        return $result;
    }

    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------

    public function call_event($event, $attr) {
        if (strpos($event, 'before_insert_classificator_') === 0) {
            return $this->event_before_insert_classificator_item($attr[0], substr($event, strlen('before_insert_classificator_')));
        }

        return parent::call_event($event, $attr);
    }

    protected function import_process() {
        $nc_core = nc_core::get_object();

        if (!$nc_core->catalogue->can_create()) {
            throw new Exception(CONTROL_CONTENT_CATALOUGE_ERROR_CANNOT_CREATE_MORE);
        }

        $nc_core->event->execute(nc_event::BEFORE_SITE_IMPORTED);

        $this->dumper->register_dict_field(array(
            'Parent_Sub_ID' => 'Subdivision_ID',
            'Parent_Template_ID' => 'Template_ID',
            'ClassTemplate' => 'Class_ID',
            'Class_Template_ID' => 'Class_ID',
            'Main_ClassTemplate_ID' => 'Class_ID',
            'Edit_Class_Template' => 'Class_ID',
            'Admin_Class_Template' => 'Class_ID',
            'Index_Mixin_Preset_ID' => 'Mixin_Preset_ID',
            'IndexItem_Mixin_Preset_ID' => 'Mixin_Preset_ID',
            'Filter_Sub_Class_ID' => 'Sub_Class_ID',
            'Data_Sub_Class_ID' => 'Sub_Class_ID',
        ));

        // Add fields for system tables
        $this->dumper->import_table_fields('Catalogue');
        $this->dumper->import_table_fields('Subdivision');
        $this->dumper->import_table_fields('Template');
        $this->dumper->import_table_fields('User');

        // Template
        $this->dumper->import_data('Template');
        $this->dumper->import_data('Template_Partial');

        $this->dumper->import_data('Catalogue');
        $this->new_id = $this->dumper->get_dict('Catalogue_ID', $this->id);

        // Далее могут производиться действия, запрашивающие у экземпляра сайта его id
        // Чтобы избежать исключения Catalogue with id not found, требуется обновить поле $data у экземпляра nc_Catalogue
        $nc_core->catalogue->load_all();

        // Class
        $this->dumper->import_data('Class');

        // Field
        $this->dumper->import_data('Field');

        $nc_core->component->update_cache_for_multipurpose_templates();

        // Mixin presets
        $this->dumper->import_data('Mixin_Preset');

        // Classificator
        $this->dumper->import_data('Classificator');

        // Message*
        $class_ids = $this->dumper->get_dict('Class_ID');
        foreach ($class_ids as $old_id => $new_id) {
            if (isset($this->new_components[$new_id])) {
                $this->dumper->import_table('Message' . $old_id, 'Message' . $new_id);
                $this->check_message_table_structure($new_id);
            }
        }

        // Subdivision
        $this->dumper->import_data('Subdivision');

        // Update Catalogue
        $site_update = array();
        $site = $this->site_table->where_id($this->new_id)->get_row();
        // Title_Sub_ID, E404_Sub_ID, etc.:
        foreach ($site as $key => $value) {
            if (preg_match('/_Sub_ID$/', $key)) {
                $site_update[$key] = $value ? $this->dumper->get_dict('Subdivision_ID', $value) : 0;
            }
        }
        // Set current domain
        if ($this->site_table->count_all() == 1) {
            if ($_SERVER['HTTP_HOST']) {
                $site_update['Domain'] = $_SERVER['HTTP_HOST'];
            }
        }
        $this->site_table->where_id($this->new_id)->update($site_update);

        // Sub_Class
        $this->dumper->import_data('Sub_Class');

        if (!$this->dumper->get_import_settings('save_ids')) {
            // Нужно обновить динамические значения в AreaKeyword после импорта всех инфоблоков
            $this->update_site_infoblock_area_keywords();
            // Sub_Class.Parent_Sub_Class_ID (отдельно, так как порядок при импорте не иерархический)
            $this->update_parent_sub_class_id();
        }

        // Filter
        $this->dumper->import_data('Filter');

        // AreaCondition tables
        $this->dumper->import_data('Sub_Class_AreaCondition_Subdivision');
        $this->dumper->import_data('Sub_Class_AreaCondition_Subdivision_Exception');
        $this->dumper->import_data('Sub_Class_AreaCondition_Class');
        $this->dumper->import_data('Sub_Class_AreaCondition_Class_Exception');
        $this->dumper->import_data('Sub_Class_AreaCondition_Message');  // + см. self::event_after_insert_message()

        // Custom settings relations
        if ($this->custom_settings_relations['template']) {
            $this->update_template_settings('catalogue', $this->new_id);

            $subdivision_list = $this->subdivision_table
                ->select('Subdivision_ID, Template_ID')
                ->where('Catalogue_ID', '=', $this->new_id)
                ->where('TemplateSettings', '!=', '')
                ->where('TemplateSettings', 'IS NOT', null)
                ->get_result();

            foreach ($subdivision_list as $subdivision) {
                $this->update_template_settings(
                    'subdivision',
                    $subdivision['Subdivision_ID'],
                    $subdivision['Template_ID']
                );
            }
        }

        if ($this->custom_settings_relations['component']) {
            $infoblocks_with_custom_settings = $this->subclass_table
                ->where('Catalogue_ID', $this->new_id)
                ->where_in('Class_ID', array_keys($this->custom_settings_relations['component']))
                ->where('CustomSettings', '!=', '');
            $infoblock_data = $infoblocks_with_custom_settings->index_by_id()->get_result();
            $infoblock_custom_settings = $infoblocks_with_custom_settings->get_list('CustomSettings');

            foreach ($infoblock_custom_settings as $infoblock_id => $old_custom_settings) {
                $class_template_id = nc_array_value(nc_array_value($infoblock_data, $infoblock_id), 'Class_Template_ID')  ?: nc_array_value(nc_array_value($infoblock_data, $infoblock_id),'Class_ID');
                // Нужно обновить настройки лишь единожды, поэтому передаем используемый шаблон компонента в инфоблоке
                if ($updated_custom_settings = $this->make_custom_settings_array('component', $old_custom_settings, $class_template_id)) {
                    $this->subclass_table->where_id($infoblock_id)->update(array(
                        'CustomSettings' => $updated_custom_settings
                    ));
                }
            }
        }

        // DATA
        foreach ($class_ids as $old_id => $new_id) {
            if (isset($this->new_components[$new_id]) || isset($this->not_imported_components[$new_id])) {
                $this->dumper->import_data("Message{$old_id}", "Message{$new_id}", array(
                    'Parent_Message_ID' => "Message{$new_id}.Message_ID",
                ));
            }
        }

        // Full page blocks
        $this->update_full_page_area_keywords();

        // DATA fields related to another message row
        foreach ($this->relation_message_fields as $message_id => $relation_message_fields) {
            $message_table = nc_db_table::make('Message' . $message_id, 'Message_ID');
            foreach ($relation_message_fields as $field_name => $field) {
                $old_class_id = (int)$field['Format'];
                $new_class_id = $this->dumper->get_dict('Class_ID', $old_class_id);
                // $new_message_ids = $this->dumper->get_dict("Message{$new_class_id}.Message_ID");

                $data = $message_table->select("`Message_ID`, `{$field_name}`")->index_by_id()->get_result();
                foreach ($data as $row_id => $row) {
                    $value = $this->dumper->get_dict("Message{$new_class_id}.Message_ID", $row[$field_name]);
                    $message_table->where_id($row_id)->update(array($field_name => $value));
                }

                $this->field_table->where_id($field['Field_ID'])->update(array('Format'=>$new_class_id));
            }
        }

        // Sub_Class.Condition / Sub_Class.ConditionQuery
        $this->update_infoblock_query_conditions();

        // Settings
        $this->dumper->import_data('Settings');

        // Files
        $this->dumper->import_data('Filetable');
        $this->dumper->import_data('Multifield');

        $path_prefixes = array(
            $nc_core->HTTP_TEMPLATE_PATH . 'template',
            $nc_core->HTTP_TEMPLATE_PATH . 'class',
            $nc_core->HTTP_TEMPLATE_PATH . 'asset',
            $nc_core->HTTP_FILES_PATH,
            $nc_core->HTTP_IMAGES_PATH,
        );
        $this->dumper->import_files($path_prefixes, false);

        $nc_core->event->execute(nc_event::AFTER_SITE_IMPORTED, $this->new_id);

        nc_tpl_mixin_cache::generate_site_files($this->new_id, true);
        nc_tpl_mixin_cache::generate_site_files(0, true);
    }

    //-------------------------------------------------------------------------

    protected function update_infoblock_query_conditions() {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        $infoblocks_with_conditions = $db->get_col(
            "SELECT `Sub_Class_ID`, `Condition`
               FROM `Sub_Class`
              WHERE `Catalogue_ID` = $this->new_id
                AND `Condition` != '' AND `Condition` IS NOT NULL",
            1, 0
        );

        foreach ($infoblocks_with_conditions as $infoblock_id => $condition) {
            $condition = nc_condition::create(json_decode($condition, true));
            $updated_condition_array = $condition->get_updated_raw_options_array_on_import($this->dumper);
            $infoblock_condition_translator = new nc_condition_infoblock_translator($updated_condition_array, $infoblock_id);
            $db->query(
                "UPDATE `Sub_Class`
                    SET `Condition` = '{$db->escape(json_encode($updated_condition_array))}',
                        `ConditionQuery` = '{$db->escape($infoblock_condition_translator->get_sql_condition())}'
                  WHERE `Sub_Class_ID` = $infoblock_id"
            );
        }
    }

    //-------------------------------------------------------------------------

    protected function update_template_settings($entity, $entity_id, $template_id = null) {
        switch ($entity) {
            case 'catalogue':
                $entity_table = 'site_table';
                break;
            case 'subdivision':
                $entity_table = 'subdivision_table';
                break;
            default:
                trigger_error("Unknown entity '$entity'", E_USER_WARNING);
                return false;
        }

        if ($template_id === null) {
            $template_id = $this->$entity_table
                ->where_id($entity_id)
                ->where('TemplateSettings', '!=', '')
                ->where('TemplateSettings', 'IS NOT', null)
                ->get_value('Template_ID');
        }

        if (!$template_id) {
            return false;
        }

        if ($this->template_table
            ->where_id($template_id)
            ->where('CustomSettings', '!=', '')
            ->where('CustomSettings', 'IS NOT', null)
            ->get_value('CustomSettings')
        ) {
            $template_settings = $this->$entity_table
                ->where_id($entity_id)
                ->get_value('TemplateSettings');

            $new_template_settings = $this->make_custom_settings_array('template', $template_settings, $template_id);

            if ($new_template_settings) {
                $this->$entity_table->where_id($entity_id)->update(array(
                    'TemplateSettings' => $new_template_settings
                ));
            }

            return true;
        }

        $parent_template_id = $this->template_table
            ->where('Template_ID', '=', $template_id)
            ->get_value('Parent_Template_ID');

        if (!$parent_template_id) {
            return false;
        }

        return $this->update_template_settings($entity, $entity_id, $parent_template_id);
    }

    //-------------------------------------------------------------------------

    protected function update_site_infoblock_area_keywords() {
        $nc_core = nc_Core::get_object();

        $dynamic_area_rows = (array)$nc_core->db->get_results(
            "SELECT `Sub_Class_ID`, `AreaKeyword`
               FROM `Sub_Class`
              WHERE `AreaKeyword` LIKE '%_cc_%'
                AND `Catalogue_ID` = $this->new_id",
            ARRAY_A
        );

        foreach ($dynamic_area_rows as $dynamic_area_row) {
            // Нужно выцепить старый ID инфоблока (выгруженный с готового сайта) из AreaKeyword и заменить его на новый
            if (preg_match('/_cc_(\d+)/u', $dynamic_area_row['AreaKeyword'], $matches)) {
                $old_sub_class_id = $matches[1];
                $new_sub_class_id = (int)$this->dumper->get_dict('Sub_Class_ID', $old_sub_class_id);

                if ($new_sub_class_id && $old_sub_class_id != $new_sub_class_id) {
                    $nc_core->db->query(
                        "UPDATE `Sub_Class`
                            SET `AreaKeyword` = REPLACE(`AreaKeyword`, '_cc_$old_sub_class_id', '_cc_$new_sub_class_id')
                          WHERE `Sub_Class_ID` = $dynamic_area_row[Sub_Class_ID]"
                    );
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    protected function update_full_page_area_keywords() {
        foreach ($this->imported_full_page_blocks as $old_infoblock_id => $old_area_keyword) {
            $nc_core = nc_core::get_object();
            list($old_component_id, $old_object_id) = $nc_core->component->get_full_page_area_object($old_area_keyword);
            $new_component_id = $this->dumper->get_dict('Class_ID', $old_component_id);
            $new_object_id = $this->dumper->get_dict("Message{$new_component_id}.Message_ID", $old_object_id);
            $new_infoblock_id = $this->dumper->get_dict('Sub_Class_ID', $old_infoblock_id);
            $new_area_keyword = $nc_core->get_component($new_component_id)->get_full_page_area_keyword($new_object_id);
            $nc_core->db->query("UPDATE `Sub_Class` SET `AreaKeyword` = '$new_area_keyword' WHERE `Sub_Class_ID` = $new_infoblock_id");
        }
    }

    //-------------------------------------------------------------------------

    protected function update_parent_sub_class_id() {
        $nc_core = nc_core::get_object();

        foreach ($this->imported_parent_sub_class_ids as $old_infoblock_id => $old_parent_infoblock_id) {
            $new_infoblock_id = $this->dumper->get_dict('Sub_Class_ID', $old_infoblock_id);
            $new_parent_infoblock_id = $this->dumper->get_dict('Sub_Class_ID', $old_parent_infoblock_id);
            $nc_core->db->query(
                "UPDATE `Sub_Class` 
                    SET `Parent_Sub_Class_ID` = $new_parent_infoblock_id
                  WHERE `Sub_Class_ID` = $new_infoblock_id"
            );
        }
    }

    //-------------------------------------------------------------------------

    protected function make_custom_settings_array($settings_type, $settings_code, $given_template_id) {
        if (!$settings_code) {
            return false;
        }

        $settings = eval("return $settings_code");

        if (!$settings) {
            return false;
        }

        $rel_fields = nc_array_value($this->custom_settings_relations[$settings_type], $given_template_id, array());

        foreach ($rel_fields as $settings_field => $dict_field) {
            if (isset($settings[$settings_field])) {
                $settings[$settings_field] = $this->dumper->get_dict($dict_field, $settings[$settings_field]);
            }
        }

        if ($settings_type === 'template') {
            $variable_name = 'TemplateSettings';
        } else if ($settings_type === 'component') {
            $variable_name = 'CustomSettings';
        } else {
            trigger_error("Unknown custom settings type '$settings_type'", E_USER_WARNING);
            return $settings_code;
        }

        return '$' . $variable_name . ' = ' . var_export($settings, true) . ';';
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_catalogue($row) {
        $domain_exists = (bool) $this->site_table->where('Domain', $row['Domain'])->count_all();
        if ($domain_exists) {
            $row['Domain'] = 'domain-' . uniqid();
        }
        $row['Priority'] = nc_db()->get_var("SELECT MAX(`Priority`) FROM `Catalogue`") + 1;
        return $row;
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_settings($row) {
        if (substr($row['Key'], 0, 13) == 'nc_shop_mode_') {
            $row['Key'] = 'nc_shop_mode_' . $row['Catalogue_ID'];
        }

        if ($row['Value']) {
            $module_settings = $this->backup->get_settings('module_settings');
            if (isset($module_settings[$row['Module']]['settings_dict_fields'][$row['Key']])) {
                $dict_field = $module_settings[$row['Module']]['settings_dict_fields'][$row['Key']];
                // Значение может быть как числом, так и списком идентификаторов через запятую.
                $ids = preg_split('/\s*,\s*/', $row['Value']);
                foreach ($ids as $key => $id) {
                    $ids[$key] = $this->dumper->get_dict($dict_field, $id);
                }
                $row['Value'] = implode(',', $ids); // Если были пробелы, они будут потеряны
            }
        }

        return $row;
    }

    //-------------------------------------------------------------------------

    /**
     * @param $row
     */
    protected function event_after_insert_subdivision($row) {
        $db = nc_Core::get_object()->db;

        foreach ($this->get_subdivision_file_field_names() as $file_field) {
            if (empty($row[$file_field])) {
                continue;
            }

            // Файлы «обычной ФС» — e.g. "x.png:image/png:123456:111/x.png", где 111 — ID раздела
            $value_parts = explode(':', $row[$file_field]);
            if (!isset($value_parts[3])) {
                continue;
            }

            $path_parts = explode('/', $value_parts[3]);
            $old_sub_id = (int)$path_parts[0];
            $new_sub_id = $path_parts[0] = (int)$this->dumper->get_dict('Subdivision_ID', $old_sub_id);

            if (!$new_sub_id || $old_sub_id === $new_sub_id) {
                continue;
            }

            $value_parts[3] = implode('/', $path_parts);
            $new_value = implode(':', $value_parts);

            $db->query("UPDATE `Subdivision` SET `$file_field` = '" . $db->escape($new_value) . "' WHERE `Subdivision_ID` = $new_sub_id");
        }
    }

    //-------------------------------------------------------------------------

    /**
     * @param $row
     */
    protected function event_before_insert_sub_class($row) {
        if ($row['Parent_Sub_Class_ID']) {
            $this->imported_parent_sub_class_ids[$row['Sub_Class_ID']] = $row['Parent_Sub_Class_ID'];
        }

        // Экспорт старого архива на новую копию, в которой есть поле Sub_Class.Sub_Class_Type
        if (!empty($row['IsMainContainer'])) {
            $row['Sub_Class_Type'] = 'main';
            unset($row['IsMainContainer']);
        } elseif (empty($row['Sub_Class_Type']) && empty($row['Class_ID'])) {
            $row['Sub_Class_Type'] = 'container';
        }

        if (empty($row['Sub_Class_Type'])) {
            $row['Sub_Class_Type'] = 'object_list';
        }

        // Если это инфоблок со страницы полного вывода, запоминаем его, чтобы потом обновить ключевое слово
        // области в $this->update_full_page_area_keywords(). AreaKeyword на время сбрасываем, чтобы не возникла
        // ошибка из-за неуникального набора значений.
        $area_keyword = $row['AreaKeyword'];
        if ($area_keyword && nc_core::get_object()->component->is_full_page_area($area_keyword)) {
            $this->imported_full_page_blocks[$row['Sub_Class_ID']] = $area_keyword;
            $row['AreaKeyword'] = uniqid('_tmp_');
        }

        return $row;
    }

    //-------------------------------------------------------------------------

    /**
     * @param $row
     * @return mixed
     */
    protected function event_before_insert_template($row) {
        if (!$row['Parent_Template_ID'] && isset($row['Keyword']) && $row['Keyword']) {
            $template_id = $this->template_table
                ->where('Parent_Template_ID', 0)
                ->where('Keyword', $row['Keyword'])
                ->get_value('Template_ID');

            if ($template_id) {
                // Макет "netcat_default" должен быть только один в системе
                if ($row['Keyword'] === 'netcat_default') {
                    $this->dumper->set_dict('Template_ID', $row['Template_ID'], $template_id);
                    $row = false;

                    return $row;
                }

                // Все остальные макеты буду импортированы без ключевого слова
                $row['Keyword'] = '';
            }
        }

        return $row;
    }

    //-------------------------------------------------------------------------

    protected function event_after_insert_template($row, $insert_id) {
        // Обновление пути к файлам макета
        $update = array(
            'File_Path' => ($row['Parent_Template_ID']
                                ? $this->template_paths[$row['Parent_Template_ID']]
                                : '/') .
                           ($row['Keyword'] ?: $insert_id) .
                           "/",
        );
        $this->template_paths[$insert_id] = $update['File_Path'];

        // Копируем корневую папку макета дизайна по новому пути
        if (!$row['Parent_Template_ID']) {
            $old_path = '/netcat_template/template/' . trim($row['File_Path'], '/');
            $new_path = '/netcat_template/template/' . trim($update['File_Path'], '/');

            $this->template_paths_dict[$old_path] = $new_path;
        }

        $this->template_table->where_id($insert_id)->update($update);


        // Пользовательские параметры — связь с другим объектом
        $dict_fields = $this->custom_settings_relation_fields;

        if ($row['CustomSettings']) {
            $settings_array = (array)eval("return $row[CustomSettings]");
            foreach ($settings_array as $settings_field => $settings) {
                if ($settings['type'] == 'rel' && isset($dict_fields[$settings['subtype']])) {
                    $dict_key = $dict_fields[$settings['subtype']];
                    $this->custom_settings_relations['template'][$insert_id][$settings_field] = $dict_key;
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    protected function register_component_custom_settings_relations($component_id, $component_data) {
        if ($component_data['CustomSettingsTemplate']) {
            // Пользовательские параметры — связь с другим объектом
            $dict_fields = $this->custom_settings_relation_fields;
            $settings_array = (array)eval("return $component_data[CustomSettingsTemplate]");
            foreach ($settings_array as $settings_field => $settings) {
                if ($settings['type'] == 'rel' && isset($dict_fields[$settings['subtype']])) {
                    $dict_key = $dict_fields[$settings['subtype']];
                    $this->custom_settings_relations['component'][$component_id][$settings_field] = $dict_key;
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_class($row) {
        // Для таблицы «Пользователи» пропускаем основной класс и шаблоны с
        // совпадающими ключевыми словами.
        $db = nc_db();
        $existing_component_id = null;

        if ($row['System_Table_ID']) {
            // таблица «Пользователи»
            $condition = $row['ClassTemplate'] == 0 ? "`ClassTemplate` = 0" : "`Keyword` = '$row[Keyword]'";
            $existing_component_id = $db->get_var(
                "SELECT `Class_ID`
                   FROM `Class`
                  WHERE `System_Table_ID` = 3
                    AND $condition"
            );
        }

        // Для «вспомогательных» и «служебных» компонентов, помеченных как
        // IsAuxiliary (но не их дополнительных шаблонов), попробуем найти
        // (по названию и набору полей) уже существующий компонент;
        // если таковой есть, не будем создавать новый компонент
        // (совместимость со старыми версиями)
        $auxiliary_component_fields = $this->dumper->get_dump_info('auxiliary_component_fields_' . $row['Class_ID']);
        if ($auxiliary_component_fields !== null && !$row['ClassTemplate']) {
            $db->query("SET group_concat_max_len=16384");
            $existing_component_id = $db->get_var(
                "SELECT `c`.`Class_ID`,
                        IFNULL(GROUP_CONCAT(`f`.`Field_Name` ORDER BY `f`.`Field_Name`), '') AS `Fields`
                   FROM `Class` AS `c`  LEFT JOIN `Field` AS `f` USING (`Class_ID`)
                  WHERE `c`.`Class_Name` = '" . $db->escape($row['Class_Name']) . "'
                    AND `c`.`Class_Group` = '" . $db->escape($row['Class_Group']) . "'
                    AND `c`.`IsAuxiliary` = 1
                  GROUP BY `c`.`Class_ID`
                 HAVING `Fields` = '" . $db->escape($auxiliary_component_fields) . "'"
            );
        }

        // компоненты и их шаблоны при совпадении по ключевому слову
        // с существующими не импортируются заново
        if (!$existing_component_id && $row['Keyword']) {
            $existing_component_id = $db->get_var(
                "SELECT `Class_ID`
                   FROM `Class`
                  WHERE `File_Path` = '$row[File_Path]'"
            );
        }

        if ($existing_component_id) {
            $existing_component_data = $this->class_table->get_row($existing_component_id);
            $this->dumper->set_dict('Class_ID', $row['Class_ID'], $existing_component_id);
            $this->not_imported_components[$existing_component_id] = nc_Core::get_object()->HTTP_TEMPLATE_PATH . 'template/class' . $existing_component_data['File_Path'];
            $this->imported_component_keywords[$existing_component_id] = $existing_component_data['Keyword'] ?: $existing_component_id;
            $this->register_component_custom_settings_relations($existing_component_id, $existing_component_data);
            $row = false;
        }

        return $row;
    }

    //-------------------------------------------------------------------------

    protected function event_after_insert_class($row, $class_id) {
        $parent_class_folder = '';

        $this->imported_component_keywords[$class_id] = $row['Keyword'] ?: $class_id;
        if ($row['ClassTemplate']) {
            $parent_class_folder .= '/';

            if (!empty($this->imported_component_keywords[$class_id])) {
                $parent_class_folder .= $this->imported_component_keywords[$row['ClassTemplate']];
            }
            else {
                $parent_class_folder .= $row['ClassTemplate'];
            }
        }
        else {
            $this->new_components[$class_id] = $class_id;
        }

        $this->register_component_custom_settings_relations($class_id, $row);

        $update = array(
            'File_Path' => "$parent_class_folder/" . ($row['Keyword'] ?: $class_id) . "/",
        );

        $this->class_table->where_id($class_id)->update($update);
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_multifield($row) {
        $new_component_id = $this->get_component_id_by_field_id($row['Field_ID']);
        $new_object_id = $this->dumper->get_dict("Message{$new_component_id}.Message_ID", $row['Message_ID']);

        $row['Message_ID'] = $new_object_id;

        foreach (array('Path', 'Preview') as $f) {
            $path = nc_array_value($row, $f);
            if ($path) {
                // /netcat_files/multifile/%field_id%/ — до ~5.8
                // /netcat_files/multifile/%field_id%/%message_id%/
                $path_parts = explode('/', $path);
                if (ctype_digit(nc_array_value($path_parts, 3))) {
                    $path_parts[3] = $this->dumper->get_dict('Field_ID', $path_parts[3]);
                }
                if (ctype_digit(nc_array_value($path_parts, 4))) {
                    $path_parts[4] = $new_object_id;
                }
                $row[$f] = implode('/', $path_parts);
            }
        }

        return $row;
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_filetable($row) {
        $file_path = explode('/', $row['File_Path']);

        if ($file_path[1] === 'c') {
            /** Файлы сайтов "/c/": также @see nc_backup_site::update_site_renamed_filetable_value() */
            $row['Message_ID'] = $this->new_id;
        } else if (is_numeric($file_path[1])) {
            if (count($file_path) === 3) { // Файлы разделов: "/123/"
                $file_path[1] = $this->dumper->get_dict('Subdivision_ID', $file_path[1]);
                $row['Message_ID'] = $this->dumper->get_dict('Subdivision_ID', $row['Message_ID']);
            } else if (count($file_path) === 4 && is_numeric($file_path[2])) { // Файлы объектов: "/123/456/"
                $new_component_id = $this->get_component_id_by_field_id($row['Field_ID']);
                $file_path[1] = $this->dumper->get_dict('Subdivision_ID', $file_path[1]);
                $file_path[2] = $this->dumper->get_dict('Sub_Class_ID', $file_path[2]);
                $row['Message_ID'] = $this->dumper->get_dict("Message{$new_component_id}.Message_ID", $row['Message_ID']);
            }
            $row['File_Path'] = implode('/', $file_path);
        }

        return $row;
    }

    // ------------------------------------------------------------------------

    protected function event_before_insert_field($row) {
        // skip fields of the components that were not imported
        if ($row['Class_ID'] || $row['System_Table_ID']) {
            $existing_field_id = $this->field_table
                ->where('Class_ID', $row['Class_ID'])
                ->where('System_Table_ID', $row['System_Table_ID'])
                ->where('Field_Name', $row['Field_Name'])
                ->get_value('Field_ID');

            if ($existing_field_id) {
                $this->dumper->set_dict('Field_ID', $row['Field_ID'], $existing_field_id);
                $this->store_field_info($row, $existing_field_id);
                $row = false;
            }
        }
        else if ($row['Widget_Class_ID']) {
            // виджеты в текущей версии не импортируются
            $row = false;
        }

        return $row;
    }

    // ------------------------------------------------------------------------

    protected function event_after_insert_field($row, $field_id) {
        $this->store_field_info($row, $field_id);
        $existing_class_id = $this->dumper->get_dict('Class_ID', $row['Class_ID']);
        
        if (isset($this->not_imported_components[$row['Class_ID']])) {
            $this->nc_core->component->add_field_to_message_table($existing_class_id, $row);
        }
    }

    // ------------------------------------------------------------------------

    protected function store_field_info($row, $field_id) {
        $class_id   = $row['Class_ID'];
        $field_name = $row['Field_Name'];
        $row['Field_ID'] = $field_id;
        switch ($row['TypeOfData_ID']) {
            // File fields
            case NC_FIELDTYPE_FILE:
                $this->file_fields[$class_id][$field_name] = $row;
                break;

            // Relation fields
            case NC_FIELDTYPE_RELATION:
                if (is_numeric($row['Format'])) {
                    $this->relation_message_fields[$class_id][$field_name] = $row;
                } else {
                    $this->relation_fields[$class_id][$field_name] = $row;
                }
                break;
        }
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_classificator($row) {
        $exists = $this->classificator_table->where('Table_Name', $row['Table_Name'])->count_all();

        if ($exists) {
            $this->preexisting_classificators[strtolower($row['Table_Name'])] = $row['Table_Name'];
            $this->dumper->import_data('Classificator_' . $row['Table_Name']);
            $row = false;
        }

        return $row;
    }

    //-------------------------------------------------------------------------

    protected function event_after_insert_classificator($row) {
        $table = 'Classificator_' . $row['Table_Name'];
        $this->dumper->import_table($table);
        $this->dumper->import_data($table);
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_classificator_item($row, $classificator_lowercase_name) {
        if (!isset($this->preexisting_classificators[$classificator_lowercase_name])) {
            // (This event handler is intended to process only items of the classifiers
            // which existed prior to the current import operation, see
            // event_before_insert_classificator() method)
            return $row;
        }

        // Proper (capitalized) table name:
        $classificator = $this->preexisting_classificators[$classificator_lowercase_name];
        $id_field = "{$classificator}_ID";
        $name_field = "{$classificator}_Name";

        // Check if there is a record with the same Name and Value
        $existing_id = nc_db_table::make("Classificator_{$classificator}")
                            ->where($name_field, $row[$name_field])
                            ->where('Value', $row['Value'])
                            ->get_value($id_field);

        if ($existing_id) {
            $this->dumper->set_dict($id_field, $row[$id_field], $existing_id);
            $row = false;
        }

        return $row;
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_message($component_id, $row) {
        $result = null;

        if (isset($this->relation_fields[$component_id])) {
            foreach ($this->relation_fields[$component_id] as $key => $field) {
                $val = $row[$key];
                if (!$val) {
                    continue;
                }
                $new_val = null;
                $format  = strtolower(current(explode(':', $field['Format'], 2)));
                switch ($format) {
                    case 'subdivision':
                        $new_val = $this->dumper->get_dict('Subdivision_ID', $val);
                        break;
                    case 'subclass':
                    case 'sub_class':
                    case 'sub-class':
                        $new_val = $this->dumper->get_dict('Sub_Class_ID', $val);
                        break;
                    case 'catalogue':
                        $new_val = $this->dumper->get_dict('Catalogue_ID', $val);
                        break;
                }
                if ($new_val && $new_val != $val) {
                    $row[$key] = $new_val;
                    $result    = $row;
                }
            }
        }

        if (isset($this->file_fields[$component_id])) {
            foreach ($this->file_fields[$component_id] as $key => $field) {
                $val = $row[$key];
                $val = explode(':', $val);
                if (isset($val[3])) { // «Стандартная файловая система» (/netcat_files/12/34/file.ext)
                    $file = explode('/', $val[3]);
                    $file[0] = $this->dumper->get_dict('Subdivision_ID', $file[0]);
                    $file[1] = $this->dumper->get_dict('Sub_Class_ID', $file[1]);
                    $val[3]  = implode('/', $file);
                }
                $row[$key] = implode(':', $val);
            }
            $result = $row;
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    protected function event_after_insert_message($component_id, $object_data, $new_object_id, $old_object_id) {
        if ($old_object_id != $new_object_id) {
            nc_db()->query(
                "UPDATE `Sub_Class_AreaCondition_Message`
                    SET `Message_ID` = $new_object_id
                  WHERE `Class_ID` = $component_id
                    AND `Message_ID` = $old_object_id"
            );
        }
    }

    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------

    protected function detect_type_by_path($path) {
        global $HTTP_TEMPLATE_PATH, $HTTP_FILES_PATH;

        $types = array(
            'class' => $HTTP_TEMPLATE_PATH . 'class/',
            'template' => $HTTP_TEMPLATE_PATH . 'template/',
            'multifile' => $HTTP_FILES_PATH . 'multifile/',
            'mixin_settings' => $HTTP_FILES_PATH . 'mixin/',
            'userfiles' => $HTTP_FILES_PATH . 'userfiles/',
            'files' => $HTTP_FILES_PATH,
        );

        foreach ($types as $type => $type_path) {
            if (substr($path, 0, strlen($type_path)) == $type_path) {
                return $type;
            }
        }

        return null;
    }

    //-------------------------------------------------------------------------

    protected function event_before_copy_file($path, $file, $src, $meta) {
        $nc_core = nc_core::get_object();
        switch ($this->detect_type_by_path($path)) {
            case 'class':
                foreach ($this->not_imported_components as $file_path) {
                    if (strpos($file_path, $path . $file . '/') === 0) {
                        return false;
                    }
                }

                $full_path_parts = explode('/', $path . $file);
                $i = count($full_path_parts) - 1;
                do {
                    if (ctype_digit($full_path_parts[$i])) {
                        $full_path_parts[$i] = $this->dumper->get_dict('Class_ID', $full_path_parts[$i]);
                    }
                    $i--;
                }
                while ($i && $full_path_parts[$i] !== 'class');

                return implode('/', $full_path_parts);

            case 'template':
                return nc_array_value($this->template_paths_dict, $path . $file);

            case 'multifile':
                return $path . $this->dumper->get_dict('Field_ID', $file);

            case 'userfiles':
                $new_file_folder = $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH . 'userfiles/' . nc_array_value($GLOBALS, 'AUTH_USER_ID', 1) . '/';
                $new_file_name = $this->get_available_file_name($new_file_folder, $file);
                $new_file_path = $new_file_folder . $new_file_name;

                $field_old_path = $nc_core->db->escape($meta['src']);
                $field_new_path = $nc_core->db->escape($new_file_path);

                foreach ((array)$meta['userfiles'] as $component_id => $component_data) {
                    $component_id = $this->dumper->get_dict('Class_ID', $component_id);
                    foreach ($component_data as $field_name => $object_ids) {
                        foreach ($object_ids as $object_id) {
                            $object_id = $this->dumper->get_dict("Message{$component_id}.Message_ID", $object_id);
                            $nc_core->db->query(
                                "UPDATE `Message$component_id`
                                    SET `$field_name` = REPLACE(`$field_name`, \"'$field_old_path'\", \"'$field_new_path'\")
                                  WHERE `Message_ID` = $object_id"
                            );
                            $nc_core->db->query(
                                "UPDATE `Message$component_id`
                                    SET `$field_name` = REPLACE(`$field_name`, '\"$field_old_path\"', '\"$field_new_path\"')
                                  WHERE `Message_ID` = $object_id"
                            );
                        }
                    }
                }
                return $new_file_path;

            case 'files':
                // Файлы сайтов, разделов (v.1.3+):
                $table = nc_array_value($meta, 'table');
                if ($table === 'Catalogue' || $table === 'Subdivision') {
                    return $this->update_system_table_file_path($path, $file, $meta);
                }

                $full_path = $path . $file;

                // Файлы ncSMO* в версии архивов 1.2:
                if (preg_match('@\.sub\.(\d+)(/[^/]+)?$@', $src)) {
                    $full_path = explode('/', $full_path);

                    $i = count($full_path) - 2;
                    $full_path[$i] = $this->dumper->get_dict('Subdivision_ID', $full_path[$i]);

                    return implode('/', $full_path);
                }

                // «Стандартная файловая система», «защищённая файловая система»:
                if (preg_match('@/(\d+)/(\d+)(/[^/]+)?$@', $full_path, $matches)) {
                    $full_path = explode('/', $full_path);

                    $i = count($full_path) - (isset($matches[3]) ? 2 : 1);
                    $full_path[$i] = $this->dumper->get_dict('Sub_Class_ID', $full_path[$i]);
                    $i--;
                    $full_path[$i] = $this->dumper->get_dict('Subdivision_ID', $full_path[$i]);

                    return implode('/', $full_path);
                }

                // «Простая файловая система»:
                // нужно убедиться, что файл находится в корне netcat_files
                $num_path_parts = substr_count($full_path, '/');
                if ($num_path_parts == 2 && preg_match('@^(preview_)?(\d+)_(\d+)(\.\w+)?$@', $file, $parts)) {
                    $old_field_id = $parts[2];
                    $new_field_id = $this->dumper->get_dict('Field_ID', $old_field_id);
                    $new_component_id = $this->get_component_id_by_field_id($new_field_id);
                    $new_object_id = $this->dumper->get_dict("Message{$new_component_id}.Message_ID", $parts[3]);

                    return $path . $parts[1] . $new_field_id . "_" . $new_object_id . nc_array_value($parts, 4, '');
                }

                return $full_path;

            case 'mixin_settings': // e.g.: '/netcat_files/mixin/Index/123/netcat_background/'
                $path_parts = explode('/', $path);
                if (count($path_parts) === 7 && is_numeric($path_parts[4])) {
                    $old_path = $path . $file;
                    $new_infoblock_id = $path_parts[4] = $this->dumper->get_dict('Sub_Class_ID', $path_parts[4]);
                    $new_path = implode('/', $path_parts) . $file;
                    $db = nc_db();
                    $db->query(
                        "UPDATE `Sub_Class`
                            SET `Index_Mixin_Settings` = REPLACE(
                                    `Index_Mixin_Settings`,
                                    '\"{$db->escape($old_path)}\"',
                                    '\"{$db->escape($new_path)}\"'
                                )
                          WHERE `Sub_Class_ID` = $new_infoblock_id"
                    );
                    return $new_path;
                }
                return $path . $file;

            default:
                return $path . $file;
        }
    }

    // ------------------------------------------------------------------------

    protected function update_system_table_file_path($path, $file, array $meta) {
        $table = $meta['table'];
        $id_field = $table . '_ID';
        $fs_type = $meta['fs_type'];

        if ($fs_type == NC_FS_SIMPLE) { // «простая ФС» для сайта: $field_$site.ext, $field_$sub.ext
            preg_match('@^(preview_)?(\d+)(_)(\d+)(\.\w+)?$@', $file, $file_parts);
            unset($file_parts[0]);
            $file_parts[2] = $this->dumper->get_dict('Field_ID', $file_parts[2]);
            $file_parts[4] = $this->dumper->get_dict($id_field, $file_parts[4]);
            $file = implode('', $file_parts);
        } else if ($table === 'Catalogue') { // «стандартная», «защищённая ФС»: c/file
            if (strpos($file, 'preview_') === 0) {
                $main_file_name = preg_replace('/^preview_/', '', $file);
                if (isset($this->renamed_files['c'][$main_file_name])) { // see the 'else' branch
                    $file = 'preview_' . $this->renamed_files['c'][$main_file_name];
                }
            } else {
                // проверить наличие конфликта имени файла с уже существующими файлами
                if ($fs_type == NC_FS_PROTECTED) {
                    $new_file = $this->get_protected_filesystem_file_name($path, $file);
                } else {
                    $new_file = nc_get_filename_for_original_fs($file, nc_core::get_object()->DOCUMENT_ROOT . $path);
                }

                if ($new_file !== $file) {
                    if ($fs_type == NC_FS_PROTECTED) {
                        $this->update_site_renamed_filetable_value($meta['field_name'], $new_file);
                    } else if ($fs_type == NC_FS_ORIGINAL) {
                        $this->update_site_renamed_file_field_value($meta['field_name'], $path, $new_file);
                    }
                    $this->renamed_files['c'][$file] = $new_file;
                    $file = $new_file;
                }
            }
        } else if ($table === 'Subdivision') { // «стандартная», «защищённая ФС» для раздела: $sub/file
            $path_parts = explode('/', $path); // e.g. "/netcat_files/123/"
            $path_parts[2] = $this->dumper->get_dict($id_field, $path_parts[2]);
            $path = implode('/', $path_parts);
        }

        return $path . $file;
    }

    // ------------------------------------------------------------------------

    /**
     * Обновляет значения файлового поля («стандартная ФС») у раздела, если название файла было изменено
     *
     * @param string $field_name
     * @param string $full_path
     * @param string $new_file_name
     */
    protected function update_site_renamed_file_field_value($field_name, $full_path, $new_file_name) {
        $raw_field_value = $this->site_table->where_id($this->new_id)->get_value($field_name);
        if (!$raw_field_value) {
            return;
        }

        $path = preg_replace('#^' . nc_core::get_object()->HTTP_FILES_PATH . '#', '/', $full_path);

        $raw_field_value_parts = explode(':', $raw_field_value);
        $raw_field_value_parts[3] = ltrim($path, '/') . $new_file_name;
        $raw_field_value = implode(':', $raw_field_value_parts);
        $this->site_table->where_id($this->new_id)->update(array($field_name => $raw_field_value));
    }

    // ------------------------------------------------------------------------

    protected function update_site_renamed_filetable_value($field_name, $new_file_name) {
        $nc_core = nc_core::get_object();
        $field_id = $nc_core->get_component('Catalogue')->get_field($field_name, 'id');
        nc_db_table::make('Filetable', 'ID')->where(array('Message_ID' => $this->new_id, 'Field_ID' => $field_id))
            ->update(array('Virt_Name' => $new_file_name));
    }

    // ------------------------------------------------------------------------

    protected function get_component_id_by_field_id($field_id) {
        static $cache = array();
        if (!isset($cache[$field_id])) {
            $cache[$field_id] = $this->field_table->where_id($field_id)->get_value('Class_ID');
        }
        return $cache[$field_id];
    }

    // ------------------------------------------------------------------------

    protected function export_named_entity($type, $id, $settings) {
        $backup = new nc_backup();
        $backup->export($type, $id, $settings);
    }

    // ------------------------------------------------------------------------

    protected function get_subdivision_file_field_names() {
        $nc_core = nc_core::get_object();
        return array_values(array_merge(
            $nc_core->get_component('Subdivision')->get_fields(NC_FIELDTYPE_FILE, false) +
            array('ncSMO_Image', 'ncImage', 'ncIcon')
        ));
    }

    // ------------------------------------------------------------------------

    protected function get_protected_filesystem_file_name($path, $file) {
        $nc_core = nc_core::get_object();
        while (file_exists($nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $path . $file)) {
            $file = md5(mt_rand() . date('H:i:s d.m.Y') . uniqid('', true));
        }
        return $file;
    }

    // ------------------------------------------------------------------------

    protected function get_available_file_name($path, $file) {
        $nc_core = nc_core::get_object();
        $file_parts = pathinfo($file);
        $file_name = $file_parts['filename'];
        $file_extension = $file_parts['extension'];
        while (file_exists($nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $path . $file)) {
            $file_name = preg_match('/_\d+$/', $file_name) ? ++$file_name : $file_name . '_1';
            $file = $file_name . ($file_extension ? ".$file_extension" : '');
        }
        return $file;
    }

}
