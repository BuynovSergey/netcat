<?php

/**
 * $Id: basic.php 6209 2012-02-10 10:28:29Z denis $
 */

/**
 * Простой корректор:
 *  - пробует исправить раскладку
 *  - пробует разбивать фразы на слова
 *  - если ничего не получилось, пробует нечёткий поиск (если установлены параметры
 *    модуля 'PerformFuzzySearchOnEmptyResult' и 'AllowFuzzySearch')
 * Проверка слов производится по индексу и средствами language_analyzer.
 * (nc_search_provider::has_term() и nc_search_language_analyzer::lookup())
 */
class nc_search_language_corrector_basic extends nc_search_language_corrector {

    /**
     * @var nc_search_language_analyzer
     */
    protected $analyzer;
    /**
     * @var nc_search_provider
     */
    protected $provider;
    protected $layout_ru_en = array(
            'ё' => '`', 'й' => 'q', 'ц' => 'w', 'у' => 'e', 'к' => 'r', 'е' => 't', 'н' => 'y', 'г' => 'u', 'ш' => 'i', 'щ' => 'o', 'з' => 'p', 'х' => '[', 'ъ' => ']',
            'ф' => 'a', 'ы' => 's', 'в' => 'd', 'а' => 'f', 'п' => 'g', 'р' => 'h', 'о' => 'j', 'л' => 'k', 'д' => 'l', 'ж' => ';', 'э' => '\'',
            'я' => 'z', 'ч' => 'x', 'с' => 'c', 'м' => 'v', 'и' => 'b', 'т' => 'n', 'ь' => 'm', 'б' => ',', 'ю' => '.',
            'Ё' => '~', 'Й' => 'Q', 'Ц' => 'W', 'У' => 'E', 'К' => 'R', 'Е' => 'T', 'Н' => 'Y', 'Г' => 'U', 'Ш' => 'I', 'Щ' => 'O', 'З' => 'P', 'Х' => '{', 'Ъ' => '}',
            'Ф' => 'A', 'Ы' => 'S', 'В' => 'D', 'А' => 'F', 'П' => 'G', 'Р' => 'H', 'О' => 'J', 'Л' => 'K', 'Д' => 'L', 'Ж' => ':', 'Э' => '"',
            'Я' => 'Z', 'Ч' => 'X', 'С' => 'C', 'М' => 'V', 'И' => 'B', 'Т' => 'N', 'Ь' => 'M', 'Б' => '<', 'Ю' => '>',
    );
    protected $layout_en_ru = array(// E => Ё
            '`' => 'е', 'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з', '[' => 'х', ']' => 'ъ',
            'a' => 'ф', 's' => 'ы', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л', 'l' => 'д', ';' => 'ж', '\'' => 'э',
            'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь', ',' => 'б', '.' => 'ю',
            '~' => 'Е', 'Q' => 'Й', 'W' => 'Ц', 'E' => 'У', 'R' => 'К', 'T' => 'Е', 'Y' => 'Н', 'U' => 'Г', 'I' => 'Ш', 'O' => 'Щ', 'P' => 'З', '{' => 'Х', '}' => 'Ъ',
            'A' => 'Ф', 'S' => 'Ы', 'D' => 'В', 'F' => 'А', 'G' => 'П', 'H' => 'Р', 'J' => 'О', 'K' => 'Л', 'L' => 'Д', ':' => 'Ж', '"' => 'Э',
            'Z' => 'Я', 'X' => 'Ч', 'C' => 'С', 'V' => 'М', 'B' => 'И', 'N' => 'Т', 'M' => 'Ь', '<' => 'Б', '>' => 'Ю',
    );

    /**
     *
     * @param nc_search_language_corrector_phrase $phrase
     * @return boolean
     */
    public function correct(nc_search_language_corrector_phrase $phrase) {
        // init, required
        $this->analyzer = nc_search_extension_manager::get('nc_search_language_analyzer', $this->context)
                        ->first(); // «ПЕРВЫЙ ПОПАВШИЙСЯ»! предполагается, что анализатор вообще-то один
        $this->provider = nc_search::get_provider();

        // для начала определимся, можем ли мы что-то сделать?
        $unknown_terms = $this->get_unknown_terms($phrase);
        if (!$unknown_terms) {
            return false;
        } // false or empty array
        $input_count = count($unknown_terms);

        // этап 2: пробуем исправить слово
        // раскладка
        if (nc_search::should('ChangeLayoutOnEmptyResult')) {
            $unknown_terms = $this->change_keyboard_layout($unknown_terms);
        }

        // разбивка на слова
        if (nc_search::should('BreakUpWordsOnEmptyResult')) {
            $unknown_terms = $this->break_up_words($unknown_terms);
        }

        // fuzzy search
        if (nc_search::should('PerformFuzzySearchOnEmptyResult') && nc_search::should('AllowFuzzySearch')) {
            $this->add_fuzzy_search($unknown_terms);
            $everything_corrected = true;
        } else {
            $count = count($unknown_terms);
            $everything_corrected = ($count == 0 || ($count < $input_count && nc_search::get_setting('DefaultBooleanOperator') == 'OR'));
        }

        return $everything_corrected;
    }

