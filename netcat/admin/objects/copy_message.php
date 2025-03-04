<?
/* $Id: copy_message.php 5946 2012-01-17 10:44:36Z denis $ */
// выбор связанной записи из MessageXX ($relation_class)

require("./head.php");
?>
<body>
<table id='wrapperTable' width='100%' height='100%' border='0' cellpadding='0' cellspacing='0' border='0'>
    <tr height='0' class='top_row'>
        <td id='topLeftPane' width='35%'></td>
        <td width='7'></td>
        <td id='topMainContainer' width='75%'></td>
    </tr>
    <td id='leftPane' height='100%' bgcolor='#FAFAF9'>
        <iframe name='treeIframe' id='treeIframe' src='<?= $ADMIN_PATH ?>tree_frame.php?mode=copy_message&cc=<?= $cc ?>&classID=<?= $classID ?>&message=<?= $message ?>' height='100%' width='100%' frameborder='0'></iframe>
    </td>
    <td id='slider' width='7' height='100%' valign='top'>
        <div id='slideBar'></div>
    </td>
    <td height='100%' id='mainContainer'>
        <iframe id='subViewIframe' src='' width='100%' height='100%' frameborder='0' border='0'></iframe>
    </td>
</table>
</body>
</html>