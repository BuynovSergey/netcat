<?php

/**
 * Инфоблок, выводящий данные объекта на странице полного вывода объекта
 */
class nc_partial_object_partial extends nc_partial {

    /** @var string префикс комментария (должен быть определён в классе-наследнике */
    protected $partial_comment_id_prefix = 'o';
    /** @var int счётчик вложенных фрагментов с отложенной загрузкой (используется в ID комментария) */
    static protected $partial_last_sequence_number = 0;
    /** @var int счётчик вложенных вызовов (фрагмент внутри фрагмента) */
    static protected $partial_nesting_level = 0;

    protected $infoblock_id;

    protected static $variables = array();

    public function __construct($infoblock_id) {
        $this->infoblock_id = $infoblock_id;
    }

    protected function get_src() {
        return $this->infoblock_id;
    }

    public static function set_object_variables(array $variables) {
        self::$variables = $variables;
    }
    
    public function get_content() {
        $nc_core = nc_core::get_object();

        $nc_is_called_in_wrong_context = $nc_core->page->get_routing_result('action') !== 'full' || !isset(self::$variables['f_Message_ID']);
        if (!$nc_core->admin_mode && $nc_is_called_in_wrong_context) {
            return '';
        }

        ob_start();

        $nc_partial_infoblock_id = $this->infoblock_id; // доступна и используется в шаблоне (public API)
        $nc_infoblock_properties = $nc_core->sub_class->get_by_id($this->infoblock_id);
        $nc_can_edit = $nc_core->admin_mode && !$nc_core->inside_admin && s_auth(self::$variables['current_cc'], 'full', 0);
        $nc_infoblock_has_mixin_settings = $nc_infoblock_properties['Index_Mixin_Preset_ID'] || $nc_infoblock_properties['Index_Mixin_Settings'];
        $nc_component_id = self::$variables['classID'];
        $nc_object_id = self::$variables['f_Message_ID'];
        $nc_area = $nc_core->get_component($nc_component_id)->get_full_page_area_keyword($nc_object_id);
        $nc_infoblock_breakpoints = '';

        $nc_core->page->register_component_usage($nc_infoblock_properties['Class_ID'], $nc_infoblock_properties['Class_Template_ID']);
        $nc_component_css_class = $nc_core->component->get_css_class_name(
            $nc_infoblock_properties['Class_Template_ID'] ?: $nc_infoblock_properties['Class_ID'],
            $nc_infoblock_properties['Class_ID']
        );
        $nc_component_css_selector = '.' . str_replace(' ', '.', $nc_component_css_class);
        $nc_block_id = nc_make_block_id("$this->infoblock_id");

        $nc_mixins_css_class = 'tpl-block-' . $this->infoblock_id;
        if ($nc_infoblock_has_mixin_settings) {
            nc_tpl_mixin_cache::load_block_data($nc_infoblock_properties['Catalogue_ID'], $this->infoblock_id);
            $nc_infoblock_breakpoints = $nc_core->sub_class->get_meta($this->infoblock_id, 'Index_Breakpoints');
        }

        $nc_component_template_type = $nc_core->component->get_by_id($nc_infoblock_properties['Class_Template_ID'] ?: $nc_infoblock_properties['Class_ID'], 'Type');
        if ($nc_infoblock_properties['CustomSettingsTemplate'] && $nc_component_template_type === 'object_partial') {
            $nc_a2f = new nc_a2f($nc_infoblock_properties['CustomSettingsTemplate'], 'CustomSettings', false, $this->infoblock_id, 'class_settings');
            $nc_a2f->set_value($nc_infoblock_properties['CustomSettings']);
            $cc_settings = $nc_a2f->get_values_as_array();
        }

        echo
            '<div class="' . ($nc_can_edit ? 'nc-infoblock ' : '') . $nc_mixins_css_class . '"' .
            (
                $nc_infoblock_properties['Parent_Sub_Class_ID'] &&
                $nc_core->sub_class->get_meta($nc_infoblock_properties['Parent_Sub_Class_ID'], 'expose_children_object_names')
                    ? ' data-object-name="' . htmlspecialchars($nc_infoblock_properties['Sub_Class_Name'] ?: $nc_infoblock_properties['Class_Name']) . '"'
                    : ''
            ) .
            ($nc_infoblock_breakpoints ? ' data-nc-b="' . $nc_infoblock_breakpoints . '"' : '') .
            '>';

        if ($nc_can_edit) {
            echo nc_admin_infoblock_insert_toolbar(0, $nc_area, $nc_infoblock_properties['Parent_Sub_Class_ID'], 'before', $this->infoblock_id);
            echo nc_AdminCommonMultiBlock($this->infoblock_id, 0, false);
        }

        // START OF OBJECT DATA OUTPUT BY TEMPLATE
        extract(self::$variables, EXTR_SKIP);

        echo "<div class='tpl-block-full " . ($admin_mode ? 'nc-infoblock-object ' : '') . $nc_component_css_class . "' id='" . $nc_block_id . "'>";

        if ($nc_is_called_in_wrong_context) {
            echo '<div class="nc-component-template-error">' . NETCAT_MODERATION_ERROR_FULL_PAGE_CONTEXT . '</div>';
        } else {
            $file_class = new nc_tpl_component_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
            $file_class->load(
                $nc_infoblock_properties['Class_Template_ID'],
                $nc_infoblock_properties['File_Path'],
                $nc_infoblock_properties['File_Hash']
            );
            $file_class->include_all_required_assets();

            $nc_parent_field_path = null; // такие шаблоны не наследуют шаблон от основного
            $nc_field_path = $file_class->get_field_path('RecordTemplateFull');

            try {
                if (PHP_MAJOR_VERSION === 5) {
                    nc_check_php_file($nc_field_path);
                }
                if ((include $nc_field_path) === false) {
                    throw new nc_tpl_exception_include($nc_field_path);
                }
            } catch (Throwable $e) { // PHP 7
                $file_class->print_error_message($nc_field_path, $e);
            } catch (Exception $e) { // PHP 5
                $file_class->print_error_message($nc_field_path, $e);
            }
        }

        echo '</div>'; // .nc-block-full

        // END OF OBJECT DATA OUTPUT BY TEMPLATE

        if ($nc_can_edit) {
            echo nc_admin_infoblock_insert_toolbar(0, $nc_area, $nc_infoblock_properties['Parent_Sub_Class_ID'], 'after', $this->infoblock_id);
        }

        echo '</div>'; // .nc-block-X

        return ob_get_clean();
    }
}