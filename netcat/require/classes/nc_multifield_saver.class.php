<?php

/**
 * (Не является частью публичного API.)
 *
 * Работа с изменениями значений полей «множественная загрузка» при создании и
 * изменении записей (в формах) в message_fields.php, add.php, message_php.
 *
 * Берёт данные из $_POST, $_FILES и приводит их к удобному для дальнейшей обработки
 * виду, выполняет некоторые проверки и действия с этими данными.
 *
 * Для полей приходят следующие данные:
 *  — $_FILES[f_F_file][]          загружаемые файлы ("F" = имя поля)
 *  — settings_F[...]              настройки поля
 *  — multifile_js[F] = 1          когда на клиенте сработал стандартный JS для файловых полей
 *
 * Если подключён стандартный скрипт работы с файловыми полями (jquery.upload.js),
 * для каждого файла должны быть переданы все нижеперечисленные поля, независимо от того,
 * файл новый или был закачан ранее:
 *  — multifile_id[F][]            ID файла в Multifield (для новых файлов = '')
 *  — multifile_upload_index[F][]  порядковый номер файла в $_FILES[f_F_file][] (для старых файлов = -1)
 *  — multifile_delete[F][]        1, если файл нужно удалить (как для новых, так и для старых файлов)
 *  — multifile_name[F][]          поле 'Name' (описание/пользовательское название) для файла (если включено в настройках)
 *
 * Если скрипт jquery.upload.js не подключён, параметры multifile_* отсутствуют
 * для новых файлов, и соответствующие возможности для этих файлов недоступны.
 *
 */
class nc_multifield_saver {

    /** @var  nc_multifield_saver[] */
    static protected $instances = array();

    /** @var nc_multifield  */
    protected $multifield;
    /** @var  nc_multifield_settings   передаются POST’ом, в теории могут отличаться от $multifield->settings */
    protected $multifield_settings;

    protected $component_id;
    protected $object_id;

    /** @var array  [size => 0, format => [['image', 'png'], ['image', 'gif']] */
    protected $field_format;

    protected $raw_files_data;
    protected $raw_post_data;

    protected $all_files;

