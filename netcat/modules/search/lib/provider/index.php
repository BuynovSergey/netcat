<?php

/**
 * Поисковый индексатор, сохраняющий информацию в базе данных MySQL.
 *
 * Используются таблицы:
 *   Search_Document (not provider-specific).
 *   Search_Index_Term — таблица соответствий базовых форм и их кодов (см.
 *     комментарий к nc_search_provider_index_dictionary).
 *   Search_Index_LastTerm — таблица, в которой хранится последний использованный
 *     идентификатор (в виде целого числа).
 *   Search_Index — таблица со всеми проиндексированными терминами
 *   Search_Field — список полей в индексе. Архитектура модуля позволяет иметь в
 *     индексе несколько полей с одинаковым названием, но разными параметрами
 *     (вес поля, тип данных, возможность сортировки) — для каждого такого поля
 *     будет создана отдельная таблица Search_FieldN.
 *   Search_Field[N] — таблица, хранящая содержимое поля N документов; нужна для
 *     ранжирования результатов с учётом веса поля и для поиска по полю.
 */
class nc_search_provider_index implements nc_search_provider  {

    protected $document_table_name = "Search_Document";
    protected $index_table_name = "Search_Index";
    protected $field_list_table_name = "Search_Index_Field";
    protected $term_table_name = "Search_Index_Term";
    protected $term_last_code_table_name = "Search_Index_LastTerm";
    protected $last_rank_table_name = "Search_Index_LastRank";

    /** @var string interval to keep values in the LastRank table */
    protected $rank_cache_interval = "15 MINUTE";

    /** @var int maximum text chunk length to process at once in store_field() */
    protected $text_batch_length = 204800;

    /** @var nc_search_provider_index_field_manager */
    protected $index_fields;

    /** @var int */
    protected $max_allowed_packet;

    /** @var int */
    // protected $optimal_packet_length = 16776192;

    /** @var nc_search_provider_index_dictionary */
    protected $dictionary;

    /**
     * Document fields that are not saved in the separate Search_Index_FieldN table
     * @var array
     */
    protected $skipped_fields = array(
        "doc_id"=>1, "site_id"=>1, "sub_id"=>1, "ancestor"=>1,
        "language"=>1, "last_modified"=>1
    );

    // -------------------------------------------------------------------------

    /**
     *
     */
    public function __construct() {
        $db = $this->get_db();
        // $db->query("SET max_allowed_packet={$this->optimal_packet_length}");
        $this->max_allowed_packet = (int)$db->get_var("SHOW VARIABLES LIKE 'max_allowed_packet'", 1, 0);
    }

