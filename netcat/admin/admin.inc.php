<?php

/**
 * Функции и классы, используемые в административном интерфейсе
 *  (netcat/admin, netcat - режим редактирования).
 */

/**
 * Конвертирует массив в JSON
 * @param array
 * @return string
 */
function nc_array_json($array) {
    return json_safe_encode($array);
}

/**
 * Вывод системного сообщения
 *
 * @param string $message системное сообщение
 * @param string $status тип системного сообщения
 * @param array $array массив параметров системного сообщения
 *
 * @return bool всегда возвращает true
 */
function nc_print_status($message, $status = "ok", $array = null, $quiet = false) {
    global $isNaked;

    // small hack, prevent nested format
    if (strstr($message, "id='statusMessage'")) {
        if ($quiet) return $message;
        echo $message;
        return;
    }

    if ($quiet) ob_start();

    if (is_array($array)) {
        $message = vsprintf($message, $array);
    }

    $status_colors = array(
        'error' => 'nc--red',
        'info' => 'nc--blue',
        'ok' => 'nc--green',
        'warning' => 'nc--yellow',
        'success' => 'nc--green',
    );
    $status_icons = array(
        'error' => 'nc--status-error',
        'info' => 'nc--status-info',
        'ok' => 'nc--status-success',
        'warning' => 'nc--status-warning',
        'success' => 'nc--status-success',
    );
    $color_class = isset($status_colors[$status]) ? $status_colors[$status] : '';

    if ($isNaked) {
        if ($status == 'error') {
            ob_clean();
            ?>
            <div id='nc_modal_status'>
                <div id='nc_error'>
                    <?= $message ?>
                </div>
            </div>
            <?php
            exit;
        }
    } else {
        //TODO: По возможности убрать  id='statusMessage'
        ?>
        <div id='statusMessage' class='nc-alert <?= $color_class ?>'>
            <i class='nc-icon-l <?= $status_icons[$status] ?>'></i>
            <div class='nc-alert--message'><?= $message ?></div>
        </div>
    <?php
    }

    return $quiet ? ob_get_clean() : true;
}

/**
 * Класс, выводящий состояние интерфейса для внешнего фрейма в виде json
 */
class ui_config {

    /**
     * описание см. mainView.currentSettings
     */
    public $headerText;
    public $subheaderText;
    public $tabs = array();
    public $toolbar = array();
    public $actionButtons = array();
    public $activeTab;
    public $activeToolbarButtons;
    public $treeMode;
    public $treeSelectedNode;
    public $treeChanges;
    public $locationHash;
    public $remind = array();
    public $addNavBarCatalogue;
    public $removeNavBarCatalogue;

