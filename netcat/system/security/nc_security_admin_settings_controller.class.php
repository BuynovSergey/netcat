<?php

abstract class nc_security_admin_settings_controller extends nc_security_admin_controller {

    /**
     * @param $pattern
     * @return bool
     */
    protected function site_has_own_settings_like($pattern) {
        return (bool)nc_db()->get_var(
            "SELECT 1 
                   FROM `Settings` 
                  WHERE `Key` LIKE '$pattern' 
                    AND `Module` = 'system' 
                    AND `Catalogue_ID` = $this->site_id
                  LIMIT 1"
        );
    }

    /**
     * @param $pattern
     */
    protected function delete_site_settings_like($pattern) {
        nc_db()->query(
            "DELETE FROM `Settings` 
              WHERE `Key` LIKE '$pattern'
                AND `Module` = 'system'
                AND `Catalogue_ID` = $this->site_id"
        );
        nc_core::get_object()->get_settings('', '', true, $this->site_id);
    }

}
