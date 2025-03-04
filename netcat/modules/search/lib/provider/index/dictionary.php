<?php
/**
 * В полнотекстовом индексе MySQL хранятся не сами слова, а их идентификаторы
 * в виде base-36 — «коды».
 * Это позволяет, с одной стороны, уменьшить объем индексов (примерно в полтора
 * раза для текстов на русском языке: средняя длина слова в русском языке —
 * около 5 букв = 10 байт; при этом только четырёхбуквенными кодами можно закодировать
 * 1.679.371 словоформу), и, с другой, обойти стандартное ограничение MySQL на
 * длину слов в полнотекстовом индексе и встроенный список стоп-слов.
 * В base-36-кодах используются символы 0-9 и строчные латинские буквы a-z,
 * для паддинга до 4 символов используется "_".
 * (При поиске для подстановки неизвестных слов в запрос используется "____";
 * такое значение должно отсутствовать в индексе.)
 */

class nc_search_provider_index_dictionary {

    const TERM_MAX_LENGTH = 255;

    /** @var array  Key: term, value: term code */
    protected $terms = array();

    /** @var bool  Полностью загружен в память? */
    protected $is_full_copy = false;

    /** @var string */
    protected $table_name = "Search_Index_Term";

    /** @var string */
    protected $last_code_table_name = "Search_Index_LastTerm";

    /** @var int */
    protected $last_id;

    /**
     * MySQL default stop word list; words shorter than 4 characters and longer
     * than 6 characters (2,147,483,647 == base36 "zik0zj") are omitted
     */
    protected $stop_words = array('able'=>1, 'about'=>1, 'above'=>1, 'across'=>1, 'after'=>1, 'again'=>1, 'allow'=>1, 'allows'=>1, 'almost'=>1, 'alone'=>1, 'along'=>1, 'also'=>1, 'always'=>1, 'among'=>1, 'anyhow'=>1, 'anyone'=>1, 'anyway'=>1, 'apart'=>1, 'appear'=>1, 'around'=>1, 'aside'=>1, 'asking'=>1, 'away'=>1, 'became'=>1, 'become'=>1, 'been'=>1, 'before'=>1, 'behind'=>1, 'being'=>1, 'below'=>1, 'beside'=>1, 'best'=>1, 'better'=>1, 'beyond'=>1, 'both'=>1, 'brief'=>1, 'came'=>1, 'cannot'=>1, 'cant'=>1, 'cause'=>1, 'causes'=>1, 'come'=>1, 'comes'=>1, 'could'=>1, 'course'=>1, 'does'=>1, 'doing'=>1, 'done'=>1, 'down'=>1, 'during'=>1, 'each'=>1, 'eight'=>1, 'either'=>1, 'else'=>1, 'enough'=>1, 'even'=>1, 'ever'=>1, 'every'=>1, 'except'=>1, 'fifth'=>1, 'first'=>1, 'five'=>1, 'former'=>1, 'forth'=>1, 'four'=>1, 'from'=>1, 'gets'=>1, 'given'=>1, 'gives'=>1, 'goes'=>1, 'going'=>1, 'gone'=>1, 'gotten'=>1, 'hardly'=>1, 'have'=>1, 'having'=>1, 'hello'=>1, 'help'=>1, 'hence'=>1, 'here'=>1, 'hereby'=>1, 'herein'=>1, 'hers'=>1, 'hither'=>1, 'indeed'=>1, 'inner'=>1, 'into'=>1, 'inward'=>1, 'itself'=>1, 'just'=>1, 'keep'=>1, 'keeps'=>1, 'kept'=>1, 'know'=>1, 'known'=>1, 'knows'=>1, 'last'=>1, 'lately'=>1, 'later'=>1, 'latter'=>1, 'least'=>1, 'less'=>1, 'lest'=>1, 'like'=>1, 'liked'=>1, 'likely'=>1, 'little'=>1, 'look'=>1, 'looks'=>1, 'mainly'=>1, 'many'=>1, 'maybe'=>1, 'mean'=>1, 'merely'=>1, 'might'=>1, 'more'=>1, 'most'=>1, 'mostly'=>1, 'much'=>1, 'must'=>1, 'myself'=>1, 'name'=>1, 'namely'=>1, 'near'=>1, 'nearly'=>1, 'need'=>1, 'needs'=>1, 'never'=>1, 'next'=>1, 'nine'=>1, 'nobody'=>1, 'none'=>1, 'noone'=>1, 'novel'=>1, 'often'=>1, 'okay'=>1, 'once'=>1, 'ones'=>1, 'only'=>1, 'onto'=>1, 'other'=>1, 'others'=>1, 'ought'=>1, 'ours'=>1, 'over'=>1, 'placed'=>1, 'please'=>1, 'plus'=>1, 'quite'=>1, 'rather'=>1, 'really'=>1, 'right'=>1, 'said'=>1, 'same'=>1, 'saying'=>1, 'says'=>1, 'second'=>1, 'seeing'=>1, 'seem'=>1, 'seemed'=>1, 'seems'=>1, 'seen'=>1, 'self'=>1, 'selves'=>1, 'sent'=>1, 'seven'=>1, 'shall'=>1, 'should'=>1, 'since'=>1, 'some'=>1, 'soon'=>1, 'sorry'=>1, 'still'=>1, 'such'=>1, 'sure'=>1, 'take'=>1, 'taken'=>1, 'tell'=>1, 'tends'=>1, 'than'=>1, 'thank'=>1, 'thanks'=>1, 'thanx'=>1, 'that'=>1, 'thats'=>1, 'their'=>1, 'theirs'=>1, 'them'=>1, 'then'=>1, 'thence'=>1, 'there'=>1, 'theres'=>1, 'these'=>1, 'they'=>1, 'think'=>1, 'third'=>1, 'this'=>1, 'those'=>1, 'though'=>1, 'three'=>1, 'thru'=>1, 'thus'=>1, 'took'=>1, 'toward'=>1, 'tried'=>1, 'tries'=>1, 'truly'=>1, 'trying'=>1, 'twice'=>1, 'under'=>1, 'unless'=>1, 'until'=>1, 'unto'=>1, 'upon'=>1, 'used'=>1, 'useful'=>1, 'uses'=>1, 'using'=>1, 'value'=>1, 'very'=>1, 'want'=>1, 'wants'=>1, 'well'=>1, 'went'=>1, 'were'=>1, 'what'=>1, 'when'=>1, 'whence'=>1, 'where'=>1, 'which'=>1, 'while'=>1, 'whole'=>1, 'whom'=>1, 'whose'=>1, 'will'=>1, 'wish'=>1, 'with'=>1, 'within'=>1, 'wonder'=>1, 'would'=>1, 'your'=>1, 'yours'=>1, 'zero'=>1);

