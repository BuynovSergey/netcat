<?php

class nc_redirect extends nc_record {
    /**
     * @var string
     */
    protected $primary_key = "id";

    /**
     * @var array
     */
    protected $properties = array(
        "id" => null,
        "old_url" => '',
        "new_url" => '',
        "header" => 301,
        "group" => 1,
        "checked" => 1,
    );

    /**
     * @var string
     */
    protected $table_name = "Redirect";

    /**
     * @var array
     */
    protected $mapping = array(
        "id" => 'Redirect_ID',
        "old_url" => 'OldURL',
        "new_url" => 'NewURL',
        "header" => 'Header',
        "group" => 'Group_ID',
        "checked" => 'Checked',
    );

    /**
     * @var string
     */
    protected $last_error = '';

    /**
     * Validates input data
     *
     * @return bool
     */
    public function validate() {
        $this->last_error = null;

        if (!$this->get('old_url') || !$this->get('new_url')) {
            $this->last_error = TOOLS_REDIRECT_CANTBEEMPTY;
            return false;
        }
        $redirect = new nc_redirect();
        $redirect->load_where('old_url' , $this->get('old_url'));
        if ($redirect['id'] && $redirect['id'] != $this->get_id()) {
            $this->last_error = sprintf(TOOLS_REDIRECT_OLDURL_MUST_BE_UNIQUE, $redirect['id']);
            return false;
        }
        return true;
    }

    /**
     * Sets data from user input
     *
     * @param $data
     */
    public function set_values_from_form($data) {

        if ($data['header'] != 301 && $data['header'] != 302) {
            $data['header'] = 301;
        }

        $this->set_values(array(
            'id' => $data['id'],
            'old_url' => $this->clear_url($data['old_url']),
            'new_url' => $this->clear_url($data['new_url']),
            'header' => $data['header'],
            'group' => (int)$data['group'],
            'checked' => (int)$data['checked'],
        ));
    }

    /**
     * Returns last error
     *
     * @return string
     */
    public function get_last_error() {
        return $this->last_error;
    }

    private function clear_url($url) {
        if (nc_strpos($url, '://')) {
            $parts = explode('://', $url);
            $url = $parts[1];
        }
        if (nc_strpos($url, '/') === 0) {
            $url = nc_core()->HTTP_HOST.$url;
        }
        return $url;
    }
}