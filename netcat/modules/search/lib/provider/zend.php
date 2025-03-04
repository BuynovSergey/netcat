<?php

/**
 * Адаптер для работы с библиотекой Zend_Search_Lucene
 */
class nc_search_provider_zend implements nc_search_provider {

    protected $is_opened = false;
    /**
     * @var Zend_Search_Lucene_Proxy
     */
    protected $index;
    /**
     * @var nc_search_context
     */
    protected $context;

    /**
     *
     */
    public function __construct() {
        if (!class_exists('Zend_Search_Lucene', false)) {
            nc_search::load_3rdparty_script('zend/Zend_Search_Lucene.php');
        }
    }

    /**
     * Метод вызывается однократно после установки модуля (или системы в редакции,
     * содержащей модуль). Стирает старый файл с индексом, если он имеется в
     * соответствующей папке; создаёт новый индекс.
     */
    public function first_run() {
        Zend_Search_Lucene::create($this->get_index_path());
    }

    /**
     * Проверка правильности настроек сервера, выводится на странице «Информация»
     * в панели управления модулем.
     * @return void
     */
    public function check_environment() {
        // multibyte string function overload must be disabled
        if (intval(ini_get("mbstring.func_overload")) & 2) {
            nc_print_status(NETCAT_MODULE_SEARCH_MB_OVERLOAD_ENABLED_ERROR, 'error');
        }

        // Lucene index folder must be writable
        $index_path = $this->get_index_path();
        $path_exists = file_exists($index_path);
        if (($path_exists && !is_writable($index_path)) && (!$path_exists && !is_writable("$index_path/../"))) {
            nc_print_status(NETCAT_MODULE_SEARCH_INDEX_DIRECTORY_NOT_WRITABLE_ERROR, 'error', array($index_path));
        }

        // try to open the index
        try {
            $this->open_index();
        } catch (Exception $e) {
            nc_print_status(NETCAT_MODULE_SEARCH_CANNOT_OPEN_INDEX_ERROR, 'error', array($index_path));
        }
    }

    /**
     * Метод существует для облегчения тестирования
     * @param string $option_name
     * @return mixed
     */
    protected function get_setting($option_name) {
        return nc_search::get_setting($option_name);
    }

    /**
     *
     * @return string
     */
    protected function get_index_path() {
        $path = $this->get_setting('ZendSearchLucene_IndexPath');
        $path = str_replace("%FILES%", nc_Core::get_object()->get_variable("FILES_FOLDER"), $path);
        $path = str_replace("//", "/", $path);
        return $path;
    }

    /**
     *
     * @throws nc_search_exception
     */
    protected function open_index() {
        $path = $this->get_index_path();

        try {
            if ($this->index_exists($path)) {
                $this->index = Zend_Search_Lucene::open($path);
            } else {
                $this->index = Zend_Search_Lucene::create($path);
            }
        } catch (Zend_Search_Lucene_Exception $e) {
            throw new nc_search_exception("Cannot open Lucene index: {$e->getMessage()}");
        }

        $this->is_opened = true;

        // apply settings to the index
        Zend_Search_Lucene::setResultSetLimit($this->get_setting('ZendSearchLucene_ResultSetLimit'));
        Zend_Search_Lucene::setTermsPerQueryLimit($this->get_setting('MaxTermsPerQuery'));

        $settings = array('MaxBufferedDocs', 'MaxMergeDocs', 'MergeFactor');
        foreach ($settings as $s) {
            $setter = "set$s";
            $this->index->$setter($this->get_setting("ZendSearchLucene_$s"));
        }

        // set analyzer
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(new nc_search_provider_zend_analyzer());

        // set fuzzy prefix length so fuzzy searches will match a wider array of possibilities
        Zend_Search_Lucene_Search_Query_Fuzzy::setDefaultPrefixLength(0);
        // set wildcard prefix length so wildcards will match a wider array of possibilities
        Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0);

