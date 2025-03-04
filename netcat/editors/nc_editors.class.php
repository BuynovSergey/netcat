<?php

class nc_Editors {
    protected $core, $editor_id;
    protected $editor, $html;
    // имя textarea и значение
    protected $name, $value;

    /**
     * Набор панелей
     * @var int
     */
    protected $panel = 0;

    public function  __construct($editor_id, $name, $value = '', $panel = 0) {
        $this->core = nc_Core::get_object();
        $this->name = $name;
        $this->value = $value;
        $this->panel = $panel;

        switch($editor_id) {
            case 2:
                $this->_make_fckeditor();
                break;
            case 3:
                $this->_make_ckeditor4();
                break;
            case 4:
                $this->_make_tinymce();
                break;
        }
    }

    public function get_html() {
        return $this->html;
    }

    protected function _make_fckeditor() {
        $lang = $this->core->lang->detect_lang(1);
        if ($lang === 'ru') {
            $lang = $this->core->NC_UNICODE ? 'ru_utf8' : 'ru_cp1251';
        }

        if (!class_exists('FCKeditor')) {
            include_once $this->core->ROOT_FOLDER . 'editors/FCKeditor/fckeditor.php';
        }

        $this->editor = new FCKeditor($this->name);
        $this->editor->BasePath = $this->core->SUB_FOLDER . $this->core->HTTP_ROOT_PATH . 'editors/FCKeditor/';
        $this->editor->Config['SmileyPath'] = $this->core->SUB_FOLDER . $this->core->HTTP_TEMPLATE_PATH . 'images/smiles/';
        $this->editor->ToolbarSet = 'NetCat1';
        $this->editor->Width = '100%';
        $this->editor->Height = '320';
        $this->editor->Value = $this->value;
        $this->editor->Config['DefaultLanguage'] = $lang;
        if ($this->core->AUTHORIZATION_TYPE === 'session') {
            $this->editor->Config['sid'] = session_id();
        }
        $this->html = $this->editor->CreateHtml();
    }

    protected function _make_ckeditor4() {
        if (!class_exists('CKEditor')) {
            include_once $this->core->ROOT_FOLDER . 'editors/ckeditor4/ckeditor.php';
        }

        $this->editor = new CKEditor($this->name, $this->value, $this->panel);
        $this->html = $this->editor->CreateHtml();
    }

    protected function _make_tinymce() {
        if (!class_exists('TinyMCE')) {
            include_once $this->core->ROOT_FOLDER . 'editors/tinymce/tinymce.php';
        }

        $language = $this->core->lang->detect_lang(1);
        $language = $language === 'ru' ? 'ru' : '';

        $this->editor = new TinyMCE($this->name, $this->value, $language);
        $this->html = $this->editor->CreateHtml();
    }

    public static function fckeditor_exists() {
        return file_exists(nc_Core::get_object()->ROOT_FOLDER . 'editors/FCKeditor/fckeditor.php');
    }
}