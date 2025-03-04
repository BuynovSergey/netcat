<?php
/**
 *
 */
class nc_search_query_expression_phrase extends nc_search_query_expression_composite {

    /** @var nc_search_query_expression_term[] */
    protected $items = array();
    protected $distance = 0;
    protected $boost = 1;

    /**
     * @param array $phrase_items
     */
    public function __construct(array $phrase_items = null) {
        if (is_array($phrase_items)) {
            foreach ($phrase_items as $word) {
                $this->add_item(new nc_search_query_expression_term($word));
            }
        }
    }

    /**
     * @param nc_search_query_expression $item
     * @return nc_search_query_expression_phrase
     */
    public function add_item(nc_search_query_expression $item) {
        if ($item instanceof nc_search_query_expression_term) {
            $item->set_boost($this->boost);
            return parent::add_item($item);
        }
        return $this;
    }

    /**
     * @param boolean $is_excluded
     * @return nc_search_query_expression
     */
    public function set_excluded($is_excluded) {
        $this->is_excluded = (bool)$is_excluded;
        foreach ($this->items as $item) {
            $item->set_excluded($this->is_excluded);
        }
        return $this;
    }

    public function set_boost($boost) {
        $this->boost = (float)$boost;
        foreach ($this->items as $item) {
            $item->set_boost($this->boost);
        }
        return $this;
    }

    public function get_boost() {
        return $this->boost;
    }

    public function set_distance($distance) {
        $this->distance = max(0, (int)$distance);
        return $this;
    }

    public function get_distance() {
        return $this->distance;
    }

}
