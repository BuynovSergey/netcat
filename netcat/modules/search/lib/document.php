<?php

/* $Id: document.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Документ
 *
 * В случае использования поискового индекса (напр., Lucene) часть данных хранится
 * в таблице Search_Documents (текст документа), а для индекс используется для
 * выполнения поискового запроса.
 *
 * Сохранение в индекс при вызове $nc_search_document->save() не производится.
 * Сохранение в индекс производится индексатором через интерфейс провайдера.
 */
class nc_search_document extends nc_search_data_persistent {

    protected $table_name = "Search_Document";

    /** @var array   Основные свойства документа */
    protected $properties = array(
            'id' => null,
            'content_type' => 'text/html',
            'site_id' => null,
            'sub_id' => null,
            'url' => null, // полный URL
            'path' => null, // только путь, без имени сайта
            'language' => '',
            'ancestor_ids' => null, // ID родителей и этого раздела в виде "sub1,sub11,sub111"
            'title' => null,
            'intact_content' => null, // без извлечений из контента (т.е. включая текст, сохраняемый в других полях)
            'saved_content' => null, // «виртуальное» свойство — обрезанный в соответствии с настройками модуля текст, сохраняемый в БД
            'content' => null, // контент, который попадёт в поле 'content' индекса
            'meta' => array(), // значения дополнительных полей (is_retrievable), доступные в результатах поиска
            'sitemap_include' => false,
            'sitemap_changefreq' => '',
            'sitemap_priority' => 0,
            'last_updated' => null, // время обновления в индексе
            'last_modified' => null, // значение http-заголовка last-modified
            'to_delete' => false,
            'hash' => null,
//      'access_level' => null,
    );

    /** @var array  Отображение в БД */
    protected $mapping = array(
            'id' => 'Document_ID',
            'site_id' => 'Catalogue_ID',
            'sub_id' => 'Subdivision_ID',
            'ancestor_ids' => 'Ancestors',
            'path' => 'Path',
            'title' => 'Title',
            'saved_content' => 'Content',
            'meta' => 'Meta',
            'content_type' => 'ContentType',
            'sitemap_include' => 'IncludeInSitemap',
            'sitemap_changefreq' => 'SitemapChangefreq',
            'sitemap_priority' => 'SitemapPriority',
            'language' => 'Language',
            'last_modified' => 'LastModified',
            'last_updated' => 'LastUpdated',
            'to_delete' => 'ToDelete',
            'hash' => 'Hash',
    );

    /** @var array */
    protected $serialized_properties = array('meta');

    /**
     * Поля документа (в индексе). Отличие от $properties в том, что это сведения для
     * индекса (например, часть контента, заключённая в какой-либо тэг и имеющая
     * вес, отличный от остального контента).
     *
     * Создаются парсером (список полей задаётся настройками модуля) + $this->generate_fields().
     *
     * При этом часть опций ($this->properties) тоже может при сохранении в индекс
     * сохраняться в виде полей, см. $this->field_mapping
     *
     * @var array assoc. array: key = field name, value = nc_search_field
     */
    protected $fields = array();

    /**
     * Отображение в индекс
     * key: option name
     * value: array with field settings
     */
    protected $field_mapping = array(
            'id' => array('name' => 'doc_id', 'is_normalized' => false, 'is_stored' => true),
            'site_id' => array('name' => 'site_id', 'is_normalized' => false),
            'sub_id' => array('name' => 'sub_id', 'is_normalized' => false),
            'ancestor_ids' => array('name' => 'ancestor'),
            'title' => array('name' => 'title', 'weight' => 3, 'is_searchable' => true,
                    'query' => 'title', 'query_scope' => 'document'),
            'content' => array('name' => 'content', 'is_searchable' => true),
            'language' => array('name' => 'language', 'is_normalized' => false),
            'last_modified' => array('name' => 'last_modified', 'is_normalized' => false, 'is_sortable' => true),
//      'access_level' => array('name' => 'access_level'),
    );

    /**
     * Получить документ из БД по URL
     * @param integer $site_id
     * @param string $path
     * @return self|FALSE
     */
    static public function get_by_path($site_id, $path) {
        $doc = new self;
        return $doc->load_where('site_id', $site_id, 'path', $path);
    }

