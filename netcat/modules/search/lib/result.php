<?php

/**
 * Результат поиска
 */
class nc_search_result extends nc_search_data_persistent_collection {

    protected $items_class = 'nc_search_result_document';
    /**
     * @var nc_search_query
     */
    protected $query;
    /**
     * @var string
     */
    protected $highlight_regexp;
    /**
     * @var boolean
     */
    protected $is_highlighting_enabled = true;
    /**
     * @var string
     */
    protected $correction_suggestion;
    /**
     * @var string
     */
    protected $error;
    /**
     * @var float
     */
    protected $search_time;

    /**
     * Установить параметры документа, такие как подсвеченный текст.
     * (Это лучше сделать при добавлении документа для того, чтобы не возникало
     * повторной обработки данных)
     * @param nc_search_result_document $doc
     * @param null $offset
     * @return nc_search_data_persistent_collection
     */
    public function add($doc, $offset = null) {
        $this->document_set_url($doc);
        $this->document_set_context($doc);

        return parent::add($doc, $offset);
    }

    /**
     *
     */
    public function set_query(nc_search_query $query) {
        $this->query = $query;
        return $this;
    }

    /**
     * @return nc_search_query
     */
    public function get_query() {
        return $this->query;
    }

    /**
     *
     */
    public function get_query_string() {
        if (!$this->query) {
            return null;
        }
        return $this->query->get('query_string');
    }

    /**
     * Установить свойство 'url' документа: путь от корня сайта, если документ
     * на текущем сайте, и полный путь с именем хоста, если на другом сайте
     */
    protected function document_set_url(nc_search_result_document $doc) {
        // служба поиска, теоретически, может установить свойство url
        if ($doc->get('url')) {
            return $doc;
        } // NOTHING TO DO

        global $catalogue;
        $doc_site_id = $doc->get('site_id');
        if ($catalogue == $doc_site_id) {
            // хост не подставляется, потому пользователь может быть на «зеркале»
            $doc->set('url', $doc->get('path'));
        } else {
            $nc_core = nc_Core::get_object();
            $doc->set('url', $nc_core->catalogue->get_url_by_id($doc_site_id) . $doc->get('path'));
        }
        return $doc;
    }

    /**
     * Отключает подсветку совпавших фрагментов в результатах
     */
    public function disable_highlighting() {
        $this->is_highlighting_enabled = false;
    }

    /**
     * 
     */
    protected function should_highlight() {
        if (!nc_search::should('ShowMatchedFragment')) {
            return false;
        }
        if (!$this->query) {
            return false;
        }
        return ($this->is_highlighting_enabled);
    }

    /**
     * Установить свойство 'context' документа (фрагменты совпавшего с запросом текста)
     * (не имеет отношения к nc_search_context)
     */
    protected function document_set_context(nc_search_result_document $doc) {
        // служба поиска (напр. Гугль какой-нибудь), теоретически, может установить свойство context
        if (!$doc->get('context') && $this->should_highlight()) {

            $language = $this->get_query()->get('language');

            $doc->set('context',
                      $this->highlight($doc->get('content'),
                                       $language,
                                       nc_search::get_setting('ResultContextMaxNumberOfWords')));

            $doc->set('title',
                      $this->highlight($doc->get('title'),
                                       $language,
                                       nc_search::get_setting('ResultTitleMaxNumberOfWords')));
        }
        return $doc;
    }

