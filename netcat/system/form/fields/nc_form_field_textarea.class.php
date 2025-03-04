<?php class_exists('nc_system') OR die('Unable to load file');


class nc_form_field_textarea extends nc_form_field {

    //-------------------------------------------------------------------------

    protected $default_settings = array(
        'codemirror' => false,
        'size'       => 10,
    );

    //-------------------------------------------------------------------------

    public function render_field($attr = array()) {
        $attr = $this->get_attr($attr);

        $attr['name'] = $this->get_name();

        $class_name =& $attr['class'];
        if (!$this->get('codemirror')) {
            $class_name .= ($class_name ? ' ' : '') . 'no_cm';
        }

        if (empty($attr['rows']) && ($size = $this->get('size'))) {
            $attr['rows'] = $size;
        }

        return $this->make_html_elem('textarea', $attr, $this->get_value(false));
    }

    //-------------------------------------------------------------------------

}