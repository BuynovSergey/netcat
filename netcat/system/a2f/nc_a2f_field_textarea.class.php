<?php

class nc_a2f_field_textarea extends nc_a2f_field {

    protected $size = 5;
    protected $embededitor;
    protected $codemirror = true;

    /**
     * @access private
     */
    function render_value_field($html = true) {
        $nc_core = nc_Core::get_object();

        $ret = '';
        $textarea_id = $this->get_textarea_id();
        if ($this->embededitor) {
            $windowWidth = 750;
            $windowHeight = 605;
            switch (nc_Core::get_object()->get_settings('EditorType')) {
                default:
                case 2:
                    $editor_name = 'FCKeditor';
                    break;
                case 3:
                    $editor_name = 'ckeditor4';
                    $windowWidth = 1100;
                    $windowHeight = 420;
                    break;
                case 4:
                    $editor_name = 'tinymce';
                    break;
            }
            $link = "editors/{$editor_name}/neditor.php";
            $ret.= "<button type='button' onclick=\"window.open('".$nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH.$link."?form=adminForm&control=".$textarea_id."', 'Editor', 'width={$windowWidth},height={$windowHeight},resizable=yes,scrollbars=no,toolbar=no,location=no,status=no,menubar=no');\">".TOOLS_HTML_INFO."</button><br />";
        }

        $value = $this->get_value_for_input();

        $ret .= "<textarea id='" . $textarea_id . "' name='" . $this->get_field_name() .
                "' rows='" . $this->size . "' class='ncf_value_textarea" .
                ($this->codemirror ? "" : " no_cm") .
                "'>" . $value . "</textarea>";
        if ($html) {
            $ret = "<div class='ncf_value'>".$ret."</div>\n";
        }



        return $ret;
    }

    public function get_extend_parameters() {
        return array('embededitor' => array('type' => 'checkbox', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_VIZRED),
                'nl2br' => array('type' => 'checkbox', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_BR),
                'size' => array('type' => 'string', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_SIZE_H)
        );
    }

    protected function get_textarea_id() {
        $id = $this->get_field_name();
        return str_replace(array('[', ']'), '_', $id);
    }

    public function get_value($use_default = true) {

        if (isset($this->value) || !$use_default) {
            if (!empty($this->nl2br)) {
                return nl2br($this->value);
            } else {
                return $this->value;
            }
        }    
        
        return $this->default_value;
    }

}