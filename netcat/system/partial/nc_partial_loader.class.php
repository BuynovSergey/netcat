<?php

/**
 * Загрузчик асинхронных фрагментов страниц (врезок макетов, областей макетов,
 * инфоблоков).
 */
class nc_partial_loader {

    /** @var string страница, для которой загружаются фрагменты */
    protected $page_url;
    /** @var array переменные из query-части page_url */
    protected $page_url_query = array();
    /** @var null|int|string макет дизайна, к которому относятся загружаемые врезки */
    protected $partials_template;

    protected $requested_partials = array(
        'template_partial' => array(),
        'area' => array(),
        'infoblock' => array(),
    );

    /** @var nc_tpl_template_view */
    protected $template_view;

    /**
     * @var array переменные, которые не могут быть установлены через $data
     * при асинхронной загрузке — переменные, значение которых устанавливается
     * до подключения «полного» макета дизайна.
     * Также $data фильтруется методом nc_input::clear_system_vars();
     * см. self::parse_query_string().
     */
    static protected $protected_variables = array(
        'MODULE_VARS' => true, 'NETCAT_FOLDER' => true, 'admin_url_prefix' => true, 'classPreview' => true,
        'current_user' => true, 'perm' => true,
        'current_catalogue' => true, 'current_sub' => true, 'current_cc' => true, 'cc_array' => true,
        'sub_level_count' => true, 'parent_sub_tree' => true,
        'cc_keyword' => true, 'nc_parent_template_folder_path' => true, 'subHost' => true,
        'subLink' => true, 'ccLink' => true,
        'fullLink' => true, 'fullDateLink' => true, 'fullRSSLink' => true, 'msgLink' => true,
        'addLink' => true, 'editLink' => true, 'deleteLink' => true, 'dropLink' => true, 'checkedLink' => true,
        'versionLink' => true, 'subscribeLink' => true, 'subscribeMessageLink' => true, 'searchLink' => true,
        'nc_prev_object' => true, 'nc_next_object' => true,
        'f_AdminCommon' => true, 'f_AdminButtons' => true,
    );

    /**
     * @param array $input
     *      Входящие данные
     *      — referer — путь к странице, для которой загружаются фрагменты
     *      — template — ключевое слово или идентификатор макета дизайна для врезок макетов
     *        (если не задано, то используется макет для указанной страницы)
     *      — partial — загружаемые фрагменты. Тип фрагмента определяется
     *        частью до '?':
     *        · строка для врезок (template partials)
     *        · строка, начинающаяся с '@' для областей (areas)
     *        · число для инфоблоков (идентификатор инфоблока)
     * @throws nc_partial_loader_exception
     */
    public function __construct(array $input) {
        $this->set_request_values($input);
        $this->setup_page_environment();
    }

    // ------------------- МЕТОДЫ ДЛЯ ПОЛУЧЕНИЯ КОНТЕНТА -----------------------

    /**
     * Возвращает все запрошенные фрагменты
     * @throws nc_partial_loader_exception
     */
    public function get_partials_content() {
        $result =
            $this->get_template_partials_content() +
            $this->get_area_content() +
            $this->get_infoblock_content() +
            $this->get_page_tags();

        $nc_core = nc_core::get_object();
        foreach ($result as &$content) {
            $content = $nc_core->replace_macrofunc($content);
            $content = $nc_core->security->xss_filter->filter($content);
        }
        return $result;
    }

    /**
     * Возвращает содержимое врезок
     *
     * @return array
     * @throws nc_partial_loader_exception
     */
    protected function get_template_partials_content() {
        if (!$this->template_view) {
            throw new nc_partial_loader_exception('No template');
        }

        $all_partials_content = array();

        foreach ($this->requested_partials['template_partial'] as $partial_request_string => $requested_partial) {
            $partial = $this->template_view->partial($requested_partial['id'], $requested_partial['data']);
            if (!$partial->exists()) {
                continue;
            }
            if ($partial->is_async_loading_allowed()) {
                $partial_content = $partial->is_async_partial_request(true)->make();
            } else {
                $partial_content =
                    "Asynchronous load is not enabled for template partial '" .
                    htmlspecialchars($requested_partial['id']) . "'";
            }
            $all_partials_content[$partial_request_string] = $partial_content;
        }

        return $all_partials_content;
    }

    /**
     * Возвращает содержимое запрошенных областей
     *
     * @return array
     */
    protected function get_area_content() {
        // @TODO not implemented yet
        return array();
    }

