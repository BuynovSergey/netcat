<?php

/**
 * «Поле» формы, выводящее указанный HTML-фрагмент
 */
class nc_a2f_field_custom extends nc_a2f_field {

    protected $wrap = false;
    protected $has_default = false;
    protected $can_have_initial_value = false;
    protected $html;

    public function render($template = '') {
        if ($this->wrap) {
            return parent::render($template);
        } else {
            return $this->html;
        }
    }

    function render_value_field($html = true) {
        return $this->html;
    }

    public function get_extend_parameters() {
        return array(
            'html' => array('type' => 'text', 'caption' => 'HTML'),
        );
    }

}