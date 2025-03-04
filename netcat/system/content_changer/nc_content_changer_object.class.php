<?php

class nc_content_changer_object extends nc_content_changer {

    /**
     * @var nc_content_changer_object_instruction
     */
    protected $instruction;

    /**
     * @inheritDoc
     */
    protected function validate() {
        if ($this->instruction->get_action() == "delete") {
            return true;
        }

        $dataset = $this->instruction->get_dataset();

        return isset($dataset["fields"]) || isset($dataset["files"]);
    }

    /**
     * @return bool
     */
    protected function do_update() {
        if (!$this->resolve_dependencies()) {
            return false;
        }

        $dataset = $this->instruction->get_dataset();
        $objects_by_component = $this->find_objects();
        $has_files = !empty($dataset["files"]);
        $has_fields = !empty($dataset["fields"]);

        if ($has_files) {
            $nc_files = nc_core::get_object()->files;
        }

        foreach ($objects_by_component as $component_id => $objects) {
            $table = nc_db_table::make("Message" . $component_id);

            foreach ($objects as $object_id) {
                if ($has_files) {
                    foreach ($dataset["files"] as $field_name => $file) {
                        $nc_files->field_save_file($component_id, $field_name, $object_id, $file);
                    }
                }

                if ($has_fields) {
                    $table->where("Message_ID", $object_id)->update($dataset["fields"]);
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function do_create() {
        if (!$this->resolve_dependencies()) {
            return false;
        }

        $objects_dataset = $this->instruction->get_dataset();
        $infoblock_ids = $this->get_infoblock_ids();
        $message = nc_core::get_object()->message;

        foreach ($infoblock_ids as $infoblock_id) {
            foreach ($objects_dataset["fields"] as $fields) {
                if (empty($fields)) {
                    continue;
                }

                try {
                    $message->create($infoblock_id, $fields);
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function do_delete() {
        if (!$this->resolve_dependencies()) {
            return false;
        }

        $mapped_objects = $this->find_objects();
        $message = nc_core::get_object()->message;

        foreach ($mapped_objects as $class_id => $objects_ids) {
            try {
                $message->delete_by_id($objects_ids, $class_id);
            } catch (nc_Exception_DB_Error $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function do_substring_replace() {
        if (!$this->resolve_dependencies()) {
            return false;
        }

        $objects_mapping = $this->find_objects();
        $object_dataset = $this->instruction->get_dataset();

        foreach ($objects_mapping as $class_id => $objects_ids) {
            foreach ($objects_ids as $object_id) {
                foreach ($object_dataset["fields"] as $field => $meta) {
                    $search = $meta["search"];
                    $replacement = $meta["replacement"];
                    nc_db()->query(
                        "UPDATE `Message$class_id` SET `$field` = REPLACE(`$field`, '$search', '$replacement') WHERE Message_ID = $object_id;"
                    );
                }
            }
        }

        return true;
    }

    /**
     * @return array
     */
    protected function find_objects() {
        $objects = array();

        if ($this->instruction->has_infoblock_finder()) { // Объекты в инфоблоках
            $infoblock_table = $this->instruction->get_infoblock_finder()->get_table();

            $infoblock_ids = $infoblock_table->get_list("Class_ID");

            foreach ($infoblock_ids as $infoblock_id => $component_id) {
                $table = $this->instruction->get_object_finder($component_id)->get_table()
                    ->where("Sub_Class_ID", $infoblock_id);

                $objects[$component_id] = array_values($table->get_list("Message_ID"));
            }
        } else { // Объекты компонента по условиям
            $finder = $this->instruction->get_object_finder();
            $component_id = $finder->get_component_id();
            $objects[$component_id] = array_values($finder->get_table()->get_list("Message_ID"));
        }

        return $objects;
    }

}
