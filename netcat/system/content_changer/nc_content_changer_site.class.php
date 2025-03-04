<?php

class nc_content_changer_site extends nc_content_changer {

    /**
     * @var nc_db_table
     */
    private static $site_table;

    /**
     * @inheritDoc
     */
    public function __construct(nc_content_changer_instruction $instruction) {
        parent::__construct($instruction);
        self::$site_table = nc_db_table::make("Catalogue");
    }


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
    protected function do_update() {
        $dataset = $this->instruction->get_dataset();

        if (isset($dataset["mixins"])) {
            try {
                $this->update_mixins();

                return true;
            } catch (Exception $e) {
                return true;
            }
        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function update_mixins() {
        $dataset = $this->instruction->get_dataset();
        $site_data = $this->get_site_table()->get_row();

        if (!$site_data) {
            return false;
        }

        $current_index_mixins = json_decode($site_data["MainArea_Mixin_Settings"] ?: "[]", true);

        if (empty($current_index_mixins)) {
            $current_index_mixins[""] = array();
        }

        if (isset($dataset["mixins"]["text"])) {
            $this->prepare_text_mixin_to_update($dataset);
        }

        $index_mixin_settings = array_replace_recursive($current_index_mixins, array("" => $dataset["mixins"]));
        $this->get_site_table()->update(array("MainArea_Mixin_Settings" => json_encode($index_mixin_settings, JSON_UNESCAPED_SLASHES)));

        nc_core::get_object()->catalogue->load_all();
        nc_tpl_mixin_cache::generate_site_files($site_data["Catalogue_ID"], false);

        return true;
    }

    /**
     * @return nc_db_table
     */
    private function get_site_table() {
        $site_keyword = $this->instruction->get_site_keyword();

        if ($site_keyword == null) {
            return self::$site_table->where("Catalogue_ID", (int)nc_core::get_object()->catalogue->get_current("Catalogue_ID"));
        }

        return self::$site_table->where(is_numeric($site_keyword) ? "Catalogue_ID" : "Domain", $site_keyword);
    }

    /**
     * @return void
     */
    private function prepare_text_mixin_to_update(array &$dataset) {
        $fonts_map = array();
        $fonts = nc_tpl_font::get_available_fonts();

        foreach ($fonts as $font) {
            $fonts_map[$font["name"]] = $font;
        }

        foreach ($dataset["mixins"]["text"] as $breakpoint => $data) {
            foreach ($data["settings"] as $font_type => $parameter) {
                if (!isset($fonts_map[$parameter["font_family"]])) {
                    continue;
                }

                $dataset["mixins"]["text"][$breakpoint]["settings"][$font_type]["asset"] = nc_array_value(
                    $fonts_map[$parameter["font_family"]],
                    "asset",
                    ""
                );

                if (!isset($dataset["mixins"]["text"][$breakpoint]["settings"][$font_type]["font_family_fallback"])) {
                    $dataset["mixins"]["text"][$breakpoint]["settings"][$font_type]["font_family_fallback"] = nc_array_value(
                        $fonts_map[$parameter["font_family"]],
                        "fallback",
                        ""
                    );
                }
            }
        }
    }

}
