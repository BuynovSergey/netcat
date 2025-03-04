<?php

/**
 * Вспомогательный класс для получения версий для кнопок undo / redo
 */

class nc_version_history_retriever {

    protected $page_data = array(
        // ключи, которые должны быть в $routing_data (также передаются в $nc_core->sub_class->get_by_area_keyword()):
        'action' => null,
        'site_id' => null,
        'folder_id' => null,
        'infoblock_id' => null,
        'object_id' => null,
    );

    protected $page_areas = array();

    /**
     * @param array $routing_data данные для нахождения страницы (такие же, как routing data страницы)
     * @param array $areas названия областей, которые могут быть на текущей странице (main и дополнительные)
     */
    public function __construct(array $routing_data, array $areas) {
        $this->page_data = $routing_data + $this->page_data; // добавляем дефолтное значение для создания всех необходимых ключей
        $this->page_areas = $areas;
    }

    /**
     * @return nc_version_record[]
     */
    public function get_versions_for_undo_and_redo() {
        $select_where = array(
            $this->get_subdivision_select_where(),
            $this->get_infoblock_select_where(),
            $this->get_object_select_where(),
            $this->get_site_select_where(),
        );

        $conditions = implode("\nOR\n", array_filter($select_where));

        return array(
            $this->get_version_for_undo($conditions),
            $this->get_version_for_redo($conditions),
        );
    }

    /**
     * @return nc_version_record|null
     */
    protected function get_version_for_undo($conditions) {
        $nc_core = nc_core::get_object();
        $undo_id = $nc_core->db->get_var(
            "SELECT `Version_ID`
               FROM `Version_ContentHistory`
              WHERE ($conditions)
                AND `State` = 'actual'
              ORDER BY `Version_ContentHistory_ID` DESC
              LIMIT 1"
        );

        if ($undo_id) {
            try {
                $undo_version = $nc_core->version->load_by_id($undo_id);

                if ($undo_version->get('Action') !== nc_version_record::INITIAL) {
                    return $undo_version;
                }
            } catch (Exception $ignored) {
            }
        }

        return null;
    }

    /**
     * @return nc_version_record|null
     */
    protected function get_version_for_redo($conditions) {
        $nc_core = nc_core::get_object();
        $redo_id = $nc_core->db->get_var(
            "SELECT `Version_ID`
               FROM `Version_ContentHistory`
              WHERE ($conditions)
                AND `State` = 'next'
              ORDER BY `Version_ContentHistory_ID` ASC
              LIMIT 1"
        );

        if ($redo_id) {
            try {
                return $nc_core->version->load_by_id($redo_id);
            } catch (Exception $e) {
            }
        }

        return null;
    }

    /**
     * Собирает условие для WHERE из массива
     *
     * @param array $conditions ключ — имя поля, значение может быть массивом. Значения не экранируются!
     * @return string
     */
    protected function make_where_clause(array $conditions) {
        $where = "";
        foreach ($conditions as $field => $value) {
            if ($value != 0 && !$value) {
                continue;
            }

            $where .= ($where ? " AND " : "") . "`$field`";

            if (is_array($value)) {
                $where .= " IN ('" . implode("', '", $value) . "')";
            } else {
                $where .= " = '$value'";
            }
        }
        return $where ? "($where)" : '';
    }

    /**
     * Условия для выборки для страницы раздела: инфоблоки и объекты в них, которые расположены в текущем разделе
     *
     * @return string|null
     */
    protected function get_subdivision_select_where() {
        if ($this->page_data['infoblock_id']) {
            // это страница инфоблока, либо страница объекта
            return null;
        }

        $subdivision_id = $this->page_data['folder_id'];

        global $perm;
        if (!$perm->isSubdivisionAdmin($subdivision_id)) {
            return null;
        }

        return $this->make_where_clause(array(
            'Subdivision_ID' => $subdivision_id,
        ));
    }

    /**
     * Условия для выборки для страницы инфоблока
     *
     * @return string|null
     */
    protected function get_infoblock_select_where() {
        if (!$this->page_data['infoblock_id'] || $this->page_data['object_id']) {
            // это не страница инфоблока, либо страница объекта
            return null;
        }

        $infoblock_id = $this->page_data['infoblock_id'];

        global $perm;
        if (!$perm->isSubClassAdmin($infoblock_id)) {
            return null;
        }

        return $this->make_where_clause(array(
            'Sub_Class_ID' => $infoblock_id,
        ));
    }

    /**
     * Условия для выборки для страницы объекта
     *
     * @return string|null
     */
    protected function get_object_select_where() {
        $infoblock_id = $this->page_data['infoblock_id'];
        $object_id = $this->page_data['object_id'];

        if (!$infoblock_id || !$object_id) {
            return null;
        }

        global $perm;
        if (!$perm->isSubClassAdmin($infoblock_id)) {
            return null;
        }

        // изменения для текущей страницы
        return $this->make_where_clause(array(
            'Sub_Class_ID' => $infoblock_id,
            'Message_ID' => $object_id,
        ));
    }

    /**
     * Условия для выборки сквозных инфоблоков и объектов в них
     *
     * @return string|null
     */
    protected function get_site_select_where() {
        $infoblock_ids = $this->get_infoblock_ids_on_page();
        if (!$infoblock_ids) {
            return null;
        }

        return $this->make_where_clause(array(
            'Sub_Class_ID' => $infoblock_ids,
        ));
    }

    /**
     * Возвращает ID сквозных инфоблоков на странице (с учётом условий их показа и прав пользователя)
     *
     * @return array
     */
    protected function get_infoblock_ids_on_page() {
        $infoblock_ids = array();
        foreach ($this->page_areas as $area) {
            $this->collect_infoblock_ids_recursively($infoblock_ids, $area);
        }
        return $infoblock_ids;
    }

    /**
     * Добавляет в $infoblock_ids идентификаторы всех блоков в указанной области
     *
     * @param array $infoblock_ids
     * @param string $area
     * @param int $container_id
     * @return void
     */
    protected function collect_infoblock_ids_recursively(array &$infoblock_ids, $area, $container_id = 0) {
        global $perm, $nc_core;

        $infoblocks = $nc_core->sub_class->get_by_area_keyword($area, $this->page_data, $container_id);

        foreach ($infoblocks as $infoblock) {
            if ($perm->isSubClassAdmin($infoblock['Sub_Class_ID'])) {
                $infoblock_ids[] = $infoblock['Sub_Class_ID'];
            }
            if (!$infoblock['Class_ID']) {
                $this->collect_infoblock_ids_recursively($infoblock_ids, $area, $infoblock['Sub_Class_ID']);
            }
        }
    }
}
