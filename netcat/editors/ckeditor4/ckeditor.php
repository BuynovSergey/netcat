<?php

class CKEditor {

    const SINGLE_LINE = 1;
    const NO_TOOLBAR = 2;
    const NO_HTML = 4;

    static $toolbarNames = array(
        'clipboard' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_CLIPBOARD,
        'tools' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_TOOLS,
        'undo' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_UNDO,
        'find' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_FIND,
        'selection' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_SELECTION,
        'forms' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_FORMS,
        'basicstyles' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_BASICSTYLES,
        'cleanup' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_CLEANUP,
        'list' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_LIST,
        'indent' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_INDENT,
        'blocks' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_BLOCKS,
        'align' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_ALIGN,
        'links' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_LINKS,
        'insert' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_INSERT,
        'styles' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_STYLES,
        'colors' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_COLORS,
    );

    static $defaultSkin = 'moono';

    private $name;

    private $value;

    private $panel;

    private $basePath;
    private $language;

    static protected $id_prefix;
    static protected $last_inline_id = 1;

    public function __construct($name = null, $value = null, $panel = 0) {
        $this->name = $name;
        $this->value = $value;
        $this->panel = $panel;

        if (!self::$id_prefix) {
            self::$id_prefix = rand(0, PHP_INT_MAX);
        }

        $nc_core = nc_Core::get_object();
        $this->basePath = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'editors/ckeditor4/';

        $language = $nc_core->lang->detect_lang(1);
        $this->language = $language == 'ru' ? 'ru' : 'en';
    }

    protected function getConfigModificationTime() {
        return (int)filemtime($_SERVER['DOCUMENT_ROOT'] . $this->basePath . 'config.js');
    }

    protected function getScriptLoader() {
        return "
            if (typeof CKEDITOR === 'undefined') {
                var CKEDITOR_BASEPATH = '{$this->getBasePath()}';
                nc.load_script('{$this->getScriptPath()}');
            }\n";

    }

    public function CreateHtml() {
        $html = "";

        static $initComplete;

        if (!$initComplete) {
            $initComplete = true;

            if (nc_core()->admin_mode) {
                $html .= "<script type='text/javascript'>" .
                            $this->getScriptLoader() .
                            $this->getInstanceReadyHandler() .
                         "</script>\n";
            }
            else {
                $html .= "<script type='text/javascript'>var CKEDITOR_BASEPATH = '" . $this->getBasePath() . "';</script>";
                $html .= "<script type='text/javascript' src='" . $this->getScriptPath() . "'></script>";
                $html .= "<script type='text/javascript'>{$this->getInstanceReadyHandler()}</script>";
            }
        }

        $toolbars = $this->loadToolbarsConfig('CkeditorPanelFull');
        $defaultConfig = $this->getDefaultConfig();

        $html .= "<textarea class='no_cm' name=\"{$this->name}\" id=\"{$this->name}\">" . htmlspecialchars($this->value) . "</textarea>\n";
        $html .= "<script type=\"text/javascript\">try {CKEDITOR.replace('{$this->name}', {{$toolbars}{$defaultConfig}});} catch (exception) {}</script>\n";

        return $html;
    }

    public function getBasePath() {
        return $this->basePath;
    }

    public function getScriptPath() {
        return nc_add_revision_to_url($this->basePath . 'ckeditor.js');
    }

