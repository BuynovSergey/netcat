<?php

class nc_routing_route_admin_controller extends nc_routing_admin_controller {

    /** @var  nc_routing_route_admin_ui */
    protected $ui_config;

    /**
     *
     */
    protected function init() {
        parent::init();

        $this->bind('add', array('site_id'));
        $this->bind('edit', array('route_id'));
    }

    /**
     *
     */
    protected function before_action() {
        $this->ui_config = new nc_routing_route_admin_ui(
            $this->get_short_controller_name(),
            NETCAT_MODULE_ROUTING_ROUTES);
    }

    /**
     *
     */
    protected function action_index() {
        $this->ui_config->locationHash .= ".list($this->site_id)";
        $this->ui_config->add_create_button("route.add($this->site_id)");

        $routes = nc_routing::get_routes($this->site_id, true);

        if (!count($routes)) {
            nc_routing_route_defaults::create($this->site_id);
            $routes = nc_routing::get_routes($this->site_id, true, true);
        }

        $view = $this->view('route_list')
                     ->with('routes', $routes);

        return $view;
    }

    /**
     * @param nc_routing_route $route
     * @return nc_ui_view|null
     */
    protected function save_route_and_redirect(nc_routing_route $route) {
        nc_core::get_object()->token->exit_if_invalid();
        try {
            $route->save();
            $this->redirect_to_index_action();
        }
        catch (Exception $e) { // nc_record_exception, nc_routing_pattern_parser_exception
            $this->ui_config->locationHash = "";
            $this->ui_config->add_save_and_cancel_buttons();

            $error_message = NETCAT_MODULE_ROUTING_UNABLE_TO_SAVE_RECORD .
                            "<br>" . $e->getMessage();

            return $this->view('route_edit')
                        ->with('route', $route)
                        ->with('error_message', $error_message);
        }
    }

    /**
     *
     */
    protected function action_add($site_id) {
        $this->ui_config->add_save_and_cancel_buttons();
        $this->ui_config->locationHash .= ".add($site_id)";

        $route = new nc_routing_route(array('site_id' => $site_id, 'enabled' => true));
        return $this->view('route_edit')->with('route', $route);
    }

    /**
     *
     */
    protected function action_edit($route_id) {
        $this->ui_config->add_save_and_cancel_buttons();
        $this->ui_config->locationHash .= ".edit($route_id)";

        $route = new nc_routing_route($route_id);
        return $this->view('route_edit')->with('route', $route);
    }

    /**
     * (POST only)
     */
    protected function action_save() {
        nc_core::get_object()->token->exit_if_invalid();

        $input = $this->input;
        $data = $input->fetch_post('data');
        $data['pattern'] = trim($data['pattern']);

        $route = new nc_routing_route($data['id']);
        $route->set_values($data);

        // @todo можно будет убрать trim(), если будет элемент выбора разделов/инфоблоков/объектов
        $resource_parameters = $input->fetch_post($data['resource_type']);
        $resource_parameters = array_map('trim', $resource_parameters);
        $route->set('resource_parameters', $resource_parameters);

        parse_str($input->fetch_post('query_variables'), $query_variables);
        $route->set('query_variables', $query_variables);

        return $this->save_route_and_redirect($route);
    }

    /**
     * (POST only)
     * params: route_id
     */
    protected function action_toggle() {
        nc_core::get_object()->token->exit_if_invalid();

        $route = new nc_routing_route($this->input->fetch_post('route_id'));
        $route->set('enabled', !$route->get('enabled'));
        return $this->save_route_and_redirect($route);
    }

    /**
     * (POST only)
     * params: route_id
     */
    protected function action_remove() {
        nc_core::get_object()->token->exit_if_invalid();

        $route = new nc_routing_route($this->input->fetch_post('route_id'));
        $route->delete();
        $this->redirect_to_index_action();
    }

    /**
     * (POST only)
     * params: route_id, priority
     */
    protected function action_change_priority() {
        nc_core::get_object()->token->exit_if_invalid();

        $input = $this->input;
        $route = new nc_routing_route($input->fetch_post('route_id'));
        $old_priority = (int)$route->get('priority');
        $new_priority = (int)$input->fetch_post('priority');

        if ($new_priority && $old_priority != $new_priority) {
            if ($new_priority < $old_priority) { // moving upward
                $direction = '+';
                $min = $new_priority;
                $max = $old_priority;
            }
            else { // moving downward
                $direction = '-';
                $min = $old_priority;
                $max = $new_priority;
            }

            nc_db()->query(
                "UPDATE `{$route->get_table_name()}`
                    SET `Priority` = `Priority` $direction 1
                  WHERE `Site_ID` = " . (int)$route->get('site_id') . "
                    AND `Priority` BETWEEN $min AND $max");

            $route->set('priority', $new_priority)->save();
        }

        exit;
    }
}