<?php

/**
 * Class nc_site_controller
 */
class nc_site_controller extends nc_ui_controller {

    protected $is_naked = false;

    /**
     * @param $result
     *
     * @return string
     */
    protected function after_action($result) {
        if (!$this->is_naked) {
            return BeginHtml() . $result . EndHtml();
        }

        return $result;
    }

    /**
     * @param $action
     *
     * @return string
     */
    protected function get_site_request_url($action) {
        $nc_core = nc_core::get_object();
        $modules = (array)$nc_core->db->get_col("SELECT `Keyword` FROM `Module`");

        $store_request_parameters = http_build_query(
            array(
                'copy_id' => $nc_core->get_copy_id(),
                'version' => $nc_core->get_full_version_number(),
                'edition' => $nc_core->get_edition_name(),
                'modules' => join(',', $modules),
                'host' => $_SERVER['HTTP_HOST'],
                'code' => $nc_core->get_settings('Code'),
            ),
            null,
            '&'
        );

        $store_request_url = "https://store.netcat.ru/api/v1/site/$action/?" . $store_request_parameters;

        return $store_request_url;
    }

    /**
     *
     */
    protected function action_show_add_form() {
        $this->check_permissions(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADD, 0, false);
        $nc_core = nc_core::get_object();

        if (!$nc_core->catalogue->can_create()) {
            $this->ui_config = new ui_config(
                array(
                    'treeMode' => 'sitemap',
                    'headerText' => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE,
                    'actionButtons' => array(),
                )
            );

            return $this->view('error_message')->with('message', CONTROL_CONTENT_CATALOUGE_ERROR_CANNOT_CREATE_MORE);
        }

        $this->ui_config = new ui_config(
            array(
                'treeMode' => 'sitemap',
                'headerText' => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE,
                'actionButtons' => array(
                    array(
                        "id" => "submit",
                        "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE,
                        "action" => "mainView.submitIframeForm()",
                        "align" => "right",
                    ),
                ),
            )
        );

        // Данные о сайтах с netcat.ru
        $data = null;
        $site_request_url = $this->get_site_request_url('list');
        $sites_json = file_get_contents($site_request_url);
        if ($sites_json) {
            $data = json_decode($sites_json, true);
        } else {
            $data = array('error_text' => CONTROL_CONTENT_SITE_ADD_DATA_ERROR);
        }

        return $this->view(
            'site/add',
            array(
                'domain' => $nc_core->catalogue->get_current() ? '' : $_SERVER['HTTP_HOST'],
                'data' => $data,
            )
        );
    }

    /**
     * @param $site_id
     */
    protected function redirect_to_site_properties($site_id) {
        $nc_core = nc_core::get_object();
        $redirect_url = "{$nc_core->ADMIN_PATH}catalogue/index.php?phase=2&CatalogueID=$site_id&action=edit";
        echo "<script>window.location = '$redirect_url';</script>";
    }

    /**
     *
     */
    protected function action_create() {
        $this->check_permissions(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADD, 0, true);
        $nc_core = nc_core::get_object();

        /** @todo check domain name? */

        $site_type = $nc_core->input->fetch_post('site_type');

        if ($site_type == 'blank') {
            $properties = (array)$nc_core->input->fetch_post('data');
            $created_site_id = $nc_core->catalogue->create($properties);
            $site_properties = $nc_core->catalogue->get_by_id($created_site_id);

            $this->print_new_site_ui_config($site_properties);
            $this->redirect_to_site_properties($created_site_id);
        } else if ($site_type == 'store_site') {
            $hidden_inputs = array();
            foreach ($nc_core->input->fetch_post() as $k => $v) {
                if (is_array($v)) {
                    continue;
                }
                $hidden_inputs[$k] = $v;
            }
            foreach ((array)$nc_core->input->fetch_post('data') as $k => $v) {
                $hidden_inputs["data[$k]"] = $v;
            }

            $hidden_inputs['action'] = 'download_and_import';

            return $this->view(
                'site/add_wait',
                array(
                    'hidden_inputs' => $hidden_inputs,
                )
            );
        } else {
            // wrong site_type?!
            return $this->action_show_add_form();
        }
    }

