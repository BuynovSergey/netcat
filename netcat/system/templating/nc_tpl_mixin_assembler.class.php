<?php

class nc_tpl_mixin_assembler {

    static protected $mixin_settings;
    static protected $already_assembled = array();

    /**
     * @param $mixin_preset_id
     * @return array
     */
    protected static function get_mixin_preset_settings($mixin_preset_id) {
        $mixin_preset_id = (int)$mixin_preset_id;
        if (!$mixin_preset_id) {
            return array();
        }

        if (!isset(self::$mixin_settings[$mixin_preset_id])) {
            $settings = nc_db()->get_var(
                "SELECT `Mixin_Settings` FROM `Mixin_Preset` WHERE `Mixin_Preset_ID` = $mixin_preset_id"
            ) ?: '[]';
            self::$mixin_settings[$mixin_preset_id] = json_decode($settings, true) ?: array();
        }

        return self::$mixin_settings[$mixin_preset_id];
    }

    /**
     * NB: фактически является публичным API, так как используется в шаблоне меню.
     *
     * @param string $block_selector
     * @param string $list_selector
     * @param string $scope_prefix префикс названий элементов в $block_properties, в правильном
     *    регистре, например: 'Index', 'MainArea'
     * @param array $block_properties массив с элементами:
     *      (ScopePrefix)_Mixin_Preset_ID
     *      (ScopePrefix)_Mixin_Settings
     *      (ScopePrefix)_Mixin_BreakpointType
     * @param nc_tpl_asset_collector|null $target
     *      Если null, стили добавляются в <head> страницы (использовалось в шаблоне меню).
     *      Если является экземпляром класса-сборщика стилей и скриптов, то стили добавляются в него, а не на страницу.
     * @return null|string
     */
    public static function assemble($block_selector, $list_selector, $scope_prefix, array $block_properties, $target = null) {
        if ($scope_prefix) {
            $scope_prefix .= '_';
        }

        $mixin_preset_id = nc_array_value($block_properties, $scope_prefix . 'Mixin_Preset_ID');
        $mixin_settings_json = nc_array_value($block_properties, $scope_prefix . 'Mixin_Settings');
        $breakpoint_type = nc_array_value($block_properties, $scope_prefix . 'Mixin_BreakpointType', 'viewport');

        $preset_settings = self::get_mixin_preset_settings($mixin_preset_id);
        $mixin_settings = json_decode($mixin_settings_json, true);
        if (!$preset_settings && !$mixin_settings) {
            return null;
        }

        $nc_core = nc_core::get_object();

        /** @var nc_tpl_asset_collector $collector */
        if ($target instanceof nc_tpl_asset_collector) {
            $collector = $target;
        } else if ($target === null) {
            $collector = $nc_core->page;
        } else {
            trigger_error('Incorrect value for $target argument', E_USER_WARNING);
            return null;
        }
        unset($target);

        $all_block_breakpoints = array();
        $styles = '';
        $scripts = '';
        $requested_assets = array(); // чтобы подключать ассеты только один раз для каждого из миксинов (когда миксин используется для нескольких диапазонов)

        $combined_settings = self::combine_own_settings_with_preset($mixin_settings ?: array(), $preset_settings ?: array());

        // selector[type][breakpoint] = { mixin: 'keyword', settings: {} }
        foreach ($combined_settings as $mixin_selector => $mixin_type_settings) {
            if (strpos($mixin_selector, '&') === false) {
                // Обычный селектор, применяемый к блоку для уточнения его статуса
                // (например: ":hover", ".tpl-layout-card")
                $block_selector_prefix = $block_selector . $mixin_selector;
                $list_selector_prefix = $list_selector . $mixin_selector;
            } else {
                // Селектор миксина с инверсией иерархии (например "html.tpl-alt-accessibility-font-size-big &")
                $block_selector_prefix = str_replace('&', $block_selector, $mixin_selector);
                $list_selector_prefix = str_replace('&', $list_selector, $mixin_selector);
                $mixin_selector = $block_selector_prefix;
            }
            $mixin_selector = addcslashes($mixin_selector, "'");

            foreach ($mixin_type_settings as $mixin_type => $mixin_block_settings) {
                $prev_breakpoint = 0;

                if ($mixin_selector && !empty($combined_settings[''][$mixin_type])) {
                    $mixin_block_settings = self::duplicate_mixin_settings_with_selector($combined_settings[''][$mixin_type], $mixin_block_settings);
                }

                ksort($mixin_block_settings, SORT_NUMERIC);

                foreach ($mixin_block_settings as $breakpoint => $mixin_breakpoint_settings) {
                    $mixin_keyword = $mixin_breakpoint_settings['mixin'];

                    $mixin = nc_tpl_mixin::by_keyword($mixin_type, $mixin_keyword);
                    if (!$mixin) {
                        continue;
                    }

                    $breakpoint_attributes = $breakpoint_type === 'block' ? self::get_block_query($prev_breakpoint, $breakpoint) : '';

                    $block_full_selector = strpos($block_selector_prefix, '^^') !== false
                        ? str_replace('^^', $breakpoint_attributes, $block_selector_prefix)  // указано место вставки атрибутов [data-nc-b*]
                        : $block_selector_prefix . $breakpoint_attributes;                   // если не указано, добавляется в конец селектора

                    if ($list_selector_prefix) {
                        $list_full_selector = strpos($list_selector_prefix, '^^') !== false
                            ? str_replace('^^', $breakpoint_attributes, $list_selector_prefix)  // указано место вставки атрибутов [data-nc-b*]
                            : $list_selector_prefix . $breakpoint_attributes;                   // если не указано, добавляется в конец селектора
                    } else {
                        $list_full_selector = '';
                    }

                    $mixin_settings = nc_array_value($mixin_breakpoint_settings, 'settings', array());

                    $block_styles = $mixin->get_block_styles(
                        $block_full_selector,
                        $list_full_selector,
                        (array)$mixin_settings,
                        $block_properties,
                        $collector
                    );

                    if ($breakpoint_type === 'viewport') {
                        $media_query = self::get_media_query($prev_breakpoint, $breakpoint);
                        if ($media_query) {
                            $block_styles = "@media $media_query {\n$block_styles}\n";
                        }
                    } else if ($breakpoint != nc_tpl_mixin::MAX_BLOCK_WIDTH) { // breakpoint_type === 'block'
                        $all_block_breakpoints[$breakpoint] = $breakpoint;
                    }

                    $styles .= $block_styles;

                    $init_js = $mixin->get('init_js');
                    $destruct_js = $mixin->get('destruct_js');
                    if ($init_js || $destruct_js) {
                        $js_block_selector = str_replace('^^', '', $block_selector);
                        $js_list_selector = str_replace('^^', '', $list_selector);
                        $js_mixin_selector = str_replace('^^', '', $mixin_selector);
                        $scripts .=
                            "nc_mixin_init('$js_block_selector', '$js_list_selector', '$js_mixin_selector', $prev_breakpoint, $breakpoint, " .
                            (trim($init_js, " \r\n\t;") ?: 'null') . ', ' .
                            (trim($destruct_js, " \r\n\t;") ?: 'null') . ', ' .
                            nc_array_json($mixin_settings) . ", '$breakpoint_type');";
                    }

                    if (!isset($requested_assets[$mixin_keyword])) {
                        $requested_assets[$mixin_keyword] = true;
                        $assets = $mixin->get('assets');
                        if (is_array($assets)) {
                            $collector->require_assets($assets);
                        }
                    }

                    $prev_breakpoint = $breakpoint;
                }
            }
        }

        $collector->add_styles($styles);

        asort($all_block_breakpoints);
        $collector->set_meta($scope_prefix . 'Breakpoints', implode(' ', $all_block_breakpoints));

        if ($breakpoint_type === 'block') {
            $collector->require_asset_once('nc_element_queries', array('embed' => true));
        }

        if ($scripts) {
            $collector->require_asset_once('nc_mixin_init', array('embed' => true));
            $collector->add_javascript($scripts);
        }

        return $styles;
    }

