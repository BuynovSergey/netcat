<?php

class nc_a2f_field_select_classificator extends nc_a2f_field_select {

    protected $classificator;

    public function get_extend_parameters() {
        return array('classificator' => array('type' => 'classificator', 'caption' => NETCAT_CUSTOM_EX_CLASSIFICATOR));
    }

    protected function load_classificator() {
        static $cache = array();

        if (!isset($cache[$this->classificator])) {
            $db = nc_db();

            $clft = $db->escape($this->classificator);
            $res = $db->get_row("SELECT * FROM `Classificator` WHERE `Table_Name` = '" . $clft . "' ", ARRAY_A);

            if (!$res) {
                return sprintf(NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR, $this->classificator);
            }

            switch ($res['Sort_Type']) {
                case 1:
                    $sort = "`" . $clft . "_Name`";
                    break;
                case 2:
                    $sort = "`" . $clft . "_Priority`";
                    break;
                default:
                    $sort = "`" . $clft . "_ID`";
            }

            // просто узнаем элементы списка
            $elements = $db->get_results("SELECT `" . $clft . "_ID` as `id`, `" . $clft . "_Name` as `name`
                   FROM `Classificator_" . $clft . "`
                   WHERE `Checked` = '1'
                   ORDER BY " . $sort . " " . ($res['Sort_Direction'] == 1 ? "DESC" : "ASC") . "", ARRAY_A);

            if (!$elements) {
                return sprintf(NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR_EMPTY, $res['Classificator_Name']);
            }

            $cache[$this->classificator] = $elements;
        }

        foreach ($cache[$this->classificator] as $v) {
            $this->values[$v['id']] = $v['name'];
        }

        return true;
    }

    public function render_value_field($html = true) {
        $loading_result = $this->load_classificator();
        if ($loading_result !== true) { return $loading_result; }

        // сама прорисовка реализована в родительском класса
        return parent::render_value_field($html);
    }

}