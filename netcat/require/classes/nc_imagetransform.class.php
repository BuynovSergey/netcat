<?php

@ini_set('gd.jpeg_ignore_warning', 1);

/**
 * Класс для изменения изображений
 *
 * @see nc_ImageTransform::imgResize()
 */
class nc_ImageTransform extends nc_System {
    const RESIZE_TO_BEST_FIT = 0;
    const RESIZE_TO_BEST_FIT_WITH_CROP = 1;
    const RESIZE_TO_WIDTH_WITH_CROP = 2;
    const RESIZE_TO_HEIGHT_WITH_CROP = 3;
    // Не документировано: используется только для «внутренних» целей (в nc_image_generator для изображений с указанием scale):
    const ALWAYS_RESIZE = 4; // используется как битовая маска! нужно учитывать при создании новых констант!

    const WATERMARK_POSITION_CENTER = 0;
    const WATERMARK_POSITION_TOP_LEFT = 1;
    const WATERMARK_POSITION_TOP_RIGHT = 2;
    const WATERMARK_POSITION_BOTTOM_LEFT = 3;
    const WATERMARK_POSITION_BOTTOM_RIGHT = 4;

    const WATERMARK_PADDING_SIZE = 3;

    const DEFAULT_JPG_QUALITY = 90;
    const DEFAULT_WEBP_QUALITY = 90;
    const DEFAULT_PNG_QUALITY = 6;
    const DEFAULT_BACKGROUND = 'ffffff';

    protected static $_thumbPostfix;

