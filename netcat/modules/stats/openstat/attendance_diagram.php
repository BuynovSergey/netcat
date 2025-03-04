<?php

/* $Id: attendance_diagram.php 4290 2011-02-23 15:32:35Z denis $ */

// set this constant to 1 if your hosting is bad
define("BAD_HOSTING", 0);

function imagesmoothline($image, $x1, $y1, $x2, $y2, $color) { // сглаживание
    if (BAD_HOSTING) {
        imageline($image, $x1, $y1, $x2, $y2, $color);
        return;
    }

    $colors = imagecolorsforindex($image, $color);
    if ($x1 == $x2) {
        imageline($image, $x1, $y1, $x2, $y2, $color); // вертикальная линия
    } elseif ($y1 == $y2) {
        imageline($image, $x1, $y1, $x2, $y2, $color); // горизонтальная линия
    } else {
        $m = ($y2 - $y1) / ($x2 - $x1);
        $b = $y1 - $m * $x1;
        if (abs($m) <= 1) {
            $x = min($x1, $x2);
            $endx = max($x1, $x2);
            while ($x <= $endx) {
                $y = $m * $x + $b;
                $ya = $y == floor($y) ? 1 : ($y - floor($y));
                $yb = ceil($y) - $y;
                $tempcolors = imagecolorsforindex($image, imagecolorat($image, $x, floor($y)));
                $tempcolors['red'] = $tempcolors['red'] * $ya + $colors['red'] * $yb;
                $tempcolors['green'] = $tempcolors['green'] * $ya + $colors['green'] * $yb;
                $tempcolors['blue'] = $tempcolors['blue'] * $ya + $colors['blue'] * $yb;
                if (imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']) == -1) {
                    imagecolorallocate($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']);
                }
                imagesetpixel($image, $x, floor($y), imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']));
                $tempcolors = imagecolorsforindex($image, imagecolorat($image, $x, ceil($y)));
                $tempcolors['red'] = $tempcolors['red'] * $yb + $colors['red'] * $ya;
                $tempcolors['green'] = $tempcolors['green'] * $yb + $colors['green'] * $ya;
                $tempcolors['blue'] = $tempcolors['blue'] * $yb + $colors['blue'] * $ya;
                if (imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']) == -1) {
                    imagecolorallocate($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']);
                }
                imagesetpixel($image, $x, ceil($y), imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']));
                $x++;
            }
        } else {
            $y = min($y1, $y2);
            $endy = max($y1, $y2);
            while ($y <= $endy) {
                $x = ($y - $b) / $m;
                $xa = $x == floor($x) ? 1 : ($x - floor($x));
                $xb = ceil($x) - $x;
                $tempcolors = imagecolorsforindex($image, imagecolorat($image, floor($x), $y));
                $tempcolors['red'] = $tempcolors['red'] * $xa + $colors['red'] * $xb;
                $tempcolors['green'] = $tempcolors['green'] * $xa + $colors['green'] * $xb;
                $tempcolors['blue'] = $tempcolors['blue'] * $xa + $colors['blue'] * $xb;
                if (imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']) == -1) {
                    imagecolorallocate($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']);
                }
                imagesetpixel($image, floor($x), $y, imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']));
                $tempcolors = imagecolorsforindex($image, imagecolorat($image, ceil($x), $y));
                $tempcolors['red'] = $tempcolors['red'] * $xb + $colors['red'] * $xa;
                $tempcolors['green'] = $tempcolors['green'] * $xb + $colors['green'] * $xa;
                $tempcolors['blue'] = $tempcolors['blue'] * $xb + $colors['blue'] * $xa;
                if (imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']) == -1) {
                    imagecolorallocate($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']);
                }
                imagesetpixel($image, ceil($x), $y, imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']));
                $y++;
            }
        }
    }
}

function imageBoldLine($resource, $x1, $y1, $x2, $y2, $Color, $BoldNess = 2, $func = 'imageLine') {
    $center = round($BoldNess / 2);
    for ($i = 0; $i < $BoldNess; $i++) {
        $a = $center - $i;
        if ($a < 0) {
            $a -= $a;
        }
        for ($j = 0; $j < $BoldNess; $j++) {
            $b = $center - $j;
            if ($b < 0) {
                $b -= $b;
            }
            $c = sqrt($a * $a + $b * $b);
            if ($c <= $BoldNess) {
                $func($resource, $x1 + $i, $y1 + $j, $x2 + $i, $y2 + $j, $Color);
            }
        }
    }
}

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once $NETCAT_FOLDER . 'vars.inc.php';
require $ROOT_FOLDER . 'connect_io.php';
require $ADMIN_FOLDER . 'function.inc.php';
require_once nc_module_folder('stats') . 'openstat/openstat_core_class.php';
$ttf_font_file = $nc_core->INCLUDE_FOLDER . 'font/default.ttf';

$lang = $nc_core->lang->detect_lang(1);

if (!$perm->isSupervisor()) {
    exit;
}

global $nc_core;

$openstat = new nc_Openstat_core_class($nc_core->get_settings('Openstat_Login', 'stats'), $nc_core->get_settings('Openstat_Password', 'stats'));

$start_date = intval($start_date);
$end_date = intval($end_date);
$time_offset = intval($time_offset);
$counter_id = intval($counter_id);
$width = intval($width);
$width = $width > 640 ? $width : 640;

$columns = array("0%0Dvisitors_sum", "0%0Dsessions_sum", "0%0Dpageviews_sum");
$report = $openstat->get_counter_report($counter_id, "Attendance", $start_date + $time_offset, $end_date + $time_offset, $level_of_detailing, $columns, 0, $lang);

if (!$report) {
    echo "error";
    exit;
}

$y_min = $y_max = 0;
$items = array();

foreach ($report['item'] as $item) {
    $visitors_sum = $item['c'][0] === 'nan' ? 0 : +$item['c'][0];
    $sessions_sum = $item['c'][1] === 'nan' ? 0 : +$item['c'][1];
    $page_views_sum = $item['c'][2] === 'nan' ? 0 : +$item['c'][2];
    $timestamp = strtotime($item['v']);

    $items[] = array($visitors_sum, $sessions_sum, $page_views_sum, $timestamp);
    if ($visitors_sum < $y_min) {
        $y_min = $visitors_sum;
    }
    if ($visitors_sum > $y_max) {
        $y_max = $visitors_sum;
    }
    if ($sessions_sum < $y_min) {
        $y_min = $sessions_sum;
    }
    if ($sessions_sum > $y_max) {
        $y_max = $sessions_sum;
    }
    if ($page_views_sum < $y_min) {
        $y_min = $page_views_sum;
    }
    if ($page_views_sum > $y_max) {
        $y_max = $page_views_sum;
    }
}

unset($report['item']);

define('IMG_WIDTH', $width);
define('IMG_HEIGHT', 150);
define('DIAGRAM_X', 17 + strlen($y_max) * 6);
define('DIAGRAM_Y', 17);
define('DIAGRAM_WIDTH', IMG_WIDTH - DIAGRAM_X - 140);
define('DIAGRAM_HEIGHT', IMG_HEIGHT - DIAGRAM_Y - 38);
define('MAX_NUM_VERT', 26);
define('MAX_NUM_HORIZ', 6);

$num_vert = 26;
$num_horiz = 20;

$y_delta = $y_max - $y_min;
$y_count = $y_delta < MAX_NUM_HORIZ ? $y_delta : MAX_NUM_HORIZ;

$x_count = count($items);
if ($x_count > MAX_NUM_VERT) {
    do {
        $x_count = ($x_count + 1) / 2;
    } while ($x_count > MAX_NUM_VERT);
}

while (ob_get_level() && @ob_end_clean()) {
    continue;
}

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-type: image/png');
$img = imagecreatetruecolor(IMG_WIDTH, IMG_HEIGHT);

$axes_color = imagecolorallocate($img, 0, 0, 0);
$grid_color = imagecolorallocate($img, 233, 233, 233);
$caption_color = imagecolorallocate($img, 0, 0, 0);
$border_color = imagecolorallocate($img, 204, 204, 204);
$background_color = imagecolorallocate($img, 255, 255, 255);

$color1 = imagecolorallocate($img, 29, 59, 241);
$color2 = imagecolorallocate($img, 241, 29, 29);
$color3 = imagecolorallocate($img, 26, 180, 26);

imagefill($img, 0, 0, $background_color);
imagerectangle($img, 0, 0, IMG_WIDTH - 1, IMG_HEIGHT - 1, $border_color);

drawCaptions($img, $start_date, $end_date, $y_min, $y_max, $x_count, $y_count, $caption_color, $grid_color);
drawAxes($img, $axes_color);
drawGraphs($img, $items, $y_delta, $color1, $color2, $color3);
drawLegend($img, $caption_color, $color1, $color2, $color3, NETCAT_MODULE_STATS_OPENSTAT_VISITORS, NETCAT_MODULE_STATS_OPENSTAT_SESSIONS, NETCAT_MODULE_STATS_OPENSTAT_PAGEVIEWS);
imageinterlace($img, 1);
imagepng($img);
imagedestroy($img);

function drawAxes($img, $color) {
    imageline($img, DIAGRAM_X, DIAGRAM_Y - 10, DIAGRAM_X, DIAGRAM_Y + DIAGRAM_HEIGHT, $color);
    imageline($img, DIAGRAM_X, DIAGRAM_Y + DIAGRAM_HEIGHT, DIAGRAM_X + DIAGRAM_WIDTH + 10, DIAGRAM_Y + DIAGRAM_HEIGHT, $color);
}

function drawCaptions($img, $start_date, $end_date, $y_min, $y_max, $x_count, $y_count, $text_color, $grind_color) {
    global $level_of_detailing, $ttf_font_file, $nc_core;
    $y_start = DIAGRAM_Y + DIAGRAM_HEIGHT;

    $date_step = round(($end_date - $start_date) / ($x_count - 1));
    $y_step = $y_count ? (($y_max - $y_min) / $y_count) : 0;

    $k = DIAGRAM_WIDTH / ($x_count - 1);
    $j = $y_count ? (DIAGRAM_HEIGHT / $y_count) : 0;

    $vert_end_y = DIAGRAM_Y + DIAGRAM_HEIGHT;

    imageline($img, DIAGRAM_X + DIAGRAM_WIDTH + 15, 0, DIAGRAM_X + DIAGRAM_WIDTH + 15, IMG_HEIGHT, $grind_color);

    for ($i = 0; $i < $x_count; $i++) {
        $x = DIAGRAM_X + round($k * $i);
        $date = $start_date + $date_step * $i;
        if ($x > DIAGRAM_X + DIAGRAM_WIDTH) {
            $x = DIAGRAM_X + DIAGRAM_WIDTH;
            $date = $end_date;
        }
        imageline($img, $x, DIAGRAM_Y, $x, $vert_end_y, $grind_color);

        switch ($level_of_detailing) {
            case "month" :  // "авг.\n2011"
                $monthmonth = $nc_core->NC_UNICODE ? constant("NETCAT_MODULE_STATS_OPENSTAT_SHORT_MONTH_" . date('n', $date)) : $nc_core->utf8->win2utf(constant("NETCAT_MODULE_STATS_OPENSTAT_SHORT_MONTH_" . date('n', $date)));
                imagettftext($img, 9, 0, $x - 10, $vert_end_y + 15, $text_color, $ttf_font_file, $monthmonth);
                imagettftext($img, 8, 0, $x - 11, $vert_end_y + 26, $text_color, $ttf_font_file, date('Y', $date));
                break;
            case "hour" :  // "12:00" - по верт.
                $caption = date("H:00", $date);
                imagestringup($img, 2, $x - 7, $vert_end_y + 4 + strlen($caption) * 6, $caption, $text_color);
                break;
            default :  // "12\nавг."
                $caption = date('j', $date);
                $monthday = $nc_core->NC_UNICODE ? constant("NETCAT_MODULE_STATS_OPENSTAT_SHORT_MONTH_" . date('n', $date)) : $nc_core->utf8->win2utf(constant("NETCAT_MODULE_STATS_OPENSTAT_SHORT_MONTH_" . date('n', $date)));
                imagettftext($img, 8, 0, $x - 7, $vert_end_y + 15, $text_color, $ttf_font_file, $caption);
                imagettftext($img, 9, 0, $x - 10, $vert_end_y + 26, $text_color, $ttf_font_file, $monthday);
                break;
        }
    }

    $horiz_end_x = DIAGRAM_X + DIAGRAM_WIDTH;

    for ($i = 0; $i <= $y_count; $i++) {
        $y = $y_start - round($j * $i);
        imageline($img, DIAGRAM_X, $y, $horiz_end_x, $y, $grind_color);
        $caption = $y_min + round($y_step * $i);
        imagestring($img, 2, DIAGRAM_X - strlen($caption) * 6 - 4, $y - 8, $caption, $text_color);
    }
}

function drawGraphs($img, $items, $y_delta, $color1, $color2, $color3) {
    $count = count($items);
    $y_koeff = $y_delta ? (DIAGRAM_HEIGHT / $y_delta) : 0;
    $x_koeff = DIAGRAM_WIDTH / ($count - 1);
    $y_start = DIAGRAM_Y + DIAGRAM_HEIGHT;

    $old_x[0] = DIAGRAM_X;
    $old_x[1] = DIAGRAM_X;
    $old_x[2] = DIAGRAM_X;
    $old_y[0] = round($y_start - $items[0][0] * $y_koeff);
    $old_y[1] = round($y_start - $items[0][1] * $y_koeff);
    $old_y[2] = round($y_start - $items[0][2] * $y_koeff);

    for ($i = 1; $i < $count; $i++) {
        $x = round(DIAGRAM_X + $x_koeff * $i);
        $y = round($y_start - $items[$i][2] * $y_koeff);
        imagesmoothline($img, $old_x[2], $old_y[2], $x, $y, $color3);
        $old_x[2] = $x;
        $old_y[2] = $y;

        $x = round(DIAGRAM_X + $x_koeff * $i);
        $y = round($y_start - $items[$i][1] * $y_koeff);
        imagesmoothline($img, $old_x[1], $old_y[1], $x, $y, $color2);
        $old_x[1] = $x;
        $old_y[1] = $y;

        $x = round(DIAGRAM_X + $x_koeff * $i);
        $y = round($y_start - $items[$i][0] * $y_koeff);
        imageBoldLine($img, $old_x[0], $old_y[0], $x, $y, $color1, 1.5, "imagesmoothline");
        $old_x[0] = $x;
        $old_y[0] = $y;
    }
}

function drawLegend($img, $caption_color, $color1, $color2, $color3, $caption1, $caption2, $caption3) {
    global $ttf_font_file, $nc_core;

    $y_step = 40;
    $base_x = DIAGRAM_WIDTH + DIAGRAM_X + 25;

    $y = DIAGRAM_Y + 25;
    $x = $base_x + 12 + nc_strlen($caption1) * 7;
    if (!$nc_core->NC_UNICODE) {
        $caption1 = $nc_core->utf8->win2utf($caption1);
    }
    imagettftext($img, 10, 0, $base_x, $y, $caption_color, $ttf_font_file, $caption1);
    imageBoldLine($img, $x, $y - 5, $x + 15, $y - 5, $color1, 3);

    $y = $y + $y_step;
    $x = $base_x + 12 + nc_strlen($caption2) * 7;
    if (!$nc_core->NC_UNICODE) {
        $caption2 = $nc_core->utf8->win2utf($caption2);
    }
    imagettftext($img, 10, 0, $base_x, $y, $caption_color, $ttf_font_file, $caption2);
    imageBoldLine($img, $x, $y - 5, $x + 15, $y - 5, $color2, 3);

    $y = $y + $y_step;
    $x = $base_x + 12 + nc_strlen($caption3) * 7;
    if (!$nc_core->NC_UNICODE) {
        $caption3 = $nc_core->utf8->win2utf($caption3);
    }
    imagettftext($img, 10, 0, $base_x, $y, $caption_color, $ttf_font_file, $caption3);
    imageBoldLine($img, $x, $y - 5, $x + 15, $y - 5, $color3, 3);
}

?>