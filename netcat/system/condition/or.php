<?php

class nc_condition_or extends nc_condition_composite {

    /**
     * Полное описание
     * @return string
     */
    public function get_full_description() {
        return $this->get_children_descriptions(NETCAT_COND_OR, NETCAT_COND_OR_SAME);
    }

}