    /**
     * Создает уменьшенную копию изображения
     *
     * @param string $src_img Путь к исходному изображению
     * @param string $dest_img Путь к создаваемому изображению
     * @param int $width Ширина нового изображения
     * @param int $height Высота нового изображения
     * @param int $mode [optional] Режим уменьшения:
     * - RESIZE_TO_BEST_FIT - пропорционально уменьшает;
     * - RESIZE_TO_BEST_FIT_WITH_CROP - вписывает в указанные размеры по наиболее подходящей стороне, обрезая края
     * - RESIZE_TO_WIDTH_WITH_CROP - вписывает в указанные размеры по ширине, обрезая края
     * - RESIZE_TO_HEIGHT_WITH_CROP - вписывает в указанные размеры по высоте, обрезая края
     * @param string $format [optional] Формат создаваемого изображения (jpg, gif, png, webp)
     * @param int|null $quality [optional] Качество сжатия изображения
     *   (0—100 для jpg, 0-100 для webp, 0—9 для png; null — использовать значение по умолчанию для соответствующего формата)
     * @param int $message_id номер объекта, к которому относится файл
     * @param int $field номер поля или его имя, к которому относится файл
     * @param string $color цвет заливки полей изображения при режиме уменьшения $mode = 3 в 16-ричном формате
     * @return mixed В случае ошибки возвратит false иначе возвратит путь к созданному файлу
     */
    public static function imgResize($src_img, $dest_img, $width, $height, $mode = self::RESIZE_TO_BEST_FIT, $format = NULL, $quality = NULL, $message_id = 0, $field = 0, $color = self::DEFAULT_BACKGROUND) {
        global $classID, $systemTableID;
        $nc_core = nc_Core::get_object();

        if (!file_exists($src_img)) {
            return false;
        }
        $img_size = @getimagesize($src_img);
        if ($img_size === false) {
            return false;
        }

        $img_format = strtolower(substr($img_size['mime'], strpos($img_size['mime'], '/') + 1));
        if (!function_exists($fn_imgcreatefrom = 'imagecreatefrom' . $img_format)) {
            return false;
        }

        if (!$format) {
            $format = $img_format;
        }

        $mode = (int)$mode;
        if ($mode & self::ALWAYS_RESIZE) {
            $mode &= ~self::ALWAYS_RESIZE;
            $always_resize = true;
        } else {
            $always_resize = false;
        }

        if (!$always_resize && $img_size[0] <= $width && $img_size[1] <= $height) {
            if ($dest_img !== $src_img) {
                copy($src_img, $dest_img);
            }
            return $dest_img;
        }
        
        // качество по умолчанию
        if ($quality === NULL) {
            if (preg_match('@jpe?g@i', $format)) {
                $quality = self::DEFAULT_JPG_QUALITY;
            }

            if ($format === 'png') {
                $quality = self::DEFAULT_PNG_QUALITY;
            }

            if ($format === 'webp') {
                $quality = self::DEFAULT_WEBP_QUALITY;
            }
        }

        $x_ratio = $width / $img_size[0];
        $y_ratio = $height / $img_size[1];
        $new_x = 0;
        $new_y = 0;
        list($old_width, $old_height) = $img_size;

        $dst_x = 0;
        $dst_y = 0;

        if ($mode == self::RESIZE_TO_BEST_FIT) {
            if ($x_ratio < $y_ratio) {
                $new_width = $width;
                $new_height = floor($x_ratio * $img_size[1]);
            } else {
                $new_height = $height;
                $new_width = floor($y_ratio * $img_size[0]);
            }
        } elseif ($mode == self::RESIZE_TO_BEST_FIT_WITH_CROP) {
            $new_height = $height;
            $new_width = $width;
            $new_x_ratio = $old_width / $new_width;
            $new_y_ratio = $old_height / $new_height;
            if ($new_x_ratio < $new_y_ratio) {
                $old_height = floor($new_x_ratio * $new_height);
                $new_y = floor(($img_size[1] - $old_height) / 2);
            } elseif ($new_x_ratio > $new_y_ratio) {
                $old_width = floor($new_y_ratio * $new_width);
                $new_x = floor(($img_size[0] - $old_width) / 2);
            }
        } else {
            $new_height = $height;
            $new_width = $width;
            $new_x_ratio = $old_width / $new_width;
            $new_y_ratio = $old_height / $new_height;
            if ($mode == self::RESIZE_TO_WIDTH_WITH_CROP) {
                $old_height = floor($new_x_ratio * $new_height);
                $new_y = floor(($img_size[1] - $old_height) / 2);
            } elseif ($mode == self::RESIZE_TO_HEIGHT_WITH_CROP) {
                $new_x = 0;
                $old_width = $img_size[0];
                $dst_x = (floor($new_width / 2) - (floor($old_width/$new_y_ratio) / 2));
                $new_width_dst = floor($old_width/$new_y_ratio);
            }
        }

        list($r, $g, $b) = sscanf($color, '%02x%02x%02x');
        $gd_dest_img = imagecreatetruecolor($new_width, $new_height);
        $gd_src_img = $fn_imgcreatefrom($src_img);

        if ($format === 'png' || $format === 'gif' || $format === 'webp') {
            imagealphablending($gd_dest_img, false);
            imagesavealpha($gd_dest_img, true);
            $transparent = imagecolorallocatealpha($gd_dest_img, 255, 255, 255, 127);
            imagefilledrectangle($gd_dest_img, 0, 0, $new_width, $new_height, $transparent);
        } else {
            $bgColor = imagecolorallocate($gd_dest_img, $r, $g, $b);
            imagefill($gd_dest_img, 0, 0, $bgColor);
        }

        if ($mode == self::RESIZE_TO_HEIGHT_WITH_CROP) {
            $new_width = $new_width_dst;
        }

        imagecopyresampled($gd_dest_img, $gd_src_img, $dst_x, $dst_y, $new_x, $new_y, $new_width, $new_height, $old_width, $old_height);
        switch ($format) {
            case 'gif':
                imagegif($gd_dest_img, $dest_img);
                break;
            case 'png':
                imagepng($gd_dest_img, $dest_img, min($quality, 9));
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    imagewebp($gd_dest_img, $dest_img, $quality);
                } else {
                    imagejpeg($gd_dest_img, $dest_img, $quality);
                }
                break;
            default:
                imagejpeg($gd_dest_img, $dest_img, $quality);
                break;
        }
        imagedestroy($gd_dest_img);
        imagedestroy($gd_src_img);

