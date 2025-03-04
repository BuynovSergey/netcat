<?php

/**
 * Класс для врезок (вспомогательных шаблонов, partials) в макетах дизайна
 */
class nc_partial_template_partial extends nc_partial {

    /** @var string префикс комментария (должен быть определён в классе-наследнике */
    protected $partial_comment_id_prefix = 't';
    /** @var int счётчик вложенных фрагментов с отложенной загрузкой (используется в ID комментария) */
    static protected $partial_last_sequence_number = 0;
    /** @var int счётчик вложенных вызовов (фрагмент внутри фрагмента) */
    static protected $partial_nesting_level = 0;

    /** @var bool если в настройках врезки разрешена асинхронная загрузка,
     *       всегда будет добавляться разметка */
    protected $always_add_async_markup_when_allowed = true;

    /** @var  int */
    protected $template_id;

    /** @var string  */
    protected $keyword;

    /** @var string  */
    protected $partial_file;

    /**
     *
     * @param int $template_id
     * @param $partial_keyword
     * @param array $data
     */
    public function __construct($template_id, $partial_keyword, array $data = array()) {
        $nc_core = nc_core::get_object();

        $this->template_id = $nc_core->template->get_root_id($template_id);
        $this->keyword = $partial_keyword;

        $partial_file = $nc_core->template->get_partials_path($this->template_id, $this->keyword);
        if (file_exists($partial_file)) {
            $this->partial_file = $partial_file;
        } else {
            trigger_error('File not found: ' . htmlspecialchars($partial_file), E_USER_WARNING);
        }

        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function exists() {
        return $this->partial_file ? true : false;
    }

    /**
     * Рендеринг шаблона
     *
     * @return string
     */
    public function get_content() {
        if (!$this->partial_file) {
            return '';
        }

        // Переменные для использования внутри partial
        extract($GLOBALS);
        extract($this->data);

        $nc_core = nc_core::get_object();
        $db = $nc_core->db;
        $nc_partial_async = $this->is_async_partial_request; // врезка загружена отдельно через /netcat/partial.php?

        ob_start();

        $nc_core->page->update_last_modified_if_timestamp_is_newer(filemtime($this->partial_file), 'template');
        include $this->partial_file;

        return ob_get_clean() ?: '';
    }

    /**
     * Возвращает сведения о врезке из таблицы Template_Partial
     *
     * @param string $property
     * @return mixed
     */
    protected function get_meta($property) {
        return nc_core::get_object()->template->get_partials_data($this->template_id, $this->keyword, $property);
    }

    /**
     * Проверяет, разрешено ли использование врезки отдельно от макета дизайна
     * (через /netcat/partial.php).
     *
     * @return bool
     */
    public function is_async_loading_allowed() {
        return (bool)$this->get_meta('EnableAsyncLoad');
    }

    /**
     * @param $name
     * @param array $data
     * @return nc_partial_template_partial
     */
    public function partial($name, $data = array()) {
        $data = array_merge(array('nc_partial_inside_partial' => true), $this->data, $data);
        return nc_core::get_object()->template->get_file_template($this->template_id)->partial($name, $data);
    }

    /**
     * Возвращает параметр src для загрузки этого фрагмента через partial.php
     *
     * @return string
     */
    protected function get_src() {
        return $this->keyword;
    }

}