    /**
     * Получение экземпляра класса.
     * (Данные сейчас берутся напрямик из $_POST/$_FILES, но могли бы передаваться в этот метод при необходимости)
     * @param int|string $component_id
     * @param int|null $object_id
     * @param nc_multifield $multifield
     * @return nc_multifield_saver
     */
    static public function with_post_data($component_id, $object_id, nc_multifield $multifield) {
        $key = (int)$object_id . ':' . $multifield->id;
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($component_id, $object_id, $multifield);
        }
        return self::$instances[$key];
    }


    /**
     * Сохранение данных (в add.php, message.php).
     * Изменяет набор записей в указанном поле на новый.
     * @param int|string $component_id
     * @param int $object_id
     * @param nc_multifield $multifield
     * @param bool $is_new_object
     */
    static public function save_from_post_data($component_id, $object_id, nc_multifield $multifield, $is_new_object = false) {
        $old_object_id = ($is_new_object ? null : $object_id);
        $saver = nc_multifield_saver::with_post_data($component_id, $old_object_id, $multifield);

        if ($is_new_object) {
            $saver->set_object_id($object_id);
        }

        $saver->save_changes();

        $result = $saver->get_all_files()->where_all(array(
                    array('id', 0, '>'),
                    array('is_deleted', false),
                    array('has_error', false)
                ))->to_array();

        $multifield->set_data($result);
    }

    /**
     * @param $component_id
     * @param $object_id
     * @param nc_multifield $multifield
     */
    protected function __construct($component_id, $object_id, nc_multifield $multifield) {
        $this->component_id = $component_id;
        $this->object_id = $object_id;
        $this->multifield = $multifield;
        $this->field_format = nc_field_parse_format($multifield->format, NC_FIELDTYPE_MULTIFILE);

        $nc_core = nc_core::get_object();

        $this->raw_files_data = $nc_core->input->fetch_files("f_{$multifield->name}_file");
        $this->raw_post_data = $nc_core->input->fetch_post();

        $this->multifield_settings = nc_multifield_settings::from_array(
            $multifield,
            nc_array_value($this->raw_post_data, 'settings_' . $this->multifield->name, array())
        );
    }

    /**
     * Установка object_id (используется после создания новой записи)
     * @param $object_id
     * @return $this
     */
    public function set_object_id($object_id) {
        $this->object_id = $object_id;
        $this->get_all_files()->each('set', 'object_id', $object_id);
        return $this;
    }

    /**
     * Проверяет, были ли переданы какие-либо данные для поля
     * @return bool
     */
    public function has_post_data() {
        return isset($this->raw_post_data['settings_' . $this->multifield->name]);
    }

    /**
     * Возвращает свойство поля в компоненте
     * @param $property
     * @return mixed
     */
    protected function get_field_property($property) {
        return nc_core::get_object()
                ->get_component($this->component_id)
                ->get_field($this->multifield->name, $property);
    }

    /**
     * Возвращает параметр из settings_FIELDNAME
     * @param $key
     * @return mixed (false, если нет значения)
     */
    protected function get_multifield_setting($key) {
        return $this->multifield_settings->{$key};
    }

    /**
     * Проверяет целостность настроек
     * @return bool
     */
    public function check_settings_hash() {
        return $this->multifield_settings->get_setting_hash() == $this->raw_post_data["settings_{$this->multifield->name}_hash"];
    }

    /**
     * Проверяет, не возникла ли ошибка из-за слишком больших файлов
     * @return bool
     */
    public function has_file_upload_size_error() {
        return $this->raw_files_data && (
            in_array(UPLOAD_ERR_INI_SIZE, $this->raw_files_data['error']) ||
            in_array(UPLOAD_ERR_FORM_SIZE, $this->raw_files_data['error'])
        );
    }

    /**
     * Возвращает значение из $_POST[multifile_TYPE][FIELD_NAME][INDEX]
     * (или $default_value, когда этого значения нет)
     * @param string $property_type
     * @param null|int $index    если null, возвращает всё
     * @param mixed $default_value
     * @return mixed
     */
    protected function get_post_value($property_type, $index, $default_value = '') {
        if (!isset($this->raw_post_data['multifile_' . $property_type][$this->multifield->name])) {
            return $default_value;
        }

        $result = $this->raw_post_data['multifile_' . $property_type][$this->multifield->name];

        if ($index === null) {
            return $result;
        }

        return isset($result[$index]) ? $result[$index] : $default_value;
    }

    /**
     * Возвращает информацию о файлах
     * @return nc_record_collection  коллекция с nc_multifield_file
     */
    public function get_all_files() {
        if (!$this->all_files) {
            $all_files = $this->get_all_files_with_parameters();

            // Если стандартный JS-скрипт не был использован на клиенте,
            // у закаченных файлов нет дополнительных полей и они были пропущены
            // в $this->get_all_files_with_parameters()
            if (!$this->get_post_value('js', null, false)) {
                $this->add_files_uploaded_without_js($all_files);
            }

            $this->all_files = $all_files;
        }

        return $this->all_files;
    }

    /**
     * Возвращает значение для $this->all_files с дополнительными сведениями
     * (это ранее закаченные файлы, а также новые файлы при использовании
     * скрипта работы с файловыми полями jquery.upload.js).
     * @return nc_record_collection
     */
    protected function get_all_files_with_parameters() {
        $all_files = new nc_record_collection(null, null, 'nc_multifield_file');

        $max_files = $this->get_multifield_setting('max');
        $priority = 0;

        foreach ($this->get_post_value('id', null, array()) as $index => $id) {
            $is_deleted = (bool)$this->get_post_value('delete', $index, false);
            if ($id) {
                $file = $this->get_old_file($id);
           }
            else if (!$is_deleted) { // новые файлы, отмеченные как удалённые, пропускаются
                $file_index = $this->get_post_value('upload_index', $index, -1);
                $file = $this->get_uploaded_file($file_index);
            }
            else {
                $file = false;
            }

            // если возникла ошибка (нет записи для старого файла, ошибка при загрузке
            // нового файла), то $file === false
            if ($file) {
                // общие свойства для старых и новых файлов, переданные в форме
                if ($this->get_multifield_setting('use_name')) {
                    $file->set('name', $this->get_post_value('name', $index));
                }
                $file->set('is_deleted', $is_deleted);

                if (!$is_deleted && !$file->has_error()) {
                    $file->set('priority', $priority++);
                }

                // файлы сверх максимального разрешённого числа файлов в поле
                // будут втихую проигнорированы (такая ситуация при нормальном
                // функционировании при использовании стандартного JS-скрипта
                // не встречается)
                if (!$max_files || $priority <= $max_files) {
                    $all_files->add($file);
                }
            }
        } // of "foreach multifile_id[F][]"

        return $all_files;
    }

    /**
     * Добавляет в коллекцию файлы, закаченные без стандартного JS-скрипта
     * @param nc_record_collection $all_files
     * @return nc_record_collection
     */
    protected function add_files_uploaded_without_js(nc_record_collection $all_files) {
        $num_files = $this->get_uploaded_files_count();

        if ($num_files) {
            $priority = $all_files->where('is_deleted', false)->count();

            for ($i = 0; $i < $num_files; $i++) {
                $file = $this->get_uploaded_file($i);
                if ($file) {
                    $file->set('priority', $priority++);
                    $all_files->add($file);
                }
            }
        }

        return $all_files;
    }

    /**
     * Возвращает ранее закаченный в поле файл с указанным идентификатором
     * @param $id
     * @return bool|nc_multifield_file
     */
    protected function get_old_file($id) {
        $existing_file = $this->multifield->get_record_data_by_id($id);
        if (!($existing_file instanceof nc_multifield_file)) {
            // нет такой записи?! пропускаем эту позицию
            trigger_error("Cannot find old file (ID = '$id')", E_USER_WARNING);
            return false;
        }

        return $existing_file;
    }

    /**
     * Возвращает число закачанных в $_FILES файлов
     * @return int
     */
    protected function get_uploaded_files_count() {
        if (isset($this->raw_files_data['error'])) {
            return count($this->raw_files_data['error']);        
        }
        else {
            return 0;        
        }
    }
    /**
     * Устанавливает значения для нового файла
     * @param int $file_index индекс в $_FILES
     * @return bool|nc_multifield_file
     */
    protected function get_uploaded_file($file_index) {
        // неправильный индекс в $_FILES — пропускаем
        if (!isset($this->raw_files_data['error'][$file_index])) {
            trigger_error("Incorrect upload_index for $this->multifield->name ('$file_index')", E_USER_WARNING);
            return false;
        }

        $error = $this->raw_files_data['error'][$file_index];

        // файла нет, или возникла ошибка — пропускаем
        if ($file_index < 0 || $error != UPLOAD_ERR_OK) {
            if ($error != UPLOAD_ERR_NO_FILE && $error != UPLOAD_ERR_OK) {
                trigger_error("File upload error: $error", E_USER_WARNING);
            }
            return false;
        }

        $file = new nc_multifield_file(array(
            'field_id' => $this->multifield->id,
            'object_id' => $this->object_id,
            'upload_type' => $this->raw_files_data['type'][$file_index],
            'upload_name' => $this->raw_files_data['name'][$file_index],
            'upload_tmp_name' => $this->raw_files_data['tmp_name'][$file_index],
        ));

        $file->set_multifield($this->multifield)
             ->set_multifield_settings($this->multifield_settings);

        // проверка размера файла
        $format = $this->field_format;
        if (!empty($format['size']) && $file->get_size() > $format['size']) {
            $file->set('upload_size_error', true);
        }

        // проверка типа файла
        if (!empty($format['type'][0])) {
            $file_type_parts = explode('/', $file->get_mime_type());
            $correct_type = false;

            foreach ($format['type'] as $format_type_parts) {
                $correct_type |=
                    $format_type_parts[0] == $file_type_parts[0] &&
                    (
                        empty($format_type_parts[1]) ||
                        $format_type_parts[1] == '*' ||
                        $format_type_parts[1] == $file_type_parts[1]
                    );
            }

            if (!$correct_type) {
                $file->set('upload_type_error', true);
            }
        }

        return $file;
    }

    /**
     * Возвращает количество файлов (старых и новых), не считая отмеченных
     * для удаления и с ошибками
     * @return int
     */
    public function get_file_count() {
        return $this->get_all_files()
                    ->where_all(array(
                        array('is_deleted', false),
                        array('has_error', false)
                    ))
                    ->count();
    }

    /**
     * Сохранение изменений (удаление файлов, сохранение новых файлов)
     * @return $this
     */
    public function save_changes() {
        $files = $this->get_all_files();
        $old_files = $files->where('is_new', false);

        // удаление файлов отмеченных is_deleted = true
        $old_files->where('is_deleted', true)->each('delete');

        // сохранение новых приоритетов и описаний файлов
        $old_files->where('is_changed', true)->each('save');

        // сохранение новых файлов
        $files->where_all(array(
                    array('is_new', true),
                    array('is_deleted', false),
                    array('has_error', false)
                ))
              ->each('save');

        return $this;
    }

    /**
     * Возвращает текст ошибок несоответствия загруженных файлов формату поля
     * @return string HTML-текст ошибок или пустая строка, если все файлы соответствуют формату поля
     */
    public function get_error_string() {
        $error = $this->get_file_count_error_string() .
                 $this->get_error_string_of_type(NETCAT_MODERATION_MULTIFILE_SIZE, 'upload_size_error') .
                 $this->get_error_string_of_type(NETCAT_MODERATION_MULTIFILE_TYPE, 'upload_type_error');

        if ($error) {
            $replace = array(
                '%NAME' => htmlspecialchars($this->get_field_property('description'), ENT_QUOTES),
                '%SIZE' => nc_bytes2size($this->field_format['size']),
            );

            $error = strtr($error, $replace);
        }

        return $error;
    }

    /**
     * Возвращает текст указанной ошибки
     * @param string $error_text Текст ошибки
     * @param string $error_property Проверяемое свойство
     * @return string
     */
    protected function get_error_string_of_type($error_text, $error_property) {
        $file_names = $this->get_all_files()->where_all(array(
                array('is_new', true),
                array($error_property, true)
            ))
            ->each('get', 'upload_name');

        if ($file_names) {
            return $error_text . ": <b>" .
                   htmlspecialchars(join(', ', $file_names), ENT_QUOTES) .
                   "</b>.<br />\n";
        }

        return '';
    }

    /**
     * Проверяет количество загруженных файлов:
     * — при установленном флаге «обязательно для заполнения» или (для улучшение обратной совместимости)
     *   минимальном количестве файлов, равным 1, должен быть хотя бы один файл;
     * — количество файлов должно быть больше указанного в настройках поля минимального количества,
     *   если загружен хотя бы один файл или поле обязательно для заполнения.
     * @return string HTML-текст ошибок или пустая строка, если все файлы соответствуют настройкам поля
     */
    protected function get_file_count_error_string() {
        $min = $this->get_multifield_setting('min');
        $max = $this->get_multifield_setting('max');
        $num_files = $this->get_file_count();

        $value_required = $this->get_field_property('not_null') || $min == 1;
        if ($value_required && $num_files == 0) {
            return NETCAT_MODERATION_MSG_ONE . '<br />';
        }

        $error = false;
        $expected = null;

        if ($num_files && $num_files < $min) {
            $error = NETCAT_MODERATION_MULTIFILE_MIN_COUNT;
            $expected = $min;
        }
        else if ($max && $num_files > $max) {
            $error = NETCAT_MODERATION_MULTIFILE_MAX_COUNT;
            $expected = $max;
        }

        if ($error) {
            $file_word_forms = explode(",", NETCAT_MODERATION_MULTIFILE_COUNT_FILES);
            $file_word_form = nc_core::get_object()->lang->get_numerical_inclination($expected, $file_word_forms);
            return str_replace('%FILES', "$expected $file_word_form", $error . '<br />');
        }

        return '';
    }

}