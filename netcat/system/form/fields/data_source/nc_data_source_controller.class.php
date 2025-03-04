<?php class_exists('nc_system') OR die('Unable to load file');

/**
 * Контроллер работает в связке с nc.data_source.(.min)js
 * См. также: nc_form_field_data_source::render_field();
 */

class nc_data_source_controller extends nc_ui_controller {

    //-------------------------------------------------------------------------

    protected $use_current_catalogue_only = true;

    //-------------------------------------------------------------------------

    protected function init()
    {
        $this->bind('init',    array('field_value', 'field_name'));
        $this->bind('source',  array('catalogue_id', 'subdivision_id'));
        $this->bind('filter',  array('field_value'));
        $this->bind('bindings', array('field_value'));
    }

    //-------------------------------------------------------------------------

    protected function after_action($result) {
        if (is_array($result)) {
            return json_safe_encode($result);
        }
        return $result;
    }

    /**************************************************************************
        ACTIONS
    **************************************************************************/

    public function action_init($field_value, $field_name) {
        $json = array();

        if ($field_value) {
            // $json = $this->action_source($field_value['catalogue_id'], 0);
            $json['enable_tab'] = array('source', 'filter', 'ordering', 'bindings');
            // $json['select_tab'] = 'bindings';
        } else {
            // $json['select_tab'] = 'source';
            $json['action'] = array(
                'source' => array(
                    'catalogue_id' => nc_core()->catalogue->id()
                )
            );
        }

        return $json;
    }

    /**************************************************************************
        SOURCE ACTIONS:
    **************************************************************************/

    public function action_source($catalogue_id, $subdivision_id) {
        $json = array();

        $json['select_tab'] = 'source';

        if ($catalogue_id) {
            $json['source_list'] = $this->get_subdivisions($catalogue_id, $subdivision_id);
        } else {
            $json['source_list'] = $this->get_catalogues();
        }

        if ($subdivision_id) {
            $json['source_subclass'] = $this->get_subclasses($subdivision_id);
        }

        $json['source_path'] = $this->get_path($catalogue_id, $subdivision_id);

        return $json;
    }

    //-------------------------------------------------------------------------

    protected function get_catalogues() {
        $catalogues = nc_core()->catalogue->get_all();

        $result = array();

        foreach ($catalogues as $row) {
            $result[] = array(
                'title'  => $row['Catalogue_Name'],
                'icon'   => 'site',
                'action' => array(
                    'source' => array(
                        'catalogue_id' => (int) $row['Catalogue_ID']
                    )
                ),
            );
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    protected function get_subdivisions($catalogue_id, $subdivision_id = 0) {

        $result       = array();
        $subdivisions = nc_db_table::make('Subdivision')
            ->where('Catalogue_ID', (int)$catalogue_id)
            ->where('Parent_Sub_ID', (int)$subdivision_id)
            ->order_by('Priority')
            ->order_by('Subdivision_Name')
            ->get_result();

        foreach ($subdivisions as $row) {
            $result[] = array(
                'title'  => $row['Subdivision_Name'],
                'icon'   => 'folder',
                'action' => array(
                    'source' => array(
                        'catalogue_id'   => (int) $catalogue_id,
                        'subdivision_id' => (int) $row['Subdivision_ID'],
                    )
                ),
            );
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function get_subclasses($subdivision_id) {
        $result     = array();
        $subclasses = (array) nc_core()->sub_class->get_by_subdivision_id($subdivision_id);

        foreach ($subclasses as $row) {
            $result[] = array(
                'title'      => $row['Sub_Class_Name'],
                'icon'       => 'dev-components',
                'enable_tab' => array('ordering', 'bindings'),
                'select_tab' => 'filter',
                'call'       => array(
                    'set_value' => array(
                        array(
                            'catalogue_id'   => (int) $row['Catalogue_ID'],
                            'subdivision_id' => (int) $row['Subdivision_ID'],
                            'subclass_id'    => (int) $row['Sub_Class_ID'],
                            'class_id'       => (int) $row['Class_ID'],
                        )
                    ),
                ),
            );
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    protected function get_path($catalogue_id = 0, $subdivision_id = 0) {
        $path = array();

        $path[] = array(
            'title'  => SECTION_INDEX_SITE_LIST,
            'icon'   => 'site-list',
            'action' => array(
                'source' => array(),
            ),
        );

        if ($catalogue_id) {
            $cat = (array) nc_core()->catalogue->get_by_id($catalogue_id);
            $path[] = array(
                'title'  => $cat['Catalogue_Name'],
                'icon'   => 'site',
                'action' => array(
                    'source' => array(
                        'catalogue_id' => (int) $catalogue_id,
                    ),
                ),
            );
        }

        if ($subdivision_id) {
            $parents = (array) nc_core()->subdivision->get_parent_tree($subdivision_id);
            $parents = array_reverse($parents);
            array_shift($parents);
            foreach ($parents as $row) {
                $path[] = array(
                    'title'  => $row['Subdivision_Name'],
                    'icon'   => 'folder',
                    'action' => array(
                        'source' => array(
                            'catalogue_id'   => (int) $catalogue_id,
                            'subdivision_id' => (int) $row['Subdivision_ID'],
                        ),
                    ),
                );
            }
        }

        return $path;
    }

    /**************************************************************************
        FILTER ACTIONS
    **************************************************************************/

    // public function action_filter($field_value) {
    //     $class_id = (int) $field_value['class_id'];

    //     return array(
    //         'tab_content' => array(
    //             'filter' => print_r($field_value , 1)
    //         )
    //     );

    // }

    /**************************************************************************
        BINDING ACTIONS
    **************************************************************************/

    public function action_bindings($field_value) {
        $class_id = (int) $field_value['class_id'];
        $fields   = nc_db_table::make('Field')->where('Class_ID', $class_id)->get_list('Field_Name', 'Description');

        return array(
            // 'tab_content' => array(
            //     'bindings' => print_r($fields , 1)
            // ),
            'bindings_fields' => $fields
        );
    }
}