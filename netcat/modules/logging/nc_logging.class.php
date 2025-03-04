<?php

class nc_logging {

    protected function __construct() {
        $nc_core = nc_core::get_object();

        if ($nc_core->modules->get_vars('logging', 'ACTIVITY')) {
            $nc_core->event->set_logger($this);
        }
    }

    /**
     * Get or instance self object
     *
     * @return nc_logging self object
     */
    public static function get_object() {
        static $storage;
        if (!isset($storage)) {
            $storage = new self();
        }
        return $storage;
    }

    /**
     *
     */
    public function logging_event($event, $args) {
        // DEPRECATED
        global $AUTH_USER_ID;

        $args_str = is_array($args) ? serialize($args) : '';

        $info = '';
        preg_match('/^(add|update|drop|check|uncheck|authorize|edit|block|unblock)(.+)$/is', $event, $matches);
        if (!empty($matches) && $matches[1] && $matches[2]) {
            $method = 'get_' . $matches[2] . '_info';
            if (method_exists($this, $method)) {
                $info = $this->$method($matches[1], $args);
            }
        }

        $db = nc_db();
        $db->query(
            "INSERT INTO `Logging`
                SET `Event` = '" . $db->escape($event) . "',
                    `User_ID` = " . (int)$AUTH_USER_ID . ",
                    `IP` = '" . $db->escape(nc_array_value($_SERVER, 'REMOTE_ADDR')) . "',
                    `ForwardedForIP` = '" . $db->escape(nc_array_value($_SERVER, 'HTTP_X_FORWARDED_FOR')) . "',
                    `Args` = '" . $db->escape($args_str) . "',
                    `Info` = '" . $db->escape($info) . "'"
        );
    }

    /**
     * Возвращает название и ссылку на объект(ы)
     *
     * @param string $type тип объекта (catalogue, subdivision etc)
     * @param string|Callable|false|null $link_hash путь к странице админки (хэш-часть без #), либо замыкание для генерирования ссылки
     * @param int|int[] $ids ID или массив с ID
     * @param array|Closure $names название объекта(ов) — если известны, ключ равен $id
     * @return string
     */
    protected function get_link($type, $link_hash, $ids, $names = null) {
        $nc_core = nc_core::get_object();

        $get_name_method = 'get_' . $type . '_name';
        $path_prefix = $nc_core->SUB_FOLDER . $nc_core->ADMIN_PATH . '#';

        $all_links = array();
        foreach ((array)$ids as $id) {
            // получаем название
            if (is_callable($names)) {
                $name = $names($id);
            } else if (is_array($names) && !empty($names)) {
                $name = nc_array_value($names, $id);
            } else {
                $name = $this->$get_name_method($id);
            }
            // добавляем идентификатор
            $link = $name ? "$id. $name" : $id;
            // если нет ни $id, ни $name, то результатом будет строка "?"
            $link = $link ?: '?';
            // оборачиваем в ссылку, если нужно
            if ($link_hash && $id) {
                if (is_callable($link_hash)) {
                    $href = $link_hash($id);
                } else {
                    $href = "$path_prefix$link_hash($id)";
                }
                if ($href) {
                    $link = "<a target='_blank' href='" . htmlspecialchars($href, ENT_QUOTES) . "'>" . htmlspecialchars($link) . "</a>";
                }
            }
            // добавляем кавычки
            $all_links[] = NETCAT_MODULE_LOGGING_QUOTE1 . $link . NETCAT_MODULE_LOGGING_QUOTE2;
        }

        return constant('NETCAT_MODULE_LOGGING_EVENT_ESSENCE_' . strtoupper($type)) . ' ' . implode(', ', $all_links);
    }

    protected function get_class_info($action, $args) {
        $hash = $action === 'drop' ? '' : 'dataclass_fs.edit';
        return $this->get_link('class', $hash, $args[0]);
    }

    protected function get_class_name($id) {
        return nc_db()->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = $id");
    }

