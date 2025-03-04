<?php

/**
 * Объединённый класс информации о файле в поле множественной загрузки:
 *
 * — обеспечивает сохранение изменений в форме добавления и редактирования
 *   (см. nc_multifield_saver)
 *   Поддерживается только сохранение файлов, переданных из $_FILES (это
 *   может быть доработано).
 *
 * — используется в качестве замены stdClass и array() со свойствами файла
 *   в nc_multifield->records.
 *   [По ошибке] записи в массиве nc_multifield->records могли быть как
 *   экземплярами stdClass (nc_load_multifield()), так и простым массивом
 *   (nc_get_multifile_field_values()). Данный класс обеспечивает совместимость
 *   обоих вариантов, предоставляя доступ как к свойствам ($file->Path), так и
 *   через ArrayAccess ($file['Path']).
 *
 *   Также при запросе $file->Path ($file['Path']) возвращается путь с учётом
 *   подпапки ($SUB_FOLDER).
 *   (NB: $file['path'] возвращает то значение, которое записано в БД — без подпапки)
 *
 *
 * Свойства, доступные напрямую ($file->$property) — значения в таблице Multifield: *
 * @property int ID
 * @property int Field_ID
 * @property int Message_ID
 * @property int Priority
 * @property string Name
 * @property int Size
 * @property string Path
 * @property string Preview
 */
class nc_multifield_file extends nc_record {

    protected $properties = array(
        // есть в Multifield:
        'id' => null,
        'field_id' => 0,
        'object_id' => 0,
        'priority' => -1,
        'name' => '',
        'size' => 0,
        'path' => null,
        'preview' => null,
        // старые значения:
        'old_priority' => 0,
        'old_name' => null,
        // удалённые файлы (как ранее загруженные, так и загружаемые)
        'is_deleted' => false,
        // данные для нового файла, к которому указан путь от папки с Netcat (в пути нельзя использовать ../)
        'source_file' => null,
        'source_file_move' => false, // true, если нужно удалить исходный файл
        // данные для нового файла из $_FILES:
        'upload_type' => null,
        'upload_name' => null,
        'upload_tmp_name' => null,
        // ошибки несоответствия формату поля (устанавливаются после загрузки файла)
        'upload_size_error' => false,
        'upload_type_error' => false,
    );

    protected $strict_property_mode = true;

    protected $table_name = 'Multifield';
    protected $primary_key = 'id';
    protected $mapping = array(
        'id' => 'ID',
        'field_id' => 'Field_ID',
        'object_id' => 'Message_ID',
        'priority' => 'Priority',
        'name' => 'Name',
        'size' => 'Size',
        'path' => 'Path',
        'preview' => 'Preview',
    );

    /** @var  nc_multifield */
    protected $multifield;

    /** @var  nc_image_path */
    protected $source = null;

    protected function get_image_source() {
        if ($this->source) {
            return $this->source;
        }
        $field_id = (int)$this->field_id;
        $file_path = $this->path;
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $image_path = new nc_image_path_multifield($file_path, $file_extension);
        $image_path->set_entity($this->multifield->get_component_id());
        $image_path->set_field($field_id);
        $image_path->set_object($this->object_id);
        $image_path->set_file_id($this->id);
        $this->source = $image_path;
        return $this->source;
    }

    public function __call($method, array $arguments) {
        if (method_exists('nc_image_path', $method)) {
            return call_user_func_array(array($this->get_image_source(), $method), $arguments);
        }
        throw new BadMethodCallException("Call to undefined method " . __CLASS__ . "::$method()");
    }

    /** @var  nc_multifield_settings */
    protected $multifield_settings;

    /**
     * @param nc_multifield $multifield
     * @return $this
     */
    public function set_multifield(nc_multifield $multifield) {
        $this->multifield = $multifield;
        $this->multifield_settings = $multifield->settings;
        return $this;
    }

    /**
     * @param nc_multifield_settings $multifield_settings
     * @return $this
     */
    public function set_multifield_settings(nc_multifield_settings $multifield_settings) {
        $this->multifield_settings = $multifield_settings;
        return $this;
    }

    /**
     * Возвращает значение настройки поля
     * @param $key
     * @return bool
     */
    protected function get_setting($key) {
        return $this->multifield_settings->$key;
    }

    /**
     * Файл ещё не сохранён, но указан файл-источник или загружен файл
     * @return bool
     */
    public function is_new() {
        return $this->has_uploaded_file() || $this->get('source_file');
    }

    /**
     * Проверяет, изменились ли приоритет и описание для ранее закачанного файла
     * @return bool
     */
    public function is_changed() {
        return !$this->is_new() && // файл не только что закачан
               !$this->get('is_deleted') && // файл не отмечен для удаления
               (
                   $this->get('priority') != $this->get('old_priority') ||
                   $this->get('name') != $this->get('old_name')
               );
    }

