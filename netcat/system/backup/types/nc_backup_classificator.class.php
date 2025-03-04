<?php


class nc_backup_classificator extends nc_backup_base {

    //--------------------------------------------------------------------------

    protected $name = CONTENT_CLASSIFICATORS;

    /** @var  nc_db_table */
    protected $classificator_table;

    //--------------------------------------------------------------------------

    protected function init() {
        $this->classificator_table = nc_db_table::make('Classificator');
    }

    //-------------------------------------------------------------------------

    protected function row_attributes($ids) {
        $titles = $this->classificator_table->select('Classificator_ID, Classificator_Name')->where_in_id((array)$ids)->index_by_id()->get_result();

        $result = array();
        foreach ($titles as $id => $row) {
            $result[$id] = array(
                'title'       => $row['Classificator_Name'],
                'sprite'      => 'nc--dev-classificator',
                'netcat_link' => $this->nc_core->ADMIN_PATH . "classificator.php?phase=4&ClassificatorID={$id}"
            );
        }

        return $result;
    }

    //--------------------------------------------------------------------------
    // EXPORT
    //--------------------------------------------------------------------------

    public function export_form() {
        $options = $this->classificator_table->order_by('Classificator_Name')->get_list('Classificator_Name');

        return $this->nc_core->ui->form->add_row(CONTENT_CLASSIFICATORS_NAMEONE)->select('id', $options);
    }

    //-------------------------------------------------------------------------

    protected function export_validation() {
        if (!$this->id) {
            $this->set_validation_error('Classificator not selected');
            return false;
        }
        return true;
    }

    //--------------------------------------------------------------------------

    protected function export_process() {
        $id            = $this->id;
        $classificator = $this->classificator_table->where_id($id)->get_row();

        if (!$classificator) {
            return false;
        }

        // Export data: Classificator
        $data = array($id => $classificator);
        $this->dumper->export_data('Classificator', 'Classificator_ID', $data);

        // Export data: Classificator_{Table_Name}
        $table = 'Classificator_' . $classificator['Table_Name'];
        $pk    = $classificator['Table_Name'] . '_ID';

        $classificator_data_table = nc_db_table::make($table, $pk);
        $data = $classificator_data_table->get_result();
        $this->dumper->export_data($table, $pk, $data);

        // Export table: Classificator_{Table_Name}
        $this->dumper->export_table($table);

        $this->dumper->set_dump_info('classificator_table', $classificator['Table_Name']);

        return true;
    }

    //--------------------------------------------------------------------------
    // IMPORT
    //--------------------------------------------------------------------------

    protected function import_validation() {
        $table_name = $this->dumper->get_dump_info('classificator_table');
        $exists     = $this->classificator_table->where('Table_Name', $table_name)->count_all();

        if ($exists) {
            $this->set_validation_error("Classificator: \"{$table_name}\" - already exists");
            return false;
        }

        return true;
    }

    //-------------------------------------------------------------------------

    protected function import_process() {
        $this->dumper->import_data('Classificator');

        $this->new_id = $this->dumper->get_dict('Classificator_ID', $this->id);

        $table = 'Classificator_' . $this->dumper->get_dump_info('classificator_table');

        $this->dumper->import_table($table);

        $this->dumper->import_data($table);

        $this->dumper->set_import_result('link', $this->nc_core->ADMIN_PATH . '#classificator.edit(' . $this->new_id . ')');
        $this->dumper->set_import_result('redirect', $this->nc_core->ADMIN_PATH . "classificator.php?phase=4&ClassificatorID=" . $this->new_id);
    }

    //--------------------------------------------------------------------------
}