    protected function get_classtemplate_name($id) {
        return $this->get_class_name($id);
    }

    protected function get_classtemplate_info($action, $args) {
        $hash = $action === 'drop' ? '' : 'dataclass_fs.edit';
        return
            $this->get_link('class', 'dataclass_fs.edit', $args[0]) . ', ' .
            $this->get_link('classtemplate', $hash, $args[1]);
    }

    protected function get_templatepartial_info($action, $args) {
        list($template_id, $partial_keyword) = $args;
        if ($action === 'drop') {
            $get_link = false;
        } else {
            $get_link = function ($id) use ($template_id) {
                return "template_fs.partials_edit($template_id,$id)";
            };
        }
        return
            $this->get_link('class', 'dataclass_fs.edit', $args[0]) . ', ' .
            $this->get_link('templatepartial', $get_link, $args[1]);
    }

    protected function get_templatepartial_name($id) {
        $db = nc_db();
        return $db->get_var("SELECT `Description` FROM `Template_Partial` WHERE `Keyword` = '" . $db->escape($id) . "'");
    }

    protected function get_widgetclass_info($action, $args) {
        $hash = $action === 'drop' ? '' : 'widgetclass_fs.edit';
        return $this->get_link('widgetclass', $hash, $args[0]);
    }

    protected function get_widgetclass_name($id) {
        return nc_db()->get_var("SELECT `Name` FROM `Widget_Class` WHERE `Widget_Class_ID` = '$id'");
    }

    protected function get_field_info($action, $args) {
        $field_ids = (array)$args[0];
        $field_data = (array)$args[1];

        // данные о сущности из которой были удалены поля берем из первого поля, т.к. существует возможность удаления полей
        // только из одной сущности за раз
        $first = current($field_data);
        if ($first) {
            if ($first['System_Table_ID']) {
                $info = $this->get_link('systemtable', 'systemclass_fs.edit', $first['System_Table_ID']);
            } elseif ($first['Widget_Class_ID']) {
                $info = $this->get_link('widgetclass', 'widgetclass_fs.edit', $first['Widget_Class_ID']);
            } else {
                $info = $this->get_link('class', 'dataclass_fs.edit', $first['Class_ID']);
            }
            $info .= ', ';
        } else {
            $info = '';
        }

        $field_names = array();
        foreach ($field_data as $id => $f) {
            $field_names[$id] = $f['Description'];
        }

        $info .= $this->get_link('field', 'dataclass_fs.fields', $field_ids, $field_names);
        return $info;
    }

    protected function get_field_name($id) {
        return nc_db()->get_var("SELECT `Field_Name` FROM `Field` WHERE `Field_ID` = '$id'");
    }

    protected function get_classificator_info($action, $args) {
        return $this->get_link('classificator', 'classificator.edit', $args[0]);
    }

    protected function get_classificator_name($id) {
        return nc_db()->get_var("SELECT `Classificator_Name` FROM `Classificator` WHERE `Classificator_ID` = '$id'");
    }

    protected function get_classificatoritem_info($action, $args) {
        $nc_core = nc_core::get_object();
        list ($list_id, $list_item_id) = $args;
        $get_name = function($id) use ($list_id, $nc_core) {
            return nc_array_value($nc_core->list->get_item($list_id, $id), 'Name');
        };

        return
            $this->get_link('classificator', 'classificator.edit', $list_id) . ', ' .
            $this->get_link('classificatoritem', '', $list_item_id, $get_name);
    }

    protected function get_catalogue_info($action, $args) {
        $hash = $action === 'drop' ? '' : 'catalogue.edit';
        return $this->get_link('catalogue', $hash, $args[0]);
    }

    protected function get_catalogue_name($id) {
        return nc_db()->get_var("SELECT `Catalogue_Name` FROM `Catalogue` WHERE `Catalogue_ID` = $id");
    }

