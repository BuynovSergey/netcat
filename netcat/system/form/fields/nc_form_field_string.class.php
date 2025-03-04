<?php class_exists('nc_system') OR die('Unable to load file');


class nc_form_field_string extends nc_form_field {

    //-------------------------------------------------------------------------

    protected $default_settings = array(
        'size' => null,
    );

    //-------------------------------------------------------------------------

    public function render_field($attr = array()) {
        $attr = $this->get_attr($attr);
        $attr['name']  = $this->get_name();
        $attr['type']  = 'text';
        $attr['value'] = $this->get_value(false);

        if (empty($attr['size']) && ($size = $this->get('size'))) {
            $attr['size'] = $size;
        }

        return $this->make_html_elem('input', $attr, false);
    }

    //-------------------------------------------------------------------------

}