<?php

abstract class nc_image_base {
    /**
     * Путь к файлу от DOCUMENT_ROOT
     * @var string
     */
    protected $file_path = null;

    /**
     * Расширение файла
     * @var string
     */
    protected $extension = null;

    /**
     * Данные по кропу
     * @var array
     */
    protected $crop = null;

    /**
     * Данные по ресайзу
     * @var array
     */
    protected $resize = null;

    /**
     * Данные по наложению водного знака
     * @var array
     */
    protected $watermark = null;

    /**
     * Данные по масштабированию
     * @var array
     */
    protected $scale = null;

    /**
     * Сущность, содержащая оригинальную картинку
     * Возможные значения:
     *     - число: ID класса
     *     - строка:
     *          - 'catalogue': системная таблица "Сайт"
     *          - 'subdivision': системная таблица "Раздел"
     *          - 'user': системная таблица "Пользователь"
     *          - 'class_settings': системные настройки инфоблока
     *          - 'template_settings': системные настройки макета
     * @var integer|string
     */
    protected $entity = null;

    /**
     * ID поля или название
     * @var integer|string
     */
    protected $field = null;

    /**
     * ID объекта (ID записи в компоненте, ID инфоблока, ID пользователя, и т.д.)
     * @var string
     */
    protected $object = null;

    /**
     * ID файла в многофайловом поле
     * @var string
     */
    protected $file_id = null;

    /**
     *
     * @param string|null $file_path
     * @param string|null $extension
     */
    public function __construct($file_path = null, $extension = null) {
        $this->file_path = $file_path;
        if (!$extension && $file_path) {
            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        }
        $this->set_extension($extension);
        $this->set_crop(0, 0, 0, 0);
        $this->set_resize(0, 0);
        $this->set_watermark(null);
        $this->set_scale(null);
    }

    /**
     * HTTP-путь до папки со сгенерированными картинками
     * @return string
     */
    public static function generated_images_http_path() {
        $nc_core = nc_core::get_object();
        return $nc_core->HTTP_FILES_PATH . 'generated';
    }

    /**
     * @param integer|string $entity
     * @return static
     */
    public function set_entity($entity) {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @param integer|string $field
     * @return static
     */
    public function set_field($field) {
        $this->field = $field;
        return $this;
    }

    /**
     * @param integer $object
     * @return static
     */
    public function set_object($object) {
        $this->object = $object;
        return $this;
    }

    /**
     * @param null|integer $file_id
     * @return static
     */
    public function set_file_id($file_id) {
        $this->file_id = $file_id;
        return $this;
    }

    /**
     * @param string $extension
     * @return static
     */
    public function set_extension($extension) {
        $this->extension = strtolower($extension);
        return $this;
    }

    /**
     * @param integer $width
     * @param integer $height
     * @param integer $x
     * @param integer $y
     * @return static
     */
    public function set_crop($width, $height, $x, $y) {
        $this->crop['width'] = $width;
        $this->crop['height'] = $height;
        $this->crop['x'] = $x;
        $this->crop['y'] = $y;
        return $this;
    }

    /**
     * @param integer $width
     * @param integer $height
     * @param int $mode
     * @return static
     */
    public function set_resize($width, $height, $mode = nc_ImageTransform::RESIZE_TO_BEST_FIT_WITH_CROP) {
        $this->resize['width'] = $width;
        $this->resize['height'] = $height;
        $this->resize['mode'] = $mode;
        return $this;
    }

    /**
     * @param string $http_path
     * @param integer $mode
     * @return static
     */
    public function set_watermark($http_path, $mode = nc_ImageTransform::WATERMARK_POSITION_CENTER) {
        $this->watermark = array(
            'http_path' => $http_path,
            'mode' => $mode
        );
        return $this;
    }

    /**
     * @param float $scale
     * @return static
     */
    public function set_scale($scale) {
        $this->scale = $scale;
        return $this;
    }

    /**
     * Фикс автопреобразований float-значений
     * @param float $value
     * @return string
     */
    protected static function standardize_float_value($value) {
        return str_replace(',', '.', (string)$value);
    }

    /**
     * Рассчитываем хэш от параметров обработки изображений
     * (также используется для имени файла)
     * @return string
     */
    public function calculate_parameters_hash() {
        return md5(implode('-', array(
            $this->resize['width'],
            $this->resize['height'],
            $this->resize['mode'],
            $this->crop['x'],
            $this->crop['y'],
            $this->crop['width'],
            $this->crop['height'],
            $this->watermark['http_path'],
            $this->watermark['mode'],
            self::standardize_float_value($this->scale),
        )));
    }

    /**
     * Расcчитываем хэш от хэша по параметрам и уникального ключа системы
     * (защита от злонамереной генерации тонны изображений)
     * @param null|string $parameters_hash
     * @return string
     */
    public function calculate_result_hash($parameters_hash = null) {
        $nc_core = nc_core::get_object();
        $parameters_hash = $parameters_hash ?: $this->calculate_parameters_hash();
        $secret_key = $nc_core->get_copy_id();
        return md5(implode(':', array(
            $parameters_hash,
            $secret_key
        )));
    }

    /**
     * HTTP-путь к сгенерированной картинке
     * @param null|string $parameters_hash
     * @return string
     */
    protected function generate_image_http_path($parameters_hash = null) {
        $parameters_hash = $parameters_hash ?: $this->calculate_parameters_hash();
        $result = array(nc_image_generator::generated_images_http_path());
        $result[] = $this->entity;
        $result[] = $this->field;
        $result[] = $this->generate_resize_path_part();
        $result[] = $this->object;
        if ($this->file_id) {
            $result[] = $this->file_id;
        }
        $result[] = $parameters_hash . '.' . $this->extension;

        return implode('/', $result);
    }

    /**
     * Генерация строки с параметрами ресайза
     * @return string
     */
    protected function generate_resize_path_part() {
        $width = $this->resize['width'];
        $height = $this->resize['height'];
        return $width . 'x' . $height;
    }

    /**
     * Есть ли параметры для ресайза изображения?
     * @return bool
     */
    protected function has_resize_parameters() {
        $width = (int)$this->resize['width'];
        $height = (int)$this->resize['height'];
        return $width || $height;
    }

    /**
     * Есть ли параметры для кропа изображения?
     * @return bool
     */
    protected function has_crop_parameters() {
        $width = (int)$this->crop['width'];
        $height = (int)$this->crop['height'];
        return $width || $height;
    }

    /**
     * Есть ли параметры для масштабирования изображения?
     * @return float
     */
    protected function has_scale_parameters() {
        $scale = (float)$this->scale;
        return $scale;
    }

    /**
     * Возвращает MIME-тип файла
     * @return string|null
     */
    protected function get_mime_type() {
        $nc_core = nc_core::get_object();
        $full_path = $nc_core->DOCUMENT_ROOT . $this->file_path;
        if (!file_exists($full_path)) {
            return null;
        }
        return nc_file_mime_type($full_path);
    }

    /**
     * Определяет, имеет ли файл указанный MIME-тип
     * @param string $required_type полный MIME-тип или маска вида "image/*"
     * @return bool
     */
    protected function has_mime_type($required_type) {
        return fnmatch($required_type, $this->get_mime_type());
    }

}