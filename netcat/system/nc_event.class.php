<?php

class nc_Event extends nc_System {

    const AFTER_MODULES_LOADED              = 'modulesLoaded';
    const AFTER_MODULE_ENABLED              = 'checkModule';
    const AFTER_MODULE_DISABLED             = 'uncheckModule';
    const BEFORE_MODULES_LOADED             = 'modulesLoadedPrep';
    const BEFORE_MODULE_ENABLED             = 'checkModulePrep';
    const BEFORE_MODULE_DISABLED            = 'uncheckModulePrep';

    const AFTER_SITE_CREATED                = 'addCatalogue';
    const AFTER_SITE_UPDATED                = 'updateCatalogue';
    const AFTER_SITE_DELETED                = 'dropCatalogue';
    const AFTER_SITE_ENABLED                = 'checkCatalogue';
    const AFTER_SITE_DISABLED               = 'uncheckCatalogue';
    const AFTER_SITE_IMPORTED               = 'importCatalogue';
    const BEFORE_SITE_CREATED               = 'addCataloguePrep';
    const BEFORE_SITE_UPDATED               = 'updateCataloguePrep';
    const BEFORE_SITE_DELETED               = 'dropCataloguePrep';
    const BEFORE_SITE_ENABLED               = 'checkCataloguePrep';
    const BEFORE_SITE_DISABLED              = 'uncheckCataloguePrep';
    const BEFORE_SITE_IMPORTED              = 'importCataloguePrep';

    const AFTER_SUBDIVISION_CREATED         = 'addSubdivision';
    const AFTER_SUBDIVISION_UPDATED         = 'updateSubdivision';
    const AFTER_SUBDIVISION_DELETED         = 'dropSubdivision';
    const AFTER_SUBDIVISION_ENABLED         = 'checkSubdivision';
    const AFTER_SUBDIVISION_DISABLED        = 'uncheckSubdivision';
    const BEFORE_SUBDIVISION_CREATED        = 'addSubdivisionPrep';
    const BEFORE_SUBDIVISION_UPDATED        = 'updateSubdivisionPrep';
    const BEFORE_SUBDIVISION_DELETED        = 'dropSubdivisionPrep';
    const BEFORE_SUBDIVISION_ENABLED        = 'checkSubdivisionPrep';
    const BEFORE_SUBDIVISION_DISABLED       = 'uncheckSubdivisionPrep';

    const AFTER_INFOBLOCK_CREATED           = 'addSubClass';
    const AFTER_INFOBLOCK_UPDATED           = 'updateSubClass';
    const AFTER_INFOBLOCK_DELETED           = 'dropSubClass';
    const AFTER_INFOBLOCK_ENABLED           = 'checkSubClass';
    const AFTER_INFOBLOCK_DISABLED          = 'uncheckSubClass';
    const BEFORE_INFOBLOCK_CREATED          = 'addSubClassPrep';
    const BEFORE_INFOBLOCK_UPDATED          = 'updateSubClassPrep';
    const BEFORE_INFOBLOCK_DELETED          = 'dropSubClassPrep';
    const BEFORE_INFOBLOCK_ENABLED          = 'checkSubClassPrep';
    const BEFORE_INFOBLOCK_DISABLED         = 'uncheckSubClassPrep';

    const AFTER_COMPONENT_CREATED           = 'addClass';
    const AFTER_COMPONENT_UPDATED           = 'updateClass';
    const AFTER_COMPONENT_DELETED           = 'dropClass';
    const AFTER_COMPONENT_TEMPLATE_CREATED  = 'addClassTemplate';
    const AFTER_COMPONENT_TEMPLATE_UPDATED  = 'updateClassTemplate';
    const AFTER_COMPONENT_TEMPLATE_DELETED  = 'dropClassTemplate';
    const BEFORE_COMPONENT_CREATED          = 'addClassPrep';
    const BEFORE_COMPONENT_UPDATED          = 'updateClassPrep';
    const BEFORE_COMPONENT_DELETED          = 'dropClassPrep';
    const BEFORE_COMPONENT_TEMPLATE_CREATED = 'addClassTemplatePrep';
    const BEFORE_COMPONENT_TEMPLATE_UPDATED = 'updateClassTemplatePrep';
    const BEFORE_COMPONENT_TEMPLATE_DELETED = 'dropClassTemplatePrep';

