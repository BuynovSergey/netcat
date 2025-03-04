<?php


class nc_backup_widget_class extends nc_backup_base {

    //--------------------------------------------------------------------------

    protected $name = SECTION_INDEX_DEV_WIDGET;
    /** @var  nc_db_table */
    protected $widget_class_table;
    /** @var  nc_db_table */
    protected $field_table;

    //-------------------------------------------------------------------------

    protected function init() {
        $this->widget_class_table = nc_db_table::make('Widget_Class');
        $this->field_table        = nc_db_table::make('Field');
    }

    //-------------------------------------------------------------------------

    protected function row_attributes($ids) {
        $titles = $this->widget_class_table->where_in_id((array)$ids)->get_list('Name');

        $result = array();
        foreach ($titles as $id => $title) {
            $result[$id] = array(
                'title'       => $title,
                'sprite'      => 'nc--dev-com-widgets',
                'netcat_link' => $this->nc_core->ADMIN_PATH . "widget/index.php?fs=1&phase=30&widgetclass_id={$id}"
            );
        }

        return $result;
    }

    //--------------------------------------------------------------------------
    // EXPORT
    //--------------------------------------------------------------------------

    public function export_form() {
        $options = array();
        $result  = $this->widget_class_table
            ->select('Widget_Class_ID, Category, Name, File_Mode')
            ->order_by('File_Mode', 'DESC')->order_by('Category')->order_by('Name')
            ->as_object()->get_result();

        foreach ($result as $row) {
            $suffix = ($row->File_Mode ? '' : ' (v4)');
            $group  = $row->Category . $suffix;

            $options[$group][$row->Widget_Class_ID] = str_replace($row->Category . ': ', '', $row->Name);
        }

        return $this->nc_core->ui->form->add_row(WIDGET_ADD_WK)->select('id', $options);
    }

    //-------------------------------------------------------------------------

    protected function export_validation() {
        if (!$this->id) {
            $this->set_validation_error('Widget Class not selected');
            return false;
        }
        return true;
    }

    //--------------------------------------------------------------------------

    protected function export_process() {

        $id           = $this->id;
        $widget_class = $this->widget_class_table->where_id($id)->get_row();

        if (!$widget_class) {
            return false;
        }

        // Export data: Class
        $data  = array($id => $widget_class);
        $this->dumper->export_data('Widget_Class', 'Widget_Class_ID', $data);


        // Export data: Field
        $data = $this->field_table->where('Widget_Class_ID', $id)->get_result();
        $this->dumper->export_data('Field', 'Field_ID', $data);

        // Export files
        if ($widget_class['File_Mode']) {
            $this->dumper->export_files(nc_core('HTTP_TEMPLATE_PATH') . 'widget', $widget_class['File_Path']);
        }

        $this->dumper->set_dump_info('widget_class_keyword', $widget_class['Keyword']);
        $this->dumper->set_dump_info('file_mode', $widget_class['File_Mode']);

        return true;
    }

    //--------------------------------------------------------------------------
    // IMPORT
    //--------------------------------------------------------------------------

    protected function import_validation() {
        $keyword = $this->dumper->get_dump_info('widget_class_keyword');

        $exists  = $this->widget_class_table->where('Keyword', $keyword)->count_all();

        if ($exists) {
            $this->set_validation_error("Widget class: \"{$keyword}\" - already exists");
            return false;
        }

        return true;
    }

    //-------------------------------------------------------------------------

    protected function import_process() {
        $file_mode = $this->dumper->get_dump_info('file_mode') ? '1' : '0';
        $this->dumper->import_data('Widget_Class');

        $this->new_id = $this->dumper->get_dict('Widget_Class_ID', $this->id);

        $this->dumper->import_data('Field');

        $this->dumper->import_files();

        $this->dumper->set_import_result('link', $this->nc_core->ADMIN_PATH . '#widgetclass'.($file_mode?'_fs':'').'.edit(' . $this->new_id . ')');
        $this->dumper->set_import_result('redirect', $this->nc_core->ADMIN_PATH . "widget/index.php?fs='.$file_mode.'&phase=30&widgetclass_id=" . $this->new_id);
    }

    //--------------------------------------------------------------------------

    protected function event_before_insert_field($row) {
        $row['Widget_Class_ID'] = $this->dumper->get_dict('Widget_Class_ID', $row['Widget_Class_ID']);
        return $row;
    }

}