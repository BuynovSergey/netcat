<?php

/* $Id: synonyms.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Синоним
 */
class nc_search_language_synonyms extends nc_search_data_persistent {

    protected $properties = array(
            'id' => null,
            'language' => null,
            'words' => array(),
            'dont_filter' => false, // опция используется при сохранении данных
    );
    protected $table_name = 'Search_Synonym';
    protected $mapping = array(
            'id' => 'Synonyms_ID',
            'language' => 'Language',
            'words' => 'Words',
    );
    protected $serialized_properties = array('words');

    /**
     * Перед сохранением нужно прогнать список слов через фильтры
     */
    public function save() {
        $mb_case = nc_search::get_setting('FilterStringCase');
        $apply_filter = !$this->get('dont_filter');
        $list = array();

        foreach ($this->get('words') as $word) {
            $word = trim($word);
            if (strlen($word)) { // пропустить пустые значения
                // преобразовать регистр, если в дальнейшем не будут применены фильтры
                $list[] = ($apply_filter ? $word : mb_convert_case($word, $mb_case));
            }
        }

        if ($apply_filter) {
            $context = new nc_search_context(array('language' => $this->get('language')));
            $list = nc_search_extension_manager::get('nc_search_language_filter', $context)
                            ->until_first('nc_search_language_filter_synonyms')
                            ->apply('filter', $list);
        }

        if (sizeof($list) < 2) {
            throw new nc_search_data_exception(NETCAT_MODULE_SEARCH_ADMIN_SYNONYM_LIST_MUST_HAVE_AT_LEAST_TWO_WORDS);
        }

        $this->set('words', $list);

        parent::save();
    }

}
