<?php

class nc_tpl_mixin_type extends nc_tpl_mixin_record {

    protected $properties = array(
        'type' => null,
        'path' => null,
        // files
        'name' => null,
        'priority' => null,
        'scopes' => null,
    );

    protected $properties_read_language = array(
        'name' => 'Name',
    );

    protected $properties_read = array(
        'priority' => 'Priority',
    );

    protected $properties_include = array(
        'scopes' => 'Scopes.html',
    );

    /**
     * @return nc_tpl_mixin_type_collection
     */
    public static function get_all() {
        $all_mixin_types = new nc_tpl_mixin_type_collection();
        foreach (self::get_subfolders() as $mixin_type_folder) {
            $all_mixin_types->add(self::by_type($mixin_type_folder));
        }
        return $all_mixin_types->sort_by_property_value('priority', SORT_NUMERIC);
    }

    /**
     * @param $type
     * @return nc_tpl_mixin_type|null
     */
    protected static function by_type($type) {
        $folder = nc_tpl_mixin::get_path_folder($type);
        if ($folder) {
            return new self(array(
                'type' => $type,
                'path' => $folder,
            ));
        }
        return null;
    }

    /**
     * @return string
     */
    protected function get_default_name() {
        return $this->get('type');
    }

    /**
     * @param null|string $type
     * @return array
     */
    protected static function get_subfolders($type = null) {
        $path = nc_tpl_mixin::get_path_folder($type);
        if (!$path) {
            return array();
        }
        $subfolders = glob($path . '/*', GLOB_ONLYDIR);
        return $subfolders ? array_map('basename', $subfolders) : array();
    }

    /**
     * @return nc_tpl_mixin_collection
     * @throws nc_record_exception
     */
    public function get_mixins() {
        $type = $this->get('type');
        $mixins = new nc_tpl_mixin_collection();
        foreach (self::get_subfolders($type) as $mixin_keyword) {
            $mixins->add(nc_tpl_mixin::by_keyword($type, $mixin_keyword));
        }
        return $mixins->sort_by_property_value('priority', SORT_NUMERIC);
    }
}