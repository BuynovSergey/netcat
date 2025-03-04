<?php class_exists('nc_system') OR die('Unable to load file');



class nc_data_source {


    protected $value = array();

    /** @var nc_db */
    private $db;

    //-------------------------------------------------------------------------

    public function __construct($value = null) {

        $this->db = nc_core()->db;

        if ($value) {
            $this->set_value($value);
        }

    }

    //-------------------------------------------------------------------------

    public function get_value() {
        return $this->value;
    }

    //-------------------------------------------------------------------------

    public function set_value($value) {
        if (is_string($value)) {
            if ($value[0] == '{') {
                $value = (array)json_decode($value);
            } else {
                $value = unserialize($value);
            }
        }

        $this->value = (array) $value;
    }

    //-------------------------------------------------------------------------

    public function set_option($key, $val) {
        $this->value[$key] = $val;
    }

    //-------------------------------------------------------------------------

    // public function set_ordering($order_by) {
    //     $this->value['ordering'] = $order_by;
    // }

    // //-------------------------------------------------------------------------

    // public function set_limit($order_by) {
    //     $this->value['ordering'] = $order_by;
    // }

    //-------------------------------------------------------------------------

    public function get_data() {
        return $this->get_data_from_source($this->value);


        $data = array();

        foreach ($this->value as $source) {
            $data = array_merge($data, $this->get_data_from_source($source));
        }

        return (array) $data;
    }

    //-------------------------------------------------------------------------

    // public function make_admin_field($name) {
    //     $view = nc_core()->ui->view(dirname(__FILE__) . '/views/admin_field.view.php');

    //     $view->with('field_name', $name);

    //     return $view;
    // }

    //-------------------------------------------------------------------------

    protected function bind_fields(&$data, $bindings) {
        foreach ($bindings as $field => $alias) {
            foreach ($data as &$row) {
                $row[$alias] = $row[$field];
            }
        }

    }

    //-------------------------------------------------------------------------

    protected function get_data_from_source($source) {
        $data = array();

        if (!empty($source['class_id'])) {
            $table = nc_db_table::make('Message' . (int)$source['class_id'], 'Message_ID');

            if (!empty($source['subclass_id'])) {
                $table->where('Sub_Class_ID', $source['subclass_id']);
            }

            if ($source['order_by']) {
                $table->raw('order_by', $source['order_by']);
            }

            if ($source['where']) {
                $table->where($source['where']);
            }

            if ($source['limit']) {
                $table->limit($source['limit']);
            }

            $data = $table->get_result();

            if ($source['bindings']) {
                $this->bind_fields($data, $source['bindings']);
            }

            $message_ids = array();
            foreach ($data as $i => $row) {
                $message_ids[] = $row['Message_ID'];
            }

            $full_links = array();
            # основной запрос для построения пути
            $result = nc_db()->get_results("SELECT  m.`Message_ID`, CONCAT(sub.`Hidden_URL`, IF(m.`Keyword` <> '', m.`Keyword`, CONCAT(cc.`EnglishName`, '_', m.`Message_ID`)), '.html') AS fullLink
                      FROM `Message" . $source['class_id'] . "` AS m
                      LEFT JOIN `Subdivision` AS sub
                      ON m.`Subdivision_ID` = sub.`Subdivision_ID`
                      LEFT JOIN `Sub_Class` AS cc
                      ON m.`Sub_Class_ID` = cc.`Sub_Class_ID`
                      WHERE m.`Message_ID` IN (" . implode(',', $message_ids) . ")", ARRAY_A);

            foreach ($result as $row) {
                $full_links[$row['Message_ID']] = $row['fullLink'];
            }

            $result = array();
            foreach ($data as $i => $row) {
                foreach ($row as $k => $v) {
                    $result[$i]['f_' . $k] = $v;
                }

                $result[$i]['f_RowID'] = $row['Message_ID'];
                $result[$i]['fullLink'] = nc_get_scheme() . '://' . $_SERVER['HTTP_HOST'] . $full_links[$row['Message_ID']];
                // $result[$i]['fullLink'] = 'http://' . $_SERVER['HTTP_HOST'] . nc_message_link($row['Message_ID'], $source['class_id']);
            }
        }

        return (array) $result;
    }

    //-------------------------------------------------------------------------
}