    /**
     * Возвращает массив со словами, которых нет в индексе и в словаре
     * @param nc_search_language_corrector_phrase $phrase
     * @return array|false
     */
    protected function get_unknown_terms(nc_search_language_corrector_phrase $phrase) {
        $all_terms = $phrase->get_not_corrected_terms();
        if (!sizeof($all_terms)) {
            return false;
        }

        $stopwords_analyzer = false;
        if (nc_search::should('RemoveStopwords')) {
            $stopwords_analyzer = new nc_search_language_filter_stopwords($this->context);
        }

        $unknown_terms = array();
        foreach ($all_terms as $term) {
            // строка должна быть в правильном регистре, чтобы анализатор мог её корректно обработать
            $string = $term->get('term');
            // выкинем стоп-слова для начала
            if ($stopwords_analyzer && !$stopwords_analyzer->filter(array($string))) {
                continue;
            }

            // проверка по индексу
            if (!$this->provider_lookup($string)) {
                $unknown_terms[] = $term;
                $term->set('is_incorrect', true);
                continue; // go to next term
            }

            // проверка по словарю
            $analyzer_result = $this->analyzer_lookup($string);
            if ($analyzer_result !== true) { // FALSE или STRING
                $unknown_terms[] = $term;
                $term->set('is_incorrect', true);
                if (is_string($analyzer_result)) {
                    $term->set('corrected_term', $analyzer_result);
                }
            }
        }
        return $unknown_terms;
    }

    /**
     * @param string $term
     * @return boolean
     */
    protected function provider_lookup($term) {
        $term = mb_convert_case($term, nc_search::get_setting('FilterStringCase'), 'UTF-8');
        if ($this->analyzer) {
            $base_forms = $this->analyzer->get_base_forms(array($term));
            $term = $base_forms[0];
        }
        return $this->provider->has_term($term);
    }

    /**
     * @param string $term
     * @return boolean|string
     */
    protected function analyzer_lookup($term) {
        if (!$this->analyzer) {
            return true;
        }

        $term = mb_convert_case($term, nc_search::get_setting('FilterStringCase'), 'UTF-8');
        $result = $this->analyzer->check_word($term); // true|false|array
        if (is_array($result)) { // БЛОК НЕ ТЕСТИРОВАЛСЯ; НА МОМЕНТ НАПИСАНИЯ НЕТ СПЕЛЛЧЕКЕРА
            foreach ($result as $word) { // сверимся по индексу, чтобы выбрать лучший вариант
                if ($this->provider_lookup($word)) {
                    return $word;
                }
            }
            return false;
        }
        return $result;
    }

    /**
     *
     * @param array $unknown_terms
     * @return array
     */
    protected function change_keyboard_layout(array $unknown_terms) {
        $returned = array();
        foreach ($unknown_terms as $term) {
            $string = $term->get('term');
            // if there're some latin letters, use layout_en_ru:
            $trans_table = (preg_match("/[a-z]/i", $string)) ? $this->layout_en_ru : $this->layout_ru_en;

            $string_in_another_layout = strtr($string, $trans_table);
            if ($string_in_another_layout != $string) {
                // меняем регистр только сейчас
                if ($this->provider_lookup($string_in_another_layout)) { // wow! it worked!
                    $term->set_corrected($string_in_another_layout);
                } else {
                    $returned[] = $term;
                }
            } else {
                $returned[] = $term;
            }
        }

        return $returned;
    }

    /**
     *
     * @param array $unknown_terms
     * @return array
     */
    protected function break_up_words(array $unknown_terms) {
        $returned = array();
        foreach ($unknown_terms as $term) { // input array might be empty as well
            if (!$this->break_up_word($term)) {
                $returned[] = $term;
            }
        }

        return $returned;
    }

    /**
     *
     * @param nc_search_language_corrector_phrase_term $term
     * @return boolean success
     */
    protected function break_up_word(nc_search_language_corrector_phrase_term $term) {
        $string = $term->get('term');
        $strlen = mb_strlen($string);
        if ($strlen < 4) {
            return false;
        } // минимум 4 символа
        $letters = preg_split("/(?<!^)(?!$)/u", $string);
        $first_word = "";
        for ($i = 0; $i < $strlen - 1; $i++) {
            $first_word .= array_shift($letters);
            if ($this->provider_lookup($first_word)) {
                $second_word = join('', $letters);
                if ($this->provider_lookup($second_word)) {
                    $term->set_corrected("$first_word $second_word");
                    return true; // gotcha
                }
            }
        }
        return false;
    }

    /**
     *
     * @param array $unknown_terms
     * @return array always empty
     */
    protected function add_fuzzy_search(array $unknown_terms) {
        $factor = nc_search::get_setting('FuzzySearchOnEmptyResultSimilarityFactor');
        foreach ($unknown_terms as $term) {
            $term->set_corrected($term->get('term')."~$factor");
        }
        return array();
    }

}