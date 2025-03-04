<?php

/**
 * Вспомогательный класс для получения списка шрифтов в assets (библиотеки,
 * ключевое слово которых начинается на 'font_').
 * Используется в редакторе настроек миксинов.
 */

class nc_tpl_font {

    /**
     * Список «распространённых» generic-шрифтов
     * @return array
     */
    protected static function get_generic_font_data() {
        return array(
            array('asset' => '', 'name' => 'Arial', 'fallback' => 'Helvetica, sans-serif', 'css' => array()),
            array('asset' => '', 'name' => 'Courier New', 'fallback' => 'monospace', 'css' => array()),
            array('asset' => '', 'name' => 'Georgia', 'fallback' => 'serif', 'css' => array()),
            array('asset' => '', 'name' => 'Times New Roman', 'fallback' => 'Times, serif', 'css' => array()),
            array('asset' => '', 'name' => 'Trebuchet MS', 'fallback' => 'sans-serif', 'css' => array()),
            array('asset' => '', 'name' => 'Verdana', 'fallback' => 'sans-serif', 'css' => array()),
        );
    }

    /**
     * Возвращает данные о шрифте из указанного asset
     * @param string $asset_keyword
     * @return array|null
     */
    protected static function get_asset_font_data($asset_keyword) {
        $nc_core = nc_core::get_object();
        $asset_font_data = null;

        $asset_request = nc_page_asset_request::get_instance($asset_keyword);
        $asset_request->add_request_from_string('');
        $asset_css_files = nc_array_value($asset_request->get_resource_paths(), 'css', array());

        foreach ($asset_css_files as $asset_css_file) {
            $css_file_content = file_get_contents($nc_core->DOCUMENT_ROOT . $asset_css_file);
            // !!! предполагается, что библиотека определяет только один шрифт и только шрифт !!!
            if (preg_match('/font-family:\s*[\'"]?([^\'";\r\n]+)[\'"]?[;\r\n]/', $css_file_content, $matches)) {
                $asset_font_data = array(
                    'asset' => $asset_keyword,
                    'name' => trim($matches[1]),
                    'fallback' => $asset_request->get_asset_manifest('default_font_family'),
                    'css' => $asset_css_files,
                );
                break; // ← временно: потом можно добавить извлечение font-weight — может быть несколько CSS-файлов
            }
        }

        return $asset_font_data;
    }

    /**
     * Возвращает список шрифтов из assets
     * @param bool $include_generic_fonts включить распространённые шрифты (не требующие загрузки)
     * @return array
     */
    public static function get_available_fonts($include_generic_fonts = true) {
        $nc_core = nc_core::get_object();
        $fonts = $include_generic_fonts ? self::get_generic_font_data() : array();

        foreach ((array)glob($nc_core->ASSET_FOLDER . 'font_*', GLOB_ONLYDIR) as $font_asset_folder) {
            $fonts[] = self::get_asset_font_data(basename($font_asset_folder));
        }

        $fonts = array_filter($fonts);
        usort($fonts, function($a, $b) { return $a['name'] > $b['name']; });
        return $fonts;
    }

}