    /**
     * Возвращает содержимое запрошенных инфоблоков.
     * (Пока что поддерживается только action=index)
     *
     * @return array
     */
    protected function get_infoblock_content() {
        $nc_core = nc_core::get_object();
        $all_partials_content = array();
        $current_infoblock_id = $nc_core->sub_class->get_current('Sub_Class_ID');

        foreach ($this->requested_partials['infoblock'] as $partial_request_string => $requested_partial) {
            $nc_core->sub_class->set_current_by_id($requested_partial['id']);

            $partial_content =
                nc_objects_list($GLOBALS['sub'], $requested_partial['id'], $requested_partial['data'])
                    ->is_async_partial_request(true)
                    ->make();

            $all_partials_content[$partial_request_string] = $partial_content;
        }

        $nc_core->sub_class->set_current_by_id($current_infoblock_id);

        return $all_partials_content;
    }

    /**
     * Возвращает теги, которые при обычном запросе автоматически добавляются
     * в <head> страницы (для assets, mixins, стилей компонентов)
     * @return array
     */
    protected function get_page_tags() {
        $page_tags = nc_core::get_object()->page->get_tags_for_partials();
        return $page_tags ? array('$' => $page_tags) : array();
    }


    // --- МЕТОДЫ ДЛЯ УСТАНОВКИ ПАРАМЕТРОВ ЗАПРОСА И ИНИЦИАЛИЗАЦИИ ОКРУЖЕНИЯ ---

    /**
     * Устанавливает параметры из запроса
     * @param array $input
     */
    protected function set_request_values(array $input) {
        $this->partials_template = nc_array_value($input, 'template', $this->partials_template);
        $this->set_requested_partials(nc_array_value($input, 'partial', array()));
        $this->set_page_url(nc_array_value($input, 'referer'));
    }

    /**
     * Раскладывает указанные фрагменты по типам в $this->requested_partials
     * @param string|array $partials
     *    Фрагменты могут быть заданы в виде массива (по элементу на каждый фрагмент)
     *    или в виде строки, где фрагменты разделены пробелом или запятой
     */
    protected function set_requested_partials($partials) {
        foreach ($this->parse_partial_request_strings($partials) as $key => $details) {
            $partial_type = $this->get_partial_type_by_id($details['id']);
            $this->requested_partials[$partial_type][$key] = $details;
        }
    }

    /**
     * Проверяет правильность URL
     * @param string|null $url
     * @return string|null
     */
    protected function filter_page_url($url) {
        if ($url) {
            $url = strtr($url, array('"' => '%22', "'" => '%27'));
            $url = filter_var($url, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE);
        }
        return $url;
    }

    /**
     * Устанавливает страницу, для которой генерируются фрагменты
     * @param string|null $url
     */
    protected function set_page_url($url) {
        $url = $this->filter_page_url($url) ?: nc_get_scheme() . '://' . getenv('HTTP_HOST') . '/';
        $this->page_url = $url;

        $nc_core = nc_core::get_object();
        $nc_core->url = new nc_url($url);

        $GLOBALS['REQUEST_URI'] =
        $_SERVER['REQUEST_URI'] =
        $_ENV['REQUEST_URI'] =
        $_GET['REQUEST_URI'] =
        $_POST['REQUEST_URI'] =
            $nc_core->url->get_local_url();

        if ($nc_core->url->get_parsed_url('query')) {
            $this->page_url_query = $nc_core->input->prepare_extract(true);
        }
    }

