<?php

class nc_ui_toolbar extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    //--------------------------------------------------------------------------

    public function render() {
        $attr = $this->render_attr();
        return "<ul{$attr}>\n\t" . implode("\n\t", $this->items) . "\n</ul>";
    }

    //--------------------------------------------------------------------------

    public static function get()
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        self::$obj->class_name('nc-toolbar');
        return self::$obj;
    }

    //--------------------------------------------------------------------------
    
    public function mb_ucfirst($text) {
        if ($text) {
            $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
        }
        return $text;
    }

    //--------------------------------------------------------------------------

    public function add_text($text = NULL)
    {
        $li = nc_ui_html::get('li')->text( $this->mb_ucfirst($text) )->text_wrapper('span');
        $this->items[] = $li;
        return $li;
    }

    //--------------------------------------------------------------------------

    public function add_btn($href = NULL, $text = NULL)
    {
        $li = nc_ui_html::get('li')->href($href)->text( $this->mb_ucfirst($text) );
        $this->items[] = $li;
        return $li;
    }

    //--------------------------------------------------------------------------

    public function add_divider()
    {
        $this->items[] = "<li class='nc-divider'></li>";
    }

    //--------------------------------------------------------------------------

    public function add_move_place()
    {
        $this->items[] = "<li><span class='nc-move-place'><i class='nc-icon nc--move'></i></span></li>";
    }

    //--------------------------------------------------------------------------

}