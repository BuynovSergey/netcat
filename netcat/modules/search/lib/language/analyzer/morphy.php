<?php

/* $Id: morphy.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 *
 */
class nc_search_language_analyzer_morphy extends nc_search_language_analyzer {

    /**
     * @var array of phpMorphy
     */
    static protected $instances = array();

    protected $language;
    protected $frequent_words = array();

    /**
     * @throws nc_search_exception
     * @return phpMorphy
     */
    protected function get_morphy() {
        $language = $this->context->get('language');
        $language = $language."_".$language; // phpMorphy requires "ru_ru", "en_en"
        $this->language = $language;

        if (!isset(self::$instances[$language])) {
            if (!class_exists('phpMorphy', false)) {
                nc_search::load_3rdparty_script("phpmorphy/src/common.php");
            }

            if (nc_search::should('PhpMorphy_LoadDictsDuringIndexing') && $this->context->get('action') == 'indexing') {
                $storage = PHPMORPHY_STORAGE_MEM;
            }
            else {
                $storage = PHPMORPHY_STORAGE_FILE;
            }

            $options = array(
                    'storage' => $storage,
                    'predict_by_suffix' => true,
                    'predict_by_db' => true,
            );

            // Path to directory where dictionaries are located
            $dict_path = nc_search::get_3rdparty_path().'/phpmorphy/dicts';

            try {
                self::$instances[$language] = new phpMorphy($dict_path, $language, $options);
            } catch (phpMorphy_Exception $e) {
                throw new nc_search_exception("Error occurred while creating phpMorphy instance: {$e->getMessage()}");
            }
        }

        return self::$instances[$language];
    }

    /**
     *
     * @param string $word
     * @param boolean $predict
     * @return false|string|array
     */
    protected function lemmatize($word, $predict) {
        // frequently used words cache
        // (this cache makes indexing up to 15..20% faster)
        if ($this->language == 'ru_ru') { // load frequent words
            if (!isset($this->frequent_words['ru'])) { include_once(dirname(__FILE__) . "/morphy/ru.php"); }
            if (isset($this->frequent_words['ru'][$word])) { // cache hit
                $lemmas = $this->frequent_words['ru'][$word];
                if ($lemmas == 1) { return array($word); }
                return (array)$lemmas;
            }
        }

        $morphy = $this->get_morphy();
        $type = ($predict ? phpMorphy::NORMAL : phpMorphy::IGNORE_PREDICT);
        return $morphy->getBaseForm($word, $type);
    }

    /**
     *
     * @param array $terms
     * @return array
     */
    public function get_base_forms(array $terms) {
        $result = array();
        $group_alternatives = $this->context->get('group_alternative_forms');

        foreach ($terms as $term) {
            $base_form = $this->lemmatize($term, true);
            $lemmas = (array)($base_form ? $base_form : $term);
            $num_lemmas = count($lemmas);

            if ($group_alternatives && $num_lemmas > 1) {
                $result[] = $lemmas;
            }
            else {
                for ($i=0; $i < $num_lemmas; $i++) {
                    $result[] = $lemmas[$i];
                }
            }
        }

        return $result;
    }

    /**
     *
     * @param array $terms
     * @return string
     */
    public function get_highlight_regexp(array $terms) {
        $morphy = $this->get_morphy();
        $result = array();
        foreach ($terms as $term) {
            $paradigms = $morphy->findWord($term);
            if ($paradigms) {
                foreach ($paradigms as $paradigm) {
                    $result = array_merge($result, $paradigm->getAllForms());
                }
            } else { // phpMorphy doesn't know what to do with that term
                $result[] = $term;
            }
        }
        $regexp = "(".join("|", array_unique($result)).")";
        return nc_search_util::word_regexp($regexp, "Si");
    }

    /**
     * Проверка слова по словарю
     * @param string  ВНИМАНИЕ, предполагается, что слово передано в корректном регистре (ЗАГЛАВНОМ)
     * @return boolean слово есть в словаре
     */
    public function check_word($word) {
        return ($this->lemmatize($word, false) != false);
    }

}
