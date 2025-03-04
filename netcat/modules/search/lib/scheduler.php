<?php

/* $Id: scheduler.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Планировщик переиндексации
 */
class nc_search_scheduler {

    /**
     * Запланировать запуск переиндексирования области или правила в указанное время
     * @param string $area_string
     * @param integer $timestamp
     */
    public static function schedule_indexing($area_string, $timestamp) {
        // Если данная область уже поставлена в очередь на более раннее или ближайшее
        // время, не нужно добавлять ещё раз
        $interval = $timestamp + nc_search::get_setting('MinScheduleInterval');
        $intent = nc_search::load('nc_search_scheduler_intent',
                        "SELECT * FROM `%t%`".
                        " WHERE `StartTime` <= $interval".
                        "   AND `AreaString` = '".nc_search_util::db_escape($area_string)."'")->first();
        // type is ignored
        if ($intent) { // уже есть такое расписание!
            if ($intent->get('start_time') > $timestamp) {
                $intent->set('start_time', $timestamp); // let's run it sooner
            }
        } else {
            $intent = new nc_search_scheduler_intent(array(
                            'start_time' => $timestamp,
                            'type' => nc_search_scheduler_intent::ON_REQUEST,
                            'area_string' => $area_string,
                    ));
        }

        $intent->save();
    }

    /**
     * Выполнить первую задачу из очереди
     * @param int $indexer_strategy
     * @return bool|null
     */
    public static function run($indexer_strategy = nc_search::INDEXING_NC_CRON) {
        $provider = nc_search::get_provider();
        if ($provider->is_reindexing()) {
            nc_search::log(nc_search::LOG_SCHEDULER_START, "Scheduler: indexing in progress");
            return false;
        }

        $intent = nc_search::load('nc_search_scheduler_intent',
                        'SELECT * FROM `%t%`'.
                        ' WHERE `StartTime` <= '.time().// UNIX_TIMESTAMP(NOW())
                        // imho возможна проблема с TZ, если разные настройки в php и mysql ↑
                        ' ORDER BY `StartTime` ASC LIMIT 1')->first();

        if (!$intent) {
            nc_search::log(nc_search::LOG_SCHEDULER_START, "Scheduler: no scheduler intents to process now");
            return false;
        }

        if (nc_search::will_log(nc_search::LOG_SCHEDULER_START)) {
            nc_search::log(nc_search::LOG_SCHEDULER_START,
                            "Scheduler started (planned start time: ".
                            strftime("%Y-%m-%d %H:%M:%S", $intent->get('start_time')).
                            "; area: '".preg_replace("/\s+/u", " ", $intent->get('area_string')).
                            "')");
        }

        // информация принята к сведению и больше не нужна
        $intent->delete();

        // запуск индексации
        $provider->index_area($intent->get('area_string'), $indexer_strategy);
    }

}