    /**
     * Получить документ из БД по URL (только поля 'id' и 'hash')
     * @static
     * @param $site_id
     * @param $path
     * @return self|FALSE
     */
    static public function get_hash_by_path($site_id, $path) {
        $doc = new self;
        return $doc->select_from_database("SELECT `Document_ID`, `Hash`
                                        FROM `{$doc->get_table_name()}`
                                       WHERE `Catalogue_ID` = " . (int)$site_id . "
                                         AND `Path` = '" . nc_search_util::db_escape($path) . "'
                                       LIMIT 1");
    }

    /**
     * 
     */
    public function __construct(array $values = null) {
        $this->generate_fields();
        parent::__construct($values);
    }

    /**
     * Создание полей по $this->field_mapping
     */
    protected function generate_fields() {
        foreach ($this->field_mapping as $option_name => $field_options) {
            $this->add_field(new nc_search_field($field_options));
        }
    }

    /**
     * Получить имя поля в индексе по названию опции
     * @param string $option_name
     * @throws nc_search_exception
     * @return string
     */
    protected function option_to_field($option_name) {
        if (!isset($this->field_mapping[$option_name])) {
            throw new nc_search_exception("Wrong option name '$option_name'");
        }
        return $this->field_mapping[$option_name]["name"];
    }

    /**
     * Получить имя опции, связанной с полем в индексе
     * @param string $field_name
     * @throws nc_search_exception
     * @return string
     */
    protected function field_to_option($field_name) {
        foreach ($this->field_mapping as $option_name => $mapping) {
            if ($mapping["name"] == $field_name) {
                return $option_name;
            }
        }
        throw new nc_search_exception("Wrong index field name '$field_name'");
    }

    /**
     * Переопределяет nc_search_data::set().
     * Устанавливает также значение поля, если оно связано с $option
     * @param string $option
     * @param mixed $value
     * @param bool $add_new_option   (для nc_search_document параметр игнорируется)
     * @return nc_search_document
     */
    public function set($option, $value, $add_new_option = false) {
        parent::set($option, $value, true);
        if (isset($this->field_mapping[$option])) {
            $this->set_field_value($this->option_to_field($option), $value, false);
        }
        return $this;
    }

    /**
     * @param string $option
     * @return mixed
     */
    public function get($option) {
        if ($option == 'saved_content' && ($max_length = nc_search::get_setting('MaxDocumentPreviewTextLengthInKbytes'))) {
            $max_length *= 1024;
            $content = $this->properties['intact_content'];
            if (strlen($content) > $max_length) {
                $content = substr($content, 0, strrpos($content, " ", $max_length-strlen($content)));
            }
            return $content;
        }
        return parent::get($option);
    }


    /**
     * Получить все поля (используется при сохранении в provider_*)
     * @return array array of nc_search_field
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Добавляет поле (для индекса).
     *
     * Внимание, может перезаписать существующее поле без предупреждения
     * (it’s a feature / by design)
     *
     * @param nc_search_field $field
     * @return nc_search_document
     */
    public function add_field(nc_search_field $field) {
        $field_name = $field->get('name');
        $this->fields[$field_name] = $field;
        if (!isset($this->field_mapping[$field_name])) { // required for option_to_field and vice versa
            $this->field_mapping[$field_name] = array("name" => $field_name);
        }
        return $this;
    }

    /**
     * @param string $field_name
     * @return nc_search_field
     */
    protected function get_field($field_name) {
        return $this->fields[$field_name];
    }

    /**
     * @param string $field_name
     * @return mixed
     */
    public function get_field_value($field_name) {
        return $this->get_field($field_name)->get('value');
    }

    /**
     * Используется парсером. Пользователю следует использовать $this->set()
     * @param string $field_name
     * @param mixed $value
     * @param boolean $also_set_option   if TRUE, calls $this->set($option) as well
     * @return nc_search_document
     */
    public function set_field_value($field_name, $value, $also_set_option = true) {
        $field = $this->get_field($field_name);
        $field->set('value', $value);

        if ($field->get('is_retrievable') && strlen($value)) { // значения, доступные в результатах, сохраняются в БД
            $this->properties['meta'][$field_name] = $value;
        }

        if ($also_set_option) {
            $this->set($this->field_to_option($field_name), $value);
        }
        return $this;
    }

    /**
     * Сгенерировать хэш для документа. (Должно использоваться непосредственно
     * перед сохранением)
     * Также обновляет значение 'hash' у документа.
     * @return string SHA1 (40 char hex)
     */
    public function generate_hash() {
        $hash = sha1(join("\n/__\n/", array(
                                $this->get('site_id'),
                                $this->get('path'),
                                $this->get('title'),
                                $this->get('intact_content'),
                                $this->get('last_modified'),
                        )));

        $this->set('hash', $hash);
        return $hash;
    }

    /**
     * For the extreme logging
     */
    public function dump() {
        $res = "";
        foreach ($this->properties as $k => $v) {
            $res .= str_pad(" [ $k ] ", 50, "-", STR_PAD_BOTH)."\n$v\n\n";
        }
        return $res;
    }

}