<?php

/**
 * Генератор изображений по заданным параметрам
 * Class nc_image_generator
 */
class nc_image_generator extends nc_image_base {
    /**
     * Создает экземпляр класса генератора изображений
     * @param array $parameters
     * @return self
     */
    public static function from_parameters(array $parameters) {
        // Соберем объект
        $object = new self();
        $object->set_entity($parameters['entity']);
        $object->set_field($parameters['field']);
        $object->set_object($parameters['object']);
        if ($parameters['file_id']) {
            $object->set_file_id($parameters['file_id']);
        }
        $object->set_extension($parameters['extension']);
        $resize = $parameters['resize'];
        $object->set_resize($resize[0], $resize[1], $resize[2]);
        $crop = $parameters['crop'];
        $object->set_crop($crop[0], $crop[1], $crop[2], $crop[3]);
        if (!empty($parameters['watermark_http_path'])) {
            $object->set_watermark($parameters['watermark_http_path'], $parameters['watermark_mode']);
        }
        $object->set_scale($parameters['scale']);
        $object->init_file_path_value();
        return $object;
    }

    /**
     * Парсинг параметров для генерации изображения по заданному URL файла
     * @param string $url_path       - Путь (без GET-параметров)
     * @param array $url_parameters  - Массив параметров
     * @return array
     */
    public static function get_parameters_from_url($url_path, array $url_parameters = array()) {
        $url_path = str_replace(self::generated_images_http_path() . '/', '', $url_path);
        $path_parameters = explode('/', $url_path);
        // Соберем данные
        $file_id = null;
        if (count($path_parameters) == 5) {
            $hash_and_ext_info = $path_parameters[4];
        } else {
            $file_id = $path_parameters[4];
            $hash_and_ext_info = $path_parameters[5];
        }
        $info = pathinfo($hash_and_ext_info);
        $resize = explode('x', $path_parameters[2]);
        $resize[] = nc_array_value($url_parameters, 'resize_mode', nc_ImageTransform::RESIZE_TO_BEST_FIT_WITH_CROP);
        $scale = isset($url_parameters['scale']) ? $url_parameters['scale'] : null;
        if ($scale) {
            $scale = self::standardize_float_value($scale);
            $scale = floatval($scale);
        }
        return array(
            'entity' => $path_parameters[0],
            'field' => $path_parameters[1],
            'resize' => $resize,
            'object' => $path_parameters[3],
            'parameters_hash' => $info['filename'],
            'file_id' => $file_id,
            'extension' => $info['extension'],
            'crop' => explode(':', $url_parameters['crop']),
            'result_hash' => $url_parameters['hash'],
            'watermark_http_path' => isset($url_parameters['wm_p']) ? $url_parameters['wm_p'] : null,
            'watermark_mode' => isset($url_parameters['wm_m']) ? $url_parameters['wm_m'] : null,
            'scale' => $scale,
        );
    }

    /**
     * Проверка хэша (защита от нежелательных генераций)
     * @param string $result_hash
     * @return boolean
     */
    public function validate_hash($result_hash) {
        return $result_hash == $this->calculate_result_hash();
    }

    /**
     * Генерация изображений по заданным параметрам
     * @return null|string
     */
    public function generate() {
        $nc_core = nc_core::get_object();
        // Проверим наличие оригинальной картинки
        if (!$this->file_path) {
            return null;
        }
        $original_image_absolute_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $this->file_path;
        if (!file_exists($original_image_absolute_path) || is_dir($original_image_absolute_path)) {
            return null;
        }
        // Сгенерируем папку и скопируем файл
        $image_http_path = $this->generate_image_http_path();
        $image_absolute_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $image_http_path;
        $image_dir_path = dirname($image_absolute_path);
        if (!file_exists($image_dir_path)) {
            mkdir($image_dir_path, $nc_core->DIRCHMOD, true);
        }
        if (!copy($original_image_absolute_path, $image_absolute_path)) {
            return null;
        }
        // Обработаем файл
        $this->prepare($image_absolute_path);
        return $image_absolute_path;
    }

    /**
     * Обработка скопированного оригинального изображения
     * @param string $file_path
     */
    protected function prepare($file_path) {
        $scale = $this->resize($file_path);
        $this->crop($file_path, $scale);
        $this->watermark($file_path, $scale);
    }

    /**
     * Обрезка изображения по заданным параметрам
     * @param string $file_path
     * @param float $scale
     */
    protected function crop($file_path, $scale) {
        if (!$this->has_crop_parameters()) {
            return;
        }

        $x = floor((int)$this->crop['x'] * $scale);
        $y = floor((int)$this->crop['y'] * $scale);
        $width = floor((int)$this->crop['width'] * $scale);
        $height = floor((int)$this->crop['height'] * $scale);

        nc_ImageTransform::imgCrop($file_path, $file_path, $x, $y, $x + $width, $y + $height);
    }

