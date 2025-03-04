<?php


class nc_ui_table extends nc_ui_common {

    //--------------------------------------------------------------------------

    protected static $obj;

    protected $heading = "";

    //--------------------------------------------------------------------------

    public function render() {
        $heading = "\t" . $this->heading . "";
        $rows    = "\t" . implode("\n\t", $this->items) . "\n";
        $attr    = $this->render_attr();
        return "<table{$attr}>\n{$heading}\n{$rows}</table>";
    }

    //--------------------------------------------------------------------------

    public static function get($data = null)
    {
        if (is_null(self::$obj)) {
            self::$obj = new self();
        }
        self::$obj->reset();
        self::$obj->class_name('nc-table');

        if ($data) {
            foreach ($data as $row) {
                self::$obj->add_row($row);
            }
        }

        return self::$obj;
    }

    //--------------------------------------------------------------------------

    public function set_heading() {
        $cells = func_get_args();
        $this->heading = "<tr><th>" . implode('</th><th>', $cells) . "</th></tr>";
        return $this;
    }

    //--------------------------------------------------------------------------

    public function row() {
        return nc_ui_html::get('tr');
    }

    //--------------------------------------------------------------------------

    public function add_row($row = null) {
        if ($row) {
            if (func_num_args() > 1 || is_array($row)) {
                if (func_num_args() > 1) $row = func_get_args();
                $this->items[] = "<tr><td>" . implode('</td><td>', $row) . "</td></tr>";
            }
            else {
                $this->items[] = (string)$row;
            }
        }
        else {
            $row = nc_ui_html::get('tr');
            $this->items[] = $row;
        }

        return $row;
    }

    //--------------------------------------------------------------------------

    public function thead()
    {
        $row = nc_ui_html::get('tr');
        $this->heading = $row;
        return $row;
    }

    //--------------------------------------------------------------------------

}