    /**
     *
     * @param $text
     * @param $language
     * @param $max_num_words
     * @return string
     */
    protected function highlight($text, $language, $max_num_words) {
        if (!strlen(trim($text)) || !$this->query) {
            // пусто, нечего делать
            return $text;
        }

        // Разбивка текста на «предложения»: знаки препинания ".!?", после которых
        // идёт whitespace, или двойные переносы строк. Знаки препинания добавляются
        // к тексту (look-ahead + look-behind в качестве разделителя).
        $sentences = preg_split("/(?<=[.?!])(?=\s)|(?:\s*\r?\n\s*){2,}/u", $text, -1, PREG_SPLIT_NO_EMPTY);
        $match_count = array();

        $regexp = $this->get_highlight_regexp($language);

        // на момент обработки используются спецсимволы:
        // \1 - начало совпадения
        // \2 - конец совпадения
        // \3 - пропуск (многоточие, ellipsis)
        //  $regexp = nc_search_util::convert($regexp, 1);
        foreach ($sentences as $n => $s) {
            $s = trim($s);
            $sentences[$n] = preg_replace($regexp, "\x01$1\x02", $s, -1, $count);
            $match_count[$n] = $count;
        }

        // правила формирования:
        // - показывается фрагмент с наибольшим количеством совпадений
        // - если предыдущее предложение заканчивается на слово короче 3 букв и точку
        //   (предположительно — сокращение, инициал), его следует «прилепить»
        //   к текущему;
        // - если совпадение дальше $max_num_words от начала, обрезать
        //   начало строки
        // - всего в фрагменте не более $max_num_words слов;
        // - если в фрагменте менее $max_num_words слов, попробовать прилепить
        //   следующий по количеству совпадений фрагмент (если есть), поставить
        //   между ними ellipsis

        arsort($match_count, SORT_NUMERIC);
        $n = nc_search_util::get_nth_key($match_count, 0);
        $fragment = $sentences[$n];
        $prev = $next = $n;
        $short_word_regexp = '/(?<![\pL\d])[\x01\02]?\pL{1,2}[\x01\02]?\.$/u';
        while (--$prev >= 0 && preg_match($short_word_regexp, $sentences[$prev])) {
            $fragment = $sentences[$prev] . " " . $fragment;
        }

        while (isset($sentences[++$next]) && preg_match($short_word_regexp, $fragment)) {
            $fragment .= " " . $sentences[$next];
        }

        $num_words_in_fragment = preg_match_all('/[\pL\d]+/u', $fragment, $tmp); // $tmp нужна в PHP 5.3

        if ($num_words_in_fragment < $max_num_words - 5) { // максимум будет показано два фрагмента
            $another_n = nc_search_util::get_nth_key($match_count, 1);
            if ($another_n !== null) {
                if ($match_count[$another_n] == 0) {
                    // Если совпадений по следующему фрагменту нет, добавляем первое предложение
                    // (или, если первое предложение выбрано основным, то второе)
                    $another_n = $n == 0 ? 1 : 0;
                }
                $another_fragment = $this->cut_fragment($sentences[$another_n], $max_num_words - $num_words_in_fragment);
                $shift = $another_n - $n;
                if ($shift < -1) {
                    $fragment = "$another_fragment \3 $fragment";
                } elseif ($shift > 1) {
                    $fragment = "$fragment \3 $another_fragment";
                } elseif ($shift > 0) {
                    if (!preg_match('/[!?.]$/', $fragment)) {
                        $fragment .= ".";
                    }
                    $fragment = "$fragment $another_fragment";
                } else {
                    if (!preg_match('/[!?.]$/', $another_fragment)) {
                        $another_fragment .= ".";
                    }
                    $fragment = "$another_fragment $fragment";
                }
            }
        }

        $fragment = $this->cut_fragment($fragment, $max_num_words);

        // убрать ":;," на конце фрагмента (такое может быть в списках)
        $fragment = preg_replace("/[:;,]$/", ".", $fragment);

        if (nc_search::should('HighlightMatchedWords')) {
            $open_tag = "<strong class='matched'>";
            $close_tag = "</strong>";
        } else {
            $open_tag = "";
            $close_tag = "";
        }

        $ellipsis = "<span class='skipped'>&hellip;</span>";
        $fragment = strtr($fragment, array(
                        // объединение последовательно идущих совпавших фрагментов
                        "\2 \1" => " ",
                        // тэги разметки
                        "\1" => $open_tag,
                        "\2" => $close_tag,
                        // двойной пропуск (если была обрезка + склейка фрагментов)
                        ",\3 \3" => " $ellipsis ",
                        ". \3 \3" => " $ellipsis ",
                        "\3 \3" => $ellipsis,
                        // удаление лишних знаков препинания
                        ",\3" => $ellipsis,
                        ": \3" => $ellipsis,
                        "; \3" => $ellipsis,
                        ". \3 " => " $ellipsis ",
                        // «простой» пропуск
                        "\3" => $ellipsis,
                ));

        return $fragment;
    }

