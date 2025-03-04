<?php


class nc_backup_component extends nc_backup_base {

    //--------------------------------------------------------------------------

    protected $name = SECTION_CONTROL_CLASS;

    /** @var  nc_db_table */
    protected $class_table;
    /** @var  nc_db_table */
    protected $field_table;
    /** @var  nc_db_table */
    protected $mixin_preset_table;

    /** @var array список уже переименованных папок (ключ — путь к папке) */
    protected $renamed_folders = array();
    /** @var  string ключевое слово после импорта */
    protected $component_keyword;

    /**
     *
     */
    protected function init() {
        $this->class_table = nc_db_table::make('Class');
        $this->field_table = nc_db_table::make('Field');
        $this->mixin_preset_table = nc_db_table::make('Mixin_Preset');
    }

    /**
     *
     */
    protected function reset() {
        parent::reset();
        $this->renamed_folders = array();
        $this->component_keyword = null;
    }

    /**
     * @param $ids
     * @return array
     */
    protected function row_attributes($ids) {
        $titles = $this->class_table->select('Class_ID, Class_Name, Class_Group, File_Mode')->where_in_id((array)$ids)->index_by_id()->get_result();

        $result = array();
        foreach ($titles as $id => $row) {
            $fs = $row['File_Mode'] ? 1 : 0;
            $result[$id] = array(
                'title' => $row['Class_Group'] . ': ' . $row['Class_Name'],
                'sprite' => 'nc--dev-components',
                'netcat_link' => $this->nc_core->ADMIN_PATH . "class/index.php?fs={$fs}&phase=4&ClassID={$id}",
            );
        }

        return $result;
    }

    //--------------------------------------------------------------------------
    // EXPORT
    //--------------------------------------------------------------------------

    /**
     * @return mixed
     */
    protected function export_form() {
        $options = array('' => '');
        $options_v4 = array('' => '');

        $result = $this->class_table
            ->select('Class_ID, Class_Name, Class_Group, File_Mode')
            ->where('System_Table_ID', 0)->where('ClassTemplate', 0)
            ->order_by('File_Mode', 'DESC')->order_by('Class_Group')->order_by('Class_Name')
            ->as_object()->get_result();

        foreach ($result as $row) {
            $group = $row->Class_Group . ($row->File_Mode ? '' : ' (v4)');
            $options[$group][$row->Class_ID] = $row->Class_ID . '. ' . $row->Class_Name;
        }

        return $this->nc_core->ui->form->add_row(SECTION_CONTROL_CLASS)->select('id', $options);
    }

