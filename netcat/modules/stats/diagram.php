<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ROOT_FOLDER.'connect_io.php');
require ($ADMIN_FOLDER.'function.inc.php');


if (!($perm->isAccess(array(NC_PERM_MODULE, 'stats')) || $perm->isGuest())) {
    exit;
}

define('IMG_WIDTH', 590);
define('IMG_HEIGHT', 420);
define('DIAGRAM_X', 40);
define('DIAGRAM_Y', 20);
define('DIAGRAM_WIDTH', IMG_WIDTH - 53);
define('DIAGRAM_HEIGHT', IMG_HEIGHT - 51);
define('AXIS_OFFSET_X', 5);
define('AXIS_OFFSET_Y', 5);

$nc_core = nc_core::get_object();
$cat_id = (int)$nc_core->input->fetch_get('cat_id');

$num_vert = 14;
$num_horiz = 14;


header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-type: image/png');

$img = imagecreatetruecolor(IMG_WIDTH, IMG_HEIGHT);
drawDiagram($img);
imageinterlace($img, 1);
imagepng($img);
imagedestroy($img);

function drawDiagram($img) {
    global $num_horiz;

    $axes_color = imagecolorallocate($img, 0, 0, 0);
    $grid_color = imagecolorallocate($img, 204, 204, 204);
    $caption_color = imagecolorallocate($img, 0, 0, 0);
    $border_color = imagecolorallocate($img, 204, 204, 204);
    $background_color = imagecolorallocate($img, 255, 255, 255);

    $host_color = imagecolorallocate($img, 241, 29, 29);
    $hits_color = imagecolorallocate($img, 20, 203, 80);
    $visitors_color = imagecolorallocate($img, 29, 59, 241);

    $end_date = getLastDate();
    $start_date = strtotime("-28 day", $end_date);
    $max_data = getMaxData($start_date, $end_date);
    if ($max_data < 14 && $max_data != 0) {
        $num_horiz = $max_data;
    }

    imagefill($img, 0, 0, $background_color);
    imagerectangle($img, 0, 0, IMG_WIDTH - 1, IMG_HEIGHT - 1, $border_color);

    drawAxes($img, $axes_color);
    drawGrid($img, $grid_color);
    drawCaptions($img, $start_date, $max_data, $caption_color);
    drawGraphs($img, $start_date, $end_date, $max_data, $host_color, $hits_color, $visitors_color);
}

function drawAxes($img, $color) {
    imageline($img, DIAGRAM_X, DIAGRAM_Y, DIAGRAM_X, DIAGRAM_Y + DIAGRAM_HEIGHT, $color);
    imageline($img, DIAGRAM_X, DIAGRAM_Y + DIAGRAM_HEIGHT, DIAGRAM_X + DIAGRAM_WIDTH, DIAGRAM_Y + DIAGRAM_HEIGHT, $color);
}

function drawGrid($img, $color) {
    global $num_vert, $num_horiz;

    $k = (int) floor((DIAGRAM_WIDTH - AXIS_OFFSET_X) / $num_vert);
    $j = (int) floor((DIAGRAM_HEIGHT - AXIS_OFFSET_Y) / $num_horiz);

    for ($i = 1; $i <= $num_vert; $i++) {
        imageline($img, DIAGRAM_X + ($k * $i), DIAGRAM_Y + AXIS_OFFSET_Y, DIAGRAM_X + ($k * $i), DIAGRAM_Y + DIAGRAM_HEIGHT - 1, $color);
    }

    for ($i = 0; $i < $num_horiz; $i++) {
        imageline($img, DIAGRAM_X + 1, DIAGRAM_Y + AXIS_OFFSET_Y + ($j * $i), DIAGRAM_X + DIAGRAM_WIDTH - AXIS_OFFSET_X, DIAGRAM_Y + AXIS_OFFSET_Y + ($j * $i), $color);
    }
}

