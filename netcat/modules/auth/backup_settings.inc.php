<?php

require_once dirname(__FILE__) . "/nc_auth_backup.class.php";

return array(
    'settings_dict_fields' => array(
        'pa_class_id' => 'Class_ID',
        'pm_class_id' => 'Class_ID',
        'friend_class_id' => 'Class_ID',
        'modify_sub' => 'Subdivision_ID',
        'user_list_cc' => 'Sub_Class_ID',
        'materials_sub_id' => 'Subdivision_ID',
    ),
    'extensions' => array(
        'site' => array(
            'nc_auth_backup',
        ),
    ),
);