    // ------------------- nc_search_provider interface ------------------------
    /**
     * Метод вызывается однократно после установки модуля (или системы в редакции,
     * содержащей модуль)
     */
    public function first_run() {
        // Create tables
        $create_statements = array(
            // compound index
            "CREATE TABLE IF NOT EXISTS `{$this->index_table_name}` (
                `Document_ID` int(11) NOT NULL,
                `Content` longtext character set latin1 collate latin1_bin NOT NULL,
                PRIMARY KEY  (`Document_ID`),
                FULLTEXT KEY `Content` (`Content`)
             ) ENGINE=MyISAM",
            // indexed terms and their codes
            "CREATE TABLE IF NOT EXISTS `{$this->term_table_name}` (
                  `Term` varchar(255) character set utf8 collate utf8_bin NOT NULL,
                  `Code` varbinary(255) NOT NULL,
                  `Length` tinyint(3) unsigned NOT NULL,
                  PRIMARY KEY  (`Term`)
             ) ENGINE=MyISAM DEFAULT CHARSET=utf8",
            // last used term code
            "CREATE TABLE IF NOT EXISTS `{$this->term_last_code_table_name}` (`Code` int(11) NOT NULL)",
            // index field list
            "CREATE TABLE IF NOT EXISTS `{$this->field_list_table_name}` (
              `Field_ID` int(10) unsigned NOT NULL auto_increment,
              `Name` varchar(255) NOT NULL,
              `Weight` decimal(5,2) NOT NULL,
              `Type` tinyint(4) NOT NULL,
              `IsSortable` tinyint(4) NOT NULL default '0',
              `IsStored` tinyint(4) NOT NULL default '0',
              PRIMARY KEY  (`Field_ID`)
            ) ENGINE=MyISAM",
            // offset cache for "heavy" queries
            "CREATE TABLE IF NOT EXISTS `{$this->last_rank_table_name}` (
                  `Time` timestamp NOT NULL,
                  `QueryHash` char(40) NOT NULL,
                  `Offset` int(11) UNSIGNED NOT NULL,
                  `Rank` int(11) UNSIGNED NOT NULL,
                  UNIQUE KEY `QueryHash` (`QueryHash`, `Offset`)
             ) ENGINE=MyISAM",
        );

        $db = $this->get_db();
        foreach ($create_statements as $q) { $db->query($q); }

        if (!$db->get_var("SELECT COUNT(*) FROM `{$this->term_last_code_table_name}`")) {
            $db->query("INSERT INTO `Search_Index_LastTerm` SET `Code` = 0");
        }

        if (!$this->can_create_temporary_tables()) {
            nc_search::save_setting("DatabaseIndex_AlwaysGetTotalCount", 1, false);
        }
    }

    /**
     * Проверка правильности настроек сервера, выводится на странице «Информация»
     * в панели управления модулем
     */
    public function check_environment() {
        // No special requirements

        // ???: выводить предупреждение, если DatabaseIndex_AlwaysGetTotalCount=0
        // и у пользователя нет прав на создание временных таблиц?
    }

    /**
     * Удаление документа из индекса
     * @param nc_search_document $document
     */
    public function remove_document(nc_search_document $document) {
        $doc_id = $document->get_id();

        // Delete from Search_Index and Search_Index_FieldN
        $this->get_db()->query("DELETE FROM `{$this->index_table_name}` WHERE `Document_ID` = $doc_id");
        $this->remove_document_fields($doc_id);

        // Delete from Search_Document
        $document->delete();
    }

    /**
     * Обработать документ (конкретный класс должен проверить, есть ли документ
     * в индексе, и в зависимости от этого обновить существующий документ или добавить
     * новый)
     * @param nc_search_document $document
     * @return bool
     */
    public function process_document(nc_search_document $document) {
        $doc_hash = $document->generate_hash();

        // Есть ли документ с таким путём в нашей базе?
        $stored = nc_search_document::get_hash_by_path($document->get('site_id'), $document->get('path'));

        if ($stored) {
            $document->set_id($stored->get_id());
            // Нужно ли обновлять данные в индексе? Проверить хэш для content
            if ($stored->get('hash') == $doc_hash) {
                $document->save();
                return false;  // ---- RETURN ----
            }
        }

        // Загрузить словарь в память?
        if (nc_search::should('DatabaseIndex_LoadAllCodesForIndexing')) {
            $this->load_dictionary();
        }

        // (1) Сохранение документа в `Search_Document`
        $document->save();
        $doc_id = $document->get_id();
        $doc_context = $this->create_document_context($document);

        $all_terms = array();

        // (2) Удаление старых данных из `Search_Index_FieldN`
        if ($stored) { $this->remove_document_fields($doc_id); }

        // (3) Обработка полей: сохранение данных в `Search_Index_FieldN`
        /** @var $field nc_search_field */
        foreach ($document->get_fields() as $field) {
            if (!isset($this->skipped_fields[$field->get('name')])) {
                $all_terms[] = $this->store_field($doc_id, $field, $doc_context);
            }
        }

        // (4) Сохранение искабельного текста в `Search_Index`
        $this->store_index_data($this->index_table_name,
                                $doc_id,
                                join('/', array_filter($all_terms)));

        return true;
    }

    /**
     * Запись изменений в индекс
     */
    public function commit() {
        // ok!
    }

    /**
     * Оптимизация индекса (запускается после окончания переиндексации)
     */
    public function optimize() {
        $table_manager = $this->get_index_fields();
        $table_manager->drop_empty_tables();

        // optimize tables
        $random_range = (int)nc_search::get_setting('DatabaseIndex_OptimizationFrequency');
        if ($random_range > 0 && mt_rand(1, $random_range) == 1) {
            $table_manager->optimize_tables();
            $this->get_db()->query("OPTIMIZE TABLE `{$this->index_table_name}`");
        }
    }

    /**
     * Внутренний метод для find() и suggest_titles() (нужен потому, что в suggest_titles()
     * кроме поиска к результатам добавляются точные совпадения по LIKE)
     * @param nc_search_query $query
     * @return array ['total_hits' => int, 'total_hits_unknown' => bool, 'id_list' => string]
     */
    protected function find_document_ids(nc_search_query $query) {
        $db = $this->get_db();

        $id_list = '0';
        $total_hits_unknown = false;
        $translator = new nc_search_provider_index_translator($this);

        $select_ids_query = $query->translate($translator);
        $limit = (int)$query->get('limit');
        $offset = (int)$query->get('offset');

        if ($select_ids_query) { // neither null nor empty string
            $db->last_error = null;

            if (is_array($select_ids_query)) { // will need to create a temporary table; no final row count
                $prefilter_query = $select_ids_query['prefilter'];
                $refinement_query = $select_ids_query['refinement'];

                $query_hash = sha1($prefilter_query);

                // (1) create temporary table
                // MariaDB оптимизирует подзапросы путём игнорирования ORDER BY, который нам критически
                // важен для правильной сортировки результатов; LIMIT заставляет её пропустить эту оптимизацию
                // (https://mariadb.com/kb/en/mariadb/why-is-order-by-in-a-from-subquery-ignored/)
                $db->query("SET @rank=0");
                $db->query("CREATE TEMPORARY TABLE `$select_ids_query[temp_table]` " .
                           "(INDEX (`Rank`,`Document_ID`)) " .
                           "SELECT filtered.`Document_ID`, @rank := @rank+1 AS `Rank` " .
                           "FROM ($prefilter_query LIMIT 4294967295) AS filtered");

                // (2) check for cached offset values so we won't need to check from the beginning of the pre-filtered list
                $rank_table = $this->last_rank_table_name;
                $db->query("DELETE FROM `$rank_table` WHERE `Time` < NOW() - INTERVAL {$this->rank_cache_interval}");
                $cached_rank = $offset
                    ? $db->get_row("SELECT `Offset`, `Rank` FROM `$rank_table`\n" .
                                   "WHERE `QueryHash` = '$query_hash' AND `Offset` <= $offset\n" .
                                   "ORDER BY `Offset` DESC\nLIMIT 1",
                                  ARRAY_A)
                    : null;

                if ($cached_rank) {
                    $refinement_offset = $offset - $cached_rank['Offset'];
                    $refinement_rank_value = $cached_rank['Rank'];
                }
                else {
                    $refinement_offset = $offset;
                    $refinement_rank_value = 0;
                }
                $db->query("SET @rank_value = $refinement_rank_value");

                // (3) select IDs
                $db->query("$refinement_query\n".
                           "LIMIT " . ($limit + 1) . " OFFSET $refinement_offset");
                $ids = $db->get_col();
                $id_list = join(', ', $ids);

                if (count($ids) > $limit) { // has next page
                    // total result count is set to (current page + 1 page)
                    // so there will be a paginator
                    $total_hits = $offset + 2 * $limit;
                    $total_hits_unknown = true;

                    // save rank for the next page
                    $last_rank = $db->get_var(null, 1, $limit);
                    $db->query("REPLACE INTO `{$this->last_rank_table_name}`
                                    SET `Time` = NOW(),
                                        `QueryHash` = '$query_hash',
                                        `Offset` = " . ($offset + $limit) . ",
                                        `Rank` = $last_rank");

                }
                else {
                    $total_hits = $offset + count($ids);
                }
            }
            else { // select IDs in a single query
                $id_list = join(', ', $db->get_col($select_ids_query));
                $total_hits = $db->last_error ? 0 : $db->get_var('SELECT FOUND_ROWS()');
            }
        }
        else { // Translator thinks there won't be any results (terms in the query are not in the index)
            // we could get $translator->get_unknown_terms(), but it is of no use for the correctors... :(
            $total_hits = 0;
        }

        return array('total_hits' => $total_hits, 'total_hits_unknown' => $total_hits_unknown, 'id_list' => $id_list ?: '0');
    }

    /**
     * Выполнение запроса
     * @param nc_search_query $query
     * @param boolean $should_highlight
     * @return nc_search_result
     */
    public function find(nc_search_query $query, $should_highlight = true) {
        $index_search_result = $this->find_document_ids($query);
        $total_hits = $index_search_result['total_hits'];
        $id_list = $index_search_result['id_list'];

        // make 'result' object
        $result = new nc_search_result(array(), $total_hits);
        $result->set_query($query);
        /* @todo use $ids['total_hits_unknown'] to mark that total count is not known */
        if (!$should_highlight) {
            $result->disable_highlighting();
        }

        if ($total_hits && $id_list) {
            // get field list
            $doc = new nc_search_result_document;
            $fields = $doc->get_column_names($query->get('options_to_fetch'));

            // get document data
            $doc_query = "SELECT $fields FROM `%t%` WHERE `Document_ID` IN ($id_list) " .
                         "ORDER BY FIELD(`Document_ID`, $id_list) " .
                         "LIMIT " . (int)$query->get('limit');

            $result->select_from_database($doc_query);
        }

        return $result;
    }

    /**
     * Переиндексация области здесь и сейчас
     * @param string $area_string   area string OR rule ID
     * @param integer $runner_type  тип запуска
     * @return bool
     */
    public function index_area($area_string, $runner_type = nc_search::INDEXING_NC_CRON) {
        return nc_search_indexer::index_area($area_string, $runner_type);
    }

    /**
     * Запланировать переиндексацию области в указанное время
     * @param string $area_string   area string, rule ID
     * @param int $timestamp
     */
    public function schedule_indexing($area_string, $timestamp) {
        nc_search_scheduler::schedule_indexing($area_string, $timestamp);
    }

    /**
     * Работает ли в данный момент переиндексация?
     * @return mixed   FALSE если индексирование не производится
     */
    public function is_reindexing() {
        return nc_search_indexer::get_current_task();
    }

    /**
     * Получить массив с заголовками страниц для autocomplete
     * @param string $input
     * @param string $language
     * @param integer $site_id
     * @return array   элементы массива: array("label" => "Page Title", "url" => "/path/")
     */
    public function suggest_titles($input, $language, $site_id) {
        $limit = nc_search::get_setting('NumberOfSuggestions');

        $index_search_results = '';
        $document_order_by_id = '1';
        // поиск в индексе (то есть будут варианты после обработки фильтрами - базовая форма)
        if (nc_search::should('SearchTitleBaseformsForSuggestions')) {
            $as_phrase = nc_search::should('SearchTitleAsPhraseForSuggestions');
            $b1 = $as_phrase ? '"' : '(';
            $b2 = $as_phrase ? '"' : ')';

            $full_query = "title:$b1$input$b2^5";

            // Предполагаем, что в $input не используется язык запросов (и делаем это невозможным)
            $input_tokens = $this->tokenize_text($input);
            $without_last_word = join(' ', array_slice($input_tokens, 0, -1));
            /* @todo сделать проверку на то, что последнее слово является правильным/полным? */
            $last_word = join(' ', array_slice($input_tokens, -1));

            $partial_query = '';
            if (nc_search::should('AllowWildcardSearch')) {
                $partial_query = strlen($without_last_word) ? " OR title:($b1$without_last_word$b2 $last_word*)" : " OR title:$input*";
            } else if ($without_last_word !== '') {
                $partial_query = " OR title:$b1$without_last_word$b2^0.5";
            }

            $query = new nc_search_query($full_query . $partial_query);
            $query->set('limit', $limit)
                  ->set('options_to_fetch', array('title', 'site_id', 'path'))
                  ->set('language', $language)
                  ->set('area', 'site' . (int)$site_id);

            $index_search_results = $this->find_document_ids($query);
            $document_ids = $index_search_results['id_list'];
            $index_search_results = "OR `Document_ID` IN ($document_ids)\n";
            $document_order_by_id = "FIELD(`Document_ID`, $document_ids)";
        }

        // поиск точного соответствия в таблице с документами
        $like_expression = '`Title` LIKE "' . nc_search_util::db_escape($input) . '%" ';
        $query = "SELECT `Catalogue_ID`, `Path`, `Title` FROM `%t%`\n" .
                 "WHERE $like_expression\n" .
                   $index_search_results . // результаты поиска по индексу
                   // сначала точные совпадения, затем совпадения по индексу
                 "ORDER BY $like_expression, $document_order_by_id\n".
                 "LIMIT $limit";

        $documents = new nc_search_result();
        $documents->select_from_database($query);

        $suggestions = array(); // собственно подсказки

        /** @var $doc nc_search_document */
        foreach ($documents as $doc) {
            $suggestions[] = array('label' => $doc->get('title'), 'url' => $doc->get('url'));
        }

        return $suggestions;
    }

    /**
     * Проверить, есть ли слово с индексе.
     * @param string $term
     * @return boolean
     */
    public function has_term($term) {
        $exists = $this->get_db()->get_var(
                    "SELECT 1 FROM `{$this->term_table_name}`
                     WHERE `Term`= '" . nc_search_util::db_escape($term) . "'");
        return (bool)$exists;
    }

    /**
     * Получить количество документов в индексе
     * @return integer
     */
    public function count_documents() {
        return $this->get_db()->get_var("SELECT COUNT(*) FROM `{$this->document_table_name}`");
    }

    /**
     * Получить количество слов в индексе
     * @return integer
     */
    public function count_terms() {
        return $this->get_db()->get_var("SELECT COUNT(*) FROM `{$this->term_table_name}`");
    }

    // -------------------------------------------------------------------------

    /**
     * @return nc_Db
     */
    protected function get_db() {
        return nc_Core::get_object()->db;
    }

    /**
     * @return boolean
     */
    protected function can_create_temporary_tables() {
        $t = "`Search_Index_TemporaryTest`";
        $v = 95;
        $db = $this->get_db();
        $db->query("CREATE TEMPORARY TABLE $t (`value` int(11))");
        $db->query("INSERT INTO $t SET `value` = $v");
        $is_success = ($db->get_var("SELECT `value` FROM $t") == $v);
        $db->query("DROP TEMPORARY TABLE IF EXISTS $t");
        return $is_success;
    }

    /**
     * @return nc_search_provider_index_field_manager
     */
    public function get_index_fields() {
        if (!$this->index_fields) {
            $this->index_fields = nc_search_provider_index_field_manager::get_all();
        }
        return $this->index_fields;
    }

    /**
     * @param nc_search_field $field
     * @return nc_search_provider_index_field
     */
    protected function get_index_field(nc_search_field $field) {
        return $this->get_index_fields()->get_index_field($field);
    }

    /**
     * @param nc_search_field $field
     * @return string
     */
    protected function get_field_table_name(nc_search_field $field) {
        return $this->get_index_field($field)->get_field_table_name();
    }

    /**
     * @return array
     */
    protected function get_all_field_table_names() {
        return $this->get_index_fields()->get_all_table_names();
    }

    /**
     * @param int $doc_id
     */
    protected function remove_document_fields($doc_id) {
        $all_table_names = $this->get_all_field_table_names();
        if (!count($all_table_names)) { return; }

        $doc_id = (int)$doc_id;
        $db = $this->get_db();

        foreach ($all_table_names as $t) {
            $db->query("DELETE FROM `$t` WHERE `Document_ID` = $doc_id");
        }
    }

    /**
     * @param $doc_id
     * @param nc_search_field $field
     * @param nc_search_context $doc_context
     * @return string
     */
    protected function store_field($doc_id, nc_search_field $field, nc_search_context $doc_context) {
        $text = $field->get('value');
        if (strlen($text) == 0) { return ''; } // empty fields are not stored

        $content = '';
        $raw_data = '';

        $is_stored = $field->get('is_stored') || $field->get('is_sortable');

        // 'is_indexed' → store in the `Content` field
        if ($field->get('is_indexed')) {

            $content = "";
            $filters = nc_search_extension_manager::get('nc_search_language_filter', $doc_context)
                         ->except('nc_search_language_filter_case')
                         ->stop_on(array());

            // Processing in chunks - compromise between performance (less
            // method call overhead) and memory usage
            $n = 0;
            $current_position = 0;
            $text_length = strlen($text);
            $batch_length = $this->text_batch_length;
            while ($current_position < $text_length) {
                if ($text_length < $batch_length) {
                    $batch = $text;
                    $current_position = $text_length;
                }
                else {
                    $space_position = strpos($text, " ", min($text_length, $current_position + $batch_length));
                    if (!$space_position) {
                        $batch = substr($text, $current_position);
                        $current_position = $text_length;
                    }
                    else {
                        $batch = substr($text, $current_position, $space_position-$current_position);
                        $current_position = $space_position + 1;
                    }
                }

                $tokens = $this->tokenize_text(mb_convert_case($batch, nc_search::get_setting('FilterStringCase'), 'UTF-8'));
                if ($field->get('is_normalized')) { // apply filters
                    $tokens = $filters->apply('filter', $tokens);
                }

                $content .= ($n ? ' ' : '') . join(' ', $this->get_term_codes($tokens, true));
                $n++;
            }
        }

        // 'is_stored' → store raw text as well
        if ($is_stored) {
            $raw_data = $text;
        }
        // save data in the DB
        $this->store_index_data($this->get_field_table_name($field),
                                $doc_id,
                                $content,
                                $raw_data);

        return $content;
    }

    /**
     * Сохраняет поле индекса в указанной таблице (Search_Index или Search_Index_FieldX),
     * при необходимости разбивает запрос на части таким образом, чтобы запрос
     * был не более mysql.max_allowed_packet
     * @param string $table_name
     * @param int $doc_id
     * @param string $all_content
     * @param string $all_raw_data
     */
    protected function store_index_data($table_name, $doc_id, $all_content, $all_raw_data = '') {
        $db = $this->get_db();
        $overhead = 1024; // команды SQL etc.
        $chunk_size = $this->max_allowed_packet - $overhead;
        $content_chunks = str_split($all_content, $chunk_size);
        $raw_chunks = strlen($all_raw_data) ? str_split($all_raw_data, $chunk_size) : array();
        $doc_id = (int)$doc_id;
        unset($all_content, $all_raw_data);

        $n_content = $n_raw = 0;
        while (count($content_chunks) || count($raw_chunks)) {
            $update = $n_content || $n_raw;
            $query = ($update ? "UPDATE" : "REPLACE INTO") .
                     " `$table_name` SET `Document_ID` = $doc_id";
            $content = array_shift($content_chunks);

            if (strlen($content)) {
                $content = nc_search_util::db_escape($content);
                $query .= ", `Content` = " . ($n_content ? "CONCAT(`Content`, '$content')" : "'$content'");
                $n_content++;
            }

            $add_raw = (count($content_chunks)==0 && // this is last content chunk
                        isset($raw_chunks[0]) && // has raw chunks
                        (strlen($content) + strlen($raw_chunks[0])) < $chunk_size); // adding raw_data will not cause overflow

            if ($add_raw) {
                $raw_data = nc_search_util::db_escape(array_shift($raw_chunks));
                $query .= ", `RawData` = " . ($n_raw ? "CONCAT(`RawData`, '$raw_data')" : "'$raw_data'");
                $n_raw++;
            }

            if ($update) { $query .= " WHERE `Document_ID` = $doc_id"; }

            $db->query($query);
        }
    }

    /**
     * @param string $string Text to tokenize
     * @return array
     */
    protected function tokenize_text($string) {
        // split words containing numbers into number+string parts
        $string = preg_replace("/(\pL)(\d)/u", "$1 $2", $string);
        $string = preg_replace("/(\d)(\pL)/u", "$1 $2", $string);

        $delimiter = nc_search::should('IgnoreNumbers') ? '/[^\pL]+/u' : '/[^\pL\d]+/u';
        $max_terms = (int)nc_search::get_setting('MaxTermsPerField');
        $tokens = preg_split($delimiter, $string, $max_terms ? $max_terms + 1 : -1);
        if ($max_terms && count($tokens) > $max_terms) {
            array_pop($tokens);
        }

        return $tokens;
    }

    /**
     * @param nc_search_document $document
     * @return nc_search_context
     */
    protected function create_document_context(nc_search_document $document) {
        return new nc_search_context(array(
                      'search_provider' => get_class($this),
                      'action' => 'indexing',
                      'group_alternative_forms' => true,
                      'language' => $document->get('language')
                   ));
     }

    /**
     *
     */
    protected function load_dictionary() {
        if (!$this->dictionary || !$this->dictionary->is_full_copy()) {
            $this->dictionary = nc_search_provider_index_dictionary::get_instance(true);
        }
    }
    /**
     * @return nc_search_provider_index_dictionary
     */
    protected function get_dictionary() {
        if (!$this->dictionary) {
            $this->dictionary = nc_search_provider_index_dictionary::get_instance(false);
        }
        return $this->dictionary;
    }

    /**
     * @param array $terms
     * @param bool $create_new
     * @return array
     */
    public function get_term_codes(array $terms, $create_new) {
        return $this->get_dictionary()->get_codes($terms, $create_new);
    }

}