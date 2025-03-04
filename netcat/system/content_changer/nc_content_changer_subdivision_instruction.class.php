<?php

/**
 * Клас-структура, описывающая инструкции для изменения сущности "раздел" (Subdivision)
 * Если необходимо создать древовидную структуру разделов, то в dataset следует передать Parent_Sub_ID,
 * принимающий либо существующий идентификатор раздела, либо ключевое слово раздела
 *
 * Перед созданием дочерних разделов требуется в параметр $dependencies передать инструкцию по созданию родительского раздела,
 * с указанием в dataset свойства EnglishName
 * *
 * Пример:
 * <pre>
 *   $keyword = "root_subdivision_keyword";
 *   $parent_subdivision_instruction = new nc_content_changer_subdivision_instruction("", "create", [
 *      "Subdivision_Name" => "Родительский раздел",
 *      "EnglishName" => $keyword,
 *      "Priority" => 1,
 *   ]);
 *
 *   // Перед созданием дочерних разделов, будет создан корневой
 *   $children = new nc_content_changer_subdivision_instruction("", "create", [
 *      [
 *      "Subdivision_Name" => "Дочерний раздел",
 *      "Parent_Sub_ID" => $keyword,
 *      "Priority" => 2,
 *      "ncImage" => "https://netcat.ru/"
 *       ],
 *    ], $parent_subdivision_instruction);
 * </pre>
 *
 * При передаче файловых полей следует учитывать, если они находятся на сервере, то путь к ним должен быть без DOCUMENT_ROOT
 * Пример:
 *  <pre>
 *      "ncImage" => "https://netcat.ru/logo150x150.png" передача файла по url
 *      "ncImage" => "/netcat/files/tmp/image.php" передача файла на сервере, где запущена инструкция
 *  </pre>
 */
class nc_content_changer_subdivision_instruction extends nc_content_changer_instruction {

    /**
     * @var string
     */
    public static $entity_type = "subdivision";

    /**
     * @inheritDoc
     */
    public function __construct($subdivision_keyword = null, $action = "update", $dataset = array(), $dependencies = null) {
        parent::__construct($action, "", $dataset, $dependencies);
        $this->subdivision_keyword = $subdivision_keyword;
    }

    /**
     * @inheridoc
     */
    public function to_array() {
        return array(
            "action" => $this->action,
            "entity_type" => self::$entity_type,
            "dataset" => $this->get_dataset(),
            "subdivision_keyword" => $this->subdivision_keyword,
        );
    }


}