        // нужно поменять размер в таблице Filetable
        $HTTP_FILES_PATH_PREG = str_replace('/', "\/", $nc_core->HTTP_FILES_PATH);
        // есть файл в защищенной фс
        if (preg_match('/' . $HTTP_FILES_PATH_PREG . "([0-9uct]+)\/([0-9]+\/)?([0-9A-Z]{32})/i", $dest_img, $matches)) {

            $filename = $matches[3];
            $size = (int)filesize($dest_img);

            $nc_core->db->query("UPDATE `Filetable` SET `File_Size` = '$size' WHERE `Virt_Name` = '{$nc_core->db->escape($filename)}'");
        }

        // обновление таблицы MessageXX, User
        $message_id = (int)$message_id;
        if ($message_id && $field) {
            // информация о поле
            $fld = $nc_core->db->get_row(
                "SELECT `Class_ID`, `System_Table_ID`, `Field_Name`
                 FROM `Field`
                 WHERE `Field_ID` = '" . (int)$field . "'
                 OR (`Field_Name` = '{$nc_core->db->escape($field)}' AND (`Class_ID` = '" . (int)$classID . "' OR `System_Table_ID` = '" . (int)$systemTableID . "'))
                 LIMIT 1",
                ARRAY_A);

            // определение имени таблицы
            if ($fld['Class_ID']) {
                $table = 'Message' . (int)$fld['Class_ID'];
                $where = " `Message_ID` = '$message_id' ";
            } else {
                $table = $nc_core->db->get_var("SELECT `System_Table_Name` FROM `System_Table` WHERE `System_Table_ID` = '" . (int)$fld['System_Table_ID'] . "'");
                $where = " `{$table}_ID` = '$message_id' ";
            }

            if (!$table) {
                return false;
            }
            // текущее значение поля объекта
            $value = $nc_core->db->get_var("SELECT `$fld[Field_Name]` FROM `$table` WHERE $where");

            if (!$value) {
                return false;
            }
            // обновляем размер
            $size = filesize($dest_img);
            $value = nc_preg_replace('/:[0-9]+/', ':' . $size, preg_quote($value), 1);

            $nc_core->db->query("UPDATE `$table` SET `$fld[Field_Name]` = '{$nc_core->db->escape($value)}' WHERE $where");
        }

