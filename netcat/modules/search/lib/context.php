<?php

/* $Id: context.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Контекст для менеджера расширений
 */
class nc_search_context extends nc_search_data {

    protected $properties = array(
            'search_provider' => null,
            'language' => null,
            'action' => null, // 'searching', 'indexing'
            'content_type' => null, // for selecting an appropriate parser depending on MIME type
            'group_alternative_forms' => null, // group multiple word base forms together [@see nc_search_language_analyzer_morphy]
    );

    /**
     * Сравнение контекста с правилом
     * @param nc_search_extension_rule $rule
     * @return boolean
     */
    public function conforms_to(nc_search_extension_rule $rule) {
        foreach ($this->properties as $key => $this_value) {
            // Значение NULL у свойства контекста означает «любое значение»
            if ($this_value === null) { continue; }
            if (!$rule->has_property($key)) { continue; }
            $rule_value = $rule->get($key);
            // Пустое значение у свойства правила расширения означает «любое значение»
            if ($rule_value !== null && $rule_value != '' && $rule_value != $this_value) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @return string
     */
    public function get_hash() {
        return crc32(serialize($this->properties));
    }

}