    /**
     *
     */
    protected function cut_fragment($fragment, $max_num_words) {
        // replace all whitespace characters with a single space so we can use explode(" ") below
        $fragment = preg_replace("/\s+/u", " ", trim($fragment));
        // str_word_count is UTF8-incompatible...
        $words = explode(" ", $fragment);
        // the phrase is short enough, return it
        if (count($words) <= $max_num_words) {
            return $fragment;
        }

        // cut words at the beginning of the phrase
        list($before_match, $remainder) = explode("\1", $fragment, 2);

        if ($remainder) { // i.e. there's a matching word
            // try to divide the fragment roughly in half
            $half_max_num_words = intval($max_num_words / 2);
            $words_before_match = explode(" ", $before_match);
            $num_words_before_match = count($words_before_match);
            if ($num_words_before_match > $half_max_num_words) {
                $num_words_after_match = substr_count($remainder, " ") + 2; // including the matched word!
                $num_words_before_match_to_keep = $max_num_words - $num_words_after_match;
                if ($num_words_before_match_to_keep > 0) {
                    $before_match = join(" ", array_slice($words_before_match, -$num_words_before_match_to_keep));
                    $fragment = "\3$before_match\1$remainder";
                    $words = explode(" ", $fragment);
                }
            }
        }

        // remove words from the end of the phrase
        if (count($words) > $max_num_words) {
            $fragment = join(" ", array_slice($words, 0, $max_num_words))."\3";
        }

        return $fragment;
    }

    /**
     *
     */
    protected function get_highlight_regexp($language) {
        if (!$this->highlight_regexp) {
            $query_string = $this->get_query_string();
            $context = new nc_search_context(array('language' => $language, 'action' => 'searching'));

            // Получить слова из запроса.
            // (Удалять из запроса термины с префиксом "-" и "NOT" не имеет особого смысла,
            // поскольку в результат они как правило не попадают.)
            $query_string = preg_replace("/[\^~][\d\.]+/", '', $query_string); // операторы ^1, ~1
            preg_match_all("/[\pL\d\?\*]+/u", $query_string, $matches);
            $terms = $matches[0];

            if (strpos($query_string, "*") !== false || strpos($query_string, "?") !== false) {
                $wildcards_replacement = nc_search::should('AllowWildcardSearch') ? array("?" => ".", "*" => "[\S]+") : array("?" => "", "*" => "");
                foreach ($terms as $i => $term) {
                    $terms[$i] = strtr($term, $wildcards_replacement);
                }
            }

            //if ( nc_Core::get_object()->NC_UNICODE ) {
            $terms = nc_search_extension_manager::get('nc_search_language_filter', $context)
                            ->except('nc_search_language_filter_stopwords')
                            ->apply('filter', $terms);
            //}

            $analyzer = nc_search_extension_manager::get('nc_search_language_analyzer', $context)
                            ->first();

            if ($analyzer) {
                $regexp = $analyzer->get_highlight_regexp($terms);
            } else {
                $regexp = nc_search_util::word_regexp("(".join("|", $terms).")", "Si");
            }

            $this->highlight_regexp = $regexp;
        } // of "there was no 'highlight_regexp'"
        return $this->highlight_regexp;
    }

    /**
     * Сохранить подсказку (если запрос был переписан корректором)
     * @param string
     * @return nc_search_result
     */
    public function set_correction_suggestion($message) {
        $this->correction_suggestion = $message;
        return $this;
    }

    /**
     * Получить подсказку об изменённом запросе
     * @return string
     */
    public function get_correction_suggestion() {
        return $this->correction_suggestion;
    }

    /**
     *
     */
    public function set_error_message($msg) {
        $this->error = $msg;
        return $this;
    }

    /**
     *
     */
    public function get_error_message() {
        return $this->error;
    }

    /**
     * 
     */
    public function set_search_time($t) {
        $this->search_time = $t;
    }

    /**
     *
     */
    public function get_search_time() {
        return $this->search_time;
    }

}
