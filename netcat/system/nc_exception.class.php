<?php

class nc_Exception_DB_Error extends Exception {

    protected $query, $error;

    public function __construct($query, $error) {
        $this->query = $query;
        $this->error = $error;

        parent::__construct("Query: <br/> ".$this->query."<br/>Error: <br/>".$this->error);
    }

    public function query() {
        return $this->query;
    }

    public function error() {
        return $this->error;
    }

}

class nc_Exception_Files_Not_Rights extends Exception {

    protected $path;

    public function __construct($path) {
        $this->path = $path;

        parent::__construct("Not rights to ".$path);
    }

    public function path() {
        return $this->path;
    }

}

class nc_Exception_Class_Doesnt_Exist extends Exception {

    protected $class_id;

    public function __construct($class_id) {
        $this->class_id = $class_id;

        if (defined('NETCAT_EXCEPTION_CLASS_DOESNT_EXIST')) {
            parent::__construct(sprintf(NETCAT_EXCEPTION_CLASS_DOESNT_EXIST, $class_id));
        } else {
            parent::__construct("Class with id ".$class_id." doesn't exist.");
        }
    }

    public function class_id() {
        return $this->class_id;
    }

}

class nc_Exception_Trash_Already_Exists extends Exception {

    protected $class_id, $messages;

    public function __construct($class_id, $messages) {
        $this->class_id = $class_id;
        $this->messages = $messages;

        parent::__construct("Cледующие объекты уже находятся в корзине: ".join(',', $this->messages));
    }

}

class nc_Exception_Trash_Full extends Exception {

}

class nc_Exception_Trash_Folder_Fail extends Exception {

}

class nc_Exception_Class_Invalid_Keyword extends Exception {

}