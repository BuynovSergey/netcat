<?php

/**
 * Класс для получения информации о товарах, записанной в компонентах
 * netcat_page_block_goods_common_data (компоненты nc_page_block_*, модуль requests)
 */
class nc_subdivision_goods_data {

    static protected $instances;
    protected $component_keyword = 'netcat_page_block_goods_common_data';

    protected $subdivision_id;
    protected $infoblock_id;

    protected $items;
    protected $has_disabled_items = false;

    /**
     * @param $subdivision_id
     * @return self
     */
    static public function for_subdivision($subdivision_id) {
        if (!isset(self::$instances[$subdivision_id])) {
            self::$instances[$subdivision_id] = new self($subdivision_id);
        }
        return self::$instances[$subdivision_id];
    }

    /**
     * nc_subdivision_goods_data constructor.
     *
     * @param $subdivision_id
     */
    protected function __construct($subdivision_id) {
        $this->subdivision_id = $subdivision_id;
    }

    /**
     * @return array
     */
    protected function load_items() {
        $nc_core = nc_core::get_object();

        if (!$this->subdivision_id) {
            return array();
        }

        try {
            $component_id = $nc_core->component->get_by_id($this->component_keyword, 'Class_ID');
        } catch (Exception $e) {
            return array();
        }

        try {
            $this->infoblock_id = $infoblock_id = $nc_core->sub_class->get_first_subdivision_infoblock_by_component_id(
                $this->subdivision_id, $component_id, 'Sub_Class_ID'
            );
        } catch (Exception $e) {
            return array();
        }

        $result = array();
        if ($infoblock_id) {
            $component = $nc_core->get_component($component_id);
            try {
                $order_by = $nc_core->sub_class->get_by_id($infoblock_id, 'SortBy') ?: 'a.`Priority` DESC';
            } catch (Exception $e) {
                return array();
            }
            $query = "SELECT " . $component->get_fields_query() . "
                        FROM `Message{$component_id}` AS a " . $component->get_joins() . "
                       WHERE a.`Sub_Class_ID` = $infoblock_id
                       ORDER BY $order_by";

            $result = $nc_core->db->get_results($query, ARRAY_A) ?: array();

            $this->has_disabled_items = false;
            foreach ($result as $row) {
                // → → → insert netshop price/availability sync here ← ← ←
                if (!$row['Checked']) {
                    $this->has_disabled_items = true;
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает все записи о товарах
     * @return array
     */
    public function get_all_items() {
        if ($this->items === null) {
            $this->items = $this->load_items();
        }
        return $this->items;
    }

    /**
     * Возвращает включённые записи о товарах
     * @return array
     */
    public function get_enabled_items() {
        $items = $this->get_all_items();
        if (!$this->has_disabled_items) {
            return $items;
        }
        else {
            $result = array();
            foreach ($items as $row) {
                if ($row['Checked']) { $result[] = $row; }
            }
            return $result;
        }
    }

    /**
     * Возвращает первую запись о товаре
     * @param string|null $property
     * @return mixed
     */
    public function get_first_item($property = null) {
        $items = $this->get_enabled_items();

        if ($property) {
            return isset($items[0][$property]) ? $items[0][$property] : null;
        }
        else {
            return isset($items[0]) ? $items[0] : null;
        }
    }

    /**
     * Возвращает запись о товаре по идентификатору (в компоненте netcat_page_block_goods_common_data)
     * @param $item_id
     * @return array|null
     */
    public function get_item_by_id($item_id) {
        foreach ($this->get_all_items() as $item) {
            if ($item['Message_ID'] == $item_id) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Проверяет, связаны ли товары netcat_page_block_goods_common_data с
     * товарами в интернет-магазине
     * @return bool
     */
    public function are_netshop_items() {
        if (!nc_module_check_by_keyword('netshop')) {
            return false;
        }

        $items = $this->get_all_items();
        if (!$items) {
            return false;
        }

        $goods_components = nc_netshop::get_instance()->get_goods_components_ids();

        foreach ($items as $item) {
            if (!$item['Item_Component_ID'] || !in_array($item['Item_Component_ID'], $goods_components)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Возвращает оверлей, открывающий диалог редактирования свойств товаров
     * @param string $icon_size размер (как для функции nc_modal_dialog_trigger())
     * @return string
     */
    public function get_edit_trigger_overlay($icon_size = 'xlarge') {
        return nc_modal_dialog_trigger("index.php?cc_only=$this->infoblock_id&nc_ctpl=multi_edit&isNaked=1", $icon_size);
    }

}
