<?php

/**
 * Отличия от базового класса nc_record: данные в модуле поиска
 * всегда сохраняются в UTF-8, независимо от кодировки системы. Для поддержки
 * этой возможности добавлены дополнительные свойства и методы.
 */
abstract class nc_search_data_persistent extends nc_record {

    protected $strict_property_mode = true;
    protected $throw_exception_on_error = true;

    /**
     * Внутренняя кодировка, используемая для значений внутри data object и в БД.
     * Если не установлено, записи в БД сохраняются в кодировке системы. (MYSQL_CHARSET)
     * Если равно "utf-8", записи в БД сохраняются в UTF-8 вне зависимости от кодировки системы.
     * Допустимые значения: "utf-8" или null
     * @var string
     */
    protected $internal_encoding = "utf-8";

    /**
     * Определяет, в какой кодировке значения выводятся методом get().
     * Если не установлено, равно $internal_encoding.
     * Если не равно $storage_encoding, в get() производится конвертирование кодировки
     * (из $internal_encoding в $io_encoding).
     * Допустимые значения: "utf-8", "windows-1251"
     *
     * Конвертация значений для set() должна производиться до вызова set()!
     *
     * @var string
     */
    protected $output_encoding = "utf-8";

    /**
     * @param array $values
     */
    public function __construct(array $values = null) {
        parent::__construct($values);

        if (!$this->internal_encoding) { $this->internal_encoding = nc_core('NC_CHARSET'); }
        if (!$this->output_encoding) { $this->output_encoding = $this->internal_encoding; }
    }

    // ------------------ CHARSET/ENCODING-RELATED METHODS ---------------------

    /**
     * Установка кодировки, в которой поступают входящие данные и выводятся результаты
     * @param $encoding  "utf-8" или "windows-1251"
     * @return static
     */
    public function set_output_encoding($encoding) {
        if ($encoding) { $this->output_encoding = $encoding; }
        return $this;
    }

    /**
     *
     */
    protected function set_mysql_encoding() {
        if ($this->internal_encoding == 'utf-8' && nc_core('MYSQL_CHARSET') != 'utf8') {
            nc_db()->query("SET NAMES utf8");
        }
    }

    /**
     *
     */
    protected function restore_mysql_encoding() {
        if ($this->internal_encoding == 'utf-8' && nc_core('MYSQL_CHARSET') != 'utf8') {
            nc_db()->query("SET NAMES " . nc_core('MYSQL_CHARSET'));
        }
    }

    /**
     * @param string $option
     * @return mixed
     */
    public function get($option) {
        $value = parent::get($option);

        if ($this->internal_encoding != $this->output_encoding) {
            $value = nc_core('utf8')->conv($this->internal_encoding, $this->output_encoding, $value);
        }

        return $value;
    }

    /**
     * Сохранение в БД
     */
    public function save() {
        $this->set_mysql_encoding();
        try {
            parent::save();
        }
        catch (Exception $e) {
            $this->restore_mysql_encoding();
            throw $e;
        }
        $this->restore_mysql_encoding();
        return $this;
    }

    /**
     *
     */
    protected function select_from_database($query) {
        $this->set_mysql_encoding();
        try {
            $result = parent::select_from_database($query);
            $this->restore_mysql_encoding();
            return $result;
        }
        catch (Exception $e) {
            $this->restore_mysql_encoding();
            throw $e;
        }
    }

}