    const AFTER_OBJECT_CREATED              = 'addMessage';
    const AFTER_OBJECT_UPDATED              = 'updateMessage';
    const AFTER_OBJECT_DELETED              = 'dropMessage';
    const AFTER_OBJECT_ENABLED              = 'checkMessage';
    const AFTER_OBJECT_DISABLED             = 'uncheckMessage';
    const BEFORE_OBJECT_CREATED             = 'addMessagePrep';
    const BEFORE_OBJECT_UPDATED             = 'updateMessagePrep';
    const BEFORE_OBJECT_DELETED             = 'dropMessagePrep';
    const BEFORE_OBJECT_ENABLED             = 'checkMessagePrep';
    const BEFORE_OBJECT_DISABLED            = 'uncheckMessagePrep';

    // const AFTER_SYSTEM_TABLE_CREATED        = 'addSystemTable';
    const AFTER_SYSTEM_TABLE_UPDATED        = 'updateSystemTable';
    // const AFTER_SYSTEM_TABLE_DELETED        = 'dropSystemTable';
    // const BEFORE_SYSTEM_TABLE_CREATED       = 'addSystemTablePrep';
    const BEFORE_SYSTEM_TABLE_UPDATED       = 'updateSystemTablePrep';
    // const BEFORE_SYSTEM_TABLE_DELETED       = 'dropSystemTablePrep';

    const AFTER_TEMPLATE_CREATED            = 'addTemplate';
    const AFTER_TEMPLATE_UPDATED            = 'updateTemplate';
    const AFTER_TEMPLATE_DELETED            = 'dropTemplate';
    const BEFORE_TEMPLATE_CREATED           = 'addTemplatePrep';
    const BEFORE_TEMPLATE_UPDATED           = 'updateTemplatePrep';
    const BEFORE_TEMPLATE_DELETED           = 'dropTemplatePrep';

    const AFTER_USER_CREATED                = 'addUser';
    const AFTER_USER_UPDATED                = 'updateUser';
    const AFTER_USER_DELETED                = 'dropUser';
    const AFTER_USER_ENABLED                = 'checkUser';
    const AFTER_USER_DISABLED               = 'uncheckUser';
    const AFTER_USER_AUTHORIZED             = 'authorizeUser';
    const AFTER_USER_BLOCKED                = 'blockUser';
    const AFTER_USER_UNBLOCKED              = 'unblockUser';
    const BEFORE_USER_CREATED               = 'addUserPrep';
    const BEFORE_USER_UPDATED               = 'updateUserPrep';
    const BEFORE_USER_DELETED               = 'dropUserPrep';
    const BEFORE_USER_ENABLED               = 'checkUserPrep';
    const BEFORE_USER_DISABLED              = 'uncheckUserPrep';
    const BEFORE_USER_AUTHORIZED            = 'authorizeUserPrep';
    const BEFORE_USER_BLOCKED               = 'blockUserPrep';
    const BEFORE_USER_UNBLOCKED             = 'unblockUserPrep';

    const AFTER_COMMENT_CREATED             = 'addComment';
    const AFTER_COMMENT_UPDATED             = 'updateComment';
    const AFTER_COMMENT_DELETED             = 'dropComment';
    const AFTER_COMMENT_ENABLED             = 'checkComment';
    const AFTER_COMMENT_DISABLED            = 'uncheckComment';
    const BEFORE_COMMENT_CREATED            = 'addCommentPrep';
    const BEFORE_COMMENT_UPDATED            = 'updateCommentPrep';
    const BEFORE_COMMENT_DELETED            = 'dropCommentPrep';
    const BEFORE_COMMENT_ENABLED            = 'checkCommentPrep';
    const BEFORE_COMMENT_DISABLED           = 'uncheckCommentPrep';

