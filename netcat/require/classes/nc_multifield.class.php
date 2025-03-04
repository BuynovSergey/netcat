<?php

/**
 * @property nc_multifield_settings $settings
 * @property nc_multifield_template $template
 * @property nc_multifield_file[] $records
 * @property string $name
 * @property string $desc
 * @property string $format
 * @property int $id
 * @property array $format_parsed
 */
class nc_multifield {

    private $settings = null;
    private $template = null;
    private $records = array();
    private $name = null;
    private $id = null;
    private $desc = null;
    private $format;
    private $component_id = null;

    public function __construct($name, $desc = null, $format = null, $field_id = null, $component_id = null) {
        $this->settings = new nc_multifield_settings($this);
        $this->template = new nc_multifield_template($this);
        $this->name = $name;
        $this->desc = $desc;
        $this->format = $format;
        $this->id = $field_id;
        $this->component_id = $component_id;

        require_once(nc_Core::get_object()->INCLUDE_FOLDER . '../admin/class.inc.php');

        $parsed_format = nc_field_parse_resize_options($this->format);
        // разные названия в формате поля и в классе nc_multifield_settings...
        $parsed_format['min'] = $parsed_format['multifile_min'];
        $parsed_format['max'] = $parsed_format['multifile_max'];
        $this->settings->set_values_from_array($parsed_format);
    }

    /**
     * Возвращает ID компонента или название системной таблицы
     * @return int|null
     */
    public function get_component_id() {
        return $this->component_id;
    }

    /**
     * Устанавливает список файлов в поле
     * @param array|null $data  двумерный массив, или массив stdClass, или массив nc_multifield_file
     * @return $this
     */
    public function set_data(array $data = null) {
        $this->records = array();
        foreach ((array)$data as $record) {
            $this->add_record($record);
        }
        return $this;
    }

    /**
     * Возвращает данные записи по ID в таблице Multifield
     * @param $id
     * @return nc_multifield_file|null
     */
    public function get_record_data_by_id($id) {
        foreach ((array)$this->records as $record) {
            if ($record->ID == $id) {
                return $record;
            }
        }
        return null;
    }

    /**
     * Добавляет запись о файле в поле
     * @param array|stdClass|nc_multifield_file $record
     */
    public function add_record($record) {
        if (!($record instanceof nc_multifield_file)) {
            $data = (array)$record;
            $record = new nc_multifield_file();
            $record->set_values_from_database_result($data)->set_multifield($this);
        }
        $this->records[] = $record;
    }
    
    public function set_template($template) {
        $this->template->set($template);
        return $this;
    }

    public function to_array() {
        return $this->records;
    }

    public function set_description($desc) {
        $this->desc = htmlspecialchars($desc);
    }

    public function form() {
        return $this->template->get_form();
    }

    public function get_record($record_num = 1) {
        $multifield = new self($this->name, $this->desc, $this->format, $this->id, $this->component_id);
        return $multifield->set_data(array($this->records[+$record_num - 1]))->template->set($this->template->template)->get_html();
    }

    public function get_random_record() {
        return $this->get_record(mt_rand(1, $this->count()));
    }

    public function count() {
        return count($this->records);
    }

    public function __toString() {
        return $this->template->get_html();
    }

    public function __get($name) {
        return isset($this->$name) ? $this->$name : false;
    }
}