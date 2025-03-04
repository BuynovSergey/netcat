<?php

if (!class_exists("nc_System"))
    die("Unable to load file.");

if (!class_exists("CKEditor")) {
    include_once($nc_core->ROOT_FOLDER . "editors/ckeditor4/ckeditor.php");
}

if (!class_exists("nc_Editors")) {
    include_once($nc_core->ROOT_FOLDER . "editors/nc_editors.class.php");
}

$toolbars = CKEditor::$toolbarNames;

/**
 * Вывод формы настроек CKEditor
 *
 * @return bool
 */
function WysiwygCkeditorSettingsForm() {
    global $nc_core, $db, $UI_CONFIG;

    $settings = $nc_core->get_settings(null,null,true);

    $sql = "SELECT `Wysiwyg_Panel_ID`, `Name` FROM `Wysiwyg_Panel` " .
        "WHERE `Editor` = 'ckeditor' " .
        "ORDER BY `Wysiwyg_Panel_ID` ASC";
    $panels = (array)$db->get_results($sql, ARRAY_A);

    $panels_select_array = array(
        0 => NETCAT_WYSIWYG_SETTINGS_PANEL_NOT_SELECTED,
    );

    foreach ($panels as $panel) {
        $panels_select_array[$panel['Wysiwyg_Panel_ID']] =
            $panel['Wysiwyg_Panel_ID'] . '. ' . $panel['Name'];
    }

    $editor_type = $settings['EditorType'];

    ?>
    <form method='post' action='index.php'>
        <legend><?= NETCAT_WYSIWYG_SETTINGS_BASIC_SETTINGS ?></legend>
        <table border='0' cellpadding='6' cellspacing='0' width='100%'>
            <tr>
                <td colspan="2">
                    <?php if ($editor_type == 3) { ?>
                        <?= NETCAT_WYSIWYG_SETTINGS_THIS_EDITOR_IS_USED_BY_DEFAULT; ?>
                    <?php } else { ?>
                        <a href="index.php?phase=9&editor_type=3"><?= NETCAT_WYSIWYG_SETTINGS_USE_BY_DEFAULT; ?></a>
                    <?php } ?>

                </td>
            </tr>
            <tr>
                <td>
                    <?= NETCAT_SETTINGS_EDITOR_SKINS ?>:<br>
                    <select name="CKEditorSkin">
                        <?php
                        $dir = $nc_core->ROOT_FOLDER . "editors/ckeditor4/skins/";

                        $settings_skin = $settings['CKEditorSkin'];
                        if (!file_exists($dir . $settings_skin)) {
                            $settings['CKEditorSkin'] = CKEditor::$defaultSkin;
                        }

                        if (is_dir($dir) && $handle = opendir($dir)) {
                            while (($skin = readdir($handle)) !== false) {
                                if (
                                    file_exists($dir . $skin . '/skin.js') ||
                                    file_exists($dir . $skin . '/editor.css') ||
                                    file_exists($dir . $skin . '/dialog.css')
                                ) {
                                    echo "<option value='" . $skin . "' " . ($settings['CKEditorSkin'] == $skin ? "selected" : "") . ">" . $skin . "</option>";
                                }
                            }
                            closedir($handle);
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?= nc_admin_checkbox_simple('InlineEditConfirmation', 1, "" . NETCAT_SETTINGS_INLINE_EDIT_CONFIRMATION . "", $settings['InlineEditConfirmation']) ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?= nc_admin_checkbox_simple('CkeditorEmbedEditor', 1, "" . NETCAT_SETTINGS_EDITOR_EMBED_TO_FIELD . "", $settings['CkeditorEmbedEditor']); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?= nc_admin_checkbox_simple('CKEditorFileSystem', 1, "" . NETCAT_SETTINGS_EDITOR_CKEDITOR_FILE_SYSTEM . "", $settings['CKEditorFileSystem']); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?= nc_admin_checkbox_simple('CKEditorAllowCyrilicFolder', 1, "" . NETCAT_SETTINGS_EDITOR_CKEDITOR_CYRILIC_FOLDER . "", nc_array_value($settings, 'CKEditorAllowCyrilicFolder')); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?= nc_admin_checkbox_simple('CKEditorEnableContentFilter', 1, "" . NETCAT_SETTINGS_EDITOR_CKEDITOR_CONTENT_FILTER . "", nc_array_value($settings, 'CKEditorEnableContentFilter')); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?= NETCAT_SETTINGS_EDITOR_ENTER_MODE ?>:<br>
                    <?php
                    $enter_modes = array(
                        1 => NETCAT_SETTINGS_EDITOR_ENTER_MODE_P,
                        2 => NETCAT_SETTINGS_EDITOR_ENTER_MODE_BR,
                        3 => NETCAT_SETTINGS_EDITOR_ENTER_MODE_DIV,
                    );
                    ?>
                    <?= nc_admin_select_simple('', 'CKEditorEnterMode', $enter_modes, nc_array_value($settings,'CKEditorEnterMode')); ?>
                </td>
            </tr>
        </table>
        <legend><?= NETCAT_WYSIWYG_SETTINGS_PANEL_SETTINGS; ?></legend>
        <table border='0' cellpadding='6' cellspacing='0' width='100%'>
            <tr>
                <td style="width: 200px;">
                    <?= NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_EDITOR_PANEL_FULL; ?>:<br>
                    <?= nc_admin_select_simple('', 'CkeditorPanelFull', $panels_select_array, $settings['CkeditorPanelFull']); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?= NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_EDITOR_PANEL_INLINE; ?>:<br>
                    <?= nc_admin_select_simple('', 'CkeditorPanelInline', $panels_select_array, $settings['CkeditorPanelInline']); ?>
                </td>
            </tr>
        </table>
        <legend><?= NETCAT_WYSIWYG_SETTINGS_CONFIG_JS_SETTINGS; ?></legend>
        <?= nc_admin_textarea(NETCAT_WYSIWYG_SETTINGS_CONFIG_JS_FILE, 'CkeditorConfigFile', file_get_contents($nc_core->INCLUDE_FOLDER . '../editors/ckeditor4/config.js'), 0, 0, 'height: 250px;'); ?>
        <?= $nc_core->token->get_input(); ?>
        <input type="hidden" name="phase" value="2"/>
        <input type="hidden" name="editor" value="ckeditor"/>
    </form>
    <?php
    $UI_CONFIG = new ui_config_wysiwyg(NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_SETTINGS, '#wysiwyg.ckeditor.settings', 'ckeditor-tab', 'ckeditor-settings');
    $UI_CONFIG->actionButtons = array(
        array(
            "id" => "submit",
            "caption" => NETCAT_WYSIWYG_SETTINGS_BUTTON_SAVE,
            "action" => "mainView.submitIframeForm()",
        ),
    );

    return true;
}

/**
 * Вывод формы настроек FCKEditor
 *
 * @return bool
 */
function WysiwygFckeditorSettingsForm() {
    global $nc_core, $db, $UI_CONFIG;

    $settings = $nc_core->get_settings();
    $editor_type = $settings['EditorType'];

    nc_print_status(sprintf(NETCAT_WYSIWYG_EDITOR_OUTWORN, $nc_core->ROOT_FOLDER . 'editors/FCKeditor'), 'info');

    ?>
    <legend><?= NETCAT_WYSIWYG_SETTINGS_BASIC_SETTINGS ?></legend>
    <form method='post' action='index.php'>
        <table border='0' cellpadding='6' cellspacing='0' width='100%'>
            <tr>
                <td colspan="2">
                    <?php if ($editor_type == 2) { ?>
                        <?= NETCAT_WYSIWYG_SETTINGS_THIS_EDITOR_IS_USED_BY_DEFAULT; ?>
                    <?php } else { ?>
                        <a href="index.php?phase=9&editor_type=2"><?= NETCAT_WYSIWYG_SETTINGS_USE_BY_DEFAULT; ?></a>
                    <?php } ?>

                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?= nc_admin_checkbox_simple('FckeditorEmbedEditor', 1, "" . NETCAT_SETTINGS_EDITOR_EMBED_TO_FIELD . "", $settings['FckeditorEmbedEditor']); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?php
                    if ($nc_core->modules->get_by_keyword('filemanager')) {
                        echo "<a href='" . nc_module_path('filemanager') . 'admin.php?page=manager&phase=3&file=' . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "editors/FCKeditor/fckstyles.nc.xml'>" . NETCAT_SETTINGS_EDITOR_STYLES . "</a>";
                    }
                    ?>
                </td>
            </tr>
        </table>
        <?= $nc_core->token->get_input(); ?>
        <input type="hidden" name="phase" value="2"/>
        <input type="hidden" name="editor" value="fckeditor"/>
    </form>
    <?php

    $UI_CONFIG = new ui_config_wysiwyg(NETCAT_WYSIWYG_FCKEDITOR_SETTINGS_TITLE_SETTINGS, '#wysiwyg.fckeditor.settings', 'fckeditor-tab', 'fckeditor-settings');
    $UI_CONFIG->actionButtons = array(
        array(
            "id" => "submit",
            "caption" => NETCAT_WYSIWYG_SETTINGS_BUTTON_SAVE,
            "action" => "mainView.submitIframeForm()",
        ),
    );

    return true;
}

/**
 * Сохранение настроек
 *
 * @return bool
 */
function WysiwygCkeditorSettingsCompleted() {
    global $nc_core;

    $post = $nc_core->input->fetch_get_post();
    $params = array(
        'CKEditorSkin', 'CKEditorEnterMode', 'InlineEditConfirmation', 'CkeditorEmbedEditor',
        'CKEditorFileSystem', 'CkeditorPanelFull', 'CkeditorPanelInline', 'CKEditorAllowCyrilicFolder',
        'CKEditorEnableContentFilter',
    );

    foreach ($params as $param) {
        $nc_core->set_settings($param, nc_array_value($post,$param));
    }

    file_put_contents($nc_core->INCLUDE_FOLDER . '../editors/ckeditor4/config.js', $post['CkeditorConfigFile']);

    return true;
}

/**
 * Сохранение настроек
 *
 * @return bool
 */
function WysiwygFckeditorSettingsCompleted() {
    global $nc_core;

    $post = $nc_core->input->fetch_get_post();
    $params = array('FckeditorEmbedEditor');

    foreach ($params as $param) {
        $nc_core->set_settings($param, $post[$param]);
    }

    return true;
}

/**
 * Вывод списка панелей редактора
 *
 * @return bool
 */
function WysiwygCkeditorPanels() {
    global $db, $UI_CONFIG;

    $sql = "SELECT `Wysiwyg_Panel_ID`, `Name` FROM `Wysiwyg_Panel` WHERE `Editor` = 'ckeditor' ORDER BY `Wysiwyg_Panel_ID` ASC";
    $panels = $db->get_results($sql, ARRAY_A);

    if ($panels) {
        ?>
        <form method='post' action='index.php'>
            <table class='nc-table nc--striped nc--hovered nc--wide'>
                <tr>
                    <th class='nc-text-center nc--compact'>ID</th>
                    <th><?= NETCAT_WYSIWYG_CKEDITOR_SETTINGS_PANEL_NAME; ?></th>
                    <th class='nc-text-center nc--compact'>
                        <i class='nc-icon nc--remove nc--hovered' title='<?= NETCAT_WYSIWYG_CKEDITOR_SETTINGS_DELETE; ?>'></i>
                    </th>
                </tr>
                <?php foreach ($panels as $panel) { ?>
                    <tr>
                        <td><?= $panel['Wysiwyg_Panel_ID']; ?></td>
                        <td>
                            <a href="index.php?phase=5&Wysiwyg_Panel_ID=<?= $panel['Wysiwyg_Panel_ID']; ?>"><?= $panel['Name']; ?></a>
                        </td>
                        <td class='nc-text-center'>
                            <input type='checkbox' name='delete[]' value='<?= $panel['Wysiwyg_Panel_ID']; ?>'>
                        </td>
                    </tr>
                <?php } ?>
            </table>
            <input type='hidden' name='phase' value='7'>
        </form>
    <?php
    } else {
        nc_print_status(NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_NO_PANELS, 'info');
    }

    $UI_CONFIG = new ui_config_wysiwyg(NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_PANELS, '#wysiwyg.ckeditor.panels', 'ckeditor-tab', 'ckeditor-panels');
    $UI_CONFIG->actionButtons = array(
        array(
            "id" => "ckeditor-panel-add",
            "caption" => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_ADD_PANEL,
            "location" => "wysiwyg.ckeditor.panels.add",
            "align" => "left"
        ),
    );

    if ($panels) {
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_DELETE_SELECTED,
            "action" => "mainView.submitIframeForm()",
            "red_border" => true,
        );
    }

    return true;
}