    public function getFileManagerPath() {
        global $perm;
        $path = $this->basePath . 'filemanager/index.php';

        $split_users = nc_Core::get_object()->get_settings('CKEditorFileSystem');

        if ($split_users && $perm && $perm->isSupervisor()) {
            $path .= '?expandedFolder=' . $perm->GetUserID() . '/';
        }

        return $path;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function getWindowFormScript() {
        $toolbars = $this->loadToolbarsConfig('CkeditorPanelFull');
        $defaultConfig = $this->getDefaultConfig();

        return "try {CKEDITOR.replace('nc_editor', {{$toolbars}{$defaultConfig}});} catch (exception) {}\n";
    }

    /**
     * @param $title
     * @param $value
     * @param $save_url
     * @param $save_data
     * @param $new_value_property
     * @param int $flags битовая маска (константы SINGLE_LINE, NO_TOOLBAR, NO_HTML)
     * @return string
     * @throws Exception
     */
    public function getInlineScript($title, $value, $save_url, $save_data, $new_value_property, $flags = 0) {
        $nc_core = nc_Core::get_object();

        $html = '';
        $nc = '$nc';
        $escaped_title = htmlspecialchars(strip_tags($title), ENT_QUOTES);

        $single_line = $flags & self::SINGLE_LINE;
        $hide_toolbar = $flags & self::NO_TOOLBAR;
        $no_html = $flags & self::NO_HTML;

        static $init_complete;

        if (!$init_complete) {
            $init_complete = true;
            $confirmation_dialog_id = 'nc_ckeditor_inline_confirmation_dialog';

            $html .= "<script type='text/javascript'>" .
                        $this->getScriptLoader() .
                     "\nCKEDITOR.disableAutoInline = true;\n" .
                        $this->getInstanceReadyHandler() .
                     "</script>\n";

            if ($nc_core->get_settings('InlineEditConfirmation')) {
                $html .=
                    "<div class='nc-modal-dialog' data-width='300' data-height='100' style='display: none' id='$confirmation_dialog_id'>
                        <div class='nc-modal-dialog-header'>
                            <h2>" . NETCAT_MODERATION_APPLY_CHANGES_TITLE ."</h2>
                        </div>
                        <div class='nc-modal-dialog-body'>
                            " . NETCAT_MODERATION_APPLY_CHANGES_TEXT . "
                        </div>
                        <div class='nc-modal-dialog-footer'>
                            <button class='nc-ckeditor-inline-confirm nc--blue'>
                                " . NETCAT_REMIND_SAVE_SAVE . "
                            </button>
                            <button class='nc-ckeditor-inline-reject nc--bordered nc--red'>
                                " . CONTROL_BUTTON_CANCEL . "
                            </button>
                        </div>
                    </div>";

                $html .=
                    "<script>
                        function nc_ckeditor_show_confirmation_dialog(div_id, url, data) {
                            var dialog = new parent.nc.ui.modal_dialog({
                                    full_markup: $nc('#$confirmation_dialog_id')
                                                    .clone()
                                                    .attr({id: null, style: ''})
                                }),
                                div = $nc('#' + div_id);

                            dialog.open();

                            // change confirmed
                            dialog.find('button.nc-ckeditor-inline-confirm').click(function() {
                                $nc.ajax({
                                    method: 'post',
                                    url: url,
                                    data: data,
                                    success: function(response) {
                                        var error = nc_check_error(response);
                                        if (error) {
                                            dialog.show_error(error);
                                            return;
                                        }
                                        div.data('oldValue', CKEDITOR.instances[div_id].getData());
                                        dialog.close();
                                        $nc(document).trigger('ncContentUpdate');
                                    }
                                });
                            });

                            // change rejected
                            dialog.find('button.nc-ckeditor-inline-reject').click(function() {
                                div.html(div.data('oldValue'));
                                dialog.close();
                            });

                        }
                    </script>";
            }
        }

        if ($hide_toolbar) {
            $toolbars = "floatSpaceDockedOffsetX: -1000, toolbar: [],";
        } else {
            $toolbars = $this->loadToolbarsConfig('CkeditorPanelInline');
        }
        $default_config = $this->getDefaultConfig();

        $div_id = 'ckeditor_inline_' . (self::$id_prefix) . '_' . (self::$last_inline_id++);

        $html .= "<div id='$div_id'  class='nc-ckeditor-inline" .
                 ($single_line ? " nc-ckeditor-inline-single-line" : "") .
                 "' placeholder='$escaped_title' style='display: inline-block;' contenteditable='true'>" .
                 $value .
                 "</div>";

        $config_override = array();

        if ($single_line) {
            $config_override[] = 'enterMode: CKEDITOR.ENTER_BR';
            $config_override[] = 'autoParagraph: false';
        }

        if ($no_html) {
            // пробуем запретить HTML
            $config_override[] = "allowedContent: ''";
            $config_override[] = "disallowedContent: 'br ol ul td th'"; // всегда разрешены в .filter
            $config_override[] = "pasteFilter: 'nothing'"; // 'plain-text' разрешает <br>
            // игнорировать нажатие ENTER и SHIFT+ENTER;
            // on key: игнорировать нажатие ENTER и SHIFT+ENTER;
            // on afterPaste: убирать <br> (pasteFilter не срабатывает при копировании из другого редактора на странице)
            $config_override[] = "on: { 
                    key: function(event) {
                        if (single_line && (event.data.keyCode & ~CKEDITOR.SHIFT) === 13) { 
                            event.cancel();
                        }
                    }, 
                    afterPaste: function(event) {
                        var e = event.editor;
                        e.setData(e.getData().replace(/<br \/>\\n/g, ''));
                    }
                }";
        }

        $html .= "<script>
            $nc(function() {
                var div = $nc('#$div_id'),
                    require_confirmation = " . (int)$nc_core->get_settings('InlineEditConfirmation') . ",
                    single_line = " . (int)$single_line . ",
                    parent = nc.get_closest_editor_parent(div),
                    remove_parent_active_class = function() {
                        parent.removeClass('nc--with-active-editor');
                    };

                div.data('oldValue', div.html());

                function save_value(synchronously) {
                    var instance = CKEDITOR.instances.$div_id,
                        new_value = instance.getData(),
                        old_value = div.data('oldValue');

                    // hack to remove enclosing <p></p> which is added for empty fields
                    if (single_line && (new_value.match(/<p>/g) || []).length == 1) {
                        new_value = new_value.replace(/^<p>/, '').replace(/<\\/p>$/, '');
                        instance.setData(new_value);
                    }

                    if (new_value == old_value) {
                        remove_parent_active_class();
                        return;
                    }

                    var data = {'$new_value_property': new_value};
                    " . ($save_data ? "data = $nc.extend(" . nc_array_json($save_data) . ", data);" : "") . "

                    if (require_confirmation) {
                        nc_ckeditor_show_confirmation_dialog('$div_id', '$save_url', data);
                    }
                    else if (synchronously && navigator.sendBeacon) {
                        navigator.sendBeacon(url, data);
                        $nc(document).trigger('ncContentUpdate');
                    }
                    else {
                        $nc.ajax({ method: 'post', url: '$save_url', data: data, async: !synchronously })
                            .success(function(response) {
                                var error = nc_check_error(response);
                                if (error) {
                                    alert(error);
                                    instance.setData(old_value);
                                } else {
                                    $nc(document).trigger('ncContentUpdate');
                                }
                            })
                            .always(remove_parent_active_class);
                    }

                    div.data('oldValue', new_value);
                }
                
                function toggle_editor(editor) {
                    var should_enable = nc_should_show_controls(div);
                    var toolbar = $nc('#cke_' + editor.name);
                    if (should_enable) { 
                        editor.setReadOnly(false);
                        toolbar.css('transform', 'none');
                    } else {
                        editor.setReadOnly(true);
                        toolbar.css('transform', 'translateX(-100vw)')
                    }
                }

                try {
                    CKEDITOR.inline('$div_id', $nc.extend(true, {
                            {$toolbars}
                            {$default_config}
                            floatSpaceDockedOffsetY: 56,
                            title: '$escaped_title',
                            on: {
                                instanceReady: function(e) {
                                    div.mouseover(function() {
                                        toggle_editor(e.editor);
                                    });
                                },
                                focus: function(e) {
                                    toggle_editor(e.editor);
                                    parent.addClass('nc--with-active-editor');
                                    $nc(window).on('beforeunload.save_inline_ckeditor', function() {
                                        save_value(true);
                                    });
                                },
                                blur: function() {
                                    save_value();
                                    $nc(window).off('beforeunload.save_inline_ckeditor');
                                }
                            }
                        },
                        {" . join(", ", $config_override) . "}
                        )
                    );
                } catch (e) {
                    console.log(e);   
                }
            });
        </script>\n";

        return $html;
    }

