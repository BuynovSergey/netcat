<?php

/* $Id: analyzer.php 8454 2012-11-23 09:21:46Z aix $ */

/**
 * Анализатор для Zend_Search_Lucene, поведение которого определяется
 * настройками модуля поиска.
 *
 * Механизм поиска слов отличается от Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8(Num).
 */
class nc_search_provider_zend_analyzer extends Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive {

    protected $regexp_alnum = '/([^\pL\pN]+)/Su';
 // не буквы и не цифры
    protected $regexp_alpha = '/([^\pL]+)/Su';
 // не буквы
    protected $tokenizer_regexp;
    /**
     * @var array используется для списков синонимов
     */
    protected $token_stack = array();
    /**
     * @var boolean равно настройке IgnoreNumbers
     */
    protected $ignore_numbers;
    /**
     * @var integer настройка MaxTermsPerField
     */
    protected $max_terms = 1000002; // мильон слов (можно задать больше в MaxTermsPerField)
    /**
     * @var integer
     */
    protected $max_chunks = 2000002;
    /**
     * @var array фрагменты токенизируемого текста
     */
    protected $input_chunks = array();
    /**
     * @var integer количество элементов в массиве $input_chunks
     */
    protected $num_chunks = 0;
    /**
     * @var integer следующий обрабатываемый элемент
     */
    protected $current_chunk = 0;
    /**
     * @var integer число символов от начала текста
     */
    protected $char_position = 0;

    /**
     *
     */
    public function __construct() {
        // skip numbers?
        $this->ignore_numbers = nc_search::should('IgnoreNumbers');
        $this->tokenizer_regexp = $this->ignore_numbers ? $this->regexp_alpha : $this->regexp_alnum;
        // max terms
        $max_terms = nc_search::get_setting('MaxTermsPerField');
        if ($max_terms > 0) {
            $this->max_terms = $max_terms + 2;
            $this->max_chunks = $max_terms * 2 + 2;
        }
    }

    /**
     * Tokenization stream API
     * Set input
     *
     * @param string $data
     * @param string $encoding
     */
    public function setInput($data, $encoding = '') {
        if (strlen($data)) {
            // treat words containing numbers as separate tokens (string + number)
            $data = preg_replace("/(\pL)(\pN)/u", "$1 $2", $data);
            $data = preg_replace("/(\pN)(\pL)/u", "$1 $2", $data);

            $this->input_chunks = preg_split($this->tokenizer_regexp, " ".$data, $this->max_terms, PREG_SPLIT_DELIM_CAPTURE);
            $this->num_chunks = sizeof($this->input_chunks);

            // ограничение количества терминов в одном поле
            if ($this->num_chunks > $this->max_chunks) {
                $this->input_chunks = array_slice($this->input_chunks, 0, $this->max_chunks - 1);
                $this->num_chunks = sizeof($this->input_chunks);
            }
        } else {
            $this->num_chunks = 0;
        }

        $this->reset();
    }

    /**
     * Reset token stream
     */
    public function reset() {
        $this->current_chunk = 1; // нулевой элемент массива всегда пустой, нечетные — разделители
        $this->char_position = 0;
    }

    /**
     * Tokenization stream API
     * Get next token
     * Returns null at the end of stream
     *
     * @return Zend_Search_Lucene_Analysis_Token|null
     */
    public function nextToken() {
        // есть ли нам откуда брать данные?
        if (!$this->num_chunks) {
            return null;
        }

        // сначала отдаём уже имеющиеся токены
        if (sizeof($this->token_stack)) {
            return array_pop($this->token_stack);
        }

        while ($this->num_chunks > $this->current_chunk) {
            $word = $this->input_chunks[$this->current_chunk + 1];
            // специальный случай: идентификаторы сайтов и разделов в виде sub123, site5
            if ($this->ignore_numbers &&
                    ($word == 'site' || $word == 'sub') &&
                    preg_match("/^(\d+)/", $this->input_chunks[$this->current_chunk + 2], $matches)) {
                $word .= $matches[1];
            }

            $word_length = mb_strlen($word, 'UTF-8');
            $delimiter_length = mb_strlen($this->input_chunks[$this->current_chunk], 'UTF-8');
            $start_position = ($this->current_chunk == 1 ? 0 : $this->char_position + $delimiter_length + 1);
            $end_position = $start_position + $word_length;

            // готовимся к следующему циклу
            $this->char_position = $end_position;
            $this->current_chunk += 2;

            if (!$word_length) {
                continue;
            } // на входе была строка без значащих символов?
            // применяем фильтры
            $processed = $this->apply_nc_filters($word);
            $count = sizeof($processed);
            if ($count > 0) {
                for ($i = 1; $i < $count; $i++) { // i.e. if $count > 1
                    $token = new Zend_Search_Lucene_Analysis_Token($processed[$i], $start_position, $end_position);
                    // умная книга Lucene in Action советует установить $token->setPositionIncrement(0),
                    // но, по-моему, разницы нет (в исходниках ZSL отмечено "todo: Process
                    // $token->getPositionIncrement()" - может быть, в будущем заработает)
                    $token->setPositionIncrement(0);
                    $this->token_stack[] = $token;
                }
                return new Zend_Search_Lucene_Analysis_Token($processed[0], $start_position, $end_position);
            }
        }

        return null;
    }

    /**
     * Фильтры сделаны нестандартным для Lucene способом; оправдания следующие:
     *   - возможно, в будущем в модуле будет свой парсер, и он будет обрабатывать
     *     ситуации с синонимами и проч. переписыванием запросов, e.g.:
     *     (word1 word2) → (word1 word2 syn2); "word1 word2" → ("word1 word2" OR "word1 syn2")
     *   - чтобы сделать через addFilter(), нужно создать дублирующие классы или класс-прокси
     *     (или отказаться от идеи возможного использования фильтров netcat с
     *     другими поставщиками поиска, которые [*может быть*] будут)
     *   - стандартные фильтры создают множество временных объектов
     *     (каждый фильтр, если он что-то сделал, создает новый токен)
     *
     * @param string $term
     * @return array может содержать 0 (стоп-слова), 1 или несколько (синонимы) элементов
     *
     */
    protected function apply_nc_filters($term) {
        $context = nc_search::get_current_context();
        return nc_search_extension_manager::get('nc_search_language_filter', $context)
                ->stop_on(array())
                ->apply('filter', array($term));
    }

}
