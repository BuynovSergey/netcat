<?php

abstract class nc_content_changer_instruction {

    public static $entity_type = "";


    /**
     * delete|create|update|substring_replace
     *
     * @var string
     */
    protected $action;

    /**
     * Является приоритетным критерием для поиска как инфоблоков, так и объектов
     *
     * @var nc_content_changer_infoblock_finder
     */
    protected $infoblock_finder;

    /**
     * Ключевое слово инфоблока (для поиска напрямую не используется — для выборки используется infoblock finder).
     * По факту используется только в обратном преобразовании в массив.
     *
     * @var string
     */
    protected $infoblock_keyword;

    /**
     * @var string
     */
    protected $subdivision_keyword = "";

    /**
     * @var string
     */
    protected $component_keyword = "";

    /**
     * Идентификатор сайта
     * Если хранится int, то поиск по Catalogue_ID
     * если хранится string, поиск по Domain
     * если null, то будет возвращен id текущего сайта
     *
     * @var null|int|string
     */
    protected $site_keyword;

    /**
     * @var array
     */
    private $dataset;

    /**
     *
     * Хранит список инструкций, которые будут исполнены, перед запуском основной инструкции
     * Например: перед добавлением объекта в инфоблок, надо удалить имеющиеся в нем объекты
     *
     * Если инструкции-зависимости выполнятся безуспешно, то основная инструкция не будет исполнена
     *
     * Зависимости разрешаются в порядке их передачи в параметры конструктора
     *
     * @var null|nc_content_changer_object_instruction[]
     */
    private $dependencies;

    /**
     * @param string $action
     * @param string|nc_content_changer_infoblock_finder $infoblock_keyword_or_finder
     * @param array $dataset
     * @param array|nc_content_changer_object_instruction|null $dependencies
     * @param string $component_keyword
     */
    public function __construct(
        $action = "update",
        $infoblock_keyword_or_finder = "",
        $dataset = array(),
        $dependencies = null,
        $component_keyword = ""
    ) {
        $this->action = $action;
        $this->set_infoblock_finder($infoblock_keyword_or_finder);
        $this->dataset = $dataset;
        $this->dependencies = $this->wrap_to_array($dependencies);
        $this->component_keyword = $component_keyword;
    }

    /**
     * @return nc_content_changer_infoblock_finder
     */
    public function get_infoblock_finder() {
        if (!($this->infoblock_finder instanceof nc_content_changer_infoblock_finder)) {
            $this->infoblock_finder = new nc_content_changer_infoblock_finder();
            $this->infoblock_finder->where('EnglishName', $this->infoblock_keyword);
        }
        return $this->infoblock_finder;
    }

    /**
     * @return bool
     */
    public function has_infoblock_finder() {
        return !empty($this->infoblock_keyword) || $this->infoblock_finder instanceof nc_content_changer_infoblock_finder;
    }

    /**
     * @return string
     */
    public function get_subdivision_keyword() {
        return $this->subdivision_keyword;
    }

    /**
     * @return int|string|null
     */
    public function get_site_keyword() {
        return $this->site_keyword;
    }

    /**
     * @return string
     */
    public function get_action() {
        return $this->action;
    }

    /**
     * @return string
     */
    public function get_entity_type() {
        return static::$entity_type;
    }

    /**
     * @return array
     */
    public function get_dataset() {
        return $this->dataset;
    }

    /**
     * @return bool
     */
    public function has_dependencies() {
        return is_array($this->dependencies) && count($this->dependencies) > 0;
    }

    /**
     * @return null|nc_content_changer_object_instruction[]
     */
    public function get_dependencies() {
        return $this->dependencies;
    }

    /**
     * @param string|nc_content_changer_infoblock_finder $infoblock_keyword_or_finder
     *
     * @return nc_content_changer_instruction
     */
    public function set_infoblock_finder($infoblock_keyword_or_finder) {
        if (is_string($infoblock_keyword_or_finder)) {
            $this->infoblock_keyword = $infoblock_keyword_or_finder;
        } else if ($infoblock_keyword_or_finder instanceof nc_content_changer_infoblock_finder) {
            $this->infoblock_finder = $infoblock_keyword_or_finder;
        } else {
            // throw new UnexpectedValueException(
            trigger_error(
                'Unsupported infoblock finder type: expected nc_content_changer_infoblock_finder or string, got ' .
                gettype($infoblock_keyword_or_finder),
                E_USER_WARNING
            );
        }

        return $this;
    }

    /**
     * @param string $keyword
     *
     * @return void
     */
    public function set_subdivision_keyword($keyword) {
        $this->subdivision_keyword = $keyword;
    }

    /**
     * @return string
     */
    public function get_component_keyword() {
        return $this->component_keyword;
    }

    /**
     * @param string $component_keyword
     *
     * @return void
     */
    public function set_component_keyword($component_keyword) {
        $this->component_keyword = $component_keyword;
    }

    /**
     * @param string $action
     *
     * @return nc_content_changer_instruction
     */
    public function set_action($action) {
        $this->action = $action;

        return $this;
    }

    /**
     * @param array|mixed $dataset
     *
     * @return nc_content_changer_instruction
     */
    public function set_dataset($dataset) {
        $this->dataset = $dataset;

        return $this;
    }

    /**
     * @param nc_content_changer_instruction[] $dependencies
     *
     * @return nc_content_changer_instruction
     */
    public function set_dependencies(array $dependencies) {
        $this->dependencies = $dependencies;

        return $this;
    }

    /**
     * @return array
     */
    public function to_array() {
        return array(
            "entity_type" => static::$entity_type,
            "action" => $this->action,
            "dataset" => $this->dataset,
            "dependencies" => $this->dependencies,
            "infoblock_keyword" => $this->infoblock_keyword,
            "subdivision_keyword" => $this->subdivision_keyword,
            "component_keyword" => $this->component_keyword,
        );
    }

    /**
     * @return string
     */
    public function to_json() {
        return json_encode($this->to_array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param $input
     *
     * @return array|mixed
     */
    protected function wrap_to_array($input) {
        if ($input == null) {
            return $input;
        }

        if (is_array($input)) {
            return count($input) == 1 ? array($input) : $input;
        } else {
            return array($input);
        }
    }

}
