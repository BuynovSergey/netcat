<?php
/* Restores uploaded backup
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title><? $str = get_tr(); echo $str['header']; ?></title>
</head>
<body>

<?php
if (isset($_POST["execute_backup_restore"]) && intval($_POST["execute_backup_restore"])===1) {
    execute_backup_restore($dump_options);
}
else show_form($dump_options);
?>

</body>
</html>

