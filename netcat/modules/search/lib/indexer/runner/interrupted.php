<?php
/**
 *
 */
abstract class nc_search_indexer_runner_interrupted implements nc_search_indexer_runner {
    protected $start_time;
    protected $delay;
    protected $memory_threshold;
    protected $memory_limit;
    protected $time_threshold;
    protected $time_limit;
    protected $cycle_limit;
    static protected $original_time_limit;

    abstract protected function get_time_threshold();
    abstract protected function get_memory_threshold();
    abstract protected function get_max_cycles_number();

    /**
     *
     */
    public function __construct() {
        $this->start_time = time();

        $this->time_threshold = $this->get_time_threshold();
        if (self::$original_time_limit === null) {
            self::$original_time_limit = ini_get('max_execution_time');
        }
        $this->time_limit = self::$original_time_limit;

        $this->memory_threshold = $this->get_memory_threshold();
        $this->memory_limit = nc_core::get_object()->get_memory_limit();

        $this->delay = nc_search::get_setting('CrawlerDelay');

        $this->cycle_limit = $this->get_max_cycles_number();

        @set_time_limit(0);
        nc_search::enable_error_logging();
        ignore_user_abort(true);
        nc_Core::get_object()->db->query("SET wait_timeout=900"); // might loose connection when running in slow mode
    }

    /**
     *
     * @param nc_search_indexer $indexer
     * @param $cycle_number
     * @return boolean TRUE: остановка для перезапуска, FALSE: продолжение выполнения
     */
    protected function interrupt_if_needed(nc_search_indexer $indexer, $cycle_number) {
        $memory_use = (function_exists('memory_get_usage')) ? memory_get_usage() : 0;
        $time_use = time() + $this->delay - $this->start_time;

        $out_of_memory = $out_of_time = false;

        // проверяем память
        if ($this->memory_threshold > 0 && $this->memory_limit > 0) {
            if ($this->memory_threshold <= 1) { // относительные значения
                $out_of_memory = (($memory_use / $this->memory_limit) >= $this->memory_threshold);
            } else { // абсолютные значения
                $out_of_memory = ($memory_use >= $this->memory_threshold);
            }
        }

        // проверяем время
        if ($this->time_threshold > 0) {
            if ($this->time_threshold <= 1) { // относительные значения
                // 'max_execution_time' ($this->time_limit) может быть равен 0 при запуске из консоли
                $out_of_time = $this->time_limit && (($time_use / $this->time_limit) >= $this->time_threshold);
            } else { // абсолютные значения
                $out_of_time = ($time_use >= $this->time_threshold);
            }
        }

        // проверяем ограничение на количество циклов
        $is_last_cycle = ($this->cycle_limit > 0 && $cycle_number > $this->cycle_limit);

        // останавливаемся, когда достигли лимита
        if ($out_of_memory || $out_of_time || $is_last_cycle) {
            $indexer->interrupt("mem: $memory_use bytes; time: $time_use s");
            return true;
        }

        return false;
    }

    /**
     *
     * @param nc_search_indexer $indexer
     * @throws nc_search_exception
     * @return boolean is task finished
     */
    public function loop(nc_search_indexer $indexer) {
        $cycle_number = 0;
        $save_cycles = nc_search::get_setting('IndexerSaveTaskEveryNthCycle');

        while (true) {
            // stop prematurely:
            if (!$this->check_connection()) {
                $indexer->cancel();
                return true; // nobody listens anyway
            }

            if ($this->interrupt_if_needed($indexer, $cycle_number)) {
                return false;
            }

            // сохранять задачу каждые X циклов
            if ($cycle_number % $save_cycles == 0) {
                $indexer->save_task();
            }

            switch ($indexer->next()) {
                case nc_search_indexer::TASK_FINISHED:
                    return true; // we're done
                case nc_search_indexer::TASK_STEP_FINISHED:
                    if ($this->delay) {
                        if ($this->interrupt_if_needed($indexer, $cycle_number)) {
                            return false;
                        }
                        sleep($this->delay);
                    }
                    break;
                case nc_search_indexer::TASK_STEP_SKIPPED:
                    break;
                default:
                    throw new nc_search_exception("Incorrect return value from nc_search_indexer::next()");
            }

            $cycle_number++;
        }
    }

    /**
     * @return bool
     */
    protected function check_connection() {
        return true;
    }

}
