<?php

class nc_search_permission {

    /** @var array права, необходимые для выполнения различных действий */
    static protected $permissions = array(
        // Действие «просмотр». Значение: имя шаблона страницы (views/*.php)
        'brokenlinks' => 'broken_links_view',
        'events' => array('indexing_start', 'rule_edit', 'settings_edit'),
        'indexing' => array('indexing_start', 'rule_edit'),
        'indexing_add_schedule' => array('indexing_start', 'rule_edit'),
        'indexing_on_request' => array('indexing_start', 'rule_edit'),
        'info' => array(null), // не просто null, т. к. проверяется через isset()
        'queries' => 'query_log_view',
        'queries_details' => 'query_log_view',
        'rules' => array('indexing_start', 'rule_edit'),
        'rules_edit' => array('rule_edit'),
        'widget' => array(null),
        // Действия с классами (сохранение, удаление)
        'nc_search_rule' => 'rule_edit',
        // Если шаблон или класс не заданы в массиве выше, требуется право 'settings_edit'
        // (например, может быть передано 'settings', которое не соответствует никакому template)
    );

    /**
     * @param string $name
     * @return bool
     */
    public static function check($name) {
        /** @var Permission $perm */
        global $perm;

        try {
            $search_module = array(NC_PERM_MODULE, 'search');
            if (isset(self::$permissions[$name])) {
                foreach ((array)self::$permissions[$name] as $permission) {
                    if ($perm->isAccess($search_module, $permission, 0)) {
                        return true;
                    }
                }
            } else if ($perm->isAccess($search_module, 'settings_edit', 0)) {
                return true;
            }
        } catch (Exception $e) {
        }

        return false;
    }

}