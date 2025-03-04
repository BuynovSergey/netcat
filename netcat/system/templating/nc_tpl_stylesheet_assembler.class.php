<?php

/**
 * Собирает общий файл стилей компонентов для сайта.
 */
class nc_tpl_stylesheet_assembler {

    /**
     * Shortcut for (new nc_tpl_stylesheet_assembler)->assemble();
     *
     * @param int $site_id
     * @param int[] $component_template_ids
     * @return null|array [ 'path' => '', 'container_breakpoints' => '', 'list_object_breakpoints' => '' ]
     */
    public static function get_site_component_styles($site_id, array $component_template_ids) {
        $compiler = new self;
        return $compiler->assemble($site_id, $component_template_ids);
    }

    /**
     *
     */
    public function __construct() {
    }

    /**
     * Собирает CSS-файл со стилями для компонентов для указанного сайта.
     * Пересборка осуществляется только если файлы стилей компоненты изменились
     * с момента предыдущей пересборки или собранный в прошлый раз файл стилей
     * содержит стили не для всех компонентов.
     *
     * Возвращает путь к файлу стилей компонентов от корня сайта,
     * или null, если файл стилей пуст.
     * Путь в query-части содержит timestamp последней пересборки файла
     * для снижения вероятности проблем из-за кэширования.
     *
     * @param int $site_id
     * @param int[] $component_template_ids
     * @return null|array [ 'path' => '', 'breakpoints' => [] ]
     */
    public function assemble($site_id, array $component_template_ids) {
        if (!$component_template_ids) {
            return null;
        }

        $site_id = (int)$site_id;

        array_walk_recursive($component_template_ids, function(&$item) {
            $item = (int)$item;
        });

        $template_ids = array_map('intval', array_keys($component_template_ids));

        $nc_core = nc_core::get_object();
        $db = $nc_core->db;

        $css_folder = $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_CACHE_PATH . 'css/' . $site_id;
        $css_relative_path = $css_folder . '/components.css';
        $css_absolute_path = $nc_core->DOCUMENT_ROOT . $css_relative_path;

        $tmp_path = $nc_core->DOCUMENT_ROOT . $css_folder . '/components';
        if (!file_exists($tmp_path)) {
            mkdir($tmp_path, $nc_core->DIRCHMOD, true);
        }

        $template_styles_info = (array)$db->get_results(
            "SELECT CONCAT_WS('-', `Class_ID`, `Class_Template_ID`) AS `key`, 
                    `LastUpdate`,
                    `BlockSelector` AS `block_selector`,
                    `ContainerBreakpoints` AS `container_breakpoints`,
                    `ListObjectBreakpoints` AS `list_object_breakpoints`
               FROM `Class_StyleCache`
              WHERE `Catalogue_ID` = $site_id
                AND `Class_Template_ID` IN (" . implode(',', $template_ids) . ")",
            ARRAY_A, 'key'
        );

        $has_changes = false;
        $has_styles = false;

        foreach ($component_template_ids as $template_id => $components) {
            $template_source_css_file =
                rtrim($nc_core->CLASS_TEMPLATE_FOLDER, '/') .
                $nc_core->component->get_by_id($template_id, 'File_Path') .
                'SiteStyles.css';

            foreach ($components as $component_id) {
                if ($template_id == $component_id) {
                    $component_source_css_file = $template_source_css_file;
                } else {
                    $component_source_css_file =
                        rtrim($nc_core->CLASS_TEMPLATE_FOLDER, '/') .
                        $nc_core->component->get_by_id($component_id, 'File_Path') .
                        'SiteStyles.css';
                }

                $key = "$component_id-$template_id";
                $template_processed_css_file = "$tmp_path/$key.css";

                // Файлы SiteStyles.css не существуют, когда не заданы стили в шаблоне компонента или в самом компоненте
                // Нужно убедиться, что стилей нет нигде: ни в шаблоне компонента, ни в самом компоненте, так как они наследуются
                if (!file_exists($template_source_css_file) && !file_exists($component_source_css_file)) {
                    if (isset($template_styles_info[$key])) {
                        if (file_exists($template_processed_css_file)) {
                            unlink($template_processed_css_file);
                        }
                        $db->query("DELETE FROM `Class_StyleCache` WHERE `Catalogue_ID` = $site_id AND `Class_ID` = $component_id AND `Class_Template_ID` = $template_id");
                        $has_changes = true;
                    }
                    continue;
                }

                // При отсутствии стилей в шаблоне компонента они будут взяты из самого компонента
                if (file_exists($template_source_css_file)) {
                    $target_source_css_file = $template_source_css_file;
                } else {
                    $target_source_css_file = $component_source_css_file;
                }

                $has_styles = true;
                $template_timestamp = filemtime($target_source_css_file);
                if (
                    !isset($template_styles_info[$key]) ||
                    $template_styles_info[$key]['LastUpdate'] < $template_timestamp ||
                    !file_exists($template_processed_css_file)
                ) {
                    $has_changes = true;

                    $properties = $this->assemble_component_css($target_source_css_file, $template_processed_css_file, $template_id, $component_id);
                    $template_styles_info[$key] = $properties;

                    $values = "`LastUpdate` = $template_timestamp,
                               `BlockSelector` = '" . $db->escape($properties['block_selector']) . "',
                               `ContainerBreakpoints` = '$properties[container_breakpoints]',
                               `ListObjectBreakpoints` = '$properties[list_object_breakpoints]'";

                    $db->query(
                        "INSERT INTO `Class_StyleCache`
                            SET `Catalogue_ID` = $site_id,
                                `Class_ID` = $component_id,
                                `Class_Template_ID` = $template_id,
                                $values
                            ON DUPLICATE KEY UPDATE $values"
                    );
                }
            }
        }

        if ($has_styles) {
            if ($has_changes || !file_exists($css_absolute_path)) {
                $this->assemble_site_css($tmp_path, $css_absolute_path);
            }

            $breakpoints = array();
            foreach ($template_styles_info as $template) {
                $block_selector = $template['block_selector'];
                if ($template['container_breakpoints']) {
                    $breakpoints[$block_selector] = $template['container_breakpoints'];
                }
                if ($template['list_object_breakpoints']) {
                    $breakpoints["$block_selector .tpl-block-list-objects > *"] = $template['list_object_breakpoints'];
                }
            }

            return array(
                'path' => $css_relative_path . '?' . filemtime($css_absolute_path),
                'breakpoints' => $breakpoints,
            );
        }

        return null;
    }

