<?

/* $Id: format.inc.php 5946 2012-01-17 10:44:36Z denis $ */
// вспомогательные классы для работы с полями типа "связь с др объектом"

/**
 * фабрика объектов с данными о поле
 */
class field_relation_factory {

    function __construct() {
        trigger_error("Cannot instantiate <i>field_relation_factory</i>, use static method field_relation_factory::get_instance()", E_USER_ERROR);
    }

    /**
     * на основе формата поля возвращает объект нужного класса
     * @static
     * @param string формат поля (из настроек поля)
     * @return object объект соответствующего класса для работы с данными о поле
     */
    static function get_instance($field_format) {

        // двойные/одинарные второго параметра в Format сейчас не обрабатываются;
        // они добавлены на случай добавления дополнительных параметров
        // поэтому можно переписать следующее регвыр как preg_split с ограничением
        // количества результатов
        preg_match("/^
                  ([\w_-]+)       # relation class
                  (?:             # caption (optional)
                    \s* : \s*     # delimiter from relation class
                    (['\"])?      # opening quote (optional)
                    (.*)          # caption template for listquery
                  )?
                $/xi", $field_format, $regs);

        $relation_class = nc_array_value($regs, 1);
        $quote = nc_array_value($regs, 2);
        $caption_template = nc_array_value($regs, 3);

        if (!$relation_class) {
            trigger_error("field_relation_factory::get_instance() - incorrect field format (&quot;{$fldFmt[$field_index]}&quot;)", E_USER_ERROR);
        }

        // remove trailing quote
        if ($caption_template && $quote) {
            $caption_template = nc_preg_replace("/$quote$/", "", $caption_template);
        }

        if (is_numeric($relation_class)) { // ШАБЛОН ДАННЫХ
            $relation_type = 'message';
        } else {
            $relation_type = strtolower($relation_class);
            // achtung bitte: из relation_type убираются знаки "-" и "_"!
            $relation_type = str_replace(array("-", "_"), "", $relation_type); // sub[_-]class
        }

        $class_name = "field_relation_{$relation_type}";
        if (class_exists($class_name)) {
            return new $class_name($caption_template, $relation_class);
        } else {
            trigger_error("field_relation_factory::get_instance() - wrong <i>relation_class</i> &quot;{$relation_type}&quot;", E_USER_ERROR);
        }
    }

    // of factory() method

   static function get_instance_by_field_id($field_id) {
        global $db;
        $field_format = $db->get_var("SELECT Format
                                    FROM Field
                                   WHERE Field_ID=".(int) $field_id."
                                     AND TypeOfData_ID = " . NC_FIELDTYPE_RELATION);

        if (!$field_format) {
            trigger_error("get_instance_by_field_id - wrong field ID?", E_USER_ERROR);
        }
        return field_relation_factory::get_instance($field_format);
    }

}

// of factory object
// ============================================================================

/**
 * Основной класс для работы с полями "связь".
 * Не должен использоваться отдельно! (должны использоваться field_relation_*)
 */
class field_relation {

    /**
     * Шаблон для вывода названия связанного объекта
     */
    var $caption_template;
    /**
     * дефолтная ширина всплывающего окна
     */
    var $popup_width = 350;
    /**
     * дефолтная высота всплывающего окна
     */
    var $popup_height = 500;

    function __construct($caption_template = '') {
        $this->caption_template = $caption_template;
    }

    /**
     * возвращает дефолотный шаблон названия
     * реализация - в конкретных классах
     */
    function get_default_caption_template() {

    }

    /**
     * возвращает шаблон названия (SQL)
     */
    function get_caption_template() {
        if ($this->caption_template) {
            return $this->caption_template;
        }

        $caption_template = $this->get_default_caption_template();
        if (!$caption_template) {
            trigger_error(get_class($this)."::get_default_caption_template() returned null", E_USER_ERROR);
        }
        return $caption_template;
    }

    /**
     * возвращает SQL-запрос для получения названия конкретного объекта
     * реализация - в конкретных классах
     * @return string
     */
    function get_object_query($object_id) {

    }

    /**
     * возвращает SQL-запрос для получения списка объектов
     * реализация - в конкретных классах
     */
    function get_list_query() {

    }

    /**
     * ссылка на редактирование соответствующего объекта
     * для использования вместе с listQuery
     * реализация - в конкретных классах
     */
    function get_admin_link_template() {

    }

    /**
     * шаблон для вывода значения в админке (ссылка+название)
     * для использования вместе с listQuery
     */
    function get_full_admin_template($template = '') {
        if (!$template)
                $template = "[%ID] <a href='%LINK' target='_blank'>%CAPTION</a>";
        $tpl = str_replace(array('%ID', '%LINK', '%CAPTION', '%CLASSID'),
            array("\$data[ItemID]", $this->get_admin_link_template(), "\$data[ItemCaption]", "\$data[ItemClassID]"),
                        $template);
        // $tpl = "[\$data[ItemID]] <a href='" . $this->get_admin_link_template() . "' target='_blank'>".
        //       "\$data[ItemCaption]</a>";
        return $tpl;
    }

    /**
     * не хотелось дублировать тип связанного объекта (message, subclass etc)
     * в виде переменной класса
     */
    function get_relation_type() {
        // класс имеет имя field_relation_message -> извлечь последнюю часть (e.g. message)
        $name = explode("_", get_class($this));
        return array_pop($name);
    }

}

// ---------------------------------------------------------------------------

class field_relation_message extends field_relation {

    /**
     * ID шаблона данных (MessageXX, XX=$class_id)
     */
    var $class_id;
    var $popup_width = 800;

    /**
     * Constructor
     */
    function __construct($caption_template, $class_id) {
        parent::__construct($caption_template);
        $this->class_id = (int) $class_id;
        if (!$this->class_id) {
            trigger_error(get_class($this)." -  relation class_id is not defined", E_USER_ERROR);
        }
    }

    function get_default_caption_template() {
        $tpl = "CONCAT(c.Class_Name, ' #', m.Message_ID)";
        return $tpl;
    }

    function get_object_query($object_id) {
        $object_id = (int) $object_id;
        if (!$object_id) {
            trigger_error(get_class($this)."::get_object_query - object_id is not defined", E_USER_ERROR);
        }

        // пути дальнейшей оптимизации:
        //  - делать join только если используется default caption template
        $qry = "SELECT ".$this->get_caption_template()." as ItemCaption,
                   m.Message_ID as ItemID
              FROM Message{$this->class_id} as m,
                   Sub_Class as sc,
                   Class as c
             WHERE m.Message_ID = $object_id
               AND m.Sub_Class_ID = sc.Sub_Class_ID
               AND sc.Class_ID = c.Class_ID";

        return $qry;
    }

    function get_list_query() {
        trigger_error(get_class($this)."::get_list_query - not implemented; use s_list_class with <code>list_mode=select</code> instead", E_USER_ERROR);
    }

    function get_admin_link_template() {
        global $ADMIN_PATH;
        return "".$ADMIN_PATH."#object.edit({$this->class_id},\$data[ItemID])";
    }

}

// ---------------------------------------------------------------------------

class field_relation_subdivision extends field_relation {

    function get_default_caption_template() {
        return "Subdivision_Name";
    }

    function get_object_query($object_id) {
        $object_id = (int) $object_id;
        if (!$object_id) {
            trigger_error(get_class($this)."::get_object_query - object_id is not defined", E_USER_ERROR);
        }

        $qry = "SELECT ".$this->get_caption_template()." as ItemCaption,
                   Subdivision_ID as ItemID
              FROM Subdivision
             WHERE Subdivision_ID = $object_id";

        return $qry;
    }

    function get_list_query() {
        trigger_error(get_class($this)."::get_list_query - not implemented", E_USER_ERROR);
    }

    function get_admin_link_template() {
        global $ADMIN_PATH;
        return "".$ADMIN_PATH."#subdivision.edit(\$data[ItemID])";
    }

}

// ---------------------------------------------------------------------------

class field_relation_subclass extends field_relation {

    var $popup_width = 800;

    function get_default_caption_template() {
        return "Sub_Class_Name";
    }

    function get_object_query($object_id) {
        $object_id = (int) $object_id;
        if (!$object_id) {
            trigger_error(get_class($this)."::get_object_query - object_id is not defined", E_USER_ERROR);
        }

        $qry = $this->_make_query("Sub_Class_ID = $object_id");
        return $qry;
    }

    /**
     * @param родительский раздел
     */
    function get_list_query() {
        $parent_id = (int)func_get_arg(0);
        if (!$parent_id) {
            trigger_error(get_class($this)."::get_object_query - object_id is not defined", E_USER_ERROR);
        }

        $qry = $this->_make_query("Subdivision_ID = $parent_id");
        return $qry;
    }

    function _make_query($constraint) {
        $qry = "SELECT ".$this->get_caption_template()." as ItemCaption,
                   Sub_Class_ID as ItemID, 
                   Subdivision_ID as SubID,
                   Class_ID as ItemClassID
              FROM Sub_Class
             WHERE $constraint
             ORDER BY Priority";
        return $qry;
    }

    function get_admin_link_template() {
        global $ADMIN_PATH;
        return "".$ADMIN_PATH."#subclass.edit(\$data[ItemID], \$data[SubID])";
    }

}

// ---------------------------------------------------------------------------

class field_relation_catalogue extends field_relation {

    function get_default_caption_template() {
        return "Catalogue_Name";
    }

    function get_object_query($object_id) {
        $object_id = (int) $object_id;
        if (!$object_id) {
            trigger_error(get_class($this)."::get_object_query - object_id is not defined", E_USER_ERROR);
        }

        $qry = "SELECT ".$this->get_caption_template()." as ItemCaption,
                   Catalogue_ID as ItemID
              FROM Catalogue
             WHERE Catalogue_ID = $object_id";
        return $qry;
    }

    function get_list_query() {
        $qry = "SELECT ".$this->get_caption_template()." as ItemCaption,
                   Catalogue_ID as ItemID
              FROM Catalogue ORDER BY Priority";
        return $qry;
    }

    function get_admin_link_template() {
        global $ADMIN_PATH;
        return "".$ADMIN_PATH."#site.edit(\$data[ItemID])";
    }

}

// ---------------------------------------------------------------------------

class field_relation_user extends field_relation {

    function get_default_caption_template() {
        return $GLOBALS["AUTHORIZE_BY"];
    }

    function get_object_query($object_id) {
        $object_id = (int) $object_id;
        if (!$object_id) {
            trigger_error(get_class($this)."::get_object_query - object_id is not defined", E_USER_ERROR);
        }

        $qry = "SELECT ".$this->get_caption_template()." as ItemCaption,
                   User_ID as ItemID
              FROM User
             WHERE User_ID = $object_id";
        return $qry;
    }

    function get_list_query() {
        $qry = "SELECT ".$this->get_caption_template()." as ItemCaption,
                   User_ID as ItemID
              FROM User
             ORDER BY User_ID";
        return $qry;
    }

    function get_admin_link_template() {
        global $ADMIN_PATH;
        return "".$ADMIN_PATH."#user.edit(\$data[ItemID])";
    }

}

class field_relation_class extends field_relation {

    function get_default_caption_template() {
        return "Class_Name";
    }

    function get_object_query($object_id) {
        $object_id = (int) $object_id;
        if (!$object_id) {
            trigger_error(get_class($this)."::get_object_query - object_id is not defined", E_USER_ERROR);
        }

        $qry = "SELECT ".$this->get_caption_template()." as ItemCaption,
                   Class_ID as ItemID
              FROM Class
             WHERE Class_ID = $object_id";
        return $qry;
    }

    function get_list_query() {
        $qry = "SELECT ".$this->get_caption_template()." as ItemCaption,
                   Class_ID as ItemID
              FROM Class
              WHERE ClassTemplate = 0
             ORDER BY Priority, Class_ID";
        return $qry;
    }

    function get_admin_link_template() {
        global $ADMIN_PATH;
        return "".$ADMIN_PATH."#dataclass.edit(\$data[ItemID])";
    }

}
?>
