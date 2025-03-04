<?php

class nc_landing_preset_collection extends nc_record_collection {
    protected $items_class = 'nc_landing_preset';
    protected $index_property = 'keyword';

    /**
     * Возвращает пресеты из текущей коллекции, подходящие для конкретного компонента
     * @param $component_id
     * @return nc_landing_preset_collection
     */
    public function for_component($component_id) {
        if ($component_id) {
            return $this->where('can_be_used_for_component', true, '==', array($component_id));
        }
        else {
            return $this;
        }
    }

}
