<?php

/**
 * Класс для реализации поля типа "Связь с другой сущностью"
 *
 * (Не является абстрактным из-за того, что экземпляр создаётся в function nc_customsettings_show_once())
 */
class nc_a2f_field_rel extends nc_a2f_field {

    protected $select_link, $width;
    protected $has_default = 0;
    protected $can_have_initial_value = false;

    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        parent::__construct($field_settings, $parent);
        $nc_core = nc_Core::get_object();
        require_once ($nc_core->ADMIN_FOLDER."related/format.inc.php");
    }

    public function get_subtypes() {
        return array('sub', 'cc', 'user', 'class');
    }

    protected function get_related_object_caption($object_id) {
        $field_data = $this->get_relation_object();
        $related_caption = listQuery($field_data->get_object_query($object_id),
                                     $field_data->get_full_admin_template("%ID. <a href='%LINK' target='_blank'>%CAPTION</a>"));
        return $related_caption ? $related_caption
                                : sprintf(NETCAT_MODERATION_RELATED_INEXISTENT, $object_id);
    }

    public function render_value_field($html = true) {
        $nc_core = nc_Core::get_object();

        $change_template = "&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"%s\">".NETCAT_MODERATION_CHANGE_RELATED."</a>\n";
        $remove_template = "&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"%s\">".NETCAT_MODERATION_REMOVE_RELATED."</a>\n";

        $nc = '$nc';
        $name = $this->name;

        $change_link = "window.open('".$nc_core->ADMIN_PATH.$this->select_link."', ".
                       "'nc_popup_{$name}', ".
                       "'width=".$this->width.",height=500,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes'); ".
                       "return false;";

        $remove_link = "$nc('#cs_{$name}_value').val('');".
                       "$nc('#cs_{$name}_caption').html('".NETCAT_MODERATION_NO_RELATED."');".
                       "$nc('#cs_{$name}_inherit').css('display', '');".
                       "return false;";

        $ret = "<span id='cs_{$name}_caption' style='font-weight:bold;'>";

        if (!$this->value || !is_numeric($this->value)) {
            $ret .= NETCAT_MODERATION_NO_RELATED;
        }
        else {
            $ret .= $this->get_related_object_caption($this->value);
        }

        $ret .= "</span>";
        $ret .= "<input id='cs_{$name}_value' name='{$this->get_field_name()}' type='hidden' value='".intval($this->value)."' />";

        // ссылки изменить, удалить
        $ret .= sprintf($change_template, $change_link);
        $ret .= sprintf($remove_template, $remove_link);

        // чекбокс «наследовать значение»
        if ($this->can_inherit_values) {
            $inherited = ($this->value == '#INHERIT#' || !$this->is_set);
            $ret .= "&nbsp;&nbsp;&nbsp;" .
                    "<span id='cs_{$name}_inherit' class='nc-custom-settings-inherit-checkbox'" .
                    ($this->value && $this->value != '#INHERIT#' ? " style='display:none'" : "") . ">" .
                    "<label><input type='checkbox' name='{$this->get_field_name()}' value='#INHERIT#'" .
                    ($inherited ? ' checked' : '') . "> " .
                    CONTROL_CUSTOM_SETTINGS_INHERIT .
                    "</label></span>";
        }

        return $ret;
    }

    /**
     * @return field_relation
     */
    protected function get_relation_object() {
    }

    /**
     *
     */
    protected function get_displayed_default_value() {
        if (is_numeric($this->default_value) && $this->default_value) {
            return $this->get_related_object_caption($this->default_value);
        }
        else {
            return '';
        }
    }

}
