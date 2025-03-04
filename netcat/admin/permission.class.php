<?php

class Permission {

    private $_UserID;
    /** @var array массив с группами либо идентификатор группы */
    private $_PermissionGroupID;
    /** @var int берется значение из поля таблицы User */
    private $_InsideAdminAccess;
    private $db;
    /** @var int 1 - если есть соответствующее право, 0 нету */
    private $_director, $_supervisor, $_guest;
    /** @var array Массивы, индекс - айди сущности, значение - permission set; 0 — permission set для всех сайтов */
    private $_catalogue = array(), $_catalogueContent = array(), $_sub = array(), $_cc = array();
    /** @var array Массив, нулевой элемент - для всех списков */
    private $_classificator = array(); // Массив, нулевой элемент - для всех списков
    private $_countPerm;
    /** @var int значение - permission set */
    private $_user = 0;
    private $_group = 0;
    /** @var array Массив, индекс - номер рассылки */
    private $_subscriber = array();
    /** @var array аналогично, как у $_catalogue, $_sub.... */
    private $_banCat, $_banCatContent, $_banSub, $_banCC;
    private $_fckeditor;
    private $_ckeditor;
    private $instanceTypes = array(
        'catalogue'   => CATALOGUE_ADMIN,
        'subdivision' => SUBDIVISION_ADMIN,
        'subclass'    => SUB_CLASS_ADMIN,
        'content'     => CATALOGUE_CONTENT_ADMIN
    );

    private $modules = array();

    /** @var array список проверяемых прав для каждой из сущностей */
    static protected $allInstanceActions = array(
        NC_PERM_ITEM_SITE => array(
            NC_PERM_ACTION_SUBCLASSLIST,
            NC_PERM_ACTION_SUBCLASSDEL,
            NC_PERM_ACTION_SUBCLASSADD,
            NC_PERM_ACTION_ADMIN,
            NC_PERM_ACTION_INFO,
            NC_PERM_ACTION_EDIT,
            NC_PERM_ACTION_ADDSUB,
            NC_PERM_ACTION_DELSUB,
        ),
        NC_PERM_ITEM_SUB => array(
            NC_PERM_ACTION_ADD,
            NC_PERM_ACTION_DEL,
            NC_PERM_ACTION_INFO,
            NC_PERM_ACTION_EDIT,
            NC_PERM_ACTION_SUBCLASSLIST,
            NC_PERM_ACTION_SUBCLASSDEL,
            NC_PERM_ACTION_SUBCLASSADD,
            NC_PERM_ACTION_LIST,
        ),
        NC_PERM_ITEM_CC => array(
            NC_PERM_ACTION_ADMIN,
            NC_PERM_ACTION_EDIT,
            NC_PERM_ACTION_INFO,
        ),
        NC_PERM_CLASSIFICATOR => array(
            NC_PERM_ACTION_LIST,
            NC_PERM_ACTION_ADD,
            NC_PERM_ACTION_DEL,
            NC_PERM_ACTION_VIEW,
            NC_PERM_ACTION_ADMIN,
            NC_PERM_ACTION_ADDELEMENT,
            NC_PERM_ACTION_EDIT,
        ),
    );

    static protected $instances = array();

    /**
     * Экземпляр класса для конкретного пользователя (с внутренним кэшированием)
     *
     * @param int $user_id
     * @return Permission
     */
    public static function forUser($user_id) {
        if (isset(self::$instances[$user_id])) {
            return self::$instances[$user_id];
        }
        return new self($user_id, 0);
    }

    /**
     * Экземпляр класса для группы (с внутренним кэшированием)
     *
     * @param int $group_id
     * @return Permission
     */
    public static function forGroup($group_id) {
        if (isset(self::$instances[0][$group_id])) {
            return self::$instances[0][$group_id];
        }
        return new self(0, $group_id);
    }

    /**
     * @param int $UserID
     * @param int $PermissionGroupID
     * @param array $user_result массив с результатами выборки
     */
    public function __construct($UserID, $PermissionGroupID = 0, $user_result = null) {
        global $db;

        $this->db = $db;
        $this->_UserID = (int)$UserID;
        $this->_fckeditor = false;
        $this->_ckeditor = false;

        // Если есть user_result - то данные можно взять оттуда
        if ($UserID && $user_result) {
            $this->_InsideAdminAccess = $user_result[0]['InsideAdminAccess'];
            foreach ($user_result as $row) {
                $this->_PermissionGroupID[] = $row['PermissionGroups_ID']; // sic, не путать с ключом PermissionGroup_ID
            }
        } elseif ($UserID && !$user_result) { // иначе запросом
            $this->_InsideAdminAccess = $this->db->get_var("SELECT `InsideAdminAccess` FROM `User` WHERE User_ID='" . $this->_UserID . "'");
            $this->_PermissionGroupID = nc_usergroup_get_group_by_user($this->_UserID);
        } else { // идет работа только с группой
            $this->_PermissionGroupID = array((int)$PermissionGroupID);
        }

        $this->_countPerm = 0;

        $query_where_user = $this->_UserID > 0 ? " `User_ID`='{$UserID}' OR " : '';
        $query_where_permission_group_ids = implode(',', $this->_PermissionGroupID);
        $SelectPerm = "SELECT `AdminType`, `Catalogue_ID`, `PermissionSet`
                       FROM `Permission`
                       WHERE (
                           ({$query_where_user} `PermissionGroup_ID` IN ({$query_where_permission_group_ids}))
                           AND (
                               (`PermissionBegin` IS NULL OR UNIX_TIMESTAMP(`PermissionBegin`) <= UNIX_TIMESTAMP())
                               AND (`PermissionEnd` IS NULL OR UNIX_TIMESTAMP(`PermissionEnd`) >= UNIX_TIMESTAMP())
                           )
                       )";

        $PermResult = $this->db->get_results($SelectPerm, ARRAY_A);

        if (!empty($PermResult)) {
            foreach ($PermResult as $PermArray) {
                switch ($PermArray['AdminType']) {
                    case DIRECTOR:
                        $this->_director = 1;
                        $this->_fckeditor = true;
                        $this->_ckeditor = true;
                        break;
                    case SUPERVISOR:
                        $this->_supervisor = 1;
                        $this->_fckeditor = true;
                        $this->_ckeditor = true;
                        break;
                    case GUEST:
                        $this->_guest = 1;
                        $this->_fckeditor = false;
                        $this->_ckeditor = false;
                        break;
                    case CATALOGUE_ADMIN:
                        $this->addToMask('_catalogue', $PermArray['Catalogue_ID'], $PermArray['PermissionSet'], true);
                        break;
                    case CATALOGUE_CONTENT_ADMIN:
                        $this->addToMask('_catalogueContent', $PermArray['Catalogue_ID'], $PermArray['PermissionSet'], true);
                        break;
                    case SUBDIVISION_ADMIN:
                        $this->addToMask('_sub', $PermArray['Catalogue_ID'], $PermArray['PermissionSet'], true);
                        break;
                    case SUB_CLASS_ADMIN:
                        $this->addToMask('_cc', $PermArray['Catalogue_ID'], $PermArray['PermissionSet'], true);
                        break;
                    case MODERATOR: // управляет пользователями
                        $this->_user |= $PermArray['PermissionSet'];
                        break;
                    case GROUP_MODERATOR: // управляет пользователями
                        $this->_group |= $PermArray['PermissionSet'];
                        break;
                    case CLASSIFICATOR_ADMIN:
                        $this->addToMask('_classificator', $PermArray['Catalogue_ID'], $PermArray['PermissionSet'], false);
                        break;
                    case SUBSCRIBER:
                        $this->addToMask('_subscriber', $PermArray['Catalogue_ID'], 1, false);
                        break;
                    case BAN_SITE: // ограничение в правах
                        $this->addToMask('_banCat', $PermArray['Catalogue_ID'], $PermArray['PermissionSet'], false);
                        break;
                    case BAN_SITE_CONTENT:
                        $this->addToMask('_banCatContent', $PermArray['Catalogue_ID'], $PermArray['PermissionSet'], false);
                        break;
                    case BAN_SUB:
                        $this->addToMask('_banSub', $PermArray['Catalogue_ID'], $PermArray['PermissionSet'], false);
                        break;
                    case BAN_CC:
                        $this->addToMask('_banCC', $PermArray['Catalogue_ID'], $PermArray['PermissionSet'], false);
                        break;
                }
                $this->_countPerm++;
            }
        }

        if ($this->_UserID > 0) {
            self::$instances[$this->_UserID] = $this;
        } else if ($this->_PermissionGroupID) {
            self::$instances[0][$this->_PermissionGroupID[0]] = $this;
        }
    }