function drawCaptions($img, $start_date, $max_data, $color) {
    global $num_vert, $num_horiz;

    $k = (int) floor((DIAGRAM_WIDTH - AXIS_OFFSET_X) / $num_vert);
    $j = (int) floor((DIAGRAM_HEIGHT - AXIS_OFFSET_Y) / $num_horiz);

    for ($i = 0; $i <= $num_vert; $i++) {
        $caption = date("d", $start_date).".".date("m", $start_date);
        imagestring($img, 2, DIAGRAM_X + ($k * $i) - 15, DIAGRAM_HEIGHT + 25, $caption, $color);
        $start_date = strtotime("+2 day", $start_date);
    }

    $l = (int) ($max_data / $num_horiz);
    $l_m = (int) ($max_data % $num_horiz);

    for ($i = 0; $i <= $num_horiz; $i++) {
        imagestring($img, 2, DIAGRAM_X - 33, DIAGRAM_Y + AXIS_OFFSET_Y + ($j * $i) - 5, $max_data, $color);
        if ($l_m != 0 && $i == 0) $max_data -= $l_m;

        $max_data -= $l;
    }
}

function drawGraphs($img, $start_date, $end_date, $max_data, $host_color, $hits_color, $visitors_color) {
    global $db, $cat_id;
    global $num_vert, $num_horiz;

    $k = (int) floor(((DIAGRAM_WIDTH - AXIS_OFFSET_X) / $num_vert) / 2);
    $j = (int) ($max_data / $num_horiz);

    $s = "SELECT UNIX_TIMESTAMP(Date), SUM(NewHosts), SUM(Hits), SUM(NewVisitors) ";
    $s .= "FROM Stats_Attendance ";
    $s .= "WHERE Catalogue_ID='${cat_id}' AND ";
    $s .= "(UNIX_TIMESTAMP(Date) BETWEEN '${start_date}' AND '${end_date}') ";
    $s .= "GROUP BY Date ORDER BY Date";

    $q = $db->get_results($s, ARRAY_N);
    if ($db->num_rows != 0) {
        $x = $y = $y_1 = $y_2 = 0;
        foreach ($q as $row) {
            $l = 0;
            $p = $start_date;
            while ($p != $row[0]) {
                $p = strtotime("+1 day", $p);
                $l += $k;
            }

            $x_0 = DIAGRAM_X + $l;
            $y_0 = DIAGRAM_HEIGHT + DIAGRAM_Y - ((350 / $max_data) * $row[1]);
            $y_0_1 = DIAGRAM_HEIGHT + DIAGRAM_Y - ((350 / $max_data) * $row[2]);
            $y_0_2 = DIAGRAM_HEIGHT + DIAGRAM_Y - ((350 / $max_data) * $row[3]);

            if ($x == 0 && $y == 0 && $y_1 == 0 && $y_2 == 0) {
                $x = $x_0;
                $y = $y_0;
                $y_1 = $y_0_1;
                $y_2 = $y_0_2;
            }

            imageline($img, $x_0, $y_0, $x, $y, $host_color);
            imageline($img, $x_0, $y_0_1 - 13, $x, $y_1 - 13, $hits_color);
            $x = $x_0;
            $y = $y_0;
            $y_1 = $y_0_1;
            $y_2 = $y_0_2;
        }
    }
}

function getLastDate() {
    global $db, $cat_id;

    $s = "SELECT UNIX_TIMESTAMP(Date) FROM Stats_Attendance ";
    $s .= "WHERE Catalogue_ID='{$cat_id}' ORDER BY Date DESC LIMIT 1";
    $q = $db->get_var($s);

    $result = mktime(0, 0, 0, date("n"), date("j"), date("Y"));

    if ($db->num_rows != 0) {
        $result = $q;
    }

    return $result;
}

function getMaxData($start_date, $end_date) {
    global $db, $cat_id;

    $s = "SELECT SUM(NewHosts), SUM(Hits), SUM(NewVisitors) ".
            "FROM Stats_Attendance WHERE Catalogue_ID='${cat_id}' AND ".
            "(UNIX_TIMESTAMP(Date) BETWEEN '${start_date}' AND '${end_date}') ".
            "GROUP BY Date";

    $result = 0;
    $q = $db->get_results($s, ARRAY_N);
    if ($db->num_rows != 0) {
        $i = 0;
        $tmp = array();
        foreach ($q as $res) {
            $result = max($res[0], $res[1], $res[2]);
            $tmp[$i] = $result;
            $i++;
        }
        $result = max($tmp);
    }

    return $result;
}