    const AFTER_WIDGET_COMPONENT_CREATED    = 'addWidgetClass';
    const AFTER_WIDGET_COMPONENT_UPDATED    = 'editWidgetClass';
    const AFTER_WIDGET_COMPONENT_DELETED    = 'dropWidgetClass';
    const AFTER_WIDGET_CREATED              = 'addWidget';
    const AFTER_WIDGET_UPDATED              = 'editWidget';
    const AFTER_WIDGET_DELETED              = 'dropWidget';
    const BEFORE_WIDGET_COMPONENT_CREATED   = 'addWidgetClassPrep';
    const BEFORE_WIDGET_COMPONENT_UPDATED   = 'editWidgetClassPrep';
    const BEFORE_WIDGET_COMPONENT_DELETED   = 'dropWidgetClassPrep';
    const BEFORE_WIDGET_CREATED             = 'addWidgetPrep';
    const BEFORE_WIDGET_UPDATED             = 'editWidgetPrep';
    const BEFORE_WIDGET_DELETED             = 'dropWidgetPrep';

    const AFTER_FIELD_CREATED               = 'addField';
    const AFTER_FIELD_DELETED               = 'dropField';
    const AFTER_FIELD_UPDATED               = 'updateField';
    const BEFORE_FIELD_CREATED              = 'addFieldPrep';
    const BEFORE_FIELD_DELETED              = 'dropFieldPrep';
    const BEFORE_FIELD_UPDATED              = 'updateFieldPrep';

    const AFTER_LIST_CREATED                = 'addClassificator';
    const AFTER_LIST_DELETED                = 'dropClassificator';
    const AFTER_LIST_UPDATED                = 'updateClassificator';
    const BEFORE_LIST_CREATED               = 'addClassificatorPrep';
    const BEFORE_LIST_DELETED               = 'dropClassificatorPrep';
    const BEFORE_LIST_UPDATED               = 'updateClassificatorPrep';

    const AFTER_LIST_ITEM_CREATED           = 'addClassificatorItem';
    const AFTER_LIST_ITEM_DELETED           = 'dropClassificatorItem';
    const AFTER_LIST_ITEM_UPDATED           = 'updateClassificatorItem';
    const BEFORE_LIST_ITEM_CREATED          = 'addClassificatorItemPrep';
    const BEFORE_LIST_ITEM_DELETED          = 'dropClassificatorItemPrep';
    const BEFORE_LIST_ITEM_UPDATED          = 'updateClassificatorItemPrep';

    const AFTER_TEMPLATE_PARTIAL_CREATED    = 'addTemplatePartial';
    const AFTER_TEMPLATE_PARTIAL_DELETED    = 'dropTemplatePartial';
    const AFTER_TEMPLATE_PARTIAL_UPDATED    = 'updateTemplatePartial';
    const BEFORE_TEMPLATE_PARTIAL_CREATED   = 'addTemplatePartialPrep';
    const BEFORE_TEMPLATE_PARTIAL_DELETED   = 'dropTemplatePartialPrep';
    const BEFORE_TEMPLATE_PARTIAL_UPDATED   = 'updateTemplatePartialPrep';

    /** @var array  */
    private $event_names = array();
    /** @var array[] */
    private $listeners = array();
    /** @var nc_logging */
    private $logger;
    /** @var array  */
    private $no_logging = array(
        self::BEFORE_MODULES_LOADED => true,
        self::AFTER_MODULES_LOADED => true,
    );