    /**
     * Обрабатывает массив с настройками на уровне breakpoint => mixin_settings:
     * дублирует настройки в $settings_with_selector для брейкпоинтов, которые есть в
     * $settings_without_selector, но нет в $settings_with_selector (т. е. разбивает
     * настройки с дополнительным селектором на те поддиапазоны, которые есть в настройках
     * без дополнительного селектора). Это нужно нужно для предсказуемого применения/переопределения
     * миксинов для любых количеств и типов брейкпоинтов.
     *
     * @param array $settings_without_selector  настройки для блока без дополнительного селектора
     * @param array $settings_with_selector     настройки для блока с дополнительным селектором
     * @return array настройки для блока с дополнительным селектором (модифицированный $settings_with_selector)
     */
    protected static function duplicate_mixin_settings_with_selector(array $settings_without_selector, array $settings_with_selector) {
        $all_breakpoints = array_unique(array_merge(
            array_keys($settings_without_selector),
            array_keys($settings_with_selector)
        ));
        rsort($all_breakpoints, SORT_NUMERIC);

        $prev_breakpoint_settings = null;

        foreach ($all_breakpoints as $breakpoint) {
            if (!empty($settings_with_selector[$breakpoint])) {
                $prev_breakpoint_settings = $settings_with_selector[$breakpoint];
            } else if ($prev_breakpoint_settings) {
                $settings_with_selector[$breakpoint] = $prev_breakpoint_settings;
            }
        }

        return $settings_with_selector;
    }

    /**
     * @param int $prev_breakpoint
     * @param int $breakpoint
     * @return string
     */
    protected static function get_block_query($prev_breakpoint, $breakpoint) {
        $block_selector = '';
        if ($prev_breakpoint != 0) {
            $block_selector .= '[data-nc-b1~="' . $prev_breakpoint . '"]';
        }
        if ($breakpoint != nc_tpl_mixin::MAX_BLOCK_WIDTH) {
            $block_selector .= '[data-nc-b2~="' . $breakpoint . '"]';
        }
        return $block_selector;
    }

    /**
     * @param int $prev_breakpoint
     * @param int $breakpoint
     * @return string
     */
    protected static function get_media_query($prev_breakpoint, $breakpoint) {
        $media_queries = array();
        if ($prev_breakpoint != 0) {
            $media_queries[] = '(min-width: ' . $prev_breakpoint . 'px)';
        }
        if ($breakpoint != nc_tpl_mixin::MAX_BLOCK_WIDTH) {
            $media_queries[] = '(max-width: ' . sprintf('%0.2F', $breakpoint - 0.01) . 'px)';
        }
        return join(' and ', $media_queries);
    }

    /**
     * @param array $own_settings
     * @param array $preset_settings
     * @return array
     */
    protected static function combine_own_settings_with_preset(array $own_settings, array $preset_settings) {
        foreach ($preset_settings as $key => $value) {
            if (is_array($value)) {
                if (!isset($own_settings[$key])) {
                    $own_settings[$key] = array();
                }
                $own_settings[$key] = self::combine_own_settings_with_preset($own_settings[$key], $preset_settings[$key]);
            } else if (!isset($own_settings[$key])) {
                $own_settings[$key] = $preset_settings[$key];
            }
        }
        return $own_settings;
    }

}