    /**
     * Используется в конструкторе для установки свойств _catalogue, _sub и т. п.
     *
     * @param string $key
     * @param int $catalogue_id
     * @param int $permission_set
     * @param bool $add_wysiwyg_permissions
     * @return void
     */
    protected function addToMask($key, $catalogue_id, $permission_set, $add_wysiwyg_permissions) {
        $property = &$this->$key;
        $permission_set = (int)$permission_set;
        if (!isset($property[$catalogue_id])) {
            $property[$catalogue_id] = $permission_set;
        } else {
            $property[$catalogue_id] |= $permission_set;
        }
        if ($add_wysiwyg_permissions && $permission_set & (MASK_ADD | MASK_EDIT | MASK_MODERATE)) {
            $this->_fckeditor = true;
            $this->_ckeditor = true;
        }
    }

    /**
     * Возвращает user id
     *
     * @return int usr id
     */
    public function GetUserID() {
        return $this->_UserID;
    }

    /**
     * Возвращает значение поля, по которому происходит авторизация
     *
     * @return string login
     */
    public function getLogin() {
        static $cache = array();

        if (!array_key_exists($this->_UserID, $cache)) {
            global $AUTHORIZE_BY;
            $select = "SELECT `{$AUTHORIZE_BY}` From `User` WHERE User_ID='{$this->_UserID}'";
            $cache[$this->_UserID] = $this->db->get_var($select);
        }

        return $cache[$this->_UserID];
    }

    /**
     * Return, has user access to admin?
     * Имеет доступ,если стоит галочка доступ в зону администрирования, либо
     * если он директор, супервизор, администратор/модератор сайта/раздела/компонента,
     * разработчик или модератор(управляющий пользователями)
     * @return bool
     */
    public function isInsideAdmin() {
        return ($this->_InsideAdminAccess || $this->isSupervisor()) && (
            $this->isAccessDevelopment()
            || $this->_user
            || $this->_group
            || $this->isInstanceModeratorAdmin('site')
            || $this->isInstanceModeratorAdmin('sub')
            || $this->isInstanceModeratorAdmin('cc')
            || $this->hasAnyModulePermission()
        );
    }

    /**
     * Is user a director?
     *
     * @return bool
     */
    public function isDirector() {
        return $this->_director;
    }

    /**
     * Is user a guest?
     *
     * @return bool
     */
    public function isGuest() {
        if ($this->isSupervisor()) {
            return false;
        }
        return $this->_guest;
    }

    /**
     * is user a supervisor or director
     *
     * @return bool
     */
    public function isSupervisor() {
        return ($this->_director || $this->_supervisor);
    }

    /**
     * Всегда возвращает 0, только для совместимости со старым классом
     *
     * @return int 0
     */
    public function isManager() {
        return 0;
    }

    /**
     * Есть ли у пользователя какие-то явно заданные права с типом редактора?
     * @return bool
     */
    public function hasAnyEditorPermissions() {
        return $this->_director || $this->_supervisor || $this->_catalogue || $this->_catalogueContent || $this->_sub || $this->_cc;
    }

    /**
     * Есть ли у пользователя какие-то права на списки?
     * @return bool
     */
    public function hasAnyClassificatorPermissions() {
        return $this->_director || $this->_supervisor || $this->_classificator;
    }

    /**
     * Есть ли у пользователя какие-то права на подписки?
     * @return bool
     */
    public function hasAnySubscriptionPermissions() {
        return $this->_director || $this->_supervisor || $this->_subscriber;
    }

    /* --- Catalogue --- */

    /**
     * Есть ли доступ к сайту с заданными правами
     *
     * @param int $CatalogueID , 0 - all catalogue
     * @param int $mask
     * @return bool
     */
    public function isCatalogue($CatalogueID, $mask) {
        return $this->_director || $this->_supervisor || $this->hasSitePermission($CatalogueID, $mask);
    }

    /**
     * Является ли пользователь администратором всех сайтов
     *
     * @return bool
     */
    public function isAllSiteAdmin() {
        if ($this->_director || $this->_supervisor) {
            return true;
        }
        return $this->hasSitePermission(0, MASK_ADMIN);
    }

    /**
     * Является ли пользователь модератором всех сайтов
     *
     * @return bool
     */
    public function isAllSiteModerator() {
        if ($this->isAllSiteAdmin()) {
            return true;
        }
        return $this->hasSitePermission(0, MASK_MODERATE);
    }

    /**
     * Является ли пользователь администратором сайта
     *
     * @param int $CatalogueID ID
     * @return bool
     */
    public function isCatalogueAdmin($CatalogueID) {
        return $this->isCatalogue($CatalogueID, MASK_ADMIN);
    }

    /**
     * Может ли пользователь администрировать блоки вне контентной области?
     *
     * @param $CatalogueID
     * @return bool
     */
    public function isCatalogueAllBlocksAdmin($CatalogueID) {
        return $this->_director || $this->_supervisor || $this->hasSitePermission($CatalogueID, MASK_ADMIN);
    }


    /**
     * Является ли пользователь админом хотя бы одного сайта?
     *
     * @return bool
     */
    public function IsAnyCatalogueAdmin() {
        if ($this->_director || $this->_supervisor) {
            return true;
        }

        foreach ((array)$this->_catalogue as $v) {
            if ($v & MASK_ADMIN) {
                return true;
            }
        }

        return false;
    }

    /* --- Subdivision --- */