/**
 * Вывод формы подтсерждения удаления
 *
 * @return bool
 */
function DeleteConfirmationForm() {
    global $db, $nc_core, $UI_CONFIG;

    $delete = (array)$nc_core->input->fetch_post('delete');

    foreach ($delete as $index => $item) {
        $delete[$index] = (int)$item;
    }

    $panels = null;
    if (count($delete)) {
        $condition = implode(',', $delete);
        $sql = "SELECT `Wysiwyg_Panel_ID`, `Name` FROM `Wysiwyg_Panel`" .
            "WHERE `Wysiwyg_Panel_ID` IN ({$condition})";
        $panels = $db->get_results($sql, ARRAY_A);
    }

    if ($panels) {
        ?>
        <form action="index.php" method="post">
            <?= NETCAT_WYSIWYG_CKEDITOR_SETTINGS_ARE_YOU_REALLY_WANT_TO_DELETE_PANELS; ?>
            <ul>
                <?php foreach ($panels as $panel) { ?>
                    <li>
                        <input type="hidden" name="delete[]" value="<?= $panel['Wysiwyg_Panel_ID']; ?>"/> <?= $panel['Name']; ?>
                    </li>
                <?php } ?>
            </ul>
            <?= $nc_core->token->get_input(); ?>
            <input type="hidden" name="phase" value="8"/>
        </form>
    <?php
    }

    $UI_CONFIG = new ui_config_wysiwyg(NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_DELETE_CONFIRMATION, '#wysiwyg.ckeditor.panels', 'ckeditor-tab', 'ckeditor-panels');
    $UI_CONFIG->actionButtons = array(
        array(
            "id" => "cancel",
            "caption" => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_CANCEL,
            "location" => "wysiwyg.ckeditor.panels",
            "align" => "left"
        )
    );

    if ($panels) {
        $UI_CONFIG->actionButtons[] = array(
            "id" => "ckeditor-panel-delete",
            "caption" => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_CONFIRM_DELETE,
            "action" => "mainView.submitIframeForm()",
            "red_border" => true,
        );
    }

    return $panels ? true : false;
}

