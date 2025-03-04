<?php

/* $Id: settings.php 7752 2012-07-23 10:37:13Z lemonade $ */

/**
 * Общие методы для получения настроек модуля. Singleton.
 */
class nc_search_settings {

    // ------------------------- DEFAULT VALUES --------------------------------
    protected $settings = array(
        'EnableSearch' => true,
        'SearchProvider' => 'nc_search_provider_index',
        'LogLevel' => nc_search::LOG_ALL_ERRORS,
        'DaysToKeepEventLog' => 365,
        'FilterStringCase' => MB_CASE_UPPER, // NB: в словарях phpMorphy используется верхний регистр

        'ExcludeUrlRegexps' => '',
        'RemoveStopwords' => true,

        // параметры Zend Search Lucene
        'ZendSearchLucene_IndexPath' => '%FILES%/Search/Lucene',
        // these are actually default Zend_Search_Lucene values:
        'ZendSearchLucene_ResultSetLimit' => 0,
        'ZendSearchLucene_MaxBufferedDocs' => 10,
        'ZendSearchLucene_MaxMergeDocs' => PHP_INT_MAX,
        'ZendSearchLucene_MergeFactor' => 10,

        // Параметры phpMorphy
        'PhpMorphy_LoadDictsDuringIndexing' => false, // загружать словарь полностью в память при индексировании?

        // максимальное число терминов (слов и чисел) в одном поле
        // (использовать когда не хватает памяти или времени для обработки больших документов)
        'MaxTermsPerField' => 0, // 0 == unlimited

        // параметры запросов
        'MaxTermsPerQuery' => 2048,
        'IgnoreNumbers' => false,
        'MinWordLength' => 0, // 0 == don't check

        'DefaultBooleanOperator' => 'AND',
        'AllowTermBoost' => true,
        'AllowProximitySearch' => true,
        'AllowWildcardSearch' => true,
        'AllowRangeSearch' => false,
        'AllowFuzzySearch' => true,
        'AllowFieldSearch' => true,

        // настройки http-бота
        'CrawlerUserAgent' => 'Search Module Crawler Bot',
        'CrawlerDelay' => 0,
        'CrawlerObeyRobotsTxt' => true,
        'CrawlerCheckLinks' => true,
        'CrawlerCheckOutsideLinks' => false,
        'CrawlerMaxDocumentSize' => 5242880, // 5Mb
        'CrawlerMaxRedirects' => 0, // из-за редиректов может уйти на другой сайт!
        'ObeyMetaNoindex' => true,
        'NumberOfEntriesPerSitemap' => 1000, // количество ссылок в sitemap.xml

        // настройки индексатора
        'IndexerSecretKey' => '',
        'MinScheduleInterval' => 300, // (5 минут) не ставить в очередь, если в указанный промежуток времени уже запланирован запуск [той же области]

        'IndexerSaveTaskEveryNthCycle' => 20,
        'IndexerRemoveIdleTasksAfter' => 900, // считать задачу подвисшей, если от нее нет вестей в течение 15 минут
        // Управление перезапуском скрипта индексирования в браузере по времени
        // Значение <= 1: когда прошло X*100% времени от max_execution_time
        // Значение  > 1: когда от запуска скрипта прошло X секунд
        // 0: отключить
        'IndexerTimeThreshold' => "0.7",
        // Управление перезапуском скрипта индексирования в браузере по использованной памяти
        // Значение <= 1: когда израсходовано X*100% памяти от memory_limit
        // Значение  > 1: когда потребление памяти достигло X *байт*
        // 0: отключить
        'IndexerMemoryThreshold' => "0.8",
        'IndexerNormalizeLinks' => true,
        // Задержка в секундах после выполнения каждых 10000 операций (ticks,
        // см. http://ru.php.net/manual/en/control-structures.declare.php#control-structures.declare.ticks).
        // Для использования в случае, когда требуется снижение нагрузки на процессор
        // при индексировании из cron’а.
        // Может быть задано дробное значение (разделитель — точка), напр. "0.25" (250 мс)
        'IndexerConsoleSlowdownDelay' => 0,
        // то же при запуске из браузера:
        'IndexerInBrowserSlowdownDelay' => 0,

        // Настройки индексирования из консоли по частям
        'IndexerConsoleMemoryThreshold' => "0.9",
        'IndexerConsoleTimeThreshold' => 25,
        'IndexerConsoleDocumentsPerSession' => 0,
        // Продолжать задачи, которые не обновлялись более IndexerRemoveIdleTasksAfter
        // секунд (только для режима CONSOLE_BATCH)
        //  0: нет (удалять зависшую задачу)
        //  1: да (продолжать зависшую задачу) — может привести к зацикливанию
        //     Будьте внимательны при включении этой опции: значение IndexerRemoveIdleTasksAfter
        //     должно быть значительно больше, чем значение IndexerConsoleTimeThreshold,
        //     иначе индекс будет повреждён!
        'IndexerConsoleRestartHungTasks' => 0,

        // настройки форм поиска на сайте
        'ComponentID' => 0,
        'SearchFormTemplate' => '',
        'AdvancedSearchFormTemplate' => '',

        // ПАРАМЕТРЫ NC_SEARCH_PROVIDER_INDEX ------------------------------
        // загружать коды всех известных терминов в память перед индексированием
        //   0 — не загружать (используйте, если терминов много, а памяти мало)
        //   1 — загружать (ускоряет индексирование)
        'DatabaseIndex_LoadAllCodesForIndexing' => 0,
        // максимальное количество слов, проверяемых на похожесть при нечётком
        // поиске (term~, term~0.5)
        'DatabaseIndex_MaxSimilarityCandidates' => 25000,
        // максимальное количество терминов, использующихся при подстановке
        // (поиск по похожести, по шаблону и по текстовому интервалу производится
        // путём замены соответствующего выражения списком возможных значений)
        'DatabaseIndex_MaxRewriteTerms' => 2000,
        // Определение схожести строк
        //   0 — использовать встроенную функцию levenshtein() — по умолчанию
        //   1 — использовать метод, учитывающий особенности текста в UTF-8
        'DatabaseIndex_UseUtf8Levenshtein' => 0,
        // Ограничение количества терминов в поиске по  расстоянию ("term1 term2"~4)
        // (Такие запросы переписываются во все возможные последовательности,
        // поэтому могут давать большое количество условий, что сильно замедлит
        // поиск. Например, поиск по расстоянию четырёх слов даёт (4!)=24 варианта
        // последовательности строк, и для каждой последовательности будут
        // выполняться медленные REGEXP-условия.)
        // Если выражение содержит более указанного количества слов, оно будет
        // переписано как обычный запрос из этих слов, например:
        // "one two three four five"~2 → (+one +two +three +four +five)
        'DatabaseIndex_MaxProximityTerms' => 4,
        // Максимальное расстояние между словами для поиска по расстоянию
        'DatabaseIndex_MaxProximityDistance' => 10,
        // Подсчитывать точное количество совпадений для запросов, требующих
        // уточнения REGEXP-выражением (фразы, поиск по расстоянию)?
        // Если подсчет количества совпадений отключен (0), то в результатах,
        // если это не последняя страница, будет показана ссылка только на 
        // следующую страницу, вне зависимости от общего числа результатов.
        // Отключение подсчета количества совпадений позволяет значительно
        // ускорить поиск фраз по высокочастотным словам (в ряде случаев — 
        // на порядок).
        // Если значение равно 0, у пользователя MySQL должны быть права
        // на создание врменных таблиц.
        'DatabaseIndex_AlwaysGetTotalCount' => 0,
        // Как часто выполнять оптимизацию таблиц индекса (оптимизация будет выполнена
        // приблизительно один раз за указанное количество переиндексаций).
        // Например: 1 — выполнять каждый раз, 100 — выполнять приблизительно
        // каждые 100 запусков (в среднем).
        // 0 — не выполнять оптимизацию таблиц
        'DatabaseIndex_OptimizationFrequency' => 100,
        // -----------------------------------------------------------------

        // Настройки шаблонов
        'web_SearchFormTemplate' => '',
        'web_AdvancedSearchFormTemplate' => '',
        'mobile_SearchFormTemplate' => '',
        'mobile_AdvancedSearchFormTemplate' => '',
        'responsive_SearchFormTemplate' => '',
        'responsive_AdvancedSearchFormTemplate' => '',

        // Настройки форм поиска
        'EnableAdvancedSearchForm' => true,
        'ShowAdvancedFormExcludeField' => true,
        'ShowAdvancedFormFieldSearch' => true,
        'ShowAdvancedFormTimeIntervals' => true,
        // параметры отображения результатов поиска
        'ResultTitleMaxNumberOfWords' => 25,
        'ResultContextMaxNumberOfWords' => 25,
        'AllowFieldSort' => true,
        'OpenLinksInNewWindow' => false,
        'ShowMatchedFragment' => true,
        'HighlightMatchedWords' => true,
        'MaxDocumentPreviewTextLengthInKbytes' => 100,
        // пареметры автозаполнения для поля поиска
        'EnableQuerySuggest' => true,
        'SuggestionsMinInputLength' => 3, // в символах
        'NumberOfSuggestions' => 10, // количество "подсказок"
        'SuggestMode' => 'queries', // допустимые значения: titles, queries
        'SearchTitleBaseformsForSuggestions' => true, // искать в индексе (базовые формы)
        'SearchTitleAsPhraseForSuggestions' => true, // искать в индексе как фразу
        // исправление запросов, когда они не дали результата
        'TryToCorrectQueries' => true,
        'MaxQueryLengthForCorrection' => 5, // чтобы сложнее было положить сервер
        'RemovePhrasesOnEmptyResult' => true,
        'ChangeLayoutOnEmptyResult' => true,
        'BreakUpWordsOnEmptyResult' => true,
        'PerformFuzzySearchOnEmptyResult' => true,
        'FuzzySearchOnEmptyResultSimilarityFactor' => "0.8",
        // история запросов
        'SaveQueryHistory' => true,
        'AutoPurgeHistory' => false, // автоматическая очистка истории запросов
        'AutoPurgeHistoryIntervalValue' => '', // ''|0 == не очищать историю запросов
        'AutoPurgeHistoryIntervalUnit' => 'months', // hours, days, months
    );

