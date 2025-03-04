<?php

/* $Id: nc_tags.class.php 7302 2012-06-25 21:12:35Z alive $ */

class nc_tags {

    protected $core, $db;

    public function __construct() {
        $this->core = nc_Core::get_object();
        $this->db = $this->core->db;

        $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_CREATED => 'add_message'));
        $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_UPDATED => 'update_message'));
        $this->core->event->bind($this, array(nc_Event::AFTER_OBJECT_DELETED => 'drop_message'));
    }

    /**
     * Перехват события добавление объекта
     */
    public function add_message($cat_id, $sub_id, $cc_id, $class_id, $message_id) {
        // строка с тегами
        $tags_str = $this->get_tags_str($class_id, $message_id);
        if (!$tags_str) return false;
        // получаем массив
        $tags = explode(",", $tags_str);
        $tags = array_unique($tags);

        $this->add_tag($class_id, $cc_id, $message_id, $tags);

        $this->update_weight($class_id);
    }

    /**
     * Перехват события удаление объекта
     */
    public function drop_message($cat_id, $sub_id, $cc_id, $class_id, $message_id) {


        if (!is_array($message_id)) $message_id = array($message_id);
        $message_id = array_map('intval', $message_id);

        $this->db->query("DELETE FROM `Tags_Message`
                      WHERE `Class_ID`='".intval($class_id)."'
                       AND `Message_ID` IN (".join(',', $message_id).") ");

        if ($this->db->rows_affected) {
            $this->update_weight($class_id);
        }
    }

    /**
     * Перехват события изменение объекта
     */
    public function update_message($cat_id, $sub_id, $cc_id, $class_id, $message_id) {
        if (is_array($message_id))
                foreach ($message_id as $v) {
                $this->update_message($cat_id, $sub_id, $cc_id, $class_id, $v);
                return true;
            }

        $tags_str = $this->get_tags_str($class_id, $message_id);
        // поля с тегом нет
        if ($tags_str === false) return true;

        // сообщение могли перенести - обновим sub_class_id
        $this->db->query("UPDATE `Tags_Message` SET `Sub_Class_ID` = '".intval($cc_id)."' WHERE `Class_ID` = '".intval($class_id)."' AND `Message_ID` = '".intval($message_id)."'");

        // нет новых тэгов - надо просто удалить старые
        if (!$tags_str) {
            $this->drop_message($cat_id, $sub_id, $cc_id, $class_id, $message_id);
            return true;
        }

        // новые теги
        $new_tags = array_unique(explode(",", $tags_str));
        // старые теги
        $old_tags = array();
        $old_tags_data = $this->db->get_results("SELECT d.`Tag_ID` AS `id`, d.`Tag_Text` AS `text`
	                             FROM `Tags_Message` AS `m`, `Tags_Data` AS `d`
	                             WHERE m.`Sub_Class_ID`='".intval($cc_id)."'
	                             AND m.`Message_ID`='".intval($message_id)."'
                               AND m.`Tag_ID` = d.`Tag_ID`", ARRAY_A);

        if ($old_tags_data)
                foreach ($old_tags_data as $v) {
                $old_tags[$v['id']] = $v['text'];
            }
        $old_tags = array_map('trim', $old_tags);
        $new_tags = array_map('trim', $new_tags);

        // теги, которые надо удалить
        $tags_to_delete = array_keys(array_diff($old_tags, $new_tags));
        // теги, которые надо добавить
        $tags_to_add = array_diff($new_tags, $old_tags);

        if (!empty($tags_to_delete)) {
            $this->db->query("DELETE FROM `Tags_Message` WHERE Sub_Class_ID='".intval($cc_id)."' AND Message_ID='".intval($message_id)."' AND Tag_ID IN (".join(',', $tags_to_delete).")");
        }

        $this->add_tag($class_id, $cc_id, $message_id, $tags_to_add);

        $this->update_weight($class_id);
    }

    /**
     * Добавить новые теги к объекту
     * @param int номер компонента
     * @param int номер компонента в разделе
     * @param int номер объекта
     * @param array массив с тегами
     */
    public function add_tag($class_id, $cc_id, $message_id, $tags) {
        // обрабатываем каждый тег
        if (!empty($tags))
                foreach ($tags as $tag) {
                $tag = $this->db->escape(trim($tag));
                if (!$tag) continue;

                $tag_id = $this->tag_id($tag);
                $this->bind($tag_id, $class_id, $cc_id, $message_id); // привязка тега и объекта
            }
    }

    /**
     * Возвращает номер тега по его значению. Если такого тега нет, то создает
     * @param string тэг
     * @return int номер тега
     */
    public function tag_id($tag) {
        $id = $this->db->get_var("SELECT `Tag_ID` FROM `Tags_Data` WHERE `Tag_Text` = '".$this->db->escape($tag)."' ");

        if (!$id) {
            $this->db->query("INSERT INTO `Tags_Data` SET `Tag_Text` = '".$this->db->escape($tag)."'");
            $id = $this->db->insert_id;
        }

        return $id;
    }

    /**
     * Связывает тег и объект
     * @param int номер тега
     * @param int номер компонента
     * @param int номер компонента в разделе
     * @param int номер объекта
     */
    public function bind($tag_id, $class_id, $cc_id, $message_id) {
        $this->db->query("INSERT INTO `Tags_Message` SET `Tag_ID` = '".intval($tag_id)."',
            `Class_ID` = '".intval($class_id)."',
            `Sub_Class_ID` = '".intval($cc_id)."',
            `Message_ID` = '".intval($message_id)."' ");
    }

    /**
     * Обновляет таблицу с весами по номеру компонента
     * @param int номер компонента
     */
    public function update_weight($class_id) {
        // убираем все неиспользуемые теги
        $this->db->query("DELETE FROM `Tags_Data` WHERE `Tag_ID` NOT IN ( SELECT `Tag_ID` FROM `Tags_Message` ) ");
        // удаляем ненужные веса
        $this->db->query("DELETE FROM `Tags_Weight` WHERE `Tag_Weight` <= 0 OR `Class_ID` = '".intval($class_id)."' ");

        $this->db->query("INSERT INTO `Tags_Weight` (`Tag_ID`, `Tag_Weight`, 	`Sub_Class_ID`, `Class_ID` )
      SELECT `Tag_ID`, COUNT(`Message_ID`), `Sub_Class_ID`, `Class_ID`
      FROM `Tags_Message`
      WHERE `Class_ID` = '".intval($class_id)."'
      GROUP BY `Sub_Class_ID`, `Tag_ID`");
    }

    /**
     * Возвращает все теги в виде строки объекта
     * @param int номер компонента
     * @param int номер объекта
     * @return mixed, строку с тегами или false, если нет поля для тегов
     */
    public function get_tags_str($class_id, $message_id) {
        $class_id = intval($class_id);
        $message_id = intval($message_id);

        // поиск поля для тегов в компоненте, куда был добавлен объект
        $field_name = false;
        $component = new nc_Component($class_id);
        $fields = $component->get_fields(NC_FIELDTYPE_STRING);
        if (!empty($fields))
                foreach ($fields as $v) {
                $format_string = nc_field_parse_format($v['format'], NC_FIELDTYPE_STRING);
                if ($format_string['format'] == 'tags') {
                    $field_name = $this->db->escape($v['name']);
                }
            }
        if (!$field_name) return false;

        // строка с тегами
        $tags_str = $this->db->get_var("SELECT `".$field_name."` FROM `Message".$class_id."` WHERE `Message_ID` = '".$message_id."' ");
        $tags_str = trim($tags_str);
        if (!$tags_str) $tags_str = '';

        return $tags_str;
    }

}