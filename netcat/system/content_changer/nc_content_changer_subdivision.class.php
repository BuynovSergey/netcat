<?php

class nc_content_changer_subdivision extends nc_content_changer {


    private static $file_fields = array("ncIcon", "ncImage", "ncSMO_Image");


    /**
     * @inheritDoc
     */
    protected function validate() {
        $dataset = $this->instruction->get_dataset();

        return !empty($dataset);
    }

    /**
     * @return bool
     */
    protected function do_create() {
        if (!$this->resolve_dependencies()) {
            return false;
        }


        $dataset = $this->instruction->get_dataset();
        $dataset = nc_is_nested_array($dataset) ? $dataset : array($dataset);
        $catalogue = nc_core::get_object()->catalogue;

        foreach ($dataset as $data) {
            $data["Catalogue_ID"] = nc_array_value(
                $data,
                "Catalogue_ID",
                $catalogue->get_current("Catalogue_ID")
            );

            $data["Parent_Sub_ID"] = $this->retrieve_parent_subdivision_id($data);

            try {
                $subdivision_id = nc_core::get_object()->subdivision->create($this->remove_fileable_fields_from_array($data));

                if (isset($data["EnglishName"])) {
                    $this->instruction->set_subdivision_keyword($data["EnglishName"]);
                }

                $this->update_file_fields($data, $subdivision_id);
            } catch (Exception $e) {
                continue;
            }

        }

        return true;
    }

    /**
     * @return bool
     */
    protected function do_update() {
        if (!$this->resolve_dependencies()) {
            return false;
        }

        $subdivision_table = $this->get_subdivision_table_by_keyword();
        $dataset = $this->instruction->get_dataset();

        // Файловые поля намеренно меняются до обновления не файловых, потому что может быть изменен EnglishName, если он передан в dataset
        $this->update_file_fields($dataset);

        $subdivision_table->update($this->remove_fileable_fields_from_array($dataset));

        return true;
    }

    /**
     * @return array
     */
    private function remove_fileable_fields_from_array(array $data) {
        return array_diff_key($data, array_flip(self::$file_fields));
    }

    /**
     * @param int|null $subdivision_id
     *
     * @return void
     */
    private function update_file_fields(array $fields, $subdivision_id = null) {
        $subdivision_id = $subdivision_id ?: (int)$this->get_subdivision_table_by_keyword()->get_value("Subdivision_ID");

        if (!$subdivision_id) {
            return;
        }

        $files = nc_core::get_object()->files;

        foreach (self::$file_fields as $field) {
            if (isset($fields[$field])) {
                $files->field_save_file("Subdivision", $field, $subdivision_id, $fields[$field]);
            }
        }
    }

    /**
     * @param $data
     *
     * @return int
     */
    private function retrieve_parent_subdivision_id($data) {
        if (!isset($data["Parent_Sub_ID"])) {
            return 0;
        }

        if (!is_numeric($data["Parent_Sub_ID"])) {
            return (int)$this->get_subdivision_table_by_keyword($data["Parent_Sub_ID"])->get_value("Subdivision_ID");
        }

        return (int)$data["Parent_Sub_ID"];
    }

}
