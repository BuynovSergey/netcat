<?php



class nc_backup_result implements ArrayAccess {

    //-------------------------------------------------------------------------

    protected $data = array();

    //-------------------------------------------------------------------------

    public function __construct($data = array()) {
        $this->data = $data;
    }

    //-------------------------------------------------------------------------

    public function get($offset, $default = null) {
        return $this->offsetExists($offset) ? $this->offsetGet($offset) : $default;
    }

    //-------------------------------------------------------------------------

    public function get_id() {
        return $this->get('id');
    }

    //-------------------------------------------------------------------------

    public function get_new_id() {
        return $this->get('new_id');
    }

    /**************************************************************************
        ArrayAccess methods
    **************************************************************************/

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    //-------------------------------------------------------------------------

    public function offsetGet($offset) {
        return $this->data[$offset];
    }

    //-------------------------------------------------------------------------

    public function offsetSet($offset, $value) {
        $this->data[$offset] = $value;
    }

    //-------------------------------------------------------------------------

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    //-------------------------------------------------------------------------

}