    /**
     * @return bool
     */
    protected function export_validation() {
        if (!$this->id) {
            $this->set_validation_error('Component not selected');
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function export_process() {
        $id = $this->id;
        $component = $this->class_table->where_id($id)->get_row();
        $table_name = $component['System_Table_ID'] ? 'User' : 'Message' . $id;

        if (!$component) {
            return false;
        }

        // Export data: Class
        $component_and_templates = array($id => $component);
        $component_and_templates += $this->class_table->where('ClassTemplate', $id)->index_by_id()->get_result();

        // Если параметр remove_existing == false, будет оставлена информация
        // о имеющихся подмакетах дизайна (для экспорта в репозиторий)
        $remove_existing = $this->dumper->get_export_settings('remove_existing', true);

        // Информация о каждом шаблоне будет сохранена в отдельный файл
        // (для экспорта или копирования в репозиторий)
        foreach ($component_and_templates as $row) {
            $row['Main_ClassTemplate_ID'] = 0;
            if ($component['Keyword']) {
                $this->dumper->set_export_settings(array('file_name_suffix' => $component['Keyword']));
                $filename = 'Class-' . $component['Keyword'];
                if ($row['ClassTemplate']) {
                    $filename .= "-" . ($row['Keyword'] ?: $row['Class_ID']);
                }
            }
            else {
                $filename = false;
            }
            $this->dumper->export_data('Class', 'Class_ID', $row, $filename);
            if ($row['Index_Mixin_Preset_ID'] > 0) {
                $mixin_preset = $this->mixin_preset_table->get_row($row['Index_Mixin_Preset_ID']);
                $this->dumper->export_data('Mixin_Preset', 'Mixin_Preset_ID', $mixin_preset);
            }
        }

        $keyword_map = array();
        if ($component['Keyword']) {
            $keyword_map = $remove_existing ? array() : $this->dumper->get_dump_info('keywords');
            foreach ($component_and_templates as $row) {
                $keyword_map['Class'][trim($row['File_Path'], '/')] = $row['Class_ID'];
            }
        }

        // Export files
        if ($component['File_Mode']) {
            foreach ($component_and_templates as $component_template) {
                // Убираем последнюю часть пути к шаблону компонента:
                $component_path_parts = explode('/', rtrim($component_template['File_Path'], '/'));
                $last_fragment = array_pop($component_path_parts);
                $folder = join('/', $component_path_parts);
                $this->dumper->export_files(nc_core('HTTP_TEMPLATE_PATH') . 'class' . $folder, $last_fragment, false);
            }

            // Export extra folders (not belonging to any template of this component)
            $this->export_extra_folders();
        }

        // Export data: Field
        $field_data = $component['System_Table_ID']
            ? $this->field_table->where('System_Table_ID', $component['System_Table_ID'])->get_result()
            : $this->field_table->where('Class_ID', $id)->get_result();

        $this->dumper->export_data('Field', 'Field_ID', $field_data);
        foreach ($field_data as $row) {
            $keyword_map['Field'][$component['Keyword'] ?: $id][$row['Field_Name']] = $row['Field_ID'];
        }

        //        if ($component['System_Table_ID']) {
        //            $result = nc_db()->get_results("SHOW FIELDS FROM {$table_name} WHERE System_Table_ID = " . $component['System_Table_ID'], ARRAY_A);
        //            $this->dumper->set_dump_info('table_fields', array($table_name => $result));
        //        }

        // Export `Message` table
        $this->dumper->set_dump_info('table', array());
        $this->dumper->export_table($table_name);
        $this->dumper->set_dump_info('keywords', $keyword_map);

        return true;
    }

    //--------------------------------------------------------------------------
    // IMPORT
    //--------------------------------------------------------------------------

    /**
     * @throws Exception
     */
    protected function import_process() {
        $this->dumper->import_data('Mixin_Preset');
        $this->dumper->import_data('Class');
        $this->dumper->import_data('Field');

        if ($this->dumper->get_dump_info('table', 'User')) {
            $this->dumper->import_table_fields('User');
        }

        $this->new_id = $this->dumper->get_dict('Class_ID', $this->id);
        $this->dumper->import_table('Message' . $this->id, 'Message' . $this->new_id);

        $this->check_message_table_structure($this->new_id);

        $this->renamed_folders = array();
        $this->dumper->import_files();

        nc_core::get_object()->component->update_cache_for_multipurpose_templates();

        $file_mode = nc_db_table::make('Class')->where_id($this->new_id)->get_value('File_Mode');

        $this->dumper->set_import_result('link', $this->nc_core->ADMIN_PATH . '#dataclass' . ($file_mode ? '_fs' : '') . '.edit(' . $this->new_id . ')');
        $this->dumper->set_import_result('redirect', $this->nc_core->ADMIN_PATH . "class/index.php?fs={$file_mode}&phase=4&ClassID=" . $this->new_id);
    }

    /**
     * @param $row
     * @return mixed
     */
    protected function event_before_insert_class($row) {
        if ($row['ClassTemplate']) {
            $row['ClassTemplate'] = $this->dumper->get_dict('Class_ID', $row['ClassTemplate']);
        }

        if (!$row['ClassTemplate'] && isset($row['Keyword']) && $row['Keyword']) {
            $is_duplicate_keyword = $this->class_table
                ->where('ClassTemplate', 0)
                ->where('Keyword', $row['Keyword'])
                ->count_all();

            if ($is_duplicate_keyword) {
                $row['Keyword'] = '';
            }
            $this->component_keyword = $row['Keyword'];
        }

        return $row;
    }

    /**
     * @param $row
     * @param $insert_id
     */
    protected function event_after_insert_class($row, $insert_id) {
        // Обновляем путь к директории с файлами шаблона
        if ($row['ClassTemplate']) {
            $parent_folder = '/' . ($this->component_keyword ?: $row['ClassTemplate']);
        }
        else {
            $parent_folder = '';
        }

        $update = array('File_Path' => $parent_folder . '/' . ($row['Keyword'] ?: $insert_id) . '/');
        $this->class_table->where_id($insert_id)->update($update);
    }

    // // Меняем Class_ID для полей компонента
    // protected function event_before_insert_field($row) {
    //     $row['Class_ID'] = $this->dumper->get_dict('Class_ID', $row['Class_ID']);
    //     return $row;
    // }

    /**
     * @param $path
     * @param $file
     * @param $src
     * @return string
     */
    protected function event_before_copy_file($path, $file, $src) {
        // переименовываем основную папку
        $full_path_parts = explode('/', $path . $file);  // → ['', 'netcat_template', 'class', 123, ...]
        $i = count($full_path_parts) - 1;
        do {
            if (ctype_digit($full_path_parts[$i])) {
                $full_path_parts[$i] = $this->dumper->get_dict('Class_ID', $full_path_parts[$i]);
            }
            elseif ($i == 3) {
                $full_path_parts[$i] = $this->component_keyword ?: $this->new_id;
            }

            $i--;
        }
        while ($i && $full_path_parts[$i] !== 'class');

        $path = implode('/', $full_path_parts);

        $nc_core = nc_core::get_object();
        $absolute_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $path;
        $this->renamed_folders[$absolute_path] = true;

        return $path;
    }

    /**
     * @param $path
     */
    protected function event_after_copy_file($path) {
        // переименовываем папки шаблонов (формат экспорта 1.0/pre, шаблоны внутри основной папки)
        if (isset($this->renamed_folders[$path])) {
            return;
        }

        foreach (scandir($path) as $file) {
            if (ctype_digit($file) && is_dir($path . '/' . $file)) {
                if ($new_file = $this->dumper->get_dict('Class_ID', $file, false)) {
                    rename($path . '/' . $file, $path . '/' . $new_file);
                }
            }
        }
    }

    /**
     * @param $row
     * @return bool
     */
    protected function event_before_insert_field($row) {
        // skip existing fields of the 'user' component
        if ($row['System_Table_ID']) {
            $existing_field_id = $this->field_table
                ->where('Class_ID', $row['Class_ID'])
                ->where('System_Table_ID', $row['System_Table_ID'])
                ->where('Field_Name', $row['Field_Name'])
                ->get_value('Field_ID');

            if ($existing_field_id) {
                $this->dumper->set_dict('Field_ID', $row['Field_ID'], $existing_field_id);
                return false;
            }
        }

        return $row;
    }

    /**
     * Добавляет в архив папки, не относящиеся к шаблонам компонентов
     * (это могут быть assets — JS, изображения и т. п.)
     * @return void
     */
    protected function export_extra_folders() {
        $nc_core = nc_core::get_object();
        $id = $this->id;
        $db = $nc_core->db;
        $all_template_folders = $db->get_col("SELECT IF(`Keyword` != '', `Keyword`, `Class_ID`) FROM `Class` WHERE `ClassTemplate` = $id");
        $component_folder = rtrim($nc_core->CLASS_TEMPLATE_FOLDER, '/') . $this->class_table->where_id($id)->get_value('File_Path');
        foreach ((array)glob($component_folder . '*', GLOB_ONLYDIR) as $path) {
            if ($path && !in_array(basename($path), $all_template_folders)) {
                $relative_path = substr($path, strlen($nc_core->DOCUMENT_ROOT));
                $this->dumper->export_files($relative_path, null, true);
            }
        }
    }

}