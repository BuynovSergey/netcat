<?php

/* $Id: intent.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Запись для планировщика о том, что нужно выполнить переиндексацию в указанное
 * время (однократное расписание).
 *
 * Отделена от nc_search_rule по следующим причинам:
 *   - возможнна переиндексация с указанием области, а не "правила" (т.е.
 *     переиндексация не по "правилу")
 *   - возможна переиндексация "правила" по запросу
 */
class nc_search_scheduler_intent extends nc_search_data_persistent {
    const ON_REQUEST = 1;
    const SCHEDULED = 2;

    protected $properties = array(
            'id' => null,
            'type' => self::ON_REQUEST,
            'area_string' => null,
            'start_time' => null, // integer timestamp
    );
    protected $table_name = 'Search_Schedule';
    protected $mapping = array(
            'id' => 'Schedule_ID',
            '_generate' => true,
    );

}