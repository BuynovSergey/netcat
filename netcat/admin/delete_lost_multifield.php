<?php

/**
 * Скрипт для удаления потерянных файлов поля типа "Множественная загрузка"
 *
 * История вопроса: из-за бага при удалении объекта с заполненным
 * полем типа "Множественная загрузка" файлы множественной загрузки не удаляются с диска.
 * Скрипт полезен также для удаления файлов, которые могли остаться из-за неверно выставленных прав на запись.
 */

$NETCAT_FOLDER = realpath("../../") . '/';
require $NETCAT_FOLDER . "vars.inc.php";
require $ADMIN_FOLDER . "function.inc.php";

$nc_core = nc_core::get_object();
$db = $nc_core->db;

ob_start();
BeginHtml('Удаление потерянных файлов поля типа "Множественная загрузка"');
echo '<div style="max-width: 1000px">';

$delete_lost_multifield_script_path = nc_standardize_path_to_file($nc_core->ADMIN_PATH . 'delete_lost_multifield.php');

$delete_dirs = array();

$settings_http_path = nc_standardize_path_to_folder($nc_core->HTTP_FILES_PATH . '/multifile/');
$settings_path = nc_standardize_path_to_folder($nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER . '/' . $settings_http_path);

foreach (glob($settings_path . '*', GLOB_ONLYDIR) as $dir) {
    // Получаем список всех директорий по идентификатору поля из базы
    $multifield_dirs = (array)$db->get_col(
        "SELECT `Message_ID`
           FROM `Multifield`
          WHERE `Field_ID` = '" . (int)basename($dir) . "'
          GROUP BY `Message_ID`"        
    );
    
    // Отсеиваем директории
    $field_dirs = array();
    foreach (glob($dir . '/*', GLOB_ONLYDIR) as $multifield_dir) {
        $field_dirs[] = basename($multifield_dir);
    }
    if (sizeof($multifield_dirs)) {
        $field_dirs = array_diff($field_dirs, $multifield_dirs);
    }

    // Добавляем к общему массиву директорий для удаления
    foreach ($field_dirs as $field_dir) {
        $delete_dirs[] = nc_standardize_path_to_folder($dir . "/" . $field_dir . "/");
    }
}

if ($nc_core->input->fetch_get_post('delete_lost_multifield') != 'start') {
    // --- Вывод сообщения -----------------------------------------------------

    if (sizeof($delete_dirs)) {
        $delete_dirs_text = null;
        foreach ($delete_dirs as $delete_dir) {
            $delete_dirs_text .= "<li>" . $delete_dir . "</li>";
        }

        echo <<<EOS
<div class="nc-alert nc--yellow">
    <div>
    <i class="nc-icon-l nc--status-warning"></i>

    <p style="margin-left: 40px"><strong>При удалении объектов с заполненным 
    полем типа «Множественная загрузка» на диске могли остаться файлы, относившиеся к этим объектам.</strong></p>

    <p>Из-за ошибки при удалении объектов в некоторых случаях на диске могли оставаться файлы, относившиеся к удаленным объектам. 
    С помощью этого инструмента будет произведено удаление этих «потерянных» файлов и папок, в которых они расположены.</p>

    <p>Если на вашем сайте найдены подобные папки, и вы не уверены, как изменения
    могут повлиять на работу вашего сайта, советуем, если это возможно, сначала
    проверить как отразятся данные изменения на копии сайта. Копию проекта
    можно создать при помощи инструмента
    «<a href="{$nc_core->ADMIN_PATH}/#tools.backup" target="_top">Архивы проекта</a>».</p>

    <p>При нажатии на кнопку «Удалить неиспользуемые файлы» будут удалены нижеприведенные папки вместе со всем их содержимым:</p>

    <ul style="margin-bottom:20px; max-height:120px; overflow-y: scroll;">
        {$delete_dirs_text}
    </ul>

    <p><a href="{$delete_lost_multifield_script_path}?delete_lost_multifield=start" class="nc-btn nc--small nc--blue">
    Удалить неиспользуемые файлы
    </a></p>

    </div>
</div>

EOS;

    }
    else {
        // Ничего не нужно делать — нет файлов, которые требуют удаления
        echo '<div class="nc-alert nc--green">'.
             '<i class="nc-icon-l nc--status-success"></i>'.
             'Нет файлов, которые требуют удаления.'.
             '</div>';
    }
}
else {
    // --- Удаление категорий и файлов -------------------------------------------------
    $no_errors = true;
    
    foreach ($delete_dirs as $delete_dir) {
        if (is_writable($delete_dir)) {
            $no_error_dir = true;
            $files = array_diff(scandir($delete_dir), array('..', '.'));
            foreach ($files as $file) {
                $delete_file = nc_standardize_path_to_file($delete_dir . $file);
                if (is_writable($delete_file)) {
                    unlink($delete_file);
                } else {
                    $no_error_dir = false;
                    $no_errors = false;
                    echo '<div class="nc-alert nc--red">'.
                        '<i class="nc-icon-l nc--status-error"></i>'.
                        'Не удалось удалить файл из-за отсутствия прав: <pre>'. $delete_file . '</pre>'.
                        '</div>';
                    // Завершаем перебор файлов в папке, т.к. с остальными скорее всего аналогичные проблемы
                    break;
                }
            }
            if ($no_error_dir) {
                rmdir($delete_dir);
            }
        } else {
            $no_errors = false;
            echo '<div class="nc-alert nc--red">'.
                 '<i class="nc-icon-l nc--status-error"></i>'.
                 'Не удалось удалить директорию из-за отсутствия прав: <pre>'. $delete_dir . '</pre>'.
                 '</div>';
        }
    }

    if ($no_errors) {
        echo '<div class="nc-alert nc--green">',
             '<i class="nc-icon-l nc--status-success"></i>',
             'Директории успешно удалены.',
             '</div>';
    }

}


echo '</div>';
EndHtml();

$buffer = ob_get_clean();
if (!$nc_core->NC_UNICODE) {
    $buffer = $nc_core->utf8->utf2win($buffer);
}

echo $buffer;