<?php

class nc_content_changer_infoblock extends nc_content_changer {

    /**
     * @inheritDoc
     */
    protected function validate() {
        if ($this->action == "delete") {
            return true;
        }

        $dataset = $this->instruction->get_dataset();

        return !empty($dataset);
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function do_update() {
        if (!$this->resolve_dependencies()) {
            return false;
        }

        $dataset = $this->instruction->get_dataset();

        if (isset($dataset["custom_settings"])) {
            $this->update_custom_settings();
        }

        if (isset($dataset["fields"])) {
            $this->update_fields();
        }

        if (isset($dataset["files"])) {
            $this->update_files();
            $this->delete_tmp_directory();
        }

        if (isset($dataset["mixins"])) {
            $this->update_mixins();
        }

        if (isset($dataset["conditions"])) {
            $this->update_conditions();
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function do_paste() {
        if (!$this->resolve_dependencies()) {
            return false;
        }

        $infoblock_id = (int)$this->get_infoblock_table_by_keyword()->get_value("Sub_Class_ID");
        $dataset = $this->instruction->get_dataset();

        if (!$infoblock_id || !isset($dataset["subdivision_id"])) {
            return false;
        }

        if (!is_numeric($dataset["subdivision_id"])) {
            $dataset["subdivision_id"] = (int)$this->get_subdivision_table_by_keyword($dataset["subdivision_id"])->get_value(
                "Subdivision_ID"
            );
        }

        if (isset($dataset["target_infoblock_id"]) && !is_numeric($dataset["target_infoblock_id"])) {
            $dataset["target_infoblock_id"] = (int)$this->get_infoblock_table_by_keyword($dataset["target_infoblock_id"])->get_value(
                "Sub_Class_ID"
            );
        }

        $nc_core = nc_core::get_object();

        $dataset["mode"] = nc_array_value($dataset, "mode", "copy");
        $dataset["position"] = nc_array_value($dataset, "position", "after");
        $dataset["main_axis"] = nc_array_value($dataset, "main_axis", "horizontal");
        $dataset["site_id"] = nc_array_value($dataset, "site_id", (int)$nc_core->catalogue->get_current("Catalogue_ID"));

        if (isset($dataset["container_id"]) && !is_numeric($dataset["container_id"])) {
            $dataset["container_id"] = (int)$this->get_infoblock_table_by_keyword($dataset["container_id"])->get_value("Sub_Class_ID");
        }

        if (isset($dataset["object_keyword"]) && isset($dataset["object_infoblock_keyword"])) {
            $infoblock_data = $this->get_infoblock_table_by_keyword($dataset["object_infoblock_keyword"])->get_row();
            $class_id = $infoblock_data["Class_ID"];
            $object_id = (int)nc_db()->get_var(
                "SELECT `Message_ID` FROM `Message$class_id` WHERE `Sub_Class_ID` = $infoblock_data[Sub_Class_ID] AND `Keyword` = '$dataset[object_keyword]'"
            ) ?: -1;

            if (!$object_id) {
                return false;
            }

            $component = $nc_core->get_component($class_id);
            $dataset["area_keyword"] = $component->get_full_page_area_keyword($object_id);
        }

        $properties = array(
            "Subdivision_ID" => $dataset["subdivision_id"],
            "AreaKeyword" => nc_array_value($dataset, "area_keyword", ""),
            "Parent_Sub_Class_ID" => nc_array_value($dataset, "container_id", 0),
        );

        if (isset($dataset["target_infoblock_id"]) && ($dataset["position"] === "before" || $dataset["position"] === "after")) {
            $properties["Priority"] = array(
                "Position" => $dataset["position"],
                "Sub_Class_ID" => $dataset["target_infoblock_id"],
            );
        }

        try {
            if ($dataset["position"] === "wrap_before" || $dataset["position"] === "wrap_after") {
                $properties["Parent_Sub_Class_ID"] = $nc_core->sub_class->wrap_with_flexbox($infoblock_id, $dataset["main_axis"]) ?: 0;
            }

            $infoblock_id = $nc_core->sub_class->duplicate($infoblock_id, $properties, nc_array_value($dataset, "copy_objects", true));
            $fields_to_update = [];

            if (isset($dataset["name"])) {
                $fields_to_update["Sub_Class_Name"] = $dataset["name"];
            }

            if (isset($dataset["english_name"])) {
                $fields_to_update["EnglishName"] = $dataset["english_name"];
            }

            if ($fields_to_update) {
                nc_db_table::make("Sub_Class")->where_id($infoblock_id)->update($fields_to_update);
            }

            nc_core::get_object()->sub_class->clear_cache();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function do_delete() {
        try {
            $infoblock_id = (int)$this->get_infoblock_table_by_keyword()->get_value("Sub_Class_ID");
            nc_core::get_object()->sub_class->delete($infoblock_id);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function update_custom_settings() {
        $dataset = $this->instruction->get_dataset();

        foreach ($this->get_infoblock_ids() as $infoblock_id) {
            $a2f = $this->get_custom_settings_instance($infoblock_id);
            $a2f->set_values($dataset["custom_settings"]);

            if (!$a2f->validate($a2f->get_values_as_array())) {
                return false;
            }

            nc_db_table::make("Sub_Class")
                       ->where_id($infoblock_id)
                       ->update(array("CustomSettings" => $a2f->get_values_as_string()));

        }

        return true;
    }

    /**
     * @return bool
     */
    private function update_fields() {
        $dataset = $this->instruction->get_dataset();

        return (bool)$this->get_infoblock_table_by_keyword()->update($dataset["fields"]);
    }

    /**
     * @return bool
     */
    private function update_files() {
        $dataset = $this->instruction->get_dataset();

        foreach ($this->get_infoblock_ids() as $infoblock_id) {
            $a2f = $this->get_custom_settings_instance($infoblock_id);

            foreach ($dataset["files"] as $key => $data) {
                if (strpos($data["file"], "http") !== false) {
                    $data["file"] = $this->save_remote_file($data["file"]);
                }

                $file_path = realpath($data["file"]);

                if ($data["file"] == "" || !file_exists($file_path)) {
                    continue;
                }

                $tmp_file_path = $this->get_tmp_directory() . "/" . basename($file_path);

                if ($file_path != realpath($tmp_file_path)) {
                    $file_moved = rename($file_path, $tmp_file_path);

                    if (!$file_moved) {
                        continue;
                    }
                }

                $data["file"] = $this->translate_tmp_directory_to_relative($tmp_file_path);
                $a2f->save(array($key => $data));
            }

            nc_db_table::make("Sub_Class")
                       ->where_id($infoblock_id)
                       ->update(array("CustomSettings" => $a2f->get_values_as_string()));
        }

        return true;
    }

    /**
     * @return void
     * @throws Exception
     */
    private function update_mixins() {
        $current_index_mixins = json_decode($this->get_infoblock_table_by_keyword()->get_value("Index_Mixin_Settings") ?: "[]", true);
        $dataset = $this->instruction->get_dataset();

        if (empty($current_index_mixins)) {
            $current_index_mixins[""] = array();
        }

        $dataset = $dataset["mixins"];
        $infoblock_id = $this->get_infoblock_table_by_keyword()->get_value("Sub_Class_ID");

        if (isset($dataset["Index_Mixin_Settings"])) {
            $index_mixin_settings = array();

            if (!isset($dataset["Index_Mixin_Settings"][""])) {
                $index_mixin_settings = array("" => $dataset["Index_Mixin_Settings"]);
            }

            $index_mixin_settings = array_replace_recursive($current_index_mixins, $index_mixin_settings);
            $nc_core = nc_core::get_object();

            if (isset($index_mixin_settings[""]["background"])) {
                $storage_path = $nc_core->FILES_FOLDER . "mixin/Index/$infoblock_id/netcat_background/";

                if (!file_exists($storage_path)) {
                    @mkdir($storage_path, $nc_core->DIRCHMOD, true);
                }

                foreach ($index_mixin_settings[""]["background"] as $breakpoint => $data) {
                    foreach ($data["settings"] as $i => $setting) {
                        if ($setting["background_url"]) {
                            $file_path = $this->save_remote_file($setting["background_url"]);
                            $file_name = basename($file_path);

                            if (rename($file_path, $storage_path . $file_name)) {
                                $index_mixin_settings[""]["background"][$breakpoint]["settings"][$i]["background_url"] = str_replace(
                                    $nc_core->DOCUMENT_ROOT,
                                    "",
                                    $storage_path .
                                    $file_name
                                );
                            }
                        }
                    }
                }
            }

            $this->get_infoblock_table_by_keyword()->update(
                array("Index_Mixin_Settings" => json_encode($index_mixin_settings, JSON_UNESCAPED_SLASHES))
            );
        }

        nc_tpl_mixin_cache::generate_block_files($infoblock_id, false);
    }

    private function apply_condition_finders($condition) {
        if (nc_array_value($condition, "value") instanceof nc_content_changer_finder) {
            $condition["value"] = (string)$condition["value"];
            if (!strlen($condition["value"])) {
                return null;
            }
        }

        if (!empty($condition["conditions"])) {
            foreach ($condition["conditions"] as $k => $v) {
                $condition["conditions"][$k] = $this->apply_condition_finders($v);
            }

            $condition["conditions"] = array_filter($condition["conditions"], "is_array");

            if (empty($condition["conditions"])) {
                return null;
            }
        }

        return $condition;
    }

    /**
     * @return bool
     */
    private function update_conditions() {
        $dataset = $this->instruction->get_dataset();
        $conditions = $dataset["conditions"];
        $conditions = $this->apply_condition_finders($conditions);

        if (empty($conditions)) {
            return false;
        }

        $infoblock_condition_translator = new nc_condition_infoblock_translator(
            $conditions,
            $this->get_infoblock_table_by_keyword()->get_value("Sub_Class_ID")
        );

        return (bool)$this->get_infoblock_table_by_keyword()->update(
            array(
                "Condition" => nc_array_json($conditions),
                "ConditionQuery" => $infoblock_condition_translator->get_sql_condition(),
            )
        );
    }

    /**
     * @param $file_url
     *
     * @return string
     */
    private function save_remote_file($file_url) {
        try {
            $http = new nc_http();
            $file_data = $http->make_get_request(str_replace(" ", "%20", $file_url));
            $file_path = $this->get_tmp_directory() . DIRECTORY_SEPARATOR . basename($file_url);

            file_put_contents($file_path, $file_data);

            return $file_path;
        } catch (nc_http_exception $e) {
            return "";
        }
    }


    /**
     * @param $infoblock_id
     *
     * @return nc_a2f|null
     */
    private function get_custom_settings_instance($infoblock_id) {
        $nc_core = nc_core::get_object();

        try {
            $custom_settings_template = $nc_core->sub_class->get_by_id($infoblock_id, "CustomSettingsTemplate");
            $custom_settings = $nc_core->sub_class->get_by_id($infoblock_id, "CustomSettings");

            $a2f = new nc_a2f($custom_settings_template, "CustomSettings");
            $a2f->set_value($custom_settings);

            return $a2f;
        } catch (Exception $e) {
            return null;
        }
    }

}
