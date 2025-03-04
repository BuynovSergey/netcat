<?php

/* $Id: html.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * Используется для вывода результатов при индексации "в браузере"
 */
class nc_search_logger_html extends nc_search_logger {

    public function __construct($level = null) {
        if (!$level) {
            $level = nc_search::get_setting('LogLevel') |
                    nc_search::LOG_ERROR |
                    nc_search::LOG_CRAWLER_REQUEST |
                    nc_search::LOG_INDEXING_BEGIN_END;
        }
        $this->level = $level;
    }

    public function log($type_string, $message) {
        echo "<div class='log_entry log_$type_string'>",
        "<span class='time'>", strftime("%H:%M:%S"), "</span> ",
        "<span class='type'>", $type_string, "</span> ",
        "<span class='message'>", nl2br(htmlspecialchars($message)), "</span>",
        "</div>\n";
        flush();
        ob_flush();
    }

}