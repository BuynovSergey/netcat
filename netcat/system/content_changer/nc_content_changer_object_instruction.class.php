<?php

/**
 * Класс-структура, описывающая инструкции для изменения сущности "объект" (Message)
 *
 * Описание поля $object_dataset:
 * 1) fields (обязательное):
 *  Если свойство action = "update", необходимо передать одномерный массив из набора обновляемых полей.
 *  Предполагается, что набор полей соответствует одному объекту,
 *  следовательно, на каждое обновление объекта требуется создавать отдельную инструкцию
 *  Пример: <code>fields: [Key => Value]</code>
 *
 *  Если свойство action = "create", необходимо перечислить массив из набора обновляемых полей (свойство fields поля $objects_dataset)
 *  Пример: <code>fields: [ [Key1 => Value1], [Key2 => Value2], ... ]</code>
 *
 *  Если свойство action = "substring_replace", необходимо перечислить массив из набора обновляемых полей (свойство fields поля
 *  $objects_dataset) Пример: <code>fields: [Text => [search => "НазваниеКомпании", replacement => "«Детский понос во плоти
 *  демонического пламени»"]]</code>
 *
 */
class nc_content_changer_object_instruction extends nc_content_changer_instruction {

    public static $entity_type = "object";

    /**
     * @var string
     */
    protected $object_keyword = "";

    /**
     * @var nc_content_changer_object_finder
     */
    protected $object_finder;

    /**
     * @param string $action
     * @param string|nc_content_changer_finder $infoblock_keyword
     * @param array $dataset
     * @param array|nc_content_changer_object_instruction|null $dependencies
     * @param string $component_keyword
     * @param string|nc_content_changer_object_finder $object_keyword_or_finder
     */
    public function __construct(
        $action = "update",
        $infoblock_keyword = "",
        $dataset = array(),
        $dependencies = null,
        $component_keyword = "",
        $object_keyword_or_finder = ""
    ) {
        parent::__construct($action, $infoblock_keyword, $dataset, $dependencies, $component_keyword);
        if ($object_keyword_or_finder) {
            $this->set_object_finder($object_keyword_or_finder);
        }
    }

    public function get_object_finder($component_keyword_or_id = null) {
        if ($component_keyword_or_id) {
            $object_finder = new nc_content_changer_object_finder($component_keyword_or_id);
            if ($this->object_keyword) {
                $object_finder->where('Keyword', $this->object_keyword);
            }
            return $object_finder;
        }

        if (!($this->object_finder instanceof nc_content_changer_object_finder)) {
            $this->object_finder = new nc_content_changer_object_finder($this->component_keyword);
            if ($this->object_keyword) {
                $this->object_finder->where('Keyword', $this->object_keyword);
            }
        }

        return $this->object_finder;
    }

    /**
     * @param string|nc_content_changer_object_finder $object_keyword_or_finder
     *
     * @return $this
     */
    public function set_object_finder($object_keyword_or_finder) {
        if (is_string($object_keyword_or_finder)) {
            $this->object_keyword = $object_keyword_or_finder;
        } else if ($object_keyword_or_finder instanceof nc_content_changer_object_finder) {
            $this->object_finder = $object_keyword_or_finder;
        } else {
            // throw new UnexpectedValueException(
            trigger_error(
                'Unsupported object finder type: expected nc_content_changer_object_finder or string, got ' .
                gettype($object_keyword_or_finder),
                E_USER_WARNING
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function get_object_keyword() {
        return $this->object_keyword;
    }

    /**
     * @param string $object_keyword
     *
     * @return void
     */
    public function set_object_keyword($object_keyword) {
        $this->object_keyword = $object_keyword;
    }

    /**
     * @return array
     */
    public function to_array() {
        return parent::to_array() + array("object_keyword" => $this->object_keyword);
    }

}
