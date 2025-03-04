<?php

/**
 * Контейнер для блоков (инфоблок с Class_ID = 0).
 * Выводит блоки с Parent_Sub_Class_ID, равным идентификатору контейнера.
 */
class nc_partial_container extends nc_partial {

    /** @var string префикс комментария (должен быть определён в классе-наследнике */
    protected $partial_comment_id_prefix = 'c';
    /** @var int счётчик вложенных фрагментов с отложенной загрузкой (используется в ID комментария) */
    static protected $partial_last_sequence_number = 0;
    /** @var int счётчик вложенных вызовов (фрагмент внутри фрагмента) */
    static protected $partial_nesting_level = 0;

    /** @var int */
    protected $container_id;

    /**
     * nc_partial_container constructor.
     *
     * @param int $container_id
     * @param array $data
     */
    public function __construct($container_id, array $data = array()) {
        $this->container_id = (int)$container_id;
        $this->set_data($data);
    }

    /**
     * @return string
     */
    public function get_content() {
        $nc_core = nc_core::get_object();
        $is_edit_mode = $nc_core->admin_mode && !$nc_core->inside_admin;

        $container_id = $this->container_id;
        $container_properties = $nc_core->sub_class->get_by_id($container_id);
        $container_has_mixin_settings = $container_properties['Index_Mixin_Preset_ID'] || $container_properties['Index_Mixin_Settings'];
        $sub = $nc_core->subdivision->get_current('Subdivision_ID');
        $area = $container_properties['AreaKeyword'];

        $can_edit = $can_add_blocks_outside = false;
        if ($is_edit_mode) {
            /** @var Permission $perm */
            global $perm;
            $can_edit = $perm->isSubClassAdmin($container_id);
            if ($container_properties['Parent_Sub_Class_ID']) {
                $can_add_blocks_outside = $perm->isSubClassAdmin($container_properties['Parent_Sub_Class_ID']);
            } else if ($container_properties['Subdivision_ID']) {
                $can_add_blocks_outside = $perm->isSubdivisionAdmin($container_properties['Subdivision_ID']);
            } else {
                $can_add_blocks_outside = $perm->isCatalogueAllBlocksAdmin($container_properties['Catalogue_ID']);
            }
        }

        $blocks = $nc_core->sub_class->get_by_container_id($container_id);
        if (!$nc_core->admin_mode) {
            $blocks = array_filter($blocks, function($block) { return $block['Checked']; });
        }

        if (!$blocks && !$can_edit && !$container_has_mixin_settings) {
            return '';
        }

        $container_breakpoints = '';

        $mixins_css_class = 'tpl-container-' . $container_id;
        $mixins_list_css_class = $mixins_css_class . '-list';
        if ($container_has_mixin_settings) {
            nc_tpl_mixin_cache::load_block_data($container_properties['Catalogue_ID'], $container_id);
            $container_breakpoints = $nc_core->sub_class->get_meta($container_id, 'Index_Breakpoints');
        }

        $result =
            '<div class="' . ($is_edit_mode ? 'nc-container ' : '') . 'tpl-container ' . $mixins_css_class . ($blocks ? '' : ' nc--empty') . '"' .
            ' id="' . $container_properties['EnglishName'] . '"' .
            (
                $container_properties['Parent_Sub_Class_ID'] &&
                $nc_core->sub_class->get_meta($container_properties['Parent_Sub_Class_ID'], 'expose_children_object_names')
                    ? ' data-object-name="' . htmlspecialchars($container_properties['Sub_Class_Name'] ?: $container_properties['Class_Name']) . '"'
                    : ''
            ) .
            ($container_breakpoints ? ' data-nc-b="' . $container_breakpoints . '"' : '') .
            '>';

        if ($can_add_blocks_outside) {
            $result .= nc_admin_infoblock_insert_toolbar($sub, $area, $container_properties['Parent_Sub_Class_ID'], 'before', $container_id);
        }

        if ($can_edit) {
            $result .= '<div class="nc-container-border"></div>';
            $result .= nc_AdminCommonMultiBlock($container_id, $sub, false);
        }

        $result .= '<div class="tpl-block-container tpl-block-list">';

        if ($blocks) {
            $result .= '<div class="tpl-block-list-objects ' . $mixins_list_css_class . '" data-object-count="' . count($blocks) . '">';

            foreach ($blocks as $block) {
                $block_id = $block['Sub_Class_ID'];
                $result .= nc_objects_list(0, $block_id, $this->data);
            }

            $result .= '</div>'; // div.tpl-block-list-objects
        } else if ($can_edit) {
            $result .= nc_admin_infoblock_insert_toolbar($sub, $area, $container_id, 'first', 0);
        }

        $result .= '</div>'; // .tpl-block-list

        if ($can_add_blocks_outside) {
            $result .= nc_admin_infoblock_insert_toolbar($sub, $area, $container_properties['Parent_Sub_Class_ID'], 'after', $container_id);
        }

        $result .= '</div>'; // .nc-container

        return $result;
    }

    /**
     * @return int
     */
    protected function get_src() {
        return $this->container_id;
    }

}