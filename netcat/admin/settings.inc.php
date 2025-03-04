<?php

function ShowSettings() {
    global $nc_core, $db, $SYSTEM_NAME, $UI_CONFIG;

    $Array = $nc_core->get_settings();
    ?>
    <table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td>
                <table border=0 cellpadding=6 cellspacing=1 width=100%><tr><td>
                            <table border=0 cellpadding=0 cellspacing=0 width=100%>
                                <tr><td width=70%><?= CONTROL_SETTINGSFILE_BASIC_VERSION
    ?>:</td><td><?= $Array["VersionNumber"]
    ?> <?= $SYSTEM_NAME
    ?></td></tr>
                                <tr><td width=70%><?= CONTROL_SETTINGSFILE_BASIC_REGCODE
    ?>:</td><td><?= $Array["ProductNumber"]
    ?> <?= $PRODUCT_NUMBER
    ?></td></tr>
                            </table>
                        </td></tr><tr><td>
                            <table border=0 cellpadding=0 cellspacing=0 width=100%>
                                <tr><td colspan=2><legend><?= CONTROL_SETTINGSFILE_BASIC_MAIN ?></legend></td></tr>
                    <tr><td width=70%><?= CONTROL_SETTINGSFILE_BASIC_MAIN_NAME ?>:</td><td><?= htmlspecialchars($Array["ProjectName"]) ?></td></tr>
                    <tr>
                        <td width=70%><?= CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE ?>:</td>
                        <td><?
                                        if ($Array["EditDesignTemplateID"]) {
                                            $_editTemplate = $db->get_var("SELECT CONCAT(`Template_ID`, ': ', `Description`)
        FROM `Template`
        WHERE `Template_ID` = '" . intval($Array['EditDesignTemplateID']) . "'");
                                            echo $_editTemplate ? $_editTemplate : CONTROL_TEMPLATE_NONE;
                                        } else {
                                            print CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE_DEFAULT;
                                        }
    ?></td></tr>

                </table>


        <tr><td>

                <table border=0 cellpadding=0 cellspacing=0 width=100%>
                    <tr><td colspan=2><legend><?= CONTROL_SETTINGSFILE_BASIC_EMAILS ?></legend></td></tr>
        <tr><td width=70%><?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FILELD ?>:</td><td>
                <?php
                echo ($Array['UserEmailField'] ? $Array['UserEmailField'] : CONTROL_SETTINGSFILE_BASIC_MAIN_FILEMANAGER_NONE);
                ?></td></tr>
        <tr><td><?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMNAME
                ?>:</td><td><?= $Array["SpamFromName"]
                ?></td></tr>
        <tr><td><?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMEMAIL
                ?>:</td><td><?= $Array["SpamFromEmail"]
                ?></td></tr>
        <tr><td><?= CONTROL_SETTINGSFILE_BASIC_EMAILS_TOEMAIL
                ?>:</td><td><?= $Array["NotificationEmail"]
                ?></td></tr>
    </table>

    </td></tr>


    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_CODEMIRROR ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_CODEMIRROR_EMBEDED
                ?>:</td>
        <td><?= $Array['CMEmbeded'] ? NETCAT_SETTINGS_CODEMIRROR_EMBEDED_ON : NETCAT_SETTINGS_CODEMIRROR_EMBEDED_OFF
                ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_CODEMIRROR_DEFAULT
                ?>:</td>
        <td><?= $Array['CMDefault'] ? NETCAT_SETTINGS_CODEMIRROR_DEFAULT_ON : NETCAT_SETTINGS_CODEMIRROR_DEFAULT_OFF
                ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE
                ?>:</td>
        <td><?= $Array['CMAutocomplete'] ? NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE_ON : NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE_OFF
                ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_CODEMIRROR_HELP
                ?>:</td>
        <td><?= $Array['CMHelp'] ? NETCAT_SETTINGS_CODEMIRROR_HELP_ON : NETCAT_SETTINGS_CODEMIRROR_HELP_OFF
                ?></td>
    </tr>
    </table>
    </td></tr>

    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_TRASHBIN ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_TRASHBIN_USE
                ?>:</td>
        <td><?= $Array['TrashUse'] ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF
                ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_TRASHBIN_MAXSIZE
                ?>:</td>
        <td><?= $Array['TrashLimit']
                ?> <?= NETCAT_SIZE_MBYTES
                ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_EXPORT_USE
                ?>:</td>
        <td><?= $Array['ExportLimitSize']
                ?> <?= NETCAT_SIZE_MBYTES
                ?></td>
    </tr>
    </table>
    </td></tr>

    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_COMPONENTS ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_REMIND_SAVE_INFO ?>:</td>
        <td><?= $Array['RemindSave'] == 1 ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_PACKET_OPERATIONS_INFO ?>:</td>
        <td><?= $Array['PacketOperations'] == 1 ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_TEXTAREA_RESIZE_INFO ?>:</td>
        <td><?= $Array['TextareaResize'] == 1 ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_DISABLE_BLOCK_MARKUP_INFO ?>:</td>
        <td><?= $Array['DisableBlockMarkup'] == 1 ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF ?></td>
    </tr>
    </table>
    </td></tr>

    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_QUICKBAR ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_QUICKBAR_ENABLE
                ?>:</td>
        <td><?= $Array['QuickBar'] == 1 ? NETCAT_SETTINGS_QUICKBAR_ON : NETCAT_SETTINGS_QUICKBAR_OFF
                ?></td>
    </tr>
    </table>
    </td></tr>

    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_SYNTAXEDITOR ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_SYNTAXEDITOR_ENABLE
                ?>:</td>
        <td><?= $Array['SyntaxEditor'] == 1 ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF
                ?></td>
    </tr>
    </table>
    </td></tr>



    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_ALTBLOCKS ?></legend></td>
    </tr>

    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_ALTBLOCKS
                ?>:</td>
        <td><?= $Array['AdminButtonsType'] == 1 ? NETCAT_SETTINGS_ALTBLOCKS_ON : NETCAT_SETTINGS_ALTBLOCKS_OFF
                ?></td>
    </tr>
    </table>
    </td></tr>

    </table></td></tr></table>
    <?php
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTROL_SETTINGSFILE_BASIC_CHANGEDATA,
            "action" => "urlDispatcher.load('system.edit')"
    );
}

