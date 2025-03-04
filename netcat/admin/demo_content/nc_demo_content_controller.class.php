<?php

class nc_demo_content_controller extends nc_ui_controller {

    protected $is_naked = false;

    /**
     *
     */
    protected function init() {
        $this->is_naked = true;
        $GLOBALS['isNaked'] = 1;
    }

    /**
     * @param $result
     * @return string
     */
    protected function after_action($result) {
        if ($this->is_naked) {
            return $result;
        }

        return BeginHtml() . $result . EndHtml();
    }

    protected function action_show_list_demo_content() {
        require_once $this->nc_core->ADMIN_FOLDER . 'catalogue/function.inc.php';
        $this->is_naked = false;
        $GLOBALS['isNaked'] = 0;
        $catalogue_id = $this->site_id;
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $this->ui_config = new ui_config_catalogue('demo_content', $catalogue_id);
        
        $result_array = array();
        $catalogue_array = $this->nc_core->catalogue->get_by_id($catalogue_id);
        $domain = ($catalogue_array['ncHTTPS'] === '1' ? 'https' : 'http') . '://' . $catalogue_array['Domain'];

        $sql = $db->get_results("
            SELECT Class.`Class_ID`,
                Sub_Class.`Sub_Class_ID`,
                Sub_Class.`Sub_Class_Name`,
                Subdivision.`Subdivision_Name`,
                Subdivision.`Subdivision_ID`
                FROM `Class`
                LEFT JOIN `Sub_Class` ON Sub_Class.`Class_ID` = Class.`Class_ID`
                LEFT JOIN `Subdivision` ON Subdivision.`Subdivision_ID` = Sub_Class.`Subdivision_ID` 
                    WHERE Class.`System_Table_ID` = '0' AND Class.`ClassTemplate` = '0' AND Subdivision.`Catalogue_ID` = '" . $catalogue_id . "'
                    ORDER BY Subdivision.`Subdivision_ID` ASC", ARRAY_A);

        foreach ($sql as $sql_row) {                  
            $item_count = $db->get_var("SELECT COUNT(`Message_ID`) FROM `Message" . $sql_row['Class_ID'] . "` WHERE `ncDemoContent` = '1' AND `Sub_Class_ID`='" . $sql_row['Sub_Class_ID'] . "'");
            if ($item_count) {
                $result_array[] = array_merge($sql_row, array('ItemCount' => $item_count));
            }
        }        

        return $this->view('show_list_demo_content', array(
            'data' => $result_array,
            'domain' => $domain
        ));
    }

}
