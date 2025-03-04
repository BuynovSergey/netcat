<?php

abstract class nc_backup_extension implements nc_backup_dumper_listener {

    /** @var nc_backup_dumper  */
    protected $dumper;

    /**
     * @param nc_backup_dumper $dumper
     */
    final public function __construct(nc_backup_dumper $dumper) {
        $this->dumper = $dumper;
    }

    /**
     * @param string $type
     * @param int $id
     */
    abstract public function export($type, $id);

    /**
     * @param string $type
     * @param int $id
     */
    abstract public function import($type, $id);

    /**
     * @param string $event
     * @param array $args
     * @return mixed
     */
    public function call_event($event, $args) {
        $method = 'event_' . $event;

        if (method_exists($this, $method)) {
            switch (count($args)) {
                case 0: return $this->$method();
                case 1: return $this->$method($args[0]);
                case 2: return $this->$method($args[0], $args[1]);
                case 3: return $this->$method($args[0], $args[1], $args[2]);
            }
        }

        return null;
    }

    /**
     * @param $classificator_name
     */
    protected function export_classificator($classificator_name) {
        $classificator_row = nc_db_table::make('Classificator')->where('Table_Name', $classificator_name)->index_by_id()->get_row();

        $export_settings = $this->dumper->get_export_settings();
        $named_entities_path = nc_array_value($export_settings, 'named_entities_path');

        if ($named_entities_path) {
            $export_settings = array_merge($export_settings, array(
                'remove_existing' => false, // чтобы не сбрасывалась дата
                'path' => $named_entities_path . '/lists/' . $classificator_name,
                'file_name_suffix' => $classificator_name,
            ));
            $backup = new nc_backup();
            $backup->export('classificator', $classificator_row['Classificator_ID'], $export_settings);

            $this->dumper->set_dump_info('required_lists', array_merge(
                (array)$this->dumper->get_dump_info('required_lists'),
                array($classificator_name => true)
            ));
        }
        else {
            $data = array($classificator_row['Classificator_ID'] => $classificator_row);
            $this->dumper->export_data('Classificator', 'Classificator_ID', $data);

            // Export data: Classificator_{Table_Name}
            $table_name = 'Classificator_' . $classificator_row['Table_Name'];
            $pk = $classificator_row['Table_Name'] . '_ID';

            $classificator_data_table = nc_db_table::make($table_name, $pk);
            $data = $classificator_data_table->get_result();

            $this->dumper->export_data($table_name, $pk, $data);

            // Export table: Classificator_{Table_Name}
            $this->dumper->export_table($table_name);
        }
    }

    /**
     * Для $row, содержащей Sub_Class_ID, определяет новое значение Message_ID
     * @param $row
     * @param $field
     * @return string
     */
     public function map_message_id_by_subclass_id($row, $field) {
         static $component_ids_by_infoblock = array();

         $message_id = $row[$field];
         $old_infoblock_id = $row['Sub_Class_ID'];
         $new_infoblock_id = (int)$this->dumper->get_dict('Sub_Class', $old_infoblock_id);

         if (!isset($component_ids_by_infoblock[$new_infoblock_id])) {
             $component_ids_by_infoblock[$new_infoblock_id] = nc_db()->get_var(
                 "SELECT `Class_ID`
                    FROM `Sub_Class`
                   WHERE `Sub_Class_ID` = $new_infoblock_id");
         }

         $new_component_id = $component_ids_by_infoblock[$new_infoblock_id];
         $new_message_id = $this->dumper->get_dict("Message{$new_component_id}", $message_id);

         return $new_message_id;
     }

}