<?php

use Sinergi\BrowserDetector\Browser;
use Sinergi\BrowserDetector\Os;

require_once __DIR__ . '/nc_stats.class.php';
$nc_core->register_class_autoload_path('nc_stats_', __DIR__ . '/classes');

// Инициализация обработчиков буфера и событий для скриптов аналитики
$nc_core->event->add_listener(nc_event::AFTER_MODULES_LOADED, function() {
    nc_stats::get_instance()->analytics->init_event_listeners();
});

require_once 'openstat/function.inc.php';

function Stats_Log() {
    global $AUTH_USER_ID;
    global $e404_sub, $catalogue, $sub, $cc, $message, $action;

    $nc_core = nc_Core::get_object();
    $db = &$nc_core->db;

    if (!$nc_core->get_settings('NC_Stat_Enabled', 'stats')) {
        return;
    }

    if ($nc_core->admin_mode) {
        return;
    }

    if ($sub == $e404_sub) {
        return;
    }

    // don't log access from the server's IP (netcat search crawler)
    $real_ip = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];
    if ($_SERVER['SERVER_ADDR'] === $real_ip) {
        return;
    }

    // bot requests are not logged
    $os = new Os();
    if ($os->getName() === Os::UNKNOWN) {
        return;
    }
    $os_name = trim($os->getName() . ' ' . $os->getVersion());

    $browser = new Browser();
    if ($browser->getName() === Browser::UNKNOWN || $browser->isRobot()) {
        return;
    }
    $browser_name = trim($browser->getName() . ' ' . $browser->getVersion());

    // validate
    $catalogue = (int)$catalogue;
    $sub = (int)$sub;
    $cc = (int)$cc;

    $saved_message_id = 0;
    if (isset($message)) {
        if (is_scalar($message)) {
            $saved_message_id = (int)$message;
        } else if (is_array($message)) {
            $saved_message_id = (int)current($message);
        }
    }

    $CookieID = $_COOKIE['CookieID'];

    if (!$CookieID) {
        $CookieID = md5(uniqid(mt_rand(), true));
        $nc_core->cookie->set('CookieID', $CookieID, time() + 3600 * 24 * 365);
    }

    $country = Stats_IP2Country($_SERVER['REMOTE_ADDR']);

    $db->query(
        "INSERT DELAYED INTO `Stats_Log`
         SET `Created` = NOW(),
         `User_ID` = '{$AUTH_USER_ID}',
         `REMOTE_ADDR` = '{$db->escape($_SERVER['REMOTE_ADDR'])}',
         `REMOTE_HOST` = '',
         `REMOTE_PORT` = '{$db->escape($_SERVER['REMOTE_PORT'])}',
         `HTTP_REFERER` = '{$db->escape($_SERVER['HTTP_REFERER'])}',
         `HTTP_HOST` = '{$db->escape($_SERVER['HTTP_HOST'])}',
         `HTTP_USER_AGENT` = '{$db->escape($_SERVER['HTTP_USER_AGENT'])}',
         `REQUEST_URI` = '{$db->escape($_SERVER['REQUEST_URI'])}',
         `REQUEST_METHOD` = '{$db->escape($_SERVER['REQUEST_METHOD'])}',
         `Country` = '{$db->escape($country)}',
         `Browser` = '{$db->escape($browser_name)}',
         `OS` = '{$db->escape($os_name)}',
         `Cookie_ID` = '{$db->escape($CookieID)}',
         `Catalogue_ID` = '{$catalogue}',
         `Subdivision_ID` = '{$sub}',
         `Sub_Class_ID` = '{$cc}',
         `Message_ID` = '{$saved_message_id}',
         `Action` = '{$db->escape($action)}'"
    );

    Stats_UpdateSearchPhrases();
}