    /**
     * @return bool
     */
    protected function has_uploaded_file() {
        return $this->get('upload_tmp_name') != null;
    }

    /**
     * Возвращает абсолютный путь к подпапке netcat
     * @return string
     */
    protected function get_netcat_folder() {
        static $netcat_folder;
        if (!$netcat_folder) {
            $nc_core = nc_core::get_object();
            $netcat_folder = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER;
        }
        return $netcat_folder;
    }

    /**
     * Возвращает полный путь в файловой системе к файлу
     * @return bool|string
     */
    public function get_full_path() {
        if ($this->is_new() && !$this->get('path')) {
            // (a) файл указан через upload_tmp_name (закачан)
            if ($this->has_uploaded_file()) {
                return $this->get('upload_tmp_name');
            }
            // (b) файл указан через source_file
            if ($this->get('source_file')) {
                return $this->get_full_source_file_path();
            }
            trigger_error("No file (path, upload_tmp_name, source_file are not set)", E_USER_WARNING);
            return false;
        }

        return $this->get_netcat_folder() . $this->get('path');
    }

    /**
     * Возвращает полный путь к файлу source_file, или false, если файла нет
     * @return bool|string
     */
    protected function get_full_source_file_path() {
        $source_file = $this->get('source_file');
        if (!$source_file) {
            return false;
        }
        // В пути не должно быть '../' и т. п.
        $source_file = preg_replace('~\.{2,}[/\\\]~', '/', $source_file);

        $source_file = $this->get_netcat_folder() . $source_file;
        return realpath($source_file);
    }

    /**
     * Возвращает полный путь в файловой системе к файлу preview
     * @return null|string
     */
    public function get_full_preview_path() {
        $preview = $this->get('preview');
        if ($preview == null) {
            return null;
        }

        return $this->get_netcat_folder() . $preview;
    }

    /**
     * Возвращает размер файла в байтах
     * @return int
     */
    public function get_size() {
        return filesize($this->get_full_path());
    }

    /**
     * Возвращает MIME-type файла.
     * Для новых файлов пытается определить реальный (а не переданный клиентом) тип файла
     * (переданный клиентом тип используется, если не удалось определить тип файла в функции
     * nc_file_mime_type()).
     * Для старых файлов возвращает результат nc_file_mime_type().
     * @return string
     */
    public function get_mime_type() {
        if ($this->is_new()) {
            return nc_file_mime_type($this->get_full_path(), $this->get('upload_type'));
        }
        return nc_file_mime_type($this->get_full_path());
    }

    /**
     * @return string
     */
    public function get_format(){
        return pathinfo($this->get_full_path(), PATHINFO_EXTENSION);
    }

    /**
     * @return string
     */
    public function get_name(){
        return basename($this->get_full_path());
    }

    /**
     * Проверяет наличие ошибок несоответствия нового файла формату поля
     * @return bool
     */
    public function has_error() {
        return $this->get('upload_size_error') || $this->get('upload_type_error');
    }

    /**
     * Сохранение файла
     *
     * @return static
     */
    public function save() {
        if ($this->is_new()) {
            if (!$this->save_file()) {
                // возникла ошибка при сохранении файла — не добавляем запись в БД
                // (warning уже был сгенерирован в move_uploaded_file)
                return $this;
            }
        }
        return parent::save();
    }

    /**
     * Сохранение файла
     * @return bool
     */
    protected function save_file() {
        $nc_core = nc_core::get_object();

        $field_id = (int)$this->multifield->id;
        $object_id = (int)$this->get('object_id');

        $relative_folder = $this->get_setting('path') ?: ($nc_core->HTTP_FILES_PATH . "multifile/$field_id/$object_id/");
        $relative_folder = nc_standardize_path_to_folder($relative_folder);
        $absolute_folder = $this->get_netcat_folder() . $relative_folder;

        if (!file_exists($absolute_folder)) {
            mkdir($absolute_folder, $nc_core->DIRCHMOD, true);
        }

        if ($this->has_uploaded_file()) {
            $file_name = nc_get_filename_for_original_fs($this->get('upload_name'), $absolute_folder);
            if (!move_uploaded_file($this->get('upload_tmp_name'), $absolute_folder . $file_name)) {
                return false;
            }
        } else {
            $source_file = $this->get_full_source_file_path();
            $file_name = nc_get_filename_for_original_fs(basename($this->get('source_file')), $absolute_folder);
            if (!$source_file || !copy($source_file, $absolute_folder . $file_name)) {
                return false;
            }
            if ($this->get('source_file_move')) {
                unlink($source_file); // выше не rename(), а copy(), чтобы вывести warning, но продолжить выполнение дальше
            }
        }

        $this->set('path', $relative_folder . $file_name);

        if (strtolower(pathinfo($file_name, PATHINFO_EXTENSION)) === 'svg') {
            nc_sanitize_svg_file($absolute_folder . $file_name);
        }

        // если это картинка...
        if (strpos($this->get_mime_type(), 'image/') === 0) {
            if ($this->get_setting('use_preview')) {
                $this->set('preview', $relative_folder . 'preview_' . $file_name);
                $this->create_image_preview();
            }

            if ($this->get_setting('use_resize')) {
                $this->resize_image();
            }

            if ($this->get_setting('use_crop')) {
                $this->crop_image();
            }

        }

        $this->set('size', $this->get_size()); // ← должно быть отдельно от set('path') и после преобразований изображения

        return true;
    }