    /**
     * Устанавливает глобальные переменные, как при стандартном запросе страницы
     * @throws nc_partial_loader_exception
     */
    protected function setup_page_environment() {
        $nc_core = nc_core::get_object();

        $nc_core->modules->load_env();
        $nc_core->user->attempt_to_authorize();

        $catalogue = $nc_core->catalogue->get_current('Catalogue_ID');
        $sub = $nc_core->catalogue->get_current('E404_Sub_ID');
        $template = null;

        extract($GLOBALS);
        extract($this->page_url_query);

        // разбор пути (для ЧПУ/режима просмотра)
        if (strpos($nc_core->url->get_parsed_url('path'), $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH) !== 0) {
            require $nc_core->INCLUDE_FOLDER . 'e404.php';
        }

        if (!isset($action)) {
            $action = 'index';
        }

        // Нужно, чтобы $catalogue, $sub, $cc были доступны в $GLOBALS
        // (иначе сломается nc_core::load_env(), где значения по факту берутся из $GLOBALS)
        $this->set_globals(get_defined_vars());

        $template = $nc_core->subdivision->get_current('Template_ID') ?: $this->partials_template;
        if (!$this->partials_template) {
            // если template явно не задан во входящих параметрах, используем макет соответствующего раздела
            $this->partials_template = $template;
        }

        // определение стандартных глобальных переменных
        require $nc_core->INCLUDE_FOLDER . 'index.php';

        // загрузка $template_settings раздела
        if ($nc_core->template->get_root_id($template) == $nc_core->template->get_root_id($this->partials_template)) {
            try {
                $template_settings = $nc_core->subdivision->get_template_settings($sub);
            } catch (Exception $e) {
                throw new nc_partial_loader_exception('Wrong sub');
            }
        }

        if (!empty($this->requested_partials['template_partial']) && !$nc_core->template->get_by_id($template, 'Template_ID')) {
            throw new nc_partial_loader_exception('Wrong template for the template partial');
        }

        if ($template && $nc_core->template->get_by_id($template, 'File_Mode')) {
            if (!isset($template_settings)) {
                $template_settings = $nc_core->template->get_settings_default_values($this->partials_template);
            }

            // подключение шаблонов вывода навигации макета (~ /netcat/require/index_fs.inc.php)
            // ($template определяется текущим разделом, либо при наличии page_url мог быть переопределён
            // в /netcat/require/index.php)

            $this->template_view = $nc_core->template->get_file_template($template);
            if (!$this->template_view) {
                throw new nc_partial_loader_exception("Unable to load file template '$template'");
            }
            $this->template_view->include_all_required_assets();

            foreach ($this->template_view->get_all_settings_path_in_array() as $nc_template_settings_path) {
                include_once $nc_template_settings_path;
            }
            unset($nc_template_settings_path);
        }

        $this->set_globals(get_defined_vars());
    }

    // ----------------------- ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ --------------------------

    /**
     * Преобразовывает строки, указывающие на фрагменты, в массивы, где ключ —
     * исходная строка, значение — ассоциативный массив с элементами id и data
     *
     * @param string|string[] $partial_request_strings  могут быть заданы через пробел или запятую, либо массивом
     * @return array
     */
    protected function parse_partial_request_strings($partial_request_strings) {
        $nc_core = nc_core::get_object();
        $result = array();

        if (!is_array($partial_request_strings)) {
            $partial_request_strings = preg_split('/[\s,]+/', $partial_request_strings, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($partial_request_strings as $request_string) {
            if (strpos($request_string, '?')) {
                list($partial_id, $partial_data_string) = explode('?', $request_string, 2);
                $partial_data = $this->parse_query_string($partial_data_string);
                $nc_core->security->add_checked_input(array(
                    "partial_{$request_string}" => $partial_data
                ));
            } else {
                $partial_id = $request_string;
                $partial_data = array();
            }

            $result[$request_string] = array(
                'id' => $partial_id,
                'data' => $partial_data,
            );
        }

        return $result;
    }

    /**
     * Возвращает переменные для асинхронной врезки из query-строки.
     * Фильтрует (убирает) элементы, которые не должны быть переопределены извне.
     * @param string $query_string query-строка, без '?' (вида 'a=b&c=d')
     * @return array
     */
    protected function parse_query_string($query_string) {
        /** @var array $variables */
        parse_str($query_string, $variables);

        $variables = nc_core::get_object()->input->clear_system_vars($variables);

        foreach ($variables as $k => $v) {
            if (isset(self::$protected_variables[$k])) {
                unset($variables[$k]);
            }
        }

        return $variables;
    }

    /**
     * Возвращает тип фрагмента по его идентификатору:
     *  — если строка начинается с '@' — это область (area)
     *  — если строка из чисел — это инфоблок
     *  — если прочая строка — это врезка (template partial)
     *
     * @param string $partial_id
     * @return string
     */
    protected function get_partial_type_by_id($partial_id) {
        if ($partial_id[0] === '@') {
            return 'area';
        }
        if (ctype_digit((string)$partial_id)) {
            return 'infoblock';
        }
        if (nc_core::get_object()->template->is_valid_partial_keyword($partial_id)) {
            return 'template_partial';
        }
        return 'unknown';
    }

    /**
     * Устанавливает значения в $GLOBALS
     *
     * (Если просто присвоить $GLOBALS = get_defined_vars(), то будет
     * некорректно работать конструкция 'global $var')
     *
     * @param array $variables
     */
    protected function set_globals(array $variables) {
        foreach ($variables as $key => $value) {
            if ($key !== 'this') {
                $GLOBALS[$key] = $value;
            }
        }
    }

}