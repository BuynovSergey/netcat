<?php

/* $Id: pie.php 6210 2012-02-10 10:30:32Z denis $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ROOT_FOLDER.'connect_io.php');
require ($ADMIN_FOLDER.'function.inc.php');

if (!($perm->isAccess(array(NC_PERM_MODULE, 'stats')) || $perm->isGuest())) {
    exit;
}

define('IMG_WIDTH', 196);
define('IMG_HEIGHT', 196);
define('PIE_C_X', 97);
define('PIE_C_Y', 97);
define('PIE_W', 190);
define('PIE_H', 190);

function getStatsData() {
    global $db;
    global $phase, $cat_id;
    global $date_start_y, $date_start_m, $date_start_d;
    global $date_end_y, $date_end_m, $date_end_d;

    $phase += 0;
    $date_start = $date_start_y."-".$date_start_m."-".$date_start_d;
    $date_end = $date_end_y."-".$date_end_m."-".$date_end_d;

    $result = array();

    switch ($phase) {
        case 1:           // IP
            $total = $db->get_var("SELECT SUM(Hits) FROM Stats_IP WHERE Catalogue_ID='".$cat_id."' AND Date>='".$date_start."' AND Date<='".$date_end."'");

            $total += 0;

            $res = $db->get_results("SELECT IP, SUM(Hits) AS Hits, ((SUM(Hits)*100)/$total) FROM Stats_IP WHERE Catalogue_ID='".$cat_id."' AND Date>='".$date_start."' AND Date<='".$date_end."' GROUP BY IP ORDER BY Hits DESC LIMIT 10", ARRAY_N);
            if (!($count = $db->num_rows)) return NULL;

            $top_ten_percentage = 0;

            for ($i = 0; $i < $count; $i++) {
                list($ip, $hits, $percent) = $res[$i];
                $result[$i][0] = $ip;
                $result[$i][1] = (float) $percent;
                $top_ten_percentage += (float) $percent;
            }

            // Остальные
            if ($top_ten_percentage < 98) {
                $result[$i][0] = 'REST';
                $result[$i][1] = 100 - $top_ten_percentage;
            }

            break;

        case 2: // OS
            $total = $db->get_var("SELECT SUM(Visitors) FROM Stats_OS WHERE Catalogue_ID='".$cat_id."' AND Date>='".$date_start."' AND Date<='".$date_end."'");

            $total += 0;

            $res = $db->get_results("SELECT OS,SUM(Visitors) AS Visitors,((SUM(Visitors)*100)/$total) FROM Stats_OS WHERE Catalogue_ID='".$cat_id."' AND Date>='".$date_start."' AND Date<='".$date_end."' GROUP BY OS ORDER BY Visitors DESC LIMIT 10", ARRAY_N);
            if (!$count = $db->num_rows) return NULL;

            $top_ten_percentage = 0;

            for ($i = 0; $i < $count; $i++) {
                list($os, $visitors, $percent) = $res[$i];
                $result[$i][0] = $os;
                $result[$i][1] = (float) $percent;
                $top_ten_percentage += (float) $percent;
            }

            // Остальные
            if ($top_ten_percentage < 98) {
                $result[$i][0] = 'REST';
                $result[$i][1] = 100 - $top_ten_percentage;
            }

            break;

        case 3: // Browsers

            $total = $db->get_var("SELECT SUM(Visitors) FROM Stats_Browser WHERE Catalogue_ID='".$cat_id."' AND Date>='".$date_start."' AND Date<='".$date_end."'");

            $total += 0;

            $res = $db->get_results("SELECT Browser,SUM(Visitors) AS Visitors,((SUM(Visitors)*100)/$total) FROM Stats_Browser WHERE Catalogue_ID='".$cat_id."' AND Date>='".$date_start."' AND Date<='".$date_end."' GROUP BY Browser ORDER BY Visitors DESC LIMIT 10", ARRAY_N);
            if (!$count = $db->num_rows) return NULL;

            $top_ten_percentage = 0;

            for ($i = 0; $i < $count; $i++) {
                list($browser, $visitors, $percent) = $res[$i];
                $result[$i][0] = $browser;
                $result[$i][1] = (float) $percent;
                $top_ten_percentage += (float) $percent;
            }

            // Остальные
            if ($top_ten_percentage < 98) {
                $result[$i][0] = 'REST';
                $result[$i][1] = 100 - $top_ten_percentage;
            }

            break;
    }

    return $result;
}

function getGradsSum($grads_a) {
    for ($i = 0; $i < count($grads_a); $i++) {
        $grads += $grads_a[$i];
    }

    return $grads;
}

function drawStatsPie($img, $r_data, $colors, $border_color) {
    $grads_a = array();
    if ($r_data == NULL) return;

    for ($i = 0; $i < count($r_data); $i++) {
        $grads_a[$i] = (int) round((360 * $r_data[$i][1]) / 100);
    }

    $grads = getGradsSum($grads_a);

    if ($grads != 360) {
        $grads = 0;

        while ($grads != 360) {
            $rnd_i = rand(0, count($grads_a) - 1);

            if ($grads < 360) $grads_a[$rnd_i] += 1;
            elseif ($grads > 360) $grads_a[$rnd_i] -= 1;

            $grads = getGradsSum($grads_a);
        }
    }

    for ($i = 0, $j = 0, $k = 0; $i < count($grads_a); $i++) {
        $grad = $grads_a[$i];
        $k += (int) $grad;
        imagefilledarc($img, PIE_C_X, PIE_C_Y, PIE_W, PIE_H, $j, $k, $colors[$i], IMG_ARC_PIE);
        imagefilledarc($img, PIE_C_X, PIE_C_Y, PIE_W, PIE_H, $j, $k, $border_color, IMG_ARC_EDGED | IMG_ARC_NOFILL);
        $j += (int) $grad;
    }
}

$tenColors = array(0 => array(101, 138, 182), array(197, 74, 92), array(142, 91, 150),
        array(150, 206, 23), array(83, 186, 157), array(204, 177, 6), array(231, 158, 1),
        array(213, 114, 55), array(51, 178, 210), array(179, 38, 116),
        array(168, 168, 168)); // eleventh color for 'REST' sector

$tenAllocColors = array();

$img = imagecreatetruecolor(IMG_WIDTH, IMG_HEIGHT);

// Allocate 10 colors
for ($i = 0; $i < 11; $i++) {
    $tenAllocColors[$i] = imagecolorallocate($img, $tenColors[$i][0], $tenColors[$i][1],
                    $tenColors[$i][2]);
}

$text_color = imagecolorallocate($img, 0, 0, 0);
$background_color = imagecolorallocate($img, 255, 255, 255);
$border_color = imagecolorallocate($img, 150, 150, 150);

imagefill($img, 0, 0, $background_color);

$r_data = getStatsData();
drawStatsPie($img, $r_data, $tenAllocColors, $border_color);

imageinterlace($img, 1);

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-type: image/png');

imagepng($img);
imagedestroy($img);
?>