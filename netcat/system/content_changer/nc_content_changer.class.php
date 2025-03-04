<?php

abstract class nc_content_changer {

    /**
     * @var nc_content_changer_instruction
     */
    protected $instruction;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $tmp_folder = "init_content";

    /**
     * @var array
     */
    private $infoblocks_cache = array();


    /**
     * @param nc_content_changer_instruction $instruction
     */
    public function __construct(nc_content_changer_instruction $instruction) {
        $this->action = $instruction->get_action();
        $this->instruction = $instruction;
    }

    /**
     * @return bool
     */
    abstract protected function validate();

    /**
     * @return void
     */
    public function apply() {
        if (!$this->validate()) {
            return;
        }

        $method = "do_" . $this->action;

        if (method_exists($this, $method)) {
            $this->$method();
        }
    }

    /**
     * @return bool
     */
    protected function resolve_dependencies() {
        if (!$this->instruction->has_dependencies()) {
            return true;
        }

        $resolved = true;
        $current_instruction = $this->instruction;
        $dependencies = $this->instruction->get_dependencies();

        foreach ($dependencies as $dependency) {
            $method = "do_" . $dependency->get_action();

            if (method_exists($this, $method)) {
                try {
                    $this->instruction = $dependency;
                    $resolved = $this->$method();
                    $this->instruction = $current_instruction;
                    array_shift($dependencies);
                    $this->instruction->set_dependencies($dependencies);
                } catch (Exception $e) {
                    $resolved = false;
                    $this->instruction = $current_instruction;
                }
            }
        }

        return $resolved;
    }

    /**
     * @param string|nc_content_changer_infoblock_finder|null $keyword_or_finder
     *
     * @return nc_db_table
     */
    protected function get_infoblock_table_by_keyword($keyword_or_finder = null) {
        if (is_string($keyword_or_finder) && $keyword_or_finder !== "") {
            $sub_class_table = nc_db_table::make("Sub_Class")->where("EnglishName", $keyword_or_finder);
        } else if ($keyword_or_finder instanceof nc_content_changer_infoblock_finder) {
            $sub_class_table = $keyword_or_finder->get_table();
        } else if ($this->instruction->get_infoblock_finder()) {
            $sub_class_table = $this->instruction->get_infoblock_finder()->get_table();
        } else {
            $sub_class_table = nc_db_table::make("Sub_Class");
        }

        if ($this->instruction->get_component_keyword()) {
            $sub_class_table->where("Class_ID", $this->get_component_id_by_keyword());
        }

        return $sub_class_table;
    }

    /**
     * @param string|null $keyword
     *
     * @return null|nc_db_table
     */
    protected function get_subdivision_table_by_keyword($keyword = null) {
        return nc_db_table::make("Subdivision")->where("EnglishName", $keyword ?: $this->instruction->get_subdivision_keyword());
    }

    /**
     * @return int
     */
    protected function get_component_id_by_keyword() {
        $keyword = $this->instruction->get_component_keyword();

        return (int)nc_db()->get_var("SELECT `Class_ID` FROM `Class` WHERE `Keyword` = '$keyword'") ?: -1;
    }

    /**
     * @param string|null $keyword
     *
     * @return array
     */
    protected function get_infoblock_ids($keyword = null) {
        $keyword = $keyword ?: $this->instruction->get_infoblock_finder();

        $cache_key = md5(serialize($keyword));

        if (isset($this->infoblocks_cache[$cache_key])) {
            return $this->infoblocks_cache[$cache_key];
        }

        $this->infoblocks_cache[$cache_key] = $this->get_infoblock_table_by_keyword($keyword)->get_list("Sub_Class_ID");

        return $this->infoblocks_cache[$cache_key];
    }

    /**
     * @return string
     */
    protected function get_tmp_directory() {
        $nc_core = nc_core::get_object();
        $dir = $nc_core->TMP_FOLDER . $this->tmp_folder;

        if (!is_dir($dir)) {
            mkdir($dir, $nc_core->DIRCHMOD, true);
        }

        return realpath($dir);
    }

    /**
     * @param $path
     *
     * @return string
     */
    protected function translate_tmp_directory_to_relative($path) {
        return str_replace(realpath(nc_core::get_object()->DOCUMENT_ROOT), "", realpath($path));
    }

    /**
     * @return void
     */
    protected function delete_tmp_directory() {
        nc_delete_dir(nc_core::get_object()->TMP_FOLDER . $this->tmp_folder);
    }
}