    // -------------------------------------------------------------------------

    protected $file_templates = array(
        'web_SearchFormTemplate' => 1,
        'web_AdvancedSearchFormTemplate' => 1,
        'mobile_SearchFormTemplate' => 1,
        'mobile_AdvancedSearchFormTemplate' => 1,
        'responsive_SearchFormTemplate' => 1,
        'responsive_AdvancedSearchFormTemplate' => 1,
    );

    protected $non_file_templates = array(
        'SearchFormTemplate' => 1,
        'AdvancedSearchFormTemplate' => 1,
    );

    protected $loaded_file_templates = array();
    protected $module_editor;

    /**
     *
     */
    public function __construct() {
        $this->load_settings();
    }

    protected function load_settings() {
        $settings = nc_Core::get_object()->get_settings(null, 'search');
        foreach ($settings as $k => $v) { $this->settings[$k] = $v; }
    }

    /**
     *
     */
    public function get($option) {
        if (isset($this->file_templates[$option])) { return $this->get_template($option); }

        if (!isset($this->settings[$option])) {
            throw new nc_search_exception("nc_search_settings::get(): invalid setting '$option'");
        }

        return $this->settings[$option];
    }

    /**
     *
     */
    public function set($option, $value) {
        $this->settings[$option] = $value;
        return $this;
    }

