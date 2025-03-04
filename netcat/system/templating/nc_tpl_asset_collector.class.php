<?php

/**
 * Класс для сбора информации о необходимых библиотеках, стилях и скриптах.
 */

abstract class nc_tpl_asset_collector {

    protected $styles = array();
    protected $scripts = array();
    protected $scripts_on_dom_loaded = array();
    protected $meta = array();

    /**
     * Добавляет библиотеку на страницу
     *
     * Для подключения библиотек в компонентах и макетах дизайна используйте файл RequiredAssets.html.
     *
     * @param $asset_keyword_and_version
     *    Ключевое слово и версия библиотеки через @, например: 'jquery@1.*'
     *    Допустимые способы задания версий:
     *      (пустая строка — использовать последнюю имеющуюся версию)
     *      1.1
     *      1.0-1.3
     *      1.*
     *      1.0-2.*
     *    Пробелы не допускаются
     * @param array $options
     *    'defer' — использовать defer для скриптов (по умолчанию true)
     *    'embed' — встроить скрипты в head страницы (по умолчанию false)
     * @return bool false, если библиотека не была добавлена из-за конфликта версий
     */
    abstract public function require_asset($asset_keyword_and_version, array $options = array());

    /**
     * Добавляет на страницу библиотеки, перечисленные в массиве.
     * Возможные способы указания библиотек:
     * array(
     *     'asset_keyword@ver',
     *     'asset_keyword@ver' => array('defer' => false),
     * )
     *
     * Этот же формат используется в файлах RequiredAssets.html.
     *
     * Не является частью публичного API. Для подключения библиотек в компонентах
     * и макетах дизайна используйте файл RequiredAssets.html.
     *
     * @param array $assets
     * @return bool false, если библиотека не была добавлена из-за конфликта версий
     */
    abstract public function require_assets(array $assets);

    /**
     * Добавляет библиотеку; при повторном вызове, в отличие от self::require_asset(),
     * не производит анализ и объединение версий и параметров, поэтому может использоваться
     * лишь со служебными библиотеками, параметры подключения которых везде одинаковые.
     *
     * @internal Не является частью публичного API — может быть изменён в будущих версиях.
     * Для подключения библиотек в прочих случаях используйте RequiredAssets.html,
     * или, где это невозможно, $nc_core->page->require_asset().
     *
     * @param string $asset_keyword  только ключевое слово, без указания версий
     * @param array $options
     */
    abstract public function require_asset_once($asset_keyword, array $options = array());

    /**
     * Устанавливает значение в свойстве meta
     *
     * @param string $key
     * @param mixed $value
     */
    public function set_meta($key, $value) {
        $this->meta[$key] = $value;
    }

    /**
     * Возвращает значение из свойства meta (или все значения, если $key === null)
     *
     * @param string|null $key
     * @return array|mixed
     */
    public function get_meta($key = null) {
        return $key === null ? $this->meta : nc_array_value($this->meta, $key);
    }

    /**
     * (Для nc_page пока не является частью API, может быть переделано)
     *
     * @param string $styles
     */
    public function add_styles($styles) {
        $this->styles[] = $styles;
    }

    /**
     * (Для nc_page пока не является частью API, может быть переделано)
     *
     * @param string $js
     * @param boolean $execute_on_dom_loaded
     */
    public function add_javascript($js, $execute_on_dom_loaded = true) {
        $this->{$execute_on_dom_loaded ? 'scripts_on_dom_loaded' : 'scripts'}[] = $js;
    }

}