    /** Сохранять по 25000 новых терминов за один INSERT */
    protected $max_save_batch = 25000;

    /** @var nc_search_provider_index_dictionary */
    static protected $instance;

    protected $new_term_data = array();

    /**
     * @param bool $load_all
     * @return nc_search_provider_index_dictionary
     */
    static public function get_instance($load_all = false) {
        if (!isset(self::$instance)) { self::$instance = new self(); }
        if ($load_all) { self::$instance->load_all(); }
        return self::$instance;
    }

    /**
     *
     */
    protected function __construct() {
    }

    /**
     *
     * @return nc_Db
     */
    protected function get_db() {
        return nc_Core::get_object()->db;
    }

    /**
     *
     */
    public function load_all() {
        $this->is_full_copy = true;
        $this->load_from_db();
        return $this;
    }

    /**
     *
     */
    public function load_terms(array $terms) {
        if ($this->is_full_copy || !count($terms)) { return; }
        $values = array();
        foreach ($terms as $row) {
            foreach ((array)$row as $t) {
                if (is_string($t) && !isset($this->terms[$t])) {
                    $values[$t] = "'" . nc_search_util::db_escape($t) . "'";
                }
            }
        }
        if ($values) {
            $this->load_from_db("`Term` IN (" . join(", ", $values) . ")");
        }
    }

    /**
     * @param string $query_where
     */
    protected function load_from_db($query_where = '1') {
        $result = $this->get_db()->get_results("SELECT `Term`, `Code`
                                                  FROM `{$this->table_name}`
                                                 WHERE $query_where",
                                                ARRAY_N);
        if ($result) {
            foreach ($result as $row) { $this->terms[$row[0]] = $row[1]; }
        }
    }

    /**
     * @param string $query
     * @return array
     */
    protected function query($query) {
        return $this->get_db()->get_results($query, ARRAY_A);
    }

