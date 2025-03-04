<?php

class nc_partial_object_list extends nc_partial {

    /** @var string префикс комментария (должен быть определён в классе-наследнике */
    protected $partial_comment_id_prefix = 'i';
    /** @var int счётчик вложенных фрагментов с отложенной загрузкой (используется в ID комментария) */
    static protected $partial_last_sequence_number = 0;
    /** @var int счётчик вложенных вызовов (фрагмент внутри фрагмента) */
    static protected $partial_nesting_level = 0;

    protected $infoblock_id;

    protected $longpage_mode = false;
    protected $show_in_admin_mode = true;
    protected $add_list_div = true;

    public function __construct($infoblock_id, array $data = array()) {
        $this->infoblock_id = $infoblock_id;
        $this->set_data($data);
    }

    public function set_longpage_mode($longpage_mode = true) {
        $this->longpage_mode = $longpage_mode;
        return $this;
    }

    public function show_in_admin_mode($show_in_admin_mode = true) {
        $this->show_in_admin_mode = $show_in_admin_mode;
        return $this;
    }

    protected function get_query_string() {
        return http_build_query($this->data, null, '&');
    }

    public function get_content() {
        return $this->process_template();
    }

    /**
     * @param bool $just_query_where
     * @return string|array
     * @throws Exception
     * @throws nc_Exception_Class_Doesnt_Exist
     */
    private function process_template($just_query_where = false) {
        extract($this->data);

        // *** Глобальные переменные ***

        /** @var Permission $perm */
        if ($GLOBALS['perm'] instanceof Permission) {
            $perm = $GLOBALS['perm'];
        }
        else {
            $perm = false; // :)
        }

        global $UI_CONFIG, $_cache, $admin_url_prefix, $classPreview;
        global $AUTH_USER_ID, $AUTH_USER_GROUP, $current_user;
        global $sub_level_count, $parent_sub_tree;
        global $cc_array;
        global $subHost;
        // for old modules (forum)
        global $current_catalogue, $current_sub, $current_cc;
        global $nc_parent_template_folder_path;
        global $nc_minishop;

        // *** Необходимые локальные переменные ***

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        // modules variables
        $MODULE_VARS = $nc_core->modules->get_module_vars();
        // system variables
        $FILES_FOLDER      = $nc_core->get_variable("FILES_FOLDER");
        $HTTP_ROOT_PATH    = $nc_core->get_variable("HTTP_ROOT_PATH");
        $ADMIN_PATH        = $nc_core->get_variable("ADMIN_PATH");
        $ADMIN_TEMPLATE    = $nc_core->get_variable("ADMIN_TEMPLATE");
        $DOMAIN_NAME       = $nc_core->get_variable("DOMAIN_NAME");
        $SHOW_MYSQL_ERRORS = $nc_core->get_variable("SHOW_MYSQL_ERRORS");
        $AUTHORIZE_BY      = $nc_core->get_variable("AUTHORIZE_BY");
        $HTTP_FILES_PATH   = $nc_core->get_variable("HTTP_FILES_PATH");
        $DOCUMENT_ROOT     = $nc_core->get_variable("DOCUMENT_ROOT");
        $SUB_FOLDER        = $nc_core->get_variable("SUB_FOLDER");

        $inside_admin = $nc_core->inside_admin;
        $admin_mode   = $nc_core->admin_mode;
        $nc_partial_async = $this->is_async_partial_request; // загружается через /netcat/partial.php?

        $system_env        = $nc_core->get_settings();
        $current_catalogue = $nc_core->catalogue->get_current();

        if (!$this->longpage_mode) {
            $current_sub = $nc_core->subdivision->get_current();
        }

        // [MERGE] $ignore_eval is always an empty array ?????????
        $ignore_eval = array();

        //$srchPat дважды urldecodeд и "+" теряется, берем значения из $_REQUEST которые уже один раз urldecodeд
        //если $_REQUEST['srchPat'] пустой, то srchPat передался через s_list_class, сохраняем его
        $srchPat = isset($srchPat)
                    ? $nc_core->input->fetch_get_post('srchPat')
                        ? $nc_core->input->fetch_get_post('srchPat')
                        : $srchPat
                    : null;
        $srchPatAdd = isset($srchPatAdd)
                    ? $nc_core->input->fetch_get_post('srchPatAdd')
                        ? $nc_core->input->fetch_get_post('srchPatAdd')
                        : $srchPatAdd
                    : null;


        // *** Санация переменных ***

        $cc = (int)$this->infoblock_id;
        $parent_message = isset($parent_message) ? (int)$parent_message : 0;

        if (!$cc) {
            return false;
        }


        // *** Переменные, которые могут устанавливаться только в системных настройках ***

        $ignore_all       = false;
        $ignore_catalogue = false;
        $ignore_sub       = false;
        $ignore_cc        = false;
        $ignore_check     = false;
        $ignore_parent    = false;
        $ignore_user      = true;
        $ignore_calc      = false;
        $ignore_link      = false;
        $ignore_prefix    = false;
        $ignore_suffix    = false;
        $distinct         = false;
        $distinctrow      = false;
        $message_select   = null;
        $query_from       = null;
        $query_group      = null;
        $query_join       = null;
        $query_order      = null;
        $query_select     = null;
        $query_where      = null;
        $query_having     = null;
        $query_limit      = null;
        $nc_data          = null;

        $result_vars = '';

        // *** Значения по умолчанию / инициализация переменных ***

        if (!isset($nc_title)) { $nc_title = false; }
        if (!isset($isMainContent)) { $isMainContent = false; }
        if (!isset($isSubClassArray)) { $isSubClassArray = false; }
        if (!isset($cur_cc)) { $cur_cc = false; }
        if (!isset($curPos)) { $curPos = 0;}
        if (!isset($recNum)) { $recNum = 0;}
        if (!isset($list_mode)) { $list_mode = null; }

        $nc_ctpl = !empty($nc_ctpl) ? $nc_ctpl : 0;
        if (!$nc_ctpl && $nc_title) { $nc_ctpl = 'title'; }

        $nc_page = isset($nc_page) ? (int)$nc_page : null;

        // [MERGE] was in _db version only
        //    if (+$_REQUEST['isModal']) {
        //        $inside_admin = false;
        //        $admin_mode = false;
        //    }


        try {
            // *** Получение параметров инфоблока ***

            $cc_env = $nc_core->sub_class->get_by_id($cc, null, $nc_ctpl);

            if (!$this->longpage_mode) {
                $current_cc = $cc_env;
            }

            // *** Параметры областей страниц для nc_area() ***

            $nc_area_keyword = null;
            if ($cc_env['AreaKeyword']) {
                $nc_area_keyword = $cc_env['AreaKeyword'];
                $isSubClassArray = true;
            }


            // *** Информация о редактировании инфоблока из другого раздела ***
            if (!$nc_area_keyword && ($cc != $cc_env['Sub_Class_ID'] || !$isMainContent) && $admin_mode && !$this->show_in_admin_mode) {
                $Subdivision_ID = $nc_core->sub_class->get_by_id($cc, 'Subdivision_ID');
                $Subdivision_Name = $nc_core->subdivision->get_by_id($Subdivision_ID, 'Subdivision_Name');
                return nc_print_status(
                    sprintf(
                        CONTROL_CONTENT_SUBCLASS_EDIT_IN_PLACE,
                        $SUB_FOLDER . $HTTP_ROOT_PATH . "index.php?inside_admin=" . $inside_admin . "&sub=" . $Subdivision_ID . "&cc=" . $cc,
                        $Subdivision_Name),
                    'info', null, true
                );
            }


            try {
                $wrong_nc_ctpl = (
                    $nc_ctpl &&
                    $nc_ctpl !== 'title' &&
                    $nc_ctpl != $cc_env['Class_ID'] &&
                    $cc_env['Class_ID'] != $nc_core->component->get_component_template_by_keyword($cc_env['Class_ID'], $nc_ctpl, 'ClassTemplate') &&
                    !$nc_core->component->is_template_compatible_with_component($nc_ctpl, $cc_env['Class_ID'])
                );

                if ($wrong_nc_ctpl) {
                    throw new Exception();
                }
            }
            catch (Exception $e) {
                $wrong_nc_ctpl = $e->getMessage();
                $nc_ctpl = 0;
                $cc_env = $nc_core->sub_class->get_by_id($cc);
            }

            if (!$nc_ctpl) {
                if ($admin_mode && $cc_env['Edit_Class_Template']) {
                    $cc_env = $nc_core->sub_class->get_by_id($cc, null, $cc_env['Edit_Class_Template']);
                }

                if ($inside_admin && $cc_env['Admin_Class_Template']) {
                    $cc_env = $nc_core->sub_class->get_by_id($cc, null, $cc_env['Admin_Class_Template']);
                }
            }
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            if ($current_user && $current_user['InsideAdminAccess']) {
                return $e->getMessage();
            }
            else {
                return '';
            }
        }

        if (!isset($isNaked)) {
            $isNaked = $cc_env['isNaked'];
        }

        // set user table mode
        $user_table_mode = (bool)$cc_env['System_Table_ID'];

        // Просмотр в виде таблицы
        $table_view_mode = (bool)$cc_env['TableViewMode'];

        // *** Зеркальные инфоблоки ***

        $_db_cc        = $cc;
        $_db_sub       = $sub = $cc_env['Subdivision_ID'];
        $_db_catalogue = $catalogue = $cc_env['Catalogue_ID'];
        $_db_Class_ID  = $cc_env['Real_Class_ID'];
        $_db_File_Path = $cc_env['File_Path'];
        $_db_File_Hash = $cc_env['File_Hash'];

        // Для зеркальных инфоблоков:

        if ($cc_env['SrcMirror']) {
            $source_env = $nc_core->sub_class->get_by_id($cc_env['SrcMirror'], null, $nc_ctpl);
            $cc         = $source_env['Sub_Class_ID'];
            $sub        = $source_env['Subdivision_ID'];
            $catalogue  = $source_env['Catalogue_ID'];
            $is_mirror  = true;
            $nc_default_action = $source_env['DefaultAction'];
        }
        else {
            $mirror_env = null;
            $is_mirror = false;
            $nc_default_action = $cc_env['DefaultAction'];
        }

        if ($nc_area_keyword) {
            $sub = $nc_core->subdivision->get_current('Subdivision_ID');
        }

        // записываем реальный номер шаблона компонента
        if ($nc_ctpl === 'title') {
            $nc_ctpl = $cc_env['Real_Class_ID'];
        }


        if ($cc_env['Type'] == 'rss' || $cc_env['Type'] == 'xml') {
            $cc_env['Cache_Access_ID'] = 2;
        }


        // *** Режим работы: шаблоны в файлах или в базе? ***

        $component_file_mode = (bool)$cc_env['File_Mode'];

        if ($table_view_mode && $inside_admin) {
            $component_file_mode = true;
        }

        // *** Подготовка переменных для предварительного просмотра ***

        // если preview для нашего класса, то подменим cc_env из $_SESSION
        if ($classPreview == ($cc_env["Class_Template_ID"] ? $cc_env["Class_Template_ID"] : $cc_env["Class_ID"])) {
            $magic_gpc = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc();
            if (!empty($_SESSION["PreviewClass"][$classPreview])) {
                foreach ($_SESSION["PreviewClass"][$classPreview] as $tkey => $tvalue) {
                    $cc_env[$tkey] = $magic_gpc ? stripslashes($tvalue) : $tvalue;
                }
            }
            // Запретим кеширование в режиме предпросмотра.
            $cc_env['Cache_Access_ID'] = 2;
        }


        // *** Проверка прав доступа ***

        if ($cc_env['Read_Access_ID'] > 1 && !$AUTH_USER_ID) {
            return false;
        }

        if ($AUTH_USER_ID && $cc_env['Read_Access_ID'] > 2) {
            if (!CheckUserRights($cc, 'read', 1)) {
                return false;
            }
        }


        // *** Состояние модулей, влияющих на работу данной функции ***

        $routing_module_enabled = nc_module_check_by_keyword('routing');
        $cache_module_enabled   = nc_module_check_by_keyword('cache');


        // *** Названия CSS-классов для блоков, в которых выводится содержимое ***
        // $nc_component_css_class, $nc_component_css_selector, $nc_block_id — часть API
        $nc_mixins_css_class = $nc_mixins_list_css_class = '';

        $nc_add_block_markup =
            (!$isNaked || $admin_mode) && // не добавлять разметку в режиме просмотра, если есть isNaked
            $nc_core->component->can_add_block_markup($cc_env['Class_Template_ID'] ?: $cc_env['Class_ID']);

        $nc_add_list_container_markup = $nc_add_block_markup && !$cc_env['DisableBlockListMarkup'];

        $nc_block_mixins_breakpoints_attribute = '';

        if ($nc_add_block_markup) {
            static $nc_objects_list_count = 0;
            ++$nc_objects_list_count;

            $nc_core->page->register_component_usage($cc_env['Class_ID'], $cc_env['Real_Class_ID']);

            $nc_mixins_css_class = 'tpl-block-' . $cc;
            $nc_mixins_list_css_class = $nc_mixins_css_class . '-list';
            $nc_component_css_class = $nc_core->component->get_css_class_name($cc_env['Real_Class_ID'] ?: $cc_env['Class_ID'], $cc_env['Class_ID']);
            $nc_component_css_selector = '.' . str_replace(' ', '.', $nc_component_css_class);
            $nc_block_id = nc_make_block_id("$nc_objects_list_count/$cc");

            if ($nc_add_list_container_markup) {
                nc_tpl_mixin_cache::load_block_data($cc_env['Catalogue_ID'], $cc);
                // ↑ в т.ч. загрузит информацию о брейкпоинтах в meta для $cc

                $nc_block_breakpoints = $nc_core->sub_class->get_meta($cc, 'Index_Breakpoints');
                $nc_list_breakpoints = $nc_core->sub_class->get_meta($cc, 'IndexItem_Breakpoints');
                if ($nc_block_breakpoints || $nc_list_breakpoints) {
                    $nc_block_mixins_breakpoints_attribute = " data-nc-b='" .
                        $nc_block_breakpoints . ($nc_block_breakpoints ? ' ' : '') . $nc_list_breakpoints .
                    "'";
                }

                unset($nc_block_breakpoints, $nc_list_breakpoints);
            }

            $nc_block_markup_prefix = '<div class="tpl-block-list ' . $nc_component_css_class . '" id="' . $nc_block_id . '">';
            if ($isMainContent && $isSubClassArray) {
                $nc_block_markup_prefix .= '<div class="tpl-anchor" id="' . nc_transliterate($cc_env['EnglishName'], true) . '"></div>';
            }
            $nc_block_markup_suffix = '</div>';
        }
        else {
            $nc_component_css_class = $nc_component_css_selector = $nc_block_id = null;
            $nc_block_markup_prefix = '';
            $nc_block_markup_suffix = '';
        }


        // *** Проверка наличия результата в кэше ***
        $nc_cache_list = null;
        $cached_result = -1;
        $cached_data = "";
        $cached_eval = false;
        $cache_key = null;
        if ($cache_module_enabled && $cc_env['Cache_Access_ID'] == 1 && !$user_table_mode) {
            // startup values

            $nc_cache_list = nc_cache_list::getObject();
            try {
                // cache auth add-on string
                $cache_for_user = $nc_cache_list->authAddonString($cc_env['CacheForUser'], $current_user);
                $cache_key = $this->get_query_string() . $cache_for_user . "type=" . $cc_env['Type'] . "classtemplate=" . $cc_env['ClassTemplate'];
                // check cached data
                $cached_result = $nc_cache_list->read($_db_sub, $_db_cc, $cache_key, $cc_env['Cache_Lifetime']);
                if ($cached_result != -1) {
                    // get cached parameters
                    list ($cached_data, $cached_eval, $cache_vars) = $cached_result;
                    // debug info
                    $cache_debug_info = "Read, sub[" . $_db_sub . "], cc[" . $_db_cc . "], Access_ID[" . $cc_env['Cache_Access_ID'] . "], Lifetime[" . $cc_env['Cache_Lifetime'] . "], bytes[" . strlen($cached_data) . "], eval[" . (int)$cached_eval . "]";
                    $nc_cache_list->debugMessage($cache_debug_info, __FILE__, __LINE__);
                    // return cache if eval flag is not set
                    if (!$cached_eval) {
                        return $cached_data;
                    }
                }
            }
            catch (Exception $e) {
                $nc_cache_list->errorMessage($e);
            }
        }


        // *** Подготовка прочих переменных; подготовка к вычислению «системных настроек» ***

        // Если присутствует параметр isSubClassArray в вызове функции nc_objects_list() или вызов из фильтра для получения условия выборки, то добавляем
        // в массив $cc_env элемент cur_cc, который будет участвовать в формировании навигации по страницам
        // при отображении нескольких шаблонов на странице
        if ((isset($isSubClassArray) && $isSubClassArray) || $just_query_where) {
            $cc_env['cur_cc'] = $_db_cc;
        }

        $intQueryStr = '?';

        // $cc_settings — пользовательские настройки инфоблока
        $cc_settings = & $cc_env["Sub_Class_Settings"];

        // $subLink, $ccLink, $cc_keyword
        if ($admin_mode) {
            $subLink = $admin_url_prefix .  '?catalogue=' . $_db_catalogue . '&amp;sub=' . $_db_sub;
            $cc_keyword = null;
            $ccLink = $subLink . '&amp;cc=' . $_db_cc;
            $intQueryStr = $ccLink;
        }
        else if ($routing_module_enabled) {
            $subLink = new nc_routing_path_folder($_db_sub);
            $ccLink = new nc_routing_path_infoblock($_db_cc);
            $cc_keyword = $cc_env['EnglishName'];
        }
        else {
            $subLink = $SUB_FOLDER . nc_array_value($cc_env,'Hidden_URL');
            $cc_keyword = $cc_env['EnglishName'];
            $ccLink = $subLink . $cc_keyword . '.html';
        }

        // переменные curPos, recNum нужно привести к "правильному" виду
        // до И после выполнения системных настроек компонента
        $maxRows = $cc_env['RecordsPerPage'];

        $curPos = ($nc_page !== null) ? ($nc_page - 1) * $maxRows : (int)$curPos;
        if ($curPos < 0) {
            $curPos = 0;
        }

        $recNum = (int)$recNum;
        if ($recNum < 0) {
            $recNum = 0;
        }

        $ignore_limit = null;
        $SortBy = $cc_env['SortBy'];
        $classID = $cc_env['Class_ID'];
        $userTableID = $cc_env['System_Table_ID'];

        $no_cache_marks = 0;

        if (isset($MODULE_VARS['searchold']['INDEX_TABLE']) && $MODULE_VARS['searchold']['INDEX_TABLE'] == $classID) {
            $ignore_eval['sort_by'] = true;
        }

        $file_class = null;

        // фильтр для данного инфоблока (может и не быть)
        if ($cc_env['HasFilter']) {
            $nc_infoblock_filter = nc_infoblock_filter::get_by_data_infoblock_id($this->infoblock_id);
            if (!$nc_infoblock_filter->exists()) {
                $nc_infoblock_filter = false;
            }
        } else {
            $nc_infoblock_filter = false;
        }

        $nc_has_fatal_template_error = false;

        // *** Вычисление «системных настроек» шаблона ***
        $nc_settings_error_message = '';
        if ($component_file_mode) {
            $file_class = new nc_tpl_component_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);

            // Переменные, доступные в шаблонах компонента
            $nc_parent_class_folder_path = nc_get_path_to_main_parent_folder($cc_env['File_Path']);
            // два названия: одно без ошибок, другое указано в документации [5.4]
            $nc_class_aggregator_path = $nc_class_agregator_path = $nc_core->INCLUDE_FOLDER . 'classes/nc_class_aggregator_setting.class.php';
            // clear this variable after system settings eval!
            $result = "";

            // check and include component part
            // На странице может подключаться один и тот же компонент, у которого есть системные настройки
            // На текущий момент необходим include, а не include_once

            try {
                if ($table_view_mode && $inside_admin) {
                    // В табличном виде собственные системные настройки компонента вычисляются до системных настроек шаблона компонента table
                    if ($cc_env['File_Mode']) { // для компонента v4 в табличном режиме $component_file_mode будет true!
                        $nc_component_file_class = new nc_tpl_component_view($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
                        $nc_component_file_class->load($_db_Class_ID, $_db_File_Path, $_db_File_Hash);
                        // Переменная, доступная в шаблонах компонента
                        $nc_parent_field_path = $nc_component_file_class->get_parent_field_path('Settings');
                        // Путь к файлу системных настроек для обычного вида
                        $nc_component_field_path = $nc_component_file_class->get_field_path('Settings');
                        // Настройки обычного вида
                        if (PHP_MAJOR_VERSION === 5) {
                            nc_check_php_file($nc_component_field_path);
                        }
                        if ((include $nc_component_field_path) === false) {
                            throw new nc_tpl_exception_include($nc_component_field_path);
                        }
                        unset($nc_component_file_class, $nc_component_field_path, $nc_parent_field_path);
                    } elseif ($cc_env['Settings']) {
                        eval(nc_check_eval($cc_env['Settings']));
                    }

                    // Табличный вид
                    $file_class->load('table', '/table/', $_db_File_Hash);
                    $file_class->include_all_required_assets();
                    $nc_field_path = $file_class->get_field_path('Settings');
                } else {
                    // Обычный вид
                    $file_class->load($_db_Class_ID, $_db_File_Path, $_db_File_Hash);
                    // Assets
                    $file_class->include_all_required_assets();
                    // Переменная, доступная в шаблонах компонента
                    $nc_parent_field_path = $file_class->get_parent_field_path('Settings');
                    // Путь к файлу системных настроек для обычного вида
                    $nc_field_path = $file_class->get_field_path('Settings');
                }

                if (!$just_query_where && $nc_infoblock_filter && !$isNaked) {
                    // Наличие фильтра включает асинхронную загрузку для блока
                    $this->enable_async_loading();
                    // При асинхронной загрузке устанавливаем значения фильтра из параметров запроса
                    $filter = $nc_core->input->fetch_post_get('filter');
                    if ($nc_partial_async && isset($filter[$this->infoblock_id]) && is_array($filter[$this->infoblock_id])) {
                        $nc_infoblock_filter->set_filter_values($filter[$this->infoblock_id]);
                    }
                }

                // Файл системных настроек табличного ИЛИ обычного вида
                if (PHP_MAJOR_VERSION === 5) {
                    nc_check_php_file($nc_field_path);
                }

                // если для данного инфоблока есть фильтр, то надо пытаться использовать кэш системных настроек инфоблока
                if ($nc_infoblock_filter) {
                    $system_settings_cache = $nc_core->sub_class->get_system_settings_cache($this->infoblock_id);
                    if (!$system_settings_cache) {
                        if ((include $nc_field_path) === false) {
                            throw new nc_tpl_exception_include($nc_field_path);
                        }

                        // после подключения файла с системными настройками сохраним переменные в кэш.
                        // это нужно, чтобы при работе фильтра не инклюдить файл дважды
                        $nc_defined_vars = get_defined_vars();
                        // ключ just_query_where надо убить, иначе потом он превратится в переменную $just_query_where там, где не надо.
                        unset($nc_defined_vars['just_query_where']);

                        // по умолчанию это 0 и если сохранить в кэш, то при восстановлении
                        // может быть перезаписано реальное значение $curPos и не будет работать пагинация
                        unset($nc_defined_vars['curPos']);

                        $nc_core->sub_class->set_system_settings_cache($this->infoblock_id, $nc_defined_vars);
                    } else {
                        extract($system_settings_cache);
                    }
                } else {
                    if ((include $nc_field_path) === false) {
                        throw new nc_tpl_exception_include($nc_field_path);
                    }
                }
            } catch (Throwable $e) { // PHP 7
                $nc_settings_error_message = isset($nc_component_file_class) // unsetится сразу после include
                    ? $nc_component_file_class->get_error_message($nc_component_field_path, $e)
                    : $file_class->get_error_message($nc_field_path, $e);
                $nc_has_fatal_template_error = true;
            } catch (Exception $e) { // PHP 5
                $nc_settings_error_message = isset($nc_component_file_class)
                    ? $nc_component_file_class->get_error_message($nc_component_field_path, $e)
                    : $file_class->get_error_message($nc_field_path, $e);
                $nc_has_fatal_template_error = true;
            }

            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
            // «компоненты v4»
            if ($cc_env['Settings']) { eval(nc_check_eval($cc_env['Settings'])); }
        }

        if ($ignore_limit === null) {
            $ignore_limit = (!$maxRows && !$recNum);
        }

        // *** Сброс переменных после вычисления «системных настроек» ***

        $recNum = (int)$recNum;
        if ($recNum < 0) {
            $recNum = 0;
        }

        if (!$recNum) {
            $recNum = $maxRows;
        }
        else {
            $maxRows = $recNum;
        }

        $maxRows = (int)$maxRows;

        $curPos = ($nc_page !== null) ? ($nc_page - 1) * $maxRows : (int)$curPos;
        if ($curPos < 0) {
            $curPos = 0;
        }

        $result = $nc_settings_error_message;
        unset($nc_settings_error_message);

        // Если возникла ошибка в системных настройках, запрос производиться не будет, т. к. из-за этого
        // стандартным запросом могут быть выведены данные, не предназначенные для глаз пользователя
        if ($nc_has_fatal_template_error) {
            $nc_data = array();
        }


        // *** Данные не из запроса, а подготовленные в системных настройках — $nc_data ***

        $nc_prepared_data = 0;
        if (isset($nc_data) && (is_array($nc_data) || $nc_data instanceof ArrayAccess)) {
            $nc_prepared_data = 1;
            $cc_env['convert2txt'] = '';
        }

        // *** Подготовка переменных для построения запроса к БД ***

        // выйдем, если нет идентификатора шаблона, поскольку дальше работа функции бессмысленна
        if (!$classID) {
            return false;
        }

        $component = $nc_core->get_component($cc_env['System_Table_ID'] ? 'User' : $classID);
        $field_vars = null;
        $date_field = null;

        if (!$nc_prepared_data || $just_query_where) { // данные будут получены из запроса к БД

            if (!$SortBy) {
                $sort_by = "a." . ($user_table_mode ? "`" . $AUTHORIZE_BY . "`" : "`Priority` DESC") . ", a.`LastUpdated` DESC";
            }
            else {
                $sort_by = $SortBy;
            }

            $field_names = $component->get_fields_query();
            $field_vars = $component_file_mode ? null : $component->get_fields_vars();
            $multilist_fields = $component->get_fields(NC_FIELDTYPE_MULTISELECT);
            $date_field = $component->get_date_field();

            $cc_env['convert2txt'] = $component->get_convert2txt_code($cc_env);


            // *** «Поиск» по компоненту ***


            $full_search_params = $component->get_search_query($srchPat);
            $full_search_params_add = $component->get_search_query($srchPatAdd, $cc);
            $full_search_query = $full_search_url = '';
            if (!empty($full_search_params['query'])) {
                $full_search_query = $full_search_params['query'];
                $full_search_url = $full_search_params['link'];
            }
            if (!empty($full_search_params_add['query'])) {
                $full_search_query .= " ".$full_search_params_add['query'];
                $full_search_url .= empty($full_search_params_add['link']) ? $full_search_params_add['link'] : "&".$full_search_params_add['link'];
            }

            // *** Подготовка запроса к БД ***

            $cond_catalogue = (!$ignore_catalogue && !$nc_area_keyword)
                                 ? $cond_catalogue = " AND sub.`Catalogue_ID` = '" . $catalogue . "' "
                                 : "";
                // [MERGE] not used, not in API
                // $cond_catalogue_add = " AND a.`Subdivision_ID` = sub.`Subdivision_ID` ";
                // $cond_catalogue_addtable = ", `Subdivision` AS sub ";

            $cond_sub = (!$ignore_sub && !$nc_area_keyword) ? " AND a.`Subdivision_ID` = '" . $sub . "' " : "";
            $cond_cc = !$ignore_cc ? " AND a.`Sub_Class_ID` = '" . $cc . "' " : "";
            $cond_user = !$ignore_user ? " AND a.`User_ID` = '" . $AUTH_USER_ID . "' " : "";
            $cond_parent = !$ignore_parent ? " AND a.`Parent_Message_ID` = '" . $parent_message . "' " : "";
            $cond_search = $full_search_query;
            $cond_mod = (!$admin_mode && !$ignore_check) ? $cond_mod = " AND a.`Checked` = 1 " : "";

            $cond_date = (isset($date) && $date && $date_field && strtotime($date) > 0)
                            ? $cond_date = " AND a.`" . $date_field . "` LIKE '" . $db->escape($date) . "%' "
                            : "";

            $cond_distinct = isset($distinct) && $distinct ? "DISTINCT" : "";
            if (!$cond_distinct) {
                $cond_distinct = isset($distinctrow) && $distinctrow ? "DISTINCTROW" : "";
            }

            if (isset($query_select) && $query_select) {
                $cond_select = $component_file_mode
                                    ? ", " . $query_select
                                    : ", " . nc_add_column_aliases($query_select);
            }
            else {
                $cond_select = "";
            }

            $cond_where = $query_where ? " AND " . $query_where : "";
            $cond_group = $query_group ? " GROUP BY " . $query_group : "";
            $cond_having = $query_having ? " HAVING " . $query_having : "";

            if (isset($query_order) && $query_order) {
                $sort_by = $query_order;
            }

            if ($user_table_mode) {
                $cond_sub = "";
                $cond_cc = "";
                // [MERGE] not used        $cond_catalogue_add = "";
                $cond_catalogue = "";
                // [MERGE] not used        $cond_catalogue_addtable = "";
                $cond_parent = "";
            }

            if ($full_search_url) {
                $intQueryStr .= (($intQueryStr == '?') ? '' : '&amp;') . $full_search_url;
            }

            // для совместимости со старыми версиями до 2.4.5 и 3.0.0
            // [MERGE] не удалось выяснить, что это было; условие всегда выполняется
            // (кроме компонента старого модуля поиска), так как $ignore_eval — пустой массив:
            if (!$component_file_mode && !empty($ignore_eval['sort_by'])) {
                eval(nc_check_eval("\$sort_by = \"" . $sort_by . "\";"));
            }

            if (!$ignore_all) {
                $nc_custom_query_where = '(1 ' . $cond_parent . $cond_where . $cond_catalogue . $cond_sub . $cond_cc . $cond_user . $cond_mod . $cond_search . $cond_date . ')';
                // cond_user, cond_search, cond_date

                if ($cc_env['ConditionQuery']) {
                    $nc_custom_query_where .= ' OR (1' . $cond_catalogue . ' AND ' . $cc_env['ConditionQuery'] . $cond_user . $cond_mod . $cond_search . $cond_date . ')';
                }

                if ($just_query_where) {
                    return array('where' => $nc_custom_query_where, 'join' => $query_join);
                }

                // возможно для этого инфоблока есть настроенный фильтр.
                // если так, то надо дополнить условие $query_where
                if (!$just_query_where && $nc_infoblock_filter) {
                    $nc_filter_conditions = $nc_infoblock_filter->get_query_conditions();
                    if ($nc_filter_conditions) {
                        $nc_custom_query_where .= ($nc_custom_query_where ? ' AND ' : '') . $nc_filter_conditions;
                    }
                }

                $message_select =
                    "SELECT" . (!$ignore_calc ? " SQL_CALC_FOUND_ROWS" : "") . " " .
                               $cond_distinct . " " . $field_names . $cond_select . "
                       FROM (" . ($user_table_mode ? "`User`" : "`Message" . $classID . "`") . " AS a " .
                            ($query_from ? ", " . $query_from : "") . ") " .
                            $component->get_joins() . " " .
                            $query_join . "
                      WHERE $nc_custom_query_where" .
                      $cond_group .
                      $cond_having .
                      ($sort_by ? " ORDER BY " . $sort_by : "");

                if (!$ignore_limit) {
                    if ($cur_cc === false || !isset($cc_env['cur_cc']) || $cc_env['cur_cc'] == $cur_cc) {
                        $nc_query_offset = $curPos;
                    } else {
                        $nc_query_offset = 0;
                    }

                    $nc_query_limit = $maxRows;
                    if ($cc_env['ConditionQuery']) {
                        if ($cc_env['ConditionOffset']) {
                            $nc_query_offset += $cc_env['ConditionOffset'];
                        }

                        if ($cc_env['ConditionLimit']) { // ConditionLimit: NULL = отсутствует ограничение, 0 игнорируется
                            $nc_query_limit = min($maxRows ?: 1000000, $cc_env['ConditionLimit'] - $curPos);
                        }
                    }

                    $message_select .= " LIMIT $nc_query_offset, $nc_query_limit";
                } else {
                    $message_select .= (isset($query_limit) && $query_limit ? " LIMIT " . $query_limit : "");
                }
            }
            elseif ($query_select && $query_from) {
                if ($just_query_where) {
                    return array('where' => $query_where, 'join' => $query_join);
                }
                $message_select =
                    "SELECT" . (!$ignore_calc ? " SQL_CALC_FOUND_ROWS" : "") . " " . $query_select .
                     " FROM " . $query_from . ($query_join ? " " . $query_join : "") .
                     ($query_where  ? " WHERE " . $query_where : "") .
                     ($query_group  ? " GROUP BY " . $query_group : "") .
                     ($query_having ? " HAVING  " . $query_having : "") .
                     ($query_order  ? " ORDER BY " . $query_order : "") .
                     ($query_limit  ? " LIMIT " . $query_limit : "");
            }

            $cc_env['dateField'] = $date_field;
            $cc_env['fieldCount'] = count($component->get_fields());
        }

        $cc_env['curPos'] = $curPos;
        $cc_env['recNum'] = $recNum;
        $cc_env['maxRows'] = $maxRows;
        $cc_env['LocalQuery'] = $intQueryStr;

        // *** Ссылки для действий с инфоблоком ***

        if ($routing_module_enabled) {
            $addLink = new nc_routing_path_infoblock($_db_cc, 'add');
            $rssLink = $cc_env['AllowRSS'] ? new nc_routing_path_infoblock($_db_cc, 'index', 'rss') : '';
            $xmlLink = $cc_env['AllowXML'] ? new nc_routing_path_infoblock($_db_cc, 'index', 'xml') : '';
            $xmlFullLink = '';
            $subscribeLink = new nc_routing_path_infoblock($cc, 'subscribe');
            $searchLink = new nc_routing_path_infoblock($cc, 'search');
        }
        else {
            $addLink = $SUB_FOLDER . nc_array_value($cc_env, 'Hidden_URL') . 'add_' . $cc_env['EnglishName'] . '.html';
            $rssLink = $cc_env['AllowRSS'] ? $SUB_FOLDER . nc_array_value($cc_env, 'Hidden_URL') . $cc_env['EnglishName'] . '.rss' : '';
            $xmlLink = $cc_env['AllowXML'] ? $SUB_FOLDER . nc_array_value($cc_env, 'Hidden_URL') . $cc_env['EnglishName'] . '.xml' : '';
            $xmlFullLink = "";
            $subscribeLink = $SUB_FOLDER . nc_array_value($cc_env, 'Hidden_URL') . 'subscribe_' . $cc_env['EnglishName'] . '.html';
            $searchLink = $SUB_FOLDER . nc_array_value($cc_env, 'Hidden_URL') . 'search_' . $cc_env['EnglishName'] . '.html';
        }

        $cc_env['addLink'] = $addLink;
        $cc_env['subscribeLink'] = $subscribeLink;
        $cc_env['searchLink'] = $searchLink;


        // *** Проверка наличия результата в кэше ***

        // cache eval section
        if ($cache_module_enabled && $cc_env['Cache_Access_ID'] == 1 && is_object($nc_cache_list) && $cached_eval && $cached_result != -1) {
            // get cached objects blocks
            $component_cache_blocks = $nc_cache_list->getCachedBlocks($cached_data);

            // cached prefix
            eval(nc_check_eval("\$result = \"" . $component_cache_blocks['prefix'] . "\";"));

            if (is_array($component_cache_blocks) && !empty($component_cache_blocks)) {
                // concat cached objects
                foreach ($component_cache_blocks['objects'] as $k => $v) {
                    // extract cached object variables
                    if (!empty($cache_vars) && is_array($cache_vars[$k])) {
                        extract($cache_vars[$k]);
                    }
                    // append object data
                    eval(nc_check_eval("\$result .= \"" . $v . "\";"));
                }
            }

            // cached suffix
            eval(nc_check_eval("\$result .= \"" . $component_cache_blocks['suffix'] . "\";"));

            return $result;
        }


        // *** Проверка наличия формы добавления и формы поиска в коде компонента ***

        if ($component_file_mode) {
            $component_body = nc_check_file($file_class->get_field_path('Class')) ? nc_get_file($file_class->get_field_path('Class')) : null;
            // @todo ↑↑↑ refactor: don’t load template files (use lazy variables instead?)
            if ($cc_env['Class_Template_ID'] && strpos($component_body, '$nc_parent_field_path') !== false) {
                $component_body .= nc_check_file($file_class->get_parent_field_path('Class')) ? nc_get_file($file_class->get_parent_field_path('Class')) : null;
            }
        }
        else {
            // «компоненты v4»
            $cc_env['AddTemplate'] = $cc_env['AddTemplate'] ?: $component->add_form($catalogue, $sub, $cc);
            $cc_env['FullSearchTemplate'] = $cc_env['FullSearchTemplate'] ?: $component->search_form(1);

            $component_body =
                ($ignore_prefix ? '' : $cc_env['FormPrefix']) .
                ($ignore_suffix ? '' : $cc_env['FormSuffix']) .
                $cc_env['RecordTemplate'] .
                $cc_env['RecordTemplateFull'] .
                $cc_env['Settings'];
        }


        // *** Форма добавления — $addForm ***

        $addForm = '';
        if ($nc_default_action === 'add' || strpos($component_body, '$addForm') !== false) {
            $addForm = $this->get_add_form(get_defined_vars());
        }

        // Фильтр объектов в режиме администратора
        $filter_form_html = '';

        // *** Форма поиска (выборки) — $searchForm ***
        $searchForm = '';
        if ($nc_default_action === 'search' || strpos($component_body, '$searchForm') !== false) {
            if ($component_file_mode) {
                $nc_parent_field_path = $file_class->get_parent_field_path('FullSearchTemplate');
                $nc_field_path = $file_class->get_field_path('FullSearchTemplate');
                $searchForm = '';
                if (filesize($nc_field_path)) {
                    ob_start();
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
                    $searchForm = ob_get_clean();
                }
                else {
                    eval(nc_check_eval("\$searchForm.= \"" . $component->search_form(1) . "\";"));
                }

                $nc_parent_field_path = null;
                $nc_field_path = null;
            }
            else {
                // «компоненты v4»
                eval(nc_check_eval("\$searchForm = \"" . $cc_env["FullSearchTemplate"] . "\";"));
            }

            if ($searchForm && $nc_add_block_markup) {
                $searchForm = "<div class='tpl-block-search-form $nc_component_css_class'>" . $searchForm . "</div>";
            }
        }
        elseif ($inside_admin && $isMainContent) {
            // Системная форма поиска (фильтр)
            $filter_additional_fields = $component->get_additional_search_fields($cc);
            $filter_fields = $component->get_fields();
            $filter_view_data = array(
                'cc'      => $cc,
                'form'    => eval(nc_check_eval('return "' . nc_fields_form('search', $filter_fields, 0, $filter_additional_fields) . '";')),
                'fields'  => array_merge($filter_additional_fields, $filter_fields),
                'is_open' => false, // оставляем всегда закрытым
            );
            $filter_view      = $nc_core->ADMIN_FOLDER . 'views/component/objects_filter_form.view.php';
            $filter_form_html = $nc_core->ui->view($filter_view, $filter_view_data);

            unset($filter_fields, $filter_additional_fields, $filter_view_data, $filter_view);
        }

        unset($component_body);


        // *** Блок с действием по умолчанию «добавление» или «поиск» в основной части страницы ***

        if ($isMainContent && !$inside_admin) {
            switch ($nc_default_action) {
                case 'add':
                    return $nc_block_markup_prefix . $addForm . $nc_block_markup_suffix;
                case 'search':
                    return $nc_block_markup_prefix . $searchForm . $nc_block_markup_suffix;
            }
        }


        // *** Выполнение запроса к БД ***

        $db->last_error = "";
        if ($message_select) {
            $res = $db->get_results($message_select, ARRAY_A);
        }
        else {
            $res = false;
        }


        // *** Обработка ошибок, возникших при выполнении запроса ***

        if ($db->last_error) {
            // determine error cause
            switch (true) {
                case preg_match("/Table '\w+\.Classificator_(\w+)' doesn't exist/i", $db->last_error, $regs):
                    $err = sprintf(NETCAT_FUNCTION_OBJECTS_LIST_CLASSIFICATOR_ERROR, $regs[1]);
                    break;
                case preg_match("/Unknown column '(.+?)' in 'field list'/i", $db->last_error, $regs):
                    $err = sprintf(NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_UNKNOWN, $regs[1]);
                    break;
                case preg_match("/Unknown column '(.+?)' in 'order clause'/i", $db->last_error, $regs):
                    $err = sprintf(NETCAT_FUNCTION_OBJECTS_LIST_SQL_COLUMN_ERROR_CLAUSE, $regs[1]);
                    break;
                case $SHOW_MYSQL_ERRORS == "on":
                    $err = $db->last_error;
                    break;
                default:
                    $err = "";
            }

            // error message
            if ($perm instanceof Permission && $perm->isSupervisor()) {
                // error info for the supervisor
                nc_print_status($err ?: $db->last_error, 'error');
                trigger_error(sprintf(NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_SUPERVISOR, $sub, $cc, $this->get_query_string(), ($err ? $err . ", " : "")), E_USER_WARNING);
            }
            else {
                // error info for the simple users
                echo NETCAT_FUNCTION_OBJECTS_LIST_SQL_ERROR_USER;
            }
            return false;
        }


        // *** Подсчёт количества объектов в результатах ($rowCount) и общего ($totRows) ***

        $totRows = 0;
        if ($message_select) {
            // object in this page
            $rowCount = $db->num_rows;
            // total objects
            $totRows = !$ignore_calc ? $db->get_var("SELECT FOUND_ROWS()") : $rowCount;
            $totRows += 0;
            // Если в условиях выборки указано количество записей, делаем вид,
            // что выбраны все записи (чтобы не появлялась постраничная разбивка)
            if ($cc_env['ConditionQuery'] && $cc_env['ConditionLimit']) {
                $totRows = min($totRows, $cc_env['ConditionLimit']);
            }
        }
        else if ($nc_prepared_data) {
            $rowCount = sizeof($nc_data);
            $totRows += 0;
            if (!$totRows) {
                $totRows = ($nc_data instanceof nc_record_collection) ? $nc_data->get_total_count() : $rowCount;
            }
        }
        else {
            $rowCount = 0;
            $totRows = 0;
        }


        // *** Подготовка к работе с компонентом-агрегатором ***

        $nc_class_aggregator = null;
        $nc_class_aggregator_data = null;

        if (class_exists('nc_class_aggregator_setting', false)) {
            $nc_class_aggregator_settings = nc_class_aggregator_setting::get_instanse();

            if ($nc_class_aggregator_settings && $res) {
                require_once $nc_core->INCLUDE_FOLDER . "classes/nc_class_aggregator.class.php";

                $class_data = array();

                foreach ($res as $row) {
                    $class_data[] = array('db_Class_ID' => nc_array_value($row,'db_Class_ID'), 'db_Message_ID' => nc_array_value($row,'db_Message_ID'));
                }

                $nc_class_aggregator = new nc_class_aggregator($nc_class_aggregator_settings, $class_data);
                $nc_class_aggregator_data = $nc_class_aggregator->get_full_data();
            }
        }


        // *** Переменные для вывода листалки страниц ***

        // Перенос GET-переменных в пути $nextLink, $prevLink
        $_get_arr = $nc_core->input->fetch_get();
        $get_param = array(
            'cur_cc' => null, // чтобы порядок параметров был как в nc_browse_messages
        );
        if (!empty($_get_arr)) {
            $ignore_arr = array(
                'sid' => true, 'ced' => true, 'inside_admin' => true, 'REQUEST_URI' => true,
                'cur_cc' => true, 'curPos' => true, 'nc_page' => true);

            foreach ($_get_arr as $k => $v) {
                if (!isset($ignore_arr[$k])) {
                    $get_param[$k] = $v;
                }
            }
        }
        unset($_get_arr, $ignore_arr);

        $begRow = $curPos + 1;
        $endRow = $curPos + $maxRows;
        if ($endRow > $totRows) { $endRow = $totRows; }

        $prevLink = $nextLink = '';

        if ($curPos > $maxRows && isset($cc_env['cur_cc']) && $cc_env['cur_cc']) {
            $get_param['cur_cc'] = $cc_env['cur_cc'];
        }
        if ($classPreview == $cc_env["Class_ID"]) {
            $get_param["classPreview"] = $classPreview;
        }

        if ($curPos > $maxRows) { // мы сейчас на третьей странице или далее (ссылка на первую страницу — без curPos)
            $get_param['curPos'] = $curPos - $maxRows;
        }

        // готовим $prevLink
        if ($maxRows && $curPos && ($cur_cc === false || $cur_cc == $_db_cc)) {
            if ($routing_module_enabled && !$admin_mode) {
                $nc_previous_page = (int)($curPos / $maxRows);
                $prevLink = new nc_routing_path(
                                    $nc_core->page->get_routing_result('resource_type'),
                                    array(
                                        'site_id' => $_db_catalogue,
                                        'folder_id' => $_db_sub,
                                        'infoblock_id' => $_db_cc,
                                        'action' => 'index',
                                        'format' => 'html',
                                        'variables' => $get_param,
                                        'page' => ($nc_previous_page > 1 ? $nc_previous_page : null),
                                        'date' => (isset($date) ? $date : null),
                                    ));
            }
            else {
                $prevLink = ($admin_mode ? $admin_url_prefix : $nc_core->url->get_parsed_url('path')) .
                            nc_array_to_url_query($get_param, '&amp;');
            }
        }

        // готовим $nextLink
        if ($maxRows && $endRow < $totRows) {
            $get_param['curPos'] = $endRow;

            if (isset($cc_env['cur_cc']) && $cc_env['cur_cc']) {
                $get_param['cur_cc'] = $cc_env['cur_cc'];
            }

            if ($routing_module_enabled && !$admin_mode) {
                $nextLink = new nc_routing_path(
                                    $nc_core->page->get_routing_result('resource_type'),
                                    array(
                                        'site_id' => $_db_catalogue,
                                        'folder_id' => $_db_sub,
                                        'infoblock_id' => $_db_cc,
                                        'action' => 'index',
                                        'format' => 'html',
                                        'variables' => $get_param,
                                        'page' => (int)($curPos / $maxRows + 2),
                                        'date' => (isset($date) ? $date : null),
                                    ));
            }
            else {
                $nextLink = ($admin_mode ? $admin_url_prefix : $nc_core->url->get_parsed_url('path')) .
                            nc_array_to_url_query($get_param, '&amp;');
            }
        }

        unset($get_param);


        $cc_env['begRow'] = $begRow;
        $cc_env['endRow'] = $endRow;
        $cc_env['totRows'] = $totRows;
        $cc_env['prevLink'] = $prevLink;
        $cc_env['nextLink'] = $nextLink;


        // *** Подготовка к извлечению полученных данных ***

        if ($component_file_mode) {
            if ($nc_prepared_data && isset($nc_data[0])) {
                $f_Checked = 1;
                $fetch_row = $nc_data;
            }
            else {
                $fetch_row = $res;
            }
        }
        else {
            // «компоненты v4»
            if ($nc_prepared_data && isset($nc_data[0])) {
                $fetch_row = '$f_Checked = 1; ';
                // нужно подготовить $fetch_row вида:
                // $f_a = $nc_data[$f_RowNum]['a']; $f_b = $nc_data[$f_RowNum]['b']; ...
                // элементы $nc_data могут быть как массивом, так и объектом, реализующим Iterator, поэтому array_keys не подходит
                foreach ($nc_data[0] as $key => $value) {
                    $fetch_row .= '$f_' . $key . ' = $nc_data[$f_RowNum]["' . $key . '"]; ';
                }
            }
            else if (!$ignore_all) {
                $fetch_row = "list(" . $field_vars . ($result_vars ? ", " . $result_vars : "") . ") = array_values(\$res[\$f_RowNum]);";
            }
            else {
                $fetch_row = $result_vars ? "list(" . $result_vars . ") = array_values(\$res[\$f_RowNum]);" : "";
            }

        }


        // *** Подготовка элементов интерфейса для режима администрирования ***

        $f_AdminCommon = "";
        $f_AdminCommon_cc = "";
        $f_AdminCommon_cc_name = "";
        $f_AdminCommon_add = "";
        $f_AdminCommon_delete_all = "";
        $f_AdminButtons = "";

        // Право на модерирование и изменение объектов.
        $modPerm = false;
        $changePerm = false;

        $nc_show_admin_ui = false;
        $nc_show_add_record_button = false;
        $nc_show_delete_record_button = false;

        $nc_can_admin_infoblock = false;
        $nc_can_moderate_infoblock = false;
        $nc_can_change_own_objects = false; // используется при проверке, показывать ли $f_AdminButtons

        if ($admin_mode && $perm) {
            $nc_can_admin_infoblock = $perm->isSubClass($cc, MASK_ADMIN);
            $nc_can_moderate_infoblock = $nc_can_admin_infoblock || $perm->isSubClass($cc, MASK_MODERATE);
            $nc_can_change_own_objects =
                !$nc_can_moderate_infoblock && (
                    $perm->isSubClass($cc, MASK_DELETE) ||
                    $perm->isSubClass($cc, MASK_ADD) ||
                    $perm->isSubClass($cc, MASK_EDIT) ||
                    $perm->isSubClass($cc, MASK_CHECKED)
                );
            $nc_show_admin_ui = $nc_can_moderate_infoblock || !empty($current_user['InsideAdminAccess']);
        }

        if ($nc_show_admin_ui) {
            $modPerm = CheckUserRights($cc, 'moderate', 1); // право модератора
            $changePerm = s_auth($cc_env, 'change', 1); //               или просто на изменение объектов

            if ($perm && $perm->isBanned($cc_env, 'change')) {
                // пользователю запретили изменение объектов
                $modPerm = $changePerm = false;
            }

            $f_AdminCommon_add = $admin_url_prefix . "add.php?inside_admin=" . $inside_admin . "&amp;catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc;
            $f_AdminCommon_delete_all = $admin_url_prefix . "message.php?inside_admin=" . $inside_admin . "&amp;catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;classID=" . $classID . "&amp;delete=1";
            $f_AdminCommon_export_csv = $admin_url_prefix . "message.php?inside_admin=" . $inside_admin . "&amp;catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;classID=" . $classID . "&amp;export=1";
            $f_AdminCommon_import_csv = $admin_url_prefix . "message.php?inside_admin=" . $inside_admin . "&amp;catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;classID=" . $classID . "&amp;import=1";
            $f_AdminCommon_export_xml = $admin_url_prefix . "message.php?inside_admin=" . $inside_admin . "&amp;catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;classID=" . $classID . "&amp;export=2";
            $f_AdminCommon_import_xml = $admin_url_prefix . "message.php?inside_admin=" . $inside_admin . "&amp;catalogue=" . $catalogue . "&amp;sub=" . $sub . "&amp;cc=" . $cc . "&amp;classID=" . $classID . "&amp;import=2";

            $addLink = $f_AdminCommon_add;

            // Js и форма для пакетной обработки объектов
            $f_AdminCommon_package = "<script type='text/javascript' language='javascript'>\n";
            $f_AdminCommon_package .= "\tif (typeof(nc_package_obj) != 'undefined') {nc_package_obj.new_cc(" . $cc . ", '" . NETCAT_MODERATION_NOTSELECTEDOBJ . "'); }\n";
            $f_AdminCommon_package .= "</script>\n";
            $f_AdminCommon_package .= "<form id='nc_form_selected_" . $cc . "' action='" . $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php' method='post'>\n";
            $f_AdminCommon_package .= "\t<input type='hidden' name='catalogue' value='" . $catalogue . "'>\n";
            $f_AdminCommon_package .= "\t<input type='hidden' name='sub' value='" . $sub . "'>\n";
            $f_AdminCommon_package .= "\t<input type='hidden' name='cc' value='" . $cc . "'>\n";
            $f_AdminCommon_package .= "\t<input type='hidden' name='curPos' value='" . $curPos . "'>\n";
            $f_AdminCommon_package .= "\t<input type='hidden' name='admin_mode' value='" . $admin_mode . "'>\n";
            $f_AdminCommon_package .= "\t<input type='hidden' name='inside_admin' value='" . $inside_admin . "'>\n";
            $f_AdminCommon_package .= "</form>\n";

            if ($list_mode != "select") {
                $nc_show_add_record_button = (
                        $nc_can_moderate_infoblock ||
                        $perm->isSubClass($cc, MASK_ADD)
                    ) && (
                        !strlen($cc_env['MaxRecordsInInfoblock']) ||
                        $cc_env['MaxRecordsInInfoblock'] > $totRows
                    ) && $nc_data === null;

                $nc_show_delete_record_button = (
                        $nc_can_moderate_infoblock ||
                        $perm->isSubClass($cc, MASK_DELETE)
                    ) && (
                        !$cc_env['MinRecordsInInfoblock'] ||
                        $cc_env['MinRecordsInInfoblock'] < $totRows
                    );

                if ($inside_admin && $isMainContent && $UI_CONFIG) {
                    // в админке нет AdminCommon, но нужна часть для пакетной обработки
                    if ($totRows != 0) {
                        $result .= $f_AdminCommon_package;
                    }
                    // add button
                    $UI_CONFIG->actionButtons = array();

                    if ($nc_show_add_record_button) {
                        $UI_CONFIG->actionButtons[] = array(
                            "id" => "addObject",
                            "align" => "left",
                            "caption" => NETCAT_MODERATION_BUTTON_ADD,
                            "action" => "nc.load_dialog('{$SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}add.php?inside_admin=1&cc=$cc')",
                        );
                    }

                    // кнопки пакетной обработки нужны только если есть объекты
                    if ($totRows != 0) {
                        //  button "delete all"
                        if ($nc_show_delete_record_button) {
                            $UI_CONFIG->actionButtons[] = array(
                                "id" => "deleteAll",
                                "caption" => NETCAT_MODERATION_REMALL,
                                "align" => "right",
                                "action" => "urlDispatcher.load('subclass.purge(" . $cc . ")')",
                                "red_border" => true,
                            );
                        }
                        if ($nc_core->get_settings('PacketOperations')) {
                            // button "Удалить выбранные"
                            $UI_CONFIG->actionButtons[] = array(
                                "id" => "deleteChecked",
                                "caption" => NETCAT_MODERATION_DELETESELECTED,
                                "align" => "right",
                                "action" => "document.getElementById('mainViewIframe').contentWindow.nc_package_obj.process('delete', " . $cc . ")",
                                "red_border" => true,
                            );
                            // button "Выключить выбранные"
                            $UI_CONFIG->actionButtons[] = array(
                                "id" => "checkOff",
                                "caption" => NETCAT_MODERATION_SELECTEDOFF,
                                "align" => "left",
                                "action" => "document.getElementById('mainViewIframe').contentWindow.nc_package_obj.process('checkOff', " . $cc . ")"
                            );
                            // button "Включить выбранные"
                            $UI_CONFIG->actionButtons[] = array(
                                "id" => "checkOn",
                                "caption" => NETCAT_MODERATION_SELECTEDON,
                                "align" => "left",
                                "action" => "document.getElementById('mainViewIframe').contentWindow.nc_package_obj.process('checkOn', " . $cc . ")"
                            );
                        }
                    }
                }

                if (!$inside_admin) {
                    if ($nc_can_admin_infoblock) {
                        $f_AdminCommon .= nc_AdminCommonMultiBlock($cc, $sub, $nc_show_add_record_button);
                    } else if ($nc_show_add_record_button) {
                        $f_AdminCommon .= nc_AdminCommonAddObject($cc, $sub);
                    }
                }

            }
        }


        // *** Массив $row_ids: ID всех объектов в полученной выборке ***

        $row_ids = array();
        $res_key = $user_table_mode ? 'User_ID' : 'Message_ID';

        if (!$nc_prepared_data && isset($res[0][$res_key])) {
            $res_key = $user_table_mode ? 'User_ID' : 'Message_ID';
            for ($f_RowNum = 0; $f_RowNum < $rowCount; $f_RowNum++) {
                if (!empty($res[$f_RowNum]['Created'])) {
                    $nc_core->page->update_last_modified_if_timestamp_is_newer(strtotime($res[$f_RowNum]['Created']));
                }
                if (!empty($res[$f_RowNum]['Updated'])) {
                    $nc_core->page->update_last_modified_if_timestamp_is_newer(strtotime($res[$f_RowNum]['Updated']));
                }
                $row_ids[] = $res[$f_RowNum][$res_key];
            }
        }
        unset($res_key, $f_RowNum);

        // Фильтр объектов в режиме администратора
        if ($filter_form_html) {
            $result .= $filter_form_html;
        }


        // *** Загрузка и кэширование информации о файлах ***

        // ID (символьный для таблицы пользователей, числовой для прочих компонентов) для nc_file_info
        $hybrid_component_id = ($user_table_mode ? 'User' : $classID);
        $multifile_field_values = array();

        if (!empty($row_ids)) {
            // Загрузить все значения полей типа NC_FIELD_MULTIFILE
            $multifile_field_values = nc_get_multifile_field_values($hybrid_component_id, $row_ids);

            // Передать в file_info значения полей типа файл для дальнейшего использования:
            $nc_core->file_info->cache_object_list_data($hybrid_component_id, $res);
            // Загрузить данные о файлах объектов в списке из Filetable:
            $nc_core->file_info->preload_filetable_values($hybrid_component_id, $row_ids);
        }


        // *** Загрузка информации о группах пользователей (user_table_mode) ***

        // требуется получить все группы пользователей
        if ($user_table_mode && !empty($row_ids)) {
            $nc_user_group = $db->get_results("SELECT ug.`User_ID`, ug.`PermissionGroup_ID`, g.`PermissionGroup_Name`
                                           FROM `User_Group` AS ug,`PermissionGroup` AS g
                                           WHERE User_ID IN (" . join(', ', $row_ids) . ")
                                           AND g.`PermissionGroup_ID` = ug.`PermissionGroup_ID` ", ARRAY_A);
            if (!empty($nc_user_group)) {
                foreach ($nc_user_group as $v) {
                    $nc_user_group_sort[$v['User_ID']][$v['PermissionGroup_ID']] = $v['PermissionGroup_Name'];
                }
            }
            unset($nc_user_group);
        }

        $result .= $nc_block_markup_prefix;

        // *** Системные уведомления ***

        if ($wrong_nc_ctpl !== false && $perm && $perm->isSupervisor()) {
            $result .= "<div style='color: red;'>" .
                        sprintf(CONTROL_CLASS_CLASS_OBJECTSLIST_WRONG_NC_CTPL, $_db_sub, $_db_cc, $nc_ctpl) .
                        $wrong_nc_ctpl .
                        "</div>";
        }

        // *** Префикс списка объектов ***

        if (!$ignore_prefix && !$nc_has_fatal_template_error) {
            if ($component_file_mode) {
                $nc_parent_field_path = $file_class->get_parent_field_path('FormPrefix');
                $nc_field_path = $file_class->get_field_path('FormPrefix');

                ob_start();
                try {
                    if (PHP_MAJOR_VERSION === 5) {
                        nc_check_php_file($nc_field_path);
                    }
                    if ((include $nc_field_path) === false) {
                        throw new nc_tpl_exception_include($nc_field_path);
                    }
                } catch (Throwable $e) { // PHP 7
                    $file_class->print_error_message($nc_field_path, $e);
                    $nc_has_fatal_template_error = true;
                } catch (Exception $e) { // PHP 5
                    $file_class->print_error_message($nc_field_path, $e);
                    $nc_has_fatal_template_error = true;
                }
                $result .= ob_get_clean();

                $nc_parent_field_path = null;
                $nc_field_path = null;
            }
            else {
                // «компоненты v4»
                if ($cc_env['FormPrefix']) {
                    eval(nc_check_eval("\$result.= \"" . $cc_env["FormPrefix"] . "\";"));
                }
            }
        }
        else {
            $result .= $f_AdminCommon;
        }

        // если список пуст, внутри админки нужно показать сообщение "нет объектов"
        if ($inside_admin && $totRows == 0 && !strlen(trim($result))) {
            $result .= nc_print_status(NETCAT_MODERATION_NO_OBJECTS_IN_SUBCLASS, 'info', null, 1);
        }


        // *************************** Листинг объектов ****************************

        $cache_vars = array();
        $iteration_RecordTemplate = array();

        // переменные, которые будут созданы при extract’е:
        $f_RowID = $f_User_ID = $f_UserID = $f_LastUserID = $f_LastUser_ID = 0;
        $f_Subdivision_ID = $f_Sub_Class_ID = $f_Message_ID =  0;
        $f_Hidden_URL = $f_Keyword = $f_EnglishName = '';
        $f_Created = $f_LastUpdated = null;
        $f_Priority = $f_Checked = $f_PermissionGroup_ID = 0;
        // переменные, значение которых будет присвоено в случае компонента-агрегатора
        $f_db_Subdivision_ID = $f_db_Class_ID = $f_db_Sub_Class_ID = $f_db_Message_ID = 0;
        $f_db_Keyword = '';
        // переменные, значение которых будет присвоено в цикле
        $f_Created_year = $f_Created_month = $f_Created_day =
            $f_Created_hours = $f_Created_minutes = $f_Created_seconds =
            $f_Created_date = $f_Created_time = null;
        $f_LastUpdated_year = $f_LastUpdated_month = $f_LastUpdated_day =
            $f_LastUpdated_hours = $f_LastUpdated_minutes = $f_LastUpdated_seconds =
            $f_LastUpdated_date = $f_LastUpdated_time = null;
        $f_AdminInterface_user_add = $f_AdminInterface_user_change = '';
        $fullRSSLink = $fullXMLLink = $subscribeMessageLink = $msgLink = '';
        $nc_token_for_drop = ($routing_module_enabled && $nc_core->token->is_use('drop')
                                ? array('nc_token' => $nc_core->token->get())
                                : null);
        $nc_sub_folder_length = ($routing_module_enabled ? strlen($SUB_FOLDER) : null);

        // Список названий переменных для частичного кэширования
        $cache_vars_name = null;
        if ($rowCount && $cache_module_enabled && $no_cache_marks) {
            if ($component_file_mode) {
                $cache_vars_name = array_keys($fetch_row[0]);
                foreach ($cache_vars_name as &$_variable_name) {
                    $_variable_name = "f_$_variable_name";
                }
            }
            else {
                if (preg_match('/^list\((.*?)\)/', $fetch_row, $matches)) {
                    $cache_vars_name_string = preg_replace('/[$\s]+/', '', $matches[1]);
                    $cache_vars_name = explode(",", $cache_vars_name_string);
                }
            }
            unset($_variable_name);
        }

        // Прежние названия переменных в fetch_row (v4): f_UserID, f_LastUserID, f_UserGroup, Hidden_URL
        $nc_compatibility_variable_map = array(
            'f_User_ID' => 'f_UserID',
            'f_LastUser_ID' => 'f_LastUserID',
            'f_PermissionGroup_ID' => 'f_UserGroup',
            'f_Hidden_URL' => 'Hidden_URL',
        );

        if (!$component_file_mode && $result_vars) {
            // Не затирать значения переменных, которые указаны в $result_vars:
            foreach ($nc_compatibility_variable_map as $nc_v5_variable_name => $nc_v4_variable_name) {
                if (preg_match('/\$' . $nc_v4_variable_name . '\b/', $result_vars)) {
                    unset($nc_compatibility_variable_map[$nc_v5_variable_name]);
                }
            }
            // Проверить, есть ли $f_RowID в $result_vars:
            $nc_result_vars_has_row_id = (bool)preg_match('/\$f_RowID\b/', $result_vars);
        }
        else {
            $nc_result_vars_has_row_id = false;
        }

        // *** Кнопка модуля landing ***

        $nc_show_landing_button =
            $admin_mode &&
            !$nc_prepared_data &&
            nc_module_check_by_keyword('landing') &&
            nc_landing::get_instance()->has_presets_for_component($classID);

        $nc_show_drag_button = $admin_mode && nc_show_drag_handler($cc, $query_order);

        // *** Проверка кода, который может попасть в eval() в цикле ***

        $system_env['AdminButtons'] = $nc_core->security->php_filter->filter($system_env['AdminButtons'], 'string');
        if ($component_file_mode) {
            $nc_evaluated_record_template = null;
        } else {
            $nc_evaluated_record_template = nc_preg_replace('/\$result\b/', '$row', $cc_env['RecordTemplate']);
            if ($nc_evaluated_record_template) {
                $nc_evaluated_record_template = $nc_core->security->php_filter->filter($nc_evaluated_record_template, 'string');
            }
        }


        // *** Перебор всех полученных записей ***

        if ($nc_add_list_container_markup && $rowCount) {
            $result = $this->inject_list_container_attributes(
                $result,
                $nc_mixins_list_css_class,
                "data-object-count='$rowCount'" . $nc_block_mixins_breakpoints_attribute
            );
        }

        for ($f_RowNum = 0; $f_RowNum < $rowCount; $f_RowNum++) {

            // *** Извлечение данных из $res или $nc_data ***

            if ($component_file_mode) {
                if (is_object($fetch_row[$f_RowNum]) && method_exists($fetch_row[$f_RowNum], 'to_array')) {
                    // duck typing, прежде всего это nc_record
                    extract($fetch_row[$f_RowNum]->to_array(), EXTR_PREFIX_ALL, 'f');
                }
                else if (is_array($fetch_row)) {
                    extract($fetch_row[$f_RowNum], EXTR_PREFIX_ALL, 'f');
                    // добываем старые переменные
                    extract($component->get_old_vars($fetch_row[$f_RowNum]), EXTR_PREFIX_ALL, 'f');
                }
                if ($nc_class_aggregator instanceof nc_class_aggregator) {
                    $fetch_row[$f_RowNum] = array_merge($fetch_row[$f_RowNum], $nc_class_aggregator_data[$f_RowNum]);
                    extract($nc_class_aggregator_data[$f_RowNum], EXTR_PREFIX_ALL, 'f');
                }
            }
            else {
                // «компоненты v4»
                eval($fetch_row);
            }

            // *** Дополнительные переменные, доступные в шаблонах (обратная совместимость) ***

            // Прежние названия переменных в fetch_row (v4): f_RowID, f_UserID, f_LastUserID, f_UserGroup, Hidden_URL
            foreach ($nc_compatibility_variable_map as $nc_v5_variable_name => $nc_v4_variable_name) {
                $$nc_v4_variable_name = $$nc_v5_variable_name;
                if ($component_file_mode && is_array($fetch_row)) {
                    $fetch_row[$f_RowNum][$nc_v4_variable_name] = $$nc_v5_variable_name;
                }
            }
            if (!$nc_result_vars_has_row_id) {
                $f_RowID = ($user_table_mode ? $f_User_ID : $f_Message_ID);
            }

            // fix fullLink для системных таблиц, у которых в old_vars не попадает EnglishName
            if ($user_table_mode) {
                $f_EnglishName = $cc_env['EnglishName'];
                $f_Hidden_URL = nc_array_value($cc_env, 'Hidden_URL');
            }

            // *** Кэширование ***

            if ($cache_module_enabled && $no_cache_marks && $cache_vars_name) {
                // caching variables array
                $cache_vars[$f_RowNum] = array();

                foreach ($cache_vars_name as $_variable_name) {
                    $cache_vars[$f_RowNum][$_variable_name] = $$_variable_name;
                }
                unset($_variable_name);
            }

            // *** Ссылки ***

            // переопределение $subLink и $cc_keyword, чтобы ссылки $fullLink вел в инфоблок,
            // в котором был добавлен объект (иначе будет вести в инфоблок, в котором объект выводится)
            $use_row_path = (!$ignore_link && !$is_mirror);
            if ($use_row_path) {
                if ($routing_module_enabled) {
                    if (!$subLink || !($subLink instanceof nc_routing_path_folder) || $subLink->get_folder_id() != $f_Subdivision_ID) {
                        $subLink = new nc_routing_path_folder($f_Subdivision_ID);
                    }
                }
                else {
                    // $f_Hidden_URL уже содержит SUB_FOLDER
                    $subLink = $f_Hidden_URL;
                }

                $cc_keyword = $f_EnglishName;
            }

            $routing_object_parameters = !$routing_module_enabled ? null :
                array(
                    'site_id' => $_db_catalogue,
                    'folder' => ($use_row_path
                                    ? substr($f_Hidden_URL, $nc_sub_folder_length) // $f_Hidden_URL включает SUB_FOLDER
                                    : nc_array_value($cc_env, 'Hidden_URL')),
                    'folder_id' => ($use_row_path ? $f_Subdivision_ID : $_db_sub),
                    'infoblock_id' => ($use_row_path ? $f_Sub_Class_ID : $_db_cc),
                    'infoblock_keyword' => $cc_keyword,
                    'object_id' => $f_RowID,
                    'object_keyword' => $f_Keyword,
                    'action' => 'full',
                    'format' => 'html',
                    'date' => $date_field && ${"f_$date_field"}
                                ? ${"f_{$date_field}_year"} . "-" . ${"f_{$date_field}_month"} . "-" . ${"f_{$date_field}_day"}
                                : null,
                );

            if (!$user_table_mode && $admin_mode && $AUTHORIZE_BY === 'User_ID') {
                $f_AdminInterface_user_add = $f_UserID;
                $f_AdminInterface_user_change = $f_LastUserID;
            }

            $f_PermissionGroup = null;

            // *** Особые типы полей ***

            // List (надо обработать custom_settings)
            $iteration_select_fields = array();
            if (!$nc_prepared_data) {
                $nc_list_fields = $component->get_fields(NC_FIELDTYPE_SELECT);
                if (!empty($nc_list_fields)) {
                    // просмотр каждого поля типа select и обработка CustomSettings
                    foreach ($nc_list_fields as $list_field) {
                        ${"f_" . $list_field['name'] . "_settings"} = $nc_core->list->evaluate_item_settings($list_field['table'], ${"f_" . $list_field['name'] . "_settings"});

                        if ($component_file_mode) {
                            $iteration_select_fields['f_' . $list_field['name'] . '_settings'] = ${"f_" . $list_field['name'] . "_settings"};
                        }
                    }
                    if ($component_file_mode) {
                        $iteration_RecordTemplate[$f_RowNum]['select_fields'] = $iteration_select_fields;
                    }
                }

                // Multiselect
                $iteration_multilist_fields = array();
                if (!empty($multilist_fields)) {
                    // просмотр каждого поля типа multiselect
                    foreach ($multilist_fields as $multilist_field) {
                        // таблицу с элементами можно взять из кэша, если ее там нет — то добавить
                        if (empty($_cache['classificator'][$multilist_field['table']])) {
                            $db_res = $db->get_results(
                                "SELECT `" . $multilist_field['table'] . "_ID` AS ID, `" . $multilist_field['table'] . "_Name` AS Name, `Value`, `CustomSettings`
                               FROM `Classificator_" . $multilist_field['table'] . "`", ARRAY_A);

                            if (!empty($db_res)) {
                                foreach ($db_res as $v) { // запись в кэш
                                    $_cache['classificator'][$multilist_field['table']][$v['ID']] = array($v['Name'], $v['Value'], $v['CustomSettings']);
                                }
                            }
                            unset($db_res);
                        }

                        ${"f_" . $multilist_field['name'] . "_id"} = array();
                        ${"f_" . $multilist_field['name'] . "_value"} = array();
                        ${"f_" . $multilist_field['name'] . "_settings"} = array();

                        if (($value = ${"f_" . $multilist_field['name']})) { // значение из базы
                            ${"f_" . $multilist_field['name']} = array();
                            ${"f_" . $multilist_field['name'] . "_id"} = array();
                            $ids = explode(',', $value);
                            if (!empty($ids)) {
                                foreach ($ids as $id) { // для каждого элемента по id определяем имя и значение
                                    if ($id) {
                                        array_push(${"f_" . $multilist_field['name']}, $_cache['classificator'][$multilist_field['table']][$id][0]);
                                        array_push(${"f_" . $multilist_field['name'] . "_value"}, $_cache['classificator'][$multilist_field['table']][$id][1]);
                                        array_push(
                                            ${"f_" . $multilist_field['name'] . "_settings"},
                                            $nc_core->list->evaluate_item_settings($multilist_field['table'], $_cache['classificator'][$multilist_field['table']][$id][2])
                                        );
                                        array_push(${"f_" . $multilist_field['name'] . "_id"}, $id);
                                    }

                                }
                            }
                        }
                        // default values
                        if (!is_array(${"f_" . $multilist_field['name']})) {
                            ${"f_" . $multilist_field['name']} = array();
                        }

                        if ($component_file_mode) {
                            $iteration_multilist_fields['f_' . $multilist_field['name']] = ${"f_" . $multilist_field['name']};
                            $iteration_multilist_fields['f_' . $multilist_field['name'] . '_value'] = ${"f_" . $multilist_field['name'] . "_value"};
                            $iteration_multilist_fields['f_' . $multilist_field['name'] . '_settings'] = ${"f_" . $multilist_field['name'] . "_settings"};
                            $iteration_multilist_fields['f_' . $multilist_field['name'] . '_id'] = ${"f_" . $multilist_field['name'] . "_id"};
                        }
                    }

                    if ($component_file_mode) {
                        $iteration_RecordTemplate[$f_RowNum]['multilist_fields'] = $iteration_multilist_fields;
                    }
                    unset($ids, $id, $value, $multilist_field, $iteration_multilist_fields);
                }

                // get file fields variables
                if ($component_file_mode) {
                    $iteration_RecordTemplate[$f_RowNum]['fields_files'] =
                        $nc_core->file_info->get_all_object_file_variables($hybrid_component_id, $f_RowID);

                    // get multifile fields variables
                    if ($multifile_field_values && count($multifile_field_values)) {
                        foreach ($multifile_field_values[$f_RowID] as $field_name => $field_value) {
                            /** @var nc_multifield $field_value */
                            if (isset(${'f_' . $field_name . '_tpl'})) {
                                $field_value->set_template(${'f_' . $field_name . '_tpl'});
                            }
                            $iteration_RecordTemplate[$f_RowNum]['multifile_fields']['f_' . $field_name] = $field_value;
                        }
                    }
                } else {
                    // «компоненты v4»
                    extract($nc_core->file_info->get_all_object_file_variables($hybrid_component_id, $f_RowID));

                    // get multifile fields variables
                    if ($multifile_field_values) {
                        foreach ($multifile_field_values[$f_RowID] as $field_name => $field_value) {
                            /** @var nc_multifield $field_value */
                            ${'f_' . $field_name} = $field_value->set_template(${'f_' . $field_name . '_tpl'});;
                        }
                    }
                }

                if ($nc_class_aggregator instanceof nc_class_aggregator && $nc_class_aggregator->has_multifile_fields($fetch_row[$f_RowNum]['db_Class_ID'])) {
                    foreach ($fetch_row[$f_RowNum] as $field_name => $field_value) {
                        if ($field_value instanceof nc_multifield && isset(${'f_' . $field_name . '_tpl'})) {
                            $field_value->set_template(${'f_' . $field_name . '_tpl'});
                        }
                    }
                }

                if ($user_table_mode) {
                    $f_PermissionGroup = &$nc_user_group_sort[$f_RowID];
                }

                // *** Части даты ***
                if (isset($f_Created)) {
                    list($nc_tmp_date, $nc_tmp_time) = explode(" ", $f_Created, 2);
                    list($f_Created_year, $f_Created_month, $f_Created_day) = explode("-", $nc_tmp_date);
                    list($f_Created_hours, $f_Created_minutes, $f_Created_seconds) = explode(":", $nc_tmp_time);
                    $f_Created_date = $f_Created_day . "." . $f_Created_month . "." . $f_Created_year;
                    $f_Created_time = $f_Created_hours . ":" . $f_Created_minutes . ":" . $f_Created_seconds;
                }

                if (isset($f_LastUpdated) && $f_LastUpdated) {
                    $f_LastUpdated_year = substr($f_LastUpdated, 0, 4);
                    $f_LastUpdated_month = substr($f_LastUpdated, 4, 2);
                    $f_LastUpdated_day = substr($f_LastUpdated, 6, 2);
                    $f_LastUpdated_hours = substr($f_LastUpdated, 8, 2);
                    $f_LastUpdated_minutes = substr($f_LastUpdated, 10, 2);
                    $f_LastUpdated_seconds = substr($f_LastUpdated, 12, 2);
                    $f_LastUpdated_date = $f_LastUpdated_day . "." . $f_LastUpdated_month . "." . $f_LastUpdated_year;
                    $f_LastUpdated_time = $f_LastUpdated_hours . ":" . $f_LastUpdated_minutes . ":" . $f_LastUpdated_seconds;
                }
            }

            if ($admin_mode && !$nc_prepared_data) {

                // *** Режим редактирования: элементы и ссылки для управления объектом в админке ***

                $dateLink = '';

                if ($date_field && nc_filter_date_for_path(${"f_{$date_field}"})) {
                    $dateLink = "&date=" . ${"f_{$date_field}_year"} . "-" . ${"f_{$date_field}_month"} . "-" . ${"f_{$date_field}_day"};
                }

                // full link for object
                $fullLink = nc_get_fullLink($admin_url_prefix, $_db_catalogue, $_db_sub, $_db_cc, $f_RowID, $inside_admin);
                $fullDateLink = nc_get_fullDateLink($fullLink, $dateLink);

                $subLink = $admin_url_prefix . '?catalogue=' . $_db_catalogue . '&amp;sub=' . $_db_sub;

                // ID объекта в шаблоне
                $f_AdminButtons_id = $f_RowID;

                // Приоритет объекта
                $f_AdminButtons_priority = $f_Priority;

                // ID добавившего пользователя
                $f_AdminButtons_user_add = $f_UserID;

                // ID изменившего пользователя
                $f_AdminButtons_user_change = nc_get_AdminButtons_user_change($f_LastUserID);

                $f_AdminButtons_copy = "";
                $f_AdminButtons_change = "";
                if ($perm->isSubClass($cc, MASK_EDIT) || $nc_can_moderate_infoblock) {
                    // копировать объект
                    $f_AdminButtons_copy = nc_get_AdminButtons_copy($ADMIN_PATH, $catalogue, $sub, $cc, $classID, $f_RowID);

                    // изменить
                    $f_AdminButtons_change = nc_get_AdminButtons_change($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos, $inside_admin);
                    $editLink = $f_AdminButtons_change;
                }

                if ($nc_core->get_settings('AutosaveUse') == 1) {
                    $f_AdminButtons_version = nc_get_AdminButtons_version($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos, $inside_admin);
                    $versionLink = $f_AdminButtons_version;
                }
                else {
                    $f_AdminButtons_version = "";
                }

                $f_AdminButtons_delete = $deleteLink = $dropLink = "";
                if ($nc_show_delete_record_button) {
                    // удалить
                    $f_AdminButtons_delete = nc_get_AdminButtons_delete($SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $f_RowID, $curPos, $inside_admin);
                    $deleteLink = $f_AdminButtons_delete;
                    $dropLink = nc_get_dropLink($deleteLink, $nc_core);
                }

                $f_AdminButtons_check = $f_AdminButtons_uncheck = $checkedLink = "";
                if ($perm->isSubClass($cc, MASK_CHECKED) || $nc_can_moderate_infoblock) {
                    // включить-выключить
                    $f_AdminButtons_check = nc_get_AdminButtons_check($f_Checked, $SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $classID, $f_RowID, $curPos, $admin_mode, $admin_url_prefix, $nc_core);
                    $f_AdminButtons_uncheck = nc_get_AdminButtons_check(!$f_Checked, $SUB_FOLDER, $HTTP_ROOT_PATH, $catalogue, $sub, $cc, $classID, $f_RowID, $curPos, $admin_mode, $admin_url_prefix, $nc_core);
                    $checkedLink = $f_AdminButtons_check;
                }

                // выбрать связанный (JS код!!!) -- когда список вызван в popup для выбора связанного объекта
                $f_AdminButtons_select = nc_get_AdminButtons_select($f_AdminButtons_id);

                if ($list_mode == 'select') {
                    $f_AdminButtons_buttons = nc_get_list_mode_select_AdminButtons_buttons($f_AdminButtons_select, $ADMIN_TEMPLATE);
                    $f_AdminButtons = nc_get_list_mode_select($f_Checked, $classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_buttons);
                }
                else {
                    $nc_show_admin_buttons =
                        $modPerm ||
                        $nc_can_moderate_infoblock ||
                        ($changePerm && $f_AdminButtons_user_add == $AUTH_USER_ID) ||
                        ($nc_can_change_own_objects && $f_AdminButtons_user_add == $AUTH_USER_ID);

                    if ($system_env['AdminButtonsType']) {
                        // проверка этого кода производится до цикла:
                        eval("\$f_AdminButtons = \"" . $system_env['AdminButtons'] . "\";");
                    }
                    else if (!$inside_admin) {
                        if ($nc_show_admin_buttons) {
                            $f_AdminButtons = nc_get_AdminButtonsMultiBlock(
                                $f_RowID,
                                $f_Checked,
                                $f_AdminButtons_check,
                                $f_AdminButtons_copy,
                                $f_AdminButtons_change,
                                $f_AdminButtons_delete,
                                $f_AdminButtons_version,
                                $cc,
                                $sub,
                                $classID,
                                $nc_show_landing_button,
                                $nc_show_drag_button
                            );
                        }
                    }
                    else if ($nc_show_admin_buttons) {
                        $f_AdminButtons_buttons = nc_get_AdminButtons_buttons($f_RowID, $f_Checked, $f_AdminButtons_check, $f_AdminButtons_uncheck, $f_AdminButtons_copy, $f_AdminButtons_change, $f_AdminButtons_delete, $f_AdminButtons_version, $cc, $sub, $classID, $nc_show_landing_button);
                        $f_AdminButtons =
                            nc_get_AdminButtons_prefix($f_Checked, $cc) .
                            nc_get_AdminButtons_modPerm($classID, $f_RowID, $f_AdminButtons_id, $f_AdminButtons_priority, $f_AdminInterface_user_add, $f_AdminButtons_user_add, $f_AdminInterface_user_change, $f_AdminButtons_user_change, $f_AdminButtons_buttons, $cc, $query_order) .
                            nc_get_AdminButtons_suffix();
                    }
                }
                if ($user_table_mode) {
                    $f_AdminButtons = "";
                }
            }
            else {

                // *** Режим просмотра: ссылки на действия с объектом ***

                $f_AdminButtons_id = "";
                $f_AdminButtons_priority = "";
                $f_AdminButtons_user_add = "";
                $f_AdminButtons_user_change = "";
                $f_AdminButtons_copy = "";
                $f_AdminButtons_change = "";
                $f_AdminButtons_version = "";
                $f_AdminButtons_delete = "";
                $f_AdminButtons_check = "";
                $f_AdminButtons_select = "";
                $f_AdminButtons = "";

                if (!isset($f_Keyword)) {
                    $f_Keyword = '';
                }

                // модуль маршрутизации: нет аналога для $msgLink
                $msgLink = $f_Keyword != '' ? $f_Keyword : $cc_keyword . "_" . $f_RowID;

                $dateLink = '';

                if ($date_field && nc_filter_date_for_path(${"f_{$date_field}"})) {
                    $dateLink = ${"f_{$date_field}_year"} . "/" . ${"f_{$date_field}_month"} . "/" . ${"f_{$date_field}_day"} . "/";
                }

                if ($routing_module_enabled) {
                    $_add_domain = ($_db_catalogue != $current_catalogue['Catalogue_ID']);

                    $fullLink = new nc_routing_path_object($classID, $routing_object_parameters, 'full', 'html', false, null, $_add_domain);
                    $fullRSSLink = $cc_env['AllowRSS']
                        ? new nc_routing_path_object($classID, $routing_object_parameters, 'full', 'rss', false, null, $_add_domain)
                        : "";
                    $fullXMLLink = $cc_env['AllowXML']
                        ? new nc_routing_path_object($classID, $routing_object_parameters, 'full', 'xml', false, null, $_add_domain)
                        : "";
                    $fullDateLink = $dateLink
                        ? new nc_routing_path_object($classID, $routing_object_parameters, 'full', 'html', true, null, $_add_domain)
                        : $fullLink;
                    $editLink = new nc_routing_path_object($classID, $routing_object_parameters, 'edit', 'html', false, null, $_add_domain);
                    if ($nc_core->get_settings('AutosaveUse') == 1) {
                        $versionLink = new nc_routing_path_object($classID, $routing_object_parameters, 'version', 'html', false, null, $_add_domain);
                    }
                    $deleteLink = new nc_routing_path_object($classID, $routing_object_parameters, 'delete', 'html', false, null, $_add_domain);
                    $dropLink = new nc_routing_path_object(
                                    $classID,
                                    $routing_object_parameters,
                                    'drop',
                                    'html',
                                    false,
                                    $nc_token_for_drop,
                                    $_add_domain);
                    $checkedLink = new nc_routing_path_object($classID, $routing_object_parameters, 'checked', 'html', false, null, $_add_domain);
                    $subscribeMessageLink = new nc_routing_path_object($classID, $routing_object_parameters, 'subscribe', 'html', false, null, $_add_domain);
                }
                else {
                    $_host = ($_db_catalogue == $current_catalogue['Catalogue_ID']) ? '' : $nc_core->catalogue->get_url_by_id($_db_catalogue);

                    $fullLink = $_host . $subLink . $msgLink . ".html"; // полный вывод
                    $fullRSSLink = $cc_env['AllowRSS'] ? $_host . $subLink . $msgLink . ".rss" : ""; // rss
                    $fullXMLLink = $cc_env['AllowXML'] ? $_host . $subLink . $msgLink . ".xml" : "";
                    $fullDateLink = $_host . $subLink . $dateLink . $msgLink . ".html"; // полный вывод с датой
                    $editLink = $_host . $subLink . "edit_" . $msgLink . ".html"; // ссылка для редактирования
                    if ($nc_core->get_settings('AutosaveUse') == 1) {
                        $versionLink = $_host . $subLink . "version_" . $msgLink . ".html"; // ссылка для черновика
                    }
                    $deleteLink = $_host . $subLink . "delete_" . $msgLink . ".html"; // удаления
                    $dropLink = $_host . $subLink . "drop_" . $msgLink . ".html" . ($nc_core->token->is_use('drop') ? "?" . $nc_core->token->get_url() : ""); // удаления без подтверждения
                    $checkedLink = $_host . $subLink . "checked_" . $msgLink . ".html"; // включения\выключения
                    $subscribeMessageLink = $_host . $subLink . "subscribe_" . $msgLink . ".html"; // подписка на объект
                }

                // Если это превью данного компонента то, мы добавляем переменную к ссылкам на полный просмотр объекта
                if ($classPreview == $cc_env["Class_ID"]) {
                    $fullLink .= "?classPreview=" . $classPreview;
                    $fullDateLink .= "?classPreview=" . $classPreview;
                }
            }

            // *** Ссылки для агрегированных объектов ***

            if (is_object($nc_class_aggregator) && $f_db_Subdivision_ID) {
                if ($routing_module_enabled) {
                    $fullLink = new nc_routing_path_object($f_db_Class_ID, array_merge(
                            $routing_object_parameters,
                            array(
                                'folder' => $nc_core->subdivision->get_by_id($f_db_Subdivision_ID, 'Hidden_URL'),
                                'folder_id' => $f_db_Subdivision_ID,
                                'infoblock_id' => $f_db_Sub_Class_ID,
                                'infoblock_keyword' => $nc_core->sub_class->get_by_id($f_db_Sub_Class_ID, 'EnglishName'),
                                'object_id' => $f_db_Message_ID,
                                'object_keyword' => $f_db_Keyword,
                            )
                        ));
                }
                else {
                    $fullLink = $SUB_FOLDER .
                                $nc_core->subdivision->get_by_id($f_db_Subdivision_ID, 'Hidden_URL') .
                                ($f_db_Keyword
                                    ? $f_db_Keyword . '.html'
                                    : $nc_core->sub_class->get_by_id($f_db_Sub_Class_ID, 'EnglishName') . '_' . $f_db_Message_ID . '.html'
                                );
                }
            }

            if ($component_file_mode) {
                $vars = array();
                $vars['f_RowID'] = $f_RowID;
                $vars['f_UserID'] = $f_UserID;
                $vars['f_LastUserID'] = $f_LastUserID;
                $vars['f_AdminInterface_user_add'] = $f_AdminInterface_user_add;
                $vars['f_AdminInterface_user_change'] = $f_AdminInterface_user_change;
                $vars['subLink'] = $subLink;
                $vars['cc_keyword'] = $cc_keyword;
                $vars['fullLink'] = $fullLink;
                $vars['fullDateLink'] = $fullDateLink;
                $vars['fullRSSLink'] = $fullRSSLink;
                $vars['fullXMLLink'] = $fullXMLLink;
                $vars['editLink'] = $editLink;
                if ($nc_core->get_settings('AutosaveUse') == 1) {
                    $vars['versionLink'] = $versionLink;
                }
                $vars['deleteLink'] = $deleteLink;
                $vars['dropLink'] = $dropLink;
                $vars['checkedLink'] = $checkedLink;
                $vars['subscribeMessageLink'] = $subscribeMessageLink;
                $vars['f_Keyword'] = $f_Keyword;
                $vars['msgLink'] = $msgLink;
                $vars['dateLink'] = $dateLink;
                $vars['date_field'] = $date_field;
                $vars['f_AdminButtons_id'] = $f_AdminButtons_id;
                $vars['f_AdminButtons_priority'] = $f_AdminButtons_priority;
                $vars['f_AdminButtons_user_add'] = $f_AdminButtons_user_add;
                $vars['f_AdminButtons_user_change'] = $f_AdminButtons_user_change;
                $vars['f_AdminButtons_copy'] = $f_AdminButtons_copy;
                $vars['f_AdminButtons_change'] = $f_AdminButtons_change;
                if ($nc_core->get_settings('AutosaveUse') == 1) {
                    $vars['f_AdminButtons_version'] = $f_AdminButtons_version;
                }
                $vars['f_AdminButtons_delete'] = $f_AdminButtons_delete;
                $vars['f_AdminButtons_check'] = $f_AdminButtons_check;
                $vars['f_AdminButtons_select'] = $f_AdminButtons_select;
                $vars['f_AdminButtons'] = $f_AdminButtons;
                $vars['f_PermissionGroup'] = $f_PermissionGroup;
                $vars['f_Created_year'] = $f_Created_year;
                $vars['f_Created_month'] = $f_Created_month;
                $vars['f_Created_day'] = $f_Created_day;
                $vars['f_Created_hours'] = $f_Created_hours;
                $vars['f_Created_minutes'] = $f_Created_minutes;
                $vars['f_Created_seconds'] = $f_Created_seconds;
                $vars['f_Created_date'] = $f_Created_date;
                $vars['f_Created_time'] = $f_Created_time;

                if (isset($f_LastUpdated) && $f_LastUpdated) {
                    $vars['f_LastUpdated'] = $f_LastUpdated;
                    $vars['f_LastUpdated_year'] = $f_LastUpdated_year;
                    $vars['f_LastUpdated_month'] = $f_LastUpdated_month;
                    $vars['f_LastUpdated_day'] = $f_LastUpdated_day;
                    $vars['f_LastUpdated_hours'] = $f_LastUpdated_hours;
                    $vars['f_LastUpdated_minutes'] = $f_LastUpdated_minutes;
                    $vars['f_LastUpdated_seconds'] = $f_LastUpdated_seconds;
                    $vars['f_LastUpdated_date'] = $f_LastUpdated_date;
                    $vars['f_LastUpdated_time'] = $f_LastUpdated_time;
                }

                $iteration_RecordTemplate[$f_RowNum]['vars'] = $vars;

                unset($vars);
            }
            else {
                // «компоненты v4»
                $row = "";
                eval($cc_env['convert2txt']);
                // проверка кода производится до цикла:
                eval("\$row = \"" . $nc_evaluated_record_template . "\";");

                // внутри админки: для того, чтобы объекты можно было перетаскивать...
                // ... сделаем "обертку" с ID, номером класса и ID родителя:
                if ($admin_mode) {
                    $row = nc_finishing_RecordTemplate($row, $inside_admin, $classID, $f_RowID, $parent_message, $cc, $cc_env["Class_Name"], $no_cache_marks);
                }
                else if ($no_cache_marks) {
                    $row = nc_add_no_cache_marks($row, $f_RowID);
                }

                $result .= $row;
            }

        } // "foreach row"

        if ($component_file_mode && !$nc_has_fatal_template_error) {
            $nc_parent_field_path = $file_class->get_parent_field_path('RecordTemplate');
            $nc_field_path = $file_class->get_field_path('RecordTemplate');

            ob_start();
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
            $result .= ob_get_clean();

            $nc_parent_field_path = null;
            $nc_field_path = null;
            unset($iteration_RecordTemplate);
        }

        if ($nc_add_list_container_markup && $rowCount && $this->add_list_div) {
            $result .= '</div>';
        }

        // (Конец блока «листинг объектов»)


        // *** Суффикс списка объектов ***

        if (!$ignore_suffix && !$nc_has_fatal_template_error) {
            if ($component_file_mode) {
                $nc_parent_field_path = $file_class->get_parent_field_path('FormSuffix');
                $nc_field_path = $file_class->get_field_path('FormSuffix');

                ob_start();
                try {
                    if (PHP_MAJOR_VERSION === 5) {
                        nc_check_php_file($nc_field_path);
                    }
                    if ((include $nc_field_path) === false) {
                        throw new nc_tpl_exception_include($nc_field_path);
                    }
                } catch (Throwable $e) { // PHP 7
                    $result = $nc_block_markup_prefix; // не выводить префикс и список объектов, так как могут быть незакрыты тэги
                    $file_class->print_error_message($nc_field_path, $e);
                    $nc_has_fatal_template_error = true;
                } catch (Exception $e) { // PHP 5
                    $result = $nc_block_markup_prefix;
                    $file_class->print_error_message($nc_field_path, $e);
                    $nc_has_fatal_template_error = true;
                }
                $result .= ob_get_clean();

                $nc_parent_field_path = null;
                $nc_field_path = null;
            }
            else {
                // «компоненты v4»
                if ($cc_env['FormSuffix']) {
                    eval(nc_check_eval("\$result .= \"" . $cc_env["FormSuffix"] . "\";"));
                }
            }
        }

        // Вывод панели управления блоком, если шаблон не был использован из-за ошибки
        if ($nc_has_fatal_template_error && $perm && $perm->isSubClassAdmin($cc)) {
            $result = $f_AdminCommon . $result;
        }

        $result .= $nc_block_markup_suffix;

        // добавить скрипт для D&D
        if (($admin_mode || $inside_admin) && !$user_table_mode && $perm->isSubClass($cc, MASK_MODERATE)) {
            // приоритет позволять менять только если отсортировано по умолчанию (Priority DESC)
            $change_priority = nc_show_drag_handler($cc, $query_order) ? 'true' : 'false';
            $result .= "<script>";
            $result .= "if (typeof formAsyncSaveEnabled != 'undefined') { messageInitDrag(" .
                        nc_array_json(array($classID => $row_ids)) . ", " . $change_priority .
                        "); }";
            $result .= "</script>";
        }

        // title
        if ($isMainContent && (!$isSubClassArray || (isset($cc_array[0]) && $cc_array[0] == $cc))) {
            $title = '';
            if (empty($current_sub['Title']) && $cc_env['TitleList']) {
                eval(nc_check_eval("\$title = \"" . $cc_env['TitleList'] . "\";"));
            }

            if ($title) {
                $nc_core->page->set_metatags('title', $title);
                $cc_env['Cache_Access_ID'] = 2;
            }
        }

        // cache section
        if (nc_module_check_by_keyword("cache") && $cc_env['Cache_Access_ID'] == 1 && is_object($nc_cache_list) && !$user_table_mode && !$nc_prepared_data) {
            try {
                $bytes = $nc_cache_list->add($_db_sub, $_db_cc, $cache_key, $result, $cache_vars);
                if ($no_cache_marks) {
                    $result = $nc_cache_list->nocacheClear($result);
                }
                // debug info
                if ($bytes) {
                    $cache_debug_info = "Written, sub[" . $_db_sub . "], cc[" . $_db_cc . "], Access_ID[" . $cc_env['Cache_Access_ID'] . "], Lifetime[" . $cc_env['Cache_Lifetime'] . "], bytes[" . $bytes . "]";
                    $nc_cache_list->debugMessage($cache_debug_info, __FILE__, __LINE__, "ok");
                }
            } catch (Exception $e) {
                $nc_cache_list->errorMessage($e);
            }
        }
        $data_name = (
            $nc_add_block_markup &&
            $cc_env['Parent_Sub_Class_ID'] &&
            $nc_core->sub_class->get_meta($cc_env['Parent_Sub_Class_ID'], 'expose_children_object_names')
        )   ? ' data-object-name="' . htmlspecialchars($cc_env['Sub_Class_Name'] ?: $cc_env['Class_Name']) . '"'
            : '';

        if ($admin_mode) {
            $container_id = $cc_env['Parent_Sub_Class_ID'];
            $add_toolbars = $nc_can_admin_infoblock && !$nc_core->inside_admin && ($isSubClassArray || $container_id);

            $result = "<div id='nc_admin_mode_content{$cc}' class='nc-infoblock nc_admin_mode_content $nc_mixins_css_class'$data_name$nc_block_mixins_breakpoints_attribute>" .
                      ($add_toolbars ? nc_admin_infoblock_insert_toolbar($sub, $nc_area_keyword, $container_id, 'before', $cc) : '') .
                      $result .
                      ($add_toolbars ? nc_admin_infoblock_insert_toolbar($sub, $nc_area_keyword, $container_id, 'after', $cc) : '') .
                      "</div>";
        } else if ($nc_add_block_markup) {
            $result = "<div class='$nc_mixins_css_class'$data_name$nc_block_mixins_breakpoints_attribute>$result</div>";
        }

        return $result;
    }

    /**
     * Возвращает параметр src для загрузки этого фрагмента через partial.php
     *
     * @return string
     */
    protected function get_src() {
        return $this->infoblock_id;
    }

    /**
     * @param $html
     * @return bool|int
     */
    protected function get_last_tag_position($html) {
        $length = $position = strlen($html);
        while (false !== ($position = strrpos($html, '<', $position - $length - 1))) {
            if (preg_match('/[\w]/', $html[$position + 1])) {
                return $position;
            }
        }
        return false;
    }

    /**
     * Вставляет разметку для применения миксинов расположения элементов списка.
     * Если последний тэг в префиксе — <ul> или <ol>, то добавляет к класс к нему;
     * иначе добавляет <div> вокруг списка.
     *
     * @param string $html
     * @param string $mixins_list_css_class
     * @param string $attributes
     * @return string
     */
    protected function inject_list_container_attributes($html, $mixins_list_css_class, $attributes) {
        $list_container_css_classes = "tpl-block-list-objects $mixins_list_css_class";

        // если последний тэг в префиксе — <ul>, то используем его для разметки списка вместо добавления <div>
        $last_tag_position = $this->get_last_tag_position($html);
        if ($last_tag_position) {
            preg_match('/\w+/', $html, $matches, 0, $last_tag_position + 1);
            $last_tag = strtolower($matches[0]);
            $this->add_list_div = ($last_tag !== 'ul' && $last_tag !== 'ol');
        } else {
            $this->add_list_div = true;
        }

        if ($this->add_list_div) {
            $html .= "<div class='$list_container_css_classes' $attributes>";
        } else {
            $html = $this->add_attributes_to_tag($html, $last_tag_position, $list_container_css_classes, $attributes);
        }

        return $html;
    }

    /**
     * Добавляет указанные класс и атрибуты к тегу в указанной позиции в строке
     * @param string $html модифицируемая строка
     * @param int $tag_start положение тега в строке (положение '<')
     * @param string $class класс, который будет добавлен к элементу
     * @param string $attributes строка атрибутами, которая будет добавлена к элементу
     * @return string
     */
    protected function add_attributes_to_tag($html, $tag_start, $class, $attributes) {
        // (1) смотрим, есть ли атрибут class='x', class="x", class=x
        $tag_end = strpos($html, '>', $tag_start) ?: strlen($html) - 1;
        $class_value_start = $class_value = $class_has_quote = null;

        if (
            preg_match('/class\s*=\s*(?P<quote>[\'"])?(?P<class>.*?)(?P=quote)/is', $html, $matches, PREG_OFFSET_CAPTURE, $tag_start) ||
            preg_match('/class\s*=\s*(?P<class>[^\s>]+)/is', $html, $matches, PREG_OFFSET_CAPTURE, $tag_start)
        ) {
            $class_value = $matches['class'][0];
            $class_value_start = $matches['class'][1];
            $class_has_quote = !empty($matches['quote'][0]);
        }

        if ($class_value_start && $class_value_start < $tag_end) {
            // вставляем класс в существующий атрибут class
            $new_class_value = "$class_value $class";
            if (!$class_has_quote) {
                $new_class_value = "'$new_class_value'";
            }
            $html = substr_replace($html, $new_class_value, $class_value_start, strlen($class_value));
        } else {
            // добавляем атрибут class
            $html = substr_replace($html, " class='$class'>", $tag_end, 1);
        }

        // (2) добавляем прочие атрибуты в конец тега
        $tag_end = strpos($html, '>', $tag_start) ?: strlen($html) - 1;
        $html = substr_replace($html, " $attributes>", $tag_end, 1);

        return $html;
    }

    public function get_filter_query_where() {
        return $this->process_template(true);
    }

    protected function get_add_form(array $variables) {
        $variables += array(
            // переменные, которые используются в сгенерированной форме добавления и должны быть определены
            'warnText' => 0,
            'systemTableID' => $variables['user_table_mode'] ? 3 : 0,
            'f_Parent_Message_ID' => 0,
            'f_Priority' => null,
            'f_Keyword' => null,
            'f_ncTitle' => null,
            'f_ncKeywords' => null,
            'f_ncDescription' => null,
            'class_id' => $variables['classID'],
        );

        extract($variables);

        /** @var nc_component $component */
        foreach ($component->get_fields(NC_FIELDTYPE_MULTIFILE) as $multifield_row) {
            ${'f_' . $multifield_row['name']} = new nc_multifield(
                $multifield_row['name'],
                $multifield_row['description'],
                $multifield_row['format'],
                $multifield_row['id'],
                $user_table_mode ? 'User' : $classID
            );
        }

        if ($component_file_mode) {
            $nc_parent_field_path = $file_class->get_parent_field_path('AddTemplate');
            $nc_field_path = $file_class->get_field_path('AddTemplate');

            ob_start();
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
            $addForm = ob_get_clean();

            if (!$addForm) {
                $addTemplate = $component->add_form($catalogue, $sub, $cc);
                eval(nc_check_eval("\$addForm = \"" . $addTemplate . "\";"));
            }
        } else {
            // «компоненты v4»
            eval(nc_check_eval("\$addForm = \"" . $cc_env["AddTemplate"] . "\";"));
        }

        if ($addForm && $nc_add_block_markup) {
            $addForm = "<div class='tpl-block-add-form $nc_component_css_class'>" . $addForm . "</div>";
        }

        return $addForm;
    }
}