/**
 * Удаление панелей
 *
 * @return bool
 */
function DeletePanels() {
    global $db, $nc_core;

    $delete = (array)$nc_core->input->fetch_post('delete');

    foreach ($delete as $index => $item) {
        $delete[$index] = (int)$item;
    }

    if (count($delete)) {
        $condition = implode(',', $delete);
        $sql = "DELETE FROM `Wysiwyg_Panel`" .
            "WHERE `Wysiwyg_Panel_ID` IN ({$condition})";
        $db->query($sql);
    }

    return true;
}

/**
 * Вывод формы добавления/редактирования панели
 *
 * @param null|int $id
 * @return bool
 */
function PanelForm($id = null) {
    global $db, $nc_core, $UI_CONFIG;
    global $toolbars;

    $panelName = null;

    if ($id !== null) {
        $id = (int)$id;
        $sql = "SELECT `Wysiwyg_Panel_ID`, `Name`, `Toolbars` FROM `Wysiwyg_Panel` " .
            "WHERE `Wysiwyg_Panel_ID` = {$id}";
        $panel = $db->get_row($sql, ARRAY_A);

        if (!$panel) {
            return false;
        }

        $panelName = $panel['Name'];

        $panel['Toolbars'] = (array)@unserialize($panel['Toolbars']);
    } else {
        $panel = array(
            'Name' => '',
            'Toolbars' => array(),
        );
    }

    $post = $nc_core->input->fetch_post();

    if (isset($post['Name'])) {
        $panel['Name'] = $post['Name'];

        if (!isset($post['Toolbars'])) {
            $post['Toolbars'] = array();
            $panel['Toolbars'] = array();
        }
    }

    foreach ($toolbars as $toolbar => $title) {
        if (isset($post['Toolbars']) && isset($post['Toolbars'][$toolbar])) {
            $panel['Toolbars'][$toolbar] = true;
        }

        if (!isset($panel['Toolbars'][$toolbar])) {
            $panel['Toolbars'][$toolbar] = false;
        }
    }

    ?>
    <form action="index.php" method="post">
        <table>
            <tr>
                <td>
                    <?= NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_PANEL_NAME; ?>:<br>
                    <input type="text" name="Name" value="<?= $panel['Name']; ?>" size="70"/><br><br>
                </td>
            </tr>
            <tr>
                <td>
                    <?= NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_SETTINGS; ?>:<br>
                    <?php foreach ($toolbars as $toolbar => $title) { ?>
                        <?= nc_admin_checkbox_simple(('Toolbars[' . $toolbar . ']'), 1, $title, $panel['Toolbars'][$toolbar]); ?>
                        <br>
                    <?php } ?>
                </td>
            </tr>
        </table>
        <?= $nc_core->token->get_input(); ?>
        <input type="hidden" name="phase" value="6"/>
        <?php if ($id) { ?>
            <input type="hidden" name="Wysiwyg_Panel_ID" value="<?= $id; ?>"/>
        <?php } ?>
    </form>
    <legend><?= NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_PANEL_PREVIEW; ?>:<br></legend>
    <?php
    $ckeditor = new CKEditor();
    echo $ckeditor->CreatePanelPreviewHtml();

    $UI_CONFIG = new ui_config_wysiwyg($id ? $panelName : NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TITLE_ADD_FORM,
        $id ? '#wysiwyg.ckeditor.panels.edit(' . $id . ')' : '#wysiwyg.ckeditor.panels.add',
        'ckeditor-tab', 'ckeditor-panels');

    $UI_CONFIG->actionButtons = array(
        array(
            "id" => "submit",
            "caption" => $id ? NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_EDIT_PANEL : NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_ADD_PANEL,
            "action" => "mainView.submitIframeForm()",
        ),
        array(
            "id" => "cancel",
            "caption" => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_BUTTON_CANCEL,
            "location" => "wysiwyg.ckeditor.panels",
            "align" => "left"
        )
    );

    return true;
}