    protected function get_subdivision_info($action, $args) {
        $hash = $action === 'drop' ? '' : 'subdivision.edit';
        return
            $this->get_link('catalogue', 'catalogue.edit', $args[0]) . ', ' .
            $this->get_link('subdivision', $hash, $args[1]);
    }

    protected function get_subdivision_name($id) {
        return nc_db()->get_var("SELECT `Subdivision_Name` FROM `Subdivision` WHERE `Subdivision_ID` = $id");
    }

    protected function get_subclass_info($action, $args) {
        $hash = $action === 'drop' ? '' : 'subclass.edit';
        return
            $this->get_link('catalogue', 'catalogue.edit', $args[0]) . ', ' .
            $this->get_link('subdivision', 'subclass.list', $args[1]) . ', ' .
            $this->get_link('subclass', $hash, $args[2]);
    }

    protected function get_subclass_name($id) {
        return nc_db()->get_var("SELECT `Sub_Class_Name` FROM `Sub_Class` WHERE `Sub_Class_ID` = $id");
    }

    protected function get_message_info($action, $args) {
        list($site_id, $subdivision_id, $infoblock_id, $component_id, $object_id) = $args;

        if ($action === 'drop') {
            $get_link = false;
            $get_name = false;
        } else {
            $nc_core = nc_core::get_object();
            $get_link = function($object_id) use ($component_id) {
                return nc_object_url($component_id, $object_id);
            };
            $get_name = function($object_id) use ($component_id, $nc_core) {
                return $nc_core->message->get_object_name($component_id, $object_id);
            };
        }

        return
            $this->get_link('catalogue', 'catalogue.edit', $site_id) . ', ' .
            $this->get_link('subdivision', 'subclass.list', $subdivision_id) . ', ' .
            $this->get_link('subclass', 'object.list', $infoblock_id) . ', ' .
            $this->get_link('class', 'dataclass_fs.edit', $component_id) . ', ' .
            $this->get_link('message', $get_link, $object_id, $get_name);
    }

    protected function get_message_name($id) {
        return '';
    }

    protected function get_template_info($action, $args) {
        $hash = $action === 'drop' ? '' : 'template_fs.edit';
        return $this->get_link('template', $hash, $args[0]);
    }

    protected function get_template_name($id) {
        return nc_db()->get_var("SELECT `Description` FROM `Template` WHERE `Template_ID` = $id");
    }

    protected function get_systemtable_info($action, $args) {
        $hash = $action === 'drop' ? '' : 'systemclass_fs.edit';
        return $this->get_link('systemtable', $hash, $args[0]);
    }

    protected function get_systemtable_name($id) {
        return constant(nc_db()->get_var("SELECT `System_Table_Rus_Name` FROM `System_Table` WHERE `System_Table_ID` = $id"));
    }

    protected function get_user_info($action, $args) {
        $hash = $action === 'drop' ? '' : 'user.edit';
        return $this->get_link('user', $hash, $args[0]);
    }

    protected function get_user_name($id) {
        $nc_core = nc_core::get_object();
        return $nc_core->db->get_var("SELECT `$nc_core->AUTHORIZE_BY` FROM `User` WHERE `User_ID` = $id");
    }

    protected function get_module_info($action, $args) {
        list($keyword, $catalogue_id) = $args;
        $info = $this->get_link('module', "module.$keyword", $keyword);
        if ($catalogue_id) {
            $info .= ', ' .
                $this->get_link('catalogue', 'site.edit', $catalogue_id);
        }
        return $info;
    }

    protected function get_module_name($id) {
        $module_name = nc_db()->get_var("SELECT `Module_Name`, `Checked` FROM `Module` WHERE `Keyword` = '$id'");
        return defined($module_name) ? constant($module_name) : $module_name;
    }

    protected function get_comment_info($action, $args) {
        return
            $this->get_message_info($action, $args) . ', ' .
            $this->get_link('comment', 'module.comments.edit', nc_array_value($args, 5));
    }

    protected function get_comment_name($id) {
        return null;
    }

}
