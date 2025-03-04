<?php

class nc_routing_route_collection extends nc_record_collection {

    /** @var nc_routing_route[] */
    protected $items = array();

    /**
     * @param nc_routing_request $request
     * @return nc_routing_result|false
     */
    public function resolve(nc_routing_request $request) {
        $first_partial_match = null;
        if ($this->items) {
            foreach ($this->items as $route) {
                $result = $route->resolve($request);
                if ($result) {
                    if ($result->is_exact_match()) {
                        return $result;
                    }
                    if (!$first_partial_match) {
                        $first_partial_match = $result;
                    }
                }
            }
        }
        return $first_partial_match ?: false;
    }

}