    /**
     * Добавляет слушателя событий.
     * Рекомендуемый способ — метод nc_event::add_listener()
     *
     * @param object $object examine object
     * @param string|array $event_data
     * @return bool
     */
    public function bind(&$object, $event_data) {
        // validate
        if (!(is_string($event_data) || is_array($event_data))) {
            return false;
        }

        // remap array
        $events_remap_arr = array();

        // имя метода совпадает с именем события
        if (is_string($event_data)) {
            $event_name = $event_data;
        } else {
            // get parameters
            $event_name = key($event_data);
            $event_remap_name = current($event_data);
            next($event_data);

            // для одного метода названачены несколько событий ( перечислены через запятую )
            if (strpos($event_name, ',') && ($events = explode(',', $event_name))) {
                foreach ($events as $v) {
                    $this->bind($object, array($v => $event_remap_name));
                }
                return true;
            }

            // remap array
            $events_remap_arr = $event_data;
        }

        // already bound
        if (isset($this->listeners[$event_name]) && in_array($object, $this->listeners[$event_name], true)) {
            return true;
        }

        // bind object with remap array
        $this->listeners[$event_name][] = array('object' => $object, 'remap' => $events_remap_arr);

        return true;
    }

    /**
     * Добавляет слушателя событий
     * @param string $event_name  событие (например, константа nc_event::*)
     * @param callable $callback
     * @return bool
     */
    public function add_listener($event_name, $callback) {
        if (!is_callable($callback)) {
            return false;
        }

        $this->listeners[$event_name][] = array('callback' => $callback);

        return true;
    }

    /**
     * Добавляет слушателя для всех событий (для логирования)
     * @param nc_logging $logger
     * @return bool
     */
    public function set_logger(nc_logging $logger) {
        $this->logger = $logger;
    }

    /**
     * Оповещение слушателей о событии
     * Аргументы: название события, далее сведения о событии
     *
     */
    public function execute() {
        $args = func_get_args();

        if (empty($args) || empty($this->listeners)) {
            return false;
        }

        $event = array_shift($args);

        if (!$event) {
            return false;
        }

        if ($this->logger && empty($this->no_logging[$event])) {
            $this->logger->logging_event($event, $args);
        }

        if (empty($this->listeners[$event])) {
            return false;
        }

        foreach ($this->listeners[$event] as $object) {
            $event_method = $event;

            // check remapped events
            if (!empty($object['remap'])) {
                // remap event method
                $event_method = $object['remap'][$event] ?: "";
            }

            $callback = !empty($object['callback']) ? $object['callback'] : array($object['object'], $event_method);

            // check and execute observer method
            if (is_callable($callback)) {
                // execute event method
                call_user_func_array($callback, $args);
            }
        }

        return true;
    }

    /**
     * Возвращает список всех известных событий (стандартных и зарегистрированных
     * через nc_event::register_event())
     *
     * @return array events list
     */
    public function get_all_events() {
        $class = new ReflectionClass($this);
        return array_merge(
            array_values($class->getConstants()),
            array_keys($this->event_names)
        );
    }

    /**
     * Проверяет наличие события по имени
     *
     * @param string $event
     * @return bool result
     */
    public function check_event($event) {
        return in_array($event, $this->get_all_events(), true);
    }

    /**
     * Регистрирует текстовое описание события (используется в логгере).
     *
     * @param string $event
     * @param string $name
     * @param bool $no_logging
     * @return bool
     */
    public function register_event($event, $name, $no_logging = false) {
        if (!nc_preg_match('/^[_a-z0-9]+$/i', $event)) {
            return false;
        }

        $this->event_names[$event] = $name;

        if ($no_logging) {
            $this->no_logging[$event] = true;
        }

        return true;
    }

    /**
     * @param string $event
     * @return mixed
     */
    public function event_name($event) {
        // check base system event
        if (!$event) {
            return false;
        }

        // пользовательское имя события
        if (!empty($this->event_names[$event])) {
            return $this->event_names[$event];
        }

        $const = 'NETCAT_EVENT_' . strtoupper($event);
        return defined($const) ? constant($const) : $event;
    }
}