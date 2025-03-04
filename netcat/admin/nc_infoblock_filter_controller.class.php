<?php

class nc_infoblock_filter_controller extends nc_ui_controller {

    protected $is_naked = false;
    protected $subdivision_list = array();
    protected $allowed_subs = array();
    protected $filter_type_names = array(
        'string' => NC_FILTER_TYPE_STRING_NAME,
        'number' => NC_FILTER_TYPE_NUMBER_NAME,
        'range' => NC_FILTER_TYPE_RANGE_NAME,
        'list' => NC_FILTER_TYPE_LIST_NAME,
        'multiple' => NC_FILTER_TYPE_MULTIPLE_NAME,
        'date' => NC_FILTER_TYPE_DATE_NAME,
        'date_range' => NC_FILTER_TYPE_DATE_RANGE_NAME,
    );

    protected function init() {
        $nc_core = nc_Core::get_object();
        if ($nc_core->input->fetch_get('isNaked')) {
            $this->is_naked = true;
        }
    }

    protected function after_action($result) {
        if ($this->is_naked) {
            return $result;
        }

        return BeginHtml() . $result . EndHtml();
    }

    public function action_show_filter_settings() {
        $nc_core = nc_Core::get_object();
        $this->is_naked = true;

        $filter_infoblock_id = $nc_core->input->fetch_get('filter_infoblock_id');
        $filter = nc_infoblock_filter::get_by_filter_infoblock_id($filter_infoblock_id);
        $data_infoblock_id = (int)$filter->get_data_infoblock_id();

        if ($filter->exists()) {
            // если показ формы с уже существующим фильтром, то надо заполнить все нужные данные для вывода
            $data_infoblock = $nc_core->sub_class->get_by_id($data_infoblock_id);
            $subdivision_id = $data_infoblock['Subdivision_ID'];
        } else {
            // если фильтра еще нет, то по умолчанию текущий раздел с фильтром
            $filter_infoblock = $nc_core->sub_class->get_by_id($filter_infoblock_id);
            $subdivision_id = $filter_infoblock['Subdivision_ID'];
            $data_infoblock_id = 0;
        }

        $infoblocks = $nc_core->db->get_results(
            "SELECT `Sub_Class_ID`, `Sub_Class_Name`, `Class_ID`
             FROM `Sub_Class`
             WHERE `Class_ID` != 0 AND `Subdivision_ID` = " . (int)$subdivision_id ."
             ORDER BY `Priority`",
            ARRAY_A
        );

        $infoblock_options = $this->make_infoblocks_options($infoblocks, $data_infoblock_id);

        // если нет выбранного инфоблока данных для фильтра (новый фильтр), то возьмем первый инфоблок на текущей странице
        if (!$data_infoblock_id) {
            $data_infoblock_id = $infoblocks[0]['Sub_Class_ID'];
        }

        if ($data_infoblock_id) {
            $component = $nc_core->get_component_by_infoblock_id($data_infoblock_id);
            $fields = $component->get_fields(nc_infoblock_filter::get_field_types());
        } else {
            $fields = array();
        }

        $filter_form = $this->view('filter/edit_filter_form', array(
            'fields' => $fields,
            'filter_types' => nc_infoblock_filter::get_filter_types(),
            'filter_type_names' => $this->filter_type_names,
        ));

        return $this->view('filter/edit_settings', array(
            'title' => $nc_core->sub_class->get_by_id($filter_infoblock_id, 'Sub_Class_Name'),
            'subdivisions' => $this->get_subdivisions(),
            'active_subdivision_id' => $subdivision_id,
            'filter' => $filter,
            'infoblock_options' => $infoblock_options,
            'filter_form' => $filter_form,
        ));
    }

    protected function get_subdivisions() {
        $this->subdivision_list = array();
        $this->get_subdivisions_tree(0);

        return $this->subdivision_list;
    }

    protected function get_subdivisions_tree($parent_sub_id, $count = 1, $need_check_perm = true) {
        global $perm;
        $nc_core = nc_Core::get_object();

        if ($need_check_perm) {
            $this->allowed_subs = $perm->GetAllowSub($this->site_id, MASK_ADMIN | MASK_MODERATE);
        }

        $security_limit = $this->allowed_subs && !$perm->isGuest() ? " `Subdivision_ID` IN (" . join(', ', (array)$this->allowed_subs) . ")" : " 1";

        $result = $nc_core->db->get_results(
            "SELECT `Subdivision_ID`, `Subdivision_Name`
             FROM `Subdivision` AS `sub`
             WHERE `Catalogue_ID` = " . (int)$this->site_id . " 
               AND `Parent_Sub_ID` = " . (int)$parent_sub_id . " 
               AND " . $security_limit . "
             ORDER BY `Priority`",
            ARRAY_A
        );

        if (!empty($result)) {
            foreach ($result as $row) {
                $this->subdivision_list[$row['Subdivision_ID']] = str_repeat('[space][space][space][space]', $count) . $row['Subdivision_Name'];
                $this->get_subdivisions_tree($row['Subdivision_ID'], $count + 1, false);
            }
        }
    }

    protected function action_get_infoblock_options() {
        $nc_core = nc_Core::get_object();
        $subdivision_id = $nc_core->input->fetch_get('subdivision_id');
        $infoblocks = $nc_core->db->get_results(
            "SELECT `Sub_Class_ID`, `Sub_Class_Name`
             FROM `Sub_Class`
             WHERE `Class_ID` != 0 AND `Subdivision_ID` = " . (int)$subdivision_id ."
             ORDER BY `Priority`",
            ARRAY_A
        );
        $this->is_naked = true;
        return $this->make_infoblocks_options($infoblocks);
    }

    protected function make_infoblocks_options($infoblocks, $selected_infoblock_id = 0) {
        $result = array();
        if (!empty($infoblocks)) {
            foreach ($infoblocks as $infoblock) {
                $result[] = '<option value="' . $infoblock['Sub_Class_ID'] .'"' . ($selected_infoblock_id == $infoblock['Sub_Class_ID'] ? ' selected' : NULL) . '>' . $infoblock['Sub_Class_Name'] . '</option>';
            }
        }
        return implode('', $result);
    }

    protected function action_get_filter_form() {
        $nc_core = nc_Core::get_object();
        $infoblock_id = $nc_core->input->fetch_get('infoblock_id');
        $component = $nc_core->get_component_by_infoblock_id($infoblock_id);
        $fields = $component->get_fields(nc_infoblock_filter::get_field_types());
        $this->is_naked = true;
        return $this->view('filter/edit_filter_form', array(
            'fields' => $fields,
            'filter_types' => nc_infoblock_filter::get_filter_types(),
            'filter_type_names' => $this->filter_type_names,
        ));
    }

    protected function action_save_filter() {
        $nc_core = nc_Core::get_object();
        $this->is_naked = true;

        $data_infoblock_id = $nc_core->input->fetch_post_get('data_infoblock_id');
        $filter_infoblock_id = $nc_core->input->fetch_post_get('filter_infoblock_id');
        $filter_settings = $nc_core->input->fetch_post_get('filter_settings');
        $filter_id = $nc_core->input->fetch_post_get('filter_id');

        $properties = array(
            'Filter_Sub_Class_ID' => $filter_infoblock_id,
            'Data_Sub_Class_ID' => $data_infoblock_id,
            'Settings' => $filter_settings
        );
        if (!$filter_id) {
            $filter_id = nc_infoblock_filter::create($properties);
        } else {
            $properties['Filter_ID'] = $filter_id;
            nc_infoblock_filter::update($properties);
        }
        exit;
    }

}
