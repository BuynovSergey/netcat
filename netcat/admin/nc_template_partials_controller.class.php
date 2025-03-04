<?php

class nc_template_partials_controller extends nc_ui_controller {

    protected $is_naked = false;
    protected $error = false;
    protected $template_id = 0;
    protected $partial = false;


    /**
     *
     */
    protected function init() {
        $this->bind('list', array('TemplateID'));
        $this->bind('add', array('TemplateID'));
        $this->bind('edit', array('TemplateID', 'partial'));
        $this->bind('remove', array('TemplateID', 'partial'));
    }

    /**
     * @param nc_ui_view $view
     */
    protected function init_view(nc_ui_view $view) {
        $view->with('error', $this->error);
        $view->with('template_id', $this->template_id);
        $view->with('action_url', $this->nc_core->SUB_FOLDER . $this->nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.template_partials&fs=1&TemplateID=' . $this->template_id . '&action=');
    }

    /**
     * @param $result
     * @return string
     */
    protected function after_action($result) {
        // JSON
        if (is_array($result)) {
            return json_safe_encode($result);
        }
        // With template
        if (!$this->is_naked) {
            return BeginHtml() . $result . EndHtml();
        }

        return $result;
    }

    /**
     * @param $template_id
     * @return nc_ui_view
     */
    public function action_list($template_id) {
        $this->check_permissions(NC_PERM_TEMPLATE, NC_PERM_ACTION_LIST, 0, false);

        $this->template_id = (int)$template_id;
        $data = array();

        $this->ui_config('list');
        $this->ui_config->actionButtons[] = array(
            'caption' => CONTROL_TEMPLATE_PARTIALS_ADD,
            'align' => 'left',
            'location' => "template_fs.partials_add({$this->template_id})",
        );

        if ($node = nc_core()->input->fetch_get('deleteNode')) {
            $this->ui_config->treeChanges['deleteNode'][] = $node;
        }

        $data['partials'] = $this->nc_core->template->get_partials_data($this->template_id);

        return $this->view('template_partials/list', $data);
    }

    /**
     * @param $template_id
     * @return nc_ui_view
     */
    public function action_add($template_id) {
        $this->check_permissions(NC_PERM_TEMPLATE, NC_PERM_ACTION_ADD, 0, true);

        global $NETCAT_PATH, $DIRCHMOD;

        $this->template_id = (int)$template_id;
        $data = array(
            'action' => 'add',
        );

        $this->ui_config('add', CONTROL_TEMPLATE_PARTIALS_NEW);
        $this->ui_config->actionButtons[] = array(
            'caption' => NETCAT_CUSTOM_ONCE_SAVE,
            'action' => "nc.view.main('form').submit(); return false",
        );
        if (isset($_POST['partial_keyword'])) {
            $this->nc_core->event->execute(nc_Event::BEFORE_TEMPLATE_PARTIAL_CREATED, $template_id, null);

            $data['partial_keyword'] = $this->nc_core->input->fetch_post('partial_keyword');
            $data['partial_source'] = $this->nc_core->input->fetch_post('partial_source');

            if (!$data['partial_keyword']) {
                $this->error = CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD_REQUIRED_ERROR;
            } elseif ($this->nc_core->template->is_valid_partial_keyword($data['partial_keyword'])) {

                $partial_file = $this->nc_core->template->get_partials_path($this->template_id, $data['partial_keyword']);

                if (!file_exists($partial_file)) {
                    $partials_dir = $this->nc_core->template->get_partials_path($this->template_id);
                    if (!is_dir($partials_dir)) {
                        mkdir($partials_dir, $DIRCHMOD);
                    }
                    file_put_contents($partial_file, $data['partial_source']);
                    $this->save_meta($template_id, $data['partial_keyword']);
                    $this->nc_core->event->execute(nc_Event::AFTER_TEMPLATE_PARTIAL_CREATED, $template_id, $data['partial_keyword']);
                    header("Location: {$NETCAT_PATH}action.php?ctrl=admin.template_partials&action=edit&fs=1&TemplateID={$template_id}&partial={$data['partial_keyword']}&addNode=1");
                } else {
                    $this->error = CONTROL_TEMPLATE_PARTIALS_EXISTS_ERROR;
                }
            } else {
                $this->error = CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD_ERROR;
            }
        }

        return $this->view('template_partials/edit', $data);
    }

    /**
     * @param $template_id
     * @param $partial_keyword
     * @return nc_ui_view|null
     */
    public function action_edit($template_id, $partial_keyword) {
        $this->check_permissions(NC_PERM_TEMPLATE, NC_PERM_ACTION_EDIT, $template_id, true);

        $this->partial = $partial_keyword;
        $this->template_id = (int)$template_id;

        if (isset($_POST['partial_keyword'])) {
            $this->nc_core->event->execute(nc_Event::BEFORE_TEMPLATE_PARTIAL_UPDATED, $template_id, $partial_keyword);
        }

        $data = array(
            'action' => 'edit',
        );
        
        $partial_file = $this->nc_core->template->get_partials_path($this->template_id, $partial_keyword);
        
        if (file_exists($partial_file)) {
            if (isset($_POST['partial_source'])) {
                $partial_source = $this->input->fetch_post('partial_source');
                file_put_contents($partial_file, $partial_source);
                $this->save_meta($template_id, $partial_keyword);
            }
            
            $partial_keyword_old = $partial_keyword;
            
            if (isset($_POST['partial_keyword']) && $this->input->fetch_post('partial_keyword') != $partial_keyword_old) {
                $data['partial_keyword'] = $this->input->fetch_post('partial_keyword');
                
                if (!$data['partial_keyword']) {
                    $this->error = CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD_REQUIRED_ERROR;
                } elseif (preg_match('/^[a-z0-9_-]+$/ui', $data['partial_keyword'])) {
                    $partial_file_old = $this->nc_core->template->get_partials_path($this->template_id, $partial_keyword);
                    $partial_file = $this->nc_core->template->get_partials_path($this->template_id, $data['partial_keyword']);
                    
                    if (!file_exists($partial_file)) {
                        rename($partial_file_old, $partial_file);
                        
                        $partial_keyword = $data['partial_keyword'];
                        
                        $data = array(
                            'Template_ID' => $template_id,
                            'Keyword' => $partial_keyword,
                        );
                
                        $this->get_query_object($template_id, $partial_keyword_old)->update($data);
                    } else {
                        $partial_file = $partial_file_old;
                        $this->error = CONTROL_TEMPLATE_PARTIALS_EXISTS_ERROR;
                    }
                } else {
                    $this->error = CONTROL_TEMPLATE_PARTIALS_KEYWORD_FIELD_ERROR;
                }
            }
            
            $this->ui_config('edit', CONTROL_TEMPLATE_PARTIALS . ' <small>' . $partial_keyword . '</small>');
            $this->ui_config->locationHash = "template.partials_edit({$this->template_id},{$partial_keyword})";

            $partial_source = file_get_contents($partial_file);

            $data['partial_keyword'] = $partial_keyword;
            $data['partial_source'] = $partial_source;

            $meta = $this->get_query_object($template_id, $partial_keyword)->get_row();
            $data['partial_description'] = nc_array_value($meta, 'Description', '');
            $data['partial_enable_async_load'] = nc_array_value($meta, 'EnableAsyncLoad', 0);

            $tree_node_name = $data['partial_description'] ? "$data[partial_description] ($partial_keyword)" : $partial_keyword;
            
            if ($partial_keyword_old != $partial_keyword) {
                $this->ui_config->treeChanges['deleteNode'][] = "template_partial-{$template_id}-{$partial_keyword_old}";
            }
            
            if ($this->input->fetch_get('addNode') || $partial_keyword_old != $partial_keyword) {
                $this->ui_config->treeChanges['addNode'][] = array(
                    "parentNodeId" => "template_partials-{$template_id}",
                    "nodeId" => "template_partial-{$template_id}-{$partial_keyword}",
                    "name" => $tree_node_name,
                    "href" => "#template_fs.partials_edit({$template_id},{$partial_keyword})",
                    "sprite" => 'dev-com-templates',
                    "buttons" => array(
                        nc_get_array_2json_button(
                            CONTROL_TEMPLATE_PARTIALS_REMOVE,
                            "template_fs.partials_remove({$template_id},{$partial_keyword})",
                            "nc-icon nc--remove nc--hovered"
                        )
                    )
                );
            } elseif ($partial_keyword_old == $partial_keyword) {
                $this->ui_config->treeChanges['updateNode'][] = array(
                    "nodeId" => "template_partial-{$template_id}-{$partial_keyword}",
                    "name" => $tree_node_name,
                );
            }

        } else {
            nc_print_status('Template not found', 'error');
            return null;
        }

        $this->ui_config->actionButtons[] = array(
            'caption' => NETCAT_CUSTOM_ONCE_SAVE,
            'action' => "nc.view.main('form').submit(); return false",
        );

        if (isset($_POST['partial_keyword'])) {
            $this->nc_core->event->execute(nc_Event::AFTER_TEMPLATE_PARTIAL_UPDATED, $template_id, $partial_keyword);
        }

        return $this->view('template_partials/edit', $data);
    }

    /**
     * @param $template_id
     * @param $partial_keyword
     */
    public function action_remove($template_id, $partial_keyword) {
        $this->check_permissions(NC_PERM_TEMPLATE, NC_PERM_ACTION_DEL, $template_id, true);

        global $NETCAT_PATH;
        
        $this->partial = $partial_keyword;
        $this->template_id = (int)$template_id;
        
        if (!isset($_POST['confirmed'])) {
            $meta = $this->get_query_object($template_id, $partial_keyword)->get_row();
            
            $data = array(
                'action' => 'remove',
                'partial_keyword' => $partial_keyword,
                'partial_description' => nc_array_value($meta, 'Description', ''),
            );
            
            $this->ui_config('remove', CONTROL_TEMPLATE_PARTIALS . ' <small>' . $partial_keyword . '</small>');
            $this->ui_config->locationHash = "template.partials_remove({$this->template_id},{$partial_keyword})";
            
            $this->ui_config->actionButtons[] = array(
                'caption' => NETCAT_CUSTOM_ONCE_DROP,
                'action' => "nc.view.main('form').submit(); return false",
            );
            
            return $this->view('template_partials/remove', $data);
        } else {
            $this->nc_core->event->execute(nc_Event::BEFORE_TEMPLATE_PARTIAL_DELETED, $this->template_id, $partial_keyword);

            $this->ui_config('list');
            $this->ui_config->locationHash = "template.partials_list({$this->template_id})";

            $partial_file = $this->nc_core->template->get_partials_path($this->template_id, $partial_keyword);

            if (file_exists($partial_file)) {
                unlink($partial_file);
            }

            $this->get_query_object($template_id, $partial_keyword)->delete();

            $this->nc_core->event->execute(nc_Event::AFTER_TEMPLATE_PARTIAL_DELETED, $template_id, $partial_keyword);

            $this->is_naked = true;
            header("Location: {$NETCAT_PATH}action.php?ctrl=admin.template_partials&action=list&fs=1&TemplateID={$template_id}&deleteNode=template_partial-{$this->template_id}-{$this->partial}");
        }

    }

    /**
     * @param $mode
     * @param string $title
     */
    protected function ui_config($mode, $title = CONTROL_TEMPLATE_PARTIALS) {
        $this->ui_config = new ui_config(array(
            'headerText' => $title,
            'treeMode' => 'template_fs',
        ));

        $this->ui_config->locationHash = "template.partials_{$mode}({$this->template_id})";
        if ($this->partial) {
            $this->ui_config->treeSelectedNode = "template_partial-{$this->template_id}-{$this->partial}";
        } else {
            $this->ui_config->treeSelectedNode = "template_partials-{$this->template_id}";
        }
    }

    /**
     * Сохранение данных в таблице Template_Partial
     *
     * @param $template_id
     * @param $partial_keyword
     */
    protected function save_meta($template_id, $partial_keyword) {
        $data = array(
            'Template_ID' => $template_id,
            'Keyword' => $partial_keyword,
            'Description' => (string)$this->input->fetch_post('partial_description'),
            'EnableAsyncLoad' => $this->input->fetch_post('partial_enable_async_load') ? 1 : 0,
        );

        $updated = $this->get_query_object($template_id, $partial_keyword)->update($data);
        if (!$updated) {
            nc_db_table::make('Template_Partial')->insert($data);
        }
    }

    /**
     * @param $template_id
     * @param $partial_keyword
     * @return nc_db_table
     */
    protected function get_query_object($template_id, $partial_keyword) {
        return nc_db_table::make('Template_Partial')
            ->where('Template_ID', $template_id)
            ->where('Keyword', $partial_keyword);
    }

}