    /**
     * Создаёт превьюху
     */
    protected function create_image_preview() {
        $file_path = $this->get_full_path();
        $preview_path = $this->get_full_preview_path();

        copy($file_path, $preview_path);

        @nc_ImageTransform::imgResize(
            $preview_path,
            $preview_path,
            $this->get_setting('preview_width'),
            $this->get_setting('preview_height'),
            $this->get_setting('preview_mode')
        );

        if ($this->get_setting('use_preview_crop')) {
            $preview_crop_ignore = $this->get_setting('preview_crop_ignore_width') &&
                                   $this->get_setting('preview_crop_ignore_height');

            @nc_ImageTransform::imgCrop(
                $preview_path, 
                $preview_path, 
                $this->get_setting('preview_crop_x0'),
                $this->get_setting('preview_crop_y0'),
                $this->get_setting('preview_crop_x1'),
                $this->get_setting('preview_crop_y1'),
                NULL, 
                NULL,
                0, 
                0,
                $preview_crop_ignore ? $this->get_setting('preview_crop_ignore_width') : 0,
                $preview_crop_ignore ? $this->get_setting('preview_crop_ignore_height') : 0,
                $this->get_setting('preview_crop_mode'),
                $this->get_setting('preview_crop_width'),
                $this->get_setting('preview_crop_height')
            );
        }
    }

    /**
     * Уменьшение изображения
     */
    protected function resize_image() {
        $file_path = $this->get_full_path();
        @nc_ImageTransform::imgResize(
            $file_path,
            $file_path,
            $this->get_setting('resize_width'),
            $this->get_setting('resize_height'),
            $this->get_setting('resize_mode')
        );
    }

    /**
     * Обрезка изображения
     */
    protected function crop_image() {
        $file_path = $this->get_full_path();
        $crop_ignore = $this->get_setting('crop_ignore_width') && $this->get_setting('crop_ignore_height');
        @nc_ImageTransform::imgCrop(
            $file_path,
            $file_path,
            $this->get_setting('crop_x0'),
            $this->get_setting('crop_y0'),
            $this->get_setting('crop_x1'),
            $this->get_setting('crop_y1'),
            NULL,
            NULL,
            0,
            0,
            $crop_ignore ? $this->get_setting('crop_ignore_width') : 0,
            $crop_ignore ? $this->get_setting('crop_ignore_height') : 0,
            $this->get_setting('crop_mode'),
            $this->get_setting('crop_width'),
            $this->get_setting('crop_height')
        );
    }

    /**
     * Удаление файла
     */
    public function delete() {
        if (file_exists($this->get_full_path())) {
            unlink($this->get_full_path());
        }

        if (file_exists($this->get_full_preview_path())) {
            unlink($this->get_full_preview_path());
        }

        return parent::delete();
    }

    /**
     * Обеспечивает доступ к виртуальным свойствам ($this->$offset) через ArrayAccess.
     * @param mixed $offset
     * @return mixed
     * @throws nc_record_exception
     */
    public function offsetGet($offset) {
        if ($this->offsetExists($offset)) {
            return parent::offsetGet($offset);
        }

        return $this->__get($offset);
    }

    /**
     * Эмуляция совместимости со старым способом хранения информации о файлах
     * (stdClass с свойствами, соответствующими названиям колонок в таблице Multifield)
     * @param $property
     * @return mixed
     * @throws nc_record_exception
     */
    public function __get($property) {
        $offset = $this->column_to_property($property);

        if ($property === 'Path') {
            return nc_core::get_object()->SUB_FOLDER . $this->get($offset);
        }

        if ($property === 'Preview') {
            $preview_path = $this->get($offset);
            return $preview_path ? nc_core::get_object()->SUB_FOLDER . $preview_path : null;
        }

        return $offset ? $this->get($offset) : null;
    }

    /**
     * Аналог для set_values для загрузки результатов из БД
     *
     * @param array $values
     * @return static
     */
    public function set_values_from_database_result(array $values) {
        $this->set_values(array(
            'old_name' => nc_array_value($values, 'Name'),
            'old_priority' => nc_array_value($values, 'Priority'),
        ));
        return parent::set_values_from_database_result($values);
    }

}