function Stats_UpdateSearchPhrases() {
    global $HTTP_REFERER, $catalogue;

    $catalogue = (int)$catalogue;
    $nc_core = nc_Core::get_object();

    $search_domains = array(
        'www.yandex.ru', 'pda.yandex.ru', 'large.yandex.ru', // 0 1 2
        'family.yandex.ru', 'www.rambler.ru', 'search.rambler.ru', // 3 4 5
        'sm.aport.ru', 'go.mail.ru', 'search.msn.com', // 6 7 8
        'search.yahoo.com', 'www.altavista.com', 'www.google.ru', // 9 10 11
        'www.google.com', 'www.alltheweb.com', 'yandex.ru', // 12 13 14
        'nova.rambler.ru', 'gogo.ru', 'nigma.ru'
    ); //15 16 17

    $search_queries = array(
        'text', 'query', 'query', 'text', 'words', 'words',
        'r', 'q', 'q', 'p', 'q', 'q', 'q', 'q', 'text', 'exclude', 'q', 's'
    );

    $url = parse_url($HTTP_REFERER);

    if (!isset($url['host']) || !isset($url['query'])) {
        return;
    }

    $search_domains_count = count($search_domains);

    for ($i = 0; $i < $search_domains_count; $i++) {
        $domain = $search_domains[$i];
        $query = $search_queries[$i];

        if ($domain === $url['host']) {
            $args = array();
            parse_str($url['query'], $args);

            if (isset($args[$query]) && strlen($args[$query]) > 0) {
                $now_date = date('Y-m-d');
                $search_phrase = urldecode($args[$query]);

                // For AllTheWeb
                if ($i == 13) {
                    $search_phrase = html_entity_decode($search_phrase, ENT_QUOTES, 'cp1251');
                }

                // For Google, Altavista, MSN, Yahoo, Yandex, Nigma UTF-8
                if (in_array($i, array(0, 1, 2, 3, 14, 11, 12, 10, 8, 9, 15, 17), true)) {
                    //$search_phrase = iconv("UTF-8", "cp1251", $search_phrase);
                    //$search_phrase = stats_EncodeUTF($search_phrase, "w");
                    require_once 'utf8/utf8.php';
                    $search_phrase = nc_utf2win($search_phrase);
                }

                $search_phrase = $nc_core->db->escape($search_phrase);

                $nc_core->db->query(
                    "SELECT `Hits`
                     FROM `Stats_Phrases`
                     WHERE `Date` = '{$now_date}'
                     AND `Phrase` = '{$nc_core->db->escape($search_phrase)}'
                     AND `Catalogue_ID` = '{$catalogue}'"
                );

                if ($nc_core->db->num_rows == 0) {
                    $nc_core->db->query(
                        "INSERT INTO `Stats_Phrases`
                         (`Date`, `Phrase`, `Hits`, `Catalogue_ID`)
                         VALUES
                         ('{$now_date}', '{$nc_core->db->escape($search_phrase)}', '1', '{$catalogue}')"
                    );
                } else {
                    $nc_core->db->query(
                        "UPDATE `Stats_Phrases`
                         SET `Hits` = `Hits` + 1
                         WHERE `Date` = '{$now_date}'
                         AND `Phrase` = '{$nc_core->db->escape($search_phrase)}'
                         AND `Catalogue_ID` = '{$catalogue}'"
                    );
                }
            }
        }
    }
}

function Stats_IP2Country($ip) {

    $nc_core = nc_Core::get_object();
    $ip_long = (float)sprintf('%u', ip2long($ip));

    // Exclude LAN address
    if (
        ( 167772160 < $ip_long && $ip_long < 184549375) ||
        (2886729728 < $ip_long && $ip_long < 2887778303) ||
        (3232235520 < $ip_long && $ip_long < 3232301055)
    ) {
        return '';
    }

    $nets = explode('.', $ip);

    return $nc_core->db->get_var(
        "SELECT `Country`
         FROM `Stats_IP2Country`
         WHERE `IP_Net` = '{$nets[0]}'
         AND '{$ip_long}' BETWEEN `IP_Range_Start` AND `IP_Range_End`"
    );
}