function SettingsForm() {
    global $nc_core;
    global $db, $ADMIN_PATH;

    $Array = $nc_core->get_settings(null, null, true, 0);
    ?>

    <form method='post' action='settings.php' style='overflow:hidden' class="nc-form">
        <fieldset>
            <legend><?= CONTROL_SETTINGSFILE_BASIC_MAIN ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= CONTROL_SETTINGSFILE_BASIC_MAIN_NAME
                        ?>:<br>
                        <?= nc_admin_input_simple('ProjectName', $Array["ProjectName"], 70, '', "maxlength='255'")
                        ?><br>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php
                        $tpl = $db->get_results("SELECT `Template_ID` as value,
      CONCAT(`Template_ID`, ': ', `Description`) as description,
      `Parent_Template_ID` as parent
      FROM `Template`
      ORDER BY `Priority`, `Template_ID`", ARRAY_A);
                        if (!empty($tpl)) {
                            ?>
                            <?= CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE ?>:<br>
                            <select name="EditDesignTemplateID">
                                <option value="0"><?= CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE_DEFAULT ?></option>
                                <option></option>
                                <?= nc_select_options($tpl, $Array["EditDesignTemplateID"]); ?>
                            </select>
                            <?php
                        } else {
                            echo CONTROL_TEMPLATE_NONE;
                        }
                        ?>
                    </td>
                </tr>
                <?php
                    //настройки экскурсии будут подключаться только при наличии таблицы
                    if ($db->get_var("SHOW TABLES LIKE 'Excursion'"))
                    {
                        global $AUTH_USER_ID;
                        $excursion_show = $db->get_var("SELECT `ShowNext` 
                                FROM `Excursion`
                                WHERE `User_ID` = $AUTH_USER_ID");
                ?>
                <tr>
                    <td>
                            <?= CONTROL_SETTINGSFILE_SHOW_EXCURSION ?>:<br>
                            <select name="ShowExcursion" id="ShowExcursion">
                                <option <?= ($excursion_show == 0 ? 'selected' :'' );?> value="2">Нет</option>
                                <option <?= ($excursion_show == 1 ? 'selected' :'' );?> value="1">Да</option>
                            </select>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </fieldset>
        <br>
        <fieldset>
            <legend><?= CONTROL_SETTINGSFILE_BASIC_EMAILS ?></legend>
            <table border=0 cellpadding=6 cellspacing=0 width=100%><tr><td>
                        <?= CONTROL_SETTINGSFILE_CHANGE_EMAILS_FIELD
                        ?>:<br>
                        <?php
                        $systable = $db->get_var("SELECT System_Table_ID FROM System_Table WHERE System_Table_Name='User'");

                        $res = $db->get_results("SELECT Field_Name,Description FROM Field WHERE System_Table_ID='" . $systable . "' AND Format LIKE 'email%' ORDER BY Priority", ARRAY_N);

                        if ($count = $db->num_rows) {
                            if ($count == 1) {
                                list($field_id, $field_name) = $res[0];
                                echo "" . $field_name . "<input type=hidden name=UserEmailField value=" . $field_id . ">";
                            } else {
                                echo "<select name=UserEmailField>";
                                foreach ($res as $field) {
                                    list($field_id, $field_name) = $field;
                                    echo "<option " . ($field_id == $Array["UserEmailField"] ? "selected" : "") . " value=" . $field_id . ">" . $field_id . ": " . $field_name;
                                }
                                echo "</select>";
                            }
                        } else {
                            ?>
                            <b><?= CONTROL_SETTINGSFILE_CHANGE_EMAILS_NONE
                            ?></b> (<a href=<?= "" . $ADMIN_PATH . "field/index.php?fs=1&isSys=1&SystemTableID=" . $systable
                            ?>><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_ADD
                            ?></a>)
                        <? } ?></td></tr><tr><td>
                        <?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMNAME ?>:<br>
                        <?= nc_admin_input_simple('SpamFromName', $Array["SpamFromName"], 70, '', "maxlength='255'") ?>
                    </td></tr><tr><td>
                        <?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMEMAIL ?>:<br>
    <?= nc_admin_input_simple('SpamFromEmail', $Array["SpamFromEmail"], 70, '', "maxlength='255'") ?>
                    </td></tr><tr><td>
                        <?= CONTROL_SETTINGSFILE_BASIC_EMAILS_TOEMAIL ?>:<br>
                        <?= nc_admin_input_simple('NotificationEmail', $Array["NotificationEmail"], 70, '', "maxlength='255'") ?>
                    </td></tr>
                <tr>
                    <td>
                        <h3><?= CONTROL_SETTINGSFILE_BASIC_MAIL_TRANSPORT_HEADER ?>:</h3>
                        <?= nc_admin_radio_simple('SpamUseTransport', 'Mail', CONTROL_SETTINGSFILE_BASIC_USE_MAIL, !isset($Array["SpamUseTransport"]) || (isset($Array["SpamUseTransport"]) && $Array["SpamUseTransport"] === 'Mail'), 'UseMail') ?>
                        <?= nc_admin_radio_simple('SpamUseTransport', 'Smtp', CONTROL_SETTINGSFILE_BASIC_USE_SMTP, isset($Array["SpamUseTransport"]) && $Array["SpamUseTransport"] === 'Smtp', 'UseSmtp') ?>
                        <?= nc_admin_radio_simple('SpamUseTransport', 'Sendmail', CONTROL_SETTINGSFILE_BASIC_USE_SENDMAIL, isset($Array["SpamUseTransport"]) && $Array["SpamUseTransport"] === 'Sendmail', 'UseSendmail') ?>
                        <div id="SpamMailWrapper" class="UseTransportWrapper"
                         <?php
                         if (nc_array_value($Array, 'SpamUseTransport', 'Mail') !== 'Mail') {
                             echo 'style="display: none;"';
                         }
                         ?>>
                            <div><?= CONTROL_SETTINGSFILE_BASIC_MAIL_PARAMETERS ?>:<br>
                                <?= nc_admin_input_simple('SpamMailAdditionalParameters', $Array["SpamMailAdditionalParameters"], 32) ?>
                            </div>
                        </div>
                        <div id="SpamSmtpWrapper" class="UseTransportWrapper" <?php if (empty($Array["SpamUseTransport"]) || $Array["SpamUseTransport"] === 'Sendmail' || $Array["SpamUseTransport"] === 'Mail'){echo 'style="display: none;"';}?>>
                            <div><?= CONTROL_SETTINGSFILE_BASIC_SMTP_HOST ?>:<br>
                                <?= nc_admin_input_simple('SpamSmtpHost', nc_array_value($Array, 'SpamSmtpHost', ''), 32) ?>
                            </div>
                            <div><?= CONTROL_SETTINGSFILE_BASIC_SMTP_PORT ?>:<br>
                                <?= nc_admin_input_simple('SpamSmtpPort', nc_array_value($Array, 'SpamSmtpPort', ''), 10) ?>
                            </div>
                            <div>
                                <?= nc_admin_checkbox_simple('SpamSmtpAuthUse', 1, CONTROL_SETTINGSFILE_BASIC_SMTP_AUTH_USE, isset($Array["SpamSmtpAuthUse"]) && $Array["SpamSmtpAuthUse"] == 1) ?>
                            </div>
                            <div><?= CONTROL_SETTINGSFILE_BASIC_SMTP_USERNAME ?>:<br>
                                <?= nc_admin_input_simple('SpamSmtpUser', nc_array_value($Array, 'SpamSmtpUser', ''), 32) ?>
                            </div>
                            <div><?= CONTROL_SETTINGSFILE_BASIC_SMTP_PASSWORD ?>:<br>
                                <?= nc_admin_input_password('SpamSmtpPass', nc_array_value($Array, 'SpamSmtpPass', ''), 32) ?>
                            </div>
                            <div><?= CONTROL_SETTINGSFILE_BASIC_SMTP_ENCRYPTION ?>:<br>
                                <select name="SpamSmtpEncryption">
                                    <option value=""><?= CONTROL_SETTINGSFILE_BASIC_SMTP_NOENCRYPTION ?></option>
                                    <option value="ssl"<?php if (!empty($Array["SpamSmtpEncryption"]) && $Array["SpamSmtpEncryption"] === 'ssl') {echo " selected";} ?>>SSL</option>
                                    <option value="tls"<?php if (!empty($Array["SpamSmtpEncryption"]) && $Array["SpamSmtpEncryption"] === 'tls') {echo " selected";} ?>>TLS</option>
                                </select>
                            </div>
                        </div>
                        <div id="SpamSendmailWrapper" class="UseTransportWrapper" <?php if (empty($Array["SpamUseTransport"]) || $Array["SpamUseTransport"] === 'Smtp' || $Array["SpamUseTransport"] === 'Mail'){echo 'style="display: none;"';}?>>
                            <div><?= CONTROL_SETTINGSFILE_BASIC_SENDMAIL_COMMAND ?>:<br>
                                <?= nc_admin_input_simple('SpamSendmailCommand', nc_array_value($Array, 'SpamSendmailCommand', ''), 32) ?>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <script type='text/javascript'>
                $nc(document).ready(function() {
                    $nc('[name=SpamUseTransport]').click(function () {
                        $nc('.UseTransportWrapper').hide();
                        $nc("#Spam"+$nc(this).val()+"Wrapper").show();
                    });
                });
            </script>
        </fieldset>

        <fieldset>
            <legend>
                <?= NETCAT_SETTINGS_DRAG ?><br>
                <?= nc_admin_select_simple('', 'DragMode', array(
                    'silent' => NETCAT_SETTINGS_DRAG_SILENT,
                    'confirm' => NETCAT_SETTINGS_DRAG_CONFIRM,
                    'disabled' => NETCAT_SETTINGS_DRAG_DISABLED,
                ), $Array["DragMode"]) ?><br>
            </legend>
            <? // здесь же «заодно» обновляем значение для JS ?>
            <script>nc.config('drag_mode', '<?= $Array['DragMode'] ?>');</script>
        </fieldset>

        <fieldset>
            <legend><?= NETCAT_SETTINGS_EDITOR ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'>
						<?
						$kc_block = "<select name='SaveKeycode'>";
						$kc = !empty($Array['SaveKeycode']) ? $Array['SaveKeycode'] : 83;
						for ($i = 65; $i <= 90; $i++):
							$kc_block .= "<option value='" . $i . "'" . ($i == $kc ? ' selected' : '') . ">" . chr($i) . "</option>";
						endfor;
						$kc_block .= "</select>";
						?>
						<?= sprintf(NETCAT_SETTINGS_EDITOR_KEYCODE, $kc_block) ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <fieldset>
            <legend><?= CONTROL_SETTINGSFILE_AUTOSAVE ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <div>
                            <?= nc_admin_checkbox_simple('AutosaveUse', 1, CONTROL_SETTINGSFILE_AUTOSAVE_USE, (isset($Array["AutosaveUse"]) && $Array["AutosaveUse"] == 1 ? true : false)) ?>
                        </div>
                        <div id="AutosaveSettingsWrap" <?php if (empty($Array["AutosaveUse"])) {echo " style='display: none;' ";} ?>>
                            <div>
                                <?= nc_admin_radio_simple('AutosaveType', 'keyboard', CONTROL_SETTINGSFILE_AUTOSAVE_TYPE_KEYBOARD, ($Array["AutosaveType"] == 'keyboard')) ?>
                                <?= nc_admin_radio_simple('AutosaveType', 'timer', CONTROL_SETTINGSFILE_AUTOSAVE_TYPE_TIMER, ($Array["AutosaveType"] == 'timer')) ?>
                            </div>
                            <div id="AutosavePeriodWrap" <?php if (empty($Array["AutosaveType"]) || $Array["AutosaveType"] != 'timer') {echo " style='display: none;' ";} ?>>
                                <?= CONTROL_SETTINGSFILE_AUTOSAVE_PERIOD ?>:<br>
                                    <?= nc_admin_input_simple('AutosavePeriod', $Array["AutosavePeriod"], 10) ?>
                                <br>
                                <?= nc_admin_checkbox_simple('AutosaveNoActive', 1, CONTROL_SETTINGSFILE_AUTOSAVE_NO_ACTIVE, (isset($Array["AutosaveNoActive"]) && $Array["AutosaveNoActive"] == 1 ? true : false)) ?>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <script type='text/javascript'>
                $nc(document).ready(function() {
                    $nc("input[name=AutosaveUse]").change(function () {
                        if ($nc(this).is(':checked')) {
                            $nc('#AutosaveSettingsWrap').show();
                        } else {
                            $nc('#AutosaveSettingsWrap').hide();
                        }
                    });
                    $nc("input[name=AutosaveType]:radio").change(function () {
                        if ($nc(this).val() == 'timer') {
                            $nc('#AutosavePeriodWrap').show();
                        } else {
                            $nc('#AutosavePeriodWrap').hide();
                        }
                    });
                });
            </script>
        </fieldset>        
        <br>
        <fieldset>
            <legend><?= NETCAT_SETTINGS_CODEMIRROR ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td colspan="2">
                        <?= nc_admin_checkbox_simple('CMEmbeded', 1, "" . NETCAT_SETTINGS_CODEMIRROR_EMBEDED . "", $Array['CMEmbeded'])
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?= nc_admin_checkbox_simple('CMDefault', 1, "" . NETCAT_SETTINGS_CODEMIRROR_DEFAULT . "", $Array['CMDefault'], '', $Array['CMEmbeded'] != 1 ? ' disabled' : '')
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?= nc_admin_checkbox_simple('CMAutocomplete', 1, "" . NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE . "", $Array['CMAutocomplete'], '', $Array['CMEmbeded'] != 1 ? ' disabled' : '')
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?= nc_admin_checkbox_simple('CMHelp', 1, "" . NETCAT_SETTINGS_CODEMIRROR_HELP . "", $Array['CMHelp'], '', $Array['CMEmbeded'] != 1 ? ' disabled' : '')
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>
        <script type='text/javascript'>$nc('#CMEmbeded').change(function () {
            var chk = $nc(this).attr('checked');
            $nc('input[name^=\"CM\"]').each(function (i, e) { if($nc(e).attr('id') != 'CMEmbeded') { if(chk) $nc(e).removeAttr('disabled').removeAttr('checked'); else $nc(e).attr('disabled', true); }});
        })</script>

		<fieldset>
            <legend><?= NETCAT_SETTINGS_JS ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= NETCAT_SETTINGS_JS_FUNC_NC_JS ?>:
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('JSLoadjQueryDollar', 1, NETCAT_SETTINGS_JS_LOAD_JQUERY_DOLLAR, $Array['JSLoadjQueryDollar']) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('JSLoadjQueryExtensionsAlways', 1, NETCAT_SETTINGS_JS_LOAD_JQUERY_EXTENSIONS_ALWAYS, $Array['JSLoadjQueryExtensionsAlways']) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('JSLoadModulesScripts', 1, NETCAT_SETTINGS_JS_LOAD_MODULES_SCRIPTS, $Array['JSLoadModulesScripts']) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('MinifyStaticFiles', 1, NETCAT_SETTINGS_MINIFY_STATIC_FILES, $Array['MinifyStaticFiles']) ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <!-- Корзина-->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_TRASHBIN
                        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('TrashUse', 1, "" . NETCAT_SETTINGS_TRASHBIN_USE . "", $Array['TrashUse'])
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= NETCAT_SETTINGS_TRASHBIN_MAXSIZE
                        ?> (<?= NETCAT_SIZE_MBYTES
                        ?>):<br>
                        <?= nc_admin_input_simple('TrashLimit', $Array["TrashLimit"], 70, '', "maxlength='255'")
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <!-- Экспорт/импорт данных-->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_EXPORT
                        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= NETCAT_SETTINGS_EXPORT_USE
                        ?> (<?= NETCAT_SIZE_MBYTES
                        ?>):<br>
                        <?= nc_admin_input_simple('ExportLimitSize', $Array["ExportLimitSize"], 70, '', "maxlength='255'")
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>        

        <!-- Компоненты -->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_COMPONENTS
                        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('RemindSave', 1, NETCAT_SETTINGS_REMIND_SAVE, $Array['RemindSave']); ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('PacketOperations', 1, NETCAT_SETTINGS_PACKET_OPERATIONS, $Array['PacketOperations']); ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('TextareaResize', 1, NETCAT_SETTINGS_TEXTAREA_RESIZE, $Array['TextareaResize']); ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('DisableBlockMarkup', 1, NETCAT_SETTINGS_DISABLE_BLOCK_MARKUP_INFO, $Array['DisableBlockMarkup']); ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <!-- Netcat QuickBar -->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_QUICKBAR
                        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('QuickBar', 1, "" . NETCAT_SETTINGS_QUICKBAR_ENABLE . "", $Array['QuickBar'])
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>
        
        <fieldset>
            <legend><?= CONTROL_SETTINGSFILE_INLINE_IMAGE_CROP ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <div>
                            <?= nc_admin_checkbox_simple('InlineImageCropUse', 1, CONTROL_SETTINGSFILE_INLINE_IMAGE_CROP_USE, (isset($Array["InlineImageCropUse"]) && $Array["InlineImageCropUse"] == 1 ? true : false)) ?>
                        </div>
                        <div id="InlineImageCropSettingsWrap" <?php if (empty($Array["InlineImageCropUse"])) {echo " style='display: none;' ";} ?>>
                            <div><?= CONTROL_SETTINGSFILE_INLINE_IMAGE_CROP_DIMENSIONS ?></div>
                            <?php 
                                $Array['InlineImageCropDimensions'] = unserialize(nc_array_value($Array, 'InlineImageCropDimensions'));
                                $image_crop_to_js = "";
                            ?>
                            <?php if ($Array['InlineImageCropDimensions'] !== false && is_array($Array['InlineImageCropDimensions']) && count($Array['InlineImageCropDimensions']['X']) > 0): ?>
                            <?php foreach ($Array['InlineImageCropDimensions']['X'] as $key => $value): ?>
                            <?php $image_crop_to_js .= "icd.add('".$Array['InlineImageCropDimensions']['X'][$key]."', '".$Array['InlineImageCropDimensions']['Y'][$key]."');\n"; ?>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            <a href="#"><?= NETCAT_MODERATION_BUTTON_ADD ?></a>
                        </div>
                    </td>
                </tr>
            </table>
            <script type='text/javascript'>
                $nc(document).ready(function() {
                    $nc("input[name=InlineImageCropUse]").change(function () {
                        if ($nc(this).is(':checked')) {
                            $nc('#InlineImageCropSettingsWrap').show();
                        } else {
                            $nc('#InlineImageCropSettingsWrap').hide();
                        }
                    });
                    nc_image_crop_dimensions = function() {
                        this.nums = 0;
                        this.div_id = 'image_crop_dimensions';
                    };
                    nc_image_crop_dimensions.prototype = {
                        add: function(valueX, valueY) {
                            this.nums++;
                            
                            if (!valueX)
                                valueX = '';
                            if (!valueY)
                                valueY = '';

                            var con_id = this.div_id + "_con_" + this.nums;
                            $nc('#InlineImageCropSettingsWrap a').before("<div class='image_crop_dimension' id='" + con_id + "'></div>");

                            $nc('#' + con_id).append("<input name='InlineImageCropDimensions[X][]' type='text' value='"+valueX+"' />");
                            $nc('#' + con_id).append("<span>x</span>");
                            $nc('#' + con_id).append("<input name='InlineImageCropDimensions[Y][]' type='text' value='"+valueY+"' />");
                            $nc('#' + con_id).append("<div class='drop' onclick='icd.drop(" + this.nums + ")'><i class='nc-icon nc--remove'></i> " + ncLang.Drop + "</div>");
                            $nc('#' + con_id).append("<div style='clear:both;'></div>");
                        },
                        drop: function(id) {
                            $nc("#" + this.div_id + "_con_" + id).remove();
                        }
                    };
                    icd = new nc_image_crop_dimensions();
                    
                    <?php echo $image_crop_to_js; ?>
                    
                    $nc('#InlineImageCropSettingsWrap a').click(function(e) {
                      e.preventDefault();
                      icd.add();
                    });                    
                });
            </script>
        </fieldset>        
        <br>
        <!-- Syntax Highlighting -->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_SYNTAXEDITOR
        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('SyntaxEditor', 1, "" . NETCAT_SETTINGS_SYNTAXEDITOR_ENABLE . "", $Array['SyntaxEditor'], '', "id='SyntaxEditor'")
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>
        <!-- Syntax Checking -->

        <!-- Token -->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_USETOKEN
                        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('UseTokenAdd', 1, "" . NETCAT_SETTINGS_USETOKEN_ADD . "", $Array['UseToken'] & NC_TOKEN_ADD, '', "id='UseTokenAdd'")
                        ?>
                        <br/>
                        <?= nc_admin_checkbox_simple('UseTokenEdit', 1, "" . NETCAT_SETTINGS_USETOKEN_EDIT . "", $Array['UseToken'] & NC_TOKEN_EDIT, '', "id='UseTokenEdit'")
                        ?>
                        <br/>
                        <?= nc_admin_checkbox_simple('UseTokenDrop', 1, "" . NETCAT_SETTINGS_USETOKEN_DROP . "", $Array['UseToken'] & NC_TOKEN_DROP, '', "id='UseTokenDrop'")
                        ?>
                        <br/>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>


        <fieldset>
            <legend><?= NETCAT_SETTINGS_ALTBLOCKS ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
    <?= nc_admin_checkbox_simple('AdminButtonsType', 1, "" . NETCAT_SETTINGS_ALTBLOCKS_TEXT . "", $Array['AdminButtonsType'], '', "id='AdminButtonsType'") ?>
                    </td>
                </tr>
                <tr>
                    <td>
    <?= nc_admin_textarea("\$f_AdminButtons", "AdminButtons", $Array['AdminButtons'], 1, 0) ?>
                    </td>
                </tr>
                <tr>
                    <td>
    <?= nc_admin_textarea("\$f_AdminCommon", "AdminCommon", $Array['AdminCommon'], 1, 0) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= NETCAT_SETTINGS_ALTBLOCKS_PARAMS ?>:<br>
    <?= nc_admin_input_simple('AdminParameters', $Array["AdminParameters"], 70, '', "maxlength='255'") ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <!-- License
        <fieldset>
          <legend><?= NETCAT_SETTINGS_LICENSE ?></legend>
          <table border='0' cellpadding='6' cellspacing='0' width='100%'>
           <tr>
            <td>
        <?= NETCAT_SETTINGS_LICENSE_PRODUCT ?>:<br>
    <?= nc_admin_input_simple('ProductNumber', $Array["ProductNumber"], 70, '', "id='ProductNumber' maxlength='255'") ?>
            </td>
          </tr>
           <tr>
            <td>
        <?= NETCAT_SETTINGS_LICENSE_CODE
        ?>:<br>
        <?= nc_admin_input_simple('Code', $Array["Code"], 70, '', "id='ProductNumber' maxlength='255'")
        ?>
            </td>
          </tr>
          </table>
        </fieldset>
        <br>-->

        <!-- Proxy -->
        <input type='hidden' name='HttpProxyEnabled' value='0'>
        <fieldset id='nc_settings_proxy'>
            <legend>
                <label>
                    <input type='checkbox' name='HttpProxyEnabled' value='1' <?= nc_array_value($Array, 'HttpProxyEnabled') ? " checked" : "" ?>>
                    <?= NETCAT_SETTINGS_HTTP_PROXY ?>
                </label>
            </legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%' style="display: none">
                <tr><td>
                    <?= NETCAT_SETTINGS_HTTP_PROXY_HOST ?>:<br>
                    <?= nc_admin_input_simple('HttpProxyHost', nc_array_value($Array, 'HttpProxyHost'), 70, '', "maxlength='255'") ?>
                </td></tr>
                <tr><td>
                    <?= NETCAT_SETTINGS_HTTP_PROXY_PORT ?>:<br>
                    <?= nc_admin_input_simple('HttpProxyPort', nc_array_value($Array, 'HttpProxyPort'), 70, '', "maxlength='6'") ?>
                </td></tr>
                <tr><td>
                    <?= NETCAT_SETTINGS_HTTP_PROXY_USER ?>:<br>
                    <?= nc_admin_input_simple('HttpProxyUser', nc_array_value($Array, 'HttpProxyUser'), 70, '', "maxlength='255'") ?>
                </td></tr>
                <tr><td>
                    <?= NETCAT_SETTINGS_HTTP_PROXY_PASSWORD ?>:<br>
                    <?= nc_admin_input_simple('HttpProxyPassword', nc_array_value($Array, 'HttpProxyPassword'), 70, '', "maxlength='255'") ?>
                </td></tr>
            </table>
        </fieldset>
        <script>
            $nc(function() {
                var proxy_checkbox = $nc('input[name=HttpProxyEnabled]');

                function proxy_block_toggle() {
                    $nc('#nc_settings_proxy > table').toggle(proxy_checkbox.is(':checked'));
                };

                proxy_checkbox.change(function() {
                    proxy_block_toggle();
                    if ($nc(this).is(':checked')) {
                        $nc('input[name=HttpProxyHost]').focus();
                    }
                });

                proxy_block_toggle();
            });
        </script>
        <!-- Miscellaneous -->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_MISC ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= NETCAT_SETTINGS_MISC_SECRET_KEY ?>:<br>
                        <?= nc_admin_input_simple('SecretKey', $Array["SecretKey"], 70, '', "maxlength='255'") ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>


        <input type=hidden name=phase value=2>
        <?php echo $nc_core->token->get_input(); ?>
        <?php
        global $UI_CONFIG;
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
                "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                "action" => "mainView.submitIframeForm()"
        );
        ?>
        <input type='submit' class='hidden'>
    </form>
    <?php
}

function SettingsCompleted() {
    $nc_core = nc_Core::get_object();
    global $AUTH_USER_ID;

    if (!$nc_core->input->fetch_get_post('ProjectName')) {
        nc_print_status(CONTROL_SETTINGSFILE_DOCHANGE_ERROR_NAME, 'error');
        #SettingsForm();
        return false;
    }

    $input = $nc_core->input->fetch_get_post();
    $p = array('ProjectName', 'UserEmailField',
            'SpamFromName', 'SpamFromEmail', 'SpamUseTransport',
            'SpamMailAdditionalParameters',
            'SpamSmtpHost', 'SpamSmtpPort', 'SpamSmtpAuthUse', 'SpamSmtpUser', 'SpamSmtpPass', 'SpamSmtpEncryption',
            'SpamSendmailCommand', 'NotificationEmail',
            'AutosaveUse','AutosaveType', 'AutosavePeriod', 'AutosaveNoActive',
            'RemindSave', 'PacketOperations', 'TextareaResize', 'DisableBlockMarkup', 'QuickBar', 'SyntaxEditor', 'AdminButtonsType',
            'InlineImageCropUse',
            'AdminCommon', 'AdminParameters', 'AdminButtons', 'TrashUse', 'TrashLimit', 'ExportLimitSize', 'EditDesignTemplateID',
            'CMEmbeded', 'CMDefault', 'CMAutocomplete', 'CMHelp', 'SaveKeycode',
            'JSLoadjQueryDollar', 'JSLoadjQueryExtensionsAlways', 'JSLoadModulesScripts', 'MinifyStaticFiles'/* , 'ProductNumber', 'Code' */,
            'HttpProxyEnabled', 'HttpProxyHost', 'HttpProxyPort', 'HttpProxyUser', 'HttpProxyPassword',
            'DragMode',
            'SecretKey',
        );

    foreach ($p as $key) {
        $nc_core->set_settings($key, trim(nc_array_value($input, $key)));
    }

    $nc_core->set_settings('UseToken', NC_TOKEN_ADD * nc_array_value($input, 'UseTokenAdd') + NC_TOKEN_EDIT * nc_array_value($input,'UseTokenEdit') + NC_TOKEN_DROP * nc_array_value($input, 'UseTokenDrop'));

    if (isset($input['InlineImageCropUse']) && $input['InlineImageCropUse'] == 1) {
        if (!empty($input['InlineImageCropDimensions'])) {
            foreach ($input['InlineImageCropDimensions']['X'] as $key => $value) {
                $input['InlineImageCropDimensions']['X'][$key] = intval($input['InlineImageCropDimensions']['X'][$key]);
                $input['InlineImageCropDimensions']['Y'][$key] = intval($input['InlineImageCropDimensions']['Y'][$key]);
                if ($input['InlineImageCropDimensions']['X'][$key] == 0 && $input['InlineImageCropDimensions']['Y'][$key] == 0) {
                    unset($input['InlineImageCropDimensions']['X'][$key]);
                    unset($input['InlineImageCropDimensions']['Y'][$key]);
                    $input['InlineImageCropDimensions']['X'] = array_values($input['InlineImageCropDimensions']['X']);
                    $input['InlineImageCropDimensions']['Y'] = array_values($input['InlineImageCropDimensions']['Y']);
                }
            }
            if (count($input['InlineImageCropDimensions']) > 0) {
                $nc_core->set_settings('InlineImageCropDimensions', serialize($input['InlineImageCropDimensions']));
            } else {
                $nc_core->set_settings('InlineImageCropDimensions', "");
            }
        } else {
            $nc_core->set_settings('InlineImageCropDimensions', "");
        }
    }
    
    if ((int)$input['ShowExcursion'] === 1) {
        $nc_core->db->query("INSERT INTO `Excursion` (`User_ID`, `ShowNext`) VALUES ($AUTH_USER_ID, 1) ON DUPLICATE KEY UPDATE `ShowNext`= 1");
         
    }
    else if ((int)$input['ShowExcursion'] === 2) {
        $nc_core->db->query("INSERT INTO `Excursion` (`User_ID`, `ShowNext`) VALUES ($AUTH_USER_ID, 0) ON DUPLICATE KEY UPDATE `ShowNext`= 0");
    }
    
    return true;
}
