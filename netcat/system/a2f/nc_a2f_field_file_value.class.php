<?php

/**
 * Класс для реализации поля типа "Файл"
 */
class nc_a2f_field_file_value extends nc_image_path_a2f implements ArrayAccess {
    protected $data = array();

    public static function by_field_file(nc_a2f_field_file $field_file) {
        $data = $field_file->get_value_raw();
        if (empty($data)) {
            return array();
        }
        $result_file_path = $data['resultpath'];
        $object = new self($result_file_path, pathinfo($result_file_path, PATHINFO_EXTENSION));
        $a2f = $field_file->get_parent();
        $entity = $a2f->get_entity();
        $entity_type = $a2f->get_entity_type();
        if ($entity && $entity_type) {
            $object->set_entity($entity_type);
            $object->set_field($field_file->get_name());
            $object->set_object($entity);
        }
        $object->set_data($data);
        return $object;
    }

    public function set_data(array $data) {
        $this->data = $data;
    }

    public function offsetExists($offset) {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function offsetSet($offset, $value) {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    protected function can_user_edit_image() {  }

    protected function get_editable_image_form($file_path) {  }


    /**
     * Данная функция вызывается при обратном преобразовании из var_export.
     * Этот фикс нужен, чтобы скрипт не падал, при загрузке пользовательских настроек из базы.
     * @param $array
     * @return nc_a2f_field_file_value
     */
    public static function __set_state($array) {
        $object = new self();
        return $object;
    }
}
