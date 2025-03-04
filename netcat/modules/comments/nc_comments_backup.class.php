<?php

/**
 *
 */
class nc_comments_backup extends nc_backup_extension {

    /**
     * @param string $type
     * @param int $id
     */
    public function export($type, $id) {
        if ($type != 'site') { return; }

        $infoblock_ids = $this->dumper->get_dict('Sub_Class_ID');

        // Comments_Count
        $comments_count = nc_db_table::make('Comments_Count')->where_in('Sub_Class_ID', $infoblock_ids)->get_result();
        $this->dumper->export_data('Comments_Count', 'id', $comments_count);

        // Comments_LastVisit

        // Comments_Rules
        $comments_rules = nc_db_table::make('Comments_Rules')->where('Catalogue_ID', $id)->get_result();
        $this->dumper->export_data('Comments_Rules', 'ID', $comments_rules);

        // Comments_Subscribe
        // $comments_subscriptions = nc_db_table::make('Comments_Subscribe')->where('Catalogue_ID', $id)->get_result();
        // $this->dumper->export_data('Comments_Subscribe', 'ID', $comments_subscriptions);

        // Comments_Template + files: netcat_template/module/comments/$id/*
        // (E.g.: $nc_comments->wall($nc_comments_object_id, 2) IN TEMPLATES)
        $comment_wall_templates = $this->find_comment_wall_templates($id);

        if ($comment_wall_templates) {
            $templates = nc_db_table::make('Comments_Template')
                                ->where_in('ID', array_keys($comment_wall_templates))
                                ->get_result();
            $this->dumper->export_data('Comments_Template', 'ID', $templates);
            $this->dumper->set_dump_info('comments_templates', $comment_wall_templates);

            $template_path = nc_core('HTTP_TEMPLATE_PATH') . 'module/comments';
            foreach ($comment_wall_templates as $comment_template_id => $component_template_ids) {
                $this->dumper->export_files($template_path, $comment_template_id);
            }
        }

        // Comments_Text
        $all_infoblocks = $this->dumper->get_dict('Sub_Class_ID');
        $comments_text = nc_db_table::make('Comments_Text')->where_in('Sub_Class_ID', $all_infoblocks)->get_result();
        $this->dumper->export_data('Comments_Text', 'id', $comments_text);

    }

    /**
     * @param string $type
     * @param int $id
     */
    public function import($type, $id) {
        if ($type != 'site') { return; }

        $map_message_id = array('Message_ID' => array($this, 'map_message_id_by_subclass_id'));

        $this->dumper->import_data('Comments_Count', null, $map_message_id);
        $this->dumper->import_data('Comments_Rules', null, $map_message_id);

        //update subdivisions and sub_classes Comment_Rule_ID columns
        $comments_rules = (array)nc_db_table::make('Comments_Rules')
            ->as_array()
            ->get_result();

        foreach($comments_rules as $comments_rule) {
            $rule_id = $comments_rule['ID'];
            $rule_subdivision_id = $comments_rule['Subdivision_ID'];
            $rule_sub_class_id = $comments_rule['Sub_Class_ID'];

            if ($rule_sub_class_id) {
                $sql = "UPDATE `Sub_Class` SET `Comment_Rule_ID` = {$rule_id} WHERE `Sub_Class_ID` = {$rule_sub_class_id}";
            } else{
                $sql = "UPDATE `Subdivision` SET `Comment_Rule_ID` = {$rule_id} WHERE `Subdivision_ID` = {$rule_subdivision_id}";
            }
            nc_db()->query($sql);
        }

        // $this->dumper->import_data('Comments_Subscribe', null, $map_message_id);
        $this->dumper->import_data('Comments_Text', null, $map_message_id);

        $templates = $this->dumper->get_dump_info('comments_templates');

        if ($templates) {
            $this->dumper->import_data('Comments_Template');
            // see also: event_before_copy_file()
            $this->dumper->import_files(array($this->get_template_path_prefix()));

            // Modify templates...
            foreach ($templates as $comment_template => $component_templates) {
                foreach ($component_templates as $component_template) {
                    $new_component_template = $this->dumper->get_dict('Class_ID', $component_template);
                    $file_to_check = 'RecordTemplateFull.html';
                    $path_prefix = rtrim(nc_core::get_object()->CLASS_TEMPLATE_FOLDER, '/\\');
                    $template_path = nc_db_table::make('Class')
                                        ->where('Class_ID', $new_component_template)
                                        ->where('File_Mode', 1)
                                        ->index_by('Class_ID')
                                        ->get_value('File_Path');

                    if (!$template_path) {
                        continue;
                    }

                    $file_path = $path_prefix . $template_path . $file_to_check;

                    $template = file_get_contents($file_path);
                    $result_template = $template;

                    if (preg_match_all("/->wall\(([^,)]+\s*,\s*)(\d+)(.*)\)/", $template, $matches)) {
                        foreach ($matches[0] as $i => $search) {
                            $replace = "->wall(" . $matches[1][$i] .
                                       $this->dumper->get_dict("Comments_Template.ID", $matches[2][$i]) .
                                       $matches[3][$i] . ")";
                            $result_template = str_replace($search, $replace, $result_template);
                        }

                        if ($result_template != $template) {
                            file_put_contents($file_path, $template);
                        }
                    }

                }
            }
        }
    }

    /**
     * @param $path
     * @param $file
     * @param $src
     * @return string new file path
     */
    public function event_before_copy_file($path, $file, $src) {
        if (strpos($path, $this->get_template_path_prefix()) === false) {
            return false;
        }

        return $path . $this->dumper->get_dict("Comments_Template.ID", $file);
    }

    /**
     * @return string
     */
    protected function get_template_path_prefix() {
        return nc_core('HTTP_TEMPLATE_PATH') . 'module/comments';
    }


    /**
     * Смотрит шаблоны компонентов, извлекает из вызова метода wall() (у любых объектов)
     * второй параметр, если он является числом. Возвращает массив, где ключи —
     * найденные числа, значения — массив с ID шаблонов компонентов, где они
     * встречаются.
     * @param int $exported_site_id
     * @return array   comment_template_id => array(component_template_id,...)
     */
    protected function find_comment_wall_templates($exported_site_id) {
        $comment_templates = array();

        $component_template_ids = $this->dumper->get_dict('Class_ID');

        $nc_core = nc_core::get_object();
        $file_to_check = 'RecordTemplateFull.html';
        $path_prefix = rtrim($nc_core->CLASS_TEMPLATE_FOLDER, '/\\');
        $template_paths = nc_db_table::make('Class')
                            ->where('File_Mode', 1)
                            ->where_in('Class_ID', $component_template_ids)
                            ->or_where_in('ClassTemplate', $component_template_ids) // т. о. просмтариваются все шаблоны используемых компонентов...
                            ->index_by('Class_ID')
                            ->get_list('File_Path');
        if (!$template_paths) { return $comment_templates; }

        foreach ($template_paths as $template_id => $template_path) {
            $template = file_get_contents($path_prefix . $template_path . $file_to_check);
            if (strpos($template, '->wall(')) {
                if (preg_match_all('/->wall\([^,)]+\s*,\s*(\d+).*\)/', $template, $matches)) {
                    foreach ($matches[1] as $comment_template_id) {
                        $comment_templates[$comment_template_id][] = $template_id;
                    }
                }
            }
        }

        return $comment_templates;
    }
}