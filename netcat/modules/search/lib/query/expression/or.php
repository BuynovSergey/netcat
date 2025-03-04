<?php
/**
 *
 */
class nc_search_query_expression_or extends nc_search_query_expression_composite {

    /**
     * Заменяет последний операнд выражением AND(последний_операнд, новый_операнд)
     * @param nc_search_query_expression $new_item
     * @return nc_search_query_expression_or
     */
    public function conjunct_last(nc_search_query_expression $new_item) {
        $last_item = array_pop($this->items);
        if ($last_item instanceof nc_search_query_expression_and) { // already an AND
            $last_item->add_item($new_item);
            $this->add_item($last_item);
        }
        else {
            $this->add_item(new nc_search_query_expression_and($last_item, $new_item));
        }
        return $this;
    }
}