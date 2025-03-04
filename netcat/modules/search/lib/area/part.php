<?php

/**
 * 
 */
abstract class nc_search_area_part {

    /* @var int[]     ID сайта/раздела; может быть пустым, если указана страница (не раздел) */
    protected $ids;

    /* @var string  путь, соответствующий области или доменное имя сайта, в том виде, как задан пользователем */
    protected $url = "";

    /* @var bool    https */
    protected $https = false;

    /* @var bool    область исключена из поиска (начинается на "-") */
    protected $is_excluded = false;

    /* @var bool    прямые потомки  */
    protected $include_children = true;

    /* @var bool    прямые и отдалённые потомки ("subX*") */
    protected $include_descendants = false;

    protected $document_table_name = "Search_Document";

    /**
     * @param array $values
     */
    public function __construct(array $values) {
        foreach ($values as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * Сравнение URL с областью. Вернёт истину, если $url входит в область
     * ВНИМАНИЕ!!! НЕ УЧИТЫВАЕТСЯ СВОЙСТВО $this->is_excluded!
     * @param string $url
     * @return boolean
     */
    abstract public function matches($url);

    /**
     * Возвращает полный URL, соответствующий области. allsites или sub, который задан
     * через указание пути без имени сайта, может вернуть несколько элементов в массиве
     * @return array
     */
    abstract public function get_urls();

    /**
     * Возвращает SQL-условие
     * @return string
     */
    abstract public function get_sql_condition();

    /**
     * Возвращает условие для запроса в терминах языка запросов (i.e., lucene query)
     * @return string
     */
    abstract public function get_field_condition();

    /**
     * Возвращает «человекопонятное» описание области
     */
    abstract public function get_description();

    /**
     * Возвращает соответствующее области текстовое описание; для разделов и сайтов
     * в виде subXX/siteXX
     * @return string
     */
    public function to_string() {
        return $this->get_prefix().$this->get_string().$this->get_suffix();
    }

    abstract public function get_string();

    protected function get_prefix() {
        return ($this->is_excluded ? "-" : "");
    }

    protected function get_suffix() {
        return "";
    }

    public function is_excluded() {
        return $this->is_excluded;
    }

    /**
     * @param array $conditions
     * @return string
     */
    public function join_multiple_or_conditions(array $conditions) {
        $multiple = count($conditions) > 1;
        return ($multiple ? "(" : "") . join(" OR ", $conditions) . ($multiple ? ")" : "");
    }

}
