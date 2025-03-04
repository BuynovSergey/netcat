<?php class_exists('nc_system') OR die('Unable to load file');


class nc_form_field_select extends nc_form_field {

    //-------------------------------------------------------------------------

    protected $default_settings = array(
        'options'  => array(),
        'multiple' => false,
        'size'     => false,
    );

    //-------------------------------------------------------------------------

    public function set_options($options, $empty_option = false) {
        if ($empty_option !== false) {
            array_unshift($options, $empty_option === true ? '' : $empty_option);
        }
        return $this->set('options', $options);
    }

    //-------------------------------------------------------------------------

    public function render_field($attr = array()) {
        $attr = $this->get_attr($attr);

        $attr['name']  = $this->get_name();

        $options_html = '';
        $options      = (array) $this->get('options');

        if ($this->get('multiple')) {
            $attr['multiple'] = 'multiple';
        }
        if (empty($attr['size']) && ($size = $this->get('size'))) {
            $attr['size'] = $size;
        }

        foreach ($options as $value => $title) {
            if (is_array($title)) {
                $options = '';
                foreach ($title as $v => $t) {
                    $options .= $this->render_option_item($t, $v);
                }
                $options_html .= $this->make_html_elem('optgroup', array('label' => $value), $options);
            } else {
                $options_html .= $this->render_option_item($title, $value);
            }
        }

        return $this->make_html_elem('select', $attr, $options_html);
    }

    //-------------------------------------------------------------------------

    protected function render_option_item($title, $value) {
        $attr = array('value' => $value);
        if ($value == $this->get_value()) {
            $attr['selected'] = 'selected';
        }
        return $this->make_html_elem('option', $attr, $title);
    }

    //-------------------------------------------------------------------------

}