        return $dest_img;
    }

    /**
     * Обрезает изображение
     *
     * @param string $src_img Путь к исходному изображению
     * @param string $dest_img Путь к создаваемому изображению
     * @param int $x0 X0
     * @param int $y0 Y0
     * @param int $x1 X1
     * @param int $y1 Y1
     * @param string $format [optional] Формат создаваемого изображения (jpg, gif, png, webp)
     * @param int|null $quality [optional] Качество сжатия изображения
     *   (0—100 для jpg, 0-100 для webp, 0—9 для png; null — использовать значение по умолчанию для соответствующего формата)
     * @param int $message_id номер объекта, к которому относится файл
     * @param int $field номер поля или его имя, к которому относится файл
     * @param int $ignore_crop_width не обрезать, если меньше указанной ширины
     * @param int $ignore_crop_height не обрезать, если меньше указанной ширины
     * @param int $crop_mode_center 0 - обрезка по координатам, 1 - обрезка по центру
     * @param int $crop_width ширина вырезаемого из центра куска
     * @param int $crop_height высота вырезаемого из центра куска
     * @param string $color цвет полей (hex)
     *
     * @return mixed В случае ошибки возвратит false иначе возвратит путь к созданному файлу
     */
    public static function imgCrop($src_img, $dest_img, $x0, $y0, $x1, $y1, $format = NULL, $quality = NULL, $message_id = 0, $field = 0, $ignore_crop_width = 0, $ignore_crop_height = 0, $crop_mode_center = 0, $crop_width = 0, $crop_height = 0, $color = self::DEFAULT_BACKGROUND) {
        global $classID, $systemTableID;
        $nc_core = nc_Core::get_object();

        if (!file_exists($src_img)) {
            return false;
        }
        $img_size = @getimagesize($src_img);
        if ($img_size === false) {
            return false;
        }

        list($src_width, $src_height) = $img_size;
        if ($ignore_crop_width && $ignore_crop_height && $src_width < $ignore_crop_width && $src_height < $ignore_crop_height) {
            return false;
        }

        $x0 = (int)$x0;
        $x1 = (int)$x1;
        $y0 = (int)$y0;
        $y1 = (int)$y1;

        $img_format = strtolower(substr($img_size['mime'], strpos($img_size['mime'], '/') + 1));

        if (!function_exists($fn_imgcreatefrom = 'imagecreatefrom' . $img_format)) {
            return false;
        }

        if (!$format) {
            $format = $img_format;
        }
        
        // качество по умолчанию
        if ($quality === NULL) {
            if (preg_match('@jpe?g@i', $format)) {
                $quality = self::DEFAULT_JPG_QUALITY;
            }

            if ($img_format === 'png') {
                $quality = self::DEFAULT_PNG_QUALITY;
            }

            if ($format === 'webp') {
                $quality = self::DEFAULT_WEBP_QUALITY;
            }
        }

        $dst_x = 0;
        $dst_y = 0;

        if ($crop_mode_center) {
            $dst_width = (int)$crop_width;
            $dst_height = (int)$crop_height;
            $src_x = floor(($src_width  - $dst_width) / 2);
            $src_y = floor(($src_height - $dst_height) / 2);

            if ($src_x < 0) {
                $dst_x -= $src_x;
                $src_x = 0;
            }

            if ($src_y < 0) {
                $dst_y -= $src_y;
                $src_y = 0;
            }
        } else {
            $dst_width  = $x1 - $x0;
            $dst_height = $y1 - $y0;
            $src_y = $y0;
            $src_x = $x0;
        }

        $gd_dst_img = imagecreatetruecolor($dst_width, $dst_height);
        $gd_src_img = $fn_imgcreatefrom($src_img);

        if ($format === 'png' || $format === 'gif' || $format === 'webp') {
            imagealphablending($gd_dst_img, false);
            imagesavealpha($gd_dst_img, true);
            $transparent = imagecolorallocatealpha($gd_dst_img, 255, 255, 255, 127);
            imagefilledrectangle($gd_dst_img, 0, 0, $dst_width, $dst_height, $transparent);
        } else {
            list($r, $g, $b) = sscanf($color, '%02x%02x%02x');
            imagefill($gd_dst_img, 0, 0, imagecolorallocate($gd_dst_img, $r, $g, $b));
        }

        imagecopy(
            $gd_dst_img,
            $gd_src_img,
            $dst_x, $dst_y,
            $src_x, $src_y,
            min($src_width,  $dst_width),
            min($src_height, $dst_height)
        );

        switch ($format) {
            case 'gif':
                imagegif($gd_dst_img, $dest_img);
                break;
            case 'png':
                imagepng($gd_dst_img, $dest_img, min($quality, 9));
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    imagewebp($gd_dst_img, $dest_img, $quality);
                } else {
                    imagejpeg($gd_dst_img, $dest_img, $quality);
                }
                break;
            default:
                imagejpeg($gd_dst_img, $dest_img, $quality);
                break;
        }
        imagedestroy($gd_dst_img);
        imagedestroy($gd_src_img);

        // нужно поменять размер в таблице Filetable
        $HTTP_FILES_PATH_PREG = str_replace('/', "\/", $nc_core->HTTP_FILES_PATH);
        // есть файл в защищенной фс
        if (preg_match('/' . $HTTP_FILES_PATH_PREG . "([0-9uct]+)\/([0-9]+\/)?([0-9A-Z]{32})/i", $dest_img, $matches)) {
            $filename = $matches[3];
            $size = (int)filesize($dest_img);

            $nc_core->db->query(
                "UPDATE `Filetable`
                 SET `File_Size` = '$size'
                 WHERE `Virt_Name` = '{$nc_core->db->escape($filename)}'"
            );
        }

        // обновление таблицы MessageXX, User
        $message_id = (int)$message_id;
        if ($message_id && $field) {
            // информация о поле
            $fld = $nc_core->db->get_row(
                "SELECT `Class_ID`, `System_Table_ID`, `Field_Name`
                 FROM `Field`
                 WHERE `Field_ID` = '" . (int)$field . "'
                 OR (`Field_Name` = '{$nc_core->db->escape($field)}' AND (`Class_ID` = '" . (int)$classID . "' OR `System_Table_ID` = '" . (int)$systemTableID . "'))
                 LIMIT 1",
                ARRAY_A
            );

            // определение имени таблицы
            if ($fld['Class_ID']) {
                $table = 'Message' . (int)$fld['Class_ID'];
                $where = " `Message_ID` = '{$message_id}'";
            } else {
                $table = $nc_core->db->get_var("SELECT `System_Table_Name` FROM `System_Table` WHERE `System_Table_ID` = '" . (int)$fld['System_Table_ID'] . "'");
                $where = " `{$table}_ID` = '{$message_id}'";
            }

            if (!$table) {
                return false;
            }
            // текущее значение поля объекта
            $value = $nc_core->db->get_var("SELECT `$fld[Field_Name]` FROM `$table` WHERE $where");

            if (!$value) {
                return false;
            }
            // обновляем размер
            $size = filesize($dest_img);
            $value = nc_preg_replace('/:[0-9]+/', ':' . $size, preg_quote($value, '/'), 1);

            $nc_core->db->query("UPDATE `$table` SET `$fld[Field_Name]` = '{$nc_core->db->escape($value)}' WHERE $where");
        }

        return $dest_img;
    }

    /**
     * Функции для создания thumbnails для полей типа файл,
     * в действиях после добавления, после изменения.
     *
     * @param string $src_field_name - имя поля-источника
     * @param string $dest_field_name - имя поля-приёмника
     * @param int $width       Ширина нового изображения
     * @param int $height      Высота нового изображения
     * @param int $mode     [optional] Режим уменьшения: 0 - пропорционально уменьшает; 1 - вписывает в указанные размеры, обрезая края
     * @param string $format   [optional] Формат создаваемого изображения (jpg, gif, png, webp)
     * @param int|null $quality [optional] Качество сжатия изображения
     *   (0—100 для jpg, 0-100 для webp, 0—9 для png, null — использовать значение по умолчанию для соответствующего формата)
     *
     * @return bool true в случае удачи, false - в случае ошибки.
     */
    public static function createThumb($src_field_name, $dest_field_name, $width, $height, $mode = self::RESIZE_TO_BEST_FIT, $format = NULL, $quality = NULL) {
        global $GLOBALS;
        global $message, $classID;
        $nc_core = nc_Core::get_object();

        $src_field_id = $GLOBALS['fldID'][array_search($src_field_name, $GLOBALS['fld'], true)];
        $is_sys = $nc_core->db->get_row("SELECT Class_ID, System_Table_ID from Field WHERE Field_ID = '$src_field_id'", ARRAY_A);
        $is_sys = $is_sys["System_Table_ID"];
        $dest_field_id = $GLOBALS['fldID'][array_search($dest_field_name, $GLOBALS['fld'], true)];

        return self::createThumb_byID($classID, $message, $src_field_id, $dest_field_id, $width, $height, $mode, $format, $quality, $src_field_name, $is_sys);
    }

    /**
     * Функции для создания thumbnails для полей типа файл
     *
     *
     * @param int $classID идентификатор класса (компонента)
     * @param int $message номер объекты
     * @param int $field_src_id идентификатор поля источника
     * @param int $field_dst_id идентификатор поля приемника
     * @param int $width Ширина нового изображения
     * @param int $height Высота нового изображения
     * @param int $mode [optional] Режим уменьшения:
     * - RESIZE_TO_BEST_FIT - пропорционально уменьшает;
     * - RESIZE_TO_BEST_FIT_WITH_CROP - вписывает в указанные размеры по наиболее подходящей стороне, обрезая края
     * - RESIZE_TO_WIDTH_WITH_CROP - вписывает в указанные размеры по ширине, обрезая края
     * - RESIZE_TO_HEIGHT_WITH_CROP - вписывает в указанные размеры по высоте, обрезая края
     * @param string $format [optional] Формат создаваемого изображения (jpg, gif, png, webp)
     * @param int|null $quality [optional] Качество сжатия изображения
     *   (0—100 для jpg, 0-100 для webp, 0—9 для png, null — использовать значение по умолчанию для соответствующего формата)
     * @param string [optional] $field_name_src
     *
     * @return bool true в случае удачи, false - в случае ошибки.
     */
    public static function createThumb_byID($classID, $message, $field_src_id, $field_dst_id, $width, $height, $mode = self::RESIZE_TO_BEST_FIT, $format = NULL, $quality = NULL, $field_name_src = '', $is_sys = false) {
        $nc_core = nc_Core::get_object();

        $classID = $is_sys ? $nc_core->get_system_table_name_by_id($is_sys) : $classID;
        $src_file_info = $nc_core->file_info->get_file_info($classID, $message, $field_src_id, false);

        if (!$src_file_info['url']) {
            return false;
        }

        $ext = $format ? '.' . $format : '.' . pathinfo($src_file_info['name'], PATHINFO_EXTENSION); // расширение файла
        $file_name = pathinfo($src_file_info['name'], PATHINFO_FILENAME); // имя файла без расширения.

        //save result to tmp file
        $tmp_file = nc_core('SUB_FOLDER') . nc_core('HTTP_FILES_PATH') . md5($src_file_info['url']);

        self::imgResize(
            nc_core('DOCUMENT_ROOT') . nc_core('SUB_FOLDER') . $src_file_info['url'],
            nc_core('DOCUMENT_ROOT') . $tmp_file,
            $width,
            $height,
            $mode,
            $format,
            $quality
        );

        $file = array(
            'path' => $tmp_file,
            'name' => $file_name . '_thumb' . ++self::$_thumbPostfix . $ext,
            'type' => $src_file_info['type'],
        );
        //save to $field_dst_id
        $dst_file_info = $nc_core->files->field_save_file($classID, $field_dst_id, $message, $file, true);

        unlink(nc_core('DOCUMENT_ROOT') . $tmp_file);

        return !empty($dst_file_info);
    }

    public static function putWatermark_file($filepath, $watermark, $mode = self::WATERMARK_POSITION_CENTER, $quality = NULL, $scale = 1) {
        $nc_core = nc_Core::get_object();
        // исходный файл
        if (!file_exists($filepath)) {
            $filepath = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $filepath;
        }
        if (!file_exists($filepath)) {
            trigger_error("File $filepath not found.", E_USER_WARNING);
            return;
        }
        $src = getimagesize($filepath);
        list($src_w, $src_h) = $src;
        $src_type = strtolower(substr($src['mime'], strpos($src['mime'], '/') + 1)); // тип
        // в зависимости от типа - разные функции
        $func = function_exists('imagecreatefrom' . $src_type) ? 'imagecreatefrom' . $src_type : 'imagecreatefromjpeg';
        // ресурс
        $img_src = $func($filepath);

        // ватермарк
        if (!file_exists($watermark)) {
            $watermark = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . $watermark;
        }
        if (!file_exists($watermark)) {
            trigger_error("File $watermark not found.", E_USER_WARNING);
            return;
        }
        $water = getimagesize($watermark);
        list($original_water_w, $original_water_h) = $water;
        $water_type = strtolower(substr($water['mime'], strpos($water['mime'], '/') + 1)); // тип
        // в зависимости от типа - разные функции
        $func = function_exists('imagecreatefrom' . $water_type) ? 'imagecreatefrom' . $water_type : 'imagecreatefromjpeg';
        // ресурс
        $img_water = $func($watermark);

        // результат
        $img = imagecreatetruecolor($src_w, $src_h);

        $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $transparent);
        imagesavealpha($img, true);

        // копируем в результат исходное изображение
        imagealphablending($img_src, false);
        imagecopyresampled($img, $img_src, 0, 0, 0, 0, $src_w, $src_h, $src_w, $src_h);
        imagealphablending($img_src, true);

        // масштабирование ватермарка
        $water_w = floor($original_water_w * $scale);
        $water_h = floor($original_water_h * $scale);

        // определяем, куда копировать ватермарк
        switch ($mode) {
            case self::WATERMARK_POSITION_TOP_LEFT:
                $x = $y = self::WATERMARK_PADDING_SIZE;
                break;
            case self::WATERMARK_POSITION_TOP_RIGHT:
                $x = $src_w - $water_w - self::WATERMARK_PADDING_SIZE;
                $y = self::WATERMARK_PADDING_SIZE;
                break;
            case self::WATERMARK_POSITION_BOTTOM_LEFT:
                $y = $src_h - $water_h - self::WATERMARK_PADDING_SIZE;
                $x = self::WATERMARK_PADDING_SIZE;
                break;
            case self::WATERMARK_POSITION_BOTTOM_RIGHT:
                $x = $src_w - $water_w - self::WATERMARK_PADDING_SIZE;
                $y = $src_h - $water_h - self::WATERMARK_PADDING_SIZE;
                break;
            default: // по центру
                $x = floor(($src_w - $water_w) / 2);
                $y = floor(($src_h - $water_h) / 2);
        }
        if ($x < 0) {
            $x = 0;
        }
        if ($y < 0) {
            $y = 0;
        }

        // копируем  ватермарк
        imagealphablending($img_water, false);
        imagecopyresampled($img, $img_water, $x, $y, 0, 0, $water_w, $water_h, $original_water_w, $original_water_h);
        imagealphablending($img_water, true);

        // записываем в файл
        $func = function_exists('image' . $src_type) ? 'image' . $src_type : 'imagejpeg';

        // качество по умолчанию
        if ($quality === NULL) {
            if ($src_type === 'jpeg') {
                $quality = self::DEFAULT_JPG_QUALITY;
            }

            if ($src_type === 'png') {
                $quality = self::DEFAULT_PNG_QUALITY;
            }

            if ($src_type === 'webp') {
                $quality = self::DEFAULT_WEBP_QUALITY;
            }
        }

        // можно задать качество
        if ($func === 'imagejpeg' || $func === 'imagepng' || $func === 'imagewebp') {
            $r = $func($img, $filepath, $quality);
        } else {
            $r = $func($img, $filepath);
        }

        imagedestroy($img);
        imagedestroy($img_src);
        imagedestroy($img_water);

        return $r;
    }

    public static function putWatermark($classID, $field, $message, $watermark, $mode = self::WATERMARK_POSITION_CENTER, $quality = NULL) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        $message = (int)$message;
        $src = nc_file_path($classID, $message, $field);
        if (!$src) {
            return false;
        }

        // вставляем ватермарк
        self::putWatermark_file($src, $watermark, $mode, $quality);
        // теперь нужно обновить размер
        $systemTableID = 0;
        // определяем таблицу и первичный ключ в ней
        if (!is_int($classID)) {
            $table = $db->escape($classID);
            $pk = $db->escape($classID) . '_ID';
            $systemTableID = $nc_core->get_system_table_id_by_name($classID);
        } else {
            $table = 'Message' . (int)$classID;
            $pk = 'Message_ID';
        }
        // определяем номер поля и его имя
        if (is_int($field)) {
            $field_id = (int)$field;
            $field_name = $db->get_var("SELECT `Field_Name` FROM `Field` WHERE `Field_ID` = '$field_id' ");
        } else {
            $field_name = $db->escape($field);
            $field_id = $db->get_var("SELECT `Field_ID` FROM `Field` WHERE `Field_Name` = '$field_name' AND " . ($systemTableID ? "`System_Table_ID` = '$systemTableID'" : "`Class_ID` = '$classID'"));
        }
        // новое значение
        clearstatcache();
        $filesize = filesize($nc_core->DOCUMENT_ROOT . $src);
        $old_value = $db->get_var("SELECT `$field_name` FROM `$table` WHERE `$pk` = '$message'");
        $new_value = preg_replace("/:(\d+):/", ':' . $filesize . ':', $old_value);
        $new_value = preg_replace("/:(\d+)$/", ':' . $filesize, $new_value);
        $db->query("UPDATE `{$table}` SET `$field_name` = '{$db->escape($new_value)}' WHERE `$pk` = '$message'");
        // и в таблице Filetable
        $db->query("UPDATE `Filetable` SET `File_Size` = '$filesize' WHERE `Message_ID` = '$message' AND `Field_ID` = '$field_id'");
        return true;
    }
}