    /**
     *
     */
    public function save($option, $value, $check_permissions = true) {
        if ($check_permissions) {
            /** @var Permission $perm */
            global $perm;

            if (!($perm instanceof Permission) || $perm->isGuest()) {
                return $this;
            }

            $is_template_option = isset($this->file_templates[$option]) || isset($this->non_file_templates[$option]);
            if ($is_template_option && !$perm->isSupervisor()) {
                trigger_error("Unable to save search module setting '$option': user must be a supervisor", E_USER_WARNING);
                return $this;
            }
        }

        if (isset($this->file_templates[$option])) { // file templates
            if (!$this->module_editor) {
                $this->module_editor = new nc_tpl_module_subtypes_editor();
                $this->module_editor->load('search');
            }
            $this->module_editor->save(array($option => $value));
        }
        else { // other settings
            nc_Core::get_object()->set_settings($option, $value, 'search');
        }

        $this->set($option, $value);
        return $this;
    }

    /**
     *
     */
    protected function get_template($name) {
        if (!isset($this->loaded_file_templates[$name])) {
            // название настроек имеет вид "web_SearchFormTemplate"
            list($template_type, $template_name) = explode("_", $name, 2);

            $tpl = new nc_tpl_module_view();
            $tpl->load('search', $template_type);
            $this->settings[$name] = $tpl->get_field($template_name);

            $this->loaded_file_templates[$name] = 1;
        }
        return $this->settings[$name];
    }

}