/**
 * Сохранение формы добавления/редактирования
 *
 * @return bool
 */
function PanelFormCompleted() {
    global $db, $nc_core;
    global $errorString;
    global $toolbars;

    $post = $nc_core->input->fetch_post();

    $Wysiwyg_Panel_ID = isset($post['Wysiwyg_Panel_ID']) ? (int)$post['Wysiwyg_Panel_ID'] : 0;

    if (!$post['Name']) {
        $errorString = NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_FILL_PANEL_NAME;
        return false;
    }

    $toolbars_array = array();

    foreach ($toolbars as $toolbar => $title) {
        if (isset($post['Toolbars']) && isset($post['Toolbars'][$toolbar])) {
            $toolbars_array[$toolbar] = true;
        }
    }

    $Name = isset($post['Name']) ? $db->escape($post['Name']) : '';

    if (!count($toolbars_array)) {
        $errorString = NETCAT_WYSIWYG_CKEDITOR_SETTINGS_MESSAGE_SELECT_TOOLBAR;
        return false;
    }

    $toolbars_array = $db->escape(serialize($toolbars_array));

    if ($Wysiwyg_Panel_ID) {
        $sql = "UPDATE `Wysiwyg_Panel` SET " .
            "`Name` = '{$Name}'," .
            "`Toolbars` = '{$toolbars_array}' " .
            "WHERE `Wysiwyg_Panel_ID` = {$Wysiwyg_Panel_ID}";
    } else {
        $sql = "INSERT INTO `Wysiwyg_Panel` (`Name`, `Toolbars`, `Editor`) VALUES ('{$Name}', '{$toolbars_array}', 'ckeditor')";
    }

    $db->query($sql);

    return true;
}

