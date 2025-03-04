<?php

class nc_tpl_fields {

    public $standart = null;
    public $Settings = array();
    private $template = null;
    private static $fields = array(
        'Template' => array(
            'Header' => null,
            'Footer' => null,
            'Settings' => null,
            'RequiredAssets' => array(
                'is_optional' => true,
            ),
        ),

        'Class' => array(
            'FormPrefix' => array(
                'name' => 'OBJECTSLIST_PREFIX', // константа CONTROL_CLASS_CLASS_OBJECTSLIST_PREFIX. Этот класс загружается раньше языковых файлов, константы не определены...
                'path' => 'edit',
            ),
            'RecordTemplate' => array(
                'name' => 'OBJECTSLIST_BODY',
                'path' => 'edit',
            ),
            'FormSuffix' => array(
                'name' => 'OBJECTSLIST_SUFFIX',
                'path' => 'edit',
            ),
            'RecordTemplateFull' => array(
                'name' => 'OBJECTSLIST_SHOWOBJ_PAGEBODY',
                'path' => 'edit',
            ),
            'Settings' => array(
                'name' => 'OBJECTSLIST_SHOWOBJ_SYSTEM',
                'path' => 'edit',
            ),
            'AddTemplate' => array(
                'name' => 'FORMS_ADDFORM',
                'path' => 'customadd',
            ),
            'AddCond' => array(
                'name' => 'FORMS_ADDRULES',
                'path' => 'customadd',
            ),
            'AddActionTemplate' => array(
                'name' => 'FORMS_ADDLASTACTION',
                'path' => 'customadd',
            ),
            'EditTemplate' => array(
                'name' => 'FORMS_EDITFORM',
                'path' => 'customedit',
            ),
            'EditCond' => array(
                'name' => 'FORMS_EDITRULES',
                'path' => 'customedit',
            ),
            'EditActionTemplate' => array(
                'name' => 'FORMS_EDITLASTACTION',
                'path' => 'customedit',
            ),
            'CheckActionTemplate' => array(
                'name' => 'FORMS_ONONACTION',
                'path' => 'customedit',
            ),
            'DeleteTemplate' => array(
                'name' => 'FORMS_DELETEFORM',
                'path' => 'customdelete',
            ),
            'DeleteCond' => array(
                'name' => 'FORMS_DELETERULES',
                'path' => 'customdelete',
            ),
            'DeleteActionTemplate' => array(
                'name' => 'FORMS_ONDELACTION',
                'path' => 'customdelete',
            ),
            'FullSearchTemplate' => array(
                'name' => 'FORMS_QSEARCH',
                'path' => 'customsearch',
            ),
            'SearchTemplate' => array(
                'name' => 'FORMS_SEARCH',
                'path' => 'customsearch',
            ),
            'RequiredAssets' => array(
                'is_optional' => true,
            ),
            'RecordMockData' => array(
                'is_optional' => true,
            ),
            'SiteStyles' => array(
                'extension' => '.css',
                'is_optional' => true,
            ),
            'BlockSettingsDialog' => array(
                'is_optional' => true,
            ),
        ),

        'Widget_Class' => array(
            'Template' => null,
            'Settings' => null,
            'AddForm' => null,
            'EditForm' => null,
            'AfterSaveAction' => null,
            'BeforeSaveAction' => null,
        ),
    );

    // Список файлов, для которых в get_path() уже было получено время изменения
    private static $processed_files_timestamps = array();

    public function __construct(nc_tpl $template) {
        $this->template = $template;
        $this->standart = nc_array_value(self::$fields, $this->template->type);
        if (!is_array($this->standart)) {
            $this->standart = $this->{$this->get_method_name()}();
        }
    }

    private function get_method_name() {
        $method_name = "get_{$this->template->id}_fields";
        return !method_exists('nc_tpl_fields', $method_name) ? 'get_module_fields' : $method_name;
    }

    private function get_comments_fields() {
        $default_path = $this->template->path_to_root_folder.'comments/0';

        $files = scandir(nc_standardize_path_to_folder($default_path));
        $fields = array();

        foreach ($files as $file) {
            if (strpos($file, '.') === 0) { continue; }
            $fields[str_replace($this->template->extension, '', $file)] = null;
        }

        return $fields;
    }

    private function get_module_fields () {
        $files = scandir(nc_standardize_path_to_folder($this->template->absolute_path));
        $fields = array();

        foreach ($files as $file) {
            if ($file[0] == '.') {
                continue;
            }

            $basename = str_replace($this->template->extension, '', $file);

            // Игнорируем файлы без расширений
            if ($basename != $file) {
                $fields[$basename] = null;
            }
        }

        return $fields;
    }

    protected function get_field_file_extension($field) {
        return isset($this->standart[$field]['extension'])
                   ? $this->standart[$field]['extension']
                   : $this->template->extension;
    }

    public function get_path($field) {
        $path = $this->template->absolute_path . $field . $this->get_field_file_extension($field);
        if (!isset(self::$processed_files_timestamps[$path])) {
            nc_Core::get_object()->page->update_last_modified_if_timestamp_is_newer(
                $this->get_last_modified_timestamp_by_path($path),
                'template'
            );
            self::$processed_files_timestamps[$path] = true;
        }
        return $path;
    }

    public function get_parent_path($field) {
        return nc_get_path_to_parent_folder($this->template->absolute_path) . $field . $this->get_field_file_extension($field);
    }

    /**
     * @param string $path
     * @return int
     */
    public function get_last_modified_timestamp_by_path($path) {
        if (file_exists($path)) {
            return (int)filemtime($path);
        }

        return 0;
    }

    public function has_field($field_name) {
        return isset($this->standart[$field_name]);
    }

}