    /**
     * Изменение размера изображения по заданным параметрам
     * @param string $file_path
     * @return float|int применённый коэффициент масштабирования
     */
    protected function resize($file_path) {
        if (!$this->has_resize_parameters()) {
            return 1;
        }

        list($mode, $scale) = $this->get_actual_mode_and_scale($file_path);
        $width = floor($this->resize['width'] * $scale);
        $height = floor($this->resize['height'] * $scale);

        nc_ImageTransform::imgResize($file_path, $file_path, $width, $height, $mode);
        return $scale;
    }

    /**
     * Возвращает режим и коэффициент масштабирования, которые будут применены к файлу,
     * исходя из запрошенного scale и фактических размеров изображения
     * @param string $file_path путь к файлу
     * @return array массив с элементами: режим масштабирования, коэффициент масштабирования
     */
    protected function get_actual_mode_and_scale($file_path) {
        $scale = $this->scale ?: 1;
        $mode = $this->resize['mode'];

        if ($scale > 1) {
            $requested_width = $this->resize['width'];
            $requested_height = $this->resize['height'];

            list($image_width, $image_height) = $this->get_image_size($file_path);
            // Если картинка по любому измерению меньше запрошенных размеров, определяем фактический коэффициент масштабирования
            if ($image_width <= $requested_width || $image_height <= $requested_height) {
                // Если изображение менее запрошенных размеров, nc_ImageTransform::imgResize() ничего не делает.
                // Для scale > 1 нам надо смасштабировать (увеличить) изображение.
                if ($image_width <= $requested_width && $image_height <= $requested_height) {
                    $mode = nc_ImageTransform::RESIZE_TO_BEST_FIT;
                }

                if ($mode == nc_ImageTransform::RESIZE_TO_BEST_FIT || nc_ImageTransform::RESIZE_TO_BEST_FIT_WITH_CROP) {
                    $width_ratio = min(1, $image_width / $requested_width);
                    $height_ratio = min(1, $image_height / $requested_height);
                    $scale *= max($width_ratio, $height_ratio);
                }
            }

            // Масштабируем всегда (даже при достижении фактических размеров изображения),
            // чтобы картинка была одинаковой при всех значениях scale
            $mode |= nc_ImageTransform::ALWAYS_RESIZE;
        }

        return array($mode, $scale);
    }

    /**
     * Наложение водяного знака на изображение по заданным параметрам
     * @param string $file_path
     * @param int|float $scale
     */
    protected function watermark($file_path, $scale) {
        $nc_core = nc_core::get_object();
        $watermark = $this->watermark['http_path'];
        $mode = $this->watermark['mode'];
        if ($watermark) {
            $watermark = '/' . ltrim($watermark, '/');
            $watermark_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $watermark;
            $watermark_exists = file_exists($watermark_path) && !is_dir($watermark_path);
            if ($watermark_exists) {
                nc_ImageTransform::putWatermark_file($file_path, $watermark, $mode, null, $scale);
            }
        }
    }

    /**
     * Получение размера изображения
     * @param $file_path
     * @return array|null
     */
    protected function get_image_size($file_path) {
        $img_size = @getimagesize($file_path);
        if ($img_size === false) {
            return null;
        }
        return array($img_size[0], $img_size[1]);
    }

    /**
     * Устанавливает путь к оригинальной картинке ($this->file_path) по параметрам, заданным
     * у объекта (сущность, поле, объект, файл)
     */
    protected function init_file_path_value() {
        $nc_core = nc_core::get_object();

        $entity = $this->entity;
        $object_id = (int)$this->object;
        $field = $this->field;
        if (!$entity || !$object_id || !$field) {
            return;
        }

        $file_path = null;

        if (is_numeric($entity)) { // Компонент?
            $component_id = (int)$entity;
            $field_id = (int)$field;
            if (!$nc_core->get_component($component_id)->has_field($field_id)) {
                return;
            }
            $file_id = (int)$this->file_id;
            if ($file_id) { // Из поля "Множественная загрузка файлов"
                $file_path = $nc_core->db->get_var(
                    "SELECT `Path`
                     FROM `Multifield`
                     WHERE `Message_ID`='{$object_id}' AND `Field_ID`='{$field_id}' AND `ID`='{$file_id}'"
                );
            } else { // Из поля "Файл"
                $file_path = nc_file_path($component_id, $object_id, $field);
            }
        } else if ($entity === 'class_settings') { // Настройки инфоблока?
            $custom_settings_fields_data = $nc_core->sub_class->get_by_id($object_id, 'Sub_Class_Settings');
            if (isset($custom_settings_fields_data[$field]['path'])) {
                $file_path = $custom_settings_fields_data[$field]['path'];
            }
        } else if ($entity === 'template_settings') { // Настройки макета дизайна?
            $custom_settings_fields_data = $nc_core->subdivision->get_template_settings($object_id);
            if (isset($custom_settings_fields_data[$field]['path'])) {
                $file_path = $custom_settings_fields_data[$field]['path'];
            }
        } else { // Системные таблицы?
            $system_table_name = ucfirst($entity);
            if ($nc_core->get_system_table_id_by_name($system_table_name) && $nc_core->get_component($system_table_name)->has_field($field)) {
                $file_path = nc_file_path($system_table_name, $object_id, $field);
            }
        }
        $this->file_path = $file_path;
    }

