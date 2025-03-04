<?php

class nc_version {

    /**
     * @var int
     */
    protected $changeset_id;

    /**
     * @var bool
     */
    private $prevent_reopen_changeset = false;

    /**
     *
     */
    public function __construct() {
        $this->register_event_listeners();
    }

    /**
     * @param bool $state
     */
    public function prevent_reopen_changeset($state = true) {
        $this->prevent_reopen_changeset = $state;
    }

    /**
     *
     */
    protected function register_event_listeners() {
        $nc_core = nc_Core::get_object();

        $nc_core->event->add_listener(nc_event::AFTER_SITE_IMPORTED, array($this, 'site_import_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_SITE_CREATED, array($this, 'entity_create_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_SITE_UPDATED, array($this, 'entity_update_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_SITE_ENABLED, array($this, 'entity_enable_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_SITE_DISABLED, array($this, 'entity_disable_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_SITE_DELETED, array($this, 'entity_delete_listener'));

        $nc_core->event->add_listener(nc_event::AFTER_SUBDIVISION_CREATED, array($this, 'entity_create_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_SUBDIVISION_UPDATED, array($this, 'entity_update_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_SUBDIVISION_ENABLED, array($this, 'entity_enable_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_SUBDIVISION_DISABLED, array($this, 'entity_disable_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_SUBDIVISION_DELETED, array($this, 'entity_delete_listener'));

        $nc_core->event->add_listener(nc_event::AFTER_INFOBLOCK_CREATED, array($this, 'entity_create_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_INFOBLOCK_UPDATED, array($this, 'entity_update_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_INFOBLOCK_ENABLED, array($this, 'entity_enable_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_INFOBLOCK_DISABLED, array($this, 'entity_disable_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_INFOBLOCK_DELETED, array($this, 'entity_delete_listener'));

        $nc_core->event->add_listener(nc_event::AFTER_OBJECT_CREATED, array($this, 'entity_create_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_OBJECT_UPDATED, array($this, 'entity_update_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_OBJECT_ENABLED, array($this, 'entity_enable_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_OBJECT_DISABLED, array($this, 'entity_disable_listener'));
        $nc_core->event->add_listener(nc_event::AFTER_OBJECT_DELETED, array($this, 'entity_delete_listener'));
    }


    /**
     * @param string $action
     * @param int $site_id
     * @param int $subdivision_id
     * @param int $infoblock_id
     * @param int $component_id
     * @param int $object_id
     * @return array
     */
    protected function get_common_content_properties($action, $site_id, $subdivision_id, $infoblock_id, $component_id, $object_id) {
        return array(
            'Action' => $action,
            'Catalogue_ID' => $site_id,
            'Subdivision_ID' => $subdivision_id,
            'Sub_Class_ID' => $infoblock_id,
            'Class_ID' => $component_id,
            'Message_ID' => $object_id,
        );
    }

    /**
     * @param int $site_id
     */
    public function site_import_listener($site_id) {
        $nc_core = nc_core::get_object();
        if (!$nc_core->get_settings('StoreVersions', 'system', false, $site_id)) {
            return;
        }

        $this->init_changeset(NETCAT_VERSION_CHANGESET_SITE_IMPORT);
        $this->save_initial_site_state($site_id, nc_version_record::INITIAL);
        $this->commit_changeset();
    }

    /**
     * @param int $site_id
     * @param int $subdivision_id
     * @param int $infoblock_id
     * @param int $component_id
     * @param int $object_id
     */
    public function entity_create_listener($site_id, $subdivision_id = 0, $infoblock_id = 0, $component_id = 0, $object_id = 0) {
        $this->process_content_change_event(nc_version_record::CREATED, $site_id, $subdivision_id, $infoblock_id, $component_id, $object_id);
    }

    /**
     * @param int $site_id
     * @param int $subdivision_id
     * @param int $infoblock_id
     * @param int $component_id
     * @param int $object_id
     */
    public function entity_enable_listener($site_id, $subdivision_id = 0, $infoblock_id = 0, $component_id = 0, $object_id = 0) {
        $this->process_content_change_event(nc_version_record::ENABLED, $site_id, $subdivision_id, $infoblock_id, $component_id, $object_id);
    }

    /**
     * @param int $site_id
     * @param int $subdivision_id
     * @param int $infoblock_id
     * @param int $component_id
     * @param int $object_id
     */
    public function entity_disable_listener($site_id, $subdivision_id = 0, $infoblock_id = 0, $component_id = 0, $object_id = 0) {
        $this->process_content_change_event(nc_version_record::DISABLED, $site_id, $subdivision_id, $infoblock_id, $component_id, $object_id);
    }

    /**
     * @param int $site_id
     * @param int $subdivision_id
     * @param int $infoblock_id
     * @param int $component_id
     * @param int $object_id
     */
    public function entity_update_listener($site_id, $subdivision_id = 0, $infoblock_id = 0, $component_id = 0, $object_id = 0) {
        $this->process_content_change_event(nc_version_record::UPDATED, $site_id, $subdivision_id, $infoblock_id, $component_id, $object_id);
    }

    /**
     * @param int $site_id
     * @param int $subdivision_id
     * @param int $infoblock_id
     * @param int $component_id
     * @param int $object_id
     */
    public function entity_delete_listener($site_id, $subdivision_id = 0, $infoblock_id = 0, $component_id = 0, $object_id = 0) {
        $this->process_content_change_event(nc_version_record::DELETED, $site_id, $subdivision_id, $infoblock_id, $component_id, $object_id);
    }

    /**
     * @param string $action
     * @param int $site_id
     * @param int $subdivision_id
     * @param int $infoblock_id
     * @param int $component_id
     * @param int $object_id
     */
    protected function process_content_change_event($action, $site_id, $subdivision_id, $infoblock_id, $component_id, $object_id) {
        $nc_core = nc_core::get_object();

        if (!$nc_core->get_settings('StoreVersions', 'system', false, $site_id)) {
            return;
        }

        $common_properties = $this->get_common_content_properties($action, $site_id, $subdivision_id, $infoblock_id, $component_id, $object_id);

        if ($object_id) {
            // действие с объектом
            foreach ((array)$object_id as $id) {
                $this->create_version_record(array(
                        'Message_ID' => $id,
                        'Entity' => 'object',
                    ) + $common_properties);
            }
        } else if ($infoblock_id) {
            // действие с инфоблоком
            foreach ((array)$infoblock_id as $id) {
                $this->create_version_record(array(
                        'Sub_Class_ID' => $id,
                        'Entity' => 'infoblock',
                    ) + $common_properties);
            }
        } else if ($subdivision_id) {
            // действие с разделом
            foreach ((array)$subdivision_id as $id) {
                $this->create_version_record(array(
                        'Subdivision_ID' => $id,
                        'Entity' => 'subdivision',
                    ) + $common_properties);
            }
        } else {
            // действие с сайтом
            foreach ((array)$site_id as $id) {
                $this->create_version_record(array(
                        'Catalogue_ID' => $id,
                        'Entity' => 'site',
                    ) + $common_properties);
            }
        }
    }

    /**
     * @param array $data
     * @return nc_version_record
     */
    protected function create_version_record(array $data) {
        $data += array(
            'Timestamp' => date('Y-m-d H:i:s'),
            'User_ID' => $GLOBALS['AUTH_USER_ID'] ?: 0,
            'IsActual' => 1,
        );

        /** @var nc_version_record $version */
        $record_class = 'nc_version_record_' . $data['Entity'];
        $version = new $record_class($data);
        $version->save();
        return $version;
    }

    /**
     * @param $version_id
     * @return nc_version_record
     * @throws nc_record_exception
     */
    public function load_by_id($version_id) {
        return nc_version_record::load_by_query("SELECT * FROM `Version` WHERE `Version_ID` = " . (int)$version_id);
    }

    /**
     * @param int $id
     * @param int $subdivision_id
     * @return nc_version_record
     * @throws nc_record_exception
     */
    public function load_newest_in_chain($id, $subdivision_id = 0) {
        $changeset_id = nc_db()->get_var("SELECT `Version_Changeset_ID` FROM `Version` WHERE `Version_ID` = " . (int)$id);

        return nc_version_record::load_by_query(
            "SELECT *
             FROM `Version`
             WHERE `Version_ID` =
                 (
                     SELECT MAX(`Version_ID`)
                     FROM `Version`
                     WHERE `Version_Changeset_ID` = '" . $changeset_id . "'" . ($subdivision_id ? ' AND `Subdivision_ID` = ' . (int)$subdivision_id : '') . "
                 )
             LIMIT 1"
        );
    }

    /**
     * Возвращает ID «оригинальной» версии — которая является первой «самостоятельной», а не восстановленной
     *
     * @return int
     */
    public function get_original_version_id($version_id) {
        $nc_core = nc_core::get_object();
        while (true) {
            $restored_version_id = $nc_core->db->get_var(
                "SELECT `Restored_Version_ID` FROM `Version` WHERE `Version_ID` = $version_id LIMIT 1"
            );
            if (!$restored_version_id) {
                return $version_id;
            }
            $version_id = $restored_version_id;
        }
    }

    /**
     * Возвращает changeset id
     *
     * @return string
     */
    public function get_changeset_id() {
        return $this->changeset_id;
    }

    /**
     * Создаёт новый changeset; все создаваемые версии будут относиться к нему до вызова commit_changeset()
     *
     * @param string $description
     *
     * @return int
     */
    public function init_changeset($description = '') {
        if ($this->prevent_reopen_changeset) {
            return $this->changeset_id;
        }

        $this->commit_changeset();

        $db = nc_core::get_object()->db;
        $db->query("INSERT INTO `Version_Changeset` SET `Description` = '{$db->escape($description)}'");

        $this->changeset_id = $db->insert_id;

        return $this->changeset_id;
    }

    /**
     * @param string $description
     *
     * @return void
     */
    public function update_changeset_description($description){
        if ($this->changeset_id) {
            $db = nc_core::get_object()->db;
            nc_db()->query("UPDATE `Version_Changeset` SET `Description` = '{$db->escape($description)}' WHERE `Version_Changeset_ID` = {$this->changeset_id}");
        }
    }

    /**
     * Закрывает changeset
     *
     * @return void
     */
    public function commit_changeset() {
        if ($this->prevent_reopen_changeset) {
            return;
        }

        if ($this->changeset_id) {
            nc_core::get_object()->db->query(
                "DELETE FROM `Version_Changeset` WHERE `Version_Changeset_ID` = $this->changeset_id AND `ChangeCount` = 0"
            );
        }

        $this->changeset_id = null;
    }

    /**
     * @param int $site_id
     * @param string $action
     */
    public function save_initial_site_state($site_id, $action = nc_version_record::INITIAL) {
        @set_time_limit(0);
        ignore_user_abort(true);

        $this->init_changeset(NETCAT_VERSION_CHANGESET_INITIAL);

        $nc_core = nc_Core::get_object();
        $this->create_version_record(array(
                'Entity' => 'site',
            ) + $this->get_common_content_properties($action, $site_id, 0, 0, 0, 0));

        $subdivision_ids = $nc_core->db->get_col('SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Catalogue_ID` = ' . (int)$site_id);

        if (!empty($subdivision_ids)) {
            foreach ($subdivision_ids as $subdivision_id) {
                $this->create_version_record(array(
                        'Entity' => 'subdivision',
                    ) + $this->get_common_content_properties($action, $site_id, $subdivision_id, 0, 0, 0));
            }
        }

        $infoblocks = (array)$nc_core->db->get_results('SELECT `Sub_Class_ID`, `Subdivision_ID`, `Class_ID` FROM `Sub_Class` WHERE `Catalogue_ID` = ' . (int)$site_id, ARRAY_A);

        foreach ($infoblocks as $infoblock) {
            $this->create_version_record(array(
                    'Entity' => 'infoblock',
                ) + $this->get_common_content_properties($action, $site_id, $infoblock['Subdivision_ID'], $infoblock['Sub_Class_ID'], 0, 0));

            if ($infoblock['Class_ID']) {
                $message_ids = $nc_core->db->get_col("SELECT `Message_ID` FROM Message" . (int)$infoblock['Class_ID'] . " WHERE `Sub_Class_ID` = " . (int)$infoblock['Sub_Class_ID']);
                if (!empty($message_ids)) {
                    foreach ($message_ids as $message_id) {
                        $this->create_version_record(array(
                                'Entity' => 'object',
                            ) + $this->get_common_content_properties($action, $site_id, $infoblock['Subdivision_ID'], $infoblock['Sub_Class_ID'], $infoblock['Class_ID'], $message_id));
                    }
                }
            }
        }
    }

    /**
     * Удаляет все версии контента указанного сайта
     * @param int $site_id
     */
    public function remove_site_versions($site_id) {
        $site_id = (int)$site_id;
        $db = nc_core::get_object()->db;

        $db->query(
            "DELETE FROM `Version_Changeset` 
             WHERE `Version_Changeset_ID` IN (
                 SELECT DISTINCT `Version_Changeset_ID` FROM `Version` WHERE `Catalogue_ID` = $site_id
             )"
        );

        $db->query("DELETE FROM `Version` WHERE `Catalogue_ID` = $site_id");
        $db->query("DELETE FROM `Version_ContentHistory` WHERE `Catalogue_ID` = $site_id");
        $db->query("DELETE FROM `Version_File`");
    }

}