        /**
         * @todo set default search fields
         */
    }

    /**
     * @param string $path
     * @return boolean
     */
    protected function index_exists($path) {
        return file_exists("$path/segments.gen");
    }

    /**
     * @return Zend_Search_Lucene_Proxy
     */
    protected function get_index() {
        if (!$this->is_opened) {
            $this->open_index();
        }
        return $this->index;
    }

    /**
     * Так Надо. (Особенности Lucene)
     */
    protected function pad_id($id) {
        return str_pad($id, 9, "x", STR_PAD_LEFT);
    }

    /**
     *
     */
    protected function pad_integer($number) {
        return sprintf("%09d", $number);
    }

    /**
     * Преобразование документа NC_Search_Document в Zend_Search_Lucene_Document
     * @param nc_search_document $nc_doc
     * @return Zend_Search_Lucene_Document
     */
    protected function convert_document(nc_search_document $nc_doc) {
        $zend_doc = new Zend_Search_Lucene_Document;

        foreach ($nc_doc->get_fields() as $f) {
            $name = $f->get('name');
            $value = $f->get('value');

            if (strlen(trim($value)) == 0) {
                continue;
            } // no need to add empty fields

            if ($name == 'doc_id') {
                $value = $this->pad_id($value);
            } elseif ($f->get('type') == nc_search_field::TYPE_INTEGER) {
                $value = $this->pad_integer($value);
            }

            $zend_field = new Zend_Search_Lucene_Field(
                            $name,
                            $value,
                            'UTF-8',
                            ($f->get('is_stored') || $f->get('is_sortable')),
                            ($f->get('is_indexed') || $f->get('is_sortable')),
                            $f->get('is_normalized'),
                            false /* is_binary */);

            $zend_field->boost = $f->get('weight');

            $zend_doc->addField($zend_field, 'UTF-8');
        }

        return $zend_doc;
    }

    /**
     *
     * @param nc_search_document $document
     */
    protected function mark_as_deleted(nc_search_document $document) {
        $doc_id = $this->pad_id($document->get_id());
        if (!$doc_id) {
            return;
        } // NO ID == not in the index
        $this->set_indexing_context($document);
        $index = $this->get_index();
        $hits = $index->find("doc_id:$doc_id");
        foreach ($hits as $hit) {
            $index->delete($hit->id);
        }
        $this->remove_indexing_context();
    }

    /**
     *
     * @param nc_search_document $document
     */
    protected function set_indexing_context(nc_search_document $document) {
        if (!$this->context) {
            $this->context = new nc_search_context(array(
                            'search_provider' => get_class($this),
                            'action' => 'indexing',
                    ));
        }
        $this->context->set('language', $document->get('language'));
        nc_search::set_current_context($this->context);
    }

    /**
     *
     */
    protected function remove_indexing_context() {
        nc_search::set_current_context();
    }

    /**
     * Трансформация запроса nc_search_query в запрос Lucene
     * @param nc_search_query $query
     * @return Zend_Search_Lucene_Search_Query
     */
    protected function get_lucene_query(nc_search_query $query) {
        Zend_Search_Lucene_Search_QueryParser::suppressQueryParsingExceptions();
        if ($this->get_setting('DefaultBooleanOperator') == 'AND') {
            Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(Zend_Search_Lucene_Search_QueryParser::B_AND);
        }

        $query_string = $query->to_string();

        // split letters and numbers
        $query_string = preg_replace("/(\pL)(\pN\S*)(?!:)/u", "$1 $2", $query_string);
        $query_string = preg_replace("/(\pN)(\pL\S*)(?!:)/u", "$1 $2", $query_string);

        // range search for integers
        if (nc_search::should('AllowRangeSearch') && strpos($query_string, ' TO ')) {
            preg_match_all("/(\[|\{)\s*(\d+)\s+TO\s+(\d+)\s*(\]|\})/", $query_string, $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $query_string = str_replace($m[0],
                                $m[1].$this->pad_integer($m[2]).
                                " TO ".
                                $this->pad_integer($m[3]).$m[4],
                                $query_string);
            }
        }

        // add a time range should it be required
        $modified_after = $query->get('modified_after');
        $modified_before = $query->get('modified_before');
        if ($modified_before || $modified_after) {
            $modified_after = $modified_after ? strftime("%Y%m%d%H%M%S", strtotime($modified_after)) : "19000101000000";
            $modified_before = $modified_before ? strftime("%Y%m%d%H%M%S", strtotime($modified_before)) : "22000101000000";
            $query_string = "($query_string) last_modified:[$modified_after TO $modified_before]";
        }

        // add area
        $area = $query->get('area');
        if ($area) {
            if (!($area instanceof nc_search_area)) {
                $area = new nc_search_area($area);
            }
            $is_boolean = nc_search_util::is_boolean_query($query_string);
            $query_string = "($query_string) ".($is_boolean ? " AND " : "+").
                    $area->get_field_condition($is_boolean);
        }

        // parse string into Lucene Query
        $zend_query = Zend_Search_Lucene_Search_QueryParser::parse($query_string, 'UTF-8');

        return $zend_query;
    }

    // -------------------------- PROVIDER INTERFACE -----------------------------

    /**
     * Добавление в индекс
     * @param nc_search_document $document
     */
    protected function add_document(nc_search_document $document) {
        $document->save();
        $this->set_indexing_context($document);
        $this->get_index()->addDocument($this->convert_document($document));
        $this->remove_indexing_context();
    }

    /**
     * Удаление из индекса
     * @param nc_search_document $document
     */
    public function remove_document(nc_search_document $document) {
        $this->mark_as_deleted($document);
        $document->delete();
    }

    /**
     * Обновление документа (удаление+добавление)
     * @param nc_search_document $document
     */
    protected function update_document(nc_search_document $document) {
        $this->mark_as_deleted($document);
        $this->add_document($document);
    }

    /**
     * Обработать документ
     * @param nc_search_document $document
     * @return bool
     */
    public function process_document(nc_search_document $document) {
        $doc_hash = $document->generate_hash();

        // Есть ли документ с таким путём в нашей базе?
        $stored = nc_search_document::get_hash_by_path($document->get('site_id'), $document->get('path'));

        if ($stored) {
            // Нужно ли обновлять данные в индексе? Проверить хэш для content
            if ($stored->get('hash') == $doc_hash) { // «cтолько времени ззря!» © Фандорин
                $document->set_id($stored->get_id())->save();
                return false;
            }
            $document->set_id($stored->get_id());
            $this->update_document($document);
        } else {
            $this->add_document($document);
        }
    }

    /**
     * Сохранение сделанных изменений
     */
    public function commit() {
        $this->get_index()->commit();
    }

    /**
     * Оптимизация индекса
     */
    public function optimize() {
        $zsl = $this->get_index();
        $zsl->optimize();

        // ВНИМАНИЕ!!!
        // Код, который следует далее, зависит от версии формата индекса Lucene
        // и может перестать работать и/или приводить к возникновению ошибок
        // в будущем при изменении формата, используемого Zend_Search_Lucene!
        // После некорректного завершения переиндексирования могут остаться "лишние"
        // файлы: устроим чистку...
        try {
            $path = $this->get_index_path();
            //$directory = $zsl->getDirectory();
            $directory = new Zend_Search_Lucene_Storage_Directory_Filesystem($path);

            Zend_Search_Lucene_LockManager::obtainWriteLock($directory);

            $generation = Zend_Search_Lucene::getActualGeneration($directory);
            $files_to_keep = array("segments.gen",
                    Zend_Search_Lucene_LockManager::WRITE_LOCK_FILE,
                    Zend_Search_Lucene_LockManager::READ_LOCK_FILE,
                    Zend_Search_Lucene_LockManager::READ_LOCK_PROCESSING_LOCK_FILE,
                    Zend_Search_Lucene_LockManager::OPTIMIZATION_LOCK_FILE);

            if ($generation > 0) {
                $segments_file_name = Zend_Search_Lucene::getSegmentFileName($generation);
                $files_to_keep[] = $segments_file_name;
                $segments_file = $directory->getFileObject($segments_file_name);

                // после оптимизации должен остаться только один сегмент
                // найдём имя этого сегмента
                $segments_file->seek(16); // 4 (int, file format marker) + 8 (long, index version) + 4 (int, segment name counter)
                $seg_count = $segments_file->readInt();
                if ($seg_count == 1) {
                    $seg_name = $segments_file->readString();
                    $files_to_keep[] = "$seg_name.cfs";
                    $files_to_keep[] = "$seg_name.sti";

                    if ($seg_name && chdir($path)) {
                        $files_to_delete = array_diff(glob("*"), $files_to_keep);
                        foreach ($files_to_delete as $f) {
                            unlink($f);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // something went south, probably it was impossible to get the write lock
            // or read some file
            // trigger_error(get_class($e) . ": {$e->getMessage()}", E_USER_WARNING);
        }

        Zend_Search_Lucene_LockManager::releaseWriteLock($directory);
    }

    /**
     * @return false|nc_search_indexer_task
     */
    public function is_reindexing() {
        return nc_search_indexer::get_current_task();
    }

    /**
     * Переиндексация области
     */
    public function index_area($area_string, $runner_type = nc_search::INDEXING_NC_CRON) {
        // индексатор: получает и парсит информацию
        return nc_search_indexer::index_area($area_string, $runner_type);
    }

    /**
     *
     */
    public function schedule_indexing($area_string, $timestamp) {
        nc_search_scheduler::schedule_indexing($area_string, $timestamp);
    }

    /**
     * Поиск по индексу
     * @param nc_search_query $query
     * @param boolean $should_highlight
     * @return nc_search_result
     */
    public function find(nc_search_query $query, $should_highlight = true) {
        nc_search::set_current_context(new nc_search_context(array(
                                'search_provider' => get_class($this),
                                'action' => 'searching',
                                'language' => $query->get('language'),
                        )));

        $index = $this->get_index();
        $lucene_query = $this->get_lucene_query($query);

        if ($query->get('sort_by')) { // custom sort
            $lucene_result = $index->find($lucene_query,
                            $query->get('sort_by'),
                            SORT_STRING,
                            $query->get('sort_direction'));
        } else {
            $lucene_result = $index->find($lucene_query);
        }
        $total_hits = count($lucene_result);

        $result = new nc_search_result(array(), $total_hits);
        $result->set_query($query);
        if (!$should_highlight) {
            $result->disable_highlighting();
        }

        nc_search::set_current_context();

        // truncate to get the requested page only
        $lucene_result = array_slice($lucene_result, $query->get('offset'), $query->get('limit'));

        // сформировать nc_search_result
        $result_ids = array();
        foreach ($lucene_result as $hit) {
            $result_ids[] = $hit->doc_id;
        }
        if (count($result_ids)) {
            foreach ($result_ids as $i => $id) {
                $result_ids[$i] = (int) trim($id, "x");
            }
            $id_list = join(", ", $result_ids);

            $doc = new nc_search_result_document;
            $fields = $doc->get_column_names($query->get('options_to_fetch'));

            $query = "SELECT $fields FROM `%t%` WHERE `Document_ID` IN ($id_list) ".
                    "ORDER BY FIELD(`Document_ID`, $id_list)";

            $result->select_from_database($query);
        }
        return $result;
    }

    /**
     * @param string $input
     * @param string $language
     * @param integer $site_id
     * @return array
     */
    public function suggest_titles($input, $language, $site_id) {
        $suggestions = array(); // собственно подсказки
        $titles = array();

        $limit = nc_search::get_setting('NumberOfSuggestions');

        // поиск в индексе (то есть будут варианты после обработки фильтрами - базовая форма)
        if (nc_search::should('SearchTitleBaseformsForSuggestions')) {
            $last_space = strrpos($input, " ");
            $as_phrase = nc_search::should('SearchTitleAsPhraseForSuggestions');
            $b1 = ($as_phrase) ? '"' : '(';
            $b2 = ($as_phrase) ? '"' : ')';
            /* @todo сделать проверку на то, что последнее слово является правильным/полным? */
            $query_string = "(title:$b1$input$b2".
                    ($last_space ? " OR title:$b1".trim(substr($input, 0, $last_space)).$b2 : '').
                    ") AND site_id:$site_id";

            $query = new nc_search_query($query_string);
            $query->set('limit', $limit)
                    ->set('options_to_fetch', array('title', 'site_id', 'path'))
                    ->set('language', $language);

            $documents = $this->find($query, false);

            foreach ($documents as $doc) {
                $suggestions[] = array("label" => $doc->get('title'), "url" => $doc->get('url'));
                $titles[] = '"'.nc_search_util::db_escape($doc->get('title')).'"';
            }

            $titles = array_unique($titles);
        }

        // поиск точного соответствия в таблице с документами
        // по-хорошему следовало бы сначала сделать запрос к БД, а потом к индексу, однако
        // в случае запроса к индексу не получится так же просто отфильтровать уже совпавшие запросы
        $query = "SELECT `Catalogue_ID`, `Path`, `Title` FROM `%t%` ".
                ' WHERE `Title` LIKE "'.nc_search_util::db_escape($input).'%" '.
                ($titles ? " AND `Title` NOT IN (".join(", ", $titles).") " : "").
                " ORDER BY `Title` ".
                " LIMIT $limit";

        $documents = new nc_search_result();
        $documents->select_from_database($query);

        foreach ($documents as $doc) {
            array_unshift($suggestions, array("label" => $doc->get('title'), "url" => $doc->get('url')));
        }

        $suggestions = array_slice($suggestions, 0, $limit);
        return $suggestions;
    }

    /**
     * Проверить, есть ли слово с индексе.
     * @param string $term
     * @return boolean
     */
    public function has_term($term) {
        //return $this->get_index()->hasTerm(new Zend_Search_Lucene_Index_Term($term));
        $q = new Zend_Search_Lucene_Search_Query_Term(new Zend_Search_Lucene_Index_Term($term));
        Zend_Search_Lucene::setResultSetLimit(1);
        $has_term = sizeof($this->get_index()->find($q)) > 0;
        Zend_Search_Lucene::setResultSetLimit($this->get_setting('ZendSearchLucene_ResultSetLimit'));
        return $has_term;
    }

    /**
     * Получить количество документов в индексе
     * @return integer
     */
    public function count_documents() {
        try {
            return $this->get_index()->numDocs();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Получить количество слов в индексе
     * @return integer
     */
    public function count_terms() {
        try {
            return count($this->get_index()->terms());
        } catch (Exception $e) {
            return 0;
        }
    }

}