    /**
     *
     */
    protected function action_download_and_import() {
        $this->check_permissions(NC_PERM_ITEM_SITE, NC_PERM_ACTION_ADD, 0, true);
        $nc_core = nc_core::get_object();

        @set_time_limit(0);
        @ignore_user_abort(true);

        $site_request_url = $this->get_site_request_url('get') .
            '&site_id=' . ((int)$nc_core->input->fetch_post('site_id'));

        $source_file_handle = fopen($site_request_url, 'r');

        if (!$source_file_handle) {
            return $this->view('error_message.view.php')
                        ->with('message', CONTROL_CONTENT_SITE_ADD_DOWNLOADING_ERROR);
        }

        $tmp_file_name = $nc_core->TMP_FOLDER . 'site-' . uniqid() . '.tgz';
        $tmp_file_handle = fopen($tmp_file_name, 'w');

        while (!feof($source_file_handle)) {
            fwrite($tmp_file_handle, fread($source_file_handle, 65535));
        }

        fclose($source_file_handle);
        fclose($tmp_file_handle);

        if (!file_exists($tmp_file_name) || !filesize($tmp_file_name)) {
            return $this->view('error_message.view.php')
                        ->with('message', CONTROL_CONTENT_SITE_ADD_DOWNLOADING_ERROR);
        }

        /** @var nc_backup_result $result */
        $result = $nc_core->backup->import(
            $tmp_file_name,
            array(
                'save_ids' => false,
            )
        );

        $created_site_id = $result->get_new_id();
        unlink($tmp_file_name);

        // Установка названия сайта, домена, если они были заданы
        $data = (array)$nc_core->input->fetch_post('data');
        $data = array_filter($data, 'strlen');
        if ($data) {
            nc_db_table::make('Catalogue')->where_id($created_site_id)->update($data);
        }

        $site_properties = $nc_core->catalogue->get_by_id($created_site_id);
        $this->print_new_site_ui_config($site_properties);
        $this->redirect_to_site_properties($created_site_id);

        return '';
    }

    /**
     * @param array $site_properties
     */
    protected function print_new_site_ui_config(array $site_properties) {
        $site_name = $site_properties['Catalogue_Name'];
        $site_href = "#site.map($site_properties[Catalogue_ID])";

        $site_scheme = nc_get_scheme();
        $site_domain = $_SERVER['HTTP_HOST'];

        if ($site_properties['Domain']) {
            $site_scheme = nc_Core::get_object()->catalogue->get_scheme_by_id($site_properties['Catalogue_ID']);
            $site_domain = $site_properties['Domain'];
        }

        $UI_CONFIG = new ui_config(
            array(
                'treeChanges' => array(
                    'addNode' => array(
                        array(
                            'nodeId' => "site-$site_properties[Catalogue_ID]",
                            'sprite' => 'nc-icon nc--site',
                            'name' => "$site_properties[Catalogue_ID]. $site_name",
                            'href' => $site_href,
                            'hasChildren' => true,
                            'acceptDropFn' => 'treeSitemapAcceptDrop',
                            'onDropFn' => 'treeSitemapOnDrop',
                            'dragEnabled' => true,
                            'buttons' => array(
                                array(
                                    "label" => CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_A_VIEW,
                                    "action" => "window.open('{$site_scheme}://{$site_domain}');",
                                    "icon" => "arrow-right",
                                    "sprite" => true,
                                ),
                                array(
                                    "label" => CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_A_ADDSUBSECTION,
                                    "action" => "parent.location.hash = 'subdivision.add(0,$site_properties[Catalogue_ID])'",
                                    "icon" => "folder-add",
                                    "sprite" => true,
                                ),
                            ),
                        ),
                    ),
                ),
                'addNavBarCatalogue' => array(
                    'name' => $site_name,
                    'href' => $site_href,
                ),
            )
        );

        echo $UI_CONFIG->to_json();
    }
}
