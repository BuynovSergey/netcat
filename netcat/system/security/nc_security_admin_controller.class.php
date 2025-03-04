<?php

abstract class nc_security_admin_controller extends nc_ui_controller {

    /** @var nc_security_admin_ui */
    protected $ui_config;
    /** @var bool  */
    protected $set_current_site_id_as_default = false;

    /**
     * @param $view_path
     */
    public function __construct($view_path = null) {
        parent::__construct($view_path);
        $this->set_view_path(nc_core::get_object()->ADMIN_FOLDER . 'views/security');
    }

    /**
     * @return string|void
     */
    protected function before_action() {
        /** @var Permission $perm */
        global $perm;
        if (!$perm->isDirector() && !$perm->isSupervisor() && !$perm->isGuest()) {
            die(NETCAT_MODERATION_ERROR_NORIGHT);
        }
        $this->ui_config = new nc_security_admin_ui();
        return parent::before_action();
    }

    /**
     * @param nc_ui_view $view
     * @return nc_ui_view
     */
    protected function init_view(nc_ui_view $view) {
        return $view->with('site_id', $this->site_id);
    }

    /**
     * @param $result
     * @return string
     */
    protected function after_action($result) {
        if ($this->use_layout) {
            return BeginHtml() . $result . EndHtml();
        }
        return $result;
    }

}
