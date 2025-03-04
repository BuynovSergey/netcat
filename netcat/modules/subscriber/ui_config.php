<?php

/* $Id: ui_config.php 6210 2012-02-10 10:30:32Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Класс для облегчения формирования UI в модулях
 */
class ui_config_module_subscriber extends ui_config_module {

    public function __construct($active_tab = 'admin', $toolbar_action = 'mailer', $hash = '') {
        global $db;
        global $MODULE_FOLDER;

        parent::__construct('subscriber', $active_tab);



        if ($active_tab == 'admin') {
            $this->toolbar[] = array(
                    'id' => "mailer",
                    'caption' => NETCAT_MODULE_SUBSCRIBE_MAILERS,
                    'location' => "module.subscriber.mailer",
                    'group' => "subscribe"
            );

            $this->toolbar[] = array(
                    'id' => "once",
                    'caption' => NETCAT_MODULE_SUBSCRIBE_ONCE,
                    'location' => "module.subscriber.once",
                    'group' => "subscribe"
            );

            $this->toolbar[] = array(
                    'id' => "stats",
                    'caption' => NETCAT_MODULE_SUBSCRIBE_STATS,
                    'location' => "module.subscriber.stats",
                    'group' => "subscribe"
            );

            $this->toolbar[] = array(
                    'id' => "settings",
                    'caption' => NETCAT_MODULE_SUBSCRIBE_SETTINGS,
                    'location' => "module.subscriber.settings",
                    'group' => "subscribe"
            );



            if ($toolbar_action)
                    $this->locationHash = "module.subscriber.".$toolbar_action.( $hash ? ".".$hash : "" );
            $this->activeToolbarButtons[] = $toolbar_action;
        }
    }

}
?>