<?php

if (!class_exists("nc_System"))
    die("Unable to load file.");

class nc_Revision extends nc_System
{

    protected $core;
    protected $indexes;

    public function __construct()
    {
        // load parent constructor
        parent::__construct();
        $this->core = nc_Core::get_object();
    }

    public function set_indexes($current_cc, $message)
    {
        $this->indexes = array(
          'Subdivision_ID' => $current_cc['Subdivision_ID'],
          'Class_ID' => $current_cc['Class_ID'],
          'Sub_Class_ID' => $current_cc['Sub_Class_ID'],
          'Message_ID' => $message
        );
    }

    public function restore_draft()
    {
        $draft = $this->select_file();
        if (!empty($draft['File']) && file_exists($draft['File']) && is_readable($draft['File'])) {
            $res = $this->core->backup->draft->import($draft['File'], array());
            return $res->get('fields');
        } else {
            return false;
        }
    }

    /**
     * 
     * Сохраняет запись о черновике в базе и сам черновик в файл
     * @param type $current_cc
     * @param type $message
     */
    public function save_draft()
    {
        //todo filter fields
        $fields = $this->core->input->fetch_post_get();

        //вставляем запись в базу
        $version_id = $this->insert_draft();

        $infoblock_id = $this->indexes['Sub_Class_ID'];
        $path = $this->core->DUMP_FOLDER .
                'drafts/' .
                $infoblock_id . '/' .
                $this->core->backup->make_filename('draft', $this->indexes['Message_ID'], $version_id);

        //создаем дамп-файл
        $file = $this->core->backup->draft->export(
            $infoblock_id,
            array(
                'path' => $path,
                'compress' => true,
                'current_cc' => $this->indexes,
                'fields' => $fields,
                'version_id' => $version_id,
            )
        );
        $this->core->db->query("UPDATE `Component_Revisions` SET `File` = '" . $this->core->db->escape($file) . "' WHERE Revision_ID='" . $version_id . "'");
    }
    
    public function check_draft_exists()
    {
        $result = false;
        $draft = $this->select_file();
        if (!empty($draft['File']) && file_exists($draft['File']) && is_readable($draft['File'])) {
            $result = true;
        }
        return $result;
    }

    /**
     * 
     * Добавляет запись о черновике в базу
     * @return int
     */
    protected function insert_draft()
    {
        $this->clean_old_draft();

        $this->core->db->query(
          "INSERT INTO `Component_Revisions`
                            (`File`, `Class_ID`, `Subdivision_ID`, `Sub_Class_ID`, `Message_ID`, `Created`)
                     VALUES ('', '" . intval($this->indexes['Class_ID']) . "', '" . intval($this->indexes['Subdivision_ID']) . "',"
          . "'" . intval($this->indexes['Sub_Class_ID']) . "', '" . intval($this->indexes['Message_ID']) . "', NOW())"
        );
        return $this->core->db->insert_id;
    }

    protected function select_file()
    {
        $where_str = "WHERE Class_ID='" . intval($this->indexes['Class_ID']) . "' AND Subdivision_ID='" . intval($this->indexes['Subdivision_ID']) . "' AND Sub_Class_ID='" . intval($this->indexes['Sub_Class_ID']) . "' AND Message_ID='" . intval($this->indexes['Message_ID']) . "'";
        $result = $this->core->db->get_results("SELECT `Revision_ID`, `File` FROM `Component_Revisions` " . $where_str . " LIMIT 1", ARRAY_A);
        if (isset($result[0]) && $result[0]['Revision_ID'] > 0) {
            $ret = $result[0];
            if (!empty($ret['File'])) {
                $ret['File'] = str_replace("//", "/", $this->core->DOCUMENT_ROOT . $this->core->SUB_FOLDER . $ret['File']);
            }
        } else {
            $ret['File'] = "";
        }
        return $ret;
    }

    /**
     * Удаляет предыдущий черновик. В дальнейшем планируется заменить на 
     * очищение версии по параметрам в настройках.
     */
    protected function clean_old_draft()
    {
        $draft = $this->select_file();
        if (!empty($draft['File']) && file_exists($draft['File']) && is_readable($draft['File'])) {
            unlink($draft['File']);
        }
        $where_str = "WHERE Class_ID='" . intval($this->indexes['Class_ID']) . "' AND Subdivision_ID='" . intval($this->indexes['Subdivision_ID']) . "' AND Sub_Class_ID='" . intval($this->indexes['Sub_Class_ID']) . "' AND Message_ID='" . intval($this->indexes['Message_ID']) . "'";
        $this->core->db->query("DELETE FROM `Component_Revisions` " . $where_str);
    }

}
