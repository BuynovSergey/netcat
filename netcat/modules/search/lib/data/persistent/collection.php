<?php

/**
 * 
 */
class nc_search_data_persistent_collection extends nc_record_collection {

    /**
     * @param string $encoding  "utf-8", "windows-1251"
     * @return static
     */
    public function set_output_encoding($encoding) {
        $this->each('set_output_encoding', $encoding);
        return $this;
    }

    /**
     *
     */
    protected function set_mysql_encoding() {
        if (nc_core('MYSQL_CHARSET') != 'utf8') {
            nc_db()->query("SET NAMES utf8");
        }
    }

    /**
     *
     */
    protected function restore_mysql_encoding() {
        if (nc_core('MYSQL_CHARSET') != 'utf8') {
            nc_db()->query("SET NAMES " . nc_core('MYSQL_CHARSET'));
        }
    }

    /**
     *
     * @param string $query  SQL query
     *   Вместо имени таблицы можно использовать '%t%'
     * @throws nc_search_data_exception|Exception
     * @return nc_search_data_persistent_collection
     */
    public function select_from_database($query) {
        try {
            $this->set_mysql_encoding();
            parent::select_from_database($query);
            $this->restore_mysql_encoding();
        }
        catch (Exception $e) {
            $this->restore_mysql_encoding();
            throw $e;
        }

        return $this;
    }


}
