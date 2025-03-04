<?php
/**
 *
 */
class nc_condition_admin_helpers {

    /**
     * Возвращает HTML-фрагмент для вставки скриптов редактора условий
     * @return string
     */
    public static function get_condition_editor_js() {
        return self::get_script_tag(nc_core::get_object()->ADMIN_PATH . 'condition/js/editor.js');
    }

    /**
     * Выводит HTML-фрагмент для вставки скриптов редактора условий
     * @return void
     */
    public static function include_condition_editor_js() {
        echo static::get_condition_editor_js();
    }

    /**
     * Возвращает <script src="$src">
     * @param string $src
     * @return string
     */
    protected static function get_script_tag($src) {
        return '<script src="' . nc_add_revision_to_url($src) . '"></script>' . "\n";
    }

    /**
     * Возвращает все разделы указанного сайта, содержащие компоненты товаров,
     * а также родителей этих разделов до корня сайта.
     *
     * @param int $site_id
     * @param int $sub_class_id
     * @return array
     */
    public static function get_subdivisions($site_id, $sub_class_id) {
        $db = nc_db();

        try {
            $class_id = (int)nc_Core::get_object()->sub_class->get_by_id($sub_class_id, 'Class_ID');
        } catch (Exception $e) {
            return array();
        }

        $site_id = (int)$site_id;

        // step (1): all subdivisions with goods components in it
        $subdivisions = $db->get_col(
            "SELECT s.`Subdivision_ID`, s.`Hidden_URL`
               FROM `Subdivision` AS s, `Sub_Class` as c
              WHERE s.`Catalogue_ID` = $site_id
                AND s.`Subdivision_ID` = c.`Subdivision_ID`
                AND c.`Class_ID` = $class_id",
            0, 1);

        // No results?!
        if (!$subdivisions) { return array(); }

        // step (2): get all ascendant subdivisions to the root
        $add_parents = array();
        foreach ($subdivisions as $sub_path => $sub_id) {
            $path_parts = explode('/', trim($sub_path, '/'));
            $path = '/';
            foreach ($path_parts as $p) {
                $path .= "$p/";
                if (!isset($subdivisions[$path])) {
                    $add_parents[$path] = "'$path'";
                }
            }
        }

        if ($add_parents) {
            $add_parents_ids = (array)$db->get_col(
                "SELECT `Subdivision_ID`
                   FROM `Subdivision`
                  WHERE `Catalogue_ID` = $site_id
                    AND `Hidden_URL` IN (" . implode(', ', $add_parents) . ')'
            );

            $subdivisions = array_merge($subdivisions, $add_parents_ids);
        }

        $all_ids = implode(',', $subdivisions);

        // step (2): collect subdivision data
        return self::get_subdivision_data(0, $all_ids);
    }

    /**
     * @param $parent_sub_id
     * @param $all_ids
     * @param int $depth
     * @return array
     */
    protected static function get_subdivision_data($parent_sub_id, $all_ids, $depth = 1) {
        $result = array();
        $subdivision_data = (array)nc_db()->get_results(
            "SELECT `Catalogue_ID`, `Subdivision_ID`, `Parent_Sub_ID`, `Subdivision_Name`, `Hidden_URL`, `Checked`, `Priority`,
                    $depth AS `Depth`
               FROM `Subdivision`
              WHERE `Subdivision_ID` IN ($all_ids)
                AND `Parent_Sub_ID` = $parent_sub_id
              ORDER BY `Priority`",
            ARRAY_A
        );

        foreach ($subdivision_data as $sub) {
            $result[] = $sub;
            foreach (self::get_subdivision_data($sub['Subdivision_ID'], $all_ids, $depth+1) as $child) {
                $result[] = $child;
            }
        }

        return $result;
    }

    protected static $field_types = array(
        NC_FIELDTYPE_STRING => 'string',
        NC_FIELDTYPE_INT => 'integer',
        NC_FIELDTYPE_TEXT => 'text',
        NC_FIELDTYPE_SELECT => 'select',
        NC_FIELDTYPE_BOOLEAN => 'boolean',
        NC_FIELDTYPE_FLOAT => 'float',
        NC_FIELDTYPE_DATETIME => 'datetime',
        NC_FIELDTYPE_MULTISELECT => 'multiselect'
    );

    /**
     * Возвращает TypeOfData_ID
     *
     * @return array
     */
    public static function get_field_types() {
        return array_keys(self::$field_types);
    }

    /**
     * Возвращает фрагмент для вставки в SQL-запрос, который фильтрует записи
     * по TypeOfData_ID, которые могут быть использованы в полях редактора условий
     *
     * @return string
     */
    public static function get_field_types_to_export_for_query() {
        return '`TypeOfData_ID` IN (' . implode(', ', array_keys(self::$field_types)) . ')';
    }

    /**
     * Used to export data about component and user table fields
     * $field must have following entries: Class_ID, Field_Name, Description, TypeOfData_ID, Format
     */
    public static function export_field(array $field, $override_class_id = null, $skip_class_id = false) {
        $class_id = $override_class_id ?: $field['Class_ID'];
        $field_id = ($skip_class_id
            ? $field['Field_Name']
            : "$class_id:$field[Field_Name]:$field[TypeOfData_ID]"
        );
        $result = array(
            'id' => $field_id,
            'description' => $field['Description'],
            'type' => self::$field_types[$field['TypeOfData_ID']],
        );

        if ($result['type'] === 'select' || $result['type'] === 'multiselect') {
            list($classifier) = explode(':', $field['Format']);
            $result['classifier'] = $classifier;
        }

        return $result;
    }

    /**
     * Возвращает hash-ссылку на компонент (для использования при формировании
     * текстовых описаний условий)
     */
    public static function get_component_link($component_id) {
        static $cache = array();
        $component_id = (int)$component_id;

        if (!isset($cache[$component_id])) {
            list($component_exists, $name, $fs) = nc_db()->get_row(
                "SELECT `Class_ID`, `Class_Name`, `File_Mode` FROM `Class` WHERE `Class_ID` = $component_id",
                ARRAY_N
            );

            if (!$component_exists) {
                $cache[$component_id] = "<em class='nc--status-error'>" . NETCAT_COND_NONEXISTENT_COMPONENT . '</em>';
            }
            else {
                $hash = '#dataclass' . ($fs ? '_fs' : '') . ".info($component_id)";
                $link = nc_ui_helper::get()->hash_link($hash, $name);
                $cache[$component_id] = sprintf(NETCAT_COND_QUOTED_VALUE, $link);
            }
        }

        return $cache[$component_id];
    }

    /**
     * @param $iso_date
     * @return bool|string
     */
    public static function format_date($iso_date) {
        return date(NETCAT_CONDITION_DATE_FORMAT, strtotime($iso_date));
    }

    /**
     *
     */
    public static function get_field_data($component_ids, $field_name, $field_type) {
        static $cache = array();
        $component_ids = is_array($component_ids) ? $component_ids : array($component_ids);
        sort($component_ids, SORT_NUMERIC);
        $array_hash = md5(json_encode($component_ids));

        $key = "$array_hash:$field_name:$field_type";

        if (!isset($cache[$key])) {
            $cache[$field_name] = false;
            $nc_core = nc_core::get_object();
            foreach ($component_ids as $component_item_id) {
                $field_data = $nc_core->get_component($component_item_id)->get_field($field_name);
                if (!$field_type || $field_data['type'] == $field_type) {
                    $cache[$key] = $field_data;
                    break;
                }
            }
        }

        return $cache[$key];
    }

    /**
     * Кодирование ассоциативного массива в виде JSON-массива с элементами key, value
     * @param array $values
     * @return string
     */
    public static function key_value_json(array $values) {
        $result = array();
        foreach ($values as $k => $v) {
            $result[] = array('key' => $k, 'value' => $v);
        }
        return nc_array_json($result);
    }

    /**
     * @param array $additional_constant_prefixes
     * @return string
     */
    public static function get_strings(array $additional_constant_prefixes = array()) {
        $all_constants = get_defined_constants(true);
        $constants_to_export = array();
        $prefixes = array_merge(array('NETCAT_CONDITION_'), $additional_constant_prefixes);

        foreach ($prefixes as $prefix) {
            $prefix_length = strlen($prefix);

            foreach ($all_constants['user'] as $key => $value) {
                if (strpos($key, $prefix) !== 0) {
                    continue;
                }
                $short_key = substr($key, $prefix_length);
                $constants_to_export[$short_key] = $value;
            }
        }

        return nc_array_json($constants_to_export);
    }

}