    /**
     * Получить коды для указанных терминов.
     * @param array $terms
     * @param boolean $create_new  Создавать запись, если термин неизвестен?
     * @return array
     */
    public function get_codes(array $terms, $create_new) {
        $codes = array();
        if (!count($terms)) { return $codes; }

        // load codes for the terms [if needed]
        $this->load_terms($terms);

        // get array with the term codes
        $codes = $this->get_term_codes($terms, $create_new);

        // save new terms [if needed]
        $this->save_new_terms();

        // remove terms from the memory
        if (!$this->is_full_copy && count($this->terms) > 50) { $this->terms = array(); }

        return $codes;
    }

    /**
     * Получение кодов для массива терминов вынесено в отдельный метод для
     * удобства рекурсивного вызова, которое необходимо при обработке альтернативных
     * форм слов (когда значение в массиве $terms является массивом)
     * @param array $terms
     * @param boolean $create_new
     * @return array
     */
    protected function get_term_codes(array $terms, $create_new) {
        $codes = array();
        $db = $create_new ? $this->get_db() : null;
        foreach ($terms as $t) {
            // skip empty terms (think nc_search_language_filter_stopwords)
            if ($t === null || (is_scalar($t) && strlen($t) == 0) || (is_array($t) && sizeof($t) == 0)) {
                continue;
            }
            if (is_array($t)) { // "alternative forms"
                $res = join("|", $this->get_term_codes($t, $create_new));
                if ($res) {
                    $codes[] = $res;
                }
            } else {
                $length = mb_strlen($t, 'UTF-8');
                if ($length > self::TERM_MAX_LENGTH) { // maximum length of the `Term` field in the database is 255
                    $t = mb_substr($t, 0, self::TERM_MAX_LENGTH, 'UTF-8');
                    $length = self::TERM_MAX_LENGTH;
                }
                if (isset($this->terms[$t])) { // this is a known term
                    $codes[] = $this->terms[$t];
                } else if ($create_new) { // should create new records in Search_Index_Term
                    $new_code = $this->get_next_code();
                    $this->terms[$t] = $codes[] = $new_code;
                    // values for the query: REPLACE INTO ... (`Term`, `Code`, `Length`) VALUES
                    $this->new_term_data[] = "('{$db->escape($t)}', '$new_code', $length)";
                }
                // else (i.e. $create_new == false and term is unknown): do not add entry to the $codes
            }
        }
        return $codes;
    }

    /**
     *
     */
    protected function save_new_terms() {
        if (!count($this->new_term_data)) { return; }

        // store last used code
        $this->save_last_code();
        // save $new_term_data in batches (avoid queries longer than 'max_allowed_packet')
        $offset = 0;
        $batch_size = $this->max_save_batch;
        $new_term_data_length = count($this->new_term_data);
        while ($offset < $new_term_data_length) {
            $this->query(
                "REPLACE INTO `{$this->table_name}` (`Term`, `Code`, `Length`) VALUES " .
                join(", ", array_slice($this->new_term_data, $offset, $batch_size))
            );
            $offset += $batch_size;
        }
        $this->new_term_data = array();
    }

    /**
     * Возвращает последний использованный код (base-10, i.e. INT)
     * @return int
     */
    protected function get_last_id() {
        if (!$this->last_id) {
            $db = $this->get_db();
            $last_id = $db->get_var("SELECT `Code` FROM `$this->last_code_table_name`");
            if ($last_id === null) {
                $last_id = $db->get_var("SELECT COUNT(*) FROM `$this->table_name`");
                $db->query("INSERT INTO `$this->last_code_table_name` SET `Code` = $last_id");
            }
            $this->last_id = (int)$last_id;
        }
        return $this->last_id;
    }

    /**
     * @return int
     */
    protected function get_next_code() {
        $this->get_last_id();
        do { // get next code that is not in the MySQL stop word list
            $new_code = str_pad(base_convert(++$this->last_id, 10, 36), 4, "_");
        } while (isset($this->stop_words[$new_code]));

        return $new_code;
    }

    protected function save_last_code() {
        if ($this->last_id) {
            $this->query("UPDATE `{$this->last_code_table_name}` SET `Code` = " . (int)$this->last_id);
        }
    }

    /**
     * @return bool
     */
    public function is_full_copy() {
        return $this->is_full_copy;
    }

}