    /**
     * Гибкое удаление сгенерированных изображений
     * @param null|integer|string $entity  - Сущность (Компонент/Системная таблица)
     *                                       [например: 169, 'subdivision', 'class_settings']
     * @param null|integer|string $field   - Поле в сущности
     *                                       [например: 582 или 'Image' - для компонентов и системных таблиц
     *                                                  'Image' - для полей пользовательских настроек]
     * @param null|integer $object  - Объект в сущности
     * @param null|integer $file_id    - ID файла в мультифайловом поле (если поле мультифайл)
     */
    public static function remove_generated_images($entity = null, $field = null, $object = null, $file_id = null) {
        $nc_core = nc_core::get_object();
        $generated_images_http_path = nc_image_base::generated_images_http_path();
        $generated_images_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $generated_images_http_path;
        $current_dir = $generated_images_path;
        // Удаление всех сгенерированных изображений
        if (!$entity) {
            if (file_exists($current_dir) && is_dir($current_dir)) {
                nc_delete_dir($current_dir);
            }
            return;
        }
        // Удаление сгенерированных изображений сущности
        $current_dir .= '/' . $entity;
        if (!$field) {
            if (file_exists($current_dir) && is_dir($current_dir)) {
                nc_delete_dir($current_dir);
            }
            return;
        }
        // Удаление в сущности изображение определенного поля
        $field = self::get_field_dir_name_from_entity_and_field($entity, $field);
        $current_dir .= '/' . $field;
        if (!$object) {
            if (file_exists($current_dir) && is_dir($current_dir)) {
                nc_delete_dir($current_dir);
            }
            return;
        }
        // Удаление в сущности изображений объекта из определенного поля
        $resize_dirs = glob($current_dir . '/*', GLOB_ONLYDIR);
        if ($resize_dirs) {
            foreach ($resize_dirs as $current_dir) {
                $current_dir .= '/' . $object;
                // Если "Множественная загрузка файлов", то удаляем картинки только по ID картинки
                if ($file_id) {
                    $current_dir .= '/' . $file_id;
                }
                if (file_exists($current_dir) && is_dir($current_dir)) {
                    nc_delete_dir($current_dir);
                }
            }
        }
    }

    /**
     * Получение  поля по заданному entity и field.
     * Для компонента или системной таблицы - ID поля.
     * Для прочих ситуаций - название поля.
     * @param integer|string $entity
     * @param integer|string $field
     * @return array|null|string
     */
    protected static function get_field_dir_name_from_entity_and_field($entity, $field) {
        $system_tables = self::get_system_table_names();
        array_walk($system_tables, function (&$val) {
            $val = strtolower($val);
        });
        unset($val);
        $entity_is_id = ctype_digit($entity);
        $is_component_or_system_table = $entity_is_id || in_array($entity, $system_tables);
        if (!$is_component_or_system_table) {
            return $field;
        }
        $field_is_id = ctype_digit($field);
        if ($field_is_id) {
            return $field;
        }
        $component_or_system_table_keyword = $entity_is_id ? $entity : ucfirst($entity);
        $component_or_system_table = new nc_component($component_or_system_table_keyword);
        $field = $component_or_system_table->get_field($field, 'id');
        return $field;
    }

    /**
     * Возвращает латинские имена системных таблиц
     * @return array
     */
    protected static function get_system_table_names() {
        $nc_core = nc_core::get_object();
        $result = array(
            // Системная таблица "Сайт"
            $nc_core->get_system_table_name_by_id(1),
            // Системная таблица "Раздел"
            $nc_core->get_system_table_name_by_id(2),
            // Системная таблица "Пользователь"
            $nc_core->get_system_table_name_by_id(3),
            // Системная таблица "Макет дизайна"
            $nc_core->get_system_table_name_by_id(4),
        );
        return $result;
    }

    /**
     * Получение ключевого слова сущности по ID класса или ключевого слова или ID системной таблицы
     * @param null|integer $class_id [напр: 10]
     * @param null|string|integer $sys_table [напр: 1 или 3 или Catalogue]
     * @return null|string
     */
    public static function get_entity_by_class_id_or_system_table($class_id = null, $sys_table = null) {
        if ($class_id && is_numeric($class_id)) {
            return $class_id;
        }
        if ($sys_table) {
            if (is_numeric($sys_table)) {
                $nc_core = nc_core::get_object();
                $sys_table = $nc_core->get_system_table_name_by_id($sys_table);
            }
            return strtolower($sys_table);
        }
        return null;
    }
}