    /**
     * Обрабатывает файл $source_file (добавляет класс блока и делает пути абсолютными)
     * и записывает результат в $destination_file
     * @param string $source_file
     * @param string $destination_file
     * @param int $template_id
     * @param int $component_id
     * @return array ['selector' => '', 'container_breakpoints' => '', 'list_object_breakpoints' => '']
     */
    protected function assemble_component_css($source_file, $destination_file, $template_id, $component_id = 0) {
        $nc_core = nc_core::get_object();
        $stylesheet = nc_tpl_stylesheet::from_file($source_file);

        $block_class = str_replace(' ', '.', $nc_core->component->get_css_class_name($template_id, $component_id));
        $url_prefix =
            $nc_core->SUB_FOLDER .
            $nc_core->HTTP_TEMPLATE_CACHE_PATH .
            'class' .
            $nc_core->component->get_by_id($template_id, 'File_Path');

        $result = $stylesheet->transform($block_class, $url_prefix);
        file_put_contents($destination_file, $result['declarations']);

        return array(
            'block_selector' => ".$block_class",
            'container_breakpoints' => implode(' ', $result['container_breakpoints']),
            'list_object_breakpoints' => implode(' ', $result['list_object_breakpoints']),
        );
    }

    /**
     * Склеивает все файлы .css из $source_path в файл $target_file
     * @param $source_path
     * @param $target_file
     */
    protected function assemble_site_css($source_path, $target_file) {
        $fh = fopen($target_file, 'wb');
        foreach (glob("$source_path/*.css") as $file) {
            fputs($fh, file_get_contents($file));
        }
        fclose($fh);
    }

}