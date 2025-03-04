<?php

/* $Id: select_subclass_list.php 7727 2012-07-19 12:40:50Z ewind $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require($ADMIN_FOLDER . "function.inc.php");

require_once($INCLUDE_FOLDER . "s_common.inc.php");

$sub = (int)$sub;
$message = (int)$message;
$classID = (int)$classID;
?>

<html>
<head>
    <title>Subclass list</title>
    <link type='text/css' rel='Stylesheet' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/sprites.css') ?>'>
    <link type='text/css' rel='Stylesheet' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/admin.css') ?>'>
    <link type='text/css' rel='Stylesheet' href='<?= nc_add_revision_to_url($ADMIN_TEMPLATE . 'css/main.css') ?>'>
</head>
<body class='nc-subclass-list'>
<?php

$sql = "SELECT * FROM Sub_Class WHERE Subdivision_ID = '" . $sub . "' AND Class_ID = '" . $classID . "'";
$cc_list = $db->get_results($sql, ARRAY_A);

if ($cc_list) {
    echo "<div class='related_list'>";
    foreach ($cc_list as $v) {
        ?>
        <table width='100%'>
            <tr>
                <td width='99%'><span class='id'><?= $v['Sub_Class_ID']; ?></span> <?= $v['Sub_Class_Name']; ?></td>
                <td>
                    <a href='#' onclick='top.copyItem(<?= $v['Subdivision_ID']; ?>, <?= $v['Sub_Class_ID']; ?>, <?= $classID; ?>, <?= $message; ?>, 0)' title='<?= NETCAT_MODERATION_COPY_HERE_RELATED; ?>'>
                        <div class='icons icon_copy'></div>
                    </a>
                </td>
                <td>
                    <a href='#' onclick='top.copyItem(<?= $v['Subdivision_ID']; ?>, <?= $v['Sub_Class_ID']; ?>, <?= $classID; ?>, <?= $message; ?>, 1)' title='<?= NETCAT_MODERATION_MOVE_HERE_RELATED; ?>'>
                        <div class='icons icon_fm_download'></div>
                    </a>
                </td>
            </tr>
        </table>
    <?php
    }
    echo "</div>";
} else {
    nc_print_status(NETCAT_MODERATION_RELATED_NO_ANY_CLASS_IN_SUB, 'info');
} ?>
</body>
</html>