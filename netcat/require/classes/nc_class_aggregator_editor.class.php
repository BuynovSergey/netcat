<?php

class nc_class_aggregator_editor {
    public $ignore_catalogue = false;
    public $catalogue_id = null;
    
    private $settings = array();
    private $class_aggregator_setting = null;

    private function __construct(nc_class_aggregator_setting $settings) {
        $this->class_aggregator_setting = $settings;
        $this->settings = $this->class_aggregator_setting->get_complete_settings();
        $this->ignore_catalogue = $this->class_aggregator_setting->ignore_catalogue;
    }

    public function get_message_select_area($prefix, array $prefix_attrs = array(), array $select_attrs = array(), array $option_attrs = array()) {
        $nc_core = nc_Core::get_object();

        $data = $this->attrs_array2data($select_attrs, "nc_select_attrs");
        $data .= $data ? '&' : '';
        $data .= $this->attrs_array2data($option_attrs, "nc_option_attrs");

        return "
        <div " . $this->attrs_array2str($prefix_attrs) ." >
            $prefix
            <div id='nc_message_select'>
            </div>
        </div>

        <script type='text/javascript'>
            function nc_message_select_load(selected) {
                var Class_ID = \$nc('#db_Class_ID').val();
                var cc = \$nc('#adminForm input[name=cc]').val();
                \$nc.ajax({
                    type: 'POST',
                    url: '{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}add.php?db_Class_ID=' + Class_ID + '&nc_get_message_select=1&cc=' + cc + '&db_selected=' + selected,
                    data: '$data',
                    success: function(response) { \$nc('#nc_message_select').html(response); }});
            }

            setTimeout(function() {
                    nc_message_select_load(\$nc('input[name=db_Message_ID]').val());
                    \$nc('#db_Class_ID').change(function() {
                            nc_message_select_load();
                    });
            }, 250);

         </script>";
    }

    public function get_class_select(array $select_attrs = array(), array $option_attrs = array(), $selected = null) {
        return $this->get_select('db_Class_ID', $this->get_options($this->get_class_ids_and_name_by_class_ids(array_keys($this->settings)), $option_attrs, $selected), $select_attrs);
    }

    public function get_message_select($Class_ID, array $select_attrs = array(), array $option_attrs = array(), $selected = null) {
        return $this->get_select('db_'.($_GET['nc_is_parent'] ? 'Parent_' : '').'Message_ID', $this->get_options($this->get_message_ids_by_class_id($Class_ID), $option_attrs, $selected), $select_attrs);
    }

    private function get_message_ids_by_class_id($Class_ID) {
        $field_as_name = $this->class_aggregator_setting->classes($Class_ID)->get_field_as_message_name();
        
        $SQL_SELECT = 'SELECT ' . ($field_as_name ? "CONCAT(Message_ID, ' ', $field_as_name)" : 'Message_ID');
        $SQL_FROM = " FROM Message$Class_ID as m";
        $SQL_WHERE = " WHERE 1";
        
        if (!$this->ignore_catalogue && $this->catalogue_id) {
            $SQL_FROM .= ', Sub_Class as sc';
            $SQL_WHERE .= ' AND m.Sub_Class_ID = sc.Sub_Class_ID AND sc.`Catalogue_ID` = ' . +$this->catalogue_id;
        }
        
        $SQL = $SQL_SELECT . $SQL_FROM . $SQL_WHERE;
        return (array) nc_Core::get_object()->db->get_col($SQL);
    }

    private function get_class_ids_and_name_by_class_ids($Class_IDs) {
        $SQL = "SELECT CONCAT(Class_ID, ' ', Class_Name) FROM Class WHERE Class_ID IN (".join(', ', (array) $Class_IDs).")";
        return (array) nc_Core::get_object()->db->get_col($SQL);
    }

    private function get_options(array $values = array(), array $option_attrs = array(), $selected = null) {
        $attrs = $this->attrs_array2str($option_attrs);

        $options = array();
        foreach ($values as $value) {
            $options[] = "<option $attrs value='" . +$value . "'" . (+$selected == +$value ? ' selected' : '') . ">$value</option>";
        }

        return join("\n", $options);
    }

    private function get_select($name, $options, array $select_attrs = array()) {
        return "<select " . $this->attrs_array2str($select_attrs) . " id='$name' name='f_$name'>$options</select>";
    }

    private function attrs_array2str(array $attrs) {
        $result = array();
        foreach ($attrs as $key => $value) {
            $result[] = $key . "='$value'";
        }
        return join(' ', $result);
    }

    private function attrs_array2data(array $attrs, $data_name) {
        $result = array();
        foreach ($attrs as $key => $value) {
            $result[] = $data_name."[$key]=" . $value;
        }
        return join('&', $result);
    }

    public static function init(nc_tpl_component_view $class) {
        extract($GLOBALS, EXTR_SKIP);
        $nc_class_agregator_path = nc_Core::get_object()->INCLUDE_FOLDER . 'classes/nc_class_aggregator_setting.class.php';
        $nc_parent_field_path = $class->get_parent_field_path('Settings');
        $nc_field_path = $class->get_field_path('Settings');
        $str_field = file_get_contents($nc_field_path);

        $have_aggregator = strpos($str_field, 'aggregator');

        if ($have_aggregator === false && strpos($str_field, 'nc_parent_field_path') !== false) {
            $have_aggregator = strpos(file_get_contents($nc_parent_field_path), 'aggregator');
        }

        if ($have_aggregator !== false) {
            ob_start();
            include $nc_field_path;
            ob_clean();
            $nc_class_aggregator_settings = class_exists('nc_class_aggregator_setting') ? nc_class_aggregator_setting::get_instanse() : null;
            if (is_object($nc_class_aggregator_settings)) {
                return new self($nc_class_aggregator_settings);
            }
        }
        return null;
    }

    public function prepare($str, $type) {
        $result = '';
        switch ($type) {
            case 'class':
                $result = str_replace("db_Class_ID", "db_Parent_Class_ID", $str);
                break;

            case 'message':
                $result = str_replace("#db_Class_ID", "#db_Parent_Class_ID", $str);
                $result = str_replace("nc_message_select", "nc_parent_message_select", $result);
                $result = str_replace("&nc_get_message_select", "&nc_is_parent=1&nc_get_message_select", $result);
                break;
        }
        return $result;
    }
}