    /**
     * Конструктор
     * @param array параметры (ключ-значение)
     */
    public function __construct($config_array = array()) {
        foreach ($config_array as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * Вывод настроек в формате json виде <script>parent.mainView.updateSettings()</script>
     */
    public function to_json() {
        $ret = get_object_vars($this);

        // do not export variables which names start with underscore "_":
        foreach ($ret as $k => $v) {
            if ($k[0] == "_") {
                unset($ret[$k]);
            }
        }

        if (!empty($_REQUEST['fs'])) {
            $ret['locationHash'] = str_replace('.', '_fs.', $ret['locationHash']);
            $ret['treeSelectedNode'] .= '&fs=1';
        }

        $ret['tabsCrc'] = crc32(serialize($ret['tabs']));
        $ret['toolbarCrc'] = crc32(serialize($ret['toolbar']));
        $ret['actionButtonsCrc'] = crc32(serialize($ret['actionButtons']));
        $disable_reload = 'var' . mt_rand(1, 65536);
        return "<script>if (parent && parent.mainView) { var $disable_reload; if(!$disable_reload) { $disable_reload = true; parent.mainView.updateSettings(" . nc_array_json($ret) . "); }}</script>\n";
    }

      /**
     * @param $caption
     *
     * @return void
     */
    public function add_submit_button($caption = NETCAT_CUSTOM_ONCE_SAVE) {
        $this->actionButtons[] = array(
            "id" => "submit_form",
            "caption" => $caption,
            "action" => "mainView.submitIframeForm()",
        );
    }

    /**
     * @param string $location
     * @param string|null $module_keyword
     *
     * @return void
     */
    public function add_create_button($location, $module_keyword = null) {
        $this->actionButtons[] = array(
            "id" => "add",
            "caption" => NETCAT_MODERATION_ADD_BUTTON,
            "location" => "#module.$module_keyword.$location",
            "align" => "left",
        );
    }

    /**
     * @param $save_button_caption
     *
     * @return void
     */
    public function add_save_and_cancel_buttons($save_button_caption = NETCAT_CUSTOM_ONCE_SAVE) {
        $this->add_back_button();
        $this->add_submit_button($save_button_caption);
    }

    /**
     * @param $caption
     *
     * @return void
     */
    public function add_back_button($caption = TOOLS_REDIRECT_BACK) {
        $this->actionButtons[] = array(
            "id" => "history_back",
            "caption" => $caption,
            "action" => "history.back(1)",
            "align" => "left",
        );
    }

    /**
     * @param string $id
     * @param string $caption
     * @param string $action
     * @param string $align
     * @param bool $red_border
     *
     * @return void
     */
    public function add_button($id, $caption, $action, $align = "left", $red_border = false) {
        $this->actionButtons[] = array(
            "id" => $id,
            "caption" => $caption,
            "action" => $action,
            "align" => $align,
            "red_border" => $red_border,
        );
    }

    /**
     * @return void
     */
    public function clear_action_buttons() {
        $this->actionButtons = array();
    }

    /**
     * @param string $hash
     * @param string|null $module_keyword
     *
     * @return void
     */
    public function set_location_hash($hash, $module_keyword = null) {
        $this->locationHash = "module.$module_keyword.$hash";
    }

}

// of class ui_config

/**
 * для совместной работы с ui_config_subdivision, ...
 */
class ui_subdivision_data {

    public $label_color;
    public $catalogue_id;
    public $subdivision_id;
    public $subdivision_name;
    public $subdivision_url;
    public $subdivision_checked;

    // private
    var $subclasses = array();
    var $moderated_subclasses = array();
    var $administered_subclasses = array();
    var $is_subdivision_admin = false;

    /**
     * загрузить данные о разделе по id раздела
     * @param integer
     */
    function fetch_by_subdivision_id($sub_id) {
        settype($sub_id, 'integer');
        if (!$sub_id) return;

        $qry = "SELECT sc.Sub_Class_ID,
                   sc.Sub_Class_Name,
                   sc.Subdivision_ID,
                   sc.Class_ID,
                   sd.Subdivision_Name,
                   sd.Catalogue_ID,
                   sd.Hidden_URL,
                   sd.Checked,
                   sd.LabelColor
              FROM Sub_Class as sc,
                   Subdivision as sd
             WHERE sc.Subdivision_ID = $sub_id
               AND sc.Subdivision_ID = sd.Subdivision_ID
               AND sc.Class_ID != 0
             ORDER BY sc.Priority";
        $this->fetch_by_query($qry);

        // если нет шаблонов в разделе
        if (!$this->subdivision_id) {
            global $db;
            $sub_data = $db->get_row("SELECT Subdivision_ID, Subdivision_Name, Catalogue_ID, Hidden_URL, Checked, LabelColor
                                  FROM Subdivision
                                 WHERE Subdivision_ID = $sub_id", ARRAY_A);

            $this->catalogue_id = $sub_data['Catalogue_ID'];
            $this->subdivision_id = $sub_data['Subdivision_ID'];
            $this->subdivision_name = $sub_data['Subdivision_Name'];
            $this->subdivision_checked = $sub_data['Checked'];
            $this->label_color = $sub_data['LabelColor'];

            if (nc_module_check_by_keyword('routing')) {
                $this->subdivision_url = (string)nc_routing::get_folder_path($sub_id);
            } else {
                $this->subdivision_url = nc_core('SUB_FOLDER') . $sub_data['Hidden_URL'];
            }

            $this->check_rights();
        }
    }

    /**
     * загрузить данные о разделе по id шаблона в разделе
     * @param integer
     */
    function fetch_by_subclass_id($cc_id) {
        settype($cc_id, 'integer');
        $qry = "SELECT sc.Sub_Class_ID,
                   sc.Sub_Class_Name,
                   sc.Subdivision_ID,
                   sc.Class_ID,
                   sd.Subdivision_Name,
                   sd.Catalogue_ID,
                   sd.Hidden_URL,
                   sd.Checked,
                   sd.LabelColor
              FROM Sub_Class as sc,
                   Sub_Class as ref,
                   Subdivision as sd
             WHERE ref.Sub_Class_ID = $cc_id
               AND ref.Subdivision_ID = sc.Subdivision_ID
               AND sc.Subdivision_ID = sd.Subdivision_ID
               AND sc.Class_ID != 0
             ORDER BY sc.Priority";
        $this->fetch_by_query($qry);
    }

    /**
     * 'собственно функция для получения нужной информации'
     * @access private
     */
    function fetch_by_query($qry) {

        global $db;
        $this->subclasses = $db->get_results($qry, ARRAY_A);

        if (!empty($this->subclasses[0])) {
            $this->catalogue_id = $this->subclasses[0]['Catalogue_ID'];
            $this->subdivision_id = $this->subclasses[0]['Subdivision_ID'];
            $this->subdivision_name = $this->subclasses[0]['Subdivision_Name'];
            $this->subdivision_checked = $this->subclasses[0]['Checked'];
            $this->label_color = $this->subclasses[0]['LabelColor'];

            if (nc_module_check_by_keyword('routing')) {
                $this->subdivision_url = nc_routing::get_folder_path($this->subclasses[0]['Subdivision_ID']);
            } else {
                $this->subdivision_url = nc_core('SUB_FOLDER') . $this->subclasses[0]['Hidden_URL'];
            }

            $this->check_rights();
        }
    }

  /**
   * Fills $administered_subclasses, $moderated_subclasses
   *
   * @access private
   * @throws Exception
   */
    function check_rights() {

        global $AUTH_USER_ID;
        /**
         * @var Permission $perm
         */
        global $perm;

        if (!$AUTH_USER_ID) {
            Refuse();
            exit;
        }

        // has rights for everything in the sub
        $user_is_cool = $perm->isAllSiteAdmin() || $perm->isGuest();

        if ($user_is_cool) {
            $this->moderated_subclasses = & $this->subclasses;
            $this->administered_subclasses = & $this->subclasses;
            $this->is_subdivision_admin = true;
            return true;
        }

        if ($perm->isSubdivisionAdmin($this->subdivision_id)) {
            // $this->administered_subclasses = &$this->subclasses;
            $this->is_subdivision_admin = true;
        }

        if (!$this->subclasses) {
            return;
        }

        // subclass admin / moderator
        foreach ($this->subclasses as $idx => $sc) {
            if ($perm->isSubClassAdmin($sc['Sub_Class_ID'])) {
                $this->administered_subclasses[] = & $this->subclasses[$idx];
                //$this->moderated_subclasses[] = &$this->subclasses[$idx];
            }
            if ($perm->isSubClass($sc['Sub_Class_ID'], MASK_MODERATE)) {
                $this->moderated_subclasses[] = & $this->subclasses[$idx];
            }
        }

        // subdivision moderator
        $user_is_moderator = (
            $perm->isCatalogue($this->catalogue_id, MASK_MODERATE) ||
            $perm->isSubdivision($this->subdivision_id, MASK_MODERATE)
        );

        if ($user_is_moderator) {
            $this->moderated_subclasses = & $this->subclasses;
        }
    }

    /**
     * является ли пользователь администратором данного раздела
     * @return boolean
     */
    function is_subdivision_admin() {
        return $this->is_subdivision_admin;
    }

    /**
     * является ли пользователь модератором шаблона $cc_id или если $cc_id не задан
     * является ли пользователь модератором хотя бы одного шаблона в разделе
     * @access public
     * @return boolean
     */
    function is_subclass_moderator($cc_id = 0) {
        if (!$cc_id) {
            return (bool)$this->moderated_subclasses;
        }

        foreach ((array)$this->moderated_subclasses as $sc) {
            if ($sc["Sub_Class_ID"] == $cc_id) return true;
        }
        return false;
    }

    /**
     * является ли пользователь администратором [$cc_id|хотя бы одного] шаблона в разделе
     * @access public
     * @return boolean
     */
    function is_subclass_admin($cc_id = 0) {
        return (bool)$this->administered_subclasses;
    }

    /**
     * массив с информацией о всех шаблонах-в-разделе, которые пользователь
     * может модерировать (в т.ч. те, которые может администрировать)
     * @access public
     * @return array
     */
    function get_moderated_subclasses() {
        return $this->moderated_subclasses;
    }

    /**
     * массив с информацией о всех шаблонах-в-разделе, которые пользователь
     * может администрировать
     * @access public
     * @return array
     */
    function get_administered_subclasses() {
        return $this->administered_subclasses;
    }

    /**
     * получить id первого шаблона в разделе, который юзер администрирует
     */
    function get_first_administered_subclass_id() {
        return $this->administered_subclasses[0]['Sub_Class_ID'];
    }

    /**
     * получить id первого шаблона в разделе, который юзер модерирует
     */
    function get_first_moderated_subclass_id() {
        return nc_array_value(nc_array_value($this->moderated_subclasses,0), 'Sub_Class_ID');
    }

}

/**
 * общий класс для работы с разделом, шаблонами и объектами в нем
 * отвечает за то, чтобы создать правильный (соответствующий
 * полномочиям пользователя) набор вкладок
 */
class ui_config_subdivision_generic extends ui_config {

    /**
     * объект с информацией о разделе и шаблонах в нем (ui_subdivision_data)
     */
    var $sub;

    /**
     * @param integer
     * @param integer
     */
    function init($sub_id, $cc_id = 0, $message_id = 0) {
        /** @var Permission $perm */
        global $perm;
        $this->sub = new ui_subdivision_data();
        if ($sub_id) {
            $this->sub->fetch_by_subdivision_id($sub_id);
        } elseif ($cc_id) {
            $this->sub->fetch_by_subclass_id($cc_id);
        } else {
            trigger_error("Wrong parameters for ui_config_subdivision_generic::init", E_USER_ERROR);
        }

        $this->headerText = $this->sub->subdivision_name;

        // определим набор вкладок
        $this->tabs = array();


        // 2. Настройки - только админ раздеа и выше
        if ($this->sub->is_subdivision_admin()) {
            $this->tabs[] = array('id' => 'settings',
                'caption' => STRUCTURE_TAB_SETTINGS,
                'location' => "subdivision.design({$this->sub->subdivision_id})"
            );
        }


        // 3. Используемые компонеты. Админ раздела видит все. Админ сс - только свои.
        if ($this->sub->is_subdivision_admin()) {
            $loc = ($this->sub->subclasses) ? "subclass.list({$this->sub->subdivision_id})" : "subclass.add({$this->sub->subdivision_id})";
            $this->tabs[] = array('id' => 'subclass',
                'caption' => STRUCTURE_TAB_USED_SUBCLASSES,
                'location' => $loc
            );
        } else if ($this->sub->is_subclass_admin()) { // admin только сабкласса
            $active_cc_id = $this->sub->get_first_administered_subclass_id();
            $this->tabs[] = array('id' => 'subclass',
                'caption' => STRUCTURE_TAB_USED_SUBCLASSES,
                'location' => "subclass.edit(" . $active_cc_id . ", " . $this->sub->subdivision_id . ")"
            );
        }

        // 1. Информация - только админ раздела и выше
        if ($this->sub->is_subdivision_admin()) {
            $this->tabs[] = array('id' => 'info',
                'caption' => STRUCTURE_TAB_INFO,
                'location' => "subdivision.info({$this->sub->subdivision_id})"
            );
        }

        // 4. Редактирование и удаленные объекты
        $active_cc_id = $this->sub->get_first_moderated_subclass_id();
        //print $active_cc_id;
        if ($active_cc_id) {
            $this->tabs[] = array('id' => 'objects',
                'caption' => STRUCTURE_TAB_EDIT,
                'location' => "object.list($active_cc_id)"
            );
        }

        $this->tabs[] = array('id' => 'trashed_objects',
            'caption' => NETCAT_MODERATION_TRASHED_OBJECTS,
            'location' => "subdivision.trashed_objects({$this->sub->subdivision_id})"
        );

        $show_versions =
            nc_core::get_object()->get_settings('StoreVersions', 'system', false, $this->sub->catalogue_id) &&
            $perm->isAccess(NC_PERM_ITEM_SUB, NC_PERM_ACTION_ADMIN, $this->sub->subdivision_id);

        if ($show_versions) {
            $this->tabs[] = array('id' => 'versions',
                'caption' => NETCAT_VERSION_TAB_NAME,
                'location' => "subdivision.versions({$this->sub->subdivision_id})"
            );
        }

        // 5. Просмотр
        $this->tabs[] = array('id' => 'view',
            'caption' => STRUCTURE_TAB_PREVIEW,
            'action' => "window.open('".nc_folder_url($this->sub->subdivision_id)."');"
        );

        // Прочие общие настройки
        $this->headerText = $this->sub->subdivision_name;
        $this->treeMode = 'sitemap';
        $this->treeSelectedNode = "sub-" . $this->sub->subdivision_id;
    }

}

class ui_config_objects extends ui_config_subdivision_generic {

    public function __construct($cc_id, $message_id = null) {
        $this->init(0, $cc_id);
        if (!$cc_id) {
            trigger_error("Wrong cc_id in ui_config_objects::ui_config_objects", E_USER_ERROR);
        }

        $this->activeTab = 'objects';

        $this->tabs[] = array(
            'id' => 'objects_settings',
            'caption' => '',
            'pull_right' => true,
            'sprite' => 'dev-templates',
            'location' => "object.switch_view($cc_id)"
        );

        // кнопки
        $subclasses = $this->sub->get_moderated_subclasses();

        if (is_array($subclasses) && count($subclasses) > 1) {
            $enable_subclass_drag = $this->sub->is_subdivision_admin();
            foreach ($subclasses as $sc) {
                $this->toolbar[] = array('id' => "subclass$sc[Class_ID]-$sc[Sub_Class_ID]",
                    'caption' => $sc["Sub_Class_Name"],
                    'location' => "object.list($sc[Sub_Class_ID])",
                    'group' => "grp1",
                    'acceptDropFn' => 'subclassAcceptDrop',
                    'onDropFn' => 'subclassOnDrop',
                    'dragEnabled' => $enable_subclass_drag,
                    'metadata' => array("subdivisionId" => $sc['Subdivision_ID'])
                );
                $cc_subs[$sc["Sub_Class_ID"]] = $sc["Class_ID"];
            }

            $this->activeToolbarButtons[] = "subclass" . nc_array_value($cc_subs, $cc_id) . "-$cc_id";
        }
    }

    public function replace_view_link_url($url) {
        foreach ((array)$this->tabs as $index => $tab) {
            if ($tab['id'] == 'view') {
                $catalogue = nc_Core::get_object()->catalogue;
                $this->tabs[$index]['action'] = "window.open('" . $catalogue->get_url_by_id($this->sub->catalogue_id) . $url . "');";
                break;
            }
        }
    }

}

/**
 * Инструменты
 */
class ui_config_tool extends ui_config {

    /**
     * @param string имя тулзы
     * @param string файл с картинкой для заголовка
     * @param string адрес (#хэш)
     */
    public function __construct($name, $caption, $big_icon, $location) {
        $this->headerText = $name;
        $this->tabs = array(array("id" => "tool", "caption" => $caption, "location" => $location));
        $this->activeTab = "tool";
        $this->locationHash = $location;
        $this->treeMode = 'sitemap';
    }

}

class ui_config_trash extends ui_config {

    /**
     * @param string name - текст над вкладками
     * @param string page - активная вкладка
     * @param string caption1 текст первой вкладки
     * @param string caption2 текст второй вкладки
     */
    public function __construct($name, $page, $caption1) {
        $this->headerText = $name;
        $this->tabs = array(
            array(
                "id" => "trashlist",
                "caption" => $caption1,
                "location" => '#trash.list'));
        $this->activeTab = $page == 'settings' ? "trashsettings" : "trashlist";
        $this->locationHash = '#trash.' . ($page == 'settings' ? "settings" : "list");
        $this->treeMode = 'sitemap';
    }

}

/**
 * Перемещение объекта из одного шаблона в разделе в другой.
 *
 * Пользователь должен обладать правами: изменение в разделе, где
 * находится объект, и удаление в разделе, куда переносится объект.
 *
 * @param integer $class_id ID класса объекта
 * @param integer $message_id ID объекта
 * @param integer $destination_cc_id ID шаблона в разделе, куда переносится объект
 * @return boolean
 */
function nc_move_message($class_id, $message_id, $destination_cc_id) {
    $nc_core = nc_core::get_object();

    $class_id = (int)$class_id;
    $message_id = (int)$message_id;
    $destination_cc_id = (int)$destination_cc_id;

    if (!$class_id || !$message_id || !$destination_cc_id) {
        trigger_error("Wrong parameters for nc_move_message()", E_USER_WARNING);
        return false;
    }

    $db = $nc_core->db;

    // перемещаемое сообщение
    $message = $db->get_row("SELECT sd.`Catalogue_ID`,
                                  m.*
                             FROM `Message" . $class_id . "` AS m,
                                  `Subdivision` AS sd
                            WHERE m.`Message_ID`='" . $message_id . "'
                              AND m.`Subdivision_ID`=sd.`Subdivision_ID` ", ARRAY_A);

    // сабкласс назначения
    $dest_subclass = $db->get_row("SELECT sd.`Catalogue_ID`,
                                        sc.`Sub_Class_ID`,
                                        sc.`Subdivision_ID`,
                                        sc.`Class_ID`,
                                        IFNULL(MAX(m.`Priority`)+1,1) AS Next_Priority
                                   FROM (`Sub_Class` AS sc,
                                        `Subdivision` AS sd)
                                        LEFT JOIN `Message" . $class_id . "` AS m
                                          ON m.`Sub_Class_ID`=sc.`Sub_Class_ID`
                                  WHERE sc.`Sub_Class_ID`='" . $destination_cc_id . "'
                                    AND sc.`Subdivision_ID`=sd.`Subdivision_ID`
                                  GROUP BY m.`Sub_Class_ID` ", ARRAY_A);

    // существует ли объект и компонент в разделе
    if (!$message || !$dest_subclass) {
        $what = ($message ? 'subclass' : 'object');
        trigger_error("nc_move_message: $what doesn't exist", E_USER_WARNING);
        return false;
    }

    // перемещать можно только в рамках одного компонента
    if ($dest_subclass['Class_ID'] != $class_id) {
        trigger_error("nc_move_message: destination subclass belongs to different class", E_USER_WARNING);
        return false;
    }

    // перенос в самого себя
    if ($dest_subclass['Sub_Class_ID'] == $message['Sub_Class_ID']) {
        return true;
    }

    // права
    /** @var $perm Permission */
    global $perm;
    $has_rights = ($perm->isSubClass($message['Sub_Class_ID'], MASK_ADMIN | MASK_MODERATE) && $perm->isSubClass($dest_subclass['Sub_Class_ID'], MASK_ADMIN | MASK_MODERATE));
    if (!$has_rights) {
        trigger_error("nc_move_message: insufficient rights", E_USER_WARNING);
        return false;
    }

    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_OBJECT_UPDATED, $dest_subclass['Catalogue_ID'], $dest_subclass['Subdivision_ID'],
        $dest_subclass['Sub_Class_ID'], $dest_subclass['Class_ID'], $message_id);

    // файлы
    $file_fields = $nc_core->get_component($class_id)->get_fields(NC_FIELDTYPE_FILE);
    foreach ($file_fields as $field) {
        $file = $nc_core->file_info->get_file_info($class_id, $message_id, $field['name'], false);
        if (!isset($file['url'])) { // файла нет
            continue;
        }

        if ($file['fs_type'] == NC_FS_SIMPLE) { // NC_FS_SIMPLE не требует перемещения файла
            continue;
        }

        // Создание папки, если она не существует
        $dest_folder = $nc_core->FILES_FOLDER . $dest_subclass['Subdivision_ID'] . '/' . $dest_subclass['Sub_Class_ID'] . '/';
        if (!is_dir($dest_folder)) {
            if (!mkdir($dest_folder, $nc_core->DIRCHMOD, true)) {
                return false;
            }
        }

        $new_file_name = null;

        if ($file['fs_type'] == NC_FS_PROTECTED) { // «защищённая файловая система»
            // Имя файла в новой папке
            $new_file_name = basename($file['url']);
            $new_file_path = $dest_folder . $new_file_name;
            while (file_exists($new_file_path)) {
                $new_file_path++;
            }

            // Перемещение файла
            if (!rename($nc_core->DOCUMENT_ROOT . $file['url'], $new_file_path)) {
                continue;
            }

            // Обновление значения поля в БД
            $db->query(
                "UPDATE `Filetable` 
                    SET `Virt_Name` = '" . $db->escape(basename($new_file_path)) . "',
                        `File_Path` = '/$dest_subclass[Subdivision_ID]/$dest_subclass[Sub_Class_ID]/' 
                  WHERE `Message_ID` = $message_id
                    AND `Field_ID` = $field[id]"
            );
        }
        else if ($file['fs_type'] == NC_FS_ORIGINAL) { // «стандартная файловая система»
            // Имя файла в новой папке
            $new_file_name = nc_get_filename_for_original_fs($file['name'], $dest_folder);

            // Перемещение файла
            $old_file_path = $nc_core->DOCUMENT_ROOT . $file['url'];
            $new_file_path = $dest_folder . $new_file_name;
            if (!rename($old_file_path, $new_file_path)) {
                continue;
            }

            // Обновление значения поля в БД
            // OriginalName.jpg:image/jpeg:123456:u/OriginalName.jpg
            $file_db_values = explode(':', $message[$field['name']]);
            $file_db_values[3] = $dest_subclass['Subdivision_ID'] . '/' . $dest_subclass['Sub_Class_ID'] . '/' . $new_file_name;
            $file_db_string = join(':', $file_db_values);

            $db->query(
                "UPDATE `Message" . $class_id . "`
                    SET `" . $field['name'] . "` = '" . $db->escape($file_db_string) . "' 
                  WHERE `Message_ID` = '" . $message_id . "'"
            );
        }

        // Перемещение preview
        if (file_exists($nc_core->DOCUMENT_ROOT . $file['preview_url']) && $new_file_name) {
            rename($nc_core->DOCUMENT_ROOT . $file['preview_url'], $dest_folder . 'preview_' . $new_file_name);
        }
    }

    // собственно перемещение
    $db->query("UPDATE Message{$class_id}
                 SET Subdivision_ID = $dest_subclass[Subdivision_ID],
                     Sub_Class_ID = $dest_subclass[Sub_Class_ID],
                     Priority = $dest_subclass[Next_Priority]
               WHERE Message_ID = {$message_id}");

    // обновление приоритетов
    $db->query("UPDATE Message{$class_id}
                 SET Created=Created, LastUpdated=LastUpdated,
                     Priority = Priority-1
               WHERE Sub_Class_ID = $message[Sub_Class_ID]
                 AND Priority > $message[Priority]");

    //перемещение комментариев объекта
    if ($nc_core->modules->get_by_keyword('comments')) {
        $db->query("UPDATE `Comments_Text`
                     SET `Sub_Class_ID` = $dest_subclass[Sub_Class_ID]
                   WHERE `Message_ID` = {$message_id}
                     AND `Sub_Class_ID` = $message[Sub_Class_ID]");

        $db->query("UPDATE `Comments_Count`
                     SET `Sub_Class_ID` = $dest_subclass[Sub_Class_ID]
                   WHERE `Message_ID` = {$message_id}
                     AND `Sub_Class_ID`= $message[Sub_Class_ID]");
    }

    // execute core action
    $nc_core->event->execute(nc_Event::AFTER_OBJECT_UPDATED, $dest_subclass['Catalogue_ID'], $dest_subclass['Subdivision_ID'],
        $dest_subclass['Sub_Class_ID'], $dest_subclass['Class_ID'], $message_id);

    // перемещение детей
    $childs_id = $db->get_col("SELECT `Message_ID` FROM `Message" . $class_id . "` WHERE `Parent_Message_ID` = '" . $message_id . "' ");

    if (!empty($childs_id)) {
        foreach ($childs_id as $child_id) {
            nc_move_message($class_id, $child_id, $destination_cc_id);
        }
    }

    return true;
}

/**
 * Копирование объекта из одного инфоблока в другой.
 *
 * Пользователь должен обладать правами: изменение в разделе, где
 * находится объект, и удаление в разделе, куда переносится объект.
 *
 * @param integer ID класса объекта
 * @param integer ID объекта
 * @param integer ID шаблона в разделе, куда переносится объект
 * @param array карта соответствий полей, если различаются компоненты (поле-источник => поле-приёмник)
 * @param int ID компонента, в который нужно скопировать объект (обязательно при копировании в инфоблок другого компонента)
 * @return int|boolean
 */
function nc_copy_message($class_id, $message_id, $destination_cc_id, $fields_map = array(), $destination_class_id = 0) {
    try {

        $nc_core = nc_core::get_object();

        $class_id = (int)$class_id;
        $message_id = (int)$message_id;
        $destination_cc_id = (int)$destination_cc_id;

        if (!$class_id || !$message_id || !$destination_cc_id) {
            throw new Exception('Wrong parameters');
        }

        /** @var Permission $perm */
        global $perm;
        // Пользователь должен обладать правами: чтение в инфоблоке, где находится объект,
        // и добавление в инфоблоке, куда переносится объект.
        $has_rights =
            $perm->isSubClass($nc_core->message->get_by_id($class_id, $message_id, 'Sub_Class_ID'), MASK_READ) &&
            $perm->isSubClass($destination_cc_id, MASK_ADMIN | MASK_MODERATE);

        if (!$has_rights) {
            throw new Exception('Insufficient rights');
        }

        if ($destination_class_id && $class_id != $destination_class_id) {
            return $nc_core->message->duplicate_to_another_component($class_id, $message_id, $destination_cc_id, (array)$fields_map);
        }

        return $nc_core->message->duplicate($class_id, $message_id, $destination_cc_id);

    } catch (Exception $e) {
        trigger_error("nc_copy_message(): {$e->getMessage()}", E_USER_WARNING);
        return false;
    }
}

/**
 * Перемещение шаблона-в-разделе в другой раздел
 */
function nc_move_subclass($subclass_id, $dest_sub_id) {
    global $db, $perm, $nc_core;

    $subclass_id = intval($subclass_id);
    $dest_sub_id = intval($dest_sub_id);

    $subclass = $db->get_row("SELECT Class_ID, Catalogue_ID, Subdivision_ID, Sub_Class_ID, Priority FROM Sub_Class WHERE Sub_Class_ID = $subclass_id", ARRAY_A);
    $subdivision = $db->get_row("SELECT Catalogue_ID, Subdivision_ID FROM Subdivision WHERE Subdivision_ID = $dest_sub_id", ARRAY_A);

    if (!$subclass || !$subdivision) {
        trigger_error("nc_move_subclass: wrong parameters (can't get data)", E_USER_WARNING);
        return false;
    }

    $has_rights = false;

    $has_rights = $perm->isSubdivisionAdmin($dest_sub_id);

    if (!$has_rights) {
        trigger_error("nc_move_subclass: insufficient rights", E_USER_WARNING);
        return false;
    }

    if ($subclass['Subdivision_ID'] == $dest_sub_id) {
        return false;
    }

    // move subdivision
    $next_priority = $db->get_var("SELECT IFNULL(MAX(Priority)+1,1)
                                   FROM Sub_Class
                                  WHERE Subdivision_ID = $dest_sub_id");

    // execute core action
    $nc_core->event->execute(nc_Event::BEFORE_INFOBLOCK_UPDATED, $subdivision['Catalogue_ID'], $subdivision['Subdivision_ID'], $subclass_id);

    $db->query("UPDATE Sub_Class
                 SET Subdivision_ID=$dest_sub_id, Priority=$next_priority
               WHERE Sub_Class_ID=$subclass_id");

    // close gap priority at old subdivision
    $db->query("UPDATE Sub_Class
                 SET Priority=Priority-1
               WHERE Subdivision_ID = $subclass[Subdivision_ID]
                 AND Priority > $subclass[Priority]");

    // move 'messages'
    $db->query("UPDATE Message{$subclass[Class_ID]}
                 SET Subdivision_ID = $dest_sub_id
               WHERE Sub_Class_ID = $subclass_id");

    // move files
    $has_files = $db->get_var("SELECT COUNT(*)
                               FROM Filetable
                              WHERE File_Path = '/$subclass[Subdivision_ID]/$subclass[Sub_Class_ID]/'");
    if ($has_files) {
        // prepare dirs
        global $FILES_FOLDER, $DIRCHMOD;
        @mkdir($FILES_FOLDER . "$dest_sub_id", $DIRCHMOD);

        // move dir
        $old_path = $FILES_FOLDER . "$subclass[Subdivision_ID]/$subclass[Sub_Class_ID]";
        $new_path = $FILES_FOLDER . "$dest_sub_id/$subclass_id";

        if (file_exists($old_path)) {
            rename($old_path, $new_path);
        }

        $db->query("UPDATE Filetable
                   SET File_Path='/$dest_sub_id/$subclass_id/'
                 WHERE File_Path='/$subclass[Subdivision_ID]/$subclass[Sub_Class_ID]/'");
    }

    // execute core action
    $nc_core->event->execute(nc_Event::AFTER_INFOBLOCK_UPDATED, $subdivision['Catalogue_ID'], $subdivision['Subdivision_ID'], $subclass_id);

    if ($subclass['Catalogue_ID'] != $subdivision['Catalogue_ID']) {
        nc_tpl_mixin_cache::delete_block_files($subclass['Catalogue_ID'], $subclass_id);
    }
    nc_tpl_mixin_cache::generate_block_files($subclass_id, true);

    return true;
}

/**
 * Отображение данных(функций, переменных) для вставки в textarea формы
 * @param string $type тип формы (template || class)
 * @param string $window окно для вставки данных, например window или opener
 * @param string $form идентификатор формы
 * @param string $textarea идентификатор textarea
 */
function nc_form_data_insert($type, $window, $form, $textarea) {
    global $db;
//  global $class_id, $system_class_id;

    $class_id = intval($_GET['classid']);
    $system_class_id = intval($_GET['systemclassid']);

    $window = htmlspecialchars($window, ENT_QUOTES);
    $form = htmlspecialchars($form, ENT_QUOTES);
    $textarea = htmlspecialchars($textarea, ENT_QUOTES);

    switch ($type) {
        case "class":
            if (!$class_id && !$system_class_id) return;
            if ($textarea == 'PageBody' || $textarea == 'ListBody') {
                // все поля компонента
                $class_fields = $db->get_results("SELECT `Field_Name`, `Description`, `TypeOfData_ID` as `type`
                                            FROM `Field`
                                            WHERE " . ($class_id ?
                        "`Class_ID` = '" . $class_id . "'" :
                        "`System_Table_ID` = '" . $system_class_id . "'") . "
                                            ORDER BY `Priority`", ARRAY_A);


                if (!empty($class_fields)) {
                    // Поля компонента
                    echo "<table class='InsertDataTable'>\n";
                    echo "<tr><td colspan='2'><b>" . NETCAT_HINT_COMPONENT_FIELD . "</b></td></tr>";
                    foreach ($class_fields as $class_field) {
                        echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "</a>
                    </td><td>" . $class_field['Description'] . "</td></tr>\n";

                        if ($class_field['type'] == NC_FIELDTYPE_FILE) {
                            echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "_size</a>
                    </td><td>" . NETCAT_HINT_COMPONENT_SIZE . " " . $class_field['Description'] . "</td></tr>\n";
                            echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "_type</a>
                    </td><td>" . NETCAT_HINT_COMPONENT_TYPE . " " . $class_field['Description'] . "</td></tr>\n";
                        }

                        if ($class_field['type'] == NC_FIELDTYPE_SELECT || $class_field['type'] == NC_FIELDTYPE_MULTISELECT) {
                            echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "_id</a>
                    </td><td>" . NETCAT_HINT_COMPONENT_ID . " " . $class_field['Description'] . "</td></tr>\n";
                        }

                        if ($class_field['type'] == NC_FIELDTYPE_DATETIME) {
                            echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "_day</a>
                    </td><td>" . NETCAT_HINT_COMPONENT_DAY . " " . $class_field['Description'] . "</td></tr>\n";
                            echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "_month</a>
                    </td><td>" . NETCAT_HINT_COMPONENT_MONTH . " " . $class_field['Description'] . "</td></tr>\n";
                            echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "_year</a>
                    </td><td>" . NETCAT_HINT_COMPONENT_YEAR . " " . $class_field['Description'] . "</td></tr>\n";
                            echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "_hours</a>
                    </td><td>" . NETCAT_HINT_COMPONENT_HOUR . " " . $class_field['Description'] . "</td></tr>\n";
                            echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "_minutes</a>
                    </td><td>" . NETCAT_HINT_COMPONENT_MINUTE . " " . $class_field['Description'] . "</td></tr>\n";
                            echo "<tr><td>
                      <a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_" . $class_field['Field_Name'] . "_seconds</a>
                    </td><td>" . NETCAT_HINT_COMPONENT_SECONDS . " " . $class_field['Description'] . "</td></tr>\n";
                        }
                    }
                    echo "</table>\n<br><br>\n";
                }


                // Переменные, содержащие свойства текущего объекта
                echo "<table class='InsertDataTable'>\n";
                echo "<tr><td colspan='2'><b>" . NETCAT_HINT_OBJECT_PARAMS . "</b></td></tr>";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_Created</a></td><td>" . NETCAT_HINT_CREATED_DESC . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_LastUpdated</a></td><td>" . NETCAT_HINT_LASTUPDATED_DESC . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_RowID</a></td><td>" . NETCAT_HINT_MESSAGE_ID . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_UserID</a></td><td>" . NETCAT_HINT_USER_ID . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_Checked</a></td><td>" . NETCAT_HINT_CHECKED . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_IP</a></td><td>" . NETCAT_HINT_IP . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_UserAgent</a></td><td>" . NETCAT_HINT_USER_AGENT . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_LastUserID</a></td><td>" . NETCAT_HINT_LAST_USER_ID . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_LastIP</a></td><td>" . NETCAT_HINT_LAST_USER_IP . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_LastUserAgent</a></td><td>" . NETCAT_HINT_LAST_USER_AGENT . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_AdminButtons</a></td><td>" . NETCAT_HINT_ADMIN_BUTTONS . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_AdminCommon</a></td><td>" . NETCAT_HINT_ADMIN_COMMONS . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$fullLink</a></td><td>" . NETCAT_HINT_FULL_LINK . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$fullDateLink</a></td><td>" . NETCAT_HINT_FULL_DATE_LINK . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$editLink</a></td><td>" . NETCAT_HINT_EDIT_LINK . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$deleteLink</a></td><td>" . NETCAT_HINT_DELETE_LINK . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$dropLink</a></td><td>" . NETCAT_HINT_DROP_LINK . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$checkedLink</a></td><td>" . NETCAT_HINT_CHECKED_LINK . "</td></tr>\n";
                echo "</table>\n";

                // Переменные, доступные в списке объектов шаблона
                echo "<table class='InsertDataTable'>\n";
                echo "<tr><td colspan='2'><b>" . NETCAT_HINT_VARS_IN_LIST_SCOPE . "</b></td></tr>";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$prevLink</a></td><td>" . NETCAT_HINT_PREV_LINK . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$nextLink</a></td><td>" . NETCAT_HINT_NEXT_LINK . "</td></tr>\n";
                if ($textarea == 'PageBody')
                    echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_RowNum</a></td><td>" . NETCAT_HINT_ROW_NUM . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$recNum</a></td><td>" . NETCAT_HINT_REC_NUM . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$totRows</a></td><td>" . NETCAT_HINT_TOT_ROWS . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$begRow</a></td><td>" . NETCAT_HINT_BEG_ROW . "</td></tr>\n";
                echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$endRow</a></td><td>" . NETCAT_HINT_END_ROW . "</td></tr>\n";
                echo "</table>\n";
            }

            // Переменные, доступные во всех полях шаблона
            echo "<table class='InsertDataTable'>\n";
            echo "<tr><td colspan='2'><b>" . NETCAT_HINT_VARS_IN_COMPONENT_SCOPE . "</b></td></tr>";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$admin_mode</a></td><td>" . NETCAT_HINT_ADMIN_MODE . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$subHost</a></td><td>" . NETCAT_HINT_SUB_HOST . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$subLink</a></td><td>" . NETCAT_HINT_SUB_LINK . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$ccLink</a></td><td>" . NETCAT_HINT_CC_LINK . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$catalogue</a></td><td>" . NETCAT_HINT_CATALOGUE_ID . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$sub</a></td><td>" . NETCAT_HINT_SUB_ID . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$cc</a></td><td>" . NETCAT_HINT_CC_ID . "</td></tr>\n";
            echo "</table>\n";

            // Хэш-массивы
            echo "<table class='InsertDataTable'>\n";
            echo "<tr><td colspan='2'><b>" . NETCAT_HINT_ARRAY . "</b></td></tr>";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$current_catalogue[]</a></td><td>" . NETCAT_HINT_CURRENT_CATALOGUE . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$current_sub[]</a></td><td>" . NETCAT_HINT_CURRENT_SUB . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$current_cc[]</a></td><td>" . NETCAT_HINT_CURRENT_CC . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$current_user[]</a></td><td>" . NETCAT_HINT_CURRENT_USER . "</td></tr>\n";
            echo "</table>\n";

            // Функции
            echo "<table class='InsertDataTable'>\n";
            echo "<tr><td colspan='2'><b>" . NETCAT_HINT_FUNCTION . "</b></td></tr>";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">is_even(int \$param)</a></td><td>" . NETCAT_HINT_IS_EVEN . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">opt(\$flag, \$string)</a></td><td>" . NETCAT_HINT_OPT . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">opt_case(\$flag, \$string1, \$string2)</a></td><td>" . NETCAT_HINT_OPT_CAES . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">listQuery(char \$sql_query, char \$output_template = NULL, char \$divider = NULL)</a></td><td>" . NETCAT_HINT_LIST_QUERY . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">nc_list_select(\$classificator_name, \$field_name = NULL, \$current_value = NULL, \$sort_type = NULL, \$sort_direction = NULL, \$template_prefix = NULL, \$template_object = NULL, \$template_suffix = NULL, \$template_any = NULL)</a></td><td>" . NETCAT_HINT_NC_LIST_SELECT . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">nc_message_link(int \$message_id, int \$class_id)</a></td><td>" . NETCAT_HINT_NC_MESSAGE_LINK . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">nc_file_path( mixed \$class_id, int \$message_id, mixed \$field_name_or_id, [string \$file_name_prefix=\"\"] )</a></td><td>" . NETCAT_HINT_NC_FILE_PATH . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">browse_messages(\$cc_env, \$range)</a></td><td>" . NETCAT_HINT_BROWSE_MESSAGE . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">nc_objects_list(int \$sub, int \$cc, char \$params, bool \$show_in_admin_mode = FALSE)</a></td><td>" . NETCAT_HINT_NC_OBJECTS_LIST . "</td></tr>\n";
            echo "<tr><td colspan='2'><b>" . NETCAT_HINT_RTFM . "</b></td></tr>";
            echo "</table>\n";
            break;
        case "template":
            // Переменные, доступные во всех полях шаблона
            echo "<table class='InsertDataTable'>\n";
            echo "<tr><td colspan='2'><b>" . NETCAT_HINT_VARS_IN_COMPONENT_SCOPE . "</b></td></tr>";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$catalogue</a></td><td>" . NETCAT_HINT_CATALOGUE_ID . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$sub</a></td><td>" . NETCAT_HINT_SUB_ID . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$cc</a></td><td>" . NETCAT_HINT_CC_ID . "</td></tr>\n";
            echo "</table>\n";

            // Хэш-массивы
            echo "<table class='InsertDataTable'>\n";
            echo "<tr><td colspan='2'><b>" . NETCAT_HINT_ARRAY . "</b></td></tr>";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$current_catalogue[]</a></td><td>" . NETCAT_HINT_CURRENT_CATALOGUE . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$current_sub[]</a></td><td>" . NETCAT_HINT_CURRENT_SUB . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$current_cc[]</a></td><td>" . NETCAT_HINT_CURRENT_CC . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$current_user[]</a></td><td>" . NETCAT_HINT_CURRENT_USER . "</td></tr>\n";
            echo "</table>\n";

            // Функции
            echo "<table class='InsertDataTable'>\n";
            echo "<tr><td colspan='2'><b>" . NETCAT_HINT_FUNCTION . "</b></td></tr>";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">is_even(int \$param)</a></td><td>" . NETCAT_HINT_IS_EVEN . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">opt(\$flag, \$string)</a></td><td>" . NETCAT_HINT_OPT . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">opt_case(\$flag, \$string1, \$string2)</a></td><td>" . NETCAT_HINT_OPT_CAES . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">listQuery(char \$sql_query, char \$output_template = NULL, char \$divider = NULL)</a></td><td>" . NETCAT_HINT_LIST_QUERY . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">nc_message_link(int \$message_id, int \$class_id)</a></td><td>" . NETCAT_HINT_NC_MESSAGE_LINK . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">nc_file_path( mixed \$class_id, int \$message_id, mixed \$field_name_or_id, [string \$file_name_prefix=\"\"] )</a></td><td>" . NETCAT_HINT_NC_FILE_PATH . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">nc_objects_list(int \$sub, int \$cc, char \$params, bool \$show_in_admin_mode = FALSE)</a></td><td>" . NETCAT_HINT_NC_OBJECTS_LIST . "</td></tr>\n";
            echo "<tr><td colspan='2'><b>" . NETCAT_HINT_RTFM . "</b></td></tr>";
            echo "</table>\n";
            break;

        case "classinput":
            if ($class_id) {
                $class_fields = $db->get_results("SELECT Field_Name, Description FROM Field WHERE Class_ID = '" . $class_id . "' ORDER BY Priority", ARRAY_A);
            } else {
                if ($system_class_id)
                    $class_fields = $db->get_results("SELECT Field_Name, Description FROM Field WHERE System_Table_ID = '" . $system_class_id . "' ORDER BY Priority", ARRAY_A);
            }
            if ($class_fields) {
                // Поля шаблона
                echo "<table class='InsertDataTable'>\n";
                echo "<tr><td colspan='2'><b>" . NETCAT_HINT_COMPONENT_FIELD . "</b></td></tr>";
                foreach ($class_fields as $class_field) {
                    echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">" . $class_field['Field_Name'] . "</a></td><td>" . $class_field['Description'] . "</td></tr>\n";
                }
                echo "</table>\n<br><br>\n";
            }
            echo "<table class='InsertDataTable'>\n";
            echo "<tr><td colspan='2'><b>" . NETCAT_HINT_OBJECT_PARAMS . "</b></td></tr>";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_Created</a></td><td>" . NETCAT_HINT_CREATED_DESC . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_LastUpdated</a></td><td>" . NETCAT_HINT_LASTUPDATED_DESC . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_RowID</a></td><td>" . NETCAT_HINT_MESSAGE_ID . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_UserID</a></td><td>" . NETCAT_HINT_USER_ID . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_Checked</a></td><td>" . NETCAT_HINT_CHECKED . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_IP</a></td><td>" . NETCAT_HINT_IP . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_UserAgent</a></td><td>" . NETCAT_HINT_USER_AGENT . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_LastUserID</a></td><td>" . NETCAT_HINT_LAST_USER_ID . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_LastIP</a></td><td>" . NETCAT_HINT_LAST_USER_IP . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_LastUserAgent</a></td><td>" . NETCAT_HINT_LAST_USER_AGENT . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_AdminButtons</a></td><td>" . NETCAT_HINT_ADMIN_BUTTONS . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$f_AdminCommon</a></td><td>" . NETCAT_HINT_ADMIN_COMMONS . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$fullLink</a></td><td>" . NETCAT_HINT_FULL_LINK . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$fullDateLink</a></td><td>" . NETCAT_HINT_FULL_DATE_LINK . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$editLink</a></td><td>" . NETCAT_HINT_EDIT_LINK . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$deleteLink</a></td><td>" . NETCAT_HINT_DELETE_LINK . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$dropLink</a></td><td>" . NETCAT_HINT_DROP_LINK . "</td></tr>\n";
            echo "<tr><td><a href='#' title='' onclick=\"insert_bbcode('" . $window . "', '" . $form . "', '" . $textarea . "', this.innerHTML, '', false);\">\$checkedLink</a></td><td>" . NETCAT_HINT_CHECKED_LINK . "</td></tr>\n";
            echo "</table>\n";
            break;
    }
}

function nc_admin_img($name, $alt, $width = 0, $height = 0, $align = '', $class = '') {
    global $ADMIN_TEMPLATE;
    if (!stripos($name, '.')) {
        return "<div class='icons icon_" . $name . "' " . ($align ? "align='" . $align . "'" : "") . " title='" . $alt . "'></div>";
    }
    return "<img src='" . $ADMIN_TEMPLATE . "img/" . $name . "' " . ($width ? "width='" . $width . "'" : "") . " " . ($height ? "height='" . $height . "'" : "") . "  " . ($align ? "align='" . $align . "'" : "") . " alt='" . $alt . "' title='" . $alt . "' class='" . $class . "'/>";
}

function nc_admin_textarea($disc, $name, $value, $is_resizeble = 0, $fck = 0, $style = '') {
    if (is_array($value) && isset($value[$name])) {
        $value = $value[$name];
    }

    $nc_core = nc_Core::get_object();
    if (!$style)
        $style = "margin-top: 5px; width:100%; height:6em; line-height:1em";
    $ret = "<div style='margin:10px 0; _padding:0;'>\n" . $disc . ":";
    if ($fck) {
        $EditorType = $nc_core->get_settings('EditorType');
        $windowWidth = 750;
        $windowHeight = 605;
        switch ($EditorType) {
            default:
            case 2:
                $editor_name = 'FCKeditor';
                break;
            case 3:
                $editor_name = 'ckeditor4';
                $windowWidth = 1100;
                $windowHeight = 420;
                break;
            case 4:
                $editor_name = 'tinymce';
                break;
        }
        $link = "editors/{$editor_name}/neditor.php";
        $ret .= "<button type='button' onclick=\"window.open('" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . $link . "?form=main&control=" . $name . "', 'Editor', 'width={$windowWidth},height={$windowHeight},resizable=yes,scrollbars=no,toolbar=no,location=no,status=no,menubar=no');\">" . TOOLS_HTML_INFO . "</button><br />";
    }
    if (is_array($value)) {
        return;
    }
    $ret .= "
        <textarea name='" . $name . "' id='" . $name . "' style='" . $style . "'>" . htmlentities($value, ENT_COMPAT, MAIN_ENCODING) . "</textarea>" .
        "</div>\n";

    return $ret;
}

function nc_admin_classificators($disc, $name, $value = '', $style = '') {
    $nc_core = nc_Core::get_object();
    static $cf = array();

    if (empty($cf)) {
        $res = $nc_core->db->get_results("SELECT `Classificator_Name`, `Table_Name`
                                      FROM `Classificator`
                                      WHERE `System` = 0 ORDER BY `Classificator_ID` ", ARRAY_A);
        if (!empty($res))
            foreach ($res as $v) {
                $cf[$v['Table_Name']] = $v['Classificator_Name'];
            }
    }

    $res = $disc . ":<br/>";
    $res .= "<select name='" . $name . "' " . ($style ? "style='" . $style . "'" : "") . ">";
    foreach ($cf as $k => $v) {
        $res .= "<option value='" . $k . "' " . ($value == $k ? " selected='selected' " : "") . ">" . $v . "</option>";
    }
    $res .= "</select>";

    return $res;
}

function nc_admin_select_static($disc, $name, $value = array()) {
    $nc_core = nc_Core::get_object();
    $ret = $disc . ":<br/>";
    $ret .= "<div id='select_static'>
            <div id='select_static_head'>
              <div class='key'>" . NETCAT_CUSTOM_KEY . "</div>
              <div class='value'>" . NETCAT_CUSTOM_VALUE . "</div>
              <div style='clear:both;'></div>
            </div>
          </div>";
    $ret .= "<div onclick='nc_s.add()' class='select_static_add'>
            <div class='icons icon_obj_add' title='" . CONTROL_CONTENT_SUBDIVISION_FUNCS_ADD . "'></div>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_ADD . "
         </div>";

    $ret .= "<script> nc_s = new nc_selectstatic();\r\n";
    if (!empty($value)
    ) foreach ($value as $k => $v) {
        $ret .= "\tnc_s.add('" . $k . "', '" . $v . "'); \r\n";
    }
    $ret .= "</script>";

    return $ret;
}

function nc_admin_select_subdivision($disc, $name, $value) {
    global $db;
    static $subs;

    if (is_array($value) && isset($value[$name])) {
        $value = $value[$name];
    }

    if (!$subs) {
        $subs = $db->get_results("SELECT s.`Subdivision_ID` as `value`,
      CONCAT(s.`Subdivision_ID`, '. ', s.`Subdivision_Name`) as  `description`,
      c.`Catalogue_Name` as `optgroup`,
      s.`Parent_Sub_ID` as `parent`
      FROM `Catalogue` AS `c`, `Subdivision` AS `s`
      WHERE s.`Catalogue_ID` = c.`Catalogue_ID`
      ORDER BY c.`Priority`, s.`Priority` ", ARRAY_A);
    }

    $res = "<div style='margin:5px 0; _padding:0;'>" . $disc . " ";
    $res .= "<select class='chosen-select' name='" . $name . "'>" . nc_select_options($subs, $value) . "</select></div>";
    return $res;
}

function nc_admin_select_component($disc, $name, $value = '') {
    global $db;
    static $classes;

    if (is_array($value) && isset($value[$name])) {
        $value = $value[$name];
    }

    if (!$classes) {
        $classes = $db->get_results("SELECT `Class_ID` as value,
      CONCAT(`Class_ID`, '. ', `Class_Name`) as description,
      `Class_Group` as optgroup
      FROM `Class`
      WHERE `ClassTemplate` = 0
      ORDER BY `Class_Group`, `Priority`, `Class_ID`", ARRAY_A);
    }

    $res = "<div style='margin:5px 0; _padding:0;'>" . $disc . " ";
    $res .= "<select class='chosen-select' name='" . $name . "'>" . nc_select_options($classes, $value) . "</select></div>";
    return $res;
}

function nc_admin_select_field($table, $disc, $name, $value = '') {
    $c = new nc_Component(0, 3);
    $fields = $c->get_fields();
    if (is_array($value) && isset($value[$name])) {
        $value = $value[$name];
    }

    $res = "<div style='margin:5px 0; _padding:0;'>" . $disc . " ";
    if (!empty($fields)) {
        $res .= "<select class='chosen-select' name='" . $name . "'>\n";
        foreach ($fields as $v) {
            $res .= "\t<option value='" . $v['name'] . "' " . ($value == $v['name'] ? "selected='selected'" : "") . ">" . $v['description'] . "</option>\n";
        }
        $res .= "</select>\n";
    }
    $res .= "</div>";
    return $res;
}

function nc_admin_select_usergroup($disc, $name, $value = '') {
    global $db;
    $groups = $db->get_results("SELECT * FROM `PermissionGroup`", ARRAY_A);

    $res = "<div style='margin:5px 0; _padding:0;'>" . $disc . " ";
    if (!empty($groups)) {
        $res .= "<select class='chosen-select' name='" . $name . "'>\n";
        foreach ($groups as $v) {
            $res .= "\t<option value='" . $v['PermissionGroup_ID'] . "' " . ($value == $v['PermissionGroup_ID'] ? "selected='selected'" : "") . ">" . $v['PermissionGroup_Name'] . "</option>\n";
        }
        $res .= "</select>\n";
    }
    $res .= "</div>";
    return $res;
}

function nc_admin_label_color_field($value = null, $with_label = true, $field_name = 'LabelColor') {
    $colors = array('', 'purple', 'blue', 'cyan', 'green', 'olive', 'yellow', 'orange', 'red');

    $result = $with_label ? CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_LABEL_COLOR . ':' : '';
    $result .= "<div class='nc-admin-label-color-field'>";

    foreach ($colors as $color) {
        $color_class = $color ? ' nc-bg-' . $color : '';
        $checked = $value == $color ? " checked='checked'" : '';
        $result .= "<label class='nc-padding-5{$color_class}'><input type='radio' name='{$field_name}' value='{$color}'{$checked} /></label> ";
    }
    $result .= "</div>";

    return $result;
}

/**
 * Функция возвращает js-код для ресайза текстовых полей
 * @return string js-код
 */
function nc_admin_js_resize() {
    return "<script type='text/javascript'>\$nc(function() {bindTextareaResizeButtons();} )</script>";
}

/**
 * Функция выводит список пользовательских настроек
 *
 * @param int номер компонента
 * @param int номер макета дизайн
 * @param array массив с настройками
 * @return int
 */
function nc_customsettings_show($ClassID = 0, $TemplateID = 0, $custom_settings = array(), $Class_Template = 0) {
    global $UI_CONFIG;

    $suffix = +$_REQUEST['fs'] ? '_fs' : '';

    if (!$ClassID && !$TemplateID) return false;

    if ($ClassID && !$Class_Template) {
        $ac = "urlDispatcher.load('dataclass$suffix.custom.new(" . $ClassID . ")')";
        $ac1 = "urlDispatcher.load('dataclass$suffix.custom.manual(" . $ClassID . ")')";
    } else if ($ClassID && $Class_Template) {
        $ac = "urlDispatcher.load('classtemplate$suffix.custom.new(" . $ClassID . ")')";
        $ac1 = "urlDispatcher.load('classtemplate$suffix.custom.manual(" . $ClassID . ")')";
    } else {
        $ac = "urlDispatcher.load('template$suffix.custom.new(" . $TemplateID . ")')";
        $ac1 = "urlDispatcher.load('template$suffix.custom.manual(" . $TemplateID . ")')";
    }

    $UI_CONFIG->actionButtons[] = array("id" => "addcs",
        "caption" => CONTROL_FIELD_LIST_ADD,
        "action" => $ac,
        'align' => 'left');
    $UI_CONFIG->actionButtons[] = array("id" => "del",
        "caption" => NETCAT_CUSTOM_ONCE_MANUAL_EDIT,
        "action" => $ac1,
        'align' => 'left');

    if (empty($custom_settings)) {
        nc_print_status(NETCAT_CUSTOM_NONE_SETTINGS, 'info');
        return false;
    }

    $a2f = new nc_a2f($custom_settings, '');

    $header = " <table class='nc-table nc--striped nc--small nc--hovered' style='width:100%'>
            <tr>
              <th width='30%'>" . NETCAT_CUSTOM_ONCE_FIELD_NAME . "</th>
              <th width='45%'>" . NETCAT_CUSTOM_ONCE_FIELD_DESC . "</th>
              <th  width='20%'>" . NETCAT_CUSTOM_TYPE . "</th>
              <td align=center width='5%'>
                " . nc_admin_img('delete', NETCAT_CUSTOM_ONCE_DROP) . "
              </td>
            </tr>";

    echo "<form action='index.php' method='post' >";
    echo "<input type='hidden' name='phase' value='" . ($ClassID ? ($Class_Template ? "2410" : "241") : "81") . "' />";
    echo "<input type='hidden' name='ClassID' value='" . $ClassID . "' />";
    echo "<input type='hidden' name='TemplateID' value='" . $TemplateID . "' />";
    echo "<input type='hidden' name='fs' value='" . +$_REQUEST['fs'] . "' />";

    echo $a2f->render_settings($header, '<tr style="background-color: #FFF;"><td><a href="index.php?phase=' . ($ClassID ? ($Class_Template ? 250 : 25) : 9) . '&amp;' . ($ClassID ? "ClassID=" . $ClassID : "TemplateID=" . $TemplateID) . '&amp;param=%NAME&fs=' . +$_REQUEST['fs'] . '">%NAME</a></td><td>%CAPTION</td><td>%TYPENAME</td><td align="center">' . nc_admin_checkbox_simple("kill[]", "%NAME") . '</td></tr>', '</table>');

    $UI_CONFIG->actionButtons[] = array("id" => "del",
        "caption" => NETCAT_CUSTOM_ONCE_DROP_SELECTED,
        "action" => "mainView.submitIframeForm()",
        "align" => "right",
        "red_border" => true,
    );


    echo "</form>";
    return false;
}

/**
 * Функция удаляет параметры в пользовательских настроек
 * имена удаляемые параметров передаются в массиве $_POST['kill']
 * @param int номер компонента
 * @param int номер макета дизайн
 * @param array массив с настройками
 * @return string строка с настройками
 */
function nc_customsettings_drop($ClassID = 0, $TemplateID = 0, $custom_settings = '') {
    $nc_core = nc_Core::get_object();

    if (!$custom_settings || empty($_POST['kill'])) return $custom_settings;

    $ClassID = intval($nc_core->input->fetch_get_post('ClassID'));
    $TemplateID = intval($nc_core->input->fetch_get_post('TemplateID'));

    $a2f = new nc_a2f($custom_settings, '');
    $custom_settings = $a2f->eval_value($custom_settings);

    foreach ($custom_settings as $k => $v) {
        if (in_array($k, $_POST['kill'])) unset($custom_settings[$k]);
    }

    $s = '';
    if (!empty($custom_settings)) {
        $s = '$settings_array = ' . nc_a2f::array_to_string($custom_settings) . ';';
    }

    if ($ClassID) {
        $nc_core->component->update($ClassID, array('CustomSettingsTemplate' => $s));
    } else {
        $nc_core->template->update($TemplateID, array('CustomSettings' => $s));
    }

    return $s;
}

/**
 * Функция выводит форму добавления/редактирования параметра пользовательских настроек
 *
 * @param int номер компонента
 * @param int номер макета дизайна
 * @param string имя параметра
 * @return int
 */
function nc_customsettings_show_once($ClassID = 0, $TemplateID = 0, $param = '', $Class_Template = 0) {
    $nc_core = nc_Core::get_object();
    global $UI_CONFIG;

    $TemplateID = (int)$TemplateID;

    $types = array('string', 'textarea', 'int', 'float', 'checkbox', 'datetime', 'select', 'rel', 'file', 'color', 'divider', 'custom');
    $subtypes = array();
    $extend_params = array();
    $has_default = array();
    $has_initial_value = array();
    foreach ($types as $v) {
        $classname = "nc_a2f_field_" . $v;
        if (!class_exists($classname)) {
            die('Incorrect type: ' . $v . '. Class ' . $classname . ' not found.');
        }
        /** @var nc_a2f_field $f */
        $f = new $classname();
        $extend_params[$v] = $f->get_extend_parameters();
        $subtypes[$v] = $f->get_subtypes();
        $has_default[$v] = $f->has_default();
        $has_initial_value[$v] = $f->can_have_initial_value();
        foreach ($subtypes[$v] as $k => $val) {
            $classname = "nc_a2f_field_" . $v . "_" . $val;
            if (!class_exists($classname)) {
                die('Incorrect type: ' . $val . '. Class ' . $classname . ' not found.');
            }
            $f = new $classname();
            $extend_params[$v . "_" . $val] = $f->get_extend_parameters();
            $subtypes[$v][$k] = array($val => constant('NETCAT_CUSTOM_TYPENAME_' . strtoupper($v) . '_' . strtoupper($val)));
        }
    }

    if ($ClassID) {
        $custom_settings = $nc_core->component->get_by_id($ClassID, 'CustomSettingsTemplate');
    } else {
        $custom_settings = $nc_core->template->get_by_id($TemplateID, 'CustomSettings');
    }

    if ($custom_settings) {
        $a2f = new nc_a2f($custom_settings, '');
        $custom_settings = $a2f->eval_value($custom_settings);
        $settings = $custom_settings[$param];
    }
    else {
        $settings = array();
    }

    if (($pst = $nc_core->input->fetch_get_post('type'))) {
        $settings['type'] = $pst;
    }
    if (($pst = $nc_core->input->fetch_get_post('subtype'))) {
        $settings['subtype'] = $pst;
    }
    if (empty($settings['type'])) {
        $settings['type'] = 'string';
    }
    $classname = 'nc_a2f_field_' . $settings['type'];
    /** @var nc_a2f_field $cs */
    $cs = new $classname($settings);

    echo "<script type='text/javascript'>" .
        "nc_cs = new nc_customsettings('" .
        $settings['type'] . "', '" .
        (isset($settings['subtype']) ? $settings['subtype'] : '') . "', " .
        nc_array_json($subtypes) . ", " .
        nc_array_json($has_default) . ", " .
        nc_array_json($has_initial_value) .
        ")" .
        "</script>";
    echo "<form action='index.php' method='post' >";
    echo "<input type='hidden' name='phase' value='" . ($ClassID ? ($Class_Template ? "2510" : "251") : "91") . "' />";
    echo "<input type='hidden' name='ClassID' value='" . $ClassID . "' />";
    echo "<input type='hidden' name='TemplateID' value='" . $TemplateID . "' />";
    echo "<input type='hidden' name='param' value='" . $param . "' />";
    echo "<input type='submit' class='hidden'>";
    echo "<input type='hidden' name='fs' value='" . +$_REQUEST['fs'] . "' />";
    echo "<fieldset style='margin-bottom: 15px;'><legend>" . NETCAT_CUSTOM_ONCE_MAIN_SETTINGS . "</legend><div style='width: 50%;'>";
    echo nc_admin_input(NETCAT_CUSTOM_ONCE_FIELD_NAME, 'name', (($pst = $nc_core->input->fetch_get_post('name')) ? $pst : $param), 0, 'width:100%;  margin-bottom: 10px;');
    echo nc_admin_input(NETCAT_CUSTOM_ONCE_FIELD_DESC, 'caption', (($pst = $nc_core->input->fetch_get_post('caption')) ? $pst : nc_array_value($settings, 'caption')), 0, 'width:100%;  margin-bottom: 10px;');

    echo "<div id='initial_value'>" .
        nc_admin_input(
            ($ClassID ? NETCAT_CUSTOM_ONCE_FIELD_INITIAL_VALUE_INFOBLOCK : NETCAT_CUSTOM_ONCE_FIELD_INITIAL_VALUE_SUBDIVISION),
            'initial_value',
            $nc_core->input->fetch_get_post('initial_value') ?: nc_array_value($settings, 'initial_value'),
            0,
            'width:100%;  margin-bottom: 10px;'
        ) .
        "</div>";

    echo "<div id='def' style='display: " . ($cs->has_default() ? "block" : "none") . "'>" . nc_admin_input(NETCAT_CUSTOM_ONCE_DEFAULT, 'default_value', (($pst = $nc_core->input->fetch_get_post('default_value')) ? $pst : nc_array_value($settings,'default_value')), 0, 'width:100%;  margin-bottom: 10px;') . "</div>";

    echo "<div style='margin-bottom: 20px'><input type='hidden' name='skip_in_form' value='0'>" .
        nc_admin_checkbox(
            NETCAT_CUSTOM_ONCE_DONT_SHOW,
            'skip_in_form',
            $nc_core->input->fetch_get_post('skip_in_form') ?: nc_array_value($settings, 'skip_in_form')
        ) .
        "</div>";

    echo NETCAT_CUSTOM_TYPE . ": <br/>
        <select id='type' style='width: 100%;  margin-bottom: 10px;' name='type' onchange='nc_cs.changetype()' >";

    foreach ($types as $v) {
        echo "<option value='" . $v . "' " . ($v == $settings['type'] ? "selected='selected' " : "") . ">" . constant('NETCAT_CUSTOM_TYPENAME_' . strtoupper($v)) . "</option>";
    }

    echo "</select><br/>";

    echo "<div id='cs_subtypes_caption' style='display:none;'>" . NETCAT_CUSTOM_SUBTYPE . ":</div>";
    echo "<div id='cs_subtypes' style=' margin-bottom: 10px;'></div>";
    echo "</div></fieldset>";

    foreach ($extend_params as $n => $tp) {
        echo "<div id='extend_" . $n . "' class='cs_extends' style='display: none;'>";

        if (!empty($tp)) {
            echo "<fieldset><legend>" . NETCAT_CUSTOM_ONCE_EXTEND . "</legend><div style='width: 50%;'>";
            foreach ($tp as $name => $v) {
                if ($v['type'] == 'checkbox') {
                    echo nc_admin_checkbox($v['caption'], 'cs_' . $name, (($pst = $nc_core->input->fetch_get_post('cs_' . $name)) ? $pst : nc_array_value($settings, $name)));
                } else if ($v['type'] == 'text') {
                    if (is_array(nc_array_value($settings, $name))) {
                        $settings[$name] = nc_a2f::array_to_string(nc_array_value($settings, $name));
                    }
                    echo nc_admin_textarea($v['caption'], 'cs_' . $name, (($pst = $nc_core->input->fetch_get_post('cs_' . $name)) ? $pst : nc_array_value($settings, $name)), 1);
                } else if ($v['type'] == 'classificator') {
                    echo nc_admin_classificators($v['caption'], 'cs_' . $name, (($pst = $nc_core->input->fetch_get_post('cs_' . $name)) ? $pst : nc_array_value($settings, $name)), 'width:100%;');
                } else if ($v['type'] == 'static') {
                    echo nc_admin_select_static($v['caption'], 'cs_' . $name, nc_array_value($settings, $name));
                } else {
                    echo nc_admin_input($v['caption'], 'cs_' . $name, (($pst = $nc_core->input->fetch_get_post('cs_' . $name)) ? $pst : nc_array_value($settings, $name)), 0, 'width:100%;  margin-bottom: 10px;');
                }
            }
            echo "</div></fieldset>";
        }
        echo "</div>";
    }
    echo nc_admin_js_resize();

    echo "</form>";

    echo "<script type='text/javascript'>
          nc_cs.changetype();
          nc_cs.changesubtype();
        </script>";
    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => ($param ? NETCAT_CUSTOM_ONCE_SAVE : NETCAT_CUSTOM_ONCE_ADD),
        "action" => "mainView.submitIframeForm()",
        "align" => "right"
    );

    return 0;
}

/**
 * Сохранение информации о конкретном параметре пользовательских настроек
 * @return bool
 * @throws Exception
 */
function nc_customsettings_save_once() {
    $nc_core = nc_Core::get_object();
    $ClassID = $nc_core->input->fetch_get_post('ClassID');
    $TemplateID = $nc_core->input->fetch_get_post('TemplateID');
    $old_name = $nc_core->input->fetch_get_post('param');
    $new_name = trim($nc_core->input->fetch_get_post('name'));
    $type = $nc_core->input->fetch_get_post('type');
    $subtype = $nc_core->input->fetch_get_post('subtype');
    $caption = $nc_core->input->fetch_get_post('caption');
    $default_value = trim($nc_core->input->fetch_get_post('default_value'));
    $initial_value = trim($nc_core->input->fetch_get_post('initial_value'));
    $skip_in_form = $nc_core->input->fetch_get_post('skip_in_form');

    if (!preg_match("/^[a-z0-9_]+$/i", $new_name)) {
        throw new Exception(NETCAT_CUSTOM_ONCE_ERROR_FIELD_NAME);
    }
    if (!$caption) {
        throw new Exception(NETCAT_CUSTOM_ONCE_ERROR_CAPTION);
    }

    if ($ClassID) {
        $custom_settings = $nc_core->component->get_by_id($ClassID, 'CustomSettingsTemplate');
    } else {
        $custom_settings = $nc_core->template->get_by_id($TemplateID, 'CustomSettings');
    }

    if (!$custom_settings) {
        $custom_settings = array();
    } else {
        $a2f = new nc_a2f($custom_settings, '');
        $custom_settings = $a2f->eval_value($custom_settings);
    }

    $keys = empty($custom_settings) ? array() : array_keys($custom_settings);

    if (in_array($new_name, $keys) && $new_name != $old_name) {
        throw new Exception(NETCAT_CUSTOM_ONCE_ERROR_FIELD_EXISTS);
    }

    $param = array('type' => $type, 'subtype' => $subtype, 'caption' => $caption);
    if (strlen($default_value)) {
        $param['default_value'] = $default_value;
    }

    $param['initial_value'] = $initial_value;
    $param['skip_in_form'] = $skip_in_form;

    $input = $nc_core->input->fetch_get_post();

    // загружаем объект для работы с полем
    $classname = "nc_a2f_field_" . $type . ($subtype ? "_" . $subtype : "");
    if (!class_exists($classname)) {
        return false;
    }
    /** @var nc_a2f_field $fl */
    $fl = new $classname();
    $ex_params = $fl->get_extend_parameters();
    if (!empty($ex_params)) {
        foreach ($ex_params as $k => $v) {
            if ($type == 'select' && $subtype == 'static' && $k == 'values') {
                $param['values'] = array();
                if (!empty($_POST['select_static_key'])) {
                    foreach ($_POST['select_static_key'] as $i => $option_key) {
                        $option_key = trim($option_key);
                        $option_value = trim($_POST['select_static_value'][$i]);

                        if (!strlen($option_key) || !strlen($option_value)) {
                            continue;
                        }
                        $param['values'][$option_key] = $option_value;
                    }
                }
            } else {
                if (isset($input['cs_' . $k])) {
                    $param[$k] = $input['cs_' . $k];
                }
            }
        }
    }

    $custom_settings_new = array();
    if (!empty($custom_settings)) {
        foreach ($custom_settings as $k => $v) {
            if ($k != $old_name && $k != $new_name) {
                $custom_settings_new[$k] = $v;
            }
            if ($k == $old_name || $k == $new_name) {
                $custom_settings_new[$new_name] = $param;
            }
        }
    }

    if (!$old_name) {
        $custom_settings_new[$new_name] = $param;
    }


    $s = nc_a2f::array_to_string($custom_settings_new);
    $s = $custom_settings_new ? '$settings_array = ' . $s . ';' : '';

    $entity = '_settings';
    if ($ClassID) {
        $entity = 'class' . $entity;
        $nc_core->component->update($ClassID, array('CustomSettingsTemplate' => $s));
    } else {
        $entity = 'template' . $entity;
        $nc_core->template->update($TemplateID, array('CustomSettings' => $s));
    }

    nc_image_generator::remove_generated_images($entity, $old_name);
    nc_image_generator::remove_generated_images($entity, $new_name);

    return true;
}

/**
 * Показ формы ручного редактирования пользовательских настроек
 * @param int номер компонента
 * @param int номер макета дизайна
 */
function nc_customsettings_show_manual($ClassID = 0, $TemplateID = 0, $Class_Template = 0) {
    global $UI_CONFIG;
    $nc_core = nc_Core::get_object();
    $suffix = +$_REQUEST['fs'] ? '_fs' : '';

    if ($ClassID) {
        $custom_settings = $nc_core->component->get_by_id($ClassID, 'CustomSettingsTemplate');
    } else {
        $custom_settings = $nc_core->template->get_by_id($TemplateID, 'CustomSettings');
    }

    echo "<form action='index.php' method='post' >";
    echo "<input type='hidden' name='phase' value='" . ($ClassID ? ($Class_Template ? "2610" : "261") : "101") . "' />";
    echo "<input type='hidden' name='ClassID' value='" . $ClassID . "' />";
    echo "<input type='hidden' name='TemplateID' value='" . $TemplateID . "' />";
    echo "<input type='hidden' name='fs' value='" . +$_REQUEST['fs'] . "' />";
    echo nc_admin_textarea(NETCAT_CUSTOM_USETTINGS, 'CustomSettings', $custom_settings, 1, 0, "height:286px;");
    echo nc_admin_js_resize();
    echo "</form>";

    $UI_CONFIG->actionButtons[] = array(
        "id" => "back",
        "align" => "left",
        "caption" => CONTROL_AUTH_HTML_BACK,
        "location" => ($ClassID ? ($Class_Template ? "classtemplate" . $suffix . ".custom(" . $ClassID . ")" : "dataclass" . $suffix . ".custom(" . $ClassID . ")") : "template" . $suffix . ".custom(" . $TemplateID . ")")
    );

    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => NETCAT_CUSTOM_ONCE_SAVE,
        "action" => "mainView.submitIframeForm()"
    );
}

class nc_admin_fieldset_collection {
    private $collection = array();
    private $prefix = '';
    private $suffix = '';
    private static $static_prefix = null;

    public function new_fieldset($name, $title = '', $spoiler = '') {
        if (isset($this->collection[$name])) {
            throw new Exception("fieldset '$name' is exist");
        }
        $this->collection[$name] = new nc_admin_fieldset($title, 'on', $spoiler);
        return $this->collection[$name];
    }

    public function __get($name) {
        if (isset($this->collection[$name])) {
            return $this->collection[$name];
        }
        throw new Exception("fieldset '$name' is not exist");
    }

    public function to_string($divider = '') {
        $result = array();
        foreach ($this->collection as $fieldset) {
            $result[] = $fieldset->result();
        }
        return $this->get_static_prefix() . $this->prefix . join($divider, $result) . $this->suffix;
    }

    public function set_prefix($prefix) {
        $this->prefix = $prefix;
        return $this;
    }

    public function set_suffix($suffix) {
        $this->suffix = $suffix;
        return $this;
    }

    public function set_static_prefix($static_prefix, $reset = false) {
        if (self::$static_prefix === null || $reset) {
            self::$static_prefix = $static_prefix;
        }
        return $this;
    }

    private function get_static_prefix() {
        $static_prefix = self::$static_prefix;
        self::$static_prefix = '';
        return $static_prefix;
    }
}

class nc_admin_fieldset {

    protected $name;
    protected $text = '';
    protected static $style = null;
    protected $show = true;
    protected $spoiler;

    public function __construct($name = '', $mode = 'on', $spoiler = false) {
        $this->name = htmlspecialchars($name);
        if (self::$style === null) {
            self::$style = $this->get_style();
        }
        $this->spoiler = $spoiler;
    }

    protected function get_style() {
        return "";
    }

    public function add($text) {
        $this->text .= "\n" . $text;
        return $this;
    }

    public function show($show = true) {
        $this->show = (bool)$show;
        return $this;
    }

    public function result() {
        $spoiler = $this->spoiler;
        $style = self::$style;
        self::$style = '';
        $html = $style . "<div class='nc_admin_fieldset'" . ($this->show ? '' : " style='display: none;'") . ">";

        if ($this->name) {
            $html .= "<div class='nc_admin_fieldset_head'>";

            if ($spoiler) {
                $html .= "<a href='#' class='nc--dashed' onclick='\$nc(this).closest(\".nc_admin_fieldset_head\").next().toggle(); return false;'>{$this->name}</a>";
            } else {
                $html .= $this->name;
            }

            $html .= "</div>";
        }

        $html .= "<div class='nc_admin_fieldset_body " . ($spoiler ? 'nc--hide' : '') . "'>{$this->text}</div></div>";

        return $html;
    }

}

// $attr = array("style", "size", "maxlength", "simple", "disabled")
function nc_admin_input_template($name, $value = '', $attr = null, $prefix = '', $suffix = '', $type = 'text') {    
    if (is_array($value) && isset($value[$name])) {
        $value = $value[$name];
    }

    $input = $prefix . "<input type='$type' name='$name'";

    if (isset($attr["disabled"]) && $attr["disabled"]) {
        $input .= "disabled";
    }

    if (isset($attr["simple"])) {
        $attrStr = " " . $attr["simple"];
        unset($attr["simple"]);
    }
    foreach ($attr as $key => $val) {
        if (!empty($val)) {
            $input .= " $key='$val'";
        }
    }
    
    if (is_array($value)) {
        //если это массив , выход из функции, поскольку ее вывод предусматривает работу со строкой
        //избавляет от warning в случае с неустановленной настройкой номера инфоблока со списком пользователей
        return;
    }
    
    return $input . (nc_strlen($value) ? " value='" . htmlentities($value, ENT_QUOTES, MAIN_ENCODING) . "'" : "") . $attrStr . " />" . $suffix;
}

function nc_admin_input($label, $name, $value = '', $size = 0, $style = '', $simple = '', $is_disabled = false) {
    $attr = array(
        'style' => $style,
        'size' => $size,
        'simple' => $simple,
        'disabled' => $is_disabled,
        'class' => 'input'
    );

    return nc_admin_input_template($name, $value, $attr, "<div class='inf_block'><label>$label:</label><br>", '</div>');
}

function nc_admin_input_simple($name, $value = '', $size = 0, $style = '', $simple = '') {
    $attr = array(
        'style' => $style,
        'size' => $size,
        'simple' => $simple
    );
    if (strpos($simple, 'class=') === false) {
        $attr['class'] = 'input';
    }
    return nc_admin_input_template($name, $value, $attr);
}

function nc_admin_input_in_text($disc, $name, $value = '', $size = 0, $style = '', $simple = '') {
    $attr = array('style' => $style,
        'size' => $size,
        'simple' => $simple);
    if (strpos($disc, '%input') !== false) {
        return str_replace('%input', nc_admin_input_template($name, $value, $attr), $disc) . "\n";
    }
    return $disc . "\n";
}

function nc_admin_input_password($name, $value = '', $size = 32, $style = '', $simple = '') {
    $attr = array('style' => $style,
        'size' => $size,
        'simple' => $simple);
    return nc_admin_input_template($name, $value, $attr, "", "", "password");
}

function nc_admin_checkbox($disc, $name, $value, $style = '', $simple = '') {
    if (is_array($value) && isset($value[$name])) {
        $value = $value[$name];
    }

    return " <div style='margin:5px 0; _padding:0;'>\n
        <input type='checkbox' name='" . $name . "' id='" . $name . "' " . ($style ? $style : "") . " value='1'" . ($value ? " checked" : "") . "/>
        <label for='" . $name . "'>" . $disc . "</label>\n</div>\n";
}

function nc_admin_checkbox_simple($name, $value = '', $disc = '', $checked = false, $id = '', $simple = '') {
    if ($disc) {
        if (!$id) {
            $id = $name;
        }
        $suff = " <label for='$id'>" . $disc . '</label>';
    } else {
        $suff = '';
    }
    $attr = array('simple' => $simple . ($checked ? ' checked' : ''),
        'id' => $id);
    return nc_admin_input_template($name, $value, $attr, '', $suff, 'checkbox');
}

function nc_admin_radio_simple($name, $value = '', $disc = '', $checked = false, $id = '', $simple = '') {
    if ($disc) {
        if (!$id) {
            $id = $name;
        }
        $suff = " <label for='$id'>" . $disc . '</label>';
    } else {
        $suff = '';
    }
    $attr = array(
        'simple' => $simple . ($checked ? ' checked' : ''),
        'id' => $id);
    return nc_admin_input_template($name, $value, $attr, '', $suff, 'radio');
}

function nc_admin_textarea_simple($name, $value = '', $disc = '', $rows = 0, $cols = 0, $simple = '', $wrap = 'off', $class = '') {
    $attr = array(
        'rows' => $rows,
        'cols' => $cols,
        'simple' => $simple,
        'wrap' => $wrap,
        'class' => $class);
    return nc_admin_textarea_template($name, $value, $attr, $disc, '');
}

function nc_admin_textarea_resize($name, $value = '', $disc = '', $rows = 0, $cols = 0, $id = '', $wrap = 'off') {
    if (!$id) {
        $id = $name;
    }
    $attr = array(
        'rows' => $rows,
        'cols' => $cols,
        'id' => $id,
        'wrap' => $wrap);

    $pref = $disc ? "<div>$disc</div>" : '';
    return nc_admin_textarea_template($name, $value, $attr, $pref);
}

function nc_admin_textarea_template($name, $value = '', $attr = null, $prefix = '', $suffix = '') {
    if (is_array($value) && isset($value[$name])) {
        $value = $value[$name];
    }

    $ret = $prefix . "<textarea name='$name'";
    if (isset($attr["simple"])) {
        $ret .= " " . $attr["simple"];
        unset($attr["simple"]);
    }

    foreach ($attr as $key => $val) {
        if (!empty($val)) {
            $ret .= " $key='$val'";
        }
    }
    $ret .= '>' . (nc_strlen($value) ? htmlentities($value, ENT_QUOTES, MAIN_ENCODING) : '') . '</textarea>' . $suffix;
    return $ret;
}

function nc_admin_select_simple($disc, $name, $array, $selectedVar = 'nosel', $simple = '') {
    $ret = $disc . "<select class='chosen-select' name='$name' $simple>";
    if ($selectedVar == 'nosel') {
        foreach ($array as $key => $val) {
            $ret .= "<option value='$key'>" . $val . "</option>";
        }
    } else {
        foreach ($array as $key => $val) {
            $ret .= "<option value='$key'" . ($key == $selectedVar ? " selected" : "") . ">" . $val . "</option>";
        }
    }
    $ret .= "</select>";
    return $ret;
}

function nc_unique_message_keyword($keyword, $class_id) {
    if (!$keyword || !$class_id) return;
    $i = 0;
    while (nc_core('db')->get_var("SELECT COUNT(*)
            FROM `Message" . intval($class_id) . "`
                WHERE `Keyword` = '" . nc_core('db')->escape($keyword . ($i ? '-' . $i : '')) . "'")) {
        $i++;
    }
    return $i ? $keyword . '-' . $i : $keyword;
}