    public function loadToolbarsConfig($setting_name) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $panel_id = $this->panel ? $this->panel : (int)$nc_core->get_settings($setting_name);

        $sql = "SELECT `Toolbars`, `RemoveButtons` FROM `Wysiwyg_Panel` WHERE `Wysiwyg_Panel_ID` = {$panel_id}";
        $result = $db->get_row($sql, ARRAY_A);

        $toolbars = @unserialize($result['Toolbars']);
        $removeButtons = $result['RemoveButtons'];

        $toolbarNames = self::$toolbarNames;

        if (is_array($toolbars)) {
            $jsonArray = array('mode');
            foreach ($toolbarNames as $toolbar => $title) {
                if (!empty($toolbars[$toolbar])) {
                    $jsonArray[] = array(
                        'name' => $toolbar,
                    );
                }
            }

            $config = 'toolbarGroups: ' . json_encode($jsonArray) . ',';
            if (strlen(trim($removeButtons))) {
                $config .= "removeButtons: '$removeButtons',";
            }
            return $config;
        }

        return '';
    }

    public function loadSkinConfig() {
        $nc_core = nc_Core::get_object();
        $skin = $nc_core->get_settings('CKEditorSkin');

        $dir = $nc_core->ROOT_FOLDER . "editors/ckeditor4/skins/";

        if (!$skin || !file_exists($dir . $skin)) {
            $skin = self::$defaultSkin;
        }

        return $skin;
    }

    public function getPreviewFunctions() {
        ?>
        <script type="text/javascript">
            function nc_update_ckeditor_toolbars_preview(instance_name, toolbars) {
                for(var i in toolbars) {
                    alert(i);
                }
            }
        </script>
    <?php
    }

    public function CreatePanelPreviewHtml() {
        $html = "";

        static $initComplete;

        if (!$initComplete) {
            $initComplete = true;

            $html .= "<script type='text/javascript'>var CKEDITOR_BASEPATH = '" . $this->getBasePath() . "';</script>";
            $html .= "<script type='text/javascript' src='" . $this->getScriptPath() . "'></script>";
        }

        $defaultConfig = $this->getDefaultConfig();

        $html .= "<textarea class='no_cm' name=\"\" id=\"preview\"></textarea>\n";
        $html .= "<script type=\"text/javascript\">
    var nc_default_ckeditor_toolbars = " . json_encode(array_keys(self::$toolbarNames)) . ";
    function nc_update_ckeditor_toolbars_preview() {
        var toolbars = ['mode'];

        for (var i in nc_default_ckeditor_toolbars) {
            var toolbar = nc_default_ckeditor_toolbars[i];
            if (\$nc('INPUT[name=\"Toolbars[' + toolbar + ']\"]').is(':checked')) {
                toolbars[toolbars.length] = toolbar;
            }
        }

        try {
            if (typeof(CKEDITOR.instances['preview']) != 'undefined') {
                CKEDITOR.instances['preview'].destroy();
            }
            CKEDITOR.replace('preview', {
                toolbarGroups: toolbars,
                {$defaultConfig}
            });
        } catch (exception) {
        }
    }

    \$nc(function(){
        nc_update_ckeditor_toolbars_preview();

        \$nc('INPUT[name^=Toolbars]').on('change', function(){
            nc_update_ckeditor_toolbars_preview();
        });
    });
</script>\n";

        return $html;
    }

    public function getDefaultConfig() {
        $nc_core = nc_Core::get_object();

        $skin = $this->loadSkinConfig();
        $enterMode = (int)$nc_core->get_settings('CKEditorEnterMode') ?: 'CKEDITOR.ENTER_P';

        $result = "skin: '{$skin}',
language: '" . $this->getLanguage() . "',
filebrowserBrowseUrl:  '" . $this->getFileManagerPath() . "',\n" .
($nc_core->get_settings('CKEditorEnableContentFilter') ? "" : "allowedContent: true,\n") .
"entities: true,
autoParagraph: false,
fillEmptyBlocks: false,
enterMode: {$enterMode},
baseFloatZIndex: 11000,";

        return $result;
    }

    public function getInstanceReadyHandler() {
        $html = "CKEDITOR.config.customConfig = 'config.js?t={$this->getConfigModificationTime()}';
CKEDITOR.on('instanceReady', function(ev) {    
    for(var tag in CKEDITOR.dtd.\$block) {
        ev.editor.dataProcessor.writer.setRules(tag, {
            indent: false,
            breakBeforeOpen: true,
            breakAfterOpen: false,
            breakBeforeClose: false,
            breakAfterClose: false
        });
    }
});";

        return $html;
    }

}
