<?php

abstract class nc_image_path extends nc_image_base {
    /**
     * URL картинки от DOCUMENT_ROOT (может отличаться для «защищённой файловой системы»)
     * @var string
     */
    protected $file_url = null;

    /**
     * Является ли файл изображением?
     * @var boolean
     */
    protected $is_image = false;

    /**
     * Нужно ли выводить путь к картинке с тегом <img>?
     * @var bool
     */
    protected $as_img = false;

    /**
     * Нужно ли встраивать SVG-картинку в страницу?
     * @var bool
     */
    protected $as_icon = false;

    /**
     * Атрибуты тега <img>
     * @var array
     */
    protected $tag_attributes = array();

    /**
     * Разрешенные расширения файлов, которые обрабатываются
     * @var array
     */
    protected $allowed_image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'svg', 'webp');

    /**
     * @var array  Если изображение генерируется через тег "img" (as_img),
     * то используется атрибут "srcset" с данным набором масштабов
     */
    protected $img_scales = array(1.5, 2, 3, 4);

    /**
     * nc_image_path constructor.
     *
     * @param string|null $file_path
     * @param string|null $extension
     */
    public function __construct($file_path = null, $extension = null) {
        parent::__construct($file_path, $extension);
        $this->file_url = $file_path;
        if ($this->extension) {
            $this->is_image = in_array($this->extension, $this->allowed_image_extensions, true);
        } else if (nc_is_edit_mode()) {
            $this->is_image = true;
        }
    }

    /**
     * @param string $file_url
     * @return static
     */
    public function set_file_url($file_url) {
        $this->file_url = $file_url;
        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function set_as_img(array $attributes) {
        $this->as_img = true;
        $this->tag_attributes = $attributes;
        return $this;
    }

    /**
     * Вставляет изображение-значок: SVG-файлы возвращаются в виде тэга SVG,
     * остальные картинки — как <img>. К тэгу добавляется атрибут class="tpl-icon".
     * Если в SVG-картинки отсутствуют атрибуты fill, то также добавляется
     * class="tpl-state-default-color".
     *
     * @param array $attributes
     * @return $this
     */
    public function set_as_icon(array $attributes) {
        $this->as_icon = true;
        $this->tag_attributes = $attributes;
        return $this;
    }

    // Методы, не модифицирующие исходный объект

    /**
     * @param int $width
     * @param int $height
     * @param int $x
     * @param int $y
     * @return self
     */
    public function crop($width = 0, $height = 0, $x = 0, $y = 0) {
        $object = clone $this;
        return $object->set_crop($width, $height, $x, $y);
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $type
     * @return self
     */
    public function resize($width = 0, $height = 0, $type = nc_ImageTransform::RESIZE_TO_BEST_FIT_WITH_CROP) {
        $object = clone $this;
        return $object->set_resize($width, $height, $type);
    }

    /**
     * @param string $http_path
     * @param int $mode
     * @return self
     */
    public function watermark($http_path, $mode = nc_ImageTransform::WATERMARK_POSITION_CENTER) {
        $object = clone $this;
        return $object->set_watermark($http_path, $mode);
    }

    /**
     * @param $scale
     * @return static
     */
    public function scale($scale) {
        $object = clone $this;
        return $object->set_scale($scale);
    }

    /**
     * После приведения объекта к строке будет выведен тэг <img> для вставки изображения
     * @param array $attributes дополнительные атрибуты тега <img>
     * @return static
     */
    public function as_img(array $attributes = array()) {
        $object = clone $this;
        return $object->set_as_img($attributes);
    }

    /**
     * После приведения объекта к строке SVG-файл будет вставлен в тело страницы, а для прочих
     * изображений будет выведен тэг <img> для вставки изображения
     * @param array $attributes дополнительные атрибуты для тегов <svg> и <img>
     * @return static
     */
    public function as_icon(array $attributes = array()) {
        $object = clone $this;
        return $object->set_as_icon($attributes);
    }

    /**
     * Есть ли изменения, которые нужно обработать?
     * @return bool
     */
    protected function need_to_process_image() {
        return
            $this->crop['width'] ||
            $this->crop['height'] ||
            $this->crop['x'] ||
            $this->crop['y'] ||
            $this->resize['height'] ||
            $this->resize['height'] ||
            $this->watermark['http_path'] ||
            $this->scale;
    }

    /**
     * Есть ли оригинальное изображение и обязательные параметры, без которых невозможна его обработка?
     * @return bool
     */
    protected function can_process_image() {
        if (empty($this->file_path)) {
            return false;
        }
        if (empty($this->extension)) {
            return false;
        }
        if (
            !$this->is_image()
            || $this->extension === 'svg'
            || ($this->extension === 'webp' && !function_exists('imagewebp'))
        ) {
            return false;
        }
        if (empty($this->entity)) {
            return false;
        }
        // Если число, то сущность - компонент
        if (is_numeric($this->entity)) {
            // Для компонента должны быть указаны поле и объект
            return !empty($this->field) && !empty($this->object);
        }
        return true;
    }

    /**
     * Проверяет, есть ли изображение
     * @return boolean
     */
    public function has_file() {
        return !empty($this->file_path);
    }

    /**
     * Проверяет, является ли указанный файл изображением
     * @return boolean
     */
    protected function is_image() {
        return $this->is_image;
    }

    /**
     * Получить итоговый путь к картинке
     * @return string
     */
    public function get_path() {
        if ($this->need_to_process_image() && $this->can_process_image()) {
            return $this->generate_image_url();
        }

        return $this->file_url ?: $this->file_path ?: '';
    }

    /**
     * Возвращает тэг <img> для изображения
     * @return string
     */
    public function get_img_tag() {
        $attributes = $this->tag_attributes;
        $attributes['src'] = $this->get_path();

        if ($this->is_image() && !array_key_exists('srcset', $this->tag_attributes) && $this->has_resize_parameters()) {
            $attributes['srcset'] = $this->generate_img_srcsets();
        }

        $attributes = nc_make_attribute_string_from_array($attributes, true);

        return "<img {$attributes['result']}>";
    }

    /**
     * Преобразование итоговых параметров изображения в путь к картинке,
     * либо строку для добавления картинки в HTML
     * @return string
     */
    public function to_string() {
        if ($this->as_icon) {
            if (!isset($this->tag_attributes['class'])) {
                $this->tag_attributes['class'] = 'tpl-icon';
            } else if (!preg_match('/\btpl-icon\b/', $this->tag_attributes['class'])) {
                $this->tag_attributes['class'] .= ' tpl-icon';
            }

            if ($this->extension === 'svg') {
                return nc_get_svg_as_icon(nc_core::get_object()->DOCUMENT_ROOT . $this->file_path, $this->tag_attributes);
            }
        }

        if ($this->as_img || $this->as_icon) {
            if ($this->is_image() && nc_is_edit_mode() && $this->can_user_edit_image()) {
                return $this->get_editable_image_form($this->get_path());
            }

            return $this->get_img_tag();
        }

        return $this->get_path();
    }

    /**
     * Генерирует набор URL изображений для атрибута "srcset" тега "img"
     * @return string
     */
    protected function generate_img_srcsets() {
        $img_srcs = array();
        foreach ($this->img_scales as $img_scale) {
            $src = $this->scale($img_scale)->get_path();
            $img_scale = str_replace(',', '.', (string)$img_scale);
            $src .= " {$img_scale}x";
            $img_srcs[] = $src;
        }
        return implode(', ', $img_srcs);
    }

    /**
     * Генерация итогового URL для картинки (со всеми параметрами обработки)
     * @return string
     */
    protected function generate_image_url() {
        $hash = $this->calculate_parameters_hash();
        $image_url_base = $this->generate_image_http_path($hash);
        $image_url_query = $this->generate_image_url_query($hash, $image_url_base);
        return $image_url_base . ($image_url_query ? "?{$image_url_query}" : null);
    }

    /**
     * Генерация query-части URL картинки
     * (содержит дополнительные параметры генерации, помимо основных в PATH)
     * @param string $parameters_hash
     * @param string $image_url_base
     * @return string
     */
    protected function generate_image_url_query($parameters_hash, $image_url_base) {
        $query = array();

        // хэш для обновления изображения после изменений в админке (путь при изменении изображения не меняется)
        $nc_core = nc_core::get_object();
        if ($nc_core->admin_mode) {
            $file_path = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $image_url_base;
            $query['_nc_editable_image'] = file_exists($file_path) ? filemtime($file_path) : mt_rand(0, PHP_INT_MAX);
        }

        $query['crop'] = implode(':', array(
            $this->crop['width'],
            $this->crop['height'],
            $this->crop['x'],
            $this->crop['y'],
        ));
        $query['hash'] = $this->calculate_result_hash($parameters_hash);
        $query['resize_mode'] = $this->resize['mode'];
        $query['wm_p'] = $this->watermark['http_path'];
        $query['wm_m'] = $this->watermark['mode'];
        $query['scale'] = $this->scale;
        return http_build_query($query, null, '&');
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->to_string();
    }

    /**
     * Проверяет, есть ли у текущего пользователя права на редактирование изображения
     * @return bool
     */
    abstract protected function can_user_edit_image();

    /**
     * Возвращает <img> с добавлением возможности in-place редактирования
     * @return string
     */
    abstract protected function get_editable_image_form($file_path);
}