/**
 * Установка текущего редактора
 *
 * @return bool
 */
function ActivateEditor() {
    global $db, $nc_core;

    $editor_type = $nc_core->input->fetch_get('editor_type');

    if ($editor_type) {
        $nc_core->set_settings('EditorType', $editor_type);
    }

    return true;
}

class ui_config_wysiwyg extends ui_config {

    /**
     * @param $header
     * @param $location
     * @param $active_toolbar
     */
    function __construct($header, $location, $active_tab, $active_toolbar) {
        $this->headerText = $header;
        $this->locationHash = $location;
        $this->treeMode = 'sitemap';
        $this->tabs = array(
            array(
                'id' => 'ckeditor-tab',
                'caption' => NETCAT_SETTINGS_EDITOR_CKEDITOR,
                'location' => "wysiwyg.ckeditor.settings",
            ),
        );

        if (nc_Editors::fckeditor_exists()) {
            $this->tabs[] = array(
                'id' => 'fckeditor-tab',
                'caption' => NETCAT_SETTINGS_EDITOR_FCKEDITOR,
                'location' => "wysiwyg.fckeditor.settings",
            );
        } else {
            $this->tabs[] = array(
                'id' => 'dummy-tab',
                'caption' => '',
                'location' => "",
            );
        }

        if ($active_tab == 'fckeditor-tab') {
            $this->activeTab = 'fckeditor-tab';
            $this->toolbar = array(
                array(
                    'id' => 'fckeditor-settings',
                    'caption' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TAB_SETTINGS,
                    'location' => "wysiwyg.fckeditor.settings",
                    'group' => 'grp1',
                ),
            );
        } else {
            $this->activeTab = 'ckeditor-tab';
            $this->toolbar = array(
                array(
                    'id' => 'ckeditor-settings',
                    'caption' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TAB_SETTINGS,
                    'location' => "wysiwyg.ckeditor.settings",
                    'group' => 'grp1',
                ),
                array(
                    'id' => 'ckeditor-panels',
                    'caption' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_TAB_PANELS,
                    'location' => "wysiwyg.ckeditor.panels",
                    'group' => 'grp1',
                ),
            );
        }

        $this->activeToolbarButtons = array($active_toolbar);
    }
}
