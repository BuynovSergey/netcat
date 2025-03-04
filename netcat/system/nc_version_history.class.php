<?php

/**
 * Класс для работы с таблицей Version_ContentHistory.
 *
 * В Version_ContentHistory частично дублируется информация из Version. Это сделано для упрощения и ускорения
 * поиска версии для отмены и возврата отмены (undo, redo) в режиме редактирования.
 *
 * В Version хранятся все версии, в т. ч. созданные через undo и redo. В Version_ContentHistory — «логическая»
 * последовательность версий, которая может быть использована для undo и redo.
 *
 * У записей есть состояние State:
 *      previous — предыдущая «логическая» версия
 *      actual — текущая версия
 *      next — отменённая через undo версия, существует если после undo ещё не производились другие действия
 *
 * @return void
 */
class nc_version_history {

    /**
     * @param nc_version_record $version
     * @return void
     */
    public static function update(nc_version_record $version) {
        $history = function () {
            return nc_db_table::make('Version_ContentHistory');
        };

        $location = array(
            'Catalogue_ID' => $version->get('Catalogue_ID'),
            'Subdivision_ID' => $version->get('Subdivision_ID'),
            'Sub_Class_ID' => $version->get('Sub_Class_ID'),
            'Message_ID' => $version->get('Message_ID'),
        );

        // actual становится previous (за исключением undo)
        $actual_state = 'previous';

        $restored_version_id = $version->get('Restored_Version_ID');
        if ($restored_version_id) {
            $restored_type = $history()->where('Version_ID', $restored_version_id)->get_value('State');
            if ($restored_type === 'previous') {
                // Сделано undo: actual становится next
                $actual_state = 'next';
            }
        }

        // Обновляем State для записи, которая сейчас actual
        $history()->where($location + array('State' => 'actual'))->update(array('State' => $actual_state));

        if ($restored_version_id) {
            // Восстановленная версия становится actual
            $history()->where('Version_ID', $restored_version_id)->update(array('State' => 'actual'));
        } else {
            // Обычная правка (не undo, redo):
            // next больше недоступны
            $history()->where(array('State' => 'next'))->delete();
            // Добавляем новую запись
            $history()->insert(
                $location +
                array(
                    'Version_ID' => $version->get('Version_ID'),
                    'State' => 'actual'
                ));
        }
    }

}