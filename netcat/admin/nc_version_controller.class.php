<?php

class nc_version_controller extends nc_ui_controller {

    protected $use_layout = true;

    /**
     * @return void
     */
    protected function init() {
        $nc_core = nc_Core::get_object();
        if ($nc_core->input->fetch_get('isNaked')) {
            $this->use_layout = false;
        }
    }

    /**
     * @return nc_ui_view
     * @throws nc_record_exception
     */
    protected function action_show_page_versions() {
        $nc_core = nc_Core::get_object();
        $subdivision_id = (int)$nc_core->input->fetch_get('subdivision_id');

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $subdivision_id);

        $versions = nc_version_record_collection::load(
            'nc_version_record',
            "SELECT * FROM `Version` WHERE `Subdivision_ID` = " . $subdivision_id . " GROUP BY `Version_Changeset_ID` ORDER BY `Version_ID` DESC"
        );

        return $this->view('version/page', array(
            'versions' => $versions,
        ));
    }

    /**
     * @return void|null
     * @throws nc_Exception_DB_Error
     * @throws nc_record_exception
     */
    protected function action_restore_page() {
        $nc_core = nc_Core::get_object();
        $this->use_layout = false;
        $subdivision_id = (int)$nc_core->input->fetch_get_post('subdivision_id');
        $id = (int)$nc_core->input->fetch_post('id');

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $subdivision_id);

        if (!$id) {
            die('Wrong ID');
        }

        /** @var Permission $perm */
        global $perm;
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $subdivision_id);

        $version = $nc_core->version->load_newest_in_chain($id, $subdivision_id);

        $time = date(NETCAT_VERSION_TIME_FORMAT, $version->get('Timestamp'));
        $changeset_description = sprintf(NETCAT_VERSION_CHANGESET_RESTORE_PAGE, $time);
        $nc_core->version->init_changeset($changeset_description);

        // все не актуальные версии инфоблоков, сделанные до восстанавливаемой версии
        $infoblock_versions = nc_version_record_collection::load(
            "nc_version_record",
            "SELECT  `v`.* FROM `Version` AS `v`
             INNER JOIN (
                SELECT `Sub_Class_ID`, MAX(`Version_ID`) AS `Max_Version_ID`
                FROM `Version`
                WHERE `Version_ID` < " . $version->get_id() . " AND `Entity` = 'infoblock' AND `Subdivision_ID` = " . $subdivision_id . "
                GROUP BY `Sub_Class_ID`
            ) AS `v2` ON `v`.`Sub_Class_ID` = `v2`.`Sub_Class_ID` AND `v`.`Version_ID` = `v2`.`Max_Version_ID`
            WHERE `v`.`IsActual` != 1 
            " . ($version->get('Entity') == 'infoblock' ? "
                AND NOT (`v`.`Entity` = 'infoblock' AND `v`.`Sub_Class_ID` = " . (int)$version->get('Sub_Class_ID') . ")
            " : null) . "
            ORDER BY `v`.`Version_ID` ASC"
        );

        // все не актуальные версии объектов, сделанные до восстанавливаемой версии
        $object_versions = nc_version_record_collection::load(
            "nc_version_record",
            "SELECT  `v`.* FROM `Version` AS `v`
             INNER JOIN (
                SELECT `Class_ID`, `Message_ID`, MAX(`Version_ID`) AS `Max_Version_ID`
                FROM `Version`
                WHERE `Version_ID` < " . $version->get_id() . " AND `Entity` = 'object' AND `Subdivision_ID` = " . $subdivision_id . " 
                GROUP BY `Class_ID`, `Message_ID`
            ) AS `v2` ON `v`.`Class_ID` = `v2`.`Class_ID` AND `v`.`Message_ID` = `v2`.`Message_ID` AND `v`.`Version_ID` = `v2`.`Max_Version_ID`
            WHERE `v`.`IsActual` != 1 
            " . ($version->get('Entity') == 'object' ? "
                AND NOT (`v`.`Entity` = 'object' AND `v`.`Class_ID` = " . (int)$version->get('Class_ID') . " AND `v`.`Message_ID` = " . (int)$version->get('Message_ID') . ")
            " : null) . "
            ORDER BY `v`.`Version_ID` ASC"
        );

        // если восстанавливают не версию раздела, то надо посмотреть ближайшую предыдущую версию раздела и, если она не актуальная, то восстановить ее
        if ($version->get('Entity') != 'subdivision') {
            $subdivision_version_data = $nc_core->db->get_row("
                SELECT *
                FROM Version
                WHERE Version_ID < " . $version->get_id() . " AND Entity = 'subdivision' AND Subdivision_ID = " . $subdivision_id . "
                ORDER BY Version_ID DESC
                LIMIT 1
            ", ARRAY_A);
            if (!empty($subdivision_version_data) && $subdivision_version_data['IsActual'] != 1) {
                $subdivision_version = new nc_version_record_subdivision();
                $subdivision_version->set_values_from_database_result($subdivision_version_data);
                $subdivision_version->restore();
            }
        }

        // восстанавливаем все необходимые версии инфоблоков
        if ($infoblock_versions->count() > 0) {
            /** @var nc_version_record_infoblock $infoblock_version */
            foreach ($infoblock_versions as $infoblock_version) {
                $infoblock_version->restore();
            }
        }

        // восстанавливаем все необходимые версии объектов
        if ($object_versions->count() > 0) {
            /** @var nc_version_record_object $object_version */
            foreach ($object_versions as $object_version) {
                $object_version->restore();
            }
        }

        // надо удалить все инфоблоки и объекты, которые были созданые позднее восстанавливаемой версии
        $infoblocks_for_delete = $nc_core->db->get_col("
            SELECT `Sub_Class_ID`
            FROM `Version`
            WHERE `Version_ID` > " . $version->get_id() . " AND `Entity` = 'infoblock' AND `Subdivision_ID` = " . $subdivision_id . " AND `Action` = 'created'
        ", ARRAY_A);
        $objects_for_delete = $nc_core->db->get_results("
            SELECT `Class_ID`, `Message_ID`
            FROM `Version`
            WHERE `Version_ID` > " . $version->get_id() . " AND `Entity` = 'object' AND `Subdivision_ID` = " . $subdivision_id . " AND `Action` = 'created'
        ", ARRAY_A);


        if (!empty($objects_for_delete)) {
            foreach ($objects_for_delete as $object) {
                $nc_core->message->delete_by_id($object['Message_ID'], $object['Class_ID'], $nc_core->get_settings('TrashUse'));
            }
        }
        if (!empty($infoblocks_for_delete)) {
            foreach ($infoblocks_for_delete as $infoblock_id) {
                $nc_core->sub_class->delete($infoblock_id);
            }
        }

        if (!$version->get('IsActual')) {
            $version->restore();
        }

        $nc_core->version->commit_changeset();

        return null;
    }

    /**
     * @param $version
     * @return string
     */
    protected function get_user_data_if_not_current($version) {
        $user_id = $version->get('User_ID');
        if ($user_id == $GLOBALS['AUTH_USER_ID']) {
            return '';
        }
        $nc_core = nc_core::get_object();
        return NETCAT_VERSION_TITLE_BY_USER . ' ' . $nc_core->user->get_by_id($user_id, $nc_core->AUTHORIZE_BY);
    }

    /**
     * @param string $action
     * @param nc_version_record|null $version
     * @return array|null
     * @throws nc_record_exception
     */
    protected function get_undo_redo_data($action, nc_version_record $version = null) {
        if (!$version) {
            return null;
        }

        $title = array(
            // "Отменить" или "Вернуть"
            $action,
            // [что]
            $version->get_changeset_description() ?: $version->generate_description(),
            // "пользователем [user]"
            $this->get_user_data_if_not_current($version),
            // ...отразится не только на этой странице...
            $version->get('Subdivision_ID') ? '' : '<div class="nc-version-title-warning">' . NETCAT_VERSION_TITLE_GLOBAL_WARNING . '</div>',
        );

        $title = implode(' ', array_filter($title));

        return array(
            'version_id' => $version->get_id(),
            'changeset_id' => $version->get('Version_Changeset_ID'),
//            'changeset_id' => $changeset_to_restore ?: $version->get('Version_Changeset_ID'),
            'title' => $title,
        );
    }

    /**
     * @return string
     * @throws nc_record_exception
     */
    protected function action_get_undo_and_redo() {
        $nc_core = nc_core::get_object();
        $this->use_layout = false;

        $routing_data = array(
            'action' => $nc_core->input->fetch_get('page_action'),
            'site_id' => (int)$nc_core->input->fetch_get('site_id'), // cannot be null
            'folder_id' => (int)$nc_core->input->fetch_get('subdivision_id'), // cannot be null
            'infoblock_id' => (int)$nc_core->input->fetch_get('infoblock_id') ?: null, // cannot be 0
            'object_id' => (int)$nc_core->input->fetch_get('object_id') ?: null, // cannot be 0
        );

        $areas = $nc_core->input->fetch_get('page_areas') ?: 'main';
        $areas = array_filter(explode(' ', $areas));

        $getter = new nc_version_history_retriever($routing_data, $areas);

        list($version_for_undo, $version_for_redo) = $getter->get_versions_for_undo_and_redo();

        $response = array(
            'undo' => $this->get_undo_redo_data(NETCAT_VERSION_UNDO, $version_for_undo),
            'redo' => $this->get_undo_redo_data(NETCAT_VERSION_REDO, $version_for_redo),
        );

        return nc_array_json($response);
    }

    /**
     * @return string
     */
    protected function action_undo_changeset() {
        global $perm;
        $nc_core = nc_core::get_object();

        $subdivision_id = $nc_core->input->fetch_post('subdivision_id');
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $subdivision_id);

        $changeset_id = (int)$nc_core->input->fetch_post('changeset_id');
        $changeset = new nc_version_changeset($changeset_id);
        $changeset->undo();

        $this->use_layout = false;
        return '';
    }

    /**
     * @return string
     */
    protected function action_redo_changeset() {
        global $perm;
        $nc_core = nc_core::get_object();

        $subdivision_id = $nc_core->input->fetch_post('subdivision_id');
        $perm->ExitIfNotAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $subdivision_id);

        $changeset_id = (int)$nc_core->input->fetch_post('changeset_id');
        $changeset = new nc_version_changeset($changeset_id);
        $changeset->restore();

        $this->use_layout = false;
        return '';
    }

    /**
     * @param $result
     * @return string|true
     */
    protected function after_action($result) {
        if (!$this->use_layout) {
            return $result;
        }

        return BeginHtml() . $result . EndHtml();
    }

}