    /**
     * Есть ли право к данному разделу с данной маской
     *
     * @param int $SubdivisionID ID
     * @param int $mask
     * @param bool $checkParents - учитывать родителей (т.е. если пользователь имеет данное право к родителю, то вернет true)
     * @return bool
     */
    public function isSubdivision($SubdivisionID, $mask, $checkParents = true) {
        global $nc_core;
        $SubdivisionID = (int)$SubdivisionID;
        if ($this->_director || $this->_supervisor) {
            return true;
        }

        try {
            $catalogue_id = $nc_core->subdivision->get_by_id($SubdivisionID, 'Catalogue_ID');
        } catch (Exception $e) {
            $this->triggerError($e);
            return false;
        }

        if ($this->hasContentPermission($catalogue_id, $mask)) {
            return true;
        }

        if (empty($this->_sub)) {
            return false;
        }

        if (!empty($this->_sub[$SubdivisionID]) && $this->_sub[$SubdivisionID] & $mask) {
            return true;
        }

        // Проверить на доступ к родителям
        if ($checkParents) {
            // Получим все id родителей данного саба
            foreach ($nc_core->subdivision->get_parent_tree($SubdivisionID) as $parent) { // Проверка на доступ к родителям раздела
                if (isset($parent['Subdivision_ID'], $this->_sub[$parent['Subdivision_ID']]) && $this->_sub[$parent['Subdivision_ID']] & $mask) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Пользователь админ раздела?
     *
     * @param int $SubdivisionID ID
     * @param bool $checkParents учитывать родителей?
     * @return bool
     */
    public function isSubdivisionAdmin($SubdivisionID, $checkParents = true) {
        return $this->isSubdivision($SubdivisionID, MASK_ADMIN, $checkParents);
    }

    /**
     * Пользователь админ какого либо раздела?
     *
     * @return bool
     */
    public function IsAnySubdivisionAdmin() {
        if ($this->IsAnyCatalogueAdmin()) {
            return true;
        }
        foreach ((array)$this->_sub as $v) {
            if ($v & MASK_ADMIN) {
                return true;
            }
        }
        return false;
    }

    /* --- Sub Class --- */

    /**
     * Проверка доступа к инфоблоку
     *
     * @param int $SubClassID
     * @param int $mask
     * @param bool $check_parents учитывать права на родительские контейнеры, разделы и сайт
     * @return bool
     */
    public function isSubClass($SubClassID, $mask, $check_parents = true) {
        if ($this->_director || $this->_supervisor) {
            return true;
        }

        if (isset($this->_cc[$SubClassID]) && ($this->_cc[$SubClassID] & $mask)) {
            return true;
        }

        if ($check_parents) {
            try {
                $nc_core = nc_core::get_object();

                // Проверка прав на родительский контейнер
                $parent_container_id = $nc_core->sub_class->get_by_id($SubClassID, 'Parent_Sub_Class_ID');
                if ($parent_container_id) {
                    return $this->isSubClass($parent_container_id, $mask, true);
                }

                $site_id = $nc_core->sub_class->get_by_id($SubClassID, 'Catalogue_ID');
                $subdivision_id = $nc_core->sub_class->get_by_id($SubClassID, 'Subdivision_ID');
                // Инфоблок раздела
                if ($subdivision_id) {
                    // редактор раздела
                    if ($this->isSubdivision($subdivision_id, $mask)) {
                        return true;
                    }
                    // редактор сайта или редактор контента сайта
                    if ($this->hasContentPermission($site_id, $mask)) {
                        return true;
                    }
                    // Сквозной блок + редактор сайта
                } elseif ($this->hasSitePermission($site_id, $mask)) {
                    return true;
                }
            } catch (Exception $e) {
                $this->triggerError($e);
                return false;
            }
        }

        return false;
    }

    /**
     * Пользователь является ли админом данного сс
     *
     * @param int $SubClassID
     * @return bool
     */
    public function isSubClassAdmin($SubClassID) {
        return $this->isSubClass($SubClassID, MASK_ADMIN);
    }

    /**
     * Пользователь является ли админом какого-либо сс
     *
     * @return bool
     */
    public function isAnySubClassAdmin() {
        if ($this->_director ||
            $this->_supervisor ||
            $this->hasContentPermission(0, MASK_ADMIN) ||
            $this->IsAnySubdivisionAdmin()
        ) {
            return true;
        }
        foreach ((array)$this->_cc as $v) {
            if ($v & MASK_ADMIN) {
                return true;
            }
        }
        // Проверка на админа сайта прошла в методе IsAnySubdivisionAdmin
        return false;
    }

    /**
     * Является ли пользователь модератором или администратором хотя бы одного сайта/раздела/инфоблока?
     * @return bool
     */
    public function isModeratorOrAdmin() {
        return (
            $this->isInstanceModeratorAdmin('site') ||
            $this->isInstanceModeratorAdmin('sub') ||
            $this->isInstanceModeratorAdmin('cc')
        );
    }

    /**
     * Является ли пользователь модератором или администратором хотя бы одной сущности $instance_type?
     * @param string $instance_type тип сущности - 'catalogue', 'subdivision', 'subclass'
     * @return boolean
     */
    public function isInstanceModeratorAdmin($instance_type) {

        if (
            $this->_director ||
            $this->_supervisor ||
            $this->hasContentPermission(0, MASK_ADMIN | MASK_MODERATE)) {
            return true;
        }

        $values = array(); // сюда будут собираться id сущностей (сайтов, разделов либо сс)

        switch ($instance_type) {
            case 'catalogue':
            case 'site':
                $values = $this->_catalogue;
                break;
            case 'subdivision':
            case 'sub':
                $values = array_values((array)$this->_sub);
                break;
            case 'subclass':
            case 'cc':
                $values = array_values((array)$this->_cc);
                break;
        }

        foreach ($values as $v) {
            if ($v & (MASK_ADMIN | MASK_MODERATE)) {
                return true;
            }
        }

        return false;
    }

    public function accessToFCKEditor() {
        return ($this->_fckeditor && !$this->_guest);
    }

    // Доступ к корзине удаленных объектов
    public function accessToTrash() {
        return ($this->_fckeditor && !$this->_guest);
    }

    public function accessToCKEditor() {
        return ($this->_ckeditor && !$this->_guest);
    }

    /**
     * Есть ли у пользователя доступ хотя бы к одному системному списку
     * @return bool
     */
    public function isAnyClassificator() {
        if ($this->_director || $this->_supervisor) {
            return true;
        }
        return !empty($this->_classificator);
    }

    /**
     * Есть ли у пользователя доступ к дереву разработчика
     * @return bool
     */
    public function isAccessDevelopment() {
        return $this->_director || $this->_supervisor || $this->_guest || $this->isAnyClassificator();
    }

    /**
     * Есть ли у пользователя доступ к карте сайта
     * @return bool
     */
    public function isAccessSiteMap() {
        return
            $this->_director || $this->_supervisor || $this->_guest ||
            !empty($this->_catalogue) || !empty($this->_catalogueContent) ||
            !empty($this->_sub) || !empty($this->_cc);
    }

    /**
     * Проверить по маске доступ к классификатору
     * Нужно для системных списков, когда право нужно задавать явно
     * @param string $action
     * @param int $id classificator id
     * @return bool
     */
    public function isDirectAccessClassificator($action = '', $id = 0) {
        $mask = 0;

        switch ($action) { // По действию узнаем маску
            case NC_PERM_ACTION_VIEW:
                $mask = MASK_READ;
                break;
            case NC_PERM_ACTION_ADDELEMENT:
                $mask = MASK_ADD;
                break;
            case NC_PERM_ACTION_EDIT:
                $mask = MASK_EDIT;
                break;
            case NC_PERM_ACTION_ADMIN:
                $mask = MASK_MODERATE;
                break;
        }
        if (isset($this->_classificator[$id])) {
            return ($this->_classificator[$id] & $mask);
        }
        else {
            return 0;
        }
    }

    /**
     * Если у пользователя право подписки на рассылку с номером mailer_id
     * @param int $mailer_id номер рассылки
     * @return bool
     */
    public function isSubscriber($mailer_id) {
        return $this->_director || $this->_supervisor || $this->_subscriber[$mailer_id];
    }

    public function ExitIfGuest() {
        if ($this->_guest) {
            $this->halt(NETCAT_MODERATION_ERROR_NORIGHTS);
        }

        return null;
    }

    public function ExitIfNotSupervisor() {
        if (!$this->_supervisor && !$this->_director) {
            $this->halt(NETCAT_MODERATION_ERROR_NORIGHTS);
        }

        return null;
    }

    /**
     * Массив с ID объектов, которые пользователь может модерировать
     * @param string $instance_type тип сущности - 'catalogue', 'subdivision', 'subclass'
     * @param string $type_of_access тип доступа - 'moderator', 'admin'  (по умолчанию - оба)
     * @return array
     */
    public function listItems($instance_type, $type_of_access = null) {

        $instance_num = $this->instanceTypes[strtolower($instance_type)];
        if (!$instance_num) {
            trigger_error("Unknown instance type '$instance_type'", E_USER_WARNING);
            return array();
        }

        $rights_mask = MASK_MODERATE | MASK_ADMIN;
        switch (strtolower($type_of_access)) {
            case 'moderator':
                $rights_mask = MASK_MODERATE;
                break;
            case 'admin':
            case 'administrator':
                $rights_mask = MASK_ADMIN;
                break;
            case null:
                break;
            default:
                $rights_mask = $type_of_access;
                break;
        }

        $array = array();

        switch ($instance_type) {
            case 'catalogue':
            case 'site':
                $array = (array)$this->_catalogue;
                foreach ($this->_catalogueContent as $site_id => $permission) {
                    $array[$site_id] = nc_array_value($array, $site_id, 0) | $permission;
                }
                break;
            case 'subdivision':
            case 'sub':
                $array = (array)$this->_sub;
                break;
            case 'subclass':
            case 'cc':
                $array = (array)$this->_cc;
                break;
        }

        $ret = array();

        foreach ($array as $k => $v) {
            if ($v & $rights_mask) {
                $ret[] = $k;
            }
        }

        return $ret;
    }

    /**
     * Выйти, если нет права
     *
     * @param string|array $instance_type тип сущности
     * @param string $action действие
     * @param int $id id
     * @param string $text текст выводимой в плашке
     * @param int $posting будет ли запись в БД
     * @return int 1
     */
    public function ExitIfNotAccess($instance_type, $action = "", $id = 0, $text = NETCAT_MODERATION_ERROR_NORIGHTS, $posting = 1) {

        if ($this->_guest && !$posting) {
            return 1;
        }

        if ($this->_guest && $posting) {
            $this->halt(NETCAT_MODERATION_ERROR_NORIGHTGUEST);
        }

        if ($this->_director) {
            return 1;
        }

        if ($this->isAccess($instance_type, $action, $id, $posting)) {
            return 1;
        }

        if (!$text) {
            $text = NETCAT_MODERATION_ERROR_NORIGHTS;
        }

        // Права нет - на выход
        $this->halt($text);
    }

    /**
     * Если ли доступ
     *
     * @param string|array $instance_type тип сущности, константа NC_PERM_*    см. файл const.inc.php
     *     Для модулей — массив, где первый элемент — это NC_PERM_MODULE, второй — ключевое слово модуля
     * @param string $action действие, константа NC_PERM_ACTION_*;
     *     Для модулей — строка, обозначающая действие (если не задано, то проверяет, есть ли какие-то права на модуль;
     *     для проверки всех прав — константа NC_PERM_MODULE_ALL)
     * @param mixed $id id or array with id; для модулей — ID сайта, 0 (все сайты) или null (любой сайт)
     * @param int $posting будет ли запись в БД
     * @return bool
     */
    public function isAccess($instance_type, $action = '', $id = 0, $posting = 1) {
        global $nc_core;
        if ($this->_director) {
            return true;
        }

        // гость может смотреть, но не может редактировать ничего
        if ($this->_guest) {
            return !$posting;
        }

        // Практически ко всем действиям супервизор имеет доступ
        // кроме управления пользователями - надо проверять отдельно
        // чтобы он не мог редактировать директоров
        if ($this->_supervisor && $instance_type != NC_PERM_ITEM_USER && $instance_type != NC_PERM_ITEM_GROUP) {
            return true;
        }

        $instance = null;
        if (is_array($instance_type) && count($instance_type) > 1) {
            list($instance_type, $instance) = $instance_type;
        }

        // NB! при добавлении новых проверок может понадобиться внести изменения в свойство $allPermissions!

        switch ($instance_type) {

            // Catalogue
            case NC_PERM_ITEM_SITE:
                switch ($action) {
                    // Действия со сквозными блоками (для проверки действий с конкретными блоками
                    // проверяйте NC_PERM_ITEM_CC)
                    case NC_PERM_ACTION_SUBCLASSLIST:
                    case NC_PERM_ACTION_SUBCLASSDEL:
                    case NC_PERM_ACTION_SUBCLASSADD:
                    case NC_PERM_ACTION_ADMIN:    #
                    case NC_PERM_ACTION_EDIT:     # Изменить настройки сайта
                        return $this->hasSitePermission($id, MASK_ADMIN);
                    case NC_PERM_ACTION_INFO:     # Получить инфу по сайту
                    case NC_PERM_ACTION_ADDSUB:   # Добавить раздел в корень сайта
                    case NC_PERM_ACTION_DELSUB:   # Удалить корневой раздел
                        return $this->hasContentPermission($id, MASK_ADMIN);
                }
                break;

            // Subdivision
            case NC_PERM_ITEM_SUB:
                // Действия доступны админам сайта(ов)
                $catalogue_id = $nc_core->subdivision->get_by_id($id, 'Catalogue_ID');
                if ($this->hasContentPermission($catalogue_id, MASK_ADMIN)) {
                    return true;
                }

                switch ($action) {
                    case NC_PERM_ACTION_ADD:           # Добавить подраздел
                    case NC_PERM_ACTION_DEL:           # Удалить подраздел
                    case NC_PERM_ACTION_INFO:          # Получить инфу по разделу
                    case NC_PERM_ACTION_EDIT:          # Изменить раздел (настройки)
                    case NC_PERM_ACTION_SUBCLASSLIST:  # Получить список сс
                    case NC_PERM_ACTION_SUBCLASSDEL:   # Удалить сс в разделе
                    case NC_PERM_ACTION_SUBCLASSADD:   # Добавить
                        if ($this->isSubdivision($id, MASK_ADMIN)) {
                            return true;
                        }
                        break;
                    case NC_PERM_ACTION_LIST:           # Список разделов
                        if ($this->isSubdivision($id, MASK_ADMIN | MASK_MODERATE)) {
                            return true;
                        }
                        break;
                }
                break;

            // Sub class
            case NC_PERM_ITEM_CC:
                // Проверка, не является ли пользователем админом вышестоящей сущности

                if (is_array($id)) { // первый элемент - id раздела, второй - sub class id
                    $subdivision_id = $id[0];
                    $id = $id[1];
                } else {
                    $subdivision_id = $nc_core->sub_class->get_by_id($id, 'Subdivision_ID');
                }

                $catalogue_id = $nc_core->sub_class->get_by_id($id, 'Catalogue_ID');
                if ($this->hasSitePermission($catalogue_id, MASK_ADMIN)) {
                    return true;
                }
                if ($subdivision_id && $this->hasContentPermission($catalogue_id, MASK_ADMIN)) {
                    return true;
                }

                if ($subdivision_id) {
                    if ($this->isSubdivision($subdivision_id, MASK_ADMIN)) {
                        return true;
                    }
                }

                switch ($action) {
                    case NC_PERM_ACTION_ADMIN:
                    case NC_PERM_ACTION_EDIT:       # Изменить настройки компонента
                        if ($this->_cc[$id] & MASK_ADMIN) {
                            return true;
                        }
                        break;
                    case NC_PERM_ACTION_INFO:       # Информация о компоненте
                        if ($this->_cc[$id] & MASK_ADMIN) {
                            return true;
                        }
                        break;
                }

                break;

            // User
            case NC_PERM_ITEM_USER:
                return $this->checkModeratorPermissions($action, $id);

            // Group
            case NC_PERM_ITEM_GROUP:
                return $this->checkUserGroupPermissions($action, $id);

            // classificator
            case NC_PERM_CLASSIFICATOR:
                switch ($action) {
                    case NC_PERM_ACTION_LIST: # Показать список классификаторов
                        if (!empty($this->_classificator)) {
                            return true;
                        }
                        break;
                    case NC_PERM_ACTION_ADD:  # Добавлять или удалять может только Модератор всех списков
                    case NC_PERM_ACTION_DEL:
                        // нулевой индес означает все списки
                        if ($this->_classificator[0] & MASK_MODERATE) {
                            return true;
                        }
                        break;
                    case NC_PERM_ACTION_VIEW: # Просмотреть определенный список

                        if ($this->_classificator[$id] & MASK_READ) {
                            return true;
                        }
                        if ($this->_classificator[0] & MASK_READ) {
                            return true;
                        }
                        break;
                    case NC_PERM_ACTION_ADMIN:  # Изменить приоритеты элементов, удалить элементы списка
                        if ($this->_classificator[$id] & MASK_MODERATE) {
                            return true;
                        }
                        if ($this->_classificator[0] & MASK_MODERATE) {
                            return true;
                        }
                        break;
                    case NC_PERM_ACTION_ADDELEMENT: # Добавить элемент в список
                        if ($this->_classificator[$id] & MASK_ADD) {
                            return true;
                        }
                        if ($this->_classificator[0] & MASK_ADD) {
                            return true;
                        }
                        break;
                    case NC_PERM_ACTION_EDIT: # Изменить  элемент в списке
                        if ($this->_classificator[$id] & MASK_EDIT) {
                            return true;
                        }
                        if ($this->_classificator[0] & MASK_EDIT) {
                            return true;
                        }
                        break;
                }
                break;

            case NC_PERM_FAVORITE:
                return $this->IsAnySubdivisionAdmin();

            case NC_PERM_MODULE:
                if (!$instance || !$action) {
                    return $this->hasAnyModulePermission($instance ?: NC_PERM_MODULE_ALL, $id);
                }
                return $this->hasModulePermission($instance, $action, $id);

            // only supervisor and director
            case NC_PERM_SQL:
            case NC_PERM_CLASS:
            case NC_PERM_FIELD:
            case NC_PERM_SYSTABLE:
            case NC_PERM_PATCH:
            case NC_PERM_REPORT:
            case NC_PERM_TEMPLATE:
            case NC_PERM_CRON:
            case NC_PERM_TOOLSHTML:
            case NC_PERM_SEO:
            case NC_PERM_REDIRECT:
            case NC_PERM_WIDGETCLASS:
                break;
        }


        return false;
    }

    /**
     * Проверка прав на управление пользователями в isAccess()
     *
     * @param int $action
     * @param int $user_id
     * @return bool
     */
    protected function checkModeratorPermissions($action, $user_id) {
        $user_id = (int)$user_id;
        switch ($action) {
            case NC_PERM_ACTION_LIST: // Просмотреть список пользователей
                if ($this->_supervisor || $this->_user & MASK_READ) {
                    return true;
                }
                break;

            case NC_PERM_ACTION_ADD: // Добавить пользователя
                if ($this->_supervisor || $this->_user & MASK_ADD) {
                    return true;
                }
                break;

            case NC_PERM_ACTION_EDIT: // Редактирование пользователей
                // Если ID ≤ 0, то проверяется наличие соответствующего права
                // без проверки для конкретного редактируемого пользователя
                if ($user_id < 1) {
                    return $this->_supervisor || $this->_user & MASK_EDIT;
                }

                // Для редактирования собственных данных достаточно MASK_EDIT
                if ($this->_UserID === $user_id && ($this->_user & MASK_EDIT)) {
                    return true;
                }

                $other_user = self::forUser($user_id);
                if ($this->_supervisor) {
                    return !$other_user->isDirector();
                }

                $mask_to_check = $other_user->_countPerm ? MASK_MODERATE : MASK_EDIT;
                return ($this->_user & $mask_to_check) && $this->hasAllRightsOf($other_user);

            case NC_PERM_ACTION_RIGHT: // Редактирование прав
                if ($user_id < 1) {
                    return $this->_supervisor || $this->_user & MASK_ADMIN;
                }

                $other_user = self::forUser($user_id);

                if ($this->_supervisor) {
                    return !$other_user->isDirector();
                }

                // Себе нельзя назначить права, если ты не директор/супервизор
                if ($this->_UserID === $user_id) {
                    return false;
                }

                // Права можно присвоить любому другому пользователю, кроме директора/супервизора.
                // Возможность присвоения конкретных прав определяется при присваивании.
                return ($this->_user & MASK_ADMIN) && !$other_user->isSupervisor();

            case NC_PERM_ACTION_DEL: // Удаление пользователя
                if ($user_id < 1) {
                    return $this->_supervisor || $this->_user & MASK_DELETE;
                }

                $other_user = self::forUser($user_id);

                // Нельзя самоудалиться
                if ($this->_UserID === $user_id) {
                    return false;
                }

                if ($this->_supervisor) {
                    return !$other_user->isDirector();
                }

                // Пользователя с правами можно удалить только если есть право на модерирование
                if ($other_user->_countPerm && !($this->_user & MASK_MODERATE)) {
                    return false;
                }

                return ($this->_user & MASK_DELETE) && $this->hasAllRightsOf($other_user);
        }

        return false;
    }

    /**
     * Проверка прав на управление группами в isAccess()
     *
     * @param int $action
     * @param int $group_id
     * @return bool
     * @throws Exception
     */
    protected function checkUserGroupPermissions($action, $group_id) {
        $group_id = (int)$group_id;
        switch ($action) {
            case NC_PERM_ACTION_LIST: // Просмотреть список групп
                if ($this->_supervisor || $this->_group & MASK_READ) {
                    return true;
                }
                break;

            case NC_PERM_ACTION_ADD: // Добавить группу
                if ($this->_supervisor || $this->_group & MASK_ADD) {
                    return true;
                }
                break;

            case NC_PERM_ACTION_EDIT: // Редактирование группы
                // Если ID ≤ 0, то проверяется наличие соответствующего права
                // без проверки для конкретного редактируемой группы
                if ($group_id < 1) {
                    return $this->_supervisor || $this->_group & MASK_EDIT;
                }

                $group = self::forGroup($group_id);
                if ($this->_supervisor) {
                    return !$group->isDirector();
                }

                return ($this->_group & MASK_EDIT) && $this->hasAllRightsOf($group);

            case NC_PERM_ACTION_RIGHT: // Редактирование прав
                if ($group_id < 1) {
                    return $this->_supervisor || $this->_group & MASK_ADMIN;
                }

                $group = self::forGroup($group_id);

                if ($this->_supervisor) {
                    return !$group->isDirector();
                }

                return ($this->_group & MASK_ADMIN) && !$group->isSupervisor();

            case NC_PERM_ACTION_DEL: // Удаление группы
                if ($group_id < 1) {
                    return $this->_supervisor || $this->_group & MASK_DELETE;
                }

                $group = self::forGroup($group_id);

                if ($this->_supervisor) {
                    return !$group->isDirector();
                }

                return ($this->_group & MASK_DELETE) && $this->hasAllRightsOf($group);
        }

        return false;
    }

    /**
     * Проверка, не забанен ли пользователь для данного действия данного cc
     *
     * @param array $cc_env должен включать Sub_Class_ID, Subdivision_ID, Catalogue_ID
     * @param string $action
     * @return bool
     */
    public function isBanned($cc_env, $action) {
        global $parent_sub_tree, $delete, $checked;

        if (!$this->_banCC && !$this->_banSub && !$this->_banCat) {
            return false;
        }

        //По action определим маску
        switch ($action) {
            case 'read':
                $mask = MASK_READ;
                break;
            case 'add':
                $mask = MASK_ADD;
                break;

            case 'change':
                switch (true) {
                    case isset($delete):
                        $mask = MASK_DELETE;
                        break;
                    case isset($checked):
                        $mask = MASK_CHECKED;
                        break;
                    default:
                        $mask = MASK_EDIT;
                }
                break;

            case 'moderate':
                $mask = MASK_MODERATE;
                break;
            case 'subscribe':
                $mask = MASK_SUBSCRIBE;
                break;
            case 'comment':
                $mask = MASK_COMMENT;
                break;
            default:
                $mask = MASK_READ;
                break;
        }

        // Ограничение присвоили "прямо"
        if ($this->_banCC[$cc_env['Sub_Class_ID']] & $mask) {
            return true;
        }
        if ($this->_banSub[$cc_env['Subdivision_ID']] & $mask) {
            return true;
        }
        if ($this->checkSitePermission($this->_banCat, $cc_env['Catalogue_ID'], $mask)) {
            return true;
        }
        if ($this->checkSitePermission($this->_banCatContent, $cc_env['Catalogue_ID'], $mask)) {
            return true;
        }

        $parent_sub = array();

        // Так же надо проверить на ограничение родительский разделов
        // Если заполнен массив parent_sub_tree - то взять оттуда
        // иначе - из базы
        // Проверка на родительские разделы
        if (is_array($parent_sub_tree)) {
            // 0 и последний элемент - это сам раздел и сайт соответственно
            for ($i = 1; $i < count($parent_sub_tree) - 1; $i++) {
                $parent_sub[] = $parent_sub_tree[$i]['Subdivision_ID'];
            }
        } else {
            $parent_sub = $this->db->get_col(
                "SELECT parent.`Subdivision_ID`
                 FROM `Subdivision` as parent, `Subdivision` as child
                 WHERE child.`Subdivision_ID` = '{$cc_env['Subdivision_ID']}'
                 AND child.`Hidden_URL` LIKE CONCAT(parent.`Hidden_URL`, '%')
                 AND parent.`Catalogue_ID` = child.`Catalogue_ID`"
            );
        }

        foreach ($parent_sub as $v) {
            if ($this->_banSub[$v] & $mask) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return perm name
     * нужно для вывода в админку
     * @return int|string
     */
    public function GetMaxPerm() {
        if ($this->_director) {
            return BEGINHTML_PERM_DIRECTOR;
        }
        if ($this->_supervisor) {
            return BEGINHTML_PERM_SUPERVISOR;
        }
        if (!empty($this->_catalogue) || !empty($this->_catalogueContent)) {
            return BEGINHTML_PERM_CATALOGUEADMIN;
        }
        if (!empty($this->_sub)) {
            return BEGINHTML_PERM_SUBDIVISIONADMIN;
        }
        if (!empty($this->_cc)) {
            return BEGINHTML_PERM_SUBCLASSADMIN;
        }
        if ($this->_user || $this->_group) {
            return BEGINHTML_PERM_MODERATOR;
        }
        if (!empty($this->_classificator)) {
            return BEGINHTML_PERM_CLASSIFICATORADMIN;
        }
        if ($this->_guest) {
            return BEGINHTML_PERM_GUEST;
        }

        return 0;
    }

    /**
     * Получить catalogue id, всех сайтов к разделам или компонентам раздела которого
     * пользователь имеет доступ, определяемой маской
     *
     * @param int $mask
     * @param bool $withSubClass учитывать sub class
     * @return mixed 1.array([0]=>id, [1] => id...) 2. null - имеет доступ ко всем
     */
    public function GetAllowSite($mask = MASK_ADMIN, $withSubClass = true) {

        if ($this->_director || $this->_supervisor || $this->_guest || $this->hasContentPermission(0, $mask)) {
            return null;
        }

        $site_list = (array)$this->listItems('catalogue', $mask);

        $temp = $this->listItems('subdivision', $mask);
        if ($temp) {
            $site_list = array_merge(
                $this->db->get_col(
                    'SELECT `Catalogue_ID` FROM `Subdivision`
                     WHERE `Subdivision_ID` IN (' . implode(', ', $temp) . ')'
                ),
                $site_list
            );
        }

        if ($withSubClass) {
            $temp = $this->listItems('subclass', $mask);
            if ($temp) {
                $site_list = array_merge(
                    $this->db->get_col(
                        'SELECT `Catalogue_ID` FROM `Sub_Class`
                         WHERE `Sub_Class_ID` IN (' . implode(', ', $temp) . ')'
                    ),
                    $site_list
                );
            }
        }

        $site_list = array_unique((array)$site_list);

        if (empty($site_list)) {
            return array(-1); // нет доступа ни к чему
        }

        return $site_list;
    }

    /**
     * Получить все разделы, к которым пользователь имеет доступ (+ его родителей, если необходимо)
     *
     * @param int $CatalogueID
     * @param int $mask
     * @param bool $withParent - с этой опцией метод вернет разделы, к которым пользователь может не иметь доступа, но они
     * являются родителями к тем разделам, к которым пользователь имеет доступ
     * @param bool $withChild - вернуть еще разделы, к родителям которого пользователь имеет доступ
     * @param bool $withSubClass - вернуть разделы, к компонентам которого пользователь имеет доступ
     * @return mixed array ([0] => id, [1] => id) или null - если ко всем
     */
    public function GetAllowSub($CatalogueID, $mask = MASK_ADMIN, $withParent = true, $withChild = true, $withSubClass = true) {

        if ($this->_director || $this->_supervisor || $this->hasContentPermission($CatalogueID, $mask)) {
            return null;
        }

        $allow = array(); //возвращаемый массив
        $temp = array();

        // разделы непосредственно из прав
        foreach ((array)$this->_sub as $k => $v) {
            if ($v & $mask) {
                $allow[] = $k;
            }
        }

        if ($withChild) {
            // права на дочерние наследуются
            $allow = array_merge((array)$allow, (array)$this->_GetChildrenSub($allow));
        }

        if ($withSubClass) {
            foreach ((array)$this->_cc as $k => $v) {
                if ($v & $mask) {
                    $temp[] = $k;
                }
            }
            if (!empty($temp)) {
                $temp = $this->db->get_col('SELECT `Subdivision_ID` FROM `Sub_Class` WHERE `Sub_Class_ID` IN (' . implode(',', $temp) . ')');
            }

            $allow = array_merge((array)$allow, (array)$temp);
        }

        if (empty($allow)) {
            return array(-1);
        }

        $allow = array_unique($allow);

        if ($withParent) {
            $temp = $this->db->get_col(
                'SELECT parent.`Subdivision_ID`
                 FROM `Subdivision` AS parent, `Subdivision` AS allowed
                 WHERE allowed.`Subdivision_ID` IN (' . implode(',', $allow) . ")
                 AND allowed.`Hidden_URL` LIKE CONCAT(parent.`Hidden_URL`, '%')"
            );
            $allow = array_merge($allow, (array)$temp);
        }

        if (empty($allow)) {
            return array(-1);
        }

        $allow = array_unique($allow);

        return $allow;
    }

    /* --- Methods for work with users --- */

    /**
     * Показывать или нет "Пользователи" в горизонтальном меню админке
     * @return bool
     */
    public function isUserMenuShow() {
        return ($this->_supervisor || $this->_director || $this->_guest || $this->_user || $this->_group);
    }

    /**
     * Проверяет, есть ли у текущего пользователя есть все права, которые есть у
     * другого пользователя (или группы)
     *
     * @param self $other
     * @return bool
     */
    public function hasAllRightsOf(self $other) {
        if ($this->_director) {
            return true;
        }

        if ($other->_director) {
            return false;
        }

        if ($other->_supervisor) {
            return (bool)$this->_supervisor;
        }

        if ($this->_UserID) {
            if ($other->_UserID === $this->_UserID) {
                return true;
            }
        } else if ($this->_PermissionGroupID) {
            if ($other->_PermissionGroupID === $this->_PermissionGroupID) {
                return true;
            }
        }

        $sites_to_check = array_unique(array_merge(array_keys($other->_catalogue), array_keys($other->_catalogueContent)));
        foreach ($sites_to_check as $site_id) {
            if (!$this->hasSameActionPermissions($other, NC_PERM_ITEM_SITE, $site_id)) {
                return false;
            }
        }

        foreach (array_keys($other->_sub) as $subdivision_id) {
            if (!$this->hasSameActionPermissions($other, NC_PERM_ITEM_SUB, $subdivision_id)) {
                return false;
            }
        }

        foreach (array_keys($other->_cc) as $infoblock_id) {
            if (!$this->hasSameActionPermissions($other, NC_PERM_ITEM_CC, $infoblock_id)) {
                return false;
            }
        }

        foreach (array_keys($other->_classificator) as $list_id) {
            if (!$this->hasSameActionPermissions($other, NC_PERM_CLASSIFICATOR, $list_id)) {
                return false;
            }
        }

        if ($other->_user && ($this->_user | $other->_user) !== $this->_user) {
            return false;
        }

        if ($other->_group && ($this->_group | $other->_group) !== $this->_group) {
            return false;
        }

        return true;
    }

    /**
     * Проверяет, имеет ли этот пользователь все права на указанную сущность,
     * которые имеет другой пользователь или группа
     *
     * @param self $other
     * @param int $instance_type
     * @param int $id
     * @return bool
     */
    protected function hasSameActionPermissions(self $other, $instance_type, $id) {
        foreach (self::$allInstanceActions[$instance_type] as $action) {
            if ($other->isAccess($instance_type, $action, $id) && !$this->isAccess($instance_type, $action, $id)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Вычисляет маску прав для указанной сущности
     *
     * @param int $admin_type
     * @param int $id
     * @return int|false false, если маска неприменима/не поддерживается для указанного $admin_type
     */
    public function calculateMask($admin_type, $id) {
        $nc_core = nc_core::get_object();
        $full_mask = $this->isSupervisor() ? bindec(str_repeat('1', 32)) : 0;

        switch ($admin_type) {

            case CATALOGUE_ADMIN:
                return $full_mask ?:
                    nc_array_value($this->_catalogue, 0, 0) |
                    nc_array_value($this->_catalogue, $id, 0);

            case CATALOGUE_CONTENT_ADMIN:
                return $full_mask ?:
                    nc_array_value($this->_catalogueContent, 0, 0) |
                    nc_array_value($this->_catalogueContent, $id, 0) |
                    (int)$this->calculateMask(CATALOGUE_ADMIN, $id);

            case SUBDIVISION_ADMIN:
                if ($full_mask) {
                    return $full_mask;
                }
                $mask = 0;
                foreach ($nc_core->subdivision->get_parent_tree($id) as $parent) {
                    if (isset($parent['Subdivision_ID'])) {
                        $mask |= nc_array_value($this->_sub, $parent['Subdivision_ID'], 0);
                    } else {
                        $mask |= (int)$this->calculateMask(CATALOGUE_CONTENT_ADMIN, $parent['Catalogue_ID']);
                    }
                }
                return $mask;

            case SUB_CLASS_ADMIN:
                return $full_mask ?:
                    nc_array_value($this->_cc, $id, 0) |
                    (int)$this->calculateMask(SUBDIVISION_ADMIN, $nc_core->sub_class->get_by_id($id, 'Subdivision_ID'));

            case MODERATOR: // управляет пользователями
                return $full_mask ?: ($this->_user ?: 0);

            case GROUP_MODERATOR: // управляет пользователями
                return $full_mask ?: ($this->_group ?: 0);

            case CLASSIFICATOR_ADMIN:
                return $full_mask ?:
                    nc_array_value($this->_classificator, 0, 0) |
                    nc_array_value($this->_classificator, $id, 0);

            // BAN_SITE, BAN_SITE_CONTENT, BAN_SUB, BAN_CC здесь не учитываются, не обрабатываются (@todo?)
        }

        return false;
    }

    /**
     * Возвращает максимально возможные из запрошенных в $desired_mask права
     * (используется при присвоении прав: нельзя присвоить другому пользователю
     * права бо́льшие, чем есть у текущего пользователя).
     *
     * @param int $admin_type
     * @param int $id
     * @param int $desired_mask
     * @return int|false
     */
    public function getAllowedMask($admin_type, $id, $desired_mask) {
        $own_mask = $this->calculateMask($admin_type, $id);
        if ($own_mask === false) {
            return false;
        }
        return $desired_mask & $own_mask;
    }

    /**
     * Проверяет возможность изменения записи с правами другого пользователя.
     *
     * @param array $permission массив с записью из таблицы Permission (с элементами AdminType, Catalogue_ID, PermissionSet)
     * @return bool
     */
    public function canChangePermission(array $permission) {
        if ($this->_director) {
            return true;
        }

        if ($permission['AdminType'] == DIRECTOR) {
            return false;
        }

        return !($permission['PermissionSet'] & ~(int)$this->calculateMask($permission['AdminType'], $permission['Catalogue_ID']));
        //       ↑ изменяемые права           ↑ содержат биты, которых нет у текущего пользователя
    }

    /**
     * Вернет  массив  с правами пользователя
     * каждая "строчка" - отдельное право, в строчке следующее "столбцы":
     * ID, live (время жизни), AdminType, title,  0, 1,2,3 4,5, ...
     * 0 1 2 3 4 5 - это чтение, добавление, изменение, подписка, мод-ние и администрирование (берется из констант, могут быть в другом порядке)
     * эти элементы - тоже массивы, ключи: 'checkbox' - 0 - нету, 1 - есть, 2 - есть всегда, 3 - нету в принципе
     *                                    'mask' -  маска для этого права - берется из констант (1, 2,4,8,16....)
     * но может быть не массив, а "-1" - значит нету ничего в принципе (директор, ...)
     *
     * @param int $UserID
     * @param int $GroupID
     * @return array
     */
    public static function GetAllPermission($UserID, $GroupID = 0) {
        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        // Получим все права
        if ($GroupID) {
            $GroupID = (int)$GroupID;
            $Result = $db->get_results("SELECT * FROM `Permission` WHERE `PermissionGroup_ID`='{$GroupID}'", ARRAY_A);
        } else {
            $UserID = (int)$UserID;
            $Result = $db->get_results("SELECT * FROM `Permission` WHERE `User_ID`='{$UserID}'", ARRAY_A);
        }

        $quote = function($string) {
            return sprintf(CONTROL_USER_RIGHTS_QUOTES, $string);
        };
        $ret = array();
        foreach ((array)$Result as $prm) {
            $id = $prm['Permission_ID'];
            // если есть дата - преобразуем ее
            if ($prm['PermissionBegin']) {
                $prm['PermissionBegin'] = strtotime($prm['PermissionBegin']);
                $prm['PermissionBegin'] = strftime('%d.%m.%y %H:%M', $prm['PermissionBegin']);
            }
            if ($prm['PermissionEnd']) {
                $prm['PermissionEnd'] = strtotime($prm['PermissionEnd']);
                $prm['PermissionEnd'] = strftime('%d.%m.%y %H:%M', $prm['PermissionEnd']);
            }


            switch (true) { // определение live - времени жизни
                case (!$prm['PermissionBegin'] && !$prm['PermissionEnd']):
                    $ret[$id]['live'] = '<nobr>' . CONTROL_USER_RIGHTS_UNLIMITED . '</nobr>';
                    break;
                case ($prm['PermissionBegin'] && $prm['PermissionEnd']):
                    $ret[$id]['live'] =
                        '<nobr>' . NETCAT_COND_DATE_FROM. ' ' . $prm['PermissionBegin'] .
                        '</nobr><br><nobr>' . NETCAT_COND_DATE_TO . ' ' . $prm['PermissionEnd'] . '</nobr>';
                    break;
                case ($prm['PermissionBegin'] && !$prm['PermissionEnd']):
                    $ret[$id]['live'] = '<nobr>' . NETCAT_COND_DATE_FROM . ' ' . $prm['PermissionBegin'] . '</nobr>';
                    break;
                case (!$prm['PermissionBegin'] && $prm['PermissionEnd']):
                    $ret[$id]['live'] = '<nobr>' . NETCAT_COND_DATE_TO . ' ' . $prm['PermissionEnd'] . '</nobr>';
                    break;
            }

            $ret[$id]['AdminType'] = $prm['AdminType'];
            $ret[$id]['ID'] = $prm['AdminType'];
            $ret[$id]['PermissionSet'] = $ps = $prm['PermissionSet']; // ps - permission set
            $ret[$id]['Catalogue_ID'] = $c_id = $prm['Catalogue_ID'];

            // r - read, e - edit, d - add, s - subscribe, m - moderate, a - admin, l - delete, h -checked
            // в зависимости от этих переменных, включатся-выключатся checkbox'ы
            $r = ($ps & MASK_READ) ? 1 : 0;
            $d = ($ps & MASK_ADD) ? 1 : 0;
            $e = ($ps & MASK_EDIT) ? 1 : 0;
            $s = ($ps & MASK_SUBSCRIBE) ? 1 : 0;
            $m = ($ps & MASK_MODERATE) ? 1 : 0;
            $a = ($ps & MASK_ADMIN) ? 1 : 0;
            $c = ($ps & MASK_COMMENT) ? 1 : 0;
            $l = ($ps & MASK_DELETE) ? 1 : 0;
            $h = ($ps & MASK_CHECKED) ? 1 : 0;

            $ret[$id]['title'] = Permission::GetPermNameByID($prm['AdminType']);
            $ret[$id]['title'] .= ' ';

            switch ($prm['AdminType']) {
                case DIRECTOR:
                case SUPERVISOR:
                case GUEST:
                    // для них нету просмотр-изменение...-администрирование вообще
                    $r = $c = $e = $d = $s = $m = $a = $l = $h = -1;
                    break;
                case SUBSCRIBER:
                    $r = $c = $e = $d = $s = $m = $a = $l = $h = -1;
                    $nc_s = nc_subscriber::get_object();
                    $ret[$id]['title'] = self::GetPermNameByID($prm['AdminType']) . ' ' . CONTROL_USER_RIGHTS_SUBSCRIBER_OF . ' ' . $quote($nc_s->get($c_id, 'Name'));
                    break;
                case BAN_SITE:
                    $m = $a = 3; // нету модерирования и администрирования

                    if ($c_id) { // определенный сайт
                        $ret[$id]['title'] .= $quote($nc_core->catalogue->get_by_id($c_id, 'Catalogue_Name'));
                    } else {  // все сайты
                        $ret[$id]['title'] = CONTROL_USER_RIGHTS_SITEALL;
                    }
                    break;
                case CATALOGUE_ADMIN:
                    if ($c_id) {
                        $ret[$id]['title'] .= $quote($nc_core->catalogue->get_by_id($c_id, 'Catalogue_Name'));
                    } else {
                        $ret[$id]['title'] = CONTROL_USER_RIGHTS_CATALOGUEADMINALL;
                    }
                    break;
                case CATALOGUE_CONTENT_ADMIN:
                    if ($c_id) {
                        $ret[$id]['title'] .= CONTROL_USER_RIGHTS_CONTENTEDITOR . ' ' . $quote($nc_core->catalogue->get_by_id($c_id, 'Catalogue_Name'));
                    } else {
                        $ret[$id]['title'] = CONTROL_USER_RIGHTS_ALLSITESCONTENTEDITOR;
                    }
                    break;
                case BAN_SUB:
                    $m = $a = 3; // нету модерирования и администрирования
                // здесь break не нужен
                case SUBDIVISION_ADMIN:
                    $catalogue_name = $nc_core->catalogue->get_by_id($nc_core->subdivision->get_by_id($c_id, 'Catalogue_ID'), 'Catalogue_Name');
                    $ret[$id]['title'] .= ' ' . $quote($nc_core->subdivision->get_by_id($c_id, "Subdivision_Name")) . ' ' . CONTROL_USER_FUNCS_FROMCAT . ' ' . $quote($catalogue_name);
                    break;
                case BAN_CC:
                    $m = $a = 3;
                // здесь break не нужен
                case SUB_CLASS_ADMIN:
                    $subdivision_name = $nc_core->subdivision->get_by_id(GetSubdivisionBySubClass($c_id), "Subdivision_Name");
                    $ret[$id]['title'] .= $quote(GetSubClassName($c_id)) . ' ' . CONTROL_USER_FUNCS_FROMSEC . ' ' . $quote($subdivision_name);
                    break;
                case CLASSIFICATOR_ADMIN:
                    $r = 1;
                    $s = 3;
                    $a = 3;
                    $c = 3;
                    $l = 3;
                    $h = 3; // Просмотр - всегда, подписки и администрирования нет
                    if ($c_id) {
                        $ret[$id]['title'] .= $quote(self::_GetClassificatorNameByID($c_id));
                    } else {
                        $ret[$id]['title'] = CONTROL_USER_RIGHTS_CLASSIFICATORADMINALL;
                    }
                    break;
                case MODERATOR:
                    $r = 1;
                    $s = 3;
                    $c = 3;
                    $h = 3;
                    break;
                case GROUP_MODERATOR:
                    $r = 1;
                    $s = 3;
                    $c = 3;
                    $m = 3;
                    $h = 3;
                    break;
            }
            $ret[$id][NC_PERM_READ_ID]['checkbox'] = $r;
            $ret[$id][NC_PERM_READ_ID]['mask'] = MASK_READ;
            $ret[$id][NC_PERM_ADD_ID]['checkbox'] = $d;
            $ret[$id][NC_PERM_ADD_ID]['mask'] = MASK_ADD;
            $ret[$id][NC_PERM_EDIT_ID]['checkbox'] = $e;
            $ret[$id][NC_PERM_EDIT_ID]['mask'] = MASK_EDIT;
            $ret[$id][NC_PERM_SUBSCRIBE_ID]['checkbox'] = $s;
            $ret[$id][NC_PERM_SUBSCRIBE_ID]['mask'] = MASK_SUBSCRIBE;
            $ret[$id][NC_PERM_MODERATE_ID]['checkbox'] = $m;
            $ret[$id][NC_PERM_MODERATE_ID]['mask'] = MASK_MODERATE;
            $ret[$id][NC_PERM_ADMIN_ID]['checkbox'] = $a;
            $ret[$id][NC_PERM_ADMIN_ID]['mask'] = MASK_ADMIN;
            $ret[$id][NC_PERM_COMMENT_ID]['checkbox'] = $c;
            $ret[$id][NC_PERM_COMMENT_ID]['mask'] = MASK_COMMENT;
            $ret[$id][NC_PERM_CHECKED_ID]['checkbox'] = $h;
            $ret[$id][NC_PERM_CHECKED_ID]['mask'] = MASK_CHECKED;
            $ret[$id][NC_PERM_DELETE_ID]['checkbox'] = $l;
            $ret[$id][NC_PERM_DELETE_ID]['mask'] = MASK_DELETE;

            $ret[$id]['title'] = trim($ret[$id]['title']);
        }

        // отсортируем массив с помощью пользовательской функцией cmp
        if (!empty($ret)) {
            uasort($ret, array('self', '_cmp'));
        }
        return $ret;
    }

    public static function DeleteObsoletePerm() {
        global $db;
        $db->query('DELETE FROM `Permission` WHERE `PermissionEnd` < NOW() ');
        $db->query('DELETE FROM `Module_Permission` WHERE `PermissionEnd` < NOW() ');
    }

    /**
     * Получить "имя" права по его id
     *
     * @param int $id
     * @return null|string
     */
    private static function GetPermNameByID($id) {
        switch ($id) {
            case DIRECTOR:
                return CONTROL_USER_RIGHTS_DIRECTOR;
            case SUPERVISOR:
                return CONTROL_USER_RIGHTS_SUPERVISOR;
            case CATALOGUE_ADMIN:
                return CONTROL_USER_RIGHTS_SITEADMIN;
            case SUBDIVISION_ADMIN:
                return CONTROL_USER_RIGHTS_SUBDIVISIONADMIN;
            case SUB_CLASS_ADMIN:
                return CONTROL_USER_RIGHTS_SUBCLASSADMIN;
            case CLASSIFICATOR_ADMIN:
                return CONTROL_USER_RIGHTS_CLASSIFICATORADMIN;
            case GUEST:
                return CONTROL_USER_RIGHTS_GUESTONE;
            case MODERATOR:
                return CONTROL_USER_RIGHTS_MODERATOR;
            case GROUP_MODERATOR:
                return CONTROL_USER_RIGHTS_USER_GROUP;
            case BAN_SITE:
            case BAN_SITE_CONTENT:
                return CONTROL_USER_RIGHTS_SITE;
            case BAN_SUB:
                return CONTROL_USER_RIGHTS_SUB;
            case BAN_CC:
                return CONTROL_USER_RIGHTS_CC;
            case SUBSCRIBER:
                return CONTROL_USER_RIGHTS_SUBSCRIBER;
        }

        return null;
    }

    /**
     * Получить подразделы данного раздела
     *
     * @param array $sub
     * @todo функция такая уже есть, но нельзя подключить, т.к. файл, где она объявлена содержит еще объявление класса,
     * родитель которого объявлен в другом классе
     * @return mixed
     */
    private function _GetChildrenSub($sub) {
        global $db;

        $sub = (array)$sub;

        if (empty($sub)) {
            return null;
        }

        $subdivision_ids = implode(',', array_unique($sub));

        $ret = (array)$db->get_col(
            "SELECT child.`Subdivision_ID`
             FROM `Subdivision` as child, `Subdivision` as parent
             WHERE parent.`Subdivision_ID` IN ({$subdivision_ids})
             AND child.`Hidden_URL` LIKE CONCAT(parent.`Hidden_URL`, '%')
             AND child.`Subdivision_ID` <> parent.`Subdivision_ID`"
        );

        return $ret;
    }

    /**
     * Имя списка по его id
     *
     * @return mixed
     */
    private static function _GetClassificatorNameByID($ClassificatorID) {
        global $db;
        $ClassificatorID = (int)$ClassificatorID;

        return $db->get_var("SELECT Classificator_Name FROM Classificator WHERE Classificator_ID='{$ClassificatorID}'");
    }

    /**
     * Функция, используемая для сравнения
     * При сортировке всех прав пользователя
     *
     * @param  array $a , в нем есть AdminType - по ним и будет производиться сортировка
     * @param  array $b
     * @return int 0, 1, - 1 - если права равны, стоят выше или ниже соответственно
     */
    private static function _cmp($a, $b) {
        // По этому массиву будет выполнять сортировка, т.е.
        // сначала будут все директоры, потом супервизоры....
        static $order_array = array(
            DIRECTOR, SUPERVISOR, SUBSCRIBER, GUEST,
            MODERATOR, GROUP_MODERATOR, CLASSIFICATOR_ADMIN,
            CATALOGUE_ADMIN, SUBDIVISION_ADMIN, SUB_CLASS_ADMIN,
            BAN_SITE, BAN_SITE_CONTENT, BAN_SUB, BAN_CC
        );

        if ($a['AdminType'] == $b['AdminType']) {
            return 0;
        }

        $a_id = null;
        $b_id = null;

        // узнаем, какую позицию занимает AdminType в каждом праве в массиве order_array
        foreach ($order_array as $k => $v) {
            if ($v == $a['AdminType']) {
                $a_id = $k;
            }
            if ($v == $b['AdminType']) {
                $b_id = $k;
            }
        }

        return ($a_id < $b_id) ? -1 : 1;
    }


    public static function get_all_permission_names_by_id($user_id) {
        $all_perm = (array)self::GetAllPermission($user_id);
        $result = array();
        foreach ($all_perm as $perm) {
            $result[] = $perm['title'];
        }

        return $result;
    }

    /**
     * Проверка прав $mask в $this->_catalogue и $this->_catalogueContent
     * с учётом возможности задания прав для всех сайтов (ключ 0 в этих массивах).
     * @param array $source
     * @param int $site_id
     * @param int $mask
     * @return bool
     */
    protected function checkSitePermission($source, $site_id, $mask) {
        // права для всех сайтов
        if (isset($source[0]) && $source[0] & $mask) {
            return true;
        }
        // права для конкретного сайта
        if (isset($source[$site_id]) && $source[$site_id] & $mask) {
            return true;
        }
        return false;
    }

    /**
     * Проверка прав редактора сайта (с правами на любые, в т. ч. сквозные блоки),
     * без учёта прав редактора контента сайта
     * @param int $site_id
     * @param int $mask
     * @return bool
     */
    protected function hasSitePermission($site_id, $mask) {
        return $this->checkSitePermission($this->_catalogue, $site_id, $mask);
    }

    /**
     * Проверка прав редактора сайта и прав редактора контента сайта
     * (т. е. проверка возможности выполнения действия с несквозными блоками)
     * @param int $site_id
     * @param int $mask
     * @return bool
     */
    protected function hasContentPermission($site_id, $mask) {
        return
            $this->checkSitePermission($this->_catalogue, $site_id, $mask) ||
            $this->checkSitePermission($this->_catalogueContent, $site_id, $mask);
    }

    /**
     * Загрузка информации о модулях из БД
     * @param bool $reset
     */
    protected function loadModulePermissions($reset = false) {
        if ($this->modules && !$reset) {
            return;
        }

        $conditions =
            ($this->_UserID ? "`User_ID`='{$this->_UserID}'" : '0') .
            ' OR ' .
            ($this->_PermissionGroupID ? '`PermissionGroup_ID` IN (' . implode(', ', $this->_PermissionGroupID) . ')' : '0') .
            ' AND (`PermissionBegin` IS NULL OR `PermissionBegin` >= NOW())' .
            ' AND (`PermissionEnd` IS NULL OR `PermissionEnd` <= NOW())';

        $data = (array)nc_db()->get_results("SELECT * FROM `Module_Permission` WHERE $conditions", ARRAY_A);

        foreach ($data as $row) {
            $this->modules[$row['Module_Keyword']][$row['Catalogue_ID']][$row['PermissionType']] = $row;
        }
    }

    /**
     * @param string $module_keyword ключевое слово модуля
     *    Специальное ключевое слово  NC_PERM_MODULE_ALL — права на любые действия в любых модулях
     * @param string $permission_type тип действий (тип прав) в модуле
     *    Специальное право (для всех модулей) — NC_PERM_MODULE_ALL — право на любые действия в модуле
     * @param int|null $site_id сайт, на котором выполняется действия (0 — для всех сайтов, null — для какого-нибудь сайта)
     * @return bool
     */
    public function hasModulePermission($module_keyword, $permission_type = NC_PERM_MODULE_ALL, $site_id = null) {
        if ($this->isSupervisor() || $this->isGuest()) {
            return true;
        }

        $this->loadModulePermissions();

        if (empty($this->modules[$module_keyword])) {
            return false;
        }

        if ($site_id === null) {
            foreach ($this->modules[$module_keyword] as $id => $permissions) {
                if ($this->hasModulePermission($module_keyword, $permission_type, $id)) {
                    return true;
                }
            }
            return false;
        }

        $site_id = (int)$site_id;
        return
            !empty($this->modules[$module_keyword][0][NC_PERM_MODULE_ALL]) ||
            !empty($this->modules[$module_keyword][0][$permission_type]) ||
            !empty($this->modules[NC_PERM_MODULE_ALL][0][NC_PERM_MODULE_ALL]) ||
            ($site_id && (
                !empty($this->modules[$module_keyword][$site_id][NC_PERM_MODULE_ALL]) ||
                !empty($this->modules[$module_keyword][$site_id][$permission_type]) ||
                !empty($this->modules[NC_PERM_MODULE_ALL][$site_id][NC_PERM_MODULE_ALL])
            ));
    }

    /**
     * @param string|null $module_keyword
     * @param int|null $site_id (null — любой сайт)
     * @return bool
     */
    public function hasAnyModulePermission($module_keyword = null, $site_id = null) {
        if ($this->isSupervisor() || $this->isGuest()) {
            return true;
        }

        $this->loadModulePermissions();

        if (!$module_keyword && $site_id === null) {
            return !empty($this->modules);
        }

        if ($module_keyword) {
            if ($site_id === null) {
                return !empty($this->modules[$module_keyword]) || !empty($this->modules[NC_PERM_MODULE_ALL]);
            }
            if ($site_id && (!empty($this->modules[$module_keyword][$site_id]) || !empty($this->modules[NC_PERM_MODULE_ALL][$site_id]))) {
                return true;
            }
            return !empty($this->modules[$module_keyword][0]) || !empty($this->modules[NC_PERM_MODULE_ALL][0]);
        }

        // !$module_keyword && $site_id !== null:
        foreach ($this->modules as $row) {
            if (isset($row[$site_id])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Есть какие-нибудь права на управление пользователями?
     * @return bool
     */
    public function isUserModerator() {
        return $this->_director || $this->_supervisor || $this->_user;
    }

    /**
     * Есть какие-нибудь права на управление группами пользователей?
     * @return bool
     */
    public function isGroupModerator() {
        return $this->_director || $this->_supervisor || $this->_group;
    }

    /**
     * @param $message
     */
    protected function halt($message) {
        nc_print_status($message, 'error');
        $GLOBALS['AJAX_SAVER'] = false;
        EndHtml();
        exit;
    }

    /**
     * @param Exception $e
     * @return void
     */
    protected function triggerError(Exception $e) {
        $message = $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getFile();
        trigger_error($message, E_USER_WARNING);
    }
    
}
